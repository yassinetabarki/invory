# ADR-0020 — Barcodes follow the name

- **Status:** Accepted · 2026-10-31 (approved by the human with the Technical work of INV-8; tech-lead)
- **Builds on:** [ADR-0019](0019-finished-stock-per-colour-and-size.md) (a variant is a product, a colour and a size, with no table of
  its own), ADR-0018 (colours per workshop, codes never change), ADR-0013 (every record belongs to a workshop; another workshop's
  record is a 404), ADR-0017 (every writer of a product's stock, sizes or colours takes the product's row lock first)

## Context
INV-8 gives every variant (a colour in a size) an EAN-13 barcode made by Invory. The card says one barcode stands for **one name +
colour + size**: every product with the same name ("denim jacket" is "Denim  Jacket"), colour and size shares it; a barcode never
changes and its number is never given to anything else; a deleted product's barcodes, and those of a colour taken off, come back when
a product of that name has that colour again; a rename carries the barcodes to the new name and the old name keeps them; a rename
that would give one colour and size two numbers is refused. INV-9 prints labels from the same barcodes.

## Decision
1. **Two tables.** `barcodes` holds one row per number (`workshop_id`, `number`, `colour_id`, `size_id`, `created_by`), unique
   (`workshop_id`, `number`): a number is given once per workshop, and two workshops can each have 2000000000015. `barcode_names`
   holds what a number stands for (`workshop_id`, `barcode_id`, `name_key`, `colour_id`, `size_id`), unique (`workshop_id`, `name_key`,
   `colour_id`, `size_id`): one name, colour and size never has two barcodes. On PostgreSQL a composite foreign key (`workshop_id`,
   `barcode_id`, `colour_id`, `size_id`) → `barcodes` stops a name pointing at a number of another workshop, colour or size. Every key is `restrictOnDelete`
   (`created_by` `nullOnDelete`): nothing in the app deletes a barcode.
2. **The same-name rule is one PHP function**, `App\Domain\Barcodes\NameKey::of()` (trimmed, runs of spaces as one, `mb_strtolower`,
   accents kept), stored as `products.name_key` (set by a mutator whenever `name` is set) and never computed in SQL: PostgreSQL's and
   SQLite's `lower()` disagree on accents. The migration fills every product, deleted ones too, with a frozen copy of the rule.
3. **A product's barcodes are looked up, never owned.** `ProductBarcodes::of()` joins the product's `name_key`, colours and size list
   to `barcode_names`; a product shows every barcode its name already has. "Shared with" lists the other non-deleted products of the
   workshop, any status, whose name stands for the number and that have the colour and size.
4. **In-store numbers from a counter on the workshop.** `workshops.last_barcode_serial` (default 0); serial n becomes "2" + n on 11
   digits + the EAN-13 check digit (1 → 2000000000015). `GenerateBarcodes` runs in one transaction: the product's row lock, the
   variants checked again, then the workshop's row lock; a variant whose name, colour and size already has a barcode counts as
   "already used"; the others take the next serials in colour order, then size order; the counter is saved last. Any failure writes
   nothing and leaves the counter as it was.
5. **Lock order: product, then workshop**, for every barcode writer (Generate, the rename), so they never deadlock; the unique keys
   refuse anything that slips through.
6. **A rename adds the new name; the old name keeps it.** After the colours and category are saved, for every colour and size the
   product has **after** the save, a barcode of the old name that the new name lacks gets the new name too (`AddNameToBarcodes`, one
   insert). Colours taken off before or in the same save are not carried. A change of capitals or spaces only writes nothing.
7. **A rename that joins two numbers is refused**, before any write: `RenameClash::refusal()` finds every colour and size the product
   has after the save where the old and the new name each have a barcode with different numbers, and `UpdateProductRequest` puts the
   sentence on `name`; `UpdateProduct` checks again under the product's and then the workshop's lock.
8. **Every list of variants or SKUs is written by the server** (`VariantNames`): toasts, the AC-5 sentence and the Generate window's
   text (`missing_names`). The frontend draws the bars from the 13 digits (`lib/ean13.ts`, no library) and computes nothing else.

## Consequences
- Nothing touches costing, materials, stock, orders, sales or the dashboard.
- A barcode can stand for several names (after renames) but always for one colour and one size; one name, colour and size always has
  at most one number.
- Numbers starting with 2 are GS1 "restricted circulation" numbers, for use inside the shop only. A retailer selling these goods may
  use the same range for its own items: fine while barcodes stay in the workshop; selling through retailers would need GS1 numbers.
- Rejected: one table where a renamed name repeats the number (nothing would stop a number being reused for another colour);
  barcodes owned by products (a deleted product's barcodes couldn't come back); a database sequence for the counter (it skips numbers
  on rollback and isn't per workshop); a barcode library (the picture is ~60 lines, tested against the published example).
