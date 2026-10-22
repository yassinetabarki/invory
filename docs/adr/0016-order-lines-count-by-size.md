# ADR-0016 — Order lines count by size: a row per size, the line keeps the totals

- **Status:** Accepted · 2026-10-22 (approved by the human with the Technical work of S-60 and S-61 and the business-rules update;
  tech-lead; the product-owner's answers of 2026-10-22 on where sizes show; to be delivered by S-60 and S-61)
- **Keeps:** [ADR-0014](0014-cutting-takes-materials-out-of-stock.md) (a cut takes and returns materials per unit, latest cut first),
  [ADR-0015](0015-sizes-come-from-the-product-category.md) (fixed size lists, a product has its category's sizes, later stories point at
  `sizes.id`), [ADR-0013](0013-one-workshop-per-account.md) (every record belongs to a workshop),
  [ADR-0002](0002-money-and-quantity-precision.md) (no money or decimal quantity changes here: units are whole numbers)

## Context
S-60 lets an order line hold a quantity per size ("S 10 · M 20 · L 15 · XL 5"), still one line per product. S-61 follows every size
through To start, In cutting, Ready to ship and Shipped, with one move window that moves several sizes at once. Every size uses the same
composition, so a cut takes the same materials per unit whatever the size. Lines recorded before sizes keep their quantity as "One size"
(S-60 AC-5). Two later stories build on this: S-57 (cancel an order: per size, To start becomes "Dropped", Ready to ship and later In
cutting become "Put in stock") and S-56 (finished stock per size). The Orders list, its filter and its totals (S-50) read the line's
four stage counts with integer SQL sums, and a line's status comes from them.

## Decision
1. **A row per size of a line: `order_line_sizes`** (`workshop_id`, `order_line_id` cascade, `size_id` restrict, `quantity`,
   `in_cutting`, `ready_to_ship`, `shipped`; unique per line and size). Only sizes ordered have a row (quantity 1 … 1 000 000); To start
   is derived per size exactly as per line. PostgreSQL adds the same CHECKs as on `order_lines` and a composite workshop key.
2. **The line keeps its `quantity` and its three stage counts, as the sums of its size rows.** They are written only by the two
   Actions that write size rows (`SyncOrderLines`, `MoveOrderLineUnits`), in the same transaction, from the rows they just wrote, under
   the order's row lock that every writer takes first. So the Orders list's filter, its totals, its search, the line and order statuses
   and the delete refusal keep reading the line and don't change.
3. **Every line counts by a size list: `order_lines.size_list_id`.** A new line takes its product's list. The migration gives every
   existing line the "One size" list and one size row holding its quantity and counts (S-60 AC-5). Because a product on an order can't
   change to a category with another list (S-58 AC-4), a line's list equals its product's, except for lines recorded before sizes.
4. **Three ways a line counts, decided in one place** (`OrderLineSizeMode`, worked out by one service for the request, the Actions and
   the Resource): `by_size` (the line's list has several sizes: a field per size), `one_size` (a "One size" product: the single
   "Quantity" field and the single "How many units?" field, as before) and `before_sizes` (a line recorded before sizes whose product now
   has several sizes: the single field and the hint "Recorded before sizes."). Single-field lines keep the payloads, the messages and
   the error keys of before (`quantity`, `units`); `by_size` lines send a quantity per size id.
5. **Cuts stay per line, not per size.** A move asks a number per size and moves all of them or none; Start cutting takes the materials
   of the total, and Move back to To start returns from the line's cuts for the total, latest cut first, whatever sizes those cuts were
   (the materials per unit are the same for every size). `CuttingCalculator` and the cut tables don't change.
6. **The sizes of a move are recorded on its history entries**, as a snapshot on `stock_movements.sizes` (JSON: size id, label and
   units of each size moved, in size order; null for single-field lines and every other entry), so the history reads "14 × Denim
   Jacket (S 4 · M 10)" without a join, and keeps reading so if a size list ever changes.
7. **Saving an order locks the products of its lines** (closes [F-64](../TECH-DEBT.md)): the sizes submitted are checked against the
   product's list read under that lock, so an order saved in the same instant as a category change can't record sizes of another list.
8. **Built for S-57 and S-56.** Stage counts have the same column names on the line and on its size rows, To start is derived in one
   place for both, and the move engine works from `OrderLineMove`'s from-stage and to-stage. S-57 adds its stages (dropped, put in
   stock) to both tables and its move to the enum; the order page's size table takes its columns as a list, so a cancelled order can
   show Dropped / In cutting / Put in stock / Shipped. S-56 counts finished stock per `sizes.id` on its own table.

## Consequences
- The order pages load the size rows and their labels: a few queries per page, whatever the number of lines; the Orders list and an
  order's page stay within the page budget of 10.
- The line's totals duplicate its size rows. Only two Actions write them, both under the order lock, and their tests assert that the
  line equals the sum of its rows after every write. Factories build a line with a matching "One size" row by default.
- A line recorded before sizes stays "One size" for good: no screen converts it to sizes (S-60 AC-5).
- The New order form adds the live "Total 50 units" by summing the whole numbers typed. That is input feedback on whole units, not a
  figure: the saved quantity and every stage count come from the server.

## Alternatives rejected
- **Drop the line's counts and sum the size rows everywhere.** Every query of the Orders list (filter, totals, search, the units sum)
  and the statuses would move to a join or subquery, for no change on screen; S-57 would then change them all again.
- **One order line per product and size.** The card keeps one line per product (one product, one block on the order page, one "50 ×
  Denim Jacket" on the list), and the 50-products limit and the unique product per order would change meaning.
- **Sizes as a JSON column on `order_lines`.** Nothing to lock or check per size, no CHECKs, and S-57 and S-61 update counts per size.
- **Cuts per size.** The materials per unit are the same for every size, and S-61 AC-4 returns "the latest cut first, as today".
- **A table of sizes per history entry.** One more query on every history read for a fixed, write-once text.
