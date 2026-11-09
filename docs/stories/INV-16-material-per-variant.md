---
id: INV-16
title: Material use per colour and size
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Done
created: 2026-11-06
tags:
  - story
  - variants
---

# INV-16 · Material use per colour and size

> [!abstract] Description
> As a **workshop owner**, I want **a composition line to use more material in some colours or sizes, and every figure to follow it**,
> so that **an XL jacket takes its 2.2 m of denim, Black denim is its own stock, and "Can produce now", the cost and Start batch tell
> me the truth instead of one quantity for every variant**. Each line is either **Same for every variant** (today, unchanged) or **By
> colour and size** (one quantity per colour and size; a blank cell means that colour and size doesn't use the material). The product
> page then shows **guaranteed** (what any mix of colours and sizes can reach) beside **best** (what one colour on its own can reach),
> a table of capacity by colour, and cost and margin as a range when they vary.
> New: the quantity choice and the grid on a composition line, "Capacity by colour", ranges for cost and margin, the mixed-batch
> shortfall message, a lock on a size that has quantities. Changed: the product page, the Products list, a material's "Used in" table, the dashboard's
> "Can't produce" and Start batch. The pages are drawn in [[INV-16-material-per-variant-ui]].
> Left out on purpose: a cost range per composition line (the read-only table says "Varies" and links to the grid), an inline grid
> for products with few colours (the grid always opens in a side panel), a multiplier per size, and one stock split by colour (both
> ruled out: Black denim and Indigo denim are two materials, each with its own stock).
> Built after INV-18 (sizes chosen per product, managed in Settings): the Denim Jacket is then given the sizes S · M · L · XL, as in
> every example below. The product's sizes are the ones chosen in its Sizes field, not its category's; the question before a size with
> typed quantities is taken off and the lock on a size with saved quantities are drawn in [[INV-18-product-sizes-ui]].

> [!info] How it works today
> A composition line has one quantity, used by every colour and every size: the Denim Jacket's denim line reads "1.8000 m" for an S
> and for an XL, in Black and in Indigo. The product page shows one "Can produce now" (112, from current stock, any mix of sizes), one
> bottleneck ("Metal shank button 17mm"), one unit cost ("TND 35.754") and one margin. Start batch checks the units typed against that
> one composition and, when short, says "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs
> short. Restock it first, or cut 12 or fewer." There is no way to say an XL jacket needs more denim, or that Black denim is stocked
> apart from Indigo denim.

## Acceptance criteria

The **Denim Jacket**, colours Indigo and Black (in that order), sizes S · M · L · XL; labor TND 12.000, overhead TND 3.500, selling
price TND 89.000. Button, thread and label are **Same for every variant**: Metal shank button 17mm 8 pc (stock 900), Polyester thread
120 m at 5 % waste (stock 20,000), Woven brand label 1 pc (stock 400), as today. Its two denim lines are **By colour and size**, both at
8.5000 per m and 10 % waste:

| Line                  | Stock      | S      | M      | L      | XL     |
| --------------------- | ---------- | ------ | ------ | ------ | ------ |
| Denim 12oz Indigo (m) | 250.0000 m | 1.6000 | 1.8000 | 2.0000 | 2.2000 |
| Denim 12oz Black (m)  | 40.0000 m  | 1.6000 | 1.8000 | 2.0000 | 2.2000 |

The Indigo line has cells under Indigo only, the Black line under Black only. Can produce, per variant: Indigo S 112 · M 112 · L 112 ·
XL 103; Black S 22 · M 20 · L 18 · XL 16.

- [x] **AC-1 · Choose how a line counts.** ✅ 2026-11-09 · e01b2ea In Add product and in Edit product, each composition line keeps its material, waste % and
  notes and gains the control **Same for every variant** (selected on every line that exists today and on a new line) and **By colour and size**.
  - **Same for every variant** shows today's quantity field and unit, unchanged.
  - **By colour and size** replaces the field with a summary and **Edit grid**: "4 of 8 cells · Indigo only" (a line with cells in
    one or two colours names them; with more, only the count, "12 of 20 cells"), or "No cells set yet" for a line with none.
  - Switching the Indigo line, typed 1.8000, to **By colour and size** opens its grid at once with all 8 cells holding 1.8000, ready
    to edit or clear. A line with no quantity typed opens with all cells blank, and the first colour (in the product's colour order)
    open, so the fields are ready to type in.
  - A line with no material chosen can't be switched: **By colour and size** is disabled and, under the control, reads "Choose a
    material first."
  - Switching the filled Indigo line back asks: "Switch to one quantity for every variant? This clears the 4 quantities you set for
    Denim 12oz Indigo across its colours and sizes." with **Keep the grid** (nothing changes) and **Switch anyway** (the line shows
    its quantity field again, empty). A line with no cell switches back without asking.
  - Pressing **By colour and size** and then straight back, while the grid is still as pre-filled and no cell has been edited,
    brings back the quantity you typed and asks nothing.
  - The grid's colours are the colours ticked on the form and its sizes the sizes pressed in its Sizes field. Taking a colour off the
    form drops its cells with the save, with no question.
  - Pressing a size off the form (Add product, or Edit product for quantities typed but not saved) while a line holds quantities for
    it asks first: "Take XL off this product?" and "This clears the 2 quantities you set for XL across its materials." with **Keep XL**
    and **Take XL off**. A size with no quantity comes off without asking. On Add product, "Use the sizes of Footwear" with quantities
    typed asks "Use the sizes of Footwear?" and "This replaces the sizes and clears the 8 quantities you set across its materials."
    with **Keep the sizes** and **Use Footwear sizes**.
  - Pressing **One size** while other sizes hold typed quantities asks the same way: "Use One size?" and "This replaces the sizes
    and clears the 8 quantities you set across its materials." with **Keep the sizes** and **Use One size** (the name is the one
    the workshop gave its One size; "the 1 quantity" for one). With no typed quantity to clear, it asks nothing. Pressing another
    size while One size is pressed and holds 3 typed quantities asks "Take One size off this product?" and "This clears the 3
    quantities you set for One size across its materials." with **Keep One size** and **Take One size off**.
  - On a phone the control is full width under the line, and every field and button is 44 px high.

- [x] **AC-2 · The quantity grid.** ✅ 2026-11-09 · e01b2ea **Edit grid** opens a side panel titled "Denim 12oz Indigo", with the composition list still
  behind it, "Quantity by colour and size, in m. A blank cell means that colour and size doesn't use this material.", **Find colour**
  (filters the product's own colours as you type), "1 of 2 colours used", **Expand all** and **Collapse all**.
  - One row per colour, in the product's colour order, closed except any colour that already has a cell; when no colour has a cell
    (a line switched with no quantity typed), the first colour is open. A closed row reads "Not used", "4 of 4 sizes · 1.8000 m"
    (the same value in every size) or "4 of 4 sizes · 1.6000–2.2000 m". Find colour with no match reads "No colour matches "xyz"."
  - Opening Indigo shows S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000, each field named "Indigo, size M" and so on (a One size
    product: "Indigo, One size"), with the unit m.
    With only S typed at 1.6000, **Fill empty sizes with 1.6000 m** appears; pressing it fills M, L and XL with 1.6000 and never
    changes a cell already typed.
  - The footer reads "4 of 8 cells filled" and **Done**, which closes the panel; nothing is saved until **Save product**. A cell left
    empty or typed 0 is no cell.
  - Black, with no cell on this line, reads "Not used" and nothing warns about it: Black's variants simply don't use Indigo denim.
  - On a phone the panel is full height, one size per row, with **Find colour** and the count above the list and **Done** fixed at
    the bottom; nothing scrolls sideways at 320 px. With 20 colours and 11 sizes the list scrolls and **Find colour** narrows it.
  - Read-only: on the product page, **View grid** (see AC-4) opens the same panel with the cells shown and none editable.

- [x] **AC-3 · Saving, and keeping the grid true.** ✅ 2026-11-09 · e01b2ea
  - **Save product**, on Add product and on Edit product, saves the page and its grids together; the toast reads "Product
    updated." on Edit and "Product created." on Add.
  - Refused, nothing saved: a cell that isn't a number from 0 to 1,000,000 with up to 4 decimals, under that cell: "Enter a number
    from 0 to 1,000,000 with up to 4 decimals."; a **By colour and size** line with no cell: "Add at least one quantity for
    Denim 12oz Black, or switch it to Same for every variant."; when the product's colours or sizes changed since the page opened:
    "The sizes or colours of Denim Jacket have changed. Enter its quantities again."; more than 2,000 cells on the product: "Denim
    Jacket can hold at most 2,000 quantities across its materials. Remove some, or switch a line to Same for every variant." A failed
    save keeps every typed value, shows its message under the line, and opens the grid's colour row of the offending cell.
  - On Add and Edit product, taking a colour off while a line holds quantities for it shows a quiet line under Colours, with no
    question: "Taking Black off clears its 4 quantities across its materials when you save." ("its 1 quantity" for one).
  - Taking a colour off the product removes its cells in the same save, with no question. Adding a colour (Light wash) gives it no
    cells: its variants use only the **Same for every variant** lines (unit cost TND 18.924, can produce 112), and nothing warns. A size
    added to the product works the same way.
  - On Edit product, a size with a saved quantity on any **By colour and size** line is locked in the Sizes field and can't be taken
    off: its reason reads "has material quantities", alone or after the others ("Can't be taken off: it has units in stock, is on orders
    and has material quantities."), and a stale save is refused with "XL can't be taken off Denim Jacket: it has material quantities.".
    The three category locks are gone, since the category no longer decides sizes (INV-18).
  - A material used by any line, in either mode, can't be deleted and its unit can't change, as today.

- [x] **AC-4 · Guaranteed and best on the product page.** ✅ 2026-11-09 · e01b2ea Opening the Denim Jacket:
  - **Can produce now** reads "16 units" with "from current stock, any mix of colours and sizes." ("any mix of colours" for one size).
  - The bottleneck callout reads "Bottleneck: Denim 12oz Black (Black, size XL)." and "40 m in stock covers 16 units of Black,
    size XL — the smallest across every colour and size.", then in a quieter line "Best case: 112 units, in Indigo — limited there by
    Metal shank button 17mm.", the material names being links as today. For a "One size" product the same lines read "Bottleneck: Denim 12oz Black (Black)." and
    "40 m in stock covers 16 units of Black — the smallest across every colour." For a one-colour product with several sizes they read
    "Bottleneck: Denim 12oz Indigo (size XL)." and "… covers 16 units of size XL — the smallest across every size."
  - **Capacity by colour** follows, one row per colour in the product's colour order:

    | Colour | Guaranteed | Best | Limited by             |
    | ------ | ---------- | ---- | ---------------------- |
    | Indigo | 103        | 112  | Denim 12oz Indigo (XL) |
    | Black  | 16         | 22   | Denim 12oz Black (XL)  |

    and a last row "All colours (any mix)" with 16 and 112. A colour that uses no material at all reads "No composition" in its row
    and counts for neither capacity figure, but its unit cost (TND 15.500, labor and overhead) is part of the cost range (AC-5). While it loads the card shows a placeholder; on a phone each colour is a stacked card with
    Guaranteed, Best and Limited by.
  - With Denim 12oz Black at 0 m: "0 units" with the same caption, "Best case: 112 units, in Indigo — limited there by Metal shank
    button 17mm.", Black reading 0 and 0. "Can't produce" shows only when best is 0, on the hero, the Products list and the
    Composition table's footer badge alike (see AC-5).
  - When the bottleneck's colour and size can't be made at all (guaranteed 0, best above 0), the callout keeps its title "Bottleneck:
    Denim 12oz Black (Black, size XL)." and reads "Denim 12oz Black is out of stock, so Black, size XL can't be made." ("Denim
    12oz Black doesn't cover one unit of Black, size XL. 0.5 m in stock." when it has some stock), then the "Best case" line. A
    material below its reorder level adds "It's also below its reorder level of 30 m."
  - In the **Composition** table the two denim lines read "Varies" under Quantity, Effective qty and Units possible, each with a
    **View grid** link; waste % and the material's stock stay single values.
  - The guaranteed and best figures and the bottleneck are exact: 40 ÷ 2.42 gives 16, never rounded up.

- [x] **AC-5 · Cost and margin as a range.** ✅ 2026-11-09 · e01b2ea Wherever one unit cost or margin stood, the Denim Jacket shows its lowest to highest:
  - Cost breakdown: Materials "TND 18.384–23.994", Unit cost "TND 33.884–39.494", Margin "TND 49.506–55.116 · 55.62–61.93%"; Labor
    "TND 12.000" and Overhead "TND 3.500" stay single. The hero **Unit cost** reads "TND 33.884–39.494" with "varies by colour and
    size" in place of "same for every size". On a phone each range wraps onto its own line under its label.
  - Products list: the same unit cost and margin ranges in the same cells, and the capacity badge "16–112 units" (success tone);
    with Black at 0 m "0–112 units" (warning tone, with the short-stock icon); "Can't produce" (danger, as today) only when best
    is 0. The "Can produce" badge in the footer of the Composition table (and its phone layout) follows the same rule and reads
    the same: "16–112 units", "0–112 units" (warning) or "Can't produce" only when best is 0.
  - The range counts every colour and size, a colour with no composition included: with a third colour that uses no material, the
    lowest unit cost is TND 15.500 (labor and overhead only).
  - When nothing on a product varies (all lines **Same for every variant**, or one variant), the range is one number and every
    place reads exactly as today: "TND 35.754", "112 units".
  - Money keeps its currency's decimals (TND 3); every figure comes from the server and the screen computes none.

- [x] **AC-6 · Start batch and Undo batch on a mixed grid.** ✅ 2026-11-09 · e01b2ea
  - In the Start batch window, "Can produce now" shows the guaranteed figure (16) in the same words as today.
  - Start batch of Indigo M 6 · Indigo L 10 · Black S 5 (21 units) takes 33.8800 m Denim 12oz Indigo (6 × 1.98 + 10 × 2.2), 8.8000 m
    Denim 12oz Black (5 × 1.76), 168 pc button, 2,646 m thread and 21 pc label, and starts three batches In cutting. "Can produce
    now" goes from 16 to 12 and Indigo's guaranteed from 103 to 89.
  - Black S 10 · Black XL 10 · Indigo M 10 (30 units) needs 41.8000 m of Black denim against 40.0000 m: the table's "Short by" row
    reads 1.8000 m and the message reads "Short on Denim 12oz Black. Restock it first, or lower the units."; with the button at 100
    pc as well: "Short on Denim 12oz Black and Metal shank button 17mm. Restock them first, or lower the units." The materials are named in
    composition order, no "at most" number is given, and **Start batch** is disabled but still reachable by keyboard whenever a short message shows, in the mixed case and the single-variant case below. The check is on
    the exact total: Black S 10 · Black XL 9 (39.3800 m) starts.
  - One colour and size only, or a product with no **By colour and size** line, keeps today's message: Black XL 17 reads "Not enough
    stock to cut 17 units. You can cut at most 16 now: Denim 12oz Black runs short. Restock it first, or cut 16 or fewer."
  - Undo batch of the Indigo L batch (10 units, In cutting) gives back 22.0000 m Indigo denim, 80 pc, 1,260 m and 10 pc, and nothing
    of the Black denim. Undoing all three batches gives back exactly what the start took. On a phone the message wraps full width
    above the disabled button and the "Short by" rows stay above it.

- [x] **AC-7 · Nothing changes where nothing varies, and the other screens follow.** ✅ 2026-11-09 · e01b2ea
  - A product with no **By colour and size** line keeps today's figures and words everywhere: the Denim Jacket with every line
    **Same for every variant** reads 112, "TND 35.754", "Limited by Metal shank button 17mm" and "from current stock, any mix of
    sizes". Existing products are not touched when this ships.
  - A batch started before this change, then undone, gives back what it always would have.
  - The dashboard counts a product in "Can't produce" only when its best capacity is 0, as the Products list does.
  - A material's page, "Used in" the Denim Jacket: for Denim 12oz Black, "Needs per unit" reads "Varies", "This stock covers" reads
    "16–22 units" (Indigo denim: "103–142 units"), "Can make now" reads the product's guaranteed 16 with "This material
    (bottleneck)" on the Black denim, and no "Suggestion:" sentence is shown for that product. Products with no **By colour and
    size** line read as today.
  - The Sales log and "Record a sale" are not changed: a sale has no colour or size and still pre-fills the one selling price.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 13 commits
> Tech-lead · 2026-11-07 · approved 2026-11-07 (replanned on per-product sizes, INV-18 / ADR-0028; first proposed 2026-11-06) ·
> delivered 2026-11-08
> Why complex: changes the cost and capacity maths (per colour and size), two new tables and two column changes, a new business rule
> (the grid, its cap, a fifth reason on the size lock), Start batch's take and Undo batch's return, and the data four screens receive.

### In short
A composition line gets a mode. A **Same for every variant** line keeps its one quantity; a **By colour and size** line keeps its
quantities in a new table, one row per filled cell. Behind every figure, the server now builds the composition of each colour and size
(the shared lines plus that variant's cells), runs today's calculator on it unchanged, and sums up: the smallest capacity is the
**guaranteed** figure, the largest the **best**, and cost and margin become a lowest–highest range. A product with no **By colour and
size** line is calculated once, exactly as today, so its figures and words don't move. The grid's sizes are the product's own sizes
(INV-18): a size with saved quantities joins INV-18's size lock as its fifth reason, and on the form a size with typed quantities asks
before it comes off. Start batch adds up what each colour and size takes, checks the exact total against stock, and remembers the
amount per unit for each colour and size so Undo batch gives back exactly what it took. Batches started before this change are read
the old way. The work runs maths first, then saving, then the editor, then each screen.

### Steps

| Step         | What it does                                            | Proves     | Lane         | Who                | Size       |
| ------------ | ------------------------------------------------------- | ---------- | ------------ | ------------------ | ---------- |
| **INV-16-a** | The quantity tables, their models, the ADR              | AC-3, AC-7 | CRITICAL     | Backend            | ~250 lines |
| **INV-16-b** | Cost and capacity per colour and size (pure maths)      | AC-4, AC-5 | CRITICAL     | Backend            | ~350 lines |
| **INV-16-c** | Taking and giving back per colour and size (pure maths) | AC-6       | CRITICAL     | Backend            | ~250 lines |
| **INV-16-d** | Every product costed per variant; Can't produce by best | AC-4, AC-7 | CRITICAL     | Backend            | ~300 lines |
| **INV-16-e** | Saving the grid, and the lock on a size with quantities | AC-3       | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-16-f** | Same for every variant or By colour and size            | AC-1       | CRITICAL     | Frontend           | ~320 lines |
| **INV-16-g** | The quantity grid panel                                 | AC-2       | CRITICAL     | Frontend           | ~380 lines |
| **INV-16-m** | A size taken off while quantities are typed             | AC-1       | CRITICAL     | Frontend           | ~250 lines |
| **INV-16-h** | Guaranteed and best on the product page                 | AC-4       | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-16-i** | Capacity by colour, and Varies in the composition       | AC-4       | NON-CRITICAL | Frontend           | ~300 lines |
| **INV-16-j** | Cost and margin as a range, the list badge              | AC-5       | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-16-k** | Start batch and Undo batch on a mixed grid              | AC-6, AC-7 | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-16-l** | A material's Used in table                              | AC-7       | CRITICAL     | Backend + Frontend | ~250 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
run their PostgreSQL tests; after the last wave the whole tree runs `composer test:pgsql`, and the tech-lead runs the load test once.
A "Backend + Frontend" step is one commit: the backend developer changes the Resources and `domain.ts`, then the frontend developer
changes every screen and test fixture that reads them, so no commit leaves a type or a screen behind. INV-16-m was added in the
replan; the commits merge in the table's order.

#### INV-16-a · The quantity tables, their models, the ADR
**Commit:** `feat(products): quantity cells per colour and size on composition lines (INV-16-a)`

- `product_components.quantity_mode` (`same` · `by_variant`, default `same`); `quantity` becomes nullable. PostgreSQL CHECK: `quantity`
  is null exactly when `by_variant`, and > 0 otherwise. Every existing line becomes `same` with its quantity; nothing else moves.
- `product_component_quantities`: `workshop_id`, `product_component_id` (cascade), `product_id`, `colour_id`, `size_id` (restrict),
  `quantity` 14,4, timestamps; unique (`product_component_id`, `colour_id`, `size_id`); an index on (`product_id`, `size_id`) for the
  size lock. PostgreSQL: CHECK `quantity > 0 AND quantity <= 1000000`, the workshop composite keys (ADR-0013; sizes already carry
  `UNIQUE (workshop_id, id)` since INV-18), (`product_id`, `colour_id`) → `product_colours` and (`product_id`, `size_id`) →
  `product_sizes`. Both product keys are `NO ACTION` (the default, checked at the end of the statement) rather than `RESTRICT`, so a
  product's own cascade can still remove its links and its cells together.
- `production_cut_variants` (`workshop_id`, `production_cut_id` cascade, `colour_id`, `size_id`, `units`, `units_returned`; unique cut +
  colour + size; CHECK `units >= 1`, `0 <= units_returned <= units`) and `production_cut_variant_materials` (`workshop_id`,
  `production_cut_variant_id` cascade, `material_id` restrict, `quantity_per_unit` 20,8 > 0; unique variant + material).
  `production_cut_materials.quantity_per_unit` becomes nullable (CHECK `> 0` when set). No backfill (ADR-0027 §7).
- Enum `ComponentQuantityMode`; models `ProductComponentQuantity`, `ProductionCutVariant`, `ProductionCutVariantMaterial`
  (`BelongsToWorkshop`); relations `ProductComponent::quantities()`, `ProductionCut::variants()`; factories (a `byVariant()` state).
- The migration is dated after INV-18's (`2026_11_08_100000` is taken by it).
- The tech-lead writes in this commit: ADR-0027 Accepted, with §8's category bullet replaced by the size lock (a size with a cell
  can't be taken off; INV-18's fifth reason), appendix G's category row replaced likewise, appendix H reading "sizes the save leaves on
  the product", and appendix I's note on the Apparel list dropped (real products now take the sizes S · M · L · XL); the appendix
  applied to the costing skill, whose *Sizes* section's `[INV-16: material quantities]` becomes the fifth reason; and
  `docs/ARCHITECTURE.md` (the tables, the variant costing, the cut groups, the fifth lock reason).

**Tests**
- 1 PostgreSQL schema dataset: a cell for a colour the product doesn't have, a cell for a size the product doesn't have, another
  workshop's colour or size, quantity 0, a second cell for one colour and size, a `by_variant` line with a quantity and a `same` line
  without one: all refused.
- 1 migration test: existing lines read `same` with their quantity; the golden Denim Jacket still costs 35.7540 and makes 112.
- The workshop arch test lists the three models.

> [!example]- Files (≈12)
> - `.claude/skills/invory-costing-domain/SKILL.md`, `docs/adr/0027-material-use-per-colour-and-size.md`, `docs/ARCHITECTURE.md`
> - `app/Enums/ComponentQuantityMode.php` (new), `app/Models/{ProductComponentQuantity,ProductionCutVariant,ProductionCutVariantMaterial}.php` (new),
>   `app/Models/{ProductComponent,ProductionCut}.php`
> - `database/migrations/2026_11_09_100000_add_quantity_cells_to_compositions.php` (new), `database/factories/…Factory.php` (new, 3; `ProductComponentFactory`)
> - `tests/Feature/Products/QuantityCellsSchemaTest.php` (new), `tests/Arch/WorkshopScopeTest.php`

#### INV-16-b · Cost and capacity per colour and size (pure maths)
**Commit:** `feat(costing): cost and capacity per colour and size, guaranteed and best (INV-16-b)`

- `App\Domain\Costing\VariantCosting` (pure, no `Illuminate\*`): takes the lines in composition order (each with its quantity, or its
  cells by variant), the variants in order (colour list order, then the product's size order), labor, overhead and the selling price.
  It builds each variant's composition and runs `CostingCalculator::calculate()` on it **unchanged**, once per distinct composition.
  It returns the appendix's figures: per variant (cost, capacity, bottleneck), per colour (guaranteed, best, the binding size and its
  bottleneck, or null for "No composition"), per product (guaranteed and its binding variant, best and where it is reached, unit and
  material cost min–max, margin amount and percent min–max), and per material its need and units covered, lowest and highest, over
  the variants that use it.
- A variant with an empty composition counts for neither capacity figure but is part of the cost range, at labor + overhead (AC-4,
  AC-5).
- With no `by_variant` line it calculates once and every figure equals today's (`varies = false`).

**Tests**
- The appendix's worked example as a golden test (eight variants, exact strings: 33.8840 … 39.4940, margin 49.5060–55.1160 and
  55.62–61.93, Indigo 103 / 112, Black 16 / 22, product 16 / 112 bound at Black XL, best at Indigo S limited by the button).
- 1 edge-case dataset: every line `same` (the main golden figures), a colour with no line at all (capacity null and left out, unit cost
  15.5000 at the bottom of the range), a colour material at 0 (guaranteed 0, best 112), ties for binding and for best (colour order,
  then size order), no selling price, selling price 0.
- 1 test for the per-material lowest and highest (Black denim 16–22, Indigo denim 103–142).

> [!example]- Files (≈6)
> - `app/Domain/Costing/{VariantCosting,VariantLine,VariantCostingResult,ColourCapacity}.php` (new)
> - `tests/Unit/Domain/Costing/{VariantCostingTest,VariantCostingEdgeCasesTest}.php` (new)

#### INV-16-c · Taking and giving back per colour and size (pure maths)
**Commit:** `feat(stock): mixed cuts take and give back per colour and size (INV-16-c)`

- `CuttingCalculator::takeVariants()`: per variant its units and its composition; per material `total_exact = Σ n × per-unit`,
  `needed = round4(total_exact)` once, short when `total_exact > stock` (never the rounded figure); takes in composition order. The
  plan says whether it is **mixed** (more than one variant and a `by_variant` line): a mixed plan has no `maxCuttable`; any other plan
  has today's, so `take()` becomes the one-variant case and its tests stay as they are.
- `giveBack()` for one cut made of groups (a variant's outstanding units and its per-unit amounts): `held = round4(Σ r × per-unit)`, the
  batch's group loses k. A cut made before INV-16 is one group, so it returns what it always did.

**Tests**
- The appendix's start (33.8800 m, 8.8000 m, 168 pc, 2,646 m, 21 pc); 41.8 m against 40 refused with "Short by" 1.8000; 39.38 m
  allowed; a case where the rounded total would pass but the exact one is short; one variant gives today's `maxCuttable`.
- Undo Indigo L 10: 22.0000 m, 80 pc, 1,260 m, 10 pc, nothing of the Black denim; the three batches undone give back exactly the take;
  a one-group cut gives INV-5's 7.92 m / 32 pc / 504 m / 4 pc.

> [!example]- Files (≈6)
> - `app/Domain/Stock/{CuttingCalculator,CutPlan,CutBatch}.php`, `app/Domain/Stock/{VariantUnits,CutGroup}.php` (new)
> - `tests/Unit/Domain/Stock/CuttingVariantsTest.php` (new)

#### INV-16-d · Every product costed per variant; Can't produce by best
**Commit:** `feat(costing): products costed per colour and size; Can't produce counts best (INV-16-d)`

- `ProductCostingService` builds the `VariantCosting` input from the product's lines and cells, its colours and its own sizes
  (`ProductSizes::of()`, and `ProductSizes::forProducts()` for a page of products), and `ProductCosting` carries the result.
  `costMany()` and every reader that loads compositions (`ProductIndexQuery`, `ProductCatalog`, `MaterialUsageService`,
  `CuttingPreview`) also load the cells and colours, in a constant number of queries. A product with no `by_variant` line takes the
  fast path.
- `ProductCosting::cantProduce()` reads **best = 0**: the dashboard's count and alerts and the products list's Can't produce filter
  follow. `CapacityResource` sends the guaranteed figure and its bottleneck under today's keys (the contract changes in INV-16-h).
- A load test (group `load`, left out of `composer test`, run by the tech-lead in review): the products list page with 25 products at
  2,000 cells and the dashboard with 100 products, five at the cap, each under 1.5 s on SQLite.

**Tests**
- The card's Denim Jacket through real models, given the sizes S · M · L · XL (`ProductFactory::withSizes()`): guaranteed, best,
  ranges; Light wash added with no cells (18.9240, 112); the golden product unchanged; the dashboard and the filter counting a product
  only at best 0 (Black at 0 m: not counted).
- 1 N+1 test: `costMany()` over 10 varying products runs the same number of queries as over 1.

> [!example]- Files (≈10)
> - `app/Services/Costing/{ProductCostingService,ProductCosting}.php`, `app/Services/Products/{ProductCatalog,ProductIndexQuery}.php`,
>   `app/Services/Costing/MaterialUsageService.php`, `app/Services/Orders/CuttingPreview.php`, `app/Http/Resources/CapacityResource.php`
> - `phpunit.xml` (exclude the `load` group)
> - `tests/Feature/Products/{ProductCostingServiceTest,VariantCostingLoadTest}.php` (one new), `tests/Feature/Dashboard/DashboardTest.php`

#### INV-16-e · Saving the grid, and the lock on a size with quantities
**Commit:** `feat(products): save quantities by colour and size with the product (INV-16-e)`

- `StoreProductRequest` (its rules and messages shared with `UpdateProductRequest`, so Add product and Edit product take the same
  grid): `components.*.quantity_mode` (`same` · `by_variant`, default `same`); `same` needs `quantity` as today; `by_variant` takes
  `quantities[colourId][sizeId]` (empty or 0 = no cell; else a number up to 1,000,000 with up to 4 decimals) and at least one cell.
  The card's sentences: "Enter a number from 0 to 1,000,000 with up to 4 decimals." under the cell; "Add at least one quantity for …,
  or switch it to Same for every variant." on `components.{i}.quantities`; on `components`, "The sizes or colours of … have changed.
  Enter its quantities again." for a cell whose colour isn't in the save's `colours` or whose size isn't in its `sizes` (both are
  required on Add and Edit since INV-1 / INV-18), and the 2,000-cell sentence. These read only the payload, so nothing about them is
  checked again under the lock.
- `SyncProductComponents` writes the mode and replaces each line's cells (a line back to `same` loses them). `SyncProductColours`
  deletes the cells of a colour it takes off, before it detaches it. `SyncProductSizes` doesn't change: a size it takes off has no
  saved cell (the lock below) and gets none from the save (the request), and on PostgreSQL the (`product_id`, `size_id`) key refuses
  it if both ever failed.
- `ProductSizeLock::reasons()` gains its fifth reason, **has material quantities**, last: one grouped query, the distinct sizes of the
  product's cells. Everything else follows from INV-18 without change: the Sizes field's tooltip ("Can't be taken off: it has units in
  stock, is on orders and has material quantities."), the refusal on `sizes.removed.{sizeId}` ("XL can't be taken off Denim Jacket: it
  has material quantities.") in the request and again in `UpdateProduct` under the product's row lock, and the One size sentences.
  The Sizes field's lock note already names material quantities. Edit product's query budget goes from 4 to 5 lock queries.
- `ProductComponentResource`: `quantity_mode`, `quantities` (colour list order, then size order); `quantity`, `effective_quantity`,
  `line_cost` and `units_possible` null on a `by_variant` line. Frontend type-compat in the same commit: the composition table and
  the editor's rows accept the null fields (the table writes "Varies"; INV-16-i adds **View grid**), fixtures gain the new fields.

**Tests**
- Save the card's two denim lines on Edit (cells stored, toast "Product updated.") and on Add ("Product created."); 1 validation
  dataset (each refusal with its field and sentence; a cell for a colour or a size not in the save; 2,000 cells accepted, 2,001
  refused; 0 and empty make no cell; another workshop's colour refused); taking Indigo off deletes its cells; Light wash added, or a
  size added, has none; switching a line back to `same` clears its cells.
- Size lock (`ProductSizeLockTest`): XL with a saved cell reads "has material quantities", alone and after stock and orders; a save
  taking it off is refused with the card's sentence, and so is the second check under the row lock; XL locked this way keeps One size
  from being chosen; a size with no cell comes off. A material on a `by_variant` line can't be deleted or change unit.
- Edit page contract via `assertInertia`: one `by_variant` line's shape; the query budget with five lock queries.

> [!example]- Files (≈12)
> - `app/Http/Requests/Products/{StoreProductRequest,UpdateProductRequest}.php`, `app/Actions/Products/{SyncProductComponents,SyncProductColours}.php`,
>   `app/Services/Products/ProductSizeLock.php`, `app/Http/Resources/ProductComponentResource.php`
> - `resources/js/types/domain.ts`, `resources/js/components/products/{bom-table,bom-rows}.ts(x)`, FE fixtures that type a `ProductComponentLine`
> - `tests/Feature/Products/{QuantityGridWriteTest,ProductSizeLockTest}.php` (one new), `tests/Feature/Performance/QueryBudgetTest.php`

#### INV-16-f · Same for every variant or By colour and size
**Commit:** `feat(products): a composition line counts the same for every variant or by colour and size (INV-16-f)`

- The composition row of `ProductForm`, on Add product and on Edit product alike, gains the `ToggleGroup` **Same for every variant** /
  **By colour and size** (full width under the line on a phone, 44 px). By colour and size shows the card's summary (cells filled of
  cells possible, the colours named up to two, "No cells set yet") and **Edit grid**. Switching a line with a quantity pre-fills every
  cell of the form's colours and sizes and opens the grid; a line with none opens blank. Switching back a line with cells asks the
  card's question (**Keep the grid** / **Switch anyway**). The payload sends `quantity_mode` and the filled cells of the colours ticked
  and the sizes pressed in the form.
- The grid's colours are the colours ticked in the form, in list order; its sizes are the sizes pressed in the Sizes field, in list
  order (`form.data.sizes`, labelled from the `sizes` prop). A colour taken off leaves its cells out of the payload, with no question;
  a size taken off is INV-16-m.

**Tests**
- Vitest: default mode; pre-filled switch; blank switch; the three summary forms; the confirm, both answers, and no confirm on an empty
  line; the submitted payload, on Add product and on Edit product; a colour taken off leaves its cells out; a cell error after a failed
  save shown on the line.

> [!example]- Files (≈6)
> - `resources/js/components/products/{bom-editor.tsx,bom-editor-row.tsx,bom-rows.ts,product-form.tsx}`, `…/quantity-mode-control.tsx` (new)
> - `resources/js/components/products/{bom-editor.test.tsx,bom-rows.test.ts,product-form.test.tsx}`

#### INV-16-g · The quantity grid panel
**Commit:** `feat(products): the quantity grid by colour and size in a side panel (INV-16-g)`

- `QuantityGridSheet` (a `Sheet`, full height on a phone): title, help text with the unit, **Find colour**, "1 of 2 colours used",
  **Expand all** / **Collapse all**, one `Collapsible` row per colour (closed except the colour just switched and colours with a cell;
  "Not used", one value, or lowest–highest, as the card writes them), fields named "Indigo, size M", **Fill empty sizes with …** (never
  overwrites), the footer count and **Done** fixed at the bottom. A cell's server error sits under it, and its colour opens. Read-only
  mode for **View grid** (same layout, plain values). No horizontal scroll at 320 px.
- Lowest and highest of typed decimal strings without numbers: a string comparison helper in `components/products/decimals.ts`.

**Tests**
- Vitest: the example's rows and summaries; Find colour narrowing 20 colours; Expand/Collapse all; Fill empty sizes; the footer;
  Done keeps the values in the form; read-only mode; the comparison helper's rules (trailing zeros, integer vs decimal).

> [!example]- Files (≈5)
> - `resources/js/components/products/quantity-grid-sheet.tsx` (new, + test), `resources/js/components/products/decimals.ts` (+ test)

#### INV-16-m · A size taken off while quantities are typed
**Commit:** `feat(products): ask before a size with typed quantities comes off the product (INV-16-m)`

- `ProductForm` checks every change of the Sizes field: when it takes off a size that holds typed cells on any **By colour and size**
  line, an alert dialog asks first: "Take XL off this product?" and "This clears the 2 quantities you set for XL across its
  materials." ("the 1 quantity"; a number size "Take size 36 off this product?" and "…for size 36…", as INV-18's UI card writes them),
  with **Keep XL** (focused; Esc does the same) and the red **Take XL off**, which clears those cells and then takes the size off. A
  size with no typed cell comes off without asking. On Edit product a size with saved cells is locked (INV-16-e), so only quantities
  typed since the page opened reach the question.
- Add product: **Use the sizes of Footwear**, when the replacement would clear typed cells, asks "Use the sizes of Footwear?" and "This
  replaces the sizes and clears the 8 quantities you set across its materials." with **Keep the sizes** and **Use Footwear sizes**;
  the count is the cells of the sizes the replacement takes off, and with none it asks nothing. Choosing another category keeps the
  sizes ("Sizes kept: you changed them.") as soon as any line holds a quantity per size, as INV-18's UI card rule 6 says.
- Pressing **One size** while other sizes hold typed cells asks, in the same dialog as the category button, "Use One size?" and "This
  replaces the sizes and clears the 8 quantities you set across its materials." ("clears the 1 quantity you set across its
  materials." for one) with **Keep the sizes** (focused; Esc does the same) and **Use One size**. Title and button carry the name the
  workshop gave its One size (the `sizes` option flagged `one_size`, e.g. "Use Free size?"). The count is the typed cells of the
  sizes that come off; with none, nothing is asked and One size is pressed at once.
- The reverse move is the take-off rule above: pressing another size while One size is pressed and holds typed cells asks "Take One
  size off this product?" and "This clears the 3 quantities you set for One size across its materials." with **Keep One size** and
  **Take One size off**; the press happens only on **Take One size off**, and with no typed cell nothing is asked.
- One dialog component (`size-quantities-dialog.tsx`) serves every question of this step: a size taken off, the category button and
  One size. A helper in `bom-rows.ts` counts the typed cells of a set of sizes.

**Tests**
- Vitest: the question on XL with 2 cells, both answers (Keep XL leaves the sizes and the cells; Take XL off sends neither XL nor its
  cells); "the 1 quantity" and "size 36"; no question for a size with no cell; Use the sizes of Footwear with 8 cells to clear, both
  answers, and no question when none would be cleared; a category change with a typed quantity keeps the sizes.
- Vitest, One size: pressing it with 8 typed cells on S–XL asks "Use One size?", both answers (Keep the sizes changes nothing; Use One
  size sends One size alone and none of the 8 cells); the "clears the 1 quantity" form; the workshop's own name ("Use Free size?");
  nothing asked with no typed cell; the reverse "Take One size off this product?" with 3 cells, both answers.

> [!example]- Files (≈5)
> - `resources/js/components/products/{product-form.tsx,bom-rows.ts}`, `resources/js/components/products/size-quantities-dialog.tsx` (new)
> - `resources/js/components/products/{product-form.test.tsx,bom-rows.test.ts}`

#### INV-16-h · Guaranteed and best on the product page
**Commit:** `feat(products): guaranteed and best capacity on the product page (INV-16-h)`

- Backend: `Capacity` gains `best` and `by_variant` (the binding variant; where best is reached and its bottleneck), and `Product`
  gains `capacity_by_colour` (see the Contract). `max_producible` and `bottleneck` keep their meaning: the guaranteed figure.
- Frontend: the hero's number, its caption ("any mix of colours and sizes." / "any mix of colours."), "Can't produce" only at best 0;
  the bottleneck callout's three sentences with links, in the card's three readings: several colours and sizes ("(Black, size XL)"), a
  "One size" product ("(Black)", "… the smallest across every colour."; `binding.size` null) and a one-colour product ("(size XL)", "…
  the smallest across every size."). Products with no `by_variant` line render exactly as today.

**Tests**
- Contract via `assertInertia`: the example (16, 112, Black XL, Indigo + button) and a product with no `by_variant` line (`best` =
  `max_producible`, `by_variant` null). Vitest: hero and callout for the example, for Black at 0 m, for a "One size" product and for a
  one-colour product, and unchanged for the golden product.

> [!example]- Files (≈9)
> - `app/Http/Resources/{CapacityResource,ProductResource,ColourCapacityResource}.php` (one new), `resources/js/types/domain.ts`
> - `resources/js/components/products/{product-hero,bottleneck-callout}.tsx` (+ tests), FE fixtures typing `Capacity` / `Product`
> - `tests/Feature/Products/ProductContractTest.php`

#### INV-16-i · Capacity by colour, and Varies in the composition
**Commit:** `feat(products): capacity by colour and the varying composition lines (INV-16-i)`

- `CapacityByColourCard` after the callout: one row per colour, "No composition", the "All colours (any mix)" row, "Limited by" linked
  with the binding size; the skeleton while the product loads; stacked cards on a phone. The composition table's **View grid** opens
  INV-16-g's panel read-only.

**Tests**
- Vitest: the example's rows and last row, a "No composition" colour, the phone cards, the placeholder, View grid opening read-only.

> [!example]- Files (≈5)
> - `resources/js/components/products/capacity-by-colour-card.tsx` (new, + test), `resources/js/components/products/bom-table.tsx` (+ test),
>   `resources/js/pages/products/show.tsx`

#### INV-16-j · Cost and margin as a range, the list badge
**Commit:** `feat(products): unit cost and margin as a range when they vary (INV-16-j)`

- Backend: `CostBreakdown.material_cost` / `unit_cost`, `Margin.amount` / `percent` and `ProductListItem.unit_cost` become ranges.
- Frontend: `formatMoneyRange` / `formatPercentRange` (one figure when the ends are equal), the cost breakdown, the hero's unit cost and
  "varies by colour and size", the products table's cells, and `CapacityBadge` "16–112 units" (success), "0–112 units" (warning with
  the short-stock icon), "Can't produce" at best 0. Phone: each range on its own line.

**Tests**
- Contract: the example's ranges (TND 33.884–39.494, margin 49.506–55.116 · 55.62–61.93%) and a product with no `by_variant` line
  (min = max). Vitest: the formatters' rules (TND 3 decimals, equal ends), the card, the table cells and the badge's three tones.

> [!example]- Files (≈10)
> - `app/Http/Resources/{CostBreakdownResource,ProductResource,ProductListResource}.php`, `resources/js/types/domain.ts`
> - `resources/js/lib/format.ts`, `resources/js/components/products/{cost-breakdown-card,product-hero,products-table,price-margin}.tsx`,
>   `resources/js/components/shared/capacity-badge.tsx` (+ tests)

#### INV-16-k · Start batch and Undo batch on a mixed grid
**Commit:** `feat(production): Start batch and Undo batch per colour and size (INV-16-k)`

- `CuttingPreview::plan()` takes the grid and builds each variant's composition; `StartBatch` checks it with `takeVariants()` and, on a
  mixed refusal, says `ProductionMessages::shortOn()` ("Short on A. Restock it first, or lower the units." · "Short on A and B. Restock
  them first, …", composition order); a single variant or a product with no `by_variant` line keeps today's sentence. It writes the
  cut's variant rows and their per-unit amounts (`production_cut_materials.quantity_per_unit` null).
- `ProductionReturns` reads the cut's groups (its variant rows, or one group for an old cut); `UndoBatch` also counts the variant's
  units returned. The undo preview follows.
- `progress.products.materials` takes `units[colourId][sizeId]` instead of `units`; the Start batch window sends its grid and keeps
  **Start batch** reachable by keyboard but `aria-disabled` while the preview is short. `StartBatchLine.can_produce` is the guaranteed
  figure.

**Tests**
- The card's start (takes, three batches, Can produce now and Indigo's guaranteed after); both mixed sentences; the exact check
  (Black S 10 · XL 9 starts); the single-variant sentence; Undo Indigo L; all three undone = the take; a batch started before INV-16
  undone gives INV-5's figures; the preview with a grid. Vitest: the query sent, the server message, the disabled button.

> [!example]- Files (≈12)
> - `app/Services/Orders/CuttingPreview.php`, `app/Actions/Production/{StartBatch,UndoBatch}.php`, `app/Services/Production/{ProductionReturns,ProductionMessages}.php`,
>   `app/Http/Requests/Production/StartBatchPreviewRequest.php`, `app/Http/Controllers/ProductionProductMaterialController.php`,
>   `app/Http/Resources/{StartBatchPreview,StartBatchLine}Resource.php`, `resources/js/types/domain.ts`
> - `resources/js/components/production/start-batch-dialog.tsx` (+ test)
> - `tests/Feature/Production/{StartBatchTest,UndoBatchTest}.php`

#### INV-16-l · A material's Used in table
**Commit:** `feat(materials): Used in shows how a varying product uses the material (INV-16-l)`

- `MaterialUsageService` reads the variant figures: on a product with a `by_variant` line, `effective_quantity` null ("Varies"),
  `units_covered` the lowest–highest over the variants that use it, `max_producible` the guaranteed figure, `is_bottleneck` against the
  binding variant's bottleneck, `next_limit` null. Products with no such line read as today. Frontend: "Varies", "16–22 units", no
  "Suggestion:" sentence.

**Tests**
- Black denim and Indigo denim rows of the card's example; a product with only `same` lines unchanged. Vitest: the three readings.

> [!example]- Files (≈7)
> - `app/Services/Costing/{MaterialUsageService,MaterialUsage}.php`, `app/Http/Resources/MaterialUsageResource.php`, `resources/js/types/domain.ts`
> - `resources/js/components/materials/material-usage.tsx` (+ test), `tests/Feature/Materials/MaterialUsageTest.php`

### Contract (frozen before dispatch)
```ts
// New
export type ComponentQuantityMode = 'same' | 'by_variant';
export type QuantityCell = { colour_id: number; size_id: number; quantity: Decimal };  // > 0, in the material's unit
export type DecimalRange = { min: Decimal; max: Decimal };                           // min = max when nothing varies
export type CountRange = { min: number; max: number };
export type VariantRef = { colour: Colour; size: Size | null };                      // size null for a "One size" product
export type ColourCapacity = { colour: Colour;
    guaranteed: number | null; best: number | null;     // null = "No composition"
    bottleneck: MaterialRef | null; binding_size: Size | null };  // what limits guaranteed, at that size ("(XL)")
export type CapacityByVariant = { binding: VariantRef;                               // "(Black, size XL)"
    best_at: { colour: Colour; bottleneck: MaterialRef } };                         // "Best case: …, in Indigo — limited there by …"

// Changed
Capacity          += { best: number | null; by_variant: CapacityByVariant | null }  // max_producible = guaranteed; by_variant null without a By colour and size line
Product           += { capacity_by_colour: ColourCapacity[] | null }                // colour list order; null without a By colour and size line
CostBreakdown      : material_cost: DecimalRange; unit_cost: DecimalRange            // labor_cost, overhead_cost unchanged
Margin             : { amount: DecimalRange; percent: DecimalRange | null }         // amount.min = selling price − highest unit cost
ProductListItem    : unit_cost: DecimalRange
ProductComponentLine += { quantity_mode: ComponentQuantityMode; quantities: QuantityCell[] }   // [] on a `same` line
ProductComponentLine   quantity, effective_quantity, line_cost: Decimal | null; units_possible: number | null  // null = "Varies"
ProductComponentFormRow += { quantity_mode: ComponentQuantityMode; quantities: Record<number, Record<number, string>> } // '' = no cell
MaterialUsage      : effective_quantity: Decimal | null; units_covered: CountRange
StartBatchPreview  : max_cuttable null also for a mixed grid; short_message may be the "Short on …" sentence
```
Errors: `components.{i}.quantities.{colourId}.{sizeId}` (a cell) · `components.{i}.quantities` (no cell) · `components` (changed,
over 2,000) · `sizes.removed.{sizeId}` (INV-18's size lock, now with "has material quantities"). Edit product's `size_locks`,
`one_size_lock` and `other_sizes_lock` props keep their shape; their text gains the fifth reason. Routes: no new route;
`progress.products.materials` takes `units[colourId][sizeId]=n` (only n > 0) in place of `units=16`.

### Decisions
- **The calculator doesn't change; it runs per variant.** `VariantCosting` feeds it each variant's composition and sums up, so the
  golden test and every existing formula stay as they are. Variants with the same composition are costed once.
  *Amended in the build (2026-11-07, INV-16-d load test: 11 s for 25 products at the cap):* a full `calculate()` per distinct
  composition costs ~0.4 ms, and a product at the cap has up to 220 of them. `VariantCosting` now works out each line's exact figures
  once per (line, quantity) with the calculator's formulas, and per variant sums the exact line costs (rounded once), floors each
  material's units possible and picks the bottleneck with the calculator's tie-break; `CostingCalculator::calculate()` still runs,
  unchanged, for the binding variant (`single`) and for products that don't vary. An equivalence test checks every variant's figures
  against `calculate()` on that variant's composition. ADR-0027 §4 records it.
- **Best is reached at** the first variant, in colour list order then size order, with the largest capacity: the card's example names
  Indigo, limited by the button. **A colour with no line at all** gives null ("No composition") and counts for neither capacity
  figure, as an empty composition does today; its unit cost (labor + overhead) is part of the cost range (ADR-0027, updated).
- **The cap is 2,000 filled cells per product**, over all its lines, counted on the save.
- **Old production cuts are not rewritten.** A cut with no variant rows is one group, which gives exactly today's return; every new
  cut writes variant rows. ADR-0027 §7 changed from a backfill to this (no live row is touched).
- **`max_producible` keeps its key and becomes "guaranteed"**, which is today's figure for every product that doesn't vary, so
  screens that don't show colours (Start batch's "Can produce now", the dashboard alerts) stay true without a change.
- **Ranges replace single values** in `CostBreakdown`, `Margin` and `ProductListItem` rather than living beside them: one meaning per
  key. Each such change ships with its screens in one commit.
- **The grid follows the form, and the lock keeps saved cells true** (replanned 2026-11-07 on ADR-0028). The grid uses the colours
  ticked and the sizes pressed in the form; the server checks every cell against the colours and sizes the save leaves on the product,
  which the form always sends (so, as in INV-18, the last save wins for them). A size with a saved cell is locked through
  `ProductSizeLock`'s fifth reason, the slot ADR-0028 §5 kept for it, so INV-18's tooltip, refusal, second check under the row lock
  and One size sentences cover it unchanged; it also means a "One size" product with a cell can't take other sizes until its
  By colour and size lines are cleared. The category lock of the first plan is dropped: a category no longer decides sizes.
- **The product keys of the cells are `NO ACTION`, not `RESTRICT`**, so a product's own cascade still deletes its size and colour links
  and its cells in one statement, while a size taken off with cells is still refused.
- **No new generated component:** the colour rows use the existing `Collapsible` (the design names an accordion; same behaviour).

### Order
Wave 1 (backend): INV-16-a → INV-16-b ∥ INV-16-c (two developers, disjoint folders) → INV-16-d → INV-16-e (backend, then its
frontend type-compat). Wave 2: INV-16-f → INV-16-g → INV-16-m (frontend) in parallel with INV-16-k → INV-16-l (backend, then each
one's small frontend part). Wave 3: INV-16-h → INV-16-i → INV-16-j (they share the product page and `domain.ts`). Merge order is the
table's order.

### Risks
- **Costing every variant of every product on the list and the dashboard** (up to 220 variants at the cap) → one costing per distinct
  composition, the fast path for products that don't vary, constant queries (sizes through `ProductSizes::forProducts()`), and the load
  test at the cap; if it is over budget, the cells are read as plain rows instead of models.
- **`quantity` becoming nullable** breaks readers that expect a string → every reader of `ProductComponent::quantity` is listed in the
  INV-16-d and INV-16-e briefs; strict types and the golden tests catch the rest.
- **Changing a column on SQLite rebuilds the table** → the migration test runs on both databases; parity after wave 1.
- **Changing existing contract types ripples into frontend fixtures** → each contract step carries its frontend part in one commit.
- **A colour detached while its cells exist** would break the PostgreSQL key → `SyncProductColours` deletes the cells first, under the
  product lock every writer takes.
- **A size detached while its cells exist** → it can't happen through the app (the size lock under the row lock, and the request), and
  the PostgreSQL key makes it fail loudly instead of leaving orphans; the schema test proves the key.
- **`ProductForm` grows again** (INV-18's Sizes field, now the mode control and two questions) → the size questions are their own step
  (INV-16-m) with their own dialog component, so INV-16-f stays within budget.
- **The card's example** is built in the feature tests by giving the Denim Jacket the sizes S · M · L · XL (INV-18's `withSizes()`);
  the domain golden test (INV-16-b) uses ADR-0027's eight abstract variants and doesn't depend on any size list.

### Progress log
- 2026-11-07 · build started · wave 1: INV-16-a ∥ INV-16-b ∥ INV-16-c dispatched (b and c are pure domain, disjoint from a)
- 2026-11-08 · INV-16-a, -b, -c, -d approved (a: one fix round for the old rollback tests; d: one round for the load budget, list 2.1 s
  on this host vs 1.5 s, see Decisions); ADR-0027 accepted, skill and ARCHITECTURE amended; `composer test` green (3,035 passed) · next: INV-16-e
- 2026-11-08 · INV-16-e (BE + FE type-compat, one test-ripple round), INV-16-f, -g, -m, INV-16-k (BE + FE) and INV-16-l (BE) done;
  next: INV-16-l (FE), then wave 3 (INV-16-h → -i → -j), then full gates and PostgreSQL parity
- 2026-11-08 · INV-16-l (FE) and INV-16-h (BE + FE, one test-ripple round; `MaterialUsage.product_varies` added) done; next: INV-16-i ∥ INV-16-j (BE), then INV-16-j (FE)
- 2026-11-08 · INV-16-i and INV-16-j (BE + FE) done; one fix round (a test helper name clash, the hero's range test); all 13 steps
  approved. Gates: `composer test`, `npx vp test`, `npm run check`, `npm run build` green; `types:check` at the 21-error baseline (INV-20)
- 2026-11-08 · PostgreSQL parity green (3,167 passed); load test 0.83 s (list) and 0.25 s (dashboard), within 1.5 s · delivery report sent
- 2026-11-09 · fix round after acceptance (human-approved, 10 items: footer badge best, switch-back 44 px, mode check mark, One size
  field name, no colour match, first colour open, no material chosen, E3 callout, colour-off note, straight back restores) done;
  gates green again (`composer test` 3,079 passed, `npx vp test` 1,588 passed, check, build; types:check at the 21-error baseline)
