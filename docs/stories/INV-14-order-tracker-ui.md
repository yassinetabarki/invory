---
id: INV-14
title: Order tracker, covered from the shelf
type: ui
priority: Must
status: Approved
created: 2026-11-01
tags:
  - ui-card
---

# INV-14 · Order tracker, covered from the shelf — UI card

> [!abstract] Description
> The order page answers "Where is Boutique Lina's order?" in one look: how many units are still **To deliver**, how many are
> **Delivered** and how many **Cancelled**, with the **Total**, a bar, and a table per product by colour and size that also shows
> what is **on the shelf now**. An order no longer owns production. Products are made on **Progress**, for the shelf; an order is
> served from the finished stock. Two buttons stay under each product: **Mark delivered**, which takes the units off the shelf, and
> **Cancel units**.
> New: the **Order tracker** card, the table per product by colour and size with "On the shelf now", **Mark delivered** (now moving
> finished stock), **Cancel units**, the status Cancelled. Changed: the order page (the stage boxes, **Start cutting**, **Move to …**,
> **Mark ready**, **Ship**, the three **Move back**, **Tickets** and **See these units in Progress** leave it), the Orders list and
> the orders table of a client page (new words and columns), the Delete order window (its refusal goes).
> This is the third of three cards that refactor the order (INV-12 is the client, INV-13 the products). It builds on order lines per
> colour and size (INV-13) and on Progress making products for the shelf (INV-5, redrawn the same day).
> Left out on purpose: setting shelf stock aside for an order (the shelf is checked, never held), splitting a delivery across
> orders by itself, a history of deliveries (the order shows how many units are delivered, not when or by whom), a "Make more" link
> from an order to Progress, undoing a cancel, cancelling a whole order in one click, closing an order, and everything about making
> products (Progress).

> [!info] How it looks today
> Today units of an order go through production **on the order**: its page shows, for each product, To start · In cutting · Ready to
> ship · Shipped, with **Start cutting**, **Mark ready**, **Ship** and the Move back buttons (INV-5's earlier design added Sewing,
> Finishing and **Tickets** there); "Ship" never touches the finished stock. Under the title: a status ("Not started", "In progress",
> "Shipped") and "0 of 80 shipped". The Orders list shows per order To start · In production · Ready to ship · Shipped.
> That does not match how the workshop works: a finished product is not tied to an order. It is put on the shelf, and when an order
> comes the owner checks the shelf. There is also no way to say a client dropped part of an order: the units stay To start for ever.

## Pages

Today is 30 Oct 2026. **Boutique Lina**'s order of 15 Oct 2026 (Tunis, 71 234 567, Walk-in / shop), 80 units. Shelf stock (S · M · L ·
XL): Denim Jacket Indigo 2 · 0 · 3 · 1; Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0.

| Product · colour · size          | To deliver | Delivered | Cancelled | Total | On the shelf now |
| -------------------------------- | ---------- | --------- | --------- | ----- | ---------------- |
| Denim Jacket · Indigo S          | 10         | 0         | 0         | 10    | 2                |
| Denim Jacket · Indigo M          | 20         | 0         | 0         | 20    | 0                |
| Denim Jacket · Indigo L          | 15         | 0         | 0         | 15    | 3                |
| Denim Jacket · Indigo XL         | 0          | 5         | 0         | 5     | 1                |
| **Denim Jacket**                 | 45         | 5         | 0         | 50    |                  |
| Denim Jeans 5-pocket · Indigo S  | 5          | 0         | 0         | 5     | 0                |
| Denim Jeans 5-pocket · Indigo M  | 10         | 0         | 0         | 10    | 5                |
| Denim Jeans 5-pocket · Indigo L  | 10         | 0         | 0         | 10    | 2                |
| Denim Jeans 5-pocket · Indigo XL | 0          | 0         | 5         | 5     | 0                |
| **Denim Jeans 5-pocket**         | 25         | 0         | 5         | 30    |                  |
| **Boutique Lina**                | 70         | 5         | 5         | 80    |                  |

- The Orders list has three open orders: Amira Ben Salah (28 Oct, 2 units, none delivered), Yassine Trabelsi (27 Oct, 3 units, 2
  delivered, 1 to deliver) and Boutique Lina. Two more show under "All orders": Mehdi Jaziri (Leather Tote, 12 units, all
  delivered) and Nour Gharbi (Classic White T-shirt, 2 units, all cancelled).

### What the words mean
- **To deliver:** ordered, not delivered yet, not cancelled. These are the units the client is waiting for.
- **Delivered:** gone to the client. **Mark delivered** took them off the shelf.
- **Cancelled:** dropped by the client; kept on the order, never delivered, no effect on stock.
- **The three counts always add up to the Total.** Delivered and Cancelled units stay in the Total.
- **On the shelf now** is the finished stock of that colour and size (INV-2, INV-3). It is a fact about the product, not a count of
  the order. **It is not set aside for the order**: if two orders both ask for the last 2 Indigo S, both show "On the shelf now 2",
  and whoever marks them delivered first gets them.
- **An order's status:** **Cancelled** when every unit is cancelled; **Delivered** when nothing is left to deliver and at least one
  unit was delivered; **Not started** while nothing has been delivered yet; **In progress** when some units are delivered and some
  are still to deliver. Boutique Lina is In progress.
- **An open order** is one with at least one unit To deliver.
- **Making products is not part of an order.** Cutting, sewing, tickets and putting products in stock happen on Progress. An order
  doesn't start production and doesn't take materials.
- **Mark delivered** takes the units off the finished stock, in their colour and size, never more than the shelf holds. Its move
  back puts them on the shelf again. Cancelling changes no stock.

### Order page
Board: INV-14 · Order page · covered from the shelf: tracker, To deliver and On the shelf now, Mark delivered, Cancel units, Delete order, states · light and dark · desktop (Invory App UI canvas)
1. Breadcrumb "Orders › Boutique Lina"; the header, INV-4's client line ("[Walk-in / shop] · Tunis · 71 234 567 · All orders of
   Boutique Lina"), **Edit** and **Delete** as today.
2. The **Order tracker** card:
   - heading **Order tracker**, the status badge (**In progress**) and "**5 of 80** units delivered · 5 cancelled";
   - a thin bar whose segments follow the counts; Cancelled is hatched. The bar is decoration: the tiles hold the numbers;
   - four tiles in one row, a list named "Units of this order", each with its word, an icon, the count in the number font and
     "units": **To deliver** 70 (truck icon, blue tone) · **Delivered** 5 (check-circle, green) · **Cancelled** 5 (dashed outline,
     ban icon) · **Total** 80 ("units ordered"). A tile is read "To deliver, 70 units";
   - a grey line: "To deliver: ordered, not delivered yet. On the shelf now is your finished stock; it isn't set aside for this order.
     Delivered and Cancelled units stay in the total."
3. **Products:** one card per product with
   - the name (a link to the product), its SKU, "50 units", its status badge and "5 of 50 delivered";
   - the table "Denim Jacket by colour and size": first column **Colour · size**, then **To deliver · Delivered · Cancelled ·
     Total**, a gap, and **On the shelf now**. A colour row (swatch, name, its subtotals, bold, on a light band), its sizes under it,
     a **Total** row with a heavier line above (no shelf figure on the colour and Total rows). "One size" products: colour rows only.
     Figures right-aligned, zeros in a readable grey; a shelf figure of 0 is the amber word **Out**;
   - under the table: **Mark delivered** (main when the shelf covers some units), **Cancel units** (outline, red text) and the "⋯"
     button named "More actions for Denim Jacket", which holds "Move delivered back to To deliver".
4. The figures never link anywhere. Every number comes from the server; the screen only formats.
5. Gone from the order page: the boxes and phase columns, **Start cutting**, **Move to Sewing**, **Move to Finishing**, **Mark
   ready**, **Ship**, the three **Move back**, **Tickets (2)**, **See these units in Progress**, "0 of 80 shipped".

### Mark delivered and Move back (windows)
Board: INV-14 · Order page · covered from the shelf (the three windows) (Invory App UI canvas)
- Title "Mark delivered · Denim Jacket". A grey line "Boutique Lina · to deliver and on the shelf: Indigo S 2 · L 3". The group
  "How many units of each colour and size?": one row per colour that has something to deliver and on the shelf, one field per size
  that has both, "Up to 2" under Indigo S and "Up to 3" under Indigo L (the smaller of the units to deliver and the shelf). The focus
  starts in the first field. M has none on the shelf, so it has no field.
- A note: "Delivered units leave your finished stock."
- Buttons **Mark delivered** (main) and **Cancel**. Toast: "5 units of Denim Jacket marked delivered." ("1 unit of Denim Jacket
  marked delivered.")
- After typing Indigo S 2 and L 3: To deliver 65 · Delivered 10, and the shelf reads Indigo S 0 · L 0 on the Jacket (and on its
  product page).
- The "⋯" entry "Move delivered back to To deliver" opens the same window titled "Move back to To deliver · Denim Jacket", with the
  grey line "Boutique Lina · delivered: Indigo XL 5", the note "These units go back on your shelf." and the button **Move back**.
  Toast: "5 units of Denim Jacket moved back to To deliver."

### Cancel units (window)
Board: INV-14 · Order page · covered from the shelf (the three windows) (Invory App UI canvas)
- Title "Cancel units · Denim Jeans 5-pocket". The grey line "Boutique Lina · to deliver: Indigo S 5 · M 10 · L 10". The group
  "How many units of each colour and size?", "Up to 10" under Indigo M and L, the focus in the first.
- An amber note: "Cancelled units stay on the order, marked Cancelled, and are not delivered. This can't be undone. Finished stock
  doesn't change."
- Buttons **Keep units** (outline) and **Cancel 5 units** (red; the number follows what is typed). The window is the confirmation;
  no second one. Toast: "5 units of Denim Jeans 5-pocket cancelled." ("1 unit of … cancelled.")
- After typing Indigo L 5: To deliver 65 · Cancelled 10.
- A mistake is corrected by raising the quantity in Edit order.

### Orders list
Board: INV-14 · Orders list and client page · To deliver and Delivered, status filter, states · desktop (Invory App UI canvas)
- Header as today with **New order**. (No link to Progress: Progress no longer lists orders.)
- Two totals: **Open orders** 3 · **To deliver** 73 units. They count every open order, whatever the filters, and never count
  Delivered or Cancelled units.
- Filters: the search (as today), **Status** ("Open orders" by default, "Delivered", "Cancelled", "All orders") and **Client**
  (INV-4).
- Table: **Client** (the name, a link; the order date in grey under it) · **Products** · **Ordered** (the order's Total; "5 cancelled"
  in grey under it when there are any) · **To deliver** · **Delivered** · **Status** (badge with an icon).

| Client           | Ordered | To deliver | Delivered | Status      |
| ---------------- | ------- | ---------- | --------- | ----------- |
| Amira Ben Salah  | 2       | 2          | 0         | Not started |
| Yassine Trabelsi | 3       | 1          | 2         | In progress |
| Boutique Lina    | 80      | 70         | 5         | In progress |
| Mehdi Jaziri     | 12      | 0          | 12        | Delivered   |
| Nour Gharbi      | 2       | 0          | 0         | Cancelled   |

- Boutique Lina's "Ordered" cell reads "80" with "5 cancelled" under it. The old columns To start, In cutting, In production, Ready
  to ship and Shipped, and the status Shipped, are gone.

### Client page
- The orders table on a client page takes the same columns and words: **To deliver · Delivered**, the Status list "Open orders ·
  Delivered · Cancelled · All orders" ("All orders" by default there), and the empty message "Amira Ben Salah has no delivered
  orders." · **Show all orders**.

### Delete order
- **Confirm:** "Delete order?" · "The order of **Boutique Lina** (2 products, 80 units) will be removed. Material stock and sales are
  not affected. This cannot be undone." · **Cancel** · **Delete order** · toast "Order deleted." When the order has delivered units,
  one more sentence: "Finished stock isn't changed: the 5 units already delivered are not put back." ("the 1 unit already delivered
  is not put back").
- **No refusal any more.** An order holds no materials and no production, so nothing is lost by deleting it.

### The shelf, restocks and colours
- **Mark delivered lowers the shelf**, so an older restock may no longer be undoable; INV-3's message already says so ("This restock
  can't be undone: … has 2 units on the shelf, fewer than the 8 it added."). The product page keeps no trail of deliveries.
- **Edit product (carried over from INV-5's earlier card):** a colour that is on an open order can't be taken off its product: "Light
  wash is on 1 open order, so it can't be removed from Denim Jacket." An order is open while it has units To deliver; Delivered and
  Cancelled units don't count.

## States
- **Tracker, default:** as in Pages.
- **Tracker, all delivered:** status **Delivered**, "12 of 12 units delivered", the bar all green; **Mark delivered** and **Cancel
  units** can't be pressed, each with its reason beside it.
- **Tracker, all cancelled:** status **Cancelled**, "0 of 2 units delivered · 2 cancelled", the bar all hatched; both buttons can't
  be pressed.
- **Tracker, loading:** the header and the card frame at once; a bar and four tile placeholders.
- **Tracker, error:** "Couldn't load the tracker of this order." · **Try again**.
- **Buttons that can't be pressed** stay reachable by keyboard and say why in text beside them: **Mark delivered** "Nothing on the
  shelf covers this order yet. Units arrive from Progress or a restock." (when nothing is left to deliver: "Nothing is left to
  deliver.") · **Cancel units** "Nothing to cancel: no unit is waiting to be delivered."
- **Mark delivered, errors:** "Enter at least one unit to mark delivered." · "You can mark at most 2 units of Indigo S: only 2 are on
  the shelf." · "You can mark at most 4 units of Indigo S: only 4 are left to deliver." · "Indigo S: Enter a whole number of units."
  · the shelf or the order changed since the page opened: "The shelf or this order changed since you opened this page." and
  **Close**. While saving the button shows a spinner and keeps its label.
- **Move back, errors:** "Enter at least one unit to move back." · "You can move back at most 5 units of Indigo XL." · "Indigo XL:
  Enter a whole number of units."
- **Cancel units, errors:** "Enter at least one unit to cancel." · "You can cancel at most 10 units of Indigo L." · "Indigo L: Enter
  a whole number of units." · units delivered or cancelled meanwhile: "This order changed since you opened this page." and **Close**.
- **Orders list, empty:** "No orders yet" · "Record a client's order to keep track of what you've promised to make." · **New order**.
- **Orders list, none open:** "No open orders" · "Every order has been delivered or cancelled." · **Show all orders**.
- **Orders list, no match:** "No orders match your filters" · "Try a different client, product or note, or clear the filters to see
  your open orders." · **Clear filters**.
- **Orders list, loading and error:** the header, totals and filters at once, 5 placeholder rows; "Couldn't load the orders." · **Try
  again**.
- **Long content:** a 100-character client name is cut with "…" in the list and shown in full on the order page; 12 colours make 12
  rows; 11 shoe sizes scroll inside their table (a labelled region), never the page; 7-digit counts fit the tiles.

## Wording
- Words that change everywhere: To start → **Not started** (an order's status only); In cutting, In production, Ready to ship,
  Shipped, Ship → gone from orders; new **To deliver**, **Delivered**, **Cancelled**, **On the shelf now**. Verbs: **Mark
  delivered**, **Cancel units**, **Keep units**.
- Order page: "Order tracker" · "5 of 80 units delivered · 5 cancelled" · "To deliver" · "Delivered" · "Cancelled" · "Total" · "units" ·
  "On the shelf now" · "Out" · "To deliver: ordered, not delivered yet. On the shelf now is your finished stock; it isn't set aside
  for this order. Delivered and Cancelled units stay in the total." · "Denim Jacket by colour and size" · "Colour · size" · "Mark
  delivered" · "Cancel units" · "More actions for Denim Jacket" · "Move delivered back to To deliver".
- Windows: "Mark delivered · Denim Jacket" · "Move back to To deliver · Denim Jacket" · "Cancel units · Denim Jeans 5-pocket" · "How
  many units of each colour and size?" · "Up to 2" · "Boutique Lina · to deliver and on the shelf: Indigo S 2 · L 3" · "Delivered
  units leave your finished stock." · "These units go back on your shelf." · "Cancelled units stay on the order, marked Cancelled,
  and are not delivered. This can't be undone. Finished stock doesn't change." · "Keep units" · "Cancel 5 units" · "Move back" ·
  "Cancel".
- Toasts: "5 units of Denim Jacket marked delivered." · "5 units of Denim Jacket moved back to To deliver." · "5 units of Denim
  Jeans 5-pocket cancelled." · "Order deleted.".
- Orders list: "Open orders" · "To deliver" · "Delivered" · "Ordered" · "5 cancelled" · Status list "Open orders", "Delivered",
  "Cancelled", "All orders" · statuses "Not started", "In progress", "Delivered", "Cancelled".
- Delete order: "Delete order?" · "Finished stock isn't changed: the 5 units already delivered are not put back."
- Gone: "To start", "In cutting", "In production", "Ready to ship", "Shipped", "Ship", "Start cutting", "Mark ready", "Move to
  Sewing", "Move to Finishing", "Move back" (the three on the order page), "Tickets (2)", "See these units in Progress", "See
  Progress" (on the Orders list), "0 of 80 shipped", the delete refusal and "Open Progress".

## Phone layout
Board: INV-14 · Order page, Mark delivered, Cancel units, Orders list, Delete order · 375 (Invory App UI canvas)
- **Order page:** the title; the client line stacked (source label and city, then the phone, then "All orders of Boutique Lina");
  **Edit** and **Delete** side by side.
- **Tracker:** the status and "5 of 80 units delivered · 5 cancelled", the bar, **Total** first and full width, then the three
  counts two to a row.
- **Each product:** a band per colour and one line per size with only the counts that hold units and the shelf ("M · To deliver 20 ·
  Total 20 · On the shelf Out"), the Total line first. **Mark delivered**, **Cancel units** and **More** full width, one under
  another (44 px); the reason of a button that can't be pressed shows under it.
- **Windows:** full screen; the fields three to a row; **Cancel 5 units** and **Keep units** (or **Mark delivered** and **Cancel**) in
  the bar fixed at the bottom.
- **Orders list:** the two totals side by side, the search, **Filters** ("Filters · 1" when set) opening a full-height panel with
  Status and Client, then one card per order: name, status badge, date, "5 cancelled", the products, a small bar and "To deliver 70 ·
  Delivered 5".
- Every field and button is 44 px high on a phone. At 320 px nothing scrolls sideways.
