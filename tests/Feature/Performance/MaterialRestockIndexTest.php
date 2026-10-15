<?php

use App\Models\Material;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The partial index behind the "needs restock" query (S-28-b, plan P-2; per workshop since S-44-b)
|--------------------------------------------------------------------------
|
| The index predicate mirrors Material::needsRestock(), and it leads with `workshop_id`. The PostgreSQL test proves the planner can use
| it for the dashboard's query, with bound parameters as the app runs it (parity run only; skipped on SQLite).
*/

it('creates the partial restock index on materials', function () {
    expect(Schema::hasIndex('materials', 'materials_restock_idx'))->toBeTrue();
});

it('lets the planner use the restock index for the needs-restock query of a workshop', function () {
    // F-58: this test failed once in a full parity run (and passed alone), because the plan depends on the table statistics and on how
    // many rows the index saves. So the data is large and lopsided, whatever earlier tests left behind: thousands of healthy rows in
    // two workshops (bulk-inserted, one statement each) against a handful that need a restock, and fresh statistics.
    DB::statement('SET LOCAL enable_seqscan = off');

    $workshop = Workshop::factory()->create();
    $other = Workshop::factory()->create();
    $categoryId = DB::table('material_categories')->where('code', 'fabric')->value('id');
    $unitId = DB::table('units')->where('code', 'm')->value('id');

    foreach ([$workshop, $other] as $owner) {
        // Stock 500 against a reorder level of 10: healthy, so the partial index doesn't hold them.
        DB::insert(
            'INSERT INTO materials (workshop_id, sku, name, category_id, unit_id, unit_cost, quantity_on_hand, reorder_level, created_at, updated_at) '
            ."SELECT ?, 'BULK-' || ? || '-' || n, 'Bulk material ' || n, ?, ?, 1, 500, 10, now(), now() FROM generate_series(1, 3000) AS n",
            [$owner->id, $owner->id, $categoryId, $unitId],
        );
    }

    Material::factory()->count(2)->outOfStock()->create(['workshop_id' => $workshop->id]);
    Material::factory()->count(2)->lowStock()->create(['workshop_id' => $workshop->id]);
    Material::factory()->lowStock()->create(['workshop_id' => $workshop->id])->delete();
    Material::factory()->outOfStock()->create(['workshop_id' => $other->id]);
    DB::statement('ANALYZE materials');

    // The query as the workshop's screens run it: filtered by `workshop_id` (the global scope of S-44-c adds the same condition).
    $query = Material::query()
        ->without(['unit', 'category'])
        ->where('workshop_id', $workshop->id)
        ->needsRestock()
        ->orderByRaw('CASE WHEN quantity_on_hand <= 0 THEN 0 ELSE 1 END')
        ->orderBy('name')
        ->orderBy('id')
        ->limit(5);

    $plan = collect(DB::select('EXPLAIN '.$query->toSql(), $query->getBindings()))
        ->map(fn (object $row): string => (string) head((array) $row))
        ->implode("\n");

    expect($plan)->toContain('materials_restock_idx')
        ->and($query->pluck('id'))->toHaveCount(4);
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'Index planning is checked on PostgreSQL only (the parity run).');
