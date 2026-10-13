# Rendering & Loading — Analysis & Plan

> Status: **Approved — W-01 done, remainder pending**
> Date: 2026-10-13
> Author: main session (discussion draft)

---

## 1. Current Rendering Flow

Invory is an Inertia SPA: Laravel handles routing and data, React renders the UI on the client. No full page reload after first boot.

```
Browser (React + Inertia client)
  → XHR GET /products/{id}
  → Auth + Policy check          (middleware, Gate::authorize)
  → Controller builds props      (lazy closures skipped by partial reloads)
  → Inertia::render('page', props)  (all non-deferred props → JSON)
  → React renders page with all props at once
  → [second round-trip] Inertia::defer props arrive → React updates those sections
```

### Mechanisms already in use

| Mechanism | Status | Notes |
|---|---|---|
| Lazy closures | ✅ Active | Every expensive prop is a PHP closure; partial reloads skip it |
| Partial reloads | ✅ Active | Filter/sort/pagination/period changes send `only: [...]` |
| `Inertia::defer` | ✅ Active | Used for `recent_history` on materials index |
| Reference once-prop | ✅ Active | Units and categories shared globally, never re-queried per page |
| Hover prefetch | ⚠️ Under question | `<Link prefetch cacheTags=…>` fires a request on every hover, cached 30s |
| Skeleton UI | ❌ Not yet | No `<Deferred>` / `<WhenVisible>` wrappers in React; no skeleton components |

---

## 2. What's Already Good

- Architecture is textbook Inertia: thin controllers, actions for writes, pure domain for maths.
- Lazy closures on every controller prevent unnecessary computation during partial reloads.
- Dashboard period switch is a partial reload — only `stats` + `top_products` re-run.
- `recent_history` on materials list is already deferred — the pattern exists and works.
- Reference data is shared and versioned — zero per-page option queries.
- No N+1 on the BOM: components are eager-loaded with `load(['components.material'])`.
- Money is always decimal strings + `BigDecimal`; UI only formats, never computes.
- ADR-0009 documents the data-loading rules — the team has a contract.

---

## 3. Issues & Opportunities

### A — Hover prefetch: should it stay?

Every major nav link (`materials`, `products`, `sales`, `dashboard`) and every CTA fires a full page request on hover. The response is cached for 30 seconds under a tag; writes call `invalidateCacheTags` to bust it.

**Arguments for keeping it**
- Navigation feels instant for the common case (sidebar nav).
- Inertia 3's cache + tag invalidation makes stale data unlikely for short sessions.
- Zero JS complexity — no loading state needed, data is already there.

**Arguments against**
- Every hover = a server round-trip + full query execution, even if the user never clicks.
- Hovering while scanning the sidebar (4 items) can fire 4 parallel requests.
- 30s stale window: a restock done in tab A may not appear when navigating from tab B within the window.
- No fallback loading state if the prefetch misses (cache expired mid-hover) — navigation still takes the full round-trip with no visual feedback.

**Recommendation:** Remove hover prefetch entirely. Replace perceived speed with skeleton loading (below). The 30s cache window is too short to add real value once a user returns from another tab.

---

### B — Skeleton / progressive loading

Currently the page is empty (Inertia loading bar) until all non-deferred props arrive. With `Inertia::defer` + React `<Deferred>` / `<WhenVisible>`, the shell renders instantly and only the heavy sections wait.

**Page heat map**

| Page | Load | Props that are heavy |
|---|---|---|
| `dashboard` | 🔴 HIGH | stats · top_products · needs_restock · recent_sales · alerts |
| `products/index` | 🔴 HIGH | products list with BOM + capacity costing per row |
| `products/show` | 🟡 MEDIUM | product+BOM+cost · sales_last_30_days · recent_sales |
| `materials/index` | 🟡 MEDIUM | already deferred: `recent_history` ✓ |
| `materials/show` | 🟡 MEDIUM | usage (BOM scan) · history (ledger) |
| `sales/index` | 🟢 LOW | paginated list, filtered server-side |

**The right tool: `Inertia::defer` — not a separate fetch API.**

Reasons:
- Single auth session — deferred props reuse the same authenticated request, no new endpoints.
- Same `domain.ts` contract — Resource/props type unchanged.
- Zero client-side state management — Inertia merges deferred data automatically.
- Inertia 3 ships `<Deferred>` with a `fallback` slot for skeleton components.

**Note on "parallel" loading:** `Inertia::defer` sends one second request for all deferred props together (not truly parallel). True parallelism requires `useHttp` hooks + dedicated JSON routes (ADR-0009 rule 5). That is a larger investment and is not recommended for this scope.

---

### C — Tech-lead workflow: plan-first before any code

The current chain (human → main session → tech-lead → devs) has no human-review step before the tech-lead dispatches work. The proposed change:

1. Tech-lead receives a story or feature request.
2. Tech-lead writes a **plan document**: scope, subtasks, file list, risks, open questions.
3. Main session presents the plan to the human for approval.
4. Human approves (or amends). **Only then** does the tech-lead dispatch backend/frontend agents.

Requires updating `CLAUDE.md` and `docs/TEAM.md`.

---

### D — Best practices audit

| Finding | Status |
|---|---|
| Eager-load relations before Resources (no N+1 inside serialization) | ✅ Used on product show |
| `->without('category')` strips unneeded auto-loaded relations | ✅ Used |
| Paginate all lists | ✅ |
| Reference data via once-prop | ✅ |
| Dashboard runs `unproducibleCount()` and `count()` as separate calls | ⚠️ Verify they share a query |
| Products index: costing runs on every row | ⚠️ Post-MVP: `product_metrics` table (already in scalability plan) |
| No Pest `expectQueryBudget()` test on the dashboard | ⚠️ ADR-0009 §8 requires one per index page |
| `materials/show` — eager-load of `unit`/`category` on the material itself | ⚠️ Verify with `Model::shouldBeStrict()` |

---

## 4. Task Breakdown

### Workflow

| ID | Task | Effort | Type |
|---|---|---|---|
| W-01 | Define plan-first protocol for tech-lead | Low | Docs only — ✅ done |

Updated `CLAUDE.md` and `docs/TEAM.md` with the two-phase dispatch protocol and plan document format. Q2 resolved: plans are committed `docs/plans/<story>-plan.md` files (consistent with existing plans, traceable in git history).

---

### Hover Prefetch Removal

| ID | Task | Effort | Type |
|---|---|---|---|
| P-01 | Remove `prefetch` from all navigation links | Low | Frontend (26 prod files + 11 tests + 1 guard) — ✅ done 2026-10-13 |
| P-02 | Decide fate of `prefetchProps` helper and cache-tags | Low | Frontend + Decision — ✅ done 2026-10-13 (system removed) |

**P-01:** Remove `prefetch: true` from all `<Link>` components (sidebar nav, CTAs, action buttons). Keep `cacheTags` / `invalidateCacheTags` on writes for now.

*P-01 delivery notes (tech-lead, 2026-10-13):* the audit found 26 production files, not ~8 (every `{...prefetchProps(...)}` / `{...prefetchPropsForUrl(...)}` spread plus a bare `prefetch` in `user-menu-content.tsx`). The whole spread was removed, `cacheTags` included: on a `<Link>`, Inertia only reads `cacheTags` inside its prefetch call (`@inertiajs/react` Link), so a tag without prefetch does nothing. Write-side `invalidatedBy(...)` is unchanged, and `lib/cache-tags.ts` is untouched for P-02 (`prefetchProps` / `prefetchPropsForUrl` are now unused exports). Existing "prefetches …" tests now assert absence. A source-scan guard (`resources/js/lib/no-link-prefetch.test.ts`) covers components that have no test of their own, in place of ~18 padded render tests.

**P-02:** If hover prefetch is fully removed, `prefetchProps()` in `cache-tags.ts` becomes unused. Decide: keep `cacheTags` for write-invalidation only, or remove the system entirely. Update ADR-0009.

*P-02 delivery notes (tech-lead, 2026-10-13):* decision (main session, Q1): **remove the cache-tag system entirely**. Verified in `@inertiajs/core` 3.8: `invalidateCacheTags` only calls `router.flushByCacheTags` → `prefetchedRequests.removeByTags`, i.e. it empties tagged entries of the prefetch cache, which is always empty after P-01; it does not affect history (back-button) state. One frontend commit (NON-CRITICAL, 1 round): deleted `resources/js/lib/cache-tags.ts` + `cache-tags.test.ts` (23 cases); removed `invalidateCacheTags` (and the `invalidatedBy` import) from 11 production callers; stripped cache-tag plumbing from the test helper `resources/js/test/inertia.tsx` (mock `<Link>` `data-cache-tags`, mock `<Form>` `options` recording); dropped the `PREFETCH_HELPER` check from `no-link-prefetch.test.ts`; trimmed the option from 11 test files and deleted 10 invalidation-only `it`s; removed 11 now-dead `not.toHaveAttribute('data-cache-tags')` lines (the brief from the main session missed these). 34 files, +14 / −490 (production −134). Vitest 1161 → 1128. ADR-0009 amended (rule 3 retired), ARCHITECTURE.md and BACKLOG (S-19 bullet, F-47) refreshed.

---

### Skeleton / Progressive Loading

| ID | Task | Effort | Type |
|---|---|---|---|
| S-04 | Shared skeleton component library | Low | Frontend (foundation) |
| S-01 | Dashboard: defer secondary panels + skeleton cards | Medium | Backend + Frontend |
| S-02 | Product show: defer sales panels | Low–Medium | Backend + Frontend |
| S-03 | Material show: defer history panel | Low | Backend + Frontend |
| S-05 | Products index: loading indicator for the list | Low | Frontend (UX polish) |

**S-04 (do first):** Create reusable skeleton primitives — `SkeletonCard`, `SkeletonRow`, `SkeletonStat`. These become the `fallback` for every `<Deferred>` block. **Decision needed:** pulse (`animate-pulse`) or shimmer animation?

**S-01:** Move `needs_restock`, `recent_sales`, `alerts` to `Inertia::defer`. Keep `stats` and `top_products` eager (period-sensitive, needed for the KPI row). Wrap deferred sections in `<Deferred fallback={<SkeletonCard />}>`. Dashboard shell renders instantly.

**S-02:** Move `sales_last_30_days` and `recent_sales` to `Inertia::defer`. Keep `product` (BOM + costing) eager — it is the hero data. Wrap `ProductHero`'s sales stat and `RecentSalesCard` in `<Deferred>`.

**S-03:** Move `history` to `Inertia::defer`. The stock history can load after material KPIs and the "what you can make" section render. Add skeleton rows to the history list component.

**S-05:** The product list is a lazy closure (not deferred) — it's part of the initial render. Add a table skeleton that shows while Inertia is loading. No backend change needed.

---

### Best Practices & Query Audits

| ID | Task | Effort | Type |
|---|---|---|---|
| Q-01 | Add Pest query-budget test for the dashboard | Low | Backend (testing) |
| Q-02 | Verify material show eager-loads unit + category | Low | Backend (N+1 check) |

---

## 5. Open Questions

| # | Question | Needed before |
|---|---|---|
| Q1 | ✅ Resolved 2026-10-13: remove entirely. `invalidateCacheTags` only flushes the prefetch cache (not history), so it was a no-op after P-01. | P-02 |
| Q2 | ✅ Resolved: committed `docs/plans/<story>-plan.md` file. | W-01 |
| Q3 | Should `stats` (KPI cards) on the dashboard be deferred or kept eager? Deferring means the KPI row shows skeletons for ~200ms; keeping them eager means the page waits for them before painting anything. | S-01 |
| Q4 | Skeleton animation: pulse (`animate-pulse`, simpler) or shimmer (moving gradient, more familiar)? | S-04 |

---

## 6. Suggested Execution Order

1. **W-01** ✅ — Plan-first protocol is now in `CLAUDE.md` and `docs/TEAM.md`.
2. **Q-01, Q-02** — Audit queries. Establish baselines before adding defer.
3. **S-04** — Build skeleton components. Required by all S-0x tasks.
4. **P-01, P-02** — Remove hover prefetch. Clean slate before adding defer (no interaction between the two caching systems).
5. **S-01** — Dashboard (highest value, most queries).
6. **S-02, S-03** — Product show and Material show (can run in parallel — independent files).
7. **S-05** — Products index loading state (polish, last).
