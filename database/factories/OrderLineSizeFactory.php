<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colour;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One size row of an order line. Pass `order_line_id` (or `->for($line, 'line')`) to place it; otherwise it creates a line. It takes
 * its line's workshop (pass `workshop_id` to say otherwise) and, by default, the first size of the line's list that has no row yet
 * (pass `size_id` when the list has none left: a "One size" line already holds its only size). Its colour is the first colour of the
 * line's product (INV-13), made a colour of the product when it has none; pass `colour_id` for another.
 *
 * It does not touch the line: its figures are the caller's to keep equal to the sums, which is what `OrderLine::factory()->sized()` does.
 *
 * @extends Factory<OrderLineSize>
 */
class OrderLineSizeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_line_id' => OrderLine::factory(),
            // After `order_line_id`: a closure sees the attributes resolved before it.
            'workshop_id' => static fn (array $attributes): mixed => OrderLine::query()->whereKey($attributes['order_line_id'])->value('workshop_id'),
            'colour_id' => static fn (array $attributes): int => self::firstColourOf((int) OrderLine::query()->whereKey($attributes['order_line_id'])->value('product_id')),
            'size_id' => static fn (array $attributes): mixed => Size::query()
                ->where('size_list_id', OrderLine::query()->whereKey($attributes['order_line_id'])->value('size_list_id'))
                ->whereNotIn('id', OrderLineSize::query()->where('order_line_id', $attributes['order_line_id'])->where('colour_id', $attributes['colour_id'])->select('size_id'))
                ->orderBy('position')
                ->value('id'),
            'quantity' => fake()->numberBetween(1, 100),
            'delivered' => 0,
            'cancelled' => 0,
        ];
    }

    /**
     * The first colour of the product (position, name, id); a product with none gets its workshop's first colour, linked to it.
     */
    public static function firstColourOf(int $productId): int
    {
        $product = Product::withTrashed()->findOrFail($productId);
        $colourId = $product->colours()->value('colours.id');

        if ($colourId === null) {
            $colourId = Colour::query()->where('workshop_id', $product->workshop_id)->orderBy('position')->orderBy('name')->orderBy('id')->value('id');
            $product->colours()->syncWithoutDetaching([$colourId => ['workshop_id' => $product->workshop_id]]);
        }

        return (int) $colourId;
    }
}
