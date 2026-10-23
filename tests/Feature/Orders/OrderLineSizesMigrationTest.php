<?php

use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The order line sizes migration on data you already have (S-60-a, S-60 AC-5, ADR-0016)
|--------------------------------------------------------------------------
|
| The S-58 pattern: roll the migration back (order lines lose their `size_list_id`, the size table goes), put lines in the old shape, run it
| forward. Schema changes are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after the test.
*/

beforeEach(fn () => $this->seed(DemoCatalogSeeder::class));

/** The columns an order line had before the migration. */
const LINE_COLUMNS_BEFORE_SIZES = ['id', 'workshop_id', 'order_id', 'product_id', 'quantity', 'position', 'in_cutting', 'ready_to_ship', 'shipped', 'created_at', 'updated_at'];

/** Boutique Lina's Denim Jacket 50 (20 in cutting, 10 ready to ship) and Hotel Dar Said's fully shipped Leather Tote 12, in the old shape. */
function putLinesInTheOldShape(): int
{
    $workshopId = DB::table('products')->where('id', pid(1))->value('workshop_id');
    $order = fn (string $client, string $orderedOn): int => DB::table('orders')->insertGetId([
        'workshop_id' => $workshopId, 'client' => $client, 'ordered_on' => $orderedOn, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $line = fn (int $orderId, int $product, int $quantity, array $counts): array => [
        'workshop_id' => $workshopId, 'order_id' => $orderId, 'product_id' => pid($product), 'quantity' => $quantity, 'position' => 0,
        ...$counts, 'created_at' => now(), 'updated_at' => now(),
    ];

    DB::table('order_lines')->insert([
        $line($order('Boutique Lina', '2026-10-15'), 1, 50, ['in_cutting' => 20, 'ready_to_ship' => 10, 'shipped' => 0]),
        $line($order('Hotel Dar Said', '2026-10-02'), 4, 12, ['in_cutting' => 0, 'ready_to_ship' => 0, 'shipped' => 12]),
    ]);

    return $workshopId;
}

/** What the migration decides, in codes and labels (ids are not stable across a round trip): the list and the size rows of every line. */
function sizesOfEveryLine(): array
{
    return DB::table('order_line_sizes')
        ->join('order_lines', 'order_lines.id', '=', 'order_line_sizes.order_line_id')
        ->join('orders', 'orders.id', '=', 'order_lines.order_id')
        ->join('size_lists', 'size_lists.id', '=', 'order_lines.size_list_id')
        ->join('sizes', 'sizes.id', '=', 'order_line_sizes.size_id')
        ->orderBy('order_lines.id')
        ->get([
            'orders.client', 'size_lists.code as list', 'sizes.label as size', 'order_line_sizes.quantity', 'order_line_sizes.in_cutting',
            'order_line_sizes.ready_to_ship', 'order_line_sizes.shipped', 'order_line_sizes.workshop_id', 'order_lines.workshop_id as line_workshop_id',
        ])->map(fn (object $row): array => (array) $row)->all();
}

/** @return list<array<string, mixed>> */
function linesAsTheyWereBeforeSizes(): array
{
    return DB::table('order_lines')->orderBy('id')->get(LINE_COLUMNS_BEFORE_SIZES)->map(fn (object $row): array => (array) $row)->all();
}

it('makes every line recorded before sizes "One size" with one row holding its quantity and counts, and round-trips', function () {
    $migration = orderLineSizesMigration();
    $migration->down();

    $workshopId = putLinesInTheOldShape();
    $before = linesAsTheyWereBeforeSizes();
    $migration->up();

    $expected = [
        ['client' => 'Boutique Lina', 'list' => 'one_size', 'size' => 'One size', 'quantity' => 50, 'in_cutting' => 20, 'ready_to_ship' => 10, 'shipped' => 0, 'workshop_id' => $workshopId, 'line_workshop_id' => $workshopId],
        ['client' => 'Hotel Dar Said', 'list' => 'one_size', 'size' => 'One size', 'quantity' => 12, 'in_cutting' => 0, 'ready_to_ship' => 0, 'shipped' => 12, 'workshop_id' => $workshopId, 'line_workshop_id' => $workshopId],
    ];

    // The size rows are the lines' figures; nothing else on a line changed.
    expect(sizesOfEveryLine())->toBe($expected)
        ->and(linesAsTheyWereBeforeSizes())->toBe($before);

    $migration->down();

    expect(Schema::hasTable('order_line_sizes'))->toBeFalse()
        ->and(Schema::hasColumn('order_lines', 'size_list_id'))->toBeFalse()
        ->and(Schema::hasColumn('stock_movements', 'sizes'))->toBeFalse()
        ->and(linesAsTheyWereBeforeSizes())->toBe($before);

    $migration->up();

    expect(sizesOfEveryLine())->toBe($expected)
        ->and(Schema::hasColumn('stock_movements', 'sizes'))->toBeTrue();
});
