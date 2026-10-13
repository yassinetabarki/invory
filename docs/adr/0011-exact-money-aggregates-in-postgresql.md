# ADR-0011 — Exact money aggregates in SQL on PostgreSQL, PHP loop on other drivers

- **Status:** Accepted · 2026-10-13 (tech-lead, on the human's S-28 brief; [plan](../plans/perf-dashboard-endpoint.md) P-1)
- **Narrows:** [ADR-0010](0010-database-architecture.md) "Aggregates: money sums in PHP with `BigDecimal` over narrow lazy cursors"
- **Keeps:** [ADR-0002](0002-money-and-quantity-precision.md) (decimal strings + BigDecimal, never floats)

## Context
`InventorySummary::summarize()` (dashboard stats and the materials-index `summary`) hydrated every non-deleted material and summed
`quantity_on_hand × unit_cost` with `BigDecimal`, because SQLite, which runs the fast test suite, sums in floats. The cost grows with the
catalogue on every dashboard visit. PostgreSQL, the only database of dev and production, stores these columns as `NUMERIC`, whose `*`,
`ROUND` and `SUM` are exact.

## Decision
- A money aggregate may run in SQL **on PostgreSQL only**, when every input column is `NUMERIC` and the SQL reproduces the domain rule
  exactly: per-row rounding with `ROUND(x, 4)` (half away from zero, equal to `HalfUp` for the non-negative values the CHECK constraints
  guarantee), then an exact `SUM`. The result is re-serialised in PHP to a scale-4 string (`BigDecimal::of(...)->toScale(4, HalfUp)`).
- On every other driver the PHP `BigDecimal` path stays, unchanged.
- The SQL path is proven by running the same test file in the PostgreSQL parity suite; the PHP path by the SQLite run.
- First (and so far only) use: `App\Services\Inventory\InventorySummary`. Costing maths (`unit_cost`, capacity) stays in `App\Domain\Costing`.

## Consequences
- ✅ One query for the inventory totals whatever the catalogue size; no hydration, no per-row objects (resolves F-24 on PostgreSQL).
- ⚠️ Two implementations of one figure. They must change together; only the parity suite exercises the SQL one, so it must run for any change
  to these aggregates (CI runs it, ADR-0005).
