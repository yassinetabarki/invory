---
id: INV-1
title: Colours as fixed values
type: feature
phase: Colour × size variants
priority: Must
size: M
status: Ready
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
  holding the colour code (#B7C3CF; a code typed small, "#b7c3cf", is accepted and shown in capitals), with the hint "Pick the colour,
  or type its code, such as #3E4C7E."; "Preview", the colour tag as it will look on a light strip "On a light screen" and a dark strip
  "On a dark screen", following what is typed or picked; and "Position (optional)" with "Lower numbers are listed first. Leave empty
  to list it last.". Adding Stone wash, STW, #B7C3CF with no position → **Save colour** shows a spinner, then the toast "Colour
  created.", and Stone wash is listed 21st, after Forest green, with "Not used". **Cancel** closes the window and saves nothing.
- [ ] **AC-4 · A wrong entry is refused.** In Add colour or Edit colour, **Save colour** with a wrong entry saves nothing, keeps the
  window open, shows the message under its field and puts the focus on the first wrong field: an empty name "Enter the colour's
  name."; "indigo" while Indigo exists (capitals ignored) "You already have a colour named "Indigo"."; a 33-character name "Keep the
  name to 32 characters or fewer."; an empty code, "INDIG" or "I-D" "Enter 2 to 4 capital letters or digits, such as IND."; "IND"
  while Indigo has it "Code IND is already used by Indigo."; an empty swatch or "blue" "Enter a colour code like #3E4C7E.".
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
