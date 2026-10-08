<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use App\Domain\Sales\ProductSales;
use App\Domain\Sales\SaleLine;
use App\Domain\Sales\SalesCalculator;
use App\Enums\SalesPeriod;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * The fixture sales, dated relative to today, and the builders of every sales-shaped prop.
 * Totals, averages, rankings and percentages all come from SalesCalculator.
 *
 * @phpstan-type DatedSale array{id: int, date: string, product_id: int, quantity: int, unit_price: string, note: string|null}
 */
final class PreviewSales
{
    /**
     * Every fixture sale in id order, with its date ("YYYY-MM-DD", app timezone) and unit price (its product's selling price).
     *
     * @return list<DatedSale>
     */
    public static function all(): array
    {
        $today = CarbonImmutable::today();
        $sales = [];

        foreach (PreviewFixture::sales() as $sale) {
            $price = PreviewFixture::product($sale['product_id'])['selling_price'] ?? null;

            if ($price === null) {
                throw new LogicException("Fixture sale {$sale['id']} is of a product without a selling price.");
            }

            $sales[] = [
                'id' => $sale['id'],
                'date' => $today->subDays($sale['days_ago'])->toDateString(),
                'product_id' => $sale['product_id'],
                'quantity' => $sale['quantity'],
                'unit_price' => PreviewDecimal::required($price),
                'note' => $sale['note'],
            ];
        }

        return $sales;
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
                $product = PreviewFixture::product($sale['product_id']);

                foreach ([$product['name'] ?? '', $product['sku'] ?? '', $sale['note'] ?? ''] as $haystack) {
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
        $product = PreviewFixture::product($sale['product_id']) ?? throw new LogicException("Unknown fixture product {$sale['product_id']}.");

        return [
            'id' => $sale['id'],
            'product' => PreviewProducts::ref($product),
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
        return array_map(
            static function (ProductSales $ranked): array {
                $product = PreviewFixture::product($ranked->productId) ?? throw new LogicException("Unknown fixture product {$ranked->productId}.");

                return [
                    'product' => PreviewProducts::ref($product),
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
