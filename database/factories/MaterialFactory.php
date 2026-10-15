<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Unit;
use Database\Factories\Concerns\DefaultWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Realistic fashion materials: name, category code, unit code, unit cost range in whole currency units.
     * The codes are the defaults the reference-data migration inserts.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int, 4: int}>
     */
    private const CATALOGUE = [
        ['Denim 12oz Indigo', 'fabric', 'm', 6, 11],
        ['Cotton poplin white', 'fabric', 'm', 4, 8],
        ['Viscose lining black', 'lining', 'm', 2, 4],
        ['Fusible interlining 90cm', 'interlining', 'm', 1, 3],
        ['Full-grain leather tan', 'leather', 'sqft', 5, 12],
        ['Polyester thread', 'thread', 'cone', 1, 4],
        ['Grosgrain ribbon 25mm', 'trim', 'm', 0, 1],
        ['Metal shank button 17mm', 'fastener', 'pc', 0, 1],
        ['YKK zipper 20cm', 'fastener', 'pc', 0, 2],
        ['Woven brand label', 'label', 'pc', 0, 1],
        ['Rubber outsole size 42', 'sole', 'pair', 3, 7],
        ['Brass D-ring 25mm', 'hardware', 'pc', 0, 1],
        ['Recycled poly mailer', 'packaging', 'pc', 0, 1],
    ];

    /**
     * Define the model's default state (always in stock).
     *
     * `category` and `unit` are reference CODES here (or a model, or an id): `expandAttributes()` turns them into
     * `category_id` and `unit_id`, so `Material::factory()->create(['category' => 'thread', 'unit' => 'cone'])` reads like the form.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$name, $category, $unit, $minCost, $maxCost] = fake()->randomElement(self::CATALOGUE);

        return [
            'workshop_id' => static fn (): int => DefaultWorkshop::id(),
            'sku' => fake()->unique()->bothify('MAT-######'),
            'name' => $name,
            'category' => $category,
            'unit' => $unit,
            'unit_cost' => $this->decimal(fake()->numberBetween($minCost, $maxCost), fake()->numberBetween(0, 9999)),
            'quantity_on_hand' => $this->decimal(fake()->numberBetween(200, 2000), fake()->numberBetween(0, 9999)),
            'reorder_level' => $this->decimal(fake()->numberBetween(10, 100), 0),
            'supplier_name' => fake()->company(),
            'supplier_reference' => fake()->bothify('??-####'),
            'color' => fake()->safeColorName(),
            'composition' => null,
            'notes' => null,
        ];
    }

    /**
     * Quantity at or below the reorder level, but not zero.
     */
    public function lowStock(): static
    {
        return $this->state(fn (): array => [
            'reorder_level' => '100.0000',
            'quantity_on_hand' => $this->decimal(fake()->numberBetween(1, 99), fake()->numberBetween(0, 9999)),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['quantity_on_hand' => '0.0000']);
    }

    /**
     * Replace `category` and `unit` (a code, an id or a model, already reduced to its key) by `category_id` and `unit_id`; an explicit
     * `category_id` / `unit_id` wins over them.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected function expandAttributes(array $definition): array
    {
        $attributes = parent::expandAttributes($definition);

        foreach ([['category', MaterialCategory::class], ['unit', Unit::class]] as [$key, $model]) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            $value = $attributes[$key];
            unset($attributes[$key]);

            // An explicit `category_id` or `unit_id` beats the default code of the definition.
            if (array_key_exists("{$key}_id", $attributes)) {
                continue;
            }

            $attributes["{$key}_id"] = is_string($value) && ! ctype_digit($value)
                ? $model::query()->where('code', $value)->value('id') ?? throw new InvalidArgumentException("No {$key} with code [{$value}].")
                : $value;
        }

        return $attributes;
    }

    /**
     * Build a scale-4 decimal string from its integer and fractional parts (no float maths).
     */
    private function decimal(int $whole, int $fraction): string
    {
        return sprintf('%d.%04d', $whole, $fraction);
    }
}
