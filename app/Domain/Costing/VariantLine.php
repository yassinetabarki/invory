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
 *
 * Colour stock (INV-21, ADR-0029): a line on a material kept per colour also carries that material's stock by colour id (and, with
 * cost per colour, its cost by colour id). A variant of colour c then reads the stock and the cost of colour c; a colour the material
 * lacks is a stock of 0, flagged missing, and with cost per colour it has no cost. A one-stock line (`$colourStock` null) reads
 * `$stockOnHand` and `$unitCost` exactly as before.
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

    /** @var array<int, BigDecimal> the colour's stock, by colour id; empty for a one-stock material */
    private array $stockOf;

    /** @var array<int, list<BigDecimal>> exact line cost by colour id, then option, with cost per colour; empty otherwise */
    private array $colourCosts;

    /**
     * @param  string|null  $quantity  A `same` line: the quantity per unit, > 0. Null on a by-colour-and-size line.
     * @param  array<int, array<int, string>>|null  $cells  A by-colour-and-size line: quantity by colour id, then size id, each > 0.
     *                                                      Null on a `same` line; never empty when set.
     * @param  array<int, string>|null  $colourStock  The material's stock by colour id (>= 0); null for a one-stock material, which reads `$stockOnHand`.
     * @param  array<int, string>  $colourUnitCosts  The material's unit cost by colour id (>= 0); the same colours as `$colourStock` with cost per colour, else empty.
     * @param  bool  $costPerColour  Whether the material has cost per colour; needs `$colourStock`.
     *
     * @throws InvalidArgumentException If both or neither of quantity and cells are given, the cells are empty, a quantity is invalid, or the colour stock and costs do not match.
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
        public ?array $colourStock = null,
        public array $colourUnitCosts = [],
        public bool $costPerColour = false,
    ) {
        if (($quantity === null) === ($cells === null)) {
            throw new InvalidArgumentException('A line has either a quantity or cells, never both and never neither.');
        }

        if ($cells === []) {
            throw new InvalidArgumentException('A by-colour-and-size line needs at least one cell.');
        }

        $stockOf = [];

        foreach ($colourStock ?? [] as $colourId => $colourQuantity) {
            $stockOf[$colourId] = Decimals::parseNonNegative($colourQuantity, 'colourStock');
        }

        $colourCostOf = [];

        if ($costPerColour) {
            if ($colourStock === null || array_diff_key($colourUnitCosts, $colourStock) !== [] || array_diff_key($colourStock, $colourUnitCosts) !== []) {
                throw new InvalidArgumentException('Cost per colour needs a unit cost for each colour of the stock, and only those.');
            }

            foreach ($colourUnitCosts as $colourId => $colourCost) {
                $colourCostOf[$colourId] = Decimals::parseNonNegative($colourCost, 'colourUnitCosts');
            }
        } elseif ($colourUnitCosts !== []) {
            throw new InvalidArgumentException('Colour unit costs need cost per colour.');
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

        $colourCosts = [];

        $option = function (string $raw) use ($waste, $unitCostDecimal, $colourCostOf, &$effectives, &$costs, &$colourCosts, &$byRaw, &$byValue): int {
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

                foreach ($colourCostOf as $colourId => $colourCost) {
                    $colourCosts[$colourId][] = $effective->multipliedBy($colourCost);
                }
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
        $this->stockOf = $stockOf;
        $this->colourCosts = $colourCosts;
    }

    /** True for a material kept per colour: a variant reads the stock (and maybe the cost) of its own colour. */
    public function perColour(): bool
    {
        return $this->colourStock !== null;
    }

    /** The stock a variant of this colour draws on: the colour's (0 when the material lacks it), or the one stock. */
    public function stockFor(int $colourId): BigDecimal
    {
        return $this->colourStock === null ? $this->stock : ($this->stockOf[$colourId] ?? BigDecimal::zero());
    }

    /** Whether the material is kept per colour and has no stock of this colour. */
    public function missingFor(int $colourId): bool
    {
        return $this->colourStock !== null && ! isset($this->stockOf[$colourId]);
    }

    /** Whether a variant of this colour has no unit cost for the material: cost per colour, and the material lacks the colour. */
    public function noCostFor(int $colourId): bool
    {
        return $this->costPerColour && ! isset($this->colourCosts[$colourId]);
    }

    /** The exact line cost of an option for a variant of this colour, or null when it has no unit cost ({@see self::noCostFor()}). */
    public function costFor(int $colourId, int $option): ?BigDecimal
    {
        return $this->costPerColour ? ($this->colourCosts[$colourId][$option] ?? null) : $this->costs[$option];
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
     * The costing line for one quantity of this line (BomLine's own checks apply). With a colour of a material kept per colour, it holds
     * that colour's stock (0 when missing) and cost (the material's own unit cost when the colour has none, so the line stays costable).
     *
     * @throws InvalidArgumentException
     */
    public function bomLine(string $quantity, ?int $colourId = null): BomLine
    {
        $stock = $this->stockOnHand;
        $unitCost = $this->unitCost;
        $stockColourId = null;
        $missing = false;

        if ($colourId !== null && $this->colourStock !== null) {
            $stock = $this->colourStock[$colourId] ?? '0';
            $unitCost = $this->colourUnitCosts[$colourId] ?? $this->unitCost;
            $stockColourId = $colourId;
            $missing = $this->missingFor($colourId);
        }

        return new BomLine(
            $this->materialId,
            $this->materialName,
            $this->unit,
            $quantity,
            $this->wastePercent,
            $unitCost,
            $stock,
            $stockColourId,
            $missing,
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
