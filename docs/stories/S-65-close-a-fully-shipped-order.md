---
id: S-65
title: Close a fully shipped order
type: feature
phase: 5
priority: Should
size: M
status: Ready
created: 2026-10-22
tags:
  - story
  - phase-5
---

# S-65 · Close a fully shipped order

> [!abstract] Description
> As a **workshop owner**, I want **to check an order that has shipped in full and confirm it is finished**, so that **it leaves my open
> work and stays as a record that nobody changes or deletes by accident**.
> When the last unit ships, the order waits as "All shipped · ready to close" on a page that shows everything. The only thing you can
> still do there is move shipped units back, to fix a wrong shipment. It waits until you click "Confirm finished · Close order", and
> then reads "Shipped" for good. The list of shipments with their dates and the order's timeline are S-66; the materials the order used
> are S-67.

> [!info] How it works today
> When the last unit of an order ships, the order reads "Shipped" at once: it leaves "Open orders" and its count on the Orders list, and
> shows under "Shipped". Its page still offers "Edit", "Delete" and "Move back" on the shipped units. Raising a quantity in "Edit order"
> makes the order "In progress" again, and "Delete" removes it after "Delete order?". So a finished order can change or disappear at any
> time, and Invory never asks whether it is really finished. Invory remembers who recorded each order, but not the day units were marked
> ready or shipped.

## Acceptance criteria
- [ ] **AC-1 · Ready to close on the last shipment.** Boutique Lina's order (ordered 15 Oct 2026) has Denim Jacket S 10 · M 20 · L 15 ·
  XL 5 (50 units, all shipped) and Denim Jeans 5-pocket S 5 · M 10 · L 10 · XL 5 (30 units, S 5 and M 10 shipped, L 10 and XL 5 ready to
  ship), so the page reads "65 of 80 shipped". "Ship" on Denim Jeans 5-pocket with L 10 and XL 5 shows "15 units of Denim Jeans 5-pocket
  moved to Shipped.", and the order's status becomes "All shipped · ready to close", as a word with an icon, with "80 of 80 shipped".
  Both lines read "Shipped". No material stock changes and no sale is recorded, as today.
- [ ] **AC-2 · Everything shows, only shipped units move back.** An order ready to close has no "Edit", "Delete", "Cancel order", "Start
  cutting", "Mark ready" or "Ship", and no field to type in: the only button left on each line is "Move back" under Shipped. It shows
  "Ordered 80 units" and "Shipped 80 units"; under "Order details", "Client Boutique Lina", "Order date 15 Oct 2026", "Note Deliver
  before 30 Oct, in 2 boxes" and "Recorded by Admin"; and for each product a table with the columns "Size", "Ordered" and "Shipped":
  Denim Jacket S 10 · 10, M 20 · 20, L 15 · 15, XL 5 · 5 and Total 50 · 50; Denim Jeans 5-pocket S 5 · 5, M 10 · 10, L 10 · 10, XL 5 · 5
  and Total 30 · 30. A "One size" line has one row ("One size 12 · 12"). Without a note there is no "Note"; if the account that
  recorded the order was deleted, there is no "Recorded by". Any other change that reaches Invory (from a page opened earlier) is
  refused: nothing changes, the page refreshes and shows "Boutique Lina has shipped in full, so it can't be changed."
- [ ] **AC-3 · Fix a wrong shipment before closing.** On the order ready to close, "Move back to Ready to ship · Denim Jeans 5-pocket"
  with L 10 shows "10 units of Denim Jeans 5-pocket moved back to Ready to ship." The order is "In progress" again, with "70 of 80
  shipped", the close buttons are gone, and every button of an order in progress is back ("Edit", "Delete", "Cancel order" and the
  moves, with their usual rules). Shipping those 10 again makes it "All shipped · ready to close" once more.
- [ ] **AC-4 · Confirm finished · Close order.** The order ready to close shows "Confirm finished · Close order" at the top, and at the
  bottom a box "Is this order finished?" with "Once you close it, the order moves to Shipped and can't be reopened." and the buttons
  "Confirm finished · Close order" and "Not yet". Either "Confirm finished · Close order" closes the order at once, with no further
  window, as Shipped, and opens the Orders list with "Boutique Lina closed as Shipped." and a "View order" link to the order's page. "Not
  yet" closes nothing and takes you back to the Orders list. Only an order ready to close offers these buttons: never one not started, in
  progress, cancelled or already closed. If someone else closed it meanwhile, you see "Boutique Lina is already closed." and the closed
  order's page; if someone moved units back meanwhile, nothing is closed and you see "Boutique Lina isn't fully shipped any more, so it
  can't be closed." with the refreshed page. Closing changes no stock, no sale and no figure of the order.
- [ ] **AC-5 · A closed order stays closed.** Opening Boutique Lina's order again shows the same page with "Shipped · closed 30 Oct 2026
  by Admin" in place of "All shipped · ready to close" (the day you clicked, and who clicked), and neither "Confirm finished · Close
  order" nor the "Is this order finished?" box. "Move back" is gone too. Nothing on the page or the Orders list reopens, edits, moves,
  deletes or cancels it, and a change that reaches Invory anyway is refused with "Boutique Lina is closed, so it can't be changed." If
  Admin's account is deleted later, the line reads "Shipped · closed 30 Oct 2026".
- [ ] **AC-6 · The Orders list.** With Boutique Lina ready to close and Concept Store Nour (Classic White T-shirt 120, nothing moved),
  the totals read "Open orders 2", "To start 120 units", "In cutting 0 units" and "Ready to ship 0 units", and Boutique Lina's row reads
  "Ready to close", as a word with an icon, with no "Edit" or "Delete" button. The "Status" filter offers "Open orders", "Ready to
  close", "Shipped", "Cancelled" and "All orders"; "Ready to close" lists Boutique Lina. After closing, the totals read "Open orders 1"
  and the same three unit totals; "Open orders" lists only Concept Store Nour; "Ready to close" shows "No orders match your filters", as
  today; "Shipped" lists Boutique Lina, reading "Shipped", with no "Edit" or "Delete" button. "All orders" lists both.
- [ ] **AC-7 · Orders shipped in full before this change.** Hotel Dar Said's order (Leather Tote 12, all 12 shipped before this
  change) reads "All shipped · ready to close" like any other: it shows under "Open orders" and "Ready to close" and counts in "Open
  orders" until you close it. Closed on 30 Oct 2026, it reads "Shipped · closed 30 Oct 2026 by Admin".
