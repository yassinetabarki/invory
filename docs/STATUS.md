# Invory — project status report

> Owner: **product-owner** (updated at every phase boundary and after every accepted story) · Report date: **2026-10-15**
> Sources: every plan in [docs/plans/](plans/README.md), the [backlog archive](archive/backlog-2026-10-14.md) and `git log` on `main`.
> Gate results: run by the main session on 2026-10-14 for this first report. Later updates quote the tech-lead's latest delivery report
> (the product-owner never runs gates).
> Decision IDs: **D-x** are this report's open decisions. Recorded decisions keep their source, e.g. "Phase 3 plan Q7" or "rendering plan Q3".
> Live documents: [ROADMAP.md](ROADMAP.md) (phases) · [BACKLOG.md](BACKLOG.md) (ordered stories) · [TECH-DEBT.md](TECH-DEBT.md) (technical register)

---

## 1. Headline

| | Status | In one line |
|---|---|---|
| **Overall** | 🟡 **AMBER** | The MVP is built and every automated check passes on this computer. The checks now also run on GitHub, but nobody has seen a result yet. You haven't signed the MVP off. Since 2026-10-15 Phase 5 (customer orders) is under way: its first story, S-48, is done. The rest of Phase 4 is on hold (D-12). |
| Scope (MVP features) | 🟡 AMBER | Every feature in the MVP goal and the 2026-10-08 scope additions works on real data. The preview layer is gone. Since 2026-10-15 every account sees only its own workshop's materials, products and sales (S-44, D-9), and records its clients' orders (S-48). Units and categories are still shared until S-45. |
| Quality (automated) | 🟢 GREEN | All automated checks pass on this computer (tech-lead's S-48 delivery report, 2026-10-15): 2 216 backend tests, the same 2 216 on PostgreSQL, 1 155 screen tests, 2 browser tests, code style, static analysis, type check and production build. |
| Product acceptance | 🔴 RED | The MVP as a whole isn't signed off: the Phase 2 click-through has been pending since 2026-10-08, and the MVP walkthrough (S-30) is on hold since 2026-10-15 (D-12). S-43, S-44 and S-48 were accepted one by one. |
| Dev environment | 🟡 AMBER | The app runs on `php artisan serve` (http://localhost:8000); Docker runs PostgreSQL and the test mailbox (http://localhost:8026). Sail is gone (S-29, done 2026-10-15). Not yet seen: a password-reset email in the test mailbox, and the page-speed target (S-18), which may not hold on the built-in server (known gaps K-3, K-4). |
| Release readiness | 🔴 RED | Missing: the MVP sign-off (S-30), a live server with a chosen database, and separate units and categories for each workshop (S-45). Both S-30 and S-45 are on hold (D-12) and must be done before go-live. S-08 and S-29 were closed as they stood (D-13), leaving four known gaps for go-live (§6.4). |
| Process | 🟡 AMBER | Roles are split: product-owner (what and why) and tech-lead (how). Phase 5 started before Phase 4 closed, by your choice (D-12). S-08 and S-29 were closed without their last checks, by your choice (D-13). |

**Current phase:** **Phase 5: Customer orders** (started 2026-10-15, D-12). **S-48 is done** (accepted 2026-10-15 after your
click-through; `e3b7ae7`, `5af8ed1` on the build branch, on `main` once it merges). Next: D-11's cutting rules, then the cards S-49, S-53
and S-50. Phase 5 tech allowance: F-41 only (moved from Phase 6, 2026-10-15).
**Phase 4: MVP release readiness** is on hold for later; S-08 and S-29 are done (closed as they stood, D-13; see [ROADMAP.md](ROADMAP.md)).

---

## 2. Decisions needed from you

**Needed from the human now**
| # | Decision | Recommendation |
|---|---|---|
| D-4 | Two early choices are still provisional (Phase 3 plan): **Q4** records are numbered with simple counters (already used everywhere) · **Q6** currency and language are set at installation, not in a settings screen. Keep both? (Q5 is closed: S-17 removed the preview layer. Q9 was replaced by D-10 on 2026-10-15) | Keep both; confirm before Phase 7 (production go-live, was Phase 6) |
| D-5 | Production database host (needs PostgreSQL 13+ and `pg_trgm`) | Before S-26; not urgent |
| D-6 | Delete the 5 merged branches and the stale worktree `.claude/worktrees/fix-hover-prefetch-bug`? | Yes (nothing unmerged) |
| D-11 | **How cutting changes stock** (S-53, 2026-10-15): what happens when stock is short, whether moving back returns materials, whether deleting an order returns them, the date of the history entries, low-stock alerts, and whether S-49 and S-53 ship together. Then approve the cards S-49, S-53 and S-50. (Decided 2026-10-15 with S-48: open orders never set stock aside) | Refuse to cut more than stock allows; moving back returns exactly what was taken; deleting returns nothing; history dated the day of the move; no alerts yet; ship S-49 and S-53 together |

**Decided on 2026-10-15 (S-48 acceptance):** you accepted S-48 after your click-through · a line whose product no longer exists
reads "The selected product is invalid." (close to the Sales form) · that message and a " (not active)" mark in the Edit order picker
were fixed before the commit · the product-owner kept the extra sentence under "No orders match your search" and "80 units" on the phone
cards.

**Decided on 2026-10-15 (S-48 planning):** S-48's Technical work approved (`Planned`) · a product added twice to an order is flagged
("… is already on this order.") only after Save, not while you pick · Phase 5 tech allowance is F-41 only, moved from Phase 6.

**Decided on 2026-10-15: D-12 Phase 5 starts now** (your words: "phase 4 will [be postponed] now for [a] late[r] time, S-08 and S-29
close them"; "we will start with S-48"):
- Phase 5 "Customer orders" starts now, with S-48. Its card is `Ready` (approved as revised): only active products can go on an order,
  and recording an order never sets stock aside. The rest of D-11 waits for S-49 and S-53.
- The rest of Phase 4 is on hold for later: S-45, S-30 and S-46 (on hold since 2026-10-15), S-31 and S-32 (on hold since 2026-10-14).
  Every card stays approved. No second real workshop signs up until S-45 is done (D-9), and Phase 4 closes before go-live (Phase 7).
- S-40, S-41 and S-42 are postponed and kept for later (`Ready`, on hold since 2026-10-15).
- **D-13 (same day): S-08 and S-29 closed as they stood** ("close them as they are"). Both are **Done** on the commits already on
  `main` (§4). Their unfinished checks are known gaps to close before go-live (§6.4).

**Decided on 2026-10-15: D-10 Customer orders** (you asked for it the same day: "design the order management UI"):
- A new **Phase 5 "Customer orders"** (it started early, the same day: D-12). The later phases move up one number (speed is Phase 6,
  go-live Phase 7, planning Phase 8).
- An order holds **several products**, each with its own quantity and its own stages: To start, In cutting, Ready to ship, Shipped.
- **"Start cutting" takes the materials out of stock.** This **replaces Q9** ("making products never reduces material stock"). It is its
  own story, S-53, after S-49. The tech-lead writes the new rule into the business rules, with a decision record, before any code changes.
- Shipping doesn't record a sale for now. A later story, S-54 (Could), may add "Also record a sale" to "Ship".
- The client is a name typed on each order, and the delivery date goes in the note.
- Cards S-49, S-53 and S-50 are `Draft`, rewritten with these answers; S-48 is `Ready` (D-12). The rules for cutting are still open
  (D-11).

**Decided on 2026-10-14:** **D-1** the repository is on GitHub (private, `yassinetabarki/invory`); `main` was pushed at `3a0785f`, and the
automatic checks run on every push to `main` and `phase/**` (only you can see their results, in the repository's Actions tab) ·
**D-2** go ahead with S-29 (dev environment) · **D-3** Phase 4 scope approved, together with the cards of S-30, S-08, S-31 and S-32,
which are `Ready` with every recommended answer accepted ([stories](stories/README.md)) · **D-7** keep the dashboard's KPI cards and top 5 loading just after
the page frame (supersedes rendering plan Q3) · remove the local "frontend-design" skill, which competed with the product-owner on UX ·
**Low stock:** no new story. The existing "Reorder at" level, the stock badges, the "Needs restock" filter and the dashboard panel are
accepted through the S-30 walkthrough. No email alerts for now (stock only falls when you edit it, Q8/Q9). A material with
no "Reorder at" is never flagged as low. ·
**Large demo catalogue** (you asked for it on 2026-10-14):
- [S-40](stories/S-40-realistic-catalogue-100-products.md) is `Ready`: 100 products in all (the 7 demo products plus 93 new ones) and about 110 more materials, on a new, throwaway database only (never `laravel`).
- S-40 is an optional step after the demo workshop. It adds no sales, gives the same data every run, and adds no brand or sub-category. It is a Phase 4 Could, planned after S-08.
- Its photos are split into [S-41](stories/S-41-real-photos-for-the-catalogue.md) (`Ready`, approved 2026-10-14): real photos from Pexels only, with credits in a licence record and the setup guide, none on screens.
- S-41 is a Phase 4 Could, right after S-40, and needs your free Pexels key first.

**D-8 Overhead cost optional** (you asked for it on 2026-10-14):
- [S-43](stories/S-43-overhead-cost-optional.md): card approved 2026-10-14 with every recommended answer. Delivered, accepted by you
  and **Done** the same day (on `main`, see §4).
- New business rule: overhead is optional. A blank overhead is saved as 0, and the unit-cost formula doesn't change. The tech-lead adds the
  rule to the costing rules before any code changes.
- It applies to "Add product" and "Edit <product>". Existing products keep their overhead, and labor cost stays required.
- S-43 joins Phase 4 as a Should and goes next. S-31 and S-32 are on hold, not dropped ("stop S-31 and S-32 for now"), and S-42 waits
  behind S-32.
- You chose to start S-43 now, even though S-29 (in progress) and S-08 (in acceptance) already fill the two-story work-in-progress limit.

**D-9 One workshop per account** (you asked for it on 2026-10-14: "each user must only see HIS products and materials"):
- Cards approved: [S-44](stories/S-44-each-workshop-sees-only-its-own-data.md) (each workshop sees only its own materials, products and
  sales, Must), [S-45](stories/S-45-each-workshop-has-its-own-units-and-categories.md) (its own units and categories, Must) and
  [S-46](stories/S-46-name-your-workshop.md) (name your workshop, Should). All three are `Ready`.
- The screen calls the business account a **workshop**. Every account belongs to one workshop, and the data belongs to the workshop, not
  the person, so several people can share a workshop later. Inviting people and roles are not scheduled.
- Business rules R1–R6 approved. In short: everything belongs to one workshop; you see and change only yours; SKUs and names are unique
  per workshop; compositions and sales stay inside one workshop; every figure is per workshop; signing up creates a new workshop. The
  tech-lead writes them into the costing rules and a new decision record before any code changes. They replace ADR-0003 ("single company")
  and change Q3 to "any member of a workshop edits that workshop's catalogue".
- Today's data: every existing account joins one workshop that holds everything recorded so far.
- Sign-up stays open to anyone, and each sign-up gets a new, empty workshop. Confirm this again before the production server is public (S-26).
- Each workshop gets its own copy of the standard units and categories (S-45).
- Another workshop's address shows the plain "404 Not Found" page, as for a deleted product.
- Deleting an account deletes only the person; the workshop's data stays (not my recommendation). The proposed story S-47 was dropped, and
  its ID won't be reused. The "Delete account" wording changes so it no longer promises to delete "all of its resources and data" (S-44 AC-6).
- Photos: an address that can't be guessed and never appears on another workshop's screens is enough for now.
- **Over the limit:** S-44 started at once, even though S-29 (in progress) and S-08 (in acceptance) already filled the two-story
  work-in-progress limit (not my recommendation). S-45 comes right after S-44 and they ship together: no second real workshop signs up
  until both are done. **S-44 is Done** (accepted 2026-10-15 after your click-through; on `main`, see §4).

**Recorded and still valid:** Phase 3 plan Q1 native serving (ADR-0007) · Q2 user-editable units, conversions later · Q3 any member of a
workshop edits that workshop's catalogue (was "any signed-in user", changed by D-9) · Q7 unit locked while used · Q8 sales are stock-neutral
and hard-deleted · F-20 restock updates the unit cost by default ·
F-37 deleted demo products stay deleted · F-39 no photo thumbnails · F-40 corrections have no reason field · F-31 q3 "Manage units" link: not now ·
dev-Docker plan Q1–Q4 (Docker Mailpit on 1026/8026, remove Sail, the main session updates agent docs, skip the dev DB demo seed) · rendering plan
Q1 (prefetch removed), Q4 (pulse placeholders).

---

## 3. What we have (user-visible capabilities, all on real data)

| Area | What a signed-in user can do today |
|---|---|
| **Materials** | List, search, filter (category, stock status) and sort the inventory; see stock status badges and the inventory summary (count, low, out, stock value). Create, edit and delete materials (deleting is blocked while a product uses it, and the message names the products). Upload one photo per material. |
| **Restock and history** | Restock from the list or the detail page (the unit cost becomes the latest price paid). See the history of restocks, corrections and price changes. The latest movements show on the list, loaded after the page appears. |
| **"What you can make with it"** | On a material's page: for each product that uses it, how much one unit needs, how many units this stock covers, how many can be made now, what limits it, and the next limit. |
| **Products and composition** | List products with photo, unit cost, price and margin, "can produce", bottleneck, status and units sold (30 days), with a "can't produce" filter. Create and edit a product with its composition (bill of materials): material search, quantity, waste %, notes, reordering, no duplicates. Selling price and photo. Overhead cost is optional: left empty, it counts as 0 (S-43). |
| **Costing** | For every product: unit cost, how many units can be produced from stock, the bottleneck material, the next limit, and the margin. All amounts are exact decimals (never floating point), computed on the server. Worked example: Denim Jacket costs **35.754 TND**, **112** can be made, bottleneck "Metal shank button 17mm". |
| **Sales** | Record, edit and delete sales (product, quantity, unit price pre-filled from the selling price, date, note). Sales never change stock. Totals and filters: search, period presets, product. |
| **Orders** | Record a client's order of one or more products (client, order date, note, a quantity per product), then find it by client, product, SKU or note, edit it and delete it. 25 orders a page, newest first; cards at phone width. Orders never change stock, sales or any figure. A product that is archived or deleted later stays on the orders that have it (S-48). |
| **Dashboard** | Period selector, KPI cards, top 5 products by units or revenue, materials that need a restock, recent sales, and "can't produce" alerts. The page appears at once and its panels fill in. |
| **Settings: Catalogue** | Manage units, material categories and product categories without a deploy: create, rename, archive, restore, delete when unused. Names are unique regardless of case. A material's unit is locked while a product uses it. |
| **Account** | Starter-kit authentication: register, login, email verification, password reset, two-factor, passkeys, profile, password, light/dark appearance. Every account belongs to one workshop and sees only that workshop's materials, products, sales and figures. Signing up creates a new, empty workshop, another workshop's address shows "404 Not Found", and "Delete account" keeps the workshop's data (S-44). Units and categories stay shared until S-45. |
| **Speed** | Every page runs at most 10 database queries (guarded by tests). List filters reload only what changed. Heavy panels load after the page appears, behind loading placeholders. Inventory totals use a single exact SQL aggregate on PostgreSQL. The composition picker searches on the server. |

**Under the hood:** pure costing domain with 100 % branch coverage, 46 application routes behind policies, 15 migrations, architecture tests,
a PostgreSQL parity suite (last green: 2 074 passed on 2026-10-13, during RL-01), and 11 ADRs.

---

## 4. Delivery history

| Phase | Stories | Committed | Result |
|---|---|---|---|
| **1 — Foundation + materials** | S-00, S-01, S-02, S-03 | 2026-10-08 (`c53ec14` … `d877a4a`) | ✅ Done |
| **2 — UI-first preview** (the approved mockup on preview data) | S-12, S-13, S-05, S-06-FE, S-07-FE, S-14-FE, S-15-FE, S-16-FE | 2026-10-08 (`5c61a17` S-13b maths, `becfcae`) | ✅ Built. ⚠️ **Click-through never recorded** (now part of S-30) |
| **3P — Performance quick wins** | S-18 (repo side), S-19, S-20 | 2026-10-09 (`d126cb3`, `c7ed37c`, `c7a9f64`) | ✅ Done. ⚠️ S-18 speed target **not measured** (machine steps pending) |
| **3R — Reference data** | S-21, S-22, S-22b (F-27, F-30, F-31, F-32), F-33 | 2026-10-09 (`25bd2a1` … `30e48d6`) | ✅ Done |
| **3 — Real backend** | S-04 (incl. F-29), S-14-BE (incl. F-37), S-16-BE, S-25, S-15-BE (incl. F-36), S-06-BE, S-07-BE, S-17 | 2026-10-09/13 (`4e5b2ef`, `11299a9`, `2240e7d` wip start of S-16-BE + S-25, `12c2a16`, `4a98c74`; `57e215b` photo storage fix) | ✅ Done. Preview layer removed |
| **Perf — dashboard endpoint** | S-28 (+ the S-28-0 baseline fix) | 2026-10-13 (`9ea79f7`, `784ae8c`, `b2ea946`) | ✅ Done |
| **Rendering and loading** | W-01, P-01, P-02, RL-04, RL-01, RL-02 (+ fix) | 2026-10-13 (`03cc4da` … `c809962`) | ✅ Done. RL-03 and RL-05 still open |
| **4 — MVP release readiness** | S-43 overhead cost optional (D-8) | 2026-10-14 (`6179d52` card, `4644a08` costing rule, `9fc8e98` saving, `9df0ca0` form, `eccb09a` close-out; on `main`, not pushed) | ✅ Done. Accepted by you on the delivery report (tech-lead APPROVED; every gate green, including PostgreSQL parity and the golden path). Click-through on the card optional |
| **4 — MVP release readiness** | S-29 reliable dev environment | 2026-10-14 (`d351514`, `7b0fb05`, `c70109f`, `a68043e`; on `main`) | ✅ Done 2026-10-15, closed as it stood (D-13). Docker runs only PostgreSQL and the test mailbox; Sail removed; instructions updated. Open: gaps K-3, K-4 |
| **4 — MVP release readiness** | S-08 golden-path test + demo data on a fresh install | 2026-10-14 (`723f57a`, `932f01a`, `1b40f75`, `af3d6de`, `5241147`; docs `6e776f0`, `6cbfb01`, `a368d8c`; on `main`) | ✅ Done 2026-10-15, closed as it stood (D-13). AC-1–4, 7, 8 accepted on test evidence. Open: gaps K-1, K-2 |
| **4 — MVP release readiness** | S-44 each workshop sees only its own data (D-9) | 2026-10-15 (`672937f` cards, ADR-0013, rules and project instructions; `a54856f` workshops, S-44-a–d; `9a48cd3` delete-account wording, S-44-e; on `main`, not pushed) | ✅ Done. Tech-lead APPROVED in round 1 (2 132 backend tests, PostgreSQL parity 2 132, 1 137 screen tests, check, types, build, golden path). You backed up and migrated `laravel` yourself; click-through passed 2026-10-15 |
| **5 — Customer orders** | S-48 record customer orders with one or more products (D-10, D-12) | 2026-10-15 (`57ab45b` card, `5ff800f` Technical work, on `main`; `e3b7ae7` saving and rules, S-48-a–c; `5af8ed1` screens, S-48-d–e; on the build branch, on `main` once it merges) | ✅ Done. Tech-lead APPROVED (every gate green, including PostgreSQL parity 2 216 and 2 browser tests). One fix round after the product-owner review: the "product is invalid" message and the " (not active)" mark. Your click-through passed 2026-10-15. New tech debt F-60, F-61 |

All phase branches (`phase/1-materials`, `phase/2-ui-first-preview`, `phase/3-performance-spa-data`, `fix/rl-02-product-show-loading`,
`worktree-fix-hover-prefetch-bug`) are fully merged into `main`. They and the worktree under `.claude/worktrees/` can be deleted (human decision D-6).

---

## 5. Review of every plan

| Plan | Status written in the file | Real status (verified against `git log` and the code) |
|---|---|---|
| [phase-1-foundation-materials](plans/phase-1-foundation-materials.md) | "awaiting human commit approval and sign-off" | **Done**, committed 2026-10-08 |
| [phase-2-ui-first-preview](plans/phase-2-ui-first-preview.md) | "awaiting commit approval and click-through" | **Done** (committed). The preview data was retired by S-17. The click-through moves to S-30 |
| [phase-3-performance-spa-data](plans/phase-3-performance-spa-data.md) | "3P delivered; the rest Proposed" | **Mostly done**: S-18 (repo side), S-19, S-20, S-21, S-22, S-04 committed. **Open:** S-23, S-24 (remainder), S-26, S-27, S-18 measurement |
| [phase-3-real-backend](plans/phase-3-real-backend.md) | "awaiting commit + PostgreSQL parity" | **Done**: committed `12c2a16`; first parity run after it was green on 2026-10-13 (2 073, during S-28) |
| [perf-dashboard-endpoint](plans/perf-dashboard-endpoint.md) (S-28) | "approved, awaiting commit" | **Done**, committed |
| [rendering-loading-plan](plans/rendering-loading-plan.md) | "W-01 done, remainder pending" | **Mostly done**: W-01, P-01, P-02, RL-04, RL-01, RL-02 committed; Q-01 and Q-02 are covered by `QueryBudgetTest`. **Open:** RL-03 (→ S-33), RL-05 (→ S-34) |
| [s04-s01-skeleton-dashboard-plan](plans/s04-s01-skeleton-dashboard-plan.md) (RL-04, RL-01) | "delivered, awaiting commit" | **Done**, committed |
| [s02-product-show-defer-plan](plans/s02-product-show-defer-plan.md) (RL-02) | "delivered, awaiting commit" | **Done**, committed. Partly superseded by the fix plan below |
| [fix-rl-02-product-defer-all](plans/fix-rl-02-product-defer-all.md) | "in execution" | **Done**, committed `a9208d3` + `c809962`. ⚠️ Ran without a human review between plan and execution; the commit also deferred the dashboard KPIs against rendering plan Q3, which the human then confirmed (**D-7**, 2026-10-14) |
| [dev-docker-db-mail-only-plan](plans/dev-docker-db-mail-only-plan.md) | "Proposed, awaiting Q1–Q4" | **Done**, committed (`d351514` … `a68043e`); story **S-29** closed as it stood on 2026-10-15 (D-13), with gaps K-3 and K-4. The plan file's own status line is the tech-lead's to update |

The plan headers were corrected on 2026-10-14, and [plans/README.md](plans/README.md) now indexes every plan with its real status.

---

## 6. What is missing or incomplete

### 6.1 Blocks the MVP release (Phase 4, on hold since 2026-10-15; must be done before go-live)
| # | Gap | Why it matters | Backlog |
|---|---|---|---|
| G-1 | **No product acceptance.** No screen was ever signed off by the human | We don't know that the product does what the business wants; "done" so far means "tests pass" | S-30 |
| G-3 | **A panel that fails to load spins forever.** Deferred panels have no error state, and the dashboard and product page are now fully deferred | One failed request leaves the user stuck on loading placeholders, with no retry | S-31 (F-48) |
| G-5 | **Two people saving the same SKU at the same moment get a server error page** instead of a validation message | Rare, but it's a crash | S-32 (F-35) |
| G-6 | **Units and categories are still shared by every workshop.** S-44 (done 2026-10-15) separated materials, products and sales | One workshop renaming "Fabric" would change another workshop's screens, so no second real workshop signs up before S-45 (D-9) | S-45 |

### 6.2 Not started, needed for real-world use (Phases 5–6)
- **Speed at real catalogue sizes:** the dashboard and the "can't produce" filter cost every product in PHP, and sales totals walk every row
  of the period. Fine today, never measured at 500 products and 50 000 sales (S-27 measures, S-23 fixes).
- **On-demand material history** with "Load more" (S-33), a loading state on the products list (S-34), an instant product edit page (S-35),
  instant delete and archive feedback (S-36).
- **Production:** no runtime image, no production database chosen (it needs PostgreSQL 13+ with `pg_trgm`), no session or cache store
  decision, no health check, no backups (S-26).
- **CI results not seen yet:** since 2026-10-14 (D-1) the automatic checks run on GitHub on every push to `main` and `phase/**`, but nobody
  has looked at a result yet (gap K-1 below).

### 6.3 Open human steps
- Dev database: demo seeding was **skipped by decision** (2026-10-14). Don't re-offer it.

### 6.4 Known gaps for go-live (left open when S-08 and S-29 were closed as they stood, D-13, 2026-10-15)
| #   | Gap                                                        | From       |
| --- | ---------------------------------------------------------- | ---------- |
| K-1 | Nobody has seen the `golden-path` check green on GitHub    | S-08 AC-5  |
| K-2 | The fresh-install demo click-through wasn't walked         | S-08 AC-6  |
| K-3 | No password-reset email seen in the test mailbox           | S-29       |
| K-4 | The page-speed target (p95 ≤ 150 ms) was never measured    | S-29, S-18 |

- K-2: the automated test `FreshInstallDemoTest` already checks every figure of the demo workshop; only the look on screen is unchecked.
- K-4: the app runs on `php artisan serve`, which handles one request at a time on Windows, so the panels that load after the page queue
  behind each other and the target may not hold. Phase 6 (S-27) measures speed on real catalogue sizes.
- Each gap is closed, or accepted by you, before the production server goes live (Phase 7).

---

## 7. Risks and issues (RAID)

| # | Type | Item | Severity | Mitigation / owner |
|---|---|---|---|---|
| R-1 | Risk | CI runs on every push since 2026-10-14 (D-1), but no result has been seen yet: the repository is private and only the human can open the Actions tab. It may already be red (the dev-Docker plan suspects the photo-storage check fails in CI) | High | Human: check the Actions tab after the next push and report the result; tech-lead: keep CI green (S-29 includes the CI photo-storage fix) |
| R-2 | Risk (closed 2026-10-15) | No E2E test across pages | — | Closed: S-08's golden-path test is on `main`; its GitHub result is gap K-1 |
| R-3 | Issue | Deferred panels can hang without an error state | Medium | S-31 |
| R-4 | Risk | S-29 was closed without two checks: the password-reset mail to Docker's test mailbox is not confirmed, and the speed target may not hold on `php artisan serve` | Medium | Known gaps K-3 and K-4, closed or accepted before go-live; tech-lead's speed report still welcome |
| R-5 | Risk | Unmeasured performance at real catalogue sizes (whole-catalogue costing in PHP) | Medium | S-27, then S-23 |
| R-6 | Risk | Stories counted as "done" without business acceptance | Medium | S-30; from now on the product-owner acceptance step (TEAM.md) |
| R-7 | Issue | Stale statuses and colliding IDs (RL stories reused S-01…S-05; the dev-Docker plan reused F-41…F-43) | Low | Fixed 2026-10-14; IDs are now assigned only by their owners (product-owner: S-, tech-lead: F-) |
| R-8 | Risk | Plans executed without a human review (fix-rl-02) | Low | The workflow now requires an approved story card and an approved technical plan |
| R-9 | Issue | Flaky Vitest test under load (F-22) | Low | Gates run sequentially; fix in TECH-DEBT |
| R-10 | Dependency | S-41 (real photos) can't start until you have created a free Pexels account and key and put the key in your local settings | Low | Human, before S-41 starts |
| R-11 | Risk | About 220 Pexels photos stored in the repository are fine while it stays private. Publishing the repository would redistribute them in bulk | Low | Product-owner raises the licence conditions with you before any change to the repository's visibility (S-41 AC-6) |
| R-12 | Risk (closed 2026-10-15) | S-44 ran over the two-story limit beside S-29 and S-08 (D-9). It landed with the demo data and the golden-path test unchanged (S-44 AC-7) | — | Closed: S-44 accepted 2026-10-15 |
| R-13 | Risk | When someone deletes the only account of a workshop, that workshop's materials, products and sales stay in the database with nobody able to reach them (D-9). That may matter for privacy once other businesses use a public server | Low now, higher at go-live | The "Delete account" wording says the data stays (S-44 AC-6). Product-owner raises it with you again before S-26 |
| R-14 | Dependency (closed 2026-10-15) | The project instructions, ADR-0003 and ARCHITECTURE.md said "single company, no teams" | — | Closed: updated in `672937f` (ADR-0003 superseded by ADR-0013) |
| R-16 | Risk | With S-45 on hold (D-12), units and categories stay shared by every workshop. If a second real workshop signs up, its renames would change your screens | Medium | No second real workshop signs up until S-45 is done (D-9); S-45 is done before go-live. Product-owner raises it again before S-26 |
| R-17 | Risk | Orders are built on screens nobody has signed off: the MVP walkthrough (S-30) is on hold (D-12) | Medium | Each Phase 5 story gets its own click-through (S-48's passed 2026-10-15); S-30 is walked before go-live |
| R-15 | Risk | With S-53, Invory changes material stock by itself for the first time (D-10). A wrong amount would quietly spoil stock, "Can produce" and the inventory value | Medium | S-53 shows the materials before you confirm, writes a history entry per material, and "Move back" returns what was taken; the tech-lead encodes the rule before code and tests it against the worked example (30 Denim Jackets → Inventory value TND 9,936.680) |

---

## 8. Housekeeping done on 2026-10-14
- The backlog was **cleaned**: done stories and closed follow-ups moved to [archive/backlog-2026-10-14.md](archive/backlog-2026-10-14.md) (verbatim).
  The live [BACKLOG.md](BACKLOG.md) holds only open work, ordered by priority.
- Technical follow-ups moved to the tech-lead's register [TECH-DEBT.md](TECH-DEBT.md). UX and behaviour follow-ups (F-11, F-14, F-16, F-17,
  F-18, F-21, F-31 q3, F-48, F-50) became product stories.
- The dev-Docker plan's follow-ups F-41…F-43 collided with existing IDs; they are now **F-51…F-53**.
- Every follow-up from the old backlog is accounted for: open ones are in BACKLOG or TECH-DEBT, closed ones are listed in
  [TECH-DEBT.md](TECH-DEBT.md#closed-during-the-2026-10-14-clean-up).
- Plan headers corrected; [plans/README.md](plans/README.md) added.
- New **product-owner** agent; the tech-lead now owns technical planning and code review only (see [TEAM.md](TEAM.md)).

## 9. Next steps (recommended order)
**From 2026-10-15 (D-12): Phase 5 first.**
1. Main session: commit these closing documents on the build branch, then merge it into `main` (S-48 done 2026-10-15). Push when you choose;
   CI runs on the push, and only you can see the result.
2. Human and product-owner: D-11's remaining cutting rules, then the cards S-49, S-53 and S-50 (from a session on the main checkout).
3. Tech-lead: F-41 (Phase 5's tech allowance) before S-53; F-60 and F-61 (new from S-48) are offered for a tech allowance.
4. Tech-lead: mark the S-29 plan done (D-13) and close what it carried (S-18 moves to the known gaps, K-4).
5. The rest of Phase 4 waits; S-45, S-30 and the known gaps K-1 to K-4 are dealt with before go-live (Phase 7).

**History of the Phase 4 steps (before D-12):**
1. Human: decisions D-4 … D-6, and a first look at the CI result in GitHub's Actions tab.
2. Tech-lead (in progress): adjust S-29's plan to `php artisan serve` (no Apache, no Laragon), report the speed consequence, then finish S-29.
3. Tech-lead: plan S-08, S-31 and S-32 next, from their `Ready` cards (done by the product-owner on 2026-10-14).
   **Update 2026-10-14:** S-08 is delivered (its work is now on `main`: `723f57a` … `5241147`). The product-owner review accepted every
   criterion from the evidence; two things are still yours:
   - AC-5: push, then check that the `golden-path` job is green on GitHub (this also re-checks PostgreSQL, which wasn't re-run locally);
   - AC-6: the fresh-install click-through on a throwaway database.
   The review found that screen readers hear "112units" on the product page. That is now **S-42** (`Ready`, approved 2026-10-14; Phase 4
   Could, planned after S-32). Any further places get fixed only with your agreement.
   **Update 2026-10-14 (D-8):** S-31 and S-32 are on hold. S-43 went first and is **Done**: it is on `main` at `eccb09a`, not pushed yet.
   - Human: push when you choose. CI runs on the push, and only you can see the result.
   - Optional for you: the 10-minute S-43 click-through on its card. It also checks the look of "Overhead cost (optional)", which no test sees.
   - Human: say yes or no to changing S-30 step 4.2 from "Overhead cost 3.5" to "Overhead cost (optional) 3.5" (it works either way).
   **Update 2026-10-15 (D-9):** S-44 is **Done**. It was accepted after your click-through on `laravel` and is on `main` at `9a48cd3`, not pushed yet.
   The rules, ADR-0013 and the project instructions landed with it (`672937f`).
   - Tech-lead, now: S-45's Technical work, for your approval. No second real workshop signs up until S-45 is done.
   - S-46 follows S-45.
   - Human: push when you choose. CI runs on the push, and only you can see the result.
4. Human: walk S-30 as soon as S-29 lands. The product-owner sorts the findings into "fix in Phase 4" and "new backlog item"; you confirm.
5. Phase 4 closes when S-30 is accepted, the S-08 test is green in the gates and in CI on GitHub, and S-45 is accepted (S-44 was accepted on 2026-10-15).
6. Coulds, if time allows and in this order:
   - The tech-lead plans S-08 first; S-40's plan follows once you approve S-08's plan.
   - S-41 (`Ready`) is planned after S-40, and its work starts once you confirm your Pexels key is in your local settings.
   - S-40 and S-41 slip to Phase 5 before anything else.
