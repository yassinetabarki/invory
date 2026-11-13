<?php

use App\Domain\Costing\ColourCapacity;
use App\Domain\Costing\VariantCosting;
use App\Domain\Costing\VariantLine;

// ADR-0029 appendix F / the costing skill's "Worked example per colour of a material": Denim 12oz kept per colour, Indigo 250 m at
// 8.5000 and Black 40 m at 9.2000 (cost per colour); the Denim Jacket in Indigo (10) and Black (20), sizes S (1) M (2) L (3) XL (4);
// labor 12.0000, overhead 3.5000, selling price 89.0000. Button, thread and label are one stock, as in the skill.

const COLOUR_STOCK_INDIGO = 10;
const COLOUR_STOCK_BLACK = 20;
const COLOUR_STOCK_LIGHT_WASH = 30;

/**
 * @param  array<int, string>  $stock  by colour id
 * @param  array<int, string>  $costs  by colour id; empty = the same cost for every colour
 * @return list<VariantLine>
 */
function colourStockLines(?string $denimQuantity, bool $bySize, array $stock, array $costs): array
{
    $sizes = [1 => '1.6000', 2 => '1.8000', 3 => '2.0000', 4 => '2.2000'];
    $cells = $bySize ? [COLOUR_STOCK_INDIGO => $sizes, COLOUR_STOCK_BLACK => $sizes] : null;

    return [
        new VariantLine(1, 'Denim 12oz', 'm', $bySize ? null : $denimQuantity, $cells, '10.00', '8.5000', '290.0000', $stock, $costs, $costs !== []),
        new VariantLine(2, 'Metal shank button 17mm', 'pc', '8.0000', null, '0.00', '0.3500', '900.0000'),
        new VariantLine(3, 'Polyester thread', 'm', '120.0000', null, '5.00', '0.0040', '20000.0000'),
        new VariantLine(4, 'Woven brand label', 'pc', '1.0000', null, '0.00', '0.1200', '400.0000'),
    ];
}

/** @return list<array{0: int, 1: int}> */
function colourStockVariants(int ...$colourIds): array
{
    $variants = [];

    foreach ($colourIds as $colourId) {
        foreach ([1, 2, 3, 4] as $sizeId) {
            $variants[] = [$colourId, $sizeId];
        }
    }

    return $variants;
}

function colourStockCalculate(array $lines, array $variants)
{
    return (new VariantCosting)->calculate($lines, $variants, '12.0000', '3.5000', '89.0000');
}

it('reads each colour of a material for stock and cost (ADR-0029 appendix F)', function () {
    $stock = [COLOUR_STOCK_INDIGO => '250.0000', COLOUR_STOCK_BLACK => '40.0000'];
    $costs = [COLOUR_STOCK_INDIGO => '8.5000', COLOUR_STOCK_BLACK => '9.2000'];
    $variants = colourStockVariants(COLOUR_STOCK_INDIGO, COLOUR_STOCK_BLACK);

    $result = colourStockCalculate(colourStockLines(null, true, $stock, $costs), $variants);

    $rows = array_map(fn ($v): array => [$v->colourId, $v->sizeId, $v->maxProducible, $v->bottleneckMaterialName, $v->bottleneckColourId, $v->bottleneckMissing], $result->variants);

    expect($result->perColour)->toBeTrue()
        ->and($rows)->toBe([
            [10, 1, 112, 'Metal shank button 17mm', null, false],
            [10, 2, 112, 'Metal shank button 17mm', null, false],
            [10, 3, 112, 'Metal shank button 17mm', null, false],
            [10, 4, 103, 'Denim 12oz', 10, false],
            [20, 1, 22, 'Denim 12oz', 20, false],
            [20, 2, 20, 'Denim 12oz', 20, false],
            [20, 3, 18, 'Denim 12oz', 20, false],
            [20, 4, 16, 'Denim 12oz', 20, false],
        ])
        // Black M costs 37.1400 (Black's price), Indigo M 35.7540.
        ->and($result->variants[5]->unitCost)->toBe('37.1400')
        ->and($result->variants[5]->margin->amount)->toBe('51.8600')
        ->and($result->variants[5]->margin->percent)->toBe('58.27')
        ->and($result->variants[1]->unitCost)->toBe('35.7540')
        ->and([$result->unitCostMin, $result->unitCostMax])->toBe(['33.8840', '41.1880'])
        // The denim covers Indigo 103-142 and Black 16-22 units (the button, thread and label are one stock: no colours).
        ->and($result->materials[1]->byColour)->toBe([
            10 => ['min' => 103, 'max' => 142, 'missing' => false],
            20 => ['min' => 16, 'max' => 22, 'missing' => false],
        ])
        ->and($result->materials[2]->byColour)->toBe([])
        ->and($result->colours)->toEqual([
            new ColourCapacity(10, 103, 112, 4, 1, 'Denim 12oz', 10, false),
            new ColourCapacity(20, 16, 22, 4, 1, 'Denim 12oz', 20, false),
        ])
        ->and([$result->guaranteed, $result->bindingColourId, $result->bindingBottleneckColourId, $result->bindingBottleneckMissing])->toBe([16, 20, 20, false])
        ->and([$result->best, $result->bestColourId, $result->bestBottleneckColourId])->toBe([112, 10, null]);

    // Black raised to 250 m: Black S goes from 22 to 112 (the button limits), and Black's stock covers 142 of them.
    $raised = colourStockCalculate(colourStockLines(null, true, array_replace($stock, [COLOUR_STOCK_BLACK => '250.0000']), $costs), $variants);

    expect($raised->variants[4]->maxProducible)->toBe(112)
        ->and($raised->variants[4]->bottleneckMaterialName)->toBe('Metal shank button 17mm')
        ->and($raised->materials[1]->byColour[COLOUR_STOCK_BLACK])->toBe(['min' => 103, 'max' => 142, 'missing' => false]);

    // A "Same for every variant" line of 1.8 m (1.98 with waste): Indigo 126, Black 20, one number each, and the jacket is limited to 20.
    $same = colourStockCalculate(colourStockLines('1.8000', false, $stock, []), $variants);

    expect($same->varies)->toBeFalse()
        ->and($same->perColour)->toBeTrue()
        ->and($same->materials[1]->byColour)->toBe([
            10 => ['min' => 126, 'max' => 126, 'missing' => false],
            20 => ['min' => 20, 'max' => 20, 'missing' => false],
        ])
        ->and([$same->guaranteed, $same->bindingColourId, $same->bindingMaterialName, $same->bindingBottleneckColourId])->toBe([20, 20, 'Denim 12oz', 20])
        ->and($same->best)->toBe(112)
        // The single costing is the binding variant's (Black), with Black's stock.
        ->and($same->single->capacity->maxProducible)->toBe(20);
});

it('handles a product colour the material lacks, and a material kept as one stock', function (?array $stock, array $costs, array $expected) {
    $variants = [[COLOUR_STOCK_INDIGO, 2], [COLOUR_STOCK_BLACK, 2], [COLOUR_STOCK_LIGHT_WASH, 2]];
    $lines = colourStockLines('1.8000', false, $stock ?? [], $costs);

    if ($stock === null) {
        $lines[0] = new VariantLine(1, 'Denim 12oz', 'm', '1.8000', null, '10.00', '8.5000', '250.0000');
    }

    $result = colourStockCalculate($lines, $variants);
    $light = $result->variants[2];

    expect([$light->maxProducible, $light->bottleneckMaterialName, $light->bottleneckColourId, $light->bottleneckMissing])->toBe($expected['light'])
        ->and($light->unitCost)->toBe($expected['lightUnitCost'])
        ->and($light->margin?->amount)->toBe($expected['lightMargin'])
        ->and([$result->guaranteed, $result->best])->toBe($expected['capacity'])
        ->and([$result->unitCostMin, $result->unitCostMax])->toBe($expected['unitCost'])
        // No variant with a unit cost: the material cost range is null too, not zero.
        ->and($result->materialCostMin === null)->toBe($expected['unitCost'][0] === null)
        ->and([$result->marginAmountMin, $result->marginAmountMax])->toBe($expected['margin'])
        ->and($result->colours[2])->toEqual(new ColourCapacity(30, $expected['light'][0], $expected['light'][0], 2, $expected['lightMaterialId'], $expected['light'][1], $expected['light'][2], $expected['light'][3]))
        ->and($result->materials[1]->byColour[30] ?? null)->toBe($expected['span'])
        ->and($result->perColour)->toBe($stock !== null);
})->with([
    'colour missing, same cost' => [
        ['10' => '250.0000', '20' => '40.0000'],
        [],
        [
            'light' => [0, 'Denim 12oz', 30, true],
            'lightMaterialId' => 1,
            'lightUnitCost' => '35.7540',
            'lightMargin' => '53.2460',
            'capacity' => [0, 112],
            'unitCost' => ['35.7540', '35.7540'],
            'margin' => ['53.2460', '53.2460'],
            'span' => ['min' => 0, 'max' => 0, 'missing' => true],
        ],
    ],
    'colour missing, cost per colour' => [
        ['10' => '250.0000', '20' => '40.0000'],
        ['10' => '8.5000', '20' => '9.2000'],
        [
            'light' => [0, 'Denim 12oz', 30, true],
            'lightMaterialId' => 1,
            'lightUnitCost' => null,
            'lightMargin' => null,
            'capacity' => [0, 112],
            'unitCost' => ['35.7540', '37.1400'],
            'margin' => ['51.8600', '53.2460'],
            'span' => ['min' => 0, 'max' => 0, 'missing' => true],
        ],
    ],
    'every colour missing, cost per colour' => [
        ['99' => '250.0000'],
        ['99' => '8.5000'],
        [
            'light' => [0, 'Denim 12oz', 30, true],
            'lightMaterialId' => 1,
            'lightUnitCost' => null,
            'lightMargin' => null,
            'capacity' => [0, 0],
            'unitCost' => [null, null],
            'margin' => [null, null],
            'span' => ['min' => 0, 'max' => 0, 'missing' => true],
        ],
    ],
    'every material one stock' => [
        null,
        [],
        [
            'light' => [112, 'Metal shank button 17mm', null, false],
            'lightMaterialId' => 2,
            'lightUnitCost' => '35.7540',
            'lightMargin' => '53.2460',
            'capacity' => [112, 112],
            'unitCost' => ['35.7540', '35.7540'],
            'margin' => ['53.2460', '53.2460'],
            'span' => null,
        ],
    ],
]);
