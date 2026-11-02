<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductRestockLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one restock added to one product in one colour and size (INV-3, ADR-0022). Only variants that got units have a line. The
 * product may since have been deleted (products are soft-deleted), so {@see self::product()} reads deleted ones too.
 *
 * @property int $id
 * @property int $workshop_id The restock's workshop. Never mass-assignable.
 * @property int $product_restock_id
 * @property int $product_id
 * @property int $colour_id
 * @property int $size_id
 * @property int $position The product's place on the restock, from 1, in the order it was added.
 * @property int $units 1 to 1,000,000.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['product_id', 'colour_id', 'size_id', 'position', 'units'])]
class ProductRestockLine extends Model
{
    /** @use HasFactory<ProductRestockLineFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['position' => 'integer', 'units' => 'integer'];
    }

    /** @return BelongsTo<ProductRestock, $this> */
    public function restock(): BelongsTo
    {
        return $this->belongsTo(ProductRestock::class, 'product_restock_id');
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
