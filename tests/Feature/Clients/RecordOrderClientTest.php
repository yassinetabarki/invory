<?php

use App\Enums\ClientSource;
use App\Models\Client;
use App\Models\Order;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| New order finds or adds its client by phone (INV-4-b, AC-1, AC-5, ADR-0021)
|--------------------------------------------------------------------------
|
| New order sends the Client card. The save finds the client by the phone's digits, whatever the lookup said, or adds her with the
| order, in the same transaction.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** A New order body with the Client card's fields. */
function orderWithClient(array $client): array
{
    return validOrderPayload(['client' => $client]);
}

it('records the order to the client whose phone it is, however typed, and changes none of her details', function (string $typed) {
    $amira = demoClient('Amira Ben Salah');

    actingAs($this->user)->post(route('orders.store'), orderWithClient(['phone' => $typed, 'name' => 'Amina B.', 'city' => 'Tunis', 'source' => 'tiktok']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded.']);

    $order = Order::query()->sole();

    expect($order->client_id)->toBe($amira->id)
        ->and($order->client)->toBe('Amira Ben Salah')
        ->and(Client::query()->count())->toBe(1)
        ->and($amira->fresh()->only(['name', 'city', 'phone', 'source']))
        ->toBe(['name' => 'Amira Ben Salah', 'city' => 'Sousse', 'phone' => '22 345 678', 'source' => ClientSource::Instagram]);
})->with(['22345678', '22 345 678', '+216 22 345 678', '22.345.678']);

it('adds a client for a new phone, with the order, and says so', function () {
    actingAs($this->user)->post(route('orders.store'), orderWithClient([
        'phone' => '20456789', 'name' => ' Ines Mrad ', 'city' => 'Monastir', 'source' => 'instagram', 'source_other' => 'ignored',
    ]))->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded. Ines Mrad is now one of your clients.']);

    $ines = Client::query()->sole();

    expect($ines->only(['name', 'phone', 'phone_key', 'city', 'source', 'source_other', 'user_id', 'workshop_id']))->toBe([
        'name' => 'Ines Mrad', 'phone' => '20 456 789', 'phone_key' => '21620456789', 'city' => 'Monastir', 'source' => ClientSource::Instagram,
        'source_other' => null, 'user_id' => $this->user->id, 'workshop_id' => $this->user->workshop_id,
    ])->and(Order::query()->sole()->only(['client_id', 'client']))->toBe(['client_id' => $ines->id, 'client' => 'Ines Mrad']);
});

it('keeps the text of the source Other, and no source at all', function (array $fields, ?ClientSource $source, ?string $other) {
    actingAs($this->user)->post(route('orders.store'), orderWithClient(['phone' => '20456789', 'name' => 'Ines Mrad', 'city' => 'Monastir', ...$fields]))
        ->assertSessionHasNoErrors();

    expect(Client::query()->sole()->only(['source', 'source_other']))->toBe(['source' => $source, 'source_other' => $other]);
})->with([
    'Other with a text' => [['source' => 'other', 'source_other' => ' Fashion fair Sousse '], ClientSource::Other, 'Fashion fair Sousse'],
    'Other without a text' => [['source' => 'other', 'source_other' => ''], ClientSource::Other, null],
    'no source' => [['source' => '', 'source_other' => ''], null, null],
    'no source keys at all' => [[], null, null],
]);

it('asks nothing more than the phone of a known client, and refuses an unknown source', function () {
    demoClient('Amira Ben Salah');

    actingAs($this->user)->post(route('orders.store'), orderWithClient(['phone' => '22 345 678']))->assertSessionHasNoErrors();

    actingAs($this->user)->post(route('orders.store'), orderWithClient(['phone' => '20456789', 'name' => 'Ines Mrad', 'city' => 'Monastir', 'source' => 'snapchat']))
        ->assertSessionHasErrors(['client.source']);
});

it('saves no client when the order is refused', function () {
    actingAs($this->user)->post(route('orders.store'), validOrderPayload([
        'client' => ['phone' => '20456789', 'name' => 'Ines Mrad', 'city' => 'Monastir'], 'lines' => [],
    ]))->assertSessionHasErrors(['lines']);

    expect(Client::query()->count())->toBe(0)
        ->and(Order::query()->count())->toBe(0);
});

it('finds the client someone else added between the check and the save, instead of adding her twice', function () {
    $added = false;

    // Someone else saves Amira right after this save looked for her phone, and before it adds her: the unique phone then refuses
    // this save's insert, inside its savepoint, and the save finds her instead.
    DB::listen(function (QueryExecuted $query) use (&$added): void {
        if (! $added && str_starts_with($query->sql, 'select * from "clients"') && str_contains($query->sql, '"phone_key" = ?')) {
            $added = true;
            DB::table('clients')->insert([
                'workshop_id' => $this->user->workshop_id, 'name' => 'Amira Ben Salah', 'phone' => '22 345 678', 'phone_key' => '21622345678',
                'city' => 'Sousse', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    });

    actingAs($this->user)->post(route('orders.store'), orderWithClient(['phone' => '+216 22 345 678', 'name' => 'Amira B.', 'city' => 'Sousse']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded.']);

    expect($added)->toBeTrue()
        ->and(Client::query()->where('phone_key', '21622345678')->count())->toBe(1)
        ->and(Order::query()->sole()->client)->toBe('Amira Ben Salah');
});

it('never finds another workshop\'s client: the same phone is a new client here', function () {
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => demoClient('Amira Ben Salah'));

    actingAs($this->user)->post(route('orders.store'), orderWithClient(['phone' => '22 345 678', 'name' => 'Amira Ben Salah', 'city' => 'Sousse']))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded. Amira Ben Salah is now one of your clients.']);

    expect(Order::query()->sole()->clientRecord->workshop_id)->toBe($this->user->workshop_id);
});

it('gives New order the cities, the sources and, from a client page, the client already chosen', function () {
    $amira = amiraWithOrders();
    demoClient('Boutique Lina');
    demoClient('Yassine Trabelsi');
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn () => Client::factory()->create(['city' => 'Paris']));

    $page = fn (array $query = []) => actingAs($this->user)->get(route('orders.create', $query))->assertOk();

    expect(pageProp($page(), 'cities'))->toBe(['Sousse', 'Tunis'])
        ->and(array_column(pageProp($page(), 'client_sources'), 'label'))
        ->toBe(['Instagram', 'Facebook', 'TikTok', 'WhatsApp', 'Website', 'Walk-in / shop', 'Word of mouth', 'Other'])
        ->and(pageProp($page(), 'client_preset'))->toBeNull()
        ->and(pageProp($page(['client' => $amira->id]), 'client_preset'))->toMatchArray([
            'id' => $amira->id, 'name' => 'Amira Ben Salah', 'orders' => 3, 'open_orders' => 1, 'last_order_on' => '2026-10-28',
        ])
        ->and(pageProp($page(['client' => $foreign->id]), 'client_preset'))->toBeNull()
        ->and(pageProp($page(['client' => 'x']), 'client_preset'))->toBeNull();
});
