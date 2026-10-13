<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Domain\Sales\ProductSales;
use App\Domain\Sales\SaleLine;
use App\Domain\Sales\SalesCalculator;
use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Models\Product;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Eloquent side of the sales figures: the filtered query of the sales log, its totals, the sales of one product, the figures of the
 * dashboard (period lines, summary, top products, newest sales) and the
 * product options of "Record a sale".
 *
 * Every amount goes through {@see SalesCalculator} over the decimal strings of the rows. SQL only ever sums `quantity` (an
 * integer, exact on PostgreSQL and SQLite): SQLite sums decimals as floats, so `unit_price` is never summed or multiplied there.
 */
final readonly class SalesReport
{
    /** Rows read per query when the totals walk a filtered set. */
    private const CHUNK = 1000;

    /**
     * The sales of a period (inclusive days ending today), optionally of one product and/or matching a search on the product name,
     * its SKU or the note (case-insensitive). Soft-deleted products count (D-3). Unordered: the caller orders or chunks it.
     *
     * @param  array{search: string|null, product_id: int|null, period?: string}  $filters
     * @return Builder<Sale>
     */
    public function filtered(array $filters, SalesPeriod $period): Builder
    {
        [$first, $last] = $period->range(CarbonImmutable::today());

        return Sale::query()
            ->soldBetween($first, $last)
            ->when($filters['product_id'], fn (Builder $sales, int $productId) => $sales->where('product_id', $productId))
            ->when($filters['search'], fn (Builder $sales, string $term) => $sales->where(
                fn (Builder $match) => $match
                    ->whereLike('note', "%{$term}%")
                    ->orWhereIn('product_id', Product::query()->withTrashed()->search($term)->select('id')),
            ));
    }

    /**
     * `SalesTotals` of a set of sales: revenue, units, count and average, over the whole set (not a page).
     *
     * @param  Builder<Sale>  $sales
     * @return array{revenue: string, units_sold: int, sales_count: int, average_sale: string|null}
     */
    public function totals(Builder $sales): array
    {
        $summary = (new SalesCalculator)->summarize($this->lines($sales));

        return [
            'revenue' => $summary->revenue,
            'units_sold' => $summary->unitsSold,
            'sales_count' => $summary->salesCount,
            'average_sale' => $summary->averageSale,
        ];
    }

    /**
     * Every sale of the period (D-3: soft-deleted products included) as the maths' input. The dashboard reads it once and feeds
     * {@see self::periodSummary()} and {@see self::topProducts()} with it.
     *
     * @return list<SaleLine>
     */
    public function periodLines(SalesPeriod $period): array
    {
        return $this->lines($this->filtered(['search' => null, 'product_id' => null], $period));
    }

    /**
     * The sales half of `DashboardStats`.
     *
     * @param  list<SaleLine>  $lines
     * @return array{revenue: string, units_sold: int, sales_count: int, products_sold_count: int}
     */
    public function periodSummary(array $lines): array
    {
        $summary = (new SalesCalculator)->summarize($lines);

        return [
            'revenue' => $summary->revenue,
            'units_sold' => $summary->unitsSold,
            'sales_count' => $summary->salesCount,
            'products_sold_count' => $summary->productsSoldCount,
        ];
    }

    /**
     * `top_products`: the best sellers (at most `$limit`) by units and by revenue, ranked and measured by SalesCalculator.
     * Only product ids: the caller reads the product refs (soft-deleted ones count, D-3).
     *
     * @param  list<SaleLine>  $lines
     * @return array{by_units: list<ProductSales>, by_revenue: list<ProductSales>}
     */
    public function topProducts(array $lines, int $limit = 5): array
    {
        $calculator = new SalesCalculator;

        return [
            'by_units' => $calculator->topProducts($lines, 'units', $limit),
            'by_revenue' => $calculator->topProducts($lines, 'revenue', $limit),
        ];
    }

    /**
     * The newest sales whatever the period (the product is not loaded: the caller attaches it, soft-deleted ones included, D-3).
     *
     * @return list<Sale>
     */
    public function recent(int $limit = 5): array
    {
        return array_values(Sale::query()->newestFirst()->limit($limit)->get()->all());
    }

    /**
     * `sales_last_30_days` of the product page.
     *
     * @return array{revenue: string, units_sold: int}
     */
    public function lastThirtyDays(Product $product): array
    {
        $totals = $this->totals($this->filtered(['search' => null, 'product_id' => $product->id], SalesPeriod::LastThirtyDays));

        return ['revenue' => $totals['revenue'], 'units_sold' => $totals['units_sold']];
    }

    /**
     * The newest sales of the product, carrying the product itself (the caller has it already: no query for it).
     *
     * @return list<Sale>
     */
    public function recentOf(Product $product, int $limit = 5): array
    {
        $sales = Sale::query()->where('product_id', $product->id)->newestFirst()->limit($limit)->get();

        foreach ($sales as $sale) {
            $sale->setRelation('product', $product);
        }

        return array_values($sales->all());
    }

    /**
     * The products "Record a sale" and the product filter list: every non-deleted product by name, whatever its status (the form
     * says which can be sold). Only the columns of a `SaleProductOption`.
     *
     * @return Collection<int, Product>
     */
    public function productOptions(): Collection
    {
        return Product::query()->byName()->get(['id', 'sku', 'name', 'status', 'selling_price', 'photo_path']);
    }

    /** The id when it is an active, non-deleted product (the only ones that can be sold), else null. */
    public function sellableProductId(?int $id): ?int
    {
        if ($id === null) {
            return null;
        }

        $found = Product::query()->whereKey($id)->where('status', ProductStatus::Active->value)->value('id');

        return is_int($found) ? $found : null;
    }

    /**
     * A narrow select read in chunks by id: the rows are only what the maths needs, never full models.
     *
     * @param  Builder<Sale>  $sales
     * @return list<SaleLine>
     */
    private function lines(Builder $sales): array
    {
        $lines = [];

        foreach ($sales->select(['id', 'product_id', 'quantity', 'unit_price'])->lazyById(self::CHUNK) as $sale) {
            $lines[] = new SaleLine($sale->product_id, $sale->quantity, $sale->unit_price);
        }

        return $lines;
    }
}
