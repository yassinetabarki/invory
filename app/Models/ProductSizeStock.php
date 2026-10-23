<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductSizeStockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The finished units of a product on the shelf in one size (S-56, ADR-0017). Unique per product and size, and a row exists only
 * while that size holds at least 1 unit: a product with nothing in stock has no rows.
 *
 * @property int $id
 * @property int $workshop_id The workshop of the product. Never mass-assignable: it comes from the product.
 * @property int $product_id
 * @property int $size_id
 * @property int $units Whole units, at least 1.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['size_id', 'units'])]
class ProductSizeStock extends Model
{
    /** @use HasFactory<ProductSizeStockFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['units' => 'integer'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
