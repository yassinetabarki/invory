<?php

use App\Models\Colour;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\OrderLineSize;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| An order line counts per colour and size (INV-13-a, AC-6)
|--------------------------------------------------------------------------
|
| The lines are written while the migration is up, the migration is rolled back (one row per line and size, no colour), the products'
| colours are set, and `up()` runs on the rows of before INV-13: every row takes its product's first colour, no unit or count moves.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/**
 * Every row as `[product sku, colour code, size label, quantity, delivered, cancelled]`, in id order (INV-14's counts).
 *
 * @return list<array{string, string, string, int, int, int}>
 */
function orderLineColourRows(): array
{
    return DB::table('order_line_sizes')
        ->join('order_lines', 'order_lines.id', '=', 'order_line_sizes.order_line_id')
        ->join('products', 'products.id', '=', 'order_lines.product_id')
        ->join('colours', 'colours.id', '=', 'order_line_sizes.colour_id')
        ->join('sizes', 'sizes.id', '=', 'order_line_sizes.size_id')
        ->orderBy('order_line_sizes.id')
        ->get(['products.sku', 'colours.code', 'sizes.label', 'order_line_sizes.quantity', 'order_line_sizes.delivered', 'order_line_sizes.cancelled'])
        ->map(fn (object $row): array => [$row->sku, $row->code, $row->label, (int) $row->quantity, (int) $row->delivered, (int) $row->cancelled])
        ->all();
}

/** @param  list<string>  $codes */
function colourProductForOrderTest(Product $product, array $codes): void
{
    $product->colours()->sync(Colour::query()->whereIn('code', $codes)->pluck('id')->mapWithKeys(fn (int $id): array => [$id => ['workshop_id' => $product->workshop_id]])->all());
}

it('gives every row its product\'s first colour and keeps every quantity and count', function () {
    $jacket = demoProduct('PRD-JKT-001');
    $jeans = demoProduct('PRD-JNS-005');
    $tote = demoProduct('PRD-TOT-004');
    $order = Order::factory()->create();
    OrderLine::factory()->sized(['S' => 10, 'M' => 20], ['M' => [5, 0]])->create(['order_id' => $order->id, 'product_id' => $jacket->id, 'position' => 0]);
    OrderLine::factory()->sized(['L' => 7])->create(['order_id' => $order->id, 'product_id' => $jeans->id, 'position' => 1]);
    OrderLine::factory()->create(['order_id' => $order->id, 'product_id' => $tote->id, 'quantity' => 4, 'position' => 2]);
    $jeans->delete(); // A deleted product's lines are filled too.

    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();

    orderLineColoursMigration()->down();
    expect(Schema::hasColumn('order_line_sizes', 'colour_id'))->toBeFalse();

    // Indigo comes after Black in list order, so Black is the jacket's first; the jeans only have Indigo; the tote has no colour.
    colourProductForOrderTest($jacket, ['IND', 'BLK']);
    colourProductForOrderTest(Product::withTrashed()->findOrFail($jeans->id), ['IND']);
    $tote->colours()->detach();

    orderLineColoursMigration()->up();

    deliveryCountsMigration()->up();
    sizesPerWorkshopMigration()->up();

    expect(orderLineColourRows())->toBe([
        ['PRD-JKT-001', 'BLK', 'S', 10, 0, 0],
        ['PRD-JKT-001', 'BLK', 'M', 20, 5, 0],
        ['PRD-JNS-005', 'IND', 'L', 7, 0, 0],
        ['PRD-TOT-004', 'WHT', 'One size', 4, 0, 0],
    ])
        // The tote got its workshop's first colour (White), linked to it, so it never reads "No colour".
        ->and($tote->colours()->pluck('code')->all())->toBe(['WHT'])
        ->and(Schema::hasColumn('order_line_sizes', 'colour_id'))->toBeTrue();
});

it('adds the colours of a size back into one row on down()', function () {
    $jacket = demoProduct('PRD-JKT-001');
    colourProductForOrderTest($jacket, ['BLK', 'IND']);
    $line = OrderLine::factory()->sized(['S' => 4])->create(['product_id' => $jacket->id]);
    $first = OrderLineSize::query()->where('order_line_id', $line->id)->firstOrFail();

    // Back in the shape of before INV-14, with its stage counts.
    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();
    DB::table('order_line_sizes')->where('id', $first->id)->update(['in_cutting' => 1, 'shipped' => 2]);
    DB::table('order_line_sizes')->insert([
        'workshop_id' => $line->workshop_id, 'order_line_id' => $line->id, 'colour_id' => demoColour('IND')->id, 'size_id' => $first->size_id,
        'quantity' => 6, 'ready_to_ship' => 3, 'created_at' => now(), 'updated_at' => now(),
    ]);

    orderLineColoursMigration()->down();

    $rows = DB::table('order_line_sizes')->where('order_line_id', $line->id)->get();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->id)->toBe($first->id)
        ->and([(int) $rows[0]->quantity, (int) $rows[0]->in_cutting, (int) $rows[0]->ready_to_ship, (int) $rows[0]->shipped])->toBe([10, 1, 3, 2]);

    orderLineColoursMigration()->up();

    deliveryCountsMigration()->up();
    sizesPerWorkshopMigration()->up();
});

it('refuses a row with no colour and a second row of the same colour and size', function () {
    $line = OrderLine::factory()->sized(['S' => 4])->create(['product_id' => demoProduct('PRD-JKT-001')->id]);
    $row = OrderLineSize::query()->where('order_line_id', $line->id)->firstOrFail();
    $copy = fn (array $override): array => [
        'workshop_id' => $line->workshop_id, 'order_line_id' => $line->id, 'size_id' => $row->size_id, 'quantity' => 1,
        'colour_id' => $row->colour_id, 'created_at' => now(), 'updated_at' => now(), ...$override,
    ];

    expect(fn () => DB::transaction(fn () => DB::table('order_line_sizes')->insert($copy([]))))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => DB::table('order_line_sizes')->insert($copy(['colour_id' => null]))))->toThrow(QueryException::class)
        // Another colour of the same size is a row of its own.
        ->and(DB::table('order_line_sizes')->insert($copy(['colour_id' => demoColour('IND')->id])))->toBeTrue();
});

it('keeps the colour key restrictive and indexed', function () {
    $foreignKey = collect(Schema::getForeignKeys('order_line_sizes'))->firstWhere('foreign_table', 'colours');
    $indexes = collect(Schema::getIndexes('order_line_sizes'));

    expect($foreignKey['columns'])->toBe(['colour_id'])
        ->and($foreignKey['on_delete'])->toBe('restrict')
        ->and($indexes->firstWhere('columns', ['order_line_id', 'colour_id', 'size_id'])['unique'] ?? null)->toBeTrue()
        ->and($indexes->firstWhere('columns', ['order_line_id', 'size_id']))->toBeNull()
        ->and($indexes->contains(fn (array $index): bool => $index['columns'] === ['colour_id']))->toBeTrue();
});
