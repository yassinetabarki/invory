<?php

use App\Models\Order;
use App\Models\Size;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The data of the order's own page (S-49-a: AC-1, AC-3 and the delete refusal text, server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order of the card: Denim Jacket 50, Denim Jeans 5-pocket 30. Guests, another workshop's order (404) and the leak scan
| are walked by the shared route tests; the saves and the delete redirects are in OrderWriteTest and DeleteOrderTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

function orderPage(Order $order): TestResponse
{
    return actingAs(test()->user)->get(route('orders.show', $order));
}

it('shows Boutique Lina\'s order with its lines in the order entered, nothing started (AC-1)', function () {
    $order = bookOrder();

    orderPage($order)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('orders/show')
        ->has('order', fn (Assert $order) => $order
            ->has('id')
            ->where('client', 'Boutique Lina')
            ->where('ordered_on', '2026-10-15')
            ->where('note', 'Deliver before 30 Oct, in 2 boxes')
            ->where('units', 80)
            ->where('to_deliver', 80)
            ->where('delivered', 0)
            ->where('cancelled', 0)
            ->where('status', 'not_started')
            ->where('status_label', 'Not started')
            ->missing('delete_refusal')
            ->where('client_id', fn (int $id): bool => $id > 0)
            ->where('client_details.name', 'Boutique Lina')
            ->etc()
            ->has('lines', 2)
            ->has('lines.0', fn (Assert $line) => $line
                ->has('id')
                ->where('product', ['id' => pid(1), 'sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'status' => 'active', 'deleted' => false])
                ->where('quantity', 50)
                ->where('to_deliver', 50)
                ->where('delivered', 0)
                ->where('cancelled', 0)
                // Recorded before sizes (the factory default): the Denim Jacket has several sizes, the line counts as "One size" (S-60 AC-5).
                ->where('size_mode', 'before_sizes')
                ->where('sizes', [[
                    // INV-13-a: the rows of before INV-13 hold their product's first colour.
                    'colour' => ['id' => demoColour('WHT')->id, 'code' => 'WHT', 'name' => 'White', 'swatch' => '#FFFFFF'],
                    'size' => ['id' => Size::query()->where('label', 'One size')->value('id'), 'label' => 'One size'],
                    'quantity' => 50, 'to_deliver' => 50, 'delivered' => 0, 'cancelled' => 0,
                ]])
                ->where('status', 'not_started')
                ->where('status_label', 'Not started'))
            ->has('lines.1', fn (Assert $line) => $line
                ->has('id')
                ->where('product.name', 'Denim Jeans 5-pocket')
                ->where('quantity', 30)
                ->where('to_deliver', 30)
                ->where('delivered', 0)
                ->where('cancelled', 0)
                ->where('status', 'not_started')
                ->where('status_label', 'Not started')
                ->etc())));
});

it('gives each line and the order their status from delivered and cancelled units (INV-14 AC-1)', function (array $counts, array $toDeliver, array $lineStatuses, string $orderStatus, string $label) {
    $order = bookOrderWithCounts($counts);

    $response = orderPage($order)->assertOk();

    expect(pageProp($response, 'order.lines.*.to_deliver'))->toBe($toDeliver)
        ->and(pageProp($response, 'order.lines.*.status'))->toBe($lineStatuses)
        ->and(pageProp($response, 'order.status'))->toBe($orderStatus)
        ->and(pageProp($response, 'order.status_label'))->toBe($label);
})->with([
    'nothing delivered' => [[[50, 0, 0], [30, 0, 0]], [50, 30], ['not_started', 'not_started'], 'not_started', 'Not started'],
    'cancelled units only' => [[[50, 0, 5], [30, 0, 0]], [45, 30], ['not_started', 'not_started'], 'not_started', 'Not started'],
    'some delivered, some to deliver' => [[[50, 5, 0], [30, 0, 5]], [45, 25], ['in_progress', 'not_started'], 'in_progress', 'In progress'],
    'all delivered' => [[[50, 50, 0], [30, 30, 0]], [0, 0], ['delivered', 'delivered'], 'delivered', 'Delivered'],
    'delivered and the rest cancelled' => [[[50, 45, 5], [30, 0, 30]], [0, 0], ['delivered', 'cancelled'], 'delivered', 'Delivered'],
    'all cancelled' => [[[50, 0, 50], [30, 0, 30]], [0, 0], ['cancelled', 'cancelled'], 'cancelled', 'Cancelled'],
]);
