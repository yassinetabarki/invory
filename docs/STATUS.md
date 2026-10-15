# Invory — project status report

> Owner: **product-owner** (updated at every phase boundary and after every accepted story) · Report date: **2026-10-14**
> Sources: every plan in [docs/plans/](plans/README.md), the [backlog archive](archive/backlog-2026-10-14.md) and `git log` on `main`.
> Gate results: run by the main session on 2026-10-14 for this first report. Later updates quote the tech-lead's latest delivery report
> (the product-owner never runs gates).
> Decision IDs: **D-x** are this report's open decisions. Recorded decisions keep their source, e.g. "Phase 3 plan Q7" or "rendering plan Q3".
> Live documents: [ROADMAP.md](ROADMAP.md) (phases) · [BACKLOG.md](BACKLOG.md) (ordered stories) · [TECH-DEBT.md](TECH-DEBT.md) (technical register)

---

## 1. Headline

| | Status | In one line |
|---|---|---|
| **Overall** | 🟡 **AMBER** | The MVP is built and every automated check passes on this computer. The checks now also run on GitHub, but nobody has seen a result yet. You haven't signed the MVP off, and no test walks a whole user journey. The Phase 4 cards are approved. |
| Scope (MVP features) | 🟡 AMBER | Every feature in the MVP goal and the 2026-10-08 scope additions works on real data. The preview layer is gone. New since 2026-10-14 (D-9): every account must see only its own workshop's data (S-44, S-45). Today all accounts share one catalogue. |
| Quality (automated) | 🟢 GREEN | All automated checks pass on this computer (2026-10-14): 2 050 backend tests, 1 134 screen tests, code style, static analysis, type check and production build. |
| Product acceptance | 🔴 RED | No screen has been signed off by the human. The Phase 2 click-through has been pending since 2026-10-08. "Done" has meant "tech-lead approved". |
| Dev environment | 🟡 AMBER | The app runs on `php artisan serve` (http://localhost:8000); Docker runs PostgreSQL and the test mailbox (http://localhost:8026), and `.env` already points mail there. Laragon and Apache aren't used. S-29 (in progress) removes the Sail remnants; the page-speed target (S-18) is unmeasured and may not hold on the built-in server (tech-lead reporting). |
| Release readiness | 🔴 RED | Missing: a test of a full user journey, a live server with a chosen database, and separate data for each workshop (S-44, S-45). The automatic checks now run on GitHub on every push (D-1, 2026-10-14), but **no result has been seen yet**. |
| Process | 🟡 AMBER | Statuses in the backlog and in 9 of 10 plans were stale. Fixed today. Roles are now split: product-owner (what and why) and tech-lead (how). Three stories are in flight against a limit of two (S-29, S-08, S-44), by your choice (D-9). |

**Current phase:** Phase 3 (real backend) and most of the performance track are **finished and committed**. The project now enters
**Phase 4: MVP release readiness** (see [ROADMAP.md](ROADMAP.md)).

---

## 2. Decisions needed from you

**Needed from the human now**
| # | Decision | Recommendation |
|---|---|---|
| D-4 | Three early choices were only provisional (Phase 3 plan): **Q4** records are numbered with simple counters (already used everywhere) · **Q6** currency and language are set at installation, not in a settings screen · **Q9** making products never reduces material stock (no "production runs" for now). Keep all three? (Q5 is closed: S-17 removed the preview layer) | Keep all three; confirm before Phase 6 |
| D-5 | Production database host (needs PostgreSQL 13+ and `pg_trgm`) | Before S-26; not urgent |
| D-6 | Delete the 5 merged branches and the stale worktree `.claude/worktrees/fix-hover-prefetch-bug`? | Yes (nothing unmerged) |

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
- **Over the limit:** S-44 starts now, even though S-29 (in progress) and S-08 (in acceptance) already fill the two-story
  work-in-progress limit (not my recommendation). S-45 comes right after S-44 and they ship together: no second real workshop signs up
  until both are done.

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
| **Dashboard** | Period selector, KPI cards, top 5 products by units or revenue, materials that need a restock, recent sales, and "can't produce" alerts. The page appears at once and its panels fill in. |
| **Settings: Catalogue** | Manage units, material categories and product categories without a deploy: create, rename, archive, restore, delete when unused. Names are unique regardless of case. A material's unit is locked while a product uses it. |
| **Account** | Starter-kit authentication: register, login, email verification, password reset, two-factor, passkeys, profile, password, light/dark appearance. Every account sees the same data (one shared catalogue) until S-44 and S-45 land. |
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
| [dev-docker-db-mail-only-plan](plans/dev-docker-db-mail-only-plan.md) | "Proposed, awaiting Q1–Q4" | **Approved, in execution** (D-2, 2026-10-14). The human answered the plan's Q1–Q4 the same day. Story **S-29** |

The plan headers were corrected on 2026-10-14, and [plans/README.md](plans/README.md) now indexes every plan with its real status.

---

## 6. What is missing or incomplete

### 6.1 Blocks the MVP release (Phase 4)
| # | Gap | Why it matters | Backlog |
|---|---|---|---|
| G-1 | **No product acceptance.** No screen was ever signed off by the human | We don't know that the product does what the business wants; "done" so far means "tests pass" | S-30 |
| G-2 | **No end-to-end test** (0 browser tests) and no one-command demo data for a fresh install | A break between pages (login → material → product → sale → dashboard) would go unnoticed | S-08 |
| G-3 | **A panel that fails to load spins forever.** Deferred panels have no error state, and the dashboard and product page are now fully deferred | One failed request leaves the user stuck on loading placeholders, with no retry | S-31 (F-48) |
| G-4 | **Dev environment drift**: `laravel.test`, Sail and `sail.cmd` are still on `main`, and CLAUDE.md and the agent files still describe Sail (F-25); S-29 removes them (in progress). Laragon and Apache are no longer used: the app runs on `php artisan serve`, and `.env` already sends mail to Docker's test mailbox (a password-reset check is still to do). The built-in server handles one request at a time on Windows | Agents follow outdated commands; the panels that load after the page queue behind each other, so the speed target (S-18) may not hold. The tech-lead is adjusting S-29's plan and will report | S-29 |
| G-5 | **Two people saving the same SKU at the same moment get a server error page** instead of a validation message | Rare, but it's a crash | S-32 (F-35) |
| G-6 | **Every account sees and changes the same data.** A new sign-up opens on every material, product and sale of the installation | Invory can't serve a second business until each workshop sees only its own data (D-9) | S-44, S-45 |

### 6.2 Not started, needed for real-world use (Phases 5–6)
- **Speed at real catalogue sizes:** the dashboard and the "can't produce" filter cost every product in PHP, and sales totals walk every row
  of the period. Fine today, never measured at 500 products and 50 000 sales (S-27 measures, S-23 fixes).
- **On-demand material history** with "Load more" (S-33), a loading state on the products list (S-34), an instant product edit page (S-35),
  instant delete and archive feedback (S-36).
- **Production:** no runtime image, no production database chosen (it needs PostgreSQL 13+ with `pg_trgm`), no session or cache store
  decision, no health check, no backups (S-26).
- **CI results not seen yet:** since 2026-10-14 (D-1) the automatic checks run on GitHub on every push to `main` and `phase/**`, but nobody
  has looked at a result yet. Phase 4's exit criterion "S-08 green in CI" no longer waits for a decision.

### 6.3 Open human steps
- S-18 page-speed sign-off (after S-29: run the probe, target p95 ≤ 150 ms). Open point: whether that target holds on `php artisan serve`
  is pending the tech-lead's report; the criterion stays as written until you decide.
- Dev database: demo seeding was **skipped by decision** (2026-10-14). Don't re-offer it.

---

## 7. Risks and issues (RAID)

| # | Type | Item | Severity | Mitigation / owner |
|---|---|---|---|---|
| R-1 | Risk | CI runs on every push since 2026-10-14 (D-1), but no result has been seen yet: the repository is private and only the human can open the Actions tab. It may already be red (the dev-Docker plan suspects the photo-storage check fails in CI) | High | Human: check the Actions tab after the next push and report the result; tech-lead: keep CI green (S-29 includes the CI photo-storage fix) |
| R-2 | Risk | No E2E test across pages | High | S-08 |
| R-3 | Issue | Deferred panels can hang without an error state | Medium | S-31 |
| R-4 | Issue | Agent docs still describe Sail; the password-reset mail to Docker's test mailbox is not yet confirmed; the speed target may not hold on `php artisan serve` | Medium | S-29 (tech-lead reporting on speed) |
| R-5 | Risk | Unmeasured performance at real catalogue sizes (whole-catalogue costing in PHP) | Medium | S-27, then S-23 |
| R-6 | Risk | Stories counted as "done" without business acceptance | Medium | S-30; from now on the product-owner acceptance step (TEAM.md) |
| R-7 | Issue | Stale statuses and colliding IDs (RL stories reused S-01…S-05; the dev-Docker plan reused F-41…F-43) | Low | Fixed 2026-10-14; IDs are now assigned only by their owners (product-owner: S-, tech-lead: F-) |
| R-8 | Risk | Plans executed without a human review (fix-rl-02) | Low | The workflow now requires an approved story card and an approved technical plan |
| R-9 | Issue | Flaky Vitest test under load (F-22) | Low | Gates run sequentially; fix in TECH-DEBT |
| R-10 | Dependency | S-41 (real photos) can't start until you have created a free Pexels account and key and put the key in your local settings | Low | Human, before S-41 starts |
| R-11 | Risk | About 220 Pexels photos stored in the repository are fine while it stays private. Publishing the repository would redistribute them in bulk | Low | Product-owner raises the licence conditions with you before any change to the repository's visibility (S-41 AC-6) |
| R-12 | Risk | S-44 changes how every screen picks its data while S-29 and S-08 are still open: three stories in flight against a limit of two (D-9). The demo data and the golden-path test must keep their figures | Medium | Tech-lead plans S-44 against S-08's demo data and golden path (S-44 AC-7); S-45 follows right after; the product-owner reviews both together |
| R-13 | Risk | When someone deletes the only account of a workshop, that workshop's materials, products and sales stay in the database with nobody able to reach them (D-9). That may matter for privacy once other businesses use a public server | Low now, higher at go-live | The "Delete account" wording says the data stays (S-44 AC-6). Product-owner raises it with you again before S-26 |
| R-14 | Dependency | The project instructions, ADR-0003 and ARCHITECTURE.md still say "single company, no teams" | Low | Main session and tech-lead update them with S-44 (D-9) |

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
1. Human: decisions D-4 … D-6, and a first look at the CI result in GitHub's Actions tab.
2. Tech-lead (in progress): adjust S-29's plan to `php artisan serve` (no Apache, no Laragon), report the speed consequence, then finish S-29.
3. Tech-lead: plan S-08, S-31 and S-32 next, from their `Ready` cards (done by the product-owner on 2026-10-14).
   **Update 2026-10-14:** S-08 is delivered, not yet committed. The product-owner review accepted every criterion from the evidence;
   two things are still yours:
   - AC-5: push, then check that the `golden-path` job is green on GitHub (this also re-checks PostgreSQL, which wasn't re-run locally);
   - AC-6: the fresh-install click-through on a throwaway database.
   The review found that screen readers hear "112units" on the product page. That is now **S-42** (`Ready`, approved 2026-10-14; Phase 4
   Could, planned after S-32). Any further places get fixed only with your agreement.
   **Update 2026-10-14 (D-8):** S-31 and S-32 are on hold. S-43 went first and is **Done**: it is on `main` at `eccb09a`, not pushed yet.
   - Human: push when you choose. CI runs on the push, and only you can see the result.
   - Optional for you: the 10-minute S-43 click-through on its card. It also checks the look of "Overhead cost (optional)", which no test sees.
   - Human: say yes or no to changing S-30 step 4.2 from "Overhead cost 3.5" to "Overhead cost (optional) 3.5" (it works either way).
   **Update 2026-10-14 (D-9):** S-44, S-45 and S-46 are `Ready`.
   - Tech-lead, now: write the business rules R1–R6 into the costing rules and a decision record that replaces ADR-0003, then S-44's
     Technical work (S-45's straight after), for your approval.
   - Main session and tech-lead: update the project instructions and ARCHITECTURE.md ("single company, no teams").
   - S-46 follows S-45.
4. Human: walk S-30 as soon as S-29 lands. The product-owner sorts the findings into "fix in Phase 4" and "new backlog item"; you confirm.
5. Phase 4 closes when S-30 is accepted, the S-08 test is green in the gates and in CI on GitHub, and S-44 and S-45 are accepted.
6. Coulds, if time allows and in this order:
   - The tech-lead plans S-08 first; S-40's plan follows once you approve S-08's plan.
   - S-41 (`Ready`) is planned after S-40, and its work starts once you confirm your Pexels key is in your local settings.
   - S-40 and S-41 slip to Phase 5 before anything else.
