# S-43 — Save a product without filling in the overhead cost
> Phase 4 · Priority Should (next in line, your choice 2026-10-14) · Size S · Status **Acceptance** (card approved 2026-10-14; delivered 2026-10-14, not committed yet; product-owner review done, waits for your click-through) · Type feature
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Related: S-30 (the walkthrough
> types an overhead of 3.5, so it is not affected) · S-31 and S-32 (on hold so this story goes first) · Technical plan: added by the tech-lead
> Asked for by the human on 2026-10-14: "we need to remove overhead cost from product creation as required field".
> Started over the two-story limit (S-29 in progress, S-08 in acceptance) by the human's explicit choice, 2026-10-14.

## Story
As a workshop owner adding a product, I want to leave "Overhead cost" empty when I don't track overhead, so that I can save the product
without typing a 0 I don't think about.

## Why it matters
Today "Add product" refuses to save until "Overhead cost" has a value. A blank field shows "The overhead cost field is required." Many
small workshops don't split out overhead per product, so they are forced to type 0 on every product. Nothing in the costing needs that
value to be typed: a product already counts 0 overhead when the value is 0.

## How it works today (checked 2026-10-14)
- "Add product" and "Edit <product>" share one form. In the "Costs and price" section, "Labor cost" and "Overhead cost" both carry the red
  required star; "Selling price" is marked "(optional)". The overhead field starts empty with a grey "0" placeholder.
- Saving with the overhead blank is refused on **both** create and edit, with "The overhead cost field is required." under the field.
- The unit cost is materials + labor + overhead (the costing rules). The product page's "Cost breakdown" card shows an "Overhead" row, and
  the "Unit cost" card says "materials, labor and overhead".
- No decision or ADR made overhead mandatory. The stored value already falls back to 0 when nothing is given; only the form's check
  insists on a typed value. Labor cost is required in exactly the same way (see Q4).

## Business rule (approved by the human 2026-10-14; the tech-lead writes it into the costing rules before any code changes)
**Overhead cost is optional. A blank overhead is saved as 0 and counts as 0 in the unit cost.** Any value that is typed follows today's
rules: 0 or more, at most 4 decimals. The unit-cost formula does not change. Labor cost stays required.

## Acceptance criteria
AC-1 **Create without overhead.** Given "Add product" with every required field filled in and "Overhead cost" left empty, when I click
"Save product", then the product is saved, I see "Product created." and the product's page opens. No error appears under "Overhead cost".

AC-2 **Blank counts as 0.** A product saved with a blank overhead has an overhead of 0: its unit cost is materials + labor, and the
"Cost breakdown" card shows "Overhead" with TND 0.000 (see the example).

AC-3 **Edit: clearing the field.** Given a product with an overhead of TND 3.500, when I open "Edit", empty "Overhead cost" and save, then
I see "Product updated.", the overhead becomes TND 0.000 and the unit cost drops by TND 3.500.

AC-4 **Edit shows what was saved.** Given a product whose overhead is 0 (typed or left blank), when I open "Edit", then "Overhead cost"
shows "0".

AC-5 **Typed values still checked.** A typed overhead is still refused when it is below 0, has more than 4 decimals, or is not a number,
with the same messages as today. 0 typed by hand is accepted, as today.

AC-6 **The field says it is optional.** On "Add product" and "Edit <product>", the label reads "Overhead cost (optional)" with no red star,
and screen readers no longer announce it as required. "Labor cost" keeps its star and stays required (Q4).

AC-7 **Existing products unchanged.** Products saved before this change keep their overhead, unit cost, margin, capacity and bottleneck
exactly as they were.

## Example with real numbers
The Denim Jacket of the costing worked example, added with "Overhead cost" left empty: labor TND 12.000, selling price TND 89.000, the
usual four materials (materials TND 20.254, waste included).

| | With overhead 3.5 (today's example) | Overhead left empty |
|---|---|---|
| Materials (4 lines, waste included) | TND 20.254 | TND 20.254 |
| Labor | TND 12.000 | TND 12.000 |
| Overhead | TND 3.500 | **TND 0.000** |
| **Unit cost** | **TND 35.754** | **TND 32.254** |
| Selling price | TND 89.000 | TND 89.000 |
| Margin | TND 53.246 · 59.83% | **TND 56.746 · 63.76%** |
| Can produce now | 112 units | 112 units (unchanged) |
| Limited by | Metal shank button 17mm | Metal shank button 17mm (unchanged) |

Margin check: 89.000 − 32.254 = 56.746, and 56.746 ÷ 89.000 = 63.76 %. The overhead never affects "Can produce" or the bottleneck.

Edit example (AC-3): open "Edit Denim Jacket" for the jacket saved with 3.5, clear "Overhead cost" and click "Save product". You see
"Product updated." and the product page now shows the right-hand column of the table: unit cost TND 32.254 instead of TND 35.754.

## What you will see (UX)
- **Where:** the "Costs and price" section of "Add product" and "Edit <product>".
- **Label:** "Overhead cost (optional)", in the same muted "(optional)" style as "Selling price (optional)". The red star goes.
- **Hint under the field (new):** "Leave empty if you don't track overhead. It counts as 0."
- **Placeholder:** the grey "0" stays.
- **Section text unchanged:** "Labor and overhead are added to the materials cost to get the unit cost."
- **Product page, "Cost breakdown" card:** the "Overhead" row stays and shows TND 0.000. The "Unit cost" card keeps "materials, labor and
  overhead". No "Not set" wording (Q1).
- **Toasts unchanged:** "Product created." and "Product updated."
- **Errors:** "The overhead cost field is required." no longer appears. The other overhead messages are unchanged.
- Works at 375 px, in dark mode and with the keyboard only, like the rest of the form. The approved mockup shows the field without
  "(optional)"; this story knowingly changes that label only.

## Out of scope
- Removing "Overhead cost" from the form or from the product: not planned. The field stays; it just isn't required.
- Making "Labor cost" optional: not now (Q4); a later story if you want it.
- Telling "no overhead" apart from "overhead not entered yet" on screen (for example a "Not set" label or a list of products missing it):
  not planned (Q1).
- Changing existing products' overhead in bulk: not planned (AC-7).
- A workshop-wide default overhead (for example "add TND 2.000 to every product"): not planned; would be a new story.
- The demo data, the golden-path test and the S-30 walkthrough keep typing an overhead of 3.5 and are not changed by this story.

## Questions for you (answered 2026-10-14)
Q1 **What does an empty overhead mean?** **Answer: (a)** it is 0: saved as 0, the unit cost counts 0, the page shows TND 0.000 and "Edit"
shows "0". (Other option was: (b) "not set", where the product remembers it was left empty and the page shows "Not set".)

Q2 **Where does it apply?** **Answer: (a)** "Add product" and "Edit <product>". (Other option was: (b) "Add product" only, with editing
still requiring a value.)

Q3 **Existing products?** **Answer: (a)** unchanged, they keep the overhead they have. (Other option was: (b) set every existing product's
overhead to 0.)

Q4 **Labor cost too?** **Answer: (a)** overhead only; labor cost stays required. (Other option was: (b) make labor cost optional too, with the
same rule.)

Q5 **When?** **Answer:** next, ahead of S-31 and S-32. In your words: "stop S-31 and S-32 for now". S-31 and S-32 are on hold, not dropped;
their cards stay `Ready`. (The options offered were: (a) Phase 4 Should after S-31 and S-32, recommended · (b) next, ahead of S-31 and S-32 ·
(c) Phase 5.) You chose to start S-43 now even though S-29 (in progress) and S-08 (in acceptance) already fill the two-story limit.

Q6 **Approve the card?** **Answer: (a)** yes. The card is `Ready`.

## For the tech-lead's sizing
- Bring the approved business rule (above) into the costing rules before code changes.
- Confirm that no other screen, export or check relies on overhead always being typed.
- The hint and "(optional)" change the form's existing tests for required fields.

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 Create without overhead | Backend test "saves a blank overhead as 0, and the Denim Jacket then costs 32.2540" (blank, empty and not sent; "Product created.", product page opens); "reports every broken field at once" no longer lists overhead; form test "sends an empty selling price and an empty overhead as empty strings" · click-through step 3 | Met; waits for your click-through |
| AC-2 Blank counts as 0 | Same backend test: stored 0, materials 20.2540 + labor 12.0000 + overhead 0.0000 = unit cost 32.2540, margin 56.7460 · 63.76, 112 units, button limits · screen test "keeps the Overhead row and shows a zero overhead as TND 0.000" · step 3 | Met; waits for your click-through |
| AC-3 Edit: clearing the field | Backend test "clears the overhead to 0 and the unit cost drops by the old overhead" (35.7540 → 32.2540, "Product updated.") · step 5 | Met; waits for your click-through |
| AC-4 Edit shows what was saved | Form test "shows 0 in Overhead cost for a product whose overhead is 0" · step 4 | Met; waits for your click-through |
| AC-5 Typed values still checked | Invalid-input checks on create and edit: "text overhead cost" (new), "negative overhead cost", "overhead cost with 5 decimals"; "free product" accepts 0 typed by hand. Only "required" was removed, so the other messages are unchanged · step 6 | Met; waits for your click-through |
| AC-6 The field says it is optional | Form test "marks the required fields and labels the optional ones": "Overhead cost (optional)" isn't announced as required and is read with its hint; Labor cost still is. The golden-path test finds the field by its new name · steps 2 and 6 | Met; your eyes for the look |
| AC-7 Existing products unchanged | No change to stored data; the worked-example, product-page, demo-data and PostgreSQL tests pass unchanged (35.7540 / 112 / 59.83) · step 7 | Met; waits for your click-through |

Product-owner review 2026-10-14 (delivery report: all cards APPROVED, every gate green on the final tree). Verdict: ready for your click-through.

## Click-through (about 10 minutes, http://localhost:8000)
Your development data has no demo workshop (you chose not to load it), so you type a small two-material jacket with `OH-` SKUs and
delete it at the end. Expected figures: materials TND 19.630 (denim 1.98 m × TND 8.500 = 16.830, plus 8 buttons × TND 0.350 = 2.800).

1. **Do:** Products: write down the unit cost of one of your own products. Then Materials → "Add material": `OH-DEN-012` · Denim 12oz
   Indigo · Fabric · Metre (m) · Unit cost 8.5 · Quantity in stock 250 → "Save material". Again: `OH-BTN-017` · Metal shank button 17mm ·
   Fastener · Piece (pc) · Unit cost 0.35 · Quantity in stock 900 → "Save material". **Expect:** "Material created." twice.
2. **Do:** Products → "Add product", look at "Costs and price". **Expect:** "Labor cost" with a red star, "Overhead cost (optional)" with no
   star, a grey "0" in the box and under it "Leave empty if you don't track overhead. It counts as 0."
3. **Do:** fill in `OH-JKT-001` · Denim Jacket · Apparel · Active · Labor cost 12 · **leave Overhead cost empty** · Selling price 89. In
   Composition, add Denim 12oz Indigo 1.8, waste 10, and Metal shank button 17mm 8, waste 0 → "Save product". **Expect:** "Product created."
   with no error. "Can produce now" **112 units**, "Unit cost" **TND 31.630**. "Cost breakdown": Materials (2 lines, waste included)
   TND 19.630 · Labor TND 12.000 · **Overhead TND 0.000** · Unit cost TND 31.630 · Selling price TND 89.000 · Margin **TND 57.370 · 64.46%**.
   The bottleneck is Metal shank button 17mm.
4. **Do:** "Edit". **Expect:** "Overhead cost (optional)" shows **0**. **Do:** type 3.5 → "Save product". **Expect:** "Product updated.",
   Overhead TND 3.500, Unit cost **TND 35.130**, Margin TND 53.870 · 60.53%, still 112 units.
5. **Do:** "Edit", empty "Overhead cost" → "Save product". **Expect:** "Product updated.", Overhead TND 0.000, Unit cost back to
   **TND 31.630** (TND 3.500 less).
6. **Do:** "Edit". Type -1 in "Overhead cost" → "Save product": expect a red message under the field and nothing saved. Type 2.12345 →
   save: again a message under the field. Then empty "Labor cost" → save: expect "The labor cost field is required." under Labor cost.
   Click "Cancel".
7. **Do:** open the product you noted in step 1. **Expect:** the same unit cost as before.
8. *Optional.* **Do:** narrow the window to phone width, or switch to dark mode, and open "Edit" on the jacket. **Expect:** the label and
   hint are readable and wrap cleanly.
9. **Do:** tidy up. Denim Jacket → "Delete" → "Delete product". Then delete both `OH-` materials. **Expect:** "Product deleted.", then
   "Material deleted." twice.
