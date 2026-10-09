<?php

use App\Models\Material;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('creates a material, redirects to show and flashes a success toast', function () {
    $response = actingAs($this->user)->post(route('materials.store'), validMaterialPayload());

    $material = Material::query()->where('sku', 'FAB-DEN-001')->firstOrFail();

    $response
        ->assertRedirect(route('materials.show', $material))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material created.']);

    expect($material->name)->toBe('Denim 12oz Indigo')
        ->and($material->category->code)->toBe('fabric')
        ->and($material->unit->code)->toBe('m')
        ->and($material->unit_cost)->toBe('8.5000')
        ->and($material->quantity_on_hand)->toBe('250.0000')
        ->and($material->reorder_level)->toBe('50.0000')
        ->and($material->supplier_name)->toBe('Candiani')
        ->and($material->supplier_reference)->toBe('CD-1200')
        ->and($material->color)->toBe('Indigo')
        ->and($material->composition)->toBe('98% cotton, 2% elastane')
        ->and($material->notes)->toBe('Pre-washed');
});

it('returns decimals as scale-4 strings and an exact stock value after creating', function () {
    actingAs($this->user)->post(route('materials.store'), validMaterialPayload());
    $material = Material::query()->firstOrFail();

    actingAs($this->user)->get(route('materials.show', $material))
        ->assertInertia(fn (Assert $page) => $page
            ->where('material.unit_cost', '8.5000')
            ->where('material.quantity_on_hand', '250.0000')
            ->where('material.reorder_level', '50.0000')
            ->where('material.stock_value', '2125.0000')
            ->where('material.stock_status', 'in_stock'));
});

it('rounds the stock value half up at scale 4', function (string $quantity, string $unitCost, string $expected) {
    actingAs($this->user)->post(route('materials.store'), validMaterialPayload([
        'quantity_on_hand' => $quantity,
        'unit_cost' => $unitCost,
        'reorder_level' => null,
    ]));

    actingAs($this->user)->get(route('materials.show', Material::query()->firstOrFail()))
        ->assertInertia(fn (Assert $page) => $page->where('material.stock_value', $expected));
})->with([
    'exact half goes up (0.00005 -> 0.0001)' => ['0.0001', '0.5', '0.0001'],
    'exact half goes up (0.00015 -> 0.0002)' => ['0.0003', '0.5', '0.0002'],
    'below half (0.00014997 -> 0.0001)' => ['0.0003', '0.4999', '0.0001'],
    'above half (0.00015003 -> 0.0002)' => ['0.0003', '0.5001', '0.0002'],
    'generic (0.11108889 -> 0.1111)' => ['0.3333', '0.3333', '0.1111'],
    'exact product' => ['250', '8.5', '2125.0000'],
    'max magnitude' => ['9999999999.9999', '99999999.9999', '999999999998990000.0000'],
]);

it('stores optional fields as null when blank and reorder_level as null for an empty string', function () {
    actingAs($this->user)->post(route('materials.store'), validMaterialPayload([
        'reorder_level' => '',
        'supplier_name' => '',
        'supplier_reference' => '',
        'color' => '',
        'composition' => '',
        'notes' => '',
    ]))->assertSessionHasNoErrors();

    $material = Material::query()->firstOrFail();

    expect($material->reorder_level)->toBeNull()
        ->and($material->supplier_name)->toBeNull()
        ->and($material->supplier_reference)->toBeNull()
        ->and($material->color)->toBeNull()
        ->and($material->composition)->toBeNull()
        ->and($material->notes)->toBeNull();
});

it('accepts a payload without any optional field', function () {
    actingAs($this->user)->post(route('materials.store'), [
        'sku' => 'X-1', 'name' => 'Bare', 'category' => 'other', 'unit' => 'pc',
        'unit_cost' => '0', 'quantity_on_hand' => '0',
    ])->assertSessionHasNoErrors();

    expect(Material::query()->firstOrFail()->unit_cost)->toBe('0.0000');
});

it('ignores unknown and non-fillable input', function () {
    actingAs($this->user)->post(route('materials.store'), validMaterialPayload([
        'id' => 999,
        'deleted_at' => now()->toDateTimeString(),
        'stock_status' => 'out',
    ]));

    $material = Material::query()->firstOrFail();

    expect($material->id)->not->toBe(999)
        ->and($material->deleted_at)->toBeNull();
});

it('updates a material, redirects to show and flashes a success toast', function () {
    $material = Material::factory()->create(['sku' => 'OLD-1']);

    actingAs($this->user)
        ->put(route('materials.update', $material), validMaterialPayload([
            'sku' => 'NEW-1',
            'name' => 'Renamed',
            'unit_cost' => '1.25',
            'quantity_on_hand' => '10.5',
            'reorder_level' => null,
            'category' => 'thread',
            'unit' => 'cone',
        ]))
        ->assertRedirect(route('materials.show', $material))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material updated.']);

    $material->refresh();

    expect($material->sku)->toBe('NEW-1')
        ->and($material->name)->toBe('Renamed')
        ->and($material->category->code)->toBe('thread')
        ->and($material->unit->code)->toBe('cone')
        ->and($material->unit_cost)->toBe('1.2500')
        ->and($material->quantity_on_hand)->toBe('10.5000')
        ->and($material->reorder_level)->toBeNull();

    assertDatabaseCount('materials', 1);
});

it('lets a material keep its own sku on update', function () {
    $material = Material::factory()->create(['sku' => 'KEEP-1']);

    actingAs($this->user)
        ->put(route('materials.update', $material), validMaterialPayload(['sku' => 'KEEP-1']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('materials.show', $material));
});

it('rejects an update that takes another active material\'s sku', function () {
    Material::factory()->create(['sku' => 'TAKEN']);
    $material = Material::factory()->create(['sku' => 'MINE']);

    actingAs($this->user)
        ->put(route('materials.update', $material), validMaterialPayload(['sku' => 'TAKEN']))
        ->assertSessionHasErrors('sku');

    expect($material->refresh()->sku)->toBe('MINE');
});

it('allows an update to reuse the sku of a soft-deleted material', function () {
    Material::factory()->create(['sku' => 'GONE'])->delete();
    $material = Material::factory()->create(['sku' => 'MINE']);

    actingAs($this->user)
        ->put(route('materials.update', $material), validMaterialPayload(['sku' => 'GONE']))
        ->assertSessionHasNoErrors();

    expect($material->refresh()->sku)->toBe('GONE');
});
