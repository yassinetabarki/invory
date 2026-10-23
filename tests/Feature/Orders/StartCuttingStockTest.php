<?php

use App\Actions\Orders\MoveOrderLineUnits;
use App\Enums\OrderLineMove;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Inventory\InventorySummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Start cutting takes the materials out of stock (S-53-c: AC-2, AC-3, AC-4, AC-7 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order of the card, Denim Jacket 50, on the demo stock (Denim 12oz Indigo 250 m, Metal shank button 17mm 900 pc,
| Polyester thread 20,000 m, Woven brand label 400 pc), with the demo stock history, so the ledger balances. Cutting 30 jackets takes
| 59.4 m, 240 pc, 3,780 m and 30 pc. The preview of the same table is in MovePreviewTest.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->admin = DemoHistory::seed(); // "Admin" wrote the history: the person who clicks below is somebody else
});

/** Posts Start cutting for the first line of the order (the Denim Jacket), coming from the order's own page. */
function cutJackets(Order $order, int|string $units): TestResponse
{
    return actingAs(test()->user)->from(route('orders.show', $order))
        ->post(route('orders.lines.moves.store', [$order, $order->lines()->get()[0]]), ['move' => 'start_cutting', 'units' => $units]);
}

/** The preview of Start cutting for the first line of the order, as JSON. */
function jacketPreview(Order $order, int $units): TestResponse
{
    return actingAs(test()->user)->getJson(route('orders.lines.moves.preview', [$order, $order->lines()->get()[0], 'move' => 'start_cutting', 'units' => $units]));
}

/**
 * Everything a refused cut must leave alone: the materials, the ledger, the cuts and the line counts.
 *
 * @return array<string, mixed>
 */
function stockAndCuts(): array
{
    return [
        'materials' => Material::query()->orderBy('id')->get()->toArray(),
        'ledger' => StockMovement::query()->orderBy('id')->get()->toArray(),
        'cuts' => OrderLineCut::query()->orderBy('id')->get()->toArray(),
        'remembered' => OrderLineCutMaterial::query()->orderBy('id')->get()->toArray(),
        'lines' => OrderLine::query()->orderBy('id')->get()->toArray(),
    ];
}

/** The stock saved now of the material with this SKU. */
function stockOf(string $sku): string
{
    return demoMaterial($sku)->quantity_on_hand;
}

/** The saved unit cost of every material, by SKU. */
function unitCosts(): array
{
    return Material::query()->orderBy('sku')->pluck('unit_cost', 'sku')->all();
}

it('takes the materials of 30 jackets out of stock and remembers the cut (AC-2)', function () {
    $order = bookOrder();
    $costs = unitCosts();
    $jacketCost = loadedPageProp(actingAs($this->user)->get(route('products.show', pid(1))), 'product.cost');

    cutJackets($order, 30)
        ->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '30 units of Denim Jacket moved to In cutting. Materials taken out of stock.']);

    $page = actingAs($this->user)->get(route('products.show', pid(1)));

    expect(stockOf('MAT-DEN-012'))->toBe('190.6000')
        ->and(stockOf('MAT-BTN-017'))->toBe('660.0000')
        ->and(stockOf('MAT-THR-PES'))->toBe('16220.0000')
        ->and(stockOf('MAT-LBL-WOV'))->toBe('370.0000')
        ->and(loadedPageProp($page, 'product.capacity.max_producible'))->toBe(82)
        ->and(loadedPageProp($page, 'product.capacity.bottleneck.sku'))->toBe('MAT-BTN-017')
        ->and(app(InventorySummary::class)->summarize()['inventory_value'])->toBe('9936.6800')
        ->and(unitCosts())->toBe($costs)
        ->and(loadedPageProp($page, 'product.cost'))->toBe($jacketCost);

    $line = $order->lines()->get()[0];
    $cut = OrderLineCut::query()->sole();

    expect($cut->only(['order_line_id', 'workshop_id', 'units', 'units_returned', 'user_id']))->toBe([
        'order_line_id' => $line->id, 'workshop_id' => $line->workshop_id, 'units' => 30, 'units_returned' => 0, 'user_id' => $this->user->id,
    ]);

    $remembered = OrderLineCutMaterial::query()->where('order_line_cut_id', $cut->id)->orderBy('id')->get();

    expect($remembered->map(fn (OrderLineCutMaterial $row): array => [Material::query()->findOrFail($row->material_id)->sku, $row->quantity_per_unit, $row->quantity_taken])->all())->toBe([
        ['MAT-DEN-012', '1.98000000', '59.4000'],
        ['MAT-BTN-017', '8.00000000', '240.0000'],
        ['MAT-THR-PES', '126.00000000', '3780.0000'],
        ['MAT-LBL-WOV', '1.00000000', '30.0000'],
    ])->and($remembered->every(fn (OrderLineCutMaterial $row): bool => $row->workshop_id === $line->workshop_id
        && $row->unit_id === Material::query()->findOrFail($row->material_id)->unit_id))->toBeTrue();
});

it('moves a product with no composition as before: no suffix, a cut with no materials, no history, no stock change (AC-2)', function () {
    $order = bookOrder(lines: [[Product::factory()->create(['name' => 'Plain Tote']), 30]]);
    $materials = Material::query()->orderBy('id')->get()->toArray();
    $ledger = StockMovement::query()->orderBy('id')->get()->toArray();

    cutJackets($order, 30)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '30 units of Plain Tote moved to In cutting.']);

    expect(OrderLineCut::query()->sole()->units)->toBe(30)
        ->and(OrderLineCutMaterial::query()->count())->toBe(0)
        ->and(Material::query()->orderBy('id')->get()->toArray())->toBe($materials)
        ->and(StockMovement::query()->orderBy('id')->get()->toArray())->toBe($ledger)
        ->and($order->lines()->get()[0]->in_cutting)->toBe(30);
});

it('refuses a cut the stock can\'t cover, changes nothing, and says what the preview says (AC-3)', function (array $stock, int $units, string $message, array $shortBy) {
    foreach ($stock as $sku => $quantity) {
        demoMaterial($sku)->update(['quantity_on_hand' => $quantity]);
    }

    $order = bookOrder();
    $before = stockAndCuts();

    $preview = jacketPreview($order, $units)->assertOk();
    cutJackets($order, $units)->assertRedirect(route('orders.show', $order))->assertSessionHasErrors(['units' => $message]);

    $rows = collect($preview->json('rows'));

    expect(stockAndCuts())->toBe($before)
        ->and($preview->json('short_message'))->toBe($message)
        ->and($rows->mapWithKeys(fn (array $row): array => [$row['material']['sku'] => $row['short_by']])->filter()->all())->toBe($shortBy)
        ->and($rows->filter(fn (array $row): bool => $row['short_by'] !== null)->pluck('after')->filter()->all())->toBe([]);
})->with([
    'buttons at 100, cut 20' => [
        ['MAT-BTN-017' => '100.0000'], 20,
        'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.',
        ['MAT-BTN-017' => '60.0000'],
    ],
    'buttons at 100 and thread at 2,000 m: both short, the button is named' => [
        ['MAT-BTN-017' => '100.0000', 'MAT-THR-PES' => '2000.0000'], 20,
        'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.',
        ['MAT-BTN-017' => '60.0000', 'MAT-THR-PES' => '520.0000'],
    ],
    'buttons at 7, cut 20' => [
        ['MAT-BTN-017' => '7.0000'], 20,
        'Not enough stock to cut 20 units. You can\'t cut any now: restock Metal shank button 17mm first.',
        ['MAT-BTN-017' => '153.0000'],
    ],
    'buttons at 7, cut 1: the singular' => [
        ['MAT-BTN-017' => '7.0000'], 1,
        'Not enough stock to cut 1 unit. You can\'t cut any now: restock Metal shank button 17mm first.',
        ['MAT-BTN-017' => '1.0000'],
    ],
]);

it('cuts exactly what the stock allows and leaves the rest (AC-3)', function () {
    demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '100.0000']);

    cutJackets(bookOrder(), 12)->assertSessionHasNoErrors();

    expect(stockOf('MAT-BTN-017'))->toBe('4.0000');
});

it('checks the stock saved now, not the one the preview saw (AC-3)', function () {
    demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '100.0000']);
    $order = bookOrder();

    expect(jacketPreview($order, 12)->json('max_cuttable'))->toBe(12);

    // Someone else's cut of another product uses 40 buttons in the meantime.
    demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '60.0000']);
    $before = stockAndCuts();

    cutJackets($order, 12)->assertSessionHasErrors(['units' => 'Not enough stock to cut 12 units. You can cut at most 7 now: Metal shank button 17mm runs short. Restock it first, or cut 7 or fewer.']);

    expect(stockAndCuts())->toBe($before);
});

it('writes one Cutting entry per material: who, which order, which product, how many units, and the day of the click (AC-4)', function () {
    $this->travelTo('2026-10-20 09:30:00');
    $order = bookOrder();

    cutJackets($order, 30)->assertSessionHasNoErrors();

    $entries = StockMovement::query()->where('type', StockMovementType::Cutting)->orderBy('id')->get();

    expect($entries)->toHaveCount(4)
        ->and($entries->map(fn (StockMovement $entry): array => [
            Material::query()->findOrFail($entry->material_id)->sku, $entry->quantity, $entry->quantity_before, $entry->quantity_after,
        ])->all())->toBe([
            ['MAT-DEN-012', '59.4000', '250.0000', '190.6000'],
            ['MAT-BTN-017', '240.0000', '900.0000', '660.0000'],
            ['MAT-THR-PES', '3780.0000', '20000.0000', '16220.0000'],
            ['MAT-LBL-WOV', '30.0000', '400.0000', '370.0000'],
        ])
        ->and($entries->every(fn (StockMovement $entry): bool => $entry->order_id === $order->id
            && $entry->product_id === pid(1)
            && $entry->units === 30
            && $entry->user_id === $this->user->id
            && $entry->unit_price === null
            && $entry->occurred_on->toDateString() === '2026-10-20'
            && $entry->workshop_id === $this->user->workshop_id))->toBeTrue();
});

it('shows what is left: units still to start aren\'t set aside (AC-7)', function () {
    $canProduce = fn () => loadedPageProp(actingAs($this->user)->get(route('products.show', pid(1))), 'product.capacity.max_producible');
    $order = bookOrder();

    expect($canProduce())->toBe(112);

    cutJackets($order, 30)->assertSessionHasNoErrors();

    // 20 jackets of the order are still to start: Can produce reads 82, not 62.
    expect($order->lines()->get()[0]->to_start)->toBe(20)
        ->and($canProduce())->toBe(82);
});

it('takes 80 jackets: the denim is at 91.6 m and Low stock, "Needs restock" goes from 4 to 5, and the ledger balances (AC-7)', function () {
    $needsRestock = fn () => loadedPageProp(actingAs($this->user)->get(route('dashboard')), 'stats.needs_restock_count');
    $order = bookOrder(lines: [[demoProduct('PRD-JKT-001'), 80]]);

    expect($needsRestock())->toBe(4)
        ->and(demoMaterial('MAT-DEN-012')->stockStatus()->value)->toBe('in_stock');

    cutJackets($order, 80)->assertSessionHasNoErrors();

    expect(stockOf('MAT-DEN-012'))->toBe('91.6000')
        ->and(demoMaterial('MAT-DEN-012')->stockStatus()->value)->toBe('low')
        ->and($needsRestock())->toBe(5);

    $this->artisan('invory:stock:verify')->assertExitCode(0);
});

it('rolls back everything when writing the third material fails', function () {
    $order = bookOrder();
    $before = stockAndCuts();
    $written = 0;

    StockMovement::creating(function (StockMovement $movement) use (&$written): void {
        if ($movement->type === StockMovementType::Cutting && ++$written === 3) {
            throw new RuntimeException('The third material failed.');
        }
    });

    $line = $order->lines()->get()[0];

    expect(fn () => app(MoveOrderLineUnits::class)->handle($line, OrderLineMove::StartCutting, [$line->sizes()->value('size_id') => 30], $this->user))
        ->toThrow(RuntimeException::class, 'The third material failed.')
        ->and($written)->toBe(3)
        ->and(stockAndCuts())->toBe($before);
});

it('locks the order, the line, its size rows and then the materials in ascending id (PostgreSQL)', function () {
    $order = bookOrder();

    $locks = queriesMatching(queryLog(fn () => cutJackets($order, 5)->assertSessionHasNoErrors()), 'for update');

    expect($locks)->toHaveCount(4)
        ->and($locks[0])->toContain('"orders"')
        ->and($locks[1])->toContain('"order_lines"')
        ->and($locks[2])->toContain('"order_line_sizes"')
        ->and($locks[3])->toContain('"materials"')
        ->and($locks[3])->toMatch('/order by "id" asc for update/i');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'FOR UPDATE is only emitted on PostgreSQL (SQLite has no row locks).');
