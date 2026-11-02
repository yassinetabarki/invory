# ADR-0022 — Restocks of finished products are recorded and undone as a whole

- **Status:** Accepted · 2026-11-02 (approved by the human with the Technical work of INV-3; tech-lead role held by the main session)
- **Builds on:** ADR-0013 (every record belongs to a workshop; another workshop's record is a 404), ADR-0017 and ADR-0019 (finished
  stock per colour and size, one row per variant, every writer locks the product first), ADR-0020 (barcodes follow the product's
  name), ADR-0009 rule 5 (small session-authenticated JSON routes for typeaheads and lookups)

## Context
INV-3 adds a **restock** of finished products: units that were made or arrived go on the shelf in their colours and sizes, for one
to 50 products at once, with a date, a reference, a note and who recorded it. A restock **adds**; Update stock still **sets** and
writes no history. A restock can be **undone as a whole**, at any time, while every colour and size it added still holds at least
that many units; it then stays in the list, marked Undone. A restock never changes materials, costing, sales or orders.

## Decision
1. **Two tables, and the shelf count stays where it is.** `product_restocks` (date, reference, note, `units` its total, `user_id`,
   `undone_at`, `undone_by`) and `product_restock_lines` (one row per product, colour and size that got units, with the product's
   `position` on the restock). `product_size_stocks` stays the only count of the shelf, read by `FinishedStock` as before: the restock
   tables are a record, not a second count. Rejected: a ledger summed on read, which could never add up since Update stock writes no
   history.
2. **The total is stored.** A restock is never edited, so `units` never goes stale, and the list needs no sum per row.
3. **Every writer of finished stock locks the products first, in id order**: `RecordRestock` and `UndoRestock` (several products),
   `UpdateFinishedStock` and `DeleteProduct` (one). A restock and an Update stock on the same product run one after the other; the
   later save wins, as the card says. Locking in id order means two restocks of the same products can't deadlock.
4. **Saving re-checks everything under the locks:** a deleted product, colours or sizes that changed since the page, and a variant that
   would go over 1,000,000 units are refused with the card's messages, and nothing is written.
5. **Undo is a flag, never a delete.** It is checked twice: without locks for the panel's reason, then under the locks for the write.
   A product deleted since is skipped (its stock went with it); the other products' units are taken back. A colour taken off a
   product or a category change needs that stock at 0 first, so such a restock simply can't be undone, with the card's reason.
6. **Whole units as PHP integers**, as `FinishedStock` and Update stock already do: finished units are counts, never money or material
   quantities, so they don't go through `BigDecimal` or `App\Domain\Costing`.
7. **The product picker, a product's grid and the barcode lookup are JSON routes** (ADR-0009 rule 5). A scanned number finds the
   non-deleted products whose name it stands for (ADR-0020) and whose size list has its size; when the products of that name no
   longer have its colour, or its size, the lookup says so.

## Consequences
- Lines point at products with `restrictOnDelete`; products are soft-deleted, so a restock always reads its products, deleted ones
  included ("Denim Jacket (deleted)").
- Lines keep their colour and size ids (`restrictOnDelete`); colours and sizes are never deleted.
- PostgreSQL adds composite keys (`workshop_id`, `product_restock_id`) and (`workshop_id`, `product_id`) and CHECKs on the units;
  the Actions enforce the same rules on SQLite.
