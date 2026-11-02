<?php

use App\Models\Client;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The Clients page (INV-4-c, AC-2)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** The rows as the table reads them: name · source · city · phone · open orders · last order. */
function clientRows(array $clients): array
{
    return array_map(fn (array $c): array => [$c['name'], $c['source_label'], $c['city'], $c['phone'], $c['open_orders'], $c['last_order_on']], $clients);
}

it('lists the six clients A → Z with their source, city, phone, open orders and last order', function () {
    demoClientsWithOrders();
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => Client::factory()->create(['name' => 'Aaron Other']));

    $response = actingAs($this->user)->get(route('clients.index'))->assertOk();

    expect(clientRows(pageProp($response, 'clients.data')))->toBe([
        ['Amira Ben Salah', 'Instagram', 'Sousse', '22 345 678', 1, '2026-10-28'],
        ['Boutique Lina', 'Walk-in / shop', 'Tunis', '71 234 567', 1, '2026-10-15'],
        ['Mehdi Jaziri', 'WhatsApp', 'Ariana', '29 111 222', 0, '2026-10-12'],
        ['Nour Gharbi', 'TikTok', 'Sfax', '55 123 456', 0, '2026-10-20'],
        ['Rania Hamdi', 'Website', 'Bizerte', '50 987 654', 0, '2026-10-09'],
        ['Yassine Trabelsi', 'Facebook', 'Tunis', '98 765 432', 1, '2026-10-27'],
    ])
        ->and(pageProp($response, 'filters'))->toBe(['search' => null, 'source' => null])
        ->and(pageProp($response, 'has_clients'))->toBeTrue()
        ->and(pageProp($response, 'cities'))->toBe(['Ariana', 'Bizerte', 'Sfax', 'Sousse', 'Tunis'])
        ->and(pageProp($response, 'clients.meta.per_page'))->toBe(25);
});

it('searches by name, city or phone digits, filters by source, and keeps both in the page address', function (array $query, array $names) {
    demoClientsWithOrders();

    $response = actingAs($this->user)->get(route('clients.index', $query))->assertOk();

    expect(array_column(pageProp($response, 'clients.data'), 'name'))->toBe($names);
})->with([
    'a name' => [['search' => 'amira'], ['Amira Ben Salah']],
    'a city' => [['search' => 'sousse'], ['Amira Ben Salah']],
    'phone digits' => [['search' => '22 345'], ['Amira Ben Salah']],
    'a source' => [['source' => 'instagram'], ['Amira Ben Salah']],
    'search and source together' => [['search' => 'tunis', 'source' => 'facebook'], ['Yassine Trabelsi']],
    'no match' => [['search' => 'Amina'], []],
    'a source nobody came from' => [['source' => 'word_of_mouth'], []],
]);

it('keeps the filters in the links of the next page', function () {
    Client::factory()->count(26)->create(['source' => 'instagram']);

    $response = actingAs($this->user)->get(route('clients.index', ['source' => 'instagram']))->assertOk();

    expect(pageProp($response, 'clients.links.next'))->toContain('source=instagram')
        ->and(pageProp($response, 'clients.data'))->toHaveCount(25);
});

it('tells an empty workshop, refuses an unknown source, and sends a guest to sign in', function () {
    $response = actingAs($this->user)->get(route('clients.index'))->assertOk();

    expect(pageProp($response, 'has_clients'))->toBeFalse()
        ->and(pageProp($response, 'clients.data'))->toBe([]);

    actingAs($this->user)->from(route('clients.index'))->get(route('clients.index', ['source' => 'snapchat']))->assertSessionHasErrors('source');

    auth()->logout();

    $this->get(route('clients.index'))->assertRedirect(route('login'));
});
