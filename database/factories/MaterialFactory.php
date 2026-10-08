<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MaterialCategory;
use App\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Realistic fashion materials: name, category, unit, unit cost range in whole currency units.
     *
     * @var list<array{0: string, 1: MaterialCategory, 2: UnitOfMeasure, 3: int, 4: int}>
     */
    private const CATALOGUE = [
        ['Denim 12oz Indigo', MaterialCategory::Fabric, UnitOfMeasure::Metre, 6, 11],
        ['Cotton poplin white', MaterialCategory::Fabric, UnitOfMeasure::Metre, 4, 8],
        ['Viscose lining black', MaterialCategory::Lining, UnitOfMeasure::Metre, 2, 4],
        ['Fusible interlining 90cm', MaterialCategory::Interlining, UnitOfMeasure::Metre, 1, 3],
        ['Full-grain leather tan', MaterialCategory::Leather, UnitOfMeasure::SquareFoot, 5, 12],
        ['Polyester thread', MaterialCategory::Thread, UnitOfMeasure::Cone, 1, 4],
        ['Grosgrain ribbon 25mm', MaterialCategory::Trim, UnitOfMeasure::Metre, 0, 1],
        ['Metal shank button 17mm', MaterialCategory::Fastener, UnitOfMeasure::Piece, 0, 1],
        ['YKK zipper 20cm', MaterialCategory::Fastener, UnitOfMeasure::Piece, 0, 2],
        ['Woven brand label', MaterialCategory::Label, UnitOfMeasure::Piece, 0, 1],
        ['Rubber outsole size 42', MaterialCategory::Sole, UnitOfMeasure::Pair, 3, 7],
        ['Brass D-ring 25mm', MaterialCategory::Hardware, UnitOfMeasure::Piece, 0, 1],
        ['Recycled poly mailer', MaterialCategory::Packaging, UnitOfMeasure::Piece, 0, 1],
    ];

    /**
     * Define the model's default state (always in stock).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$name, $category, $unit, $minCost, $maxCost] = fake()->randomElement(self::CATALOGUE);

        return [
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
     * Build a scale-4 decimal string from its integer and fractional parts (no float maths).
     */
    private function decimal(int $whole, int $fraction): string
    {
        return sprintf('%d.%04d', $whole, $fraction);
    }
}
