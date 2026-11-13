---
id: INV-21
title: Material stock per colour
type: ui
priority: Must
status: Approved
created: 2026-11-08
tags:
  - ui-card
---

# INV-21 · Material stock per colour — UI card

> [!abstract] Description
> A material can now keep its stock **per colour**. Fabric bought by colour is one material, **Denim 12oz**, with **Indigo 250 m** and
> **Black 40 m**, instead of two materials. Buttons, thread and labels stay on today's **One stock for all colours**. The owner
> chooses the setting on Add and Edit material; a second setting lets each colour have its **own unit cost**. A product's colour takes
> its material from the same colour of the list in Settings › Colours.
> New: the two settings and the Colours table on the material form; **Stock by colour** on the material page; "2 colours" on the
> Materials list; a Restock sheet with a quantity per colour; the confirmations when the settings are switched; the "no stock for
> this colour" state with **Add Light wash to Denim 12oz**; colour-aware wording on the product page, Start batch and "Used in".
> Left out on purpose: merging materials such as "Denim 12oz Indigo" and "Denim 12oz Black" (the owner fixes those by hand), a hint
> that two materials look alike, a reorder level per colour, and any change to Settings › Colours.

> [!info] How it looks today
> A material has one quantity in stock and one unit cost. Colour-specific fabric is made by creating one material per colour, "Denim
> 12oz Indigo" (250 m) and "Denim 12oz Black" (40 m), and the Denim Jacket's composition then lists both. The material's **Colour**
> field is free text ("Antique brass"), not a colour of the list. Restock adds one quantity to one stock. The product page's
> bottleneck reads "Bottleneck: Denim 12oz Black (Black, size XL)." and Start batch says "Short on Denim 12oz Black."

## Pages

Worked example used throughout: **Denim 12oz** (fabric, metres, reorder at 100 m) with **Indigo 250 m** and **Black 40 m**. With
cost per colour: Indigo TND 8.500 / m, Black TND 9.200 / m, so the stock value is Indigo TND 2,125.000 + Black TND 368.000 = **TND
2,493.000**. The product is the **Denim Jacket** (Indigo, Black); **Light wash** is added only to show a colour the material lacks.
Quantities and prices show as the screens always show them ("250 m", "TND 8.500"). The screens never add or compare numbers; every
figure, status and sentence comes from the server.

### Colour line (used on several pages)
Board: `Inv21Stock` (desktop)
- A colour line is the colour's swatch and name, then its quantity with the unit, then a status word **only when it isn't fine**:
  **Low stock** or **Out of stock**, with the same icons and words as today's stock badge. A colour that is fine shows no badge
  (twenty green badges would hide the two that matter); the table on the material page shows the plain muted word "In stock".
- A colour is never shown by its swatch alone: its name is always beside it, and every status has its word.
- Colours are always listed in the order of Settings › Colours, on every screen. They never reorder by urgency. With the default
  list, Black comes before Indigo.

### Material page — Stock by colour
Board: `Inv21Stock` (desktop, top)
1. Header unchanged (name, SKU, category). Its badge is the **material's**: **Low stock** when any colour is at or under the reorder
   level (0 counts), **Out of stock** only when every colour is at 0, otherwise **In stock**.
2. The four figures under the header keep their places and change where one number would mislead:
   - **In stock, all colours** — 290 m, with the small line "2 colours".
   - **Reorder at** — 100 m, with the small line "each colour".
   - **Unit cost** — one cost as today; with cost per colour a range, "TND 8.500–9.200 / m", with the small line "per colour".
   - **Stock value** — the total: TND 2,493.000 with cost per colour.
3. The warning callout names the colours. One colour: **"Black is below reorder level."** with "40 m on hand, reorder at 100 m."
   Several: **"3 colours are at or below reorder level."** with "Black 40 m, Camel 0 m and Rust 12 m. Reorder at 100 m." One at 0:
   **"Camel is out of stock."** with "0 m on hand, reorder at 100 m."
4. A new card **Stock by colour** sits between the callout and Details. Title "Stock by colour"; description "Each colour has
   its own stock. A product takes its material from its own colour." A table in Settings › Colours order:

   | Colour      | In stock | Unit cost*    | Stock value*  | Status    | Button         |
   | ----------- | -------- | ------------- | ------------- | --------- | -------------- |
   | Indigo      | 250 m    | TND 8.500 / m | TND 2,125.000 | In stock  | Restock Indigo |
   | Black       | 40 m     | TND 9.200 / m | TND 368.000   | Low stock | Restock Black  |
   | All colours | 290 m    |               | TND 2,493.000 |           |                |

   - \*The Unit cost and Stock value columns show only when cost is per colour; with one cost for every colour they are left out
     (the figures above the card carry them).
   - Each row's **Restock** button is named for the colour ("Restock Black") and opens the Restock sheet with that colour's
     quantity field ready to type in.
   - The last row is the total.
5. With **more than 8 colours** a line sits above the table: "3 of 20 colours need restock" ("1 of 12 colours needs restock" for
   one, "0 of 12 colours need restock" for none), with a switch **Only colours that need restock**. The switch filters; it never
   reorders, and when it leaves nothing the card reads "No colour needs restock." With 8 colours or fewer neither shows.
6. The **Details** card leaves out the free-text **Colour** row for a per-colour material (the text is kept, not shown).
7. **Used in** is described under "Product pages and Start batch" below.

### Materials list
Board: `Inv21Stock` (desktop, bottom)
- A one-stock material looks exactly as today. A per-colour material differs in three cells:
  - **In stock:** the total in bold (290 m) and under it a small button **2 colours** with a chevron ("1 colour" for one), whose
    accessible name is "2 colours, show stock by colour for Denim 12oz". It opens the row's inline panel, a third panel beside Restock and History.
  - **Reorder at:** "100 m" with the muted words "each colour" under it. **Unit cost:** one cost, or the range with "per colour".
  - **Status:** the badge, and under it, in muted text, the colours that need restock: "Black", "Black and Camel", "Camel, Black and
    Light wash"; from four on the first three and a count ("Camel, Black, Light wash and 2 more").
- The panel is titled "Stock by colour · Denim 12oz", with **Close**. It shows one tile per colour (swatch and name, the quantity in
  bold, and a badge only when Low or Out), two to five tiles per row. Footer: "Reorder at 100 m for each colour." Tiles are not
  buttons.
- The filter **Needs restock** keeps its meaning and now includes materials where any colour is low or out. The sort **Stock
  low→high** sorts by the total shown.

### Restock a per-colour material
Board: `Inv21Restock`
A per-colour material opens Restock in a **side sheet** (from the material page, the Materials list, or a colour's **Restock**
button). A one-stock material keeps today's panel and dialog, unchanged.
1. Title "Restock Denim 12oz". Under it: "In stock now: 290 m in 2 colours · cost per colour" (or "· unit cost TND 8.500" when the cost is
   the same).
2. **Date** (today by default, not after today) and **Reference** (optional, 64 characters, placeholder "PO or delivery note"), side
   by side, as today.
3. With the **same cost for every colour**, one **Unit price paid** field, as today, sits above the colour lines.
4. Help line: "Type a quantity for each colour you received. Leave the others empty."
5. One line per colour of the material, in Settings order: swatch and name, the muted "Now 40 m" under the name, a **Quantity
   received** field with the unit, and, with cost per colour, a **Unit price paid** field with the currency (its placeholder is that
   colour's current cost). An empty line is not part of the receipt.
6. With more than 8 colours, **Find colour** appears above the lines. A quantity already typed stays when the filter hides its line.
7. The checkbox keeps today's behaviour, checked by default: **Use this price as the new unit cost** (same cost) or **Use these
   prices as the new unit costs** (cost per colour; only colours that received a quantity change).
8. A fixed footer: "2 colours in this receipt" · **Cancel** · **Add to stock**, with the line "Logged in the material's history. The
   new stock is calculated when you save."
9. Opened from a colour's Restock button, that colour's quantity field is focused and in view; nothing else differs.
10. Saved: the toast **"Restocked Denim 12oz: Indigo +100 m, Black +20 m."** and the stock becomes Indigo 350 m, Black 60 m. The
    sheet closes and focus returns to the button that opened it. A receipt of one colour reads "Restocked Denim 12oz: Indigo +100
    m." (today's form).

### Add and Edit material
Board: `Inv21MaterialForm`
The sections stay (Identity, Stock and cost, Supplier and notes, Photo). The changes are in **Stock and cost**.
1. **How is stock kept?** is two radio cards: **One stock for all colours** ("For buttons, thread, labels. One quantity, whatever the
   colour.", chosen on a new material) and **Stock per colour** ("For fabric and leather. A quantity for each colour, such as
   Indigo 250 m and Black 40 m.").
2. **How is cost set?** shows **only** when Stock per colour is chosen (hidden otherwise, never greyed): **Same cost for every
   colour** ("One unit cost for the whole material.", chosen) and **Cost per colour** ("Each colour has its own unit cost, such as
   Indigo TND 8.500 and Black TND 9.200.").
3. What the section holds, always starting with **Unit of measure** and ending with **Reorder at**:

   | Stock       | Cost       | Fields                                                    |
   | ----------- | ---------- | --------------------------------------------------------- |
   | One stock   | (hidden)   | Unit cost, Quantity in stock (today's form)               |
   | Per colour  | Same       | Unit cost, then the Colours table: Colour, Quantity in stock |
   | Per colour  | Per colour | The Colours table: Colour, Quantity in stock, Unit cost   |

4. **Colours table.** Heading "Colours", help "From Settings › Colours. A product's colour takes its material from the same colour."
   One row per colour: swatch and name, a **Quantity in stock** field with the unit, a **Unit cost** field when cost is per colour,
   and **Remove**. Below the table, **Add colour**.
5. **Add colour** opens the same searchable list as the Colours field on Add product ("Search colours", only the colours the material
   doesn't have yet, "3 chosen", **Done**; with no match: "No colour named "teal". Missing one? Add it in Settings › Colours.",
   opening in a new tab). A colour added is a new row at quantity 0; with cost per colour its **Unit cost** is empty and required.
6. **Remove** works only for a colour at 0 with no history (for example one just added). Any other colour's Remove is greyed but
   stays reachable by keyboard and says why: "Indigo has 250 m in stock, so it can't be removed." or "Black has history, so it can't
   be removed." On a phone the reason is written under the colour's name. The working button is named "Remove Black".
7. A colour's quantity can be corrected here, as the single quantity can today; the change is logged as a correction for that
   colour.
8. **Switching to Stock per colour on a material that has stock** (Denim 12oz, 250 m): the single **Quantity in stock** is replaced
   by the Colours table with one row, a colour picker ("Choose a colour") holding the 250 m, and above it an info note: "You have
   250 m in stock now. Choose the colour that holds it. Colours you add start at 0." Saving without choosing reads "Choose the
   colour that holds the 250 m you have now." under the picker. On a material at 0 stock there is no note and the table starts
   empty with **Add colour**. Nothing is saved until **Save material**; **Cancel** discards the switch.
9. **Switching cost to Cost per colour:** no window. The cost column appears with every colour filled with the current unit cost, and
   under the table: "Each colour starts at the current cost, TND 8.500 / m. Change the ones that differ."
10. **Switching cost back to Same cost for every colour** opens a window: **"Use the same cost for every colour?"** "Choose the cost
    to keep. Every colour will use it, and the stock value changes with it." Then a list of the saved costs, none chosen ("Indigo ·
    TND 8.500 / m", "Black · TND 9.200 / m"), and **Keep cost per colour** · **Use this cost for all colours** (not usable until
    one is chosen; the line "Choose the cost to keep." stays on screen).
11. **Switching back to One stock for all colours** opens a window: **"Switch to one stock for all colours?"** "**Indigo 250 m +
    Black 40 m become one stock of 290 m.** The history of each colour stays." With cost per colour the same window adds "Choose
    the cost to keep for every colour:" with the list above and "The stock value changes with the cost you keep." Buttons: **Keep
    stock per colour** (closes it and puts the radio back; Esc does the same; always usable) · **Switch to one stock** (not usable
    until the total is known and, when one is asked, a cost is chosen). While the total is worked out the window shows a placeholder
    line; if that fails it reads "Couldn't work out the total." with **Try again**. **Keep stock per colour** stays usable in both
    cases; **Switch to one stock** stays off. After confirming, the form shows one **Quantity in stock**
    (290 m) and **Unit cost**; nothing is saved until **Save material**.
12. **Colour (free text)** in Identity is hidden while Stock per colour is chosen; its text is kept for when the owner switches
    back. A one-stock material shows it as today, with no new hint.
13. **Add material** is the same form, One stock chosen. Choosing Stock per colour shows an empty table: "No colours yet. Add the
    colours you buy this material in." with **Add colour**. A per-colour material needs at least one colour to be saved.
14. **Reorder at** is one field. For a per-colour material its hint reads "Applies to each colour. The material shows Low stock when
    any colour is at or under this level." (a one-stock material keeps "Shown as Low stock at or below this level.").
15. **Save material** and the toasts are today's: "Material updated." and "Material created."

### Product pages and Start batch
Board: `Inv21Readers`
1. **Composition table (product page).** For a per-colour material, **In stock** shows the product's own colours with their
   stock ("Indigo 250 m · Black 40 m"; up to 3 colours, then "5 colours" with a list on press). **Units possible** reads **Varies**
   with a small **View by colour** link opening "Indigo 126 units · Black 20 units". A line "Same for every variant" on a per-colour
   material varies by colour in the same way. A product colour the material doesn't have reads "Light wash — no stock" in the
   warning tone with the link **Add Light wash to Denim 12oz**.
2. **Bottleneck.** A colour the material lacks: **"Bottleneck: Denim 12oz — no Light wash stock."** with "Denim 12oz has no Light
   wash yet, so Denim Jacket can't be made in Light wash." and the link **Add Light wash to Denim 12oz**. "Can produce now" reads 0 (the
   guaranteed figure); the "Best case: 112 units, in Indigo — limited there by Metal shank button 17mm." line is unchanged. In
   **Capacity by colour** the Light wash row reads Guaranteed 0, Best 0, Limited by "Denim 12oz — no Light wash stock", with the
   same link. A normal limit reads **"Bottleneck: Denim 12oz, Black stock (size XL)."** with "40 m of Black in stock covers 16 units
   of Black, size XL — the smallest across every colour and size." When the colour that limits is itself at or under the reorder
   level, the sentence "It's also below its reorder level of 100 m." follows (Black, 40 m); for Indigo (250 m) it doesn't. On a
   cost-per-colour material, the unit-cost and margin ranges stay for the colours that have a cost, and each colour the material
   lacks adds one line under them: "No unit cost yet. Add Light wash to Denim 12oz." When every colour is lacking there is no range
   and no margin, only those lines.
3. **Add Light wash to Denim 12oz** opens a small window (the page stays behind it): **"Add Light wash to Denim 12oz?"** "Light wash
   starts at 0 m, so Denim Jacket can't be made in Light wash until you restock it." With cost per colour it adds a required field **Unit
   cost of Light wash** (per m). **Cancel** · **Add Light wash**. The toast reads **"Light wash added to Denim 12oz."** with the
   button **Restock Light wash**, which opens the Restock sheet on that colour. The same window opens from every place the link
   appears, over Start batch when used there; what was typed stays. After a colour is added, focus returns to the link; if the link
   is gone, focus stays in the window.
4. **Start batch.** The materials table has one row per material and colour the batch uses: "Denim 12oz · Indigo" needs 33.88 m,
   in stock 250 m; "Denim 12oz · Black" needs 8.8 m, in stock 40 m. A one-stock material keeps one row. A colour the material
   lacks: needs 8.8 m, in stock "No stock", short by 8.8 m, with **Add Light wash to Denim 12oz**. The shortfall sentence names
   material and colour: **"Short on Denim 12oz in Black. Restock it first, or lower the units."** Two: "Short on Denim 12oz in
   Black and Metal shank button 17mm. Restock them first, or lower the units." The same material in two colours: "Short on
   Denim 12oz in Black and Light wash." One colour and size, or a product with no By colour and size line, keeps "You can cut at
   most N now: Denim 12oz in Black runs short."
5. **Used in** (material page, "What you can make with it"). Under **This stock covers** the range shows ("20–126 units") with a
   small button **By colour** that opens a line under the row: "Indigo 126 units · Black 20 units · Light wash no stock" (the last
   with **Add Light wash to Denim 12oz**). **Limited by** reads "This material (bottleneck), Black stock" when it is the limit. The
   intro for a per-colour material: "Here's how far your stock goes, colour by colour, and how many units you can make right now
   with all their materials." The suggestion names the colour: "this material limits Denim Jacket to 16 units, in Black. If you
   restock Black, the next limit is …".
6. **Other screens, wording only, no new layout:**
   - **Dashboard › Needs restock:** the figure still counts materials; each row names its colours ("Denim 12oz · Black 40 m, Camel
     0 m"). **Inventory value** is the sum, with each colour's own cost where cost is per colour.
   - **Add and Edit product › Composition:** the material search shows "290 m in 2 colours" for a per-colour material; when the
     product has a colour the material lacks, a note under the line: "Denim 12oz has no Light wash stock yet. Add Light wash".
   - **Material history:** each entry of a per-colour material names its colour with its swatch ("Restock · Indigo +100 m"); the
     entries of one receipt sit together under its date and reference. "Back from cutting" names the colour taken from or returned
     to.

## States

| State                          | Content                                                                                                                                  |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------- |
| **One colour only**            | The card and panel still show: "Stock by colour" with one row; the button reads "1 colour"                                               |
| **Many colours**               | 20 colours: the card's table scrolls with the page; the summary line and switch show; the list panel is a grid of tiles                  |
| **Everything fine**            | No callout, no badge on a tile; the material's badge reads "In stock"                                                                    |
| **Every colour at 0**          | The material reads "Out of stock" everywhere                                                                                             |
| **No colours**                 | A per-colour material always has one; on the form: "No colours yet. Add the colours you buy this material in."                           |
| **Loading**                    | The material page and list show at once; History keeps its placeholder as today; the switch-back total shows a placeholder line          |
| **Error, quantity**            | Under the field, colour first: "Black: Enter a number from 0, with up to 4 decimals."                                                    |
| **Error, cost**                | "Black: Enter a unit cost." (also for a colour just added on a cost-per-colour material)                                                 |
| **Error, no colour**           | "Add at least one colour, or choose One stock for all colours." under the table                                                          |
| **Error, colour for stock**    | "Choose the colour that holds the 250 m you have now." under the picker                                                                  |
| **Error, restock line**        | "Indigo: Enter a number above 0, with up to 4 decimals." · "Black: Enter the price paid." Focus goes to the first error; typed values stay |
| **Error, restock empty**       | "Enter a quantity for at least one colour." above the lines                                                                              || **Error, data changed**        | "The colours of Denim 12oz have changed. Enter the quantities again." and the lines are drawn again                                      |
| **Disabled, Remove**           | Greyed, reachable by keyboard, with its reason in words                                                                                  |
| **Disabled, dialog buttons**   | Not usable until a cost is chosen, with "Choose the cost to keep." on screen                                                             |
| **Confirm**                    | The two switch-back windows and "Add Light wash to Denim 12oz?"; buttons are verbs                                                       |
| **Long content**               | A 32-character colour name wraps inside its cell; 7-digit quantities stay on one line                                                    |

Add to stock is never greyed without a reason: it stays pressable and the messages explain.

- **Too much stock:** "Indigo: The stock would exceed 9,999,999,999.9999." under the Restock line; on the material form the same
  sentence without the colour, under the table.
- **Restock sheet won't open:** a toast, "Couldn't open Restock for Denim 12oz. Try again.", and no sheet.

## Wording
- Settings: "How is stock kept?" · "One stock for all colours" · "For buttons, thread, labels. One quantity, whatever the colour." ·
  "Stock per colour" · "For fabric and leather. A quantity for each colour, such as Indigo 250 m and Black 40 m." · "How is cost set?"
  · "Same cost for every colour" · "One unit cost for the whole material." · "Cost per colour" · "Each colour has its own unit cost, such as
  Indigo TND 8.500 and Black TND 9.200."
- Colours table: "Colours" · "From Settings › Colours. A product's colour takes its material from the same colour." · "Add colour" ·
  "Remove Black" · "Indigo has 250 m in stock, so it can't be removed." · "Black has history, so it can't be removed." · "No colours yet.
  Add the colours you buy this material in." · "Each colour starts at the current cost, TND 8.500 / m. Change the ones that differ."
  · "You have 250 m in stock now. Choose the colour that holds it. Colours you add start at 0."
- Switching: "Switch to one stock for all colours?" · "Indigo 250 m + Black 40 m become one stock of 290 m. The history of each colour
  stays." · "Choose the cost to keep for every colour:" · "The stock value changes with the cost you keep." · "Keep stock per
  colour" · "Switch to one stock" · "Use the same cost for every colour?" · "Choose the cost to keep. Every colour will use it, and the
  stock value changes with it." · "Choose the cost to keep." · "Keep cost per colour" · "Use this cost for all colours" · "Couldn't work
  out the total." · "Try again".
- Stock by colour: "Stock by colour" · "Each colour has its own stock. A product takes its material from its own colour." · "In stock,
  all colours" · "2 colours" · "each colour" · "per colour" · "All colours" · "Black is below reorder level." · "3 colours are at or
  below reorder level." · "Camel is out of stock." · "3 of 20 colours need restock" · "Only colours that need restock" · "Reorder at
  100 m for each colour." · "2 colours, show stock by colour for Denim 12oz" · "Restock Black".
- Reorder hint: "Applies to each colour. The material shows Low stock when any colour is at or under this level."
- Restock: "Restock Denim 12oz" · "In stock now: 290 m in 2 colours · cost per colour" · "Type a quantity for each colour you received.
  Leave the others empty." · "Quantity received" · "Unit price paid" · "Now 40 m" · "Find colour" · "Use these prices as the new unit
  costs" · "2 colours in this receipt" · "Add to stock" · "Restocked Denim 12oz: Indigo +100 m, Black +20 m."
- Missing colour: "Bottleneck: Denim 12oz — no Light wash stock." · "Denim 12oz has no Light wash yet, so Denim Jacket can't be made in Light wash." ·
  "Add Light wash to Denim 12oz" · "Add Light wash to Denim 12oz?" · "Light wash starts at 0 m, so Denim Jacket can't be made in Light
  wash until you restock it." · "Unit cost of Light wash" · "Add Light wash" · "Light wash added to Denim 12oz." · "Restock Light wash" ·
  "Light wash — no stock" · "No unit cost yet. Add Light wash to Denim 12oz.".
- Product page: "Bottleneck: Denim 12oz, Black stock (size XL)." · "40 m of Black in stock covers 16 units of Black, size XL — the
  smallest across every colour and size." · "It's also below its reorder level of 100 m." · "Varies" · "View by colour" · "By colour".
- Start batch: "Short on Denim 12oz in Black. Restock it first, or lower the units." · "Short on Denim 12oz in Black and Metal shank button
  17mm. Restock them first, or lower the units." · "Short on Denim 12oz in Black and Light wash." · "You can cut at most N now: Denim 12oz in
  Black runs short." · "No stock".

## Phone layout
Board: `Inv21Phone`
- **Material page:** the four figures stack two by two; **Stock by colour** keeps its table as one block per colour (name, quantity,
  status, a 44 px Restock button).
- **Materials list:** a row is a card as today; "In stock" shows the total with the **2 colours** button (44 px), and the panel opens
  inside the card with the colours one per line (swatch, name, quantity, badge).
- **Restock sheet:** full height. Each colour is one block (name and "Now 40 m" on the first row; below it **Quantity received** and,
  with cost per colour, **Unit price paid** side by side, 44 px high, labels above the fields). Date and Reference stack. The footer
  with the count, a 44 px **Cancel** button and **Add to stock** beside it is fixed at the bottom.
- **Material form:** one column; the two settings stack as full-width cards (at least 44 px high); each colour is a block (name,
  Remove, quantity, cost), with the reason a colour can't be removed written under its name; **Add colour** is full width and 44 px;
  **Save material** stays in the bar at the bottom; the windows are full width.
- **Product page, Start batch, Used in:** the new lines and links wrap full width; nothing scrolls sideways at 320 px; every field
  and button is 44 px high.

## What this changes in INV-16's wording
INV-16's cards stay as approved; this section is what the development card of INV-21 takes over from them. It applies to a material
set to Stock per colour; today's way of making a line "only for Indigo" (cells only under Indigo) keeps working for one-stock
materials, and INV-17's cards need no change.
1. **Bottleneck and "Limited by":** "Bottleneck: Denim 12oz Black (Black, size XL)." becomes "Bottleneck: Denim 12oz, Black stock
   (size XL)."; in Capacity by colour, "Denim 12oz Black (XL)" becomes "Denim 12oz, Black stock (XL)". A missing colour reads
   "Denim 12oz — no Light wash stock".
2. **Start batch:** "Short on Denim 12oz Black." becomes "Short on Denim 12oz in Black."; the "at most N" message names "Denim 12oz in
   Black"; the materials table has one row per material and colour.
3. **Grid sheet:** for a per-colour material it carries one more muted line: "Each colour takes its stock from the same colour of
   Denim 12oz." The composition line shows "Stock per colour" under the material's name.
4. **Worked example:** the two materials "Denim 12oz Indigo" and "Denim 12oz Black" become one material, Denim 12oz, with two
   colours. The numbers stay the same when the quantities are the same: guaranteed 16, best 112, Indigo 103 / 112, Black 16 / 22.
5. **"Varies":** a line "Same for every variant" on a per-colour material now also varies by colour, with **View by colour**.
