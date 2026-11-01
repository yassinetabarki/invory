<?php

use App\Domain\Barcodes\Ean13;

// INV-8-a: the in-store numbers of a workshop and their check digit (ADR-0020).

it('works out the check digit and the in-store numbers, and refuses what has none', function () {
    // The published example: 4006381333931 is valid, so the check digit of its first 12 digits is 1. A sum that is a multiple of 10 gives 0.
    expect(Ean13::checkDigit('400638133393'))->toBe(1)
        ->and(Ean13::checkDigit('200000000001'))->toBe(5)
        ->and(Ean13::checkDigit('000000000000'))->toBe(0);

    $numbers = [1 => '2000000000015', 2 => '2000000000022', 3 => '2000000000039', 4 => '2000000000046', 5 => '2000000000053', 99_999_999_999 => '2999999999991'];

    foreach ($numbers as $serial => $number) {
        expect(Ean13::inStore($serial))->toBe($number)
            ->and(Ean13::checkDigit(substr($number, 0, 12)))->toBe((int) $number[12]);
    }

    foreach ([fn () => Ean13::inStore(0), fn () => Ean13::inStore(-1), fn () => Ean13::inStore(100_000_000_000), fn () => Ean13::checkDigit('40063813339'), fn () => Ean13::checkDigit('40063813339A')] as $refused) {
        expect($refused)->toThrow(InvalidArgumentException::class);
    }
});
