<?php

use App\Domain\Sales\SaleLine;

it('holds a sale as given', function () {
    $line = new SaleLine(productId: 3, quantity: 12, unitPrice: '24.0000');

    expect($line->productId)->toBe(3)
        ->and($line->quantity)->toBe(12)
        ->and($line->unitPrice)->toBe('24.0000');
});

it('accepts the smallest and the largest valid sales', function (int $quantity, string $unitPrice) {
    expect((new SaleLine(1, $quantity, $unitPrice))->quantity)->toBe($quantity);
})->with([
    'one unit, free' => [1, '0'],
    'one unit, one minor unit' => [1, '0.0001'],
    'a million units at the largest price' => [1_000_000, '99999999.9999'],
]);

it('rejects a quantity below one', function (int $quantity) {
    expect(fn () => new SaleLine(1, $quantity, '10.0000'))
        ->toThrow(InvalidArgumentException::class, 'quantity');
})->with([
    'zero' => [0],
    'negative' => [-1],
    'very negative' => [-1_000_000],
]);

it('rejects a negative or malformed unit price', function (string $unitPrice) {
    expect(fn () => new SaleLine(1, 1, $unitPrice))
        ->toThrow(InvalidArgumentException::class, 'unitPrice');
})->with([
    'negative' => ['-0.0001'],
    'minus one' => ['-1'],
    'not a number' => ['abc'],
    'empty' => [''],
    'exponent' => ['1e3'],
    'comma decimal' => ['89,50'],
    'leading plus' => ['+89'],
    'padded' => ['89 '],
    'lone point' => ['.'],
]);
