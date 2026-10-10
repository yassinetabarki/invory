<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

final readonly class UpdateMaterial
{
    public function __construct(
        private ResolveReferenceCodes $resolveCodes,
        private LockMaterialForUpdate $lockMaterial,
        private AppendStockMovement $appendMovement,
    ) {}

    /**
     * A changed `quantity_on_hand` is logged as a `correction` (before and after, no reason: the form has none) and a changed
     * `unit_cost` as a `price_change`, both dated today. Equal values are compared as numbers, so "250" over "250.0000" is no change.
     *
     * @param  array<string, mixed>  $data  validated input, with `category` and `unit` as codes
     */
    public function handle(Material $material, array $data, User $actor): Material
    {
        return DB::transaction(function () use ($material, $data, $actor): Material {
            $this->lockMaterial->handle($material);

            $stockBefore = BigDecimal::of($material->quantity_on_hand);
            $costBefore = BigDecimal::of($material->unit_cost);

            $material->fill($this->resolveCodes->handle($data));
            $material->updated_by = $actor->id;
            $material->save();

            $stockAfter = BigDecimal::of($material->quantity_on_hand);
            $costAfter = BigDecimal::of($material->unit_cost);
            $today = today()->toDateString();

            if (! $stockAfter->isEqualTo($stockBefore)) {
                $this->appendMovement->handle($material, StockMovementType::Correction, $today, $actor, [
                    'quantity_before' => (string) $stockBefore,
                    'quantity_after' => (string) $stockAfter,
                ]);
            }

            if (! $costAfter->isEqualTo($costBefore)) {
                $this->appendMovement->handle($material, StockMovementType::PriceChange, $today, $actor, [
                    'unit_cost_before' => (string) $costBefore,
                    'unit_cost_after' => (string) $costAfter,
                    'quantity_after' => (string) $stockAfter,
                ]);
            }

            // The category and unit relations were loaded before the change.
            return $material->load(['category', 'unit']);
        });
    }
}
