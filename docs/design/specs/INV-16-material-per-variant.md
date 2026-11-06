# INV-16 · Material use per colour and size — design spec
Status: proposal · Designer · 2026-11-06 · Boards: [Invory App UI canvas][canvas], title
"Material use per colour and size (INV-16)" — `Inv16BomEditor`, `Inv16QuantityGrid`, `Inv16ProductShow`, `Inv16StartBatchShort`,
`Inv16Phone`

[canvas]: https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc

## Problem and user
The workshop owner, at the cutting table on a laptop (composition editor, product page) and in the stockroom on a phone (Start
batch), needs a composition line to cost more material for some colours or sizes — a bigger denim panel in XL, a colour-specific
stock material (Denim 12oz Indigo vs Denim 12oz Black) — without re-typing every material for every colour by hand, and without
losing today's one-line simplicity for the materials that never vary (thread, buttons, labels). Reading the product afterwards, the
owner needs one honest headline number for "how many, any mix" plus a way to see "what if I only made one colour."

This spec covers: the composition editor's new per-line quantity-mode toggle and the colour × size grid; the product page's
capacity (guaranteed, best, bottleneck) and its new per-colour breakdown; the cost and margin range; the Start batch shortfall
message for a mixed grid; and the category-lock refusal. It amends ADR-0027 (Proposed); nothing here changes a formula or a number
— every figure named is the server's, from the ADR's appendix.

## Flow
1. **Edit a line.** On Edit product → Composition, each line shows a quantity-mode control next to its material: **Same for every
   variant** (today, selected by default) or **By colour and size**.
2. Switching a line to **By colour and size** opens its quantity grid at once (a Sheet), the same way **Add material** already
   opens its picker at once. If the line had a typed quantity, every colour × size cell is pre-filled with that number as a
   starting point; the user clears or edits the exceptions. A fresh line (no quantity yet) opens with every cell blank.
3. In the grid, the user finds a colour (search, or scrolls the accordion — closed by default except the colour just switched to),
   opens it, and types a quantity per size. A blank cell means that colour and size doesn't use this material — nothing to do for
   colours that don't need it. **Fill empty sizes with …** copies the first typed value in that colour into its still-blank sizes,
   so a line that only differs on one or two sizes is a few clicks, not a few hundred.
4. **Done** closes the sheet. Back in the composition list, the line shows a summary ("4 of 44 cells · Indigo only") and an
   **Edit grid** button instead of a quantity field. Waste % and Placement notes stay single fields, same as today.
5. Switching a filled **By colour and size** line back to **Same for every variant** asks first: "Switch to one quantity for every
   variant? This clears the 4 quantities you set for Denim 12oz Indigo across its colours and sizes." — because real typed cells
   would otherwise be silently discarded.
6. **Save product.** The server recalculates; the product page now shows guaranteed capacity as the headline, best next to the
   bottleneck, and a Capacity by colour table.
7. **Start batch** with units in two or more colours/sizes: if a mixed grid is short on stock, the message names every short
   material and says "Restock it first, or lower the units." — no single "cut at most N" is offered, because none exists for a
   mix.

## Layout

### Composition editor — the quantity-mode control and the line (desktop, and 375)
Each composition line keeps today's row — Material · Waste · Placement notes · remove — and gains a second line underneath it,
inside the same bordered row group, holding the quantity:
- A two-option segmented control (`ToggleGroup`, single-select): **Same for every variant** / **By colour and size**.
- When **Same for every variant**: today's quantity field and unit, unchanged, right beside the toggle.
- When **By colour and size**: a compact summary in place of the field — a pill with the cell count and, when the line only uses
  one or two colours, their names ("4 of 44 cells · Indigo only"; "0 of 44 cells" / "No cells set yet" for a line with none) — plus
  an **Edit grid** button that opens the Sheet (step 2–4 of the Flow). A short preview of up to 4 values may show beside the pill on
  a wide screen (chips: "S 1.6000 · M 1.8000 · L 2.0000 · XL 2.2000 m"); it is a convenience, not required reading.
- Board: `Inv16BomEditor` (desktop); `Inv16Phone`, frame 1 (375, stacked card per line, the toggle full width).

### The quantity grid (the Sheet)
Opens as a `Sheet` (side panel on desktop, full height on a phone) titled with the material's name, so the list of composition
lines stays in view underneath — the design system's own rule for side work. Inside, top to bottom:
1. One line of help text: "Quantity by colour and size, in <unit>. A blank cell means that colour and size doesn't use this
   material."
2. **Find colour** — a plain text filter over the product's own colours (not a picker: the product's colour list is fixed here).
   Needed because a product can carry every colour of the workshop (20+); scrolling an unfiltered list of that length to find one
   colour is the layout risk the tech-lead flagged.
3. A count ("3 of 20 colours used") and **Expand all** / **Collapse all**.
4. An accordion, one row per colour, in the product's own colour order. **Closed by default**, except: the colour the user just
   switched this line to (if any), and any colour that already has at least one cell. A closed row's header shows the colour swatch
   and name plus a summary: "Not used", or "N of <sizes> sizes · <value>" when every filled cell in that colour holds the same
   number, or "N of <sizes> sizes · <min>–<max>" otherwise.
5. Opening a colour reveals its sizes as a row of labelled fields (the same pattern as Start batch's colour × size grid: size label
   above, a quantity field with the material's unit below, one row per colour, wrapping on a phone to one column). An open colour
   with ≥ 2 sizes and at least one filled cell also shows **Fill empty sizes with `<first filled value>` `<unit>`** — it only fills
   blank cells, never overwrites a typed one.
6. Footer: a running "N of M cells filled" count and **Done** (closes the sheet; nothing is "saved" separately — the grid is part of
   the same product form, saved with the page's own **Save product**).
- This is why the grid is a *sheet*, not an inline expansion under the row: at up to 20 colours × 11 sizes (220 cells) an always-open
  grid would make the product-edit page itself scroll for thousands of pixels, on every line that varies — the opposite of "the
  answer first." A sheet keeps the composition list scannable and puts the full grid only in front of the person actually filling
  it in.
- Boards: `Inv16QuantityGrid` (desktop); `Inv16Phone`, frame 2 (375, the same accordion, one size per row instead of a row of four).

### Product page — capacity, per colour, cost and margin
- **Hero "Can produce now"** (unchanged component, new wording only when the product has any "By colour and size" line that
  actually varies, i.e. `guaranteed ≠ best`): the big number is **guaranteed**. Caption extends today's sizes sentence:
  - varies, > 1 colour, > 1 size → "from current stock, any mix of colours and sizes."
  - varies, > 1 colour, 1 size → "from current stock, any mix of colours."
  - doesn't vary → unchanged (today's "any mix of sizes" / plain sentence, exactly as now).
- **Bottleneck callout** (same component, extended): title names the bottleneck material and, when it varies, the colour and size
  that binds it — "Bottleneck: Denim 12oz Black (Black, size XL)." Body: "<stock> in stock covers <guaranteed> units of <colour>,
  size <size> — the smallest across every colour and size." A second, visually secondary line (same muted treatment the "next
  limit" sentence already uses) gives **Best**: "Best case: 112 units, in Indigo — limited there by Metal shank button 17mm." When
  guaranteed is 0 but best is not, this is exactly where the user learns it isn't hopeless — that case needs the tech-lead's
  colour-naming value for best (see "For the tech-lead").
- **Capacity by colour** — a new card/table, shown only when the product has any varying line, placed right after the bottleneck
  callout: one row per colour (Guaranteed · Best · Limited by, the material linked as today's bottleneck links), a muted detail of
  the binding size in the "Limited by" cell, and a totals row "All colours (any mix)" repeating the product-level guaranteed/best —
  the same relationship today's Sizes & colours card already has between its per-colour rows and its Total row. On a phone this
  becomes one stacked card per colour (reusing the pattern of the existing per-colour stock table), not a scrolling table.
- **Cost breakdown card**: "Materials", "Unit cost" and "Margin" rows become a range exactly where they were one number, in the same
  place, same size: "TND 18.254–23.864", "TND 33.884–39.494", "TND 49.506–55.116 · 55.62–61.92%". Labor, Overhead and Selling price
  stay single values (they don't vary by variant). When nothing varies, min = max and the row reads exactly as it does today — no
  new case to design for that product.
- **Products list / table**: unit cost and margin become the same range, in the same cell. The capacity badge, compact by nature,
  shows the range *in* the badge rather than headline-plus-secondary: "16–112 units" (success tone, since it's still producible),
  or "0–112 units" (warning tone + a short-stock icon, since the guaranteed mix is 0 but it isn't unproducible), or "Can't produce"
  (danger, unchanged) only when **best** is 0. This is the one place capacity is shown as a compact range rather than as
  guaranteed-plus-secondary-best; flagged as a question below since it extends recommendation 1 beyond the product page.
- Boards: `Inv16ProductShow` (desktop, all of the above together); `Inv16Phone`, frame 3 (375).

### Composition table (read-only, on the product page)
A "By colour and size" line can't show one quantity, effective quantity or units-possible figure. Its row keeps Material, Waste
(single value, unchanged) and the material's own stock-on-hand (single value, unchanged — it's the material's total stock, not
per-variant). Quantity, Effective qty and Units possible each read "Varies" (muted) with a small **View grid** link that opens the
same grid, read-only, so the curious owner can see the exact cells without leaving the page. Line cost is left as "Varies" too
rather than inventing a new per-line range figure (see "For the tech-lead" — a per-line cost range is possible later if wanted, but
isn't needed for INV-16). The line-level "Bottleneck" highlight keeps using the product's own bottleneck (the binding variant's),
unchanged mechanics.

### Start batch — shortfall on a mixed grid
When the grid being started has more than one colour/size cell with units > 0 **and** the product has at least one "By colour and
size" line, "You can start at most N now" no longer applies (no single N is meaningful for that mix — ADR-0027 §6). The dialog's
summary alert becomes: **"Short on `<material 1>`[, `<material 2>`][ and `<material 3>`]. Restock `it`/`them` first, or lower the
units."** (singular "it" for one short material, "them" for more). Each short material's exact amount is already in the materials
table's "Short by" row directly above the alert (unchanged visual, from today's dialog) — the alert names them again, briefly,
without repeating the numbers, so a screen-reader user who only hears the alert still gets every material's name. When only one
variant is typed, or the product has no "By colour and size" line, today's single-N message is unchanged (a single N is still
exactly true there). Board: `Inv16StartBatchShort`.

### Category lock
Exact wording, matching today's lock voice (ADR-0015 / `ProductSizeLock`):
> "`<product>` has materials set by colour and size, so its category can't change to one with other sizes."
followed by today's existing ending, unchanged: "Keep a category with sizes `<list name>`." (several sizes) or "Keep a "One size"
category." (a one-size list). Shown the same way as today's two existing locks: an inline error under the Category field. Shown in
`Inv16BomEditor`'s Category snippet.

## States
| State | Content |
|---|---|
| **Default** | A line "Same for every variant" with a typed quantity (today, unchanged); a line "By colour and size" with cells filled, summary pill + Edit grid |
| **Empty (grid)** | A "By colour and size" line with zero cells: pill reads "No cells set yet", Edit grid still offered, no warning, no red state — this is a normal, allowed starting point |
| **Empty colour (in the sheet)** | A colour added to the product with no cells on this line: its accordion row reads "Not used", collapsed, no warning |
| **Loading** | The product page's Capacity by colour card and Composition table skeleton like every other server-filled card today (`SkeletonCard`) |
| **Error** | The grid's cells, waste and notes keep an invalid line's typed values on a failed save (unchanged rule); a per-cell error sits under that cell: "Enter a whole number from 0 to 1,000,000 with up to 4 decimals." for a bad cell; a line-level error "Add at least one quantity for `<material>`, or switch it to "Same for every variant"." when a "By colour and size" line has zero cells at save time |
| **Disabled** | "Start batch" disabled with the short-materials alert visible (today's pattern, unchanged) |
| **Read-only** | n/a — the grid and the toggle are edit-time only; the product page's figures are always read-only display |
| **Confirm** | Switching a filled "By colour and size" line back to "Same for every variant": title "Switch to one quantity for every variant?", body names the material and the exact count of quantities being cleared, buttons "Keep the grid" / "Switch anyway" |
| **Success** | Save product toast unchanged ("Product saved."); nothing new to confirm for the grid itself, since it is part of the same form |
| **Long content** | A product with 20 colours × 11 sizes: the sheet's accordion scrolls, Find colour filters it; the composition editor's line stays one compact row regardless of how large its grid is; the Capacity-by-colour table scrolls horizontally past the point its columns (3 anyway: Guaranteed/Best/Limited by) would ever need to, since colours are rows, not columns |

## Accessibility
- **Contrast**: the new cell-badge/pill (`#EEF0FB` on `#1D2770` text, matching the existing `a-info` tone) and the warning capacity
  badge reuse existing tokens already checked at 4.5:1 in both themes; no new colour introduced.
- **Targets**: every grid cell is a labelled text input at least 40 px tall on desktop and 44 px on the phone board (matching Start
  batch's own per-size fields); the segmented toggle's two buttons are each at least 36 px tall with 44 px achieved via padding on
  touch layouts, per the skill's own touch rule.
- **Names**: each grid cell's accessible name is "`<colour>` `<size>`" (e.g. "Indigo, size M"), not just the size label, since many
  identical size labels repeat down the sheet; the segmented toggle is a `radiogroup`/`ToggleGroup` with a visible label
  ("Quantity") read once per line, not repeated as "Quantity for Denim 12oz Indigo, Quantity for Denim 12oz Black…" unless a screen
  reader needs the row context (then it is, via `aria-label`, exactly as today's quantity field already does for its own
  `aria-label="Quantity per unit for <material>"`).
- **Keyboard (2.1.1).** The accordion headers are real buttons (`aria-expanded`), reachable and operable with Enter/Space; Find colour
  filters live without requiring Enter; Fill empty sizes is a normal button, not a drag gesture (2.5.7 n/a since nothing here is
  draggable).
- **Errors**: a per-cell error sits directly under that cell, matching the design system's existing field-error pattern (3.3.1,
  3.3.3); the line-level "add at least one quantity" error announces via the composition section's existing `role="alert"` region.
- **Reflow**: the sheet and its accordion work at 320 CSS px (phone board drawn at 375, the narrower edge case is the same pattern
  one column narrower); the Capacity-by-colour table never needs sideways scroll since colours are rows.

## Options and recommendation
**A. Where the grid lives.**
(a) **Recommended** — a `Sheet` opened by an "Edit grid" button, as specced above. Scales to any number of colours without
lengthening the product-edit page; matches the design system's existing rule that side work opens in a sheet.
(b) An inline accordion expanding directly under the row, no sheet. One click fewer to reach it, but a product with several
varying lines and many colours turns the edit page into a very long scroll, and phones get a doubly-nested scroll region (page
scroll + accordion). Rejected as the default, but worth revisiting only if user testing shows the extra click genuinely slows
people down for the common one- or two-colour case.

**B. Pre-filling the grid from a typed quantity.**
(a) **Recommended** — switching "Same" → "By colour and size" with a quantity already typed copies it into every cell as a
starting point (§Flow step 2). Matches the common real case (ADR's own worked example: sizes that are "mostly the same, bigger in
XL") — the user deletes or edits the few cells that differ instead of typing every cell from a blank grid.
(b) Never pre-fill; every switch starts from a fully blank grid. Safer (never puts a number somewhere the user didn't literally
type), but slower for the common case and contradicts "a missing cell means not used" less than it first seems — a pre-filled grid
is still edited before saving, same as a form's defaults always are. Rejected as the default.

**C. Capacity shown as a range in compact contexts (badge, list).**
(a) **Recommended** — the capacity badge shows "guaranteed–best units" when they differ, instead of the headline-plus-secondary
pattern used on the full product page. A table cell and a badge have no room for a secondary sentence next to a bottleneck callout
the way the product page does.
(b) The badge always shows guaranteed alone (ignoring best entirely in compact contexts), with the range only ever visible on the
product page. Simpler, but a list full of "0 units" red/warning badges for products that are perfectly makeable in their best
colour would read as far more alarming than it is. Flagged as a question below since this extends the approved recommendation 1
beyond where it was decided.

## For the product-owner
Proposed acceptance-criteria wording (plain numbers from ADR-0027's own worked example, reusable as-is):
- **AC · Quantity mode.** Given a composition line, when the user switches it to "By colour and size", then its quantity field is
  replaced by a quantity grid (opened at once) with one cell per colour and size of the product; a blank cell means that colour and
  size doesn't use the material.
- **AC · Guaranteed and best.** Given the Denim Jacket worked example (Indigo and Black, varying by colour and size), when its page
  opens, then "Can produce now" reads "16, from current stock, any mix of colours and sizes", and the bottleneck callout names
  "Denim 12oz Black (Black, size XL)" with a secondary line "Best case: 112 units, in Indigo."
- **AC · Capacity by colour.** Given the same product, then a "Capacity by colour" table shows Indigo (guaranteed 103, best 112) and
  Black (guaranteed 16, best 22), each linked to its limiting material.
- **AC · Cost and margin range.** Given the same product, then unit cost reads "TND 33.884–39.494" and margin reads "TND
  49.506–55.116 · 55.62–61.92%" wherever a single unit cost or margin stood before.
- **AC · Dashboard "Can't produce".** A product counts as "Can't produce" only when its **best** capacity is 0, not its guaranteed.
- **AC · Mixed-grid shortfall.** Given a Start batch mix across two or more colours/sizes that is short on two materials, then the
  dialog reads "Short on `<material 1>` and `<material 2>`. Restock them first, or lower the units." with no "at most N" figure.
- **AC · Category lock.** Given a product with a "By colour and size" line, when its category is changed to one with a different
  size list, then it is refused: "`<product>` has materials set by colour and size, so its category can't change to one with other
  sizes. Keep a category with sizes `<list>`."
- **AC · Silent empty colour.** Given a product colour with no cells on a "By colour and size" line, then nothing warns about it;
  that colour's capacity is computed from its other lines only, exactly as ADR-0027 formula B defines.

## For the tech-lead
- **New value needed:** which colour achieves **best(P)** (and, for the per-colour table, which size achieves **best(P, c)**) — the
  ADR's appendix defines `binding(P, c)` for *guaranteed* but not for *best*. The spec above names the colour next to "Best case" on
  the bottleneck callout and in the Capacity-by-colour table's rows; without this value those sentences can only say the number.
  Recommended default if this is costly: ship the number alone ("Best case: 112 units.") and add the colour later.
- **New values needed, generally:** `guaranteed`, `best`, `binding` (colour + size) at product and colour level, per ADR-0027
  appendix B; `unit_cost_range` (min/max) and the equivalent margin range; a per-colour capacity table (guaranteed, best, bottleneck
  material + its binding size, per colour); whether a line is `by_variant` and its cell count/filled count, for the editor's
  summary pill; the product's colours' own stock values are unchanged (already sent).
- **Clarify the "guaranteed = 0, best > 0" edge case**: confirm whether a product colour with no line at all for a "by_variant"-only
  composition yields `max_producible(P, v) = null` (edge case G) or `0`. The UI designed above treats `null` as "No composition" for
  that colour (existing neutral badge state) and a real `0` as "Can't produce" for that colour (existing danger state) — both
  already exist as components; which one fires just needs confirming against the actual formula output.
- **New shadcn component**: none required beyond what's already in the design system (`ToggleGroup` for the quantity-mode control,
  `Sheet` and `Accordion` for the grid — `Accordion` is not yet listed among Invory's "Components to reach for" but is a standard
  shadcn component).
- **Cap on cells** (ADR-0027 §9, "order of 2,000"): once the tech-lead sets the exact number, the refusal wording is the
  product-owner's to write; this spec leaves it as `<cap>` deliberately.
- Everything else in this spec reads values already defined in ADR-0027's appendix; no other new figure is introduced.

## Questions for the product-owner / human
1. Should the **best-colour name** (and its binding size) be sent so the bottleneck callout and the capacity-by-colour table can
   say "Best case: 112 units, in Indigo" instead of just "Best case: 112 units"? (a) yes, name it — **recommended**, it's the
   sentence that turns "guaranteed is 0" from alarming into actionable · (b) no, ship the number alone for INV-16, add the colour
   later if it's cheap · default if unanswered: (a).
2. In compact contexts only (the products list's capacity badge), should capacity show as a **range** ("16–112 units") rather than
   the headline-plus-secondary pattern decided for the product page? (a) yes, range in the badge — **recommended**, there's no room
   for a secondary sentence in a table cell · (b) no, the badge always shows guaranteed alone, even when it reads "0 units" for a
   product that's fully makeable in its best colour · default if unanswered: (a).
3. Should the composition editor **pre-fill every cell** with the line's existing single quantity when switching to "By colour and
   size" (a convenience, immediately editable), or always start blank? (a) pre-fill — **recommended** · (b) always blank · default
   if unanswered: (a).
4. Is the **Sheet-based grid editor** (open via an "Edit grid" button, closed accordion by colour, Find colour filter) the right
   shape, or would an inline, always-expanded grid under the row be preferred for the common one-or-two-colour product even though
   it doesn't scale to 20+ colours? (a) Sheet, uniformly — **recommended** · (b) inline for ≤ 2 colours, Sheet beyond that · default
   if unanswered: (a).
5. Should the read-only Composition table on the product page get a **per-line cost range** (e.g. "TND 2.80–3.40") for a "By colour
   and size" line, instead of "Varies" with a link to the grid? This needs a new server value (line-level min/max) that ADR-0027
   doesn't currently define. (a) "Varies" + View grid for INV-16, add a per-line range later if asked — **recommended** · (b) add
   the per-line range now · default if unanswered: (a).
