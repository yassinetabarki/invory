# Story Plan: Product show, deferred sales panels (S-02 → RL-02)

> Status: **done** (verified 2026-10-14: committed `0241b7a`; extended by [fix-rl-02-product-defer-all.md](fix-rl-02-product-defer-all.md)) (Q-1 = RL-02 confirmed, Q-2 = skeleton gap accepted)
> Date: 2026-10-13
> Story: S-02 of [rendering-loading-plan.md](rendering-loading-plan.md) §4 "Skeleton / Progressive Loading"
> Owner: tech-lead · To be executed by `backend-developer` (BE) and `frontend-developer` (FE)
> Reference implementation: RL-01 (dashboard), [s04-s01-skeleton-dashboard-plan.md](s04-s01-skeleton-dashboard-plan.md), commit `3a2eeca`

**ID note.** Backlog `S-02` is already "Materials — UI" (Phase 1). Following decision D-1 of the RL-04 / RL-01 plan (approved 2026-10-13),
this story is called **RL-02** in commits and the backlog. It delivers the "product recent sales deferred" half of backlog **S-24**'s open bullet.

## 1. Intake facts (read from the code on 2026-10-13, HEAD `3a2eeca`, tree clean)

- `ProductController::show()` sends 3 props, **all lazy closures**:
  `product` (loads `category` + `components.material`, costs it, `ProductResource`), `sales_last_30_days` (`SalesReport::lastThirtyDays`,
  one filtered SUM over sales) and `recent_sales` (`SalesReport::recentOf`, newest 5, `setRelation('product', $product)`, so no product query).
  The two sales closures do **not** depend on the `product` closure (they only need the route-bound `$product`), so there is no ordering
  constraint between them (unlike F-49 on the dashboard).
- `pages/products/show.tsx` passes `sales_last_30_days` to `ProductHero` (`sales` prop) and `recent_sales` to
  `components/products/recent-sales-card.tsx` (`RecentSalesCard`, a different component from the dashboard's `components/dashboard/recent-sales-card.tsx`).
- `ProductHero` renders a 3-card grid: "Can produce now" (primary), "Unit cost", "Sold · last 30 days" (`section aria-label="Sales, last 30 days"`).
  It reads `sales.units_sold` / `sales.revenue` directly in JSX. `ProductHero` is used **only** by `products/show`.
- **No partial reload exists on `products/show`.** There is no `only: [...]` anywhere in production `resources/js`, and the page has no period
  switch (`sales_last_30_days` is always `last_30_days`). Recording a sale happens on `/sales` (`SaleController` → `back()` to `/sales`). The only
  writes that land on this page are product create/update (`to_route('products.show')`), which are full visits.
- **Correction to the story text:** the skeletons live in `resources/js/components/shared/skeletons.tsx` (import `@/components/shared/skeletons`),
  not `components/ui/skeletons.tsx`. `components/ui/` is shadcn-generated and must not be edited (CLAUDE.md). Available: `SkeletonCard
  {label, title?, rows?, thumb?, className?}`, `SkeletonRow`, `SkeletonStat {label, className?}`. `SkeletonStat` was built for exactly this story.
- Verified in `node_modules/@inertiajs/react` 3.8.0 (`types/Deferred.d.ts`): `children: ReactNode | ((slot: {reloading}) => ReactNode)`,
  `fallback` required and must be truthy. The Vitest mock (`resources/js/test/inertia.tsx`, `MockDeferred`) reads **mocked page props**
  (`setPageProps`), not the component's props, and follows `setPageProps` without a rerender.
- Server side (from RL-01, `inertia-laravel` 3.5.1): `Inertia::defer($cb, 'default')` leaves the prop out of the first response and lists it in
  `deferredProps.default`; one group = one deferred request. In the deferred request only the listed closures run, so the `product` closure
  (the costing) is **not** re-run. Test helpers already exist in `tests/Pest.php`: `loadedPageProps()`, `loadedPageProp()`, `partialReloadQueries()`.
- PHP tests that read the two props from the first response today (all must move): `ProductPagesTest.php` lines 252, 261–262, 286 (dataset
  `expectedProducts`, 7 rows), 376–381, 402–403; `ProductContractTest.php` 150–154; `ProductSalesFiguresTest.php` 37–41. `QueryBudgetTest.php`
  has two `products show` rows (first request only); they keep passing (the first request loses 2 queries).
- FE tests that render the page / hero: `pages/products/show.test.tsx` (sets page props `{}` in `beforeEach`, so with `<Deferred>` every test
  would see the fallbacks, same trap as RL-01 R-4) and `components/products/product-hero.test.tsx` (sets no page props).
- Environment: `php` on PATH is 8.4.26; Docker is up (`invory-laravel.test-1`, `invory-pgsql-1`, `invory-mailpit-1`), so parity can run.

## 2. Scope

**Changes**
- Backend: `sales_last_30_days` and `recent_sales` → `Inertia::defer(…)`, both in group `'default'` (one deferred request). Closure bodies and
  value shapes unchanged. `product` stays an eager lazy closure.
- Contract: `ProductShowProps.sales_last_30_days` and `.recent_sales` become optional (`?`), commented `// deferred prop`.
- Frontend: the "Sold · last 30 days" hero card is wrapped in `<Deferred data="sales_last_30_days" fallback={<SkeletonStat …/>}>` **inside
  `ProductHero`**; the `RecentSalesCard` is wrapped in `<Deferred data="recent_sales" fallback={<SkeletonCard …/>}>` in the page.

**Stays the same**
- `product` (BOM, cost, margin, capacity, bottleneck) is in the first response: header, photo, "Can produce now", "Unit cost", bottleneck
  callout, cost breakdown and composition paint at once. No costing code is touched (`app/Domain`, `ProductCostingService`, Resources).
- `SalesReport`, `SaleResource`, `ProductResource`: untouched. Values, scales (revenue scale-4 string, `units_sold` int) and ordering unchanged.
- `RecentSalesCard` (products) and the skeleton primitives: untouched. Breadcrumb layout (reads `product` only): untouched.

**Out of scope**
- Error state for a failed deferred request (`rescue`): existing follow-up **F-48** covers every deferred panel.
- `WhenVisible` (load recent sales on scroll) mentioned in backlog S-24: not asked here; `defer` is what the story asks.
- S-03 (material show), S-05 (products index loading state).

## 3. Decisions taken by the tech-lead (not product decisions; the human may override)

| # | Decision | Why |
|---|---|---|
| D-1 | Story label **RL-02** | Backlog S-02 is taken; same rule as RL-01 / RL-04 |
| D-2 | Both props in **one group** (`'default'`) | One deferred request instead of two app boots; both are cheap (one SUM, one `LIMIT 5`) and land together |
| D-3 | The `<Deferred>` for the sales stat lives **inside `ProductHero`** (as the story says); `ProductHero.sales` becomes optional | Keeps the 3-card grid in one component. Precedent: `components/materials/history-panel.tsx` owns its `<Deferred data="recent_history">`. `ProductHero` has one consumer, so knowing the page prop key is acceptable |
| D-4 | The sold card is extracted to a small local component (`SoldCard({ sales })`, not exported) and rendered as `{sales && <SoldCard sales={sales} />}` inside `<Deferred>` | `<Deferred>` children are built eagerly by the parent: `formatCount(sales.units_sold)` written inline would throw on `undefined` before `<Deferred>` decides to show the fallback. The guard also narrows the type with no `!` assertion and no fake zero-sales object |
| D-5 | Recent sales: page code `recentSales ?? []` inside `<Deferred>` | Same as RL-01 D-7 (`recentHistory ?? []`). The `[]` can't be reached at runtime |
| D-6 | Fallbacks: `<SkeletonStat label="Sold · last 30 days" />` and `<SkeletonCard title="Recent sales" label="recent sales" rows={5} thumb={false} />` | Real known text stays visible (RL-04 D-5). 5 rows = the max `recentOf` returns. `thumb={false}`: the product's sales rows have no thumbnail. Identical to the dashboard's recent-sales fallback |
| D-7 | Small visual differences accepted, no new skeleton and no className tuning: the "View all" link is absent while loading; `SkeletonStat`'s label is `text-sm font-medium` vs the hero's `0.8125rem semibold` | The constraint is "reuse the existing skeletons". The height difference is a few px (~32 + 14 px blocks vs a 35 px figure + hint line) |
| D-8 | **One commit** for BE + FE (RL-02-a + RL-02-b) | Bisect safety, as RL-01 D-9: the controller change alone crashes the page (`sales.units_sold` of `undefined`), the `domain.ts` change alone turns `types:check` red. "Never split a contract" |
| D-9 | **One wave, BE ∥ FE in parallel** | The contract is frozen below. FE's code compiles against both the current (required) and the new (optional) type, so neither developer waits. Full gates run once both handoffs are in |
| D-10 | Add a deferred-request query-budget test next to the existing `recent_history` one in `QueryBudgetTest` | ADR-0009 §8: every request of a page has a budget; the existing `products show` rows only measure the first request |

## 4. Frozen contract

### Controller (`app/Http/Controllers/ProductController.php`, `show()` only)

```php
return Inertia::render('products/show', [
    'product' => function () use ($product, $costing): ProductResource { /* unchanged */ },     // eager (lazy closure)
    'sales_last_30_days' => Inertia::defer(fn (): array => $sales->lastThirtyDays($product)),  // deferred, group 'default'
    'recent_sales' => Inertia::defer(fn (): array => SaleResource::collection($sales->recentOf($product))->resolve()), // deferred, 'default'
]);
```
Update the class docblock / an inline comment to say the two sales props are deferred (RL-02). No other method changes.

### Type (`resources/js/types/domain.ts`, `ProductShowProps` only)

```ts
export type ProductShowProps = {
    product: Product;
    sales_last_30_days?: { units_sold: number; revenue: Decimal }; // deferred prop
    recent_sales?: Sale[]; // deferred prop: newest 5
};
```

### `ProductHero` (`resources/js/components/products/product-hero.tsx`)

```tsx
type ProductHeroProps = {
    capacity: Capacity;
    unitCost: Decimal;
    /** Sales of the last 30 days: a deferred prop, undefined until it lands. */
    sales?: { units_sold: number; revenue: Decimal };
};
// third grid cell:
<Deferred data="sales_last_30_days" fallback={<SkeletonStat label="Sold · last 30 days" />}>
    {sales && <SoldCard sales={sales} />}
</Deferred>
```
`SoldCard` renders exactly today's third `HeroCard` (label, `aria-label="Sales, last 30 days"`, units, `<Money>` revenue). The first two cards are not wrapped.

### Page (`resources/js/pages/products/show.tsx`)

```tsx
<ProductHero capacity={product.capacity} unitCost={product.cost.unit_cost} sales={sales} />   {/* unchanged call */}
…
<Deferred data="recent_sales" fallback={<SkeletonCard title="Recent sales" label="recent sales" rows={5} thumb={false} />}>
    <RecentSalesCard sales={recentSales ?? []} recordHref={recordHref} viewAllHref={…unchanged…} />
</Deferred>
```

## 5. Subtasks and slicing table

| # | Subtask | Lane | Owner | Files (exact) | Lines (prod / test) | Tests allowed (confirmed: this is the pre-flight ack) | Commit label |
|---|---|---|---|---|---|---|---|
| 1 | **RL-02-a** product show defers the 2 sales props (backend + contract) | **CRITICAL** (contract edit + props shape change) | BE | **edit** `app/Http/Controllers/ProductController.php` (`show()` + docblock), **edit** `resources/js/types/domain.ts` (`ProductShowProps` only), **edit** `tests/Feature/Products/ProductPagesTest.php`, **edit** `tests/Feature/Products/ProductContractTest.php`, **edit** `tests/Feature/Sales/ProductSalesFiguresTest.php`, **edit** `tests/Feature/Performance/QueryBudgetTest.php` | ~8 / ~70 changed + ~35 new | **+2 new:** (1) ProductPagesTest `show` describe: "sends the product first, and the sales of the last 30 days and the recent sales in one deferred request" (`has('product')`, `missingAll(['sales_last_30_days','recent_sales'])` on the first response, `loadDeferredProps('default', fn => hasAll([both]))`). (2) QueryBudgetTest: "stays within the query budget when the deferred sales of the product page load, however many sales there are" (measure the deferred request with the demo data, add ~30 sales to the product, measure again: ≤ `QUERY_BUDGET` and equal counts). **Changed, not new:** (a) ProductPagesTest "sends the ProductShowProps contract": first-response `hasAll` → `product` only, the two sales shape assertions move into `loadDeferredProps`. (b) dataset `expectedProducts`: `sales_last_30_days.units_sold` read via `loadedPageProp` (outside `assertInertia`). (c) "sends the sales of the last 30 days and the newest 5 sales…" and "sends no sales and no margin for the unpriced draft": sales reads via `loadedPageProp` / `loadDeferredProps`. (d) ProductContractTest "sends the other props of the show page as before" and ProductSalesFiguresTest: `inertiaProps` / `pageProp` → `loadedPageProp`. **No** change to the two existing `products show` QueryBudgetTest rows | (lumped into the RL-02 commit) |
| 2 | **RL-02-b** product show wraps the sales stat and recent sales | NON-CRITICAL (reviewed with RL-02-a under the full checklist: same commit carries the contract edit) | FE | **edit** `resources/js/components/products/product-hero.tsx`, **edit** `resources/js/pages/products/show.tsx`, **edit** `resources/js/components/products/product-hero.test.tsx`, **edit** `resources/js/pages/products/show.test.tsx` | ~35 / ~50 changed | **+1 new** in show.test.tsx: "paints the product, its cost and composition at once, and shows loading placeholders for the sales figure and the recent sales until the deferred props land" (page props **without** the 2 keys → "Production capacity" and "Unit cost" regions + composition table visible, statuses `Loading Sold · last 30 days…` and `Loading recent sales…` present, no "Sales, last 30 days" region, no "Recent sales" table → `setPageProps` with both keys → real card (38 units) and table visible, statuses gone). **Changed, not new:** (a) show.test.tsx: a local `renderShow(props)` that calls `setPageProps({...props}, {url, component})` then `render`, used by every test; the TND test merges its `app` settings into the same page props. (b) product-hero.test.tsx: `renderHero` sets page props `{ sales_last_30_days: sales }` before rendering. No change to `recent-sales-card.test.tsx` or `skeletons.test.tsx` | `perf(products): defer the product page's sales figures behind skeletons (RL-02)`: one commit for rows 1 + 2 |

Totals: ~43 prod lines in 4 production files, net **+3** tests (BE 2, FE 1).

## 6. Execution order

```
Wave 1  RL-02-a (BE) ──┐  disjoint files, frozen contract (D-9): run in parallel
        RL-02-b (FE) ──┘
                       ▼
        TL: full gates on the whole tree, sequentially (F-22):
            composer test → npx vp test → npm run check → npm run types:check → npm run build
            + PostgreSQL parity (QueryBudgetTest counts queries; the parity DB is shared, so the TL runs it once, not the BE)
            full-checklist review of RL-02-a + RL-02-b as one unit
                       ▼
Commit (human approves): RL-02
```
Briefs will carry: the frozen contract above, the confirmed file and test list (CRITICAL pre-flight ack inside the brief; stop rule: any file
or test outside the list goes under "Open questions"), "don't `git add` / `git mv`", "run your focused checks sequentially", for BE "do not run
the parity suite", and for FE "don't edit `domain.ts`; BE makes the two props optional in this wave".

## 7. Acceptance criteria (one commit, see D-8)

1. A first visit to `/products/{id}` returns `product` and **not** `sales_last_30_days` or `recent_sales`. Those two arrive together in one
   deferred request with the same values as today (Denim Jacket: `{units_sold: 38, revenue: "3382.0000"}`, newest 5 sales ids 57, 52, 43, 40, 38).
2. The page paints the header, photo, "Can produce now", "Unit cost", bottleneck callout, cost breakdown and composition at once. The
   "Sold · last 30 days" card shows a `SkeletonStat` and the recent-sales panel a `SkeletonCard` until the deferred request lands, then the real ones.
   Each placeholder announces itself once to screen readers ("Loading …" status).
3. The first request and the deferred request each stay within `QUERY_BUDGET`, and the deferred one doesn't grow with the number of sales.

## 8. Risks and edge cases

| # | Risk / edge case | Mitigation |
|---|---|---|
| R-1 | **First-response props change shape.** ~14 assertions in 3 PHP files (incl. a 7-row dataset) read the two props from the first response and would fail or silently read `null` | All 3 files are in RL-02-a's list; `loadedPageProp()` exists. Reviewer greps `tests/` for both keys after the wave |
| R-2 | **Eager JSX children.** Writing the sold card inline inside `<Deferred>` evaluates `sales.units_sold` before the fallback decision → `TypeError` while loading | D-4 (`{sales && <SoldCard sales={sales} />}`). The new FE test renders without the props, so it would catch a regression |
| R-3 | **FE tests pass for the wrong reason.** The mocked `<Deferred>` reads `setPageProps`; with page props `{}` every existing test sees fallbacks. Some assertions could still pass by accident (the fallback shows "Recent sales" title and the "Sold · last 30 days" label) | RL-02-b brief: page props mirror the rendered props in every test (`renderShow`, `renderHero`). Reviewer checks the content assertions (38 units, €3,382.00, 6 table rows, "No sales recorded…") still run against real cards |
| R-4 | **Period partial reload:** none on this page (no period switch, no `only:` in production code), so no risk of re-requesting or blanking the deferred panels | Nothing to do. If a period switch is ever added to the product page, it must use `only` without the deferred keys (as `PERIOD_PROPS` on the dashboard) |
| R-5 | **Skeleton flash after every product save.** Create/update redirect to `products.show` as a full visit, so the two sales panels briefly show skeletons each time | Accepted: same as any first visit; the hero data (what the user just edited) is immediate |
| R-6 | **Extra round trip.** The deferred request re-runs auth, the session, route-model binding (1 product query) and the policy. The costing is not re-run (only listed closures resolve) | Accepted by design (same trade as RL-01 R-2). D-10 pins the deferred request's budget |
| R-7 | **No error state.** A failed deferred request leaves the skeletons on screen | Out of scope, tracked by **F-48** (now also covers RL-02) |
| R-8 | **Small layout shift** (D-7): stat label typography, no "View all" link while loading | Accepted; visual check at 375 px and in dark mode in review |
| R-9 | `<Deferred>` throws on a falsy `fallback` | Both fallbacks are elements |
| R-10 | Parity: `QueryBudgetTest` counts may differ on PostgreSQL | TL runs the parity suite after the wave (Docker is up) |
| R-11 | Vitest `user.type` flake under parallel load (F-22) | Gates run sequentially |

## 9. Questions for the human

No product decision is required: the scope, placement and fallbacks come from the story. Two confirmations, both with a default the plan
already follows:

- **Q-1 (confirm D-1):** label this story **RL-02** in the commit and the backlog (backlog S-02 is "Materials — UI")?
- **Q-2 (confirm D-7):** while the recent sales load, the skeleton shows the "Recent sales" title but **not** the "View all" link (it appears with
  the data). The alternative is a small change to the shared `SkeletonCard` (an `action` slot), which widens this story into RL-04's component.
  Default: accept the small difference.

## 10. Progress log

- 2026-10-13: plan written (planning-only mode). Intake: HEAD `3a2eeca`, tree clean, Docker up. Not dispatched.
- 2026-10-13: plan approved (Q-1 RL-02, Q-2 accept the gap). Intake gates on HEAD green (Pest 2048 passed / 26 skipped, Vitest 1132).
  Wave 1 (RL-02-a BE ∥ RL-02-b FE) dispatched.
- 2026-10-13: Wave 1 approved in round 1. Full gates are green: Pest 2050 passed / 26 skipped, Vitest 1133, check, types, build, and
  PostgreSQL parity 2076 passed. FE production lines came to +52 / −22 (budget ~35): the `SoldCard` extraction plus formatter wrapping, accepted.
  The FE page test uses `rerender` after `setPageProps`, because the page receives the deferred values as component props. Status:
  **delivered, awaiting commit approval**.

## 11. Follow-ups to record on delivery

- Backlog **S-24**: mark "product recent sales deferred" as delivered by RL-02 (the `WhenVisible` variant is not needed).
- **F-48**: add RL-02's two panels to the list of deferred panels that need a `rescue` error state.
- rendering-loading-plan.md §4: mark S-02 delivered as RL-02 with a link to this plan.
