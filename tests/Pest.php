<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // Feature tests assert Inertia props, not assets: a stale local Vite manifest (public/build) must not fail them.
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A valid Store/UpdateMaterialRequest payload (decimals as strings), with optional overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validMaterialPayload(array $overrides = []): array
{
    return [
        'sku' => 'FAB-DEN-001',
        'name' => 'Denim 12oz Indigo',
        'category' => 'fabric',
        'unit' => 'm',
        'unit_cost' => '8.5',
        'quantity_on_hand' => '250',
        'reorder_level' => '50',
        'supplier_name' => 'Candiani',
        'supplier_reference' => 'CD-1200',
        'color' => 'Indigo',
        'composition' => '98% cotton, 2% elastane',
        'notes' => 'Pre-washed',
        ...$overrides,
    ];
}

/**
 * The exact keys of the `Material` type in resources/js/types/domain.ts.
 *
 * @return list<string>
 */
function materialContractKeys(): array
{
    return [
        'id', 'sku', 'name', 'category', 'category_label', 'unit', 'unit_label', 'unit_cost',
        'quantity_on_hand', 'reorder_level', 'stock_value', 'stock_status', 'supplier_name',
        'supplier_reference', 'color', 'composition', 'notes', 'created_at', 'updated_at',
    ];
}
