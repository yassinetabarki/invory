---
id: INV-13
title: Search products and see stock when taking an order
type: feature
phase: Colour × size variants
priority: Must
size: M
status: Planned
created: 2026-11-03
tags:
  - story
  - variants
---

# INV-13 · Search products and see stock when taking an order

> [!abstract] Description
> As a **workshop owner**, I want **to search a product while I take an order and see its shelf stock for every colour and size**,
> so that **I can answer "Do we have this jacket in Indigo M, or must we make it?" before I add it, and record the order in the exact
> colours and sizes the client wants**.
> New: the **Search products** box on New order and Edit order, its results with the stock of each colour and size, **Add to order**,
> and a quantity grid per colour and size with the stock under each field. Changed: an order line now holds **colour and size**, not
> size alone, wherever an order's lines are written (Orders list, order page, client page). The pages are drawn in
> [[INV-13-order-products-ui]].
> Left out on purpose: setting shelf stock aside for an order (open orders don't reserve stock), a sale or a restock from New order,
> "Can produce now" (the search and the order show only what is on the shelf), and the order tracker (Delivered, Cancel units), which is INV-14.

> [!info] How it works today
> New order has a Products card where each line is a picker "Choose a product…" listing every active product, with no stock figure,
> then "How many units of each size?" with one field per size. **Add product** adds another line. An order line has no colour: the
> Orders list reads "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)". To know whether the jacket is on the shelf, the owner leaves the
> order, opens the Denim Jacket page, reads its Sizes & colours card ("Indigo M 0") and comes back.
> The order page shows "Denim Jacket: units by size and stage" with To start · In cutting · Ready to ship · Shipped per size, and its
> **Start cutting**, **Mark ready**, **Ship** and Move back buttons. In Edit order a size can't go below its started units ("M can't be
> less than the 10 units already in cutting, ready to ship or shipped.") and a started line can't be removed ("Denim Jacket can't be
> removed: 30 units have started.").

## Acceptance criteria

Today is 30 Oct 2026. Shelf stock used in every example (S · M · L · XL):

| Product              | Colour     | S | M | L | XL |
| -------------------- | ---------- | - | - | - | -- |
| Denim Jacket         | Black      | 1 | 0 | 2 | 1  |
| Denim Jacket         | Indigo     | 2 | 0 | 3 | 1  |
| Denim Jacket         | Light wash | 0 | 0 | 0 | 0  |
| Denim Jeans 5-pocket | Indigo     | 0 | 5 | 2 | 0  |
| Denim Jeans 5-pocket | Black      | 1 | 2 | 0 | 0  |

The Denim Jacket (SKU PRD-JKT-001) and the Denim Jeans 5-pocket each read "In stock 10 units". Settings › Colours lists Black,
Indigo, Light wash in that order.

- [ ] **AC-1 · Search products.** New order's Products card reads "Products" · "Search what the client wants, check what is in
  stock, then add it." and has the box **Search products** (placeholder "Name, SKU, colour or barcode", a **Clear search** button)
  with the hint "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of stock can still be
  ordered."
  - It finds **active** products by name, SKU, a variant SKU ("PRD-JKT-001-IND-M"), a colour name or a barcode number. Typing "den"
    shows "2 products found": Denim Jacket and Denim Jeans 5-pocket.
  - Each result shows its photo, name and SKU, "In stock 10 units" (no "Can produce now"), **Add to order** (read as
    "Add Denim Jacket to this order") and the table "Stock of Denim Jacket by colour and size": one row per colour in Settings ›
    Colours order, one column per size, each cell the number or an amber **Out**. Denim Jacket reads Black 1 · Out · 2 · 1, Indigo
    2 · Out · 3 · 1, Light wash Out in every size. A "One size" product has the columns Colour · Stock.
  - At most 6 results; beyond, "Showing 6 of 14. Keep typing to narrow the list."
  - States: before typing, only the box and its hint; while searching, a placeholder block and "Searching…"; no match, "No product
    matches "denm xyz"." · "Check the spelling, or search by SKU, colour or barcode."; a failure, "Couldn't search the products." ·
    **Try again**, with the lines already on the order kept.

- [ ] **AC-2 · Quantities per colour and size.** **Add to order** puts the product under "On this order · 1 product" and moves the
  focus to its first quantity field; its result then shows **On this order** (with a check), which moves the focus to that line.
  - Each product shows its name, SKU, **Remove** (read as "Remove Denim Jacket from this order")
    and the table "Quantity per colour and size · Denim Jacket": rows are the product's colours, columns its sizes and **Total**. Each
    cell is a field with its stock under it: "In stock 2" in grey, or **Out of stock** in amber and bold. A "One size" product has the
    columns Colour · Quantity.
  - Typing Indigo S 4 and Indigo M 8 on the Jacket and Indigo M 10 on the Jeans shows "Total 12 units" and "Total 10 units", the band
    "Total on this order · 22 units · 2 products", and "22 units · 2 products" beside **Save order** and **Cancel**.
  - An empty cell or 0 means no units. Out of stock never blocks the order and no warning appears when a number is above the stock.
  - With no product: "No products on this order yet" · "Search for a product above and add what the client ordered."
  - With 50 products on the order, **Add to order** can't be pressed and reads beside it "An order can hold up to 50 products."

- [ ] **AC-3 · Saving the order.** **Save order** records one line per product with its units per colour and size, then shows "Order
  recorded." (or INV-4's toast for a new client) and opens the order page. Saving changes no stock, no material and no sale.
  - Errors appear under their field or line, the focus on the first one: "Indigo M: Enter a whole number from 0 to 1,000,000." ·
    "Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order." · "A line can hold up to 1,000,000 units." ·
    "The colours or sizes of Denim Jacket have changed. Enter its quantities again." · with no product, "Add at least one product to
    this order."
  - While saving, **Save order** shows a spinner and keeps its label. If saving fails: "Couldn't save the order. Try again.", with
    everything typed kept.

- [ ] **AC-4 · Edit order.** Edit order has the same Products part and grid, filled with the order's lines; **Save order** shows "Order
  updated.". A cell whose units have started (in cutting, ready to ship or shipped) shows "At least 10" under its field and can't go
  lower: "Indigo M can't be less than the 10 units already in cutting, ready to ship or shipped." A line with started units can't be
  removed: "Denim Jacket can't be removed: 30 units have started." A product already on the order stays on it even if it was
  archived since.

- [ ] **AC-5 · Lines read with their colour.** The Orders list and the client page write "50 × Denim Jacket (Indigo S 10 · M 20 · L 15
  · XL 5)"; with two colours "4 × Denim Jacket (Black M 1 · Indigo S 1 · M 2)". Until INV-14 replaces it, the order page keeps its
  stages and buttons, each row now a colour and size ("Indigo M"), and **Start cutting**, **Mark ready**, **Ship** and the Move back
  buttons act on that colour and size.

- [ ] **AC-6 · Colours on orders.** An order recorded before this card keeps its quantities, sizes and started units, and reads as
  the product's first colour in Settings › Colours order (Boutique Lina's 50 Denim Jackets read "Black S 10 · M 20 · L 15 · XL 5").
  The owner can correct the colour in Edit order, except for units that have started.
  - In Edit product, taking a colour off a product is refused while open orders hold units of it that are not shipped yet: with 12
    Indigo Denim Jackets ordered and not shipped, "Indigo has 12 units on open orders, so it can't be removed from Denim Jacket."
    (1 unit: "has 1 unit on open orders"). The existing refusals (units on the shelf, units in production) stay as they are.

- [ ] **AC-7 · Phone and keyboard.** At 375 px, a result is a box with one group per colour and a chip per size ("S 1", "M" with the
  amber "Out", "L 2"); a colour with nothing reads "Out of stock in every size"; **Add to order** is full width. The quantities read
  per colour (swatch, name, the colour's total) with three sizes to a row, each field with "In stock 2" or "Out of stock" under it.
  **Save order** sits in a bar fixed at the bottom with "22 units · 2 products" above it, and never hides the field with the focus.
  Every control is at least 44 px and nothing scrolls sideways at 320 px; 11 shoe sizes scroll inside their table, never the page.
  The whole flow works with the keyboard alone, and stock is never shown by colour alone.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 8 commits
> Tech-lead · 2026-11-03 · approved 2026-11-03 · delivered 2026-11-03
> Why complex: a migration of every order line, a changed contract (the order form, the order page's moves, a new search route), a
> new business rule (a colour on open orders can't be removed) and the order page's cutting moving to colour and size.

### In short
Each size row of an order line gains a **colour**: the rows already there take their product's first colour, so no unit is lost and
the started counts stay where they are. The order forms stop loading every product: a new search route answers with at most 6 active
products, each with its shelf stock per colour and size, and Edit order receives the same data for the
products already on the order. Saving, the Edit order floors and the order page's moves (Start cutting, Mark ready, Ship and back) then
work per colour and size, until INV-14 removes the cutting from orders. Edit product refuses to take off a colour that open orders
still hold. The frontend redraws the Products card (search, results, grid, totals, phone layout) and writes every line with its colour.

### Steps

| Step         | What it does                                              | Proves          | Lane         | Who      | Size       |
| ------------ | --------------------------------------------------------- | --------------- | ------------ | -------- | ---------- |
| **INV-13-a** | A colour on every order size row; old rows get the first  | AC-6            | CRITICAL     | Backend  | ~250 lines |
| **INV-13-b** | A colour on open orders can't come off its product        | AC-6            | CRITICAL     | Backend  | ~100 lines |
| **INV-13-c** | The product search route with the shelf stock             | AC-1            | CRITICAL     | Backend  | ~250 lines |
| **INV-13-d** | Save and edit an order per colour and size                | AC-3, AC-4      | CRITICAL     | Backend  | ~400 lines |
| **INV-13-e** | The order page's moves per colour and size                | AC-5            | CRITICAL     | Backend  | ~300 lines |
| **INV-13-f** | Search products and its results on the order forms        | AC-1, AC-7      | CRITICAL     | Frontend | ~350 lines |
| **INV-13-g** | The quantity grid, totals, errors and Edit order floors   | AC-2–AC-4, AC-7 | CRITICAL     | Frontend | ~450 lines |
| **INV-13-h** | Lines read with their colour; the order page per colour   | AC-5, AC-6      | NON-CRITICAL | Frontend | ~300 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
run their PostgreSQL tests; after INV-13-h the whole tree runs `composer test:pgsql`.

#### INV-13-a · A colour on every order size row; old rows get the first
**Commit:** `feat(orders): an order line counts its units per colour and size (INV-13-a)`

- Migration `add_colour_to_order_line_sizes`: `order_line_sizes.colour_id` (nullable, FK `colours` restrict, indexed), filled with the
  first colour of the line's product (position, name, id; a deleted product's lines too), the way `2026_10_31_100000` filled the
  stock rows: `DB::table` only, no app class, chunks of 500. A product with lines and no colour is first linked to its workshop's
  first colour. Then `NOT NULL`, and the unique key grows from (line, size) to (line, colour, size). `down()` adds each line's colours
  back into one row per size (quantity and the three counts summed) before dropping the column.
- `OrderLineSize`: `colour_id`, the `colour` relation, `#[Fillable]` gains `colour_id`. The factory picks the product's first colour.
- `OrderBook` loads the rows with their colour and orders them by colour (position, name, id) then size.
- The hand-kept migration list (F-60) in `tests/Pest.php` gains it.
- The tech-lead writes in this commit: ADR-0025 "Order lines per colour and size" (amends ADR-0016: the row key, the first colour for
  old rows, the moves per colour and size until INV-14) and `docs/ARCHITECTURE.md`.

**Tests**
- Migration: every row gets its product's first colour; a deleted product's rows too; a product with no colour gets the workshop's
  first; quantities and the three counts unchanged; `down()` adds two colours of a size back into one row. PostgreSQL: a null colour
  and a repeated (line, colour, size) are refused. Arch: `OrderLineSize` still uses `BelongsToWorkshop`.

> [!example]- Files (≈7)
> - `database/migrations/2026_11_06_100000_add_colour_to_order_line_sizes.php` (new)
> - `app/Models/OrderLineSize.php`, `database/factories/OrderLineSizeFactory.php`, `app/Services/Orders/OrderBook.php`
> - `docs/adr/0025-order-lines-per-colour-and-size.md` (new), `docs/ARCHITECTURE.md`
> - `tests/Feature/Orders/OrderLineColoursMigrationTest.php` (new), `tests/Pest.php`

#### INV-13-b · A colour on open orders can't come off its product
**Commit:** `feat(products): a colour still on open orders can't be removed from its product (INV-13-b)`

- `ProductColourLock::refusals()` adds a third read: per colour being taken off, the units of this product's order lines in that colour
  not shipped yet (`quantity − shipped` of the size rows, summed), only where it is above 0. "Indigo has 12 units on open orders, so it
  can't be removed from Denim Jacket." (1: "has 1 unit on open orders"). The shelf sentence wins, then production, then orders, one
  sentence per colour, in list order. `UpdateProduct` already calls it under the product's lock, so nothing else changes.

**Tests**
- 12 Indigo units ordered and not shipped: refused with the sentence; 1 unit: the singular; every unit shipped: allowed; another
  product's order: allowed; a colour with stock and orders shows the shelf sentence only; nothing written on a refusal.

> [!example]- Files (≈2)
> - `app/Services/Products/ProductColourLock.php`
> - `tests/Feature/Products/ProductColourLockTest.php`

#### INV-13-c · The product search route with the shelf stock
**Commit:** `feat(orders): search products with their stock per colour and size (INV-13-c)`

- `App\Services\Orders\OrderProductSearch::find(string $term)`: trimmed, at most 64 characters; empty → nothing. Active, non-deleted
  products whose name or SKU contains the term, or whose SKU is the start of a variant SKU typed in full ("PRD-JKT-001-IND-M":
  the term starts with the SKU and "-"), or with a colour whose name contains the term, or with a barcode whose number is the term
  (`RestockBarcodeLookup`'s matching, reused). By name, at most 6, plus the count of every match ("Showing 6 of 14").
- Each result: the product (id, name, SKU, photo URL), its sizes, `one_size` and its `FinishedStock` (`FinishedStock::of`). No costing:
  the search shows only the shelf (the human's answer, 2026-11-03).
- `OrderProductSearchController`: `GET orders/products/search?q=` (JSON, ADR-0009 rule 5), `Gate::authorize('create', Order::class)`,
  registered before the resource.

**Tests**
- "den" finds the Jacket and the Jeans with AC-1's stock tables; by SKU, a variant SKU, a colour name, a barcode number; archived,
  draft and deleted products left out; another workshop's product never found (by name, SKU or barcode); 14 matches → 6 and `total`
  14; a one-size product; a guest is sent to sign in.

> [!example]- Files (≈7)
> - `app/Services/Orders/OrderProductSearch.php` (new), `app/Http/Controllers/OrderProductSearchController.php` (new),
>   `app/Http/Resources/OrderStockProductResource.php` (new), `routes/web.php`, `resources/js/types/domain.ts`
> - `tests/Feature/Orders/OrderProductSearchTest.php` (new)

#### INV-13-d · Save and edit an order per colour and size
**Commit:** `feat(orders): record and edit an order's units per colour and size (INV-13-d)`

- The form sends each line as `{ product_id, units: { colourId: { sizeId: '4' } } }`, as New restock does; a one-size product (and a
  line recorded before sizes) has one size key per colour. `StoreOrderRequest` checks each cell (empty or a whole number from 0 to
  1,000,000: "Indigo M: Enter a whole number from 0 to 1,000,000."), a colour that isn't the product's (Edit order also accepts the
  colours already on the line) or a size of another list ("The colours or sizes of Denim Jacket have changed. Enter its quantities
  again."), the line's total from 1 ("Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order.") to 1,000,000
  ("A line can hold up to 1,000,000 units."), and no line ("Add at least one product to this order.").
- `SyncOrderLines` writes one row per colour and size typed above 0, matched on (colour, size). A started row keeps at least its started
  units ("Indigo M can't be less than the 10 units already in cutting, ready to ship or shipped.", under `lines.N.units.C.S`), and a
  line with started units can't be removed (today's sentence). It still re-checks under the products' locks.
- Props: New order and Edit order no longer get `products` and `size_lists`. Edit order gets `line_products`: for each line, in order,
  the same data as a search result (deleted or archived products included), so its grid and stock show without a search.
- `OrderLineSizeResource` gains `colour` (`Colour`); `OrderMessages` gets the new sentences.

**Tests**
- AC-3's order saved as 3 rows (Jacket Indigo S 4 · M 8, Jeans Indigo M 10) and no stock, material or sale written; every refusal with
  its key and sentence; 50 products / 51; Edit order: a started cell lowered and a started line removed refused, an unstarted colour
  moved to another, an archived product kept; another workshop's product a validation error; PostgreSQL run.

> [!example]- Files (≈9)
> - `app/Http/Requests/Orders/{StoreOrderRequest,UpdateOrderRequest}.php`, `app/Actions/Orders/SyncOrderLines.php`,
>   `app/Services/Orders/{OrderBook,OrderMessages}.php`, `app/Http/Controllers/OrderController.php`
> - `app/Http/Resources/OrderLineSizeResource.php`, `resources/js/types/domain.ts`, `resources/js/pages/orders/{create,edit}.tsx` (props only)
> - `tests/Feature/Orders/{OrderColoursWriteTest,OrderSizesWriteTest}.php`

#### INV-13-e · The order page's moves per colour and size
**Commit:** `feat(orders): move an order's units per colour and size (INV-13-e)`

- The move and its preview take `units: { colourId: { sizeId: n } }` instead of `sizes`; `MoveOrderLineUnits` locks and checks each
  (colour, size) row, with "Indigo M" in its limit sentence and the errors under `units.C.S`. The cut's and the stock movement's size
  snapshot gain the colour ("Indigo M 4"); older snapshots read as before. Materials are still taken and returned for the line's total.

**Tests**
- Start cutting Indigo M 4 takes the materials of 4 and counts them on that row only; a limit refused per colour and size; Mark ready,
  Ship and each move back; a colour no longer on the line refused; the snapshot with its colour; PostgreSQL run.

> [!example]- Files (≈7)
> - `app/Actions/Orders/{MoveOrderLineUnits,TakeMaterialsForCut,ReturnMaterialsFromCuts}.php`, `app/Services/Orders/CuttingPreview.php`
> - `app/Http/Requests/Orders/{MoveOrderLineUnitsRequest,MovePreviewRequest}.php`, `resources/js/types/domain.ts`
> - `tests/Feature/Orders/{MoveSizesTest,MoveOrderLineUnitsTest}.php`

#### INV-13-f · Search products and its results on the order forms
**Commit:** `feat(orders): search products and see their stock on New order and Edit order (INV-13-f)`

- `components/orders/product-search.tsx`: the labelled box, the hint, **Clear search**, a debounced call to the search route (the last
  answer wins), the states of AC-1 (opened, searching, results, no match, failed with **Try again**), "Showing 6 of 14…".
- `product-search-result.tsx`: photo, name, SKU, "In stock 10 units", **Add to order** / **On this
  order** / disabled at 50, and the table "Stock of … by colour and size" (a labelled region that scrolls on its own); on a phone the
  colour groups with size chips and "Out of stock in every size". Stock is a word as well as a colour ("Out").

**Tests**
- Vitest with the route mocked: each state and sentence, "den" → the two tables cell by cell, On this order moves the focus, the 50th
  product, a one-size product, the phone layout's chips.

> [!example]- Files (≈6)
> - `resources/js/components/orders/{product-search,product-search-result}.tsx` (new, + tests)
> - `resources/js/components/orders/use-product-search.ts` (new, + test)

#### INV-13-g · The quantity grid, totals, errors and Edit order floors
**Commit:** `feat(orders): quantities per colour and size with the stock under each field (INV-13-g)`

- `order-lines-editor.tsx` is rebuilt on the search: **On this order · 2 products**, one block per product (name, SKU,
  **Remove**), the grid "Quantity per colour and size · Denim Jacket" with **Total** per colour, "In stock 2" / **Out of stock** under
  each field, "Total 12 units", the band "Total on this order · 22 units · 2 products", "22 units · 2 products" beside the buttons, the
  empty state. Totals are whole-number sums of what is typed, shown only (the server checks every number). Edit order: "At least 10"
  and the locked **Remove** from each row's `started`. Errors under their field, the focus on the first. The phone layout: a band per
  colour, three sizes to a row, **Save order** in a fixed bar that never hides the focused field. The old product picker
  (`order-line-row.tsx`, `order-size-fields.tsx`) goes.

**Tests**
- Vitest: AC-2's typing and every total, an empty cell, 0, Remove, the first quantity focused after Add, every save error under its
  field, the spinner, "Couldn't save the order. Try again." with what was typed kept, Edit order's floors and locked Remove, a one-size
  product, the phone bar.

> [!example]- Files (≈8)
> - `resources/js/components/orders/{order-lines-editor,order-form,quantity-grid}.tsx` (+ tests; `quantity-grid` new)
> - `resources/js/components/orders/{order-line-row,order-size-fields}.tsx` (removed, with their tests)
> - `resources/js/pages/orders/{create,edit}.tsx` (+ tests)

#### INV-13-h · Lines read with their colour; the order page per colour
**Commit:** `feat(orders): order lines read with their colour, and the order page moves per colour (INV-13-h)`

- The Orders list and the client page: "50 × Denim Jacket (Indigo S 10 · M 20 · L 15 · XL 5)", a colour named once before its sizes
  ("Black M 1 · Indigo S 1 · M 2"), rows in the server's order. The order page's "units by size and stage" table and the move window
  list a row per colour and size ("Indigo M"); the window sends `units` by colour and size.

**Tests**
- Vitest: the two summaries, a one-size line ("Camel 10"), the stage table and the move window per colour and size.

> [!example]- Files (≈8)
> - `resources/js/components/orders/{orders-table,size-stages-table,move-units-dialog,order-line-card}.tsx` (+ tests)
> - `resources/js/lib/order-line-summary.ts` (new, + test)

### Contract (frozen before dispatch)
```ts
export type OrderStockProduct = {
    product: OrderProduct & { photo_url: string | null };
    sizes: Size[];          // the product's list, in order
    one_size: boolean;
    stock: FinishedStock;   // every colour of the product, in list order, 0 included
};
/** `GET orders/products/search?q=` (at most 6, by name). */
export type OrderProductSearch = { total: number; products: OrderStockProduct[] };
// Changed
OrderLineSize += { colour: Colour };                     // rows by colour (list order), then size
OrderLineFormRow = { product_id: number; units: Record<number, Record<number, string>> }; // colour id → size id → typed ('' = 0)
OrderCreateProps -= { products, size_lists };
OrderEditProps -= { products, size_lists } += { line_products: OrderStockProduct[] }; // one per line, in line order
OrderLineMove payload: { move, units: Record<number, Record<number, number>> };     // was `sizes`
```
Errors: `lines` · `lines.N` · `lines.N.product_id` · `lines.N.units` (the line) · `lines.N.units.C.S` (a cell). Route:
`orders.products.search` GET orders/products/search.

### Decisions
- **One row per colour and size, the first colour for old rows**, as the stock rows did in INV-2: no unit is lost and no count moves.
- **The search is a JSON route, not a prop:** the forms stop loading every product, and the stock shown is fresh at each search.
- **The grid's rows on Edit order are the product's colours plus any colour already on the line**, so a shipped row in a colour since
  removed still shows and keeps its units.
- **A line recorded before sizes** keeps one field per colour (as a one-size product), with the colour's total stock under it.
- **The cutting on the order page is kept per colour and size** (the human's answer 1a), with the smallest change to its Actions: it
  goes with INV-14.
- **Wording the card doesn't give, written here:** none planned; any found during the build is listed in the delivery report.

### Order
Wave 1 (backend, one after another): INV-13-a → INV-13-b → INV-13-c → INV-13-d → INV-13-e. Wave 2 (frontend, after wave 1's types
land): INV-13-f → INV-13-g, in parallel with INV-13-h. Merge order is the step order.

### Risks
- **The migration touches every order line** → `DB::table` only, chunks, a test per path (deleted product, no colour), `down()` tested;
  the dev database is migrated with `php artisan migrate`, never refreshed.
- **Variant SKU and barcode matching** reuse INV-8's rules, so a label read out in a message finds the same product as the scan field.
- **The cutting per colour is thrown away by INV-14** → its change is kept to the request keys, the row lookup and the snapshot.
- **Size:** about 2,400 lines over 8 steps, more than an M; the steps are small enough to review one at a time.

### Delivered differently from the plan
- **The move window sends `cells`, not `units` by colour and size**: `units` stays the single field of a line with one row. A line counted
  by size, or holding several colours (a "One size" line in two colours too), sends `cells` (colour id → size id → units); errors come
  back under `cells` and `cells.<colour id>.<size id>`, "You can move at most 4 units of Indigo M.", "Indigo M: Enter a whole number of
  units.". The cut's history entries carry `colour_id` and the label "Indigo M".
- **Every line names its colours on the Orders list and the client page**, also a line that counts as one size: "12 × Leather Tote
  (Camel 12)", and a line recorded before sizes "50 × Denim Jacket (White 50)" (its first colour, AC-6). The order page's chips read
  "Indigo M 20" (or "Camel 12").
- **Edit order reads its lines once**: the order and `line_products` come from the same loaded lines, and `SizeLists` now builds the
  categories' lists from its own cached lists (two queries fewer on every page that reads both), so Edit order stays within 10 queries.
  `FinishedStock::ofMany()` reads the shelf of several products in one query (the search and Edit order).
- **`OrderFormProductResource`, the `products` and `size_lists` props of the order forms, and the old line row are gone.**
- **Tests send the new shapes** through two helpers in `tests/Pest.php`: `orderUnits()` (a line's `units`) and `cellUnits()` /
  `whiteCells()` (a move); `colourFigures()` reads a line's rows by "Indigo M".
- **Wording the card doesn't give, written here:** the order page's table heads its first column **Colour and size** (was "Size") and is
  named "Denim Jacket: units by colour, size and stage"; its phone list is "Colours and sizes". "1 product found" for one result. The
  quantity grid's **Remove** shows its word on a laptop and is a 44 px icon on a phone.

### Delivery report (2026-11-03)
Gates on this machine, final tree: `composer test` green (Pint, PHPStan, 3,079 Pest tests: 3,003 green, 76 skipped); `npx vp test`
green (129 files, 1,513 tests); `npm run check`, `npm run types:check` and `npm run build` green; `composer test:pgsql` 3,079 of
3,079. Not run: the browser golden path (Playwright is outdated on this machine, see INV-3).

| AC | Evidence |
| --- | --- |
| AC-1 · Search products | `OrderProductSearchTest` (name, SKU, variant SKU, colour, barcode; drafts, archived and deleted left out; 6 of 14; empty and no match; one size; another workshop; guests; one stock query); `product-search.test.tsx` (box, hint, Searching…, results and tables, Out, On this order, 50 products, no match, Try again, Clear search, phone chips) |
| AC-2 · Quantities | `order-form.test.tsx` (Add to order focus, In stock / Out of stock under each field, Total 12 units, band and "22 units · 2 products", Remove, 0 and empty, one size, phone layout) |
| AC-3 · Saving | `OrderSizesWriteTest` (the card's order, no stock, material or sale touched, every refusal and its key, colours or sizes changed, the old shape refused); `OrderWriteTest` ("Add at least one product to this order.", 51 products, one size); `order-form.test.tsx` (messages under fields and focus, Couldn't save, spinner) |
| AC-4 · Edit order | `EditStartedOrderTest`, `OrderSizesWriteTest` (started cells "White M can't be less than…", started line kept, colour moved, a colour since taken off kept, under the lock); `OrderPagesTest` (`line_products`); `edit.test.tsx` (grid filled, At least 10, locked Remove) |
| AC-5 · Lines with colour | `orders-table.test.tsx` ("Black M 1 · Indigo S 1 · M 2"); `order-line-card.test.tsx`; `MoveSizesTest` (Indigo M 4 cut, its limit, a colour not on the line, a one-size line in two colours); `move-units-dialog.test.tsx` (cells per colour) |
| AC-6 · Colours on orders | `OrderLineColoursMigrationTest` (first colour, deleted product, no colour, counts kept, `down()`, keys); `ProductColourLockTest` (12 units / 1 unit refused, shipped and deleted orders allowed, shelf sentence first, other products ignored) |
| AC-7 · Phone and keyboard | `product-search.test.tsx` (chips, full-width Add), `order-form.test.tsx` (band, three to a row, 44 px Remove, fixed Save bar); the 320 px and keyboard checks are in the click-through |

