<?php

use App\Domain\Barcodes\VariantNames;

// INV-8-b: the one rule behind every sentence that lists variants or SKUs (costing skill "Variant lists" and "SKU lists").

/** A colour with its sizes, for VariantNames::list(). */
function variantColour(string $name, string ...$sizes): array
{
    return ['colour' => $name, 'sizes' => array_values($sizes)];
}

it('writes lists of variants, one variant and lists of SKUs', function (string $expected, Closure $write) {
    expect($write())->toBe($expected);
})->with([
    'one colour' => ['Light wash S, M, L and XL', fn () => VariantNames::list([variantColour('Light wash', 'S', 'M', 'L', 'XL')])],
    'one colour, one size' => ['Black M', fn () => VariantNames::list([variantColour('Black', 'M')])],
    'one colour, two sizes' => ['Black M and L', fn () => VariantNames::list([variantColour('Black', 'M', 'L')])],
    'two colours' => ['Black M and L, and Light wash S', fn () => VariantNames::list([variantColour('Black', 'M', 'L'), variantColour('Light wash', 'S')])],
    'the card example' => ['Black M and L, and Light wash S, M, L and XL', fn () => VariantNames::list([variantColour('Black', 'M', 'L'), variantColour('Light wash', 'S', 'M', 'L', 'XL')])],
    'three colours' => ['Black M, Indigo S and L, and Light wash S', fn () => VariantNames::list([variantColour('Black', 'M'), variantColour('Indigo', 'S', 'L'), variantColour('Light wash', 'S')])],
    'shoe sizes' => ['Light wash sizes 36, 37 and 38', fn () => VariantNames::list([variantColour('Light wash', '36', '37', '38')])],
    'two shoe sizes' => ['Black sizes 36 and 37', fn () => VariantNames::list([variantColour('Black', '36', '37')])],
    'one shoe size' => ['Light wash size 36', fn () => VariantNames::list([variantColour('Light wash', '36')])],
    'shoe sizes in two colours' => ['Black sizes 36 and 37, and Light wash size 38', fn () => VariantNames::list([variantColour('Black', '36', '37'), variantColour('Light wash', '38')])],
    '"One size" names the colours' => ['Camel and Rust', fn () => VariantNames::list([variantColour('Camel', 'One size'), variantColour('Rust', 'One size')], oneSize: true)],
    '"One size", one colour' => ['Camel', fn () => VariantNames::list([variantColour('Camel', 'One size')], oneSize: true)],
    '"One size", three colours' => ['Camel, Rust and Olive', fn () => VariantNames::list([variantColour('Camel', 'One size'), variantColour('Rust', 'One size'), variantColour('Olive', 'One size')], oneSize: true)],
    'nothing' => ['', fn () => VariantNames::list([])],
    'three colours within the limit of three' => ['Black M, Indigo S, and Navy L', fn () => VariantNames::list([variantColour('Black', 'M'), variantColour('Indigo', 'S'), variantColour('Navy', 'L')], colourLimit: 3)],
    'five colours with the limit' => ['Black M, Indigo S and L, Navy L, and 2 more colours', fn () => VariantNames::list([variantColour('Black', 'M'), variantColour('Indigo', 'S', 'L'), variantColour('Navy', 'L'), variantColour('Red', 'S'), variantColour('Pink', 'XL')], colourLimit: 3)],
    'four colours with the limit' => ['Black M, Indigo S, Navy L, and 1 more colour', fn () => VariantNames::list([variantColour('Black', 'M'), variantColour('Indigo', 'S'), variantColour('Navy', 'L'), variantColour('Red', 'S')], colourLimit: 3)],
    '"One size", four colours with the limit' => ['Camel, Rust, Olive, and 1 more colour', fn () => VariantNames::list([variantColour('Camel'), variantColour('Rust'), variantColour('Olive'), variantColour('Red')], oneSize: true, colourLimit: 3)],
    'one variant, a letter size' => ['Light wash S', fn () => VariantNames::one('Light wash', 'S')],
    'one variant, a shoe size' => ['Light wash size 36', fn () => VariantNames::one('Light wash', '36')],
    'one variant, "One size"' => ['Camel', fn () => VariantNames::one('Camel', null)],
    '1 SKU' => ['PRD-JKT-002', fn () => VariantNames::skus(['PRD-JKT-002'])],
    '2 SKUs' => ['PRD-JKT-002 and PRD-JKT-007', fn () => VariantNames::skus(['PRD-JKT-002', 'PRD-JKT-007'])],
    '3 SKUs' => ['PRD-JKT-002, PRD-JKT-007 and PRD-JKT-009', fn () => VariantNames::skus(['PRD-JKT-002', 'PRD-JKT-007', 'PRD-JKT-009'])],
    '4 SKUs' => ['PRD-JKT-002, PRD-JKT-007 and 2 more', fn () => VariantNames::skus(['PRD-JKT-002', 'PRD-JKT-007', 'PRD-JKT-009', 'PRD-JKT-011'])],
    '5 SKUs' => ['PRD-JKT-002, PRD-JKT-007 and 3 more', fn () => VariantNames::skus(['PRD-JKT-002', 'PRD-JKT-007', 'PRD-JKT-009', 'PRD-JKT-011', 'PRD-JKT-012'])],
]);
