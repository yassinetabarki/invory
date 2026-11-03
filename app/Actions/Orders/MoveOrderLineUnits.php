<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderLineMove;
use App\Enums\OrderLineSizeMode;
use App\Enums\OrderStage;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\SizeList;
use App\Models\User;
use App\Services\Orders\MoveResult;
use App\Services\Orders\OrderLineSizing;
use App\Services\Orders\OrderMessages;
use App\Services\Products\SizeLists;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class MoveOrderLineUnits
{
    public function __construct(
        private TakeMaterialsForCut $takeMaterials,
        private ReturnMaterialsFromCuts $returnMaterials,
        private OrderLineSizing $sizing,
        private SizeLists $lists,
    ) {}

    /**
     * Moves units of an order line from one stage to the next or the previous one, per colour and size (S-61-a, ADR-0016, ADR-0025):
     * `$unitsByCell` is "<colour id>:<size id>" → units (1 or more), and every cell moves or none does. The limit of each cell is the
     * count saved now: the order row is locked first (the same lock "Edit order" takes), then the line, then its rows, and the counts
     * are read under those locks, never from the model the route handed in (ADR-0014 §7). Start cutting takes the materials of the
     * total out of stock in one cut, and Move back to To start returns what the total took, latest cut first, both inside this
     * transaction (S-53); every other move reads and writes only the order, the line and its rows, so Mark ready, Ship and their moves
     * back can't touch materials, stock history or sales. Afterwards each row holds its new counts and the line's three counts are the
     * sums of its rows.
     *
     * A window by cell (a line counted by size, or holding several colours) reports its refusals under `cells.<colour id>.<size id>` (a
     * limit) or `cells` (a product deleted, too little stock); a line with a single cell has one row and reports them under `units`.
     *
     * @param  array<string, int>  $unitsByCell
     *
     * @throws ValidationException more than a cell's stage holds, a cell that is not on the line, Start cutting on a deleted product, or
     *                             too little stock to cut
     */
    public function handle(OrderLine $line, OrderLineMove $move, array $unitsByCell, User $actor): MoveResult
    {
        $unitsByCell = array_filter($unitsByCell, static fn (int $units): bool => $units >= 1);

        if ($unitsByCell === []) {
            throw new InvalidArgumentException('A move needs at least one unit.');
        }

        return DB::transaction(function () use ($line, $move, $unitsByCell, $actor): MoveResult {
            // An order deleted in the meantime is not found.
            Order::query()->whereKey($line->order_id)->lockForUpdate()->firstOrFail();

            $locked = OrderLine::query()->lockForUpdate()->findOrFail($line->id);
            $product = $locked->product()->firstOrFail();
            $rows = $locked->sizes()->with(['size', 'colour'])->lockForUpdate()->get();
            $byCell = $this->sizing->forLine($locked->setRelation('product', $product)) === OrderLineSizeMode::BySize || $rows->count() > 1;
            $oneSize = $this->lists->list((int) $locked->size_list_id)->code === SizeList::ONE_SIZE;

            if ($move === OrderLineMove::StartCutting && $product->trashed()) {
                throw ValidationException::withMessages([$byCell ? 'cells' : 'units' => OrderMessages::productDeleted($product->name)]);
            }

            $keyed = $rows->keyBy(fn (OrderLineSize $row): string => "{$row->colour_id}:{$row->size_id}")->all();
            $moved = $this->checked($keyed, $unitsByCell, $move->fromStage(), $byCell, $oneSize, $product->name);
            $total = array_sum($unitsByCell);
            $snapshot = $byCell ? array_map(
                static fn (OrderLineSize $row): array => [
                    'size_id' => $row->size_id,
                    'colour_id' => $row->colour_id,
                    // INV-13: the colour is part of the label the history shows ("Indigo M 4").
                    'label' => OrderMessages::variant($row->colour->name, $row->size->label, $oneSize),
                    'units' => $unitsByCell["{$row->colour_id}:{$row->size_id}"],
                ],
                $moved,
            ) : null;

            $cut = $move === OrderLineMove::StartCutting ? $this->takeMaterials->handle($locked, $total, $actor, $snapshot) : null;
            $returned = $move === OrderLineMove::BackToStart ? $this->returnMaterials->handle($locked, $total, $actor, $snapshot) : null;

            // To start has no column: it is whatever the three counters leave of the quantity.
            foreach ($moved as $row) {
                $units = $unitsByCell["{$row->colour_id}:{$row->size_id}"];

                foreach ([[$move->fromStage(), -$units], [$move->toStage(), $units]] as [$stage, $change]) {
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
     * The rows that move, in the line's order, once every cell is checked against what its stage holds now. Every refusal is reported,
     * not only the first one.
     *
     * @param  array<string, OrderLineSize>  $rows  the locked rows by cell, in the line's order, each with its size and colour
     * @param  array<string, int>  $unitsByCell
     * @return list<OrderLineSize>
     *
     * @throws ValidationException
     */
    private function checked(array $rows, array $unitsByCell, OrderStage $from, bool $byCell, bool $oneSize, string $product): array
    {
        $errors = [];

        foreach ($unitsByCell as $cell => $units) {
            $row = $rows[$cell] ?? null;

            if ($row === null) {
                $errors['cells'] = OrderMessages::moveSizesChanged($product);
            } elseif ($units > ($held = $this->held($row, $from))) {
                $errors[$byCell ? 'cells.'.str_replace(':', '.', $cell) : 'units'] = $byCell
                    ? OrderMessages::sizeMoveLimit(OrderMessages::variant($row->colour->name, $row->size->label, $oneSize), $held)
                    : OrderMessages::moveLimit($held);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return array_values(array_filter($rows, static fn (OrderLineSize $row): bool => isset($unitsByCell["{$row->colour_id}:{$row->size_id}"])));
    }

    /** The units the stage holds on a locked line or row. */
    private function held(OrderLine|OrderLineSize $model, OrderStage $stage): int
    {
        return $stage === OrderStage::ToStart ? $model->to_start : (int) $model->getAttribute($stage->value);
    }
}
