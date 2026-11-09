<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComponentQuantityMode;
use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One line of a product's bill of materials: the quantity of a material that goes into ONE unit of the product, in the
 * material's own unit, plus the expected waste. One line per material per product (database-unique).
 *
 * Since INV-16 (ADR-0027) a line is either {@see ComponentQuantityMode::Same} (one `quantity`, as before) or
 * {@see ComponentQuantityMode::ByVariant} (`quantity` is null; its quantities are the {@see ProductComponentQuantity} cells, and a
 * colour and size with no cell doesn't use the material).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the product (and of its material). Never mass-assignable: it comes from the product.
 * @property int $product_id
 * @property int $material_id
 * @property ComponentQuantityMode $quantity_mode
 * @property string|null $quantity Decimal string, scale 4, > 0. Null exactly when the line is by colour and size.
 * @property string $waste_percent Decimal string, scale 2, 0 <= x < 100.
 * @property string|null $notes Placement, e.g. "body", "lining".
 * @property int $position Display order.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['material_id', 'quantity_mode', 'quantity', 'waste_percent', 'notes', 'position'])]
class ProductComponent extends Model
{
    /** @use HasFactory<ProductComponentFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'quantity_mode' => 'same',
        'waste_percent' => '0.00',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_mode' => ComponentQuantityMode::class,
            'quantity' => 'decimal:4',
            'waste_percent' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Soft-deleted materials stay readable: the BOM of a deleted product (history, restore) still points at them.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    /**
     * The filled cells of a by-colour-and-size line (none on a `same` line).
     *
     * @return HasMany<ProductComponentQuantity, $this>
     */
    public function quantities(): HasMany
    {
        return $this->hasMany(ProductComponentQuantity::class);
    }
}
