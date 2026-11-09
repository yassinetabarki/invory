<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComponentQuantityMode;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One BOM line. Pass `product_id` / `material_id` (or `->for($product)`, `->for($material)`) to place it; otherwise it creates both.
 * It takes its product's workshop (pass `workshop_id` to say otherwise). By default it is "Same for every variant" with a quantity;
 * {@see byVariant()} makes it a by-colour-and-size line with no quantity, whose cells {@see ProductComponentQuantityFactory} makes.
 *
 * @extends Factory<ProductComponent>
 */
class ProductComponentFactory extends Factory
{
    private const NOTES = ['Body', 'Lining', 'Seams', 'Closure', 'Label', 'Outsole', null, null];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'material_id' => Material::factory(),
            // After `product_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->value('workshop_id'),
            'quantity_mode' => ComponentQuantityMode::Same,
            'quantity' => sprintf('%d.%04d', fake()->numberBetween(0, 4), fake()->numberBetween(1, 9999)),
            'waste_percent' => fake()->randomElement(['0.00', '2.50', '5.00', '10.00']),
            'notes' => fake()->randomElement(self::NOTES),
            'position' => 0,
        ];
    }

    /** A line by colour and size: no quantity of its own (the table allows a null one only for this mode). */
    public function byVariant(): static
    {
        return $this->state(fn (): array => ['quantity_mode' => ComponentQuantityMode::ByVariant, 'quantity' => null]);
    }
}
