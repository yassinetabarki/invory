---
name: invory-costing-domain
description: Invory business rules for fashion bill-of-materials (BOM) costing and production capacity — glossary, units, exact formulas for effective quantity, waste, line cost, unit cost, max producible quantity and bottleneck, rounding/precision rules, edge cases, and a worked example to test against. Use whenever implementing, testing, displaying or reviewing anything about materials, products, BOM/composition, cost or "how many can we produce".
---

# Invory costing domain

This file is the **single source of truth** for Invory's business maths. Code, tests and UI copy must match it.
If a rule here is wrong or incomplete, stop and ask the tech-lead to update it (with an ADR if it changes behaviour). Do not "fix" it locally in code.

## Glossary (use these words in code, UI and tests)

| Term | Meaning | Code name |
|---|---|---|
| Material | A primary item bought and stocked (fabric, button, thread, sole…) | `Material` / `materials` |
| Product | A final fashion item we make (jacket, sneaker, tote…) | `Product` / `products` |
| Composition / BOM | The list of materials (with quantities) that make **one unit** of a product | `ProductComponent` / `product_components` |
| Component line | One row of the BOM: material + quantity per unit + waste % | `BomLine` (domain VO) |
| Waste % | Expected loss on top of the net quantity (cutting, shrinkage, defects) | `waste_percent` |
| Effective quantity | Quantity actually consumed per unit, waste included | `effective_quantity` |
| Unit cost | Total cost to make one unit of a product | `unit_cost` |
| Capacity / can produce | Max whole units we can make **now** from stock on hand | `max_producible` |
| Bottleneck | The material that limits capacity | `bottleneck` |
| Stock status | `out` if `quantity_on_hand = 0`; else `low` if `reorder_level` is set and `quantity_on_hand <= reorder_level`; else `in_stock`. Computed server-side. "Needs restock" = `low` or `out` | `stock_status` / `StockStatus` enum |

## Units and categories

- `UnitOfMeasure`: `m, cm, yd, sqm, sqft, kg, g, l, ml, pc, pair, roll, cone`.
  A BOM quantity is **always expressed in the material's own unit**. There are no conversions in the MVP.
- `MaterialCategory`: `fabric, lining, interlining, leather, thread, trim, fastener, label, sole, hardware, packaging, other`.
- `ProductCategory`: `apparel, footwear, bag, accessory, jewelry, other`.
- `ProductStatus`: `draft, active, archived`.
- Each PHP enum exposes `label(): string` and `static options(): array<array{value:string,label:string}>`. The frontend gets options as props and never hard-codes labels.

## Formulas

For a product P with BOM lines L₁…Lₙ:

```
effective_qty(L)      = L.quantity × (1 + L.waste_percent / 100)
line_cost(L)          = effective_qty(L) × L.material.unit_cost
material_cost(P)      = Σ line_cost(L)
unit_cost(P)          = material_cost(P) + P.labor_cost + P.overhead_cost

required(m)           = Σ effective_qty(L) for every line L whose material is m     ← aggregate per material!
units_possible(m)     = ⌊ m.quantity_on_hand / required(m) ⌋
max_producible(P)     = min over materials m of units_possible(m)
bottleneck(P)         = the material m with the smallest units_possible(m)
                        (tie → lowest material name in byte order (`strcmp`), then lowest id)

stock_value(m)        = m.quantity_on_hand × m.unit_cost
inventory_value       = Σ stock_value(m) over non-deleted materials
```

## Precision and rounding (non-negotiable, see ADR-0002)

1. **Never use floats** for money or quantities: no `float` casts, no `(float)`, no `+`/`*` on numeric strings in PHP, and no `Number(x) * y` in TypeScript.
2. **PHP**: use `Brick\Math\BigDecimal` (already a Laravel dependency). Build values from strings: `BigDecimal::of('1.8000')`.
3. **Storage scales**: `unit_cost`, `labor_cost`, `overhead_cost` → `decimal(12,4)`; quantities → `decimal(14,4)`; `waste_percent` → `decimal(5,2)`.
   Eloquent casts: `'decimal:4'` / `'decimal:2'`. These return **strings**.
4. **Intermediate maths is exact** (BigDecimal multiply/add keep full scale). Do not round mid-calculation.
5. **Serialization**: every money/quantity output is a **string with scale 4**, rounded half-up (`RoundingMode::HalfUp`; brick/math 1.x enum cases are PascalCase), for example `"35.7540"`.
   Percentages use scale 2. `max_producible` is an **integer** (or `null`).
6. **Capacity** uses `dividedBy($required, 0, RoundingMode::Down)`, which floors positive values. Never round capacity up.
7. **Display only** (frontend): format with `Intl.NumberFormat(locale, { style: 'currency', currency })` from shared props.
   Money is shown with the **currency's own ISO minor-unit digits** (TND 3, EUR 2, JPY 0; decided 2026-10-08, F-10).
   Per-unit material costs below one minor unit (TND 0.001, EUR 0.01) show 4 decimals (`<Money precise>`).
   Display rounding never feeds back into calculations: the server values stay scale-4 strings.

## Edge cases (each one needs a test)

| Situation | Result |
|---|---|
| BOM is empty | `material_cost = "0.0000"`, `unit_cost = labor + overhead`, `max_producible = null`, `bottleneck = null`. UI: "No composition defined" |
| A required material has `quantity_on_hand = 0` | `max_producible = 0`, bottleneck = that material |
| Stock smaller than one unit's need | `max_producible = 0` |
| Same material on two lines (shouldn't happen, DB-unique) | requirements **summed** before dividing |
| `waste_percent = 0` | `effective_qty = quantity` |
| Very small quantities (e.g. thread 0.0001 m) | no precision loss; exact BigDecimal maths |
| Very large capacity (e.g. 1 000 000 / 0.0001) | integer result fits in PHP int and JS safe integer range |
| Ties for bottleneck | lowest name, then lowest id (deterministic) |
| `unit_cost = 0` material | allowed, contributes 0 to cost but still limits capacity |
| Archived / draft products | still calculated; status is display/filter only |

## Validation rules (inputs)

- Material: `unit_cost` ≥ 0 (max 4 decimals), `quantity_on_hand` ≥ 0 (max 4 decimals), `reorder_level` nullable ≥ 0.
- BOM line: `quantity` > 0 (max 4 decimals); `waste_percent` 0 ≤ x < 100 (max 2 decimals); `material_id` exists, not soft-deleted, `distinct` within the product.
- Product: `labor_cost`, `overhead_cost` ≥ 0 (max 4 decimals). An empty BOM is allowed (e.g. a draft).
- A material referenced by any non-deleted product **cannot be deleted** (`MaterialInUseException`).

## Worked example — use as a golden test (backend unit test + frontend display test)

**Denim Jacket** (`labor_cost = 12.0000`, `overhead_cost = 3.5000`)

| Material | Unit | Unit cost | Qty / unit | Waste % | Effective qty | Line cost | Stock | Units possible |
|---|---|---|---|---|---|---|---|---|
| Denim 12oz Indigo | m | 8.5000 | 1.8000 | 10.00 | 1.9800 | 16.8300 | 250.0000 | ⌊250 / 1.98⌋ = **126** |
| Metal shank button 17mm | pc | 0.3500 | 8.0000 | 0.00 | 8.0000 | 2.8000 | 900.0000 | ⌊900 / 8⌋ = **112** |
| Polyester thread | m | 0.0040 | 120.0000 | 5.00 | 126.0000 | 0.5040 | 20000.0000 | ⌊20000 / 126⌋ = **158** |
| Woven brand label | pc | 0.1200 | 1.0000 | 0.00 | 1.0000 | 0.1200 | 400.0000 | **400** |

- `material_cost = 16.8300 + 2.8000 + 0.5040 + 0.1200 = "20.2540"`
- `unit_cost = 20.2540 + 12.0000 + 3.5000 = "35.7540"` → displayed as **TND 35.754** with the installation default TND (locale `en`, 3 decimals per rule 7),
  or **€35.75** with EUR
- `max_producible = min(126, 112, 158, 400) = 112`
- `bottleneck = Metal shank button 17mm`

Variant checks:
- Set button stock to `7.0000` → `max_producible = 0`, bottleneck = button.
- Remove all lines → `material_cost = "0.0000"`, `unit_cost = "15.5000"`, `max_producible = null`.

## Where it lives in code

- Pure maths: `app/Domain/Costing/` (`BomLine`, `LineCost`, `CostBreakdown`, `ProductionCapacity`, `CostingCalculator`). It must not import `Illuminate\*`.
- Eloquent adapter: `app/Services/Costing/ProductCostingService` maps a `Product` with `components.material` eager-loaded into `BomLine[]`.
- Serialization: `CostBreakdownResource` and the `cost` and `capacity` keys of `ProductResource` / `ProductListResource`.
- TS contract: `resources/js/types/domain.ts` (`CostBreakdown`, `Capacity`, `Decimal = string`).
- Display: `resources/js/lib/format.ts`, `components/shared/money.tsx`, `components/products/capacity-badge.tsx`.
