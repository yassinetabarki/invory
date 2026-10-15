<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\ProductComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a product's bill of materials: the quantity of a material that goes into ONE unit of the product, in the
 * material's own unit, plus the expected waste. One line per material per product (database-unique).
 *
 * @property int $id
 * @property int $workshop_id The workshop of the product (and of its material). Never mass-assignable: it comes from the product.
 * @property int $product_id
 * @property int $material_id
 * @property string $quantity Decimal string, scale 4, > 0.
 * @property string $waste_percent Decimal string, scale 2, 0 <= x < 100.
 * @property string|null $notes Placement, e.g. "body", "lining".
 * @property int $position Display order.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['material_id', 'quantity', 'waste_percent', 'notes', 'position'])]
class ProductComponent extends Model
{
    /** @use HasFactory<ProductComponentFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'waste_percent' => '0.00',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
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
}
