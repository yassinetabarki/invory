---
id: INV-1
title: Colours as fixed values
type: ui
priority: Must
status: Approved
created: 2026-10-24
tags:
  - ui-card
---

# INV-1 · Colours as fixed values — UI card

> [!abstract] Description
> The workshop keeps one list of its colours in Settings › Catalogue › **Colours**, starting from 20 default colours, and each product
> picks the colours it is made in from that list (at least one), so "Indigo" is always the same Indigo, never "indigo", "Indgo" or "Dark blue". A colour
> has a name, a short code (IND) and a swatch, and always shows as its swatch **and** its name: ■ Black ■ Indigo ■ Light wash.
> New: the Settings › Colours page with its Add colour and Edit colour windows. Changed: Add product and Edit product get a Colours
> field, and the product page gets a "Colours" line.
> Left out on purpose: stock, barcodes, orders and sales per colour (INV-2 and later), colours on the Products list, and the materials'
> "Colour" field, which stays free text ("Antique brass").

> [!info] How it looks today
> Products have no colour at all. Add product and Edit product show Details (SKU, Name, Category with "Sizes: S M L XL" and "Sizes come
> with the category.", Status with "Only active products can be sold.", Description), then Costs and price, Photo and Composition. The
> Denim Jacket page header reads "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active".
> Materials have a typed colour: the material form has "Colour (optional)" with the hint text "e.g. Indigo, Antique brass", and anything
> can be typed.
> Settings › Catalogue has Units, Material categories and Product categories: a table each (Name, Code, Used by, Status and the row's
> buttons), cards below laptop width; "Used by" reads "3 products", or "Not used".

## Pages

### How a colour shows, everywhere
- **Swatch:** a small rounded square filled with the colour, with a thin edge in the same grey as a field's border (3.2:1 on the card,
  in light and dark mode), so White shows on a white card and Black shows in dark mode. The fill is the same in light and dark mode (it
  is the fabric's colour). A swatch is always next to the colour's name, never alone. Screen readers skip the swatch and read the name.
- **Colour tag** (read-only, on the product page and in Settings): swatch and name, no border.
- **Colour chip** (in the product form): a round pill with swatch, name and a ✕ button named "Remove Indigo".
- **Colour option** (in the picker): one row with a check mark when chosen (empty space when not), the swatch, the name, and the code in
  grey in the number font on the right (IND).
- A product's colours always show in the order of Settings › Colours, the same on every product: Black, Indigo, Light wash, whatever
  order they were picked in.

### Add product and Edit product · Colours field
Board: INV-1 · Product form and page · Colours field and picker · desktop (Invory App UI canvas)
- In the **Details** card, a full-width row after Category and Status, before Description.
- Label **Colours**, with the hint under it: "The colours this product is made in, from your list in Settings › Colours."
- The chosen colours as chips in a row that wraps, then an outline **Add colour** button with a plus sign at the end of the row.
- Denim Jacket, Edit product: chips "Black", "Indigo", "Light wash", then **Add colour**.
- Every product has at least one colour. **Save product** with no colour chosen saves nothing and shows under the Colours field:
  "Choose at least one colour."
- Every product already recorded when colours arrive gets the first colour of the workshop's list, in the order of Settings ›
  Colours (White, in the 20 defaults).
- A colour with units in stock can't be taken off a product: its chip's ✕ still works, but **Save product** saves nothing and shows
  under the Colours field: "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first."
- The chips sit in a group that screen readers announce as "Colours". Each chip's ✕ takes that colour off the form; nothing is saved
  until **Save product**.
- **Save product** → toast "Product updated." (Edit) or "Product created." (Add), and the product page opens.

### The colour picker
Board: INV-1 · Product form and page · Colours field and picker · desktop (Invory App UI canvas)
- **Add colour** opens a list under the button, about as wide as a phone screen.
- Top: a search field labelled "Search colours". Then the workshop's colours in the order of Settings › Colours, the chosen ones
  checked.
- Typing filters by name or code: "ind" finds Indigo, "BLK" finds Black.
- Enter or a click chooses a colour, or un-chooses it; the list stays open, so several can be picked one after another.
- Bottom: "3 chosen" on the left, **Done** on the right. **Done** or Esc closes the list and puts the focus back on **Add colour**.
- Screen readers hear a chosen option as "Indigo, IND, selected".

### Product page · Colours line
Board: INV-1 · Product form and page · Colours field and picker · desktop (Invory App UI canvas)
- In the header, a line under the badges row (PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active): grey "Colours", then the colour
  tags "■ Black ■ Indigo ■ Light wash", wrapping on a narrow screen. Screen readers hear "Colours: Black, Indigo, Light wash".
- Nothing else on the page changes: "Can produce now 112 units", "In stock" and "Unit cost TND 35.754" stay as they are.

### Settings › Colours
Board: INV-1 · Settings › Colours · list, used, archived · desktop (Invory App UI canvas)
- In the Settings menu, group **Catalogue**: Units, Material categories, Product categories, **Colours**.
- Header: **Colours**, the description "The colours your products come in. Each one shows as its swatch and its name. Rename any of
  them, or add your own.", and the main button **Add colour**.
- Table on a laptop: **Colour** (swatch and name) · **Code** (IND, in the number font) · **Used by** ("3 products", "1 product", "Not
  used") · the button "Edit Indigo".
- The colours are listed in their order.
- Example: Black "4 products", Indigo "3 products", White "2 products", Ecru "Not used".
- Every workshop starts with these 20 colours, in this order:

| #   | Name         | Code | Swatch  |
| --- | ------------ | ---- | ------- |
| 1   | White        | WHT  | #FFFFFF |
| 2   | Ecru         | ECR  | #EFE6D2 |
| 3   | Beige        | BEI  | #D8C3A0 |
| 4   | Camel        | CAM  | #B98A57 |
| 5   | Brown        | BRN  | #6B4A32 |
| 6   | Grey         | GRY  | #8C8C8C |
| 7   | Charcoal     | CHA  | #3D3D42 |
| 8   | Black        | BLK  | #1F1F23 |
| 9   | Navy         | NAV  | #1F2A44 |
| 10  | Indigo       | IND  | #3E4C7E |
| 11  | Light wash   | LWH  | #9DB3D1 |
| 12  | Lilac        | LIL  | #B3A2D3 |
| 13  | Pink         | PNK  | #E8A9B8 |
| 14  | Red          | RED  | #B8282E |
| 15  | Burgundy     | BUR  | #6E1F2E |
| 16  | Rust         | RST  | #B5552B |
| 17  | Mustard      | MUS  | #D4A12A |
| 18  | Khaki        | KHA  | #A69F74 |
| 19  | Olive        | OLV  | #5F6B3A |
| 20  | Forest green | FGR  | #2F5A43 |

- Board: INV-1 · The 20 default colours · light and dark (Invory App UI canvas).

### Add colour and Edit colour windows
Board: INV-1 · Settings › Colours · add, edit, errors, delete · desktop (Invory App UI canvas)
- Title **Add colour**, or **Edit Indigo** with "Code IND can't be changed." under it.
- **Name**: up to 32 characters.
- **Code** (only when adding), with the hint "2 to 4 capital letters or digits, such as IND. It can't be changed later."
- **Swatch**: a square colour well that opens the computer's own colour chooser (named "Pick swatch colour"), beside a text field with
  the colour code "#3E4C7E". Hint: "Pick the colour, or type its code, such as #3E4C7E."
- In **Edit Indigo**, the swatch hint adds: "A new name or swatch shows on the 3 products that use Indigo." For a colour no product uses,
  that sentence isn't shown.
- **Preview**: the colour tag as it will look, on a light strip and on a dark strip, labelled "On a light screen" and "On a dark
  screen", so a pale or very dark colour can be checked before saving.
- **Position (optional)**, as in Units and categories: "Lower numbers are listed first. Leave empty to list it last."
- Buttons **Cancel** and **Save colour**. While saving, **Save colour** shows a spinner; on an error the window stays open.
- **Save colour** → toast "Colour created." (Add) or "Colour updated." (Edit), and the list shows the colour in its place.

## States

### Colours field (Add product and Edit product)
- **Saving:** nothing extra loads (the colours come with the page); **Save product** keeps its label and shows a spinner.
- **No colour chosen:** under the field, in red, with the focus on **Add colour**: "Choose at least one colour."
- **A colour with stock taken off:** under the field, in red: "Indigo has 6 units in stock, so it can't be removed from Denim Jacket.
  Set its stock to 0 first."

### Picker
- **Default:** the 20 colours, with Black, Indigo and Light wash checked, and "3 chosen" · **Done**.
- **Long list:** with 40 colours the list scrolls inside the picker, about 8 rows high, and the search field stays on top. The focused
  option is never hidden under the bottom bar.

### Product page · Colours line
- **Default:** "Colours ■ Black ■ Indigo ■ Light wash". There is no empty state: every product has at least one colour.

### Settings › Colours
- **Default:** the 20 colours with their "Used by".
- **Loading:** as Units today: the list comes with the page; **Save colour** shows a spinner while it saves.
- **The code can't change:** "Code IND can't be changed." in the Edit window.
- **Long content:** a 32-character name wraps in its cell; 40 colours fit in one list, with no pages, as Units.

### Add colour and Edit colour · errors
Shown under the field after **Save colour**, with the focus on the first one:
- Name empty: "Enter the colour's name."
- Name already used, ignoring capitals: "You already have a colour named "Indigo"."
- Name too long: "Keep the name to 32 characters or fewer."
- Code empty or not 2 to 4 capital letters or digits: "Enter 2 to 4 capital letters or digits, such as IND."
- Code already used: "Code IND is already used by Indigo."
- Swatch empty or not a colour code: "Enter a colour code like #3E4C7E."

## Wording
- Product form: "Colours" · "The colours this product is made in, from your list in Settings › Colours." · "Add colour" · "Remove
  Indigo" · "Choose at least one colour." · "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0
  first."
- Picker: "Search colours" · "3 chosen" · "Done" · on a phone, the title "Choose colours" and "Close".
- Product page: "Colours".
- Settings › Colours: "Colours" · "The colours your products come in. Each one shows as its swatch and its name. Rename any of them, or
  add your own." · "Add colour" · column names "Colour", "Code", "Used by" · "3 products", "1 product", "Not used" · "Edit Indigo".
- Add colour and Edit colour windows: "Add colour" · "Edit Indigo" · "Code IND can't be changed." · "Name" · "Code" · "2 to 4 capital
  letters or digits, such as IND. It can't be changed later." · "Swatch" · "Pick swatch colour" · "Pick the colour, or type its code,
  such as #3E4C7E." · "A new name or swatch shows on the 3 products that use Indigo." · "Preview" · "On a light screen" · "On a dark
  screen" · "Position (optional)" · "Lower numbers are listed first. Leave empty to list it last." · "Cancel" · "Save colour", and the
  six error messages above.
- Toasts: "Colour created." · "Colour updated." · "Product created." · "Product updated."
- The spelling is "Colour" everywhere on screen, as the materials already say.

## Phone layout
Board: INV-1 · Product form, picker, Settings › Colours · 375 (Invory App UI canvas)
- **Product form:** one column. The Colours field takes the full width; the chips wrap two or three to a row and are 44 px tall, with
  a 44 px target for each ✕. **Add colour** sits on its own row, full width, 44 px tall. **Save product** stays in the bar fixed at the
  bottom, as today.
- **Picker:** a sheet over the whole screen, titled **Choose colours**, with ✕ "Close"; the search field at the top, the list in 44 px
  rows, and a bar fixed at the bottom with "3 chosen" and **Done**. The focus stays inside the sheet while it is open, and closing it
  puts the focus back on **Add colour**.
- **Product page:** the Colours line wraps under the badges; nothing scrolls sideways.
- **Settings › Colours:** one card per colour (swatch and name as its title, then the code, Used by and the "Edit Indigo" button), as
  Units today; **Add colour** full width under the description. The Add and Edit windows take the full width.
- At 320 px the chips and tags still wrap, and nothing is cut off.
