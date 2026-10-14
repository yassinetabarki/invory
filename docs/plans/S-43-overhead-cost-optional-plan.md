# Technical plan: S-43 Save a product without filling in the overhead cost
> Status: **delivered 2026-10-14, awaiting acceptance** (approved as written) · Date: 2026-10-14 · Owner: tech-lead
> Story card: [docs/stories/S-43-overhead-cost-optional.md](../stories/S-43-overhead-cost-optional.md) (`Ready`, approved 2026-10-14, decision D-8)
> Branch: `phase/4-mvp-release-readiness`. Runs ahead of S-31 and S-32 (on hold). Dev runtime per ADR-0012; nothing here migrates, seeds or
> wipes the development database `laravel`.

## Scope
**In:** the approved business rule written into the costing rules first (`.claude/skills/invory-costing-domain/SKILL.md`, the human asked for
it via D-8); the product form request accepts a blank or missing overhead and stores it as `0.0000` on create and update; the product form
labels "Overhead cost (optional)" with the card's hint, no star and no `aria-required`; tests for AC-1…AC-7; the golden-path browser test's
field locator follows the new label (it still types 3.500).
**Out (matches the card):** removing the field; making labor cost optional; a "Not set" state or a list of products missing overhead; bulk
changes to existing products (no migration); a workshop-wide default overhead; any change to the demo data, the golden-path values or the
S-30 walkthrough figures.

## Facts verified at intake (2026-10-14, read-only)
| Fact | Evidence |
|---|---|
| The only thing that makes overhead mandatory is the `required` rule in `StoreProductRequest::fieldRules()` (shared by `UpdateProductRequest`) | `app/Http/Requests/Products/StoreProductRequest.php` |
| The column is `decimal(12,4) NOT NULL DEFAULT 0` with a PostgreSQL check `overhead_cost >= 0`. Writing an explicit `null` would violate NOT NULL on both SQLite and PostgreSQL, so a blank value must become `'0'` before it reaches the model | migrations `2026_10_09_300100_…`, `2026_10_09_300200_…` |
| `ConvertEmptyStringsToNull` turns the form's `''` into `null` (JSON and multipart alike); the form always sends `overhead_cost` (its state starts `''`) | `product-form.tsx` `emptyState()` |
| `CreateProduct` / `UpdateProduct` fill the model from `validated()`; `Product::$attributes` already defaults `overhead_cost` to `'0.0000'` | `app/Actions/Products/*`, `app/Models/Product.php` |
| `CostingCalculator::calculate()` already takes overhead as a non-negative string (default `'0'`); `ProductCostingService` passes the stored value. **No domain change** | `app/Domain/Costing/CostingCalculator.php` |
| Resources always serialise `overhead_cost` as a scale-4 string; `domain.ts` types it `Decimal` (and `string` in the form state). **No contract change** | `ProductResource`, `CostBreakdownResource`, `resources/js/types/domain.ts` |
| Factory and demo seeders always set a value (1–5, 3.5000 for the jacket); there is **no CSV import** and no other write path for products | `ProductFactory`, `DemoProductsSeeder`, `grep csv\|Import app resources/js` |
| `FormField` gives the control `aria-required` only (the form is `noValidate`): dropping `required` and adding `optional` is the whole a11y change. The "(optional)" span is part of the accessible name ("Selling price (optional)") | `components/products/form-field.tsx` |
| `trimDecimal('0.0000')` returns `'0'`, so Edit already shows "0" for a zero overhead (AC-4 needs a test, not code) | `components/products/decimals.ts` |
| The golden-path browser test finds the field by **exact** accessible name `'Overhead cost'` (`tests/Browser/GoldenPathTest.php:101`). After the label change its name is "Overhead cost (optional)", so the locator must change with the label or the browser gate goes red | source |
| The cost-breakdown card always renders the "Overhead" row (no zero hiding) | `components/products/cost-breakdown-card.tsx` |
| Environment: PHP 8.3.35 on the documented path; `invory-pgsql-1` and `invory-mailpit-1` up and healthy | `php -v`, `docker ps` |

## AC traceability
| AC | Technical card(s) | Test(s) that will prove it |
|---|---|---|
| AC-1 Create without overhead | S-43-b (server), S-43-c (form sends `''`) | `tests/Feature/Products/ProductWriteTest.php` › store › "saves a blank overhead as 0 …" (dataset: `null`, `''`, key missing; redirect to the product page, toast "Product created.", no `overhead_cost` error) · › "reports every broken field at once" (asserts no `overhead_cost` error) · `product-form.test.tsx` › "sends an empty selling price and an empty overhead as empty strings" |
| AC-2 Blank counts as 0 | S-43-a (rule), S-43-b, S-43-c | `ProductWriteTest` › store › "saves a blank overhead as 0 …": stored `'0.0000'`; deferred props `cost = {20.2540, 12.0000, 0.0000, 32.2540}`, `margin = {56.7460, 63.76}`, capacity 112, bottleneck `MAT-BTN-017` · `cost-breakdown-card.test.tsx` › "keeps the Overhead row and shows a zero overhead as TND 0.000" |
| AC-3 Edit: clearing the field | S-43-b | `ProductWriteTest` › update › "clearing the overhead saves 0 and lowers the unit cost by the old overhead" (seeded Denim Jacket 3.5000 → `''` → `'0.0000'`, toast "Product updated.", unit cost 35.7540 → 32.2540) |
| AC-4 Edit shows what was saved | S-43-c | `product-form.test.tsx` › editing a product › "shows 0 in Overhead cost for a product whose overhead is 0" |
| AC-5 Typed values still checked | S-43-b | `ProductWriteTest` dataset `invalidProductInput` (store **and** update): "negative overhead cost", "overhead cost with 5 decimals", new "text overhead cost"; `validProductBoundaries` "free product" (0 typed by hand) |
| AC-6 The field says it is optional | S-43-c | `product-form.test.tsx` › "marks the required fields and labels the optional ones" (Labor cost still `aria-required`; "Overhead cost (optional)" has no `aria-required` and its description is the hint) · golden path (`composer test:browser`) finds "Overhead cost (optional)" |
| AC-7 Existing products unchanged | S-43-b (no migration, no seeder change) | Review check: no file under `database/` in the diff · existing tests stay green unchanged: `ProductWriteTest` › "creates the Denim Jacket of the worked example…" (35.7540 / 112 / 59.83, prop for prop equal to the seeded jacket), `ProductPagesTest` (`overhead_cost` `3.5000`), `DemoSeedersTest` |

## Technical cards (slicing table)
| # | Card id | AC | Lane | Files (exact paths) | Line budget | Tests allowed | Owner | Commit label |
|---|---|---|---|---|---|---|---|---|
| 1 | S-43-a | AC-2, AC-5 (the rule) | NON-CRITICAL (docs; wording below is frozen) | `.claude/skills/invory-costing-domain/SKILL.md` | ~10 | none | TL | `docs(costing): overhead cost is optional and a blank counts as 0 (S-43-a)` |
| 2 | S-43-b | AC-1, AC-2, AC-3, AC-5, AC-7 | CRITICAL (FormRequest encoding a business rule; write to a NOT NULL money column) | `app/Http/Requests/Products/StoreProductRequest.php`, `app/Http/Requests/Products/UpdateProductRequest.php`, `tests/Feature/Products/ProductWriteTest.php`, `tests/Feature/Routes/RoutesTest.php` (added in review round 1) | ~25 prod / ~90 test | dataset edits: remove "missing overhead cost", add "text overhead cost" (runs on store and update); "reports every broken field at once" asserts no `overhead_cost` error; 1 new store test with a 3-row dataset (`null`, `''`, key missing) asserting stored value + golden-variant props; 1 new update test (AC-3). No new test files | BE | `feat(products): overhead cost is optional and a blank is saved as 0 (S-43-b)` |
| 3 | S-43-c | AC-1 (client), AC-2 (display), AC-4, AC-6 | CRITICAL (`ProductForm` is on the CRITICAL list, and the a11y + golden-path gate ride on it; the diff is tiny so the full review is cheap) | `resources/js/components/products/product-form.tsx`, `resources/js/components/products/product-form.test.tsx`, `resources/js/components/products/cost-breakdown-card.test.tsx`, `tests/Browser/GoldenPathTest.php` (one locator) | ~10 prod / ~50 test | 1 modified test (required/optional labels, hint as accessible description); 1 extended test (empty overhead sent as `''`); 1 new edit test (AC-4); 1 new cost-breakdown test (zero row); golden path: change the locator string only | FE | `feat(products): label Overhead cost as optional with a hint (S-43-c)` |

`composer test`, `npx vp test` and `composer test:browser` stay green at every row: S-43-a is docs; S-43-b only relaxes a rule (the form
still sends values); S-43-c changes the label and the golden-path locator in the same commit.

## Frozen contract
- **No route, prop, Resource or `resources/js/types/domain.ts` change.** `overhead_cost` stays a scale-4 `Decimal` string, never `null`, in
  `ProductResource` and `CostBreakdownResource`.
- **Request rule** (`StoreProductRequest::fieldRules()`, shared by update):
  `'overhead_cost' => ['nullable', 'decimal:0,4', 'min:0', 'max:99999999.9999']` — the other three constraints are unchanged, so the
  messages for a negative, 5-decimal or non-numeric value are unchanged (AC-5).
- **Blank → 0 before validation.** Both `StoreProductRequest` and `UpdateProductRequest` implement `prepareForValidation()`, and when
  `overhead_cost` is missing or `null` (a blank or whitespace-only field after `TrimStrings` + `ConvertEmptyStringsToNull`) they merge
  `'overhead_cost' => '0'`. One shared helper (a `public static` method on `StoreProductRequest`, beside `fieldRules()`), called from both
  classes. Result: `validated()['overhead_cost']` is **always** a decimal string; `CreateProduct`, `UpdateProduct`, the model, the factory
  and the seeders are untouched.
- **Labor cost unchanged:** `['required', 'decimal:0,4', 'min:0', 'max:99999999.9999']`; "The labor cost field is required." still shows.
- **Form copy (verbatim from the card):** label `Overhead cost` with `optional` (renders "Overhead cost (optional)", muted, same style as
  "Selling price (optional)"); no `required`; hint `Leave empty if you don't track overhead. It counts as 0.`; placeholder `0` stays; section
  description "Labor and overhead are added to the materials cost to get the unit cost." unchanged; toasts unchanged.
- **Golden path:** `tests/Browser/GoldenPathTest.php:101` becomes `$field('Overhead cost (optional)')->fill('3.500')`. Value unchanged.

### S-43-a: exact text for the costing rules (frozen; applied by the tech-lead as the first step of execution)
1. **Formulas**, after the `unit_cost(P)` line, a note: "`P.overhead_cost` is never null: a blank overhead is stored as `0` (S-43)."
2. **Edge cases** table, new row: "Overhead left blank on Add/Edit product | saved as `0.0000`; `unit_cost = material_cost + labor_cost`;
   capacity, bottleneck and next limit unaffected (decided 2026-10-14, D-8)".
3. **Validation rules → Product**, replaced by: "`labor_cost` required, ≥ 0 (max 4 decimals). `overhead_cost` **optional**, ≥ 0 (max 4
   decimals): blank or missing is saved as `0` and counts as 0 in the unit cost; a typed value follows the same rules (decided 2026-10-14,
   D-8, S-43). An empty BOM is allowed (e.g. a draft). `selling_price` nullable ≥ 0 (max 4 decimals)."
4. **Worked example → Variant checks**, new bullet: "Overhead left empty (`0.0000`) → `unit_cost = "32.2540"`, at selling price `89.0000`
   `margin.amount = "56.7460"`, `margin.percent = "63.76"`; `max_producible = 112`, bottleneck = button (unchanged)."

Check of the card's numbers: 20.2540 + 12.0000 + 0 = 32.2540; 89.0000 − 32.2540 = 56.7460; 56.7460 ÷ 89.0000 × 100 = 63.7596… → 63.76 (HalfUp).

## Approach and decisions
- **Where the blank becomes 0: the FormRequest's `prepareForValidation()`.** Precedent: `StoreRestockRequest` defaults `update_unit_cost`
  the same way (F-20). It keeps `validated()` total and typed, needs no change to the Actions, the model or the domain, and the rule list
  still documents the field as optional. Rejected: (1) coalescing in `CreateProduct` / `UpdateProduct` (two places, and `validated()` would
  carry a `null` that only the Actions know how to read); (2) a model `set` mutator on `overhead_cost` (bypasses the `decimal:4` cast's
  set-side handling and hides the rule in the model); (3) a nullable column (that is the card's rejected Q1 option (b), and it would ripple
  into the domain, Resources and `domain.ts`).
- **A missing key on update also means 0.** The form always submits its whole state (as with the composition, F-36), and labor cost is
  still required, so there are no partial updates. Treating "missing" like "blank" keeps one rule and no hidden "keep the old value" path.
  No caller other than the product form writes products.
- **No ADR.** The formula, precision and architecture don't change; the product decision is D-8 in `docs/STATUS.md`, and the rule lands in
  the costing skill (S-43-a). The skill's "ADR if it changes behaviour" is meant for maths changes; this is an input rule.
- **No migration (AC-7).** Existing rows keep their stored value; the column default stays 0.
- **Tests are edits to the existing files**, not new files: the validation datasets already run on store and update, so one dataset row
  covers AC-5 on both.

## Execution order / waves
- **Step 0 (tech-lead, after approval):** apply S-43-a to the costing skill. Nothing else starts before it.
- **Wave 1 (parallel, disjoint files, no contract dependency):** S-43-b (backend-developer) ∥ S-43-c (frontend-developer). Pre-flight is in
  the brief (files + tests confirmed, stop-rule) instead of an ack round.
- **Review:** full checklist on both cards; gates in order `composer test` → `npx vp test` → `npm run check` → `npm run types:check` →
  `npm run build` → `composer test:browser` (golden path) → `composer test:pgsql` (the write path stores into a NOT NULL money column).
- **Commits (merge order):** S-43-a → S-43-b → S-43-c.

## Risks
| Risk | Mitigation |
|---|---|
| The golden-path gate goes red because its locator matches the label exactly | The locator change is in S-43-c, same commit as the label; the tech-lead runs `composer test:browser` in review |
| A blank reaches the model as `null` and violates NOT NULL (500 instead of a save) | Frozen: merge `'0'` before validation; the AC-1 dataset covers `null`, `''` and a missing key, and parity runs on PostgreSQL |
| `product-form.test.tsx` flakes near the 5 s timeout under load (F-22) | Known; review runs `npx vp test` with nothing else heavy running and, if needed, re-runs the file alone before calling it red. The new tests type nothing long |
| The card says the golden-path test "is not changed"; the locator string does change | Only the field's name changes, the typed value 3.500 and every asserted figure stay. Flagged to the product-owner below |

## Questions for the product-owner
None blocking. Two notes for its records:
1. The golden-path browser test needs one locator change ("Overhead cost" → "Overhead cost (optional)") because it finds fields by their
   exact name. It still types 3.500 and checks the same figures, so the card's "not changed by this story" holds for what it tests.
2. The S-30 walkthrough step reads "Overhead cost 3.5"; the field will read "Overhead cost (optional)". The walkthrough is still followable;
   whether to touch its wording is the product-owner's call.

## Questions for the human
None. Every product decision is settled by the card (Q1–Q6) and D-8; the remaining choices are technical and recorded above.

## Progress log
- 2026-10-14: plan written (planning-only). Approved as written.
- 2026-10-14: S-43-a applied to the costing skill by the tech-lead. Wave 1 (S-43-b ∥ S-43-c) dispatched.
- 2026-10-14: S-43-c approved in round 1. S-43-b round 1 CHANGES REQUESTED: `tests/Feature/Routes/RoutesTest.php` still expected an
  `overhead_cost` error on an empty store/update body (missed at intake); fixed in round 2 and approved. All gates green (local PHP 8.3
  run with `ext-sockets` via a session-only `PHP_INI_SCAN_DIR`, F-56). Delivered.
