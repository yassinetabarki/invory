<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderUnitsChange;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductSizeStock;
use App\Services\Orders\OrderMessages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Mark delivered, its move back and Cancel units on one order line (INV-14, AC-3 and AC-4; ADR-0026), per colour and size.
 *
 * One transaction. Lock order, as Edit order and the restocks take them: the order row → the line's product → the line → its rows → the
 * product's shelf rows. Every limit is checked on what is read under those locks, never on the page's figures, and every refusal is
 * reported at once; nothing is written when one is found.
 *
 * - **Deliver** takes each cell off the shelf (a row that reaches 0 is deleted, ADR-0017) and adds it to `delivered`. A cell can't
 *   deliver more than it has To deliver nor more than the shelf holds.
 * - **Move back** takes each cell off `delivered` and puts it on the shelf again (a row made when there is none; the colour linked to
 *   the product again when it was taken off since, as the PostgreSQL key of the shelf needs), never past 1,000,000 units.
 * - **Cancel** adds each cell to `cancelled`; no stock moves.
 *
 * The line's two counts stay the sums of its rows. No stock history entry and no restock: the shelf is the only trace (the card).
 */
final readonly class ChangeOrderUnits
{
    private const MAX_SHELF = 1000000;

    /**
     * @param  array<string, int>  $unitsByCell  "<colour id>:<size id>" → units (1 or more)
     * @return string the toast
     *
     * @throws ValidationException a limit, or the order or the shelf changed since the page (`cells`), with nothing written
     */
    public function handle(OrderLine $line, OrderUnitsChange $change, array $unitsByCell): string
    {
        $unitsByCell = array_filter($unitsByCell, static fn (int $units): bool => $units >= 1);

        if ($unitsByCell === []) {
            throw new InvalidArgumentException('A change needs at least one unit.');
        }

        return DB::transaction(function () use ($line, $change, $unitsByCell): string {
            // An order deleted in the meantime is not found.
            Order::query()->whereKey($line->order_id)->lockForUpdate()->firstOrFail();
            $product = Product::withTrashed()->whereKey($line->product_id)->lockForUpdate()->firstOrFail();
            $locked = OrderLine::query()->lockForUpdate()->findOrFail($line->id);
            /** @var Collection<string, OrderLineSize> $rows */
            $rows = $locked->sizes()->with(['colour', 'size'])->lockForUpdate()->get()
                ->keyBy(fn (OrderLineSize $row): string => "{$row->colour_id}:{$row->size_id}");
            $shelf = $change === OrderUnitsChange::Cancel ? collect() : ProductSizeStock::query()
                ->where('product_id', $product->id)->orderBy('id')->lockForUpdate()->get()
                ->keyBy(fn (ProductSizeStock $row): string => "{$row->colour_id}:{$row->size_id}");
            // How the line counts is the line's own (a line recorded before sizes names no size), whatever its product has become.
            $oneSize = ! $locked->countsBySize();

            $this->check($change, $rows, $shelf, $unitsByCell, $oneSize);

            foreach ($unitsByCell as $cell => $units) {
                $row = $rows->get($cell) ?? throw new InvalidArgumentException("No row {$cell}.");

                match ($change) {
                    OrderUnitsChange::Deliver => $this->deliver($row, $shelf->get($cell), $units),
                    OrderUnitsChange::MoveBack => $this->moveBack($row, $shelf->get($cell), $product, $units),
                    OrderUnitsChange::Cancel => $row->increment('cancelled', $units),
                };
            }

            // The line's counts are the sums of its rows (ADR-0016).
            $locked->forceFill([
                'delivered' => (int) $rows->sum(fn (OrderLineSize $row): int => $row->delivered),
                'cancelled' => (int) $rows->sum(fn (OrderLineSize $row): int => $row->cancelled),
            ])->save();

            $total = array_sum($unitsByCell);

            return match ($change) {
                OrderUnitsChange::Deliver => OrderMessages::delivered($total, $product->name),
                OrderUnitsChange::MoveBack => OrderMessages::movedBack($total, $product->name),
                OrderUnitsChange::Cancel => OrderMessages::cancelled($total, $product->name),
            };
        });
    }

    /**
     * Every refusal at once: a cell that is no longer a row, or holds nothing for this change any more, refuses the window as a whole
     * ("… changed since you opened this page."); a cell above its limit refuses under its own field.
     *
     * @param  Collection<string, OrderLineSize>  $rows
     * @param  Collection<string, ProductSizeStock>  $shelf
     * @param  array<string, int>  $unitsByCell
     *
     * @throws ValidationException
     */
    private function check(OrderUnitsChange $change, Collection $rows, Collection $shelf, array $unitsByCell, bool $oneSize): void
    {
        $errors = [];
        $changed = $change === OrderUnitsChange::Deliver ? OrderMessages::shelfOrOrderChanged() : OrderMessages::orderChanged();

        foreach ($unitsByCell as $cell => $units) {
            $row = $rows->get($cell);

            if ($row === null) {
                $errors['cells'] = $changed;

                continue;
            }

            $key = 'cells.'.str_replace(':', '.', $cell);
            $variant = OrderMessages::variant($row->colour->name, $row->size->label, $oneSize);
            $onShelf = $shelf->get($cell)->units ?? 0;

            match ($change) {
                OrderUnitsChange::Deliver => match (true) {
                    $row->to_deliver === 0 || $onShelf === 0 => $errors['cells'] = $changed,
                    $units > $onShelf && $onShelf <= $row->to_deliver => $errors[$key] = OrderMessages::deliverShelfLimit($variant, $onShelf),
                    $units > $row->to_deliver => $errors[$key] = OrderMessages::deliverOrderLimit($variant, $row->to_deliver),
                    default => null,
                },
                OrderUnitsChange::MoveBack => match (true) {
                    $row->delivered === 0 => $errors['cells'] = $changed,
                    $units > $row->delivered => $errors[$key] = OrderMessages::moveBackLimit($variant, $row->delivered),
                    $onShelf + $units > self::MAX_SHELF => $errors[$key] = OrderMessages::shelfFull($variant),
                    default => null,
                },
                OrderUnitsChange::Cancel => match (true) {
                    $row->to_deliver === 0 => $errors['cells'] = $changed,
                    $units > $row->to_deliver => $errors[$key] = OrderMessages::cancelLimit($variant, $row->to_deliver),
                    default => null,
                },
            };
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function deliver(OrderLineSize $row, ?ProductSizeStock $onShelf, int $units): void
    {
        $onShelf ?? throw new InvalidArgumentException('Nothing on the shelf.');
        $onShelf->units === $units ? $onShelf->delete() : $onShelf->decrement('units', $units);
        $row->increment('delivered', $units);
    }

    private function moveBack(OrderLineSize $row, ?ProductSizeStock $onShelf, Product $product, int $units): void
    {
        if ($onShelf !== null) {
            $onShelf->increment('units', $units);
        } else {
            // The colour of a shelf row must be one of the product's (INV-2): one taken off since the delivery comes back.
            $product->colours()->syncWithoutDetaching([$row->colour_id => ['workshop_id' => $product->workshop_id]]);
            ProductSizeStock::query()->insert([
                'workshop_id' => $product->workshop_id, 'product_id' => $product->id, 'colour_id' => $row->colour_id,
                'size_id' => $row->size_id, 'units' => $units, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $row->decrement('delivered', $units);
    }
}
