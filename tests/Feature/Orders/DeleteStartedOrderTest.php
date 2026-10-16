<?php

use App\Actions\Orders\DeleteOrder;
use App\Exceptions\OrderDeleteRefusedException;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertNotSoftDeleted;
use function Pest\Laravel\assertSoftDeleted;

/*
|--------------------------------------------------------------------------
| Deleting an order that has started (S-49-c: AC-6 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order, Denim Jacket and Denim Jeans 5-pocket, with the stage counts each test sets. An order with units in cutting
| or ready to ship can't be deleted; the same text is the order's `delete_refusal` and the error toast.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** Where each line stands, `[quantity, in cutting, ready to ship, shipped]`, in position order. */
function savedCounts(Order $order): array
{
    return OrderLine::query()->where('order_id', $order->id)->orderBy('position')->get()
        ->map(static fn (OrderLine $line): array => [$line->quantity, $line->in_cutting, $line->ready_to_ship, $line->shipped])
        ->all();
}

it('refuses to delete an order with units in cutting or ready to ship, from its page and from the list (AC-6)', function (string $from, array $stages, string $message) {
    $order = bookOrderAtStages($stages);
    $page = $from === 'page' ? route('orders.show', $order) : route('orders.index', ['search' => 'lina']);

    actingAs($this->user)->from($page)->delete(route('orders.destroy', $order))
        ->assertRedirect($page)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => $message]);

    assertNotSoftDeleted($order);
    expect(savedCounts($order))->toBe($stages)
        ->and(OrderLine::query()->where('order_id', $order->id)->count())->toBe(2)
        ->and(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.delete_refusal'))->toBe($message);
})->with([
    'from the order page, 30 units' => ['page', [[50, 20, 10, 0], [30, 0, 0, 0]], "Boutique Lina can't be deleted: 30 units are in cutting or ready to ship. Move them back to To start first."],
    'from the list, 30 units' => ['list', [[50, 20, 10, 0], [30, 0, 0, 0]], "Boutique Lina can't be deleted: 30 units are in cutting or ready to ship. Move them back to To start first."],
    'from the order page, 1 unit' => ['page', [[50, 1, 0, 0], [30, 0, 0, 0]], "Boutique Lina can't be deleted: 1 unit is in cutting or ready to ship. Move it back to To start first."],
    'from the list, 1 unit ready to ship' => ['list', [[50, 0, 0, 0], [30, 0, 1, 0]], "Boutique Lina can't be deleted: 1 unit is in cutting or ready to ship. Move it back to To start first."],
]);

it('deletes an order whose units are only to start or shipped, as before (AC-6)', function (array $stages) {
    $order = bookOrderAtStages($stages);
    $list = route('orders.index', ['search' => 'lina']);

    expect(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.delete_refusal'))->toBeNull();

    actingAs($this->user)->from($list)->delete(route('orders.destroy', $order))
        ->assertRedirect($list)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order deleted.']);

    assertSoftDeleted($order);
    expect(OrderLine::query()->where('order_id', $order->id)->count())->toBe(2);
})->with([
    'only To start' => [[[50, 0, 0, 0], [30, 0, 0, 0]]],
    'only Shipped' => [[[50, 0, 0, 50], [30, 0, 0, 30]]],
    'To start and Shipped mixed' => [[[50, 0, 0, 20], [30, 0, 0, 0]]],
]);

it('refuses on the saved counts when cutting started after the page was loaded (AC-6)', function () {
    $order = bookOrder();
    $stale = Order::query()->with('lines')->findOrFail($order->id);

    // Someone else starts cutting 5 jackets after this page was loaded.
    OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->update(['in_cutting' => 5]);

    // Straight into the Action, on the stale model: the check reads the lines again under the order lock.
    expect(fn () => app(DeleteOrder::class)->handle($stale))
        ->toThrow(OrderDeleteRefusedException::class, "Boutique Lina can't be deleted: 5 units are in cutting or ready to ship. Move them back to To start first.");

    assertNotSoftDeleted($order);
    expect(savedCounts($order))->toBe([[50, 5, 0, 0], [30, 0, 0, 0]]);
});

it('locks the order row before it reads the lines (PostgreSQL)', function () {
    $order = bookOrder();

    $locks = queriesMatching(queryLog(fn () => actingAs($this->user)->delete(route('orders.destroy', $order))->assertSessionHasNoErrors()), 'for update');

    expect($locks)->toHaveCount(1)
        ->and($locks[0])->toContain('"orders"');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'FOR UPDATE is only emitted on PostgreSQL (SQLite has no row locks).');
