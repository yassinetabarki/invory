<?php

use App\Actions\Orders\UpdateOrder;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\Orders\OrderMessages;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Editing an order that has started (S-49-c: AC-6 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order at Denim Jacket 50 (To start 20, In cutting 20, Ready to ship 10, Shipped 0) and Denim Jeans 5-pocket 30
| (nothing started). Lines are matched by product, so a swapped product reads as a removed one. Datasets name products (`jacket`,
| `jeans`, `tshirt`) because they are built before the app boots.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * A valid payload for the order with these lines, `[product key, quantity]` each, and a changed client name, so a refusal that saved
 * the order's own fields first would show.
 *
 * @param  list<array{string, int}>  $lines
 * @return array<string, mixed>
 */
function startedEditBody(array $lines): array
{
    return validOrderPayload([
        'client' => 'Changed Client',
        'lines' => array_map(static fn (array $line): array => [
            'product_id' => match ($line[0]) {
                'jacket' => pid(1),
                'tshirt' => pid(2),
                'jeans' => pid(5),
            },
            'quantity' => (string) $line[1],
        ], $lines),
    ]);
}

/**
 * Everything an edit may change, as saved: the order's own fields and each line's product, quantity, position and counts.
 *
 * @return array<string, mixed>
 */
function savedOrderFigures(Order $order): array
{
    $saved = Order::query()->findOrFail($order->id);

    return [
        'order' => ['client' => $saved->client, 'ordered_on' => $saved->ordered_on->toDateString(), 'note' => $saved->note],
        'lines' => OrderLine::query()->where('order_id', $order->id)->orderBy('position')->get()
            ->map(static fn (OrderLine $line): array => $line->only(['id', 'product_id', 'quantity', 'position', 'in_cutting', 'ready_to_ship', 'shipped']))
            ->all(),
    ];
}

/**
 * The lines in position order, each `[to start, in cutting, ready to ship, shipped]`.
 *
 * @return list<array{int, int, int, int}>
 */
function savedStages(Order $order): array
{
    return OrderLine::query()->where('order_id', $order->id)->orderBy('position')->get()
        ->map(static fn (OrderLine $line): array => [$line->to_start, $line->in_cutting, $line->ready_to_ship, $line->shipped])
        ->all();
}

dataset('started line refusals', [
    'Denim Jacket 25' => [
        [['jacket', 25], ['jeans', 30]], 'lines.0.quantity',
        "The quantity can't be less than the 30 units already in cutting, ready to ship or shipped.",
    ],
    'Denim Jacket left out' => [[['jeans', 30]], 'lines', "Denim Jacket can't be removed: 30 units have started."],
    'Denim Jacket swapped for another product' => [[['tshirt', 50], ['jeans', 30]], 'lines', "Denim Jacket can't be removed: 30 units have started."],
]);

it('refuses a started line that drops below its started units or leaves the order, and saves nothing (AC-6)', function (array $lines, string $key, string $message) {
    $order = bookOrderAtStages([[50, 20, 10, 0], [30, 0, 0, 0]]);
    $before = savedOrderFigures($order);

    actingAs($this->user)->from(route('orders.edit', $order))->put(route('orders.update', $order), startedEditBody($lines))
        ->assertRedirect(route('orders.edit', $order))
        ->assertSessionHasErrors([$key => $message]);

    expect(savedOrderFigures($order))->toBe($before);
})->with('started line refusals');

it('refuses leaving out a line that has one unit in cutting, in the singular (AC-6)', function () {
    $order = bookOrderAtStages([[50, 1, 0, 0], [30, 0, 0, 0]]);
    $before = savedOrderFigures($order);

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody([['jeans', 30]]))
        ->assertSessionHasErrors(['lines' => "Denim Jacket can't be removed: 1 unit has started."]);

    expect(savedOrderFigures($order))->toBe($before);
});

it('words the quantity message for one started unit in the singular (AC-6)', function () {
    // Not reachable through the route: a quantity below 1 is refused first by the 1 to 1,000,000 rule, so the message is proved on its own.
    expect(OrderMessages::quantityBelowStarted(1))
        ->toBe("The quantity can't be less than the 1 unit already in cutting, ready to ship or shipped.");
});

it('keeps what has not started free to change, and a started line free to grow (AC-6)', function (array $lines, array $stages) {
    $order = bookOrderAtStages([[50, 20, 10, 0], [30, 0, 0, 0]]);

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody($lines))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors();

    expect(savedStages($order))->toBe($stages)
        ->and(Order::query()->findOrFail($order->id)->client)->toBe('Changed Client');
})->with([
    'Denim Jacket 40' => [[['jacket', 40], ['jeans', 30]], [[10, 20, 10, 0], [30, 0, 0, 0]]],
    'Denim Jeans swapped' => [[['jacket', 50], ['tshirt', 5]], [[20, 20, 10, 0], [5, 0, 0, 0]]],
    'Denim Jeans removed' => [[['jacket', 50]], [[20, 20, 10, 0]]],
    'Denim Jeans changed' => [[['jacket', 50], ['jeans', 12]], [[20, 20, 10, 0], [12, 0, 0, 0]]],
    'a new line added' => [[['jacket', 50], ['jeans', 30], ['tshirt', 7]], [[20, 20, 10, 0], [30, 0, 0, 0], [7, 0, 0, 0]]],
]);

it('puts a fully shipped line back In progress when its quantity is raised from 50 to 60 (AC-6)', function () {
    $order = bookOrderAtStages([[50, 0, 0, 50], [30, 0, 0, 30]]);

    expect(Order::query()->with('lines')->findOrFail($order->id)->progress()->value)->toBe('shipped');

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody([['jacket', 60], ['jeans', 30]]))
        ->assertSessionHasNoErrors();

    $saved = Order::query()->with('lines')->findOrFail($order->id);

    expect(savedStages($order))->toBe([[10, 0, 0, 50], [0, 0, 0, 30]])
        ->and($saved->lines[0]->progress()->value)->toBe('in_progress')
        ->and($saved->progress()->value)->toBe('in_progress');
});

it('refuses on the saved counts when units started after the form was opened (AC-6)', function (array $lines, string $key, string $message) {
    $order = bookOrder();
    $before = savedOrderFigures($order);

    // The form was opened here, with nothing started; then someone else started 30 jackets.
    OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->update(['in_cutting' => 30]);
    $started = savedOrderFigures($order);

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody($lines))
        ->assertSessionHasErrors([$key => $message]);

    expect($before['lines'][0]['in_cutting'])->toBe(0)
        ->and(savedOrderFigures($order))->toBe($started);
})->with('started line refusals');

it('checks again under the order lock, on a model loaded before the units started (AC-6)', function (array $lines, string $key, string $message) {
    $order = bookOrder();
    $stale = Order::query()->with('lines')->findOrFail($order->id);

    OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->update(['in_cutting' => 30]);
    $started = savedOrderFigures($order);
    $data = startedEditBody($lines);

    // Straight into the Action: the form request, whose check runs on unlocked data, is out of the picture.
    $refusal = null;

    try {
        app(UpdateOrder::class)->handle($stale, $data);
    } catch (ValidationException $e) {
        $refusal = $e->errors();
    }

    expect($refusal)->toBe([$key => [$message]])
        ->and(savedOrderFigures($order))->toBe($started);
})->with('started line refusals');
