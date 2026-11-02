# INV-5 · Progress, production for the shelf — design spec (revision 3)
Status: proposal · Designer · 2026-11-01 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc) (version 69), row
"Progress, production for the shelf (INV-5 rev. 3)":
- "INV-5 rev. 3 · Progress for the shelf · all phases by product with the shelf, filters, empty, loading, error · desktop" (`project/Inv5ShelfOverview.dc.html`)
- "INV-5 rev. 3 · Progress for the shelf · one phase, Start batch, Put in stock, Undo batch, states · desktop" (`project/Inv5ShelfPhase.dc.html`)
- "INV-5 rev. 3 · Progress for the shelf · batch ticket, Scan page, Settings › Production phases · desktop" (`project/Inv5ShelfTickets.dc.html`)
- "INV-5 rev. 3 · Progress, one phase, Start batch, Put in stock, Settings, Scan · 375" (`project/Inv5ShelfPhone.dc.html`)

> **This replaces [INV-5-progress.md](INV-5-progress.md)** (revisions 1 and 2, where Progress moved the units of open orders and tickets named a
> client). That spec and its eight boards ("INV-5 · Progress …", "INV-5 · Work tickets …", "INV-5 · Scan a ticket …") describe the old model and
> stay on the canvas only as history. Still valid from before: the Scan page and its message wording (changed where noted), the move-window
> shape, the "since" rule, the phase tiles, Settings › Production phases (changed), S-53's materials rules.
>
> **The human's decisions of 2026-11-01:** production is not started from an order; Progress makes products for the finished stock; an order is
> covered from the shelf (checked, never held). Batches are for a product, colour and size and are started on Progress; materials are taken at
> the cut under S-53's rules; the workshop's phases end in **Put in stock**, which adds to finished stock; no To start and no Shipped phases, no
> Order or Client columns; **Undo batch** replaces Move back to To start; tickets carry product, colour, size and batch, no client, and are printed
> from Progress only; the Scan page moves batches forward; Settings › Production phases follows. "Not started" is an order status only; Progress
> never says "Delivered".

## Problem and user
The owner walks the floor with a phone, or sits at the cutting table, and asks: **"What am I making for the shelf right now, where is each batch,
and what is on the shelf?"** Today's Progress answers it per order, which no longer matches how the workshop works: she sews a stock of Indigo M
jackets, puts them on the shelf, and orders are served from there.

## How the model works (words used on screen)
- A **batch** is units of one product in one colour and one size, made together. **Start batch** (Progress) takes the materials out of stock
  (S-53's cut) and puts the batch **In cutting**. Typing a grid of colour × size units makes one batch per colour and size, each with its number
  (**B-0153**).
- A batch moves one phase at a time: **In cutting** (always first) → the workshop's own phases (Sewing, Finishing, 0 to 6) → **Put in stock**.
  Put in stock is the end, not a phase with a tile: it adds the units to the finished stock (INV-2/INV-3) and the batch leaves Progress.
- **Undo batch** (only while the batch is In cutting) takes it off Progress and gives back what was cut, from the amounts remembered at the cut
  (S-53). Later phases move back one step at a time until In cutting.
- Nothing is reserved: batches belong to no order. Progress shows **On the shelf now** beside each colour and size as a guide, and never links to
  orders or clients.
- The word "Shipped" is gone from Progress; "Not started" and "Delivered" never appear on it.

## Flow
1. The owner opens **Progress** (sidebar): tiles **All phases 26 · In cutting 7 · Sewing 15 · Finishing 4**; a card per product. Denim Jacket
   "21 units in 6 batches"; Indigo M: In cutting 4, Sewing 6, Finishing 4, **On the shelf now Out**.
2. **Start batch**: Denim Jacket, Indigo M 6 and Indigo L 10 (the shelf under each field: "Shelf Out", "Shelf 3"). The window shows the materials
   for 16 units (Denim 12oz Indigo 31.68 m, Metal shank button 17mm 128 pc, Polyester thread 2,016 m, Woven brand label 16 pc) and refuses with S-53's
   message if stock is short. Then "2 batches started": tickets **B-0153** (Indigo M · 6) and **B-0154** (Indigo L · 10) with **Print 2 tickets**.
3. The cutter ties each ticket to its bundle. At the machine Sami scans B-0147 with his phone: **Move to Finishing?** → **Move 4 units to Finishing**.
4. In **Finishing**, B-0148 (Indigo M, 4 units): **Put in stock** → "Put in stock · Denim Jacket", 4 units → "4 units of Denim Jacket · Indigo M put in
   stock." The shelf reads Indigo M 4; a restock "From production" is in Restocks (question 1).
5. A mistake in cutting: B-0150 (In cutting): "⋯" → **Undo batch** → "Undo batch B-0150?" with the materials that come back.

## Layout

### Sidebar
Unchanged from rev. 2: Dashboard, Materials, Products, Restocks, Orders, **Progress**, **Scan**, Clients, Sales. Breadcrumbs "Progress", "Progress ›
Sewing", "Scan".

### Progress, all phases (desktop)
Board: Progress for the shelf · overview
1. Header **Progress**, description "The batches you are making for your shelf, phase by phase, in their colour and size." Buttons: **Production
   phases** (outline, gear), **Scan a ticket** (outline), **Start batch** (main, plus).
2. Phase tiles in a navigation group named "Phases": **All phases** · **In cutting** · **Sewing** · **Finishing** (the workshop's own phases; wrap
   to two rows when there are more). Each: name, count in the number font, "units". The chosen tile: 2 px Denim border, light tint, bold count,
   "current page". Tiles follow the filters.
3. Filters: **Product** (searchable, "All products") and **Colour** (swatch and name, "All colours"). In the page address. No Client, no Order.
   With a filter: "Showing Indigo · 23 of 26 units" · **Clear filters**.
4. A card per product that has units in production, A → Z, 10 a page: name (link), SKU, "21 units in 6 batches", and the outline button **Start
   batch** (named "Start a batch of Denim Jacket", product pre-chosen). The table "Denim Jacket by phase": **Colour · size**, one column per phase,
   **In production** (the row total), a gap, **On the shelf now**. Colour rows on a band with subtotals; sizes indented; a **Total** row. Zeros grey; a
   shelf figure of 0 is the amber word **Out**. Colour rows and the Total row have no shelf figure. Only colours and sizes in production have rows.

### One phase (desktop)
Board: Progress for the shelf · one phase …
1. Breadcrumb "Progress › Sewing"; tiles stay; heading **Sewing**; "15 units · 3 batches · 2 products · oldest since 26 Oct 2026"; grey "From In
   cutting. Next: Finishing."
2. Table "In Sewing", one row per batch, oldest first: **Since** ("26 Oct 2026", "4 days") · **Batch** (B-0146, number font) · **Product** (link, SKU) ·
   **Colour · size** · **Units** · **Move to Finishing** (44 px, named "Move batch B-0146 Denim Jeans 5-pocket Indigo S to Finishing") and "⋯" named "More
   moves for batch B-0146" (**Move back to In cutting**, **Print ticket B-0146**). 25 rows a page.
3. **In cutting:** forward **Move to Sewing**; the menu has **Undo batch** and **Print ticket**; "Since" is the day the batch started. With no phase of
   its own, In cutting's button is **Put in stock**.
4. **The last phase** (Finishing): the button is **Put in stock** (main); the menu **Move back to Sewing**, **Print ticket**.
5. A deleted product's row reads "Denim Jacket (deleted)", no link; **Put in stock** can't be pressed, with the reason "Denim Jacket was deleted, so it can't
   be put in stock. Move the batch back to In cutting and undo it to return its materials." in a tip the keyboard reaches.

### Start batch (window)
- Title **Start batch**, "Make products for your shelf. Materials are taken out of stock now." **Product** (searchable; shows "Can produce now 112"). The
  group "How many units of each colour and size?": a row per colour (Settings › Colours order), a field per size, with the shelf under each ("Shelf 2" / amber
  "Shelf Out"). "Total 16 units · 2 batches (Indigo M 6, Indigo L 10)". The table "Materials for 16 units": Material · Taken · In stock · After (S-53's table,
  with its "Short by" column when stock is short). **Cancel** · **Start batch** (spinner while saving).
- Refusal wording is S-53's: "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or
  cut 12 or fewer." · "You can't cut any now: restock Metal shank button 17mm first."
- After saving the window ends on "2 batches started" · "16 units of Denim Jacket started. Materials taken out of stock." · "2 tickets to print:
  B-0153 · Indigo M · 6 units, B-0154 · Indigo L · 10 units" · **Print 2 tickets** (main) · **Done**. Print opens the tickets in a new tab (as the labels do).
- Also from a product's **Start batch** button on Progress (product pre-chosen). Not from an order.

### Moves, Put in stock, Undo batch (windows)
- **Move to Finishing · Denim Jacket**: "B-0149 · Indigo M · in Sewing: 6 units"; one field "How many units?" (Up to 6). Moving part of a batch splits it: the
  4 units moved get their own batch and ticket; the toast offers **Print ticket B-0155**. A whole batch keeps its number.
- **Put in stock · Denim Jacket**: "B-0148 · Indigo M · in Finishing: 4 units"; "How many units?" (Up to 4); note "Adds to your finished stock: Indigo M goes
  from 0 to 4. Recorded as a restock "From production"." Toast "4 units of Denim Jacket · Indigo M put in stock." with **See the restock**.
- **Undo batch B-0150?**: "Takes the 4 units of Denim Jacket · Indigo M off Progress and gives back what was cut: 7.92 m Denim 12oz Indigo, 32 pc Metal shank
  button 17mm, 504 m Polyester thread and 4 pc Woven brand label." **Undo batch** (red) · **Keep batch**. Toast "Batch B-0150 undone. Materials returned to
  stock." S-53's rules: nothing back for a material deleted, or whose unit changed, since the cut.

### The work ticket and the Scan page
Board: Progress for the shelf · batch ticket, Scan page, Settings
- A **ticket stands for a batch** (one colour and size), with the batch number: top line "Work ticket" and, large, **B-0147**; QR code (30 mm); **Denim Jacket**,
  "Indigo · S", **4 units**, "Started 23 Oct 2026", the SKU "PRD-JKT-001-IND-S"; the path "☑ In cutting ☐ Sewing ☐ Finishing ☐ Put in stock" and "Printed 30 Oct
  2026"; "Scan to move it to the next phase." A split adds "Split from B-0149". 105 × 74 mm on A4, black on white; **no client, phone or price**.
- Printed from Progress only: the end of Start batch, the "⋯" menu, a split's toast. Orders have no tickets. Ticket size stays in Settings › Labels.
- The QR opens the batch in Invory with a secret code; a scanner or the Scan page reads the same; the number can be typed.
- **Scan page** as rev. 2 with batch words: field "Scan or type a batch number"; hint "A scanner works like typing. Each ticket opens its move to
  confirm."; "Your scans today". One confirm per scan: **Move to Finishing?** · "B-0147 · **4 units** · Denim Jacket · Indigo · S · started 23 Oct 2026" · "In
  cutting ✓ · Sewing (now) · **Finishing** · Put in stock" · **Move 4 units to Finishing** (56 px) · **Not now**. **At the last phase the confirm reads Put 4 units
  in stock?** with the note "Adds to your finished stock: Indigo M goes from 0 to 4." and the button **Put 4 units in stock** (question 2).
- Scan messages: "4 units of Denim Jacket · Indigo S moved to Finishing." · "4 units of Denim Jacket · Indigo M put in stock." · "B-0139 was put in stock on 28
  Oct 2026. Nothing left to do." · "B-0150 was undone on 30 Oct 2026: its materials went back to stock. Nothing to move." · "This code isn't a work ticket of
  your workshop. Check the ticket, or type its number." · "No batch B-0999 in your workshop. Check the number on the ticket." · "A batch number looks like
  B-0142. Check what you typed." · twice quickly "B-0147 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock." · moved
  meanwhile · phases changed meanwhile · product deleted ("Denim Jacket was deleted, so B-0148 can't be put in stock.") · camera refused · couldn't move /
  load · "No scan yet today." (all in the board).

### Settings › Production phases
Board: Progress for the shelf · batch ticket, Scan page, Settings
- Header **Production phases**, "The phases a batch goes through between cutting and putting it in stock. In cutting and Put in stock are always there." **Add
  phase**. The list: **In cutting** (fixed, lock, "Always there. Start batch puts a batch here and takes the materials."), the workshop's phases (name, "Holds 15
  units" / "Empty", **Move up**, **Move down**, **Edit Sewing**, **Remove Sewing**), **Put in stock** (fixed, lock, "Always there. Ends a batch: its units are added
  to your finished stock."). Up to 6 of its own; a phase holding units can't be moved or removed; no dragging.
- Errors: "In cutting and Put in stock are already there. Choose another name." plus rev. 2's (empty, 24 characters, a name used, 6 phases). Empty list: "No phase
  of your own. Batches go from In cutting straight to Put in stock." Remove confirm: "Quality check is empty. It leaves Progress; batches then go from Finishing to Put
  in stock."

## States
Drawn on the boards; the wording is in them. In short: Progress default, filtered, no match ("No batch matches these filters." · "Try another product or colour, or
clear the filters."), empty ("Nothing in production" · "Start a batch to make products for your shelf. They are added to your finished stock when you put them in
stock." · **Start batch**), loading, error ("Couldn't load the progress of your production." · **Try again**), long content; a phase view default, empty ("Nothing in Sewing"
· "Batches come here from In cutting with Move to Sewing."), no match, loading, error ("Couldn't load what is in Sewing."), disabled (deleted product), moving,
refused, changed meanwhile, success toasts; Start batch with errors ("Choose a product." · "Enter at least one unit to start." · "Indigo M: Enter a whole number from 0 to
1,000,000." · "The colours or sizes of Denim Jacket have changed. Enter the units again." · "Couldn't start the batch. Try again.") and the S-53 refusal; Put in stock;
Undo batch; tickets; Scan; Settings.

## Accessibility
- **Contrast.** Chosen tile Denim `#2B3A86` on `#E9ECFA` 9.0:1 (dark 7.1:1); amber "Out" `#8A4405` on `#FDF0DC` 6.4:1 (dark 8.1:1); grey `#5F5F66` on white 6.3:1;
  field borders 3.2:1; the ticket is black on white (21:1).
- **Colour never alone.** Phases are words; "Out" is a word; the shelf caption under a field is text.
- **Structure.** The tiles are a navigation group named "Phases" with "current page"; each table has a caption ("Denim Jacket by phase", "In Sewing"), header cells and
  one Tab stop per row action; figures are not links. Row buttons are named with the batch: "Move batch B-0146 Denim Jeans 5-pocket Indigo S to Finishing".
- **Fields and windows.** Start batch fields are named "Indigo M units" and described by their shelf caption; the first field focuses on open, the product on a
  fresh window; dialogs trap focus and return it; errors are tied to their field and announced; saving keeps the button label with a spinner.
- **Scan.** The camera is never the only way (the number field is always there); the camera view is named "Camera: point it at the QR code"; the confirm is a dialog; the
  move button is 56 px; no time limit, no flashing; the QR has a 4-module quiet zone and prints at least 25 mm.
- **Targets (44 px)** on every phone control; **reflow** at 320 px: the tables become cards or lines.

## Phone (375 px)
Board: INV-5 rev. 3 · Progress, one phase, Start batch, Put in stock, Settings, Scan · 375
- **Progress:** title, **Start batch** full width, **Scan a ticket** and **Phases** side by side, tiles two to a row (All phases full width first), **Filters**, then a card per
  product: a band per colour, a line per size with the phases that hold units and the shelf ("M · In cutting 4 · Sewing 6 · Finishing 4 · On the shelf Out"), the Total line
  first, **Start batch** full width.
- **One phase:** a card per batch: product and batch number, colour · size, "4 units" in bold, "In Sewing since 27 Oct 2026 · 3 days", **Move to Finishing** and **More**
  full width (the last phase: **Put in stock**).
- **Start batch** and **Put in stock:** full screen, fields three to a row, **Start batch** or **Put in stock** and **Cancel** in the bar fixed at the bottom; the materials table is
  short (Material · Taken · After). **Undo batch:** a sheet from the bottom.
- **Settings:** a card per phase with 44 px named icon buttons. **Scan:** the camera square, the number field, **Find ticket**, and the confirm as a sheet from the bottom, its
  buttons one under another.

## For the product-owner
- The boards cover everything the human listed. The new card replaces "Order", "Client" and "Shipped" with the batch model; the words list in "How the model works" is the
  glossary to keep: Batch, Start batch, Put in stock, Undo batch, B-number.
- Acceptance wording to add: Start batch with Indigo M 6 and Indigo L 10 shows the materials of 16 units (31.68 m, 128 pc, 2,016 m, 16 pc), makes B-0153 and B-0154, takes the
  materials out of stock ("Can produce now" goes from 112 to 96: the buttons, 900 − 128 = 772, ⌊772 / 8⌋ = 96) and prints two tickets; Put in stock with
  4 on B-0148 raises the Denim Jacket's Indigo M shelf from 0 to 4 and the batch leaves Progress; Undo batch on B-0150 returns 7.92 m, 32 pc, 504 m and 4 pc.
- Also decide: what happens to the order units already in production when INV-5 is built (the BACKLOG note says materials already taken by cuts on orders belong to no batch; the
  tech-lead says how).
- INV-9's labels and INV-3's Restocks list now meet Progress: a restock "From production" with the reference "B-0148" (question 1).

## For the tech-lead
- **Values the server sends:** per product, colour and size: units per phase, total in production, the shelf stock; per phase: the batches (number, product, colour, size, units, since
  date, product deleted?); the phases list (fixed first and last, workshop's in between, units held); the Start batch grid (colours × sizes with shelf and capacity) and the materials
  preview for the total; batch tickets (number, product, colour, size, units, start and print dates, phase path as printed, split-from); the restock created by Put in stock.
- **Writes:** Start batch (materials per S-53; one batch per colour and size, a number each), move forward/back (with split), Put in stock (finished stock up, a restock "From
  production"), Undo batch (S-53 return from the batch's remembered amounts, only In cutting).
- **Costly or risky:** a batch has no order, so S-53's per-line cut records become per-batch; splitting keeps the materials proportion (S-53's `round4(per_unit × units)`); Put in stock
  must take the product's row lock like INV-2's writers; the phase rules (a phase with units can't move or go); the old `order_line_cuts` and tickets-per-order data; `Scan`
  moving a batch to stock is a stock write from a phone.

## Questions for the product-owner / human
1. **Does Put in stock leave a trace in Restocks?** (a) Yes: one restock per Put in stock, "From production", reference "B-0148", listed in Restocks and undoable with
   INV-3's Undo restock **recommended** (the shelf has one history of units arriving, and "Last restock" on the product page stays right) · (b) it only raises the shelf,
   no record. Default: (a). (If undone, the batch is not brought back; its materials stay used.)
2. **Does a scan finish a batch?** (a) Yes: at the last phase the confirm reads "Put 4 units in stock?" and adds to the shelf **recommended** (one gesture at the machine; a
   mistake is undone with Undo restock) · (b) scans stop one phase before, and Put in stock is only a button on Progress. Default: (a).
3. **One number for batch and ticket?** (a) Yes: the batch is B-0147 and its ticket carries B-0147 **recommended** · (b) separate batch and ticket numbers. Default: (a).
4. **Where can a batch be undone?** (a) Only while In cutting; later phases move back first **recommended** (a sewn jacket does not give its fabric back; S-53 had the same
   limit) · (b) from any phase. Default: (a).
5. **A planning aid on Progress.** The owner decides what to make by looking at the shelf and what clients wait for. (a) Progress and Start batch show only the shelf and
   what is in production, no order data (as decided) **recommended** · (b) add a small column "Waiting in open orders" per colour and size (a count, no client or order named)
   so she sees "wanted 20, on the shelf 0, in production 4". Default: (a); (b) can come as its own card.
6. **Start batch from the product page.** (a) Progress only, plus the button on each product card **recommended** · (b) also a button in the product's Sizes & colours card. Default: (a).
7. **Putting part of a batch in stock.** (a) Allowed: the field takes up to the batch's units and the rest stays (it splits, like any move) **recommended** · (b) only the whole batch.
   Default: (a).
