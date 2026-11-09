---
id: INV-21
title: Material stock per colour
type: feature
phase: Colour × size variants
priority: Must
size: XL
status: Ready
created: 2026-11-08
tags:
  - story
  - variants
---

# INV-21 · Material stock per colour

> [!abstract] Description
> As a **workshop owner**, I want **a material such as fabric to keep its stock, and if I choose its unit cost, per colour**, so that
> **Denim 12oz is one material with Indigo 250 m and Black 40 m, and "Can produce now", Start batch, Restock and the dashboard use
> the stock of each product colour's own colour**. Each material is either **One stock for all colours** (today, unchanged; buttons,
> thread, labels) or **Stock per colour**. A per-colour material can also be set to **Cost per colour**. A product's colour is matched
> to the material's colour by the colour itself, from Settings › Colours.
> New: the two settings and the Colours table on Add and Edit material; "Stock by colour" on the material page; "2 colours" on the
> Materials list; a Restock sheet with a quantity per colour; the windows that confirm a switch; the "no stock for this colour"
> state with its **Add Light wash to Denim 12oz** window. Changed: the product page, Start batch and Undo batch, a material's "Used
> in" table, the material's history, the dashboard's "Needs restock" and the Inventory value. The pages are drawn in
> [[INV-21-material-stock-per-colour-ui]].
> Left out on purpose: merging materials such as "Denim 12oz Indigo" and "Denim 12oz Black" (the owner fixes those by hand), a hint
> that two materials look alike, a reorder level per colour, and any change to Settings › Colours. Doesn't change the colour × size
> grid of INV-16: a line still counts by colour and size as drawn there; only where its stock and cost are read changes.

> [!info] How it works today
> A material has one quantity in stock and one unit cost. Fabric bought by colour is two materials, "Denim 12oz Indigo" (250 m) and
> "Denim 12oz Black" (40 m), and the Denim Jacket's composition lists both, each line only for its own colour. The material's
> **Colour** field is free text ("Antique brass"), not a colour of the list. Restock adds one quantity, at one price, to one stock:
> "Restocked Denim 12oz Indigo: +100 m." The product page says "Bottleneck: Denim 12oz Black (Black, size XL)." and Start batch says
> "Short on Denim 12oz Black." Nothing tells the system that Indigo denim and Black denim are the same fabric.

## Acceptance criteria

**Denim 12oz** (fabric, metres, reorder at 100 m): **Indigo 250 m** and **Black 40 m**, **Stock per colour**. With **Cost per colour**:
Indigo TND 8.500 per m, Black TND 9.200 per m, so the stock value is Indigo TND 2,125.000 + Black TND 368.000 = **TND 2,493.000**.
The **Denim Jacket** (colours Indigo and Black, sizes S · M · L · XL; labor TND 12.000, overhead TND 3.500, selling price TND 89.000)
has one denim line on Denim 12oz, **By colour and size**, with cells under both colours: S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000 m,
waste 10 %. Metal shank button 17mm 8 pc (stock 900), Polyester thread 120 m at 5 % waste (stock 20,000) and Woven brand label 1 pc
(stock 400) are **One stock for all colours** and **Same for every variant**, as today. Can produce, per variant: Indigo S 112 · M 112
· L 112 · XL 103; Black S 22 · M 20 · L 18 · XL 16 (INV-16's figures, the same when the quantities are). The wording is that of
[[INV-21-material-stock-per-colour-ui]].

- [ ] **AC-1 · The two settings.** On Add and Edit material, under Stock and cost, **How is stock kept?** offers **One stock for all
  colours** ("For buttons, thread, labels. One quantity, whatever the colour.", chosen on a new material) and **Stock per colour**
  ("For fabric and leather. A quantity for each colour, such as Indigo 250 m and Black 40 m.").
  - With Stock per colour chosen, **How is cost set?** appears with **Same cost for every colour** ("One unit cost for the whole
    material.", chosen) and **Cost per colour** ("Each colour has its own unit cost, such as Indigo TND 8.500 and Black TND 9.200.").
    With One stock chosen it isn't shown at all.
  - One stock shows today's **Unit cost** and **Quantity in stock** fields. Stock per colour with the same cost shows **Unit cost**
    and the **Colours** table (Colour, Quantity in stock). Cost per colour shows the table with a **Unit cost** column and no single
    Unit cost field.
  - **Colour** (free text) in Identity is hidden while Stock per colour is chosen, and its text is kept for when the owner
    switches back. A one-stock material shows it as today.
  - **Reorder at** is one field; for a per-colour material its hint reads "Applies to each colour. The material shows Low stock when
    any colour is at or under this level."
- [ ] **AC-2 · Colours of a material.** The table is headed "Colours" with "From Settings › Colours. A product's colour takes its
  material from the same colour."; each row is the colour's swatch and name, **Quantity in stock** (with the unit) and, with cost
  per colour, **Unit cost**, and a **Remove** button. **Add colour** opens the searchable list of Add product's Colours field,
  offering only colours the material doesn't have yet; a colour added is a new row at 0.
  - On a cost-per-colour material the new colour's **Unit cost** is empty and required: "Black: Enter a unit cost."
  - A new per-colour material shows "No colours yet. Add the colours you buy this material in." with **Add colour**. Saving a
    per-colour material with no colour reads "Add at least one colour, or choose One stock for all colours." under the table.
  - A quantity that isn't a number from 0, with up to 4 decimals, reads "Black: Enter a number from 0, with up to 4 decimals."
    under its field; focus goes to the first error and the typed values stay.
  - Saving shows "Material added." or "Material updated.", as today.
- [ ] **AC-3 · Switching to Stock per colour with stock.** On Edit Denim 12oz with 250 m in one stock, choosing **Stock per colour**
  replaces **Quantity in stock** with the Colours table holding one row, a picker "Choose a colour", and the note "You have 250 m in
  stock now. Choose the colour that holds it. Colours you add start at 0." Choosing Indigo and adding Black at 40 saves Indigo 250 m
  and Black 40 m. Saving without a colour chosen reads "Choose the colour that holds the 250 m you have now." A material at 0 shows no
  note and an empty table. Nothing is saved until **Save material**; **Cancel** discards the switch.
- [ ] **AC-4 · Stock by colour on the material page.** Denim 12oz's page shows **In stock, all colours** 290 m ("2 colours"), **Reorder
  at** 100 m ("each colour"), and the card **Stock by colour** ("Each colour has its own stock. A product takes its material from its
  own colour.") with Indigo 250 m "In stock" and Black 40 m "Low stock", then "All colours 290 m", colours in the order of Settings ›
  Colours. With cost per colour the card adds **Unit cost** (TND 8.500 / m, TND 9.200 / m) and **Stock value** (TND 2,125.000, TND
  368.000, total TND 2,493.000), and the figures show **Unit cost** "TND 8.500–9.200 / m" ("per colour") and **Stock value** TND
  2,493.000. Details leaves out the free-text Colour. With more than 8 colours "3 of 20 colours need restock" and the switch **Only
  colours that need restock** appear; the switch filters and never reorders.
- [ ] **AC-5 · Status of a per-colour material.** The material is **Low stock** when any colour is at or under the reorder level (0
  counts), **Out of stock** only when every colour is at 0, otherwise **In stock**. With no reorder level the material is never
  **Low stock**; it is **Out of stock** only when every colour is at 0. The callout names the colours: "Black is below
  reorder level." with "40 m on hand, reorder at 100 m."; "3 colours are at or below reorder level." with "Black 40 m, Camel 0 m
  and Rust 12 m. Reorder at 100 m."; "Camel is out of stock." with "0 m on hand, reorder at 100 m." A colour that is fine shows no
  badge on the list tiles.
- [ ] **AC-6 · Materials list.** Denim 12oz's row shows **290 m** in bold with the button **2 colours** ("1 colour" for one), named
  "Show stock by colour for Denim 12oz"; pressing it opens the panel "Stock by colour · Denim 12oz" (a tile per colour: swatch and
  name, quantity, a badge only when Low or Out; footer "Reorder at 100 m for each colour."; **Close**). **Reorder at** reads "100 m"
  with "each colour"; **Status** shows the badge and, under it, "Black"; with four or more, the first three and a count ("Camel,
  Black, Light wash and 2 more"). The filter **Needs restock** includes a material where any colour is low or out; **Stock
  low→high** sorts by the total. A one-stock material's row is as today.
- [ ] **AC-7 · Restock per colour.** **Restock** on Denim 12oz (from its page, the list, or a colour's **Restock Black** button)
  opens a side sheet "Restock Denim 12oz" with "In stock now: 290 m in 2 colours · cost per colour", **Date**, **Reference**, and one
  line per colour (**Quantity received**, and with cost per colour **Unit price paid**, showing "Now 40 m"). Typing Indigo 100 and
  Black 20 and pressing **Add to stock** shows **"Restocked Denim 12oz: Indigo +100 m, Black +20 m."**, makes Indigo 350 m and Black
  60 m, and closes the sheet; one colour reads "Restocked Denim 12oz: Indigo +100 m."
  - Opened from "Restock Black", that colour's quantity field has focus. Lines left empty are not in the receipt; the footer reads
    "2 colours in this receipt". With more than 8 colours **Find colour** appears and a typed quantity stays when it hides its line.
  - With the same cost, one **Unit price paid** sits above the lines. With cost per colour each colour has its own.
  - The checkbox stays checked by default: **Use this price as the new unit cost**, or with cost per colour **Use these prices as
    the new unit costs**, which changes only colours that received a quantity: Indigo at TND 8.800 and Black at TND 9.200 make
    Indigo TND 8.800 and leave Black at TND 9.200.
  - Errors: "Enter a quantity for at least one colour." · "Indigo: Enter a number above 0, with up to 4 decimals." · "Black: Enter
    the price paid." · "The colours of Denim 12oz have changed. Enter the quantities again."; nothing is saved and typed values stay.
  - A one-stock material keeps today's Restock, unchanged.
- [ ] **AC-8 · Removing and correcting a colour.** **Remove Black** works only for a colour at 0 with no history. Any other colour's
  Remove is greyed but stays reachable by keyboard and says why: "Indigo has 250 m in stock, so it can't be removed." or "Black has
  history, so it can't be removed." (on a phone, written under the colour's name). A colour's quantity can be corrected on Edit
  material; it is logged as a correction for that colour.
- [ ] **AC-9 · Cost per colour on and off.** Choosing **Cost per colour** on Denim 12oz at TND 8.500 shows the cost column with every
  colour at 8.500 and "Each colour starts at the current cost, TND 8.500 / m. Change the ones that differ." with no window. Choosing
  **Same cost for every colour** with Indigo 8.500 and Black 9.200 opens **"Use the same cost for every colour?"** ("Choose the cost
  to keep. Every colour will use it, and the stock value changes with it.") with the list "Indigo · TND 8.500 / m", "Black · TND 9.200
  / m", none chosen; **Use this cost for all colours** works only once one is chosen ("Choose the cost to keep." stays on screen);
  **Keep cost per colour** closes the window and puts the choice back. Keeping Indigo's makes both TND 8.500 and the stock value TND
  2,465.000. A change of cost is logged as a price change for the colour.
- [ ] **AC-10 · Switching back to One stock.** Choosing **One stock for all colours** on Denim 12oz opens **"Switch to one stock for
  all colours?"** with "**Indigo 250 m + Black 40 m become one stock of 290 m.** The history of each colour stays." With cost per
  colour it adds "Choose the cost to keep for every colour:" with the list above and "The stock value changes with the cost you
  keep."; **Switch to one stock** works only once a cost is chosen. **Keep stock per colour** (or Esc) closes it and puts the choice
  back. While the total is worked out a line shows a placeholder; if that fails it reads "Couldn't work out the total." with **Try
  again** and both buttons stay off. After confirming, the form shows one **Quantity in stock** (290 m) and **Unit cost**; nothing
  is saved until **Save material**, and then Denim 12oz is one stock of 290 m.
- [ ] **AC-11 · Can produce takes the stock of each colour.** The Denim Jacket's composition table shows, for the denim line,
  **Stock on hand** "Indigo 250 m · Black 40 m" and **Units possible** "Varies" with **View by colour** ("Indigo 103–142 units · Black
  16–22 units", one number when a colour's sizes agree); Indigo M is limited by Indigo stock and Black M by Black stock. A line
  "Same for every variant" of 1.8 m (1.98 m with waste) on Denim 12oz gives 126 and 20, each variant taking from its own colour's stock. Raising Black to 250 m changes
  Black S from 22 to 112 (then limited by the button, Black stock covering 142). A one-stock material is read as today.
- [ ] **AC-12 · A colour the material doesn't have.** With Light wash added to the Denim Jacket and Denim 12oz without Light wash,
  the composition shows "Light wash — no stock" with the link **Add Light wash to Denim 12oz**, the bottleneck reads **"Bottleneck:
  Denim 12oz — no Light wash stock."** with "Denim 12oz has no Light wash yet, so no Light wash jacket can be made.", "Can produce
  now" reads 0, and the "Best case" line is unchanged. In Capacity by colour the Light wash row reads Guaranteed 0, Best 0, Limited by
  "Denim 12oz — no Light wash stock". A normal limit reads **"Bottleneck: Denim 12oz, Black stock (size XL)."** with "40 m of Black in
  stock covers 16 units of Black, size XL — the smallest across every colour and size." On a cost-per-colour material, the colour it
  lacks has no unit cost or margin: those variants are left out of the product's cost and margin ranges.
- [ ] **AC-13 · Add a colour from the product.** **Add Light wash to Denim 12oz** opens a window (the page stays behind): "Add Light
  wash to Denim 12oz?" and "Light wash starts at 0 m, so no Light wash jacket can be made until you restock it." With cost per
  colour it adds the required field **Unit cost of Light wash**. **Add Light wash** shows **"Light wash added to Denim 12oz."** with
  the button **Restock Light wash**, which opens the Restock sheet on that colour; **Cancel** adds nothing. The same window opens
  from Start batch and from "Used in", and focus returns to the link.
- [ ] **AC-14 · Start batch and Undo batch per colour.** A batch of Indigo M 6, Indigo L 10 and Black S 5 takes **33.88 m** from
  Indigo and **8.8 m** from Black, so Denim 12oz is Indigo 216.12 m, Black 31.2 m; the materials table shows one row per material and
  colour ("Denim 12oz · Indigo" needs 33.88 m, in stock 250 m; "Denim 12oz · Black" needs 8.8 m, in stock 40 m), one row for each
  one-stock material. Undoing the Indigo L batch gives back 22 m to Indigo and nothing to Black. A mixed batch with Black S 30 reads
  **"Short on Denim 12oz in Black. Restock it first, or lower the units."**; with the button also short, "Short on Denim 12oz in Black
  and Metal shank button 17mm. Restock them first, or lower the units."; Black and Light wash short, "Short on Denim 12oz in Black
  and Light wash. Restock them first, or lower the units."; this also holds for a product with only "Same for every variant"
  lines when its material is per colour; one colour and size keeps "You can cut at most N now: Denim 12oz in Black runs short." A missing colour's row
  reads "No stock", short by 8.8 m, with **Add Light wash to Denim 12oz**. Batches started before INV-21 give back what they took. A colour the material no longer has gets nothing back: its row reads
  "Light wash not on this material, not returned".
- [ ] **AC-15 · "Used in".** With the Denim Jacket's denim line **Same for every variant** at 1.8 m, on Denim 12oz's page the Denim Jacket row shows **This stock covers** "20–126 units" with the button
  **By colour** opening "Indigo 126 units · Black 20 units · Light wash no stock" (the last with the Add link); **Limited by** reads
  "This material (bottleneck), Black stock" when it is the limit; the intro reads "Here's how far your stock goes, colour by colour,
  and how many units you can make right now with all their materials."; the suggestion names the colour: "this material limits
  Denim Jacket to 20 units, in Black. If you restock Black, the next limit is …".
- [ ] **AC-16 · Cost, margin and value follow the colour.** With Denim 12oz at Indigo TND 8.500 and Black TND 9.200, the Denim Jacket in
  Black M costs TND 37.140 (Indigo M TND 35.754), margin TND 51.860 · 58.27 %, and the product's unit cost reads TND 33.884–41.188.
  The **Inventory value** adds Denim 12oz as TND 2,493.000. The dashboard's **Needs restock** still counts materials, and its row
  for Denim 12oz reads "Denim 12oz · Black 40 m".
- [ ] **AC-17 · History names the colour.** Each entry of a per-colour material names its colour with its swatch ("Restock · Indigo
  +100 m", "Correction · Black", "Back from cutting · Indigo"); the entries of one receipt sit together under its date and
  reference; the sum of a colour's entries is its stock. Switching to Stock per colour is logged as "Split by colour · Indigo 250 m", and
  switching back as "Joined into one stock · 290 m".
- [ ] **AC-18 · Nothing else changes.** Every material that exists today stays **One stock for all colours**, and "Denim 12oz
  Indigo" and "Denim 12oz Black" stay two materials, untouched and not merged, with every product, cost, "Can produce now" and
  Start batch figure as before. A line "only for Indigo" (cells under Indigo only) keeps working for them.
- [ ] **AC-19 · Phone.** At 375 px the material page's Stock by colour (a block per colour with a 44 px **Restock** button), the list
  row (the **2 colours** button, 44 px, and the panel inside the card with a colour per line), the Restock sheet (full height, a
  block per colour, the footer with **Add to stock** fixed at the bottom), the form (settings as full-width cards, a block per
  colour, a full-width **Add colour**, **Save material** in the bottom bar) and the windows read in one column with no sideways
  scroll; every field and button is 44 px high. Colour is never shown by its swatch alone, and the screens work in dark mode and
  by keyboard.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 19 commits
> Tech-lead · 2026-11-09 · proposed (revised 2026-11-09 on the human's answers, D-32)
> Why complex: a new table and three column changes, new costing, cutting and ledger rules (stock and cost per colour, ADR-0029), and
> new data on seven screens. Size: **XL** (about 5,750 lines of production code, more than INV-16's 13 commits).

### In short
A material gets two settings. A **one-stock** material stays exactly as today. A **per-colour** material keeps one row per colour of
Settings › Colours in a new table, with its quantity and, with cost per colour, its unit cost; the material itself keeps the total (and
the lowest cost) so the list, the dashboard and the sorting keep reading one figure, and one writer keeps the two in step. Behind every
figure, a product's colour now reads its material's stock and cost **of that same colour**: the INV-16 maths runs per colour and size as
before, only where it reads stock and cost changes, and a colour the material doesn't have counts as no stock. Start batch checks and
takes per colour, remembers which colour each take came from, and Undo batch gives back to that colour; batches started before keep
their figures. Every stock entry of a per-colour material names its colour, and a check proves that each colour's entries add up to
its stock. The work runs the database and the maths first, then saving a material, then each screen.

### Steps

| Step         | What it does                                            | Proves                     | Lane         | Who                | Size       |
| ------------ | ------------------------------------------------------- | -------------------------- | ------------ | ------------------ | ---------- |
| **INV-21-a** | The colour stock table, the two settings, the ADR       | AC-18                      | CRITICAL     | Backend            | ~250 lines |
| **INV-21-b** | Status, value and the ledger per colour                 | AC-5, AC-16, AC-17         | CRITICAL     | Backend            | ~300 lines |
| **INV-21-c** | Cost and capacity from each colour's stock (pure maths) | AC-11, AC-12, AC-16        | CRITICAL     | Backend            | ~300 lines |
| **INV-21-d** | Taking and giving back per colour (pure maths)          | AC-14                      | CRITICAL     | Backend            | ~200 lines |
| **INV-21-e** | Products costed from each colour's stock                | AC-11, AC-12, AC-16, AC-18 | CRITICAL     | Backend            | ~300 lines |
| **INV-21-f** | Saving a per-colour material                            | AC-1, AC-2, AC-8           | CRITICAL     | Backend            | ~350 lines |
| **INV-21-g** | Switching the stock and cost settings                   | AC-3, AC-9, AC-10          | CRITICAL     | Backend            | ~300 lines |
| **INV-21-h** | The two settings and the Colours table on the form      | AC-1, AC-2, AC-8, AC-19    | CRITICAL     | Frontend           | ~380 lines |
| **INV-21-i** | The switch windows                                      | AC-3, AC-9, AC-10          | CRITICAL     | Frontend           | ~320 lines |
| **INV-21-j** | Stock by colour on the material page                    | AC-4, AC-5, AC-19          | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-21-k** | The Materials list and the dashboard                    | AC-6, AC-16                | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-21-l** | Restock per colour, on the server                       | AC-7                       | CRITICAL     | Backend            | ~250 lines |
| **INV-21-m** | The Restock sheet                                       | AC-7, AC-19                | CRITICAL     | Frontend           | ~380 lines |
| **INV-21-n** | History names the colour                                | AC-17                      | CRITICAL     | Backend + Frontend | ~220 lines |
| **INV-21-o** | The product page per colour                             | AC-11, AC-12, AC-19        | CRITICAL     | Frontend           | ~320 lines |
| **INV-21-p** | Add a colour from the product                           | AC-13                      | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-21-q** | Start batch and Undo batch per colour                   | AC-14, AC-18               | CRITICAL     | Backend + Frontend | ~400 lines |
| **INV-21-r** | Used in, colour by colour                               | AC-15                      | CRITICAL     | Backend + Frontend | ~300 lines |
| **INV-21-s** | Golden path in the browser, desktop and phone           | AC-7, AC-14, AC-19         | NON-CRITICAL | Frontend           | ~150 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green; the steps that touch
the database or its queries (a, b, e, f, g, k, l, q) also run `composer test:pgsql`, and the tech-lead reruns INV-16's load test after
INV-21-e. A step that changes `domain.ts` is one commit: the backend developer changes the Resources and the types, then the frontend
developer updates every screen and test fixture that reads them. The commits merge in the table's order.

#### INV-21-a · The colour stock table, the two settings, the ADR
**Commit:** `feat(materials): stock per colour table and the stock and cost settings (INV-21-a)`

- `materials.stock_mode` (`single` · `per_colour`, default `single`) and `materials.cost_mode` (`same` · `per_colour`, default
  `same`); PostgreSQL CHECK: `same` with `single`. Every existing material reads `single` / `same`; no row is rewritten.
- `material_colours` as ADR-0029 §2 (quantity 14,4 ≥ 0, unit cost 12,4 nullable ≥ 0, unique material + colour, the workshop composite
  keys on PostgreSQL).
- `stock_movements.colour_id` (nullable, restrict, indexed with `material_id`) and `entry_group` (uuid, nullable); two new movement
  types `colour_split` ("Split by colour") and `colour_join` ("Joined into one stock").
- `production_cut_materials.colour_id` (nullable, restrict); its unique key becomes cut + material + colour, with a partial unique
  index on cut + material where the colour is null.
- Enums `MaterialStockMode`, `MaterialCostMode`; model `MaterialColour` (`BelongsToWorkshop`); `Material::colours()` (Settings ›
  Colours order); a `perColour([...])` factory state.
- The migration is dated after INV-16's (`2026_11_10_100000`). The dev database `laravel` is never migrated by a developer.
- The tech-lead writes in this commit: ADR-0029 Accepted, its appendix applied to the costing skill, and `docs/ARCHITECTURE.md`.

**Tests**
- 1 PostgreSQL schema dataset: a negative quantity or cost, a second row of one colour, another workshop's colour, `per_colour` cost
  on a one-stock material, a second null-colour take of one material in one cut: all refused.
- 1 migration test: existing materials read `single` / `same`, their movements and cuts have no colour, and the golden Denim Jacket
  still costs 35.7540 and makes 112.
- The workshop arch test lists `MaterialColour`.

> [!example]- Files (≈11)
> - `.claude/skills/invory-costing-domain/SKILL.md`, `docs/adr/0029-material-stock-per-colour.md`, `docs/ARCHITECTURE.md`
> - `app/Enums/{MaterialStockMode,MaterialCostMode,StockMovementType}.php`, `app/Models/MaterialColour.php` (new), `app/Models/{Material,StockMovement,ProductionCutMaterial}.php`
> - `database/migrations/2026_11_10_100000_add_stock_per_colour_to_materials.php` (new), `database/factories/{MaterialColourFactory,MaterialFactory}.php`
> - `tests/Feature/Materials/MaterialColoursSchemaTest.php` (new), `tests/Arch/WorkshopScopeTest.php`

#### INV-21-b · Status, value and the ledger per colour
**Commit:** `feat(materials): status, stock value and ledger of a per-colour material (INV-21-b)`

- `MaterialStockWriter` (new Action): sets the one stock or one colour's quantity (and a colour's cost), and keeps
  `materials.quantity_on_hand` = the total and `unit_cost` = the lowest colour cost with cost per colour, exact BigDecimal sums, under
  the material's row lock. Every later step writes stock through it.
- `Material::stockStatus()` and `stockValue()` for a per-colour material (ADR-0029 appendix B); the `needsRestock` scope and
  `InventorySummary` (PostgreSQL aggregate and PHP path) follow them; the per-colour statuses and the restocking colours are read with
  one query per page, never per row.
- `StockLedgerVerifier` checks the three invariants (no-colour entries = the one stock, each colour's entries = its row, all entries
  = the total).

**Tests**
- 1 status dataset: every colour fine · one at the reorder level · one at 0 · every colour at 0 · no reorder level with one at 0
  (In stock: Low needs a reorder level) · no reorder level with every colour at 0 (Out).
- 1 value test: Denim 12oz 2493.0000 with cost per colour; 2465.0000 with the same cost 8.5000; the Inventory value on both drivers.
- 1 writer test: setting Indigo to 216.12 leaves the total 247.12; the lowest cost follows a changed colour cost.
- 1 ledger test: a colour row off by 0.0001 is reported; balanced colour entries are not.

> [!example]- Files (≈8)
> - `app/Actions/Materials/MaterialStockWriter.php` (new), `app/Models/Material.php`, `app/Services/Inventory/{InventorySummary,StockLedgerVerifier}.php`
> - `tests/Feature/Materials/{MaterialColourStatusTest,MaterialStockWriterTest}.php` (new), `tests/Feature/Inventory/InventorySummaryTest.php`, `tests/Feature/Stock/StockLedgerVerifierTest.php`

#### INV-21-c · Cost and capacity from each colour's stock (pure maths)
**Commit:** `feat(costing): cost and capacity read each colour's stock and cost (INV-21-c)`

- `VariantLine` takes the material's colour stock (per colour id: quantity and cost; null for one stock); a variant of colour c reads
  that colour's stock and cost, and a missing colour reads 0, flagged missing; with cost per colour a missing colour has no cost.
- `VariantCosting` keys a composition by its options and, when a line is per colour, the colour; a variant's result, a colour's
  summary and the binding / best figures carry the bottleneck's stock colour and the missing flag; a variant with no cost is left out
  of the cost and margin ranges (ADR-0029 appendix B; AC-12). Material spans gain, per product colour, the lowest and highest units
  possible over that colour's sizes (the "View by colour" range), or missing.
- `BomLine` gains `stockColourId` (null for one stock); `CostingCalculator` is unchanged, and the INV-16 equivalence test still pins
  `VariantCosting` to it.

**Tests**
- The ADR-0029 worked example as a golden test: INV-16's eight capacities on one material, Black M 37.1400, margin 51.8600 · 58.27,
  range 33.8840–41.1880, denim by colour Indigo 103–142 and Black 16–22; a `same` line of 1.8 m: Indigo 126, Black 20 (min = max),
  product limited to 20 in Black; Black at 250: Black S 112.
- 1 edge dataset: Light wash missing (guaranteed 0, best 112, bottleneck Denim 12oz missing) with the same cost and with cost per
  colour (Light wash left out of the range); every material one stock (INV-16's golden figures unchanged).

> [!example]- Files (≈7)
> - `app/Domain/Costing/{VariantLine,VariantCosting,VariantResult,ColourCapacity,MaterialSpan,BomLine}.php`
> - `tests/Unit/Domain/Costing/VariantColourStockTest.php` (new)

#### INV-21-d · Taking and giving back per colour (pure maths)
**Commit:** `feat(stock): starts take and return per colour of a material (INV-21-d)`

- `CuttingCalculator::takeVariants()` sums, rounds once and checks per stock (material, or material and colour); a missing colour is a
  stock of 0. `MaterialTake` gains the colour and the missing flag; a plan is *mixed* with a per-colour material too.
- `giveBackFromGroup()`: a take with a colour holds only the cut's groups of that colour; a take without one holds every group, so a
  cut made before INV-21 returns what it always did.

**Tests**
- AC-14's start: Indigo 33.8800 m, Black 8.8000 m, button 168, thread 2,646, label 21; Black S 30 short (52.8 against 40); a missing
  Light wash short by 8.8000.
- Undo Indigo L 10: 22.0000 m to Indigo, nothing to Black; the three batches undone give back exactly the takes; a one-group cut gives
  INV-5's 7.92 m / 32 pc / 504 m / 4 pc.

> [!example]- Files (≈6)
> - `app/Domain/Stock/{CuttingCalculator,MaterialTake,CutBatchMaterial,CutPlan}.php`
> - `tests/Unit/Domain/Stock/CuttingColourStockTest.php` (new)

#### INV-21-e · Products costed from each colour's stock
**Commit:** `feat(costing): products read their materials' colour stock (INV-21-e)`

- `ProductCostingService` loads the colour rows of the materials on the compositions it costs (one query for many products) and hands
  them to the domain; a product varies when it has a `by_variant` line **or** a per-colour material. Next limit is given for a varying
  product too, on its binding colour and size (needed by INV-21-r).
- `ProductResource`, `ProductComponentResource` and the capacity keys gain the frozen colour fields (Contract); `domain.ts` changes in
  this commit, with the frontend developer's fixture updates.

**Tests**
- 1 feature test on the product page props: the Denim Jacket on Denim 12oz gives Indigo 250 · Black 40 on the denim line, Black M
  37.1400, range 33.8840–41.1880, the line's colour ranges 103–142 and 16–22, bottleneck Denim 12oz in Black at XL; with Light wash
  added, missing and Can produce 0, and on a cost-per-colour material Light wash out of the cost and margin ranges.
- 1 regression: two one-stock materials "Denim 12oz Indigo" and "Denim 12oz Black" with lines only for their colour give INV-16's
  figures exactly (AC-18).
- 1 query-count test: the products list with per-colour materials keeps a constant number of queries.

> [!example]- Files (≈8)
> - `app/Services/Costing/{ProductCostingService,ProductCosting}.php`, `app/Http/Resources/{ProductResource,ProductComponentResource,CapacityResource,ColourCapacityResource}.php`
> - `resources/js/types/domain.ts`, `resources/js/test/fixtures/products.ts`
> - `tests/Feature/Products/ProductColourStockTest.php` (new)

#### INV-21-f · Saving a per-colour material
**Commit:** `feat(materials): add and edit a material with stock per colour (INV-21-f)`

- `StoreMaterialRequest` / `UpdateMaterialRequest`: `stock_mode`, `cost_mode`, `colours[]` (`colour_id`, `quantity_on_hand`,
  `unit_cost`) with the card's messages ("Black: Enter a number from 0, with up to 4 decimals.", "Black: Enter a unit cost.", "Add at
  least one colour, or choose One stock for all colours."); colours of the workshop only (`WorkshopRule`), distinct.
- `CreateMaterial` writes one `created` entry per colour; `UpdateMaterial` writes a `correction` per changed colour, a `price_change`
  per changed colour cost, refuses to drop a colour with stock or history (the card's two sentences), and keeps `color` untouched.
- `MaterialResource` gains `stock_mode`, `cost_mode` and `colours[]` with `removable` and `remove_reason` (Contract), with the
  frontend developer's fixture updates.

**Tests**
- 1 happy test per action: Denim 12oz saved with Indigo 250 and Black 40 at their costs; on edit, Black 40 → 35 logs one correction for
  Black, Black's cost 9.2000 → 9.5000 one price change for Black.
- 1 validation dataset (each message of AC-2, a colour of another workshop, a colour twice, cost per colour with one stock).
- 1 removal dataset: Black at 0 with no history goes; Indigo with 250 m and Black with history stay, with their sentences.

> [!example]- Files (≈8)
> - `app/Http/Requests/Materials/{StoreMaterialRequest,UpdateMaterialRequest}.php`, `app/Actions/Materials/{CreateMaterial,UpdateMaterial}.php`, `app/Http/Resources/MaterialResource.php`
> - `resources/js/types/domain.ts`, `resources/js/test/fixtures.ts`
> - `tests/Feature/Materials/MaterialColoursWriteTest.php` (new)

#### INV-21-g · Switching the stock and cost settings
**Commit:** `feat(materials): switch a material to stock or cost per colour and back (INV-21-g)`

- Switch to per colour: the current stock goes to the chosen colour ("Choose the colour that holds the 250 m you have now." when none)
  in one `colour_split` group ("Split by colour · Indigo 250 m"); other colours start where typed. Switch back: the colours' total
  becomes the one stock in one `colour_join` group ("Joined into one stock · 290 m"), the kept cost applies, the colour rows go,
  their entries stay. Cost per colour on: every colour at the current
  cost, no entry; off: the kept cost, a `price_change` for each colour that changes. Nothing changes before **Save material**.
- `GET materials/stock-total` (JSON, `viewAny` on materials): from the unit and the typed quantities it returns the total and the
  sentence "Indigo 250 m + Black 40 m become one stock of 290 m.", or 422 (the window then reads "Couldn't work out the total.").

**Tests**
- 1 switch dataset: one stock 250 m → Indigo 250 + Black 40; back to one stock 290 m keeping Indigo's 8.5000 (stock value 2465.0000);
  cost per colour on and off; each leaves the ledger balanced (INV-21-b's check).
- 1 endpoint test: the sentence and total; a bad quantity is a 422; a guest is refused.

> [!example]- Files (≈7)
> - `app/Http/Requests/Materials/UpdateMaterialRequest.php`, `app/Actions/Materials/{UpdateMaterial,SwitchMaterialStock}.php` (one new), `app/Http/Controllers/MaterialStockTotalController.php` (new), `routes/web.php`
> - `tests/Feature/Materials/MaterialStockSwitchTest.php` (new)

#### INV-21-h · The two settings and the Colours table on the form
**Commit:** `feat(materials): stock and cost settings and the Colours table on the material form (INV-21-h)`

- **How is stock kept?** and **How is cost set?** as radio cards, the three field layouts of the UI card, the Colours table with
  **Add colour** (Add product's searchable colour list, only colours the material lacks), **Remove** with its reasons (greyed, still
  focusable), the free-text Colour hidden and kept, the reorder hint, the errors under each field and focus on the first.
- Phone: settings as full-width cards, a block per colour, full-width **Add colour**, 44 px targets.

**Tests**
- 1 form test: choosing Stock per colour shows the table and hides Colour; cost per colour adds the cost column and drops the single
  Unit cost; the submitted payload.
- 1 error test: the AC-2 messages land under their fields and focus goes to the first.
- 1 Remove test: the working button is named "Remove Black"; Indigo's is disabled with its reason readable by keyboard.

> [!example]- Files (≈7)
> - `resources/js/components/materials/{material-form,material-colours-table,stock-settings}.tsx` (two new), `resources/js/pages/materials/{create,edit}.tsx`
> - `resources/js/components/materials/material-form.test.tsx`, `resources/js/components/materials/material-colours-table.test.tsx` (new)

#### INV-21-i · The switch windows
**Commit:** `feat(materials): confirm windows for switching stock and cost settings (INV-21-i)`

- Switch to per colour with stock: one row with "Choose a colour" and the note; **Cancel** discards. Cost per colour on: the column
  filled from the current cost and its note. **"Use the same cost for every colour?"** and **"Switch to one stock for all colours?"**
  with their cost lists, the disabled confirm and "Choose the cost to keep.", the total from `materials.stock-total` (placeholder,
  "Couldn't work out the total." with **Try again**), Esc and **Keep …** putting the choice back.

**Tests**
- 1 window test per switch-back window: confirm disabled until a cost is chosen; Keep puts the radio back; the fetched sentence shows.
- 1 failure test: the total request fails, both buttons stay off, **Try again** asks again.
- 1 switch-to test: saving without a colour shows "Choose the colour that holds the 250 m you have now." under the picker.

> [!example]- Files (≈6)
> - `resources/js/components/materials/{switch-to-one-stock-dialog,same-cost-dialog}.tsx` (new), `resources/js/components/materials/{material-form,material-colours-table}.tsx`
> - `resources/js/components/materials/switch-dialogs.test.tsx` (new)

#### INV-21-j · Stock by colour on the material page
**Commit:** `feat(materials): stock by colour on the material page (INV-21-j)`

- `MaterialResource` gains the page's server sentences: the callout title and detail, "3 of 20 colours need restock" (more than 8
  colours only), the unit cost range, each colour's status and stock value (Contract).
- The four figures ("In stock, all colours", "each colour", "per colour"), the named callout, the **Stock by colour** card (table,
  total row, **Restock <colour>** buttons wired in INV-21-m, the filter switch), Details without the free-text Colour; a block per colour
  on a phone.

**Tests**
- 1 Resource test: Denim 12oz's figures and sentences (290 m, "Black is below reorder level.", 2493.0000, 8.5000–9.2000).
- 1 page test: the card with its rows and total, the cost columns only with cost per colour; the switch filters without reordering.

> [!example]- Files (≈8)
> - `app/Http/Resources/MaterialResource.php`, `app/Services/Materials/MaterialStockSentences.php` (new)
> - `resources/js/types/domain.ts`, `resources/js/components/materials/{material-kpis,material-stock-callout,stock-by-colour-card,material-details-card}.tsx` (one new), `resources/js/pages/materials/show.tsx`
> - `tests/Feature/Materials/MaterialColourPageTest.php` (new), `resources/js/components/materials/stock-by-colour-card.test.tsx` (new)

#### INV-21-k · The Materials list and the dashboard
**Commit:** `feat(materials): colours on the materials list and the dashboard (INV-21-k)`

- The list row: total with the **2 colours** button and its panel (tiles, footer), "each colour", the unit cost range, the restocking
  colours under the status ("Camel, Black, Light wash and 2 more"); **Needs restock** and **Stock low→high** read the per-colour
  status and the total. The dashboard's **Needs restock** row reads "Denim 12oz · Black 40 m".
- The colours of a page of materials come in one query.

**Tests**
- 1 index test: the filter keeps a material with one low colour; the sort uses the total; the row's colour fields; constant queries.
- 1 dashboard test: the row's colour text and the count of materials.
- 1 table test: the button's name, the panel's tiles with badges only for Low and Out.

> [!example]- Files (≈9)
> - `app/Http/Controllers/{MaterialController,DashboardController}.php`, `app/Http/Resources/MaterialResource.php`
> - `resources/js/types/domain.ts`, `resources/js/components/materials/{materials-table,stock-by-colour-panel}.tsx` (one new), `resources/js/components/dashboard/needs-restock-card.tsx`
> - `tests/Feature/Materials/MaterialIndexTest.php`, `resources/js/components/materials/stock-by-colour-panel.test.tsx` (new)

#### INV-21-l · Restock per colour, on the server
**Commit:** `feat(materials): restock several colours in one receipt (INV-21-l)`

- `StoreRestockRequest` for a per-colour material: `lines[colourId]` (`quantity`, `unit_price`) for every colour of the material, one
  date, reference and checkbox, one `unit_price` with the same cost; the card's messages, and "The colours of Denim 12oz have changed.
  Enter the quantities again." when the colours differ. A one-stock material keeps today's request.
- `RestockMaterial` books every received colour through `MaterialStockWriter` in one group (a `restock` per colour, a `price_change`
  where the cost changed, only for received colours); the toast "Restocked Denim 12oz: Indigo +100 m, Black +20 m.".

**Tests**
- 1 happy test: Indigo 100 at 8.8000 and Black 20 at 9.2000 → 350 / 60 m, Indigo 8.8000, Black 9.2000, the toast; one colour gives the
  one-colour toast.
- 1 validation dataset (the four messages, nothing saved); 1 test that a one-stock restock is unchanged.

> [!example]- Files (≈6)
> - `app/Http/Requests/Materials/StoreRestockRequest.php`, `app/Actions/Materials/RestockMaterial.php`, `app/Http/Controllers/MaterialRestockController.php`, `app/Services/Materials/MaterialStockSentences.php`
> - `tests/Feature/Materials/RestockColoursTest.php` (new)

#### INV-21-m · The Restock sheet
**Commit:** `feat(materials): restock sheet for a per-colour material (INV-21-m)`

- The side sheet of the UI card: header line, Date and Reference, one price or a price per colour, a line per colour with "Now 40 m",
  **Find colour** beyond 8 colours keeping typed values, the checkbox wording, the fixed footer with the count of colours typed;
  opened from the page, the list, or a colour's **Restock** button with that field focused; focus back to the opener. One-stock
  materials keep today's panel and dialog.

**Tests**
- 1 sheet test: typing Indigo 100 and Black 20 submits two lines; the footer reads "2 colours in this receipt".
- 1 focus test: opened from "Restock Black", Black's field has focus; a hidden typed line keeps its value.
- 1 error test: the server messages land on their lines and the typed values stay.

> [!example]- Files (≈6)
> - `resources/js/components/materials/{restock-sheet,restock-colour-line}.tsx` (new), `resources/js/components/materials/{stock-by-colour-card,materials-table}.tsx`, `resources/js/pages/materials/show.tsx`
> - `resources/js/components/materials/restock-sheet.test.tsx` (new)

#### INV-21-n · History names the colour
**Commit:** `feat(materials): history entries name their colour (INV-21-n)`

- The history rows gain the colour (swatch and name) and the entry group; the list groups one receipt's entries under its date and
  reference and shows the two switch entries as "Split by colour · Indigo 250 m" and "Joined into one stock · 290 m".

**Tests**
- 1 props test: a receipt's two entries share a group and name Indigo and Black; a colour's entries add up to its stock.
- 1 list test: "Restock · Indigo +100 m" with its swatch; grouped entries sit together.

> [!example]- Files (≈6)
> - `app/Http/Resources/StockMovementResource.php`, `app/Http/Controllers/MaterialController.php`
> - `resources/js/types/domain.ts`, `resources/js/components/materials/history-list.tsx`
> - `tests/Feature/Materials/StockHistoryPropsTest.php`, `resources/js/components/materials/history-list.test.tsx`

#### INV-21-o · The product page per colour
**Commit:** `feat(products): composition and bottleneck read each colour's stock (INV-21-o)`

- Composition table: **Stock on hand** "Indigo 250 m · Black 40 m" (three colours, then "5 colours" with a list), **Varies** with
  **View by colour** (the server's lowest–highest units per colour, over its sizes), "Light wash — no stock" in the warning tone with **Add Light wash to Denim 12oz** (wired in INV-21-p).
- Bottleneck callout: "Bottleneck: Denim 12oz, Black stock (size XL)." with its line, and the missing-colour sentences; **Capacity by
  colour**'s Limited by. Wraps full width on a phone.

**Tests**
- 1 table test: the stock line; View by colour reads "Indigo 103–142 units · Black 16–22 units", and one number when a colour's
  sizes agree; the missing colour's warning line and link.
- 1 callout test: the normal and the missing sentences; Best case unchanged.

> [!example]- Files (≈6)
> - `resources/js/components/products/{bom-table,bottleneck-callout,capacity-by-colour-card,colour-stock-line}.tsx` (one new)
> - `resources/js/components/products/{bom-table,bottleneck-callout}.test.tsx`

#### INV-21-p · Add a colour from the product
**Commit:** `feat(materials): add a missing colour to a material from where it is used (INV-21-p)`

- `POST materials/{material}/colours` (`update` policy): adds the colour at 0, with its cost on a cost-per-colour material ("Unit cost
  of Light wash" required), no entry; the toast "Light wash added to Denim 12oz." carries a **Restock Light wash** action.
  `GET materials/{material}/restock-sheet` (JSON) gives the sheet its data on pages that don't have it.
- The window "Add Light wash to Denim 12oz?" opens from the product page, Start batch and Used in, focus back on the link; the toast's
  action opens the Restock sheet over the current page on that colour.

**Tests**
- 1 feature test: the colour added at 0, its cost required with cost per colour, a colour already on the material or of another
  workshop refused, a guest refused.
- 1 window test: Cancel adds nothing; Add posts and the toast's action opens the sheet on Light wash.

> [!example]- Files (≈9)
> - `app/Http/Controllers/{MaterialColourController,MaterialRestockSheetController}.php` (new), `app/Actions/Materials/AddMaterialColour.php` (new), `app/Http/Requests/Materials/StoreMaterialColourRequest.php` (new), `routes/web.php`
> - `resources/js/types/global.d.ts` (toast action), `resources/js/components/materials/add-material-colour-dialog.tsx` (new)
> - `tests/Feature/Materials/AddMaterialColourTest.php` (new), `resources/js/components/materials/add-material-colour-dialog.test.tsx` (new)

#### INV-21-q · Start batch and Undo batch per colour
**Commit:** `feat(production): start and undo batches per colour of a material (INV-21-q)`

- `CuttingPreview` and `StartBatch` take and record per stock (the colour on each `cutting` entry and remembered take);
  `ProductionReturns` and `UndoBatch` give back per stock (ADR-0029 §6): into the one stock when the material is one stock at the
  undo, whatever the take came from; into the take's colour (or, for a take made from one stock, the batch's colour) when the
  material is per colour and has it; otherwise nothing, the row reading "Light wash not on this material, not returned".
- Messages: a grid of more than one colour and size on a product with a `by_variant` line **or a per-colour material** (also with
  only "Same for every variant" lines) reads "Short on Denim 12oz in Black. Restock it first, or lower the units." · "Short on Denim
  12oz in Black and Metal shank button 17mm. Restock them first, or lower the units." · "Short on Denim 12oz in Black and Light
  wash. Restock them first, or lower the units."; one colour and size keeps "You can cut at most N now: Denim 12oz in Black runs
  short.".
- The Start batch materials table: one row per material and colour, "No stock" with **Add Light wash to Denim 12oz**; the Undo batch
  window's not-returned row.

**Tests**
- 1 feature test: AC-14's start (Indigo 216.12, Black 31.2, the two entries name their colour) and its Indigo L undo (22 m to Indigo).
- 1 message dataset: Black short · Black and the button · Black and Light wash · a per-colour material with only `same` lines in two
  colours · one colour and size.
- 1 return dataset: a batch started before INV-21 undoes to the same figures; a per-colour take undone after a switch to one stock
  returns into the one stock; a one-stock take undone after a switch to per colour returns into the batch's colour; a colour the
  material no longer has gets nothing, with its sentence.
- 1 dialog test: the per-colour rows, the No stock row and the not-returned row.

> [!example]- Files (≈9)
> - `app/Services/Orders/CuttingPreview.php`, `app/Actions/Production/{StartBatch,UndoBatch}.php`, `app/Services/Production/{ProductionReturns,ProductionMessages}.php`, `app/Http/Resources/MaterialMoveRowResource.php`
> - `resources/js/types/domain.ts`, `resources/js/components/production/{start-batch-dialog,undo-batch-dialog}.tsx`
> - `tests/Feature/Production/BatchColourStockTest.php` (new)

#### INV-21-r · Used in, colour by colour
**Commit:** `feat(materials): used in reads each colour's stock (INV-21-r)`

- `MaterialUsageService` gives, per product, the range covered, the figure per product colour (missing ones included), the bottleneck
  colour and, when this material limits, the colour and the next limit of the binding colour and size.
- The table: "20–126 units" with **By colour**, "This material (bottleneck), Black stock", the per-colour intro and suggestion.

**Tests**
- 1 feature test: the Denim Jacket row with its `same` 1.8 m line (20–126 units; Indigo 126, Black 20, Light wash missing; limited
  in Black; "this material limits Denim Jacket to 20 units, in Black" and the next limit).
- 1 component test: the By colour line with its Add link, the intro and the suggestion.

> [!example]- Files (≈7)
> - `app/Services/Costing/{MaterialUsageService,MaterialUsage}.php`, `app/Http/Resources/MaterialUsageResource.php`
> - `resources/js/types/domain.ts`, `resources/js/components/materials/material-usage.tsx`
> - `tests/Feature/Materials/MaterialUsageTest.php`, `resources/js/components/materials/material-usage.test.tsx`

#### INV-21-s · Golden path in the browser, desktop and phone
**Commit:** `test(materials): stock per colour golden path in the browser (INV-21-s)`

- One Pest browser test: create Denim 12oz per colour (Indigo 250, Black 40), restock Indigo 100 and Black 20 in one receipt, start a
  batch Indigo M 6 · Black S 5 on the Denim Jacket; once at desktop width and once at 375 px with no sideways scroll.

**Tests**
- 1 browser test, two widths.

> [!example]- Files (1)
> - `tests/Browser/MaterialColourStockGoldenPathTest.php` (new)

### Contract (frozen before dispatch)
Every new field is sent for a one-stock material too (`single`, `same`, empty or null colour fields). Types in `domain.ts`:

```ts
export type MaterialStockMode = 'single' | 'per_colour';
export type MaterialCostMode = 'same' | 'per_colour';
export type MaterialColourStock = {
    colour: Colour;
    quantity_on_hand: Decimal;
    unit_cost: Decimal | null; // cost per colour only
    stock_value: Decimal | null; // cost per colour only
    stock_status: StockStatus;
    removable: boolean;
    remove_reason: string | null; // "Indigo has 250 m in stock, so it can't be removed."
};
// Material += (INV-21-f) stock_mode, cost_mode, colours: MaterialColourStock[] (Settings › Colours order; [] for one stock)
//          += (INV-21-j) unit_cost_range: DecimalRange | null; stock_callout: { title: string; detail: string } | null;
//             colours_restock_summary: string | null  // "3 of 20 colours need restock", more than 8 colours only
//          += (INV-21-k) restock_colours: string | null  // "Camel, Black, Light wash and 2 more"
// MaterialFormData += stock_mode, cost_mode, colours: { colour_id: number | ''; quantity_on_hand: string; unit_cost: string }[]
// StockTotal (GET materials/stock-total?unit=m&colours[12]=250&colours[13]=40) = { total: Decimal; sentence: string }
// RestockFormData (per colour) = { received_on; reference; update_unit_cost; unit_price: string /* same cost */;
//                                  lines: Record<number, { quantity: string; unit_price: string }> }
export type ColourStockReading = {
    colour: Colour;
    missing: boolean; // the material doesn't have this colour ("Light wash — no stock")
    stock_on_hand: Decimal; // "0.0000" when missing
    units_possible: CountRange; // lowest and highest over the colour's sizes (min = max shows one number); 0–0 when missing
};
// ProductComponentLine += stock_by_colour: ColourStockReading[] | null   (null for a one-stock material; the product's colours)
//                      material += stock_mode
// Capacity += bottleneck_colour: Colour | null; bottleneck_missing: boolean; bottleneck_stock: Decimal | null
// ColourCapacity += bottleneck_colour: Colour | null; bottleneck_missing: boolean
// MaterialMoveRow += colour: Colour | null; missing: boolean;
//                    not_returned += 'colour_missing'  // "Light wash not on this material, not returned"
// StockMovement += colour: Colour | null; entry_group: string | null;  StockMovementType += 'colour_split' | 'colour_join'
// MaterialUsage += by_colour: ColourStockReading[] | null; bottleneck_colour: Colour | null; limit_colour: Colour | null;
//                 next_limit no longer always null when product_varies
// Dashboard needs-restock row += colours_text: string | null   // "Black 40 m, Camel 0 m"
// Flash toast += action?: { label: string; restock: { material_id: number; colour_id: number } }   (global.d.ts)
```
New routes: `materials.stock-total` (GET, JSON), `materials.colours.store` (POST), `materials.restock-sheet` (GET, JSON); the
existing `materials.restocks.store` takes the per-colour payload.

### Decisions
- **ADR-0029** (proposed with this plan) records the rules; its appendix goes into the costing skill in INV-21-a.
- **The material keeps the total and the lowest cost**, written by one Action with the colour rows under the material's row lock;
  rejected: a sum on read (float sums on SQLite, a sub-select on every sort).
- **The material row lock guards its colour rows**; Start batch's lock order is unchanged.
- **A missing colour is a stock of 0**, flagged so the screens can name it; with cost per colour it has no cost and stays out of the
  cost range (no guessed price, D-30 #19).
- **Every stock entry of a per-colour material names its colour**; switches write both sides of the move so the ledger stays exact.
- **The Restock sheet opens over the current page** from the toast's action (its data from a small JSON endpoint), so a typed Start
  batch grid is not lost; rejected: sending the user to the material page.
- **The switch-back total comes from the server** from the typed quantities, so the screen never adds numbers.
- **The human's answers (D-32)** are built in: "View by colour" and "By colour" show the lowest–highest units over a colour's sizes
  (one number when they agree); Low needs a reorder level; a missing colour on a cost-per-colour material has no cost or margin;
  the "Short on … in …" wording for any grid of more than one colour and size on a product with a per-colour material; the labels
  "Split by colour" and "Joined into one stock"; a colour the material no longer has gets nothing back ("… not on this material,
  not returned"), and a material that is one stock at the undo takes the amount back into its one stock.
- Out of INV-21 (D-32, moved to INV-26): the material search's "290 m in 2 colours", the note on Add and Edit product's composition,
  the grid sheet's muted line and "Stock per colour" under the material's name.
- Unchanged: `CostingCalculator`, INV-16's grid, its cap and its size lock.

### Order
Wave 1 (backend): INV-21-a · Wave 2: INV-21-b, then INV-21-c and INV-21-d side by side (disjoint folders) · Wave 3: INV-21-e (backend,
then frontend fixtures), INV-21-f · Wave 4: INV-21-g, then INV-21-h and INV-21-i (frontend) · Wave 5: INV-21-j, INV-21-k ·
Wave 6: INV-21-l, then INV-21-m · Wave 7: INV-21-n, INV-21-o, INV-21-p, INV-21-q, INV-21-r · Wave 8: INV-21-s. The tree is snapshotted
after each approved step so the commits can be made one per step, in the table's order.

### Risks
- The stored total drifts from the colour rows → one writer for every quantity and cost; the ledger check runs in every writer's
  tests and the tech-lead runs it on the dev database after the migration step.
- Products using a per-colour material are now costed per variant → the colours load in one query; INV-16's load test is rerun after
  INV-21-e.
- Many fixtures read the changed types → each contract step is one commit with the frontend developer's fixture updates.
- The live dev database must keep its figures → the migration only adds columns and a table; existing materials stay one stock; the
  tech-lead checks `laravel`'s migrations table after INV-21-a (a developer once migrated it mid-step).
- A material switched while a batch is in cutting → ADR-0029 §6's return rule, tested in INV-21-q.
- Two people editing one material's colours at once → the second save is checked under the material's lock; a colour added meanwhile
  at 0 is kept only if the form sent it (accepted: the last save wins, as for quantities today).
