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
            'in_cutting' => 0,
            'ready_to_ship' => 0,
            'shipped' => 0,
        ];
    }

    /**
     * Units already moved on (S-49). The quantity is raised to hold them when it would not: the counts never exceed it. To start is
     * whatever is left.
     */
    public function stages(int $inCutting = 0, int $readyToShip = 0, int $shipped = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'in_cutting' => $inCutting,
            'ready_to_ship' => $readyToShip,
            'shipped' => $shipped,
            'quantity' => max((int) $attributes['quantity'], $inCutting + $readyToShip + $shipped),
        ]);
    }

    public function inCutting(int $units): static
    {
        return $this->stages(inCutting: $units);
    }

    public function readyToShip(int $units): static
    {
        return $this->stages(readyToShip: $units);
    }

    public function shipped(int $units): static
    {
        return $this->stages(shipped: $units);
    }
}
