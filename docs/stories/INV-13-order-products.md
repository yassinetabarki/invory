---
id: INV-13
title: Search products and see stock when taking an order
type: feature
phase: Colour × size variants
priority: Must
size: M
status: Ready
created: 2026-11-03
tags:
  - story
  - variants
---

# INV-13 · Search products and see stock when taking an order

> [!abstract] Description
> As a **workshop owner**, I want **to search a product while I take an order and see its shelf stock for every colour and size**,
> so that **I can answer "Do we have this jacket in Indigo M, or must we make it?" before I add it, and record the order in the exact
> colours and sizes the client wants**.
> New: the **Search products** box on New order and Edit order, its results with the stock of each colour and size, **Add to order**,
> and a quantity grid per colour and size with the stock under each field. Changed: an order line now holds **colour and size**, not
> size alone, wherever an order's lines are written (Orders list, order page, client page). The pages are drawn in
> [[INV-13-order-products-ui]].
> Left out on purpose: setting shelf stock aside for an order (open orders don't reserve stock), a sale or a restock from New order,
> a "Can produce now" figure per colour (it stays per product), and the order tracker (Delivered, Cancel units), which is INV-14.

> [!info] How it works today
> New order has a Products card where each line is a picker "Choose a product…" listing every active product, with no stock figure,
> then "How many units of each size?" with one field per size. **Add product** adds another line. An order line has no colour: the
> Orders list reads "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)". To know whether the jacket is on the shelf, the owner leaves the
> order, opens the Denim Jacket page, reads its Sizes & colours card ("Indigo M 0") and comes back.
> The order page shows "Denim Jacket: units by size and stage" with To start · In cutting · Ready to ship · Shipped per size, and its
> **Start cutting**, **Mark ready**, **Ship** and Move back buttons. In Edit order a size can't go below its started units ("M can't be
> less than the 10 units already in cutting, ready to ship or shipped.") and a started line can't be removed ("Denim Jacket can't be
> removed: 30 units have started.").

## Acceptance criteria

Today is 30 Oct 2026. Shelf stock used in every example (S · M · L · XL):

| Product              | Colour     | S | M | L | XL |
| -------------------- | ---------- | - | - | - | -- |
| Denim Jacket         | Black      | 1 | 0 | 2 | 1  |
| Denim Jacket         | Indigo     | 2 | 0 | 3 | 1  |
| Denim Jacket         | Light wash | 0 | 0 | 0 | 0  |
| Denim Jeans 5-pocket | Indigo     | 0 | 5 | 2 | 0  |
| Denim Jeans 5-pocket | Black      | 1 | 2 | 0 | 0  |

The Denim Jacket (SKU PRD-JKT-001) reads "In stock 10 units" and "Can produce now 112"; the Denim Jeans 5-pocket "In stock 10 units"
and "Can produce now 64". Settings › Colours lists Black, Indigo, Light wash in that order.

- [ ] **AC-1 · Search products.** New order's Products card reads "Products" · "Search what the client wants, check what is in
  stock, then add it." and has the box **Search products** (placeholder "Name, SKU, colour or barcode", a **Clear search** button)
  with the hint "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of stock can still be
  ordered."
  - It finds **active** products by name, SKU, a variant SKU ("PRD-JKT-001-IND-M"), a colour name or a barcode number. Typing "den"
    shows "2 products found": Denim Jacket and Denim Jeans 5-pocket.
  - Each result shows its photo, name and SKU, "In stock 10 units · Can produce now 112 from materials", **Add to order** (read as
    "Add Denim Jacket to this order") and the table "Stock of Denim Jacket by colour and size": one row per colour in Settings ›
    Colours order, one column per size, each cell the number or an amber **Out**. Denim Jacket reads Black 1 · Out · 2 · 1, Indigo
    2 · Out · 3 · 1, Light wash Out in every size. A "One size" product has the columns Colour · Stock.
  - At most 6 results; beyond, "Showing 6 of 14. Keep typing to narrow the list."
  - States: before typing, only the box and its hint; while searching, a placeholder block and "Searching…"; no match, "No product
    matches "denm xyz"." · "Check the spelling, or search by SKU, colour or barcode."; a failure, "Couldn't search the products." ·
    **Try again**, with the lines already on the order kept.

- [ ] **AC-2 · Quantities per colour and size.** **Add to order** puts the product under "On this order · 1 product" and moves the
  focus to its first quantity field; its result then shows **On this order** (with a check), which moves the focus to that line.
  - Each product shows its name, SKU, "Can produce now 112 from materials", **Remove** (read as "Remove Denim Jacket from this order")
    and the table "Quantity per colour and size · Denim Jacket": rows are the product's colours, columns its sizes and **Total**. Each
    cell is a field with its stock under it: "In stock 2" in grey, or **Out of stock** in amber and bold. A "One size" product has the
    columns Colour · Quantity.
  - Typing Indigo S 4 and Indigo M 8 on the Jacket and Indigo M 10 on the Jeans shows "Total 12 units" and "Total 10 units", the band
    "Total on this order · 22 units · 2 products", and "22 units · 2 products" beside **Save order** and **Cancel**.
  - An empty cell or 0 means no units. Out of stock never blocks the order and no warning appears when a number is above the stock.
  - With no product: "No products on this order yet" · "Search for a product above and add what the client ordered."
  - With 50 products on the order, **Add to order** can't be pressed and reads beside it "An order can hold up to 50 products."

- [ ] **AC-3 · Saving the order.** **Save order** records one line per product with its units per colour and size, then shows "Order
  recorded." (or INV-4's toast for a new client) and opens the order page. Saving changes no stock, no material and no sale.
  - Errors appear under their field or line, the focus on the first one: "Indigo M: Enter a whole number from 0 to 1,000,000." ·
    "Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order." · "A line can hold up to 1,000,000 units." ·
    "The colours or sizes of Denim Jacket have changed. Enter its quantities again." · with no product, "Add at least one product to
    this order."
  - While saving, **Save order** shows a spinner and keeps its label. If saving fails: "Couldn't save the order. Try again.", with
    everything typed kept.

- [ ] **AC-4 · Edit order.** Edit order has the same Products part and grid, filled with the order's lines; **Save order** shows "Order
  updated.". A cell whose units have started (in cutting, ready to ship or shipped) shows "At least 10" under its field and can't go
  lower: "Indigo M can't be less than the 10 units already in cutting, ready to ship or shipped." A line with started units can't be
  removed: "Denim Jacket can't be removed: 30 units have started." A product already on the order stays on it even if it was
  archived since.

- [ ] **AC-5 · Lines read with their colour.** The Orders list and the client page write "50 × Denim Jacket (Indigo S 10 · M 20 · L 15
  · XL 5)"; with two colours "4 × Denim Jacket (Black M 1 · Indigo S 1 · M 2)". Until INV-14 replaces it, the order page keeps its
  stages and buttons, each row now a colour and size ("Indigo M"), and **Start cutting**, **Mark ready**, **Ship** and the Move back
  buttons act on that colour and size.

- [ ] **AC-6 · Colours on orders.** An order recorded before this card keeps its quantities, sizes and started units, and reads as
  the product's first colour in Settings › Colours order (Boutique Lina's 50 Denim Jackets read "Black S 10 · M 20 · L 15 · XL 5").
  The owner can correct the colour in Edit order, except for units that have started.
  - In Edit product, taking a colour off a product is refused while open orders hold units of it that are not shipped yet: with 12
    Indigo Denim Jackets ordered and not shipped, "Indigo has 12 units on open orders, so it can't be removed from Denim Jacket."
    (1 unit: "has 1 unit on open orders"). The existing refusals (units on the shelf, units in production) stay as they are.

- [ ] **AC-7 · Phone and keyboard.** At 375 px, a result is a box with one group per colour and a chip per size ("S 1", "M" with the
  amber "Out", "L 2"); a colour with nothing reads "Out of stock in every size"; **Add to order** is full width. The quantities read
  per colour (swatch, name, the colour's total) with three sizes to a row, each field with "In stock 2" or "Out of stock" under it.
  **Save order** sits in a bar fixed at the bottom with "22 units · 2 products" above it, and never hides the field with the focus.
  Every control is at least 44 px and nothing scrolls sideways at 320 px; 11 shoe sizes scroll inside their table, never the page.
  The whole flow works with the keyboard alone, and stock is never shown by colour alone.
