<?php

declare(strict_types=1);

namespace App\Actions\Production;

use App\Actions\Materials\AppendStockMovement;
use App\Enums\ProductionBatchMoveKind;
use App\Enums\ProductionBatchStatus;
use App\Enums\StockMovementType;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\ProductionCounter;
use App\Models\User;
use App\Services\Production\ProductionMessages;
use App\Services\Production\ProductionReturns;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Undo batch (INV-5-e, AC-6, ADR-0023): only while the batch is In cutting. Under the production lock, then the batch, its cut and
 * the cut's materials in ascending id: gives back what {@see ProductionReturns} works out, writes one `cutting_return` entry per
 * material that gets something back, counts the units on the cut and marks the batch undone. Every figure written is the plan's.
 */
final readonly class UndoBatch
{
    public function __construct(
        private ProductionReturns $returns,
        private AppendStockMovement $appendMovement,
    ) {}

    /** @throws ValidationException on `undo` when the batch moved on (or was undone) since the page */
    public function handle(ProductionBatch $batch, User $actor): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $actor): ProductionBatch {
            ProductionCounter::lock();
            $locked = ProductionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if (! $locked->isInCutting()) {
                throw ValidationException::withMessages(['undo' => ProductionMessages::undoMovedOn($locked->label)]);
            }

            ['plan' => $plan, 'materials' => $materials, 'cut' => $cut] = $this->returns->plan($locked, lock: true);
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
                    'product_id' => $locked->product_id,
                    'units' => $locked->units,
                ]);
            }

            // A counter of units, not money: the batch's own units, added to the locked row.
            $cut->increment('units_returned', $plan->unitsCovered);

            $locked->forceFill([
                'status' => ProductionBatchStatus::Undone,
                'undone_at' => now(),
                'undone_by' => $actor->id,
            ])->save();

            $entry = new ProductionBatchMove([
                'production_batch_id' => $locked->id,
                'kind' => ProductionBatchMoveKind::Undo,
                'units' => $locked->units,
                'from_label' => ProductionMessages::IN_CUTTING,
                'to_label' => null,
            ]);
            $entry->forceFill(['workshop_id' => $locked->workshop_id, 'user_id' => $actor->id])->save();

            return $locked;
        });
    }
}
