---
id: INV-15
title: Work tickets and the Scan page
type: feature
phase: Colour × size variants
priority: Must
size: M
status: Ready
created: 2026-11-02
tags:
  - story
  - variants
---

# INV-15 · Work tickets and the Scan page

> [!abstract] Description
> As a **workshop owner**, I want **a printed work ticket on every batch and a Scan page that moves a batch on when its ticket is
> scanned**, so that **my team moves batches through the phases from the workshop floor, with a phone or a scanner, without opening
> Progress and looking for the batch**. A **work ticket** stands for one batch and carries its number (**B-0147**) and a QR code. It
> names no client, no phone number and no price: it lies on the workshop floor.
> New: the work tickets and their printing from Progress (at the end of Start batch, from the "⋯" menu, from a split's toast); the
> **Scan** page in the sidebar and **Scan a ticket** on Progress; the scan confirm, which moves a whole batch to its next phase or puts
> it in stock at the last phase; opening a ticket from a phone's own camera. Changed: Settings › Labels gets a **Ticket size**
> (**waits for INV-9**, which makes that page; until then tickets print on the A4 sheet of 8). The pages are drawn in
> [[INV-5-progress-ui]] ("Work tickets", "Scan page and the scan confirm", their states, wording and phone layout).
> Left out on purpose: moving back by scan (it stays in the windows on Progress); moving part of a batch by scan; tickets on orders;
> printing several chosen batches at once from Progress; a history of scans beyond today.

> [!info] How it works today
> INV-5 is done: Progress lists the batches phase by phase, and each batch moves with **Move to …**, **Move back to …**, **Put in
> stock** and **Undo batch** in windows on Progress. A batch has its number (B-0147) but nothing is printed: the team writes the number
> on a slip of paper, and to move a batch someone opens Progress, picks the phase, finds the row and presses the button.
> Start batch ends with "2 batches started" · "16 units of Denim Jacket started. Materials taken out of stock." · the batches · **Done**,
> with nothing to print. A split's toast reads "… Batch B-0149 was split: the units moved are on B-0155." with no button. The "⋯" menu
> of a batch has only its moves (and **Undo batch** In cutting). The sidebar reads Dashboard, Materials, Products, Restocks, Orders,
> Progress, Clients, Sales. Progress's header has **Production phases** and **Start batch**. Settings › Labels doesn't exist yet (INV-9).

## Acceptance criteria

Today is 30 Oct 2026. The workshop's phases are **Sewing** and **Finishing**. The examples use INV-5's batches:

| Batch  | Product · colour · size         | Units | Phase      | Since       | Started     |
| ------ | ------------------------------- | ----- | ---------- | ----------- | ----------- |
| B-0146 | Denim Jeans 5-pocket · Indigo S | 5     | Sewing     | 26 Oct 2026 | 24 Oct 2026 |
| B-0147 | Denim Jacket · Indigo S         | 4     | Sewing     | 27 Oct 2026 | 23 Oct 2026 |
| B-0149 | Denim Jacket · Indigo M         | 6     | Sewing     | 28 Oct 2026 | 25 Oct 2026 |
| B-0148 | Denim Jacket · Indigo M         | 4     | Finishing  | 29 Oct 2026 | 25 Oct 2026 |
| B-0150 | Denim Jacket · Indigo M         | 4     | In cutting | 25 Oct 2026 | 25 Oct 2026 |

B-0139 (Denim Jacket · Indigo L) was put in stock on 28 Oct 2026. The Denim Jacket's SKU for Indigo S is PRD-JKT-001-IND-S. The shelf
for Denim Jacket Indigo M reads 0. The signed-in member is named **Admin**.

- [ ] **AC-1 · The work ticket.** A ticket is 105 × 74 mm, black on white, and reads from top to bottom: "Work ticket" and, large,
  **B-0147**; on the left the QR code (30 mm); on the right **Denim Jacket** (bold, at most two lines), "Indigo · S", **4 units**
  (large), "Started 23 Oct 2026" and "PRD-JKT-001-IND-S" in the number font; the path as the phases are when it is printed, the current
  one ticked (printed at Start batch: "☑ In cutting ☐ Sewing ☐ Finishing ☐ Put in stock"; B-0147 printed today ticks Sewing), and
  "Printed 30 Oct 2026"; the bottom line "Scan to move it to the next phase.". A batch split from
  another adds "Split from B-0149". No client, no phone number and no price appear on a ticket. The units are the batch's units when
  printed: after 1 of B-0148's 4 units is put in stock, a ticket printed again reads **3 units**.

- [ ] **AC-2 · Printing tickets from Progress.** Tickets print only from Progress, in a new tab with the browser's print window, as
  INV-2's labels do; until Ticket size exists (AC-7) they print on an **A4 sheet, 8 tickets (2 × 4)**.
  - **Start batch** ends with "2 batches started" · "16 units of Denim Jacket started. Materials taken out of stock." · "2 tickets to
    print: B-0153 · Indigo M · 6 units, B-0154 · Indigo L · 10 units" · **Print 2 tickets** (main) · **Done**. One batch reads "1 ticket
    to print: …" and **Print ticket**.
  - The "⋯" menu of every batch, in every phase, has **Print ticket B-0147** (after the moves; In cutting after **Undo batch**).
  - Moving part of a batch: the toast "4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was split: the units moved
    are on B-0155." has **Print ticket B-0155**.
  - The order page has no ticket and no print button.
  - If the browser blocks the new tab: "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again." Nothing is
    lost: the button can be pressed again.

- [ ] **AC-3 · The Scan page.** The sidebar reads Dashboard, Materials, Products, Restocks, Orders, Progress, **Scan** (QR icon), Clients,
  Sales; the breadcrumb is "Scan". Progress's header gets **Scan a ticket** (outline) between **Production phases** and **Start batch**;
  it opens the Scan page. Any signed-in member of the workshop can scan.
  - **Phone:** the camera view with a frame and "Point the camera at the QR code on a work ticket."; under it "Or type the batch
    number", the field and **Find ticket**.
  - **Laptop:** the field "Scan or type a batch number" has the focus; the hint "A scanner works like typing. Each ticket opens its move
    to confirm." A USB or Bluetooth scanner reading a ticket opens its confirm (AC-4) with nothing else to press. With no camera, the field only.
  - Typing **B-0147**, **b-0147**, **B147** or just **147** and **Find ticket** opens B-0147's confirm.
  - Under it, **Your scans today**: one line per move this member made from the Scan page today, newest first: batch, product,
    "→ Finishing" (or "→ Put in stock") and the time. Empty: "No scan yet today."
  - On a phone, nothing scrolls sideways at 320 px and every control is at least 44 px.

- [ ] **AC-4 · The scan confirm.** Each scan opens one confirm; it never moves anything by itself and never times out.
  - B-0147 (in Sewing): the title **Move to Finishing?**; "B-0147 · **4 units** · Denim Jacket · Indigo · S · started 23 Oct 2026"; the
    path "In cutting ✓ · Sewing (now) · **Finishing** · Put in stock"; **Move 4 units to Finishing** (main, full width, 56 px tall) and
    **Not now**. Pressing it moves the whole batch, exactly as **Move to Finishing** does on Progress (B-0147 keeps its number, its
    "Since" becomes 30 Oct 2026), shows "4 units of Denim Jacket · Indigo S moved to Finishing." and makes the next scan ready. **Not
    now** moves nothing.
  - B-0150 (In cutting) offers **Move 4 units to Sewing**.
  - At the last phase the scan finishes the batch: B-0148 (in Finishing) reads **Put 4 units in stock?**, with "Adds to your finished
    stock: Indigo M goes from 0 to 4." and **Put 4 units in stock**. It is the same Put in stock as on Progress: the shelf reads 4 and
    Restocks lists a restock with Reference "B-0148" and Note "From production". Toast: "4 units of Denim Jacket · Indigo M put in
    stock." With no phase of the workshop's own, a batch In cutting is put in stock the same way.
  - There is no moving back and no moving part of a batch by scan.
  - On a phone the confirm is a sheet from the bottom, its buttons one under another.

- [ ] **AC-5 · Opening a ticket with a phone's camera.** The QR code holds a link to the batch in Invory with a secret code of its own;
  the phone's own camera app, a scanner and the Scan page all read it. Opening it while signed in shows the Scan page with that
  batch's confirm (AC-4). Not signed in: the sign-in page, then straight back to that confirm. A split batch's ticket has its own code.
  A ticket's code keeps working while the batch exists, whatever phase it is in.

- [ ] **AC-6 · When a scan can't move the batch.** Nothing moves in any of these cases:
  - Already in stock (B-0139): "B-0139 was put in stock on 28 Oct 2026. Nothing left to do." · **Scan another**.
  - Undone (after **Undo batch** on B-0150 today): "B-0150 was undone on 30 Oct 2026: its materials went back to stock. Nothing to move." · **Scan another**.
  - A code that isn't a ticket, or another workshop's ticket: "This code isn't a work ticket of your workshop. Check the ticket, or type
    its number." It never says that another workshop exists.
  - A number that doesn't exist: "No batch B-0999 in your workshop. Check the number on the ticket." Something that isn't a batch
    number: "A batch number looks like B-0142. Check what you typed."
  - Scanned twice quickly: when the batch's last move was less than 2 minutes ago, a warning line above the button reads "B-0147 moved
    to Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock." The button still works.
  - Moved meanwhile (someone moved it while the confirm was open): "B-0147 has moved since you scanned it: it is now in Finishing. Scan
    it again to move it on."
  - Phases changed meanwhile: "The phases have changed since you scanned. B-0147 now goes to Quality check." The button relabels.
  - Product deleted: "Denim Jacket was deleted, so B-0148 can't be put in stock." Moves between phases still work.
  - Camera refused: "Invory can't use the camera. Allow the camera for Invory in your browser's settings, or type the batch number."
  - Couldn't move: "Couldn't move B-0147. Check the connection and try again." · **Try again**. Couldn't load: "Couldn't load ticket
    B-0147. Try again." · **Try again**.

- [ ] **AC-7 · Ticket size in Settings › Labels (waits for INV-9).** Settings › Labels, the page INV-9 makes, gets the list **Ticket
  size**, beside the label size: "A4 sheet, 8 tickets (2 × 4)" (the default) or "100 × 60 mm, one per label (label printer)". It is
  saved with **Save label settings** like the other label settings, for the whole workshop. Every ticket printed after that uses the
  chosen size. This criterion is built once INV-9's page exists; until then tickets print on the A4 sheet of 8.
