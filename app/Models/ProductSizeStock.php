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
 * The finished units of a product on the shelf in one colour and one size (S-56, ADR-0017; per colour since INV-2, ADR-0019). One row
 * per product, colour and size, and a row exists only while that variant holds at least 1 unit: a product with nothing in stock has no
 * rows. The colour is always one of the product's colours (PostgreSQL enforces it with a composite key).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the product. Never mass-assignable: it comes from the product.
 * @property int $product_id
 * @property int $colour_id
 * @property int $size_id
 * @property int $units Whole units, at least 1.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['colour_id', 'size_id', 'units'])]
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
}
