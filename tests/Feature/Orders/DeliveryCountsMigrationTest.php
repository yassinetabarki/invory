<?php

use App\Models\Colour;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Orders count Delivered and Cancelled; old orders mapped (INV-14-a, AC-7)
|--------------------------------------------------------------------------
|
| The migration is rolled back to the stage counts of before INV-14, rows are written in that shape, and `up()` runs on them: Shipped
| becomes Delivered, the rest To deliver, and the Ready-to-ship units go on the shelf as one restock "Orders" per workshop.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->travelTo('2026-11-07 09:00:00');
});

/** The id of an Apparel size: in the Apparel list while the lists exist (before INV-18-c), in the oldest workshop's own list after. */
function apparelSize(string $label): int
{
    $size = DB::table('sizes')->where('label', $label);

    return (int) (Schema::hasTable('size_lists')
        ? $size->whereIn('size_list_id', DB::table('size_lists')->where('code', 's_to_xl')->select('id'))->value('id')
        : $size->where('workshop_id', DB::table('workshops')->min('id'))->value('id'));
}

/** A line of the jacket in the old shape: `[colour code, size, quantity, in cutting, ready to ship, shipped]` per row. */
function oldShapeLine(Order $order, Product $product, array $rows): int
{
    $lineId = DB::table('order_lines')->insertGetId([
        'workshop_id' => $order->workshop_id, 'order_id' => $order->id, 'product_id' => $product->id, 'position' => 0,
        'size_list_id' => DB::table('size_lists')->where('code', 's_to_xl')->value('id'),
        'quantity' => array_sum(array_column($rows, 2)), 'in_cutting' => array_sum(array_column($rows, 3)),
        'ready_to_ship' => array_sum(array_column($rows, 4)), 'shipped' => array_sum(array_column($rows, 5)),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ($rows as [$colour, $size, $quantity, $inCutting, $ready, $shipped]) {
        DB::table('order_line_sizes')->insert([
            'workshop_id' => $order->workshop_id, 'order_line_id' => $lineId, 'colour_id' => Colour::query()->where('code', $colour)->value('id'),
            'size_id' => apparelSize($size), 'quantity' => $quantity, 'in_cutting' => $inCutting, 'ready_to_ship' => $ready, 'shipped' => $shipped,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return $lineId;
}

/** @return list<array{int, int, int}> each row's [quantity, delivered, cancelled] */
function deliveryRows(int $lineId): array
{
    return DB::table('order_line_sizes')->where('order_line_id', $lineId)->orderBy('id')->get()
        ->map(fn (object $row): array => [(int) $row->quantity, (int) $row->delivered, (int) $row->cancelled])->all();
}

it('turns Shipped into Delivered and the rest into To deliver, and puts Ready to ship on the shelf as one restock "Orders"', function () {
    $jacket = demoProduct('PRD-JKT-001');
    setFinishedStock($jacket, ['Indigo' => [0, 2, 0, 0, 0, 0, 0]]);
    $order = Order::factory()->create(['client' => 'Boutique Lina']);

    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();
    $lineId = oldShapeLine($order, $jacket, [['IND', 'S', 10, 0, 10, 0], ['IND', 'XL', 5, 0, 0, 5], ['IND', 'M', 20, 4, 0, 0]]);
    deliveryCountsMigration()->up();
    sizesPerWorkshopMigration()->up();

    $line = OrderLine::query()->findOrFail($lineId);
    $restock = DB::table('product_restocks')->where('reference', 'Orders')->sole();

    expect(deliveryRows($lineId))->toBe([[10, 0, 0], [5, 5, 0], [20, 0, 0]])
        ->and([$line->quantity, $line->delivered, $line->cancelled, $line->to_deliver])->toBe([35, 5, 0, 30])
        ->and(Schema::hasColumns('order_lines', ['in_cutting', 'ready_to_ship', 'shipped']))->toBeFalse()
        ->and(Schema::hasColumn('order_line_sizes', 'shipped'))->toBeFalse()
        ->and([$restock->restocked_on, $restock->note, (int) $restock->units, $restock->user_id])->toBe(['2026-11-07', 'Ready to ship before INV-14', 10, null])
        ->and(DB::table('product_restock_lines')->where('product_restock_id', $restock->id)->get(['product_id', 'colour_id', 'size_id', 'units'])->map(fn (object $row): array => (array) $row)->all())
        ->toEqual([['product_id' => $jacket->id, 'colour_id' => demoColour('IND')->id, 'size_id' => apparelSize('S'), 'units' => 10]])
        // The shelf of Indigo S grows by 10: 2 → 12.
        ->and((int) DB::table('product_size_stocks')->where(['product_id' => $jacket->id, 'colour_id' => demoColour('IND')->id, 'size_id' => apparelSize('S')])->value('units'))->toBe(12);
});

it('makes a shelf row and links the colour when there was none, keeps workshops apart, and writes no restock without Ready to ship', function () {
    $jeans = demoProduct('PRD-JNS-005');
    $order = Order::factory()->create();
    [$other, $otherProduct, $otherOrder] = CurrentWorkshop::run(Workshop::factory()->create(), function (): array {
        $product = Product::factory()->create(['category' => 'apparel']);

        return [$product->workshop_id, $product, Order::factory()->create()];
    });

    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();
    oldShapeLine($order, $jeans, [['BLK', 'M', 3, 0, 3, 0]]);
    CurrentWorkshop::run(Workshop::query()->findOrFail($other), fn () => oldShapeLine($otherOrder, $otherProduct, [['WHT', 'L', 4, 0, 0, 4]]));
    deliveryCountsMigration()->up();
    sizesPerWorkshopMigration()->up();

    expect(DB::table('product_restocks')->where('reference', 'Orders')->pluck('workshop_id')->all())->toBe([$jeans->workshop_id])
        ->and(DB::table('product_colours')->where(['product_id' => $jeans->id, 'colour_id' => demoColour('BLK')->id])->exists())->toBeTrue()
        ->and((int) DB::table('product_size_stocks')->where(['product_id' => $jeans->id, 'size_id' => apparelSize('M')])->value('units'))->toBe(3)
        ->and(DB::table('product_size_stocks')->where('product_id', $otherProduct->id)->exists())->toBeFalse();
});

it('stops, naming the variant, when the shelf would pass 1,000,000 units', function () {
    $jacket = demoProduct('PRD-JKT-001');
    setFinishedStock($jacket, ['Indigo' => [0, 999995, 0, 0, 0, 0, 0]]);
    $order = Order::factory()->create();

    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();
    oldShapeLine($order, $jacket, [['IND', 'S', 10, 0, 10, 0]]);

    expect(fn () => deliveryCountsMigration()->up())->toThrow(RuntimeException::class, 'Denim Jacket Indigo S would hold 1000005 units on the shelf, more than 1,000,000');
});

it('brings the stage counts back on down(), Shipped = Delivered', function () {
    $line = OrderLine::factory()->sized(['S' => 10, 'M' => 4], ['M' => [3, 1]])->create(['product_id' => demoProduct('PRD-JKT-001')->id]);

    sizesPerWorkshopMigration()->down();
    deliveryCountsMigration()->down();

    $rows = DB::table('order_line_sizes')->where('order_line_id', $line->id)->orderBy('id')->get(['quantity', 'in_cutting', 'ready_to_ship', 'shipped'])
        ->map(fn (object $row): array => [(int) $row->quantity, (int) $row->in_cutting, (int) $row->ready_to_ship, (int) $row->shipped])->all();

    expect($rows)->toBe([[10, 0, 0, 0], [4, 0, 0, 3]])
        ->and(Schema::hasColumn('order_lines', 'delivered'))->toBeFalse();

    deliveryCountsMigration()->up();
    sizesPerWorkshopMigration()->up();
});

it('refuses negative counts and counts above the quantity (PostgreSQL)', function () {
    $line = OrderLine::factory()->sized(['S' => 4])->create(['product_id' => demoProduct('PRD-JKT-001')->id]);

    expect(fn () => DB::transaction(fn () => DB::table('order_line_sizes')->where('order_line_id', $line->id)->update(['delivered' => 3, 'cancelled' => 2])))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => DB::table('order_lines')->whereKey($line->id)->update(['cancelled' => 5])))->toThrow(QueryException::class);
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'PostgreSQL only: SQLite keeps no CHECK.');
