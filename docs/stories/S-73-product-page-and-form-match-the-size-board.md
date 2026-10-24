---
id: S-73
title: The product page and form match the size board
type: feature
phase: 5
priority: Should
size: S
status: Ready
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
- [ ] **AC-1 · Three figures in one row.** On the Denim Jacket's page on a wide screen (1280 px), next to the photo, one row of three
  equal cards in this order: "Can produce now 112 units" (blue, "from current stock, any mix of sizes"), "In stock 10 units" (with
  "Update stock" at the top right and the chips S 3 · M 0 · L 5 · XL 2) and "Unit cost TND 35.754" ("same for every size"). Each
  figure is 36 px bold with a 15 px label, as on the board. Under the row come the bottleneck banner and then "Cost breakdown", as
  today. "No composition" and "Can't produce" keep their words. Screen readers meet the three cards in the same order, under the
  names they have today.
- [ ] **AC-2 · The sales figure moves into "Recent sales".** The product page has no "Sold · last 30 days" card. Under the "Recent
  sales" heading it reads "38 units sold in the last 30 days · TND 3,382.000", or "1 unit sold in the last 30 days · TND 89.000" for
  one unit. These are the same two figures the card shows today. A product with older sales but none in the last 30 days reads "No
  sales in the last 30 days." above its list. A product that was never sold keeps only "No sales recorded for this product yet." and,
  when it is active, "Record a sale", as today.
- [ ] **AC-3 · Narrower screens and loading.** From tablet width the three cards stay in one row while each can be at least 200 px
  wide. When there is less room, they wrap (two, then one under them) instead of getting narrower than that. At 375 px they stack in
  the same order: Can produce now, In stock, Unit cost. "Update stock" stays on the "In stock" label row. If the row is too narrow, it
  may drop under the label but never covers "1,000,000 units". A shoe's 11 chips wrap inside the "In stock" card, and nothing scrolls
  sideways. While the page loads, three placeholders fill the row (Can produce now, In stock, Unit cost), not four, and "Recent sales"
  keeps its placeholder. If the stock can't be read, the "In stock" card shows "Couldn't load the stock." and "Try again", as today.
- [ ] **AC-4 · Size tags in the form, as on the board.** On "Edit product" for the Denim Jacket, under Category, "Sizes:" is followed
  by [S] [M] [L] [XL] drawn as on the board: 13 px semibold text on the card's background (white in light mode), a clearly visible grey
  border darker than the lines between rows, slightly rounded corners and a little more room inside. They look the same way in dark
  mode. Only the form's tags change: the "Apparel", "Sizes S, M, L, XL" and "Active" badges on the product page keep their look.
  Footwear still reads "Sizes: **36 to 46**" and Bag "**One size**". Screen readers still hear "Sizes: S, M, L, XL", then "Sizes come
  with the category.". The tags still can't be clicked.
