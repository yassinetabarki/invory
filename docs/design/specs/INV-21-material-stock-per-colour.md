# INV-21 · Material stock per colour — design spec
Status: proposal · Designer · 2026-11-08 · Boards: [Invory App UI canvas][canvas], title "Material stock per colour (INV-21)" —
`Inv21Stock`, `Inv21Restock`, `Inv21MaterialForm`, `Inv21Readers`, `Inv21Phone`

[canvas]: https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc

Built on the 17 decisions in `docs/BACKLOG.md` (INV-21, D-30). Nothing in them is reopened. Quantities are written the way the
screens show them (trailing zeros dropped: "250 m", not "250.0000 m"); money with the installation's decimals (TND 8.500).

## Problem and user
The workshop owner buys fabric by colour: 250 m of Indigo denim, 40 m of Black. Today that means one material per colour
("Denim 12oz Indigo", "Denim 12oz Black"). From INV-21 a material can be set to **stock per colour**: one material, one quantity
for each colour of the workshop's list (Settings › Colours). A product's colour then takes its material from the same colour.
Buttons, thread and labels stay on today's **one stock** setting.

The owner is at the cutting table on a laptop (Edit material, product page) and in the stockroom on a phone (checking stock,
restocking, Start batch). The question each screen answers, in the order the human asked for them:
1. **How much of this material do I have, in each colour?** (material page and list)
2. **I received a delivery: how do I put several colours in at once?** (Restock)
3. **How do I tell the system a material is kept per colour, and undo that?** (Add and Edit material)
4. **Which product can't be made because a colour of a material is missing, and how do I fix it?** (product page, Start batch,
   Used in)

The worked example everywhere: material **Denim 12oz** (MAT-DEN-012, fabric, in metres, reorder at 100 m) with **Indigo 250 m**
and **Black 40 m**. Cost example, when cost is per colour: Indigo TND 8.500 / m, Black TND 9.200 / m, so stock value Indigo
TND 2,125.000 + Black TND 368.000 = TND 2,493.000. The product is the **Denim Jacket** (Indigo, Black), with **Light wash** added
only where a missing colour has to be shown. Quantities per jacket are INV-16's: M takes 1.98 m with waste, so the same
16 / 112 guaranteed / best figures come out when the jacket's denim line is one line on Denim 12oz instead of two materials.

## Flow
1. **Set it up.** Edit material → "Stock and cost" → *How is stock kept?* → **Stock per colour**. If the material has stock, the form
   asks which colour holds it (the 250 m); then **Add colour** adds Black (starts at 0) and the owner types 40. Optionally *How is
   cost set?* → **Cost per colour**. **Save material** → toast "Material updated." (today's toast).
2. **Read it.** The material page shows **Stock by colour** right under the numbers; the Materials list shows the total with a
   "2 colours" button that opens the colours in place.
3. **Restock.** On a per-colour material, Restock opens a side sheet with one line per colour: type what arrived for Indigo and
   Black, one date, one reference. **Add to stock** → toast "Restocked Denim 12oz: Indigo +100 m, Black +20 m."
4. **See the effect.** The Denim Jacket page says what limits each colour. A product colour the material doesn't have reads
   "Denim 12oz — no Light wash stock" with **Add Light wash to Denim 12oz**; one click, one small dialog, and Light wash exists at 0,
   ready to restock.
5. **Undo it.** Edit material → **One stock for all colours** → a dialog names the sum ("Indigo 250 m + Black 40 m become one stock
   of 290 m") and, when cost was per colour, asks which colour's cost to keep.

## Layout

### 1 · Showing stock per colour

**Building block — the colour line.** Swatch (existing `ColourSwatch`, 16 px) + colour name (existing `ColourTag`), then the quantity
with its unit in the numbers font, then a status word only when it isn't fine: **Low stock** (warning) or **Out of stock** (danger),
the same `StockBadge` words and icons the page already uses. A colour that is fine shows no pill (twenty green pills would drown
the two that matter): on the list tiles nothing, in the page's table the plain muted word "In stock".

**Material page** (`Inv21Stock`, top).
- Header: unchanged (name, SKU, category, badge). The badge is the *material's*: **Low stock** when any colour is at or under the
  reorder level (zero counts), **Out of stock** only when every colour is at 0, otherwise **In stock**. The colours are named in the
  callout and in the card, never only by the badge.
- KPI strip, same four cards, relabelled where a single number would lie:
  - **In stock, all colours** — the total (290 m). Small muted line: "2 colours".
  - **Reorder at** — 100 m, small muted line: "each colour".
  - **Unit cost** — one cost as today; with cost per colour a range, "TND 8.500–9.200 / m", small muted line "per colour".
  - **Stock value** — the total (TND 2,493.000 with cost per colour).
- Callout (existing `Callout`, warning), naming the colours: **"Black is below reorder level."** "40 m on hand, reorder at 100 m."
  Several: **"3 colours are at or below reorder level."** "Black 40 m, Camel 0 m and Rust 12 m. Reorder at 100 m." One at 0:
  **"Camel is out of stock."** "0 m on hand, reorder at 100 m." The callout's tone follows the material (warning); the danger tone
  lives on the colour's own row.
- **Stock by colour** card (new), between the callout and Details. Title "Stock by colour", description "Each colour has its own stock.
  A product takes its material from its own colour." A table (`Table`), colours in Settings › Colours order:

  | Colour | In stock | Unit cost* | Stock value* | Status | |
  | ------ | -------- | ---------- | ------------ | ------ | - |
  | ■ Indigo | 250 m | TND 8.500 / m | TND 2,125.000 | In stock | Restock |
  | ■ Black | 40 m | TND 9.200 / m | TND 368.000 | Low stock | Restock |
  | All colours | 290 m | | TND 2,493.000 | | |

  \*Only when cost is per colour; with the same cost the two columns are left out (the KPI strip carries them). The per-row
  **Restock** button (ghost, named "Restock Black") opens the Restock sheet with that colour's field focused. The last row is the
  total, server-sent.
- With **more than 8 colours** a line sits above the table: "3 of 20 colours need restock" and a switch "Only colours that need
  restock". Colours never reorder (the order is the same everywhere); the switch filters. Under 9 colours neither shows.
- **Details** card: the free-text **Colour** row is left out for a per-colour material (its text is kept, not shown).
- **Used in:** see 4.

**Materials list** (`Inv21Stock`, bottom). Default materials look exactly as today. A per-colour row differs in three cells only:
- **In stock:** the total in bold, and under it a small button "2 colours" with a chevron (`aria-expanded`, named "Show stock by
  colour for Denim 12oz"). It opens the existing inline row panel, a third kind beside Restock and History.
- **Reorder at:** "100 m" with a muted "each colour" under it. **Unit cost:** one cost, or the range with "per colour".
- **Status:** the badge, and under it the colours that need restock in muted text: "Black", "Black and Camel", "Camel, Black and Light
  wash", and from four on the first three and a count ("Camel, Black, Light wash and 2 more"). Out of stock only when every colour
  is 0.
- **The panel** ("Stock by colour · Denim 12oz", **Close**): a grid of colour tiles (min 10 rem wide, 2 to 5 per row): swatch and
  name, the quantity in bold, and the badge only when Low or Out. Footer: "Reorder at 100 m for each colour." Twenty colours is
  four or five rows of tiles, with Low and Out tiles keeping their place. Tiles are not buttons.
- The filter **Needs restock** keeps its meaning: materials where any colour is low or out. The sort **Stock low→high** keeps
  sorting by the shown total.
- At <768 px the row is a card as today: "In stock" shows the total with the "2 colours" button (44 px); the panel opens inside the
  card and lists colours one per line (swatch, name, quantity, badge).

**States.**

| State | What the screen shows |
| --- | --- |
| Default | As above, real numbers |
| One colour only | The card and panel still show: "Stock by colour" with one row; the count button reads "1 colour" |
| Many colours | 20 colours: card table scrolls with the page (20 rows, 44 px each); summary line and switch appear; list panel is a tile grid |
| Everything fine | No callout, no badges on tiles; badge "In stock" |
| Empty | Not reachable: a per-colour material always has at least one colour (see 3). A material set to per colour with every colour at 0 reads "Out of stock" everywhere |
| Loading | The page props are immediate; History stays deferred with its skeleton as today. The list panel has no wait |
| Error | None of its own; a failed Restock or Edit keeps today's toasts and inline errors |
| Read-only | None |
| Long content | 32-character colour names wrap inside their cell (existing `ColourTag` rule); 7-digit quantities stay on one line in the numbers font |

### 2 · Restock

Restock of a **per-colour material** opens a **Sheet** (right side on a laptop, about 34 rem wide; full height on a phone), from the
material page, the list row and a row's **Restock** button. A one-stock material keeps today's inline panel and dialog, unchanged.
Reason for a sheet: a receipt can hold up to 20 colours with a price each; the list or page stays in view behind it (design system:
side work opens in a sheet).

`Inv21Restock`:
- **Title** "Restock Denim 12oz"; under it "In stock now: 290 m in 2 colours · cost per colour" (or "· unit cost TND 8.500" when the
  cost is the same).
- **Date** (today by default, not after today) and **Reference** (optional, 64 characters, placeholder "PO or delivery note"), side
  by side, same fields as today.
- With the **same cost for every colour:** one **Unit price paid** field, as today, above the colour lines.
- Help line: "Type a quantity for each colour you received. Leave the others empty."
- **Colour lines**, one per colour of the material, in Settings order. Each line: swatch + name, muted "Now 40 m" under the name, a
  **Quantity received** field with the unit ("m") and, with cost per colour, a **Unit price paid** field with the currency
  (placeholder: that colour's current cost, "9.2"). On a laptop the column titles sit once above the lines; each field still has an
  accessible name with the colour ("Quantity received, Black"). An empty line is simply not in the receipt.
- **Find colour** (filter) appears above the lines when the material has more than 8 colours; a typed quantity stays when the
  filter hides its line.
- Checkbox, as today: **Use this price as the new unit cost** (same cost) or **Use these prices as the new unit costs** (cost per
  colour; only colours with a quantity change). Checked by default, as today.
- Footer (sticky): "2 colours in this receipt" (count of lines with a quantity) · **Cancel** · **Add to stock** (spinner while
  saving). A one-line note as today: "Logged in the material's history. The new stock is calculated when you save."
- Opened from a colour's **Restock** button: that colour's quantity field has focus and is scrolled into view; nothing else
  differs.

| State | What the screen shows |
| --- | --- |
| Default | Two lines, both empty; focus on the first quantity |
| Filled | "2 colours in this receipt"; Indigo 100 m at TND 8.500, Black 20 m at TND 9.200 |
| Loading | Button spinner, fields stay; the sheet closes on success |
| Error, a line | Under the field: "Indigo: Enter a number above 0, with up to 4 decimals." · "Black: Enter the price paid." · date and reference errors as today. Focus moves to the first error; typed values stay |
| Error, nothing typed | At the top of the lines (announced): "Enter a quantity for at least one colour." |
| Error, data changed | "The colours of Denim 12oz have changed. Enter the quantities again." and the lines are redrawn |
| Disabled | **Add to stock** is never silently grey: it stays pressable and the errors explain |
| Success | Toast "Restocked Denim 12oz: Indigo +100 m, Black +20 m." (today's "Restocked name: +quantity unit." for several colours); the sheet closes, focus returns to the Restock button |
| Long content | 20 colours: the lines scroll inside the sheet between a fixed title and a fixed footer |

Phone: the sheet is full height; each colour is one block (name and "Now 40 m" on the first row; below, **Quantity received** and, with
cost per colour, **Unit price paid** side by side, 44 px high, labels visible above each field); Date and Reference stack; the
footer bar (count, Add to stock) is fixed at the bottom; Cancel is the header ✕ ("Close") and a link above the button.

### 3 · Add and Edit material

`Inv21MaterialForm`. The form keeps its sections (Identity, Stock and cost, Supplier and notes, Photo). Changes sit in **Stock and
cost** and one hint in Identity.

**The two settings, in plain words** (a radio group: two cards, a radio, a title and one line each; **new shadcn component:
radio-group**).
- *How is stock kept?*
  - **One stock for all colours** — "For buttons, thread, labels. One quantity, whatever the colour." (selected on a new material)
  - **Stock per colour** — "For fabric and leather. A quantity for each colour, such as Indigo 250 m and Black 40 m."
- *How is cost set?* — shown **only** when Stock per colour is chosen (hidden, not disabled, otherwise):
  - **Same cost for every colour** — "One unit cost for the whole material." (selected)
  - **Cost per colour** — "Each colour has its own unit cost, such as Indigo TND 8.500 and Black TND 9.200."

**What the section shows for each combination** (always: Unit of measure first; **Reorder at** last):

| Stock | Cost | Fields |
| --- | --- | --- |
| One stock | (hidden) | Unit cost · Quantity in stock — today's form, unchanged |
| Per colour | Same | Unit cost · the **Colours** table: Colour · Quantity in stock |
| Per colour | Per colour | The Colours table: Colour · Quantity in stock · Unit cost (no single Unit cost field) |

**The Colours table** (`Table`; phone: one block per colour). Heading "Colours", help "From Settings › Colours. A product's colour
takes its material from the same colour." Each row: swatch + name, a **Quantity in stock** field (unit as suffix), a **Unit cost**
field when cost is per colour, and a **Remove** button at the end. Below: outline **Add colour** (plus icon).
- **Add colour** opens the same searchable list as Add product's Colours field (Popover + Command, "Search colours", the colours the
  material doesn't have yet, "3 chosen" and **Done**; "No colour named "teal". Missing one? Add it in Settings › Colours." opens in
  a new tab). A colour added is a new row with quantity 0 (and, with cost per colour, an empty cost the owner must fill).
- **Remove** is enabled only for a colour at 0 with no history (a row added a moment ago, or never used). Otherwise it is a
  **disabled button that stays focusable** and says why, on a tooltip and in the row's description read by screen readers:
  "Indigo has 250 m in stock, so it can't be removed." / "Black has history, so it can't be removed." On a phone the reason is
  written under the colour name (no hover). Enabled button name: "Remove Black".
- At least one colour: saving with none shows "Add at least one colour, or choose One stock for all colours." under the table.

**Switching on, material has stock** (Edit Denim 12oz, 250 m). Choosing **Stock per colour** replaces **Quantity in stock** with the
Colours table whose only row is a colour picker ("Choose a colour") holding the 250 m, and an info callout above it: "You have 250
m in stock now. Choose the colour that holds it. Colours you add start at 0." Saving without choosing: "Choose the colour that holds
the 250 m you have now." under the picker. Nothing is saved until **Save material**; **Cancel** discards the switch. Material at
0 stock: no callout, an empty table with **Add colour**.

**Switching cost on** (stock already per colour): no dialog. The cost column appears, every colour pre-filled with the current
unit cost; hint under the table: "Each colour starts at the current cost, TND 8.500 / m. Change the ones that differ."

**Switching cost off** (stock stays per colour) — a dialog opens on the choice, as INV-16's switch-back does:
title **"Use the same cost for every colour?"**, body "Choose the cost to keep. Every colour will use it, and the stock value
changes with it." then a radio list with the saved costs ("Indigo · TND 8.500 / m", "Black · TND 9.200 / m", none chosen) ·
**Keep cost per colour** · **Use this cost for all colours** (disabled until one is chosen; the line "Choose the cost to keep." is
on screen, not only in a tooltip).

**Switching stock off** — a dialog opens on the choice: title **"Switch to one stock for all colours?"**, body "**Indigo 250 m +
Black 40 m become one stock of 290 m.** The history of each colour stays." With cost per colour the same window adds "Choose the
cost to keep for every colour:" and the radio list above, and "The stock value changes with the cost you keep." · **Keep stock per
colour** (closes and puts the radio back) · **Switch to one stock** (disabled until a cost is chosen when relevant). Esc is the
same as **Keep stock per colour**. The sum is the server's: the window shows a skeleton line while it is worked out and, if that
fails, "Couldn't work out the total." with **Try again**; the buttons stay disabled meanwhile. After confirming, the form shows the
single **Quantity in stock** (290 m) and **Unit cost** fields; nothing is saved until **Save material**.

**Identity › Colour (free text).** Hidden while Stock per colour is chosen, its text kept for when the owner switches back. For a
one-stock material a hint is added: "A description, such as Antique brass. To keep stock for each colour from your colour list,
choose Stock per colour below." (This is wording, not a look-alike hint, and can be dropped.)

**Add material** (new): same form; defaults One stock; choosing Stock per colour shows an empty Colours table ("No colours yet.
Add the colours you buy this material in.") with **Add colour**.

**Reorder at:** one field, hint "Applies to each colour. The material shows Low stock when any colour is at or under this level."
(one-stock materials keep today's "Shown as Low stock at or below this level.").

| State | What the screen shows |
| --- | --- |
| Default | As above |
| Empty table | "No colours yet. Add the colours you buy this material in." + **Add colour** |
| Loading | **Save material** keeps its label and shows a spinner; the dialogs' sum shows a skeleton |
| Error | Inline under the field with the colour first: "Black: Enter a number from 0, with up to 4 decimals." · "Black: Enter a unit cost." · "Add at least one colour, or choose One stock for all colours." · "Choose the colour that holds the 250 m you have now." Focus goes to the first error; typed values stay |
| Disabled | Remove with its reason (above); dialog buttons with "Choose the cost to keep." |
| Read-only | None |
| Confirm | The two dialogs above and the cost dialog; buttons are verbs |
| Success | Toast "Material updated." / "Material added." as today |
| Long content | 20 colour rows scroll with the page; the Add colour list has search |

Phone: one column; the two settings stack as full-width cards (44 px minimum height); each colour is a block (name, ✕ Remove 44 px,
quantity, cost), **Add colour** is a full-width 44 px button; **Save material** stays in the sticky bottom bar; dialogs are full
width.

### 4 · The places that read it

`Inv21Readers`.

**Product page · Composition (read-only table).** A line whose material is per colour shows, in the *Stock on hand* cell, the
product's own colours with their stock: "■ Indigo 250 m · ■ Black 40 m" (up to 3 colours; more: "5 colours" with a popover
listing them). *Units possible* reads **Varies** with **View by colour** (popover: "Indigo 126 units · Black 20 units"), the
same word and pattern as INV-16's "Varies / View grid". The quantity stays one number when the line is "Same for every variant".
A product colour the material doesn't have shows "■ Light wash — no stock" in the warning tone, in words, with the link
**Add Light wash to Denim 12oz**.

**Product page · bottleneck and Capacity by colour** (INV-16 wording, adapted; see the end for the list):
- Missing colour: **"Bottleneck: Denim 12oz — no Light wash stock."** "Denim 12oz has no Light wash yet, so no Light wash jacket can
  be made." Best-case line unchanged ("Best case: 112 units, in Indigo — limited there by Metal shank button 17mm."), link **Add
  Light wash to Denim 12oz**. "Can produce now" reads 0; that is INV-16's guaranteed 0 with best above 0.
- *Capacity by colour*, Light wash row: Guaranteed 0 · Best 0 · Limited by "Denim 12oz — no Light wash stock" and the same link.
- A normal per-colour bottleneck reads **"Bottleneck: Denim 12oz, Black stock (size XL)."** (INV-16's "Bottleneck: Denim 12oz Black
  (Black, size XL)." assumed a material per colour.) Body: "40 m of Black in stock covers 16 units of Black, size XL — the
  smallest across every colour and size."
- **Add Light wash to Denim 12oz** opens a small dialog (not a page change): title **"Add Light wash to Denim 12oz?"**, body "Light
  wash starts at 0 m, so no Light wash jacket can be made until you restock it." With cost per colour it adds a required field
  **Unit cost of Light wash** (per m). **Cancel** · **Add Light wash**. Toast "Light wash added to Denim 12oz." with an action
  button **Restock Light wash** that opens the Restock sheet with that colour focused. Same dialog everywhere the link appears,
  stacked over Start batch when it is used there; its focus returns to the link, and the page behind refreshes with what was typed
  kept.

**Start batch window.** The materials table gets one row per material **and colour** the batch uses: "Denim 12oz · Indigo" needs
33.88 m, in stock 250 m; "Denim 12oz · Black" needs 8.8 m, in stock 40 m; one-stock materials keep one row. A colour the
material doesn't have: the row reads needs 8.8 m, in stock "No stock", short by 8.8 m, and carries **Add Light wash to Denim 12oz**.
INV-16's shortfall sentence names material and colour: **"Short on Denim 12oz in Black. Restock it first, or lower the units."**
Two: "Short on Denim 12oz in Black and Metal shank button 17mm. Restock them first, or lower the units." Same material, two colours:
"Short on Denim 12oz in Black and Light wash." One colour and size keeps "You can cut at most N now: Denim 12oz in Black runs short."

**Material page · Used in** ("What you can make with it"). Each product row keeps its columns; under **This stock covers** the
range shows ("20–126 units") with a small **By colour** button (`aria-expanded`) that opens a line under the row: "■ Indigo 126
units · ■ Black 20 units · ■ Light wash no stock" (the last with **Add Light wash to Denim 12oz**). *Limited by* reads "This
material (bottleneck), Black stock" when it is the limit. The intro sentence for a per-colour material: "Here's how far your stock
goes, colour by colour, and how many units you can make right now with all their materials." The Suggestion callout names the
colour: "this material limits Denim Jacket to 16 units, in Black. If you restock Black, the next limit is … at …" (new
server value, see below).

**Other screens that need a wording change (not designed here).**
- **Dashboard › Needs restock** (card and key figure): the figure still counts materials; each row names its colours: "Denim 12oz ·
  Black 40 m, Camel 0 m". **Inventory value** is the sum, with per-colour cost where set.
- **Add and Edit product › Composition**: the material search shows "290 m in 2 colours" for a per-colour material; when the
  product has a colour the chosen material lacks, an info note under the line: "Denim 12oz has no Light wash stock yet. Add Light
  wash".
- **History** (card and panel): an entry of a per-colour material names its colour, with its swatch ("Restock · Indigo +100 m");
  the entries of one receipt (same date and reference) sit together.
- **Undo batch / Start batch toasts and history**: "Back from cutting" names the colour taken from or returned to.
- **Settings › Colours**: *Used by* may read "3 products and 2 materials".
- **Materials list sort and filter**, **Products list**: no wording change.

## Accessibility
- **Colour is never alone** (1.4.1): every swatch has the colour's name beside it; every status has its word and icon
  (existing badges); the "no stock" state is words plus the warning icon.
- **Contrast** (1.4.3, 1.4.11): only existing tones (warning `#FDF1DC` / `#6E3B00`, danger, success, info, muted `#5E5E66` on white
  at about 6:1) and the existing swatch edge (3:1 against the card). No new colour.
- **Targets**: 44 px on the phone for Remove, Add colour, the "2 colours" button, By colour, Restock buttons and every field; 40 px
  fields on a laptop; 8 px between neighbours.
- **Names** (1.3.1, 2.4.4, 4.1.2): every quantity and cost field in a table is named with its colour ("Quantity in stock, Black");
  buttons name the material and colour ("Restock Black", "Remove Black", "Show stock by colour for Denim 12oz"); tables have header
  cells, the colour is a row header.
- **Disabled with a reason** (never silent): Remove is `aria-disabled` and keeps focus; its reason is read as its description and
  shown on a tooltip (keyboard-reachable) and, on a phone, as text.
- **Keyboard** (2.1.1, 2.4.3, 2.4.7, 2.4.11): sheet and dialogs trap focus, Esc closes, focus returns to the opening button; the
  "2 colours" and "By colour" buttons are real buttons with `aria-expanded`; the radio groups use arrow keys; the Add colour list is
  the same Command pattern as INV-1's.
- **Errors** (3.3.1, 3.3.3): next to the field, colour named first, announced; focus moves to the first error.
- **Reflow** (1.4.10): the Restock sheet, the Colours table and the tiles reflow to one column at 320 px; nothing scrolls sideways.
- **Redundant entry** (3.3.7): the colour the stock goes to is picked once; the cost to keep is asked once and never retyped.
- **Motion** (2.3.3): none beyond the shadcn defaults.

## Options and recommendation
**A · Colours on the list.** (a) **Recommended**: the total plus a "2 colours" button that opens a tile panel in the row — the list
stays as quiet and as tall as today, and the colours are one click away. (b) Show up to three colours inline in the cell, then "and
17 more": numbers at a glance for a 2 to 3 colour material, but rows grow, and "which three" has to be decided. (c) A separate
Colours column of chips: wide, and unreadable past four colours.

**B · Restock lines.** (a) **Recommended**: every colour of the material is a line, empty lines are ignored, Find colour past 8 —
no picking step for the usual 2 to 4 colours, and nothing hidden. (b) Start with no lines and add colours from a picker: matches
"pick which colours are in the receipt" literally, but costs two clicks for the common case and one more control to learn.

**C · Where Restock opens for per-colour materials.** (a) **Recommended**: a sheet (room for up to 20 lines and a price per
colour; keeps the page in view). (b) The existing inline panel and dialog: no new container, but 20 lines with prices don't fit
either, and a dialog hides the stock being replenished.

**D · The switch-back confirmation.** (a) **Recommended**: a dialog on the choice, with the sum from the server (shows what was typed,
reads like INV-16's switch-back). (b) At Save: a surprise at the last click, and the form would show a quantity that isn't yet
known.

**E · "Add Light wash to this material".** (a) **Recommended**: a small dialog that adds the colour at 0 on the spot, with a
Restock action in the toast. (b) A link to Edit material with the picker open: more steps, and a stacked page change from Start
batch.

**F · Colour order and attention.** (a) **Recommended**: fixed Settings order, a summary line and a filter past 8 colours. (b)
Problem colours first: attention-first, but the order changes from day to day and from screen to screen.

## For the product-owner
Proposed acceptance-criteria wording (real numbers from the example):
- **AC · The setting.** Given Edit material, then "How is stock kept?" offers "One stock for all colours" and "Stock per colour";
  choosing the second shows "How is cost set?" with "Same cost for every colour" and "Cost per colour"; with the first, the cost
  setting isn't shown.
- **AC · Switching on.** Given Denim 12oz with 250 m, when I choose "Stock per colour", then I must choose the colour that holds
  the 250 m ("Choose the colour that holds the 250 m you have now."); other colours I add start at 0.
- **AC · Stock by colour.** Given Denim 12oz (Indigo 250 m, Black 40 m, reorder at 100 m), then its page shows "Stock by colour" with
  "Indigo 250 m · In stock" and "Black 40 m · Low stock", and "Black is below reorder level."; the Materials list shows "290 m"
  with a "2 colours" button that lists both.
- **AC · Switching back.** When I choose "One stock for all colours", then I read "Indigo 250 m + Black 40 m become one stock of
  290 m." and confirm with "Switch to one stock"; with cost per colour I must also choose which colour's cost to keep.
- **AC · Removing a colour.** A colour at 0 with no history has "Remove Black"; any other colour's Remove is disabled and says "Indigo
  has 250 m in stock, so it can't be removed." or "Black has history, so it can't be removed."
- **AC · Restock per colour.** Given Denim 12oz, when I type Indigo 100 and Black 20 and press "Add to stock", then I read
  "Restocked Denim 12oz: Indigo +100 m, Black +20 m." and the stock is 350 m and 60 m; with cost per colour each colour has its own
  "Unit price paid".
- **AC · A colour the material lacks.** Given the Denim Jacket made in Light wash and Denim 12oz without it, then its page reads
  "Bottleneck: Denim 12oz — no Light wash stock." with "Add Light wash to Denim 12oz"; pressing it and "Add Light wash" adds the colour at 0 and
  shows "Light wash added to Denim 12oz."
- **AC · Start batch.** Given a batch of Indigo and Black jackets with Black short, then it reads "Short on Denim 12oz in Black. Restock
  it first, or lower the units."
- **AC · Phone.** At 375 px the colours, the Restock sheet and the form read in one column, with no sideways scroll.

Questions are at the end of this file. **INV-16's UI card and the Start batch window to reword because of this:**
1. Bottleneck sentence and "Limited by" cell: material per colour is no longer "Denim 12oz Black"; see "4 · The places that read it".
2. Start batch: "Short on Denim 12oz Black." becomes "Short on Denim 12oz in Black."; the at-most-N message names "Denim 12oz in
   Black"; the materials table has a row per material and colour.
3. The INV-16 grid sheet for a per-colour material gets one muted line: "Each colour takes its stock from the same colour of Denim
   12oz." The composition line shows "Stock per colour" under the material name. Today's "only for Indigo" cells keep working for one-stock materials.
4. INV-16's worked example (two materials, Denim 12oz Indigo and Black) is now one material with two colours; the numbers
   (16 / 112, Indigo 103 / 112, Black 16 / 22) are the same when the quantities are.
5. Composition table "Varies": a line "Same for every variant" on a per-colour material now varies by colour too ("View by colour").

## For the tech-lead
- **New values the server sends** (the screens compute nothing): per colour of a material — id, name, swatch, quantity, unit cost,
  stock value, status, can-remove and why not ("has stock" / "has history"); the material's stock mode and cost mode, the total
  quantity, the cost range (min, max) and stock value; the colours at or under the reorder level (names, in list order); the sum and
  the choice list for the switch-off dialogs (a small preview request with what the form holds); for a product's line on a
  per-colour material — the stock of each of the product's colours, the colours the material lacks, and "units possible" per colour;
  for Start batch — a materials row per material and colour, and a colour-aware shortfall sentence; next limit per colour for the
  Suggestion.
- **New request:** add a colour to a material at 0 (and, with cost per colour, a cost) — used by the small dialog.
- **New shadcn component:** radio-group (the two settings and the cost-to-keep list). Everything else exists: sheet, dialog,
  alert-dialog, popover + command, table, tooltip, toggle-group.
- **Possibly costly:** the history list now has a colour on every entry and groups a multi-colour receipt; the inline row panel
  gets a third kind; the Restock request carries several colours in one receipt.
- No new number, formula or business rule is introduced here. The material status rule (Low when any colour is at or under the level;
  Out only when every colour is 0) is a reading of decision 13 that the product-owner should confirm.

## Questions for the product-owner / human
1. **Material badge and counts.** Low when any colour is at or under the level (decided); Out only when **every** colour is at 0, and
   the dashboard's "Needs restock" counts **materials**, not colours. (a) yes, as written — **recommended** · (b) "Out of stock" as soon
   as any colour is 0, and count colours · default if unanswered: (a).
2. **At least one colour.** A per-colour material must have at least one colour to be saved. (a) yes — **recommended**, it also
   means a per-colour material never has an empty stock card · (b) allow none (an empty state is then needed) · default: (a).
3. **Correcting a colour's quantity on Edit material**, as the single quantity can be corrected today (logged as a correction per
   colour). (a) yes — **recommended**, parity · (b) only by Restock · default: (a).
4. **New colour on a cost-per-colour material.** (a) its unit cost is required when the colour is added — **recommended**; no
   silent price · (b) it starts at the first colour's cost · default: (a).
5. **Free-text Colour on a per-colour material.** (a) hidden, text kept — **recommended** · (b) shown, renamed "Description" ·
   default: (a). And the hint under it on one-stock materials (keep, or drop).
6. **History of a multi-colour receipt.** (a) one entry per colour, grouped under the receipt's date and reference —
   **recommended** · (b) separate entries, ungrouped · default: (a).
7. **Restock container.** (a) a sheet for per-colour materials, today's panel and dialog for one-stock ones — **recommended** · (b) the
   sheet for every material · default: (a).
8. **Settings › Colours "Used by".** (a) "3 products and 2 materials" — **recommended** (a colour on a material with stock matters) ·
   (b) leave products only · default: (a).
