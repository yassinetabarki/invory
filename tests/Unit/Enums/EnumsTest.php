<?php

use App\Enums\MaterialCategory;
use App\Enums\StockStatus;
use App\Enums\UnitOfMeasure;
use Tests\TestCase;

// Labels go through __(), so these tests need the application booted.
uses(TestCase::class);

dataset('materialEnums', [
    'MaterialCategory' => [MaterialCategory::class],
    'UnitOfMeasure' => [UnitOfMeasure::class],
    'StockStatus' => [StockStatus::class],
]);

it('exposes options as a list of value/label pairs', function (string $enum) {
    $options = $enum::options();

    expect($options)->toBeArray()
        ->and(array_is_list($options))->toBeTrue()
        ->and($options)->toHaveCount(count($enum::cases()));

    foreach ($enum::cases() as $index => $case) {
        expect($options[$index])->toBe(['value' => $case->value, 'label' => $case->label()]);
    }
})->with('materialEnums');

it('gives every case a non-empty label and a unique value', function (string $enum) {
    $values = [];
    $labels = [];

    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        $values[] = $case->value;
        $labels[] = $case->label();
    }

    expect($values)->toBe(array_values(array_unique($values)))
        ->and($labels)->toBe(array_values(array_unique($labels)));
})->with('materialEnums');

it('matches the domain skill values', function () {
    expect(array_column(MaterialCategory::options(), 'value'))->toBe([
        'fabric', 'lining', 'interlining', 'leather', 'thread', 'trim',
        'fastener', 'label', 'sole', 'hardware', 'packaging', 'other',
    ])->and(array_column(UnitOfMeasure::options(), 'value'))->toBe([
        'm', 'cm', 'yd', 'sqm', 'sqft', 'kg', 'g', 'l', 'ml', 'pc', 'pair', 'roll', 'cone',
    ])->and(array_column(StockStatus::options(), 'value'))->toBe(['in_stock', 'low', 'out']);
});

it('uses the decided labels', function () {
    expect(MaterialCategory::Fabric->label())->toBe('Fabric')
        ->and(MaterialCategory::Interlining->label())->toBe('Interlining')
        ->and(MaterialCategory::Packaging->label())->toBe('Packaging')
        ->and(UnitOfMeasure::Metre->label())->toBe('Metre (m)')
        ->and(UnitOfMeasure::SquareMetre->label())->toBe('Square metre (sqm)')
        ->and(UnitOfMeasure::Piece->label())->toBe('Piece (pc)')
        ->and(StockStatus::InStock->label())->toBe('In stock')
        ->and(StockStatus::Low->label())->toBe('Low stock')
        ->and(StockStatus::Out->label())->toBe('Out of stock');
});

it('can be created from its backing value', function () {
    expect(MaterialCategory::from('fabric'))->toBe(MaterialCategory::Fabric)
        ->and(UnitOfMeasure::from('sqft'))->toBe(UnitOfMeasure::SquareFoot)
        ->and(StockStatus::tryFrom('nope'))->toBeNull();
});
