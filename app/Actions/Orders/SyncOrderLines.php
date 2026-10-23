<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderLineSizeMode;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\SizeList;
use App\Services\Orders\OrderLineSizing;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SyncOrderLines
{
    public function __construct(private OrderLineSizing $sizing, private SizeLists $lists) {}

    /**
     * Make the order's lines equal to the submitted ones, in one transaction: lines are matched by product (a product still listed
     * keeps its line, with quantity and position updated in place; a new product gets a new line), lines whose product is no longer
     * listed are deleted, and `position` follows the submitted order. Matching by product means swapping two products between
     * lines never meets the `unique(order_id, product_id)` index.
     *
     * A line counts by size (ADR-0016): a row per size ordered (a size left empty or 0 has none) and the line's `quantity` is the sum
     * of its rows. A line that counts as one size (a "One size" product, or recorded before sizes) holds its `quantity` in the one row
     * of its list. The products of the lines are locked first (ascending id, F-64), and how each line counts is worked out again
     * under that lock: a product moved to other sizes after the request was checked refuses the save.
     *
     * A product listed twice is not merged: the second line is inserted and the database's unique index rejects it, which rolls the
     * whole sync back (the form request refuses it earlier).
     *
     * A size that has started (S-49, S-61) keeps its product and at least its started units; so does a single-field line. The counts
     * are read here, inside the transaction, after the caller took the order's row lock, so they are the saved ones: this check is the
     * one that counts. Rows that have started are never removed or lowered, so the line's three counts stay the sums of its rows.
     *
     * @param  array<int|string, array{product_id: int|string, quantity?: int|string|null, sizes?: array<int|string, int|string|null>|null}>  $lines  validated input, in display order (`position` is the index in the order given; a key only names a line in an error message)
     *
     * @throws ValidationException a started line was removed or given fewer units than started, or a product's sizes changed
     */
    public function handle(Order $order, array $lines): void
    {
        DB::transaction(function () use ($order, $lines): void {
            $products = $this->lockProducts($lines);

            /** @var Collection<int, OrderLine> $stale lines still to be matched, by product id */
            $stale = $order->lines()->with(['product', 'sizes'])->get()->keyBy('product_id');

            $units = $this->unitsBySize($lines, $stale, $products);
            $refusals = $this->refusals($stale, $lines);

            if ($refusals !== []) {
                throw ValidationException::withMessages($refusals);
            }

            $position = 0;

            foreach ($lines as $key => $line) {
                $quantity = array_sum($units[$key]['rows']);

                // `pull` takes the line out of the pool, so a repeated product finds nothing and hits the unique index.
                $existing = $stale->pull((int) $line['product_id']);

                if ($existing === null) {
                    $new = $order->lines()->make(['product_id' => (int) $line['product_id'], 'quantity' => $quantity, 'position' => $position]);

                    // Never mass-assignable: a line belongs to its order's workshop, whatever the request says.
                    $new->workshop_id = $order->workshop_id;
                    $new->size_list_id = $units[$key]['list']->id;
                    $new->save();
                    $this->writeRows($new, $units[$key]['rows'], new Collection);
                } else {
                    $existing->update(['quantity' => $quantity, 'position' => $position]);
                    $this->writeRows($existing, $units[$key]['rows'], $existing->sizes->keyBy('size_id'));
                }

                $position++;
            }

            if ($stale->isNotEmpty()) {
                OrderLine::query()->whereKey($stale->pluck('id')->all())->delete();
            }

            // A relation loaded before the sync no longer matches the table.
            $order->unsetRelation('lines');
        });
    }

    /**
     * How a line of this product counts, and by which list: a saved line by its own (it was recorded with it, whatever its product
     * has become), a new line by its product's. The form request and `handle()` both ask here.
     *
     * @param  OrderLine|null  $saved  the order's saved line of this product, with its `product` loaded; null for a new line
     * @return array{OrderLineSizeMode, SizeList}
     */
    public function counting(Product $product, ?OrderLine $saved): array
    {
        $mode = $saved === null ? $this->sizing->forProduct($product) : $this->sizing->forLine($saved);

        return [$mode, $this->lists->list($saved->size_list_id ?? $this->lists->category($product->category_id)->size_list_id)];
    }

    /**
     * What a submitted list of lines may not do to the lines that have started: remove one (a swap of its product reads as a removal,
     * since lines are matched by product), lower a size below its started units, or lower a single-field line's quantity below them.
     * Lines that haven't started, and new lines, are free. The form request runs this on unlocked data for early messages;
     * `handle()` runs it under the order lock.
     *
     * @param  Collection<int, OrderLine>  $current  the order's saved lines, by product id, with `product` and `sizes` loaded
     * @param  array<int|string, mixed>  $lines  as submitted (the request has validated their shape; anything else is skipped)
     * @return array<string, list<string>> messages by error key: `lines` (a started line is missing), `lines.<key>.quantity` and `lines.<key>.sizes.<size id>`
     */
    public function refusals(Collection $current, array $lines): array
    {
        $submitted = [];

        foreach ($lines as $key => $line) {
            if (is_array($line)) {
                $submitted[(int) $line['product_id']] ??= [$key, $line];
            }
        }

        $refusals = [];

        foreach ($current as $productId => $saved) {
            if ($saved->started === 0) {
                continue;
            }

            if (! isset($submitted[$productId])) {
                // The product may be deleted: the line keeps it.
                $refusals['lines'][] = OrderMessages::startedLineRemoved($saved->product->name, $saved->started);

                continue;
            }

            [$key, $line] = $submitted[$productId];

            if ($this->sizing->forLine($saved) !== OrderLineSizeMode::BySize) {
                if ((int) ($line['quantity'] ?? 0) < $saved->started) {
                    $refusals["lines.{$key}.quantity"][] = OrderMessages::quantityBelowStarted($saved->started);
                }

                continue;
            }

            $typed = is_array($line['sizes'] ?? null) ? $line['sizes'] : [];
            $labels = $this->lists->list((int) $saved->size_list_id)->sizes->pluck('label', 'id');

            foreach ($saved->sizes as $row) {
                if ($row->started > 0 && (int) ($typed[$row->size_id] ?? 0) < $row->started) {
                    $refusals["lines.{$key}.sizes.{$row->size_id}"][] = OrderMessages::sizeBelowStarted((string) $labels->get($row->size_id), $row->started);
                }
            }
        }

        return $refusals;
    }

    /**
     * The products of the submitted lines, locked in ascending id so two saves never wait on each other in a circle.
     *
     * @param  array<int|string, array<string, mixed>>  $lines
     * @return Collection<int, Product>
     */
    private function lockProducts(array $lines): Collection
    {
        $ids = array_values(array_unique(array_map(static fn (array $line): int => (int) $line['product_id'], $lines)));
        sort($ids);

        return Product::query()->withTrashed()->whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /**
     * For every submitted line, the list it counts by and its units by size id (only sizes of 1 unit or more), worked out under the
     * product locks. A shape that no longer fits the product's sizes refuses the whole save.
     *
     * @param  array<int|string, array<string, mixed>>  $lines
     * @param  Collection<int, OrderLine>  $stale
     * @param  Collection<int, Product>  $products
     * @return array<int|string, array{list: SizeList, rows: array<int, int>}>
     *
     * @throws ValidationException
     */
    private function unitsBySize(array $lines, Collection $stale, Collection $products): array
    {
        $plans = [];
        $refusals = [];

        foreach ($lines as $key => $line) {
            $productId = (int) $line['product_id'];
            $product = $products->get($productId) ?? throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
            [$mode, $list] = $this->counting($product, $stale->get($productId));
            $rows = $mode === OrderLineSizeMode::BySize ? $this->typedSizes($line, $list) : $this->typedQuantity($line, $list);

            if ($rows === null) {
                // The field that was submitted: sizes for a line counted by size, else the single quantity.
                $field = $mode === OrderLineSizeMode::BySize && ! array_key_exists('sizes', $line) ? 'quantity' : 'sizes';
                $refusals["lines.{$key}.{$field}"][] = OrderMessages::orderSizesChanged($product->name);
            } elseif ($rows === []) {
                $refusals["lines.{$key}.sizes"][] = OrderMessages::atLeastOneUnit($product->name);
            }

            $plans[$key] = ['list' => $list, 'rows' => $rows ?? []];
        }

        if ($refusals !== []) {
            throw ValidationException::withMessages($refusals);
        }

        return $plans;
    }

    /**
     * The units typed per size, or null when `sizes` is missing or names a size that is not of the list.
     *
     * @param  array<string, mixed>  $line
     * @return array<int, int>|null
     */
    private function typedSizes(array $line, SizeList $list): ?array
    {
        if (! is_array($line['sizes'] ?? null)) {
            return null;
        }

        $ids = $list->sizes->pluck('id')->all();
        $rows = [];

        foreach ($line['sizes'] as $id => $value) {
            if (! in_array($id, $ids, true)) {
                return null;
            }

            if ((int) $value > 0) {
                $rows[$id] = (int) $value;
            }
        }

        return $rows;
    }

    /**
     * The single quantity, on the only size of the list; null when the line was submitted without one.
     *
     * @param  array<string, mixed>  $line
     * @return array<int, int>|null
     */
    private function typedQuantity(array $line, SizeList $list): ?array
    {
        if (! isset($line['quantity'])) {
            return null;
        }

        return (int) $line['quantity'] > 0 ? [$list->sizes->firstOrFail()->id => (int) $line['quantity']] : [];
    }

    /**
     * Make the size rows of a line equal to `$rows`: new sizes inserted, changed ones updated, the others removed (none of them has started).
     *
     * @param  array<int, int>  $rows  units by size id
     * @param  Collection<int, OrderLineSize>  $saved  the line's saved rows by size id
     */
    private function writeRows(OrderLine $line, array $rows, Collection $saved): void
    {
        $new = [];

        foreach ($rows as $sizeId => $units) {
            $row = $saved->get($sizeId);

            if ($row === null) {
                $new[] = [
                    'workshop_id' => $line->workshop_id, 'order_line_id' => $line->id, 'size_id' => $sizeId, 'quantity' => $units,
                    'created_at' => now(), 'updated_at' => now(),
                ];
            } elseif ($row->quantity !== $units) {
                $row->update(['quantity' => $units]);
            }
        }

        // By size id, not by key: an Eloquent collection's `except()` filters by the models' own keys.
        $gone = array_diff_key($saved->all(), $rows);

        if ($gone !== []) {
            OrderLineSize::query()->whereKey(array_map(static fn (OrderLineSize $row): int => $row->id, $gone))->delete();
        }

        if ($new !== []) {
            OrderLineSize::query()->insert($new);
        }
    }
}
