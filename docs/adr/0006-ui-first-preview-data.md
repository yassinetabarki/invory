# ADR-0006 — UI-first preview: real pages, real routes, fixture data behind one server-side seam

- **Status:** Accepted · 2026-10-08
- **Plan:** [phase-2-ui-first-preview.md](../plans/phase-2-ui-first-preview.md) · **Removal:** backlog S-17 (release blocker)

## Context
On 2026-10-08 the human approved a clickable mockup (dashboard, sales, materials, products) and five new product decisions (sales log, photos,
"what you can make with it", restock + history, selling price). They want to click through the **real app pages now**, before the backend
for products, sales, photos and stock history exists, and later swap the data source for real queries **without changing the pages**.
Materials (S-01, S-02) and the costing engine (S-03) are already real.

Options considered:
1. **Fixtures in TypeScript** (pages import mock objects when props are missing). Rejected: pages would change at swap time, routes would not
   exist (no Wayfinder), and it invites maths in TS.
2. **Separate `/preview/...` routes.** Rejected: route names would change at swap time, so every page import and link would change.
3. **Final routes, final controller classes and final FormRequests, with the controller bodies reading one server-side fixture source.** Chosen.

## Decision
- **Contract is final.** Route names (`products.*`, `sales.*`, `materials.restocks.store`, `dashboard`), page components, page props and
  `resources/js/types/domain.ts` are the production contract. Backend stories later rewrite controller bodies only.
- **One seam: `App\Mock`** (`app/Mock/`). It holds the **raw inputs** that have no table yet: products and their compositions (materials referenced
  by SKU), selling prices, sales (dates relative to today), stock movement history, photo paths. Every file starts with a `MOCK` docblock naming
  the story that removes it. Static placeholder images live in `public/mock/photos/`.
- **Derived numbers are computed on the server by the production domain code**, never typed by hand and never in TS: `CostingCalculator`
  (unit cost, capacity, bottleneck, units possible), `CostingCalculator::capacityWithout()` (next limit), `MarginCalculator`, `SalesCalculator`
  (totals, averages, top products, percent of top). The pages receive final scale-4 strings, exactly like the real Resources will send.
  This deliberately goes one step beyond "precomputed constants": hand-typed derived values can disagree with each other; the engine can't.
- **Materials stay real.** `DemoCatalogSeeder` seeds the mockup's 12 materials (idempotent, keyed by SKU). Fixture products resolve their
  materials from the database by SKU on every request, so editing a material's stock moves preview capacities immediately. A fixture line whose
  SKU is missing from the database is skipped.
- **Preview writes validate for real and persist nothing.** Product create / update / delete, record / edit / delete sale, restock, and photo
  uploads go through their final FormRequests (validation errors render normally), then flash an `info` toast ("Preview: … not saved") and redirect.
  Real material create / update keep working; an attached photo is validated and ignored, and the toast says so.
- **Authorization.** Preview-only controllers authorize the `access-preview` ability (any verified user; **denied in production**). Real
  policies (`ProductPolicy`, `SalePolicy`) arrive with their backend stories. Material routes keep `MaterialPolicy`.
- **Boundaries enforced by an arch test:** `App\Mock` may only be used by `App\Http\Controllers`, `App\Http\Requests` (temporary rules such as
  "active product ids") and `Database\Seeders`. Domain, Actions, Models and Resources never depend on it.

## Removal path
Each backend story replaces its controller bodies with Eloquent queries + Resources and deletes the fixture parts it supersedes:
S-04 (products), S-14-BE (sales), S-16-BE (stock movements, restock), S-15-BE (photo storage), S-06-BE (dashboard), S-07-BE (material usage).
S-17 deletes `app/Mock`, `public/mock/`, the `access-preview` ability and the preview tests, and checks that nothing references `App\Mock`.
**S-17 blocks the first production release.**

## Consequences
- ✅ The human clicks through every approved screen now, with numbers that agree across pages (worked example: Denim Jacket TND 35.754 / 112 / button).
- ✅ FormRequests, enums, domain maths, the TS contract and every page are production code from day one. Only `App\Mock` and controller bodies are throwaway.
- ✅ Swapping to real data changes no page, no route name and no prop.
- ⚠️ Until S-04, deleting a material that a preview product uses is not blocked server-side (the UI disables the button using `used_in_count`).
- ⚠️ Preview figures react to real material edits but not to preview writes (nothing is saved), which the toasts state explicitly.
- ⚠️ A temporary `access-preview` ability bypasses model policies on preview routes; it is removed with S-17.
