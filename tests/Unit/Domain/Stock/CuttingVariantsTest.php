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

// INV-16-c: Start batch and Undo batch over a grid of colours and sizes (ADR-0027 decisions 6 and 7, appendix E and I).
// The card's Denim Jacket: Indigo denim by size on the Indigo variants, Black denim on the Black ones; button, thread, label for all.

const CV_COLOURS = ['Indigo' => 10, 'Black' => 20];
const CV_SIZES = ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4];
const CV_DENIM = ['S' => '1.6000', 'M' => '1.8000', 'L' => '2.0000', 'XL' => '2.2000'];
const CV_ORDER = [1, 2, 3, 4, 5];
const CV_BIG_STOCK = [1 => '1000.0000', 2 => '1000.0000', 3 => '1000.0000', 4 => '1000.0000', 5 => '1000.0000'];

/** @return list<BomLine> the lines of one variant, in composition order */
function cvLines(string $colour, string $size): array
{
    $denim = $colour === 'Indigo'
        ? new BomLine(1, 'Denim 12oz Indigo', 'm', CV_DENIM[$size], '10.00', '8.5000', '250.0000')
        : new BomLine(2, 'Denim 12oz Black', 'm', CV_DENIM[$size], '10.00', '8.5000', '40.0000');

    return [
        $denim,
        new BomLine(3, 'Metal shank button 17mm', 'pc', '8.0000', '0.00', '0.3500', '900.0000'),
        new BomLine(4, 'Polyester thread', 'm', '120.0000', '5.00', '0.0040', '20000.0000'),
        new BomLine(5, 'Woven brand label', 'pc', '1.0000', '0.00', '0.1200', '400.0000'),
    ];
}

function cvVariant(string $colour, string $size, int $units): VariantUnits
{
    return new VariantUnits(CV_COLOURS[$colour], CV_SIZES[$size], $units, cvLines($colour, $size));
}

/** @param list<MaterialTake> $takes */
function cvTakes(array $takes, string $field): array
{
    return array_map(fn (MaterialTake $take): ?string => $take->{$field}, $takes);
}

/** @param array<int, string> $perUnit */
function cvAmounts(array $perUnit): array
{
    return array_map(fn (string $value): string => (string) BigDecimal::of($value)->toScale(4), $perUnit);
}

/** @param list<MaterialReturn> $returns */
function cvReturned(array $returns): array
{
    $byMaterial = [];

    foreach ($returns as $return) {
        $byMaterial[$return->materialId] = $return->returned;
    }

    return $byMaterial;
}

/** A cut as the database would hand it back: the takes' amounts taken, no per-unit on the materials, one group per variant. */
function cvCut(CutPlan $plan, array $groups): CutBatch
{
    return new CutBatch(7, $plan->units, 0, array_map(
        fn (MaterialTake $take): CutBatchMaterial => new CutBatchMaterial($take->materialId, null, $take->needed, null),
        $plan->takes,
    ), $groups);
}

function cvStart(): CutPlan
{
    return (new CuttingCalculator)->takeVariants(
        [cvVariant('Indigo', 'M', 6), cvVariant('Indigo', 'L', 10), cvVariant('Black', 'S', 5)],
        CV_ORDER,
        true,
    );
}

// --- Take ------------------------------------------------------------------------------------------------------------

it('takes the card\'s mixed start of 21 units, material by material, and remembers each variant (AC-6)', function () {
    $plan = cvStart();

    expect($plan->units)->toBe(21)
        ->and($plan->allowed)->toBeTrue()
        ->and($plan->mixed)->toBeTrue()
        ->and($plan->maxCuttable)->toBeNull()
        ->and($plan->bottleneckMaterialId)->toBeNull()
        ->and($plan->bottleneckMaterialName)->toBeNull()
        ->and(cvTakes($plan->takes, 'materialName'))->toBe(['Denim 12oz Indigo', 'Denim 12oz Black', 'Metal shank button 17mm', 'Polyester thread', 'Woven brand label'])
        ->and(cvTakes($plan->takes, 'needed'))->toBe(['33.8800', '8.8000', '168.0000', '2646.0000', '21.0000'])
        ->and(cvTakes($plan->takes, 'after'))->toBe(['216.1200', '31.2000', '732.0000', '17354.0000', '379.0000'])
        ->and(cvTakes($plan->takes, 'shortBy'))->toBe([null, null, null, null, null])
        // Indigo denim differs between the variants that use it and Black denim is not used by all of them: no single figure.
        ->and(cvTakes($plan->takes, 'perUnitExact')[0])->toBeNull()
        ->and(cvTakes($plan->takes, 'perUnitExact')[1])->toBeNull()
        ->and(cvAmounts([$plan->takes[2]->perUnitExact]))->toBe(['8.0000'])
        ->and($plan->variants)->toHaveCount(3)
        ->and([$plan->variants[0]->colourId, $plan->variants[0]->sizeId, $plan->variants[0]->units, $plan->variants[0]->unitsReturned])->toBe([10, 2, 6, 0])
        ->and(cvAmounts($plan->variants[0]->perUnit))->toBe([1 => '1.9800', 3 => '8.0000', 4 => '126.0000', 5 => '1.0000'])
        ->and(cvAmounts($plan->variants[1]->perUnit))->toBe([1 => '2.2000', 3 => '8.0000', 4 => '126.0000', 5 => '1.0000'])
        ->and([$plan->variants[2]->colourId, $plan->variants[2]->sizeId, $plan->variants[2]->units])->toBe([20, 1, 5])
        ->and(cvAmounts($plan->variants[2]->perUnit))->toBe([2 => '1.7600', 3 => '8.0000', 4 => '126.0000', 5 => '1.0000']);
});

it('refuses 41.8 m of Black denim against 40 m and shows the shortfall, and starts the 39.38 m grid (AC-6)', function () {
    $calculator = new CuttingCalculator;

    $refused = $calculator->takeVariants(
        [cvVariant('Black', 'S', 10), cvVariant('Black', 'XL', 10), cvVariant('Indigo', 'M', 10)],
        CV_ORDER,
        true,
    );

    expect($refused->allowed)->toBeFalse()
        ->and($refused->mixed)->toBeTrue()
        ->and($refused->maxCuttable)->toBeNull()
        ->and(cvTakes($refused->takes, 'needed'))->toBe(['19.8000', '41.8000', '240.0000', '3780.0000', '30.0000'])
        ->and(cvTakes($refused->takes, 'shortBy'))->toBe([null, '1.8000', null, null, null])
        ->and(cvTakes($refused->takes, 'after'))->toBe(['230.2000', null, '660.0000', '16220.0000', '370.0000']);

    $started = $calculator->takeVariants([cvVariant('Black', 'S', 10), cvVariant('Black', 'XL', 9)], CV_ORDER, true);

    expect($started->allowed)->toBeTrue()
        ->and($started->takes[0]->materialId)->toBe(2)
        ->and($started->takes[0]->needed)->toBe('39.3800')
        ->and($started->takes[0]->after)->toBe('0.6200');
});

it('checks the exact total, never the rounded one', function () {
    // 4.6 + 0.40004 = 5.00004 m against 5 m: round4 is 5.0000, which "fits", but the exact total is above the stock.
    $line = fn (string $quantity, string $waste): array => [new BomLine(1, 'Silk', 'm', $quantity, $waste, '1.0000', '5.0000')];

    $plan = (new CuttingCalculator)->takeVariants(
        [new VariantUnits(10, 1, 1, $line('4.6000', '0.00')), new VariantUnits(10, 2, 1, $line('0.4000', '0.01'))],
        [1],
        true,
    );

    expect($plan->allowed)->toBeFalse()
        ->and($plan->takes[0]->needed)->toBe('5.0000')
        ->and($plan->takes[0]->after)->toBeNull()
        ->and($plan->takes[0]->shortBy)->toBe('0.0000');
});

it('keeps today\'s figures for one colour and size, and for a product that does not vary', function () {
    $calculator = new CuttingCalculator;

    $single = $calculator->takeVariants([cvVariant('Black', 'XL', 17)], CV_ORDER, true);

    expect($single->mixed)->toBeFalse()
        ->and($single->maxCuttable)->toBe(16)
        ->and($single->bottleneckMaterialId)->toBe(2)
        ->and($single->bottleneckMaterialName)->toBe('Denim 12oz Black')
        ->and($single->allowed)->toBeFalse()
        ->and($single->takes[0]->shortBy)->toBe('1.1400')
        ->and(cvAmounts([$single->takes[0]->perUnitExact]))->toBe(['2.4200']);

    // Several variants, one shared composition: exactly take() for the total.
    $lines = cvLines('Indigo', 'M');
    $shared = $calculator->takeVariants(
        [new VariantUnits(10, 2, 10, $lines), new VariantUnits(20, 1, 7, $lines)],
        [1, 3, 4, 5],
        false,
    );
    $today = $calculator->take($lines, 17);

    expect($shared->mixed)->toBeFalse()
        ->and($shared->units)->toBe($today->units)
        ->and($shared->takes)->toEqual($today->takes)
        ->and($shared->maxCuttable)->toBe($today->maxCuttable)
        ->and($shared->bottleneckMaterialId)->toBe($today->bottleneckMaterialId)
        ->and($shared->bottleneckMaterialName)->toBe($today->bottleneckMaterialName)
        ->and($shared->allowed)->toBe($today->allowed)
        ->and($shared->variants)->toHaveCount(2);
});

// --- Give back -------------------------------------------------------------------------------------------------------

it('gives back one variant\'s share when the Indigo L batch is undone, and nothing of the Black denim (AC-6)', function () {
    $plan = cvStart();
    $cut = cvCut($plan, $plan->variants);

    $back = (new CuttingCalculator)->giveBackFromGroup($cut, 10, 3, 10, CV_BIG_STOCK);

    expect($back->units)->toBe(10)
        ->and($back->unitsCovered)->toBe(10)
        ->and($back->nothingTaken)->toBeFalse()
        ->and(array_map(fn ($batch): array => [$batch->cutId, $batch->units], $back->batches))->toBe([[7, 10]])
        ->and(cvReturned($back->returns))->toBe([1 => '22.0000', 3 => '80.0000', 4 => '1260.0000', 5 => '10.0000'])
        ->and(array_map(fn (MaterialReturn $r): ?string => $r->after, $back->returns))->toBe(['1022.0000', '1080.0000', '2260.0000', '1010.0000']);
});

it('gives back exactly what the start took when every batch is undone, whatever the order (AC-6)', function (callable $start, array $undoOrder) {
    $calculator = new CuttingCalculator;
    $plan = $start();
    $groups = $plan->variants;
    $given = [];

    foreach ($undoOrder as [$colourId, $sizeId]) {
        $cut = cvCut($plan, $groups);
        $units = null;

        foreach ($groups as $index => $group) {
            if ($group->colourId === $colourId && $group->sizeId === $sizeId) {
                $units = $group->outstanding();
                $groups[$index] = new CutGroup($colourId, $sizeId, $group->units, $group->units, $group->perUnit);
            }
        }

        $back = $calculator->giveBackFromGroup($cut, $colourId, $sizeId, $units, CV_BIG_STOCK);

        foreach ($back->returns as $return) {
            $given[$return->materialId] = BigDecimal::of($given[$return->materialId] ?? '0')->plus($return->returned);
        }
    }

    foreach ($plan->takes as $take) {
        expect((string) $given[$take->materialId]->toScale(4))->toBe($take->needed);
    }
})->with([
    'the card\'s start, Indigo L then M then Black S' => [fn () => cvStart(), [[10, 3], [10, 2], [20, 1]]],
    'the card\'s start, Black S first' => [fn () => cvStart(), [[20, 1], [10, 2], [10, 3]]],
    'two variants whose halves round apart (0.40004 each)' => [
        fn () => (new CuttingCalculator)->takeVariants([
            new VariantUnits(10, 1, 1, [new BomLine(1, 'Silk', 'm', '0.4000', '0.01', '1.0000', '5.0000')]),
            new VariantUnits(10, 2, 1, [new BomLine(1, 'Silk', 'm', '0.4000', '0.01', '1.0000', '5.0000')]),
        ], [1], true),
        [[10, 1], [10, 2]],
    ],
]);

it('gives back a cut made before INV-16 as one group, as it always did (AC-7)', function () {
    // The Denim Jacket cut of 16 units (INV-5): exact per-unit amounts on the materials, no groups.
    $cut = new CutBatch(3, 16, 0, [
        new CutBatchMaterial(1, '1.98000000', '31.6800', null),
        new CutBatchMaterial(2, '8.00000000', '128.0000', null),
        new CutBatchMaterial(3, '126.00000000', '2016.0000', null),
        new CutBatchMaterial(4, '1.00000000', '16.0000', null),
    ]);
    $stock = [1 => '218.3200', 2 => '772.0000', 3 => '17984.0000', 4 => '384.0000'];

    $calculator = new CuttingCalculator;
    $back = $calculator->giveBackFromGroup($cut, 10, 2, 4, $stock);

    expect(array_map(fn (MaterialReturn $r): string => $r->returned, $back->returns))->toBe(['7.9200', '32.0000', '504.0000', '4.0000'])
        ->and($back)->toEqual($calculator->giveBack([$cut], 4, $stock));
});

// --- Guards ----------------------------------------------------------------------------------------------------------

/** A cut of one group (Indigo S, 5 units, 2 back) and the figures its materials need. */
function cvGroupedCut(array $materials = [new CutBatchMaterial(1, null, '7.5000', null)]): CutBatch
{
    return new CutBatch(7, 5, 2, $materials, [new CutGroup(10, 1, 5, 2, [1 => '1.50000000', 2 => '2.00000000', 3 => '3.00000000'])]);
}

it('refuses what no cut can give or plan, and says why', function (callable $attempt, string $message) {
    expect($attempt)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'a plan without a variant' => [fn () => (new CuttingCalculator)->takeVariants([], CV_ORDER, true), 'A plan needs at least one variant.'],
    'no unit back' => [fn () => (new CuttingCalculator)->giveBackFromGroup(cvGroupedCut(), 10, 1, 0, CV_BIG_STOCK), 'units must be at least 1, got 0.'],
    'a negative number of units back' => [fn () => (new CuttingCalculator)->giveBackFromGroup(cvGroupedCut(), 10, 1, -3, CV_BIG_STOCK), 'units must be at least 1, got -3.'],
    'more than the group has outstanding' => [fn () => (new CuttingCalculator)->giveBackFromGroup(cvGroupedCut(), 10, 1, 4, CV_BIG_STOCK), 'The group has 3 units outstanding, cannot give back 4.'],
    'more than a cut without groups has outstanding' => [
        fn () => (new CuttingCalculator)->giveBackFromGroup(new CutBatch(3, 16, 4, [new CutBatchMaterial(1, '1.98000000', '31.6800', null)]), 10, 2, 13, CV_BIG_STOCK),
        'Cut #3 has 12 units outstanding, cannot give back 13.',
    ],
    'a colour and size the cut has no group for' => [fn () => (new CuttingCalculator)->giveBackFromGroup(cvGroupedCut(), 20, 1, 1, CV_BIG_STOCK), 'Cut #7 has no group for colour 20 and size 1.'],
    'a returnable material with no stock figure' => [fn () => (new CuttingCalculator)->giveBackFromGroup(cvGroupedCut(), 10, 1, 1, []), 'Stock on hand is missing for returnable material 1.'],
]);

it('shows a deleted or unit-changed material of a cut with groups as taken and gives back only the others', function () {
    $cut = cvGroupedCut([
        new CutBatchMaterial(1, null, '7.5000', CutBatchMaterial::DELETED),
        new CutBatchMaterial(2, null, '10.0000', CutBatchMaterial::UNIT_CHANGED),
        new CutBatchMaterial(3, null, '15.0000', null),
    ]);

    $back = (new CuttingCalculator)->giveBackFromGroup($cut, 10, 1, 2, [3 => '100.0000']);

    expect(array_map(fn (MaterialReturn $r): array => [$r->materialId, $r->taken, $r->returned, $r->notReturned, $r->inStock, $r->after], $back->returns))->toBe([
        [1, '3.0000', '0.0000', CutBatchMaterial::DELETED, null, null],
        [2, '4.0000', '0.0000', CutBatchMaterial::UNIT_CHANGED, null, null],
        [3, '6.0000', '6.0000', null, '100.0000', '106.0000'],
    ])->and($back->nothingTaken)->toBeFalse();
});
