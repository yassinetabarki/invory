<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderLineMove;
use App\Enums\OrderStage;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use App\Services\Orders\MoveResult;
use App\Services\Orders\OrderMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class MoveOrderLineUnits
{
    /**
     * Moves `$units` of an order line from one stage to the next or the previous one. The limit is the count saved now: the order row
     * is locked first (the same lock "Edit order" takes), then the line, and the counts are read under those locks, never from the
     * model the route handed in (ADR-0014 §7). It reads and writes only the order and the line, so Mark ready, Ship and every move
     * back can't touch materials, stock history or sales.
     *
     * @throws ValidationException under `units`: more than the stage holds, or Start cutting on a deleted product
     */
    public function handle(OrderLine $line, OrderLineMove $move, int $units, User $actor): MoveResult
    {
        return DB::transaction(function () use ($line, $move, $units): MoveResult {
            // An order deleted in the meantime is not found.
            Order::query()->whereKey($line->order_id)->lockForUpdate()->firstOrFail();

            $locked = OrderLine::query()->lockForUpdate()->findOrFail($line->id);
            $product = $locked->product()->firstOrFail();

            if ($move === OrderLineMove::StartCutting && $product->trashed()) {
                throw ValidationException::withMessages(['units' => OrderMessages::productDeleted($product->name)]);
            }

            $held = $this->held($locked, $move->fromStage());

            if ($units > $held) {
                throw ValidationException::withMessages(['units' => OrderMessages::moveLimit($held)]);
            }

            // To start has no column: it is whatever the three counters leave of the quantity.
            foreach ([[$move->fromStage(), -$units], [$move->toStage(), $units]] as [$stage, $change]) {
                if ($stage !== OrderStage::ToStart) {
                    $locked->setAttribute($stage->value, $this->held($locked, $stage) + $change);
                }
            }

            $locked->save();

            return new MoveResult($units, OrderMessages::moved($move, $units, $product->name));
        });
    }

    /** The units the stage holds on the locked line. */
    private function held(OrderLine $line, OrderStage $stage): int
    {
        return $stage === OrderStage::ToStart ? $line->to_start : (int) $line->getAttribute($stage->value);
    }
}
