# Invory — Architecture

> Owner: **tech-lead** agent. Any change to this document goes through an ADR in [`docs/adr/`](adr/).
> Last updated: 2026-10-14 (one workshop per account, S-44/S-45/S-46, [ADR-0013](adr/0013-one-workshop-per-account.md); earlier: reference data in the database, S-21, [ADR-0008](adr/0008-reference-data-in-database.md))

## 1. Product in one paragraph

Invory is a costing tool for fashion products such as garments, shoes, bags and accessories. Users keep an
**inventory of primary materials** (fabric, trims, thread, leather, soles, labels, packaging…) with stock and
cost per unit. They define **final products** and the **bill of materials (BOM)** that composes each one.
The app then answers two questions for every product:

1. **What does one unit cost to make?** (materials + waste + labor + overhead)
2. **How many units can we produce right now** with the stock on hand, and **which material is the bottleneck**?

## 2. Tech stack (pinned)

| Layer | Choice | Notes |
|---|---|---|
| Runtime | PHP ≥ **8.3** (native 8.3 in dev; CI tests 8.3 and 8.5), Node **24** | Code must run on the lowest PHP in use (8.3). |
| Backend | **Laravel 13** | Laravel has no LTS since v6. v13 is the current major: bug fixes until Q3 2027, security fixes until Q1 2028. See [ADR-0001](adr/0001-stack.md). |
| Glue | **Inertia 3** (`inertiajs/inertia-laravel`, `@inertiajs/react`) | Server-side routing and controllers; React renders pages. No separate REST API in the MVP. |
| Frontend | **React 19** + **TypeScript** (strict) + **React Compiler** | From the official Laravel React starter kit. |
| Styling/UI | **Tailwind CSS 4** + **shadcn/ui** (Radix) + lucide icons + sonner toasts | |
| Typed routes | **Laravel Wayfinder** | Generates TypeScript functions for every controller action and named route. |
| Auth | **Laravel Fortify** (starter kit) | Login, registration, password reset, 2FA. One workshop per account ([ADR-0013](adr/0013-one-workshop-per-account.md)): registration creates the workshop. |
| Tooling (FE) | **Vite+** (`vp`): Vite 8, Oxlint, Oxfmt, **Vitest** | `npm run check`, `npm run types:check`, `npx vp test` |
| Tooling (BE) | **Pest 4**, **Larastan**, **Pint** | `composer test` |
| Database | **PostgreSQL** (Docker in dev, and production). SQLite in-memory for the fast test suite only. | Write portable SQL only. The parity suite runs on PostgreSQL locally (`composer test:pgsql`) and in CI. |
| Dev environment | Native PHP 8.3 + Composer (app on `php artisan serve` :8000 via `composer dev`, tooling, agents) + **Docker Compose** for PostgreSQL and Mailpit only | See [ADR-0012](adr/0012-dev-docker-db-and-mail-only.md) and [SETUP.md](SETUP.md). |
| AI tooling | **Laravel Boost** (MCP server + guidelines + skills) | Version-aware docs search, DB schema, logs. |

## 3. Architectural style

A **modular monolith, Laravel-conventional**: standard Laravel folders, plus three additions that keep the
code testable and ready to scale.

1. **Actions** (`app/Actions/<Module>/`): one class per write use case (`CreateMaterial`, `UpdateProduct`…).
   Controllers stay thin, and the same actions can later be reused by a REST API, CLI commands, imports or queued jobs.
2. **Pure costing domain** (`app/Domain/Costing/`): plain PHP with no Laravel, no Eloquent and no I/O. It holds
   all cost and capacity maths, so it can be unit-tested exhaustively without a database. See [ADR-0004](adr/0004-pure-costing-domain.md).
3. **Explicit contract** between backend and frontend: API Resources on the PHP side, mirrored by
   `resources/js/types/domain.ts` on the TS side. Pest tests (`assertInertia`) check the contract.

```
 Browser ── React 19 page (resources/js/pages/**)
    │  Inertia visit / form submit (Wayfinder-typed URL)
    ▼
 routes/web.php ── auth + verified middleware
    ▼
 Controller (thin) ── FormRequest (validate + authorize via Policy)
    │                        │
    │ reads                  │ writes
    ▼                        ▼
 Query / Eloquent      Action (use case, DB::transaction)
    │                        │
    ▼                        ▼
 ProductCostingService ──► Domain\Costing\CostingCalculator (pure PHP, BigDecimal)
    │
    ▼
 API Resource(s) ──► Inertia::render('module/page', props) ──► React page
```

### Layer rules (enforced by Pest `arch()` tests and by review)

| Layer | May depend on | Must NOT |
|---|---|---|
| `Domain/Costing` | PHP + `brick/math` only | import `Illuminate\*`, models, facades, `config()` |
| `Actions` | Models, Domain, Enums, Exceptions | read the HTTP request, return responses |
| `Http/Controllers` | Actions, Services, Resources, Requests | contain business rules, run raw queries, call `DB::` |
| `Http/Requests` | Enums, Rules, Policies | mutate data |
| `Http/Resources` | Models, Domain result objects | run queries (relations must be eager-loaded first) |
| `Models` | Workshop models use `BelongsToWorkshop` (global scope + stamping); workshop-limited validation goes through `App\Validation\WorkshopRule` | bypass the scope (`withoutGlobalScope(s)`, raw `DB::table()` on workshop tables, `Rule::exists/unique` on them outside `WorkshopRule`) |
| React `pages/` | components, hooks, types, Wayfinder | contain business maths (cost/capacity come from the server) |

## 4. Domain model

```
 ┌──────────────┐ 1      * ┌──────────────┐
 │   Workshop   │──────────│     User     │   Every row below also belongs to exactly one workshop (`workshop_id`, ADR-0013)
 └──────────────┘          └──────────────┘
 ┌──────────────┐ 1      * ┌────────────────────┐ *      1 ┌──────────────┐ 1      * ┌────────────────┐
 │   Product    │──────────│  ProductComponent  │──────────│   Material   │──────────│ StockMovement  │
 │ (final item) │          │     (BOM line)     │          │(primary item)│          │   (history)    │
 └──────────────┘          └────────────────────┘          └──────────────┘          └────────────────┘
        │ 1
        │ *
 ┌──────────────┐
 │     Sale     │  (does not change material stock)
 └──────────────┘
```
Status (2026-10-13): every table above is real: `sales` (S-14-BE), `stock_movements` (S-16-BE), `photo_path` on materials and products
(S-15-BE). The preview layer is gone (S-17). Demo data: `DemoCatalogSeeder` (materials) → `DemoProductsSeeder`, then `DemoSalesSeeder`.
Status (2026-10-09): `materials`, `products` and `product_components` are real (S-04; demo products from `DemoProductsSeeder`).
Earlier status (2026-10-08): `materials` is real. `products`, `product_components`, `sales`, `stock_movements` and the `photo_path` columns are
**designed here and served from preview fixtures** (`App\Mock`, [ADR-0006](adr/0006-ui-first-preview-data.md)) until their Phase 3 stories
(S-04, S-14-BE, S-16-BE, S-15-BE) create them.

### `workshops` and `workshop_id` (S-44, [ADR-0013](adr/0013-one-workshop-per-account.md))
| Table / column | Type | Rules |
|---|---|---|
| `workshops` | id, timestamps (S-46 adds `name` string(100)) | the business account; created by registration (`CreateWorkshop`) |
| `users.workshop_id` | FK → workshops, NOT NULL | `restrictOnDelete`; one workshop per account, many accounts per workshop |
| `workshop_id` on `materials`, `products`, `product_components`, `stock_movements`, `sales` (S-45: `units`, `material_categories`, `product_categories`) | FK → workshops, NOT NULL | `restrictOnDelete`; never mass-assignable; stamped from the current workshop, children take their parent's |

Every query on these tables is limited to the current workshop by the `BelongsToWorkshop` global scope (route-model binding included, so
another workshop's record is a 404). PostgreSQL also has `unique(workshop_id, id)` on `materials` and `products` and composite foreign keys
`product_components (workshop_id, product_id | material_id)`, `sales (workshop_id, product_id)`, `stock_movements (workshop_id, material_id)`
→ `(workshop_id, id)`, so a composition or a sale can never cross workshops (R4).

### `materials` (primary items)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| workshop_id | FK → workshops | see above |
| sku | string(64) | required, unique per workshop among non-deleted rows |
| name | string(150) | required |
| category_id | FK → material_categories | `restrictOnDelete`; forms and filters use the category **code** (S-21, ADR-0008) |
| unit_id | FK → units | `restrictOnDelete`; forms use the unit **code**; can't change while a BOM uses the material |
| unit_cost | decimal(12,4) | ≥ 0, cost per 1 `unit` |
| quantity_on_hand | decimal(14,4) | ≥ 0, in `unit` |
| reorder_level | decimal(14,4) nullable | low-stock threshold |
| supplier_name | string(150) nullable | becomes a `suppliers` table in phase 2 |
| supplier_reference | string(64) nullable | supplier's article code |
| color | string(64) nullable | |
| composition | string(255) nullable | e.g. "98% cotton, 2% elastane" |
| notes | text nullable | |
| photo_path | string(255) nullable | S-15-BE: public-disk path of the one photo (JPG/PNG/WebP, ≤ 4 MB); `photo_url` derived |
| timestamps, deleted_at | | soft deletes |

Indexes: `materials_sku_unique` on `(workshop_id, sku)` (partial unique `WHERE deleted_at IS NULL`, on PostgreSQL and SQLite), `index(category_id)`,
`index(unit_id)`, `index(name)`, `materials_restock_idx` on `(workshop_id, name, id)` (partial, the "needs restock" predicate).

### Reference tables (S-21, [ADR-0008](adr/0008-reference-data-in-database.md))
| Table | Columns |
|---|---|
| `units` | id, `code` string(16) unique (immutable), `name` string(64), `dimension` (`UnitDimension`), `factor_to_base` decimal(24,12) nullable, `is_system` bool, `position`, `archived_at`, timestamps |
| `material_categories` / `product_categories` | id, `code` string(32) unique (immutable), `name` string(64), `position`, `archived_at`, timestamps |

Defaults are inserted by the migration (frozen snapshot, `insertOrIgnore` by code). Writes go through `App\Actions\Reference\*`, which bump the
`reference` once-prop version (shared with signed-in users; [ADR-0009](adr/0009-spa-data-loading-on-inertia.md)).

### `products` (final items)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| workshop_id | FK → workshops | see above |
| sku | string(64) | required, unique per workshop among non-deleted rows (`products_sku_unique` on `(workshop_id, sku)`) |
| name | string(150) | required |
| category_id | FK → product_categories | `restrictOnDelete`; forms and filters use the category **code** |
| status | string(16) | `ProductStatus` enum: draft, active, archived (default active) |
| description | text nullable | |
| labor_cost | decimal(12,4) | ≥ 0, cost per unit, default 0 (cut-make-trim) |
| overhead_cost | decimal(12,4) | ≥ 0, cost per unit, default 0 |
| selling_price | decimal(12,4) nullable | ≥ 0; pre-fills "Record a sale"; margin = selling_price − unit_cost (server-side) |
| photo_path | string(255) nullable | as on materials |
| timestamps, deleted_at | | soft deletes |

### `product_components` (BOM lines)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| workshop_id | FK → workshops | the product's workshop (composite FK to products and materials on PostgreSQL) |
| product_id | FK → products | `cascadeOnDelete` |
| material_id | FK → materials | `restrictOnDelete` |
| quantity | decimal(14,4) | > 0, **per 1 unit of product**, in the material's unit |
| waste_percent | decimal(5,2) | 0 ≤ x < 100, default 0 (cutting loss, shrinkage…) |
| notes | string(100) nullable | placement, e.g. "body", "lining", "outsole" |
| position | unsignedSmallInteger | display order |
| timestamps | | |

Constraint: `unique(product_id, material_id)`. One line per material per product. The calculator still
aggregates by material defensively.

### `sales` (S-14-BE)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| workshop_id | FK → workshops | the product's workshop |
| product_id | FK → products | `restrictOnDelete`; only `active` products of the same workshop can be sold |
| quantity | unsigned integer | whole units, 1 … 1 000 000 |
| unit_price | decimal(12,4) | ≥ 0; pre-filled from `products.selling_price`, editable |
| sold_on | date | ≤ today |
| note | string(255) nullable | channel, customer or order number |
| user_id | FK → users nullable | who recorded it (`nullOnDelete`) |
| timestamps | | |

Indexes: `index(workshop_id, sold_on, id)`, `index(product_id, sold_on)`. `total = quantity × unit_price` is computed, never stored. Recording a sale never changes stock.

### `stock_movements` (S-16-BE, material history)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| workshop_id | FK → workshops | the material's workshop |
| material_id | FK → materials | `cascadeOnDelete` |
| type | string(16) | `StockMovementType`: created, restock, correction, price_change |
| occurred_on | date | receipt / count date |
| quantity | decimal(14,4) nullable | created: opening stock · restock: quantity received |
| quantity_before, quantity_after | decimal(14,4) nullable | correction: before / after · others: after |
| unit_price | decimal(12,4) nullable | created: opening cost · restock: price paid |
| unit_cost_before, unit_cost_after | decimal(12,4) nullable | price_change |
| reference | string(64) nullable | PO or delivery note |
| note | string(255) nullable | correction reason |
| user_id | FK → users nullable | who did it |
| timestamps | | |

Index: `index(material_id, occurred_on, id)`. A restock updates `materials.quantity_on_hand` and (by default) `materials.unit_cost` = latest price
paid inside one transaction, and writes a `restock` + (if the cost changed) a `price_change` row. The ledger is append-only.

### Business rules (the costing engine)
All formulas, rounding rules, edge cases and a worked example live in the skill
[`.claude/skills/invory-costing-domain/SKILL.md`](../.claude/skills/invory-costing-domain/SKILL.md), which is the single source of truth. Summary:

- `effective_qty = quantity × (1 + waste_percent / 100)`
- `line_cost = effective_qty × material.unit_cost`
- `unit_cost = Σ line_cost + labor_cost + overhead_cost`
- `max_producible = min over materials ⌊ quantity_on_hand / effective_qty ⌋`. `null` if the BOM is empty.
- `bottleneck` = the material that gives the minimum.
- `next_limit` = capacity and bottleneck of the BOM without the current bottleneck (material page suggestion).
- `margin = selling_price − unit_cost`, `margin % = margin ÷ selling_price × 100`.
- Sales: `total = quantity × unit_price`; period totals, average sale and top 5 (by units or revenue) via `SalesCalculator`.
- All arithmetic uses `Brick\Math\BigDecimal` on decimal **strings**, never floats ([ADR-0002](adr/0002-money-and-quantity-precision.md)).

### Deletion rules
- A **material used in any non-deleted product's BOM cannot be deleted**: `DeleteMaterial` throws
  `MaterialInUseException`, and the controller turns it into a flash error listing the products.
- Deleting a product soft-deletes it. Its BOM lines stay, for history and restore.

## 5. Backend structure

```
app/
├── Actions/
│   ├── Fortify/                         # starter kit
│   ├── Materials/  CreateMaterial · UpdateMaterial · DeleteMaterial
│   ├── Products/   CreateProduct · UpdateProduct · DeleteProduct · SyncProductComponents
│   └── Workshops/  CreateWorkshop   (registration; S-45 adds the standard units and categories)
├── Domain/
│   └── Costing/    BomLine · LineCost · CostBreakdown · ProductionCapacity · CostingCalculator
├── Enums/          ProductStatus · StockStatus · StockMovementType · SalesPeriod · UnitDimension   (each has label() + options())
├── Exceptions/     MaterialInUseException
├── Http/
│   ├── Controllers/  DashboardController · MaterialController · ProductController
│   ├── Requests/     Materials/{Store,Update}MaterialRequest · Products/{Store,Update}ProductRequest
│   └── Resources/    MaterialResource · ProductListResource · ProductResource · ProductComponentResource · CostBreakdownResource
├── Models/         Material · Product · ProductComponent · Sale · StockMovement · User · Workshop · Concerns/BelongsToWorkshop · Scopes/WorkshopScope
├── Policies/       MaterialPolicy · ProductPolicy · SalePolicy   (any member of the record's workshop; another workshop → 404, never 403)
└── Services/
    ├── Costing/    ProductCostingService                   (Eloquent → BomLine adapter; calls the calculator)
    └── Workshops/  CurrentWorkshop                         (the current workshop: explicit, else the signed-in user's)
app/Validation/WorkshopRule  exists / unique limited to the current workshop
config/invory.php   currency (APP_CURRENCY, default TND), pagination size, decimal scales
database/
├── factories/      MaterialFactory · ProductFactory · ProductComponentFactory
├── migrations/
└── seeders/        DemoCatalogSeeder (realistic fashion data: denim jacket, t-shirt, sneaker, leather tote)
tests/
├── Unit/Domain/Costing/            pure calculator tests (no DB)
├── Feature/Materials/ · Feature/Products/ · Feature/Dashboard/
└── Arch/ArchTest.php               layer rules + Pest presets (php, security, laravel)
```

### Routes (all under `auth` + `verified`, named, resourceful)

| Method | URI | Name | Controller@action | Inertia page |
|---|---|---|---|---|
| GET | /dashboard | dashboard | DashboardController | `dashboard` |
| GET | /materials | materials.index | MaterialController@index | `materials/index` |
| GET | /materials/create | materials.create | @create | `materials/create` |
| POST | /materials | materials.store | @store | redirect → index |
| GET | /materials/{material} | materials.show | @show | `materials/show` (details + "used in") |
| GET | /materials/{material}/edit | materials.edit | @edit | `materials/edit` |
| PUT | /materials/{material} | materials.update | @update | redirect → show |
| DELETE | /materials/{material} | materials.destroy | @destroy | redirect → index |
| GET | /products | products.index | ProductController@index | `products/index` (cost + capacity per product) |
| GET | /products/create | products.create | @create | `products/create` |
| POST | /products | products.store | @store | redirect → show |
| GET | /products/{product} | products.show | @show | `products/show` (composition + cost breakdown + capacity) |
| GET | /products/{product}/edit | products.edit | @edit | `products/edit` |
| PUT | /products/{product} | products.update | @update | redirect → show |
| DELETE | /products/{product} | products.destroy | @destroy | redirect → index |
| POST | /materials/{material}/restocks | materials.restocks.store | MaterialRestockController@store | redirect back |
| GET | /sales | sales.index | SaleController@index | `sales/index` (log, totals, record / edit panel) |
| POST | /sales | sales.store | @store | redirect back |
| PUT | /sales/{sale} | sales.update | @update | redirect back |
| DELETE | /sales/{sale} | sales.destroy | @destroy | redirect back |

Settings › Catalogue (S-21/S-22): for each of `units`, `material-categories` and `product-categories`, `GET settings/<list>` (page `settings/<list>`),
`POST`, `PUT …/{id}`, `DELETE …/{id}`, `PATCH …/{id}/archive` and `PATCH …/{id}/restore`, named `settings.<list>.*`. Option lists for forms and
filters come from the shared `reference` once-prop, not from page props.

The index pages accept the query parameters `search`, `category`, `status` and `cant_produce` (products), `low_stock` (materials),
`product_id`, `period`, `record`, `record_product_id` (sales), `sort` and `page`. The dashboard accepts `period`.
Pagination: 25 per page, `->withQueryString()`. Every write persists (Phase 3).

### Preview layer (retired 2026-10-13, ADR-0006)
`app/Mock/` and `public/mock/` were removed by S-17; an arch test forbids `App\Mock`. Photos live on the `public` disk (`photo_url` accessor,
`App\Services\Photos\PhotoStorage`; production needs `php artisan storage:link`).

### Shared Inertia props (HandleInertiaRequests)
`name`, `auth.user`, `sidebarOpen` (starter kit) and `app.currency` / `app.locale` / `app.today` (typed in `resources/js/types/global.d.ts`).
Toasts use Inertia 3's native flash: `Inertia::flash('toast', ['type' => 'success', 'message' => …])`, shown by the starter kit's `<Toaster>`.

## 6. Frontend structure

```
resources/js/
├── pages/
│   ├── dashboard.tsx
│   ├── materials/  index.tsx · create.tsx · edit.tsx · show.tsx
│   ├── products/   index.tsx · create.tsx · edit.tsx · show.tsx
│   └── sales/      index.tsx
├── components/
│   ├── ui/                 # shadcn — generated via `npx shadcn@latest add …`, never hand-edited
│   ├── dashboard/          KPI cards, top sellers, needs restock, alerts
│   ├── materials/          material-form · materials-table · stock-badge · restock form · history list · usage table
│   ├── products/           product-form · bom-editor · bom-table · cost-breakdown-card
│   ├── sales/              sales table · sale form
│   ├── settings/           reference manager (units, material and product categories): table, row actions, form dialogs (S-22)
│   └── shared/             money · quantity · page-header · empty-state · confirm-delete-dialog · pagination · filters-bar ·
│                           photo-thumb · photo-field · stat-card · callout · capacity-badge · product-status-badge
├── hooks/                  use-flash-toast (starter kit) · use-app-settings · use-debounced-value
├── layouts/                # starter kit (sidebar layout; add Materials/Products nav items)
├── lib/                    format.ts (Intl money/quantity/percent) · utils.ts · reference.ts (options from the shared `reference` prop)
├── types/
│   ├── index.d.ts          # starter kit (auth, nav)
│   └── domain.ts           # THE CONTRACT — mirrors API Resources (snake_case, decimals as strings)
├── actions/ · routes/      # Wayfinder — GENERATED, never edit, never commit
└── **/*.test.tsx           # colocated Vitest + React Testing Library tests
```

Principles:
- **Pages are thin.** They read props, set `<Head>`, and compose components. Logic sits in components and hooks that can be tested without Inertia.
- **The server is the source of truth for numbers.** The UI only *formats* decimal strings (`Intl.NumberFormat`). It never adds or multiplies money.
- **Forms**: Inertia `<Form>` / `useForm` with Wayfinder `.form()` / `.url()`. Field errors come from the Laravel validation bag. Toasts come from flash messages.
- **Accessibility**: WCAG 2.2 AA. Every input has a label, controls are keyboard-reachable, status is never conveyed by colour alone.

## 7. Key screens (MVP)

Approved mockup (2026-10-08): [docs/design/mockup/](design/mockup/README.md). Sidebar: Dashboard, Materials, Products, Sales.
Look: shadcn starter-kit layout, neutral surfaces, indigo "denim" primary `#2B3A86`, Instrument Sans + IBM Plex Mono for SKUs and codes,
tabular numbers, status badges with text + icon. Light and dark mode, usable at 375 px.

| Screen | Shows | Purpose |
|---|---|---|
| Dashboard | Period select (Last 30 days default). Alert per product that can't be produced. KPI cards: total sales, units sold, inventory value, needs restock (low · out), can't produce (of N products). **Top 5 products sold** with a Units / Revenue toggle. Needs-restock list. Recent sales. | At-a-glance status |
| Sales | Totals (total sales, units, count, average sale); search, period and product filters; sales table with edit / delete; **Record a sale** panel (active products only, unit price pre-filled from the selling price, date, note; stock unchanged) | Sales log (decision 1) |
| Materials list | Thumbnail, name + SKU, category, stock + unit, reorder at, unit cost, stock value, status badge, **used in** (# products); search, category, "needs restock", sort; per-row **Restock** quick form and **History** panel | Manage inventory (decision 4) |
| Material form | All material fields with unit-aware inputs + **photo** upload / replace / remove | Create/edit (decision 2) |
| Material detail | Photo, KPIs, below-reorder callout, details; **What you can make with it**: per product need per unit, units this stock covers, can make now, limited by (bottleneck flag) + **next-limit suggestion**; Restock; full History; delete disabled with the reason when used | Impact view (decision 3) |
| Products list | Thumbnail, name + SKU + # materials, category, status, **unit cost**, price · margin, **can produce**, **bottleneck**, sold (30 d); "can't produce" filter | **Objective #4** |
| Product detail | Photo, **can produce now** hero, unit cost, sold (30 d), bottleneck callout, cost breakdown with **selling price and margin**, **composition table** (material, qty, waste, effective qty, unit cost, line cost, stock, units possible, bottleneck highlight), recent sales | **Objective #2** (decision 5) |
| Product form | Details, costs and **selling price**, photo, **BOM editor** (searchable material picker, qty with unit suffix, waste %, placement notes, add / remove / move rows) | **Objective #3** |

## 8. Quality, testing and definition of done

Test the **risk**, not the code. Every subtask is tagged CRITICAL or NON-CRITICAL by the tech-lead (see [TEAM.md](TEAM.md) §Two-lane delivery)
and gets a **test budget** in the brief. Agents write to the budget — not more, not less. Rubric below:

| Level | Tooling | Owner | Rubric |
|---|---|---|---|
| Domain unit | Pest (no DB) | backend | 100 % of `App\Domain\*` branches; worked example from the skill with exact string assertions |
| Policy | Pest + `assertStatus(403)` | backend | 1 dataset per policy (guest / denied / allowed) |
| Action | Pest + `RefreshDatabase` | backend | 1 happy + 1 rollback-on-exception if transactional + 1 per business rule it enforces |
| Feature/HTTP | Pest + `RefreshDatabase` + `assertInertia` | backend | 1 happy per route + 1 validation **dataset** (5–10 rule combinations in one test) + 1 guest redirect |
| Resource contract | Pest `assertInertia` on one row | backend | 1 shape test per Resource; the `resources/js/types/domain.ts` edit ships in the same commit |
| N+1 regression | `expectQueryBudget()` / `DB::enableQueryLog` | backend | 1 per index page |
| Architecture | Pest `arch()` + presets | backend (set up), tech-lead (guards) | 1 test per layer rule from §3 (not per file) |
| Static analysis | Larastan (level 7; raise to 8 once the MVP is green) + Pint | backend | zero errors |
| Formatters | Vitest (jsdom) | frontend | 1 per format rule |
| Form editors (`BomEditor`, `ProductForm`, forms with validation branching) | Vitest + RTL + user-event | frontend | 1 happy + 1 error branch + 1 key edge, queries by role / label |
| Page | Vitest + RTL | frontend | 1 render-with-props smoke that shows the critical values (unit cost, can produce, bottleneck) |
| Lint/format/types | `npm run check`, `npm run types:check` | frontend | zero warnings (`denyWarnings: true`) |
| E2E smoke | Pest 4 browser tests (Playwright) | frontend | 1 golden path per feature (`material → product with BOM → capacity shown`) |

**Skip these (both sides):** framework glue (Request extends Request, Resource maps field 1:1), getters/setters, per-column assertions when a shape
test covers them, repeated datasets, snapshots, implementation-detail queries (`getByTestId` where `getByRole` works), private methods, pure
shadcn/ui wrappers, empty-state components.

**Reliability patterns (both sides):** `travelTo()` / `vi.setSystemTime` for date-dependent code; factories valid-by-default; `waitFor` always wraps
a positive assertion with a tight timeout; behaviour not internals; one assertion focus per test.

**Definition of Done** for a story: every acceptance criterion ships its own commit · tests written to budget and green · `composer test`,
`npm run check`, `npm run types:check`, `npx vp test` and `npm run build` green · PostgreSQL parity green when the diff touches migrations, queries
or seeders · contract (`resources/js/types/domain.ts`) in sync · tech-lead verdict **APPROVED** (full checklist for CRITICAL, fast lane for NON-CRITICAL) ·
every acceptance criterion of the story card has evidence, the product-owner's acceptance review is done and **the human accepted the story**
(product side: `invory-product-owner` skill §7).

## 9. Reliability and security baseline

- `Model::shouldBeStrict(! app()->isProduction())` catches lazy loading (N+1), silently discarded attributes and missing attributes in dev and tests.
- `DB::prohibitDestructiveCommands(app()->isProduction())`.
- All multi-row writes (product + BOM sync) run inside `DB::transaction`.
- Mass assignment only from `$request->validated()`. Every model has explicit `$fillable`.
- Authorization goes through Policies on every action, including reads. Every query on workshop data is limited to the current workshop by a
  shared rule (the `BelongsToWorkshop` global scope, ADR-0013); Policies check the workshop again and answer 404 for another workshop's record.
- No raw SQL with interpolated input. No `dangerouslySetInnerHTML`.
- Money and quantities are never floats, on either side of the wire.
- Foreign keys are enforced. Delete protections apply to referenced materials.

## 10. Scalability path (post-MVP, designed for but not built)

| Need | Plan | Why the MVP is ready |
|---|---|---|
| Large catalogs / sort by capacity | Denormalized `product_metrics` (unit_cost, max_producible, bottleneck_id), recomputed by a queued job on `MaterialUpdated` / `ProductComponentsSynced` events | All maths is behind one service and one pure calculator |
| Stock history and audit | `stock_movements` ledger (receipt, adjustment, consumption); `quantity_on_hand` becomes a cached sum | Stock changes only go through actions |
| Purchase vs consumption units | `unit_conversions` (roll → m, cone → m) | Unit is an enum with a single owner |
| Sizes and variants | `product_variants` with per-size consumption factors | BOM lines are an isolated table |
| Sub-assemblies (multi-level BOM) | Polymorphic component (material **or** product), recursive costing in the domain | Calculator works on `BomLine` value objects, not models |
| Suppliers and purchasing | `suppliers` table, purchase orders, reorder suggestions from `reorder_level` | `supplier_*` fields already captured |
| Several people per workshop | Invitations and roles on `users.workshop_id`; no data moves ([ADR-0013](adr/0013-one-workshop-per-account.md)) | Data already belongs to the workshop, not the person |
| Integrations / mobile | `/api/v1` with Sanctum, reusing Actions and Resources | Controllers are thin and Resources already exist |
| Roles | Policies gain role checks (owner, planner, viewer) | Policies already wired everywhere |
| Cost history | Snapshot `CostBreakdown` per product on change | The breakdown is already an immutable value object |
| Containerised production | Production image (FrankenPHP/Octane or php-fpm + nginx) | App is developed and tested on PostgreSQL; CI runs the suite on Linux |
