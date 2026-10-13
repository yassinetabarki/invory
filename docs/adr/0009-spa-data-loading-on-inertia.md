# ADR-0009 — Fast SPA on Inertia 3: data-loading rules, no separate API

- **Status:** Proposed · 2026-10-08 (tech-lead; [phase-3 plan](../plans/phase-3-performance-spa-data.md) §B)
- **Confirms:** [ADR-0001](0001-stack.md) (Inertia instead of a REST API + client SPA)

## Context
The human asked for "an SPA, optimised, with fast data loading". The app is already an Inertia SPA: client-side visits, persistent layouts, every
link and form through Inertia. Measurements show the latency comes from the dev environment (ADR-0007), and that the props are eager and complete
on every visit (e.g. every material on the product form, a full inventory scan on every materials-list filter change).

## Decision (proposed)
No separate REST API or client-side SPA. The latency is server boot, which an API pays too, and Inertia 3 already provides prefetching, tagged
caches, partial and deferred loading, merge and infinite scroll, optimistic updates and plain HTTP calls (`useHttp`).

Data-loading rules for every page (enforced in review):
1. **Lazy by default:** every prop that costs a query or a computation is a closure, so partial reloads skip it.
2. **Partial reloads** (`only` / `except`) for filter, sort, pagination and period changes.
3. ~~**Prefetch** primary navigation and CTAs (`<Link prefetch cacheTags=…>`); every write invalidates the tags it affects (`invalidateCacheTags`).~~
   **Retired 2026-10-13** (P-01 + P-02, see the amendment below).
4. **Reference data once per session:** the shared once-prop `reference` (versioned key), never per-page option lists.
5. **Never send whole tables.** Pickers over growing tables use server-side search (`useHttp` → a session-authenticated JSON route in
   `routes/web.php` with a policy and a Resource). This is not a public API.
6. **Defer** secondary panels (`Inertia::defer` + `<Deferred>` / `WhenVisible`); use optional props for data shown on demand.
7. **Optimistic UI** only for simple writes whose result the client can predict (delete a sale, archive an item). Costs and capacities always come
   from the server.
8. **Budgets** (plan §B.4) and Pest query-budget tests per page.

## Consequences
- ✅ One deployable, one contract (`domain.ts`), session auth. Same Actions and Resources for any future `/api/v1`.
- ✅ Payloads stay flat as the catalogue grows. (The "instant prefetched navigation" benefit went with rule 3 on 2026-10-13.)
- ⚠️ Partial reloads and once-props need tests on both sides (`X-Inertia-Partial-Data`, `X-Inertia-Except-Once-Props`).
- ⚠️ Small internal JSON routes exist (typeahead, cost preview). They follow the same auth, policy and Resource rules as Inertia routes.

## Amendment — 2026-10-13: prefetch and cache tags retired (rule 3)
Rule 3 is withdrawn ([rendering & loading plan](../plans/rendering-loading-plan.md), P-01 and P-02).
- **P-01** removed hover prefetch from every `<Link>`. Each hover fired a full page request, which cost more than the 30 s cache saved.
  The source-scan guard `resources/js/lib/no-link-prefetch.test.ts` keeps the `prefetch` prop out of app code.
- **P-02** removed the cache-tag system: `resources/js/lib/cache-tags.ts` (`CACHE_TAGS`, `invalidatedBy`, `prefetchProps`,
  `prefetchPropsForUrl`, `cacheTagForUrl`) and the `invalidateCacheTags` option on every form and `router` write. In Inertia 3.8
  `invalidateCacheTags` only calls `router.flushByCacheTags`, which empties tagged entries of the **prefetch** cache
  (`@inertiajs/core`, `prefetchedRequests.removeByTags`). With no prefetching that cache is always empty, so the option did nothing.
  It does not touch history (back-button) state.
- Freshness after a write comes from the server response itself (redirect or partial reload). Perceived speed is the job of deferred props
  and skeletons (rule 6, plan S-0x).
- If prefetch comes back, it needs a new decision covering both sides again: tags on the prefetching links and `invalidateCacheTags` on every
  write that changes what those pages show.
