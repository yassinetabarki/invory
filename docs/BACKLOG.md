# Invory — MVP backlog

> Owner: **tech-lead**. Status: `todo` → `in progress` → `review` → `done`.
> Every story follows the Definition of Done in [ARCHITECTURE.md §8](ARCHITECTURE.md#8-quality-testing-and-definition-of-done).
> BE = backend-developer, FE = frontend-developer, TL = tech-lead.

## MVP goal
A logged-in user can maintain a materials inventory, define products and their composition, and see for every product
its **unit cost**, **how many units can be produced** from current stock, and **which material is the bottleneck**.

## Delivery plan (what can run in parallel)

```
Sprint 0   S-00  Foundation (BE ∥ FE ∥ TL)
Sprint 1   S-01  Materials backend (BE)      ∥   S-03  Costing engine (BE, after S-01 or parallel in a 2nd session)
           S-02  Materials UI (FE, after S-01)
Sprint 2   S-04  Products + BOM backend (BE, needs S-01 + S-03)
           S-05  Products UI + BOM editor (FE, after S-04)
Sprint 3   S-06  Dashboard (BE → FE) · S-07 Material "used in" (BE → FE) · S-08 E2E smoke (FE) · demo seed polish
```

---

## S-00 · Foundation — `todo`
**Owners:** BE, FE, TL · **Depends on:** project scaffolded ([SETUP.md](SETUP.md))

BE
- [ ] `config/invory.php` (`currency` from `APP_CURRENCY`, default `EUR`; `per_page` = 25); share `app.currency` and `app.locale`, plus `flash.success` and `flash.error`, in `HandleInertiaRequests`
- [ ] `Model::shouldBeStrict(! app()->isProduction())`, `DB::prohibitDestructiveCommands(app()->isProduction())` in `AppServiceProvider`
- [ ] `tests/Arch/ArchTest.php` with presets and the layer rules from the backend skill
- [ ] Larastan stays at the starter kit's level 7 or higher, green; `composer test` green (baseline on the fresh scaffold: Pint, Larastan 0 errors, 40 Pest tests)

FE
- [ ] Vitest via Vite+: jsdom + Testing Library installed, `test` block in `vite.config.ts`, `resources/js/test/setup.ts`, `resources/js/test/inertia.tsx` helper, npm script `"test": "vp test"`
- [ ] `lib/format.ts` + tests (worked example → `€35.75`); `<Money>` / `<Quantity>` shared components
- [ ] `use-flash-toasts` hook wired into the app layout (sonner)
- [ ] Sidebar nav: Dashboard, Materials, Products (lucide icons); routes may be stubs until S-01/S-04
- [ ] `resources/js/types/domain.ts` skeleton: `Decimal`, `Paginated<T>`, `Option` (BE owns it from then on)

TL
- [ ] CI: the starter kit already ships `.github/workflows/tests.yml` (PHP 8.3 + Node 22, `composer setup` then `composer ci:check`, on SQLite). Extend it rather than replace it: add a job (or matrix entry) that runs the suite **against a PostgreSQL service** (job env `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_DATABASE=testing`, …, which override `phpunit.xml`) on PHP 8.3 **and** 8.5, and add `npx vp test` and `npm run build` to the Node checks (`ci:check` already runs `npm run check`, `npm run types:check` and the PHP gates)
- [ ] Verify the Sail stack: `.\sail up -d`, `.\sail artisan migrate`, app at http://localhost, Mailpit receives the password-reset email
- [ ] Review S-00

**Acceptance:** all gates green locally and in CI; the app boots; the nav shows the three sections.

---

## S-01 · Materials — backend — `todo`
**Owner:** BE · **Depends on:** S-00

- [ ] Enums `MaterialCategory`, `UnitOfMeasure` (with `label()`, `options()`)
- [ ] Migration, model (soft deletes, casts), factory (realistic, `lowStock()` / `outOfStock()` states), policy
- [ ] `Store/UpdateMaterialRequest`; actions `CreateMaterial`, `UpdateMaterial`, `DeleteMaterial` (plain soft delete for now; S-04 adds the "in use" guard once `product_components` exists)
- [ ] `MaterialController` (index with `search`, `category`, `low_stock` filters + pagination; create/store/show/edit/update/destroy)
- [ ] `MaterialResource` with `stock_value` and `is_low_stock`; `domain.ts` updated; page stubs
- [ ] Feature tests: guests redirected; list + filters + pagination; create/update happy path; validation datasets (negative cost, >4 decimals, unknown enum, duplicate SKU, SKU reusable after soft delete); delete

**Acceptance:** all CRUD routes work behind auth; decimals round-trip as scale-4 strings; contract asserted in tests.

## S-02 · Materials — UI — `todo`
**Owner:** FE · **Depends on:** S-01

- [ ] `materials/index`: table (SKU, name, category, stock with unit, unit cost, stock value, low-stock badge), search (debounced), category and low-stock filters, pagination, empty state, "New material" button
- [ ] `materials/create` and `materials/edit`: `MaterialForm` (all fields, unit select, decimal inputs with unit suffix, inline errors)
- [ ] `materials/show`: details card (+ "Used in" placeholder until S-07)
- [ ] Delete with confirm dialog; flash toasts
- [ ] Tests: `StockBadge`, `MaterialForm` (renders errors, submits), filters behaviour

**Acceptance:** a user can add, find, edit and delete materials without touching the URL bar; usable at 375 px; keyboard-only flow works.

## S-03 · Costing engine (pure domain) — `todo`
**Owner:** BE · **Depends on:** S-00 (independent of S-01)

- [ ] `App\Domain\Costing`: `BomLine`, `LineCost`, `CostBreakdown`, `ProductionCapacity`, `CostingCalculator` (BigDecimal, no Illuminate)
- [ ] Unit tests: the worked example (exact strings), every edge case in the domain skill, aggregation of duplicate materials, tie-breaking, large/small magnitudes
- [ ] Arch test proves the namespace is framework-free

**Acceptance:** 100 % branch coverage of the domain; worked example passes exactly (`unit_cost = "35.7540"`, `max_producible = 112`, bottleneck = button).

## S-04 · Products + composition — backend — `todo`
**Owner:** BE · **Depends on:** S-01, S-03

- [ ] Enums `ProductCategory`, `ProductStatus`; migrations `products`, `product_components` (FKs, unique pair); models + factories (`withComponents()`)
- [ ] Actions `CreateProduct`, `UpdateProduct`, `DeleteProduct`, `SyncProductComponents` (transactional)
- [ ] `ProductCostingService` (Eloquent → `BomLine[]` → calculator)
- [ ] `ProductController`: index (paginated, filters `search`/`category`/`status`, each row with `unit_cost`, `max_producible`, `bottleneck`, `components_count`); show (composition lines with per-line effective qty, line cost, units possible, `is_bottleneck`; `cost` breakdown; `capacity`); create/edit (with `materials` options: id, sku, name, unit, unit_cost); store/update/destroy
- [ ] `DeleteMaterial` now blocks materials used by non-deleted products (+ test)
- [ ] `ProductListResource`, `ProductResource`, `ProductComponentResource`, `CostBreakdownResource`; `domain.ts` updated; page stubs
- [ ] Feature tests: N+1-safe index (strict mode on), worked example end-to-end through HTTP props, BOM validation (distinct material, qty > 0, waste < 100, trashed material rejected), sync removes and reorders lines, transaction rollback on failure

**Acceptance:** creating the Denim Jacket from the domain skill via HTTP yields exactly the expected props.

## S-05 · Products — UI + composition editor — `todo`
**Owner:** FE · **Depends on:** S-04

- [ ] `products/index`: table/cards with unit cost, `CapacityBadge`, bottleneck link, status badge, filters, pagination, empty state — **objective #4**
- [ ] `products/show`: header (name, SKU, status, category) + **can-produce hero number** + bottleneck callout; `BomTable` (material, qty + unit, waste %, effective qty, unit cost, line cost, stock, units possible, bottleneck row highlighted with text + icon); `CostBreakdownCard` (materials, labor, overhead, total) — **objective #2**
- [ ] `products/create` and `products/edit`: `ProductForm` + `BomEditor` (searchable material combobox, unit suffix, waste %, placement notes, add/remove/reorder rows, duplicate prevention, nested errors) — **objective #3**
- [ ] Tests: `CapacityBadge` states, `CostBreakdownCard` (worked example), `BomEditor` interactions, `ProductForm` error mapping

**Acceptance:** a user can define the Denim Jacket in under 2 minutes and immediately see €35.75 / 112 units / bottleneck "Metal shank button 17mm".

## S-06 · Dashboard — `todo`
**Owners:** BE → FE · **Depends on:** S-04

- [ ] BE: `DashboardController` props: `stats` (materials_count, inventory_value, low_stock_count, products_count, unproducible_count), `top_products` (by capacity, max 5), `low_stock_materials` (max 5); tests
- [ ] FE: KPI cards, top-products list, low-stock list, links to filtered list pages; tests

## S-07 · Material "used in" — `todo`
**Owners:** BE → FE · **Depends on:** S-04

- [ ] BE: `materials.show` adds `used_in` (product id, sku, name, quantity per unit, effective qty); test
- [ ] FE: "Used in" table on the material page; delete button explains why it's disabled when the material is in use

## S-08 · E2E smoke + demo data — `todo`
**Owners:** FE (browser test), BE (seeder) · **Depends on:** S-05

- [ ] BE: `DemoCatalogSeeder` (worked example + t-shirt, sneaker, leather tote, ~25 materials)
- [ ] FE: Pest 4 browser test (golden path: login → create 2 materials → create a product with BOM → product page shows the expected cost and capacity)

---

## Stretch (after MVP sign-off)
- **S-09 Live cost preview** in the BOM editor: `POST /products/cost-preview` (debounced) returns a `CostBreakdown`; the UI shows it while editing (keeps the server as the single source of truth).
- **S-10 Production planner:** "Plan N units" on the product page shows per-material requirement vs stock and the shortage to buy.
- **S-11 CSV import/export** for materials.

## Phase 2 (see ARCHITECTURE.md §10)
Stock movement ledger · suppliers and purchase orders · unit conversions · sizes and variants · multi-level BOM · product images ·
roles and permissions · cost history snapshots · denormalized product metrics for large catalogs · REST API · multi-company (teams).

## Follow-ups from reviews
_(tech-lead appends MINOR/NIT items here)_
