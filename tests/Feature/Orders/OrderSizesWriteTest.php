<?php

use App\Actions\Orders\SyncOrderLines;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SizeList;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Record and edit an order per colour and size (S-60-c; INV-13-d: AC-3, AC-4 server side, F-64)
|--------------------------------------------------------------------------
|
| A line posts `units`: colour id → size id → as typed (INV-13). Denim Jacket (XS to XXXL) is counted by colour and size, Leather Tote
| ("One size") by colour. Every test that writes checks, through `sizedFigures()` or `colourFigures()`, that each line equals the sum of
| its rows. The limits once work has started are in EditStartedOrderTest.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->travelTo('2026-10-15 12:00:00');
});

/**
 * The payload of an order with these lines, `[product id, units]` each (`orderUnits()`'s shape; several colours are merged).
 *
 * @param  list<array{int, array<int, array<int, int|string|null>>}>  $lines
 * @return array<string, mixed>
 */
function sizedOrderBody(array $lines): array
{
    return validOrderPayload(['lines' => array_map(static fn (array $line): array => ['product_id' => $line[0], 'units' => $line[1]], $lines)]);
}

it('records the units per colour and size, a cell left empty having no row, and a "One size" product per colour (S-60 AC-1, INV-13 AC-3)', function () {
    actingAs($this->user)->post(route('orders.store'), sizedOrderBody([
        [pid(1), orderUnits(pid(1), ['S' => '10', 'M' => '20', 'L' => '15', 'XL' => '5'])],
        [pid(4), orderUnits(pid(4), ['One size' => '12'])],
    ]))->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded. Boutique Lina is now one of your clients.']);

    $order = Order::query()->firstOrFail();

    expect(sizedFigures($order))->toBe([
        pid(1) => ['S' => [10, 0, 0], 'M' => [20, 0, 0], 'L' => [15, 0, 0], 'XL' => [5, 0, 0]],
        pid(4) => ['One size' => [12, 0, 0]],
    ])->and($order->lines()->pluck('quantity', 'product_id')->all())->toBe([pid(1) => 50, pid(4) => 12]);

    // The same jacket with XL left empty: no XL row, 45 units.
    actingAs($this->user)->post(route('orders.store'), sizedOrderBody([[pid(1), orderUnits(pid(1), ['S' => '10', 'M' => '20', 'L' => '15', 'XL' => ''])]]))
        ->assertSessionHasNoErrors();

    $second = Order::query()->latest('id')->firstOrFail();

    expect(sizedFigures($second))->toBe([pid(1) => ['S' => [10, 0, 0], 'M' => [20, 0, 0], 'L' => [15, 0, 0]]])
        ->and($second->lines()->value('quantity'))->toBe(45);
});

it('records the card\'s order: Jacket Indigo S 4 and M 8, Jeans Indigo M 10, with no stock, material or sale touched (INV-13 AC-3)', function () {
    $movements = StockMovement::query()->count();
    $jacket = orderUnits(pid(1), ['S' => '4', 'M' => '8'], 'IND');
    // Black is a colour of the jacket too: its empty cells are sent, as the grid sends every cell.
    $black = orderUnits(pid(1), ['S' => '', 'M' => '0'], 'BLK');

    actingAs($this->user)->post(route('orders.store'), sizedOrderBody([
        [pid(1), $black + $jacket],
        [pid(5), orderUnits(pid(5), ['M' => '10'], 'IND')],
    ]))->assertSessionHasNoErrors();

    expect(colourFigures(Order::query()->firstOrFail()))->toBe([
        pid(1) => ['Indigo S' => [4, 0, 0], 'Indigo M' => [8, 0, 0]],
        pid(5) => ['Indigo M' => [10, 0, 0]],
    ])
        ->and(StockMovement::query()->count())->toBe($movements)
        ->and(DB::table('product_size_stocks')->count())->toBe(0)
        ->and(DB::table('sales')->where('created_at', '>=', '2026-10-15')->count())->toBe(0);
});

it('refuses a cell that is not a whole number from 0 to 1,000,000, a line with no unit or too many, and colours or sizes it can\'t hold (INV-13 AC-3)', function (array $sizes, ?string $cell, string $message, int $product = 1, ?string $colour = null) {
    $units = orderUnits(pid($product), $sizes, $colour);
    $key = $cell === null ? 'lines.0.units' : unitsKey(0, orderUnits(pid($product), [$cell => 0], $colour));

    actingAs($this->user)->from(route('orders.create'))->post(route('orders.store'), validOrderPayload(['lines' => [['product_id' => pid($product), 'units' => $units]]]))
        ->assertRedirect(route('orders.create'))
        ->assertInertiaFlashMissing('toast');

    // Only the field of the case is flagged, and nothing is saved.
    expect(session('errors')->keys())->toBe([$key])
        ->and(session('errors')->get($key))->toBe([$message])
        ->and([Order::query()->count(), OrderLine::query()->count(), OrderLineSize::query()->count()])->toBe([0, 0, 0]);
})->with([
    'Indigo M -1' => [['S' => '10', 'M' => '-1'], 'M', 'Indigo M: Enter a whole number from 0 to 1,000,000.', 1, 'IND'],
    'M 2.5' => [['S' => '10', 'M' => '2.5'], 'M', 'White M: Enter a whole number from 0 to 1,000,000.'],
    'M abc' => [['S' => '10', 'M' => 'abc'], 'M', 'White M: Enter a whole number from 0 to 1,000,000.'],
    'M 1,000,001' => [['S' => '10', 'M' => '1000001'], 'M', 'White M: Enter a whole number from 0 to 1,000,000.'],
    'a shoe size is named "size 36"' => [['36' => 'x', '37' => '4'], '36', 'White, size 36: Enter a whole number from 0 to 1,000,000.', 3],
    'a "One size" product names the colour' => [['One size' => '-2'], 'One size', 'White: Enter a whole number from 0 to 1,000,000.', 4],
    'every cell empty' => [['S' => '', 'M' => ''], null, 'Enter at least one unit of Denim Jacket, or remove it from this order.'],
    'every cell 0' => [['S' => '0', 'M' => '0', 'L' => '0', 'XL' => '0'], null, 'Enter at least one unit of Denim Jacket, or remove it from this order.'],
    'S 1,000,000 and M 1' => [['S' => '1000000', 'M' => '1'], null, 'A line can hold up to 1,000,000 units.'],
]);

it('refuses a colour the product doesn\'t have, a size of another list, or no units at all (INV-13 AC-3)', function (Closure $units) {
    actingAs($this->user)->post(route('orders.store'), validOrderPayload(['lines' => [['product_id' => pid(1), ...$units()]]]))
        ->assertSessionHasErrors(['lines.0.units' => 'The colours or sizes of Denim Jacket have changed. Enter its quantities again.']);

    expect(Order::query()->count())->toBe(0);
})->with([
    'a colour of another product' => [fn () => ['units' => [demoColour('RED')->id => sizeIds(['M' => '5'])]]],
    'a shoe size' => [fn () => ['units' => [array_key_first(orderUnits(pid(1), [])) => sizeIds(['40' => '5'], SizeList::EU_36_TO_46)]]],
    'the old shape' => [fn () => ['quantity' => '50', 'sizes' => sizeIds(['M' => '50'])]],
]);

it('says "Add at least one product to this order." with no product (INV-13 AC-3)', function () {
    actingAs($this->user)->post(route('orders.store'), validOrderPayload(['lines' => []]))
        ->assertSessionHasErrors(['lines' => 'Add at least one product to this order.']);
});

it('changes a cell, removes a cell set to 0 and moves units to another colour, line and rows staying equal (S-60 AC-4, INV-13 AC-4)', function () {
    $order = bookSizedOrder();
    $line = $order->lines()->firstOrFail();
    $edit = fn (array $units) => actingAs($this->user)->put(route('orders.update', $order), sizedOrderBody([[pid(1), $units]]));

    $edit(orderUnits(pid(1), ['S' => '10', 'M' => '25', 'L' => '15', 'XL' => '5']))
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order updated.']);

    expect(sizedFigures($order)[pid(1)])->toBe(['S' => [10, 0, 0], 'M' => [25, 0, 0], 'L' => [15, 0, 0], 'XL' => [5, 0, 0]])
        ->and($line->fresh()->quantity)->toBe(55)
        ->and(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0.quantity'))->toBe(55);

    // The S and M units move from White to Indigo; XL goes.
    $edit(orderUnits(pid(1), ['L' => '15'], 'WHT') + orderUnits(pid(1), ['S' => '10', 'M' => '25'], 'IND'))->assertSessionHasNoErrors();

    expect(colourFigures($order)[pid(1)])->toBe(['White L' => [15, 0, 0], 'Indigo S' => [10, 0, 0], 'Indigo M' => [25, 0, 0]])
        ->and($line->fresh()->quantity)->toBe(50)
        ->and($order->lines()->count())->toBe(1);
});

it('keeps a row in a colour since taken off the product, and refuses a colour the line never held (INV-13 AC-4)', function () {
    $order = bookSizedOrder(['M' => 4], ['M' => [4, 0]]);
    $white = demoColour('WHT')->id;
    $jacket = demoProduct('PRD-JKT-001');
    // The 4 white jackets were delivered; White then left the product, which now has Indigo only.
    $jacket->colours()->sync([demoColour('IND')->id => ['workshop_id' => $jacket->workshop_id]]);

    actingAs($this->user)->put(route('orders.update', $order), sizedOrderBody([[pid(1), orderUnits(pid(1), ['M' => '4'], 'WHT') + orderUnits(pid(1), ['S' => '2'], 'IND')]]))
        ->assertSessionHasNoErrors();

    expect(colourFigures($order)[pid(1)])->toBe(['White M' => [4, 4, 0], 'Indigo S' => [2, 0, 0]]);

    actingAs($this->user)->put(route('orders.update', $order), sizedOrderBody([[pid(1), [$white => sizeIds(['M' => '4'])] + [demoColour('RED')->id => sizeIds(['M' => '1'])]]]))
        ->assertSessionHasErrors(['lines.0.units' => 'The colours or sizes of Denim Jacket have changed. Enter its quantities again.']);
});

it('keeps a line recorded before sizes on its single size, one field per colour (S-60 AC-5, INV-13)', function () {
    $order = bookOrder([], [[demoProduct('PRD-JKT-001'), 50]]);
    $line = $order->lines()->firstOrFail();

    actingAs($this->user)->put(route('orders.update', $order), validOrderPayload(['lines' => [
        ['product_id' => pid(1), 'units' => orderUnits(pid(1), ['One size' => '60'], list: SizeList::ONE_SIZE)],
    ]]))->assertSessionHasNoErrors();

    expect(sizedFigures($order))->toBe([pid(1) => ['One size' => [60, 0, 0]]])
        ->and($line->fresh()->size_list_id)->toBe(SizeList::query()->where('code', SizeList::ONE_SIZE)->value('id'))
        ->and(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0.size_mode'))->toBe('before_sizes');

    // The product's own sizes don't fit the line any more.
    actingAs($this->user)->put(route('orders.update', $order), validOrderPayload(['lines' => [['product_id' => pid(1), 'units' => orderUnits(pid(1), ['M' => '7'])]]]))
        ->assertSessionHasErrors(['lines.0.units' => 'The colours or sizes of Denim Jacket have changed. Enter its quantities again.']);
});

/**
 * What a direct call to the Action refuses with, as the errors by key; null when it saved.
 *
 * @param  array<int, array<string, mixed>>  $lines
 * @return array<string, list<string>>|null
 */
function syncRefusal(Order $order, array $lines): ?array
{
    try {
        app(SyncOrderLines::class)->handle($order, $lines);
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return null;
}

it('checks a cell against its started units under the lock, on lines read inside the transaction (S-61 AC-5, INV-13 AC-4)', function () {
    $order = bookSizedOrder(stages: ['M' => [10, 0]]);
    $before = sizedFigures($order);

    // Straight into the Action: the form request, whose check runs on unlocked data, is out of the picture.
    $refusal = syncRefusal($order, [['product_id' => pid(1), 'units' => orderUnits(pid(1), ['S' => '10', 'M' => '8', 'L' => '15', 'XL' => '5'])]]);

    expect($refusal)->toBe([unitsKey(0, orderUnits(pid(1), ['M' => 0])) => ["White M can't be less than the 10 units already delivered or cancelled."]])
        ->and(sizedFigures($order))->toBe($before);
});

it('refuses a line whose product changed sizes after the request was checked, and saves nothing (F-64)', function () {
    $order = Order::factory()->create();
    $units = orderUnits(pid(1), ['M' => '5']);

    // The request saw the Denim Jacket with XS to XXXL; before the lock is taken it moves to Footwear (36 to 46).
    Product::query()->whereKey(pid(1))->update(['category_id' => ProductCategory::query()->where('code', 'footwear')->value('id')]);

    expect(syncRefusal($order, [['product_id' => pid(1), 'units' => $units]]))
        ->toBe(['lines.0.units' => ['The colours or sizes of Denim Jacket have changed. Enter its quantities again.']])
        ->and([OrderLine::query()->count(), OrderLineSize::query()->count()])->toBe([0, 0]);
});
