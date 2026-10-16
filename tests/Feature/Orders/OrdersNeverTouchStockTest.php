<?php

use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLineCut;
use App\Models\StockMovement;
use Illuminate\Testing\TestResponse;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Nothing else touches stock (S-53-d: AC-6 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order, Denim Jacket 50, after a real cut of 30 on the demo stock. Only Start cutting and Move back to To start
| change a material's stock or history; these leave every material, the history and the cuts exactly as they were.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    DemoHistory::seed();
});

/** Posts a move of the Denim Jacket line (the first) and expects it to be accepted. */
function acceptedMove(Order $order, string $move, int $units): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->get()[0]]), ['move' => $move, 'units' => $units])
        ->assertSessionHasNoErrors();
}

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

it('leaves every material, its history and the cuts alone (AC-6)', function (array $setUp, Closure $act) {
    $order = bookOrder();

    foreach ($setUp as [$move, $units]) {
        acceptedMove($order, $move, $units);
    }

    $before = stockHistoryAndCuts();

    $act($order);

    expect(stockHistoryAndCuts())->toBe($before);
})->with(function () {
    $started = [['start_cutting', 30], ['mark_ready', 20], ['ship', 5]]; // To start 20 · In cutting 10 · Ready to ship 15 · Shipped 5

    return [
        'Mark ready' => [$started, fn (Order $order) => acceptedMove($order, 'mark_ready', 2)],
        'Ship' => [$started, fn (Order $order) => acceptedMove($order, 'ship', 2)],
        'Move back to In cutting' => [$started, fn (Order $order) => acceptedMove($order, 'back_to_cutting', 2)],
        'Move back to Ready to ship' => [$started, fn (Order $order) => acceptedMove($order, 'back_to_ready', 2)],
        'editing the order\'s quantities' => [$started, function (Order $order): void {
            actingAs(test()->user)->put(route('orders.update', $order), validOrderPayload(['lines' => [
                ['product_id' => pid(1), 'quantity' => '60'], ['product_id' => pid(5), 'quantity' => '35'],
            ]]))->assertSessionHasNoErrors();
        }],
        'recording a sale' => [$started, function (): void {
            actingAs(test()->user)->post(route('sales.store'), validSalePayload())->assertSessionHasNoErrors();
        }],
        'deleting an order whose cut units are all shipped' => [
            [['start_cutting', 30], ['mark_ready', 30], ['ship', 30]],
            function (Order $order): void {
                actingAs(test()->user)->delete(route('orders.destroy', $order))->assertSessionHasNoErrors();

                expect(Order::query()->find($order->id))->toBeNull()
                    ->and(OrderLineCut::query()->count())->toBe(1);
            },
        ],
        'a refused delete of an order with units in cutting' => [$started, function (Order $order): void {
            actingAs(test()->user)->delete(route('orders.destroy', $order))
                ->assertInertiaFlash('toast', ['type' => 'error', 'message' => "Boutique Lina can't be deleted: 25 units are in cutting or ready to ship. Move them back to To start first."]);

            expect(Order::query()->find($order->id))->not->toBeNull();
        }],
    ];
});
