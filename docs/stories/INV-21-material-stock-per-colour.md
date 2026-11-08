---
id: INV-21
title: Material stock per colour
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
created: 2026-11-08
tags:
  - story
  - variants
---

# INV-21 · Material stock per colour

> [!abstract] Description
> As a **workshop owner**, I want **a material such as fabric to keep its stock, and if I choose its unit cost, per colour**, so that
> **Denim 12oz is one material with Indigo 250 m and Black 40 m, and "Can produce now", Start batch, Restock and the dashboard use
> the stock of each product colour's own colour**. Each material is either **One stock for all colours** (today, unchanged; buttons,
> thread, labels) or **Stock per colour**. A per-colour material can also be set to **Cost per colour**. A product's colour is matched
> to the material's colour by the colour itself, from Settings › Colours.
> New: the two settings and the Colours table on Add and Edit material; "Stock by colour" on the material page; "2 colours" on the
> Materials list; a Restock sheet with a quantity per colour; the windows that confirm a switch; the "no stock for this colour"
> state with its **Add Light wash to Denim 12oz** window. Changed: the product page, Start batch and Undo batch, a material's "Used
> in" table, the material's history, the dashboard's "Needs restock" and the Inventory value. The pages are drawn in
> [[INV-21-material-stock-per-colour-ui]].
> Left out on purpose: merging materials such as "Denim 12oz Indigo" and "Denim 12oz Black" (the owner fixes those by hand), a hint
> that two materials look alike, a reorder level per colour, and any change to Settings › Colours. Doesn't change the colour × size
> grid of INV-16: a line still counts by colour and size as drawn there; only where its stock and cost are read changes.

> [!info] How it works today
> A material has one quantity in stock and one unit cost. Fabric bought by colour is two materials, "Denim 12oz Indigo" (250 m) and
> "Denim 12oz Black" (40 m), and the Denim Jacket's composition lists both, each line only for its own colour. The material's
> **Colour** field is free text ("Antique brass"), not a colour of the list. Restock adds one quantity, at one price, to one stock:
> "Restocked Denim 12oz Indigo: +100 m." The product page says "Bottleneck: Denim 12oz Black (Black, size XL)." and Start batch says
> "Short on Denim 12oz Black." Nothing tells the system that Indigo denim and Black denim are the same fabric.

## Acceptance criteria

**Denim 12oz** (fabric, metres, reorder at 100 m): **Indigo 250 m** and **Black 40 m**, **Stock per colour**. With **Cost per colour**:
Indigo TND 8.500 per m, Black TND 9.200 per m, so the stock value is Indigo TND 2,125.000 + Black TND 368.000 = **TND 2,493.000**.
The **Denim Jacket** (colours Indigo and Black, sizes S · M · L · XL; labor TND 12.000, overhead TND 3.500, selling price TND 89.000)
has one denim line on Denim 12oz, **By colour and size**, with cells under both colours: S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000 m,
waste 10 %. Metal shank button 17mm 8 pc (stock 900), Polyester thread 120 m at 5 % waste (stock 20,000) and Woven brand label 1 pc
(stock 400) are **One stock for all colours** and **Same for every variant**, as today. Can produce, per variant: Indigo S 112 · M 112
· L 112 · XL 103; Black S 22 · M 20 · L 18 · XL 16 (INV-16's figures, the same when the quantities are). The wording is that of
[[INV-21-material-stock-per-colour-ui]].

- [ ] **AC-1 · The two settings.** On Add and Edit material, under Stock and cost, **How is stock kept?** offers **One stock for all
  colours** ("For buttons, thread, labels. One quantity, whatever the colour.", chosen on a new material) and **Stock per colour**
  ("For fabric and leather. A quantity for each colour, such as Indigo 250 m and Black 40 m.").
  - With Stock per colour chosen, **How is cost set?** appears with **Same cost for every colour** ("One unit cost for the whole
    material.", chosen) and **Cost per colour** ("Each colour has its own unit cost, such as Indigo TND 8.500 and Black TND 9.200.").
    With One stock chosen it isn't shown at all.
  - One stock shows today's **Unit cost** and **Quantity in stock** fields. Stock per colour with the same cost shows **Unit cost**
    and the **Colours** table (Colour, Quantity in stock). Cost per colour shows the table with a **Unit cost** column and no single
    Unit cost field.
  - **Colour** (free text) in Identity is hidden while Stock per colour is chosen, and its text is kept for when the owner
    switches back. A one-stock material shows it as today.
  - **Reorder at** is one field; for a per-colour material its hint reads "Applies to each colour. The material shows Low stock when
    any colour is at or under this level."
- [ ] **AC-2 · Colours of a material.** The table is headed "Colours" with "From Settings › Colours. A product's colour takes its
  material from the same colour."; each row is the colour's swatch and name, **Quantity in stock** (with the unit) and, with cost
  per colour, **Unit cost**, and a **Remove** button. **Add colour** opens the searchable list of Add product's Colours field,
  offering only colours the material doesn't have yet; a colour added is a new row at 0.
  - On a cost-per-colour material the new colour's **Unit cost** is empty and required: "Black: Enter a unit cost."
  - A new per-colour material shows "No colours yet. Add the colours you buy this material in." with **Add colour**. Saving a
    per-colour material with no colour reads "Add at least one colour, or choose One stock for all colours." under the table.
  - A quantity that isn't a number from 0, with up to 4 decimals, reads "Black: Enter a number from 0, with up to 4 decimals."
    under its field; focus goes to the first error and the typed values stay.
  - Saving shows "Material added." or "Material updated.", as today.
- [ ] **AC-3 · Switching to Stock per colour with stock.** On Edit Denim 12oz with 250 m in one stock, choosing **Stock per colour**
  replaces **Quantity in stock** with the Colours table holding one row, a picker "Choose a colour", and the note "You have 250 m in
  stock now. Choose the colour that holds it. Colours you add start at 0." Choosing Indigo and adding Black at 40 saves Indigo 250 m
  and Black 40 m. Saving without a colour chosen reads "Choose the colour that holds the 250 m you have now." A material at 0 shows no
  note and an empty table. Nothing is saved until **Save material**; **Cancel** discards the switch.
- [ ] **AC-4 · Stock by colour on the material page.** Denim 12oz's page shows **In stock, all colours** 290 m ("2 colours"), **Reorder
  at** 100 m ("each colour"), and the card **Stock by colour** ("Each colour has its own stock. A product takes its material from its
  own colour.") with Indigo 250 m "In stock" and Black 40 m "Low stock", then "All colours 290 m", colours in the order of Settings ›
  Colours. With cost per colour the card adds **Unit cost** (TND 8.500 / m, TND 9.200 / m) and **Stock value** (TND 2,125.000, TND
  368.000, total TND 2,493.000), and the figures show **Unit cost** "TND 8.500–9.200 / m" ("per colour") and **Stock value** TND
  2,493.000. Details leaves out the free-text Colour. With more than 8 colours "3 of 20 colours need restock" and the switch **Only
  colours that need restock** appear; the switch filters and never reorders.
- [ ] **AC-5 · Status of a per-colour material.** The material is **Low stock** when any colour is at or under the reorder level (0
  counts), **Out of stock** only when every colour is at 0, otherwise **In stock**. The callout names the colours: "Black is below
  reorder level." with "40 m on hand, reorder at 100 m."; "3 colours are at or below reorder level." with "Black 40 m, Camel 0 m
  and Rust 12 m. Reorder at 100 m."; "Camel is out of stock." with "0 m on hand, reorder at 100 m." A colour that is fine shows no
  badge on the list tiles.
- [ ] **AC-6 · Materials list.** Denim 12oz's row shows **290 m** in bold with the button **2 colours** ("1 colour" for one), named
  "Show stock by colour for Denim 12oz"; pressing it opens the panel "Stock by colour · Denim 12oz" (a tile per colour: swatch and
  name, quantity, a badge only when Low or Out; footer "Reorder at 100 m for each colour."; **Close**). **Reorder at** reads "100 m"
  with "each colour"; **Status** shows the badge and, under it, "Black"; with four or more, the first three and a count ("Camel,
  Black, Light wash and 2 more"). The filter **Needs restock** includes a material where any colour is low or out; **Stock
  low→high** sorts by the total. A one-stock material's row is as today.
- [ ] **AC-7 · Restock per colour.** **Restock** on Denim 12oz (from its page, the list, or a colour's **Restock Black** button)
  opens a side sheet "Restock Denim 12oz" with "In stock now: 290 m in 2 colours · cost per colour", **Date**, **Reference**, and one
  line per colour (**Quantity received**, and with cost per colour **Unit price paid**, showing "Now 40 m"). Typing Indigo 100 and
  Black 20 and pressing **Add to stock** shows **"Restocked Denim 12oz: Indigo +100 m, Black +20 m."**, makes Indigo 350 m and Black
  60 m, and closes the sheet; one colour reads "Restocked Denim 12oz: Indigo +100 m."
  - Opened from "Restock Black", that colour's quantity field has focus. Lines left empty are not in the receipt; the footer reads
    "2 colours in this receipt". With more than 8 colours **Find colour** appears and a typed quantity stays when it hides its line.
  - With the same cost, one **Unit price paid** sits above the lines. With cost per colour each colour has its own.
  - The checkbox stays checked by default: **Use this price as the new unit cost**, or with cost per colour **Use these prices as
    the new unit costs**, which changes only colours that received a quantity: Indigo at TND 8.800 and Black at TND 9.200 make
    Indigo TND 8.800 and leave Black at TND 9.200.
  - Errors: "Enter a quantity for at least one colour." · "Indigo: Enter a number above 0, with up to 4 decimals." · "Black: Enter
    the price paid." · "The colours of Denim 12oz have changed. Enter the quantities again."; nothing is saved and typed values stay.
  - A one-stock material keeps today's Restock, unchanged.
- [ ] **AC-8 · Removing and correcting a colour.** **Remove Black** works only for a colour at 0 with no history. Any other colour's
  Remove is greyed but stays reachable by keyboard and says why: "Indigo has 250 m in stock, so it can't be removed." or "Black has
  history, so it can't be removed." (on a phone, written under the colour's name). A colour's quantity can be corrected on Edit
  material; it is logged as a correction for that colour.
- [ ] **AC-9 · Cost per colour on and off.** Choosing **Cost per colour** on Denim 12oz at TND 8.500 shows the cost column with every
  colour at 8.500 and "Each colour starts at the current cost, TND 8.500 / m. Change the ones that differ." with no window. Choosing
  **Same cost for every colour** with Indigo 8.500 and Black 9.200 opens **"Use the same cost for every colour?"** ("Choose the cost
  to keep. Every colour will use it, and the stock value changes with it.") with the list "Indigo · TND 8.500 / m", "Black · TND 9.200
  / m", none chosen; **Use this cost for all colours** works only once one is chosen ("Choose the cost to keep." stays on screen);
  **Keep cost per colour** closes the window and puts the choice back. Keeping Indigo's makes both TND 8.500 and the stock value TND
  2,465.000. A change of cost is logged as a price change for the colour.
- [ ] **AC-10 · Switching back to One stock.** Choosing **One stock for all colours** on Denim 12oz opens **"Switch to one stock for
  all colours?"** with "**Indigo 250 m + Black 40 m become one stock of 290 m.** The history of each colour stays." With cost per
  colour it adds "Choose the cost to keep for every colour:" with the list above and "The stock value changes with the cost you
  keep."; **Switch to one stock** works only once a cost is chosen. **Keep stock per colour** (or Esc) closes it and puts the choice
  back. While the total is worked out a line shows a placeholder; if that fails it reads "Couldn't work out the total." with **Try
  again** and both buttons stay off. After confirming, the form shows one **Quantity in stock** (290 m) and **Unit cost**; nothing
  is saved until **Save material**, and then Denim 12oz is one stock of 290 m.
- [ ] **AC-11 · Can produce takes the stock of each colour.** The Denim Jacket's composition table shows, for the denim line,
  **Stock on hand** "Indigo 250 m · Black 40 m" and **Units possible** "Varies" with **View by colour** ("Indigo 126 units · Black
  20 units"); Indigo M is limited by Indigo stock and Black M by Black stock. A line "Same for every variant" of 1.8 m (1.98 m with
  waste) on Denim 12oz gives the same 126 and 20, each variant taking from its own colour's stock. Raising Black to 250 m changes
  Black S from 22 to 112 (then limited by the button, Black stock covering 142). A one-stock material is read as today.
- [ ] **AC-12 · A colour the material doesn't have.** With Light wash added to the Denim Jacket and Denim 12oz without Light wash,
  the composition shows "Light wash — no stock" with the link **Add Light wash to Denim 12oz**, the bottleneck reads **"Bottleneck:
  Denim 12oz — no Light wash stock."** with "Denim 12oz has no Light wash yet, so no Light wash jacket can be made.", "Can produce
  now" reads 0, and the "Best case" line is unchanged. In Capacity by colour the Light wash row reads Guaranteed 0, Best 0, Limited by
  "Denim 12oz — no Light wash stock". A normal limit reads **"Bottleneck: Denim 12oz, Black stock (size XL)."** with "40 m of Black in
  stock covers 16 units of Black, size XL — the smallest across every colour and size."
- [ ] **AC-13 · Add a colour from the product.** **Add Light wash to Denim 12oz** opens a window (the page stays behind): "Add Light
  wash to Denim 12oz?" and "Light wash starts at 0 m, so no Light wash jacket can be made until you restock it." With cost per
  colour it adds the required field **Unit cost of Light wash**. **Add Light wash** shows **"Light wash added to Denim 12oz."** with
  the button **Restock Light wash**, which opens the Restock sheet on that colour; **Cancel** adds nothing. The same window opens
  from Start batch and from "Used in", and focus returns to the link.
- [ ] **AC-14 · Start batch and Undo batch per colour.** A batch of Indigo M 6, Indigo L 10 and Black S 5 takes **33.88 m** from
  Indigo and **8.8 m** from Black, so Denim 12oz is Indigo 216.12 m, Black 31.2 m; the materials table shows one row per material and
  colour ("Denim 12oz · Indigo" needs 33.88 m, in stock 250 m; "Denim 12oz · Black" needs 8.8 m, in stock 40 m), one row for each
  one-stock material. Undoing the Indigo L batch gives back 22 m to Indigo and nothing to Black. A mixed batch with Black S 30 reads
  **"Short on Denim 12oz in Black. Restock it first, or lower the units."**; with the button also short, "Short on Denim 12oz in Black
  and Metal shank button 17mm. Restock them first, or lower the units."; Black and Light wash short, "Short on Denim 12oz in Black
  and Light wash."; one colour and size keeps "You can cut at most N now: Denim 12oz in Black runs short." A missing colour's row
  reads "No stock", short by 8.8 m, with **Add Light wash to Denim 12oz**. Batches started before INV-21 give back what they took.
- [ ] **AC-15 · "Used in".** With the Denim Jacket's denim line **Same for every variant** at 1.8 m, on Denim 12oz's page the Denim Jacket row shows **This stock covers** "20–126 units" with the button
  **By colour** opening "Indigo 126 units · Black 20 units · Light wash no stock" (the last with the Add link); **Limited by** reads
  "This material (bottleneck), Black stock" when it is the limit; the intro reads "Here's how far your stock goes, colour by colour,
  and how many units you can make right now with all their materials."; the suggestion names the colour: "this material limits
  Denim Jacket to 16 units, in Black. If you restock Black, the next limit is …".
- [ ] **AC-16 · Cost, margin and value follow the colour.** With Denim 12oz at Indigo TND 8.500 and Black TND 9.200, the Denim Jacket in
  Black M costs TND 37.140 (Indigo M TND 35.754), margin TND 51.860 · 58.27 %, and the product's unit cost reads TND 33.884–41.188.
  The **Inventory value** adds Denim 12oz as TND 2,493.000. The dashboard's **Needs restock** still counts materials, and its row
  for Denim 12oz reads "Denim 12oz · Black 40 m".
- [ ] **AC-17 · History names the colour.** Each entry of a per-colour material names its colour with its swatch ("Restock · Indigo
  +100 m", "Correction · Black", "Back from cutting · Indigo"); the entries of one receipt sit together under its date and
  reference; the sum of a colour's entries is its stock.
- [ ] **AC-18 · Nothing else changes.** Every material that exists today stays **One stock for all colours**, and "Denim 12oz
  Indigo" and "Denim 12oz Black" stay two materials, untouched and not merged, with every product, cost, "Can produce now" and
  Start batch figure as before. A line "only for Indigo" (cells under Indigo only) keeps working for them.
- [ ] **AC-19 · Phone.** At 375 px the material page's Stock by colour (a block per colour with a 44 px **Restock** button), the list
  row (the **2 colours** button, 44 px, and the panel inside the card with a colour per line), the Restock sheet (full height, a
  block per colour, the footer with **Add to stock** fixed at the bottom), the form (settings as full-width cards, a block per
  colour, a full-width **Add colour**, **Save material** in the bottom bar) and the windows read in one column with no sideways
  scroll; every field and button is 44 px high. Colour is never shown by its swatch alone, and the screens work in dark mode and
  by keyboard.
