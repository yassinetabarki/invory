<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductionCutMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one Start batch took of one material (INV-5, ADR-0014's maths): the exact amount per unit, waste included, and the amount
 * taken. One row per material per production cut (database-unique).
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $production_cut_id
 * @property int $material_id
 * @property int|null $unit_id The material's unit at the cut.
 * @property int|null $colour_id The colour of the stock the take came from (INV-21); null for a one-stock take and every cut before.
 * @property string|null $quantity_per_unit Decimal string, 8 decimals. Null on a cut that has {@see ProductionCutVariant} rows (INV-16).
 * @property string $quantity_taken Decimal string, 4 decimals.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['material_id', 'colour_id', 'unit_id', 'quantity_per_unit', 'quantity_taken'])]
class ProductionCutMaterial extends Model
{
    /** @use HasFactory<ProductionCutMaterialFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_per_unit' => 'decimal:8', 'quantity_taken' => 'decimal:4'];
    }

    /** @return BelongsTo<ProductionCut, $this> */
    public function cut(): BelongsTo
    {
        return $this->belongsTo(ProductionCut::class, 'production_cut_id');
    }

    /** @return BelongsTo<Colour, $this> */
    public function colour(): BelongsTo
    {
        return $this->belongsTo(Colour::class);
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
