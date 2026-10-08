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
3. **Prefetch** primary navigation and CTAs (`<Link prefetch cacheTags=…>`); every write invalidates the tags it affects (`invalidateCacheTags`).
4. **Reference data once per session:** the shared once-prop `reference` (versioned key), never per-page option lists.
5. **Never send whole tables.** Pickers over growing tables use server-side search (`useHttp` → a session-authenticated JSON route in
   `routes/web.php` with a policy and a Resource). This is not a public API.
6. **Defer** secondary panels (`Inertia::defer` + `<Deferred>` / `WhenVisible`); use optional props for data shown on demand.
7. **Optimistic UI** only for simple writes whose result the client can predict (delete a sale, archive an item). Costs and capacities always come
   from the server.
8. **Budgets** (plan §B.4) and Pest query-budget tests per page.

## Consequences
- ✅ One deployable, one contract (`domain.ts`), session auth. Same Actions and Resources for any future `/api/v1`.
- ✅ Perceived navigation becomes instant for prefetched routes; payloads stay flat as the catalogue grows.
- ⚠️ Partial reloads and once-props need tests on both sides (`X-Inertia-Partial-Data`, `X-Inertia-Except-Once-Props`).
- ⚠️ Small internal JSON routes exist (typeahead, cost preview). They follow the same auth, policy and Resource rules as Inertia routes.
