<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Material;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * What a cut took of one material. Pass `order_line_cut_id` / `material_id` (or `->for($cut, 'cut')`, `->for($material)`) to place it;
 * otherwise it creates a cut, and a material of that cut's workshop. It takes its cut's workshop and the material's unit (pass
 * `workshop_id` / `unit_id` to say otherwise). The amounts are `quantity_per_unit` 1.98000000 and `quantity_taken` 59.4000 (30 units).
 *
 * @extends Factory<OrderLineCutMaterial>
 */
class OrderLineCutMaterialFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_line_cut_id' => OrderLineCut::factory(),
            // After `order_line_cut_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => OrderLineCut::query()->whereKey($attributes['order_line_cut_id'])->value('workshop_id'),
            'material_id' => static fn (array $attributes): int => Material::factory()
                ->create(['workshop_id' => OrderLineCut::query()->whereKey($attributes['order_line_cut_id'])->value('workshop_id')])->id,
            'unit_id' => static fn (array $attributes): mixed => Material::withTrashed()->without('category', 'unit')->whereKey($attributes['material_id'])->value('unit_id'),
            'quantity_per_unit' => '1.98000000',
            'quantity_taken' => '59.4000',
        ];
    }
}
