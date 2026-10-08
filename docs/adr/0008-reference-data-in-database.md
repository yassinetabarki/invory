# ADR-0008 — Reference data in the database: units and categories become tables; behavioural enums stay in PHP

- **Status:** Accepted · 2026-10-08 (the human: users may add, rename and archive units; seeded units keep a fixed dimension and factor; BOM unit
  conversions later (columns designed now, no conversion maths in Phase 3); any signed-in user edits reference data. Q7, the unit lock, is
  provisional until the human confirms it. See [phase-3 plan](../plans/phase-3-performance-spa-data.md) §C)
- **Changes:** costing skill §"Units and categories"; ARCHITECTURE §4 and §10

## Context
Units of measure (`UnitOfMeasure`), material categories (`MaterialCategory`) and product categories (`ProductCategory`) are PHP enums. They're
mirrored as TS literal unions and stored as strings on `materials`. The human wants this master data in the database so the company can manage it.
Other enums (`ProductStatus`, `StockStatus`, `StockMovementType`, `SalesPeriod`) drive behaviour: who can be sold, how stock status is computed, how
each movement type is written, and period date ranges.

## Decision
- **Tables** `units`, `material_categories` and `product_categories`:
  - columns: `id`, an immutable unique `code`, an editable `name`, `position`, `archived_at`; units also have `dimension`, `factor_to_base` and
    `is_system`
  - referenced by FKs with `restrictOnDelete`
  - archive instead of delete when in use
  - writes through Actions; policies on every route
- **Units carry a typed `UnitDimension`** (PHP enum: length, area, mass, volume, count, package) and a decimal-string `factor_to_base`
  (`numeric(24,12)`, `BigDecimal` only). Seeded units keep a fixed dimension and factor. **No conversion maths in Phase 3:** a BOM quantity stays in
  the material's own unit (costing skill unchanged). The columns prepare a later `UnitConverter` (scale 12, HalfUp, then the existing rules) that
  will need its own ADR.
- **A material's unit can't change while a non-deleted product's BOM uses it** (otherwise BOM quantities silently change meaning).
  *Provisional (Q7):* the human hasn't confirmed this yet; S-21 must not start until they do.
- **Who edits:** any signed-in, verified user (policies in place, no roles story).
- **Behavioural values stay PHP enums**: `ProductStatus`, `StockStatus`, `StockMovementType`, `SalesPeriod` (+ the new `UnitDimension`).
- **Currency and locale stay in config** (installation settings, not master data) unless the human chooses a Company settings page.
- **Defaults ship in migrations** (frozen snapshot, `insertOrIgnore` by `code`), because the FKs are `NOT NULL` and every environment and test needs
  them. Seeders are for demo data only.
- **Delivery to the UI:** one shared once-prop `reference` (`Inertia::shareOnce(...)->as('reference:v'.$version)`), resent only when an Action
  bumps the version. Per-page option props are removed. The URL filters keep codes (`?category=fabric`).

## Consequences
- ✅ Companies manage their own units and categories in Settings; no deploy needed to add "Elastic" or "inch".
- ✅ Reference lists reach the browser once per session, not once per page.
- ⚠️ Contract change: `MaterialCategory`, `UnitOfMeasure` and `ProductCategory` TS unions become `string`; forms submit `*_id`; `Material` gains
  `category_id` and `unit_id`. This is a named exception to ADR-0006's "props don't change" (S-21/S-22, one vertical commit).
- ⚠️ A data migration converts existing `materials.category` / `unit` strings into FKs (tested on SQLite and PostgreSQL, reversible).
- ⚠️ The costing skill and ARCHITECTURE must be updated when this is accepted.
