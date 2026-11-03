# ADR-0024 — Work tickets and scanning

- **Status:** Accepted · 2026-11-02 (approved by the human with the Technical work of INV-15; tech-lead role held by the main session)
- **Builds on:** ADR-0023 (production batches, the production lock, a stale page is refused), ADR-0013 (every record belongs to a
  workshop; another workshop's record is never found), ADR-0009 rule 5 (small session-authenticated JSON routes)

## Context
INV-15 gives every production batch a printed **work ticket** with a QR code, and adds a **Scan** page: a phone camera, a USB or
Bluetooth scanner, or a typed number finds the batch and offers one confirm that moves it to its next phase, or puts it in stock at
the last one. The ticket lies on the workshop floor, so it names no client, phone number or price. A phone's own camera app reading
the QR code must open the same confirm, through sign-in when needed.

## Decision
1. **A random ticket code per batch, not its id or number.** `production_batches.ticket_code` holds 32 random letters and digits
   (`Str::random`), unique, made in the model's `creating` hook, so Start batch, a split and the factories all get one. A split is a
   replicate of its parent, so `MoveBatch` leaves the code out of it. The code is hidden from every array and Resource; only the QR
   link carries it. The number (B-0147) is for people and can be guessed; the code can't, so a photo of a ticket names nothing else.
   Inside the workshop both find the same batch; the workshop scope means another workshop's code or number is simply not found, and
   the page says "This code isn't a work ticket of your workshop." without revealing that the other workshop exists.
2. **The QR code is the link `/scan/t/<code>`**, built with `route()` (so it follows `APP_URL`), and drawn on the server with
   `bacon/bacon-qr-code` (already installed for Fortify's two-factor codes; now a direct requirement). `TicketQr` turns the module
   matrix into one SVG path, so the page draws `<path d=…>` and never injects HTML. Rejected: a QR package in the browser.
3. **`/scan/t/<code>` sits behind `auth`**, so a guest signs in and Laravel's intended URL brings them straight back to the confirm.
4. **A scan reuses INV-5's Actions.** `ScanBatch` takes the production lock and the batch, refuses when anything differs from what the
   confirm showed (the batch's phase or units: "B-0147 has moved since you scanned it…"; its next step: "The phases have changed since
   you scanned…", with a fresh confirm), then calls `MoveBatch` or `PutBatchInStock` for all its units. Those two Actions only learn
   a `scanned` flag, written on their move (`production_batch_moves.scanned`), which "Your scans today" reads. There is no moving back
   and no part of a batch by scan.
5. **After a scan the page goes to `/scan`, never back to the ticket link**, so the next step is never offered without a new scan.
6. **Tickets print from a page of their own** (`progress/tickets`, an Inertia page with no app frame, opened in a new tab from the
   click itself) that calls the browser's print window once drawn. Labels (INV-9) can follow the same pattern.
7. **The ticket size is a workshop label setting** in `workshop_label_settings` (one row per workshop, made on first save):
   `ticket_size` `a4_8` (default) or `label_100x60`. INV-15 makes the table and Settings › Labels with Ticket size only; INV-9 adds
   the label size and the selling price to the same row and page.
8. **The camera reads QR codes with `barcode-detector`**: the browser's own `BarcodeDetector` where there is one, its WebAssembly
   reader otherwise, the `.wasm` file bundled and served by Invory (never a CDN), loaded only on the Scan page.

## Consequences
- `production_batches.ticket_code` is unique on both engines and `NOT NULL` on PostgreSQL; the migration gives the batches made before
  INV-15 a code.
- On a laptop in development the QR link starts with `http://localhost:8000`, which a phone can't open; set `APP_URL` to the laptop's
  address on the Wi-Fi to try it with a phone.
- A typed entry is read on the server only (`ScanLookup`): `B-0147`, `b-0147`, `B147`, `147` and a scanned link all work.
