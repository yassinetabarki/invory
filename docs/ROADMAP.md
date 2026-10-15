# Invory — product roadmap

> Owner: **product-owner** · Approved by: the human · Last updated: 2026-10-14
> Format: **Now / Next / Later**, organised by outcomes, not dates. Each phase is one slice of the story map with one goal, a MoSCoW-labelled
> set of stories and exit criteria. Detailed stories live in [BACKLOG.md](BACKLOG.md); progress lives in [STATUS.md](STATUS.md).

## Product goal
A small fashion workshop knows, at any moment, **what each product costs to make**, **how many it can make from the stock on the shelves**,
**which material will run out first**, and how sales are going. All of this comes without a spreadsheet and with exact numbers.

## Story map (user activities → what exists → where the rest lands)

| Activity | Built (Phases 1–3) | Phase 4 | Phase 5 | Phase 6 | Phase 7 + Vision |
|---|---|---|---|---|---|
| **Keep stock of materials** | List, filters, CRUD, photo, restock, history, status badges | Acceptance | History on demand (S-33) | | Suppliers and purchase orders, CSV import (S-11) |
| **Define products** | Product CRUD, composition editor, selling price, photo | Acceptance; overhead cost optional (S-43, done) | Instant edit page (S-35) | | Sizes and variants, sub-assemblies, unit conversions |
| **Know cost and capacity** | Unit cost, can produce, bottleneck, next limit, margin, "what you can make with it" | Acceptance, E2E (S-08) | Fast at 500 products (S-23, S-27) | | Live cost preview (S-09), production planner (S-10), cost history |
| **Sell** | Sales log, totals, filters, record/edit/delete | Acceptance | Instant delete feedback (S-36) | | Custom date range (S-37) |
| **Monitor** | Dashboard: KPIs, top 5, restock list, alerts | Panels offer "Try again" after an error (S-31) | Loading states (S-34) | | |
| **Run the business on it** | Login, 2FA, catalogue settings | Reliable dev setup (S-29), crash-free SKU saves (S-32), each workshop sees only its own data (S-44 done, S-45), workshop name (S-46) | | Production server, backups (S-26) | Several people in one workshop (invitations, roles and permissions) |

---

## NOW — Phase 4: MVP release readiness
**Goal:** the business owner has accepted every MVP screen, one automated test proves the golden path end to end, and each workshop sees
only its own data.
**Scope approved** by the human on 2026-10-14 (decision D-3), together with the cards of S-30, S-08, S-31 and S-32 ([stories](stories/README.md)).
**Added on 2026-10-14 (D-9):** each account gets its own workshop (S-44, S-45, S-46, cards approved), so the release can serve more than one
business. S-44 started at once, over the two-story limit, by the human's choice, and is done (2026-10-15). S-45 follows right after it: no second real workshop signs up until S-45 is done too.

| Priority | Story | Size |
|---|---|---|
| Must | S-29 Reliable dev environment (Docker = database + mail only; Sail removed). Open point: speed target on `php artisan serve`, see BACKLOG | M |
| Must | S-30 MVP acceptance walkthrough (`Ready`) | S |
| Must | S-08 End-to-end golden-path test + demo data on a fresh install (`Ready`) | M |
| Must (your request 2026-10-14, D-9) | S-44 Each workshop sees only its own materials, products and sales (✅ `Done` 2026-10-15: `672937f`, `a54856f`, `9a48cd3`; started over the two-story limit) | M |
| Must (your request 2026-10-14, D-9) | S-45 Each workshop has its own units and categories (`Ready`; right after S-44, ships with it) | S |
| Should (your choice 2026-10-14, D-8) | S-43 Save a product without filling in the overhead cost (✅ `Done` 2026-10-14: `6179d52`, `4644a08`, `9fc8e98`, `9df0ca0`, close-out `eccb09a`) | S |
| Should (D-9) | S-46 Name your workshop (`Ready`; after S-45) | S |
| Should | S-31 A section that fails to load offers "Try again" (`Ready`, on hold since 2026-10-14, D-8) | S |
| Should | S-32 Saving a duplicate SKU at the same moment shows a clear message, not a crash (`Ready`, on hold since 2026-10-14, D-8) | S |
| Could (your choice 2026-10-14) | S-42 Screen readers hear "112 units", not "112units" (`Ready`, after S-32; found in the S-08 review) | S |
| Could (your choice 2026-10-14, S-40 Q6) | S-40 A realistic catalogue of 100 products on a fresh install (`Ready`, after S-08; not an exit criterion) | M |
| Could (your choice 2026-10-14, S-41 Q4) | S-41 A real photo on every item of the demo catalogue, from Pexels (`Ready`, after S-40; needs your Pexels key; slips to Phase 5 first) | M |
| Tech allowance (~15 %) | F-22 flaky form test · F-12 frontend skill wording · F-45 stale "preview" test names (F-08, F-25, F-51 ride with S-29) | — |

Musts are about 70 % of the phase (above the 60 % guideline) because the phase is a release gate; S-31 and S-32 slip first. With S-44 and
S-45 (D-9) the Musts grow further; S-46 and the Coulds slip before them.

**Exit criteria:** S-30 accepted by the human · S-08 green in the tech-lead's gates and in CI on GitHub (D-1 done 2026-10-14; the human
reads the result) · S-18 page-speed target measured (p95 ≤ 150 ms; open point, see S-29 in BACKLOG) · S-44 (accepted 2026-10-15) and S-45 accepted by the human ·
every gate green.

## NEXT — Phase 5: Fast at real catalogue sizes
**Goal:** every page stays within its speed budget with 2 000 materials, 500 products and 50 000 sales.

| Priority | Story | Size |
|---|---|---|
| Must | S-27 Performance dataset and measured budgets (measure first; per workshop once S-44 lands) | S |
| Must | S-23 Pre-computed product figures (`product_metrics`), if S-27 shows the need | M |
| Should | S-33 Material history loads on demand, with "Load more" | S |
| Should | S-35 The product edit page opens instantly | S |
| Could | S-34 The products list shows it is loading while filters change | S |
| Could | S-36 Deleting a sale or archiving a catalogue item feels instant | S |
| Tech allowance | F-26 · F-38 · F-41 · F-42 · F-49 | — |

**Exit criteria:** S-27's report shows every page within budget, and the human has accepted S-33 and S-35.

## NEXT — Phase 6: Production go-live
**Goal:** Invory runs on a production server the business can rely on: backups, health check, fast static assets.

| Priority | Story | Size |
|---|---|---|
| Must | S-26 Production runtime (needs decision D-5: database host with PostgreSQL 13+ and `pg_trgm`) | M |
| Must | Confirm the provisional decisions Q4 (bigint keys), Q6 (currency and locale in config) and Q9 (no production runs), and confirm again that sign-up stays open to anyone once the server is public (D-9) | — |
| Tech allowance | F-43 · F-46 · F-52 · F-53 | — |

## LATER — Phase 7: Planning features (order to be confirmed with the human)
| Candidate | Value |
|---|---|
| S-10 Production planner: "plan N units" shows each material's requirement, the stock and the shortage to buy | High: turns capacity into a shopping list |
| S-09 Live cost preview while editing the composition | Medium |
| S-11 CSV import/export of materials | Medium: onboarding a real inventory |
| S-37 Custom date range for sales and the dashboard (F-11) | Low |
| S-38 UX polish bundle (F-14, F-16, F-17, F-18, F-21) | Low |
| S-39 "Manage units" link in the material form (F-31 q3: "not now") | Low |

## VISION — not scheduled
Suppliers and purchase orders · unit conversions in compositions (columns exist, Q2) · sizes and variants · sub-assemblies
(multi-level BOM) · **several people in one workshop**: inviting people, removing them, roles and permissions, and history that still
names a person after their account is deleted (e.g. "by a former member") (S-44 keeps the data with the workshop, so this adds people
without moving data) · cost history snapshots · production runs that consume stock (Q9) · REST API / mobile.
Design notes: [ARCHITECTURE.md §10](ARCHITECTURE.md#10-scalability-path-post-mvp-designed-for-but-not-built).

## Done (Phases 1–3)
Phase 1 foundation + materials · Phase 2 UI-first build of the approved mockup · Phase 3 real backend, reference data, performance quick wins,
dashboard endpoint, rendering and loading. Details: [STATUS.md §4](STATUS.md#4-delivery-history) and the
[backlog archive](archive/backlog-2026-10-14.md).
