<?php

use App\Actions\Orders\MoveOrderLineUnits;
use App\Enums\OrderLineMove;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineSize;
use App\Models\SizeList;
use App\Models\StockMovement;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Moving units size by size (S-61-a: AC-2 and AC-6 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's Denim Jacket counted by size: S 10 · M 20 · L 15 · XL 5 (50 units). A move posts `sizes` (size id → units); every size
| moves or none does, and the line stays equal to the sums of its sizes (`sizedFigures()` checks it each time). What cutting takes from
| stock, and its history, are in CutSizesStockTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * Posts a move of the order's first line by size label, coming from the order's own page.
 *
 * @param  array<string, int|string>  $byLabel  sizes of the Apparel list
 * @param  array<string, int|string>  $footwear  sizes of the Footwear list, which the jacket line does not have
 */
function sizedMove(Order $order, string $move, array $byLabel, array $footwear = []): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->firstOrFail()]), ['move' => $move, 'sizes' => sizeIds($byLabel) + sizeIds($footwear, SizeList::EU_36_TO_46)]);
}

/**
 * Everything a refused move must leave alone: the sizes, the line, the materials, the ledger and the cuts.
 *
 * @return array<string, mixed>
 */
function sizedSnapshot(Order $order): array
{
    return [
        'sizes' => sizedFigures($order),
        'rows' => OrderLineSize::query()->orderBy('id')->get()->toArray(),
        'lines' => OrderLine::query()->orderBy('id')->get()->toArray(),
        'materials' => Material::query()->orderBy('id')->get()->toArray(),
        'ledger' => StockMovement::query()->orderBy('id')->get()->toArray(),
        'cuts' => OrderLineCut::query()->orderBy('id')->get()->toArray(),
    ];
}

/** The Denim Jacket's sizes as `label => [quantity, in cutting, ready to ship, shipped]`. */
function jacketSizes(Order $order): array
{
    return sizedFigures($order)[pid(1)];
}

it('starts cutting 4 S and 10 M: each size, the line and the toast follow (AC-2)', function () {
    $order = bookSizedOrder();

    sizedMove($order, 'start_cutting', ['S' => 4, 'M' => 10])
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '14 units of Denim Jacket moved to In cutting. Materials taken out of stock.']);

    $line = $order->lines()->firstOrFail();

    expect(jacketSizes($order))->toBe(['S' => [10, 4, 0, 0], 'M' => [20, 10, 0, 0], 'L' => [15, 0, 0, 0], 'XL' => [5, 0, 0, 0]])
        ->and([$line->to_start, $line->in_cutting, $line->ready_to_ship, $line->shipped])->toBe([36, 14, 0, 0]);
});

it('moves each size by the same move as a line that counts as one size: Mark ready, Ship and the three Move backs (AC-2)', function (string $move, string $message, array $expected) {
    // Units in every stage: S 10 (4 in cutting, 3 ready, 2 shipped, 1 to start), M 20 (6, 5, 4, 5 to start).
    $order = bookSizedOrder(['S' => 10, 'M' => 20], ['S' => [4, 3, 2], 'M' => [6, 5, 4]]);

    sizedMove($order, $move, ['S' => 1, 'M' => 2])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $message]);

    // `sizedFigures()` also checks the line against the sums of its sizes.
    expect(jacketSizes($order))->toBe($expected);
})->with([
    'mark ready' => ['mark_ready', '3 units of Denim Jacket moved to Ready to ship.', ['S' => [10, 3, 4, 2], 'M' => [20, 4, 7, 4]]],
    'ship' => ['ship', '3 units of Denim Jacket moved to Shipped.', ['S' => [10, 4, 2, 3], 'M' => [20, 6, 3, 6]]],
    'back to start' => ['back_to_start', '3 units of Denim Jacket moved back to To start.', ['S' => [10, 3, 3, 2], 'M' => [20, 4, 5, 4]]],
    'back to cutting' => ['back_to_cutting', '3 units of Denim Jacket moved back to In cutting.', ['S' => [10, 5, 2, 2], 'M' => [20, 8, 3, 4]]],
    'back to ready' => ['back_to_ready', '3 units of Denim Jacket moved back to Ready to ship.', ['S' => [10, 4, 4, 1], 'M' => [20, 6, 7, 2]]],
]);

it('refuses what a size can\'t move or the form can\'t read, and changes nothing (AC-2)', function (array $byLabel, string $field, string $message, array $footwear) {
    $order = bookSizedOrder();
    $before = sizedSnapshot($order);

    $key = $field === 'sizes' ? 'sizes' : 'sizes.'.array_key_first($footwear === [] ? sizeIds([$field => 1]) : sizeIds([$field => 1], SizeList::EU_36_TO_46));

    sizedMove($order, 'start_cutting', $byLabel, $footwear)
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors([$key => $message]);

    expect(sizedSnapshot($order))->toBe($before);
})->with(function () {
    $whole = 'M: Enter a whole number of units.';

    return [
        'every size empty' => [['S' => '', 'M' => ''], 'sizes', 'Enter at least one unit to move.', []],
        'every size 0' => [['S' => '0', 'M' => '0'], 'sizes', 'Enter at least one unit to move.', []],
        'a decimal' => [['S' => '4', 'M' => '2.5'], 'M', $whole, []],
        'a negative number' => [['S' => '4', 'M' => '-1'], 'M', $whole, []],
        'text' => [['S' => '4', 'M' => 'abc'], 'M', $whole, []],
        'more than the stage holds, and the size that was fine stays put' => [['S' => '4', 'M' => '25'], 'M', 'You can move at most 20 units of M.', []],
        'a size beyond the stock of the others, the total being fine' => [['S' => '10', 'M' => '20', 'L' => '15', 'XL' => '6'], 'XL', 'You can move at most 5 units of XL.', []],
        'a size of another list' => [['S' => '4'], 'sizes', 'The sizes ordered for Denim Jacket have changed. Enter the units again.', ['36' => '3']],
    ];
});

it('moves nothing when a size is not on the line, and the order page still shows the sizes as saved (S-61 F3)', function () {
    $order = bookSizedOrder();
    $before = sizedSnapshot($order);

    // Straight into the Action, as a window built before the product moved to other sizes would end up there.
    $refusal = null;

    try {
        app(MoveOrderLineUnits::class)->handle($order->lines()->firstOrFail(), OrderLineMove::StartCutting, sizeIds(['S' => 4]) + sizeIds(['36' => 3], SizeList::EU_36_TO_46), $this->user);
    } catch (ValidationException $e) {
        $refusal = $e->errors();
    }

    $page = pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0.sizes');

    expect($refusal)->toBe(['sizes' => ['The sizes ordered for Denim Jacket have changed. Enter the units again.']])
        ->and(sizedSnapshot($order))->toBe($before)
        ->and(array_combine(array_column(array_column($page, 'size'), 'label'), array_column($page, 'quantity')))->toBe(['S' => 10, 'M' => 20, 'L' => 15, 'XL' => 5]);
});

it('names a size of digits "size 36" in the limit (F9)', function () {
    $order = Order::factory()->create();
    OrderLine::factory()->sized(['36' => 10, '37' => 8])->create(['order_id' => $order->id, 'product_id' => demoProduct('PRD-SNK-003')->id, 'position' => 0]);
    $before = sizedSnapshot($order);
    $size = array_key_first(sizeIds(['36' => 0], SizeList::EU_36_TO_46));

    actingAs($this->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->firstOrFail()]), ['move' => 'start_cutting', 'sizes' => [$size => 11]])
        ->assertSessionHasErrors(["sizes.{$size}" => 'You can move at most 10 units of size 36.']);

    // A size that is not a number names itself too, as "Size 36" at the start of its message (S-70-a).
    actingAs($this->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->firstOrFail()]), ['move' => 'start_cutting', 'sizes' => [$size => '-1']])
        ->assertSessionHasErrors(["sizes.{$size}" => 'Size 36: Enter a whole number of units.']);

    expect(sizedSnapshot($order))->toBe($before);
});

it('moves a line ordered as M 20 only through the per-size payload (AC-2)', function () {
    $order = bookSizedOrder(['M' => 20]);

    sizedMove($order, 'start_cutting', ['M' => 5])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved to In cutting. Materials taken out of stock.']);

    expect(jacketSizes($order))->toBe(['M' => [20, 5, 0, 0]]);

    // The single field of a line that counts as one size is not read: this line needs its sizes.
    actingAs($this->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->firstOrFail()]), ['move' => 'mark_ready', 'units' => 2])
        ->assertSessionHasErrors(['sizes' => 'Enter at least one unit to move.']);

    expect(jacketSizes($order))->toBe(['M' => [20, 5, 0, 0]]);
});

it('adds every size to the Orders list and to the statuses: In progress after 14 units, Shipped once every size has shipped (AC-6)', function () {
    $order = bookSizedOrder();

    $figures = function () use ($order): array {
        $line = pageProp(actingAs($this->user)->get(route('orders.show', $order))->assertOk(), 'order.lines.0');
        $list = actingAs($this->user)->get(route('orders.index', ['status' => 'all']))->assertOk();

        return [pageProp($list, 'totals.in_cutting'), pageProp($list, 'orders.data.0.in_cutting'), $line['status'], pageProp($list, 'orders.data.0.status')];
    };

    sizedMove($order, 'start_cutting', ['S' => 4, 'M' => 10])->assertSessionHasNoErrors();

    expect($figures())->toBe([14, 14, 'in_progress', 'in_progress']);

    // Everything else: cut the rest, then every size ready, then every size shipped.
    sizedMove($order, 'start_cutting', ['S' => 6, 'M' => 10, 'L' => 15, 'XL' => 5])->assertSessionHasNoErrors();
    sizedMove($order, 'mark_ready', ['S' => 10, 'M' => 20, 'L' => 15, 'XL' => 5])->assertSessionHasNoErrors();

    expect($figures())->toBe([0, 0, 'in_progress', 'in_progress']);

    sizedMove($order, 'ship', ['S' => 10, 'M' => 20, 'L' => 15, 'XL' => 5])->assertSessionHasNoErrors();

    expect($figures())->toBe([0, 0, 'shipped', 'shipped'])
        ->and(jacketSizes($order))->toBe(['S' => [10, 0, 0, 10], 'M' => [20, 0, 0, 20], 'L' => [15, 0, 0, 15], 'XL' => [5, 0, 0, 5]]);
});

it('rolls everything back when writing a size row fails (AC-2)', function () {
    $order = bookSizedOrder();
    $line = $order->lines()->firstOrFail();
    $before = sizedSnapshot($order);
    $sizes = sizeIds(['S' => 4, 'M' => 10]);

    OrderLineSize::saving(function (): void {
        throw new RuntimeException('The size rows failed.');
    });

    // Start cutting has taken the materials and written the history by then: all of it goes.
    expect(fn () => app(MoveOrderLineUnits::class)->handle($line, OrderLineMove::StartCutting, $sizes, $this->user))
        ->toThrow(RuntimeException::class, 'The size rows failed.')
        ->and(sizedSnapshot($order))->toBe($before);
});
