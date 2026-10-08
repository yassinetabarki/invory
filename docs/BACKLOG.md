# Invory — MVP backlog

> Owner: **tech-lead**. Status: `todo` → `in progress` → `review` → `done`.
> Every story follows the Definition of Done in [ARCHITECTURE.md §8](ARCHITECTURE.md#8-quality-testing-and-definition-of-done).
> BE = backend-developer, FE = frontend-developer, TL = tech-lead.

## MVP goal
A logged-in user can maintain a materials inventory, define products and their composition, and see for every product
its **unit cost**, **how many units can be produced** from current stock, and **which material is the bottleneck**.

## Delivery plan (phases → waves; the tech-lead dispatches, see [TEAM.md](TEAM.md))

| Phase | Stories | Plan | Status |
|---|---|---|---|
| **1 — Foundation + Materials** | S-00, S-01, S-02, S-03 | [plans/phase-1-foundation-materials.md](plans/phase-1-foundation-materials.md) | **review: all 4 stories approved by the tech-lead (2026-10-08), awaiting human commit approval** |
| 2 — Products + composition | S-04, S-05 | written by the tech-lead before Phase 2 | todo |
| 3 — Dashboard, "used in", E2E | S-06, S-07, S-08 | written by the tech-lead before Phase 3 | todo |

```
Phase 1  Wave 1: S-00 (BE ∥ FE ∥ TL)
         Wave 2: S-01 (BE #1) ∥ S-03 (BE #2) ∥ S-02a components (FE)
         Wave 3: S-02b pages (FE, needs S-01)
Phase 2  S-04 Products + BOM backend (BE, needs S-01 + S-03) → S-05 Products UI + BOM editor (FE; components can start contract-first)
Phase 3  S-06 Dashboard (BE → FE) · S-07 Material "used in" (BE → FE) · S-08 E2E smoke + demo seed
```
The subtask-level breakdown (file lists, contract, checks) lives in the phase plan. This backlog keeps the story-level view.

---

## S-00 · Foundation — `review` (approved by TL, awaiting commit) (Phase 1, Wave 1)
**Owners:** BE, FE, TL · **Depends on:** project scaffolded ✅ · Subtasks: [plan §S-00](plans/phase-1-foundation-materials.md#s-00--foundation--wave-1)

- [ ] BE: `config/invory.php`; `Model::shouldBeStrict`, `JsonResource::withoutWrapping()`; share `app.currency` / `app.locale`; **full Phase 1 contract in `domain.ts`**; arch tests
- [ ] FE: Vitest via Vite+ (`npm test`), Inertia test helper, `lib/format.ts`, `<Money>` / `<Quantity>`, Invory branding (logo text, landing page, remove starter-kit footer links)
- [ ] TL: extend `.github/workflows/tests.yml` (PostgreSQL job, PHP 8.3 + 8.5, `vp test`, build); triage `npm audit`; review
- Toasts already work through the starter kit's native `Inertia::flash('toast', …)` + `<Toaster>`, so there's nothing to build.

**Acceptance:** all gates green; shared props asserted; contract types in place for Wave 2.

---

## S-01 · Materials — backend — `review` (approved by TL, awaiting commit) (Phase 1, Wave 2)
**Owner:** BE · **Depends on:** S-00 · Subtasks: [plan §S-01](plans/phase-1-foundation-materials.md#s-01--materials--backend--wave-2-be-1--size-m)

- [ ] Enums `MaterialCategory`, `UnitOfMeasure`, `StockStatus`; migration with a partial unique SKU index; model, factory, policy
- [ ] `IndexMaterialRequest` (filters + sort), `Store/UpdateMaterialRequest`; actions `CreateMaterial`, `UpdateMaterial`, `DeleteMaterial` (soft delete; S-04 adds the "in use" guard)
- [ ] `MaterialController` (resource) + `MaterialResource` matching `domain.ts` (`stock_value`, `stock_status`); toasts via `Inertia::flash`; page stubs
- [ ] Feature tests: guests, filters/sort/pagination, contract, CRUD, validation datasets, stock status, DB-level SKU uniqueness

**Acceptance:** all CRUD routes work behind auth; decimals round-trip as scale-4 strings; contract asserted; PostgreSQL parity green.

## S-02 · Materials — UI — `review` (approved by TL, awaiting commit) (Phase 1, Waves 2–3)
**Owner:** FE · **Depends on:** S-00 (S-02a), S-01 (S-02b) · Subtasks: [plan §S-02](plans/phase-1-foundation-materials.md#s-02--materials--ui)

- [ ] S-02a (components, contract-first): `StockBadge`, `MaterialsTable` (responsive), `FiltersBar` + `useDebouncedValue`, `Pagination`, `EmptyState`, `PageHeader`, `MaterialForm`, `ConfirmDeleteDialog`
- [ ] S-02b (pages): `materials/{index,create,edit,show}` wired with Wayfinder; sidebar **Materials** item; delete flow; page tests

**Acceptance:** a user can add, find, edit and delete materials without touching the URL bar; usable at 375 px and in dark mode; keyboard-only flow works.

## S-03 · Costing engine (pure domain) — `review` (approved by TL, awaiting commit) (Phase 1, Wave 2)
**Owner:** BE · **Depends on:** S-00 (independent of S-01) · Subtasks: [plan §S-03](plans/phase-1-foundation-materials.md#s-03--costing-engine--wave-2-be-2--size-m)

- [ ] `App\Domain\Costing`: `BomLine`, `LineCost`, `CostBreakdown`, `ProductionCapacity`, `CostingResult`, `CostingCalculator` (BigDecimal, no Illuminate)
- [ ] Unit tests first: the worked example (exact strings), every edge case in the domain skill, aggregation, tie-break, magnitudes

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

From the Phase 1 review (2026-10-08):
- **F-01 (MINOR, FE)**: `components/nav-main.tsx` uses exact URL matching, so "Materials" isn't highlighted on `/materials/{id}`, `/create` or `/edit`. Use parent-URL matching for non-dashboard items (do it with S-05 when Products is added).
- **F-02 (MINOR, FE)**: `components/nav-footer.tsx` has been unused since S-00 removed the starter-kit footer links. Delete it.
- **F-03 (MINOR, BE, security)**: `HandleInertiaRequests` shares the full `User` model as `auth.user` (starter-kit default). Share an explicit array or resource (id, name, email, avatar, email_verified_at) so no future column leaks into props.
- **F-04 (MINOR, FE tooling)**: the shadcn CLI/registry currently emits `import { cn } from "cn"` and umbrella `radix-ui` imports, and tries `pnpm`. S-02a hand-fixed the imports in `ui/{table,textarea,alert-dialog}.tsx`. Find a working setup before S-05 needs `command` and `popover`.
- ~~**F-05 (NIT, BE)**: `SharedPropsTest` default-currency test depended on the local env.~~ **Done 2026-10-08**, with the TND default change: the test now evaluates `config/invory.php` against an isolated env repository.
- **F-10 (pending product decision)**: TND's ISO minor unit is 3 decimals, but `formatMoney` always shows 2 (skill rule 7). If the human chooses 3, change: `lib/format.ts` (use the currency's own fraction digits; `precise` threshold = below one minor unit), costing-domain skill rule 7 + worked-example text, the pinned "TND, en" tests in `format.test.ts`, and ADR-0002's amendment note. Storage and serialization (scale 4) are unaffected.
- **F-06 (NIT, BE)**: `%` and `_` in the materials search act as LIKE wildcards (values are bound, so this is safe). Escape them if users complain.
- **F-07 (NIT, BE)**: `UpdateMaterial::handle()` returns the model without `refresh()`, so decimals on the returned instance are the raw input strings. The redirect makes this harmless today; use `refresh()` if a caller ever reads the return value.
- **F-08 (NIT, repo)**: a coverage run from Git Bash once wrote a `C`+U+F03A directory at the repo root. Add a `.gitignore` guard and document the coverage command in `docs/SETUP.md`: `docker compose exec -u sail -e XDEBUG_MODE=coverage laravel.test php -d pcov.enabled=0 vendor/bin/pest tests/Unit/Domain/Costing --coverage --path-coverage`.
- **F-09 (NIT, FE tooling)**: `npm ls` flags vitest's nested `vite@8.3.3` as "invalid" because of the `vite` → `@voidzero-dev/vite-plus-core@0.3.3` alias and `$vite` override that `vite-plus@0.3.3` requires. Cosmetic; revisit when upgrading vite-plus.
- **S-04 reminder**: add the `MaterialInUseException` guard to `DeleteMaterial` plus the error-toast `back()` branch in `MaterialController::destroy`. The S-02 delete dialog already closes on `onFinish`, so the flash error will show.
