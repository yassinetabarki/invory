<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductRestock;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The Order tracker, Mark delivered, its move back and Cancel units (INV-14-c, INV-14-d: AC-1 to AC-4 server side)
|--------------------------------------------------------------------------
|
| The card's Boutique Lina, 80 units in Indigo: Denim Jacket S 10 · M 20 · L 15 · XL 5 (XL delivered), Denim Jeans 5-pocket S 5 · M 10 ·
| L 10 · XL 5 (XL cancelled). On the shelf (XS · S · M · L · XL · XXL · XXXL): Jacket Indigo 0 · 2 · 0 · 3 · 1 · 0 · 0, Jeans Indigo
| 0 · 0 · 5 · 2 · 0 · 0 · 0.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->order = Order::factory()->create(['client' => 'Boutique Lina', 'ordered_on' => '2026-10-15']);
    $this->jacket = indigoLine($this->order, demoProduct('PRD-JKT-001'), ['S' => 10, 'M' => 20, 'L' => 15, 'XL' => 5], ['XL' => [5, 0]], 0);
    $this->jeans = indigoLine($this->order, demoProduct('PRD-JNS-005'), ['S' => 5, 'M' => 10, 'L' => 10, 'XL' => 5], ['XL' => [0, 5]], 1);
    setFinishedStock(demoProduct('PRD-JKT-001'), ['Indigo' => [0, 2, 0, 3, 1, 0, 0]]);
    setFinishedStock(demoProduct('PRD-JNS-005'), ['Indigo' => [0, 0, 5, 2, 0, 0, 0]]);
});

/** A line of the product in Indigo, its rows by size label, with `[delivered, cancelled]` per label. */
function indigoLine(Order $order, Product $product, array $sizes, array $counts, int $position): OrderLine
{
    $line = OrderLine::factory()->sized($sizes, $counts)->create(['order_id' => $order->id, 'product_id' => $product->id, 'position' => $position]);
    $product->colours()->syncWithoutDetaching([demoColour('IND')->id => ['workshop_id' => $product->workshop_id]]);
    OrderLineSize::query()->where('order_line_id', $line->id)->update(['colour_id' => demoColour('IND')->id]);

    return $line;
}

/** The cells of a window in Indigo, by size label. */
function indigo(array $byLabel): array
{
    return [demoColour('IND')->id => sizeIds($byLabel, 's_to_xl')];
}

function change(string $route, OrderLine $line, array $cells): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', test()->order))
        ->post(route($route, [test()->order, $line]), ['cells' => $cells]);
}

/** The shelf of a product in Indigo, by size label, every size of its list. */
function indigoShelf(string $sku): array
{
    return collect(sizeIds(['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, 'XXL' => 0, 'XXXL' => 0], 's_to_xl'))
        ->map(fn (int $zero, int $sizeId): int => (int) DB::table('product_size_stocks')
            ->where(['product_id' => demoProduct($sku)->id, 'colour_id' => demoColour('IND')->id, 'size_id' => $sizeId])->value('units'))
        ->values()->all();
}

it('sends the tracker of the card: counts, status, rows by colour and size with the shelf now (AC-1, AC-2)', function () {
    $first = actingAs($this->user)->get(route('orders.show', $this->order))->assertOk();
    $tracker = loadedPageProp($first, 'tracker');

    expect($tracker)->toMatchArray(['to_deliver' => 70, 'delivered' => 5, 'cancelled' => 5, 'total' => 80, 'status' => 'in_progress', 'status_label' => 'In progress'])
        ->and($tracker['lines'][0])->toMatchArray(['id' => $this->jacket->id, 'one_size' => false, 'to_deliver' => 45, 'delivered' => 5, 'cancelled' => 0, 'total' => 50, 'status' => 'in_progress'])
        ->and($tracker['lines'][0]['product'])->toMatchArray(['name' => 'Denim Jacket', 'sku' => 'PRD-JKT-001', 'deleted' => false])
        ->and($tracker['lines'][0]['colours'][0])->toMatchArray(['to_deliver' => 45, 'delivered' => 5, 'cancelled' => 0, 'total' => 50])
        ->and($tracker['lines'][0]['colours'][0]['colour']['name'])->toBe('Indigo')
        ->and(array_map(fn (array $row): array => [$row['size']['label'], $row['to_deliver'], $row['delivered'], $row['cancelled'], $row['total'], $row['on_shelf'], $row['can_deliver']], $tracker['lines'][0]['colours'][0]['rows']))
        ->toBe([['S', 10, 0, 0, 10, 2, 2], ['M', 20, 0, 0, 20, 0, 0], ['L', 15, 0, 0, 15, 3, 3], ['XL', 0, 5, 0, 5, 1, 0]])
        ->and(array_map(fn (array $row): array => [$row['size']['label'], $row['to_deliver'], $row['cancelled'], $row['on_shelf']], $tracker['lines'][1]['colours'][0]['rows']))
        ->toBe([['S', 5, 0, 0], ['M', 10, 0, 5], ['L', 10, 0, 2], ['XL', 0, 5, 0]])
        ->and($tracker['lines'][1]['status'])->toBe('not_started');
});

it('marks Indigo S 2 and L 3 delivered: the counts, the toast and the shelf, its rows at 0 gone (AC-3)', function () {
    change('orders.lines.deliveries.store', $this->jacket, indigo(['S' => '2', 'L' => '3']))
        ->assertRedirect(route('orders.show', $this->order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket marked delivered.']);

    expect(colourFigures($this->order)[pid(1)])->toBe(['Indigo S' => [10, 2, 0], 'Indigo M' => [20, 0, 0], 'Indigo L' => [15, 3, 0], 'Indigo XL' => [5, 5, 0]])
        ->and(indigoShelf('PRD-JKT-001'))->toBe([0, 0, 0, 0, 1, 0, 0])
        ->and(DB::table('product_size_stocks')->where('product_id', pid(1))->where('units', 0)->exists())->toBeFalse()
        ->and(OrderLine::query()->findOrFail($this->jacket->id)->only(['delivered', 'cancelled']))->toBe(['delivered' => 10, 'cancelled' => 0]);

    // The product page reads the lowered shelf too.
    expect(loadedPageProp(actingAs($this->user)->get(route('products.show', pid(1))), 'finished_stock.units'))->toBe(1);
});

it('says "1 unit … marked delivered." in the singular', function () {
    change('orders.lines.deliveries.store', $this->jacket, indigo(['S' => '1']))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 unit of Denim Jacket marked delivered.']);
});

it('refuses a delivery past the shelf or what is left to deliver, and when nothing fits any more, writing nothing (AC-3)', function (Closure $setUp, array $cells, string $key, string $message) {
    $setUp($this);
    $before = [colourFigures($this->order), indigoShelf('PRD-JKT-001')];
    $key = str_replace(['IND', 'S', 'L'], [(string) demoColour('IND')->id, (string) array_key_first(sizeIds(['S' => 0])), (string) array_key_first(sizeIds(['L' => 0]))], $key);

    change('orders.lines.deliveries.store', $this->jacket, indigo($cells))
        ->assertRedirect(route('orders.show', $this->order))
        ->assertSessionHasErrors([$key => $message]);

    expect([colourFigures($this->order), indigoShelf('PRD-JKT-001')])->toBe($before);
})->with([
    'more than the shelf' => [fn () => null, ['S' => '3'], 'cells.IND.S', 'You can mark at most 2 units of Indigo S: only 2 are on the shelf.'],
    'more than left to deliver' => [function ($test): void {
        // 4 left to deliver of Indigo S, 6 on the shelf.
        OrderLineSize::query()->where('order_line_id', $test->jacket->id)->where('size_id', array_key_first(sizeIds(['S' => 0])))->update(['delivered' => 6]);
        OrderLine::query()->whereKey($test->jacket->id)->update(['delivered' => 11]);
        setFinishedStock(demoProduct('PRD-JKT-001'), ['Indigo' => [0, 6, 0, 3, 1, 0, 0]]);
    }, ['S' => '5'], 'cells.IND.S', 'You can mark at most 4 units of Indigo S: only 4 are left to deliver.'],
    'a size with nothing on the shelf any more' => [fn () => null, ['M' => '1'], 'cells', 'The shelf or this order changed since you opened this page.'],
    'nothing typed' => [fn () => null, ['S' => '', 'L' => '0'], 'cells', 'Enter at least one unit to mark delivered.'],
    'not a whole number' => [fn () => null, ['S' => '1.5'], 'cells.IND.S', 'Indigo S: Enter a whole number of units.'],
]);

it('moves delivered units back to To deliver and puts them on the shelf again (AC-3)', function () {
    change('orders.lines.deliveries.destroy', $this->jacket, indigo(['XL' => '5']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved back to To deliver.']);

    expect(colourFigures($this->order)[pid(1)]['Indigo XL'])->toBe([5, 0, 0])
        ->and(indigoShelf('PRD-JKT-001'))->toBe([0, 2, 0, 3, 6, 0, 0]);

    change('orders.lines.deliveries.destroy', $this->jacket, indigo(['XL' => '1']))
        ->assertSessionHasErrors(['cells' => 'This order changed since you opened this page.']);
});

it('refuses moving back more than was delivered, and past 1,000,000 on the shelf (AC-3)', function () {
    $xl = 'cells.'.demoColour('IND')->id.'.'.array_key_first(sizeIds(['XL' => 0]));

    change('orders.lines.deliveries.destroy', $this->jacket, indigo(['XL' => '6']))
        ->assertSessionHasErrors([$xl => 'You can move back at most 5 units of Indigo XL.']);
    change('orders.lines.deliveries.destroy', $this->jacket, indigo([]))
        ->assertSessionHasErrors(['cells' => 'Enter at least one unit to move back.']);

    setFinishedStock(demoProduct('PRD-JKT-001'), ['Indigo' => [0, 2, 0, 3, 999999, 0, 0]]);
    change('orders.lines.deliveries.destroy', $this->jacket, indigo(['XL' => '2']))
        ->assertSessionHasErrors([$xl => "Indigo XL can't go over 1,000,000 units on the shelf."]);
});

it('makes a shelf row again, and links a colour taken off the product since, when moving back (AC-3)', function () {
    $jacket = demoProduct('PRD-JKT-001');
    setFinishedStock($jacket, ['Indigo' => [0, 2, 0, 3, 0, 0, 0]]);

    change('orders.lines.deliveries.destroy', $this->jacket, indigo(['XL' => '2']))->assertSessionHasNoErrors();

    expect(indigoShelf('PRD-JKT-001'))->toBe([0, 2, 0, 3, 2, 0, 0]);
});

it('cancels Indigo L 5 of the Jeans, with no stock touched (AC-4)', function () {
    $shelf = DB::table('product_size_stocks')->orderBy('id')->get(['product_id', 'colour_id', 'size_id', 'units'])->toArray();
    $restocks = ProductRestock::query()->count();

    change('orders.lines.cancellations.store', $this->jeans, indigo(['L' => '5']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jeans 5-pocket cancelled.']);

    $order = Order::query()->with('lines')->findOrFail($this->order->id);

    expect(colourFigures($this->order)[pid(5)]['Indigo L'])->toBe([10, 0, 5])
        ->and([$order->unitsToDeliver(), $order->unitsDelivered(), $order->unitsCancelled()])->toBe([65, 5, 10])
        ->and(DB::table('product_size_stocks')->orderBy('id')->get(['product_id', 'colour_id', 'size_id', 'units'])->toArray())->toEqual($shelf)
        ->and(ProductRestock::query()->count())->toBe($restocks);
});

it('refuses cancelling more than is left to deliver, or a cell with nothing left (AC-4)', function () {
    $l = 'cells.'.demoColour('IND')->id.'.'.array_key_first(sizeIds(['L' => 0]));

    change('orders.lines.cancellations.store', $this->jeans, indigo(['L' => '11']))
        ->assertSessionHasErrors([$l => 'You can cancel at most 10 units of Indigo L.']);
    change('orders.lines.cancellations.store', $this->jeans, indigo(['XL' => '1']))
        ->assertSessionHasErrors(['cells' => 'This order changed since you opened this page.']);
    change('orders.lines.cancellations.store', $this->jeans, [])
        ->assertSessionHasErrors(['cells' => 'Enter at least one unit to cancel.']);
    // A colour the line doesn't hold: the window was built for another order.
    change('orders.lines.cancellations.store', $this->jeans, [demoColour('RED')->id => sizeIds(['L' => '1'], 's_to_xl')])
        ->assertSessionHasErrors(['cells' => 'This order changed since you opened this page.']);

    expect(colourFigures($this->order)[pid(5)]['Indigo L'])->toBe([10, 0, 0]);
});

it('answers 404 for another workshop\'s order, and for a line of another order', function () {
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn () => OrderLine::factory()->create());
    $other = OrderLine::factory()->create(['order_id' => Order::factory()->create()->id]);

    actingAs($this->user)->post(route('orders.lines.cancellations.store', [$foreign->order_id, $foreign->id]), ['cells' => indigo(['S' => '1'])])->assertNotFound();
    actingAs($this->user)->post(route('orders.lines.deliveries.store', [$this->order, $other]), ['cells' => indigo(['S' => '1'])])->assertNotFound();
});

it('lowers the shelf so an older restock may no longer be undone (INV-3\'s sentence)', function () {
    $jacket = demoProduct('PRD-JKT-001');
    setFinishedStock($jacket, []);
    $restock = ProductRestock::factory()->create();
    DB::table('product_restock_lines')->insert([
        'workshop_id' => $restock->workshop_id, 'product_restock_id' => $restock->id, 'product_id' => $jacket->id, 'colour_id' => demoColour('IND')->id,
        'size_id' => array_key_first(sizeIds(['S' => 0])), 'position' => 0, 'units' => 2, 'created_at' => now(), 'updated_at' => now(),
    ]);
    setFinishedStock($jacket, ['Indigo' => [0, 2, 0, 0, 0, 0, 0]]);

    change('orders.lines.deliveries.store', $this->jacket, indigo(['S' => '2']))->assertSessionHasNoErrors();

    actingAs($this->user)->post(route('restocks.undo', $restock))
        ->assertSessionHasErrors('undo');
});
