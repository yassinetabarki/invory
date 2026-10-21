---
id: S-50
title: See on the Orders list how far each order has got, and what is being made
type: feature
phase: 5
priority: Must
size: S
status: Ready
created: 2026-10-15
tags:
  - story
  - phase-5
---

# S-50 · See on the Orders list how far each order has got, and what is being made

> [!abstract] Description
> As a **workshop owner**, I want **the Orders list to show each order's stages and status, with totals for all open orders at the top**,
> so that **I see at a glance how many units are in cutting and ready to ship, and which orders still need work, without opening each
> one**. An order's figures are the sums of its product lines, and an order is open until every line has shipped.

> [!info] How it works today
> Since S-48 the Orders list shows, for each order, "Order date", "Client", "Products", "Units" and "Note", newest first, 25 a page, with a
> search ("No orders match your search" when nothing matches). It doesn't say how far an order has got. With S-49 the four stages (To
> start, In cutting, Ready to ship, Shipped) are on each order's own page, line by line, so knowing how many units are in cutting across all
> orders means opening every order and adding the numbers up by hand.

## Acceptance criteria
- [ ] **AC-1 · Stages on every row.** Given three orders:
  - Boutique Lina, ordered 15 Oct 2026: Denim Jacket 50 (To start 20, In cutting 20, Ready to ship 10, Shipped 0) and Denim Jeans
    5-pocket 30 (nothing moved yet);
  - Concept Store Nour, ordered 14 Oct 2026: Classic White T-shirt 120 (nothing moved yet);
  - Hotel Dar Said, ordered 2 Oct 2026: Leather Tote 12 (all 12 shipped),

  the list's columns are "Order date", "Client", "Products", "Ordered", "To start", "In cutting", "Ready to ship", "Shipped" and
  "Status", and Boutique Lina's row reads 15 Oct 2026 · Boutique Lina · "50 × Denim Jacket" and "30 × Denim Jeans 5-pocket" · 80 · 50 ·
  20 · 10 · 0 · "In progress". The "Units" and "Note" columns give way to "Ordered" and the four stages: the client's note now shows
  under the client's name, cut short with "…". The client's name still opens the order's page, and each row keeps its "Edit" and
  "Delete" buttons.
- [ ] **AC-2 · Status, never by colour alone.** Each status is a badge with its word and an icon: "Not started" (Concept Store Nour), "In
  progress" (Boutique Lina) and "Shipped" (Hotel Dar Said). It reads the same in dark mode.
- [ ] **AC-3 · Open orders first.** A "Status" filter offers "Open orders" (chosen when the page opens), "Shipped" and "All orders".
  "Open orders" lists Boutique Lina, then Concept Store Nour; "Shipped" lists only Hotel Dar Said; "All orders" lists all three, newest
  first. The filter works together with the search. When nothing matches, the list shows "No orders match your filters", "Try a
  different client, product or note, or clear the filters to see your open orders." and a "Clear filters" button, which empties the
  search and goes back to "Open orders". When every order has shipped, "Open orders" shows "No open orders", "Every order has shipped."
  and a "Show all orders" button. With no orders at all, the list still shows "No orders yet" and "New order", as today, without the
  search or the Status filter; the four totals above it read 0.
- [ ] **AC-4 · Totals for all open orders.** Above the list, four figures: "Open orders 2", "To start 170 units", "In cutting 20 units" and
  "Ready to ship 10 units". They count every line of every open order in your workshop; the search and the Status filter never change
  them. After "Mark ready" with 5 on Boutique Lina's Denim Jacket line, the list shows "In cutting 15 units" and "Ready to ship 15 units".
  With no open orders, they read "Open orders 0" and "0 units". One unit reads "1 unit", as on the order's page.
- [ ] **AC-5 · Phone width.** At 375 px each order is a card: "Boutique Lina" and its status badge, "50 × Denim Jacket" and "30 × Denim
  Jeans 5-pocket", then "To start 50 · In cutting 20 · Ready to ship 10 · Shipped 0", the date, the note, and "Edit" and "Delete". The
  ordered total ("80 units") no longer sits next to the client: the status badge takes its place. Below 1024 px (tablets too) the list
  shows these cards; from 1024 px up, the table. The four totals sit in two rows of two.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 3 commits
> Tech-lead · 2026-10-21 · proposed (revised 2026-10-21 after the product-owner's answers)
> Why complex: the list gets a new rule (an order is open until every line has shipped), a new filter and new totals, so the data the
> Orders page receives changes (a new contract).

### In short
The server does all the counting. Each order on the list gets one more figure, "To start", added up from its lines like the other three
stages already are. "Open order" is defined once, in one database rule (an order with at least one line not fully shipped), and that
same rule drives the Status filter, the "Open orders" count and the four totals, so they can never disagree. The totals are one small
database sum over the open orders of your workshop, sent next to the list but never reloaded when you search or change the filter, so
the filter can't change them. The rule is also written down in the business-rules file (the human's choice), so the later stories on
cancelling and sizes build on it. The server part comes first; then two frontend steps run side by side: the rows and phone cards, and
the filter, totals and empty messages around them.

### Steps

| Step       | What it does                                     | Proves           | Lane         | Who                | Size       |
| ---------- | ------------------------------------------------ | ---------------- | ------------ | ------------------ | ---------- |
| **S-50-a** | Open orders, the Status filter and the totals    | AC-1, AC-3, AC-4 | CRITICAL     | Backend (+ FE fix) | ~200 lines |
| **S-50-b** | The new columns, the status badge, phone cards   | AC-1, AC-2, AC-5 | NON-CRITICAL | Frontend           | ~220 lines |
| **S-50-c** | The Status filter, the four totals, empty states | AC-3, AC-4, AC-5 | NON-CRITICAL | Frontend           | ~280 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. S-50-a also
runs `composer test:pgsql` (new SQL sums and filters); S-50-b also runs `composer test:browser` (the golden path reads the list's columns).

#### S-50-a · Open orders, the Status filter and the totals
**Commit:** `feat(orders): open orders, status filter and open-order totals on the list (S-50-a)`

**Tests**
- AC-1, one `assertInertia` shape: Boutique Lina's row carries `units` 80, `to_start` 50, `in_cutting` 20, `ready_to_ship` 10,
  `shipped` 0, `status` `in_progress` "In progress" (the three orders of the card, built with the line factory's stage states).
- AC-3, one dataset over the query string: none → Boutique Lina, Concept Store Nour · `status=open` → the same · `status=shipped` →
  Hotel Dar Said · `status=all` → all three, newest first · `status=all&search=Denim` → Boutique Lina · an order with one line fully
  shipped and one not → listed under Open orders. `filters` echoes `{search, status}` (status `open` when absent).
- AC-3, the `status` rule joins the index validation dataset: an unknown value is refused.
- AC-4, one dataset: the totals read Open orders 2 · To start 170 · In cutting 20 · Ready to ship 10 whatever the query string (none,
  `search=Nour`, `status=shipped`, `status=all&search=Hotel`).
- AC-4, one test: "Mark ready" with 5 on Boutique Lina's Denim Jacket line through the move route, then the list reads In cutting 15 ·
  Ready to ship 15.
- AC-4, one dataset: only a shipped order → all four 0 and `has_orders` true · no order at all → all four 0 and `has_orders` false.
- AC-4, one test: another workshop's open orders and a deleted open order never count.
- The orders row of `QueryBudgetTest` stays within budget with the two new queries (same count with 3 and 25 orders).

**Business rules file** (the tech-lead writes it, in this commit): the glossary gains "Open order" (an order with at least one line not
fully shipped; "Shipped" once every line is), and the Formulas gain the list totals (open orders counted; To start, In cutting and Ready
to ship summed over every line of every open order of the workshop; the search and the Status filter never change them).

> [!example]- Files (11, plus 6 test-fixture files)
> - `.claude/skills/invory-costing-domain/SKILL.md` ("Open order" and the list totals; tech-lead)
> - `app/Enums/OrderStatusFilter.php` (new)
> - `app/Models/Order.php` (`open()` and `shipped()` scopes, `unitsToStart()`)
> - `app/Services/Orders/OrderBook.php` (`list()` takes the status; `openTotals()`, `hasOrders()`)
> - `app/Http/Requests/Orders/IndexOrderRequest.php`
> - `app/Http/Controllers/OrderController.php` (`index` props)
> - `app/Http/Resources/OrderResource.php` (`to_start`)
> - `resources/js/types/domain.ts`
> - `resources/js/components/orders/{orders-table,delete-order-dialog,move-units-dialog}.test.tsx` (fixtures: `to_start`; frontend pass)
> - `resources/js/pages/orders/{index,show,edit}.test.tsx` (fixtures: `to_start`, the new index props; frontend pass)
> - `tests/Feature/Orders/OrdersIndexTest.php` (the `filters` assertion, AC-1 and AC-3)
> - `tests/Feature/Orders/OpenOrderTotalsTest.php` (new, AC-4)
> - `tests/Feature/Performance/QueryBudgetTest.php` (only if the budget needs it)

#### S-50-b · The new columns, the status badge, phone cards
**Commit:** `feat(orders): stages and status on every row of the orders list (S-50-b)`

**Tests**
- AC-1, one test: the column headers in order ("Order date" … "Status") and Boutique Lina's row reads 15 Oct 2026 · Boutique Lina ·
  both product lines · 80 · 50 · 20 · 10 · 0 · "In progress"; the note sits under the client (full text in its `title`); the client
  links to the order's page; the row keeps its Edit and Delete icon buttons (named "Edit order of Boutique Lina on …" and "Delete
  order of …"); there is no "Units" or "Note" header.
- AC-2, one `it.each` over the three statuses: the badge shows the word, with an icon hidden from screen readers.
- AC-5, one test (scoped `within` the cards list): "Boutique Lina" with its status badge next to it, both product lines, "To start 50 ·
  In cutting 20 · Ready to ship 10 · Shipped 0", the date, the note, and the labelled "Edit" and "Delete" buttons; "80 units" is gone.
- AC-5, the switch at 1024 px: the table's wrapper shows from `lg` up and the cards list hides from `lg` up (checked on their classes,
  since jsdom has no layout; the click-through checks 1023 and 1024 px).
- The browser golden path's list step reads the new columns and the "In progress" badge.

> [!example]- Files (3)
> - `resources/js/components/orders/orders-table.tsx`
> - `resources/js/components/orders/orders-table.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php`

#### S-50-c · The Status filter, the four totals, empty states
**Commit:** `feat(orders): status filter, open-order totals and empty states on the orders list (S-50-c)`

**Tests**
- AC-3, one test: the "Status" select offers "Open orders", "Shipped" and "All orders" (from the `statuses` prop), shows "Open orders"
  on opening, and choosing "All orders" reloads only the list with `status=all` and the current search kept.
- AC-3, one `it.each` over the three empty lists: no match → "No orders match your filters", "Try a different client, product or note,
  or clear the filters to see your open orders." and "Clear filters" (which empties the search and goes back to Open orders) · every
  order shipped → "No open orders", "Every order has shipped." and "Show all orders" (which asks for `status=all`) · no order at all →
  "No orders yet" and "New order", with neither the search nor the Status filter on the page, and the totals reading "Open orders 0"
  and "0 units".
- AC-4, one test: the four figures "Open orders 2", "To start 170 units", "In cutting 20 units" and "Ready to ship 10 units"; "0 units"
  when a total is 0 and "1 unit" when it is 1.
- AC-5 (totals in two rows of two at 375 px): a two-column grid below `lg`, checked in the click-through (jsdom has no layout).

> [!example]- Files (6)
> - `resources/js/pages/orders/index.tsx`
> - `resources/js/pages/orders/index.test.tsx`
> - `resources/js/components/orders/orders-filters.tsx` (new: search and Status)
> - `resources/js/components/orders/orders-filters.test.tsx` (new)
> - `resources/js/components/orders/open-order-totals.tsx` (new)
> - `resources/js/components/orders/open-order-totals.test.tsx` (new)

### Contract (frozen before dispatch)
**Route:** `GET orders` (`orders.index`) takes `status` = `open` | `shipped` | `all` (optional; `open` when absent, an unknown value is
a validation error) next to `search` and `page`. The pagination links keep it (`withQueryString`). The frontend leaves `status` out of
the URL when it is `open`.

**Page props** of `orders/index` (each one that costs a query is a closure; a search, filter or page change reloads only `orders` and
`filters`, so `totals` and `has_orders` never follow the filters):

```ts
export type OrderStatusFilter = 'open' | 'shipped' | 'all';

export type OrderFilters = {
    search: string | null; // client, note, or a line's product name or SKU
    status: OrderStatusFilter; // 'open' when the URL has none
};

// Order gains one field (S-50-a):
//     to_start: number; // the lines' To start summed, server-side
// On the list (S-50), Order.status is the order's status ("Not started", "In progress", "Shipped").

/** Every line of every open order of the workshop, whatever the search and the filter (S-50). */
export type OpenOrderTotals = {
    open_orders: number;
    to_start: number;
    in_cutting: number;
    ready_to_ship: number;
};

export type OrdersIndexProps = {
    orders: Paginated<Order>;
    filters: OrderFilters;
    statuses: Option<OrderStatusFilter>[]; // "Open orders", "Shipped", "All orders", from the server
    totals: OpenOrderTotals;
    has_orders: boolean; // any order at all in the workshop, whatever its status: tells "No orders yet" from "No open orders"
};
```

**PHP:** `App\Enums\OrderStatusFilter` (`Open`, `Shipped`, `All`; `label()` and `options()` like the other enums). `Order::open()`: the
order has a line with `shipped < quantity`; `Order::shipped()`: it has none. `OrderBook::list(?string $search, OrderStatusFilter $status)`,
`OrderBook::openTotals(): array{open_orders: int, to_start: int, in_cutting: int, ready_to_ship: int}` (one integer SQL sum over the
lines of `Order::open()`, cast to `int`: PostgreSQL returns sums as strings) and `OrderBook::hasOrders(): bool`.

### Decisions
- **"Open" is one SQL scope, and the totals go through it.** The shipped orders would add 0 anyway, but S-57 adds cancelled orders,
  which must leave the totals and the Open filter in one place.
- **The SQL scope and `Order::progress()` must agree.** The status is still worked out from the loaded lines (no extra query); the
  AC-3 dataset includes an order with one line shipped and one not, which both must call open.
- **The totals are a separate prop, outside the list's partial reload.** That is what makes "the search and the Status filter never
  change them" true by construction. A move or a delete reloads the whole page, so they stay current.
- **`has_orders` instead of reading the totals.** With no open order, the list can't tell "No orders yet" from "No open orders"
  otherwise.
- **The order's To start comes from the server** (`Order.to_start`), as the other stage figures do; the screen formats, never adds.
- **No ADR.** This reuses the existing patterns (enum filter with options, closure props, Eloquent scopes).

### Order
Wave 1: S-50-a (backend), then a short frontend pass on the test fixtures in the same commit (the new `Order.to_start` and index props
break typed fixtures, so `npm run types:check` would go red without it). Wave 2, after S-50-a is approved: S-50-b ∥ S-50-c (disjoint
files). Then every gate on the combined tree, `composer test:pgsql` and `composer test:browser`.

### Risks
- "Open orders" is now the default, so a test that lists a shipped order without asking for `status=all` changes meaning → the
  developer runs the whole suite; the cross-workshop and leak-scan tests read `orders.index` and are checked in review.
- The list gains two queries (totals, `has_orders`) under a budget of 10 → the query-budget test runs in S-50-a; if it is tight,
  `has_orders` folds into the totals query.
- Ten columns don't fit a tablet → the table shows from `lg` (1024 px) up and the cards below it (the product-owner's answer, AC-5);
  until now the switch was at `md` (768 px), so tablets now get cards, which the click-through checks at 1023 and 1024 px.
- The SQL scope and the PHP status drift apart → one dataset row pins them together (see Decisions).
- **For the size stories:** S-61 AC-6 wants these totals to add every size. That stays free if S-61 keeps the line's three stage
  columns as the line's totals (the sum of its sizes, written under the same lock). If S-61 moves the counts to a per-size table only,
  the `open()` scope, the totals query and `Order.to_start` must be rewritten. S-60 AC-3 changes the "Products" cell only: it stays its
  own small component in `orders-table.tsx`. S-57 adds a "Cancelled" case to `OrderStatusFilter` and one condition to `open()`.
