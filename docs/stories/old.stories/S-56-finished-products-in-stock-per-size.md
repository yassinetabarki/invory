---
id: S-56
title: Finished products in stock, per size
type: feature
phase: 5
priority: Should
size: M
status: Done
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-56 · Finished products in stock, per size

> [!abstract] Description
> As a **workshop owner**, I want **each product to show how many finished units are on the shelf in each size**, so that **I know what
> I have, for example 3 S, 5 L and 2 XL Denim Jackets, before I cut more**.
> You count and correct this stock yourself; cancelling an order adds to it (S-57). "Can produce now" stays what the materials allow, the
> Inventory value stays materials only, and recording a sale doesn't change it. A new order doesn't take from it either. The products
> list doesn't change.

> [!info] How it works today
> Invory counts materials only. Once a jacket is made it is forgotten: nothing records the finished units waiting on the shelf, and
> units cut for a client who then cancelled have nowhere to go. The Denim Jacket's page shows "Can produce now 112" and nothing about
> finished jackets.

## Acceptance criteria
- [x] **AC-1 · In stock on the product page.** Next to "Can produce now 112", the Denim Jacket's page shows "In stock 10 units" and under
  it "S 3 · M 0 · L 5 · XL 2". A product with nothing in stock shows "In stock 0 units". A "One size" product shows only "In stock 4
  units". ✅ 2026-10-23 · 83062eb
- [x] **AC-2 · Update the count.** On the "In stock" figure each size under "10 units" is a chip with its label and count: "S 3",
  "M 0", "L 5", "XL 2". A size at 0, like "M 0", is greyed. Screen readers hear "In stock per size: S 3, M 0, L 5, XL 2". At 375 px
  the chips wrap onto more lines. "Update stock", a button at the top right of the figure on the same row as "In stock", opens
  "Update stock · Denim Jacket" with a field per size holding today's count (S 3, M 0, L 5, XL 2). Changing M to 4 and clicking
  "Save" shows "Stock updated." and the page reads "In stock 14 units" with the chips "S 3", "M 4", "L 5", "XL 2". Each size takes a
  whole number from 0 to 1,000,000 ("Enter a whole number from 0 to 1,000,000."). A size left empty counts as 0. If the product's
  sizes change while the window is open, nothing is saved and the window shows the new sizes with "The sizes of Denim Jacket have
  changed. Enter the stock again." "Cancel" changes nothing. ✅ 2026-10-23 · 83062eb
- [x] **AC-3 · Nothing else changes.** After the update, "Can produce now" (112), the unit cost (TND 35.754), the materials' stock and the
  dashboard's Inventory value read as before. Recording a sale of 2 Denim Jackets leaves "In stock 14 units". A new order of Denim
  Jackets doesn't change it either. ✅ 2026-10-23 · 83062eb
- [x] **AC-4 · The category stays locked while there is stock.** With 14 Denim Jackets in stock and the jacket on no order, changing its
  category to Footwear shows "Denim Jacket has units in stock, so its category can't change to one with other sizes. Keep a category
  with sizes S, M, L, XL." and nothing is saved (a "One size" product ends with "Keep a "One size" category."). If the jacket is also
  on an order, the S-58 message shows instead. At "In stock 0 units" the S-58 rule applies alone. ✅ 2026-10-23 · 83062eb
- [x] **AC-5 · States.** If the stock can't be loaded, the figure shows "Couldn't load the stock." with "Try again". If saving fails, the
  window stays open with what you typed and shows "Couldn't save the stock. Try again." ✅ 2026-10-23 · 83062eb
- [x] **AC-6 · The "Update stock" window, and phone width.** "Update stock · Denim Jacket" shows the note "Doesn't change materials,
  "Can produce now" or sales." and the line "Total after saving 10 units", which follows what you type: changing M from 0 to 4 makes
  it "Total after saving 14 units" and shows "was 0" under M. A size you haven't changed shows no "was" hint, and putting M back to 0
  removes it. A size left empty counts as 0 in the total. At 375 px "In stock 10 units" sits right under "Can produce now", a shoe's
  11 sizes (36 to 46) wrap onto several lines without scrolling sideways, and in the window the size fields wrap onto several rows,
  each with its label, with "Save" and "Cancel" under the last row. ✅ 2026-10-23 · 83062eb

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 5 commits
> Tech-lead · 2026-10-23 · approved 2026-10-23
> Why complex: a new table (finished units per product and size), a new business rule on the category (locked while there is
> stock), a new route that writes counts, and new data for the product page.

### In short
Invory gets a small table of finished units: one row per product and size, holding how many units of that size are on the shelf. A
row exists only while its size has at least one unit, so "nothing in stock" means no rows at all. The product page asks for that stock
on its own, after the rest of the page, so if only the stock fails to load the page still shows and the figure offers "Try again".
"Update stock" saves the number you typed for every size (a count, not a plus or minus; an empty size counts as 0), in one go,
while it holds the product's record so a category change can't slip in between; if the sizes changed meanwhile, nothing is saved and
the window shows the new sizes. Nothing else reads this table: "Can produce now", the unit cost, the materials, sales, orders and the
dashboard stay exactly as they are. Deleting a product deletes its stock with it. Last, the S-58 category lock also refuses a change
while the product has units in stock (when it is also on an order, the S-58 message shows). S-57 will add cancelled units to the same
rows, and the last "Update stock" save wins ([ADR-0017](../adr/0017-finished-stock-per-size.md)).

### Steps

| Step       | What it does                                      | Proves           | Lane         | Who      | Size       |
| ---------- | ------------------------------------------------- | ---------------- | ------------ | -------- | ---------- |
| **S-56-a** | Finished stock stored per size; the page reads it | AC-1, AC-5       | CRITICAL     | Backend  | ~250 lines |
| **S-56-b** | The "In stock" figure on the product page         | AC-1, AC-5, AC-6 | NON-CRITICAL | Frontend | ~200 lines |
| **S-56-c** | Saving a count per size                           | AC-2, AC-3       | CRITICAL     | Backend  | ~230 lines |
| **S-56-d** | The "Update stock" window                         | AC-2, AC-5, AC-6 | CRITICAL     | Frontend | ~270 lines |
| **S-56-e** | The category stays locked while in stock          | AC-4             | CRITICAL     | Backend  | ~80 lines  |

| AC   | Proved by                                                                        |
| ---- | -------------------------------------------------------------------------------- |
| AC-1 | S-56-a (the counts, every size in order), S-56-b (the figure, "One size" total)  |
| AC-2 | S-56-c (counts, empty = 0, sizes changed), S-56-d (the window, new sizes shown)  |
| AC-3 | S-56-c (nothing else changes: capacity, cost, materials, dashboard, sale, order) |
| AC-4 | S-56-e (stock lock with its second sentence; the S-58 message wins; 0 in stock)  |
| AC-5 | S-56-a (the read can fail alone), S-56-b (load error), S-56-d (save error)       |
| AC-6 | S-56-b (the figure and the 2×2 grid), S-56-d (the window's fields)               |

Deleting a product with stock (the human's decision of 2026-10-23, no criterion) is proved in S-56-c.

The rows are in merge order: each server change lands right before the screen that uses it. Every step keeps `composer test`,
`npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-56-a, S-56-c and S-56-e also run their
PostgreSQL tests (`composer test:pgsql` with a filter: a new table, new constraints, new queries). After the last wave the combined tree
runs the full `composer test:pgsql` and `composer test:browser` (the product golden path gains "Update stock" in S-56-d).

#### S-56-a · Finished stock stored per size; the page reads it
**Commit:** `feat(products): finished units in stock per size, sent to the product page (S-56-a)`

- One migration, `product_size_stocks`: `workshop_id`, `product_id` (`cascadeOnDelete`; products are soft-deleted, so this only fires
  on a hard delete), `size_id` (`restrictOnDelete`), `units` (whole number), timestamps; unique (`product_id`, `size_id`), an index on
  `size_id`. A row exists only while its size has at least 1 unit. PostgreSQL only: a composite key (`workshop_id`, `product_id`) →
  `products (workshop_id, id)` (that unique key exists since 2026_10_15_100100) and a CHECK `units >= 1`. No upper bound in the
  database: 1,000,000 is the input rule of "Update stock", and S-57 adds to these rows. `down()` drops the table.
- Model `ProductSizeStock` (`BelongsToWorkshop`, `product()`, `size()`), `Product::sizeStocks()`, a factory.
- One reader, `App\Services\Products\FinishedStock::of(Product)`: every size of the product's list (from `SizeLists`), in order, with
  its units (0 when it has no row) and the total. Tests swap it to make the read fail.
- `ProductController::show` adds `finished_stock`: `Inertia::defer(…, 'stock', rescue: true)`, its own group, so a failure is
  reported and only this prop comes back empty (Inertia lists it in `rescuedProps`) while `product` and the sales load as before.
  `FinishedStockResource` shapes it. `domain.ts` as in the contract below.
- The tech-lead writes in this commit: `docs/ARCHITECTURE.md` §4 (the new table) and the business rules file (approved by the human with
  this Technical work on 2026-10-23; [ADR-0017](../adr/0017-finished-stock-per-size.md) was written and accepted at planning) (glossary "Finished stock"; a short "Finished stock (S-56)" section: what it is, a count set by
  "Update stock" where the last save wins, what never changes it, the lock and which message wins, deleting a product deletes it).

**Tests**
- AC-1, one dataset on the page's `stock` group: Denim Jacket with S 3 · L 5 · XL 2 → 10 units, sizes S 3 · M 0 · L 5 · XL 2 in that
  order · a product with no stock → 0 units, every size 0 · a "One size" product (Leather Tote) with 4 → 4 units, one size "One size" 4.
- AC-5, one test: with `FinishedStock` made to throw, the page still answers, the `default` group loads, and `finished_stock` is in
  `rescuedProps`.
- Contract: the `FinishedStock` shape joins the product contract test (one row).
- Query budget: the `stock` group stays within the page budget of 10 and doesn't grow with the number of sizes (a shoe's 11).
- One schema test (PostgreSQL only, as `OrderSchemaTest` does): the database refuses a row with 0 units, the same size twice for one
  product, and a row whose workshop differs from its product's.
- The workshop arch test treats `ProductSizeStock` as workshop data.

> [!example]- Files (16, plus the older migration tests)
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead, once the human approves it)
> - `docs/ARCHITECTURE.md`, `docs/adr/0017-finished-stock-per-size.md` (tech-lead)
> - `app/Models/ProductSizeStock.php` (new), `app/Models/Product.php`
> - `app/Services/Products/FinishedStock.php` (new)
> - `app/Http/Resources/FinishedStockResource.php` (new)
> - `app/Http/Controllers/ProductController.php` (`show`)
> - `database/migrations/2026_10_23_100000_create_product_size_stocks_table.php` (new)
> - `database/factories/ProductSizeStockFactory.php` (new)
> - `resources/js/types/domain.ts`
> - `tests/Feature/Products/FinishedStockTest.php` (new: AC-1, AC-5)
> - `tests/Feature/Products/ProductContractTest.php`, `tests/Feature/Products/FinishedStockSchemaTest.php` (new)
> - `tests/Feature/Performance/QueryBudgetTest.php`, `tests/Arch/WorkshopScopeTest.php`
> - The older migration tests that roll back the size lists migration (`sizes` is now referenced by this table; the developer greps
>   for them and rolls this migration back first, as S-60-a did)

#### S-56-b · The "In stock" figure on the product page
**Commit:** `feat(products): "In stock" per size next to "Can produce now" (S-56-b)`

- A new figure "In stock" right after "Can produce now": "10 units" (or "1 unit"), and under it the sizes, "S 3 · M 0 · L 5 · XL 2",
  zeros included, in size order. A "One size" product shows the total only. A shoe shows all 11 sizes, zeros included, "36: 0 · 37: 2
  · … · 46: 0" (the colon rule chosen at the S-60 and S-61 acceptance), wrapping onto several lines.
- The four figures sit in a grid of two by two from tablet width (Can produce now | In stock, then Unit cost | Sold) and one under
  the other on a phone, in that order; the page's three-column step at wide screens goes, so the grid stays two by two. The page
  skeleton gains an "In stock" placeholder in the same grid.
- The figure has its own `<Deferred data="finished_stock">`: a placeholder while it loads, and its `rescue` slot shows "Couldn't load
  the stock." with "Try again", which reloads only `finished_stock`.
- `formatSizes` learns to keep sizes with 0 units (an option; the order screens keep leaving them out).
- The Vitest Inertia stub learns `rescue` (it says today that rescue is not simulated).
- "Update stock" is not here yet (S-56-d).

**Tests**
- AC-1, one test: "In stock", "10 units" and "S 3 · M 0 · L 5 · XL 2"; 0 → "0 units"; a "One size" product with 4 shows "4 units"
  and no sizes line; a shoe shows all 11 sizes, "36: 0" first.
- `formatSizes`, one row added to its dataset: zeros kept on request, "36: 0 · 37: 2".
- AC-5, one test: a rescued stock shows "Couldn't load the stock."; "Try again" reloads only `finished_stock`.
- AC-6, one test: the figures in order (Can produce now, In stock, Unit cost, Sold) in a grid of one column on a phone and two from
  tablet width (no three-column step), and the sizes in a wrapping element (jsdom has no layout; the click-through checks 375 px and
  tablet width).
- The product page smoke test gets the new prop.

> [!example]- Files (8)
> - `resources/js/pages/products/show.tsx`, `resources/js/pages/products/show.test.tsx`
> - `resources/js/components/products/finished-stock-figure.tsx` (new), `finished-stock-figure.test.tsx` (new)
> - `resources/js/components/products/product-hero.tsx`
> - `resources/js/lib/sizes.ts`, `resources/js/lib/sizes.test.ts`
> - `resources/js/test/inertia.tsx` (the `rescue` slot)

#### S-56-c · Saving a count per size
**Commit:** `feat(products): update the finished stock of a product, size by size (S-56-c)`

- Route `PUT products/{product}/stock` (`products.stock.update`, digit constraint), `ProductStockController@update`.
  `UpdateFinishedStockRequest` checks the `update` policy (another workshop's product is a 404) and validates a number per size:
  - each size a whole number from 0 to 1,000,000, otherwise "Enter a whole number from 0 to 1,000,000." under that size
    (`sizes.<size id>`); a size left empty counts as 0;
  - every size of the product's list present (empty is fine) and no other size, otherwise "The sizes of Denim Jacket have changed.
    Enter the stock again." on `sizes` and nothing is saved.
- Action `UpdateFinishedStock`: in one transaction, locks the product row (the same lock `UpdateProduct` takes), reads its size list
  under that lock and checks the submitted sizes against it again (the same message on `sizes`), then writes each size: a row with
  the units, or no row at 0. It sets counts: the last save wins, also over units S-57 will add (ADR-0017 decision 3).
- Redirect to the product page with the toast "Stock updated.". On the "sizes have changed" refusal the redirect back carries the
  window's partial reload, so `finished_stock` comes back with the new sizes and their counts.
- `DeleteProduct` locks the product row and removes its stock rows in its transaction, before the soft delete (the human's decision
  of 2026-10-23: the stock goes with the product).
- Nothing else is written: no material, history entry, sale, order or product field changes.

**Tests**
- AC-2, one test: S 3 · M 4 · L 5 · XL 2 → the product page, "Stock updated.", rows S 3, M 4, L 5, XL 2 (14 units); then M 0 → no M
  row.
- AC-2, one test: M left empty with S 3 · L 5 · XL 2 → saved, no M row (10 units).
- AC-2, one validation dataset on M: "-1" · "2.5" · "abc" · "1000001" → "Enter a whole number from 0 to 1,000,000." under M · a size of
  the shoe list · a size missing → "The sizes of Denim Jacket have changed. Enter the stock again." on `sizes`. Nothing saved in any
  row.
- One test: Denim Jacket with 14 in stock is deleted → its stock rows are gone; materials, their history and other products' stock
  are unchanged.
- One route dataset: a guest → login · another workshop's product → 404 · a deleted product → 404.
- AC-3, one test on the demo: after Denim Jacket is set to 14, "Can produce now" is 112, the unit cost "35.7540", the materials and
  their history and the dashboard's figures (Inventory value included) are unchanged; recording a sale of 2 Denim Jackets and then an
  order of Denim Jackets leaves 14 units.
- One test calling `UpdateFinishedStock` directly with sizes of another list (the category changed after the request was checked):
  refused with the "sizes have changed" message, nothing saved.

> [!example]- Files (7)
> - `app/Actions/Products/UpdateFinishedStock.php` (new)
> - `app/Actions/Products/DeleteProduct.php`
> - `app/Http/Controllers/ProductStockController.php` (new)
> - `app/Http/Requests/Products/UpdateFinishedStockRequest.php` (new)
> - `routes/web.php`
> - `tests/Feature/Products/FinishedStockWriteTest.php` (new: AC-2, AC-3, deleting a product with stock)
> - `docs/ARCHITECTURE.md` §5 routes (tech-lead)

#### S-56-d · The "Update stock" window
**Commit:** `feat(products): the "Update stock" window, a field per size (S-56-d)`

- "Update stock", a button on the "In stock" figure under its sizes, opens "Update stock · Denim Jacket" with a field per size, each
  labelled with its size and holding today's count. A "One size" product gets one field labelled "One size". The fields reuse the
  order form's size fields (S-60), without "Total". A field left empty is sent empty and counts as 0.
- "Save" sends every size by id and reloads only `finished_stock`, so the rest of the page stays as it is; the window closes and the
  toast reads "Stock updated.". "Cancel" closes it and sends nothing.
- A size's message shows under that size. On "The sizes of Denim Jacket have changed. Enter the stock again." the window stays open,
  shows that message at the top and swaps its fields for the new sizes with their counts from the reloaded `finished_stock` (what was
  typed for the old sizes is dropped; the message stays until the next save).
- If saving fails (a server error or no network, not a refused number), the window stays open with what you typed and shows "Couldn't
  save the stock. Try again." (`onHttpException` / `onNetworkError` on the visit).
- Phone: the fields wrap onto several rows, each with its label, and "Save" and "Cancel" sit under the last row.
- The product golden path (browser) opens Denim Jacket, sets M to 4 and sees "In stock 14 units".

**Tests**
- AC-2, one test: the window's title and S 3, M 0, L 5, XL 2; M → 4 and "Save" sends the sizes by id and asks only for
  `finished_stock`; M cleared is sent empty; "Cancel" sends nothing. A "One size" product's window has one field "One size".
- AC-2, one `it.each` on server messages: a size's message under that size · the "sizes have changed" message at the top.
- AC-2, one test: after the "sizes have changed" refusal, with `finished_stock` now holding the shoe list, the window stays open with
  the message and shows the 11 shoe sizes with their counts.
- AC-5, one test: a server error and a network error each keep the window open with the typed values and show "Couldn't save the
  stock. Try again.".
- AC-6, one test: the fields in a wrapping layout, each labelled, "Save" and "Cancel" after the last field.
- Browser: the golden path step above.

> [!example]- Files (6)
> - `resources/js/components/products/update-stock-dialog.tsx` (new), `update-stock-dialog.test.tsx` (new)
> - `resources/js/components/products/finished-stock-figure.tsx`, `finished-stock-figure.test.tsx`
> - `resources/js/pages/products/show.tsx`
> - `tests/Browser/GoldenPathTest.php`

#### S-56-e · The category stays locked while in stock
**Commit:** `feat(products): a product with finished units in stock keeps the sizes of its category (S-56-e)`

- `ProductSizeLock::refusal()` also refuses a move to a category with another size list while the product has any row in
  `product_size_stocks`: "Denim Jacket has units in stock, so its category can't change to one with other sizes." followed by the
  S-58 ending, "Keep a category with sizes S, M, L, XL." (a list with several sizes) or "Keep a "One size" category." (a one-size
  list). When the product is also on an order the S-58 message shows instead: the order check runs first. Moves within the same list
  stay allowed; at 0 units (no row) the S-58 rule applies alone.
- It runs where the S-58 lock already runs: in the update request and again in `UpdateProduct` under the product row lock. Because
  `UpdateFinishedStock` takes that same lock, a stock update and a category change can't pass each other.

**Tests**
- AC-4, one dataset: Denim Jacket with 14 in stock and on no order → Footwear refused with "Denim Jacket has units in stock, so its
  category can't change to one with other sizes. Keep a category with sizes S, M, L, XL.", nothing saved · set to 0 → Footwear allowed
  · in stock and on an order → the S-58 message ("Denim Jacket is on orders, …") · at 0 in stock and on an order → the S-58 message ·
  Leather Tote with 4 in stock → Accessory allowed (same list) · Leather Tote with 4 in stock → Apparel refused, the message ending
  "Keep a "One size" category.".
- One test: stock written between the request and the Action → `UpdateProduct` refuses too, nothing saved.

> [!example]- Files (3)
> - `app/Services/Products/ProductSizeLock.php`
> - `tests/Feature/Products/ProductSizeLockTest.php`
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead: the lock line)

### Contract (frozen before dispatch)
- **Page prop** on `products/show`: `finished_stock`, deferred in its own group `stock`, rescued.
  ```ts
  /** Finished units of a product on the shelf in one size (S-56). */
  export type FinishedStockSize = { size: Size; units: number };
  /** Every size of the product's list, in order, 0 included; `units` is their sum. */
  export type FinishedStock = { units: number; sizes: FinishedStockSize[] };
  export type ProductShowProps = {
      // …the three props of today, unchanged
      finished_stock?: FinishedStock | null; // deferred prop (group 'stock'); null and listed in rescuedProps when it couldn't load
  };
  ```
- **Route** `PUT /products/{product}/stock`, name `products.stock.update`, `ProductStockController@update`. Body
  `{ sizes: { [sizeId: number]: string } }` with every size of the product's list (`''` counts as 0). Errors: `sizes.<size id>`
  ("Enter a whole number from 0 to 1,000,000."), `sizes` ("The sizes of <product> have changed. Enter the stock again."). Success:
  redirect to `products.show` with the flash toast "Stock updated."; the window submits with `only: ['finished_stock']`,
  `preserveScroll`, `preserveState`, so a refusal also brings back `finished_stock` with the product's current sizes.
- **PHP**: `ProductSizeStock` (table `product_size_stocks`: `workshop_id`, `product_id`, `size_id`, `units` ≥ 1), `Product::sizeStocks()`,
  `FinishedStock::of(Product $product)`, `UpdateFinishedStock::handle(Product $product, array<int, int> $unitsBySizeId): void`,
  `ProductSizeLock::refusal()` (same signature). `ProductResource`, `ProductListResource` and the dashboard don't change.

### Decisions
- **A row per product and size, only while it holds at least one unit.** The AC-4 lock is one `exists`, and a product at 0 that changes
  category leaves nothing pointing at its old sizes. Rejected: a row for every size including 0 (rows to rewrite on every category
  change), a JSON column on `products` (nothing to check or lock per size, and S-57 adds per size), a column per size.
- **"Update stock" sets counts, it doesn't add or remove.** The window shows today's count and saves what you typed; an empty size
  counts as 0. No history entry: no criterion asks for one, and `stock_movements` is the materials' ledger (each entry points at a
  material).
- **The last save wins (Q-H1, the human's decision of 2026-10-23).** Once S-57 adds cancelled units, a count saved from a window opened
  before the cancellation replaces them: the count you save is the count on the shelf. No version check.
- **The sizes are checked under the product's lock.** If the product's list changed since the window opened, nothing is saved and the
  window gets the new sizes from the same partial reload, with "The sizes of Denim Jacket have changed. Enter the stock again.".
- **Which message wins (Q1):** a product on an order gets the S-58 message even when it also has stock; the stock message, with the
  same "Keep a category with …" ending as S-58 (Q2), shows only for a product with stock and on no order.
- **Deleting a product deletes its stock (Q8).** `DeleteProduct` removes the rows under the product's lock before the soft delete, so a
  deleted product holds no shelf units.
- **Screens (Q5–Q7):** a one-size product's single field is labelled "One size"; a shoe shows all 11 sizes under "In stock", zeros
  included; the product page's figures sit two by two from tablet width and one under another on a phone.
- **Its own deferred group, rescued** (`Inertia::defer(…, 'stock', rescue: true)` with `<Deferred rescue>`, Inertia 3.8): a failure
  touches only the figure (AC-5), "Try again" reloads only it, and saving reloads only it, so the page doesn't flash back to its
  skeleton.
- **Locks:** `UpdateFinishedStock`, `UpdateProduct` and `DeleteProduct` take the same product row lock. S-57 will add cancelled units
  through its own Action on the same rows, under that same lock.
- Finished stock never reaches `App\Domain\Costing` and has no money: whole units only, plain integers.
- [ADR-0017](../adr/0017-finished-stock-per-size.md) (Accepted 2026-10-23) records the table, the count-not-delta rule, the last save winning,
  the lock and which message wins, the delete, and the S-57 hook.

### Order
Wave 1 (backend): S-56-a · Wave 2, after the wave 1 types land: S-56-b (frontend) ∥ S-56-c (backend) · Wave 3: S-56-d (frontend,
after S-56-c's route) ∥ S-56-e (backend).

### Risks
- Older migration tests roll back the size lists migration, which drops `sizes`, now referenced by `product_size_stocks` → the
  developer finds them and rolls this migration back first (S-60-a did the same).
- Saving with `only: ['finished_stock']` relies on Inertia keeping the partial reload across the redirect → checked in S-56-d's test
  and the browser golden path; if it doesn't hold, the save reloads the whole page (skeleton for a moment) and we say so before merging.
- `onHttpException` / `onNetworkError` must stop Inertia's own error window → the frontend developer reads it in
  `node_modules/@inertiajs/core` before coding.
- The Vitest Inertia stub doesn't simulate `rescue` today → S-56-b adds it, with a test of its own branch.
- `ProductFactory` picks a random category ([F-68](../TECH-DEBT.md)) → every new test pins Apparel or Bag.
- Once S-57 adds units, a count saved from a window opened before a cancellation overwrites them → the human chose this (the last save
  wins, ADR-0017); S-57's Action takes the product's lock, so the two never interleave mid-write.
- The "sizes have changed" window must keep its message while its fields are rebuilt for the new sizes → S-56-d's test covers it; the
  fields are rebuilt from the reloaded `finished_stock`, not by remounting the form, which would drop the message.
- Deleting a product now writes to a second table → `DeleteProduct` does it in its existing transaction under the product's lock;
  S-56-c's test checks nothing else changes.

### Progress
- 2026-10-23 · acceptance fix round (frontend, against the design board): size chips with a spoken line, "Update stock" at the top right, "Total after saving" and "was N" in the window, "31 units" spacing. Gates green.
