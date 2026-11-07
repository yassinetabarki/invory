<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductionBatchStatus;
use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionCounter;
use App\Models\ProductionCut;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A batch in production, In cutting since today, of a new production cut (a new Apparel product) with the same units. By default the
 * product's first colour (linked when it has none) and its first size (both linked when they aren't the product's), and the next number of the workshop, taken from
 * its production counter (so a later Start batch never gets the same number). Override only what a test is about.
 *
 * @extends Factory<ProductionBatch>
 */
class ProductionBatchFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'units' => fake()->numberBetween(1, 20),
            // A `product_id` the test passes is already a number here; the default one (below) is still a closure.
            'production_cut_id' => static fn (array $attributes): int => ProductionCut::factory()->create(array_filter([
                'units' => $attributes['units'],
                'product_id' => is_numeric($attributes['product_id'] ?? null) ? (int) $attributes['product_id'] : null,
            ]))->id,
            'product_id' => static fn (array $attributes): mixed => ProductionCut::query()->whereKey($attributes['production_cut_id'])->value('product_id'),
            'workshop_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->value('workshop_id'),
            'number' => static fn (array $attributes): int => CurrentWorkshop::run(
                (int) $attributes['workshop_id'],
                static fn (): int => ProductionCounter::lock()->next(),
            ),
            'colour_id' => static fn (array $attributes): mixed => Product::withTrashed()->whereKey($attributes['product_id'])->firstOrFail()->colours()->value('colours.id')
                ?? Colour::query()->where('workshop_id', $attributes['workshop_id'])->orderBy('position')->orderBy('name')->orderBy('id')->value('id'),
            'size_id' => static fn (array $attributes): mixed => Product::withTrashed()->findOrFail((int) $attributes['product_id'])->sizes()->value('sizes.id'),
            'status' => ProductionBatchStatus::InProduction,
            'phase_id' => null,
            'phase_since' => now()->toDateString(),
            'started_on' => now()->toDateString(),
        ];
    }

    /** The colour and size are always made the product's own, as Start batch only makes batches of the product's own colours and sizes. */
    public function configure(): static
    {
        return $this->afterMaking(function (ProductionBatch $batch): void {
            $product = Product::withTrashed()->findOrFail($batch->product_id);

            $product->colours()->syncWithoutDetaching([$batch->colour_id => ['workshop_id' => $product->workshop_id]]);
            $product->sizes()->syncWithoutDetaching([$batch->size_id => ['workshop_id' => $product->workshop_id]]);
        });
    }
}
