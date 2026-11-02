# INV-14 · Order tracker, covered from the shelf — design spec
Status: proposal, redrawn for the model of 2026-11-01 (production fills the shelf; an order is covered from it) · Designer · 2026-11-01 ·
Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc) (version 69), row "Order tracker, covered from the shelf
(INV-14)":
- "INV-14 · Order page · covered from the shelf: tracker, To deliver and On the shelf now, Mark delivered, Cancel units, Delete order, states
  · light and dark · desktop" (`project/Inv14OrderPage.dc.html`)
- "INV-14 · Orders list and client page · To deliver and Delivered, status filter, states · desktop" (`project/Inv14OrdersList.dc.html`)
- "INV-14 · Order page, Mark delivered, Cancel units, Orders list, Delete order · 375" (`project/Inv14Phone.dc.html`)

> **This replaces the tracker part of [INV-12-refactor-the-order.md](INV-12-refactor-the-order.md)** (units in progress and ready to
> ship, Mark delivered as a status move, the delete refusal). The client part and the product-search part of that spec still stand
> (INV-12 and INV-13 are approved). The three old boards "INV-12 · Order page …", "INV-12 · Orders list …" are marked SUPERSEDED on the
> canvas; "INV-12 · New order, order page, Cancel units, Orders list · 375" is still right for its New order phones only.
> The product-owner's text for this card is [INV-14-order-tracker-ui.md](../../stories/INV-14-order-tracker-ui.md); this spec draws it and
> adds what the card leaves to the designer.

## Problem and user
The owner asks one question about an order: **"What does this client still wait for, and can I deliver it now?"** Production is no longer
on the order: Progress (INV-5) makes products for the shelf, and an order is served from the finished stock. So the order page shows what
is left to deliver next to what the shelf holds today, and one gesture hands the units over (Mark delivered takes them off the shelf).

## Flow
1. Amira messages "is my jacket ready?". The owner opens her order. The tracker says **To deliver 2 · Delivered 0 · Cancelled 0 · Total
   2**; the Denim Jacket table shows Indigo M **To deliver 1**, **On the shelf now** the amber **Out**. Not ready: she answers "next week".
2. A restock or Progress's Put in stock fills the shelf. Next day the same row reads **On the shelf now 3**. She presses **Mark delivered**;
   the window offers Indigo M up to 1 (the smaller of 1 to deliver and 3 on the shelf). Toast "1 unit of Denim Jacket marked delivered."
   The shelf now reads 2, the order's status is **Delivered**.
3. Boutique Lina drops 5 jeans: **Cancel units** → Indigo L 5 → **Cancel 5 units**. The units stay on the order as Cancelled.
4. A mistake in a delivery: "⋯" → **Move delivered back to To deliver** puts the units back on the shelf.
5. An order entered by mistake: **Delete** → "Delete order?" → **Delete order**. Always possible; delivered units are not put back.

## Layout

### Order page (desktop)
Board: INV-14 · Order page …
1. Breadcrumb, title, INV-4's client line, **Edit**, **Delete**.
2. **Order tracker** card: heading, status badge, "**5 of 80** units delivered · 5 cancelled", a thin bar (To deliver / Delivered / Cancelled
   hatched, weighted by the counts; decoration only), four tiles in a row: **To deliver** 70 (info tone, truck icon) · **Delivered** 5
   (success tone, check-circle icon) · **Cancelled** 5 (dashed outline, ban icon) · **Total** 80 ("units ordered"). A grey line: "To deliver:
   ordered, not delivered yet. On the shelf now is your finished stock; it isn't set aside for this order. Delivered and Cancelled units
   stay in the total."
3. **Products**: a card per product: name (link), SKU, "50 units", status badge, "5 of 50 delivered". The table "Denim Jacket by colour and
   size": **Colour · size**, **To deliver**, **Delivered**, **Cancelled**, **Total**, a gap, **On the shelf now**. Colour rows on a light band
   with subtotals and no shelf figure; sizes indented; **Total** row. Zeros in the readable grey; a shelf figure of 0 is the amber word
   **Out** (words, not colour alone). "One size": colour rows only. Under the table: **Mark delivered** (main when the shelf covers at least
   one unit, otherwise grey with its reason beside it), **Cancel units** (outline, red text), "⋯" named "More actions for Denim Jacket"
   (**Move delivered back to To deliver**).
4. Gone: boxes and phase columns, Start cutting, Move to …, Mark ready, Ship, the Move backs, Tickets, "See these units in Progress",
   "0 of 80 shipped". Nothing links to Progress from an order.

### Mark delivered, Move back, Cancel units (windows)
- Same shape as INV-5's move window: title with the product, a grey line with the limits, the group "How many units of each colour and size?",
  a field per colour and size that can move, "Up to N" under each, the first field focused, the note, the buttons.
- **Mark delivered**: "Boutique Lina · to deliver and on the shelf: Indigo S 2 · L 3". Only sizes with something to deliver **and** on the shelf
  have a field (Indigo M has none). "Up to" is the smaller of the two. Note "Delivered units leave your finished stock." Buttons **Cancel** and
  **Mark delivered**.
- **Move back to To deliver**: the delivered sizes; note "These units go back on your shelf."; **Move back**.
- **Cancel units**: any size with units to deliver, whether or not the shelf covers it; amber note "Cancelled units stay on the order, marked
  Cancelled, and are not delivered. This can't be undone. Finished stock doesn't change."; **Keep units** and **Cancel 5 units** (red, the
  number follows what is typed). The window is the confirmation.
- Messages: see the card's States; the board lists them.

### Orders list and client page
Board: INV-14 · Orders list …
- Header with **New order** only. Two totals: **Open orders** 3 · **To deliver** 73 units (every open order, whatever is filtered; never
  Delivered or Cancelled units). Filters: search, **Status** (Open orders, Delivered, Cancelled, All orders), **Client**.
- Table: **Client** (date in grey) · **Products** · **Ordered** ("5 cancelled" under it) · **To deliver** · **Delivered** · **Status** (badge with
  icon: Not started, In progress, Delivered, Cancelled). The client page's orders table takes the same columns.

### Delete order
- "Delete order?" · "The order of **Boutique Lina** (2 products, 80 units) will be removed. Material stock and sales are not affected. This
  cannot be undone." and, only when units were delivered, "Finished stock isn't changed: the 5 units already delivered are not put back."
  Buttons **Cancel** and **Delete order** (red). No refusal, no Open Progress button.

## States
As in the card. Drawn: tracker default, dark, all delivered, all cancelled, nothing on the shelf covers the order (**Mark delivered** grey:
"Nothing on the shelf covers this order yet. Units arrive from Progress or a restock."), loading, error, the three windows, delete (with and
without the sentence), Orders list default / All orders / empty / none open / no match / loading / error, client page.

## Accessibility
- **Contrast.** Light: info `#2B3A86` on `#EEF0FA` 9.0:1; success `#0B5C4B` on `#E6F4EF` 7.0:1; neutral `#3F3F46` on `#F0F0EC` 9.1:1; amber
  "Out" `#8A4405` on `#FDF0DC` 6.4:1; danger text `#A3221A` on `#FDE8E6` 6.4:1; grey `#5F5F66` on white 6.3:1. Dark: 7.8 / 8.2 / 9.7 / 8.1 /
  8.0 / 6.8:1.
- **Colour never alone (1.4.1).** Each tile has a word and an icon; Cancelled is dashed with the ban icon and a hatched bar segment; "Out" is a
  word; the bar is decoration (hidden from screen readers) because the tiles carry the numbers.
- **Structure (1.3.1).** The tiles are a list named "Units of this order", read "To deliver, 70 units". The tables have a caption ("Denim
  Jacket by colour and size") and header cells; the shelf column header is "On the shelf now" and an empty cell on colour rows is empty, not "0".
- **Buttons that can't be pressed** stay reachable by keyboard and say why in text beside them; their description includes the reason.
- **Windows (2.4.3, 2.4.7).** Dialogs trap focus, Esc closes, focus returns to the button that opened them; errors are tied to their field and
  announced; the first wrong field gets the focus.
- **Targets (2.5.8, 44 px).** Fields, buttons and the "⋯" menu are 44 px on a phone.
- **Reflow.** At 320 px the tables become the lines of the phone layout; nothing scrolls the page sideways.

## Phone (375 px)
Board: INV-14 · Order page, Mark delivered, Cancel units, Orders list, Delete order · 375
- Tracker: status and the delivered line, the bar, **Total** first and full width, **To deliver** and **Delivered** side by side, **Cancelled**
  full width. A short grey line about the shelf.
- Each product: a band per colour (name, units), one line per size with the counts that hold units and the shelf ("M · To deliver 20 · Total 20 · On the
  shelf Out"), the Total line first; **Mark delivered**, **Cancel units**, **More** full width, one under another.
- Windows: full screen, fields three to a row, the buttons in a bar fixed at the bottom. Delete order: a sheet from the bottom.
- Orders list: the two totals side by side, search, **Filters**, a card per order with a small bar and "To deliver 70 · Delivered 5".

## What the shelf model changes in INV-12 and INV-13 (check, with findings)
Nothing in the boards of INV-12 or INV-13 contradicts the shelf model: the Client card and the Products card never mention production, the
search hint ("In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of stock can still be ordered.")
is exactly the shelf rule, and saving an order takes nothing off the shelf. Four small points:
1. **INV-13, Pages item 6:** "(M is out of stock, so it will be made)" says the order makes the product. Proposed: "(M is out of stock, so those units
   wait for the shelf)". The abstract already says it right ("the units simply wait until Progress or a restock puts them on the shelf").
2. **Board "INV-12 · New order · product search …"** (section 3, Edit order): its note reads "…already in progress, ready to ship, delivered or
   cancelled"; the approved card says "delivered or cancelled". The card wins; the board is stale on that one line.
3. **Two names for one fact:** INV-13 says "In stock 2" / "Out of stock"; INV-14 and Progress say "On the shelf now" / "Out". Both are the finished
   stock of INV-2/INV-3. Left as is (question 1).
4. **"Can produce now 112 from materials"** in the search stays true (Start batch on Progress uses the same figure), but it is the only place
   that hints at making things when taking an order; it is a small grey second line and needs no change.
- **Interaction with INV-3:** Mark delivered lowers the shelf, so an older restock may no longer be undoable ("This restock can't be undone: …
  has 2 units on the shelf, fewer than the 8 it added."). INV-3's message already handles it; the product page has no trail of deliveries.

## For the product-owner
- Accept the card as drawn; the boards match its wording word for word. Items I added that the card does not say: the disabled reason "Nothing
  on the shelf covers this order yet. …" (the card has it), the dark-mode tile tones, the phone arrangement of the tiles, the sentence under the
  tracker.
- Fix the INV-13 line in point 1 above before the development card.
- Acceptance wording to add: Mark delivered on Boutique Lina's Denim Jacket with Indigo S 2 and L 3 shows "5 units of Denim Jacket marked
  delivered.", the tracker reads To deliver 65 · Delivered 10, and the Denim Jacket page shows Indigo S 0 · L 0 on the shelf; Move delivered
  back gives them back.

## For the tech-lead
- **Values the server sends:** per order, per line and per colour × size: to deliver, delivered, cancelled, total; the shelf stock of that colour and
  size; the order status code and label (Not started, In progress, Delivered, Cancelled); the Orders list totals (open orders, to deliver) and the
  status filter options. Weights for the bar are the counts.
- **Writes:** Mark delivered (finished stock down, per colour and size, never below 0), its move back (stock up), Cancel units (to deliver down,
  cancelled up, no stock), Delete order (no stock effect). Row lock on the product's finished stock like INV-2's writers.
- **Costly or risky:** two people marking the last units at once (the second sees "The shelf or this order changed since you opened this page.");
  migrating orders with units in cutting, ready to ship or shipped (Shipped → Delivered, the rest → To deliver; the materials already taken belong to
  no batch); every test of the old stage words and buttons.

## Questions for the product-owner / human
1. **One name for the shelf figure.** (a) Keep "In stock" in the search (INV-13) and "On the shelf now" on the order page and Progress **recommended**
   (the first asks "do we have it?", the second "what can I hand over?") · (b) "On the shelf now" everywhere, INV-13 reworded · (c) "In stock" everywhere,
   including the order table. Default: (a).
2. **A trace of deliveries.** The Delivered count has no date or user. (a) None in INV-14 (as the card) **recommended** · (b) the order page lists
   "Delivered 30 Oct 2026 · 5 units · Admin" per delivery (a small history; needs a stored delivery per Mark delivered) · (c) a line on the product
   page. Default: (a).
3. **Out of stock on the order page.** (a) Nothing more than the amber **Out** **recommended** · (b) a link "Make more" opening Progress's Start batch for
   that product. Cutting stays on Progress either way. Default: (a).
