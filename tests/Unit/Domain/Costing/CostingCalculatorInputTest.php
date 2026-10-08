<?php

use App\Domain\Costing\CostingCalculator;

require_once __DIR__.'/CostingTestSupport.php';

it('rejects invalid labor and overhead', function (string $labor, string $overhead, string $field) {
    expect(fn () => (new CostingCalculator)->calculate([costingLine()], $labor, $overhead))
        ->toThrow(InvalidArgumentException::class, $field);
})->with([
    'negative labor' => ['-1', '0', 'laborCost'],
    'tiny negative labor' => ['-0.0001', '0', 'laborCost'],
    'non-numeric labor' => ['abc', '0', 'laborCost'],
    'empty labor' => ['', '0', 'laborCost'],
    'exponent labor' => ['1e2', '0', 'laborCost'],
    'negative overhead' => ['0', '-1', 'overheadCost'],
    'non-numeric overhead' => ['0', 'twelve', 'overheadCost'],
    'empty overhead' => ['0', '', 'overheadCost'],
    'fraction overhead' => ['0', '1/2', 'overheadCost'],
]);

it('rejects invalid labor even when the BOM is empty', function () {
    expect(fn () => (new CostingCalculator)->calculate([], '-1'))
        ->toThrow(InvalidArgumentException::class, 'laborCost');
});

it('accepts zero and decimal labor and overhead', function () {
    $result = costingCalculate([], '0', '0.0000');

    expect($result->cost->unitCost)->toBe('0.0000');
});

it('rejects an element that is not a BomLine', function (mixed $element) {
    // The phpdoc says list<BomLine>; this is the runtime guard for callers that break it.
    expect(fn () => (new CostingCalculator)->calculate([costingLine(), $element]))
        ->toThrow(InvalidArgumentException::class, 'BomLine');
})->with([
    'string' => ['not a line'],
    'array' => [['materialId' => 1]],
    'null' => [null],
    'plain object' => [new stdClass],
]);
