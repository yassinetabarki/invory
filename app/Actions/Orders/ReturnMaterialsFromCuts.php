<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Actions\Materials\AppendStockMovement;
use App\Domain\Stock\ReturnPlan;
use App\Enums\StockMovementType;
use App\Models\OrderLine;
use App\Models\User;
use App\Services\Orders\CuttingPreview;
use Brick\Math\BigDecimal;
use LogicException;

/**
 * Move back to To start returns exactly what the units took (S-53, ADR-0014 section 5). Called only by {@see MoveOrderLineUnits},
 * inside its transaction and after it locked the order and the line and checked the limit: it locks the line's cuts that still hold
 * units, newest first, and then their materials in ascending id, makes the plan from the cuts' remembered amounts (never from the
 * composition) and writes the plan's figures, so there is no arithmetic here. Nothing comes back for a material deleted since or whose
 * unit changed since; units no cut covers return nothing. Unit costs never change.
 */
final readonly class ReturnMaterialsFromCuts
{
    public function __construct(
        private CuttingPreview $preview,
        private AppendStockMovement $appendMovement,
    ) {}

    public function handle(OrderLine $line, int $units, User $actor): ReturnPlan
    {
        ['plan' => $plan, 'materials' => $materials, 'cuts' => $cuts] = $this->preview->returnPlan($line, $units, lock: true);

        $today = today()->toDateString();

        foreach ($plan->returns as $return) {
            if ($return->notReturned !== null || ! BigDecimal::of($return->returned)->isPositive()) {
                continue;
            }

            $material = $materials[$return->materialId];
            $after = $return->after ?? throw new LogicException("Material #{$return->materialId} is returnable without a stock after the return.");

            $material->quantity_on_hand = $after;
            $material->updated_by = $actor->id;
            $material->save();

            $this->appendMovement->handle($material, StockMovementType::CuttingReturn, $today, $actor, [
                'quantity' => $return->returned,
                'quantity_before' => $return->inStock,
                'quantity_after' => $after,
                'order_id' => $line->order_id,
                'product_id' => $line->product_id,
                'units' => $units,
            ]);
        }

        // A counter of units, not money: the batch's own units, added to the locked row.
        foreach ($plan->batches as $batch) {
            $cuts[$batch->cutId]->increment('units_returned', $batch->units);
        }

        return $plan;
    }
}
