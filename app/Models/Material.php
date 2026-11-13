<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockStatus;
use App\Models\Concerns\BelongsToWorkshop;
use App\Services\Materials\MaterialStockSentences;
use App\Services\Photos\PhotoStorage;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;

/**
 * @property int $id
 * @property int $workshop_id The workshop the material belongs to. Never mass-assignable.
 * @property string $sku
 * @property string $name
 * @property int $category_id
 * @property int $unit_id
 * @property MaterialCategory $category Always eager-loaded ({@see self::$with}); narrow selects must `without()` it.
 * @property Unit $unit Always eager-loaded ({@see self::$with}); narrow selects must `without()` it.
 * @property string $unit_cost
 * @property string $quantity_on_hand
 * @property string|null $reorder_level
 * @property MaterialStockMode $stock_mode One stock for all colours, or a stock per colour (INV-21). Never mass-assignable.
 * @property MaterialCostMode $cost_mode `same` unless the stock is per colour (INV-21). Never mass-assignable.
 * @property string|null $supplier_name
 * @property string|null $supplier_reference
 * @property string|null $color
 * @property string|null $composition
 * @property string|null $notes
 * @property int|null $created_by Audit: the user who created the material (null for rows that predate the column or a deleted user).
 * @property int|null $updated_by Audit: the user who last changed it.
 * @property int $used_in_count Non-deleted products whose composition uses this material. Not a column: `withUsedInCount()` provides it (route binding adds it too), strict models throw when a caller forgot.
 * @property string|null $photo_path Public-disk path of the photo (S-15-BE). Narrow selects must include it: `photo_url` reads it.
 * @property-read string|null $photo_url Public URL of the photo (the `public` disk's), null without one. Not a column.
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
    use BelongsToWorkshop, HasFactory, SoftDeletes;

    /**
     * Every Resource, option and costing line shows the category and the unit, so they are always loaded (two constant
     * queries per query, never one per row). A query that selects a few columns only must call `without(['category', 'unit'])`.
     *
     * @var list<string>
     */
    protected $with = ['category', 'unit'];

    /**
     * What the database defaults to, so a material that is made but not saved reads as one stock too (INV-21).
     *
     * @var array<string, string>
     */
    protected $attributes = ['stock_mode' => 'single', 'cost_mode' => 'same'];

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
            'stock_mode' => MaterialStockMode::class,
            'cost_mode' => MaterialCostMode::class,
        ];
    }

    /**
     * The URL of the photo on the public disk, or null. Reads `photo_path`, so a strict model throws when a narrow select left it out.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path === null ? null : Storage::disk(PhotoStorage::DISK)->url($this->photo_path));
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
     * The colour rows of a material kept per colour (none for one stock), in the order of Settings › Colours: colour position, then name,
     * then id. The order joins `colours` (one query, so `with('colours')` is not N+1); the select keeps the rows' own columns and adds
     * `has_history` (1 or null: some ledger entry names the colour), so a page knows which colours may be removed without a query per row.
     *
     * @return HasMany<MaterialColour, $this>
     */
    public function colours(): HasMany
    {
        return $this->hasMany(MaterialColour::class)
            ->select('material_colours.*')
            ->addSelect(['has_history' => StockMovement::query()
                ->selectRaw('1')
                ->whereColumn('stock_movements.material_id', 'material_colours.material_id')
                ->whereColumn('stock_movements.colour_id', 'material_colours.colour_id')
                ->limit(1)])
            ->join('colours', 'colours.id', '=', 'material_colours.colour_id')
            ->orderBy('colours.position')
            ->orderBy('colours.name')
            ->orderBy('colours.id');
    }

    /**
     * The stock ledger of this material (append-only, see {@see StockMovement}).
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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
     *
     * A per-colour material (INV-21, ADR-0029 section 8) is `out` when every colour is at 0, else `low` when the reorder level is set and
     * some colour is at or under it (a colour at 0 is), else `in_stock`. It reads the loaded `colours` relation and never queries for it:
     * callers eager-load `colours` (`with('colours')`, or `MaterialStockWriter::lock()`), and a one-stock material needs nothing but
     * `stock_mode`, `quantity_on_hand` and `reorder_level` (narrow selects must include `stock_mode`).
     *
     * @throws LogicException when the material is per colour and `colours` is not loaded
     */
    public function stockStatus(): StockStatus
    {
        if ($this->stock_mode === MaterialStockMode::PerColour) {
            $quantities = $this->loadedColours()->map(fn (MaterialColour $row): BigDecimal => BigDecimal::of($row->quantity_on_hand));

            if ($quantities->every(fn (BigDecimal $quantity): bool => $quantity->isZero())) {
                return StockStatus::Out;
            }

            $reorder = $this->reorder_level;

            return $reorder !== null && $quantities->contains(fn (BigDecimal $quantity): bool => $quantity->isLessThanOrEqualTo($reorder))
                ? StockStatus::Low
                : StockStatus::InStock;
        }

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
     * Stock value as a scale-4 string: with cost per colour the sum of each colour's own `round4(quantity x cost)` (it reads the loaded
     * `colours`, see {@see self::stockStatus()}); otherwise `round4(quantity on hand x unit cost)`, the total being the stored one.
     *
     * @throws LogicException when the cost is per colour and `colours` is not loaded
     */
    public function stockValue(): string
    {
        if ($this->stock_mode === MaterialStockMode::PerColour && $this->cost_mode === MaterialCostMode::PerColour) {
            return (string) $this->loadedColours()
                ->reduce(fn (BigDecimal $sum, MaterialColour $row): BigDecimal => $sum->plus(
                    BigDecimal::of($row->quantity_on_hand)->multipliedBy($row->unit_cost ?? '0')->toScale(4, RoundingMode::HalfUp),
                ), BigDecimal::zero())
                ->toScale(4, RoundingMode::HalfUp);
        }

        return (string) BigDecimal::of($this->quantity_on_hand)
            ->multipliedBy($this->unit_cost)
            ->toScale(4, RoundingMode::HalfUp);
    }

    /**
     * Loads the colour rows (with their colour) of the per-colour materials among these, for a Resource or a status: one query for the
     * rows of all of them and one for their colours, and none when no material is per colour. Narrow selects must include `stock_mode`.
     *
     * @param  iterable<Material>  $materials
     */
    public static function loadColours(iterable $materials): void
    {
        $perColour = [];

        foreach ($materials as $material) {
            if ($material->stock_mode === MaterialStockMode::PerColour) {
                $perColour[] = $material;
            }
        }

        (new Collection($perColour))->load('colours.colour');
    }

    /** The status of one colour of a per-colour material, by the one-stock rule on that colour's quantity and the material's reorder level. */
    public function colourStatus(MaterialColour $row): StockStatus
    {
        $quantity = BigDecimal::of($row->quantity_on_hand);

        if ($quantity->isZero()) {
            return StockStatus::Out;
        }

        return $this->reorder_level !== null && $quantity->isLessThanOrEqualTo($this->reorder_level) ? StockStatus::Low : StockStatus::InStock;
    }

    /**
     * Why a colour row can't be removed (INV-21 AC-8), or null when it can: it holds stock ("Indigo has 250 m in stock, so it can't be
     * removed."), or some ledger entry names it ("Black has history, so it can't be removed."). The stock reason comes first.
     *
     * @param  bool  $hasHistory  whether a stock movement names the colour (the `colours` relation's `has_history`, or a query)
     */
    public function colourRemovalRefusal(MaterialColour $row, bool $hasHistory): ?string
    {
        $name = $row->colour->name;
        $quantity = BigDecimal::of($row->quantity_on_hand);

        if (! $quantity->isZero()) {
            return __(':colour has :quantity in stock, so it can\'t be removed.', [
                'colour' => $name,
                'quantity' => MaterialStockSentences::quantity($quantity, $this->unit->code),
            ]);
        }

        if ($hasHistory) {
            return __(':colour has history, so it can\'t be removed.', ['colour' => $name]);
        }

        return null;
    }

    /**
     * @return Collection<int, MaterialColour>
     *
     * @throws LogicException
     */
    private function loadedColours(): Collection
    {
        if (! $this->relationLoaded('colours')) {
            throw new LogicException("Material #{$this->id}: load `colours` before reading the status or value of a material kept per colour.");
        }

        return $this->getRelation('colours');
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
     * The first two arms (with the soft-delete condition) are mirrored by the partial index `materials_restock_idx`
     * (migration 2026_10_13_300000_add_restock_index_to_materials_table). The third is a per-colour material (INV-21) with some colour at or
     * under the reorder level; every colour at 0 is the first arm, since the stored total is their exact sum. The index must hold
     * `stock_mode = 'per_colour'` rows too for the planner to use it with that arm.
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
                ->whereColumn('quantity_on_hand', '<=', 'reorder_level'))
            ->orWhere(fn (Builder $q) => $q
                ->where('stock_mode', MaterialStockMode::PerColour->value)
                ->whereNotNull('reorder_level')
                ->whereExists(MaterialColour::query()
                    ->selectRaw('1')
                    ->whereColumn('material_colours.material_id', 'materials.id')
                    ->whereColumn('material_colours.quantity_on_hand', '<=', 'materials.reorder_level')
                    ->toBase())));
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
