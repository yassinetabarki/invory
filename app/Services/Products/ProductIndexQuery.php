<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Enums\SalesPeriod;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Costing\ProductCostingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * The page of the products list: SQL filters (name or SKU, category code, status), by name then id, 25 to a page, and every
 * product on the page carrying its `costing` (the Resources read it).
 *
 * "Can't produce" (`max_producible = 0`) is a result of the costing, not a column, so that filter costs the whole filtered set
 * and keeps the matches in PHP before paginating. This interim is accepted until S-23 adds `product_metrics` (capacity as a
 * column), which turns it into a SQL condition; every other filter paginates in SQL and costs the one page.
 */
final readonly class ProductIndexQuery
{
    public function __construct(private ProductCostingService $costing) {}

    /**
     * @param  array{search: string|null, category: string|null, status: string|null, cant_produce: bool}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Product::query()
            ->withComposition()
            // `units_sold_last_30_days` (S-14-BE): a correlated sum of the integer `quantity`, so no query of its own and no money in SQL.
            ->withSum(['sales as units_sold_last_30_days' => self::soldInLastThirtyDays(...)], 'quantity')
            ->search($filters['search'])
            ->inCategory($filters['category'])
            ->when($filters['status'], fn ($products, string $status) => $products->where('status', $status))
            ->byName();

        if (! $filters['cant_produce']) {
            $page = $query->paginate($perPage)->withQueryString();
            $this->attachCosting($page->items());

            return $page;
        }

        // INTERIM (until S-23): cost the whole filtered set, keep the products that can't be made, then paginate in memory.
        $products = $query->get();
        $costings = $this->costing->costMany($products);
        $matching = $products->filter(fn (Product $product): bool => $costings[$product->id]->cantProduce())->values();
        $current = Paginator::resolveCurrentPage();

        $paginator = new LengthAwarePaginator(
            $matching->forPage($current, $perPage)->values(),
            $matching->count(),
            $perPage,
            $current,
            ['path' => Paginator::resolveCurrentPath()],
        );

        foreach ($paginator->items() as $product) {
            $product->setAttribute('costing', $costings[$product->id]);
        }

        return $paginator->withQueryString();
    }

    /**
     * @param  Builder<Sale>  $sales
     * @return Builder<Sale>
     */
    private static function soldInLastThirtyDays(Builder $sales): Builder
    {
        [$first, $last] = SalesPeriod::LastThirtyDays->range(CarbonImmutable::today());

        return $sales->soldBetween($first, $last);
    }

    /**
     * @param  array<int, Product>  $products
     */
    private function attachCosting(array $products): void
    {
        $costings = $this->costing->costMany($products);

        foreach ($products as $product) {
            $product->setAttribute('costing', $costings[$product->id]);
        }
    }
}
