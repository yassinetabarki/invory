<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductSizeStock;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Products\SizeLists;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The read side of Progress (INV-5-f, AC-3 and AC-4): the tiles, the product cards of "All phases" and the view of one phase. Read-only;
 * the workshop scope limits every query to the current workshop. Every figure is added up here, never on the page. Whole units only.
 *
 * Filters: `product` and `colour` (ids, or null). Only batches in production count; finished and undone batches never show.
 */
final readonly class ProgressBook
{
    public const PRODUCTS_PER_PAGE = 10;

    public const BATCHES_PER_PAGE = 25;

    public function __construct(private SizeLists $sizeLists) {}

    /**
     * The tiles: the units in production per step of the path (In cutting first), with the filters.
     *
     * @return list<array{phase: string|int, name: string, units: int}>
     */
    public function tiles(ProductionPath $path, ?int $product, ?int $colour): array
    {
        $byPhase = [];

        foreach ($this->filtered($product, $colour)->groupBy('phase_id')->toBase()->selectRaw('phase_id, SUM(units) as units')->get() as $group) {
            $byPhase[ProductionPath::refOf($group->phase_id === null ? null : (int) $group->phase_id)] = (int) $group->units;
        }

        return array_map(static fn (array $step): array => [
            'phase' => $step['ref'],
            'name' => $step['name'],
            'units' => $byPhase[$step['ref']] ?? 0,
        ], $path->steps);
    }

    /** The units in production, with the filters (the All phases tile) or without (null filters). */
    public function units(?int $product = null, ?int $colour = null): int
    {
        return (int) $this->filtered($product, $colour)->sum('units');
    }

    /** True when the workshop has any batch in production: tells "Nothing in production" from no match. */
    public function hasBatches(): bool
    {
        return ProductionBatch::query()->inProduction()->exists();
    }

    /**
     * The product cards of "All phases" (AC-3): the products with units in production, A → Z, 10 a page, each with its rows per colour
     * (a subtotal row, then a row per size in production) and its Total. Five queries for a page, whatever its size.
     *
     * @return LengthAwarePaginator<int, array{product: Product, units: int, batches: int, one_size: bool, rows: list<array{colour: Colour, size: Size|null, by_phase: list<int>, in_production: int, on_shelf: int|null}>, total: array{by_phase: list<int>, in_production: int}}>
     */
    public function products(ProductionPath $path, ?int $product, ?int $colour): LengthAwarePaginator
    {
        $page = Product::withTrashed()
            ->whereIn('id', $this->filtered($product, $colour)->select('product_id'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PRODUCTS_PER_PAGE)
            ->withQueryString();

        $ids = $page->getCollection()->modelKeys();

        $groups = $this->filtered($product, $colour)
            ->whereIn('product_id', $ids)
            ->groupBy('product_id', 'colour_id', 'size_id', 'phase_id')
            ->toBase()
            ->selectRaw('product_id, colour_id, size_id, phase_id, SUM(units) as units, COUNT(*) as batches')
            ->get();

        $colours = Colour::query()->whereIn('id', $groups->pluck('colour_id')->unique()->all())->ordered()->get()->keyBy('id');
        $sizes = Size::query()->whereIn('id', $groups->pluck('size_id')->unique()->all())->get()->keyBy('id');
        $shelf = $this->shelf(array_values(array_map('intval', $ids)));
        $refs = array_column($path->steps, 'ref');

        return $page->through(function (Product $product) use ($groups, $colours, $sizes, $shelf, $refs): array {
            $mine = $groups->where('product_id', $product->id);
            $oneSize = $this->sizeLists->category($product->category_id)->sizeList->code === SizeList::ONE_SIZE;
            $rows = [];
            $total = array_fill(0, count($refs), 0);

            foreach ($colours as $colour) {
                $ofColour = $mine->where('colour_id', $colour->id);

                if ($ofColour->isEmpty()) {
                    continue;
                }

                $colourRow = array_fill(0, count($refs), 0);
                $sizeRows = [];

                foreach ($ofColour->pluck('size_id')->unique()->sortBy(static fn (mixed $id): array => [$sizes[$id]->position, (int) $id]) as $sizeId) {
                    $byPhase = array_map(
                        static fn (string|int $ref): int => (int) $ofColour->where('size_id', $sizeId)->first(
                            static fn (object $group): bool => ProductionPath::refOf($group->phase_id === null ? null : (int) $group->phase_id) === $ref,
                        )?->units,
                        $refs,
                    );

                    foreach ($byPhase as $i => $units) {
                        $colourRow[$i] += $units;
                        $total[$i] += $units;
                    }

                    $sizeRows[] = [
                        'colour' => $colour,
                        'size' => $sizes[$sizeId],
                        'by_phase' => $byPhase,
                        'in_production' => array_sum($byPhase),
                        'on_shelf' => $shelf[$product->id][$colour->id][(int) $sizeId] ?? 0,
                    ];
                }

                // A "One size" product has colour rows only: its one size row carries the shelf.
                if ($oneSize) {
                    $rows[] = $sizeRows[0];

                    continue;
                }

                $rows[] = ['colour' => $colour, 'size' => null, 'by_phase' => array_values($colourRow), 'in_production' => array_sum($colourRow), 'on_shelf' => null];
                array_push($rows, ...$sizeRows);
            }

            return [
                'product' => $product,
                'units' => array_sum($total),
                'batches' => (int) $mine->sum('batches'),
                'one_size' => $oneSize,
                'rows' => $rows,
                'total' => ['by_phase' => array_values($total), 'in_production' => array_sum($total)],
            ];
        });
    }

    /**
     * The view of one phase (AC-4): its batches, oldest first (the day they got there, then the batch), 25 a page, each with "days
     * since" and what its colour and size has on the shelf; and the heading's figures.
     *
     * @return array{units: int, batch_count: int, product_count: int, oldest_since: string|null, batches: LengthAwarePaginator<int, array{batch: ProductionBatch, one_size: bool, since_days: int, on_shelf: int}>}
     */
    public function phase(string|int $ref, ?int $product, ?int $colour): array
    {
        $inPhase = fn (): Builder => $this->filtered($product, $colour)->where('phase_id', ProductionPath::phaseIdOf($ref));

        $stats = $inPhase()->toBase()
            ->selectRaw('COALESCE(SUM(units), 0) as units, COUNT(*) as batches, COUNT(DISTINCT product_id) as products, MIN(phase_since) as oldest')
            ->first();

        $page = $inPhase()
            ->with(['product', 'colour', 'size'])
            ->orderBy('phase_since')
            ->orderBy('id')
            ->paginate(self::BATCHES_PER_PAGE)
            ->withQueryString();

        $shelf = $this->shelf(array_values(array_unique(array_map(static fn (ProductionBatch $batch): int => $batch->product_id, $page->items()))));
        $today = today();

        return [
            'units' => (int) ($stats->units ?? 0),
            'batch_count' => (int) ($stats->batches ?? 0),
            'product_count' => (int) ($stats->products ?? 0),
            'oldest_since' => $stats?->oldest === null ? null : substr((string) $stats->oldest, 0, 10),
            'batches' => $page->through(fn (ProductionBatch $batch): array => [
                'batch' => $batch,
                'one_size' => $this->sizeLists->category($batch->product->category_id)->sizeList->code === SizeList::ONE_SIZE,
                'since_days' => (int) $batch->phase_since->diffInDays($today),
                'on_shelf' => $shelf[$batch->product_id][$batch->colour_id][$batch->size_id] ?? 0,
            ]),
        ];
    }

    /** @return Builder<ProductionBatch> the batches in production, with the filters */
    private function filtered(?int $product, ?int $colour): Builder
    {
        return ProductionBatch::query()
            ->inProduction()
            ->when($product !== null, static fn (Builder $query) => $query->where('product_id', $product))
            ->when($colour !== null, static fn (Builder $query) => $query->where('colour_id', $colour));
    }

    /**
     * What the products have on the shelf, in one query.
     *
     * @param  list<int>  $productIds
     * @return array<int, array<int, array<int, int>>> product id → colour id → size id → units
     */
    private function shelf(array $productIds): array
    {
        $shelf = [];

        if ($productIds === []) {
            return $shelf;
        }

        /** @var Collection<int, ProductSizeStock> $rows */
        $rows = ProductSizeStock::query()->whereIn('product_id', $productIds)->get(['product_id', 'colour_id', 'size_id', 'units']);

        foreach ($rows as $row) {
            $shelf[$row->product_id][$row->colour_id][$row->size_id] = $row->units;
        }

        return $shelf;
    }
}
