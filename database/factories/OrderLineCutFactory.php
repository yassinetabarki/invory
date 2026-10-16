<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderLine;
use App\Models\OrderLineCut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One cut of an order line. Pass `order_line_id` (or `->for($line, 'line')`) to place it; otherwise it creates a line. It takes its
 * line's workshop (pass `workshop_id` to say otherwise) and has no materials: add them with {@see OrderLineCutMaterialFactory}.
 *
 * @extends Factory<OrderLineCut>
 */
class OrderLineCutFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_line_id' => OrderLine::factory(),
            // After `order_line_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => OrderLine::query()->whereKey($attributes['order_line_id'])->value('workshop_id'),
            'units' => fake()->numberBetween(1, 50),
            'units_returned' => 0,
            'user_id' => null,
        ];
    }

    /** Units of this cut already moved back to To start. */
    public function returned(int $units): static
    {
        return $this->state(fn (): array => ['units_returned' => $units]);
    }
}
