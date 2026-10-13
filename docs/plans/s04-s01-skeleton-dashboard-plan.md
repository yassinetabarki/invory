# Story Plan: Skeleton primitives (S-04) + deferred dashboard panels (S-01)

> Status: **delivered 2026-10-13, awaiting commit** (Q-1 = A, Q-2 and Q-3 confirmed)
> Date: 2026-10-13
> Stories: S-04 and S-01 of [rendering-loading-plan.md](rendering-loading-plan.md) §4 "Skeleton / Progressive Loading"
> Owner: tech-lead · Executed by `frontend-developer` (FE) and `backend-developer` (BE)

**ID note (D-1).** `S-01` and `S-04` already name other stories in [BACKLOG.md](../BACKLOG.md) (Phase 1 "Materials backend", Phase 3
"Products + composition backend"). In this plan, in commit labels and in the backlog, these two stories are called **RL-04** (skeletons) and
**RL-01** (dashboard), where RL stands for Rendering & Loading. RL-01 delivers the dashboard part of backlog **S-24** ("Dashboard secondary panels
deferred"). The rest of S-24 stays open.

## Intake facts (read from the code on 2026-10-13, HEAD `33b3810`, tree clean)

- `DashboardController` sends 7 props, all closures: `period`, `periods`, `stats`, `top_products`, `needs_restock`, `recent_sales`, `alerts`.
  `stats` and `top_products` share a per-request memo of the period's sales lines. `stats` (counts), `top_products` (refs), `recent_sales`
  (refs) and `alerts` read the same request-scoped `ProductCatalog`, so today the costed products are built once per visit.
- `pages/dashboard.tsx` destructures every prop and passes it straight to `UnproducibleAlerts` (top of the page, renders `null` without alerts),
  `KeyFigures`, `TopProductsCard`, `NeedsRestockCard` (in a 2-column grid beside top products) and `RecentSalesCard`. The period switch is a partial
  reload `only: ['period', 'stats', 'top_products']`.
- Existing pattern: `materials/index` defers `recent_history`. Contract `recent_history?: StockMovement[]; // deferred prop`, page code
  `recentHistory ?? []` inside `<Deferred data="recent_history" fallback={<HistorySkeleton/>}>` (`components/materials/history-panel.tsx`).
- `components/ui/skeleton.tsx` (shadcn, generated, do not edit) already renders `bg-primary/10 animate-pulse rounded-md`. That meets the
  `animate-pulse` decision with no custom CSS.
- **There is no barrel in `components/shared/`.** Every import is per file (`@/components/shared/page-header`), and the only `index.ts` in
  `resources/js` is `types/index.ts`. See D-2.
- Verified in the sources:
  - `inertiajs/inertia-laravel` 3.5.1. `Inertia::defer($cb, string $group = 'default', bool $rescue = false)`. A deferred prop is left out of the
    first response and listed in `deferredProps[group]`. A partial reload whose `only` doesn't name it skips it and doesn't list it again, so the
    period switch won't trigger a second deferred load. `AssertableInertia::loadDeferredProps(groups|callback)` replays the deferred request.
  - `@inertiajs/react` 3.8.0. `<Deferred data fallback>` throws if `fallback` is falsy. It shows `children` once every key is defined in
    `usePage().props`. It flips to `reloading` only for partial reloads that request its keys, so the period switch won't flash the panels.
    Each deferred **group** is its own request.
  - Test helper `resources/js/test/inertia.tsx`: the mocked `<Deferred>` reads the **mocked page props** (`setPageProps`), not the component's
    props, and re-renders when `setPageProps` is called again.
- Environment: `php` on PATH is 8.4.26 (not the Laragon 8.3 build in CLAUDE.md, worth noting but not blocking). Docker is up (`invory-laravel.test-1`,
  `invory-pgsql-1`), so the parity suite can run.

## Scope

**In**
- RL-04: `SkeletonCard`, `SkeletonRow`, `SkeletonStat` in `components/shared/skeletons.tsx` + Vitest.
- RL-01 backend: `needs_restock`, `recent_sales`, `alerts` → `Inertia::defer` (one group). `stats`, `top_products`, `period`, `periods` stay eager.
  Contract: the 3 keys become optional in `DashboardProps`. PHP tests move to the deferred round trip.
- RL-01 frontend: wrap the 3 panels in `<Deferred>` with RL-04 fallbacks. The KPI row and top products are not wrapped.

**Out**
- S-02, S-03, S-05 (product show, material show, products index): they reuse RL-04 later.
- `rescue` / error state for a failed deferred request (follow-up F-48).
- Moving the alerts block on the page (see Q-1, option C).
- `SkeletonStat` has no consumer in RL-01 because the stats stay eager. It is built now for S-02 (the product hero's sales stat), as the human asked.

## Decisions taken by the tech-lead (not product decisions; the human may override)

| # | Decision | Why |
|---|---|---|
| D-1 | Stories are called RL-04 / RL-01 in commits and the backlog | Backlog S-01 / S-04 are already taken; `git log --grep` must stay unambiguous |
| D-2 | **No barrel file.** The three primitives live in **one module**, `components/shared/skeletons.tsx`, imported as `@/components/shared/skeletons` | The repo imports per file. A one-off `index.ts` in `shared/` would start a second convention. One module gives the "single import place" the story asks for |
| D-3 | Build on the generated `ui/skeleton` (`animate-pulse`). Add `motion-reduce:animate-none` (a Tailwind utility, not custom CSS) | Meets Q4 (pulse), respects `prefers-reduced-motion` (WCAG 2.3.3) |
| D-4 | Accessibility: the decorative blocks are `aria-hidden`. `SkeletonCard` and `SkeletonStat` each carry one `role="status"` with sr-only text "Loading {label}…". `SkeletonRow` is decorative only, so a card with 5 rows announces once, not 5 times | Same pattern as `HistorySkeleton` (`history-panel.tsx`), which the existing tests already query (`getByText('Loading history…')`) |
| D-5 | Fallbacks show the **real, known text** (card title, stat label) and pulse only the data | The page outline and heading landmarks stay stable while loading. Less layout shift |
| D-6 | All three deferred props go in **one group** (`'default'`), so the dashboard makes one deferred request | Separate groups mean 3 parallel requests, each booting the app, resolving the session and (for `alerts` and `recent_sales`) rebuilding the `ProductCatalog`. One request builds it once |
| D-7 | Page code reads the deferred props as `prop ?? []` inside `<Deferred>` children | Existing precedent (`recentHistory ?? []`). Keeps `tsc` strict with no non-null assertions. The `[]` can't be reached at runtime |
| D-8 | Shared Pest helpers `loadedPageProps()` / `loadedPageProp()` in `tests/Pest.php` | ~12 dashboard assertions read the deferred props. One helper keeps the edits mechanical, and S-02 / S-03 reuse it |
| D-9 | RL-01 is **one commit** (BE + FE lumped) | Bisect safety: the controller change alone crashes the page (`alerts.length` of `undefined`), and the `domain.ts` change alone turns `types:check` red. TEAM.md: "never split a contract" |

## Frozen contract

### RL-04: component API (`resources/js/components/shared/skeletons.tsx`)

```tsx
/** One decorative list/table row: optional thumb square + two text lines + a short trailing block. aria-hidden. */
export function SkeletonRow(props: { thumb?: boolean /* default true */; className?: string }): JSX.Element;

/** A dashboard-panel-shaped placeholder: same shell as the panel cards (`rounded-xl border bg-card p-5 text-card-foreground`),
 *  optional visible title (an <h2 class="text-base font-bold">), `rows` × <SkeletonRow>, one role="status" "Loading {label}…". */
export function SkeletonCard(props: { label: string; title?: string; rows?: number /* default 3 */; thumb?: boolean; className?: string }): JSX.Element;

/** A KPI-card-shaped placeholder: same shell as <StatCard> (`rounded-xl border bg-card px-5 py-[18px]`), the visible label,
 *  a pulsing figure block and hint line, one role="status" "Loading {label}…". */
export function SkeletonStat(props: { label: string; className?: string }): JSX.Element;
```
All pulsing blocks are `<Skeleton className="… motion-reduce:animate-none" aria-hidden="true" />` from `@/components/ui/skeleton`.

### RL-01: props (`DashboardController`) and type (`resources/js/types/domain.ts`)

```php
'period'        => $period->value,                          // eager (unchanged)
'periods'       => SalesPeriod::options(),                  // eager (unchanged)
'stats'         => fn (): array => [...],                   // eager closure (unchanged)
'top_products'  => function () {...},                       // eager closure (unchanged)
'needs_restock' => Inertia::defer(fn (): array => ...),     // deferred, group 'default'
'recent_sales'  => Inertia::defer(function (): array {...}),// deferred, group 'default'
'alerts'        => Inertia::defer(fn (): array => ...),     // deferred, group 'default'
```
The bodies of the closures and the value shapes **do not change**.

```ts
export type DashboardProps = {
    period: SalesPeriod;
    periods: Option<SalesPeriod>[];
    stats: DashboardStats;
    top_products: { by_units: TopProduct[]; by_revenue: TopProduct[] }; // max 5 each, for the period
    needs_restock?: Material[]; // deferred prop: max 5, out first, then low; by name
    recent_sales?: Sale[]; // deferred prop: newest 5, any period
    alerts?: UnproducibleAlert[]; // deferred prop: every product that can't be produced, by name
};
```

### RL-01: page structure (`resources/js/pages/dashboard.tsx`)

```tsx
<Deferred data="alerts" fallback={<span role="status" className="sr-only">Checking production alerts…</span>}>  {/* Q-1 = A */}
    <UnproducibleAlerts alerts={alerts ?? []} />
</Deferred>
<KeyFigures … />                                            {/* not wrapped */}
<div className="grid … xl:grid-cols-[…]">
    <TopProductsCard … />                                   {/* not wrapped */}
    <Deferred data="needs_restock" fallback={<SkeletonCard title="Needs restock" label="restock list" rows={5} />}>
        <NeedsRestockCard materials={needsRestock ?? []} />
    </Deferred>
</div>
<Deferred data="recent_sales" fallback={<SkeletonCard title="Recent sales" label="recent sales" rows={5} thumb={false} />}>
    <RecentSalesCard sales={recentSales ?? []} />
</Deferred>
```
`PERIOD_PROPS` doesn't change.

### Test helpers (frozen signatures)

```php
// tests/Pest.php
/** The page props once every deferred group has loaded: the first response merged with the deferred request(s), as the client ends up with them. */
function loadedPageProps(TestResponse $response): array;
/** Like pageProp(), on loadedPageProps() (supports `a.*.b`). */
function loadedPageProp(TestResponse $response, string $path): mixed;
```
Implemented with `$response->assertInertia(fn (Assert $page) => $page->loadDeferredProps(fn (Assert $reloaded) => …))`, the same way as
`StockHistoryPropsTest`. Use a plain closure capturing by reference (see that file's comment on arrow functions).

## Subtasks and slicing table

| # | Subtask | Lane | Owner | Files (exact) | Lines (prod / test) | Tests allowed (confirmed: this is the pre-flight ack) | Commit label |
|---|---|---|---|---|---|---|---|
| 1 | **RL-04** skeleton primitives | NON-CRITICAL | FE | **create** `resources/js/components/shared/skeletons.tsx`, **create** `resources/js/components/shared/skeletons.test.tsx` | ~90 / ~70 | 3 `it`s, one per primitive: **SkeletonCard** announces "Loading {label}…" as a status, shows the title, renders `rows` decorative rows hidden from the a11y tree (default 3). **SkeletonStat** shows the label and announces loading. **SkeletonRow** is aria-hidden and drops the thumb with `thumb={false}`. No snapshot, no class-name assertions except one check that the blocks pulse (`animate-pulse`) | `feat(ui): shared skeleton primitives for deferred panels (RL-04)` |
| 2 | **RL-01-a** dashboard defers 3 panels (backend + contract) | **CRITICAL** | BE | **edit** `app/Http/Controllers/DashboardController.php`, **edit** `resources/js/types/domain.ts` (DashboardProps only), **edit** `tests/Pest.php` (2 helpers), **edit** `tests/Feature/Dashboard/DashboardTest.php`, **edit** `tests/Feature/Performance/PartialReloadTest.php`, **edit** `tests/Feature/Materials/MaterialUsedInCountTest.php`, **edit** `tests/Feature/Products/ProductContractTest.php` | ~15 / ~110 changed | **+1 new:** DashboardTest "sends the stats and top products first, and the restock list, recent sales and alerts in one deferred request" (`missingAll` the 3 on the first response, `hasAll` the 4 eager ones, `loadDeferredProps('default', …)->hasAll` the 3). **Changed, not new:** (a) every DashboardTest read of the 3 props → `loadedPageProp`, and the first test's `hasAll([7])` → eager 4 + loaded 3. (b) DashboardTest "answers within the query budget…": measure the first request **and** the deferred request, each ≤ 10 and constant as materials/products/sales grow. (c) PartialReloadTest dashboard: "runs the restock list on a full visit" → "…in the deferred request, not in the first response" (0 in first, 1 in deferred); "builds the costed products once…" → once **per request** (first: stats + rankings; deferred: alerts + recent sales); the period-switch and `only: needs_restock` tests stay as they are. (d) MaterialUsedInCountTest ×2 and ProductContractTest ×1: read via the helper. **No** new QueryBudgetTest rows | (lumped into the RL-01 commit) |
| 3 | **RL-01-b** dashboard page wraps 3 panels | NON-CRITICAL (reviewed with RL-01-a under the full checklist, because the commit carries a contract edit) | FE | **edit** `resources/js/pages/dashboard.tsx`, **edit** `resources/js/pages/dashboard.test.tsx` | ~30 / ~60 changed | **+1 new:** "paints the key figures and top products at once, and shows a loading placeholder for the restock list and recent sales [and alerts per Q-1] until the deferred props land" (render with page props **without** the 3 keys → KPI + top products visible, `Loading restock list…` / `Loading recent sales…` statuses → `setPageProps` with the 3 keys → panels visible, statuses gone). **Changed, not new:** test setup sets page props equal to the rendered props (a local `renderDashboard(props)` that calls `setPageProps(props, {url, component})` then `render`), because the mocked `<Deferred>` reads page props. No component-test edits (`components/dashboard/*` unchanged) | `perf(dashboard): defer restock, recent sales and alerts behind skeletons (RL-01)`: one commit for rows 2 + 3 |

Totals: ~135 prod lines, 4 production files + 1 created module, net **+5** tests (3 + 1 + 1).

## Execution order / waves

```
Wave 1  RL-04 (FE) ─────────────┐   disjoint files: run in parallel
        RL-01-a (BE) ───────────┤
                                ▼
        TL review RL-04 (fast lane; focused gates on its 2 files, since RL-01-a's domain.ts edit
        makes `types:check` red on pages/dashboard.tsx until wave 2. That red is expected and outside RL-04's files)
                                ▼
Wave 2  RL-01-b (FE): needs the approved RL-04 API + RL-01-a's domain.ts edit
                                ▼
        TL: full gates on the whole tree (sequential: composer test → npx vp test → npm run check → types:check → build)
            + PostgreSQL parity (DashboardTest + PartialReloadTest carry driver-specific SQL matchers)
            full-checklist review of RL-01-a + RL-01-b as one unit
                                ▼
Commits (human approves): 1. RL-04   2. RL-01
```
This still honours "RL-04 first": it is reviewed and committed first. RL-01-a doesn't import the skeletons, so it can start in parallel and
the plan saves a wave. RL-04's commit is new files only, imported by nobody before RL-01, so its own head is green when its focused checks are
(`npx vp test resources/js/components/shared/skeletons.test.tsx`, `npx vp check` on both files, `tsc` errors limited to `pages/dashboard.tsx`).
If the human prefers strictly sequential, the alternative is Wave 1 RL-04, then Wave 2 RL-01-a ∥ RL-01-b, with no other change to the plan.

Briefs will carry: the frozen contract above, the confirmed test list (CRITICAL pre-flight ack inside the brief, stop rule: any file or test
outside the list goes under "Open questions"), "don't `git add` / `git mv`", "run the gates sequentially (F-22)", and for BE "do not run the
parity suite yourself; the tech-lead runs it once (shared `testing` DB)".

## Acceptance criteria

**RL-04**
1. `SkeletonCard`, `SkeletonRow` and `SkeletonStat` are exported from `@/components/shared/skeletons`. They pulse with `animate-pulse` (none with
   reduced motion), use the panel / StatCard shells, and give screen readers one "Loading …" status per card or stat.

**RL-01**
1. A first visit to `/dashboard` returns `period`, `periods`, `stats` and `top_products`, and **not** `needs_restock`, `recent_sales` or `alerts`.
   Those three arrive together in one deferred request with the same values as today.
2. The page paints the KPI row and top products at once. The restock and recent-sales panels show skeleton cards (alerts per Q-1) until the
   deferred request lands, then the real panels.
3. The period switch still reloads only `period`, `stats` and `top_products`. It neither re-requests nor blanks the deferred panels.
4. Each of the two requests stays within 10 queries and doesn't grow with the number of materials, products or sales.

## Risks and gotchas

| # | Risk | Mitigation |
|---|---|---|
| R-1 | **The first-response props change shape.** ~12 assertions in 4 PHP test files read `needs_restock` / `recent_sales` / `alerts` from the first response (`pageProp`, `inertiaProps`, `hasAll`) and will fail or silently read `null` | All 4 files are in RL-01-a's list. `loadedPageProp()` helper (D-8). Reviewer greps `tests/` for the 3 keys after the wave |
| R-2 | **Total DB work grows across the two round trips.** The `ProductCatalog` (products + lines + materials) is built in the first request (stats, rankings) **and** in the deferred one (alerts, recent-sales refs). That's ~4 more queries per dashboard visit in total, and a second app boot. That's the price of painting earlier | Accepted by design. PartialReloadTest pins "once per request". If it matters later, S-23 metrics / caching is the lever, not regrouping |
| R-3 | **Layout shift from the alerts block** (top of the page, usually empty: a placeholder that then collapses pushes the KPI row up) | Q-1 |
| R-4 | **FE page tests break silently.** The mocked `<Deferred>` reads `setPageProps`, while the tests render `<Dashboard {...props}/>` with page props `{}`, so every existing test would see fallbacks. Some still pass by accident because the fallback shows the card title (D-5) | RL-01-b brief: page props mirror rendered props in every test. Reviewer checks that the content assertions (restock rows, recent sales, alerts) still run on real panels |
| R-5 | **Bisect red window.** After wave 1, `types:check` is red on `pages/dashboard.tsx` (optional props passed to required component props) until RL-01-b lands | Expected and documented. RL-01 is one commit (D-9), so no committed head is red |
| R-6 | `<Deferred>` throws on a falsy `fallback` (`null` is not allowed) | Every fallback is an element, including the alerts one |
| R-7 | **No error state.** If the deferred request fails (500), the skeletons stay on screen. Inertia 3's `rescue` (server `defer(..., rescue: true)` + `<Deferred rescue>`) is not used yet, same as `recent_history` today | Out of scope. Follow-up **F-48** proposed: rescue + an inline "Couldn't load, retry" for every deferred panel, decided once for S-01/S-02/S-03 |
| R-8 | SQL-text matchers in `tests/Feature/Performance` (`NEEDS_RESTOCK_QUERY`, `SUMMARY_SCAN`) behave differently on PostgreSQL | Tech-lead runs the parity suite after wave 2 (Docker is up) |
| R-9 | Long `user.type` Vitest flake (F-22) when gates run in parallel | Gates run sequentially |

## Questions for the human

**Answered 2026-10-13:** Q-1 = **A** (the alerts fallback is only an sr-only "Checking production alerts…" status, with no visible placeholder).
Q-2 and Q-3 are confirmed (RL-04 / RL-01 labels; one `skeletons.tsx` module, no barrel).

- **Q-1: Alerts placeholder while loading.** The alerts block is at the very top and is **empty most of the time** (demo data: 1 alert; often 0).
  - **A (recommended):** no visible placeholder. The fallback is just an sr-only "Checking production alerts…" status. Alerts appear above the
    KPI row when they arrive. That's a one-time downward shift, and only when there is something to warn about.
  - **B:** a one-row skeleton the height of an alert callout. The page shifts up every time there are no alerts, which is the common case.
  - **C:** move the alerts block below the KPI row (layout change, out of this plan's scope).
  The story text said "SkeletonCard (or SkeletonRow)". A is a small departure from it, so it needs your yes.
- **Q-2 (confirm D-1):** may these stories be renamed **RL-04 / RL-01** in commits and the backlog, to avoid clashing with backlog S-01 / S-04?
- **Q-3 (confirm D-2):** one module `components/shared/skeletons.tsx` instead of a new barrel `components/shared/index.ts`?

## Progress log

- 2026-10-13: plan approved. Intake gates on HEAD `33b3810` were green. Wave 1 (RL-04 FE ∥ RL-01-a BE) was dispatched.
- 2026-10-13: Wave 1 approved in round 1. One change from the plan: in the controller `alerts` now sits **before** `recent_sales`, because
  deferred props resolve in array order and `alerts` builds the `ProductCatalog` that `recent_sales` takes its refs from. Otherwise recent sales
  would cost one extra products query. `PartialReloadTest` pins "once per request".
- 2026-10-13: Wave 2 (RL-01-b) approved in round 1. Full gates are green: Pest 2048 passed / 26 skipped, Vitest 1132, check, types, build, and
  PostgreSQL parity 2074 passed. Status: **delivered, awaiting commit approval**.

## Follow-ups to add to the backlog on delivery

- **F-48 (FE + BE):** error state for deferred panels (`rescue`), applied to `recent_history`, the dashboard, and S-02 / S-03.
- Backlog **S-24:** tick "Dashboard secondary panels deferred" (delivered by RL-01). Material history / product recent sales stay with S-02 / S-03.
