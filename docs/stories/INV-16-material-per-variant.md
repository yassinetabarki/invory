---
id: INV-16
title: Material use per colour and size
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

# INV-16 · Material use per colour and size

> [!abstract] Description
> As a **workshop owner**, I want **a composition line to use more material in some colours or sizes, and every figure to follow it**,
> so that **an XL jacket takes its 2.2 m of denim, Black denim is its own stock, and "Can produce now", the cost and Start batch tell
> me the truth instead of one quantity for every variant**. Each line is either **Same for every variant** (today, unchanged) or **By
> colour and size** (one quantity per colour and size; a blank cell means that colour and size doesn't use the material). The product
> page then shows **guaranteed** (what any mix of colours and sizes can reach) beside **best** (what one colour on its own can reach),
> a table of capacity by colour, and cost and margin as a range when they vary.
> New: the quantity choice and the grid on a composition line, "Capacity by colour", ranges for cost and margin, the mixed-batch
> shortfall message, a new category lock. Changed: the product page, the Products list, a material's "Used in" table, the dashboard's
> "Can't produce" and Start batch. The pages are drawn in [[INV-16-material-per-variant-ui]].
> Left out on purpose: a cost range per composition line (the read-only table says "Varies" and links to the grid), an inline grid
> for products with few colours (the grid always opens in a side panel), a multiplier per size, and one stock split by colour (both
> ruled out: Black denim and Indigo denim are two materials, each with its own stock).

> [!info] How it works today
> A composition line has one quantity, used by every colour and every size: the Denim Jacket's denim line reads "1.8000 m" for an S
> and for an XL, in Black and in Indigo. The product page shows one "Can produce now" (112, from current stock, any mix of sizes), one
> bottleneck ("Metal shank button 17mm"), one unit cost ("TND 35.754") and one margin. Start batch checks the units typed against that
> one composition and, when short, says "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs
> short. Restock it first, or cut 12 or fewer." There is no way to say an XL jacket needs more denim, or that Black denim is stocked
> apart from Indigo denim.

## Acceptance criteria

The **Denim Jacket**, colours Indigo and Black (in that order), sizes S · M · L · XL; labor TND 12.000, overhead TND 3.500, selling
price TND 89.000. Button, thread and label are **Same for every variant**: Metal shank button 17mm 8 pc (stock 900), Polyester thread
120 m at 5 % waste (stock 20,000), Woven brand label 1 pc (stock 400), as today. Its two denim lines are **By colour and size**, both at
8.5000 per m and 10 % waste:

| Line                  | Stock      | S      | M      | L      | XL     |
| --------------------- | ---------- | ------ | ------ | ------ | ------ |
| Denim 12oz Indigo (m) | 250.0000 m | 1.6000 | 1.8000 | 2.0000 | 2.2000 |
| Denim 12oz Black (m)  | 40.0000 m  | 1.6000 | 1.8000 | 2.0000 | 2.2000 |

The Indigo line has cells under Indigo only, the Black line under Black only. Can produce, per variant: Indigo S 112 · M 112 · L 112 ·
XL 103; Black S 22 · M 20 · L 18 · XL 16.

- [ ] **AC-1 · Choose how a line counts.** In Edit product, each composition line keeps its material, waste % and notes and gains the
  control **Same for every variant** (selected on every line that exists today and on a new line) and **By colour and size**.
  - **Same for every variant** shows today's quantity field and unit, unchanged.
  - **By colour and size** replaces the field with a summary and **Edit grid**: "4 of 8 cells · Indigo only" (a line with cells in
    one or two colours names them; with more, only the count, "12 of 20 cells"), or "No cells set yet" for a line with none.
  - Switching the Indigo line, typed 1.8000, to **By colour and size** opens its grid at once with all 8 cells holding 1.8000, ready
    to edit or clear. A line with no quantity typed opens with all cells blank.
  - Switching the filled Indigo line back asks: "Switch to one quantity for every variant? This clears the 4 quantities you set for
    Denim 12oz Indigo across its colours and sizes." with **Keep the grid** (nothing changes) and **Switch anyway** (the line shows
    its quantity field again, empty). A line with no cell switches back without asking.
  - On a phone the control is full width under the line, and every field and button is 44 px high.

- [ ] **AC-2 · The quantity grid.** **Edit grid** opens a side panel titled "Denim 12oz Indigo", with the composition list still
  behind it, "Quantity by colour and size, in m. A blank cell means that colour and size doesn't use this material.", **Find colour**
  (filters the product's own colours as you type), "1 of 2 colours used", **Expand all** and **Collapse all**.
  - One row per colour, in the product's colour order, closed except the colour just switched to and any colour that already has a
    cell. A closed row reads "Not used", "4 of 4 sizes · 1.8000 m" (the same value in every size) or "4 of 4 sizes · 1.6000–2.2000 m".
  - Opening Indigo shows S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000, each field named "Indigo, size M" and so on, with the unit m.
    With only S typed at 1.6000, **Fill empty sizes with 1.6000 m** appears; pressing it fills M, L and XL with 1.6000 and never
    changes a cell already typed.
  - The footer reads "4 of 8 cells filled" and **Done**, which closes the panel; nothing is saved until **Save product**. A cell left
    empty or typed 0 is no cell.
  - Black, with no cell on this line, reads "Not used" and nothing warns about it: Black's variants simply don't use Indigo denim.
  - On a phone the panel is full height, one size per row, with **Find colour** and the count above the list and **Done** fixed at
    the bottom; nothing scrolls sideways at 320 px. With 20 colours and 11 sizes the list scrolls and **Find colour** narrows it.
  - Read-only: on the product page, **View grid** (see AC-4) opens the same panel with the cells shown and none editable.

- [ ] **AC-3 · Saving, and keeping the grid true.**
  - **Save product** saves the page and its grids together; the toast reads "Product saved.".
  - Refused, nothing saved: a cell that isn't a number from 0 to 1,000,000 with up to 4 decimals, under that cell: "Enter a whole
    number from 0 to 1,000,000 with up to 4 decimals."; a **By colour and size** line with no cell: "Add at least one quantity for
    Denim 12oz Black, or switch it to Same for every variant."; when the product's colours or sizes changed since the page opened:
    "The sizes or colours of Denim Jacket have changed. Enter its quantities again."; more than 2,000 cells on the product: "Denim
    Jacket can hold at most 2,000 quantities across its materials. Remove some, or switch a line to Same for every variant." A failed
    save keeps every typed value.
  - Taking a colour off the product removes its cells in the same save, with no question. Adding a colour (Light wash) gives it no
    cells: its variants use only the **Same for every variant** lines (unit cost TND 18.924, can produce 112), and nothing warns.
  - Changing the category to one with other sizes is refused while any line is **By colour and size**: "Denim Jacket has materials set
    by colour and size, so its category can't change to one with other sizes. Keep a category with sizes S, M, L, XL." ("Keep a "One
    size" category." for a one-size list.) When the product is also on an order, has units in stock or in production, that existing
    refusal shows instead.
  - A material used by any line, in either mode, can't be deleted and its unit can't change, as today.

- [ ] **AC-4 · Guaranteed and best on the product page.** Opening the Denim Jacket:
  - **Can produce now** reads "16 units" with "from current stock, any mix of colours and sizes." ("any mix of colours" for one size).
  - The bottleneck callout reads "Bottleneck: Denim 12oz Black (Black, size XL)." and "40.0000 m in stock covers 16 units of Black,
    size XL — the smallest across every colour and size.", then in a quieter line "Best case: 112 units, in Indigo — limited there by
    Metal shank button 17mm.", the material names being links as today.
  - **Capacity by colour** follows, one row per colour in the product's colour order:

    | Colour | Guaranteed | Best | Limited by             |
    | ------ | ---------- | ---- | ---------------------- |
    | Indigo | 103        | 112  | Denim 12oz Indigo (XL) |
    | Black  | 16         | 22   | Denim 12oz Black (XL)  |

    and a last row "All colours (any mix)" with 16 and 112. A colour that uses no material at all reads "No composition" in its row
    and counts for neither figure. While it loads the card shows a placeholder; on a phone each colour is a stacked card with
    Guaranteed, Best and Limited by.
  - With Denim 12oz Black at 0 m: "0 units" with the same caption, "Best case: 112 units, in Indigo — limited there by Metal shank
    button 17mm.", Black reading 0 and 0. "Can't produce" shows only when best is 0.
  - In the **Composition** table the two denim lines read "Varies" under Quantity, Effective qty and Units possible, each with a
    **View grid** link; waste % and the material's stock stay single values.
  - The guaranteed and best figures and the bottleneck are exact: 40 ÷ 2.42 gives 16, never rounded up.

- [ ] **AC-5 · Cost and margin as a range.** Wherever one unit cost or margin stood, the Denim Jacket shows its lowest to highest:
  - Cost breakdown: Materials "TND 18.384–23.994", Unit cost "TND 33.884–39.494", Margin "TND 49.506–55.116 · 55.62–61.92%"; Labor
    "TND 12.000" and Overhead "TND 3.500" stay single. The hero **Unit cost** reads "TND 33.884–39.494" with "varies by colour and
    size" in place of "same for every size". On a phone each range wraps onto its own line under its label.
  - Products list: the same unit cost and margin ranges in the same cells, and the capacity badge "16–112 units" (success tone);
    with Black at 0 m "0–112 units" (warning tone, with the short-stock icon); "Can't produce" (danger, as today) only when best
    is 0.
  - When nothing on a product varies (all lines **Same for every variant**, or one variant), the range is one number and every
    place reads exactly as today: "TND 35.754", "112 units".
  - Money keeps its currency's decimals (TND 3); every figure comes from the server and the screen computes none.

- [ ] **AC-6 · Start batch and Undo batch on a mixed grid.**
  - Start batch of Indigo M 6 · Indigo L 10 · Black S 5 (21 units) takes 33.8800 m Denim 12oz Indigo (6 × 1.98 + 10 × 2.2), 8.8000 m
    Denim 12oz Black (5 × 1.76), 168 pc button, 2,646 m thread and 21 pc label, and starts three batches In cutting. "Can produce
    now" goes from 16 to 12 and Indigo's guaranteed from 103 to 89.
  - Black S 10 · Black XL 10 · Indigo M 10 (30 units) needs 41.8000 m of Black denim against 40.0000 m: the table's "Short by" row
    reads 1.8000 m and the message reads "Short on Denim 12oz Black. Restock it first, or lower the units."; with the button at 100
    pc as well: "Short on Denim 12oz Black and Metal shank button 17mm. Restock them first, or lower the units." The materials are named in
    composition order, no "at most" number is given, and **Start batch** stays disabled but reachable by keyboard. The check is on
    the exact total: Black S 10 · Black XL 9 (39.3800 m) starts.
  - One colour and size only, or a product with no **By colour and size** line, keeps today's message: Black XL 17 reads "Not enough
    stock to cut 17 units. You can cut at most 16 now: Denim 12oz Black runs short. Restock it first, or cut 16 or fewer."
  - Undo batch of the Indigo L batch (10 units, In cutting) gives back 22.0000 m Indigo denim, 80 pc, 1,260 m and 10 pc, and nothing
    of the Black denim. Undoing all three batches gives back exactly what the start took. On a phone the message wraps full width
    above the disabled button and the "Short by" rows stay above it.

- [ ] **AC-7 · Nothing changes where nothing varies, and the other screens follow.**
  - A product with no **By colour and size** line keeps today's figures and words everywhere: the Denim Jacket with every line
    **Same for every variant** reads 112, "TND 35.754", "Limited by Metal shank button 17mm" and "from current stock, any mix of
    sizes". Existing products are not touched when this ships.
  - A batch started before this change, then undone, gives back what it always would have.
  - The dashboard counts a product in "Can't produce" only when its best capacity is 0, as the Products list does.
  - A material's page, "Used in" the Denim Jacket: for Denim 12oz Black, "Needs per unit" reads "Varies", "This stock covers" reads
    "16–22 units" (Indigo denim: "103–142 units"), "Can make now" reads the product's guaranteed 16 with "This material
    (bottleneck)" on the Black denim, and no "Suggestion:" sentence is shown for that product. Products with no **By colour and
    size** line read as today.
  - The Sales log and "Record a sale" are not changed: a sale has no colour or size and still pre-fills the one selling price.
