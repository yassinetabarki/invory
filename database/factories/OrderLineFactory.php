<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Size;
use App\Models\SizeList;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * One order line. Pass `order_id` / `product_id` (or `->for($order)`, `->for($product)`) to place it; otherwise it creates an order
 * and an active product of that order's workshop. It takes its order's workshop (pass `workshop_id` to say otherwise).
 *
 * A line is counted by size (ADR-0016). By default it is "One size" (list `one_size`) with one size row holding the line's quantity and
 * counts, built from the saved line: `counts()`, `delivered()`, `cancelled()` and a `create(['quantity' => …])` override keep the row matching.
 * {@see sized()} counts the line by the sizes of a list instead.
 *
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    private const FIGURES = ['quantity', 'delivered', 'cancelled'];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            // After `order_id`: a closure sees the attributes resolved before it.
            'product_id' => static fn (array $attributes): int => Product::factory()
                ->create(['workshop_id' => Order::withTrashed()->whereKey($attributes['order_id'])->value('workshop_id')])->id,
            'workshop_id' => static fn (array $attributes): mixed => Order::withTrashed()->whereKey($attributes['order_id'])->value('workshop_id'),
            'size_list_id' => static fn (): mixed => SizeList::query()->where('code', SizeList::ONE_SIZE)->value('id'),
            'quantity' => fake()->numberBetween(1, 100),
            'position' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];
    }

    /** The size rows of the line, made once it exists: the ones `sized()` planned, or the one row of its list's first size. */
    public function configure(): static
    {
        return $this->afterCreating(function (OrderLine $line): void {
            $rows = $line->relationLoaded('sizes')
                ? $line->getRelation('sizes')
                : new Collection([$this->sizeRow(
                    Size::query()->where('size_list_id', $line->size_list_id)->orderBy('position')->value('id'),
                    $line->only(self::FIGURES),
                )]);

            foreach ($rows as $row) {
                // INV-13: a row without a colour takes the product's first one.
                $row->colour_id ??= OrderLineSizeFactory::firstColourOf($line->product_id);
                $row->forceFill(['workshop_id' => $line->workshop_id, 'order_line_id' => $line->id])->save();
            }

            $line->unsetRelation('sizes');
        });
    }

    /**
     * A line counted by the sizes of a list: one row per size given, and the line's quantity and three counts are the sums of its rows
     * (a `quantity` or counts passed to `create()` are overwritten).
     *
     * The list is the one of the product's category; when the labels are not all in it, the list that holds them (so `['One size' => 5]`
     * gives a line recorded before sizes on a product that has several).
     *
     * @param  array<string, int>  $quantities  size label => quantity (1 or more), e.g. `['S' => 10, 'M' => 20]`
     * @param  array<string, array{0?: int, 1?: int}>  $stages  size label => [delivered, cancelled]; a size left out has none, e.g. `['M' => [10, 0]]`
     */
    public function sized(array $quantities, array $stages = []): static
    {
        return $this->afterMaking(function (OrderLine $line) use ($quantities, $stages): void {
            $labels = array_map('strval', array_keys($quantities));
            $lists = Size::query()->whereIn('label', $labels)->get()->groupBy('size_list_id')
                ->filter(fn (Collection $sizes): bool => $sizes->count() === count($labels));
            $productList = ProductCategory::query()
                ->whereKey(Product::withTrashed()->whereKey($line->product_id)->value('category_id'))->value('size_list_id');
            $listId = $lists->has($productList) ? $productList : ($lists->keys()->first() ?? throw new InvalidArgumentException('No size list has the sizes '.implode(', ', $labels).'.'));

            $rows = new Collection;
            $sums = array_fill_keys(self::FIGURES, 0);

            foreach ($quantities as $label => $quantity) {
                [$delivered, $cancelled] = array_pad($stages[$label] ?? [], 2, 0);
                $figures = array_combine(self::FIGURES, [$quantity, $delivered, $cancelled]);
                $rows->push($this->sizeRow($lists[$listId]->firstWhere('label', (string) $label)?->id, $figures));

                foreach ($figures as $figure => $units) {
                    $sums[$figure] += $units;
                }
            }

            $line->forceFill(['size_list_id' => $listId, ...$sums])->setRelation('sizes', $rows);
        });
    }

    /**
     * Units already delivered or cancelled (INV-14). The quantity is raised to hold them when it would not: the counts never exceed it.
     * To deliver is whatever is left.
     */
    public function counts(int $delivered = 0, int $cancelled = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'quantity' => max((int) $attributes['quantity'], $delivered + $cancelled),
        ]);
    }

    public function delivered(int $units): static
    {
        return $this->counts(delivered: $units);
    }

    public function cancelled(int $units): static
    {
        return $this->counts(cancelled: $units);
    }

    /**
     * An unsaved size row.
     *
     * @param  array<string, int>  $figures  `quantity` and the three counts
     */
    private function sizeRow(?int $sizeId, array $figures): OrderLineSize
    {
        return (new OrderLineSize(['size_id' => $sizeId, 'quantity' => $figures['quantity']]))->forceFill([
            'delivered' => $figures['delivered'], 'cancelled' => $figures['cancelled'],
        ]);
    }
}
