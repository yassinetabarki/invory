<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Material;
use App\Models\ProductionCutVariant;
use App\Models\ProductionCutVariantMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * What one unit of a production cut's colour and size took of one material. Pass `production_cut_variant_id` and `material_id`; by
 * default a new variant and a new material of its workshop, 1 per unit.
 *
 * @extends Factory<ProductionCutVariantMaterial>
 */
class ProductionCutVariantMaterialFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'production_cut_variant_id' => ProductionCutVariant::factory(),
            'workshop_id' => static fn (array $attributes): mixed => ProductionCutVariant::query()->whereKey($attributes['production_cut_variant_id'])->value('workshop_id'),
            'material_id' => static fn (array $attributes): int => Material::factory()->create(['workshop_id' => $attributes['workshop_id']])->id,
            'quantity_per_unit' => '1.00000000',
        ];
    }
}
