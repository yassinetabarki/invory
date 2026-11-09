<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductionCutVariantMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The exact amount of one material that one unit of a production cut's colour and size took (INV-16, ADR-0027 decision 7), waste included.
 * One row per variant and material (database-unique). The amount taken stays on {@see ProductionCutMaterial}, once per material.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $production_cut_variant_id
 * @property int $material_id
 * @property string $quantity_per_unit Decimal string, 8 decimals, > 0.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['material_id', 'quantity_per_unit'])]
class ProductionCutVariantMaterial extends Model
{
    /** @use HasFactory<ProductionCutVariantMaterialFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_per_unit' => 'decimal:8'];
    }

    /** @return BelongsTo<ProductionCutVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductionCutVariant::class, 'production_cut_variant_id');
    }

    /**
     * Soft-deleted materials stay readable: Undo batch finds out that one was deleted since the cut.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }
}
