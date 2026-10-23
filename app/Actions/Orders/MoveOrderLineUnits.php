<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderLineMove;
use App\Enums\OrderLineSizeMode;
use App\Enums\OrderStage;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\User;
use App\Services\Orders\MoveResult;
use App\Services\Orders\OrderLineSizing;
use App\Services\Orders\OrderMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class MoveOrderLineUnits
{
    public function __construct(
        private TakeMaterialsForCut $takeMaterials,
        private ReturnMaterialsFromCuts $returnMaterials,
        private OrderLineSizing $sizing,
    ) {}

    /**
     * Moves units of an order line from one stage to the next or the previous one, per size (S-61-a, ADR-0016): `$unitsBySize` is size
     * id → units (1 or more), and every size moves or none does. The limit of each size is the count saved now: the order row is locked
     * first (the same lock "Edit order" takes), then the line, then its size rows, and the counts are read under those locks, never from
     * the model the route handed in (ADR-0014 §7). Start cutting takes the materials of the total out of stock in one cut, and Move back
     * to To start returns what the total took, latest cut first, both inside this transaction (S-53); every other move reads and writes
     * only the order, the line and its sizes, so Mark ready, Ship and their moves back can't touch materials, stock history or sales.
     * Afterwards each size row holds its new counts and the line's three counts are the sums of its rows.
     *
     * A line counted by size reports its refusals under `sizes.<size id>` (a limit) or `sizes` (a product deleted, too little stock); a
     * line that counts as one size has one row and reports them under `units`, as before.
     *
     * @param  array<int, int>  $unitsBySize
     *
     * @throws ValidationException more than a size's stage holds, a size that is not on the line, Start cutting on a deleted product, or
     *                             too little stock to cut
     */
    public function handle(OrderLine $line, OrderLineMove $move, array $unitsBySize, User $actor): MoveResult
    {
        $unitsBySize = array_filter($unitsBySize, static fn (int $units): bool => $units >= 1);

        if ($unitsBySize === []) {
            throw new InvalidArgumentException('A move needs at least one unit.');
        }

        return DB::transaction(function () use ($line, $move, $unitsBySize, $actor): MoveResult {
            // An order deleted in the meantime is not found.
            Order::query()->whereKey($line->order_id)->lockForUpdate()->firstOrFail();

            $locked = OrderLine::query()->lockForUpdate()->findOrFail($line->id);
            $product = $locked->product()->firstOrFail();
            $rows = $locked->sizes()->with('size')->lockForUpdate()->get();
            $bySize = $this->sizing->forLine($locked->setRelation('product', $product)) === OrderLineSizeMode::BySize;

            if ($move === OrderLineMove::StartCutting && $product->trashed()) {
                throw ValidationException::withMessages([$bySize ? 'sizes' : 'units' => OrderMessages::productDeleted($product->name)]);
            }

            $moved = $this->checked($rows->keyBy('size_id')->all(), $unitsBySize, $move->fromStage(), $bySize, $product->name);
            $total = array_sum($unitsBySize);
            $snapshot = $bySize ? array_map(
                static fn (OrderLineSize $row): array => ['size_id' => $row->size_id, 'label' => $row->size->label, 'units' => $unitsBySize[$row->size_id]],
                $moved,
            ) : null;

            $cut = $move === OrderLineMove::StartCutting ? $this->takeMaterials->handle($locked, $total, $actor, $snapshot) : null;
            $returned = $move === OrderLineMove::BackToStart ? $this->returnMaterials->handle($locked, $total, $actor, $snapshot) : null;

            // To start has no column: it is whatever the three counters leave of the quantity.
            foreach ($moved as $row) {
                foreach ([[$move->fromStage(), -$unitsBySize[$row->size_id]], [$move->toStage(), $unitsBySize[$row->size_id]]] as [$stage, $change]) {
                    if ($stage !== OrderStage::ToStart) {
                        $row->setAttribute($stage->value, $this->held($row, $stage) + $change);
                    }
                }

                $row->save();
            }

            // The line's counts are the sums of its rows (ADR-0016).
            foreach ([OrderStage::InCutting, OrderStage::ReadyToShip, OrderStage::Shipped] as $stage) {
                $locked->setAttribute($stage->value, (int) $rows->sum(fn (OrderLineSize $row): int => $this->held($row, $stage)));
            }

            $locked->save();

            $message = OrderMessages::moved($move, $total, $product->name);

            if ($cut !== null && $cut->hasComposition()) {
                return new MoveResult($total, "{$message} ".OrderMessages::materialsTaken(), true);
            }

            if ($returned !== null && $returned->changesStock()) {
                return new MoveResult($total, "{$message} ".OrderMessages::materialsReturned(), true);
            }

            return new MoveResult($total, $message);
        });
    }

    /**
     * The rows that move, in size order, once every size is checked against what its stage holds now. Every refusal is reported, not
     * only the first one.
     *
     * @param  array<int, OrderLineSize>  $rows  the locked rows by size id, in size order, each with its size
     * @param  array<int, int>  $unitsBySize
     * @return list<OrderLineSize>
     *
     * @throws ValidationException
     */
    private function checked(array $rows, array $unitsBySize, OrderStage $from, bool $bySize, string $product): array
    {
        $errors = [];

        foreach ($unitsBySize as $sizeId => $units) {
            $row = $rows[$sizeId] ?? null;

            if ($row === null) {
                $errors['sizes'] = OrderMessages::moveSizesChanged($product);
            } elseif ($units > ($held = $this->held($row, $from))) {
                $errors[$bySize ? "sizes.{$sizeId}" : 'units'] = $bySize
                    ? OrderMessages::sizeMoveLimit($row->size->label, $held)
                    : OrderMessages::moveLimit($held);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return array_values(array_filter($rows, static fn (OrderLineSize $row): bool => isset($unitsBySize[$row->size_id])));
    }

    /** The units the stage holds on a locked line or size row. */
    private function held(OrderLine|OrderLineSize $model, OrderStage $stage): int
    {
        return $stage === OrderStage::ToStart ? $model->to_start : (int) $model->getAttribute($stage->value);
    }
}
