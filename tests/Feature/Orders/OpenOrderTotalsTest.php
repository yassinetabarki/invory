<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The totals above the Orders list (S-50-a, AC-4 and AC-3's "no orders at all" server side)
|--------------------------------------------------------------------------
|
| The three orders of the card (`bookCardOrders()`): Boutique Lina and Concept Store Nour are open, Hotel Dar Said has shipped. The
| totals count every line of every open order of the workshop; the search and the Status filter never change them.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * @param  array<string, mixed>  $query
 */
function ordersPage(array $query = []): TestResponse
{
    return actingAs(test()->user)->get(route('orders.index', $query))->assertOk();
}

it('counts every line of every open order, whatever the search and the Status filter (AC-4)', function (array $query) {
    bookCardOrders();

    expect(pageProp(ordersPage($query), 'totals'))
        ->toBe(['open_orders' => 2, 'to_start' => 170, 'in_cutting' => 20, 'ready_to_ship' => 10]);
})->with([
    'no query' => [[]],
    'a search' => [['search' => 'Nour']],
    'shipped orders' => [['status' => 'shipped']],
    'all orders and a search' => [['status' => 'all', 'search' => 'Hotel']],
]);

it('follows a move: Mark ready with 5 on the Denim Jacket line (AC-4)', function () {
    $lina = bookCardOrders()['lina'];
    $jacket = $lina->lines()->get()[0];

    actingAs($this->user)->from(route('orders.index'))
        ->post(route('orders.lines.moves.store', [$lina, $jacket]), ['move' => 'mark_ready', 'units' => 5])
        ->assertSessionHasNoErrors();

    expect(pageProp(ordersPage(), 'totals'))
        ->toBe(['open_orders' => 2, 'to_start' => 170, 'in_cutting' => 15, 'ready_to_ship' => 15]);
});

it('reads all zeros when no order is open, and says whether the workshop has any order at all (AC-3, AC-4)', function (bool $shippedOrder, bool $hasOrders) {
    if ($shippedOrder) {
        bookShippedOrder();
    }

    $page = ordersPage();

    expect(pageProp($page, 'totals'))->toBe(['open_orders' => 0, 'to_start' => 0, 'in_cutting' => 0, 'ready_to_ship' => 0])
        ->and(pageProp($page, 'has_orders'))->toBe($hasOrders);
})->with([
    'only a shipped order' => [true, true],
    'no order at all' => [false, false],
]);

it("never counts another workshop's open orders nor a deleted open order (AC-4)", function () {
    bookCardOrders();
    bookOrder(['client' => 'Deleted client'], [[demoProduct('PRD-BLT-007'), 40]])->delete();
    CurrentWorkshop::run(Workshop::factory()->create(), function (): void {
        $order = Order::factory()->create();
        OrderLine::factory()->stages(inCutting: 7)->create(['order_id' => $order->id, 'quantity' => 90]);
    });

    expect(pageProp(ordersPage(), 'totals'))
        ->toBe(['open_orders' => 2, 'to_start' => 170, 'in_cutting' => 20, 'ready_to_ship' => 10]);
});
