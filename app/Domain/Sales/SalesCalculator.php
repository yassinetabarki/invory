<?php

declare(strict_types=1);

namespace App\Domain\Sales;

use App\Domain\Costing\Decimals;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Sale totals, period summary and top-products ranking.
 *
 * Pure PHP (ADR-0004). The rules are specified in the invory-costing-domain skill ("Sales"): the maths is exact
 * and each result is rounded once, on output. The caller picks the sales of the period; this class knows no dates.
 *
 * @phpstan-type ProductTotals array{productId: int, units: int, revenue: BigDecimal}
 */
final class SalesCalculator
{
    private const SCALE = 4;

    private const PERCENT_SCALE = 2;

    /** Every percent of a ranking whose top metric is zero (no bar to measure against). */
    private const ZERO_PERCENT = '0.00';

    /**
     * Total of one sale: quantity × unit price, scale 4.
     *
     * @throws InvalidArgumentException If the quantity is below 1, or the price is malformed or negative.
     */
    public function lineTotal(int $quantity, string $unitPrice): string
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException(sprintf('quantity must be at least 1, got %d.', $quantity));
        }

        $price = Decimals::parseNonNegative($unitPrice, 'unitPrice');

        return self::money($price->multipliedBy($quantity));
    }

    /**
     * @param  list<SaleLine>  $sales
     *
     * @throws InvalidArgumentException If a sale is not a SaleLine.
     */
    public function summarize(array $sales): SalesSummary
    {
        $this->assertSaleLines($sales);

        $revenue = BigDecimal::zero();
        $unitsSold = 0;
        $productIds = [];

        foreach ($sales as $sale) {
            $revenue = $revenue->plus(self::exactTotal($sale));
            $unitsSold += $sale->quantity;
            $productIds[$sale->productId] = true;
        }

        $salesCount = count($sales);

        return new SalesSummary(
            revenue: self::money($revenue),
            unitsSold: $unitsSold,
            salesCount: $salesCount,
            averageSale: $salesCount === 0 ? null : $revenue->dividedBy($salesCount, self::SCALE, RoundingMode::HalfUp)->toString(),
            productsSoldCount: count($productIds),
        );
    }

    /**
     * The best-selling products, ranked by units or by revenue.
     *
     * Ranked by the chosen metric descending; a tie goes to the other metric descending, then to the lowest product id.
     * `percentOfTop` is the metric over the first ranked product's metric, so it drives a bar that is full for the first.
     *
     * @param  list<SaleLine>  $sales
     * @param  string  $by  "units" or "revenue" (typed loosely on purpose: it is checked at runtime, callers pass user input).
     * @return list<ProductSales>
     *
     * @throws InvalidArgumentException If $by is unknown, $limit is below 1, or a sale is not a SaleLine.
     */
    public function topProducts(array $sales, string $by, int $limit = 5): array
    {
        if ($by !== 'units' && $by !== 'revenue') {
            throw new InvalidArgumentException(sprintf('by must be "units" or "revenue", got "%s".', $by));
        }

        if ($limit < 1) {
            throw new InvalidArgumentException(sprintf('limit must be at least 1, got %d.', $limit));
        }

        $this->assertSaleLines($sales);

        $ranked = array_values($this->totalsPerProduct($sales));
        usort($ranked, static fn (array $a, array $b): int => self::rank($a, $b, $by));
        $ranked = array_slice($ranked, 0, $limit);

        if ($ranked === []) {
            return [];
        }

        $topMetric = self::metric($ranked[0], $by);

        return array_map(
            fn (array $totals): ProductSales => new ProductSales(
                productId: $totals['productId'],
                unitsSold: $totals['units'],
                revenue: self::money($totals['revenue']),
                percentOfTop: $topMetric->isZero() ? self::ZERO_PERCENT : $this->percentOfTop(self::metric($totals, $by), $topMetric),
            ),
            $ranked,
        );
    }

    /**
     * @param  list<SaleLine>  $sales
     * @return array<int, ProductTotals>
     */
    private function totalsPerProduct(array $sales): array
    {
        $perProduct = [];

        foreach ($sales as $sale) {
            $totals = $perProduct[$sale->productId] ?? ['productId' => $sale->productId, 'units' => 0, 'revenue' => BigDecimal::zero()];

            $perProduct[$sale->productId] = [
                'productId' => $sale->productId,
                'units' => $totals['units'] + $sale->quantity,
                'revenue' => $totals['revenue']->plus(self::exactTotal($sale)),
            ];
        }

        return $perProduct;
    }

    /**
     * Ranking order: the chosen metric descending, then the other metric descending, then the lowest product id.
     *
     * @param  ProductTotals  $a
     * @param  ProductTotals  $b
     */
    private static function rank(array $a, array $b, string $by): int
    {
        $byUnits = $b['units'] <=> $a['units'];
        $byRevenue = $b['revenue']->compareTo($a['revenue']);

        $order = $by === 'units' ? [$byUnits, $byRevenue] : [$byRevenue, $byUnits];

        foreach ($order as $comparison) {
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return $a['productId'] <=> $b['productId'];
    }

    /** @param ProductTotals $totals */
    private static function metric(array $totals, string $by): BigDecimal
    {
        return $by === 'units' ? BigDecimal::of($totals['units']) : $totals['revenue'];
    }

    /** metric ÷ top metric × 100, one rounding. The top metric is not zero. */
    private function percentOfTop(BigDecimal $metric, BigDecimal $topMetric): string
    {
        return $metric->multipliedBy(100)->dividedBy($topMetric, self::PERCENT_SCALE, RoundingMode::HalfUp)->toString();
    }

    /**
     * Runtime guard for callers that break the `list<SaleLine>` contract (it is typed loosely on purpose).
     *
     * @param  array<mixed>  $sales
     *
     * @throws InvalidArgumentException
     */
    private function assertSaleLines(array $sales): void
    {
        foreach ($sales as $sale) {
            if (! $sale instanceof SaleLine) {
                throw new InvalidArgumentException(sprintf('Every sale must be a %s, got %s.', SaleLine::class, get_debug_type($sale)));
            }
        }
    }

    /** Quantity × unit price, exact. A SaleLine is valid by construction. */
    private static function exactTotal(SaleLine $sale): BigDecimal
    {
        return BigDecimal::of($sale->unitPrice)->multipliedBy($sale->quantity);
    }

    private static function money(BigDecimal $value): string
    {
        return $value->toScale(self::SCALE, RoundingMode::HalfUp)->toString();
    }
}
