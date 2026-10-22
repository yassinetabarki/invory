---
id: S-57
title: Cancel an order
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

# S-57 · Cancel an order

> [!abstract] Description
> As a **workshop owner**, I want **to cancel an order the client called off, and put the units already made into stock in their sizes**,
> so that **nothing cut for that client is lost and it waits on the shelf for another client**.
> In your words: "if and order is cancelled and we already start cutting we will continue producing the product and then put them in the
> stock user need confirm that cutting is finished and the product is ready to put in stock". "Delete" stays for orders entered by
> mistake.

> [!info] How it works today
> An order can only be deleted, and "Delete" is refused while units are in cutting or ready to ship: "Boutique Lina can't be deleted: 30
> units are in cutting or ready to ship. Move them back to To start first." A client who cancels after cutting has started leaves you
> two bad choices: keep a "live" order that will never ship, or move the units back to To start, which puts fabric back on the shelf that
> was already cut.

## Acceptance criteria
- [ ] **AC-1 · Cancel order.** Boutique Lina's order has Denim Jacket S 10 · M 20 · L 15 · XL 5 at To start 20 (S 5, M 5, L 10), In
  cutting 20 (S 5, M 10, L 5), Ready to ship 10 (M 5, XL 5) and Shipped 0. Its page offers "Cancel order" next to "Edit" and "Delete".
  It opens "Cancel order?" with "Units to start are dropped. Units ready to ship go into stock now. Units in cutting stay on the order
  until you put them in stock. This can't be undone." and the buttons "Cancel order" and "Keep order". Confirming shows "Order
  cancelled." and the order reads "Cancelled", as a word with a crossed-out circle icon. A fully shipped or already cancelled order has
  no "Cancel order".
- [ ] **AC-2 · What happens to each stage.** After cancelling, the 20 units to start are dropped, the 10 ready to ship go into stock in
  their sizes (Denim Jacket "In stock 10 units", "S 0 · M 5 · L 0 · XL 5", from 0), shipped units stay shipped, and the 20 in cutting stay.
  The line's figures read "Dropped 20 units", "In cutting 20 units", "Put in stock 10 units" and "Shipped 0 units", and they still add
  up to 50. No material goes back into stock and none is taken.
- [ ] **AC-3 · Cutting finished · Put in stock.** On the cancelled order, "Cutting finished · Put in stock" opens a window with a field
  per size ("Up to 5" for S, "Up to 10" for M, "Up to 5" for L). With S 5 and M 10, "Move units" shows "15 units of Denim Jacket put in
  stock." and the product reads "In stock 25 units", "S 5 · M 15 · L 0 · XL 5". Then L 5 brings it to "In stock 30 units". The same
  limits and messages as the other moves apply.
- [ ] **AC-4 · A cancelled order is closed.** On a cancelled order, "Edit", "Start cutting", "Mark ready", "Ship" and every "Move back"
  can't be clicked. "Delete" is refused while units are in cutting: "Boutique Lina can't be deleted: 20 units are in cutting. Put them in
  stock first." Once none are, the order deletes after "Delete order?", as today, and deleting never changes the stock.
- [ ] **AC-5 · Orders list.** The "Status" filter gains "Cancelled". "Open orders" and the totals at the top ("To start", "In cutting",
  "Ready to ship") leave cancelled orders out. Boutique Lina's row reads "Cancelled" with its icon in every theme.
- [ ] **AC-6 · One size.** A "One size" line (Leather Tote 12) behaves the same with one field: cancelling with 4 ready to ship shows the
  Leather Tote "In stock 4 units".
- [ ] **AC-7 · Phone width.** At 375 px "Cancel order?" shows its whole message, with "Cancel order" and "Keep order" under it, without
  scrolling sideways. On the cancelled order each size becomes its own row of four labelled figures, with the Total first ("Total ·
  Dropped 20 · In cutting 20 · Put in stock 10 · Shipped 0", then "S · Dropped 5 · In cutting 5 · Put in stock 0 · Shipped 0"), and
  "Cancelled" keeps its icon next to the word. In "Cutting finished · Put in stock" the size fields wrap onto several rows, each with its
  label, like the order form's.
