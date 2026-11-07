<?php

use App\Domain\Sizes\SizeLine;

/*
|--------------------------------------------------------------------------
| The sizes of a product in words (INV-18, AC-7; costing skill "Sizes")
|--------------------------------------------------------------------------
*/

const LETTER_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
const SHOE_SIZES = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'];

it('writes the badge and the Suggests line of a set of sizes', function (array $labels, bool $oneSize, string $badge, string $suggests) {
    // The workshop's list: the letters, the shoes and the One size, as every new workshop has it.
    $workshop = [...LETTER_SIZES, ...SHOE_SIZES, 'One size'];

    expect(SizeLine::badge($labels, $workshop, $oneSize))->toBe($badge)
        ->and(SizeLine::suggests($labels, $workshop, $oneSize))->toBe($suggests)
        // The workshop list may come as a closure: it is asked for only when seven sizes or more need it.
        ->and(SizeLine::badge($labels, fn (): array => $workshop, $oneSize))->toBe($badge);
})->with([
    'One size' => [['One size'], true, 'One size', 'One size'],
    'One size, renamed' => [['Taille unique'], true, 'Taille unique', 'Taille unique'],
    'a single letter size' => [['M'], false, 'Size M', 'M'],
    'a single number size' => [['38'], false, 'Size 38', '38'],
    'two sizes' => [['S', 'M'], false, 'Sizes S, M', 'S, M'],
    'four letter sizes, with a gap' => [['S', 'M', 'L', 'XL'], false, 'Sizes S, M, L, XL', 'S, M, L, XL'],
    'three number sizes' => [['38', '39', '40'], false, 'Sizes 38, 39, 40', '38, 39, 40'],
    'six sizes with gaps stay in full' => [['XS', 'M', 'XL', 'XXXL', '36', '46'], false, 'Sizes XS, M, XL, XXXL, 36, 46', 'XS, M, XL, XXXL, 36, 46'],
    'seven letter sizes in a run' => [LETTER_SIZES, false, 'Sizes XS to XXXL', 'XS to XXXL'],
    'seven number sizes in a run' => [array_slice(SHOE_SIZES, 2, 7), false, 'Sizes 38 to 44', '38 to 44'],
    'all the number sizes' => [SHOE_SIZES, false, 'Sizes 36 to 46', '36 to 46'],
    'seven sizes with gaps' => [['XS', 'S', 'M', 'XXL', 'XXXL', '36', '37'], false, 'Sizes XS, S, M and 4 more', 'XS, S, M and 4 more'],
    'seven sizes with a gap inside the run' => [['XS', 'S', 'L', 'XL', 'XXL', 'XXXL', '36'], false, 'Sizes XS, S, L and 4 more', 'XS, S, L and 4 more'],
]);
