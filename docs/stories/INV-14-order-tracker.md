---
id: INV-14
title: Order tracker, covered from the shelf
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
created: 2026-11-03
tags:
  - story
  - variants
---

# INV-14 · Order tracker, covered from the shelf

> [!abstract] Description
> As a **workshop owner**, I want **each order to show how many units are still to deliver, delivered and cancelled, beside what is on
> the shelf now, and to deliver from the shelf or cancel units in a few clicks**, so that **I answer "Where is Boutique Lina's order?"
> in one look, and the order no longer pretends to run production**. Products are made on Progress, for the shelf; an order is served
> from the finished stock.
> New: the **Order tracker** card, the table per product by colour and size with **On the shelf now**, **Mark delivered** (it takes the
> units off the shelf) and its move back, **Cancel units**, the status **Cancelled**. Changed: the order page loses its stages and
> cutting buttons, the Orders list and a client page's orders table get the new words and columns, the Delete order window loses its
> refusal, Edit order counts delivered and cancelled units. The pages are drawn in [[INV-14-order-tracker-ui]].
> Left out on purpose: setting shelf stock aside for an order (the shelf is checked, never held), a history of deliveries (how many,
> not when or by whom), a "Make more" link to Progress, undoing a cancel, cancelling a whole order in one click, closing an order, and
> everything about making products (Progress).

> [!info] How it works today
> Units of an order go through production on the order: its page shows, per colour and size, To start · In cutting · Ready to ship ·
> Shipped with **Start cutting**, **Mark ready**, **Ship** and three **Move back** buttons. Start cutting takes materials out of stock;
> **Ship** never touches the finished stock. Under the title: "In progress" and "0 of 80 shipped". The Orders list shows To start · In
> cutting · Ready to ship · Shipped per order, filters "Open orders · Shipped · All orders", and an order with units in cutting or
> ready to ship can't be deleted ("… Move them back to To start first."). There is no way to say a client dropped part of an order: the
> units stay To start for ever. Meanwhile INV-5 makes products on Progress for the shelf, so a finished product is not tied to an order.

## Acceptance criteria

Today is 30 Oct 2026. **Boutique Lina**'s order of 15 Oct 2026, 80 units. On the shelf (S · M · L · XL): Denim Jacket Indigo 2 · 0 · 3 ·
1; Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0.

| Product · colour · size          | To deliver | Delivered | Cancelled | Total | On the shelf now |
| -------------------------------- | ---------- | --------- | --------- | ----- | ---------------- |
| Denim Jacket · Indigo S          | 10         | 0         | 0         | 10    | 2                |
| Denim Jacket · Indigo M          | 20         | 0         | 0         | 20    | 0                |
| Denim Jacket · Indigo L          | 15         | 0         | 0         | 15    | 3                |
| Denim Jacket · Indigo XL         | 0          | 5         | 0         | 5     | 1                |
| Denim Jeans 5-pocket · Indigo S  | 5          | 0         | 0         | 5     | 0                |
| Denim Jeans 5-pocket · Indigo M  | 10         | 0         | 0         | 10    | 5                |
| Denim Jeans 5-pocket · Indigo L  | 10         | 0         | 0         | 10    | 2                |
| Denim Jeans 5-pocket · Indigo XL | 0          | 0         | 5         | 5     | 0                |
| **Boutique Lina**                | 70         | 5         | 5         | 80    |                  |

The three counts always add up to the Total; Delivered and Cancelled units stay in it. An order is **open** while it has at least one
unit To deliver. Its status: **Cancelled** when every unit is cancelled; **Delivered** when nothing is left to deliver and at least one
unit was delivered; **Not started** while nothing has been delivered; **In progress** when some units are delivered and some are still
to deliver.

- [ ] **AC-1 · The Order tracker.** Under the order's header (breadcrumb, client line, **Edit**, **Delete** as today), the card
  **Order tracker** shows the status badge (**In progress**) and "5 of 80 units delivered · 5 cancelled"; a thin bar whose segments
  follow the counts (Cancelled hatched; the bar is decoration); the list "Units of this order" with four tiles, each read "To deliver,
  70 units": **To deliver** 70 · **Delivered** 5 · **Cancelled** 5 · **Total** 80 ("units ordered"); and the grey line "To deliver:
  ordered, not delivered yet. On the shelf now is your finished stock; it isn't set aside for this order. Delivered and Cancelled
  units stay in the total."
  - All delivered: **Delivered**, "12 of 12 units delivered", the bar all green. All cancelled: **Cancelled**, "0 of 2 units delivered
    · 2 cancelled", the bar all hatched.
  - While it loads, the header and the card frame show at once with a bar and four tile placeholders; if it fails, "Couldn't load the
    tracker of this order." · **Try again**.
  - On a phone: the status line, the bar, **Total** full width, then the three counts two to a row.

- [ ] **AC-2 · Products by colour and size, without production.** One card per product: its name (a link), SKU, "50 units", its
  status badge and "5 of 50 delivered"; then the table "Denim Jacket by colour and size": **Colour · size** · **To deliver** ·
  **Delivered** · **Cancelled** · **Total**, a gap, **On the shelf now**. A colour row (swatch, name, its subtotals, bold, on a light
  band), its sizes under it, a **Total** row; no shelf figure on the colour and Total rows. A "One size" product has colour rows only.
  Zeros in a readable grey; a shelf figure of 0 is the amber word **Out**. Every number comes from the server.
  - Under the table: **Mark delivered** (main when the shelf covers some units to deliver), **Cancel units** (outline, red text) and
    "⋯" named "More actions for Denim Jacket" with "Move delivered back to To deliver".
  - A button that can't be pressed stays reachable by keyboard and says why beside it: **Mark delivered** "Nothing on the shelf covers
    this order yet. Units arrive from Progress or a restock." (or "Nothing is left to deliver.") · **Cancel units** "Nothing to cancel:
    no unit is waiting to be delivered."
  - Gone from the order page: the stage boxes and columns, **Start cutting**, **Mark ready**, **Ship**, the three **Move back**, "0 of
    80 shipped". An order no longer takes or returns materials.
  - On a phone: a band per colour and one line per size with only the counts that hold units and the shelf ("M · To deliver 20 · Total
    20 · On the shelf Out"), the Total line first; the three buttons full width, one under another, 44 px.

- [ ] **AC-3 · Mark delivered and its move back.** **Mark delivered** on the Jacket opens "Mark delivered · Denim Jacket" with
  "Boutique Lina · to deliver and on the shelf: Indigo S 2 · L 3" and "How many units of each colour and size?": a field only where
  something is both to deliver and on the shelf, "Up to 2" under Indigo S and "Up to 3" under Indigo L (the smaller of the two), the
  focus in the first; the note "Delivered units leave your finished stock."; **Mark delivered** and **Cancel**.
  - Typing Indigo S 2 and L 3: "5 units of Denim Jacket marked delivered." ("1 unit of Denim Jacket marked delivered."); To deliver 65 ·
    Delivered 10; the shelf of the Jacket reads Indigo S 0 · L 0, also on its product page.
  - Refused, nothing changed: "Enter at least one unit to mark delivered." · "You can mark at most 2 units of Indigo S: only 2 are on
    the shelf." · "You can mark at most 4 units of Indigo S: only 4 are left to deliver." · "Indigo S: Enter a whole number of units." ·
    when the shelf or the order changed since the page opened so a field no longer fits: "The shelf or this order changed since you
    opened this page." and **Close**. While saving, the button shows a spinner and keeps its label.
  - "Move delivered back to To deliver" opens "Move back to To deliver · Denim Jacket" with "Boutique Lina · delivered: Indigo XL 5",
    the note "These units go back on your shelf." and **Move back**: "5 units of Denim Jacket moved back to To deliver."; the units go
    back on the shelf. Refused: "Enter at least one unit to move back." · "You can move back at most 5 units of Indigo XL." · "Indigo
    XL: Enter a whole number of units."

- [ ] **AC-4 · Cancel units.** **Cancel units** on the Jeans opens "Cancel units · Denim Jeans 5-pocket" with "Boutique Lina · to
  deliver: Indigo S 5 · M 10 · L 10", "How many units of each colour and size?" with "Up to 10" under Indigo M and L, the focus in the
  first; the amber note "Cancelled units stay on the order, marked Cancelled, and are not delivered. This can't be undone. Finished
  stock doesn't change."; **Keep units** (outline) and **Cancel 5 units** (red; the number follows what is typed). The window is the
  confirmation.
  - Typing Indigo L 5: "5 units of Denim Jeans 5-pocket cancelled." ("1 unit of … cancelled."); To deliver 65 · Cancelled 10. No
    stock, material or sale changes.
  - Refused: "Enter at least one unit to cancel." · "You can cancel at most 10 units of Indigo L." · "Indigo L: Enter a whole number
    of units." · units delivered or cancelled meanwhile: "This order changed since you opened this page." and **Close**.
  - On a phone the windows are full screen, the fields three to a row, and their two buttons in a bar fixed at the bottom.

- [ ] **AC-5 · Orders list and client page.** The Orders list shows two totals over every open order, whatever the filters: **Open
  orders** 3 · **To deliver** 73 units. Filters: the search, **Status** ("Open orders" by default, "Delivered", "Cancelled", "All
  orders") and **Client**. Table: **Client** (name, the date in grey under it) · **Products** · **Ordered** (the Total, "5 cancelled" in
  grey under it when there are any) · **To deliver** · **Delivered** · **Status** (badge with an icon): Amira Ben Salah 2 · 2 · 0 Not
  started; Yassine Trabelsi 3 · 1 · 2 In progress; Boutique Lina 80 · 70 · 5 In progress; under "All orders" also Mehdi Jaziri 12 · 0 ·
  12 Delivered and Nour Gharbi 2 · 0 · 0 Cancelled.
  - Empty: "No orders yet" · "Record a client's order to keep track of what you've promised to make." · **New order**. None open: "No
    open orders" · "Every order has been delivered or cancelled." · **Show all orders**. No match: "No orders match your filters" · "Try
    a different client, product or note, or clear the filters to see your open orders." · **Clear filters**.
  - A client page's orders table takes the same columns and words, its Status list "Open orders · Delivered · Cancelled · All orders"
    ("All orders" by default there) and the empty message "Amira Ben Salah has no delivered orders." · **Show all orders**.
  - On a phone: the two totals side by side, the search, **Filters** ("Filters · 1" when set) opening a full-height panel, then one
    card per order with its name, status, date, "5 cancelled", the products, a small bar and "To deliver 70 · Delivered 5".
  - The words To start, In cutting, In production, Ready to ship, Shipped and Ship are gone from orders.

- [ ] **AC-6 · Edit order, Delete order and colours.** In Edit order a cell with delivered or cancelled units shows "At least 10" and
  can't go lower: "Indigo M can't be less than the 10 units already delivered or cancelled."; a line with delivered or cancelled units
  can't be removed: "Denim Jacket can't be removed: 5 units are delivered or cancelled." Delete order is always allowed: "Delete
  order?" · "The order of **Boutique Lina** (2 products, 80 units) will be removed. Material stock and sales are not affected. This
  cannot be undone." and, when units were delivered, "Finished stock isn't changed: the 5 units already delivered are not put back."
  ("the 1 unit already delivered is not put back") · **Cancel** · **Delete order** · "Order deleted.". In Edit product, a colour with
  units To deliver on open orders can't be taken off its product ("Indigo has 12 units on open orders, so it can't be removed from
  Denim Jacket.", as INV-13 built it); Delivered and Cancelled units don't count.

- [ ] **AC-7 · Orders recorded before.** Every order keeps its Total per colour and size. Units that were **Shipped** become
  **Delivered** (the shelf doesn't change); units **To start**, **In cutting** or **Ready to ship** become **To deliver**. Units that
  were **Ready to ship** are first put on the shelf, in their colour and size, as one restock dated the day of the change with
  Reference "Orders" and Note "Ready to ship before INV-14", so they can be marked delivered at once. Materials already taken by past
  cuts stay as they are in the materials' history. Boutique Lina's order with 5 Jackets Indigo XL shipped and 10 Indigo S ready to
  ship reads Delivered 5 and To deliver 75, and the shelf of Indigo S grows by 10.


---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 8 steps
> Tech-lead · 2026-11-03 · approved 2026-11-03 · delivered 2026-11-03
> Why complex: a migration that rewrites every order's counts and puts units on the shelf, a business-rule change (orders stop
> cutting, delivering moves finished stock), new writers of the shelf, and a new contract for the order pages.

### In short
Each order row stops counting In cutting, Ready to ship and Shipped and counts **Delivered** and **Cancelled** instead; To deliver is
what is left of the quantity. The migration turns Shipped into Delivered and, per workshop, puts the Ready-to-ship units on the shelf
as one restock "Orders". All the cutting code leaves the orders (moves, previews, cuts, the delete refusal); the cuts already recorded
stay in the materials' history. Three new writers — Mark delivered, its move back, Cancel units — work per colour and size under the
same locks as restocks, and Mark delivered takes units off the shelf. The order page gets a deferred **tracker** prop (counts, rows
per colour and size, the shelf now), and the list, the client page and the windows are redrawn.

### Steps

| Step         | What it does                                                   | Proves          | Lane         | Who      | Size       |
| ------------ | -------------------------------------------------------------- | --------------- | ------------ | -------- | ---------- |
| **INV-14-a** | Delivered and Cancelled replace the stages; old orders mapped  | AC-7            | CRITICAL     | Backend  | ~400 lines |
| **INV-14-b** | Cutting leaves the orders; Delete order always allowed         | AC-2, AC-6      | CRITICAL     | Backend  | ~150 lines |
| **INV-14-c** | The tracker and the lists' figures, statuses and filters       | AC-1, AC-2, AC-5 | CRITICAL    | Backend  | ~400 lines |
| **INV-14-d** | Mark delivered, its move back and Cancel units                 | AC-3, AC-4      | CRITICAL     | Backend  | ~450 lines |
| **INV-14-e** | Edit order's floors and the colour lock count delivered units  | AC-6            | CRITICAL     | Backend  | ~100 lines |
| **INV-14-f** | The order page: tracker card and products by colour and size   | AC-1, AC-2      | CRITICAL     | Frontend | ~450 lines |
| **INV-14-g** | The three windows                                              | AC-3, AC-4      | CRITICAL     | Frontend | ~350 lines |
| **INV-14-h** | Orders list, client page, Delete order, Edit order wording     | AC-5, AC-6      | NON-CRITICAL | Frontend | ~350 lines |

`composer test` stays green after every backend step; the frontend follows in wave 2 (between the waves the screens' types lag, so
the commits are grouped at delivery). After INV-14-h the whole tree runs every gate and `composer test:pgsql`.

#### INV-14-a · Delivered and Cancelled replace the stages; old orders mapped
**Commit:** `feat(orders): orders count delivered and cancelled units (INV-14-a)`

- Migration `count_delivered_and_cancelled_on_orders`, `DB::table` only: `order_lines` and `order_line_sizes` gain `delivered` and
  `cancelled` (unsigned, default 0); `delivered = shipped`. For each workshop with Ready-to-ship rows, one `product_restocks` row
  (restocked today, Reference "Orders", Note "Ready to ship before INV-14", `units` the sum, no user) and one line per product, colour
  and size, then `product_size_stocks` gains those units (a row inserted when there is none; the colour linked to the product when it
  isn't, as the PostgreSQL key needs). A variant that would pass 1,000,000 stops the migration with its product, colour and size named.
  Then `in_cutting`, `ready_to_ship` and `shipped` go, with their PostgreSQL checks; new checks: both ≥ 0 and `delivered + cancelled ≤
  quantity`. `down()` brings the three columns back with `shipped = delivered` (the restock stays: it is real stock now).
- `HasStageCounts` becomes `HasDeliveryCounts`: `to_deliver = quantity − delivered − cancelled`, `settled = delivered + cancelled`.
  `OrderProgress`: `not_started`, `in_progress`, `delivered`, `cancelled` (the card's rules, on a line and on an order).
  `OrderStatusFilter`: `open`, `delivered`, `cancelled`, `all`; scopes `open()` (a row with something to deliver), `delivered()`,
  `cancelled()`. `Order::units*()` become `unitsToDeliver()`, `unitsDelivered()`, `unitsCancelled()`.
- The hand-kept migration list (F-60) gains it. The tech-lead writes ADR-0026 "Orders are served from the shelf" (supersedes the
  order parts of ADR-0014 and ADR-0016's stages) and updates `docs/ARCHITECTURE.md`.

**Tests**
- Migration: Shipped → Delivered; To start, In cutting, Ready to ship → To deliver; the restock "Orders" per workshop with its lines and
  the shelf grown (a new row, an existing row, a colour linked); two workshops kept apart; nothing Ready to ship → no restock; the
  1,000,000 stop; `down()`. Statuses and filters per the card's five orders. PostgreSQL: the checks.

> [!example]- Files (≈12)
> - `database/migrations/2026_11_07_100000_count_delivered_and_cancelled_on_orders.php` (new)
> - `app/Models/{Order,OrderLine,OrderLineSize}.php`, `app/Models/Concerns/HasDeliveryCounts.php` (replaces `HasStageCounts`)
> - `app/Enums/{OrderProgress,OrderStatusFilter}.php`, `database/factories/{OrderLineFactory,OrderLineSizeFactory}.php`
> - `docs/adr/0026-orders-are-served-from-the-shelf.md` (new), `docs/ARCHITECTURE.md`
> - `tests/Feature/Orders/DeliveryCountsMigrationTest.php` (new), `tests/Pest.php`

#### INV-14-b · Cutting leaves the orders; Delete order always allowed
**Commit:** `refactor(orders): orders no longer cut or hold materials (INV-14-b)`

- Removed: `MoveOrderLineUnits`, `TakeMaterialsForCut`, `ReturnMaterialsFromCuts`, `CuttingPreview`, `OrderLineMoveController`,
  `OrderLineMovePreviewController`, their requests, `MovePreviewResource`, the enums `OrderLineMove` and `OrderStage`, the routes
  `orders.lines.moves.*`, and their tests. The tables `order_line_cuts` and `order_line_cut_materials` and the cutting entries of the
  stock history stay (history); `OrderLine::cuts()` stays read-only for the material history.
- `DeleteOrder` loses its refusal and `OrderDeleteRefusedException`; `OrderResource` loses `delete_refusal` and gains `delivered`
  (for the confirm's sentence).

**Tests**
- Deleting an order with delivered units: allowed, the shelf, materials and sales unchanged. The old move routes are 404. The
  material history still reads a past cut "Cut for Boutique Lina".

> [!example]- Files (≈−20, +4)
> - the removed files above; `app/Actions/Orders/DeleteOrder.php`, `app/Http/Controllers/OrderController.php`, `routes/web.php`
> - `tests/Feature/Orders/{DeleteOrderTest,…}.php`

#### INV-14-c · The tracker and the lists' figures, statuses and filters
**Commit:** `feat(orders): the order tracker with the shelf now, and the lists' new figures (INV-14-c)`

- `App\Services\Orders\OrderTracker::of(Order)`: the order's counts and status; per line, its counts, status and rows by colour (list
  order) then size, each colour with its subtotals, each size row with `to_deliver`, `delivered`, `cancelled`, `total` and `on_shelf`
  (`FinishedStock::ofMany` for every product of the order, one query), plus what each window needs: per row the "Up to" of Mark
  delivered (`min(to_deliver, on_shelf)`), of Move back (`delivered`) and of Cancel (`to_deliver`). Queries flat for 50 lines.
- `orders.show`: `order` (header) as today; `tracker` deferred (`Inertia::defer`), so the card frame shows at once and a failure is
  rescued with "Try again".
- `OrderResource` / `OrderLineResource` / `OrderLineSizeResource`: `to_deliver`, `delivered`, `cancelled` (the stages go).
  `OrderBook::openTotals()`: `{ open_orders, to_deliver }`. The client page's filter default stays "All orders".

**Tests**
- The card's Boutique Lina: every count, subtotal, status and shelf figure; "Up to" per row; a "One size" product; the five orders'
  statuses and filters; totals over open orders only, whatever the filter; the query budgets of the list, the order page and the
  deferred tracker.

> [!example]- Files (≈10)
> - `app/Services/Orders/{OrderTracker,OrderBook}.php`, `app/Http/Resources/{OrderTrackerResource,OrderResource,OrderLineResource,OrderLineSizeResource}.php`
> - `app/Http/Controllers/OrderController.php`, `resources/js/types/domain.ts`
> - `tests/Feature/Orders/{OrderTrackerTest,OrderPageTest,OrdersListTest}.php`, `tests/Feature/Performance/QueryBudgetTest.php`

#### INV-14-d · Mark delivered, its move back and Cancel units
**Commit:** `feat(orders): mark units delivered from the shelf, move them back, or cancel them (INV-14-d)`

- Routes (POST, `update` policy, scoped bindings): `orders.lines.deliveries.store` (Mark delivered), `orders.lines.deliveries.destroy`
  (Move back), `orders.lines.cancellations.store` (Cancel). Each takes `cells` (colour id → size id → units) like INV-13's moves; every
  cell must be a row of the line, at least one unit in all.
- `DeliverOrderUnits`, `ReturnDeliveredUnits`, `CancelOrderUnits`, one transaction each. Lock order (as Edit order and restocks): the
  order row → the line's product → the line → its rows → the shelf rows. Deliver checks each cell against `to_deliver` and the shelf
  read under the lock, takes the units off the shelf (a row at 0 is deleted, ADR-0017) and adds them to `delivered`; Move back takes
  them off `delivered` and puts them on the shelf (refused past 1,000,000 with INV-3's sentence); Cancel adds to `cancelled` only.
  The line's counts stay the sums of its rows. No stock history entry and no restock: the shelf is the only trace (the card).
- `OrderMessages` gets every sentence of AC-3 and AC-4; a cell that is no longer a row, or holds nothing to deliver or move back any
  more, refuses with "The shelf or this order changed since you opened this page." / "This order changed since you opened this
  page." under `cells`.

**Tests**
- AC-3: Indigo S 2 and L 3 delivered: the counts, the toast, the shelf rows (0 rows deleted), the product page's stock; each refusal
  and nothing written; the shelf emptied by a sale in between; a deleted product (its shelf is gone: refused). Move back: the counts,
  the shelf, the 1,000,000 limit. AC-4: Indigo L 5 cancelled, no stock touched; each refusal. Another workshop's order or line: 404.
  An undone restock check still sees the lowered shelf (INV-3's sentence).

> [!example]- Files (≈10)
> - `app/Actions/Orders/{DeliverOrderUnits,ReturnDeliveredUnits,CancelOrderUnits}.php` (new), `app/Http/Requests/Orders/OrderCellsRequest.php` (new)
> - `app/Http/Controllers/{OrderDeliveryController,OrderCancellationController}.php` (new), `app/Services/Orders/OrderMessages.php`, `routes/web.php`
> - `tests/Feature/Orders/{DeliverOrderUnitsTest,CancelOrderUnitsTest}.php` (new)

#### INV-14-e · Edit order's floors and the colour lock count delivered units
**Commit:** `feat(orders): Edit order keeps delivered and cancelled units (INV-14-e)`

- `SyncOrderLines::refusals()` reads `settled` (delivered + cancelled) instead of `started`: "Indigo M can't be less than the 10 units
  already delivered or cancelled." and "Denim Jacket can't be removed: 5 units are delivered or cancelled." `ProductColourLock` counts
  `quantity − delivered − cancelled` (To deliver).

**Tests**
- The floors and the removal refusal with delivered, with cancelled, under the lock; a colour with only delivered or cancelled units
  comes off; with units To deliver it doesn't.

> [!example]- Files (≈5)
> - `app/Actions/Orders/SyncOrderLines.php`, `app/Services/Orders/OrderMessages.php`, `app/Services/Products/ProductColourLock.php`
> - `tests/Feature/Orders/{EditStartedOrderTest,OrderSizesWriteTest}.php`, `tests/Feature/Products/ProductColourLockTest.php`

#### INV-14-f · The order page: tracker card and products by colour and size
**Commit:** `feat(orders): the order tracker and each product by colour and size on the order page (INV-14-f)`

- `components/orders/order-tracker.tsx`: heading, status badge, "5 of 80 units delivered · 5 cancelled", the bar (aria-hidden; Cancelled
  hatched), the list "Units of this order" with the four tiles, the grey line; the loading placeholders and the error with **Try again**
  (the deferred prop's rescue).
- `order-product-card.tsx` replaces `order-line-card.tsx`: name (link), SKU, "50 units", status, "5 of 50 delivered", the table by colour
  and size (colour bands, size rows, Total, the gap, **On the shelf now**, the amber **Out**, a labelled region that scrolls), the three
  buttons with their reasons, the "⋯" menu. Phone: bands and one line per size, buttons full width. `size-stages-table`,
  `stage-figure`, `move-button`, `move-units-dialog`, `materials-move-table` and `use-move-preview` go.

**Tests**
- Vitest: the card's tracker (tiles read "To deliver, 70 units"), all delivered, all cancelled, loading, error; the Jacket's table row
  by row; One size; the reasons of the disabled buttons; the phone lines; nothing of the old stages on the page.

> [!example]- Files (≈10)
> - `resources/js/components/orders/{order-tracker,order-product-card}.tsx` (new, + tests), `resources/js/pages/orders/show.tsx` (+ test)
> - the removed components and their tests

#### INV-14-g · The three windows
**Commit:** `feat(orders): Mark delivered, Move back and Cancel units windows (INV-14-g)`

- `components/orders/order-units-dialog.tsx`, one window in three modes (deliver · move back · cancel): title, the grey line, "How many
  units of each colour and size?" with a field per row that has an "Up to" above 0, the "Up to" under it, the note (amber for Cancel),
  the buttons (**Mark delivered** / **Move back** / **Cancel N units** in red, with **Cancel** / **Keep units**), the server's messages
  under their fields, the "changed" message with **Close**, the spinner; full screen on a phone with the fields three to a row and the
  buttons in a fixed bar.

**Tests**
- Vitest: each mode's title, line, fields and "Up to", the payload by colour and size, "Cancel 5 units" following the typing, each
  server message, the changed message and Close, the focus in the first field, the phone layout.

> [!example]- Files (≈3)
> - `resources/js/components/orders/order-units-dialog.tsx` (new, + test), `resources/js/components/orders/order-product-card.tsx`

#### INV-14-h · Orders list, client page, Delete order, Edit order wording
**Commit:** `feat(orders): To deliver and Delivered on the Orders list and the client page (INV-14-h)`

- `open-order-totals.tsx` (Open orders · To deliver), `orders-table.tsx` (Client with the date · Products · Ordered with "5 cancelled" ·
  To deliver · Delivered · Status; the phone cards with the small bar), `orders/index.tsx` (the statuses and empty states),
  `clients/show.tsx` (its filter and "has no delivered orders."), `delete-order-dialog.tsx` (the confirm and its sentence, no refusal),
  Edit order's "At least 10" from `delivered + cancelled`.

**Tests**
- Vitest: the five orders' rows and statuses, the totals, each empty state, the client page's filter and empty line, the confirm with
  and without delivered units, Edit order's floors.

> [!example]- Files (≈10)
> - `resources/js/components/orders/{open-order-totals,orders-table,delete-order-dialog}.tsx` (+ tests), `resources/js/pages/orders/index.tsx` (+ test)
> - `resources/js/pages/clients/show.tsx` (+ test), `resources/js/components/orders/order-form.tsx`

### Contract (frozen before dispatch)
```ts
export type OrderProgress = 'not_started' | 'in_progress' | 'delivered' | 'cancelled';
export type OrderStatusFilter = 'open' | 'delivered' | 'cancelled' | 'all';
// Order, OrderLine, OrderLineSize: − to_start, in_cutting, ready_to_ship, shipped, started; + to_deliver, delivered, cancelled
// Order: − delete_refusal
export type OpenOrderTotals = { open_orders: number; to_deliver: number };
export type TrackerCounts = { to_deliver: number; delivered: number; cancelled: number; total: number };
export type TrackerRow = TrackerCounts & { size: Size; on_shelf: number; can_deliver: number };  // can_deliver = min(to_deliver, on_shelf)
export type TrackerColour = TrackerCounts & { colour: Colour; rows: TrackerRow[] };
export type TrackerLine = TrackerCounts & { id: number; product: OrderProduct; one_size: boolean; status: OrderProgress;
    status_label: string; colours: TrackerColour[] };
export type OrderTracker = TrackerCounts & { status: OrderProgress; status_label: string; lines: TrackerLine[] };
export type OrderShowProps = { order: Order; tracker?: OrderTracker };   // deferred
export type OrderCellsFormData = { cells: Record<number, Record<number, string>> };
```
Routes: `orders.lines.deliveries.store` POST orders/{order}/lines/{line}/deliveries · `orders.lines.deliveries.destroy` POST
orders/{order}/lines/{line}/deliveries/back · `orders.lines.cancellations.store` POST orders/{order}/lines/{line}/cancellations.
Errors: `cells`, `cells.<colour id>.<size id>`. Toasts from the server's flash.

### Decisions
- **Columns, not a table of deliveries:** the card asks how many, not when or by whom; two counts per row keep the sums rule of
  ADR-0016 and every list query as it is.
- **No stock history entry for a delivery:** finished stock has no history today (ADR-0017); the shelf row is the trace, as the card
  says ("The product page keeps no trail of deliveries").
- **The Ready-to-ship units become a real restock**, so they can be undone like any restock if the human finds them wrong (the
  human's answer 1b).
- **The cuts stay:** they are the materials' history; nothing reads them for orders any more.
- **The tracker is deferred:** it reads the shelf of every product of the order; the card's loading and error states are Inertia's
  deferred prop and its rescue.

### Order
Wave 1 (backend, one after another): INV-14-a → INV-14-b → INV-14-c → INV-14-d → INV-14-e. Wave 2 (frontend, after wave 1's types
land): INV-14-f → INV-14-g, in parallel with INV-14-h.

### Risks
- **The migration writes stock** → `DB::table` only, one restock per workshop, the 1,000,000 stop named, a test per path; it runs on
  the dev database with `php artisan migrate` only.
- **Two writers of the shelf meet** (a restock undo, Update stock, Put in stock, a delivery) → every writer locks the product row
  first; the delivery re-reads the shelf under that lock.
- **A big removal** → the removed code goes in its own step (INV-14-b) with its tests, so a reviewer sees it at once.
- **Size L** → kept as one card (the human's answer 2); the steps keep each review small.

### Delivered differently from the plan
- **One Action for the three windows:** `ChangeOrderUnits` with an `OrderUnitsChange` (deliver · move back · cancel) instead of three
  Actions, and one request, `OrderCellsRequest`; the routes, locks and messages are those of the plan.
- **`CuttingPreview` stays, trimmed to `plan()`**: Start batch (INV-5) uses it. Its order parts, the move code, the previews, the enums
  `OrderLineMove` and `OrderStage`, `OrderDeleteRefusedException` and their tests are gone (INV-14-b). `stage-figure.tsx` keeps only
  `formatUnits`.
- **The order header loses its status line**: the status lives in the Order tracker card (AC-1). The Orders list's date moves under the
  client's name (the card); the client page keeps its "Order date" column.
- **The phone's Filters panel** (AC-5) is a bottom sheet at full height holding Status and Client; the search stays above it.
- **A line recorded before sizes can't be delivered**: it names "One size" for a product with sizes, so no shelf row matches it (ADR-0026).
  Edit order keeps it on "One size" (INV-13); the owner can remove it and add the product again with real sizes when nothing is
  delivered or cancelled on it.
- **Tests' helpers:** `bookOrderWithCounts()` and `bookDeliveredOrder()` replace `bookOrderAtStages()` and `bookShippedOrder()`; the
  factory's `counts()`, `delivered()` and `cancelled()` replace `stages()`, `inCutting()`, `readyToShip()` and `shipped()`; the
  hand-kept migration list gains `deliveryCountsMigration()`.
- **Wording the card doesn't give, written here:** "Loading the tracker…" (screen readers), the menu's "More" label on a phone, the
  tiles' word "units ordered" under Total, "Enter at least one unit to move back." / "… to cancel." (the card gives them), and
  "You can't mark any unit of Indigo S: none is on the shelf." for a field emptied meanwhile (shown as "The shelf or this order
  changed since you opened this page." in practice, since such a cell refuses the window as a whole).

### Delivery report (2026-11-03)
Gates on this machine, final tree: `composer test` green (Pint, PHPStan, 2,989 Pest tests: 2,916 green, 73 skipped); `npx vp test`
green (125 files, 1,455 tests); `npm run check`, `npm run types:check` and `npm run build` green; `composer test:pgsql` 2,989 of 2,989.
Not run: the browser golden path (Playwright is outdated on this machine).

| AC | Evidence |
| --- | --- |
| AC-1 · Order tracker | `OrderTrackerTest` (counts, status, rows); `OrderPageTest` (statuses: not started, cancelled only, in progress, delivered, delivered and cancelled, all cancelled); `show.test.tsx` (tiles read "To deliver, 70 units", the line, loading, error and Try again); `order-tracker.test.tsx` (all delivered, all cancelled, the bar); `QueryBudgetTest` (deferred tracker) |
| AC-2 · Products by colour and size | `OrderTrackerTest` (rows, shelf, `can_deliver`); `show.test.tsx` (the table row by row, Out, the reasons beside disabled buttons, the phone lines, no cutting buttons); `order-tracker.test.tsx` (One size, zeros, Out); `OrdersNeverTouchStockTest` (no material, history or cut touched; the old routes 404) |
| AC-3 · Mark delivered and move back | `OrderTrackerTest` (Indigo S 2 and L 3, the shelf rows, the product page, each refusal, the singular, move back with its shelf, a row made again, 1,000,000, a restock no longer undoable); `show.test.tsx` (the window, fields, Up to, focus, payload, messages, Close, the menu's move back) |
| AC-4 · Cancel units | `OrderTrackerTest` (Indigo L 5, no stock or restock touched, refusals, another workshop 404); `show.test.tsx` (the amber note, Keep units, "Cancel 5 units") |
| AC-5 · Orders list and client page | `OpenOrderTotalsTest`, `OrdersIndexTest` (statuses and filters of the five orders), `ClientPageTest`, `OrderClientTest`; `orders-table.test.tsx` (columns, "5 cancelled", the phone card), `open-order-totals.test.tsx`, `index.test.tsx` (empty states, Filters · 1), `clients/show.test.tsx` |
| AC-6 · Edit order, Delete order, colours | `EditStartedOrderTest`, `OrderSizesWriteTest` (floors and removal with delivered and cancelled, under the lock); `OrdersNeverTouchStockTest` (delete always allowed, nothing put back); `ProductColourLockTest` (units To deliver only); `delete-order-dialog.test.tsx`, `edit.test.tsx` |
| AC-7 · Orders recorded before | `DeliveryCountsMigrationTest` (Shipped → Delivered, the rest To deliver, the restock "Orders" per workshop, a shelf row made and a colour linked, the 1,000,000 stop, `down()`, PostgreSQL checks) |

