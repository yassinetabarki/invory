# ADR-0002 — Money and quantities: decimal strings + BigDecimal, never floats

- **Status:** Accepted · 2026-10-07

## Context
Costs per unit in fashion can be tiny (thread at €0.0040/m) and quantities fractional (1.98 m of denim). Floating-point maths
produces visible errors (`0.1 + 0.2 ≠ 0.3`) and inconsistent rounding between PHP and JavaScript. Storing integer cents can't represent
sub-cent unit costs.

## Decision
- Store costs as `decimal(12,4)`, quantities as `decimal(14,4)`, percentages as `decimal(5,2)`.
- Eloquent `decimal:N` casts return **strings**. All arithmetic uses `Brick\Math\BigDecimal` (already a Laravel dependency).
- Intermediate results are exact. Outputs are rounded once, `HALF_UP`, to scale 4. Capacity is floored.
- Over the wire, decimals are **strings**. The frontend only formats them (`Intl.NumberFormat` accepts numeric strings) and never computes.
- Single currency per installation (`APP_CURRENCY`, default TND; *amended 2026-10-08: the default was EUR. Display uses each currency's own ISO minor-unit digits (TND 3, EUR 2), and per-unit costs below one minor unit show 4 decimals (human decision 2026-10-08, backlog F-10). Storage and serialization scales are unaffected*).

## Consequences
- ✅ Deterministic, testable numbers (golden tests use exact strings).
- ⚠️ SQLite (fast test suite only) has no true decimal type. Values are read back through the `decimal:N` cast (BigDecimal rounding), so they
  stay correct at scale 4. Dev (Docker PostgreSQL, [ADR-0012](0012-dev-docker-db-and-mail-only.md)) and production use PostgreSQL native decimals, and CI runs the suite on PostgreSQL too ([ADR-0005](0005-dev-environment-laragon-sail.md)).
- ⚠️ Multi-currency would need a currency column plus FX snapshots (phase 2+).
