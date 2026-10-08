<?php

use App\Domain\Costing\BomLine;

require_once __DIR__.'/CostingTestSupport.php';

it('exposes its constructor arguments as public readonly properties', function () {
    $line = new BomLine(7, 'Denim', 'm', '1.8000', '10.00', '8.5000', '250.0000');

    expect($line->materialId)->toBe(7)
        ->and($line->materialName)->toBe('Denim')
        ->and($line->unit)->toBe('m')
        ->and($line->quantity)->toBe('1.8000')
        ->and($line->wastePercent)->toBe('10.00')
        ->and($line->unitCost)->toBe('8.5000')
        ->and($line->stockOnHand)->toBe('250.0000');

    expect(fn () => $line->quantity = '2')->toThrow(Error::class);
});

it('accepts boundary values', function (string $quantity, string $waste, string $unitCost, string $stock) {
    $line = costingLine(quantity: $quantity, waste: $waste, unitCost: $unitCost, stock: $stock);

    expect($line)->toBeInstanceOf(BomLine::class);
})->with([
    'smallest quantity' => ['0.0001', '0', '0', '0'],
    'integers' => ['8', '0', '1', '900'],
    'largest waste below 100' => ['1', '99.99', '1', '1'],
    'zero unit cost and zero stock' => ['1', '0.00', '0.0000', '0.0000'],
    'max stock' => ['1', '0', '1', '9999999999.9999'],
]);

it('rejects a value that is not a plain decimal number', function (string $field, string $value) {
    $args = ['quantity' => '1', 'waste' => '0', 'unitCost' => '1', 'stock' => '1'];
    $args[$field] = $value;

    $field = match ($field) {
        'waste' => 'wastePercent',
        'stock' => 'stockOnHand',
        default => $field,
    };

    expect(fn () => costingLine(...$args))
        ->toThrow(InvalidArgumentException::class, $field);
})->with(function () {
    foreach (['quantity', 'waste', 'unitCost', 'stock'] as $field) {
        foreach (['empty' => '', 'letters' => 'abc', 'exponent' => '1e3', 'fraction' => '1/2', 'comma' => '1,5',
            'padded' => ' 1', 'trailing newline' => "1\n", 'plus sign' => '+1', 'leading point' => '.5',
            'trailing point' => '1.', 'double sign' => '--1', 'two points' => '1.2.3'] as $label => $value) {
            yield "$field: $label" => [$field, $value];
        }
    }
});

it('rejects out-of-range values', function (array $args, string $field) {
    expect(fn () => costingLine(...$args))
        ->toThrow(InvalidArgumentException::class, $field);
})->with([
    'zero quantity' => [['quantity' => '0'], 'quantity'],
    'zero quantity with decimals' => [['quantity' => '0.0000'], 'quantity'],
    'negative quantity' => [['quantity' => '-1'], 'quantity'],
    'negative waste' => [['waste' => '-0.01'], 'wastePercent'],
    'waste of exactly 100' => [['waste' => '100'], 'wastePercent'],
    'waste of 100.00' => [['waste' => '100.00'], 'wastePercent'],
    'waste above 100' => [['waste' => '150'], 'wastePercent'],
    'negative unit cost' => [['unitCost' => '-0.0001'], 'unitCost'],
    'negative stock' => [['stock' => '-1'], 'stockOnHand'],
]);
