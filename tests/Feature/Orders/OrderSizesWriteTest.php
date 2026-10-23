<?php

use App\Actions\Orders\SyncOrderLines;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SizeList;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Record and edit an order with a quantity per size (S-60-c: S-60 AC-1, AC-2, AC-4 and AC-5 server side, F-64)
|--------------------------------------------------------------------------
|
| Denim Jacket (S, M, L, XL) is counted by size, Leather Tote ("One size") by one quantity. Every test that writes checks, through
| `sizedFigures()`, that each line equals the sum of its size rows. The limits once work has started are in EditStartedOrderTest.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->travelTo('2026-10-15 12:00:00');
});

/**
 * The payload of an order with these lines, `[product id, quantity or sizes by id]` each: a list of sizes (an array) for a line counted
 * by size, a number for a line with a single quantity.
 *
 * @param  list<array{int, array<int, int|string|null>|string}>  $lines
 * @return array<string, mixed>
 */
function sizedOrderBody(array $lines): array
{
    return validOrderPayload(['lines' => array_map(
        static fn (array $line): array => ['product_id' => $line[0], ...(is_array($line[1]) ? ['sizes' => $line[1]] : ['quantity' => $line[1]])],
        $lines,
    )]);
}

it('records a quantity per size, with a size left empty having no row, and a "One size" product with its single quantity (S-60 AC-1)', function () {
    actingAs($this->user)->post(route('orders.store'), sizedOrderBody([
        [pid(1), sizeIds(['S' => '10', 'M' => '20', 'L' => '15', 'XL' => '5'])],
        [pid(4), '12'],
    ]))->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded.']);

    $order = Order::query()->firstOrFail();

    expect(sizedFigures($order))->toBe([
        pid(1) => ['S' => [10, 0, 0, 0], 'M' => [20, 0, 0, 0], 'L' => [15, 0, 0, 0], 'XL' => [5, 0, 0, 0]],
        pid(4) => ['One size' => [12, 0, 0, 0]],
    ])->and($order->lines()->pluck('quantity', 'product_id')->all())->toBe([pid(1) => 50, pid(4) => 12]);

    // The same jacket with XL left empty: no XL row, 45 units.
    actingAs($this->user)->post(route('orders.store'), sizedOrderBody([[pid(1), sizeIds(['S' => '10', 'M' => '20', 'L' => '15', 'XL' => ''])]]))
        ->assertSessionHasNoErrors();

    $second = Order::query()->latest('id')->firstOrFail();

    expect(sizedFigures($second))->toBe([pid(1) => ['S' => [10, 0, 0, 0], 'M' => [20, 0, 0, 0], 'L' => [15, 0, 0, 0]]])
        ->and($second->lines()->value('quantity'))->toBe(45);
});

it('refuses a size that is not a whole number from 0 to 1,000,000, a line with no unit or too many, and sizes of another list (S-60 AC-2)', function (?array $sizes, string $list, ?string $size, string $message) {
    $typed = $sizes === null ? ['quantity' => '50'] : ['sizes' => sizeIds($sizes, $list)];
    $key = $size === null ? 'lines.0.sizes' : 'lines.0.sizes.'.array_key_first(sizeIds([$size => 0]));

    actingAs($this->user)->from(route('orders.create'))->post(route('orders.store'), validOrderPayload(['lines' => [['product_id' => pid(1), ...$typed]]]))
        ->assertRedirect(route('orders.create'))
        ->assertInertiaFlashMissing('toast');

    // Only the field of the case is flagged, and nothing is saved.
    expect(session('errors')->keys())->toBe([$key])
        ->and(session('errors')->get($key))->toBe([$message])
        ->and([Order::query()->count(), OrderLine::query()->count(), OrderLineSize::query()->count()])->toBe([0, 0, 0]);
})->with([
    'M -1' => [['S' => '10', 'M' => '-1'], SizeList::S_TO_XL, 'M', 'Enter a whole number from 0 to 1,000,000.'],
    'M 2.5' => [['S' => '10', 'M' => '2.5'], SizeList::S_TO_XL, 'M', 'Enter a whole number from 0 to 1,000,000.'],
    'M abc' => [['S' => '10', 'M' => 'abc'], SizeList::S_TO_XL, 'M', 'Enter a whole number from 0 to 1,000,000.'],
    'M 1,000,001' => [['S' => '10', 'M' => '1000001'], SizeList::S_TO_XL, 'M', 'Enter a whole number from 0 to 1,000,000.'],
    'every size empty' => [['S' => '', 'M' => ''], SizeList::S_TO_XL, null, 'Enter at least one unit of Denim Jacket.'],
    'every size 0' => [['S' => '0', 'M' => '0', 'L' => '0', 'XL' => '0'], SizeList::S_TO_XL, null, 'Enter at least one unit of Denim Jacket.'],
    'S 1,000,000 and M 1' => [['S' => '1000000', 'M' => '1'], SizeList::S_TO_XL, null, 'A line can hold up to 1,000,000 units.'],
    'a size of the footwear list' => [['40' => '5'], SizeList::EU_36_TO_46, null, 'The sizes of Denim Jacket have changed. Enter its quantities again.'],
    'a quantity of 50 without sizes' => [null, SizeList::S_TO_XL, null, 'Enter at least one unit of Denim Jacket.'],
]);

it('changes a size and removes a size set to 0, line and rows staying equal (S-60 AC-4)', function () {
    $order = bookSizedOrder();
    $line = $order->lines()->firstOrFail();
    $edit = fn (array $sizes) => actingAs($this->user)->put(route('orders.update', $order), sizedOrderBody([[pid(1), sizeIds($sizes)]]));

    $edit(['S' => '10', 'M' => '25', 'L' => '15', 'XL' => '5'])
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order updated.']);

    expect(sizedFigures($order)[pid(1)])->toBe(['S' => [10, 0, 0, 0], 'M' => [25, 0, 0, 0], 'L' => [15, 0, 0, 0], 'XL' => [5, 0, 0, 0]])
        ->and($line->fresh()->quantity)->toBe(55)
        ->and(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0.quantity'))->toBe(55);

    $edit(['S' => '10', 'M' => '25', 'L' => '15', 'XL' => '0'])->assertSessionHasNoErrors();

    expect(sizedFigures($order)[pid(1)])->toBe(['S' => [10, 0, 0, 0], 'M' => [25, 0, 0, 0], 'L' => [15, 0, 0, 0]])
        ->and($line->fresh()->quantity)->toBe(50)
        ->and($order->lines()->count())->toBe(1);
});

it('keeps a line recorded before sizes as one size, whatever sizes are sent (S-60 AC-5)', function () {
    $order = bookOrder([], [[demoProduct('PRD-JKT-001'), 50]]);
    $line = $order->lines()->firstOrFail();

    // The form of a line recorded before sizes sends its single quantity; sizes, if any, are ignored.
    actingAs($this->user)->put(route('orders.update', $order), validOrderPayload(['lines' => [
        ['product_id' => pid(1), 'quantity' => '60', 'sizes' => sizeIds(['M' => '7'])],
    ]]))->assertSessionHasNoErrors();

    expect(sizedFigures($order))->toBe([pid(1) => ['One size' => [60, 0, 0, 0]]])
        ->and($line->fresh()->size_list_id)->toBe(SizeList::query()->where('code', SizeList::ONE_SIZE)->value('id'))
        ->and(pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0.size_mode'))->toBe('before_sizes');

    // Without a quantity the line is refused, as a line with a single quantity always was.
    actingAs($this->user)->put(route('orders.update', $order), validOrderPayload(['lines' => [['product_id' => pid(1), 'sizes' => sizeIds(['M' => '7'])]]]))
        ->assertSessionHasErrors(['lines.0.quantity' => 'The quantity must be a whole number from 1 to 1,000,000.']);
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

it('checks a size against its started units under the lock, on lines read inside the transaction (S-61 AC-5, S-60-c)', function () {
    $order = bookSizedOrder(stages: ['M' => [10, 0, 0]]);
    $before = sizedFigures($order);
    $mId = array_key_first(sizeIds(['M' => 0]));

    // Straight into the Action: the form request, whose check runs on unlocked data, is out of the picture.
    $refusal = syncRefusal($order, [['product_id' => pid(1), 'sizes' => sizeIds(['S' => '10', 'M' => '8', 'L' => '15', 'XL' => '5'])]]);

    expect($refusal)->toBe(["lines.0.sizes.{$mId}" => ["M can't be less than the 10 units already in cutting, ready to ship or shipped."]])
        ->and(sizedFigures($order))->toBe($before);
});

it('refuses a line whose product changed sizes after the request was checked, and saves nothing (F-64)', function () {
    $order = Order::factory()->create();
    $sizes = sizeIds(['M' => '5']);

    // The request saw the Denim Jacket with S, M, L, XL; before the lock is taken it moves to Footwear (36 to 46).
    Product::query()->whereKey(pid(1))->update(['category_id' => ProductCategory::query()->where('code', 'footwear')->value('id')]);

    expect(syncRefusal($order, [['product_id' => pid(1), 'sizes' => $sizes]]))
        ->toBe(['lines.0.sizes' => ['The sizes of Denim Jacket have changed. Enter its quantities again.']])
        ->and([OrderLine::query()->count(), OrderLineSize::query()->count()])->toBe([0, 0]);

    // The next page load offers the product's new sizes, which is what the form is built from.
    $offered = array_column(pageProp(actingAs($this->user)->get(route('orders.edit', $order))->assertOk(), 'products'), 'size_list_code', 'name');

    expect($offered['Denim Jacket'])->toBe(SizeList::EU_36_TO_46);
});
