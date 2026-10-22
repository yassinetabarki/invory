---
id: S-58
title: Every product gets the sizes of its category
type: feature
phase: 5
priority: Must
size: S
status: Planned
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-58 · Every product gets the sizes of its category

> [!abstract] Description
> As a **workshop owner**, I want **each product to carry the sizes of its kind, clothing in S, M, L and XL and shoes in 36 to 46**, so
> that **orders, production and finished stock can be counted per size**.
> The sizes come from a short list set up with Invory for each product category. You don't pick them, and there is no screen to change
> these lists for now. Sizes don't change the composition, the unit cost, "Can produce" or the bottleneck: every size of a product uses
> the same composition. The products list, sales and "Record sale" don't change: a sale has no size. Using sizes on orders is S-60 and
> S-61.

> [!info] How it works today
> Nothing in Invory knows about sizes. The Denim Jacket's page shows "PRD-JKT-001", the category "Apparel" and the status "Active" under
> its name, then "Can produce now 112 units from current stock" and "Unit cost TND 35.754", without saying in which sizes the jacket is
> made. On "Add product" and "Edit Denim Jacket" the "Category" field (Apparel, Footwear, Bag, Accessory, Jewelry, Other, plus any you add
> in Settings) only sorts and filters products. The demo Canvas Low Sneaker uses the material "Rubber cupsole 42": the size is written
> in the material's name because there is nowhere else to put it. On the Orders list, "50 × Denim Jacket" can't say how many are S or
> XL.

## Acceptance criteria
- [ ] **AC-1 · Sizes come with the category.** Every workshop, including one created at sign-up, has these sizes:
  - Apparel: S, M, L, XL, in that order;
  - Footwear: 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, in that order;
  - every other category (Bag, Accessory, Jewelry, Other, and any category you add in Settings): "One size".

  A product has every size of its category. There is no screen to change these lists. Renaming Apparel to "Clothing" in Settings, or
  archiving it, keeps its sizes S, M, L, XL.
- [ ] **AC-2 · The product page names the sizes.** Under its name, next to "Apparel", the Denim Jacket's page shows "Sizes S, M, L, XL",
  and its "Can produce now" card reads "112 units" and "from current stock, any mix of sizes". The Canvas Low Sneaker shows "Sizes 36 to
  46". The Leather Tote shows "One size", and its card keeps "from current stock". "Can't produce" and "No composition" read as today.
  The unit cost (TND 35.754), "Can produce now" (112) and the bottleneck (Metal shank button 17mm) are unchanged.
- [ ] **AC-3 · The form shows the sizes of the chosen category.** On "Add product" and "Edit Denim Jacket", under
  "Category", the line "Sizes: S, M, L, XL" appears when Apparel is chosen, "Sizes: 36 to 46" for Footwear and "One size" for any other
  category, with the hint "Sizes come with the category.". It changes as soon as you choose another category. On "Add product" no line
  shows until a category is chosen. "Save product" works as today ("Product created." or "Product updated.").
- [ ] **AC-4 · The sizes are locked once the product is on an order.** When the Denim Jacket is on Boutique Lina's order, changing its
  category from Apparel to Footwear (or to Bag) and clicking "Save product" shows, under "Category", "Denim Jacket is on orders, so its
  category can't change to one with other sizes. Keep a category with sizes S, M, L, XL." and nothing is saved. A Footwear product's
  message ends "Keep a category with sizes 36 to 46.". When the Leather Tote is on an order, changing it from Bag to Apparel shows
  "Leather Tote is on orders, so its category can't change to one with other sizes. Keep a "One size" category." An order counts until
  it is deleted, also once it is fully shipped. Changing to a category with the same sizes (Bag to Accessory, or Bag to any category
  you add in Settings, all "One size") is always allowed, and a product on no order can change to any category.
- [ ] **AC-5 · Existing products follow their category.** After this change, every product you already have shows the sizes of its
  category: the Denim Jacket, the Denim Jeans 5-pocket, the Classic White T-shirt and the Canvas Chore Jacket "Sizes S, M, L, XL"; the
  Canvas Low Sneaker "Sizes 36 to 46"; the Leather Tote and the Leather Belt "One size". Nothing else about them changes: costs, "Can
  produce", sales and orders read as before.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 5 commits
> Tech-lead · 2026-10-21 · approved 2026-10-21 · delivered 2026-10-22
> Why complex: a database change (two new tables and a new link on every product category, filled in for the data you already
> have), a new business rule (the AC-4 lock) and new data for the product page and the product form (a new contract).

### In short
Invory gets two small fixed tables: the three size lists ("S, M, L, XL", "36 to 46" and "One size") and their sizes, in order. Each
product category points at one list. When this ships, Apparel gets the first, Footwear the second and every other category, including
any you added, "One size"; a category added later in Settings gets "One size" too. The link follows the category's hidden code, not its
name, so renaming or archiving a category keeps its sizes. A product never stores sizes of its own: it has the sizes of its category,
so every existing product has them the moment the change is installed, and no cost or "Can produce" figure moves. The server then
sends each product's sizes to its page and every category's sizes to the product form. Last, saving a product refuses a category with
another size list while the product is on an order that isn't deleted. The stored sizes are what S-60, S-61 and S-56 will count by,
which is why each size is a row of its own ([ADR-0015](../adr/0015-sizes-come-from-the-product-category.md)).

### Steps

| Step       | What it does                                    | Proves                 | Lane         | Who                | Size       |
| ---------- | ----------------------------------------------- | ---------------------- | ------------ | ------------------ | ---------- |
| **S-58-a** | Size lists stored, and every category gets one  | AC-1, AC-5             | CRITICAL     | Backend            | ~230 lines |
| **S-58-b** | The sizes reach the product page and the form   | AC-1, AC-2, AC-3, AC-5 | CRITICAL     | Backend (+ FE fix) | ~150 lines |
| **S-58-c** | Sizes locked once the product is on an order    | AC-4                   | CRITICAL     | Backend            | ~120 lines |
| **S-58-d** | The product page names the sizes                | AC-2                   | NON-CRITICAL | Frontend           | ~80 lines  |
| **S-58-e** | The form shows the sizes of the chosen category | AC-3, AC-4             | CRITICAL     | Frontend           | ~100 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-58-a,
S-58-b and S-58-c also run `composer test:pgsql` (new tables, a migration that rebuilds `product_categories`, new queries); after
S-58-d and S-58-e the combined tree runs `composer test:browser` (the golden path opens the product page and the product form).

#### S-58-a · Size lists stored, and every category gets one
**Commit:** `feat(products): size lists, and every product category gets one (S-58-a)`

- One migration creates `size_lists` (`code` string(32) unique and immutable, `name` string(64)) and `sizes` (`size_list_id`
  `restrictOnDelete`, `label` string(16), `position`; unique per list on label and on position), and inserts the frozen snapshot of
  ADR-0015 §1 by code (no app class imported): `s_to_xl` "S, M, L, XL", `eu_36_to_46` "36 to 46" (36 … 46), `one_size` "One size".
- The same migration adds `product_categories.size_list_id`: nullable first, filled by **code** (`apparel` → `s_to_xl`, `footwear` →
  `eu_36_to_46`, every other code → `one_size`), then NOT NULL, `restrictOnDelete`, indexed. SQLite rebuilds the table on that change
  and forgets the `WHERE` of the partial index `product_categories_active_name_unique`, so the migration drops it first and creates it
  again at the end, in `up()` and in `down()` (as the S-21 migration does with the SKU index of `materials`).
- Models `SizeList` (`sizes()` in position order) and `Size`, and `ProductCategory::sizeList()`. `CreateProductCategory` gives a new
  category `one_size`; rename, archive and restore never touch the link. The category factory defaults to `one_size`.
- The tech-lead writes, in this commit: `docs/ARCHITECTURE.md` §4 (the two tables and the new column) and the
  business rules file (glossary "Size list" and "Size"; a short "Sizes" section: the three lists, chosen by category code, a product has
  its category's sizes, sizes change no figure, the AC-4 lock). The human approved this business-rules update on 2026-10-21.

**Tests**
- AC-1, one dataset over the six default categories: the list's labels in order and its name (Apparel S, M, L, XL · Footwear 36 … 46 ·
  Bag, Accessory, Jewelry, Other "One size").
- AC-1, one test: a category created through Settings ("Kids") gets "One size".
- AC-1, one dataset: Apparel renamed "Clothing" · Apparel archived · archived then restored → still S, M, L, XL.
- AC-5, one migration test (roll back, put rows in the old shape, run forward; the S-21 pattern): the six defaults, a category added
  before the change ("Kids") and an archived Apparel renamed "Clothing" get the right lists; every product keeps its `category_id`; a
  round trip (down, up) gives the same rows; afterwards the partial index still refuses a second active "bag", whatever its capitals.
- The workshop arch test allow-lists `SizeList` and `Size` (installation-wide reference data, ADR-0015).

> [!example]- Files (10, plus the older migration tests)
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead; approved by the human 2026-10-21)
> - `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Models/{SizeList,Size}.php` (new)
> - `app/Models/ProductCategory.php`
> - `app/Actions/Reference/CreateProductCategory.php`
> - `database/migrations/2026_10_21_100000_create_size_lists_and_sizes_tables.php` (new)
> - `database/factories/ProductCategoryFactory.php`
> - `tests/Arch/WorkshopScopeTest.php`
> - `tests/Feature/Products/SizeListsTest.php` (new, AC-1)
> - `tests/Feature/Products/SizeListMigrationTest.php` (new, AC-5)
> - `tests/Feature/Reference/{ReferenceMigrationTest,ReferenceNameIndexMigrationTest}.php`, `tests/Feature/Products/ProductMigrationsTest.php`,
>   `tests/Feature/Workshops/OwnedDataMigrationTest.php`, `tests/Feature/Orders/OrderSchemaTest.php`, `tests/Feature/Stock/CutSchemaTest.php`
>   (where they roll back older migrations, the new one goes back first; the developer greps for the full list)

#### S-58-b · The sizes reach the product page and the form
**Commit:** `feat(products): send each product's and each category's sizes to the product screens (S-58-b)`

- `ProductResource` gains `size_list` (a new `SizeListResource`: `code`, `name`, `sizes` as `{id, label}` in order), on the product
  page and on Edit product.
- Add product and Edit product receive `category_sizes`: every category's code (archived ones included, since a product can keep an
  archived category) → its size list. Always sent as a JSON object, also if a code looks like a number.
- The three lists are read once per request by one small service (the `ReferenceData` memo pattern), so a page adds at most three
  queries.
- `resources/js/types/domain.ts` as in the contract below, then a short frontend pass in the same commit on the typed test fixtures
  (`Product` literals get `size_list`, the Add/Edit page props get `category_sizes`), so `npm run types:check` stays green. Nothing on
  screen changes yet.

**Tests**
- AC-2 and AC-5, one dataset over the seven demo products on the product page: Denim Jacket, Denim Jeans 5-pocket, Classic White
  T-shirt and Canvas Chore Jacket → "S, M, L, XL" with 4 sizes in order; Canvas Low Sneaker → "36 to 46" with 11; Leather Tote and
  Leather Belt → "One size" with 1. The Denim Jacket row also asserts unit cost "35.7540", `max_producible` 112 and the bottleneck Metal
  shank button 17mm.
- Contract: the `size_list` shape joins the existing product contract test (one row).
- AC-3, one test on Add product and Edit Denim Jacket: `category_sizes` maps apparel → "S, M, L, XL", footwear → 11 sizes, bag → "One
  size", and a category added in Settings with the code "2027" → "One size" (the object stays an object).
- AC-1, one test: an account that has just signed up opens Add product and gets the same `category_sizes`.
- The query-budget test's product show, create and edit rows stay ≤ 10 with 3 and 30 products (the test changes only if it must).

> [!example]- Files (7, plus the frontend fixture pass)
> - `app/Http/Resources/SizeListResource.php` (new)
> - `app/Http/Resources/ProductResource.php`
> - `app/Services/Products/SizeLists.php` (new: the lists, once per request)
> - `app/Http/Controllers/ProductController.php` (`show`, `create`, `edit`)
> - `resources/js/types/domain.ts`
> - `tests/Feature/Products/ProductSizesTest.php` (new: AC-1 sign-up, AC-2, AC-3, AC-5)
> - `tests/Feature/Products/ProductContractTest.php`
> - Frontend pass, only where `npm run types:check` needs it: `resources/js/test/fixtures/{products,dashboard}.ts`,
>   `resources/js/pages/products/{create,edit,show}.test.tsx`, `resources/js/components/orders/order-lines-editor.test.tsx` and the
>   product component tests that build a `Product`

#### S-58-c · Sizes locked once the product is on an order
**Commit:** `feat(products): keep a product's sizes while it is on an order (S-58-c)`

- One check, `ProductSizeLock::refusal()`: no refusal when the new category has the same size list, or when the product is on no
  order that isn't deleted (a deleted order's lines don't count; a fully shipped order's do). Otherwise it returns the AC-4 message:
  "<product> is on orders, so its category can't change to one with other sizes." followed by "Keep a category with sizes <list
  name>." when the current list has several sizes ("S, M, L, XL", "36 to 46"), or by "Keep a "One size" category." when it has one.
- `UpdateProductRequest` runs it after its rules (the error sits on `category`, next to any other field errors). `UpdateProduct` locks
  the product row and runs it again inside its transaction, with the same error on `category`, so nothing is saved. Add product has no
  lock.

**Tests**
- AC-4, one dataset through the update route for the Denim Jacket (Apparel): on an open order → Footwear refused · → Bag refused · on a
  fully shipped order → Footwear refused · on a deleted order only → Footwear allowed · on no order → Footwear allowed · on an order,
  Apparel kept and the name changed → allowed. Each refusal asserts the exact message under `category` and that nothing changed (name,
  category, composition).
- AC-4, one test: the Canvas Low Sneaker (Footwear) on an order → Apparel refused, the message ending "Keep a category with sizes 36
  to 46.", nothing saved.
- AC-4, one test: the Leather Tote (Bag) on an order → Apparel refused with "Leather Tote is on orders, so its category can't change to
  one with other sizes. Keep a "One size" category.", nothing saved.
- AC-4, one dataset: the Leather Tote (Bag) on an order moves to Accessory · to a category added in Settings (all "One size").
- One test calling `UpdateProduct` directly for a product on an order: the same error, nothing saved (proves the second check).

> [!example]- Files (4)
> - `app/Services/Products/ProductSizeLock.php` (new)
> - `app/Http/Requests/Products/UpdateProductRequest.php`
> - `app/Actions/Products/UpdateProduct.php`
> - `tests/Feature/Products/ProductSizeLockTest.php` (new)

#### S-58-d · The product page names the sizes
**Commit:** `feat(products): sizes on the product page (S-58-d)`

- Under the product's name, next to the category: "Sizes S, M, L, XL", "Sizes 36 to 46" or "One size".
- The "Can produce now" card reads "from current stock, any mix of sizes" when the product has more than one size, and "from current
  stock" for one size. "Can't produce" and "No composition" read as today.

**Tests**
- AC-2, one `it.each` on the header: Denim Jacket "Sizes S, M, L, XL" next to "Apparel" · Canvas Low Sneaker "Sizes 36 to 46" ·
  Leather Tote "One size".
- AC-2, one `it.each` on the card: Denim Jacket "112 units" and "from current stock, any mix of sizes" · Leather Tote "from current
  stock" without "any mix of sizes" · a product that can't produce and one without a composition read as today.

> [!example]- Files (4)
> - `resources/js/components/products/product-header.tsx`
> - `resources/js/components/products/product-header.test.tsx`
> - `resources/js/components/products/product-hero.tsx`
> - `resources/js/components/products/product-hero.test.tsx`

#### S-58-e · The form shows the sizes of the chosen category
**Commit:** `feat(products): the product form shows the sizes of the chosen category (S-58-e)`

- `ProductForm` takes the category sizes from Add product and Edit product. Under "Category": nothing while no category is chosen;
  then "Sizes: S, M, L, XL", "Sizes: 36 to 46" or "One size", with the hint "Sizes come with the category.", both linked to the field
  for screen readers. The line follows the chosen category at once. "Save product" works as today.

**Tests**
- AC-3, one test on Add product: no sizes line and no hint before a category is chosen; Apparel → "Sizes: S, M, L, XL" and the hint;
  then Footwear → "Sizes: 36 to 46"; then Bag → "One size".
- AC-3, one test on Edit Denim Jacket: "Sizes: S, M, L, XL" shows at once.
- AC-4, one test: a server error on `category` shows the card's message under "Category", with the sizes line still there.

> [!example]- Files (6)
> - `resources/js/components/products/product-form.tsx`
> - `resources/js/components/products/product-form.test.tsx`
> - `resources/js/pages/products/create.tsx`
> - `resources/js/pages/products/create.test.tsx`
> - `resources/js/pages/products/edit.tsx`
> - `resources/js/pages/products/edit.test.tsx`

### Contract (frozen before dispatch)
```ts
/** One size of a product (S-58). Later stories (S-60, S-61, S-56) refer to a size by its id. */
export type Size = { id: number; label: string }; // "S", "36", "One size"

/** The fixed sizes of a product category (S-58, ADR-0015). */
export type SizeList = {
    code: string; // immutable: 's_to_xl', 'eu_36_to_46' or 'one_size' today
    name: string; // what the screens show: "S, M, L, XL", "36 to 46" or "One size"
    sizes: Size[]; // in order, at least one
};

// Product gains:            size_list: SizeList;  (the sizes of the product's category)
// ProductCreateProps gains: category_sizes: Record<ProductCategory, SizeList>;  (every category, archived ones included;
//                           ProductEditProps inherits it)
```
PHP: `SizeList` (`code`, `name`, `sizes()`), `Size` (`size_list_id`, `label`, `position`), `ProductCategory::sizeList()`,
`SizeListResource`, `App\Services\Products\SizeLists` (`of(ProductCategory $category): SizeList`, `byCategoryCode(): array<string, SizeList>`),
`App\Services\Products\ProductSizeLock::refusal(Product $product, ProductCategory $to): ?string`. The screens only add the card's
words: "Sizes " + `name` on the page and "Sizes: " + `name` in the form when there is more than one size, `name` alone for one size.

### Decisions
- **Size lists are their own installation-wide tables, filled by the migration** (ADR-0015 §1). Product categories are still shared
  by every workshop (S-45 is on hold), and no screen edits the lists, so they carry no `workshop_id`. When S-45 gives each workshop its
  own categories, each copy keeps its list ([F-65](../TECH-DEBT.md)).
- **The category points at its list by a stored link, set by code.** Renaming or archiving can't touch it (AC-1), and a category added
  before this change, or later in Settings, is "One size".
- **A product stores no sizes.** It has its category's list, so existing products have their sizes at once (AC-5) and nothing can
  drift.
- **"The same sizes" means the same list.** Bag → Accessory stays on `one_size`, so it is always allowed (AC-4).
- **The lock is checked twice:** in the update request, so its error shows with the other errors, and in `UpdateProduct` under the
  product row lock. Saving an order doesn't lock the product yet, so an order saved in the same instant can slip past; harmless until
  order lines carry sizes, and S-60 closes it ([F-64](../TECH-DEBT.md)).
- **The server names each list** ("S, M, L, XL", "36 to 46", "One size"), and the AC-4 message uses the same name (a one-size list gets
  its own ending, "Keep a "One size" category."). The screens never
  work a range out of the labels.
- **`category_sizes` is a prop of Add product and Edit product, not of the shared reference data.** The shared data loads on every
  first visit, where the query budget is tight (dashboard, materials), and Settings shows no sizes.
- **"One size" is a real size row** (ADR-0015 §6), so S-60 can record a line from before sizes against it.
- Nothing in the costing maths, the products list, sales or the dashboard changes.

### Order
Wave 1 (backend): S-58-a. Wave 2, after S-58-a is approved: S-58-b (backend, then the frontend fixture pass) ∥ S-58-c (a second backend
developer; the files don't overlap; their parity runs go one after the other). Wave 3, after S-58-b is approved: S-58-d ∥ S-58-e
(frontend; the files don't overlap). Then every gate on the combined tree, `composer test:pgsql` and `composer test:browser`.

### Risks
- SQLite rebuilds `product_categories` when the new column becomes NOT NULL and drops the `WHERE` of its partial name index → the
  migration drops and re-creates that index both ways, the AC-5 migration test checks it still refuses a duplicate, and parity runs on
  PostgreSQL.
- Older migration tests roll back S-21 or the products migration and now find a newer migration on top of them → S-58-a lists them, and
  the developer runs the whole suite, not only the new tests.
- `Product` gains a required field and Add/Edit gain a prop → typed fixtures break → the frontend fixture pass sits in the same commit
  as the contract (S-58-b).
- The product page, Add product and Edit product must stay within 10 queries → the lists are read once per request (at most three
  queries); if a page is still over, the lists come in one joined query.
- The race on the lock → accepted until S-60 ([F-64](../TECH-DEBT.md)).
- A category code made only of digits would make PHP send `category_sizes` as a list → it is always cast to an object, and the AC-3
  test uses such a code.

### Progress
- 2026-10-22 · build started in worktree `claude/magical-morse-e3d3b5` (from `main` 8ff2d47). Environment: `composer install` and `npm ci`
  from the lock files, `storage:link` and a build; `composer test` green on the base (2427 tests). Wave 1: S-58-a dispatched.
- 2026-10-22 · S-58-a approved in round 1 (all five gates green, 2443 PHP tests; the migration tests also pass on PostgreSQL in a
  targeted run). Docs: ARCHITECTURE §4 and the business rules ("Sizes"). Wave 2: S-58-b ∥ S-58-c dispatched.
- 2026-10-22 · S-58-b approved in round 2 (round 1: four older tests pinned the old page contract, size and query budgets; the
  create page's payload limit is now 6 KB and the show/create/edit budgets 10/3/7 extra queries, all pages still ≤ 10) plus its
  frontend fixture pass. S-58-c approved in round 1. Gates green; the S-58-b/c tests also pass on PostgreSQL in a targeted run.
  Wave 3: S-58-d ∥ S-58-e dispatched.
- 2026-10-22 · S-58-d and S-58-e approved in round 1. All five gates green on the combined tree (2463 PHP tests, 1225 Vitest
  tests with `--maxWorkers=3`; the default run hit the known F-22 timeouts), both browser golden paths pass. The full
  PostgreSQL parity suite was not run (the human's choice); the S-58 PHP tests pass on PostgreSQL in targeted runs. F-66 logged.
  Delivered; waiting for acceptance.
