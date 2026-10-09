<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockStatus;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property int $category_id
 * @property int $unit_id
 * @property MaterialCategory $category Always eager-loaded ({@see self::$with}); narrow selects must `without()` it.
 * @property Unit $unit Always eager-loaded ({@see self::$with}); narrow selects must `without()` it.
 * @property string $unit_cost
 * @property string $quantity_on_hand
 * @property string|null $reorder_level
 * @property string|null $supplier_name
 * @property string|null $supplier_reference
 * @property string|null $color
 * @property string|null $composition
 * @property string|null $notes
 * @property int|null $created_by Audit: the user who created the material (null for rows that predate the column or a deleted user).
 * @property int|null $updated_by Audit: the user who last changed it.
 * @property int $used_in_count Non-deleted products whose composition uses this material. Not a column: `withUsedInCount()` provides it (route binding adds it too), strict models throw when a caller forgot.
 * @property string|null $photo_url Public URL of the photo. Not a column: the controllers set it as an attribute (a photo accessor in S-15-BE).
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 */
#[Fillable([
    'sku', 'name', 'category_id', 'unit_id', 'unit_cost', 'quantity_on_hand', 'reorder_level',
    'supplier_name', 'supplier_reference', 'color', 'composition', 'notes',
])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Every Resource, option and costing line shows the category and the unit, so they are always loaded (two constant
     * queries per query, never one per row). A query that selects a few columns only must call `without(['category', 'unit'])`.
     *
     * @var list<string>
     */
    protected $with = ['category', 'unit'];

    /**
     * Columns the index may be sorted by: the single source of truth for `sortedBy()` and IndexMaterialRequest.
     *
     * @var list<string>
     */
    public const SORTABLE = ['name', 'sku', 'quantity_on_hand', 'unit_cost', 'updated_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:4',
            'quantity_on_hand' => 'decimal:4',
            'reorder_level' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<MaterialCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * The BOM lines that use this material (any product, deleted ones included: the lines stay for history and restore).
     *
     * @return HasMany<ProductComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class);
    }

    /**
     * A route-bound material carries `used_in_count` too (a sub-select in the one query that finds it), so the show and edit pages
     * don't pay a query of their own for it. A soft-deleted material is not found.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return static|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::query()->withUsedInCount()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }

    /**
     * Computed server-side, see the "Stock status" rule in the costing domain skill.
     */
    public function stockStatus(): StockStatus
    {
        $quantity = BigDecimal::of($this->quantity_on_hand);

        if ($quantity->isZero()) {
            return StockStatus::Out;
        }

        if ($this->reorder_level !== null && $quantity->isLessThanOrEqualTo(BigDecimal::of($this->reorder_level))) {
            return StockStatus::Low;
        }

        return StockStatus::InStock;
    }

    /**
     * Stock value = quantity on hand x unit cost, as a scale-4 string.
     */
    public function stockValue(): string
    {
        return (string) BigDecimal::of($this->quantity_on_hand)
            ->multipliedBy($this->unit_cost)
            ->toScale(4, RoundingMode::HalfUp);
    }

    /**
     * Case-insensitive match on name or SKU.
     *
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->whereLike('name', "%{$term}%")
            ->orWhereLike('sku', "%{$term}%"));
    }

    /**
     * Materials of the category with this code (no filter for null). Renamed from `category`, which is now the relation.
     *
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, ?string $categoryCode): void
    {
        if ($categoryCode !== null) {
            $query->whereIn('category_id', MaterialCategory::query()->where('code', $categoryCode)->select('id'));
        }
    }

    /**
     * Adds `used_in_count`: how many non-deleted products' compositions use the material (one sub-select, no extra query).
     *
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function withUsedInCount(Builder $query): void
    {
        // Lines of non-deleted products only: the lines of a soft-deleted product stay for history and don't count as a use.
        $query->withCount(['components as used_in_count' => fn (Builder $lines) => $lines->whereIn('product_id', Product::query()->select('id'))]);
    }

    /**
     * Materials whose stock status is `low` or `out`.
     *
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function needsRestock(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('quantity_on_hand', '<=', 0)
            ->orWhere(fn (Builder $q) => $q
                ->whereNotNull('reorder_level')
                ->whereColumn('quantity_on_hand', '<=', 'reorder_level')));
    }

    /**
     * Apply a sort on an allowlisted column (a leading `-` means descending). `id` breaks ties so pagination is stable.
     *
     * @param  Builder<Material>  $query
     *
     * @throws InvalidArgumentException when the column is not in {@see self::SORTABLE}
     */
    #[Scope]
    protected function sortedBy(Builder $query, string $sort): void
    {
        $descending = str_starts_with($sort, '-');
        $column = $descending ? substr($sort, 1) : $sort;

        if (! in_array($column, self::SORTABLE, true)) {
            throw new InvalidArgumentException("Cannot sort materials by [{$column}].");
        }

        $query->orderBy($column, $descending ? 'desc' : 'asc')->orderBy('id');
    }
}
