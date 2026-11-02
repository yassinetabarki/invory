<?php

use App\Domain\Clients\PhoneNumber;

/*
|--------------------------------------------------------------------------
| A client's phone (INV-4-a, ADR-0021)
|--------------------------------------------------------------------------
|
| One rule makes what the screens show and the key that finds a client, so the save, the lookup and the search always agree.
*/

it('reads a Tunisian number however it is typed as one key, shown "22 345 678"', function (string $typed) {
    $phone = PhoneNumber::parse($typed);

    expect($phone?->display)->toBe('22 345 678')
        ->and($phone?->key)->toBe('21622345678');
})->with(['22345678', '22 345 678', '22.345.678', '22-345-678', ' 22 345 678 ', '+216 22 345 678', '+21622345678', '0021622345678', '00216 22 345 678', '(+216) 22 345 678']);

it('keeps a number abroad as typed, with single spaces, keyed by its digits', function (string $typed, string $display, string $key) {
    $phone = PhoneNumber::parse($typed);

    expect($phone?->display)->toBe($display)
        ->and($phone?->key)->toBe($key);
})->with([
    'France' => ['+33 6 12 34 56 78', '+33 6 12 34 56 78', '33612345678'],
    'double spaces' => ['+33  6 12  34 56 78', '+33 6 12 34 56 78', '33612345678'],
    'eight digits' => ['+1234 5678', '+1234 5678', '12345678'],
    'fifteen digits' => ['+123456789012345', '+123456789012345', '123456789012345'],
]);

it('refuses what is not a phone Invory knows', function (string $typed) {
    expect(PhoneNumber::parse($typed))->toBeNull();
})->with([
    'seven digits' => ['2234567'],
    'nine digits' => ['223456789'],
    'abroad, too short' => ['+33 1234'],
    'abroad, sixteen digits' => ['+1234567890123456'],
    '+216 and seven digits' => ['+216 2234567'],
    '00 and a country other than Tunisia' => ['0033612345678'],
    'letters' => ['abc'],
    'digits and letters' => ['22 345 67a'],
    'empty' => [''],
    'a plus alone' => ['+'],
]);

it('gives the digits of a search term, from two digits on', function (string $term, ?string $digits) {
    expect(PhoneNumber::searchDigits($term))->toBe($digits);
})->with([
    ['22 345', '22345'],
    ['+216 22', '21622'],
    ['Amira', null],
    ['Tunis 2', null],
]);
