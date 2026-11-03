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
| The three orders of the card (`bookCardOrders()`): Boutique Lina and Concept Store Nour are open, Hotel Dar Said is delivered. The
| totals (INV-14) count the open orders and their units To deliver; the search and the Status filter never change them.
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
        ->toBe(['open_orders' => 2, 'to_deliver' => 190]);
})->with([
    'no query' => [[]],
    'a search' => [['search' => 'Nour']],
    'delivered orders' => [['status' => 'delivered']],
    'cancelled orders' => [['status' => 'cancelled']],
    'all orders and a search' => [['status' => 'all', 'search' => 'Hotel']],
]);

it('follows a delivery and a cancel: never counts Delivered or Cancelled units (INV-14 AC-5)', function () {
    $lina = bookCardOrders()['lina'];
    $jacket = $lina->lines()->get()[0];
    setFinishedStock(demoProduct('PRD-JKT-001'), ['White' => [0, 0, 0, 0, 0, 0, 0]]);
    DB::table('product_size_stocks')->insert([
        'workshop_id' => $jacket->workshop_id, 'product_id' => pid(1), 'colour_id' => demoColour('WHT')->id,
        'size_id' => $jacket->sizes()->value('size_id'), 'units' => 5, 'created_at' => now(), 'updated_at' => now(),
    ]);

    actingAs($this->user)->post(route('orders.lines.deliveries.store', [$lina, $jacket]), ['cells' => [demoColour('WHT')->id => [$jacket->sizes()->value('size_id') => 3]]])
        ->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('orders.lines.cancellations.store', [$lina, $jacket]), ['cells' => [demoColour('WHT')->id => [$jacket->sizes()->value('size_id') => 2]]])
        ->assertSessionHasNoErrors();

    expect(pageProp(ordersPage(), 'totals'))->toBe(['open_orders' => 2, 'to_deliver' => 185]);
});

it('reads all zeros when no order is open, and says whether the workshop has any order at all (AC-3, AC-4)', function (bool $shippedOrder, bool $hasOrders) {
    if ($shippedOrder) {
        bookDeliveredOrder();
    }

    $page = ordersPage();

    expect(pageProp($page, 'totals'))->toBe(['open_orders' => 0, 'to_deliver' => 0])
        ->and(pageProp($page, 'has_orders'))->toBe($hasOrders);
})->with([
    'only a delivered order' => [true, true],
    'no order at all' => [false, false],
]);

it("never counts another workshop's open orders nor a deleted open order (AC-4)", function () {
    bookCardOrders();
    bookOrder(['client' => 'Deleted client'], [[demoProduct('PRD-BLT-007'), 40]])->delete();
    CurrentWorkshop::run(Workshop::factory()->create(), function (): void {
        $order = Order::factory()->create();
        OrderLine::factory()->delivered(7)->create(['order_id' => $order->id, 'quantity' => 90]);
    });

    expect(pageProp(ordersPage(), 'totals'))
        ->toBe(['open_orders' => 2, 'to_deliver' => 190]);
});
