<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Unit cost, production capacity and bottleneck of a product's bill of materials.
 *
 * Pure PHP (ADR-0004). The rules are specified in the invory-costing-domain skill and ADR-0002:
 * intermediate maths is exact, and results are rounded once, on output.
 */
final class CostingCalculator
{
    private const SCALE = 4;

    private const PERCENT_SCALE = 2;

    /**
     * @param  list<BomLine>  $lines
     * @param  string  $laborCost  Labor cost per unit, >= 0.
     * @param  string  $overheadCost  Overhead cost per unit, >= 0.
     *
     * @throws InvalidArgumentException If labor or overhead is invalid, or a line is not a BomLine.
     */
    public function calculate(array $lines, string $laborCost = '0', string $overheadCost = '0'): CostingResult
    {
        $labor = Decimals::parseNonNegative($laborCost, 'laborCost');
        $overhead = Decimals::parseNonNegative($overheadCost, 'overheadCost');
        $this->assertBomLines($lines);

        // Pass 1: exact figures per line, and the exact requirement per material.
        $exactMaterialCost = BigDecimal::zero();
        $exactLines = [];
        /** @var array<int, array{name: string, stock: BigDecimal, required: BigDecimal}> $materials */
        $materials = [];

        foreach ($lines as $line) {
            // quantity × (1 + waste / 100), exact: no division involved.
            $effective = BigDecimal::of($line->quantity)
                ->multipliedBy(BigDecimal::of($line->wastePercent)->plus(100))
                ->withPointMovedLeft(2);
            $cost = $effective->multipliedBy($line->unitCost);

            $exactMaterialCost = $exactMaterialCost->plus($cost);
            $exactLines[] = ['line' => $line, 'effective' => $effective, 'cost' => $cost];

            // The material's name and stock come from its first line.
            $materials[$line->materialId] ??= [
                'name' => $line->materialName,
                'stock' => BigDecimal::of($line->stockOnHand),
                'required' => BigDecimal::zero(),
            ];
            $materials[$line->materialId]['required'] = $materials[$line->materialId]['required']->plus($effective);
        }

        // Pass 2: units possible per material (floored), and the bottleneck.
        $unitsPossible = [];
        $bottleneckId = null;

        foreach ($materials as $materialId => $material) {
            $unitsPossible[$materialId] = $material['stock']->dividedBy($material['required'], 0, RoundingMode::Down)->toInt();

            if ($bottleneckId === null || $this->isBetterBottleneck(
                $unitsPossible[$materialId], $material['name'], $materialId,
                $unitsPossible[$bottleneckId], $materials[$bottleneckId]['name'], $bottleneckId,
            )) {
                $bottleneckId = $materialId;
            }
        }

        // Pass 3: serialize. Rounding happens only here.
        $lineCosts = [];

        foreach ($exactLines as ['line' => $line, 'effective' => $effective, 'cost' => $cost]) {
            $lineCosts[] = new LineCost(
                materialId: $line->materialId,
                materialName: $line->materialName,
                unit: $line->unit,
                quantity: self::money(BigDecimal::of($line->quantity)),
                wastePercent: BigDecimal::of($line->wastePercent)->toScale(self::PERCENT_SCALE, RoundingMode::HalfUp)->toString(),
                effectiveQuantity: self::money($effective),
                unitCost: self::money(BigDecimal::of($line->unitCost)),
                lineCost: self::money($cost),
                stockOnHand: self::money(BigDecimal::of($line->stockOnHand)),
                unitsPossible: $unitsPossible[$line->materialId],
                isBottleneck: $line->materialId === $bottleneckId,
            );
        }

        return new CostingResult(
            cost: new CostBreakdown(
                lines: $lineCosts,
                materialCost: self::money($exactMaterialCost),
                laborCost: self::money($labor),
                overheadCost: self::money($overhead),
                unitCost: self::money($exactMaterialCost->plus($labor)->plus($overhead)),
            ),
            capacity: new ProductionCapacity(
                maxProducible: $bottleneckId === null ? null : $unitsPossible[$bottleneckId],
                bottleneckMaterialId: $bottleneckId,
                bottleneckMaterialName: $bottleneckId === null ? null : $materials[$bottleneckId]['name'],
            ),
        );
    }

    /**
     * The "next limit": the capacity and bottleneck of the BOM once every line of $materialId is removed,
     * i.e. what limits production if that material were unlimited. Same rules and tie-break as calculate().
     *
     * Lines are aggregated per material before dividing, so every line of the removed material goes at once.
     * A material that is not in the BOM changes nothing. `maxProducible` is null when nothing is left.
     *
     * @param  list<BomLine>  $lines
     *
     * @throws InvalidArgumentException If a line is not a BomLine.
     */
    public function capacityWithout(array $lines, int $materialId): ProductionCapacity
    {
        $this->assertBomLines($lines);

        $remaining = array_values(array_filter(
            $lines,
            static fn (BomLine $line): bool => $line->materialId !== $materialId,
        ));

        return $this->calculate($remaining)->capacity;
    }

    /**
     * Runtime guard for callers that break the `list<BomLine>` contract (it is typed loosely on purpose).
     *
     * @param  array<mixed>  $lines
     *
     * @throws InvalidArgumentException
     */
    private function assertBomLines(array $lines): void
    {
        foreach ($lines as $line) {
            if (! $line instanceof BomLine) {
                throw new InvalidArgumentException(sprintf('Every line must be a %s, got %s.', BomLine::class, get_debug_type($line)));
            }
        }
    }

    /**
     * Bottleneck order: fewest units possible, then name in byte order (strcmp), then lowest id.
     */
    private function isBetterBottleneck(int $units, string $name, int $id, int $bestUnits, string $bestName, int $bestId): bool
    {
        $order = $units <=> $bestUnits;

        if ($order === 0) {
            $order = strcmp($name, $bestName);
        }

        if ($order === 0) {
            $order = $id <=> $bestId;
        }

        return $order < 0;
    }

    private static function money(BigDecimal $value): string
    {
        return $value->toScale(self::SCALE, RoundingMode::HalfUp)->toString();
    }
}
