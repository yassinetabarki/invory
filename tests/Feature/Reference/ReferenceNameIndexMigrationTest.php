<?php

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The migration that guards case-insensitive active names (F-32, ADR-0008)
|--------------------------------------------------------------------------
|
| Each test rolls the migration back (RefreshDatabase has run it), puts rows in the shape under test and runs it forward.
| Schema changes are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after each test.
| ASCII names only: PostgreSQL's `lower()` also folds non-ASCII letters and SQLite's does not.
*/

/** The migration object of S-22b. */
function nameIndexMigration(): Migration
{
    return require database_path('migrations/2026_10_09_200000_add_active_name_unique_indexes_to_reference_tables.php');
}

/**
 * @return array<string, array{string, class-string<Model>}>
 */
dataset('indexedTables', [
    'units' => ['units', Unit::class],
    'material categories' => ['material_categories', MaterialCategory::class],
    'product categories' => ['product_categories', ProductCategory::class],
]);

it('creates one partial index per table, and down drops them', function () {
    $indexes = ['units_active_name_unique', 'material_categories_active_name_unique', 'product_categories_active_name_unique'];
    $tables = ['units', 'material_categories', 'product_categories'];

    foreach ($tables as $position => $table) {
        expect(Schema::hasIndex($table, $indexes[$position]))->toBeTrue();
    }

    nameIndexMigration()->down();

    foreach ($tables as $position => $table) {
        expect(Schema::hasIndex($table, $indexes[$position]))->toBeFalse();
    }
});

it('round-trips: after down an active duplicate is accepted, after up it is rejected again', function (string $table, string $model) {
    $migration = nameIndexMigration();
    $migration->down();

    DB::table($table)->insert($model::factory()->raw(['name' => 'Elastic']));
    DB::table($table)->insert($model::factory()->raw(['name' => 'elastic']));
    DB::table($table)->where('name', 'elastic')->delete();

    $migration->up();

    expect(fn () => DB::transaction(fn () => DB::table($table)->insert($model::factory()->raw(['name' => 'ELASTIC']))))->toThrow(QueryException::class);

    // A second round: down and up again.
    $migration->down();
    $migration->up();

    expect(Schema::hasIndex($table, "{$table}_active_name_unique"))->toBeTrue()
        ->and(fn () => DB::transaction(fn () => DB::table($table)->insert($model::factory()->raw(['name' => 'ELASTIC']))))->toThrow(QueryException::class);
})->with('indexedTables');

it('migrates a clean dataset, archived namesakes included', function (string $table, string $model) {
    $migration = nameIndexMigration();
    $migration->down();

    DB::table($table)->insert($model::factory()->raw(['name' => 'Elastic']));
    DB::table($table)->insert($model::factory()->archived()->raw(['name' => 'ELASTIC']));
    DB::table($table)->insert($model::factory()->archived()->raw(['name' => 'elastic']));

    $migration->up();

    expect(Schema::hasIndex($table, "{$table}_active_name_unique"))->toBeTrue();
})->with('indexedTables');

it('refuses to run with active rows that differ only in case, naming the table and the names', function (string $table, string $model) {
    $migration = nameIndexMigration();
    $migration->down();

    DB::table($table)->insert($model::factory()->raw(['name' => 'Elastic']));
    DB::table($table)->insert($model::factory()->raw(['name' => 'ELASTIC']));
    DB::table($table)->insert($model::factory()->raw(['name' => 'Plastic']));

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, "{$table}: \"Elastic\", \"ELASTIC\"");

    try {
        $migration->up();
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())
            ->toContain('Rename or archive one of them, then migrate again.')
            ->not->toContain('Plastic');
    }

    // Nothing was created (every table is checked before the first index).
    expect(Schema::hasIndex($table, "{$table}_active_name_unique"))->toBeFalse();
})->with('indexedTables');

it('lists the clashes of every table in one failure and creates no index', function () {
    $migration = nameIndexMigration();
    $migration->down();

    DB::table('units')->insert(Unit::factory()->raw(['name' => 'Inch']));
    DB::table('units')->insert(Unit::factory()->raw(['name' => 'INCH']));
    DB::table('product_categories')->insert(ProductCategory::factory()->raw(['name' => 'Hat']));
    DB::table('product_categories')->insert(ProductCategory::factory()->raw(['name' => 'hat']));

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'units: "Inch", "INCH"; product_categories: "Hat", "hat"');

    expect(Schema::hasIndex('units', 'units_active_name_unique'))->toBeFalse()
        ->and(Schema::hasIndex('material_categories', 'material_categories_active_name_unique'))->toBeFalse()
        ->and(Schema::hasIndex('product_categories', 'product_categories_active_name_unique'))->toBeFalse();
});

it('does not count an archived row as a clash with an active one', function () {
    $migration = nameIndexMigration();
    $migration->down();

    // Seeded "Metre" is active: an archived "metre" next to it is fine, a second active one is not.
    DB::table('units')->insert(Unit::factory()->archived()->raw(['name' => 'metre']));
    $migration->up();

    $migration->down();
    DB::table('units')->insert(Unit::factory()->raw(['name' => 'metre']));

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'units: "Metre", "metre"');
});
