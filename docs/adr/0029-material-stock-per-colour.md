# ADR-0029 — Material stock per colour: a stock (and a cost) per colour of the workshop's list

- **Status:** Accepted · 2026-11-09 (proposed by the tech-lead the same day, from the human's decisions D-30 on INV-21; revised on
  the human's answers D-32; accepted with INV-21's Technical work). Its appendix is applied to the costing skill in the commit of
  step INV-21-a.
- **Amends:** ADR-0027 §2 ("Materials don't change": a material can now keep its stock per colour) and §4 (the fast path: a product
  is costed once only when it has no "By colour and size" line **and** no material kept per colour), §6 (a start is checked per
  stock, not per material), §7 (a cut remembers what it took per material **and colour**); ADR-0018 decision 7 and the skill's
  *Colours* section ("colours change no figure", now also not true when a material is kept per colour).
- **Keeps:** ADR-0002 (decimal strings and `BigDecimal`, rounding once on output), ADR-0004 (pure costing domain), ADR-0011 (exact sums;
  no SQL sums of decimals on SQLite), ADR-0013 (every record belongs to a workshop; another workshop's record is a 404), ADR-0014 (a
  return gives back exactly what was taken), ADR-0026 (only Start batch takes materials), ADR-0027 §1, §3, §5, §8 and §9.

## Context
INV-16 (ADR-0027) made a composition vary by colour and size but kept one stock per material: fabric bought in two colours was two
materials, "Denim 12oz Indigo" and "Denim 12oz Black", and a line "only for Indigo" chose between them. The human decided on
2026-11-08 (D-30) that a material can instead keep **its stock per colour**, with the colours of Settings › Colours, matched to a
product's colour **by the colour itself**, optionally with **a cost per colour**; that restock records quantities per colour; that one
reorder level applies to each colour; and that existing materials are neither changed nor merged.

Everything that reads a material's stock or cost today reads two columns of `materials`: costing, Start batch and Undo batch, restock,
the edit form's correction, the stock ledger check, the status, the Inventory value, the list's filter and sort, the dashboard and the
material options. The live database holds materials, movements and production cuts that must keep their figures.

## Decision

### 1. Two settings on a material
- `materials.stock_mode` (`single` · `per_colour`, enum `MaterialStockMode`, default `single`) and `materials.cost_mode` (`same` ·
  `per_colour`, enum `MaterialCostMode`, default `same`). PostgreSQL CHECK: `cost_mode = 'same'` when `stock_mode = 'single'`.
- Every existing material is `single` / `same`: nothing moves (INV-21 AC-18).
- The free-text `color` column stays as it is; it is kept, not cleared, while a material is per colour.

### 2. One row per colour of a per-colour material
- **`material_colours`**: `workshop_id`, `material_id` (cascade), `colour_id` (restrict; colours are never removed), `quantity_on_hand`
  `decimal(14,4)`, `unit_cost` `decimal(12,4)` nullable, timestamps; unique (`material_id`, `colour_id`). PostgreSQL: CHECK
  `quantity_on_hand >= 0`, CHECK `unit_cost IS NULL OR unit_cost >= 0`, and the workshop composite keys of ADR-0013 (to `materials`
  and `colours`).
- `unit_cost` is set exactly when the material's cost is per colour (kept by the writers, checked by the save's validation, not by the
  database, since the mode lives on the parent row).
- A per-colour material has at least one row; a one-stock material has none. Checked on every save; a switch back to one stock
  deletes the rows in the same transaction.

### 3. The material row keeps the total and the lowest cost
- For a per-colour material, `materials.quantity_on_hand` is the **total over its colours** and `materials.unit_cost` is the same cost
  (`same`) or the **lowest colour cost** (`per_colour`). Both are written by **one writer**, `App\Actions\Materials\MaterialStockWriter`,
  in the same transaction as the colour row and under the material's row lock. Every quantity or cost writer goes through it: create,
  update (corrections, switches), restock, Start batch, Undo batch, adding a colour.
- Why a stored total and not a sum on read: the list sorts by stock, the dashboard and the options read one column, and a SQL sum of
  decimals would be a float on SQLite (ADR-0011). The ledger check (§7) verifies the total and every colour.
- **The material's row lock guards its colour rows.** Nothing locks a colour row on its own; Start batch keeps its lock order (product,
  then materials in ascending id).

### 4. Matching a variant to a colour's stock
- A variant of colour c draws, from a per-colour material, on that material's row of colour c, **by colour id**. `stock(m, c)` is that
  row's quantity; `unit_cost(m, c)` is the row's cost (`per_colour`) or the material's (`same`).
- A per-colour material with no row of colour c is **"no stock"** for c: `units_possible = 0` and, when it limits, the bottleneck is
  that material, flagged *missing* ("Denim 12oz — no Light wash stock"). Its cost: `same` → the material's cost; `per_colour` → **no
  cost**: those variants have no unit cost or margin and are left out of the product's cost and margin ranges (D-32 Q4, INV-21
  AC-12; appendix B).
- This holds for a `same` line and a `by_variant` line alike (D-30 #14). A one-stock material is read exactly as today.
- Within one variant all lines of a material draw on one colour, so `required(m, v)` is still summed per material and the bottleneck
  tie-break (name, then id) is unchanged.

### 5. The costing domain
- `VariantLine` gains the material's colour stock, null for a one-stock material: per colour id, its stock and its cost. Its exact
  effective quantity stays per distinct quantity; its exact line cost becomes per distinct (quantity, colour cost).
- `VariantCosting` keys a composition by its line options **and**, when any line is per colour, the variant's colour. A variant's
  result carries the colour of its bottleneck's stock (null for a one-stock bottleneck) and whether that stock is missing; so do the
  colour summaries. Material spans gain a figure per product colour.
- `BomLine` gains `stockColourId` (null for one stock) so the cutting maths can key stock; `CostingCalculator` is unchanged.
- **Fast path:** a product with no `by_variant` line and no per-colour material is costed once, as today. A product that uses a
  per-colour material is costed per variant even when every line is `same` (D-30 #14, INV-21 AC-11).

### 6. Start batch and Undo batch per stock
- A *stock* is a one-stock material, or one colour of a per-colour material. Start batch sums, rounds once and checks **per stock**:
  `total_exact(m, c) = Σ n_v × required_exact(m, v)` over the variants of colour c, against `stock(m, c)`; a missing colour is a stock
  of 0. A one-stock material is checked as today.
- A grid is *mixed* when it has more than one variant and the product has a `by_variant` line **or a per-colour material**; a mixed
  grid says "Short on <material> in <colour> …" without an "at most" number, also when every line is "Same for every variant" (D-32
  Q5): "Short on Denim 12oz in Black. Restock it first, or lower the units." · "Short on Denim 12oz in Black and Light wash. Restock
  them first, or lower the units." (one material in several colours names its colours, in list order, after "in"; several stocks
  take "them"). One colour and size keeps "You can cut at most N now: Denim 12oz in Black runs short."
- `production_cut_materials.colour_id` (nullable, restrict): the colour of the stock a take came from; null for a one-stock take and
  every cut made before INV-21. The unique key (`production_cut_id`, `material_id`) becomes (`production_cut_id`, `material_id`,
  `colour_id`) plus a partial unique index on (`production_cut_id`, `material_id`) where `colour_id` is null (both drivers).
- A return holds per stock: a take with a colour sums the cut's variant groups **of that colour**; a take without one sums every group
  (today's figure, so batches started before INV-21 give back what they took).
- **Where a return goes** when the material changed since the start: into the one stock when the material is now one stock; into the
  batch's colour when the material is now per colour and has it; otherwise nothing comes back for that material, and the row reads
  "Light wash not on this material, not returned" (D-32 Q7). A deleted material or a changed unit returns nothing, as today.

### 7. The ledger per colour
- `stock_movements.colour_id` (nullable, restrict) names the colour of an entry of a per-colour material; `stock_movements.entry_group`
  (uuid, nullable) ties the entries of one receipt or one switch together for the history.
- Invariants checked by `StockLedgerVerifier`: the entries **without a colour** add up to the one stock (0 while the material is per
  colour); the entries **of colour c** add up to that colour's row (0 once it is gone); **all** entries add up to
  `materials.quantity_on_hand`.
- A switch to per colour writes, in one group, the one stock going to 0 and the chosen colour coming from 0; a switch back writes every
  colour going to 0 and the one stock coming from 0 to the total. Two new movement types carry these: `colour_split`, read
  "Split by colour · Indigo 250 m", and `colour_join`, read "Joined into one stock · 290 m" (D-32 Q6, INV-21 AC-17). Colours added on the form at a quantity above 0 write a `correction` from 0 (a new
  material: one `created` entry per colour); a cost change writes a `price_change` per colour.
- **A colour can be removed** only while its row is at 0 and no entry names it (D-30 #15).

### 8. Status, value, restock
- Status of a per-colour material: `out` when every colour is at 0; else `low` when the reorder level is set and some colour is at
  or under it (a colour at 0 is under it); else `in_stock` (D-30 #13, #18; D-32 Q3). As for a one-stock material, **Low needs a
  reorder level**: with none, a per-colour material is never Low, and Out only when every colour is at 0. `needsRestock` and the
  Inventory summary follow it in SQL (PostgreSQL) and in PHP (SQLite).
- `stock_value(m)` = `Σ_c round4(q(m, c) × unit_cost(m, c))` with cost per colour; `round4(total × unit_cost)` with the same cost
  (one figure, as the material page shows).
- Restock of a per-colour material: one date and reference, a quantity per colour (empty = not received), a price per colour with
  cost per colour or one price with the same cost; the "use as the new unit cost" option changes only the colours that received a
  quantity. One `restock` entry per colour received (and a `price_change` where the cost changed), all in one group.

### 9. Contract
`MaterialResource`, the history rows, the composition line, the capacity, the Start batch rows, `MaterialUsage` and the dashboard's
restock rows gain colour fields; `domain.ts` changes in the same commit as each (frozen in INV-21's Technical work). A one-stock material
sends what it sends today plus `stock_mode: 'single'`, `cost_mode: 'same'` and empty colour lists.

## Consequences
- Every existing material, product and cut keeps its figures; the two materials "Denim 12oz Indigo" and "Denim 12oz Black" stay two.
- Products that use a per-colour material are costed per variant, so the products list and the dashboard cost more of them per
  variant; the INV-16 load test is rerun with per-colour materials.
- The material's total and lowest cost are stored twice (on the material and as the colour rows); the single writer and the ledger
  check keep them equal.
- Two movement types ("Split by colour", "Joined into one stock") and one return reason ("<colour> not on this material, not
  returned") join the history and the Undo window.
- Out of INV-21 (D-32 Q8, moved to INV-26): the material search's "290 m in 2 colours", the note on Add and Edit product's
  composition, the grid sheet's muted line and "Stock per colour" under the material's name.

## Alternatives rejected
- **One material per colour, linked by a "family":** keeps every reader as it is but contradicts D-30 (one material, Denim 12oz).
- **A total computed on read (`withSum`):** float sums on SQLite (ADR-0011) and a sub-select on every list sort.
- **A stock row for one-stock materials too** (colour null): every reader would join for nothing, and the partial-unique and
  null-colour rules would spread to the whole table.
- **Matching by colour name:** D-30 #2 says by the colour itself; names can be renamed.
- **Cost of a missing colour taken from another colour:** a guessed price; D-30 #19 says no price is guessed.

---

## Appendix — staged amendment to `.claude/skills/invory-costing-domain/SKILL.md`

### A. Glossary (new rows, after "Stock status")

| Term | Meaning | Code name |
|---|---|---|
| Stock mode | A material keeps **One stock for all colours** (every material before INV-21) or **Stock per colour** (a quantity per colour of Settings › Colours) | `stock_mode`, `MaterialStockMode` (`single`, `per_colour`) |
| Cost mode | Of a per-colour material: **Same cost for every colour** or **Cost per colour** | `cost_mode`, `MaterialCostMode` (`same`, `per_colour`) |
| Colour stock | The quantity on hand (and, with cost per colour, the unit cost) of one colour of a per-colour material | `MaterialColour` / `material_colours` |
| Stock (of a take) | A one-stock material, or one colour of a per-colour material | `stockColourId` |

### B. Formulas (amends "Formulas"; INV-16's per-variant formulas stay, with these readings)

```
For a material m and a colour c:
stock(m, c)           = m.quantity_on_hand                       when m is one stock
                      = the quantity of m's row of colour c      when m is per colour and has that row
                      = 0, "missing"                             otherwise
unit_cost(m, c)       = m.unit_cost                              when m is one stock, or per colour with the same cost
                      = the cost of m's row of colour c          when m has cost per colour and that row
                      = none                                     otherwise (missing colour, cost per colour)

For a variant v = (c, z) of P:  line_cost(L, v) = effective_qty(L, v) × unit_cost(m, c);
                                units_possible(m, v) = ⌊ stock(m, c) / required(m, v) ⌋
A variant with a line whose unit_cost(m, c) is none has no unit cost and no margin, and is left out of unit_cost_range(P) and the
margin range; its capacity is 0 (that material is its bottleneck, missing).

total(m)              = Σ_c q(m, c)  for a per-colour material (stored as m.quantity_on_hand)
stock_value(m)        = Σ_c round4( q(m, c) × unit_cost(m, c) )  with cost per colour
                      = round4( total(m) × m.unit_cost )         otherwise
inventory_value       = Σ stock_value(m) over the workshop's non-deleted materials
status(m)             = out       when every q(m, c) = 0
                      = low       when the reorder level is set and some q(m, c) ≤ it      (no reorder level: never low)
                      = in_stock  otherwise                                          (one-stock materials: unchanged)

Per colour c of P, for a per-colour material m on P's composition (composition table "View by colour", "Used in" "By colour"):
units_possible(m, P, c) = min and max over the sizes z of P of units_possible(m, (c, z))   ("Indigo 103–142 units"; one number
                          when min = max, "Indigo 126 units"); "no stock" when m lacks c
```
- When P uses no per-colour material, every figure is as before INV-21.
- The next limit of a varying product (its "Used in" suggestion) is that of its binding colour and size: with a `same` line of 1.8 m
  on Denim 12oz, "this material limits Denim Jacket to 20 units, in Black".

### C. Start batch and Undo batch (amends the block)
```
stock s = (m) for a one-stock material, (m, c) for colour c of a per-colour material
total_exact(s)        = Σ n_v × required_exact(m, v) over the variants v that draw on s
requires: total_exact(s) ≤ stock(s) for every stock s;  needed(s) = round4(total_exact(s)), once per stock per start
remembered: per stock its colour (null for one stock) and taken(c, s) = needed(s)
held(c, s, r)         = round4( Σ_u r_u × per_unit(c, u, m) ) over the cut's variants u of s's colour (every variant when s has none)
a return goes to: the one stock if m is one stock now (whatever the take came from); colour c if m is per colour now and has c
                  (c = the take's colour, or the batch's colour for a take made from one stock); otherwise nothing back
```
- *Mixed* (no "at most N"): more than one colour and size, and a `by_variant` line or a per-colour material (also with only `same`
  lines). "Short on Denim 12oz in Black. Restock it first, or lower the units." · "Short on Denim 12oz in Black and Metal shank button
  17mm. Restock them first, or lower the units." · "Short on Denim 12oz in Black and Light wash. Restock them first, or lower the
  units." One colour and size: "You can cut at most N now: Denim 12oz in Black runs short."
- Nothing back for a colour the material no longer has: "Light wash not on this material, not returned".

### D. Validation (new bullets)
- Material: `stock_mode` `single` or `per_colour`; `cost_mode` `same` or `per_colour`, and `same` with one stock. Per colour: `colours`
  at least one, each a colour of the workshop, distinct; each `quantity_on_hand` ≥ 0 with up to 4 decimals; with cost per colour each
  `unit_cost` required, ≥ 0, up to 4 decimals. A colour leaves only at 0 and with no history entry.
- Restock per colour: `lines` keyed by every colour of the material (else "The colours of <material> have changed. Enter the
  quantities again."); each quantity empty or > 0 with up to 4 decimals; at least one; a price per received colour with cost per
  colour, one price otherwise.

### E. Edge cases (new rows)

| Situation | Result |
|---|---|
| Every material one stock | Every figure as before INV-21 |
| A product colour the material doesn't have | that colour's variants make 0, bottleneck the material, missing; no cost with cost per colour |
| A `same` line on a per-colour material | each variant takes from its own colour's stock |
| Start batch in two colours of one material | checked and rounded per colour; one remembered take per colour |
| Undo a batch started before INV-21 | the same figures as before |
| Material switched between a start and its undo | into one stock, or into the batch's colour if the material has it; else nothing back |
| Switch to per colour with stock | the stock goes to the chosen colour; logged "Split by colour · Indigo 250 m", the ledger records both sides |
| Switch back to one stock | the colours' total becomes the one stock, logged "Joined into one stock · 290 m"; the colour rows go, their entries stay |
| Per-colour material, no reorder level, a colour at 0 | In stock (Low needs a reorder level); Out only when every colour is at 0 |

### F. Worked example (added beside INV-16's)
Denim 12oz, per colour, Indigo 250 m at 8.5000, Black 40 m at 9.2000 (reorder 100): total 290, status `low` (Black), stock value
2125.0000 + 368.0000 = **2493.0000**. The Denim Jacket's denim line by colour and size (S 1.6 · M 1.8 · L 2.0 · XL 2.2, waste 10 %)
on it gives INV-16's capacities (Indigo 112 · 112 · 112 · 103, Black 22 · 20 · 18 · 16); Black M costs **37.1400** (margin 51.8600,
58.27 %), Indigo M 35.7540; unit cost range **33.8840–41.1880**; by colour the denim covers Indigo **103–142** and Black **16–22**
units. A `same` line of 1.8 m on it: Indigo 126, Black 20 units covered, and the jacket is limited to 20, in Black.
Start Indigo M 6 · Indigo L 10 · Black S 5: Indigo takes 33.8800 m (216.1200 left), Black 8.8000 m (31.2000 left); undoing Indigo L
gives back 22.0000 m to Indigo, nothing to Black. Keeping Indigo's cost for every colour: stock value **2465.0000**.
