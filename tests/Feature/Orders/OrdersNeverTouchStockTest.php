<?php

use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLineCut;
use App\Models\Size;
use App\Models\StockMovement;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Orders never touch materials (S-53-d; INV-14: AC-2, AC-4, AC-6)
|--------------------------------------------------------------------------
|
| Since INV-14 an order holds no materials and no production (ADR-0026). Boutique Lina's Denim Jacket line keeps a cut recorded before
| INV-14 (the materials' history); delivering, moving back, cancelling, editing and deleting the order leave every material, the whole
| stock history and the cuts exactly as they were.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    DemoHistory::seed();
});

/**
 * What these actions must leave alone: every material, the whole stock history and the cuts.
 *
 * @return array<string, mixed>
 */
function stockHistoryAndCuts(): array
{
    return [
        'materials' => Material::query()->withTrashed()->orderBy('id')->get()->toArray(),
        'history' => StockMovement::query()->orderBy('id')->get()->toArray(),
        'cuts' => OrderLineCut::query()->orderBy('id')->get()->toArray(),
    ];
}

it('leaves every material, its history and the past cuts alone (INV-14)', function (Closure $act) {
    $order = bookSizedOrder(['S' => 10, 'M' => 20], ['M' => [2, 0]]);
    OrderLineCut::factory()->create(['order_line_id' => $order->lines()->firstOrFail()->id, 'units' => 5]);
    setFinishedStock(demoProduct('PRD-JKT-001'), ['White' => [0, 4, 0, 0, 0, 0, 0]]);
    $before = stockHistoryAndCuts();

    $act($order);

    expect(stockHistoryAndCuts())->toBe($before);
})->with([
    'Mark delivered' => [fn (Order $order) => actingAs(test()->user)
        ->post(route('orders.lines.deliveries.store', [$order, $order->lines()->firstOrFail()]), ['cells' => orderUnits(pid(1), ['S' => 3])])
        ->assertSessionHasNoErrors()],
    'Move back' => [fn (Order $order) => actingAs(test()->user)
        ->post(route('orders.lines.deliveries.destroy', [$order, $order->lines()->firstOrFail()]), ['cells' => orderUnits(pid(1), ['M' => 1])])
        ->assertSessionHasNoErrors()],
    'Cancel units' => [fn (Order $order) => actingAs(test()->user)
        ->post(route('orders.lines.cancellations.store', [$order, $order->lines()->firstOrFail()]), ['cells' => orderUnits(pid(1), ['S' => 2])])
        ->assertSessionHasNoErrors()],
    'editing the order\'s quantities' => [fn (Order $order) => actingAs(test()->user)
        ->put(route('orders.update', $order), validOrderPayload(['lines' => [['product_id' => pid(1), 'units' => orderUnits(pid(1), ['S' => '12', 'M' => '20'])]]]))
        ->assertSessionHasNoErrors()],
    'deleting the order' => [fn (Order $order) => actingAs(test()->user)->delete(route('orders.destroy', $order))->assertSessionHasNoErrors()],
]);

it('always deletes an order, also with delivered units, and puts nothing back on the shelf (INV-14 AC-6)', function () {
    $order = bookSizedOrder(['S' => 10, 'M' => 20], ['M' => [5, 3]]);
    setFinishedStock(demoProduct('PRD-JKT-001'), ['White' => [0, 4, 0, 0, 0, 0, 0]]);
    $shelf = DB::table('product_size_stocks')->orderBy('id')->get(['colour_id', 'size_id', 'units'])->toArray();

    actingAs($this->user)->from(route('orders.show', $order))->delete(route('orders.destroy', $order))
        ->assertRedirect(route('orders.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order deleted.']);

    expect(Order::query()->find($order->id))->toBeNull()
        ->and(DB::table('product_size_stocks')->orderBy('id')->get(['colour_id', 'size_id', 'units'])->toArray())->toEqual($shelf);
});

it('no longer has the cutting routes of an order (INV-14 AC-2)', function () {
    $order = bookSizedOrder();
    $line = $order->lines()->firstOrFail();

    actingAs($this->user)->post("/orders/{$order->id}/lines/{$line->id}/moves", ['move' => 'start_cutting', 'units' => 5])->assertNotFound();
    actingAs($this->user)->get("/orders/{$order->id}/lines/{$line->id}/moves/preview?move=start_cutting&units=5")->assertNotFound();

    expect(Size::query()->count())->toBe(19);
});
