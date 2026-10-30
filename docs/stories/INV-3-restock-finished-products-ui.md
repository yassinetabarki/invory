---
id: INV-3
title: Restock finished products
type: ui
priority: Must
status: Approved
created: 2026-10-25
tags:
  - ui-card
---

# INV-3 · Restock finished products — UI card

> [!abstract] Description
> A **restock** adds finished units that were made or arrived to the shelf, in their colours and sizes, for one or several products
> at once, and keeps a record (date, reference, note, who) that can be undone. **Restock adds; Update stock sets.**
> New: the **Restocks** page (in the sidebar), the **New restock** page with a field to scan or type a barcode, the restock detail and
> the Undo confirm. Changed: the product page's Sizes & colours card (a **Restock** button and a "Last restock" line) and the line
> under the title of the Update stock window.
> Left out on purpose: materials (a restock never changes them), production that takes materials (S-53, INV-5), clients (INV-4),
> progress (INV-5), orders, sales and "Put in stock" per colour, a cost or price on a restock, and scanning with the phone's camera.

> [!info] How it looks today
> Finished units reach the shelf all the time: a batch sewn in the workshop for stock, a box from a subcontractor with a delivery note,
> units a boutique sends back. The only way to show them is **Update stock** in the Sizes & colours card, which *sets* each count: to
> add 8 Indigo M to the Denim Jacket you look up "Indigo M has 0", add 8 in your head and type 8, colour by colour, size by size.
> Nothing records that 8 arrived, when, or from where, and two people counting at once overwrite each other.
> Today the Denim Jacket has:
>
> | Colour     | S | M | L | XL | Total |
> | ---------- | - | - | - | -- | ----- |
> | Black      | 1 | 0 | 2 | 1  | 4     |
> | Indigo     | 2 | 0 | 3 | 1  | 6     |
> | Light wash | 0 | 0 | 0 | 0  | 0     |
> | Total      | 3 | 0 | 5 | 2  | 10    |

## Pages

### Restock and Update stock, side by side

| What           | Restock                                  | Update stock                               |
| -------------- | ---------------------------------------- | ------------------------------------------ |
| What it does   | Adds the units typed to what is there    | Sets each count to what is typed           |
| When           | Units were made or arrived               | After counting the shelf                   |
| Where          | Restocks page, or Restock on the product | The product's Sizes & colours card only    |
| Products       | One or several                           | One                                        |
| Fields start   | Empty, "now 2" under each                | Today's count, "was 0" under a changed one |
| Record         | Yes: date, reference, note, who; undo    | No                                         |
| Button         | "Restock 34 units"                       | "Save"                                     |
| Toast          | "34 units restocked."                    | "Stock updated."                           |

- A restock never changes materials, "Can produce now", the unit cost, margin, sales, orders or the Inventory value.
- Draft and archived products can be restocked like active ones.
- If someone saves Update stock while a restock was saved in between, the Update stock save wins, as today (the last save wins).

### Sidebar
- A new entry **Restocks** with a package-plus icon, after Products. Breadcrumbs: "Restocks", "Restocks › New restock".

### Restocks page
Board: INV-3 · Restocks list · default, empty, no match, loading, error · desktop (Invory App UI canvas)
- Header **Restocks**, description "Finished units that were made or arrived, added to the shelf in their colours and sizes.", main
  button **New restock** with a plus sign.
- Filters: the search "Search reference, note or product" and the list **Product** ("All products", then the products by name). Both
  are kept in the page address. 25 restocks a page, newest first, with "Showing 1–25 of 48".
- Table: **Date** ("25 Oct 2026") · **Reference** ("BL-0412", "—" when empty) · **Products** ("Denim Jacket 24 · Denim Jeans 5-pocket
  10"; more than two: "Denim Jacket 24 · Leather Tote 6 and 2 more") · **Units** (number font, right-aligned: "34") · **By** ("Admin")
  · the row's button **Open**, named "Open restock of 25 Oct 2026, BL-0412". A click on the row opens the same.
- An undone restock: the grey badge **Undone** after its reference, its units in the grey text colour.
- The detail opens in a panel on the right, so the list stays in view; the open restock is kept in the page address, so a link opens
  it.
- Example rows:

| Date        | Reference            | Products                                  | Units | By    |
| ----------- | -------------------- | ----------------------------------------- | ----- | ----- |
| 25 Oct 2026 | BL-0412              | Denim Jacket 24 · Denim Jeans 5-pocket 10 | 34    | Admin |
| 22 Oct 2026 | —                    | Leather Tote 6                            | 6     | Sami  |
| 18 Oct 2026 | Return Boutique Lina | Classic White T-shirt 12 · Undone         | 12    | Admin |

### New restock page
Board: INV-3 · New restock · colour × size grid, scan, errors · desktop (Invory App UI canvas)
Top to bottom, one column, at most about 960 px wide:
1. Header **New restock**, description "Add finished units that were made or arrived. They are added to what is on the shelf."
2. **Details** card, one row: **Date** (today; not after today) · **Reference (optional)** (hint "Delivery note, batch or who made
   them", up to 64 characters) · **Note (optional)** (one line, up to 255 characters).
3. **Scan or type a barcode** card: a field with a barcode icon, labelled "Scan or type a barcode", the outline button **Add 1**, and
   the hint "Each scan adds one unit to its colour and size. A barcode scanner works like typing." Under it, the line of the last
   result (see States). The focus stays in the field after each scan, so the next label can be scanned at once.
4. **Products**: one card per product, in the order added:
   - Header: the product name (bold), its SKU in the number font ("PRD-JKT-001"), "Sizes S, M, L, XL"; on the right "Adding **24**
     units" and a ✕ button named "Remove Denim Jacket from this restock".
   - Table "Units to add · Denim Jacket": first column **Colour** (swatch and name), one column per size in size order (S, M, L, XL; a
     shoe 36 … 46), last column **Adding** (the colour's units, number font; "—" when none). One row per colour of the product, in the
     order of Settings › Colours.
   - Each cell: a size field as in S-70 (small, centred, number font, 44 px high, 7 digits), empty at first, and under it in grey
     "now 2" (the units of that colour and size on the shelf). A typed cell gets the Denim border of a changed field.
   - **"One size" product** (Leather Tote in Camel and Black): the table has the columns **Colour** · **Units to add**, one field per
     colour with "now 4" under it; no Adding column, the header's total is enough.
   - The line's messages go under its table (see States).
5. **Add product** (outline, plus sign) opens the product picker: the search "Search products", each option the name, the SKU in grey
   and, for a draft or archived product, its status badge. A product already on the restock shows a check, and choosing it moves the
   focus to its line.
6. Grey band: "Total on this restock" **34 units** · "2 products".
7. Note: "Adds to what is on the shelf. Doesn't change materials, "Can produce now" or sales. To set a count after a stock check, use
   Update stock on the product's page."
8. Buttons on the right: **Cancel** (outline) and **Restock 34 units** (main). The count follows what is typed; with nothing typed the
   button reads **Restock**.
- **Saving:** **Restock 34 units** saves everything at once → toast "34 units restocked." and the Restocks list opens with the new
  restock on top. One wrong field saves nothing.
- How a variant is named in text: the colour, then the size as in S-60: "Indigo M", "Black, size 38"; a "One size" product by the
  colour alone ("Camel"). A message about one field starts with that name.

### Restock detail
Board: INV-3 · Restock detail, undo, product page · desktop (Invory App UI canvas)
- A panel on the right, about 560 px wide. Title **Restock · 25 Oct 2026**; under it "BL-0412 · recorded by Admin on 25 Oct 2026 at
  14:32". The ✕ "Close".
- Figures line: "**34 units** · 2 products".
- The note, if any, in a grey box: "Batch sewn by Atelier Nour, delivered with the invoice."
- One block per product: the name as a link to its page, and the SKU; a table **Colour** · S · M · L · XL · **Total**, only the colours
  that got units, in the order of Settings › Colours (Black 0 · 4 · 0 · 0 = 4, Indigo 4 · 8 · 6 · 2 = 20), every 0 grey; a Total row
  (4 · 12 · 6 · 2 = 24) when two colours or more got units.
- Footer: **Undo restock** (outline, red text) and **Close**.
- **Undo restock** asks first (see States), then takes the whole restock back off the shelf. It works at any time, as long as every
  colour and size it added still has at least that many units on the shelf. The restock stays in the list, marked **Undone**.

### Product page · Sizes & colours card
Board: INV-3 · Restock detail, undo, product page · desktop (Invory App UI canvas)
- The heading's row: **Manage sizes & colours** (outline) · **Update stock** (outline) · **Restock** (main, plus sign). **Restock**
  opens New restock with the Denim Jacket in it and the focus in its first field (Black S); saving or cancelling comes back to the
  product page, and saving shows "24 units of Denim Jacket restocked." with the new counts.
- Under the Stock by colour table, after "Grey zeros are variants with nothing on the shelf.": "Last restock: 25 Oct 2026, 24 units ·
  **See the restocks of Denim Jacket**" (a link to Restocks with that product chosen). Never restocked: "Not restocked yet.", with no
  link. An undone restock doesn't count as the last one.
- **Update stock** window: the line under its title becomes "Finished units on the shelf, counted per colour and size. Saving sets
  these counts; to add units that arrived, use Restock."

## States

### Restocks page
- **Default:** the three example rows, "Showing 1–25 of 48".
- **Empty:** "No restocks yet" · "Each time finished units are made or arrive, add them here. They go on the shelf in their colours
  and sizes." · **New restock**.
- **No match:** "No restock matches "BL-9"." · **Clear filters**.
- **Loading:** the header, filters and table head at once; 5 placeholder rows.
- **Error:** "Couldn't load the restocks." · **Try again**.
- **Long content:** a 64-character reference and a 60-character product name are cut with "…", the full text in the detail;
  "1,000,000" units fit.

### New restock page
- **Just opened:** Details filled with today; the scan field; in place of the products: "Add the products that were made or arrived, or
  scan their labels." and **Add product**; the band "Total on this restock 0 units"; the button **Restock**.
- **From a product page:** the Denim Jacket's line already there, the focus in its first field (Black S).
- **Typed:** Denim Jacket Indigo S 4 · M 8 · L 6 · XL 2 and Black M 4 → "Adding 24 units"; Denim Jeans 5-pocket Indigo M 6 · L 4 →
  "Adding 10 units"; the band "34 units · 2 products"; **Restock 34 units**.
- **Scanned:** a green line with a check: "Added 1 · Denim Jacket · Indigo · M · 8 on this restock"; the Indigo M field ringed in Denim
  until the next scan.
- **Scan of a product not yet on the restock:** its line is added at the end with that one unit: "Added 1 · Leather Tote · Camel · 1 on
  this restock".
- **Shared barcode:** a small choice under the scan field: "2000000000022 is Indigo M on 2 products named Denim Jacket. Which one?" ·
  **PRD-JKT-001** · **PRD-JKT-002**. If one of them is already on this restock, the unit goes there and nothing is asked.
- **Unknown barcode:** red line: "No product has the barcode 2000000000992. Check the label, or enter the units below."
- **Not a barcode:** red line: "A barcode has 13 digits. Check the number."
- **Colour no longer on the product:** red line: "2000000000077 is Light wash L of Denim Jacket, which no longer comes in Light wash."
- **Scan failed:** red line: "Couldn't look up the barcode. Try again."
- **Field error:** a red border on the field; under the line's table: "Indigo M: Enter a whole number from 0 to 1,000,000."; at the top
  of the page: "Check Denim Jacket: 1 field can't be saved."; the focus on the first wrong field.
- **Over the limit:** under the table: "Indigo M: This would put more than 1,000,000 units on the shelf."
- **A line with nothing typed:** under the table: "Enter at least one unit of Denim Jacket, or remove it from this restock."
- **Nothing typed at all:** under the band: "Enter at least one unit to restock."
- **Details:** "The date can't be in the future." · "Keep the reference to 64 characters or fewer." · "Keep the note to 255 characters
  or fewer."
- **Changed meanwhile:** at the top: "The sizes or colours of Denim Jacket have changed. Enter its units again." The line shows the new
  colours and sizes; nothing is saved.
- **Product deleted meanwhile:** at the top: "Denim Jacket was deleted. Remove it from this restock." Nothing is saved.
- **Save failed:** "Couldn't save the restock. Try again." Everything typed stays.
- **Saving:** **Restock 34 units** shows a spinner and keeps its label.
- **Cancel with units typed:** a confirm "Discard this restock?" · "The 34 units you entered aren't added to the shelf." · **Discard
  restock** · **Keep editing**. With nothing typed, Cancel leaves at once.
- **Success:** toast "34 units restocked." (one unit: "1 unit restocked.") and the Restocks list, the new restock on top; from a product
  page: "24 units of Denim Jacket restocked." and that page with its new counts.
- **Read-only:** none.
- **Long content:** a shoe line has 11 size columns: on a laptop under 1100 px the line's table scrolls sideways inside its card, never
  the page; 12 colours make 12 rows; a 51st product is refused: "A restock can hold up to 50 products."

### Restock detail and undo
- **Default:** as in Pages, with **Undo restock** in the footer.
- **Loading:** the title and the figures line at once, the product blocks as placeholders.
- **Error:** "Couldn't load this restock." · **Try again**.
- **Confirm:** **Undo this restock?** · "Takes the 34 units of 25 Oct 2026 (BL-0412) back off the shelf: Denim Jacket 24, Denim Jeans
  5-pocket 10. The restock stays in the list, marked Undone." · **Undo restock** (red) · **Keep restock**. Without a reference: "Takes
  the 6 units of 22 Oct 2026 back off the shelf: Leather Tote 6. The restock stays in the list, marked Undone."
- **Undoing:** **Undo restock** shows a spinner and keeps its label.
- **Success:** toast "Restock undone."; the panel shows the undone state; the row reads **Undone**.
- **Can't be undone:** **Undo restock** can't be pressed, with the reason in text above it: "This restock can't be undone: Denim Jacket
  in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update stock instead." The button
  stays reachable with the keyboard, and its name includes the reason.
- **Refused meanwhile:** someone changed the stock while the confirm was open: the confirm shows the same reason in red, and nothing
  changes.
- **Undone:** the badge **Undone** by the title; "Undone by Sami on 26 Oct 2026. Its 34 units were taken back off the shelf." No Undo
  button.
- **A deleted product:** its block reads "Denim Jacket (deleted)", with no link; undo takes back the other products' units and leaves
  the deleted one's (its stock went with it).

### Product page
- **Default:** "Last restock: 25 Oct 2026, 24 units · See the restocks of Denim Jacket".
- **Never restocked:** "Not restocked yet.", with no link.
- **Loading and error:** part of the card, as in INV-2 ("Couldn't load the stock and barcodes." · **Try again**).

## Wording
- Sidebar and breadcrumbs: "Restocks" · "New restock".
- Restocks page: "Restocks" · "Finished units that were made or arrived, added to the shelf in their colours and sizes." · "New
  restock" · "Search reference, note or product" · "Product" · "All products" · "Date" · "Reference" · "Products" · "Units" · "By" ·
  "Open" · "Open restock of 25 Oct 2026, BL-0412" · "and 2 more" · "Undone" · "No restocks yet" · "Each time finished units are made or
  arrive, add them here. They go on the shelf in their colours and sizes." · "No restock matches "BL-9"." · "Clear filters" · "Couldn't
  load the restocks." · "Try again".
- New restock: "New restock" · "Add finished units that were made or arrived. They are added to what is on the shelf." · "Details" ·
  "Date" · "Reference (optional)" · "Delivery note, batch or who made them" · "Note (optional)" · "Scan or type a barcode" · "Add 1" ·
  "Each scan adds one unit to its colour and size. A barcode scanner works like typing." · "Added 1 · Denim Jacket · Indigo · M · 8 on
  this restock" · "2000000000022 is Indigo M on 2 products named Denim Jacket. Which one?" · "No product has the barcode 2000000000992.
  Check the label, or enter the units below." · "A barcode has 13 digits. Check the number." · "2000000000077 is Light wash L of Denim
  Jacket, which no longer comes in Light wash." · "Couldn't look up the barcode. Try again." · "Products" · "Add product" · "Search
  products" · "Add the products that were made or arrived, or scan their labels." · "Units to add · Denim Jacket" · "Colour" ·
  "Adding" · "Units to add" · "now 2" · "Adding 24 units" · "Remove Denim Jacket from this restock" · "Total on this restock" · "34
  units" · "2 products" · "Adds to what is on the shelf. Doesn't change materials, "Can produce now" or sales. To set a count after a
  stock check, use Update stock on the product's page." · "Cancel" · "Restock 34 units" · "Restock".
- New restock messages: "Indigo M: Enter a whole number from 0 to 1,000,000." · "Indigo M: This would put more than 1,000,000 units on
  the shelf." · "Enter at least one unit of Denim Jacket, or remove it from this restock." · "Enter at least one unit to restock." ·
  "Check Denim Jacket: 1 field can't be saved." · "The date can't be in the future." · "Keep the reference to 64 characters or fewer."
  · "Keep the note to 255 characters or fewer." · "The sizes or colours of Denim Jacket have changed. Enter its units again." · "Denim
  Jacket was deleted. Remove it from this restock." · "A restock can hold up to 50 products." · "Couldn't save the restock. Try
  again."
- Discard confirm: "Discard this restock?" · "The 34 units you entered aren't added to the shelf." · "Discard restock" · "Keep
  editing".
- Detail: "Restock · 25 Oct 2026" · "BL-0412 · recorded by Admin on 25 Oct 2026 at 14:32" · "34 units · 2 products" · "Total" · "Undo
  restock" · "Close" · "Couldn't load this restock." · "Undone by Sami on 26 Oct 2026. Its 34 units were taken back off the shelf." ·
  "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right
  count with Update stock instead." · "Denim Jacket (deleted)".
- Undo confirm: "Undo this restock?" · "Takes the 34 units of 25 Oct 2026 (BL-0412) back off the shelf: Denim Jacket 24, Denim Jeans
  5-pocket 10. The restock stays in the list, marked Undone." · "Undo restock" · "Keep restock".
- Product page: "Restock" · "Last restock: 25 Oct 2026, 24 units" · "See the restocks of Denim Jacket" · "Not restocked yet." · in
  Update stock: "Finished units on the shelf, counted per colour and size. Saving sets these counts; to add units that arrived, use
  Restock."
- Toasts: "34 units restocked." · "24 units of Denim Jacket restocked." · "1 unit restocked." · "Restock undone."
- The verbs stay the same through each flow: **Restock** → **Restock 34 units** → "34 units restocked."; **Undo restock** → "Undo
  this restock?" → **Undo restock** → "Restock undone."

## Phone layout
Board: INV-3 · Restocks, New restock, detail · 375 (Invory App UI canvas)
- **Restocks:** the header and **New restock** full width; the search, then **Filters** ("Filters · 1" when a product is chosen),
  which opens a panel with the Product list. One card per restock: the date and reference as its title, the products line, "34 units"
  in bold, "by Admin", and the **Undone** badge when undone; the whole card opens the detail.
- **New restock:** one column. Details (Date, Reference, Note) one under another; the scan field full width with **Add 1** under it;
  each product card: name and SKU, "Adding 24 units", ✕; then one group per colour (swatch and name as its title, "Adding 20" on the
  right), its size fields four to a row with "now 2" under each (a shoe's 11 sizes on three rows); **Add product** full width. A bar
  fixed at the bottom: "Total 34 units" and **Restock 34 units** full width; **Cancel** sits above the bar, at the end of the form. The
  bar never hides the field with the focus.
- **Detail:** the panel fills the screen; each product's table stays narrow (four sizes fit; a shoe's table scrolls inside its box);
  **Undo restock** full width at the bottom.
- **Confirms:** full width, buttons one under the other, the confirm button first.
- **Product page:** **Restock** (main), **Update stock** and **Manage sizes & colours** full width, 44 px, one under the other.
- At 320 px the page never scrolls sideways.
