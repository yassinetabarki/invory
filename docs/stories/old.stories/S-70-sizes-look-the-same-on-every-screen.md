---
id: S-70
title: Sizes look the same on every screen
type: feature
phase: 5
priority: Should
size: M
status: Done
created: 2026-10-23
tags:
  - story
  - phase-5
---

# S-70 · Sizes look the same on every screen

> [!abstract] Description
> As a **workshop owner**, I want **a size and its count to look the same on every screen**, so that **"Denim Jacket in S, M, L, XL"
> and "S 10 · M 20" read the same on the product page, the product form, the order page, the Orders list and every window with size
> fields**.
> S-58, S-60, S-61 and S-56 each built their own piece of sizes, so the same fact looks different from one screen to the next. This
> story brings every piece in line with the design board "Size building blocks and states". It changes how sizes look, where a size's
> message sits, and adds the lowest number a started size can go to. No figure, rule or step of any flow changes.

> [!info] How it works today
> - **Order page:** under "Denim Jacket · PRD-JKT-001 · 50 units", the sizes are a plain grey line, "S 10 · M 20 · L 15 · XL 5". A line
>   recorded before sizes reads "One size 50" as text.
> - **Product page:** "Sizes S, M, L, XL" is small grey text between the "Apparel" and "Active" badges, so it reads like a caption.
>   "Unit cost TND 35.754" has "materials, labor and overhead" under it.
> - **Chips:** the "In stock" figure shows a shoe size with a colon inside its chip: [36: | 4].
> - **Product form:** under Category, "Sizes: S, M, L, XL" and "Sizes come with the category." are both small grey hint text.
> - **Size fields:** they are wide and short, with the figure on the left. At 375 px three fit on a row, so a shoe's 11 sizes take four
>   rows. When M is wrong ("2.5"), the M field jumps onto a row of its own with "Enter a whole number from 0 to 1,000,000." under it, so
>   the row no longer reads S, M, L, XL.
> - **Edit order:** nothing shows how low a started size can go. With 10 M already in cutting, ready to ship or shipped, you only find
>   out when "Save order" refuses M 8: "M can't be less than the 10 units already in cutting, ready to ship or shipped."
> - **Orders list:** "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" is all in one font and one colour.

## Acceptance criteria
- [x] **AC-1 · A size reads as a chip.** Boutique Lina's order page shows "Denim Jacket · PRD-JKT-001 · 50 units", with the SKU in the
  number font. Under it there is one chip per size ordered: "S 10", "M 20", "L 15", "XL 5". Each chip has the size in bold on grey and
  its count beside it in the number font, the same chip as the "In stock" figure. A size with 0 units has no chip. A line recorded
  before sizes shows one chip, "One size 50". Screen readers hear the sizes once, as "S 10, M 20, L 15, XL 5", not as four chips. In
  every chip, on the order page and on the "In stock" figure alike, a shoe size shows its bare label: [36 | 4], not [36: | 4]. Screen
  readers still hear "36: 4". ✅ 2026-10-23 · 99daefe
- [x] **AC-2 · The product page.** Under "Denim Jacket", in the row "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active", the sizes
  are an outlined badge between "Apparel" and "Active", as tall as those two badges. It reads "Sizes 36 to 46" on the Canvas Low
  Sneaker and "One size" on the Leather Tote. "Unit cost TND 35.754" has "same for every size" under it on a product with several
  sizes. The Leather Tote ("One size") keeps "materials, labor and overhead". ✅ 2026-10-23 · d6a6815
- [x] **AC-3 · The product form names the sizes.** Under Category, choosing Apparel shows "Sizes:" in grey and one outlined tag per
  size: [S] [M] [L] [XL]. A list with more than 6 sizes shows its name in bold instead of tags: "Sizes: **36 to 46**" for Footwear. A
  "One size" category such as Bag shows **One size** in bold. Under it, "Sizes come with the category." stays in small grey text.
  Screen readers hear "Sizes: S, M, L, XL", "Sizes: 36 to 46" or "One size", then the hint. With no category chosen on "Add product",
  nothing shows, as today. ✅ 2026-10-23 · d6a6815
- [x] **AC-4 · Size fields look the same everywhere.** On "New order", "Edit order", the move window and "Update stock", each size field
  shows its figure centred, with the size's label small and bold above it. Size fields are 44 px high; other fields keep their height.
  A 7-digit figure ("1000000") shows whole. At 375 px four size fields fit on a row, so a shoe's 11 sizes take three rows, without
  scrolling sideways. ✅ 2026-10-23 · b28eaad
- [x] **AC-5 · A size's message keeps the row.** Typing "2.5" in M and clicking "Save order" marks M in red and keeps S, M, L and XL on
  one row. The message shows under the size fields and names the size: "M: Enter a whole number from 0 to 1,000,000.", or "Size 36:
  Enter a whole number from 0 to 1,000,000." for a shoe. Where the form shows "Total 50 units", the messages come under it. Two sizes
  in error give two lines, in size order. A message that already names its size shows as it is, with no second name: "M can't be less
  than the 10 units already in cutting, ready to ship or shipped.", "You can move at most 20 units of M.". The same holds in the move
  window and "Update stock". When you move to M, a screen reader reads "M: Enter a whole number from 0 to 1,000,000." with it. ✅ 2026-10-23 · b200905, b28eaad
- [x] **AC-6 · A started size shows its minimum.** In "Edit order", with 10 M already in cutting, ready to ship or shipped, the M field
  shows a lock icon and "At least 10" under it before you type anything. Screen readers hear "At least 10" with the M field. Sizes
  with nothing started show no hint. Saving M 8 still shows "M can't be less than the 10 units already in cutting, ready to ship or
  shipped." ✅ 2026-10-23 · ce9ca1d, 4d646e9
- [x] **AC-7 · The Orders list, and phone width.** In "Products", "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" keeps its words,
  with "50" in the number font and the sizes part in grey. At 375 px the chips wrap onto more lines, a shoe's 11 chips included, and
  the sizes badge wraps with the other badges under the product's name. Nothing scrolls sideways. ✅ 2026-10-23 · 99daefe, d6a6815

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 6 commits
> Tech-lead · 2026-10-23 · approved 2026-10-23 · delivered 2026-10-23
> Why complex: a small change to the data the order pages receive (each size's started units), the wording of the server's
> per-size messages, and six screens.

### In short
Most of this story is how sizes look, so most of it is screen work. Two things change behind the screens first. The server puts
the size's name in front of each per-size message that doesn't already name it ("M: Enter a whole number from 0 to 1,000,000.",
"Size 36: …"), so the screen can show every message as it comes, under the Total, without guessing whether a message already names
its size. And each size of an order line carries its started units (in cutting + ready to ship + shipped), counted by the server
like the line's own `started`, so "At least 10" never adds anything up on the screen. Then the screens follow the board: one shared
size chip (the "In stock" chip) on the order page and on "In stock", the sizes badge and the unit cost line on the product page, the
sizes line in the product form, and one size field shape used by "New order", "Edit order", the move window and "Update stock". No
figure, rule or flow step changes.

### Steps

| Step       | What it does                                           | Proves           | Lane         | Who      | Size       |
| ---------- | ------------------------------------------------------ | ---------------- | ------------ | -------- | ---------- |
| **S-70-a** | The server names the size in its per-size messages     | AC-5             | CRITICAL     | Backend  | ~80 lines  |
| **S-70-b** | Each size of an order line sends its started units     | AC-6             | CRITICAL     | Backend  | ~20 lines  |
| **S-70-c** | One size chip on the order page and "In stock"         | AC-1, AC-7       | NON-CRITICAL | Frontend | ~180 lines |
| **S-70-d** | Sizes badge, unit cost line, the form's sizes line     | AC-2, AC-3, AC-7 | NON-CRITICAL | Frontend | ~150 lines |
| **S-70-e** | Size fields: their shape, and messages under the Total | AC-4, AC-5       | CRITICAL     | Frontend | ~250 lines |
| **S-70-f** | A started size shows "At least N" in Edit order        | AC-6             | CRITICAL     | Frontend | ~70 lines  |

| AC   | Proved by                                                                                   |
| ---- | ------------------------------------------------------------------------------------------- |
| AC-1 | S-70-c (chips with the size bold on grey, spoken once, bare shoe label, "One size 50")      |
| AC-2 | S-70-d (outlined sizes badge; "same for every size" or "materials, labor and overhead")     |
| AC-3 | S-70-d (tags up to 6 sizes, the list's name in bold above 6, "One size", what is spoken)    |
| AC-4 | S-70-e (centred figure, small bold label, 44 px, 7 digits whole, four to a row at 375 px)   |
| AC-5 | S-70-a (the size named in the message), S-70-e (messages under the Total, row kept, linked) |
| AC-6 | S-70-b (started units per size), S-70-f (lock and "At least 10" under the field, linked)    |
| AC-7 | S-70-c (Orders list styling, chips wrap), S-70-d (the badge wraps), browser check at 375 px |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. No
table, query or migration changes, so no step needs the PostgreSQL suite; after the last wave the combined tree runs the full
`composer test:pgsql` once, `composer test:browser` (the order golden path reads the Orders list's words, which don't change), and a
click-through at 375 px and 320 px.

#### S-70-a · The server names the size in its per-size messages
**Commit:** `feat(orders): per-size messages name their size (S-70-a)`

- The naming rule of 2026-10-22 moves out of `OrderMessages::sizeName()` into one small shared helper (for example
  `App\Services\Products\SizeNames`): a size in a sentence ("M", "size 36", "Size 36" at the start) and a message with its size in
  front ("M: …", "Size 36: …"). `OrderMessages` and the "Update stock" request both use it.
- The three per-size messages that don't name their size get the size in front:
  - New / Edit order (`StoreOrderRequest`, shared by Edit order): "M: Enter a whole number from 0 to 1,000,000.";
  - the move window (`MoveOrderLineUnitsRequest`, `sizes.<size id>`): "M: Enter a whole number of units.";
  - "Update stock" (`UpdateFinishedStockRequest`): "M: Enter a whole number from 0 to 1,000,000.".

  A key that isn't a size of the line or the list keeps the bare message (the screen never sends one, and the "sizes have changed"
  message covers it).
- Messages that already name the size stay as they are: "M can't be less than the 10 units already in cutting, ready to ship or
  shipped.", "You can move at most 20 units of M.". Messages about the whole line or window ("Enter at least one unit of Denim
  Jacket.", "A line can hold up to 1,000,000 units.", "The sizes of … have changed …", the short-stock message) don't change.
- The tech-lead writes in this commit (approved by the human with this Technical work on 2026-10-23): the business rules file, "How
  a size is named in text" (a chip shows the bare label, spoken "36: 4"; a per-size message that doesn't name its size starts with
  it, "M: …", "Size 36: …").

**Tests** (existing tests updated; no new file)
- AC-5, the order form's validation dataset: "2.5" in M → "M: Enter a whole number from 0 to 1,000,000." on `lines.0.sizes.<M>`;
  "x" in 36 of a shoe line → "Size 36: Enter a whole number from 0 to 1,000,000.".
- AC-5, the move window's dataset: "2.5" in M → "M: Enter a whole number of units."; "-1" in 36 → "Size 36: …".
- AC-5, "Update stock"'s dataset: the same two rows with "Enter a whole number from 0 to 1,000,000.".
- The existing "can't be less than" and "at most N units of M" tests stay unchanged: they prove there is no second name.

> [!example]- Files (9)
> - `.claude/skills/invory-costing-domain/SKILL.md` (tech-lead, approved 2026-10-23)
> - `app/Services/Orders/OrderMessages.php`, `app/Services/Products/SizeNames.php` (new)
> - `app/Http/Requests/Orders/StoreOrderRequest.php`, `app/Http/Requests/Orders/MoveOrderLineUnitsRequest.php`
> - `app/Http/Requests/Products/UpdateFinishedStockRequest.php`
> - `tests/Feature/Orders/OrderSizesWriteTest.php`, `tests/Feature/Orders/MoveSizesTest.php`
> - `tests/Feature/Products/FinishedStockWriteTest.php`

#### S-70-b · Each size of an order line sends its started units
**Commit:** `feat(orders): each size of an order line sends its started units (S-70-b)`

- `OrderLineSizeResource` adds `started`, read from the size row's `HasStageCounts` (in cutting + ready to ship + shipped), the same
  way `OrderLineResource` sends the line's. No new query. It reaches every page that sends order lines (order page, Edit order, the
  Orders list); only Edit order uses it.
- `domain.ts`, `OrderLineSize`: `started: number; // in_cutting + ready_to_ship + shipped, computed server-side`.
- Type-compat in the same commit, because the new field breaks typed literals: the `OrderLineSize` builders in
  `resources/js/test/fixtures.ts`, `order-line-card.test.tsx`, `orders-table.test.tsx` and `move-units-dialog.test.tsx` add
  `started`. Only those lines change in those files.

**Tests**
- AC-6, one contract row (`assertInertia` on Edit order): M with 4 in cutting, 3 ready to ship and 3 shipped → `started` 10; S with
  nothing started → 0. The existing `sizes` shape assertion on the order page gains the key.

> [!example]- Files (8)
> - `app/Http/Resources/OrderLineSizeResource.php`
> - `resources/js/types/domain.ts`
> - `resources/js/test/fixtures.ts`
> - `resources/js/components/orders/{order-line-card,orders-table,move-units-dialog}.test.tsx` (type-compat lines only)
> - `tests/Feature/Orders/OrderPageTest.php`, `tests/Feature/Orders/EditStartedOrderTest.php`

#### S-70-c · One size chip on the order page and "In stock"
**Commit:** `feat(orders): sizes as chips on the order page, the same chip as "In stock" (S-70-c)`

- A shared `SizeChips` in `components/shared`, lifted from the "In stock" figure: one chip per size, the bare label bold on the
  muted background, the count in the number font beside it, one thin border, the small radius, wrapping with a small gap. Zero sizes
  are left out unless the caller keeps them (only "In stock" does, greyed). The chips are hidden from screen readers; one hidden line
  carries the words, built by `formatSizes` with ", " ("S 10, M 20, L 15, XL 5"; a shoe size still spoken "36: 4").
- The "In stock" figure uses it, so its shoe chips lose the colon: [36 | 4].
- The order page: the SKU in the line's heading in the number font; under the heading the chips of a sized line, or one chip
  "One size 50" on a line recorded before sizes; a "One size" product shows none, as today.
- The Orders list: the line's count in the number font and " (S 10 · M 20 · L 15 · XL 5)" in grey; the words and spaces don't change
  (the golden path reads them).

**Tests**
- `SizeChips`, one test with a dataset: letter sizes → chips S 10 … XL 5 and the spoken line once; a shoe size → chip label "36",
  spoken "36: 4"; a zero size left out, or kept and greyed when asked.
- AC-1, the order line card test updated: a sized line shows the four chips and "S 10, M 20, L 15, XL 5" once; a line recorded before
  sizes shows "One size 50"; a "One size" product shows no chip.
- The "In stock" test updated: the shoe chip reads "36", the spoken line still "36: 0".

> [!example]- Files (8)
> - `resources/js/components/shared/size-chips.tsx` (new), `size-chips.test.tsx` (new)
> - `resources/js/components/products/finished-stock-figure.tsx`, `finished-stock-figure.test.tsx`
> - `resources/js/components/orders/order-line-card.tsx`, `order-line-card.test.tsx`
> - `resources/js/components/orders/orders-table.tsx`, `orders-table.test.tsx` (only if an assertion needs it)

#### S-70-d · Sizes badge, unit cost line, the form's sizes line
**Commit:** `feat(products): sizes badge, "same for every size" and the form's size tags (S-70-d)`

- Product page header: the sizes become the shadcn outline badge, the same height and shape as the category and status badges, with
  today's words ("Sizes S, M, L, XL", "Sizes 36 to 46", "One size"); it wraps with the other badges.
- "Unit cost": "same for every size" under it when the product has several sizes (the flag "Can produce now" already uses), "materials,
  labor and overhead" otherwise.
- Product form, under Category: a line in body size, grey "Sizes:" then one outline badge per size when the list has at most 6 sizes
  (a named constant), the list's name in bold above 6, **One size** in bold for one size; then the hint "Sizes come with the
  category." in small grey, then any error. The tags are hidden from screen readers and a hidden line says "Sizes: S, M, L, XL",
  "Sizes: 36 to 46" or "One size"; the Category select is described by that line and the hint. No category chosen: nothing, as today.

**Tests**
- AC-2, the header test, one dataset: Apparel "Sizes S, M, L, XL", Footwear "Sizes 36 to 46", Bag "One size", each a badge.
- AC-2, the hero test, one dataset: several sizes → "same for every size"; one size → "materials, labor and overhead".
- AC-3, the form test, one dataset: Apparel → tags S, M, L, XL and the Category select described by "Sizes: S, M, L, XL" and the hint;
  Footwear → "36 to 46", no tags; Bag → "One size". The existing "no category, no sizes line" test stays.

> [!example]- Files (6)
> - `resources/js/components/products/product-header.tsx`, `product-header.test.tsx`
> - `resources/js/components/products/product-hero.tsx`, `product-hero.test.tsx`
> - `resources/js/components/products/product-form.tsx`, `product-form.test.tsx`

#### S-70-e · Size fields: their shape, and messages under the Total
**Commit:** `feat(orders): size fields keep their row, messages under the Total (S-70-e)`

- `OrderSizeFields` (used by "New order", "Edit order", the move window and "Update stock") gets the board's shape: the label small and
  bold above each field, the figure centred, 44 px high, tabular figures. The fields sit in one wrapping grid whose columns fill the
  row with a minimum width that shows "1000000" whole, so a 375 px row holds four (a shoe's 11 sizes on three rows) and 320 px three;
  from tablet width they keep a fixed width. The figure keeps the input's 16 px on phones (a smaller one makes iPhones zoom in). If the
  move window or "Update stock" can't hold four at 375 px, their side padding drops to 16 px on phones only (a class on their
  `DialogContent`; the shadcn files aren't edited). The human approved this on 2026-10-23 (16 px on phones only; AC-4 holds as
  written).
- A field in error keeps its place: red border and `aria-invalid`, nothing under it. Under the "Total" line (or under the fields where
  there is no Total) come the messages: the line's own message first, then one line per size in error, in size order, each shown as
  the server sent it. Each size field is described by its own message line (focusing M reads "M: Enter a whole number from 0 to
  1,000,000.") and by its hint. Hints ("Up to 10", "was 0") stay under their field. Focus still goes to the first field in error.
- Other fields of these forms keep their height.

**Tests**
- AC-5, one test: M in error → the four fields stay in one group in size order, M is invalid and described by "M: Enter a whole number
  from 0 to 1,000,000.", shown after "Total 50 units"; S, L and XL aren't invalid.
- AC-5, one test: two sizes and a line message → the line message, then the two size lines in size order; a message that names its
  size shows unchanged.
- AC-4, one test: the fields share one wrapping grid and the figure is centred (jsdom has no layout; the browser check measures it).
- The move window, "Update stock" and order lines editor tests: their per-size error assertions follow the new place; no new tests.
- Browser check by the developer at 375 px and 320 px, in all four places, with 11 shoe sizes and "1000000" typed: four (three at
  320 px) to a row, 7 digits whole, nothing scrolls sideways; screenshots in the handoff.

> [!example]- Files (8)
> - `resources/js/components/orders/order-size-fields.tsx`, `order-size-fields.test.tsx`
> - `resources/js/components/orders/move-units-dialog.tsx`, `move-units-dialog.test.tsx`
> - `resources/js/components/products/update-stock-dialog.tsx`, `update-stock-dialog.test.tsx`
> - `resources/js/components/orders/order-lines-editor.test.tsx`
> - `resources/js/components/orders/order-line-row.tsx` (only if the wiring of the line's message changes)

#### S-70-f · A started size shows "At least N" in Edit order
**Commit:** `feat(orders): a started size shows its minimum in Edit order (S-70-f)`

- Edit order builds, for each saved sized line, a hint per size whose `started` is at least 1: a lock icon (lucide, hidden from screen
  readers) and "At least 10" (the number formatted, never worked out). It goes through `OrderSizeFields`' hints, so the field is
  described by it before anything is typed. Sizes with nothing started, new lines, and lines with one "Quantity" field show none.
- The hints come from the saved order, so they stay after a refused save; "M can't be less than the 10 units …" still shows under the
  Total.

**Tests**
- AC-6, one test in the Edit order form: M with 10 started is described by "At least 10"; S has no hint; a new line has none.

> [!example]- Files (5)
> - `resources/js/components/orders/order-form.tsx`, `order-form.test.tsx`
> - `resources/js/components/orders/order-lines-editor.tsx`
> - `resources/js/components/orders/order-line-row.tsx`
> - `resources/js/pages/orders/edit.test.tsx` (only if its fixture needs it)

### Contract (frozen before dispatch)
- `OrderLineSize` gains `started: number` (in_cutting + ready_to_ship + shipped, from the server). Nothing else in `domain.ts`.
- No route, prop name or page changes. Per-size errors keep their keys (`lines.<n>.sizes.<size id>`, `sizes.<size id>`); only the
  wording of three messages changes, as in S-70-a.

### Decisions
- **The server names the size, not the screen.** Only the server knows whether a message already names its size, and it already owns
  the naming rule (`sizeName`). The screen shows every per-size message as sent. Rejected: the screen putting the name in front, which
  would mean guessing from the text whether "M" is already in it.
- **Started units per size come from the size row**, like the line's: no new query, nothing added up on the screen.
- **One chip component**, lifted from the "In stock" figure into `components/shared`. The chip shows the bare label; the spoken line
  keeps `formatSizes` and its colon, so text and speech stay as the 2026-10-22 rule says.
- **Message order under the Total:** the line's own message first, then the sizes in size order. They rarely meet (the server checks
  the line's total only when every size is a whole number).
- No ADR: no new pattern, table or dependency.

### Order
Wave 1: backend S-70-a then S-70-b ∥ frontend developer 1 S-70-d (product files, nothing shared with S-70-b's type-compat lines).
Wave 2, after S-70-b lands: frontend developer 1 S-70-c ∥ frontend developer 2 S-70-e, then S-70-f (it needs `started`). The two
frontend developers' files don't overlap. Merge order: a, b, d, c, e, f.

### Risks
- Four fields with a 7-digit figure at 16 px in the two windows at 375 px is tight (about 70 px each) → a grid with a minimum width,
  small inner padding and gaps, and the windows' 16 px side padding on phones (approved by the human 2026-10-23); the developer
  measures it in a browser. If it still doesn't fit, the developer stops and the tech-lead reports it.
- Long hints ("Up to 1,000,000", "was 1,000,000") wrap inside a narrow field → fields align at the top; checked in the browser.
- The new wording breaks backend tests that expect the bare message → updated in S-70-a; frontend tests use their own strings.
- The new `started` key breaks typed test literals → type-compat lines in S-70-b's commit.
- Screen readers could hear chips twice or lose a message → chips hidden behind one spoken line, each size field described by its own
  message; both covered by tests.
- The golden path reads the Orders list's text → words and spaces kept; `composer test:browser` after the last wave.

### Progress
- 2026-10-23 · Build started in worktree `objective-hawking-a968ec` from 1591dc4. Tech-lead wrote the SKILL.md naming lines (S-70-a).
  Wave 1 dispatched: backend S-70-a then S-70-b ∥ frontend S-70-d.
- 2026-10-23 · Wave 1 cut off once by a rate limit (nothing written), re-dispatched. S-70-a, S-70-b, S-70-d approved in round 1; all
  five gates green on the combined tree (Pest 2558, Vitest 1313). Wave 2 dispatched: frontend S-70-c ∥ frontend S-70-e then S-70-f.
- 2026-10-23 · Wave 2: S-70-c, S-70-e, S-70-f approved in round 1. Combined tree: `composer test` (2558, 52 skipped), `npx vp test`
  (1321), `npm run check`, `npm run types:check`, `npm run build`, `composer test:pgsql` (2558) and `composer test:browser` (2) green.
  Size fields measured in a throwaway browser test at 375 px (4, 4, 3 per row) and 320 px (3, 3, 3, 2), 7 digits whole, no sideways
  scroll, in all four places. Delivered; nothing committed.
- 2026-10-23 · Fix round 1 after the product-owner and designer reviews (six layout items, approved by the human): row and label
  gaps, 8 px from tablet up, titles clear of the ✕, "At least N" kept as one phrase, smaller SKU, badge weight. Approved; all gates and
  `composer test:browser` green again. Screenshots `s70-*-fix.png` in the session scratchpad.
