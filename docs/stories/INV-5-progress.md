---
id: INV-5
title: Progress, production for the shelf
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
created: 2026-11-02
tags:
  - story
  - variants
---

# INV-5 · Progress, production for the shelf

> [!abstract] Description
> As a **workshop owner**, I want **to start batches of a product in its colours and sizes, follow them through my own production
> phases and put the finished units in stock**, so that **I see at a glance what is being made, where each batch is and since when,
> and the shelf fills up without counting in my head**. A **batch** is units of one product in one colour and one size, made together,
> with its own number (**B-0147**). Starting a batch takes its materials out of stock; putting it in stock adds its units to the
> finished stock and records a restock "From production". Nothing is tied to an order or a client.
> New: the **Progress** page in the sidebar (phase tiles, a table per product with "On the shelf now", a view per phase), **Start
> batch**, the moves between phases (with splitting), **Put in stock**, **Undo batch**, and **Settings › Production phases**. Changed:
> Edit product refuses two changes while units are in production. The pages are drawn in [[INV-5-progress-ui]].
> Left out on purpose: the work tickets, their printing, the ticket size and the **Scan** page (INV-15, same UI card); any link
> between a batch and an order or a client; setting shelf stock aside; starting a batch from an order or the product page; undoing a
> batch after cutting; dragging; moving several batches at once. The order page keeps its cutting buttons as today.

> [!info] How it works today
> There is no production page. The owner cuts from an order: the order page shows each line's table (To start · In cutting · Ready to
> ship · Shipped) with **Start cutting**, **Mark ready**, **Ship** and the Move back buttons. To know how many Indigo M jackets are being
> made she opens every order and adds them up in her head. Sewing, finishing or a quality check don't exist anywhere, and a pile of
> finished jackets only reaches the shelf when someone makes a restock or types a count in **Update stock**.
> Cutting takes the materials out of stock: 30 Denim Jackets take 59.4 m Denim 12oz Indigo, 240 pc Metal shank button 17mm, 3,780 m
> Polyester thread and 30 pc Woven brand label. If stock is short, cutting is refused with the number that can be cut.
> The sidebar reads Dashboard, Materials, Products, Restocks, Orders, Clients, Sales. Edit product already refuses to take off a colour
> that has units on the shelf ("Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first.") and
> to move a product that is on orders to a category with other sizes.

## Acceptance criteria

Today is 30 Oct 2026. The workshop's phases are **Sewing** and **Finishing**. The examples use these batches:

| Batch  | Product · colour · size         | Units | Phase      | Since       |
| ------ | ------------------------------- | ----- | ---------- | ----------- |
| B-0146 | Denim Jeans 5-pocket · Indigo S | 5     | Sewing     | 26 Oct 2026 |
| B-0147 | Denim Jacket · Indigo S         | 4     | Sewing     | 27 Oct 2026 |
| B-0149 | Denim Jacket · Indigo M         | 6     | Sewing     | 28 Oct 2026 |
| B-0148 | Denim Jacket · Indigo M         | 4     | Finishing  | 29 Oct 2026 |
| B-0150 | Denim Jacket · Indigo M         | 4     | In cutting | 25 Oct 2026 |
| B-0151 | Denim Jacket · Light wash · L   | 2     | In cutting | 29 Oct 2026 |
| B-0152 | Denim Jacket · Light wash · XL  | 1     | In cutting | 29 Oct 2026 |

The shelf (S · M · L · XL): Denim Jacket Indigo 2 · 0 · 3 · 1, Light wash 0 · 0 · 0 · 0; Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0. The
Denim Jacket (per unit: 1.98 m Denim 12oz Indigo, 8 pc Metal shank button 17mm, 126 m Polyester thread, 1 pc Woven brand label; 900
buttons in stock) shows "Can produce now 112", bottleneck Metal shank button 17mm.

- [ ] **AC-1 · Production phases.** The Settings menu has a group **Production** with **Production phases**: "The phases a batch goes
  through between cutting and putting it in stock. In cutting and Put in stock are always there." and **Add phase**. The list shows
  the whole path: **In cutting** (grey, lock icon, "Always there. Start batch puts a batch here and takes the materials."), the
  workshop's phases in order (here **Sewing** "Holds 15 units", **Finishing** "Holds 4 units", each with **Move up**, **Move down**,
  **Edit Sewing**, **Remove Sewing**), and **Put in stock** (grey, lock icon, "Always there. Ends a batch: its units are added to your
  finished stock.").
  - **Add phase** / **Edit Sewing** ask for a **Name** (hint "A short verb or noun your team uses, such as Sewing or Quality check.") with
    **Cancel** and **Save phase**. Refusals: "Enter the phase's name." · "Keep the name to 24 characters or fewer." · "You already have a
    phase named "Sewing"." · "In cutting and Put in stock are already there. Choose another name." · "You can have up to 6 phases."
  - A phase that holds units can't be moved or removed: the buttons can't be pressed and a tip the keyboard reaches says "Sewing holds 15
    units, so it can't be moved or removed. Move them on or back first." Removing an empty phase asks **Remove Quality check?** ·
    "Quality check is empty. It leaves Progress; batches then go from Finishing to Put in stock." · **Remove phase** · **Keep phase**; if
    it filled up meanwhile: "Quality check now holds 2 units, so it can't be removed."
  - With no phase of its own: "No phase of your own. Batches go from In cutting straight to Put in stock." · **Add phase**.
  - Toasts: "Phase added." · "Phase updated." · "Phase removed." · "Phases reordered." Each workshop has its own phases; a new workshop
    has none.
- [ ] **AC-2 · Start batch.** **Start batch** opens from the Progress header and from each product card on Progress (product already
  chosen, named "Start a batch of Denim Jacket"). The window **Start batch**, "Make products for your shelf. Materials are taken out of
  stock now.", has **Product** (searchable, showing "Can produce now 112") and "How many units of each colour and size?": a row per
  colour in the order of Settings › Colours, a field per size, the shelf under each ("Shelf 2" or the amber "Shelf Out").
  - Given Indigo M 6 and Indigo L 10: "Total 16 units · 2 batches (Indigo M 6, Indigo L 10)" and the table "Materials for 16 units"
    (Material · Taken · In stock · After): 31.68 m Denim 12oz Indigo, 128 pc Metal shank button 17mm, 2,016 m Polyester thread, 16 pc
    Woven brand label. **Start batch** makes **one batch per colour and size**, B-0153 (Indigo M, 6) and B-0154 (Indigo L, 10), both In
    cutting since today, takes those materials out of stock all at once or not at all, and "Can produce now" goes from 112 to 96. The
    shelf doesn't change. The window then shows "2 batches started" · "16 units of Denim Jacket started. Materials taken out of
    stock." · **Done**.
  - Active, draft and archived products can be started, like a restock; a deleted product can't.
  - Batch numbers are B- followed by at least four digits, counted per workshop and never reused.
  - Short stock refuses the whole batch and shows a "Short by" column: "Not enough stock to cut 20 units. You can cut at most 12 now:
    Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer." or, when nothing can be cut, "You can't cut any now:
    restock Metal shank button 17mm first." A product with no composition can be started: nothing is taken.
  - Other refusals, keeping everything typed: "Choose a product." · "Enter at least one unit to start." · "Indigo M: Enter a whole number
    from 0 to 1,000,000." · "The colours or sizes of Denim Jacket have changed. Enter the units again." · "Couldn't start the batch. Try
    again." The button shows a spinner while saving.
- [ ] **AC-3 · Progress, all phases.** The sidebar reads Dashboard, Materials, Products, Restocks, Orders, **Progress** (factory icon),
  Clients, Sales. The page **Progress**, "The batches you are making for your shelf, phase by phase, in their colour and size.", has
  **Production phases** and **Start batch**, then the tiles "Phases": **All phases** 26 · **In cutting** 7 · **Sewing** 15 · **Finishing**
  4 units (the chosen tile is marked by more than its colour and announced as the current page; a tile at 0 still opens).
  - Filters **Product** ("All products") and **Colour** ("All colours"), kept in the page address. With Indigo: "Showing Indigo · 23 of
    26 units", **Clear filters**, and the tiles In cutting 4 · Sewing 15 · Finishing 4. There is no Client and no Order filter.
  - One card per product with units in production, A → Z, 10 a page: the name (a link), the SKU, "21 units in 6 batches", **Start
    batch**, and the table "Denim Jacket by phase": Colour · size, In cutting, Sewing, Finishing, **In production**, **On the shelf now**.
    Colour rows carry their subtotals, only colours and sizes in production have rows, a **Total** row ends it, a shelf of 0 reads
    **Out**. Indigo M: 4 · 6 · 4 · 14 · Out; Indigo S: 0 · 4 · 0 · 4 · 2; Total 7 · 10 · 4 · 21. Denim Jeans 5-pocket: "5 units in 1
    batch". A "One size" product has colour rows only. Every figure comes from the server.
  - States: "Nothing in production" · "Start a batch to make products for your shelf. They are added to your finished stock when you
    put them in stock." · **Start batch** (tiles at 0); "No batch matches these filters." · "Try another product or colour, or clear the
    filters." · **Clear filters**; loading placeholders for the tile counts and two cards; "Couldn't load the progress of your
    production." · **Try again**. Progress never mentions an order or a client.
- [ ] **AC-4 · One phase.** A tile opens "Progress › Sewing": the heading **Sewing**, "15 units · 3 batches · 2 products · oldest since
  26 Oct 2026", "From In cutting. Next: Finishing.", the filters kept, and the table "In Sewing", one row per batch, oldest first, 25 a
  page: **Since** ("26 Oct 2026" / "4 days"; "1 day"; "today") · **Batch** · **Product** (a link, SKU in grey) · **Colour · size** ·
  **Units**, then **Move to Finishing** (named "Move batch B-0146 Denim Jeans 5-pocket Indigo S to Finishing") and the menu "More moves
  for batch B-0146" with **Move back to In cutting**.
  - In cutting: the button is **Move to Sewing** (or **Put in stock** with no phase of its own); the menu has **Undo batch**; Since is
    the day the batch started. The last phase (Finishing): the main button **Put in stock**; the menu **Move back to Sewing**.
  - A deleted product reads "Denim Jacket (deleted)" with no link; its **Put in stock** can't be pressed, with the tip "Denim Jacket was
    deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials."
  - States: "Nothing in Sewing" · "Batches come here from In cutting with Move to Sewing." · **Show all phases**; "Nothing in Sewing
    matches these filters." · **Clear filters**; five placeholder rows while loading; "Couldn't load what is in Sewing." · **Try again**.
- [ ] **AC-5 · Moving a batch, and splitting it.** **Move to Finishing** on B-0149 opens "Move to Finishing · Denim Jacket", "B-0149 ·
  Indigo M · in Sewing: 6 units", "How many units?" ("Up to 6", empty, focused) and **Move units**. A batch moves one phase at a time;
  **Move back to …** uses the same window. Moves never touch materials or the shelf, and each move remembers its day.
  - Moving all 6: "6 units of Denim Jacket · Indigo M moved to Finishing." B-0149 keeps its number.
  - Moving 4 of 6 splits it: the 4 units become **B-0155** in Finishing since today; B-0149 keeps 2 in Sewing with its number and its
    day. Toast: "4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was split: the units moved are on B-0155."
  - Refusals: "You can move at most 6 units of this batch." · "Enter at least one unit to move." · "Enter a whole number of units." If
    someone moved the batch meanwhile: "These units have moved on since you opened this page." and **Close**; nothing moves.
- [ ] **AC-6 · Put in stock and Undo batch.** **Put in stock** on B-0148 opens "Put in stock · Denim Jacket", "B-0148 · Indigo M · in
  Finishing: 4 units", "How many units?" ("Up to 4") and "Adds to your finished stock: Indigo M goes from 0 to 4. Recorded as a restock
  "From production"." Confirming adds 4 to the Denim Jacket's Indigo M shelf (0 → 4), the batch leaves Progress, and Restocks lists a
  restock dated today, by whoever pressed it, Reference "B-0148", Note "From production". Toast: "4 units of Denim Jacket · Indigo M
  put in stock." with **See the restock**. Materials and "Can produce now" don't change.
  - Putting 3 of 4 in stock adds 3; B-0148 keeps 1 unit, its number and its phase.
  - That restock can be undone in Restocks like any other: the units leave the shelf, the batch doesn't come back and its materials
    stay used.
  - Refusals: "Enter at least one unit to put in stock." · "You can put at most 4 units of this batch in stock." · "Enter a whole number
    of units." · "Couldn't put the units in stock. Try again." No colour and size can go over 1,000,000 units on the shelf: "Indigo M
    can't go over 1,000,000 units on the shelf."
  - **Undo batch**, only for a batch In cutting, asks **Undo batch B-0150?** · "Takes the 4 units of Denim Jacket · Indigo M off Progress
    and gives back what was cut: 7.92 m Denim 12oz Indigo, 32 pc Metal shank button 17mm, 504 m Polyester thread and 4 pc Woven brand
    label." · **Undo batch** (red) · **Keep batch**. Toast: "Batch B-0150 undone. Materials returned to stock." It returns exactly what
    was taken at the cut, nothing for a material deleted or whose unit changed since. If the batch moved on meanwhile: "B-0150 has moved
    on since you opened this page, so it can't be undone. Move it back to In cutting first." Otherwise: "Couldn't undo the batch. Try
    again."
- [ ] **AC-7 · Edit product while units are in production.** Taking Light wash off the Denim Jacket (B-0151 and B-0152 hold 3 units) is
  refused: "Light wash has 3 units in production, so it can't be removed from Denim Jacket." (1 unit: "has 1 unit in production").
  Moving the Denim Jacket to a category with other sizes is refused: "Denim Jacket has units in production, so its category can't change
  to one with other sizes. Keep a category with sizes S, M, L, XL." The existing refusals (units on the shelf, on orders) stay as they
  are.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 12 commits
> Tech-lead (main session standing in, at the human's request) · 2026-11-02 · approved 2026-11-02
> Why complex: five new tables and a counter, material stock taken and given back under row locks (reusing ADR-0014's maths), a
> restock written from production, a new business rule (batches, splits, Undo only In cutting), nine new routes (three JSON) and a new
> contract on three pages.

### In short
Production gets records of its own. A workshop keeps its own **phases** (0 to 6, in order). **Start batch** locks the product and its
materials, makes the cut plan for the total units with today's cutting maths, takes the materials, remembers what it took in one
**production cut**, and writes one **batch** per colour and size, numbered from a counter on the workshop. A batch holds its units,
its phase (none = In cutting) and the day it got there. Moving part of a batch splits it into a new numbered batch; every move is
logged. **Put in stock** goes through INV-3's restock (Reference = the batch number, Note "From production"), so Restocks, its Undo
and the 1,000,000 limit work unchanged. **Undo batch** gives back the cut's remembered amounts for that batch's units, with the same
formula as Move back to To start, so the batches of one start always return exactly what it took. The Progress page reads it all
through one read-only service; every figure comes from the server. Order cutting is untouched.

### Steps

| Step        | What it does                                             | Proves     | Lane         | Who      | Size       |
| ----------- | -------------------------------------------------------- | ---------- | ------------ | -------- | ---------- |
| **INV-5-a** | The production tables, models, policies, ADR            | AC-1–AC-6   | CRITICAL     | Backend  | ~300 lines |
| **INV-5-b** | Production phases: add, rename, reorder, remove          | AC-1       | CRITICAL     | Backend  | ~350 lines |
| **INV-5-c** | Start batch: take the materials, write the batches       | AC-2       | CRITICAL     | Backend  | ~400 lines |
| **INV-5-d** | Move a batch forward or back, splitting it               | AC-5       | CRITICAL     | Backend  | ~300 lines |
| **INV-5-e** | Put in stock (a restock) and Undo batch                  | AC-6       | CRITICAL     | Backend  | ~350 lines |
| **INV-5-f** | The Progress read side: tiles, product cards, one phase  | AC-3, AC-4 | NON-CRITICAL | Backend  | ~400 lines |
| **INV-5-g** | Edit product refuses while units are in production       | AC-7       | NON-CRITICAL | Backend  | ~120 lines |
| **INV-5-h** | Settings › Production phases page                        | AC-1       | NON-CRITICAL | Frontend | ~350 lines |
| **INV-5-i** | The Progress page, all phases, and the sidebar entry     | AC-3       | NON-CRITICAL | Frontend | ~400 lines |
| **INV-5-j** | The one-phase view                                       | AC-4       | NON-CRITICAL | Frontend | ~300 lines |
| **INV-5-k** | The move, Put in stock and Undo batch windows            | AC-5, AC-6 | CRITICAL     | Frontend | ~380 lines |
| **INV-5-l** | The Start batch window                                   | AC-2       | CRITICAL     | Frontend | ~400 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
run their PostgreSQL tests; after the last wave the whole tree runs `composer test:pgsql`.

#### INV-5-a · The production tables, models, policies, ADR
**Commit:** `feat(production): phases, batches, production cuts and the batch counter (INV-5-a)`

- Migration `production_phases`: `workshop_id`, `name` (24), `position` (unsigned tiny integer), timestamps. Unique (`workshop_id`,
  `position`); PostgreSQL only: unique (`workshop_id`, `lower(name)`), `unique(workshop_id, id)`, CHECK `position BETWEEN 1 AND 6`.
- `production_cuts` (one per Start batch) and `production_cut_materials`: the same columns as `order_line_cuts` /
  `order_line_cut_materials` with `product_id` instead of the order line (`units`, `units_returned`, `user_id`; per material
  `unit_id`, `quantity_per_unit` 20,8, `quantity_taken` 14,4). Same CHECKs and composite keys as ADR-0014's tables.
- `production_batches`: `workshop_id`, `number` (unsigned integer), `product_id`, `colour_id`, `size_id` (`restrictOnDelete`),
  `production_cut_id` (`restrictOnDelete`), `split_from_id` (self, nullable), `units` (unsigned integer, what it holds now),
  `status` (`in_production` · `in_stock` · `undone`), `phase_id` (nullable = In cutting, `restrictOnDelete`; set to null when the
  batch leaves Progress), `phase_since` (date), `started_on` (date), `user_id`, `product_restock_id` (nullable, the last Put in stock),
  `finished_at`, `undone_at`, `undone_by` (nullable), timestamps. Unique (`workshop_id`, `number`); index (`workshop_id`, `status`,
  `phase_id`, `phase_since`, `id`) for the phase view; index (`product_id`, `status`). PostgreSQL only: composite keys to products,
  cuts, phases; CHECK `units BETWEEN 1 AND 1000000`, `number >= 1`.
- `production_batch_moves`: `workshop_id`, `production_batch_id`, `kind` (`start` · `forward` · `back` · `stock` · `undo`), `units`,
  `from_label`, `to_label` (names at the time, so removing a phase never breaks history), `user_id`, `created_at`. Kept for "since"
  and for INV-15's "Your scans today"; nothing reads it yet besides tests.
- `workshops.last_batch_number` (unsigned integer, default 0).
- Models `ProductionPhase`, `ProductionCut`, `ProductionCutMaterial`, `ProductionBatch` (`number_label` "B-0147": at least four
  digits), `ProductionBatchMove`, all `BelongsToWorkshop`; enum `ProductionBatchStatus`; `ProductionPhasePolicy`,
  `ProductionBatchPolicy` (any member; another workshop is a 404 by the scope); factories.
- The tech-lead writes in this commit: ADR-0023 "Production batches for the shelf", `docs/ARCHITECTURE.md` (the tables, the lock
  order, the routes) and the costing skill (glossary: Batch, Production phase, Production cut; "Start batch and Undo batch" under
  "Cutting and returning stock", with the worked example 16 units → 31.68 m … and 112 → 96).

**Tests**
- 1 PostgreSQL schema test: a batch pointing at another workshop's product, cut or phase is refused; units 0, a second batch number
  in one workshop, a seventh position and a duplicate name in other capitals are refused.
- The workshop arch test lists the five models; 1 policy dataset (guest · member · other workshop 404).

> [!example]- Files (≈14)
> - `.claude/skills/invory-costing-domain/SKILL.md`, `docs/adr/0023-production-batches-for-the-shelf.md` (new), `docs/ARCHITECTURE.md`
> - `database/migrations/2026_11_04_100000_create_production_tables.php` (new)
> - `app/Models/{ProductionPhase,ProductionCut,ProductionCutMaterial,ProductionBatch,ProductionBatchMove}.php` (new),
>   `app/Enums/ProductionBatchStatus.php` (new), `app/Policies/{ProductionPhase,ProductionBatch}Policy.php` (new)
> - `database/factories/…Factory.php` (new, 4)
> - `tests/Feature/Production/ProductionSchemaTest.php` (new), `tests/Arch/WorkshopScopeTest.php`

#### INV-5-b · Production phases: add, rename, reorder, remove
**Commit:** `feat(production): the workshop's production phases in Settings (INV-5-b)`

- Actions `AddProductionPhase`, `RenameProductionPhase`, `MoveProductionPhase` (up or down: swaps with its neighbour),
  `RemoveProductionPhase`. Each locks the workshop row, then the phases, re-checks under the lock and refuses with the card's
  sentences: empty name, over 24, a name already used (ignoring capitals), "In cutting" or "Put in stock" (ignoring capitals), a
  seventh phase; a move or removal touching a phase that holds units ("Sewing holds 15 units, so it can't be moved or removed. Move
  them on or back first."); "Quality check now holds 2 units, so it can't be removed.". Positions stay 1…n with no gap.
- `App\Services\Production\ProductionMessages`: every text of the production pages (as `RestockMessages`).
- `Settings\ProductionPhaseController` (index, store, update, destroy) and `Settings\ProductionPhaseOrderController@update` (`PATCH
  settings/production-phases/{phase}/position`, `direction` up|down); toasts "Phase added." · "Phase updated." · "Phase removed." ·
  "Phases reordered.". Props: `phases` (with the units each holds and its refusal, null when free). Stub page
  `settings/production-phases`; the `domain.ts` types of the Contract.

**Tests**
- Add Sewing then Finishing; every refusal with its field and text; a seventh refused; rename to "sewing" refused, to its own name in
  other capitals accepted; move up/down and the positions; removing an empty phase; removing or moving one holding units refused, also
  when a batch arrives between the page and the save. Another workshop's phase is a 404.

> [!example]- Files (≈10)
> - `app/Actions/Production/{Add,Rename,Move,Remove}ProductionPhase.php` (new), `app/Services/Production/ProductionMessages.php` (new)
> - `app/Http/Controllers/Settings/ProductionPhase{,Order}Controller.php` (new), `app/Http/Requests/Production/…` (new, 2)
> - `routes/settings.php`, `resources/js/types/domain.ts`, `resources/js/pages/settings/production-phases.tsx` (stub)
> - `tests/Feature/Production/ProductionPhasesTest.php` (new)

#### INV-5-c · Start batch: take the materials, write the batches
**Commit:** `feat(production): Start batch takes the materials and writes one batch per colour and size (INV-5-c)`

- `StartBatchRequest`: `product_id` a product of the workshop (deleted included through `WorkshopRule`, so the Action names it),
  `units` colour id → size id → whole number 0 to 1,000,000, at least one above 0. Messages are the card's ("Choose a product." ·
  "Enter at least one unit to start." · "Indigo M: Enter a whole number from 0 to 1,000,000.").
- `App\Actions\Production\StartBatch`: one transaction. Locks the product (the lock Update product, Update stock and Delete product
  take), refuses a deleted product, checks the grid is exactly its colours and sizes (`UpdateFinishedStock::matches`, "The colours or
  sizes of Denim Jacket have changed. Enter the units again."), makes the plan for the total through `CuttingPreview::plan(…, lock:
  true)` (materials in ascending id), refuses a short plan with today's sentence on `units`, writes the production cut and its
  remembered materials, one `cutting` stock movement per material (product and units, no order), then locks the workshop row,
  reserves n numbers from `last_batch_number`, and writes the batches (In cutting, since today) in colour list order then size order,
  each with a `start` move. Returns the batches for the flash `batches_started`.
- JSON (ADR-0009 rule 5): `progress.products.line` (`GET progress/products/{product}`: the restock line plus "Can produce now" and
  the bottleneck) and `progress.products.materials` (`GET progress/products/{product}/materials?units=16`: the materials table, short
  rows and the short message, from `CuttingPreview::plan` without locks). The product picker is `restocks.products.options` (every
  non-deleted product, any status: the human's answer of 2026-11-02).
- `ProductionBatchController@store` (`POST progress/batches`) → back with the flash.

**Tests**
- AC-2's example end to end: B-0153 Indigo M 6 and B-0154 Indigo L 10, In cutting since today; the four materials down by 31.68 m,
  128 pc, 2,016 m, 16 pc; four `cutting` entries; "Can produce now" 96; the shelf, orders and sales unchanged.
- Refused, nothing written: 20 with 100 buttons (at most 12, "Short by" 60 pc), 0 possible, a deleted product, a colour taken off
  meanwhile, nothing typed, 1,000,001. A product with no composition starts with nothing taken. Draft and archived products start.
- Numbers: two workshops each get B-0001; a second start continues; B-10000 has five digits. The two JSON routes; another
  workshop's product is a 404.

> [!example]- Files (≈9)
> - `app/Actions/Production/StartBatch.php` (new), `app/Http/Requests/Production/StartBatchRequest.php` (new)
> - `app/Http/Controllers/{ProductionBatch,ProductionProductLine,ProductionProductMaterials}Controller.php` (new),
>   `app/Http/Resources/{StartBatchLine,StartBatchPreview}Resource.php` (new)
> - `routes/web.php`, `resources/js/types/domain.ts`
> - `tests/Feature/Production/StartBatchTest.php` (new)

#### INV-5-d · Move a batch forward or back, splitting it
**Commit:** `feat(production): move a batch one phase on or back, splitting it when part of it moves (INV-5-d)`

- `MoveBatchRequest`: `from_phase` and `to_phase` ('cutting' or a phase id, what the page showed), `units` whole ≥ 1.
- `App\Actions\Production\MoveBatch`: locks the batch, then the destination phase. Refuses with "These units have moved on since you
  opened this page." (under `moved`) when the batch is no longer in production or in `from_phase`, or `to_phase` is no longer the next
  (or previous) phase; with "You can move at most 6 units of this batch." when it holds fewer. All units: the batch changes phase,
  `phase_since` = today. Part of it: the batch keeps the rest, its phase and its day; the units moved become a new batch (next number,
  same cut, colour, size, `split_from_id`), in the destination since today. Writes a `forward` or `back` move. Moves never touch
  materials or the shelf. Forward from the last phase is not a move: it is Put in stock (INV-5-e).
- `ProductionBatchMoveController@store` (`POST progress/batches/{batch}/moves`) → back with the card's toast.

**Tests**
- AC-5's examples: 6 of B-0149 → Finishing (number kept); 4 of 6 → B-0155 in Finishing since today, B-0149 keeps 2 in Sewing since
  28 Oct; back to In cutting; each refusal with its text; moved meanwhile (phase changed, a phase inserted or removed); materials,
  shelf and restocks unchanged. Another workshop's batch is a 404.

> [!example]- Files (≈6)
> - `app/Actions/Production/MoveBatch.php` (new), `app/Http/Requests/Production/MoveBatchRequest.php` (new),
>   `app/Http/Controllers/ProductionBatchMoveController.php` (new), `routes/web.php`
> - `tests/Feature/Production/MoveBatchTest.php` (new)

#### INV-5-e · Put in stock (a restock) and Undo batch
**Commit:** `feat(production): put a batch in stock as a restock, or undo it while in cutting (INV-5-e)`

- `App\Actions\Production\PutBatchInStock`: locks the batch, refuses if it moved on (`moved`), more than it holds, or a deleted
  product ("Denim Jacket was deleted, so it can't be put in stock. …"); then calls `RecordRestock` in the same transaction with
  today, Reference "B-0148", Note "From production", the batch's colour and size only (the product's other cells 0). A refusal over
  1,000,000 is reported under `units` with "Indigo M can't go over 1,000,000 units on the shelf.". All units: status `in_stock`,
  `phase_id` null, `finished_at`, `product_restock_id`. Part: the batch keeps the rest. A `stock` move. The toast carries the action
  **See the restock** → `restocks.index?restock=<id>` (`FlashToast` gains an optional `action`).
- `App\Actions\Production\UndoBatch`: locks the batch, refuses unless it is In cutting and in production ("B-0150 has moved on since
  you opened this page, so it can't be undone. Move it back to In cutting first." on `undo`), locks its cut, gives back through
  `CuttingCalculator::giveBack` on that one cut with the batch's units (the cut's `units − units_returned` as outstanding, so the
  batches of one start return exactly what it took), writes `cutting_return` movements, `units_returned`, status `undone`, `undone_at`
  and `undone_by`, an `undo` move. Nothing comes back for a material deleted or whose unit changed.
- JSON `progress.batches.undo-preview` (`GET progress/batches/{batch}/undo`): the rows the sheet lists. Controllers
  `ProductionBatchStockController@store`, `ProductionBatchUndoController@store`.

**Tests**
- AC-6: B-0148 → Indigo M 0 → 4, the restock (date, by, Reference, Note, one line), the batch gone from Progress; 3 of 4; the 999,998
  + 4 refusal; a deleted product refused; undoing that restock in Restocks leaves the batch `in_stock` and the materials used.
- Undo B-0150: 7.92 m, 32 pc, 504 m, 4 pc back, four `cutting_return` entries; B-0153 and B-0154 undone one after the other give back
  exactly the 16-unit cut; a batch split before undo; a material deleted since; a batch in Sewing refused.

> [!example]- Files (≈9)
> - `app/Actions/Production/{PutBatchInStock,UndoBatch}.php` (new), `app/Http/Requests/Production/PutBatchInStockRequest.php` (new)
> - `app/Http/Controllers/ProductionBatch{Stock,Undo,UndoPreview}Controller.php` (new), `routes/web.php`,
>   `resources/js/types/{domain,ui}.ts`
> - `tests/Feature/Production/{PutBatchInStock,UndoBatch}Test.php` (new)

#### INV-5-f · The Progress read side: tiles, product cards, one phase
**Commit:** `feat(production): the Progress page's data, all phases and one phase (INV-5-f)`

- `App\Services\Production\ProgressBook`: the path (In cutting, the phases); the tiles, all and filtered (one grouped query); the
  product cards (products with units in production, A → Z, 10 a page; per colour and size the units per phase, the row totals, the
  colour subtotals, the Total row, and the shelf from `product_size_stocks` in one query for the page); the phase view (batches in
  the phase, oldest `phase_since` then id, 25 a page, with "days since" counted on the server, the units, batches, products and oldest
  day of the heading). Filters `product` and `colour`, kept in the address; `has_batches` tells "Nothing in production" from no match.
- `ProgressController@index` (`GET progress`, `?phase=cutting|<id>&product=&colour=&page=`); an unknown phase is a 404. Resources and
  the `domain.ts` types; stub page `progress/index`.

**Tests**
- The 7 example batches: tiles 26 · 7 · 15 · 4 and, with Indigo, 23 (4 · 15 · 4); the Denim Jacket card rows of AC-3 (Indigo M 4 · 6 ·
  4 · 14 · shelf 0; Total 7 · 10 · 4 · 21); "One size" rows; Sewing: B-0146, B-0147, B-0149 with 4, 3, 2 days and the heading;
  In cutting "since" is the start day; a deleted product's batch; undone and finished batches absent; queries flat for 10 cards and 25
  rows; another workshop's batches never counted.

> [!example]- Files (≈8)
> - `app/Services/Production/ProgressBook.php` (new), `app/Http/Controllers/ProgressController.php` (new)
> - `app/Http/Resources/{ProgressProductCard,ProgressBatch}Resource.php` (new), `routes/web.php`
> - `resources/js/types/domain.ts`, `resources/js/pages/progress/index.tsx` (stub)
> - `tests/Feature/Production/ProgressPageTest.php` (new)

#### INV-5-g · Edit product refuses while units are in production
**Commit:** `feat(production): a colour or category can't change while units are in production (INV-5-g)`

- `ProductColourLock` also refuses a colour with batches in production ("Light wash has 3 units in production, so it can't be removed
  from Denim Jacket.", singular for 1). `ProductSizeLock` adds "Denim Jacket has units in production, so its category can't change to
  one with other sizes." with the existing ending. Both still run in the request and under `UpdateProduct`'s lock; Start batch takes
  the same product lock, so neither can slip between the check and the save. Order of messages: see Decisions.

**Tests**
- Light wash refused with 3, with 1; a colour whose batches were all put in stock or undone comes off; the category refused; the
  existing shelf and order refusals unchanged.

> [!example]- Files (4)
> - `app/Services/Products/{ProductColourLock,ProductSizeLock}.php`
> - `tests/Feature/Products/{ProductColourLockTest,ProductSizeLockTest}.php`

#### INV-5-h · Settings › Production phases page
**Commit:** `feat(production): the Settings › Production phases page (INV-5-h)`

- Page `settings/production-phases`: the fixed In cutting and Put in stock rows (grey, lock icon), the phases with **Move up**,
  **Move down**, **Edit**, **Remove** (disabled, with the server's reason in a tooltip reachable by Tab), **Add phase** /
  **Edit** dialog (Name, hint, **Save phase**), the Remove confirm, the "No phase of your own" state; the Settings menu gains the group
  **Production**. Phone: one card per phase, 44 px icon buttons.

**Tests**
- Vitest: the path with Sewing and Finishing, the disabled buttons and their tip, add/edit/remove flows posting the right routes,
  each server error shown under Name, the empty state, the menu group.

> [!example]- Files (≈6)
> - `resources/js/pages/settings/production-phases.tsx`, `…/production-phases.test.tsx` (new)
> - `resources/js/components/production/phase-dialog.tsx` (new), `resources/js/layouts/settings/layout.tsx` (+ test)

#### INV-5-i · The Progress page, all phases, and the sidebar entry
**Commit:** `feat(production): the Progress page with its tiles, filters and a table per product (INV-5-i)`

- Sidebar: **Progress** (factory icon) after Orders. Page `progress/index`: header and buttons, the tiles (a group named "Phases",
  `aria-current="page"` on the chosen one, 2 px border and bold count, not colour alone), the Product (restock picker in filter mode)
  and Colour filters in the address, "Showing Indigo · 23 of 26 units", the product cards and their tables, the empty, no-match,
  loading and error states, the phone layout (tiles two to a row, Filters panel, lines per size).

**Tests**
- Vitest: the example tiles and cards, Out in amber with its word, the filtered line and Clear filters, the states, the sidebar order.

> [!example]- Files (≈8)
> - `resources/js/pages/progress/index.tsx` (+ test), `resources/js/components/production/{phase-tiles,progress-product-card,progress-filters}.tsx` (new, + tests)
> - `resources/js/components/app-sidebar.tsx` (+ test)

#### INV-5-j · The one-phase view
**Commit:** `feat(production): the view of one phase, one row per batch (INV-5-j)`

- With `?phase=`: breadcrumb "Progress › Sewing", heading and the grey path line, the table "In Sewing" (Since with the server's
  days, Batch, Product link, Colour · size, Units, the forward button named in full, the "⋯" menu "More moves for batch B-0146"), the
  In cutting and last-phase variants, the deleted-product row with its disabled Put in stock and tooltip, the states, and the phone
  cards.

**Tests**
- Vitest: Sewing's three rows, the button names, each phase's menu items, the deleted product, the empty, no-match and error states.

> [!example]- Files (≈4)
> - `resources/js/components/production/{phase-view,batch-row-actions}.tsx` (new, + tests), `resources/js/pages/progress/index.tsx`

#### INV-5-k · The move, Put in stock and Undo batch windows
**Commit:** `feat(production): move, put in stock and undo a batch from Progress (INV-5-k)`

- One window for **Move to …** and **Move back to …** ("How many units?", "Up to 6", focused, **Move units**); **Put in stock** with
  its note (the shelf now from the row plus the typed units, as Restock's page adds its typed counts); the **Undo batch** sheet listing
  what comes back (from the undo preview, formatted on the page). Spinners keep their label; server errors under the field; the
  "changed meanwhile" state with **Close**; toasts, with **See the restock** as a toast action (`use-flash-toast`).

**Tests**
- Vitest: each window's text for B-0149, B-0148 and B-0150, the posted payloads (`from_phase`, `to_phase`, `units`), each error,
  moved meanwhile, the toast action link.

> [!example]- Files (≈7)
> - `resources/js/components/production/{move-batch-dialog,put-in-stock-dialog,undo-batch-sheet}.tsx` (new, + tests)
> - `resources/js/hooks/use-flash-toast.ts` (+ test)

#### INV-5-l · The Start batch window
**Commit:** `feat(production): the Start batch window (INV-5-l)`

- From the header (product to choose) and a product card (product chosen): the picker with "Can produce now 112", the grid with
  "Shelf 2" / "Shelf Out", "Total 16 units · 2 batches (Indigo M 6, Indigo L 10)" (the typed counts added on the page), the materials
  table from `progress.products.materials` (debounced, with "Short by" and the short message), **Start batch** with a spinner, the
  errors keeping what was typed, and the result view ("2 batches started" …, **Done**) read from the flash. Full screen on a phone
  with the fixed bar; the short materials table there.

**Tests**
- Vitest: the example grid and totals, the materials rows, the short case, each error, the result view, the phone layout.

> [!example]- Files (≈5)
> - `resources/js/components/production/{start-batch-dialog,start-batch-grid}.tsx` (new, + tests), `resources/js/pages/progress/index.tsx`

### Contract (frozen before dispatch)
```ts
export type PhaseRef = 'cutting' | number;                                       // In cutting has no row
export type ProductionPhase = { id: number; name: string; position: number; units: number; refusal: string | null };
export type ProductionPhasesProps = { phases: ProductionPhase[] };
export type ProgressStep = { phase: PhaseRef; name: string };                    // the path: In cutting, then the phases
export type ProgressTile = ProgressStep & { units: number };
export type ProgressFilters = { phase: PhaseRef | null; product: number | null; colour: number | null };
export type ProgressRow = { colour: Colour; size: Size | null; by_phase: number[]; in_production: number;
    on_shelf: number | null };                                                   // size null = the colour's subtotal row, no shelf
export type ProgressProductCard = { product: RestockProductRef; units: number; batches: number; one_size: boolean;
    rows: ProgressRow[]; total: { by_phase: number[]; in_production: number } }; // by_phase in path order
export type ProgressBatch = { id: number; number: string; product: RestockProductRef; colour: Colour; size: Size; one_size: boolean;
    units: number; since: string; since_days: number; on_shelf: number };       // 'YYYY-MM-DD'; 0 = "today"
export type ProgressPhaseView = { phase: ProgressStep; previous: ProgressStep | null; next: ProgressStep | null; // next null = Put in stock
    units: number; batch_count: number; product_count: number; oldest_since: string | null; batches: Paginated<ProgressBatch> };
export type ProgressIndexProps = { path: ProgressStep[]; tiles: ProgressTile[]; all_units: number; total_units: number;
    filters: ProgressFilters; product_filter: RestockProductRef | null; colours: Colour[]; has_batches: boolean;
    products: Paginated<ProgressProductCard> | null; phase_view: ProgressPhaseView | null };
export type StartBatchLine = RestockLine & { can_produce: number | null; bottleneck: string | null };
export type StartBatchPreview = { units: number; rows: MaterialMoveRow[]; has_composition: boolean; max_cuttable: number | null;
    short_message: string | null };
export type UndoBatchPreview = { rows: MaterialMoveRow[]; nothing_taken: boolean };
export type BatchesStarted = { product: string; units: number;
    batches: { number: string; colour: string; size: string | null; units: number }[] }; // flash 'batches_started'
export type StartBatchFormData = { product_id: number | null; units: Record<number, Record<number, string>> };
export type MoveBatchFormData = { from_phase: PhaseRef; to_phase: PhaseRef; units: string };
export type PutInStockFormData = { from_phase: PhaseRef; units: string };
// Changed (types/ui.ts)
FlashToast += { action?: { label: string; href: string } };
```
Errors: `name`, `phase` (phases) · `product_id`, `units`, `units.{colour}.{size}` (Start batch) · `units`, `moved` (moves, Put in
stock) · `undo`. Routes: `progress.index` GET progress · `progress.batches.store` POST progress/batches ·
`progress.batches.moves.store` POST progress/batches/{batch}/moves · `progress.batches.stock` POST progress/batches/{batch}/stock ·
`progress.batches.undo` POST progress/batches/{batch}/undo · `progress.batches.undo-preview` GET progress/batches/{batch}/undo (JSON) ·
`progress.products.line` GET progress/products/{product} (JSON) · `progress.products.materials` GET
progress/products/{product}/materials (JSON) · `settings.production-phases.{index,store,update,destroy}` and
`settings.production-phases.position` PATCH settings/production-phases/{phase}/position. Bound with the digit constraint.

### Decisions
- **One production cut per Start batch, shared by its batches.** The plan is made once for the total (the card's "Materials for 16
  units"), so it is checked and rounded once per material, as today. Each batch, and each batch split from it, points at that cut;
  Undo returns `held(r) − held(r − k)` from it, ADR-0014's formula, so undoing every batch of a start gives back exactly what it took.
  Rejected: a cut per batch (16 units could round differently from 6 + 10, and the check would no longer match the window).
- **The maths is reused, not copied.** `CuttingCalculator` (pure) and `CuttingPreview::plan` serve both orders and batches; the
  short-stock sentence stays `OrderMessages::notEnoughStock`. INV-14 removes order cutting and can move them to a neutral namespace.
- **Put in stock is a restock.** It calls `RecordRestock` inside its own transaction, so the shelf, the Restocks list, Undo restock and
  the 1,000,000 limit have one writer and one rule.
- **Batch numbers come from a counter on the workshop row**, locked last (after products and materials), as barcodes already do.
  Numbers are never reused; an undone batch keeps its number.
- **Lock order:** batch → phase · product → materials → workshop · workshop → phases. Nothing locks in the other direction, so there
  is no deadlock. Moves lock only the batch and the destination phase.
- **Phases are deleted for real** (only when empty, and no batch in production points at them); history keeps their names as text.
- **A stale page is refused, never guessed:** a move carries the phase it came from and the phase it goes to, so a phase inserted,
  removed or reordered meanwhile gives "These units have moved on since you opened this page.".
- **Whole units as integers**, as INV-3; the page adds typed counts for "Total 16 units" and "goes from 0 to 4", as Restock and Update
  stock do. Material quantities stay decimal strings from the server.
- **The human's answers, 2026-11-02 (the recommended defaults):** a phase holding units keeps its place, so moving an empty phase
  past it is refused with that phase's sentence; when nothing can be cut, Start batch shows today's full sentence ("Not enough stock to
  cut 20 units. You can't cut any now: restock … first."); a colour with units both on the shelf and in production shows only the shelf
  sentence, and the production sentence once the shelf is at 0.
- **The material history is unchanged:** a batch's cut reads "−31.68 m · 16 × Denim Jacket" (no client), its return
  "+7.92 m · 4 × Denim Jacket".

### Order
Wave 1 (backend, one after another: they share `routes/web.php` and `domain.ts`): INV-5-a → INV-5-b → INV-5-c → INV-5-d → INV-5-e →
INV-5-f → INV-5-g. Wave 2 (frontend, after wave 1's types land): INV-5-h in parallel with INV-5-i → INV-5-j → INV-5-k → INV-5-l
(same page). Merge order is the step order.

### Risks
- **Two Start batch on products sharing a material** → materials locked in ascending id after the product, as order cuts do; a
  concurrency test in INV-5-c.
- **Order cuts and batch cuts coexist until INV-14** → both take materials under the same locks and the same maths; "Can produce now"
  reads the stock either way.
- **A restock refusal inside Put in stock** → reported under `units` with the card's sentence; the whole move rolls back.
- **CHECKs and composite keys only on PostgreSQL** → the Actions enforce the same rules; the parity suite proves the database side.
- **Browser tests can't run on this machine** ("Playwright is outdated", see INV-3) → Vitest and feature tests carry the proof; a
  browser golden path is written only if the tooling is fixed.
