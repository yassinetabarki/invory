---
id: INV-3
title: Restock finished products
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
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
