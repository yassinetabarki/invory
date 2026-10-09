<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S-04a (ADR-0010, plan §D.1 / §D.4): PostgreSQL-only extras. SQLite does nothing here (it relies on validation and a LIKE scan).
 *
 * - CHECK range constraints: the database refuses a negative cost or stock, a zero or negative BOM quantity and a waste of 100 % or more.
 * - `pg_trgm` GIN indexes on the name and the SKU of materials and products, so the `%term%` search can use an index.
 *
 * `CREATE EXTENSION pg_trgm` is a "trusted" extension since PostgreSQL 13: the database owner can run it without being a superuser.
 * Older versions, or a role that does not own the database, need a superuser to run `CREATE EXTENSION pg_trgm;` once beforehand.
 * `down()` drops the constraints and the indexes, never the extension (other objects may use it).
 */
return new class extends Migration
{
    /** @var array<string, array<string, string>> table => constraint name => condition */
    private const CHECKS = [
        'materials' => [
            'materials_unit_cost_check' => 'unit_cost >= 0',
            'materials_quantity_on_hand_check' => 'quantity_on_hand >= 0',
            'materials_reorder_level_check' => 'reorder_level IS NULL OR reorder_level >= 0',
        ],
        'products' => [
            'products_labor_cost_check' => 'labor_cost >= 0',
            'products_overhead_cost_check' => 'overhead_cost >= 0',
            'products_selling_price_check' => 'selling_price IS NULL OR selling_price >= 0',
        ],
        'product_components' => [
            'product_components_quantity_check' => 'quantity > 0',
            'product_components_waste_percent_check' => 'waste_percent >= 0 AND waste_percent < 100',
        ],
    ];

    /** @var array<string, array{0: string, 1: string}> index name => [table, column] */
    private const TRIGRAM_INDEXES = [
        'materials_name_trgm_idx' => ['materials', 'name'],
        'materials_sku_trgm_idx' => ['materials', 'sku'],
        'products_name_trgm_idx' => ['products', 'name'],
        'products_sku_trgm_idx' => ['products', 'sku'],
    ];

    public function up(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        $this->assertExistingMaterialsSatisfyTheChecks();

        foreach (self::CHECKS as $table => $constraints) {
            foreach ($constraints as $name => $condition) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$condition})");
            }
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach (self::TRIGRAM_INDEXES as $name => [$table, $column]) {
            DB::statement("CREATE INDEX {$name} ON {$table} USING gin ({$column} gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        foreach (array_keys(self::TRIGRAM_INDEXES) as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }

        foreach (self::CHECKS as $table => $constraints) {
            foreach (array_keys($constraints) as $name) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$name}");
            }
        }
    }

    private function isPostgres(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }

    /**
     * `materials` already holds rows (the new tables are empty). Fail with the ids to fix instead of PostgreSQL's generic
     * "check constraint is violated by some row". Soft-deleted rows count, a constraint covers every row.
     */
    private function assertExistingMaterialsSatisfyTheChecks(): void
    {
        $violations = DB::table('materials')
            ->where(fn ($query) => $query
                ->where('unit_cost', '<', 0)
                ->orWhere('quantity_on_hand', '<', 0)
                ->orWhere('reorder_level', '<', 0))
            ->orderBy('id')
            ->get(['id', 'sku', 'unit_cost', 'quantity_on_hand', 'reorder_level']);

        if ($violations->isNotEmpty()) {
            $details = $violations
                ->map(fn (object $row): string => "#{$row->id} {$row->sku} (unit_cost {$row->unit_cost}, quantity_on_hand {$row->quantity_on_hand}, reorder_level ".($row->reorder_level ?? 'null').')')
                ->implode('; ');

            throw new RuntimeException(
                "Cannot add the CHECK constraints to materials, these rows have a negative unit_cost, quantity_on_hand or reorder_level: {$details}. "
                .'Correct them, then migrate again.',
            );
        }
    }
};
