<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSizeStock;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The finished units of one colour and size of a product. Pass `product_id` (or `->for($product)`) to place it; otherwise it creates an
 * Apparel product (`ProductFactory` picks a random category, F-68, so the default here is pinned). It takes its product's workshop and,
 * by default, the product's first colour (list order) and the first size of the product's list that has no row yet in that colour (pass
 * `colour_id` and `size_id` to say otherwise; `count()` makes its rows before any is stored, so name the sizes when making several).
 * The colour is always made a colour of the product (linked in `product_colours` when it isn't), as the PostgreSQL key requires; a
 * product without colours takes its workshop's first.
 *
 * @extends Factory<ProductSizeStock>
 */
class ProductSizeStockFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->state(['category' => 'apparel']),
            // After `product_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->value('workshop_id'),
            'colour_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->firstOrFail()->colours()->value('colours.id')
                ?? Colour::query()->where('workshop_id', $attributes['workshop_id'])->orderBy('position')->orderBy('name')->orderBy('id')->value('id'),
            'size_id' => static fn (array $attributes): mixed => Size::query()
                ->where('size_list_id', ProductCategory::query()
                    ->whereKey(Product::withTrashed()->whereKey($attributes['product_id'])->value('category_id'))
                    ->value('size_list_id'))
                ->whereNotIn('id', ProductSizeStock::query()->where('product_id', $attributes['product_id'])->where('colour_id', $attributes['colour_id'])->select('size_id'))
                ->orderBy('position')
                ->value('id'),
            'units' => fake()->numberBetween(1, 50),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProductSizeStock $stock): void {
            $product = Product::withTrashed()->findOrFail($stock->product_id);

            $product->colours()->syncWithoutDetaching([$stock->colour_id => ['workshop_id' => $product->workshop_id]]);
        });
    }
}
