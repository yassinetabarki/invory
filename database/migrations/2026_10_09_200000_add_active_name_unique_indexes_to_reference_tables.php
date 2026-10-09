<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * F-32 (ADR-0008): the name of a unit or a category is unique among the ACTIVE rows, ignoring case ("Elastic" and "elastic" can't both
 * be active). Validation checks it with `lower(name) = lower(?)`; this partial index is the database's own guard, so a race or a
 * code path that skips validation can't break the rule. An archived row never clashes. Same syntax on PostgreSQL and SQLite.
 *
 * `lower()` folds non-ASCII letters on PostgreSQL (with a Unicode-aware locale) and ASCII only on SQLite, on the index and on the
 * validation query alike, so the two always agree on a given engine.
 *
 * If a later migration rebuilds one of these tables on SQLite (a column change or drop makes it copy the table and re-create the
 * indexes from introspection, which does not know the `WHERE` clause or the expression of a partial index), it must drop these
 * indexes first and create them again at the end, as the S-21 migration does with the SKU index of `materials`.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = ['units', 'material_categories', 'product_categories'];

    public function up(): void
    {
        // Check every table first, so one failed run lists everything the human has to fix.
        $clashes = [];

        foreach (self::TABLES as $table) {
            foreach ($this->clashingNames($table) as $names) {
                $clashes[] = "{$table}: ".implode(', ', array_map(fn (string $name): string => "\"{$name}\"", $names));
            }
        }

        if ($clashes !== []) {
            throw new RuntimeException(
                'Cannot make names unique ignoring case, active rows clash ('.implode('; ', $clashes).'). '
                .'Rename or archive one of them, then migrate again.',
            );
        }

        foreach (self::TABLES as $table) {
            DB::statement("CREATE UNIQUE INDEX {$this->indexName($table)} ON {$table} (lower(name)) WHERE archived_at IS NULL");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            DB::statement("DROP INDEX IF EXISTS {$this->indexName($table)}");
        }
    }

    private function indexName(string $table): string
    {
        return "{$table}_active_name_unique";
    }

    /**
     * Groups of active names that are equal once lower-cased, as stored.
     *
     * @return list<list<string>>
     */
    private function clashingNames(string $table): array
    {
        $folded = DB::table($table)
            ->whereNull('archived_at')
            ->groupByRaw('lower(name)')
            ->havingRaw('count(*) > 1')
            ->selectRaw('lower(name) as folded')
            ->pluck('folded');

        $groups = [];

        foreach ($folded as $key) {
            /** @var list<string> $names */
            $names = DB::table($table)
                ->whereNull('archived_at')
                ->whereRaw('lower(name) = ?', [$key])
                ->orderBy('id')
                ->pluck('name')
                ->all();

            $groups[] = $names;
        }

        return $groups;
    }
};
