<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UnitDimension;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A custom (non-system) unit by default. The seeded units come from the migration, so tests read those by code.
 *
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('u_####'),
            'name' => fake()->unique()->bothify('Unit ####'),
            'dimension' => UnitDimension::Count,
            'factor_to_base' => '1.000000000000',
            'is_system' => false,
            'position' => 100,
            'archived_at' => null,
        ];
    }

    public function dimension(UnitDimension $dimension, ?string $factor = '1'): static
    {
        return $this->state(fn (): array => [
            'dimension' => $dimension,
            'factor_to_base' => $dimension->hasFactor() ? $factor : null,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (): array => ['is_system' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
