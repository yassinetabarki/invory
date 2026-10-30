# INV-5 · Progress — design spec
Status: proposal, revision 2 (decisions of 2026-10-30 written in; QR work tickets added at the human's request) · Designer ·
2026-10-30 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc), row "Progress (INV-5)":
- "INV-5 · Progress · all phases by product, filters, empty, no match, error · desktop" (`project/Inv5Overview.dc.html`)
- "INV-5 · Progress · one phase (Sewing), move window, refusals · desktop" (`project/Inv5PhaseDetail.dc.html`)
- "INV-5 · Order page and New order per colour, Settings › Production phases · desktop" (`project/Inv5OrderSettings.dc.html`)
- "INV-5 · Progress, one phase, move window, Settings · 375" (`project/Inv5Phone.dc.html`)
- Row "Progress · QR work tickets (INV-5)": "INV-5 · Work tickets · print after Start cutting, the ticket, Scan page with a scanner,
  Tickets column · desktop" (`project/Inv5Tickets.dc.html`) and "INV-5 · Scan a ticket · camera, confirm, moved, finished, errors ·
  375" (`project/Inv5ScanPhone.dc.html`)

> **Decisions of 2026-10-30.** The human: **phases (a)**, each workshop keeps its own list in Settings › Production phases · **shelf
> (a)**, an order's units never go on the shelf · **colour lock (a)**, a colour can't be removed from a product while it is on an
> open order · **orders per colour: open, to be discussed later** (section B stays as written, undecided). The product-owner, as
> recommended: page layout C1 · moves from Progress D1 · "since" E1 · tiles follow the filters G1 · Orders list "In production" H1 ·
> order lines recorded before colours are test data and are **dropped** · Progress after Orders in the sidebar.
> **New request (the human):** a QR code on each bundle in production; scanning it moves the bundle to the next phase, up to the
> finish. Moving back by scan is not handled. See "QR work tickets" below.

> **Where the first four boards differ from this revision** (drawn before the tickets; not redrawn, this spec wins): the phase view
> has no **Tickets** column and its "⋯" menu has no **Print ticket**; the Start cutting window ends without "Print 2 tickets"; the
> order page line has no **Tickets** link. The Tickets board shows them.

Builds on the approved UI cards of INV-1 (a colour is always its swatch and its name, in Settings order), INV-2 (stock per colour ×
size; "Indigo M"), INV-3 (a colour × size grid of fields, "now 2" under each; Restock adds to the shelf) and INV-4 (every order has a
client with a name, a phone and a city; the source is optional; Clients sit after Orders in the sidebar). The order stages are today's
(S-49, S-53, S-60, S-61): To start → In cutting → Ready to ship → Shipped, per size, with Start cutting taking the materials out of
stock and Move back to To start returning them. S-57 (cancel) and S-65 (close) are on hold and are not built on here.

## Problem and user
The workshop owner, at the cutting table on a laptop or walking the floor with a phone, asks: **"What is being made right now, and
where is it?"** Today the answer is spread over every open order's page. To know how many Indigo M jackets are being sewn, she opens
Boutique Lina, then Amira Ben Salah, then Yassine Trabelsi, and adds up in her head. Nothing says what has waited longest, and the
order page only knows "In cutting" and "Ready to ship": sewing, finishing or a quality check don't exist, so a jacket being sewn reads
"In cutting" for a week.

Progress answers three questions on one page:
1. **How many units are in each phase**, across all open orders (the phase tiles).
2. **Where each product stands**, colour by colour and size by size (the table per product).
3. **What is in one phase**: which units, of which order and client, since when, and how many (the phase view), with the button that
   moves them on.

## How it looks today
- Sidebar: Dashboard, Materials, Products, Restocks, Orders, Clients, Sales (INV-3, INV-4). No production page.
- The order page shows each line's table: one row per size ordered, the columns To start · In cutting · Ready to ship · Shipped, a
  Total row, and under it the buttons Start cutting, Mark ready, Ship and the three Move back. Each opens a window "How many units of
  each size?" with "Up to 10" under each size; Start cutting and Move back to To start show the materials table.
- Order lines count by size only: "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)". INV-2 counts the shelf per colour and size, but an
  order line has no colour yet (INV-2 left "orders per colour" out on purpose).
- The Orders list shows per order To start · In cutting · Ready to ship · Shipped, and the open-order totals at the top ("Open orders
  3 · To start 48 · In cutting 26 · Ready to ship 6" in this spec's example, where today "In cutting" also holds the units being sewn).
- Invory remembers the day of each cut (S-53), but not the day units were marked ready or shipped (S-65's How it works today).

## Example data used everywhere
Today is 30 Oct 2026. Three open orders, middle phases **Sewing** and **Finishing** (option A1 below):

| Order                     | Line                                         | Units |
| ------------------------- | -------------------------------------------- | ----- |
| Boutique Lina · 15 Oct    | Denim Jacket · Indigo S 10 · M 20 · L 15 · XL 5 | 50    |
| Boutique Lina · 15 Oct    | Denim Jeans 5-pocket · Indigo S 5 · M 10 · L 10 · XL 5 | 30    |
| Amira Ben Salah · 28 Oct  | Denim Jacket · Black M 1                     | 1     |
| Amira Ben Salah · 28 Oct  | Denim Jeans 5-pocket · Indigo M 1            | 1     |
| Yassine Trabelsi · 27 Oct | Denim Jacket · Light wash L 2 · XL 1         | 3     |

Where the units are (To start · In cutting · Sewing · Finishing · Ready to ship · Shipped):

| Product · colour · size            | To start | In cutting | Sewing | Finishing | Ready to ship | Shipped | Total |
| ---------------------------------- | -------- | ---------- | ------ | --------- | ------------- | ------- | ----- |
| Denim Jacket · Black M             | 1        | 0          | 0      | 0         | 0             | 0       | 1     |
| Denim Jacket · Indigo S            | 0        | 0          | 4      | 0         | 6             | 0       | 10    |
| Denim Jacket · Indigo M            | 6        | 4          | 6      | 4         | 0             | 0       | 20    |
| Denim Jacket · Indigo L            | 15       | 0          | 0      | 0         | 0             | 0       | 15    |
| Denim Jacket · Indigo XL           | 0        | 0          | 0      | 0         | 0             | 5       | 5     |
| Denim Jacket · Light wash L        | 0        | 2          | 0      | 0         | 0             | 0       | 2     |
| Denim Jacket · Light wash XL       | 0        | 1          | 0      | 0         | 0             | 0       | 1     |
| **Denim Jacket**                   | 22       | 7          | 10     | 4         | 6             | 5       | 54    |
| Denim Jeans 5-pocket · Indigo S    | 0        | 0          | 5      | 0         | 0             | 0       | 5     |
| Denim Jeans 5-pocket · Indigo M    | 11       | 0          | 0      | 0         | 0             | 0       | 11    |
| Denim Jeans 5-pocket · Indigo L    | 10       | 0          | 0      | 0         | 0             | 0       | 10    |
| Denim Jeans 5-pocket · Indigo XL   | 5        | 0          | 0      | 0         | 0             | 0       | 5     |
| **Denim Jeans 5-pocket**           | 26       | 0          | 5      | 0         | 0             | 0       | 31    |
| **All open orders**                | 48       | 7          | 15     | 4         | 6             | 5       | 85    |

- Since when: Jeans Indigo S in Sewing since 26 Oct; Jacket Indigo S in Sewing since 27 Oct; Jacket Indigo M in Sewing since 28 Oct;
  Jacket Indigo M in cutting since 25 Oct; Light wash L and XL in cutting since 29 Oct; Indigo M in Finishing since 29 Oct; Indigo S
  ready to ship since 29 Oct; Indigo XL shipped 28 Oct.
- Every Indigo unit except Amira's jeans is Boutique Lina's; Black M is Amira's; Light wash is Yassine's.

## Flow
1. The owner opens **Progress** in the sidebar. She sees seven tiles: **All phases 85 units** (chosen), **To start 48**, **In cutting
   7**, **Sewing 15**, **Finishing 4**, **Ready to ship 6**, **Shipped 5**. Under them, one block per product: Denim Jacket "54 units on
   3 open orders", a table with a row per colour and size, a column per phase.
2. She picks **Colour: Indigo** in the filters. The tiles and the tables show Indigo only (To start 47 …), and the address keeps the
   filter, so the link can be shared.
3. She clicks the tile **Sewing 15**. The phase view lists what is in Sewing, oldest first: Denim Jeans 5-pocket Indigo S 5, Boutique
   Lina, since 26 Oct 2026 (4 days); Denim Jacket Indigo S 4, since 27 Oct (3 days); Denim Jacket Indigo M 6, since 28 Oct (2 days).
4. On the Denim Jacket Indigo M row she clicks **Move to Finishing**. The window "Move to Finishing · Denim Jacket" opens on Boutique
   Lina's line, the Indigo M field focused, "Up to 6" under it. She types 6 and clicks **Move units**: toast "6 units of Denim Jacket
   for Boutique Lina moved to Finishing." The tiles read Sewing 9 · Finishing 10; the row has gone from Sewing. Boutique Lina's order
   page shows Indigo M: Sewing 0 · Finishing 10.
5. On **To start**, the row Denim Jacket Indigo L 15 · Boutique Lina · ordered 15 Oct 2026 has **Start cutting**. It opens today's
   Start cutting window for that line, with the materials table (10 units: Denim 12oz Indigo 19.8 m, Metal shank button 17mm 80 pc,
   Polyester thread 1,260 m, Woven brand label 10 pc), the same short-stock check and refusal.
6. With Indigo M 6 and L 10 typed, **Move units** ends on "2 tickets to print" and **Print 2 tickets**. The cutter ties each bundle
   with its ticket (T-0152, T-0153).
7. At the sewing machine, Sami scans T-0142 with his phone: **Move to Finishing?** · "T-0142 · 6 units · Denim Jacket · Indigo M ·
   Boutique Lina". He taps **Move 6 units to Finishing**: "6 units of Denim Jacket · Indigo M moved to Finishing." Progress and the
   order page show it at once. Scanned again at Ready to ship, the ticket says it is finished and moves nothing.
8. On the order page the same moves exist under the table, with the new phases as columns. Whichever page a move starts from, it is
   the same move, with the same window, limits, materials, toast and history.

## Options and recommendation

Decided: A1, C1, D1, E1, F1, G1, H1 (see the decisions at the top). Open: B.

### A · The phases — **decided: (A1)**
Today: To start → In cutting → Ready to ship → Shipped. A workshop sews, finishes, irons, checks and packs between cutting and ready;
a shoe workshop stitches uppers, lasts and soles.

- **(A1) Middle phases set per workshop, in Settings › Production phases — recommended.** To start, In cutting, Ready to ship and
  Shipped stay fixed (cutting takes the materials; ready and shipped drive the order's status). Between In cutting and Ready to ship
  the workshop keeps its own list, 0 to 6 phases, starting with **Sewing** and **Finishing**; it can add "Quality check" or "Packing",
  rename, reorder and remove. A phase that holds units can't be moved or removed. Fits apparel and shoes alike; one more Settings page.
- **(A2) Fixed middle phases for every workshop: Sewing and Finishing.** Cheaper (two more columns, no Settings page), but a shoe
  workshop gets "Sewing" for lasting, and nobody can add Quality check.
- **(A3) No new phases.** Progress shows today's four stages only. Cheapest, but "In cutting" keeps meaning "anywhere on the floor",
  which is what the human wants to fix.
- Rejected: phases per product category (Apparel vs Footwear lists). A workshop rarely makes both, and it doubles every screen's
  rules.

How the middle phases behave (A1 and A2):
- Order: To start → In cutting → Sewing → Finishing → Ready to ship → Shipped. Units move one phase at a time, forward or back, as today.
- The forward button names where the units go: under To start **Start cutting** (takes the materials, S-53); under In cutting **Move to
  Sewing**; under Sewing **Move to Finishing**; under the last middle phase **Mark ready**; under Ready to ship **Ship**. With no middle
  phase, In cutting has **Mark ready**, as today.
- **Move back** goes one phase back. Only Move back to To start returns materials (S-53). Mark ready, Ship, the middle moves and their
  Move back never touch stock.
- A product's units move per colour and size, as they do per size today.

### B · Colour on an order — needed for "per colour" — **open, to be discussed later** (the human, 2026-10-30)
The rest of this spec draws the colour on order lines (B1) so the screens can be read; nothing in it is decided until this is.
An order line has no colour today, so Progress can't say "Indigo M". Progress needs it.
- **(B1) INV-5 brings it: each order line is counted per colour and size — recommended.** In New order and Edit order, a line's "Quantity
  per size" becomes the INV-3 grid: one row per colour of the product (Settings order), one column per size, a field in each cell,
  "Total 50 units" under it. A "One size" product: one field per colour. The order page, the Orders list and every move window follow.
  The product-owner may still split the development cards (orders per colour first, then Progress).
- (B2) A separate INV card for orders per colour, approved before INV-5. Same screens, one more card round.
- (B3) Progress per size only, colour later. Doesn't meet the request.

### C · What the page shows
- **(C1) Phase tiles + one table per product — recommended.** Tiles at the top give the count per phase and open each phase's view; the
  table per product has a row per colour × size, a column per phase, the same columns as the order page, so the figures read the same
  way everywhere. Works with 6 phases and 40 rows; at 375 px each size becomes a short line of its non-zero phases.
- (C2) A board with one column per phase (cards per product and colour, size chips inside). Shows flow at a glance, but 7 columns don't
  fit a laptop beside the sidebar, scroll sideways on a phone, hide sizes inside cards, and invite dragging (which then needs a click
  alternative, 2.5.7). Drawn small on the overview board for comparison.
- (C3) One tab per phase only, no overview. Simple, but no answer to "where does the Denim Jacket stand?".

### D · Moving units from Progress
- **(D1) Each row of a phase view has its forward button and Move back, opening the order page's own window for that order line —
  recommended.** One window, one set of rules, so Progress can never move differently from the order page.
- (D2) Progress is read-only; each row links to its order. Safest, but the owner walks to another page for every move.
- (D3) Pick several rows across orders and move them together ("Move 14 Indigo M to Finishing"). The cutting table's real batch, but
  Start cutting across orders means one materials check over several lines, and a partial refusal is hard to explain. A later story.

### E · "Since when"
Invory remembers the day of each cut, not of the other moves.
- **(E1) Each move is remembered with its day; a row shows the day the oldest of its units entered the phase, and how long ago:
  "since 26 Oct 2026 · 4 days" — recommended.** Sorting oldest first puts what waits longest on top. To start shows the order date
  ("ordered 15 Oct 2026 · 15 days"). Units already in a phase before INV-5: "before 30 Oct 2026".
- (E2) The day of the latest move into the phase. Easier to keep, but hides a unit stuck for a week behind one moved yesterday.
- (E3) No "since" until S-66 (each shipment remembered). The phase view then sorts by order date.

### F · Ready to ship, and the shelf (Restock)
- **(F1) An order's units stay the order's — recommended.** Ready to ship and Shipped never change "In stock". The shelf holds only units
  promised to nobody: Restock adds them, Update stock sets them (INV-3). Progress never changes stock, so nothing is counted twice.
- (F2) Production for the shelf: a batch with no client goes through the same phases and ends with **Put in stock** instead of Ship,
  which adds its units to the shelf, recorded as a restock "From production". Valuable for workshops that sew ahead for Instagram,
  but it is a new kind of work: a later INV card, if the human wants it.
- (F3) Ship from the shelf: an order takes finished units from stock instead of making them. Not production; out of scope.

### G · Do the tiles follow the filters?
- **(G1) Yes, and the page says so — recommended.** With Colour Indigo, the tiles read "To start 47" and the line under them reads
  "Showing Indigo · 81 of 85 units · Clear filters". At the cutting table "how many Indigo are in Sewing" is the question.
- (G2) No: like the Orders list, the tiles always count every open order. Consistent with S-50, but the filtered table and the tiles then
  disagree on screen.

### H · The Orders list and the middle phases
- **(H1) One column "In production" = In cutting + the middle phases — recommended.** The list's columns and totals become To start ·
  In production · Ready to ship · Shipped ("In production 26 units"), with the link **See Progress** by the totals. The split by phase
  lives on Progress and the order page.
- (H2) A column per phase on the list. Exact, but 6 to 10 figure columns on a list.
- (H3) Unchanged: "In cutting" counts only cutting. Then the list's totals drop units that are being sewn.

## QR work tickets (revision 2, the human's request)
In the human's words: when a product is in progress, Invory generates a QR code; when the cutter or any other user scans it, the units
move to the next phase, until the finish. Going back by scan is not handled for now.

### Q · What a QR code stands for
- **(Q1) A bundle: the units of one colour and size that one Start cutting cut for one order line — recommended.** It is what the
  cutting room ties together and carries from table to machine. "Start cutting · Denim Jacket" with Indigo M 6 and L 10 makes two
  tickets: **T-0152** (Denim Jacket · Indigo M · 6 units · Boutique Lina) and **T-0153** (Indigo L · 10 units). A scan moves **all the
  units of the bundle**, one phase on. Moving part of a bundle stays in the move windows.
- (Q2) An order line × colour × size (Boutique Lina's Indigo M jackets). Its 20 units sit in four phases at once (6 to start, 4 in
  cutting, 6 sewing, 4 finishing), so a scan can't know which units to move without asking a phase and a count every time.
- (Q3) One code per order, or per order line. Same problem, bigger.
- A scan that asks "how many?" each time was rejected: the point of the ticket is one gesture at the machine.

How tickets follow the move windows (so the paper and the screen never disagree):
- A window that moves units **on** (Move to Sewing …) takes them from the **oldest** tickets in that phase first; a window that moves
  units **back** takes the **newest** first, as cuts are returned today (S-53).
- A whole ticket moved by a window keeps its number and moves with its units.
- Part of a ticket moved by a window **splits** it: the units moved get a new ticket ("T-0157 · split from T-0142"), and Invory offers
  to print it. The units that stay keep T-0142, which now reads 4 units.
- Units moved back to **To start** leave their ticket; a ticket left with no unit is **no longer used** (its paper can be thrown away).
  When those units are cut again they get a new ticket.

### When a ticket is made, and where it is printed
- **Made at Start cutting**, one per colour and size cut, numbered per workshop (T-0001, T-0002 …). Units still to start have no
  ticket, so **a scan never starts cutting**: cutting takes the materials and is always the Start cutting window, with its materials
  table and its short-stock check (S-53).
- **Start cutting ends on a print step:** after **Move units**, the window shows "16 units of Denim Jacket moved to In cutting.
  Materials taken out of stock." and "2 tickets to print: T-0152 Indigo M · 6 units, T-0153 Indigo L · 10 units", with **Print 2
  tickets** (main) and **Done**. Print opens the tickets in a new tab and the browser's print window (as labels, INV-2).
- **Printed again** at any time: the order page's line has **Tickets (2)**, which opens a panel listing its tickets (number, colour ·
  size, units, phase, "Not printed yet" until printed) with **Print ticket T-0142** each and **Print all**; the phase view's "⋯" menu
  has **Print tickets of this row**; a split offers its new ticket in the toast ("Print ticket T-0157").
- **Ticket size**, in Settings › Labels: "A4 sheet, 8 tickets (2 × 4)" (default) or "100 × 60 mm, one per label (label printer)".
- **The ticket** (105 × 74 mm on A4), always black on white:
  1. Top line: "Work ticket" and, large, **T-0142**.
  2. Left: the QR code (30 mm). Right: **Denim Jacket** (bold, at most two lines), "Indigo · M", **6 units** (large), "Boutique Lina ·
     ordered 15 Oct 2026", SKU "PRD-JKT-001-IND-M" in the number font.
  3. The path with boxes, as the phases were when printed, the phase it was in ticked: "☑ In cutting ☐ Sewing ☐ Finishing ☐ Ready to
     ship", and "Cut 30 Oct 2026 · printed 30 Oct 2026".
  4. Bottom: "Scan to move it to the next phase." A split ticket adds "Split from T-0142".
  - No phone number and no price on a ticket: it lies on the workshop floor.
- **What the QR holds:** an Invory link with a secret code for that ticket, so the phone's own camera opens the right screen, and a
  scanner or the Scan page reads the same. The short number under it ("T-0142") can be typed instead.

### Where and how a ticket is scanned
- **(S1) Both, the same screen — recommended:**
  - **Scan** in the sidebar, after Progress (and **Scan a ticket** on the Progress header): on a phone it opens the camera; on any
    device it has the field "Scan or type a ticket number", where a USB or Bluetooth scanner types like a keyboard (INV-3's scan field
    pattern).
  - The phone's **own camera app** opens the ticket's link, which opens the same confirm screen in Invory.
- **Who:** any signed-in member of the workshop. Not signed in: the sign-in page, then straight back to the scanned ticket.
- **(C1) One confirm per scan — recommended.** The scan opens a confirm with everything on it, and one large button does the move:
  - Title **Move to Finishing?**
  - "T-0142 · **6 units** · Denim Jacket · ■ Indigo · M · Boutique Lina (ordered 15 Oct 2026)"
  - The path: "In cutting ✓ · Sewing (now) · **Finishing** · Ready to ship"
  - **Move 6 units to Finishing** (main, full width, 56 px) and **Not now**.
  - Moved: a green line "6 units of Denim Jacket · Indigo M moved to Finishing." (also announced), the camera or field ready for the
    next ticket, and the move in "Your scans today".
  - Why a confirm: a phone camera reads any QR in view, and a move can only be undone in a Move back window. The confirm never times
    out (2.2.1).
- (C2) Move at once, with **Undo** in the toast for 10 seconds (Undo moves the bundle back one phase; it isn't a scan). Faster at a
  station with a scanner, riskier on a phone.
- (C3) A choice per device: confirm on phones, at once on the Scan page's scanner field.
- **The finish:**
  - **(E1) Scans move a bundle up to Ready to ship — recommended.** Shipping stays the order page's **Ship**, because a parcel is made
    per client, often from several bundles. A scan of a bundle that is ready to ship: "T-0139 is ready to ship. Its 6 units of Denim
    Jacket · Indigo S wait for Boutique Lina's shipment." with **Open the order** and **Scan another**; nothing moves.
  - (E2) Scans go on to Shipped: at Ready to ship the confirm reads **Ship 6 units?**
  - A shipped bundle: "T-0136 shipped on 28 Oct 2026. Nothing left to do." and **Scan another**.
- **No moving back by scan.** Moving back stays in the Move back windows of the order page and the phase view.

### Scan states, with their wording
| State | On screen |
| ----- | --------- |
| Ready, phone | Camera view with a frame; "Point the camera at the QR code on a work ticket."; under it "Or type the ticket number" · field · **Find ticket** |
| Ready, laptop | The field "Scan or type a ticket number", focused; hint "A scanner works like typing. Each ticket opens its move to confirm." |
| Confirm | As above: **Move to Finishing?** … **Move 6 units to Finishing** · **Not now** |
| Moving | The button shows a spinner and keeps its label |
| Moved | "6 units of Denim Jacket · Indigo M moved to Finishing." and the next scan is ready |
| Ready to ship (finish) | "T-0139 is ready to ship. Its 6 units of Denim Jacket · Indigo S wait for Boutique Lina's shipment." · **Open the order** · **Scan another** |
| Shipped | "T-0136 shipped on 28 Oct 2026. Nothing left to do." · **Scan another** |
| Unknown code, or another workshop's | "This code isn't a work ticket of your workshop. Check the ticket, or type its number." (never says that another workshop exists) |
| Number not found | "No ticket T-0999 in your workshop. Check the number on the ticket." |
| Not a ticket number | "A ticket number looks like T-0142. Check what you typed." |
| No longer used | "T-0142 is no longer used: its units went back to To start on 30 Oct 2026. They get a new ticket when they are cut again." |
| Split | The confirm moves what the ticket holds now, with the note "2 of its units moved apart on ticket T-0157." |
| Order cancelled (S-57) | "Boutique Lina's order was cancelled, so T-0142 can't move. Its units stay where they are." · **Open the order** |
| Order closed (S-65) | "Boutique Lina's order is closed, so T-0142 can't move." · **Open the order** |
| Scanned twice quickly | The confirm shows the bundle as it is now, with a warning line above the button: "T-0142 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Ready to ship." The warning shows when the last move was under 2 minutes ago |
| Moved meanwhile | Someone moved it between scan and confirm: "T-0142 has moved since you scanned it: it is now in Finishing. Scan it again to move it on." Nothing moves |
| Phases changed meanwhile | A phase was added, removed or reordered between scan and confirm: "The phases have changed since you scanned. T-0142 now goes to Quality check." The button relabels; nothing moved. A phase holding units can't be removed (A1), so a bundle's own phase never disappears |
| Product deleted | Moves as usual (only cutting is refused for a deleted product); the confirm reads "Denim Jacket (deleted)" |
| Camera refused | "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the ticket number." |
| No camera (laptop) | The field only |
| Couldn't move | "Couldn't move T-0142. Check the connection and try again." · **Try again** |
| Couldn't load the ticket | "Couldn't load ticket T-0142. Try again." · **Try again** |
| Print blocked | "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again." (INV-2's wording) |
| Your scans today, empty | "No scan yet today." |

### Ticket wording
"Work ticket" · "T-0142" · "6 units" · "Boutique Lina · ordered 15 Oct 2026" · "Cut 30 Oct 2026 · printed 30 Oct 2026" · "Scan to move
it to the next phase." · "Split from T-0142" · Start cutting: "2 tickets to print: T-0152 Indigo M · 6 units, T-0153 Indigo L · 10
units" · "Print 2 tickets" · "Done" · order page: "Tickets (2)" · "Not printed yet" · "Print ticket T-0142" · "Print all" · phase view:
"Tickets" (column) · "Print tickets of this row" · split toast: "4 units of Denim Jacket for Boutique Lina moved to Finishing. Ticket
T-0142 was split: the units moved are on T-0157." with the action "Print ticket T-0157" · Scan page: "Scan" (sidebar) · "Scan a
ticket" · "Point the camera at the QR code on a work ticket." · "Or type the ticket number" · "Scan or type a ticket number" · "A
scanner works like typing. Each ticket opens its move to confirm." · "Find ticket" · "Move to Finishing?" · "Move 6 units to
Finishing" · "Not now" · "(now)" · "Your scans today" · "Scan another" · "Open the order" · and the state messages above · Settings ›
Labels: "Ticket size" · "A4 sheet, 8 tickets (2 × 4)" · "100 × 60 mm, one per label (label printer)".

### Ticket accessibility and phone
- The camera is never the only way: the number field is always there (2.5.x, 1.1.1); the camera view is named "Camera: point it at
  the QR code". The confirm is a dialog that keeps the focus and returns it to the field; the result line is a polite live region
  (4.1.3); the move button is 56 px tall and the only main button.
- No time limit, no sound required, nothing flashes (2.2.1, 2.3.1). The QR has a 4-module quiet zone and prints at least 25 mm.
- **375 px (board "Scan a ticket … 375"):** the Scan page fills the screen: title, the camera square (full width, 1:1), the hint, the
  number field and **Find ticket**, then "Your scans today" (ticket, product, "→ Finishing", time). The confirm is a sheet from the
  bottom, full width, buttons one under the other with **Move 6 units to Finishing** first. Every error is a line in the sheet with its
  button; the camera stays usable under it.

## Layout

### Sidebar
- New entry **Progress**, with a factory icon, after Orders, and **Scan** (a QR icon) after it: Dashboard, Materials, Products,
  Restocks, Orders, **Progress**, **Scan**, Clients, Sales. Breadcrumbs: "Progress", "Progress › Sewing", "Scan".

### Progress page, all phases (desktop)
Board: INV-5 · Progress · all phases by product, filters, empty, no match, error · desktop
1. `PageHeader` **Progress**, description "Every unit of your open orders, phase by phase, in its colour and size." Main button: none
   (Progress records nothing new); the outline link **Production phases** (settings gear icon) opens Settings › Production phases.
2. **Phase tiles**, one row, a navigation group named "Phases": **All phases** · **To start** · **In cutting** · **Sewing** ·
   **Finishing** · **Ready to ship** · **Shipped**. Each tile: the phase name (14 px, grey) and its count in the number font (24 px)
   with "units" ("15 units"). The chosen tile has a 2 px Denim border and the light Denim tint, and is announced "current page". A tile
   at 0 shows "0 units" in grey and still opens its view. Tiles wrap to two rows under 1100 px.
3. `FiltersBar`: **Product** (searchable list, "All products"), **Colour** (swatch and name, "All colours"), **Client** (searchable, phone
   in grey, "All clients"), **Order** (searchable, "Boutique Lina · 15 Oct 2026", "All open orders"). All in the page address; they
   combine; **Clear filters** once any is set.
4. With a filter set, under the bar: "Showing Indigo · 81 of 85 units" and **Clear filters**.
5. One `Card` per product with units on open orders, by name A → Z, 10 products a page (`Pagination`):
   - Header: the product name (bold, a link to its page), SKU in the number font, "54 units on 3 open orders". On the right the link
     **Open orders of Denim Jacket** (the Orders list filtered to the product).
   - Table "Denim Jacket by phase": first column **Colour · size**; then **To start**, **In cutting**, **Sewing**, **Finishing**,
     **Ready to ship**, **Shipped**, **Total**. Rows grouped by colour in Settings order: a colour row (colour tag, bold figures: the
     colour's subtotal, a light grey band) then its sizes in size order, indented, the size label in semibold. Only colours and sizes
     with units on open orders have rows. A **Total** row last, with a heavier line above. Every figure in the number font,
     right-aligned; zeros in the readable grey (S-71). Figures are not links (keyboard: one stop per product, not 70).
   - A "One size" product: the colour rows only, no size rows.
6. The page never computes: every figure, subtotal and total is sent by the server.

### Progress page, one phase (desktop)
Board: INV-5 · Progress · one phase (Sewing), move window, refusals · desktop
1. Breadcrumb "Progress › Sewing". The tiles stay on top, **Sewing** chosen; the filters stay.
2. Heading **Sewing**, the line "15 units · 2 products · 1 open order · oldest since 26 Oct 2026". Under it, in grey, where units come
   from and go to: "From In cutting. Next: Finishing."
3. `Table` "In Sewing", one row per order line × colour × size in the phase, oldest first:
   **Since** ("26 Oct 2026", under it "4 days" in grey) · **Product** (name, link; SKU in grey) · **Colour · size** (colour tag + "S") ·
   **Units** (number font, bold) · **Tickets** ("T-0142"; two or more: "T-0142, T-0150"; in To start "—") · **Order** ("Boutique
   Lina", link to the order; under it "ordered 15 Oct 2026") · the row's buttons:
   **Move to Finishing** (outline, small, 44 px tall) and a "⋯" menu named "More moves for Denim Jacket Indigo S, Boutique Lina" with
   **Move back to In cutting** and **Print tickets of this row**.
4. 25 rows a page.
5. To start view: the button is **Start cutting**; "Since" reads the order date ("ordered 15 Oct 2026", "15 days"). Ready to ship view:
   **Ship**. Shipped view: no forward button; the menu has **Move back to Ready to ship**; "Since" is the day shipped.
6. A deleted product's row: "Denim Jacket (deleted)", no link; **Start cutting** can't be pressed, with the reason "Denim Jacket was
   deleted, so it can't be cut." (S-49) in a tip the keyboard reaches; other moves work.

### The move window, opened from Progress
- Today's window (S-61), for the order line of the row, with the colour × size grid of B1: title "Move to Finishing · Denim Jacket",
  under it "Boutique Lina · ordered 15 Oct 2026 · in Sewing: Indigo S 4 · M 6". Fieldset "How many units of each colour and size?":
  one row per colour that has units in Sewing, a field per size that has units there, "Up to 6" under each. The field of the row clicked
  (Indigo M) has the focus; it starts empty.
- Start cutting and Move back to To start keep the materials table, its short-stock check and its messages (S-53), for the total typed.
- **Move units** → toast "6 units of Denim Jacket for Boutique Lina moved to Finishing." (Start cutting: "10 units of Denim Jacket for
  Boutique Lina moved to In cutting. Materials taken out of stock."). The window closes, the focus goes to the next row's button (or the
  heading when the phase is empty), the tiles and the row refresh.

### Order page (changed)
Board: INV-5 · Order page and New order per colour, Settings › Production phases · desktop
- Each line's table: first column **Colour · size**, grouped by colour as on Progress (colour row with its subtotal, then its sizes); the
  columns To start · In cutting · Sewing · Finishing · Ready to ship · Shipped. Total row as today.
- Under the Total row, lined up with the figures (S-71): **Start cutting** · **Move to Sewing** · **Move to Finishing** · **Mark ready** ·
  **Ship**, and below them each **Move back**. A button whose phase holds 0 can't be pressed (grey, S-71).
- The line's heading chips: "Indigo S 10 · M 20 · L 15 · XL 5"; two colours: "Black M 1 · Indigo S 1 · M 2".
- Under "Products": the link **See these units in Progress** (Progress filtered to this order).
- The header, status words ("Not started", "In progress", "Shipped") and "0 of 80 shipped" don't change.

### New order and Edit order (changed, B1)
- A line's sizes become the grid "Quantity per colour and size · Denim Jacket": one row per colour of the product (Settings order), one
  column per size, an empty field in each cell, **Total** on the right of each row and "Total 50 units" under the grid. "One size":
  "Quantity per colour", one field per colour.
- Edit order: a started cell shows "At least 10" under it (S-70's rule, per colour and size).

### Orders list (changed, H1)
- Columns To start · **In production** · Ready to ship · Shipped; totals "Open orders 3 · To start 48 units · In production 26 units ·
  Ready to ship 6 units" and the link **See Progress**.
- Products: "50 × Denim Jacket (Indigo S 10 · M 20 · L 15 · XL 5)".

### Settings › Production phases (A1)
- In the Settings menu, a new group **Production** with **Production phases**.
- Header **Production phases**, description "The phases your units go through between cutting and ready to ship. To start, In cutting,
  Ready to ship and Shipped are always there." Main button **Add phase**.
- A list that shows the whole path: the fixed phases in grey with a lock icon and "Always there" (To start, In cutting … Ready to ship,
  Shipped); the workshop's phases between them, each with its name, "Holds 15 units" or "Empty", and the buttons **Move up**, **Move
  down**, **Edit Sewing** and **Remove Sewing**. No dragging.
- **Add phase** / **Edit Sewing** window: **Name** (up to 24 characters), hint "A short verb or noun your team uses, such as Sewing or
  Quality check." · **Cancel** · **Save phase**.
- Up to 6 phases. A phase that holds units can't be moved or removed (see States).

## States

### Progress, all phases

| State        | On screen |
| ------------ | --------- |
| Default      | Tiles All phases 85 · To start 48 · In cutting 7 · Sewing 15 · Finishing 4 · Ready to ship 6 · Shipped 5; Denim Jacket "54 units on 3 open orders"; Denim Jeans 5-pocket "31 units on 2 open orders" |
| Empty        | `EmptyState`: "Nothing in production" · "When you record an order, its units show here phase by phase until they ship." · **New order**. Tiles show 0 |
| No match     | "No unit matches these filters." · "Try another colour or client, or clear the filters." · **Clear filters** |
| Filtered     | "Showing Indigo · 81 of 85 units" · **Clear filters**; tiles To start 47 · In cutting 4 · Sewing 15 · Finishing 4 · Ready to ship 6 · Shipped 5 |
| Loading      | Header, tiles' frames and filters at once; tile counts and 2 product cards as `Skeleton` |
| Error        | "Couldn't load the progress of your orders." · **Try again** (reloads tiles and tables) |
| Read-only    | Nothing to type here. A cancelled or closed order (S-57, S-65, on hold) never shows on Progress |
| Long content | 60-character product name wraps in the card header; 12 colours × 11 shoe sizes shows only the variants on open orders; 7-digit counts fit the tiles; 25 products → 3 pages |

### Progress, one phase

| State        | On screen |
| ------------ | --------- |
| Default      | Sewing: 3 rows, oldest first, as in Flow step 3 |
| Empty        | "Nothing in Sewing" · "Units come here from In cutting with Move to Sewing." · **Show all phases**. To start empty: "Nothing to start" · "Every unit of your open orders has started." |
| No match     | "Nothing in Sewing matches these filters." · **Clear filters** |
| Loading      | Heading and table head at once; 5 placeholder rows |
| Error        | "Couldn't load what is in Sewing." · **Try again** |
| Disabled     | **Start cutting** on a deleted product: "Denim Jacket was deleted, so it can't be cut." in a keyboard-reachable tip; its name includes the reason |
| Moving       | The window's **Move units** shows a spinner and keeps its label |
| Refused      | In the window, today's messages: "You can move at most 6 units of Indigo M." · "Enter at least one unit to move." · "Size 38: Enter a whole number of units." · Start cutting short of stock: "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer." |
| Changed meanwhile | Someone moved units of the line: the limit is the count saved now, the window's "Up to" and the page refresh (S-49 AC-4). A row whose units all left: the window shows "These units have moved on since you opened this page." and **Close** |
| Success      | Toast "6 units of Denim Jacket for Boutique Lina moved to Finishing."; one unit: "1 unit of …"; back: "4 units of Denim Jacket for Boutique Lina moved back to In cutting." |
| Long content | 40 rows → 2 pages; a 100-character client name is cut with "…", full text on the order |

### Settings › Production phases

| State     | On screen |
| --------- | --------- |
| Default   | To start · In cutting · **Sewing** "Holds 15 units" · **Finishing** "Holds 4 units" · Ready to ship · Shipped |
| No phase  | Between In cutting and Ready to ship: "No phase of your own. Units go from In cutting straight to Ready to ship." · **Add phase** |
| Errors    | "Enter the phase's name." · "Keep the name to 24 characters or fewer." · "You already have a phase named "Sewing"." · "To start, In cutting, Ready to ship and Shipped are already there. Choose another name." · "You can have up to 6 phases." |
| Disabled  | **Move up**, **Move down** and **Remove Sewing** can't be pressed while Sewing holds units: "Sewing holds 15 units, so it can't be moved or removed. Move them on or back first." (tip the keyboard reaches) |
| Confirm   | **Remove Quality check?** · "Quality check is empty. It leaves Progress and every order page; units then go from Finishing to Ready to ship." · **Remove phase** · **Keep phase** |
| Refused meanwhile | Someone moved units into it: "Quality check now holds 2 units, so it can't be removed." Nothing changes |
| Success   | "Phase added." · "Phase updated." · "Phase removed." · "Phases reordered." |

### Order page, New order, Orders list
- Order page default: Boutique Lina's Denim Jacket table as in the example; **Move to Sewing** pressable (4 in cutting).
- New order, errors (per cell, INV-3's naming): "Indigo M: Enter a whole number from 0 to 1,000,000." · "Enter at least one unit of
  Denim Jacket." · "A line can hold up to 1,000,000 units." · Edit order: "Indigo M can't be less than the 10 units already started or
  shipped." · changed meanwhile: "The sizes or colours of Denim Jacket have changed. Enter its quantities again."
- Edit product (INV-1's Colours field): "Light wash is on 1 open order, so it can't be removed from Denim Jacket." (question 10).
- Delete order refusal (S-49) widens to every phase: "Boutique Lina can't be deleted: 29 units are in production or ready to ship.
  Move them back to To start first."

## Wording
- Sidebar and breadcrumbs: "Progress" · "Progress › Sewing".
- Progress page: "Progress" · "Every unit of your open orders, phase by phase, in its colour and size." · "Production phases" · "Phases"
  (the tiles' group) · "All phases" · "To start" · "In cutting" · "Sewing" · "Finishing" · "Ready to ship" · "Shipped" · "15 units" ·
  "Product" · "All products" · "Colour" · "All colours" · "Client" · "All clients" · "Order" · "All open orders" · "Clear filters" ·
  "Showing Indigo · 81 of 85 units" · "54 units on 3 open orders" · "Open orders of Denim Jacket" · "Denim Jacket by phase" ·
  "Colour · size" · "Total" · "Nothing in production" · "When you record an order, its units show here phase by phase until they
  ship." · "New order" · "No unit matches these filters." · "Try another colour or client, or clear the filters." · "Couldn't load the
  progress of your orders." · "Try again".
- Phase view: "15 units · 2 products · 1 open order · oldest since 26 Oct 2026" · "From In cutting. Next: Finishing." · "In Sewing" ·
  "Since" · "4 days" · "1 day" · "today" · "ordered 15 Oct 2026" · "before 30 Oct 2026" · "Units" · "Order" · "Move to Finishing" ·
  "Start cutting" · "Mark ready" · "Ship" · "Move back to In cutting" · "More moves for Denim Jacket Indigo S, Boutique Lina" · "Nothing
  in Sewing" · "Units come here from In cutting with Move to Sewing." · "Show all phases" · "Nothing to start" · "Every unit of your
  open orders has started." · "Nothing in Sewing matches these filters." · "Couldn't load what is in Sewing." · "Denim Jacket
  (deleted)" · "Denim Jacket was deleted, so it can't be cut."
- Move window: "Move to Finishing · Denim Jacket" · "Boutique Lina · ordered 15 Oct 2026 · in Sewing: Indigo S 4 · M 6" · "How many
  units of each colour and size?" · "Up to 6" · "Cancel" · "Move units" · "These units have moved on since you opened this page." ·
  "Close" · and today's messages.
- Toasts: "6 units of Denim Jacket for Boutique Lina moved to Finishing." · "1 unit of Denim Jacket for Amira Ben Salah moved to In
  cutting. Materials taken out of stock." · "4 units of Denim Jacket for Boutique Lina moved back to In cutting." (from the order page,
  today's toasts without "for …", which the page already says).
- Order page: "Colour · size" · "Move to Sewing" · "Move to Finishing" · "Move back" · "See these units in Progress".
- Order form: "Quantity per colour and size · Denim Jacket" · "Quantity per colour" · "Total 50 units" · "At least 10" · the messages
  above.
- Orders list: "In production" · "See Progress".
- Settings › Production phases: "Production" (menu group) · "Production phases" · "The phases your units go through between cutting and
  ready to ship. To start, In cutting, Ready to ship and Shipped are always there." · "Add phase" · "Always there" · "Holds 15 units" ·
  "Empty" · "Move up" · "Move down" · "Edit Sewing" · "Remove Sewing" · "Name" · "A short verb or noun your team uses, such as Sewing or
  Quality check." · "Save phase" · "No phase of your own. Units go from In cutting straight to Ready to ship." · the errors, confirm and
  toasts above.
- Same verb through each flow: **Move to Finishing** → "Move to Finishing · Denim Jacket" → **Move units** → "… moved to Finishing.";
  **Remove Sewing** → "Remove Sewing?" → **Remove phase** → "Phase removed.".

## Accessibility
- **Tiles (1.3.1, 4.1.2, 2.4.4):** links in a `nav` named "Phases", the chosen one `aria-current="page"`; each link's text is its
  phase and count ("Sewing, 15 units"). The chosen state is a 2 px Denim border **and** a tint **and** bold count, never colour alone
  (1.4.1). Denim #2B3A86 border on #FAFAF9: 10.4:1; #97A4EE on the dark card: 7.1:1 (1.4.11).
- **Tables (1.3.1):** a caption ("Denim Jacket by phase", "In Sewing"); colour rows and size rows are row headers (`th scope="row"`),
  so a screen reader hears "Indigo, M, Sewing, 6". The swatch is skipped; the colour's name is read.
- **Buttons (2.4.4, 4.1.2):** a row's button names its row: "Move Denim Jacket Indigo M of Boutique Lina to Finishing". The "⋯" menu has
  its name. A button that can't be pressed keeps focus and gives its reason (tip on focus, and in its name).
- **Focus (2.4.3, 2.4.11):** after a move from Progress, focus goes to the same position's next button, or the heading "Sewing" when the
  phase is empty; never lost behind a sticky bar.
- **Targets (2.5.8; Invory 44 px):** tiles at least 64 px tall; row buttons 44 px; Settings' Move up / Move down 44 px.
- **No dragging (2.5.7):** neither the phases nor the units are dragged; reordering uses Move up / Move down.
- **Contrast (1.4.3):** grey "4 days" and "Always there" in #5F5F66 on white 6.4:1; grey zeros in the S-71 grey (≥4.5:1).
- **Reflow (1.4.10):** at 320 px the tiles are two to a row, the tables become cards; only a deliberate, labelled table region on a
  laptop under 1100 px scrolls sideways inside its card ("Denim Jacket by phase, scroll for more phases").
- **Status messages (4.1.3):** the "Showing Indigo · 81 of 85 units" line and toasts are announced politely.

## Phone (375 px)
Board: INV-5 · Progress, one phase, move window, Settings · 375
- **Progress:** title and description; the tiles two to a row (All phases full width first), 64 px tall; the search-free filter row:
  **Filters** ("Filters · 1" when a filter is set) opens a full-height sheet with Product, Colour, Client and Order; then one card per
  product: name and SKU, "54 units on 3 open orders", then per colour a group (colour tag and "50 units"), and per size one line of its
  phases that hold units: "S · Sewing 4 · Ready to ship 6", "M · To start 6 · In cutting 4 · Sewing 6 · Finishing 4". The Total line
  first ("Total · To start 22 · In cutting 7 · …"), as S-61 put the Total first.
- **One phase:** the tiles as above, the chosen one ringed; heading and figures line; one card per row: "Denim Jacket · ■ Indigo · M" as
  its title, "6 units" bold, "Boutique Lina · ordered 15 Oct 2026" (link), "In Sewing since 28 Oct 2026 · 2 days", **Move to Finishing**
  full width (44 px), **Move back to In cutting** outline full width under it.
- **Move window:** full screen, the grid one colour at a time (the colour tag as the group's title, the size fields four to a row,
  S-70), **Move units** and **Cancel** in a bar fixed at the bottom that never hides the focused field.
- **Order page:** each line's sizes as S-61's cards, grouped by colour, phases with units only; the buttons full width.
- **Settings › Production phases:** one card per phase; fixed phases flat and grey; **Move up**, **Move down**, **Edit**, **Remove** in a
  row of 44 px icon buttons with names; **Add phase** full width.
- At 320 px nothing scrolls sideways.

## For the product-owner
Proposed acceptance-criteria wording for the UI card (with the recommended options A1, B1, C1, D1, E1, F1, G1, H1):
- **Progress page.** Given the example data, Progress shows the tiles "All phases 85 units", "To start 48 units", "In cutting 7 units",
  "Sewing 15 units", "Finishing 4 units", "Ready to ship 6 units" and "Shipped 5 units", then "Denim Jacket · 54 units on 3 open orders"
  with Indigo M "6 · 4 · 6 · 4 · 0 · 0 · 20". The figures equal the sum of the open orders' pages.
- **Filters.** Colour Indigo shows "Showing Indigo · 81 of 85 units", the tiles "To start 47" …, and the address keeps the filter.
- **One phase.** "Sewing" lists, oldest first, Denim Jeans 5-pocket Indigo S 5 · Boutique Lina · since 26 Oct 2026 · 4 days; Denim
  Jacket Indigo S 4 · 27 Oct · 3 days; Denim Jacket Indigo M 6 · 28 Oct · 2 days.
- **Move from Progress.** "Move to Finishing" on Indigo M with 6 shows "6 units of Denim Jacket for Boutique Lina moved to Finishing.";
  Sewing reads 9 and Finishing 10, and Boutique Lina's order page reads Indigo M Sewing 0 · Finishing 10. Start cutting from Progress
  takes the same materials, with the same refusal, as from the order page.
- **Phases.** Settings › Production phases starts with Sewing and Finishing between In cutting and Ready to ship; a phase that holds
  units can't be moved or removed ("Sewing holds 15 units, so it can't be moved or removed. Move them on or back first.").
- **Orders per colour.** New order asks "Quantity per colour and size"; the Orders list reads "50 × Denim Jacket (Indigo S 10 · M 20 ·
  L 15 · XL 5)".
- **Stock unchanged.** Moving to Sewing, Finishing, Ready to ship or Shipped, and back, never changes material stock or "In stock";
  only Start cutting and Move back to To start do (S-53).
- **Tickets at Start cutting.** Start cutting Indigo M 6 and L 10 on Boutique Lina's Denim Jacket shows "2 tickets to print: T-0152
  Indigo M · 6 units, T-0153 Indigo L · 10 units" and **Print 2 tickets**; each ticket shows its number, QR, "Denim Jacket", "Indigo ·
  M", "6 units", "Boutique Lina · ordered 15 Oct 2026", the SKU, the path with the current phase ticked and "Scan to move it to the
  next phase."
- **Scan moves a bundle on.** Scanning T-0142 (6 Indigo M in Sewing) shows **Move to Finishing?** with **Move 6 units to Finishing**;
  confirming shows "6 units of Denim Jacket · Indigo M moved to Finishing." and Progress reads Sewing 9 · Finishing 10. A scan never
  starts cutting and never moves back.
- **The finish.** Scanning a bundle that is ready to ship shows "T-0139 is ready to ship. Its 6 units of Denim Jacket · Indigo S wait
  for Boutique Lina's shipment." and moves nothing.
- **Scan errors.** An unknown code, or another workshop's, shows "This code isn't a work ticket of your workshop. Check the ticket, or
  type its number."; a second scan within 2 minutes shows "T-0142 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on
  to Ready to ship." above the button.
- **Phone.** At 375 px the Scan page shows the camera, the number field and "Your scans today"; the confirm is a bottom sheet whose
  main button is 56 px tall.
- Notes for the card: "In cutting, ready to ship or shipped" in S-49 / S-60 / S-61 messages widens to every phase ("already started or
  shipped", "in production or ready to ship"). S-57 (cancel) and S-65 (close) will need the middle phases when they resume: units in a
  middle phase behave like units in cutting. S-62 ("open orders by size") is covered by Progress and can leave the backlog. S-66 gets
  part of its job done (each move remembered with its day).

## For the tech-lead
- **Values the server sends.**
  - The workshop's phases in order, fixed and own (id, name, position, fixed or not, units held now), and for each phase the forward
    and back move it allows.
  - Progress, all phases: per phase the units on open orders (with the filters applied), the unfiltered total ("of 85 units"); per
    product (10 a page): name, SKU, deleted or not, units on open orders, open orders count, and per colour (Settings order) × size
    (size order) with units, the count per phase and total; colour subtotals and the product total.
  - One phase: rows (order line × colour × size with units there): product, colour, size, units, order id, client name, order date, the
    day the oldest of those units entered the phase and the days since (or "before INV-5"), 25 a page, oldest first; the figures line
    (units, products, open orders, oldest day).
  - Filter options: products, colours, clients (name, phone), open orders (client, date).
  - Order page, order form, Orders list, move windows: counts per order line × colour × size × phase; the Orders list's "In production"
    per order and in the totals.
  - Settings › Production phases: the list with "units held".
- **New rules to encode** (costing skill): the phase list (fixed ends, 0–6 own phases, defaults Sewing and Finishing); stage counts per
  phase on order-line-variant rows; To start = quantity − every other phase; moves between neighbouring phases; only In cutting ↔ To
  start touches stock; "open order" unchanged (S-50); a colour on an open order can't leave its product.
- **New data:** a colour on order line rows (today per size, ADR-0016); a phase per count (the counts become per phase, not per fixed
  column); a dated record of every move, for "since" (E1) — today only cuts are dated.
- **Components:** toggle-group or links for the tiles, table, card, dropdown-menu, tooltip, sheet, popover + command (filters),
  `FiltersBar`, `Pagination`, `EmptyState`, `Skeleton`, sonner; the INV-3 colour × size grid of fields; `SizeChips`. Nothing new from
  shadcn.
- **Costly or risky:** phases as data instead of four fixed columns touches every order screen, the S-50 totals, the delete and edit
  refusals, and every test of moves; the "since" day needs a move log; sums across all open orders per variant need one grouped query
  per page, not one per product.
- **Order lines recorded before colours** are test data and are dropped (decided 2026-10-30).
- **Work tickets (revision 2):**
  - Values: per ticket its number (per workshop, T-0001 …), product, colour, size, units now, phase now, order (client, date), cut
    day, printed or not, "split from", "no longer used" with its day, and its last move (phase, who, when) for the "moved 12 seconds
    ago" warning; per order line its tickets; per phase-view row its ticket numbers; "Your scans today" for the signed-in person.
  - A lookup by QR link and by typed number that answers "unknown" for another workshop's ticket exactly as for one that doesn't
    exist (404, ADR-0013), and a move by ticket that refuses when the ticket's phase or the phase list changed since the confirm opened.
  - Rules to encode: tickets made per colour × size at Start cutting; window moves take oldest tickets first going on, newest first
    going back; a part move splits a ticket; a move back to To start empties tickets; a scan moves the whole ticket one phase on, never
    from To start, never back, never past Ready to ship (E1).
  - The QR's link carries an unguessable code, not the ticket number; generating the QR image (server or a small client library) is
    yours. Printing reuses the labels' new-tab print (INV-2). The phone camera needs a camera-reading library or the browser's barcode
    reader: a new dependency to choose, and HTTPS on the device (fine in production; in development a phone reaching
    `localhost:8000` needs a tunnel or the LAN address).
  - Costly or risky: tickets must stay consistent with every move window (splits), and with S-57 / S-65 when they resume.

## Questions for the product-owner / human
The first eleven are answered (see the decisions at the top), except orders per colour, which is open, to be discussed later. New,
for the QR work tickets:
1. **What does a QR code stand for?** (a) a bundle: the units of one colour and size cut together for one order line, moved whole by a
   scan **recommended** · (b) an order line × colour × size, the scan then asking a phase and a count · (c) one code per order line ·
   default if unanswered: (a).
2. **A confirm on every scan?** (a) yes, one large "Move 6 units to Finishing" button **recommended** · (b) move at once, with Undo in
   the toast for 10 seconds · (c) confirm on phones, at once on the Scan page's scanner field · default: (a).
3. **Where does "the finish" of scanning stop?** (a) at Ready to ship; Ship stays the order page's button **recommended** · (b) scans
   also ship · default: (a).
4. **How to scan?** (a) both: a Scan page in the app (phone camera + a field for a scanner or the typed number), and the phone's own
   camera opening the ticket's link **recommended** · (b) the Scan page only · (c) a scanner field on Progress only, no camera ·
   default: (a).
5. **Big cuts: one ticket per colour and size, or bundles of a set size?** (a) one per colour and size per cut **recommended** · (b)
   Start cutting also asks "Units per ticket (optional)", so 20 Indigo M make two tickets of 10 · default: (a).
6. **Ticket size?** (a) A4 sheet of 8 by default, or 100 × 60 mm labels, chosen in Settings › Labels **recommended** · (b) A4 only ·
   default: (a).
7. **A row's button in the phase view, now that bundles have tickets?** (a) unchanged: it opens the order page's move window, with a
   **Tickets** column added **recommended** · (b) one row per ticket, its button moving the whole bundle like a scan (changes the decided
   phase view) · default: (a).
8. **Scan in the sidebar?** (a) yes, after Progress, plus "Scan a ticket" on the Progress page **recommended** · (b) only the button on
   Progress · default: (a).
