<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Actions\Materials\AppendStockMovement;
use App\Domain\Stock\CutPlan;
use App\Enums\StockMovementType;
use App\Models\OrderLine;
use App\Models\User;
use App\Services\Orders\CuttingPreview;
use App\Services\Orders\OrderMessages;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Start cutting takes the product's materials out of stock (S-53, ADR-0014). Called only by {@see MoveOrderLineUnits}, inside its
 * transaction and after it locked the order and the line: it locks the materials of the composition in ascending id, makes the plan
 * on the stock read under those locks, and either refuses it or writes it. Every figure written is the plan's, so there is no
 * arithmetic here. Unit costs never change.
 */
final readonly class TakeMaterialsForCut
{
    public function __construct(
        private CuttingPreview $preview,
        private AppendStockMovement $appendMovement,
    ) {}

    /**
     * @throws ValidationException under `units`, with the same text as the preview, when the stock saved now is too short
     */
    public function handle(OrderLine $line, int $units, User $actor): CutPlan
    {
        ['plan' => $plan, 'materials' => $materials] = $this->preview->plan($line->product()->firstOrFail(), $units, lock: true);

        if (! $plan->allowed) {
            throw ValidationException::withMessages(['units' => OrderMessages::notEnoughStock($plan)]);
        }

        // Never mass-assignable: the workshop comes from the line, and who started the cut from the actor.
        $cut = $line->cuts()->make(['units' => $units, 'units_returned' => 0]);
        $cut->workshop_id = $line->workshop_id;
        $cut->user_id = $actor->id;
        $cut->save();

        $today = today()->toDateString();

        foreach ($plan->takes as $take) {
            $material = $materials[$take->materialId];
            $after = $take->after ?? throw new LogicException("Material #{$take->materialId} is short in an allowed cut.");

            $material->quantity_on_hand = $after;
            $material->updated_by = $actor->id;
            $material->save();

            $this->appendMovement->handle($material, StockMovementType::Cutting, $today, $actor, [
                'quantity' => $take->needed,
                'quantity_before' => $take->inStock,
                'quantity_after' => $after,
                'order_id' => $line->order_id,
                'product_id' => $line->product_id,
                'units' => $units,
            ]);

            $remembered = $cut->materials()->make([
                'material_id' => $material->id,
                'unit_id' => $material->unit_id,
                'quantity_per_unit' => $take->perUnitExact,
                'quantity_taken' => $take->needed,
            ]);
            $remembered->workshop_id = $cut->workshop_id;
            $remembered->save();
        }

        return $plan;
    }
}
