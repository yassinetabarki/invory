# INV-3 · Restock finished products — design spec
Status: proposal · Designer · 2026-10-25 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc), new row
"Restock finished products (INV-3)":
- "INV-3 · Restocks list · default, empty, no match, loading, error · desktop" (`project/Inv3Restocks.dc.html`)
- "INV-3 · New restock · colour × size grid, scan, errors · desktop" (`project/Inv3NewRestock.dc.html`)
- "INV-3 · Restock detail, undo, product page · desktop" (`project/Inv3RestockDetail.dc.html`)
- "INV-3 · Restocks, New restock, detail · 375" (`project/Inv3Phone.dc.html`)

Builds on the approved UI cards of INV-1 (colours, always swatch + name, Settings order) and INV-2 (stock per colour × size, Update
stock in the Sizes & colours card, in-store EAN-13 barcodes shared by name + colour + size). Every example uses the INV-2 Denim
Jacket (Black, Indigo, Light wash; S, M, L, XL) with today's stock:

| Colour     | S | M | L | XL | Total |
| ---------- | - | - | - | -- | ----- |
| Black      | 1 | 0 | 2 | 1  | 4     |
| Indigo     | 2 | 0 | 3 | 1  | 6     |
| Light wash | 0 | 0 | 0 | 0  | 0     |
| Total      | 3 | 0 | 5 | 2  | 10    |

## Problem and user
Finished units reach the shelf all the time: a batch sewn in the workshop for stock, a box from a subcontractor with a delivery note,
units a boutique sends back. Today the only way to show them is **Update stock**, which *sets* each count: the owner has to look up
"Indigo M has 0", add the 8 that arrived in her head and type 8, colour by colour, size by size, and nothing records that 8 arrived,
when, or from where. Two people counting at once overwrite each other.

The owner restocks on a **phone in the stockroom**, box open, often scanning labels, and on a **laptop** when typing a delivery note.
The one question the new screen answers: **"These units arrived: add them to the shelf in their colours and sizes, and keep a record."**

**Restock adds; Update stock sets.** That one sentence drives the wording below.

## Flow
1. **Sidebar › Restocks** (new, after Products). The page lists every restock, newest first: date, reference, products, units, who.
   Main button **New restock**.
2. **New restock** opens a page. Date (today), Reference and Note at the top; under them **Scan or type a barcode**; then the
   products. **Add product** picks a product (search by name or SKU).
3. Each product shows a **colour × size grid**: one row per colour (Settings order), one column per size, each cell a small field for
   the units to add, with "now 2" under it (what is on the shelf). The line's header follows what is typed: "Adding 24 units".
4. **Or scan.** Each scan of a label adds one unit to its colour and size: the product's line appears if it isn't there yet, the cell
   goes up by one and is ringed, and a line confirms "Added 1 · Denim Jacket · Indigo · M · 8 on this restock".
5. The grey band reads "Total on this restock **34 units** · 2 products". The note says "Adds to what is on the shelf. Doesn't change
   materials, "Can produce now" or sales."
6. **Restock 34 units** saves everything at once → toast "34 units restocked." and the Restocks list opens with the new restock on top.
   From a product page the restock goes back to that page and the toast names it: "24 units of Denim Jacket restocked."
7. **A mistake?** Open the restock (a side sheet over the list) → **Undo restock** → "Undo this restock?" → **Undo restock** →
   "Restock undone." The restock stays in the list, marked **Undone**.
8. **From a product page:** the Sizes & colours card gets **Restock**, which opens New restock with that product already in it.

## Options and recommendation

### A · Where it lives
- (a) **A Restocks page in the sidebar (list + New restock for one or several products), and Restock on the product page, which opens
  New restock with that product in it** — **recommended.** One delivery note often holds several products; scanning a mixed box
  needs one place that takes any label; the list is the record. The product page keeps a one-click start.
- (b) Product page only: a Restock window per product (like Update stock) and a history tab in the Sizes & colours card. Cheapest,
  but a box of jackets and jeans becomes two restocks, scanning has to know the product first, and there is no place to see all of them.
- (c) Both a Restocks page and a separate Restock window on the product page. Fastest for one product, but two forms that must stay
  the same, and two ways to do one thing.

### B · How quantities are entered
- (a) **A colour × size grid per product, every colour visible** — **recommended.** The whole delivery for a product is on screen at
  once, Tab moves across sizes then down colours, and a missed colour is visible. Unlike Update stock's pills, nothing is hidden, which
  also helps tell the two apart.
- (b) Colour pills like Update stock: pick Indigo, type its sizes, pick Black. Familiar, compact, but the units typed in another colour
  are hidden behind a pill.
- (c) One line per variant (colour, size, units, "Add line"). Good for one or two variants, slow for a box of 30.

### C · Scan or type a barcode (optional part)
- (a) **A "Scan or type a barcode" field on New restock; a USB or Bluetooth scanner types the 13 digits and Enter, so it works like
  typing; no phone camera** — **recommended, as an optional part the product-owner can drop.** Cheap on screen; one server lookup.
- (b) Leave scanning out of INV-3; quantities are typed only. The field can come later without changing the grid.
- (c) (a) plus scanning with the phone's camera. Most convenient in the stockroom, but a camera reader is a new piece and costly.

### D · History
- (a) **A record of every restock (the Restocks list and its detail), restocks only; Update stock still writes no history (S-56)** —
  **recommended.**
- (b) Also record each Update stock save as a "Stock count" entry beside the restocks, so the shelf has a full history. Better trail,
  but it changes S-56's rule and the list would need a type column.
- (c) No record. Then a wrong restock can't be found or undone.

### E · Undo or correction
- (a) **Undo a whole restock, at any time, as long as every colour and size it added still has at least that many units on the shelf;
  the restock stays listed, marked Undone** — **recommended.** Simple, all or nothing, nothing disappears from the record.
- (b) Edit a restock (change its numbers, Invory applies the difference). Flexible but hard to follow and to explain.
- (c) No undo: fix the count with Update stock. Leaves a wrong entry in the record.

### F · Materials
- **Restock never touches materials** — **recommended, and the only option drawn.** Units made in the workshop already took their
  materials out of stock when they were cut (S-53); units from a subcontractor or sent back never used your materials. Taking materials
  here would count them twice. Production that consumes materials outside an order is INV-5 / S-53 territory.

### G · The product page's buttons
- (a) **Restock becomes the main button of the Sizes & colours card; Update stock becomes an outline button** — **recommended.** Adding
  arrivals is the everyday action; counting the shelf is occasional. This changes the approved INV-2 card.
- (b) Keep Update stock as the main button, Restock as an outline button.

## Layout

### Sidebar
- New entry **Restocks** (lucide "package-plus" icon), after Products. Breadcrumbs: "Restocks", "Restocks › New restock".

### Restocks page (desktop)
- `PageHeader`: **Restocks**, description "Finished units that were made or arrived, added to the shelf in their colours and sizes.",
  main button **New restock** (plus icon).
- `FiltersBar`: search "Search reference, note or product" · select **Product** ("All products", then products by name). Both in the page
  address. 25 per page, `Pagination` ("Showing 1–25 of 48").
- Table (shadcn table): **Date** ("25 Oct 2026") · **Reference** ("BL-0412", "—" when empty) · **Products** ("Denim Jacket 24 · Denim
  Jeans 5-pocket 10"; more than two: "Denim Jacket 24 · Leather Tote 6 and 2 more") · **Units** (number font, right: "34") · **By**
  ("Admin") · the row's button **Open** named "Open restock of 25 Oct 2026, BL-0412". The row click opens the same.
- An undone restock: the neutral badge **Undone** after its reference, its units in the grey text colour.
- The detail opens in a `Sheet` on the right, so the list stays in view; the open restock is kept in the page address, so a link opens it.

Example rows:

| Date        | Reference           | Products                                 | Units | By    |
| ----------- | ------------------- | ---------------------------------------- | ----- | ----- |
| 25 Oct 2026 | BL-0412             | Denim Jacket 24 · Denim Jeans 5-pocket 10 | 34    | Admin |
| 22 Oct 2026 | —                   | Leather Tote 6                           | 6     | Sami  |
| 18 Oct 2026 | Return Boutique Lina | Classic White T-shirt 12 · Undone        | 12    | Admin |

### New restock page (desktop)
Top to bottom, one column, max about 960 px:
1. `PageHeader` **New restock**, description "Add finished units that were made or arrived. They are added to what is on the shelf."
2. **Details** card, one row: **Date** (date field, today, not after today) · **Reference (optional)** (hint "Delivery note, batch or
   who made them", 64 characters) · **Note (optional)** (one line, 255 characters).
3. **Scan or type a barcode** card (optional part, option C): a field with a barcode icon, labelled "Scan or type a barcode", the
   outline button **Add 1**, and the hint "Each scan adds one unit to its colour and size. A barcode scanner works like typing." Under it
   the last result line (see States). The focus stays in the field after each scan, so the next label can be scanned at once.
4. **Products**: one card per product, in the order added:
   - Header: the product name (bold), its SKU in the number font ("PRD-JKT-001"), "Sizes S, M, L, XL"; on the right "Adding **24**
     units" and an icon button ✕ named "Remove Denim Jacket from this restock".
   - Table "Units to add · Denim Jacket": first column **Colour** (row headers: swatch and name), one column per size in size order
     (S, M, L, XL; a shoe 36 … 46), last column **Adding** (the colour's units, number font; "—" when none). One row per colour of the
     product, in Settings order.
   - Each cell: a size field as in S-70 (small, centred, number font, 44 px high, 7 digits), empty by default, and under it in grey
     "now 2" (the variant's units on the shelf, from the server). A typed cell gets the Denim border of a changed field.
   - **"One size" product** (Leather Tote in Camel and Black): the table has the columns **Colour** · **Units to add** (one field per
     colour, "now 4" under it). No Adding column; the header total is enough.
   - Messages for the line go under its table (see States).
5. **Add product** (outline, plus icon) opens the product picker (popover + command): search "Search products", each option the name,
   the SKU in grey and, for a draft or archived product, its status badge. A product already on the restock shows a check and choosing
   it moves the focus to its line.
6. Grey band: "Total on this restock" **34 units** · "2 products".
7. Note: "Adds to what is on the shelf. Doesn't change materials, "Can produce now" or sales. To set a count after a stock check, use
   Update stock on the product's page."
8. Buttons, right: **Cancel** (outline) and **Restock 34 units** (main). The count follows what is typed; with nothing typed the
   button reads **Restock**.

### Restock detail (sheet, about 560 px wide)
- Title **Restock · 25 Oct 2026**; under it "BL-0412 · recorded by Admin on 25 Oct 2026 at 14:32". The ✕ "Close".
- Figures line: "**34 units** · 2 products".
- The note, if any, in a grey box: "Batch sewn by Atelier Nour, delivered with the invoice."
- One block per product: the name as a link to its page and the SKU; a table **Colour** · S · M · L · XL · **Total**, only the
  colours that got units, in Settings order (Black 0 · 4 · 0 · 0 = 4, Indigo 4 · 8 · 6 · 2 = 20), every 0 grey; a Total row (4 · 12 ·
  6 · 2 = 24) when two colours or more.
- Footer: **Undo restock** (outline, red text) and **Close**.

### Product page (changes to the approved INV-2 card)
- **Sizes & colours** card, heading row: **Manage sizes & colours** (outline) · **Update stock** (outline) · **Restock** (main, plus
  icon), option G (a). **Restock** opens New restock with Denim Jacket in it; saving or cancelling comes back to the product page.
- Under the Stock by colour table, after "Grey zeros are variants with nothing on the shelf.": "Last restock: 25 Oct 2026, 24 units ·
  **See the restocks of Denim Jacket**" (a link to Restocks with the product filter). Never restocked: "Not restocked yet."
- **Update stock** window: its line under the title becomes "Finished units on the shelf, counted per colour and size. Saving sets
  these counts; to add units that arrived, use Restock."

## States

### Restocks page

| State          | What the screen shows                                                                                                                        |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| Default        | The three example rows, "Showing 1–25 of 48"                                                                                                  |
| Empty          | `EmptyState`: "No restocks yet" · "Each time finished units are made or arrive, add them here. They go on the shelf in their colours and sizes." · **New restock** |
| No match       | "No restock matches "BL-9"." · **Clear filters**                                                                                             |
| Loading        | Header, filters and table head at once; 5 skeleton rows                                                                                      |
| Error          | "Couldn't load the restocks." · **Try again**                                                                                                |
| Long content   | A 64-character reference and a 60-character product name are cut with "…", the full text in the detail; "1,000,000" units fit              |

### New restock page

| State                 | What the screen shows                                                                                                                                                  |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Empty (just opened)   | Details filled with today; scan field; in place of the products: "Add the products that were made or arrived, or scan their labels." and **Add product**; band "Total on this restock 0 units"; button **Restock** |
| From a product page   | Denim Jacket's line already there, focus in its first field (Black S)                                                                                                  |
| Default (typed)       | Denim Jacket Indigo S 4 · M 8 · L 6 · XL 2, Black M 4 → "Adding 24 units"; Denim Jeans 5-pocket Indigo M 6 · L 4 → "Adding 10 units"; band "34 units · 2 products"; **Restock 34 units** |
| Scanned               | Green line (success tone, check icon): "Added 1 · Denim Jacket · Indigo · M · 8 on this restock"; the Indigo M field ringed in Denim until the next scan                |
| Scan of a new product | Its line is added at the end with that one unit; the line says so: "Added 1 · Leather Tote · Camel · 1 on this restock"                                               |
| Shared barcode        | A small choice under the scan field: "2000000000060 is Indigo M on 2 products named Denim Jacket. Which one?" · **PRD-JKT-001** · **PRD-JKT-002**. If one of them is already on this restock, the unit goes there and nothing is asked |
| Unknown barcode       | Red line: "No product has the barcode 2000000000999. Check the label, or enter the units below."                                                                      |
| Not a barcode         | Red line: "A barcode has 13 digits. Check the number."                                                                                                                 |
| Colour no longer made | Red line: "2000000000077 is Light wash L of Denim Jacket, which no longer comes in Light wash." (its barcode is kept, INV-2)                                          |
| Scan failed           | Red line: "Couldn't look up the barcode. Try again."                                                                                                                   |
| Field error           | Red border on the field; under the line's table: "Indigo M: Enter a whole number from 0 to 1,000,000." At the top of the page: "Check Denim Jacket: 1 field can't be saved." Focus on the first wrong field |
| Over the limit        | Under the table: "Indigo M: This would put more than 1,000,000 units on the shelf."                                                                                    |
| A line with nothing   | Under the table: "Enter at least one unit of Denim Jacket, or remove it from this restock."                                                                            |
| Nothing at all        | Under the band: "Enter at least one unit to restock."                                                                                                                  |
| Date                  | "The date can't be in the future." · Reference: "Keep the reference to 64 characters or fewer." · Note: "Keep the note to 255 characters or fewer."                   |
| Changed meanwhile     | At the top: "The sizes or colours of Denim Jacket have changed. Enter its units again." The line shows the new colours and sizes; nothing is saved                      |
| Product deleted meanwhile | At the top: "Denim Jacket was deleted. Remove it from this restock." Nothing is saved                                                                              |
| Save failed           | "Couldn't save the restock. Try again." Everything typed stays                                                                                                         |
| Saving                | **Restock 34 units** shows a spinner and keeps its label                                                                                                               |
| Cancel with units     | Confirm "Discard this restock?" · "The 34 units you entered aren't added to the shelf." · **Discard restock** · **Keep editing**. With nothing typed, Cancel leaves at once |
| Success               | Toast "34 units restocked." and the Restocks list, the new restock on top; from a product page: "24 units of Denim Jacket restocked." and that page with its new counts |
| Read-only             | None. Draft and archived products can be restocked like active ones (question 6)                                                                                      |
| Long content          | A shoe line has 11 size columns: on a laptop under 1100 px the line's table scrolls sideways inside its card, never the page; 12 colours = 12 rows; 50 products at most: "A restock can hold up to 50 products." |

How a variant is named in text: the colour, then the size as in S-60: "Indigo M", "Black, size 38"; a "One size" product the colour alone
("Camel"). A message about one field starts with that name.

### Restock detail and undo

| State           | What the screen shows                                                                                                                                                                  |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Default         | As in Layout, **Undo restock** in the footer                                                                                                                                           |
| Loading         | Title and figures line at once, product blocks as skeletons                                                                                                                            |
| Error           | "Couldn't load this restock." · **Try again**                                                                                                                                          |
| Confirm         | Alert dialog **Undo this restock?** · "Takes the 34 units of 25 Oct 2026 (BL-0412) back off the shelf: Denim Jacket 24, Denim Jeans 5-pocket 10. The restock stays in the list, marked Undone." · **Undo restock** (red) · **Keep restock** |
| Undoing         | **Undo restock** shows a spinner and keeps its label                                                                                                                                   |
| Success         | Toast "Restock undone."; the sheet shows the Undone state; the row reads **Undone**                                                                                                    |
| Can't be undone | **Undo restock** can't be pressed, with the reason in text above it: "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update stock instead." The button stays reachable by keyboard and its name includes the reason |
| Refused meanwhile | Someone changed the stock while the confirm was open: the confirm shows the same reason in red and nothing changes                                                                    |
| Undone (read-only) | Badge **Undone** by the title; "Undone by Sami on 26 Oct 2026. Its 34 units were taken back off the shelf." No Undo button                                                         |
| Deleted product | Its block reads "Denim Jacket (deleted)" with no link; undo takes back the other products' units and leaves the deleted one's (its stock went with it)                               |

### Product page
- **Default:** "Last restock: 25 Oct 2026, 24 units · See the restocks of Denim Jacket".
- **Never restocked:** "Not restocked yet." and no link.
- **Loading / error:** part of the card, as INV-2 ("Couldn't load the stock and barcodes." · **Try again**).

## Wording
- Sidebar and breadcrumbs: "Restocks" · "New restock".
- Restocks page: "Restocks" · "Finished units that were made or arrived, added to the shelf in their colours and sizes." · "New restock"
  · "Search reference, note or product" · "Product" · "All products" · "Date" · "Reference" · "Products" · "Units" · "By" · "Open" ·
  "Open restock of 25 Oct 2026, BL-0412" · "and 2 more" · "Undone" · "No restocks yet" · "Each time finished units are made or arrive,
  add them here. They go on the shelf in their colours and sizes." · "No restock matches "BL-9"." · "Clear filters" · "Couldn't load
  the restocks." · "Try again".
- New restock: "New restock" · "Add finished units that were made or arrived. They are added to what is on the shelf." · "Details" ·
  "Date" · "Reference (optional)" · "Delivery note, batch or who made them" · "Note (optional)" · "Scan or type a barcode" · "Add 1" ·
  "Each scan adds one unit to its colour and size. A barcode scanner works like typing." · "Added 1 · Denim Jacket · Indigo · M · 8 on
  this restock" · "2000000000060 is Indigo M on 2 products named Denim Jacket. Which one?" · "No product has the barcode 2000000000999.
  Check the label, or enter the units below." · "A barcode has 13 digits. Check the number." · "2000000000077 is Light wash L of Denim
  Jacket, which no longer comes in Light wash." · "Couldn't look up the barcode. Try again." · "Products" · "Add product" · "Search
  products" · "Add the products that were made or arrived, or scan their labels." · "Units to add · Denim Jacket" · "Colour" · "Adding"
  · "Units to add" · "now 2" · "Adding 24 units" · "Remove Denim Jacket from this restock" · "Total on this restock" · "34 units" · "2
  products" · "Adds to what is on the shelf. Doesn't change materials, "Can produce now" or sales. To set a count after a stock check,
  use Update stock on the product's page." · "Cancel" · "Restock 34 units" · "Restock".
- New restock messages: "Indigo M: Enter a whole number from 0 to 1,000,000." · "Indigo M: This would put more than 1,000,000 units on
  the shelf." · "Enter at least one unit of Denim Jacket, or remove it from this restock." · "Enter at least one unit to restock." ·
  "Check Denim Jacket: 1 field can't be saved." · "The date can't be in the future." · "Keep the reference to 64 characters or fewer."
  · "Keep the note to 255 characters or fewer." · "The sizes or colours of Denim Jacket have changed. Enter its units again." · "Denim
  Jacket was deleted. Remove it from this restock." · "A restock can hold up to 50 products." · "Couldn't save the restock. Try again."
- Discard confirm: "Discard this restock?" · "The 34 units you entered aren't added to the shelf." · "Discard restock" · "Keep editing".
- Detail: "Restock · 25 Oct 2026" · "BL-0412 · recorded by Admin on 25 Oct 2026 at 14:32" · "34 units · 2 products" · "Total" · "Undo
  restock" · "Close" · "Couldn't load this restock." · "Undone by Sami on 26 Oct 2026. Its 34 units were taken back off the shelf." ·
  "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right
  count with Update stock instead." · "Denim Jacket (deleted)".
- Undo confirm: "Undo this restock?" · "Takes the 34 units of 25 Oct 2026 (BL-0412) back off the shelf: Denim Jacket 24, Denim Jeans
  5-pocket 10. The restock stays in the list, marked Undone." · "Undo restock" · "Keep restock". Without a reference: "Takes the 6 units
  of 22 Oct 2026 back off the shelf: Leather Tote 6. …"
- Product page: "Restock" · "Last restock: 25 Oct 2026, 24 units" · "See the restocks of Denim Jacket" · "Not restocked yet." · Update
  stock: "Finished units on the shelf, counted per colour and size. Saving sets these counts; to add units that arrived, use Restock."
- Toasts: "34 units restocked." · "24 units of Denim Jacket restocked." · "1 unit restocked." · "Restock undone."
- The verbs stay the same through each flow: **Restock** → **Restock 34 units** → "34 units restocked."; **Undo restock** → "Undo this
  restock?" → **Undo restock** → "Restock undone."

## Restock and Update stock, side by side

|                  | Restock                                   | Update stock                                   |
| ---------------- | ----------------------------------------- | ---------------------------------------------- |
| What it does     | Adds the units typed to what is there     | Sets each count to what is typed               |
| When             | Units were made or arrived                | After counting the shelf                       |
| Where            | Restocks page, or Restock on the product  | The product's Sizes & colours card only        |
| Products         | One or several                            | One                                            |
| Fields start     | Empty, "now 2" under each                 | Today's count, "was 0" under a changed one     |
| Record           | Yes: date, reference, note, who; undo     | No (S-56), question 4                          |
| Button           | "Restock 34 units"                        | "Save"                                         |
| Toast            | "34 units restocked."                     | "Stock updated."                               |

## Accessibility
- **Grid (1.3.1, 4.1.2):** a real table with column headers (sizes) and row headers (colours); each field's name is "Units to add,
  Indigo, M" and its description "now 0". Tab goes across a colour's sizes, then the next colour. On a phone each colour is a fieldset
  "Indigo · units to add" with labelled size fields.
- **Colour never alone (1.4.1):** swatch with its name in every row, block and message; the scanned cell has a 2 px Denim ring **and**
  the result line names it; "Undone" is a word; errors are words with a red border.
- **Contrast (1.4.3, 1.4.11):** "now 2" and grey zeros in the grey text colour (#5F5F66 on white 6.3:1; #A1A1AA on the dark card 7.0:1),
  never the boards' paler grey; field borders the field grey (3.2:1); success line (`--success`) #0B5C4B on #E6F4EF 7.0:1, dark
  #7ED9BF on #12332B 8.2:1; error line (`--danger`) #A3221A on #FDE8E6 6.4:1, dark #F7A8A2 on #411917 8.1:1; Undone badge
  (`--neutral`) #3F3F46 on #F0F0EC 9.1:1, dark #D4D4D8 on #2A2A30 9.6:1; the Denim ring #2B3A86 on white 10.2:1.
- **Announcements (4.1.3):** the scan result line is a polite live region; errors under a table and the top summary are announced;
  the toasts. "Adding 24 units" and the band are polite live regions, as "Total after saving" is today.
- **Focus (2.4.3, 2.4.7, 2.4.11):** the scan field keeps the focus after each scan; Add product → focus in the new line's first field;
  removing a line → focus on **Add product**; after an error → the first wrong field; the sheet and dialogs trap focus, Esc closes,
  focus returns to the row's **Open** or to **Undo restock**. Nothing sticky hides the focused field on a phone (the bottom bar leaves
  room).
- **Names (2.4.4, 2.4.6):** "Open restock of 25 Oct 2026, BL-0412", "Remove Denim Jacket from this restock", "See the restocks of Denim
  Jacket"; headings in order (page, Details, Products, each product).
- **Disabled (3.3.2):** **Undo restock** that can't be pressed keeps its reason as visible text, and stays focusable.
- **Targets (2.5.8, Invory 44 px):** fields 44 px high (S-70); buttons 44 px on a phone; the ✕ on a product line 44 px.
- **Redundant entry (3.3.7):** from a product page the product is already on the restock; the date is today.
- **Reflow (1.4.10):** at 320 px the page never scrolls sideways; on a laptop only a shoe line's own table box may scroll, and it is
  named "Units to add · Canvas Low Sneaker".

## Phone (375 px)
- **Restocks:** header and **New restock** full width; search, then **Filters** ("Filters · 1" when a product is chosen) opening a
  sheet with the Product select. One card per restock: date and reference as its title, the products line, "34 units" in bold, "by
  Admin", **Undone** badge when undone; the whole card opens the detail.
- **New restock:** one column. Details (Date, Reference, Note) stacked; the scan field full width with **Add 1** under it; each product
  card: name and SKU, "Adding 24 units", ✕; then one fieldset per colour (swatch and name as its legend, "Adding 20" on the right), its
  size fields four to a row with "now 2" under each (a shoe's 11 sizes on three rows); **Add product** full width. A bar fixed at the
  bottom: "Total 34 units" and **Restock 34 units** full width; **Cancel** sits above the bar, at the end of the form.
- **Detail:** the sheet fills the screen; each product a table that stays narrow (four sizes fit; a shoe's table scrolls inside its box,
  named); **Undo restock** full width at the bottom.
- **Confirms:** full width, buttons one under the other, the confirm button first.
- **Product page:** **Restock** (main), **Update stock** and **Manage sizes & colours** full width, 44 px, one under the other.

## For the product-owner
Proposed content of the UI card `docs/stories/INV-3-restock-finished-products-ui.md`:
- **Description:** a Restock adds finished units that were made or arrived to the shelf, per colour and size, for one or several
  products at once, and keeps a record (date, reference, note, who) that can be undone. New: the Restocks page (sidebar), the New
  restock page, the restock detail sheet, the Undo confirm. Changed: the Sizes & colours card (Restock button, last restock line), the
  Update stock window's line. Left out: materials (a restock never changes them), production taking materials (S-53, INV-5), clients
  (INV-4), progress (INV-5), orders, sales and "Put in stock" per colour, a cost or price on a restock, camera scanning.
- **Pages, States, Wording, Phone:** the sections above, word for word.
- **Proposed acceptance wording for the development card later:**
  - Given Denim Jacket with Indigo M 0, when you restock Indigo M 8 and save, then its page shows Indigo M 8 and "In stock 18 units",
    and "Can produce now" and the unit cost (TND 35.754) don't change.
  - One restock of Denim Jacket 24 and Denim Jeans 5-pocket 10 with reference BL-0412 shows on Restocks as "25 Oct 2026 · BL-0412 ·
    Denim Jacket 24 · Denim Jeans 5-pocket 10 · 34 · Admin".
  - Scanning 2000000000060 twice adds 2 to Denim Jacket Indigo M ("Added 1 · Denim Jacket · Indigo · M · 2 on this restock").
  - Undoing it takes the 34 units back; when Indigo M has fallen to 2, the undo is refused with the sentence above.
  - Saving is all or nothing: one wrong field saves nothing.
- **Rules the development card will need:** a restock adds whole units (0 … 1,000,000 per field, at least one unit, at most
  1,000,000 per variant after adding, at most 50 products); the date is today or earlier; it never changes materials, "Can produce
  now", the unit cost, margin, sales, orders or the Inventory value; undo is all or nothing and refused when any variant has fewer units
  than the restock added; an undone restock stays in the record; Update stock keeps "last save wins" (S-56 Q-H1), also over a restock
  saved in between (question 7).
- INV-2's UI card is `Approved`: the Restock button, the button order and the Update stock line change it, so they need the human's yes.

## For the tech-lead
- **Values the server sends.**
  - Restocks list: per restock its id, date, reference, note, who recorded it and when, the products (name and units; the first two
    plus how many more), total units, and if undone who and when; whether it can be undone and, if not, the first variant that stops it
    (product, colour, size, units on the shelf, units added). Filters: search (reference, note, product name) and product; 25 per page.
  - Detail: per product its name, SKU, whether it is deleted, its sizes in order, the colours that got units in Settings order, units
    per variant, per colour, per size and per product, and the restock total.
  - New restock: the product options (name, SKU, status); for a chosen product its colours in Settings order with swatch, its sizes in
    order, and the units on the shelf per variant ("now 2").
  - Barcode lookup: from 13 digits to the products, colour and size that use it (several when shared by name), or "unknown", or "colour
    no longer on the product".
  - Product page: the date and units of the product's last restock (not undone), or none.
- **Live totals on screen** ("Adding 24 units", "Total on this restock 34 units", "Restock 34 units", "8 on this restock") add the
  whole numbers typed or scanned, display only, exactly as Update stock's "Total after saving" does today (S-56); the server works out
  the stock on save. Say if you'd rather have the server send them.
- **Writes:** save a restock (all or nothing; the "changed meanwhile" check on each product's sizes and colours; deleted products
  refused; the product row locks of ADR-0017, as every finished-stock writer); undo a restock (all or nothing, refused below the units
  added, also when two people act at once). Neither touches materials or their history.
- **Domain:** finished stock now changes by adding as well as by counting; the costing skill's "Finished stock" section needs the
  restock and undo rules (an ADR if you judge it a behaviour change). A restock is a new record kept per workshop (R1).
- **Components:** sheet, table, popover + command, alert-dialog, `FiltersBar`, `Pagination`, `EmptyState`, `Callout`, skeleton, sonner,
  the S-70 size fields: all exist. New: a sidebar entry; possibly a shared "scan or type a barcode" field.
- **Costly or risky:** a product line can hold 12 colours × 11 sizes = 132 fields; the barcode lookup and the shared-barcode choice;
  undo with a deleted product on the restock; the phone layout with a fixed bottom bar over many fields.

## Questions for the product-owner / human
1. Where does restocking live? (a) A Restocks page in the sidebar, plus Restock on the product page opening it with the product
   **recommended** · (b) the product page only, a window per product · (c) both, with two forms · default (a).
2. How are units entered? (a) A colour × size grid, every colour visible **recommended** · (b) colour pills like Update stock · (c) a
   line per variant · default (a).
3. Scan or type a barcode in INV-3? (a) Yes, a field that works with a barcode scanner, no camera **recommended** · (b) not in INV-3 ·
   (c) also the phone's camera · default (a).
4. Is Update stock recorded too? (a) No, only restocks are recorded (S-56 unchanged) **recommended** · (b) yes, as "Stock count"
   entries in the same record · default (a).
5. How is a wrong restock fixed? (a) Undo the whole restock, kept and marked Undone, refused when the shelf has fewer units than it
   added **recommended** · (b) edit a restock · (c) no undo, Update stock only · default (a).
6. Which products can be restocked? (a) Any product, draft and archived included, as INV-2 treats barcodes **recommended** · (b)
   active products only · default (a).
7. A restock saved while someone has Update stock open: (a) the Update stock save still wins (S-56 "last save wins") **recommended** ·
   (b) Update stock is refused with "The stock of Denim Jacket has changed. Enter the stock again." · default (a).
8. The Sizes & colours card's buttons: (a) Restock main, Update stock outline (changes the approved INV-2 card) **recommended** · (b)
   Update stock stays main, Restock outline · default (a).
9. The Update stock window's line: (a) "… Saving sets these counts; to add units that arrived, use Restock." (changes INV-2)
   **recommended** · (b) keep INV-2's line as approved · default (a).
