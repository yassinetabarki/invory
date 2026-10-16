---
id: S-53
title: Starting to cut an order takes its materials out of stock
type: feature
phase: 5
priority: Must
size: L
status: Planned
created: 2026-10-15
tags:
  - story
  - phase-5
---

# S-53 · Starting to cut an order takes its materials out of stock

> [!abstract] Description
> As a **workshop owner**, I want **"Start cutting" on an order to take the materials of those units out of stock**, so that **the stock on
> hand, "Can produce" and the restock list always match what is really left on the shelves, without correcting each material by hand**.
> The materials taken are the product's composition, waste included, times the units cut. You see them before you confirm, and you can't
> cut more than the stock allows. Moving units back to To start puts the same materials back. Later stages (ready to ship, shipped) never
> touch stock, recording a sale still never changes stock, and restocking and editing stock work as today. This replaces the earlier rule
> that making products never reduces stock (decision Q9, changed on 2026-10-15, D-10). Low-stock emails or notifications are not part of
> it.

> [!info] How it works today
> Stock only changes when you restock a material or edit its quantity. Making products never reduces it (decision Q9), and recording an
> order (S-48) changes nothing either. So after cutting 30 Denim Jackets on the floor, Denim 12oz Indigo still shows "In stock 250 m",
> Denim Jacket still shows "Can produce now 112", and the dashboard's Inventory value still reads TND 10,544.300, until you correct four
> materials by hand.

## Acceptance criteria
- [ ] **AC-1 · See what will be taken before you confirm.** Given the demo stock (Denim 12oz Indigo 250 m, Metal shank button 17mm 900 pc,
  Polyester thread 20,000 m, Woven brand label 400 pc) and Boutique Lina's Denim Jacket line with To start 50, when you type 30 in "Start
  cutting · Denim Jacket", the window shows "Materials taken out of stock" with the columns "Material", "Needed", "In stock" and "Left
  after":
  - Denim 12oz Indigo · 59.4 m · 250 m · 190.6 m
  - Metal shank button 17mm · 240 pc · 900 pc · 660 pc
  - Polyester thread · 3,780 m · 20,000 m · 16,220 m
  - Woven brand label · 30 pc · 400 pc · 370 pc

  with the line "Waste included. Stock on hand allows 112 units." The table follows the number you type. Nothing changes until you click
  "Move units". While the table loads it shows "Loading materials…"; if it can't load, it shows "Couldn't load the materials for this
  move." with "Try again", and "Move units" can't be clicked until the table is shown. A product with no composition can still be cut,
  and the window says "Denim Jacket has no composition, so no material is taken out of stock." A product archived or set back to draft
  since the order was taken can still be cut. A line whose product was deleted can't start cutting: "Start cutting" can't be clicked,
  with the hint "Denim Jacket was deleted, so it can't be cut."; its units already cut still move on and back.
- [ ] **AC-2 · Confirming takes them out.** Clicking "Move units" shows "30 units of Denim Jacket moved to In cutting. Materials taken out
  of stock." Then Denim 12oz Indigo shows "In stock 190.6 m", Denim Jacket shows "Can produce now 82" and "Bottleneck: Metal shank button
  17mm.", and the dashboard's Inventory value reads TND 9,936.680. Unit costs don't change. When nothing is taken (no composition), the
  message is only "30 units of Denim Jacket moved to In cutting.".
- [ ] **AC-3 · You can't cut more than the stock allows.** Given Metal shank button 17mm at 100 pc (Denim Jacket can produce 12), "Start
  cutting" with 20 marks the button's row "Short by 60 pc" (needed 160 pc, in stock 100 pc) with a warning icon and those words, and
  shows "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or
  fewer." "Move units" then changes nothing. Cutting 12 works and leaves 4 pc. When several materials are short, each of their rows reads
  "Short by …" and the message names the bottleneck. When the stock allows no unit at all (buttons at 7 pc), it reads "Not enough stock
  to cut 20 units. You can't cut any now: restock Metal shank button 17mm first." If someone else used the stock in the meantime, the
  check uses the stock saved now. Stock never goes below 0; amounts are exact, to 4 decimal places like all stock.
- [ ] **AC-4 · Each material's history says why its stock fell.** After cutting 30, each of the four materials' history shows a new entry
  with the badge "Cutting", the text "−59.4 m · Boutique Lina · 30 × Denim Jacket" (the material's own quantity and unit), and "by" the
  person who clicked. The entry is dated the day you click "Move units"; there is no date to choose. The history's description reads
  "Restocks, cutting, stock corrections and unit price changes, newest first."
- [ ] **AC-5 · Moving back to To start returns what was taken.** With Denim Jacket at To start 20 · In cutting 20, "Move back to To start ·
  Denim Jacket" with 5 shows "Materials returned to stock" with the columns "Material", "Returned", "In stock" and "After return":
  - Denim 12oz Indigo · 9.9 m · 190.6 m · 200.5 m
  - Metal shank button 17mm · 40 pc · 660 pc · 700 pc
  - Polyester thread · 630 m · 16,220 m · 16,850 m
  - Woven brand label · 5 pc · 370 pc · 375 pc

  Confirming shows "5 units of Denim Jacket moved back to To start. Materials returned to stock." Denim 12oz Indigo then shows "In stock
  200.5 m", Denim Jacket shows "Can produce now 87", and each history shows "Back from cutting", "+9.9 m · Boutique Lina · 5 × Denim
  Jacket", dated the day you click "Move units".
  - What comes back is what was taken for those units, even if the composition changed since: if the jacket now needs 10 buttons instead
    of 8, moving back 5 returns 40 pc, not 50.
  - If the units were cut at different times, the latest cut comes back first: after cutting 30 at 8 buttons each and then 10 at 10
    buttons each, moving back 5 returns 50 pc of Metal shank button 17mm.
  - Moving back units of a deleted product still returns their materials.
  - A material deleted since the cut, or whose unit has changed since, gets nothing back; its row reads "Deleted, not returned" or
    "Unit changed, not returned", and the other materials come back.
  - When no material was taken for those units, the window says "No material was taken out of stock for these units, so none is
    returned." When nothing comes back, the message is only "5 units of Denim Jacket moved back to To start.".
- [ ] **AC-6 · Nothing else touches stock.** "Mark ready", "Ship" and their "Move back", editing an order's quantities, and recording a
  sale change no stock. An order with units in cutting or ready to ship can't be deleted (S-49), so no material is ever lost by deleting.
- [ ] **AC-7 · "Can produce" and the restock warnings use what is left.** "Can produce now", the bottleneck, "what you can make with it" and
  the "Needs restock" badges and panel all use the stock left on the shelves, so they fall when cutting starts. Units still to start on
  open orders are not set aside: with Boutique Lina's 20 Denim Jackets still to start, Denim Jacket shows "Can produce now 82", not 62.
  When cutting 80 Denim Jackets takes Denim 12oz Indigo (Reorder at 100 m) from 250 m to 91.6 m, it shows "Low stock" and the dashboard's
  "Needs restock" goes from 4 to 5. No email or notification is sent.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 6 commits (one build with F-41 and S-49: 12 commits)
> Tech-lead · 2026-10-16 · approved 2026-10-16 (revised)
> Why complex: Invory changes stock by itself for the first time. It needs new stock maths, two new tables, new history types, a new
> business rule (ADR-0014, replacing Q9), row locks so two people can't use the same stock twice, and a new contract for the move window.
> Size: **L** (about 1,650 lines of production code over 6 commits; S-49 is about 1,450). One story: the move back to To start stays in.

### In short
When you start cutting, the server works out each material's need from the product's composition (F-41's total per material, waste
included) times the units, checks it against the stock saved at that moment, and refuses the cut if any material is short. If the cut
goes ahead, it takes the materials out of stock, writes a "Cutting" history entry per material, and **remembers what each material cost
that cut, per unit**. "Move back to To start" never looks at today's composition: it walks back through the remembered cuts, the
latest cut first, and returns exactly their amounts, so a cut returned in pieces gives back to the last 0.0001 what it took. While a
move runs, the order, the line and the materials are locked, so two people cutting from the same stock take turns and the second one
sees the first one's stock. Deleting an order is refused while units are in cutting or ready to ship (S-49), so no material taken out of
stock can be lost that way. The window's materials table is worked out by the server as you type (the screen never computes amounts). The maths is pure
domain code with its own tests; the business rule goes into the costing rules and ADR-0014 before any code.

### Steps

| Step       | What it does                                              | Proves                       | Lane         | Who      | Size       |
| ---------- | --------------------------------------------------------- | ---------------------------- | ------------ | -------- | ---------- |
| **S-53-a** | Remembering each cut; two new history types               | AC-4, AC-5                   | CRITICAL     | Backend  | ~320 lines |
| **S-53-b** | The maths of taking and returning materials               | AC-1, AC-3, AC-5             | CRITICAL     | Backend  | ~220 lines |
| **S-53-c** | Start cutting takes the materials out of stock            | AC-1, AC-2, AC-3, AC-4, AC-7 | CRITICAL     | Backend  | ~360 lines |
| **S-53-d** | Move back to To start returns what was taken              | AC-5, AC-6                   | CRITICAL     | Backend  | ~260 lines |
| **S-53-e** | The materials table in the move window                    | AC-1, AC-3, AC-5             | CRITICAL     | Frontend | ~390 lines |
| **S-53-f** | Cutting and Back from cutting in a material's history     | AC-4, AC-5                   | NON-CRITICAL | Frontend | ~120 lines |

It needs [F-41](F-41-summed-requirement-per-material.md) first, and it builds on S-49's moves; the order of the whole build is in
[S-49](S-49-track-cutting-ready-shipped.md), section "Order (the whole build: F-41, S-49 and S-53)". Every step keeps `composer test`,
`npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-53-a, S-53-c and S-53-d also run
`composer test:pgsql` (tables, locks, decimals); S-53-e also runs `composer test:browser`.

#### S-53-a · Remembering each cut; two new history types
**Commit:** `feat(stock): order line cuts and the cutting ledger types (S-53-a)`

**Tests**
- 1 migration test: both tables and the three ledger columns created, and `down()` removes them (the older rollback tests list the new
  migrations, F-60). PostgreSQL only, one dataset: the database refuses a cut whose workshop differs from its line's, a cut material
  whose workshop differs from its material's, `units_returned` above `units`, and a per-unit amount of 0.
- 1 stamp dataset (2 rows): a cut takes its line's workshop; a cut material takes its cut's.
- Ledger check: `invory:stock:verify` balances a material with a `cutting` of 59.4 and a `cutting_return` of 9.9 (250 → 200.5) and
  reports drift when one of them is missing.
- History data (AC-4, AC-5): a `cutting` and a `cutting_return` entry come back with `type_label` "Cutting" / "Back from cutting", the
  units, the
  order's client and the product's name, in the one history query (no N+1; a deleted order or product still reads).
- No new arch test: both tables join the workshop-table list, and the "every model uses `BelongsToWorkshop`" rule picks up both models.

> [!example]- Files (19)
> - `app/Enums/StockMovementType.php` (`cutting`, `cutting_return`)
> - `app/Models/{OrderLineCut,OrderLineCutMaterial}.php`
> - `app/Models/{OrderLine,StockMovement}.php` (relations, columns)
> - `app/Services/Inventory/{StockLedgerVerifier,StockHistory}.php`
> - `app/Http/Resources/StockMovementResource.php`
> - `database/migrations/2026_10_16_200000_create_order_line_cuts_tables.php`
> - `database/factories/{OrderLineCut,OrderLineCutMaterial}Factory.php`
> - `resources/js/types/domain.ts` (`StockMovementType`, `StockMovement`)
> - `tests/Feature/Stock/{CutSchemaTest,StockLedgerVerifierTest,StockHistoryTest}.php`
> - `tests/Feature/Workshops/WorkshopStampTest.php` (2 rows)
> - `tests/Arch/WorkshopScopeTest.php` (table and model lists)
> - `tests/Pest.php` (the hand-kept rollback list, F-60)
> - `docs/ARCHITECTURE.md` §4 and §5 (tech-lead)

#### S-53-b · The maths of taking and returning materials
**Commit:** `feat(stock): pure cutting maths, take and return (S-53-b)`

**Tests** (full branch coverage of `App\Domain\Stock`, exact strings)
- Take, golden (AC-1): Denim Jacket × 30 on the demo stock → needed `"59.4000"`, `"240.0000"`, `"3780.0000"`, `"30.0000"`; left after
  `"190.6000"`, `"660.0000"`, `"16220.0000"`, `"370.0000"`; max cuttable 112.
- Take, short (AC-3): buttons at 100 → 20 refused, button short by `"60.0000"`, at most 12, the bottleneck named; 12 allowed, leaves
  `"4.0000"`. Two materials short → both rows carry a short-by figure and only the bottleneck is named; buttons at 7 → at most 0.
- Take, edges: an empty composition (allowed, nothing taken, max cuttable null); stock 0; tiny amounts (0.0001 m at 0.01 % × 10 000
  units → `"1.0001"`, not `"1.0000"`); a cut that the exact check allows never leaves a negative stock.
- Return, golden (AC-5): 30 cut, 5 back → `"9.9000"`, `"40.0000"`, `"630.0000"`, `"5.0000"`.
- Return, latest cut first (AC-5): cut 30 at 8 buttons, then 10 at 10 buttons → moving back 5 returns `"50.0000"` buttons; moving back
  10 more returns 5 × 10 + 5 × 8 = `"90.0000"`; a composition change after the cuts changes nothing.
- Return, exactness: a cut of 3 units at 0.00015 per unit (takes `"0.0005"`) returned one unit at a time gives back exactly `"0.0005"`
  in total; a dataset of partial returns always sums to what was taken.
- Return, edges: a deleted material and a material whose unit changed get nothing back and are flagged with their reason (deleted,
  unit changed); cuts that took no material (an empty composition) give a plan with no rows and "nothing taken"; units no cut covers
  return nothing.

> [!example]- Files (9)
> - `app/Domain/Stock/CuttingCalculator.php`
> - `app/Domain/Stock/{CutPlan,MaterialTake}.php`
> - `app/Domain/Stock/{CutBatch,CutBatchMaterial}.php`
> - `app/Domain/Stock/{ReturnPlan,MaterialReturn,BatchReturn}.php`
> - `tests/Unit/Domain/Stock/CuttingCalculatorTest.php`

#### S-53-c · Start cutting takes the materials out of stock
**Commit:** `feat(orders): start cutting takes the materials out of stock (S-53-c)`

**Tests**
- Preview (AC-1): the JSON for Denim Jacket × 30 has the four rows (needed, in stock, left after) and max cuttable 112; with no
  composition `has_composition` is false and there are no rows; an archived or draft product previews and cuts; Start cutting on a
  deleted product → 422 with "Denim Jacket was deleted, so it can't be cut." (S-49-b refuses the move itself). Guest, another workshop's
  order and a line of another order → refused (route guards); 1 query-budget row.
- Cutting (AC-2): Start cutting 30 → "30 units of Denim Jacket moved to In cutting. Materials taken out of stock."; Denim 12oz Indigo
  `"190.6000"`, Denim Jacket can produce 82 with the button as bottleneck, inventory value `"9936.6800"`, unit costs unchanged; one cut
  with four remembered materials. With no composition the toast is only "30 units of Denim Jacket moved to In cutting." and a cut
  with no materials is remembered (no history entry).
- Too little stock (AC-3), a dataset with nothing changed on each refusal, the message under `units` and the same text as the preview's
  `short_message`:
  - buttons at 100, 20 → "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it
    first, or cut 12 or fewer."; the preview marks the button short by `"60.0000"`;
  - buttons at 100 and thread at 1,000 m, 20 → both rows short, the message names the button (the bottleneck);
  - buttons at 7, 20 → "Not enough stock to cut 20 units. You can't cut any now: restock Metal shank button 17mm first.".
  Cutting 12 at 100 buttons works and leaves `"4.0000"`.
- The saved stock wins (AC-3): the preview said 12, someone else then cut 5 of another product using the button; cutting 12 is refused
  with the new maximum.
- History (AC-4): four `cutting` entries, quantity `"59.4000"` …, before and after, Boutique Lina, Denim Jacket, 30 units, by the person
  who clicked, dated the day of the click (time fixed with `travelTo`).
- What is left (AC-7): with 20 still to start, Denim Jacket shows 82, not 62; cutting 80 on the demo workshop takes the denim to
  `"91.6000"`, it reads Low stock, and the dashboard's "Needs restock" goes from 4 to 5.
- 1 rollback: a failure while writing the third material leaves the stock, the counts and the history untouched.
- PostgreSQL only: the cut locks the order, the line and the materials in ascending id (`for update` in the query log, in that order).

> [!example]- Files (16)
> - `app/Actions/Orders/TakeMaterialsForCut.php`
> - `app/Services/Orders/MoveMessages.php` (the toasts and the two short-stock messages)
> - `app/Actions/Orders/MoveOrderLineUnits.php` (calls it on `start_cutting`)
> - `app/Actions/Materials/AppendStockMovement.php` (order, product and units)
> - `app/Services/Orders/CuttingPreview.php`
> - `app/Http/Controllers/OrderLineMovePreviewController.php`
> - `app/Http/Requests/Orders/MovePreviewRequest.php`
> - `app/Http/Resources/{MovePreview,MaterialMoveRow}Resource.php`
> - `routes/web.php` (the preview)
> - `resources/js/types/domain.ts` (`MovePreview`, `MaterialMoveRow`)
> - `tests/Feature/Orders/{StartCuttingStockTest,MovePreviewTest}.php`
> - `tests/Feature/Performance/QueryBudgetTest.php` (1 row)
> - `tests/Feature/Routes/RoutesTest.php`
> - `tests/Feature/Workshops/CrossWorkshopAccessTest.php`

#### S-53-d · Move back to To start returns what was taken
**Commit:** `feat(orders): moving back to To start returns the materials taken (S-53-d)`

**Tests**
- Return (AC-5): from 20 · 20 (30 cut, 10 moved on), the preview for 5 has the four rows the card lists (returned, in stock, after
  return: `"9.9000"` · `"190.6000"` · `"200.5000"` …); back 5 → "5 units of Denim Jacket moved back to To start. Materials returned to
  stock."; Denim 12oz Indigo `"200.5000"`, can produce 87; four `cutting_return` entries "+9.9" … with Boutique Lina, 5 units, dated
  the day.
- Exactly what was taken (AC-5): the jacket now needs 10 buttons; back 5 returns `"40.0000"`, not 50. Cut 30 at 8 buttons, then 10 at 10
  buttons → back 5 returns `"50.0000"` (the latest cut first); the preview lists the same amounts.
- Deleted product (AC-5): the product is deleted after the cut; moving 5 back still returns its four materials.
- Not returned (AC-5), a dataset: a material removed from the composition and deleted → nothing back for it, no history entry, the
  preview flags it `deleted`; a material removed from the composition whose unit then changed → the same, flagged `unit_changed`; the
  other three come back each time.
- Nothing taken (AC-5): units cut while the product had no composition → the preview has `nothing_taken` true and no rows; the move
  back changes no stock and the toast is only "5 units of Denim Jacket moved back to To start.". The same toast when every material of
  the cut was deleted since.
- Cut and returned in full: after any mix of cuts and moves back, every material is back at its starting stock and
  `invory:stock:verify` reports no drift.
- Nothing else touches stock (AC-6), one dataset: Mark ready, Ship and their moves back, editing the order's quantities, recording a
  sale, deleting an order whose cut units are all shipped (its cuts stay with it), and a refused delete of an order with units in
  cutting (S-49-c) leave every material and its history unchanged.
- The saved state wins: two moves back of the same units one after the other return them once.
- PostgreSQL only: the return locks the order, the line, its cuts and then the materials in ascending id.

> [!example]- Files (6)
> - `app/Actions/Orders/ReturnMaterialsFromCuts.php`
> - `app/Actions/Orders/MoveOrderLineUnits.php` (calls it on `back_to_start`)
> - `app/Services/Orders/CuttingPreview.php` (the return preview)
> - `tests/Feature/Orders/{BackToStartStockTest,OrdersNeverTouchStockTest}.php`
> - `tests/Feature/Orders/MovePreviewTest.php` (the return rows)

#### S-53-e · The materials table in the move window
**Commit:** `feat(orders): materials taken and returned in the move window (S-53-e)`

**Tests**
- Start cutting (AC-1): typing 30 asks the server once the typing rests and shows "Materials taken out of stock" with "Material",
  "Needed", "In stock", "Left after" and the four rows as the card writes them (59.4 m · 250 m · 190.6 m …), and "Waste included. Stock on
  hand allows 112 units."; typing again follows the new number, and a slow early answer never overwrites a newer one.
- Loading and failure (AC-1), for Start cutting and Move back to To start: "Loading materials…" while the preview loads; on failure
  "Couldn't load the materials for this move." with "Try again", which asks again; "Move units" can't be clicked until the table (or the
  no-composition / nothing-taken sentence) is shown. The other four moves have no table and "Move units" works as in S-49.
- No composition (AC-1): "Denim Jacket has no composition, so no material is taken out of stock." (the deleted-product hint on "Start
  cutting" is S-49-d's; Move back to To start on a deleted product's line shows its table as usual).
- Short (AC-3): the button's row reads "Short by 60 pc" with a warning icon and the words, and the server's `short_message` ("Not
  enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.")
  shows; with two short materials both
  rows read "Short by …"; the "You can't cut any now: restock … first." message shows as sent; clicking "Move units" then changes
  nothing: the server's refusal shows under the field.
- Move back to To start (AC-5): "Materials returned to stock" with the columns "Material", "Returned", "In stock" and "After return" and
  the card's four rows (9.9 m · 190.6 m · 200.5 m …); a row flagged `deleted` reads "Deleted, not returned", one flagged `unit_changed`
  "Unit changed, not returned"; with `nothing_taken`, "No material was taken out of stock for these units, so none is returned.".
- 1 browser test: Start cutting 30 on Boutique Lina's Denim Jacket → the toast with "Materials taken out of stock." → Denim 12oz
  Indigo's page shows "In stock 190.6 m".

> [!example]- Files (8)
> - `resources/js/components/orders/move-units-dialog.tsx`
> - `resources/js/components/orders/{materials-move-table.tsx,use-move-preview.ts}`
> - `resources/js/components/orders/order-line-card.tsx`
> - `resources/js/components/orders/{move-units-dialog,materials-move-table}.test.tsx`
> - `resources/js/components/orders/use-move-preview.test.ts`
> - `tests/Browser/OrderGoldenPathTest.php`

#### S-53-f · Cutting and Back from cutting in a material's history
**Commit:** `feat(materials): cutting entries in the stock history (S-53-f)`

**Tests**
- History list (AC-4, AC-5): a `cutting` entry shows the badge "Cutting", "−59.4 m · Boutique Lina · 30 × Denim Jacket", its date and
  "by" the person; a `cutting_return` entry shows "Back from cutting" and "+9.9 m · Boutique Lina · 5 × Denim Jacket".
- The description reads "Restocks, cutting, stock corrections and unit price changes, newest first." on the history panel and card.

> [!example]- Files (6)
> - `resources/js/components/materials/{history-list,history-card,history-panel}.tsx`
> - `resources/js/components/materials/{history-list,history-card,history-panel}.test.tsx`

### Contract (frozen before dispatch)
**Business rule:** [ADR-0014](../adr/0014-cutting-takes-materials-out-of-stock.md) (Accepted 2026-10-16, amended the same day for the
final D-11 answers) and `.claude/skills/invory-costing-domain/SKILL.md` ("Cutting and returning stock"), already updated to match. They
are committed with this card **before** S-53-a starts.

**Route** (S-53-c; under `auth` + `verified`, `view` on the order, scoped `{line}` as in S-49):
- `GET orders/{order}/lines/{line}/moves/preview?move=start_cutting|back_to_start&units=N` → `orders.lines.moves.preview` → JSON
  `MovePreview` (ADR-0009 rule 5, like the material picker). `units` must be a whole number from 1 to 1,000,000 and `move` one of the two,
  otherwise 422. It reads without locks; the move itself redoes the maths under the locks.
- `POST orders/{order}/lines/{line}/moves` (S-49) now takes and returns stock on `start_cutting` and `back_to_start`. " Materials
  taken out of stock." / " Materials returned to stock." is added to the toast only when at least one material's stock changed;
  otherwise the toast is S-49's first sentence alone. Start cutting on a deleted product is refused by S-49-b; moving its cut units back
  to To start returns their materials.

**`resources/js/types/domain.ts`:**
```ts
// `cutting_return` is labelled "Back from cutting" (it fits the 16-character `stock_movements.type` column, so no column change)
export type StockMovementType = 'created' | 'restock' | 'correction' | 'price_change' | 'cutting' | 'cutting_return';

export type StockMovement = {
    // … every field as today; `quantity` is positive for cutting (taken) and cutting_return (given back)
    units: number | null; // cutting / cutting_return: the units of the order line
    order: { id: number; client: string } | null; // cutting / cutting_return (soft-deleted orders included)
    product_name: string | null; // cutting / cutting_return (soft-deleted products included)
};

export type MaterialMoveRow = {
    material: MaterialRef;
    quantity: Decimal; // start_cutting: needed · back_to_start: what those units took
    in_stock: Decimal; // the material's stock now ("In stock")
    after: Decimal | null; // "Left after" / "After return"; null when short or not returned
    short_by: Decimal | null; // start_cutting, only when the stock is short: "Short by …" (every short row)
    not_returned: 'deleted' | 'unit_changed' | null; // back_to_start: "Deleted, not returned" / "Unit changed, not returned"
};

export type MovePreview = {
    move: 'start_cutting' | 'back_to_start';
    units: number;
    rows: MaterialMoveRow[]; // by material name
    has_composition: boolean; // start_cutting: false → "… has no composition, so no material is taken out of stock."
    nothing_taken: boolean; // back_to_start: true → "No material was taken out of stock for these units, so none is returned."
    max_cuttable: number | null; // start_cutting: "Stock on hand allows N units."; null without composition
    // start_cutting, only when the units exceed max_cuttable; written by the server, the same text as the refusal under `units`:
    //   max_cuttable ≥ 1: "Not enough stock to cut 20 units. You can cut at most 12 now: <bottleneck> runs short. Restock it first,
    //                     or cut 12 or fewer."
    //   max_cuttable = 0: "Not enough stock to cut 20 units. You can't cut any now: restock <bottleneck> first."
    short_message: string | null;
};
```

**Schema** (S-53-a):
- `order_line_cuts`: `id`; `workshop_id` (FK, `restrictOnDelete`, never mass-assignable, from the line); `order_line_id` (FK,
  `cascadeOnDelete`: only a line with nothing started can be removed, so nothing outstanding goes with it; an order is only
  soft-deleted, and only with nothing in cutting or ready to ship); `units` and `units_returned` (unsigned integers); `user_id` (FK →
  users, nullable, `nullOnDelete`); timestamps. Index `(order_line_id, id)`.
- `order_line_cut_materials`: `id`; `workshop_id`; `order_line_cut_id` (FK, `cascadeOnDelete`); `material_id` (FK, `restrictOnDelete`;
  materials are only soft-deleted); `unit_id` (FK → units, nullable, `nullOnDelete`: the material's unit at the cut); `quantity_per_unit`
  `decimal(20,8)` (exact, waste included); `quantity_taken` `decimal(14,4)`; timestamps. `unique(order_line_cut_id, material_id)`.
- `stock_movements` gains `order_id` (FK → orders, nullable, `restrictOnDelete`), `product_id` (FK → products, nullable,
  `restrictOnDelete`) and `units` (unsigned integer, nullable). Both parents are only ever soft-deleted.
- PostgreSQL only: `unique(workshop_id, id)` on `order_lines` and `order_line_cuts`; composite keys from both new tables to their line,
  cut and material on `(workshop_id, …)`; CHECKs `units >= 1`, `units_returned <= units`, `quantity_per_unit > 0`, `quantity_taken >= 0`.
  `materials.quantity_on_hand >= 0` is already checked.

**PHP:**
- `App\Domain\Stock\CuttingCalculator::take(list<BomLine> $lines, int $units): CutPlan` and
  `::giveBack(list<CutBatch> $newestFirst, int $units): ReturnPlan` (pure; built on `CostingResult::$requirements` from F-41).
- `TakeMaterialsForCut::handle(OrderLine $line, int $units, User $actor): CutPlan` and
  `ReturnMaterialsFromCuts::handle(OrderLine $line, int $units, User $actor): ReturnPlan`, called only by `MoveOrderLineUnits` inside its
  transaction, after the order and line locks. Stock is written from the plan's "after" figures, so the Actions do no arithmetic.

### Decisions
- **The amounts follow ADR-0014:** need = `round4(exact need per unit × units)`, HalfUp, once per material per cut; a return gives back
  `round4(per unit × r) − round4(per unit × (r − k))`, so it is exact and never drifts. The exact per-unit need is stored with 8 decimals
  (4 from the quantity, 2 from the waste, and 2 more from dividing the percentage by 100).
- **One message builder.** The short-stock message (both variants) and the toasts are built once on the server and used by the preview
  and the refusal alike, so the window and the error can't say different things.
- **Cuts are remembered in their own tables**, not in the history: the history is append-only, and a cut's "units returned" changes.
- **History entries point at the order and the product**, not at the line or the cut, because a line with nothing started may be removed
  in Edit order (S-49 AC-6), while orders and products are only ever soft-deleted. The client and product names shown are today's.
- **A material whose unit changed since the cut gets nothing back**, like a deleted one (D-14; a unit can change once the material is no
  longer in any composition): returning "40 pc" into a material now counted in boxes would be wrong. Its row reads "Unit changed, not
  returned".
- **History type `cutting_return`, labelled "Back from cutting"** (D-11). `back_from_cutting` would need 17 characters and a change to
  `stock_movements.type` (`string(16)`), which rebuilds the table on SQLite (F-59) for no gain.
- **No delete warning data.** An order with units in cutting or ready to ship can't be deleted (S-49-c), so every cut whose materials
  are still out of stock belongs to an order that stays; `units_cut` is gone from the contract.
- **The preview is a JSON route**, debounced at 250 ms with "last request wins", the same pattern as the material picker. "Move units"
  stays disabled for Start cutting and Move back to To start until a preview answer is shown, so nobody confirms without seeing what
  moves; the server still re-checks everything on the move.
- **Locks:** order → line → cuts → materials in ascending id, the same order for every writer (ADR-0014 §7). Restock and stock edits lock
  one material, so no deadlock is possible. SQLite stays in `DEFERRED` mode: it runs only in tests, where a single connection can't race.

### Risks
- A wrong amount quietly spoils stock (STATUS R-15) → exact maths in the pure domain with full-branch tests on the card's figures, and
  `invory:stock:verify` must report no drift after every scenario test.
- Two people use the same stock at once → row locks in one fixed order; a test with a stale preview; PostgreSQL tests check the locks.
- Precision lost between the need per unit and the units → the exact per-unit need from F-41, stored at 8 decimals; a tiny-amount test.
- A partial return drifts from what was taken → the difference formula; a dataset where partial returns always add up to the cut.
- A return reads today's composition by mistake → the return code never loads `product_components`; the "now needs 10 buttons" test.
- Units started before S-53 have no cut to return from (only possible on the branch, between S-49-b and S-53-c) → they return nothing,
  and the domain has a test for it. No order on `laravel` has started units before the build lands.
- Adding keys to `stock_movements` rebuilds the table on SQLite (F-59) → nothing points at `stock_movements`; the migration test runs
  with rows present. The older rollback tests keep their hand-kept migration lists (the human's choice for F-60, 2026-10-16) → S-53-a
  adds the new migration to them.
- The window's table must not compute on the screen → every figure comes from the preview; a test fails if the table is shown without a
  server answer.
- A failed or slow preview blocks the move → "Loading materials…", then "Couldn't load the materials for this move." with "Try again";
  the other four moves don't use the preview.
- S-53 is L → it is cut into six steps that each stay within one commit's budget; S-53-e, the largest, is about 420 lines.
