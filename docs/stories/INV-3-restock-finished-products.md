---
id: INV-3
title: Restock finished products
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
created: 2026-11-02
tags:
  - story
  - variants
---

# INV-3 · Restock finished products

> [!abstract] Description
> As a **workshop owner**, I want **to add the finished units that were made or arrived to the shelf, in their colours and sizes, and
> keep a record of each time I do it**, so that **I never add counts in my head and I can see, and undo, what came in, when and from
> where**. A batch sewn for stock, a box from a subcontractor with its delivery note, units a boutique sends back: each becomes one
> **restock** of one or several products, with a date, a reference, a note and who recorded it. **Restock adds; Update stock sets.**
> New: the **Restocks** page in the sidebar, the **New restock** page with a field to scan or type a barcode, the restock detail and
> **Undo restock**. Changed: the product page's Sizes & colours card gets a **Restock** button and a "Last restock" line. The pages
> are drawn in [[INV-3-restock-finished-products-ui]].
> Left out on purpose: materials and their own Restock (a restock of finished products never changes them), production that takes
> materials (INV-5), orders, sales, a cost or price on a restock, a history for Update stock, and scanning with the phone's camera.

> [!info] How it works today
> The only way to put finished units on the shelf is **Update stock** in the product page's Sizes & colours card, which *sets* each
> count. Its window already reads "Finished units on the shelf, counted per colour and size. Saving sets these counts; to add units
> that arrived, use Restock.", but there is no Restock yet. To add 8 Indigo M to the Denim Jacket you look up "Indigo M has 0", add 8
> in your head and type 8, colour by colour, size by size, then **Save** ("Stock updated."). Nothing records that 8 arrived, when or
> from where, and two people counting at once overwrite each other. The card's buttons are **Manage sizes & colours** and **Update
> stock**; the sidebar reads Dashboard, Materials, Products, Orders, Clients, Sales. Today the Denim Jacket (PRD-JKT-001, sizes S, M,
> L, XL) has:
>
> | Colour     | S | M | L | XL | Total |
> | ---------- | - | - | - | -- | ----- |
> | Black      | 1 | 0 | 2 | 1  | 4     |
> | Indigo     | 2 | 0 | 3 | 1  | 6     |
> | Light wash | 0 | 0 | 0 | 0  | 0     |
> | Total      | 3 | 0 | 5 | 2  | 10    |

## Acceptance criteria

The examples use the Denim Jacket above, the Denim Jeans 5-pocket (Indigo, sizes S to XL, nothing on the shelf), the Leather Tote
("One size", Camel with 4 on the shelf and Black) and the Classic White T-shirt. Today is 25 Oct 2026.

- [ ] **AC-1 · A restock adds to the shelf and is recorded.** A restock has a **Date** (today by default, not after today), a
  **Reference** (optional, up to 64 characters), a **Note** (optional, up to 255 characters), who recorded it and when, and the units
  it added to each colour and size of up to 50 products. Saving adds those units to what is on the shelf, all at once or not at all.
  Given the Denim Jacket above, restocking Indigo S 4 · M 8 · L 6 · XL 2 and Black M 4 (24 units) leaves Black 1 · 4 · 2 · 1 = 8 and
  Indigo 6 · 8 · 9 · 3 = 26, 34 on the shelf.
  - A restock never changes materials, "Can produce now", the unit cost, the margin, sales, orders or the Inventory value.
  - Draft and archived products can be restocked like active ones.
  - Update stock still sets the counts and writes no record; if someone saves Update stock after a restock was saved in between, that
    save wins, as today.
  - No colour and size can go over 1,000,000 units on the shelf. The restock belongs to the workshop like every other record.
- [ ] **AC-2 · Restocks page.** The sidebar reads Dashboard, Materials, Products, **Restocks** (package-plus icon), Orders, Clients,
  Sales. The page **Restocks**, "Finished units that were made or arrived, added to the shelf in their colours and sizes.", has the
  main button **New restock** (plus sign), the search "Search reference, note or product" and the list **Product** ("All products",
  then the products by name), both kept in the page address. The table, 25 a page, newest first, "Showing 1–25 of 48": **Date** ·
  **Reference** ("—" when empty) · **Products** · **Units** (number font, right-aligned) · **By** · the button **Open**, named "Open
  restock of 25 Oct 2026, BL-0412"; a click on the row opens the same. These restocks show as:

  | Date        | Reference            | Products                                  | Units | By    |
  | ----------- | -------------------- | ----------------------------------------- | ----- | ----- |
  | 25 Oct 2026 | BL-0412              | Denim Jacket 24 · Denim Jeans 5-pocket 10 | 34    | Admin |
  | 22 Oct 2026 | —                    | Leather Tote 6                            | 6     | Sami  |
  | 18 Oct 2026 | Return Boutique Lina | Classic White T-shirt 12                  | 12    | Admin |

  - More than two products: "Denim Jacket 24 · Leather Tote 6 and 2 more". The 18 Oct restock is undone: the grey badge **Undone**
    after its reference, its units in grey. Searching "BL-04" or choosing Product "Leather Tote" lists only its restock.
  - **Empty:** "No restocks yet" · "Each time finished units are made or arrive, add them here. They go on the shelf in their colours
    and sizes." · **New restock**. **No match:** "No restock matches "BL-9"." · **Clear filters**. **Loading:** the header, filters and
    table head at once, 5 placeholder rows. **Error:** "Couldn't load the restocks." · **Try again**. A 64-character reference and a
    60-character product name are cut with "…"; "1,000,000" units fit.
  - **Phone (375 px):** **New restock** full width, the search, **Filters** ("Filters · 1" when a product is chosen), then one card per
    restock: the date and reference, the products line, "34 units" in bold, "by Admin", the **Undone** badge; the card opens the
    detail. At 320 px nothing scrolls sideways.
- [ ] **AC-3 · New restock page.** **New restock** opens the page **New restock**, "Add finished units that were made or arrived. They
  are added to what is on the shelf.", with the cards **Details** (Date, **Reference (optional)** with the hint "Delivery note, batch
  or who made them", **Note (optional)**), **Scan or type a barcode** (AC-4) and **Products**, which first reads "Add the products
  that were made or arrived, or scan their labels." with **Add product**.
  - **Add product** opens a picker, "Search products": the name, the SKU in grey and the status badge of a draft or archived product.
    A product already on the restock shows a check, and choosing it moves the focus to its line. A 51st product is refused: "A
    restock can hold up to 50 products."
  - **A product's line:** the name, its SKU in the number font, "Sizes S, M, L, XL", "Adding **24** units" and ✕ "Remove Denim Jacket
    from this restock". The table "Units to add · Denim Jacket": **Colour**, one column per size in size order, **Adding** ("—" when
    none); one row per colour in the order of Settings › Colours. Each field starts empty, with "now 2" in grey under it (what that
    colour and size has on the shelf); a typed field gets the Denim border. A "One size" product has **Colour** · **Units to add** and
    no Adding column (Leather Tote: Camel "now 4", Black "now 0"). On a laptop under 1100 px a shoe's 11 sizes scroll inside the card,
    never the page.
  - **Totals:** with the typing of AC-1 and Denim Jeans 5-pocket Indigo M 6 · L 4, the lines read "Adding 24 units" and "Adding 10
    units", the grey band "Total on this restock" **34 units** · "2 products", and the button **Restock 34 units** (with nothing typed,
    **Restock**). Under the band: "Adds to what is on the shelf. Doesn't change materials, "Can produce now" or sales. To set a count
    after a stock check, use Update stock on the product's page."
  - **Saving** shows a spinner on **Restock 34 units** and keeps its label, then the toast "34 units restocked." ("1 unit restocked.")
    and the Restocks list with the new restock on top. One wrong field saves nothing, and everything typed stays.
  - **Errors** (a variant is named "Indigo M", "Black, size 38", or "Camel" for a "One size" product): a red border and under the
    line's table "Indigo M: Enter a whole number from 0 to 1,000,000." or "Indigo M: This would put more than 1,000,000 units on the
    shelf."; at the top "Check Denim Jacket: 1 field can't be saved.", the focus on the first wrong field. A line with nothing typed:
    "Enter at least one unit of Denim Jacket, or remove it from this restock."; nothing typed at all, under the band: "Enter at least
    one unit to restock.". Details: "The date can't be in the future." · "Keep the reference to 64 characters or fewer." · "Keep the
    note to 255 characters or fewer.". Changed meanwhile: "The sizes or colours of Denim Jacket have changed. Enter its units again."
    and the line shows the new ones. Deleted meanwhile: "Denim Jacket was deleted. Remove it from this restock.". Save failed:
    "Couldn't save the restock. Try again.".
  - **Cancel** leaves at once when nothing is typed; otherwise it asks "Discard this restock?" · "The 34 units you entered aren't
    added to the shelf." · **Discard restock** · **Keep editing**.
  - **Phone (375 px):** one column; Details one under another; the scan field full width with **Add 1** under it; on each line one
    group per colour (its name and "Adding 20"), the size fields four to a row with "now 2" under each; **Add product** full width; a
    bar fixed at the bottom with "Total 34 units" and **Restock 34 units**, which never hides the field with the focus; **Cancel** at
    the end of the form. Confirms are full width, the confirm button first.
- [ ] **AC-4 · Scan or type a barcode.** The card **Scan or type a barcode** has the field "Scan or type a barcode" (barcode icon), the
  outline button **Add 1** and the hint "Each scan adds one unit to its colour and size. A barcode scanner works like typing."
  Scanning a label, or typing its 13 digits and pressing Enter or **Add 1**, adds one unit to that colour and size, and the focus stays
  in the field for the next label.
  - Scanning Denim Jacket Indigo M when 7 are typed there: a green line with a check "Added 1 · Denim Jacket · Indigo · M · 8 on this
    restock", and the Indigo M field ringed in Denim until the next scan.
  - A product not yet on the restock gets its line at the end with that unit: "Added 1 · Leather Tote · Camel · 1 on this restock".
  - **Shared barcode:** "2000000000022 is Indigo M on 2 products named Denim Jacket. Which one?" · **PRD-JKT-001** · **PRD-JKT-002**;
    if one of them is already on this restock, the unit goes there and nothing is asked.
  - Red lines: "No product has the barcode 2000000000992. Check the label, or enter the units below." · "A barcode has 13 digits.
    Check the number." · "2000000000077 is Light wash L of Denim Jacket, which no longer comes in Light wash." · "Couldn't look up the
    barcode. Try again.". None of them changes the units typed.
- [ ] **AC-5 · Restock detail.** Opening a restock shows it in a panel on the right (full screen on a phone), the list still in view;
  the open restock is kept in the page address, so a link opens it. Title **Restock · 25 Oct 2026**; "BL-0412 · recorded by Admin on
  25 Oct 2026 at 14:32"; "**34 units** · 2 products"; the note in a grey box if any. One block per product: its name as a link to
  its page and its SKU, then **Colour** · S · M · L · XL · **Total** with only the colours that got units (Denim Jacket: Black 0 · 4
  · 0 · 0 = 4, Indigo 4 · 8 · 6 · 2 = 20, Total 4 · 12 · 6 · 2 = 24; the Total row only with two colours or more; zeros grey). The
  footer: **Undo restock** (outline, red text) and **Close**.
  - **Loading:** the title and figures at once, the product blocks as placeholders. **Error:** "Couldn't load this restock." · **Try
    again**. A deleted product's block reads "Denim Jacket (deleted)", with no link.
- [ ] **AC-6 · Undo a restock.** **Undo restock** asks "Undo this restock?" · "Takes the 34 units of 25 Oct 2026 (BL-0412) back off the
  shelf: Denim Jacket 24, Denim Jeans 5-pocket 10. The restock stays in the list, marked Undone." · **Undo restock** (red, with a
  spinner while it works) · **Keep restock** (without a reference: "Takes the 6 units of 22 Oct 2026 back off the shelf: Leather Tote
  6. …"). Confirming takes every unit it added back off the shelf at once (the Denim Jacket is back to the 10 above), shows "Restock
  undone.", and the restock reads **Undone** in the list and in the panel: "Undone by Sami on 26 Oct 2026. Its 34 units were taken
  back off the shelf.", with no Undo button.
  - It works at any time, as long as every colour and size it added still has at least that many on the shelf. Otherwise **Undo
    restock** can't be pressed, can still be reached with Tab, and the reason shows above it and in its name ("This restock can't
    be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update
    stock instead."). If the stock changed while the confirm was open, the confirm shows the same reason in red and nothing changes.
  - A deleted product's units are left out (its stock went with it); the other products' units are taken back.
  - An undone restock never counts as a product's last restock (AC-7).
- [ ] **AC-7 · Restock from the product page.** The Sizes & colours card's buttons read **Manage sizes & colours** (outline) · **Update
  stock** (outline) · **Restock** (main, plus sign); on a phone, full width, 44 px, one under the other, Restock first. **Restock**
  opens New restock with the Denim Jacket's line in it and the focus in its first field (Black S); Cancel or saving comes back to the
  product page, and saving shows "24 units of Denim Jacket restocked." with the new counts (Black 8, Indigo 26, total 34).
  - Under the stock table, after "Grey zeros are variants with nothing on the shelf.": "Last restock: 25 Oct 2026, 24 units · **See
    the restocks of Denim Jacket**", a link to Restocks with Product "Denim Jacket" chosen. Never restocked, or only undone restocks:
    "Not restocked yet.", with no link. Loading and error as the card today ("Couldn't load the stock and barcodes." · **Try again**).

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 11 commits
> Tech-lead (main session standing in, at the human's request) · 2026-11-02 · approved 2026-11-02 · delivered 2026-11-02 (on PI-DEV, waiting for acceptance)
> Why complex: two new tables, multi-row writes on the finished stock under row locks (add, undo), a new business rule (undo only
> while every variant still holds what it added), five new routes (three of them JSON) and a new contract on two pages.

### In short
A restock becomes a record of its own: one row for the restock (date, reference, note, who, its total, and when and by whom it was
undone) and one row per product, colour and size it added. Saving locks every product on it, checks each one still exists and still
has the colours and sizes the screen showed, refuses a count that would go over 1,000,000, then adds the units to the finished stock and
writes the record, all in one transaction. Undo locks the same products, checks every variant still holds at least what the restock
added, takes the units back off and marks the restock undone; nothing is ever deleted. Update stock, materials, costing, sales and
orders are untouched. The screens read through small read-only routes: a product picker, a product's grid with its "now" counts, and
a barcode lookup for the scan field. The product page gets its "Last restock" line in the same deferred group as the stock, so one
"Try again" still reloads the whole card.

### Steps

| Step        | What it does                                          | Proves           | Lane         | Who      | Size       |
| ----------- | ----------------------------------------------------- | ---------------- | ------------ | -------- | ---------- |
| **INV-3-a** | The restock tables, their models and policy           | AC-1             | CRITICAL     | Backend  | ~240 lines |
| **INV-3-b** | Save a restock: add to the shelf, all or nothing      | AC-1, AC-3       | CRITICAL     | Backend  | ~380 lines |
| **INV-3-c** | Undo a restock, or say why it can't be                | AC-6             | CRITICAL     | Backend  | ~240 lines |
| **INV-3-d** | Restocks list, detail, New restock page, picker, grid | AC-2, AC-3, AC-5 | CRITICAL     | Backend  | ~380 lines |
| **INV-3-e** | Barcode lookup for the scan field                     | AC-4             | CRITICAL     | Backend  | ~200 lines |
| **INV-3-f** | Last restock on the product page, back to it on save  | AC-7             | NON-CRITICAL | Backend  | ~120 lines |
| **INV-3-g** | The Restocks page and the sidebar entry               | AC-2             | NON-CRITICAL | Frontend | ~320 lines |
| **INV-3-h** | The restock panel and Undo                            | AC-5, AC-6       | CRITICAL     | Frontend | ~300 lines |
| **INV-3-i** | The New restock page                                  | AC-3             | CRITICAL     | Frontend | ~400 lines |
| **INV-3-j** | Scan or type a barcode                                | AC-4             | CRITICAL     | Frontend | ~260 lines |
| **INV-3-k** | Restock on the product page                           | AC-7             | NON-CRITICAL | Frontend | ~140 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
run their PostgreSQL tests; after the last wave the whole tree runs `composer test:pgsql` and `php artisan test tests/Browser` (after
`npm run build`).

#### INV-3-a · The restock tables, their models and policy
**Commit:** `feat(restocks): restocks of finished products and their lines (INV-3-a)`

- Migration `product_restocks`: `workshop_id`, `restocked_on` (date), `reference` (64, nullable), `note` (255, nullable), `units`
  (unsigned integer, the restock's total, written once), `user_id` (who recorded it, `nullOnDelete`), `undone_at` (nullable),
  `undone_by` (FK users, nullable, `nullOnDelete`), timestamps (`created_at` is "recorded … at 14:32"). Index (`workshop_id`,
  `restocked_on`, `id`) for the newest-first list. PostgreSQL only: `unique(workshop_id, id)`, CHECK `units >= 1`.
- Same migration, `product_restock_lines`: `workshop_id`, `product_restock_id` (`cascadeOnDelete`; nothing deletes a restock),
  `product_id` (`restrictOnDelete`; products are soft-deleted), `colour_id`, `size_id` (`restrictOnDelete`), `position` (unsigned
  small integer, the product's place on the restock), `units` (unsigned integer). Unique (`product_restock_id`, `product_id`,
  `colour_id`, `size_id`); index (`product_id`, `product_restock_id`) for the product filter and "Last restock". Only variants that got
  units have a row. PostgreSQL only: composite foreign constraints (`workshop_id`, `product_restock_id`) → product_restocks and (`workshop_id`,
  `product_id`) → products, CHECK `units BETWEEN 1 AND 1000000`.
- Models `ProductRestock` (`BelongsToWorkshop`, `lines()`, `recorder()`, `undoer()`, scopes `active()` (not undone), `newestFirst()`)
  and `ProductRestockLine` (`BelongsToWorkshop`, `product()` with trashed, `colour()`, `size()`); `ProductRestockPolicy` (any member of
  the workshop; another workshop is a 404 by the scope); factories.
- The tech-lead writes in this commit: ADR-0022 "Restocks of finished products are recorded and undone as a whole",
  `docs/ARCHITECTURE.md` (both tables, `product_size_stocks` gains its second writer and third, the routes) and the business rules file
  ("Restock" of finished products next to the material Restock in the glossary).

**Tests**
- 1 PostgreSQL schema test: a line pointing at another workshop's restock or product is refused; units 0 and 1,000,001 are refused;
  a second line for the same restock, product, colour and size is refused.
- The workshop arch test lists both models; 1 policy dataset (guest · member · other workshop 404).

> [!example]- Files (11)
> - `.claude/skills/invory-costing-domain/SKILL.md`, `docs/adr/0022-restocks-of-finished-products.md` (new), `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Models/{ProductRestock,ProductRestockLine}.php` (new), `app/Policies/ProductRestockPolicy.php` (new)
> - `database/migrations/2026_11_03_100000_create_product_restocks_tables.php` (new)
> - `database/factories/{ProductRestock,ProductRestockLine}Factory.php` (new)
> - `tests/Feature/Restocks/RestockSchemaTest.php` (new), `tests/Arch/WorkshopScopeTest.php`

#### INV-3-b · Save a restock: add to the shelf, all or nothing
**Commit:** `feat(restocks): a restock adds its units to the shelf, all or nothing (INV-3-b)`

- `StoreRestockRequest`: `restocked_on` a date ≤ today (the workshop's today, as `app.today`), `reference` ≤ 64, `note` ≤ 255,
  `products` 1 to 50 entries, each `product_id` a product of the workshop (deleted ones included, through `WorkshopRule`, so the Action
  can name it) and `units` colour id → size id → empty or a whole number 0 to 1,000,000; `from_product` nullable. Every message is the
  card's: field errors on `products.{i}.units.{colour}.{size}` start with the variant's name ("Indigo M", "Black, size 38", "Camel"),
  a line with nothing typed on `products.{i}`, nothing typed at all and the 51st product on `products`.
- `App\Actions\Restocks\RecordRestock`: one transaction. Locks the products in id order (`lockForUpdate`, with trashed: the lock
  `UpdateFinishedStock` and `DeleteProduct` take). For each line, in order: deleted → "Denim Jacket was deleted. Remove it from this
  restock."; colours or sizes not exactly the product's (reuse `UpdateFinishedStock::matches`) → "The sizes or colours of Denim Jacket
  have changed. Enter its units again."; then every cell where stock + added > 1,000,000 → "Indigo M: This would put more than
  1,000,000 units on the shelf.". Any refusal throws one `ValidationException` with every message and writes nothing. Then it adds the
  units to `product_size_stocks` (update or insert, whole integers), writes the restock with its total and its lines (non-zero cells
  only, `position` = the product's place), and returns it.
- `App\Services\Restocks\RestockMessages`: every text and toast of the restock pages (as `OrderMessages`, `ClientMessages`).
- `RestockController@store` → `restocks.index` with "34 units restocked." ("1 unit restocked.").

**Tests**
- AC-1's example end to end: Denim Jacket 10 → 34 with the exact rows; Denim Jeans 5-pocket gets new rows; the restock and its 6
  lines; materials, stock movements, sales, orders and the product rows unchanged (`updated_at` too).
- Datasets: each message with its key; nothing written on any refusal (one bad product among two); a product deleted and a colour
  taken off between the page and the save; 999,990 + 10 accepted, + 11 refused; a draft and an archived product accepted; another
  workshop's product refused.
- 1 race test: Update stock and a restock on the same product, the second save wins as the card says.

> [!example]- Files (8)
> - `app/Actions/Restocks/RecordRestock.php` (new), `app/Services/Restocks/RestockMessages.php` (new)
> - `app/Http/Requests/Restocks/StoreRestockRequest.php` (new), `app/Http/Controllers/RestockController.php` (new)
> - `routes/web.php`
> - `tests/Feature/Restocks/RecordRestockTest.php` (new), `tests/Feature/Restocks/StoreRestockRequestTest.php` (new)

#### INV-3-c · Undo a restock, or say why it can't be
**Commit:** `feat(restocks): undo a restock as a whole while the shelf still holds its units (INV-3-c)`

- `App\Services\Restocks\UndoCheck`: given a restock and the current stock rows, the first variant short of what it added (products
  by position, colours in list order, sizes in size order), or none; lines of deleted products are skipped. Read without locks for the
  panel (`undo_refusal`), again under the locks by the Action.
- `App\Actions\Restocks\UndoRestock`: locks the restock row, refuses an undone one, locks its non-deleted products in id order,
  runs `UndoCheck`, takes each line's units off (a row reaching 0 is deleted, as everywhere), sets `undone_at` and `undone_by`.
  A refusal is a `ValidationException` on `undo` with "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the
  shelf, fewer than the 8 it added. Set the right count with Update stock instead."
- `RestockUndoController@store` (`POST restocks/{restock}/undo`, policy `update`) → back with "Restock undone.". Its own controller:
  Laravel's arch preset allows only resource methods on a controller.

**Tests**
- The 25 Oct restock undone: Denim Jacket back to 10, Denim Jeans 5-pocket back to no rows, the restock marked with who and when.
- Refused: Indigo M at 2 after an Update stock; a colour taken off; undone twice; nothing changes on any refusal.
- A deleted product's lines skipped, the other product's units taken back. Another workshop's restock is a 404.

> [!example]- Files (6)
> - `app/Actions/Restocks/UndoRestock.php` (new), `app/Services/Restocks/UndoCheck.php` (new)
> - `app/Http/Controllers/RestockUndoController.php` (new), `routes/web.php`
> - `tests/Feature/Restocks/UndoRestockTest.php` (new; it also covers `UndoCheck`)

#### INV-3-d · Restocks list, detail, New restock page, picker, grid
**Commit:** `feat(restocks): the Restocks list and detail, the New restock page and its product picker (INV-3-d)`

- `App\Services\Restocks\RestockBook`: the list (search on reference, note or a line's product name, deleted products included;
  `product` filter; 25 a page, `restocked_on` then id, newest first), each with its first two products and their units, the count of
  the others and who recorded it, in two queries for the page; the detail (blocks per product in position order, only the colours with
  units, totals, who and when, `undo_refusal` through `UndoCheck`); `hasRestocks()`.
- `RestockController@index` (`restocks`): props `restocks`, `filters` (`search`, `product`, `restock`), `product_filter`,
  `has_restocks`, and `restock` (the detail, a closure prop that is null without `?restock=`, so the panel opens from a link and
  reloads alone). `RestockController@create` (`restocks/create`, `?product=`): `preset` (that product's line) or null.
- JSON (ADR-0009 rule 5, as `materials.options`): `restocks.products.options` (`search`, `limit` ≤ 50, `include`; every non-deleted
  product, any status, A → Z, name, SKU, status) and `restocks.products.line` (`restocks/products/{product}`: the product, its sizes,
  whether it is "One size", and its finished stock through `FinishedStock`, for "now 2").
- Resources and the `domain.ts` types of the Contract below, in the same commit; one-line stub pages `restocks/index` and
  `restocks/create` (as INV-4-c did), replaced by INV-3-g and INV-3-i.

**Tests**
- The three example rows as in AC-2, "and 2 more", the Undone flag; search "BL-04" and the product filter; "No restocks yet" vs no
  match (`has_restocks`); the detail of AC-5 (Black 0 · 4 · 0 · 0 = 4, Indigo 4 · 8 · 6 · 2 = 20, totals); a deleted product's block;
  `undo_refusal` set and null.
- The picker: draft and archived products with their status, deleted ones absent, `include` brings the chosen back. The line of the
  Leather Tote ("One size", Camel now 4). Another workshop's product and restock are 404s; queries per page stay flat (25 restocks).

> [!example]- Files (11)
> - `app/Services/Restocks/RestockBook.php` (new), `app/Http/Controllers/RestockController.php`
> - `app/Http/Controllers/RestockProductController.php` (new), `app/Http/Requests/Restocks/{IndexRestockRequest,RestockProductOptionsRequest}.php` (new)
> - `app/Http/Resources/{RestockSummaryResource,RestockDetailResource,RestockLineResource,RestockProductRefResource}.php` (new)
> - `routes/web.php`, `resources/js/types/domain.ts`
> - `tests/Feature/Restocks/{RestocksPageTest,RestockProductsTest}.php` (new)

#### INV-3-e · Barcode lookup for the scan field
**Commit:** `feat(restocks): look up a scanned barcode's product, colour and size (INV-3-e)`

- `App\Services\Restocks\RestockBarcodeLookup`: the typed text without spaces; not 13 digits → "A barcode has 13 digits. Check the
  number."; no barcode with that number in the workshop → "No product has the barcode 2000000000992. Check the label, or enter the
  units below."; else the non-deleted products (any status) whose name the number stands for (`barcode_names`) and whose size list has
  its size: those with its colour are the matches, by SKU; none with it but one without → "2000000000077 is Light wash L of Denim
  Jacket, which no longer comes in Light wash."; none whose size list has its size but one of that name → "2000000000088 is Indigo
  XXL of Denim Jacket, which no longer comes in size XXL." (the human's answer, 2026-11-02); none at all → the "No product has the
  barcode" message. Two queries.
- `restocks.barcode` (`GET restocks/barcode?number=`): JSON `RestockBarcodeLookup` with the matches as `RestockLine`s, so a scan of a
  product not yet on the restock adds its line without a second request.

**Tests**
- Dataset: Indigo M of the Denim Jacket (one match, its colour and size); a shared number with PRD-JKT-001 and PRD-JKT-002; an
  unknown number; "12345"; Light wash taken off; the size gone after a category change; an archived product found; a deleted product not found; another workshop's number
  unknown.

> [!example]- Files (5)
> - `app/Services/Restocks/RestockBarcodeLookup.php` (new), `app/Http/Controllers/RestockBarcodeController.php` (new)
> - `routes/web.php`, `resources/js/types/domain.ts`
> - `tests/Feature/Restocks/RestockBarcodeLookupTest.php` (new)

#### INV-3-f · Last restock on the product page, back to it on save
**Commit:** `feat(restocks): the product page shows its last restock; a restock started there comes back to it (INV-3-f)`

- `ProductController@show`: `last_restock` (`restocked_on` and the product's units on it, from the newest restock not undone, by
  date then id; null when none), deferred in the group `stock` with `finished_stock` and `barcodes`, rescued.
- `RestockController@store` with `from_product` → `products.show` with "24 units of Denim Jacket restocked." when that product is the
  only one on the restock, else the general "34 units restocked." (the human's answer, 2026-11-02).

**Tests**
- Last restock 25 Oct 2026, 24 units; an undone newer restock ignored; null when never restocked; the redirect and both toasts.

> [!example]- Files (5)
> - `app/Http/Controllers/ProductController.php`, `app/Services/Restocks/RestockBook.php`, `app/Http/Controllers/RestockController.php`
> - `resources/js/types/domain.ts`, `tests/Feature/Restocks/LastRestockTest.php` (new)

#### INV-3-g · The Restocks page and the sidebar entry
**Commit:** `feat(restocks): the Restocks page in the sidebar (INV-3-g)`

- `restocks/index`: header, **New restock**, the search and the Product filter (a combobox on `restocks.products.options`) kept in
  the address, the table with AC-2's columns and the Undone badge, **Open** and the row click (they set `?restock=`), the empty, no
  match, loading and error states, cut text, and the phone cards and **Filters** panel. The sidebar gains **Restocks** (package-plus)
  after Products; breadcrumbs.

**Tests**
- Vitest: the three rows; "and 2 more"; Undone; each state's words and **Clear filters**; the filters and `restock` in the address;
  the phone cards; the sidebar order.

> [!example]- Files (6)
> - `resources/js/pages/restocks/index.tsx` (new), `resources/js/components/restocks/{restocks-table,restocks-filters}.tsx` (new)
> - `resources/js/components/app-sidebar.tsx`, `resources/js/pages/restocks/index.test.tsx` (new), `resources/js/test/fixtures/restocks.ts` (new)

#### INV-3-h · The restock panel and Undo
**Commit:** `feat(restocks): the restock panel and Undo restock (INV-3-h)`

- A right-hand sheet (full screen on a phone) driven by `?restock=`: title, the recorded line, figures, note, product blocks (deleted
  products without a link), footer **Undo restock** and **Close**; loading and error with **Try again** (a partial reload of
  `restock`). The confirm with both wordings (with and without a reference), the spinner, the refusal in red from the `undo` error; the
  disabled-with-reason state (`aria-disabled`, reachable with Tab, the reason in its name); the undone state.

**Tests**
- Vitest: AC-5's panel; the confirm's two texts; the refusal; the disabled button's name; the undone line; Close clears `restock`.

> [!example]- Files (4)
> - `resources/js/components/restocks/{restock-panel,undo-restock-dialog}.tsx` (new), `resources/js/pages/restocks/index.tsx`
> - `resources/js/components/restocks/restock-panel.test.tsx` (new)

#### INV-3-i · The New restock page
**Commit:** `feat(restocks): the New restock page (INV-3-i)`

- `restocks/create`: Details, the Products section, one card per product (its grid reusing the size fields of `finished-stock-fields`
  with "now 2" under each; the "One size" table; sideways scroll inside the card), **Add product** (picker on
  `restocks.products.options`, then `restocks.products.line`; a check on those already there; the 50 limit), the totals band and the
  button's label (summed from the typed whole numbers, as Update stock's `typedTotal` does), the note, Save with the spinner, the
  server's errors placed under their line and at the top with the focus on the first, Cancel and its confirm, the preset line and focus
  from a product page, and the phone layout with the fixed bottom bar.
- 1 browser golden path: New restock with two products → the list → open it → Undo restock.

**Tests**
- Vitest: AC-3's totals and button labels; each message's place; the 51st product refused; Cancel with and without units; the
  preset's focus; the phone bar.

> [!example]- Files (6)
> - `resources/js/pages/restocks/create.tsx` (new), `resources/js/components/restocks/{restock-line,restock-product-picker}.tsx` (new)
> - `resources/js/pages/restocks/create.test.tsx` (new), `tests/Browser/RestockGoldenPathTest.php` (new)
> - `resources/js/components/products/finished-stock-fields.tsx` (the field without the "was" line)

#### INV-3-j · Scan or type a barcode
**Commit:** `feat(restocks): scan or type a barcode on New restock (INV-3-j)`

- The scan card: the field (Enter or **Add 1** looks up `restocks.barcode`), the focus kept in it, the green result line and the
  Denim ring on the field until the next scan, a new product's line added at the end with its unit, the shared-barcode choice (skipped
  when one of them is already on the restock), the red lines from the server's message, and "Couldn't look up the barcode. Try again."
  when the request fails. A scan never changes other typed units.

**Tests**
- Vitest with the lookup mocked: each AC-4 line; 7 → 8 on Indigo M; Leather Tote added at the end; the choice and the skip; focus.

> [!example]- Files (4)
> - `resources/js/components/restocks/{scan-field,use-barcode-lookup}.tsx` (new), `resources/js/pages/restocks/create.tsx`
> - `resources/js/components/restocks/scan-field.test.tsx` (new)

#### INV-3-k · Restock on the product page
**Commit:** `feat(restocks): Restock on the product's Sizes & colours card (INV-3-k)`

- Sizes & colours card: **Restock** (main) after **Update stock** (now outline), to `restocks.create?product=`; the "Last restock"
  line and its link to `restocks.index?product=`, "Not restocked yet."; the three buttons full width on a phone, Restock first.

**Tests**
- Vitest: the buttons' order and kinds; both lines; the link's address; the phone order.

> [!example]- Files (3)
> - `resources/js/components/products/sizes-colours-card.tsx`, `resources/js/pages/products/show.tsx`
> - `resources/js/components/products/sizes-colours-card.test.tsx`

### Contract (frozen before dispatch)
```ts
export type RestockProductRef = { id: number; name: string; sku: string; status: ProductStatus; deleted: boolean };
export type RestockLine = { product: RestockProductRef; sizes: Size[]; one_size: boolean; stock: FinishedStock }; // "now 2"
export type RestockSummary = {
    id: number; restocked_on: string; reference: string | null; units: number; // 'YYYY-MM-DD'
    products: { name: string; units: number }[]; more_products: number;         // the first two, by position
    recorded_by: string | null; undone: boolean;
};
export type RestockBlock = {
    product: RestockProductRef; sizes: Size[]; one_size: boolean; units: number;
    rows: { colour: Colour; units: number[]; total: number }[];                 // colours with units only, list order
    totals: number[] | null;                                                    // per size, only with two colours or more
};
export type RestockDetail = RestockSummary & {
    note: string | null; recorded_at: string; products_count: number;          // "2026-10-25T14:32", wall-clock time
    undone_at: string | null; undone_by: string | null;
    blocks: RestockBlock[]; undo_refusal: string | null;
};
export type RestockFilters = { search: string | null; product: number | null; restock: number | null };
export type RestocksIndexProps = { restocks: Paginated<RestockSummary>; filters: RestockFilters;
    product_filter: RestockProductRef | null; has_restocks: boolean; restock: RestockDetail | null };
export type RestockCreateProps = { preset: RestockLine | null };
export type RestockBarcodeLookup =
    | { result: 'found'; colour_id: number; size_id: number; matches: RestockLine[] } // by SKU
    | { result: 'invalid' | 'unknown' | 'colour_gone' | 'size_gone'; message: string };
export type NewRestockFormData = { restocked_on: string; reference: string; note: string; from_product: number | null;
    products: { product_id: number; units: Record<number, Record<number, string>> }[] };
// Changed
ProductShowProps += { last_restock?: { restocked_on: string; units: number } | null }; // deferred, group 'stock', rescued
```
Errors: `restocked_on`, `reference`, `note`, `products`, `products.{i}` (nothing typed), `products.{i}.product` (deleted or
changed), `products.{i}.units.{colour}.{size}` (New restock) · `undo`
(Undo). Routes: `restocks.index` GET restocks · `restocks.create` GET restocks/create · `restocks.store` POST restocks ·
`restocks.undo` POST restocks/{restock}/undo · `restocks.products.options` GET restocks/products/options (JSON) ·
`restocks.products.line` GET restocks/products/{product} (JSON) · `restocks.barcode` GET restocks/barcode (JSON). `{restock}` and
`{product}` are route-model bound with the digit constraint. `RestockFormData` already names the material restock, hence
`NewRestockFormData`.

### Decisions
- **A record per restock, lines per variant, and the stock still in `product_size_stocks`.** The shelf count stays one row per
  variant, read by `FinishedStock` as today; the restock tables are history, not a second count. Rejected: a stock ledger summed on
  read (Update stock writes no history, so a ledger could never add up).
- **The total is stored on the restock.** A restock is never edited, so `units` never goes stale, and the list needs no sum per row.
- **Every writer locks the products first, in id order** (Record, Undo, Update stock, Delete product), so a restock and an Update
  stock on the same product run one after the other and the later one wins, as the card says.
- **Undo is a flag, not a delete**, and is checked twice: without locks for the panel's reason, under the locks for the write.
- **Whole units as PHP integers**, as `FinishedStock` and Update stock already do: finished units are counts, never money or
  material quantities, so they don't go through `BigDecimal` or `App\Domain\Costing`.
- **Picker, grid and barcode lookup are JSON routes** (ADR-0009 rule 5), as the material and client pickers.
- **The page adds the typed numbers for "Adding 24 units"**, as Update stock does today; the server re-checks everything on save.
- **The human's answers, 2026-11-02:** a restock started on a product page with other products added comes back to that page with the
  general toast; "By" (list and detail) shows "—" when the person who recorded or undid it has deleted their account; a scanned
  barcode whose size the product no longer has gets its own message, like a colour taken off.

### Delivered differently from the plan
- **The product picker came with INV-3-g**, not INV-3-i: the Restocks page's Product filter is the same picker (`filter` mode) as
  New restock's **Add product** (`add` mode).
- **A product deleted or changed since the page is refused on `products.{i}.product`** (shown at the top of the page, and its line is
  fetched again with its new colours and sizes); a product with nothing typed stays on `products.{i}` (under its table).
- **New restock has its own fields** (`restock-line.tsx`) instead of reusing `finished-stock-fields.tsx`, which keeps its "was N".
- **The Restocks list with a product chosen and no restock** reads "No restock of Denim Jacket yet." (the card only words the
  search's no-match).
- **The phone layouts are chosen with `useIsMobile`**, so the DOM order is the visual order (Restock first on a phone).
- **The browser golden path (`tests/Browser/RestockGoldenPathTest.php`) is written but not committed**: on this machine every browser
  test stops with "Playwright is outdated", the existing ones included.
- **Seven migration tests** roll the restock tables back before `colours`, `sizes` or `products` (`9b184aa`), as INV-4 did for clients.

### Order
Wave 1 (backend, one after another, they share `routes/web.php`, the controller and `domain.ts`): INV-3-a → INV-3-b → INV-3-c →
INV-3-d → INV-3-e → INV-3-f. Wave 2 (frontend, after wave 1's types land): INV-3-g → INV-3-h (same page), then INV-3-i → INV-3-j
(same page), INV-3-k in parallel with either pair (disjoint files). Merge order is the step order.

### Risks
- **A restock and Update stock on one product at once** → both take the product row lock; the race test in INV-3-b proves the order.
- **Two restocks touching the same products in a different order** → products are always locked in id order, so no deadlock.
- **A colour taken off or a category changed after a restock** → the stock is then 0 (both need it), so Undo is refused with the
  card's reason; the lines keep their colour and size ids (`restrictOnDelete`; colours and sizes are never deleted).
- **CHECKs and composite keys only on PostgreSQL** → the Actions enforce the same rules; the parity suite proves the database side.
- **The scan field and the grid share the typed state** → INV-3-j runs after INV-3-i, on the same page, with its own tests.
