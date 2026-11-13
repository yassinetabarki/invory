<?php

use App\Domain\Costing\BomLine;
use App\Domain\Stock\CutBatch;
use App\Domain\Stock\CutBatchMaterial;
use App\Domain\Stock\CutGroup;
use App\Domain\Stock\CutPlan;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\MaterialReturn;
use App\Domain\Stock\MaterialTake;
use App\Domain\Stock\VariantUnits;
use Brick\Math\BigDecimal;

// INV-21-d: Start batch and Undo batch per stock (ADR-0029 section 6, appendix C and F). Denim 12oz is kept per colour
// (Indigo 250 m, Black 40 m, no Light wash); the button, thread and label are one stock.

const CCS_COLOURS = ['Indigo' => 10, 'Black' => 20, 'Light wash' => 30];
const CCS_SIZES = ['S' => 1, 'M' => 2, 'L' => 3];
const CCS_DENIM = ['S' => '1.6000', 'M' => '1.8000', 'L' => '2.0000'];
const CCS_STOCK = ['Indigo' => '250.0000', 'Black' => '40.0000', 'Light wash' => '0'];
const CCS_ORDER = [1, 3, 4, 5];

function ccsVariant(string $colour, string $size, int $units): VariantUnits
{
    return new VariantUnits(CCS_COLOURS[$colour], CCS_SIZES[$size], $units, [
        new BomLine(1, 'Denim 12oz', 'm', CCS_DENIM[$size], '10.00', '8.5000', CCS_STOCK[$colour], CCS_COLOURS[$colour], $colour === 'Light wash'),
        new BomLine(3, 'Metal shank button 17mm', 'pc', '8.0000', '0.00', '0.3500', '900.0000'),
        new BomLine(4, 'Polyester thread', 'm', '120.0000', '5.00', '0.0040', '20000.0000'),
        new BomLine(5, 'Woven brand label', 'pc', '1.0000', '0.00', '0.1200', '400.0000'),
    ]);
}

/** @param list<array{string, string, int}> $grid colour, size, units */
function ccsStart(array $grid): CutPlan
{
    return (new CuttingCalculator)->takeVariants(
        array_map(fn (array $cell): VariantUnits => ccsVariant(...$cell), $grid),
        CCS_ORDER,
        true,
    );
}

function ccsStartCard(): CutPlan
{
    return ccsStart([['Indigo', 'M', 6], ['Indigo', 'L', 10], ['Black', 'S', 5]]);
}

/** @param list<MaterialTake>|list<MaterialReturn> $rows */
function ccsColumn(array $rows, string $field): array
{
    return array_map(fn (object $row): mixed => $row->{$field}, $rows);
}

/** A cut as the database would hand it back: one remembered take per stock, one group per variant. */
function ccsCut(CutPlan $plan, ?array $groups = null): CutBatch
{
    return new CutBatch(7, $plan->units, 0, array_map(
        fn (MaterialTake $take): CutBatchMaterial => new CutBatchMaterial($take->materialId, null, $take->needed, null, $take->colourId),
        $plan->takes,
    ), $groups ?? $plan->variants);
}

/** Today's stock after the start, by stock key. */
function ccsStockAfter(CutPlan $plan): array
{
    $stock = [];

    foreach ($plan->takes as $take) {
        $stock[CuttingCalculator::stockKey($take->materialId, $take->colourId)] = $take->after;
    }

    return $stock;
}

it('takes 33.88 m from Indigo and 8.8 m from Black, one take per stock, rounded once (AC-14)', function () {
    $plan = ccsStartCard();

    expect($plan->units)->toBe(21)
        ->and($plan->allowed)->toBeTrue()
        ->and($plan->mixed)->toBeTrue()
        ->and($plan->maxCuttable)->toBeNull()
        ->and(ccsColumn($plan->takes, 'materialName'))->toBe(['Denim 12oz', 'Denim 12oz', 'Metal shank button 17mm', 'Polyester thread', 'Woven brand label'])
        ->and(ccsColumn($plan->takes, 'colourId'))->toBe([10, 20, null, null, null])
        ->and(ccsColumn($plan->takes, 'missing'))->toBe([false, false, false, false, false])
        ->and(ccsColumn($plan->takes, 'inStock'))->toBe(['250.0000', '40.0000', '900.0000', '20000.0000', '400.0000'])
        ->and(ccsColumn($plan->takes, 'needed'))->toBe(['33.8800', '8.8000', '168.0000', '2646.0000', '21.0000'])
        ->and(ccsColumn($plan->takes, 'after'))->toBe(['216.1200', '31.2000', '732.0000', '17354.0000', '379.0000'])
        ->and(ccsColumn($plan->takes, 'shortBy'))->toBe([null, null, null, null, null]);
});

it('says which colour is short, and a colour the material lacks is a stock of 0 short by the whole need (AC-14)', function () {
    $short = ccsStart([['Indigo', 'M', 6], ['Indigo', 'L', 10], ['Black', 'S', 30]]);

    expect($short->allowed)->toBeFalse()
        ->and($short->mixed)->toBeTrue()
        ->and(ccsColumn($short->takes, 'colourId'))->toBe([10, 20, null, null, null])
        ->and(ccsColumn($short->takes, 'shortBy'))->toBe([null, '12.8000', null, null, null])
        ->and(ccsColumn($short->takes, 'after'))->toBe(['216.1200', null, '532.0000', '14204.0000', '354.0000']);

    $missing = ccsStart([['Indigo', 'M', 6], ['Light wash', 'S', 5]]);

    expect($missing->allowed)->toBeFalse()
        ->and(ccsColumn($missing->takes, 'colourId'))->toBe([10, 30, null, null, null])
        ->and(ccsColumn($missing->takes, 'missing'))->toBe([false, true, false, false, false])
        ->and(ccsColumn($missing->takes, 'inStock'))->toBe(['250.0000', '0.0000', '900.0000', '20000.0000', '400.0000'])
        ->and(ccsColumn($missing->takes, 'shortBy'))->toBe([null, '8.8000', null, null, null])
        ->and($missing->takes[1]->after)->toBeNull();
});

it('gives back 22 m to Indigo and nothing to Black when the Indigo L batch is undone (AC-14)', function () {
    $plan = ccsStartCard();
    $return = (new CuttingCalculator)->giveBackFromGroup(ccsCut($plan), 10, 3, 10, ccsStockAfter($plan));

    expect(ccsColumn($return->returns, 'materialId'))->toBe([1, 3, 4, 5])
        ->and(ccsColumn($return->returns, 'colourId'))->toBe([10, null, null, null])
        ->and(ccsColumn($return->returns, 'returned'))->toBe(['22.0000', '80.0000', '1260.0000', '10.0000'])
        ->and(ccsColumn($return->returns, 'inStock'))->toBe(['216.1200', '732.0000', '17354.0000', '379.0000'])
        ->and(ccsColumn($return->returns, 'after'))->toBe(['238.1200', '812.0000', '18614.0000', '389.0000']);
});

it('gives back exactly what was taken when every batch of a start is undone, stock by stock (AC-14)', function () {
    $plan = ccsStartCard();
    $groups = $plan->variants;
    $back = [];

    foreach ([[10, 2, 6], [10, 3, 10], [20, 1, 5]] as [$colourId, $sizeId, $units]) {
        $cut = new CutBatch(7, $plan->units, 0, ccsCut($plan)->materials, $groups);
        $return = (new CuttingCalculator)->giveBackFromGroup($cut, $colourId, $sizeId, $units, ccsStockAfter($plan));

        foreach ($return->returns as $line) {
            $key = CuttingCalculator::stockKey($line->materialId, $line->colourId);
            $back[$key] = (string) BigDecimal::of($back[$key] ?? '0')->plus($line->returned);
        }

        $groups = array_map(
            fn (CutGroup $group): CutGroup => $group->colourId === $colourId && $group->sizeId === $sizeId
                ? new CutGroup($group->colourId, $group->sizeId, $group->units, $group->unitsReturned + $units, $group->perUnit)
                : $group,
            $groups,
        );
    }

    $taken = [];

    foreach ($plan->takes as $take) {
        $taken[CuttingCalculator::stockKey($take->materialId, $take->colourId)] = $take->needed;
    }

    $back = array_map(fn (string $value): string => (string) BigDecimal::of($value)->toScale(4), $back);
    ksort($back);
    ksort($taken);

    expect($back)->toBe($taken);
});

it('returns a cut made before INV-21 as before: a take without a colour holds every group (AC-14)', function () {
    // 4 Indigo M and 5 Black S, remembered as the old rows were: no colour on any take.
    $plan = ccsStart([['Indigo', 'M', 4], ['Black', 'S', 5]]);
    $old = new CutBatch(7, 9, 0, array_map(
        fn (MaterialTake $take): CutBatchMaterial => new CutBatchMaterial($take->materialId, null, $take->needed, null),
        array_values(array_filter($plan->takes, fn (MaterialTake $take): bool => $take->colourId === null || $take->colourId === 10)),
    ), $plan->variants);

    $return = (new CuttingCalculator)->giveBackFromGroup($old, 10, 2, 4, [1 => '100.0000', 3 => '100.0000', 4 => '100.0000', 5 => '100.0000']);

    expect(ccsColumn($return->returns, 'colourId'))->toBe([null, null, null, null])
        ->and(ccsColumn($return->returns, 'returned'))->toBe(['7.9200', '32.0000', '504.0000', '4.0000']);
});
