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
| Selling price | Optional price of one unit of a product; pre-fills "Record a sale" | `selling_price` |
| Margin | Selling price minus unit cost, as an amount and a % of the selling price | `margin` (`amount`, `percent`) |
| Sale | A recorded sale: product, whole-unit quantity, unit price, date, optional note. **Does not change material stock** | `Sale` / `sales` |
| Units covered | Units of a product that one material's stock covers on its own | `units_covered` |
| Next limit | The bottleneck and capacity a product would have if its current bottleneck were unlimited | `next_limit` |
| Stock movement | A history entry of a material: `created`, `restock`, `correction`, `price_change` | `StockMovement` / `StockMovementType` enum |

## Units and categories

Units and categories are **database tables** managed in Settings by any signed-in user (ADR-0008, S-21). Each row has an immutable `code`
(used in forms, filters and URLs), an editable `name`, a `position` and `archived_at`. Archived rows are hidden from pickers but still show on
existing records. A row can be deleted only when nothing references it.
- `units` (seeded defaults, `is_system`): `m, cm, yd, sqm, sqft, kg, g, l, ml, pc, pair, roll, cone`. Users may add, rename and archive units.
  Each unit has a `dimension` (`UnitDimension` enum: length, area, mass, volume, count, package) and a `factor_to_base` (decimal string,
  scale 12; `null` for `package`). Seeded units keep a fixed dimension and factor.
  A BOM quantity is **always expressed in the material's own unit**. There are **no conversions** in Phase 3. The factor exists for a later
  conversion story, which needs its own ADR and rules here.
- **A material's unit can't change while any non-deleted product's BOM uses it** (validation error on `unit`; decided 2026-10-09, Q7).
- `material_categories` (defaults): `fabric, lining, interlining, leather, thread, trim, fastener, label, sole, hardware, packaging, other`.
- `product_categories` (defaults): `apparel, footwear, bag, accessory, jewelry, other`.
- `ProductStatus`: `draft, active, archived`. Only `active` products can be sold (mockup copy "Only active products can be sold").
- `SalesPeriod`: `last_7_days, last_30_days, this_month, this_year` (default `last_30_days`).
- `StockMovementType`: `created, restock, correction, price_change`.
- Each PHP enum exposes `label(): string` and `static options(): array<array{value:string,label:string}>`. The frontend gets options as props
  (or from the shared `reference` prop) and never hard-codes labels.

## Formulas

For a product P with BOM lines L₁…Lₙ:

```
effective_qty(L)      = L.quantity × (1 + L.waste_percent / 100)
line_cost(L)          = effective_qty(L) × L.material.unit_cost
material_cost(P)      = Σ line_cost(L)
unit_cost(P)          = material_cost(P) + P.labor_cost + P.overhead_cost
                        (P.overhead_cost is never null: a blank overhead is stored as 0, S-43)

required(m)           = Σ effective_qty(L) for every line L whose material is m     ← aggregate per material!
units_possible(m)     = ⌊ m.quantity_on_hand / required(m) ⌋
max_producible(P)     = min over materials m of units_possible(m)
bottleneck(P)         = the material m with the smallest units_possible(m)
                        (tie → lowest material name in byte order (`strcmp`), then lowest id)

stock_value(m)        = m.quantity_on_hand × m.unit_cost
inventory_value       = Σ stock_value(m) over non-deleted materials
```

### Material usage and next limit (added 2026-10-08, decision 3)

For a material m and every non-deleted product P whose BOM uses m:

```
needed_per_unit(m, P) = required_P(m)                 (effective quantity, waste included, summed over P's lines of m)
units_covered(m, P)   = units_possible_P(m) = ⌊ m.quantity_on_hand / required_P(m) ⌋      (m alone, other materials ignored)
can_make_now(P)       = max_producible(P)             (the whole BOM)
limited_by(P)         = bottleneck(P);  is_bottleneck = (bottleneck(P) = m)
next_limit(P, m)      = only when bottleneck(P) = m: max_producible and bottleneck of P's BOM with every line of m removed
                        (same tie-break as bottleneck). null when m is P's only material.
```
`next_limit` reads "if you restock m, the next limit is X at N units". It can equal the current capacity (a tie at the bottleneck).
Worked example: the button limits the Denim Jacket to 112; without it the next limit is Denim 12oz Indigo at **126**.

### Selling price and margin (added 2026-10-08, decision 5)

```
margin(P)             = P.selling_price − unit_cost(P)                       scale 4, may be negative
margin_percent(P)     = margin(P) ÷ P.selling_price × 100                    scale 2, HalfUp   (gross margin: % of the selling price, not markup on cost)
```
`selling_price` is optional (`decimal(12,4)`, ≥ 0). `selling_price = null` → `margin = null`. `selling_price = 0` → `margin.amount = −unit_cost`, `margin.percent = null`.
Worked example: Denim Jacket at selling price `89.0000` → `margin.amount = "53.2460"`, `margin.percent = "59.83"`.

### Sales (added 2026-10-08, decision 1)

```
sale_total(S)         = S.quantity × S.unit_price                            scale 4 (quantity is a whole number ≥ 1)
revenue               = Σ sale_total(S) over the period
units_sold            = Σ S.quantity over the period
sales_count           = number of sales in the period
average_sale          = revenue ÷ sales_count                                scale 4, HalfUp; null when sales_count = 0
products_sold_count   = number of distinct products sold in the period
top products          = per product: units_sold and revenue over the period; ranked by the chosen metric descending
                        (tie → the other metric descending, then lowest product id); first 5
percent_of_top(p)     = metric(p) ÷ metric(first ranked) × 100              scale 2, HalfUp (drives the bar length; 100.00 for the first)
                        if metric(first ranked) = 0 (e.g. only zero-price sales ranked by revenue) → "0.00" for every product
```
Worked example (sales fixture of the UI-first plan, last 30 days): 48 sales, 296 units, revenue `"16850.0000"`, average sale `"351.0417"`;
top by units T-shirt 142 (`100.00`), Jeans 64 (`45.07`), Denim Jacket 38 (`26.76`), Belt 31 (`21.83`), Tote 12 (`8.45`).
Periods are inclusive calendar-day ranges ending today (app timezone): `last_7_days` = today − 6 … today, `last_30_days` = today − 29 … today,
`this_month` = 1st of this month … today, `this_year` = 1 January … today. A sale's date can't be in the future.
"Sold (30 d)" on products always uses `last_30_days`. Recording, editing or deleting a sale **never** changes material stock.
"Can't produce" counts products (any status) whose `max_producible = 0`; products with an empty BOM (`null`) are not counted.

### Restock and price history (added 2026-10-08, decision 4)

```
restock(m, q, price, update_unit_cost):
    m.quantity_on_hand' = m.quantity_on_hand + q                             q > 0 (max 4 decimals), price ≥ 0 (max 4 decimals)
    m.unit_cost'        = price  if update_unit_cost (default on)            latest price paid, NOT a weighted average
                        = m.unit_cost otherwise
    history: one `restock` movement (quantity q, unit price paid, reference); plus one `price_change` movement
             (unit_cost before → after) when the unit cost actually changed. Both are dated with the receipt date.
```
A manual edit of `quantity_on_hand` is logged as a `correction` (before → after; no reason is recorded, decided 2026-10-13, F-40); a manual edit of `unit_cost` as a `price_change`.
History is listed newest first (date, then entry order) with who did it.

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
| Overhead left blank on Add/Edit product | saved as `0.0000`; `unit_cost = material_cost + labor_cost`; capacity, bottleneck and next limit unaffected (decided 2026-10-14, D-8) |

## Validation rules (inputs)

- Material: `unit_cost` ≥ 0 (max 4 decimals), `quantity_on_hand` ≥ 0 (max 4 decimals), `reorder_level` nullable ≥ 0.
- BOM line: `quantity` > 0 (max 4 decimals); `waste_percent` 0 ≤ x < 100 (max 2 decimals); `material_id` exists, not soft-deleted, `distinct` within the product.
- Product: `labor_cost` required, ≥ 0 (max 4 decimals). `overhead_cost` **optional**, ≥ 0 (max 4 decimals): blank or missing is saved as `0`
  and counts as 0 in the unit cost; a typed value follows the same rules (decided 2026-10-14, D-8, S-43). An empty BOM is allowed (e.g. a draft).
  `selling_price` nullable ≥ 0 (max 4 decimals).
- A material referenced by any non-deleted product **cannot be deleted** (`MaterialInUseException`).
- Sale: `product_id` exists, not soft-deleted, status `active`; `quantity` integer 1 … 1 000 000; `unit_price` ≥ 0 (max 4 decimals);
  `sold_on` a date ≤ today; `note` nullable, ≤ 255 chars.
- Restock: `quantity` > 0 (max 4 decimals); `unit_price` ≥ 0 (max 4 decimals); `received_on` a date ≤ today; `reference` nullable, ≤ 64 chars;
  `update_unit_cost` boolean (default true).
- Photo (material or product): optional file, JPG / PNG / WebP only (no SVG), max 4 MB (4096 KB); `remove_photo` boolean. One photo per item.

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
- Overhead left empty (`0.0000`) → `unit_cost = "32.2540"`, at selling price `89.0000` `margin.amount = "56.7460"`, `margin.percent = "63.76"`;
  `max_producible = 112`, bottleneck = button (unchanged).

## Where it lives in code

- Pure maths: `app/Domain/Costing/` (`BomLine`, `LineCost`, `CostBreakdown`, `ProductionCapacity`, `CostingCalculator`). It must not import `Illuminate\*`.
- Eloquent adapter: `app/Services/Costing/ProductCostingService` maps a `Product` with `components.material` eager-loaded into `BomLine[]`.
- Serialization: `CostBreakdownResource` and the `cost` and `capacity` keys of `ProductResource` / `ProductListResource`.
- TS contract: `resources/js/types/domain.ts` (`CostBreakdown`, `Capacity`, `Decimal = string`).
- Display: `resources/js/lib/format.ts`, `components/shared/money.tsx`, `components/shared/capacity-badge.tsx`.
- Added 2026-10-08 (UI-first track): `App\Domain\Costing\MarginCalculator` (margin), `CostingCalculator::capacityWithout()` (next limit),
  `App\Domain\Sales\SalesCalculator` (sale total, period summary, top products, percent of top), `App\Enums\SalesPeriod::range()` (period dates).
  Eloquent adapters (Phase 3, 2026-10-13): `App\Services\Costing\MaterialUsageService` (usage and next limit of a material),
  `App\Services\Products\ProductCatalog` (dashboard "can't produce" figures), `App\Services\Sales\SalesReport` (sales log totals, period
  summary, top products; SQL sums only the integer `quantity`). The preview layer `App\Mock` is gone (ADR-0006, S-17).
