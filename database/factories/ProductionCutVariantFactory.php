<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductionCut;
use App\Models\ProductionCutVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One colour and size of a production cut, with nothing returned. Pass `production_cut_id` to place it; otherwise it creates a cut. It
 * takes its cut's workshop and, by default, the cut product's first colour (the workshop's first when it has none) and the first size of
 * the product that has no variant yet on that cut in that colour (pass `colour_id` and `size_id` to say otherwise). The colour and size
 * are always made the product's own. Add its materials with {@see ProductionCutVariantMaterialFactory}.
 *
 * @extends Factory<ProductionCutVariant>
 */
class ProductionCutVariantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'production_cut_id' => ProductionCut::factory(),
            // After `production_cut_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => ProductionCut::query()->whereKey($attributes['production_cut_id'])->value('workshop_id'),
            'colour_id' => static fn (array $attributes): mixed => self::productOf($attributes)->colours()->value('colours.id')
                ?? Colour::query()->where('workshop_id', $attributes['workshop_id'])->orderBy('position')->orderBy('name')->orderBy('id')->value('id'),
            'size_id' => static fn (array $attributes): mixed => self::productOf($attributes)->sizes()
                ->whereNotIn('sizes.id', ProductionCutVariant::query()->where('production_cut_id', $attributes['production_cut_id'])->where('colour_id', $attributes['colour_id'])->select('size_id'))
                ->value('sizes.id'),
            'units' => fake()->numberBetween(1, 20),
            'units_returned' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProductionCutVariant $variant): void {
            $product = self::productOf(['production_cut_id' => $variant->production_cut_id]);

            $product->colours()->syncWithoutDetaching([$variant->colour_id => ['workshop_id' => $product->workshop_id]]);
            $product->sizes()->syncWithoutDetaching([$variant->size_id => ['workshop_id' => $product->workshop_id]]);
        });
    }

    /** @param  array<string, mixed>  $attributes */
    private static function productOf(array $attributes): Product
    {
        return Product::withTrashed()->findOrFail((int) ProductionCut::query()->whereKey($attributes['production_cut_id'])->value('product_id'));
    }
}
