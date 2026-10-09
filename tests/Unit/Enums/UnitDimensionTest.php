<?php

use App\Enums\UnitDimension;
use Tests\TestCase;

// Labels go through __(), so these tests need the application booted.
uses(TestCase::class);

it('has the six dimensions of ADR-0008, in order', function () {
    expect(array_column(UnitDimension::options(), 'value'))
        ->toBe(['length', 'area', 'mass', 'volume', 'count', 'package']);
});

it('exposes options as a list of value/label pairs', function () {
    $options = UnitDimension::options();

    expect(array_is_list($options))->toBeTrue()
        ->and($options)->toHaveCount(count(UnitDimension::cases()));

    foreach (UnitDimension::cases() as $index => $case) {
        expect($options[$index])->toBe(['value' => $case->value, 'label' => $case->label()]);
    }
});

it('uses the decided labels', function () {
    expect(array_column(UnitDimension::options(), 'label'))
        ->toBe(['Length', 'Area', 'Mass', 'Volume', 'Count', 'Package']);
});

it('gives every case a non-empty, unique label', function () {
    $labels = array_map(fn (UnitDimension $case): string => $case->label(), UnitDimension::cases());

    expect($labels)->each->not->toBeEmpty()
        ->and($labels)->toBe(array_values(array_unique($labels)));
});

it('says which dimensions have a factor to the base unit', function (UnitDimension $dimension, bool $hasFactor) {
    expect($dimension->hasFactor())->toBe($hasFactor);
})->with([
    'length' => [UnitDimension::Length, true],
    'area' => [UnitDimension::Area, true],
    'mass' => [UnitDimension::Mass, true],
    'volume' => [UnitDimension::Volume, true],
    'count' => [UnitDimension::Count, true],
    'package' => [UnitDimension::Package, false],
]);

it('can be created from its backing value', function () {
    expect(UnitDimension::from('mass'))->toBe(UnitDimension::Mass)
        ->and(UnitDimension::tryFrom('weight'))->toBeNull();
});
