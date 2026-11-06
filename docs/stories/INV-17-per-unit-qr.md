---
id: INV-17
title: Per-unit QR codes
type: feature
phase: Colour × size variants
priority: Should
size: L
status: Ready
created: 2026-11-06
tags:
  - story
  - variants
---

# INV-17 · Per-unit QR codes

> [!abstract] Description
> As a **workshop owner**, I want **an optional QR code on every unit, and scanning it to move only that unit**, so that **my team moves
> the pieces that are really ready (three of ten Indigo S jackets are ready for Finishing while seven are still being sewn) instead of
> the whole batch or counting units by hand**. A unit gets its own **unit code** (**B-0147-03**) and a **unit label** to stick on the
> piece. The paper in her hand decides: **a ticket moves a batch, a unit label moves one unit**. Batches and their tickets keep working.
> New: the tick "Also print a QR code for each unit" at Start batch; **Print unit codes** in a batch's "⋯" menu; the unit label and its
> printing; **Unit label size** in Settings › Labels; on the Scan page the unit confirm, the switch **Move units as soon as I scan** and its
> result card; on Progress the **Unit codes** badge, "Split from …" and a line showing where a batch's units are now. Changed: the Scan
> page's button is **Find** (it said **Find ticket**), and **every move joins** the batch of the same start that is already in that phase
> (see "How it works today": this changes INV-5's splitting, also for batches without unit codes).
> The pages are drawn in [[INV-17-per-unit-qr-ui]], which builds on [[INV-5-progress-ui]] and [[INV-15-work-tickets-scan]].
> Two limits are placeholders: **200 units** at a start with the tick on, and **100 labels** printed at a time. The tech-lead may set other
> numbers; the sentences then carry them.
> Left out on purpose: lost or defective units; undoing a mistaken scan (the way back is **Move back** and **Undo restock**); choosing
> "I am scanning at: Sewing" on the Scan page; starting a label sheet at a chosen slot (decided together with INV-9); any link between a
> unit and an order or a client.

> [!info] How it works today
> Nothing per unit. The smallest thing that moves is a **batch**: B-0147 (Denim Jacket · Indigo S, 10 units, in Sewing) is one row on
> Progress, and its ticket moves all its units at once. To move 3 of its 10 units, the team opens Progress, chooses **Move to Finishing**,
> types 3, and the 3 become **a batch of their own** with a new number and a new ticket.
> That is true even when a batch of the same start is already in Finishing: moving 2 more later makes yet another batch, so Finishing
> can hold B-0155 and B-0156, both made from B-0147. Only a batch moved whole keeps its number.
> **From now on, units that arrive in a phase where a batch of their start already is join it** (the batch already there keeps its
> number), for every move, by scan or in the windows on Progress, forward or back, with or without unit codes. Only when nothing of the
> start is there do the units become a batch of their own. Batches that already sit side by side in a phase (B-0155 and B-0156 in
> Finishing, both from B-0147) are left as they are: nothing is merged afterwards, and the next move into that phase joins the one with
> the lowest number.
> Start batch ends with "2 tickets to print" and nothing else. The Scan page says "Point the camera at the QR code on a work ticket.", has the
> field "Scan or type a batch number" and the button **Find ticket**, and every scan asks for a confirm that moves the whole batch.
> Settings › Labels has **Ticket size** only.

## Acceptance criteria

Today is 6 Nov 2026. The workshop's phases are **Sewing** and **Finishing**. The examples use these batches (the next free batch
number is B-0155; the Start batch example in AC-1 is made after the scan examples, so its batches are B-0156 and B-0157):

| Batch  | Product · colour · size | Units | Phase      | Started    | Unit codes             |
| ------ | ----------------------- | ----- | ---------- | ---------- | ---------------------- |
| B-0147 | Denim Jacket · Indigo S | 10    | Sewing     | 3 Nov 2026 | B-0147-01 to B-0147-10 |
| B-0149 | Denim Jacket · Indigo M | 6     | Sewing     | 4 Nov 2026 | none                   |
| B-0153 | Denim Jacket · Indigo M | 3     | Finishing  | 4 Nov 2026 | none                   |
| B-0150 | Denim Jacket · Indigo M | 4     | In cutting | 5 Nov 2026 | B-0150-01 to B-0150-04 |

B-0153 was split from B-0149 on 5 Nov 2026. The shelf for Denim Jacket Indigo S reads 0. The signed-in member is named **Admin**. A
**unit code** is the batch number and the unit's own number, two digits (three when the batch has 100 units or more: B-0158-112); it
**never changes**, even after the unit has joined another batch. A **start** is one batch as Start batch made it, with every batch its
units later split into (so always one product, one colour and one size).

- [ ] **AC-1 · The tick at Start batch.** Start batch for the Denim Jacket with Indigo M 6 and Indigo L 10.
  - Under the grid "How many units of each colour and size?" and above the total there is a tick row: **Also print a QR code for each
    unit**, with the help line "Each unit gets a label to scan on its own. The batch still gets its ticket.". It is **off every time**
    the window opens; the choice is not remembered.
  - Ticked, the total reads "Total 16 units · 2 batches (Indigo M 6, Indigo L 10) · 16 unit labels" ("1 unit label" for one). The
    materials taken, "Can produce now" and the shelf are exactly what Start batch gives without the tick.
  - After **Start batch**, ticked: the window reads "2 batches started" and "16 units of Denim Jacket started. Materials taken out of
    stock.", then two boxes, each with its own main button: "2 tickets to print" (B-0156 · Indigo M · 6 units, B-0157 · Indigo L · 10
    units) with **Print 2 tickets**, and "16 unit labels to print" (B-0156-01 to B-0156-06 · Indigo M, B-0157-01 to B-0157-10 · Indigo L)
    with **Print 16 unit labels**; then **Done**. Each button opens its own new tab and prints every label of the start in one go.
    One batch and one unit read "1 ticket to print" · **Print ticket** and "1 unit label to print" · **Print unit label**.
  - Not ticked: the window ends as today, with the ticket box only, and no unit codes are made.
  - Over the limit (200 units, a placeholder), ticked: "A start with unit labels can hold up to 200 units. Start fewer units, or
    untick the box." Nothing is started and everything typed stays.

- [ ] **AC-2 · The unit label, and its size.** A unit label shows, in this order: the QR code on the left; the unit code in the number
  font, bold (**B-0157-03**); the product name in bold, two lines at most (**Denim Jacket**); colour and size ("Indigo · L"; only the
  colour, "Camel", for a "One size" product). No price, client, phone or variant barcode. It is always black on white, in dark mode too.
  - **Unit label size** is a second list in Settings › Labels, beside **Ticket size**: "A4 sheet, 24 labels (3 × 8)" (the default) or "50
    × 30 mm, one per label (label printer)", saved with **Save label settings** for the whole workshop. The line under the title reads
    "How your work tickets and unit labels print.".
  - On the A4 sheet each slot is 70 × 37 mm with the QR code 28 mm square; more than 24 labels continue on a second sheet. On the 50 × 30
    mm label the QR code is 22 mm square with its quiet zone, one label per page. A three-digit code (B-0158-112) stays on one line; a
    long name is cut after two lines.
  - Labels come in code order, batch by batch. They print only from Progress, in a new tab with no app frame that opens the browser's
    print window once drawn and keeps a **Print** button for a second try, as tickets do. If the browser blocks the tab: "Your browser
    blocked the unit labels' tab. Allow pop-ups for Invory, then print again."
  - The QR code holds a link with a secret code of its own, not the unit's number, and is readable at 22 mm. The phone's own camera,
    a scanner and the Scan page all read it: signed in, it opens the Scan page with that unit's confirm; not signed in, the sign-in
    page, then straight back to that confirm.

- [ ] **AC-3 · Print unit codes.** The "⋯" menu of every batch, with or without codes, in every phase, has **Print unit codes B-0147** after
  **Print ticket B-0147** (In cutting: after **Undo batch**). It opens "Print unit codes · B-0147", the grey line "Denim Jacket · Indigo
  S · in Sewing: 7 units" (B-0147 after three units left) and one blue note:
  - No unit has a code (B-0149, 6 units): "None of these 6 units has a code yet. Printing makes 6 codes, B-0149-01 to B-0149-06, and
    prints their labels. A code never changes."
  - All have a code (B-0147, 7 units): "All 7 units have a code. Printing again gives the same codes: B-0147-01, 02, 04, 05, 07, 08 and 10."
  - Some have a code (a batch of 7 units, 4 coded, whose start has used codes up to 10): "4 of these 7 units have a code. Printing makes
    codes for the other 3, B-0147-11 to B-0147-13, and prints all 7 labels." New codes continue the numbering of the batch's start and
    are never reused.
  - Then "Total 7 labels · Unit label A4 sheet, 24 labels (3 × 8)", the link **Change in Settings › Labels** (it opens in a new tab and its
    name says so), **Cancel** and **Print 7 unit labels**. Printing makes the codes and opens the labels in a new tab. The button names the
    count; there is never a "Print 0 labels".
  - Afterwards the batch shows the badge **Unit codes** (the words always shown), or **Unit codes: 4 of 7** when only some units have a code.
  - Over the limit (100 labels at a time, a placeholder; a batch of 130 units): two fields appear, **From unit** and **To unit**
    (positions 1 to 130); pressing the button with more than 100 reads "You can print up to 100 labels at a time. Choose a shorter
    range." Nothing is made or printed. The limit applies to this window; the end screen of AC-1 prints the whole start.

- [ ] **AC-4 · A unit moves alone.** The Scan page keeps its header **Scan**; the button is **Find** (it said **Find ticket**), the field
  reads "Scan or type a batch or unit number" and takes a unit as **B-0147-03**, **b-0147-3**, **B147-3** or **147-3**, and a batch as before
  (**B-0147**, **b-0147**, **B147**, **0147**, **147**). With the switch **Off** (AC-8) every scan opens a confirm that never moves
  anything by itself and never times out. B-0147 (10 units, coded, in Sewing), nothing of its start in Finishing:
  - Scanning B-0147-03 opens **Move to Finishing?**, "B-0147-03 · **1 unit** · Denim Jacket · Indigo · S · from batch B-0147", the path
    "In cutting ✓ · Sewing (now) · **Finishing** · Put in stock" and the grey-blue line "B-0147 keeps 9 units. This unit goes to
    Finishing as a batch of its own.", with **Move 1 unit to Finishing** and **Not now**. **Not now** moves nothing. A unit in B-0150
    (In cutting) offers **Move 1 unit to Sewing**.
  - **Move 1 unit to Finishing** leaves B-0147 with 9 units in Sewing and makes B-0155 with 1 unit in Finishing since today. B-0155 shows
    **Split from B-0147**, the badge **Unit codes**, and both batches show the grey line "Sewing 9 · Finishing 1 of 10 started" (two
    lines at most; the full text in the cell's tip). The unit keeps the code B-0147-03. The shelf, the materials and "Can produce now" don't
    change. The toast reads "1 unit of Denim Jacket · Indigo S moved to Finishing." and the field is empty and ready for the next scan.
  - After B-0147-06 and B-0147-09 are scanned the same way, B-0147 holds 7 units and B-0155 holds 3, "Sewing 7 · Finishing 3 of 10
    started", and Finishing never has a second batch of that start (AC-5).
  - Invory cannot know where a unit was scanned. The phase it has the unit in is always named "(now)"; a unit it has ahead of the floor
    is **not refused**, and **Move back** on Progress corrects one that was moved too far.
  - **Your scans today · 4** lists one line per scan, newest first, with a **Units** column ("1 unit", "5 units") that tells a unit line
    from a batch line; the newest 10, then **Show all 37 scans**. Empty: "No scan yet today."

- [ ] **AC-5 · Every move joins.** A start never has two batches in the same phase. Units that arrive in a phase where a batch of their
  start already is **join** it, and that batch keeps its number, whatever moved them: a unit scan, a ticket scan, or **Move to …** and
  **Move back to …** in the windows on Progress; with or without unit codes. When nothing of the start is there, the units become a batch
  of their own with a new number (a whole batch simply moves and keeps its number). B-0147 holds 7 units in Sewing (B-0147-01, 02, 04, 05, 07,
  08, 10) and B-0155 holds 3 in Finishing:
  - Scanning B-0147-04 shows "B-0147 keeps 6 units. This unit joins B-0155, already in Finishing (3 units, 4 after)." Afterwards B-0155
    holds 4 units and no new batch exists.
  - From the same state, **Move to Finishing** on B-0147 with 2 units says under "How many units?" "Moves the lowest unit numbers: B-0147-01
    and B-0147-02." and in the grey-blue note "These 2 units join batch B-0155, already in Finishing (3 units, 5 after). 5 stay on B-0147."
    The toast reads "2 units of Denim Jacket · Indigo S moved to Finishing and joined batch B-0155." Moving all 7 says "These 7 units join
    batch B-0155, already in Finishing (3 units, 10 after)." (the last sentence is dropped); B-0147 then leaves Progress.
  - When nothing of the start is in the phase, INV-5's note and toast stay: "Moving part of a batch splits it: the 2 units moved get their
    own batch and ticket; 5 stay on B-0147." and the split toast with **Print ticket**.
  - A batch without codes follows the same rule, without the "Moves the lowest unit numbers" line: B-0149 (6 units, Sewing) and B-0153 (3
    units, Finishing): moving 2 of B-0149's units says "These 2 units join batch B-0153, already in Finishing (3 units, 5 after). 4 stay
    on B-0149." and no new batch is made, where INV-5 would have made one. Moving 1 unit of B-0153 back to Sewing joins B-0149 the same way.
  - Batches made before this change are not merged. If B-0155 and B-0156 (both from B-0147) already sit in Finishing, they stay two
    batches, and the next unit or units moved into Finishing from that start join B-0155, the lower number (the note and the toast name
    B-0155).
  - The **All phases** view changes nothing: its grid counts the units in each phase, whichever paper moved them, and a joined batch
    counts as one batch in "units in N batches".

- [ ] **AC-6 · A ticket after units left.** A ticket still moves a batch, with its current units, and always asks first, with the switch
  on or off. B-0147's ticket (7 units in Sewing; B-0155 holds 3 units of its start in Finishing) opens **Move to Finishing?**, "B-0147 ·
  **7 units** · Denim Jacket · Indigo · S · started 3 Nov 2026", **Move 7 units to Finishing**, and a grey-blue line:
  - units of the start ahead in another phase: "3 units of this start are already ahead (B-0155).";
  - units ahead in the phase it moves to (this case): "3 units of this start are already ahead (B-0155). These 7 units join them in
    Finishing.";
  - units also put in stock: "5 units of this start are already ahead (3 in B-0155, 2 put in stock).".
  - Pressing the button: B-0155 holds 10 units and B-0147 leaves Progress. If B-0147 holds 6 units when the button is pressed, nothing
    moves and the confirm says "B-0147 has changed since you scanned it: it now holds 6 units. Scan it again to move them on."

- [ ] **AC-7 · Put 1 unit in stock.** B-0155 holds 3 units in Finishing, the last phase (B-0147-03, -06 and -09). Scanning B-0147-03 (switch
  Off) opens **Put 1 unit in stock?**, "B-0147-03 · **1 unit** · Denim Jacket · Indigo · S · from batch B-0155", the path "Finishing (now) ·
  **Put in stock**", "Adds to your finished stock: Indigo S goes from 0 to 1.", **Put 1 unit in stock** and **Not now**.
  - Pressing it puts the shelf for Indigo S at 1 and Restocks lists one restock of 1 unit, dated today, by Admin, Reference "B-0155"
    (the batch the unit is in then) and Note "From production". B-0155 holds 2 units, and the start line reads "Sewing 7 · Finishing 2 ·
    In stock 1 of 10 started". Materials and "Can produce now" don't change. Toast: "1 unit of Denim Jacket · Indigo S put in stock."
  - Each unit is its own restock: a pile of 40 finished jackets scanned one by one lists 40 restocks.
  - **Undo restock** takes the unit off the shelf (Indigo S back to 0); the unit does not come back to Progress and its materials stay used.

- [ ] **AC-8 · Fast scanning.** The first row of the Scan page is a switch, the whole row its target: the label **Move units as soon as I
  scan**, the word **Off** or **On** in a pill, and one help sentence. It is **Off** at first and is remembered on this device.
  - Off: "Off: every scan asks you to confirm first. Remembered on this device." On: "On: a unit label moves its unit at once, with no
    confirm. A batch ticket still asks first. Remembered on this device." Changing it moves nothing and is announced: "Scans now move
    units at once." / "Scans now ask first." The field hint reads "A scanner works like typing. Each ticket or unit label opens its move
    to confirm." (On: "A scanner works like typing. Unit labels move at once; a ticket opens its move to confirm.")
  - On, scanning B-0147-04 moves the unit with no confirm, and a result card under the field (under the camera on a phone) replaces the
    last one: "Moving B-0147-04 …" (waiting); then **B-0147-04 moved to Finishing** with "1 unit · Denim Jacket · Indigo · S · joined
    batch B-0155 (now 4 units)" (or "new batch B-0155, split from B-0147"), the time, and the link "Wrong unit? Move it back on Progress ›
    Finishing."; a unit at the last phase puts itself in stock at once: **B-0147-03 put in stock**, "1 unit · Denim Jacket · Indigo · S ·
    Indigo S goes from 0 to 1" and **See the restock**; a refusal shows "Not moved" and its sentence (AC-9); idle: "Ready. Scan the next unit
    label." The field stays empty and focused, and the camera ignores a code it has just read for a few seconds (the tech-lead sets how many).
  - Every batch ticket, and a unit moved less than 2 minutes ago, still opens the normal confirm. That unit's confirm carries the warning
    "B-0147-04 moved to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock." and the line "\"Move units as soon
    as I scan\" is on, but this unit was just moved, so Invory asks first."
  - The switch is named "Move units as soon as I scan", reads "on" or "off" with its help sentence, and works with Space and Enter. A move
    result is read politely; a refusal and "Couldn't move …" are read as alerts. Colour is never the only signal.

- [ ] **AC-9 · Refusals, errors and Undo batch.** Nothing moves in these cases; each is shown in red with the alert icon (in fast mode with
  the words "Not moved") and the field is ready again:
  - Unit already in stock: "B-0147-03 was put in stock on 6 Nov 2026. Nothing left to do." · **Scan another**.
  - Batch undone: "B-0150-02 was undone on 6 Nov 2026: its materials went back to stock. Nothing to move." · **Scan another**.
  - A code that is neither a ticket nor a unit label of the workshop (or is another workshop's): "This code isn't a ticket or a unit
    label of your workshop. Check the paper, or type its number." It never says that another workshop exists.
  - A unit number that doesn't exist: "B-0147 has no unit 12. Check the number on the label." A batch number that doesn't: "No batch
    B-0999 in your workshop. Check the number on the ticket." Not a number: "A batch number looks like B-0142 and a unit number like
    B-0142-03. Check what you typed."
  - A ticket of a batch whose units all joined another: "B-0149 has no units left: they joined batch B-0153. Scan the ticket of B-0153
    instead." (All put in stock: "B-0149 was put in stock on 6 Nov 2026. Nothing left to do.")
  - Moved meanwhile: "B-0147-04 has moved since you scanned it: it is now in Finishing. Scan it again to move it on."
  - Phases changed meanwhile: "The phases have changed since you scanned. B-0147-04 now goes to Quality check." The button relabels.
  - Product deleted: "Denim Jacket was deleted, so B-0147-03 can't be put in stock." Other moves work.
  - Camera refused: "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch or unit number."
  - Errors with **Try again**: "Couldn't move B-0147-04. Check the connection and try again." (in fast mode it scans the code again, so a
    move that did succeed asks first) · "Couldn't load unit B-0147-04. Try again." · "Couldn't make the unit codes. Try again." · "Couldn't load
    your scans. Try again." While loading, **Find** and **Print unit codes** show a spinner and keep their label; Your scans today shows its
    heading and 3 placeholder lines.
  - **Undo batch B-0150?** on a coded batch adds the warning "Its 4 unit codes stop working. Scanning one will say the batch was undone."

- [ ] **AC-10 · Phone.** At 375 px nothing scrolls sideways (also at 320 px) and every control is at least 44 px.
  - Start batch: the tick is a boxed 44 px row under the grid; the bar fixed at the bottom keeps **Start batch** and **Cancel**; the end
    screen is the two boxes one under the other, each button full width.
  - Progress: the badge, "Split from …" and the start line sit inside the batch card; **More** opens a bottom sheet with its items at 48 px
    (the moves, **Print ticket B-0147**, **Print unit codes B-0147**).
  - Scan: the switch row first, then the camera ("Point the camera at the QR code on a work ticket or a unit label."), the result card
    directly under it so it stays on screen on a 375 × 812 phone, then the field ("Or type the batch or unit number") and **Find**. The
    confirm is a sheet from the bottom, its buttons one under another (the move button 56 px). **Your scans today** lines wrap.
