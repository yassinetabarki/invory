<?php

use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| Restock a material: the HTTP flow (S-16-BE)
|--------------------------------------------------------------------------
|
| StoreRestockRequest validates and authorizes, RestockMaterial books the receipt and its ledger entries. Rules: `invory-costing-domain`
| skill, "Validation rules" and "Restock and price history". The clock is frozen at 2026-10-08 and the 12 demo materials exist
| (the button: 900 pc at 0.3500).
*/

beforeEach(fn () => $this->user = preparePreview($this));

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
    'update_unit_cost sent as null' => [['update_unit_cost' => null], 'update_unit_cost'],
    'update_unit_cost not a boolean' => [['update_unit_cost' => 'maybe'], 'update_unit_cost'],
    'update_unit_cost as text' => [['update_unit_cost' => 'yes'], 'update_unit_cost'],
]);

dataset('validRestockBoundaries', [
    'the smallest quantity' => [['quantity' => '0.0001']],
    'an integer quantity' => [['quantity' => '500']],
    'the biggest quantity on a material that is empty' => [['quantity' => '9999999999.9999'], 'MAT-SOL-042'],
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

it('redirects guests to the login page and books nothing', function () {
    post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload())->assertRedirect(route('login'));

    expect(StockMovement::query()->count())->toBe(0)
        ->and(previewMaterial('MAT-BTN-017')->quantity_on_hand)->toBe('900.0000');
});

it('asks the material policy for the right to update before it books anything', function () {
    // MaterialPolicy lets everyone in (one company, no roles), so deny the ability to prove the route asks for it.
    Gate::before(fn (User $user, string $ability) => $ability === 'update' ? false : null);

    actingAs($this->user)->post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload())->assertForbidden();

    expect(StockMovement::query()->count())->toBe(0)
        ->and(previewMaterial('MAT-BTN-017')->quantity_on_hand)->toBe('900.0000');
});

it('books the receipt: stock plus the quantity, the price paid as the unit cost, a success toast, and back where it came from', function () {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)
        ->from(route('materials.show', $button))
        ->post(route('materials.restocks.store', $button), validRestockPayload(['quantity' => '500', 'unit_price' => '0.36', 'received_on' => '2026-10-07', 'reference' => 'PO-2300']))
        ->assertRedirect(route('materials.show', $button))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Restocked Metal shank button 17mm: +500 pc.']);

    $movements = StockMovement::query()->where('material_id', $button->id)->orderBy('id')->get();

    expect($button->fresh()->quantity_on_hand)->toBe('1400.0000')
        ->and($button->fresh()->unit_cost)->toBe('0.3600')
        ->and($movements->pluck('type')->map->value->all())->toBe(['restock', 'price_change'])
        ->and($movements[0]->only(['quantity', 'quantity_after', 'unit_price', 'reference', 'user_id']))->toBe([
            'quantity' => '500.0000', 'quantity_after' => '1400.0000', 'unit_price' => '0.3600', 'reference' => 'PO-2300', 'user_id' => $this->user->id,
        ])
        ->and($movements->pluck('occurred_on')->map->toDateString()->all())->toBe(['2026-10-07', '2026-10-07'])
        ->and($movements[1]->only(['unit_cost_before', 'unit_cost_after']))->toBe(['unit_cost_before' => '0.3500', 'unit_cost_after' => '0.3600']);
});

it('updates the unit cost when update_unit_cost is left out', function () {
    $button = previewMaterial('MAT-BTN-017');
    $payload = validRestockPayload(['unit_price' => '0.4']);
    unset($payload['update_unit_cost']);

    actingAs($this->user)->post(route('materials.restocks.store', $button), $payload)->assertSessionHasNoErrors();

    expect($button->fresh()->unit_cost)->toBe('0.4000')
        ->and(StockMovement::query()->where('material_id', $button->id)->count())->toBe(2);
});

it('keeps the unit cost, and logs only the restock, when update_unit_cost is off', function (mixed $off) {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)
        ->post(route('materials.restocks.store', $button), validRestockPayload(['quantity' => '500', 'unit_price' => '0.9', 'update_unit_cost' => $off]))
        ->assertSessionHasNoErrors();

    expect($button->fresh()->quantity_on_hand)->toBe('1400.0000')
        ->and($button->fresh()->unit_cost)->toBe('0.3500')
        ->and(StockMovement::query()->where('material_id', $button->id)->pluck('type')->map->value->all())->toBe(['restock'])
        ->and(StockMovement::query()->where('material_id', $button->id)->value('unit_price'))->toBe('0.9000');
})->with(['"0"' => '0', 'false' => false, '0' => 0]);

it('words the quantity of the toast like a sentence', function (string $quantity, string $shown) {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)->post(route('materials.restocks.store', $button), validRestockPayload(['quantity' => $quantity]))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => "Restocked Metal shank button 17mm: +{$shown} pc."]);
})->with([
    'a whole number' => ['500', '500'],
    'trailing zeros' => ['500.0000', '500'],
    'a round thousand' => ['1000', '1000'],
    'a fraction' => ['12.5', '12.5'],
    'the smallest' => ['0.0001', '0.0001'],
]);

it('rejects invalid input with an error on the field, no toast and no change', function (array $override, string $field) {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)
        ->from(route('materials.index'))
        ->post(route('materials.restocks.store', $button), validRestockPayload($override))
        ->assertRedirect(route('materials.index'))
        ->assertSessionHasErrors($field)
        ->assertInertiaFlashMissing('toast');

    expect($button->fresh()->quantity_on_hand)->toBe('900.0000')
        ->and($button->fresh()->unit_cost)->toBe('0.3500')
        ->and(StockMovement::query()->count())->toBe(0);
})->with('invalidRestockInput');

it('accepts the boundary values', function (array $override, string $sku = 'MAT-BTN-017') {
    actingAs($this->user)
        ->post(route('materials.restocks.store', previewMaterial($sku)), validRestockPayload($override))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast');

    expect(StockMovement::query()->where('material_id', previewMaterial($sku)->id)->where('type', 'restock')->count())->toBe(1);
})->with('validRestockBoundaries');

it('rejects only the invalid fields', function () {
    actingAs($this->user)
        ->post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload(['quantity' => '0', 'received_on' => '2031-01-01']))
        ->assertSessionHasErrors(['quantity', 'received_on'])
        ->assertSessionDoesntHaveErrors(['unit_price', 'reference', 'update_unit_cost']);
});

it('answers with an error on the quantity, not a server error, when the stock would not fit its column', function () {
    $button = previewMaterial('MAT-BTN-017');

    actingAs($this->user)
        ->post(route('materials.restocks.store', $button), validRestockPayload(['quantity' => '9999999999.9999']))
        ->assertSessionHasErrors('quantity');

    expect($button->fresh()->quantity_on_hand)->toBe('900.0000')
        ->and(StockMovement::query()->count())->toBe(0);
});

it('answers 404 for a deleted material before it validates anything', function () {
    $material = previewMaterial('MAT-BTN-017');
    $material->delete();

    actingAs($this->user)->post(route('materials.restocks.store', $material->id), ['quantity' => '0'])
        ->assertNotFound()
        ->assertSessionHasNoErrors();

    expect(Material::withTrashed()->findOrFail($material->id)->quantity_on_hand)->toBe('900.0000');
});

it('no longer needs the preview ability, which production denies', function () {
    Gate::define('access-preview', fn (User $user): bool => false);

    actingAs($this->user)->post(route('materials.restocks.store', previewMaterial('MAT-BTN-017')), validRestockPayload())
        ->assertSessionHasNoErrors();

    expect(previewMaterial('MAT-BTN-017')->quantity_on_hand)->toBe('1400.0000');
});
