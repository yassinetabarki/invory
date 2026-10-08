<?php

use App\Enums\StockStatus;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('computes the stock status', function (string $quantity, ?string $reorderLevel, StockStatus $expected) {
    $material = Material::factory()->make(['quantity_on_hand' => $quantity, 'reorder_level' => $reorderLevel]);

    expect($material->stockStatus())->toBe($expected);
})->with([
    'well above reorder level' => ['100', '50', StockStatus::InStock],
    'just above reorder level' => ['50.0001', '50', StockStatus::InStock],
    'equal to reorder level counts as low' => ['50', '50', StockStatus::Low],
    'just below reorder level' => ['49.9999', '50', StockStatus::Low],
    'zero wins over reorder level' => ['0', '50', StockStatus::Out],
    'zero with equal reorder level is out, not low' => ['0.0000', '0.0000', StockStatus::Out],
    'zero without reorder level' => ['0', null, StockStatus::Out],
    'no reorder level is never low' => ['1', null, StockStatus::InStock],
    'tiny stock and zero reorder level' => ['0.0001', '0', StockStatus::InStock],
]);

it('computes the stock value with BigDecimal at scale 4', function () {
    expect(Material::factory()->make(['quantity_on_hand' => '250', 'unit_cost' => '8.5'])->stockValue())->toBe('2125.0000')
        ->and(Material::factory()->make(['quantity_on_hand' => '0', 'unit_cost' => '8.5'])->stockValue())->toBe('0.0000')
        ->and(Material::factory()->make(['quantity_on_hand' => '20000', 'unit_cost' => '0.004'])->stockValue())->toBe('80.0000')
        ->and(Material::factory()->make(['quantity_on_hand' => '0.0001', 'unit_cost' => '0.5'])->stockValue())->toBe('0.0001');
});

it('has factory states matching their name and defaults to in stock', function () {
    expect(Material::factory()->create()->stockStatus())->toBe(StockStatus::InStock)
        ->and(Material::factory()->lowStock()->create()->stockStatus())->toBe(StockStatus::Low)
        ->and(Material::factory()->outOfStock()->create()->stockStatus())->toBe(StockStatus::Out);

    // Defaults hold across many random draws.
    Material::factory()->count(25)->create()->each(
        fn (Material $material) => expect($material->stockStatus())->toBe(StockStatus::InStock),
    );
    Material::factory()->count(25)->lowStock()->create()->each(
        fn (Material $material) => expect($material->stockStatus())->toBe(StockStatus::Low),
    );
});

it('serialises the stock status on the resource', function () {
    Material::factory()->create(['name' => 'A', 'quantity_on_hand' => '100', 'reorder_level' => '50']);
    Material::factory()->create(['name' => 'B', 'quantity_on_hand' => '50', 'reorder_level' => '50']);
    Material::factory()->create(['name' => 'C', 'quantity_on_hand' => '0', 'reorder_level' => '50']);

    actingAs(User::factory()->create())->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('materials.data.0.stock_status', 'in_stock')
            ->where('materials.data.1.stock_status', 'low')
            ->where('materials.data.2.stock_status', 'out'));
});

it('enforces a unique sku among non-deleted rows at database level', function () {
    $row = fn (string $sku, ?string $deletedAt = null): array => [
        'sku' => $sku,
        'name' => 'Raw row',
        'category' => 'fabric',
        'unit' => 'm',
        'unit_cost' => '1.0000',
        'quantity_on_hand' => '1.0000',
        'deleted_at' => $deletedAt,
    ];

    DB::table('materials')->insert($row('SKU-1'));

    // Failed statements abort the surrounding PostgreSQL transaction, so each one runs in its own savepoint.
    // Active duplicate: rejected by the partial unique index.
    expect(fn () => DB::transaction(fn () => DB::table('materials')->insert($row('SKU-1'))))->toThrow(QueryException::class);

    // A trashed row does not block an active one, and several trashed rows may share a SKU.
    DB::table('materials')->insert($row('SKU-2', now()->toDateTimeString()));
    DB::table('materials')->insert($row('SKU-2'));
    DB::table('materials')->insert($row('SKU-2', now()->toDateTimeString()));

    expect(DB::table('materials')->where('sku', 'SKU-2')->count())->toBe(3);

    // Active row after its twin was trashed also works, but a second active one does not.
    expect(fn () => DB::transaction(fn () => DB::table('materials')->insert($row('SKU-2'))))->toThrow(QueryException::class);
});
