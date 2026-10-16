<?php

use App\Domain\Costing\MaterialRequirement;

require_once __DIR__.'/CostingTestSupport.php';

// F-41: the engine returns the summed need of every material (costing skill: required(m)).

/** @param list<MaterialRequirement> $requirements */
function costingRequirementRows(array $requirements): array
{
    return array_map(fn (MaterialRequirement $r): array => [
        $r->materialId, $r->materialName, $r->unit, $r->perUnit, $r->stockOnHand, $r->unitsPossible, $r->isBottleneck,
    ], $requirements);
}

it('returns the Denim Jacket requirements in order of first appearance (AC-1)', function () {
    $result = costingCalculate(costingDenimJacketLines(), '12.0000', '3.5000');

    expect($result->requirements)->toHaveCount(4)->each->toBeInstanceOf(MaterialRequirement::class)
        ->and(costingRequirementRows($result->requirements))->toBe([
            [1, 'Denim 12oz Indigo', 'm', '1.9800', '250.0000', 126, false],
            [2, 'Metal shank button 17mm', 'pc', '8.0000', '900.0000', 112, true],
            [3, 'Polyester thread', 'm', '126.0000', '20000.0000', 158, false],
            [4, 'Woven brand label', 'pc', '1.0000', '400.0000', 400, false],
        ])
        ->and($result->requirements[1]->perUnitExact)->toBe('8.00000000')   // 8.0000 pc at 0.00 %
        ->and($result->cost->unitCost)->toBe('35.7540')
        ->and($result->capacity->maxProducible)->toBe(112)
        ->and($result->capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm');
});

it('sums a material on two lines into one requirement (AC-2)', function (array $lines, string $perUnit, string $exact, int $units) {
    $requirements = costingCalculate($lines)->requirements;

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->perUnit)->toBe($perUnit)
        ->and($requirements[0]->perUnitExact)->toBe($exact)
        ->and($requirements[0]->unitsPossible)->toBe($units)
        ->and($requirements[0]->isBottleneck)->toBeTrue();
})->with([
    'button 4 + 4 pc, same as one 8-pc line' => [
        [costingLine(2, 'Button', '4.0000', '0.00', '0.35', '900.0000'), costingLine(2, 'Button', '4.0000', '0.00', '0.35', '900.0000')],
        '8.0000', '8.00000000', 112,
    ],
    'each line keeps its own waste' => [
        [costingLine(2, 'Button', '1.0000', '0.00', '1', '10.0000'), costingLine(2, 'Button', '1.0000', '10.00', '1', '10.0000')],
        '2.1000', '2.10000000', 4,
    ],
]);

it('keeps the unrounded need next to the 4-decimal one (AC-4)', function () {
    $requirement = costingCalculate([costingLine(1, 'Thread', '0.0001', '0.01', '1', '1.0000', 'm')])->requirements[0];

    expect($requirement->perUnit)->toBe('0.0001')
        ->and($requirement->perUnitExact)->toBe('0.00010001');
});

it('returns no requirements for an empty composition', function () {
    expect(costingCalculate([], '12.0000', '3.5000')->requirements)->toBe([]);
});
