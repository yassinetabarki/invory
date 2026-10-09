<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Realistic fashion products: name and category code (a default the reference-data migration inserts).
     *
     * @var list<array{0: string, 1: string}>
     */
    private const CATALOGUE = [
        ['Denim Jacket', 'apparel'],
        ['Cotton T-shirt', 'apparel'],
        ['Slim Fit Jeans', 'apparel'],
        ['Linen Summer Dress', 'apparel'],
        ['Leather Sneaker', 'footwear'],
        ['Suede Chelsea Boot', 'footwear'],
        ['Canvas Tote Bag', 'bag'],
        ['Leather Crossbody Bag', 'bag'],
        ['Braided Leather Belt', 'accessory'],
        ['Silk Scarf', 'accessory'],
    ];

    /**
     * Define the model's default state: an active product without a composition.
     *
     * `category` is a reference CODE here (or a model, or an id): `expandAttributes()` turns it into `category_id`, so
     * `Product::factory()->create(['category' => 'footwear'])` reads like the form.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$name, $category] = fake()->randomElement(self::CATALOGUE);

        return [
            'sku' => fake()->unique()->bothify('PRD-######'),
            'name' => $name,
            'category' => $category,
            'status' => ProductStatus::Active,
            'description' => null,
            'labor_cost' => $this->decimal(fake()->numberBetween(4, 18), fake()->numberBetween(0, 9999)),
            'overhead_cost' => $this->decimal(fake()->numberBetween(1, 5), fake()->numberBetween(0, 9999)),
            'selling_price' => $this->decimal(fake()->numberBetween(40, 220), fake()->numberBetween(0, 9999)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Draft]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Archived]);
    }

    public function withoutSellingPrice(): static
    {
        return $this->state(fn (): array => ['selling_price' => null]);
    }

    /**
     * Give the product a composition once it exists.
     *
     * - An integer makes that many new materials and one line each (random quantity and waste).
     * - A list of lines uses the given materials: `['material' => Material|int, 'quantity' => '1.8', 'waste_percent' => '10', 'notes' => 'Body']`
     *   (`waste_percent` and `notes` are optional). Lines are stored in the order given.
     *
     * @param  int|list<array{material: Material|int, quantity: string, waste_percent?: string, notes?: string|null}>  $lines
     */
    public function withComponents(int|array $lines = 3): static
    {
        return $this->afterCreating(function (Product $product) use ($lines): void {
            if (is_int($lines)) {
                $lines = array_map(fn (): array => [
                    'material' => Material::factory()->create(),
                    'quantity' => $this->decimal(fake()->numberBetween(0, 3), fake()->numberBetween(1, 9999)),
                    'waste_percent' => fake()->randomElement(['0.00', '5.00', '10.00']),
                ], range(1, $lines));
            }

            foreach ($lines as $position => $line) {
                $product->components()->create([
                    'material_id' => $line['material'] instanceof Material ? $line['material']->id : $line['material'],
                    'quantity' => $line['quantity'],
                    'waste_percent' => $line['waste_percent'] ?? '0.00',
                    'notes' => $line['notes'] ?? null,
                    'position' => $position,
                ]);
            }

            $product->unsetRelation('components');
        });
    }

    /**
     * Replace `category` (a code, an id or a model, already reduced to its key) by `category_id`; an explicit `category_id` wins.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected function expandAttributes(array $definition): array
    {
        $attributes = parent::expandAttributes($definition);

        if (! array_key_exists('category', $attributes)) {
            return $attributes;
        }

        $value = $attributes['category'];
        unset($attributes['category']);

        if (! array_key_exists('category_id', $attributes)) {
            $attributes['category_id'] = is_string($value) && ! ctype_digit($value)
                ? ProductCategory::query()->where('code', $value)->value('id') ?? throw new InvalidArgumentException("No product category with code [{$value}].")
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
