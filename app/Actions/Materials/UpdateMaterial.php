<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use App\Services\Photos\PhotoStorage;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

final readonly class UpdateMaterial
{
    public function __construct(
        private ResolveReferenceCodes $resolveCodes,
        private LockMaterialForUpdate $lockMaterial,
        private AppendStockMovement $appendMovement,
        private PhotoStorage $photos,
    ) {}

    /**
     * A changed `quantity_on_hand` is logged as a `correction` (before and after, no reason: the form has none) and a changed
     * `unit_cost` as a `price_change`, both dated today. Equal values are compared as numbers, so "250" over "250.0000" is no change.
     *
     * A `photo` upload replaces the photo (it wins over `remove_photo`) and `remove_photo` clears it. The new file is stored
     * before the transaction and deleted again if it throws; the old file is deleted after it committed.
     *
     * @param  array<string, mixed>  $data  validated input, with `category` and `unit` as codes (and `photo` / `remove_photo`)
     */
    public function handle(Material $material, array $data, User $actor): Material
    {
        return $this->photos->persist('materials', $data, $material->photo_path, fn (array $attributes, array $photo): Material => DB::transaction(function () use ($material, $attributes, $photo, $actor): Material {
            $this->lockMaterial->handle($material);

            $stockBefore = BigDecimal::of($material->quantity_on_hand);
            $costBefore = BigDecimal::of($material->unit_cost);

            $material->fill($this->resolveCodes->handle($attributes))->forceFill($photo);
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
        }));
    }
}
