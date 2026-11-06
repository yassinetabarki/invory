---
id: INV-17
title: Per-unit QR codes
type: ui
priority: Should
status: Approved
created: 2026-11-06
approved: 2026-11-06
tags:
  - ui-card
---

# INV-17 · Per-unit QR codes — UI card

> [!abstract] Description
> Today the smallest thing that moves through Progress is a **batch**: scan its work ticket and all its units go to the next phase
> together. But units do not finish together: three of ten Indigo S jackets are ready for Finishing while seven are still being
> sewn. This card adds an **optional QR code on every unit**. A unit gets its own **unit code** (B-0147-03) and a **unit label** to stick
> on the piece; scanning it moves **only that unit**. Batches and their work tickets keep working as they do: the paper in her hand
> decides, a **ticket moves a batch, a unit label moves one unit**.
> New: a tick at Start batch ("Also print a QR code for each unit"); **Print unit codes** in a batch's "⋯" menu; the unit label and its
> printing; **Unit label size** in Settings › Labels; on the Scan page a unit confirm, a switch **Move units as soon as I scan**, and its
> result card; on Progress a **Unit codes** badge, "Split from …" and a line showing where a batch's units are now. Changed: the Scan
> page's button is **Find** (it said **Find ticket**); a move now **joins** a batch of the same start that is already in that phase.
> Built on [[INV-5-progress-ui]] and [[INV-15-work-tickets-scan]]: their wording, layout and tone are kept. Design:
> [INV-17-per-unit-qr.md](../design/specs/INV-17-per-unit-qr.md) and the design canvas "Invory App UI" (the five INV-17 boards).
> Left out on purpose: lost or defective units, undoing a mistaken scan (the way back is Move back and Undo restock), choosing "I am
> scanning at: Sewing" on the Scan page, starting a label sheet at a chosen slot (decided together with INV-9), and any link between a
> unit and an order or a client.

> [!success] Approved by the human on 2026-11-06
> Three points were decided with the approval:
> 1. A unit the system has ahead of the floor is **not refused**: the confirm names the phase as "(now)" and Move back is the way to correct it.
> 2. **Every move joins** a batch of the same start that is already in the phase, batches without unit codes included.
> 3. The two limits (200 units at a start with the tick on, 100 labels printed at a time) are **left to the tech-lead** to set.

> [!info] How it looks today
> Nothing per unit. Batch B-0147 (Denim Jacket · Indigo S) is one row on Progress; its ticket moves all its units at once. To move 3 of
> its 10 units the team opens Progress, chooses **Move to Finishing**, types 3, and the 3 become a batch of their own with a new number
> and ticket. Start batch ends with "2 tickets to print" and nothing else. The Scan page says "Point the camera at the QR code on a work
> ticket.", has the field "Scan or type a batch number" and the button **Find ticket**, and every scan asks for a confirm.
> Settings › Labels has **Ticket size** only.

## Pages

Today is 6 Nov 2026. The workshop's phases are **Sewing** and **Finishing**. The examples use these batches (the next free batch number
is B-0155; the Start batch example below is made after the scan examples, so its numbers are B-0156 and B-0157):

| Batch  | Product · colour · size | Units | Phase      | Started    | Unit codes             |
| ------ | ----------------------- | ----- | ---------- | ---------- | ---------------------- |
| B-0147 | Denim Jacket · Indigo S | 10    | Sewing     | 3 Nov 2026 | B-0147-01 to B-0147-10 |
| B-0149 | Denim Jacket · Indigo M | 6     | Sewing     | 4 Nov 2026 | none                   |
| B-0150 | Denim Jacket · Indigo M | 4     | In cutting | 5 Nov 2026 | B-0150-01 to B-0150-04 |

The shelf for Denim Jacket Indigo S reads 0. The signed-in member is **Admin**.

### How units and batches work
- A **unit code** is the batch number and the unit's own number: **B-0147-03** (two digits; three when the batch has 100 units or more:
  B-0158-112). A unit code **never changes**, even after the unit has moved to another batch.
- A **unit label** is the paper with the unit's QR code and its code. A **ticket** is today's work ticket of a batch, unchanged.
- A **start** is one batch as it was made by Start batch, with every batch its units later split into (so always one product, one
  colour and one size). The Progress line "Sewing 7 · Finishing 3 of 10 started" counts one start.
- **A start never has two batches in the same phase.** A unit or a batch that arrives in a phase where a batch of its start already is
  **joins** it, and the batch that was already there keeps its number. When nothing of its start is there, the units moved become a
  batch of their own with a new number (a whole batch simply moves and keeps its number). This holds for every move, by scan or from the
  windows on Progress, forward or back, and whether or not the units have codes.
- A unit scan moves **one unit one step**. At the last phase it **puts the unit in stock**: one restock of 1 unit, Reference = the
  number of the batch it is in then (**B-0155**), Note "From production". Undoing that restock takes the unit off the shelf; the unit
  does not come back to Progress.
- Moving units from a window on Progress takes the **lowest unit numbers** of a coded batch first. Invory cannot know which piece is
  physically where, so the window says which numbers it moves, and **Move back** is the way to correct a unit that was moved too far.
- Unit codes are made **only when asked**: by the tick at Start batch, or later with **Print unit codes** in a batch's "⋯" menu.
  Batches without unit codes work exactly as they did.
- Moves between phases still never touch materials or the shelf. **Undo batch** (only In cutting) makes the batch's unit codes stop
  working.

### Start batch (window)
Board: INV-17 · Start batch with unit labels, Print unit codes · desktop (Invory App UI canvas)
- Under the grid "How many units of each colour and size?" and above the total, a tick row (44 px): **Also print a QR code for each unit**,
  with the help line "Each unit gets a label to scan on its own. The batch still gets its ticket." **Off every time** the window
  opens; the choice is not remembered.
- The total line adds the labels when the box is ticked: "Total 16 units · 2 batches (Indigo M 6, Indigo L 10) · 16 unit labels" ("1 unit
  label" for one). The materials table is the one for 16 units, unchanged.
- At most **200 units** can be started with the tick on (a placeholder number the tech-lead may set); over it: "A start with unit labels
  can hold up to 200 units. Start fewer units, or untick the box." Everything typed stays.
- **After Start batch, ticked:** title "2 batches started"; the green line "16 units of Denim Jacket started. Materials taken out of stock.";
  then **two boxes**, each with a heading, a list and its own main button:
  - "2 tickets to print": B-0156 · Indigo M · 6 units, B-0157 · Indigo L · 10 units, and **Print 2 tickets**;
  - "16 unit labels to print": B-0156-01 to B-0156-06 · Indigo M, B-0157-01 to B-0157-10 · Indigo L, and **Print 16 unit labels**;
  - then **Done** (outline). Each button opens its own new tab, so the browser lets both through.
  - One batch or one unit reads "1 ticket to print" · **Print ticket** and "1 unit label to print" · **Print unit label**.
  - **Not ticked:** exactly today's end screen, with the ticket box only.
- Example: 16 units of Denim Jacket; "Can produce now" goes from 112 to 96; the shelf is unchanged.

### Progress
Board: INV-17 · Progress, split and regrouped batches, partial moves, Undo batch · desktop (Invory App UI canvas)
- **Batch cell** (phase view): under the number, in this order, only what applies:
  1. the badge **Unit codes** (neutral blue, QR icon, the words always shown); when only some units have a code, **Unit codes: 4 of 7**;
  2. **Split from B-0147**, on a batch made by splitting;
  3. the grey **start line**: where the start's units are now, then how many were started. "Sewing 7 · Finishing 3 of 10 started"; with
     units already in stock "Sewing 7 · Finishing 3 · In stock 2 of 12 started". It shows on every batch of a start whose units are in
     more than one place, and not on a batch that was never split. Two lines at most; the full text is in the cell's tip.
- **Example:** after B-0147-03, -06 and -09 are scanned to Finishing, Sewing shows B-0147 with **7 units** (Unit codes, "Sewing 7 ·
  Finishing 3 of 10 started"); Finishing shows **B-0155**, "Split from B-0147", **3 units**, the same line.
- **The "⋯" menu** of every batch, coded or not, in every phase, gets **Print unit codes B-0147** after **Print ticket B-0147** (In cutting:
  after **Undo batch**).
- **All-phases view:** nothing changes. The grid counts units in each phase, whichever paper moved them; a joined batch counts as one
  batch in "units in N batches".
- **Moving part of a coded batch from a window** (Move to …, Put in stock, Move back to …): under "How many units?" the line "Moves the
  lowest unit numbers: B-0147-01 and B-0147-02." and the grey-blue note
  - when a batch of its start is already in that phase: "These 2 units join batch B-0155, already in Finishing (3 units, 5 after). 5 stay on B-0147.";
  - otherwise INV-5's: "Moving part of a batch splits it: the 2 units moved get their own batch and ticket; 5 stay on B-0147."
  - Toasts: "2 units of Denim Jacket · Indigo S moved to Finishing and joined batch B-0155." or INV-5's split toast with **Print ticket**.
  - A batch without codes gets the same join note and toast, without the "Moves the lowest unit numbers" line.
- **Undo batch B-0150?** on a coded batch adds a warning line: "Its 4 unit codes stop working. Scanning one will say the batch was undone."

### Print unit codes (window)
Board: INV-17 · Start batch with unit labels, Print unit codes · desktop; INV-17 · Unit label 50 x 30 mm and A4 sheet of 24, Settings › Labels · desktop (Invory App UI canvas)
- Opened by **Print unit codes B-0147** in the "⋯" menu. Title "Print unit codes · B-0147"; the grey line "Denim Jacket · Indigo S · in Sewing: 7 units".
  One blue note, by case:
  - **No unit has a code** (B-0149, 6 units): "None of these 6 units has a code yet. Printing makes 6 codes, B-0149-01 to B-0149-06, and prints their labels. A code never changes."
  - **All have a code** (B-0147 after three moved away, 7 units): "All 7 units have a code. Printing again gives the same codes: B-0147-01, 02, 04, 05, 07, 08 and 10."
  - **Some have a code** (4 of 7): "4 of these 7 units have a code. Printing makes codes for the other 3, B-0147-11 to B-0147-13, and prints all 7 labels." New codes
    continue the numbering of the batch's start; they are never reused.
- Then "Total 7 labels · Unit label A4 sheet, 24 labels (3 × 8)", the link **Change in Settings › Labels** (it opens in a new tab and its name
  says so), and **Cancel** · **Print 7 unit labels**. Printing makes the codes and opens the labels in a new tab.
- **Over the limit** (100 labels at a time, a placeholder the tech-lead may set; a batch of 130 units): two fields appear, **From unit** and
  **To unit** (positions 1 to 130), and pressing the button with more than 100 reads "You can print up to 100 labels at a time. Choose a
  shorter range." Nothing is made or printed.

### The unit label
Board: INV-17 · Unit label 50 x 30 mm and A4 sheet of 24, Settings › Labels · desktop (Invory App UI canvas)
- **Content, in this order, both sizes:** the QR code on the left; the unit code in the number font, bold (**B-0147-03**); the product
  name in bold, two lines at most (**Denim Jacket**); colour and size ("Indigo · S"; just the colour, "Camel", for a "One size" product).
  **No price, no client, no phone and no variant barcode.** Always black on white, in dark mode too.
- **50 × 30 mm** (label printer): the QR code 22 mm square with its quiet zone, one label per page. **A4 sheet of 24 labels (3 × 8)**:
  each slot 70 × 37 mm with the QR code 28 mm; more than 24 labels continue on a second sheet. A three-digit code shrinks to stay on
  one line; a long name is cut after two lines.
- Labels come in code order, batch by batch. They print only from Progress (the end of Start batch, **Print unit codes**), in a new tab
  with no app frame that opens the browser's print window once drawn and keeps a **Print** button for a second try, as tickets do. If the
  browser blocks the tab: "Your browser blocked the unit labels' tab. Allow pop-ups for Invory, then print again."
- A unit label's QR code opens the Scan page with that unit's confirm, from the phone's own camera too (signed in; otherwise sign in
  first and come straight back), like a ticket. It holds a secret code of its own, not the unit's number, and is readable at 22 mm.

### Settings › Labels
Board: INV-17 · Unit label 50 x 30 mm and A4 sheet of 24, Settings › Labels · desktop (Invory App UI canvas)
- The page already has **Ticket size**. It gets a second list, **Unit label size**: "A4 sheet, 24 labels (3 × 8)" (the default) or
  "50 × 30 mm, one per label (label printer)", saved with **Save label settings** for the whole workshop. It is separate from the other
  label settings so tickets and unit labels can use different paper.
- The line under the title changes to "How your work tickets and unit labels print."

### Scan page
Board: INV-17 · Scan page: unit confirms, fast scanning on and off, refusals · desktop (Invory App UI canvas)
Same page, header **Scan**. From top to bottom:
1. **The switch row**, full width, 64 px, a real switch (the whole row is the target): the label **Move units as soon as I scan**, the word
   **Off** or **On** in a pill, and one help sentence. It is **Off** at first and is remembered on this device.
   - Off (white row): "Off: every scan asks you to confirm first. Remembered on this device."
   - On (Denim border and light tint, solid pill): "On: a unit label moves its unit at once, with no confirm. A batch ticket still asks first. Remembered on this device."
   - Changing it moves nothing and is announced: "Scans now move units at once." / "Scans now ask first."
2. **The camera** (phone): "Point the camera at the QR code on a work ticket or a unit label." Then the field "Scan or type a batch or unit
   number" (phone: "Or type the batch or unit number") and the button **Find**. Hint: "A scanner works like typing. Each ticket or unit label opens
   its move to confirm." (switch On: "A scanner works like typing. Unit labels move at once; a ticket opens its move to confirm.")
   The field takes a unit as **B-0147-03**, **b-0147-3**, **B147-3** or **147-3**, and a batch as before (**B-0147**, **b-0147**, **B147**, **0147**, **147**).
3. **Switch Off: every scan opens a confirm** (a dialog; a sheet from the bottom on a phone), which never moves anything by itself and never times out.
   - **A unit in Sewing (B-0147-04):** title **Move to Finishing?**; "B-0147-04 · **1 unit** · Denim Jacket · Indigo · S · from batch B-0147"; the path
     "In cutting ✓ · Sewing (now) · **Finishing** · Put in stock"; one grey-blue line: "B-0147 keeps 6 units. This unit joins B-0155, already in
     Finishing (3 units, 4 after)." or, when nothing of its start is in Finishing, "B-0147 keeps 6 units. This unit goes to Finishing as a batch of its
     own."; **Move 1 unit to Finishing** (main, full width, 56 px) and **Not now**. The phase Invory has the unit in is always named "(now)": Invory
     cannot know where the unit was scanned, so a unit it has ahead of the floor is shown, not refused, and the person can correct it with Move back.
   - **A unit in the last phase (B-0147-03 in Finishing):** title **Put 1 unit in stock?**; the same facts line; the path "Finishing (now) · **Put in
     stock**"; "Adds to your finished stock: Indigo S goes from 0 to 1."; **Put 1 unit in stock** and **Not now**. The shelf reads 1 and Restocks lists a
     restock with Reference "B-0155" and Note "From production".
   - **A batch ticket (B-0147, 7 units, units of its start already ahead):** INV-15's confirm with the **current** count: **Move to Finishing?** ·
     "B-0147 · **7 units** · Denim Jacket · Indigo · S · started 3 Nov 2026" · **Move 7 units to Finishing**, and a grey-blue line:
     - units ahead in another phase: "3 units of this start are already ahead (B-0155)."
     - units ahead in the phase it moves to: "3 units of this start are already ahead (B-0155). These 7 units join them in Finishing."
     - with units also in stock: "5 units of this start are already ahead (3 in B-0155, 2 put in stock)."
4. **Switch On: a unit label moves its unit at once.** The page shows a **result slot** under the field (under the camera on a phone), one card at
   a time, each replacing the last; the field stays empty and focused, and the camera ignores a code it has just read for a few seconds:
   - waiting (blue, spinner): "Moving B-0147-04 …";
   - moved (green, check): **B-0147-04 moved to Finishing**, "1 unit · Denim Jacket · Indigo · S · joined batch B-0155 (now 4 units)" (or "new batch
     B-0155, split from B-0147"), the time, and the link "Wrong unit? Move it back on Progress › Finishing.";
   - put in stock (green, check): **B-0147-03 put in stock**, "1 unit · Denim Jacket · Indigo · S · Indigo S goes from 0 to 1", and **See the restock**;
   - not moved (red, alert icon, the words "Not moved"): the refusal sentence;
   - idle: "Ready. Scan the next unit label."
   - **Every batch ticket, and a unit moved less than 2 minutes ago, still opens the normal confirm.** A unit's confirm then carries INV-15's warning,
     "B-0147-04 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock." and the line "\"Move units as soon as I scan\" is
     on, but this unit was just moved, so Invory asks first."
   - A pile of 40 finished jackets puts 40 restocks in Restocks, one per unit.
5. **Your scans today · 4** (the count in the heading): one line per scan, newest first, with a **Units** column ("1 unit", "5 units") that tells a
   unit line from a batch line; the newest 10, then **Show all 37 scans**. Empty: "No scan yet today."

## States

- **Default:** Start batch: the tick off. Progress: as INV-5, plus the badge, "Split from B-0147" and the start line where they apply. Scan: the
  switch **Off**, the field focused (laptop) or the camera (phone).
- **Empty:** no unit codes anywhere: no badge and no start line; Progress and Scan read as in INV-15. Scans: "No scan yet today." Fast mode idle:
  "Ready. Scan the next unit label."
- **Loading:** **Find**: a spinner, the label kept. Fast mode: "Moving B-0147-04 …". **Print unit codes**: a spinner on the button, the label kept.
  Your scans today: the heading and 3 placeholder lines.
- **Error:** "Couldn't move B-0147-04. Check the connection and try again." with **Try again** (in fast mode it scans the code again, so a move that did
  succeed asks first) · "Couldn't load unit B-0147-04. Try again." · "Couldn't make the unit codes. Try again." · "Couldn't load your scans. Try again."
- **Disabled:** there is never a "Print 0 labels": the button names the count. Over the print limit the button stays and the range error shows when pressed.
- **Read-only:** none. Any member of the workshop scans and uses the switch.
- **Long content:** a 60-character product name wraps in the confirm and the result card and is cut after two lines on a label; "B-0158-112" fits on
  one line; 12 phases make the start line wrap to two lines; 37 scans show 10.

**Refusals on the Scan page** (red, with the alert icon; in fast mode with the words "Not moved"; the field is ready again):
- Unit already in stock: "B-0147-03 was put in stock on 6 Nov 2026. Nothing left to do." · **Scan another**
- Batch undone: "B-0150-02 was undone on 6 Nov 2026: its materials went back to stock. Nothing to move." · **Scan another**
- A code that is neither a ticket nor a unit label of the workshop (or is another workshop's): "This code isn't a ticket or a unit label of your
  workshop. Check the paper, or type its number." It never says that another workshop exists.
- A unit number that doesn't exist: "B-0147 has no unit 12. Check the number on the label." · a batch number that doesn't: "No batch B-0999 in
  your workshop. Check the number on the ticket." · not a number: "A batch number looks like B-0142 and a unit number like B-0142-03. Check
  what you typed."
- The ticket of a batch whose units all joined another: "B-0147 has no units left: they joined batch B-0155. Scan the ticket of B-0155 instead."
  (all put in stock: INV-15's "B-0147 was put in stock on 6 Nov 2026. Nothing left to do.")
- Moved meanwhile: "B-0147-04 has moved since you scanned it: it is now in Finishing. Scan it again to move it on." · a ticket whose count
  changed while its confirm was open: "B-0147 has changed since you scanned it: it now holds 6 units. Scan it again to move them on."
- Phases changed meanwhile: "The phases have changed since you scanned. B-0147-04 now goes to Quality check." The button relabels; nothing has moved.
- Product deleted: "Denim Jacket was deleted, so B-0147-03 can't be put in stock." Other moves work.
- Camera refused: "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch or unit number."

## Wording
- Start batch: "Also print a QR code for each unit" · "Each unit gets a label to scan on its own. The batch still gets its ticket." · "Total 16 units · 2 batches
  (Indigo M 6, Indigo L 10) · 16 unit labels" · "2 tickets to print" · "16 unit labels to print" · "Print 2 tickets" · "Print 16 unit labels" · "Print ticket" ·
  "Print unit label" · "1 unit label to print" · "A start with unit labels can hold up to 200 units. Start fewer units, or untick the box."
- Progress: "Unit codes" · "Unit codes: 4 of 7" · "Split from B-0147" · "Sewing 7 · Finishing 3 of 10 started" · "Sewing 7 · Finishing 3 · In stock 2 of 12
  started" · "Print unit codes B-0147" · "Moves the lowest unit numbers: B-0147-01 and B-0147-02." · "These 2 units join batch B-0155, already in Finishing (3
  units, 5 after). 5 stay on B-0147." · "2 units of Denim Jacket · Indigo S moved to Finishing and joined batch B-0155." · "Its 4 unit codes stop working.
  Scanning one will say the batch was undone."
- Print unit codes: "Print unit codes · B-0147" · "Denim Jacket · Indigo S · in Sewing: 7 units" · the three blue notes above · "Total 7 labels · Unit label A4
  sheet, 24 labels (3 × 8)" · "Change in Settings › Labels" · "Print 7 unit labels" · "From unit" · "To unit" · "You can print up to 100 labels at a time. Choose a
  shorter range." · "Your browser blocked the unit labels' tab. Allow pop-ups for Invory, then print again."
- Settings › Labels: "Unit label size" · "A4 sheet, 24 labels (3 × 8)" · "50 × 30 mm, one per label (label printer)" · "How your work tickets and unit labels print."
- Scan: "Move units as soon as I scan" · "Off" · "On" · the two help sentences above · "Scans now move units at once." · "Scans now ask first." ·
  "Point the camera at the QR code on a work ticket or a unit label." · "Scan or type a batch or unit number" · "Or type the batch or unit number" ·
  "Find" · the two hints above · "Move to Finishing?" · "Move 1 unit to Finishing" · "Put 1 unit in stock?" · "Put 1 unit in stock" · "Adds to your finished
  stock: Indigo S goes from 0 to 1." · "Not now" · "(now)" · "Moving B-0147-04 …" · "B-0147-04 moved to Finishing" · "B-0147-03 put in stock" ·
  "Wrong unit? Move it back on Progress › Finishing." · "See the restock" · "Not moved" · "Ready. Scan the next unit label." · "Your scans today" ·
  "Units" · "1 unit" · "5 units" · "Show all 37 scans" · "Scan another" · "No scan yet today.", and the refusals above.
- The unit label: the unit code, the product name, "Indigo · S" (or "Camel"), and nothing else.
- Gone: **Find ticket** (now **Find**), "Point the camera at the QR code on a work ticket.", "Scan or type a batch number".

## Phone layout
Boards: INV-17 · Start batch, unit labels, Progress, Scan (off and on) · 375 (Invory App UI canvas)
- **Start batch:** the tick is a boxed 44 px row under the grid; the bar fixed at the bottom keeps **Start batch** and **Cancel**. The end screen is the
  two boxes one under the other, each button full width.
- **Progress:** the badge, "Split from …" and the start line sit inside the batch card; **More** opens a bottom sheet with its items at 48 px
  (the moves, **Print ticket B-0147**, **Print unit codes B-0147**).
- **Scan:** the switch row first, then the camera square, the result slot **directly under the camera** so it stays on screen on a 375 × 812 phone,
  then the field and **Find**. The confirm is a sheet from the bottom, its buttons one under another (56 px for the move). **Your scans today**
  lines wrap; nothing scrolls sideways at 320 px.
- Every control is at least 44 px on a phone; the labels are drawn for paper, not for the screen.

## Keyboard and screen reader
- The switch is named "Move units as soon as I scan", reads "on" or "off" and its help sentence, and works with Space and Enter.
- A move result is read politely; a refusal and "Couldn't move …" are read as alerts, so a person scanning without looking hears the outcome.
- Dialogs trap the focus, Esc closes, and the focus returns to the field. The main button is never hidden under the keyboard. Colour is
  never the only signal: the switch says On or Off, a result says "moved", "put in stock" or "Not moved" with an icon.

## Acceptance criteria
For the human's validation of the pages; the development card takes them over with its own wording.
- [ ] **AC-1 · The tick.** Start batch for the Denim Jacket with Indigo M 6 and Indigo L 10: ticking **Also print a QR code for each unit** makes
  the total read "Total 16 units · 2 batches (Indigo M 6, Indigo L 10) · 16 unit labels"; the next time the window opens the box is off. After
  **Start batch** the window shows "2 tickets to print" (**Print 2 tickets**) and "16 unit labels to print" (**Print 16 unit labels**); unticked, it ends as today.
- [ ] **AC-2 · The unit label.** A unit label shows the QR code, **B-0157-03**, **Denim Jacket** (two lines at most) and "Indigo · L", black on white, with
  no price, client or phone, on an A4 sheet of 24 or a 50 × 30 mm label as Settings › Labels says (**Unit label size**, default "A4 sheet, 24 labels (3 × 8)").
- [ ] **AC-3 · Print unit codes.** The "⋯" menu of every batch has **Print unit codes B-0147**. For B-0149 (6 units, no codes) it reads "None of these 6 units has
  a code yet. Printing makes 6 codes, B-0149-01 to B-0149-06, and prints their labels. A code never changes."; for B-0147 with 7 coded units "All 7 units have
  a code. Printing again gives the same codes: B-0147-01, 02, 04, 05, 07, 08 and 10."
- [ ] **AC-4 · A unit moves alone.** B-0147 (10 units, Sewing, coded): scanning B-0147-03 and confirming **Move 1 unit to Finishing** leaves B-0147 with 9 units in
  Sewing and a new batch B-0155 with 1 unit in Finishing, "Split from B-0147", "Sewing 9 · Finishing 1 of 10 started"; the shelf is unchanged. After -06 and -09 too,
  B-0147 holds 7 units and B-0155 holds 3, and Finishing never has a second batch of that start.
- [ ] **AC-5 · Units join.** With B-0155 (3 units) in Finishing, scanning B-0147-04 shows "B-0147 keeps 6 units. This unit joins B-0155, already in Finishing (3
  units, 4 after)."; afterwards B-0155 holds 4 units and no new batch exists. Moving 2 of B-0147's units from the window says "Moves the lowest unit numbers:
  B-0147-01 and B-0147-02." and "These 2 units join batch B-0155, already in Finishing (3 units, 5 after). 5 stay on B-0147."
- [ ] **AC-6 · A ticket after units left.** Scanning B-0147's ticket shows **Move to Finishing?**, "B-0147 · **7 units** …", "3 units of this start are already ahead
  (B-0155). These 7 units join them in Finishing." and **Move 7 units to Finishing**; if it holds 6 units when pressed, "B-0147 has changed since you scanned it: it now holds 6 units. Scan it again to move them on."
- [ ] **AC-7 · Put 1 unit in stock.** Scanning B-0147-03 in Finishing reads **Put 1 unit in stock?** with "Adds to your finished stock: Indigo S goes from 0 to 1."; pressing
  it puts the shelf at 1 and Restocks lists one restock, Reference "B-0155", Note "From production". Undo restock takes it off and the unit does not come back.
- [ ] **AC-8 · Fast scanning.** The switch **Move units as soon as I scan** is **Off** at first, says On or Off in words, and is remembered on this device. With it **On**,
  scanning B-0147-04 moves the unit with no confirm and shows "B-0147-04 moved to Finishing" and "1 unit · Denim Jacket · Indigo · S · joined batch B-0155 (now 4 units)";
  scanning a unit at the last phase puts it in stock at once; a unit moved less than 2 minutes ago, and every ticket, still open the confirm.
- [ ] **AC-9 · Refusals.** Each sentence under "Refusals on the Scan page" appears word for word, and Undo batch on a coded batch warns "Its 4 unit codes stop working. Scanning one will say the batch was undone."
- [ ] **AC-10 · Phone.** At 375 px the Start batch tick, the two print boxes, the Progress lines, the Scan switch row and the result slot read as under "Phone layout", with
  no sideways scrolling at 320 px and every control at least 44 px.
