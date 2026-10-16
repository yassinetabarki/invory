<?php

use App\Models\Material;
use App\Models\OrderLine;
use App\Models\OrderLineCut;
use App\Models\OrderLineCutMaterial;
use App\Models\StockMovement;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The cut tables and the ledger references (S-53-a)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration; the rollback test runs it backwards and forwards again with the migration object
| (schema changes are transactional, so RefreshDatabase undoes them). The composite keys and CHECKs only exist on PostgreSQL.
*/

it('creates both tables and the three ledger columns, and removes them on rollback without losing a movement', function () {
    $foreignKey = fn (string $table, string $parent) => collect(Schema::getForeignKeys($table))->firstWhere('foreign_table', $parent);

    expect(Schema::hasColumns('order_line_cuts', ['id', 'workshop_id', 'order_line_id', 'units', 'units_returned', 'user_id', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('order_line_cut_materials', ['id', 'workshop_id', 'order_line_cut_id', 'material_id', 'unit_id', 'quantity_per_unit', 'quantity_taken', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('stock_movements', ['order_id', 'product_id', 'units']))->toBeTrue()
        ->and(Schema::hasIndex('order_line_cuts', ['order_line_id', 'id']))->toBeTrue()
        ->and(Schema::hasIndex('order_line_cut_materials', ['order_line_cut_id', 'material_id'], 'unique'))->toBeTrue()
        ->and($foreignKey('order_line_cuts', 'workshops')['on_delete'])->toBe('restrict')
        ->and($foreignKey('order_line_cuts', 'order_lines')['on_delete'])->toBe('cascade')
        ->and($foreignKey('order_line_cuts', 'users')['on_delete'])->toBe('set null')
        ->and($foreignKey('order_line_cut_materials', 'order_line_cuts')['on_delete'])->toBe('cascade')
        ->and($foreignKey('order_line_cut_materials', 'materials')['on_delete'])->toBe('restrict')
        ->and($foreignKey('order_line_cut_materials', 'units')['on_delete'])->toBe('set null')
        ->and($foreignKey('stock_movements', 'orders')['on_delete'])->toBe('restrict')
        ->and($foreignKey('stock_movements', 'products')['on_delete'])->toBe('restrict');

    // A movement that exists before the rollback (SQLite rebuilds `stock_movements` for its new keys).
    $movement = StockMovement::factory()->restock('5.0000', '5.0000')->create();

    cutsMigration()->down();

    expect(Schema::hasTable('order_line_cuts'))->toBeFalse()
        ->and(Schema::hasTable('order_line_cut_materials'))->toBeFalse()
        ->and(Schema::hasColumn('stock_movements', 'order_id'))->toBeFalse()
        ->and(Schema::hasColumn('stock_movements', 'product_id'))->toBeFalse()
        ->and(Schema::hasColumn('stock_movements', 'units'))->toBeFalse()
        ->and(StockMovement::query()->find($movement->id)->quantity)->toBe('5.0000');

    cutsMigration()->up();

    $cut = OrderLineCut::factory()->create();
    $cutMaterial = OrderLineCutMaterial::factory()->create(['order_line_cut_id' => $cut->id]);

    $survivor = StockMovement::query()->findOrFail($movement->id);
    $cutMaterial->refresh();

    expect($survivor->quantity)->toBe('5.0000')
        ->and($survivor->units)->toBeNull()
        ->and($cut->fresh()->units_returned)->toBe(0)
        ->and([$cutMaterial->quantity_per_unit, $cutMaterial->quantity_taken])->toBe(['1.98000000', '59.4000']);
});

it('makes PostgreSQL refuse a cut that breaks a workshop, units or amount rule', function (Closure $row, string $table, string $constraint) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite foreign keys and the CHECKs are PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $make = fn (Workshop $workshop, string $model) => CurrentWorkshop::run($workshop, fn () => $model::factory()->create());
    $line = $make($mine, OrderLine::class);
    $cut = CurrentWorkshop::run($mine, fn () => OrderLineCut::factory()->create(['order_line_id' => $line->id, 'units' => 10]));
    $ids = [
        'mine' => $mine->id,
        'other' => $other->id,
        'line' => $line->id,
        'cut' => $cut->id,
        'material' => $make($mine, Material::class)->id,
        'foreign material' => $make($other, Material::class)->id,
    ];

    // A savepoint, so the rejected row doesn't abort the surrounding transaction; the message names the constraint that fired.
    expect(fn () => DB::transaction(fn () => DB::table($table)->insert([...$row($ids), 'created_at' => now(), 'updated_at' => now()])))
        ->toThrow(QueryException::class, $constraint);
})->with([
    'a cut of another workshop than its line' => [
        fn (array $ids) => ['workshop_id' => $ids['other'], 'order_line_id' => $ids['line'], 'units' => 1],
        'order_line_cuts', 'order_line_cuts_workshop_line_foreign',
    ],
    'units returned above the units' => [
        fn (array $ids) => ['workshop_id' => $ids['mine'], 'order_line_id' => $ids['line'], 'units' => 5, 'units_returned' => 6],
        'order_line_cuts', 'order_line_cuts_units_returned_check',
    ],
    'a cut of 0 units' => [
        fn (array $ids) => ['workshop_id' => $ids['mine'], 'order_line_id' => $ids['line'], 'units' => 0],
        'order_line_cuts', 'order_line_cuts_units_check',
    ],
    'a cut material of another workshop than its cut' => [
        fn (array $ids) => ['workshop_id' => $ids['other'], 'order_line_cut_id' => $ids['cut'], 'material_id' => $ids['foreign material'], 'quantity_per_unit' => '1.00000000', 'quantity_taken' => '10.0000'],
        'order_line_cut_materials', 'order_line_cut_materials_workshop_cut_foreign',
    ],
    'a cut material of another workshop than its material' => [
        fn (array $ids) => ['workshop_id' => $ids['mine'], 'order_line_cut_id' => $ids['cut'], 'material_id' => $ids['foreign material'], 'quantity_per_unit' => '1.00000000', 'quantity_taken' => '10.0000'],
        'order_line_cut_materials', 'order_line_cut_materials_workshop_material_foreign',
    ],
    'a per-unit amount of 0' => [
        fn (array $ids) => ['workshop_id' => $ids['mine'], 'order_line_cut_id' => $ids['cut'], 'material_id' => $ids['material'], 'quantity_per_unit' => '0.00000000', 'quantity_taken' => '0.0000'],
        'order_line_cut_materials', 'order_line_cut_materials_per_unit_check',
    ],
]);
