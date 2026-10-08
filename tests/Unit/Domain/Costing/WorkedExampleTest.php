<?php

use App\Domain\Costing\CostBreakdown;
use App\Domain\Costing\CostingResult;
use App\Domain\Costing\LineCost;
use App\Domain\Costing\ProductionCapacity;

require_once __DIR__.'/CostingTestSupport.php';

// Golden test: the Denim Jacket of the invory-costing-domain skill (labor 12.0000, overhead 3.5000).

it('computes the Denim Jacket worked example exactly', function () {
    $result = costingCalculate(costingDenimJacketLines(), '12.0000', '3.5000');

    expect($result)->toBeInstanceOf(CostingResult::class)
        ->and($result->cost)->toBeInstanceOf(CostBreakdown::class)
        ->and($result->capacity)->toBeInstanceOf(ProductionCapacity::class);

    expect($result->cost->materialCost)->toBe('20.2540')
        ->and($result->cost->laborCost)->toBe('12.0000')
        ->and($result->cost->overheadCost)->toBe('3.5000')
        ->and($result->cost->unitCost)->toBe('35.7540');

    expect($result->capacity->maxProducible)->toBe(112)
        ->and($result->capacity->bottleneckMaterialId)->toBe(2)
        ->and($result->capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm');
});

it('returns one LineCost per input line, in input order, with exact per-line figures', function () {
    $lines = costingCalculate(costingDenimJacketLines(), '12.0000', '3.5000')->cost->lines;

    expect($lines)->toHaveCount(4)->each->toBeInstanceOf(LineCost::class);

    $actual = array_map(fn (LineCost $l): array => [
        'materialId' => $l->materialId,
        'materialName' => $l->materialName,
        'unit' => $l->unit,
        'quantity' => $l->quantity,
        'wastePercent' => $l->wastePercent,
        'effectiveQuantity' => $l->effectiveQuantity,
        'unitCost' => $l->unitCost,
        'lineCost' => $l->lineCost,
        'stockOnHand' => $l->stockOnHand,
        'unitsPossible' => $l->unitsPossible,
        'isBottleneck' => $l->isBottleneck,
    ], $lines);

    expect($actual)->toBe([
        [
            'materialId' => 1, 'materialName' => 'Denim 12oz Indigo', 'unit' => 'm',
            'quantity' => '1.8000', 'wastePercent' => '10.00', 'effectiveQuantity' => '1.9800',
            'unitCost' => '8.5000', 'lineCost' => '16.8300', 'stockOnHand' => '250.0000',
            'unitsPossible' => 126, 'isBottleneck' => false,
        ],
        [
            'materialId' => 2, 'materialName' => 'Metal shank button 17mm', 'unit' => 'pc',
            'quantity' => '8.0000', 'wastePercent' => '0.00', 'effectiveQuantity' => '8.0000',
            'unitCost' => '0.3500', 'lineCost' => '2.8000', 'stockOnHand' => '900.0000',
            'unitsPossible' => 112, 'isBottleneck' => true,
        ],
        [
            'materialId' => 3, 'materialName' => 'Polyester thread', 'unit' => 'm',
            'quantity' => '120.0000', 'wastePercent' => '5.00', 'effectiveQuantity' => '126.0000',
            'unitCost' => '0.0040', 'lineCost' => '0.5040', 'stockOnHand' => '20000.0000',
            'unitsPossible' => 158, 'isBottleneck' => false,
        ],
        [
            'materialId' => 4, 'materialName' => 'Woven brand label', 'unit' => 'pc',
            'quantity' => '1.0000', 'wastePercent' => '0.00', 'effectiveQuantity' => '1.0000',
            'unitCost' => '0.1200', 'lineCost' => '0.1200', 'stockOnHand' => '400.0000',
            'unitsPossible' => 400, 'isBottleneck' => false,
        ],
    ]);
});

it('flags only the bottleneck line as bottleneck', function () {
    $lines = costingCalculate(costingDenimJacketLines(), '12.0000', '3.5000')->cost->lines;

    $flagged = array_values(array_filter($lines, fn (LineCost $l): bool => $l->isBottleneck));

    expect($flagged)->toHaveCount(1)
        ->and($flagged[0]->materialName)->toBe('Metal shank button 17mm');
});

it('variant: button stock 7.0000 gives capacity 0 with the button as bottleneck', function () {
    $result = costingCalculate(costingDenimJacketLines('7.0000'), '12.0000', '3.5000');

    expect($result->capacity->maxProducible)->toBe(0)
        ->and($result->capacity->bottleneckMaterialId)->toBe(2)
        ->and($result->capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm');

    // Stock never changes the cost.
    expect($result->cost->unitCost)->toBe('35.7540');
});

it('variant: removing all lines leaves labor + overhead and no capacity', function () {
    $result = costingCalculate([], '12.0000', '3.5000');

    expect($result->cost->lines)->toBe([])
        ->and($result->cost->materialCost)->toBe('0.0000')
        ->and($result->cost->laborCost)->toBe('12.0000')
        ->and($result->cost->overheadCost)->toBe('3.5000')
        ->and($result->cost->unitCost)->toBe('15.5000')
        ->and($result->capacity->maxProducible)->toBeNull()
        ->and($result->capacity->bottleneckMaterialId)->toBeNull()
        ->and($result->capacity->bottleneckMaterialName)->toBeNull();
});
