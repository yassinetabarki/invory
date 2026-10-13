# Invory — MVP backlog

> Owner: **tech-lead**. Status: `todo` → `in progress` → `review` → `done`.
> Every story follows the Definition of Done in [ARCHITECTURE.md §8](ARCHITECTURE.md#8-quality-testing-and-definition-of-done).
> BE = backend-developer, FE = frontend-developer, TL = tech-lead.

## MVP goal
A logged-in user can maintain a materials inventory, define products and their composition, and see for every product
its **unit cost**, **how many units can be produced** from current stock, and **which material is the bottleneck**.

Scope added by the human on 2026-10-08 with the approved mockup ([docs/design/mockup](design/mockup/README.md)):
1. **Sales log**: record sales (product, quantity, unit price, date, note; stock unchanged) and a sales dashboard (totals, top 5, recent sales, alerts).
2. **Photos**: one optional photo per material and per product (JPG/PNG/WebP, max 4 MB). Pulled forward from Phase 2 ("product images").
3. **Material "What you can make with it"**: per product, need per unit, units this stock covers, can make now, limited by, and a next-limit suggestion (extends S-07).
4. **Restock quick action + History**: restock form (unit cost = latest price paid) and a history of restocks, corrections and price changes. Pulled forward from Phase 2 ("stock movement ledger").
5. **Selling price** on products: pre-fills "Record a sale", and product pages show the margin (server-side).

## Delivery plan (phases → waves; the tech-lead dispatches, see [TEAM.md](TEAM.md))

| Phase | Stories | Plan | Status |
|---|---|---|---|
| **1 — Foundation + Materials** | S-00, S-01, S-02, S-03 | [plans/phase-1-foundation-materials.md](plans/phase-1-foundation-materials.md) | **done** (committed on `phase/1-materials`) |
| **2 — UI-first preview** (approved mockup on preview data, decided 2026-10-08) | S-12, S-13, S-05, S-06-FE, S-07-FE, S-14-FE, S-15-FE, S-16-FE | [plans/phase-2-ui-first-preview.md](plans/phase-2-ui-first-preview.md) | **review: all stories approved by the tech-lead and committed on `phase/2-ui-first-preview` (2026-10-08), awaiting the human click-through to mark `done`** |
| 3 — Real backend (replaces preview data) | S-04, S-14-BE, S-16-BE, S-25, S-15-BE, S-06-BE, S-07-BE, S-17 | [plans/phase-3-real-backend.md](plans/phase-3-real-backend.md) | **review: all approved by the tech-lead 2026-10-13, awaiting commit** (S-04 done) |
| 4 — E2E + demo data | S-08 | written by the tech-lead before Phase 4 | todo |
| 3P / 3R — Performance, SPA data loading, reference data (+ amendments to Phase 3 and 4) | S-18 … S-27 | [plans/phase-3-performance-spa-data.md](plans/phase-3-performance-spa-data.md) | **Proposed** (2026-10-08, awaiting the human's decisions Q1–Q10) |

```
Phase 1  Wave 1: S-00 (BE ∥ FE ∥ TL)
         Wave 2: S-01 (BE #1) ∥ S-03 (BE #2) ∥ S-02a components (FE)
         Wave 3: S-02b pages (FE, needs S-01)
Phase 2  Wave 1: S-13a contract + routes (BE-1) ∥ S-13b domain maths (BE-2) ∥ S-12 theme + shared UI (FE-1)
         Wave 2: S-13c preview data layer (BE-3) ∥ S-05 products UI (FE-2) ∥ S-06-FE + S-14-FE (FE-3) ∥ S-07-FE + S-15-FE + S-16-FE (FE-4)
Phase 3  S-04 products → S-14-BE sales ∥ S-16-BE stock ledger ∥ S-15-BE photos → S-06-BE dashboard ∥ S-07-BE usage → S-17 remove preview layer
Phase 4  S-08 E2E smoke + full demo seed
```
The subtask-level breakdown (file lists, contract, checks) lives in the phase plan. This backlog keeps the story-level view.
Phase 2 pages are final: Phase 3 stories replace controller bodies (preview fixtures → Eloquent + Resources) **without changing pages, routes or props**
([ADR-0006](adr/0006-ui-first-preview-data.md)).

---

## S-00 · Foundation — `done` (Phase 1, Wave 1)
**Owners:** BE, FE, TL · **Depends on:** project scaffolded ✅ · Subtasks: [plan §S-00](plans/phase-1-foundation-materials.md#s-00--foundation--wave-1)

- [ ] BE: `config/invory.php`; `Model::shouldBeStrict`, `JsonResource::withoutWrapping()`; share `app.currency` / `app.locale`; **full Phase 1 contract in `domain.ts`**; arch tests
- [ ] FE: Vitest via Vite+ (`npm test`), Inertia test helper, `lib/format.ts`, `<Money>` / `<Quantity>`, Invory branding (logo text, landing page, remove starter-kit footer links)
- [ ] TL: extend `.github/workflows/tests.yml` (PostgreSQL job, PHP 8.3 + 8.5, `vp test`, build); triage `npm audit`; review
- Toasts already work through the starter kit's native `Inertia::flash('toast', …)` + `<Toaster>`, so there's nothing to build.

**Acceptance:** all gates green; shared props asserted; contract types in place for Wave 2.

---

## S-01 · Materials — backend — `done` (Phase 1, Wave 2)
**Owner:** BE · **Depends on:** S-00 · Subtasks: [plan §S-01](plans/phase-1-foundation-materials.md#s-01--materials--backend--wave-2-be-1--size-m)

- [ ] Enums `MaterialCategory`, `UnitOfMeasure`, `StockStatus`; migration with a partial unique SKU index; model, factory, policy
- [ ] `IndexMaterialRequest` (filters + sort), `Store/UpdateMaterialRequest`; actions `CreateMaterial`, `UpdateMaterial`, `DeleteMaterial` (soft delete; S-04 adds the "in use" guard)
- [ ] `MaterialController` (resource) + `MaterialResource` matching `domain.ts` (`stock_value`, `stock_status`); toasts via `Inertia::flash`; page stubs
- [ ] Feature tests: guests, filters/sort/pagination, contract, CRUD, validation datasets, stock status, DB-level SKU uniqueness

**Acceptance:** all CRUD routes work behind auth; decimals round-trip as scale-4 strings; contract asserted; PostgreSQL parity green.

## S-02 · Materials — UI — `done` (Phase 1, Waves 2–3)
**Owner:** FE · **Depends on:** S-00 (S-02a), S-01 (S-02b) · Subtasks: [plan §S-02](plans/phase-1-foundation-materials.md#s-02--materials--ui)

- [ ] S-02a (components, contract-first): `StockBadge`, `MaterialsTable` (responsive), `FiltersBar` + `useDebouncedValue`, `Pagination`, `EmptyState`, `PageHeader`, `MaterialForm`, `ConfirmDeleteDialog`
- [ ] S-02b (pages): `materials/{index,create,edit,show}` wired with Wayfinder; sidebar **Materials** item; delete flow; page tests

**Acceptance:** a user can add, find, edit and delete materials without touching the URL bar; usable at 375 px and in dark mode; keyboard-only flow works.

## S-03 · Costing engine (pure domain) — `done` (Phase 1, Wave 2)
**Owner:** BE · **Depends on:** S-00 (independent of S-01) · Subtasks: [plan §S-03](plans/phase-1-foundation-materials.md#s-03--costing-engine--wave-2-be-2--size-m)

- [ ] `App\Domain\Costing`: `BomLine`, `LineCost`, `CostBreakdown`, `ProductionCapacity`, `CostingResult`, `CostingCalculator` (BigDecimal, no Illuminate)
- [ ] Unit tests first: the worked example (exact strings), every edge case in the domain skill, aggregation, tie-break, magnitudes

**Acceptance:** 100 % branch coverage of the domain; worked example passes exactly (`unit_cost = "35.7540"`, `max_producible = 112`, bottleneck = button).

## Phase 2 — UI-first preview (plan: [phase-2-ui-first-preview.md](plans/phase-2-ui-first-preview.md))
Every Phase 2 page is final and typed by `domain.ts`. Data without a table yet comes from `App\Mock` ([ADR-0006](adr/0006-ui-first-preview-data.md));
writes to preview data validate for real, persist nothing and say so in an `info` toast.

## S-12 · Theme, shell, shared UI — `review` (Phase 2, Wave 1; approved 2026-10-08)
**Owner:** FE · **Depends on:** S-00 · Subtasks: [plan §S-12](plans/phase-2-ui-first-preview.md#s-12--theme-shell-shared-ui--wave-1-fe-1--size-m)

- [ ] Denim theme tokens (primary `#2B3A86`, neutral surfaces, status tokens) with derived dark tokens (AA); Instrument Sans + IBM Plex Mono; tabular numbers
- [ ] Nav parent matching (F-01), delete `nav-footer.tsx` (F-02), logo tile, `StockBadge` on tokens
- [ ] Shared `PhotoThumb`, `PhotoField`, `StatCard`, `Callout`, `CapacityBadge`, `ProductStatusBadge`; shadcn `popover` + `command`; `formatDate`, `formatCount`; test helper for `<Deferred>` and `useForm`

**Acceptance:** FE gates green; light and dark AA; usable at 375 px.

## S-13 · Preview data layer — backend — `review` (Phase 2, Waves 1–2; approved 2026-10-08)
**Owner:** BE · **Depends on:** S-01, S-03 · Subtasks: [plan §S-13](plans/phase-2-ui-first-preview.md#s-13--preview-data-layer--backend)

- [ ] S-13a: full UI-first contract in `domain.ts`, `app.today` shared prop, enums `ProductCategory`/`ProductStatus`/`SalesPeriod`/`StockMovementType`, final routes, skeleton controllers, page stubs, `access-preview` ability
- [ ] S-13b: `MarginCalculator`, `CostingCalculator::capacityWithout()` (next limit), `SalesCalculator` (pure, 100 % branches)
- [ ] S-13c: `App\Mock` fixture (7 products, 60 sales, stock history, photos) computed through the domain; real `InventorySummary`; FormRequests for every write; `DemoCatalogSeeder` (12 materials); arch rule; feature tests for every "Expected" value

**Acceptance:** every preview page receives exactly the plan's expected props (Denim Jacket `35.7540` / 112 / button; 48 sales / `16850.0000`); PostgreSQL parity green.

## S-05 · Products — UI + composition editor — `review` (Phase 2, Wave 2, on preview data; approved 2026-10-08)
**Owner:** FE · **Depends on:** S-12, S-13a (S-13c for real props) · Subtasks: [plan §S-05](plans/phase-2-ui-first-preview.md#s-05--products-ui--wave-2-fe-2--size-l)

- [ ] `products/index`: thumb, unit cost, price · margin, `CapacityBadge`, bottleneck link, status badge, sold (30 d), filters incl. "Can't produce", pagination, empty states — **objective #4**
- [ ] `products/show`: photo, **can-produce hero**, unit cost, sold (30 d), bottleneck callout, cost breakdown with selling price + margin, composition table (bottleneck row with text + icon), recent sales — **objective #2**
- [ ] `products/create|edit`: `ProductForm` (costs and price, photo) + `BomEditor` (searchable material combobox, unit suffix, waste %, notes, add/remove/move, duplicate prevention, nested errors) — **objective #3**
- [ ] Tests: `BomEditor` interactions, `ProductForm` payload and errors, composition table (worked example), cost breakdown variants, page tests

**Acceptance:** with the demo seed, the Denim Jacket shows TND 35.754 (default currency; €35.75 with EUR) / 112 units / bottleneck "Metal shank button 17mm" / margin 59.83 %, and the edit form shows its 4 lines.

## S-06-FE · Dashboard — UI — `review` (Phase 2, Wave 2, on preview data; approved 2026-10-08)
**Owner:** FE · Subtasks: [plan §S-06-FE + S-14-FE](plans/phase-2-ui-first-preview.md#s-06-fe--s-14-fe--dashboard-sales-navigation--wave-2-fe-3--size-l)

- [ ] Period select (Last 30 days default), can't-produce alerts, 5 KPI cards with links, top 5 sellers with Units / Revenue toggle, needs restock list, recent sales; empty states

## S-14-FE · Sales log — UI — `review` (Phase 2, Wave 2, on preview data; approved 2026-10-08) · decision 1
**Owner:** FE · Subtasks: as S-06-FE

- [ ] Sidebar Products + Sales; totals; filters (search, period, product); table + cards; record / edit sale panel (active products only, price pre-filled from the selling price, date ≤ today); delete with confirm

## S-07-FE · Material "What you can make with it" — UI — `review` (Phase 2, Wave 2, on preview data; approved 2026-10-08) · decision 3
**Owner:** FE · Subtasks: [plan §S-07-FE + S-15-FE + S-16-FE](plans/phase-2-ui-first-preview.md#s-07-fe--s-15-fe--s-16-fe--materials-ui-additions--wave-2-fe-4--size-l)

- [ ] Usage table (needs per unit, this stock covers, can make now, limited by), next-limit suggestions, delete disabled with the reason when used

## S-15-FE · Photos — UI — `review` (Phase 2, Wave 2; approved 2026-10-08) · decision 2
**Owner:** FE (materials: FE-4, products: FE-2)

- [ ] Thumbnails in tables and lists, large photo on detail pages, upload / replace / remove in the material and product forms (JPG/PNG/WebP, max 4 MB)

## S-16-FE · Restock + History — UI — `review` (Phase 2, Wave 2, on preview data; approved 2026-10-08) · decision 4
**Owner:** FE (FE-4)

- [ ] Materials list: per-row Restock quick form and History panel (deferred); material detail: Restock action + full History; used-in column

---

## Phase 3 — Real backend (replaces the preview data; pages, routes and props do not change)

## S-04 · Products + composition — backend — `review` (approved by the tech-lead 2026-10-09; S-04a foundation + S-04b switch)
**Owner:** BE · **Depends on:** S-01, S-03, S-13

- [ ] Migrations `products` (incl. `selling_price decimal(12,4) null`, `photo_path`), `product_components` (FKs, unique pair); models + factories (`withComponents()`); `ProductPolicy`
- [ ] Actions `CreateProduct`, `UpdateProduct`, `DeleteProduct`, `SyncProductComponents` (transactional)
- [ ] `ProductCostingService` (Eloquent → `BomLine[]` → calculator, margin, next limit)
- [ ] `ProductController` bodies switch from `App\Mock` to Eloquent + `ProductListResource`, `ProductResource`, `ProductComponentResource`, `CostBreakdownResource`, matching the Phase 2 contract exactly; FormRequest `// MOCK:` rules become `unique` / `exists`
- [ ] `DeleteMaterial` blocks materials used by non-deleted products (`MaterialInUseException` + error toast); `used_in_count` via `withCount`
- [ ] Feature tests: N+1-safe index, worked example end-to-end through HTTP props, BOM validation, sync removes and reorders lines, transaction rollback

**Acceptance:** creating the Denim Jacket via HTTP yields exactly the Phase 2 expected props; the products fixture is deleted from `App\Mock`.

## S-14-BE · Sales — backend — `review` (approved by the tech-lead 2026-10-13; includes F-37) · decision 1
**Owner:** BE · **Depends on:** S-04

- [ ] Migration `sales` (product FK `restrictOnDelete`, `quantity` unsigned int, `unit_price decimal(12,4)`, `sold_on date`, `note`, `user_id`), model, factory, `SalePolicy`
- [ ] Actions `RecordSale`, `UpdateSale`, `DeleteSale`; `SaleController` bodies on Eloquent; totals and top products through `SalesCalculator` (exact on SQLite and PostgreSQL)
- [ ] Tests: filters, periods (frozen time), totals, active-product rule, stock unchanged by sales

## S-16-BE · Stock movement ledger + restock — backend — `review` (approved by the tech-lead 2026-10-13; started in the `wip` commit 2240e7d) · decision 4
**Owner:** BE · **Depends on:** S-01

- [ ] Migration `stock_movements` (material FK, type, occurred_on, quantity, quantity_before/after, unit_price, unit_cost_before/after, reference, note, user_id)
- [ ] Action `RestockMaterial` (transaction: stock + optional unit cost = latest price paid, restock + price_change movements); `UpdateMaterial` logs corrections and price changes; `CreateMaterial` logs `created`
- [ ] `MaterialRestockController` and material `history` / `recent_history` on Eloquent; tests incl. PostgreSQL parity

## S-15-BE · Photo storage — backend — `review` (approved by the tech-lead 2026-10-13; includes F-36; no thumbnails, F-39) · decision 2
**Owner:** BE · **Depends on:** S-04 (products part)

- [ ] `photo_path` on materials and products; public disk + `storage:link`; upload, replace (delete the old file), remove; delete files with the item; `photo_url` accessor; tests with `Storage::fake()`

## S-06-BE · Dashboard — backend — `review` (approved by the tech-lead 2026-10-13)
**Owner:** BE · **Depends on:** S-04, S-14-BE

- [ ] `DashboardController` on real data (stats, top products, needs restock, recent sales, alerts) with the same props as Phase 2; tests

## S-07-BE · Material usage — backend — `review` (approved by the tech-lead 2026-10-13) · decision 3
**Owner:** BE · **Depends on:** S-04

- [ ] `materials.show` `usage` (effective qty, units covered, capacity, bottleneck, next limit) from real compositions in one eager-loaded query; tests

## S-17 · Remove the preview layer — `review` (approved by the tech-lead 2026-10-13; demo seed lives in DemoProductsSeeder/DemoSalesSeeder, DemoCatalogSeeder untouched) · **release blocker**
**Owner:** BE + TL · **Depends on:** all Phase 3 stories

- [ ] Delete `app/Mock`, `public/mock/`, the `access-preview` ability and preview-only tests; move the demo fixture into `DemoCatalogSeeder`; arch test that nothing references `App\Mock`

---

## Phase 4

## S-08 · E2E smoke + demo data — `todo`
**Owners:** FE (browser test), BE (seeder) · **Depends on:** Phase 3

- [ ] BE: `DemoCatalogSeeder` grows to the full Phase 2 fixture (12 materials, 7 products, 60 sales, history) on real tables
- [ ] FE: Pest 4 browser test (golden path: login → create 2 materials → create a product with BOM → product page shows the expected cost and capacity → record a sale → dashboard totals)

---

## Phase 3P / 3R — Performance, SPA data loading, reference data (all `Proposed`, 2026-10-08)
Plan with the measurements, the ER diagram and full acceptance criteria: [plans/phase-3-performance-spa-data.md](plans/phase-3-performance-spa-data.md).
ADR drafts (Proposed): [0007 dev runtime](adr/0007-dev-runtime-native-serving.md) · [0008 reference data](adr/0008-reference-data-in-database.md) ·
[0009 SPA data loading](adr/0009-spa-data-loading-on-inertia.md) · [0010 database architecture](adr/0010-database-architecture.md).
Order: S-18 ∥ S-19 ∥ S-20 (now) → S-21a → S-21b ∥ S-22 → S-04 → S-14-BE ∥ S-16-BE ∥ S-15-BE ∥ S-25 → S-23 → S-06-BE ∥ S-07-BE → S-24 → S-17 → S-27 ∥ S-08 → S-26.
The plan's §F.3 lists the amendments to S-04, S-14-BE, S-15-BE, S-16-BE, S-06-BE and S-07-BE. They are proposals; the stories above keep their status until the human approves.

## S-18 · Dev runtime performance — `review` (repo side approved 2026-10-09; waiting for the human's machine steps, SETUP.md §4.1) · size S
**Owners:** TL (docs, ADR-0007), human (machine config) · **Depends on:** decision Q1
- [ ] Serve natively (Laragon web server + OPcache) against Docker PostgreSQL/Mailpit, or the option the human picks; SETUP.md section; Xdebug off by default
- **Acceptance:** with 3 s between requests, `/login` and authenticated pages p95 ≤ 150 ms (today 2.1 s); the parity suite still runs in the container

## S-19 · SPA navigation quick wins — frontend — `review` (approved by the tech-lead 2026-10-09) · size S
**Owner:** FE · **Depends on:** — (parallel with S-18, S-20)
- [ ] Exclude `*.test.tsx` from the pages glob (F-13); `prefetch` + `cacheTags` on CTAs, row links, breadcrumbs, KPI links; `invalidateCacheTags` on every write; `only` partial reloads for list filters and the dashboard period; `showProgress: false` on prefetch
- **Acceptance:** no test chunks in `public/build/manifest.json`; Vitest asserts the router options

## S-20 · Lazy props, query budgets, explicit `auth.user` — backend — `review` (approved by the tech-lead 2026-10-09) · size S
**Owner:** BE · **Depends on:** — (parallel with S-18, S-19)
- [ ] Closures for non-trivial props (no contract change); F-03 explicit `auth.user`; `expectQueryBudget()` Pest helper + one test per page; partial-reload tests
- **Acceptance:** a filter-only partial reload of `/materials` doesn't run the inventory summary; every page ≤ 10 queries

## S-21 · Reference data in the database — backend — `review` (approved by the tech-lead 2026-10-09) · size L
**Owner:** BE · **Depends on:** decisions Q2, Q3, Q7 · **Blocks:** S-04 (products are created with `category_id`)
- [ ] S-21a contract wave: `ReferenceData` types, `reference` shared once-prop, settings routes, page stubs, FE type-compat edits
- [ ] S-21b: `units` / `material_categories` / `product_categories` with defaults in migrations; `UnitDimension`; materials backfilled to FKs (reversible); Actions with in-use guard and version bump; policies; unit locked while used; `App\Mock` + `DemoCatalogSeeder` on codes; old enums deleted; PG `pg_trgm` + CHECKs
- **Acceptance:** existing materials keep their unit and category; archive/delete rules; the once-prop is resent only after a version bump; PG parity green

## S-22 · Reference data — UI — `review` (approved by the tech-lead 2026-10-09; includes F-27) · size M
**Owner:** FE · **Depends on:** S-21a (parallel with S-21b)
- [ ] Settings "Catalogue": Units, Material categories, Product categories (table, create/edit dialog, archive/restore, delete confirm with reason); forms and filters read `reference`, submit ids, hide archived options
- **Acceptance:** add "Elastic" and "inch" without a deploy and use them on a material; 375 px, dark mode, keyboard

## S-23 · `product_metrics` read model — `Proposed` · size M
**Owner:** BE · **Depends on:** S-04, S-16-BE · **Blocks:** S-06-BE, S-07-BE
- [ ] Table + `RefreshProductMetrics` in every costing-relevant write transaction + `invory:metrics:rebuild`; the products index filters, sorts and paginates in SQL; versioned cache for the inventory summary and period sales totals
- **Acceptance:** metrics equal the calculator (golden example) after every write path; dashboard and products index within the query budget

## S-24 · Deferred and on-demand data — `Proposed` · size M
**Owners:** BE + FE (one vertical commit) · **Depends on:** S-06-BE, S-07-BE
- [ ] Dashboard secondary panels deferred; material history deferred with "Load more"; product recent sales deferred / `WhenVisible`; optimistic delete for sales and archive for reference items

## S-25 · Material picker server-side search — `review` (approved by the tech-lead 2026-10-13; started in the `wip` commit 2240e7d) · size M
**Owners:** BE + FE · **Depends on:** S-04
- [ ] `GET /materials/options?search=&limit=20` (policy, Resource); the BOM picker uses `useHttp` (debounced); the `materials` prop is removed from product create/edit
- **Acceptance:** product create JSON ≤ 5 KB at any catalogue size; render test with 50 rows / 2 000 materials

## S-26 · Production runtime — `Proposed` · size M (Phase 4)
**Requirement (recorded 2026-10-09):** the production PostgreSQL must be 13+ with the app role owning the database (so `pg_trgm` installs itself), or a superuser runs `CREATE EXTENSION pg_trgm;` once before the first migrate.
**Owners:** TL (ADR) + BE
- [ ] Production image (FrankenPHP or php-fpm + nginx), OPcache without timestamp checks, `artisan optimize`, classmap-authoritative autoload, immutable + Brotli static assets, sessions/cache store decision, health check

## S-27 · Performance dataset and budgets — `Proposed` · size S (Phase 4)
**Owners:** BE + TL · **Depends on:** S-06-BE, S-23
- [ ] Dev-only `PerformanceDatasetSeeder` (2 000 materials, 500 products, 50 000 sales, 20 000 movements); probe p50/p95 per page; record the results against the budgets in the plan §B.4

---

## Stretch (after MVP sign-off)
- **S-09 Live cost preview** in the BOM editor: `POST /products/cost-preview` (debounced) returns a `CostBreakdown`; the UI shows it while editing (keeps the server as the single source of truth).
- **S-10 Production planner:** "Plan N units" on the product page shows per-material requirement vs stock and the shortage to buy.
- **S-11 CSV import/export** for materials.

## Later (see ARCHITECTURE.md §10)
Suppliers and purchase orders · unit conversions · sizes and variants · multi-level BOM · roles and permissions · cost history snapshots ·
denormalized product metrics for large catalogs · REST API · multi-company (teams). (The stock movement ledger and product images were
pulled forward on 2026-10-08 as S-16 and S-15.)

## Follow-ups from reviews
_(tech-lead appends MINOR/NIT items here)_

From the Phase 1 review (2026-10-08):
- **F-01 (MINOR, FE)**: `components/nav-main.tsx` uses exact URL matching, so "Materials" isn't highlighted on `/materials/{id}`, `/create` or `/edit`. Use parent-URL matching for non-dashboard items (do it with S-05 when Products is added).
- **F-02 (MINOR, FE)**: `components/nav-footer.tsx` has been unused since S-00 removed the starter-kit footer links. Delete it.
- **F-03 (MINOR, BE, security)**: `HandleInertiaRequests` shares the full `User` model as `auth.user` (starter-kit default). Share an explicit array or resource (id, name, email, avatar, email_verified_at) so no future column leaks into props.
- **F-04 (MINOR, FE tooling)**: the shadcn CLI/registry currently emits `import { cn } from "cn"` and umbrella `radix-ui` imports, and tries `pnpm`. S-02a hand-fixed the imports in `ui/{table,textarea,alert-dialog}.tsx`. Find a working setup before S-05 needs `command` and `popover`.
- ~~**F-05 (NIT, BE)**: `SharedPropsTest` default-currency test depended on the local env.~~ **Done 2026-10-08**, with the TND default change: the test now evaluates `config/invory.php` against an isolated env repository.
- ~~**F-10**: money display precision for TND.~~ **Done 2026-10-08**: the human chose each currency's own minor-unit digits (TND 3, EUR 2). `formatMoney` reads them from Intl, and `precise` shows 4 decimals below one minor unit. Skill rule 7, ADR-0002 and the worked example are updated.
- **F-06 (NIT, BE)**: `%` and `_` in the materials search act as LIKE wildcards (values are bound, so this is safe). Escape them if users complain.
- **F-07 (NIT, BE)**: `UpdateMaterial::handle()` returns the model without `refresh()`, so decimals on the returned instance are the raw input strings. The redirect makes this harmless today; use `refresh()` if a caller ever reads the return value.
- **F-08 (NIT, repo)**: a coverage run from Git Bash once wrote a `C`+U+F03A directory at the repo root. Add a `.gitignore` guard and document the coverage command in `docs/SETUP.md`: `docker compose exec -u sail -e XDEBUG_MODE=coverage laravel.test php -d pcov.enabled=0 vendor/bin/pest tests/Unit/Domain/Costing --coverage --path-coverage`.
- **F-09 (NIT, FE tooling)**: `npm ls` flags vitest's nested `vite@8.3.3` as "invalid" because of the `vite` → `@voidzero-dev/vite-plus-core@0.3.3` alias and `$vite` override that `vite-plus@0.3.3` requires. Cosmetic; revisit when upgrading vite-plus.
- **S-04 reminder**: add the `MaterialInUseException` guard to `DeleteMaterial` plus the error-toast `back()` branch in `MaterialController::destroy`. The S-02 delete dialog already closes on `onFinish`, so the flash error will show.

From the Phase 2 planning (2026-10-08):
- F-01 and F-02 are scheduled in S-12.
- **F-11 (scope, FE + BE)**: the mockup's sales period select has "Custom range…". Phase 2 ships the four presets only. Add `period=custom` with `from` / `to` dates (validated, `from ≤ to ≤ today`) when the human asks for it.
- **F-12 (docs)**: `invory-react-frontend` skill still says `CapacityBadge` 0 → "Out of stock" and places it in `components/products/`. The approved mockup uses "Can't produce" and S-12 puts it in `components/shared/`. Update the skill text.

From the Phase 2 review (2026-10-08, all MINOR/NIT, none blocking):
- **F-13 (MINOR, FE tooling)**: the `@inertiajs/vite` pages glob bundles `pages/**/*.test.tsx` into `public/build` (lazy chunks, never loaded, but shipped). Exclude test files from the glob or move page tests out of `pages/`.
- **F-14 (MINOR, FE)**: the "Record a sale" panel sits beside the table from `lg`; at 1024 px with the sidebar open the 7-column table scrolls inside its card. Revisit the breakpoint (`components/sales/use-wide-screen.ts`) after the human's click-through.
- **F-15 (MINOR, FE)**: `components/materials/form-field.tsx` and `components/products/form-field.tsx` (and the decimal input / currency affix helpers) duplicate each other: extract to `components/shared`. Move `components/ui/popover-command.test.tsx` out of the generated `ui/` folder.
- **F-16 (MINOR, FE a11y)**: the sales filter's visually hidden "Product" label has the same accessible name as the sale form's "Product" field. Rename it "Filter by product".
- **F-17 (NIT, FE)**: the sale form shows the server's unit price as `89.0000`, while the product form trims to `89` with the string-only `trimDecimal`. Reuse it for consistency.
- **F-18 (NIT, FE)**: primary buttons hover lighter (shadcn `bg-primary/90`) instead of the mockup's darker `#1E2A66`. Add a `--primary-hover` token if the human wants the mockup's hover.
- **F-19 (NIT, contract)**: Laravel's `meta.links` entries carry a `page` key that `PaginationLink` doesn't declare. Add `page?: number | null`.
- **F-20 (decided 2026-10-09: optional, default true; done in S-16-BE)**: `StoreRestockRequest` requires `update_unit_cost`; the skill says "boolean (default true)". Decide whether an omitted value means true. The UI always sends it.
- **F-21 (NIT, copy)**: when the next limit equals the current capacity (a tie at the bottleneck), the suggestion still reads "the next limit is X at N units". Consider a tie-specific sentence.

From the Phase 3 performance planning (2026-10-08):
- F-03 is fixed by S-20 and F-13 by S-19 (2026-10-09).

From the Phase 3P review (2026-10-09, MINOR/NIT, none blocking):
- **F-22 (MINOR, FE tests)**: `product-form.test.tsx` "posts the typed values, the selling price and the composition without row keys" failed once
  (passed on 4 reruns) when the Vitest suite ran at the same time as Pest. It's a long `user.type` test near the 5 s default timeout. Give the
  heavy form tests a larger timeout or type less, and run the gates sequentially.
- **F-23 (NIT, FE build)**: the `wayfinder-*.js` chunk is now 377 KB raw (118 KB gzip). That's chunk renaming, not growth: React DOM moved into it, and
  the entry closure is unchanged at ~565 KB raw / 176 KB gzip. Consider `manualChunks` naming (vendor-react, vendor-inertia) for readable reports.
- **F-24 (NIT, BE)**: `InventorySummary` reads with `lazyById(500)`, so the materials-index query count grows by one per 500 materials. That's within
  budget until S-23 caches the summary.
- **F-25 (docs, main session)**: `CLAUDE.md` and the agent definitions still describe Sail as the runtime and give the parity command without
  `--profile app`. Update them to match SETUP.md §4–§5 (ADR-0007).

From the S-21 review (2026-10-09, MINOR/NIT, none blocking):
- **F-26 (MINOR, BE perf)**: `Material::$with = ['category','unit']` adds 2 queries to every page that loads materials, and the `reference`
  once-prop adds 3 queries on full loads (SPA visits that hold the key skip them). All pages are within the ≤ 10 budget. Option: resolve the labels
  from a version-keyed cached `ReferenceData` payload (0 queries on a hit). Revisit with S-23 / S-27.
- ~~**F-27**: restore name clash, archive re-stamp.~~ **Done in S-22 (2026-10-09)**: restore is refused with a `name` error when an active row has
  the name; archiving an archived row is a no-op.
- **F-28 (S-22 note)**: system units only accept `name` and `position` on update (`dimension`/`factor_to_base` are `prohibited`, even unchanged);
  `factor_to_base` arrives as a scale-12 string (`"0.010000000000"`), so S-22 trims it for display.
- **F-29 (S-04 note)**: S-04 swaps the MOCK usage sources: `UpdateMaterialRequest::compositionsUsing()` (Q7 lock),
  `PreviewFixture::productCountsByCategory()` (product-category usage and delete guard, passed into `DeleteProductCategory`). S-04 also takes
  over `pg_trgm`, PostgreSQL CHECKs and `created_by`/`updated_by` (moved out of S-21).

From the S-22 review (2026-10-09, MINOR/NIT, none blocking):
- ~~**F-30**~~ **Done in S-22b (2026-10-09)**: restoring an already-active row still writes `archived_at = null` and bumps the reference version (one needless client
  refetch). Add the same early return as archive.
- **F-31 (UX)** — human 2026-10-09: q1 keep cards below `xl` (no change); q2 the "(archived)" suffix, **done in S-22b**; q3 the "Manage units" link: **not now** (human, 2026-10-09), stays in the backlog. Original note: the settings tables switch from cards to a table only at `xl` (the sidebar plus the settings menu
  leave ~500 px at `md`/`lg`); a kept archived option shows its plain name, with no "(archived)" suffix; there's no "Manage units" link in the
  material form yet (needs `FormField.hint` to accept a node). Restoring a deleted-in-error item needs archive → delete, by design.
- ~~**F-32**~~ **Done in S-22b (2026-10-09)**: names are unique case-insensitively among active rows (rule `UniqueActiveName`, folded in SQL,
  plus partial unique indexes on `lower(name)`). Original note: name uniqueness for reference rows is case-sensitive as the database compares (create, update and restore agree). Make it
  case-insensitive in all three places if the human wants.

From the S-22b review (2026-10-09):
- ~~**F-33**~~ **Done (2026-10-09)**: a unique-index clash on a reference name or code during create, update or restore now returns the
  rule's own validation error (`App\Services\Reference\ReferenceNameGuard`). Original note: two simultaneous creates of the same name both pass validation; the second hits the new `lower(name)` index and returns a
  500 instead of a `name` error. Catch `UniqueConstraintViolationException` in the reference Actions/controllers and map it to a validation error.
- **F-34 (note)**: PostgreSQL's `lower()` folds non-ASCII letters and SQLite's doesn't, so "Étiquette" and "étiquette" clash on PostgreSQL only.
  Tests stay ASCII; production (PostgreSQL) behaves as intended.

From the S-04 review (2026-10-09, MINOR/NIT, none blocking):
- **F-35 (MINOR, BE)**: two simultaneous creates with the same product (or material) SKU both pass validation and the second hits the partial
  unique index → 500. Add an F-33-style fallback that maps it to a `sku` validation error.
- **F-36 (S-15-BE note)**: a multipart update (photo attached) can't carry an empty `components` list, so clearing the composition while
  uploading a photo leaves it unchanged. S-15-BE must send `components` explicitly (form side) or use a sentinel.
- **F-37 (NIT, seed)**: `DemoProductsSeeder` recreates a demo product whose SKU exists only soft-deleted, and demo products are dated "now"
  (the fixture said 37 days ago). Fine for demo data; revisit with S-08.
- **F-38 (perf note)**: the dashboard and material show sit exactly at the 10-query budget (preview adapters over real products) until
  S-06-BE/S-07-BE replace the adapters; the products index costs the whole filtered set when `cant_produce` is on (interim until S-23).
  **Update 2026-10-13:** both pages are real and at ≤ 10 via request-scoped units and material categories (`ReferenceData`). The
  dashboard still costs every product and the sales totals walk every row of the period in PHP (O(n)) until S-23.

From the Phase 3 real-backend review (2026-10-13, MINOR/NIT, none blocking):
- **F-35 (MINOR, BE, deferred by the human 2026-10-13, not blocking Phase 3)**: two simultaneous creates with the same material or product
  SKU both pass validation; the second hits the partial unique index → 500. Map `UniqueConstraintViolationException` to a `sku` validation
  error (F-33 pattern, `ReferenceNameGuard`). Schedule as its own follow-up story.
- ~~**F-39**~~ **Decided 2026-10-13 (human): skip.** Original photos ship as-is (up to 4 MB, 25 per list page); no `photo_thumb_url`, no
  contract change. Revisit only if list pages get slow (S-27 measures it).
- ~~**F-40**~~ **Decided 2026-10-13 (human): no reason field.** The costing skill no longer mentions an "optional reason" for a `correction`;
  corrections log before → after only.
- **F-41 (MINOR, domain)**: `MaterialUsageService` takes `effective_quantity` / `units_covered` from the material's single line (DB-unique), because
  the arch rule forbids arithmetic in `App\Services\Costing`. Expose the summed requirement per material from the domain
  (`CostingCalculator`) so the "same material on two lines" edge case is honoured by construction.
- **F-42 (MINOR, BE arch)**: `DashboardController` shapes `TopProduct`, `UnproducibleAlert` and `ProductRef` in private methods. Move them to
  Resources so the controller is thin again.
- **F-43 (NIT, BE)**: `PhotoStorage::persist()` deletes the old file when the Action's transaction returns; if an Action is ever called inside an
  outer transaction, use `DB::afterCommit`. Two concurrent photo edits of one material can leave one orphan file.
- **F-44 (NIT, FE)**: after a pick, the material picker fires one more `include` request before the popover unmounts (cancelled, harmless).
- **F-45 (NIT, tests)**: stale "preview" wording in test comments/names (`MaterialPreviewDataTest`, `previewFixtureMaterials()`,
  `DashboardTest` header…), `/mock/photos/...` strings in Vitest fixtures, and two dashboard test files (`tests/Feature/DashboardTest.php`
  and `tests/Feature/Dashboard/DashboardTest.php`).
- **F-46 (NIT, BE)**: `RecordSale` doesn't re-check that the product is still active inside its transaction (validation already did).

