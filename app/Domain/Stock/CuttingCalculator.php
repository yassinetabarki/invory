<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\Decimals;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * The maths of taking materials out of stock for a cut and of giving them back (ADR-0014, costing skill
 * "Cutting and returning stock"). Pure PHP: exact BigDecimal maths, rounded to scale 4 (HalfUp) once per material per cut.
 */
final class CuttingCalculator
{
    private const SCALE = 4;

    public function __construct(private CostingCalculator $costing = new CostingCalculator) {}

    /**
     * @param  list<BomLine>  $lines
     *
     * @throws InvalidArgumentException If $units is below 1 or a line is invalid.
     */
    public function take(array $lines, int $units): CutPlan
    {
        $this->assertUnits($units);

        $result = $this->costing->calculate($lines);
        $takes = [];

        foreach ($result->requirements as $requirement) {
            $exactNeed = BigDecimal::of($requirement->perUnitExact)->multipliedBy($units);
            $needed = self::round($exactNeed);
            $stock = BigDecimal::of($requirement->stockOnHand);
            $short = $exactNeed->isGreaterThan($stock);

            $takes[] = new MaterialTake(
                materialId: $requirement->materialId,
                materialName: $requirement->materialName,
                unit: $requirement->unit,
                perUnitExact: $requirement->perUnitExact,
                needed: (string) $needed,
                inStock: (string) self::round($stock),
                after: $short ? null : (string) $stock->minus($needed)->toScale(self::SCALE),
                shortBy: $short ? (string) self::round($exactNeed->minus($stock)) : null,
            );
        }

        $capacity = $result->capacity;

        return new CutPlan(
            units: $units,
            takes: $takes,
            maxCuttable: $capacity->maxProducible,
            bottleneckMaterialId: $capacity->bottleneckMaterialId,
            bottleneckMaterialName: $capacity->bottleneckMaterialName,
            allowed: $capacity->maxProducible === null || $units <= $capacity->maxProducible,
        );
    }

    /**
     * The plan of a Start batch over a grid of colours and sizes (ADR-0027 decision 6): per material
     * `total_exact = Σ n_v × required_exact(m, v)`, `needed = round4(total_exact)` once, short when `total_exact` (never the rounded
     * figure) is above the stock. With one variant, or a product that doesn't vary, it is `take()` for the total.
     *
     * @param  list<VariantUnits>  $variants  the grid's colours and sizes with at least one unit, each with its composition
     * @param  list<int>  $materialOrder  material ids in the product's composition order: the takes are listed in it
     * @param  bool  $varies  the product has a line by colour and size
     *
     * @throws InvalidArgumentException If there is no variant.
     */
    public function takeVariants(array $variants, array $materialOrder, bool $varies): CutPlan
    {
        if ($variants === []) {
            throw new InvalidArgumentException('A plan needs at least one variant.');
        }

        $units = 0;
        $capacity = null;
        /** @var array<int, array{name: string, unit: string, stock: BigDecimal, total: BigDecimal}> $materials */
        $materials = [];
        $groups = [];

        foreach ($variants as $variant) {
            $result = $this->costing->calculate($variant->lines);
            $capacity ??= $result->capacity;
            $units += $variant->units;
            $perUnit = [];

            foreach ($result->requirements as $requirement) {
                $materials[$requirement->materialId] ??= [
                    'name' => $requirement->materialName,
                    'unit' => $requirement->unit,
                    'stock' => BigDecimal::of($requirement->stockOnHand),
                    'total' => BigDecimal::zero(),
                ];
                $materials[$requirement->materialId]['total'] = $materials[$requirement->materialId]['total']
                    ->plus(BigDecimal::of($requirement->perUnitExact)->multipliedBy($variant->units));
                $perUnit[$requirement->materialId] = $requirement->perUnitExact;
            }

            $groups[] = new CutGroup($variant->colourId, $variant->sizeId, $variant->units, 0, $perUnit);
        }

        $takes = [];
        $allowed = true;

        foreach (array_values(array_unique([...$materialOrder, ...array_keys($materials)])) as $materialId) {
            if (! isset($materials[$materialId])) {
                continue;
            }

            ['name' => $name, 'unit' => $unit, 'stock' => $stock, 'total' => $exactNeed] = $materials[$materialId];
            $needed = self::round($exactNeed);
            $short = $exactNeed->isGreaterThan($stock);
            $allowed = $allowed && ! $short;

            $takes[] = new MaterialTake(
                materialId: $materialId,
                materialName: $name,
                unit: $unit,
                perUnitExact: self::sharedPerUnit($groups, $materialId),
                needed: (string) $needed,
                inStock: (string) self::round($stock),
                after: $short ? null : (string) $stock->minus($needed)->toScale(self::SCALE),
                shortBy: $short ? (string) self::round($exactNeed->minus($stock)) : null,
            );
        }

        $mixed = $varies && count($variants) > 1;

        return new CutPlan(
            units: $units,
            takes: $takes,
            maxCuttable: $mixed ? null : $capacity->maxProducible,
            bottleneckMaterialId: $mixed ? null : $capacity->bottleneckMaterialId,
            bottleneckMaterialName: $mixed ? null : $capacity->bottleneckMaterialName,
            allowed: $allowed,
            mixed: $mixed,
            variants: $groups,
        );
    }

    /**
     * @param  list<CutBatch>  $newestFirst  the line's cuts, latest first
     * @param  array<int, string>  $stockOnHand  stock per material id, for every returnable material of the batches
     *
     * @throws InvalidArgumentException If $units is below 1, or a returnable material has no stock figure.
     */
    public function giveBack(array $newestFirst, int $units, array $stockOnHand): ReturnPlan
    {
        $this->assertUnits($units);

        $remaining = $units;
        $batches = [];
        /** @var array<int, array{taken: BigDecimal, returned: BigDecimal, returnable: bool, deleted: bool}> $totals */
        $totals = [];

        foreach ($newestFirst as $batch) {
            $outstanding = $batch->units - $batch->unitsReturned;
            $back = min($remaining, $outstanding);

            if ($back < 1) {
                continue;
            }

            $batches[] = new BatchReturn($batch->cutId, $back);
            $remaining -= $back;

            foreach ($batch->materials as $material) {
                $perUnit = BigDecimal::of($material->perUnitExact ?? throw new InvalidArgumentException(sprintf(
                    'Cut #%d remembers its amounts per colour and size: use giveBackFromGroup().', $batch->cutId,
                )));
                // What the cut holds with r units outstanding, minus what it holds once k are back: no division, no drift.
                $amount = self::round($perUnit->multipliedBy($outstanding))
                    ->minus(self::round($perUnit->multipliedBy($outstanding - $back)));

                self::addToTotals($totals, $material, $amount);
            }
        }

        return new ReturnPlan(
            units: $units,
            unitsCovered: $units - $remaining,
            batches: $batches,
            returns: self::returnsFrom($totals, $stockOnHand),
            nothingTaken: $totals === [],
        );
    }

    /**
     * Undo batch of $units units of one colour and size out of a cut (ADR-0027 decision 7): per material
     * `returned = held(r) − held(r with the group's r − k)`, `held(r) = round4(Σ_u r_u × per_unit(u, m))` over every group of the cut.
     * Lists only the materials that group uses. A cut without groups is one group: it gives what giveBack() gives.
     *
     * @param  array<int, string>  $stockOnHand  stock per material id, for every returnable material the group uses
     *
     * @throws InvalidArgumentException If $units is below 1 or above the group's outstanding units, the cut has groups but none
     *                                  in that colour and size, or a returnable material has no stock figure.
     */
    public function giveBackFromGroup(CutBatch $cut, int $colourId, int $sizeId, int $units, array $stockOnHand): ReturnPlan
    {
        $this->assertUnits($units);

        if ($cut->groups === []) {
            if ($units > $cut->units - $cut->unitsReturned) {
                throw new InvalidArgumentException(sprintf('Cut #%d has %d units outstanding, cannot give back %d.', $cut->cutId, $cut->units - $cut->unitsReturned, $units));
            }

            return $this->giveBack([$cut], $units, $stockOnHand);
        }

        $target = null;

        foreach ($cut->groups as $group) {
            if ($group->colourId === $colourId && $group->sizeId === $sizeId) {
                $target = $group;
            }
        }

        if ($target === null) {
            throw new InvalidArgumentException(sprintf('Cut #%d has no group for colour %d and size %d.', $cut->cutId, $colourId, $sizeId));
        }

        if ($units > $target->outstanding()) {
            throw new InvalidArgumentException(sprintf('The group has %d units outstanding, cannot give back %d.', $target->outstanding(), $units));
        }

        $totals = [];

        foreach ($cut->materials as $material) {
            if (! isset($target->perUnit[$material->materialId])) {
                continue;
            }

            $amount = self::heldBy($cut->groups, $material->materialId, null, 0)
                ->minus(self::heldBy($cut->groups, $material->materialId, $target, $units));

            self::addToTotals($totals, $material, $amount);
        }

        return new ReturnPlan(
            units: $units,
            unitsCovered: $units,
            batches: [new BatchReturn($cut->cutId, $units)],
            returns: self::returnsFrom($totals, $stockOnHand),
            nothingTaken: $totals === [],
        );
    }

    /** @throws InvalidArgumentException */
    private function assertUnits(int $units): void
    {
        if ($units < 1) {
            throw new InvalidArgumentException(sprintf('units must be at least 1, got %d.', $units));
        }
    }

    /**
     * round4(Σ r_u × per_unit(u, m)) over the groups, with $back units of $target given back.
     *
     * @param  list<CutGroup>  $groups
     */
    private static function heldBy(array $groups, int $materialId, ?CutGroup $target, int $back): BigDecimal
    {
        $sum = BigDecimal::zero();

        foreach ($groups as $group) {
            if (! isset($group->perUnit[$materialId])) {
                continue;
            }

            $outstanding = $group->outstanding() - ($group === $target ? $back : 0);
            $sum = $sum->plus(BigDecimal::of($group->perUnit[$materialId])->multipliedBy($outstanding));
        }

        return self::round($sum);
    }

    /**
     * The per-unit figure of a material when every variant of the plan uses it at the same amount; null when it differs.
     *
     * @param  list<CutGroup>  $groups
     */
    private static function sharedPerUnit(array $groups, int $materialId): ?string
    {
        $first = $groups[0]->perUnit[$materialId] ?? null;

        foreach ($groups as $group) {
            if ($first === null || ! isset($group->perUnit[$materialId]) || ! BigDecimal::of($group->perUnit[$materialId])->isEqualTo($first)) {
                return null;
            }
        }

        return $first;
    }

    /** @param array<int, array{taken: BigDecimal, returned: BigDecimal, returnable: bool, deleted: bool}> $totals */
    private static function addToTotals(array &$totals, CutBatchMaterial $material, BigDecimal $amount): void
    {
        $total = $totals[$material->materialId] ?? ['taken' => BigDecimal::zero(), 'returned' => BigDecimal::zero(), 'returnable' => false, 'deleted' => false];
        $total['taken'] = $total['taken']->plus($amount);

        if ($material->notReturned === null) {
            $total['returnable'] = true;
            $total['returned'] = $total['returned']->plus($amount);
        } else {
            $total['deleted'] = $total['deleted'] || $material->notReturned === CutBatchMaterial::DELETED;
        }

        $totals[$material->materialId] = $total;
    }

    /**
     * @param  array<int, array{taken: BigDecimal, returned: BigDecimal, returnable: bool, deleted: bool}>  $totals
     * @param  array<int, string>  $stockOnHand
     * @return list<MaterialReturn>
     *
     * @throws InvalidArgumentException If a returnable material has no stock figure.
     */
    private static function returnsFrom(array $totals, array $stockOnHand): array
    {
        $returns = [];

        foreach ($totals as $materialId => $total) {
            $inStock = null;

            if ($total['returnable']) {
                if (! isset($stockOnHand[$materialId])) {
                    throw new InvalidArgumentException(sprintf('Stock on hand is missing for returnable material %d.', $materialId));
                }

                $inStock = self::round(Decimals::parseNonNegative($stockOnHand[$materialId], 'stockOnHand'));
            }

            $returns[] = new MaterialReturn(
                materialId: $materialId,
                taken: (string) $total['taken']->toScale(self::SCALE),
                returned: (string) $total['returned']->toScale(self::SCALE),
                notReturned: $total['returnable'] ? null : ($total['deleted'] ? CutBatchMaterial::DELETED : CutBatchMaterial::UNIT_CHANGED),
                inStock: $inStock === null ? null : (string) $inStock,
                after: $inStock === null ? null : (string) $inStock->plus($total['returned'])->toScale(self::SCALE),
            );
        }

        return $returns;
    }

    private static function round(BigDecimal $value): BigDecimal
    {
        return $value->toScale(self::SCALE, RoundingMode::HalfUp);
    }
}
