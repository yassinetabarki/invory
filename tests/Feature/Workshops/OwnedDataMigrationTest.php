<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Costing\ProductCostingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| S-44-b: every record belongs to a workshop, and today's data moves into one (AC-7)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The tests roll it back and forward again with the migration object: schema changes
| are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after the test.
|
| The round trip WITH data runs on PostgreSQL only. SQLite rebuilds a table to add a foreign key or a NOT NULL, and dropping the old
| table fires the foreign keys that point at it, so rows in a referenced table are not safe there (F-59). Development and production
| run PostgreSQL; SQLite only runs the suite.
*/

const OWNED_TABLES = ['materials', 'products', 'product_components', 'stock_movements', 'sales'];

function ownedDataMigration(): Migration
{
    return require database_path('migrations/2026_10_15_100100_add_workshop_to_owned_tables.php');
}

/** @return array<string, int> rows per owned table, soft-deleted ones included */
function ownedRowCounts(): array
{
    return collect(OWNED_TABLES)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
}

/** The SQL text of an index (SQLite keeps what was typed, PostgreSQL its normalised form). */
function indexDefinition(string $index): string
{
    return (string) (DB::getDriverName() === 'pgsql'
        ? DB::table('pg_indexes')->where('indexname', $index)->value('indexdef')
        : DB::table('sqlite_master')->where('type', 'index')->where('name', $index)->value('sql'));
}

it('moves today\'s data into the one workshop of its accounts, and back, losing nothing', function () {
    $this->travelTo(DEMO_NOW);
    $this->seed(DatabaseSeeder::class);

    // Today's data: the demo catalogue, products, sales and stock history, and soft-deleted rows (a material, a product and its line).
    Material::factory()->create(['sku' => 'GONE-MAT'])->delete();
    Product::factory()->withComponents(1)->create(['sku' => 'GONE-PRD'])->delete();

    $workshopId = (int) User::query()->where('email', 'test@example.com')->value('workshop_id');
    $counts = ownedRowCounts();

    expect($counts['materials'])->toBe(14)
        ->and($counts['products'])->toBe(8)
        ->and($counts['sales'])->toBe(60)
        ->and($counts['product_components'])->toBeGreaterThan(0);

    foreach ([1, 2] as $round) {
        ownedDataMigration()->down();

        expect(Schema::hasColumn('materials', 'workshop_id'))->toBeFalse()
            ->and(Schema::hasColumn('sales', 'workshop_id'))->toBeFalse()
            ->and(ownedRowCounts())->toBe($counts);

        ownedDataMigration()->up();

        // One workshop, the accounts' one, holds every row of every table: soft-deleted ones too, none moved or lost.
        expect(Workshop::query()->count())->toBe(1)
            ->and(Workshop::query()->value('id'))->toBe($workshopId)
            ->and(ownedRowCounts())->toBe($counts);

        foreach (OWNED_TABLES as $table) {
            expect(DB::table($table)->where('workshop_id', $workshopId)->count())->toBe($counts[$table], "{$table} (round {$round})");
        }
    }

    $jacket = Product::query()->with('components.material')->where('sku', 'PRD-JKT-001')->firstOrFail();
    $costing = app(ProductCostingService::class)->cost($jacket);

    expect($costing->result->cost->unitCost)->toBe('35.7540')
        ->and($costing->result->capacity->maxProducible)->toBe(112)
        ->and($costing->result->capacity->bottleneckMaterialName)->toBe('Metal shank button 17mm');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'Rows in a rebuilt SQLite table fire the foreign keys that point at it (F-59): the round trip with data runs on PostgreSQL only.');

it('gives every owned table a NOT NULL workshop and per-workshop indexes, and creates no workshop for an empty database', function () {
    // An empty database: nothing to move, so no workshop (also after a rollback and a new migration).
    ownedDataMigration()->down();
    ownedDataMigration()->up();

    expect(Workshop::query()->count())->toBe(0);

    foreach (OWNED_TABLES as $table) {
        $column = collect(Schema::getColumns($table))->firstWhere('name', 'workshop_id');
        $keys = collect(Schema::getForeignKeys($table))->where('foreign_table', 'workshops');

        expect($column['nullable'])->toBeFalse("{$table}.workshop_id is nullable")
            ->and($keys->pluck('columns')->all())->toBe([['workshop_id']], "{$table} has no foreign key to workshops")
            ->and($keys->first()['on_delete'])->toBe('restrict');
    }

    $indexes = fn (string $table) => collect(Schema::getIndexes($table))->keyBy('name');

    expect($indexes('materials')['materials_sku_unique']['columns'])->toBe(['workshop_id', 'sku'])
        ->and($indexes('materials')['materials_sku_unique']['unique'])->toBeTrue()
        ->and($indexes('products')['products_sku_unique']['columns'])->toBe(['workshop_id', 'sku'])
        ->and($indexes('products')['products_sku_unique']['unique'])->toBeTrue()
        ->and($indexes('materials')['materials_restock_idx']['columns'])->toBe(['workshop_id', 'name', 'id'])
        ->and(Schema::hasIndex('sales', ['workshop_id', 'sold_on', 'id']))->toBeTrue()
        ->and(Schema::hasIndex('sales', ['sold_on', 'id']))->toBeFalse();

    // The predicates survive (a partial index is what lets a deleted SKU be used again, and what the restock query matches).
    expect(indexDefinition('materials_sku_unique'))->toContain('deleted_at IS NULL')
        ->and(indexDefinition('products_sku_unique'))->toContain('deleted_at IS NULL')
        ->and(indexDefinition('materials_restock_idx'))->toContain('deleted_at IS NULL')->toContain('reorder_level IS NOT NULL');
});

it('keeps a SKU unique per workshop among non-deleted rows', function (string $model) {
    $first = Workshop::factory()->create();
    $second = Workshop::factory()->create();
    $make = fn (Workshop $workshop) => $model::factory()->create(['workshop_id' => $workshop->id, 'sku' => 'SAME-SKU']);

    // Accepted in two workshops, refused twice in one (a savepoint, so the rejected row doesn't abort the transaction).
    $original = $make($first);
    $make($second);

    expect(fn () => DB::transaction(fn () => $make($first)))->toThrow(QueryException::class);

    // A soft delete frees the SKU.
    $original->delete();
    $make($first);

    expect($model::query()->where('sku', 'SAME-SKU')->count())->toBe(2);
})->with([
    'materials' => [Material::class],
    'products' => [Product::class],
]);

it('refuses to merge the workshops back while two of them share a SKU, and changes nothing', function () {
    $first = Workshop::factory()->create();
    $second = Workshop::factory()->create();

    foreach ([$first, $second] as $workshop) {
        Material::factory()->create(['workshop_id' => $workshop->id, 'sku' => 'DUP-MAT']);
        Product::factory()->create(['workshop_id' => $workshop->id, 'sku' => 'DUP-PRD']);
        // A deleted twin is no clash: the global index being restored ignores deleted rows.
        Material::factory()->create(['workshop_id' => $workshop->id, 'sku' => 'GONE-MAT'])->delete();
    }

    $counts = ownedRowCounts();

    expect(fn () => ownedDataMigration()->down())->toThrow(function (RuntimeException $e): void {
        expect($e->getMessage())->toContain('materials: DUP-MAT')->toContain('products: DUP-PRD')->not->toContain('GONE-MAT');
    })->and(Schema::hasColumn('materials', 'workshop_id'))->toBeTrue()
        ->and(ownedRowCounts())->toBe($counts);
});

it('makes the database refuse a record whose workshop differs from its parent\'s', function (string $constraint, Closure $make) {
    $workshop = Workshop::factory()->create();
    $other = Workshop::factory()->create();

    $parents = [
        'product' => Product::factory()->create(['workshop_id' => $workshop->id]),
        'material' => Material::factory()->create(['workshop_id' => $workshop->id]),
        'otherProduct' => Product::factory()->create(['workshop_id' => $other->id]),
        'otherMaterial' => Material::factory()->create(['workshop_id' => $other->id]),
    ];

    // A savepoint, so the rejected row doesn't abort the surrounding transaction.
    expect(fn () => DB::transaction(fn () => $make($workshop, $parents)))->toThrow(QueryException::class, $constraint);
})->with([
    'a line of a product from another workshop' => ['product_components_workshop_product_foreign', fn (Workshop $workshop, array $p) => ProductComponent::factory()->create([
        'workshop_id' => $workshop->id, 'product_id' => $p['otherProduct']->id, 'material_id' => $p['material']->id,
    ])],
    'a line of a material from another workshop' => ['product_components_workshop_material_foreign', fn (Workshop $workshop, array $p) => ProductComponent::factory()->create([
        'workshop_id' => $workshop->id, 'product_id' => $p['product']->id, 'material_id' => $p['otherMaterial']->id,
    ])],
    'a sale of a product from another workshop' => ['sales_workshop_product_foreign', fn (Workshop $workshop, array $p) => Sale::factory()->create([
        'workshop_id' => $workshop->id, 'product_id' => $p['otherProduct']->id,
    ])],
    'a movement of a material from another workshop' => ['stock_movements_workshop_material_foreign', fn (Workshop $workshop, array $p) => StockMovement::factory()->create([
        'workshop_id' => $workshop->id, 'material_id' => $p['otherMaterial']->id,
    ])],
])->skip(fn () => DB::getDriverName() !== 'pgsql', 'The composite foreign keys exist on PostgreSQL only; SQLite relies on validation and the Actions (F-59).');
