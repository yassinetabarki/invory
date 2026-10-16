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
                $perUnit = BigDecimal::of($material->perUnitExact);
                // What the cut holds with r units outstanding, minus what it holds once k are back: no division, no drift.
                $amount = self::round($perUnit->multipliedBy($outstanding))
                    ->minus(self::round($perUnit->multipliedBy($outstanding - $back)));

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
        }

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

        return new ReturnPlan(
            units: $units,
            unitsCovered: $units - $remaining,
            batches: $batches,
            returns: $returns,
            nothingTaken: $returns === [],
        );
    }

    /** @throws InvalidArgumentException */
    private function assertUnits(int $units): void
    {
        if ($units < 1) {
            throw new InvalidArgumentException(sprintf('units must be at least 1, got %d.', $units));
        }
    }

    private static function round(BigDecimal $value): BigDecimal
    {
        return $value->toScale(self::SCALE, RoundingMode::HalfUp);
    }
}
