<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductionCutVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One colour and size of a production cut (INV-16, ADR-0027 decision 7): the units of that variant in the start and how many of them were
 * undone so far. Its amounts per unit are remembered in {@see ProductionCutVariantMaterial}. A cut made before INV-16 has none: it is read
 * as one group (its own units and {@see ProductionCutMaterial::$quantity_per_unit}).
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $production_cut_id
 * @property int $colour_id
 * @property int $size_id
 * @property int $units The units of this variant in the start, at least 1.
 * @property int $units_returned How many of them were undone so far, at most `units`.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['colour_id', 'size_id', 'units', 'units_returned'])]
class ProductionCutVariant extends Model
{
    /** @use HasFactory<ProductionCutVariantFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['units' => 'integer', 'units_returned' => 'integer'];
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

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /** @return HasMany<ProductionCutVariantMaterial, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductionCutVariantMaterial::class);
    }
}
