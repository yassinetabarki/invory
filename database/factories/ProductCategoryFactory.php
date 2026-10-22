<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\SizeList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A custom category. The seeded defaults come from the migration, so tests read those by code.
 *
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('cat_####'),
            'name' => fake()->unique()->bothify('Category ####'),
            'position' => 100,
            'archived_at' => null,
            'size_list_id' => fn (): mixed => SizeList::query()->where('code', SizeList::ONE_SIZE)->value('id'),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
