<?php

use App\Domain\Costing\Margin;
use App\Domain\Costing\MarginCalculator;

// Rules: invory-costing-domain skill, "Selling price and margin".
//   margin = selling_price - unit_cost            (scale 4, may be negative)
//   percent = margin / selling_price * 100        (scale 2, HalfUp; null when the selling price is 0)
//   selling_price = null -> no margin at all

function marginOf(?string $sellingPrice, string $unitCost): ?Margin
{
    return (new MarginCalculator)->calculate($sellingPrice, $unitCost);
}

it('computes the worked example: Denim Jacket at 89.0000 with a unit cost of 35.7540', function () {
    $margin = marginOf('89.0000', '35.7540');

    expect($margin)->toBeInstanceOf(Margin::class)
        ->and($margin->amount)->toBe('53.2460')
        ->and($margin->percent)->toBe('59.83');
});

it('matches every priced product of the preview fixture (Expected table of the plan)', function (string $sellingPrice, string $unitCost, string $amount, string $percent) {
    $margin = marginOf($sellingPrice, $unitCost);

    expect($margin->amount)->toBe($amount)
        ->and($margin->percent)->toBe($percent);
})->with([
    '1 Denim Jacket' => ['89.0000', '35.7540', '53.2460', '59.83'],
    '2 Classic White T-shirt' => ['24.0000', '9.7484', '14.2516', '59.38'],
    '3 Canvas Low Sneaker' => ['69.0000', '21.7990', '47.2010', '68.41'],
    '4 Leather Tote' => ['249.0000', '101.0300', '147.9700', '59.43'],
    '5 Denim Jeans 5-pocket' => ['79.0000', '29.4780', '49.5220', '62.69'],
    '7 Leather Belt' => ['45.0000', '17.0440', '27.9560', '62.12'],
]);

it('has no margin when the product has no selling price (Canvas Chore Jacket, draft)', function () {
    expect(marginOf(null, '32.4300'))->toBeNull();
});

it('has no margin for a null price, whatever the unit cost is', function (string $unitCost) {
    expect(marginOf(null, $unitCost))->toBeNull();
})->with(['zero' => ['0'], 'scale 4' => ['0.0001'], 'large' => ['99999999.9999']]);

it('gives the negative unit cost and no percent when the selling price is zero', function () {
    $margin = marginOf('0.0000', '35.7540');

    expect($margin->amount)->toBe('-35.7540')
        ->and($margin->percent)->toBeNull();
});

it('gives a zero margin and no percent when both the price and the cost are zero', function () {
    $margin = marginOf('0', '0');

    expect($margin->amount)->toBe('0.0000')
        ->and($margin->percent)->toBeNull();
});

it('gives a zero margin and 0.00 % when the price equals the unit cost', function () {
    $margin = marginOf('35.7540', '35.7540');

    expect($margin->amount)->toBe('0.0000')
        ->and($margin->percent)->toBe('0.00');
});

it('gives a 100.00 % margin for a free product (unit cost 0)', function () {
    $margin = marginOf('12.5000', '0.0000');

    expect($margin->amount)->toBe('12.5000')
        ->and($margin->percent)->toBe('100.00');
});

it('gives a negative margin when the price is below the unit cost', function () {
    // 30 - 35.7540 = -5.7540; -5.754 / 30 * 100 = -19.18
    $margin = marginOf('30.0000', '35.7540');

    expect($margin->amount)->toBe('-5.7540')
        ->and($margin->percent)->toBe('-19.18');
});

it('gives a margin below -100 % when the product sells for less than half its cost', function () {
    // 10 - 35 = -25; -25 / 10 * 100 = -250
    $margin = marginOf('10', '35');

    expect($margin->amount)->toBe('-25.0000')
        ->and($margin->percent)->toBe('-250.00');
});

it('rounds the percent half-up at the third decimal', function (string $sellingPrice, string $unitCost, string $percent) {
    expect(marginOf($sellingPrice, $unitCost)->percent)->toBe($percent);
})->with([
    // margin / 200 * 100 = margin / 2
    'tie 0.005 rounds up (HalfEven would give 0.00)' => ['200', '199.9900', '0.01'],
    'tie 0.015 rounds up' => ['200', '199.9700', '0.02'],
    'tie 0.025 rounds up (HalfEven would give 0.02)' => ['200', '199.9500', '0.03'],
    'just below the tie rounds down' => ['200', '199.9901', '0.00'],
    'just above the tie rounds up' => ['200', '199.9899', '0.01'],
    // margin / 8 * 100 = margin * 12.5: 0.0001 -> 0.00125 (down), 0.0004 -> 0.005 (tie, up), 0.0007 -> 0.00875 (up)
    'third decimal 1 rounds down' => ['8', '7.9999', '0.00'],
    'tie at the third decimal rounds up' => ['8', '7.9996', '0.01'],
    'third decimal 8 rounds up' => ['8', '7.9993', '0.01'],
    // 1 / 16 * 100 = 6.25 exactly; 1 / 32 * 100 = 3.125 (tie) -> 3.13
    'exact two decimals is kept' => ['16', '15', '6.25'],
    'tie at 3.125 rounds up' => ['32', '31', '3.13'],
    // 1 / 7 * 100 = 14.2857... -> 14.29 ; 2 / 3 * 100 = 66.6666... -> 66.67
    'repeating decimal rounds up' => ['7', '6', '14.29'],
    'two thirds' => ['3', '1', '66.67'],
    'one third rounds down' => ['3', '2', '33.33'],
]);

it('rounds a negative tie away from zero, like every other half-up rounding in the domain', function () {
    // (200 - 200.0100) / 200 * 100 = -0.005 -> -0.01
    $margin = marginOf('200', '200.0100');

    expect($margin->amount)->toBe('-0.0100')
        ->and($margin->percent)->toBe('-0.01');
});

it('computes the percent from the exact margin, not from the rounded amount', function () {
    // Unit costs may carry more than 4 decimals when they come from an exact BOM sum.
    // Exact margin 0.0004 - 0.000349 = 0.000051 -> amount "0.0001"; percent 0.000051 / 0.0004 * 100 = 12.75
    // (from the rounded amount it would be 0.0001 / 0.0004 * 100 = 25.00).
    $margin = marginOf('0.0004', '0.000349');

    expect($margin->amount)->toBe('0.0001')
        ->and($margin->percent)->toBe('12.75');
});

it('rounds the amount to scale 4, half-up', function (string $sellingPrice, string $unitCost, string $amount) {
    expect(marginOf($sellingPrice, $unitCost)->amount)->toBe($amount);
})->with([
    'scale 0 inputs' => ['89', '35', '54.0000'],
    'scale 2 inputs' => ['89.5', '35.25', '54.2500'],
    'five decimals, tie rounds up' => ['1', '0.99995', '0.0001'],
    'five decimals, below the tie' => ['1', '0.99996', '0.0000'],
    'negative tie rounds away from zero' => ['0.99995', '1', '-0.0001'],
]);

it('handles large amounts without precision loss', function () {
    $margin = marginOf('99999999.9999', '0.0001');

    expect($margin->amount)->toBe('99999999.9998')
        ->and($margin->percent)->toBe('100.00');
});

it('handles tiny prices without precision loss', function () {
    // 0.0003 - 0.0001 = 0.0002; 0.0002 / 0.0003 * 100 = 66.666... -> 66.67
    $margin = marginOf('0.0003', '0.0001');

    expect($margin->amount)->toBe('0.0002')
        ->and($margin->percent)->toBe('66.67');
});

it('rejects a malformed or negative selling price', function (string $sellingPrice) {
    expect(fn () => marginOf($sellingPrice, '10'))->toThrow(InvalidArgumentException::class, 'sellingPrice');
})->with([
    'negative' => ['-1'],
    'negative scale 4' => ['-0.0001'],
    'not a number' => ['abc'],
    'empty' => [''],
    'exponent' => ['1e3'],
    'comma decimal' => ['89,50'],
    'leading plus' => ['+89'],
    'padded' => [' 89'],
    'lone point' => ['.'],
]);

it('rejects a malformed or negative unit cost', function (string $unitCost) {
    expect(fn () => marginOf('89', $unitCost))->toThrow(InvalidArgumentException::class, 'unitCost');
})->with([
    'negative' => ['-0.0001'],
    'not a number' => ['abc'],
    'empty' => [''],
    'exponent' => ['1e3'],
]);

it('validates the unit cost even when there is no selling price to compare with', function () {
    expect(fn () => marginOf(null, 'abc'))->toThrow(InvalidArgumentException::class, 'unitCost');
});
