# Invory — Product Backlog

> [!info] Quick Links & Ownership
> **Owner:** `product-owner` (order, wording, acceptance criteria) · **Technical Detail:** `tech-lead` ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> **Phases & Goals:** [ROADMAP.md](ROADMAP.md) · **Progress:** [STATUS.md](STATUS.md)

> [!history] Last Cleaned: 2026-10-31
> - **INV-2 Done** (accepted 2026-10-31; `46af1cc`, `ab313eb`, `4088200`), so it left the open list. The PostgreSQL parity suite was green before the commits (2619 tests). INV-8 is next.
> - **INV-10 done** (`76b1770`; its card `1f854bf`), so it left the open list.
> - **INV-2 Split:** INV-2's card was split into three: **INV-2** (stock, done), **INV-8** (barcodes), and **INV-9** (labels).
> - **Current Order** (INV-2 done): `INV-8` → `INV-9` → `INV-3` → `INV-4` → `INV-5` → `INV-7`. Next free ID stays **INV-11**: the tab-order fix landed in INV-2, so no INV-11 was needed.
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
  - Everything new since **2026-10-24 (D-20, D-21)** is numbered **INV-n** (including technical items without UI cards). Next free ID: **INV-11**.
  - `INV-1` to `INV-5`: Variant rework items (`INV-1` is done).
  - `INV-6`: Picker-row swatch edge item.
  - `INV-7`: Settings tap targets.
  - `INV-2`: Stock per colour and size (done, `46af1cc`, `ab313eb`, `4088200`).
  - `INV-8` & `INV-9`: Barcodes and labels (split from INV-2 on 2026-10-31).
  - `INV-10`: PostgreSQL test fix (done, `76b1770`).
  - Old `S-` and `F-` IDs are retained and never reused (last story ID: `S-74`).
  - Technical items register: See [TECH-DEBT.md](TECH-DEBT.md).

---

## ⚡ Active Focus — Colour × Size Variants (D-20, D-21)

> [!warning] Card Flow Paused (2026-10-24)
> Every `S-` item below is **On hold**. Nothing is dropped: when work resumes, each item will be checked against the new variant model before returning to its prior status.

### Execution Plan & Order
1. **INV-1** (Colours) — `#done` *(Accepted 2026-10-31)*
2. **INV-2** (Stock per colour and size) — `#done` *(Accepted 2026-10-31)*
3. **Next Sequence:** `INV-8` ➔ `INV-9` ➔ `INV-3` ➔ `INV-4` ➔ `INV-5`
4. **Card Structure:** Each feature receives a UI card first (`INV-n-<slug>-ui.md`), followed by a development card (`INV-n-<slug>.md`).
5. **Shared UI Cards:** `INV-2`, `INV-8`, and `INV-9` share the same UI card but are accepted individually.
6. **Phase 7 Tech Allowance:** `F-43` · `F-46` · `F-52` · `F-53`

### Open Backlog Table

| ID | What it brings | Type | Status | Priority / Size |
| :--- | :--- | :--- | :--- | :--- |
| **INV-8** | Barcodes for every colour and size | Feature | `Ready` | Must / L |
| **INV-9** | Print barcode labels | Feature | `Ready` | Must / M |
| **INV-3** | Restock of finished products | Feature | `UI approved` | Must |
| **INV-4** | Clients as an entity | Feature | `UI approved` | Must |
| **INV-5** | Production progress page | Feature | `UI approved` | Must |
| **INV-7** | Bigger tap targets on Settings pages | Feature | `Idea` | Could / S–M |

> [!note] Technical Note on INV-6
> **INV-6** (picker-row swatch edge) joins a phase's tech allowance when proposed by the tech lead. It does not block the feature sequence above.

---

### Item Details

#### `INV-8` · Barcodes for every colour and size
- **Type:** Feature | **Priority:** Must | **Size:** L | **Status:** `Ready`
- **Cards:** [Development Card](stories/INV-8-barcodes.md) · [UI Card](stories/INV-2-product-sizes-colours-barcodes-ui.md) *(shared)*
- **Details:** 
  - Adds "Stock by colour" and "Barcodes" (with "4 missing" badge) tabs to Sizes & colours card.
  - Shows one EAN-13 barcode per name + colour + size.
  - "Generate" always prompts first and is all-or-nothing. Runs after `INV-2` (done).
  - **Rules (2026-10-31):** Variant SKU format = `PRODUCT_SKU-COLOUR_CODE-SIZE_LABEL` (e.g., `PRD-JKT-001-IND-S`). In-store numbers run per workshop from `2000000000015`.

#### `INV-9` · Print barcode labels
- **Type:** Feature | **Priority:** Must | **Size:** M | **Status:** `Ready`
- **Cards:** [Development Card](stories/INV-9-labels.md) · [UI Card](stories/INV-2-product-sizes-colours-barcodes-ui.md) *(shared)*
- **Details:** 
  - Adds "Print label" & "Print labels · Indigo" to Barcodes tab.
  - Supports $50 \times 30\text{ mm}$ labels or A4 sheets of 24.
  - Adds `Settings › Labels`. Runs after `INV-8`.

#### `INV-3` · Restock finished products
- **Type:** Feature | **Priority:** Must | **Status:** `UI approved`
- **Cards:** [UI Card](stories/INV-3-restock-finished-products-ui.md)
- **Details:** 
  - Adds Restocks page in sidebar + New restock modal (colour × size grid up to 50 products, scanning support).
  - Restock adds to shelf and never touches materials; can be undone as a whole.
  - Restock serves as the main action on the product page's Sizes & colours card.

#### `INV-4` · Clients as an entity
- **Type:** Feature | **Priority:** Must | **Status:** `UI approved`
- **Cards:** [UI Card](stories/INV-4-clients-ui.md)
- **Details:** 
  - B2C buyer focus: Clients page in sidebar with Source filter, plus individual client profiles.
  - Fields: Name, Phone (unique), City (all required), and Source (optional: Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in, Word of mouth, Other).
  - Integrated into New Order flow ("Existing client · New client").

#### `INV-5` · Production progress page
- **Type:** Feature | **Priority:** Must | **Status:** `UI approved`
- **Cards:** [UI Card](stories/INV-5-progress-ui.md)
- **Details:** 
  - Progress page in sidebar: Phase tiles, per-product colour × size table, phase-by-phase view (oldest first).
  - Configurable phases (`Settings › Production phases`).
  - QR work tickets per bundle cut; Scan page moves bundles forward.
  - *Open discussion:* Counting order lines per colour and size.

#### `INV-6` · Picker row swatch edge
- **Type:** Tech | **Status:** `Idea`
- **Details:** Fixes contrast ratio on hovered/active rows in light/dark mode (currently ~2.8:1, target 3:1). Join phase tech allowance when proposed by tech-lead.

#### `INV-7` · Bigger tap targets on Settings pages
- **Type:** Feature | **Priority:** Could | **Size:** S–M | **Status:** `Idea`
- **Details:** Increases button/field heights on phone/tablet from 32-36 px to 44 px. Related to `S-68` (order screen tap targets). Runs after `INV-5`.

> [!abstract] Unscheduled Idea
> **Production for the shelf:** A batch with no attached client moving through production phases, ending with "Put in stock". *(No ID assigned yet).*

---

## 🏛️ Decisions That Shape the Backlog

> [!summary] Core Operational Rules
> - **D-20 (2026-10-24):** Card flow paused. Open items placed on hold for variant rework (`INV-n` sequence).
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
