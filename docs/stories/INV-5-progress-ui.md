---
id: INV-5
title: Progress
type: ui
priority: Must
status: Approved
created: 2026-10-30
tags:
  - ui-card
---

# INV-5 · Progress — UI card

> [!abstract] Description
> A **Progress** page shows every unit of the open orders, phase by phase, in its colour and size: how many units are in each phase,
> where each product stands, and what is in one phase, of which order and client, and since when. Units can be moved on from there.
> Between In cutting and Ready to ship, each workshop keeps its own **production phases** (Sewing and Finishing to start with), so a
> jacket being sewn no longer reads "In cutting" for a week. Each bundle cut gets a **work ticket** with a QR code: scanning it moves
> the bundle to its next phase, up to Ready to ship.
> New: the Progress page (in the sidebar) with its phase tiles, a table per product and a view per phase; the work tickets and their
> printing; the **Scan** page (in the sidebar); the page Settings › Production phases. Changed: the order page (a column per phase,
> the new move buttons, the line's tickets), the move windows (Start cutting ends on printing the tickets), the Orders list (a column
> "In production") and Settings › Labels (a ticket size).
> **To be discussed:** order lines counted per colour and size (see "Orders per colour and size"). Until it is decided, every colour
> shown on Progress, the tickets and the order screens is drawn as it would be with colour.
> Left out on purpose: moving back by scan, shipping by scan, production for the shelf (a batch with no client ending with "Put in
> stock"), shipping from the shelf, moving several rows across orders at once, a board with a column per phase, and dragging. Progress
> and scans never change "In stock": an order's units stay the order's.

> [!info] How it looks today
> There is no production page. To know how many Indigo M jackets are being sewn, the owner opens Boutique Lina, then Amira Ben Salah,
> then Yassine Trabelsi, and adds up in her head. Nothing says what has waited longest.
> The order page shows each line's table: one row per size ordered, the columns To start · In cutting · Ready to ship · Shipped, a Total
> row, and under it Start cutting, Mark ready, Ship and the three Move back. Each opens a window "How many units of each size?" with
> "Up to 10" under each size; Start cutting and Move back to To start show the materials table. Sewing, finishing or a quality check
> don't exist.
> Order lines count by size only: "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)"; an order line has no colour. The Orders list shows
> per order To start · In cutting · Ready to ship · Shipped and the open-order totals at the top. Invory remembers the day of each cut,
> but not the day units were marked ready or shipped.

## Pages

Today is 30 Oct 2026. Every example uses three open orders, with the phases **Sewing** and **Finishing**:

| Order                     | Line                                                   | Units |
| ------------------------- | ------------------------------------------------------ | ----- |
| Boutique Lina · 15 Oct    | Denim Jacket · Indigo S 10 · M 20 · L 15 · XL 5        | 50    |
| Boutique Lina · 15 Oct    | Denim Jeans 5-pocket · Indigo S 5 · M 10 · L 10 · XL 5 | 30    |
| Amira Ben Salah · 28 Oct  | Denim Jacket · Black M 1                               | 1     |
| Amira Ben Salah · 28 Oct  | Denim Jeans 5-pocket · Indigo M 1                      | 1     |
| Yassine Trabelsi · 27 Oct | Denim Jacket · Light wash L 2 · XL 1                   | 3     |

Where the units are:

| Product · colour · size          | To start | In cutting | Sewing | Finishing | Ready to ship | Shipped | Total |
| -------------------------------- | -------- | ---------- | ------ | --------- | ------------- | ------- | ----- |
| Denim Jacket · Black M           | 1        | 0          | 0      | 0         | 0             | 0       | 1     |
| Denim Jacket · Indigo S          | 0        | 0          | 4      | 0         | 6             | 0       | 10    |
| Denim Jacket · Indigo M          | 6        | 4          | 6      | 4         | 0             | 0       | 20    |
| Denim Jacket · Indigo L          | 15       | 0          | 0      | 0         | 0             | 0       | 15    |
| Denim Jacket · Indigo XL         | 0        | 0          | 0      | 0         | 0             | 5       | 5     |
| Denim Jacket · Light wash L      | 0        | 2          | 0      | 0         | 0             | 0       | 2     |
| Denim Jacket · Light wash XL     | 0        | 1          | 0      | 0         | 0             | 0       | 1     |
| **Denim Jacket**                 | 22       | 7          | 10     | 4         | 6             | 5       | 54    |
| Denim Jeans 5-pocket · Indigo S  | 0        | 0          | 5      | 0         | 0             | 0       | 5     |
| Denim Jeans 5-pocket · Indigo M  | 11       | 0          | 0      | 0         | 0             | 0       | 11    |
| Denim Jeans 5-pocket · Indigo L  | 10       | 0          | 0      | 0         | 0             | 0       | 10    |
| Denim Jeans 5-pocket · Indigo XL | 5        | 0          | 0      | 0         | 0             | 0       | 5     |
| **Denim Jeans 5-pocket**         | 26       | 0          | 5      | 0         | 0             | 0       | 31    |
| **All open orders**              | 48       | 7          | 15     | 4         | 6             | 5       | 85    |

- Since when: Jeans Indigo S in Sewing since 26 Oct; Jacket Indigo S in Sewing since 27 Oct; Jacket Indigo M in Sewing since 28 Oct;
  Jacket Indigo M in cutting since 25 Oct; Light wash L and XL in cutting since 29 Oct; Indigo M in Finishing since 29 Oct; Indigo S
  ready to ship since 29 Oct; Indigo XL shipped 28 Oct.
- Every Indigo unit except Amira's jeans is Boutique Lina's; Black M is Amira's; Light wash is Yassine's.

### How the phases work
- The path: **To start** → **In cutting** → the workshop's own phases (here **Sewing** → **Finishing**) → **Ready to ship** →
  **Shipped**. To start, In cutting, Ready to ship and Shipped are always there; the workshop keeps 0 to 6 phases of its own between
  In cutting and Ready to ship, starting with Sewing and Finishing.
- Units move one phase at a time, forward or back, per colour and size. The forward button names where the units go: under To start
  **Start cutting** (takes the materials out of stock, as today); under In cutting **Move to Sewing**; under Sewing **Move to
  Finishing**; under the last own phase **Mark ready**; under Ready to ship **Ship**. With no phase of its own, In cutting has **Mark
  ready**, as today.
- **Move back** goes one phase back. Only Move back to To start returns materials, as today. Mark ready, Ship, the moves between own
  phases and their Move back never touch stock.
- Whichever page a move starts from (Progress or the order page), it is the same move, with the same window, limits, materials, toast
  and history.
- Every move is remembered with its day, so a row says since when its units are in their phase. Units already in a phase before INV-5
  read "before 30 Oct 2026".
- An order's units stay the order's: Ready to ship and Shipped never change "In stock". Progress never changes stock.
- A colour that is on an open order can't be taken off its product (Edit product, INV-1's Colours field): "Light wash is on 1 open
  order, so it can't be removed from Denim Jacket."
- Orders recorded before order lines had colours are test data and are dropped.

### Sidebar
- Two new entries after Orders: **Progress** with a factory icon, and **Scan** with a QR icon: Dashboard, Materials, Products,
  Restocks, Orders, **Progress**, **Scan**, Clients, Sales. Breadcrumbs: "Progress", "Progress › Sewing", "Scan".

### Progress page · all phases
Board: INV-5 · Progress · all phases by product, filters, empty, no match, error · desktop (Invory App UI canvas)
1. Header **Progress**, description "Every unit of your open orders, phase by phase, in its colour and size." No main button (Progress
   records nothing new); the outline link **Production phases**, with a gear icon, opens Settings › Production phases, and the
   outline button **Scan a ticket** opens the Scan page.
2. **Phase tiles**, one row, a group named "Phases": **All phases** · **To start** · **In cutting** · **Sewing** · **Finishing** ·
   **Ready to ship** · **Shipped**. Each tile: the phase name (14 px, grey) and its count in the number font (24 px) with "units" ("15
   units"). The chosen tile has a 2 px Denim border, the light Denim tint and a bold count, and is announced "current page". A tile at 0
   shows "0 units" in grey and still opens its view. The tiles wrap to two rows under 1100 px.
3. Filters: **Product** (with its own search, "All products"), **Colour** (swatch and name, "All colours"), **Client** (with its own
   search, the phone in grey, "All clients"), **Order** (with its own search, "Boutique Lina · 15 Oct 2026", "All open orders"). All are
   kept in the page address, they combine, and **Clear filters** shows once any is set.
4. With a filter set, under the filters: "Showing Indigo · 81 of 85 units" and **Clear filters**. The tiles and the tables follow the
   filters.
5. One card per product with units on open orders, by name A → Z, 10 products a page:
   - Header: the product name (bold, a link to its page), its SKU in the number font, "54 units on 3 open orders". On the right the
     link **Open orders of Denim Jacket** (the Orders list showing that product).
   - Table "Denim Jacket by phase": first column **Colour · size**; then **To start**, **In cutting**, **Sewing**, **Finishing**,
     **Ready to ship**, **Shipped**, **Total**. Rows grouped by colour in the order of Settings › Colours: a colour row (the colour tag
     and its subtotal in bold, on a light grey band), then its sizes in size order, indented, the size label in semibold. Only colours
     and sizes with units on open orders have rows. A **Total** row last, with a heavier line above it. Every figure in the number
     font, right-aligned; zeros in the readable grey (S-71). The figures aren't links.
   - A "One size" product: the colour rows only, no size rows.
6. Every figure, subtotal and total comes from the server; the page adds nothing up.

### Progress page · one phase
Board: INV-5 · Progress · one phase (Sewing), move window, refusals · desktop (Invory App UI canvas)
1. Breadcrumb "Progress › Sewing". The tiles stay on top, with **Sewing** chosen; the filters stay.
2. Heading **Sewing**, and the line "15 units · 2 products · 1 open order · oldest since 26 Oct 2026". Under it, in grey: "From In
   cutting. Next: Finishing."
3. Table "In Sewing", one row per order line × colour × size in the phase, oldest first: **Since** ("26 Oct 2026", under it "4 days" in
   grey; also "1 day", "today") · **Product** (the name, a link; the SKU in grey) · **Colour · size** (the colour tag and "S") ·
   **Units** (number font, bold) · **Tickets** ("T-0142"; two or more: "T-0142, T-0150"; in To start "—") · **Order** ("Boutique
   Lina", a link to the order; under it "ordered 15 Oct 2026") · the row's buttons: **Move to Finishing** (outline, 44 px tall, named
   "Move Denim Jacket Indigo M of Boutique Lina to Finishing") and a "⋯" menu named "More moves for Denim Jacket Indigo S, Boutique Lina"
   with **Move back to In cutting** and **Print tickets of this row**.
4. 25 rows a page.
5. The To start view: the button is **Start cutting**, and "Since" reads the order date ("ordered 15 Oct 2026", "15 days"). The Ready
   to ship view: **Ship**. The Shipped view: no forward button; the menu has **Move back to Ready to ship**; "Since" is the day shipped.
6. A deleted product's row: "Denim Jacket (deleted)", with no link; **Start cutting** can't be pressed, with the reason "Denim Jacket
   was deleted, so it can't be cut." in a tip the keyboard reaches; the other moves work.

### The move window, opened from Progress
Board: INV-5 · Progress · one phase (Sewing), move window, refusals · desktop (Invory App UI canvas)
- Today's move window, for the order line of the row, with a colour × size grid: title "Move to Finishing · Denim Jacket", under it
  "Boutique Lina · ordered 15 Oct 2026 · in Sewing: Indigo S 4 · M 6". The group "How many units of each colour and size?": one row per
  colour that has units in Sewing, a field per size that has units there, "Up to 6" under each. The field of the row clicked (Indigo M)
  has the focus; it starts empty.
- Start cutting and Move back to To start keep the materials table, its short-stock check and its messages, for the total typed.
- **Move units** → toast "6 units of Denim Jacket for Boutique Lina moved to Finishing." (Start cutting: "10 units of Denim Jacket for
  Boutique Lina moved to In cutting. Materials taken out of stock."). The window closes, the focus goes to the next row's button (or to
  the heading when the phase is empty), and the tiles and the row refresh.
- Example: Move to Finishing on Indigo M with 6 → the tiles read Sewing 9 · Finishing 10, the row leaves Sewing, and Boutique Lina's
  order page shows Indigo M: Sewing 0 · Finishing 10. Start cutting on Denim Jacket Indigo L with 10 takes Denim 12oz Indigo 19.8 m,
  Metal shank button 17mm 80 pc, Polyester thread 1,260 m and Woven brand label 10 pc, with the same refusal as on the order page.
- **Start cutting ends on a print step:** after **Move units**, the window shows "16 units of Denim Jacket moved to In cutting.
  Materials taken out of stock." and "2 tickets to print: T-0152 Indigo M · 6 units, T-0153 Indigo L · 10 units", with **Print 2
  tickets** (main) and **Done**. Print opens the tickets in a new tab with the browser's print window, as the labels do (INV-2).
- **Tickets follow the move windows**, so the paper and the screen never disagree: moving on takes units from the oldest tickets in
  the phase first; moving back takes the newest first. A whole ticket keeps its number and moves with its units. Moving part of a
  ticket splits it: the units moved get a new ticket, and the toast offers to print it: "4 units of Denim Jacket for Boutique Lina
  moved to Finishing. Ticket T-0142 was split: the units moved are on T-0157." with **Print ticket T-0157**. The units that stay keep
  T-0142, which now reads 2 units.
- Units moved back to To start leave their ticket; a ticket left with no unit is no longer used. When those units are cut again they
  get a new ticket.

### Work tickets
Board: INV-5 · Work tickets · print after Start cutting, the ticket, Scan page with a scanner, Tickets column · desktop (Invory App
UI canvas)
- A **work ticket** stands for a **bundle**: the units of one colour and size that one Start cutting cut for one order line. "Start
  cutting · Denim Jacket" with Indigo M 6 and L 10 makes two tickets: **T-0152** (Denim Jacket · Indigo M · 6 units · Boutique Lina)
  and **T-0153** (Indigo L · 10 units). Tickets are numbered per workshop (T-0001, T-0002 …).
- Tickets are made at Start cutting, one per colour and size cut. Units still to start have no ticket, so a scan never starts
  cutting: cutting is always the Start cutting window, with its materials table and its short-stock check.
- **The ticket** (105 × 74 mm on A4), always black on white:
  1. The top line: "Work ticket" and, large, **T-0142**.
  2. On the left the QR code (30 mm). On the right **Denim Jacket** (bold, at most two lines), "Indigo · M", **6 units** (large),
     "Boutique Lina · ordered 15 Oct 2026", and the SKU "PRD-JKT-001-IND-M" in the number font.
  3. The path, as the phases were when printed, with the phase it was in ticked: "☑ In cutting ☐ Sewing ☐ Finishing ☐ Ready to
     ship", and "Cut 30 Oct 2026 · printed 30 Oct 2026".
  4. The bottom line: "Scan to move it to the next phase." A split ticket adds "Split from T-0142".
  - No phone number and no price on a ticket: it lies on the workshop floor.
- The QR code opens the ticket in Invory with a secret code, so the phone's own camera opens the right screen; a scanner or the Scan
  page reads the same. The short number under it ("T-0142") can be typed instead.
- **Printed again** at any time: the order page's line has **Tickets (2)**, which opens a panel listing its tickets (number, colour ·
  size, units, phase, "Not printed yet" until printed) with **Print ticket T-0142** for each and **Print all**; the phase view's "⋯"
  menu has **Print tickets of this row**.
- **Ticket size**, in Settings › Labels (beside INV-2's label size): "A4 sheet, 8 tickets (2 × 4)" (the default) or "100 × 60 mm, one
  per label (label printer)".

### Scan page and the scan confirm
Board: INV-5 · Scan a ticket · camera, confirm, moved, finished, errors · 375 (Invory App UI canvas)
- **Scan** in the sidebar (and **Scan a ticket** on Progress): on a phone it opens the camera, with the line "Point the camera at the
  QR code on a work ticket." and under it "Or type the ticket number", a field and **Find ticket**. On any device the field "Scan or
  type a ticket number" takes a USB or Bluetooth scanner, which types like a keyboard; hint "A scanner works like typing. Each ticket
  opens its move to confirm." Under it, "Your scans today" (ticket, product, "→ Finishing", time).
- The phone's own camera app opens the ticket's link, which opens the same confirm in Invory.
- Any signed-in member of the workshop can scan. Not signed in: the sign-in page, then straight back to the scanned ticket.
- **One confirm per scan.** Title **Move to Finishing?**; "T-0142 · **6 units** · Denim Jacket · ■ Indigo · M · Boutique Lina
  (ordered 15 Oct 2026)"; the path "In cutting ✓ · Sewing (now) · **Finishing** · Ready to ship"; **Move 6 units to Finishing** (main,
  full width, 56 px tall) and **Not now**. The confirm never times out.
- **Move 6 units to Finishing** moves all the units of the bundle, one phase on, as the order page's move does (same history, same
  page figures). Moved: a green line "6 units of Denim Jacket · Indigo M moved to Finishing.", the camera or the field ready for the
  next ticket, and the move added to "Your scans today". Progress and the order page show it at once.
- **The finish:** scans move a bundle up to Ready to ship. Shipping stays the order page's **Ship**, because a parcel is made per
  client, often from several bundles.
- **No moving back by scan.** Moving back stays in the Move back windows of the order page and the phase view.

### Order page
Board: INV-5 · Order page and New order per colour, Settings › Production phases · desktop (Invory App UI canvas)
- Each line's table: first column **Colour · size**, grouped by colour as on Progress (a colour row with its subtotal, then its sizes);
  the columns To start · In cutting · Sewing · Finishing · Ready to ship · Shipped; the Total row as today.
- Under the Total row, lined up with the figures (as S-71): **Start cutting** · **Move to Sewing** · **Move to Finishing** · **Mark
  ready** · **Ship**, and below them each **Move back**. A button whose phase holds 0 can't be pressed (grey, as S-71).
- The line's heading chips: "Indigo S 10 · M 20 · L 15 · XL 5"; with two colours: "Black M 1 · Indigo S 1 · M 2".
- Under "Products": the link **See these units in Progress** (Progress showing this order).
- Each line has **Tickets (2)**, which opens the panel of its tickets (see Work tickets).
- The header, the status words ("Not started", "In progress", "Shipped") and "0 of 80 shipped" don't change.
- Example, Boutique Lina's Denim Jacket: Indigo S 0 · 0 · 4 · 0 · 6 · 0; M 6 · 4 · 6 · 4 · 0 · 0; L 15 · 0 · 0 · 0 · 0 · 0; XL 0 · 0 ·
  0 · 0 · 0 · 5; **Move to Sewing** can be pressed (4 in cutting).
- The Delete order refusal covers every phase: "Boutique Lina can't be deleted: 29 units are in production or ready to ship. Move
  them back to To start first."

### Orders per colour and size — to be discussed
Board: INV-5 · Order page and New order per colour, Settings › Production phases · desktop (Invory App UI canvas)
- **Not decided.** An order line has no colour today. This part is drawn so the rest of the card can be read; it is decided with
  you later, and Progress, the tickets and the order page follow that decision.
- As drawn: in New order and Edit order, a line's sizes become the grid "Quantity per colour and size · Denim Jacket": one row per
  colour of the product (in the order of Settings › Colours), one column per size, an empty field in each cell, **Total** on the right
  of each row and "Total 50 units" under the grid. A "One size" product: "Quantity per colour", one field per colour. Edit order: a
  started cell shows "At least 10" under it (as S-70, per colour and size).
- As drawn: the Orders list's products read "50 × Denim Jacket (Indigo S 10 · M 20 · L 15 · XL 5)", and the order page groups each
  line's table by colour.

### Orders list
- The columns To start · **In production** · Ready to ship · Shipped, where In production is In cutting plus the workshop's own
  phases; the totals "Open orders 3 · To start 48 units · In production 26 units · Ready to ship 6 units" and the link **See
  Progress**.

### Settings › Production phases
Board: INV-5 · Order page and New order per colour, Settings › Production phases · desktop (Invory App UI canvas)
- In the Settings menu, a new group **Production** with **Production phases**.
- Header **Production phases**, description "The phases your units go through between cutting and ready to ship. To start, In
  cutting, Ready to ship and Shipped are always there." Main button **Add phase**.
- A list of the whole path: the fixed phases in grey with a lock icon and "Always there" (To start, In cutting … Ready to ship,
  Shipped); the workshop's phases between them, each with its name, "Holds 15 units" or "Empty", and the buttons **Move up**, **Move
  down**, **Edit Sewing** and **Remove Sewing**. No dragging.
- **Add phase** and **Edit Sewing** window: **Name** (up to 24 characters), hint "A short verb or noun your team uses, such as Sewing or
  Quality check." · **Cancel** · **Save phase**.
- Up to 6 phases. A phase that holds units can't be moved or removed.

## States

### Progress · all phases
- **Default:** the tiles All phases 85 · To start 48 · In cutting 7 · Sewing 15 · Finishing 4 · Ready to ship 6 · Shipped 5; Denim
  Jacket "54 units on 3 open orders" with Indigo M 6 · 4 · 6 · 4 · 0 · 0 · 20; Denim Jeans 5-pocket "31 units on 2 open orders".
- **Empty:** "Nothing in production" · "When you record an order, its units show here phase by phase until they ship." · **New order**.
  The tiles show 0.
- **No match:** "No unit matches these filters." · "Try another colour or client, or clear the filters." · **Clear filters**.
- **Filtered:** "Showing Indigo · 81 of 85 units" · **Clear filters**; the tiles To start 47 · In cutting 4 · Sewing 15 · Finishing 4
  · Ready to ship 6 · Shipped 5.
- **Loading:** the header, the tiles' frames and the filters at once; the tile counts and 2 product cards as placeholders.
- **Error:** "Couldn't load the progress of your orders." · **Try again** (reloads the tiles and the tables).
- **Read-only:** nothing to type here.
- **Long content:** a 60-character product name wraps in the card header; 12 colours × 11 shoe sizes shows only the variants on open
  orders; 7-digit counts fit the tiles; 25 products make 3 pages.

### Progress · one phase
- **Default:** Sewing: Denim Jeans 5-pocket Indigo S 5 · Boutique Lina · since 26 Oct 2026 · 4 days; Denim Jacket Indigo S 4 · since
  27 Oct 2026 · 3 days; Denim Jacket Indigo M 6 · since 28 Oct 2026 · 2 days.
- **Empty:** "Nothing in Sewing" · "Units come here from In cutting with Move to Sewing." · **Show all phases**. To start empty:
  "Nothing to start" · "Every unit of your open orders has started."
- **No match:** "Nothing in Sewing matches these filters." · **Clear filters**.
- **Loading:** the heading and the table head at once; 5 placeholder rows.
- **Error:** "Couldn't load what is in Sewing." · **Try again**.
- **Can't be pressed:** **Start cutting** on a deleted product: "Denim Jacket was deleted, so it can't be cut." in a tip the keyboard
  reaches; its name includes the reason.
- **Moving:** the window's **Move units** shows a spinner and keeps its label.
- **Refused:** in the window, today's messages: "You can move at most 6 units of Indigo M." · "Enter at least one unit to move." ·
  "Size 38: Enter a whole number of units." · Start cutting short of stock: "Not enough stock to cut 20 units. You can cut at most 12
  now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer."
- **Changed meanwhile:** someone moved units of the line: the limit is the count saved now, and the window's "Up to" and the page
  refresh. A row whose units have all left: the window shows "These units have moved on since you opened this page." and **Close**.
- **Success:** toast "6 units of Denim Jacket for Boutique Lina moved to Finishing."; one unit: "1 unit of Denim Jacket for Amira Ben
  Salah moved to In cutting. Materials taken out of stock."; back: "4 units of Denim Jacket for Boutique Lina moved back to In
  cutting." From the order page, today's toasts without "for …", which the page already says.
- **Long content:** 40 rows make 2 pages; a 100-character client name is cut with "…", the full text on the order.

### Settings › Production phases
- **Default:** To start · In cutting · **Sewing** "Holds 15 units" · **Finishing** "Holds 4 units" · Ready to ship · Shipped.
- **No phase:** between In cutting and Ready to ship: "No phase of your own. Units go from In cutting straight to Ready to ship." ·
  **Add phase**.
- **Errors:** "Enter the phase's name." · "Keep the name to 24 characters or fewer." · "You already have a phase named "Sewing"." · "To
  start, In cutting, Ready to ship and Shipped are already there. Choose another name." · "You can have up to 6 phases."
- **Can't be pressed:** **Move up**, **Move down** and **Remove Sewing** while Sewing holds units: "Sewing holds 15 units, so it can't
  be moved or removed. Move them on or back first." (a tip the keyboard reaches).
- **Confirm:** **Remove Quality check?** · "Quality check is empty. It leaves Progress and every order page; units then go from
  Finishing to Ready to ship." · **Remove phase** · **Keep phase**.
- **Refused meanwhile:** someone moved units into it: "Quality check now holds 2 units, so it can't be removed." Nothing changes.
- **Success:** "Phase added." · "Phase updated." · "Phase removed." · "Phases reordered."

### Scan page and the scan confirm
- **Ready, phone:** the camera view with a frame; "Point the camera at the QR code on a work ticket."; under it "Or type the ticket
  number" · the field · **Find ticket**.
- **Ready, laptop:** the field "Scan or type a ticket number", with the focus; the hint "A scanner works like typing. Each ticket
  opens its move to confirm." With no camera, the field only.
- **Confirm:** **Move to Finishing?** … **Move 6 units to Finishing** · **Not now**.
- **Moving:** the button shows a spinner and keeps its label.
- **Moved:** "6 units of Denim Jacket · Indigo M moved to Finishing." and the next scan is ready.
- **Ready to ship (the finish):** "T-0139 is ready to ship. Its 6 units of Denim Jacket · Indigo S wait for Boutique Lina's shipment."
  · **Open the order** · **Scan another**. Nothing moves.
- **Shipped:** "T-0136 shipped on 28 Oct 2026. Nothing left to do." · **Scan another**.
- **Unknown code, or another workshop's:** "This code isn't a work ticket of your workshop. Check the ticket, or type its number." It
  never says that another workshop exists.
- **Number not found:** "No ticket T-0999 in your workshop. Check the number on the ticket."
- **Not a ticket number:** "A ticket number looks like T-0142. Check what you typed."
- **No longer used:** "T-0142 is no longer used: its units went back to To start on 30 Oct 2026. They get a new ticket when they are
  cut again."
- **Split:** the confirm moves what the ticket holds now, with the note "4 of its units moved apart on ticket T-0157."
- **Scanned twice quickly:** the confirm shows the bundle as it is now, with a warning line above the button: "T-0142 moved to
  Finishing 12 seconds ago (Admin). Scanning again moves it on to Ready to ship." The warning shows when the last move was less than 2
  minutes ago.
- **Moved meanwhile** (between the scan and the confirm): "T-0142 has moved since you scanned it: it is now in Finishing. Scan it
  again to move it on." Nothing moves.
- **Phases changed meanwhile:** "The phases have changed since you scanned. T-0142 now goes to Quality check." The button relabels;
  nothing has moved.
- **Product deleted:** the bundle moves as usual; the confirm reads "Denim Jacket (deleted)".
- **Camera refused:** "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the ticket number."
- **Couldn't move:** "Couldn't move T-0142. Check the connection and try again." · **Try again**.
- **Couldn't load the ticket:** "Couldn't load ticket T-0142. Try again." · **Try again**.
- **Print blocked:** "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again."
- **Your scans today, empty:** "No scan yet today."

### New order, Edit order and Edit product
- New order, per cell (INV-3's naming; part of "Orders per colour and size", to be discussed): "Indigo M: Enter a whole number from 0 to 1,000,000." · "Enter at least one unit of Denim
  Jacket." · "A line can hold up to 1,000,000 units."
- Edit order: "Indigo M can't be less than the 10 units already started or shipped." · changed meanwhile: "The sizes or colours of
  Denim Jacket have changed. Enter its quantities again."
- Edit product: "Light wash is on 1 open order, so it can't be removed from Denim Jacket."

## Wording
- Sidebar and breadcrumbs: "Progress" · "Progress › Sewing" · "Scan".
- Progress page: "Scan a ticket" · "Progress" · "Every unit of your open orders, phase by phase, in its colour and size." · "Production phases" · "Phases"
  (the tiles' group) · "All phases" · "To start" · "In cutting" · "Sewing" · "Finishing" · "Ready to ship" · "Shipped" · "15 units" ·
  "Product" · "All products" · "Colour" · "All colours" · "Client" · "All clients" · "Order" · "All open orders" · "Clear filters" ·
  "Showing Indigo · 81 of 85 units" · "54 units on 3 open orders" · "Open orders of Denim Jacket" · "Denim Jacket by phase" · "Colour ·
  size" · "Total" · "Nothing in production" · "When you record an order, its units show here phase by phase until they ship." · "New
  order" · "No unit matches these filters." · "Try another colour or client, or clear the filters." · "Couldn't load the progress of
  your orders." · "Try again".
- Phase view: "15 units · 2 products · 1 open order · oldest since 26 Oct 2026" · "From In cutting. Next: Finishing." · "In Sewing" ·
  "Since" · "4 days" · "1 day" · "today" · "ordered 15 Oct 2026" · "before 30 Oct 2026" · "Units" · "Order" · "Move to Finishing" ·
  "Start cutting" · "Mark ready" · "Ship" · "Move back to In cutting" · "Move back to Ready to ship" · "More moves for Denim Jacket
  Indigo S, Boutique Lina" · "Nothing in Sewing" · "Units come here from In cutting with Move to Sewing." · "Show all phases" ·
  "Nothing to start" · "Every unit of your open orders has started." · "Nothing in Sewing matches these filters." · "Couldn't load what
  is in Sewing." · "Denim Jacket (deleted)" · "Denim Jacket was deleted, so it can't be cut."
- Move window: "Move to Finishing · Denim Jacket" · "Boutique Lina · ordered 15 Oct 2026 · in Sewing: Indigo S 4 · M 6" · "How many
  units of each colour and size?" · "Up to 6" · "Cancel" · "Move units" · "These units have moved on since you opened this page." ·
  "Close", and today's messages.
- Toasts: "6 units of Denim Jacket for Boutique Lina moved to Finishing." · "1 unit of Denim Jacket for Amira Ben Salah moved to In
  cutting. Materials taken out of stock." · "4 units of Denim Jacket for Boutique Lina moved back to In cutting."
- Order page: "Colour · size" · "Move to Sewing" · "Move to Finishing" · "Move back" · "See these units in Progress" · "Boutique Lina
  can't be deleted: 29 units are in production or ready to ship. Move them back to To start first."
- Order form (to be discussed): "Quantity per colour and size · Denim Jacket" · "Quantity per colour" · "Total 50 units" · "At least
  10", and the messages above.
- Tickets: "Work ticket" · "T-0142" · "6 units" · "Boutique Lina · ordered 15 Oct 2026" · "Cut 30 Oct 2026 · printed 30 Oct 2026" ·
  "Scan to move it to the next phase." · "Split from T-0142" · Start cutting: "2 tickets to print: T-0152 Indigo M · 6 units, T-0153
  Indigo L · 10 units" · "Print 2 tickets" · "Done" · order page: "Tickets (2)" · "Not printed yet" · "Print ticket T-0142" · "Print
  all" · phase view: "Tickets" (column) · "Print tickets of this row" · the split toast "4 units of Denim Jacket for Boutique Lina moved
  to Finishing. Ticket T-0142 was split: the units moved are on T-0157." with the action "Print ticket T-0157" · Settings › Labels:
  "Ticket size" · "A4 sheet, 8 tickets (2 × 4)" · "100 × 60 mm, one per label (label printer)".
- Scan page: "Scan" · "Scan a ticket" · "Point the camera at the QR code on a work ticket." · "Or type the ticket number" · "Scan or
  type a ticket number" · "A scanner works like typing. Each ticket opens its move to confirm." · "Find ticket" · "Move to Finishing?"
  · "Move 6 units to Finishing" · "Not now" · "(now)" · "Your scans today" · "Scan another" · "Open the order" · "No scan yet today.",
  and the scan states' messages above.
- Orders list: "In production" · "See Progress".
- Settings › Production phases: "Production" (the menu group) · "Production phases" · "The phases your units go through between cutting
  and ready to ship. To start, In cutting, Ready to ship and Shipped are always there." · "Add phase" · "Always there" · "Holds 15
  units" · "Empty" · "Move up" · "Move down" · "Edit Sewing" · "Remove Sewing" · "Name" · "A short verb or noun your team uses, such as
  Sewing or Quality check." · "Save phase" · "No phase of your own. Units go from In cutting straight to Ready to ship.", and the
  errors, confirm and toasts above.
- The same verb through each flow: **Move to Finishing** → "Move to Finishing · Denim Jacket" → **Move units** → "… moved to
  Finishing."; **Remove Sewing** → "Remove Sewing?" → **Remove phase** → "Phase removed.".

## Phone layout
Board: INV-5 · Progress, one phase, move window, Settings · 375 (Invory App UI canvas)
- **Progress:** the title and description; the tiles two to a row (All phases full width first), 64 px tall; **Filters** ("Filters · 1"
  when a filter is set) opens a panel over the whole screen with Product, Colour, Client and Order; then one card per product: the name
  and SKU, "54 units on 3 open orders", then per colour a group (the colour tag and "50 units"), and per size one line of the phases
  that hold units: "S · Sewing 4 · Ready to ship 6", "M · To start 6 · In cutting 4 · Sewing 6 · Finishing 4". The Total line comes
  first ("Total · To start 22 · In cutting 7 · …").
- **One phase:** the tiles as above, the chosen one ringed; the heading and its line; one card per row: "Denim Jacket · ■ Indigo · M"
  as its title, "6 units" in bold, "Boutique Lina · ordered 15 Oct 2026" (a link), "In Sewing since 28 Oct 2026 · 2 days", **Move to
  Finishing** full width (44 px), and **Move back to In cutting** (outline) full width under it.
- **Move window:** full screen, the grid one colour at a time (the colour tag as the group's title, the size fields four to a row, as
  S-70), **Move units** and **Cancel** in a bar fixed at the bottom that never hides the field with the focus.
- **Order page:** each line's sizes as cards, grouped by colour, showing only the phases with units; the buttons full width.
- **Settings › Production phases:** one card per phase; the fixed phases flat and grey; **Move up**, **Move down**, **Edit** and
  **Remove** as a row of named 44 px icon buttons; **Add phase** full width.
- **Scan page** (board "INV-5 · Scan a ticket · camera, confirm, moved, finished, errors · 375"): it fills the screen: the title, the
  camera square (full width), the hint, the number field and **Find ticket**, then "Your scans today". The confirm is a panel from the
  bottom, full width, its buttons one under the other with **Move 6 units to Finishing** first (56 px tall). Every error is a line in
  that panel with its button; the camera stays usable under it. The camera is never the only way: the number field is always there.
- At 320 px nothing scrolls sideways; the tiles stay two to a row and the tables become cards.
