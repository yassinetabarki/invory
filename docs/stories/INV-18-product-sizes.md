---
id: INV-18
title: Sizes chosen per product, managed in Settings
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
created: 2026-11-06
tags:
  - story
  - variants
---

# INV-18 · Sizes chosen per product, managed in Settings

> [!abstract] Description
> As a **workshop owner**, I want **one list of sizes in Settings, and each product to pick the sizes it really comes in**, so that
> **the Denim Jacket stops carrying XS, XXL and XXXL it never makes, a sneaker made only in 38 to 44 shows 7 sizes instead of 11, and a
> new kind of garment is no longer stuck with "One size"**. The category stops deciding a product's sizes; it only **suggests** them
> when a product is added. Every screen that shows a product's sizes (stock, restocks, barcodes, labels, orders, Progress, Start batch)
> then shows the product's own, in the order set in Settings.
> New: **Settings › Sizes** with Add size and Edit size, a **Sizes** field on Add and Edit product with locked sizes, a **Suggests**
> column and **Suggested sizes** in Settings › Product categories. Changed: the product page's Sizes badge and Sizes & colours card.
> Gone: the line "Sizes:" and the hint "Sizes come with the category." on the product form, and the three category locks. The pages are
> drawn in [[INV-18-product-sizes-ui]].
> Left out on purpose: deleting or archiving a size, a code for each size, groups of sizes, sizes on the Products list or its filters,
> sizes of materials. Material use per colour and size is INV-16, built after this card: the question "Take XL off this product?" and the
> lock "has material quantities" (both drawn in the UI card) arrive with it, because quantities per size don't exist before it.

> [!info] How it works today
> A product's sizes come from its category and nothing in the app can change them: every Apparel product has XS · S · M · L · XL ·
> XXL · XXXL, every Footwear product 36 to 46, every other product "One size". The Denim Jacket (Indigo, Black, Light wash) shows
> "3 colours × 7 sizes = 21 variants", seven size columns in its stock table and seven barcode rows per colour, although the workshop
> only makes it in S to XL. The product form says "Sizes:" and "Sizes come with the category.". A product on an order, with stock or
> in production can't move to another size list ("Denim Jacket is on orders, so its category can't change to one with other sizes.
> Keep a category with sizes XS to XXXL."). Settings › Catalogue holds Units, Material categories, Product categories, Colours and
> Labels, and no sizes.

## Acceptance criteria

The workshop's 19 starting sizes, in order, numbered 10, 20 … 190: XS 10 · S 20 · M 30 · L 40 · XL 50 · XXL 60 · XXXL 70 · 36 80 · 37
90 · 38 100 · 39 110 · 40 120 · 41 130 · 42 140 · 43 150 · 44 160 · 45 170 · 46 180 · One size 190. The **Denim Jacket** (Apparel, Indigo,
Black and Light wash) has XS to XXXL today, with units in stock, on orders and with barcodes in S, M, L and XL; XS, XXL and XXXL hold
nothing. A **Leather Tote** (a category suggesting One size) has "One size".

- [ ] **AC-1 · The list in Settings.** Settings › Catalogue lists Units · Material categories · Product categories · Colours ·
  **Sizes** · Labels. **Sizes** shows the heading, "The sizes your products come in. Each product picks the ones it has. Rename any of
  them, or add your own.", the main button **Add size**, and one table with the caption "Sizes".
  - One row per size in list order, no paging: **Size** (a small outlined tag) · **Position** · **Used by** ("3 products", "1
    product", "Not used") · **Edit**, named "Edit XXL". A 20th size, XXS at position 15, sits between XS (10) and S (20). There is no
    Status column, no Archive and no Delete.
  - "One size" carries a neutral badge "No size" after its name (also after a rename).
  - Under the table: "A size is never removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One
    size" is for products that don't come in sizes: a product that has it has no other size."
  - On a phone each size is one 56 px row with its tag, "Used by 3 products" and a 44 px **Edit**; **Add size** is full width, 44 px.
  - Sizes with the same position are listed by their creation order, not by name (S, M, L stay S, M, L).

- [ ] **AC-2 · Add and rename a size.** **Add size** opens a window with **Name** (up to 12 characters) and **Position (optional)**
  ("Lower numbers are listed first. Leave empty to list it last."), **Cancel** and **Save size**; **Edit XXL** is the same window.
  - Saved: "Size created." / "Size updated."; the size is in its place at once. A new size is on no product until chosen on one. An
    empty position lists it last; 0 lists it first.
  - Refused, the window stays open, the message under the field, the focus on the first: "Enter the size's name." · "You already have
    a size named "XL"." (ignoring capitals) · "Keep the name to 12 characters or fewer." · "Enter a whole number from 0 to 32,767, or
    leave it empty."
  - Editing a size that products use shows under Name "A new name shows on the 3 products that use XXL, and in their variant SKUs.
    Barcodes don't change." ("… on the 1 product that uses XXL, and in its variant SKUs. …"); an unused size shows no such line.
    Editing "One size" adds "The size for products without sizes. A product that has it has no other size." under the title.
  - Renaming XXL to 2XL shows "2XL" at once on every product that has it, in its variant SKUs ("PRD-JKT-001-IND-XXL" becomes
    "PRD-JKT-001-IND-2XL") and in the barcode lists; no barcode number changes.
  - Every figure is as before: sizes change no cost, "Can produce now" or sale.

- [ ] **AC-3 · Every product keeps the sizes it has.** When this ships, a workshop gets the 19 sizes above, and each product keeps
  exactly the sizes its category gave it: the Denim Jacket XS to XXXL ("3 colours × 7 sizes = 21 variants"), a Footwear product 36 to
  46, the Leather Tote "One size".
  - Nothing moves: stock, restocks, barcodes (their numbers and names), order lines and their delivered and cancelled counts, batches in
    production, work tickets and variant SKUs read exactly as before, and so does every cost and every "Can produce now".
  - "Used by" counts the products that have each size (non-deleted, any status): XS 3 products when three Apparel products exist.
  - A new account's workshop starts with the same 19 sizes, numbered 10 to 190.
  - The three starting size lists no longer exist as lists a category points to; the lock "… so its category can't change to one with
    other sizes." is gone from every screen, and changing a product's category changes none of its sizes.

- [ ] **AC-4 · The category suggests sizes.** Settings › Product categories gains the column **Suggests**: Apparel "XS to XXXL",
  Footwear "36 to 46", every other category "One size" (an unbroken run in list order reads first to last, otherwise the sizes are
  listed one by one). The Edit category window gains **Suggested sizes**, the same toggle buttons as the product form with nothing
  locked, and the hint "Add product pre-selects these sizes when you choose this category.".
  - Saving with none chosen: "Choose at least one size." under the field, nothing saved. One size can't be combined with others here
    either (pressing it turns the others off, and the reverse).
  - A category added in Settings starts by suggesting "One size". Renaming or archiving a category changes none of its suggestions.
  - Changing a category's suggestion changes no existing product; it only decides what Add product pre-selects.

- [ ] **AC-5 · The Sizes field on Add product.** In the Details card, after Category and Status and before Colours, **Sizes** (red
  star) shows one toggle button per size of the list, all visible, in Settings order, with the hint "Choosing a category pre-selects
  its sizes. Change them as you like." and a count under them.
  - A pressed size is tinted, ringed and has a check mark before its name (never colour alone); the group is one stop for the
    keyboard, arrow keys move along it and Space presses. Buttons are 40 px high on a laptop, 44 px on a phone, 19 sizes wrapping onto
    four rows at 375 px.
  - With no category: nothing pressed, "None chosen yet". Choosing Apparel presses XS to XXXL, "7 sizes chosen"; the owner presses XS,
    XXL and XXXL off and keeps S · M · L · XL, "4 sizes chosen" ("1 size chosen" for one). Add product → "Product created." and the page
    reads "Sizes S, M, L, XL" and "3 colours × 4 sizes = 12 variants.".
  - **One size** and other sizes exclude each other: pressing One size turns the others off, pressing another size turns One size off.
  - Choosing another category replaces the sizes only while she has not changed them herself. Once she has, they stay and a line under
    the buttons reads "Sizes kept: you changed them." with the text button **Use the sizes of Footwear**, which replaces them.
  - Refused on save, nothing saved, under the group, the focus on the first button: no size pressed → "Choose at least one size."; One
    size with others (a stale page) → "One size can't be combined with other sizes. Choose it alone, or choose other sizes.".

- [ ] **AC-6 · The Sizes field on Edit product, and the locks.** Edit product shows the same field with the hint "A new size adds a
  variant for every colour, with no stock and no barcode yet.", the product's sizes pressed.
  - A size the product **uses** shows a lock icon after its name, stays pressed, keeps its full-strength text and can't be pressed
    off, but stays reachable by keyboard. Its reason is read on focus and hover, in this order, joined with commas and "and": "it has
    units in stock", "is on orders", "has barcodes", "has units in production" (and, with INV-16, "has material quantities"): "Can't be
    taken off: it has units in stock, is on orders and has barcodes." Under the group, once: "Sizes with a lock are in use by this
    product's stock, orders, barcodes, production or material quantities, so they can't be taken off." Orders that were deleted don't
    count; a size that ever had a barcode on the product stays on it for good.
  - The Denim Jacket: S, M, L and XL are locked, XS, XXL and XXXL are free. Pressing XS, XXL and XXXL off and saving gives "Product
    updated." and the badge "Sizes S, M, L, XL"; the stock table, barcodes and variants of those three sizes disappear, and nothing
    that held a figure is lost, since none held any. Any size of the list can be pressed on,
    including 36 (the category no longer limits it).
  - A size added later: XXS (15) appears between XS and S; pressed on and saved, the Denim Jacket gains 3 variants (one per colour),
    each with no stock and no barcode, "3 colours × 5 sizes = 15 variants".
  - **One size** can't be pressed while a locked size is chosen; its reason reads "S, M, L and XL are in use, so One size can't be
    chosen.".
  - Refused on save, nothing saved: "Choose at least one size."; a size taken off that is in use (a stale page): "XL can't be taken
    off Denim Jacket: it has units in stock, is on orders and has barcodes." (one sentence per size, in list order; a number size reads
    "Size 36 can't be taken off Denim Jacket: …"). Changing the category on Edit product never changes the sizes.
  - Two people saving at once can't take a size off that the other has just put stock or an order on.

- [ ] **AC-7 · The product page, and every screen follows the product's sizes.**
  - The badge between Category and Status reads "One size" (or "Size M", "Size 38" for a single size), "Sizes S, M, L, XL" or "Sizes
    38, 39, 40" for two to six sizes, "Sizes XS to XXXL" or "Sizes 38 to 44" for seven or more in an unbroken run of the list, and
    "Sizes XS, S, M and 4 more" for seven or more with gaps.
  - **Sizes & colours** reads "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode."; the stock
    table, the Barcodes tab and the phone cards list only the product's sizes in Settings order. **Manage sizes & colours** opens Edit
    product on the Sizes field. A product with one size that isn't One size reads "1 colour × 1 size = 1 variant" and keeps its size
    column. "Can produce now … any mix of sizes" and "Unit cost … same for every size" show when the product has more than one size.
  - Update stock, New restock, barcode generation and lists, labels, order lines (New order and Edit order), the order tracker, Start
    batch and Progress show only the product's own sizes, in Settings order: the Denim Jacket at S · M · L · XL offers 4 sizes in each,
    never XS, XXL or XXXL. Variant lists in text keep their rules ("Indigo S, M, L and XL", "Light wash sizes 36, 37 and 38"). A
    renamed size shows its new name in all of them.
  - A size taken off a product that still appears in an old record can't happen: a size on an order, a batch, stock or a barcode is
    locked, so those screens never lose a row.
