<?php

use App\Actions\Orders\UpdateOrder;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Services\Orders\OrderMessages;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Editing an order that has started (S-49-c: AC-6 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order at Denim Jacket 50 (To start 20, In cutting 20, Ready to ship 10, Shipped 0) and Denim Jeans 5-pocket 30
| (nothing started), both recorded before sizes: they keep their single quantity. Lines are matched by product, so a swapped product
| reads as a removed one. Datasets name products (`jacket`, `jeans`, `tshirt`) because they are built before the app boots.
|
| S-61 AC-5 (the last test) edits a Denim Jacket counted by size, S 10 · M 20 · L 15 · XL 5, with 10 units of M started.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * A valid payload for the order with these lines, `[product key, quantity]` each, and a changed client name, so a refusal that saved
 * the order's own fields first would show. INV-13: the saved jacket and jeans lines were recorded before sizes, so their quantity goes
 * on "One size" in their first colour; a new T-shirt line is counted by size and gets M.
 *
 * @param  list<array{string, int}>  $lines
 * @return array<string, mixed>
 */
function startedEditBody(array $lines): array
{
    return validOrderPayload([
        'lines' => array_map(static fn (array $line): array => [
            'product_id' => $id = match ($line[0]) {
                'jacket' => pid(1),
                'tshirt' => pid(2),
                'jeans' => pid(5),
            },
            'units' => $line[0] === 'tshirt'
                ? orderUnits($id, ['M' => (string) $line[1]])
                : orderUnits($id, ['One size' => (string) $line[1]], list: 'one_size'),
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
            ->map(static fn (OrderLine $line): array => $line->only(['id', 'product_id', 'quantity', 'position', 'delivered', 'cancelled']))
            ->all(),
    ];
}

/**
 * The lines in position order, each `[to deliver, delivered, cancelled]` (INV-14).
 *
 * @return list<array{int, int, int}>
 */
function savedStages(Order $order): array
{
    return OrderLine::query()->where('order_id', $order->id)->orderBy('position')->get()
        ->map(static fn (OrderLine $line): array => [$line->to_deliver, $line->delivered, $line->cancelled])
        ->all();
}

/** A dataset's error key: 'jacket cell' is the one cell of the jacket's line (first colour, "One size"); anything else as given. */
function startedKey(string $key): string
{
    return $key === 'jacket cell' ? unitsKey(0, orderUnits(pid(1), ['One size' => 0], list: 'one_size')) : $key;
}

dataset('started line refusals', [
    'Denim Jacket 25' => [
        [['jacket', 25], ['jeans', 30]], 'jacket cell',
        "White can't be less than the 30 units already delivered or cancelled.",
    ],
    'Denim Jacket left out' => [[['jeans', 30]], 'lines', "Denim Jacket can't be removed: 30 units are delivered or cancelled."],
    'Denim Jacket swapped for another product' => [[['tshirt', 50], ['jeans', 30]], 'lines', "Denim Jacket can't be removed: 30 units are delivered or cancelled."],
]);

it('refuses a line that drops below its delivered and cancelled units or leaves the order, and saves nothing (INV-14 AC-6)', function (array $lines, string $key, string $message) {
    $order = bookOrderWithCounts([[50, 20, 10], [30, 0, 0]]);
    $before = savedOrderFigures($order);

    $key = startedKey($key);

    actingAs($this->user)->from(route('orders.edit', $order))->put(route('orders.update', $order), startedEditBody($lines))
        ->assertRedirect(route('orders.edit', $order))
        ->assertSessionHasErrors([$key => $message]);

    expect(savedOrderFigures($order))->toBe($before);
})->with('started line refusals');

it('refuses leaving out a line that has one unit cancelled, in the singular (INV-14 AC-6)', function () {
    $order = bookOrderWithCounts([[50, 0, 1], [30, 0, 0]]);
    $before = savedOrderFigures($order);

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody([['jeans', 30]]))
        ->assertSessionHasErrors(['lines' => "Denim Jacket can't be removed: 1 unit is delivered or cancelled."]);

    expect(savedOrderFigures($order))->toBe($before);
});

it('words the cell message for one unit in the singular (INV-14 AC-6)', function () {
    expect(OrderMessages::sizeBelowSettled('Indigo M', 1))
        ->toBe("Indigo M can't be less than the 1 unit already delivered or cancelled.");
});

it('keeps what is To deliver free to change, and a line with delivered units free to grow (INV-14 AC-6)', function (array $lines, array $stages) {
    $order = bookOrderWithCounts([[50, 20, 10], [30, 0, 0]]);

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody($lines))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors();

    expect(savedStages($order))->toBe($stages)
        // Edit order keeps the order's client: a typed name is no longer read (INV-4-b; INV-4-d gives Edit order its picker).
        ->and(Order::query()->findOrFail($order->id)->client)->toBe('Boutique Lina');
})->with([
    'Denim Jacket 40' => [[['jacket', 40], ['jeans', 30]], [[10, 20, 10], [30, 0, 0]]],
    'Denim Jeans swapped' => [[['jacket', 50], ['tshirt', 5]], [[20, 20, 10], [5, 0, 0]]],
    'Denim Jeans removed' => [[['jacket', 50]], [[20, 20, 10]]],
    'Denim Jeans changed' => [[['jacket', 50], ['jeans', 12]], [[20, 20, 10], [12, 0, 0]]],
    'a new line added' => [[['jacket', 50], ['jeans', 30], ['tshirt', 7]], [[20, 20, 10], [30, 0, 0], [7, 0, 0]]],
]);

it('puts a fully delivered line back In progress when its quantity is raised from 50 to 60 (INV-14 AC-6)', function () {
    $order = bookOrderWithCounts([[50, 50, 0], [30, 30, 0]]);

    expect(Order::query()->with('lines')->findOrFail($order->id)->progress()->value)->toBe('delivered');

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody([['jacket', 60], ['jeans', 30]]))
        ->assertSessionHasNoErrors();

    $saved = Order::query()->with('lines')->findOrFail($order->id);

    expect(savedStages($order))->toBe([[10, 50, 0], [0, 30, 0]])
        ->and($saved->lines[0]->progress()->value)->toBe('in_progress')
        ->and($saved->progress()->value)->toBe('in_progress');
});

it('refuses on the saved counts when units started after the form was opened (AC-6)', function (array $lines, string $key, string $message) {
    $order = bookOrder();
    $before = savedOrderFigures($order);

    // The form was opened here, with nothing delivered; then someone else delivered 30 jackets.
    OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->update(['delivered' => 30]);
    OrderLineSize::query()->whereIn('order_line_id', OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->select('id'))->update(['delivered' => 30]);
    $started = savedOrderFigures($order);

    actingAs($this->user)->put(route('orders.update', $order), startedEditBody($lines))
        ->assertSessionHasErrors([startedKey($key) => $message]);

    expect($before['lines'][0]['delivered'])->toBe(0)
        ->and(savedOrderFigures($order))->toBe($started);
})->with('started line refusals');

it('checks again under the order lock, on a model loaded before the units started (AC-6)', function (array $lines, string $key, string $message) {
    $order = bookOrder();
    $stale = Order::query()->with('lines')->findOrFail($order->id);

    OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->update(['delivered' => 30]);
    OrderLineSize::query()->whereIn('order_line_id', OrderLine::query()->where('order_id', $order->id)->where('product_id', pid(1))->select('id'))->update(['delivered' => 30]);
    $started = savedOrderFigures($order);
    $data = startedEditBody($lines);

    // Straight into the Action: the form request, whose check runs on unlocked data, is out of the picture.
    $refusal = null;

    try {
        app(UpdateOrder::class)->handle($stale, $data);
    } catch (ValidationException $e) {
        $refusal = $e->errors();
    }

    expect($refusal)->toBe([startedKey($key) => [$message]])
        ->and(savedOrderFigures($order))->toBe($started);
})->with('started line refusals');

it('names a size of digits "size 36" when it cannot go below its started units, in the singular too (F9)', function (int $started, string $message) {
    $order = Order::factory()->create();
    OrderLine::factory()->sized(['36' => 10, '37' => 8], ['36' => [$started, 0]])->create(['order_id' => $order->id, 'product_id' => demoProduct('PRD-SNK-003')->id, 'position' => 0]);
    $before = sizedFigures($order);
    $size = array_key_first(sizeIds(['36' => 0], 'eu_36_to_46'));

    actingAs($this->user)->from(route('orders.edit', $order))
        ->put(route('orders.update', $order), validOrderPayload(['lines' => [['product_id' => pid(3), 'units' => orderUnits(pid(3), ['36' => '0', '37' => '8'])]]]))
        ->assertSessionHasErrors([unitsKey(0, orderUnits(pid(3), ['36' => 0])) => $message]);

    expect(sizedFigures($order))->toBe($before);
})->with([
    'four units' => [4, "White, size 36 can't be less than the 4 units already delivered or cancelled."],
    'one unit' => [1, "White, size 36 can't be less than the 1 unit already delivered or cancelled."],
]);

dataset('started sizes', [
    'delivered' => [[10, 0]],
    'cancelled' => [[0, 10]],
    'both' => [[6, 4]],
]);

/**
 * The payload that edits Boutique Lina's Denim Jacket counted by size, with these units by label and the order's other fields changed.
 *
 * @param  array<string, int>  $sizes
 * @return array<string, mixed>
 */
function sizedEditBody(array $sizes): array
{
    return validOrderPayload([
        'lines' => [['product_id' => pid(1), 'units' => orderUnits(pid(1), array_map('strval', $sizes))]],
    ]);
}

it('keeps each cell at its delivered and cancelled units, lets the others change, and keeps such a line on the order (INV-14 AC-6)', function (array $started) {
    $order = bookSizedOrder(stages: ['M' => $started]);
    $before = [savedOrderFigures($order), sizedFigures($order)];
    $mKey = unitsKey(0, orderUnits(pid(1), ['M' => 0]));

    // M 8 is below the 10 units started: refused under M, nothing saved (not even the client's name).
    actingAs($this->user)->from(route('orders.edit', $order))->put(route('orders.update', $order), sizedEditBody(['S' => 10, 'M' => 8, 'L' => 15, 'XL' => 5]))
        ->assertRedirect(route('orders.edit', $order))
        ->assertSessionHasErrors([$mKey => "White M can't be less than the 10 units already delivered or cancelled."]);

    expect(session('errors')->keys())->toBe([$mKey])
        ->and([savedOrderFigures($order), sizedFigures($order)])->toBe($before);

    // The line can't leave the order while 10 of its units have started.
    actingAs($this->user)->put(route('orders.update', $order), validOrderPayload(['lines' => [['product_id' => pid(5), 'units' => orderUnits(pid(5), ['M' => '5'])]]]))
        ->assertSessionHasErrors(['lines' => "Denim Jacket can't be removed: 10 units are delivered or cancelled."]);

    expect([savedOrderFigures($order), sizedFigures($order)])->toBe($before);

    // M at its 10 started units is allowed, and L is free: the stages of M stay where they are.
    actingAs($this->user)->put(route('orders.update', $order), sizedEditBody(['S' => 10, 'M' => 10, 'L' => 20, 'XL' => 5]))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors();

    expect(sizedFigures($order)[pid(1)])->toBe([
        'S' => [10, 0, 0], 'M' => [10, ...$started], 'L' => [20, 0, 0], 'XL' => [5, 0, 0],
    ]);
})->with('started sizes');

it('sends each cell its delivered and cancelled units on Edit order (INV-14 AC-6)', function () {
    $order = bookSizedOrder(['S' => 10, 'M' => 20], ['M' => [4, 3]]);

    actingAs($this->user)->get(route('orders.edit', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.lines.0.sizes.0.size.label', 'S')
            ->where('order.lines.0.sizes.0.delivered', 0)
            ->where('order.lines.0.sizes.1.size.label', 'M')
            ->where('order.lines.0.sizes.1.delivered', 4)
            ->where('order.lines.0.sizes.1.cancelled', 3)
            ->where('order.lines.0.sizes.1.to_deliver', 13)
            ->where('order.lines.0.delivered', 4)
            ->etc());
});
