# Phase 3 — Real backend (swap the preview fixtures for real tables)

> Owner: **tech-lead** · Status: **done** (verified 2026-10-14: committed `12c2a16` + `4a98c74` on 2026-10-13; PostgreSQL parity green the same day, 2 073 during S-28) (written 2026-10-13) · Branch: work continues on `main` (S-04 and the Wave A `wip` commit are there).
> Stories: S-16-BE, S-25 (both started in the `wip` commit 2240e7d), S-14-BE, S-06-BE, S-07-BE, S-15-BE, S-17. S-04 is done (4e5b2ef).
> Background and the database design: [phase-3-performance-spa-data.md](phase-3-performance-spa-data.md) §D, §F.3. Process: [TEAM.md](../TEAM.md).

## Goal
Replace every remaining `App\Mock` adapter with Eloquent + Resources **without changing a page, a route or a prop shape** (ADR-0006).
`resources/js/types/domain.ts` is frozen for this phase. At the end `app/Mock`, `public/mock/` and the `access-preview` ability are gone (S-17).

## State found on 2026-10-13 (intake)
- `main` is clean. The human committed Wave A of the earlier sequencing (S-16-BE stock ledger + S-25 material search) as **`wip` (2240e7d)**
  after a cut-off. It was never reviewed and it is **red**:
  - `composer test`: Pint (`StockMovementResource.php` imports), Larastan ×2 (`MaterialController.php:78` and `StockLedgerVerifier.php:45`
    pass `array<int,int>` / `array<mixed>` where `list<int>` is expected), Pest 5 failures + 2 errors:
    - arch preset: `app/Console/Commands/VerifyStockLedger.php` must have the suffix `Command`
    - `QueryBudgetTest` "materials show" ×2 and `StockHistoryPropsTest` show budget: **11 queries > 10** (the real history query is new;
      the page sat at exactly 10 with the preview usage adapter, F-38)
    - `PartialReloadTest` materials index ("Not a valid Inertia response")
    - `StockHistoryPropsTest` index `recent_history` (null)
    - `ProductContractTest` create/edit props (S-25 removed the `materials` prop; the test still reads it)
  - `npx vp test`: 2 failures in `components/products/bom-editor.test.tsx` (server search: the request carries `include` instead of
    `search`; the 50-row / 2 000-material test can't find the row combobox)
  - `npm run check`: formatting in 8 files. `npm run types:check`: green.
- Docker Desktop is down on 2026-10-13, so the PostgreSQL parity suite and the dev database are unreachable (reported, not started).

## Decisions for this phase
| # | Decision | By |
|---|---|---|
| Q8 | Sales are stock-neutral; `DeleteSale` is a plain hard delete (no ledger entry, no stock change) | human 2026-10-09 |
| F-20 | `StoreRestockRequest.update_unit_cost` is optional; omitted means `true` (already in the `wip` code; keep the test) | human 2026-10-09 |
| F-37 | `DemoProductsSeeder` never recreates a SKU that exists only soft-deleted (**skip**, deletions stick). Demo products dated "now" stay (S-08) | human 2026-10-09 |
| F-36 | **The composition is always replaced on update.** The product form always submits its whole state, so a missing or null `components` on update means **an empty composition** (a multipart body can't carry an empty list). No sentinel, no frontend change. The request rule stays `nullable|array`; `UpdateProduct` uses `$data['components'] ?? []`. Documented in `UpdateProduct` | tech-lead (simplest option, as asked) |
| F-38 | Every page keeps the ≤ 10 query budget (`QueryBudgetTest` covers every page). New controllers get `expectQueryBudget()` guards. Costing every product on the dashboard stays in PHP until S-23 | human / tech-lead |
| D-1 | `DemoCatalogSeeder` is **not edited and not run** in this phase (it resets stock and costs). Demo sales go into a new `DemoSalesSeeder`, called from `DatabaseSeeder` | human brief 2026-10-13 |
| D-2 | `DemoSalesSeeder` inserts the 60 fixture sales (dated relative to today) **only when the `sales` table is empty**: idempotent and never duplicates (sales have no natural key) | tech-lead |
| D-3 | Sales of a soft-deleted product stay in the sales log, the totals and the dashboard (the revenue happened). Their `ProductRef` is read `withTrashed()`. The product filter and "Record a sale" list only non-deleted products, as today | tech-lead |
| D-4 | `SalePolicy` (viewAny, create, update, delete: any verified user, like the other policies). The dashboard authorizes `viewAny` on `Material`, `Product` and `Sale`. `access-preview` disappears with S-17 | tech-lead |
| D-5 | Photos (S-15-BE): the original file on the `public` disk under `materials/` or `products/`, `photo_url` is an accessor. A replace or remove deletes the old file **after commit**; deleting an item (soft delete) deletes its file after commit and clears `photo_path`. **No thumbnail variants** in this phase: `photo_thumb_url` would change the frozen contract (F-39: the human decided on 2026-10-13 to skip thumbnails) | tech-lead |
| D-6 | After S-17 the demo data has no photos (the SVG swatches were preview-only and SVG isn't an accepted upload) | tech-lead |
| D-7 | Pre-flight ack for CRITICAL subtasks is **embedded in the brief**: the brief lists the confirmed files and tests; a developer who needs another file, another test or a different approach stops before coding that part and returns it under "Open questions". (A separate ack round would need a background resume, which forces a premature handback, see the tech-lead memory) | tech-lead |
| D-8 | F-35 (SKU race → validation error) and S-23 (`product_metrics`) are **out of scope** for this run; they stay in the backlog | tech-lead |

## Waves (files overlap between stories, so not everything can run at once)
The human's graph runs S-14-BE ∥ S-16-BE ∥ S-15-BE in parallel. S-15-BE shares `MaterialController`, `ProductController` and
`DashboardController` (the photo decoration) with S-16-BE, S-14-BE, S-06-BE and S-07-BE, so it moves after them. Each wave ends with the
tech-lead running every gate; each story is one green commit on top of the previous wave.

```
Wave A  S-16-BE finish + S-25 BE test fix (BE-1)  ∥  S-25 FE finish (FE-1)  ∥  S-14-BE sales + F-37 (BE-2)
Wave B  S-06-BE dashboard (BE-1)  ∥  S-07-BE material usage (BE-2)
Wave C  S-15-BE photos + F-36 (BE-1)
Wave D  S-17 remove the preview layer (BE-1)
```
`tests/Pest.php` belongs to BE-2 in Wave A (demo sales in `preparePreview`), to nobody in Wave B unless the brief says so.
`tests/Feature/Performance/*` take **targeted edits only** (never a rewrite): each developer edits only the block of its own page.

## Slicing tables
The human asked for **one commit per story**; the rows of a story are its review checkpoints and are squashed into the story commit.

### S-16-BE + S-25 (Wave A, resume of the `wip` commit)
| # | Subtask | Lane | Files | Budget | Tests | Owner | Commit label |
|---|---|---|---|---|---|---|---|
| 1 | S-16-BE-fix | CRITICAL | `app/Console/Commands/VerifyStockLedger*.php`, `app/Services/Inventory/{StockHistory,StockLedgerVerifier}.php`, `app/Http/Resources/StockMovementResource.php`, `app/Http/Controllers/MaterialController.php`, whatever the show-page budget fix needs (e.g. a request-scoped unit lookup), `tests/Feature/Materials/StockHistoryPropsTest.php`, materials blocks of `tests/Feature/Performance/PartialReloadTest.php` | ~100 changed | fix the existing ones, no new tests unless a criterion is uncovered | BE-1 | `feat(materials): stock movement ledger, restock and history on real tables (S-16-BE)` |
| 2 | S-25-BE-fix | CRITICAL (contract) | `tests/Feature/Products/ProductContractTest.php` (+ the S-25 BE files only if the review finds something) | ~20 | update the create/edit contract test to the S-25 props | BE-1 | `feat(products): server-side material search for the composition picker (S-25)` |
| 3 | S-25-FE-fix | CRITICAL (BomEditor) | `resources/js/components/products/{bom-editor,bom-editor.test,material-search,use-material-options}.ts(x)`, the 8 formatting files | ~50 | fix the 2 failing tests | FE-1 | same commit as row 2 |

### S-14-BE · Sales (Wave A, BE-2)
| # | Subtask | Lane | Files | Budget | Tests | Owner |
|---|---|---|---|---|---|---|
| 1 | S-14-a schema | CRITICAL | `database/migrations/2026_10_13_100000_create_sales_table.php`, `app/Models/Sale.php`, `database/factories/SaleFactory.php`, `app/Policies/SalePolicy.php`, `Product::sales()` | ~170 | 1 migration test (FKs, PG CHECKs, indexes; PG-only parts skipped on SQLite), 1 policy dataset | BE-2 |
| 2 | S-14-b writes | CRITICAL | `app/Actions/Sales/{RecordSale,UpdateSale,DeleteSale}.php`, `app/Http/Requests/Sales/*` (authorize → policy), `SaleController` store/update/destroy, `routes/web.php` (`{sale}` binding) | ~180 | 1 happy per Action, 1 validation dataset (active-product rule, future date, bounds), 1 guest, 1 "stock unchanged" (record + edit + delete leave `materials` and `stock_movements` untouched) | BE-2 |
| 3 | S-14-c reads | CRITICAL (money) | `app/Services/Sales/SalesReport.php` (name free), `app/Http/Resources/SaleResource.php`, `SaleController::index`, `ProductController` (index sold 30 d, show `sales_last_30_days` + `recent_sales`), `database/seeders/{DemoSalesSeeder,DatabaseSeeder,DemoProductsSeeder}.php`, `tests/Pest.php` (`preparePreview` seeds demo sales), preview sales/product tests ported | ~300 | golden totals (48 / 296 / `16850.0000` / `351.0417`, frozen time), 1 filter dataset (search, product, period), 1 contract (`assertInertia` shape), 1 query budget for sales index, F-37 test | BE-2 |

Commit: `feat(sales): persist sales and serve the sales log, totals and product sales figures from the database (S-14-BE)`.

### S-06-BE · Dashboard (Wave B, BE-1) · CRITICAL
Files: `app/Http/Controllers/DashboardController.php`, `app/Http/Requests/DashboardRequest.php`, the S-14 sales service (top products,
period summary), a real "costed catalogue" service replacing `PreviewProducts::load/alerts/unproducibleCount` (e.g.
`app/Services/Products/ProductCatalog.php`), `tests/Feature/Dashboard/*`, the dashboard block of the performance tests.
Budget ~250. Tests: golden dashboard (period stats, top 5 by units and revenue with `percent_of_top`, recent 5, alerts, can't-produce count),
period dataset, 1 policy/guest, 1 query budget. Commit: `feat(dashboard): dashboard on real sales and products (S-06-BE)`.

### S-07-BE · Material usage (Wave B, BE-2) · CRITICAL
Files: `app/Services/Costing/MaterialUsageService.php` (name free), `MaterialController::show`, `tests/Feature/Materials/MaterialUsageTest.php`,
the materials-show block of the performance tests. Budget ~180. Tests: worked example (button limits the Denim Jacket to 112, next limit
Denim 12oz at 126), only-material → `next_limit = null`, ordering (bottleneck rows first, then name, then id), soft-deleted product
excluded, 1 query budget flat in the number of products. Commit: `feat(materials): "What you can make with it" from real compositions (S-07-BE)`.

### S-15-BE · Photos + F-36 (Wave C, BE-1) · CRITICAL
Files: migration `add_photo_path_to_materials`, `app/Services/Photos/PhotoStorage.php`, `Material`/`Product` `photo_url` accessors, the
material and product create/update/delete Actions, `MaterialController`/`ProductController`/`DashboardController`/`SaleController`
(remove `decorate` and the "Photos are a preview" toast), `UpdateProduct` (F-36), tests with `Storage::fake('public')`.
Budget ~300. Tests: upload / replace (old file gone) / remove / delete-with-item per model (one dataset), rollback leaves no orphan file,
invalid type/size dataset, F-36 (multipart update without `components` clears the composition), 1 contract (`photo_url` is the disk URL).
Commit: `feat(photos): store material and product photos on the public disk (S-15-BE)`.

### S-17 · Remove the preview layer (Wave D, BE-1) · CRITICAL (gate removal)
Delete `app/Mock`, `public/mock/`, the `access-preview` ability, the preview-only tests (keep any assertion that still guards a real
behaviour by moving it to its feature folder); arch test "nothing references `App\Mock`"; ADR-0006 status note; `DemoCatalogSeeder`
untouched (D-1). Commit: `chore(preview): remove the UI-first preview layer (S-17)`.

## Progress log (tech-lead)
- 2026-10-13: intake. Found Wave A red in `wip` (2240e7d), reds listed above. Docker down: parity not runnable. Plan written.
  Dispatching Wave A: BE-1 (S-16-BE finish + S-25 contract test) ∥ FE-1 (S-25 FE) ∥ BE-2 (S-14-BE + F-37).
- 2026-10-13: Wave A **approved in one round** (S-16-BE, S-25, S-14-BE + F-37). Gates run by the tech-lead: Pest 2 101 (22 skipped),
  Pint, Larastan 0, Vitest 1 162, check, types, build. PostgreSQL parity **not run** (Docker down). Notes: the materials-show budget is
  met by `ReferenceData::units()` (one units read per request, kept on the request); a real 500 was fixed in the materials index
  (`recent_history` read the paginator after `MaterialResource::collection()` had replaced its items); the products index folds
  "Sold (30 d)" into its page query (`withSum` over the integer `quantity`). Dispatching Wave B: S-06-BE (BE-1) ∥ S-07-BE (BE-2).
- 2026-10-13: Wave B approved. S-07-BE in one round (`MaterialUsageService`, 3 queries, only the products that use the material;
  outputs compared equal to the preview adapter for all 12 demo materials, then the comparison was dropped). S-06-BE in two rounds:
  round 1 left the dashboard at 11 queries; the fix shares the material categories per request (`ReferenceData::materialCategories()`).
  `PreviewSales.php` and the fixture sales are deleted. Gates: Pest 2 087 (22 skipped), Pint, Larastan 0, Vitest 1 162, check, types,
  build. Parity not run (Docker down). Dispatching Wave C: S-15-BE + F-36 (BE-1).
- 2026-10-13: Wave C approved in one round (S-15-BE + F-36): `PhotoStorage::persist()` writes the new file before the transaction, deletes it if
  the transaction throws, deletes the old one after; `photo_path` stays non-mass-assignable (`forceFill`); every `App\Mock` use left
  `app/Http`. Gates: Pest 2 094, Vitest 1 162, check, types, build. Dispatching Wave D: S-17.
- 2026-10-13: Wave D approved in one round (S-17): `app/Mock`, `public/mock/`, `access-preview` and `tests/Feature/Preview` gone; real-behaviour
  tests moved to `Products/`, `Routes/`, `Seeders/`; `preparePreview` → `prepareDemo`; one arch rule forbids `App\Mock`. Final gates: Pest
  2 068 (22 skipped), Pint, Larastan 0, Vitest 1 162, check, types, build. **PostgreSQL parity not run (Docker down) — required before merge.**
  The tech-lead updated ADR-0006 (retired), ARCHITECTURE §4/§5, the costing skill's "Where it lives", the backlog (statuses, F-39…F-46).
  Phase status: **all stories approved, awaiting the human (commit, parity run, `storage:link`)**.
- 2026-10-13: human decisions: F-39 skip (original photos, no contract change); F-40 no reason field (costing skill reworded);
  F-35 deferred as a MINOR backlog item, not blocking Phase 3. Docs updated; no code change.
