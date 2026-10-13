<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use App\Services\Costing\ProductCosting;
use App\Services\Photos\PhotoStorage;
use Carbon\CarbonInterface;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * A final fashion product and the cost it adds on top of its materials (the BOM lives in `components`).
 *
 * Cost, capacity and margin are never stored here: App\Services\Costing\ProductCostingService computes them.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property int $category_id
 * @property ProductStatus $status
 * @property string|null $description
 * @property string $labor_cost Decimal string, scale 4.
 * @property string $overhead_cost Decimal string, scale 4.
 * @property string|null $selling_price Decimal string, scale 4.
 * @property string|null $photo_path Public-disk path of the photo (S-15-BE). Narrow selects must include it: `photo_url` reads it.
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property ProductCosting $costing Not a column: the controller sets it from ProductCostingService before a Resource reads it (strict models throw if it forgot).
 * @property-read string|null $photo_url Public URL of the photo (the `public` disk's), null without one. Not a column.
 * @property int $units_sold_last_30_days Not a column: the controller sets it for the list (the sales aggregate of S-23 later).
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 */
#[Fillable(['sku', 'name', 'category_id', 'status', 'description', 'labor_cost', 'overhead_cost', 'selling_price'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The database defaults, so a freshly created model reads like a stored one without a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'labor_cost' => '0.0000',
        'overhead_cost' => '0.0000',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'labor_cost' => 'decimal:4',
            'overhead_cost' => 'decimal:4',
            'selling_price' => 'decimal:4',
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

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * The bill of materials, in display order.
     *
     * @return HasMany<ProductComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The recorded sales of this product (a sale outlives a soft-deleted product, D-3).
     *
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Case-insensitive match on name or SKU.
     *
     * @param  Builder<Product>  $query
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
     * Products of the category with this code (no filter for null).
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, ?string $categoryCode): void
    {
        if ($categoryCode !== null) {
            $query->whereIn('category_id', ProductCategory::query()->where('code', $categoryCode)->select('id'));
        }
    }

    /**
     * By name (case-insensitive), then id: the order of every product list.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function byName(Builder $query): void
    {
        $query->orderByRaw('LOWER(name)')->orderBy('id');
    }

    /**
     * Everything a Resource and the costing read: the category, the composition in display order and the material of each line
     * (with its unit, always eager-loaded; the category of a material is never shown, so it is skipped).
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withComposition(Builder $query): void
    {
        $query->with([
            'category',
            'components.material' => fn ($materials) => $materials->without('category'),
        ]);
    }
}
