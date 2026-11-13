<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The stock of one colour of a material: 100 in the first colour of the material's workshop (list order) that the material has no row
 * for yet, with no cost of its own. It takes its material's workshop. It does not change the material: use `MaterialFactory::perColour()`
 * for a consistent per-colour material (mode, total and cost), and this factory only for a row on its own.
 *
 * @extends Factory<MaterialColour>
 */
class MaterialColourFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            // After `material_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => Material::withoutGlobalScopes()->whereKey($attributes['material_id'])->value('workshop_id'),
            'colour_id' => static fn (array $attributes): mixed => Colour::withoutGlobalScopes()->where('workshop_id', $attributes['workshop_id'])
                ->whereNotIn('id', MaterialColour::withoutGlobalScopes()->where('material_id', $attributes['material_id'])->select('colour_id'))
                ->orderBy('position')->orderBy('name')->orderBy('id')->value('id'),
            'quantity_on_hand' => '100.0000',
            'unit_cost' => null,
        ];
    }
}
