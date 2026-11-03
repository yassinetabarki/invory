<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Edit order's client, the order page's client line, the Orders list's Client filter (INV-4-d, AC-6, AC-7)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** Edit order's body for the order: its own lines and date, with this client. */
function editWithClient(Order $order, mixed $clientId): array
{
    return [
        'client_id' => $clientId,
        'ordered_on' => '2026-10-08',
        'note' => '',
        'lines' => [['product_id' => pid(4), 'units' => orderUnits(pid(4), ['One size' => '2'])]],
    ];
}

it('moves the order to another client, and the order keeps her name', function () {
    $amira = amiraWithOrders();
    $lina = demoClient('Boutique Lina');
    $order = Order::query()->where('client_id', $amira->id)->where('ordered_on', '2026-10-28')->sole();

    actingAs($this->user)->put(route('orders.update', $order), editWithClient($order, $lina->id))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order updated.']);

    expect($order->fresh()->only(['client_id', 'client']))->toBe(['client_id' => $lina->id, 'client' => 'Boutique Lina']);
});

it('asks to choose a client when none, another workshop\'s or an unknown one is sent', function (Closure $clientId) {
    $amira = amiraWithOrders();
    $order = Order::query()->where('client_id', $amira->id)->firstOrFail();

    actingAs($this->user)->from(route('orders.edit', $order))->put(route('orders.update', $order), editWithClient($order, $clientId()))
        ->assertRedirect(route('orders.edit', $order))
        ->assertSessionHasErrors(['client_id' => 'Choose a client.']);

    expect($order->fresh()->client_id)->toBe($amira->id);
})->with([
    'none' => [fn () => ''],
    'another workshop\'s' => [fn () => CurrentWorkshop::run(Workshop::factory()->create(), fn (): int => Client::factory()->create()->id)],
    'an unknown id' => [fn () => 999999],
    'not a number' => [fn () => 'amira'],
]);

it('sends the order page who the client is', function () {
    $amira = amiraWithOrders();
    $order = Order::query()->where('client_id', $amira->id)->firstOrFail();

    expect(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.client_details'))->toBe([
        'id' => $amira->id, 'name' => 'Amira Ben Salah', 'phone' => '22 345 678', 'city' => 'Sousse',
        'source' => 'instagram', 'source_label' => 'Instagram', 'source_other' => null,
    ])
        ->and(pageProp(actingAs($this->user)->get(route('orders.show', $order)), 'order.client_id'))->toBe($amira->id);
});

it('gives Edit order the client\'s id, and the cities only when the Add client window asks for them', function () {
    $amira = amiraWithOrders();
    $order = Order::query()->where('client_id', $amira->id)->firstOrFail();
    $edit = actingAs($this->user)->get(route('orders.edit', $order))->assertOk();

    expect(pageProp($edit, 'order.client_id'))->toBe($amira->id)
        ->and(pageProp($edit, 'order.client_details'))->toBeNull()
        ->and(pageProp($edit, 'cities'))->toBeNull()
        ->and(array_column(pageProp($edit, 'client_sources'), 'value'))->toContain('instagram');

    $edit->assertInertia(fn ($page) => $page->reloadOnly('cities', fn ($reloaded) => $reloaded->where('cities', ['Sousse'])));
});

it('leaves the client details out of the Orders list and the client page: they read the name the order keeps', function () {
    $amira = amiraWithOrders();

    expect(array_column(pageProp(actingAs($this->user)->get(route('orders.index', ['status' => 'all'])), 'orders.data'), 'client_details'))->toBe([null, null, null])
        ->and(array_column(pageProp(actingAs($this->user)->get(route('clients.show', $amira)), 'orders.data'), 'client_id'))->toBe([$amira->id, $amira->id, $amira->id]);
});

it('filters the Orders list by client, combined with the search and the status, and keeps the totals', function () {
    $clients = demoClientsWithOrders();
    $amira = $clients['Amira Ben Salah'];
    $all = pageProp(actingAs($this->user)->get(route('orders.index')), 'totals');

    $response = actingAs($this->user)->get(route('orders.index', ['client' => $amira->id]))->assertOk();

    expect(array_column(pageProp($response, 'orders.data'), 'ordered_on'))->toBe(['2026-10-28'])
        ->and(pageProp($response, 'filters'))->toBe(['search' => null, 'status' => 'open', 'client' => $amira->id])
        ->and(pageProp($response, 'client_filter.name'))->toBe('Amira Ben Salah')
        ->and(pageProp($response, 'totals'))->toBe($all);

    $shipped = actingAs($this->user)->get(route('orders.index', ['client' => $amira->id, 'status' => 'delivered']));

    expect(array_column(pageProp($shipped, 'orders.data'), 'ordered_on'))->toBe(['2026-10-14', '2026-09-01'])
        ->and(pageProp($shipped, 'orders.links.next'))->toBeNull();

    $searched = actingAs($this->user)->get(route('orders.index', ['client' => $amira->id, 'status' => 'all', 'search' => 'boutique']));

    expect(pageProp($searched, 'orders.data'))->toBe([]);
});

it('lists every order for a client id that is not a client of the workshop', function () {
    demoClientsWithOrders();
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn (): Client => Client::factory()->create());

    $response = actingAs($this->user)->get(route('orders.index', ['client' => $foreign->id]))->assertOk();

    expect(pageProp($response, 'orders.data'))->toHaveCount(3)
        ->and(pageProp($response, 'filters.client'))->toBeNull()
        ->and(pageProp($response, 'client_filter'))->toBeNull();
});
