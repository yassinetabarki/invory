---
id: INV-5
title: Progress, production for the shelf
type: ui
priority: Must
status: Approved
created: 2026-10-30
tags:
  - ui-card
---

# INV-5 · Progress, production for the shelf — UI card

> [!abstract] Description
> **This card replaces the version validated on 2026-10-30**, where Progress moved the units of open orders and the tickets named a
> client. On 2026-11-01 you decided that a finished product is not tied to an order: the workshop makes products **for the shelf**,
> and an order is served from the finished stock. So Progress now follows **batches**: units of one product in one colour and one
> size, made together. The owner starts a batch (the materials leave stock), moves it through the phases, and **puts it in stock**,
> which adds the units to the finished stock. Each batch has a **work ticket** with a QR code and its own number (**B-0147**);
> scanning the ticket moves the batch to its next phase.
> New: the Progress page (in the sidebar) with its phase tiles, a table per product and a view per phase; **Start batch**; **Put in
> stock**; **Undo batch**; the work tickets and their printing; the **Scan** page (in the sidebar); the page Settings › Production
> phases. Changed: Settings › Labels (a ticket size).
> Cutting, phases, batches and tickets are on Progress only; the order page has none of them (INV-14).
> Left out on purpose: any link between a batch and an order or a client, setting shelf stock aside, a count of what open orders wait
> for, starting a batch from an order or from the product page, undoing a batch after cutting, a board with a column per phase,
> dragging, and moving several batches at once.

> [!info] How it looks today
> There is no production page. The owner cuts from an order: the order page shows each line's table (To start · In cutting · Ready to
> ship · Shipped) with **Start cutting**, **Mark ready**, **Ship** and the Move back buttons. To know how many Indigo M jackets are being
> made she opens every order and adds up in her head. Sewing, finishing or a quality check don't exist, and a pile of finished
> jackets only reaches the shelf when someone types a count in **Update stock** (or, with INV-3, makes a restock).
> Cutting takes the materials out of stock (S-53): 30 jackets take 59.4 m Denim 12oz Indigo, 240 pc Metal shank button 17mm, 3,780 m
> Polyester thread and 30 pc Woven brand label.

## Pages

Today is 30 Oct 2026. Every example uses these batches, with the phases **Sewing** and **Finishing**:

| Batch  | Product · colour · size          | Units | Phase      | Since       |
| ------ | -------------------------------- | ----- | ---------- | ----------- |
| B-0146 | Denim Jeans 5-pocket · Indigo S  | 5     | Sewing     | 26 Oct 2026 |
| B-0147 | Denim Jacket · Indigo S          | 4     | Sewing     | 27 Oct 2026 |
| B-0149 | Denim Jacket · Indigo M          | 6     | Sewing     | 28 Oct 2026 |
| B-0148 | Denim Jacket · Indigo M          | 4     | Finishing  | 29 Oct 2026 |
| B-0150 | Denim Jacket · Indigo M          | 4     | In cutting | 25 Oct 2026 |
| B-0151 | Denim Jacket · Light wash · L    | 2     | In cutting | 29 Oct 2026 |
| B-0152 | Denim Jacket · Light wash · XL   | 1     | In cutting | 29 Oct 2026 |

- That is 26 units: In cutting 7 · Sewing 15 · Finishing 4. The Denim Jacket has 21 units in 6 batches.
- The shelf (finished stock, S · M · L · XL): Denim Jacket Indigo 2 · 0 · 3 · 1, Light wash 0 · 0 · 0 · 0; Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0.
  The Denim Jacket's "Can produce now" is 112 (bottleneck Metal shank button 17mm).

### How batches work
- A **batch** is units of one product in one colour and one size. **Start batch** typed for a grid of colours and sizes makes **one
  batch per colour and size**, each with its number (**B-0153**, **B-0154** …), numbered per workshop.
- **The path:** **In cutting** (always first) → the workshop's own phases (here **Sewing** → **Finishing**, 0 to 6) → **Put in stock**.
  Put in stock is the end, not a phase with a tile: it adds the units to the finished stock and the batch leaves Progress.
- **Start batch** takes the materials out of stock, with S-53's rules (below), and puts the batch In cutting.
- A batch moves **one phase at a time**, forward by the button that names where it goes (**Move to Sewing**, **Move to Finishing**,
  **Put in stock**), back by **Move back to …**. Moving part of a batch **splits** it: the units moved become a batch of their own,
  with a new number and ticket; the units that stay keep the number.
- **Undo batch** is possible **only while the batch is In cutting**: it takes the batch off Progress and gives back what was cut, from
  the amounts remembered at the cut. A batch in a later phase moves back to In cutting first (a sewn jacket does not give its fabric back).
- Only Start batch and Undo batch touch materials. Moves between phases never do.
- **Put in stock** adds the units to the finished stock of their colour and size and writes a **restock** (INV-3): Reference = the batch
  number ("B-0148"), Note = "From production". It appears in Restocks and can be undone there like any restock; undoing the restock
  takes the units off the shelf but does not bring the batch back, and its materials stay used.
- **Nothing is reserved and nothing is linked to orders.** A batch belongs to no order. Progress shows **On the shelf now** beside
  each colour and size as a guide for what to make, and never mentions an order or a client. The words "Not started" and "Delivered"
  never appear on it.
- Every move is remembered with its day, so a row says since when its batch is in its phase.
- **Edit product:** a colour with units in production can't be taken off its product: "Light wash has 3 units in production, so it
  can't be removed from Denim Jacket." A product with units in production can't move to a category with other sizes: "Denim Jacket has
  units in production, so its category can't change to one with other sizes." with S-58's ending ("Keep a category with sizes S, M, L,
  XL.").

### S-53's materials rules (unchanged, now for a batch)
- Starting a batch of N units needs the product's materials for N units from stock. If stock is short the batch is refused with the
  message: "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut
  12 or fewer." When nothing can be cut: "You can't cut any now: restock Metal shank button 17mm first." Every short material shows its
  "Short by".
- The amounts are remembered per batch at the cut. Undo returns them exactly, nothing for a material deleted or whose unit changed
  since. A product with no composition can be started: nothing is taken and nothing comes back.
- Example: 16 units take 31.68 m Denim 12oz Indigo, 128 pc Metal shank button 17mm, 2,016 m Polyester thread and 16 pc Woven brand
  label; "Can produce now" goes from 112 to 96 (900 − 128 = 772 buttons, ⌊772 / 8⌋ = 96).

### Sidebar
- Two entries after Orders: **Progress** (factory icon) and **Scan** (QR icon): Dashboard, Materials, Products, Restocks, Orders,
  **Progress**, **Scan**, Clients, Sales. Breadcrumbs: "Progress", "Progress › Sewing", "Scan".

### Progress page · all phases
Board: INV-5 rev. 3 · Progress for the shelf · all phases by product with the shelf, filters, empty, loading, error · desktop (Invory App UI canvas)
1. Header **Progress**, description "The batches you are making for your shelf, phase by phase, in their colour and size." Buttons:
   **Production phases** (outline, gear icon), **Scan a ticket** (outline) and **Start batch** (main, plus icon).
2. **Phase tiles**, a group named "Phases": **All phases** 26 · **In cutting** 7 · **Sewing** 15 · **Finishing** 4 (the workshop's own
   phases; they wrap to two rows when there are more). Each tile: the name and the count in the number font with "units". The chosen
   tile has a 2 px Denim border, the light tint, a bold count and is announced "current page". A tile at 0 still opens its view. The
   tiles follow the filters.
3. Filters: **Product** (searchable, "All products") and **Colour** (swatch and name, "All colours"). Kept in the page address. With a
   filter: "Showing Indigo · 23 of 26 units" and **Clear filters**. There is no Client and no Order filter.
4. One card per product that has units in production, A → Z, 10 a page:
   - header: the name (a link), the SKU (number font), "21 units in 6 batches", and the outline button **Start batch** (named "Start a
     batch of Denim Jacket", product chosen);
   - the table "Denim Jacket by phase": **Colour · size**, one column per phase (In cutting · Sewing · Finishing), **In production**
     (the row total), a gap, and **On the shelf now**. Colours in the order of Settings › Colours: a colour row on a light band with its
     subtotals (no shelf figure), its sizes indented, a **Total** row with a heavier line. Only colours and sizes in production have
     rows. Zeros in the readable grey; a shelf figure of 0 is the amber word **Out**. A "One size" product: colour rows only.
   - Example, Indigo M: In cutting 4 · Sewing 6 · Finishing 4 · In production 14 · On the shelf now **Out**; Indigo S: Sewing 4 · In
     production 4 · On the shelf now 2.
5. Every figure comes from the server; the page adds nothing up. The figures aren't links.

### Progress page · one phase
Board: INV-5 rev. 3 · Progress for the shelf · one phase, Start batch, Put in stock, Undo batch, states · desktop (Invory App UI canvas)
1. Breadcrumb "Progress › Sewing"; the tiles stay with **Sewing** chosen; the filters stay.
2. Heading **Sewing** and "15 units · 3 batches · 2 products · oldest since 26 Oct 2026"; in grey: "From In cutting. Next: Finishing."
3. Table "In Sewing", one row per batch, oldest first, 25 a page: **Since** ("26 Oct 2026", under it "4 days"; "1 day"; "today") · **Batch**
   (B-0146, number font) · **Product** (name, a link; the SKU in grey) · **Colour · size** · **Units** (bold) · the forward button
   **Move to Finishing** (outline, 44 px, named "Move batch B-0146 Denim Jeans 5-pocket Indigo S to Finishing") and a "⋯" menu named
   "More moves for batch B-0146" with **Move back to In cutting** and **Print ticket B-0146**.
4. **In cutting:** the forward button is **Move to Sewing**; the menu has **Undo batch** and **Print ticket**; "Since" is the day the batch
   started. With no phase of the workshop's own, In cutting's button is **Put in stock**.
5. **The last phase** (Finishing): the button is **Put in stock** (main); the menu has **Move back to Sewing** and **Print ticket**.
6. A deleted product's row reads "Denim Jacket (deleted)", with no link; **Put in stock** can't be pressed, with the reason "Denim Jacket
   was deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials." in a tip the
   keyboard reaches.

### Start batch (window)
Board: INV-5 rev. 3 · Progress for the shelf · one phase, Start batch, Put in stock, Undo batch, states · desktop (Invory App UI canvas)
- Opened by **Start batch** on Progress or on a product's card (product chosen). Not from an order and not from the product page.
- Title **Start batch**, "Make products for your shelf. Materials are taken out of stock now." **Product** (searchable; shows "Can
  produce now 112"). The group "How many units of each colour and size?": a row per colour (Settings › Colours order), a field per
  size, with the shelf under each ("Shelf 2" or the amber "Shelf Out"). Under the grid: "Total 16 units · 2 batches (Indigo M 6,
  Indigo L 10)".
- The table "Materials for 16 units": **Material · Taken · In stock · After** (31.68 m Denim 12oz Indigo, 128 pc Metal shank button
  17mm, 2,016 m Polyester thread, 16 pc Woven brand label), with S-53's "Short by" column when stock is short.
- Buttons **Cancel** and **Start batch** (a spinner while saving). The refusal is S-53's (above).
- It then shows "2 batches started" · "16 units of Denim Jacket started. Materials taken out of stock." · "2 tickets to print: B-0153 ·
  Indigo M · 6 units, B-0154 · Indigo L · 10 units" · **Print 2 tickets** (main) · **Done**. Print opens the tickets in a new tab with
  the browser's print window, as the labels do (INV-2).
- Example: "Can produce now" goes from 112 to 96; the shelf is unchanged.

### Moves, Put in stock and Undo batch (windows)
Board: INV-5 rev. 3 · Progress for the shelf · one phase, Start batch, Put in stock, Undo batch, states · desktop (Invory App UI canvas)
- **Move to Finishing · Denim Jacket:** the grey line "B-0149 · Indigo M · in Sewing: 6 units"; one field "How many units?" ("Up to 6"),
  empty, focused. Moving 4 of the 6 splits the batch: the 4 units become **B-0155** with a new ticket, B-0149 keeps 2 and its number.
  Toast: "4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was split: the units moved are on B-0155." with **Print
  ticket B-0155**. A whole batch keeps its number: "6 units of Denim Jacket · Indigo M moved to Finishing." The same window serves
  **Move back to …**.
- **Put in stock · Denim Jacket:** "B-0148 · Indigo M · in Finishing: 4 units"; "How many units?" ("Up to 4"); the note "Adds to your
  finished stock: Indigo M goes from 0 to 4. Recorded as a restock "From production"."; **Cancel** and **Put in stock**. Part of a batch
  may be put in stock: the units left keep the batch number and its ticket reads fewer units. Toast: "4 units of Denim Jacket · Indigo M
  put in stock." with **See the restock**. The batch leaves Progress, the Denim Jacket's Indigo M shelf reads 4, and Restocks lists a
  restock with Reference "B-0148" and Note "From production".
- **Undo batch B-0150?** (a sheet, only for a batch In cutting): "Takes the 4 units of Denim Jacket · Indigo M off Progress and gives
  back what was cut: 7.92 m Denim 12oz Indigo, 32 pc Metal shank button 17mm, 504 m Polyester thread and 4 pc Woven brand label."
  **Undo batch** (red) and **Keep batch**. Toast: "Batch B-0150 undone. Materials returned to stock."

### Work tickets
Board: INV-5 rev. 3 · Progress for the shelf · batch ticket, Scan page, Settings › Production phases · desktop (Invory App UI canvas)
- A **work ticket** stands for a **batch** (one colour and one size), and carries the batch's number: **B-0147**. One number for both.
- **The ticket** (105 × 74 mm on A4), always black on white:
  1. The top line: "Work ticket" and, large, **B-0147**.
  2. On the left the QR code (30 mm). On the right **Denim Jacket** (bold, at most two lines), "Indigo · S", **4 units** (large),
     "Started 23 Oct 2026", and the SKU "PRD-JKT-001-IND-S" in the number font.
  3. The path as the phases were when printed, the current one ticked: "☑ In cutting ☐ Sewing ☐ Finishing ☐ Put in stock", and "Printed
     30 Oct 2026".
  4. The bottom line: "Scan to move it to the next phase." A split batch adds "Split from B-0149".
  - **No client, no phone number and no price** on a ticket: it lies on the workshop floor.
- The QR code opens the batch in Invory with a secret code; the phone's own camera, a scanner or the Scan page reads the same. The
  batch number can be typed instead.
- **Printed** from Progress only: at the end of Start batch, from the "⋯" menu (**Print ticket B-0147**), and from a split's toast.
  Orders have no tickets.
- **Ticket size**, in Settings › Labels (beside INV-2's label size): "A4 sheet, 8 tickets (2 × 4)" (the default) or "100 × 60 mm, one per
  label (label printer)".

### Scan page and the scan confirm
Board: INV-5 rev. 3 · Progress for the shelf · batch ticket, Scan page, Settings › Production phases · desktop (Invory App UI canvas); INV-5 rev. 3 · Progress, one phase, Start batch, Put in stock, Settings, Scan · 375
- **Scan** in the sidebar (and **Scan a ticket** on Progress): on a phone it opens the camera, with "Point the camera at the QR code on
  a work ticket." and under it "Or type the batch number", a field and **Find ticket**. On any device the field "Scan or type a batch
  number" takes a USB or Bluetooth scanner, which types like a keyboard; hint "A scanner works like typing. Each ticket opens its move
  to confirm." Under it, "Your scans today" (batch, product, "→ Finishing", time).
- Any signed-in member of the workshop can scan. Not signed in: the sign-in page, then straight back to the scanned ticket.
- **One confirm per scan.** Title **Move to Finishing?**; "B-0147 · **4 units** · Denim Jacket · Indigo · S · started 23 Oct 2026"; the
  path "In cutting ✓ · Sewing (now) · **Finishing** · Put in stock"; **Move 4 units to Finishing** (main, full width, 56 px tall) and **Not
  now**. The confirm never times out.
- **At the last phase a scan finishes the batch:** the title reads **Put 4 units in stock?**, with the note "Adds to your finished stock:
  Indigo M goes from 0 to 4." and the button **Put 4 units in stock**. It is the same Put in stock as on Progress, with the same restock.
- **No moving back by scan.** Moving back stays in the windows on Progress.

### Settings › Production phases
Board: INV-5 rev. 3 · Progress for the shelf · batch ticket, Scan page, Settings › Production phases · desktop (Invory App UI canvas)
- In the Settings menu, a group **Production** with **Production phases**.
- Header **Production phases**, description "The phases a batch goes through between cutting and putting it in stock. In cutting and Put
  in stock are always there." Main button **Add phase**.
- The list of the whole path: **In cutting** (grey, lock icon, "Always there. Start batch puts a batch here and takes the materials."),
  the workshop's phases (name, "Holds 15 units" or "Empty", **Move up**, **Move down**, **Edit Sewing**, **Remove Sewing**), and **Put in
  stock** (grey, lock icon, "Always there. Ends a batch: its units are added to your finished stock."). No dragging.
- **Add phase** and **Edit Sewing** window: **Name** (up to 24 characters), hint "A short verb or noun your team uses, such as Sewing or
  Quality check." · **Cancel** · **Save phase**.
- Up to 6 phases of its own. A phase that holds units can't be moved or removed.

## States

### Progress · all phases
- **Default:** the tiles All phases 26 · In cutting 7 · Sewing 15 · Finishing 4; Denim Jacket "21 units in 6 batches"; Denim Jeans
  5-pocket "5 units in 1 batch".
- **Filtered:** "Showing Indigo · 23 of 26 units" · **Clear filters**; the tiles In cutting 4 · Sewing 15 · Finishing 4.
- **No match:** "No batch matches these filters." · "Try another product or colour, or clear the filters." · **Clear filters**.
- **Empty:** "Nothing in production" · "Start a batch to make products for your shelf. They are added to your finished stock when you put
  them in stock." · **Start batch**. The tiles show 0.
- **Loading:** the header, the tiles' frames and the filters at once; the tile counts and 2 product cards as placeholders.
- **Error:** "Couldn't load the progress of your production." · **Try again**.
- **Long content:** a 60-character product name wraps in the card header; 12 colours × 11 shoe sizes show only the variants in
  production; 7-digit counts fit the tiles; 25 products make 3 pages.

### Progress · one phase
- **Default:** as in Pages (Sewing: B-0146, B-0147, B-0149).
- **Empty:** "Nothing in Sewing" · "Batches come here from In cutting with Move to Sewing." · **Show all phases**.
- **No match:** "Nothing in Sewing matches these filters." · **Clear filters**.
- **Loading:** the heading and the table head at once; 5 placeholder rows. **Error:** "Couldn't load what is in Sewing." · **Try again**.
- **Can't be pressed:** **Put in stock** on a deleted product (reason above).
- **Moving:** the window's button shows a spinner and keeps its label.
- **Refused in a move window:** "You can move at most 6 units of this batch." · "Enter at least one unit to move." · "Enter a whole number of units."
- **Changed meanwhile:** someone moved the batch: "These units have moved on since you opened this page." and **Close**.
- **Success:** the toasts above.

### Start batch
- **Errors:** "Choose a product." · "Enter at least one unit to start." · "Indigo M: Enter a whole number from 0 to 1,000,000." · "The colours
  or sizes of Denim Jacket have changed. Enter the units again." · "Couldn't start the batch. Try again." and S-53's refusal. Everything
  typed stays.
- **Printing blocked:** "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again."

### Put in stock and Undo batch
- **Put in stock errors:** "Enter at least one unit to put in stock." · "You can put at most 4 units of this batch in stock." · "Enter a whole
  number of units." · "Couldn't put the units in stock. Try again."
- **Undo errors:** the batch moved on meanwhile: "B-0150 has moved on since you opened this page, so it can't be undone. Move it back to In
  cutting first." · "Couldn't undo the batch. Try again."

### Settings › Production phases
- **Default:** In cutting · **Sewing** "Holds 15 units" · **Finishing** "Holds 4 units" · Put in stock.
- **No phase of its own:** "No phase of your own. Batches go from In cutting straight to Put in stock." · **Add phase**.
- **Errors:** "Enter the phase's name." · "Keep the name to 24 characters or fewer." · "You already have a phase named "Sewing"." · "In
  cutting and Put in stock are already there. Choose another name." · "You can have up to 6 phases."
- **Can't be pressed:** **Move up**, **Move down** and **Remove Sewing** while Sewing holds units: "Sewing holds 15 units, so it can't be
  moved or removed. Move them on or back first." (a tip the keyboard reaches).
- **Confirm:** **Remove Quality check?** · "Quality check is empty. It leaves Progress; batches then go from Finishing to Put in stock." ·
  **Remove phase** · **Keep phase**.
- **Refused meanwhile:** "Quality check now holds 2 units, so it can't be removed."
- **Success:** "Phase added." · "Phase updated." · "Phase removed." · "Phases reordered."

### Scan page and the scan confirm
- **Ready, phone:** the camera view with a frame; "Point the camera at the QR code on a work ticket."; "Or type the batch number" · the field · **Find ticket**.
- **Ready, laptop:** the field "Scan or type a batch number", with the focus; with no camera, the field only.
- **Moved:** "4 units of Denim Jacket · Indigo S moved to Finishing." and the next scan is ready. **Put in stock:** "4 units of Denim Jacket
  · Indigo M put in stock."
- **Already in stock:** "B-0139 was put in stock on 28 Oct 2026. Nothing left to do." · **Scan another**.
- **Undone:** "B-0150 was undone on 30 Oct 2026: its materials went back to stock. Nothing to move." · **Scan another**.
- **Unknown code, or another workshop's:** "This code isn't a work ticket of your workshop. Check the ticket, or type its number." It never
  says that another workshop exists.
- **Number not found:** "No batch B-0999 in your workshop. Check the number on the ticket." **Not a batch number:** "A batch number
  looks like B-0142. Check what you typed."
- **Scanned twice quickly:** a warning line above the button: "B-0147 moved to Finishing 12 seconds ago (Admin). Scanning again moves it
  on to Put in stock." It shows when the last move was less than 2 minutes ago.
- **Moved meanwhile:** "B-0147 has moved since you scanned it: it is now in Finishing. Scan it again to move it on." Nothing moves.
- **Phases changed meanwhile:** "The phases have changed since you scanned. B-0147 now goes to Quality check." The button relabels;
  nothing has moved.
- **Product deleted:** "Denim Jacket was deleted, so B-0148 can't be put in stock." Other moves work.
- **Camera refused:** "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch number."
- **Couldn't move:** "Couldn't move B-0147. Check the connection and try again." · **Try again**. **Couldn't load:** "Couldn't load ticket
  B-0147. Try again." · **Try again**.
- **Your scans today, empty:** "No scan yet today."

## Wording
- Sidebar and breadcrumbs: "Progress" · "Progress › Sewing" · "Scan".
- Progress page: "Progress" · "The batches you are making for your shelf, phase by phase, in their colour and size." · "Production phases" ·
  "Scan a ticket" · "Start batch" · "Start a batch of Denim Jacket" · "Phases" · "All phases" · "In cutting" · "Sewing" · "Finishing" · "15
  units" · "Product" · "All products" · "Colour" · "All colours" · "Clear filters" · "Showing Indigo · 23 of 26 units" · "21 units in 6
  batches" · "Denim Jacket by phase" · "Colour · size" · "In production" · "On the shelf now" · "Out" · "Total" · "Nothing in production"
  · "Start a batch to make products for your shelf. They are added to your finished stock when you put them in stock." · "No batch
  matches these filters." · "Try another product or colour, or clear the filters." · "Couldn't load the progress of your production." ·
  "Try again".
- Phase view: "15 units · 3 batches · 2 products · oldest since 26 Oct 2026" · "From In cutting. Next: Finishing." · "In Sewing" · "Since" ·
  "4 days" · "1 day" · "today" · "Batch" · "Units" · "Move to Sewing" · "Move to Finishing" · "Put in stock" · "Move back to In cutting" ·
  "Move back to Sewing" · "Undo batch" · "Print ticket B-0147" · "More moves for batch B-0146" · "Nothing in Sewing" · "Batches come here
  from In cutting with Move to Sewing." · "Show all phases" · "Nothing in Sewing matches these filters." · "Couldn't load what is in
  Sewing." · "Denim Jacket (deleted)".
- Start batch: "Start batch" · "Make products for your shelf. Materials are taken out of stock now." · "Product" · "Can produce now 112" · "How many
  units of each colour and size?" · "Shelf 2" · "Shelf Out" · "Total 16 units · 2 batches (Indigo M 6, Indigo L 10)" · "Materials for
  16 units" · "Material" · "Taken" · "In stock" · "After" · "Short by" · "Cancel" · "2 batches started" · "16 units of Denim Jacket
  started. Materials taken out of stock." · "2 tickets to print: B-0153 · Indigo M · 6 units, B-0154 · Indigo L · 10 units" · "Print 2
  tickets" · "Done".
- Moves: "Move to Finishing · Denim Jacket" · "B-0149 · Indigo M · in Sewing: 6 units" · "How many units?" · "Up to 6" · "Move units" ·
  "Put in stock · Denim Jacket" · "Adds to your finished stock: Indigo M goes from 0 to 4. Recorded as a restock "From production"." ·
  "See the restock" · "Undo batch B-0150?" · "Keep batch" · toasts "4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was
  split: the units moved are on B-0155." · "4 units of Denim Jacket · Indigo M put in stock." · "Batch B-0150 undone. Materials returned to
  stock."
- Tickets: "Work ticket" · "B-0147" · "4 units" · "Started 23 Oct 2026" · "Printed 30 Oct 2026" · "Scan to move it to the next phase." · "Split
  from B-0149" · Settings › Labels: "Ticket size" · "A4 sheet, 8 tickets (2 × 4)" · "100 × 60 mm, one per label (label printer)".
- Scan page: "Scan" · "Point the camera at the QR code on a work ticket." · "Or type the batch number" · "Scan or type a batch number" · "A scanner
  works like typing. Each ticket opens its move to confirm." · "Find ticket" · "Move to Finishing?" · "Move 4 units to Finishing" · "Put 4
  units in stock?" · "Put 4 units in stock" · "Not now" · "(now)" · "Your scans today" · "Scan another" · "No scan yet today.", and the scan
  messages above.
- Settings › Production phases: "Production" (the menu group) · "Production phases" · "The phases a batch goes through between cutting and
  putting it in stock. In cutting and Put in stock are always there." · "Add phase" · "Always there. Start batch puts a batch here and takes the
  materials." · "Always there. Ends a batch: its units are added to your finished stock." · "Holds 15 units" · "Empty" · "Move up" · "Move
  down" · "Edit Sewing" · "Remove Sewing" · "Name" · "A short verb or noun your team uses, such as Sewing or Quality check." · "Save
  phase" · "No phase of your own. Batches go from In cutting straight to Put in stock.", and the errors, confirm and toasts above.
- Restock written by Put in stock: Reference "B-0148", Note "From production".
- The same verb through each flow: **Move to Finishing** → "Move to Finishing · Denim Jacket" → **Move units** → "… moved to Finishing.";
  **Put in stock** → "Put in stock · Denim Jacket" → **Put in stock** → "… put in stock."; **Undo batch** → "Undo batch B-0150?" → **Undo
  batch** → "Batch B-0150 undone.".
- Gone from the earlier card: "To start", "Ready to ship", "Shipped", "Start cutting", "Mark ready", "Ship", "Tickets (2)", the Order and Client
  columns and filters, "T-0142" ticket numbers.

## Phone layout
Board: INV-5 rev. 3 · Progress, one phase, Start batch, Put in stock, Settings, Scan · 375 (Invory App UI canvas)
- **Progress:** the title; **Start batch** full width; **Scan a ticket** and **Phases** side by side; the tiles two to a row (All phases full
  width first); **Filters** ("Filters · 1" when set) opening a full-screen panel with Product and Colour; then a card per product: a band per
  colour, a line per size with the phases that hold units and the shelf ("M · In cutting 4 · Sewing 6 · Finishing 4 · On the shelf Out"),
  the Total line first, **Start batch** full width.
- **One phase:** a card per batch: product and batch number, "Indigo · M", "4 units" in bold, "In Sewing since 27 Oct 2026 · 3 days",
  **Move to Finishing** and **More** full width (44 px); the last phase has **Put in stock**.
- **Start batch** and **Put in stock:** full screen, the fields three to a row, **Start batch** or **Put in stock** and **Cancel** in a bar fixed at
  the bottom that never hides the field with the focus; the materials table is short (Material · Taken · After). **Undo batch** is a sheet
  from the bottom.
- **Settings › Production phases:** one card per phase; the fixed phases flat and grey; **Move up**, **Move down**, **Edit** and **Remove** as
  named 44 px icon buttons; **Add phase** full width.
- **Scan:** the camera square, the number field, **Find ticket**, and the confirm as a sheet from the bottom with its buttons one under
  another (56 px for the move).
- At 320 px nothing scrolls sideways; every control is at least 44 px on a phone.
