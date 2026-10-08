<?php

use App\Models\Material;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

beforeEach(function () {
    $this->user = User::factory()->create();
});

dataset('invalidMaterialInput', [
    'negative cost' => [['unit_cost' => '-1'], 'unit_cost'],
    'cost with more than 4 decimals' => [['unit_cost' => '1.12345'], 'unit_cost'],
    'non-numeric cost' => [['unit_cost' => 'abc'], 'unit_cost'],
    'exponent cost' => [['unit_cost' => '1e3'], 'unit_cost'],
    'cost over column capacity' => [['unit_cost' => '100000000'], 'unit_cost'],
    'missing cost' => [['unit_cost' => null], 'unit_cost'],
    'negative quantity' => [['quantity_on_hand' => '-0.0001'], 'quantity_on_hand'],
    'quantity with more than 4 decimals' => [['quantity_on_hand' => '1.00001'], 'quantity_on_hand'],
    'non-numeric quantity' => [['quantity_on_hand' => 'ten'], 'quantity_on_hand'],
    'exponent quantity' => [['quantity_on_hand' => '1e3'], 'quantity_on_hand'],
    'quantity over column capacity' => [['quantity_on_hand' => '10000000000'], 'quantity_on_hand'],
    'missing quantity' => [['quantity_on_hand' => null], 'quantity_on_hand'],
    'negative reorder level' => [['reorder_level' => '-1'], 'reorder_level'],
    'reorder level with more than 4 decimals' => [['reorder_level' => '1.23456'], 'reorder_level'],
    'non-numeric reorder level' => [['reorder_level' => 'x'], 'reorder_level'],
    'exponent reorder level' => [['reorder_level' => '1e3'], 'reorder_level'],
    'unknown category' => [['category' => 'bananas'], 'category'],
    'missing category' => [['category' => null], 'category'],
    'unknown unit' => [['unit' => 'bananas'], 'unit'],
    'missing unit' => [['unit' => null], 'unit'],
    'missing sku' => [['sku' => null], 'sku'],
    'missing name' => [['name' => null], 'name'],
    'sku too long' => [['sku' => str_repeat('a', 65)], 'sku'],
    'name too long' => [['name' => str_repeat('a', 151)], 'name'],
    'supplier name too long' => [['supplier_name' => str_repeat('a', 151)], 'supplier_name'],
    'supplier reference too long' => [['supplier_reference' => str_repeat('a', 65)], 'supplier_reference'],
    'color too long' => [['color' => str_repeat('a', 65)], 'color'],
    'composition too long' => [['composition' => str_repeat('a', 256)], 'composition'],
    'notes too long' => [['notes' => str_repeat('a', 5001)], 'notes'],
    'array name' => [['name' => ['x']], 'name'],
]);

it('rejects invalid input on store', function (array $override, string $field) {
    actingAs($this->user)
        ->post(route('materials.store'), validMaterialPayload($override))
        ->assertSessionHasErrors($field);

    assertDatabaseCount('materials', 0);
})->with('invalidMaterialInput');

it('rejects invalid input on update', function (array $override, string $field) {
    $material = Material::factory()->create(['name' => 'Untouched']);

    actingAs($this->user)
        ->put(route('materials.update', $material), validMaterialPayload($override))
        ->assertSessionHasErrors($field);

    expect($material->refresh()->name)->toBe('Untouched');
})->with('invalidMaterialInput');

it('accepts boundary values', function (array $override) {
    actingAs($this->user)
        ->post(route('materials.store'), validMaterialPayload($override))
        ->assertSessionHasNoErrors();
})->with([
    'zero cost and stock' => [['unit_cost' => '0', 'quantity_on_hand' => '0.0000', 'reorder_level' => '0']],
    'max decimals' => [['unit_cost' => '99999999.9999', 'quantity_on_hand' => '9999999999.9999', 'reorder_level' => '9999999999.9999']],
    'integers' => [['unit_cost' => '12', 'quantity_on_hand' => '3', 'reorder_level' => '1']],
    'max length strings' => [[
        'sku' => str_repeat('a', 64),
        'name' => str_repeat('a', 150),
        'supplier_name' => str_repeat('a', 150),
        'supplier_reference' => str_repeat('a', 64),
        'color' => str_repeat('a', 64),
        'composition' => str_repeat('a', 255),
        'notes' => str_repeat('a', 5000),
    ]],
]);

it('rejects a duplicate sku among active materials', function () {
    Material::factory()->create(['sku' => 'DUP-1']);

    actingAs($this->user)
        ->post(route('materials.store'), validMaterialPayload(['sku' => 'DUP-1']))
        ->assertSessionHasErrors('sku');

    assertDatabaseCount('materials', 1);
});

it('allows reusing the sku of a soft-deleted material', function () {
    Material::factory()->create(['sku' => 'DUP-1'])->delete();

    actingAs($this->user)
        ->post(route('materials.store'), validMaterialPayload(['sku' => 'DUP-1']))
        ->assertSessionHasNoErrors();

    expect(Material::query()->where('sku', 'DUP-1')->count())->toBe(1)
        ->and(Material::withTrashed()->where('sku', 'DUP-1')->count())->toBe(2);
});
