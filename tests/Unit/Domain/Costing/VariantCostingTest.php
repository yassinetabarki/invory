<?php

use App\Domain\Costing\ColourCapacity;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\MaterialSpan;
use App\Domain\Costing\VariantCosting;
use App\Domain\Costing\VariantLine;
use Brick\Math\BigDecimal;

// Golden test: ADR-0027 appendix I. Denim Jacket in Indigo (10) and Black (20), sizes S (1), M (2), L (3), XL (4);
// labor 12.0000, overhead 3.5000, selling price 89.0000.

const GOLDEN_INDIGO = 10;
const GOLDEN_BLACK = 20;

/** @return list<VariantLine> */
function goldenVariantLines(): array
{
    $sizes = [1 => '1.6000', 2 => '1.8000', 3 => '2.0000', 4 => '2.2000'];

    return [
        new VariantLine(1, 'Denim 12oz Indigo', 'm', null, [GOLDEN_INDIGO => $sizes], '10.00', '8.5000', '250.0000'),
        new VariantLine(5, 'Denim 12oz Black', 'm', null, [GOLDEN_BLACK => $sizes], '10.00', '8.5000', '40.0000'),
        new VariantLine(2, 'Metal shank button 17mm', 'pc', '8.0000', null, '0.00', '0.3500', '900.0000'),
        new VariantLine(3, 'Polyester thread', 'm', '120.0000', null, '5.00', '0.0040', '20000.0000'),
        new VariantLine(4, 'Woven brand label', 'pc', '1.0000', null, '0.00', '0.1200', '400.0000'),
    ];
}

/** @return list<array{0: int, 1: int}> */
function goldenVariants(): array
{
    $variants = [];

    foreach ([GOLDEN_INDIGO, GOLDEN_BLACK] as $colourId) {
        foreach ([1, 2, 3, 4] as $sizeId) {
            $variants[] = [$colourId, $sizeId];
        }
    }

    return $variants;
}

it('costs the eight variants of ADR-0027 appendix I exactly', function () {
    $result = (new VariantCosting)->calculate(goldenVariantLines(), goldenVariants(), '12.0000', '3.5000', '89.0000');

    $rows = array_map(fn ($v): array => [
        $v->colourId, $v->sizeId, $v->unitCost, $v->maxProducible, $v->bottleneckMaterialName,
    ], $result->variants);

    expect($result->varies)->toBeTrue()
        ->and($rows)->toBe([
            [10, 1, '33.8840', 112, 'Metal shank button 17mm'],
            [10, 2, '35.7540', 112, 'Metal shank button 17mm'],
            [10, 3, '37.6240', 112, 'Metal shank button 17mm'],
            [10, 4, '39.4940', 103, 'Denim 12oz Indigo'],
            [20, 1, '33.8840', 22, 'Denim 12oz Black'],
            [20, 2, '35.7540', 20, 'Denim 12oz Black'],
            [20, 3, '37.6240', 18, 'Denim 12oz Black'],
            [20, 4, '39.4940', 16, 'Denim 12oz Black'],
        ])
        ->and([$result->unitCostMin, $result->unitCostMax])->toBe(['33.8840', '39.4940'])
        ->and([$result->materialCostMin, $result->materialCostMax])->toBe(['18.3840', '23.9940'])
        ->and([$result->marginAmountMin, $result->marginAmountMax])->toBe(['49.5060', '55.1160'])
        ->and([$result->marginPercentMin, $result->marginPercentMax])->toBe(['55.62', '61.93']);

    // Indigo M matches the skill's main example exactly (margin 53.2460, 59.83).
    expect($result->variants[1]->margin->amount)->toBe('53.2460')
        ->and($result->variants[1]->margin->percent)->toBe('59.83');

    expect($result->colours)->toEqual([
        new ColourCapacity(10, 103, 112, 4, 1, 'Denim 12oz Indigo'),
        new ColourCapacity(20, 16, 22, 4, 5, 'Denim 12oz Black'),
    ]);

    expect($result->guaranteed)->toBe(16)
        ->and($result->best)->toBe(112)
        ->and([$result->bindingColourId, $result->bindingSizeId, $result->bindingMaterialId, $result->bindingMaterialName])
        ->toBe([20, 4, 5, 'Denim 12oz Black'])
        ->and([$result->bestColourId, $result->bestSizeId, $result->bestMaterialId, $result->bestMaterialName])
        ->toBe([10, 1, 2, 'Metal shank button 17mm'])
        // single = the binding variant (Black XL).
        ->and($result->single->cost->unitCost)->toBe('39.4940')
        ->and($result->single->capacity->maxProducible)->toBe(16);
});

it('gives each material its lowest and highest need and units covered over the variants that use it', function () {
    $materials = (new VariantCosting)->calculate(goldenVariantLines(), goldenVariants(), '12.0000', '3.5000', '89.0000')->materials;

    // Composition order, one entry per material.
    expect(array_keys($materials))->toBe([1, 5, 2, 3, 4])
        ->and($materials[5])->toEqual(new MaterialSpan(5, 'Denim 12oz Black', 'm', '1.7600', '2.4200', 16, 22))
        ->and($materials[1])->toEqual(new MaterialSpan(1, 'Denim 12oz Indigo', 'm', '1.7600', '2.4200', 103, 142))
        ->and($materials[2])->toEqual(new MaterialSpan(2, 'Metal shank button 17mm', 'pc', '8.0000', '8.0000', 112, 112));
});

// The per-variant figures are worked out without CostingCalculator::calculate() (it is ~0.4 ms a call, 220 times a product at the cap):
// this pins them to it. Every composition is costed by the calculator itself, line by line, and compared. Memoising equal compositions
// (also when written "2.0000", "2.00" and "2") is proven by the same comparison and by the load test's budget, not by object identity.
it('gives every variant the cost, capacity and bottleneck CostingCalculator gives its composition', function (Closure $build) {
    [$lines, $variants, $bottlenecks] = $build();

    $result = (new VariantCosting)->calculate($lines, $variants, '12.0000', '3.5000', null);
    $calculator = new CostingCalculator;

    $actual = [];
    $expected = [];
    $units = [];
    $needs = [];

    foreach ($result->variants as $variant) {
        $bom = [];

        foreach ($lines as $line) {
            $quantity = $line->quantityFor($variant->colourId, $variant->sizeId);

            if ($quantity !== null) {
                $bom[] = $line->bomLine($quantity);
            }
        }

        $costed = $calculator->calculate($bom, '12.0000', '3.5000');

        foreach ($costed->requirements as $requirement) {
            $units[$requirement->materialId][] = $requirement->unitsPossible;
            $needs[$requirement->materialId][] = BigDecimal::of($requirement->perUnit);
        }

        $actual[] = [$variant->materialCost, $variant->unitCost, $variant->maxProducible, $variant->bottleneckMaterialId, $variant->bottleneckMaterialName];
        $expected[] = [
            $costed->cost->materialCost, $costed->cost->unitCost, $costed->capacity->maxProducible,
            $costed->capacity->bottleneckMaterialId, $costed->capacity->bottleneckMaterialName,
        ];
    }

    expect($actual)->toBe($expected);

    // The span of each material is the lowest and highest of the calculator's own requirements.
    $spans = array_map(fn (MaterialSpan $span): array => [$span->neededMin, $span->neededMax, $span->unitsCoveredMin, $span->unitsCoveredMax], $result->materials);
    $expectedSpans = [];

    foreach ($lines as $line) {
        $expectedSpans[$line->materialId] ??= [
            (string) BigDecimal::min(...$needs[$line->materialId]), (string) BigDecimal::max(...$needs[$line->materialId]),
            min($units[$line->materialId]), max($units[$line->materialId]),
        ];
    }

    expect($spans)->toBe($expectedSpans);

    if ($bottlenecks !== null) {
        expect(array_column($actual, 3))->toBe($bottlenecks);
    }
})->with([
    'ADR-0027 appendix I' => [fn (): array => [goldenVariantLines(), goldenVariants(), null]],
    // Material 1 is on two lines: where both apply their needs add up before the division. Colour 3 has only the second line.
    // Quantities are the same value written differently ("1.5", "1.50", "1.5000").
    'a material on two lines' => [fn (): array => [
        [
            new VariantLine(1, 'Cotton', 'm', null, [1 => [1 => '1.5', 2 => '1.50'], 2 => [1 => '1.5000', 2 => '4']], '10.00', '2.3333', '100.0000'),
            new VariantLine(2, 'Lining', 'm', '0.7000', null, '2.50', '1.1111', '31.0000'),
            new VariantLine(1, 'Cotton', 'm', '0.5000', null, '0.00', '2.3333', '100.0000'),
        ],
        [[1, 1], [1, 2], [2, 1], [2, 2], [3, 1], [3, 2]],
        null,
    ]],
    // Units possible tie at 10: decided by name ("Button" before "Zip", byte order), then by id (3 before 9). Size 2 of colour 1 makes Zip alone the limit.
    'a tie on units possible, decided by name and then by id' => [fn (): array => [
        [
            new VariantLine(7, 'Zip', 'pc', null, [1 => [1 => '10', 2 => '20'], 2 => [1 => '10', 2 => '10']], '0.00', '0.2500', '100.0000'),
            new VariantLine(9, 'Button', 'pc', '5.0000', null, '0.00', '0.3500', '50.0000'),
            new VariantLine(3, 'Button', 'pc', '2.0000', null, '0.00', '0.1000', '20.0000'),
        ],
        [[1, 1], [1, 2], [2, 1], [2, 2]],
        [3, 7, 3, 3],
    ]],
    // Lines costing 0.00004 each: rounded line by line they are 0, rounded once 0.0001. Every line is by colour and size, so colour 3
    // (no cell) has an empty composition: material cost 0.0000, no capacity.
    'tiny quantities, an empty composition' => [fn (): array => [
        [
            new VariantLine(1, 'Thread', 'm', null, [1 => [1 => '0.0001', 2 => '0.0001'], 2 => [1 => '0.0001', 2 => '0.00010']], '0.00', '0.4000', '1000000.0000'),
            new VariantLine(2, 'Bead', 'pc', null, [1 => [1 => '0.0001', 2 => '0.0001'], 2 => [1 => '0.0001']], '3.33', '0.4000', '1000000.0000'),
            new VariantLine(3, 'Dye', 'l', null, [1 => [1 => '0.0001', 2 => '0.0003'], 2 => [2 => '0.0001']], '99.99', '0.4000', '999999.9999'),
        ],
        [[1, 1], [1, 2], [2, 1], [2, 2], [3, 1]],
        null,
    ]],
]);
