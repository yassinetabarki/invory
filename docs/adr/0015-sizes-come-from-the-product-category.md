# ADR-0015 — Sizes come from the product category, as fixed size lists

- **Status:** Accepted · 2026-10-21 (approved by the human with the S-58 Technical work; tech-lead; S-58, the human's decisions D-15
  and D-16 of 2026-10-18 and the S-58 answers of 2026-10-21: the sizes lock while the product is on any order that isn't deleted,
  Settings shows no sizes, no screen edits the lists; AC-4's message has its own ending for one-size lists)
- **Keeps:** [ADR-0008](0008-reference-data-in-database.md) (reference data in tables, immutable `code`, frozen migration snapshots),
  [ADR-0013](0013-one-workshop-per-account.md) (workshops), [ADR-0004](0004-pure-costing-domain.md) (sizes never reach the costing domain)

## Context
S-58 gives every product the sizes of its category: Apparel S, M, L, XL; Footwear 36 to 46; every other category, and every category
added in Settings, "One size". Nobody picks or edits the sizes. Three later stories count by size: S-60 (an order line per size),
S-61 (stage counts per size) and S-56 (finished stock per size). So the sizes must be stored rows those stories can point at, and
they must not change under them: S-58 AC-4 locks a product's sizes once it is on an order that isn't deleted, and S-56 AC-4 adds the
same lock while it has finished stock. Product categories are still shared by every workshop until S-45 (on hold).

## Decision
1. **Two installation-wide tables, filled by the migration.** `size_lists` (`code` immutable and unique, `name`) and `sizes`
   (`size_list_id`, `label`, `position`; unique per list on label and on position). The migration inserts a frozen snapshot, keyed by
   code: `s_to_xl` "S, M, L, XL" (S, M, L, XL), `eu_36_to_46` "36 to 46" (36 … 46) and `one_size` "One size" (One size). No
   `workshop_id`: they are fixed reference data that no screen edits (allow-listed in the workshop arch test with this ADR as the reason).
2. **Each product category points at exactly one size list.** `product_categories.size_list_id`, NOT NULL, `restrictOnDelete`.
   The migration sets it by category **code** (apparel → `s_to_xl`, footwear → `eu_36_to_46`, every other code → `one_size`), so a
   renamed or archived default keeps its sizes and a category added before this change is "One size". `CreateProductCategory` gives
   every new category `one_size`; renaming, archiving and restoring never touch the column.
3. **A product has the sizes of its category; nothing is stored per product.** A product's sizes are `category → size list → sizes`.
   Copying them onto each product would let the two drift; the card says a product has every size of its category.
4. **"The same sizes" means the same size list.** Bag → Accessory keeps `one_size`, so it is always allowed. A product on an order that
   isn't deleted (fully shipped ones included) can't move to a category with another size list. The check runs in the update request
   (so the error shows with the other field errors) and again in `UpdateProduct` under the product's row lock, with the same message.
5. **The server names the list.** `name` is the text the screens show after "Sizes" ("S, M, L, XL", "36 to 46") or alone ("One size").
   The frontend adds only the card's wording around it; it never works out a range from the labels.
6. **Later stories point at `sizes.id`.** Because a product's size list can't change while it is on an order, an order line's sizes
   (S-60) and finished stock per size (S-56) stay valid. A line recorded before sizes (S-60 AC-5) can point at the single size of
   `one_size`, whatever its product's category, so "One size" is a real row, not a null.

## Consequences
- Sizes never reach `App\Domain\Costing`: unit cost, "Can produce", the bottleneck and every figure stay as they are.
- The product page and the product form load the lists: a few small queries, kept within the page budget of 10 by reading the three
  lists once per request.
- The lock is only race-free once saving an order also locks the product row. Until S-60 stores sizes on order lines that race is
  harmless; S-60 closes it ([F-64](../TECH-DEBT.md)).
- When S-45 gives each workshop its own categories, each copy keeps its size list; the lists stay installation-wide until a story
  lets a workshop edit them ([F-65](../TECH-DEBT.md)).
- A size-list editor, sizes per material (S-61 says every size uses the same composition) and a size per sale are out of scope.

## Alternatives rejected
- **A JSON list of sizes on `product_categories`.** Nothing for S-60, S-61 and S-56 to point at, and no way to tell a renamed size
  from a new one.
- **Size rows per category.** Bag and Accessory would each have their own "One size", so moving a product between them would change
  the ids its orders and stock point at, although the card says that move is always allowed.
- **Size rows per product,** copied from the category. They can drift from the category and every category change must rewrite them.
- **Working out "36 to 46" in TypeScript from the labels.** A display rule the server would need again for the AC-4 message.
