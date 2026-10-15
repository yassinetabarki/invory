<?php

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
| The orders and order_lines tables (S-48-a, AC-7 schema side)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The rollback test runs it backwards and forwards again with the migration object:
| schema changes are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after the test.
| The composite keys and the CHECK only exist on PostgreSQL (the parity run).
*/

it('creates both tables with their columns, indexes and foreign keys, and drops both on rollback', function () {
    $foreignKeys = fn (string $table, string $parent) => collect(Schema::getForeignKeys($table))->firstWhere('foreign_table', $parent);

    expect(Schema::hasColumns('orders', ['id', 'workshop_id', 'client', 'ordered_on', 'note', 'user_id', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue()
        ->and(Schema::hasColumns('order_lines', ['id', 'workshop_id', 'order_id', 'product_id', 'quantity', 'position', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasIndex('orders', ['workshop_id', 'ordered_on', 'id']))->toBeTrue()
        ->and(Schema::hasIndex('order_lines', ['order_id', 'product_id'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('order_lines', ['product_id']))->toBeTrue()
        ->and($foreignKeys('orders', 'workshops')['on_delete'])->toBe('restrict')
        ->and($foreignKeys('orders', 'users')['on_delete'])->toBe('set null')
        ->and($foreignKeys('order_lines', 'orders')['on_delete'])->toBe('cascade')
        ->and($foreignKeys('order_lines', 'products')['on_delete'])->toBe('restrict');

    ordersMigration()->down();

    expect(Schema::hasTable('order_lines'))->toBeFalse()
        ->and(Schema::hasTable('orders'))->toBeFalse();

    ordersMigration()->up();

    $line = OrderLine::factory()->create();

    expect(Schema::hasTable('orders'))->toBeTrue()
        ->and(Schema::hasTable('order_lines'))->toBeTrue()
        ->and(Order::query()->whereKey($line->order_id)->exists())->toBeTrue();
});

it('makes PostgreSQL refuse an order line that breaks a workshop, quantity or duplicate rule', function (Closure $override, string $constraint) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite foreign keys and the CHECK are PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $make = fn (Workshop $workshop, string $model) => CurrentWorkshop::run($workshop, fn () => $model::factory()->create());
    $ids = [
        'order' => $make($mine, Order::class)->id,
        'product' => $make($mine, Product::class)->id,
        'spare product' => $make($mine, Product::class)->id,
        'foreign order' => $make($other, Order::class)->id,
        'foreign product' => $make($other, Product::class)->id,
        'mine' => $mine->id,
        'other' => $other->id,
    ];
    $row = ['workshop_id' => $ids['mine'], 'order_id' => $ids['order'], 'product_id' => $ids['product'], 'quantity' => 1, 'position' => 0, 'created_at' => now(), 'updated_at' => now()];

    // The order already holds the product once, so a second line of it is refused.
    DB::table('order_lines')->insert($row);

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names the constraint that fired.
    expect(fn () => DB::transaction(fn () => DB::table('order_lines')->insert([...$row, ...$override($ids)])))
        ->toThrow(QueryException::class, $constraint);
})->with([
    'a workshop that is not its order\'s' => [fn (array $ids) => ['workshop_id' => $ids['other'], 'product_id' => $ids['foreign product']], 'order_lines_workshop_order_foreign'],
    'a workshop that is not its product\'s' => [fn (array $ids) => ['workshop_id' => $ids['other'], 'order_id' => $ids['foreign order']], 'order_lines_workshop_product_foreign'],
    'a quantity of 0' => [fn (array $ids) => ['product_id' => $ids['spare product'], 'quantity' => 0], 'order_lines_quantity_check'],
    'the same product twice on one order' => [fn (array $ids) => [], 'order_lines_order_id_product_id_unique'],
]);
