<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The S-04a migrations: products, product_components, audit columns, PostgreSQL CHECKs and trigram indexes
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run them. Each test rolls some back and forward again with the migration objects: schema changes
| are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after each test.
| The CHECK and trigram tests run on PostgreSQL only (the parity run); on SQLite they are skipped with a reason.
*/

function auditMigration04(): Migration
{
    return require database_path('migrations/2026_10_09_300000_add_audit_columns_to_materials.php');
}

function productsMigration04(): Migration
{
    return require database_path('migrations/2026_10_09_300100_create_products_and_product_components_tables.php');
}

/** S-44-b: it sits on top of every table here (a `workshop_id` on each, composite keys on PostgreSQL), so a rollback of an older one starts with it. */
function workshopMigration04(): Migration
{
    return require database_path('migrations/2026_10_15_100100_add_workshop_to_owned_tables.php');
}

function postgresExtrasMigration04(): Migration
{
    return require database_path('migrations/2026_10_09_300200_add_postgres_checks_and_trigram_indexes.php');
}

function onPostgres04(): bool
{
    return DB::getDriverName() === 'pgsql';
}

/** A `materials` row for DB::table() inserts (the factory's raw attributes carry codes, not ids). */
function materialRow04(array $overrides = []): array
{
    return [
        ...rawWorkshop('materials'),
        'sku' => 'MAT-'.fake()->unique()->numerify('######'),
        'name' => 'Row material',
        'category_id' => DB::table('material_categories')->where('code', 'fabric')->value('id'),
        'unit_id' => DB::table('units')->where('code', 'm')->value('id'),
        'unit_cost' => '1.0000',
        'quantity_on_hand' => '10.0000',
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ];
}

/** A `products` row for DB::table() inserts. */
function productRow04(array $overrides = []): array
{
    return [
        ...rawWorkshop('products'),
        'sku' => 'PRD-'.fake()->unique()->numerify('######'),
        'name' => 'Row product',
        'category_id' => DB::table('product_categories')->where('code', 'apparel')->value('id'),
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ];
}

/** A `product_components` row for DB::table() inserts. */
function componentRow04(int $productId, int $materialId, array $overrides = []): array
{
    return [
        ...rawWorkshop('product_components'),
        'product_id' => $productId,
        'material_id' => $materialId,
        'quantity' => '1.0000',
        'waste_percent' => '0.00',
        'position' => 0,
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ];
}

/** Insert in a savepoint, so a rejected row doesn't abort the surrounding transaction (PostgreSQL). */
function insertInSavepoint04(string $table, array $row): void
{
    DB::transaction(fn () => DB::table($table)->insert($row));
}

describe('schema', function () {
    it('creates products with the agreed columns, indexes and defaults', function () {
        expect(Schema::hasColumns('products', [
            'id', 'sku', 'name', 'category_id', 'status', 'description', 'labor_cost', 'overhead_cost', 'selling_price',
            'photo_path', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]))->toBeTrue();

        foreach (['products_sku_unique', 'products_name_index', 'products_category_id_index', 'products_status_index'] as $index) {
            expect(Schema::hasIndex('products', $index))->toBeTrue("products is missing {$index}");
        }

        $id = DB::table('products')->insertGetId(productRow04());
        $row = DB::table('products')->find($id);

        expect($row->status)->toBe('active')
            ->and((float) $row->labor_cost)->toBe(0.0)
            ->and((float) $row->overhead_cost)->toBe(0.0)
            ->and($row->selling_price)->toBeNull()
            ->and($row->photo_path)->toBeNull()
            ->and($row->deleted_at)->toBeNull();
    });

    it('creates product_components with the agreed columns, indexes and defaults', function () {
        expect(Schema::hasColumns('product_components', [
            'id', 'product_id', 'material_id', 'quantity', 'waste_percent', 'notes', 'position', 'created_at', 'updated_at',
        ]))->toBeTrue()
            ->and(Schema::hasIndex('product_components', ['product_id', 'material_id'], 'unique'))->toBeTrue()
            ->and(Schema::hasIndex('product_components', ['material_id']))->toBeTrue();

        $productId = DB::table('products')->insertGetId(productRow04());
        $materialId = DB::table('materials')->insertGetId(materialRow04());
        $row = componentRow04($productId, $materialId);
        unset($row['waste_percent']);
        $id = DB::table('product_components')->insertGetId($row);

        expect((float) DB::table('product_components')->find($id)->waste_percent)->toBe(0.0);
    });

    it('adds nullable audit columns to materials', function () {
        expect(Schema::hasColumns('materials', ['created_by', 'updated_by']))->toBeTrue();

        $id = DB::table('materials')->insertGetId(materialRow04());

        expect(DB::table('materials')->find($id)->created_by)->toBeNull();
    });
});

describe('foreign keys', function () {
    it('refuses to delete a product category that a product uses', function () {
        $category = ProductCategory::factory()->create();
        DB::table('products')->insert(productRow04(['category_id' => $category->id]));

        expect(fn () => DB::transaction(fn () => $category->delete()))->toThrow(QueryException::class);
    });

    it('cascades the composition when a product is hard-deleted', function () {
        $productId = DB::table('products')->insertGetId(productRow04());
        $materialId = DB::table('materials')->insertGetId(materialRow04());
        DB::table('product_components')->insert(componentRow04($productId, $materialId));

        DB::table('products')->where('id', $productId)->delete();

        expect(DB::table('product_components')->count())->toBe(0);
    });

    it('refuses to hard-delete a material that a composition uses', function () {
        $productId = DB::table('products')->insertGetId(productRow04());
        $materialId = DB::table('materials')->insertGetId(materialRow04());
        DB::table('product_components')->insert(componentRow04($productId, $materialId));

        expect(fn () => DB::transaction(fn () => DB::table('materials')->where('id', $materialId)->delete()))
            ->toThrow(QueryException::class);
    });

    it('nulls created_by and updated_by when the user is deleted', function () {
        $user = User::factory()->create();
        $productId = DB::table('products')->insertGetId(productRow04(['created_by' => $user->id, 'updated_by' => $user->id]));

        $user->delete();

        $row = DB::table('products')->find($productId);

        expect($row->created_by)->toBeNull()
            ->and($row->updated_by)->toBeNull();
    });
});

/**
 * Asserts the SKU index of the table is the partial unique one: a live duplicate is refused, a soft-deleted row frees its SKU
 * and two soft-deleted rows may share one. Leaves the table without rows of that SKU.
 *
 * @param  callable(array<string, mixed>): array<string, mixed>  $row
 */
function expectPartialSkuIndex04(string $table, callable $row): void
{
    DB::table($table)->insert($row(['sku' => 'SAME-SKU']));

    expect(fn () => insertInSavepoint04($table, $row(['sku' => 'SAME-SKU'])))->toThrow(QueryException::class);

    // A soft-deleted row frees its SKU.
    DB::table($table)->where('sku', 'SAME-SKU')->update(['deleted_at' => now()]);
    DB::table($table)->insert($row(['sku' => 'SAME-SKU']));

    // Two soft-deleted rows may share a SKU.
    DB::table($table)->where('sku', 'SAME-SKU')->whereNull('deleted_at')->update(['deleted_at' => now()]);

    expect(DB::table($table)->where('sku', 'SAME-SKU')->count())->toBe(2);

    DB::table($table)->where('sku', 'SAME-SKU')->delete();
}

describe('partial SKU indexes', function () {
    it('keeps SKUs unique among non-deleted materials and products', function (string $table, string $row) {
        expectPartialSkuIndex04($table, $row);
    })->with([
        'materials' => ['materials', 'materialRow04'],
        'products' => ['products', 'productRow04'],
    ]);
});

describe('up, down, up', function () {
    it('rolls every S-04a migration back and forward again, keeping the data that must survive', function () {
        $material = Material::factory()->create(['sku' => 'KEEP-1', 'name' => 'Survivor']);

        // Newest first, as `migrate:rollback` does.
        workshopMigration04()->down();
        postgresExtrasMigration04()->down();
        productsMigration04()->down();
        auditMigration04()->down();

        expect(Schema::hasTable('products'))->toBeFalse()
            ->and(Schema::hasTable('product_components'))->toBeFalse()
            ->and(Schema::hasColumn('materials', 'created_by'))->toBeFalse()
            ->and(Schema::hasColumn('materials', 'updated_by'))->toBeFalse()
            ->and(DB::table('materials')->where('sku', 'KEEP-1')->value('name'))->toBe('Survivor');

        // The partial SKU index of materials is back and still partial after the rollback.
        expect(Schema::hasIndex('materials', 'materials_sku_unique'))->toBeTrue();
        expectPartialSkuIndex04('materials', materialRow04(...));

        auditMigration04()->up();
        productsMigration04()->up();
        postgresExtrasMigration04()->up();
        workshopMigration04()->up();

        expect(Schema::hasTable('products'))->toBeTrue()
            ->and(Schema::hasTable('product_components'))->toBeTrue()
            ->and(Schema::hasColumns('materials', ['created_by', 'updated_by']))->toBeTrue()
            ->and(DB::table('materials')->where('sku', 'KEEP-1')->value('name'))->toBe('Survivor');

        // Everything works again after the round trip.
        $product = Product::factory()->withComponents(2)->create();

        expect($product->components()->count())->toBe(2);
    });

    it('keeps the partial SKU index of materials through the audit columns, up and down', function () {
        // SQLite rebuilds `materials` when a column is dropped, and would lose the `WHERE` of a partial index it re-creates itself.
        // The migrations before and after this one are rolled back first, so only the audit columns change.
        workshopMigration04()->down();
        postgresExtrasMigration04()->down();
        productsMigration04()->down();

        foreach ([1, 2] as $round) {
            auditMigration04()->down();
            expectPartialSkuIndex04('materials', materialRow04(...));

            auditMigration04()->up();
            expectPartialSkuIndex04('materials', materialRow04(...));
        }

        expect(Schema::hasColumns('materials', ['created_by', 'updated_by']))->toBeTrue();
    });

    it('runs the products migration down and up more than once', function () {
        workshopMigration04()->down();
        postgresExtrasMigration04()->down();
        productsMigration04()->down();
        productsMigration04()->up();
        productsMigration04()->down();
        productsMigration04()->up();
        postgresExtrasMigration04()->up();
        workshopMigration04()->up();

        expect(Schema::hasIndex('products', 'products_sku_unique'))->toBeTrue();

        DB::table('products')->insert(productRow04(['sku' => 'ONCE']));

        expect(fn () => insertInSavepoint04('products', productRow04(['sku' => 'ONCE'])))->toThrow(QueryException::class);
    });

    it('does nothing on SQLite from the PostgreSQL-only migration', function () {
        postgresExtrasMigration04()->down();
        postgresExtrasMigration04()->up();
        postgresExtrasMigration04()->down();
        postgresExtrasMigration04()->up();

        expect(Schema::hasTable('products'))->toBeTrue();
    });
});

describe('PostgreSQL CHECK constraints', function () {
    it('rejects bad rows', function (string $table, Closure $row, ?string $constraint) {
        $build = $row();

        expect(fn () => insertInSavepoint04($table, $build))->toThrow(QueryException::class, $constraint);
    })->with([
        'material negative unit cost' => ['materials', fn () => materialRow04(['unit_cost' => '-0.0001']), 'materials_unit_cost_check'],
        'material negative stock' => ['materials', fn () => materialRow04(['quantity_on_hand' => '-1']), 'materials_quantity_on_hand_check'],
        'material negative reorder level' => ['materials', fn () => materialRow04(['reorder_level' => '-1']), 'materials_reorder_level_check'],
        'product negative labor' => ['products', fn () => productRow04(['labor_cost' => '-1']), 'products_labor_cost_check'],
        'product negative overhead' => ['products', fn () => productRow04(['overhead_cost' => '-1']), 'products_overhead_cost_check'],
        'product negative selling price' => ['products', fn () => productRow04(['selling_price' => '-0.0001']), 'products_selling_price_check'],
    ])->skip(fn () => ! onPostgres04(), 'CHECK constraints exist on PostgreSQL only; SQLite relies on validation.');

    it('rejects bad composition lines', function (array $override, string $constraint) {
        $productId = DB::table('products')->insertGetId(productRow04());
        $materialId = DB::table('materials')->insertGetId(materialRow04());

        expect(fn () => insertInSavepoint04('product_components', componentRow04($productId, $materialId, $override)))
            ->toThrow(QueryException::class, $constraint);
    })->with([
        'zero quantity' => [['quantity' => '0'], 'product_components_quantity_check'],
        'negative quantity' => [['quantity' => '-1'], 'product_components_quantity_check'],
        'negative waste' => [['waste_percent' => '-0.01'], 'product_components_waste_percent_check'],
        'waste of 100' => [['waste_percent' => '100'], 'product_components_waste_percent_check'],
    ])->skip(fn () => ! onPostgres04(), 'CHECK constraints exist on PostgreSQL only; SQLite relies on validation.');

    it('accepts the boundary values', function () {
        $productId = DB::table('products')->insertGetId(productRow04(['labor_cost' => '0', 'overhead_cost' => '0', 'selling_price' => '0']));
        $materialId = DB::table('materials')->insertGetId(materialRow04(['unit_cost' => '0', 'quantity_on_hand' => '0', 'reorder_level' => '0']));
        DB::table('materials')->insert(materialRow04(['reorder_level' => null]));
        DB::table('product_components')->insert(componentRow04($productId, $materialId, ['quantity' => '0.0001', 'waste_percent' => '99.99']));
        DB::table('products')->insert(productRow04(['selling_price' => null]));

        expect(DB::table('product_components')->count())->toBe(1);
    })->skip(fn () => ! onPostgres04(), 'CHECK constraints exist on PostgreSQL only.');

    it('is removed by down and added again by up', function () {
        postgresExtrasMigration04()->down();

        // Without the constraints a negative cost is stored.
        DB::table('materials')->insert(materialRow04(['sku' => 'NEG-1', 'unit_cost' => '-1']));

        expect(fn () => postgresExtrasMigration04()->up())
            ->toThrow(RuntimeException::class, 'Cannot add the CHECK constraints to materials');

        DB::table('materials')->where('sku', 'NEG-1')->delete();
        postgresExtrasMigration04()->up();

        expect(fn () => insertInSavepoint04('materials', materialRow04(['unit_cost' => '-1'])))->toThrow(QueryException::class);
    })->skip(fn () => ! onPostgres04(), 'CHECK constraints exist on PostgreSQL only.');

    it('fails with the ids and values of the rows that violate a materials CHECK, soft-deleted ones included', function () {
        postgresExtrasMigration04()->down();

        $good = DB::table('materials')->insertGetId(materialRow04(['sku' => 'GOOD-1']));
        $bad = DB::table('materials')->insertGetId(materialRow04(['sku' => 'BAD-1', 'quantity_on_hand' => '-5', 'deleted_at' => now()]));
        $worse = DB::table('materials')->insertGetId(materialRow04(['sku' => 'BAD-2', 'reorder_level' => '-2']));

        try {
            postgresExtrasMigration04()->up();
            $this->fail('The migration must refuse negative rows.');
        } catch (RuntimeException $exception) {
            expect($exception->getMessage())
                ->toContain("#{$bad} BAD-1")
                ->toContain('quantity_on_hand -5.0000')
                ->toContain("#{$worse} BAD-2")
                ->toContain('reorder_level -2.0000')
                ->toContain('Correct them, then migrate again.')
                ->not->toContain("#{$good} GOOD-1");
        }

        // Nothing was added: the constraint check runs before the first ALTER.
        expect(DB::table('pg_constraint')->where('conname', 'materials_unit_cost_check')->count())->toBe(0)
            ->and(DB::table('pg_indexes')->where('indexname', 'materials_name_trgm_idx')->count())->toBe(0);
    })->skip(fn () => ! onPostgres04(), 'CHECK constraints exist on PostgreSQL only.');
});

describe('PostgreSQL trigram indexes', function () {
    it('creates the pg_trgm extension and a GIN trigram index on the name and the SKU of materials and products', function (string $index, string $table, string $column) {
        expect(DB::table('pg_extension')->where('extname', 'pg_trgm')->exists())->toBeTrue();

        $definition = DB::table('pg_indexes')->where('indexname', $index)->where('tablename', $table)->value('indexdef');

        expect($definition)->toContain('USING gin')
            ->and($definition)->toContain("{$column} gin_trgm_ops");
    })->with([
        ['materials_name_trgm_idx', 'materials', 'name'],
        ['materials_sku_trgm_idx', 'materials', 'sku'],
        ['products_name_trgm_idx', 'products', 'name'],
        ['products_sku_trgm_idx', 'products', 'sku'],
    ])->skip(fn () => ! onPostgres04(), 'Trigram indexes exist on PostgreSQL only; SQLite falls back to a LIKE scan.');

    it('drops the indexes on down and keeps the extension', function () {
        postgresExtrasMigration04()->down();

        expect(DB::table('pg_indexes')->where('indexname', 'like', '%_trgm_idx')->count())->toBe(0)
            ->and(DB::table('pg_extension')->where('extname', 'pg_trgm')->exists())->toBeTrue();

        postgresExtrasMigration04()->up();

        expect(DB::table('pg_indexes')->where('indexname', 'like', '%_trgm_idx')->count())->toBe(4);
    })->skip(fn () => ! onPostgres04(), 'Trigram indexes exist on PostgreSQL only.');

    it('still answers the case-insensitive %term% search of the materials list', function () {
        Material::factory()->create(['sku' => 'TRG-1', 'name' => 'Denim 12oz Indigo']);
        Material::factory()->create(['sku' => 'TRG-2', 'name' => 'Cotton poplin white']);

        expect(Material::query()->search('INDIGO')->pluck('sku')->all())->toBe(['TRG-1'])
            ->and(Material::query()->search('trg-')->count())->toBe(2);
    });
});
