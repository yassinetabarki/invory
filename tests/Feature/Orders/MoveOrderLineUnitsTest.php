<?php

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Moving units of an order line on and back (S-49-b: AC-2, AC-3, AC-5 and AC-7 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order of the card: Denim Jacket 50, Denim Jeans 5-pocket 30. The limits (AC-4) are in MoveLimitsTest. The guards of
| the route (guest, unverified, digits, another workshop's order) are walked by RoutesTest and CrossWorkshopAccessTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** Posts a move of the line at that position (0 = Denim Jacket), coming from the order's own page. */
function moveUnits(Order $order, int $position, string $move, int|string $units): TestResponse
{
    $line = $order->lines()->get()[$position];

    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $line]), ['move' => $move, 'units' => $units]);
}

/**
 * The saved counts of each line in position order, each `[to start, in cutting, ready to ship, shipped]`.
 *
 * @return list<array{int, int, int, int}>
 */
function lineStages(Order $order): array
{
    return OrderLine::query()->where('order_id', $order->id)->orderBy('position')->get()
        ->map(static fn (OrderLine $line): array => [$line->to_start, $line->in_cutting, $line->ready_to_ship, $line->shipped])
        ->all();
}

/** The `order` prop of the order's page. */
function savedOrder(Order $order): array
{
    return pageProp(actingAs(test()->user)->get(route('orders.show', $order))->assertOk(), 'order');
}

/**
 * Each move, for 2 units, from 20 to start, 20 in cutting, 10 ready to ship and 5 shipped: the counts afterwards.
 *
 * @return array<string, array{string, array{int, int, int, int}}>
 */
function stageMoveRows(): array
{
    return [
        'mark_ready' => ['mark_ready', [15, 18, 12, 5]],
        'ship' => ['ship', [15, 20, 8, 7]],
        'back_to_start' => ['back_to_start', [17, 18, 10, 5]],
        'back_to_cutting' => ['back_to_cutting', [15, 22, 8, 5]],
        'back_to_ready' => ['back_to_ready', [15, 20, 12, 3]],
    ];
}

it('moves units on line by line and the order follows (AC-2, AC-3)', function () {
    $order = bookOrder();

    // Start cutting 30 of the 50 jackets.
    moveUnits($order, 0, 'start_cutting', 30)
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '30 units of Denim Jacket moved to In cutting.']);
    expect(lineStages($order))->toBe([[20, 30, 0, 0], [30, 0, 0, 0]])
        ->and(savedOrder($order)['status'])->toBe('in_progress');

    moveUnits($order, 0, 'mark_ready', 10)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '10 units of Denim Jacket moved to Ready to ship.']);
    expect(lineStages($order))->toBe([[20, 20, 10, 0], [30, 0, 0, 0]]);

    moveUnits($order, 0, 'ship', 10)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '10 units of Denim Jacket moved to Shipped.']);
    $saved = savedOrder($order);

    expect(lineStages($order))->toBe([[20, 20, 0, 10], [30, 0, 0, 0]])
        ->and($saved['lines'][0])->toMatchArray(['shipped' => 10, 'quantity' => 50, 'status' => 'in_progress'])
        ->and($saved)->toMatchArray(['shipped' => 10, 'units' => 80, 'status' => 'in_progress'])
        ->and($saved['lines'][1])->toMatchArray(['to_start' => 30, 'status' => 'not_started']);

    // One unit reads in the singular.
    moveUnits($order, 1, 'start_cutting', 1)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 unit of Denim Jeans 5-pocket moved to In cutting.']);
    expect(lineStages($order))->toBe([[20, 20, 0, 10], [29, 1, 0, 0]]);
});

it('moves a product archived or set back to draft since the order was taken as usual (AC-2)', function (string $status) {
    $order = bookOrder();
    demoProduct('PRD-JKT-001')->update(['status' => $status]);

    moveUnits($order, 0, 'start_cutting', 30)->assertSessionHasNoErrors();

    expect(lineStages($order)[0])->toBe([20, 30, 0, 0]);
})->with(['archived', 'draft']);

it('refuses Start cutting on a deleted product and moves nothing (AC-2)', function () {
    $order = bookOrderAtStages([[50, 20, 10, 5], [30, 0, 0, 0]]);
    demoProduct('PRD-JKT-001')->delete();

    moveUnits($order, 0, 'start_cutting', 5)
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors(['units' => 'Denim Jacket was deleted, so it can\'t be cut.']);

    expect(lineStages($order)[0])->toBe([15, 20, 10, 5]);
});

it('still moves a deleted product\'s units on and back (AC-2)', function (string $move, array $expected) {
    $order = bookOrderAtStages([[50, 20, 10, 5], [30, 0, 0, 0]]);
    demoProduct('PRD-JKT-001')->delete();

    moveUnits($order, 0, $move, 2)->assertSessionHasNoErrors();

    expect(lineStages($order)[0])->toBe($expected);
})->with(stageMoveRows());

it('moves units back to the stage before, with the card\'s messages (AC-5)', function (string $move, array $expected, string $message) {
    $order = bookOrderAtStages([[60, 20, 10, 10], [30, 0, 0, 0]]);

    moveUnits($order, 0, $move, 5)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $message]);

    expect(lineStages($order)[0])->toBe($expected);
})->with([
    'from In cutting' => ['back_to_start', [25, 15, 10, 10], '5 units of Denim Jacket moved back to To start.'],
    'from Ready to ship' => ['back_to_cutting', [20, 25, 5, 10], '5 units of Denim Jacket moved back to In cutting.'],
    'from Shipped' => ['back_to_ready', [20, 20, 15, 5], '5 units of Denim Jacket moved back to Ready to ship.'],
]);

it('makes a shipped line and order In progress again once units move back (AC-5)', function () {
    $order = bookOrderAtStages([[50, 0, 0, 50], [30, 0, 0, 30]]);

    expect(savedOrder($order))->toMatchArray(['status' => 'shipped', 'shipped' => 80]);

    moveUnits($order, 0, 'back_to_ready', 5)->assertSessionHasNoErrors();
    $saved = savedOrder($order);

    expect($saved['lines'][0])->toMatchArray(['status' => 'in_progress', 'shipped' => 45, 'ready_to_ship' => 5])
        ->and($saved['lines'][1]['status'])->toBe('shipped')
        ->and($saved)->toMatchArray(['status' => 'in_progress', 'shipped' => 75]);
});

// Move back to To start is left out: from S-53-c on it returns materials, so it is not stock-neutral.
it('leaves materials, stock history, sales and the dashboard alone on Mark ready, Ship and their Move back (AC-7)', function (string $move, array $expected) {
    $order = bookOrderAtStages([[50, 20, 10, 5], [30, 0, 0, 0]]);
    $before = figuresOrdersLeaveAlone();

    moveUnits($order, 0, $move, 2)->assertSessionHasNoErrors();

    expect(lineStages($order)[0])->toBe($expected)
        ->and(figuresOrdersLeaveAlone())->toBe($before);
})->with(array_diff_key(stageMoveRows(), ['back_to_start' => true]));

it('answers 404 for a line of another order, and moves nothing', function () {
    $mine = bookOrder();
    $other = bookOrder(['client' => 'Atelier Noor']);
    $theirLine = $other->lines()->get()[0];

    actingAs($this->user)->post(route('orders.lines.moves.store', [$mine, $theirLine]), ['move' => 'start_cutting', 'units' => 5])
        ->assertNotFound();
    actingAs($this->user)->post(route('orders.lines.moves.store', [$mine, NO_SUCH_ID]), ['move' => 'start_cutting', 'units' => 5])
        ->assertNotFound();

    expect(lineStages($mine))->toBe([[50, 0, 0, 0], [30, 0, 0, 0]])
        ->and(lineStages($other))->toBe([[50, 0, 0, 0], [30, 0, 0, 0]]);
});

it('locks the order row and then the line (PostgreSQL)', function () {
    $order = bookOrder();

    $locks = queriesMatching(queryLog(fn () => moveUnits($order, 0, 'start_cutting', 5)->assertSessionHasNoErrors()), 'for update');

    expect($locks)->toHaveCount(2)
        ->and($locks[0])->toContain('"orders"')
        ->and($locks[1])->toContain('"order_lines"');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'FOR UPDATE is only emitted on PostgreSQL (SQLite has no row locks).');
