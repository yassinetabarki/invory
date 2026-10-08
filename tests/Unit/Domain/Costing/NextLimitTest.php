<?php

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\ProductionCapacity;

require_once __DIR__.'/CostingTestSupport.php';

// Rules: invory-costing-domain skill, "Material usage and next limit".
//   next limit = capacity and bottleneck of the BOM with every line of the material removed.

/** @param list<BomLine> $lines */
function nextLimit(array $lines, int $materialId): ProductionCapacity
{
    return (new CostingCalculator)->capacityWithout($lines, $materialId);
}

/**
 * The 12 seeded materials of the UI-first plan: SKU => [id, name, unit, unit cost, stock on hand].
 * Ids follow the order of the plan's table.
 *
 * @return array<string, array{int, string, string, string, string}>
 */
function nextLimitMaterials(): array
{
    return [
        'MAT-RVT-009' => [1, 'Brass rivet 9mm', 'pc', '0.0800', '5000'],
        'MAT-CAN-010' => [2, 'Cotton canvas 10oz Natural', 'm', '5.4000', '300'],
        'MAT-JRS-180' => [3, 'Cotton jersey 180gsm White', 'm', '4.2000', '64'],
        'MAT-DEN-012' => [4, 'Denim 12oz Indigo', 'm', '8.5000', '250'],
        'MAT-BTN-017' => [5, 'Metal shank button 17mm', 'pc', '0.3500', '900'],
        'MAT-ZIP-018' => [6, 'Metal zipper 18cm', 'pc', '0.9500', '1250'],
        'MAT-LTH-NAP' => [7, 'Nappa leather Cognac', 'sqft', '6.8000', '540'],
        'MAT-THR-PES' => [8, 'Polyester thread', 'm', '0.0040', '20000'],
        'MAT-SOL-042' => [9, 'Rubber cupsole 42', 'pair', '5.9000', '0'],
        'MAT-LIN-VIS' => [10, 'Viscose lining Black', 'm', '3.1000', '180'],
        'MAT-LAC-120' => [11, 'Waxed cotton laces 120cm', 'pair', '0.4500', '600'],
        'MAT-LBL-WOV' => [12, 'Woven brand label', 'pc', '0.1200', '400'],
    ];
}

/**
 * Compositions of the plan's 7 products: product id => list of [SKU, quantity, waste %].
 *
 * @return array<int, list<array{string, string, string}>>
 */
function nextLimitCompositions(): array
{
    return [
        1 => [['MAT-DEN-012', '1.8000', '10.00'], ['MAT-BTN-017', '8', '0'], ['MAT-THR-PES', '120', '5.00'], ['MAT-LBL-WOV', '1', '0']],
        2 => [['MAT-JRS-180', '1.2000', '6.00'], ['MAT-THR-PES', '80', '5.00'], ['MAT-LBL-WOV', '1', '0']],
        3 => [['MAT-CAN-010', '0.6500', '10.00'], ['MAT-SOL-042', '1', '0'], ['MAT-LAC-120', '1', '0'], ['MAT-THR-PES', '40', '5.00'], ['MAT-LBL-WOV', '1', '0']],
        4 => [['MAT-LTH-NAP', '9.2000', '10.00'], ['MAT-LIN-VIS', '0.8000', '5.00'], ['MAT-ZIP-018', '1', '0'], ['MAT-RVT-009', '8', '0'], ['MAT-THR-PES', '150', '5.00'], ['MAT-LBL-WOV', '1', '0']],
        5 => [['MAT-DEN-012', '1.4000', '8.00'], ['MAT-BTN-017', '1', '0'], ['MAT-RVT-009', '6', '0'], ['MAT-ZIP-018', '1', '0'], ['MAT-THR-PES', '180', '5.00'], ['MAT-LBL-WOV', '1', '0']],
        6 => [['MAT-CAN-010', '2.1000', '10.00'], ['MAT-BTN-017', '5', '0'], ['MAT-THR-PES', '130', '5.00'], ['MAT-LBL-WOV', '1', '0']],
        7 => [['MAT-LTH-NAP', '1.2000', '15.00'], ['MAT-RVT-009', '4', '0']],
    ];
}

/** @return list<BomLine> */
function nextLimitProductLines(int $productId): array
{
    $materials = nextLimitMaterials();

    return array_map(
        function (array $line) use ($materials): BomLine {
            [$sku, $quantity, $waste] = $line;
            [$id, $name, $unit, $unitCost, $stock] = $materials[$sku];

            return new BomLine($id, $name, $unit, $quantity, $waste, $unitCost, $stock);
        },
        nextLimitCompositions()[$productId],
    );
}

function nextLimitMaterialId(string $sku): int
{
    return nextLimitMaterials()[$sku][0];
}

// --- worked example ---------------------------------------------------------------------------------------------

it('worked example: without the button the Denim Jacket is limited by the denim at 126', function () {
    $next = nextLimit(costingDenimJacketLines(), 2);

    expect($next)->toBeInstanceOf(ProductionCapacity::class)
        ->and($next->maxProducible)->toBe(126)
        ->and($next->bottleneckMaterialId)->toBe(1)
        ->and($next->bottleneckMaterialName)->toBe('Denim 12oz Indigo');
});

it('worked example: removing a material that is not the bottleneck leaves the capacity unchanged', function (int $materialId) {
    $next = nextLimit(costingDenimJacketLines(), $materialId);

    expect($next->maxProducible)->toBe(112)
        ->and($next->bottleneckMaterialId)->toBe(2)
        ->and($next->bottleneckMaterialName)->toBe('Metal shank button 17mm');
})->with([
    'denim' => [1],
    'thread' => [3],
    'label' => [4],
]);

it('worked example: with the button restocked the same BOM gives the next limit as its real capacity', function () {
    // The next limit says "limited by the denim at 126" once the button is no longer short.
    $restocked = costingCalculate(costingDenimJacketLines('100000.0000'));
    $next = nextLimit(costingDenimJacketLines(), 2);

    expect($restocked->capacity->maxProducible)->toBe($next->maxProducible)
        ->and($restocked->capacity->bottleneckMaterialId)->toBe($next->bottleneckMaterialId);
});

// --- the preview fixture of the plan (Expected table) -------------------------------------------------------------

it('matches the fixture products: cost, capacity, bottleneck and next limit of the plan', function (
    int $productId, string $labor, string $overhead, string $materialCost, string $unitCost,
    int $max, string $bottleneck, string $bottleneckSku, string $nextName, int $nextMax,
) {
    $lines = nextLimitProductLines($productId);
    $result = costingCalculate($lines, $labor, $overhead);

    expect($result->cost->materialCost)->toBe($materialCost)
        ->and($result->cost->unitCost)->toBe($unitCost)
        ->and($result->capacity->maxProducible)->toBe($max)
        ->and($result->capacity->bottleneckMaterialName)->toBe($bottleneck);

    $next = nextLimit($lines, nextLimitMaterialId($bottleneckSku));

    expect($next->bottleneckMaterialName)->toBe($nextName)
        ->and($next->maxProducible)->toBe($nextMax);
})->with([
    '1 Denim Jacket' => [1, '12.0000', '3.5000', '20.2540', '35.7540', 112, 'Metal shank button 17mm', 'MAT-BTN-017', 'Denim 12oz Indigo', 126],
    '2 T-shirt' => [2, '3.2000', '0.7500', '5.7984', '9.7484', 50, 'Cotton jersey 180gsm White', 'MAT-JRS-180', 'Polyester thread', 238],
    '3 Sneaker' => [3, '9.3000', '2.0000', '10.4990', '21.7990', 0, 'Rubber cupsole 42', 'MAT-SOL-042', 'Woven brand label', 400],
    '4 Tote' => [4, '22.5000', '4.7700', '73.7600', '101.0300', 53, 'Nappa leather Cognac', 'MAT-LTH-NAP', 'Polyester thread', 126],
    '5 Jeans' => [5, '11.0000', '2.9700', '15.5080', '29.4780', 105, 'Polyester thread', 'MAT-THR-PES', 'Denim 12oz Indigo', 165],
    '6 Chore Jacket' => [6, '14.5000', '3.0400', '14.8900', '32.4300', 129, 'Cotton canvas 10oz Natural', 'MAT-CAN-010', 'Polyester thread', 146],
    '7 Belt' => [7, '6.0000', '1.3400', '9.7040', '17.0440', 391, 'Nappa leather Cognac', 'MAT-LTH-NAP', 'Brass rivet 9mm', 1250],
]);

it('fixture: the Jeans without the thread are limited by the denim at 165', function () {
    $next = nextLimit(nextLimitProductLines(5), nextLimitMaterialId('MAT-THR-PES'));

    expect($next->maxProducible)->toBe(165)
        ->and($next->bottleneckMaterialId)->toBe(nextLimitMaterialId('MAT-DEN-012'))
        ->and($next->bottleneckMaterialName)->toBe('Denim 12oz Indigo');
});

it('fixture: the Belt without the nappa leather is limited by the brass rivets at 1250', function () {
    $next = nextLimit(nextLimitProductLines(7), nextLimitMaterialId('MAT-LTH-NAP'));

    expect($next->maxProducible)->toBe(1250)
        ->and($next->bottleneckMaterialId)->toBe(nextLimitMaterialId('MAT-RVT-009'))
        ->and($next->bottleneckMaterialName)->toBe('Brass rivet 9mm');
});

it('fixture: the Sneaker is out of production, its next limit is real (label, 400)', function () {
    $lines = nextLimitProductLines(3);

    expect(costingCalculate($lines)->capacity->maxProducible)->toBe(0);

    $next = nextLimit($lines, nextLimitMaterialId('MAT-SOL-042'));

    expect($next->maxProducible)->toBe(400)
        ->and($next->bottleneckMaterialName)->toBe('Woven brand label');
});

// --- edge cases ---------------------------------------------------------------------------------------------------

it('edge: a product whose only material is removed has no next limit', function () {
    $next = nextLimit([costingLine(1, 'Denim', quantity: '2', stock: '100')], 1);

    expect($next->maxProducible)->toBeNull()
        ->and($next->bottleneckMaterialId)->toBeNull()
        ->and($next->bottleneckMaterialName)->toBeNull();
});

it('edge: an empty BOM has no next limit, whatever material is asked for', function () {
    $next = nextLimit([], 1);

    expect($next->maxProducible)->toBeNull()
        ->and($next->bottleneckMaterialId)->toBeNull()
        ->and($next->bottleneckMaterialName)->toBeNull();
});

it('edge: a material that is not in the BOM changes nothing', function () {
    $lines = costingDenimJacketLines();
    $current = costingCalculate($lines)->capacity;
    $next = nextLimit($lines, 999);

    expect($next)->toEqual($current)
        ->and($next->maxProducible)->toBe(112);
});

it('edge: a material that is not in the BOM of an empty-stock product changes nothing either', function () {
    $lines = costingDenimJacketLines('0.0000');
    $next = nextLimit($lines, 999);

    expect($next)->toEqual(costingCalculate($lines)->capacity)
        ->and($next->maxProducible)->toBe(0);
});

it('edge: every line of the removed material is removed, not just the first', function () {
    // Material 1 sits on two lines (it limits capacity to ⌊10 / 7⌋ = 1). Material 2 alone allows ⌊50 / 2⌋ = 25.
    $lines = [
        costingLine(1, 'Denim', quantity: '3', stock: '10'),
        costingLine(2, 'Label', quantity: '2', stock: '50'),
        costingLine(1, 'Denim', quantity: '4', stock: '10'),
    ];

    expect(costingCalculate($lines)->capacity->maxProducible)->toBe(1);

    $next = nextLimit($lines, 1);

    expect($next->maxProducible)->toBe(25)
        ->and($next->bottleneckMaterialId)->toBe(2)
        ->and($next->bottleneckMaterialName)->toBe('Label');
});

it('edge: duplicate lines of a remaining material are still aggregated before dividing', function () {
    // Removing material 3 leaves Denim on two lines: ⌊100 / (3 + 4)⌋ = 14, not min(⌊100/3⌋, ⌊100/4⌋) = 25.
    $lines = [
        costingLine(1, 'Denim', quantity: '3', stock: '100'),
        costingLine(3, 'Thread', quantity: '1', stock: '5'),
        costingLine(1, 'Denim', quantity: '4', stock: '100'),
        costingLine(2, 'Label', quantity: '1', stock: '500'),
    ];

    $next = nextLimit($lines, 3);

    expect($next->maxProducible)->toBe(14)
        ->and($next->bottleneckMaterialId)->toBe(1);
});

it('edge: waste of the remaining lines is part of the requirement', function () {
    // 1.8 m + 10 % = 1.98 m per unit: ⌊250 / 1.98⌋ = 126. The button (removed) would have limited to 3.
    $lines = [
        costingLine(1, 'Denim', quantity: '1.8', waste: '10', stock: '250'),
        costingLine(2, 'Button', quantity: '8', stock: '24'),
    ];

    $next = nextLimit($lines, 2);

    expect($next->maxProducible)->toBe(126)
        ->and($next->bottleneckMaterialName)->toBe('Denim');
});

it('edge: a remaining material without stock gives a next limit of 0', function () {
    $lines = [
        costingLine(1, 'Button', quantity: '8', stock: '24'),
        costingLine(2, 'Sole', quantity: '1', stock: '0'),
    ];

    // The sole already is the bottleneck (0); removing the button changes nothing.
    $next = nextLimit($lines, 1);

    expect($next->maxProducible)->toBe(0)
        ->and($next->bottleneckMaterialId)->toBe(2);
});

it('edge: a tie at the bottleneck keeps the same capacity once the tie-break winner is removed', function () {
    // Alpha and Beta both allow 10 units; Alpha wins the tie (lowest name), Gamma allows 100.
    $lines = [
        costingLine(7, 'Alpha', quantity: '1', stock: '10'),
        costingLine(3, 'Beta', quantity: '2', stock: '20'),
        costingLine(5, 'Gamma', quantity: '1', stock: '100'),
    ];

    $current = costingCalculate($lines)->capacity;

    expect($current->maxProducible)->toBe(10)
        ->and($current->bottleneckMaterialName)->toBe('Alpha');

    $next = nextLimit($lines, 7);

    expect($next->maxProducible)->toBe(10)
        ->and($next->bottleneckMaterialId)->toBe(3)
        ->and($next->bottleneckMaterialName)->toBe('Beta');

    // Removing the other tied material leaves the winner as it was.
    $other = nextLimit($lines, 3);

    expect($other->maxProducible)->toBe(10)
        ->and($other->bottleneckMaterialName)->toBe('Alpha');
});

it('edge: ties among the remaining materials use the lowest name', function () {
    // Material 1 (5 units) is removed; Alpha, Zeta and Mid all allow 30 units.
    $lines = [
        costingLine(1, 'Removed', quantity: '1', stock: '5'),
        costingLine(2, 'Zeta', quantity: '1', stock: '30'),
        costingLine(3, 'Mid', quantity: '2', stock: '60'),
        costingLine(4, 'Alpha', quantity: '1', stock: '30'),
    ];

    $next = nextLimit($lines, 1);

    expect($next->maxProducible)->toBe(30)
        ->and($next->bottleneckMaterialId)->toBe(4)
        ->and($next->bottleneckMaterialName)->toBe('Alpha');
});

it('edge: ties among the remaining materials with the same name use the lowest id', function () {
    $lines = [
        costingLine(1, 'Removed', quantity: '1', stock: '5'),
        costingLine(9, 'Same name', quantity: '1', stock: '30'),
        costingLine(4, 'Same name', quantity: '1', stock: '30'),
        costingLine(6, 'Same name', quantity: '2', stock: '60'),
    ];

    $next = nextLimit($lines, 1);

    expect($next->maxProducible)->toBe(30)
        ->and($next->bottleneckMaterialId)->toBe(4);
});

it('edge: it does not change the input lines or the result of calculate()', function () {
    $lines = costingDenimJacketLines();
    $before = costingCalculate($lines, '12', '3.5');

    nextLimit($lines, 2);

    expect($lines)->toHaveCount(4)
        ->and(costingCalculate($lines, '12', '3.5'))->toEqual($before);
});

it('edge: keyed or sparse input arrays are handled like lists', function () {
    $lines = costingDenimJacketLines();
    unset($lines[0]);

    // Without the denim line (key 0 gone) and without the button: thread 158 vs label 400.
    $next = nextLimit($lines, 2);

    expect($next->maxProducible)->toBe(158)
        ->and($next->bottleneckMaterialName)->toBe('Polyester thread');
});

it('rejects an input that is not made of BomLine objects', function (mixed $notALine) {
    $call = fn () => (new CostingCalculator)->capacityWithout([costingLine(1, 'Denim'), $notALine], 1);

    expect($call)->toThrow(InvalidArgumentException::class, BomLine::class);
})->with([
    'array' => [['materialId' => 2]],
    'stdClass' => [new stdClass],
    'null' => [null],
    'string' => ['line'],
]);
