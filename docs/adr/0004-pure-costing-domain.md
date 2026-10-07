# ADR-0004 — Costing maths in a pure, framework-free domain module

- **Status:** Accepted · 2026-10-07

## Context
Unit cost and production capacity are the product's core value. They must be exactly right, reusable (list pages, detail pages,
dashboard, future queued recomputation, API, production planner) and cheap to test exhaustively.

## Decision
- All costing logic lives in `app/Domain/Costing` as plain PHP with `final readonly` value objects
  (`BomLine` in → `CostBreakdown` / `ProductionCapacity` out), depending only on `brick/math`.
- `App\Services\Costing\ProductCostingService` is the single adapter from Eloquent models to `BomLine[]`.
- A Pest `arch()` test forbids `Illuminate\*`, `App\Models` and `App\Http` imports in `App\Domain`.
- The rules are specified in the `invory-costing-domain` skill, which is the source of truth for both code and tests.

## Consequences
- ✅ Millisecond unit tests without a database. Fully deterministic.
- ✅ Multi-level BOMs, variants or cached metrics later only change the adapter, or add inputs, not callers.
- ⚠️ The adapter must eager-load `components.material` (enforced by strict mode in tests).
