# S-28 — Perf: speed up the dashboard endpoint

> Owner: **tech-lead** · Status: **approved 2026-10-13, awaiting commit** (written 2026-10-13) · Branch: `main` (clean at intake, HEAD 57e215b).
> Backend only: no page, prop, route or `domain.ts` change. No caching layer. `ProductCatalog` loading every product stays an accepted
> limitation until S-23 / F-38.

## Goal
Two backend fixes the human's investigation found on `GET /dashboard` (the second also helps the materials index, which shares both code paths):
1. `InventorySummary::summarize()` hydrates every non-deleted material in PHP (chunks of 500, 3+ `BigDecimal`s per row). On PostgreSQL the
   columns are exact `NUMERIC`, so one aggregate query gives the same exact figures.
2. The `needsRestock` scope (dashboard "Needs restock", materials index `low_stock` filter) has no supporting index: a sequential scan before
   `LIMIT 5`.

## Decisions
| # | Decision | By |
|---|---|---|
| P-1 | `summarize()` is **driver-aware**: on `pgsql` one aggregate query (`COUNT(*)`, `COUNT(*) FILTER (WHERE …)` for low/out, `COALESCE(SUM(ROUND(quantity_on_hand * unit_cost, 4)), 0)`); every other driver (SQLite tests) keeps the `lazyById` loop **unchanged**. PostgreSQL `ROUND(numeric, 4)` rounds half away from zero, which equals `HalfUp` here because stock and cost are ≥ 0 (CHECK constraints). The SQL value is re-serialised through `BigDecimal::of(...)->toScale(4, RoundingMode::HalfUp)` (an empty table sums to `0`, which must come out as `"0.0000"`). Recorded in **ADR-0011** (it narrows ADR-0010's "money sums in PHP") | human brief + tech-lead |
| P-2 | **The index the brief named is not used by PostgreSQL**, so it ships in a different shape. Measured by the tech-lead on 2026-10-13 (PostgreSQL in `invory-pgsql-1`, temp table, 200 000 rows, 3 327 needing restock, the dashboard's exact `WHERE … ORDER BY CASE …, name, id LIMIT 5`): no index → Seq Scan, 22.0 ms; brief's `(quantity_on_hand, reorder_level) WHERE deleted_at IS NULL` → **still Seq Scan**, 20.5 ms (the planner can't use a btree on `qoh <= reorder_level`, a column-to-column comparison inside an `OR`); **partial index whose predicate is the scope itself** → Bitmap Index Scan, **4.6 ms**. Decision: `CREATE INDEX materials_restock_idx ON materials (name, id) WHERE deleted_at IS NULL AND (quantity_on_hand <= 0 OR (reorder_level IS NOT NULL AND quantity_on_hand <= reorder_level))`. Key `(name, id)` also gives the materials index `low_stock` filter its default order. Same syntax on SQLite. `down()` drops it with `DB::statement()`, like `materials_sku_unique`. The scope and the index predicate must stay identical: a comment on the scope points at the migration, and a PostgreSQL-only test proves the planner can use the index for the scope's query (with bound parameters) | tech-lead (technical, evidence above) |
| P-3 | The PostgreSQL path is covered by running the **existing** `InventorySummaryTest` in the parity suite (empty table, preview fixture, soft deletes, the status dataset, scale-4 rounding, beyond-float exactness, 501 rows) plus one PostgreSQL-only test (one aggregate query for 501 materials). The SQLite tests keep proving the PHP path | tech-lead |
| P-4 | Developers may run the migration on the **`testing`** database through the parity container (incl. `migrate:rollback --step=1` to prove `down()`), **not** on the dev database `laravel`. The human runs `migrate` on the dev DB | tech-lead |

## Slicing table
Both rows are CRITICAL (money aggregate; migration index), disjoint files, dispatched in parallel. One commit per row.

| # | Subtask | Lane | Files (exact paths) | Line budget | Tests allowed | Owner | Commit label |
|---|---|---|---|---|---|---|---|
| 1 | S-28-a | CRITICAL | `app/Services/Inventory/InventorySummary.php`, `tests/Feature/Inventory/InventorySummaryTest.php` | ~70 prod / ~20 test | existing file unchanged except 1 new PostgreSQL-only test: 501 materials → exactly 1 query and the right totals | BE-1 | `perf(inventory): sum the inventory totals in one SQL aggregate on PostgreSQL (S-28)` |
| 2 | S-28-b | CRITICAL | `database/migrations/2026_10_13_300000_add_restock_index_to_materials_table.php`, `app/Models/Material.php` (comment on `needsRestock` only), `tests/Feature/Performance/MaterialRestockIndexTest.php` | ~40 prod / ~40 test | 1 PostgreSQL-only test: the planner uses `materials_restock_idx` for the `needsRestock()` query (seq scan disabled with `SET LOCAL`); 1 driver-agnostic test: the index exists after migrations | BE-2 | `perf(materials): partial index for the needs-restock query (S-28)` |

## Progress log (tech-lead)
- 2026-10-13: intake. `main` clean, PHP 8.3.35, Docker up (`invory-laravel.test-1`, `invory-pgsql-1`). Measured P-2. Plan + ADR-0011
  written. Dispatching S-28-a (BE-1) ∥ S-28-b (BE-2).
- 2026-10-13: both handoffs in. **HEAD itself was red** (not caused by S-28): Pint on `tests/Feature/Photos/StorageSetupTest.php` (from
  57e215b) and 13 SQLite errors from `DROP TABLE IF EXISTS products CASCADE` in the `down()` of `2026_10_09_300100_…` (from 12c2a16).
  Added row **S-28-0** (driver-aware `down()`, Pint), its own commit merged first. Full parity then found 3 reds in
  `PartialReloadTest` (`SUMMARY_SCAN` only matched the PHP scan) and a planner flake in `MaterialRestockIndexTest` (6 rows, stale stats);
  fixed in one round (alternation regex; 40 healthy rows + `ANALYZE materials`). All gates green, parity 2 073 passed. **Approved.**

| # | Subtask | Lane | Files | Owner | Commit label |
|---|---|---|---|---|---|
| 0 | S-28-0 | CRITICAL (migration `down()`) | `database/migrations/2026_10_09_300100_create_products_and_product_components_tables.php`, `tests/Feature/Photos/StorageSetupTest.php` | BE-3 | `fix(migrations): drop products without CASCADE on SQLite; format StorageSetupTest` |
