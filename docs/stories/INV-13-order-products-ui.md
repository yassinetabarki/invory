---
id: INV-13
title: Search products and see stock when taking an order
type: ui
priority: Must
status: Approved
created: 2026-11-01
tags:
  - ui-card
---

# INV-13 · Search products and see stock when taking an order — UI card

> [!abstract] Description
> When the owner takes an order, the Products part of New order answers "Do we have this jacket in Indigo M, or must we make it?"
> before she adds anything. One **Search products** box finds a product by name, SKU, colour or barcode and shows the **shelf stock
> of every colour and size**; **Add to order** puts it on the order as a grid of quantities per colour and size, with the stock
> repeated under each field. An order line now holds **colour and size**, not size alone.
> New: Search products and its results, the stock tables, the quantity grid per colour and size with "In stock" and "Out of stock"
> under each field, the order totals. Changed: the product picker of New order and Edit order (it listed every product with no stock
> information), and the order lines, which gain a colour.
> This is the second of three cards that refactor the order (INV-12 is the client, INV-14 the tracker). An order is served from the
> finished stock: the search is how the owner **checks the shelf** when she takes the order. Out of stock never blocks an order; the
> units simply wait until Progress or a restock puts them on the shelf.
> Left out on purpose: setting shelf stock aside for an order (open orders don't reserve stock), a sale or a restock from here,
> and a stock figure per colour for "Can produce now" (it is per product).

> [!info] How it looks today
> New order has a Products card where each line is a product picker (a list of every product, with no stock figure) and one quantity
> per size: "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)". An order line has no colour. Nothing on the screen says whether the
> jacket is on the shelf: the owner opens the Denim Jacket page, reads its Sizes & colours card ("Indigo M 0"), and comes back to
> the order.
> INV-5's approved card draws a grid "Quantity per colour and size" for the order form as a part "to be discussed"; this card
> adopts it, as decided.

## Pages

Today is 30 Oct 2026. Shelf stock used in every example (S · M · L · XL), from the Sizes & colours cards:

| Product              | Colour     | S | M | L | XL |
| -------------------- | ---------- | - | - | - | -- |
| Denim Jacket         | Black      | 1 | 0 | 2 | 1  |
| Denim Jacket         | Indigo     | 2 | 0 | 3 | 1  |
| Denim Jacket         | Light wash | 0 | 0 | 0 | 0  |
| Denim Jeans 5-pocket | Indigo     | 0 | 5 | 2 | 0  |
| Denim Jeans 5-pocket | Black      | 1 | 2 | 0 | 0  |

- The Denim Jacket: "In stock 10 units", "Can produce now 112". The Denim Jeans 5-pocket: "In stock 10 units", "Can produce now 64".

### How stock is shown
- **In stock** means the shelf holds more than 0 of that colour and size now. **Out of stock** means 0. The numbers are the finished
  stock of INV-2 and INV-3.
- **Out of stock never blocks the order**, and no warning appears when the typed number is higher than the stock: those units wait
  for the shelf to be filled. Only the amber words tell her.
- **Open orders don't set stock aside.** If two orders ask for the last 2 Indigo S, both show "In stock 2"; saving an order takes
  nothing off the shelf (that happens when the units are marked delivered, INV-14).
- **"Can produce now 112 from materials"** is per product, never per colour: every colour and size uses the same materials.
- A stock figure never moves because of this screen: recording or editing an order changes no stock, no material and no sale.

### New order · the Products card
Board: INV-12 · New order · product search with stock per colour and size, quantity grid, states · desktop (Invory App UI canvas)
The card sits between Client (INV-12) and Details (Order date, Note, as today). Top to bottom:
1. Heading **Products** and "Search what the client wants, check what is in stock, then add it."
2. **Search products** (a visible label; placeholder "Name, SKU, colour or barcode"; a search icon; a clear button named "Clear
   search"). Hint: "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of stock can still
   be ordered."
   - It finds a product by name, SKU, a variant SKU ("PRD-JKT-001-IND-M"), a colour name or a barcode number (INV-8), so a label read
     out in a message finds the product.
3. **Results**, only while there is a search: "2 products found", then one block per product, up to 6 ("Showing 6 of 14. Keep typing
   to narrow the list." when there are more). Each block shows:
   - a small photo, the name (bold) and the SKU (number font);
   - a grey line "In stock 10 units · Can produce now 112 from materials";
   - the outline button **Add to order** (named "Add Denim Jacket to this order");
   - the table "Stock of Denim Jacket by colour and size": one row per colour (swatch and name, in the order of Settings ›
     Colours), one column per size, each cell the number (number font) or an amber **Out**. A "One size" product has the columns
     Colour · Stock.
   - Typing "den" shows Denim Jacket (Black 1 · 0 · 2 · 1, Indigo 2 · **Out** · 3 · 1, Light wash Out in every size) and Denim Jeans
     5-pocket (Indigo Out · 5 · 2 · Out, Black 1 · 2 · Out · Out).
   - A product already on the order shows **On this order** with a check in place of **Add to order**; pressing it moves the focus to
     that line.
4. **Add to order** adds the product to the list below and puts the focus in its first quantity field.
5. **On this order** (heading with "· 2 products"): one block per product with
   - the name, the SKU, "Can produce now 112 from materials" and the button **Remove** (named "Remove Denim Jacket from this order");
   - the table "Quantity per colour and size · Denim Jacket": rows are colours, columns are the sizes and **Total**; **each cell is a
     quantity field with its stock under it** in small text: "In stock 2" in grey, or **Out of stock** in amber and bold. A "One
     size" product: the columns Colour · Quantity;
   - under the table, "Total 12 units".
   - After the products, a grey band: "Total on this order · 22 units · 2 products".
6. The owner types Indigo S 4 and Indigo M 8 on the Jacket (M is out of stock, so it will be made), adds the Jeans and types Indigo
   M 10: "Total 12 units" on the Jacket, "Total 10 units" on the Jeans, "Total on this order · 22 units · 2 products". An empty cell or
   0 means no units in that colour and size.
7. Next to **Save order** and **Cancel**: "22 units · 2 products".

An order holds at most 50 products and a line at most 1,000,000 units; each cell takes a whole number from 0 to 1,000,000.

### Edit order
Board: INV-12 · New order · product search with stock per colour and size, quantity grid, states · desktop (Invory App UI canvas)
- The same Products part and the same grid, with the order's lines filled in. A cell whose units were delivered or cancelled shows
  "At least 10" under the field, after the stock line, and can't go lower.
- A line with delivered or cancelled units can't be removed. The Client field is INV-4's picker (see INV-12).
- **Save order** → "Order updated.", as today.

### How the order's lines read elsewhere
- The Orders list and the client page write a line "50 × Denim Jacket (Indigo S 10 · M 20 · L 15 · XL 5)"; with two colours "Black
  M 1 · Indigo S 1 · M 2" (INV-5's wording).
- **Orders recorded before this card** keep their quantities and sizes and read as the product's first colour in Settings ›
  Colours order, the way INV-2 moved stock into the first colour. The owner can correct them in Edit order.

## States
- **Search just opened:** only the box and its hint; no list.
- **Results:** "2 products found" and the blocks; beyond 6, "Showing 6 of 14. Keep typing to narrow the list."
- **No match:** "No product matches "denm xyz"." · "Check the spelling, or search by SKU, colour or barcode."
- **Searching:** the box stays; a placeholder block (photo, two lines, a table) and "Searching…".
- **Search failed:** "Couldn't search the products." · **Try again**; the lines already on the order stay.
- **Already added:** **On this order** (check) in place of **Add to order**.
- **50 products on the order:** **Add to order** can't be pressed; beside it "An order can hold up to 50 products." (read by screen
  readers and the keyboard).
- **No product yet:** "No products on this order yet" · "Search for a product above and add what the client ordered."
- **Typing:** "Total 12 units" per product; the band "Total on this order · 22 units · 2 products"; "22 units · 2 products" beside the
  buttons.
- **Errors on save** (under their field or line, the focus on the first one): "Indigo M: Enter a whole number from 0 to 1,000,000." ·
  "Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order." · "A line can hold up to 1,000,000 units." · "The
  colours or sizes of Denim Jacket have changed. Enter its quantities again." · with no product: "Add at least one product to this
  order."
- **Edit order, started cell:** "At least 10" under the field and "Indigo M can't be less than the 10 units already delivered or
  cancelled."
- **Saving:** **Save order** shows a spinner and keeps its label. **Save failed:** "Couldn't save the order. Try again." Everything
  typed stays.
- **Long content:** a 60-character product name wraps in the result and in the block's header; 12 colours make 12 rows; 11 shoe sizes
  scroll inside their table (a labelled region), never the page; 7-digit counts fit the cells.

## Wording
- "Products" · "Search what the client wants, check what is in stock, then add it." · "Search products" · "Name, SKU, colour or
  barcode" · "Clear search" · "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of
  stock can still be ordered." · "2 products found" · "Showing 6 of 14. Keep typing to narrow the list." · "In stock 10 units" ·
  "Can produce now 112 from materials" · "Stock of Denim Jacket by colour and size" · "Add to order" · "Add Denim Jacket to this order" ·
  "On this order" · "Out" · "In stock 2" · "Out of stock" · "Quantity per colour and size · Denim Jacket" · "Total 12 units" ·
  "Total on this order · 22 units · 2 products" · "Remove" · "Remove Denim Jacket from this order" · "At least 10" · "An order can
  hold up to 50 products." · "No products on this order yet" · "Search for a product above and add what the client ordered." ·
  "Searching…" · "Couldn't search the products." · "Try again".
- Kept: "Details" · "Order date" · "Note (optional)" · "Save order" · "Cancel" · "Order recorded." · "Order updated.".
- Gone: the product picker that lists every product, and the per-size quantity row.

## Phone layout
Board: INV-12 · New order, order page, Cancel units, Orders list · 375 (Invory App UI canvas)
- **Search results:** each result is a box: the name and SKU, "In stock 10 units · Can produce now 112 from materials", then one group
  per colour (swatch and name) with a chip per size: "S 1", "M" and the amber "Out", "L 2". A colour with nothing in any size reads
  "Out of stock in every size". **Add to order** full width (44 px).
- **Quantities:** per colour a band (swatch, name, the colour's total), under it the sizes **three to a row**: the size above, the
  44 px field, "In stock 2" or "Out of stock" under it. **Remove** is a 44 px icon button named "Remove Denim Jacket from this order".
- **Save order** in the bar fixed at the bottom, with "22 units · 2 products" above it once there are products. The bar never hides
  the field with the focus.
- At 320 px nothing scrolls sideways.
