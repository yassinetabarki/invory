<?php

use App\Models\Client;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| New order's lookup (INV-4-b, AC-5, ADR-0009 rule 5)
|--------------------------------------------------------------------------
|
| `GET clients/lookup?phone=` when Phone loses focus, `?name=` for the "already a client named" note. JSON, read-only, a hint only.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

it('finds Amira by her phone however typed, with her orders, open orders and last order', function (string $typed) {
    $amira = amiraWithOrders();

    actingAs($this->user)->getJson(route('clients.lookup', ['phone' => $typed]))
        ->assertOk()
        ->assertExactJson(['client' => [
            'id' => $amira->id, 'name' => 'Amira Ben Salah', 'phone' => '22 345 678', 'city' => 'Sousse',
            'source' => 'instagram', 'source_label' => 'Instagram', 'source_other' => null,
            'orders' => 3, 'open_orders' => 1, 'last_order_on' => '2026-10-28',
        ]]);
})->with(['22345678', '+216 22 345 678', '22.345.678', '0021622345678']);

it('finds a client by her name ignoring capitals and spaces around, the first A → Z then the oldest', function () {
    $amira = demoClient('Amira Ben Salah');
    Client::factory()->create(['name' => 'Amira Ben Salah', 'city' => 'Tunis']);

    $found = actingAs($this->user)->getJson(route('clients.lookup', ['name' => '  amira BEN salah ']))->assertOk()->json('client');

    expect($found['id'])->toBe($amira->id)
        ->and($found['orders'])->toBe(0)
        ->and($found['last_order_on'])->toBeNull();
});

it('answers no client for a new phone, a phone Invory does not know, a name nobody has, and another workshop\'s client', function (array $query) {
    demoClient('Boutique Lina');
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => demoClient('Amira Ben Salah'));

    actingAs($this->user)->getJson(route('clients.lookup', $query))->assertOk()->assertExactJson(['client' => null]);
})->with([
    'a new phone' => [['phone' => '20 456 789']],
    'seven digits' => [['phone' => '2234567']],
    'a name nobody has' => [['name' => 'Ines Mrad']],
    'another workshop\'s phone' => [['phone' => '22 345 678']],
    'another workshop\'s name' => [['name' => 'Amira Ben Salah']],
]);

it('asks for a phone or a name, and sends a guest to sign in', function () {
    actingAs($this->user)->getJson(route('clients.lookup'))->assertUnprocessable()->assertJsonValidationErrors(['phone', 'name']);

    auth()->logout();

    $this->get(route('clients.lookup', ['phone' => '22 345 678']))->assertRedirect(route('login'));
});
