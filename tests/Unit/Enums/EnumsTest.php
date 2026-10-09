<?php

use App\Enums\StockStatus;
use Tests\TestCase;

// Labels go through __(), so these tests need the application booted.
uses(TestCase::class);

// Units, material categories and product categories are tables since ADR-0008: their defaults are asserted in
// tests/Feature/Reference/ReferenceDefaultsTest.php. StockStatus stays an enum (it is computed, not managed).

it('exposes the stock status options as a list of value/label pairs', function () {
    $options = StockStatus::options();

    expect($options)->toBeArray()
        ->and(array_is_list($options))->toBeTrue()
        ->and($options)->toHaveCount(count(StockStatus::cases()));

    foreach (StockStatus::cases() as $index => $case) {
        expect($options[$index])->toBe(['value' => $case->value, 'label' => $case->label()]);
    }
});

it('gives every stock status a non-empty label and a unique value', function () {
    $values = [];
    $labels = [];

    foreach (StockStatus::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        $values[] = $case->value;
        $labels[] = $case->label();
    }

    expect($values)->toBe(array_values(array_unique($values)))
        ->and($labels)->toBe(array_values(array_unique($labels)));
});

it('matches the domain skill values', function () {
    expect(array_column(StockStatus::options(), 'value'))->toBe(['in_stock', 'low', 'out']);
});

it('uses the decided labels', function () {
    expect(StockStatus::InStock->label())->toBe('In stock')
        ->and(StockStatus::Low->label())->toBe('Low stock')
        ->and(StockStatus::Out->label())->toBe('Out of stock');
});

it('can be created from its backing value', function () {
    expect(StockStatus::tryFrom('nope'))->toBeNull();
});
