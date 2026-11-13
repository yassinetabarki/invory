# Invory — Product Backlog

> [!info] Quick Links & Ownership
> **Owner:** `product-owner` (order, wording, acceptance criteria) · **Technical Detail:** `tech-lead` ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> **Phases & Goals:** [ROADMAP.md](ROADMAP.md) · **Progress:** [STATUS.md](STATUS.md)

> [!history] Last Cleaned: 2026-11-09
> - **Added 2026-11-13 (D-34):** the human decided the acceptance review of the delivered `INV-21`, all recommended options. Corrections went into the `INV-21` cards (wording and states, listed under D-34 below and in [STATUS.md](STATUS.md)); a fifth touch joined `INV-26`; the designer's remaining findings became `INV-28` (`Idea`, Could / S). The tech-lead's two small tech-debt items from the INV-21 delivery took `INV-29` and `INV-30` (see IDs & History). Next free ID is now **INV-31**. `INV-21` stays `Ready` until the human accepts and the commits land.
> - **Added 2026-11-11:** `INV-27` (Live production screen) — an idea from the human: a read-only board that follows the Progress page and shows newly arrived orders, opened in its own browser window for a TV in the workshop, updating by itself. Status `Idea`; the human asked to start with the UI design. Place decided by the human the same day: right after `INV-21`, before `INV-17` (see Item Details). **UI card `Approved` 2026-11-12.** Next free ID is now **INV-28**.
> - **INV-16 Done** (accepted 2026-11-09 by the human **without a click-through**: the 10-step click-through script was not run; one code commit `e01b2ea` covers steps INV-16-a … INV-16-m and the fix round of the UX review's ten items; docs `7548753`: cards, ADR-0027 accepted, tech debt INV-22 to INV-24 proposed). A composition line is **Same for every variant** or **By colour and size**; "Can produce now" shows guaranteed beside best, with Capacity by colour; cost and margin are ranges; Start batch and Undo batch work per colour and size; a size with saved quantities is locked. It left the open list. **Next (D-31): INV-21, then INV-17, INV-9, INV-7.** **Decided with the UX review (D-31):** a blank switch opens the first colour; the Composition table's footer badge follows the list badge ("16–112 units", "0–112 units" in warning, "Can't produce" only when best is 0); a One size field is named "Indigo, One size"; "Choose a material first." on a line with no material; taking a colour off shows a quiet line under Colours; switching straight back restores the typed quantity and asks nothing; the Capacity by colour skeleton stays as built; the summary's "only" rule and the Capacity by colour caption are left as polish. **Open gaps, none blocking** (collected in `INV-25`, [TECH-DEBT.md](TECH-DEBT.md)): the read-only grid's term/value list, the Sheet's first focus, the line-level error naming its cell, below-cost screen-reader text, "Edit grid for <material>", "Short by less than 0.0001 m", link target size, the Sheet's close X size, the disabled state's timing, the INV-18 focus gap, and 320 px wrapping still to be checked in a browser. The dev database `laravel` has the INV-16 migration applied; its one product "JEBA" has a By colour and size line of 21 cells (the human's own data; agents never seed or reset it).
> - **Added 2026-11-08:** `INV-21` (Material stock per colour) — an idea from the human, decisions recorded as **D-30**; UI card `Approved` and development card `Ready` the same day.
>   Place decided by the human: right after `INV-16`, before `INV-17` (see Item Details). Next free ID is now **INV-27** (INV-22 to INV-25 are tech-debt items, see below; INV-26 is the INV-21 polish idea, D-32).
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
> - **INV-18 Done** (accepted 2026-11-07 by the human **without a click-through**, with the UX defects fixed; one squashed code commit
>   `e069997` covers steps INV-18-a … INV-18-i and the UX fixes; docs `6954983`; UI card and card committed earlier, Technical work
>   `0dc6e74`, ADR-0028). Sizes are one list in Settings, each product picks its own, the category only suggests them, and every size
>   screen follows the product's sizes. It left the open list. **Small gaps, logged, none blocking** (the human's decisions of
>   2026-11-07): (1) a renamed "One size" still reads "One size" in lock sentences ("One size is in use, so other sizes can't be
>   chosen."); (2) "Sizes kept: you changed them." hides when the sizes match the category's; (3) the Sizes phone rows switch at the
>   md breakpoint; (4) a locked size's reason has no touch route (hover and focus only); (5) Cancel and Save in the reference dialog
>   aren't 44 px on phones; (6) not click-tested on the running app at 375 px and in dark mode. **INV-16 is next**, but its Technical
>   work must be replanned by the tech-lead on per-product sizes (ADR-0028) before the build.
> - **Current Order** (D-29, D-30, D-31 carried out; `INV-27` placed by the human 2026-11-11): `INV-21` → `INV-27` → `INV-17` → `INV-9` → `INV-7` (`INV-16` done 2026-11-09). Next
>   free ID (2026-11-09): **INV-27** (`INV-26` is the INV-21 polish idea); `INV-21` is "Material stock per colour"; `INV-22` to `INV-25` are tech-debt items; `INV-19` (the stale orders browser test) and `INV-20` (the package manager and lock file) are tech-debt items
>   the tech-lead proposed on 2026-11-07 in [TECH-DEBT.md](TECH-DEBT.md); they don't collide with INV-17 (per-unit QR codes), which keeps
>   its number. INV-11 is the tech-debt item raised in the INV-8 review; INV-15 is the real, done "Work tickets and the Scan page".
> - **Added 2026-11-06:** `INV-17` (Per-unit QR codes) — a new idea from the human: keep batch progress and add progress per unit, with
>   a QR code per unit; a scan moves only that unit. Both modes stay, the user chooses. **UI card draft** (2026-11-06): the human decided the
>   interaction model the same day (see Item Details); [INV-17-per-unit-qr-ui.md](stories/INV-17-per-unit-qr-ui.md) is `UI approved` (2026-11-06); its development card [INV-17-per-unit-qr.md](stories/INV-17-per-unit-qr.md) is `Ready` (approved 2026-11-06).
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
  - Everything new since **2026-10-24 (D-20, D-21)** is numbered **INV-n** (including technical items without UI cards). Next free ID: **INV-31**.
  - `INV-29`, `INV-30`: tech-debt items proposed by the tech-lead with the INV-21 delivery on 2026-11-13, outside the order. `INV-29`: small duplications left by INV-21 (two copies of the colour rows, local copies of two Restock helpers, a sentence written twice, an alias method). `INV-30`: the "colour not on this material" case of a return is rewritten after the domain call, and the Restock sheet could load lazily. The tech-lead proposed them as INV-28 and INV-29; `INV-28` was already the designer's leftovers, so they moved up one and two. See [TECH-DEBT.md](TECH-DEBT.md).
  - `INV-27`: Live production screen, a read-only board for a workshop TV (Progress batches by phase, plus newly arrived orders). Added 2026-11-11.
  - `INV-22`, `INV-23`, `INV-24`: tech-debt items proposed by the tech-lead on 2026-11-08, outside the order (list load time on a throttled host; the mirrored tie-break and the cells read twice; `ProductionCutMaterialFactory` and `unit_id` under strict models). `INV-25`: the polish and gaps collected from INV-16's UX review (2026-11-09). See [TECH-DEBT.md](TECH-DEBT.md).
  - `INV-21`: Material stock per colour (stock and cost per colour for materials such as fabric). Added 2026-11-08 (D-30); UI card `Approved` 2026-11-08; development card `Ready` (approved 2026-11-08).
  - `INV-18`: Sizes chosen per product, managed in Settings (done 2026-11-07, `e069997`).
  - `INV-19`, `INV-20`: tech-debt items proposed by the tech-lead on 2026-11-07 (the stale orders browser test; one package manager and lock file), see [TECH-DEBT.md](TECH-DEBT.md).
  - `INV-17`: Per-unit QR codes, moving units one by one besides whole batches. Added 2026-11-06 (D-28), placed after `INV-16` and before `INV-9`. UI card `Approved` 2026-11-06, development card `Ready` 2026-11-06 (see Item Details).
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
  - `INV-16`: Material use per colour and size, and "Can produce" per colour (done 2026-11-09, `e01b2ea`). Added 2026-11-06 (first as "INV-15", renumbered once the `PI-DEV` merge showed that number taken).
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
10. **INV-18** (Sizes chosen per product, managed in Settings) — `#done` *(Accepted 2026-11-07, `e069997`)*
11. **INV-16** (Material use per colour and size) — `#done` *(Accepted 2026-11-09, `e01b2ea`, without a click-through)*
12. **Next Sequence (D-29, D-30, D-31, D-33):** `INV-21` *(Material stock per colour)* ➔ `INV-27` *(Live production screen)* ➔ `INV-17` ➔ `INV-9` ➔ `INV-7`
8. **Card Structure:** Each feature receives a UI card first (`INV-n-<slug>-ui.md`), followed by a development card (`INV-n-<slug>.md`).
9. **Shared UI Cards:** `INV-2`, `INV-8`, and `INV-9` share the same UI card but are accepted individually; so do `INV-5` and `INV-15`.
10. **Phase 7 Tech Allowance:** `F-43` · `F-46` · `F-52` · `F-53`

### Open Backlog Table

| ID         | What it brings                                               | Type    | Status                        | Priority / Size    |
| ---------- | ------------------------------------------------------------ | ------- | ----------------------------- | ------------------ |
| **INV-21** | Material stock per colour: stock and cost per colour         | Feature | `Ready` 2026-11-08            | Must / XL          |
| **INV-27** | Live production screen: a TV board of batches and new orders | Feature | `UI approved` 2026-11-12      | Should / M–L       |
| **INV-17** | Per-unit QR codes: move units one by one, besides batches    | Feature | `Ready`                       | Should / L         |
| **INV-9**  | Print barcode labels                                         | Feature | `Ready`                       | Must / M           |
| **INV-7**  | Bigger tap targets on Settings pages                         | Feature | `Idea`                        | Could / S–M        |
| **INV-26** | INV-21 polish: five small touches left out of INV-21         | Feature | `Idea`                        | Could / S          |
| **INV-28** | INV-21 review leftovers: small fixes and browser checks      | Feature | `Idea`                        | Could / S          |

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

#### `INV-21` · Material stock per colour
- **Type:** Feature | **Priority:** Must | **Size:** XL (sized by the tech-lead 2026-11-09: complex, 19 commits; the card said L) | **Status:** `Ready`: UI card `Approved` and development card `Ready` (both 2026-11-08); Technical work proposed 2026-11-09, waiting for the human. The human chose to keep it as **one card**; the tech-lead cuts the build into steps.
- **Place in the order (decided by the human, 2026-11-08):** right after `INV-16`, before `INV-17`: `INV-16` → `INV-21` → `INV-17` → `INV-9` → `INV-7`. `INV-16` is done (2026-11-09), so this is one contained change on top of it.
- **What it brings:** a material can be set to **stock per colour**: its quantity on hand is kept for each colour (Indigo 250 m, Black 40 m), instead of the workshop making one material per colour ("Denim 12oz Indigo", "Denim 12oz Black") as it does today. A product's colour then uses the stock of the same colour of the material. Materials that don't vary by colour (buttons, thread) stay as they are.
- **Decided by the human (2026-11-08, D-30):**
  1. A material has a setting the user chooses: **stock per colour**, or **default** (as today: one stock for all colours). Buttons, thread and the like stay one material on the default setting; fabric and the like can be set to per colour.
  2. The colours of a per-colour material come from the system's colour list (`Settings › Colours`). A product's colour is matched to the material's colour **by the colour itself**, not by its name.
  3. **No merging** of existing materials such as "Denim 12oz Indigo" and "Denim 12oz Black": the user fixes those by hand, for now.
  4. **Unit cost can differ per colour**, with a second setting: cost the same for all colours, or cost per colour.
  5. **Restocking changes:** restocking a per-colour material records quantities per colour.
  6. **A clean, simple screen shows how much of each material is in stock per colour.**
- **Consequences for `INV-16` (nothing is reopened):** the colour × size grid, the quantity-mode choice, the guaranteed / best wording, the cost range and the size lock all stay as designed. What changes is where stock and cost are read: "Can produce" for a variant uses the stock (and cost) of the variant's own colour; Start batch takes from, and Undo batch returns to, that colour's stock; the cut records keep an amount per material and colour. Today's way of making a line "only for Indigo" (cells only under Indigo) keeps working for materials on the default setting.
- **Also touched (for the tech-lead to size):** restock, Add and Edit material, the stock history check, the dashboard and the Inventory value, the material screens and "Used in". About 17 app files, 21 frontend files and 37 test files mention the quantity on hand today. A change to the business rules in the costing domain (ADR-0027 §2 and a new ADR) must be agreed and written by the tech-lead before any card.
- **Answered by the human (2026-11-08, all the recommended options; all twelve questions are settled):**
  7. **The two settings:** cost per colour is offered only when stock per colour is on; the cost setting is hidden for default materials.
  8. **Switching to stock per colour on a material that has stock:** the user picks the colour in the same step; the other colours start at 0.
  9. **Switching back to default:** allowed with a confirmation that names the sum ("Indigo 250 m + Black 40 m become one stock of 290 m"). For the cost setting, switching to "same for all colours" asks which colour's cost to keep.
  10. **A product colour the material doesn't have:** the variant shows 0 can produce, limited by "Denim 12oz — no Black stock", with "Add Black to this material" (the colour starts at 0).
  11. **The material's free-text "Colour" field:** kept as a plain description; ignored or hidden for per-colour materials.
  12. **Restock:** one receipt can cover several colours: a quantity per colour, a price per colour when cost is per colour, one reference and one date.
  13. **Reorder level:** one level per material, applied to each colour; the material is "Low" when any colour is at or under it, and the screen names the colour.
- **Decided by the human (2026-11-08), the last four, all the recommended options:**
  14. A per-colour material on a "same for every variant" line: each variant takes from its own colour's stock, matched by colour.
  15. A colour can be removed from a material only while its stock is 0 and it has no history; otherwise it stays.
  16. Inventory value with per-colour cost: the sum over colours of quantity × that colour's cost.
  17. No hint for look-alike materials such as "Denim 12oz Indigo" and "Denim 12oz Black"; the user fixes them by hand.
- **Decided by the human (2026-11-08), on the UI card, all the recommended options:**
  18. A per-colour material's status: **Low stock** when any colour is at or under the reorder level (0 counts); **Out of stock** only when every colour is at 0. The dashboard's "Needs restock" counts materials and names the colours.
  19. A colour added to a cost-per-colour material needs its unit cost typed when it is added (on the form and in "Add Light wash to Denim 12oz"); no price is guessed.
  20. On a per-colour restock the "use this price as the new unit cost" checkbox stays checked by default, as today (it changes only colours that received a quantity).
- **UI card `Approved` (2026-11-08, validated by the human):** [UI card](stories/INV-21-material-stock-per-colour-ui.md) · design spec [INV-21-material-stock-per-colour](design/specs/INV-21-material-stock-per-colour.md) · boards `Inv21Stock`, `Inv21Restock`, `Inv21MaterialForm`, `Inv21Readers`, `Inv21Phone` (Invory App UI canvas). **Development card `Ready` (approved by the human 2026-11-08):** [Development card](stories/INV-21-material-stock-per-colour.md) (19 criteria in build order; size XL after the tech-lead's sizing, kept as one card by the human's choice). **Next:** the tech-lead's sizing, the new ADR and the costing-domain rules, then its Technical work, when its turn comes (after `INV-16`). Nothing is built before that. Its wording replaces some of INV-16's (bottleneck sentence, Start batch shortfall, the grid sheet's muted line, "Varies"); INV-16's cards stay as approved.
- **Decided by the human (2026-11-09, D-32), on the tech-lead's eight questions, all the recommended options:** (1) "View by colour" shows a range per colour ("Indigo 103–142 units · Black 16–22 units"); (2) the suggestion example reads "20 units, in Black"; (3) a per-colour material with no reorder level is never Low, and is Out only when every colour is at 0; (4) a colour a cost-per-colour material lacks has no cost, and its variants are left out of the product's cost and margin ranges; (5) a Start batch in several colours on a product with only "Same for every variant" lines but a per-colour material reads like a mixed grid ("… Restock them first, or lower the units."); (6) switching is logged as "Split by colour" and "Joined into one stock"; (7) Undo batch gives nothing back to a colour the material no longer has ("Light wash not on this material, not returned"); (8) four small touches of the UI card are left out and logged as `INV-26`. **The criteria AC-5, AC-11, AC-12, AC-14, AC-15 and AC-17 were edited for this and need the human's re-approval.** The approved UI card's line 192 ("or a product with no By colour and size line, keeps 'You can cut at most N now'") is narrowed by (5): that sentence now holds only when no material of the product is per colour.
- **Still to do:** the human's re-approval of the six edited criteria, then the human's approval of the Technical work (the tech-lead applies D-32 to it first).

#### `INV-26` · INV-21 polish: five small touches left out of INV-21
- **Type:** Feature | **Priority:** Could | **Size:** S | **Status:** `Idea` (no card yet; the human decided on 2026-11-09, D-32, to leave the first four out of INV-21; they are drawn in the approved [INV-21 UI card](stories/INV-21-material-stock-per-colour-ui.md). The fifth was added on 2026-11-13, D-34, in the acceptance review)
- **What it brings:** (1) the material search on Add and Edit product shows "290 m in 2 colours" for a per-colour material; (2) the note under a composition line when the product has a colour the material lacks ("Denim 12oz has no Light wash stock yet. Add Light wash"); (3) the muted line in INV-16's grid sheet; (4) "Stock per colour" under the material's name; (5) the Undo batch window explains its two labels "Deleted, not returned" and "Unit changed, not returned" (today they show with no sentence saying why nothing came back). The product page already shows the same warning and link, so nothing is blocked without them. Order: after `INV-21`; the human places it.

#### `INV-28` · INV-21 review leftovers: small fixes and browser checks
- **Type:** Feature | **Priority:** Could | **Size:** S | **Status:** `Idea` (no card yet; collected on 2026-11-13, D-34, from the designer's review of the delivered INV-21; the human chose to keep these out of the INV-21 acceptance)
- **What it brings:** (1) **Check in a browser:** the composition table, dark mode and the phone width (375 px) of INV-21 have not been seen on screen yet. (2) **Find colour with no match** shows nothing; it should say `No colour named "teal".` (3) After **Switch to one stock**, the quantity field is read-only with no reason on screen; say why. (4) The Stock by colour buttons say "Restock" (accessible name "Restock Black"); make the visible text match the name. (5) A dashboard "Needs restock" row's second line reads "290 m · reorder at 100 m"; for a per-colour material it should say "each colour", as the other screens do. (6) The **Add colour** window: the cost field shifts the layout when it appears, and the field that takes focus on opening brings up the phone keyboard; neither should happen. (7) The focus ring of the radio cards has a contrast of 2.7:1 (the target is 3:1); it is the same card everywhere in the app, so the tech-lead decides whether this becomes a tech-debt item. (8) The one-stock **Restock** overflow message reads "The stock would exceed 9999999999.9999." with no separators, while the per-colour screens write "9,999,999,999.9999"; make it match (decided 2026-11-13, D-35). (9) A colour at 0 on the material reads "Denim 12oz has no Black in stock, so Black, size XL can't be made." with no extra reorder-level sentence. (10) The Products list, when only some colours lack a cost, keeps the range of the costed colours (the product page does the same, decided D-35). (11) On a phone, the list card's label reads "Needs restock: Black". (12) The failure toast gets a **Try again** action. (13) The "No unit cost yet" sentence: its size and its full stop. (14) The **Add colour** window: focus jumps when the cost field appears. (15) The list markup: the **2 colours** button points (aria-controls) at a list that doesn't exist while the panel is closed. Order: after `INV-21`; the human places it.

#### `INV-27` · Live production screen
- **Type:** Feature | **Priority:** Should | **Size:** M–L (the tech-lead sizes it) | **Status:** `UI approved` (validated by the human 2026-11-12); development card written 2026-11-12 (`Draft`, waits for the human's approval).
- **Cards:** [Development card](stories/INV-27-live-production.md) (`Draft`) · [UI card](stories/INV-27-live-production-ui.md) (`Approved`, 2026-11-12) · design [INV-27-live-production](design/specs/INV-27-live-production.md).
- **Decided by the human (2026-11-11, D-33):** (1) **Open on TV** on Progress opens the board in a new window, and the address can be bookmarked; (2) the board is opened by a **private share link with no sign-in** (the TV is never signed in); the owner creates, copies and switches off the link in the app; (3) a refresh every 15 seconds (default); (4) new orders = open orders of the last 48 hours, newest first, older not-started orders under **Waiting**; (5) a finished batch leaves at once, with a **Put in stock today** counter; (6) one screen, two zones (production left, orders right); (7) the look follows the user's theme (refined into Light / Dark / Follow the TV on the link, preset to the owner's setting, confirmed by the human the same day); (8) the TV shows the client's full name and city; (9) place in the order: `INV-21` → `INV-27` → `INV-17` → `INV-9` → `INV-7`.
- **Settled by the product-owner on the designer's questions (2026-11-11):** product photo on a batch card (64 px, initials when none); date and clock on the board; a change stays highlighted 2 minutes; a shelf line on each new order; **Waiting** = open orders recorded more than 48 hours ago with nothing delivered yet; "new" means recorded in Invory in the last 48 hours (not dated in them: the order date is what the owner typed), and "waiting" counts whole days from the order date (2026-11-12); one link per workshop (a new link replaces the old one); any member of the workshop may create or switch off the link, the dialog shows who (no roles yet, ADR-0013); the link lives in the **Open on TV** dialog on Progress only; past 5 batches in a column every column switches to one-line cards, then "and N more batches". **One UI card** for the screen; the development card is split into a link-and-production-zone part and an orders-zone part only if the tech-lead rates the work above M (the split takes the next free IDs).
- **The request, from the human (2026-11-11):** "i want build feature live production to track all product begin processed from progress this screen i want be able to open it in separate windows browser that i can put in TV so team can track what is been processed i live and also orders that newly came and will need to make cool ui simple for the user."
- **What it brings:** a read-only "Live production" board for a TV in the workshop. It shows what the Progress page tracks (batches In cutting and in each production phase: product, colour, size, units, since when) and the orders that newly arrived. It opens in its own browser window and updates by itself, with nobody touching it.
- **What it builds on:** `INV-5` (Progress tiles and tables), `INV-15` (tickets, Scan), `INV-13` and `INV-14` (orders and the order tracker). Not on `INV-17`: the first version shows batches; a per-unit view can come after `INV-17`.
- **Proposed place (the product-owner recommends; the human decides):** right after `INV-21`, before `INV-17`: `INV-21` → `INV-27` → `INV-17` → `INV-9` → `INV-7`. Why: it needs nothing from `INV-21`'s code, the human's own small asks have jumped the queue before, and it is cheaper than `INV-17`. The design work is docs-only and can start now without touching `INV-21`.
- **Also answered by the human (2026-11-11, the recommended options):** a link never expires until switched off; "Put in stock today" is net since midnight; the shelf line compares each order with the shelf alone. No open questions; the UI card waits for the human's validation.

#### `INV-17` · Per-unit QR codes: move units one by one, besides batches
- **Type:** Feature | **Priority:** Should (the human's order, D-28, puts it ahead of the Must `INV-9`) | **Size:** L, kept as one card with ten criteria in build order; the tech-lead sizes it and may propose a split (the "every move joins" part first, which stands alone; the split takes the next free IDs) | **Status:** `Ready` (development card approved by the human 2026-11-06; the UI card is `Approved`). The tech-lead adds the Technical work only when its turn comes in the queue (`INV-18` → `INV-16` → `INV-17`).
- **Cards:** [Development card](stories/INV-17-per-unit-qr.md) (`Ready`) · [UI card](stories/INV-17-per-unit-qr-ui.md) (`Approved`, 2026-11-06) · design [INV-17-per-unit-qr](design/specs/INV-17-per-unit-qr.md).
- **What it brings:** in Progress the workshop keeps batch progress and adds progress per unit. A unit gets its own QR code; scanning
  it moves only that unit, through the same phases, until it is put in stock. Scanning a batch's work ticket still moves the batch.
- **The request, from the human (2026-11-06):** "In Progress we want to keep the batch progress, and add progress per item. Generate a
  QR code for each product (unit) and track its phases until the final step. Whenever a code is scanned, only that product moves."
  On how it relates to the batch tickets: "I want the system to handle both, based on what the user chooses: move a batch, or move one
  by one."
- **Decided by the human (2026-11-06, D-28):** per-unit QR codes come **alongside** today's batch tracking; the system supports **both**
  modes and the user chooses. Queue: after `INV-16`, before `INV-9`.
- **Interaction model, decided by the human (2026-11-06):**
  - **Model B with regrouping:** a unit scan splits that unit off its batch and moves it one step; it joins the batch of the same start
    already in that phase; a batch has one phase. The paper decides the mode: a ticket moves a batch, a unit label moves one unit.
  - **Unit codes** are made by an optional tick at Start batch (off by default) and by **Print unit codes** in a batch's "⋯" menu.
  - **Last phase:** a unit scan asks "Put 1 unit in stock?" and is a restock of 1 (Reference = the batch it is in then, Note "From
    production"), one restock per scan.
  - **Fast scanning:** a Scan-page switch "Move units as soon as I scan", off by default, remembered on the device.
  - **Out of scope** (later cards): lost or defective units, and undoing a mistaken scan.
- **Settled in the UI card draft by the product-owner (2026-11-06, from the designer's questions):** fast mode moves unit labels only (a
  ticket always asks) and puts a unit in stock at once; a unit the system has ahead of the floor is not refused (the confirm names
  "Finishing (now)" and Move back is the fix); every move joins a batch of the same start already in the phase, and the batch already
  there keeps its number; a separate "Unit label size" in Settings › Labels; the button is **Find**; the Start batch tick is not
  remembered; a partly used A4 sheet ("Start at slot") is decided together with `INV-9`.
- **UI card approved by the human (2026-11-06), with three answers:** (1) a unit ahead of the floor is not refused (the recommended
  option); (2) every move joins a batch of the same start already in the phase, batches without unit codes included; (3) the two
  limits (200 units at a start with the tick on, 100 labels at a time) are left to the tech-lead.
- **What exists:** `INV-5` (batches move through phases, Put in stock, Undo batch, split) and `INV-15` (a work ticket and QR per batch;
  the Scan page moves a whole batch; moving part of a batch by scan was left out). Barcodes per colour and size (`INV-8`) identify a
  variant, not one unit.
- **Development card (2026-11-06):** the UI card's ten checks are taken over as AC-1 to AC-10. It states plainly that **every move
  joins** a batch of the same start already in the phase, which changes INV-5's splitting (today a partial move always makes a new
  batch), also for batches without unit codes. The two limits (200 units at a start with the tick on, 100 labels at a time) are
  placeholders left to the tech-lead.
- **Built after `INV-18` and `INV-16`:** nothing in the card needs their data, but Start batch's materials and "Can produce now" are
  then the per-variant ones from `INV-16` (the card quotes no material figures for that reason), and the sizes on a unit label are
  the product's own from `INV-18`.
- **Card approved by the human (2026-11-06, `Ready`), with these answers:** (1) the card stays one L card; the tech-lead proposes a
  split only if it rates the work above L; (2) the unit toasts stay as written ("1 unit of Denim Jacket · Indigo S moved to
  Finishing.", "… and joined batch B-0155.", "1 unit of Denim Jacket · Indigo S put in stock."), and the note's last sentence is dropped
  when all units of a batch move; (3) batches of one start that already sit side by side in a phase are **left as they are** (nothing
  is merged), and the next move into that phase joins the lowest-numbered one (written into AC-5 and "How it works today").
- **Next:** the tech-lead adds the Technical work and sizes it, but only when INV-17's turn comes (`INV-16` done → `INV-21` → `INV-17`).

> [!abstract] Unscheduled Idea
> **Production for the shelf:** A batch with no attached client moving through production phases, ending with "Put in stock". *(No ID assigned yet.)* Since 2026-11-01 this is no longer an idea apart: it is how Progress works, and it is now `INV-5`'s rewritten card.

> [!abstract] Unscheduled Idea
> **Barcodes tab polish (from the INV-8 UX review, 2026-11-01):** a visible focus after "Generate" lands on the table; the confirm button
> keeps the keyboard focus while generating; SKU and "N more" links get bigger tap targets on a phone; the table has one name for screen
> readers, not two. Could / S. Best planned with INV-9, which touches the same tab. *(No ID assigned yet).*

---

## 🏛️ Decisions That Shape the Backlog

> [!summary] Core Operational Rules
> - **D-34 (2026-11-13, human):** the acceptance review of the delivered `INV-21`, all recommended options. Colours keep the order of Settings › Colours (with the default list, Black comes before Indigo); the app's toast "Material created." stays; the column "In stock" stays; the Add-colour texts name the product, not "jacket" ("…so Denim Jacket can't be made in Light wash."); in the switch-back window **Keep stock per colour** stays usable while loading and on failure; a colour the material lacks on a cost-per-colour material shows "No unit cost yet. Add Light wash to Denim 12oz." and no margin; focus after adding a colour from Start batch returns to the link, or stays in the window if the link is gone; the "2 colours" button is named "2 colours, show stock by colour for Denim 12oz"; the Undo window's missing explanation is a fifth touch of `INV-26`; small wording: "The stock would exceed 9,999,999,999.9999.", "1 of 12 colours needs restock" / "0 of 12 colours need restock", "No colour needs restock.", the toast "Couldn't open Restock for Denim 12oz. Try again."; on a phone the Restock sheet keeps a 44 px Cancel button beside Add to stock; the bottleneck callout adds "It's also below its reorder level" only when the limiting colour is itself low. The designer's other findings are `INV-28`.
> - **D-33 (2026-11-11, human):** `INV-27` (Live production screen) is registered and placed after `INV-21`, before `INV-17`: `INV-21` → `INV-27` → `INV-17` → `INV-9` → `INV-7`. The board is read-only, opens through a private link with no sign-in, refreshes every 15 seconds, shows production by step (left) and new orders from the last 48 hours with older not-started orders under Waiting (right), a Put in stock today counter, and the client's full name and city. The UI card is drafted. **Answered the same day, all the recommended options:** the look is chosen on the link (Light / Dark / Follow the TV, preset to the owner's setting); a link lives until the owner switches it off; "Put in stock today" is net since midnight (an undo takes units off); each new order's shelf line is compared with the shelf on its own, as the order page does.
> - **D-32 (2026-11-09, human):** the tech-lead's eight questions on `INV-21`, all the recommended options: ranges per colour in "View by colour"; "20 units, in Black"; no reorder level means never Low; a colour a cost-per-colour material lacks has no cost and is left out of the cost and margin ranges; a mixed Start batch with a per-colour material reads "… Restock them first, or lower the units."; switch entries "Split by colour" and "Joined into one stock"; an Undo batch gives nothing back to a colour the material no longer has; four small UI-card touches become `INV-26` (`Idea`). Six criteria edited, re-approval needed.
> - **D-31 (2026-11-09, human):** INV-16 accepted without a click-through, and the decisions of its UX review: a blank switch to By colour and size opens the first colour; the Composition table's footer badge follows the Products list badge ("16–112 units", "0–112 units" in warning, "Can't produce" only when best is 0); a One size product's field is named "Indigo, One size"; a line with no material can't be switched ("Choose a material first."); taking a colour off while quantities exist shows a quiet line under Colours, with no question; pressing By colour and size and straight back restores the typed quantity and asks nothing; the Capacity by colour skeleton stays as built; the summary's "only" rule and the Capacity by colour caption stay as polish (INV-25). Order: `INV-21` → `INV-17` → `INV-9` → `INV-7`.
> - **D-30 (2026-11-08, human):** materials get **stock per colour** (a per-material setting, default = one stock as today), matched to product colours by colour, with an optional **cost per colour**, per-colour restock, no merge of existing per-colour-named materials, and a simple per-colour stock screen. Registered as `INV-21` (`Idea`). **Order decided the same day: `INV-16` → `INV-21` → `INV-17` → `INV-9` → `INV-7`.** Also decided: cost per colour only exists with stock per colour; switching on asks for the colour that gets the existing stock; switching off asks for confirmation (and which cost to keep); a missing colour shows 0 can produce with "Add <colour> to this material"; the free-text Colour field stays a plain description; one restock covers several colours; one reorder level per material, applied to each colour. Also decided: each variant takes from its own colour's stock on "same for every variant" lines; a colour leaves a material only while its stock is 0 and it has no history; inventory value is the sum of quantity × that colour's cost; no hint for look-alike materials. All twelve questions are settled. **On the UI card (same day):** the material is Low when any colour is at or under the level and Out only when every colour is 0 (the dashboard counts materials and names the colours); a new colour on a cost-per-colour material needs its cost; the restock price checkbox stays checked by default. The UI card was validated by the human the same day (`Approved`). This lifts the "can be added later" line of ADR-0027's rejected alternatives.
> - **D-29 (2026-11-06, human):** sizes chosen per product and managed in Settings, like units, come **first** as `INV-18` (an `Idea`; the human's answer on `INV-16`). `INV-16` stays `Ready` but is **on hold until `INV-18` is done**; its Technical work is replanned then (category lock → "a size with cells can't be taken off", sizes from the product's own list). Order: `INV-18` → `INV-16` → `INV-17` → `INV-9` → `INV-7`. **Carried out: INV-18 done 2026-11-07 (`e069997`).**
> - **D-28 (2026-11-06):** `INV-17` (per-unit QR codes) is registered and placed after `INV-16`, before `INV-9`: `INV-16` → `INV-17` → `INV-9` → `INV-7`. Scope: unit QR codes **alongside** batch tracking; both modes supported, the user chooses. The interaction model was decided the same day (see Item Details). Development card approved (`Ready`) 2026-11-06: one L card; toasts as written; old side-by-side batches of a start are left as they are and the next move joins the lowest-numbered one.
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
