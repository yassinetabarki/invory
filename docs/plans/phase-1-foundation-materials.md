# Phase 1 — Foundation + Materials inventory (+ costing engine)

> Owner: **tech-lead** · Status: **delivered: S-00 to S-03 approved 2026-10-08, awaiting human commit approval and sign-off** · Stories: S-00, S-01, S-02, S-03 ([BACKLOG](../BACKLOG.md))
> Executed by the tech-lead, who dispatches `backend-developer` (BE) and `frontend-developer` (FE) per wave and reviews every handoff.

## Outcome
At the end of Phase 1 a signed-in user can **manage the materials inventory end-to-end** (list, search, filter, create, edit, view, delete),
with stock status, stock value and a clear UI. The **costing engine** (unit cost, capacity, bottleneck) is built and fully tested,
ready for Products in Phase 2 (S-04, S-05).

**Out of scope:** products, BOM, dashboard KPIs, "used in" (Phase 2+).

## Facts from the scaffold that this plan builds on
- **Toasts:** use the starter kit's native Inertia 3 flash: `Inertia::flash('toast', ['type' => 'success'|'info'|'warning'|'error', 'message' => __('…')])`
  then `return to_route(...)`. `<Toaster>` + `useFlashToast()` already show it. **No custom flash props.**
- **Shared props** are typed in `resources/js/types/global.d.ts` (`InertiaConfig.sharedPageProps`) and set in `app/Http/Middleware/HandleInertiaRequests.php`.
- **Navigation:** `mainNavItems` in `resources/js/components/app-sidebar.tsx` (Wayfinder route functions, lucide icons).
- Available shadcn components: alert, badge, breadcrumb, button, card, checkbox, dialog, dropdown-menu, input, label, select, separator, sheet, skeleton, sonner, tooltip…
  **Missing (add with `npx shadcn@latest add …`):** `table`, `alert-dialog`, `textarea`, `command`, `popover`.
- `AppServiceProvider::configureDefaults()` already sets `DB::prohibitDestructiveCommands(production)`. Larastan is at **level 7**.
- Native `php artisan` reads `.env` with `DB_HOST=pgsql`, so **DB-touching artisan commands run in the container**:
  `docker compose exec -u sail laravel.test php artisan …`. The test suite runs natively on SQLite in-memory.

## Waves (what runs in parallel)

```
Wave 1   S-00-BE ─┐
         S-00-FE ─┼─► TL review S-00 ─► human approves commit
         S-00-TL ─┘
Wave 2   S-01 (BE #1) ─┐
         S-03 (BE #2) ─┼─► TL review S-01, S-03 ─► commits
         S-02a (FE)  ──┘   (components only, against the frozen contract)
Wave 3   S-02b (FE: pages + wiring, needs S-01 routes) ─► TL review S-02 ─► commit ─► Phase 1 demo & sign-off
```

**Parallel-work rules (one shared working tree):**
1. Agents in the same wave touch **disjoint files** (listed per subtask). If a subtask needs a file outside its list, it stops and reports.
2. While others are working, each agent runs **focused** checks on its own paths:
   `php artisan test --compact --filter=…`, `vendor/bin/pint --test <paths>`, `vendor/bin/phpstan analyse <paths>`, `npx vp test <paths>`, `npx vp check <paths>`.
   The **tech-lead runs the full gates once every handoff in the wave is in**.
3. Git: branch `phase/1-materials`. **One commit per approved story**, staged by its file list (the main session asks the human first). Merge to `main` at sign-off.

---

## The contract (frozen for Phase 1 — changes go through the tech-lead)

### Routes (`routes/web.php`, inside the existing `auth` + `verified` group)
`Route::resource('materials', MaterialController::class);` → `materials.index|create|store|show|edit|update|destroy`.

### Shared props (added in S-00)
```ts
// resources/js/types/global.d.ts → InertiaConfig.sharedPageProps
app: { currency: string; locale: string }   // from config('invory.currency'), app()->getLocale()
```

### Types (`resources/js/types/domain.ts`, written by BE in **S-00**, contract-first)
```ts
export type Decimal = string; // decimals travel as strings (scale 4). Never do maths on them in TS.

export type Option<T extends string = string> = { value: T; label: string };

export type PaginationLink = { url: string | null; label: string; active: boolean };
export type Paginated<T> = {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number; from: number | null; last_page: number; links: PaginationLink[];
        path: string; per_page: number; to: number | null; total: number;
    };
};

export type MaterialCategory =
    | 'fabric' | 'lining' | 'interlining' | 'leather' | 'thread' | 'trim'
    | 'fastener' | 'label' | 'sole' | 'hardware' | 'packaging' | 'other';
export type UnitOfMeasure =
    | 'm' | 'cm' | 'yd' | 'sqm' | 'sqft' | 'kg' | 'g' | 'l' | 'ml' | 'pc' | 'pair' | 'roll' | 'cone';
export type StockStatus = 'in_stock' | 'low' | 'out';

export type Material = {
    id: number;
    sku: string;
    name: string;
    category: MaterialCategory;
    category_label: string;
    unit: UnitOfMeasure;
    unit_label: string;
    unit_cost: Decimal;
    quantity_on_hand: Decimal;
    reorder_level: Decimal | null;
    stock_value: Decimal;            // quantity_on_hand × unit_cost, scale 4
    stock_status: StockStatus;       // computed server-side, see rule below
    supplier_name: string | null;
    supplier_reference: string | null;
    color: string | null;
    composition: string | null;
    notes: string | null;
    created_at: string;              // ISO-8601
    updated_at: string;
};

export type MaterialSort =
    | 'name' | '-name' | 'sku' | '-sku' | 'quantity_on_hand' | '-quantity_on_hand'
    | 'unit_cost' | '-unit_cost' | 'updated_at' | '-updated_at';

export type MaterialFilters = {
    search: string | null;
    category: MaterialCategory | null;
    low_stock: boolean;              // true → stock_status in (low, out)
    sort: MaterialSort;              // default 'name'
};

/** Fields the material form submits (strings for decimals). */
export type MaterialFormData = {
    sku: string; name: string; category: MaterialCategory | ''; unit: UnitOfMeasure | '';
    unit_cost: string; quantity_on_hand: string; reorder_level: string;
    supplier_name: string; supplier_reference: string; color: string; composition: string; notes: string;
};

// Page props
export type MaterialsIndexProps = { materials: Paginated<Material>; filters: MaterialFilters; categories: Option<MaterialCategory>[] };
export type MaterialCreateProps = { categories: Option<MaterialCategory>[]; units: Option<UnitOfMeasure>[] };
export type MaterialEditProps = MaterialCreateProps & { material: Material };
export type MaterialShowProps = { material: Material };
```
**Single resources are passed flat** (`material: {...}`, not `material: { data: {...} }`). Use `JsonResource::withoutWrapping()` in
`AppServiceProvider` and assert the shape in tests. Paginated collections keep `{ data, links, meta }`.

### Stock status rule (server-side, also added to the domain skill)
`out` if `quantity_on_hand = 0` · else `low` if `reorder_level` is set and `quantity_on_hand <= reorder_level` · else `in_stock`.

### Costing engine API (S-03, pure PHP, `App\Domain\Costing`)
```php
final readonly class BomLine {          // input: one material requirement per unit of product
    public function __construct(
        public int $materialId, public string $materialName, public string $unit,
        public string $quantity, public string $wastePercent, public string $unitCost, public string $stockOnHand,
    ) {}
}
final readonly class LineCost {         // output per line
    // materialId, materialName, unit, quantity, wastePercent, effectiveQuantity, unitCost, lineCost,
    // stockOnHand, unitsPossible (int), isBottleneck (bool) — decimals as scale-4 strings
}
final readonly class CostBreakdown {    // lines (list<LineCost>), materialCost, laborCost, overheadCost, unitCost
}
final readonly class ProductionCapacity { // maxProducible (?int), bottleneckMaterialId (?int), bottleneckMaterialName (?string)
}
final readonly class CostingResult {    // cost (CostBreakdown), capacity (ProductionCapacity)
}
final class CostingCalculator {
    /** @param list<BomLine> $lines */
    public function calculate(array $lines, string $laborCost = '0', string $overheadCost = '0'): CostingResult;
}
```
All rules, rounding and edge cases: `.claude/skills/invory-costing-domain/SKILL.md`.

---

## S-00 · Foundation — Wave 1

### S-00-BE (backend-developer) · size S
**Files:** `config/invory.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Middleware/HandleInertiaRequests.php`,
`resources/js/types/global.d.ts` (only `sharedPageProps`), `resources/js/types/domain.ts`, `tests/Arch/ArchTest.php`, `tests/Feature/Foundation/*`,
`phpunit.xml` (add an `Arch` testsuite for `tests/Arch`; *amended at intake: without it the arch tests never run*)
1. `config/invory.php`: `currency` ← `env('APP_CURRENCY', 'EUR')`, `per_page` = 25.
2. `AppServiceProvider::configureDefaults()`: add `Model::shouldBeStrict(! app()->isProduction())` and `JsonResource::withoutWrapping()`.
3. `HandleInertiaRequests::share()`: add `'app' => ['currency' => config('invory.currency'), 'locale' => app()->getLocale()]`. Type it in `global.d.ts`.
4. `resources/js/types/domain.ts`: write the **full Phase 1 contract** above (contract-first, so FE can build in Wave 2).
5. `tests/Arch/ArchTest.php`: presets `php`, `security`, `laravel` (add `->ignoring(...)` only for starter-kit code that trips them, and list each ignore in the handoff),
   plus the layer rules from the `invory-laravel-backend` skill.
6. Test: an authenticated page response carries `app.currency` and `app.locale`.

**Done when:** `composer test` green (Pint, Larastan 7, Pest incl. arch); shared props asserted; `domain.ts` matches the contract verbatim.

### S-00-FE (frontend-developer) · size S
**Files:** `package.json`, `package-lock.json`, `vite.config.ts` (the `test` block, plus `skills-lock.json` in `fmt.ignorePatterns`:
*amended at intake, that generated lock file made `npm run check` red at baseline*), `resources/js/test/*`, `resources/js/lib/format.ts(+test)`,
`resources/js/hooks/use-app-settings.ts`, `resources/js/components/shared/{money,quantity}.tsx(+tests)`, `resources/js/components/app-sidebar.tsx`,
`resources/js/components/app-logo.tsx`, `resources/js/pages/welcome.tsx`
1. Vitest through Vite+: install `jsdom @testing-library/react @testing-library/user-event @testing-library/jest-dom`; add the `test` block
   (`environment: 'jsdom'`, `setupFiles`, `include: ['resources/js/**/*.test.{ts,tsx}']`); `resources/js/test/setup.ts`; npm script `"test": "vp test"`.
2. `resources/js/test/inertia.tsx`: shared helper to mock `@inertiajs/react` (`router`, `usePage`, `Head`, `Link`) for page-level tests.
3. `lib/format.ts`: `formatMoney`, `formatQuantity`, `formatPercent` (Intl on numeric **strings**, no float maths) + tests, including the worked example
   `"35.7540"` → `€35.75` (locale `en`, EUR) and the tiny unit cost `"0.0040"` with 4 fraction digits.
4. `hooks/use-app-settings.ts` (reads `app.currency/locale` from shared props); `<Money value unit? />` and `<Quantity value unit />` + tests.
5. `npm audit` fixes triaged by the tech-lead at intake (non-breaking only): bump `vite-plus` `0.3.0` → `0.3.3` (exact pin; fixes tinypool via oxfmt)
   and lift the transitive `shell-quote` to ≥ 1.10.1 under `concurrently@10` (`npm update shell-quote`, or an `overrides` entry if needed).
6. Branding: app logo text "Invory"; remove the starter kit's footer links (Repository/Documentation); replace the welcome page with a simple
   Invory landing (name, one-line pitch, Log in / Register buttons). Nav items for Materials and Products come in S-02b and S-05, when their routes exist.

**Done when:** `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` are green.

### S-00-TL (tech-lead) · size S
**Files:** `.github/workflows/tests.yml`, `docs/**`
1. Extend CI. Keep the existing `ci` job, and add a **PostgreSQL** job (service `postgres:18`, env `DB_CONNECTION=pgsql`, `DB_HOST=localhost`,
   `DB_DATABASE=testing`, `DB_USERNAME`, `DB_PASSWORD`) with a matrix of PHP **8.3 and 8.5**. Add `npx vp test` and `npm run build` to the Node side.
2. Triage `npm audit` (5 critical at scaffold time). Report what they are; fix only if non-breaking. Otherwise add a backlog follow-up.
3. Review S-00 (full gates) → APPROVED / CHANGES REQUESTED.

---

## S-01 · Materials — backend — Wave 2 (BE #1) · size M
**Files:** `app/Enums/{MaterialCategory,UnitOfMeasure,StockStatus}.php`, `database/migrations/*_create_materials_table.php`,
`app/Models/Material.php`, `database/factories/MaterialFactory.php`, `app/Policies/MaterialPolicy.php`,
`app/Http/Requests/Materials/{IndexMaterialRequest,StoreMaterialRequest,UpdateMaterialRequest}.php`,
`app/Actions/Materials/{CreateMaterial,UpdateMaterial,DeleteMaterial}.php`, `app/Http/Controllers/MaterialController.php`,
`app/Http/Resources/MaterialResource.php`, `routes/web.php`, `resources/js/pages/materials/{index,create,edit,show}.tsx` (**stubs only**),
`tests/Feature/Materials/*`, `tests/Unit/Enums/*`, `tests/Pest.php` (shared payload helpers; S-03 must not touch it)

1. Enums (string-backed, `label()`, `options()`): `MaterialCategory`, `UnitOfMeasure`, `StockStatus`.
2. Migration per ARCHITECTURE §4. SKU uniqueness among non-deleted rows: a **partial unique index** via
   `DB::statement('CREATE UNIQUE INDEX materials_sku_unique ON materials (sku) WHERE deleted_at IS NULL')`. This works on both PostgreSQL and SQLite.
   Drop it in `down()`. Indexes on `category`, `name`.
3. Model: `SoftDeletes`, explicit fillable, casts (enums, `decimal:4`), `stockStatus(): StockStatus`, `stockValue(): string` (BigDecimal, scale 4),
   query scopes `search()`, `category()`, `needsRestock()` (out or low, via `whereColumn`). Factory with realistic fashion data plus `lowStock()` / `outOfStock()` states.
4. Policy: every ability → `true` for authenticated users (MVP, ADR-0003).
5. `IndexMaterialRequest` validates `search` (≤100), `category` (enum), `low_stock` (boolean), `sort` (whitelist above), `page`.
   `Store/UpdateMaterialRequest` follow the domain skill's validation rules; SKU unique `withoutTrashed()`, ignoring self on update.
6. Actions `CreateMaterial`, `UpdateMaterial`, `DeleteMaterial` (soft delete; S-04 adds the "in use" guard).
7. `MaterialController` (resource): index (filters, sort, paginate `config('invory.per_page')`, `->withQueryString()`), create, store,
   show, edit, update, destroy. Flash toasts via `Inertia::flash('toast', …)`. Redirects: store → show, update → show, destroy → index.
8. `MaterialResource` exactly matching `Material` in `domain.ts`. Page stubs (`// STUB — owned by frontend-developer`).
9. Feature tests: guests redirected (dataset of all routes); index + each filter + sort + pagination + props contract; create/update happy paths
   with DB assertions (decimals stored and returned as scale-4 strings); validation dataset (negative cost, > 4 decimals, unknown enum, duplicate SKU,
   SKU reusable after soft delete, reorder_level optional); show/edit 404 for trashed; destroy soft-deletes and flashes; stock_status
   for in_stock/low/out; partial unique index enforced at DB level.

**Done when:** focused checks green; the tech-lead's full gates are green, including the PostgreSQL parity run (migration on real PostgreSQL).

## S-03 · Costing engine — Wave 2 (BE #2) · size M
**Files:** `app/Domain/Costing/*`, `tests/Unit/Domain/Costing/*`, and the domain rule in `tests/Arch/ArchTest.php` (already added by S-00);
`composer.json` / `composer.lock` (*amended in review: `brick/math` becomes a direct `^1.0` dependency, since the domain uses its 1.x enum API*)
1. Tests first: the worked example (exact strings: material cost `"20.2540"`, unit cost `"35.7540"`, capacity `112`, bottleneck "Metal shank button 17mm"),
   every edge case in the domain skill, aggregation of duplicate material ids, deterministic tie-break, tiny and huge magnitudes.
2. Implement the API above with `Brick\Math\BigDecimal` only. No `Illuminate\*` imports.

**Done when:** 100 % of `App\Domain\Costing` branches covered by tests (state how you checked); arch rule passes; Larastan clean.

## S-02 · Materials — UI
### S-02a — Wave 2 (FE, components only, no routes needed) · size M
**Files:** `resources/js/components/materials/*`, `resources/js/components/shared/{page-header,empty-state,pagination,filters-bar,confirm-delete-dialog}.tsx`,
`resources/js/hooks/use-debounced-value.ts`, `resources/js/components/ui/{table,alert-dialog,textarea}.tsx` (via shadcn CLI), tests next to each
1. `StockBadge` (`in_stock` / `low` / `out`: text + icon + colour) + test.
2. `MaterialsTable` (props: `Material[]`, `onDelete`): table ≥ md (SKU, Name, Category, Stock + unit, Unit cost, Stock value, Status, actions);
   stacked cards < md; uses `<Money>` and `<Quantity>`. Row actions take an `hrefs` builder via props (Wayfinder is wired in S-02b) + test.
3. `FiltersBar` (search debounced 300 ms, category select, "Needs restock" toggle, sort select) as a **controlled** component that emits
   `MaterialFilters` changes + test. `useDebouncedValue` hook + test.
4. `Pagination` (from `meta.links`, accessible labels), `EmptyState`, `PageHeader` (title, description, actions slot).
5. `MaterialForm` (props: `initial: MaterialFormData`, options, `errors`, `processing`, `onSubmit`/form binding): all fields, decimal inputs
   `type="text" inputMode="decimal"` with the unit as suffix, required markers, inline errors with `aria-invalid` / `aria-describedby` + test.
6. `ConfirmDeleteDialog` (shadcn AlertDialog) + test.

### S-02b — Wave 3 (FE, after S-01 merged) · size S
**Files:** `resources/js/pages/materials/{index,create,edit,show}.tsx` (+ page tests), `resources/js/components/app-sidebar.tsx`,
`resources/js/test/*` (shared Radix/jsdom polyfills, Inertia `Form` mocking if needed; *amended at Wave 3 dispatch*)
1. Regenerate Wayfinder (`npm run build`). Sidebar item **Materials** (lucide `Boxes`).
2. Pages wire the S-02a components to Wayfinder + Inertia: index (filters → `router.get(index.url(), …, { preserveState: true, replace: true })`),
   create/edit (`<Form {...MaterialController.store.form()}>` / update), show (details card, edit + delete actions), breadcrumbs, `<Head>` titles.
3. Delete: `ConfirmDeleteDialog` → `router.delete(destroy(material.id))`. Toasts come from flash automatically.
4. Page tests with the Inertia helper (index renders rows and the empty state; form shows server errors).
5. If the stack is up: smoke in the browser (http://localhost:8000) and check the console is clean.

**Done when (S-02):** a user can add, find, edit and delete materials without typing a URL. Works at 375 px and in dark mode, keyboard-only flow works,
and all FE gates are green.

---

## Phase 1 sign-off (tech-lead → main session → human)
- [ ] S-00, S-01, S-02, S-03 approved and committed on `phase/1-materials`
- [ ] Full gates and PostgreSQL parity green; CI green on the branch (once a remote exists)
- [ ] Demo script passes: register → Materials → add "Denim 12oz Indigo" (m, 8.5000, 250) → search → filter "Needs restock" → edit → delete
- [ ] BACKLOG updated, follow-ups recorded, merge to `main` approved by the human

## Risks and mitigations
| Risk | Mitigation |
|---|---|
| Laravel Boost not yet installed (no `search-docs`) | Recommend `php artisan boost:install` before Wave 1. Until then, agents read `vendor/` sources for Inertia 3 / Laravel 13 APIs |
| `arch()->preset()->laravel()` flags starter-kit code | Targeted `->ignoring()` with each listed in the handoff; never ignore our own namespaces |
| SQLite vs PostgreSQL differences | Portable SQL only; partial index syntax shared by both; tech-lead runs the PostgreSQL parity suite for DB stories |
| Parallel agents in one working tree | Disjoint file lists, focused checks while running, full gates by the tech-lead after the wave |
| Native PHP 8.3 vs container 8.5 | Code targets 8.3; CI matrix runs 8.3 and 8.5 |
