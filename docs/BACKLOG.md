# Invory — product backlog

> Owner: **product-owner** (order, wording, acceptance criteria) · Technical detail: **tech-lead** ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> Phases and goals: [ROADMAP.md](ROADMAP.md) · Progress: [STATUS.md](STATUS.md) · Done work and full history: [archive/backlog-2026-10-14.md](archive/backlog-2026-10-14.md)
> Last cleaned: 2026-10-14. **Open work only**, top = next. Done items leave this file; they are recorded in STATUS.md and the archive.

## How to read an item
- **Status:** `Idea` → `Draft` (product-owner writing the card) → `Ready` (you approved the card) → `Planned` (you approved the tech-lead's plan)
  → `In progress` → `In review` (tech-lead gates) → `Acceptance` (product-owner check + your click-through) → `Done` (committed).
- **Priority** (MoSCoW, per phase): Must · Should · Could · Won't (this time). **Size:** S (≤ 1 day of agent work) · M · L (split it).
- **Card:** the full story card (story, acceptance criteria with examples, what you will see, out of scope, questions) lives in
  `docs/stories/<id>-<slug>.md` once the product-owner writes it. Items below show the short version.
- **IDs:** `S-` stories are numbered by the product-owner, `F-` technical items by the tech-lead. IDs are never reused. Next free: **S-40**, **F-54**.

---

## NOW — Phase 4: MVP release readiness

### S-29 · Reliable dev environment · Must · M · enabler · `In progress` (you gave the go on 2026-10-14, D-2)
**You get:** sign-up and password-reset emails that arrive in the test mailbox (http://localhost:8026), pages that open in under 0.15 s,
and AI-team instructions that no longer mention retired tools.
**Why:** those emails fail today, and the AI team still reads instructions for a tool we retired (F-25). The page-speed target (S-18) can't
be measured until this lands.
**Done when:** you reset a password and see the email in the test mailbox; the speed check passes (S-18); the database test suite runs with
one command; no document mentions the retired tool (Sail).
Technical plan (written before story cards existed, so it needs no card; its questions were answered on 2026-10-14):
[plans/dev-docker-db-mail-only-plan.md](plans/dev-docker-db-mail-only-plan.md). Includes F-51 (the `composer dev` script would clash with
the new server). Closes S-18, F-08 (rewritten), F-25 and F-51.

### S-30 · MVP acceptance walkthrough · Must · S · acceptance · `Idea` (card next)
**You get:** a step-by-step click-through script (about 30 minutes) covering every MVP screen on real data: materials, restock and history,
products and composition, cost and capacity, sales, dashboard, catalogue settings. You tick each step or note what's wrong.
**Why:** no screen has ever been signed off. Phase 2's click-through has been pending since 2026-10-08, so "done" has only meant "tests pass".
**Done when:** you have walked the script; every finding is either fixed or recorded as a new backlog item; you declare the MVP accepted.
Includes a check of decision D-7 (2026-10-14): the dashboard numbers fill in just after the page frame appears.

### S-08 · End-to-end golden-path test + demo data on a fresh install · Must · M · feature (quality) · `Idea` (card next)
**You get:** one automated browser test that does what a new user does: sign in → create two materials → create a product with a composition →
see the expected cost and capacity → record a sale → see it on the dashboard. A fresh clone can load the full demo data (12 materials,
7 products, 60 sales, history) with one documented command.
**Why:** no test covers the whole journey across pages today.
**Done when:** the test passes locally (and in CI once the repo has a remote); the README seed command produces the demo data on an empty database.
**To refine:** the demo data is now split into three loaders (decided in the real-backend plan). Never load demo data into the
existing development database (your decision, 2026-10-14).

### S-31 · A section that fails to load offers "Retry" · Should · S · feature · `Idea` (card next; was F-48)
**You get:** if a panel that loads after the page (dashboard panels, product page, material history) fails, you see "Couldn't load this
section" with a **Retry** button instead of loading placeholders that never go away.
**Why:** the dashboard and the product page now load their content after the page appears. One failed request leaves you stuck.
**Done when:** for each deferred section, a failed load shows the message and Retry reloads only that section.

### S-32 · A duplicate SKU saved at the same moment shows a clear message · Should · S · bug · `Idea` (card next; was F-35)
**You get:** if two people save a material or product with the same SKU at the same moment, the second one sees "This SKU is already used"
on the SKU field, not a server error page.
**Why:** it's rare, but today it's a crash. The same protection already exists for catalogue names (F-33).
**Done when:** the race is covered by a test for materials and products, and both show the field error.

**Phase 4 tech allowance** (tech-lead picks these up alongside the stories): F-22 flaky form test · F-12 frontend skill wording ·
F-45 stale "preview" test names (F-08, F-25 and F-51 ride with S-29). Details in [TECH-DEBT.md](TECH-DEBT.md).
**Sizing note:** the Musts are about 70 % of this phase, above the usual 60 % guideline, because the phase is a release gate. S-31 and S-32
are the contingency and slip to Phase 5 first.

---

## NEXT — Phase 5: Fast at real catalogue sizes

### S-27 · Measure speed on a realistic dataset · Must · S · `Idea`
**You get:** a report of how fast every page is with 2 000 materials, 500 products, 50 000 sales and 20 000 stock movements, against
agreed targets (development-only data, never on your database).
**Why:** today's pages are fast on small data. The dashboard and the "can't produce" filter cost every product, which may not scale.

### S-23 · Pre-computed product figures · Must (if S-27 shows the need) · M · `Idea`
**You get:** the products list, its "can't produce" filter and the dashboard stay fast at any catalogue size, because each product's
cost and capacity are kept up to date when something changes instead of being recalculated on every page view.
**Done when:** the figures always equal the live calculation (golden example) after every kind of change, and S-27's budgets pass.

### S-33 · Material history loads on demand · Should · S · `Idea` (was RL-03 + part of S-24)
**You get:** a material's page shows its stock figures and "what you can make with it" at once; the history fills in just after, with
"Load more" for older entries.

### S-35 · The product edit page opens instantly · Should · S · `Idea` (was F-50)
**You get:** clicking "Edit" on a product shows the form frame at once and fills it in, like the product page already does.

### S-34 · The products list shows it is loading · Could · S · `Idea` (was RL-05)
**You get:** while a filter or page change is loading, the list shows a loading state instead of looking frozen.

### S-36 · Deleting a sale or archiving a catalogue item feels instant · Could · S · `Idea` (was part of S-24)
**You get:** the row disappears at once, and comes back with an error message if the server refuses.

**Phase 5 tech allowance:** F-26 · F-38 · F-41 · F-42 · F-49.

---

## NEXT — Phase 6: Production go-live

### S-26 · Production server · Must · M · `Idea`
**You get:** Invory running on a production server: fast page loads, compressed and cached static files, a health check, and a decided
home for sessions and cache. Backups and the database host are part of the card.
**Needs from you first:** decision D-5 (database host with PostgreSQL 13+, where the app can install `pg_trgm`, or a one-time
`CREATE EXTENSION pg_trgm;` by an administrator). Confirm the provisional decisions Q4 and Q6.

**Phase 6 tech allowance:** F-43 · F-46 · F-52 · F-53.

---

## LATER — Phase 7: Planning features (candidates; order to confirm with you)

| ID | You get | Priority (proposed) |
|---|---|---|
| S-10 | **Production planner:** "Plan N units" on a product shows, per material, what's needed, what's in stock and the shortage to buy | Should |
| S-09 | **Live cost preview:** the cost updates while you edit a product's composition (calculated on the server) | Could |
| S-11 | **CSV import/export** of materials | Could |
| S-37 | **Custom date range** ("from – to") for the sales log and the dashboard (was F-11) | Could |
| S-38 | **UX polish bundle:** sale panel layout at 1024 px (F-14), clearer "Filter by product" label for screen readers (F-16), unit price shown the same way everywhere (F-17), darker button hover from the mockup (F-18), a clearer sentence when two materials tie as the limit (F-21) | Could |
| S-39 | **"Manage units" link** in the material form (F-31 q3; you said "not now" on 2026-10-09) | Won't (this time) |

## VISION — not scheduled
Suppliers and purchase orders · unit conversions in compositions · sizes and variants · sub-assemblies · roles and permissions ·
cost history snapshots · production runs that consume stock (Q9) · REST API / mobile · multi-company. See [ROADMAP.md](ROADMAP.md).

---

## Decisions that shape the backlog (still valid)
Q1 native serving · Q2 units editable, conversions later · Q3 any signed-in user edits the catalogue · Q4 bigint keys (provisional) ·
Q6 currency/locale in config (provisional) · Q7 unit locked while used · Q8 sales stock-neutral, hard delete · Q9 no production runs (provisional) ·
F-20 restock updates unit cost by default · F-37 deleted demo products stay deleted · F-39 no thumbnails · F-40 no correction reason ·
dev DB demo seed skipped (2026-10-14). Pending: [STATUS.md §2](STATUS.md#2-decisions-needed-from-you).
