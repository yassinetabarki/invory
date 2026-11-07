<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\ProductRestockLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One product, colour and size a restock added units to. By default a new restock and a new Apparel product of the restock's
 * workshop, the product's first colour (list order; linked when the product has none) and its first size. The colour and the size are always made
 * the product's own (linked when they aren't). It takes its
 * restock's workshop. It never changes the restock's `units` or the shelf: a test that needs them sets them.
 *
 * @extends Factory<ProductRestockLine>
 */
class ProductRestockLineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_restock_id' => ProductRestock::factory(),
            // After `product_restock_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => ProductRestock::query()->whereKey($attributes['product_restock_id'])->value('workshop_id'),
            'product_id' => static fn (array $attributes): int => Product::factory()->state(['category' => 'apparel', 'workshop_id' => $attributes['workshop_id']])->create()->id,
            'colour_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->firstOrFail()->colours()->value('colours.id')
                ?? Colour::query()->where('workshop_id', $attributes['workshop_id'])->orderBy('position')->orderBy('name')->orderBy('id')->value('id'),
            'size_id' => static fn (array $attributes): mixed => Product::withTrashed()->findOrFail((int) $attributes['product_id'])->sizes()->value('sizes.id'),
            'position' => 1,
            'units' => fake()->numberBetween(1, 50),
        ];
    }

    /** The colour and size are always made the product's own, as a restock only ever adds to the product's own colours and sizes. */
    public function configure(): static
    {
        return $this->afterMaking(function (ProductRestockLine $line): void {
            $product = Product::withTrashed()->findOrFail($line->product_id);

            $product->colours()->syncWithoutDetaching([$line->colour_id => ['workshop_id' => $product->workshop_id]]);
            $product->sizes()->syncWithoutDetaching([$line->size_id => ['workshop_id' => $product->workshop_id]]);
        });
    }
}
