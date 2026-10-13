<?php

use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The partial index behind the "needs restock" query (S-28-b, plan P-2)
|--------------------------------------------------------------------------
|
| The index predicate mirrors Material::needsRestock(). The PostgreSQL test proves the planner can use it for the
| dashboard's query, with bound parameters as the app runs it (parity run only; skipped on SQLite).
*/

it('creates the partial restock index on materials', function () {
    expect(Schema::hasIndex('materials', 'materials_restock_idx'))->toBeTrue();
});

it('lets the planner use the restock index for the needs-restock query', function () {
    DB::statement('SET LOCAL enable_seqscan = off');

    // Many healthy rows (the factory's stock is well above its reorder level) make the partial index clearly the cheapest
    // path, whatever planner statistics earlier tests left behind.
    Material::factory()->count(40)->create();
    Material::factory()->outOfStock()->create();
    Material::factory()->lowStock()->create();
    Material::factory()->lowStock()->create()->delete();
    DB::statement('ANALYZE materials');

    $query = Material::query()
        ->without(['unit', 'category'])
        ->needsRestock()
        ->orderByRaw('CASE WHEN quantity_on_hand <= 0 THEN 0 ELSE 1 END')
        ->orderBy('name')
        ->orderBy('id')
        ->limit(5);

    $plan = collect(DB::select('EXPLAIN '.$query->toSql(), $query->getBindings()))
        ->map(fn (object $row): string => (string) head((array) $row))
        ->implode("\n");

    expect($plan)->toContain('materials_restock_idx');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'Index planning is checked on PostgreSQL only (the parity run).');
