<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductionCut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One Start batch's record of what it took. Pass `product_id` to place it; otherwise it creates an Apparel product. It takes its
 * product's workshop and has no materials: add them with {@see ProductionCutMaterialFactory}.
 *
 * @extends Factory<ProductionCut>
 */
class ProductionCutFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => static fn (): int => Product::factory()->create(['category' => 'apparel'])->id,
            'workshop_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->value('workshop_id'),
            'units' => fake()->numberBetween(1, 50),
            'units_returned' => 0,
            'user_id' => null,
        ];
    }
}
