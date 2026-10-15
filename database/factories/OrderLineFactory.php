<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One order line. Pass `order_id` / `product_id` (or `->for($order)`, `->for($product)`) to place it; otherwise it creates an order
 * and an active product of that order's workshop. It takes its order's workshop (pass `workshop_id` to say otherwise).
 *
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            // After `order_id`: a closure sees the attributes resolved before it.
            'product_id' => static fn (array $attributes): int => Product::factory()
                ->create(['workshop_id' => Order::withTrashed()->whereKey($attributes['order_id'])->value('workshop_id')])->id,
            'workshop_id' => static fn (array $attributes): mixed => Order::withTrashed()->whereKey($attributes['order_id'])->value('workshop_id'),
            'quantity' => fake()->numberBetween(1, 100),
            'position' => 0,
        ];
    }
}
