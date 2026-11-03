# ADR-0026 — Orders are served from the shelf

- **Status:** Accepted · 2026-11-03 (approved by the human with the Technical work of INV-14; tech-lead role held by the main session)
- **Supersedes:** the order parts of ADR-0014 (cutting takes materials out of stock) and the stage counts of ADR-0016 (order lines count
  by size). **Builds on:** ADR-0017 and ADR-0019 (finished stock per colour and size), ADR-0022 (restocks), ADR-0023 (production for the
  shelf), ADR-0025 (order lines per colour and size)

## Context
Since INV-5 products are made on Progress, for the shelf; a finished product is not tied to an order. The order page still ran its own
production (To start, In cutting, Ready to ship, Shipped, with cutting taking materials). INV-14 makes the order a tracker of what the
client is waiting for: To deliver, Delivered, Cancelled, served from the finished stock.

## Decision
1. **Two counts per row:** `order_lines` and `order_line_sizes` count `delivered` and `cancelled`; To deliver is `quantity − delivered −
   cancelled`, never stored (`HasDeliveryCounts`). The line's counts are the sums of its rows. Statuses: Cancelled when every unit is
   cancelled; Delivered when nothing is left to deliver and at least one unit was delivered; Not started while nothing was delivered;
   In progress otherwise. An order is open while it has a unit To deliver.
2. **Old orders mapped by the migration:** Shipped → Delivered (the shelf doesn't change); To start, In cutting, Ready to ship → To
   deliver. The Ready-to-ship units physically exist, so they go on the shelf first as one restock per workshop (Reference "Orders",
   Note "Ready to ship before INV-14"), which can be undone like any restock. A shelf figure that would pass 1,000,000 stops the
   migration, naming the variant.
3. **Orders no longer cut:** the move Actions, the previews, the routes and the delete refusal go. `order_line_cuts` and their
   materials stay as the materials' history.
4. **One writer for the three windows:** `ChangeOrderUnits` (Mark delivered, its move back, Cancel units), per colour and size, under the
   lock order of Edit order and the restocks: order row → the line's product → the line → its rows → the product's shelf rows. Mark
   delivered takes units off `product_size_stocks` (a row at 0 is deleted), its move back puts them on it again (never past 1,000,000;
   a colour taken off the product since is linked again), Cancel touches no stock. No stock history entry and no restock: the shelf
   is the only trace (the card: the product page keeps no trail of deliveries).
5. **The tracker is a deferred, rescued prop** of the order page (`OrderTracker`): it reads the shelf of every product of the order in
   one query; the page shows its frame at once and "Try again" when it fails.

## Consequences
- A delivery lowers the shelf, so an older restock may no longer be undoable (INV-3's refusal says so).
- A line recorded before sizes names "One size" for a product with sizes: no shelf row has that size, so it can't be delivered until
  Edit order gives it real sizes.
- Deleting an order is never refused; delivered units are not put back.
- The hand-kept migration list (F-60) gains `deliveryCountsMigration()`.
