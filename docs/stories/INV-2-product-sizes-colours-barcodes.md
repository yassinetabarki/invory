---
id: INV-2
title: Product sizes × colours, stock
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

# INV-2 · Product sizes × colours, stock

> [!abstract] Description
> As a **workshop owner**, I want **every colour and size of a product to have its own finished stock**, so that **I know exactly what
> is on the shelf in Indigo, size M**. A **variant** is one colour in one size: the Denim Jacket in Indigo, size M. This is the first of
> three cards that share one UI card: [[INV-2-product-sizes-colours-barcodes-ui]].
> Left out on purpose: barcodes (INV-8) and printing labels (INV-9), so the Sizes & colours card shows the stock table only; orders,
> sales and "Put in stock" per colour; scanning; **Restock** and the "Last restock" line, which arrive with INV-3; clients (INV-4);
> production progress (INV-5); a composition per colour (an Indigo and a Black jacket use the same materials, so "Can produce now"
> keeps "any mix of sizes"); and stock on the Products list.

> [!info] How it works today
> The Denim Jacket page shows "Can produce now 112 units", "In stock 10 units" with the chips [S 3] [M 0] [L 5] [XL 2] and an **Update
> stock** button at the top right of that figure, and "Unit cost TND 35.754". Stock is counted per size only: nothing says how many are
> Indigo and how many Black. "Update stock · Denim Jacket" has one field per size, "was 0" under a changed size, "Total after saving 10
> units" and the note "Doesn't change materials, "Can produce now" or sales."; **Save** shows "Stock updated.".
> Since INV-1 every product has at least one colour (White for products recorded before it), shown under the header badges as
> "Colours ■ Black ■ Indigo ■ Light wash", and Edit product lets you take any colour off freely. Each colour has a short code that
> never changes (Black BLK, Indigo IND, Light wash LWH).
> No product has a stock count per colour. "Delete product?" reads "Denim Jacket (SKU PRD-JKT-001) and its composition will be removed.
> This cannot be undone.".

## Acceptance criteria
- [ ] **AC-1 · Stock is counted per colour and size.** Every colour and size of a product has its own count of finished units on the
  shelf, 0 to 1,000,000. When INV-2 arrives, the units a product had per size go into its first colour, in the order of Settings ›
  Colours: the Denim Jacket with S 3 · M 0 · L 5 · XL 2 and only White reads White S 3 · M 0 · L 5 · XL 2, and its other colours start
  at 0. There is no "No colour" stock. Nothing else changes: "Can produce now 112 units", "Unit cost TND 35.754", the materials and
  their history, the Inventory value, sales and orders stay as they are; a sale or an order never changes finished stock. Deleting a
  product deletes its stock. A product with units in stock, in any colour, still can't move to a category with other sizes (the
  rule of today's sizes, counted over all colours). **A colour with stock can't be taken off a product:** on Edit product, taking
  Indigo (6 units in stock) off the Denim Jacket and clicking **Save product** saves nothing and shows under the Colours field
  "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first."; a colour at 0 is removed as
  before; if Indigo is added again later, the product's Indigo count starts at 0.
- [ ] **AC-2 · The In stock figure and the Sizes & colours card.** With Black 1 · 0 · 2 · 1, Indigo 2 · 0 · 3 · 1 and Light wash 0 ·
  0 · 0 · 0 (S · M · L · XL):
  - **In stock** reads "10 units", then one line in the order of Settings › Colours, a colour tag and its count in bold: "■ Black **4**
    · ■ Indigo **6** · ■ Light wash 0" (a colour at 0 in the grey text colour, its "0" still shown); screen readers hear "In stock
    per colour: Black 4, Indigo 6, Light wash 0". Under it the link **By size and colour**, which moves to the Sizes & colours card.
    **Update stock** is no longer on this figure. At 0 units: "0 units", no colour line, the link stays.
    Loading: today's placeholder labelled "In stock". Error: "Couldn't load the stock." with **Try again**, which reloads the figure
    and the card together. 12 colours wrap onto more lines and a 32-character colour name wraps. "Can produce now" and "Unit cost"
    are unchanged.
  - **Sizes & colours** is a full-width card under the three figures, with the heading and the line "3 colours × 4 sizes = 12
    variants. Each variant has its own stock." (other products: "1 colour × 4 sizes = 4 variants", "2 colours × One size = 2
    variants"). On the heading's row: the outline buttons **Manage sizes & colours**, which opens Edit product with the focus on the
    Colours field, and **Update stock**. Under the heading the stock table. While loading: the frame and heading at once, a
    placeholder of 3 rows; error: "Couldn't load the stock." with **Try again**.
  - **The stock table** is named "Units in stock per colour and size": **Colour** (swatch and name), one column per size in size
    order (S, M, L, XL; a shoe 36 … 46), **Total**; one row per colour in the order of Settings › Colours, and a last row **Total**
    under a heavier line: Black 1 · 0 · 2 · 1 = 4; Indigo 2 · 0 · 3 · 1 = 6; Light wash 0 · 0 · 0 · 0 = 0; Total 3 · 0 · 5 · 2 = 10.
    Figures in the number font, right-aligned, a 0 in the readable grey. Under it: "Grey zeros are variants with nothing on the
    shelf." (not shown when every cell is 0, where every cell is a grey 0). A "One size" product (Leather Tote in Camel and Black)
    has the columns **Colour** and **In stock**, no size columns and no Total column; the Total row stays with two colours or more.
    A shoe's 11 size columns scroll sideways inside the card on a laptop under 1100 px, never the page.
  - **Phone, 375 px:** the figure sits under Can produce now and its colour line wraps; in the card the buttons **Update stock** and
    **Manage sizes & colours** are full width, 44 px, one under the other; in place of the table, one card per colour ("■ Indigo · 6
    units" with the chips [S 2] [M 0] [L 3] [XL 1], zeros kept and grey), then a **Total** card "10 units" with the size totals as
    chips; nothing scrolls sideways at 320 px.
- [ ] **AC-3 · Update stock, per colour.** **Update stock** in the card opens "Update stock · Denim Jacket" with the line "Finished
  units on the shelf, counted per colour and size. Saving sets these counts; to add units that arrived, use Restock." and the ✕
  "Close".
  - **Colour** pills (swatch, name, and the colour's total after saving in grey: "Black 4", "Indigo 6", "Light wash 0"), the first
    colour chosen when the window opens. A colour with a changed size gets a small dot that screen readers hear as "changed"
    ("Indigo, 10, changed").
  - The fieldset "Units in stock per size · Indigo" holds one field per size (today's size fields) with today's count, 2 · 0 · 3 · 1,
    and "was 0" under a changed size. Switching pills keeps what was typed in every colour.
  - A grey band "Total after saving, all colours" **10 units**, following what is typed in every colour; the note "Doesn't change
    materials, "Can produce now" or sales.". Indigo M from 0 to 4 → "was 0" under M, a dot on Indigo, the pill "Indigo 10", the
    total "14 units".
  - Buttons **Cancel** and **Save**. **Save** shows a spinner, saves every colour at once (the last save wins), shows "Stock
    updated.", closes the window and puts the focus back on **Update stock**; the card and the In stock figure show the new counts.
    No history entry is written.
  - Errors: a wrong number shows under its size "M: Enter a whole number from 0 to 1,000,000."; when it is in another colour, that
    pill shows "1 error" in red text, the top of the window reads "Check Black: 1 size can't be saved.", and the window switches
    to the first colour with an error and puts the focus on its field. If the product's sizes or colours changed meanwhile, the top
    reads "The sizes or colours of Denim Jacket have changed. Enter the stock again.", the fields show the new colours and sizes
    with their counts, and nothing is saved. A failed save shows "Couldn't save the stock. Try again." and keeps what was typed in
    every colour.
  - A "One size" product has no pills: one field per colour, labelled with the swatch and the name ("■ Camel", "■ Black"), in a
    fieldset "Units in stock per colour".
  - **Phone:** the window fills the screen; the pills wrap at the top, the size fields four to a row, the total band, then **Save**
    and **Cancel** in a bar fixed at the bottom.
