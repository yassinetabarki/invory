<?php

use App\Enums\ProductStatus;
use App\Models\Order;
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
        ->where('filters', ['search' => null])
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
    Order::factory()->count(26)->create(['client' => 'Filler', 'ordered_on' => '2026-08-01']);

    $first = orderList();

    expect(array_slice(pageProp($first, 'orders.data.*.client'), 0, 4))->toBe(['Newest', 'Fifth, last recorded', 'Fifth, first recorded', 'Oldest'])
        ->and(pageProp($first, 'orders.data'))->toHaveCount(25)
        ->and(pageProp($first, 'orders.meta.total'))->toBe(30)
        ->and(pageProp(orderList(['page' => 2]), 'orders.data'))->toHaveCount(5)
        // The search travels with the page buttons.
        ->and(pageProp(orderList(['search' => 'fil']), 'orders.links.next'))->toContain('search=fil');
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
