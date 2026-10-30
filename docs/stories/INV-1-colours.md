---
id: INV-1
title: Colours as fixed values
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
created: 2026-10-30
tags:
  - story
  - variants
---

# INV-1 · Colours as fixed values

> [!abstract] Description
> As a **workshop owner**, I want **one fixed list of colours, and each product's colours picked from it**, so that **"Indigo" is
> always the same Indigo**, never "indigo", "Indgo" or "Dark blue", and stock, barcodes and orders can later be counted per colour.
> Each workshop keeps its list in Settings › Catalogue › **Colours**, starting from 20 default colours. Add product and Edit product
> get a Colours field, and the product page gets a "Colours" line. How every page looks is in the UI card: [[INV-1-colours-ui]].
> Left out on purpose: stock, barcodes, orders and sales per colour, and with them the rule that a colour with units in stock can't be
> taken off a product (they all come with stock per colour, INV-2); removing a colour from the list; colours on the Products list; and
> the materials' "Colour" field, which stays free text ("Antique brass").

> [!info] How it works today
> Products have no colour. Add product and Edit product show Details (SKU, Name, Category with its size tags and "Sizes come with the
> category.", Status with "Only active products can be sold.", Description), then Costs and price, Photo and Composition. The Denim
> Jacket page header reads "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active", and nothing says which colours it is made in.
> Materials have a typed colour: the material form's "Colour (optional)" field shows "e.g. Indigo, Antique brass", and anything can be
> typed, so "Indigo", "indigo" and "Indgo" count as three different colours.
> Settings › Catalogue lists Units, Material categories and Product categories: a table each (Name, Code, Used by, Status and the
> row's buttons), one card per row below laptop width; "Used by" reads "3 products", or "Not used".

## Acceptance criteria
- [ ] **AC-1 · 20 colours in every workshop, shown the same everywhere.** Given any workshop, when INV-1 arrives, its colour list
  holds the 20 default colours of the UI card, in that order, each with its name, code and swatch (White WHT #FFFFFF first, Indigo
  IND #3E4C7E tenth, Forest green FGR #2F5A43 last). A workshop created by signing up starts with the same 20. Each workshop's list is
  its own: renaming Indigo in one workshop changes nothing in another, and two workshops may each have a colour coded IND. Every
  product recorded before INV-1 gets White, the first colour of the list, so the Denim Jacket's page reads "Colours ■ White". A colour
  always shows as its swatch **and** its name, never the swatch alone: a small rounded square in the colour, the same fill in light and
  dark mode, with a thin edge in the grey of a field's border (3.2:1 on the card in both modes), so White shows on a white card and
  Black in dark mode. Screen readers skip the swatch and read the name.
- [ ] **AC-2 · Settings › Colours.** The Settings menu's Catalogue group reads Units, Material categories, Product categories,
  **Colours**. The page shows the title "Colours", the line "The colours your products come in. Each one shows as its swatch and its
  name. Rename any of them, or add your own." and the main button **Add colour**. On a laptop, a table with the columns "Colour"
  (swatch and name), "Code" (IND, in the number font) and "Used by", and a button "Edit Indigo" on each row; colours listed in their
  order. "Used by" counts the products that have the colour, whatever their status, deleted products left out, as the categories
  count today: Black "4 products", Indigo "3 products", Light wash "1 product", Ecru "Not used". There is no Status column and no
  Archive, Restore or Delete: a colour is never removed. 40 colours fit in one list with no pages, and a 32-character name wraps in
  its cell. Below laptop width, one card per colour (swatch and name as its title, then the code, Used by and the "Edit Indigo"
  button), with **Add colour** full width under the line.
- [ ] **AC-3 · Add a colour.** **Add colour** opens the window "Add colour" with: "Name" (up to 32 characters); "Code" with the hint
  "2 to 4 capital letters or digits, such as IND. It can't be changed later." (letters typed small are saved as capitals, "stw" as
  STW); "Swatch", a square colour well named "Pick swatch colour" that opens the computer's own colour chooser, beside a text field
  holding the colour code (#B7C3CF; a code typed small, "#b7c3cf", is accepted and shown in capitals, and so is one typed without "#", "B7C3CF" → "#B7C3CF"), with the hint "Pick the colour,
  or type its code, such as #3E4C7E."; "Preview", the colour tag as it will look on a light strip "On a light screen" and a dark strip
  "On a dark screen", following what is typed or picked; and "Position (optional)" with "Lower numbers are listed first. Leave empty
  to list it last.". Adding Stone wash, STW, #B7C3CF with no position → **Save colour** shows a spinner, then the toast "Colour
  created.", and Stone wash is listed 21st, after Forest green, with "Not used". **Cancel** closes the window and saves nothing.
- [ ] **AC-4 · A wrong entry is refused.** In Add colour or Edit colour, **Save colour** with a wrong entry saves nothing, keeps the
  window open, shows the message under its field and puts the focus on the first wrong field: an empty name "Enter the colour's
  name."; "indigo" while Indigo exists (capitals ignored) "You already have a colour named "Indigo"."; a 33-character name "Keep the
  name to 32 characters or fewer."; an empty code, "INDIG" or "I-D" "Enter 2 to 4 capital letters or digits, such as IND."; "IND"
  while Indigo has it "Code IND is already used by Indigo."; an empty swatch or "blue" "Enter a colour code like #3E4C7E."; a position that isn't a whole number from 0 to
  32,767 ("2.5", "-1" or "40000") "Enter a whole number from 0 to 32,767, or leave it empty.".
- [ ] **AC-5 · Edit a colour.** "Edit Indigo" opens the window "Edit Indigo" with "Code IND can't be changed." under its title and no
  Code field; Name, Swatch, Preview and Position work as in Add colour. While 3 products use Indigo, the swatch hint adds "A new name
  or swatch shows on the 3 products that use Indigo."; for a colour no product uses (Ecru), that sentence isn't shown. Renaming Indigo
  to "Indigo blue" → toast "Colour updated.", the list shows it in its place, and the 3 products' pages read "Indigo blue". The code
  IND never changes.
- [ ] **AC-6 · Colours in Add product and Edit product.** In the Details card, a full-width row after Category and Status, before
  Description: "Colours", the hint "The colours this product is made in, from your list in Settings › Colours.", the chosen colours as
  chips in a row that wraps (swatch, name and a ✕ named "Remove Indigo"), then an outline **Add colour** button with a plus sign;
  screen readers announce the chips' group as "Colours". Add product opens with no colour chosen; Edit Denim Jacket shows "White"
  after INV-1 arrives. Every product needs at least one colour, whatever its status: **Save product** with none saves nothing and shows
  "Choose at least one colour." under the field, with the focus on **Add colour**. On Edit Denim Jacket, adding Black, Indigo and
  Light wash and taking off White with its ✕, then **Save product** (spinner while saving) → toast "Product updated." and the product
  page; a ✕ only takes the colour off the form, nothing is saved before **Save product**. A new product → "Product created.". On a
  phone, one column: the chips wrap two or three to a row and are 44 px tall with a 44 px target for each ✕, **Add colour** sits on
  its own row, full width and 44 px tall, and **Save product** stays in the bar fixed at the bottom.
- [ ] **AC-7 · Pick colours, and see them on the product page.** **Add colour** opens a list under the button, about as wide as a
  phone screen: a search field labelled "Search colours", then the workshop's colours in list order, each row with a check mark when
  chosen, the swatch, the name and the code in grey in the number font (IND); at the bottom "3 chosen" and **Done**. Typing filters by
  name or code ("ind" finds Indigo, "BLK" finds Black); with no match, "No colour named "teal"." and "Missing one? Add it in Settings
  › Colours.". Enter or a click chooses or un-chooses a colour and the list stays open; **Done** or Esc closes it and puts the focus
  back on **Add colour**. Screen readers hear "Indigo, IND, selected". With 40 colours the list scrolls inside, about 8 rows high, the
  search field stays on top and the focused row is never hidden under the bottom bar. On a phone the list is a sheet over the whole
  screen titled "Choose colours" with ✕ "Close", 44 px rows and a bar fixed at the bottom with "3 chosen" and **Done**; the focus
  stays inside the sheet, and closing it puts the focus back on **Add colour**. On the product page, a line under the badges row
  (PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active) reads grey "Colours", then "■ Black ■ Indigo ■ Light wash", always in the
  list's order whatever order they were picked in (Light wash, Black, Indigo picked → Black, Indigo, Light wash); screen readers hear
  "Colours: Black, Indigo, Light wash". It wraps on a narrow screen and nothing scrolls sideways at 375 or 320 px. Nothing else
  changes: "Can produce now 112 units", "In stock", "Unit cost TND 35.754", the materials, orders and sales stay as they are.

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 7 commits
> Tech-lead · 2026-10-30 · approved 2026-10-30
> Why complex: two new tables and a data step on the live database (20 colours per workshop, White for every product), new rules
> on names and codes, a new Settings page with its routes, and new data in the product's form and page.

### In short
Each workshop gets its own table of colours: a name, a short code, a swatch and a place in the list. The 20 default colours are
written into every workshop that exists when INV-1 arrives, and into every new workshop at sign-up, from one list kept in the code.
A second table links each product to its colours; the same data step gives every product already recorded the first colour of its
workshop (White). The server keeps the rules: names unique in the workshop whatever the capitals, codes unique and never changed
(INV-2 builds variant SKUs from them), and another workshop's colour simply doesn't exist (a 404 on its address, refused in a product
form). The screens come after the server part they use: first Settings › Colours with its windows, then the product form's Colours
field and picker, then the Colours line on the product page. Costing, "Can produce now", stock, orders and sales don't read colours,
so no figure changes.

### Steps

| Step        | What it does                                      | Proves                 | Lane         | Who      | Size       |
| ----------- | ------------------------------------------------- | ---------------------- | ------------ | -------- | ---------- |
| **INV-1-a** | A colour list in every workshop, 20 to start      | AC-1                   | CRITICAL     | Backend  | ~220 lines |
| **INV-1-b** | Settings › Colours on the server: list, add, edit | AC-2, AC-3, AC-4, AC-5 | CRITICAL     | Backend  | ~320 lines |
| **INV-1-c** | The colour tag and the Settings › Colours page    | AC-1, AC-2             | NON-CRITICAL | Frontend | ~250 lines |
| **INV-1-d** | The Add colour and Edit colour windows            | AC-3, AC-4, AC-5       | NON-CRITICAL | Frontend | ~280 lines |
| **INV-1-e** | Products keep their colours; White for old ones   | AC-1, AC-6, AC-7       | CRITICAL     | Backend  | ~260 lines |
| **INV-1-f** | The Colours field and the colour picker           | AC-6, AC-7             | CRITICAL     | Frontend | ~400 lines |
| **INV-1-g** | The Colours line on the product page              | AC-1, AC-7             | NON-CRITICAL | Frontend | ~90 lines  |

| AC   | Proved by                                                                                         |
| ---- | ------------------------------------------------------------------------------------------------- |
| AC-1 | INV-1-a (20 per workshop, sign-up), INV-1-c (the tag), INV-1-e (White for old products), INV-1-g  |
| AC-2 | INV-1-b (the list, its order, "Used by" counts), INV-1-c (the page, the menu, the cards)          |
| AC-3 | INV-1-b (save, capitals, listed last), INV-1-d (the window, preview, spinner, Cancel)             |
| AC-4 | INV-1-b (each refusal and its exact message), INV-1-d (messages under fields, focus on the first) |
| AC-5 | INV-1-b (rename, the code never changes), INV-1-d (the Edit window and its hint)                  |
| AC-6 | INV-1-e (at least one colour, the workshop's only), INV-1-f (the field, chips, the error, phone)  |
| AC-7 | INV-1-e (list order), INV-1-f (the picker), INV-1-g (the product page line)                       |

The rows are in merge order: each server change lands right before the screens that use it. Every step keeps `composer test`,
`npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. INV-1-a, INV-1-b and INV-1-e also run their PostgreSQL
tests (`composer test:pgsql` with a filter: new tables, indexes and constraints). After the last wave the whole tree runs the full
`composer test:pgsql` and `composer test:browser` (one new golden path in INV-1-g). The phone layout (44 px targets, the sheet, no
sideways scroll at 375 and 320 px) is checked by the designer's review and the click-through; the tests check names, states and focus.

#### INV-1-a · A colour list in every workshop, 20 to start
**Commit:** `feat(colours): a colour list per workshop with the 20 defaults (INV-1-a)`

- Migration `colours`: `workshop_id` (`restrictOnDelete`), `code` (up to 4), `name` (up to 32), `swatch` (7, `#RRGGBB`), `position`,
  timestamps. Unique (`workshop_id`, `code`); a unique index on (`workshop_id`, `lower(name)`) so "Indigo" and "indigo" can't both
  exist; an index on (`workshop_id`, `position`). PostgreSQL only: CHECK `code ~ '^[A-Z0-9]{2,4}$'`, CHECK `swatch ~ '^#[0-9A-F]{6}$'`,
  and unique (`workshop_id`, `id`) for INV-1-e's composite key. No `archived_at`: a colour is never removed.
- The same migration writes the 20 defaults (the UI card's table, positions 1 to 20) into every workshop that exists, from its own
  frozen copy of the list (a migration imports no app class, as the size lists migration does). A database with no workshop gets none.
  `down()` drops the table.
- Model `Colour` (`BelongsToWorkshop`, scope `ordered()`: position, then name, then id). Saving a changed `code` on an existing colour
  throws: the code stays fixed even if a later caller forgets.
- `App\Actions\Colours\CreateDefaultColours::handle(Workshop)`: the app's copy of the 20, one insert. `CreateWorkshop` calls it, so
  sign-up and the demo seeder get the 20; `WorkshopFactory` calls it too (`afterCreating`), so every test workshop looks like a real one.
- The tech-lead writes in this commit: ADR-0018 "Colours: a list per workshop with fixed codes", `docs/ARCHITECTURE.md` §4 (the two
  tables) and the business rules file (approved by the human with this Technical work on 2026-10-30): the glossary row "Colour"; R1
  adds colours, R3 colour names (ignoring capitals) and codes unique per workshop, R4 a product takes only its workshop's colours, R6 a
  new workshop is empty except for its 20 default colours; and a short "Colours (INV-1)" section: colours change no figure, a
  product's colours always show in list order, products recorded before INV-1 got their workshop's first colour (White).

**Tests**
- AC-1, one migration test: two workshops before → each has the 20 in order with their exact names, codes and swatches (one dataset;
  White WHT #FFFFFF first, Indigo IND #3E4C7E tenth, Forest green FGR #2F5A43 last), both hold an IND; no workshop → no colour.
- AC-1, one sign-up test: a new account's workshop has the same 20 as a migrated one.
- One model test: changing a colour's code is refused.
- One schema test (PostgreSQL only): the database refuses "indigo" beside "Indigo", a second IND in one workshop, a code "I-D" and a
  swatch "blue".
- The workshop arch test lists `colours` as workshop data.

> [!example]- Files (12)
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead, approved 2026-10-30)
> - `docs/adr/0018-colours-per-workshop.md` (new), `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Models/Colour.php` (new)
> - `app/Actions/Colours/CreateDefaultColours.php` (new), `app/Actions/Workshops/CreateWorkshop.php`
> - `database/migrations/2026_10_30_100000_create_colours_table.php` (new)
> - `database/factories/ColourFactory.php` (new), `database/factories/WorkshopFactory.php`
> - `tests/Feature/Colours/ColourDefaultsTest.php` (new: migration, sign-up, fixed code)
> - `tests/Feature/Colours/ColourSchemaTest.php` (new, PostgreSQL only)
> - `tests/Arch/WorkshopScopeTest.php`

#### INV-1-b · Settings › Colours on the server: list, add, edit
**Commit:** `feat(colours): list, add and edit colours in settings (INV-1-b)`

- Three routes in `routes/settings.php`, behind `auth` + `verified`: `settings.colours.index` (GET), `.store` (POST) and `.update`
  (PUT, id 1 to 18 digits). No delete, archive or restore route. `ColourPolicy` (any member, like the categories); another workshop's
  colour is already a 404 through the workshop scope.
- `ColourController`: `index` renders `settings/colours` with `colours` (every colour in list order, with `usage_count` = the
  non-deleted products that have it, any status; one `withCount`, no pages). `store` and `update` call the Actions, flash "Colour
  created." / "Colour updated." and go back.
- `StoreColourRequest` / `UpdateColourRequest`: before validation the code and the swatch are put in capitals ("stw" → STW, "#b7c3cf"
  → #B7C3CF), and a swatch of exactly 6 hex digits typed without "#" gets one ("B7C3CF" → #B7C3CF; anything else is left for the
  rule to refuse). Rules with the card's exact messages: name required, at most 32 characters, unique in the workshop ignoring capitals
  (`You already have a colour named "Indigo".`, naming the stored colour); code (Add only) required and `^[A-Z0-9]{2,4}$`, free in the
  workshop (`Code IND is already used by Indigo.`); swatch required and `^#[0-9A-F]{6}$` (`Enter a colour code like #3E4C7E.`);
  position optional, a whole number 0 to 32767 (`Enter a whole number from 0 to 32,767, or leave it empty.`, however it fails).
  Update has no code rule, and a code sent anyway is ignored. Both look-ups go through the `Colour` model, so they stay inside the
  workshop (no `Rule::unique` and no `DB::table` on a workshop table, ADR-0013).
- `CreateColour` (empty position → after the last) and `UpdateColour` (name, swatch, position; never the code). Each catches the one
  unique-index violation a lost race can cause and turns it into the same message on `name` or `code`, as the categories do (F-33).
- `domain.ts`: `Colour`, `ColourRow`, `ColoursSettingsProps`, `ColourFormData` (see Contract). The page stub `settings/colours.tsx`.

**Tests**
- One route dataset: guest → login · another workshop's colour → 404 on update · a member → allowed.
- AC-2, one page test: the props shape (one row), list order, and `usage_count` with Black on 4 products (draft and archived count,
  a deleted one doesn't) and Ecru on none; 40 colours in one list.
- AC-3, one test: Stone wash, "stw", "#b7c3cf", no position → saved as STW / #B7C3CF, 21st after Forest green, toast "Colour created.";
  the same with "B7C3CF" (no "#") → #B7C3CF.
- AC-4, one dataset of refusals, each with its exact message and nothing saved: empty name · "indigo" · 33 characters · empty code ·
  "INDIG" · "I-D" · "IND" (names Indigo) · empty swatch · "blue" · "#FFF" · "FFF" · position "2.5" · "-1" · "40000".
- AC-5, one test: Indigo → "Indigo blue", toast "Colour updated.", code IND unchanged although "XYZ" was sent; the other workshop's
  Indigo unchanged (AC-1: each list is its own). A rename to "INDIGO" (itself) is allowed, to "black" refused.
- One lost-race test: the unique index's violation comes back as the name message.
- Query budget: Settings › Colours stays within 10 queries, the same with 3 and with 40 colours.

> [!example]- Files (13)
> - `app/Http/Controllers/Settings/ColourController.php` (new)
> - `app/Http/Requests/Colours/{StoreColourRequest,UpdateColourRequest}.php` (new)
> - `app/Actions/Colours/{CreateColour,UpdateColour}.php` (new)
> - `app/Policies/ColourPolicy.php` (new)
> - `app/Services/Colours/ColourClash.php` (new: the scoped look-ups by name and code, shared by the rules and the Actions)
> - `routes/settings.php`
> - `resources/js/types/domain.ts`
> - `resources/js/pages/settings/colours.tsx` (stub)
> - `tests/Feature/Colours/ColourSettingsTest.php` (new)
> - `tests/Feature/Performance/QueryBudgetTest.php`

#### INV-1-c · The colour tag and the Settings › Colours page
**Commit:** `feat(colours): the colour tag and the Settings › Colours page (INV-1-c)`

- A shared `ColourSwatch` (a small rounded square filled with the swatch through an inline style, the same in light and dark mode,
  edged with the field-border token, hidden from screen readers) and `ColourTag` (swatch and name, no border). Every later screen
  uses them.
- Settings menu: Colours after Product categories in the Catalogue group, with the full-width layout of the other catalogue pages.
- The page: title, line and **Add colour** as the card says; a table (Colour · Code in the number font · Used by · "Edit Indigo") on a
  laptop and one card per colour below laptop width, **Add colour** full width under the line. "Used by": "4 products", "1 product",
  "Not used". No Status column, no Archive, Restore or Delete. Long names wrap. The window itself comes in INV-1-d (until then **Add
  colour** and "Edit …" do nothing).

**Tests**
- AC-1, one `ColourTag` test: the name is read, the swatch is hidden from screen readers and carries the colour.
- AC-2, one page test: the columns, the list order, the three "Used by" wordings, an "Edit Indigo" button per row, no Status column and
  no Archive, Restore or Delete; the cards show the same (scoped with `within()`).
- AC-2, the settings layout test gains Colours in the Catalogue group, in its place.

> [!example]- Files (7)
> - `resources/js/components/shared/colour-tag.tsx` (new)
> - `resources/js/components/shared/colour-tag.test.tsx` (new)
> - `resources/js/components/settings/colours-table.tsx` (new)
> - `resources/js/pages/settings/colours.tsx`
> - `resources/js/pages/settings/colours.test.tsx` (new)
> - `resources/js/layouts/settings/layout.tsx`
> - `resources/js/layouts/settings/layout.test.tsx`

#### INV-1-d · The Add colour and Edit colour windows
**Commit:** `feat(colours): Add colour and Edit colour windows (INV-1-d)`

- One window for both: Name; Code (Add only) with its hint; Swatch, a native colour well named "Pick swatch colour" beside a text field,
  each following the other, the text shown in capitals; Preview, the tag on a light and on a dark strip, following what is typed or
  picked (a code typed without "#" shows with it; a text that isn't a colour code yet keeps the last good preview); Position
  (optional) with its hint; **Cancel** and **Save colour** with a spinner while saving. Edit: title "Edit Indigo", "Code IND can't be changed." under it, and the hint's extra sentence
  only when `usage_count` > 0 ("…shows on the 3 products that use Indigo.").
- The server's messages show under their fields, the window stays open and the focus goes to the first wrong field, in the window's
  order; on success the toast comes from the server and the window closes.

**Tests**
- AC-3, one test: typing Stone wash, "stw", "#b7c3cf" (and "B7C3CF") shows #B7C3CF in the field, the preview follows, **Save
  colour** posts the values and shows the spinner; **Cancel** closes without a request.
- AC-4, one test: the server's errors show under Name, Code and Position and the focus lands on Name; the window stays open.
- AC-5, one test: Edit Indigo has no Code field, shows "Code IND can't be changed.", the hint sentence with "3 products" for Indigo and
  none for Ecru, and sends the update.

> [!example]- Files (4)
> - `resources/js/components/settings/colour-form-dialog.tsx` (new)
> - `resources/js/components/settings/colour-form-dialog.test.tsx` (new)
> - `resources/js/components/settings/colours-table.tsx`
> - `resources/js/pages/settings/colours.tsx` (opens the window)

#### INV-1-e · Products keep their colours; White for old ones
**Commit:** `feat(products): every product has its colours, White for products recorded before (INV-1-e)`

- Migration `product_colours`: `workshop_id`, `product_id` (`cascadeOnDelete`; products are soft-deleted, so it only fires on a hard
  delete), `colour_id` (`restrictOnDelete`); unique (`product_id`, `colour_id`), an index on `colour_id` (the "Used by" count).
  PostgreSQL only: composite keys (`workshop_id`, `product_id`) → products and (`workshop_id`, `colour_id`) → colours, so the database
  itself refuses a colour of another workshop. The same migration links every product (soft-deleted ones too) to its workshop's first
  colour by position (White). `down()` drops the table.
- `Product::colours()` (in list order, the link row carrying `workshop_id`) and `Colour::products()`.
- Store and Update product: `colours` required, a list of at least one id, distinct, each a colour of the workshop
  (`WorkshopRule::exists`); none (missing key or empty list, also in a multipart body) → "Choose at least one colour." on `colours`.
  `SyncProductColours` replaces the product's colours inside the existing transaction of `CreateProduct` / `UpdateProduct`.
- `ProductResource` gains `colours` (list order); `products.create` and `products.edit` gain the prop `colours` (the workshop's whole
  list, in order). `domain.ts`: `Product.colours`, `ProductFormData.colours`, `ProductCreateProps.colours`.
- Demo seed: each new demo product gets its workshop's first colour (White). Test helpers `validProductPayload()` and `productData()`
  send White.
- Type-compatibility lines in the frontend fixtures (`colours` on the product fixtures) and the product form's initial data (the
  product's colour ids, so Edit product keeps them) land in this commit; the frontend developer writes them in the same wave.

**Tests**
- AC-1, one migration test: two workshops' products, a deleted one included, each get their own workshop's White.
- AC-6, one validation dataset on store and update: no `colours` key · `[]` → "Choose at least one colour." · another workshop's
  colour · an unknown id · the same id twice → refused, nothing saved.
- AC-6, one test: Edit Denim Jacket with Black, Indigo and Light wash replaces White; one test that a failed save (composition
  refused inside the transaction) leaves the colours as they were.
- AC-7, one contract test: picked Light wash, Black, Indigo → the product's `colours` read Black, Indigo, Light wash; the form pages'
  `colours` hold this workshop's list only.
- The query budgets of the product page and Edit product stay within 10.

> [!example]- Files (17, plus the tests that build their own product payloads)
> - `app/Models/Product.php`, `app/Models/Colour.php`
> - `app/Actions/Products/SyncProductColours.php` (new)
> - `app/Actions/Products/{CreateProduct,UpdateProduct}.php`
> - `app/Http/Requests/Products/StoreProductRequest.php`
> - `app/Http/Resources/ProductResource.php`
> - `app/Http/Resources/ColourResource.php` (new)
> - `app/Http/Controllers/ProductController.php` (`create`, `edit`, `show` load the colours)
> - `database/migrations/2026_10_30_200000_create_product_colours_table.php` (new)
> - `database/seeders/DemoProductsSeeder.php`
> - `resources/js/types/domain.ts`
> - `resources/js/test/fixtures/products.ts`, `resources/js/test/fixtures.ts` (frontend, type-compat)
> - `resources/js/components/products/product-form.tsx` (frontend, initial `colours` only)
> - `tests/Pest.php`
> - `tests/Feature/Products/ProductColoursTest.php` (new)
> - `tests/Feature/Products/ProductContractTest.php`
> - The tests that post their own product payloads (found by grep: product writes, photos, workshops, routes) and the older
>   migration tests that roll back the products tables (they roll this migration back first)

#### INV-1-f · The Colours field and the colour picker
**Commit:** `feat(products): choose a product's colours in Add and Edit product (INV-1-f)`

- `ColoursField` in the Details card, after Category and Status: label, hint, the chosen colours as chips in list order (swatch, name,
  ✕ "Remove Indigo"), in a group named "Colours", then an outline **Add colour** with a plus sign; the error under the field.
- `ColourPicker`: a popover under the button on a laptop and a full-screen sheet "Choose colours" with ✕ "Close" on a phone (the
  app's mobile breakpoint). A search field "Search colours" (name or code, capitals ignored), the list in order with check mark, swatch,
  name and the code in grey; "No colour named "teal"." and "Missing one? Add it in Settings › Colours." as plain text when nothing
  matches; "3 chosen" and **Done** in a bar that stays under the list. Enter or a click toggles and the list stays open; **Done** or
  Esc closes it and the focus goes back to **Add colour**. Built as a multi-select listbox (`aria-multiselectable`, `aria-selected` =
  chosen, the active row by `aria-activedescendant`, scrolled into view inside the list, never under the bar), so a screen reader hears
  "Indigo, IND, selected".
- The product form sends `colours` (ids); "Choose at least one colour." under the field puts the focus on **Add colour**. A ✕ only
  changes the form. Phone: chips and their ✕ 44 px, **Add colour** full width on its own row.

**Tests**
- AC-6, one happy test: Edit Denim Jacket shows White; add Black, Indigo, Light wash, remove White, **Save product** → the request
  carries the three ids; nothing is sent before **Save product**.
- AC-6, one error test: with no colour, the server's "Choose at least one colour." shows under the field and the focus is on **Add
  colour**; Add product opens with no chip.
- AC-7, one picker test: "ind" finds Indigo and "BLK" Black; "teal" shows the two plain lines and no link; Enter toggles and the list
  stays open; "3 chosen"; the options are named "Indigo, IND" and marked selected.
- AC-7, one test: **Done** and Esc close the list and put the focus back on **Add colour**; on a phone the sheet "Choose colours" opens
  with "Close" and keeps the focus inside.

> [!example]- Files (8)
> - `resources/js/components/products/colours-field.tsx` (new)
> - `resources/js/components/products/colours-field.test.tsx` (new)
> - `resources/js/components/products/colour-picker.tsx` (new)
> - `resources/js/components/products/colour-picker.test.tsx` (new)
> - `resources/js/components/products/product-form.tsx`
> - `resources/js/pages/products/{create,edit}.tsx` (pass `colours`)
> - `resources/js/pages/products/edit.test.tsx`

#### INV-1-g · The Colours line on the product page
**Commit:** `feat(products): the Colours line on the product page (INV-1-g)`

- In the header, under the badges row: grey "Colours", then the product's tags in list order, wrapping; the whole line is read as
  "Colours: Black, Indigo, Light wash". Nothing else on the page changes.
- One browser golden path: add Stone wash in Settings › Colours, choose it in Edit product, see it on the product page.

**Tests**
- AC-7, one header test: "Colours" with Black, Indigo, Light wash in that order and the spoken text above.
- AC-1, the product page test shows "Colours White" for a product with White.
- One browser test (`composer test:browser`), as above.

> [!example]- Files (5)
> - `resources/js/components/products/product-header.tsx`
> - `resources/js/components/products/product-header.test.tsx`
> - `resources/js/pages/products/show.tsx`
> - `resources/js/pages/products/show.test.tsx`
> - `tests/Browser/ColourGoldenPathTest.php` (new)

### Contract (frozen before dispatch)
Routes, all behind `auth` + `verified`; ids `[0-9]{1,18}`; another workshop's colour → 404:
- `GET settings/colours` → `settings.colours.index` → page `settings/colours`, props `ColoursSettingsProps`.
- `POST settings/colours` → `settings.colours.store`, body `ColourFormData` → back, toast "Colour created.".
- `PUT settings/colours/{colour}` → `settings.colours.update`, body `name`, `swatch`, `position` (a `code` is ignored) → back, toast
  "Colour updated.".
- `products.store` / `products.update` also take `colours: number[]`; errors on `colours` ("Choose at least one colour.") and on
  `colours.N` (an id that isn't one of the workshop's colours).
- `products.create` / `products.edit` also send `colours: Colour[]`; the deferred `product` of `products.show` carries `colours`.

`resources/js/types/domain.ts`:
```ts
/** One colour of the workshop's list (INV-1). */
export type Colour = {
    id: number;
    code: string; // 2 to 4 of A-Z 0-9, unique in the workshop, never changes (INV-2 builds variant SKUs from it)
    name: string; // at most 32 characters, unique in the workshop ignoring capitals
    swatch: string; // '#RRGGBB', capitals
};
export type ColourRow = Colour & { position: number; usage_count: number }; // non-deleted products with the colour, any status
export type ColoursSettingsProps = { colours: ColourRow[] }; // list order: position, then name, then id
export type ColourFormData = { code: string; name: string; swatch: string; position: string }; // code on create only; '' = last

// Added to existing types:
// Product            colours: Colour[]   in list order; at least one for every product saved through the form
// ProductFormData    colours: number[]   colour ids
// ProductCreateProps colours: Colour[]   the workshop's whole list, in list order (ProductEditProps inherits it)
```

### Decisions
- **A list per workshop from the start** (the card), although units and categories are still shared until S-45: `colours` and
  `product_colours` carry `workshop_id` and the usual guards (scope, `WorkshopRule`, the arch test, composite keys on PostgreSQL).
  Recorded in ADR-0018.
- **Code names say `Colour`** (`Colour`, `colours`, `product_colours`), the word the screens use, which also keeps them apart from the
  material's free-text `color` field.
- **Products point at colours by id**, as compositions point at materials; the fixed code is for people and for INV-2's SKUs.
- **The 20 defaults live twice on purpose:** a frozen copy in the migration (for today's workshops) and the app's copy in
  `CreateDefaultColours` (for new workshops); a test checks that a signed-up workshop matches a migrated one. Changing the defaults
  later changes only new workshops.
- **Colours are a page prop, not part of the shared `reference` prop**: only the product form and Settings need them, and nothing has
  to be refreshed when one is added.
- **Every test workshop gets the 20 too** (`WorkshopFactory`), so tests see what a real workshop has.
- **Chips in the form follow the list order too** (the UI card: "the same on every product"), whatever order they were picked in.
- **Demo data:** demo products get White, like the products of today.
- **The colour picker is not built on the `Command` component:** it marks the highlighted row `aria-selected`, so a screen reader
  would say "selected" for a colour that isn't chosen. It is a plain listbox.

### Order
Wave 1 (backend): INV-1-a, then INV-1-b (one developer). · Wave 2, in parallel: backend INV-1-e · frontend INV-1-c, INV-1-d, then the
type-compatibility lines of INV-1-e (disjoint files; the contract is frozen above). · Wave 3 (frontend): INV-1-f, then INV-1-g.
Commits INV-1-e and INV-1-f are pushed together: in between, the server asks for colours that the product form can't send yet.

### Risks
- Every product payload in the tests now needs a colour → the two helpers send White; the developer greps the files that build their
  own payloads and runs the whole suite, not only the new tests.
- Twenty more rows for every test workshop → one insert per workshop; a test that expects a new workshop to hold nothing but its
  account is adjusted (rule R6 changes in the rules file, INV-1-a).
- Adding `colours` to `Product` breaks typed fixtures → the type-compat lines go in the same commit (Wave 2).
- Product page and Edit product gain one or two queries against a budget of 10 → the budget tests run in INV-1-e; if a page goes over,
  Edit product's own colours are read as ids and matched against the list it already sends.
- "Indigo" against "indigo": SQLite's `lower()` folds only plain letters, PostgreSQL's all → the same index and rule on both, as the
  categories; the parity suite runs the refusals.
- The live database `laravel` must never be reset → both data steps are migrations run with `php artisan migrate` (the human runs it
  before the click-through; developers don't migrate it). `down()` drops the tables, which forgets the chosen colours.
- Older migration tests that roll back the products or workshop tables meet the new foreign keys → they roll the two new migrations
  back first, as S-56 did.
- A native colour well gives `#rrggbb` in small letters, and people may type the code without "#" → the text field shows it with "#"
  and in capitals; the server does both again before validating.
