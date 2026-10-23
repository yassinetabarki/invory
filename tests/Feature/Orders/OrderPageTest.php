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
            ->where('to_start', 80)
            ->where('in_cutting', 0)
            ->where('ready_to_ship', 0)
            ->where('shipped', 0)
            ->where('status', 'not_started')
            ->where('status_label', 'Not started')
            ->where('delete_refusal', null)
            ->has('lines', 2)
            ->has('lines.0', fn (Assert $line) => $line
                ->has('id')
                ->where('product', ['id' => pid(1), 'sku' => 'PRD-JKT-001', 'name' => 'Denim Jacket', 'status' => 'active', 'deleted' => false])
                ->where('quantity', 50)
                ->where('to_start', 50)
                ->where('in_cutting', 0)
                ->where('ready_to_ship', 0)
                ->where('shipped', 0)
                ->where('started', 0)
                // Recorded before sizes (the factory default): the Denim Jacket has several sizes, the line counts as "One size" (S-60 AC-5).
                ->where('size_mode', 'before_sizes')
                ->where('sizes', [[
                    'size' => ['id' => Size::query()->where('label', 'One size')->value('id'), 'label' => 'One size'],
                    'quantity' => 50, 'to_start' => 50, 'in_cutting' => 0, 'ready_to_ship' => 0, 'shipped' => 0, 'started' => 0,
                ]])
                ->where('status', 'not_started')
                ->where('status_label', 'Not started'))
            ->has('lines.1', fn (Assert $line) => $line
                ->has('id')
                ->where('product.name', 'Denim Jeans 5-pocket')
                ->where('quantity', 30)
                ->where('to_start', 30)
                ->where('in_cutting', 0)
                ->where('ready_to_ship', 0)
                ->where('shipped', 0)
                ->where('started', 0)
                ->where('status', 'not_started')
                ->where('status_label', 'Not started')
                ->etc())));
});

it('gives each line and the order their status, and To start as what has not moved (AC-3)', function (array $stages, array $toStart, array $lineStatuses, string $orderStatus, string $label, int $shipped) {
    $order = bookOrderAtStages($stages);

    $response = orderPage($order)->assertOk();

    expect(pageProp($response, 'order.lines.*.to_start'))->toBe($toStart)
        ->and(pageProp($response, 'order.lines.*.status'))->toBe($lineStatuses)
        ->and(pageProp($response, 'order.status'))->toBe($orderStatus)
        ->and(pageProp($response, 'order.status_label'))->toBe($label)
        ->and(pageProp($response, 'order.shipped'))->toBe($shipped)
        ->and(pageProp($response, 'order.lines.0.status_label'))->toBe(['not_started' => 'Not started', 'in_progress' => 'In progress', 'shipped' => 'Shipped'][$lineStatuses[0]]);
})->with([
    'everything to start' => [[[50, 0, 0, 0], [30, 0, 0, 0]], [50, 30], ['not_started', 'not_started'], 'not_started', 'Not started', 0],
    'a unit in cutting' => [[[50, 1, 0, 0], [30, 0, 0, 0]], [49, 30], ['in_progress', 'not_started'], 'in_progress', 'In progress', 0],
    'every unit shipped but one' => [[[50, 0, 0, 50], [30, 0, 29, 1]], [0, 0], ['shipped', 'in_progress'], 'in_progress', 'In progress', 51],
    'every line fully shipped' => [[[50, 0, 0, 50], [30, 0, 0, 30]], [0, 0], ['shipped', 'shipped'], 'shipped', 'Shipped', 80],
    'one line shipped, the other not started' => [[[50, 0, 0, 50], [30, 0, 0, 0]], [0, 30], ['shipped', 'not_started'], 'in_progress', 'In progress', 50],
    'twenty, twenty, ten, ten' => [[[50, 20, 10, 10], [30, 0, 0, 0]], [10, 30], ['in_progress', 'not_started'], 'in_progress', 'In progress', 10],
]);

it('carries the refusal to delete while units are in cutting or ready to ship, and nothing otherwise (AC-6)', function (array $stages, ?string $refusal) {
    $order = bookOrderAtStages($stages);

    expect(pageProp(orderPage($order)->assertOk(), 'order.delete_refusal'))->toBe($refusal)
        ->and(pageProp(actingAs($this->user)->get(route('orders.index')), 'orders.data.0.delete_refusal'))->toBe($refusal);
})->with([
    '20 in cutting and 10 ready to ship, in two lines' => [[[50, 20, 0, 0], [30, 0, 10, 5]], 'Boutique Lina can\'t be deleted: 30 units are in cutting or ready to ship. Move them back to To start first.'],
    'one unit in cutting' => [[[50, 1, 0, 0], [30, 0, 0, 0]], 'Boutique Lina can\'t be deleted: 1 unit is in cutting or ready to ship. Move it back to To start first.'],
    'only shipped units' => [[[50, 0, 0, 50], [30, 0, 0, 12]], null],
]);
