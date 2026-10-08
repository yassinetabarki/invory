<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialCategory;
use App\Enums\StockStatus;
use App\Enums\UnitOfMeasure;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property MaterialCategory $category
 * @property UnitOfMeasure $unit
 * @property string $unit_cost
 * @property string $quantity_on_hand
 * @property string|null $reorder_level
 * @property string|null $supplier_name
 * @property string|null $supplier_reference
 * @property string|null $color
 * @property string|null $composition
 * @property string|null $notes
 * @property int $used_in_count Products whose composition uses this material. Not a column: the controllers set it as an attribute (the real `withCount()` later).
 * @property string|null $photo_url Public URL of the photo. Not a column: the controllers set it as an attribute (a photo accessor later).
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 */
#[Fillable([
    'sku', 'name', 'category', 'unit', 'unit_cost', 'quantity_on_hand', 'reorder_level',
    'supplier_name', 'supplier_reference', 'color', 'composition', 'notes',
])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, SoftDeletes;

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
            'category' => MaterialCategory::class,
            'unit' => UnitOfMeasure::class,
            'unit_cost' => 'decimal:4',
            'quantity_on_hand' => 'decimal:4',
            'reorder_level' => 'decimal:4',
        ];
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
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function category(Builder $query, ?MaterialCategory $category): void
    {
        if ($category !== null) {
            $query->where('category', $category->value);
        }
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
