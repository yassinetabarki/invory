<?php

use App\Domain\Sales\SalesCalculator;

// Rule: sale_total = quantity x unit_price, scale 4 (invory-costing-domain skill, "Sales").

function salesLineTotal(int $quantity, string $unitPrice): string
{
    return (new SalesCalculator)->lineTotal($quantity, $unitPrice);
}

it('computes the total of a sale: 4 x 89.0000 = 356.0000', function () {
    expect(salesLineTotal(4, '89.0000'))->toBe('356.0000');
});

it('returns a scale-4 string whatever the scale of the price', function (int $quantity, string $unitPrice, string $total) {
    expect(salesLineTotal($quantity, $unitPrice))->toBe($total);
})->with([
    'scale 0' => [4, '89', '356.0000'],
    'scale 1' => [3, '0.5', '1.5000'],
    'scale 2' => [12, '24.50', '294.0000'],
    'scale 4' => [7, '0.0001', '0.0007'],
    'zero price' => [5, '0', '0.0000'],
    'zero price, scale 4' => [5, '0.0000', '0.0000'],
    'one unit' => [1, '249.0000', '249.0000'],
]);

it('is exact: no float artifacts', function () {
    // 3 x 0.1 is 0.30000000000000004 with floats.
    expect(salesLineTotal(3, '0.1'))->toBe('0.3000')
        ->and(salesLineTotal(7, '0.1'))->toBe('0.7000')
        ->and(salesLineTotal(1_000_000, '0.0001'))->toBe('100.0000');
});

it('handles the largest sale without precision loss', function () {
    expect(salesLineTotal(1_000_000, '99999999.9999'))->toBe('99999999999900.0000');
});

it('rounds half-up when the price has more than 4 decimals', function (int $quantity, string $unitPrice, string $total) {
    expect(salesLineTotal($quantity, $unitPrice))->toBe($total);
})->with([
    'tie rounds up' => [1, '0.00005', '0.0001'],
    'tie after multiplying' => [3, '0.00005', '0.0002'],
    'below the tie' => [1, '0.00004', '0.0000'],
]);

it('matches the fixture sale prices (ids 60, 59, 58, 57, 56)', function () {
    expect(salesLineTotal(6, '79.0000'))->toBe('474.0000')   // 60: Jeans
        ->and(salesLineTotal(20, '24.0000'))->toBe('480.0000') // 59: T-shirt
        ->and(salesLineTotal(2, '249.0000'))->toBe('498.0000') // 58: Tote
        ->and(salesLineTotal(4, '89.0000'))->toBe('356.0000')  // 57: Denim Jacket
        ->and(salesLineTotal(3, '45.0000'))->toBe('135.0000'); // 56: Belt
});

it('rejects a quantity below one', function (int $quantity) {
    expect(fn () => salesLineTotal($quantity, '10.0000'))
        ->toThrow(InvalidArgumentException::class, 'quantity');
})->with([
    'zero' => [0],
    'negative' => [-4],
]);

it('rejects a negative or malformed unit price', function (string $unitPrice) {
    expect(fn () => salesLineTotal(1, $unitPrice))
        ->toThrow(InvalidArgumentException::class, 'unitPrice');
})->with([
    'negative' => ['-0.01'],
    'not a number' => ['abc'],
    'empty' => [''],
    'exponent' => ['1e2'],
    'comma decimal' => ['10,5'],
]);
