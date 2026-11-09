<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductComponentQuantityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The quantity of a "By colour and size" composition line for one colour and one size (INV-16, ADR-0027): the amount of the material in
 * ONE unit of that variant, in the material's unit. One row per filled cell; a variant with no row doesn't use the material. The colour
 * and the size are always the product's own, and the product the line's (PostgreSQL enforces all three with composite keys).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the line. Never mass-assignable.
 * @property int $product_component_id Set by `ProductComponent::quantities()`.
 * @property int $product_id
 * @property int $colour_id
 * @property int $size_id
 * @property string $quantity Decimal string, scale 4, > 0 and at most 1,000,000.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['product_id', 'colour_id', 'size_id', 'quantity'])]
class ProductComponentQuantity extends Model
{
    /** @use HasFactory<ProductComponentQuantityFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    /** @return BelongsTo<ProductComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(ProductComponent::class, 'product_component_id');
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
