---
id: INV-2
title: Product sizes × colours, stock
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
created: 2026-10-31
tags:
  - story
  - variants
---

# INV-2 · Product sizes × colours, stock

> [!abstract] Description
> As a **workshop owner**, I want **every colour and size of a product to have its own finished stock**, so that **I know exactly what
> is on the shelf in Indigo, size M**. A **variant** is one colour in one size: the Denim Jacket in Indigo, size M. This is the first of
> three cards that share one UI card: [[INV-2-product-sizes-colours-barcodes-ui]].
> Left out on purpose: barcodes (INV-8) and printing labels (INV-9), so the Sizes & colours card shows the stock table only; orders,
> sales and "Put in stock" per colour; scanning; **Restock** and the "Last restock" line, which arrive with INV-3; clients (INV-4);
> production progress (INV-5); a composition per colour (an Indigo and a Black jacket use the same materials, so "Can produce now"
> keeps "any mix of sizes"); and stock on the Products list.

> [!info] How it works today
> The Denim Jacket page shows "Can produce now 112 units", "In stock 10 units" with the chips [S 3] [M 0] [L 5] [XL 2] and an **Update
> stock** button at the top right of that figure, and "Unit cost TND 35.754". Stock is counted per size only: nothing says how many are
> Indigo and how many Black. "Update stock · Denim Jacket" has one field per size, "was 0" under a changed size, "Total after saving 10
> units" and the note "Doesn't change materials, "Can produce now" or sales."; **Save** shows "Stock updated.".
> Since INV-1 every product has at least one colour (White for products recorded before it), shown under the header badges as
> "Colours ■ Black ■ Indigo ■ Light wash", and Edit product lets you take any colour off freely. Each colour has a short code that
> never changes (Black BLK, Indigo IND, Light wash LWH).
> No product has a stock count per colour. "Delete product?" reads "Denim Jacket (SKU PRD-JKT-001) and its composition will be removed.
> This cannot be undone.".

## Acceptance criteria
- [ ] **AC-1 · Stock is counted per colour and size.** Every colour and size of a product has its own count of finished units on the
  shelf, 0 to 1,000,000. When INV-2 arrives, the units a product had per size go into its first colour, in the order of Settings ›
  Colours: the Denim Jacket with S 3 · M 0 · L 5 · XL 2 and only White reads White S 3 · M 0 · L 5 · XL 2, and its other colours start
  at 0. There is no "No colour" stock. Nothing else changes: "Can produce now 112 units", "Unit cost TND 35.754", the materials and
  their history, the Inventory value, sales and orders stay as they are; a sale or an order never changes finished stock. Deleting a
  product deletes its stock. A product with units in stock, in any colour, still can't move to a category with other sizes (the
  rule of today's sizes, counted over all colours). **A colour with stock can't be taken off a product:** on Edit product, taking
  Indigo (6 units in stock) off the Denim Jacket and clicking **Save product** saves nothing and shows under the Colours field
  "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first."; if several such colours are
  taken off at once, each gets its own sentence, one per line in the order of Settings › Colours (Black before Indigo). These
  sentences stay until the next **Save product**, or until the colour a sentence names is added back (choosing another colour
  doesn't clear them). A colour at 0 is removed as before; if Indigo is added again later, the product's Indigo count starts at 0.
- [ ] **AC-2 · The In stock figure and the Sizes & colours card.** With Black 1 · 0 · 2 · 1, Indigo 2 · 0 · 3 · 1 and Light wash 0 ·
  0 · 0 · 0 (S · M · L · XL):
  - **In stock** reads "10 units", then one line in the order of Settings › Colours, a colour tag and its count in bold: "■ Black **4**
    · ■ Indigo **6** · ■ Light wash 0" (a colour at 0 in the grey text colour, its "0" still shown); screen readers hear "In stock
    per colour: Black 4, Indigo 6, Light wash 0". Under it the link **By size and colour**, which moves to the Sizes & colours card.
    **Update stock** is no longer on this figure. At 0 units: "0 units", no colour line, the link stays.
    Loading: today's placeholder labelled "In stock". Error: "Couldn't load the stock." with **Try again**, which reloads the figure
    and the card together. 12 colours wrap onto more lines and a 32-character colour name wraps. "Can produce now" and "Unit cost"
    are unchanged.
  - **Sizes & colours** is a full-width card under the three figures, with the heading and the line "3 colours × 4 sizes = 12
    variants. Each variant has its own stock." (other products: "1 colour × 4 sizes = 4 variants", "2 colours × One size = 2
    variants"). On the heading's row: the outline buttons **Manage sizes & colours**, which opens Edit product with the focus on the
    Colours field, and **Update stock**. Under the heading the stock table. While loading: the frame and heading at once, a
    placeholder of 3 rows; error: "Couldn't load the stock." with **Try again**.
  - **The stock table** is named "Units in stock per colour and size": **Colour** (swatch and name), one column per size in size
    order (S, M, L, XL; a shoe 36 … 46), **Total**; one row per colour in the order of Settings › Colours, and a last row **Total**
    under a heavier line: Black 1 · 0 · 2 · 1 = 4; Indigo 2 · 0 · 3 · 1 = 6; Light wash 0 · 0 · 0 · 0 = 0; Total 3 · 0 · 5 · 2 = 10.
    Figures in the number font, right-aligned, a 0 in the readable grey. Under it: "Grey zeros are variants with nothing on the
    shelf." (not shown when every cell is 0, where every cell is a grey 0). A "One size" product (Leather Tote in Camel and Black)
    has the columns **Colour** and **In stock**, no size columns and no Total column; the Total row stays with two colours or more.
    A shoe's 11 size columns scroll sideways inside the card on a laptop under 1100 px, never the page.
  - **Phone, 375 px:** the figure sits under Can produce now and its colour line wraps; in the card the buttons **Update stock** and
    **Manage sizes & colours** are full width, 44 px, one under the other; in place of the table, one card per colour ("■ Indigo · 6
    units" with the chips [S 2] [M 0] [L 3] [XL 1], zeros kept and grey), then a **Total** card "10 units" with the size totals as
    chips; nothing scrolls sideways at 320 px.
- [ ] **AC-3 · Update stock, per colour.** **Update stock** in the card opens "Update stock · Denim Jacket" with the line "Finished
  units on the shelf, counted per colour and size. Saving sets these counts; to add units that arrived, use Restock." and the ✕
  "Close".
  - **Colour** pills (swatch, name, and the colour's total after saving in grey: "Black 4", "Indigo 6", "Light wash 0"), the first
    colour chosen when the window opens. A colour with a changed size gets a small dot that screen readers hear as "changed"
    ("Indigo, 10, changed").
  - The fieldset "Units in stock per size · Indigo" holds one field per size (today's size fields) with today's count, 2 · 0 · 3 · 1,
    and "was 0" under a changed size. Switching pills keeps what was typed in every colour.
  - A grey band "Total after saving, all colours" **10 units**, following what is typed in every colour; the note "Doesn't change
    materials, "Can produce now" or sales.". Indigo M from 0 to 4 → "was 0" under M, a dot on Indigo, the pill "Indigo 10", the
    total "14 units".
  - Buttons **Cancel** and **Save**. **Save** shows a spinner, saves every colour at once (the last save wins), shows "Stock
    updated.", closes the window and puts the focus back on **Update stock**; the card and the In stock figure show the new counts.
    No history entry is written.
  - Errors: a wrong number shows under its size "M: Enter a whole number from 0 to 1,000,000."; when it is in another colour, that
    pill shows "1 error" in red text, the top of the window reads "Check Black: 1 size can't be saved.", and the window switches
    to the first colour with an error and puts the focus on its field. If the product's sizes or colours changed meanwhile, the top
    reads "The sizes or colours of Denim Jacket have changed. Enter the stock again.", the fields show the new colours and sizes
    with their counts, and nothing is saved. A failed save shows "Couldn't save the stock. Try again." and keeps what was typed in
    every colour.
  - A "One size" product has no pills: one field per colour, labelled with the swatch and the name ("■ Camel", "■ Black"), in a
    fieldset "Units in stock per colour".
  - **Phone:** the window fills the screen; the pills wrap at the top, the size fields four to a row, the total band, then **Save**
    and **Cancel** in a bar fixed at the bottom.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 4 commits
> Tech-lead · 2026-10-31 · approved 2026-10-31
> Why complex: the finished stock table changes shape and its data moves on the live database, a new rule guards a product's
> colours, and the data the product page receives and the "Update stock" save both change.

### In short
Today each row of finished stock says "this product, this size, N units". INV-2 adds the colour to that row, so a row says "this
product, in Indigo, size M, N units"; as today, a row exists only while it holds at least one unit. A migration on the live database
gives every existing row the product's first colour (White), so nothing is lost and nothing is counted twice. The product page
receives, besides today's totals, the count of every colour and size, and "Update stock" saves every colour at once, refusing the
whole save if the product's sizes or colours changed meanwhile. On Edit product, the server refuses to take off a colour that still
has stock, checked again while it holds the product's record so a stock update can't slip in between. The screens follow: the In
stock figure with its colour line and the new Sizes & colours card, then the Update stock window with its colour pills. "Can produce
now", the unit cost, materials, sales and orders read nothing of this, so no figure changes. A variant is not stored on its own: it is
a product, a colour and a size, which is all INV-8 needs to give each one a barcode and a SKU.

### Steps

| Step        | What it does                                         | Proves     | Lane         | Who      | Size       |
| ----------- | ---------------------------------------------------- | ---------- | ------------ | -------- | ---------- |
| **INV-2-a** | Stock kept per colour and size; saving it per colour | AC-1, AC-3 | CRITICAL     | Backend  | ~300 lines |
| **INV-2-b** | A colour with stock stays on the product             | AC-1       | CRITICAL     | Backend  | ~120 lines |
| **INV-2-c** | The In stock figure and the Sizes & colours card     | AC-1, AC-2 | NON-CRITICAL | Frontend | ~380 lines |
| **INV-2-d** | The Update stock window, per colour                  | AC-3       | CRITICAL     | Frontend | ~350 lines |

| AC   | Proved by                                                                                                                                                                                |
| ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-1 | INV-2-a (counts per colour and size, the move into White, nothing else changes, delete, the category lock), INV-2-b (the colour lock), INV-2-c (the lock sentences stay on Edit product) |
| AC-2 | INV-2-c (the figure, the card, the table, the phone cards, loading and error)                                                                                                            |
| AC-3 | INV-2-a (saving every colour at once, the refusals and their messages), INV-2-d (the window)                                                                                             |

The steps share no file, so each is one commit: AC-1 is INV-2-a + INV-2-b, AC-2 is INV-2-c (with AC-1's lock sentences on Edit
product), AC-3 is INV-2-d (its server side lands with INV-2-a, because the table's new shape and its writer can't be split). Every step keeps `composer test`, `npx vp test`,
`npm run check`, `npm run types:check` and `npm run build` green. INV-2-a and INV-2-b also run their PostgreSQL tests; after the last
wave the whole tree runs the full parity suite and `php artisan test tests/Browser` (after `npm run build`). INV-2-a and INV-2-d are
pushed together: in between, the old window sends a per-size count the server no longer takes (gates stay green).

#### INV-2-a · Stock kept per colour and size; saving it per colour
**Commit:** `feat(products): finished stock per colour and size, saved per colour (INV-2-a)`

- Migration on `product_size_stocks` (name and model `ProductSizeStock` kept, to keep the change small; the docblocks say "one row per
  product, colour and size"): add `colour_id` (FK → colours, `restrictOnDelete`, indexed); give every row the product's first colour
  by position, then name, then id (a row whose product has no colour, which INV-1 left impossible, links the product to its workshop's
  first colour first, so there is never a "No colour" row); make it NOT NULL; replace unique (`product_id`, `size_id`) by unique
  (`product_id`, `colour_id`, `size_id`). PostgreSQL only: a composite key (`product_id`, `colour_id`) → `product_colours (product_id,
  colour_id)`, so the database itself refuses stock for a colour the product doesn't have. A frozen data step that imports no app
  class. `down()` adds the colours back together per size and drops the column.
- `FinishedStock::of()` reads every colour of the product (list order) × every size of its list, 0 included, with each colour's total,
  the per-size totals over all colours and the grand total, computed in PHP from the rows (whole numbers, no money). `FinishedStockResource`
  adds `colours`; `units` and `sizes` keep their meaning (all colours together), so today's screens keep compiling.
- "Update stock" (`PUT products/{product}/stock`, route unchanged) takes `stock[colourId][sizeId]`: each a whole number 0 … 1,000,000
  or empty (= 0), "M: Enter a whole number from 0 to 1,000,000." on `stock.{colourId}.{sizeId}` (the size naming rule of S-70). When
  the submitted colours or sizes are not exactly the product's, "The sizes or colours of Denim Jacket have changed. Enter the stock
  again." on `stock` and nothing is saved, checked in the request and again in `UpdateFinishedStock` under the product's row lock
  (ADR-0017 decision 4). One transaction writes every colour; no history entry; "Stock updated." as today.
- `ProductSizeLock` (the category lock) and `DeleteProduct` keep working on all rows of the product, so they count every colour.
- `domain.ts`: `FinishedStockColour` and `FinishedStock.colours` (see Contract).
- The tech-lead writes in this commit: ADR-0019 "Finished stock per colour and size" (amends ADR-0017), `docs/ARCHITECTURE.md`
  (`product_size_stocks`) and the business rules file (approved by the human with this Technical work on 2026-10-31).

**Tests**
- AC-1, one migration test: Denim Jacket S 3 · L 5 · XL 2 with White and Black → White S 3 · M 0 · L 5 · XL 2, Black all 0; two
  workshops each keep their own; a product with no colour gets its workshop's first; `down()` gives back S 3 · L 5 · XL 2.
- AC-1, one read test (the `stock` group): Black 1 · 0 · 2 · 1, Indigo 2 · 0 · 3 · 1, Light wash 0 → colours in list order with
  their totals 4, 6, 0, sizes 3 · 0 · 5 · 2, units 10; a "One size" product with Camel 4 and Black 0.
- AC-1, one test that nothing else moves after an update (Can produce now 112, unit cost 35.7540, inventory value, materials history,
  sales and orders unchanged) and that a sale or an order changes no stock; one that deleting the product deletes every colour's rows;
  the category lock counts a unit in any colour.
- AC-3, one write test: Indigo M 0 → 4 saves 14 units in all, empty = 0, a size at 0 leaves no row; one validation dataset ("x",
  "-1", "1000001", "2.5" under a named size); one dataset for changed colours or sizes (a colour missing, a colour added, a foreign
  colour, another workshop's colour, a size of another list) → the message, nothing saved; one test that the check holds under the
  row lock (a colour removed between validation and write).
- One PostgreSQL schema test: the database refuses stock for a colour the product doesn't have and a second row for the same
  product, colour and size. Contract: the `FinishedStock` shape in the product contract test. Query budget: the `stock` group stays
  within 10 and doesn't grow with 12 colours × 11 sizes.

> [!example]- Files (18)
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead, once the human approves it)
> - `docs/adr/0019-finished-stock-per-colour-and-size.md` (new), `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Models/ProductSizeStock.php`
> - `app/Services/Products/FinishedStock.php`, `app/Services/Products/FinishedStockLevel.php`
> - `app/Http/Resources/FinishedStockResource.php`
> - `app/Http/Requests/Products/UpdateFinishedStockRequest.php`
> - `app/Actions/Products/UpdateFinishedStock.php`
> - `database/migrations/2026_10_31_100000_add_colour_to_product_size_stocks.php` (new)
> - `database/factories/ProductSizeStockFactory.php`
> - `resources/js/types/domain.ts`
> - `tests/Feature/Products/FinishedStockTest.php`, `FinishedStockWriteTest.php`, `FinishedStockSchemaTest.php`
> - `tests/Feature/Products/FinishedStockColoursMigrationTest.php` (new)
> - `tests/Feature/Products/ProductContractTest.php`, `tests/Feature/Performance/QueryBudgetTest.php`
> - `tests/Pest.php` (a migration helper, and a helper to set stock per colour)
> - The older migration tests that roll back `product_colours` or `product_size_stocks` (they roll this migration back first)

#### INV-2-b · A colour with stock stays on the product
**Commit:** `feat(products): a colour with stock can't be taken off a product (INV-2-b)`

- `App\Services\Products\ProductColourLock::refusals(Product, list<int> $keptColourIds)`: for every colour taken off that still has
  units, "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first.", in list order.
- `UpdateProductRequest::after()` adds them on `colours` (next to any other error); `UpdateProduct` checks again under the product's
  row lock, before syncing the colours, so a stock update and a colour change never pass each other. A colour at 0 has no rows, so it
  comes off as before, and added back later it starts at 0. On PostgreSQL the composite key of INV-2-a refuses it a third time.

**Tests**
- AC-1, one dataset on Edit product: Indigo with 6 taken off → the message on `colours`, nothing saved (name, composition and colours
  unchanged); Light wash at 0 taken off → saved; Indigo taken off at 0, added again → its count reads 0; the check under the row lock
  (stock added between validation and save → refused).

> [!example]- Files (5)
> - `app/Services/Products/ProductColourLock.php` (new)
> - `app/Http/Requests/Products/UpdateProductRequest.php`
> - `app/Actions/Products/UpdateProduct.php`
> - `tests/Feature/Products/ProductColourLockTest.php` (new)
> - `tests/Feature/Products/ProductColoursTest.php` (if one of its cases now needs stock at 0)

#### INV-2-c · The In stock figure and the Sizes & colours card
**Commit:** `feat(products): stock per colour on the product page, and the Sizes & colours card (INV-2-c)`

- **In stock**: "10 units", the colour line in list order (tag and bold count, a 0 in the grey text colour), the screen-reader sentence
  "In stock per colour: Black 4, Indigo 6, Light wash 0", the link **By size and colour** to the card; **Update stock** leaves this
  figure. At 0 units no colour line. Loading and error as the card says.
- **Sizes & colours** card under the three figures: the heading, the variants line (server counts, the card's wording: "3 colours × 4
  sizes = 12 variants…", "1 colour …", "2 colours × One size …"), **Manage sizes & colours** (Edit product with `#colours`, which puts
  the focus on the Colours field) and **Update stock**, which opens today's window unchanged (INV-2-d rebuilds it). The table "Units in
  stock per colour and size" (Colour, a column per size, Total; a Total row under a heavier line; the "One size" form; the grey-zero
  note), every figure from the server; a shoe's 11 columns scroll inside the card under 1100 px. Phone: the buttons full width, 44 px,
  and one card per colour with size chips, then a Total card. Loading: frame, heading and a 3-row placeholder; error: "Couldn't load
  the stock." with **Try again**, which reloads the figure and the card together (one deferred prop).
- The browser golden path (`tests/Browser/GoldenPathTest.php`) follows **Update stock** into the card, both in the S-73 measure and
  in its stock step; this is the only step that edits that file.

**Tests**
- AC-2, one figure test: the colour line, its order, bold counts, grey 0, the spoken sentence, the link; "0 units" with no line.
- AC-2, one card test: the variants line in its three wordings; the table's rows, columns and Total row for the worked example; the
  note shown or not; the "One size" table; the two buttons (Manage goes to Edit product `#colours`).
- AC-2, one phone test (cards per colour and the Total card, scoped with `within()`), one loading and error test (Try again reloads
  `finished_stock`), and the Edit product test that `#colours` focuses the Colours field.
- AC-1, one Edit product test: two lock sentences (Indigo, Black) stay when another colour is chosen; adding Indigo back removes its
  sentence only; Save clears what is left.

> [!example]- Files (10)
> - `resources/js/components/products/finished-stock-figure.tsx`, `finished-stock-figure.test.tsx`
> - `resources/js/components/products/sizes-colours-card.tsx` (new), `sizes-colours-card.test.tsx` (new)
> - `resources/js/pages/products/show.tsx`, `show.test.tsx`
> - `resources/js/components/products/product-form.tsx` (focus on `#colours`; the lock sentences stay until Save or until their colour
>   is back)
> - `resources/js/pages/products/edit.test.tsx`
> - `resources/js/test/fixtures/products.ts` (stock per colour for the worked example)
> - `tests/Browser/GoldenPathTest.php` (the S-73 measure)

#### INV-2-d · The Update stock window, per colour
**Commit:** `feat(products): Update stock per colour and size (INV-2-d)`

- The window keeps its props (product and stock), so the card of INV-2-c doesn't change. Inside: the line and ✕ "Close"; colour pills
  (swatch, name, the colour's total after saving in grey; the first colour chosen; a dot spoken "changed"); the fieldset "Units in
  stock per size · Indigo" with today's counts and "was 0"; what was typed is kept per colour when switching; the band "Total after
  saving, all colours" and the note; **Cancel** and **Save** (spinner, "Stock updated.", focus back on **Update stock**). It sends
  `stock[colourId][sizeId]`.
- Errors: under the size; "1 error" on another colour's pill; "Check Black: 1 size can't be saved." at the top; the window switches to
  the first colour with an error and focuses its field; the "sizes or colours have changed" message at the top with the new colours and
  sizes shown and nothing saved; "Couldn't save the stock. Try again." keeping what was typed. "One size": no pills, one field per
  colour (swatch and name) in the fieldset "Units in stock per colour". Phone: full screen, pills wrap, four size fields to a row, the
  bar fixed at the bottom.

**Tests**
- AC-3, one happy test: Indigo M 0 → 4 shows "was 0", the dot, "Indigo 10" and "14 units"; switching pills keeps it; Save sends every
  colour, spinner, focus back on **Update stock**.
- AC-3, one error test: an error in Black while Indigo is open → "1 error" on Black, the top line, the switch and the focus; one test
  for "sizes or colours have changed" and one for a failed save keeping what was typed.
- AC-3, one "One size" test. The browser golden path's stock step must still pass unchanged: the window opens on the first colour,
  whose size fields keep today's labels.

> [!example]- Files (3)
> - `resources/js/components/products/update-stock-dialog.tsx`
> - `resources/js/components/products/update-stock-dialog.test.tsx`
> - `resources/js/components/products/finished-stock-fields.tsx` (new, only if the window needs a split)

### Contract (frozen before dispatch)
- `products.show` deferred `finished_stock` (group `stock`, rescued): `FinishedStock` gains `colours`; `units` and `sizes` stay the
  totals over every colour.
- `PUT products/{product}/stock` (`products.stock.update`): body `stock: Record<colourId, Record<sizeId, number | ''>>`, every colour
  and every size of the product; errors on `stock.{colourId}.{sizeId}` (the size's message) and on `stock` (sizes or colours changed);
  success → back, toast "Stock updated.".
- `products.update`: the colour lock's messages on `colours` (one per colour taken off with stock, in list order).
- `products.edit#colours`: the fragment that focuses the Colours field (frontend only).

`resources/js/types/domain.ts`:
```ts
/** One colour of a product with its finished units per size (INV-2). */
export type FinishedStockColour = {
    colour: Colour;
    units: number; // the colour's total
    sizes: FinishedStockSize[]; // every size of the list, in order, 0 included
};
// FinishedStock gains: colours: FinishedStockColour[] — every colour of the product, in list order, 0 included.
// units and sizes stay the totals over every colour.
```

### Decisions
- **A variant is a product, a colour and a size; it has no table of its own.** Stock rows carry the three ids; INV-8's barcodes are
  shared by name + colour + size across products, so they get their own table keyed that way, and the SKU (product SKU + colour code
  + size, "One size" dropped) is built from what exists. Recorded in ADR-0019.
- **The stock table is extended, not renamed** (`product_size_stocks`, `ProductSizeStock`): renaming would touch ten more files for
  no behaviour; the docblocks and ADR-0019 say what a row is now.
- **A row still exists only while it holds a unit** (ADR-0017), so the colour lock is one `exists`, a colour added back starts at 0,
  and the category lock is unchanged.
- **Every writer takes the product's row lock** (ADR-0017 decision 4): Update stock, Update product (sizes and colours) and Delete.
- **Totals come from the server** (per colour, per size, all): the screens add nothing up, except the window's "Total after saving"
  of the whole numbers being typed, as S-56 already does.
- **"Manage sizes & colours" uses `#colours` on Edit product**, a frontend-only link, so no route changes.

### Order
Wave 1, in parallel: backend INV-2-a, then INV-2-b (one developer) · frontend INV-2-c (against the frozen contract; it compiles once
INV-2-a's types land). · Wave 2 (frontend): INV-2-d. Docker must be up for the parity runs of INV-2-a and INV-2-b.

### Risks
- The live database `laravel` holds stock → the move into White is a migration (`php artisan migrate`, run by the human before the
  click-through, never fresh); a test checks it keeps every unit and that `down()` gives the per-size counts back.
- SQLite rebuilds the table when a NOT NULL foreign key is added → the migration recreates the unique index itself, and its test runs
  on both engines (SQLite and the parity suite).
- The new composite key on PostgreSQL makes the older migration tests' `down()` fail (as INV-1's did) → they roll this migration back
  first; the full parity suite runs before delivery, not only filtered tests.
- Between INV-2-a and INV-2-d the old window's save is refused → the two commits are pushed together.
- The browser golden path measures and uses "Update stock" → INV-2-c alone updates it; INV-2-d keeps the first colour's fields as
  they are so it still passes; the browser suite runs at the end.
- 12 colours × 11 sizes = 132 fields → the request validates once per field with one rule set; the query budget test uses that size.
- The INV-1 rule that clears the Colours error once a colour is chosen would also clear the stock-lock message → each lock sentence
  stays until the next Save or until its colour is added back (the human's answer of 2026-10-31); INV-2-c carries that change in
  `product-form.tsx`.
