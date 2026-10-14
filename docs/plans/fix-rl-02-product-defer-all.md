# Bug fix plan: product show page blocks navigation — defer all props (RL-02 fix)

> Status: **done** (verified 2026-10-14: committed `a9208d3` + `c809962`; the commit also deferred the dashboard KPIs). Ran in PLAN + EXECUTE mode, fix direction decided by the main session, no human review between phases
> Date: 2026-10-13 · Branch: `fix/rl-02-product-show-loading` (HEAD `0241b7a`)
> Owner: tech-lead · Executed by `backend-developer` (BE) ∥ `frontend-developer` (FE), one wave, one commit
> Follows: [s02-product-show-defer-plan.md](s02-product-show-defer-plan.md) (RL-02, commit `0241b7a`)

## 1. Problem

After RL-02, clicking a product link still leaves the user on the previous page until the first Inertia response arrives. `product` is an
**eager lazy closure**: on a full visit it runs before the response is sent (eager-load `category` + `components.material`, costing maths,
`ProductResource`). Then a second request fetches the deferred sales props. Result: a blocked navigation **and** two round-trips.

## 2. Intake facts (read on 2026-10-13)

- `ProductController::show()` (lines 77–91): `product` lazy closure; `sales_last_30_days`, `recent_sales` → `Inertia::defer(…)` (group `default`).
- `inertia-laravel` **3.5.1**: `ResponseFactory::defer(callable $callback, string $group = 'default', bool $rescue = false)`. Omitting the
  group puts the prop in `'default'`. One group = one deferred request. `@inertiajs/react` **3.8.0**.
- Inertia React re-evaluates `Component.layout(props)` on every render (`dist/index.js`, `layoutValue(props)`), so the breadcrumbs pick up
  the product name once the deferred request lands.
- The Vitest `<Deferred>` mock accepts an **array** `data` (all keys must be present) and follows `setPageProps` without a rerender.
- `<Deferred>` children are **built eagerly by the parent** (RL-01 lesson): JSX that dereferences `product` must sit behind a guard
  (`{product && <ProductDetails … />}`), otherwise it throws before the fallback shows.
- **Uncommitted work already in the tree, not part of this fix** (dashboard "defer stats + top products in group `primary`"):
  `app/Http/Controllers/DashboardController.php`, `resources/js/pages/dashboard.tsx`, `resources/js/types/domain.ts` (the `DashboardProps`
  hunk), `tests/Feature/Dashboard/DashboardTest.php`, `tests/Feature/Performance/PartialReloadTest.php`,
  `tests/Feature/Products/ProductContractTest.php` (the dashboard `stats.*` hunk, lines ~190–194). Nobody touches those hunks. Two files are
  shared with this fix (`domain.ts`, `ProductContractTest.php`), so the commit must stage **hunks**, not whole files (§6).
- PHP tests that read `product` from the **first** response of `products.show` (all must move to the deferred request):
  `ProductPagesTest.php` (~248–470: defer test, contract test, `expectedProducts` dataset, golden Denim Jacket, photo, empty BOM, …),
  `ProductContractTest.php:103–104`, `ProductWriteTest.php:215–231, 440–447`, `MaterialReferenceTest.php:252`.
  `QueryBudgetTest.php`: the two `products show` dataset rows now measure the instant first response (keep them); the "deferred sales" test
  must load all three props. `ProductSalesFiguresTest.php` already uses `loadedPageProp()`.
- Environment: `php` 8.4.26 on PATH; Docker up (`invory-laravel.test-1`, `invory-pgsql-1`).

## 3. Scope

**Changes**
- BE: `product` → `Inertia::defer(…)` with no group argument (`'default'`), same closure body. All three props in one group → the first
  response has none of them, one deferred request brings all three.
- Contract: `ProductShowProps.product` → `product?: Product; // deferred prop`.
- FE (`pages/products/show.tsx`): one `<Deferred data={['product', 'sales_last_30_days', 'recent_sales']} fallback={<ProductPageSkeleton />}>`
  around the whole page body; the inner `<Deferred data="recent_sales">` goes. `<Head title={product?.name ?? 'Product'} />` outside it.
  The body moves into an unexported `ProductDetails` component rendered behind `{product && …}`. The layout adds the product crumb only
  when `props.product` is set. `ProductPageSkeleton` is an unexported function in `show.tsx` built from `SkeletonStat` / `SkeletonCard`
  (+ the generated `Skeleton` primitive for the header bar, import only).
- FE (`components/products/product-hero.tsx`): the inner `<Deferred data="sales_last_30_days">` goes; `sales` becomes **required**
  (decision D-2) and the `SoldCard` renders unconditionally.

**Stays the same**: closure bodies and value shapes, costing code (`app/Domain`, `ProductCostingService`, Resources), `SalesReport`,
`skeletons.tsx`, `RecentSalesCard`, every other page.

**Out of scope**: error state of a failed deferred request (F-48); `products/edit` (still an eager closure — the edit form needs the data
to build its initial state; separate story if wanted, follow-up F-50).

## 4. Decisions

- **D-1** One group (`default`), not two: the product data and the sales data are both cheap compared with a blocked navigation, and one
  deferred request is fewer round-trips than RL-02's design. Rejected: `product` in `'primary'` + sales in `'default'` (two requests).
- **D-2** `ProductHero.sales` back to **required**: the hero only renders inside the outer `<Deferred>`, so `sales` is always present;
  an optional prop would carry a dead branch. `capacity` / `unitCost` stay required (verified: the hero renders only after `product` lands).
- **D-3** The whole page body goes into an unexported `ProductDetails` component (hooks included), so no hook is conditional and no JSX
  dereferences `product` before it lands.
- **D-4** One commit (bug fix, contract + both sides interlocked).

## 5. Slicing table

| # | Subtask id | Lane | Files (exact paths) | Line budget | Tests allowed | Owner | Commit label |
|---|---|---|---|---|---|---|---|
| 1 | FIX-RL02-BE | CRITICAL (contract edit) | `app/Http/Controllers/ProductController.php` (`show()` only), `resources/js/types/domain.ts` (`ProductShowProps` only), `tests/Feature/Products/ProductPagesTest.php`, `tests/Feature/Products/ProductContractTest.php` (product hunk only), `tests/Feature/Products/ProductWriteTest.php`, `tests/Feature/Reference/MaterialReferenceTest.php`, `tests/Feature/Performance/QueryBudgetTest.php` | ~10 prod, ~80 test churn | 0 new tests; update the defer test (first response misses all 3 + **no `product_components` query in the first response** + one deferred group has all 3); move every other read to the deferred request | BE | `fix(products): defer all product page props so navigation is instant (RL-02)` |
| 2 | FIX-RL02-FE | CRITICAL (validation-free, but contract consumer + eager-children trap) | `resources/js/pages/products/show.tsx`, `resources/js/components/products/product-hero.tsx`, `resources/js/pages/products/show.test.tsx`, `resources/js/components/products/product-hero.test.tsx` | ~120 prod | rewrite the "deferred" page test (skeletons only → all content after landing); 1 layout assertion (no product → only "Products" crumb); hero test: drop the placeholder case | FE | same commit |

## 6. Commit (one)

`fix(products): defer all product page props so navigation is instant (RL-02)` — files: the 11 above, **staging only the product hunks** of
`resources/js/types/domain.ts` and `tests/Feature/Products/ProductContractTest.php` (`git add -p`), plus this plan. The dashboard WIP stays unstaged.

## 7. Risks

| # | Risk | Mitigation |
|---|---|---|
| R-1 | Eager `<Deferred>` children throw on `product.x` before the fallback | D-3: body in `ProductDetails` behind `product &&` |
| R-2 | Breadcrumbs / title without a product | layout guard + `Head` fallback `'Product'`; layout re-runs when props land (§2) |
| R-3 | PHP tests silently assert nothing after the move | grep every `products.show` read; `loadDeferredProps('default', …)` / `loadedPageProp()` |
| R-4 | Page test asserts against fallbacks (mock reads `setPageProps`) | keep `renderShow` mirroring props into `setPageProps` |
| R-5 | Hunk-level staging of shared files | §6, listed in the delivery report |
| R-6 | `ProductDetails` remount resets the delete-dialog state on a deferred reload | none needed: no partial reload on this page (RL-02 intake) |

## 8. Progress log

- 2026-10-13: plan written; baseline gates run on HEAD + dashboard WIP; wave dispatched (BE ∥ FE).
