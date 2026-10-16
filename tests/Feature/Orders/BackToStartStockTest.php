<?php

use App\Actions\Orders\MoveOrderLineUnits;
use App\Enums\OrderLineMove;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Move back to To start returns what was taken (S-53-d: AC-5 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order of the card, Denim Jacket 50, on the demo stock (Denim 12oz Indigo 250 m, Metal shank button 17mm 900 pc,
| Polyester thread 20,000 m, Woven brand label 400 pc), with the demo stock history so the ledger balances. Cutting 30 jackets takes
| 59.4 m, 240 pc, 3,780 m and 30 pc; moving 5 back returns 9.9 m, 40 pc, 630 m and 5 pc. The cut itself is in StartCuttingStockTest.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    DemoHistory::seed();
});

/** Posts a move of the Denim Jacket line (the first), coming from the order's own page. */
function jacketMove(Order $order, string $move, int|string $units): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->get()[0]]), ['move' => $move, 'units' => $units]);
}

/** The preview of Move back to To start for the Denim Jacket line, as JSON. */
function jacketReturnPreview(Order $order, int $units): TestResponse
{
    return actingAs(test()->user)->getJson(route('orders.lines.moves.preview', [$order, $order->lines()->get()[0], 'move' => 'back_to_start', 'units' => $units]));
}

/** The stock saved now of each of the four jacket materials, by SKU. */
function jacketStock(): array
{
    return Material::query()->whereIn('sku', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])->orderBy('sku')->pluck('quantity_on_hand', 'sku')->all();
}

/** A row of the Move back to To start table; the material is read as it is now (deleted ones included). */
function returnRow(string $sku, string $quantity, string $inStock, ?string $after, ?string $notReturned = null): array
{
    $material = Material::query()->withTrashed()->where('sku', $sku)->firstOrFail();

    return [
        'material' => ['id' => $material->id, 'sku' => $sku, 'name' => $material->name, 'unit' => $material->unit->code],
        'quantity' => $quantity, 'in_stock' => $inStock, 'after' => $after, 'short_by' => null, 'not_returned' => $notReturned,
    ];
}

/** The Back from cutting entries by SKU, in entry order: `[quantity, before, after]`. */
function returnEntries(): array
{
    return StockMovement::query()->where('type', StockMovementType::CuttingReturn)->orderBy('id')->get()
        ->mapWithKeys(fn (StockMovement $entry): array => [Material::query()->findOrFail($entry->material_id)->sku => [$entry->quantity, $entry->quantity_before, $entry->quantity_after]])
        ->all();
}

/** Changes how many buttons one jacket needs now. */
function jacketNeedsButtons(string $quantity): void
{
    demoProduct('PRD-JKT-001')->components()->where('material_id', demoMaterial('MAT-BTN-017')->id)->update(['quantity' => $quantity]);
}

/** Boutique Lina's order with 30 jackets cut and 10 of them moved on: To start 20 · In cutting 20 · Ready to ship 10. */
function orderCutAndMovedOn(): Order
{
    $order = bookOrder();

    jacketMove($order, 'start_cutting', 30)->assertSessionHasNoErrors();
    jacketMove($order, 'mark_ready', 10)->assertSessionHasNoErrors();

    return $order;
}

it('returns the 5 jackets\' materials: the table, the toast, the stock, Can produce and a Back from cutting entry each (AC-5)', function () {
    $this->travelTo('2026-10-12 10:00:00');
    $order = orderCutAndMovedOn();
    $this->travelTo('2026-10-20 09:30:00');

    jacketReturnPreview($order, 5)->assertOk()->assertExactJson([
        'move' => 'back_to_start',
        'units' => 5,
        'rows' => [
            returnRow('MAT-DEN-012', '9.9000', '190.6000', '200.5000'),
            returnRow('MAT-BTN-017', '40.0000', '660.0000', '700.0000'),
            returnRow('MAT-THR-PES', '630.0000', '16220.0000', '16850.0000'),
            returnRow('MAT-LBL-WOV', '5.0000', '370.0000', '375.0000'),
        ],
        'has_composition' => true,
        'nothing_taken' => false,
        'max_cuttable' => null,
        'short_message' => null,
    ]);

    jacketMove($order, 'back_to_start', 5)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved back to To start. Materials returned to stock.']);

    $line = $order->lines()->get()[0];
    $entries = StockMovement::query()->where('type', StockMovementType::CuttingReturn)->get();

    expect(jacketStock())->toBe(['MAT-BTN-017' => '700.0000', 'MAT-DEN-012' => '200.5000', 'MAT-LBL-WOV' => '375.0000', 'MAT-THR-PES' => '16850.0000'])
        ->and(loadedPageProp(actingAs($this->user)->get(route('products.show', pid(1))), 'product.capacity.max_producible'))->toBe(87)
        ->and([$line->to_start, $line->in_cutting, $line->ready_to_ship, $line->shipped])->toBe([25, 15, 10, 0])
        ->and(OrderLineCut::query()->sole()->units_returned)->toBe(5)
        ->and(returnEntries())->toBe([
            'MAT-DEN-012' => ['9.9000', '190.6000', '200.5000'],
            'MAT-BTN-017' => ['40.0000', '660.0000', '700.0000'],
            'MAT-THR-PES' => ['630.0000', '16220.0000', '16850.0000'],
            'MAT-LBL-WOV' => ['5.0000', '370.0000', '375.0000'],
        ])
        ->and($entries->every(fn (StockMovement $entry): bool => $entry->order_id === $order->id
            && $entry->product_id === pid(1)
            && $entry->units === 5
            && $entry->user_id === $this->user->id
            && $entry->unit_price === null
            && $entry->occurred_on->toDateString() === '2026-10-20'
            && $entry->workshop_id === $this->user->workshop_id))->toBeTrue();

    $this->artisan('invory:stock:verify')->assertExitCode(0);
});

it('returns what the cut took, not what the jacket needs now (AC-5)', function () {
    $order = orderCutAndMovedOn();
    jacketNeedsButtons('10.0000');

    expect(jacketReturnPreview($order, 5)->json('rows.1'))->toBe(returnRow('MAT-BTN-017', '40.0000', '660.0000', '700.0000'));

    jacketMove($order, 'back_to_start', 5)->assertSessionHasNoErrors();

    expect(jacketStock()['MAT-BTN-017'])->toBe('700.0000');
});

it('returns the latest cut first: 50 buttons for 5 units at 10 each, then 90 for 10 units at 10 and 8 (AC-5)', function () {
    $order = bookOrder();

    jacketMove($order, 'start_cutting', 30)->assertSessionHasNoErrors();
    jacketNeedsButtons('10.0000');
    jacketMove($order, 'start_cutting', 10)->assertSessionHasNoErrors();
    jacketNeedsButtons('12.0000');

    expect(jacketStock()['MAT-BTN-017'])->toBe('560.0000')
        ->and(jacketReturnPreview($order, 5)->json('rows.1'))->toBe(returnRow('MAT-BTN-017', '50.0000', '560.0000', '610.0000'));

    jacketMove($order, 'back_to_start', 5)->assertSessionHasNoErrors();

    expect(jacketStock()['MAT-BTN-017'])->toBe('610.0000')
        ->and(jacketReturnPreview($order, 10)->json('rows.1'))->toBe(returnRow('MAT-BTN-017', '90.0000', '610.0000', '700.0000'));

    jacketMove($order, 'back_to_start', 10)->assertSessionHasNoErrors();

    // The second cut gave its last 5 units at 10 each, the first cut 5 units at 8 each.
    expect(jacketStock()['MAT-BTN-017'])->toBe('700.0000')
        ->and(OrderLineCut::query()->orderBy('id')->pluck('units_returned')->all())->toBe([5, 10]);
});

it('returns the materials of a deleted product\'s units like any other (AC-5)', function () {
    $order = orderCutAndMovedOn();
    demoProduct('PRD-JKT-001')->delete();

    expect(jacketReturnPreview($order, 5)->assertOk()->json('rows'))->toHaveCount(4);

    jacketMove($order, 'back_to_start', 5)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved back to To start. Materials returned to stock.']);

    expect(jacketStock())->toBe(['MAT-BTN-017' => '700.0000', 'MAT-DEN-012' => '200.5000', 'MAT-LBL-WOV' => '375.0000', 'MAT-THR-PES' => '16850.0000'])
        ->and(returnEntries())->toHaveCount(4);
});

it('gives nothing back for a material deleted or whose unit changed since the cut, and the other three come back (AC-5)', function (string $reason, string $flag) {
    $order = orderCutAndMovedOn();
    $label = demoMaterial('MAT-LBL-WOV');

    // Out of the composition first (a material in use can't be deleted or change unit), then deleted or given another unit.
    demoProduct('PRD-JKT-001')->components()->where('material_id', $label->id)->delete();

    if ($reason === 'deleted') {
        $label->delete();
    } else {
        $label->update(['unit_id' => Unit::query()->where('id', '!=', $label->unit_id)->firstOrFail()->id]);
    }

    jacketReturnPreview($order, 5)->assertOk()->assertJsonPath('nothing_taken', false)->assertJsonPath('rows', [
        returnRow('MAT-DEN-012', '9.9000', '190.6000', '200.5000'),
        returnRow('MAT-BTN-017', '40.0000', '660.0000', '700.0000'),
        returnRow('MAT-THR-PES', '630.0000', '16220.0000', '16850.0000'),
        returnRow('MAT-LBL-WOV', '5.0000', '370.0000', null, $flag),
    ]);

    jacketMove($order, 'back_to_start', 5)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved back to To start. Materials returned to stock.']);

    expect(array_keys(returnEntries()))->toBe(['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES'])
        ->and(StockMovement::query()->where('material_id', $label->id)->where('type', StockMovementType::CuttingReturn)->exists())->toBeFalse()
        ->and(Material::query()->withTrashed()->findOrFail($label->id)->quantity_on_hand)->toBe('370.0000')
        ->and($order->lines()->get()[0]->in_cutting)->toBe(15);
})->with([
    'deleted' => ['deleted', 'deleted'],
    'unit changed' => ['unit changed', 'unit_changed'],
]);

it('returns nothing, and says nothing about stock, when no material was taken or every one was deleted since (AC-5)', function (Closure $setUp, bool $nothingTaken, int $rows) {
    $order = $setUp();
    $before = [Material::query()->withTrashed()->orderBy('id')->get()->toArray(), StockMovement::query()->orderBy('id')->get()->toArray()];

    jacketReturnPreview($order, 5)->assertOk()->assertJsonPath('nothing_taken', $nothingTaken)->assertJsonCount($rows, 'rows');

    jacketMove($order, 'back_to_start', 5)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '5 units of Denim Jacket moved back to To start.']);

    expect([Material::query()->withTrashed()->orderBy('id')->get()->toArray(), StockMovement::query()->orderBy('id')->get()->toArray()])->toBe($before)
        ->and($order->lines()->get()[0]->in_cutting)->toBe(5);
})->with([
    'cut while the product had no composition' => [
        function (): Order {
            demoProduct('PRD-JKT-001')->components()->delete();
            $order = bookOrder();
            jacketMove($order, 'start_cutting', 10)->assertSessionHasNoErrors();

            return $order;
        },
        true, 0,
    ],
    'every material of the cut deleted since' => [
        function (): Order {
            $order = bookOrder();
            jacketMove($order, 'start_cutting', 10)->assertSessionHasNoErrors();
            demoProduct('PRD-JKT-001')->components()->delete();
            Material::query()->whereIn('sku', ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES', 'MAT-LBL-WOV'])->get()->each->delete();

            return $order;
        },
        false, 4,
    ],
]);

it('is back at the starting stock after cuts and moves back in any mix, and the ledger balances (AC-5)', function () {
    $order = bookOrder();
    $start = jacketStock();

    jacketMove($order, 'start_cutting', 30)->assertSessionHasNoErrors();
    jacketNeedsButtons('9.0000'); // a composition change in between changes nothing about what comes back
    jacketMove($order, 'start_cutting', 10)->assertSessionHasNoErrors();
    jacketMove($order, 'back_to_start', 7)->assertSessionHasNoErrors();
    jacketMove($order, 'back_to_start', 33)->assertSessionHasNoErrors();

    expect(jacketStock())->toBe($start)
        ->and(OrderLineCut::query()->orderBy('id')->pluck('units_returned')->all())->toBe([30, 10])
        ->and($order->lines()->get()[0]->in_cutting)->toBe(0);

    $this->artisan('invory:stock:verify')->assertExitCode(0);
});

it('returns units once when the same move is sent twice from a stale screen (AC-5)', function () {
    $order = bookOrder();
    $start = jacketStock();

    jacketMove($order, 'start_cutting', 5)->assertSessionHasNoErrors();
    jacketMove($order, 'back_to_start', 5)->assertSessionHasNoErrors();
    $afterFirst = [jacketStock(), StockMovement::query()->count()];

    jacketMove($order, 'back_to_start', 5)->assertSessionHasErrors(['units' => 'You can move at most 0 units.']);

    expect($afterFirst[0])->toBe($start)
        ->and([jacketStock(), StockMovement::query()->count()])->toBe($afterFirst)
        ->and(StockMovement::query()->where('type', StockMovementType::CuttingReturn)->count())->toBe(4);
});

it('rolls back everything when writing the third return fails (AC-5)', function () {
    $order = bookOrder();
    jacketMove($order, 'start_cutting', 30)->assertSessionHasNoErrors();

    $snapshot = fn (): array => [
        'materials' => Material::query()->orderBy('id')->get()->toArray(),
        'ledger' => StockMovement::query()->orderBy('id')->get()->toArray(),
        'cuts' => OrderLineCut::query()->orderBy('id')->get()->toArray(),
        'remembered' => OrderLineCutMaterial::query()->orderBy('id')->get()->toArray(),
        'lines' => OrderLine::query()->orderBy('id')->get()->toArray(),
    ];
    $before = $snapshot();
    $written = 0;

    StockMovement::creating(function (StockMovement $movement) use (&$written): void {
        if ($movement->type === StockMovementType::CuttingReturn && ++$written === 3) {
            throw new RuntimeException('The third return failed.');
        }
    });

    $line = $order->lines()->get()[0];

    expect(fn () => app(MoveOrderLineUnits::class)->handle($line, OrderLineMove::BackToStart, 5, $this->user))
        ->toThrow(RuntimeException::class, 'The third return failed.')
        ->and($written)->toBe(3)
        ->and($snapshot())->toBe($before)
        ->and(OrderLineCut::query()->sole()->units_returned)->toBe(0)
        ->and($order->lines()->get()[0]->in_cutting)->toBe(30)
        ->and(jacketStock())->toBe(['MAT-BTN-017' => '660.0000', 'MAT-DEN-012' => '190.6000', 'MAT-LBL-WOV' => '370.0000', 'MAT-THR-PES' => '16220.0000']);
});

it('locks the order, the line, its cuts and then the materials in ascending id (PostgreSQL)', function () {
    $order = orderCutAndMovedOn();

    $locks = queriesMatching(queryLog(fn () => jacketMove($order, 'back_to_start', 5)->assertSessionHasNoErrors()), 'for update');

    expect($locks)->toHaveCount(4)
        ->and($locks[0])->toContain('"orders"')
        ->and($locks[1])->toContain('"order_lines"')
        ->and($locks[2])->toContain('"order_line_cuts"')
        ->and($locks[3])->toContain('"materials"')
        ->and($locks[3])->toMatch('/order by "id" asc for update/i');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'FOR UPDATE is only emitted on PostgreSQL (SQLite has no row locks).');
