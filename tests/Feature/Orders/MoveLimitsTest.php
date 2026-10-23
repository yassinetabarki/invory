<?php

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A move can't take more than the stage holds (S-49-b: AC-4 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's Denim Jacket at 30 to start, 20 in cutting, 0 ready to ship, 0 shipped (quantity 50). The moves themselves are in
| MoveOrderLineUnitsTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** Posts a move of the Denim Jacket line (position 0), coming from the order's own page. */
function limitsMove(Order $order, string $move, int|string $units): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->get()[0]]), ['move' => $move, 'units' => $units]);
}

/**
 * The saved counts of the Denim Jacket line: to start, in cutting, ready to ship, shipped.
 *
 * @return array{int, int, int, int}
 */
function limitsStages(Order $order): array
{
    $line = OrderLine::query()->where('order_id', $order->id)->orderBy('position')->firstOrFail();

    return [$line->to_start, $line->in_cutting, $line->ready_to_ship, $line->shipped];
}

it('refuses a move the stage can\'t cover or the form can\'t read, with the card\'s message, and changes nothing (AC-4)', function (array $stages, string $move, int|string $units, string $field, ?string $message) {
    $order = bookOrderAtStages($stages);
    $before = limitsStages($order);

    $response = limitsMove($order, $move, $units)->assertRedirect(route('orders.show', $order));

    $message === null ? $response->assertSessionHasErrors($field) : $response->assertSessionHasErrors([$field => $message]);
    expect(limitsStages($order))->toBe($before);
})->with(function () {
    $cutting = [[50, 20, 0, 0], [30, 0, 0, 0]];
    $invalid = 'Enter a whole number of units, at least 1.';

    return [
        'more than the stage holds' => [$cutting, 'mark_ready', 25, 'units', 'You can move at most 20 units.'],
        'more than is left to start' => [$cutting, 'start_cutting', 31, 'units', 'You can move at most 30 units.'],
        'a stage holding one unit' => [[[50, 1, 0, 0], [30, 0, 0, 0]], 'mark_ready', 2, 'units', 'You can move at most 1 unit.'],
        'a stage holding nothing' => [$cutting, 'ship', 1, 'units', 'You can move at most 0 units.'],
        'a move back past what the stage holds' => [$cutting, 'back_to_start', 21, 'units', 'You can move at most 20 units.'],
        'empty' => [$cutting, 'mark_ready', '', 'units', $invalid],
        'zero' => [$cutting, 'mark_ready', 0, 'units', $invalid],
        'negative' => [$cutting, 'mark_ready', -3, 'units', $invalid],
        'a decimal' => [$cutting, 'mark_ready', '2.5', 'units', $invalid],
        'text' => [$cutting, 'mark_ready', 'abc', 'units', $invalid],
        'a move that is none of the six' => [$cutting, 'teleport', 5, 'move', null],
    ];
});

it('limits a move to the count saved now, not the one on the screen (AC-4)', function () {
    $order = bookOrderAtStages([[50, 20, 0, 0], [30, 0, 0, 0]]);

    // The screen showed 20 in cutting; someone else marks 15 of them ready meanwhile.
    // The line's counts are the sums of its size row (ADR-0016): the row is what the move reads.
    $saved = ['in_cutting' => 5, 'ready_to_ship' => 15];
    $jacket = OrderLine::query()->where('order_id', $order->id)->where('position', 0)->firstOrFail();
    OrderLine::query()->whereKey($jacket->id)->update($saved);
    $jacket->sizes()->update($saved);

    limitsMove($order, 'mark_ready', 10)
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors(['units' => 'You can move at most 5 units.']);

    // Following the redirect, the page carries the saved counts.
    $page = pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0');

    expect($page)->toMatchArray(['to_start' => 30, 'in_cutting' => 5, 'ready_to_ship' => 15, 'shipped' => 0])
        ->and(limitsStages($order))->toBe([30, 5, 15, 0]);
});
