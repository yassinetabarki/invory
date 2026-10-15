<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: an order placed today in the current workshop, without lines. Override only what a test is about.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'client' => fake()->company(),
            'ordered_on' => now()->toDateString(),
            'note' => null,
        ];
    }
}
