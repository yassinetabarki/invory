---
id: S-61
title: Follow each size through cutting, ready to ship and shipped
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

# S-61 · Follow each size through cutting, ready to ship and shipped

> [!abstract] Description
> As a **workshop owner**, I want **to see and move the units of each size through To start, In cutting, Ready to ship and Shipped**, so
> that **I know, for example, that 4 S and 10 M are being cut while the L and XL haven't started**.
> Each move window asks a number per size, so one cut can take several sizes at once, as on the cutting table. Cutting still takes the
> same materials per unit whatever the size; a bigger size using more fabric is a later story. Ships together with S-60.

> [!info] How it works today
> On an order's page each product line shows four figures: "To start 50 units", "In cutting 0 units", "Ready to ship 0 units" and
> "Shipped 0 units". "Start cutting" opens "Start cutting · Denim Jacket" with one field, "How many units?", and the hint "Up to 50
> units.". Cutting 30 takes 59.4 m of Denim 12oz Indigo, 240 pc of Metal shank button 17mm, 3,780 m of Polyester thread and 30 pc of
> Woven brand label out of stock. Nothing says which sizes those 30 jackets are.

## Acceptance criteria
- [ ] **AC-1 · Stages per size.** Boutique Lina's Denim Jacket line (S 10 · M 20 · L 15 · XL 5) shows a table with a row per size and a
  "Total" row, and the columns "To start", "In cutting", "Ready to ship" and "Shipped". It starts at S 10 · 0 · 0 · 0, M 20 · 0 · 0 · 0,
  L 15 · 0 · 0 · 0, XL 5 · 0 · 0 · 0 and Total 50 · 0 · 0 · 0. The line's four figures and "0 of 50 shipped" are the Total row. A "One size"
  line looks as it does today.
- [ ] **AC-2 · One move, several sizes.** "Start cutting · Denim Jacket" asks "How many units of each size?", with a field per size and
  the hints "Up to 10", "Up to 20", "Up to 15" and "Up to 5". With S 4 and M 10, "Move units" shows "14 units of Denim Jacket moved to In
  cutting. Materials taken out of stock." and the table reads S 6 · 4, M 10 · 10, L 15 · 0, XL 5 · 0, Total 36 · 14. "Mark ready", "Ship"
  and each "Move back" work the same way. A size whose stage holds 0 units shows no field. Empty fields move nothing; with every field
  empty the window shows "Enter at least one unit to move." Each size can move at most what its stage holds: M 25 shows "You can move at
  most 20 units of M." and nothing moves.
- [ ] **AC-3 · Cutting takes the same materials for every size.** For S 4 and M 10, "Materials taken out of stock" shows the needs of 14
  jackets: Denim 12oz Indigo 27.72 m, Metal shank button 17mm 112 pc, Polyester thread 1,764 m and Woven brand label 14 pc, as if all 14
  were one size. Each material's history reads "−27.72 m · Boutique Lina · 14 × Denim Jacket (S 4 · M 10)". The short-stock check
  counts every size together: with Metal shank button 17mm at 100 pc, S 10 and M 10 is refused with "Not enough stock to cut 20 units.
  You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer."
- [ ] **AC-4 · Moving back returns what was taken.** "Move back to To start · Denim Jacket" with M 5 shows "5 units of Denim Jacket moved
  back to To start. Materials returned to stock." and returns 9.9 m, 40 pc, 630 m and 5 pc, the latest cut first, as today. M then
  reads To start 15 · In cutting 5.
- [ ] **AC-5 · Editing an order that has started, per size.** In "Edit order", with 10 M in cutting, ready to ship or shipped, M 8 shows
  "M can't be less than the 10 units already in cutting, ready to ship or shipped." and nothing is saved. Sizes with no unit started
  change freely. The line still can't be removed while any of its units has started ("Denim Jacket can't be removed: 14 units have
  started.").
- [ ] **AC-6 · Statuses and totals add the sizes.** A line is "Not started", "In progress" or "Shipped" from its Total row, and the order
  from its lines, as today. The Orders list's figures and its totals ("In cutting 14 units") add every size together.
- [ ] **AC-7 · Phone width.** At 375 px each size becomes its own row of four labelled figures ("S · To start 6 · In cutting 4 · Ready to
  ship 0 · Shipped 0"), with the Total first, and the move window's fields wrap like the order form's.
