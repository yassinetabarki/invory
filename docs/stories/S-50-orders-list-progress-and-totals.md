---
id: S-50
title: See on the Orders list how far each order has got, and what is being made
type: feature
phase: 5
priority: Must
size: S
status: Ready
created: 2026-10-15
tags:
  - story
  - phase-5
---

# S-50 · See on the Orders list how far each order has got, and what is being made

> [!abstract] Description
> As a **workshop owner**, I want **the Orders list to show each order's stages and status, with totals for all open orders at the top**,
> so that **I see at a glance how many units are in cutting and ready to ship, and which orders still need work, without opening each
> one**. An order's figures are the sums of its product lines, and an order is open until every line has shipped.

> [!info] How it works today
> Since S-48 the Orders list shows, for each order, "Order date", "Client", "Products", "Units" and "Note", newest first, 25 a page, with a
> search ("No orders match your search" when nothing matches). It doesn't say how far an order has got. With S-49 the four stages (To
> start, In cutting, Ready to ship, Shipped) are on each order's own page, line by line, so knowing how many units are in cutting across all
> orders means opening every order and adding the numbers up by hand.

## Acceptance criteria
- [ ] **AC-1 · Stages on every row.** Given three orders:
  - Boutique Lina, ordered 15 Oct 2026: Denim Jacket 50 (To start 20, In cutting 20, Ready to ship 10, Shipped 0) and Denim Jeans
    5-pocket 30 (nothing moved yet);
  - Concept Store Nour, ordered 14 Oct 2026: Classic White T-shirt 120 (nothing moved yet);
  - Hotel Dar Said, ordered 2 Oct 2026: Leather Tote 12 (all 12 shipped),

  the list's columns are "Order date", "Client", "Products", "Ordered", "To start", "In cutting", "Ready to ship", "Shipped" and
  "Status", and Boutique Lina's row reads 15 Oct 2026 · Boutique Lina · "50 × Denim Jacket" and "30 × Denim Jeans 5-pocket" · 80 · 50 ·
  20 · 10 · 0 · "In progress". The "Units" and "Note" columns give way to "Ordered" and the four stages: the client's note now shows
  under the client's name, cut short with "…". The client's name still opens the order's page.
- [ ] **AC-2 · Status, never by colour alone.** Each status is a badge with its word and an icon: "Not started" (Concept Store Nour), "In
  progress" (Boutique Lina) and "Shipped" (Hotel Dar Said). It reads the same in dark mode.
- [ ] **AC-3 · Open orders first.** A "Status" filter offers "Open orders" (chosen when the page opens), "Shipped" and "All orders".
  "Open orders" lists Boutique Lina, then Concept Store Nour; "Shipped" lists only Hotel Dar Said; "All orders" lists all three, newest
  first. The filter works together with the search. When nothing matches, the list shows "No orders match your filters" and a "Clear
  filters" button. When every order has shipped, "Open orders" shows "No open orders", "Every order has shipped." and a "Show all orders"
  button. With no orders at all, the list still shows "No orders yet" and "New order", as today.
- [ ] **AC-4 · Totals for all open orders.** Above the list, four figures: "Open orders 2", "To start 170 units", "In cutting 20 units" and
  "Ready to ship 10 units". They count every line of every open order in your workshop; the search and the Status filter never change
  them. After "Mark ready" with 5 on Boutique Lina's Denim Jacket line, the list shows "In cutting 15 units" and "Ready to ship 15 units".
  With no open orders, they read "Open orders 0" and "0 units".
- [ ] **AC-5 · Phone width.** At 375 px each order is a card: "Boutique Lina" and its status badge, "50 × Denim Jacket" and "30 × Denim
  Jeans 5-pocket", then "To start 50 · In cutting 20 · Ready to ship 10 · Shipped 0", the date and the note. The four totals sit in two rows
  of two.
