---
id: INV-2
title: Product sizes × colours, stock and barcodes
type: ui
priority: Must
status: Approved
created: 2026-10-25
tags:
  - ui-card
---

# INV-2 · Product sizes × colours, stock and barcodes — UI card

> [!abstract] Description
> Each product shows how many finished units of every colour and size are on the shelf, and which barcode goes on the label of each
> one. A **variant** is one colour in one size: the Denim Jacket in Indigo, size M. Each variant has its own stock count and its own
> barcode, and **one barcode stands for one name + colour + size: every product with the same name, colour and size shares it.**
> Barcodes are EAN-13 numbers from the workshop's own in-store range, made by Invory.
> New: the **Sizes & colours** card on the product page with its tabs **Stock by colour** and **Barcodes**, the Generate barcodes
> window, the Print labels window and the label, and the page Settings › Labels. Changed: the "In stock" figure, the "Update stock"
> window (colour pills, and it now opens only from the Sizes & colours card), and Delete product (one sentence).
> Left out on purpose: orders, sales and "Put in stock" per colour, scanning, restock of finished products (INV-3), clients (INV-4),
> production progress (INV-5), a composition per colour (an Indigo and a Black jacket use the same materials, so "Can produce now"
> keeps "any mix of sizes"), and barcodes on the Products list.

> [!info] How it looks today
> The Denim Jacket page shows three figures in one row: **Can produce now** "112 units, from current stock, any mix of sizes", **In
> stock** "10 units" with the chips [S 3] [M 0] [L 5] [XL 2] and an **Update stock** button at the top right of that figure, and
> **Unit cost** "TND 35.754, same for every size". The header reads "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active", and INV-1
> adds the line "Colours ■ Black ■ Indigo ■ Light wash"; every product has at least one colour.
> "Update stock · Denim Jacket" has one field per size, "was 0" under a changed size, "Total after saving 10 units" and the note
> "Doesn't change materials, "Can produce now" or sales.". Save → "Stock updated.".
> Stock is counted per size only. No product has a barcode.

## Pages

The agreed look is the human's own boards: "Product detail with sizes, colors and barcodes", "Update stock dialog, per color" and
"Size building blocks and states" (Invory App UI canvas). The INV-2 boards fill the gaps. Every example below uses the Denim Jacket
in Black, Indigo and Light wash (the order of Settings › Colours), with:

| Colour     | S | M | L | XL | Total |
| ---------- | - | - | - | -- | ----- |
| Black      | 1 | 0 | 2 | 1  | 4     |
| Indigo     | 2 | 0 | 3 | 1  | 6     |
| Light wash | 0 | 0 | 0 | 0  | 0     |
| Total      | 3 | 0 | 5 | 2  | 10    |

### Stock counted before INV-2
- The units each product had per size go into its first colour, in the order of Settings › Colours. A product that got White by
  default (INV-1) shows its S 3 · M 0 · L 5 · XL 2 as White S 3 · M 0 · L 5 · XL 2. Nothing else asks for a move.

### Product page · header and figures
Board: Product detail with sizes, colors and barcodes (Invory App UI canvas)
- **Header:** as INV-1 approved it: "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active" and under it "Colours ■ Black ■ Indigo ■
  Light wash".
- **Can produce now** and **Unit cost:** unchanged.
- **In stock:** "10 units", then one line per colour in the order of Settings › Colours, each a colour tag and its count in bold: "■
  Black **4** · ■ Indigo **6** · ■ Light wash 0". A colour at 0 is in the grey text colour, its "0" still shown. Screen readers hear "In
  stock per colour: Black 4, Indigo 6, Light wash 0". Under it the link **By size and colour**, which moves to the Sizes & colours card
  and its Stock tab.
- **Update stock** is no longer on this figure: it is in the Sizes & colours card, so stock is changed in one place.

### Product page · Sizes & colours card
Board: Product detail with sizes, colors and barcodes (Invory App UI canvas)
- Full width, under the three figures, where the board puts it.
- Heading **Sizes & colours** and the line "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode."
  Other products: "1 colour × 4 sizes = 4 variants", "2 colours × One size = 2 variants".
- On the heading's row: the outline button **Manage sizes & colours**, which opens Edit product with the focus on the Colours field
  (sizes come with the category, just above it), the outline button **Update stock**, and the main button **Restock** (plus sign),
  which opens New restock with this product in it (INV-3). The three buttons keep this order, left to right on a laptop and top to
  bottom on a phone, so the keyboard follows what you see.
- Two tabs, **Stock by colour** and **Barcodes**. While any variant has no barcode, Barcodes carries the warning badge "4 missing".
  The page opens on Stock by colour; the open tab is kept in the page address, so a link can open Barcodes directly.

### Stock by colour tab
Board: INV-2 · Product page · Sizes & colours · states · desktop (Invory App UI canvas)
- A table named "Units in stock per colour and size": first column **Colour** (swatch and name), one column per size in size order
  (S, M, L, XL; a shoe 36 … 46), then **Total**. One row per colour in the order of Settings › Colours. The last row is **Total**, with
  a heavier line above it.
- Every figure in the number font, right-aligned; a 0 in the grey text colour (the readable grey of S-71, not the board's paler
  grey).
- Under the table: "Grey zeros are variants with nothing on the shelf." Then: "Last restock: 25 Oct 2026, 24 units · **See the
  restocks of Denim Jacket**" (a link to Restocks with that product chosen); never restocked: "Not restocked yet.", with no link.
- **"One size" product** (Leather Tote in Camel and Black): the table has the columns **Colour** and **In stock**, no size columns and
  no Total column; the Total row stays when there are two colours or more.

### Barcodes tab
Board: INV-2 · Barcodes · generate, shared, print labels · desktop (Invory App UI canvas)
- **Colour pills** (the board's round pills, a group named "Colour"): swatch, name, and in grey "· 4 missing" when that colour has
  variants without a barcode. The chosen pill has a 2 px Denim ring and a tint, and is announced "pressed". The pills follow the order
  of Settings › Colours; the first colour (Black) is chosen when the tab opens.
- On the pills' row, to the right: the main button **Generate missing barcodes (4)**, shown only while any variant of the product, in
  any colour, has no barcode (the count is the product's total), and the outline button **Print labels · Indigo** with a printer icon.
- Table "Barcodes for Indigo": **Size** (the size tag) · **SKU** (number font: "PRD-JKT-001-IND-S") · **Barcode (EAN-13)** (the bars,
  and the 13 digits under them in the number font: "2000000000015") · **In stock** (this product's units of that variant) · the row's
  button.
- The row's button: **Print label** (named "Print label for PRD-JKT-001-IND-S") when the variant has a barcode; **Generate** (named
  "Generate barcode for PRD-JKT-001-LWH-S") when it doesn't, and then the Barcode cell shows the warning badge "No barcode yet".
- **A shared barcode:** under the digits, a grey line "Shared with PRD-JKT-002", where PRD-JKT-002 is a link to that product. Two or
  three others: "Shared with PRD-JKT-002, PRD-JKT-007". More: "Shared with PRD-JKT-002, PRD-JKT-007 and 3 more", where "3 more" opens
  a small list. The SKU is shown, not the name, because the name is the same.
- "The same name" means the same words, ignoring capitals and extra spaces: "denim jacket" is "Denim Jacket".
- A combination that already has a barcode on any product with the same name never counts as missing and never shows **Generate**; it
  shows the shared barcode.
- Under the table: "One barcode for each colour and size. Every product named Denim Jacket shares it, and a barcode never changes."
- **"One size" product:** no pills; one table, one row per colour: **Colour** · SKU "PRD-TOT-001-CAM" · Barcode · In stock · button.
  **Print labels** prints every colour.
- Archived and draft products show, generate and print barcodes like active ones.

### Generate barcodes window
Board: INV-2 · Barcodes · generate, shared, print labels · desktop (Invory App UI canvas)
- It always asks first, because a number is used for good.
- From **Generate missing barcodes (4)**: title **Generate 4 barcodes?**, text "Denim Jacket gets a barcode for Light wash S, M, L and
  XL, from your in-store numbers. A barcode never changes, and its number is never given to anything else." Buttons **Not now** and
  **Generate 4 barcodes**.
- From a row's **Generate**: title **Generate a barcode?**, text "Denim Jacket in Light wash, size S, gets a barcode from your
  in-store numbers. A barcode never changes, and its number is never given to anything else." Buttons **Not now** and **Generate
  barcode**.
- Each new name + colour + size takes the next number of the workshop's in-store range. Generating is all or nothing: either every
  barcode asked for is made, or none.
- While generating, the confirm button shows a spinner and keeps its label; the window closes on success, and the focus goes to the
  first new barcode's **Print label**.

### Print labels window and the label
Board: INV-2 · Barcodes · generate, shared, print labels · desktop (Invory App UI canvas)
- A row's **Print label** opens no window: one label of that variant opens in a new tab, ready to print; the button's name ends "opens
  in a new tab".
- **Print labels · Indigo** opens the window **Print labels · Denim Jacket · Indigo**. One row per size that has a barcode: a checkbox
  with the size ("S") and a **Labels** field (whole number, 1 to 999), filled in with the units in stock; a size at 0 in stock starts
  unchecked, with 1 in its field. Sizes without a barcode are listed greyed: "XL · No barcode yet".
- Under the rows: "Total 6 labels · Label 50 × 30 mm · Price shown" and the link **Change in Settings › Labels**, which opens in a new
  tab and says so in its name.
- Buttons **Cancel** and **Print 6 labels**. **Print 6 labels** opens the labels in a new browser tab, sized for the printer, and the
  browser's print window opens there; the window on the product page closes. No toast, because nothing is saved.
- **A label** (50 × 30 mm; the A4 sheet uses the same layout, 3 × 8 per page), top to bottom:
  1. the product name, bold, at most two lines ("Denim Jacket");
  2. colour and size: "Indigo · S" ("One size" products: the colour alone, "Camel");
  3. the barcode, with the 13 digits under it, always black on white;
  4. the bottom line: the variant SKU in the number font on the left ("PRD-JKT-001-IND-S") and, on the right, the selling price "TND
     89.000" when Settings › Labels says to show it and the product has a selling price.

### Update stock window
Board: Update stock dialog, per color (Invory App UI canvas)
- Opens from **Update stock** in the Sizes & colours card.
- Title **Update stock · Denim Jacket**, under it "Finished units on the shelf, counted per colour and size. Saving sets these
  counts; to add units that arrived, use Restock." and the ✕ "Close".
- **Colour** pills: swatch, name, the colour's total after saving in grey ("Indigo 6"), and a small Denim dot when that colour has a
  changed size; the dot also says "changed" to screen readers ("Indigo, 10, changed"). The first colour (Black) is chosen when the
  window opens. **A product with only one colour has no pills**: its size fields show at once, and the fieldset (for example **Units
  in stock per size · White**) still names the colour.
- Fieldset **Units in stock per size · Indigo**: one field per size (the size fields of S-70), today's count in each, "was 0" under a
  changed size. Switching pills keeps what was typed in every colour.
- Grey band: "Total after saving, all colours" **14 units**, following what is typed in every colour. With one colour the band reads "Total after saving".
- Note: "Doesn't change materials, "Can produce now" or sales."
- Buttons **Cancel** and **Save**. **Save** saves every colour at once → toast "Stock updated.", the window closes, the focus goes back
  to **Update stock**, and the card and the In stock figure show the new counts.
- **"One size" product:** no pills; one field per colour, labelled with the swatch and the colour's name ("■ Camel", "■ Black"), in a
  fieldset "Units in stock per colour".

### Settings › Labels
Board: INV-2 · Settings › Barcodes, Edit and Delete product · desktop (Invory App UI canvas)
- A new page in the Settings group **Catalogue**, after Colours. It holds only how labels print; barcode numbers need no setting.
- Header **Labels**, description "How your barcode labels print."
- **Label size**, a list of two: "50 × 30 mm, one per label (label printer)" · "A4 sheet, 24 labels (3 × 8)".
- The checkbox **Show the selling price on labels**, on by default.
- Button **Save label settings** → toast "Label settings saved.".

### Edit product and Delete product
Board: INV-2 · Settings › Barcodes, Edit and Delete product · desktop (Invory App UI canvas)
- **A colour with stock can't be taken off a product** (INV-1's Colours field): "Indigo has 6 units in stock, so it can't be removed
  from Denim Jacket. Set its stock to 0 first." and nothing is saved. A count of 1,000 or more has its thousands separator
  ("Indigo has 1,000,000 units in stock, so …"); one unit reads "1 unit". A colour at 0 is removed as before; its barcodes stay and come
  back if the colour is added again.
- **Renaming a product keeps its barcodes.** If a barcode was shared with another product of the old name, it now stands for both
  names; the other product keeps it too, and labels already printed still scan. No window asks first.
- A renamed product that later gets a new colour shares the barcodes of that new colour under its current name: the Denim Jacket
  renamed "Trucker Jacket" and given Navy shares Navy S with every other "Trucker Jacket".
- **Delete product** adds one sentence when the product has barcodes: "Its 12 barcodes are kept: they come back on any product named
  Denim Jacket in the same colours and sizes." It is left out when the product has none.

## States

### In stock figure
- **Default:** "10 units", "■ Black 4 · ■ Indigo 6 · ■ Light wash 0", link "By size and colour".
- **Empty:** "0 units", no colour line, link "By size and colour".
- **Loading:** the placeholder labelled "In stock" (today's).
- **Error:** "Couldn't load the stock." and **Try again** (today's), which reloads the figure and the card together.
- **Long content:** 12 colours wrap onto more lines; a 32-character colour name wraps; "1,000,000" fits.

### Sizes & colours card
- **Default:** "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode."; the Stock table as in the
  example above.
- **Empty stock:** every cell a grey 0, and no note.
- **Loading:** the card's frame, heading and tabs at once; the table area a placeholder of 3 rows.
- **Error:** "Couldn't load the stock and barcodes." and **Try again**, which reloads the card and the In stock figure.
- **Long content:** a shoe's 11 size columns; on a laptop under 1100 px the table's box scrolls sideways inside the card, never the
  page.

### Barcodes tab
- **Default** (Indigo chosen): S 2000000000015, M 2000000000022, L 2000000000039, XL 2000000000046, each with **Print label**.
- **Missing:** Light wash: every row "No barcode yet" and **Generate**; the pill "Light wash · 4 missing"; the tab badge "4 missing";
  **Generate missing barcodes (4)**.
- **Shared:** Indigo S: the barcode and "Shared with PRD-JKT-002".
- **Generating:** the confirm button shows a spinner and keeps its label.
- **Generate error:** the confirm window stays open: "Couldn't generate the barcodes. Try again."
- **Success:** toast "4 barcodes generated for Light wash." (one: "Barcode generated for Light wash S."); the rows show their barcodes;
  the badge and the button go.
- **Success, some already shared** (someone generated one a moment ago): toast "4 barcodes ready for Light wash: 3 new, 1 already used
  by PRD-JKT-002." No second barcode is ever made for the same name, colour and size.
- **Print can't be pressed:** **Print labels · Light wash** while Light wash has no barcode, with the reason "Generate the barcodes of
  Light wash first." in a tip that the keyboard reaches.
- **Read-only:** none; archived and draft products work like active ones.
- **Long content:** 12 colour pills wrap; a 32-character colour name wraps inside its pill; "Shared with … and 3 more".

### Update stock window
- **Default:** pills "Black 4", "Indigo 6", "Light wash 0"; choosing Indigo shows its fields 2 · 0 · 3 · 1; "Total after saving, all
  colours 10 units".
- **Changed:** Indigo M from 0 to 4: "was 0" under M, a dot on Indigo, the pill "Indigo 10", the total "14 units".
- **Field error:** under the size, as today: "M: Enter a whole number from 0 to 1,000,000.". When the error is in another colour, that
  pill shows "1 error" in red text, the top of the window reads "Check Black: 1 size can't be saved.", and the window switches to the
  first colour with an error and puts the focus on its field. With one colour there is no pill and no "Check …" line: the message
  under the size is the only one, and that field takes the focus.
- **Changed meanwhile:** at the top of the window: "The sizes or colours of Denim Jacket have changed. Enter the stock again." and the
  fields show the new colours and sizes with their counts. Nothing is saved.
- **Save failed:** "Couldn't save the stock. Try again." (today's); what was typed stays, in every colour.
- **Saving:** **Save** shows a spinner and keeps its label.
- **Success:** toast "Stock updated."; the focus goes back to **Update stock**.

### Print labels window
- **Default:** S 2, M 1 (unchecked, stock 0), L 3, XL 1 → "Total 6 labels · Label 50 × 30 mm · Price shown"; **Print 6 labels**.
- **Error:** under a field: "Enter a whole number from 1 to 999."; **Print** stays clickable and shows the error on click.
- **Nothing chosen:** every size unchecked: **Print 0 labels** can't be pressed, with "Choose at least one size." under the rows.
- **Pop-up blocked:** "Your browser blocked the labels' tab. Allow pop-ups for Invory, then print again."

### Settings › Labels
- **Default:** "50 × 30 mm, one per label (label printer)" and **Show the selling price on labels** ticked.
- **Saving:** **Save label settings** shows a spinner.
- **Success:** toast "Label settings saved.".
- **Read-only:** none; any member of the workshop can change it.

## Wording
- In stock figure: "In stock" · "10 units" · "By size and colour" · "Couldn't load the stock." · "Try again".
- Sizes & colours card: "Sizes & colours" · "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode." ·
  "1 colour × 4 sizes = 4 variants" · "2 colours × One size = 2 variants" · "Manage sizes & colours" · "Update stock" · "Restock" ·
  "Stock by colour" · "Barcodes" · "4 missing" · "Couldn't load the stock and barcodes."
- Stock tab: "Units in stock per colour and size" · "Colour" · "Total" · "In stock" · "Grey zeros are variants with nothing on the
  shelf." · "Last restock: 25 Oct 2026, 24 units" · "See the restocks of Denim Jacket" · "Not restocked yet."
- Barcodes tab: "Colour" · "· 4 missing" · "Generate missing barcodes (4)" · "Print labels · Indigo" · "Print labels" · "Barcodes for
  Indigo" · "Size" · "SKU" · "Barcode (EAN-13)" · "In stock" · "Print label" · "Print label for PRD-JKT-001-IND-S" · "Generate" ·
  "Generate barcode for PRD-JKT-001-LWH-S" · "No barcode yet" · "Shared with PRD-JKT-002" · "Shared with PRD-JKT-002, PRD-JKT-007 and 3
  more" · "One barcode for each colour and size. Every product named Denim Jacket shares it, and a barcode never changes." · "Generate
  the barcodes of Light wash first."
- Generate window: "Generate 4 barcodes?" · "Denim Jacket gets a barcode for Light wash S, M, L and XL, from your in-store numbers. A
  barcode never changes, and its number is never given to anything else." · "Generate a barcode?" · "Denim Jacket in Light wash, size
  S, gets a barcode from your in-store numbers. A barcode never changes, and its number is never given to anything else." · "Not now" ·
  "Generate 4 barcodes" · "Generate barcode" · "Couldn't generate the barcodes. Try again."
- Print labels window: "Print labels · Denim Jacket · Indigo" · "Labels" · "XL · No barcode yet" · "Total 6 labels · Label 50 × 30 mm ·
  Price shown" · "Change in Settings › Labels" · "Cancel" · "Print 6 labels" · "Enter a whole number from 1 to 999." · "Choose at least
  one size." · "Your browser blocked the labels' tab. Allow pop-ups for Invory, then print again."
- Update stock window: "Update stock · Denim Jacket" · "Finished units on the shelf, counted per colour and size. Saving sets these
  counts; to add units that arrived, use Restock." · "Close" ·
  "Colour" · "changed" · "Units in stock per size · Indigo" · "Units in stock per colour" · "was 0" · "Total after saving, all colours"
  · "Total after saving" (one colour) · "14 units" · "Doesn't change materials, "Can produce now" or sales." · "Cancel" · "Save" · "1 error" · "Check Black: 1 size can't
  be saved." · "The sizes or colours of Denim Jacket have changed. Enter the stock again." · "Couldn't save the stock. Try again."
- Settings › Labels: "Labels" · "How your barcode labels print." · "Label size" · "50 × 30 mm, one per label (label printer)" · "A4
  sheet, 24 labels (3 × 8)" · "Show the selling price on labels" · "Save label settings".
- Edit and Delete product: "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first." · "Its 12
  barcodes are kept: they come back on any product named Denim Jacket in the same colours and sizes."
- Toasts: "Stock updated." · "4 barcodes generated for Light wash." · "Barcode generated for Light wash S." · "4 barcodes ready for
  Light wash: 3 new, 1 already used by PRD-JKT-002." · "Label settings saved."
- The spelling is "Colour" everywhere on screen (INV-1); the boards' "color" isn't used.

## Phone layout
Board: INV-2 · Product page, Update stock, labels · 375 (Invory App UI canvas)
- **In stock figure:** under Can produce now (as today); the colour line wraps, two or three colours a row.
- **Sizes & colours card:** the heading and its line, then **Manage sizes & colours**, **Update stock** and **Restock** (main, last), full width,
  44 px, one under the other. The tabs fill the width, half each.
- **Stock tab:** one card per colour in place of the table: the colour tag and its total ("■ Indigo · 6 units"), then the size chips
  [S 2] [M 0] [L 3] [XL 1], zeros kept and grey. Last, a **Total** card "10 units" with the size totals as chips. A shoe's 11 chips wrap.
- **Barcodes tab:** the pills wrap, 44 px tall; **Generate missing barcodes (4)** and **Print labels · Indigo** full width under them;
  then one card per size: the size tag and SKU on the first line, the barcode (bars about 140 px wide) with its digits, "In stock 2",
  "Shared with PRD-JKT-002", and **Print label** (or **Generate**) full width, 44 px.
- **Update stock:** the window fills the screen; the pills wrap at the top, the size fields four to a row (S-70), the total band, then
  **Save** and **Cancel** in a bar fixed at the bottom.
- **Print labels:** fills the screen; one 44 px row per size (checkbox, size, Labels field), the total line, **Print 6 labels** fixed
  at the bottom.
- **Generate window:** full width, buttons one under the other, the confirm button first.
- **Settings › Labels:** one column, **Save label settings** in the bar fixed at the bottom.
- At 320 px nothing makes the page scroll sideways.
