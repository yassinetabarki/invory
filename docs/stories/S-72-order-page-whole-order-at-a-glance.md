---
id: S-72
title: The order page shows the whole order at a glance, like the design
type: feature
phase: 5
priority: Could
size: S
status: Draft
created: 2026-10-24
tags:
  - story
  - phase-5
---

# S-72 · The order page shows the whole order at a glance, like the design

> [!abstract] Description
> As a **workshop owner**, I want **the order page to show the order's totals per stage, each line's progress and each product's
> photo, and on a phone each size as a small grid**, so that **I see how far an order has got without adding up the lines myself**.
> These are the parts of the design boards "Order detail" and "Order detail per size" that S-49, S-61 and S-71 didn't bring. No figure,
> rule, window or message changes. Each total is the sum of the lines, as on the Orders list. The boards' "⋯" menu on each line and
> their light blue tint on "In cutting" figures are left out: "Move back" stays in the table, as S-71 placed it.

> [!info] How it works today
> - **Header:** Boutique Lina's page shows "Ordered 15 Oct 2026 · 2 products · 80 units", the status and "0 of 80 shipped". It has no
>   totals per stage, so to know that 66 units are still to start you add up the lines yourself.
> - **Line heading:** each line reads "Denim Jacket · PRD-JKT-001 · 50 units" with "0 of 50 shipped" beside its status. It has no photo,
>   and the SKU is as dark as the name.
> - **Phone:** below 1024 px, each size is one wrapping line, "S · To start 6 · In cutting 4 · Ready to ship 0 · Shipped 0". From one
>   size to the next, the figures don't line up.

## Acceptance criteria
- [ ] **AC-1 · The order's five figures.** Under the header of Boutique Lina's order (Denim Jacket at 36 · 14 · 0 · 0, Denim Jeans
  5-pocket at 30 · 0 · 0 · 0), five figures read, in this order: "Ordered 80 units", "To start 66 units", "In cutting 14 units", "Ready to
  ship 0 units" and "Shipped 0 units". The numbers are in the number font. At 375 px they go two per row. After any move they show
  the new totals. The header line and the status stay as today.
- [ ] **AC-2 · Each line's progress.** Under Denim Jacket's table, a thin bar shows its units in four parts: Shipped, Ready to ship, In
  cutting and To start. Under the bar, a legend reads "In cutting 14 · To start 36 · 0 of 50 shipped". A stage at 0 isn't named, and
  "0 of 50 shipped" is always last. "0 of 50 shipped" moves from beside the line's status into this legend, so it shows only once. The colours are never the
  only way to read it: the legend's words say the same, and screen readers hear them.
- [ ] **AC-3 · The product's photo and a grey SKU.** Each line heading starts with the product's photo in a small square, as on the
  Products list. A product with no photo shows a grey tile with an image icon. The SKU "PRD-JKT-001" is in the number font and the
  same grey as the column headings.
- [ ] **AC-4 · Sizes on a phone.** Below 1024 px, the Total comes first, with its buttons in its four boxes as S-71 left them. Then each
  size is a box with its label in semibold, as in the table, and its four figures in a grid of two by two, each with its label: "To start 6", "In cutting 4",
  "Ready to ship 0" and "Shipped 0". The figures line up from one size to the next, in the number font. A 0 is grey, as in the table. A
  shoe's 11 sizes are 11 boxes, with no sideways scrolling at 375 px.
- [ ] **AC-5 · Figures in the number font everywhere on the page.** The figures in the boxes use the number font, like the table and
  the five figures: the four boxes of a one-size line ("To start 8 units") and, below 1024 px, the four boxes of a line's Total. A 0 in
  a box ("Ready to ship 0 units") is in the same grey as the column headings, as in the table.
