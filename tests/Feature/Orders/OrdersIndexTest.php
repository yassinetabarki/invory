<?php

use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The Orders list (S-48-b, AC-1, AC-5 and AC-7 server side)
|--------------------------------------------------------------------------
|
| The demo (`prepareDemo`, clock frozen at 2026-10-08) gives the products of the card: Denim Jacket, Denim Jeans 5-pocket. Boutique Lina's
| order is `bookOrder()`. The dates of the orders are explicit, so nothing here reads the clock.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * @param  array<string, mixed>  $query
 */
function orderList(array $query = []): TestResponse
{
    return actingAs(test()->user)->get(route('orders.index', $query));
}

it('lists an order with its lines in the order entered and the sum of their units (AC-5)', function () {
    bookOrder();

    orderList()->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('orders/index')
        ->where('filters', ['search' => null, 'status' => 'open'])
        ->has('orders.data', 1)
        ->has('orders.data.0', fn (Assert $order) => $order
            ->has('id')
            ->where('client', 'Boutique Lina')
            ->where('ordered_on', '2026-10-15')
            ->where('note', 'Deliver before 30 Oct, in 2 boxes')
            ->where('units', 80)
            ->etc() // the stage counts and statuses are asserted in OrderPageTest
            ->has('lines', 2)
            ->has('lines.0', fn (Assert $line) => $line
                ->has('id')
                ->where('quantity', 50)
                ->where('product', ['id' => pid(1), 'sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'status' => 'active', 'deleted' => false])
                ->etc())
            ->has('lines.1', fn (Assert $line) => $line
                ->has('id')
                ->where('quantity', 30)
                ->where('product', ['id' => pid(5), 'sku' => 'PRD-JNS-005', 'name' => 'Denim Jeans 5-pocket', 'status' => 'active', 'deleted' => false])
                ->etc())));
});

it('shows the newest order date first, then the order recorded last, 25 to a page (AC-5)', function () {
    // Recorded in this order; the list reads: 7 Oct, then the two of 5 Oct (last recorded first), then 1 Oct.
    bookOrder(['client' => 'Oldest', 'ordered_on' => '2026-10-01']);
    bookOrder(['client' => 'Fifth, first recorded', 'ordered_on' => '2026-10-05']);
    bookOrder(['client' => 'Newest', 'ordered_on' => '2026-10-07']);
    bookOrder(['client' => 'Fifth, last recorded', 'ordered_on' => '2026-10-05']);
    // Fillers have no lines, so they are no open orders: the pagination is read over every order.
    Order::factory()->count(26)->create(['client' => 'Filler', 'ordered_on' => '2026-08-01']);

    $first = orderList(['status' => 'all']);

    expect(array_slice(pageProp($first, 'orders.data.*.client'), 0, 4))->toBe(['Newest', 'Fifth, last recorded', 'Fifth, first recorded', 'Oldest'])
        ->and(pageProp($first, 'orders.data'))->toHaveCount(25)
        ->and(pageProp($first, 'orders.meta.total'))->toBe(30)
        ->and(pageProp(orderList(['status' => 'all', 'page' => 2]), 'orders.data'))->toHaveCount(5)
        // The search and the status travel with the page buttons.
        ->and(pageProp(orderList(['search' => 'fil', 'status' => 'all']), 'orders.links.next'))->toContain('search=fil')->toContain('status=all');
});

it('finds an order by client, note, product name or SKU whatever the case, once however many lines match (AC-5)', function (string $term, array $clients) {
    bookOrder();
    bookOrder(['client' => 'Atelier Noor', 'note' => null], [[demoProduct('PRD-TOT-004'), 5]]);

    $response = orderList(['search' => $term]);

    expect(pageProp($response, 'orders.data.*.client'))->toBe($clients)
        ->and(pageProp($response, 'filters.search'))->toBe($term);
})->with([
    'client' => ['lina', ['Boutique Lina']],
    'product name, in capitals' => ['JEANS', ['Boutique Lina']],
    'product SKU' => ['PRD-JKT-001', ['Boutique Lina']],
    'note' => ['2 boxes', ['Boutique Lina']],
    'two lines match: one row' => ['denim', ['Boutique Lina']],
    'two orders match, newest recorded first' => ['o', ['Atelier Noor', 'Boutique Lina']],
    'nothing matches' => ['zzz', []],
]);

it('shows the stages and the status of each order on its row (S-50 AC-1)', function () {
    bookCardOrders();

    orderList()->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('orders.data', 2)
        ->has('orders.data.0', fn (Assert $order) => $order
            ->where('client', 'Boutique Lina')
            ->where('units', 80)
            ->where('to_start', 50)
            ->where('in_cutting', 20)
            ->where('ready_to_ship', 10)
            ->where('shipped', 0)
            ->where('status', 'in_progress')
            ->where('status_label', 'In progress')
            ->etc()));
});

it('lists open, shipped or all orders, with the search, and an order with one line shipped is still open (S-50 AC-3)', function (array $query, array $clients) {
    bookCardOrders();
    // One line fully shipped, one not started: open, "In progress" (the scope agrees with `progress()`).
    $mixed = Order::factory()->create(['client' => 'Atelier Mix', 'ordered_on' => '2026-10-01']);
    OrderLine::factory()->shipped(10)->create(['order_id' => $mixed->id, 'product_id' => pid(7), 'quantity' => 10, 'position' => 0]);
    OrderLine::factory()->create(['order_id' => $mixed->id, 'product_id' => pid(3), 'quantity' => 5, 'position' => 1]);

    $response = orderList($query);
    $statuses = ['Boutique Lina' => 'in_progress', 'Concept Store Nour' => 'not_started', 'Hotel Dar Said' => 'shipped', 'Atelier Mix' => 'in_progress'];

    expect(pageProp($response, 'orders.data.*.client'))->toBe($clients)
        ->and(pageProp($response, 'orders.data.*.status'))->toBe(array_map(fn (string $client): string => $statuses[$client], $clients))
        ->and(pageProp($response, 'filters'))->toBe(['search' => $query['search'] ?? null, 'status' => $query['status'] ?? 'open'])
        ->and(pageProp($response, 'statuses'))->toBe([
            ['value' => 'open', 'label' => 'Open orders'],
            ['value' => 'shipped', 'label' => 'Shipped'],
            ['value' => 'all', 'label' => 'All orders'],
        ]);
})->with([
    'no status: open orders' => [[], ['Boutique Lina', 'Concept Store Nour', 'Atelier Mix']],
    'open' => [['status' => 'open'], ['Boutique Lina', 'Concept Store Nour', 'Atelier Mix']],
    'shipped' => [['status' => 'shipped'], ['Hotel Dar Said']],
    'all, newest first' => [['status' => 'all'], ['Boutique Lina', 'Concept Store Nour', 'Hotel Dar Said', 'Atelier Mix']],
    'all with a search' => [['status' => 'all', 'search' => 'Denim'], ['Boutique Lina']],
    'shipped with a search that matches an open order' => [['status' => 'shipped', 'search' => 'Denim'], []],
]);

it('refuses a status the list does not offer (S-50 AC-3)', function (mixed $status) {
    orderList(['status' => $status])->assertSessionHasErrors('status');
})->with([
    'unknown' => ['cancelled'],
    'wrong case' => ['OPEN'],
    'a list' => [['open', 'all']],
]);

it('keeps the name of an archived or deleted product on its lines and flags the deleted one (AC-7)', function () {
    bookOrder();
    demoProduct('PRD-JKT-001')->update(['status' => ProductStatus::Archived]);
    demoProduct('PRD-JNS-005')->delete();

    $lines = pageProp(orderList(), 'orders.data.0.lines');

    expect(array_map(fn (array $line): array => [$line['product']['name'], $line['product']['status'], $line['product']['deleted']], $lines))
        ->toBe([['Denim Jacket', 'archived', false], ['Denim Jeans 5-pocket', 'active', true]]);

    // A deleted product is still found by name and SKU.
    expect(pageProp(orderList(['search' => 'PRD-JNS-005']), 'orders.data.*.client'))->toBe(['Boutique Lina']);
});
