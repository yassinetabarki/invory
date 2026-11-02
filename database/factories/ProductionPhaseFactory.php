<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductionPhase;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A production phase of the current workshop, after its last one. Pass `name` and `position` when a test is about them.
 *
 * @extends Factory<ProductionPhase>
 */
class ProductionPhaseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'name' => fake()->unique()->lexify('Phase ????'),
            'position' => static fn (array $attributes): int => (int) ProductionPhase::query()->where('workshop_id', $attributes['workshop_id'])->max('position') + 1,
        ];
    }
}
