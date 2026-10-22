---
id: S-56
title: Finished products in stock, per size
type: feature
phase: 5
priority: Should
size: M
status: Ready
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-56 · Finished products in stock, per size

> [!abstract] Description
> As a **workshop owner**, I want **each product to show how many finished units are on the shelf in each size**, so that **I know what
> I have, for example 3 S, 5 L and 2 XL Denim Jackets, before I cut more**.
> You count and correct this stock yourself; cancelling an order adds to it (S-57). "Can produce now" stays what the materials allow, the
> Inventory value stays materials only, and recording a sale doesn't change it. A new order doesn't take from it either. The products
> list doesn't change.

> [!info] How it works today
> Invory counts materials only. Once a jacket is made it is forgotten: nothing records the finished units waiting on the shelf, and
> units cut for a client who then cancelled have nowhere to go. The Denim Jacket's page shows "Can produce now 112" and nothing about
> finished jackets.

## Acceptance criteria
- [ ] **AC-1 · In stock on the product page.** Next to "Can produce now 112", the Denim Jacket's page shows "In stock 10 units" and under
  it "S 3 · M 0 · L 5 · XL 2". A product with nothing in stock shows "In stock 0 units". A "One size" product shows only "In stock 4
  units".
- [ ] **AC-2 · Update the count.** "Update stock", a button on the "In stock" figure under its sizes, opens "Update stock · Denim
  Jacket" with a field per size holding today's count (S 3, M 0, L 5, XL 2). Changing M to 4 and clicking "Save" shows "Stock
  updated." and the page reads "In stock 14 units" and "S 3 · M 4 · L 5 · XL 2". Each size takes a whole number from 0 to 1,000,000 ("Enter a whole number from 0 to 1,000,000."). "Cancel" changes nothing.
- [ ] **AC-3 · Nothing else changes.** After the update, "Can produce now" (112), the unit cost (TND 35.754), the materials' stock and the
  dashboard's Inventory value read as before. Recording a sale of 2 Denim Jackets leaves "In stock 14 units". A new order of Denim
  Jackets doesn't change it either.
- [ ] **AC-4 · The category stays locked while there is stock.** With 14 Denim Jackets in stock, changing its category to Footwear shows
  "Denim Jacket has units in stock, so its category can't change to one with other sizes." and nothing is saved. At "In stock 0 units"
  the S-58 rule applies alone.
- [ ] **AC-5 · States.** If the stock can't be loaded, the figure shows "Couldn't load the stock." with "Try again". If saving fails, the
  window stays open with what you typed and shows "Couldn't save the stock. Try again."
- [ ] **AC-6 · Phone width.** At 375 px "In stock 10 units" sits right under "Can produce now", with "S 3 · M 0 · L 5 · XL 2" under it
  and "Update stock" below. A shoe's 11 sizes (36 to 46) wrap onto several lines without scrolling sideways. In "Update stock · Denim
  Jacket" the size fields wrap onto several rows, each with its label, and "Save" and "Cancel" sit under the last row.
