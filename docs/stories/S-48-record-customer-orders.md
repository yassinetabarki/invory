---
id: S-48
title: Record customer orders with one or more products
type: feature
phase: 5
priority: Must
size: M
status: Planned
created: 2026-10-15
tags:
  - story
  - phase-5
---

# S-48 · Record customer orders with one or more products

> [!abstract] Description
> As a **workshop owner**, I want **to record each order a client places: the client, every product they ordered with its quantity, and
> anything they asked for**, so that **I have one list of everything I've promised to make, instead of notes on paper and in messages**.
> One order can hold several products, each on its own line with its own quantity (for example 50 Denim Jackets and 30 Denim Jeans for the
> same boutique). Orders get their own page, "Orders", in the sidebar. Tracking each line's stages comes with S-49, and taking materials out
> of stock when cutting starts comes with S-53. Recording an order never changes material stock, never sets stock aside and never records a
> sale.

> [!info] How it works today
> Nothing yet. Invory has no orders, and the sidebar shows Dashboard, Materials, Products and Sales. The closest thing is "Record sale" on
> the Sales page: one product, a quantity, a unit price, a date and a note with the placeholder "Channel, customer or order number". But a
> sale is something already sold: it counts in the dashboard totals at once, it has no client, and it holds only one product. So today an
> order of 50 Denim Jackets and 30 Denim Jeans for a boutique lives in a notebook or a phone message.

## Acceptance criteria
- [ ] **AC-1 · An "Orders" page.** The sidebar shows "Orders" between "Products" and "Sales". It opens the page "Orders", described as
  "Every order your clients place with you.", with a "New order" button. With no orders yet, the page shows "No orders yet", "Record a
  client's order to keep track of what you've promised to make." and a "New order" button.
- [ ] **AC-2 · Record an order with two products.** "New order" opens the page "New order" with a "Details" section ("Client" with the
  placeholder "e.g. Boutique Lina", "Order date" with today filled in, "Note (optional)" on several lines with the placeholder "Delivery
  date, packing, anything the client asked for") and a "Products" section. "Products" starts with one empty line ("Choose a product…" and
  "Quantity"), and "Add product" adds another. The product picker lists only active products and shows "Denim Jacket · PRD-JKT-001". When
  you enter "Boutique Lina", 15 Oct 2026, "Deliver before 30 Oct, in 2 boxes", Denim Jacket 50 and Denim Jeans 5-pocket 30 and click "Save
  order", then the toast says "Order recorded.", the Orders list opens, and its first row reads 15 Oct 2026 · Boutique Lina · "50 × Denim
  Jacket" and "30 × Denim Jeans 5-pocket" · 80 units · "Deliver before 30 Oct, in 2 boxes". "Cancel" returns to the list and saves nothing.
- [ ] **AC-3 · Lines follow simple rules.** Each line's "Remove" button is named after its product ("Remove Denim Jeans 5-pocket"); on a
  line with no product yet it reads "Remove line". With every line removed, the section reads "No products yet. Add the products the client
  ordered." and saving shows "Add at least one product.". Saving with Denim Jacket on two lines shows "Denim Jacket is already on this
  order." under the second line only. An order holds up to 50 lines; at 50, "Add product" is disabled with the hint "An order can hold up
  to 50 products.", and an order of more than 50 products is refused with that same message.
- [ ] **AC-4 · Clear messages for mistakes.** On any of these, nothing is saved, the page keeps what you typed, and the message shows under
  its field:
  - Client left empty: "The client field is required."; longer than 100 characters: "The client field must not be greater than 100
    characters.".
  - A line with no product: "Choose a product.".
  - A line's product that isn't active (for example archived or set back to draft while the form was open): "Only active products can be
    ordered.".
  - A line's quantity empty, 0, 2.5 or more than 1,000,000: "The quantity must be a whole number from 1 to 1,000,000.".
  - Order date left empty: "The order date field is required."; after today: "The order date can't be in the future.".
  - Note longer than 1,000 characters: "The note field must not be greater than 1000 characters.".
- [ ] **AC-5 · The list.** Orders show in a table with the columns "Order date", "Client", "Products" (one "50 × Denim Jacket" line per
  product, in the order you entered them, each name linking to the product's page; an order with five products shows the first three and
  "+ 2 more"), "Units" (the sum of the lines: 80) and "Note" (cut short with "…"), plus an Edit and a Delete button on each row. The newest
  order date comes first; on the same date, the order recorded last comes first. The list shows 25 orders a page, with page buttons like
  the Sales list. The search box "Search client, product or note" finds Boutique Lina's order with "lina", "JEANS" or "PRD-JKT-001"
  (capitals don't matter). When nothing matches, the list shows "No orders match your search" and a "Clear search" button. At phone width
  (375 px) each order is a card: "Boutique Lina", then "50 × Denim Jacket" and "30 × Denim Jeans 5-pocket", the date and the note, with
  "Edit" and "Delete" buttons.
- [ ] **AC-6 · Edit and delete.** "Edit" opens the page "Edit order" with the order's details and lines. Changing Denim Jeans 5-pocket from
  30 to 40, adding Classic White T-shirt 20 and clicking "Save order" shows "Order updated." and the row reads "50 × Denim Jacket", "40 ×
  Denim Jeans 5-pocket" and "20 × Classic White T-shirt", 110 units. "Cancel" on "Edit order" returns to the list and changes nothing. On
  the order as first recorded (80 units), "Delete" asks "Delete order?" with "The order of Boutique Lina (2 products, 80 units) will be
  removed. Material stock and sales are not affected. This cannot be undone." and the buttons "Delete order" and "Cancel"; an order of one
  product with one unit reads "(1 product, 1 unit)". "Delete order" shows "Order deleted." and the row is gone; "Cancel" keeps it.
- [ ] **AC-7 · Orders stay in your workshop and change nothing else.** Another workshop's account never sees Boutique Lina's order, in its
  list or its search, and anything it sends to change or delete this order is refused as "Not Found". The product picker offers only your
  workshop's products. Recording, editing or deleting an order changes no material stock, no sale and no figure: Denim Jacket still shows
  "Can produce now 112", and the dashboard totals stay the same. If Denim Jacket is later archived, set back to draft or deleted, its order
  lines stay and still show "Denim Jacket" (a deleted product's name has no link), and when you edit the order, that line keeps Denim Jacket
  and you can still change its quantity, for example from 50 to 60.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 5 commits
> Tech-lead · 2026-10-15 · approved 2026-10-15
> Why complex: two new tables with foreign keys and workshop rules, new business rules on what an order may hold, and a new contract
> between the server and the screens (routes, page data, types).

### In short
Two new tables hold the orders: one row per order (client, order date, note) and one row per product line (product, quantity, position).
Both carry the workshop like every other record, so another workshop's order is simply "not found" without any screen having to remember
it, and the existing tests that walk every page and address as a second workshop cover the new pages automatically. The server is built
first: the tables (a), then the list with its search and Delete, plus the data the two forms need (b), then saving a new or changed order
with every rule from AC-3 and AC-4 (c). The screens follow: the Orders page and its sidebar entry (e) can start as soon as (b) is in, the
New order and Edit order forms (d) once (c) is in. Recording, editing or deleting an order touches no stock, sale or figure: the order code
never reads or writes materials, stock history or sales, and a test checks the figures before and after. A deleted order is hidden, not
erased, so that S-53 can later point each "Cutting" history entry at the order line it came from.

### Steps

| Step       | What it does                                        | Proves                       | Lane         | Who      | Size       |
| ---------- | --------------------------------------------------- | ---------------------------- | ------------ | -------- | ---------- |
| **S-48-a** | Orders and their product lines get their own tables | AC-7                         | CRITICAL     | Backend  | ~220 lines |
| **S-48-b** | The list, its search and Delete; data for the forms | AC-1, AC-5, AC-6, AC-7       | CRITICAL     | Backend  | ~350 lines |
| **S-48-c** | Saving a new or changed order, with every rule      | AC-2, AC-3, AC-4, AC-6, AC-7 | CRITICAL     | Backend  | ~280 lines |
| **S-48-d** | The New order and Edit order pages                  | AC-2, AC-3, AC-4, AC-6       | CRITICAL     | Frontend | ~380 lines |
| **S-48-e** | The Orders page, its sidebar entry and Delete       | AC-1, AC-5, AC-6, AC-7       | NON-CRITICAL | Frontend | ~320 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-48-a to
S-48-c also run `composer test:pgsql` (new tables and queries); S-48-d also runs `composer test:browser` (golden path plus the new order
path).

#### S-48-a · Orders and their product lines get their own tables
**Commit:** `feat(orders): orders and order lines belong to a workshop (S-48-a)`

**Tests**
- 1 migration test: both tables created, and `down()` drops them.
- 1 PostgreSQL-only dataset: the database refuses a line whose workshop differs from its order's or its product's, a quantity of 0, and the
  same product twice on one order.
- 1 stamp dataset (2 rows): an order takes the current workshop; a line takes its order's.
- 1 policy dataset (2 rows): same workshop allowed; another workshop's order is 404.
- No new arch test: `orders` and `order_lines` join the existing workshop-table list, and the "every model uses `BelongsToWorkshop`" rule
  picks up both models by itself.

> [!example]- Files (11)
> - `app/Models/Order.php`
> - `app/Models/OrderLine.php`
> - `app/Policies/OrderPolicy.php`
> - `database/migrations/2026_10_15_200000_create_orders_and_order_lines_tables.php`
> - `database/factories/{Order,OrderLine}Factory.php`
> - `tests/Feature/Orders/OrderSchemaTest.php`
> - `tests/Feature/Workshops/WorkshopStampTest.php` (2 rows)
> - `tests/Feature/Workshops/WorkshopPoliciesTest.php` (2 rows)
> - `tests/Arch/WorkshopScopeTest.php` (table and model lists)
> - `docs/ARCHITECTURE.md` §4 and §5 (tech-lead)

#### S-48-b · The list, its search and Delete; data for the forms
**Commit:** `feat(orders): orders list with search and delete; form data for new and edit (S-48-b)`

**Tests**
- AC-5 list test: one `assertInertia` shape on Boutique Lina's row (date, client, lines in the order entered, 80 units, note); newest order
  date first, then the order recorded last; 25 a page.
- AC-5 search dataset: "lina", "JEANS", "PRD-JKT-001" find the order; a term that matches nothing returns none.
- AC-7 kept lines: a line whose product was archived or deleted still reads "Denim Jacket", and the deleted one is flagged.
- Form data: New order lists only active products (Canvas Chore Jacket, a draft, and another workshop's products are absent); Edit order
  also lists the order's own archived or deleted products.
- AC-6 delete: "Order deleted.", the row is gone, and Denim Jacket's "Can produce" (112), stock, sales and the dashboard figures are
  unchanged.
- 1 query-budget row for the list (25 orders of 5 lines).
- The existing route guards pick up the new routes: guest redirect rows, another workshop's order is 404 on every route, and the leak scan
  walks the new pages with a marked order.

> [!example]- Files (20)
> - `app/Http/Controllers/OrderController.php` (index, create, edit, destroy)
> - `app/Http/Requests/Orders/IndexOrderRequest.php`
> - `app/Http/Resources/{Order,OrderLine,OrderProduct}Resource.php`
> - `app/Actions/Orders/DeleteOrder.php`
> - `app/Services/Orders/OrderBook.php` (list, search, product options)
> - `app/Models/Order.php` (search and newest-first scopes)
> - `routes/web.php`
> - `resources/js/types/domain.ts` (the whole contract below, form types included)
> - `resources/js/pages/orders/{index,create,edit}.tsx` (stubs, so the server tests can render the pages)
> - `tests/Feature/Orders/OrdersIndexTest.php`
> - `tests/Feature/Orders/OrderPagesTest.php`
> - `tests/Feature/Orders/DeleteOrderTest.php`
> - `tests/Feature/Performance/QueryBudgetTest.php` (1 row)
> - `tests/Feature/Routes/RoutesTest.php` (rows and the `order` parameter)
> - `tests/Feature/Workshops/CrossWorkshopAccessTest.php` (`order` record, table fingerprint)
> - `tests/Feature/Workshops/WorkshopLeakScanTest.php` (`order` parameter, a marked order)

#### S-48-c · Saving a new or changed order, with every rule
**Commit:** `feat(orders): record and edit an order with one or more products (S-48-c)`

**Tests**
- AC-2 happy path: Boutique Lina, 15 Oct 2026, the note, Denim Jacket 50 and Denim Jeans 5-pocket 30 → saved as given (positions, who
  recorded it), toast "Order recorded.", redirect to the list, and the first row reads as AC-2 says.
- AC-3 and AC-4 validation dataset (about 17 rows, time fixed at 15 Oct 2026), each with the card's exact message under its field and
  nothing saved: client empty and 101 characters; order date empty and 16 Oct 2026; note of 1,001 characters; no lines ("Add at least one
  product."); a line without a product; quantity empty, 0, 2.5 and 1,000,001; Denim Jacket on two lines ("Denim Jacket is already on this
  order." under the second line only, nothing under the first); 51 lines ("An order can hold up to 50 products."); a draft product
  (Canvas Chore Jacket) and an archived one ("Only active products can be ordered."); another workshop's product (the same message as an
  id that doesn't exist).
- AC-6 update: Denim Jeans 5-pocket 30 → 40 and Classic White T-shirt 20 added → "Order updated.", lines in the order entered, 110 units;
  two products swapped between lines also saves.
- AC-7 kept product (dataset: archived, set back to draft, deleted): the product already on the order stays valid when the order is saved
  again, and its line's quantity changes from 50 to 60; the same product that isn't on the order is refused with "Only active products can
  be ordered." (a deleted one like an id that doesn't exist).
- AC-7 nothing else changes: after recording and editing, materials, stock history and sales are identical, and Denim Jacket still shows
  112.
- 1 rollback: a line that fails to save leaves no order behind.

> [!example]- Files (11)
> - `app/Http/Controllers/OrderController.php` (store, update)
> - `app/Http/Requests/Orders/{Store,Update}OrderRequest.php`
> - `app/Actions/Orders/{RecordOrder,UpdateOrder,SyncOrderLines}.php`
> - `routes/web.php` (store and update)
> - `tests/Feature/Orders/OrderWriteTest.php`
> - `tests/Feature/Orders/OrderActionsTest.php`
> - `tests/Pest.php` (`validOrderPayload()`)
> - `tests/Feature/Workshops/CrossWorkshopAccessTest.php` (update payload)

#### S-48-d · The New order and Edit order pages
**Commit:** `feat(orders): new order and edit order pages with a products editor (S-48-d)`

**Tests**
- Order form, happy path (AC-2): the fields with their placeholders, today pre-filled, "Add product", the picker label "Denim Jacket ·
  PRD-JKT-001", the exact payload sent by "Save order", and "Cancel" going back to the list.
- Order form, error branch (AC-4): the server's messages show under their fields, line ones included, and what was typed stays.
- Order form, edge cases (AC-3): "Remove Denim Jeans 5-pocket" and "Remove line"; every line removed shows the empty text; at 50 lines
  "Add product" is disabled with its hint.
- 1 page smoke for New order; 1 for Edit order (lines filled in; a deleted product's line still reads "Denim Jacket" and its quantity
  stays editable; "Cancel" links back to the list and sends nothing, AC-6).
- 1 browser test: New order → Boutique Lina with two products → "Order recorded." and the first row on the list.

> [!example]- Files (10)
> - `resources/js/pages/orders/create.tsx`
> - `resources/js/pages/orders/edit.tsx`
> - `resources/js/components/orders/order-form.tsx`
> - `resources/js/components/orders/order-lines-editor.tsx`
> - `resources/js/components/orders/order-line-row.tsx`
> - `resources/js/components/orders/order-form.test.tsx`
> - `resources/js/components/orders/order-lines-editor.test.tsx`
> - `resources/js/pages/orders/create.test.tsx`
> - `resources/js/pages/orders/edit.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php`

#### S-48-e · The Orders page, its sidebar entry and Delete
**Commit:** `feat(orders): orders page with search, phone cards and delete (S-48-e)`

**Tests**
- Page smoke (AC-1, AC-5): title, description and "New order"; the empty state; Boutique Lina's row; "No orders match your search" with
  "Clear search".
- Orders table (AC-5, AC-7): an order of five products shows three and "+ 2 more"; a deleted product's name has no link; at phone width
  each order is a card (queries scoped with `within()`).
- Delete dialog (AC-6): the exact title and text for 2 products and 80 units, and the singular "(1 product, 1 unit)"; "Cancel" keeps the
  order.
- Sidebar (AC-1): "Orders" (icon `ClipboardList`) sits between "Products" and "Sales".

> [!example]- Files (8)
> - `resources/js/pages/orders/index.tsx`
> - `resources/js/components/orders/orders-table.tsx`
> - `resources/js/components/orders/delete-order-dialog.tsx`
> - `resources/js/components/app-sidebar.tsx`
> - `resources/js/pages/orders/index.test.tsx`
> - `resources/js/components/orders/orders-table.test.tsx`
> - `resources/js/components/orders/delete-order-dialog.test.tsx`
> - `resources/js/components/app-sidebar.test.tsx`

### Contract (frozen before dispatch)
**Routes** (under `auth` + `verified`, `{order}` route-model bound with the digit constraint `[0-9]{1,18}`, `OrderPolicy` on each):
- `GET orders` → `orders.index` → page `orders/index`. Query: `search` (≤ 100 characters), `page`.
- `GET orders/create` → `orders.create` → page `orders/create`.
- `POST orders` → `orders.store` → redirect to `orders.index`, toast "Order recorded." (S-48-c).
- `GET orders/{order}/edit` → `orders.edit` → page `orders/edit`.
- `PUT orders/{order}` → `orders.update` → redirect to `orders.index`, toast "Order updated." (S-48-c).
- `DELETE orders/{order}` → `orders.destroy` → redirect back (keeps the page and the search), toast "Order deleted.".
- No `orders.show` yet: S-49 adds the order's page, and then changes where saving lands.

**Page props:**
- `orders/index`: `{ orders: Paginated<Order>; filters: OrderFilters }`. Newest `ordered_on` first, then highest id; 25 a page,
  `withQueryString()`. Each prop that costs a query is a closure, like the sales page.
- `orders/create`: `{ products: OrderProduct[] }`: the workshop's active, non-deleted products, by name.
- `orders/edit`: `{ order: Order; products: OrderProduct[] }`: the active products plus every product already on this order, whatever its
  status, deleted ones included; by name.

**`resources/js/types/domain.ts`** (added in S-48-b, verbatim):
```ts
/** A product as an order shows and offers it. `deleted` = soft-deleted: shown by name, without a link. */
export type OrderProduct = {
    id: number;
    sku: string;
    name: string;
    status: ProductStatus;
    deleted: boolean;
};

export type OrderLine = {
    id: number;
    product: OrderProduct;
    quantity: number; // whole units, 1 … 1 000 000
};

export type Order = {
    id: number;
    client: string;
    ordered_on: string; // 'YYYY-MM-DD'
    note: string | null;
    lines: OrderLine[]; // in the order entered (position)
    units: number; // sum of the lines' quantities, computed server-side
};

export type OrderFilters = {
    search: string | null; // client, note, or a line's product name or SKU
};

export type OrderLineFormRow = {
    product_id: number | '';
    quantity: string;
};

/** Fields the order form submits. Errors come back as client, ordered_on, note, lines, lines.N.product_id, lines.N.quantity. */
export type OrderFormData = {
    client: string;
    ordered_on: string; // 'YYYY-MM-DD'
    note: string;
    lines: OrderLineFormRow[];
};

export type OrdersIndexProps = { orders: Paginated<Order>; filters: OrderFilters };
export type OrderCreateProps = { products: OrderProduct[] };
export type OrderEditProps = { order: Order; products: OrderProduct[] };
```

**Schema** (S-48-a):
- `orders`: `id`; `workshop_id` (FK → workshops, NOT NULL, `restrictOnDelete`, never mass-assignable); `client` string(100); `ordered_on`
  date; `note` string(1000) nullable; `user_id` (FK → users, nullable, `nullOnDelete`: who recorded it, from the actor); timestamps;
  `deleted_at`. Index `(workshop_id, ordered_on, id)`.
- `order_lines`: `id`; `workshop_id` (as above, taken from the order); `order_id` (FK → orders, `cascadeOnDelete`); `product_id`
  (FK → products, `restrictOnDelete`); `quantity` unsigned integer; `position` unsigned small integer; timestamps.
  `unique(order_id, product_id)`; `index(product_id)`.
- PostgreSQL only (like S-44): `unique(workshop_id, id)` on `orders`; composite foreign keys `order_lines (workshop_id, order_id)` →
  `orders (workshop_id, id)` and `order_lines (workshop_id, product_id)` → `products (workshop_id, id)`; CHECK
  `quantity BETWEEN 1 AND 1000000`.

**PHP signatures:**
- `RecordOrder::handle(array $data, User $actor): Order`, with `$data` = validated `client`, `ordered_on` (Y-m-d), `note`, `lines`
  (list of `product_id`, `quantity`). One transaction.
- `UpdateOrder::handle(Order $order, array $data): Order`. One transaction; the order row is locked (`lockForUpdate`) first.
- `SyncOrderLines::handle(Order $order, array $lines): void`. Matches lines **by product**: a product still listed keeps its line (quantity
  and position updated in place), a new product gets a new line, a product no longer listed loses its line; `position` follows the order
  submitted.
- `DeleteOrder::handle(Order $order): void`: soft delete; the lines stay with it.

**Validation** (`StoreOrderRequest`; `UpdateOrderRequest` reuses its rules with the order's current product ids):
- `client`: required, string, max 100. `ordered_on`: required, `date_format:Y-m-d`, `before_or_equal:today` → "The order date can't be in
  the future.". `note`: nullable, string, max 1000. Attribute names "client", "order date", "note" give the card's default messages.
- `lines`: required, array, min 1 → "Add at least one product."; max 50 → "An order can hold up to 50 products.".
- `lines.*.product_id`: required → "Choose a product."; then a closure (like `StoreSaleRequest::sellableProduct()`), checked in this order:
  the product exists in the workshop (deleted ones only when already on this order), else Laravel's `validation.exists` message, as for an
  id that doesn't exist; it is `active` or already on this order, else "Only active products can be ordered." (draft and archived alike);
  no earlier line has the same product, else "<product name> is already on this order." on the later line only (Laravel's `distinct`
  would flag both lines). These messages come from the server after "Save order"; the form doesn't check duplicates as you choose.
- `lines.*.quantity`: required, integer, min 1, max 1 000 000, one message for all four: "The quantity must be a whole number from 1 to
  1,000,000.".

### Decisions
- **Orders are soft-deleted.** Delete hides the order everywhere (list, search, Edit is a 404) and there is no way back on screen, as the
  card says. The rows stay so that S-53's "Cutting" and "Returned" history entries can keep pointing at the line they came from, and S-53
  AC-6 ("deleting an order returns nothing") needs no special case. Rejected: a hard delete with history entries copying the client and
  product as text, which S-53 would have to retrofit.
- **Lines are matched by product when an order is saved again**, like the composition editor, and one product appears once per order
  (`unique(order_id, product_id)`). This matches S-49's rule that a line that has started keeps its product and can't be removed, and a
  swap of two products between lines can't trip the unique index. Rejected: matching by line id, which needs a two-pass update to swap
  products and gains nothing until S-49.
- **The line is where later stories attach their data.** S-49 adds the stage counts as columns of `order_lines`, and S-53 a record of the
  materials taken per line. Both are additions; nothing in this step changes for them. Queries that total open orders (S-50) must count
  only lines of non-deleted orders: the default scope on `Order` does that when they go through it.
- **No costing maths and no money.** Quantities and the units sum are whole numbers (SQL `SUM` of an integer column, cast to int), so
  nothing here goes near `BigDecimal` or `App\Domain`. The order code never reads or writes `materials`, `stock_movements` or `sales`.
- **Who recorded it** is stored (`user_id`, from the signed-in person, as on sales) but not shown yet.
- **Product picker:** the same Select as "Record a sale", with the label "<name> · <SKU>". A searchable picker for long catalogues is a
  story candidate, not part of this card.
- No ADR: the design follows ADR-0013 and the sales pattern. `docs/ARCHITECTURE.md` gets the two tables, the routes and the screen
  (S-48-a).

### Order
Wave 1 (backend): S-48-a · Wave 2 (backend): S-48-b · Wave 3: S-48-c (backend) in parallel with S-48-e (frontend, needs the routes and
types from S-48-b) · Wave 4 (frontend): S-48-d (needs the store and update routes from S-48-c).

### Risks
- A new table or route forgets the workshop → both models use `BelongsToWorkshop`; the arch test's model and table lists, the route walk
  (404 for another workshop) and the leak scan cover the new pages, and they fail by themselves if a later route isn't mapped.
- The route guards are shared test files (`RoutesTest`, `CrossWorkshopAccessTest`, `WorkshopLeakScanTest`) → they are in S-48-b's and
  S-48-c's file lists, and the developer runs the full suite, not only `tests/Feature/Orders`.
- Inertia refuses a page that has no file yet → S-48-b ships the three page stubs that S-48-d and S-48-e replace.
- The duplicate-product message lands on both lines (Laravel's `distinct`) → a closure that flags only the later line, with a test.
- The units sum comes back as a string on PostgreSQL → cast to int in the resource; the parity suite runs on S-48-a to S-48-c.
- Narrow selects break under strict models (the `photo_url` accessor) → the order resources don't use photos; product selects include
  `deleted_at` and `status`.
- The 1,000-character note test is slow to type and flaky under load (F-22) → the frontend tests paste long text instead of typing it.
- "Today" depends on the time zone → server tests fix the time with `travelTo()`, the form reads `app.today` from the shared props.
- A product archived between opening the form and saving → refused by validation with "Only active products can be ordered."; a
  product archived in the instant between validation and the save gets through (the same tiny race as F-46, accepted).
