<?php

use App\Enums\StockMovementType;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Policies\ClientPolicy;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The clients table and the orders put away (INV-4-a, ADR-0021)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. To see what it does to orders recorded before clients, the tests roll it back, make
| orders the old way, and run it again.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** What putting the old orders away must leave alone: every row of these tables, as stored. */
function untouchedByClients(): array
{
    return collect(['order_lines', 'order_line_sizes', 'order_line_cuts', 'order_line_cut_materials', 'stock_movements', 'materials', 'sales', 'product_size_stocks'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all()])
        ->all();
}

it('puts away every order recorded before clients, and changes nothing else: lines, cuts, materials, their history, sales, finished stock', function () {
    $other = Workshop::factory()->create();
    $cut = bookSizedOrder(['S' => 10, 'M' => 20, 'L' => 15, 'XL' => 5], ['M' => [10, 0, 0]]);
    OrderLineCut::factory()->create(['order_line_id' => $cut->lines()->value('id'), 'units' => 10]);
    StockMovement::factory()->create(['material_id' => demoMaterial('FAB-DNM-001')->id, 'type' => StockMovementType::Cutting, 'order_id' => $cut->id, 'product_id' => demoProduct('PRD-JKT-001')->id, 'units' => 10]);
    $plain = bookOrder(['client' => 'Mehdi Jaziri']);
    $deletedBefore = tap(bookOrder(['client' => 'Rania Hamdi']))->delete();
    $foreign = CurrentWorkshop::run($other, fn (): Order => Order::factory()->create(['client' => 'Boutique Sfax']));
    $deletedAt = Order::withTrashed()->whereKey($deletedBefore->id)->value('deleted_at');

    clientsMigration()->down();
    $before = untouchedByClients();

    expect(Schema::hasTable('clients'))->toBeFalse()
        ->and(Schema::hasColumn('orders', 'client_id'))->toBeFalse();

    clientsMigration()->up();

    expect(DB::table('orders')->whereNull('deleted_at')->count())->toBe(0)
        ->and(DB::table('orders')->whereIn('id', [$cut->id, $plain->id, $foreign->id])->pluck('client', 'id')->all())
        ->toBe([$cut->id => 'Boutique Lina', $plain->id => 'Mehdi Jaziri', $foreign->id => 'Boutique Sfax'])
        ->and(DB::table('orders')->whereNotNull('client_id')->count())->toBe(0)
        ->and(Order::withTrashed()->whereKey($deletedBefore->id)->value('deleted_at'))->toEqual($deletedAt)
        ->and(untouchedByClients())->toBe($before)
        ->and(DB::table('clients')->count())->toBe(0);

    // The Orders list shows none of them; the materials' history still names the order's client.
    actingAs($this->user)->get(route('orders.index', ['status' => 'all']))->assertOk();

    expect(pageProp(actingAs($this->user)->get(route('orders.index', ['status' => 'all'])), 'orders.data'))->toBe([]);
});

it('rolls back to the orders table as it was, with no clients', function () {
    clientsMigration()->down();

    expect(Schema::hasTable('clients'))->toBeFalse()
        ->and(Schema::hasColumn('orders', 'client_id'))->toBeFalse()
        ->and(Schema::hasColumns('orders', ['client', 'ordered_on', 'note']))->toBeTrue();

    clientsMigration()->up();

    expect(Schema::hasColumns('clients', ['workshop_id', 'name', 'phone', 'phone_key', 'city', 'source', 'source_other', 'user_id']))->toBeTrue()
        ->and(Schema::hasColumn('orders', 'client_id'))->toBeTrue()
        ->and(Schema::hasIndex('clients', ['workshop_id', 'phone_key'], 'unique'))->toBeTrue();
});

it('keeps a phone once per workshop, whatever way it was typed, and lets two workshops each have it', function () {
    $other = Workshop::factory()->create();
    Client::factory()->create(['name' => 'Amira Ben Salah', 'phone' => '22 345 678']);
    CurrentWorkshop::run($other, fn () => Client::factory()->create(['name' => 'Amira B.', 'phone' => '+216 22 345 678']));

    expect(Client::query()->where('phone_key', '21622345678')->value('phone'))->toBe('22 345 678')
        ->and(fn () => DB::transaction(fn () => Client::factory()->create(['phone' => '22.345.678'])))
        ->toThrow(QueryException::class)
        ->and(fn () => Client::factory()->create(['phone' => '12345']))->toThrow(InvalidArgumentException::class);
});

it('makes PostgreSQL refuse an order of another workshop\'s client, and an order shown without a client', function (string $case, string $constraint) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite key and the CHECK are PostgreSQL-only.');
    }

    $order = Order::factory()->create();
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn (): Client => Client::factory()->create());
    $change = $case === 'foreign' ? ['client_id' => $foreign->id] : ['client_id' => null];

    expect(fn () => DB::transaction(fn () => DB::table('orders')->where('id', $order->id)->update($change)))
        ->toThrow(QueryException::class, $constraint);

    // Put away, an order may have no client.
    DB::table('orders')->where('id', $order->id)->update(['client_id' => null, 'deleted_at' => now()]);

    expect(DB::table('orders')->where('id', $order->id)->value('client_id'))->toBeNull();
})->with([
    'another workshop\'s client' => ['foreign', 'orders_workshop_client_foreign'],
    'no client' => ['none', 'orders_client_shown_check'],
]);

it('gives every factory order a client of its own workshop, named like the order', function () {
    $order = Order::factory()->create(['client' => 'Boutique Lina']);

    expect($order->clientRecord->name)->toBe('Boutique Lina')
        ->and($order->clientRecord->workshop_id)->toBe($order->workshop_id)
        ->and(OrderLine::query()->count())->toBe(0);
});

it('lets any member of the workshop see and add its clients, and hides another workshop\'s client as a 404', function () {
    $mine = Client::factory()->create();
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn (): Client => Client::factory()->create());
    $policy = new ClientPolicy;
    $member = User::factory()->create(['workshop_id' => $this->user->workshop_id]);

    expect($policy->viewAny($member))->toBeTrue()
        ->and($policy->create($member))->toBeTrue()
        ->and($policy->view($member, $mine)->allowed())->toBeTrue()
        ->and($policy->view($member, $foreign)->status())->toBe(404);
});
