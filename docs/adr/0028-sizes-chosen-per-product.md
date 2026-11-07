# ADR-0028 — Sizes chosen per product, from the workshop's own list

- **Status:** Accepted · 2026-11-07 (proposed 2026-11-06 by the tech-lead from the human's decisions on INV-18; approved with INV-18's
  Technical work on 2026-11-06). Its amendment of the costing skill (appendix below) is applied, word for word, in the commit of step
  INV-18-c, with one addition: `CreateProduct` without sizes takes the category's suggestion.
- **Supersedes:** ADR-0015 "Sizes come from the product category, as fixed size lists" (Accepted 2026-10-21; its file left `docs/adr/`
  on 2026-10-31, commit `1b1dfde`, and is read from git history). This ADR stands in for the amendment of ADR-0015: a new number,
  because the old file is no longer in the vault.
- **Amends:** ADR-0016 (an order line counts by "its size list" → by a flag on the line), ADR-0017 decision 5 and ADR-0019 (the
  category lock for stock goes), ADR-0023 (the category lock for production goes), ADR-0020 (a variant SKU changes when its size is
  renamed; barcodes don't). ADR-0027 (INV-16, Proposed) read "the product's size list"; it reads "the product's sizes" from here on.
- **Keeps:** ADR-0002 (sizes never touch money or quantities), ADR-0004 (sizes never reach `App\Domain\Costing`), ADR-0013 (every record
  belongs to a workshop; another workshop's record is a 404), ADR-0018 (the Colours pattern this follows), ADR-0020 (barcodes follow
  the name, colour and size).

## Context
Since S-58 a product has the sizes of its category, through three installation-wide lists: Apparel XS to XXXL (since 2026-11-02),
Footwear 36 to 46, every other category One size. Nothing can change them, so the Denim Jacket carries XS, XXL and XXXL it never makes,
each with a stock column, a barcode row and an order field. The human decided on 2026-11-06 (INV-18):

1. One list of sizes per workshop, in Settings, like Units and Colours; each product picks the sizes it comes in.
2. On Add product, the category pre-selects (suggests) its sizes; the owner may change them.
3. Every existing product keeps exactly the sizes it has.
4. A size is never removed, only renamed and reordered. A size that the product's stock, orders, barcodes or production use can't be
   taken off it; a barcode locks its size for good.
5. A product has at least one size; "One size" can't be combined with another.
6. Sizes are listed by position, ties by creation order (not by name: S, M, L must not read L, M, S).

Six tables already point at `sizes.id` (stock, order rows, barcodes, barcode names, restock lines, batches). Product categories are still
installation-wide (S-45 is on hold), while the new size list is per workshop.

## Decision

### 1. `sizes` belongs to a workshop
`sizes` gains `workshop_id` (BelongsToWorkshop) and `is_one_size` (boolean); `size_list_id` and the table `size_lists` go. The name stays in
the column `label` (every reader uses it); a new name is at most 12 characters (the column stays 16). Unique per workshop on
`lower(label)`; at most one `is_one_size` row per workshop (partial unique index); `position` is no longer unique. The list order is
**position, then id** (the scope `Size::ordered()`), everywhere a size list is read or a size subselect sorts rows.
**"One size" is the flag, not the name:** a product whose only size is the flagged row has no size in its variant SKU and one field per
colour; renaming that row to "No size" or "Taille unique" changes only the words shown.

### 2. A product's sizes are stored: `product_sizes`
`product_sizes` (`workshop_id`, `product_id` cascade, `size_id` restrict, unique product + size). A product has at least one; the flagged
row only alone (both checked by the requests, not the table). One reader, `App\Services\Products\ProductSizes`, gives a product's sizes in
list order and whether it is "One size" (once per request, in batch for pages of many products; read again, fresh, under the product's
row lock by every writer). Nothing reads a category to find a product's sizes any more.

### 3. A category only suggests: `product_category_sizes`, per workshop
`product_category_sizes` (`workshop_id`, `product_category_id` cascade, `size_id` restrict). Categories are shared by every workshop
until S-45, but sizes are not, so the suggestion is kept per workshop: editing Apparel's suggested sizes in one workshop changes no
other. **A category with no row in a workshop suggests that workshop's One size**, so a category added in Settings needs no row in any
workshop (no write crosses workshops). Suggestions are read only by Add product's pre-selection and Settings › Product categories.
Changing a category changes no product's sizes; there is no category lock any more.

### 4. The migration keeps every product's sizes and every record
1. Each workshop gets 19 sizes, positions 10, 20 … 190: XS, S, M, L, XL, XXL, XXXL, 36 … 46, One size (flagged). The lowest workshop id
   adopts the 19 existing rows (ids kept); every other workshop gets new rows, and its stock, order rows, barcodes, barcode names,
   restock lines and batches are moved onto them (one mapping per workshop, by list code and label). On PostgreSQL the composite key
   `barcode_names` → `barcodes` is dropped for the move and added back. With no workshop, the old rows are deleted.
2. Every product (soft-deleted ones too) gets its category's list, mapped to its workshop's rows; each workshop gets Apparel's and
   Footwear's suggestions by category code.
3. `order_lines.size_list_id` becomes `order_lines.by_size` (boolean): true where the list had several sizes. A line counts by size when
   the flag is set; otherwise it is `one_size` while its product is "One size" and `before_sizes` otherwise (S-60 AC-5 unchanged).
4. PostgreSQL only: composite keys (`workshop_id`, `size_id`) → `sizes (workshop_id, id)` on the six tables above and the two new ones.
5. `stock_movements.sizes` (the history of cuts before INV-14) is not rewritten: it is history and is read by its stored labels.
6. `down()` restores the three lists from the lowest workshop's rows and moves every other workshop back onto them; it refuses, naming
   the size, when a workshop has a size that isn't one of the 19 starting names (added or renamed), and it drops every product's own
   choice (a product falls back to its category's list).

### 5. The lock is per size, checked twice
`ProductSizeLock` gives, for each size of a product, its reasons in this order: **stock** (a `product_size_stocks` row), **orders** (an
`order_line_sizes` row of an order that isn't deleted, delivered or cancelled ones included, as S-58's lock did), **barcodes** (a
barcode name with the product's name key, that size and one of the product's colours: what its Barcodes tab shows; barcodes are never
deleted, so this reason never lifts while the name and colour stay), **production** (a batch In production). INV-16 adds a fifth,
material quantities, last. Four grouped queries per product, whatever its number of sizes.
The update request refuses a locked size taken off (one sentence per size, list order, on `sizes.removed.{sizeId}`), and `UpdateProduct`
checks again under the product's row lock. Every writer that puts a size on a record (Update stock, Record restock, an order save, Start
batch, Generate barcodes) already takes that lock and now re-reads the product's sizes under it, so a size can't come off between a
writer's check and its write, and a writer can't write a size that has just come off ("The sizes … of <product> have changed").

### 6. The server writes every size sentence
The badge ("One size", "Size M", "Sizes S, M, L, XL", "Sizes XS to XXXL", "Sizes XS, S, M and 4 more"), the Suggests line, the lock
reasons and the refusals are written server-side (`App\Domain\Sizes\SizeLine`, pure; `ProductSizeLock`), from the workshop's list for
"an unbroken run". The size text rule of S-70 (a digits-only name reads "size 36") is kept. Settled by the human on 2026-11-06: every
lock sentence puts "it" before its first reason; the One size sentence names the sizes in use ("XL is in use, …", "Size 38 is in use,
…", "Sizes 36, 37 and 38 are in use, …"); when One size is locked, the other sizes read "One size is in use, so other sizes can't be
chosen."; the Suggests line is the badge's rule without "Sizes" / "Size".

## Consequences
- Renaming a size changes every variant SKU that names it ("PRD-JKT-001-IND-XXL" → "-2XL") and no barcode, since barcodes point at
  the size's id. Variant SKUs are never stored, so nothing is rewritten.
- A restock can hold a size its product no longer has (restocks don't lock: stock set to 0, then the size taken off). The restock
  detail lists the product's sizes plus any size its lines hold, in list order, so no line disappears.
- A deleted order may point at a size its product no longer has. There is no order restore; one, if ever added, must check the sizes.
- Each workshop now has its own sizes, which closes F-65's "lists stay installation-wide".
- `SizeList`, `SizeLists`, `SizeListResource` and the `SizeList` type go; the product's contract carries `sizes`, `one_size` and
  `sizes_line` instead of `size_list`.
- Edit product costs four more grouped queries (the lock reasons); the page budget is re-checked in INV-18-g.

## Alternatives rejected
- **Keep the three lists and add per-product exclusions.** Still no way to add XXS or rename a size, and every reader would combine two
  sources.
- **Sizes shared by every workshop**, as today. A rename in one workshop would change the SKUs of every other.
- **Every workshop gets new rows**, the old ones deleted. Same result, but every reference in the busiest workshop is rewritten for
  nothing; the lowest workshop keeping the ids moves fewer rows.
- **Suggestions on the shared category row.** A workshop's edit would change another workshop's Add product.
- **Barcodes don't lock a size** (as they don't lock a colour). Decided against by the human (a barcode is on printed labels).
- **An order line remembers its sizes as a list id.** No lists any more; the flag says all the line needs.

## Appendix — the costing skill amendment (applied in INV-18-c)

**Glossary.** Replace the rows *Size list* and *Size* with four rows (term · meaning · code name), in the skill's table format:
- **Size** · One size of the workshop's list ("S", "36", "One size"): a name (at most 12 characters, unique in the workshop ignoring
  capitals) and a position. Never removed; renamed and moved in Settings › Sizes. Listed by position, then creation order. Every
  workshop starts with 19: XS, S, M, L, XL, XXL, XXXL, 36 … 46, One size, at 10, 20 … 190 (INV-18, ADR-0028) · `Size` / `sizes`
- **One size** · The one size of each workshop flagged `is_one_size`, whatever its name: a product that has it has no other size, no
  size in its variant SKU and one field per colour · `sizes.is_one_size`
- **Product sizes** · The sizes a product comes in, chosen on Add and Edit product, at least one, in list order · `product_sizes`,
  `ProductSizes`
- **Suggested sizes** · The sizes a category pre-selects on Add product, per workshop; none set = One size · `product_category_sizes`

**Replace the section "Sizes (added 2026-10-21, S-58, ADR-0015)"** with:

```
sizes(W)              = the sizes of workshop W, by position, then id
sizes(P)              = the sizes chosen for product P, in the order of sizes(P.workshop);  |sizes(P)| ≥ 1
one_size(P)           = sizes(P) is exactly W's flagged One size
suggested(W, C)       = the sizes category C suggests in workshop W, in list order; none set → W's One size
used_by(z)            = number of non-deleted products, any status, with size z
locked(P, z)          = reasons, in order: stock (finished(P, ·, z) ≥ 1 in a colour) · orders (a row of a non-deleted order,
                        delivered and cancelled included) · barcodes (a barcode of name_key(P), a colour of P and z) ·
                        production (a batch of P in z In production) · [INV-16: material quantities]
```
- A size taken off a product while `locked` is refused: "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has
  barcodes." (one sentence per size, list order; "Size 36 can't …" for a digits-only name; "it" always before the first reason, "…: it
  is on orders."). With a size locked, One size can't be chosen ("XL is in use, …", "Sizes 36, 37 and 38 are in use, so One size can't
  be chosen."); with One size locked, no other size can ("One size is in use, so other sizes can't be chosen."). Several sizes or none → "Choose at least
  one size."; One size with another → "One size can't be combined with other sizes. Choose it alone, or choose other sizes.".
- Changing a product's category changes none of its sizes; a category no longer locks anything. A new size is on no product.
- Renaming a size changes the variant SKUs that name it and no barcode.
- Sizes change **no figure**: unit cost, "Can produce", the bottleneck, next limit, margin, sales and the dashboard are as before.
- Screens: the badge "One size" (the flagged size's name) · "Size M" · "Sizes S, M, L, XL" (2 to 6) · "Sizes XS to XXXL" (7 or more in an
  unbroken run of sizes(W)) · "Sizes XS, S, M and 4 more" (7 or more with gaps). "Can produce now" adds ", any mix of sizes" when
  |sizes(P)| > 1. Settings › Product categories' Suggests line uses the same rule without "Sizes" / "Size" ("XS to XXXL", "S, M, L,
  XL", "One size"). The server writes them; the frontend never works out a range.

**In "Orders by size"**, replace the `size_mode` lines with:
```
size_mode(L)          = by_size        when L was recorded by size (L.by_size)
                      = one_size       when L wasn't and one_size(L.product)
                      = before_sizes   otherwise (recorded before sizes; its product now has sizes)
```
and "Every line counts by a size list, stored on the line: a new line takes its product's" with "A new line counts by size unless its
product is One size; the flag is stored on the line".

**In "Finished stock"**, delete the paragraph "**Locks the category.** …"; **in "Finished stock per colour"**, delete "The category lock
counts a unit in any colour;". **In "Where it lives in code"**, add: "Added 2026-11-06 (INV-18, ADR-0028): `App\Models\Size` per workshop
(`ordered()`, `is_one_size`), `product_sizes`, `product_category_sizes`, `App\Services\Products\ProductSizes` (the one reader),
`App\Domain\Sizes\SizeLine` (badge and Suggests line), `App\Services\Products\ProductSizeLock` (per size), `App\Actions\Sizes\{CreateSize,
UpdateSize,CreateDefaultSizes}`, `App\Actions\Products\SyncProductSizes`; `SizeList`, `SizeLists` and `SizeListResource` are gone."
