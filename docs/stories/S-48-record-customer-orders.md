---
id: S-48
title: Record customer orders with one or more products
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

# S-48 · Record customer orders with one or more products

> [!abstract] Description
> As a **workshop owner**, I want **to record each order a client places: the client, every product they ordered with its quantity, and
> anything they asked for**, so that **I have one list of everything I've promised to make, instead of notes on paper and in messages**.
> One order can hold several products, each on its own line with its own quantity (for example 50 Denim Jackets and 30 Denim Jeans for the
> same boutique). Orders get their own page, "Orders", in the sidebar. Tracking each line's stages comes with S-49, and taking materials out
> of stock when cutting starts comes with S-53. Recording an order never changes material stock, never sets stock aside and never records a
> sale.

> [!info] How it works today
> Nothing yet. Invory has no orders, and the sidebar shows Dashboard, Materials, Products and Sales. The closest thing is "Record sale" on
> the Sales page: one product, a quantity, a unit price, a date and a note with the placeholder "Channel, customer or order number". But a
> sale is something already sold: it counts in the dashboard totals at once, it has no client, and it holds only one product. So today an
> order of 50 Denim Jackets and 30 Denim Jeans for a boutique lives in a notebook or a phone message.

## Acceptance criteria
- [ ] **AC-1 · An "Orders" page.** The sidebar shows "Orders" between "Products" and "Sales". It opens the page "Orders", described as
  "Every order your clients place with you.", with a "New order" button. With no orders yet, the page shows "No orders yet", "Record a
  client's order to keep track of what you've promised to make." and a "New order" button.
- [ ] **AC-2 · Record an order with two products.** "New order" opens the page "New order" with a "Details" section ("Client" with the
  placeholder "e.g. Boutique Lina", "Order date" with today filled in, "Note (optional)" on several lines with the placeholder "Delivery
  date, packing, anything the client asked for") and a "Products" section. "Products" starts with one empty line ("Choose a product…" and
  "Quantity"), and "Add product" adds another. The product picker lists only active products and shows "Denim Jacket · PRD-JKT-001". When
  you enter "Boutique Lina", 15 Oct 2026, "Deliver before 30 Oct, in 2 boxes", Denim Jacket 50 and Denim Jeans 5-pocket 30 and click "Save
  order", then the toast says "Order recorded.", the Orders list opens, and its first row reads 15 Oct 2026 · Boutique Lina · "50 × Denim
  Jacket" and "30 × Denim Jeans 5-pocket" · 80 units · "Deliver before 30 Oct, in 2 boxes". "Cancel" returns to the list and saves nothing.
- [ ] **AC-3 · Lines follow simple rules.** Each line's "Remove" button is named after its product ("Remove Denim Jeans 5-pocket"); on a
  line with no product yet it reads "Remove line". With every line removed, the section reads "No products yet. Add the products the client
  ordered." and saving shows "Add at least one product.". Choosing Denim Jacket on two lines shows "Denim Jacket is already on this order."
  on the second one. An order holds up to 50 lines; at 50, "Add product" is disabled with the hint "An order can hold up to 50 products.".
- [ ] **AC-4 · Clear messages for mistakes.** On any of these, nothing is saved, the page keeps what you typed, and the message shows under
  its field:
  - Client left empty: "The client field is required."; longer than 100 characters: "The client field must not be greater than 100
    characters.".
  - A line with no product: "Choose a product.".
  - A line's quantity empty, 0, 2.5 or more than 1,000,000: "The quantity must be a whole number from 1 to 1,000,000.".
  - Order date left empty: "The order date field is required."; after today: "The order date can't be in the future.".
  - Note longer than 1,000 characters: "The note field must not be greater than 1000 characters.".
- [ ] **AC-5 · The list.** Orders show in a table with the columns "Order date", "Client", "Products" (one "50 × Denim Jacket" line per
  product, in the order you entered them, each name linking to the product's page; an order with five products shows the first three and
  "+ 2 more"), "Units" (the sum of the lines: 80) and "Note" (cut short with "…"), plus an Edit and a Delete button on each row. The newest
  order date comes first; on the same date, the order recorded last comes first. The list shows 25 orders a page, with page buttons like
  the Sales list. The search box "Search client, product or note" finds Boutique Lina's order with "lina", "JEANS" or "PRD-JKT-001"
  (capitals don't matter). When nothing matches, the list shows "No orders match your search" and a "Clear search" button. At phone width
  (375 px) each order is a card: "Boutique Lina", then "50 × Denim Jacket" and "30 × Denim Jeans 5-pocket", the date and the note, with
  "Edit" and "Delete" buttons.
- [ ] **AC-6 · Edit and delete.** "Edit" opens the page "Edit order" with the order's details and lines. Changing Denim Jeans 5-pocket from
  30 to 40, adding Classic White T-shirt 20 and clicking "Save order" shows "Order updated." and the row reads "50 × Denim Jacket", "40 ×
  Denim Jeans 5-pocket" and "20 × Classic White T-shirt", 110 units. On the order as first recorded (80 units), "Delete" asks "Delete
  order?" with "The order of Boutique Lina (2 products, 80 units) will be removed. Material stock and sales are not
  affected. This cannot be undone." and the buttons "Delete order" and "Cancel". "Delete order" shows "Order deleted." and the row is gone;
  "Cancel" keeps it.
- [ ] **AC-7 · Orders stay in your workshop and change nothing else.** Another workshop's account never sees Boutique Lina's order, in its
  list or its search, and anything it sends to change or delete this order is refused as "Not Found". The product picker offers only your
  workshop's products. Recording, editing or deleting an order changes no material stock, no sale and no figure: Denim Jacket still shows
  "Can produce now 112", and the dashboard totals stay the same. If Denim Jacket is later archived, set back to draft or deleted, its order
  lines stay and still show "Denim Jacket" (a deleted product's name has no link), and when you edit the order, that line keeps Denim Jacket.
