---
id: S-53
title: Starting to cut an order takes its materials out of stock
type: feature
phase: 5
priority: Must
size: M
status: Ready
created: 2026-10-15
tags:
  - story
  - phase-5
---

# S-53 · Starting to cut an order takes its materials out of stock

> [!abstract] Description
> As a **workshop owner**, I want **"Start cutting" on an order to take the materials of those units out of stock**, so that **the stock on
> hand, "Can produce" and the restock list always match what is really left on the shelves, without correcting each material by hand**.
> The materials taken are the product's composition, waste included, times the units cut. You see them before you confirm, and you can't
> cut more than the stock allows. Moving units back to To start puts the same materials back. Later stages (ready to ship, shipped) never
> touch stock, recording a sale still never changes stock, and restocking and editing stock work as today. This replaces the earlier rule
> that making products never reduces stock (decision Q9, changed on 2026-10-15, D-10). Low-stock emails or notifications are not part of
> it.

> [!info] How it works today
> Stock only changes when you restock a material or edit its quantity. Making products never reduces it (decision Q9), and recording an
> order (S-48) changes nothing either. So after cutting 30 Denim Jackets on the floor, Denim 12oz Indigo still shows "In stock 250 m",
> Denim Jacket still shows "Can produce now 112", and the dashboard's Inventory value still reads TND 10,544.300, until you correct four
> materials by hand.

## Acceptance criteria
- [ ] **AC-1 · See what will be taken before you confirm.** Given the demo stock (Denim 12oz Indigo 250 m, Metal shank button 17mm 900 pc,
  Polyester thread 20,000 m, Woven brand label 400 pc) and Boutique Lina's Denim Jacket line with To start 50, when you type 30 in "Start
  cutting · Denim Jacket", the window shows "Materials taken out of stock" with the columns "Material", "Needed", "In stock" and "Left
  after":
  - Denim 12oz Indigo · 59.4 m · 250 m · 190.6 m
  - Metal shank button 17mm · 240 pc · 900 pc · 660 pc
  - Polyester thread · 3,780 m · 20,000 m · 16,220 m
  - Woven brand label · 30 pc · 400 pc · 370 pc

  with the line "Waste included. Stock on hand allows 112 units." The table follows the number you type. Nothing changes until you click
  "Move units". A product with no composition can still be cut, and the window says "Denim Jacket has no composition, so no material is
  taken out of stock." A product archived since the order was taken can still be cut. A line whose product was deleted can't start
  cutting: "Start cutting" can't be clicked, with the hint "This product was deleted, so no more units can be cut."; its units already
  cut still move on and back.
- [ ] **AC-2 · Confirming takes them out.** Clicking "Move units" shows "30 units of Denim Jacket moved to In cutting. Materials taken out
  of stock." Then Denim 12oz Indigo shows "In stock 190.6 m", Denim Jacket shows "Can produce now 82" and "Bottleneck: Metal shank button
  17mm.", and the dashboard's Inventory value reads TND 9,936.680. Unit costs don't change.
- [ ] **AC-3 · You can't cut more than the stock allows.** Given Metal shank button 17mm at 100 pc (Denim Jacket can produce 12), "Start
  cutting" with 20 marks the button's row "Short by 60 pc" (needed 160 pc, in stock 100 pc) with a warning icon and those words, and
  shows "Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short." "Move units" then changes
  nothing. Cutting 12 works and leaves 4 pc. If someone else used the stock in the meantime, the check uses the stock saved now. Stock never
  goes below 0; amounts are exact, to 4 decimal places like all stock.
- [ ] **AC-4 · Each material's history says why its stock fell.** After cutting 30, each of the four materials' history shows a new entry
  with the badge "Cutting", the text "−59.4 m · Boutique Lina · 30 × Denim Jacket" (the material's own quantity and unit), and "by" the
  person who clicked. The entry is dated the day you click "Move units"; there is no date to choose. The history's description reads
  "Restocks, cutting, stock corrections and unit price changes, newest first."
- [ ] **AC-5 · Moving back to To start returns what was taken.** With Denim Jacket at To start 20 · In cutting 20, "Move back to To start ·
  Denim Jacket" with 5 lists "Materials returned to stock": 9.9 m, 40 pc, 630 m and 5 pc. Confirming shows "5 units of Denim Jacket moved
  back to To start. Materials returned to stock." Denim 12oz Indigo then shows "In stock 200.5 m", Denim Jacket shows "Can produce now 87",
  and each history shows "Returned", "+9.9 m · Boutique Lina · 5 × Denim Jacket", dated that day.
  - What comes back is what was taken for those units, even if the composition changed since: if the jacket now needs 10 buttons instead
    of 8, moving back 5 returns 40 pc, not 50.
  - If units were cut on different days with different compositions, the units cut most recently go back first.
  - A material deleted since then gets nothing back; the window lists it with "Deleted, not returned".
- [ ] **AC-6 · Nothing else touches stock.** "Mark ready", "Ship" and their "Move back", editing an order's quantities, and recording a
  sale change no stock. Deleting an order returns nothing: for an order with units already cut, "Delete order?" adds "Materials already
  taken out of stock for 30 units are not returned. To get them back, move those units back to To start first."
- [ ] **AC-7 · "Can produce" and the restock warnings use what is left.** "Can produce now", the bottleneck, "what you can make with it" and
  the "Needs restock" badges and panel all use the stock left on the shelves, so they fall when cutting starts. Units still to start on
  open orders are not set aside: with Boutique Lina's 20 Denim Jackets still to start, Denim Jacket shows "Can produce now 82", not 62.
  When cutting 80 Denim Jackets takes Denim 12oz Indigo (Reorder at 100 m) from 250 m to 91.6 m, it shows "Low stock" and the dashboard's
  "Needs restock" goes from 4 to 5. No email or notification is sent.
