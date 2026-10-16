<?php

use App\Domain\Costing\BomLine;
use App\Domain\Stock\BatchReturn;
use App\Domain\Stock\CutBatch;
use App\Domain\Stock\CutBatchMaterial;
use App\Domain\Stock\CuttingCalculator;
use App\Domain\Stock\MaterialReturn;
use App\Domain\Stock\MaterialTake;
use Brick\Math\BigDecimal;

require_once __DIR__.'/../Costing/CostingTestSupport.php';

// S-53-b: the maths of taking materials for a cut and of giving them back (ADR-0014). Denim Jacket = the costing skill's golden BOM.

/** @param list<MaterialTake> $takes */
function cuttingColumn(array $takes, string $field): array
{
    return array_map(fn (MaterialTake $take): ?string => $take->{$field}, $takes);
}

/** @param list<MaterialReturn> $returns */
function cuttingReturned(array $returns, string $field = 'returned'): array
{
    return array_map(fn (MaterialReturn $return): ?string => $return->{$field}, $returns);
}

/** A cut of the Denim Jacket remembered as the database stores it: exact per-unit amounts and the amounts taken. */
function cuttingDenimBatch(int $cutId = 1, int $units = 30, int $unitsReturned = 0): CutBatch
{
    return new CutBatch($cutId, $units, $unitsReturned, [
        new CutBatchMaterial(1, '1.98000000', '59.4000', null),
        new CutBatchMaterial(2, '8.00000000', '240.0000', null),
        new CutBatchMaterial(3, '126.00000000', '3780.0000', null),
        new CutBatchMaterial(4, '1.00000000', '30.0000', null),
    ]);
}

/** A cut of $units units with one material at $perUnit. */
function cuttingSingle(int $cutId, int $units, string $perUnit, int $unitsReturned = 0, int $materialId = 1): CutBatch
{
    return new CutBatch($cutId, $units, $unitsReturned, [new CutBatchMaterial($materialId, $perUnit, '0.0000', null)]);
}

const CUTTING_DENIM_STOCK = [1 => '190.6000', 2 => '660.0000', 3 => '16220.0000', 4 => '370.0000'];

// --- Take ------------------------------------------------------------------------------------------------------------

it('takes the Denim Jacket materials for 30 units (AC-1)', function () {
    $plan = (new CuttingCalculator)->take(costingDenimJacketLines(), 30);

    expect($plan->units)->toBe(30)
        ->and($plan->allowed)->toBeTrue()
        ->and($plan->hasComposition())->toBeTrue()
        ->and($plan->maxCuttable)->toBe(112)
        ->and($plan->bottleneckMaterialId)->toBe(2)
        ->and($plan->bottleneckMaterialName)->toBe('Metal shank button 17mm')
        ->and(cuttingColumn($plan->takes, 'materialName'))->toBe(['Denim 12oz Indigo', 'Metal shank button 17mm', 'Polyester thread', 'Woven brand label'])
        ->and(cuttingColumn($plan->takes, 'unit'))->toBe(['m', 'pc', 'm', 'pc'])
        ->and(cuttingColumn($plan->takes, 'perUnitExact'))->toBe(['1.98000000', '8.00000000', '126.00000000', '1.00000000'])
        ->and(cuttingColumn($plan->takes, 'needed'))->toBe(['59.4000', '240.0000', '3780.0000', '30.0000'])
        ->and(cuttingColumn($plan->takes, 'inStock'))->toBe(['250.0000', '900.0000', '20000.0000', '400.0000'])
        ->and(cuttingColumn($plan->takes, 'after'))->toBe(['190.6000', '660.0000', '16220.0000', '370.0000'])
        ->and(cuttingColumn($plan->takes, 'shortBy'))->toBe([null, null, null, null]);
});

it('refuses a cut the stock cannot cover and says by how much (AC-3)', function () {
    $lines = costingDenimJacketLines('100.0000');
    $calculator = new CuttingCalculator;

    $refused = $calculator->take($lines, 20);
    $button = $refused->takes[1];

    expect($refused->allowed)->toBeFalse()
        ->and($refused->maxCuttable)->toBe(12)
        ->and($refused->bottleneckMaterialName)->toBe('Metal shank button 17mm')
        ->and($button->needed)->toBe('160.0000')
        ->and($button->shortBy)->toBe('60.0000')
        ->and($button->after)->toBeNull()
        ->and(cuttingColumn($refused->takes, 'shortBy'))->toBe([null, '60.0000', null, null]);

    $allowed = $calculator->take($lines, 12);

    expect($allowed->allowed)->toBeTrue()
        ->and($allowed->takes[1]->after)->toBe('4.0000')
        ->and($allowed->takes[1]->shortBy)->toBeNull();
});

it('gives every short material a short-by figure and names only the bottleneck (AC-3)', function () {
    // Buttons 100 (12 units) and thread 2000 (15 units), both short for 20 units; the button is the bottleneck.
    $lines = costingDenimJacketLines('100.0000');
    $lines[2] = new BomLine(3, 'Polyester thread', 'm', '120.0000', '5.00', '0.0040', '2000.0000');

    $plan = (new CuttingCalculator)->take($lines, 20);

    expect($plan->allowed)->toBeFalse()
        ->and(cuttingColumn($plan->takes, 'shortBy'))->toBe([null, '60.0000', '520.0000', null])
        ->and(cuttingColumn($plan->takes, 'after'))->toBe(['210.4000', null, null, '380.0000'])
        ->and($plan->maxCuttable)->toBe(12)
        ->and($plan->bottleneckMaterialId)->toBe(2);
});

it('allows at most 0 when one unit already needs more than the stock (AC-3)', function () {
    $plan = (new CuttingCalculator)->take(costingDenimJacketLines('7.0000'), 1);

    expect($plan->allowed)->toBeFalse()
        ->and($plan->maxCuttable)->toBe(0)
        ->and($plan->bottleneckMaterialId)->toBe(2)
        ->and($plan->takes[1]->shortBy)->toBe('1.0000');
});

it('handles a material at stock 0', function () {
    $plan = (new CuttingCalculator)->take([costingLine(1, 'Zip', '2', '0', '1', '0')], 3);

    expect($plan->allowed)->toBeFalse()
        ->and($plan->maxCuttable)->toBe(0)
        ->and($plan->takes[0]->needed)->toBe('6.0000')
        ->and($plan->takes[0]->inStock)->toBe('0.0000')
        ->and($plan->takes[0]->shortBy)->toBe('6.0000')
        ->and($plan->takes[0]->after)->toBeNull();
});

it('allows a cut on an empty composition and takes nothing', function () {
    $plan = (new CuttingCalculator)->take([], 5);

    expect($plan->allowed)->toBeTrue()
        ->and($plan->hasComposition())->toBeFalse()
        ->and($plan->takes)->toBe([])
        ->and($plan->maxCuttable)->toBeNull()
        ->and($plan->bottleneckMaterialId)->toBeNull()
        ->and($plan->bottleneckMaterialName)->toBeNull();
});

it('keeps tiny amounts exact: 0.0001 m at 0.01 % waste for 10 000 units needs 1.0001', function () {
    $lines = [costingLine(1, 'Thread', '0.0001', '0.01', '1', '5')];

    $plan = (new CuttingCalculator)->take($lines, 10000);

    expect($plan->takes[0]->perUnitExact)->toBe('0.00010001')
        ->and($plan->takes[0]->needed)->toBe('1.0001')
        ->and($plan->takes[0]->after)->toBe('3.9999')
        ->and($plan->allowed)->toBeTrue();
});

it('never leaves a negative stock when the exact check allows the cut', function () {
    $calculator = new CuttingCalculator;

    // Stock exactly equal to the need.
    $exact = $calculator->take([costingLine(1, 'Thread', '0.0001', '0.01', '1', '1.0001')], 10000);
    expect($exact->allowed)->toBeTrue()
        ->and($exact->takes[0]->after)->toBe('0.0000');

    // Every allowed size of the Denim Jacket (112 = the limit), and the first refused one.
    foreach ([1, 50, 111, 112] as $units) {
        $plan = $calculator->take(costingDenimJacketLines(), $units);

        expect($plan->allowed)->toBeTrue();

        foreach ($plan->takes as $take) {
            expect(BigDecimal::of($take->after)->isNegative())->toBeFalse();
        }
    }

    expect($calculator->take(costingDenimJacketLines(), 113)->allowed)->toBeFalse();
});

it('rejects fewer than one unit', function (int $units) {
    $calculator = new CuttingCalculator;

    expect(fn () => $calculator->take(costingDenimJacketLines(), $units))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $calculator->giveBack([], $units, []))->toThrow(InvalidArgumentException::class);
})->with([0, -3]);

// --- Give back -------------------------------------------------------------------------------------------------------

it('returns what 5 of 30 cut units took (AC-5)', function () {
    $plan = (new CuttingCalculator)->giveBack([cuttingDenimBatch()], 5, CUTTING_DENIM_STOCK);

    expect($plan->units)->toBe(5)
        ->and($plan->unitsCovered)->toBe(5)
        ->and($plan->nothingTaken)->toBeFalse()
        ->and($plan->changesStock())->toBeTrue()
        ->and($plan->batches)->toEqual([new BatchReturn(1, 5)])
        ->and(cuttingReturned($plan->returns, 'materialId'))->toEqual([1, 2, 3, 4])
        ->and(cuttingReturned($plan->returns, 'taken'))->toBe(['9.9000', '40.0000', '630.0000', '5.0000'])
        ->and(cuttingReturned($plan->returns))->toBe(['9.9000', '40.0000', '630.0000', '5.0000'])
        ->and(cuttingReturned($plan->returns, 'inStock'))->toBe(['190.6000', '660.0000', '16220.0000', '370.0000'])
        ->and(cuttingReturned($plan->returns, 'after'))->toBe(['200.5000', '700.0000', '16850.0000', '375.0000'])
        ->and(cuttingReturned($plan->returns, 'notReturned'))->toBe([null, null, null, null]);
});

it('returns the latest cut first and what each cut remembered (AC-5)', function () {
    $calculator = new CuttingCalculator;
    $older = cuttingSingle(1, 30, '8.00000000');
    $newer = cuttingSingle(2, 10, '10.00000000');

    $first = $calculator->giveBack([$newer, $older], 5, [1 => '100.0000']);

    expect($first->batches)->toEqual([new BatchReturn(2, 5)])
        ->and($first->returns[0]->returned)->toBe('50.0000')
        ->and($first->returns[0]->after)->toBe('150.0000');

    // 5 of the newer cut are back already; 10 more come 5 from it and 5 from the older one.
    $second = $calculator->giveBack([cuttingSingle(2, 10, '10.00000000', 5), $older], 10, [1 => '100.0000']);

    expect($second->batches)->toEqual([new BatchReturn(2, 5), new BatchReturn(1, 5)])
        ->and($second->unitsCovered)->toBe(10)
        ->and($second->returns[0]->returned)->toBe('90.0000')
        ->and($second->returns[0]->taken)->toBe('90.0000');
});

/** Returns a cut piece by piece and sums what each step gives back. */
function cuttingReturnInPieces(int $units, string $perUnit, array $pieces): string
{
    $calculator = new CuttingCalculator;
    $sum = BigDecimal::zero();
    $returned = 0;

    foreach ($pieces as $piece) {
        $plan = $calculator->giveBack([cuttingSingle(1, $units, $perUnit, $returned)], $piece, [1 => '0.0000']);
        $sum = $sum->plus($plan->returns[0]->returned);
        $returned += $piece;
    }

    return (string) $sum;
}

it('gives back exactly what a cut took, whatever the pieces (AC-5)', function (int $units, string $perUnit, string $taken, array $pieces) {
    expect(cuttingReturnInPieces($units, $perUnit, $pieces))->toBe($taken);
})->with([
    '3 units at 0.00015, one at a time' => [3, '0.00015000', '0.0005', [1, 1, 1]],
    '3 units at 0.00015, all at once' => [3, '0.00015000', '0.0005', [3]],
    '3 units at 0.00015, 2 then 1' => [3, '0.00015000', '0.0005', [2, 1]],
    '7 units at 0.12345678, 3 then 4' => [7, '0.12345678', '0.8642', [3, 4]],
    '7 units at 0.12345678, one at a time' => [7, '0.12345678', '0.8642', [1, 1, 1, 1, 1, 1, 1]],
    '7 units at 0.12345678, 1 then 2 then 4' => [7, '0.12345678', '0.8642', [1, 2, 4]],
    '10 units at 1.98, 4 then 6' => [10, '1.98000000', '19.8000', [4, 6]],
]);

it('writes off a deleted material and a material whose unit changed, and returns the rest (AC-5)', function () {
    $batch = new CutBatch(1, 10, 0, [
        new CutBatchMaterial(1, '2.00000000', '20.0000', null),
        new CutBatchMaterial(2, '1.00000000', '10.0000', CutBatchMaterial::DELETED),
        new CutBatchMaterial(3, '3.00000000', '30.0000', CutBatchMaterial::UNIT_CHANGED),
    ]);

    $plan = (new CuttingCalculator)->giveBack([$batch], 4, [1 => '5.0000']);

    expect(cuttingReturned($plan->returns))->toBe(['8.0000', '0.0000', '0.0000'])
        ->and(cuttingReturned($plan->returns, 'taken'))->toBe(['8.0000', '4.0000', '12.0000'])
        ->and(cuttingReturned($plan->returns, 'notReturned'))->toBe([null, 'deleted', 'unit_changed'])
        ->and(cuttingReturned($plan->returns, 'inStock'))->toBe(['5.0000', null, null])
        ->and(cuttingReturned($plan->returns, 'after'))->toBe(['13.0000', null, null])
        ->and($plan->changesStock())->toBeTrue();
});

it('flags a material across cuts: deleted wins, and a partly returnable one still comes back', function () {
    $newer = new CutBatch(2, 5, 0, [
        new CutBatchMaterial(1, '1.00000000', '5.0000', CutBatchMaterial::UNIT_CHANGED),
        new CutBatchMaterial(2, '1.00000000', '5.0000', CutBatchMaterial::DELETED),
    ]);
    $older = new CutBatch(1, 5, 0, [
        new CutBatchMaterial(1, '1.00000000', '5.0000', CutBatchMaterial::DELETED),
        new CutBatchMaterial(2, '1.00000000', '5.0000', null),
    ]);

    $plan = (new CuttingCalculator)->giveBack([$newer, $older], 8, [2 => '1.0000']);

    expect(cuttingReturned($plan->returns))->toBe(['0.0000', '3.0000'])
        ->and(cuttingReturned($plan->returns, 'taken'))->toBe(['8.0000', '8.0000'])
        ->and(cuttingReturned($plan->returns, 'notReturned'))->toBe(['deleted', null])
        ->and(cuttingReturned($plan->returns, 'after'))->toBe([null, '4.0000']);
});

it('does not change stock when nothing is returnable', function () {
    $batch = new CutBatch(1, 2, 0, [new CutBatchMaterial(1, '1.00000000', '2.0000', CutBatchMaterial::DELETED)]);

    $plan = (new CuttingCalculator)->giveBack([$batch], 2, []);

    expect($plan->changesStock())->toBeFalse()
        ->and($plan->nothingTaken)->toBeFalse();
});

it('reports "nothing taken" for cuts without materials, or when no cut covers the units', function () {
    $calculator = new CuttingCalculator;

    $empty = $calculator->giveBack([new CutBatch(1, 4, 0, [])], 3, []);

    expect($empty->nothingTaken)->toBeTrue()
        ->and($empty->returns)->toBe([])
        ->and($empty->batches)->toEqual([new BatchReturn(1, 3)])
        ->and($empty->unitsCovered)->toBe(3)
        ->and($empty->changesStock())->toBeFalse();

    $none = $calculator->giveBack([], 3, []);

    expect($none->nothingTaken)->toBeTrue()
        ->and($none->unitsCovered)->toBe(0)
        ->and($none->batches)->toBe([]);
});

it('returns nothing for units no cut covers, and skips cuts that are fully back', function () {
    $fullyBack = cuttingSingle(2, 4, '1.00000000', 4);
    $open = cuttingSingle(1, 3, '2.00000000', 1);

    $plan = (new CuttingCalculator)->giveBack([$fullyBack, $open], 5, [1 => '0.0000']);

    expect($plan->units)->toBe(5)
        ->and($plan->unitsCovered)->toBe(2)
        ->and($plan->batches)->toEqual([new BatchReturn(1, 2)])
        ->and($plan->returns[0]->returned)->toBe('4.0000');
});

it('needs a stock figure for every returnable material', function () {
    $calculator = new CuttingCalculator;

    expect(fn () => $calculator->giveBack([cuttingSingle(1, 2, '1.00000000')], 1, []))->toThrow(InvalidArgumentException::class);
});
