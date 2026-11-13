<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkshop;
use Carbon\CarbonInterface;
use Database\Factories\MaterialColourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The stock of one colour of a material kept per colour (INV-21, ADR-0029): its quantity and, with cost per colour, its unit cost. One
 * row per colour, none for a one-stock material. Only `MaterialStockWriter` (INV-21-b) writes it, together with the material's total.
 *
 * @property int $id
 * @property int $workshop_id The workshop of the material. Never mass-assignable.
 * @property int $material_id Set by `Material::colours()`.
 * @property int $colour_id
 * @property string $quantity_on_hand Decimal string, scale 4, at least 0.
 * @property string|null $unit_cost Decimal string, scale 4, set exactly when the material's cost is per colour.
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['colour_id', 'quantity_on_hand', 'unit_cost'])]
class MaterialColour extends Model
{
    /** @use HasFactory<MaterialColourFactory> */
    use BelongsToWorkshop, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_on_hand' => 'decimal:4', 'unit_cost' => 'decimal:4'];
    }

    /** @return BelongsTo<Material, $this> */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class)->withTrashed();
    }

    /** @return BelongsTo<Colour, $this> */
    public function colour(): BelongsTo
    {
        return $this->belongsTo(Colour::class);
    }
}
