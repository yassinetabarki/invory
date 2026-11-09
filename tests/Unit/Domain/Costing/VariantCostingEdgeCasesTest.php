<?php

use App\Domain\Costing\ColourCapacity;
use App\Domain\Costing\VariantCosting;
use App\Domain\Costing\VariantLine;

require_once __DIR__.'/CostingTestSupport.php';

/** A by-colour-and-size line with no waste, a unit cost of 8.5000 and the given stock. */
function edgeCellsLine(int $id, string $name, array $cells, string $stock = '250.0000', string $waste = '10.00'): VariantLine
{
    return new VariantLine($id, $name, 'm', null, $cells, $waste, '8.5000', $stock);
}

it('gives every variant the skill figures when no line varies', function () {
    $lines = array_map(
        fn ($l): VariantLine => new VariantLine($l->materialId, $l->materialName, $l->unit, $l->quantity, null, $l->wastePercent, $l->unitCost, $l->stockOnHand),
        costingDenimJacketLines(),
    );

    $result = (new VariantCosting)->calculate($lines, [[1, 1], [1, 2], [2, 1], [2, 2]], '12.0000', '3.5000', '89.0000');

    expect($result->varies)->toBeFalse()
        ->and([$result->guaranteed, $result->best])->toBe([112, 112])
        ->and([$result->bindingColourId, $result->bindingSizeId, $result->bindingMaterialName])->toBe([1, 1, 'Metal shank button 17mm'])
        ->and([$result->bestColourId, $result->bestSizeId, $result->bestMaterialName])->toBe([1, 1, 'Metal shank button 17mm'])
        ->and([$result->unitCostMin, $result->unitCostMax])->toBe(['35.7540', '35.7540'])
        ->and([$result->materialCostMin, $result->materialCostMax])->toBe(['20.2540', '20.2540'])
        ->and([$result->marginAmountMin, $result->marginAmountMax])->toBe(['53.2460', '53.2460'])
        ->and([$result->marginPercentMin, $result->marginPercentMax])->toBe(['59.83', '59.83'])
        ->and($result->single->cost->unitCost)->toBe('35.7540')
        ->and($result->single->requirements)->toHaveCount(4)
        ->and([$result->variants[3]->unitCost, $result->variants[3]->maxProducible])->toBe(['35.7540', 112])
        ->and($result->colours)->toEqual([
            new ColourCapacity(1, 112, 112, 1, 2, 'Metal shank button 17mm'),
            new ColourCapacity(2, 112, 112, 1, 2, 'Metal shank button 17mm'),
        ])
        ->and(array_keys($result->materials))->toBe([1, 2, 3, 4])
        ->and($result->materials[1]->unitsCoveredMin)->toBe(126)
        ->and($result->materials[1]->unitsCoveredMax)->toBe(126);
});

it('leaves a colour with no composition out of the capacity but keeps its cost in the range', function () {
    // Only by-colour-and-size lines: colour 30 has no cell, so no line at all.
    $lines = [
        edgeCellsLine(1, 'Denim 12oz Indigo', [10 => [1 => '1.6000', 2 => '1.8000']]),
        edgeCellsLine(5, 'Denim 12oz Black', [20 => [1 => '1.6000', 2 => '1.8000']], '40.0000'),
    ];
    $variants = [[10, 1], [10, 2], [20, 1], [20, 2], [30, 1], [30, 2]];

    $result = (new VariantCosting)->calculate($lines, $variants, '12.0000', '3.5000', '89.0000');

    expect($result->colours)->toEqual([
        new ColourCapacity(10, 126, 142, 2, 1, 'Denim 12oz Indigo'),
        new ColourCapacity(20, 20, 22, 2, 5, 'Denim 12oz Black'),
        new ColourCapacity(30, null, null, null, null, null),
    ])
        ->and($result->variants[4]->maxProducible)->toBeNull()
        ->and([$result->guaranteed, $result->best])->toBe([20, 142])
        ->and([$result->bindingColourId, $result->bindingSizeId, $result->bindingMaterialId])->toBe([20, 2, 5])
        ->and([$result->bestColourId, $result->bestSizeId, $result->bestMaterialId])->toBe([10, 1, 1])
        ->and([$result->unitCostMin, $result->unitCostMax])->toBe(['15.5000', '32.3300'])
        ->and([$result->materialCostMin, $result->materialCostMax])->toBe(['0.0000', '16.8300'])
        ->and([$result->marginAmountMin, $result->marginAmountMax])->toBe(['56.6700', '73.5000'])
        ->and([$result->marginPercentMin, $result->marginPercentMax])->toBe(['63.67', '82.58']);
});

it('reads 0 for a colour whose material is out of stock while the best stays', function () {
    $lines = [
        edgeCellsLine(1, 'Denim 12oz Indigo', [10 => [1 => '1.6000', 2 => '1.8000']]),
        edgeCellsLine(5, 'Denim 12oz Black', [20 => [1 => '1.6000', 2 => '1.8000']], '0.0000'),
        new VariantLine(2, 'Metal shank button 17mm', 'pc', '8.0000', null, '0.00', '0.3500', '900.0000'),
    ];

    $result = (new VariantCosting)->calculate($lines, [[10, 1], [10, 2], [20, 1], [20, 2]], '12.0000', '3.5000', null);

    expect([$result->guaranteed, $result->best])->toBe([0, 112])
        ->and([$result->bindingColourId, $result->bindingSizeId, $result->bindingMaterialName])->toBe([20, 1, 'Denim 12oz Black'])
        ->and([$result->bestColourId, $result->bestSizeId, $result->bestMaterialName])->toBe([10, 1, 'Metal shank button 17mm'])
        ->and($result->colours)->toEqual([
            new ColourCapacity(10, 112, 112, 1, 2, 'Metal shank button 17mm'),
            new ColourCapacity(20, 0, 0, 1, 5, 'Denim 12oz Black'),
        ]);
});

it('breaks ties by the order the variants are given: colour order, then size order', function () {
    // 100 in stock: a 10 gives 10 units, a 20 gives 5.
    $lines = [edgeCellsLine(1, 'Denim', [
        1 => [1 => '10', 2 => '20'],
        2 => [1 => '20', 2 => '10'],
        3 => [1 => '10', 2 => '10'],
    ], '100.0000', '0.00')];

    $result = (new VariantCosting)->calculate($lines, [[1, 1], [1, 2], [2, 1], [2, 2], [3, 1], [3, 2]], '0', '0', null);

    expect([$result->guaranteed, $result->best])->toBe([5, 10])
        ->and([$result->bindingColourId, $result->bindingSizeId])->toBe([1, 2])
        ->and([$result->bestColourId, $result->bestSizeId])->toBe([1, 1])
        ->and(array_map(fn (ColourCapacity $c): ?int => $c->bindingSizeId, $result->colours))->toBe([2, 1, 1]);

    // The same variants listed with colour 2 first: it wins both ties.
    $reordered = (new VariantCosting)->calculate($lines, [[2, 1], [2, 2], [1, 1], [1, 2]], '0', '0', null);

    expect([$reordered->bindingColourId, $reordered->bindingSizeId])->toBe([2, 1])
        ->and([$reordered->bestColourId, $reordered->bestSizeId])->toBe([2, 2]);
});

it('reads margins by selling price', function (?string $sellingPrice, ?string $amount, ?string $percent) {
    $lines = [new VariantLine(1, 'Cotton', 'm', '1.0000', null, '0.00', '10.0000', '100.0000')];

    $result = (new VariantCosting)->calculate($lines, [[1, 1]], '5.0000', '0.0000', $sellingPrice);

    expect($result->unitCostMax)->toBe('15.0000')
        ->and([$result->marginAmountMin, $result->marginAmountMax])->toBe([$amount, $amount])
        ->and([$result->marginPercentMin, $result->marginPercentMax])->toBe([$percent, $percent])
        ->and($result->variants[0]->margin?->amount)->toBe($amount);
})->with([
    'no selling price' => [null, null, null],
    'selling price 0' => ['0.0000', '-15.0000', null],
]);

it('refuses input the maths cannot read', function (Closure $call) {
    expect($call)->toThrow(InvalidArgumentException::class);
})->with([
    'a quantity and cells' => [fn () => new VariantLine(1, 'A', 'm', '1', [1 => [1 => '1']], '0', '1', '1')],
    'neither' => [fn () => new VariantLine(1, 'A', 'm', null, null, '0', '1', '1')],
    'no cell' => [fn () => new VariantLine(1, 'A', 'm', null, [], '0', '1', '1')],
    'a cell at 0' => [fn () => new VariantLine(1, 'A', 'm', null, [1 => [1 => '0']], '0', '1', '1')],
    'a quantity at 0' => [fn () => new VariantLine(1, 'A', 'm', '0', null, '0', '1', '1')],
    'no variant' => [fn () => (new VariantCosting)->calculate([], [], '0', '0', null)],
    'a line that is not a VariantLine' => [fn () => (new VariantCosting)->calculate([costingLine()], [[1, 1]], '0', '0', null)],
]);
