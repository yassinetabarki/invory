<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UnitDimension;
use Carbon\CarbonInterface;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A unit of measure (ADR-0008). `code` is immutable and is what URLs, filters and forms carry.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property UnitDimension $dimension
 * @property string|null $factor_to_base Decimal string (scale 12), null for `package` units. Only ever used through BigDecimal.
 * @property bool $is_system Seeded defaults: dimension and factor are fixed and they can't be deleted, only archived.
 * @property int $position
 * @property CarbonInterface|null $archived_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['code', 'name', 'dimension', 'factor_to_base', 'is_system', 'position', 'archived_at'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dimension' => UnitDimension::class,
            'factor_to_base' => 'decimal:12',
            'is_system' => 'boolean',
            'position' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /** @return HasMany<Material, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /** "Metre (m)": how a unit is shown in option lists and next to a material. */
    public function label(): string
    {
        return "{$this->name} ({$this->code})";
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<Unit>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Position, then name (id breaks ties so the order is stable).
     *
     * @param  Builder<Unit>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name')->orderBy('id');
    }
}
