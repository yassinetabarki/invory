# ADR-0025 — Order lines per colour and size

- **Status:** Accepted · 2026-11-03 (approved by the human with the Technical work of INV-13; tech-lead role held by the main session)
- **Amends:** ADR-0016 (order lines count by size). **Builds on:** ADR-0019 (finished stock per colour and size), ADR-0009 rule 5
  (small session-authenticated JSON routes), ADR-0013 (every record belongs to a workshop)

## Context
INV-13 lets the owner search a product while taking an order, see its shelf stock per colour and size, and order it in the exact
colours and sizes the client wants. Until now an order line held a quantity per size only (ADR-0016). The cutting on the order page
stays until INV-14 removes it, so the stage counts must follow the colour too.

## Decision
1. **One `order_line_sizes` row per colour and size.** `colour_id` (FK `colours`, restrict, NOT NULL) joins the row's key:
   `unique(order_line_id, colour_id, size_id)`. The line's quantity and three counts stay the sums of its rows.
2. **Rows recorded before INV-13 take their product's first colour** (position, name, id), as the stock rows did in INV-2
   (ADR-0019). A product with lines and no colour is first linked to its workshop's first colour. No unit or count moves. `down()`
   adds the colours of a size back into one row.
3. **The order pages read the colour through subselects** (code, name, swatch), like the size label, so no page gains a query.
4. **The order forms stop receiving every product.** A JSON route (`orders.products.search`) answers a search with at most 6 active
   products and their shelf stock per colour and size; Edit order receives the same data for the products already on it. No
   costing is run: the search shows only what is on the shelf (the human's answer, 2026-11-03).
5. **A colour that open orders hold can't be taken off its product** (`ProductColourLock`, after the shelf and production refusals):
   units ordered and not shipped yet.
6. **The cutting on the order page works per colour and size until INV-14** (the human's answer, 2026-11-03), with the smallest change
   to its Actions: the request keys, the row lookup and the snapshot's colour.

## Consequences
- An order line's grid on Edit order shows the product's colours plus any colour already on the line, so a shipped row in a colour
  since removed keeps its units.
- A line recorded before sizes keeps one field per colour, like a one-size product.
- The hand-kept migration list (F-60) gains this migration: a test that rolls back the order line sizes, the colours or the product
  colours rolls it back first.
