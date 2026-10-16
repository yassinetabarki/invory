<?php

use App\Actions\Orders\MoveOrderLineUnits;
use App\Enums\OrderLineMove;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderLineCut;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The move window's materials table: the preview of Start cutting (S-53-c: AC-1, AC-3 server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's order of the card: Denim Jacket 50, Denim Jeans 5-pocket 30, on the demo stock (Denim 12oz Indigo 250 m, Metal shank
| button 17mm 900 pc, Polyester thread 20,000 m, Woven brand label 400 pc). The guards of the route (guest, unverified, digits, another
| workshop's order) are walked by RoutesTest and CrossWorkshopAccessTest; the cut itself is in StartCuttingStockTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** The preview of the line at that position (0 = Denim Jacket), as the window asks for it: JSON. */
function previewMove(Order $order, int $position, array $query): TestResponse
{
    $line = $order->lines()->get()[$position];

    return actingAs(test()->user)->getJson(route('orders.lines.moves.preview', [$order, $line, ...$query]));
}

/** The `material` of a row: the picker's reference of a demo material. */
function previewRef(string $sku): array
{
    $material = demoMaterial($sku);

    return ['id' => $material->id, 'sku' => $sku, 'name' => $material->name, 'unit' => $material->unit->code];
}

/** A row of Start cutting: never `not_returned`. */
function startCuttingRow(string $sku, string $needed, string $inStock, ?string $after, ?string $shortBy = null): array
{
    return [
        'material' => previewRef($sku), 'quantity' => $needed, 'in_stock' => $inStock, 'after' => $after, 'short_by' => $shortBy, 'not_returned' => null,
    ];
}

it('shows what Start cutting 30 Denim Jackets takes, by material name, and what the stock allows (AC-1)', function () {
    $order = bookOrder();

    previewMove($order, 0, ['move' => 'start_cutting', 'units' => 30])
        ->assertOk()
        ->assertExactJson([
            'move' => 'start_cutting',
            'units' => 30,
            'rows' => [
                startCuttingRow('MAT-DEN-012', '59.4000', '250.0000', '190.6000'),
                startCuttingRow('MAT-BTN-017', '240.0000', '900.0000', '660.0000'),
                startCuttingRow('MAT-THR-PES', '3780.0000', '20000.0000', '16220.0000'),
                startCuttingRow('MAT-LBL-WOV', '30.0000', '400.0000', '370.0000'),
            ],
            'has_composition' => true,
            'nothing_taken' => false,
            'max_cuttable' => 112,
            'short_message' => null,
        ]);
});

it('changes nothing: the preview is a read', function () {
    $order = bookOrder();
    $before = figuresOrdersLeaveAlone();

    previewMove($order, 0, ['move' => 'start_cutting', 'units' => 30])->assertOk();

    expect(figuresOrdersLeaveAlone())->toBe($before)
        ->and($order->lines()->get()[0]->in_cutting)->toBe(0);
});

it('says there is no composition, with no rows and no maximum (AC-1)', function () {
    $product = Product::factory()->create(['name' => 'Plain Tote']);
    $order = bookOrder(lines: [[$product, 10]]);

    previewMove($order, 0, ['move' => 'start_cutting', 'units' => 10])
        ->assertOk()
        ->assertJson(['rows' => [], 'has_composition' => false, 'max_cuttable' => null, 'short_message' => null, 'nothing_taken' => false]);
});

it('previews a product archived or set back to draft since the order was taken (AC-1)', function (string $status) {
    $order = bookOrder();
    demoProduct('PRD-JKT-001')->update(['status' => $status]);

    previewMove($order, 0, ['move' => 'start_cutting', 'units' => 30])
        ->assertOk()
        ->assertJsonPath('max_cuttable', 112)
        ->assertJsonCount(4, 'rows');
})->with(['archived', 'draft']);

it('refuses to preview Start cutting on a deleted product, under units (AC-1)', function () {
    $order = bookOrder();
    demoProduct('PRD-JKT-001')->delete();

    previewMove($order, 0, ['move' => 'start_cutting', 'units' => 30])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['units' => 'Denim Jacket was deleted, so it can\'t be cut.']);
});

it('refuses a move or a number of units the window can\'t ask for (AC-1)', function (array $query, string $field, ?string $message = null) {
    $order = bookOrder();

    $response = previewMove($order, 0, $query)->assertUnprocessable();

    $message === null ? $response->assertJsonValidationErrors($field) : $response->assertJsonValidationErrors([$field => $message]);
})->with([
    'zero units' => [['move' => 'start_cutting', 'units' => 0], 'units'],
    'a decimal' => [['move' => 'start_cutting', 'units' => '2.5'], 'units'],
    'more than a line can hold' => [['move' => 'start_cutting', 'units' => 1000001], 'units'],
    'no units' => [['move' => 'start_cutting', 'units' => ''], 'units', 'Enter a whole number of units, at least 1.'],
    'letters' => [['move' => 'start_cutting', 'units' => 'abc'], 'units'],
    'a move with no table' => [['move' => 'mark_ready', 'units' => 5], 'move'],
    'Move back to To start with zero units' => [['move' => 'back_to_start', 'units' => 0], 'units'],
    'Move back to To start with too many units' => [['move' => 'back_to_start', 'units' => 1000001], 'units'],
    'a move that doesn\'t exist' => [['move' => 'teleport', 'units' => 5], 'move'],
    'no move' => [['units' => 5], 'move'],
]);

it('refuses more units than the stage holds now, under units, the deleted product first, and changes nothing (AC-4)', function (string $move, array $stages, bool $deleted, int $units, string $message) {
    $order = bookOrderAtStages($stages);
    $deleted && demoProduct('PRD-JKT-001')->delete();
    $before = $deleted ? null : figuresOrdersLeaveAlone();

    previewMove($order, 0, ['move' => $move, 'units' => $units])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['units' => $message])
        ->assertJsonMissingPath('rows');

    $deleted || expect(figuresOrdersLeaveAlone())->toBe($before);
})->with([
    'Start cutting 60 with 50 to start' => ['start_cutting', [[50, 0, 0, 0]], false, 60, 'You can move at most 50 units.'],
    'Move back to To start 21 with 20 in cutting' => ['back_to_start', [[50, 20, 0, 0]], false, 21, 'You can move at most 20 units.'],
    'Start cutting 2 with 1 to start' => ['start_cutting', [[50, 49, 0, 0]], false, 2, 'You can move at most 1 unit.'],
    'a deleted product wins over the limit' => ['start_cutting', [[50, 0, 0, 0]], true, 60, 'Denim Jacket was deleted, so it can\'t be cut.'],
]);

it('previews exactly the units the stage holds (AC-4)', function () {
    $order = bookOrderAtStages([[50, 0, 0, 0]]);

    previewMove($order, 0, ['move' => 'start_cutting', 'units' => 50])->assertOk()->assertJsonPath('units', 50);
});

it('answers 404 for a line of another order', function () {
    $order = bookOrder();
    $other = bookOrder(['client' => 'Atelier Nour']);

    actingAs($this->user)
        ->getJson(route('orders.lines.moves.preview', [$order, $other->lines()->firstOrFail(), 'move' => 'start_cutting', 'units' => 5]))
        ->assertNotFound();
});

it('previews Move back to To start from the remembered cuts by material name, changing nothing, also for a deleted product (AC-5)', function () {
    $order = bookOrder();
    $move = app(MoveOrderLineUnits::class);
    $line = $order->lines()->get()[0];

    $move->handle($line, OrderLineMove::StartCutting, 30, $this->user);
    demoProduct('PRD-JKT-001')->delete();
    $before = [Material::query()->orderBy('id')->get()->toArray(), StockMovement::query()->orderBy('id')->get()->toArray(), OrderLineCut::query()->get()->toArray()];

    previewMove($order, 0, ['move' => 'back_to_start', 'units' => 5])
        ->assertOk()
        ->assertExactJson([
            'move' => 'back_to_start',
            'units' => 5,
            'rows' => [
                startCuttingRow('MAT-DEN-012', '9.9000', '190.6000', '200.5000'),
                startCuttingRow('MAT-BTN-017', '40.0000', '660.0000', '700.0000'),
                startCuttingRow('MAT-THR-PES', '630.0000', '16220.0000', '16850.0000'),
                startCuttingRow('MAT-LBL-WOV', '5.0000', '370.0000', '375.0000'),
            ],
            'has_composition' => true,
            'nothing_taken' => false,
            'max_cuttable' => null,
            'short_message' => null,
        ]);

    expect([Material::query()->orderBy('id')->get()->toArray(), StockMovement::query()->orderBy('id')->get()->toArray(), OrderLineCut::query()->get()->toArray()])->toBe($before)
        ->and($order->lines()->get()[0]->in_cutting)->toBe(30);
});
