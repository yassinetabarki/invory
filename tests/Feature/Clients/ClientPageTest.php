<?php

use App\Models\Client;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The client page (INV-4-c, AC-4)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => $this->user = prepareDemo($this));

it('shows Amira with her figures and her three orders, newest first, all orders by default', function () {
    $clients = demoClientsWithOrders();
    $amira = $clients['Amira Ben Salah'];

    $response = actingAs($this->user)->get(route('clients.show', $amira))->assertOk();

    expect(pageProp($response, 'client'))->toMatchArray([
        'id' => $amira->id, 'name' => 'Amira Ben Salah', 'orders' => 3, 'open_orders' => 1, 'last_order_on' => '2026-10-28',
    ])
        ->and(array_column(pageProp($response, 'orders.data'), 'ordered_on'))->toBe(['2026-10-28', '2026-10-14', '2026-09-01'])
        ->and(array_column(pageProp($response, 'orders.data'), 'status_label'))->toBe(['Not started', 'Delivered', 'Delivered'])
        ->and(pageProp($response, 'filters'))->toBe(['status' => 'all'])
        ->and(array_column(pageProp($response, 'statuses'), 'label'))->toBe(['Open orders', 'Delivered', 'Cancelled', 'All orders']);
});

it('filters her orders by status', function (string $status, array $dates) {
    $amira = amiraWithOrders();

    $response = actingAs($this->user)->get(route('clients.show', [$amira, 'status' => $status]))->assertOk();

    expect(array_column(pageProp($response, 'orders.data'), 'ordered_on'))->toBe($dates);
})->with([
    'open' => ['open', ['2026-10-28']],
    'delivered' => ['delivered', ['2026-10-14', '2026-09-01']],
    'cancelled' => ['cancelled', []],
]);

it('shows a client with no orders', function () {
    $ines = Client::factory()->create(['name' => 'Ines Mrad']);

    $response = actingAs($this->user)->get(route('clients.show', $ines))->assertOk();

    expect(pageProp($response, 'client'))->toMatchArray(['orders' => 0, 'open_orders' => 0, 'last_order_on' => null])
        ->and(pageProp($response, 'orders.data'))->toBe([]);
});

it('answers 404 for another workshop\'s client and a malformed id, and sends a guest to sign in', function () {
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn (): Client => Client::factory()->create());

    actingAs($this->user)->get(route('clients.show', $foreign))->assertNotFound();
    actingAs($this->user)->get('/clients/abc')->assertNotFound();

    auth()->logout();

    $this->get(route('clients.show', $foreign))->assertRedirect(route('login'));
});
