---
id: INV-14
title: Order tracker, covered from the shelf
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
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

