# Invory — Architecture

> Owner: **tech-lead** agent. Any change to this document goes through an ADR in [`docs/adr/`](adr/).
> Last updated: 2026-10-07

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
| Runtime | PHP ≥ **8.3** (Laragon 8.3.33 native, Sail container 8.5), Node **24** | Code must run on the lowest PHP in use (8.3). |
| Backend | **Laravel 13** | Laravel has no LTS since v6. v13 is the current major: bug fixes until Q3 2027, security fixes until Q1 2028. See [ADR-0001](adr/0001-stack.md). |
| Glue | **Inertia 3** (`inertiajs/inertia-laravel`, `@inertiajs/react`) | Server-side routing and controllers; React renders pages. No separate REST API in the MVP. |
| Frontend | **React 19** + **TypeScript** (strict) + **React Compiler** | From the official Laravel React starter kit. |
| Styling/UI | **Tailwind CSS 4** + **shadcn/ui** (Radix) + lucide icons + sonner toasts | |
| Typed routes | **Laravel Wayfinder** | Generates TypeScript functions for every controller action and named route. |
| Auth | **Laravel Fortify** (starter kit) | Login, registration, password reset, 2FA. Single company: no teams. |
| Tooling (FE) | **Vite+** (`vp`): Vite 8, Oxlint, Oxfmt, **Vitest** | `npm run check`, `npm run types:check`, `npx vp test` |
| Tooling (BE) | **Pest 4**, **Larastan**, **Pint** | `composer test` |
| Database | **PostgreSQL** in Sail (dev) and in production. SQLite in-memory for the fast test suite only. | Write portable SQL only. CI also runs the suite on PostgreSQL. |
| Dev environment | **Laravel Sail** (Docker: app, PostgreSQL, Mailpit) for the runtime + **Laragon** (native PHP + Composer) for tooling and agents | See [ADR-0005](adr/0005-dev-environment-laragon-sail.md) and [SETUP.md](SETUP.md). |
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
| React `pages/` | components, hooks, types, Wayfinder | contain business maths (cost/capacity come from the server) |

## 4. Domain model

```
 ┌──────────────┐ 1      * ┌────────────────────┐ *      1 ┌──────────────┐
 │   Product    │──────────│  ProductComponent  │──────────│   Material   │
 │ (final item) │          │     (BOM line)     │          │(primary item)│
 └──────────────┘          └────────────────────┘          └──────────────┘
```

### `materials` (primary items)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| sku | string(64) | required, unique among non-deleted rows |
| name | string(150) | required |
| category | string(32) | `MaterialCategory` enum: fabric, lining, interlining, leather, thread, trim, fastener, label, sole, hardware, packaging, other |
| unit | string(16) | `UnitOfMeasure` enum: m, cm, yd, sqm, sqft, kg, g, l, ml, pc, pair, roll, cone |
| unit_cost | decimal(12,4) | ≥ 0, cost per 1 `unit` |
| quantity_on_hand | decimal(14,4) | ≥ 0, in `unit` |
| reorder_level | decimal(14,4) nullable | low-stock threshold |
| supplier_name | string(150) nullable | becomes a `suppliers` table in phase 2 |
| supplier_reference | string(64) nullable | supplier's article code |
| color | string(64) nullable | |
| composition | string(255) nullable | e.g. "98% cotton, 2% elastane" |
| notes | text nullable | |
| timestamps, deleted_at | | soft deletes |

Indexes: `unique(sku)` (PostgreSQL: partial unique `WHERE deleted_at IS NULL`; elsewhere enforce in validation), `index(category)`, `index(name)`.

### `products` (final items)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| sku | string(64) | required, unique among non-deleted rows |
| name | string(150) | required |
| category | string(32) | `ProductCategory` enum: apparel, footwear, bag, accessory, jewelry, other |
| status | string(16) | `ProductStatus` enum: draft, active, archived (default active) |
| description | text nullable | |
| labor_cost | decimal(12,4) | ≥ 0, cost per unit, default 0 (cut-make-trim) |
| overhead_cost | decimal(12,4) | ≥ 0, cost per unit, default 0 |
| timestamps, deleted_at | | soft deletes |

### `product_components` (BOM lines)
| Column | Type | Rules |
|---|---|---|
| id | bigint PK | |
| product_id | FK → products | `cascadeOnDelete` |
| material_id | FK → materials | `restrictOnDelete` |
| quantity | decimal(14,4) | > 0, **per 1 unit of product**, in the material's unit |
| waste_percent | decimal(5,2) | 0 ≤ x < 100, default 0 (cutting loss, shrinkage…) |
| notes | string(100) nullable | placement, e.g. "body", "lining", "outsole" |
| position | unsignedSmallInteger | display order |
| timestamps | | |

Constraint: `unique(product_id, material_id)`. One line per material per product. The calculator still
aggregates by material defensively.

### Business rules (the costing engine)
All formulas, rounding rules, edge cases and a worked example live in the skill
[`.claude/skills/invory-costing-domain/SKILL.md`](../.claude/skills/invory-costing-domain/SKILL.md), which is the single source of truth. Summary:

- `effective_qty = quantity × (1 + waste_percent / 100)`
- `line_cost = effective_qty × material.unit_cost`
- `unit_cost = Σ line_cost + labor_cost + overhead_cost`
- `max_producible = min over materials ⌊ quantity_on_hand / effective_qty ⌋`. `null` if the BOM is empty.
- `bottleneck` = the material that gives the minimum.
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
│   └── Products/   CreateProduct · UpdateProduct · DeleteProduct · SyncProductComponents
├── Domain/
│   └── Costing/    BomLine · LineCost · CostBreakdown · ProductionCapacity · CostingCalculator
├── Enums/          MaterialCategory · UnitOfMeasure · ProductCategory · ProductStatus   (each has label() + options())
├── Exceptions/     MaterialInUseException
├── Http/
│   ├── Controllers/  DashboardController · MaterialController · ProductController
│   ├── Requests/     Materials/{Store,Update}MaterialRequest · Products/{Store,Update}ProductRequest
│   └── Resources/    MaterialResource · ProductListResource · ProductResource · ProductComponentResource · CostBreakdownResource
├── Models/         Material · Product · ProductComponent · User
├── Policies/       MaterialPolicy · ProductPolicy          (MVP: any authenticated user; roles in phase 2)
└── Services/
    └── Costing/    ProductCostingService                   (Eloquent → BomLine adapter; calls the calculator)
config/invory.php   currency (APP_CURRENCY, default EUR), pagination size, decimal scales
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

The index pages accept the query parameters `search`, `category`, `status` (products), `low_stock` (materials), `sort` and `page`.
Pagination: 25 per page, `->withQueryString()`.

### Shared Inertia props (HandleInertiaRequests)
`auth.user`, `flash.success` / `flash.error`, and `app.currency` / `app.locale`.

## 6. Frontend structure

```
resources/js/
├── pages/
│   ├── dashboard.tsx
│   ├── materials/  index.tsx · create.tsx · edit.tsx · show.tsx
│   └── products/   index.tsx · create.tsx · edit.tsx · show.tsx
├── components/
│   ├── ui/                 # shadcn — generated via `npx shadcn@latest add …`, never hand-edited
│   ├── materials/          material-form · materials-table · stock-badge
│   ├── products/           product-form · bom-editor · bom-table · cost-breakdown-card · capacity-badge
│   └── shared/             money · quantity · page-header · empty-state · confirm-delete-dialog · pagination · filters-bar
├── hooks/                  use-flash-toasts · use-debounced-value
├── layouts/                # starter kit (sidebar layout; add Materials/Products nav items)
├── lib/                    format.ts (Intl money/quantity/percent) · utils.ts
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

| Screen | Shows | Purpose |
|---|---|---|
| Dashboard | KPI cards: # materials, inventory value, # low-stock, # products, # products that can't be produced. Top products by capacity. Low-stock list. | At-a-glance status |
| Materials list | SKU, name, category, stock + unit, unit cost, stock value, low-stock badge; search and filters | Manage inventory |
| Material form | All material fields with unit-aware inputs | Create/edit |
| Material detail | Fields + **"Used in"** products with quantity per unit | Impact view |
| Products list | SKU, name, category, status, **unit cost**, **can produce**, **bottleneck** | **Objective #4** |
| Product detail | **Composition table** (material, qty, waste, effective qty, unit cost, line cost, stock, units possible from this line, bottleneck highlight) + cost breakdown card + capacity card | **Objective #2** |
| Product form | Product fields + **BOM editor** (add/remove rows, searchable material picker, qty with unit suffix, waste %, placement notes) | **Objective #3** |

## 8. Quality, testing and definition of done

| Level | Tooling | Owner | Minimum |
|---|---|---|---|
| Domain unit | Pest (no DB) | backend | 100 % of `Domain/Costing` branches, worked example from the skill |
| Feature/HTTP | Pest + `RefreshDatabase` + `assertInertia` | backend | every route: guest redirect, validation (datasets), happy path, DB state, props contract |
| Architecture | Pest `arch()` + presets | backend (set up), tech-lead (guards) | layer rules from §3 |
| Static analysis | Larastan (the starter kit ships level 7; raise to 8 once the MVP is green) + Pint | backend | zero errors |
| Component | Vitest + React Testing Library + user-event (jsdom) | frontend | every component with logic, the BOM editor, formatters |
| Lint/format/types | `npm run check`, `npm run types:check` | frontend | zero warnings (`denyWarnings: true`) |
| E2E smoke | Pest 4 browser tests (Playwright) | frontend | 1 golden path: material → product with BOM → capacity shown |

**Definition of Done** for a story: acceptance criteria met · tests written and green · `composer test`, `npm run check`,
`npm run types:check`, `npx vp test` and `npm run build` green · contract (`domain.ts`) in sync · tech-lead verdict **APPROVED**.

## 9. Reliability and security baseline

- `Model::shouldBeStrict(! app()->isProduction())` catches lazy loading (N+1), silently discarded attributes and missing attributes in dev and tests.
- `DB::prohibitDestructiveCommands(app()->isProduction())`.
- All multi-row writes (product + BOM sync) run inside `DB::transaction`.
- Mass assignment only from `$request->validated()`. Every model has explicit `$fillable`.
- Authorization goes through Policies on every action, including reads, even though the MVP allows all authenticated users.
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
| Multi-company SaaS | Starter-kit teams, `team_id` + global scope on every model | Single entry points (actions/policies) to add scoping ([ADR-0003](adr/0003-single-tenant-mvp.md)) |
| Integrations / mobile | `/api/v1` with Sanctum, reusing Actions and Resources | Controllers are thin and Resources already exist |
| Roles | Policies gain role checks (owner, planner, viewer) | Policies already wired everywhere |
| Cost history | Snapshot `CostBreakdown` per product on change | The breakdown is already an immutable value object |
| Containerised production | Production image (FrankenPHP/Octane or php-fpm + nginx); Sail stays dev-only | App is already developed and tested in containers on PostgreSQL |
