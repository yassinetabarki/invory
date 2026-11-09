<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

/**
 * One composition line of a product, with either one quantity for every variant (a `same` line) or a quantity per
 * colour and size (a `by_variant` line). ADR-0027.
 *
 * A variant without a cell does not use the material.
 *
 * The line's decimals are validated once, here, and its exact figures are worked out once per distinct quantity (an "option"): the
 * effective quantity `q × (100 + waste) / 100` and the line cost `effective × unit cost`, as CostingCalculator does. VariantCosting
 * sums those, so a cell is never parsed again.
 */
final readonly class VariantLine
{
    /** The material's stock, parsed once. */
    public BigDecimal $stock;

    /** @var list<BigDecimal> exact effective quantity, by option */
    public array $effectives;

    /** @var list<BigDecimal> exact line cost (effective × unit cost), by option */
    public array $costs;

    /** @var array<int, array<int, int>> option by colour id, then size id; empty on a `same` line */
    private array $optionOf;

    /**
     * @param  string|null  $quantity  A `same` line: the quantity per unit, > 0. Null on a by-colour-and-size line.
     * @param  array<int, array<int, string>>|null  $cells  A by-colour-and-size line: quantity by colour id, then size id, each > 0.
     *                                                      Null on a `same` line; never empty when set.
     *
     * @throws InvalidArgumentException If both or neither of quantity and cells are given, the cells are empty, or a quantity is invalid.
     */
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public ?string $quantity,
        public ?array $cells,
        public string $wastePercent,
        public string $unitCost,
        public string $stockOnHand,
    ) {
        if (($quantity === null) === ($cells === null)) {
            throw new InvalidArgumentException('A line has either a quantity or cells, never both and never neither.');
        }

        if ($cells === []) {
            throw new InvalidArgumentException('A by-colour-and-size line needs at least one cell.');
        }

        $first = $quantity ?? $this->firstCell($cells ?? []);

        // BomLine's own checks, once for the waste, the unit cost and the stock (and for the first quantity).
        $this->bomLine($first);

        $waste = BigDecimal::of($wastePercent)->plus(100);
        $unitCostDecimal = BigDecimal::of($unitCost);

        $effectives = [];
        $costs = [];
        /** @var array<string, int> $byRaw option by quantity as written */
        $byRaw = [];
        /** @var array<string, int> $byValue option by quantity value ("1.8" and "1.8000" are one) */
        $byValue = [];

        $option = function (string $raw) use ($waste, $unitCostDecimal, &$effectives, &$costs, &$byRaw, &$byValue): int {
            if (isset($byRaw[$raw])) {
                return $byRaw[$raw];
            }

            $decimal = Decimals::parse($raw, 'quantity');

            if (! $decimal->isPositive()) {
                throw new InvalidArgumentException(sprintf('quantity must be greater than 0, got "%s".', $raw));
            }

            $value = $decimal->strippedOfTrailingZeros()->toString();

            if (! isset($byValue[$value])) {
                $effective = $decimal->multipliedBy($waste)->withPointMovedLeft(2);
                $byValue[$value] = count($effectives);
                $effectives[] = $effective;
                $costs[] = $effective->multipliedBy($unitCostDecimal);
            }

            return $byRaw[$raw] = $byValue[$value];
        };

        $optionOf = [];

        if ($quantity !== null) {
            $option($quantity);
        } else {
            foreach ($cells ?? [] as $colourId => $sizes) {
                foreach ($sizes as $sizeId => $cell) {
                    $optionOf[$colourId][$sizeId] = $option($cell);
                }
            }
        }

        $this->stock = BigDecimal::of($stockOnHand);
        $this->effectives = $effectives;
        $this->costs = $costs;
        $this->optionOf = $optionOf;
    }

    /** True for a by-colour-and-size line. */
    public function varies(): bool
    {
        return $this->cells !== null;
    }

    /** The quantity this line gives the variant, or null when the variant does not use the material. */
    public function quantityFor(int $colourId, int $sizeId): ?string
    {
        return $this->cells === null ? $this->quantity : ($this->cells[$colourId][$sizeId] ?? null);
    }

    /** The option (index in {@see self::$effectives} and {@see self::$costs}) the variant uses, or null when it does not use the material. */
    public function optionFor(int $colourId, int $sizeId): ?int
    {
        return $this->cells === null ? 0 : ($this->optionOf[$colourId][$sizeId] ?? null);
    }

    /**
     * The costing line for one quantity of this line (BomLine's own checks apply).
     *
     * @throws InvalidArgumentException
     */
    public function bomLine(string $quantity): BomLine
    {
        return new BomLine(
            $this->materialId,
            $this->materialName,
            $this->unit,
            $quantity,
            $this->wastePercent,
            $this->unitCost,
            $this->stockOnHand,
        );
    }

    /** @param  array<int, array<int, string>>  $cells */
    private function firstCell(array $cells): string
    {
        foreach ($cells as $sizes) {
            foreach ($sizes as $cell) {
                return $cell;
            }
        }

        throw new InvalidArgumentException('A by-colour-and-size line needs at least one cell.');
    }
}
