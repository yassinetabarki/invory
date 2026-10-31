---
id: INV-8
title: Barcodes for every colour and size
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
created: 2026-10-31
tags:
  - story
  - variants
---

# INV-8 · Barcodes for every colour and size

> [!abstract] Description
> As a **workshop owner**, I want **every colour and size of a product to have its own barcode, made by Invory**, so that **each unit
> can be told apart and counted by a scan later**. One barcode stands for one name + colour + size, so every product with the same
> name, colour and size shares it. Barcodes are EAN-13 numbers from the workshop's own in-store range. This is the second of three
> cards that share one UI card: [[INV-2-product-sizes-colours-barcodes-ui]]; it builds on the stock card INV-2.
> Left out on purpose: printing labels and Settings › Labels (INV-9); scanning; orders, sales and "Put in stock" per colour;
> **Restock** (INV-3); and barcodes on the Products list.

> [!info] How it works today
> Once INV-2 is built, the Denim Jacket page has a Sizes & colours card under the three figures, with the line "3 colours × 4 sizes =
> 12 variants. Each variant has its own stock." and the table "Units in stock per colour and size", but no tabs. No product has a
> barcode or a variant SKU. Each colour has a short code that never changes (Black BLK, Indigo IND, Light wash LWH). Renaming a product
> changes only its name. "Delete product?" reads "Denim Jacket (SKU PRD-JKT-001) and its composition will be removed. This cannot be
> undone.".

## Acceptance criteria
- [ ] **AC-1 · Two tabs in the Sizes & colours card.** The card's line becomes "3 colours × 4 sizes = 12 variants. Each variant has its
  own stock and its own barcode." (other products: "1 colour × 4 sizes = 4 variants", "2 colours × One size = 2 variants"). Under the
  heading, two tabs: **Stock by colour**, which holds the stock table of INV-2, and **Barcodes**. The page opens on Stock by colour,
  and the open tab is kept in the page address, so a link can open Barcodes. While any variant has no barcode, **Barcodes** carries the
  warning badge "4 missing". The link **By size and colour** under the In stock figure moves to the card and opens its Stock tab. While
  loading: the frame, heading and tabs at once, with a placeholder of 3 rows; an error shows "Couldn't load the stock and barcodes."
  with **Try again**, which reloads the card and the In stock figure together. On a phone the tabs fill the width, half each.
- [ ] **AC-2 · Barcodes tab.** The Barcodes tab shows each variant's barcode, with Black, Indigo and Light wash as colour pills (a
  group named "Colour"): swatch, name and in grey "· 4 missing" when that colour has variants without a barcode; the chosen pill has a
  2 px ring and a tint and is announced "pressed"; the first colour (Black) is chosen when the tab opens.
  - A table "Barcodes for Indigo": **Size** (the size tag) · **SKU** (the number font: product SKU, colour code, size,
    "PRD-JKT-001-IND-S"; a shoe "PRD-SNK-003-BLK-38") · **Barcode (EAN-13)** (the bars and the 13 digits under them in the number
    font) · **In stock** (this product's units of that variant) · the row's button. With barcodes made, Indigo reads S 2000000000015
    · M 2000000000022 · L 2000000000039 · XL 2000000000046; a variant with a barcode has no button.
  - A variant without a barcode shows the warning badge "No barcode yet" and the button **Generate** (named "Generate barcode for
    PRD-JKT-001-LWH-S"). Light wash with nothing made reads: the pill "Light wash · 4 missing", the tab badge "4 missing", and on the
    pills' row the main button **Generate missing barcodes (4)**, shown only while any variant of the product, in any colour, has no
    barcode (the count is the product's total).
  - **A shared barcode:** under the digits a grey line "Shared with PRD-JKT-002" (PRD-JKT-002 a link to that product); two or three
    others "Shared with PRD-JKT-002, PRD-JKT-007"; more "Shared with PRD-JKT-002, PRD-JKT-007 and 3 more", where "3 more" opens a
    small list. A combination that already has a barcode on any product with the same name never counts as missing and never shows
    **Generate**. "The same name" means the same words, ignoring capitals and extra spaces: "denim jacket" is "Denim Jacket".
  - Under the table: "One barcode for each colour and size. Every product named Denim Jacket shares it, and a barcode never
    changes." Archived and draft products show and generate barcodes like active ones.
  - **A "One size" product** (Leather Tote) has no pills: one table with one row per colour, **Colour** · SKU "PRD-TOT-001-CAM" ·
    Barcode · In stock · button.
  - **Phone:** the pills wrap and are 44 px tall; **Generate missing barcodes (4)** is full width under them; then one card per
    size: the size tag and SKU on the first line, the barcode (bars about 140 px wide) with its digits, "In stock 2", "Shared with
    PRD-JKT-002", and, on a variant without a barcode, **Generate** full width, 44 px. 12 colour pills wrap and a 32-character
    colour name wraps inside its pill.
- [ ] **AC-3 · Generate barcodes.** Generating always asks first, because a number is used for good.
  - From **Generate missing barcodes (4)**, which asks for every variant of the product that has no barcode, whatever its colour: the
    title **Generate 4 barcodes?**, the text "Denim Jacket gets a barcode for Light wash S, M, L and XL, from your in-store numbers. A
    barcode never changes, and its number is never given to anything else." and the buttons **Not now** and **Generate 4 barcodes**.
    With barcodes missing in several colours the text lists each colour in turn, in colour order, and the title and button carry the
    total: **Generate 6 barcodes?**, "Denim Jacket gets a barcode for Black M and L, and Light wash S, M, L and XL, from your in-store
    numbers. …". A size that is only digits is written "Light wash sizes 36, 37 and 38" (one: "Light wash size 36"). A "One size"
    product names its colours: "Leather Tote gets a barcode for Camel and Rust, from your in-store numbers. …". When exactly one is
    missing, the window is the row's one.
  - From a row's **Generate**: **Generate a barcode?**, "Denim Jacket in Light wash, size S, gets a barcode from your in-store
    numbers. A barcode never changes, and its number is never given to anything else." and **Not now** and **Generate barcode**. A
    shoe reads "Denim Sneaker in Light wash, size 36, gets a barcode …"; a "One size" product reads "Leather Tote in Camel gets a
    barcode …". On a phone the window is full width and the confirm button comes first.
  - Each new name + colour + size takes the next number of the workshop's own in-store EAN-13 range, starting at 2000000000015, then
    2000000000022, 2000000000039, 2000000000046 (each with its right check digit). A number is never given twice, and no second barcode
    is ever made for the same name, colour and size. Generating is all or nothing: either every barcode asked for is made, or none.
    Two workshops can each have the number 2000000000015.
  - While generating, the confirm button shows a spinner and keeps its label. On success the window closes, the focus goes to the
    table of barcodes, and the toast reads, from **Generate missing barcodes**, "4 barcodes generated for Denim Jacket." (exactly
    one: "Barcode generated for Black M."), and from a row's **Generate**, "Barcode generated for Light wash S." (a shoe: "Barcode
    generated for Light wash size 36."; a "One size" product: "Barcode generated for Camel."; several colours at once on a "One
    size" product: "2 barcodes generated for Leather Tote."); the rows show their barcodes, and the tab badge and the **Generate
    missing barcodes** button go.
  - If someone made some a moment ago, the toast names the products that hold them, by SKU, once each, in SKU order: "6 barcodes
    ready for Denim Jacket: 5 new, 1 already used by PRD-JKT-002." (two or three products: "…4 new, 2 already used by PRD-JKT-002
    and PRD-JKT-007."; more: "…already used by PRD-JKT-002, PRD-JKT-007 and 3 more."); every one of them: "4 barcodes ready for
    Denim Jacket: all already used by PRD-JKT-002."; one asked from a row: "Barcode ready for Light wash S: already used by
    PRD-JKT-002."; if no product shows the barcode any more (its product was deleted meanwhile), the toast leaves out "by …": "6
    barcodes ready for Denim Jacket: 5 new, 1 already used.", "4 barcodes ready for Denim Jacket: all already used.", "Barcode
    ready for Light wash S: already used.". On failure the window stays open with "Couldn't generate the barcodes. Try again.".
- [ ] **AC-4 · Barcodes follow the name, not the product.** Renaming a product keeps its barcodes; no window asks first (the one
  refusal is AC-5). If a barcode was shared with another product of the old name, it now stands for both names, the other product
  keeps it too, and labels already printed still scan. A product renamed "Trucker Jacket" that later gets Navy shares the barcodes
  of Navy under its current name: Navy S is the same number on every "Trucker Jacket". A colour taken off a product keeps its
  barcodes, and they come back if the colour is added again under the same name. On a rename, only the colours the product has once
  the save is done follow the new name: a colour taken off before the rename (or in the same save) keeps its barcodes under the old
  name only, and shows "No barcode yet" under the new one. **Delete product** keeps the barcodes: when the product has barcodes,
  the "Delete product?" window adds, after today's text, "Its 12 barcodes are kept: they come back on any product named Denim
  Jacket in the same colours and sizes." (exactly one barcode: "Its barcode is kept: it comes back on any product named Denim
  Jacket in the same colour and size."; a "One size" product: "…in the same colours." / "…in the same colour."); with none, the
  window is as today.
- [ ] **AC-5 · A rename that would give a colour and size two numbers is refused.** A colour and size can have only one barcode
  under a name, so two different numbers can't be joined. When the product is saved with a new name (not only other capitals or
  spaces), and for a colour and size it keeps, the old name and the new name each already have a barcode, with different numbers,
  nothing is saved and the Name field reads, in red text under it, the new name as typed with the extra spaces taken out: "Trucker
  Jacket already has its own barcodes for Indigo S and L, so Denim Jacket can't take that name. Choose another name." The form stays
  filled in, there is no toast, and the focus goes to the Name field. One variant: "Trucker Jacket already has its own barcode for
  Indigo S, so …". The variants are named by colour in colour order, each colour followed by its sizes ("Black M, and Indigo S and
  L"); a shoe size reads "Indigo sizes 36 and 37"; a "One size" product names its colours ("Camel and Rust"); beyond three colours
  the first three are named, then "and 2 more colours" ("and 1 more colour" when one is left). Example: Denim Jacket (Black, Indigo and Light wash, 8 barcodes) renamed
  "Trucker Jacket" while another Trucker Jacket has its own Indigo S and Indigo L barcodes is refused with the sentence above; a
  Trucker Jacket with a Light wash S barcode only (Denim Jacket has none for Light wash S) renames fine and then shows that
  barcode. The same check runs if someone makes the clash while the form is open.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 8 commits
> Tech-lead · 2026-10-31 · approved 2026-10-31
> Why complex: two new tables and a numbering rule that must never give a number twice, a rename that carries barcodes or is
> refused, and new data and a new save between the product page and the server.

### In short
A barcode is stored once, as a number of the workshop, together with what it stands for: one colour, one size and one or more names.
A name is kept in a plain form (small letters, single spaces), so "denim jacket" and "Denim  Jacket" are the same name. The product
page looks up the barcodes of the product's name, colours and sizes, so a product shares every barcode another product with the same
name already has, and the barcodes of a deleted product, or of a colour taken off, simply wait under that name until it comes back.
Generating takes the next numbers of the workshop's own counter while holding the workshop's record, so two people can never get the
same number or make two barcodes for one name, colour and size, and it is all or nothing. A rename adds the new name to the barcodes
of the colours and sizes the product has after the save, so the old name keeps them too; when the old and the new name would each
bring a different number for one of them, the rename is refused before anything is written. Every sentence that lists variants
("Black M and L, and Light wash S, M, L and XL") is written by the server, by one rule. The screens follow: the barcode picture, the
Barcodes tab, the Generate window, then the two tabs in the card. Nothing here touches costing, stock or "Can produce now". INV-9
(labels) will read the same barcodes and draw the same picture.

### Steps

| Step        | What it does                                    | Proves           | Lane         | Who      | Size       |
| ----------- | ----------------------------------------------- | ---------------- | ------------ | -------- | ---------- |
| **INV-8-a** | Barcode tables, in-store numbers, the name rule | AC-2, AC-3       | CRITICAL     | Backend  | ~220 lines |
| **INV-8-b** | The product page gets every variant's barcode   | AC-1, AC-2, AC-3 | CRITICAL     | Backend  | ~320 lines |
| **INV-8-c** | Generate barcodes, all or nothing               | AC-3             | CRITICAL     | Backend  | ~300 lines |
| **INV-8-d** | A rename keeps the barcodes, or is refused      | AC-4, AC-5       | CRITICAL     | Backend  | ~220 lines |
| **INV-8-e** | The barcode picture, bars and digits            | AC-2             | CRITICAL     | Frontend | ~150 lines |
| **INV-8-f** | The Barcodes tab                                | AC-2             | NON-CRITICAL | Frontend | ~350 lines |
| **INV-8-g** | The Generate window                             | AC-3             | CRITICAL     | Frontend | ~260 lines |
| **INV-8-h** | Two tabs, the Delete sentence, the Name error   | AC-1, AC-4, AC-5 | NON-CRITICAL | Frontend | ~290 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
also run their PostgreSQL tests; after the last wave the whole tree runs the full parity suite and `php artisan test tests/Browser`
(after `npm run build`). The server steps can ship before the screens: the page simply receives data it doesn't show yet, and the
Edit product form already shows a `name` error under the Name field.

#### INV-8-a · Barcode tables, in-store numbers, the name rule
**Commit:** `feat(barcodes): barcode tables, in-store EAN-13 numbers and the same-name rule (INV-8-a)`

- `App\Domain\Barcodes\Ean13` (pure PHP): `checkDigit()` and `inStore(int $serial)`, "2" + the serial on 11 digits + the check digit:
  1 → 2000000000015, 2 → 2000000000022, 3 → 2000000000039, 4 → 2000000000046; a serial below 1 or above 99,999,999,999 is refused.
- `App\Domain\Barcodes\NameKey::of()` (pure PHP): trimmed, every run of spaces as one space, small letters (`mb_strtolower`);
  accents are kept ("Écru" is not "Ecru"). `NameKey::tidy()` gives the name as typed with the extra spaces taken out, capitals kept
  ("Trucker  Jacket " → "Trucker Jacket"), for the AC-5 sentence.
- Migration: `products.name_key` (150), filled for every existing product, deleted ones too, by a frozen copy of the rule; NOT NULL;
  index (`workshop_id`, `name_key`). `Product` sets it whenever `name` is set (an attribute mutator), so every writer (Actions,
  factories, seeders) keeps it right. `workshops.last_barcode_serial` (default 0): the last in-store serial given.
- `barcodes`, one row per number: `workshop_id`, `number` (13 digits), `colour_id`, `size_id`, `created_by`, timestamps; unique
  (`workshop_id`, `number`), so a number is given once per workshop and two workshops can each have 2000000000015.
- `barcode_names`, what a number stands for: `workshop_id`, `barcode_id`, `name_key`, `colour_id`, `size_id`, timestamps; unique
  (`workshop_id`, `name_key`, `colour_id`, `size_id`), so one name, colour and size never has two barcodes. PostgreSQL only: a
  composite key (`barcode_id`, `colour_id`, `size_id`) → `barcodes`, so a name can't point at a number of another colour or size. Every
  key `restrictOnDelete` (`created_by` `nullOnDelete`, as on materials): nothing in the app ever deletes a barcode.
- Models `Barcode` and `BarcodeName` with `BelongsToWorkshop`, and their factories. No route yet.
- The tech-lead writes in this commit: ADR-0020 "Barcodes follow the name" (the two tables, the counter, the locks, the rename rule
  and its refusal), `docs/ARCHITECTURE.md` (the tables) and the business rules file (a Barcodes section, the variant-list wording
  rule and the glossary), approved with this Technical work.

**Tests**
- 1 unit dataset for `Ean13`: serials 1 to 5 (…015, …022, …039, …046, …053), the published example 4006381333931, the largest
  serial, and the refusals.
- 1 unit dataset for `NameKey`: "Denim Jacket", "denim jacket", "  Denim   Jacket ", "DENIM JACKET" are one key; "Denim Jackets" and
  "Écru" / "Ecru" are not; `tidy()` keeps capitals and drops extra spaces.
- 1 migration test: existing products of two workshops, a deleted one included, get their key; `down()` removes what it added.
- 1 PostgreSQL schema test: a second row with the same number in one workshop is refused, the same number in two workshops is not; a
  second barcode for the same name, colour and size is refused; a name pointing at another colour's number is refused.

> [!example]- Files (12)
> - `.claude/skills/invory-costing-domain/SKILL.md`, `docs/adr/0020-barcodes-follow-the-name.md` (new), `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Domain/Barcodes/{Ean13,NameKey}.php` (new)
> - `app/Models/{Barcode,BarcodeName}.php` (new), `app/Models/Product.php`, `app/Models/Workshop.php`
> - `database/migrations/2026_11_01_100000_create_barcodes_tables.php` (new)
> - `database/factories/{Barcode,BarcodeName}Factory.php` (new)
> - `tests/Unit/Domain/Barcodes/{Ean13Test,NameKeyTest}.php` (new)
> - `tests/Feature/Barcodes/{BarcodesMigrationTest,BarcodesSchemaTest}.php` (new)

#### INV-8-b · The product page gets every variant's barcode
**Commit:** `feat(barcodes): every variant's barcode, SKU and sharing on the product page (INV-8-b)`

- `App\Domain\Barcodes\VariantSku::of()`: product SKU, colour code and size label joined by "-" ("PRD-JKT-001-IND-S", a shoe
  "PRD-SNK-003-BLK-38"); a "One size" product drops the size ("PRD-TOT-001-CAM"). Built on every read, never stored.
- `App\Domain\Barcodes\VariantNames` (pure PHP), the one rule for every sentence that lists variants: grouped by colour in colour
  order, each colour followed by its sizes in size order ("Black M and L, and Light wash S, M, L and XL"); a size that is only digits
  reads "Light wash sizes 36, 37 and 38" (one: "Light wash size 36"); a "One size" product names its colours ("Camel and Rust"); an
  optional limit of three colours, then "and 2 more colours" ("and 1 more colour"), used by AC-5 only. Also `one()` for a single
  variant: "Light wash S", "Light wash size 36", "Camel". The same file holds the SKU-list rule: one, two or three SKUs in full
  ("PRD-JKT-002, PRD-JKT-007 and PRD-JKT-009"), four or more as the first two and "and N more".
- `App\Services\Products\ProductBarcodes::of(Product)`: every colour of the product (list order) × every size of its list, each with
  its SKU, the number that stands for the product's name, that colour and that size (or none), and **shared with**: the other
  non-deleted products of the workshop, any status, that show the same number (their name stands for it, they have that colour and
  their list has that size), ordered by SKU, then id. Plus each colour's missing count, the product's missing total, how many
  variants have a barcode (the Delete sentence) and `missing_names`, the missing variants written by `VariantNames` (the Generate
  window's text). A fixed number of queries, however many colours, sizes or sharing products.
- `ProductBarcodesResource`, and the deferred prop `barcodes` on `products.show`, in the group `stock` and rescued like
  `finished_stock`, so one request brings stock and barcodes and **Try again** reloads both.
- `domain.ts`: the types of the Contract below, in this commit.

**Tests**
- AC-2, 1 read test on the worked example: Black and Indigo with barcodes (Indigo S 2000000000015 … XL 2000000000046), Light wash with
  none → Light wash missing 4, the product's missing 4, with a barcode 8, `missing_names` "Light wash S, M, L and XL"; the SKUs; a
  shoe ("-38") and the "One size" Leather Tote (one row per colour, "PRD-TOT-001-CAM").
- AC-2, 1 sharing dataset: one other product → PRD-JKT-002; five others → all five by SKU; "denim  jacket" counts as the same name; a
  deleted product, another workshop's product and a same-name product without Indigo are never listed; a product created after the
  barcode shows it and has nothing missing; archived and draft products read the same.
- AC-3 / AC-5, 1 unit dataset for `VariantNames`: one colour, two colours ("Black M and L, and Light wash S, M, L and XL"), a shoe
  (sizes / size), "One size" ("Camel and Rust"), five colours with the limit ("…, and 2 more colours"), four ("…, and 1 more
  colour"), one variant in each form; the SKU list with 1, 2, 3 and 5 SKUs.
- 1 contract row in `ProductContractTest`; the query budget of the `stock` group stays within 10 with 12 colours × 11 sizes and 6
  sharing products.

> [!example]- Files (11)
> - `app/Domain/Barcodes/{VariantSku,VariantNames}.php` (new)
> - `app/Services/Products/{ProductBarcodes,ProductBarcodeLevel}.php` (new)
> - `app/Http/Resources/ProductBarcodesResource.php` (new)
> - `app/Http/Controllers/ProductController.php`
> - `resources/js/types/domain.ts`
> - `tests/Feature/Barcodes/ProductBarcodesTest.php` (new), `tests/Unit/Domain/Barcodes/{VariantSkuTest,VariantNamesTest}.php` (new)
> - `tests/Feature/Products/ProductContractTest.php`, `tests/Feature/Performance/QueryBudgetTest.php`

#### INV-8-c · Generate barcodes, all or nothing
**Commit:** `feat(barcodes): generate in-store barcodes, all or nothing (INV-8-c)`

- `POST products/{product}/barcodes` (`products.barcodes.store`, `ProductBarcodeController@store`, the product's `update` policy;
  another workshop's product is a 404). The body lists the variants asked for: the row's **Generate** sends one, **Generate missing
  barcodes** sends every missing variant of the product, in every colour.
- `GenerateBarcodesRequest`: at least one variant, at most the product's colours × sizes; each a colour of the product and a size of
  its list; no variant twice. Any refusal sits on `variants` (the window then shows "Couldn't generate the barcodes. Try again.").
- `App\Actions\Barcodes\GenerateBarcodes`, one transaction: the product's row lock (ADR-0017 order), the variants checked again (a
  colour taken off meanwhile is refused), then the workshop's row lock; for each variant, in colour order then size order, a barcode
  that already stands for the name, colour and size is counted as "already used", with the products that show it; otherwise the next
  serial becomes the next number, written to `barcodes` and `barcode_names`; then the counter is saved. Any failure writes nothing
  and leaves the counter as it was.
- The toast is built by the server from the result, by how many were asked, not by which button:
  - several asked: "4 barcodes generated for Denim Jacket."; some already made: "6 barcodes ready for Denim Jacket: 5 new, 1 already
    used by PRD-JKT-002."; all already made: "4 barcodes ready for Denim Jacket: all already used by PRD-JKT-002."; a "One size"
    product the same ("2 barcodes generated for Leather Tote.");
  - one asked: "Barcode generated for Light wash S." ("…for Light wash size 36.", "…for Camel."); already made: "Barcode ready for
    Light wash S: already used by PRD-JKT-002.";
  - the products holding them: by SKU, each once, in SKU order, by the SKU-list rule of INV-8-b; when no non-deleted product
    holds them, "by …" is dropped: "6 barcodes ready for Denim Jacket: 5 new, 1 already used.", "4 barcodes ready for Denim
    Jacket: all already used.", "Barcode ready for Light wash S: already used.".
- Back to the product page with `?tab=barcodes`.

**Tests**
- AC-3, 1 happy test: Light wash S, M, L, XL in a new workshop → 2000000000015 … 2000000000046 in size order, "4 barcodes generated
  for Denim Jacket."; Black M and L with Light wash S … XL → 6, in colour order then size order; one asked → "Barcode generated for
  Light wash S." (a shoe "…size 36.", "One size" "…for Camel."); a later generate goes on at the next number; a second workshop
  starts at 2000000000015 too.
- AC-3, 1 dataset for "already used": Light wash M made by PRD-JKT-002 a moment ago → 3 new numbers, no second barcode, "…3 new, 1
  already used by PRD-JKT-002."; holders PRD-JKT-007 and PRD-JKT-002 → named once each, in SKU order; five holders → "…PRD-JKT-002,
  PRD-JKT-007 and 3 more."; all already made → "all already used by …"; one asked and already made → "Barcode ready for …";
  the holder deleted meanwhile → the three forms without "by …".
- AC-3, 1 rollback test: a failure on the third barcode → nothing saved, the counter unchanged, the next generate starts at
  2000000000015.
- AC-3, 1 validation dataset (nothing asked, a colour not on the product, another workshop's colour, a size of another list, a
  variant twice, not a number) → the error on `variants`, nothing saved; 1 test of the check under the row lock (a colour taken off
  between the check and the save); guest → login; archived and draft products generate.

> [!example]- Files (7)
> - `app/Actions/Barcodes/GenerateBarcodes.php` (new), `app/Actions/Barcodes/GeneratedBarcodes.php` (new, the result and its toast)
> - `app/Http/Controllers/ProductBarcodeController.php` (new)
> - `app/Http/Requests/Barcodes/GenerateBarcodesRequest.php` (new)
> - `routes/web.php`
> - `tests/Feature/Barcodes/GenerateBarcodesTest.php` (new)
> - `tests/Feature/Routes/RoutesTest.php` (the new route in its guest and validation datasets)

#### INV-8-d · A rename keeps the barcodes, or is refused
**Commit:** `feat(barcodes): a renamed product keeps its barcodes; a clash of numbers is refused (INV-8-d)`

- `App\Services\Barcodes\RenameClash::refusal(Product, string $newName, list<int> $colourIds, Category)`: only when the name's key
  changes. For every colour and size the product has **after** the save (the colours and category submitted, or today's when absent):
  a clash is a colour and size where the old name and the new name each have a barcode and the numbers differ. The same number under
  both names, a barcode under the new name only, or one under the old name only is never a clash. The sentence: "Trucker Jacket
  already has its own barcodes for Indigo S and L, so Denim Jacket can't take that name. Choose another name." (one: "…its own
  barcode for Indigo S, so …"), the new name by `NameKey::tidy()`, the old name as saved, the variants by `VariantNames` with the
  three-colour limit.
- `UpdateProductRequest::after()` puts it on `name`; `UpdateProduct` checks again under the product's row lock and then the
  workshop's (the order Generate uses), before any write, so a barcode made while the form is open is caught too.
- When the rename goes through, after the colours and category are saved: for every colour and size the product has now, a barcode
  of the old name that the new name doesn't have gets the new name too, in one insert (`AddNameToBarcodes`). The old name keeps it,
  so the other products of the old name keep it and labels already printed still scan. A colour taken off before or in the same save
  is not carried: under the new name it shows "No barcode yet". A change of capitals or spaces only writes nothing.
- A colour taken off and added back under the same name, and a deleted product, need no code: the barcodes stay under the name.

**Tests**
- AC-4, 1 dataset on Edit product: Denim Jacket renamed "Trucker Jacket" keeps its numbers and PRD-JKT-002, still Denim Jacket, keeps
  them too; it then gets Navy, Navy S is generated under "Trucker Jacket" and a second Trucker Jacket with Navy shows the same number;
  "denim  JACKET" writes nothing; Light wash taken off in the same save and added back later → "No barcode yet" under the new name.
- AC-4, 1 test: a colour taken off and added back shows its old numbers; a deleted Denim Jacket's barcodes show on a new product
  named Denim Jacket.
- AC-5, 1 dataset: another Trucker Jacket with its own Indigo S and L → refused with the card's sentence on `name`, nothing saved
  (name, composition, colours unchanged); one variant → "barcode for Indigo S"; a Trucker Jacket with Light wash S only (Denim Jacket
  has none there) → renames fine and shows it; the same number under both names → renames fine; a clash only on a colour taken off in
  that save → renames fine; five clashing colours → three named, "and 2 more colours"; four → "and 1 more colour"; "Trucker
  Jacket " typed with extra spaces → "Trucker Jacket".
- AC-5, 1 test of the check under the lock: the clash is made between the request's check and the save → refused, nothing saved.

> [!example]- Files (6)
> - `app/Services/Barcodes/RenameClash.php` (new)
> - `app/Actions/Barcodes/AddNameToBarcodes.php` (new)
> - `app/Actions/Products/UpdateProduct.php`, `app/Http/Requests/Products/UpdateProductRequest.php`
> - `tests/Feature/Barcodes/{RenameKeepsBarcodesTest,RenameClashTest}.php` (new)

#### INV-8-e · The barcode picture, bars and digits
**Commit:** `feat(barcodes): the EAN-13 barcode picture (INV-8-e)`

- `resources/js/lib/ean13.ts`: the 95 bars and spaces of an EAN-13 number (guards, the left half by the first digit's pattern, the
  right half), from the 13 digits the server sent; anything that isn't 13 digits with the right check digit draws nothing. Digit
  work only: no money and no quantity.
- `components/shared/barcode.tsx`: the bars as an SVG, always black on white with its quiet margins, and the 13 digits under them in
  the number font; a width (about 140 px on a phone). The digits are the text people and screen readers read. INV-9's label uses
  the same component.

**Tests**
- AC-2, 1 dataset for the encoder: 4006381333931 against its published bar pattern, 2000000000015, a wrong check digit and 12 digits
  draw nothing.
- AC-2, 1 component test: 30 bars and the 13 digits.

> [!example]- Files (4)
> - `resources/js/lib/ean13.ts`, `ean13.test.ts` (new)
> - `resources/js/components/shared/barcode.tsx`, `barcode.test.tsx` (new)

#### INV-8-f · The Barcodes tab
**Commit:** `feat(barcodes): the Barcodes tab, colour pills and shared barcodes (INV-8-f)`

- `components/products/barcodes-tab.tsx`, given the product, its `barcodes` and its `finished_stock` (for **In stock**), and a
  callback to ask for barcodes: the pills (a group named "Colour", swatch, name, "· 4 missing", pressed, the first colour chosen);
  **Generate missing barcodes (4)** while the product's missing total is above 0; the table "Barcodes for Indigo" (Size · SKU ·
  Barcode (EAN-13) · In stock · the row's button); "No barcode yet" with **Generate** named "Generate barcode for PRD-JKT-001-LWH-S";
  "Shared with …", the SKUs linking to their products, "and 3 more" opening a small list; the note with the product's name. "One
  size": no pills, one table with a **Colour** column and one row per colour. Phone: the pills wrap at 44 px, the main button full
  width, one card per size, **Generate** full width at 44 px; 12 pills and a 32-character name wrap.
- The tab doesn't count: missing counts, SKUs and sharing all come from the server.

**Tests**
- AC-2, 1 table test (Indigo on the worked example) and 1 missing test (Light wash: pill, badge, the named **Generate**, the main
  button's count, no main button when nothing is missing).
- AC-2, 1 sharing test (one, three and six others, and the small list), 1 "One size" test, 1 phone test scoped with `within()`.

> [!example]- Files (3)
> - `resources/js/components/products/barcodes-tab.tsx`, `barcodes-tab.test.tsx` (new)
> - `resources/js/test/fixtures/products.ts` (barcodes for the worked example)

#### INV-8-g · The Generate window
**Commit:** `feat(barcodes): the Generate barcodes window (INV-8-g)`

- `components/products/generate-barcodes-dialog.tsx`, two forms:
  - from **Generate missing barcodes (N)** with two or more missing: **Generate 6 barcodes?**, "Denim Jacket gets a barcode for "
    + `missing_names` + ", from your in-store numbers. …" in every form (a "One size" product: "Leather Tote gets a barcode for
    Camel and Rust, from your in-store numbers. …"), **Not now** and **Generate 6 barcodes**; it sends every missing variant of
    the product, in every colour;
  - from a row's **Generate**, and from the main button when exactly one is missing: **Generate a barcode?**, "Denim Jacket in Light
    wash, size S, gets a barcode …" (a shoe "…, size 36, …"; "One size": "Leather Tote in Camel gets a barcode …"), **Not now** and
    **Generate barcode**; it sends that variant.
- The confirm button's spinner keeps its label; it reloads only `barcodes`. Success: the window closes, the focus goes to the table of
  barcodes, the toast comes from the server. A refusal or a failed request: the window stays open with "Couldn't generate the
  barcodes. Try again." (after a refusal the tab's data is reloaded, so the next try asks for what is really missing). Phone: full
  width, the confirm button first.

**Tests**
- AC-3, 1 happy test from **Generate missing barcodes (4)**: the title, the text, the four variants sent, the spinner, the focus on
  the table; 1 several-colour test (Black M and L with Light wash S … XL → **Generate 6 barcodes?**, the text in colour order, six
  variants sent; a "One size" product: "…for Camel and Rust, from your in-store numbers. …"); 1 wording dataset for the row's
  window (letter size, shoe, "One size", and the main button with exactly one missing); 1 failure test (the window stays open with its message).

> [!example]- Files (3)
> - `resources/js/components/products/generate-barcodes-dialog.tsx`, `generate-barcodes-dialog.test.tsx` (new)
> - `resources/js/components/products/barcodes-tab.tsx` (opens the window)

#### INV-8-h · Two tabs, the Delete sentence, the Name error
**Commit:** `feat(barcodes): Stock and Barcodes tabs in the Sizes & colours card, the Delete sentence and the rename refusal (INV-8-h)`

- `sizes-colours-card.tsx`: the line "… Each variant has its own stock and its own barcode."; the tabs **Stock by colour** (today's
  table) and **Barcodes** with the warning badge "4 missing"; the open tab in the address as `?tab=barcodes` (Stock has none),
  changed without asking the server (`router.replace`); half the width each on a phone. The **By size and colour** link opens the
  card on its Stock tab. Loading: the frame, heading and tabs at once and a 3-row placeholder. Error: "Couldn't load the stock and
  barcodes." with **Try again**; it and the In stock figure's **Try again** reload `finished_stock` and `barcodes` together.
- `show.tsx`: the card waits for both props. "Delete product?" adds, when the product has barcodes, "Its 12 barcodes are kept: they
  come back on any product named Denim Jacket in the same colours and sizes." (one: "Its barcode is kept: it comes back on any
  product named Denim Jacket in the same colour and size."; a "One size" product: "…in the same colours." / "…in the same colour.").
- Edit product, AC-5: the form already shows a `name` error in red under the Name field, keeps what was typed, shows no toast and
  moves the focus to the first invalid field; this step adds the test that proves it with the rename sentence (no code change
  expected; if the long sentence needs wrapping, that is the only change).
- Tabs: `@radix-ui/react-tabs` (the same family as the 15 Radix packages in use) and the standard shadcn `components/ui/tabs.tsx`,
  added by hand because the shadcn CLI is broken here (F-04).

**Tests**
- AC-1, 1 tabs test: Stock first, `?tab=barcodes` opens Barcodes, switching changes the address, the badge shown or not, the link opens
  Stock; 1 loading and error test (frame, heading and tabs, 3 rows; the message; Try again reloads both props); the three wordings of
  the line.
- AC-4, 1 page dataset: 12 barcodes, one barcode, a "One size" product with several and with one, and the window as today with none.
- AC-5, 1 Edit product test: the server's sentence under Name, the form still filled in, no toast, the focus on Name.

> [!example]- Files (10)
> - `package.json`, `package-lock.json`
> - `resources/js/components/ui/tabs.tsx` (new)
> - `resources/js/components/products/sizes-colours-card.tsx`, `sizes-colours-card.test.tsx`
> - `resources/js/components/products/finished-stock-figure.tsx` (the link and Try again)
> - `resources/js/pages/products/show.tsx`, `show.test.tsx`
> - `resources/js/pages/products/edit.test.tsx`
> - `resources/js/components/shared/skeletons.tsx` (only if the card's placeholder needs the tabs there)

### Contract (frozen before dispatch)
- `products.show`: new deferred prop `barcodes` (group `stock`, rescued; `null` and listed in `rescuedProps` when it couldn't load).
- `POST products/{product}/barcodes` (`products.barcodes.store`): body `{ variants: { colour_id: number; size_id: number }[] }`;
  refusals on `variants`; success → `products.show` with `?tab=barcodes` and the toast. The window reloads `only: ['barcodes']`.
- `products.update`: the rename refusal on `name` (one sentence, built by the server).
- `products.show?tab=barcodes`: frontend only; the server ignores it.

`resources/js/types/domain.ts`:
```ts
/** Another product that shows the same barcode (INV-8). */
export type ProductRef = { id: number; sku: string };
/** One variant (a colour in a size) of a product and its barcode (INV-8). */
export type VariantBarcode = {
    size: Size;
    sku: string; // "PRD-JKT-001-IND-S"; "PRD-TOT-001-CAM" for a "One size" product
    barcode: string | null; // the 13 digits; null = "No barcode yet"
    shared_with: ProductRef[]; // other non-deleted products showing it, by SKU; [] without a barcode
};
export type ColourBarcodes = { colour: Colour; missing: number; variants: VariantBarcode[] }; // every size, in order
export type ProductBarcodes = {
    missing: number; // the product's total: "4 missing", "Generate missing barcodes (4)"
    missing_names: string | null; // the list only: "Black M and L, and Light wash S, M, L and XL"; null when nothing is missing
    with_barcode: number; // "Its 12 barcodes are kept…"
    colours: ColourBarcodes[]; // every colour of the product, in list order
};
// ProductShowProps gains: barcodes?: ProductBarcodes | null; // deferred prop (group 'stock')
```

### Decisions
- **A barcode is a number plus the names it stands for**, in two tables: `barcodes` makes "a number is given once" a database rule,
  and `barcode_names` makes "one barcode per name, colour and size" one. Rejected: one table where a renamed name repeats the number
  (nothing would stop a number being reused for another colour), and barcodes owned by products (a deleted product's barcodes
  couldn't come back on a new product of that name). Recorded in ADR-0020.
- **The same-name rule lives in one PHP function** (`NameKey`), stored on the product as `name_key`, and never in SQL: PostgreSQL's
  and SQLite's `lower()` treat accents differently.
- **One rule writes every list of variants and of SKUs** (`VariantNames`, on the server): the toasts, the AC-5 sentence and, through
  `missing_names`, the Generate window's text, so the screens never rebuild "Black M and L, and Light wash …" themselves. The row's
  window names one colour and one size from the row, which needs no rule.
- **The toast depends on how many variants were asked for**, not on the button: one asked reads "Barcode generated for …", several
  read "N barcodes generated for <product>". With exactly one missing, the main button opens the row's window, so both agree.
- **A rename is checked and carried over the colours and sizes the product has after the save** (AC-4, AC-5): the check comes before
  any write, under the product's lock and then the workshop's, so a Generate can't make a clash in between.
- **The counter is a column of the workshop**, taken under the workshop's row lock; every barcode write (Generate, rename) takes the
  product's lock first, then the workshop's, so they never deadlock.
- **"Shared with"** and the toast's holders list the other non-deleted products of any status that show the number; a deleted
  product never shows.
- **A refused Generate shows the card's failure sentence** ("Couldn't generate the barcodes. Try again.") and the tab reloads, so no
  new wording is needed.
- **No barcode library**: an EAN-13 picture is ~60 lines, tested against the published example, and INV-9's label reuses it.
- **Seams for INV-9**: `ProductBarcodes` already gives each variant its SKU and number, and `Barcode` draws it; nothing of labels,
  printing or Settings › Labels is built here.

### Order
Wave 1, in parallel: backend INV-8-a, then INV-8-b (one developer) · frontend INV-8-e (needs no contract). · Wave 2, in parallel:
backend INV-8-c, then INV-8-d · frontend INV-8-f (once INV-8-b's types land). · Wave 3 (frontend): INV-8-g (once INV-8-c's route
exists, for Wayfinder), then INV-8-h. Docker must be up for the parity runs.

### Risks
- The live database `laravel` gets `products.name_key` filled for every product → a migration the human runs (`php artisan migrate`,
  never fresh) before the click-through; its test checks every product, deleted ones too.
- Two people generating at once, or a rename while someone generates → both take the workshop's row lock after the product's; the
  unique keys refuse what slips through; the "already used" test and the rename's lock test cover the races the card describes.
- A rename check that reads the colours or sizes before the save would refuse or carry the wrong ones → the check and the carry both
  use the colours and category submitted; the AC-4 and AC-5 datasets include a colour taken off in the same save.
- A wrong bar pattern would print labels that don't scan → the encoder is tested against the published example, and the INV-9
  click-through scans a real label with a phone.
- Sharing could grow the queries with many same-name products → the query budget test uses 6 sharing products and 12 × 11 variants.
- Numbers starting with 2 are for use inside the shop only (GS1 "restricted circulation"); a retailer selling these goods could have
  its own numbers in that range → noted in ADR-0020; nothing to do while barcodes stay in the workshop.
- The browser golden path walks the Sizes & colours card → Stock stays the first tab, so it should pass unchanged; the browser suite
  runs at the end.

### Progress
- 2026-10-31 · build started. Docs of INV-8-a written (ADR-0020, ARCHITECTURE.md, the Barcodes section of the business rules). Wave 1 dispatched: backend INV-8-a + INV-8-b, frontend INV-8-e.
