<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One filled cell of a by-colour-and-size line. Pass `product_component_id` (or `->for($component)`) to place it; otherwise it creates a
 * by-colour-and-size line. It takes its line's workshop and product and, by default, the product's first colour (list order; the
 * workshop's first when the product has none) and the first size of the product that has no cell yet on that line in that colour (pass
 * `colour_id` and `size_id` to say otherwise; `count()` makes its rows before any is stored, so name the sizes when making several).
 * The colour is always made a colour of the product and the size a size of the product (linked when they aren't), as the PostgreSQL
 * keys require.
 *
 * @extends Factory<ProductComponentQuantity>
 */
class ProductComponentQuantityFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_component_id' => ProductComponent::factory()->byVariant(),
            // After `product_component_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => ProductComponent::query()->whereKey($attributes['product_component_id'])->value('workshop_id'),
            'product_id' => static fn (array $attributes): mixed => ProductComponent::query()->whereKey($attributes['product_component_id'])->value('product_id'),
            'colour_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->firstOrFail()->colours()->value('colours.id')
                ?? Colour::query()->where('workshop_id', $attributes['workshop_id'])->orderBy('position')->orderBy('name')->orderBy('id')->value('id'),
            'size_id' => static fn (array $attributes): mixed => Product::withTrashed()->findOrFail((int) $attributes['product_id'])->sizes()
                ->whereNotIn('sizes.id', ProductComponentQuantity::query()->where('product_component_id', $attributes['product_component_id'])->where('colour_id', $attributes['colour_id'])->select('size_id'))
                ->value('sizes.id'),
            'quantity' => sprintf('%d.%04d', fake()->numberBetween(0, 4), fake()->numberBetween(1, 9999)),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProductComponentQuantity $cell): void {
            $product = Product::withTrashed()->findOrFail($cell->product_id);

            $product->colours()->syncWithoutDetaching([$cell->colour_id => ['workshop_id' => $product->workshop_id]]);
            $product->sizes()->syncWithoutDetaching([$cell->size_id => ['workshop_id' => $product->workshop_id]]);
        });
    }
}
