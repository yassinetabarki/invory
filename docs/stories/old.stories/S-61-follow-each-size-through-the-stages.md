---
id: S-61
title: Follow each size through cutting, ready to ship and shipped
type: feature
phase: 5
priority: Must
size: M
status: Done
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-61 · Follow each size through cutting, ready to ship and shipped

> [!abstract] Description
> As a **workshop owner**, I want **to see and move the units of each size through To start, In cutting, Ready to ship and Shipped**, so
> that **I know, for example, that 4 S and 10 M are being cut while the L and XL haven't started**.
> Each move window asks a number per size, so one cut can take several sizes at once, as on the cutting table. Cutting still takes the
> same materials per unit whatever the size; a bigger size using more fabric is a later story. Ships together with S-60.

> [!info] How it works today
> On an order's page each product line shows four figures: "To start 50 units", "In cutting 0 units", "Ready to ship 0 units" and
> "Shipped 0 units". "Start cutting" opens "Start cutting · Denim Jacket" with one field, "How many units?", and the hint "Up to 50
> units.". Cutting 30 takes 59.4 m of Denim 12oz Indigo, 240 pc of Metal shank button 17mm, 3,780 m of Polyester thread and 30 pc of
> Woven brand label out of stock. Nothing says which sizes those 30 jackets are.

## Acceptance criteria
- [x] **AC-1 · Stages per size.** Boutique Lina's Denim Jacket line (S 10 · M 20 · L 15 · XL 5) shows a table with a row per size ordered
  (a size with 0 units has no row) and a "Total" row, and the columns "To start", "In cutting", "Ready to ship" and "Shipped". It
  starts at S 10 · 0 · 0 · 0, M 20 · 0 · 0 · 0, L 15 · 0 · 0 · 0, XL 5 · 0 · 0 · 0 and Total 50 · 0 · 0 · 0. The line's four figures and "0 of 50 shipped" are the Total row. A "One size"
  line looks as it does today. ✅ 2026-10-23 · 1db11bb
- [x] **AC-2 · One move, several sizes.** "Start cutting · Denim Jacket" asks "How many units of each size?", with a field per size and
  the hints "Up to 10", "Up to 20", "Up to 15" and "Up to 5". With S 4 and M 10, "Move units" shows "14 units of Denim Jacket moved to In
  cutting. Materials taken out of stock." and the table reads S 6 · 4, M 10 · 10, L 15 · 0, XL 5 · 0, Total 36 · 14. "Mark ready", "Ship"
  and each "Move back" work the same way. A size whose stage holds 0 units shows no field. Empty fields move nothing; with every field
  empty the window shows "Enter at least one unit to move." Each size can move at most what its stage holds: M 25 shows "You can move at
  most 20 units of M." and nothing moves. ✅ 2026-10-23 · 1db11bb
- [x] **AC-3 · Cutting takes the same materials for every size.** For S 4 and M 10, "Materials taken out of stock" shows the needs of 14
  jackets: Denim 12oz Indigo 27.72 m, Metal shank button 17mm 112 pc, Polyester thread 1,764 m and Woven brand label 14 pc, as if all 14
  were one size. Each material's history reads "−27.72 m · Boutique Lina · 14 × Denim Jacket (S 4 · M 10)". The short-stock check
  counts every size together: with Metal shank button 17mm at 100 pc, S 10 and M 10 is refused with "Not enough stock to cut 20 units.
  You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer." ✅ 2026-10-23 · 1db11bb
- [x] **AC-4 · Moving back returns what was taken.** "Move back to To start · Denim Jacket" with M 5 shows "5 units of Denim Jacket moved
  back to To start. Materials returned to stock." and returns 9.9 m, 40 pc, 630 m and 5 pc, the latest cut first, as today. M then
  reads To start 15 · In cutting 5. Each material's history reads "+9.9 m · Boutique Lina · 5 × Denim Jacket (M 5)". ✅ 2026-10-23 · 1db11bb
- [x] **AC-5 · Editing an order that has started, per size.** In "Edit order", with 10 M in cutting, ready to ship or shipped, M 8 shows
  "M can't be less than the 10 units already in cutting, ready to ship or shipped." and nothing is saved. Sizes with no unit started
  change freely. The line still can't be removed while any of its units has started ("Denim Jacket can't be removed: 14 units have
  started."). ✅ 2026-10-23 · 1db11bb
- [x] **AC-6 · Statuses and totals add the sizes.** A line is "Not started", "In progress" or "Shipped" from its Total row, and the order
  from its lines, as today. The Orders list's figures and its totals ("In cutting 14 units") add every size together. ✅ 2026-10-23 · 1db11bb
- [x] **AC-7 · Phone width.** At 375 px each size becomes its own row of four labelled figures ("S · To start 6 · In cutting 4 · Ready to
  ship 0 · Shipped 0"), with the Total first, and the move window's fields wrap like the order form's. ✅ 2026-10-23 · 1db11bb

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 3 commits (one build with S-60)
> Tech-lead · 2026-10-22 · approved 2026-10-22 · delivered 2026-10-22
> Why complex: moves per size with a limit per size, the materials a cut of several sizes takes and returns, and new data for the
> order page and the move window.

> [!info] Planned with S-60
> The overview of all eight steps of the build, the contract, the decisions, the order and the risks are in the Technical work of
> [S-60](S-60-order-a-product-in-several-sizes.md). This card holds its own three steps. They come after S-60-a and S-60-b; S-61-a
> comes after S-60-c, and S-61-c after S-61-a and S-60-d.

### In short
Each move now asks a number per size and moves all of them, or none if one size asks for more than its stage holds. Start cutting
takes the materials of all the units together, exactly as if they were one size, and Move back to To start returns from the latest cut
first, as it does today; the history entries of the move remember which sizes moved. The line's own figures stay the sum of its sizes,
so the statuses and the Orders list's totals add every size without any change there. On screen, the order page gets a table per
size with the Total row holding today's figures and buttons, and the move window gets a field per size.

### Steps

| Step       | What it does                                  | Proves                                 | Lane         | Who      | Size       |
| ---------- | --------------------------------------------- | -------------------------------------- | ------------ | -------- | ---------- |
| **S-61-a** | Moving units size by size                     | AC-2, AC-3, AC-4, AC-6                 | CRITICAL     | Backend  | ~250 lines |
| **S-61-b** | The order page shows each size and its stages | AC-1, AC-6, AC-7 (and S-60 AC-3, AC-5) | NON-CRITICAL | Frontend | ~250 lines |
| **S-61-c** | The move window asks a number per size        | AC-2, AC-3, AC-4, AC-7                 | CRITICAL     | Frontend | ~300 lines |

Four S-60 steps also prove criteria of this card: S-60-b sends only the sizes ordered, with their stages (AC-1), S-60-c refuses
lowering a size below its started units (AC-5, shown by S-60-d), and S-60-e shows the sizes in a material's history, on "Cutting"
(AC-3) and on "Back from cutting" (AC-4) entries.

| AC   | Proved by                                         |
| ---- | ------------------------------------------------- |
| AC-1 | S-60-b (sizes ordered), S-61-b (table)            |
| AC-2 | S-61-a (server), S-61-c (window)                  |
| AC-3 | S-61-a (stock), S-61-c (window), S-60-e (history) |
| AC-4 | S-61-a (stock), S-61-c (window), S-60-e (history) |
| AC-5 | S-60-c (server), S-60-d (form)                    |
| AC-6 | S-61-a (totals), S-61-b (status)                  |
| AC-7 | S-61-b (table), S-61-c (window)                   |

At Step 4 the human approved (2026-10-22) committing S-61-a and S-61-c together (and S-60-c with S-60-d), so a server change never
lands without its screen.

The gates are the build's (see S-60): the five gates after every step, S-61-a's PostgreSQL tests, and the full parity and browser
suites on the combined tree.

#### S-61-a · Moving units size by size
**Commit:** `feat(orders): move units size by size; cutting takes the materials of all of them (S-61-a)`

- `MoveOrderLineUnitsRequest`: a line counted by size sends `sizes` (size id → a whole number, or empty; 0 counts as empty, as on the
  order form); a line that counts as one size sends `units`, as today. A value that isn't a whole number from 0 → "Enter a whole
  number of units." under that size (`sizes.<size id>`); every size empty or 0 → "Enter at least one unit to move." under `sizes`; a
  size that isn't on the line is refused.
- `MoveOrderLineUnits` takes the units by size. Under the order lock it locks the line and its size rows, checks every size against
  what its stage holds now ("You can move at most 20 units of M." under `sizes.<size id>`; single-field lines keep "You can move at
  most N units." under `units`), and moves every size or none. Start cutting takes the materials of the total in one cut, and its
  short-stock refusal counts every size together (under `sizes`, or `units` for single-field lines); Move back to To start returns
  from the line's cuts for the total, latest cut first. Then it writes each size row, and the line's figures as their sums. The toast
  names the total ("14 units of Denim Jacket moved to In cutting. Materials taken out of stock.").
- `TakeMaterialsForCut` and `ReturnMaterialsFromCuts` put the sizes moved (size, label, units, in size order) on every history entry
  of the move, "Cutting" and "Back from cutting" alike; null for single-field lines.
- `OrderMessages` gets the new sentences, each with its singular. `domain.ts`: `MoveUnitsFormData` gains `sizes`.

**Tests**
- AC-2, one test: Start cutting S 4 · M 10 on Boutique Lina's jacket → the card's toast; S 6 · 4, M 10 · 10, L 15 · 0, XL 5 · 0; the
  line To start 36 · In cutting 14.
- AC-2, one dataset over Mark ready, Ship and the three Move backs, on a line with units in every stage: each size and the line move
  as asked, and the line equals its sizes.
- AC-2, one dataset of refusals: every size empty · every size 0 → "Enter at least one unit to move." · M "2.5" · "-1" · "abc" →
  "Enter a whole number of units." under M · M 25 with 20 to start → "You can move at most 20 units of M." · a size of another list →
  refused. Each time nothing moves: sizes, line, stock and history as before.
- AC-2, one test: a Denim Jacket line ordered as M 20 only moves M 5 through the per-size payload (one size, still `sizes`).
- AC-3, one test: the 14 jackets take 27.7200 m of Denim 12oz Indigo, 112 pc of Metal shank button 17mm, 1764 m of Polyester thread
  and 14 pc of Woven brand label; each cutting entry has 14 units and the sizes S 4 and M 10.
- AC-3, one test: with the button at 100 pc, S 10 · M 10 → "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank
  button 17mm runs short. Restock it first, or cut 12 or fewer." and nothing moves.
- AC-4, one test: after an earlier cut of S 6 and the cut of S 4 · M 10, Move back M 5 → "5 units of Denim Jacket moved back to To
  start. Materials returned to stock.", 9.9 m, 40 pc, 630 m and 5 pc back from the latest cut; M To start 15 · In cutting 5; each
  "Back from cutting" entry has 5 units and the size M 5 (the data behind "+9.9 m · Boutique Lina · 5 × Denim Jacket (M 5)").
- AC-6, one test: after S 4 · M 10, the Orders list's totals read In cutting 14 and the line and the order "In progress"; once every
  size of every line is shipped, both read "Shipped".
- One rollback test: a failure while writing the size rows leaves sizes, line, stock and history as they were.
- The existing move tests (lines that count as one size) stay as they are.

> [!example]- Files (9)
> - `app/Http/Requests/Orders/MoveOrderLineUnitsRequest.php`
> - `app/Http/Controllers/OrderLineMoveController.php`
> - `app/Actions/Orders/{MoveOrderLineUnits,TakeMaterialsForCut,ReturnMaterialsFromCuts}.php`
> - `app/Services/Orders/OrderMessages.php`
> - `resources/js/types/domain.ts`
> - `tests/Feature/Orders/MoveSizesTest.php` (new: AC-2, AC-6, rollback)
> - `tests/Feature/Orders/CutSizesStockTest.php` (new: AC-3, AC-4)

#### S-61-b · The order page shows each size and its stages
**Commit:** `feat(orders): sizes and their stages on the order page (S-61-b)`

- Under the heading "Denim Jacket · PRD-JKT-001 · 50 units": "S 10 · M 20 · L 15 · XL 5" (sizes with 0 left out) for a line counted
  by size, and "One size 50" for a line recorded before sizes on a product that now has several sizes (S-60 AC-5). A "One size"
  product's line (Leather Tote 12) shows nothing under its heading, as today.
- A line counted by size shows a table: the columns "To start", "In cutting", "Ready to ship" and "Shipped", a row per size ordered (a
  size with 0 units has no row), and the "Total" row, which holds the line's own four figures with today's move buttons under each
  figure. The size rows show figures only. The status and "0 of 50 shipped" read from the line, as today. A line that counts as one
  size looks as it does today.
- The table takes its columns as a list (S-57 gives a cancelled order Dropped / In cutting / Put in stock / Shipped) and adds no
  figures: every number comes from the server.
- Phone: the Total comes first as a card with the move buttons under it, then each size as its own row of four labelled figures ("S ·
  To start 6 · In cutting 4 · Ready to ship 0 · Shipped 0"). The ui-ux-designer reviews the delivered screen at acceptance.
- A small helper writes a list of sizes ("S 10 · M 20", sizes with 0 left out, numbers in the app's locale); S-60-e and S-61-c use it.

**Tests**
- S-60 AC-3 and AC-5, one `it.each` on the heading: S 10 · M 20 · L 15 · XL 5 → "S 10 · M 20 · L 15 · XL 5" · S 10 and XL 5 → "S 10
  · XL 5" · a Denim Jacket line recorded before sizes → "One size 50" · a Leather Tote line → nothing under the heading.
- AC-1, one test: Boutique Lina's jacket shows the four column headers, the four size rows and Total 50 · 0 · 0 · 0; the buttons sit
  in the Total row and open the move window; the size rows have no button; a Leather Tote line has no table.
- AC-1, one test: a jacket ordered S 10 and XL 5 shows two size rows, S and XL, and no M or L row.
- AC-6, one test: the status and "N of 50 shipped" are the line's own figures.
- AC-7, one test: on a phone the Total card comes first with the move buttons under it, then each size reads "S · To start 6 · In
  cutting 4 · Ready to ship 0 · Shipped 0" (labels and order; the layout is checked in the click-through).
- The helper: one `it.each` (several sizes, a 0 left out, thousands in the locale).

> [!example]- Files (7)
> - `resources/js/lib/sizes.ts` (new)
> - `resources/js/lib/sizes.test.ts` (new)
> - `resources/js/components/orders/size-stages-table.tsx` (new)
> - `resources/js/components/orders/size-stages-table.test.tsx` (new)
> - `resources/js/components/orders/order-line-card.tsx`
> - `resources/js/components/orders/order-line-card.test.tsx`
> - `resources/js/pages/orders/show.test.tsx` (only if it must change)

#### S-61-c · The move window asks a number per size
**Commit:** `feat(orders): the move window asks a number per size (S-61-c)`

- For a line counted by size, every move window asks "How many units of each size?", with a field per size whose stage holds units
  and the hint "Up to N" under each (N from the server). A size whose stage holds 0 has no field. A product with several sizes ordered
  in one size (M 20 only) still gets this window, with one field "M". A line that counts as one size keeps "How many units?" and "Up
  to N units.". A 0 in a size field counts as empty, as on the order form.
- Start cutting and Move back to To start show the materials of the total typed (today's preview, asked for that total), as if all
  the units were one size.
- Messages: "Enter at least one unit to move." and the short-stock message for the whole window; "You can move at most 20 units of
  M." and "Enter a whole number of units." under M.
- Phone: the fields wrap like the order form's (the size fields of S-60-d).
- The order golden path starts cutting S 4 · M 10 and checks the toast and the table.

**Tests**
- AC-2, one test: "Start cutting · Denim Jacket" shows "How many units of each size?", S, M, L and XL with "Up to 10", "Up to 20",
  "Up to 15" and "Up to 5"; S 4 and M 10 post the sizes by id.
- AC-2, one `it.each` over Mark ready, Ship and the Move backs: only the sizes whose stage holds units have a field, each with its
  hint; a jacket ordered as M 20 only shows "How many units of each size?" with one field "M".
- AC-2, one `it.each` on server messages: "Enter at least one unit to move." for the window · "You can move at most 20 units of M."
  and "Enter a whole number of units." under M.
- AC-3 and AC-4, one test: S 4 and M 10 ask the preview for 14 units and show its rows; Move back with M 5 asks for 5.
- AC-7, one test: the fields wrap, each with its label (the layout is checked in the click-through).
- The existing tests of lines that count as one size stay as they are.

> [!example]- Files (4)
> - `resources/js/components/orders/move-units-dialog.tsx`
> - `resources/js/components/orders/move-units-dialog.test.tsx`
> - `resources/js/components/orders/order-size-fields.tsx` (from S-60-d; only if it needs a prop)
> - `tests/Browser/OrderGoldenPathTest.php` (the cutting step)
