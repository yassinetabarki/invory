<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use InvalidArgumentException;

/**
 * One material requirement per unit of product (a line of the bill of materials).
 *
 * Decimals are plain decimal strings ("1.8000"); the quantity is expressed in the material's own unit.
 */
final readonly class BomLine
{
    /**
     * @param  string  $quantity  Net quantity per unit of product, > 0.
     * @param  string  $wastePercent  Expected loss on top of the quantity, 0 <= x < 100.
     * @param  string  $unitCost  Cost of one unit of the material, >= 0.
     * @param  string  $stockOnHand  Quantity in stock, >= 0.
     *
     * @throws InvalidArgumentException If a decimal is malformed or out of range.
     */
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $quantity,
        public string $wastePercent,
        public string $unitCost,
        public string $stockOnHand,
    ) {
        if (! Decimals::parse($quantity, 'quantity')->isPositive()) {
            throw new InvalidArgumentException(sprintf('quantity must be greater than 0, got "%s".', $quantity));
        }

        $waste = Decimals::parseNonNegative($wastePercent, 'wastePercent');

        if ($waste->isGreaterThanOrEqualTo(100)) {
            throw new InvalidArgumentException(sprintf('wastePercent must be lower than 100, got "%s".', $wastePercent));
        }

        Decimals::parseNonNegative($unitCost, 'unitCost');
        Decimals::parseNonNegative($stockOnHand, 'stockOnHand');
    }
}
