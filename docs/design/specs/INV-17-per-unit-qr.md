# INV-17 · Per-unit QR codes — design spec
Status: proposal · Designer · 2026-11-06 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc) (version 75), row
"Per-unit QR codes (INV-17)":
- "INV-17 · Start batch with unit labels, Print unit codes · desktop" (`project/Inv17StartBatch.dc.html`)
- "INV-17 · Progress, split and regrouped batches, partial moves, Undo batch · desktop" (`project/Inv17Progress.dc.html`)
- "INV-17 · Unit label 50 x 30 mm and A4 sheet of 24, Settings › Labels · desktop" (`project/Inv17UnitLabel.dc.html`)
- "INV-17 · Scan page: unit confirms, fast scanning on and off, refusals · desktop" (`project/Inv17Scan.dc.html`)
- "INV-17 · Start batch, unit labels, Progress, Scan (off and on) · 375" (`project/Inv17Phone.dc.html`)

> **Built on** [INV-5-production-for-the-shelf.md](INV-5-production-for-the-shelf.md) and the cards [[INV-5-progress-ui]] and
> [[INV-15-work-tickets-scan]] (Progress, tickets, the Scan page and its confirm). Their wording, layout and tone are kept; this spec
> only adds. **Designed to the human's decisions of 2026-11-06** (not reopened): model B with regrouping, the batch ticket
> that moves only the units still in its batch, unit codes made by a tick at Start batch or later from "⋯", a unit put in stock one
> restock per scan, a fast-scan switch, and no lost/defective units and no undo of a scan in this card.
> The examples are this spec's own (today is 6 Nov 2026); batch numbers B-0147 and B-0155 are the human's, and the rest of the table
> in INV-5 is not reused.

## Problem and user
The owner and her team move jackets through Sewing and Finishing. Today the smallest thing that moves is a **batch**: scan its ticket and
all its units go to the next phase together. But units do not finish together: three of ten Indigo S jackets are ready for Finishing
while seven are still being sewn. She wants each unit to carry its own QR code so the person at the machine scans **that unit** and only
it moves, while batches keep working as they do. **The question each screen answers:** "which paper am I scanning, and what exactly will
move?" The user chooses the mode by the paper in her hand: a **ticket** moves a batch, a **unit label** moves one unit.
She uses a laptop with a USB scanner at the cutting table, and a phone with its camera on the floor.

## Words (the glossary this spec adds)
| Word | Meaning on screen |
| ---- | ----------------- |
| **Unit code** | The number of one unit: its batch number and its own number, **B-0147-03** (two digits; three when a batch has 100 units or more). It never changes, even after the unit moves to another batch. |
| **Unit label** | The paper with the unit's QR code and code. Stuck on the piece. |
| **Ticket** | Today's work ticket of a batch (INV-15). Unchanged. |
| **Start** | All the batches made by one Start batch and the batches they split into. Shown only as "of 10 started". |
| **Joins** | A unit arriving in a phase where a batch of its start already is goes into that batch; a start never has two batches in one phase. |

The rule for wording: the **code** is the number and its QR, the **label** is the paper. The menu item and the tick keep the human's own
words ("Print unit codes", "Also print a QR code for each unit"); every button that prints says "unit labels".

## Flow
1. **Start with codes.** Start batch, Denim Jacket, Indigo M 6 and Indigo L 10. Under the grid she ticks **Also print a QR code for each unit**
   (off every time). The total line adds "· 16 unit labels". She starts; the window ends on **two boxes**: "2 tickets to print" and
   "16 unit labels to print", each with its own main print button. She prints the labels and sticks one on each piece.
2. **At the sewing table.** Sami scans the label of B-0147-04. The confirm says **Move to Finishing?** and, in one grey-blue line, what happens to
   the batches: "B-0147 keeps 6 units. This unit joins B-0155, already in Finishing (3 units, 4 after)." He presses
   **Move 1 unit to Finishing**.
3. **On Progress.** Sewing shows B-0147 with **7 units** and a grey line "Sewing 7 · Finishing 3 of 10 started"; Finishing shows **B-0155**,
   "Split from B-0147", 3 units, the same grey line. Both carry the badge **Unit codes**.
4. **A pile at the finishing table (fast scanning).** The packer turns on **Move units as soon as I scan**. Each label she scans
   puts its unit in stock at once; the page shows one result card per scan ("B-0147-03 put in stock · Indigo S goes from 0 to 1") and she
   never presses anything. A unit moved less than 2 minutes ago still asks first, so a double read cannot skip a phase.
5. **A ticket after units have left.** Someone scans B-0147's ticket: **Move to Finishing?** for **7 units** (the count now), with
   "3 units of this start are already ahead (B-0155). These 7 units join them in Finishing."
6. **Codes later.** A batch started without codes (B-0149, 6 units) gets them from its "⋯" menu, **Print unit codes B-0149**:
   "None of these 6 units has a code yet. Printing makes 6 codes, B-0149-01 to B-0149-06, and prints their labels."

## Layout

### (a) Start batch and its end screen · board `Inv17StartBatch`, phone frames 1 and 2
- **The tick** is a row under the "How many units of each colour and size?" grid and above the total: a checkbox (44 px row, boxed in Denim when
  ticked) with the label **Also print a QR code for each unit** and the help line "Each unit gets a label to scan on its own. The batch still
  gets its ticket." Off every time the window opens (the human's decision; a "remember my choice" is question 10).
- **The total line** adds the labels when ticked: "Total 16 units · 2 batches (Indigo M 6, Indigo L 10) · 16 unit labels" ("1 unit label").
  The materials table is those of 16 units, unchanged.
- **The end screen**, ticked: title "2 batches started"; the green line "16 units of Denim Jacket started. Materials taken out of stock."; then
  **two boxes**, each a heading, the list and its own main button: "2 tickets to print" (B-0156 · Indigo M · 6 units, B-0157 · Indigo L · 10 units)
  with **Print 2 tickets**, and "16 unit labels to print" (B-0156-01 to B-0156-06 · Indigo M, B-0157-01 to B-0157-10 · Indigo L) with
  **Print 16 unit labels**; then **Done** (outline). Two buttons, two new tabs: each click opens its own tab, so the pop-up blocker lets both through.
  Not ticked: exactly INV-15's end screen. One batch or one unit: "1 ticket to print" · **Print ticket**; "1 unit label to print" · **Print unit label**.
- **Phone:** the tick is a boxed 44 px row under the grid; the bar fixed at the bottom keeps **Start batch** and **Cancel**; the end screen is the
  two boxes one under the other, each button full width.

### (b) Progress · board `Inv17Progress`, phone frame 3
- **Batch rows** (phase view). In the **Batch** cell, under the number:
  1. the badge **Unit codes** (neutral blue, QR icon, the words always there); on a batch where only some units have a code, **Unit codes: 4 of 7**;
  2. **Split from B-0147** on a batch that was made by splitting;
  3. the grey **start line**: where the start's units are now, then how many were started: "Sewing 7 · Finishing 3 of 10 started" (with units
     already in stock: "Sewing 7 · Finishing 3 · In stock 2 of 12 started"). It shows on every batch of a start whose units are in more than one place; a
     batch that was never split shows no line. Two lines at most; the full text is in the cell's tip for long phase names.
  The human's "10 started" and the small "Sewing 7 · Finishing 3 of 10" line are one line here, so the number is not said twice.
- **The "⋯" menu** gets **Print unit codes B-0147** after **Print ticket B-0147**, in every phase (In cutting: after Undo batch). It is on every batch, coded or not.
- **All-phases view:** nothing changes. The grid counts units in each phase, so Indigo S reads Sewing 7 · Finishing 3 · In production 10 whichever paper moved
  them. The product card's "27 units in 7 batches" counts B-0155 as a batch.
- **Moving part of a batch from a window** (Move to …, Put in stock, Move back to …) on a coded batch: under the field, "Moves the lowest unit numbers:
  B-0147-01 and B-0147-02."; and the grey-blue note either "These 2 units join batch B-0155, already in Finishing (3 units, 5 after). 5 stay on B-0147." or
  INV-5's "Moving part of a batch splits it: the 2 units moved get their own batch and ticket; 5 stay on B-0147." Toasts: "2 units of Denim Jacket · Indigo S
  moved to Finishing and joined batch B-0155." / INV-5's split toast with **Print ticket**.
- **Undo batch** on a coded batch adds a warning line: "Its 4 unit codes stop working. Scanning one will say the batch was undone."
- **Phone:** the same lines inside the batch card (badge, "Split from …", the start line), **More** opens a bottom sheet with the three items at 48 px.

### (c) The unit label and its printing · board `Inv17UnitLabel`
- **Content, both sizes, in this order:** the QR code (left); the unit code in the number font, bold ("B-0147-03"); the product name in bold, two lines at most;
  colour and size ("Indigo · S"; "Camel" for a "One size" product). **No price, no client, no phone, no variant barcode.** Always black on white.
- **50 × 30 mm** (label printer): QR 22 mm square with its quiet zone, text column 24 mm. **A4 sheet, 24 labels (3 × 8)**, each slot 70 × 37 mm: the
  same three lines, QR 28 mm. A code of three digits shrinks its size to stay on one line; a long name is cut after two lines.
- **Printed** from Progress only, in a new tab without the app frame that calls the browser's print window once drawn and shows the sheet with a **Print**
  button for a second try (INV-15's pattern). Labels in code order, batch by batch; more than 24 continue on a second sheet.
- **Print unit codes (window from "⋯").** Title "Print unit codes · B-0147", "Denim Jacket · Indigo S · in Sewing: 7 units". One blue note by case:
  none coded ("None of these 6 units has a code yet. Printing makes 6 codes, B-0149-01 to B-0149-06, and prints their labels. A code never changes."), all coded
  ("All 7 units have a code. Printing again gives the same codes: B-0147-01, 02, 04, 05, 07, 08 and 10."), mixed ("4 of these 7 units have a code. Printing makes
  codes for the other 3, B-0147-11 to B-0147-13, and prints all 7 labels."). Then "Total 7 labels · Unit label A4 sheet, 24 labels (3 × 8)", the link **Change in
  Settings › Labels** (new tab, says so in its name, as in INV-9) and **Cancel** · **Print 7 unit labels**. Over the print limit (100, a placeholder): the
  fields **From unit** and **To unit** (positions, 1 to 130) and "You can print up to 100 labels at a time. Choose a shorter range."
- **Settings › Labels** (INV-15 made the page with **Ticket size**): INV-17 adds the list **Unit label size**, "A4 sheet, 24 labels (3 × 8)" (the default) or
  "50 × 30 mm, one per label (label printer)", saved with **Save label settings**; the line under the title becomes "How your work tickets and unit labels print."
  It is **separate from INV-9's Label size** so tickets and unit labels can use different paper. INV-9 later adds its own list and the price tick to this page.
  Nothing of INV-17 waits for INV-9.

### (d) The Scan page · board `Inv17Scan`, phone frames 4 to 6
Same page, header **Scan**. New or changed, top to bottom:
1. **The switch row** under the header: a full-width 64 px button (a real switch; the whole row is the target) with the track, the label **Move units as soon as
   I scan**, the word **Off** or **On** in a pill, and one help sentence. Off: white row, "Off: every scan asks you to confirm first. Remembered on this device."
   On: a Denim 2 px border and light tint, the pill in solid Denim, "On: a unit label moves its unit at once, with no confirm. A batch ticket still asks first.
   Remembered on this device." This one row is the visible state; there is no second banner.
2. The camera line reads "Point the camera at the QR code on a work ticket or a unit label."; the field label "Scan or type a batch or unit number" (phone: "Or type the
   batch or unit number"); the button **Find** (it said "Find ticket"); the hint "A scanner works like typing. Each ticket or unit label opens its move to confirm."
   (switch on: "A scanner works like typing. Unit labels move at once; a ticket opens its move to confirm.").
   The field takes **B-0147-03**, **b-0147-3**, **B147-3** and **147-3** for a unit, and the batch forms of INV-15 for a batch.
3. **Switch off:** every scan opens a confirm (dialog; a sheet from the bottom on a phone), as in INV-15:
   - **Unit** — title **Move to Finishing?**; "B-0147-04 · **1 unit** · Denim Jacket · Indigo · S · from batch B-0147"; the path "In cutting ✓ · Sewing (now) · **Finishing** · Put in
     stock"; one grey-blue line: "B-0147 keeps 6 units. This unit joins B-0155, already in Finishing (3 units, 4 after)." or "B-0147 keeps 6 units. This unit goes to Finishing as a
     batch of its own."; **Move 1 unit to Finishing** (main, full width, 56 px) and **Not now**. The current phase is always named by "(now)", so a unit the system has ahead of the
     floor is visible, not refused (question 3).
   - **Last phase** — title **Put 1 unit in stock?**; the same facts line; "Finishing (now) · **Put in stock**"; "Adds to your finished stock: Indigo S goes from 0 to 1."; **Put 1 unit in stock**
     · **Not now**. It is the Put in stock of Progress: one restock for the unit, Reference = the number of the batch it is in ("B-0155"), Note "From production". Undo restock takes the
     unit off the shelf and the unit does not come back.
   - **Batch ticket** — INV-15's confirm with the **current** count ("B-0147 · **7 units** …", "Move 7 units to Finishing") and, when units of its start are ahead, a grey-blue line:
     "3 units of this start are already ahead (B-0155)." When they sit in the phase it moves to: "3 units of this start are already ahead (B-0155). These 7 units join them in Finishing."
     With units also in stock: "5 units of this start are already ahead (3 in B-0155, 2 put in stock)."
4. **Switch on:** a unit label is moved at once and the page shows the **result slot** under the field (under the camera on a phone), one card at a time, replacing the last:
   - waiting: "Moving B-0147-04 …" (blue, spinner);
   - moved (green, check): **B-0147-04 moved to Finishing** · "1 unit · Denim Jacket · Indigo · S · joined batch B-0155 (now 4 units)" (or "new batch B-0156, split from B-0147") ·
     the time · "Wrong unit? Move it back on Progress › Finishing." (a link; the way back is the existing Move back window);
   - put in stock (green, check): **B-0147-03 put in stock** · "1 unit · Denim Jacket · Indigo · S · Indigo S goes from 0 to 1 · See the restock";
   - not moved (red, alert icon, the words "Not moved"): the refusal sentence, no **Scan another** needed because the field is ready;
   - idle: "Ready. Scan the next unit label."
   The field is empty and focused after every scan; the camera keeps running and ignores a code it has just read for a few seconds.
   **A unit moved less than 2 minutes ago, or any batch ticket, opens the normal confirm** (a unit's with INV-15's warning "B-0147-04 moved to Finishing 12 seconds ago (Admin).
   Scanning again moves it on to Put in stock." and the line `"Move units as soon as I scan" is on, but this unit was just moved, so Invory asks first.`).
5. **Your scans today · 4** (the count in the heading): one line per scan with a **Units** column ("1 unit", "5 units") so a unit line is told from a batch line; the newest 10, then
   **Show all 37 scans**.
6. **Refusals** (inline, with the field ready; INV-15's sentences kept, "ticket" widened): see States.

## States
| State | Content (exact words) |
| ----- | --------------------- |
| **Default** | Start batch: the tick off. Progress: as INV-5, plus badge, "Split from B-0147" and the start line on the rows above. Scan: the switch **Off**, the field focused (laptop) or the camera (phone), "No scan yet today." when empty. |
| **Empty** | Scans: "No scan yet today." No unit codes anywhere: no badge, no start line, the Progress and Scan pages read as in INV-15. Fast mode idle: "Ready. Scan the next unit label." |
| **Loading** | Find: spinner, label kept. Fast mode: "Moving B-0147-04 …" (blue, role status). Print unit codes: spinner on the button, label kept. Your scans today: the heading and 3 placeholder lines. |
| **Error** | "Couldn't move B-0147-04. Check the connection and try again." + **Try again** (in fast mode Try again scans the code again, so a move that did succeed asks first) · "Couldn't load unit B-0147-04. Try again." · "Couldn't make the unit codes. Try again." · "Couldn't load your scans. Try again." |
| **Disabled** | **Print 0 labels** never exists (the button names the count). Over the print limit: the button stays and the range error shows on click. |
| **Read-only** | None: any member of the workshop scans and switches, as today. |
| **Confirm** | The three Scan confirms above; **Undo batch** adds "Its 4 unit codes stop working. Scanning one will say the batch was undone." |
| **Success** | Confirm mode: the page returns to Scan, field focused, the line in Your scans today; Progress windows: the toasts above. Fast mode: the result card. |
| **Long content** | 60-character product names wrap in confirm and result and are cut after two lines on a label; "B-0158-112" fits on one line; 12 phases of the start line wrap to two lines; 37 scans show 10. |

**Refusals** (red, with the alert icon and the words "Not moved" in fast mode):
- Unit already in stock: "B-0147-03 was put in stock on 6 Nov 2026. Nothing left to do." · **Scan another**
- Batch undone: "B-0150-02 was undone on 6 Nov 2026: its materials went back to stock. Nothing to move." · **Scan another**
- Unknown code or another workshop's: "This code isn't a ticket or a unit label of your workshop. Check the paper, or type its number." (never says another workshop exists)
- Unit number that doesn't exist: "B-0147 has no unit 12. Check the number on the label." · batch number: "No batch B-0999 in your workshop. Check the number on the ticket." ·
  not a number: "A batch number looks like B-0142 and a unit number like B-0142-03. Check what you typed."
- Ticket of a batch whose units all joined another: "B-0147 has no units left: they joined batch B-0155. Scan the ticket of B-0155 instead." (all put in stock: INV-15's "was put in stock on …")
- Moved meanwhile: "B-0147-04 has moved since you scanned it: it is now in Finishing. Scan it again to move it on." · a ticket whose count changed while its confirm was open (new): "B-0147 has changed since you scanned it: it now holds 6 units. Scan it again to move them on."
- Phases changed: "The phases have changed since you scanned. B-0147-04 now goes to Quality check." · product deleted: "Denim Jacket was deleted, so B-0147-03 can't be put in stock."
- Camera refused: "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch or unit number."
- Later (out of INV-17): lost or defective units use the same slot, "B-0147-03 was marked lost on …".

## Accessibility
- **Contrast (1.4.3, 1.4.11).** All pairs are the existing tokens: blue note `#1C4A84` on `#E8F0FB` 7.7:1; green `#0B5C4B` on `#E6F4EF` 7.0:1; red `#A3221A` on `#FDE8E6` 6.4:1; amber
  `#8A4405` on `#FDF0DC` 6.4:1; "Unit codes" badge `#1D2770` on `#EEF0FB` 11.7:1. The switch: On track Denim `#2B3990` on white 10.0:1, white "On" pill on Denim 10.0:1; Off track
  border `#91918B` on white 3.2:1, thumb `#5F5F66` on white 6.2:1; "Off" pill `#3A3A40` on `#E6E4DF` 9:1. Dark mode uses the same tokens.
  The label is black on white (21:1).
- **Colour never alone (1.4.1).** The switch says **On** or **Off** in a word and moves its thumb; a result says "moved", "put in stock" or "Not moved" with a check or an alert icon; the badge is
  words; the start line is words and numbers.
- **The switch (4.1.2, 2.1.1).** A real switch, named "Move units as soon as I scan", its state read ("on" / "off") and its help sentence tied to it; operated with Space and Enter. Changing it
  is announced in a polite region ("Scans now move units at once." / "Scans now ask first.") and moves nothing.
- **Results and refusals.** A move result is a polite status; a refusal and "Couldn't move …" are alerts, so a person scanning without looking at the screen hears the outcome (4.1.3).
  A short vibration on a move, where the phone allows it, is a bonus and never the only signal.
- **Confirms (2.4.3, 2.4.7).** Dialogs trap focus, Esc closes, focus returns to the field; the main button is 56 px, never hidden under the keyboard; no time limit (2.2.1).
- **Targets (2.5.8; Invory 44 px).** Switch row 64 px; tick row 44 px with a 24 px box; print buttons, Find, "⋯", More and the menu items 44–48 px; the main confirm button 56 px.
- **Names (2.4.4).** "Print unit codes B-0147", "More moves for batch B-0147", "Move batch B-0147 Denim Jacket Indigo S to Finishing"; tables keep header cells; the "Unit codes" badge is text.
- **Reflow (1.4.10).** Both labels and the scan page work at 320 px; the result slot sits directly under the camera so it stays on screen on a 375 × 812 phone; no sideways scrolling.
- **Motion (2.3.3).** No animation beyond the spinner; the result card is replaced, not animated.

## Options and recommendation
**A. How a unit and a batch share the screen.** (a) **Recommended** — one Scan page; the paper in hand decides (a unit label, a ticket), with the same confirm shape for both. Simple
to learn and it is the human's idea. (b) Two tabs "Units" and "Batches" on the page. A choice the person never needs to make, because the code already says which.
**B. Where the fast switch lives.** (a) **Recommended** — on the Scan page itself, a full-width row at the top that shows its state in words. (b) Settings: hides the one control a
person at the table must be able to see and undo at once. Rejected.
**C. What shows after a fast move.** (a) **Recommended** — a result card in a fixed slot under the field, replaced by the next one, plus the line in Your scans today; the person scanning
a pile reads it by glance. (b) A toast each time: they stack and vanish while she is scanning; a screen reader hears several overlapping. Rejected.
**D. Two print jobs at the end of Start batch.** (a) **Recommended** — two boxes with a main button each. (b) One button that opens both tabs: a pop-up blocker lets only one through. (c) Tickets
and unit labels on one sheet: different sizes. Rejected.
**E. Showing the start's progress.** (a) **Recommended** — one grey line "Sewing 7 · Finishing 3 of 10 started" on each batch of the start, plus "Split from B-0147". (b) A separate "Starts" view
on Progress: a new screen for something the rows already say. Not now.

## For the product-owner
Proposed acceptance wording (examples are this spec's; every sentence is the exact screen wording):
- **AC · The tick.** Given Start batch for the Denim Jacket with Indigo M 6 and Indigo L 10, when I tick **Also print a QR code for each unit**, then the total reads "Total 16 units · 2 batches
  (Indigo M 6, Indigo L 10) · 16 unit labels"; the tick is off every time the window opens. After **Start batch** the window shows "2 tickets to print" with **Print 2 tickets** and "16 unit labels to
  print" with **Print 16 unit labels**; unticked, it ends as today.
- **AC · The unit label.** A unit label shows the QR code, **B-0147-03**, **Denim Jacket** (two lines at most) and "Indigo · S", always black on white, with no price, client or phone, on an A4 sheet of 24 or
  a 50 × 30 mm label as Settings › Labels says (**Unit label size**, default "A4 sheet, 24 labels (3 × 8)").
- **AC · Print unit codes.** The "⋯" menu of every batch has **Print unit codes B-0147**; for B-0149 (no codes) it reads "None of these 6 units has a code yet. Printing makes 6 codes, B-0149-01 to B-0149-06,
  and prints their labels. A code never changes."; for B-0147 (7 units, all coded) "All 7 units have a code. Printing again gives the same codes: B-0147-01, 02, 04, 05, 07, 08 and 10."
- **AC · A unit moves alone.** Given B-0147 (10 units, Indigo S, in Sewing, with unit codes), when I scan B-0147-03 and confirm **Move 1 unit to Finishing**, then Progress shows B-0147 with 9 units in Sewing and a new
  batch with 1 unit in Finishing whose row reads "Split from B-0147" and "Sewing 9 · Finishing 1 of 10 started"; the shelf is unchanged. After B-0147-06 and B-0147-09 are scanned too: B-0147 holds 7 units in Sewing and
  B-0155 holds 3 units in Finishing, never a second batch in Finishing.
- **AC · Units join.** Given B-0155 in Finishing, when I scan B-0147-04, the confirm says "B-0147 keeps 6 units. This unit joins B-0155, already in Finishing (3 units, 4 after)." and afterwards B-0155 holds 4 units and
  no new batch exists.
- **AC · A ticket after units left.** Scanning B-0147's ticket (7 units) reads **Move to Finishing?** · "B-0147 · **7 units** …" · "3 units of this start are already ahead (B-0155). These 7 units join them in Finishing." ·
  **Move 7 units to Finishing**; "B-0147 has moved since you scanned it …" still applies.
- **AC · Put 1 unit in stock.** Scanning B-0147-03 in Finishing reads **Put 1 unit in stock?** with "Adds to your finished stock: Indigo S goes from 0 to 1."; pressing **Put 1 unit in stock** puts the shelf at 1 and
  Restocks lists one restock, Reference "B-0155", Note "From production"; Undo restock takes it off and the unit does not come back.
- **AC · Fast scanning.** On Scan, the switch **Move units as soon as I scan** is **Off** at first, shows "On" or "Off" in words, and is remembered on this device. With it **On**, scanning a unit label moves the unit
  with no confirm and shows "B-0147-04 moved to Finishing" with "1 unit · Denim Jacket · Indigo · S · joined batch B-0155 (now 4 units)"; a unit moved less than 2 minutes ago, and every ticket, still open the confirm.
- **AC · Refusals.** The sentences in States, word for word.
- **AC · Progress windows on a coded batch.** Moving 2 of B-0147's 7 units from the window says "Moves the lowest unit numbers: B-0147-01 and B-0147-02." and "These 2 units join batch B-0155, already in Finishing
  (3 units, 5 after). 5 stay on B-0147."

Open questions are at the end. The microcopy above is exact except where marked as a proposal: the button **Find** (replacing **Find ticket**), "Print unit codes B-0147" (the number added to the menu item as for tickets), and the
words in the blue lines about joining.

## For the tech-lead
- **New shadcn component:** `switch` (not installed). Everything else is existing: checkbox, dialog, sheet, dropdown-menu, badge, alert, table, button.
- **Values the server must send:** per batch — whether and how many units have a code, the start's units per phase and in stock and how many were started, the batch it was split from, and the join target in
  a phase (number, units now, units after); for a scan — the unit's code, its batch, from and to, the join target or "a batch of its own", the shelf now/after, the current count of a ticket's batch, the units of its
  start ahead (by batch and in stock) and when the last move was; for printing — the unit labels asked for (code, product, "Indigo · S", the QR), the label size, the codes a batch's units will get.
- **Writes:** unit codes made at Start batch or later (one number per unit, never changing, continuing the start's numbering for units coded later); a unit scan = split of 1 + move, joining a batch of the start in the
  destination; `MoveBatch` and Put in stock join siblings for every move (question 4); the lowest unit numbers go first in windows; Undo batch kills the batch's unit codes; Put in stock of a unit = one restock.
  A fast scan sends the person's choice with the request, but the server still applies the 2-minute rule and the ticket rule itself.
- **Size and limits to set (the card's numbers are placeholders):** a cap of units per start with codes (200 in the boards); unit labels per print (100; INV-5's tickets allow 50); the unit-number width past 99; one restock
  per fast scan (a pile of 40 puts 40 rows in Restocks, paged 25 at a time).
- **Costly or risky:** the QR link must stay short enough to read at **22 mm on 203 dpi** (about 33 modules; test with a phone camera and the label printer before fixing the size); the camera must ignore the code
  it just read for a few seconds; a retry after "Couldn't move" must be a new scan (not a replay), so a move that did succeed asks first; a keyboard-style scanner ends each code with Enter, and while a confirm is
  open that Enter lands on the focused button (INV-15 has the same risk; fast mode is the volume answer); locks: the production lock around split + join under two phones scanning the same start; "Your scans today" now
  holds many lines.

## Questions for the product-owner / human
1. **Does fast mode also move batch tickets?** (a) No, only unit labels; a ticket always opens its confirm, because it moves many units and carries the "already ahead" line **recommended** · (b) yes, every scan
   moves at once. The switch label says "units". Default: (a).
2. **Does fast mode put a unit in stock at once at the last phase?** (a) Yes: it is the main use of the switch (a pile of finished jackets), one restock per scan, and Undo restock is the way back **recommended** · (b) no:
   moves between phases are fast, Put 1 unit in stock still asks. Default: (a).
3. **A unit the system has ahead of the floor** (a window moved the lowest numbers, but unit 03 is still on the sewing table). A scan cannot know where it was scanned. (a) No refusal: the confirm names where the system
   has the unit ("Finishing (now)") and a scan moves it one step on; the fix is Move back **recommended** · (b) a later card adds "I am scanning at: Sewing" on the Scan page, so a unit that is not in Sewing is refused with
   "B-0147-03 is already in Finishing." — safest for fast mode, but a new control the human did not ask for. Default: (a). Note that your proposed refusal "already in Finishing" needs (b).
4. **How far does the joining rule go, and which number survives?** (a) Every move joins a batch of the same start already in that phase (window moves, ticket moves, unit scans), and the batch that was already there keeps its
   number, so a ticket can end up pointing at a batch with no units ("B-0147 has no units left: they joined batch B-0155 …") **recommended** · (b) only unit scans join; window and ticket moves keep INV-5's split · (c) the lowest number
   survives. Default: (a).
5. **Reference of a unit's restock.** (a) The number of the batch the unit is in then ("B-0155"), as you decided **recommended** · (b) the unit code ("B-0147-03"), which says which piece it was. Default: (a).
6. **A partly used A4 sheet of stickers.** (a) Not in INV-17; decide one "Start at slot" answer with INV-9's labels, which have the same need **recommended** · (b) add a field now. Default: (a).
7. **Unit label size.** (a) A separate list "Unit label size" in Settings › Labels, beside Ticket size **recommended** · (b) one shared "Label size" with INV-9. Default: (a).
8. **Wording.** (a) **Find** instead of **Find ticket**, and "batch or unit number" in the field **recommended** · (b) keep INV-15's words and let the code decide. Default: (a).
9. **Caps.** The boards use 200 units per coded start and 100 labels per print (placeholders). Does the human want a different number? The tech-lead sizes it. Default: the tech-lead's number.
10. **Remember the tick at Start batch on this device?** (a) No, off every time, as decided **recommended** · (b) remember the last choice. Default: (a).
