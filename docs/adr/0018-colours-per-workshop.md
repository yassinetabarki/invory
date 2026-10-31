# ADR-0018 — Colours: a list per workshop with fixed codes, picked by every product

- **Status:** Accepted · 2026-10-30 (approved by the human with the Technical work of INV-1; tech-lead; the human's answers of
  2026-10-30 on the INV-1 card and its Technical work)
- **Keeps:** [ADR-0013](0013-one-workshop-per-account.md) (every record belongs to a workshop; another workshop's record is a 404),
  [ADR-0008](0008-reference-data-in-database.md) (reference data in tables, immutable codes), [ADR-0004](0004-pure-costing-domain.md)
  (nothing here reaches the costing domain)

## Context
INV-1 replaces typed colours with one fixed list per workshop, so "Indigo" is always the same Indigo. A colour has a name, a short code
(IND) and a swatch. Each product picks the colours it is made in from that list, at least one. INV-2 will count finished stock and print
barcodes per colour and size, and builds variant SKUs from the colour code (PRD-JKT-001-IND-S), so a code must never change. Units and
categories are still shared by every workshop until S-45, but colours are per workshop from the start. The live database must never be
reset, so the workshops and products of today need their colours from a data step.

## Decision
1. **A table `colours` with `workshop_id`** (`code` up to 4, `name` up to 32, `swatch` `#RRGGBB`, `position`, timestamps). Unique per
   workshop on `code` and on `lower(name)`. No `archived_at`: a colour is never removed. PostgreSQL adds CHECKs on the code and swatch
   formats and `unique(workshop_id, id)` for composite keys. The model uses `BelongsToWorkshop`, and the arch test lists the table as
   workshop data.
2. **The code is fixed.** Only Add colour takes it; Edit ignores it; the model refuses to save a changed code on an existing colour.
3. **Uniqueness is checked through the `Colour` model** (scoped to the workshop), not with `Rule::unique` or `DB::table`, so the messages
   can name the colour in the way ("Code IND is already used by Indigo."). A lost race meets the unique index and comes back as the
   same message, as the categories do (F-33).
4. **A link table `product_colours`** (`workshop_id`, `product_id` cascade, `colour_id` restrict; unique per product and colour). Products
   point at colours by id; PostgreSQL's composite keys refuse a colour of another workshop. A product's colours are always read in list
   order (position, name, id). At least one is required when a product is saved through its form.
5. **The 20 defaults are kept twice on purpose.** The migration writes its own frozen copy into every workshop that exists (and links
   every product, deleted ones too, to its workshop's first colour, White); `App\Actions\Colours\CreateDefaultColours` writes the app's
   copy into each new workshop (`CreateWorkshop`, and `WorkshopFactory` for tests). Changing the defaults later changes new workshops only.
6. **Colours are page props** (Settings › Colours, Add / Edit product, the product page), not part of the shared `reference` prop.
7. **Colours change no figure.** Nothing in `App\Domain\Costing`, capacity, stock, orders, sales or the dashboard reads them.

## Consequences
- Every new workshop holds 20 rows from its first second; R6 of the business rules now says "empty except for its 20 default colours".
- Every product payload (forms and tests) carries `colours`.
- INV-2 can rely on codes that never change and on one colour row per product and colour to hang stock and barcodes on.
- S-45 (units and categories per workshop) can follow the same pattern.

## Alternatives rejected
- **Free-text colours on products** (as materials have): the problem INV-1 exists to solve.
- **An installation-wide list like the size lists**: the card wants each workshop to rename and add its own.
- **Products pointing at colour codes**: ids match how compositions point at materials; the code is for people and SKUs.
- **One copy of the defaults imported by the migration**: a migration must not depend on app code that can change after it ran.
- **Colours in the shared `reference` prop**: only three screens need them, and the prop would need a version bump on every add.
