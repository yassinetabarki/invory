<?php

use App\Models\Material;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The reference-data migration: backfill, loud failure, round trip, SKU index (ADR-0008)
|--------------------------------------------------------------------------
|
| Each test rolls the migration back (so `materials` has its `category` and `unit` strings again), puts rows in the old shape,
| runs it forward and checks the result. Schema changes are transactional on SQLite and PostgreSQL, so RefreshDatabase's own
| transaction undoes them after each test. The parity run on PostgreSQL proves the same on the real engine.
*/

const OLD_CATEGORIES = ['fabric', 'lining', 'interlining', 'leather', 'thread', 'trim', 'fastener', 'label', 'sole', 'hardware', 'packaging', 'other'];
const OLD_UNITS = ['m', 'cm', 'yd', 'sqm', 'sqft', 'kg', 'g', 'l', 'ml', 'pc', 'pair', 'roll', 'cone'];

/**
 * The migration object of S-21.
 *
 * The S-04a migrations depend on its tables (`products` points at `product_categories`, and materials carry audit columns on top of
 * its shape), so a rollback of S-21 has to start with them, newest first, exactly as `migrate:rollback` does. PostgreSQL refuses to
 * drop a table that a foreign key still points at; SQLite doesn't check. Nothing here needs them again afterwards.
 */
function referenceMigration(): Migration
{
    if (Schema::hasTable('products')) {
        foreach ([
            '2026_10_09_300200_add_postgres_checks_and_trigram_indexes',
            '2026_10_09_300100_create_products_and_product_components_tables',
            '2026_10_09_300000_add_audit_columns_to_materials',
        ] as $newer) {
            (require database_path("migrations/{$newer}.php"))->down();
        }
    }

    return require database_path('migrations/2026_10_09_100000_create_reference_tables_and_link_materials.php');
}

/** A `materials` row in the shape before the migration (strings, not ids). */
function oldMaterialRow(string $sku, string $category, string $unit, ?string $deletedAt = null): array
{
    return [
        'sku' => $sku,
        'name' => "Material {$sku}",
        'category' => $category,
        'unit' => $unit,
        'unit_cost' => '1.0000',
        'quantity_on_hand' => '2.0000',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'deleted_at' => $deletedAt,
    ];
}

it('moves every default category and unit code to the matching row', function () {
    $migration = referenceMigration();
    $migration->down();

    // 13 rows: every unit code once, every category code at least once (12 categories, so the first one twice).
    foreach (OLD_UNITS as $index => $unit) {
        DB::table('materials')->insert(oldMaterialRow("SKU-{$index}", OLD_CATEGORIES[$index % 12], $unit));
    }

    $migration->up();

    $materials = DB::table('materials')->orderBy('id')->get();

    expect(Schema::hasColumns('materials', ['category', 'unit']))->toBeFalse()
        ->and($materials)->toHaveCount(13);

    foreach ($materials as $index => $material) {
        expect(DB::table('units')->where('id', $material->unit_id)->value('code'))->toBe(OLD_UNITS[$index])
            ->and(DB::table('material_categories')->where('id', $material->category_id)->value('code'))->toBe(OLD_CATEGORIES[$index % 12]);
    }

    // Every code was exercised.
    expect(DB::table('materials')->distinct()->count('unit_id'))->toBe(13)
        ->and(DB::table('materials')->distinct()->count('category_id'))->toBe(12);
});

it('keeps soft-deleted materials and their other columns', function () {
    $migration = referenceMigration();
    $migration->down();

    DB::table('materials')->insert(oldMaterialRow('KEPT', 'leather', 'sqft'));
    DB::table('materials')->insert(oldMaterialRow('GONE', 'sole', 'pair', now()->toDateTimeString()));

    $migration->up();

    $kept = Material::query()->where('sku', 'KEPT')->firstOrFail();
    $gone = Material::withTrashed()->where('sku', 'GONE')->firstOrFail();

    expect($kept->category->code)->toBe('leather')
        ->and($kept->unit->code)->toBe('sqft')
        ->and($kept->name)->toBe('Material KEPT')
        ->and($kept->unit_cost)->toBe('1.0000')
        ->and($gone->trashed())->toBeTrue()
        ->and($gone->category->code)->toBe('sole')
        ->and($gone->unit->code)->toBe('pair');
});

it('fails loudly, naming the row, when a material has a code with no matching row', function (string $category, string $unit) {
    $migration = referenceMigration();
    $migration->down();

    DB::table('materials')->insert(oldMaterialRow('GOOD', 'fabric', 'm'));
    DB::table('materials')->insert(oldMaterialRow('ODD', $category, $unit));

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, "category '{$category}', unit '{$unit}'");
})->with([
    'unknown category' => ['elastic', 'm'],
    'unknown unit' => ['fabric', 'inch'],
    'both unknown' => ['zipper', 'dozen'],
]);

it('round-trips: down restores the codes, up again gives the same ids', function () {
    $migration = referenceMigration();
    $migration->down();

    foreach (OLD_UNITS as $index => $unit) {
        DB::table('materials')->insert(oldMaterialRow("SKU-{$index}", OLD_CATEGORIES[$index % 12], $unit));
    }

    $migration->up();
    $before = DB::table('materials')->orderBy('id')->get(['sku', 'category_id', 'unit_id'])->map(fn ($row) => (array) $row)->all();

    // Down: the string columns are back, filled from the codes, and the tables are gone.
    $migration->down();

    expect(Schema::hasTable('units'))->toBeFalse()
        ->and(Schema::hasTable('material_categories'))->toBeFalse()
        ->and(Schema::hasTable('product_categories'))->toBeFalse()
        ->and(Schema::hasColumns('materials', ['category', 'unit']))->toBeTrue()
        ->and(Schema::hasColumns('materials', ['category_id', 'unit_id']))->toBeFalse()
        ->and(DB::table('materials')->orderBy('id')->pluck('unit')->all())->toBe(OLD_UNITS)
        ->and(DB::table('materials')->orderBy('id')->pluck('category')->all())->toBe(array_map(fn (int $i): string => OLD_CATEGORIES[$i % 12], array_keys(OLD_UNITS)));

    $migration->up();

    expect(DB::table('materials')->orderBy('id')->get(['sku', 'category_id', 'unit_id'])->map(fn ($row) => (array) $row)->all())->toBe($before);
});

it('restores a custom unit and a custom category as their codes on the way down', function () {
    DB::table('units')->insert(['code' => 'inch', 'name' => 'Inch', 'dimension' => 'length', 'factor_to_base' => '0.0254', 'is_system' => false, 'position' => 20, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('material_categories')->insert(['code' => 'elastic', 'name' => 'Elastic', 'position' => 20, 'created_at' => now(), 'updated_at' => now()]);
    Material::factory()->create(['sku' => 'ELA-1', 'category' => 'elastic', 'unit' => 'inch']);

    referenceMigration()->down();

    $row = DB::table('materials')->where('sku', 'ELA-1')->first();

    expect($row->category)->toBe('elastic')
        ->and($row->unit)->toBe('inch');
});

it('is idempotent: inserting the defaults again changes nothing, not even an edited name', function () {
    DB::table('units')->where('code', 'm')->update(['name' => 'Meter']);
    DB::table('material_categories')->where('code', 'fabric')->update(['name' => 'Textile']);
    DB::table('product_categories')->where('code', 'bag')->update(['name' => 'Bags']);

    $migration = referenceMigration();
    (new ReflectionMethod($migration, 'insertDefaults'))->invoke($migration);

    expect(DB::table('units')->count())->toBe(13)
        ->and(DB::table('material_categories')->count())->toBe(12)
        ->and(DB::table('product_categories')->count())->toBe(6)
        ->and(DB::table('units')->where('code', 'm')->value('name'))->toBe('Meter')
        ->and(DB::table('material_categories')->where('code', 'fabric')->value('name'))->toBe('Textile')
        ->and(DB::table('product_categories')->where('code', 'bag')->value('name'))->toBe('Bags');
});

it('enforces the foreign keys and the not-null columns on materials', function () {
    $valid = [
        'sku' => 'FK-1', 'name' => 'Row', 'unit_cost' => '1.0000', 'quantity_on_hand' => '1.0000',
        'category_id' => DB::table('material_categories')->value('id'),
        'unit_id' => DB::table('units')->value('id'),
    ];

    // Failed statements abort the surrounding PostgreSQL transaction, so each one runs in its own savepoint.
    expect(fn () => DB::transaction(fn () => DB::table('materials')->insert([...$valid, 'category_id' => 99999])))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => DB::table('materials')->insert([...$valid, 'unit_id' => 99999])))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => DB::table('materials')->insert([...$valid, 'category_id' => null])))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => DB::table('materials')->insert([...$valid, 'unit_id' => null])))->toThrow(QueryException::class);
});

it('restricts the deletion of a unit or a category that a material uses', function () {
    $material = Material::factory()->create();

    expect(fn () => DB::transaction(fn () => DB::table('units')->where('id', $material->unit_id)->delete()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => DB::table('material_categories')->where('id', $material->category_id)->delete()))->toThrow(QueryException::class);
});

describe('the partial SKU index survives the table rebuild', function () {
    $assertSkuIndexIsPartial = function (): void {
        $row = fn (string $sku, ?string $deletedAt = null): array => [
            'sku' => $sku, 'name' => 'Raw row', 'unit_cost' => '1.0000', 'quantity_on_hand' => '1.0000', 'deleted_at' => $deletedAt,
            'category_id' => DB::table('material_categories')->value('id'),
            'unit_id' => DB::table('units')->value('id'),
        ];

        DB::table('materials')->insert($row('SKU-1'));

        // An active duplicate is rejected...
        expect(fn () => DB::transaction(fn () => DB::table('materials')->insert($row('SKU-1'))))->toThrow(QueryException::class);

        // ...but a trashed row never blocks an active one, and trashed rows may share a SKU (what `WHERE deleted_at IS NULL` is for;
        // a plain unique index would reject these).
        DB::table('materials')->insert($row('SKU-2', now()->toDateTimeString()));
        DB::table('materials')->insert($row('SKU-2'));
        DB::table('materials')->insert($row('SKU-2', now()->toDateTimeString()));

        expect(DB::table('materials')->where('sku', 'SKU-2')->count())->toBe(3);
    };

    it('after up', function () use ($assertSkuIndexIsPartial) {
        $assertSkuIndexIsPartial();
    });

    it('after down and up again', function () use ($assertSkuIndexIsPartial) {
        $migration = referenceMigration();
        $migration->down();
        $migration->up();

        $assertSkuIndexIsPartial();
    });

    it('after down', function () {
        referenceMigration()->down();

        $row = fn (string $sku, ?string $deletedAt = null): array => oldMaterialRow($sku, 'fabric', 'm', $deletedAt);

        DB::table('materials')->insert($row('SKU-1'));
        expect(fn () => DB::transaction(fn () => DB::table('materials')->insert($row('SKU-1'))))->toThrow(QueryException::class);

        DB::table('materials')->insert($row('SKU-2', now()->toDateTimeString()));
        DB::table('materials')->insert($row('SKU-2'));

        expect(DB::table('materials')->where('sku', 'SKU-2')->count())->toBe(2);
    });
});

it('indexes the category and the unit of materials, and no longer the old category string', function () {
    $indexes = collect(Schema::getIndexes('materials'))->pluck('columns')->map(fn (array $columns): string => implode(',', $columns))->all();

    expect($indexes)->toContain('category_id')
        ->and($indexes)->toContain('unit_id')
        ->and($indexes)->toContain('name')
        ->and($indexes)->not->toContain('category');
});
