# ADR-0023 — Production batches for the shelf

- **Status:** Accepted · 2026-11-02 (approved by the human with the Technical work of INV-5; tech-lead role held by the main session)
- **Builds on:** ADR-0013 (every record belongs to a workshop; another workshop's record is a 404), ADR-0014 (cutting takes materials
  out of stock and a return gives back exactly what was taken), ADR-0019 (finished stock per colour and size), ADR-0022 (restocks of
  finished products), ADR-0009 rule 5 (small session-authenticated JSON routes)

## Context
INV-5 adds **Progress**: the workshop makes products **for the shelf**, in **batches** of one product in one colour and one size.
**Start batch** takes the materials out of stock (S-53's rules) and makes one batch per colour and size, each numbered per workshop
(B-0147). A batch goes from **In cutting** through the workshop's own **phases** (0 to 6) to **Put in stock**, one phase at a time;
moving part of a batch splits it into a new numbered batch. Put in stock adds the units to the finished stock and records a restock
("From production"). **Undo batch**, only while In cutting, gives back what was cut. Nothing is tied to an order; order cutting
(S-53) stays as it is until INV-14.

## Decision
1. **Five tables and a counter.** `production_phases` (name, position), `production_cuts` / `production_cut_materials` (what one Start
   batch took, as `order_line_cuts`), `production_batches` (number, product, colour, size, cut, `split_from_id`, units, status
   `in_production` · `in_stock` · `undone`, `phase_id` null = In cutting, `phase_since`, `started_on`, the restock of its last Put in
   stock), `production_batch_moves` (every start, move, Put in stock and undo, with the phase names as text), and
   `production_counters` (one row per workshop: the batch counter and the production lock).
2. **One production cut per Start batch, shared by its batches.** The plan is made once for the total, so it is checked and rounded
   once per material, as an order cut. Each batch, and every batch split from it, points at that cut. Undo batch returns
   `held(r) − held(r − k)` from the cut (ADR-0014 section 5, with r = the cut's units not returned yet), so undoing every batch of a
   start gives back exactly what it took. Rejected: a cut per batch (6 + 10 could round differently from 16, and the check would no
   longer match the window's "Materials for 16 units").
3. **The maths is reused, not copied.** `App\Domain\Stock\CuttingCalculator` and `CuttingPreview::plan()` serve orders and batches; the
   short-stock sentence stays `OrderMessages::notEnoughStock()`. Stock movements are the same `cutting` / `cutting_return` entries,
   with the product and the units and no order.
4. **Put in stock is a restock.** It calls `RecordRestock` in its own transaction (Reference = the batch number, Note "From
   production"), so the shelf, the Restocks list, Undo restock and the 1,000,000 limit have one writer and one rule. Undoing that
   restock never brings the batch back.
5. **Batch numbers come from `production_counters.last_batch_number`**, one row per workshop, made on first use. Numbers are never
   reused; an undone batch keeps its number. Not `workshops`: Update product and Generate barcodes lock product → workshop, so the
   workshop row can't be the first lock of a production write.
6. **One production lock per workshop, taken first.** Every production writer (phases, Start batch, moves, Put in stock, Undo batch)
   locks its workshop's `production_counters` row first, then: Start batch the product and its materials (ascending id); a move the
   batch; Put in stock the batch and the product (inside `RecordRestock`); Undo batch the batch, its cut and the materials; phases the
   phase rows. Production writes of one workshop run one after another (a small workshop's pace), and nothing else locks the counter,
   so no cycle is possible. Start batch takes the product lock that Update product, Update stock and Delete product take, so a colour
   or category change can't slip between Edit product's production check and a start.
7. **A stale page is refused, never guessed.** A move carries the phase it came from and the phase it goes to; a batch that moved, or
   a phase inserted, removed or reordered meanwhile, is refused with "These units have moved on since you opened this page.".
8. **Phases are deleted for real**, only while no batch in production is in them; a phase that holds units keeps its place and name
   can't be removed. History keeps the names as text.
9. **Whole units as PHP integers**, as ADR-0022 decision 6. Material quantities stay decimal strings through `BigDecimal`.

## Consequences
- Batches point at products with `restrictOnDelete`; products are soft-deleted, so a batch always reads its product ("Denim Jacket
  (deleted)"). A deleted product's batch can be moved back and undone, never put in stock.
- PostgreSQL adds `unique(workshop_id, id)` on the parents, composite keys on (`workshop_id`, …) and CHECKs on units, number, status
  and position; the Actions enforce the same rules on SQLite. A phase name is unique per workshop ignoring capitals on both engines.
- Positions are not database-unique: a swap passes through a moment where two phases hold one place. The Actions keep them 1…n under
  the production lock.
- INV-14 removes order cutting; `CuttingCalculator` and `CuttingPreview::plan()` can then move to a neutral namespace.
