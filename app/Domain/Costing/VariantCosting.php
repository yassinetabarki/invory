<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Cost and capacity of a product per colour and size (ADR-0027 §4 and §5).
 *
 * Pure PHP (ADR-0004). A product at the cap has up to 220 distinct compositions, so this class does not call
 * CostingCalculator::calculate() for each. It works out the formulas of the invory-costing-domain skill itself, from the exact figures
 * each VariantLine holds once per distinct quantity: per variant, the material cost is the sum of the exact line costs (rounded once),
 * the unit cost the exact sum plus labor and overhead (rounded once), a material's requirement the sum of the exact effective
 * quantities of its lines, its units possible the floor of stock over requirement, and the bottleneck the smallest of those with the
 * calculator's tie-break. `calculate()` of the calculator still runs, unchanged, once per product, for {@see VariantCostingResult::$single}.
 * VariantCostingTest checks every variant's figures against it.
 */
final class VariantCosting
{
    private const SCALE = 4;

    public function __construct(
        private readonly CostingCalculator $costing = new CostingCalculator,
        private readonly MarginCalculator $margins = new MarginCalculator,
    ) {}

    /**
     * @param  list<VariantLine>  $lines  In composition order.
     * @param  list<array{0: int, 1: int}>  $variants  [colourId, sizeId], colour list order then size order.
     * @param  string|null  $sellingPrice  Null when the product has none.
     *
     * @throws InvalidArgumentException If there is no variant, a line is not a VariantLine, or a figure is invalid.
     */
    public function calculate(array $lines, array $variants, string $laborCost, string $overheadCost, ?string $sellingPrice): VariantCostingResult
    {
        if ($variants === []) {
            throw new InvalidArgumentException('A product needs at least one variant.');
        }

        $this->assertVariantLines($lines);

        $fixed = Decimals::parseNonNegative($laborCost, 'laborCost')->plus(Decimals::parseNonNegative($overheadCost, 'overheadCost'));

        $varies = false;

        foreach ($lines as $line) {
            $varies = $varies || $line->varies();
        }

        /** @var array<string, array{materialCost: BigDecimal, unitCost: BigDecimal, max: int|null, materialId: int|null, materialName: string|null}> $figures one per distinct composition */
        $figures = [];
        /** @var array<int|string, Margin|null> $margins one per distinct unit cost (a PHP key: "35.7540" stays a string) */
        $margins = [];
        /** @var array<int, array{name: string, unit: string, min: BigDecimal, max: BigDecimal, unitsMin: int, unitsMax: int}> $spans */
        $spans = [];
        $variantResults = [];

        foreach ($variants as [$colourId, $sizeId]) {
            $picks = [];
            $key = '';

            foreach ($lines as $index => $line) {
                $option = $line->optionFor($colourId, $sizeId);

                if ($option !== null) {
                    $picks[$index] = $option;
                    $key .= $index.':'.$option.',';
                }
            }

            $figures[$key] ??= $this->figures($lines, $picks, $fixed, $spans);
            $figure = $figures[$key];

            $materialCost = $figure['materialCost']->toString();
            $unitCost = $figure['unitCost']->toString();

            if ($sellingPrice !== null && ! array_key_exists($unitCost, $margins)) {
                $margins[$unitCost] = $this->margins->calculate($sellingPrice, $unitCost);
            }

            $variantResults[] = new VariantResult(
                $colourId, $sizeId, $materialCost, $unitCost, $figure['max'], $figure['materialId'], $figure['materialName'], $margins[$unitCost] ?? null,
            );
        }

        return $this->summarise($lines, $varies, $variantResults, $figures, $margins, $spans, $laborCost, $overheadCost);
    }

    /**
     * The figures of one composition: material cost, unit cost, capacity and bottleneck. Also widens the spans of the materials it uses.
     *
     * @param  list<VariantLine>  $lines
     * @param  array<int, int>  $picks  Option by line index, for the lines the composition has.
     * @param  array<int, array{name: string, unit: string, min: BigDecimal, max: BigDecimal, unitsMin: int, unitsMax: int}>  $spans
     * @return array{materialCost: BigDecimal, unitCost: BigDecimal, max: int|null, materialId: int|null, materialName: string|null}
     */
    private function figures(array $lines, array $picks, BigDecimal $fixed, array &$spans): array
    {
        $exactMaterialCost = BigDecimal::zero();
        /** @var array<int, BigDecimal> $required exact requirement by material id */
        $required = [];
        /** @var array<int, VariantLine> $firstLine the material's name, unit and stock come from its first line */
        $firstLine = [];

        foreach ($picks as $index => $option) {
            $line = $lines[$index];
            $exactMaterialCost = $exactMaterialCost->plus($line->costs[$option]);

            if (isset($required[$line->materialId])) {
                $required[$line->materialId] = $required[$line->materialId]->plus($line->effectives[$option]);
            } else {
                $required[$line->materialId] = $line->effectives[$option];
                $firstLine[$line->materialId] = $line;
            }
        }

        $bottleneckId = null;
        $bottleneckUnits = 0;
        $bottleneckName = '';

        foreach ($required as $materialId => $need) {
            $material = $firstLine[$materialId];
            $units = $material->stock->dividedBy($need, 0, RoundingMode::Down)->toInt();

            if ($bottleneckId === null || self::isBetterBottleneck($units, $material->materialName, $materialId, $bottleneckUnits, $bottleneckName, $bottleneckId)) {
                $bottleneckId = $materialId;
                $bottleneckUnits = $units;
                $bottleneckName = $material->materialName;
            }

            $span = $spans[$materialId] ?? null;

            $spans[$materialId] = [
                'name' => $span['name'] ?? $material->materialName,
                'unit' => $span['unit'] ?? $material->unit,
                'min' => $span === null || $need->isLessThan($span['min']) ? $need : $span['min'],
                'max' => $span === null || $need->isGreaterThan($span['max']) ? $need : $span['max'],
                'unitsMin' => $span === null ? $units : min($span['unitsMin'], $units),
                'unitsMax' => $span === null ? $units : max($span['unitsMax'], $units),
            ];
        }

        return [
            'materialCost' => $exactMaterialCost->toScale(self::SCALE, RoundingMode::HalfUp),
            'unitCost' => $exactMaterialCost->plus($fixed)->toScale(self::SCALE, RoundingMode::HalfUp),
            'max' => $bottleneckId === null ? null : $bottleneckUnits,
            'materialId' => $bottleneckId,
            'materialName' => $bottleneckId === null ? null : $bottleneckName,
        ];
    }

    /**
     * @param  list<VariantLine>  $lines
     * @param  list<VariantResult>  $variantResults
     * @param  array<string, array{materialCost: BigDecimal, unitCost: BigDecimal, max: int|null, materialId: int|null, materialName: string|null}>  $figures
     * @param  array<int|string, Margin|null>  $margins
     * @param  array<int, array{name: string, unit: string, min: BigDecimal, max: BigDecimal, unitsMin: int, unitsMax: int}>  $spans
     */
    private function summarise(array $lines, bool $varies, array $variantResults, array $figures, array $margins, array $spans, string $laborCost, string $overheadCost): VariantCostingResult
    {
        // Capacity: the first variant in the given order wins a tie, for the lowest and for the highest.
        $binding = null;
        $best = null;
        $colourIds = [];
        /** @var array<int, VariantResult> $lowest the colour's variant giving its guaranteed */
        $lowest = [];
        /** @var array<int, VariantResult> $highest the colour's variant giving its best */
        $highest = [];

        foreach ($variantResults as $variant) {
            $capacity = $variant->maxProducible;
            $colourId = $variant->colourId;
            $colourIds[$colourId] = $colourId;

            if ($capacity === null) {
                continue;
            }

            if ($binding === null || $capacity < $binding->maxProducible) {
                $binding = $variant;
            }

            if ($best === null || $capacity > $best->maxProducible) {
                $best = $variant;
            }

            if (! isset($lowest[$colourId]) || $capacity < $lowest[$colourId]->maxProducible) {
                $lowest[$colourId] = $variant;
            }

            if (! isset($highest[$colourId]) || $capacity > $highest[$colourId]->maxProducible) {
                $highest[$colourId] = $variant;
            }
        }

        $colours = [];

        foreach ($colourIds as $colourId) {
            $low = $lowest[$colourId] ?? null;

            $colours[] = new ColourCapacity(
                colourId: $colourId,
                guaranteed: $low?->maxProducible,
                best: isset($highest[$colourId]) ? $highest[$colourId]->maxProducible : null,
                bindingSizeId: $low?->sizeId,
                bottleneckMaterialId: $low?->bottleneckMaterialId,
                bottleneckMaterialName: $low?->bottleneckMaterialName,
            );
        }

        // Cost and margin ranges over every variant (an empty composition costs labor + overhead); equal figures change no lowest or highest,
        // so the distinct compositions and the distinct unit costs are enough.
        $materialCostMin = $materialCostMax = $unitCostMin = $unitCostMax = null;

        foreach ($figures as $figure) {
            $materialCostMin = $materialCostMin === null || $figure['materialCost']->isLessThan($materialCostMin) ? $figure['materialCost'] : $materialCostMin;
            $materialCostMax = $materialCostMax === null || $figure['materialCost']->isGreaterThan($materialCostMax) ? $figure['materialCost'] : $materialCostMax;
            $unitCostMin = $unitCostMin === null || $figure['unitCost']->isLessThan($unitCostMin) ? $figure['unitCost'] : $unitCostMin;
            $unitCostMax = $unitCostMax === null || $figure['unitCost']->isGreaterThan($unitCostMax) ? $figure['unitCost'] : $unitCostMax;
        }

        $amountMin = $amountMax = $percentMin = $percentMax = null;

        foreach ($margins as $margin) {
            if ($margin === null) {
                continue;
            }

            $amount = BigDecimal::of($margin->amount);
            $amountMin = $amountMin === null || $amount->isLessThan($amountMin) ? $amount : $amountMin;
            $amountMax = $amountMax === null || $amount->isGreaterThan($amountMax) ? $amount : $amountMax;

            if ($margin->percent !== null) {
                $percent = BigDecimal::of($margin->percent);
                $percentMin = $percentMin === null || $percent->isLessThan($percentMin) ? $percent : $percentMin;
                $percentMax = $percentMax === null || $percent->isGreaterThan($percentMax) ? $percent : $percentMax;
            }
        }

        // `single`: the one costing of the whole BOM when nothing varies, else the binding variant's (the first when every composition is empty).
        $first = $variantResults[0];
        $singleVariant = $varies ? ($binding ?? $first) : $first;
        $bom = [];

        foreach ($lines as $line) {
            $quantity = $line->quantityFor($singleVariant->colourId, $singleVariant->sizeId);

            if ($quantity !== null) {
                $bom[] = $line->bomLine($quantity);
            }
        }

        $single = $this->costing->calculate($bom, $laborCost, $overheadCost);

        return new VariantCostingResult(
            varies: $varies,
            variants: $variantResults,
            colours: $colours,
            guaranteed: $binding?->maxProducible,
            best: $best?->maxProducible,
            bindingColourId: $binding?->colourId,
            bindingSizeId: $binding?->sizeId,
            bindingMaterialId: $binding?->bottleneckMaterialId,
            bindingMaterialName: $binding?->bottleneckMaterialName,
            bestColourId: $best?->colourId,
            bestSizeId: $best?->sizeId,
            bestMaterialId: $best?->bottleneckMaterialId,
            bestMaterialName: $best?->bottleneckMaterialName,
            materialCostMin: ($materialCostMin ?? BigDecimal::zero())->toString(),
            materialCostMax: ($materialCostMax ?? BigDecimal::zero())->toString(),
            unitCostMin: ($unitCostMin ?? BigDecimal::zero())->toString(),
            unitCostMax: ($unitCostMax ?? BigDecimal::zero())->toString(),
            marginAmountMin: $amountMin?->toString(),
            marginAmountMax: $amountMax?->toString(),
            marginPercentMin: $percentMin?->toString(),
            marginPercentMax: $percentMax?->toString(),
            materials: $this->materials($lines, $spans),
            single: $single,
        );
    }

    /**
     * Need and units covered per material, lowest and highest, over the variants whose composition uses it, in composition order.
     *
     * @param  list<VariantLine>  $lines
     * @param  array<int, array{name: string, unit: string, min: BigDecimal, max: BigDecimal, unitsMin: int, unitsMax: int}>  $spans
     * @return array<int, MaterialSpan>
     */
    private function materials(array $lines, array $spans): array
    {
        $materials = [];

        foreach ($lines as $line) {
            if (isset($spans[$line->materialId]) && ! isset($materials[$line->materialId])) {
                $span = $spans[$line->materialId];
                $materials[$line->materialId] = new MaterialSpan(
                    materialId: $line->materialId,
                    materialName: $span['name'],
                    unit: $span['unit'],
                    neededMin: $span['min']->toScale(self::SCALE, RoundingMode::HalfUp)->toString(),
                    neededMax: $span['max']->toScale(self::SCALE, RoundingMode::HalfUp)->toString(),
                    unitsCoveredMin: $span['unitsMin'],
                    unitsCoveredMax: $span['unitsMax'],
                );
            }
        }

        return $materials;
    }

    /**
     * Runtime guard for callers that break the `list<VariantLine>` contract (typed loosely on purpose, as CostingCalculator does).
     *
     * @param  array<mixed>  $lines
     *
     * @throws InvalidArgumentException
     */
    private function assertVariantLines(array $lines): void
    {
        foreach ($lines as $line) {
            if (! $line instanceof VariantLine) {
                throw new InvalidArgumentException(sprintf('Every line must be a %s, got %s.', VariantLine::class, get_debug_type($line)));
            }
        }
    }

    /**
     * Bottleneck order: fewest units possible, then name in byte order (strcmp), then lowest id.
     *
     * A mirror of CostingCalculator::isBetterBottleneck(), which is private; the equivalence test pins the two together.
     */
    private static function isBetterBottleneck(int $units, string $name, int $id, int $bestUnits, string $bestName, int $bestId): bool
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
}
