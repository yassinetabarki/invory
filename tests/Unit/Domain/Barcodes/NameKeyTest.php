<?php

use App\Domain\Barcodes\NameKey;

// INV-8-a: "the same name" is the same words, ignoring capitals and extra spaces; accents count (ADR-0020).

it('gives one key to the same words in other capitals or spacing, another to other words, and tidies a name', function () {
    $key = NameKey::of('Denim Jacket');

    expect($key)->toBe('denim jacket');

    foreach (['denim jacket', '  Denim   Jacket ', 'DENIM JACKET', "Denim\tJacket", "Denim\u{00A0}Jacket"] as $same) {
        expect(NameKey::of($same))->toBe($key);
    }

    expect(NameKey::of('Denim Jackets'))->not->toBe($key)
        ->and(NameKey::of('Écru'))->toBe('écru')
        ->and(NameKey::of('Écru'))->not->toBe(NameKey::of('Ecru'))
        // tidy() keeps the capitals and drops the extra spaces.
        ->and(NameKey::tidy('Trucker  Jacket '))->toBe('Trucker Jacket')
        ->and(NameKey::tidy('  Écru   Tote'))->toBe('Écru Tote');
});
