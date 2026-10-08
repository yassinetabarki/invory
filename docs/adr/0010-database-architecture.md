# ADR-0010 — Database architecture: bigint keys, exact numerics, cached stock + ledger, product metrics read model

- **Status:** Proposed · 2026-10-08 (tech-lead; decisions Q4, Q8 of [phase-3 plan](../plans/phase-3-performance-spa-data.md) §D, which holds the ER
  diagram and column details)
- **Builds on:** [ADR-0002](0002-money-and-quantity-precision.md), [ADR-0003](0003-single-tenant-mvp.md), [ADR-0006](0006-ui-first-preview-data.md),
  [ADR-0008](0008-reference-data-in-database.md)

## Context
Phase 3 replaces the preview data with real tables (`products`, `product_components`, `sales`, `stock_movements`, photos) and adds the reference
tables. The product list must filter on "can't produce" and paginate. The dashboard counts products that can't be produced and sums sales. The
material page shows usage per product. Money must stay exact, and the fast test suite runs on SQLite, whose `SUM` uses floats.

## Decision (proposed)
- **Keys:** bigint identity everywhere (`$table->id()`), no ULIDs. One company, policies on every route, routes constrained to digits, Wayfinder
  numbers. Add a `public_id` ULID only if a public API needs opaque ids.
- **Types:** `numeric(12,4)` for costs and prices, `numeric(14,4)` for quantities, `numeric(5,2)` for waste, `numeric(16,4)` for derived costs,
  `numeric(24,12)` for unit factors; `date` for business dates; no floats.
- **Integrity:**
  - FKs: BOM → product `cascade`; BOM → material `restrict`; sales → product `restrict`; movements → material `cascade` (materials are only
    soft-deleted); reference → `restrict`; users → `nullOnDelete`.
  - Partial unique SKUs `WHERE deleted_at IS NULL`.
  - `CHECK` range constraints on PostgreSQL.
  - `unique(product_id, material_id)`.
- **Deletes:** soft deletes on `materials` and `products` only; reference tables are archived; the ledger is append-only; sales are hard-deleted
  (pending decision Q8).
- **Audit:** `user_id` on `sales` and `stock_movements`; `created_by` / `updated_by` on `materials` and `products`.
- **Stock:** `materials.quantity_on_hand` is the cached authoritative balance. An append-only `stock_movements` ledger records every change. Both
  are written in one transaction with `lockForUpdate()` on the material. `invory:stock:verify` checks drift. Sales never move stock.
- **Read model `product_metrics`** (`product_id` PK, `material_cost`, `unit_cost`, `max_producible`, `bottleneck_material_id`, `components_count`,
  `computed_at`):
  - computed only by `CostingCalculator` through one Action (`RefreshProductMetrics`)
  - called inside the transaction of every write that changes a BOM, a product cost, or a material's stock or cost
  - lets the product list and dashboard filter, sort and count in SQL
  - margin is computed on read
- **Aggregates:** money sums in PHP with `BigDecimal` over narrow lazy cursors, cached under version keys bumped by the writing Actions. Integer
  counts and sums in SQL.
- **Search:** `pg_trgm` GIN indexes on `name` and `sku` of materials and products (PostgreSQL only; SQLite falls back to a LIKE scan).

## Consequences
- ✅ Exact numbers, enforced integrity, list pages paginate in SQL whatever the catalogue size.
- ✅ History and audit for stock and price changes.
- ⚠️ Every write path that affects costing must call `RefreshProductMetrics`. This is enforced by tests that compare the metrics with the calculator
  after each write path, plus an `invory:metrics:rebuild` command.
- ⚠️ PostgreSQL-only features (`CHECK`, trigram) are guarded by driver and covered by the parity suite.
- ⚠️ ARCHITECTURE §4 and §10 need an update when this is accepted (`product_metrics` moves from post-MVP into Phase 3).
