---
id: S-73
title: The product page and form match the size board
type: feature
phase: 5
priority: Should
size: S
status: Done
created: 2026-10-24
tags:
  - story
  - phase-5
---

# S-73 · The product page and form match the size board

> [!abstract] Description
> As a **workshop owner**, I want **the product page and the product form to look like the design board "Sizes · products"**, so
> that **the three facts I check first (how many I can make, how many I have, what one costs) sit side by side, and the sizes under
> Category read clearly**.
> When S-56 added "In stock", the page's figures went two by two, so "Can produce now" shrank to half the column and "Sold · last 30
> days" took a place the board gives to nothing. This story puts the board's row of three back and moves the sales figure into "Recent
> sales". No figure, rule or step changes. Taller fields and 44 px buttons (S-68 and a later story for every form), the page title's
> size and the bottleneck banner's "units" (the board's "jackets" is only an example) are left as they are.

> [!info] How it works today
> - **Product page, wide screen:** next to the Denim Jacket's photo, four figures sit two by two: "Can produce now 112 units" | "In
>   stock 10 units", then "Unit cost TND 35.754" | "Sold · last 30 days 38 units" with "TND 3,382.000 in sales". The blue "Can produce
>   now" card is half the column wide. The figures are smaller than on the board.
> - **Recent sales:** lower down, the newest sales of the product with "View all", but no total for the period.
> - **Loading:** four grey placeholders show two by two.
> - **Phone (375 px):** the four figures stack in one column.
> - **Edit product:** under Category, "Sizes:" is followed by the tags [S] [M] [L] [XL]. They are smaller than the text around them, in
>   medium weight, with a faint border, so they look lighter and rounder than on the board.

## Acceptance criteria
- [x] **AC-1 · Three figures in one row.** On the Denim Jacket's page on a wide screen (1280 px), next to the photo, one row of three
  equal cards in this order: "Can produce now 112 units" (blue, "from current stock, any mix of sizes"), "In stock 10 units" (with
  "Update stock" at the top right and the chips S 3 · M 0 · L 5 · XL 2) and "Unit cost TND 35.754" ("same for every size"). The
  amounts are 36 px bold with a 15 px label, as on the board; the unit cost's "TND" is drawn at the size of "units". Under the row
  come the bottleneck banner and then "Cost breakdown", as today. "No composition" and "Can't produce" keep their words. Screen
  readers meet the three cards in the same order, under the names they have today. ✅ 2026-10-24 · 078bead
- [x] **AC-2 · The sales figure moves into "Recent sales".** The product page has no "Sold · last 30 days" card. Under the "Recent
  sales" heading it reads "38 units sold in the last 30 days · TND 3,382.000", or "1 unit sold in the last 30 days · TND 89.000" for
  one unit. These are the same two figures the card shows today. A product with older sales but none in the last 30 days reads "No
  sales in the last 30 days." above its list. A product that was never sold keeps only "No sales recorded for this product yet." and,
  when it is active, "Record a sale", as today. ✅ 2026-10-24 · 51ba6ce
- [x] **AC-3 · Narrower screens and loading.** From tablet width the three cards stay in one row while each can be at least 200 px
  wide. When there is less room, they wrap (two, then one under them) instead of getting narrower than that. At 375 px they stack in
  the same order: Can produce now, In stock, Unit cost. "Update stock" stays on the "In stock" label row. If the row is too narrow, it
  may drop under the label but never covers "1,000,000 units". A shoe's 11 chips wrap inside the "In stock" card, and nothing scrolls
  sideways. While the page loads, three placeholders fill the row (Can produce now, In stock, Unit cost), not four, and "Recent sales"
  keeps its placeholder. If the stock can't be read, the "In stock" card shows "Couldn't load the stock." and "Try again", as today.
  ✅ 2026-10-24 · 078bead
- [x] **AC-4 · Size tags in the form, as on the board.** On "Edit product" for the Denim Jacket, under Category, "Sizes:" is followed
  by [S] [M] [L] [XL] drawn as on the board: 13 px semibold text on the card's background (white in light mode), a clearly visible grey
  border darker than the lines between rows, slightly rounded corners and a little more room inside. They look the same way in dark
  mode. Only the form's tags change: the "Apparel", "Sizes S, M, L, XL" and "Active" badges on the product page keep their look.
  Footwear still reads "Sizes: **36 to 46**" and Bag "**One size**". Screen readers still hear "Sizes: S, M, L, XL", then "Sizes come
  with the category.". The tags still can't be clicked. ✅ 2026-10-24 · bf5db86

---

## Technical work
> [!warning] Complex · NON-CRITICAL · frontend · 3 commits
> Tech-lead · 2026-10-24 · approved 2026-10-24 · delivered 2026-10-24
> Why complex: three commits, and the board's 36 px unit cost doesn't fit a row of three at 1280 px as the page is today, so the
> fix is a design choice worth recording (see Decisions).

### In short
Only screens change; the server sends exactly what it sends today. First the "Sold · last 30 days" card goes and its two figures
become the line under "Recent sales". Then the three remaining cards become one row, with the same rule the board uses: equal
cards of at least 200 px that wrap instead of shrinking. To make that row fit at 1280 px with the sidebar open, the photo goes to
the board's width on wide screens and the currency code of the unit cost is drawn at the size of the word "units" (decided
by the human on 2026-10-24). Last, the form's size tags get the board's look. The product page's figure style is used only by these
three cards, so the dashboard and the material page don't change.

### Steps

| Step       | What it does                                  | Proves     | Lane         | Who      | Size       |
| ---------- | --------------------------------------------- | ---------- | ------------ | -------- | ---------- |
| **S-73-a** | The sales figure moves into "Recent sales"    | AC-2       | NON-CRITICAL | Frontend | ~70 lines  |
| **S-73-b** | Three figures in one row, at the board's size | AC-1, AC-3 | NON-CRITICAL | Frontend | ~120 lines |
| **S-73-c** | The form's size tags as on the board          | AC-4       | NON-CRITICAL | Frontend | ~15 lines  |

| AC   | Proved by                                                                                                           |
| ---- | ------------------------------------------------------------------------------------------------------------------- |
| AC-1 | S-73-b (three cards in order with today's names, 36 px figures and 15 px labels, one row at 1280 px in the browser) |
| AC-2 | S-73-a (the line, "1 unit", "No sales in the last 30 days.", never sold; in TND in the browser)                     |
| AC-3 | S-73-b (wrap at 200 px, stack at 375 px, "Update stock" on the label row, no sideways scroll, three placeholders)   |
| AC-4 | S-73-c (13 px semibold tags on the card's background, stronger border, header badges and spoken text unchanged)     |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green on its own. No
table, query or PHP code changes, so no step needs the PostgreSQL suite. Jsdom has no layout, so the row, the wrap and the 36 px
are proven in the golden path: the tech-lead runs `composer test:browser` after S-73-b and again after S-73-c.

#### S-73-a · The sales figure moves into "Recent sales"
**Commit:** `feat(products): the 30-day sales figure moves under "Recent sales" (S-73-a)`

- `ProductHero` loses the "Sold · last 30 days" card and its `sales` prop. Until S-73-b the grid shows three cards two by two.
- `RecentSalesCard` takes the 30-day figure (`{ units_sold, revenue }`, today's `sales_last_30_days`, unchanged) and shows one
  line under the heading, before the table or list: "38 units sold in the last 30 days · TND 3,382.000" ("1 unit sold …" for one,
  with `unitsWord`). With sales listed but 0 units in the last 30 days: "No sales in the last 30 days.". With no sales at all only
  today's "No sales recorded for this product yet." (+ "Record a sale" when active). Plain text, read after the heading.
- `show.tsx` passes the figure to `RecentSalesCard` instead of `ProductHero`. The deferred props don't change.

**Tests**
- AC-2, one dataset in `recent-sales-card.test.tsx`: 38 units / "3382.0000" → "38 units sold in the last 30 days · €3,382.00";
  1 unit / "89.0000" → "1 unit sold in the last 30 days · €89.00"; older sales only → "No sales in the last 30 days." above the
  list; never sold → neither line, the empty text kept.
- `show.test.tsx`: the Sold-card assertions become one test: no "Sales, last 30 days" region, the line under "Recent sales".
- `product-hero.test.tsx`: its two Sold tests go.
- Golden path, the step after the sale: the line reads "3 units sold in the last 30 days · TND …" (the sale's total).

> [!example]- Files (7)
> - `resources/js/components/products/{product-hero,recent-sales-card}.tsx`
> - `resources/js/pages/products/show.tsx`
> - `resources/js/components/products/{product-hero,recent-sales-card}.test.tsx`
> - `resources/js/pages/products/show.test.tsx`
> - `tests/Browser/GoldenPathTest.php`

#### S-73-b · Three figures in one row, at the board's size
**Commit:** `feat(products): three figures in one row, as on the size board (S-73-b)`

- The row uses the board's own rule: `grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))]` with the 16 px gap, so the three
  cards are equal, wrap to two and one rather than go under 200 px, and stack at 375 px in DOM order (Can produce now, In stock,
  Unit cost). The class is one exported constant in `product-hero.tsx`, used by the hero and by the page skeleton.
- `HeroCard`: label 15 px (`text-[0.9375rem]`, still semibold), `min-w-0`, and its label row `flex-wrap`, so "Update stock" drops
  under "In stock" only when the row is too narrow. `BIG` goes from 32 px to 36 px (`text-[2.25rem]`); "units" stays 18 px.
  "No composition" and "Can't produce" keep their 26 px words (decided 2026-10-24).
- Room at 1280 px (decided 2026-10-24): `ProductPhoto` goes from `lg:max-w-80` (320 px) to
  `lg:max-w-64` (256 px, the board's ~260 px), and the unit cost draws its currency part at the "units" size beside the 36 px
  amount. A new `formatMoneyParts()` in `lib/format.ts` shares one `Intl.NumberFormat` builder with `formatMoney()` and returns
  `formatToParts()` of the same scale-4 string, so the joined text is exactly today's ("TND 35.754" with its no-break space; no
  `Number()`). A `<wbr>` after the space lets a large cost ("TND 1,234.567") put its amount under the code instead of overflowing.
- The skeleton shows three placeholders in that row (Can produce now, In stock, Unit cost); "Recent sales" keeps its own.
  "Couldn't load the stock." + "Try again" don't change.

**Tests**
- AC-1, AC-3, `show.test.tsx` (replaces the "two by two" test): the row's children are exactly the regions "Production capacity",
  "Finished stock", "Unit cost", in that order, and carry the row class; the shoe's chips keep `flex-wrap`.
- AC-3, `show.test.tsx` (the skeleton test): "Loading Can produce now…", "Loading In stock…", "Loading Unit cost…" and
  "Loading recent sales…", and no fourth figure placeholder.
- AC-1, `format.test.ts`: one `formatMoneyParts` dataset (TND, EUR, JPY, a negative, null → em dash): the parts joined equal
  `formatMoney()`. `product-hero.test.tsx`: the unit cost region still reads "€35.75" as one text.
- AC-1, AC-3, golden path, a new step after "Update stock" (chips S 3 · M 4 · L 5 · XL 2): at 1280 × 900 the three cards share
  one top, are equal and at least 200 px wide, each figure is 36 px, each label 15 px, "Update stock" sits on the label's row; at
  375 × 812 they stack in order and `scrollWidth` doesn't pass the window; then back to the default size.

> [!example]- Files (8)
> - `resources/js/components/products/{product-hero,product-photo}.tsx`
> - `resources/js/lib/format.ts`
> - `resources/js/pages/products/show.tsx`
> - `resources/js/components/products/product-hero.test.tsx`
> - `resources/js/lib/format.test.ts`
> - `resources/js/pages/products/show.test.tsx`
> - `tests/Browser/GoldenPathTest.php`

#### S-73-c · The form's size tags as on the board
**Commit:** `feat(products): the form's size tags as on the size board (S-73-c)`

- In `SizesLine` only, the tags' `Badge` gets a `className`: 13 px semibold (`text-[0.8125rem] font-semibold`), `bg-card`, a
  border from the tokens a step stronger than `--border` (`border-input/50`, no new colour, also in dark mode), `rounded-md` and
  `px-2.5 py-1`. `components/ui/badge.tsx` is generated code and isn't touched, so the header's "Apparel", "Sizes S, M, L, XL" and
  "Active" badges keep their look.
- The words, the spoken "Sizes: S, M, L, XL" + "Sizes come with the category.", "**36 to 46**", "**One size**" and the
  category error don't change. Add product uses the same line, so its tags change too (decided
  2026-10-24).

**Tests**
- AC-4, `product-form.test.tsx`: the Apparel row of the existing sizes dataset also checks that the four tags carry the board's
  classes and aren't focusable; the Footwear, Bag and spoken-text rows stay as they are.
- AC-4, `product-header.test.tsx` runs unchanged (the header badges).
- Golden path, the Add product step: after choosing Apparel, a tag is 13 px and weight 600.

> [!example]- Files (3)
> - `resources/js/components/products/product-form.tsx`
> - `resources/js/components/products/product-form.test.tsx`
> - `tests/Browser/GoldenPathTest.php`

### Decisions
- **The board's grid rule, as is.** `repeat(auto-fit, minmax(200px, 1fr))` is the board's own CSS, so the cards wrap the way the
  board does: two, then the third under the first, at the same width.
- **Who uses the 36 px figure style.** `HeroCard`, `BIG` and `UNITS` are used only by `product-hero.tsx` and
  `finished-stock-figure.tsx`: the three cards of the product page. The dashboard's key figures (`StatCard`, 28 px), the material
  page and the welcome page have their own styles and don't change.
- **Why the row needs room.** Measured in Instrument Sans: at 36 px bold "TND 35.754" is 193 px wide, "1,000,000" 164 px. At
  1280 px with the sidebar open the column beside the 320 px photo is ~610–625 px, so three cards are ~192–197 px (~157 px inside):
  under the 200 px floor, and the unit cost wouldn't fit even at today's 32 px (172 px). With the 256 px photo the cards are
  ~213–218 px (~173–178 px inside); "TND" at 18 px beside a 36 px amount is 155 px wide and fits. The board's own card is 214 px
  (170 px inside), so its 36 px "TND 35.754" doesn't fit either.
- **Decided by the human on 2026-10-24.** The photo goes to 256 px on wide screens and the unit cost's currency code is drawn
  at the size of "units" beside a 36 px amount (chosen over shrinking the whole figure to fit, or keeping the 320 px photo);
  "No composition" and "Can't produce" keep 26 px; Add product's size tags change with Edit product's.
- **No contract change.** `sales_last_30_days` moves from one component to another; `domain.ts`, routes and the server don't change.
- **S-56-b's note** "the grid stays two by two" is superseded by this card.

### Order
One frontend developer, in order: S-73-a → S-73-b → S-73-c (one wave; S-73-c touches other files and could run beside S-73-b,
but the golden path file is shared, so they run one after the other).

### Risks
- The room at 1280 px is tight (~213–218 px cards against the 200 px floor) → the golden path's 1280 px check fails if the cards
  wrap or a figure isn't 36 px.
- A unit cost of TND 10,000 or more is wider than a 200 px card even with the amount under the code → it may run into the card's
  padding; unit costs that high aren't in the demo, and nothing scrolls sideways (`min-w-0` on the cards).
- Jsdom can't see layout → Vitest checks order, names and classes only; the tech-lead runs `composer test:browser` at review, and
  the designer checks light and dark mode in the acceptance review.
- S-71 is built first, on its own branch → S-73's worktree is cut from `main` after S-71 merges, and the file lists are checked
  again at dispatch in case S-71 touched a shared component.

### Progress
- 2026-10-24 · S-73-a approved (round 1) · S-73-b approved (round 2: a debug print removed from the golden path) · S-73-c approved
  (round 1). All gates green on each step; golden path green; the three cards measure 218.7 px each at 1280 px.
