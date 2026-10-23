# ADR-0017 — Finished stock per size: a count per product and size, set by "Update stock"

- **Status:** Proposed · 2026-10-23 (tech-lead, with the Technical work of S-56; the human's decisions of 2026-10-23, STATUS §2 "the
  S-56 card changes", Q-H1 and Q1 to Q8; becomes Accepted when the human approves the Technical work)
- **Keeps:** [ADR-0015](0015-sizes-come-from-the-product-category.md) (fixed size lists, a product has its category's sizes, later
  stories point at `sizes.id`; the category lock), [ADR-0016](0016-order-lines-count-by-size.md) (order lines count by size),
  [ADR-0013](0013-one-workshop-per-account.md) (every record belongs to a workshop), [ADR-0004](0004-pure-costing-domain.md) (nothing
  here reaches the costing domain)

## Context
S-56 shows, on a product's page, how many finished units are on the shelf in each size ("In stock 10 units", "S 3 · M 0 · L 5 · XL 2"),
and lets the owner correct that count with "Update stock". S-57 (cancel an order) will add units to it: the units ready to ship when the
order is cancelled, and later the units put in stock from cutting. Nothing else may change it: not "Can produce now" (materials only),
not the unit cost, not the Inventory value (materials only), not a sale, not a new order. While a product has units in stock its
category can't move to one with other sizes, as S-58 already does for a product on an order.

## Decision
1. **A table `product_size_stocks`: one row per product and size, only while that size holds at least one unit** (`workshop_id`,
   `product_id`, `size_id` restrict, `units` ≥ 1; unique per product and size). "Nothing in stock" means no rows, so the category lock is
   one `exists`, and a product at 0 that changes category leaves nothing pointing at its old sizes. PostgreSQL adds a composite workshop
   key and the CHECK. The input limit (1,000,000 per size) belongs to "Update stock", not to the table, since S-57 adds to these rows.
2. **"Update stock" sets counts; it never adds or removes.** The window shows today's count of every size and saves what was typed; a
   size left empty counts as 0 and loses its row. No history entry is written: no criterion asks for one, and `stock_movements` is the
   materials' ledger.
3. **The last save wins (Q-H1, the human's decision of 2026-10-23).** Once S-57 adds cancelled units, a count saved from a window opened
   before that cancellation replaces it: the count you save is the count on the shelf. No version check or merge.
4. **One lock for every writer: the product's row.** `UpdateFinishedStock` locks the product, reads its size list under that lock and
   refuses sizes of another list ("The sizes of Denim Jacket have changed. Enter the stock again.", nothing saved). `UpdateProduct`
   takes the same lock before checking the category lock, `DeleteProduct` before removing the stock, and S-57's Action will take it
   before adding units. So a stock update, a category change, a delete and a cancellation never pass each other.
5. **The category lock grows a second reason.** A product with units in stock can't move to a category with another size list:
   "<product> has units in stock, so its category can't change to one with other sizes." followed by the S-58 ending ("Keep a category
   with sizes <list>." or "Keep a "One size" category."). When the product is also on an order, the S-58 message wins (Q1). At 0 in
   stock the S-58 rule applies alone.
6. **Deleting a product deletes its stock (Q8).** Products stay soft-deleted, but `DeleteProduct` removes the product's stock rows in
   its transaction, so a deleted product holds no shelf units and nothing reads them again.
7. **The product page reads the stock on its own** (`finished_stock`, a deferred prop in its own group, rescued): if only the stock
   fails, the rest of the page shows and the figure offers "Try again"; saving reloads only that prop.
8. **Whole units, no money.** Finished stock never reaches `App\Domain\Costing`, the products list, sales or the dashboard.

## Consequences
- A count typed from a stale window overwrites units S-57 added in between (decision 3, accepted by the human).
- S-57 adds units through its own Action on these rows, under the product's lock, creating a row when a size had none.
- S-57 cancels orders whose product may be deleted (an order line keeps a soft-deleted product); whether those units go into the stock
  of a deleted product is for S-57 to settle.
- One more small table per workshop; the product page's stock group stays within the page budget of 10 queries.

## Alternatives rejected
- **A row for every size, 0 included.** Rows to rewrite on every category change, and the lock becomes a sum instead of an `exists`.
- **A JSON column of counts on `products`.** Nothing to check or lock per size, and S-57 adds per size.
- **"Update stock" as a plus or minus (a movement), or a history of finished stock.** The card asks for today's count to be corrected;
  a ledger of finished units would be its own story.
- **A version check so a stale window can't overwrite S-57's units.** The human chose "the last save wins" (Q-H1).
- **Keeping the stock rows of a deleted product** (they would come back on a restore). Products have no restore screen, and the human
  chose that the stock goes with the product (Q8).
