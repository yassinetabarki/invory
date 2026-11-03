---
id: INV-15
title: Work tickets and the Scan page
type: feature
phase: Colour × size variants
priority: Must
size: M
status: Planned
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

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 7 commits (6 now, 1 after INV-9)
> Tech-lead (main session standing in, at the human's request) · 2026-11-02 · approved 2026-11-02
> Why complex: a private ticket code on every batch (and on every split), a page reachable from a phone's camera through sign-in, a scan
> that moves a batch or puts it in stock through INV-5's two CRITICAL Actions under their locks, a print page that sets the pattern
> INV-9's labels will follow, and a camera reader (`barcode-detector`, the human's answer).

### In short
Every batch gets a **ticket code** (32 random letters and digits, unique), made when the batch is made, including when a split makes
one. The QR code on the ticket is the link `/scan/t/<code>`, drawn on the server from `bacon/bacon-qr-code` (already installed through
Fortify) as one SVG path, so the page never injects HTML. Tickets print from a **print page** of their own (`progress/tickets`), opened
in a new tab, which calls the browser's print window once it is drawn. The **Scan** page reads a code, a typed number or a scanned link,
asks the server what the batch is and what its next step is, and shows one confirm. The confirm posts what it showed (from, to,
units); a new Action **ScanBatch** locks the batch, refuses if anything changed, and then calls **MoveBatch** or **PutBatchInStock** for
all the batch's units in the same transaction, marking the move as made by a scan. "Your scans today" reads those marked moves. Every
sentence comes from the server; the page formats dates and counts only.

### Steps

| Step         | What it does                                                   | Proves          | Lane         | Who      | Size       |
| ------------ | -------------------------------------------------------------- | --------------- | ------------ | -------- | ---------- |
| **INV-15-a** | A ticket code on every batch, the scan mark on moves, ADR      | AC-5            | CRITICAL     | Backend  | ~200 lines |
| **INV-15-b** | The tickets' data and their print route, the QR path           | AC-1, AC-2      | NON-CRITICAL | Backend  | ~300 lines |
| **INV-15-c** | Scan: find a batch, the confirm, ScanBatch, scans today        | AC-3–AC-6       | CRITICAL     | Backend  | ~450 lines |
| **INV-15-d** | The print page and the print buttons on Progress               | AC-1, AC-2      | NON-CRITICAL | Frontend | ~350 lines |
| **INV-15-e** | The Scan page: field, confirm, refusals, scans today, sidebar  | AC-3, AC-4, AC-6 | CRITICAL    | Frontend | ~450 lines |
| **INV-15-f** | The camera on the Scan page                                    | AC-3            | NON-CRITICAL | Frontend | ~200 lines |
| **INV-15-g** | Ticket size in Settings › Labels (**after INV-9**)             | AC-7            | NON-CRITICAL | Both     | ~150 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
run their PostgreSQL tests; after INV-15-f the whole tree runs `composer test:pgsql`.

#### INV-15-a · A ticket code on every batch, the scan mark on moves, ADR
**Commit:** `feat(production): a secret ticket code on every batch (INV-15-a)`

- Migration: `production_batches.ticket_code` (string 32), filled for the batches already there, then unique; on PostgreSQL also
  `NOT NULL`. `production_batch_moves.scanned` (boolean, default false) and an index (`workshop_id`, `user_id`, `created_at`) for
  "Your scans today".
- `ProductionBatch`: a `creating` hook sets `ticket_code` (`Str::random(32)`, retried on the rare clash) when it is empty, so Start
  batch, factories and a split all get one. `MoveBatch`'s `replicate([...])` adds `ticket_code` to what it leaves out, so a split
  batch never shares its parent's code. `ticket_code` is hidden from every Resource; only the QR link carries it.
- The tech-lead writes in this commit: ADR-0024 "Work tickets and scanning" (the code and why it is not the id or the number, the
  link, the scan mark, the print page as the pattern for INV-9), `docs/ARCHITECTURE.md` (the two columns, the routes).

**Tests**
- Each new batch has a 32-character code; two batches never share one; a split gets its own code, its parent keeps its code; the
  migration fills the batches already there. PostgreSQL: a duplicate code and a null code are refused.

> [!example]- Files (≈6)
> - `database/migrations/2026_11_05_100000_add_ticket_codes_to_production_batches.php` (new)
> - `app/Models/{ProductionBatch,ProductionBatchMove}.php`, `app/Actions/Production/MoveBatch.php`
> - `docs/adr/0024-work-tickets-and-scanning.md` (new), `docs/ARCHITECTURE.md`
> - `tests/Feature/Production/TicketCodeTest.php` (new)

#### INV-15-b · The tickets' data and their print route, the QR path
**Commit:** `feat(production): the work tickets' print page data and QR codes (INV-15-b)`

- `composer.json` lists `bacon/bacon-qr-code` as a direct requirement (the version already in `composer.lock`, no new package).
- `App\Services\Production\TicketQr`: the link `route('scan.ticket', code)` encoded with bacon's `Encoder` (error correction M),
  returned as `{ size, path }`: the module count and one SVG path `d` ("M3 3h1v1h-1z…") built from the matrix. Pure, no HTML.
- `App\Services\Production\TicketSheet`: for the batches asked for (in production only, in the order asked), each ticket's number,
  product name, "Indigo · S" (or "Camel" for a one-size product), units, started day, the variant SKU (`VariantSku`), "Split from
  B-0149" when split, the path names and the index of the current step, and the QR. Printed day = today. Queries flat for 50 tickets.
- `ProductionTicketController@index`: `GET progress/tickets?batches[]=12&batches[]=13` (1 to 50 ids, each a batch of the workshop
  through `WorkshopRule`; another workshop's id is a 404) → Inertia page `progress/tickets` with `tickets` and `size` (`a4_8` until
  INV-15-g).
- The flash `batches_started` gains each batch's `id`. The split toast gains the action **Print ticket B-0155** (`href` the tickets
  route for the new batch, `new_tab: true`).

**Tests**
- B-0147's ticket fields of AC-1 (Sewing ticked, "Printed 30 Oct 2026", SKU); a split batch's "Split from B-0149"; a one-size
  product; 3 units after 1 of 4 is put in stock; no client, phone or price key anywhere in the props; an undone or finished batch is
  left out; 51 ids refused; another workshop's batch a 404. `TicketQr`: the path decodes back to the link (a small decoder in the test
  reads the matrix), the same code gives the same path. The split toast's action and the flash ids.

> [!example]- Files (≈9)
> - `composer.json`, `composer.lock` (the require line only)
> - `app/Services/Production/{TicketQr,TicketSheet}.php` (new), `app/Http/Controllers/ProductionTicketController.php` (new),
>   `app/Http/Requests/Production/PrintTicketsRequest.php` (new)
> - `app/Actions/Production/StartBatch.php`, `app/Http/Controllers/ProductionBatchMoveController.php`, `routes/web.php`,
>   `resources/js/types/{domain,ui}.ts`, `resources/js/pages/progress/tickets.tsx` (stub)
> - `tests/Feature/Production/{TicketSheet,TicketQr}Test.php` (new)

#### INV-15-c · Scan: find a batch, the confirm, ScanBatch, scans today
**Commit:** `feat(production): scan a ticket to move its batch on or put it in stock (INV-15-c)`

- `App\Services\Production\ScanLookup`: reads what was typed or scanned, trimmed: a ticket link (anything ending in `/scan/t/<code>`,
  from any host) → the code; `b-0147`, `B-0147`, `B147`, `0147` or `147` (any capitals, the dash and zeros optional, 1 to 9 digits) → the
  number; anything else → "A batch number looks like B-0142. Check what you typed.". It then finds the batch in the workshop (the
  global scope does it: another workshop's code is simply not found) and returns either a **confirm** or a **refusal**:
  - confirm: batch id, number, units, product, "Indigo · S", started day, the path with the current and next steps, `to` (the next
    phase id, or `stock` when it is the last step), the shelf now and after for a Put in stock, and the warning "B-0147 moved to
    Finishing 12 seconds ago (Admin). Scanning again moves it on to Put in stock." when the batch's last forward or back move is less
    than 2 minutes old;
  - refusals: put in stock ("… was put in stock on 28 Oct 2026. Nothing left to do."), undone ("… was undone on 30 Oct 2026: its
    materials went back to stock. Nothing to move."), unknown code ("This code isn't a work ticket of your workshop. Check the ticket,
    or type its number."), unknown number ("No batch B-0999 in your workshop. Check the number on the ticket."), a deleted product at
    the last step ("Denim Jacket was deleted, so B-0148 can't be put in stock.").
- `App\Actions\Production\ScanBatch`: takes the batch and what the confirm showed (`from`, `to`, `units`). In one transaction: the
  production lock, the batch locked; refuses with "B-0147 has moved since you scanned it: it is now in Finishing. Scan it again to move
  it on." (`moved`) when it left `from` or holds other units, and with "The phases have changed since you scanned. B-0147 now goes to
  Quality check." (`phases`, with the fresh confirm in the error bag's companion flash) when its next step is no longer `to`. Then it
  calls `MoveBatch` (to a phase) or `PutBatchInStock` (`stock`) for all its units with `scanned: true`. Both Actions gain that last
  parameter (default false) and write it on their move; nothing else in them changes.
- `ScanController`: `GET scan` (page `scan/index`: `scans_today`, `confirm` null, `refusal` null) · `GET scan/t/{code}` (the same page
  with that ticket's confirm or refusal; inside `auth`, so a guest goes to sign in and comes back here through Laravel's intended
  URL) · `GET scan/lookup?q=` (JSON, ADR-0009 rule 5: `{ confirm }` or `{ refusal }`) · `POST scan/batches/{batch}` → redirect to
  `scan.index` (never back to `scan/t/…`, which would show the next confirm at once) with the toast "4 units of Denim Jacket · Indigo S
  moved to Finishing." or "… put in stock.".
- "Your scans today": this member's moves with `scanned = true` since today 00:00, newest first, at most 50: number, product,
  `to_label`, the time (ISO; the page shows it as a time).
- `App\Services\Production\ScanMessages`: every sentence of the Scan page.

**Tests**
- Each way of typing B-0147, a link from another host, a bad entry, an unknown number, another workshop's code and number (the same
  refusal as unknown, nothing about the other workshop in the response), finished and undone batches with their days.
- AC-4: B-0147 Sewing → Finishing (number kept, since today, `scanned` on the move), B-0150 → Sewing, B-0148 put in stock (shelf 0 →
  4, the restock with "B-0148" and "From production"), no own phase: In cutting → put in stock. Refused, nothing written: moved
  meanwhile, units changed (part put in stock elsewhere), a phase added after it, the product deleted. The 2-minute warning at 1:59 and
  not at 2:01 (frozen time). Scans today: only this member, only scans, only today. A guest on `scan/t/<code>` → login → back on it.

> [!example]- Files (≈10)
> - `app/Services/Production/{ScanLookup,ScanMessages}.php` (new), `app/Actions/Production/ScanBatch.php` (new),
>   `app/Actions/Production/{MoveBatch,PutBatchInStock}.php`
> - `app/Http/Controllers/{ScanController,ScanLookupController,ScanBatchController}.php` (new),
>   `app/Http/Requests/Production/ScanBatchRequest.php` (new), `routes/web.php`, `resources/js/types/domain.ts`,
>   `resources/js/pages/scan/index.tsx` (stub)
> - `tests/Feature/Production/{ScanLookup,ScanBatch,ScanPage}Test.php` (new)

#### INV-15-d · The print page and the print buttons on Progress
**Commit:** `feat(production): print work tickets from Progress (INV-15-d)`

- Page `progress/tickets` with no app frame: the tickets at 105 × 74 mm, 2 × 4 on an A4 sheet (`@page { size: A4; margin: 0 }`),
  black on white, the QR as `<svg viewBox="0 0 size size"><path d=…/></svg>` at 30 mm, the fields of AC-1; it calls `window.print()`
  once drawn. On screen it shows the sheet with a **Print** button for a second try.
- `resources/js/lib/print-tickets.ts`: opens the tickets route in a new tab from the click itself; when the browser returns no tab it
  shows "Your browser blocked the tickets' tab. Allow pop-ups for Invory, then print again." (an error toast; the button stays).
- Start batch's result: "2 tickets to print: B-0153 · Indigo M · 6 units, B-0154 · Indigo L · 10 units", **Print 2 tickets** (main)
  or **Print ticket** for one, then **Done**. The "⋯" menu: **Print ticket B-0147** last in every phase. `use-flash-toast`: an action
  with `new_tab` uses `print-tickets` instead of a visit.

**Tests**
- Vitest: the sheet for B-0147 (each line of AC-1, Split from, one size), the Start batch result with one and two batches, the menu
  item in In cutting, Sewing and the last phase, the toast action, the blocked tab message.

> [!example]- Files (≈8)
> - `resources/js/pages/progress/tickets.tsx` (+ test), `resources/js/lib/print-tickets.ts` (+ test)
> - `resources/js/components/production/{start-batch-dialog,phase-view}.tsx` (+ tests), `resources/js/hooks/use-flash-toast.ts` (+ test)

#### INV-15-e · The Scan page: field, confirm, refusals, scans today, sidebar
**Commit:** `feat(production): the Scan page and its confirm (INV-15-e)`

- Sidebar: **Scan** (`ScanQrCode` icon) after Progress. Progress's header: **Scan a ticket** (outline) between Production phases and
  Start batch; on a phone beside **Phases**.
- Page `scan/index`: the field "Scan or type a batch number" with the focus and the hint; **Find ticket**; Enter (what a scanner sends)
  finds too. It calls `scan.lookup` and shows the confirm (a dialog; a sheet from the bottom on a phone): title, the batch line, the
  path with "(now)", the warning line when given, **Move 4 units to Finishing** / **Put 4 units in stock** (56 px, full width) and
  **Not now**; or the refusal with **Scan another**. Posting shows a spinner and keeps the label; `moved` shows its sentence and **Scan
  another**; `phases` relabels the button with the fresh confirm. After a move the field is empty and focused again. The page opened
  from `scan/t/<code>` opens with its confirm. "Your scans today" and "No scan yet today.". "Couldn't move B-0147. Check the connection
  and try again." / "Couldn't load ticket B-0147. Try again." with **Try again**. Nothing scrolls sideways at 320 px; 44 px controls.

**Tests**
- Vitest: typing and Enter, each confirm (move, put in stock with the shelf note, the warning), each refusal, moved meanwhile, phases
  changed, the network errors, scans today and its empty line, the confirm on load from a ticket link, the sidebar order, the header
  button.

> [!example]- Files (≈8)
> - `resources/js/pages/scan/index.tsx` (+ test), `resources/js/components/scan/{scan-confirm,scans-today}.tsx` (new, + tests)
> - `resources/js/components/app-sidebar.tsx` (+ test), `resources/js/pages/progress/index.tsx`

#### INV-15-f · The camera on the Scan page
**Commit:** `feat(production): read a ticket's QR code with the camera on the Scan page (INV-15-f)`

- On a device with a camera, the Scan page shows the camera view with a frame and "Point the camera at the QR code on a work ticket.",
  and "Or type the batch number" above the field. A QR read goes through the same lookup as the field. The camera stops while a
  confirm is open and when the page is left. Refused camera: "Invory can't use the camera. Allow the camera for Invory in your
  browser's settings, or type the batch number."; no camera: the field only. The QR is read with `barcode-detector` (the browser's own
  `BarcodeDetector` when it has one, its WebAssembly reader otherwise, the `.wasm` file bundled by Vite and served by Invory, never
  from a CDN); it is loaded only on the Scan page, so no other page grows.

**Tests**
- Vitest with the reader mocked: a read opens the confirm, the camera pauses during the confirm, refused and missing camera.

> [!example]- Files (≈4)
> - `resources/js/components/scan/camera-reader.tsx` (new, + test), `resources/js/pages/scan/index.tsx`, `package.json`, `package-lock.json` (`barcode-detector`)

#### INV-15-g · Ticket size in Settings › Labels (after INV-9)
**Commit:** `feat(production): the ticket size in Settings › Labels (INV-15-g)`

- Built after INV-9, on the label settings it stores: a `ticket_size` (`a4_8` default · `label_100x60`) saved with **Save label
  settings**; the print route sends it to the page; the print page lays out 100 × 60 mm, one per page (`@page { size: 100mm 60mm }`). Planned in
  full when INV-9's storage exists; until then AC-7 stays open and INV-15 is accepted on AC-1 to AC-6.

### Contract (frozen before dispatch)
```ts
export type TicketQr = { size: number; path: string };                           // SVG path of the modules, viewBox 0 0 size size
export type WorkTicket = { id: number; number: string; product: string; variant: string; units: number; started_on: string;
    sku: string | null; split_from: string | null; path: string[]; current: number; printed_on: string; qr: TicketQr };
export type TicketSize = 'a4_8' | 'label_100x60';
export type PrintTicketsProps = { tickets: WorkTicket[]; size: TicketSize };
export type ScanConfirm = { batch_id: number; number: string; units: number; product: string; variant: string; started_on: string;
    path: { name: string; state: 'done' | 'now' | 'next' | 'later' }[]; from: PhaseRef; to: PhaseRef | 'stock';
    to_name: string; shelf: { now: number; after: number } | null; warning: string | null };   // shelf only when to = 'stock'
export type ScanLookupResult = { confirm: ScanConfirm; refusal: null } | { confirm: null; refusal: string };
export type ScanToday = { number: string; product: string; to: string; at: string };          // 'Finishing' | 'Put in stock'; ISO time
export type ScanIndexProps = { scans_today: ScanToday[]; confirm: ScanConfirm | null; refusal: string | null };
export type ScanBatchFormData = { from: PhaseRef; to: PhaseRef | 'stock'; units: number };
// Changed
BatchesStarted.batches[] += { id: number };
FlashToast.action += { new_tab?: boolean };                                     // opens the tickets in a new tab (types/ui.ts)
```
Errors: `batches` (print) · `moved`, `phases` (scan; `phases` comes with the flash `scan_confirm`: the fresh `ScanConfirm`). Routes:
`progress.tickets` GET progress/tickets · `scan.index` GET scan · `scan.ticket` GET scan/t/{code} (`[A-Za-z0-9]{32}`) · `scan.lookup`
GET scan/lookup (JSON) · `scan.batches.store` POST scan/batches/{batch} (digits).

### Decisions
- **A random code, not the id or the number, in the QR.** The number is typed by people and can be guessed; the code can't, so a photo
  of a ticket names nothing else. Inside the workshop both find the same batch; outside it, neither finds anything.
- **The QR is drawn on the server** with the library Fortify already uses for two-factor codes, sent as a path, not as HTML. Rejected: a
  QR package in the browser (a new dependency for something the server has).
- **The scan reuses INV-5's Actions.** ScanBatch only checks that nothing changed since the confirm and then calls MoveBatch or
  PutBatchInStock for all the units, so the locks, the split rule, the restock "From production" and the 1,000,000 limit have one
  writer. The `scanned` mark is the only thing those Actions learn.
- **After a scan the page goes to `/scan`, never back to the ticket link**, so the next step is never offered without a new scan.
- **The print page is an Inertia page with no app frame** that prints itself; INV-9's labels can follow the same pattern.
- **Wording the card doesn't give, written here:** the print page's **Print** button (for a second try) and, when every batch asked
  for has left Progress, "These batches have left Progress, so there is nothing to print.".
- **Times:** "today" and "since today 00:00" use the app's time zone (UTC), as INV-5's "since" does.

### Order
Wave 1 (backend, one after another): INV-15-a → INV-15-b → INV-15-c. Wave 2 (frontend, after wave 1's types land): INV-15-d in
parallel with INV-15-e → INV-15-f. INV-15-g after INV-9. Merge order is the step order.

### Risks
- **A split copying its parent's code** → `ticket_code` left out of the replicate and a test on it; unique in the database.
- **The QR link on this machine is `http://localhost:8000/…`**, which a phone can't open → the link follows `APP_URL`; to try it with a
  phone, set `APP_URL` to the laptop's address on the Wi-Fi. Production is unaffected.
- **A pop-up blocker on the print tab** → the tab is opened in the click itself; the blocked message otherwise.
- **iPhone Safari has no built-in QR reader for web pages** → `barcode-detector`'s WebAssembly reader; the phone's own camera app
  always works too (AC-5).
- **Browser tests can't run on this machine** → Vitest and feature tests carry the proof, as in INV-5.

### Answers (human, 2026-11-02)
1. The camera reads QR codes with the `barcode-detector` package (a): the browser's own reader where there is one, a WebAssembly
   reader served by Invory elsewhere, loaded only on the Scan page.
2. `bacon/bacon-qr-code` becomes a direct dependency (yes).
