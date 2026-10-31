<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Actions\Colours\CreateDefaultColours;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A workshop as sign-up makes it: empty except for its 20 default colours (INV-1, R6), so a test sees what a real workshop has.
 *
 * @extends Factory<Workshop>
 */
class WorkshopFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }

    public function configure(): static
    {
        return $this->afterCreating(static function (Workshop $workshop): void {
            app(CreateDefaultColours::class)->handle($workshop);
        });
    }
}
