# S-30 — Walk through every MVP screen and sign it off
> Phase 4 · Priority Must · Size S · Status **Ready** (approved by the human 2026-10-14) · Type acceptance
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Decision D-7 (2026-10-14) ·
> Depends on S-29 (the final dev setup in place, mail working) · Technical plan: none (no code change; findings become new stories)

## Story
As the workshop owner, I want to click through every Invory screen with a script that tells me what I should see, so that I can sign
the MVP off knowing it does what my workshop needs, not only that the automated tests pass.

## Why it matters
No screen has ever been signed off. The Phase 2 click-through has been pending since 2026-10-08, so "done" has meant "tests pass".
Phase 4 can't close until you accept the MVP.

## Acceptance criteria
AC-1 **Every MVP screen is covered.** The script below walks the materials list and its filters, adding, editing and deleting a material
(including the "in use" block and a photo), restock and history, "What you can make with it", the products list and its filters, adding and
editing a product with its composition, the product page (unit cost, can produce, bottleneck, margin), the sales log (record, edit, delete,
filters, totals), the dashboard, the catalogue settings, phone width, dark mode and keyboard use.

AC-2 **Real data, typed by hand.** You walk it on your own development data. No demo data is loaded (your decision, 2026-10-14). The worked
example is typed in by you, with SKUs that start with `WT-`, so it can't clash with your own items.

AC-3 **Every step says what you should see.** Each step gives the exact on-screen words and numbers, copied from the app on 2026-10-14
(commit `3a0785f`). The worked example gives Denim Jacket a unit cost of **TND 35.754**, **112** units to produce and the bottleneck
**"Metal shank button 17mm"**.

AC-4 **Decision D-7 is checked.** Step 8.1 checks that the dashboard's figure cards and top 5 fill in just after the page frame appears,
and that you find this acceptable.

AC-5 **Every finding is kept.** For each "Not OK", the product-owner writes a backlog item (or, if it blocks the MVP, a fix inside Phase 4)
before the MVP is declared accepted. Nothing you note is lost.

AC-6 **You sign it off.** You fill in the sign-off box at the end: accepted, accepted with findings, or not accepted. The product-owner
records the result in the status report.

AC-7 **About 35 minutes.** The core path (without the steps marked *optional*) takes about 30 minutes.

## Example with real numbers
The script builds the costing worked example by hand: Denim Jacket (labor TND 12.000, overhead TND 3.500, selling price TND 89.000) made
of Denim 12oz Indigo, Metal shank button 17mm, Polyester thread and Woven brand label.
It must show unit cost **TND 35.754**, can produce **112**, bottleneck **Metal shank button 17mm**, margin **TND 53.246 · 59.83%**.
After you restock 200 buttons at TND 0.38, the same product shows **TND 35.994**, can produce **126**, and the bottleneck becomes
**Denim 12oz Indigo**, which is the "next limit" the button page predicted.

## What you will see (UX)
This card **is** the script. Print it or keep it open next to the app. Each section ends with a result line: tick OK or Not OK and write
a short note. Words in "quotes" are exactly what the app should show.

---

## The walkthrough

### 0. Before you start (1 min)
- The app opens at http://localhost:8000 and Docker is running (database and test mailbox). Walk this as soon as S-29 lands.
- Use a desktop browser window at least 1280 px wide. Section 10 narrows it.
- Have one photo ready: a JPG, PNG or WebP under 4 MB. *Optional:* a PDF, to see it refused.
- Sign in. "Today" below means today's date.
- Everything you create has an SKU starting with `WT-` (walkthrough). Section 11 removes it all. A deleted item's SKU can be used again,
  so you can repeat the walkthrough later.

### 1. Materials list (1 min)
1.1 **Do:** click **Materials** in the left menu.
**Expect:** the title "Materials". Under it: "N materials · inventory value TND …". **Write N and the value down.** You also see the button
"Add material", a search box "Search by name or SKU", a menu "All categories", a sort menu "Name A–Z" and a tick box "Needs restock".
The columns are Material, Category, In stock, Reorder at, Unit cost, Stock value, Status and Used in. Every status shows an icon and a
word ("In stock", "Low stock" or "Out of stock"), never colour alone.

**Section 1 result:** [ ] OK [ ] Not OK — note:

### 2. Add four materials (7 min)
2.1 **Do:** click "Add material", then "Save material" without typing anything.
**Expect:** the page "Add material" with the cards Identity, Stock and cost, Supplier and notes, and Photo (optional). After saving, an
error appears under SKU, Name, Category, Unit of measure, Unit cost and Quantity in stock. The cursor jumps to SKU and nothing is saved.

2.2 **Do:** fill in the form and save.

| Field | Type |
|---|---|
| SKU | `WT-DEN-012` |
| Name | Denim 12oz Indigo |
| Category | Fabric |
| Colour | Indigo |
| Unit of measure | Metre (m) |
| Unit cost | 8.5 |
| Quantity in stock | 250 |
| Reorder at | 300 |
| Photo | drop your picture on "Drop a photo here or browse" (or click "browse") |

**Expect before saving:** "TND" in front of Unit cost and "/ m" after it, "m" after Quantity in stock and Reorder at, and a preview of your
photo with its file name and the buttons "Replace photo" and "Remove". *Optional:* pick the PDF first. Expect "Choose a JPG, PNG or WebP image."
**Expect after "Save material":** the message "Material created." and the material's page:
- title "Denim 12oz Indigo", `WT-DEN-012`, and the badges "Fabric" and "Low stock"
- your photo
- In stock **250 m** · Reorder at **300 m** · Unit cost **TND 8.500 / m** · Stock value **TND 2,125.000**
- a warning: "Below reorder level. 250 m on hand, reorder at 300 m."
- "What you can make with it": "Not used in any product yet."
- History: one entry dated today, "Created", "250 m at TND 8.500", "by <your name>"

2.3 **Do:** Materials → "Add material": `WT-BTN-017` · Metal shank button 17mm · Fastener · Piece (pc) · Unit cost 0.35 · Quantity in stock
900 · Reorder at 1000 → "Save material".
**Expect:** "Material created.", the badge "Low stock", Unit cost **TND 0.350 / pc**, Stock value **TND 315.000**, and "Below reorder
level. 900 pc on hand, reorder at 1,000 pc."

2.4 **Do:** `WT-THR-PES` · Polyester thread · Thread · Metre (m) · Unit cost 0.004 · Quantity in stock 20000 · Reorder at left empty.
**Expect:** the badge "In stock", In stock **20,000 m**, Reorder at "Not set", Unit cost **TND 0.004 / m**, Stock value **TND 80.000**,
no warning.

2.5 **Do:** `WT-LBL-WOV` · Woven brand label · Label · Piece (pc) · Unit cost 0.12 · Quantity in stock 400 · Reorder at left empty.
**Expect:** "In stock", Unit cost **TND 0.120 / pc**, Stock value **TND 48.000**.

**Section 2 result:** [ ] OK [ ] Not OK — note:

### 3. Find, sort and edit materials (3 min)
3.1 **Do:** open Materials.
**Expect:** the count is **N + 4** and the inventory value is **TND 2,568.000** higher than what you wrote down. The Denim row shows your
photo; the other three show a placeholder. "Used in" shows "—" for all four.

3.2 **Do:** type `WT-` in the search box. Then choose the category "Fastener". Then click "Clear filters".
**Expect:** after a short pause, only the four `WT-` materials. With "Fastener", only Metal shank button 17mm. "Clear filters" brings
everything back and keeps the sort.

3.3 **Do:** tick "Needs restock", then untick it.
**Expect:** Denim 12oz Indigo and Metal shank button 17mm are listed ("Low stock"); Polyester thread and Woven brand label are not.
(Your own materials that need a restock show too.)

3.4 **Do:** search `WT-` again and pick the sort "Unit cost high→low".
**Expect:** this order: Denim 12oz Indigo (TND 8.500 / m), Metal shank button 17mm (TND 0.350 / pc), Woven brand label
(TND 0.120 / pc), Polyester thread (TND 0.004 / m).

3.5 **Do:** open Polyester thread → "Edit". Change Quantity in stock to 19950 → "Save material".
**Expect:** the edit page is titled "Edit Polyester thread" with "WT-THR-PES · Thread" under it, and every field is filled in. After
saving: "Material updated.", In stock **19,950 m**, and the History, newest first: "Correction" "20,000 m → 19,950 m", then "Created".

**Section 3 result:** [ ] OK [ ] Not OK — note:

### 4. The worked example: a product and its composition (6 min)
4.1 **Do:** click **Products**.
**Expect:** the title "Products" with "Final items you make. Cost and capacity update from current material stock.", the button
"Add product", the filters "Search by name or SKU", "All categories", "All statuses" and a tick box "Can't produce", and the columns
Product, Category, Status, Unit cost, Price · margin, Can produce, Bottleneck and Sold (30 d).

4.2 **Do:** click "Add product" and fill in: SKU `WT-JKT-001` · Name Denim Jacket · Category Apparel · Status **Active** · Labor cost 12 ·
Overhead cost 3.5 · Selling price 89. (The photo is optional.)
**Expect:** the page "Add product" with "Details, photo and the materials that make one unit." Status starts as "Draft", with the hint
"Only active products can be sold." The Composition card says "No materials yet. Add the materials that make one unit."

4.3 **Do:** in Composition, click "Add material". The picker opens: type `WT-DEN` and pick Denim 12oz Indigo. Enter Qty per unit 1.8 and
Waste 10. Add three more rows the same way:

| Material | Qty per unit | Waste |
|---|---|---|
| Denim 12oz Indigo | 1.8 (m) | 10 |
| Metal shank button 17mm | 8 (pc) | 0 |
| Polyester thread | 120 (m) | 5 |
| Woven brand label | 1 (pc) | 0 |

**Expect:** the picker shows "Search by name or SKU…", and each option shows its stock and unit cost. A material already in the
composition shows "Already in this composition" and can't be picked again. The quantity box shows the material's unit. The card counts
"4 materials". Each row has Move up, Move down and Remove.

4.4 **Do:** click "Save product".
**Expect:** "Product created." The product page shows its frame at once and fills in within about a second:
- title "Denim Jacket", `WT-JKT-001`, the badges "Apparel" and "Active", and the buttons "Record sale", "Edit" and "Delete"
- the blue card "Can produce now" **112 units** "from current stock"
- "Unit cost" **TND 35.754** "materials, labor and overhead"
- "Sold · last 30 days" **0 units**, "TND 0.000 in sales"
- a warning: "Bottleneck: Metal shank button 17mm. 900 pc in stock covers 112 units at 8 pc each. It's also below its reorder level of
  1,000 pc." with the link "Open material"
- "Cost breakdown": Materials (4 lines, waste included) **TND 20.254** · Labor **TND 12.000** · Overhead **TND 3.500** ·
  Unit cost **TND 35.754** · Selling price **TND 89.000** · Margin **TND 53.246 · 59.83%**
- "Composition", "Materials needed to make one unit.":

| Material | Qty per unit | Waste | Effective qty | Unit cost | Line cost | In stock | Units possible |
|---|---|---|---|---|---|---|---|
| Denim 12oz Indigo | 1.8 m | 10% | 1.98 m | TND 8.500 | TND 16.830 | 250 m | 126 |
| Metal shank button 17mm | 8 pc | 0% | 8 pc | TND 0.350 | TND 2.800 | 900 pc | "Bottleneck" 112 |
| Polyester thread | 120 m | 5% | 126 m | TND 0.004 | TND 0.504 | 19,950 m | 158 |
| Woven brand label | 1 pc | 0% | 1 pc | TND 0.120 | TND 0.120 | 400 pc | 400 |

  with the footer "Materials total" **TND 20.254** and "112 units"
- "Recent sales": "No sales recorded for this product yet. Record a sale"

4.5 **Do:** open Products.
**Expect:** the row Denim Jacket: "WT-JKT-001 · 4 materials", Apparel, Active, **TND 35.754**, **TND 89.000** with **59.83%** under it,
the badge **"112 units"**, bottleneck **"Metal shank button 17mm"**, Sold (30 d) "—". Tick "Can't produce": Denim Jacket is not listed.
Untick it.

4.6 *Optional.* **Do:** Denim Jacket → "Edit". Move Woven brand label to the top with "Move up" (three times) → "Save product".
**Expect:** "Product updated." The composition now starts with Woven brand label. Every number is unchanged (TND 35.754, 112 units).

4.7 **Do:** Materials → Metal shank button 17mm → "Edit". Change Unit of measure to "Metre (m)" → "Save material".
**Expect:** an error under Unit of measure: "This material is used in 1 product composition, so its unit can't change." Nothing is
saved. Click "Cancel".

4.8 **Do:** look at the Metal shank button 17mm page.
**Expect:** the "Delete" button is greyed out, with "Used in 1 product, so it can't be deleted." under it. In the Materials list,
"Used in" now shows "1 product" for the four `WT-` materials.

**Section 4 result:** [ ] OK [ ] Not OK — note:

### 5. "What you can make with it" (1 min)
5.1 **Do:** stay on the Metal shank button 17mm page.
**Expect:** "What you can make with it" with "1 product uses this material. Here's how far your 900 pc go, and how many units you can
make right now with all its materials." The row Denim Jacket: Needs per unit **8 pc** · This stock covers **112 units** · Can make now
**112 units** · Limited by "This material (bottleneck)". Below it: "Suggestion: this material limits Denim Jacket to 112 units. If you
restock it, the next limit is Denim 12oz Indigo at 126 units."

5.2 **Do:** open Denim 12oz Indigo.
**Expect:** Needs per unit **1.98 m** · This stock covers **126 units** · Can make now **112 units** · Limited by "Metal shank button
17mm" (a link). No suggestion.

**Section 5 result:** [ ] OK [ ] Not OK — note:

### 6. Restock and history (3 min)
6.1 **Do:** Materials, search `WT-`. On the row Metal shank button 17mm, the "Restock" button is filled in colour because the material needs
restocking. Click it.
**Expect:** a panel opens under the row: "Restock Metal shank button 17mm", "In stock now: 900 pc · unit cost TND 0.350", the fields
Quantity received (with "pc"), Unit price paid (with "TND", hint 0.35), Date (today; a future date can't be picked), Reference (optional,
"PO or delivery note"), a ticked box "Use this price as the new unit cost", and the buttons "Cancel" and "Add to stock".

6.2 **Do:** enter Quantity received 200, Unit price paid 0.38, Reference `PO-WT-1`. Keep the box ticked. Click "Add to stock".
**Expect:** "Restocked Metal shank button 17mm: +200 pc." The panel closes. The row shows In stock **1,100 pc**, Unit cost
**TND 0.380 / pc**, Stock value **TND 418.000**, status "In stock", and the Restock button is no longer filled.

6.3 **Do:** click the History icon on the same row.
**Expect:** a panel "History · Metal shank button 17mm". A loading placeholder may flash, then three entries, newest first, each
"by <your name>": "Price change" "TND 0.350 → TND 0.380 per pc", "Restock" "+200 pc at TND 0.380 · PO-WT-1", "Created"
"900 pc at TND 0.350". Below them: the link "Open material page".

6.4 **Do:** open Products → Denim Jacket.
**Expect:** the answers changed. Can produce now **126 units**. Unit cost **TND 35.994**. "Bottleneck: Denim 12oz Indigo. 250 m in stock
covers 126 units at 1.98 m each. It's also below its reorder level of 300 m." Cost breakdown: Materials **TND 20.494**, Margin
**TND 53.006 · 59.56%**. In Composition, the button's line cost is **TND 3.040**, its units possible **137**, and the "Bottleneck" badge
has moved to Denim 12oz Indigo. The 126 is the "next limit" that step 5.1 promised.

**Section 6 result:** [ ] OK [ ] Not OK — note:

### 7. Sales (3 min)
7.1 **Do:** on Denim Jacket, click "Record sale".
**Expect:** the Sales page opens with the panel "Record a sale" (beside the list on a wide screen). Product shows "Denim Jacket ·
WT-JKT-001", with the hint "Only active products are listed." Unit price is pre-filled with **89.0000**. Date is today. A note says "The
total is calculated when you save. Recording a sale doesn't change material stock."

7.2 **Do:** Quantity 3, Note `Walkthrough` → "Save sale".
**Expect:** "Sale recorded." The panel closes. The first row reads: today · Denim Jacket (WT-JKT-001) · 3 · TND 89.000 ·
**TND 267.000** · Walkthrough. The four totals above the list (Total sales, Units sold, Sales recorded, Average sale) grow by
TND 267.000, 3 and 1. If you had no other sale in the last 30 days, they read TND 267.000, 3, 1 and TND 267.000.

7.3 **Do:** click the pencil on that row, change Quantity to 4 → "Save sale".
**Expect:** "Sale updated." The total is **TND 356.000**.

7.4 *Optional.* **Do:** search `Walkthrough`, then set the period to "Last 7 days", then the product to "Denim Jacket", then click
"Clear filters".
**Expect:** your sale stays listed at each step. "Clear filters" brings back every sale with "Last 30 days".

7.5 **Do:** open Denim Jacket again.
**Expect:** Can produce now is still **126 units**, because sales never change stock. "Sold · last 30 days" shows **4 units** and
**TND 356.000** in sales. "Recent sales" lists your sale with the note "Walkthrough". In the Products list, Sold (30 d) shows **4**.

**Section 7 result:** [ ] OK [ ] Not OK — note:

### 8. Dashboard, with decision D-7 (4 min)
8.1 **Do:** click **Dashboard**, then reload the page (F5) and watch it.
**Expect (D-7):** the title "Dashboard", "Sales and stock at a glance.", the period menu "Last 30 days" and the "Record sale" button appear
at once. The five figure cards (Total sales, Units sold, Inventory value, Needs restock, Can't produce) and "Top selling products" show
their labels with grey pulsing placeholders for a moment, then fill in. "Needs restock" and "Recent sales" fill in right after.
**Your call:** is this short wait acceptable? You decided yes on 2026-10-14. Tick Not OK if it bothers you in practice.

8.2 **Do:** read the figures.
**Expect:**
- Total sales includes **TND 356.000** (if it is your only sale: "TND 356.000" and "1 sale · last 30 days")
- Units sold includes **4** (alone: "across 1 product")
- Inventory value equals the value in the Materials page header
- Needs restock counts Denim 12oz Indigo, with "x low · y out of stock" under the number
- Can't produce shows "n of m products"; Denim Jacket is not one of them
- "Top selling products", "Last 30 days, ranked by units sold": Denim Jacket with **4 units** and TND 356.000 under it. The "Revenue"
  switch ranks by money instead.
- "Needs restock": Denim 12oz Indigo, "250 m · reorder at 300 m", "Low stock" (unless five other materials come first; "View all"
  opens the full list)
- "Recent sales": your sale, with the note "Walkthrough"

8.3 *Optional.* **Do:** switch the period to "This year".
**Expect:** only the figure cards and "Top selling products" reload. Your sale still counts.

8.4 **Do:** make the jacket impossible to produce. Materials → Woven brand label → "Edit" → Quantity in stock 0 → "Save material". Go back
to the Dashboard.
**Expect:** a red alert at the top: "Denim Jacket can't be produced. Woven brand label is out of stock." with "See product". Can't produce
is 1 higher. Needs restock is 1 higher, and the list shows Woven brand label first, "0 pc", "Out of stock". Click the "Can't produce" card:
the Products list opens filtered, with Denim Jacket and its badge "Can't produce". Open it: the blue card says "Can't produce" "with
current stock", and a red note says "Denim Jacket can't be produced: Woven brand label is out of stock."

8.5 **Do:** put it back: Woven brand label → "Edit" → Quantity in stock 400 → "Save material".
**Expect:** Denim Jacket can produce **126 units** again. The label's History shows two corrections: "400 pc → 0 pc" and "0 pc → 400 pc".

**Section 8 result:** [ ] OK [ ] Not OK — note:

### 9. Catalogue settings (2 min)
9.1 **Do:** click your name at the bottom of the left menu → "Settings". Under "Catalogue", click "Units".
**Expect:** "Units" with "The units materials are measured in. Rename or archive any of them, or add your own." The columns are Name,
Code, Dimension, Factor, Used by and Status. For example, Yard shows "1 yd = 0.9144 m" and Roll shows "Varies per material". Metre and
Piece count the walkthrough materials under "Used by".

9.2 **Do:** "Material categories" → "Add material category". Code `wt_test`, Name Walkthrough test → "Save material category".
**Expect:** a dialog "Add material category" with "Pick a short code now: it stays the same when the name changes." The Code hint reads
"Lowercase letters, digits and _ only, up to 32 characters. It can't be changed later." After saving: "Category created." and a new row
"Not used", "Active".

9.3 **Do:** "Edit" that row. Rename it Walkthrough sample → save.
**Expect:** the dialog "Edit Walkthrough test" with "Code wt_test can't be changed." Then "Category updated."

9.4 **Do:** "Archive" it. Then open Materials → "Add material" and look at the Category list. Then click "Cancel".
**Expect:** "Category archived.", the status "Archived", and the row moves to the bottom. The Category list of a new material doesn't
offer "Walkthrough sample".

9.5 **Do:** back in Material categories, click "Delete" on the archived row (only archived rows can be deleted) and confirm.
**Expect:** "Delete material category?" with "Walkthrough sample (wt_test) will be removed for good. This cannot be undone." and the
button "Delete material category". Then "Category deleted."

*Optional 9.6:* add a material category named `fabric` (lower case). Expect an error under Name: names are unique whatever their case.

**Section 9 result:** [ ] OK [ ] Not OK — note:

### 10. Phone width, dark mode and keyboard (3 min)
10.1 **Do:** open the browser's developer tools (F12), switch on the device toolbar (Ctrl+Shift+M) and set the width to 375 px. Visit
Materials, Denim Jacket and Sales → "Record sale".
**Expect:** each material is a card with the buttons Restock, History, View and Edit. On Denim Jacket the three answer cards stack, the
composition shows one card per material, and "Materials total" and "Can produce" sit at the bottom. "Record a sale" slides in from the
right. Nothing is cut off, nothing overlaps, and the page never scrolls sideways.

10.2 **Do:** Settings → "Appearance" → Dark. Visit the Dashboard, Denim Jacket and Materials.
**Expect:** everything is readable. Badges keep their icon and word. Switch back if you prefer.

10.3 **Do:** on Denim Jacket → "Edit", use only Tab, Shift+Tab, Enter and the arrow keys. Reach "Add material", open the picker with Enter,
type, pick with the arrows and Enter, use "Move up", and reach "Save product". Then choose "Cancel" without saving.
**Expect:** a visible focus outline at every step, and every control can be reached and used.

**Section 10 result:** [ ] OK [ ] Not OK — note:

### 11. Delete and tidy up (3 min)
11.1 **Do:** Sales → the bin icon on your sale.
**Expect:** "Delete sale?" with "The sale of 4 × Denim Jacket on <today, e.g. 14 Oct 2026> will be removed from the sales log and the
dashboard totals. Material stock is not affected. This cannot be undone." Click "Delete sale". Expect "Sale deleted." The totals drop back.

11.2 **Do:** Denim Jacket → "Delete".
**Expect:** "Delete product?" with "Denim Jacket (SKU WT-JKT-001) and its composition will be removed. This cannot be undone." Click
"Delete product". Expect "Product deleted." and the Products list without it.

11.3 **Do:** Metal shank button 17mm → "Delete" (no product uses it now, so the button works).
**Expect:** "Delete material?" with "Metal shank button 17mm (SKU WT-BTN-017) will be removed from your inventory. This cannot be undone."
Click "Delete material". Expect "Material deleted." Do the same for the other three `WT-` materials. The Materials header is back to
the N and the inventory value you wrote down in step 1.1.

You chose to remove the example (Q1), so do all of section 11.

**Section 11 result:** [ ] OK [ ] Not OK — note:

### 12. Sign-off
| | |
|---|---|
| Walked on (date) | |
| Result | [ ] MVP accepted · [ ] Accepted with findings (below) · [ ] Not accepted |
| Findings (step number + what you saw) | |

---

## Things you may notice (already visible in the code; you decide whether they are findings)
1. Form errors use the framework's standard wording, for example "The sku field is required." with a lower-case "sku". S-32 proposes clearer
   wording for a duplicate SKU only.
2. "Record a sale" pre-fills the unit price as "89.0000" (four decimals) while the rest of the app shows TND 89.000.
3. The composition picker says "Materials could not be loaded." and "Try again" if it fails. S-31 uses the same "Try again" wording for the
   other sections.

## Out of scope
- Account screens (profile, password, two-factor, passkeys): starter-kit features with their own tests. The password-reset email is
  checked in S-29.
- Speed measurement (S-18, done with S-29) and large catalogues (Phase 5: S-27, S-23).
- A section that fails to load (S-31) and two people saving the same SKU at the same moment (S-32): you can't trigger them by hand.
- Fixing what you find: each finding becomes its own backlog item.

## Questions for you (answered 2026-10-14)
Q1 After the walkthrough, should the `WT-` example stay in your development data? **Answer: (a) delete it in section 11**, which also tests
every delete. (Other option was: keep it and stop after step 11.1.)

Q2 When do you walk it? **Answer: (a) as soon as S-29 lands.** (Other option was: after S-31 and S-32 too.)

Q3 How do we handle findings? **Answer: (a)** the product-owner sorts them in one pass into "fix in Phase 4" (blocks the MVP) and "new
backlog item", and you confirm the split. (Other option was: fix every finding before you accept.)

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 Every MVP screen covered | | |
| AC-2 Real data, typed by hand | | |
| AC-3 Exact expectations | | |
| AC-4 D-7 checked (step 8.1) | | |
| AC-5 Every finding kept | | |
| AC-6 Signed off | | |
| AC-7 About 35 minutes | | |
