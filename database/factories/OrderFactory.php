<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Order;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Valid by default: an order placed today in the current workshop, without lines, for a client of its own named like its `client`
 * (INV-4: `orders.client` keeps the client's name). Override only what a test is about; pass `client_id` to use a given client.
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
            'client_id' => static fn (array $attributes): int => Client::factory()->create([
                'workshop_id' => $attributes['workshop_id'],
                'name' => $attributes['client'],
            ])->id,
            'ordered_on' => now()->toDateString(),
            'note' => null,
        ];
    }
}
