---
id: S-60
title: Order a product in several sizes
type: feature
phase: 5
priority: Must
size: M
status: Ready
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-60 · Order a product in several sizes

> [!abstract] Description
> As a **workshop owner**, I want **to enter how many units of each size a client ordered**, so that **the cutting table knows it is S 10,
> M 20, L 15 and XL 5, not just "50 Denim Jackets"**.
> An order still has one line per product. Under it you type a quantity for each size of the product, and the line's quantity is their
> sum. Following each size through cutting, ready to ship and shipped is S-61, which ships together with this story.

> [!info] How it works today
> On "New order" and "Edit order", each product line has a product and a single "Quantity". Boutique Lina's order reads "50 × Denim
> Jacket, 30 × Denim Jeans 5-pocket · 80". How many of the 50 jackets are S or XL goes in the note, if anywhere, and nothing adds those
> numbers up.

## Acceptance criteria
- [ ] **AC-1 · A quantity per size.** On "New order", after choosing Denim Jacket (sizes S, M, L, XL), the line shows one field per size,
  labelled "S", "M", "L" and "XL", in that order, in place of "Quantity", and under them "Total 50 units" once you type S 10, M 20, L 15
  and XL 5. "Save order" shows "Order recorded." A size left empty counts as 0. A "One size" product (Leather Tote) keeps the single
  "Quantity" field, as today.
- [ ] **AC-2 · What a line accepts.** Each size takes a whole number from 0 to 1,000,000; anything else shows "Enter a whole number from 0
  to 1,000,000." under that size. A line whose sizes are all empty or 0 shows "Enter at least one unit of Denim Jacket." The line's total
  can't pass 1,000,000 ("A line can hold up to 1,000,000 units."). The other rules stay: one line per product ("Denim Jacket is already
  on this order."), up to 50 products, and only active products can be added.
- [ ] **AC-3 · The sizes show wherever the order does.** The order's page shows the line "Denim Jacket · PRD-JKT-001 · 50 units" with
  "S 10 · M 20 · L 15 · XL 5" under it. The Orders list shows "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" in "Products". A size with 0
  units is left out: an order of S 10 and XL 5 reads "15 × Denim Jacket (S 10 · XL 5)".
- [ ] **AC-4 · Editing the sizes.** "Edit order" shows each size's saved quantity. Changing M from 20 to 25 and saving shows "Order
  updated." and "55 units" on the line. Limits once work has started on a size are in S-61.
- [ ] **AC-5 · Lines recorded before sizes.** A line recorded before this change keeps its quantity as "One size": on the order's page it
  reads "Denim Jacket · PRD-JKT-001 · 50 units" with "One size 50", and in "Edit order" it keeps the single "Quantity" field with the
  hint "Recorded before sizes." It moves through the stages as today.
- [ ] **AC-6 · Phone width.** At 375 px the size fields wrap onto several rows, each with its label, and "Total 50 units" sits under the
  last row. The 11 shoe sizes (36 to 46) fit without scrolling sideways.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 8 commits (S-60 and S-61 in one build)
> Tech-lead · 2026-10-22 · approved 2026-10-22
> Why complex: a database change (a row per size under every order line, filled in for the orders you already have), new business
> rules (a quantity per size, limits per size once work has started, moves per size) and new data for the order pages, the order form
> and the move window.

> [!info] One build with S-61
> This section holds the plan both stories share: the overview of all eight steps, the contract, the decisions, the order and the
> risks. Steps S-60-a to S-60-e are described below; S-61-a, S-61-b and S-61-c in the Technical work of
> [S-61](S-61-follow-each-size-through-the-stages.md).

### In short
Under every order line Invory keeps a small row per size: how many were ordered, and how many are in cutting, ready to ship and
shipped. The line keeps its own figures as the sum of its sizes, so the Orders list, its "Open orders" filter, its totals and the
statuses keep working exactly as they do. When this ships, every line you already have gets one "One size" row holding its quantity and
its stages, so nothing you recorded changes. The server then sends the sizes to the order pages, saving an order accepts a quantity per
size, and every move asks a number per size; a cut still takes the materials of all its units together, and moving back still returns
from the latest cut first. Last come the screens: the order page's table per size, the order form's size fields, the sizes on the
Orders list and in a material's history, and the move window's fields per size
([ADR-0016](../adr/0016-order-lines-count-by-size.md)).

### Steps

| Step       | What it does                                     | Proves                                        | Lane         | Who                | Size       |
| ---------- | ------------------------------------------------ | --------------------------------------------- | ------------ | ------------------ | ---------- |
| **S-60-a** | Sizes stored per line; old lines become One size | S-60 AC-5                                     | CRITICAL     | Backend            | ~250 lines |
| **S-60-b** | The order pages receive the sizes                | S-60 AC-3, AC-5 · S-61 AC-1                   | CRITICAL     | Backend (+ FE fix) | ~200 lines |
| **S-61-b** | The order page shows each size and its stages    | S-60 AC-3, AC-5 · S-61 AC-1, AC-6, AC-7       | NON-CRITICAL | Frontend           | ~250 lines |
| **S-60-c** | Saving an order with a quantity per size         | S-60 AC-1, AC-2, AC-4, AC-5 · S-61 AC-5       | CRITICAL     | Backend            | ~300 lines |
| **S-60-d** | The order form asks a quantity per size          | S-60 AC-1, AC-2, AC-4, AC-5, AC-6 · S-61 AC-5 | CRITICAL     | Frontend           | ~300 lines |
| **S-60-e** | Sizes on the Orders list and in material history | S-60 AC-3 · S-61 AC-3, AC-4                   | NON-CRITICAL | Frontend           | ~100 lines |
| **S-61-a** | Moving units size by size                        | S-61 AC-2, AC-3, AC-4, AC-6                   | CRITICAL     | Backend            | ~250 lines |
| **S-61-c** | The move window asks a number per size           | S-61 AC-2, AC-3, AC-4, AC-7                   | CRITICAL     | Frontend           | ~300 lines |

The rows are in merge order: each server change lands right before the screen that uses it. Every step keeps `composer test`,
`npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-60-a, S-60-b, S-60-c and S-61-a also run
their PostgreSQL tests (`composer test:pgsql` with a filter: a new table, new constraints, new queries). After the last wave the combined
tree runs the full `composer test:pgsql` and `composer test:browser` (the order golden path changes in S-60-d and S-61-c). At Step 4
the human approved (2026-10-22) committing S-60-c and S-60-d together, and S-61-a and S-61-c together, so the app is never left with a
server change whose screen hasn't landed.

#### S-60-a · Sizes stored per line; old lines become One size
**Commit:** `feat(orders): a row per size under every order line; lines recorded before sizes become One size (S-60-a)`

- One migration:
  - `order_line_sizes`: `workshop_id`, `order_line_id` (`cascadeOnDelete`), `size_id` (`restrictOnDelete`), `quantity`, `in_cutting`,
    `ready_to_ship`, `shipped` (whole numbers, the counts default 0), timestamps; unique (`order_line_id`, `size_id`), an index on `size_id`.
  - `order_lines.size_list_id` (`restrictOnDelete`): the size list the line counts by.
  - `stock_movements.sizes` (JSON, nullable): the sizes a cutting entry moved (written from S-61-a).
  - Fill, by code and without importing an app class (the S-58 pattern): every existing line gets the `one_size` list and one size row
    on its only size, holding the line's quantity and its three counts.
  - PostgreSQL only: `order_lines.size_list_id` becomes NOT NULL (SQLite keeps it nullable, so `order_lines`, which two tables point
    at, is never rebuilt), a composite key (`workshop_id`, `order_line_id`) → `order_lines`, and CHECKs: quantity 1 … 1 000 000, counts
    ≥ 0, counts adding up to at most the quantity.
  - `down()` removes all three, in reverse.
- Models: `OrderLineSize` (`BelongsToWorkshop`, `line()`, `size()`), `OrderLine::sizes()` (in size order) and `sizeList()`, and
  `StockMovement` casts `sizes` to an array. To start and "started" move into one trait used by `OrderLine` and `OrderLineSize`, so
  S-57 changes that rule in one place.
- Factories: an order line gets the "One size" list and one matching size row by default (its counts copied, also with the `stages()`
  and `shipped()` states), so every existing test keeps its meaning; a `sized([...])` state builds a line counted by size, the line's
  figures being the sums. `tests/Pest.php` gets `bookSizedOrder()`: Boutique Lina with Denim Jacket S 10 · M 20 · L 15 · XL 5, with
  optional stages per size, for the later steps.
- The tech-lead writes in this commit: `docs/ARCHITECTURE.md` §4 (the new table and columns) and, once the human has approved it with
  this Technical work, the business rules file (glossary "Order line size"; a short "Orders by size" section: a line's quantity and
  stages are the sums of its sizes, moves per size, a cut takes the total and moving back returns from the latest cut first, lines
  recorded before sizes are "One size").

**Tests**
- S-60 AC-5, one migration test (roll back, put rows in the old shape, run forward; the S-58 pattern): Boutique Lina's Denim Jacket 50
  with 20 in cutting and 10 ready to ship, and Hotel Dar Said's fully shipped Leather Tote 12, each get the "One size" list and one row
  with the same quantity and counts; nothing else on the line changes; a round trip (down, up) gives the same rows.
- One schema test (PostgreSQL only, as `OrderSchemaTest` does): the database refuses a size row with quantity 0, a negative count,
  counts above the quantity, and a row of another workshop's line.
- One dataset over the factory's default, `stages()`, `shipped()` and `sized()`: every line equals the sum of its size rows.
- The workshop arch test treats `OrderLineSize` as workshop data.

> [!example]- Files (10, plus the older migration tests)
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead, once the human approves it)
> - `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Models/OrderLineSize.php` (new)
> - `app/Models/Concerns/HasStageCounts.php` (new)
> - `app/Models/{OrderLine,StockMovement}.php`
> - `database/migrations/2026_10_22_100000_create_order_line_sizes_table.php` (new)
> - `database/factories/{OrderLineFactory,OrderLineSizeFactory}.php` (the second one new)
> - `tests/Pest.php` (`bookSizedOrder()`)
> - `tests/Feature/Orders/OrderLineSizesMigrationTest.php` (new, AC-5)
> - `tests/Feature/Orders/OrderSchemaTest.php`
> - The older migration tests that roll back past this migration (S-58's list and `SizeListMigrationTest`; the developer greps for
>   them)

#### S-60-b · The order pages receive the sizes
**Commit:** `feat(orders): send the sizes of every order line to the order pages (S-60-b)`

- `OrderLineResource` gains `size_mode` and `sizes` (a new `OrderLineSizeResource`: the size, its quantity and its four stages, in size
  order). One service, `OrderLineSizing`, decides how a line counts: `by_size` when its size list has several sizes, `one_size` when its
  product is "One size", `before_sizes` when the line counts as "One size" but its product has several sizes. S-60-c and S-61-a ask the
  same service.
- New order and Edit order receive `size_lists` (the three lists by code, always a JSON object, from the S-58 `SizeLists` service) and
  each offered product's `size_list_code` (a new `OrderFormProductResource`: the order product plus that code), so the page carries
  each list once however many products it offers.
- `StockMovementResource` gains `sizes` (null until S-61-a writes it).
- `OrderBook` loads the size rows and their labels with the lines (the Orders list, an order's page, Edit order) in a fixed number of
  queries.
- `resources/js/types/domain.ts` as in the contract below, then a short frontend pass in the same commit on the typed test fixtures
  (`OrderLine` literals get `size_mode` and `sizes`, `StockMovement` literals `sizes`, the New/Edit order props `size_lists` and
  `size_list_code`), so `npm run types:check` stays green. Nothing on screen changes yet.

**Tests**
- S-60 AC-3 and S-61 AC-1, one test on an order's page: Boutique Lina's Denim Jacket S 10 · M 20 · L 15 · XL 5 has `size_mode`
  "by_size", four sizes in order with To start 10, 20, 15 and 5, and the line's quantity 50 and To start 50.
- S-60 AC-5, one dataset on `size_mode`: a line counted by size → "by_size" · a Leather Tote line → "one_size" · a Denim Jacket line
  recorded before sizes → "before_sizes", with one size "One size" holding 50.
- Contract: the `size_mode` and `sizes` shape joins the order contract test (one row); the New/Edit order props test asserts
  `size_lists` (apparel "S, M, L, XL", footwear 11 sizes, "One size") and a product's `size_list_code`; the stock-movement contract
  gets `sizes` null (one row).
- The query-budget tests of the Orders list, an order's page, New order and Edit order stay at 10 or fewer and don't grow with the
  number of orders or lines (the test changes only if it must).

> [!example]- Files (10, plus the frontend fixture pass)
> - `app/Enums/OrderLineSizeMode.php` (new)
> - `app/Services/Orders/OrderLineSizing.php` (new)
> - `app/Http/Resources/{OrderLineSizeResource,OrderFormProductResource}.php` (new)
> - `app/Http/Resources/{OrderLineResource,StockMovementResource}.php`
> - `app/Services/Orders/OrderBook.php`
> - `app/Http/Controllers/OrderController.php` (`create`, `edit`)
> - `resources/js/types/domain.ts`
> - `tests/Feature/Orders/OrderSizesTest.php` (new: S-60 AC-3, AC-5, S-61 AC-1)
> - `tests/Feature/Orders/OrderPagesTest.php` and the stock-movement contract test
> - `tests/Feature/Performance/QueryBudgetTest.php` (only if a budget must change)
> - Frontend pass, only where `npm run types:check` needs it: `resources/js/test/fixtures/*` and the order and history component
>   tests that build an `OrderLine` or a `StockMovement`

#### S-60-c · Saving an order with a quantity per size
**Commit:** `feat(orders): record and edit a quantity per size, with limits per size once work has started (S-60-c)`

- `StoreOrderRequest` asks `OrderLineSizing` how each line counts (from its product, or on Edit order from the saved line) and checks:
  - `by_size` lines: `sizes` (size id → whole number); each size 0 … 1 000 000 or empty, otherwise "Enter a whole number from 0 to
    1,000,000." under that size (`lines.N.sizes.<size id>`); only sizes of the product's list. Then, on the line (`lines.N.sizes`): every
    size empty or 0 → "Enter at least one unit of Denim Jacket."; a total above 1 000 000 → "A line can hold up to 1,000,000 units.".
  - `one_size` and `before_sizes` lines: `quantity`, with today's rule and message (`lines.N.quantity`).
  - The other key of a line is ignored. One line per product, up to 50 products and active products only stay as they are.
- `SyncOrderLines` locks the products of the lines (ascending id), works out again under that lock how each line counts (closes
  [F-64](../TECH-DEBT.md)), and writes each line's size rows (a size left empty or set to 0 loses its row) and the line's size list,
  quantity and counts as their sums. A size with units started keeps at least those units: "M can't be less than the 10 units already in
  cutting, ready to ship or shipped." under that size; a single-field line keeps today's message under `lines.N.quantity`; removing a
  started line keeps today's refusal. The request runs the same refusals early on unlocked data, as today; the Action's are the ones
  that count.
- `OrderMessages` gets the new sentences, each with its singular. `domain.ts`: `OrderLineFormRow` gains `sizes`.
- The tech-lead closes F-64 in `docs/TECH-DEBT.md`.

**Tests**
- S-60 AC-1, one test: New order with Denim Jacket S 10 · M 20 · L 15 · XL 5 and Leather Tote quantity 12 → "Order recorded.", the
  jacket's four rows and quantity 50, the tote's one "One size" row of 12; with XL left empty → no XL row, quantity 45.
- S-60 AC-2, one dataset on a Denim Jacket line: M "-1" · "2.5" · "abc" · "1000001" → the size message under M · every size empty ·
  every size 0 → "Enter at least one unit of Denim Jacket." · S 1000000 and M 1 → "A line can hold up to 1,000,000 units." · a size of
  the footwear list → refused · `quantity` 50 without sizes → refused. Nothing is saved.
- S-60 AC-4, one test: Edit order, M 20 → 25 → "Order updated.", quantity 55, M 25; then XL → 0 removes the XL row (quantity 50).
- S-60 AC-5, one test: a Denim Jacket line recorded before sizes saved with `quantity` 60 → its "One size" row and the line read 60,
  still "before_sizes".
- S-61 AC-5, one dataset with 10 M in cutting · ready to ship · shipped: M 8 → "M can't be less than the 10 units already in cutting,
  ready to ship or shipped." and nothing saved · M 10 and L 20 → saved · the line removed → "Denim Jacket can't be removed: 10 units
  have started.".
- Two tests calling `SyncOrderLines` directly: M below its started units (the check under the lock), and a product moved to Footwear
  after the request was checked (F-64). Both refused, nothing saved.
- The order write tests that add a Denim Jacket with `quantity` move to `sizes`, only where they must.

> [!example]- Files (8, plus the tests that post a Denim Jacket)
> - `app/Http/Requests/Orders/{StoreOrderRequest,UpdateOrderRequest}.php`
> - `app/Actions/Orders/{SyncOrderLines,RecordOrder,UpdateOrder}.php`
> - `app/Services/Orders/OrderMessages.php`
> - `resources/js/types/domain.ts`
> - `docs/TECH-DEBT.md` (tech-lead: F-64 closed)
> - `tests/Feature/Orders/OrderSizesWriteTest.php` (new: S-60 AC-1, AC-2, AC-4, AC-5, F-64)
> - `tests/Feature/Orders/EditStartedOrderTest.php` (S-61 AC-5)
> - `tests/Feature/Orders/OrderWriteTest.php` and the other tests that post a Denim Jacket line (only where they must)

#### S-60-d · The order form asks a quantity per size
**Commit:** `feat(orders): a quantity per size on New order and Edit order (S-60-d)`

- Once a product with several sizes is chosen, its line shows one field per size, labelled with the size, in order, in place of
  "Quantity", and under them "Total 50 units" (the whole numbers typed, added up as you type; an empty field counts 0). A "One size"
  product keeps the single "Quantity" field.
- Edit order fills each size with its saved quantity. A line recorded before sizes keeps the single "Quantity" field with the hint
  "Recorded before sizes.", also if it is removed and its product added back in the same visit (the server matches lines by product).
- Errors: a size's message under that size; "Enter at least one unit of …" and "A line can hold up to 1,000,000 units." under "Total";
  every field labelled and linked to its message for screen readers.
- Phone: the size fields wrap onto several rows, each with its label, and "Total" sits under the last row; the 11 shoe sizes fit
  without scrolling sideways. The fields live in one small component that S-61-c reuses.
- The order golden path fills Denim Jacket S 10 · M 20 · L 15 · XL 5 and Denim Jeans 5-pocket per size.

**Tests**
- S-60 AC-1, one test: choosing Denim Jacket shows "S", "M", "L" and "XL" in that order and no "Quantity"; typing 10, 20, 15 and 5
  shows "Total 50 units"; saving posts the sizes by id; Leather Tote shows "Quantity".
- S-60 AC-2 and S-61 AC-5, one `it.each` on server messages: a size's message under that size · a line's message under "Total".
- S-60 AC-4 and AC-5, one test on Edit order: the saved sizes fill the fields; a line recorded before sizes shows "Quantity" and
  "Recorded before sizes.".
- S-60 AC-6, one test: the fields sit in a wrapping layout, each with its label, "Total" after the last (jsdom has no layout; the
  click-through checks 375 px).

> [!example]- Files (9)
> - `resources/js/components/orders/order-size-fields.tsx` (new)
> - `resources/js/components/orders/order-size-fields.test.tsx` (new)
> - `resources/js/components/orders/{order-line-row,order-lines-editor,order-form}.tsx`
> - `resources/js/components/orders/{order-lines-editor,order-form}.test.tsx`
> - `resources/js/pages/orders/{create,edit}.tsx`
> - `resources/js/pages/orders/{create,edit}.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php` (the "fill in" step)

#### S-60-e · Sizes on the Orders list and in material history
**Commit:** `feat(orders): sizes on the Orders list and in a material's history (S-60-e)`

- The Orders list's "Products" reads "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)", sizes with 0 left out. A "One size" line and a
  line recorded before sizes get no parenthesis and read as today ("12 × Leather Tote", "50 × Denim Jacket").
- A material's history shows the sizes of both kinds of entry that carry them (written from S-61-a): "Cutting" reads "−27.72 m ·
  Boutique Lina · 14 × Denim Jacket (S 4 · M 10)" and "Back from cutting" reads "+9.9 m · Boutique Lina · 5 × Denim Jacket (M 5)".
  Entries without sizes (lines that count as one size, and every other kind of entry) read as today, with no parenthesis.
- Both use the size helper that S-61-b adds.

**Tests**
- S-60 AC-3, one `it.each` on the list: S 10 · M 20 · L 15 · XL 5 → "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" · S 10 and XL 5 →
  "15 × Denim Jacket (S 10 · XL 5)" · Leather Tote 12 → "12 × Leather Tote" · a Denim Jacket line recorded before sizes → "50 × Denim
  Jacket".
- S-61 AC-3 and AC-4, one `it.each` on the history: a cutting entry with sizes S 4 and M 10 → AC-3's text · a "Back from cutting"
  entry with M 5 → AC-4's text · a cutting entry without sizes → as today, no parenthesis.

> [!example]- Files (4)
> - `resources/js/components/orders/orders-table.tsx`
> - `resources/js/components/orders/orders-table.test.tsx`
> - `resources/js/components/materials/history-list.tsx`
> - `resources/js/components/materials/history-list.test.tsx`

### Contract (frozen before dispatch)
```ts
/** How an order line counts its units (S-60, ADR-0016). */
export type OrderLineSizeMode =
    | 'by_size' // the line's size list has several sizes: a quantity and a move per size
    | 'one_size' // a "One size" product: one "Quantity" field and one "How many units?" field, as before
    | 'before_sizes'; // recorded before sizes: kept as "One size" whatever the product's sizes (S-60 AC-5)

/** One size of an order line (S-60, S-61). Only sizes ordered (quantity ≥ 1); its four stages add up to its quantity. */
export type OrderLineSize = {
    size: Size;
    quantity: number;
    to_start: number; // computed server-side
    in_cutting: number;
    ready_to_ship: number;
    shipped: number;
};

// OrderLine gains:  size_mode: OrderLineSizeMode;
//                   sizes: OrderLineSize[];  // in size order, at least one; the line's own figures are their sums (server-side)

/** A product the order forms offer (S-60): an OrderProduct and the code of its size list in `size_lists`. */
export type OrderFormProduct = OrderProduct & { size_list_code: string };

// OrderCreateProps becomes { products: OrderFormProduct[]; size_lists: Record<string, SizeList> }
// OrderEditProps becomes   { order: Order; products: OrderFormProduct[]; size_lists: Record<string, SizeList> }

// OrderLineFormRow gains (S-60-c):  sizes?: Record<number, string>;
//     by_size lines: size id → the quantity typed ('' counts 0); single-field lines send `quantity` as before.
//     Errors: lines.N.sizes (the line) and lines.N.sizes.<size id> (a size); single-field lines keep lines.N.quantity.
// MoveUnitsFormData gains (S-61-a): sizes?: Record<number, string>;
//     by_size lines: size id → the units typed ('' counts 0); single-field lines send `units` as before.
//     Errors: sizes (the move) and sizes.<size id> (a size); single-field lines keep units.

// StockMovement gains: sizes: { size_id: number; label: string; units: number }[] | null;
//     cutting and cutting_return of a by_size line: the sizes moved (units ≥ 1), in size order; null otherwise.
```
PHP: `App\Enums\OrderLineSizeMode` (`BySize`, `OneSize`, `BeforeSizes`); `App\Services\Orders\OrderLineSizing` (`forLine(OrderLine
$line)`, `forProduct(Product $product)`, both `OrderLineSizeMode`); `OrderLine::sizes()`, `OrderLine::sizeList()`, `OrderLineSize::line()`,
`OrderLineSize::size()`; `SyncOrderLines::handle(Order $order, array $lines)` (each line validated: `product_id`, and `quantity` or
`sizes`); `MoveOrderLineUnits::handle(OrderLine $line, OrderLineMove $move, array $unitsBySize, User $actor): MoveResult` (`array<int,
int>`, size id → units ≥ 1; the controller turns a single-field `units` into the line's only size);
`TakeMaterialsForCut::handle(..., ?array $sizes = null)` and `ReturnMaterialsFromCuts::handle(..., ?array $sizes = null)` (the
`StockMovement::$sizes` snapshot, or null). The move preview (`orders.lines.moves.preview`, `units` = the total) doesn't change.

The screens add only the card's words around server data; the one sum they make is "Total N units" while typing (see Decisions).

### Decisions
- **A row per size under each line, and the line keeps its four figures as their sums** ([ADR-0016](../adr/0016-order-lines-count-by-size.md)).
  The Orders list's filter, totals, search and units, the statuses and the delete refusal keep reading the line, so S-61 AC-6 holds
  without touching them. Only `SyncOrderLines` and `MoveOrderLineUnits` write sizes, both under the order lock, and their tests check
  that the line equals its sizes after every write.
- **Every line counts by a size list**, stored on the line. Existing lines get "One size" (AC-5); a new line takes its product's. The
  product can't change sizes while it is on an order (S-58 AC-4), so only lines recorded before sizes differ from their product.
- **Lines that count as one size keep today's fields, payloads, messages and error keys** (`quantity`, `units`): the Leather Tote and
  every older line work exactly as before, and the tests that use them don't change. Lines counted by size send a value per size id;
  ids rather than positions, so a message lands on its size whatever the order on screen.
- **Moves are all or nothing.** One size over its limit refuses the whole move (S-61 AC-2).
- **Cuts stay per line.** A cut takes the materials of the total; moving back returns from the latest cut first, whatever sizes the
  cuts held, since every size uses the same composition (S-61 AC-3, AC-4). The cutting maths and its tables don't change.
- **The sizes of a move are stored on its history entries** as a small snapshot (size, label, units), so the history needs no extra
  query and keeps its text. Both "Cutting" and "Back from cutting" entries show them (S-61 AC-3, AC-4).
- **Where sizes show (the product-owner's decisions of 2026-10-22).** Under a line's heading on the order page: the sizes for a line
  counted by size, "One size 50" only for a line recorded before sizes, and nothing for a "One size" product, as today. The Orders list
  and the history put sizes in a parenthesis only for lines counted by size. The size table has a row per size ordered (a size with 0
  units has no row), and the move buttons sit in its Total row under each figure. A product with several sizes ordered in one size
  still gets the per-size move window, with one field.
- **Saving an order locks the products of its lines** before checking their sizes (closes F-64).
- **"Total 50 units" is added up on screen while typing.** It adds whole numbers typed into the fields, as input feedback; the saved
  quantity and every stage figure come from the server. No decimal or money is involved.
- **The order forms get the three size lists once** (`size_lists`) and each product names its list by code, so New order stays small
  with a large catalogue.
- **Built so S-57 and S-56 don't redo it.** The stage figures have the same names on the line and on its sizes, To start is worked out
  in one place, the move engine follows each move's from-stage and to-stage, and the order page's size table takes its columns as a list,
  so a cancelled order can show Dropped / In cutting / Put in stock / Shipped (S-57 AC-7) by adding its stages, not by rewriting this.
- Costing, "Can produce", the bottleneck, products, sales and the dashboard don't change. The business rules file gets an "Orders by
  size" section in S-60-a, once the human approves it.

### Order
Wave 1 (backend): S-60-a. Wave 2 (backend, then its frontend fixture pass): S-60-b. Wave 3: S-60-c (backend) ∥ S-61-b (frontend).
Wave 4: S-61-a (backend; after S-60-c, since both change `OrderMessages` and `domain.ts`) ∥ S-60-d (frontend; needs S-60-c) ∥ S-60-e
(frontend; needs S-61-b's helper; no file shared with S-60-d). Wave 5: S-61-c (frontend; needs S-61-a and S-60-d's size fields). Then
every gate on the combined tree, the full `composer test:pgsql` and `composer test:browser`. Parity runs never overlap.

### Risks
- A line's figures drifting from its sizes → only two Actions write them, both under the order lock; every write test checks the sums;
  factories build matching rows; PostgreSQL CHECKs guard each row.
- The fill of existing lines → a migration test with started and fully shipped lines, a parity run, and `order_lines` is never rebuilt
  on SQLite (its NOT NULL is PostgreSQL only).
- Older migration tests roll back past the new migration → S-60-a lists them, and the developer runs the whole suite.
- Tests that post a Denim Jacket with one quantity break once sizes are required → S-60-c updates only those; factory lines stay "One
  size", so the move tests keep working unchanged.
- Between S-60-c and S-60-d (and between S-61-a and S-61-c) the old screens can't save a line counted by size → the human approved
  committing each pair together at Step 4 (2026-10-22), in the merge order above, and the browser golden path runs on the combined tree.
- The Orders list and an order's page must stay within 10 queries with the sizes → fixed eager loads; the size lists come from the
  per-request `SizeLists` service if a page needs them.
- New messages and contract lines from two backend steps → S-60-c and S-61-a run one after the other.
- The known Vitest timeouts (F-22) → the final run may use `npx vp test --maxWorkers=3`, as S-58 did.
