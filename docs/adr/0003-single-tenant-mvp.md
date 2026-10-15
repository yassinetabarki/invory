# ADR-0003 — Single company (single tenant) for the MVP

- **Status:** Superseded by [ADR-0013](0013-one-workshop-per-account.md) · 2026-10-14 (D-9). Accepted 2026-10-07 (decided by the product owner)

## Context
The starter kit can generate team (multi-company) support. That scopes every route to `/{team}/…` and needs a `team_id` plus a global
scope on every model. The MVP serves one company.

## Decision
Scaffold **without teams**. All authenticated users of the installation share the same materials and products.
Authorization still goes through Policies on every action (currently: any authenticated user).

## Consequences
- ✅ Simpler routes, queries, tests and UI. Faster MVP.
- ⚠️ Moving to multi-company later needs a migration adding `team_id` to `materials`, `products` and `product_components`, a global scope
  or trait, and team-aware routes. Writes going through Actions and reads through Policies keep that change localized.
- Revisit this decision before onboarding a second company.
