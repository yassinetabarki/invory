---
id: INV-18
title: Sizes chosen per product, managed in Settings
type: ui
priority: Must
status: Approved
created: 2026-11-06
tags:
  - ui-card
---

# INV-18 · Sizes chosen per product, managed in Settings — UI card

> [!abstract] Description
> The workshop keeps **one list of sizes in Settings**, like Units and Colours, and **each product picks the sizes it really comes
> in**. The Denim Jacket stops carrying XS, XXL and XXXL it never makes; a sneaker made only in 38 to 44 shows 7 sizes, not 11; a new
> kind of garment is no longer stuck with "One size". The category stops deciding a product's sizes: it only **suggests** them when a
> product is added.
> New: **Settings › Sizes** with its Add size and Edit size windows; a **Sizes** field of toggle buttons on Add product and Edit
> product, with locked sizes and a question before a size with quantities is taken off; a **Suggests** column and **Suggested
> sizes** in Settings › Product categories. Changed: the product page's Sizes badge and Sizes & colours card now show the product's own
> sizes. Gone: the line "Sizes:" and the hint "Sizes come with the category." on the product form, and the three category locks ("… so
> its category can't change to one with other sizes.").
> Left out on purpose: deleting or archiving a size, a code for each size, groups of sizes, sizes on the Products list or its filters,
> sizes of materials, and anything inside the quantity grid of INV-16 beyond its sizes and its take-off question. The size
> screens of stock, restocks, orders, Start batch, barcodes and labels keep their layouts and simply show the product's own sizes.

> [!info] How it looks today
> A product's sizes come from its category, and nothing in the app can change them: every Apparel product has XS · S · M · L · XL ·
> XXL · XXXL, every Footwear product 36 to 46, every other product "One size". The product form shows a line "Sizes:" with a tag per
> size and the hint "Sizes come with the category.". The product page shows the badge "Sizes XS to XXXL" and, in Sizes & colours,
> "3 colours × 7 sizes = 21 variants" with seven size columns in the stock table and seven barcode rows per colour. A product on an
> order, with stock or in production can't move to another size list ("Denim Jacket is on orders, so its category can't change to one
> with other sizes. Keep a category with sizes XS to XXXL."). Settings › Catalogue holds Units, Material categories, Product
> categories, Colours and Labels, and no sizes.

## Pages

Worked example: a workshop that starts with today's 19 sizes (XS, S, M, L, XL, XXL, XXXL, 36 to 46 and One size). The **Denim
Jacket** has Indigo, Black and Light wash, and is given S · M · L · XL.

### Settings menu and Settings › Sizes
Board: `Inv18SizeSettings` (Invory App UI canvas)
1. Catalogue lists Units · Material categories · Product categories · Colours · **Sizes** · Labels.
2. The page is built like Settings › Colours: the heading **Sizes**, the description "The sizes your products come in. Each product
   picks the ones it has. Rename any of them, or add your own.", the main button **Add size** on the right, then one table in a card.
3. The table, with the caption "Sizes", has one row per size in list order, no paging:

   | Size | Position | Used by    | Action    |
   | ---- | -------- | ---------- | --------- |
   | XS   | 10       | 3 products | Edit XS   |
   | XXS  | 15       | Not used   | Edit XXS  |
   | S    | 20       | 3 products | Edit S    |
   | XXXL | 70       | 3 products | Edit XXXL |
   | 36   | 80       | 1 product  | Edit 36   |

   The size appears as a small outlined tag, as on the product form. A workshop starts with its 19 sizes numbered 10, 20, 30 … 190, so
   a new size can go between two (15) and 0 puts it first. "Used by" counts the products that have the size. The only action is
   **Edit** (named "Edit XXL" for a screen reader). There is no Status column, no Archive and no Delete.
4. "One size" carries a small neutral badge "No size" after its name: a product that has it has no size columns and no size in its SKU.
   It can be renamed like any size; the badge stays.
5. Under the table: "A size is never removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One
   size" is for products that don't come in sizes: a product that has it has no other size."

### Add size and Edit size
Board: `Inv18SizeDialogs`
1. A window titled **Add size**, or **Edit XXL**, with **Name** (up to 12 characters) and **Position (optional)** with the hint
   "Lower numbers are listed first. Leave empty to list it last.". Buttons **Cancel** and **Save size** (a spinner while saving; the
   window stays open on an error, the focus on the first error).
2. Editing a size that products use adds under Name: "A new name shows on the 3 products that use XXL, and in their variant SKUs.
   Barcodes don't change." (one product: "A new name shows on the 1 product that uses XXL, and in its variant SKUs. Barcodes don't
   change."). An unused size shows no such line.
3. Editing "One size" adds under the title: "The size for products without sizes. A product that has it has no other size."
4. Renaming XXL to 2XL shows "2XL" at once wherever the size shows, on every product that has it, in its variant SKUs
   ("PRD-JKT-001-IND-2XL") and in the barcode lists, and changes no barcode. A new size is on no product until it is chosen on one.

### Settings › Product categories — Suggests
Board: `Inv18SizeDialogs` (bottom)
1. The table gains a column **Suggests** with "XS to XXXL", "36 to 46" or "One size", following the product badge's rule without the word
   "Sizes": one size alone ("One size", "M"); two to six sizes listed ("S, M, L, XL"); seven or more in an unbroken run, first to last
   ("XS to XXXL"); seven or more with gaps, the first three and a count ("XS, S, M and 4 more"). The Edit category window gains a **Suggested sizes** field with the same toggle
   buttons as the product form (nothing locked) and the hint "Add product pre-selects these sizes when you choose this category.".
   Saving with none chosen shows "Choose at least one size.".
2. Apparel suggests XS to XXXL, Footwear 36 to 46, every other category One size, as today's lists do. A category added in Settings
   starts by suggesting "One size". One size can't be combined with others here either.

### Product form — the Sizes field (Add product and Edit product)
Board: `Inv18ProductForm`
1. In the Details card, a full-width row after Category and Status and before Colours: the label **Sizes** with the red star, a hint,
   then **one toggle button per size of the workshop's list, all visible**, in Settings order (19 on a new workshop; they wrap).
   Under the buttons a count: "4 sizes chosen", "1 size chosen" or "None chosen yet".
2. A chosen size is **pressed**: a tinted button with a 2 px ring and a check mark before the name, so state never rests on colour
   alone. An unchosen size is a plain outline. Buttons are 40 px high on a laptop and 44 px on a phone, at least 44 px wide, 8 px
   apart. The group is one stop for the keyboard: arrow keys move along it, Space presses.
3. **Add product.** The hint reads "Choosing a category pre-selects its sizes. Change them as you like.". With no category yet: no
   size pressed, "None chosen yet". Choosing Apparel presses XS to XXXL and reads "7 sizes chosen"; she presses XS, XXL and XXXL off and
   keeps S · M · L · XL, "4 sizes chosen". After Add product, "Product created." and the page reads "Sizes S, M, L, XL".
4. **Edit product.** The hint reads "A new size adds a variant for every colour, with no stock and no barcode yet.". The sizes the
   product has are pressed. A size the product **uses** carries a lock icon after its name, stays pressed, keeps its full-strength
   text and **can't be pressed off**. Its reason is read when it takes focus or the pointer rests on it: "Can't be taken off: it has
   units in stock, is on orders and has barcodes." Every lock sentence puts "it" before its first reason ("Can't be taken off: it is on
   orders and has barcodes."); the reasons, in this order, joined with commas and "and": "has units in stock", "is on orders", "has
   barcodes", "has units in production", "has material quantities" (a saved quantity on a By colour and size line of INV-16). Under the group, once: "Sizes with a lock are in use by this product's stock, orders, barcodes, production or material
   quantities, so they can't be taken off." A size that ever had a barcode on the product stays on it for good.
   Sizes not in use can be pressed off (XS comes off the jacket) and any size of the list can be pressed on, such as 36, even though no
   category suggests it. **Save product** → "Product updated.".
5. **One size** and the other sizes exclude each other: pressing One size turns the others off; pressing another size turns One size
   off. If a locked size is pressed, One size can't be pressed, and its reason names the sizes in use: "S, M, L and XL are in use, so One size can't be chosen."
   ("XL is in use, so …", "Size 38 is in use, so …", "Sizes 36, 37 and 38 are in use, so …"). When One size itself is locked, every other
   size can't be pressed and reads "One size is in use, so other sizes can't be chosen.".
6. **A category change on Add product.** Choosing another category replaces the sizes only while she has not changed them herself and
   no composition line holds quantities per size (INV-16). Otherwise her sizes stay and a line under the buttons reads "Sizes kept: you
   changed them." with the text button **Use the sizes of Footwear**, which replaces them (after the question below if quantities
   would be cleared). Edit product never changes sizes when the category changes.
7. **Refusals on save, nothing saved**, under the group, the focus going to the first button: no size pressed → "Choose at least one
   size."; One size with others (a stale page) → "One size can't be combined with other sizes. Choose it alone, or choose other sizes.";
   a size taken off that is in use (a stale page) → "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has
   barcodes." (one sentence per size, in list order; a number size reads "Size 36 can't be taken off Denim Jacket: …").

### Taking a size off while quantities are typed (replaces INV-16's category question)
Board: `Inv18ProductForm` (bottom)
1. On the form (Add product, and Edit product for quantities typed but not yet saved), pressing a size off while a composition line
   holds quantities for it opens a question before the button changes: title **Take XL off this product?**, body "This clears the 2
   quantities you set for XL across its materials." (one: "the 1 quantity"; a number size: "Take size 36 off this product?" and "…you
   set for size 36…"), buttons **Keep XL** (focus here; Esc does the same) and the red **Take XL off**.
2. Pressing a size off that has no quantity asks nothing.
3. **Use the sizes of Footwear** with quantities typed opens "Use the sizes of Footwear?" with "This replaces the sizes and clears the 8
   quantities you set across its materials.", buttons **Keep the sizes** and **Use Footwear sizes**.
4. Quantities that are already saved never get this question: on Edit product the size is locked.

### Product page
Board: `Inv18ProductShow`
1. The badge between Category and Status is written from the product's own sizes: one size → "One size", or "Size M" and "Size 38" for
   a single size that isn't One size; two to six → "Sizes S, M, L, XL", "Sizes 38, 39, 40"; seven or more in an unbroken run of the
   list → first and last, "Sizes XS to XXXL", "Sizes 38 to 44"; seven or more with gaps → "Sizes XS, S, M and 4 more".
2. Sizes & colours reads "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode."; the stock table, the
   Barcodes tab and the phone cards list only the product's sizes, in Settings order. **Manage sizes & colours** opens Edit product on
   the Sizes field. A product with one size that isn't One size reads "1 colour × 1 size = 1 variant" and keeps its size column.
3. "Can produce now … any mix of sizes" and "Unit cost … same for every size" apply when the product has more than one size, as today.

## States

| State                         | Content                                                                                                              |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| **Settings › Sizes, default** | 19 sizes; XS … XXXL "3 products", 36 … 46 "1 product", One size "2 products" with "No size"; a new XXS "Not used"      |
| **Settings › Sizes, empty**   | Never: a workshop starts with 19 and a size is never removed                                                         |
| **Loading, error**            | As Colours today; Save size shows a spinner and the window stays open on an error                                   |
| **Success**                   | "Size created.", "Size updated."; "Product created.", "Product updated."                                            |
| **Sizes field, Add, empty**   | "None chosen yet"; saving with none: "Choose at least one size." under the group                                    |
| **Sizes field, locked**       | Pressed, lock icon, full-strength text; reason on focus and hover; the lock sentence under the group                |
| **Sizes field, long list**    | 40 sizes wrap onto more rows; a 12-character name is one button wider; nothing overlaps or scrolls sideways         |
| **Confirm**                   | "Take XL off this product?" and "Use the sizes of Footwear?", only on the form and only while quantities exist      |
| **Product page, other cases** | "One size"; "Size M"; "Sizes 38 to 44"; "Sizes XS, S, M and 4 more"                                                |
| **Loading, error (page)**     | Unchanged: placeholder in the card and "Couldn't load the stock and barcodes." with Try again                       |

## Wording
- Settings: "Sizes" · "The sizes your products come in. Each product picks the ones it has. Rename any of them, or add your own." ·
  "Add size" · "Size" · "Position" · "Used by" · "3 products" · "1 product" · "Not used" · "Edit XXL" · "No size" · "A size is never
  removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One size" is for products that don't come in
  sizes: a product that has it has no other size."
- Windows: "Add size" · "Edit XXL" · "Name" · "Position (optional)" · "Lower numbers are listed first. Leave empty to list it last." ·
  "A new name shows on the 3 products that use XXL, and in their variant SKUs. Barcodes don't change." · "The size for products
  without sizes. A product that has it has no other size." · "Cancel" · "Save size" · "Size created." · "Size updated."
- Errors: "Enter the size's name." · "You already have a size named "XL"." · "Keep the name to 12 characters or fewer." · "Enter a whole
  number from 0 to 32,767, or leave it empty."
- Product categories: "Suggests" · "Suggested sizes" · "Add product pre-selects these sizes when you choose this category." · "Choose
  at least one size."
- Sizes field: "Sizes" · "Choosing a category pre-selects its sizes. Change them as you like." · "A new size adds a variant for every
  colour, with no stock and no barcode yet." · "4 sizes chosen" · "1 size chosen" · "None chosen yet" · "Can't be taken off: it has
  units in stock, is on orders and has barcodes." · "Sizes with a lock are in use by this product's stock, orders, barcodes, production
  or material quantities, so they can't be taken off." · "S, M, L and XL are in use, so One size can't be chosen." · "One size is in use, so other sizes can't be chosen." · "Can't be taken off: it is on orders and has barcodes." · "Sizes kept: you
  changed them." · "Use the sizes of Footwear" · "Choose at least one size." · "One size can't be combined with other sizes. Choose it
  alone, or choose other sizes." · "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has barcodes."
- Questions: "Take XL off this product?" · "This clears the 2 quantities you set for XL across its materials." · "Keep XL" · "Take XL
  off" · "Use the sizes of Footwear?" · "This replaces the sizes and clears the 8 quantities you set across its materials." · "Keep the
  sizes" · "Use Footwear sizes".
- Product page: "One size" · "Size M" · "Sizes S, M, L, XL" · "Sizes XS to XXXL" · "Sizes XS, S, M and 4 more" · "3 colours × 4
  sizes = 12 variants. Each variant has its own stock and its own barcode." · "Manage sizes & colours".
- A size whose name is only digits reads "size 36" in sentences and "Size 36" at the start of one, as today.

## Phone layout
Board: `Inv18Phone`
- **Settings › Sizes:** the description, **Add size** full width and 44 px high, then one 56 px row per size (not a full card, there are
  20): the size tag, "Used by 3 products" in grey and an **Edit** button 44 px high. The "No size" badge sits after "One size".
- **Add size and Edit size:** a full-width window, Name and Position one under the other, **Cancel** and **Save size** stacked, Save first.
- **Sizes field:** full width, buttons 44 px high, six or seven to a row (19 sizes take four rows at 375 px, five at 320 px); the
  count and the lock sentence below; **Save product** stays in the sticky bar.
- **Questions:** full width, buttons stacked, **Keep XL** first.
- **Product page:** the badge wraps with the other badges; Sizes & colours is as today, with fewer size buttons per colour. Nothing
  scrolls sideways at 320 px.
