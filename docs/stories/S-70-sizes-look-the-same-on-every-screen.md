---
id: S-70
title: Sizes look the same on every screen
type: feature
phase: 5
priority: Should
size: M
status: Ready
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
- [ ] **AC-1 · A size reads as a chip.** Boutique Lina's order page shows "Denim Jacket · PRD-JKT-001 · 50 units", with the SKU in the
  number font. Under it there is one chip per size ordered: "S 10", "M 20", "L 15", "XL 5". Each chip has the size in bold on grey and
  its count beside it in the number font, the same chip as the "In stock" figure. A size with 0 units has no chip. A line recorded
  before sizes shows one chip, "One size 50". Screen readers hear the sizes once, as "S 10, M 20, L 15, XL 5", not as four chips. In
  every chip, on the order page and on the "In stock" figure alike, a shoe size shows its bare label: [36 | 4], not [36: | 4]. Screen
  readers still hear "36: 4".
- [ ] **AC-2 · The product page.** Under "Denim Jacket", in the row "PRD-JKT-001 · Apparel · Sizes S, M, L, XL · Active", the sizes
  are an outlined badge between "Apparel" and "Active", as tall as those two badges. It reads "Sizes 36 to 46" on the Canvas Low
  Sneaker and "One size" on the Leather Tote. "Unit cost TND 35.754" has "same for every size" under it on a product with several
  sizes. The Leather Tote ("One size") keeps "materials, labor and overhead".
- [ ] **AC-3 · The product form names the sizes.** Under Category, choosing Apparel shows "Sizes:" in grey and one outlined tag per
  size: [S] [M] [L] [XL]. A list with more than 6 sizes shows its name in bold instead of tags: "Sizes: **36 to 46**" for Footwear. A
  "One size" category such as Bag shows **One size** in bold. Under it, "Sizes come with the category." stays in small grey text.
  Screen readers hear "Sizes: S, M, L, XL", "Sizes: 36 to 46" or "One size", then the hint. With no category chosen on "Add product",
  nothing shows, as today.
- [ ] **AC-4 · Size fields look the same everywhere.** On "New order", "Edit order", the move window and "Update stock", each size field
  shows its figure centred, with the size's label small and bold above it. Size fields are 44 px high; other fields keep their height.
  A 7-digit figure ("1000000") shows whole. At 375 px four size fields fit on a row, so a shoe's 11 sizes take three rows, without
  scrolling sideways.
- [ ] **AC-5 · A size's message keeps the row.** Typing "2.5" in M and clicking "Save order" marks M in red and keeps S, M, L and XL on
  one row. The message shows under the size fields and names the size: "M: Enter a whole number from 0 to 1,000,000.", or "Size 36:
  Enter a whole number from 0 to 1,000,000." for a shoe. Where the form shows "Total 50 units", the messages come under it. Two sizes
  in error give two lines, in size order. A message that already names its size shows as it is, with no second name: "M can't be less
  than the 10 units already in cutting, ready to ship or shipped.", "You can move at most 20 units of M.". The same holds in the move
  window and "Update stock". When you move to M, a screen reader reads "M: Enter a whole number from 0 to 1,000,000." with it.
- [ ] **AC-6 · A started size shows its minimum.** In "Edit order", with 10 M already in cutting, ready to ship or shipped, the M field
  shows a lock icon and "At least 10" under it before you type anything. Screen readers hear "At least 10" with the M field. Sizes
  with nothing started show no hint. Saving M 8 still shows "M can't be less than the 10 units already in cutting, ready to ship or
  shipped."
- [ ] **AC-7 · The Orders list, and phone width.** In "Products", "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" keeps its words,
  with "50" in the number font and the sizes part in grey. At 375 px the chips wrap onto more lines, a shoe's 11 chips included, and
  the sizes badge wraps with the other badges under the product's name. Nothing scrolls sideways.
