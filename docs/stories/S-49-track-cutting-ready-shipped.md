---
id: S-49
title: Track each product of an order through cutting, ready to ship and shipped
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

# S-49 · Track each product of an order through cutting, ready to ship and shipped

> [!abstract] Description
> As a **workshop owner**, I want **to record, for each product of an order, how many units have gone into cutting, how many are ready to
> ship and how many have shipped**, so that **I know how far every order has got and how much is still to make**.
> Each order gets its own page. Every product line has four counts that always add up to its quantity, and you update them as the work
> moves on the floor. The order's status follows its lines. Taking the materials out of stock when cutting starts is S-53. Totals across
> all orders, on the Orders list, are S-50.

> [!info] How it works today
> Since S-48 you can record a client's order, but not follow it. The Orders list shows Boutique Lina's order as "15 Oct 2026 · Boutique
> Lina · 50 × Denim Jacket, 30 × Denim Jeans 5-pocket · 80" with an Edit and a Delete button, and saving the order brings you back to that
> list with "Order recorded.". An order has no page of its own, and nothing says how many jackets are being cut, are ready or have left:
> that is known only on the workshop floor. Denim Jacket's page shows "Can produce now 112": what the stock allows, not what is being made.

## Acceptance criteria
- [ ] **AC-1 · The order page.** Clicking "Boutique Lina" on the Orders list opens the order's page, and so does saving the order ("Order
  recorded." or "Order updated."). It shows the heading "Boutique Lina", under it "Ordered 15 Oct 2026 · 2 products · 80 units", the
  status "Not started", the line "0 of 80 shipped", the client's note under "Note", and the buttons "Edit" and "Delete". Under "Products",
  each line has its own block: "Denim Jacket · PRD-JKT-001 · 50 units" with four figures in this order, "To start 50 units", "In cutting 0
  units", "Ready to ship 0 units" and "Shipped 0 units", and the line "0 of 50 shipped". The same goes for "Denim Jeans 5-pocket · 30
  units". A line's four figures always add up to its quantity; "To start" is whatever has not moved yet.
- [ ] **AC-2 · Move units on, line by line.** On each line, "Start cutting" (under To start), "Mark ready" (under In cutting) and "Ship"
  (under Ready to ship) open a small window titled with the action and the product ("Start cutting · Denim Jacket"), asking "How many
  units?" with the hint "Up to N units." (N is what the stage holds) and the buttons "Move units" and "Cancel". On Boutique Lina's order:
  - "Start cutting" on Denim Jacket with 30 shows "30 units of Denim Jacket moved to In cutting." Denim Jacket reads To start 20 · In
    cutting 30 · Ready to ship 0 · Shipped 0, Denim Jeans 5-pocket still reads 30 · 0 · 0 · 0, and the order's status becomes "In
    progress".
  - Then "Mark ready" on Denim Jacket with 10 shows "10 units of Denim Jacket moved to Ready to ship." and Denim Jacket reads 20 · 20 · 10
    · 0.
  - A button whose stage holds 0 units can't be clicked (here "Ship" on Denim Jeans 5-pocket). One unit reads "1 unit of … moved to …".
  - A product archived or set back to draft since the order was taken moves as usual. A line whose product was deleted can't start
    cutting: "Start cutting" can't be clicked, with the hint "Denim Jacket was deleted, so it can't be cut."; "Mark ready", "Ship" and
    "Move back" still work for its units.
- [ ] **AC-3 · Ship, and the statuses.** From 20 · 20 · 10 · 0, "Ship" on Denim Jacket with 10 shows "10 units of Denim Jacket moved to
  Shipped.", the line reads 20 · 20 · 0 · 10 and "10 of 50 shipped", and the order reads "10 of 80 shipped". Each line has its own status
  ("Not started", "In progress" or "Shipped"), shown as a word with an icon, never by colour alone. The order is "Not started" while no
  unit of any line has left To start, "Shipped" once every line is fully shipped, and "In progress" otherwise. On a fully shipped line,
  "Start cutting", "Mark ready" and "Ship" can't be clicked.
- [ ] **AC-4 · A move can't take more than the stage holds.** With 20 Denim Jackets in cutting, "Mark ready" with 25 shows "You can move at
  most 20 units." and nothing moves. Empty, 0 or 2.5 shows "Enter a whole number of units, at least 1.". If someone else moved units of the
  same line in the meantime, the limit is the count saved now, not the one on your screen, and the figures refresh to it.
- [ ] **AC-5 · Correct a mistake.** In cutting, Ready to ship and Shipped each offer "Move back", which moves units of that line to the stage
  before ("Move back to To start · Denim Jacket"). With To start 20 · In cutting 20, "Move back" on In cutting with 5 shows "5 units of
  Denim Jacket moved back to To start." and the line reads To start 25 · In cutting 15. The same limits and messages as AC-4 apply. A
  shipped line, or order, that gets units moved back is "In progress" again.
- [ ] **AC-6 · Changing an order that has started.** In "Edit order", with 30 Denim Jackets in cutting, ready to ship or shipped (20 · 20 ·
  10 · 0):
  - Denim Jacket's quantity 25 shows "The quantity can't be less than the 30 units already in cutting, ready to ship or shipped." and
    nothing is saved. Quantity 40 is saved, and its To start becomes 10.
  - Denim Jacket's product can't be changed (hint "The product can't change once work on this line has started.") and its "Remove"
    button can't be clicked, with the hint "Denim Jacket can't be removed: 30 units have started.". If such a change reaches Save anyway
    (someone else started the line meanwhile), nothing is saved and the same message shows under the products. Lines that haven't
    started, and new lines, can be changed freely.
  - A fully shipped line whose quantity is raised from 50 to 60 shows To start 10 and is "In progress" again.
  - An order with units in cutting or ready to ship can't be deleted. "Delete", on the order's page or the Orders list, opens the
    "Delete order?" window, which shows "Boutique Lina can't be deleted: 30 units are in cutting or ready to ship. Move them back to To
    start first." in place of its question, with only a "Close" button; nothing is deleted. If someone else started cutting while you
    confirmed, the same text shows as an error message and nothing is deleted. An order whose units are only to start or shipped deletes
    as today, after "Delete order?"; deleting it from the order's page brings you back to the Orders list with "Order deleted.".
  - With one unit, the messages read "1 unit is in cutting or ready to ship", "Denim Jacket can't be removed: 1 unit has started." and
    "The quantity can't be less than the 1 unit already in cutting, ready to ship or shipped.".
- [ ] **AC-7 · Ready and shipped are counts.** "Mark ready", "Ship" and their "Move back" never change material stock and never record a
  sale: after 10 Denim Jackets have shipped, the Sales page and the dashboard show no new sale.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 5 commits (one build with F-41 and S-53: 12 commits)
> Tech-lead · 2026-10-16 · approved 2026-10-16 (revised) · delivered 2026-10-16
> Why complex: new columns on order lines, new business rules on moving units, on editing a started order and on deleting one, a new
> page and new routes (a new contract), and two people can move units of the same line at once. It ships with S-53 in one build (D-11).

### In short
Each order line gets three counters: In cutting, Ready to ship and Shipped. To start is never stored; it is the quantity minus the other
three, so the four figures can't stop adding up. The statuses ("Not started", "In progress", "Shipped") are worked out from those
counters on the server and sent to the screen with their words. Every move goes through one server action that locks the order and the
line while it checks and writes, so the limit it applies is always the count saved at that moment, never the one on someone's screen;
when a move is refused, the page reloads its figures. "Edit order" re-checks the "already started" rules under the same lock, and
"Delete" re-checks that no unit is in cutting or ready to ship, so someone starting to cut while another person deletes can't lose
work. The server comes first (counters and the order page's data, then the moves, then the edit and delete rules), and the screens
follow. The stock side of "Start
cutting" and "Move back to To start" is S-53, built in the same branch right after: until then, those two moves change only the counters.

### Steps

| Step       | What it does                                      | Proves                       | Lane     | Who      | Size       |
| ---------- | ------------------------------------------------- | ---------------------------- | -------- | -------- | ---------- |
| **S-49-a** | Stage counts on each line; the order page's data  | AC-1, AC-3, AC-6             | CRITICAL | Backend  | ~280 lines |
| **S-49-b** | Moving units on and back, with the limits         | AC-2, AC-3, AC-4, AC-5, AC-7 | CRITICAL | Backend  | ~300 lines |
| **S-49-c** | Editing and deleting an order that has started    | AC-6                         | CRITICAL | Backend  | ~240 lines |
| **S-49-d** | The order page and the move window                | AC-1, AC-2, AC-3, AC-4, AC-5 | CRITICAL | Frontend | ~410 lines |
| **S-49-e** | Edit order and Delete order once work has started | AC-6                         | CRITICAL | Frontend | ~220 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-49-a to
S-49-c also run `composer test:pgsql` (new columns, locks, queries); S-49-d also runs `composer test:browser` (the order golden path
changes: saving now lands on the order's page).

#### S-49-a · Stage counts on each line; the order page's data
**Commit:** `feat(orders): stage counts on order lines and the order page (S-49-a)`

**Tests**
- 1 migration test: the three columns exist with default 0 on existing lines, and `down()` drops them. PostgreSQL only: the CHECK
  refuses a negative count and counts that add up to more than the quantity.
- Order page (AC-1): one `assertInertia` shape for Boutique Lina (client, date, 2 products, 80 units, status `not_started` "Not started",
  0 shipped, the note; the lines 50 · 0 · 0 · 0 and 30 · 0 · 0 · 0, each with its product, SKU and status).
- Statuses (AC-3), one dataset over a line and its order: everything To start → Not started; any unit moved → In progress; every line
  fully shipped → Shipped; one line shipped and one not → In progress.
- Saving lands on the page (AC-1): saving a new order and saving an edit redirect to `orders.show` with "Order recorded." / "Order
  updated." (the S-48 tests that expect the list are updated). Deleting an order with nothing in cutting or ready to ship from the
  order page lands on the Orders list with "Order deleted." (AC-6); deleting from the list still goes back to the same page and
  search.
- 1 query-budget row for the order page (an order of 5 lines).
- The existing route guards pick up `orders.show`: guest redirect, another workshop's order is 404, the leak scan walks the page.

> [!example]- Files (20)
> - `app/Enums/{OrderStage,OrderProgress}.php`
> - `app/Models/OrderLine.php` (counts, `to_start`, `started`, `progress()`)
> - `app/Models/Order.php` (`progress()` from its lines)
> - `app/Http/Controllers/OrderController.php` (show; the store, update and destroy redirects)
> - `app/Http/Resources/{Order,OrderLine}Resource.php`
> - `routes/web.php` (`show`)
> - `database/migrations/2026_10_16_100000_add_stage_counts_to_order_lines.php`
> - `database/factories/OrderLineFactory.php` (states for started lines)
> - `resources/js/types/domain.ts` (the order part of the contract below)
> - `resources/js/pages/orders/show.tsx` (stub, so the server tests can render the page)
> - `tests/Feature/Orders/{OrderPageTest,OrderSchemaTest,OrderWriteTest}.php`
> - `tests/Feature/Performance/QueryBudgetTest.php` (1 row)
> - `tests/Feature/Routes/RoutesTest.php`
> - `tests/Feature/Workshops/{CrossWorkshopAccessTest,WorkshopLeakScanTest}.php`
> - `tests/Pest.php` (the hand-kept rollback list next to `ordersMigration()`, F-60)

#### S-49-b · Moving units on and back, with the limits
**Commit:** `feat(orders): move units of an order line between stages (S-49-b)`

**Tests**
- Moves on (AC-2, AC-3), one test walking the card: Start cutting 30 → "30 units of Denim Jacket moved to In cutting.", 20 · 30 · 0 · 0,
  Denim Jeans 5-pocket untouched, the order In progress; Mark ready 10 → 20 · 20 · 10 · 0; Ship 10 → 20 · 20 · 0 · 10, the line "10 of
  50 shipped" and the order "10 of 80 shipped"; one unit reads "1 unit of … moved to …".
- Product status (AC-2): a line whose product was archived or set back to draft moves as usual; on a deleted product, Start cutting is
  refused with "Denim Jacket was deleted, so it can't be cut." under `units` (nothing moves), while Mark ready, Ship and the three moves
  back work.
- Moves back (AC-5), a dataset of the three: from 20 · 20 · 10 · 0, back 5 from In cutting → 25 · 15 · 10 · 0 and "5 units of Denim
  Jacket moved back to To start."; the same for Ready to ship and Shipped; a shipped line moved back is In progress again.
- Limits (AC-4), a validation dataset with the card's messages under `units`, nothing changed: 25 with 20 in cutting → "You can move at
  most 20 units."; empty, 0 and 2.5 → "Enter a whole number of units, at least 1."; a move that isn't one of the six is refused.
- The saved count wins (AC-4): the screen showed 20 in cutting and someone else marked 15 ready meanwhile; Mark ready 10 → "You can move
  at most 5 units.", and the reloaded page carries the saved counts.
- A line of another order in the address → 404 (scoped binding); another workshop's order → 404 (route-guard rows).
- Ready and shipped are counts (AC-7): Mark ready, Ship and the three moves back leave materials, stock history, sales and the dashboard
  figures identical.
- PostgreSQL only: a move locks the order row and then the line (`for update` in the query log).

> [!example]- Files (10)
> - `app/Enums/OrderLineMove.php`
> - `app/Actions/Orders/MoveOrderLineUnits.php`
> - `app/Http/Controllers/OrderLineMoveController.php`
> - `app/Http/Requests/Orders/MoveOrderLineUnitsRequest.php`
> - `routes/web.php` (moves, scoped bindings)
> - `resources/js/types/domain.ts` (`OrderLineMove`, `MoveUnitsFormData`)
> - `tests/Feature/Orders/{MoveOrderLineUnitsTest,MoveLimitsTest}.php`
> - `tests/Feature/Routes/RoutesTest.php`
> - `tests/Feature/Workshops/CrossWorkshopAccessTest.php`

#### S-49-c · Editing and deleting an order that has started
**Commit:** `feat(orders): an order that has started keeps its started units (S-49-c)`

**Tests**
- Edit rules (AC-6), a dataset on Boutique Lina at 20 · 20 · 10 · 0, nothing saved on each refusal: Denim Jacket 25 → "The quantity
  can't be less than the 30 units already in cutting, ready to ship or shipped." under that line's quantity; Denim Jacket left out
  (removed, or swapped for another product) → "Denim Jacket can't be removed: 30 units have started." under the products list.
  One-unit rows (AC-6): a line with 1 unit in cutting left out → "Denim Jacket can't be removed: 1 unit has started."; the quantity
  message for 1 started unit reads "The quantity can't be less than the 1 unit already in cutting, ready to ship or shipped." (proved
  on the message itself: a quantity below 1 is refused first by S-48's 1 to 1,000,000 rule, see the hand-off).
- Allowed edits (AC-6): Denim Jacket 40 → saved, To start 10; Denim Jeans 5-pocket (not started) swapped, removed or changed; a new line
  added; a fully shipped line raised from 50 to 60 → To start 10, the line and the order In progress.
- The saved count wins (AC-6): the form was opened with nothing started, then someone else started 30 units; saving 25 is refused with
  the quantity message, and saving without Denim Jacket (or with another product in its place) is refused with "Denim Jacket can't be
  removed: 30 units have started." under the products; nothing is saved either time (the checks run again after the order lock).
- Delete refused (AC-6), from the order page and from the list: with 20 in cutting and 10 ready to ship, Delete → "Boutique Lina can't
  be deleted: 30 units are in cutting or ready to ship. Move them back to To start first." and the order, its lines and its counts are
  untouched; the order's `delete_refusal` carries the same text. With 1 unit in cutting: "Boutique Lina can't be deleted: 1 unit is in
  cutting or ready to ship. Move them back to To start first.". A dataset of what may be deleted: only To start, only Shipped, To start
  and Shipped mixed → deleted as today, `delete_refusal` null.
- Delete, the saved state wins (AC-6): the page showed nothing in cutting, then someone else started cutting 5 units; Delete is refused,
  nothing is deleted and the answer is an error toast with the refusal text (the check runs after the order lock, which every move also
  takes first).

> [!example]- Files (9)
> - `app/Http/Requests/Orders/UpdateOrderRequest.php`
> - `app/Actions/Orders/{UpdateOrder,SyncOrderLines}.php` (the re-check under the lock)
> - `app/Actions/Orders/DeleteOrder.php` (the lock and the refusal)
> - `app/Http/Controllers/OrderController.php` (`destroy` answers a refusal)
> - `app/Http/Resources/OrderResource.php` (`delete_refusal`)
> - `resources/js/types/domain.ts` (`Order.delete_refusal`)
> - `tests/Feature/Orders/{EditStartedOrderTest,DeleteStartedOrderTest,OrderActionsTest}.php`

#### S-49-d · The order page and the move window
**Commit:** `feat(orders): order page with stage figures and the move window (S-49-d)`

**Tests**
- Page smoke (AC-1): heading "Boutique Lina", "Ordered 15 Oct 2026 · 2 products · 80 units", the status, "0 of 80 shipped", "Note",
  "Edit" and "Delete"; each line's block with its four figures in order and "0 of 50 shipped".
- Statuses (AC-3): line and order statuses read as a word with an icon (the icon is `aria-hidden`; the word carries the meaning); on a
  fully shipped line the three forward buttons are disabled; a stage holding 0 disables its button; on a deleted product's line "Start
  cutting" is disabled with "Denim Jacket was deleted, so it can't be cut." while Mark ready, Ship and Move back stay open (AC-2).
- Move window (AC-2, AC-4, AC-5): the title "Start cutting · Denim Jacket", "How many units?", "Up to 20 units.", "Move units" posts
  `{ move, units }`; the server's message shows under the field and the window stays open; "Cancel" sends nothing; "Move back to To start
  · Denim Jacket" for a move back.
- Orders list: "Boutique Lina" links to the order's page (AC-1).
- 1 browser test: New order → lands on the order's page → Start cutting 30 → 20 · 30 · 0 · 0 and the toast (the S-48 golden path is
  updated: saving lands on the order's page).

> [!example]- Files (11)
> - `resources/js/pages/orders/show.tsx`
> - `resources/js/components/orders/{order-header,order-line-card,stage-figure,progress-status,move-units-dialog}.tsx`
> - `resources/js/components/orders/orders-table.tsx` (the client links to the order)
> - `resources/js/pages/orders/show.test.tsx`
> - `resources/js/components/orders/{move-units-dialog,order-line-card}.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php`

#### S-49-e · Edit order and Delete order once work has started
**Commit:** `feat(orders): edit and delete an order that has started (S-49-e)`

**Tests**
- Order form, edge cases (AC-6): a started line's product picker is disabled with "The product can't change once work on this line has
  started."; its "Remove" button can't be clicked and carries the hint "Denim Jacket can't be removed: 30 units have started." (and
  "… 1 unit has started." for a line with one unit started, the text coming from the line's `started`); lines not started and new lines
  stay free; the server's quantity message shows under its line, and its "can't be removed" message under the products.
- Delete (AC-6), on the order page and on the list: with `delete_refusal` set, "Delete" opens the "Delete order?" window, which shows
  "Boutique Lina can't be deleted: 30 units are in cutting or ready to ship. Move them back to To start first." in place of its
  question, with only a "Close" button, and sends nothing; with one unit it shows the server's "1 unit is …" text as sent. Without
  `delete_refusal`, "Delete order?" works as in S-48 and adds no sentence. A refusal answered by the server (someone started cutting
  meanwhile) shows as an error toast with the same text, and the order stays.

> [!example]- Files (8)
> - `resources/js/components/orders/{order-form,order-lines-editor,order-line-row,delete-order-dialog}.tsx`
> - `resources/js/components/orders/{order-lines-editor,delete-order-dialog}.test.tsx`
> - `resources/js/pages/orders/{edit,show}.test.tsx`

### Contract (frozen before dispatch)
**Routes** (under `auth` + `verified`, `OrderPolicy` on each; `{order}` and `{line}` route-model bound with digits only, and the line
must belong to the order (`scopeBindings()`), otherwise 404):
- `GET orders/{order}` → `orders.show` → page `orders/show` (S-49-a).
- `POST orders` and `PUT orders/{order}` now redirect to `orders.show` (S-49-a). `DELETE orders/{order}` sent from the order's own page
  redirects to `orders.index`; from the list it still goes back (keeping the page and the search). While any line has units in cutting
  or ready to ship, `DELETE` deletes nothing and goes back with an error toast carrying the refusal text (S-49-c).
- `POST orders/{order}/lines/{line}/moves` → `orders.lines.moves.store` (`update` on the order) → redirect to `orders.show` with the
  toast; validation errors under `units` (S-49-b).
- S-53 adds `GET orders/{order}/lines/{line}/moves/preview` (see the S-53 card).

**Page props:** `orders/show`: `{ order: Order }` (a closure); lines in position order with their products, soft-deleted ones included.

**`resources/js/types/domain.ts`** (S-49-a and S-49-b):
```ts
export type OrderStage = 'to_start' | 'in_cutting' | 'ready_to_ship' | 'shipped';
export type OrderProgress = 'not_started' | 'in_progress' | 'shipped';
/** start_cutting: to_start → in_cutting · mark_ready: in_cutting → ready_to_ship · ship: ready_to_ship → shipped ·
 *  back_to_start: in_cutting → to_start · back_to_cutting: ready_to_ship → in_cutting · back_to_ready: shipped → ready_to_ship */
export type OrderLineMove = 'start_cutting' | 'mark_ready' | 'ship' | 'back_to_start' | 'back_to_cutting' | 'back_to_ready';

export type OrderLine = {
    id: number;
    product: OrderProduct;
    quantity: number;
    to_start: number; // quantity − in_cutting − ready_to_ship − shipped, computed server-side
    in_cutting: number;
    ready_to_ship: number;
    shipped: number;
    started: number; // in_cutting + ready_to_ship + shipped, computed server-side
    status: OrderProgress;
    status_label: string; // "Not started", "In progress", "Shipped"
};

export type Order = {
    id: number;
    client: string;
    ordered_on: string; // 'YYYY-MM-DD'
    note: string | null;
    lines: OrderLine[];
    units: number;
    in_cutting: number; // the lines summed, server-side
    ready_to_ship: number;
    shipped: number; // "10 of 80 shipped"
    status: OrderProgress;
    status_label: string;
    // S-49-c. Set while units are in cutting or ready to ship: "Boutique Lina can't be deleted: 30 units are in cutting or ready to
    // ship. Move them back to To start first." (30 = in_cutting + ready_to_ship; "1 unit is" for one); null when it may be deleted
    delete_refusal: string | null;
};

export type OrderShowProps = { order: Order };
export type MoveUnitsFormData = { move: OrderLineMove; units: string };
```
`OrderStage`, `OrderProgress` and `OrderLineMove` are PHP enums with `label()` (stages "To start", "In cutting", "Ready to ship",
"Shipped"; statuses as above). Labels and toasts come from the server; the screen formats the figures and never adds them up.

**Schema** (S-49-a): `order_lines` gains `in_cutting`, `ready_to_ship` and `shipped`, unsigned integers, default 0. PostgreSQL only:
`CHECK (in_cutting + ready_to_ship + shipped <= quantity)`.

**PHP:** `MoveOrderLineUnits::handle(OrderLine $line, OrderLineMove $move, int $units, User $actor): MoveResult` (the units moved and the
toast text; S-53 adds what was taken or returned). One transaction: it locks the order row, then the line, and reads the counts under the
lock.

**Validation** (`MoveOrderLineUnitsRequest`): `move` required, one of the six; `units` required, integer, min 1, with one message for all:
"Enter a whole number of units, at least 1.". The stage limit is checked in the Action under the lock: "You can move at most N units."
under `units` (N is the saved count). Every count in a message uses the singular for 1 ("1 unit has started.", "the 1 unit already", "1 unit
is in cutting"), built with `trans_choice`. Edit order (S-49-c): a line whose product is already on the order keeps `quantity ≥ started` ("The
quantity can't be less than the N units already in cutting, ready to ship or shipped."); a started line's product must stay on the order
("<product name> can't be removed: N units have started." under `lines`). `UpdateOrder` repeats both checks after its order lock.
Start cutting on a deleted product: "<product name> was deleted, so it can't be cut." under `units` (S-49-b). Delete (S-49-c):
`DeleteOrder` locks the order row, re-reads its lines and refuses while `in_cutting + ready_to_ship > 0`, with the `delete_refusal` text.

### Decisions
- **To start is derived, not stored.** Three counters and the quantity can't disagree; a stored fourth figure could. S-50 sums the same
  columns.
- **One Action for all six moves.** Every move has the same shape (from a stage to the next or the previous one, at most what the stage
  holds). S-53 plugs the stock side into `start_cutting` and `back_to_start` only, so "Mark ready", "Ship" and their "Move back" can't
  touch stock by construction.
- **Locks in one order for every writer** (ADR-0014 §7): the order row, then the line, then (S-53) the line's cuts and the materials in
  ascending id. "Edit order" already locks the order row (S-48), so a move and an edit of the same order take turns. On SQLite (tests
  only) writers are serialised by the database file. `DeleteOrder` takes the same order lock, so a delete and a Start cutting on the
  same order take turns and the delete sees the cut.
- **Delete is refused, not warned** (D-11): an order may be deleted only with units to start or shipped, so no material taken out of
  stock can be lost by deleting (S-53 AC-6). Cancelling an order is S-57, out of scope.
- **A refused move redirects back with the error**, so Inertia reloads `order` and the window shows the saved count ("Up to 5 units.").
- **Moves happen on the order page**, not on the list; S-50 brings progress to the list.

### Order (the whole build: F-41, S-49 and S-53)
- **Before any code (docs):** ADR-0014 (with its amendment of 2026-10-16) and the costing rules in
  `.claude/skills/invory-costing-domain/SKILL.md` already match the final D-11 answers. Once the human approves this revised Technical
  work and S-53's, the main session commits them on `main` (F-41's approval still stands). The older rollback tests keep their hand-kept
  migration lists (F-60 stays open, the human's choice): S-49-a and S-53-a add their migrations to them.
- **Wave 1:** F-41-a (backend 1) ∥ S-49-a (backend 2).
- **Wave 2:** S-53-b (backend 1, the stock maths; needs F-41-a) ∥ S-49-b (backend 2).
- **Wave 3:** S-53-a (backend 1) ∥ S-49-c (backend 2) ∥ S-49-d (frontend; needs S-49-a and S-49-b).
- **Wave 4:** S-53-c (backend 1) ∥ S-49-e (frontend; needs S-49-c) ∥ S-53-f (a second frontend developer; needs S-53-a).
- **Wave 5:** S-53-d (backend 1).
- **Wave 6:** S-53-e (frontend; needs S-53-c and S-53-d).
- Then every gate on the combined tree, `composer test:pgsql`, `composer test:browser`, and one delivery report and one click-through for
  S-49 and S-53 together.
- Commit order on the branch: F-41-a, S-49-a … S-49-e, S-53-a … S-53-f. Each commit is green on its own; between S-49-b and S-53-c,
  "Start cutting" moves only the counters.

### Risks
- A move or an edit reads a count from before someone else's change → every check runs on rows locked inside the transaction; a test
  makes the screen stale on purpose, and PostgreSQL tests check that the locks are taken.
- The four figures stop adding up → To start is derived; PostgreSQL refuses counters above the quantity; the Action never lets a stage go
  below 0.
- Saving now lands on the order page, which breaks S-48 tests and the browser golden path → both are in S-49-a's and S-49-d's file lists.
- Deleting from the order page would go "back" to a page that no longer exists → `destroy` lands on the list when it was sent from the
  order page (S-49-a test).
- Someone confirms Delete while someone else starts cutting → both take the order lock first; the delete re-reads the counts and is
  refused (S-49-c test).
- The route guards are shared test files → they are in the file lists, and each developer runs the whole suite.
- A started line's product swapped in Edit order could slip through, since lines are matched by product (S-48) → a swap reads as
  "removed", so the started-line rule catches it (S-49-c dataset row).
- A status shown by colour alone → a word plus an `aria-hidden` icon, tested by role and text (S-49-d).

### Build log
Build of F-41, S-49 and S-53 in one branch (worktree `kind-haibt-05dd89`, from `6f7b53a`). Started 2026-10-16.

**Adjustments made at intake (tech-lead, 2026-10-16).** None changes a criterion, a message or the contract's shape.
- **The order contract changes once.** `Order.delete_refusal` (the field and its text) moves from S-49-c to S-49-a, so `Order` and
  `OrderLine` change in one step. S-49-c keeps the refusal itself (`DeleteOrder` under the lock) and its tests.
- **Frontend type fixes join the contract steps.** Adding fields to `Order`, `OrderLine` and `StockMovementType` breaks typed test
  fixtures (5 order test files) and the `Record<StockMovementType, …>` map in the history list; the file lists above missed them. A
  short frontend pass right after S-49-a goes into S-49-a's commit, and S-53-f runs right after S-53-a, so **S-53-a and S-53-f become
  one commit** (each alone would leave `npm run types:check` red).
- **One message builder:** `App\Services\Orders\OrderMessages` (S-49-a: the delete refusal; S-49-b: the move toasts and limits; S-53-c:
  the short-stock messages and the toast suffixes). It replaces the `MoveMessages` named in S-53-c.
- **Waves:** W1 F-41-a ∥ S-49-a, then the frontend type pass · W2 S-53-b ∥ S-49-b ∥ S-53-a, then S-49-d ∥ S-53-f · W3 S-49-c ∥ S-53-c ·
  W4 S-53-d ∥ S-49-e · W5 S-53-e. The commits follow the review points: F-41-a, S-49-a · S-53-b, S-49-b, S-53-a + S-53-f, S-49-d ·
  S-49-c, S-53-c · S-53-d, S-49-e · S-53-e. Every review point runs every gate on the whole tree.

**Progress**
- W1 (F-41-a, S-49-a + frontend type pass): approved 2026-10-16, round 1; every gate green, PostgreSQL parity green.
- W2 (S-53-b, S-49-b, S-53-a, then S-49-d, S-53-f): approved 2026-10-16, round 1; every gate green, PostgreSQL parity and browser tests green.
- W3 (S-49-c, S-53-c): first dispatch cut off by the API rate limit before any file changed (tree identical to the W2 snapshot); re-dispatched with the same briefs; approved 2026-10-16, round 1; every gate green, PostgreSQL parity and browser tests green.
- W4 (S-53-d, S-49-e): approved 2026-10-16, round 1 (S-53-d gets its rollback test in W5); every gate green, PostgreSQL parity and browser tests green.
- W5 (S-53-e, S-53-d rollback test): approved 2026-10-16, round 1.
- Delivered 2026-10-16: every gate green on the final tree (composer test 2404 tests, npx vp test 1199, check, types, build), PostgreSQL parity 2404/2404, browser tests 2/2. Nothing committed; the delivery report lists the commits per step.
- Fix round after the product-owner's acceptance review (approved by the human, 2026-10-16): the one-unit delete refusal ends "Move it back to To start first."; the preview refuses more units than the stage holds ("You can move at most N units." as you type, no table); an emptied field shows "Enter a whole number of units, at least 1."; focus goes to the line's heading when the opening button is greyed after a move; the short-stock sentence shows once after a refused cut. Approved in round 1; every gate green (composer test 2409, npx vp test 1206), PostgreSQL parity 2409/2409, browser tests 2/2.
