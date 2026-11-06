---
id: INV-16
title: Material use per colour and size
type: ui
priority: Must
status: Approved
created: 2026-11-06
tags:
  - ui-card
---

# INV-16 · Material use per colour and size — UI card

> [!abstract] Description
> A composition line can now cost more material for some colours or sizes — a bigger denim panel in XL, or a colour's own stocked
> fabric ("Denim 12oz Indigo" vs "Denim 12oz Black") — instead of forcing every colour and size to share one quantity. Each line
> chooses **Same for every variant** (today, unchanged) or **By colour and size** (a quantity grid, one cell per colour and size).
> The product page then shows one honest headline — **guaranteed**, the number any mix of colours and sizes can reach — next to
> **best**, what one colour on its own could reach, plus a new table of capacity by colour. Cost and margin become a range when they
> vary. Start batch warns by material name, not by a single number, when a mixed batch runs short.
> New: the quantity-mode choice and grid on a composition line; the product page's guaranteed/best wording and "Capacity by colour"
> table; cost and margin shown as a range; the mixed-batch shortfall message; a new category-lock reason.
> Left out on purpose: a per-line cost range in the read-only composition table (it shows "Varies" with a link to the grid instead);
> an inline grid for products with few colours (the grid always opens in a Sheet); any change to Start batch's "at most N" message
> when the batch isn't mixed across variants; a multiplier per size and a stock split by colour (both already ruled out by the
> human, ADR-0027).

> [!info] How it looks today
> A composition line has one quantity field, used by every colour and every size of the product: the Denim Jacket's denim line reads
> "1.8000 m" whether you mean an S or an XL, Black or Indigo. The product page shows one "Can produce now" (112), one bottleneck
> ("Metal shank button 17mm"), one unit cost ("TND 35.754") and one margin. Start batch's shortfall message always names one number:
> "You can start at most N now: Metal shank button 17mm runs short." There is no way to say an XL jacket needs more denim, or that
> Black denim is its own stock from Indigo denim.

## Pages

Worked example used throughout (ADR-0027): the **Denim Jacket**, colours Indigo and Black, sizes S · M · L · XL, labor TND 12.0000,
overhead TND 3.5000, selling price TND 89.0000. Its denim line is **By colour and size**: Indigo denim (stock 250.0000 m) costs
1.6000 m (S) · 1.8000 m (M) · 2.0000 m (L) · 2.2000 m (XL); Black denim (stock 40.0000 m) the same four quantities. Button, thread
and label stay **Same for every variant**, as today.

### Composition editor — the quantity-mode choice
Board: `Inv16BomEditor` (desktop)
1. Each line keeps today's row — material, waste %, placement notes, remove — and gains a quantity-mode control under it: two
   buttons, **Same for every variant** (selected by default) and **By colour and size**.
2. **Same for every variant** shows today's quantity field and unit beside the control, unchanged.
3. **By colour and size** replaces the field with a summary — "4 of 44 cells · Indigo only" (or "No cells set yet" for a line with
   none) — and an **Edit grid** button.
4. Switching a line with a typed quantity to **By colour and size** opens its grid at once, with every cell pre-filled with that
   quantity as a starting point, ready to edit or clear.
5. Switching a filled **By colour and size** line back to **Same for every variant** asks first: "Switch to one quantity for every
   variant? This clears the 4 quantities you set for Denim 12oz Indigo across its colours and sizes." · **Keep the grid** · **Switch
   anyway**.

### The quantity grid (opens in a Sheet)
Board: `Inv16QuantityGrid` (desktop)
1. The Sheet is titled with the material's name ("Denim 12oz Indigo") and opened by **Edit grid**; the composition list stays
   visible behind it.
2. Help text: "Quantity by colour and size, in m. A blank cell means that colour and size doesn't use this material."
3. **Find colour** — a text filter over the product's own colours.
4. "2 of 20 colours used" and **Expand all** / **Collapse all**.
5. One row per colour, in the product's own colour order, closed by default except the colour just switched to and any colour that
   already has a cell. A closed row reads "Not used", or "4 of 4 sizes · 1.8000 m" (same value in every size) or "4 of 4 sizes ·
   1.6000–2.2000 m" (different values).
6. Opening Indigo shows its four sizes as labelled fields: S 1.6000, M 1.8000, L 2.0000, XL 2.2000 (each field in m). **Fill empty
   sizes with 1.6000 m** appears once a colour has at least one filled cell and an empty one; it never overwrites a typed cell.
7. Footer: "8 of 44 cells filled" and **Done** (closes the Sheet; nothing saves separately — **Save product** saves the whole page).

### Product page — capacity, cost and margin
Board: `Inv16ProductShow` (desktop)
1. **Can produce now**: the headline is **guaranteed**, 16. Caption: "from current stock, any mix of colours and sizes."
2. **Bottleneck**: "Bottleneck: Denim 12oz Black (Black, size XL)." Body: "40.0000 m in stock covers 16 units of Black, size XL — the
   smallest across every colour and size." A second, quieter line: "Best case: 112 units, in Indigo — limited there by Metal shank
   button 17mm."
3. **Capacity by colour** — a new table, right after the bottleneck:

   | Colour | Guaranteed | Best | Limited by               |
   | ------ | ---------- | ---- | ------------------------ |
   | Indigo | 103        | 112  | Denim 12oz Indigo (XL)   |
   | Black  | 16         | 22   | Denim 12oz Black (XL)    |

   and a totals row "All colours (any mix)" repeating guaranteed 16 / best 112. "Limited by" links to the material, as today's
   bottleneck does.
4. **Cost breakdown**: Materials "TND 18.384–23.994", Unit cost "TND 33.884–39.494", Margin "TND 49.506–55.116 · 55.62–61.92%".
   Labor and Overhead stay single values ("TND 12.000", "TND 3.500"). When nothing on a product varies, the range collapses to one
   number and the card reads exactly as it does today.
5. **Composition table**: the denim line's Quantity, Effective qty and Units possible each read "Varies", with a small **View grid**
   link that opens the same grid read-only. Waste % and the material's own stock stay single values.
6. **Products list**: the capacity badge shows "16–112 units" (success tone) in place of one number; "0–112 units" (warning tone,
   short-stock icon) when guaranteed is 0 but best isn't; "Can't produce" (danger, unchanged) only when best is 0. Unit cost and
   margin show the same range, in the same cell.

### Start batch — a mixed grid runs short
Board: `Inv16StartBatchShort`
- When the units typed cover more than one colour or size and at least one line is **By colour and size**, "You can start at most N
  now" is replaced by: **"Short on Denim 12oz Black. Restock it first, or lower the units."** (two materials: "Short on Denim 12oz
  Black and Metal shank button 17mm. Restock them first, or lower the units."). Each short material's exact amount stays in the
  materials table's "Short by" row above the message, unchanged.
- A batch in one colour and size only, or a product with no **By colour and size** line, keeps today's single "at most N" message.

### Category lock
- "Denim Jacket has materials set by colour and size, so its category can't change to one with other sizes. Keep a category with
  sizes S, M, L, XL." (today's existing ending, unchanged wording).

## States

| State                       | Content                                                                                                                                      |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| **Grid, no cells yet**       | A fresh **By colour and size** line: "No cells set yet", **Edit grid** still offered, no warning                                            |
| **Colour with no cells**     | A colour row in the Sheet reads "Not used", collapsed, no warning — a normal, allowed state                                                  |
| **Loading**                  | Capacity by colour and the Composition table skeleton like every other server-filled card                                                    |
| **Error, bad cell**          | "Enter a whole number from 0 to 1,000,000 with up to 4 decimals." under the cell                                                              |
| **Error, empty line**        | "Add at least one quantity for Denim 12oz Black, or switch it to Same for every variant." if a By colour and size line is saved with no cells |
| **Error, data changed**      | "The sizes or colours of Denim Jacket have changed. Enter its quantities again." if the product's colours or sizes moved since the page opened |
| **Confirm, switching back**  | "Switch to one quantity for every variant?" · body names the material and the exact count cleared · **Keep the grid** / **Switch anyway**   |
| **Disabled, Start batch**    | Short-materials message visible, the button disabled, reachable by keyboard                                                                  |
| **Long content**             | 20 colours × 11 sizes: the Sheet's accordion scrolls, **Find colour** filters it; the composition line stays one compact row regardless of grid size |

## Wording
- Composition line: "Same for every variant" · "By colour and size" · "4 of 44 cells · Indigo only" · "No cells set yet" · "Edit grid".
- Switch-back confirm: "Switch to one quantity for every variant? This clears the 4 quantities you set for Denim 12oz Indigo across
  its colours and sizes." · "Keep the grid" · "Switch anyway".
- Grid Sheet: "Quantity by colour and size, in m. A blank cell means that colour and size doesn't use this material." · "Find colour"
  · "2 of 20 colours used" · "Expand all" · "Collapse all" · "Not used" · "4 of 4 sizes · 1.8000 m" · "4 of 4 sizes · 1.6000–2.2000 m"
  · "Fill empty sizes with 1.6000 m" · "8 of 44 cells filled" · "Done".
- Product page: "from current stock, any mix of colours and sizes." · "Bottleneck: Denim 12oz Black (Black, size XL)." · "40.0000 m
  in stock covers 16 units of Black, size XL — the smallest across every colour and size." · "Best case: 112 units, in Indigo —
  limited there by Metal shank button 17mm." · "Capacity by colour" · "All colours (any mix)" · "Varies" · "View grid".
- Start batch: "Short on Denim 12oz Black. Restock it first, or lower the units." · "Short on Denim 12oz Black and Metal shank button
  17mm. Restock them first, or lower the units."
- Category lock: "Denim Jacket has materials set by colour and size, so its category can't change to one with other sizes."
- Errors: "Enter a whole number from 0 to 1,000,000 with up to 4 decimals." · "Add at least one quantity for Denim 12oz Black, or
  switch it to Same for every variant." · "The sizes or colours of Denim Jacket have changed. Enter its quantities again."

## Phone layout
Board: `Inv16Phone`
- **Composition editor:** the quantity-mode control is full width under the line; **Edit grid** opens the same Sheet full height.
- **Grid Sheet:** the accordion is the same, one size per row instead of a row of four; **Find colour** and the cell count sit above
  the list; **Done** is fixed at the bottom.
- **Product page:** guaranteed stays the big number; the bottleneck and "Best case" lines stack; **Capacity by colour** becomes one
  stacked card per colour (Guaranteed, Best, Limited by), not a scrolling table; the cost breakdown's ranges wrap onto their own line
  under each label.
- **Start batch:** the shortfall message wraps full width above the disabled button; the "Short by" rows stay above it, unchanged.
- Every field and button stays 44 px high on a phone; nothing scrolls sideways at 320 px.
