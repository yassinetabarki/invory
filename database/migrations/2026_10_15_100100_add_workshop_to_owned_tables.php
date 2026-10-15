<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0013 (S-44-b): every material, product, composition line, stock movement and sale belongs to a workshop, and everything
 * recorded so far moves into one. Nothing is filtered yet (the global scope is S-44-c): this step only stamps the rows.
 *
 * Backfill: the target is the oldest workshop, which holds today's accounts after S-44-a. When there is none but a table holds a
 * row (soft-deleted ones count), one workshop is created. A database without rows gets no workshop.
 *
 * SQLite rebuilds a table when a column gets a foreign key or becomes NOT NULL, and re-creates its indexes from introspection, which
 * loses the `WHERE` of a partial index. So the partial indexes (and the sales log index, replaced anyway) are dropped first and created
 * again at the end, as 2026_10_09_300000 does for `materials`. Both engines run the same code path.
 *
 * PostgreSQL only: `unique(workshop_id, id)` on materials and products, and composite foreign keys that make the database refuse a
 * composition line, a sale or a stock movement whose workshop differs from its parent's (R4). They sit beside the single-column keys.
 *
 * `down()` merges back: it refuses while two workshops share a SKU among non-deleted rows (the installation-wide unique index could
 * not be restored), and it never deletes a row.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = ['materials', 'products', 'product_components', 'stock_movements', 'sales'];

    /** @var array<string, array{0: string, 1: string, 2: string}> constraint => [table, column, parent table], all on (workshop_id, <column>) */
    private const COMPOSITE_KEYS = [
        'product_components_workshop_product_foreign' => ['product_components', 'product_id', 'products'],
        'product_components_workshop_material_foreign' => ['product_components', 'material_id', 'materials'],
        'sales_workshop_product_foreign' => ['sales', 'product_id', 'products'],
        'stock_movements_workshop_material_foreign' => ['stock_movements', 'material_id', 'materials'],
    ];

    public function up(): void
    {
        $this->dropIndexes();

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->foreignId('workshop_id')->nullable()->constrained('workshops')->restrictOnDelete();
            });
        }

        $this->backfill();

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->unsignedBigInteger('workshop_id')->nullable(false)->change();
            });
        }

        $this->createWorkshopIndexes();

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresKeys();
        }
    }

    public function down(): void
    {
        $this->assertNoSkuIsSharedByTwoWorkshops();

        if (DB::getDriverName() === 'pgsql') {
            $this->dropPostgresKeys();
        }

        $this->dropIndexes();

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropForeign(['workshop_id']);
            });
        }

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('workshop_id');
            });
        }

        $this->createGlobalIndexes();
    }

    /** The target workshop is the oldest one; with none, a workshop is created only when some table has a row to move. */
    private function backfill(): void
    {
        $workshopId = DB::table('workshops')->orderBy('id')->value('id');

        if ($workshopId === null && $this->anyTableHasRows()) {
            $workshopId = DB::table('workshops')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        }

        if ($workshopId === null) {
            return;
        }

        foreach (self::TABLES as $name) {
            DB::table($name)->update(['workshop_id' => $workshopId]);
        }
    }

    private function anyTableHasRows(): bool
    {
        foreach (self::TABLES as $name) {
            if (DB::table($name)->exists()) {
                return true;
            }
        }

        return false;
    }

    /** The indexes this step replaces, under their real names. */
    private function dropIndexes(): void
    {
        foreach (['materials_sku_unique', 'products_sku_unique', 'materials_restock_idx', 'sales_sold_on_id_index', 'sales_workshop_id_sold_on_id_index'] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
    }

    private function createWorkshopIndexes(): void
    {
        // SKU is unique per workshop among non-deleted rows only. Same syntax on PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX materials_sku_unique ON materials (workshop_id, sku) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX products_sku_unique ON products (workshop_id, sku) WHERE deleted_at IS NULL');

        // The predicate IS Material::needsRestock() (see 2026_10_13_300000), behind the workshop.
        DB::statement(
            'CREATE INDEX materials_restock_idx ON materials (workshop_id, name, id) '
            .'WHERE deleted_at IS NULL AND (quantity_on_hand <= 0 OR (reorder_level IS NOT NULL AND quantity_on_hand <= reorder_level))',
        );

        // The sales log of a workshop, newest first.
        DB::statement('CREATE INDEX sales_workshop_id_sold_on_id_index ON sales (workshop_id, sold_on, id)');
    }

    /** The installation-wide indexes of today, as the earlier migrations created them. */
    private function createGlobalIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX materials_sku_unique ON materials (sku) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX products_sku_unique ON products (sku) WHERE deleted_at IS NULL');
        DB::statement(
            'CREATE INDEX materials_restock_idx ON materials (name, id) '
            .'WHERE deleted_at IS NULL AND (quantity_on_hand <= 0 OR (reorder_level IS NOT NULL AND quantity_on_hand <= reorder_level))',
        );
        DB::statement('CREATE INDEX sales_sold_on_id_index ON sales (sold_on, id)');
    }

    private function addPostgresKeys(): void
    {
        foreach (['materials', 'products'] as $name) {
            DB::statement("ALTER TABLE {$name} ADD CONSTRAINT {$name}_workshop_id_id_unique UNIQUE (workshop_id, id)");
        }

        foreach (self::COMPOSITE_KEYS as $constraint => [$name, $column, $parent]) {
            DB::statement("ALTER TABLE {$name} ADD CONSTRAINT {$constraint} FOREIGN KEY (workshop_id, {$column}) REFERENCES {$parent} (workshop_id, id)");
        }
    }

    private function dropPostgresKeys(): void
    {
        foreach (self::COMPOSITE_KEYS as $constraint => [$name]) {
            DB::statement("ALTER TABLE {$name} DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        foreach (['materials', 'products'] as $name) {
            DB::statement("ALTER TABLE {$name} DROP CONSTRAINT IF EXISTS {$name}_workshop_id_id_unique");
        }
    }

    /**
     * Inside one workshop a SKU is already unique among non-deleted rows, so a SKU that appears twice belongs to two workshops.
     * Name them, instead of PostgreSQL's generic "could not create unique index".
     */
    private function assertNoSkuIsSharedByTwoWorkshops(): void
    {
        $shared = [];

        foreach (['materials', 'products'] as $name) {
            $skus = DB::table($name)->whereNull('deleted_at')->groupBy('sku')->havingRaw('COUNT(*) > 1')->orderBy('sku')->pluck('sku');

            if ($skus->isNotEmpty()) {
                $shared[] = "{$name}: ".$skus->implode(', ');
            }
        }

        if ($shared !== []) {
            throw new RuntimeException(
                'Cannot merge the workshops back into one installation, these SKUs are used by more than one workshop ('.implode('; ', $shared).'). '
                .'Rename or delete them, then roll back again.',
            );
        }
    }
};
