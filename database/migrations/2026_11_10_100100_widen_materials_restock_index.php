<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * INV-21-b (ADR-0029 §8): the partial index behind the "needs restock" query holds the per-colour materials too.
 *
 * `Material::needsRestock()` got a third arm (a per-colour material with some colour at or under the reorder level). PostgreSQL uses a
 * partial index only when the query provably implies its predicate, so the predicate gets `OR stock_mode = 'per_colour'`: the scope's third
 * arm starts with the same condition, and an AND implies each of its conjuncts. Same columns as the index of 2026_10_15_100100
 * (`workshop_id, name, id`), same syntax on PostgreSQL and SQLite. `down()` restores the predicate of before. It imports no app class.
 */
return new class extends Migration
{
    private const BASE_PREDICATE = 'quantity_on_hand <= 0 OR (reorder_level IS NOT NULL AND quantity_on_hand <= reorder_level)';

    public function up(): void
    {
        $this->recreate(self::BASE_PREDICATE." OR stock_mode = 'per_colour'");
    }

    public function down(): void
    {
        $this->recreate(self::BASE_PREDICATE);
    }

    private function recreate(string $arms): void
    {
        DB::statement('DROP INDEX IF EXISTS materials_restock_idx');
        DB::statement("CREATE INDEX materials_restock_idx ON materials (workshop_id, name, id) WHERE deleted_at IS NULL AND ({$arms})");
    }
};
