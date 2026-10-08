<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Gross margin of a product: selling price minus unit cost, in money and as a percentage of the selling price.
 *
 * Pure PHP (ADR-0004). The rules are specified in the invory-costing-domain skill ("Selling price and margin"):
 * the maths is exact and each result is rounded once, on output.
 */
final class MarginCalculator
{
    private const SCALE = 4;

    private const PERCENT_SCALE = 2;

    /**
     * @param  string|null  $sellingPrice  Price of one unit, >= 0. Null when the product has no selling price.
     * @param  string  $unitCost  Cost of one unit, >= 0.
     * @return Margin|null Null when there is no selling price; its percent is null when the selling price is zero.
     *
     * @throws InvalidArgumentException If the selling price or the unit cost is malformed or negative.
     */
    public function calculate(?string $sellingPrice, string $unitCost): ?Margin
    {
        $cost = Decimals::parseNonNegative($unitCost, 'unitCost');

        if ($sellingPrice === null) {
            return null;
        }

        $price = Decimals::parseNonNegative($sellingPrice, 'sellingPrice');
        $amount = $price->minus($cost);

        return new Margin(
            amount: $amount->toScale(self::SCALE, RoundingMode::HalfUp)->toString(),
            // One rounding, from the exact amount: amount × 100 ÷ price.
            percent: $price->isZero() ? null : $this->percent($amount, $price),
        );
    }

    private function percent(BigDecimal $amount, BigDecimal $price): string
    {
        return $amount->multipliedBy(100)->dividedBy($price, self::PERCENT_SCALE, RoundingMode::HalfUp)->toString();
    }
}
