# ADR-0027 — Material use per colour and size: a quantity grid on composition lines

- **Status:** Proposed · 2026-11-06 (tech-lead; from the human's decisions of 2026-11-06 on INV-16). It becomes Accepted when the human
  approves it with INV-16's Technical work. Until then the costing skill is **not** changed: its amendment is staged in the appendix
  below and is applied, word for word, in the commit that accepts this ADR.
- **Amends:** ADR-0018 decision 7 and ADR-0019 "Consequences" ("colours change no figure"), ADR-0015 ("every size uses the same
  composition", in the skill's *Sizes* and *Orders by size* sections), ADR-0023 decision 2 (one per-unit amount per material per
  production cut).
- **Keeps:** ADR-0002 (decimal strings and `BigDecimal`, rounding once on output), ADR-0004 (pure costing domain), ADR-0013 (every record
  belongs to a workshop; another workshop's record is a 404), ADR-0014 (a return gives back exactly what was taken, `held(r) − held(r −
  k)`), ADR-0026 (orders don't cut; only Start batch takes materials).

## Context
Today a product has **one composition** for every colour and size: an L jacket and an M jacket use the same 1.8 m of denim, and an
Indigo jacket and a Black jacket draw on the same lines. INV-16 changes that. The human decided on 2026-11-06:

1. The quantity of a composition line varies by **colour and size together**: a full grid, one cell per colour × size.
2. The user types a **flat quantity** per cell (what an Indigo L needs), not a multiplier.
3. "Can produce per colour" means **colour-specific material stock**: "Denim 12oz Indigo" is its own material with its own quantity on
   hand, not one "Denim 12oz" stock divided by colour.

Since INV-14 (ADR-0026) only **Start batch** takes materials out of stock, and a batch is already one product in one colour and one
size (ADR-0023). So a per-variant composition has exactly one consumer of stock, and that consumer already knows the variant.

Most lines don't vary: thread, buttons and labels are the same for every variant, and most products have one colour. The live
database holds compositions, production cuts and batches that must keep their figures and must never be reset.

## Decision

### 1. A line is either "same for every variant" or "by colour and size"
- `product_components` gains `quantity_mode` (`same` · `by_variant`, enum `ComponentQuantityMode`, default `same`). A `same` line keeps
  `quantity` as today. A `by_variant` line has `quantity = null` (PostgreSQL CHECK: null exactly when `by_variant`) and its quantities in
  a new table.
- **`product_component_quantities`**: `workshop_id`, `product_component_id` (cascade), `product_id`, `colour_id`, `size_id` (restrict),
  `quantity` `decimal(14,4)` > 0, timestamps; unique (`product_component_id`, `colour_id`, `size_id`). PostgreSQL adds the composite key
  (`product_id`, `colour_id`) → `product_colours` (as ADR-0019 decision 3), so a cell for a colour the product doesn't have is refused by
  the database, and the workshop composite keys of ADR-0013.
- **A missing cell means "this variant doesn't use this material"**, not zero and not "fall back". This is how colour-specific stock is
  expressed: the *Denim 12oz Indigo* line has cells only under Indigo, the *Denim 12oz Black* line only under Black. A `by_variant` line
  has at least one cell.
- **Waste % stays one per line** and applies to every cell (not decided by the human; see the questions).
- One line per material per product stays (database-unique), so Indigo and Black denim are two lines because they are two materials.

### 2. Materials don't change
Colour-specific stock is the existing model used as it is: the user creates one material per stocked colour ("Denim 12oz Indigo",
"Denim 12oz Black"); the demo data already does. The material's `color` field stays free text and is **not** linked to the colour
list in INV-16. Nothing on a material says which product colour it serves; the grid says it.

### 3. Existing data: nothing moves
The migration only adds the column (every line `same`) and the empty table. No quantity is copied, so **every existing product keeps
exactly its unit cost, capacity, bottleneck and next limit**, and a product whose lines are all `same` shows what it shows today.

### 4. The costing domain runs per variant, through the calculator it already has
- The adapter (`ProductCostingService`) resolves a **variant composition** for each colour × size: every `same` line, plus every
  `by_variant` line with a cell for that variant, as a `BomLine` with that quantity. `CostingCalculator::calculate()` and
  `capacityWithout()` run **unchanged** on it. The golden test of the skill stays green as it is.
- A new pure class in `App\Domain\Costing` (working name `VariantCosting`) takes the per-variant results and returns the per-colour and
  per-product summaries of the appendix (ranges, guaranteed figures, the binding variant and its bottleneck). No `Illuminate\*`.
- **Fast path:** a product with no `by_variant` line is calculated once; every variant reads that one result. This keeps the products
  list and the dashboard (which cost every product in PHP until S-23's `product_metrics`, F-38) at today's cost for the products that
  don't vary.

### 5. Capacity is exact per variant; colour and product figures are "guaranteed" and "best"
Per variant, capacity and bottleneck are today's formulas on the variant composition. Above a variant there is no single exact
figure: how many Indigo jackets you can make depends on the size mix, and the colours share the buttons and thread. The appendix
defines two figures, both whole and both exact:

- **guaranteed** = the minimum over the variants (of the colour, or of the product). Any mix of those variants up to that many units can
  be made. Proof: every material is a linear limit `Σ n_v × r(m, v) ≤ stock(m)`; if `N ≤ ⌊stock(m) / r(m, v)⌋` for every v, then
  `Σ n_v × r(m, v) ≤ N × max_v r(m, v) ≤ stock(m)`.
- **best** = the maximum over the same variants: the most units of one variant made on its own.

When every line is `same`, guaranteed = best = today's `max_producible(P)`, and today's "any mix of sizes" sentence stays true. The
screens show guaranteed as the headline and best beside it (INV-16 AC-4); the server sends both.

Two readings settled in INV-16's Technical work (2026-11-06):
- **Where best is reached:** the first variant, in colour list order then size order, with the largest capacity. Its colour is the
  one named ("Best case: 112 units, in Indigo") and its bottleneck the material named ("limited there by …").
- **A variant whose composition is empty** (no `same` line and no cell of its colour and size) has `max_producible = null`, like an
  empty BOM today: it counts for neither guaranteed nor best, and a colour whose every size is null reads "No composition".

### 6. Start batch checks the grid it is given, exactly
The plan sums per variant: `needed(m) = round4( Σ_v n_v × required_exact(m, v) )`, rounded once per material per start (as today).
The start is allowed when `Σ_v n_v × required_exact(m, v) ≤ stock(m)` **unrounded**, for every material. With every line `same` this is
exactly today's rule `N ≤ max_producible(P)`. "Short by" stays per material. The sentence "You can cut at most N now" has no single N
for a mixed grid and needs new wording (question below).

### 7. Production cuts remember the amount per unit per variant
ADR-0023 decision 2 stays (one cut per Start batch, shared by its batches; one rounding per material), but a single per-unit amount per
material no longer fits a cut whose variants differ.
- New `production_cut_variants` (`workshop_id`, `production_cut_id`, `colour_id`, `size_id`, `units`, `units_returned`) and
  `production_cut_variant_materials` (`workshop_id`, `production_cut_variant_id`, `material_id`, `quantity_per_unit` `decimal(20,8)`).
  `production_cut_materials.quantity_taken` stays the one rounded take per material, with the material's unit at the time;
  its `quantity_per_unit` becomes nullable and is null on every cut that has variant rows.
- Return (ADR-0014 generalised): `held(c, m) = round4( Σ_v r_v × per_unit(c, v, m) )` with `r_v` the units of variant v not returned yet;
  Undo batch of a batch in variant v with k units returns `held(c, m)` before − after `r_v − k`. Undoing every batch of a start gives
  back exactly `taken`. A split batch keeps its colour and size, so it keeps its variant.
- **Existing cuts are not backfilled** (changed in INV-16's Technical work, 2026-11-06). A cut with no variant rows (every cut made
  before INV-16) is read as **one group**: outstanding `units − units_returned`, one `quantity_per_unit` per material, which is exactly
  today's `held(r) − held(r − k)`. Since `Σ r_v × p = p × r`, this is the same figure a backfill would give, without rewriting live rows.
  Every cut made from INV-16 on writes variant rows, also when nothing varies.

### 8. Colour, size and material changes keep the grid consistent
Every writer of a product's composition, colours or category takes the product's row lock first (ADR-0017 decision 4).
- **A colour taken off a product** deletes that colour's cells in the same save (`SyncProductColours`). A colour added has no cells: it
  uses only the `same` lines until cells are typed.
- **A category change to another size list** is refused while any line is `by_variant` (it would orphan every cell). This is the safe
  default; whether to refuse or clear, and the wording, is a product decision.
- A material in any line, `same` or `by_variant`, is in use (`MaterialInUseException`, the unit-change lock), as today.

### 9. Bounds
A product can have every colour of its workshop (no limit today) and up to 11 sizes, so one `by_variant` line can hold hundreds of
cells. The save is validated as one nested payload and costed per variant. **The cap is 2,000 cells per product** (filled cells over
all its `by_variant` lines; the human's figure, 2026-11-06), checked on the save. Variants with the same composition are costed once,
so a product never costs more than its distinct compositions; a load test of the products list and the dashboard at the cap guards it.

### 10. Contract
`ProductResource` gains per-colour and per-variant figures beside today's `cost` and `capacity`; `domain.ts` changes in the same
commit (frozen in INV-16's Technical work). Today's `cost` / `capacity` keys keep their meaning for a product with no `by_variant`
line, so screens that don't show colours keep working.

## Consequences
- A one-colour product, or a product with only `same` lines, is untouched: same figures, same screens, same payload values.
- The composition editor (`BomEditor`) gets the biggest change: a line switches to "by colour and size" and shows a grid. This is
  CRITICAL frontend work and the main cost of INV-16.
- Unit cost and margin become per variant. Sales have no colour or size, so no sale gets a cost; the product's selling price is one
  figure, compared with a range of costs.
- "Can produce", the bottleneck, next limit, material usage and the dashboard's "Can't produce" each need a per-colour or per-product
  reading that the screens choose (appendix gives the figures).
- `CuttingCalculator::take()` takes a list of (units, requirements) per variant instead of one (requirements, N); `giveBack()` the
  per-variant outstanding units. Order cuts (`order_line_cuts`, history only since ADR-0026) are not touched.
- The migration is additive (plus `product_components.quantity` and `production_cut_materials.quantity_per_unit` becoming nullable);
  no live row is rewritten. A test undoes a batch of a cut made before INV-16 and gets the same figures as before.

## Alternatives rejected
- **A multiplier per size, or one override per size shared by every colour:** rejected by the human (2026-11-06).
- **One stock per material divided by colour:** rejected by the human; also makes "stock on hand" two-dimensional everywhere.
- **Every line must fill every cell:** thread and labels would need 44–220 identical cells per product; one-colour products would gain
  work for nothing. A line declares `same` instead.
- **A whole composition per colour:** shared lines would be typed once per colour and drift apart; sizes would still need a grid.
- **Storing the grid as one unstructured blob on the line.** Nothing points back at a real colour or size row, so removing a colour or
  a size list change could leave silent orphans, and the database couldn't check the quantities.
- **A `colour_id` on materials, matched to product colours automatically:** not asked for; free-text colours ("Antique brass") don't
  map to the list. Can be added later without changing this model.
- **A `product_variants` table:** rejected again for the reason of ADR-0019 (rows kept in sync with colours and categories for no
  behaviour); cells point at colour and size directly.

---

## Appendix — staged amendment to `.claude/skills/invory-costing-domain/SKILL.md`

Applied when this ADR is accepted. Each block says where it goes.

### A. Glossary (new rows, after "Component line")

| Term | Meaning | Code name |
|---|---|---|
| Quantity mode | A component line is **Same for every variant** (one quantity, every line before INV-16) or **By colour and size** (a quantity per colour × size of the product; a variant with no quantity doesn't use the material) | `quantity_mode`, `ComponentQuantityMode` (`same`, `by_variant`) |
| Quantity cell | The quantity of a by-colour-and-size line for one colour and one size, in the material's unit, > 0 | `ProductComponentQuantity` / `product_component_quantities` |
| Variant composition | The lines one variant uses: every `same` line, and every `by_variant` line with a cell for it, at that cell's quantity | resolved in `ProductCostingService` |
| Guaranteed / best | Of a colour or a product: the smallest / largest capacity of its variants. Any mix up to *guaranteed* units can be made; *best* is one variant made on its own | `guaranteed`, `best` |

### B. Formulas (replaces the block under "Formulas")

```
For a product P, a variant v = (colour c, size z) of colours(P) × sizes(P), and a line L of P (material m, waste w):

q(L, v)               = L.quantity                     when L is `same`
                      = cell(L, c, z)                  when L is `by_variant` and has that cell
                      = none                           otherwise (v doesn't use L)
lines(P, v)           = the lines L with q(L, v) ≠ none                                    ← the variant composition
effective_qty(L, v)   = q(L, v) × (1 + w / 100)
line_cost(L, v)       = effective_qty(L, v) × m.unit_cost
material_cost(P, v)   = Σ line_cost(L, v) over lines(P, v)
unit_cost(P, v)       = material_cost(P, v) + P.labor_cost + P.overhead_cost

required(m, v)        = Σ effective_qty(L, v) over the lines of lines(P, v) whose material is m
units_possible(m, v)  = ⌊ m.quantity_on_hand / required(m, v) ⌋
max_producible(P, v)  = min over the materials of lines(P, v) of units_possible(m, v);  null when lines(P, v) is empty
bottleneck(P, v)      = the material with the smallest units_possible(m, v)  (tie → lowest name in byte order, then lowest id)

guaranteed(P, c)      = min over sizes z of max_producible(P, (c, z))       (variants with null left out; null if all are)
best(P, c)            = max over sizes z of max_producible(P, (c, z))
binding(P, c)         = the size z giving guaranteed(P, c)  (tie → size order);  bottleneck(P, c) = bottleneck(P, (c, binding))
guaranteed(P)         = min over every variant v of max_producible(P, v);  best(P) = max over every variant
binding(P)            = the variant giving guaranteed(P)  (tie → colour list order, then size order);  bottleneck(P) = its bottleneck
best_at(P)            = the variant giving best(P)        (tie → colour list order, then size order);  named on screen by its colour
                        and its bottleneck ("Best case: 112 units, in Indigo — limited there by Metal shank button 17mm")
unit_cost_range(P)    = min and max of unit_cost(P, v) over every variant

When P has no `by_variant` line, every variant has the same composition and:
unit_cost(P, v) = unit_cost(P),  max_producible(P, v) = guaranteed = best = max_producible(P),  bottleneck as today.
The formulas of before INV-16 (effective_qty(L), required(m), max_producible(P) …) are this case.

stock_value(m)        = m.quantity_on_hand × m.unit_cost                    (unchanged)
inventory_value       = Σ stock_value(m) over the workshop's non-deleted materials   (unchanged)
```
- Per-colour and per-product figures are each **on their own**: the colours share `same` materials (buttons, thread), so
  `best(Indigo) + best(Black)` may not be makeable together. Like `units_covered`, they ignore what other variants would use.

### C. Material usage and next limit (amends the section)
```
needed_per_unit(m, P, v) = required(m, v)                                  for every variant v whose composition uses m
units_covered(m, P, v)   = units_possible(m, v)
next_limit(P, v, m)      = only when bottleneck(P, v) = m: max_producible and bottleneck of lines(P, v) without m
```
A product with no `by_variant` line gives one figure per product, as today. Which variants a screen lists, or a min–max, is the
screens' choice.

### D. Margin (amends the section)
```
margin(P, v)          = P.selling_price − unit_cost(P, v)            scale 4;   margin_percent(P, v) as today, per variant
```

### E. Start batch and Undo batch (replaces the block)
```
start_batch(P, grid):                                     grid = units n_v per colour and size of P, whole, 0 … 1,000,000 each
    N = Σ n_v                                             N ≥ 1; P not deleted
    required_exact(m, v) = Σ effective_qty(L, v) over m's lines of lines(P, v), unrounded (at most 8 decimals)
    total_exact(m)       = Σ_v n_v × required_exact(m, v)
    requires: total_exact(m) ≤ stock(m) for every material m          (exact; with no `by_variant` line this is N ≤ max_producible(P))
    needed(m)            = round4( total_exact(m) )                    HalfUp, once per material per start
    short_by(m)          = round4( total_exact(m) − stock(m) )         for materials where that is > 0
    stock'(m)            = stock(m) − needed(m)
    writes: one production cut c; per variant with n_v > 0 its units and, per material, per_unit(c, v, m) = required_exact(m, v);
            per material taken(c, m) = needed(m); one batch per variant with n_v > 0, In cutting since today
undo_batch(b):                                            only while b is In cutting; b in variant v, k = units(b)
    r_u                  = units of variant u in c not returned yet
    held(c, m, r)        = round4( Σ_u r_u × per_unit(c, u, m) )
    returned(m)          = held(c, m, r) − held(c, m, r with r_v − k)          every batch of a start undone = exactly `taken`
    nothing back for a material deleted since, or whose unit changed since the start
```
Cuts made before INV-16 hold the same per-unit amount for every variant, so their `held` figures are unchanged.

### F. Sizes, Colours, Finished stock per colour (amend the "changes no figure" bullets)
- *Sizes:* "Sizes change no figure: every size uses the same composition" → "A size changes no figure unless a line of the product is
  By colour and size (INV-16, ADR-0027)."
- *Colours:* "Colours change no figure" → the same exception. *Finished stock per colour:* "(every colour uses the same composition)" is
  removed; finished stock still changes no figure.
- *Orders by size* (history): "Every size uses the same composition" stays true of order cuts, which ended with ADR-0026.

### G. Edge cases (new rows)

| Situation | Result |
|---|---|
| Every line `same` | Every variant has today's figures; guaranteed = best = `max_producible(P)` |
| A variant with no line at all (e.g. Black when only an Indigo-only line exists) | `max_producible(P, v) = null`, left out of guaranteed and best; a colour whose every size is null reads "No composition" |
| A colour whose material has 0 on hand | that colour's variants = 0; `guaranteed(P) = 0`, `best(P)` may still be > 0 |
| A colour taken off the product | its cells are deleted in the same save |
| A colour added to the product | no cells: its variants use only the `same` lines |
| Category change to another size list with a `by_variant` line | refused |
| Undo a batch of an old (before INV-16) start | same figures as before |
| Start a mixed grid where rounding would hide a shortfall | refused: the check is on the unrounded total |

### H. Validation (new bullets under "Validation rules")
- Composition line: `quantity_mode` `same` or `by_variant`. `same`: `quantity` as today. `by_variant`: `quantities[colourId][sizeId]`
  each empty or 0 (no cell) or > 0 up to 1,000,000 with max 4 decimals; only colours the save leaves on the product and sizes of
  its size list; at least one cell; at most 2,000 cells per product over all its lines. When the product's colours or sizes no
  longer match the submitted grid, nothing is saved ("The sizes or colours of <product> have changed. Enter its quantities again.").

### I. Worked example (new golden test, added beside the Denim Jacket one)
A domain-level example: the pure calculator is given these eight variants. (The Apparel list has had seven sizes, XS to XXXL, since
2026-11-02, so tests through real products use INV-16's card example instead.)
Denim Jacket in Indigo and Black, sizes S, M, L, XL; labor 12.0000, overhead 3.5000. Lines: *Denim 12oz Indigo* (8.5000/m, stock 250,
waste 10 %) by colour and size, Indigo only: S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000; *Denim 12oz Black* (8.5000/m, stock 40, waste
10 %), Black only, the same four quantities; button, thread and label `same` as in the main example.

| Variant   | Denim eff. qty | Unit cost | Denim units possible | Can produce | Bottleneck              |
| --------- | -------------- | --------- | -------------------- | ----------- | ----------------------- |
| Indigo S  | 1.7600         | 33.8840   | 142                  | 112         | Metal shank button 17mm |
| Indigo M  | 1.9800         | 35.7540   | 126                  | 112         | Metal shank button 17mm |
| Indigo L  | 2.2000         | 37.6240   | 113                  | 112         | Metal shank button 17mm |
| Indigo XL | 2.4200         | 39.4940   | 103                  | 103         | Denim 12oz Indigo       |
| Black S   | 1.7600         | 33.8840   | 22                   | 22          | Denim 12oz Black        |
| Black M   | 1.9800         | 35.7540   | 20                   | 20          | Denim 12oz Black        |
| Black L   | 2.2000         | 37.6240   | 18                   | 18          | Denim 12oz Black        |
| Black XL  | 2.4200         | 39.4940   | 16                   | 16          | Denim 12oz Black        |

- Indigo: guaranteed 103 (binding XL, Denim 12oz Indigo), best 112. Black: guaranteed 16 (XL, Denim 12oz Black), best 22.
  Product: guaranteed 16, best 112, unit cost 33.8840 to 39.4940. Indigo M matches the main example exactly.
- Start batch Indigo M 6 · Indigo L 10 · Black S 5 (21 units): Denim 12oz Indigo 6 × 1.98 + 10 × 2.2 = **33.8800 m**, Denim 12oz Black
  5 × 1.76 = **8.8000 m**, button 168 pc, thread 2,646 m, label 21 pc. Undoing the Indigo L batch gives back 22.0000 m of Indigo denim,
  80 pc, 1,260 m and 10 pc, and nothing of the Black denim.
