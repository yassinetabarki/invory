<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductionCutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What one Start batch took out of stock (INV-5, ADR-0023, with ADR-0014's maths): the units of all its batches together and how many
 * of them were undone so far. Its materials are remembered in {@see ProductionCutMaterial}; they are the only source of Undo batch.
 *
 * @property int $id
 * @property int $workshop_id Never mass-assignable.
 * @property int $product_id
 * @property int $units The total of the start, at least 1.
 * @property int $units_returned How many of them were undone so far.
 * @property int|null $user_id Who started it. Never mass-assignable.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['units', 'units_returned'])]
class ProductionCut extends Model
{
    /** @use HasFactory<ProductionCutFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['units' => 'integer', 'units_returned' => 'integer'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return HasMany<ProductionCutMaterial, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductionCutMaterial::class);
    }

    /** @return HasMany<ProductionBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }
}
