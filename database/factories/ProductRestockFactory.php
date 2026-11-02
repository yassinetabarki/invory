<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductRestock;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A restock of the current workshop, recorded today, not undone. Its `units` is the total of its lines, so a test that adds lines
 * sets it too (or uses `ProductRestockLineFactory`, which leaves it alone). Override only what a test is about.
 *
 * @extends Factory<ProductRestock>
 */
class ProductRestockFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'restocked_on' => now()->toDateString(),
            'reference' => null,
            'note' => null,
            'units' => 1,
        ];
    }

    public function undone(): static
    {
        return $this->state(fn (): array => ['undone_at' => now()]);
    }
}
