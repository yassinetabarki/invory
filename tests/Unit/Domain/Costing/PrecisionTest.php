<?php

use App\Domain\Costing\LineCost;

require_once __DIR__.'/CostingTestSupport.php';

// ADR-0002: exact intermediate maths, rounding only on output (scale 4 HALF_UP, wastePercent scale 2).

it('sums exact line costs and rounds once, instead of summing rounded line costs (rounding down)', function () {
    // Each line is 0.0001 × 0.4000 = 0.00004 → "0.0000". Two of them are exactly 0.00008 → "0.0001".
    // A sum of rounded lines would wrongly give "0.0000".
    $result = costingCalculate([
        costingLine(1, 'A', quantity: '0.0001', unitCost: '0.4000'),
        costingLine(2, 'B', quantity: '0.0001', unitCost: '0.4000'),
    ]);

    expect($result->cost->lines[0]->lineCost)->toBe('0.0000')
        ->and($result->cost->lines[1]->lineCost)->toBe('0.0000')
        ->and($result->cost->materialCost)->toBe('0.0001')
        ->and($result->cost->unitCost)->toBe('0.0001');
});

it('sums exact line costs and rounds once, instead of summing rounded line costs (rounding up)', function () {
    // Each line is 0.00005 → HALF_UP "0.0001". Three of them are exactly 0.00015 → "0.0002".
    // A sum of rounded lines would wrongly give "0.0003".
    $result = costingCalculate([
        costingLine(1, 'A', quantity: '0.0001', unitCost: '0.5000'),
        costingLine(2, 'B', quantity: '0.0001', unitCost: '0.5000'),
        costingLine(3, 'C', quantity: '0.0001', unitCost: '0.5000'),
    ]);

    expect(array_map(fn (LineCost $l): string => $l->lineCost, $result->cost->lines))
        ->toBe(['0.0001', '0.0001', '0.0001'])
        ->and($result->cost->materialCost)->toBe('0.0002');
});

it('rounds the unit cost once from exact material cost, labor and overhead', function () {
    // material 0.00004 + labor 0.00004 + overhead 0 = 0.00008 → "0.0001",
    // while each part rounds to "0.0000" on its own.
    $result = costingCalculate([costingLine(quantity: '0.0001', unitCost: '0.4000')], '0.00004');

    expect($result->cost->materialCost)->toBe('0.0000')
        ->and($result->cost->laborCost)->toBe('0.0000')
        ->and($result->cost->overheadCost)->toBe('0.0000')
        ->and($result->cost->unitCost)->toBe('0.0001');
});

it('rounds HALF_UP at scale 4', function (string $quantity, string $unitCost, string $expected) {
    $line = costingCalculate([costingLine(quantity: $quantity, unitCost: $unitCost)])->cost->lines[0];

    expect($line->lineCost)->toBe($expected);
})->with([
    'exactly half rounds up' => ['0.0001', '0.5000', '0.0001'],
    'just below half rounds down' => ['0.0001', '0.4999', '0.0000'],
    'above half rounds up' => ['0.0001', '0.6000', '0.0001'],
    'no rounding needed' => ['2', '1.2500', '2.5000'],
]);

it('rounds the effective quantity HALF_UP at scale 4 from the exact value', function () {
    // 0.0003 × 1.5 = 0.00045 → 0.0005 (half up); 0.0001 × 1.05 = 0.000105 → 0.0001.
    $lines = costingCalculate([
        costingLine(1, 'A', quantity: '0.0003', waste: '50'),
        costingLine(2, 'B', quantity: '0.0001', waste: '5'),
    ])->cost->lines;

    expect($lines[0]->effectiveQuantity)->toBe('0.0005')
        ->and($lines[1]->effectiveQuantity)->toBe('0.0001');
});

it('computes the line cost from the exact effective quantity, not the rounded one', function () {
    // 0.0001 × 1.05 = 0.000105 (shown 0.0001); × 1000 = 0.105. A rounded 0.0001 would give 0.1000.
    $line = costingCalculate([costingLine(quantity: '0.0001', waste: '5', unitCost: '1000')])->cost->lines[0];

    expect($line->effectiveQuantity)->toBe('0.0001')
        ->and($line->lineCost)->toBe('0.1050');
});

it('always outputs decimals with 4 decimal places and the waste with 2, even for integer inputs', function () {
    $result = costingCalculate([
        costingLine(1, 'Button', unit: 'pc', quantity: '8', waste: '10', unitCost: '1', stock: '900'),
    ], '12', '3');

    $line = $result->cost->lines[0];

    expect($line->quantity)->toBe('8.0000')
        ->and($line->wastePercent)->toBe('10.00')
        ->and($line->effectiveQuantity)->toBe('8.8000')
        ->and($line->unitCost)->toBe('1.0000')
        ->and($line->lineCost)->toBe('8.8000')
        ->and($line->stockOnHand)->toBe('900.0000')
        ->and($result->cost->materialCost)->toBe('8.8000')
        ->and($result->cost->laborCost)->toBe('12.0000')
        ->and($result->cost->overheadCost)->toBe('3.0000')
        ->and($result->cost->unitCost)->toBe('23.8000');
});

it('keeps the unit, id and name of each line', function () {
    $line = costingCalculate([costingLine(42, 'Polyester thread', unit: 'cone')])->cost->lines[0];

    expect($line->materialId)->toBe(42)
        ->and($line->materialName)->toBe('Polyester thread')
        ->and($line->unit)->toBe('cone');
});

it('rounds the waste percentage HALF_UP at scale 2', function (string $waste, string $expected) {
    $line = costingCalculate([costingLine(waste: $waste)])->cost->lines[0];

    expect($line->wastePercent)->toBe($expected);
})->with([
    'integer' => ['10', '10.00'],
    'one decimal' => ['5.5', '5.50'],
    'zero' => ['0', '0.00'],
    'third decimal rounds half up' => ['0.005', '0.01'],
    'third decimal rounds down' => ['0.004', '0.00'],
]);

it('keeps money exact beyond what a float could represent', function () {
    // (10^8 − 10^-4) × (10^7 − 10^-4) = 999999999989000.00000001: needs 23 significant digits.
    $result = costingCalculate([
        costingLine(quantity: '9999999.9999', unitCost: '99999999.9999', stock: '9999999999.9999'),
    ]);

    expect($result->cost->lines[0]->lineCost)->toBe('999999999989000.0000')
        ->and($result->cost->materialCost)->toBe('999999999989000.0000');
});

it('does not suffer from binary floating point sums', function () {
    // 0.1 + 0.2 ≠ 0.3 in floats.
    $result = costingCalculate([
        costingLine(1, 'A', quantity: '1', unitCost: '0.1'),
        costingLine(2, 'B', quantity: '1', unitCost: '0.2'),
    ]);

    expect($result->cost->materialCost)->toBe('0.3000');
});
