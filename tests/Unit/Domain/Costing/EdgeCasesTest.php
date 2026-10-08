<?php

use App\Domain\Costing\LineCost;

require_once __DIR__.'/CostingTestSupport.php';

// One named test per row of the edge-case table in the invory-costing-domain skill.

it('edge: an empty BOM costs labor + overhead and has no capacity', function () {
    $result = costingCalculate([], '2', '0.5');

    expect($result->cost->lines)->toBe([])
        ->and($result->cost->materialCost)->toBe('0.0000')
        ->and($result->cost->unitCost)->toBe('2.5000')
        ->and($result->capacity->maxProducible)->toBeNull()
        ->and($result->capacity->bottleneckMaterialId)->toBeNull()
        ->and($result->capacity->bottleneckMaterialName)->toBeNull();
});

it('edge: an empty BOM with no labor or overhead costs zero', function () {
    $result = costingCalculate([]);

    expect($result->cost->unitCost)->toBe('0.0000')
        ->and($result->cost->laborCost)->toBe('0.0000')
        ->and($result->cost->overheadCost)->toBe('0.0000');
});

it('edge: a required material with zero stock caps capacity at 0 and is the bottleneck', function () {
    $result = costingCalculate([
        costingLine(1, 'Denim', quantity: '2', stock: '100'),
        costingLine(2, 'Zip', quantity: '1', stock: '0'),
        costingLine(3, 'Label', quantity: '1', stock: '50'),
    ]);

    expect($result->capacity->maxProducible)->toBe(0)
        ->and($result->capacity->bottleneckMaterialId)->toBe(2)
        ->and($result->capacity->bottleneckMaterialName)->toBe('Zip');
});

it('edge: stock smaller than one unit\'s need gives 0, including waste', function (string $stock, int $expected) {
    // 1.8 m + 10 % waste = 1.98 m per unit.
    $result = costingCalculate([costingLine(1, 'Denim', quantity: '1.8', waste: '10', stock: $stock)]);

    expect($result->capacity->maxProducible)->toBe($expected)
        ->and($result->capacity->bottleneckMaterialId)->toBe(1);
})->with([
    'just below one unit' => ['1.9799', 0],
    'exactly one unit' => ['1.9800', 1],
    'just below two units' => ['3.9599', 1],
    'exactly two units' => ['3.9600', 2],
]);

it('edge: the same material on two lines sums its requirements before dividing', function () {
    // Per-line division would give min(⌊10/3⌋, ⌊10/4⌋, 5) = 2. Aggregated: ⌊10/(3+4)⌋ = 1.
    $result = costingCalculate([
        costingLine(1, 'Denim', quantity: '3', stock: '10'),
        costingLine(2, 'Label', quantity: '1', stock: '5'),
        costingLine(1, 'Denim', quantity: '4', stock: '10'),
    ]);

    expect($result->capacity->maxProducible)->toBe(1)
        ->and($result->capacity->bottleneckMaterialId)->toBe(1)
        ->and($result->capacity->bottleneckMaterialName)->toBe('Denim');

    $lines = $result->cost->lines;

    expect($lines)->toHaveCount(3)
        ->and(array_map(fn (LineCost $l): int => $l->materialId, $lines))->toBe([1, 2, 1])
        ->and(array_map(fn (LineCost $l): int => $l->unitsPossible, $lines))->toBe([1, 5, 1])
        ->and(array_map(fn (LineCost $l): bool => $l->isBottleneck, $lines))->toBe([true, false, true]);
});

it('edge: aggregated requirements include each line\'s own waste, and costs stay per line', function () {
    // 3 × 1.10 + 4 × 1.25 = 3.3 + 5 = 8.3 → ⌊100 / 8.3⌋ = 12 (⌊100/3.3⌋ = 30 and ⌊100/5⌋ = 20 per line).
    $result = costingCalculate([
        costingLine(1, 'Denim', quantity: '3', waste: '10', unitCost: '2', stock: '100'),
        costingLine(1, 'Denim', quantity: '4', waste: '25', unitCost: '2', stock: '100'),
    ]);

    expect($result->capacity->maxProducible)->toBe(12)
        ->and($result->cost->lines[0]->lineCost)->toBe('6.6000')
        ->and($result->cost->lines[1]->lineCost)->toBe('10.0000')
        ->and($result->cost->materialCost)->toBe('16.6000');
});

it('edge: duplicate material lines take the stock of the first line', function () {
    $result = costingCalculate([
        costingLine(1, 'Denim', quantity: '3', stock: '10'),
        costingLine(1, 'Denim', quantity: '4', stock: '999'),
    ]);

    // ⌊10 / 7⌋ = 1 (the second line's 999 is ignored), while 999 / 7 would give 142.
    expect($result->capacity->maxProducible)->toBe(1)
        ->and($result->cost->lines[0]->stockOnHand)->toBe('10.0000')
        ->and($result->cost->lines[1]->stockOnHand)->toBe('999.0000');
});

it('edge: waste of 0 leaves the effective quantity equal to the quantity', function () {
    $line = costingCalculate([costingLine(quantity: '2.5', waste: '0')])->cost->lines[0];

    expect($line->effectiveQuantity)->toBe('2.5000')
        ->and($line->quantity)->toBe('2.5000')
        ->and($line->wastePercent)->toBe('0.00');
});

it('edge: very small quantities lose no precision', function () {
    // 0.0001 m of thread at 0.0040 per m: line cost 0.0000004, far below the scale-4 resolution.
    $result = costingCalculate([
        costingLine(1, 'Thread', unit: 'm', quantity: '0.0001', waste: '0', unitCost: '0.0040', stock: '20000'),
    ]);

    expect($result->cost->lines[0]->effectiveQuantity)->toBe('0.0001')
        ->and($result->cost->lines[0]->lineCost)->toBe('0.0000')
        ->and($result->capacity->maxProducible)->toBe(200000000);
});

it('edge: capacity is computed from the exact effective quantity, not the rounded one', function () {
    // 0.0001 × 1.05 = 0.000105 (displayed as 0.0001). ⌊1 / 0.000105⌋ = 9523, whereas ⌊1 / 0.0001⌋ would be 10000.
    $result = costingCalculate([costingLine(quantity: '0.0001', waste: '5', stock: '1')]);

    expect($result->cost->lines[0]->effectiveQuantity)->toBe('0.0001')
        ->and($result->capacity->maxProducible)->toBe(9523);
});

it('edge: very large capacity fits in a PHP int and a JS safe integer', function () {
    $result = costingCalculate([costingLine(quantity: '0.0001', stock: '1000000')]);

    expect($result->capacity->maxProducible)->toBe(10_000_000_000);
});

it('edge: the extreme of decimal(14,4) stock over the minimum quantity stays an exact integer', function () {
    $result = costingCalculate([costingLine(quantity: '0.0001', stock: '9999999999.9999')]);

    expect($result->capacity->maxProducible)->toBe(99_999_999_999_999)
        ->and($result->capacity->maxProducible)->toBeLessThanOrEqual(9_007_199_254_740_991); // JS Number.MAX_SAFE_INTEGER
});

it('edge: ties for the bottleneck go to the lowest name, in any input order', function (array $order) {
    $materials = [
        'zipper' => costingLine(1, 'Zipper', stock: '10'),
        'button' => costingLine(2, 'Button', stock: '10'),
        'label' => costingLine(3, 'Label', stock: '10'),
    ];

    $result = costingCalculate(array_map(fn (string $k) => $materials[$k], $order));

    expect($result->capacity->maxProducible)->toBe(10)
        ->and($result->capacity->bottleneckMaterialName)->toBe('Button')
        ->and($result->capacity->bottleneckMaterialId)->toBe(2);
})->with([
    'zipper, button, label' => [['zipper', 'button', 'label']],
    'button, label, zipper' => [['button', 'label', 'zipper']],
    'label, zipper, button' => [['label', 'zipper', 'button']],
    'zipper, label, button' => [['zipper', 'label', 'button']],
]);

it('edge: name ties compare bytes, so uppercase sorts before lowercase', function () {
    $result = costingCalculate([
        costingLine(1, 'button', stock: '10'),
        costingLine(2, 'Zip', stock: '10'),
    ]);

    expect($result->capacity->bottleneckMaterialName)->toBe('Zip')
        ->and($result->capacity->bottleneckMaterialId)->toBe(2);
});

it('edge: ties on units and name go to the lowest id, in any input order', function (array $ids) {
    $result = costingCalculate(array_map(fn (int $id) => costingLine($id, 'Thread', stock: '10'), $ids));

    $flagged = array_values(array_filter($result->cost->lines, fn (LineCost $l): bool => $l->isBottleneck));

    expect($result->capacity->bottleneckMaterialId)->toBe(3)
        ->and($flagged)->toHaveCount(1)
        ->and($flagged[0]->materialId)->toBe(3);
})->with([
    'ascending' => [[3, 5, 9]],
    'descending' => [[9, 5, 3]],
    'shuffled' => [[5, 3, 9]],
]);

it('edge: the smallest capacity wins over name and id', function () {
    $result = costingCalculate([
        costingLine(1, 'Aaa', stock: '50'),
        costingLine(9, 'Zzz', stock: '2'),
    ]);

    expect($result->capacity->maxProducible)->toBe(2)
        ->and($result->capacity->bottleneckMaterialId)->toBe(9);
});

it('edge: a material with unit cost 0 adds no cost but still limits capacity', function () {
    $result = costingCalculate([
        costingLine(1, 'Free sample fabric', quantity: '2', unitCost: '0', stock: '5'),
        costingLine(2, 'Label', quantity: '1', unitCost: '2', stock: '100'),
    ]);

    expect($result->cost->lines[0]->lineCost)->toBe('0.0000')
        ->and($result->cost->materialCost)->toBe('2.0000')
        ->and($result->capacity->maxProducible)->toBe(2)
        ->and($result->capacity->bottleneckMaterialId)->toBe(1);
});

it('edge: capacity is floored, never rounded up', function () {
    // 900 / 8 = 112.5 and 0.9999 / 1 = 0.9999.
    expect(costingCalculate([costingLine(quantity: '8', stock: '900')])->capacity->maxProducible)->toBe(112)
        ->and(costingCalculate([costingLine(quantity: '1', stock: '0.9999')])->capacity->maxProducible)->toBe(0);
});

// Row "archived / draft products still calculated": product status does not exist in the domain
// (the calculator only sees BomLine[]), so there is nothing to test here. The ProductCostingService adapter
// (S-04) is where that row gets its test.

it('gives the same cost and capacity whatever the line order, only the output order follows the input', function () {
    $lines = costingDenimJacketLines();
    $reversed = array_reverse($lines);

    $a = costingCalculate($lines, '12', '3.5');
    $b = costingCalculate($reversed, '12', '3.5');

    expect($b->cost->unitCost)->toBe($a->cost->unitCost)
        ->and($b->capacity)->toEqual($a->capacity)
        ->and(array_map(fn (LineCost $l): int => $l->materialId, $b->cost->lines))->toBe([4, 3, 2, 1]);
});
