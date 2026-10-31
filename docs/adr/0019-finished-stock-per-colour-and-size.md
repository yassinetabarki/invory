# ADR-0019 — Finished stock per colour and size

- **Status:** Accepted · 2026-10-31 (approved by the human with the Technical work of INV-2; tech-lead)
- **Amends:** [ADR-0017](0017-finished-stock-per-size.md) (finished stock per size): a row now also names a colour
- **Keeps:** [ADR-0018](0018-colours-per-workshop.md) (colours per workshop, fixed codes, `product_colours`),
  [ADR-0013](0013-one-workshop-per-account.md) (every record belongs to a workshop; another workshop's record is a 404),
  [ADR-0004](0004-pure-costing-domain.md) (nothing here reaches the costing domain)

## Context
S-56 counts the finished units of a product per size of its category's list. INV-1 gave every product at least one colour of its
workshop's list. INV-2 counts the shelf per colour and size ("the Denim Jacket in Indigo, size M"), a **variant**. INV-8 will give each
variant a barcode and a SKU (product SKU + colour code + size). The live database holds per-size stock that must not be lost or counted
twice, and must never be reset.

## Decision
1. **A variant is a product, a colour and a size; it has no table of its own.** `product_size_stocks` gains `colour_id`; one row per
   product, colour and size, and, as in ADR-0017, only while it holds at least one unit. The table and model (`ProductSizeStock`) keep
   their names: renaming would touch many files for no behaviour. INV-8's barcodes are shared by name + colour + size across products,
   so they get their own table keyed that way.
2. **The migration moves every row into its product's first colour** (position, then name, then id), with a frozen `DB::table` data
   step. A product with stock and no colour (impossible since INV-1) is first linked to its workshop's first colour, so there is never a
   "No colour" row. Then `colour_id` becomes NOT NULL and the unique key becomes (`product_id`, `colour_id`, `size_id`). `down()` adds
   the colours back together per product and size.
3. **PostgreSQL refuses stock for a colour the product doesn't have**: a composite foreign key (`product_id`, `colour_id`) →
   `product_colours (product_id, colour_id)`. It also makes the database refuse to take a colour with stock off a product.
4. **A colour with stock stays on its product.** `ProductColourLock::refusals()` names each colour taken off that still has rows; the
   update request reports them on `colours.removed.{colourId}` (Inertia shares only the first message of a key, and a key per colour
   lets the form drop one sentence when that colour is chosen again), and `UpdateProduct` checks again under the product's row lock.
   A colour at 0 has no rows, so it comes off as before and, added back, starts at 0.
5. **"Update stock" saves every colour at once**, `stock[colourId][sizeId]`, refused on `stock` when the submitted colours or sizes
   aren't exactly the product's, checked in the request and again under the product's row lock (ADR-0017 decision 4: every writer of
   finished stock, and every writer of a product's sizes or colours, takes that lock first).
6. **Totals come from the server**: `FinishedStock::of()` returns every colour (list order) × every size, 0 included, each colour's
   total, the per-size totals and the grand total, from one query. The category lock and Delete product keep working on all rows of
   the product, so they count every colour.

## Consequences
- No figure changes: "Can produce now", the unit cost, materials, sales, orders and the dashboard read nothing of this table.
- Between the server change and the new window, the old window's per-size save is refused: the two commits ship together.
- Older migration tests that roll back `product_colours` or `product_size_stocks` roll this migration back first.
- Rejected: a `product_variants` table (rows for every colour × size, kept in sync with colours and categories, for no behaviour INV-2
  needs); keeping per-size rows plus a per-colour split (two sources of truth).
