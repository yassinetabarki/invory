<?php

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\Reference\ReferenceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The payload is built from the tables (the migration's defaults), so the application and a database are needed.
uses(TestCase::class, RefreshDatabase::class);

it('has exactly the six keys of the ReferenceData type, in order', function () {
    expect(array_keys((new ReferenceData)->all()))->toBe([
        'units', 'material_categories', 'product_categories', 'product_statuses', 'sales_periods', 'stock_statuses',
    ]);
});

it('builds every list as a zero-based list of rows with the contract keys', function () {
    $data = (new ReferenceData)->all();

    foreach (['units', 'material_categories', 'product_categories', 'product_statuses', 'sales_periods', 'stock_statuses'] as $key) {
        expect(array_is_list($data[$key]))->toBeTrue("{$key} is a list");
    }

    expect($data['units'])->toHaveCount(13)
        ->and($data['material_categories'])->toHaveCount(12)
        ->and($data['product_categories'])->toHaveCount(6)
        ->and(array_keys($data['units'][0]))->toBe(['id', 'code', 'name', 'position', 'archived', 'dimension', 'dimension_label', 'factor_to_base', 'is_system'])
        ->and(array_keys($data['material_categories'][0]))->toBe(['id', 'code', 'name', 'position', 'archived'])
        ->and(array_keys($data['product_categories'][0]))->toBe(['id', 'code', 'name', 'position', 'archived'])
        ->and(array_keys($data['product_statuses'][0]))->toBe(['value', 'label']);
});

it('types every value: ints, strings, bools, and the factor as a decimal string or null', function () {
    $units = (new ReferenceData)->all()['units'];

    foreach ($units as $unit) {
        expect($unit['id'])->toBeInt()
            ->and($unit['code'])->toBeString()
            ->and($unit['name'])->toBeString()
            ->and($unit['position'])->toBeInt()
            ->and($unit['archived'])->toBeBool()
            ->and($unit['dimension'])->toBeString()
            ->and($unit['dimension_label'])->toBeString()
            ->and($unit['is_system'])->toBeBool();
        expect($unit['factor_to_base'] === null || (is_string($unit['factor_to_base']) && preg_match('/^\d+\.\d{12}$/', $unit['factor_to_base']) === 1))->toBeTrue();
    }
});

it('includes archived rows, flagged, and orders every list by position then name', function () {
    Unit::factory()->archived()->create(['code' => 'old', 'name' => 'Aaa', 'position' => 0]);
    MaterialCategory::factory()->archived()->create(['code' => 'old', 'name' => 'Aaa', 'position' => 0]);
    ProductCategory::factory()->archived()->create(['code' => 'old', 'name' => 'Aaa', 'position' => 0]);

    $data = (new ReferenceData)->all();

    expect($data['units'])->toHaveCount(14)
        ->and($data['units'][0]['code'])->toBe('old')
        ->and($data['units'][0]['archived'])->toBeTrue()
        ->and($data['units'][1]['code'])->toBe('m')
        ->and($data['material_categories'][0])->toMatchArray(['code' => 'old', 'archived' => true])
        ->and($data['material_categories'][1])->toMatchArray(['code' => 'fabric', 'archived' => false])
        ->and($data['product_categories'][0])->toMatchArray(['code' => 'old', 'archived' => true])
        ->and($data['product_categories'][1])->toMatchArray(['code' => 'apparel', 'archived' => false]);
});

it('carries the options of the enums that stay in PHP, with their labels', function () {
    $data = (new ReferenceData)->all();

    expect($data['product_statuses'])->toBe([
        ['value' => 'draft', 'label' => 'Draft'], ['value' => 'active', 'label' => 'Active'], ['value' => 'archived', 'label' => 'Archived'],
    ])->and($data['sales_periods'])->toBe([
        ['value' => 'last_7_days', 'label' => 'Last 7 days'], ['value' => 'last_30_days', 'label' => 'Last 30 days'],
        ['value' => 'this_month', 'label' => 'This month'], ['value' => 'this_year', 'label' => 'This year'],
    ])->and($data['stock_statuses'])->toBe([
        ['value' => 'in_stock', 'label' => 'In stock'], ['value' => 'low', 'label' => 'Low stock'], ['value' => 'out', 'label' => 'Out of stock'],
    ]);
});

it('serialises a unit of each dimension', function () {
    $unit = Unit::query()->where('code', 'sqft')->firstOrFail();
    $package = Unit::query()->where('code', 'cone')->firstOrFail();

    expect(ReferenceData::unit($unit))->toBe([
        'id' => $unit->id, 'code' => 'sqft', 'name' => 'Square foot', 'position' => 4, 'archived' => false,
        'dimension' => 'area', 'dimension_label' => 'Area', 'factor_to_base' => '0.092903040000', 'is_system' => true,
    ])->and(ReferenceData::unit($package)['factor_to_base'])->toBeNull()
        ->and(ReferenceData::unit($package)['dimension'])->toBe('package');
});

it('serialises a category and flags an archived one', function () {
    $category = MaterialCategory::factory()->archived()->create(['code' => 'old', 'name' => 'Old', 'position' => 9]);

    expect(ReferenceData::category($category))->toBe(['id' => $category->id, 'code' => 'old', 'name' => 'Old', 'position' => 9, 'archived' => true])
        ->and(ReferenceData::category(ProductCategory::query()->firstOrFail())['archived'])->toBeFalse();
});

it('reads three tables and nothing else', function () {
    $queries = queryLog(fn () => (new ReferenceData)->all());

    expect($queries)->toHaveCount(3);
});
