# Invory — Product Backlog

> [!info] Quick Links & Ownership
> **Owner:** `product-owner` (order, wording, acceptance criteria) · **Technical Detail:** `tech-lead` ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> **Phases & Goals:** [ROADMAP.md](ROADMAP.md) · **Progress:** [STATUS.md](STATUS.md)

> [!history] Last Cleaned: 2026-11-06
> - **INV-14 Done** (accepted 2026-11-03; on `PI-DEV`: `7af1a63`; card `432731b`, Technical work `753c611`). Orders are served from the shelf: tracker, Mark delivered, Cancel units; orders no longer cut. It left the open list. **Next (D-26): INV-3's acceptance, then INV-9.**
> - **INV-13 Done** (accepted 2026-11-03 by the human; on `PI-DEV`: `39647ce`; card `f07ef4e`, Technical work `7bf05bb`). Order lines hold colour and size; Search products shows the shelf stock (no "Can produce now", the human's answer). It left the open list. **INV-14 is next.**
> - **D-26 (2026-11-03, human):** `INV-13` then `INV-14` come next, ahead of INV-3's acceptance and `INV-9`. INV-13's development card is approved (`Ready`, 2026-11-03) and its Technical work too (`Planned`, 2026-11-03: 8 steps, INV-13-a … INV-13-h; "Can produce now" left out of the search and the order, the human's answer).
> - **INV-15 Done** (accepted 2026-11-03 by the human; on `PI-DEV`: `967dcf4`, `d876d37`, `7af351d`, `adea45b`; card `a523494`, Technical work `ae2716d`). Its Ticket size was built with it, so `Settings › Labels` exists with Ticket size only; INV-9 adds its label settings there. It left the open list.
> - **INV-5 Done** (accepted 2026-11-02 by the human, who ran the checks himself; on `PI-DEV`: `9672c68`, `9c1b08e`, `6ae913a`, `788c3d2`, `351267e`, `3ac5d2e`, `a6c121d`; card `12496f7`, Technical work `d993ce2`, delivered `41ea604`). It left the open list.
> - **D-25 (2026-11-02, human):** `INV-15` comes next, ahead of INV-3's acceptance and `INV-9`: its development card is written now. Its **Ticket size** part needs INV-9's `Settings › Labels` and waits for it.
> - **INV-4 Done, with INV-12** (accepted 2026-11-02 by the human; on `PI-DEV`: `4ed7e4d`, `0e6fcec`, `f4f9fd8`, `ab5bb22`, `2448e70`, `d8b2ccb`, `a2c2a05`, `5966a39`, `7102486`, `d2bf98f`; card `ccd4fe4`, Technical work `7528910`, delivered `64f463c`). INV-12's New order Client card was built within INV-4 (D-22), so INV-12 is done too. Both left the open list.
> - **D-24 (2026-11-02, human):** `INV-5` comes next, ahead of INV-3's acceptance and `INV-9`, on `PI-DEV`. INV-5 is split in two sharing its UI card: **INV-5** (Progress, Start batch, moves, Put in stock, Undo batch, Production phases; size L, development card `Ready`) and **INV-15** (work tickets, Ticket size, Scan page; size M). The order page keeps its cutting buttons until INV-14.
> - **D-23 (2026-11-02, human):** `INV-3` comes next, ahead of `INV-9`. INV-3's development card is approved (`Ready`, 2026-11-02; size L, kept as one card by the human's choice) and its Technical work too (`Planned`, 2026-11-02: 11 steps, INV-3-a … INV-3-k).
> - **D-22 (2026-11-02, human):** `INV-4` comes next, ahead of `INV-9` and `INV-3`. New order uses `INV-12`'s phone-first Client card instead of INV-4's switch, so `INV-12` is built within INV-4 and leaves the open list. INV-4's development card is approved (`Ready`, 2026-11-02) and its Technical work too (`Planned`, 2026-11-02: 9 steps, INV-4-a … INV-4-i).
> - **INV-8 Done** (accepted 2026-11-01; `9f93175`, `28003cb`, `63513ef`, `4678cad`, `5704050`, `36b34a3`, `3b55319`; docs `da41e1b`), so it left the open list. INV-9 is next.
> - **INV-2 Done** (accepted 2026-10-31; `46af1cc`, `ab313eb`, `4088200`), so it left the open list. The PostgreSQL parity suite was green before the commits (2619 tests). INV-8 is next.
> - **INV-10 done** (`76b1770`; its card `1f854bf`), so it left the open list.
> - **INV-2 Split:** INV-2's card was split into three: **INV-2** (stock, done), **INV-8** (barcodes), and **INV-9** (labels).
> - **Added 2026-11-01:** `INV-12` (Refactor the order), placed after INV-5 and before INV-7. **Split the same day into `INV-12`, `INV-13` and `INV-14`, each with a UI card.** `INV-12`, `INV-13` and `INV-14` are `UI approved` (2026-11-01); `INV-5`'s UI card, reopened because finished products are not tied to an order, was rewritten and is `UI approved` again (2026-11-01). `INV-13`'s card got a one-line wording fix (re-approved 2026-11-01). They wait their turn for building.
> - **Parked idea:** "Barcodes tab polish" (from the INV-8 UX review), planned with INV-9; no ID yet.
> - **INV-3 Done** (accepted 2026-11-06 by the human, who confirmed it after merging `PI-DEV` to `main`; `2575dd0` … `cbdae79` =
>   INV-3-a … INV-3-k, docs `194534a`, `c8c4716`, browser golden path `aeee262`). Restock of finished products: the Restocks page,
>   New restock (colour × size grid, scan or type a barcode), the restock detail and Undo restock; "Restock" on the product page's
>   Sizes & colours card. It left the open list. **INV-9 is next.**
> - **Current Order** (D-27, D-28, 2026-11-06: the human prioritizes `INV-16` now, ahead of the rest, and puts `INV-17` right after it):
>   `INV-16` → `INV-17` → `INV-9` → `INV-7`. Next free ID: **INV-18** (INV-11 is the tech-debt item raised in the INV-8 review, see
>   [TECH-DEBT.md](TECH-DEBT.md); INV-15 is the real, done "Work tickets and the Scan page").
> - **Added 2026-11-06:** `INV-17` (Per-unit QR codes) — a new idea from the human: keep batch progress and add progress per unit, with
>   a QR code per unit; a scan moves only that unit. Both modes stay, the user chooses. An `Idea`: the interaction model is still to be
>   decided (see Item Details), then a UI card.
> - **Added 2026-11-06:** `INV-16` (Material use per size and colour) — a new idea from the human: how much material a size uses
>   may differ (e.g. an L jacket uses more fabric than an M), and "Can produce" should show per colour. This contradicts today's
>   rule that every size and colour of a product shares one composition (see "Colours" and "Sizes" in the costing domain), so it
>   needs the human's decisions and an ADR before any card is drafted. (First logged as "INV-15" on 2026-11-06, before the `PI-DEV`
>   merge revealed that number is taken by the done "Work tickets and Scan" item; renumbered to **INV-16**.) **Decided by the human,
>   2026-11-06:** quantity varies by **both size and colour** (a full grid, not one override shared across colours); the input is a
>   **flat quantity per size/colour combination**, not a multiplier on today's quantity; "per colour" capacity means **colour-specific
>   material stock** (e.g. "Denim 12oz Indigo" would be its own stocked material with its own quantity on hand, not one shared stock
>   sliced by colour).
> - **D-27 (2026-11-06, human):** `INV-16` moves to the front of the queue, ahead of `INV-9` and `INV-7`, in place of being appended
>   after them. The ADR and the tech-lead's agreement on the costing rules are still needed before any UI or development card is drafted.
> - **INV-16 UI approved** (2026-11-06, by the human, after ADR-0027 settled the costing rules): the composition line's
>   "Same for every variant" / "By colour and size" toggle and colour × size grid (a Sheet, pre-filled from the old quantity),
>   capacity shown as guaranteed (headline) and best (secondary, named by colour), cost and margin as ranges. Card
>   [stories/INV-16-material-per-variant-ui.md](stories/INV-16-material-per-variant-ui.md). Its development card is written
>   ([stories/INV-16-material-per-variant.md](stories/INV-16-material-per-variant.md), `Ready`, approved by the human 2026-11-06).
> - **INV-1 Done:** "Colours as fixed values" accepted & merged (`a66f4bf`, `78a4eee`), so it left this backlog.
> - **Added:** `INV-6` (picker-row item) and `INV-7` (Could: bigger tap targets on Settings).
> - **Archive:** Done items leave this file and are recorded in `STATUS.md` and the archive.

---

## 📖 How to Read an Item

### Status Lifecycle
`Idea` ➔ `Draft` *(writing card)* ➔ `Ready` *(approved card)* ➔ `Planned` *(approved tech plan)* ➔ `In progress` ➔ `In review` *(tech-lead gates)* ➔ `Acceptance` *(PO check + click-through)* ➔ `Done` *(committed)*

> [!note] On Hold Items
> A paused item reads: ``On hold` since <date> (was `<status>`)`. Its card's status is set to `On hold`.

### Key Metadata
- **Priority (MoSCoW):** `Must` · `Should` · `Could` · `Won't` *(this time)*
- **Size:** `S` ($\le 1$ day agent work) · `M` · `L` *(needs split)*
- **Card:** Full story cards (story, AC with examples, visual scope, out of scope, questions) live in `docs/stories/<id>-<slug>.md`. This backlog displays the short version.
- **IDs & History:**
  - Everything new since **2026-10-24 (D-20, D-21)** is numbered **INV-n** (including technical items without UI cards). Next free ID: **INV-18**.
  - `INV-17`: Per-unit QR codes, moving units one by one besides whole batches. Added 2026-11-06 (D-28), placed after `INV-16` and before `INV-9`. An `Idea` (see Item Details).
  - `INV-15`: Work tickets and Scan, split from INV-5 on 2026-11-02 (D-24); shares INV-5's UI card (done 2026-11-03).
  - `INV-1` to `INV-5`: Variant rework items (`INV-1`, `INV-2`, `INV-3`, `INV-4` and `INV-5` are all done).
  - `INV-6`: Picker-row swatch edge item.
  - `INV-7`: Settings tap targets.
  - `INV-2`: Stock per colour and size (done, `46af1cc`, `ab313eb`, `4088200`).
  - `INV-3`: Restock of finished products (done 2026-11-06, `2575dd0` … `cbdae79`, `aeee262`).
  - `INV-8` & `INV-9`: Barcodes (done, `9f93175` … `3b55319`) and labels (split from INV-2 on 2026-10-31).
  - `INV-10`: PostgreSQL test fix (done, `76b1770`).
  - `INV-11`: Small barcode clean-ups from the INV-8 review (MINOR tech debt, in [TECH-DEBT.md](TECH-DEBT.md); proposed for when barcodes are next touched, INV-9).
  - `INV-12`, `INV-13`, `INV-14`: Refactor the order (client first · product search with stock · order tracker and no cutting on orders). Added 2026-11-01 as `INV-12`, split into three the same day.
  - `INV-16`: Material use per size and colour, and "Can produce" per colour. Added 2026-11-06 (first as "INV-15", renumbered once the `PI-DEV` merge showed that number taken). Moved to the front of the queue by the human (D-27, 2026-11-06); UI card `Approved`, development card `Ready` (2026-11-06) (see Item Details).
  - Old `S-` and `F-` IDs are retained and never reused (last story ID: `S-74`).
  - Technical items register: See [TECH-DEBT.md](TECH-DEBT.md).

---

## ⚡ Active Focus — Colour × Size Variants (D-20, D-21)

> [!warning] Card Flow Paused (2026-10-24)
> Every `S-` item below is **On hold**. Nothing is dropped: when work resumes, each item will be checked against the new variant model before returning to its prior status.

### Execution Plan & Order
1. **INV-1** (Colours) — `#done` *(Accepted 2026-10-31)*
2. **INV-2** (Stock per colour and size) — `#done` *(Accepted 2026-10-31)*
3. **INV-8** (Barcodes) — `#done` *(Accepted 2026-11-01)*
4. **INV-4** (Clients, with INV-12's Client card) — `#done` *(Accepted 2026-11-02)*
5. **INV-5** (Production for the shelf: Progress) — `#done` *(Accepted 2026-11-02)*
6. **INV-15** (Work tickets and the Scan page) — `#done` *(Accepted 2026-11-03)*
7. **INV-13** (Search products, order lines per colour and size) — `#done` *(Accepted 2026-11-03)*
8. **INV-14** (Order tracker, served from the shelf) — `#done` *(Accepted 2026-11-03)*
9. **INV-3** (Restock of finished products) — `#done` *(Accepted 2026-11-06)*
10. **Next Sequence (D-27, D-28):** `INV-16` ➔ `INV-17` ➔ `INV-9` ➔ `INV-7`
8. **Card Structure:** Each feature receives a UI card first (`INV-n-<slug>-ui.md`), followed by a development card (`INV-n-<slug>.md`).
9. **Shared UI Cards:** `INV-2`, `INV-8`, and `INV-9` share the same UI card but are accepted individually; so do `INV-5` and `INV-15`.
10. **Phase 7 Tech Allowance:** `F-43` · `F-46` · `F-52` · `F-53`

### Open Backlog Table

| ID         | What it brings                                               | Type    | Status        | Priority / Size    |
| ---------- | ------------------------------------------------------------ | ------- | ------------- | ------------------ |
| **INV-16** | Material use per size and colour, "Can produce" per colour   | Feature | `Ready`       | Must / L           |
| **INV-17** | Per-unit QR codes: move units one by one, besides batches    | Feature | `Idea`        | Should / L (split) |
| **INV-9**  | Print barcode labels                                         | Feature | `Ready`       | Must / M           |
| **INV-7**  | Bigger tap targets on Settings pages                         | Feature | `Idea`        | Could / S–M        |

> [!note] Technical Note on INV-6
> **INV-6** (picker-row swatch edge) joins a phase's tech allowance when proposed by the tech lead. It does not block the feature sequence above.

---

### Item Details

#### `INV-9` · Print barcode labels
- **Type:** Feature | **Priority:** Must | **Size:** M | **Status:** `Ready`
- **Cards:** [Development Card](stories/INV-9-labels.md) · [UI Card](stories/INV-2-product-sizes-colours-barcodes-ui.md) *(shared)*
- **Details:** 
  - Adds "Print label" & "Print labels · Indigo" to Barcodes tab.
  - Supports $50 \times 30\text{ mm}$ labels or A4 sheets of 24.
  - Adds `Settings › Labels`. Runs after `INV-8`.
  - *Since INV-15 (2026-11-03):* `Settings › Labels` already exists with **Ticket size** only (table `workshop_label_settings`). INV-9 adds its label size and the selling price to that page and row, and widens its line "How your work tickets print.".

#### `INV-12` · `INV-13` · `INV-14` · Refactor the order
- **Type:** Feature | **Priority:** Must | **Size:** L, split on 2026-11-01 into three, one UI card each | **Status:** `INV-12`, `INV-13` and `INV-14` all `UI approved` (2026-11-01; `INV-14` was redrawn the same day for the shelf model). Building waits for its turn after `INV-5`.
- **Cards:** [INV-12 UI card: client first](stories/INV-12-client-first-ui.md) · [INV-13 UI card: product search with stock](stories/INV-13-order-products-ui.md) · [INV-14 UI card: order tracker, covered from the shelf](stories/INV-14-order-tracker-ui.md) · design spec [INV-12-refactor-the-order](design/specs/INV-12-refactor-the-order.md) · [INV-13 development card](stories/INV-13-order-products.md) (`Ready`, 2026-11-03).
- **The split (own order, each validated on its own):**
  - `INV-12` · client first on New order (point 1). **Built within `INV-4` (D-22) and done with it, 2026-11-02.**
  - `INV-13` · product search with stock per colour and size, and order lines per colour and size (point 2). **Done 2026-11-03** (`39647ce`).
    - **Answers on the development card (human, 2026-11-03, all the recommended options):** until INV-14 the order page's stages and buttons work per colour and size; Edit order's floor keeps today's "already in cutting, ready to ship or shipped" (INV-14 rewords it); a colour with units on open orders (not shipped) can't be removed from its product; orders recorded before read as the product's first colour; INV-13 is built on `PI-DEV`.
  - `INV-14` · order tracker (To deliver · Delivered · Cancelled), Mark delivered taking units off the finished stock, Cancel units, no production on orders (points 3 and 4). **Done 2026-11-03** (`7af1a63`). Needs `INV-13` and a redrawn `INV-5` (Progress makes products for the shelf, so cutting has somewhere to go).
    - **Answers on the development card (human, 2026-11-03):** Ready-to-ship units of old orders go on the shelf as one restock (Reference "Orders") and become To deliver; Shipped becomes Delivered; kept as one card (L); "Denim Jacket can't be removed: 5 units are delivered or cancelled."; Edit product keeps INV-13's sentence, counting units To deliver.
- **Decision of 2026-11-01 (human, on Q1–Q10):** order lines are a colour × size grid, built before `INV-13`; "Not started" and "Delivered" are the words; the client is phone first; cancel only what is not delivered, no undo, no stock effect. **Also:** a finished product is not tied to an order. Progress makes products **for the shelf**; an order is covered from the finished stock (the shelf is checked, never set aside). This changes `INV-5`, see below.
  - **Answers on the model (human, 2026-11-01):** (1) the shelf is checked, never held: no reservation, "On the shelf now" shown beside each colour × size, Mark delivered takes units off the finished stock up to what the shelf holds, its move back returns them, Cancel changes no stock. (2) An order counts **To deliver · Delivered · Cancelled · Total**; its status is Not started · In progress (partly delivered) · Delivered · Cancelled; the list filter is Open orders · Delivered · Cancelled · All orders. (3) Deleting an order is always allowed (no refusal); the confirm adds a sentence when units were delivered. (4) `INV-5`'s UI card is reopened (see `INV-5`).
  - **Answers on Progress and the order (human, 2026-11-01, all the recommended options):** Put in stock writes a restock (Reference = the batch number, Note "From production"), undoable in Restocks, and an undone restock doesn't bring the batch back; a scan can finish a batch; Undo batch only while In cutting; no order data on Progress (a "Waiting in open orders" column could be a later card); part of a batch can be put in stock; INV-13's line now reads "(M is out of stock, so those units wait for the shelf)"; and the defaults: "In stock" in the search and "On the shelf now" on the order page and Progress, no delivery history, no "Make more" link, one number for batch and ticket, Start batch only on Progress and its product cards, and the two new Edit product locks (a colour or a category with units in production).
  - **Order of work (D-23, INV-4 and INV-12 done 2026-11-02):** `INV-3` → `INV-9` → `INV-5` (UI card rewritten and validated again on 2026-11-01; development card next) → `INV-13` → `INV-14`. The order lines per colour and size are built before `INV-13`; the tech-lead says in which card. Existing orders with units in cutting, ready to ship or shipped need a mapping (Shipped → Delivered, the rest → To deliver); materials already taken by cuts on orders belong to no batch. The tech-lead says how to treat them.
- **The request, in four points:**
  1. **Client first.** New order starts with the client and all the information we need, like the client card. New order shows the client form. The phone number is unique per client, so each new order creates the client if it doesn't exist yet (match on phone).
  2. **Products to order.** A good search that finds products and shows, per colour (and size), whether each is in stock or not.
  3. **No cutting on orders.** Remove the cutting phase and everything related to it from orders. Cutting stays only on the Progress page.
  4. **Order tracker.** Remove the boxes that track the production phases on the order. Replace them with a tracker for the order itself: units delivered, in progress, cancelled and ready to ship, plus the total, with details per colour and size.
- **Links:**
  - `INV-4` (Clients): builds on it. INV-4 stays as approved and ships first; its client form and unique phone number are the base of point 1, and it already adds "Existing client · New client" to New order.
  - `INV-5` (Progress page): cutting and the production phases move there (points 3 and 4). INV-5's open discussion, counting order lines per colour and size, decides how the order's lines hold colours, so it must be settled before this item; points 2 and 4 depend on it.
  - `S-65` (closing orders, on hold): the tracker's "delivered" and the "Ready to close" step must agree. Check S-65 against this item before it resumes.
  - `S-68` (order screen tap targets, on hold): the new order screens must meet the 44 px tap targets, so S-68 is folded in or re-checked when this item is carded.
  - `INV-3` (Restock): the stock shown in the product search (point 2) is the finished stock that INV-3 and INV-2 maintain.
- **Today's cutting rules** (cut materials from stock, "Move back to To start", the delete refusal) are business rules; removing them from orders is a rule change the human decides when the card is written. The delete refusal keeps its meaning, reworded for In progress and Ready to ship (`INV-14`).
- **Other cards to re-check:** `S-65` (close a delivered order) and `S-57` (cancel an order) talk about "shipped" and cutting; both need rewording against the tracker before they resume. `S-68` is met by `INV-13` and `INV-14` (44 px on a phone).

#### `INV-6` · Picker row swatch edge
- **Type:** Tech | **Status:** `Idea`
- **Details:** Fixes contrast ratio on hovered/active rows in light/dark mode (currently ~2.8:1, target 3:1). Join phase tech allowance when proposed by tech-lead.

#### `INV-7` · Bigger tap targets on Settings pages
- **Type:** Feature | **Priority:** Could | **Size:** S–M | **Status:** `Idea`
- **Details:** Increases button/field heights on phone/tablet from 32-36 px to 44 px. Related to `S-68` (order screen tap targets). Runs after `INV-14`.

#### `INV-16` · Material use per size and colour, "Can produce" per colour
- **Type:** Feature | **Priority:** Must | **Status:** `Draft` (development card written 2026-11-06, awaiting the human's approval;
  the UI card is `Approved`; first logged as "INV-15" on 2026-11-06, renumbered once the `PI-DEV` merge showed that number taken by
  the done "Work tickets and Scan" item)
- **Cards:** [Development card](stories/INV-16-material-per-variant.md) (`Ready`, 2026-11-06) · [UI card: material use per colour and size](stories/INV-16-material-per-variant-ui.md) (`Approved`, 2026-11-06) · design
  spec [INV-16-material-per-variant](design/specs/INV-16-material-per-variant.md) · ADR [ADR-0027](adr/0027-material-use-per-colour-and-size.md).
- **The request, from the human (2026-11-06):**
  1. The system removes the correct amount of material depending on the size (and maybe the colour) made — the user sets up how
     much material an L uses, separately from M or XL.
  2. A new screen shows how much material is in stock and how many units can be made, **per colour**.
- **Why this needed decisions first, not a card:** today every size and every colour of a product shares one composition — "Sizes
  change no figure: every size of a product uses the same composition" and the same for colours. Point 1 changes that for sizes (and
  colours); point 2 asks for capacity split by colour. This was a business-rule change, not a screen, so it needed an ADR and the
  tech-lead's agreement on the costing rules before any card. **Settled:** ADR-0027 and the UI card above.
- **Decided by the human (2026-11-06):** quantity varies by **both size and colour** (a full grid); the input is a **flat quantity
  per size/colour combination** (not a multiplier on today's one quantity per material); "per colour" capacity means
  **colour-specific material stock** (each colour's fabric etc. is its own stocked material with its own quantity on hand), not the
  same stock sliced by what you'd cut.
- **Queue:** the human decided (D-27, 2026-11-06) to prioritize `INV-16` now, ahead of `INV-9` and `INV-7`, rather than append it
  after them. Its UI card is approved and its development card is written (`Draft`, awaiting approval); then the tech-lead adds the Technical work.
- **Links:** depends on the colour and size work already done (`INV-1`, `INV-2`) and sits downstream of the costing domain's BOM rules.

#### `INV-17` · Per-unit QR codes: move units one by one, besides batches
- **Type:** Feature | **Priority:** Should (the human's order, D-28, puts it ahead of the Must `INV-9`) | **Size:** L, to be split when carded (the split takes the next free IDs) | **Status:** `Idea`
- **What it brings:** in Progress the workshop keeps batch progress and adds progress per unit. A unit gets its own QR code; scanning
  it moves only that unit, through the same phases, until it is put in stock. Scanning a batch's work ticket still moves the batch.
- **The request, from the human (2026-11-06):** "In Progress we want to keep the batch progress, and add progress per item. Generate a
  QR code for each product (unit) and track its phases until the final step. Whenever a code is scanned, only that product moves."
  On how it relates to the batch tickets: "I want the system to handle both, based on what the user chooses: move a batch, or move one
  by one."
- **Decided by the human (2026-11-06, D-28):** per-unit QR codes come **alongside** today's batch tracking; the system supports **both**
  modes and the user chooses. The exact interaction model is still to be decided (the product-owner's brainstorm is in the hand-off of
  2026-11-06). Queue: after `INV-16`, before `INV-9`.
- **What exists:** `INV-5` (batches move through phases, Put in stock, Undo batch, split) and `INV-15` (a work ticket and QR per batch;
  the Scan page moves a whole batch; moving part of a batch by scan was left out). Barcodes per colour and size (`INV-8`) identify a
  variant, not one unit.
- **Before a card:** the human picks the interaction model; the ui-ux-designer draws the Progress and Scan changes; the tech-lead sizes
  the data and the split. Then the UI card, then the development card.

> [!abstract] Unscheduled Idea
> **Production for the shelf:** A batch with no attached client moving through production phases, ending with "Put in stock". *(No ID assigned yet.)* Since 2026-11-01 this is no longer an idea apart: it is how Progress works, and it is now `INV-5`'s rewritten card.

> [!abstract] Unscheduled Idea
> **Barcodes tab polish (from the INV-8 UX review, 2026-11-01):** a visible focus after "Generate" lands on the table; the confirm button
> keeps the keyboard focus while generating; SKU and "N more" links get bigger tap targets on a phone; the table has one name for screen
> readers, not two. Could / S. Best planned with INV-9, which touches the same tab. *(No ID assigned yet).*

---

## 🏛️ Decisions That Shape the Backlog

> [!summary] Core Operational Rules
> - **D-28 (2026-11-06):** `INV-17` (per-unit QR codes) is registered and placed after `INV-16`, before `INV-9`: `INV-16` → `INV-17` → `INV-9` → `INV-7`. Scope: unit QR codes **alongside** batch tracking; both modes supported, the user chooses; the interaction model is still to be decided.
> - **D-27 (2026-11-06):** `INV-16` moves to the front of the queue, ahead of `INV-9` and `INV-7`.
> - **D-20 (2026-10-24):** Card flow paused. Open items placed on hold for variant rework (`INV-n` sequence).
> - **D-26 (2026-11-03):** `INV-13` then `INV-14` move ahead of INV-3's acceptance and `INV-9`.
> - **D-25 (2026-11-02):** `INV-15` moves ahead of INV-3's acceptance and `INV-9` (its development card first); its Ticket size part waits for INV-9's `Settings › Labels`.
> - **D-24 (2026-11-02):** `INV-5` moves ahead of INV-3's acceptance and `INV-9`; it is split into `INV-5` (Progress) and `INV-15` (work tickets and Scan), sharing one UI card.
> - **D-23 (2026-11-02):** `INV-3` moves ahead of `INV-9`.
> - **D-22 (2026-11-02):** `INV-4` moves ahead of `INV-9` and `INV-3`, and takes in `INV-12` (New order's phone-first Client card).
> - **D-21 (2026-10-24):** Execution order set: `INV-1` ➔ `INV-2` ➔ `INV-3` ➔ `INV-4` ➔ `INV-5`. Technical items get `INV-n` numbers without UI cards.
> - **D-9 (2026-10-14):** One workshop per account. Catalog permissions scoped to workshop members.
> - **D-10 (2026-10-15):** "Start cutting" deducts raw materials from stock.
> - **D-12 (2026-10-15):** Phase 5 active. Remaining Phase 4 items postponed.
> - **D-15 / D-16 (2026-10-18):** Products by size. Count units per size when ordered, in production, and on shelf. Category dictates sizes (Apparel: S-XL; Footwear: 36-46; Other: One size). Single price and SKU across sizes.

<details>
<summary><b>📜 Click to view full historical architectural decisions (Q1–Q8, D-1 to D-17, S-60 to S-73)</b></summary>

- **Q1:** Native serving.
- **Q2:** Units editable; conversions handled later.
- **Q3 / D-9:** Workshop catalog editable by workshop members.
- **Q4:** Bigint keys (provisional).
- **Q6:** Currency/locale set in configuration.
- **Q7:** Units locked while in active use.
- **Q8:** Sales are stock-neutral; hard delete.
- **D-1:** GitHub repository with CI running on every push.
- **D-3:** Phase 4 scope approved.
- **D-8:** Overhead cost optional (blank = 0).
- **D-11 (2026-10-16):** Cutting rules. Refuses cutting beyond available stock. "Move back" restores exact deducted materials.
- **D-13:** `S-08` and `S-29` closed on `main`.
- **D-17 (2026-10-22):** Size cards approved (`S-60`, `S-61`, `S-56`, `S-57`).
- **S-56 (2026-10-23):** Finished stock rules. "Update stock" sets counts per size; chip UI layout; last save wins.
- **S-60 / S-61:** Size formatting in text ("36: 4 · 37: 8" vs "S 10 · M 20").
- **S-65 (2026-10-22):** Closing orders. Fully shipped orders transition to "Ready to close". Closing is final and permanent.
- **S-70:** Consistent size UI layout (44 px fields, 7-digit limits, explicit size tags).
- **S-71 / S-73:** Order and Product pages matched to design boards.

*Pending Decisions:* See [STATUS.md §2](STATUS.md#2-decisions-needed-from-you).
</details>
