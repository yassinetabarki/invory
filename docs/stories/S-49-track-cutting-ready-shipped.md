---
id: S-49
title: Track each product of an order through cutting, ready to ship and shipped
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

# S-49 · Track each product of an order through cutting, ready to ship and shipped

> [!abstract] Description
> As a **workshop owner**, I want **to record, for each product of an order, how many units have gone into cutting, how many are ready to
> ship and how many have shipped**, so that **I know how far every order has got and how much is still to make**.
> Each order gets its own page. Every product line has four counts that always add up to its quantity, and you update them as the work
> moves on the floor. The order's status follows its lines. Taking the materials out of stock when cutting starts is S-53. Totals across
> all orders, on the Orders list, are S-50.

> [!info] How it works today
> Since S-48 you can record a client's order, but not follow it. The Orders list shows Boutique Lina's order as "15 Oct 2026 · Boutique
> Lina · 50 × Denim Jacket, 30 × Denim Jeans 5-pocket · 80" with an Edit and a Delete button, and saving the order brings you back to that
> list with "Order recorded.". An order has no page of its own, and nothing says how many jackets are being cut, are ready or have left:
> that is known only on the workshop floor. Denim Jacket's page shows "Can produce now 112": what the stock allows, not what is being made.

## Acceptance criteria
- [ ] **AC-1 · The order page.** Clicking "Boutique Lina" on the Orders list opens the order's page, and so does saving the order ("Order
  recorded." or "Order updated."). It shows the heading "Boutique Lina", under it "Ordered 15 Oct 2026 · 2 products · 80 units", the
  status "Not started", the line "0 of 80 shipped", the client's note under "Note", and the buttons "Edit" and "Delete". Under "Products",
  each line has its own block: "Denim Jacket · PRD-JKT-001 · 50 units" with four figures in this order, "To start 50 units", "In cutting 0
  units", "Ready to ship 0 units" and "Shipped 0 units", and the line "0 of 50 shipped". The same goes for "Denim Jeans 5-pocket · 30
  units". A line's four figures always add up to its quantity; "To start" is whatever has not moved yet.
- [ ] **AC-2 · Move units on, line by line.** On each line, "Start cutting" (under To start), "Mark ready" (under In cutting) and "Ship"
  (under Ready to ship) open a small window titled with the action and the product ("Start cutting · Denim Jacket"), asking "How many
  units?" with the hint "Up to N units." (N is what the stage holds) and the buttons "Move units" and "Cancel". On Boutique Lina's order:
  - "Start cutting" on Denim Jacket with 30 shows "30 units of Denim Jacket moved to In cutting." Denim Jacket reads To start 20 · In
    cutting 30 · Ready to ship 0 · Shipped 0, Denim Jeans 5-pocket still reads 30 · 0 · 0 · 0, and the order's status becomes "In
    progress".
  - Then "Mark ready" on Denim Jacket with 10 shows "10 units of Denim Jacket moved to Ready to ship." and Denim Jacket reads 20 · 20 · 10
    · 0.
  - A button whose stage holds 0 units can't be clicked (here "Ship" on Denim Jeans 5-pocket). One unit reads "1 unit of … moved to …".
- [ ] **AC-3 · Ship, and the statuses.** From 20 · 20 · 10 · 0, "Ship" on Denim Jacket with 10 shows "10 units of Denim Jacket moved to
  Shipped.", the line reads 20 · 20 · 0 · 10 and "10 of 50 shipped", and the order reads "10 of 80 shipped". Each line has its own status
  ("Not started", "In progress" or "Shipped"), shown as a word with an icon, never by colour alone. The order is "Not started" while no
  unit of any line has left To start, "Shipped" once every line is fully shipped, and "In progress" otherwise. On a fully shipped line,
  "Start cutting", "Mark ready" and "Ship" can't be clicked.
- [ ] **AC-4 · A move can't take more than the stage holds.** With 20 Denim Jackets in cutting, "Mark ready" with 25 shows "You can move at
  most 20 units." and nothing moves. Empty, 0 or 2.5 shows "Enter a whole number of units, at least 1.". If someone else moved units of the
  same line in the meantime, the limit is the count saved now, not the one on your screen, and the figures refresh to it.
- [ ] **AC-5 · Correct a mistake.** In cutting, Ready to ship and Shipped each offer "Move back", which moves units of that line to the stage
  before ("Move back to To start · Denim Jacket"). With To start 20 · In cutting 20, "Move back" on In cutting with 5 shows "5 units of
  Denim Jacket moved back to To start." and the line reads To start 25 · In cutting 15. The same limits and messages as AC-4 apply. A
  shipped line, or order, that gets units moved back is "In progress" again.
- [ ] **AC-6 · Changing an order that has started.** In "Edit order", with 30 Denim Jackets in cutting, ready to ship or shipped (20 · 20 ·
  10 · 0):
  - Denim Jacket's quantity 25 shows "The quantity can't be less than the 30 units already in cutting, ready to ship or shipped." and
    nothing is saved. Quantity 40 is saved, and its To start becomes 10.
  - Denim Jacket's product can't be changed (hint "The product can't change once work on this line has started.") and its line can't be
    removed ("Denim Jacket can't be removed: 30 units have started."). Lines that haven't started, and new lines, can be changed freely.
  - A fully shipped line whose quantity is raised from 50 to 60 shows To start 10 and is "In progress" again.
  - "Delete order?" adds "Its progress (20 in cutting, 10 ready to ship, 0 shipped) is removed too.".
- [ ] **AC-7 · Ready and shipped are counts.** "Mark ready", "Ship" and their "Move back" never change material stock and never record a
  sale: after 10 Denim Jackets have shipped, the Sales page and the dashboard show no new sale.
