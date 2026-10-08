<?php

use App\Domain\Costing\BomLine;
use App\Domain\Costing\CostingCalculator;
use App\Domain\Costing\CostingResult;

/*
|--------------------------------------------------------------------------
| Shared builders for the costing domain tests
|--------------------------------------------------------------------------
|
| Loaded with require_once by each test file in this folder (this file is not a *Test.php,
| so PHPUnit never runs it). tests/Pest.php is owned by another story, hence the local file.
| All names are prefixed with "costing" to stay clear of other global helpers.
|
*/

function costingLine(
    int $id = 1,
    string $name = 'Material',
    string $quantity = '1',
    string $waste = '0',
    string $unitCost = '1',
    string $stock = '100',
    string $unit = 'pc',
): BomLine {
    return new BomLine($id, $name, $unit, $quantity, $waste, $unitCost, $stock);
}

/**
 * The Denim Jacket BOM of the worked example in the invory-costing-domain skill.
 *
 * @return list<BomLine>
 */
function costingDenimJacketLines(string $buttonStock = '900.0000'): array
{
    return [
        new BomLine(1, 'Denim 12oz Indigo', 'm', '1.8000', '10.00', '8.5000', '250.0000'),
        new BomLine(2, 'Metal shank button 17mm', 'pc', '8.0000', '0.00', '0.3500', $buttonStock),
        new BomLine(3, 'Polyester thread', 'm', '120.0000', '5.00', '0.0040', '20000.0000'),
        new BomLine(4, 'Woven brand label', 'pc', '1.0000', '0.00', '0.1200', '400.0000'),
    ];
}

/** @param list<BomLine> $lines */
function costingCalculate(array $lines, string $labor = '0', string $overhead = '0'): CostingResult
{
    return (new CostingCalculator)->calculate($lines, $labor, $overhead);
}
