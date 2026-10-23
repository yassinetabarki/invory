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
| Stock movement | A history entry of a material: `created`, `restock`, `correction`, `price_change`, `cutting`, `cutting_return` ("Back from cutting") | `StockMovement` / `StockMovementType` enum |
| Order | A client's order: client name, order date, optional note, and one or more order lines. Recording, editing or deleting an order **never changes material stock or sales** (S-48); only "Start cutting" and "Move back to To start" on a line do (S-53). An order can't be deleted while any unit is in cutting or ready to ship (S-49) | `Order` / `orders` |
| Order line | One product of an order with its whole-unit quantity (1 … 1 000 000); one line per product per order, at most 50 per order | `OrderLine` / `order_lines` |
| Stage counts | How many units of an order line are To start, In cutting, Ready to ship and Shipped; they always add up to the line's quantity. To start = quantity − the other three (S-49) | `in_cutting`, `ready_to_ship`, `shipped` (`to_start` derived) |
| Open order | An order with at least one line not fully shipped (`shipped < quantity`). Once every line is fully shipped the order is "Shipped". One rule drives the Orders list's "Open orders" filter, its count and its totals (S-50) | `Order::open()` / `Order::shipped()` scopes, `OrderStatusFilter` enum |
| Cut | One "Start cutting" of N units of an order line, remembering per material the exact amount per unit and the amount taken. A move back to To start returns from the latest cut first (S-53) | `OrderLineCut` / `order_line_cuts` |
| Order line size | One size of an order line: the units ordered in that size and its stage counts (In cutting, Ready to ship, Shipped; To start derived). Only sizes ordered have one. The line's quantity and stage counts are the sums of its sizes. A line recorded before sizes holds one "One size" row (S-60, S-61, ADR-0016) | `OrderLineSize` / `order_line_sizes`, `OrderLineSizeMode` |
| Size list | The fixed sizes of a product category, in order, with the name the screens show: "S, M, L, XL" (Apparel), "36 to 46" (Footwear), "One size" (every other category). Installation-wide; no screen edits them (S-58, ADR-0015) | `SizeList` / `size_lists` |
| Size | One size of a size list ("S", "36", "One size"). A product has every size of its category; later stories count orders, production and finished stock by size id | `Size` / `sizes` |
| Workshop | The business account. Every user belongs to one; every material, product, composition, stock movement, sale, order, order line, photo, unit and category belongs to one | `Workshop` / `workshops`, `workshop_id` |

## Workshops (added 2026-10-14, D-9, ADR-0013)

- **R1** Every account belongs to exactly one workshop. Every material, product, composition line, stock movement, sale, order, order line,
  photo, unit and category belongs to exactly one workshop.
- **R2** You see and change only your workshop's records. Another workshop's record behaves as if it doesn't exist: **404, never 403**.
- **R3** SKUs, and unit and category names, are unique per workshop (not per installation).
- **R4** A composition uses only materials of its product's workshop; a sale only products of its workshop.
- **R5** Every figure (unit cost, can produce, bottleneck, inventory value, sales totals, dashboard) is computed over one workshop's
  records. The costing formulas don't change.
- **R6** Signing up creates a new, empty workshop with the new account as its only member.

The formulas below always run over one workshop's records; they don't change.

## Units and categories

Units and categories are **database tables** managed in Settings by any member of the workshop. Each workshop has its own lists, starting
from the defaults below (S-45; ADR-0008, S-21). Each row has an immutable `code`
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
- `StockMovementType`: `created, restock, correction, price_change, cutting, cutting_return` (labelled "Back from cutting").
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
inventory_value       = Σ stock_value(m) over the workshop's non-deleted materials
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
revenue               = Σ sale_total(S) over the workshop's sales in the period
units_sold            = Σ S.quantity over the workshop's sales in the period
sales_count           = number of the workshop's sales in the period
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
"Can't produce" counts the workshop's products (any status) whose `max_producible = 0`; products with an empty BOM (`null`) are not counted.

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

### Cutting and returning stock (added 2026-10-16, D-10/D-11, ADR-0014; replaces Q9)

```
start_cutting(line, N):                                   N whole, 1 ≤ N ≤ to_start(line)
    requires: the product is not deleted                  (archived and draft products can be cut; a deleted product's units
                                                          still move on and back)
    requires: BOM empty  or  N ≤ max_producible(P)        on the stock saved at that moment
    required_exact(m)  = Σ effective_qty(L) over m's lines, unrounded (at most 8 decimals)
    needed(m, N)       = round4( required_exact(m) × N )             HalfUp, once per material per cut
    short_by(m, N)     = round4( N × required_exact(m) − stock(m) )  only for materials where that is > 0
    stock'(m)          = stock(m) − needed(m, N)                     never below 0
    remembers, per material: per_unit = required_exact(m), taken = needed(m, N), the material's unit
    history: one `cutting` entry per material (quantity = needed), dated today; none for an empty BOM

back_to_start(line, k):                                   k whole, 1 ≤ k ≤ in_cutting(line)
    cuts of the line, latest cut first; from each cut c take k_c = min(k still to return, c.units − c.units_returned)
    held(c, m, r)      = round4( per_unit(c, m) × r )                r = outstanding units of c
    returned(c, m)     = held(c, m, r) − held(c, m, r − k_c)         a cut returned in full gives back exactly `taken`
    stock'(m)          = stock(m) + Σ_c returned(c, m)
    nothing back for a material deleted since, or whose unit changed since the cut
    a deleted product's units return their materials like any other
    history: one `cutting_return` entry per material that gets something back, dated today
```
- The current composition is never read for a return. Units no cut covers return nothing.
- "You can cut at most N now: <bottleneck> runs short. Restock it first, or cut N or fewer." uses N = `max_producible(P)` and names
  `bottleneck(P)`, also when several materials are short (every short material still shows its "Short by"). When
  `max_producible(P) = 0`, the message reads "You can't cut any now: restock <bottleneck> first."
- Never touch stock: Mark ready, Ship, their Move back, editing or deleting an order, recording a sale.
- Deleting an order is refused while any unit is in cutting or ready to ship ("<client> can't be deleted: N units are in cutting or
  ready to ship. Move them back to To start first."), so no material taken out of stock is lost by deleting. An order whose units are
  only to start or shipped deletes as before and returns nothing.
- Open orders never set stock aside: "Can produce" uses the stock on hand only.

Worked example (demo stock, Denim Jacket): cutting 30 takes 59.4 m Denim 12oz Indigo, 240 pc Metal shank button 17mm, 3,780 m
Polyester thread and 30 pc Woven brand label; the denim goes from 250 to 190.6 m, "Can produce now" from 112 to 82 (bottleneck the
button, ⌊660 / 8⌋), and the inventory value from TND 10,544.300 to 9,936.680. Moving 5 back returns 9.9 m, 40 pc, 630 m and 5 pc (denim
200.5 m, can produce 87); if the jacket needs 10 buttons by then, still 40 pc. After cutting 30 at 8 buttons and then 10 at 10
buttons, moving 5 back returns 50 pc (the latest cut first). With the button at 100 pc, cutting 20 is refused (needs
160 pc, short by 60 pc, at most 12); cutting 12 leaves 4 pc. With the button at 7 pc, at most 0.

### Orders list: open orders and their totals (added 2026-10-21, S-50)

```
open(O)               = some line L of O has L.shipped < L.quantity        (otherwise O is "Shipped")
order stage(O)        = Σ stage(L) over O's lines, for To start, In cutting, Ready to ship and Shipped      (whole units)
open_orders           = number of the workshop's non-deleted orders with open(O)
to_start_total        = Σ to_start(L)      over every line L of every open order of the workshop
in_cutting_total      = Σ in_cutting(L)    over every line L of every open order of the workshop
ready_to_ship_total   = Σ ready_to_ship(L) over every line L of every open order of the workshop
```
- All whole numbers (integer SQL sums, cast to `int`; no BigDecimal). Deleted orders never count; another workshop's never do (R5).
- The list's search and Status filter ("Open orders" by default, "Shipped", "All orders") never change the four totals.
- An order's status on the list: "Not started" while no unit of any line has left To start, "Shipped" once every line is fully
  shipped (exactly when `open(O)` is false), "In progress" otherwise.

Worked example: Boutique Lina (Denim Jacket 50: To start 20, In cutting 20, Ready to ship 10; Denim Jeans 5-pocket 30 to start),
Concept Store Nour (Classic White T-shirt 120 to start) and Hotel Dar Said (Leather Tote 12, all shipped) → Open orders 2, To start
170, In cutting 20, Ready to ship 10. Marking 5 of Boutique Lina's jackets ready → In cutting 15, Ready to ship 15.

### Sizes (added 2026-10-21, S-58, ADR-0015)

```
size_list(category)   = by the category's immutable code: apparel → s_to_xl "S, M, L, XL" (S, M, L, XL)
                                                           footwear → eu_36_to_46 "36 to 46" (36, 37 … 46)
                                                           every other code, and every category added in Settings → one_size "One size"
sizes(P)              = sizes(size_list(P.category))       in position order; nothing is stored per product
```
- Renaming, archiving or restoring a category never changes its size list. "One size" is a real size row, not a null.
- Sizes change **no figure**: every size of a product uses the same composition, so unit cost, "Can produce", the bottleneck, next
  limit, margin, sales and every dashboard figure are as before. A sale has no size.
- **Lock (AC-4):** a product on any order that isn't deleted (fully shipped ones included) can't move to a category with another size
  list. "The same sizes" means the same list: Bag → Accessory, or → a category added in Settings, is always allowed; a product on no
  order can move anywhere. Add product has no lock. The refusal sits on `category`:
  "<product> is on orders, so its category can't change to one with other sizes." + "Keep a category with sizes <list name>." (a list
  with several sizes) or "Keep a "One size" category." (a one-size list).
- Screens: the product page shows "Sizes " + name ("Sizes S, M, L, XL"), or the name alone for one size ("One size"); "Can produce now"
  adds ", any mix of sizes" after "from current stock" when there is more than one size. The product form shows "Sizes: " + name, or
  "One size", with the hint "Sizes come with the category.". The server names the list; the frontend never works out a range.

### Orders by size (added 2026-10-22, S-60, S-61, ADR-0016)

```
quantity(L)           = Σ quantity(z)       over the size rows z of line L        (whole units; only sizes ordered have a row)
stage(L)              = Σ stage(z)          for In cutting, Ready to ship and Shipped
to_start(z)           = quantity(z) − in_cutting(z) − ready_to_ship(z) − shipped(z)    (as on the line; never stored)
size_mode(L)          = by_size        when L's size list has several sizes
                      = one_size       when L's product is "One size"
                      = before_sizes   when L counts as "One size" but its product now has several sizes (recorded before sizes)
```
- Every line counts by a size list, stored on the line: a new line takes its product's; lines recorded before sizes keep "One size"
  for good (one row holding their quantity and stages). `one_size` and `before_sizes` lines keep the single "Quantity" field, the
  single "How many units?" field, and today's messages.
- **Saving an order** (`by_size`): each size takes a whole number 0 … 1 000 000 or nothing (nothing and 0 mean no row): "Enter a
  whole number from 0 to 1,000,000."; at least one unit on the line: "Enter at least one unit of <product>."; a line's total at most
  1 000 000: "A line can hold up to 1,000,000 units.". Only sizes of the line's list. A size keeps at least its started units: "<size>
  can't be less than the N units already in cutting, ready to ship or shipped.". A started line still can't be removed. When the
  product's sizes no longer match what was submitted (a size not of the line's list, or the product moved to other sizes meanwhile):
  "The sizes of <product> have changed. Enter its quantities again." and nothing is saved; the form then shows the product's sizes.
- **Moving units** (`by_size`): a number per size (nothing or 0 moves nothing in that size); every size empty → "Enter at least one
  unit to move."; not a whole number → "Enter a whole number of units."; each size at most what its stage holds: "You can move at most
  N units of <size>.". One size over its limit refuses the whole move: every size moves, or none. A size that isn't on the line:
  "The sizes ordered for <product> have changed. Enter the units again." (for the whole window) and nothing moves. The window
  checks each size's limit only on the server: the materials preview of Start cutting and Move back to To start is asked for the
  total, but its "at most N units" for the total never replaces the per-size message.
- **How a size is named in text** (S-60/S-61 fix round, human decision 2026-10-22): a letter size by its label ("S 10 · M 20",
  "…at most 20 units of M.", "M can't be less than…"); a size whose label is a number with a colon and the word "size" ("36: 4 ·
  37: 8", "…at most 20 units of size 36.", "Size 36 can't be less than…"). "A number" = the label is digits only. Applies to the
  sizes under a line heading, the Orders list, a material's history and the per-size messages; the size fields and table rows show
  the bare label.
- **Cutting is per line, not per size.** Start cutting takes the materials of the total, exactly as one cut of that many units
  (the formulas of "Cutting and returning stock" with N = Σ units over the sizes); the short-stock check counts every size together.
  Move back to To start returns from the line's cuts for the total, latest cut first, whatever sizes those cuts held. Every size uses
  the same composition.
- The history entries of a move of a `by_size` line remember the sizes moved (size, label, units, in size order) and read
  "14 × Denim Jacket (S 4 · M 10)"; other entries have none.
- Sizes change no figure: costing, "Can produce", the bottleneck, sales and the dashboard are as before. The Orders list's totals,
  filter and statuses read the line, so they add every size.

Worked example: Boutique Lina's Denim Jacket S 10 · M 20 · L 15 · XL 5 (line 50). Start cutting S 4 · M 10 → S 6 · 4, M 10 · 10, L 15 ·
0, XL 5 · 0, line To start 36 · In cutting 14; it takes 27.72 m Denim 12oz Indigo, 112 pc Metal shank button 17mm, 1,764 m Polyester
thread and 14 pc Woven brand label. With the button at 100 pc, S 10 · M 10 is refused (at most 12). Moving M 5 back returns 9.9 m,
40 pc, 630 m and 5 pc from the latest cut; M then reads To start 15 · In cutting 5.

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
| Cut a product with an empty BOM | allowed; nothing taken, no history entry; moving those units back returns nothing |
| Composition changed between a cut and its return | the cut's remembered amounts come back, not the new composition's |
| Material deleted, or its unit changed, between a cut and its return | nothing back for it ("Deleted, not returned" / "Unit changed, not returned"); the other materials come back |
| Delete an order with units in cutting or ready to ship | refused; nothing deleted (also when someone starts cutting while Delete is confirmed) |
| Two people cut from the same stock at once | the second check sees the stock left by the first (row locks, ADR-0014) |

## Validation rules (inputs)

- Material: `unit_cost` ≥ 0 (max 4 decimals), `quantity_on_hand` ≥ 0 (max 4 decimals), `reorder_level` nullable ≥ 0.
- BOM line: `quantity` > 0 (max 4 decimals); `waste_percent` 0 ≤ x < 100 (max 2 decimals); `material_id` exists in the product's workshop, not soft-deleted, `distinct` within the product.
- Product: `labor_cost` required, ≥ 0 (max 4 decimals). `overhead_cost` **optional**, ≥ 0 (max 4 decimals): blank or missing is saved as `0`
  and counts as 0 in the unit cost; a typed value follows the same rules (decided 2026-10-14, D-8, S-43). An empty BOM is allowed (e.g. a draft).
  `selling_price` nullable ≥ 0 (max 4 decimals).
- A material referenced by any non-deleted product **cannot be deleted** (`MaterialInUseException`).
- Sale: `product_id` exists in the workshop, not soft-deleted, status `active`; `quantity` integer 1 … 1 000 000; `unit_price` ≥ 0 (max 4 decimals);
  `sold_on` a date ≤ today; `note` nullable, ≤ 255 chars.
- Restock: `quantity` > 0 (max 4 decimals); `unit_price` ≥ 0 (max 4 decimals); `received_on` a date ≤ today; `reference` nullable, ≤ 64 chars;
  `update_unit_cost` boolean (default true).
- Move units (S-49, S-53): `units` integer ≥ 1 ("Enter a whole number of units, at least 1.") and ≤ the units the stage holds now
  ("You can move at most N units."); for Start cutting also ≤ `max_producible(P)` when the BOM isn't empty, and the product not deleted.
- Photo (material or product): optional file, JPG / PNG / WebP only (no SVG), max 4 MB (4096 KB); `remove_photo` boolean. One photo per item.
- SKUs are unique per workshop among non-deleted items; unit and category names per workshop, ignoring capitals (S-45). An id of another
  workshop is refused exactly like an id that doesn't exist.
- Deleting an account deletes only the person; the workshop's records stay.

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
- Added 2026-10-16 (S-53, ADR-0014): `CostingResult::$requirements` (`MaterialRequirement`, F-41: the summed need per material, rounded
  and exact), `App\Domain\Stock\CuttingCalculator` (take and return), `App\Actions\Orders\MoveOrderLineUnits` with
  `TakeMaterialsForCut` and `ReturnMaterialsFromCuts`, `App\Services\Orders\CuttingPreview` (the move window's materials table).
- Added 2026-10-21 (S-50): `Order::open()` / `Order::shipped()` scopes and `App\Enums\OrderStatusFilter` (the Orders list filter),
  `App\Services\Orders\OrderBook::openTotals()` (the four list totals, one integer SQL sum over the open orders).
- Added 2026-10-21 (S-58, ADR-0015): `App\Models\SizeList` / `Size`, `ProductCategory::sizeList()`, `App\Services\Products\SizeLists`
  (the lists, once per request), `SizeListResource`, `App\Services\Products\ProductSizeLock::refusal()` (the AC-4 lock).
- Added 2026-10-22 (S-60, S-61, ADR-0016): `App\Models\OrderLineSize`, `App\Models\Concerns\HasStageCounts` (To start and started for
  lines and sizes), `App\Enums\OrderLineSizeMode`, `App\Services\Orders\OrderLineSizing` (how a line counts), `SyncOrderLines` and
  `MoveOrderLineUnits` (the only writers of size rows and the line's sums).
