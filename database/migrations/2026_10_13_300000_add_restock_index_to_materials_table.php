<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S-28-b (plan perf-dashboard-endpoint.md, P-2): partial index for the "needs restock" query (dashboard list, materials `low_stock` filter).
 *
 * The predicate IS the `Material::needsRestock()` scope: PostgreSQL cannot use a btree on `quantity_on_hand <= reorder_level` (a column-to-column
 * comparison inside an OR), but it can use a partial index whose predicate matches the query. Keep this predicate identical to the scope.
 * Same syntax on PostgreSQL and SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX materials_restock_idx ON materials (name, id) '
            .'WHERE deleted_at IS NULL AND (quantity_on_hand <= 0 OR (reorder_level IS NOT NULL AND quantity_on_hand <= reorder_level))',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX materials_restock_idx');
    }
};
