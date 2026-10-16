<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\OrderLineCutMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one cut took of one material (S-53, ADR-0014): the exact amount per unit, waste included, and the amount taken. One row per
 * material per cut (database-unique).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the cut (and of its material). Never mass-assignable: it comes from the cut.
 * @property int $order_line_cut_id
 * @property int $material_id
 * @property int|null $unit_id The material's unit at the cut; a material whose unit changed since gets nothing back.
 * @property string $quantity_per_unit Decimal string, scale 8, > 0: the exact effective quantity per unit (F-41 `perUnitExact`).
 * @property string $quantity_taken Decimal string, scale 4: round4(per unit x units of the cut).
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['material_id', 'unit_id', 'quantity_per_unit', 'quantity_taken'])]
class OrderLineCutMaterial extends Model
{
    /** @use HasFactory<OrderLineCutMaterialFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_per_unit' => 'decimal:8',
            'quantity_taken' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<OrderLineCut, $this> */
    public function cut(): BelongsTo
    {
        return $this->belongsTo(OrderLineCut::class, 'order_line_cut_id');
    }

    /**
     * Soft-deleted materials stay readable: a return finds out that one was deleted since the cut.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
