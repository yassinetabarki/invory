<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: one to five units of an active product, sold today. Override only what a test is about.
 *
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'workshop_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->value('workshop_id'),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => sprintf('%d.%04d', fake()->numberBetween(20, 120), fake()->numberBetween(0, 9999)),
            'sold_on' => now()->toDateString(),
            'note' => null,
        ];
    }

    /** Sold `$days` days before today. */
    public function daysAgo(int $days): static
    {
        return $this->state(fn (): array => ['sold_on' => now()->subDays($days)->toDateString()]);
    }
}
