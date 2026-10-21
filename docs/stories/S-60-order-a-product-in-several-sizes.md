---
id: S-60
title: Order a product in several sizes
type: feature
phase: 5
priority: Must
size: M
status: Draft
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-60 · Order a product in several sizes

> [!abstract] Description
> As a **workshop owner**, I want **to enter how many units of each size a client ordered**, so that **the cutting table knows it is S 10,
> M 20, L 15 and XL 5, not just "50 Denim Jackets"**.
> An order still has one line per product. Under it you type a quantity for each size of the product, and the line's quantity is their
> sum. Following each size through cutting, ready to ship and shipped is S-61, which ships together with this story.

> [!info] How it works today
> On "New order" and "Edit order", each product line has a product and a single "Quantity". Boutique Lina's order reads "50 × Denim
> Jacket, 30 × Denim Jeans 5-pocket · 80". How many of the 50 jackets are S or XL goes in the note, if anywhere, and nothing adds those
> numbers up.

## Acceptance criteria
- [ ] **AC-1 · A quantity per size.** On "New order", after choosing Denim Jacket (sizes S, M, L, XL), the line shows one field per size,
  labelled "S", "M", "L" and "XL", in that order, in place of "Quantity", and under them "Total 50 units" once you type S 10, M 20, L 15
  and XL 5. "Save order" shows "Order recorded." A size left empty counts as 0. A "One size" product (Leather Tote) keeps the single
  "Quantity" field, as today.
- [ ] **AC-2 · What a line accepts.** Each size takes a whole number from 0 to 1,000,000; anything else shows "Enter a whole number from 0
  to 1,000,000." under that size. A line whose sizes are all empty or 0 shows "Enter at least one unit of Denim Jacket." The line's total
  can't pass 1,000,000 ("A line can hold up to 1,000,000 units."). The other rules stay: one line per product ("Denim Jacket is already
  on this order."), up to 50 products, and only active products can be added.
- [ ] **AC-3 · The sizes show wherever the order does.** The order's page shows the line "Denim Jacket · PRD-JKT-001 · 50 units" with
  "S 10 · M 20 · L 15 · XL 5" under it. The Orders list shows "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" in "Products". A size with 0
  units is left out: an order of S 10 and XL 5 reads "15 × Denim Jacket (S 10 · XL 5)".
- [ ] **AC-4 · Editing the sizes.** "Edit order" shows each size's saved quantity. Changing M from 20 to 25 and saving shows "Order
  updated." and "55 units" on the line. Limits once work has started on a size are in S-61.
- [ ] **AC-5 · Lines recorded before sizes.** A line recorded before this change keeps its quantity as "One size": on the order's page it
  reads "Denim Jacket · PRD-JKT-001 · 50 units" with "One size 50", and in "Edit order" it keeps the single "Quantity" field with the
  hint "Recorded before sizes." It moves through the stages as today.
- [ ] **AC-6 · Phone width.** At 375 px the size fields wrap onto several rows, each with its label, and "Total 50 units" sits under the
  last row. The 11 shoe sizes (36 to 46) fit without scrolling sideways.
