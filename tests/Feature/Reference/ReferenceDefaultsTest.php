<?php

use App\Enums\UnitDimension;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;

/*
|--------------------------------------------------------------------------
| The defaults the reference-data migration inserts (ADR-0008)
|--------------------------------------------------------------------------
|
| They replace the PHP enums UnitOfMeasure, MaterialCategory and ProductCategory (and the "matches the domain skill values" and
| "uses the decided labels" tests of those enums): same codes, same order (`position`), same English labels. Every test runs on a
| freshly migrated database, so these are exactly the rows an installation starts with.
*/

it('inserts the 13 units of the costing skill, in the order of the former enum', function () {
    expect(Unit::query()->ordered()->pluck('code')->all())->toBe([
        'm', 'cm', 'yd', 'sqm', 'sqft', 'kg', 'g', 'l', 'ml', 'pc', 'pair', 'roll', 'cone',
    ])->and(Unit::query()->orderBy('id')->pluck('position')->all())->toBe(range(0, 12));
});

it('gives each default unit its name, dimension and factor to the base unit', function (string $code, string $name, string $dimension, ?string $factor) {
    $unit = Unit::query()->where('code', $code)->firstOrFail();

    expect($unit->name)->toBe($name)
        ->and($unit->dimension)->toBe(UnitDimension::from($dimension))
        ->and($unit->factor_to_base)->toBe($factor)
        ->and($unit->is_system)->toBeTrue()
        ->and($unit->archived_at)->toBeNull()
        // The label of the former enum: "Metre (m)".
        ->and($unit->label())->toBe("{$name} ({$code})");
})->with([
    'm' => ['m', 'Metre', 'length', '1.000000000000'],
    'cm' => ['cm', 'Centimetre', 'length', '0.010000000000'],
    'yd' => ['yd', 'Yard', 'length', '0.914400000000'],
    'sqm' => ['sqm', 'Square metre', 'area', '1.000000000000'],
    'sqft' => ['sqft', 'Square foot', 'area', '0.092903040000'],
    'kg' => ['kg', 'Kilogram', 'mass', '1.000000000000'],
    'g' => ['g', 'Gram', 'mass', '0.001000000000'],
    'l' => ['l', 'Litre', 'volume', '1.000000000000'],
    'ml' => ['ml', 'Millilitre', 'volume', '0.001000000000'],
    'pc' => ['pc', 'Piece', 'count', '1.000000000000'],
    'pair' => ['pair', 'Pair', 'count', '2.000000000000'],
    'roll' => ['roll', 'Roll', 'package', null],
    'cone' => ['cone', 'Cone', 'package', null],
]);

it('inserts the 12 material categories, in the order and with the labels of the former enum', function () {
    expect(MaterialCategory::query()->ordered()->pluck('name', 'code')->all())->toBe([
        'fabric' => 'Fabric',
        'lining' => 'Lining',
        'interlining' => 'Interlining',
        'leather' => 'Leather',
        'thread' => 'Thread',
        'trim' => 'Trim',
        'fastener' => 'Fastener',
        'label' => 'Label',
        'sole' => 'Sole',
        'hardware' => 'Hardware',
        'packaging' => 'Packaging',
        'other' => 'Other',
    ])->and(MaterialCategory::query()->orderBy('id')->pluck('position')->all())->toBe(range(0, 11));
});

it('inserts the 6 product categories, in the order and with the labels of the former enum', function () {
    expect(ProductCategory::query()->ordered()->pluck('name', 'code')->all())->toBe([
        'apparel' => 'Apparel',
        'footwear' => 'Footwear',
        'bag' => 'Bag',
        'accessory' => 'Accessory',
        'jewelry' => 'Jewelry',
        'other' => 'Other',
    ])->and(ProductCategory::query()->orderBy('id')->pluck('position')->all())->toBe(range(0, 5));
});

it('inserts every default active, and only the units are system rows', function () {
    expect(Unit::query()->active()->count())->toBe(13)
        ->and(Unit::query()->where('is_system', true)->count())->toBe(13)
        ->and(MaterialCategory::query()->active()->count())->toBe(12)
        ->and(ProductCategory::query()->active()->count())->toBe(6);
});

it('keeps a unit factor out of the package dimension only', function () {
    expect(Unit::query()->where('dimension', 'package')->whereNotNull('factor_to_base')->count())->toBe(0)
        ->and(Unit::query()->where('dimension', '!=', 'package')->whereNull('factor_to_base')->count())->toBe(0);
});
