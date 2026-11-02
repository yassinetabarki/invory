<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Material;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * What a production cut took of one material. Pass `production_cut_id` and `material_id`; by default a new cut and a new material of
 * its workshop, 1 per unit, with the material's unit.
 *
 * @extends Factory<ProductionCutMaterial>
 */
class ProductionCutMaterialFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'production_cut_id' => ProductionCut::factory(),
            'workshop_id' => static fn (array $attributes): mixed => ProductionCut::query()->whereKey($attributes['production_cut_id'])->value('workshop_id'),
            'material_id' => static fn (array $attributes): int => Material::factory()->create(['workshop_id' => $attributes['workshop_id']])->id,
            'unit_id' => static fn (array $attributes): mixed => Material::withTrashed()->whereKey($attributes['material_id'])->value('unit_id'),
            'quantity_per_unit' => '1.00000000',
            'quantity_taken' => '1.0000',
        ];
    }
}
