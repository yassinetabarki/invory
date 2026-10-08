<?php

use App\Models\Material;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Restock a material on the preview fixture (ADR-0006, S-13c)
|--------------------------------------------------------------------------
|
| The restock is validated for real by StoreRestockRequest, then nothing is saved (no stock movement table yet, S-16-BE):
| an `info` toast says so. Rules: `invory-costing-domain` skill, "Validation rules" and "Restock and price history".
*/

beforeEach(fn () => $this->user = preparePreview($this));

const RESTOCK_SAVED_TOAST = ['type' => 'info', 'message' => "Preview: the restock passed validation but wasn't saved."];

dataset('invalidRestockInput', [
    'missing quantity' => [['quantity' => null], 'quantity'],
    'zero quantity' => [['quantity' => '0'], 'quantity'],
    'zero with decimals' => [['quantity' => '0.0000'], 'quantity'],
    'negative quantity' => [['quantity' => '-5'], 'quantity'],
    'quantity with 5 decimals' => [['quantity' => '1.00001'], 'quantity'],
    'text quantity' => [['quantity' => 'lots'], 'quantity'],
    'exponent quantity' => [['quantity' => '1e3'], 'quantity'],
    'quantity over the column' => [['quantity' => '10000000000'], 'quantity'],
    'array quantity' => [['quantity' => ['5']], 'quantity'],
    'missing unit price' => [['unit_price' => null], 'unit_price'],
    'negative unit price' => [['unit_price' => '-0.0001'], 'unit_price'],
    'unit price with 5 decimals' => [['unit_price' => '0.12345'], 'unit_price'],
    'text unit price' => [['unit_price' => 'cheap'], 'unit_price'],
    'unit price over the column' => [['unit_price' => '100000000'], 'unit_price'],
    'missing date' => [['received_on' => null], 'received_on'],
    'tomorrow' => [['received_on' => '2026-10-09'], 'received_on'],
    'a date far in the future' => [['received_on' => '2031-01-01'], 'received_on'],
    'a non date' => [['received_on' => 'today'], 'received_on'],
    'a date in the wrong format' => [['received_on' => '08/10/2026'], 'received_on'],
    'an impossible date' => [['received_on' => '2026-13-01'], 'received_on'],
    'a reference over 64 characters' => [['reference' => str_repeat('a', 65)], 'reference'],
    'an array reference' => [['reference' => ['PO']], 'reference'],
    'missing update_unit_cost' => [['update_unit_cost' => null], 'update_unit_cost'],
    'update_unit_cost not a boolean' => [['update_unit_cost' => 'maybe'], 'update_unit_cost'],
    'update_unit_cost as text' => [['update_unit_cost' => 'yes'], 'update_unit_cost'],
]);

dataset('validRestockBoundaries', [
    'the smallest quantity' => [['quantity' => '0.0001']],
    'an integer quantity' => [['quantity' => '500']],
    'the biggest quantity' => [['quantity' => '9999999999.9999']],
    'a free restock' => [['unit_price' => '0']],
    'four decimal price' => [['unit_price' => '0.3500']],
    'the biggest price' => [['unit_price' => '99999999.9999']],
    'today' => [['received_on' => '2026-10-08']],
    'a date in the past' => [['received_on' => '2025-01-31']],
    'a reference of 64 characters' => [['reference' => str_repeat('a', 64)]],
    'no reference' => [['reference' => null]],
    'an empty reference' => [['reference' => '']],
    'keep the unit cost' => [['update_unit_cost' => '0']],
    'update the unit cost' => [['update_unit_cost' => '1']],
    'update_unit_cost as true' => [['update_unit_cost' => true]],
    'update_unit_cost as false' => [['update_unit_cost' => false]],
]);

it('validates, saves nothing and flashes the preview toast', function () {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)
        ->from(route('materials.index'))
        ->post(route('materials.restocks.store', $button), validRestockPayload())
        ->assertRedirect(route('materials.index'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', RESTOCK_SAVED_TOAST);
});

it('leaves the stock and the unit cost alone, whatever update_unit_cost says', function (string $updateUnitCost) {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)
        ->post(route('materials.restocks.store', $button), validRestockPayload(['quantity' => '500', 'unit_price' => '0.9', 'update_unit_cost' => $updateUnitCost]))
        ->assertSessionHasNoErrors();

    expect($button->fresh()->quantity_on_hand)->toBe('900.0000')
        ->and($button->fresh()->unit_cost)->toBe('0.3500')
        ->and(Material::query()->count())->toBe(12);
})->with(['update the cost' => '1', 'keep the cost' => '0']);

it('rejects invalid input with an error on the field and no toast', function (array $override, string $field) {
    actingAs($this->user)
        ->from(route('materials.index'))
        ->post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload($override))
        ->assertRedirect(route('materials.index'))
        ->assertSessionHasErrors($field)
        ->assertInertiaFlashMissing('toast');
})->with('invalidRestockInput');

it('accepts the boundary values', function (array $override) {
    actingAs($this->user)
        ->post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload($override))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', RESTOCK_SAVED_TOAST);
})->with('validRestockBoundaries');

it('rejects only the invalid fields', function () {
    actingAs($this->user)
        ->post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload(['quantity' => '0', 'received_on' => '2031-01-01']))
        ->assertSessionHasErrors(['quantity', 'received_on'])
        ->assertSessionDoesntHaveErrors(['unit_price', 'reference', 'update_unit_cost']);
});

it('answers 404 for a deleted material before it validates anything', function () {
    $material = previewMaterial('MAT-BTN-017');
    $material->delete();

    actingAs($this->user)->post(route('materials.restocks.store', $material->id), ['quantity' => '0'])
        ->assertNotFound()
        ->assertSessionHasNoErrors();
});
