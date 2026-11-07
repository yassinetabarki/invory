---
id: INV-18
title: Sizes chosen per product, managed in Settings
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
created: 2026-11-06
tags:
  - story
  - variants
---

# INV-18 · Sizes chosen per product, managed in Settings

> [!abstract] Description
> As a **workshop owner**, I want **one list of sizes in Settings, and each product to pick the sizes it really comes in**, so that
> **the Denim Jacket stops carrying XS, XXL and XXXL it never makes, a sneaker made only in 38 to 44 shows 7 sizes instead of 11, and a
> new kind of garment is no longer stuck with "One size"**. The category stops deciding a product's sizes; it only **suggests** them
> when a product is added. Every screen that shows a product's sizes (stock, restocks, barcodes, labels, orders, Progress, Start batch)
> then shows the product's own, in the order set in Settings.
> New: **Settings › Sizes** with Add size and Edit size, a **Sizes** field on Add and Edit product with locked sizes, a **Suggests**
> column and **Suggested sizes** in Settings › Product categories. Changed: the product page's Sizes badge and Sizes & colours card.
> Gone: the line "Sizes:" and the hint "Sizes come with the category." on the product form, and the three category locks. The pages are
> drawn in [[INV-18-product-sizes-ui]].
> Left out on purpose: deleting or archiving a size, a code for each size, groups of sizes, sizes on the Products list or its filters,
> sizes of materials. Material use per colour and size is INV-16, built after this card: the question "Take XL off this product?" and the
> lock "has material quantities" (both drawn in the UI card) arrive with it, because quantities per size don't exist before it.

> [!info] How it works today
> A product's sizes come from its category and nothing in the app can change them: every Apparel product has XS · S · M · L · XL ·
> XXL · XXXL, every Footwear product 36 to 46, every other product "One size". The Denim Jacket (Indigo, Black, Light wash) shows
> "3 colours × 7 sizes = 21 variants", seven size columns in its stock table and seven barcode rows per colour, although the workshop
> only makes it in S to XL. The product form says "Sizes:" and "Sizes come with the category.". A product on an order, with stock or
> in production can't move to another size list ("Denim Jacket is on orders, so its category can't change to one with other sizes.
> Keep a category with sizes XS to XXXL."). Settings › Catalogue holds Units, Material categories, Product categories, Colours and
> Labels, and no sizes.

## Acceptance criteria

The workshop's 19 starting sizes, in order, numbered 10, 20 … 190: XS 10 · S 20 · M 30 · L 40 · XL 50 · XXL 60 · XXXL 70 · 36 80 · 37
90 · 38 100 · 39 110 · 40 120 · 41 130 · 42 140 · 43 150 · 44 160 · 45 170 · 46 180 · One size 190. The **Denim Jacket** (Apparel, Indigo,
Black and Light wash) has XS to XXXL today, with units in stock, on orders and with barcodes in S, M, L and XL; XS, XXL and XXXL hold
nothing. A **Leather Tote** (a category suggesting One size) has "One size".

- [ ] **AC-1 · The list in Settings.** Settings › Catalogue lists Units · Material categories · Product categories · Colours ·
  **Sizes** · Labels. **Sizes** shows the heading, "The sizes your products come in. Each product picks the ones it has. Rename any of
  them, or add your own.", the main button **Add size**, and one table with the caption "Sizes".
  - One row per size in list order, no paging: **Size** (a small outlined tag) · **Position** · **Used by** ("3 products", "1
    product", "Not used") · **Edit**, named "Edit XXL". A 20th size, XXS at position 15, sits between XS (10) and S (20). There is no
    Status column, no Archive and no Delete.
  - "One size" carries a neutral badge "No size" after its name (also after a rename).
  - Under the table: "A size is never removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One
    size" is for products that don't come in sizes: a product that has it has no other size."
  - On a phone each size is one 56 px row with its tag, "Used by 3 products" and a 44 px **Edit**; **Add size** is full width, 44 px.
  - Sizes with the same position are listed by their creation order, not by name (S, M, L stay S, M, L).

- [ ] **AC-2 · Add and rename a size.** **Add size** opens a window with **Name** (up to 12 characters) and **Position (optional)**
  ("Lower numbers are listed first. Leave empty to list it last."), **Cancel** and **Save size**; **Edit XXL** is the same window.
  - Saved: "Size created." / "Size updated."; the size is in its place at once. A new size is on no product until chosen on one. An
    empty position lists it last; 0 lists it first.
  - Refused, the window stays open, the message under the field, the focus on the first: "Enter the size's name." · "You already have
    a size named "XL"." (ignoring capitals) · "Keep the name to 12 characters or fewer." · "Enter a whole number from 0 to 32,767, or
    leave it empty."
  - Editing a size that products use shows under Name "A new name shows on the 3 products that use XXL, and in their variant SKUs.
    Barcodes don't change." ("… on the 1 product that uses XXL, and in its variant SKUs. …"); an unused size shows no such line.
    Editing "One size" adds "The size for products without sizes. A product that has it has no other size." under the title.
  - Renaming XXL to 2XL shows "2XL" at once on every product that has it, in its variant SKUs ("PRD-JKT-001-IND-XXL" becomes
    "PRD-JKT-001-IND-2XL") and in the barcode lists; no barcode number changes.
  - Every figure is as before: sizes change no cost, "Can produce now" or sale.

- [ ] **AC-3 · Every product keeps the sizes it has.** When this ships, a workshop gets the 19 sizes above, and each product keeps
  exactly the sizes its category gave it: the Denim Jacket XS to XXXL ("3 colours × 7 sizes = 21 variants"), a Footwear product 36 to
  46, the Leather Tote "One size".
  - Nothing moves: stock, restocks, barcodes (their numbers and names), order lines and their delivered and cancelled counts, batches in
    production, work tickets and variant SKUs read exactly as before, and so does every cost and every "Can produce now".
  - "Used by" counts the products that have each size (non-deleted, any status): XS 3 products when three Apparel products exist.
  - A new account's workshop starts with the same 19 sizes, numbered 10 to 190.
  - The three starting size lists no longer exist as lists a category points to; the lock "… so its category can't change to one with
    other sizes." is gone from every screen, and changing a product's category changes none of its sizes.

- [ ] **AC-4 · The category suggests sizes.** Settings › Product categories gains the column **Suggests**: Apparel "XS to XXXL",
  Footwear "36 to 46", every other category "One size". The line follows the product badge's rule without the word "Sizes": one size
  alone ("One size", "M"); two to six sizes listed ("S, M, L, XL"); seven or more in an unbroken run of the list, first to last ("XS
  to XXXL"); seven or more with gaps, the first three and a count ("XS, S, M and 4 more"). The Edit category window gains **Suggested sizes**, the same toggle buttons as the product form with nothing
  locked, and the hint "Add product pre-selects these sizes when you choose this category.".
  - Saving with none chosen: "Choose at least one size." under the field, nothing saved. One size can't be combined with others here
    either (pressing it turns the others off, and the reverse).
  - A category added in Settings starts by suggesting "One size". Renaming or archiving a category changes none of its suggestions.
  - Changing a category's suggestion changes no existing product; it only decides what Add product pre-selects.

- [ ] **AC-5 · The Sizes field on Add product.** In the Details card, after Category and Status and before Colours, **Sizes** (red
  star) shows one toggle button per size of the list, all visible, in Settings order, with the hint "Choosing a category pre-selects
  its sizes. Change them as you like." and a count under them.
  - A pressed size is tinted, ringed and has a check mark before its name (never colour alone); the group is one stop for the
    keyboard, arrow keys move along it and Space presses. Buttons are 40 px high on a laptop, 44 px on a phone, 19 sizes wrapping onto
    four rows at 375 px.
  - With no category: nothing pressed, "None chosen yet". Choosing Apparel presses XS to XXXL, "7 sizes chosen"; the owner presses XS,
    XXL and XXXL off and keeps S · M · L · XL, "4 sizes chosen" ("1 size chosen" for one). Add product → "Product created." and the page
    reads "Sizes S, M, L, XL" and "3 colours × 4 sizes = 12 variants.".
  - **One size** and other sizes exclude each other: pressing One size turns the others off, pressing another size turns One size off.
  - Choosing another category replaces the sizes only while she has not changed them herself. Once she has, they stay and a line under
    the buttons reads "Sizes kept: you changed them." with the text button **Use the sizes of Footwear**, which replaces them.
  - Refused on save, nothing saved, under the group, the focus on the first button: no size pressed → "Choose at least one size."; One
    size with others (a stale page) → "One size can't be combined with other sizes. Choose it alone, or choose other sizes.".

- [ ] **AC-6 · The Sizes field on Edit product, and the locks.** Edit product shows the same field with the hint "A new size adds a
  variant for every colour, with no stock and no barcode yet.", the product's sizes pressed.
  - A size the product **uses** shows a lock icon after its name, stays pressed, keeps its full-strength text and can't be pressed
    off, but stays reachable by keyboard. Its reason is read on focus and hover. Every lock sentence puts "it" before its first reason;
    the reasons come in this order, joined with commas and "and": "has units in stock", "is on orders", "has barcodes", "has units in
    production" (and, with INV-16, "has material quantities"). So "Can't be taken off: it has units in stock, is on orders and has
    barcodes." for the Denim Jacket's XL, and "Can't be taken off: it is on orders and has barcodes." for a size with no stock. Under the group, once: "Sizes with a lock are in use by this
    product's stock, orders, barcodes, production or material quantities, so they can't be taken off." Orders that were deleted don't
    count; a size that ever had a barcode on the product stays on it for good.
  - The Denim Jacket: S, M, L and XL are locked, XS, XXL and XXXL are free. Pressing XS, XXL and XXXL off and saving gives "Product
    updated." and the badge "Sizes S, M, L, XL"; the stock table, barcodes and variants of those three sizes disappear, and nothing
    that held a figure is lost, since none held any. Any size of the list can be pressed on,
    including 36 (the category no longer limits it).
  - A size added later: XXS (15) appears between XS and S; pressed on and saved, the Denim Jacket gains 3 variants (one per colour),
    each with no stock and no barcode, "3 colours × 5 sizes = 15 variants".
  - **One size** can't be pressed while a locked size is chosen; its reason names the sizes in use by the size text rule: "S, M, L and
    XL are in use, so One size can't be chosen." ("XL is in use, so …"; "Size 38 is in use, so …"; "Sizes 36, 37 and 38 are in use, so …").
    The other way round, when One size itself is locked (a product with only One size that has stock, orders or barcodes), every other
    size can't be pressed and reads "One size is in use, so other sizes can't be chosen.".
  - Refused on save, nothing saved: "Choose at least one size."; a size taken off that is in use (a stale page): "XL can't be taken
    off Denim Jacket: it has units in stock, is on orders and has barcodes." (one sentence per size, in list order, "it" before the
    first reason; a number size reads "Size 36 can't be taken off Denim Jacket: …"). Changing the category on Edit product never changes the sizes.
  - Two people saving at once can't take a size off that the other has just put stock or an order on.

- [ ] **AC-7 · The product page, and every screen follows the product's sizes.**
  - The badge between Category and Status reads "One size" (or "Size M", "Size 38" for a single size), "Sizes S, M, L, XL" or "Sizes
    38, 39, 40" for two to six sizes, "Sizes XS to XXXL" or "Sizes 38 to 44" for seven or more in an unbroken run of the list, and
    "Sizes XS, S, M and 4 more" for seven or more with gaps.
  - **Sizes & colours** reads "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode."; the stock
    table, the Barcodes tab and the phone cards list only the product's sizes in Settings order. **Manage sizes & colours** opens Edit
    product on the Sizes field. A product with one size that isn't One size reads "1 colour × 1 size = 1 variant" and keeps its size
    column. "Can produce now … any mix of sizes" and "Unit cost … same for every size" show when the product has more than one size.
  - Update stock, New restock, barcode generation and lists, labels, order lines (New order and Edit order), the order tracker, Start
    batch and Progress show only the product's own sizes, in Settings order: the Denim Jacket at S · M · L · XL offers 4 sizes in each,
    never XS, XXL or XXXL. Variant lists in text keep their rules ("Indigo S, M, L and XL", "Light wash sizes 36, 37 and 38"). A
    renamed size shows its new name in all of them.
  - A size taken off a product that still appears in an old record can't happen: a size on an order, a batch, stock or a barcode is
    locked, so those screens never lose a row.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 9 commits
> Tech-lead · 2026-11-06 · approved 2026-11-06 · delivered 2026-11-07
> Why complex: a migration that moves every size reference of every workshop, a new business rule (the lock per size, One size
> alone), a changed contract between the server and the product, order and settings screens, and about 25 places that read sizes.

### In short
Today a product finds its sizes through its category; after this card it keeps its own list of sizes, picked from the workshop's own
list. The work starts by sending every screen and every save through **one reader** of a product's sizes, while sizes still come from
the category, so nothing changes on screen. Then one migration gives each workshop its 19 sizes, gives every product exactly the sizes
it has today and moves nothing else: stock, orders, barcodes, restocks and batches keep pointing at the same sizes. Only then come the
new screens: Settings › Sizes, the Sizes field with its locks, the product page and the category suggestions. One commit is one step,
one step one concern, and the app works between any two. The design is recorded in
[ADR-0028](../adr/0028-sizes-chosen-per-product.md) (Proposed, it replaces ADR-0015).

### Steps

| Step         | What it does                                            | Proves           | Lane         | Who                | Size       |
| ------------ | ------------------------------------------------------- | ---------------- | ------------ | ------------------ | ---------- |
| **INV-18-a** | One reader of a product's sizes, for products and stock | AC-7             | CRITICAL     | Backend            | ~300 lines |
| **INV-18-b** | The same reader for orders and production               | AC-7             | CRITICAL     | Backend            | ~300 lines |
| **INV-18-c** | Sizes per workshop and per product: the migration       | AC-3             | CRITICAL     | Backend            | ~450 lines |
| **INV-18-d** | Add and rename a size, on the server                    | AC-1, AC-2       | CRITICAL     | Backend            | ~250 lines |
| **INV-18-e** | The Settings › Sizes page                               | AC-1, AC-2       | NON-CRITICAL | Frontend           | ~300 lines |
| **INV-18-f** | Saving a product's sizes, and the lock per size         | AC-5, AC-6       | CRITICAL     | Backend            | ~350 lines |
| **INV-18-g** | The product page shows the product's own sizes          | AC-7             | CRITICAL     | Backend + Frontend | ~350 lines |
| **INV-18-h** | The Sizes field on Add and Edit product                 | AC-5, AC-6, AC-7 | CRITICAL     | Frontend + Backend | ~400 lines |
| **INV-18-i** | Product categories suggest sizes                        | AC-4             | CRITICAL     | Backend + Frontend | ~300 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green; steps a, b, c, d, f
and i also run `composer test:pgsql`, and h adds the browser golden path (`composer test:browser`). A "Backend + Frontend" step is one
commit: the backend developer changes the Resources and `domain.ts`, then the frontend developer every screen and fixture that reads
them. INV-18-c is over the line budget on purpose: a migration, its models and factories can't be split without a red suite in between.

#### INV-18-a · One reader of a product's sizes, for products and stock
**Commit:** `refactor(products): read a product's sizes through ProductSizes (INV-18-a)`

- New `App\Services\Products\ProductSizes` returning a `ProductSizeSet` (the sizes in order, their ids, `oneSize`). In this step it still
  reads category → size list (so `oneSize` = the `one_size` list); request-scoped like `SizeLists`, `of(Product)`, `forProducts()` in
  batch, and `fresh(Product)` that skips the cache, for writers under the product's row lock.
- Every reader in products, stock, barcodes and restocks switches to it: `FinishedStock`, `UpdateFinishedStock` (+ request),
  `ProductBarcodes` (its `shared_with` join included), `GenerateBarcodes` (+ request), `AddNameToBarcodes`, `RenameClash`,
  `RecordRestock` (+ request), `RestockBook`, `UndoCheck`, `RestockBarcodeLookup`. Writers use `fresh()`.
- Test helpers in `tests/Pest.php` (`sizeId('M')`, `sizeIds([...])`) replace direct `SizeList` queries in these tests, so INV-18-c only
  changes the helpers' bodies.

**Tests**
- The existing suite, unchanged in what it asserts (no behaviour moves).
- 1 feature test of `ProductSizes`: order, `oneSize`, one read per request (two calls, one query), `fresh()` reads again.

> [!example]- Files (≈13)
> - `app/Services/Products/{ProductSizes,ProductSizeSet,FinishedStock,ProductBarcodes}.php`
> - `app/Actions/Products/UpdateFinishedStock.php`, `app/Actions/Barcodes/{GenerateBarcodes,AddNameToBarcodes}.php`, `app/Actions/Restocks/RecordRestock.php`
> - `app/Http/Requests/{Products/UpdateFinishedStockRequest,Barcodes/GenerateBarcodesRequest,Restocks/StoreRestockRequest}.php`
> - `app/Services/Barcodes/RenameClash.php`, `app/Services/Restocks/{RestockBook,UndoCheck,RestockBarcodeLookup}.php`
> - `tests/Pest.php`, `tests/Feature/Products/ProductSizesTest.php`, the size helpers of the tests these files touch

#### INV-18-b · The same reader for orders and production
**Commit:** `refactor(orders): orders and production read a product's sizes through ProductSizes (INV-18-b)`

- `OrderLineSizing` decides a new line's mode by `ProductSizes` (by size unless the product is One size) and a saved line's through
  `OrderLine::countsBySize()` (today: its list has several sizes), so INV-18-c only swaps that accessor for a column.
- Switch `SyncOrderLines`, `ChangeOrderUnits`, `OrderCellsRequest`, `StoreOrderRequest`, `OrderBook`, `OrderProductSearch`,
  `StartBatch` (+ request), `PutBatchInStock`, `BatchNames` (and so `TicketSheet`), `ProgressBook`. Writers use `fresh()`.
- Every sort of size rows (`OrderLine::sizes()`, `ProgressBook`, `RestockBook`) orders by position, then id.

**Tests**
- The existing suite, unchanged in what it asserts.
- 1 dataset: a line's mode (by size, one size, before sizes) from the line and its product.

> [!example]- Files (≈13)
> - `app/Services/Orders/{OrderLineSizing,OrderBook,OrderProductSearch}.php`, `app/Models/OrderLine.php`
> - `app/Actions/Orders/{SyncOrderLines,ChangeOrderUnits}.php`, `app/Http/Requests/Orders/{OrderCellsRequest,StoreOrderRequest}.php`
> - `app/Actions/Production/{StartBatch,PutBatchInStock}.php`, `app/Http/Requests/Production/StartBatchRequest.php`
> - `app/Services/Production/{BatchNames,ProgressBook}.php`
> - `tests/Feature/Orders/OrderLineSizingTest.php` (new)

#### INV-18-c · Sizes per workshop and per product: the migration
**Commit:** `feat(sizes): each workshop has its sizes and each product keeps its own (INV-18-c)`

- One migration, as [ADR-0028](../adr/0028-sizes-chosen-per-product.md) §4 lays out: `sizes` gains `workshop_id` and `is_one_size`
  (positions 10 … 190), the lowest workshop keeps the 19 rows and their ids, every other workshop gets its own and its six kinds of
  records move onto them; `product_sizes` from each product's category; `product_category_sizes` for Apparel and Footwear by code;
  `order_lines.by_size` in place of `size_list_id`; `size_lists` and `product_categories.size_list_id` go; PostgreSQL composite keys.
  It imports no app class. `down()` as ADR-0028 §4.6 says.
- `Size` uses `BelongsToWorkshop`, `ordered()` (position, id), `isOneSize`; `Product::sizes()`, `ProductCategory::suggestedSizes()`
  (the current workshop's rows). `ProductSizes` reads `product_sizes`; `OrderLine::countsBySize()` reads `by_size`.
- `CreateDefaultSizes` (19 sizes and the two suggestions) from `CreateWorkshop` and `WorkshopFactory`. `CreateProduct` without a `sizes`
  key, and `ProductFactory`, give the category's suggestion; a `withSizes([...])` factory state. `SyncOrderLines` sets `by_size`.
- The category lock goes: `ProductSizeLock::refusal()`, its call in `UpdateProductRequest` and `UpdateProduct::guardSizes()`.
- Bridge until INV-18-g: `ProductResource` still sends `size_list` (`code` `one_size` or `sizes`; `name` as today's list names, "XS to
  XXXL", "36 to 46", "One size") and the forms `category_sizes`, built from the new tables, so no screen changes in this step. `SizeList`, `SizeLists` and `SizeListResource` are deleted.
- Factories of stock, order rows, restock lines and batches put the size on the product when it isn't there (as they do for colours).
- Docs in this commit (tech-lead): ADR-0028 Accepted, its appendix applied to the costing skill, `docs/ARCHITECTURE.md` (the tables).
- The four older migration tests run this migration's `down()` first, as they already do for INV-14's.

**Tests**
- Migration test, both engines: two workshops; the Denim Jacket (XS to XXXL), a Footwear product (36 to 46) and the Leather Tote (One
  size) keep exactly their sizes; stock, order rows (by size and before sizes), barcodes and names, restock lines, batches and the
  ticket's variant SKU read the same in both workshops; positions 10 … 190 and the flag; Apparel and Footwear suggest their lists, Bag
  suggests One size; `down()` restores, and refuses after a size was added.
- A new workshop has the 19 sizes, numbered 10 to 190 (sign-up).
- A product on orders, in stock and in production changes category, and its sizes don't change (replaces the old lock tests).
- AC-7, 1 dataset: the Denim Jacket given S · M · L · XL offers exactly those four, in order, in Update stock, New restock, the barcodes,
  the order search and tracker, Start batch, Progress and its work ticket; a restock line on a size the product no longer has still
  shows on the restock detail.
- Arch test: `Size` belongs to a workshop; the three new tables are workshop tables.
- The golden costing test and every "Can produce now" stay as they are.

> [!example]- Files (≈16)
> - `app/Models/{Size,Product,ProductCategory,OrderLine}.php`, `app/Services/Products/{ProductSizes,ProductSizeLock}.php`
> - `app/Actions/Sizes/CreateDefaultSizes.php` (new), `app/Actions/Workshops/CreateWorkshop.php`, `app/Actions/Products/{CreateProduct,UpdateProduct}.php`, `app/Actions/Orders/SyncOrderLines.php`
> - `app/Http/Requests/Products/UpdateProductRequest.php`, `app/Http/Resources/ProductResource.php`, `app/Http/Controllers/ProductController.php`, `app/Actions/Reference/CreateProductCategory.php`
> - removed: `app/Models/SizeList.php`, `app/Services/Products/SizeLists.php`, `app/Http/Resources/SizeListResource.php`
> - `database/migrations/2026_11_08_100000_sizes_per_workshop_and_product.php`, `database/factories/{Workshop,Product,ProductCategory,Size,…}Factory.php`
> - `tests/Feature/Products/SizesPerWorkshopMigrationTest.php` (new), `tests/Arch/WorkshopScopeTest.php`, `tests/Pest.php`, the four older migration tests
> - `docs/adr/0028-sizes-chosen-per-product.md`, `docs/ARCHITECTURE.md`, `.claude/skills/invory-costing-domain/SKILL.md`

#### INV-18-d · Add and rename a size, on the server
**Commit:** `feat(sizes): add and rename the workshop's sizes (INV-18-d)`

- Routes `settings.sizes.index|store|update` (GET/POST `settings/sizes`, PUT `settings/sizes/{size}`), `SizePolicy` (any member, as
  colours), `SizeController`, `Store/UpdateSizeRequest` with the card's four sentences, `CreateSize` / `UpdateSize` (a name taken a moment
  ago comes back on `label`, as colours do). An empty position lists last (highest + 1), 0 first; ties by id.
- The page lists every size with `position`, `usage_count` (non-deleted products, any status) and `one_size`. Page stub for the
  frontend.

**Tests**
- Add XXS at 15 (between XS and S), add one with no position (last), rename XXL to 2XL: the toasts, the order, and the Denim Jacket's
  variant SKU "PRD-JKT-001-IND-2XL" with the same barcode number.
- 1 validation dataset: the four sentences, a name of another size in other capitals, the same name in another workshop accepted.
- Guest, another workshop's size (404) and the contract row via `assertInertia`.

> [!example]- Files (≈8)
> - `app/Http/Controllers/Settings/SizeController.php`, `app/Http/Requests/Sizes/{StoreSizeRequest,UpdateSizeRequest}.php`, `app/Actions/Sizes/{CreateSize,UpdateSize}.php`, `app/Policies/SizePolicy.php`
> - `routes/settings.php`, `resources/js/types/domain.ts`, `resources/js/pages/settings/sizes.tsx` (stub)
> - `tests/Feature/Settings/SizesTest.php`

#### INV-18-e · The Settings › Sizes page
**Commit:** `feat(sizes): Settings › Sizes page with Add size and Edit size (INV-18-e)`

- **Sizes** after Colours in the Settings menu; the page as the UI card draws it (table, "No size" badge, the line under the table,
  rows on a phone); the Add size / Edit XXL window with the hint, the rename line ("… on the 3 products that use XXL …" / "… on the 1
  product that uses XXL …", none when unused) and the One size line, using `lib/sizes.ts` for "size 36" in text.

**Tests**
- Page smoke: rows in order, "Not used", the badge, the line under the table, "Edit XXL" names.
- Dialog: save, an error under its field with focus on it, the three rename-line forms and the One size line.
- The Settings menu lists Sizes after Colours.

> [!example]- Files (≈6)
> - `resources/js/pages/settings/sizes.tsx`, `resources/js/components/settings/{sizes-table,size-form-dialog}.tsx`, `resources/js/layouts/settings/layout.tsx`
> - `resources/js/pages/settings/sizes.test.tsx`, `resources/js/components/settings/size-form-dialog.test.tsx`, `resources/js/layouts/settings/layout.test.tsx`

#### INV-18-f · Saving a product's sizes, and the lock per size
**Commit:** `feat(products): choose a product's sizes; a size in use stays (INV-18-f)`

- `Store/UpdateProductRequest`: `sizes` (ids of the workshop's sizes, distinct). In this step absent still means "the category's
  suggestion" on Add and "unchanged" on Edit, so today's form keeps saving; INV-18-h makes it required. Empty → "Choose at least one
  size."; One size with others → the card's sentence; both on `sizes`.
- `ProductSizeLock` rewritten per size: four grouped queries give each size its reasons (stock, orders of non-deleted orders, barcodes
  of the name in the product's colours, batches In production), the tooltip sentence, the refusal sentences, the One size sentence and
  the sentence for the other sizes when One size is locked, as worded under *Wording settled* below.
  The request puts each refusal on `sizes.removed.{sizeId}` in list order; `UpdateProduct` checks again under the row lock, before
  `SyncProductSizes` writes. Changing the category never touches the sizes.
- `RenameClash` and `AddNameToBarcodes` use the sizes the save leaves the product with.

**Tests**
- Add product with S · M · L · XL; Edit the Denim Jacket: XS, XXL, XXXL off (12 variants), XXS on (15).
- 1 validation dataset: none, One size with others, another workshop's size, a locked size taken off (one, two in list order, "Size 36
  can't …", each reason alone with "it" in front, and all four together); the Leather Tote with One size locked refuses S.
- 1 sentence dataset: "XL is in use, …", "Size 38 is in use, …", "Sizes 36, 37 and 38 are in use, …", "S, M, L and XL are in use, …",
  and "One size is in use, so other sizes can't be chosen.".
- 1 reasons dataset: a deleted order doesn't lock, a fully delivered one does; a finished batch doesn't, one In production does; a
  barcode under the product's name and colour does.
- Two saves at once: stock written after the request passed → `UpdateProduct` refuses; a size taken off before Update stock, Record
  restock, an order save, Start batch and Generate barcodes → each refuses (1 dataset).
- A rename and a size change in one save: the barcodes of the new name follow the sizes kept.

> [!example]- Files (≈8)
> - `app/Http/Requests/Products/{StoreProductRequest,UpdateProductRequest}.php`, `app/Actions/Products/{CreateProduct,UpdateProduct,SyncProductSizes}.php`
> - `app/Services/Products/ProductSizeLock.php`, `app/Services/Barcodes/RenameClash.php`, `app/Actions/Barcodes/AddNameToBarcodes.php`
> - `tests/Feature/Products/{ProductSizesWriteTest,ProductSizeLockTest}.php`

#### INV-18-g · The product page shows the product's own sizes
**Commit:** `feat(products): the product page and forms receive the product's own sizes (INV-18-g)`

- Pure `App\Domain\Sizes\SizeLine` writes the badge from the product's sizes and the workshop's list (unbroken run).
  `ProductResource` sends `sizes`, `one_size` and `sizes_line` in place of `size_list`; Add and Edit product receive the workshop's
  `sizes` and `category_sizes` as ids; Edit also `size_locks` and `one_size_lock` (contract below). The bridge of INV-18-c goes.
- Frontend: the badge shows `sizes_line`; Sizes & colours counts `product.sizes` ("1 colour × 1 size = 1 variant" keeps its column);
  "any mix of sizes" and "same for every size" when there is more than one; the `SizeList` type and fixtures go.

**Tests**
- `SizeLine` dataset: One size (also renamed), "Size M", "Size 38", "Sizes S, M, L, XL", "Sizes 38, 39, 40", "Sizes XS to XXXL",
  "Sizes 38 to 44", "Sizes XS, S, M and 4 more".
- Contract via `assertInertia`: the product's sizes; Edit's locks and One size sentence for the Denim Jacket; the query budget of the
  product page and Edit product.
- Vitest: the badge, "3 colours × 4 sizes = 12 variants", the one-size-that-isn't-One-size card.

> [!example]- Files (≈12)
> - `app/Domain/Sizes/SizeLine.php`, `app/Http/Resources/ProductResource.php`, `app/Http/Controllers/ProductController.php`
> - `resources/js/types/domain.ts`, `resources/js/components/products/{product-header,sizes-colours-card,product-form}.tsx`, `resources/js/pages/products/{show,create,edit}.tsx`, `resources/js/test/fixtures/products.ts`, `resources/js/test/fixtures.ts`
> - `tests/Unit/Domain/SizeLineTest.php`, `tests/Feature/Products/ProductContractTest.php`, the tests of the components above

#### INV-18-h · The Sizes field on Add and Edit product
**Commit:** `feat(products): the Sizes field on Add and Edit product (INV-18-h)`

- `SizesField`: one `ToggleGroup` of every size, roving focus, check mark and ring when pressed, 40 / 44 px, the count line, the hints
  of Add and Edit. Add: pre-select from `category_sizes`; "Sizes kept: you changed them." and **Use the sizes of …** once she has
  changed them. One size and the others exclude each other. Edit: locked sizes keep `aria-pressed`, get `aria-disabled` and the reason
  as their description (tooltip on hover), the line under the group; One size reads `one_size_lock`; when One size itself is locked,
  every other size is `aria-disabled` and reads `other_sizes_lock` ("One size is in use, so other sizes can't be chosen."). Errors under the group, focus on
  the first button. **Manage sizes & colours** opens Edit product on `#sizes`.
- Backend part: `sizes` becomes required on both requests; the tests' product payload sends it.

**Tests**
- Vitest: Add with no category, Apparel pre-selects 7, a hand change survives a category change and **Use the sizes of Footwear**
  replaces it; One size exclusion both ways; a locked size can't be pressed off and reads its reason; One size's lock; the Leather
  Tote's other sizes can't be pressed while its One size is locked; arrow keys and
  Space; both errors with focus; the payload.
- Browser golden path: Settings › Sizes adds XXS at 15, Edit the Denim Jacket takes XS, XXL, XXXL off and puts XXS on, the page reads
  "Sizes XXS, S, M, L, XL" and "3 colours × 5 sizes = 15 variants".

> [!example]- Files (≈8)
> - `resources/js/components/products/{sizes-field,product-form}.tsx`, `resources/js/components/products/sizes-colours-card.tsx`
> - `app/Http/Requests/Products/StoreProductRequest.php`, `tests/Pest.php`
> - `resources/js/components/products/sizes-field.test.tsx`, `resources/js/components/products/product-form.test.tsx`, `tests/Browser/SizesGoldenPathTest.php`

#### INV-18-i · Product categories suggest sizes
**Commit:** `feat(categories): a category suggests the sizes Add product pre-selects (INV-18-i)`

- `UpdateProductCategoryRequest` takes `suggested_sizes` (edit only; none → "Choose at least one size."; One size with others → the
  product form's sentence); `UpdateProductCategory` replaces the current workshop's rows (One size alone is stored as no row). The rows of
  Settings › Product categories gain `suggested_size_ids` and `suggests`: `SizeLine`'s badge rule without "Sizes" / "Size" (one size:
  its name; 2 to 6: listed; 7 or more in an unbroken run: first to last; 7 or more with gaps: the first three "and N more").
- Frontend: the **Suggests** column and, in the Edit category window, **Suggested sizes** with `SizesField` (nothing locked) and the
  hint.

**Tests**
- Edit Footwear to 38 … 44: saved for this workshop, another workshop's Footwear unchanged; Add product pre-selects 38 … 44; no product's
  sizes change. 1 validation dataset (none, One size with others, another workshop's size).
- The Suggests line: "XS to XXXL", "36 to 46", "One size", "S, M, L, XL", "XS, S, M and 4 more"; a new category reads "One size".
- Vitest: the column, the field in Edit (not in Add), the error.

> [!example]- Files (≈8)
> - `app/Http/Requests/Reference/UpdateProductCategoryRequest.php`, `app/Actions/Reference/UpdateProductCategory.php`, `app/Http/Controllers/Settings/ProductCategoryController.php`, `app/Domain/Sizes/SizeLine.php`
> - `resources/js/types/domain.ts`, `resources/js/pages/settings/product-categories.tsx`, `resources/js/components/settings/{reference-form-dialog,reference-table}.tsx`
> - `tests/Feature/Reference/CategorySuggestedSizesTest.php`, `resources/js/pages/settings/categories.test.tsx`

### Contract (frozen before dispatch)
```ts
// New
export type SizeOption = Size & { one_size: boolean };                  // the workshop's list: position, then id
export type SizeRow = SizeOption & { position: number; usage_count: number };   // usage_count: non-deleted products, any status
export type SizesSettingsProps = { sizes: SizeRow[] };
// Changed
Product            : - size_list; + sizes: Size[]          // the product's own, list order, at least one
                     + one_size: boolean                   // its only size is the workshop's flagged One size
                     + sizes_line: string                  // "Sizes S, M, L, XL", "One size", "Size M", "Sizes XS to XXXL", "Sizes XS, S, M and 4 more"
ProductCreateProps : category_sizes: Record<ProductCategory, number[]>   // suggested size ids, this workshop, every category
                     + sizes: SizeOption[]                               // every size of the workshop
ProductEditProps   += { size_locks: Record<number, string>;             // size id → "Can't be taken off: it has units in stock, …"
                        one_size_lock: string | null;                    // "S, M, L and XL are in use, so One size can't be chosen."
                        other_sizes_lock: string | null }                // "One size is in use, so other sizes can't be chosen."; null unless One size is locked
ProductCategoriesSettingsProps.categories: (ReferenceRow & { suggested_size_ids: number[]; suggests: string })[]
ProductCategoriesSettingsProps.sizes: SizeOption[]       // added at INV-18-i: the toggles of Suggested sizes need the workshop's list
// Removed: SizeList
```
Payloads: product `sizes: number[]`; size `{ label, position }`; category update `suggested_sizes: number[]`. Errors: `sizes`,
`sizes.removed.{sizeId}`, `label`, `position`, `suggested_sizes`. Routes: `settings.sizes.index|store|update`. The order, stock,
restock, Start batch and Progress props keep their shape (`sizes: Size[]`, `one_size: boolean`); only where they come from changes.

### Wording settled (human, 2026-11-06)
- Every lock sentence puts "it" before its first reason: "Can't be taken off: it is on orders and has barcodes.", "XL can't be taken
  off Denim Jacket: it has barcodes.".
- The One size sentence names the sizes in use by the size text rule: "XL is in use, so One size can't be chosen.", "Size 38 is in
  use, …", "Sizes 36, 37 and 38 are in use, …", "S, M, L and XL are in use, …".
- When One size is locked, every other size is `aria-disabled` and reads "One size is in use, so other sizes can't be chosen.".
- The Suggests column follows the badge's rule without "Sizes": 2 to 6 listed, from 7 first to last in an unbroken run, otherwise
  the first three "and N more".
- The barcode reason stays as built (the product's name, in its current colours). ADR-0028 superseding ADR-0015 is accepted as the way
  to record it; the ADR itself becomes Accepted in INV-18-c.

### Decisions
- **ADR-0028 replaces ADR-0015.** ADR-0015's file left the vault on 2026-10-31, so the amendment is a new ADR that supersedes it.
- **Sizes belong to a workshop; categories stay shared** (S-45 is on hold), so a category's suggestion is stored per workshop, and no
  row means One size: a category added in Settings needs no write in other workshops.
- **One size is the flag `is_one_size`**, never the name; ties in position break by id.
- **The lowest workshop keeps the 19 existing ids**; other workshops' records move onto their own copies. Only a demo or a second
  account is moved; the real workshop's rows aren't touched.
- **An order line stores `by_size`** instead of a list id; a line of a single-size product that isn't One size counts by size (it keeps
  its size column, as the card wants for the product page).
- **The barcode reason** counts barcodes of the product's name in its current colours: what its Barcodes tab shows. "Is on orders"
  counts delivered and cancelled orders until they are deleted, as S-58's lock did; "has units in production" counts batches In
  production only.
- **The restock detail** shows the product's sizes plus any size its lines hold, so a size taken off after a restock (stock set to 0)
  never hides a line.
- **The history of cuts** (`stock_movements.sizes`) keeps its stored labels; it isn't rewritten.

### Order
Wave 1 (backend, one developer): INV-18-a → INV-18-b → INV-18-c. Wave 2: INV-18-d ∥ INV-18-f (two backend developers, disjoint
files), then INV-18-e (frontend) ∥ INV-18-g (backend, then its frontend part). Wave 3: INV-18-h, then INV-18-i (it reuses
`SizesField`). Merge order is the step order.

### Risks
- **The migration moves references in six tables** → one mapping per workshop, the lowest keeping its ids; a migration test with two
  workshops on both engines; before it runs on the dev database, the tech-lead runs it once on a copy (a scratch PostgreSQL database
  restored from `laravel`, never `laravel` itself) and compares the row counts and every product's sizes.
- **PostgreSQL refuses the move of `barcode_names`** through its composite key → dropped for the move and added back in the same
  migration.
- **SQLite rebuilds `sizes`**, which six tables reference, when `size_list_id` goes → foreign keys and indexes dropped first, as the
  F-32 migrations do; the parity suite runs after INV-18-c.
- **A writer reads sizes cached before it took the lock** → writers use `ProductSizes::fresh()` under the lock; the "two saves at
  once" dataset of INV-18-f covers each.
- **Sizes sorted by position alone** read in creation-unstable order once positions tie → `Size::ordered()` and every subselect sort by
  position, then id; the INV-18-c migration test ties two positions.
- **Thirty-four test files read size lists** → INV-18-a moves them to helpers first, so INV-18-c changes the helpers, not the tests.
- **Edit product gains four lock queries** → grouped per product, checked against the page budget in INV-18-g.
- **INV-16 is planned on size lists** → its Technical work is replanned after this card (see the hand-off).

### Progress log (build)
- 2026-11-06 · INV-18-a approved (all PHP gates green; `types:check` red on HEAD from a local `node_modules` mix, same 21 errors before and after). Docker down: parity and the scratch-copy migration wait.
- 2026-11-06 · INV-18-b approved (PHP gates green, `types:check` same 21 HEAD errors, Vitest/check/build green). Docker back: parity on a + b green (3000 passed). Next: INV-18-c.
- 2026-11-07 · INV-18-c approved: all gates green (same 21 `types:check` HEAD errors), parity green (2993). ADR-0028 Accepted, costing skill and ARCHITECTURE.md amended. Scratch copy of `laravel`: down → up round trip identical. Found `laravel` already migrated (2026-11-06 23:00:19, during the step's development); result checked correct. Next: INV-18-d ∥ INV-18-f.
- 2026-11-07 · INV-18-d (1 fix round: empty position on Edit lists last; `SizeClash`) and INV-18-f approved: all gates green, parity green (3042). INV-18-e delivered, gates running. Next: INV-18-g (backend, then frontend).
- 2026-11-07 · INV-18-e approved (fast lane; all gates green). INV-18-g backend dispatch cut off by the usage limit before any edit; re-dispatched. Remaining: g (backend, then frontend), h, i.
- 2026-11-07 · INV-18-g backend approved after 1 fix round (workshop list and suggestions moved from `ProductResource` into `ProductSizes`; Edit page budget + 4 lock queries). Next: INV-18-g frontend, then gates + parity, then h, i.
- 2026-11-07 · INV-18-g approved (backend 1 fix round, frontend 1 fix round: Update stock reads `one_size`): all gates green, parity green (3057). Next: INV-18-h (backend: `sizes` required; then frontend: the Sizes field + browser golden path), then INV-18-i.
- 2026-11-07 · INV-18-h backend (`sizes` required) and frontend (Sizes field; 1 fix round: Manage sizes & colours → `#sizes`) delivered; gates and browser tests running. Next: INV-18-i.
- 2026-11-07 · INV-18-h approved: all gates green; browser tests 4/5 (the one red, `OrderGoldenPathTest`, has been stale since INV-13: it looks for an "Add product" button the New order page no longer has). Next: INV-18-i.
- 2026-11-07 · INV-18-i backend delivered (contract adds `ProductCategoriesSettingsProps.sizes`). Next: INV-18-i frontend, then the final gates, parity and browser run.
- 2026-11-07 · INV-18-i approved. Final run on the whole tree: composer test 2991 passed (77 PG-only skips), Vitest 1475, check and build green, tsc = the 21 HEAD errors, parity 3068 passed, browser 4/5 (stale OrderGoldenPathTest). Delivered.
- 2026-11-07 · UX review fixes delivered (Edit category dialog scrolls with title and buttons fixed; the size error is announced on the first size button; check slot reserved; `anchorId` prop). Gates running.
- 2026-11-07 · UX review fixes approved: composer test 2991 passed (77 PG-only skips), Vitest 1477 (green on re-run; the first run, on an overloaded machine, had 5 timing failures in `product-form.test.tsx` that pass alone and on re-run), check and build green, tsc = the 21 HEAD errors. Not checked in the running app (not serving on :8000; no known login).
