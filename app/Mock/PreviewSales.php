<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Domain\Sales\ProductSales;
use App\Domain\Sales\SaleLine;
use App\Domain\Sales\SalesCalculator;
use App\Enums\SalesPeriod;
use App\Models\Product;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * The fixture sales, dated relative to today, and the builders of every sales-shaped prop.
 * Totals, averages, rankings and percentages all come from SalesCalculator.
 *
 * A fixture sale names its product by SKU. It is resolved against the real, non-deleted products (a sale whose product is missing
 * is skipped), so every sale carries the real product id and the product's reference as `ProductRef` sends it.
 *
 * @phpstan-type ProductReference array{id: int, sku: string, name: string, photo_url: string|null}
 * @phpstan-type DatedSale array{id: int, date: string, product_id: int, product: ProductReference, quantity: int, unit_price: string, note: string|null}
 */
final class PreviewSales
{
    /**
     * Every fixture sale in id order whose product exists (one query on `products`), with its date ("YYYY-MM-DD", app timezone).
     *
     * @return list<DatedSale>
     */
    public static function all(): array
    {
        $skus = array_values(array_unique(array_column(PreviewFixture::sales(), 'product_sku')));

        return self::resolve(Product::query()->whereIn('sku', $skus)->get(['id', 'sku', 'name']));
    }

    /**
     * The fixture sales of the given products (no query: a page that already holds them uses this), by SKU, in id order.
     * Only `id`, `sku` and `name` of each product are read.
     *
     * @param  iterable<Product>  $products
     * @return list<DatedSale>
     */
    public static function resolve(iterable $products): array
    {
        $bySku = [];

        foreach ($products as $product) {
            $bySku[$product->sku] = $product;
        }

        $today = CarbonImmutable::today();
        $sales = [];

        foreach (PreviewFixture::sales() as $sale) {
            $product = $bySku[$sale['product_sku']] ?? null;

            if ($product === null) {
                continue;
            }

            $sales[] = [
                'id' => $sale['id'],
                'date' => $today->subDays($sale['days_ago'])->toDateString(),
                'product_id' => $product->id,
                'product' => PreviewProducts::ref($product),
                'quantity' => $sale['quantity'],
                'unit_price' => PreviewDecimal::required($sale['unit_price']),
                'note' => $sale['note'],
            ];
        }

        return $sales;
    }

    /**
     * Units sold in the last 30 days by each of the given products (no query), by product id: every product has an entry.
     *
     * @param  iterable<Product>  $products
     * @return array<int, int>
     */
    public static function unitsSoldLastThirtyDays(iterable $products): array
    {
        $recent = self::inPeriod(self::resolve($products), SalesPeriod::LastThirtyDays);
        $units = [];

        foreach ($products as $product) {
            $units[$product->id] = (new SalesCalculator)->summarize(self::lines(self::ofProduct($recent, $product->id)))->unitsSold;
        }

        return $units;
    }

    /**
     * The sales of a period (inclusive calendar days ending today).
     *
     * @param  list<DatedSale>  $sales
     * @return list<DatedSale>
     */
    public static function inPeriod(array $sales, SalesPeriod $period): array
    {
        [$first, $last] = $period->range(CarbonImmutable::today());
        $first = $first->toDateString();
        $last = $last->toDateString();

        return array_values(array_filter(
            $sales,
            static fn (array $sale): bool => $sale['date'] >= $first && $sale['date'] <= $last,
        ));
    }

    /**
     * Sales of one product.
     *
     * @param  list<DatedSale>  $sales
     * @return list<DatedSale>
     */
    public static function ofProduct(array $sales, int $productId): array
    {
        return array_values(array_filter(
            $sales,
            static fn (array $sale): bool => $sale['product_id'] === $productId,
        ));
    }

    /**
     * Newest first: date descending, then id descending.
     *
     * @param  list<DatedSale>  $sales
     * @return list<DatedSale>
     */
    public static function newestFirst(array $sales): array
    {
        usort($sales, static fn (array $a, array $b): int => [$b['date'], $b['id']] <=> [$a['date'], $a['id']]);

        return $sales;
    }

    /**
     * The sales list filters: product name, SKU or note (case-insensitive), a product, and a period.
     *
     * @param  list<DatedSale>  $sales
     * @return list<DatedSale>
     */
    public static function filter(array $sales, ?string $search, ?int $productId, SalesPeriod $period): array
    {
        $sales = self::inPeriod($sales, $period);

        if ($productId !== null) {
            $sales = self::ofProduct($sales, $productId);
        }

        if ($search !== null && $search !== '') {
            $sales = array_values(array_filter($sales, static function (array $sale) use ($search): bool {
                foreach ([$sale['product']['name'], $sale['product']['sku'], $sale['note'] ?? ''] as $haystack) {
                    if (mb_stripos($haystack, $search) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return self::newestFirst($sales);
    }

    /**
     * `Sale` in resources/js/types/domain.ts.
     *
     * @param  DatedSale  $sale
     * @return array<string, mixed>
     */
    public static function shape(array $sale): array
    {
        return [
            'id' => $sale['id'],
            'product' => $sale['product'],
            'quantity' => $sale['quantity'],
            'unit_price' => $sale['unit_price'],
            'total' => (new SalesCalculator)->lineTotal($sale['quantity'], $sale['unit_price']),
            'sold_on' => $sale['date'],
            'note' => $sale['note'],
        ];
    }

    /**
     * @param  list<DatedSale>  $sales
     * @return list<array<string, mixed>>
     */
    public static function shapeAll(array $sales): array
    {
        return array_map(self::shape(...), $sales);
    }

    /**
     * `SalesTotals`.
     *
     * @param  list<DatedSale>  $sales
     * @return array{revenue: string, units_sold: int, sales_count: int, average_sale: string|null}
     */
    public static function totals(array $sales): array
    {
        $summary = (new SalesCalculator)->summarize(self::lines($sales));

        return [
            'revenue' => $summary->revenue,
            'units_sold' => $summary->unitsSold,
            'sales_count' => $summary->salesCount,
            'average_sale' => $summary->averageSale,
        ];
    }

    /**
     * The sales half of `DashboardStats`.
     *
     * @param  list<DatedSale>  $sales
     * @return array{revenue: string, units_sold: int, sales_count: int, products_sold_count: int}
     */
    public static function summary(array $sales): array
    {
        $summary = (new SalesCalculator)->summarize(self::lines($sales));

        return [
            'revenue' => $summary->revenue,
            'units_sold' => $summary->unitsSold,
            'sales_count' => $summary->salesCount,
            'products_sold_count' => $summary->productsSoldCount,
        ];
    }

    /**
     * The newest sales, shaped as `Sale[]`.
     *
     * @param  list<DatedSale>  $sales
     * @return list<array<string, mixed>>
     */
    public static function recent(array $sales, int $limit = 5): array
    {
        return self::shapeAll(array_slice(self::newestFirst($sales), 0, $limit));
    }

    /**
     * `TopProduct[]`: the five best sellers by units or by revenue.
     *
     * @param  list<DatedSale>  $sales
     * @param  'units'|'revenue'  $by
     * @return list<array<string, mixed>>
     */
    public static function topProducts(array $sales, string $by): array
    {
        $products = [];

        foreach ($sales as $sale) {
            $products[$sale['product_id']] = $sale['product'];
        }

        return array_map(
            static function (ProductSales $ranked) use ($products): array {
                $product = $products[$ranked->productId] ?? throw new LogicException("A ranked product has no sale (#{$ranked->productId}).");

                return [
                    'product' => $product,
                    'units_sold' => $ranked->unitsSold,
                    'revenue' => $ranked->revenue,
                    'percent_of_top' => $ranked->percentOfTop,
                ];
            },
            (new SalesCalculator)->topProducts(self::lines($sales), $by),
        );
    }

    /**
     * @param  list<DatedSale>  $sales
     * @return list<SaleLine>
     */
    public static function lines(array $sales): array
    {
        return array_map(
            static fn (array $sale): SaleLine => new SaleLine($sale['product_id'], $sale['quantity'], $sale['unit_price']),
            $sales,
        );
    }
}
