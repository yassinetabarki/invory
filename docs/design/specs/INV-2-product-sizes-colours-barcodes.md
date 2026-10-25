# INV-2 · Product sizes × colours: stock and barcodes — design spec
Status: proposal, decisions taken 2026-10-25 (see "Decisions") · Designer · 2026-10-25 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc), new
row "Product sizes × colours & barcodes (INV-2)":
- "INV-2 · Product page · Sizes & colours · states · desktop" (`project/Inv2SectionStates.dc.html`)
- "INV-2 · Barcodes · generate, shared, print labels · desktop" (`project/Inv2Barcodes.dc.html`)
- "INV-2 · Settings › Barcodes, Edit and Delete product · desktop" (`project/Inv2BarcodeSettings.dc.html`)
- "INV-2 · Product page, Update stock, labels · 375" (`project/Inv2Phone.dc.html`)

**The agreed source is the human's own boards** (not changed): "Product detail with sizes, colors and barcodes"
(`SizeProductShow.dc.html`), "Update stock dialog, per color" (`SizeUpdateStock.dc.html`) and "Size building blocks and states"
(`SizeDetails.dc.html`), plus the canvas thread where the human asked for them (2026-10-24). This spec keeps their layout and fills
the gaps: "One size" products, stock counted before colours, the barcode numbers, printing, every state, the wording and the phone.
Where this spec and the human's boards differ, the difference is listed under "Differences from the boards".

> **The four INV-2 boards were drawn before the decisions and are left as they are. Where they differ from this spec, this spec
> wins.** These parts of the boards are not part of INV-2:
> - The product without colours (section 3 of the states board, section 5 of the barcodes board): every product has a colour.
> - The "No colour" row, pill, swatch and callout (section 2 of the states board, the Update stock pills): stock counted before
>   colours goes into a colour.
> - On the Settings board: the "Barcode numbers" choice (GS1 prefix or in-store), "Not set yet", the prefix error and "12 of 100,000
>   numbers used". On the barcodes board: the callouts "Choose your barcode numbers first…" and "All 100,000 numbers…", the disabled
>   Generate with its tooltip, and the "Only 2 numbers are left…" refusal. Numbers are in-store only and need no setting.
> - The "Rename Denim Jacket?" confirm (the Settings board): barcodes follow the product, with no confirm.
> - The barcode digits 6194512…, which come from a GS1 prefix: in-store numbers start with 2 (2000000000053).
> - "from prefix 6194512" and "99,988 numbers left." in the Generate confirm.
> - Indigo listed and chosen first: colours follow Settings order, so Black comes first and is chosen when a tab or window opens.
> - "Colours (optional)" on the Edit product part of the Settings board: the field is now required.

## Problem and user
The workshop owner counts finished jackets on the shelf with a phone in the stockroom, and prints labels on a laptop at the packing
table. From INV-2 every product has at least one colour (from Settings › Colours; INV-1's "optional" is reversed, decision 1) as
well as the sizes of its category. A **variant** is one colour in one size: the Denim Jacket in Indigo, size M. INV-2 gives each variant its own stock count and its own barcode.

The two questions the new section answers: **how many of each colour and size are on the shelf?** and **which barcode goes on the
label of each one?**

The human's rule: **one barcode stands for one name + colour + size, and every product with the same name, colour and size shares
it.** Two products named "Denim Jacket" (PRD-JKT-001 and PRD-JKT-002) show the same barcode for Indigo M, and generating barcodes for
one never makes a second barcode for a combination the other already has.

## How it looks today
- The Denim Jacket page shows three figures in one row: **Can produce now** "112 units, from current stock, any mix of sizes",
  **In stock** "10 units" with the chips [S 3] [M 0] [L 5] [XL 2] and an **Update stock** button at the top right of that figure, and
  **Unit cost** "TND 35.754, same for every size". Header: "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active"; INV-1 adds the line
  "Colours ■ Black ■ Indigo ■ Light wash".
- **Update stock · Denim Jacket** has one field per size, "was 0" under a changed size, "Total after saving 10 units" and the note
  "Doesn't change materials, "Can produce now" or sales.". Save → "Stock updated.".
- Stock is counted per size only. No product has a barcode. Settings has no barcode page.

## Flow
1. **Nothing to set up for barcodes.** Every barcode is an in-store EAN-13 number (it starts with 2): each new name + colour + size
   takes the next number of the workshop's own range (decision 4). Settings › Barcodes only says how labels print.
2. **On the product page.** Under the three figures, the new card **Sizes & colours** reads "3 colours × 4 sizes = 12 variants. Each
   variant has its own stock and its own barcode." with **Manage sizes & colours** and **Update stock**, and two tabs.
3. **Stock by colour** (first tab): a table, one row per colour, one column per size, with each colour's total and a Total row.
4. **Update stock** (only in the card) opens the window with colour pills, Black chosen first: type Black's sizes, pick Indigo, type
   its sizes; a dot marks each
   colour changed; "Total after saving, all colours 14 units". **Save** → "Stock updated.".
5. **Barcodes** (second tab, badge "4 missing"): pick a colour pill; each size shows its SKU, its EAN-13 barcode with the digits, its
   stock and **Print label**. Light wash shows "No barcode yet" and **Generate** per size; **Generate missing barcodes (4)** at the top
   makes all four after a short confirm → "4 barcodes generated for Light wash.".
6. **A barcode shared by name.** If PRD-JKT-002 is also named "Denim Jacket" and already has Indigo S, the Indigo S row of PRD-JKT-001
   shows the same barcode with "Shared with PRD-JKT-002" (a link). Nothing needs generating for it.
7. **Print.** **Print label** on a row opens one label of that variant, ready to print. **Print labels · Indigo** opens a short window
   to choose how many labels of each size (filled in with the units in stock), then the labels open ready to print.

## Layout

### Product page header and figures
- **Header:** as INV-1 approved it (decision 11): "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active" and under it "Colours
  ■ Black ■ Indigo ■ Light wash". The board's "4 sizes · 3 colors" badge is not used.
- **Can produce now** and **Unit cost:** unchanged.
- **In stock** (the board): "10 units", then one line per colour in Settings order, each a colour tag and its count in bold: "■ Black
  **4** · ■ Indigo **6** · ■ Light wash 0" (a colour at 0 in the grey text colour, its "0" still shown). Screen readers hear "In stock
  per colour: Black 4, Indigo 6, Light wash 0". Under it the link **By size and colour**, which moves to the Sizes & colours card and its
  Stock tab. The figure has no **Update stock** button: it lives only in the card (decided).

### Sizes & colours card (full width, under the figures, before Cost breakdown's row ends; the board's place)
- Heading **Sizes & colours** and the line "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode."
  The counts are values the server sends. Other cases: "1 colour × 4 sizes = 4 variants", "2 colours × One size = 2 variants",
  "1 colour × One size = 1 variant".
- Buttons on the heading's row: outline **Manage sizes & colours** (opens Edit product with the focus on the Colours field; sizes come
  with the category, which sits just above it) and primary **Update stock**.
- **Tabs** (new shadcn component: tabs): **Stock by colour** · **Barcodes**, with a warning badge "4 missing" on Barcodes while any
  variant has no barcode. The open tab is kept in the page address, so a link can open Barcodes directly; it opens on Stock by colour.

### Stock by colour tab
- Table (shadcn table, in a box that scrolls sideways only when the table is wider than the card, named "Units in stock per colour and
  size"): first column **Colour** (row headers: swatch and name), one column per size in size order (S, M, L, XL; a shoe 36 … 46),
  then **Total**. One row per colour in Settings order. Last row **Total**, with a heavier rule above it.
- Every figure in the number font, right-aligned; a 0 in the grey text colour (the readable grey decided at S-71, not the board's
  paler grey; see Accessibility).
- Under the table: "Grey zeros are variants with nothing on the shelf." (The board's sentence about sales, orders and "Put in stock"
  is dropped: those don't count colours in INV-2.)
- **"One size" product** (Leather Tote in Camel and Black): the table has columns **Colour** and **In stock**, no size columns and no
  Total column; the Total row stays when there are two colours or more.
- A product with one colour has one row and no Total row (it would repeat the row).

### Barcodes tab
- **Colour pills** (the board's round pills, a group named "Colour"): swatch, name, and in grey "· 4 missing" when that colour has
  variants without a barcode. The chosen pill has a 2 px Denim ring and a tint, and is announced "pressed". Pills follow Settings
  order; the first colour is chosen when the tab opens (Black for the Denim Jacket). The examples below show Indigo, once picked.
- On the pills' row, to the right: primary **Generate missing barcodes (4)** (only while any variant of the product, in any colour,
  has no barcode; the count is the product's total) and outline **Print labels · Indigo** (with a printer icon).
- Table "Barcodes for Indigo": **Size** (the size tag) · **SKU** (number font: "PRD-JKT-001-IND-S", decision 8) · **Barcode
  (EAN-13)** (the bars, and the 13 digits under them in the number font: "2000000000053") · **In stock** (this product's units of that
  variant) · the row's button.
- The row's button: **Print label** (named "Print label for PRD-JKT-001-IND-S") when the variant has a barcode; **Generate** (named
  "Generate barcode for PRD-JKT-001-LWH-S") when it doesn't, in which case the Barcode cell shows the warning badge "No barcode yet".
- **Shared barcode:** under the digits, a grey line "Shared with PRD-JKT-002" where PRD-JKT-002 is a link to that product. Two or
  three others: "Shared with PRD-JKT-002, PRD-JKT-007". More: "Shared with PRD-JKT-002, PRD-JKT-007 and 3 more" (the link "3 more"
  opens a small list). The SKU is shown, not the name, because the name is the same by definition.
- Under the table: "One barcode for each colour and size. Every product named Denim Jacket shares it, and a barcode never changes."
- **"One size" product:** no pills; one table with one row per colour: **Colour** · SKU "PRD-TOT-001-CAM" · Barcode · In stock ·
  button. **Print labels** prints every colour.
- A product with one colour still shows its one pill, so the colour is always named.

### Generate barcodes · confirm window (alert dialog)
- From **Generate missing barcodes (4)**: title **Generate 4 barcodes?**, body "Denim Jacket gets a barcode for Light wash S, M, L and
  XL. A barcode never changes, and its number is never given to anything else." Buttons **Not now** and **Generate 4 barcodes**.
- From a row's **Generate**: title **Generate a barcode?**, body "Denim Jacket in Light wash, size S, gets a barcode. …" (same last
  sentence). Buttons **Not now** and **Generate barcode**.
- Generating always asks first (decision 12).
- While generating, the confirm button shows a spinner and keeps its label; the window closes on success.

### Print labels window (dialog)
- From **Print labels · Indigo**: title **Print labels · Denim Jacket · Indigo**. One row per size that has a barcode: a checkbox with
  the size ("S") and a **Labels** field (whole number, 1 to 999), filled in with the units in stock; a size at 0 in stock starts
  unchecked with 1 in its field. Sizes without a barcode are listed greyed: "XL · No barcode yet".
- Under the rows: "Total 6 labels · Label 50 × 30 mm · Price shown" and the link **Change in Settings › Barcodes** (opens in a new
  tab; its name says so).
- Buttons **Cancel** and **Print 6 labels**. **Print 6 labels** opens the labels in a new browser tab, sized for the printer, and the
  browser's print window opens there; the window on the product page closes. No toast: nothing is saved.
- From a row's **Print label**: no window; one label of that variant opens in a new tab, ready to print (the button's name ends
  "opens in a new tab").

### What a label holds (50 × 30 mm; the A4 sheet uses the same layout, 3 × 8 per page)
1. Product name, bold, at most two lines ("Denim Jacket").
2. Colour and size: "Indigo · S" (a "One size" product: the colour alone, "Camel").
3. The barcode, with the 13 digits under it, always black on white.
4. Bottom line: the variant SKU in the number font on the left ("PRD-JKT-001-IND-S"), and on the right the selling price
   "TND 89.000" when Settings says to show it and the product has a selling price.

### Update stock window (the board, refined)
- Title **Update stock · Denim Jacket**, under it "Finished units on the shelf, counted per colour and size." and the ✕ "Close".
- **Colour** pills (the board): swatch, name, the colour's total after saving in grey ("Indigo 10"), and a small Denim dot when that
  colour has a changed size; the dot also carries the words "changed" for screen readers ("Indigo, 10, changed"). The first colour in
  Settings order (Black) is chosen when the window opens. The window opens only from the card's **Update stock**.
- Fieldset **Units in stock per size · Black**: one field per size (today's S-70 size fields), today's count in each, "was 0" under a
  changed size. Switching pills keeps what was typed in every colour.
- Grey band: "Total after saving, all colours" **14 units** (follows what is typed, every colour).
- Note: "Doesn't change materials, "Can produce now" or sales."
- Buttons **Cancel** and **Save**. **Save** saves every colour at once → toast "Stock updated.", the window closes, the card and the
  In stock figure show the new counts.
- **"One size" product:** no pills; one field per colour, labelled with the swatch and the colour's name ("■ Camel", "■ Black"), in a
  fieldset "Units in stock per colour".

### Settings › Barcodes (new page, Settings group Catalogue, after Colours)
- Header **Barcodes**, description "Invory gives each new product name, colour and size its own in-store barcode, a number that
  starts with 2. Choose here how labels print."
- No numbering choice and no prefix (decision 4). A grey line gives the count from the server: "12 barcodes made so far."
- **Labels:** **Label size** (select): "50 × 30 mm, one per label (label printer)" · "A4 sheet, 24 labels (3 × 8)". Checkbox **Show
  the selling price on labels** (on by default).
- Button **Save barcode settings** → toast "Barcode settings saved.".
- 375 px: one column, the checkbox in a 44 px row, Save in the bar fixed at the bottom (as other forms).

## The rule "one barcode per name + colour + size", on screen
- **Shown where it is used:** the "Shared with PRD-JKT-002" line on the Barcodes tab (both products show it, each naming the other).
- **No duplicate:** a combination that already has a barcode on any product with the same name never counts as missing and never shows
  **Generate**; it simply shows the shared barcode. If another person generated it a moment ago, generating still makes no second one:
  the toast says "4 barcodes ready for Light wash: 3 new, 1 already used by PRD-JKT-002."
- **Footnote** under the Barcodes table, as in Layout.
- **Deleting a product with barcodes:** the delete window adds one sentence: "Its 12 barcodes are kept: they come back on any product
  named Denim Jacket in the same colours and sizes." (count from the server; left out when it has none).
- **Same name** means the same words, ignoring capitals and extra spaces: "denim  jacket" and "Denim Jacket" share (decision 5).
- **Renaming** (decision 6): a product's barcodes always follow it, with no confirm. Renamed "Trucker Jacket", PRD-JKT-001 keeps its
  8 barcodes; if PRD-JKT-002 is still "Denim Jacket", both keep showing "Shared with" for those barcodes. Edit product shows nothing
  new for a rename.

## Every product has a colour (decision 1), and "One size"
INV-1's "optional" is reversed: a product always has at least one colour.
- **Add product and Edit product:** the field's label is **Colours** (no "(optional)"); the hint stays. Saving with no colour shows
  under the field "Choose at least one colour." and nothing is saved; taking off the last chip and saving does the same.
- **Products saved before INV-2 with no colour** get the first colour of the workshop's list (White in the default list) when INV-2
  ships, so they read "Colours ■ White" until the owner changes them.

| Product                          | In stock figure | Stock tab             | Barcodes tab                 | Update stock         |
| -------------------------------- | --------------- | --------------------- | ---------------------------- | -------------------- |
| Colours and sizes (Denim Jacket) | Per-colour line | Colours × sizes table | Pills, one row per size      | Pills, sizes         |
| One size (Leather Tote)          | Per-colour line | Colour · In stock     | One row per colour, no pills | One field per colour |

## Stock counted before colours (decision 2)
Today's stock is per size, with no colour. When INV-2 ships, it goes into the first colour of the workshop's list on that product,
by default: for the Denim Jacket (Black, Indigo, Light wash in Settings order) S 3 · L 5 · XL 2 become Black S 3 · L 5 · XL 2. A
product that had no colour gets the workshop's first colour (above) and its stock goes there. There is no "No colour" row, pill or
callout. The owner corrects the split with **Update stock** if the units are really Indigo.

## Removing a colour from a product (decision 3)
A colour with units in stock can't be taken off the product. In Edit product, the chip's ✕ still
works, and **Save product** shows under the Colours field: "Indigo has 6 units in stock, so it can't be removed from Denim Jacket.
Set its stock to 0 first." and nothing is saved. A colour at 0 is removed as before; its barcodes stay (they come back if the colour is
added again). The same applies to the category lock already built (S-56): units in stock keep the sizes.

## States

### In stock figure

| State   | What the screen shows                                                                                   |
| ------- | ------------------------------------------------------------------------------------------------------- |
| Default | "10 units", "■ Black 4 · ■ Indigo 6 · ■ Light wash 0", link "By size and colour"                         |
| Empty   | "0 units", no colour line, link "By size and colour"                                                    |
| Loading | Placeholder labelled "In stock" (today's)                                                               |
| Error   | "Couldn't load the stock." + **Try again** (today's), reloads the figure and the card together          |
| Long    | 12 colours wrap onto more lines; a 32-character colour name wraps; "1,000,000" fits (number font)       |

### Sizes & colours card

| State          | What the screen shows                                                                                                                     |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Default        | "3 colours × 4 sizes = 12 variants…", Stock table Black 1 · 0 · 2 · 1 = 4, Indigo 2 · 0 · 3 · 1 = 6, Light wash 0 · 0 · 0 · 0 = 0, Total 3 · 0 · 5 · 2 = 10 |
| Empty stock    | Every cell a grey 0; no callout (a table of zeros is the answer)                                                                          |
| Loading        | The card's frame, heading and tabs at once; the table area a skeleton of 3 rows                                                          |
| Error          | "Couldn't load the stock and barcodes." + **Try again** (reloads the card and the In stock figure)                                       |
| Long           | Shoe sizes: 11 size columns; the table box scrolls sideways inside the card on a laptop under 1100 px, the page never does               |

### Barcodes tab

| State                  | What the screen shows                                                                                                                                   |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Default                | Black chosen on opening; once Indigo is picked: S 2000000000053, M 2000000000060, L 2000000000077, XL 2000000000084, each with **Print label**           |
| Missing                | Light wash: every row "No barcode yet" + **Generate**; pill "Light wash · 4 missing"; tab badge "4 missing"; **Generate missing barcodes (4)**           |
| Shared                 | Indigo S: the barcode and "Shared with PRD-JKT-002"                                                                                                     |
| Generating             | The confirm button shows a spinner, keeps its label                                                                                                     |
| Generate error         | The confirm window stays open: "Couldn't generate the barcodes. Try again."                                                                             |
| Success                | Toast "4 barcodes generated for Light wash." (one: "Barcode generated for Light wash S."); the rows show their barcodes; the badge and button go         |
| Success, some shared   | Toast "4 barcodes ready for Light wash: 3 new, 1 already used by PRD-JKT-002."                                                                          |
| Disabled print         | **Print labels · Light wash** disabled while Light wash has no barcode: reason "Generate the barcodes of Light wash first." (tooltip, keyboard)          |
| Read-only              | None: archived and draft products show, generate and print as active ones (their status is display only)                                               |
| Long                   | 12 colour pills wrap; a 32-character colour name wraps inside its pill; "Shared with … and 3 more"                                                      |

### Update stock window

| State           | What the screen shows                                                                                                              |
| --------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Default         | Pills "Black 4", "Indigo 6", "Light wash 0"; Black chosen, its fields 1 · 0 · 2 · 1; "Total after saving, all colours 10 units"   |
| Changed         | Indigo M 0 → 4: "was 0" under M, a dot on Indigo, pill "Indigo 10", total "14 units"                                             |
| Field error     | Under the size, as today: "M: Enter a whole number from 0 to 1,000,000."; when it is in another colour, that pill shows "1 error" in red text, the top of the window reads "Check Black: 1 size can't be saved.", and the window switches to the first colour with an error and focuses its field |
| Changed meanwhile | Top of the window: "The sizes or colours of Denim Jacket have changed. Enter the stock again." and the fields show the new colours and sizes with their counts |
| Save failed     | "Couldn't save the stock. Try again." (today's); what was typed stays, in every colour                                             |
| Saving          | **Save** shows a spinner and keeps its label                                                                                       |
| Success         | Toast "Stock updated."; focus back on **Update stock**                                                                             |

### Print labels window

| State     | What the screen shows                                                                                       |
| --------- | ----------------------------------------------------------------------------------------------------------- |
| Default   | S 2, M 1 (unchecked, stock 0), L 3, XL 1 → "Total 6 labels · Label 50 × 30 mm · Price shown"; **Print 6 labels** |
| Error     | Under a field: "Enter a whole number from 1 to 999."; **Print** stays enabled and shows the error on click |
| Disabled  | Every size unchecked: **Print 0 labels** disabled, with "Choose at least one size." under the rows        |
| Pop-up blocked | "Your browser blocked the labels' tab. Allow pop-ups for Invory, then print again."                    |

### Settings › Barcodes

| State     | What the screen shows                                                                                                                |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Default   | "50 × 30 mm, one per label (label printer)", "Show the selling price on labels" checked, "12 barcodes made so far."                  |
| Empty     | No barcode yet: "No barcodes made yet." in place of the count                                                                        |
| Saving    | **Save barcode settings** shows a spinner                                                                                           |
| Success   | Toast "Barcode settings saved."                                                                                                     |
| Read-only | None: any member of the workshop can change it (Q3)                                                                                  |

## Accessibility
- **Colour never alone (1.4.1):** every swatch has its name (table rows, pills, In stock line, labels). The chosen pill has a 2 px
  ring, a tint **and** "pressed"; the changed dot carries the word "changed"; "No barcode yet", "4 missing" and "1 error" are words.
- **Grey zeros (1.4.3):** the board's #8A8A90 on white is about 3.4:1 and fails for text; use the grey text colour (#5F5F66 on
  white 6.3:1; #A1A1AA on the dark card 7.0:1), as S-71 decided for the order tables.
- **Warning badge** "4 missing" / "No barcode yet": #8A4405 on #FDF0DC 6.4:1; dark #F3B866 on #3A2609 8.1:1.
- **The barcode is drawn black on a white plate in both themes** (scanners need dark bars on light), with 2 mm of white on each side
  (the quiet zone). The bars are decoration for screen readers; the digits are the text: "Barcode 2000000000053".
- **Tabs (2.1.1, 4.1.2):** a real tab list: arrows move between tabs, Tab goes into the panel; the badge is part of the tab's name
  ("Barcodes, 4 missing").
- **Names (2.4.4):** "Print label for PRD-JKT-001-IND-S", "Generate barcode for PRD-JKT-001-LWH-S", "Shared with PRD-JKT-002"
  (link to that product), "Print labels, opens in a new tab", the table header cells, the size and colour row headers.
- **Disabled buttons:** "Print labels · Light wash" keeps its reason in a tooltip reachable by keyboard (the button stays focusable).
- **Targets (2.5.8, Invory 44 px):** pills 40 px on a laptop (board) and 44 px on a phone; Print label and Generate 40 px on a laptop
  (board), 44 px full width on a phone; checkboxes in Print labels in 44 px rows.
- **Focus (2.4.7, 2.4.11):** after generating, focus goes to the first new barcode's **Print label**; after Update stock, back to
  **Update stock**; after a field error in another colour, to that field. Dialogs trap focus, Esc closes, focus returns.
- **Announcements (4.1.3):** the toasts; "Total after saving" is a polite live region, as today.
- **Reflow (1.4.10):** at 320 px nothing scrolls the page sideways; tables become cards on a phone; on a laptop only the stock table's
  own box may scroll, with its name.

## Phone (375 px)
- **In stock figure:** under Can produce now (today); the colour line wraps, two or three colours a row.
- **Sizes & colours card:** heading and line, then **Update stock** (primary) and **Manage sizes & colours** full width, 44 px,
  one under the other. The tabs fill the width, half each.
- **Stock tab:** one card per colour instead of the table, in Settings order (Black first): the colour tag and its total ("■ Indigo ·
  6 units"), then the size chips [S 2] [M 0] [L 3] [XL 1] (zeros kept, grey). Last, a **Total** card "10 units" with the size totals as chips. A shoe's 11 chips wrap.
- **Barcodes tab:** pills wrap; **Generate missing barcodes (4)** and **Print labels · Indigo** full width under them; then one card per
  size: size tag and SKU on the first line, the barcode (bars about 140 px wide) with its digits, "In stock 2", "Shared with
  PRD-JKT-002", and **Print label** (or **Generate**) full width, 44 px.
- **Update stock:** the window fills the screen; pills wrap at the top, fields four to a row (S-70), the total band, then **Save** and
  **Cancel** in a bar fixed at the bottom.
- **Print labels:** fills the screen; one 44 px row per size (checkbox, size, Labels field), the total line, **Print 6 labels** fixed at
  the bottom.
- **Generate confirm:** the alert dialog at full width, buttons stacked, the confirm button first.
- **Settings › Barcodes:** one column, Save fixed at the bottom.

## Decisions (2026-10-25)
The human decided questions 1 to 6, 11 and where Update stock lives; question 7 wasn't asked (the human's rule covers it). The
product-owner decided 8, 9, 10, 12, 13 and 14.

| #  | Question                               | Decision                                                                   | By            |
| -- | -------------------------------------- | -------------------------------------------------------------------------- | ------------- |
| 1  | A product without colours?             | Not possible: every product has at least one colour (INV-1 reversed)       | Human         |
| 2  | Stock counted before colours?          | Goes into the first colour of the workshop's list, by default              | Human         |
| 3  | Removing a colour that has stock?      | Refused until its stock is 0                                               | Human         |
| 4  | Barcode numbers?                       | In-store EAN-13 only, the next number of the workshop's range; no setting  | Human         |
| 5  | What "the same name" means?            | Ignoring capitals and extra spaces                                         | Human         |
| 6  | Renaming a product with barcodes?      | Its barcodes always follow it, with no confirm                             | Human         |
| 7  | A shared barcode at a scan?            | Not asked: the human's rule covers it                                      | —             |
| 8  | The variant SKU?                       | "PRD-JKT-001-IND-S"                                                        | Product-owner |
| 9  | Printing?                              | A row prints one label; "Print labels · Indigo" asks a count per size      | Product-owner |
| 10 | Labels?                                | 50 × 30 mm or A4 3 × 8 in Settings, price shown by default                 | Product-owner |
| 11 | Product header?                        | INV-1's, unchanged                                                         | Human         |
| 12 | Confirm before generating?             | Always                                                                     | Product-owner |
| 13 | Archived and draft products?           | Generate and print like active ones                                        | Product-owner |
| 14 | The board's notes on sales and scans?  | Dropped                                                                    | Product-owner |
| —  | Where does Update stock live?          | In the Sizes & colours card only                                           | Human         |

Colours follow Settings order everywhere, so Black comes first on the Denim Jacket.

## Differences from the boards
The human's boards differ from this spec as below. For the four INV-2 boards, see the note at the top.
1. **Header badge:** the board's "4 sizes · 3 colors" is not used; INV-1's header stays (decision 11).
2. **Footnotes:** the board's "Sales, orders and "Put in stock" now pick a color and a size" and "scanning a label at sale or stock
   count picks that exact variant" describe things INV-2 doesn't do; replaced by the sentences above (decision 14).
3. **"Cancelled orders also add here."** in the Update stock note: S-57 is on hold, so INV-2 leaves it out until S-57 resumes.
4. **Grey zeros** use the readable grey (Accessibility).
5. **Pill note** "· not generated" becomes "· 4 missing", the same words as the tab badge, and right for a colour half generated.
6. **SKU** "JKT-001-IND-S" becomes "PRD-JKT-001-IND-S" (decision 8; product SKUs are typed freely, so not all start with "PRD-").
7. **Spelling** "Colour" everywhere (INV-1); the boards say "color".
8. **"Update stock"** lives in the card only, as on the board (decided); this changes S-56's delivered AC-2, so the card says so.
9. **Order:** the boards list Indigo first; Settings order puts Black first.
10. **Barcode digits:** the board's 6194512… are GS1-style; in-store numbers start with 2 (decision 4).

## For the product-owner
Proposed content of the UI card `docs/stories/INV-2-product-sizes-colours-barcodes-ui.md`:
- **Pages:** the product page's In stock figure and the Sizes & colours card with its two tabs; the Update stock window; the Generate
  confirm; the Print labels window and the label; Settings › Barcodes (labels only); the extra sentence in Delete product; the Colours
  field in Add / Edit product (required, and the refusal for a colour with stock). Boards: the human's three, and the four INV-2 boards
  above with the note at the top.
- **INV-1's UI card** says colours are optional ("Colours (optional)", "None yet · Add colours"); decision 1 reverses that, so INV-1's
  card needs the human's yes for the change (it is `Approved`).
- **States, wording, phone:** the tables and sections above, word for word.
- **Toasts:** "Stock updated." · "4 barcodes generated for Light wash." · "Barcode generated for Light wash S." · "4 barcodes ready
  for Light wash: 3 new, 1 already used by PRD-JKT-002." · "Barcode settings saved."
- **Messages:** "Choose at least one colour." · "Couldn't generate the barcodes. Try again." · "Generate the barcodes of Light wash first." · "The sizes or colours of Denim Jacket have
  changed. Enter the stock again." · "Check Black: 1 size can't be saved." · "Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0
  first." · "Enter a whole number from 1 to 999." · "Choose at least one size." · "Your browser blocked the labels' tab. Allow pop-ups
  for Invory, then print again." · "Couldn't load the stock and barcodes." · "12 barcodes made so far." · "No barcodes made yet."
- **Rules the development card will need:** every product has at least one colour; a variant is a colour and a size; stock is
  counted per variant (a count, the last save wins, as S-56); stock from before INV-2 goes into the first colour; a barcode per name
  (ignoring capitals and extra spaces) + colour + size, shared, never changed, never reused, following its product through a rename;
  in-store EAN-13 numbers from the workshop's range; generating is all-or-nothing and always confirmed; the colour lock.
- **Leave out of INV-2** (say so in the Description): orders, sales and "Put in stock" per colour (S-57 needs a colour when it
  resumes), scanning, restock of finished products (INV-3), clients (INV-4), progress (INV-5), a composition per colour (an Indigo and
  a Black jacket use the same materials in INV-2, so "Can produce now" keeps "any mix of sizes"), barcodes on the Products list.

## For the tech-lead
- **Values the server sends (product page):** the variant counts ("3 colours × 4 sizes = 12 variants"); per colour × size: units; per
  colour total, per size total, grand total; per variant: its SKU, its barcode's 13 digits or none, and the other products sharing it
  (id, SKU, at most three, plus how many more); missing barcodes per colour and for the product; label size and "show price"; the
  barcodes count for the delete sentence and for Settings ("12 barcodes made so far.").
- **Writes:** Update stock for every colour and size at once (with the "changed meanwhile" check on colours as well as sizes);
  generate missing (all-or-nothing, never two barcodes for one name + colour + size, never one number twice, also when two people
  click at once; reports new vs already used); the label settings; the Colours field now required.
- **Barcode numbers:** in-store EAN-13 only (starts with "2"), the next number of the workshop's own range, check digit by the
  server; the range's layout and size are yours. The examples use 2000000000053.
- **Moving existing stock:** S-56's per-size rows go into the first colour of the workshop's list on each product; a product with no
  colour first gets the workshop's first colour.
- **Rename:** barcodes follow the product, so a shared barcode can stand for two names after a rename. Which name a later new
  combination is matched against needs your rule (see "Still open").
- **Drawing:** the bars from the 13 digits on screen and on the labels; a print page (new tab) sized for 50 × 30 mm and A4 3 × 8.
- **New shadcn components:** tabs. The rest exists: table, badge, dialog, alert-dialog, checkbox, select, tooltip,
  Callout, skeleton, sonner.
- **Costly or risky:** the shared register (name matching, rename, delete); moving S-56's stock into a colour; the print page; the
  required Colours field and the colour lock in Edit product (INV-1 screens). S-57 (on hold) writes stock per size and will need a colour.

## Still open
1. **Stock from before INV-2 on a product whose colours don't include the workshop's first colour** (the Denim Jacket has Black,
   Indigo, Light wash; the default list starts with White). (a) It goes into the product's own first colour in Settings order (Black)
   **recommended**, which is how this spec reads decision 2 · (b) the workshop's first colour (White) is added to the product and
   the stock goes there · default (a).
2. **After a rename, which name does a new combination share with?** PRD-JKT-001, renamed "Trucker Jacket", gets the new colour
   Navy. (a) It matches "Trucker Jacket" (its name now) **recommended** · (b) it matches "Denim Jacket", the name its barcodes were
   made under · default (a).
