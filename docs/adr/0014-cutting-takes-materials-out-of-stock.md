# ADR-0014 — Cutting takes materials out of stock; moving back returns exactly what was taken

- **Status:** Accepted · 2026-10-16 (approved by the human on 2026-10-16 with the skill wording below; tech-lead; the human's decisions
  D-10 of 2026-10-15 and D-11 of 2026-10-16, STATUS §2; delivered by F-41, S-49 and S-53)
- **Supersedes:** decision Q9 of the Phase 3 plan ("making products never reduces material stock")
- **Keeps:** [ADR-0002](0002-money-and-quantity-precision.md) (decimal strings + BigDecimal, scale 4, HalfUp on output),
  [ADR-0004](0004-pure-costing-domain.md) (pure domain), [ADR-0010](0010-database-architecture.md) (`quantity_on_hand` is the cached
  balance, `stock_movements` the append-only ledger, `invory:stock:verify` rebuilds it), [ADR-0013](0013-one-workshop-per-account.md)
  (every record belongs to a workshop). Q8 stays: recording a sale never changes stock.

## Context
Until now stock changed only by restock and manual edit. D-10 decided that **"Start cutting" on an order line takes the materials of those
units out of stock**, and D-11 settled the details:

- Cutting more than the stock allows is refused; the window names the short material and the most units you can cut.
- "Move back to To start" returns **exactly what was taken** for those units, even if the composition changed since. The units cut most
  recently go back first. A material deleted since gets nothing back.
- Deleting an order returns nothing. "Ready to ship", "Shipped", their "Move back", editing an order and recording a sale never touch stock.
- History entries "Cutting" and "Returned" are dated the day of the click. No alerts.
- Open orders never set stock aside (S-48).

This is the first time Invory changes stock **by itself**, so a wrong amount would quietly spoil stock, "Can produce" and the inventory
value (STATUS R-15). Three things make it harder than a restock: the amount comes from maths over the composition (waste included,
summed per material), the return must reproduce a past amount after the composition changed, and two people can move units of the same
line, or use the same material, at the same moment.

## Decision
1. **The maths is pure domain code** in `App\Domain\Stock\CuttingCalculator` (framework-free, covered by the existing
   `App\Domain` arch rule). It starts from F-41's summed requirement per material
   (`CostingCalculator` → `CostingResult::$requirements`), never from a single BOM line.
2. **Amounts.** For a cut of N units, the amount taken of material m is `round4(required_exact(m) × N)`, HalfUp, where `required_exact`
   is the unrounded effective quantity per unit (waste included, summed over m's lines; at most 8 decimals, since quantities have 4 and
   waste 2). Rounding happens once, per material per cut. Because stock is itself a scale-4 number, a cut allowed by the exact check
   (`N × required_exact(m) ≤ stock(m)`) never rounds to more than the stock: stock never goes below 0.
3. **The check.** A cut of N units is allowed when N ≤ the units in To start **and** (the composition is empty **or**
   N ≤ `max_producible` of the product on the stock saved at that moment). `max_producible` is the existing capacity formula, so "You can
   cut at most 12 now" and "Can produce now 12" are the same number by construction. The material named in "… runs short." is the
   bottleneck, also when several materials are short; every short material shows `short_by = round4(N × required_exact − stock)`
   (HalfUp, like every output), and a material counts as short whenever that difference is above 0. When the stock allows no unit, the
   message becomes "You can't cut any now: restock <bottleneck> first." (wording approved with S-53 AC-3).
4. **Each cut is remembered** in two new tables: `order_line_cuts` (one row per "Start cutting": units, units returned so far, who, when)
   and `order_line_cut_materials` (per material: the exact per-unit amount and the amount taken, plus the material's unit at that time).
   These rows are the only source of a return; the current composition is never read for one.
5. **Returning k units** walks the line's cuts **newest first** (LIFO). From each cut it takes as many outstanding units as it still
   needs. The amount a cut still holds for material m with r outstanding units is `round4(per_unit(m) × r)`, so returning k of them gives
   back `round4(per_unit × r) − round4(per_unit × (r − k))`. Returned in pieces or at once, a cut gives back **exactly** what it took, to
   the last 0.0001, with no division and no drift. A material that is soft-deleted now, or whose unit changed since the cut, gets nothing
   back; its share is written off and its row reads "Deleted, not returned" or "Unit changed, not returned". When the units' cuts took
   no material, nothing comes back and the window says so. Units that no cut covers (only possible for data written before S-53)
   return nothing.
6. **Ledger.** `StockMovementType` gains `cutting` and `returned`. Each writes one row per material with `quantity` (positive),
   `quantity_before`, `quantity_after`, and three new nullable references: `order_id`, `product_id` (both only ever soft-deleted, so the
   keys restrict and the text "Boutique Lina · 30 × Denim Jacket" always resolves) and `units`. `occurred_on` is today in the app's time
   zone. `StockLedgerVerifier` counts `cutting` as −quantity and `returned` as +quantity. A material with no amount (an empty
   composition, or a deleted material on return) gets no row.
7. **Concurrency: one lock order for every writer.** Every move runs in one transaction that locks, in this order: the order row
   (`orders … FOR UPDATE`, which also serialises with "Edit order"), the order line, the line's open cuts (moves back to To start only),
   then the materials of the cut **in ascending id**. The counts and stock used by the check are the ones read under those locks, never
   the request's. Restock and stock edits lock a single material, so the ascending order rules out deadlocks. "Edit order" re-checks
   "the quantity can't be less than the units already started" after its own order lock, inside the transaction. On PostgreSQL these are
   row locks. SQLite (tests only; dev and production run PostgreSQL) ignores `FOR UPDATE` and serialises writers on the database file,
   so a race there fails with "database is locked" instead of writing a wrong number; the transaction mode stays `DEFERRED`.
8. **The screen never computes.** The materials table in the move window (needed, in stock, left after, short by, returned) comes from a
   session-authenticated JSON preview route (ADR-0009 rule 5), debounced as you type; the move itself re-runs the same maths under the
   locks.

## Alternatives rejected
- **Recompute a return from the current composition** — contradicts D-11 ("even if the composition changed since").
- **Return pro rata of the cut (`taken × k / N`)** — needs a division and a rounding per return, so partial returns drift from what was
  taken. The `round4(per_unit × r)` difference is exact.
- **Store only the rounded per-unit amount** — `0.00010001 m × 10 000 units` would take 1.0000 m instead of 1.0001 m.
- **Atomic SQL `UPDATE … SET quantity_on_hand = quantity_on_hand − x WHERE quantity_on_hand >= x`** — SQLite would do the arithmetic on
  floats (ADR-0010), and the check needs every material of the composition at once.
- **Set aside stock for open orders** — decided against with S-48.

## Consequences
- ✅ Stock, "Can produce", the bottleneck, "Needs restock" and the inventory value follow the floor without hand corrections.
- ✅ `invory:stock:verify` still rebuilds every balance from the ledger.
- ✅ A deleted or edited order line can't leave stock out of reach: a line that has started can't be removed or reduced below what has
  started (S-49 AC-6), and a soft-deleted order keeps its cuts.
- ⚠️ Two new tables and three new ledger columns; the older migration tests that roll back past `2026_10_15_100100` need the new
  migrations rolled back first. On SQLite, adding keys to `stock_movements` rebuilds the table (F-59; nothing points at it).
- ⚠️ A cut's materials are written off when the material is deleted or its unit changes; the history keeps the "Cutting" entry.
- ⚠️ The business rules change: the `invory-costing-domain` skill must say so **before any code**. `.claude/**` changes only when the
  human asks; the human approved the wording below on 2026-10-16, and the main session applies it.
- ⚠️ The older migration tests keep their hand-kept rollback lists (the human's choice for F-60, 2026-10-16); S-49-a and S-53-a add
  their migrations to them.

## Change to the `invory-costing-domain` skill (approved by the human on 2026-10-16)
**Target file:** `.claude/skills/invory-costing-domain/SKILL.md` (on `main`). The main session applies these eight edits exactly as
written, in the docs commit that carries the F-41, S-49 and S-53 Technical work, **before any code**. Each edit names its anchor in the
current file; nothing else in the file changes.

**Edit 1 · Glossary, the "Stock movement" row.** Replace the row with:
```
| Stock movement | A history entry of a material: `created`, `restock`, `correction`, `price_change`, `cutting`, `returned` | `StockMovement` / `StockMovementType` enum |
```

**Edit 2 · Glossary, the "Order" row.** Replace the row with:
```
| Order | A client's order: client name, order date, optional note, and one or more order lines. Recording, editing or deleting an order **never changes material stock or sales** (S-48); only "Start cutting" and "Move back to To start" on a line do (S-53) | `Order` / `orders` |
```

**Edit 3 · Glossary, after the "Order line" row.** Insert two rows:
```
| Stage counts | How many units of an order line are To start, In cutting, Ready to ship and Shipped; they always add up to the line's quantity. To start = quantity − the other three (S-49) | `in_cutting`, `ready_to_ship`, `shipped` (`to_start` derived) |
| Cut | One "Start cutting" of N units of an order line, remembering per material the exact amount per unit and the amount taken. A move back to To start returns from the newest cuts first (S-53) | `OrderLineCut` / `order_line_cuts` |
```

**Edit 4 · Units and categories, the `StockMovementType` bullet.** Replace it with:
```
- `StockMovementType`: `created, restock, correction, price_change, cutting, returned`.
```

**Edit 5 · New section** between the line "History is listed newest first (date, then entry order) with who did it." and the heading
"## Precision and rounding (non-negotiable, see ADR-0002)". Insert, with a blank line before and after:

````
### Cutting and returning stock (added 2026-10-16, D-10/D-11, ADR-0014; replaces Q9)

```
start_cutting(line, N):                                   N whole, 1 ≤ N ≤ to_start(line)
    requires: the product is not deleted                  (archived and draft products can be cut)
    requires: BOM empty  or  N ≤ max_producible(P)        on the stock saved at that moment
    required_exact(m)  = Σ effective_qty(L) over m's lines, unrounded (at most 8 decimals)
    needed(m, N)       = round4( required_exact(m) × N )             HalfUp, once per material per cut
    short_by(m, N)     = round4( N × required_exact(m) − stock(m) )  only for materials where that is > 0
    stock'(m)          = stock(m) − needed(m, N)                     never below 0
    remembers, per material: per_unit = required_exact(m), taken = needed(m, N), the material's unit
    history: one `cutting` entry per material (quantity = needed), dated today; none for an empty BOM

back_to_start(line, k):                                   k whole, 1 ≤ k ≤ in_cutting(line)
    cuts of the line, newest first; from each cut c take k_c = min(k still to return, c.units − c.units_returned)
    held(c, m, r)      = round4( per_unit(c, m) × r )                r = outstanding units of c
    returned(c, m)     = held(c, m, r) − held(c, m, r − k_c)         a cut returned in full gives back exactly `taken`
    stock'(m)          = stock(m) + Σ_c returned(c, m)
    nothing back for a material deleted since, or whose unit changed since the cut
    history: one `returned` entry per material that gets something back, dated today
```
- The current composition is never read for a return. Units no cut covers return nothing.
- "You can cut at most N now" is `max_producible(P)`; the material it names is `bottleneck(P)`, also when several materials are short
  (every short material still shows its "Short by"). When `max_producible(P) = 0`, the message reads "You can't cut any now: restock
  <bottleneck> first."
- Never touch stock: Mark ready, Ship, their Move back, editing or deleting an order, recording a sale. Deleting an order returns
  nothing.
- Open orders never set stock aside: "Can produce" uses the stock on hand only.

Worked example (demo stock, Denim Jacket): cutting 30 takes 59.4 m Denim 12oz Indigo, 240 pc Metal shank button 17mm, 3,780 m
Polyester thread and 30 pc Woven brand label; the denim goes from 250 to 190.6 m, "Can produce now" from 112 to 82 (bottleneck the
button, ⌊660 / 8⌋), and the inventory value from TND 10,544.300 to 9,936.680. Moving 5 back returns 9.9 m, 40 pc, 630 m and 5 pc (denim
200.5 m, can produce 87); if the jacket needs 10 buttons by then, still 40 pc. With the button at 100 pc, cutting 20 is refused (needs
160 pc, short by 60 pc, at most 12); cutting 12 leaves 4 pc. With the button at 7 pc, at most 0.
````

**Edit 6 · Edge cases table, after the row "Overhead left blank on Add/Edit product | …".** Insert four rows:
```
| Cut a product with an empty BOM | allowed; nothing taken, no history entry; moving those units back returns nothing |
| Composition changed between a cut and its return | the cut's remembered amounts come back, not the new composition's |
| Material deleted, or its unit changed, between a cut and its return | nothing back for it ("Deleted, not returned" / "Unit changed, not returned"); the other materials come back |
| Two people cut from the same stock at once | the second check sees the stock left by the first (row locks, ADR-0014) |
```

**Edit 7 · Validation rules, after the "Restock" bullet** (which ends "`update_unit_cost` boolean (default true).") insert:
```
- Move units (S-49, S-53): `units` integer ≥ 1 ("Enter a whole number of units, at least 1.") and ≤ the units the stage holds now
  ("You can move at most N units."); for Start cutting also ≤ `max_producible(P)` when the BOM isn't empty, and the product not deleted.
```

**Edit 8 · Where it lives in code, at the end of the last bullet** (after "The preview layer `App\Mock` is gone (ADR-0006, S-17).") add
a new bullet:
```
- Added 2026-10-16 (S-53, ADR-0014): `CostingResult::$requirements` (`MaterialRequirement`, F-41: the summed need per material, rounded
  and exact), `App\Domain\Stock\CuttingCalculator` (take and return), `App\Actions\Orders\MoveOrderLineUnits` with
  `TakeMaterialsForCut` and `ReturnMaterialsFromCuts`, `App\Services\Orders\CuttingPreview` (the move window's materials table).
```
