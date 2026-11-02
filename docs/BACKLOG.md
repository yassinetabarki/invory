# Invory — Product Backlog

> [!info] Quick Links & Ownership
> **Owner:** `product-owner` (order, wording, acceptance criteria) · **Technical Detail:** `tech-lead` ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> **Phases & Goals:** [ROADMAP.md](ROADMAP.md) · **Progress:** [STATUS.md](STATUS.md)

> [!history] Last Cleaned: 2026-11-02
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
> - **Current Order** (D-24): `INV-5` → `INV-3` (acceptance) → `INV-9` → `INV-15` → `INV-13` → `INV-14` → `INV-7`. Next free ID: **INV-16** (INV-11 is the tech-debt item raised in the INV-8 review, see [TECH-DEBT.md](TECH-DEBT.md)).
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
  - Everything new since **2026-10-24 (D-20, D-21)** is numbered **INV-n** (including technical items without UI cards). Next free ID: **INV-16**.
  - `INV-15`: Work tickets and Scan, split from INV-5 on 2026-11-02 (D-24); shares INV-5's UI card.
  - `INV-1` to `INV-5`: Variant rework items (`INV-1` is done).
  - `INV-6`: Picker-row swatch edge item.
  - `INV-7`: Settings tap targets.
  - `INV-2`: Stock per colour and size (done, `46af1cc`, `ab313eb`, `4088200`).
  - `INV-8` & `INV-9`: Barcodes (done, `9f93175` … `3b55319`) and labels (split from INV-2 on 2026-10-31).
  - `INV-10`: PostgreSQL test fix (done, `76b1770`).
  - `INV-11`: Small barcode clean-ups from the INV-8 review (MINOR tech debt, in [TECH-DEBT.md](TECH-DEBT.md); proposed for when barcodes are next touched, INV-9).
  - `INV-12`, `INV-13`, `INV-14`: Refactor the order (client first · product search with stock · order tracker and no cutting on orders). Added 2026-11-01 as `INV-12`, split into three the same day.
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
5. **Next Sequence (D-24):** `INV-5` ➔ `INV-3` *(acceptance)* ➔ `INV-9` ➔ `INV-15` ➔ `INV-13` ➔ `INV-14` ➔ `INV-7` *(the UI cards of INV-5, INV-12, INV-13 and INV-14 are all `UI approved`, 2026-11-01)*
6. **Card Structure:** Each feature receives a UI card first (`INV-n-<slug>-ui.md`), followed by a development card (`INV-n-<slug>.md`).
7. **Shared UI Cards:** `INV-2`, `INV-8`, and `INV-9` share the same UI card but are accepted individually; so do `INV-5` and `INV-15`.
8. **Phase 7 Tech Allowance:** `F-43` · `F-46` · `F-52` · `F-53`

### Open Backlog Table

| ID | What it brings | Type | Status | Priority / Size |
| :--- | :--- | :--- | :--- | :--- |
| **INV-5** | Production for the shelf: Progress page *(Technical work approved 2026-11-02: 12 steps, INV-5-a … INV-5-l)* | Feature | `Planned` | Must / L |
| **INV-3** | Restock of finished products *(built, waiting for acceptance)* | Feature | `Planned` | Must / L |
| **INV-9** | Print barcode labels | Feature | `Ready` | Must / M |
| **INV-15** | Work tickets and the Scan page *(split from INV-5)* | Feature | `UI approved` | Must / M |
| **INV-13** | Refactor the order, 2 of 3: product search with stock | Feature | `UI approved` | Must |
| **INV-14** | Refactor the order, 3 of 3: order tracker, orders served from the shelf | Feature | `UI approved` | Must |
| **INV-7** | Bigger tap targets on Settings pages | Feature | `Idea` | Could / S–M |

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

#### `INV-3` · Restock finished products
- **Type:** Feature | **Priority:** Must / L | **Status:** `Planned` *(Technical work approved 2026-11-02: 11 steps, INV-3-a … INV-3-k)*
- **Cards:** [Development Card](stories/INV-3-restock-finished-products.md) · [UI Card](stories/INV-3-restock-finished-products-ui.md)
- **Details:** 
  - Adds Restocks page in sidebar + New restock page (colour × size grid up to 50 products, scanning support).
  - Restock adds to shelf and never touches materials; can be undone as a whole.
  - Restock serves as the main action on the product page's Sizes & colours card.

#### `INV-5` · Production progress page
- **Type:** Feature | **Priority:** Must | **Size:** L | **Status:** `Planned` *(Technical work approved 2026-11-02: 12 steps, INV-5-a … INV-5-l; development card approved 2026-11-02, D-24; UI card reopened and rewritten 2026-11-01, validated the same day)*
- **Cards:** [Development Card](stories/INV-5-progress.md) · [UI Card](stories/INV-5-progress-ui.md) *(the rewritten version replaces the one validated on 2026-10-30)* · design spec [INV-5-production-for-the-shelf](design/specs/INV-5-production-for-the-shelf.md)
- **Details:** 
  - Progress page in sidebar: phase tiles (In cutting, the workshop's own phases), per-product colour × size table with "On the shelf now", phase-by-phase view of **batches** (one product, colour and size; oldest first).
  - **Start batch** takes materials under S-53's rules; the path is In cutting → own phases → **Put in stock**, which adds to finished stock and writes a restock (Reference = the batch number, Note "From production"). **Undo batch** only while In cutting.
  - Configurable phases (`Settings › Production phases`; In cutting and Put in stock are fixed).
  - QR work tickets per batch (no client; the batch and ticket share one number); Scan page moves batches forward, and finishes them at the last phase.
  - *Settled 2026-11-01:* order lines are counted per colour and size (built before `INV-13`).
  - **Reopened for UI (2026-11-01, human):** finished products are not tied to an order. Progress makes products **for the shelf** and an order is served from the finished stock. The designer redrew Progress (spec revision 3, boards v69) and the card above was rewritten from it; it replaces the version validated on 2026-10-30. The human validated it on 2026-11-01, so its development card can be written when it is next in order.

#### `INV-15` · Work tickets and the Scan page
- **Type:** Feature | **Priority:** Must | **Size:** M | **Status:** `UI approved` *(split from INV-5 on 2026-11-02, D-24; no development card yet)*
- **Cards:** [UI Card](stories/INV-5-progress-ui.md) *(shared with INV-5: "Work tickets", "Scan page and the scan confirm")*
- **Details:**
  - A work ticket per batch (QR code, the batch number, no client or price), printed from Progress: after Start batch, from the "⋯" menu, and from a split's toast.
  - Ticket size in `Settings › Labels` (needs INV-9's page), the **Scan** page in the sidebar, and the scan confirm that moves a batch to its next phase or puts it in stock.
  - Needs INV-5 (batches) and INV-9 (Settings › Labels).

#### `INV-12` · `INV-13` · `INV-14` · Refactor the order
- **Type:** Feature | **Priority:** Must | **Size:** L, split on 2026-11-01 into three, one UI card each | **Status:** `INV-12`, `INV-13` and `INV-14` all `UI approved` (2026-11-01; `INV-14` was redrawn the same day for the shelf model). Building waits for its turn after `INV-5`.
- **Cards:** [INV-12 UI card: client first](stories/INV-12-client-first-ui.md) · [INV-13 UI card: product search with stock](stories/INV-13-order-products-ui.md) · [INV-14 UI card: order tracker, covered from the shelf](stories/INV-14-order-tracker-ui.md) · design spec [INV-12-refactor-the-order](design/specs/INV-12-refactor-the-order.md). No development cards yet.
- **The split (own order, each validated on its own):**
  - `INV-12` · client first on New order (point 1). **Built within `INV-4` (D-22) and done with it, 2026-11-02.**
  - `INV-13` · product search with stock per colour and size, and order lines per colour and size (point 2). Needs `INV-2`, `INV-3`, and the answer on order lines per colour and size.
  - `INV-14` · order tracker (To deliver · Delivered · Cancelled), Mark delivered taking units off the finished stock, Cancel units, no production on orders (points 3 and 4). Needs `INV-13` and a redrawn `INV-5` (Progress makes products for the shelf, so cutting has somewhere to go).
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

> [!abstract] Unscheduled Idea
> **Production for the shelf:** A batch with no attached client moving through production phases, ending with "Put in stock". *(No ID assigned yet.)* Since 2026-11-01 this is no longer an idea apart: it is how Progress works, and it is now `INV-5`'s rewritten card.

> [!abstract] Unscheduled Idea
> **Barcodes tab polish (from the INV-8 UX review, 2026-11-01):** a visible focus after "Generate" lands on the table; the confirm button
> keeps the keyboard focus while generating; SKU and "N more" links get bigger tap targets on a phone; the table has one name for screen
> readers, not two. Could / S. Best planned with INV-9, which touches the same tab. *(No ID assigned yet).*

---

## 🏛️ Decisions That Shape the Backlog

> [!summary] Core Operational Rules
> - **D-20 (2026-10-24):** Card flow paused. Open items placed on hold for variant rework (`INV-n` sequence).
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
