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
- **IDs:** `S-` stories are numbered by the product-owner, `F-` technical items by the tech-lead. IDs are never reused. Next free: **S-43**, **F-58** (F-IDs: see [TECH-DEBT.md](TECH-DEBT.md)).

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
**Open point (2026-10-14, waiting for the tech-lead's report):** the plan assumed Laragon's Apache serves the app. You no longer use
Laragon: the app runs on `php artisan serve` (http://localhost:8000), and Docker runs PostgreSQL and the test mailbox. That built-in server
handles one request at a time on Windows, so "pages open in under 0.15 s" (S-18) may not hold. The tech-lead is adjusting the plan and will
report the speed consequence. This criterion stays as written until then; you decide any change.

### S-30 · MVP acceptance walkthrough · Must · S · acceptance · `Ready` (card approved 2026-10-14; walk it as soon as S-29 lands)
Card: [stories/S-30-mvp-acceptance-walkthrough.md](stories/S-30-mvp-acceptance-walkthrough.md) (the card is the click-through script).
**You get:** a step-by-step script (about 35 minutes, 30 without the optional steps) over every MVP screen on your own data. You type the
worked example (Denim Jacket: TND 35.754, can produce 112, bottleneck "Metal shank button 17mm") with `WT-` SKUs, then remove it at the end.
**Why:** no screen has ever been signed off. Phase 2's click-through has been pending since 2026-10-08, so "done" has only meant "tests pass".
**Done when:** you have walked the script; every finding is either fixed or recorded as a new backlog item; you declare the MVP accepted.
Step 8.1 checks decision D-7 (2026-10-14): the dashboard numbers fill in just after the page frame appears. Best walked right after S-29.

### S-08 · End-to-end golden-path test + demo data on a fresh install · Must · M · feature (quality) · `Acceptance` (delivered 2026-10-14, not committed yet; product-owner review done: AC-5 waits for your look at GitHub, AC-6 for your click-through)
Card: [stories/S-08-golden-path-test-and-demo-data.md](stories/S-08-golden-path-test-and-demo-data.md).
**You get:** one automated browser test that does what a new user does: sign in → add two materials → add Denim Jacket with its
composition → see TND 35.130, 112 units, bottleneck "Metal shank button 17mm" → record a sale → see it on the dashboard. A fresh install
loads the demo workshop (12 materials, 7 products, 60 sales, material history) with one documented step.
**Why:** no test covers the whole journey across pages today.
**Done when:** the test passes in the tech-lead's gates and is green on GitHub (CI runs on every push since D-1, 2026-10-14); the demo-data
step gives the documented figures on an empty database. Never load demo data into the existing development database (your decision, 2026-10-14).

### S-31 · A section that fails to load offers "Try again" · Should · S · feature · `Ready` (card approved 2026-10-14; was F-48)
Card: [stories/S-31-deferred-section-try-again.md](stories/S-31-deferred-section-try-again.md).
**You get:** if a part of a page that loads after the frame fails (dashboard figure cards and top 5, dashboard panels and alerts, the
product page, the materials list's History panel), you see a message such as "Couldn't load the key figures." with a **Try again** button,
instead of loading placeholders that never go away.
**Why:** the dashboard and the product page now load their content after the page appears. One failed request leaves you stuck.
**Done when:** for each of the four places, a failed load shows its message and "Try again" reloads only that section.

### S-32 · A duplicate SKU saved at the same moment shows a clear message · Should · S · bug · `Ready` (card approved 2026-10-14; was F-35)
Card: [stories/S-32-duplicate-sku-clear-message.md](stories/S-32-duplicate-sku-clear-message.md).
**You get:** if two people save a material or product with the same SKU at the same moment, the second one sees "This SKU is already used
by another material." (or "…another product.") on the SKU field, not a server error page, and keeps what they typed.
**Why:** it's rare, but today it's a crash. The same protection already exists for catalogue names (F-33).
**Done when:** the race is covered by a test for materials and products, both show the field error, and nothing half-saved is left behind.

### S-40 · A realistic catalogue of 100 products on a fresh install · Could (after S-08; slips to Phase 5 first) · M · enabler · `Ready` (card approved 2026-10-14; next: tech-lead plan after S-08)
Card: [stories/S-40-realistic-catalogue-100-products.md](stories/S-40-realistic-catalogue-100-products.md). Depends on S-08 (its demo-data step).
**You get:** an optional setup step, after the demo workshop, on a new, empty database only (never `laravel`). It adds 93 believable
products (100 with the demo's 7) and about 110 materials, with compositions grounded in real garment, shoe and bag costing figures, and
prices in TND. It covers every situation a workshop meets: low stock, out of stock, can't produce, drafts without a composition and
negative margins. It adds no sales and the same data every run. Pictures come with S-41; until then rows show today's placeholder.
**Why:** 12 materials and 7 products can't show how lists, filters and pages behave with a real-size catalogue.
**Done when:** the step gives the documented figures on a new database, the demo workshop's figures (S-08 AC-6) still hold, and running it
twice changes nothing.

### S-41 · A real photo on every item of the demo catalogue · Could (right after S-40; first to slip to Phase 5) · M · enabler · `Ready` (card approved 2026-10-14; tech-lead plans it after S-40; work starts once you confirm your Pexels key is in your local settings)
Card: [stories/S-41-real-photos-for-the-catalogue.md](stories/S-41-real-photos-for-the-catalogue.md). Depends on S-40, and on **your
Pexels key**: you create a free Pexels account and key and put the key in your local settings (never committed). The work can't start
until it exists.
**You get:** about 220 real photos from Pexels, one per catalogue material and product. Each photo is 80 KB or less, about 18 MB in total,
stored with the app so the step works offline. A licence record lists every photo, and the setup guide reads "Photos provided by Pexels".
No credit appears on screens. No faces and no brand logos. Where no close photo exists, the closest photo of the same kind is used, and
one photo may serve at most 3 items.
**Why:** you chose real photos (S-40 Q3). Unsplash's rules don't allow storing its photos with the app without hand-picking, so Pexels
only (Q1).
**Done when:** every catalogue item has a fitting photo, every photo is in the licence record, the size limits hold, and your spot-check of
20 photos passes.

### S-42 · Screen readers hear "112 units", not "112units" · Could (after S-32; your choice 2026-10-14) · S · bug (accessibility) · `Ready` (card approved 2026-10-14; tech-lead plans it after S-32; any place beyond the two product-page cards only with your agreement)
Card: [stories/S-42-screen-readers-hear-units.md](stories/S-42-screen-readers-hear-units.md).
**You get:** on the product page, "Can produce now 112 units" and "Sold · last 30 days 38 units" are read aloud with a space between the
number and its unit. The screen looks the same.
**Why:** today assistive technology reads "112units": the gap is only visual. Invory aims at WCAG 2.2 AA.
**Done when:** every number shown with a unit word on the product page reaches screen readers with a space, and the golden-path test
checks "Can produce now 112 units".

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
dev DB demo seed skipped (2026-10-14) · D-1 repository on GitHub, CI on every push (2026-10-14) · D-3 Phase 4 scope approved with its four cards (2026-10-14) · low stock: no new story, no email alerts, no flag without "Reorder at" (2026-10-14) · large demo catalogue: new throwaway database only,
add-on after the demo workshop, no sales, same every run, no brand or sub-category, real Pexels photos with credits off-screen (S-40 Q1–Q7,
S-41 Q1–Q4, 2026-10-14). Pending: [STATUS.md §2](STATUS.md#2-decisions-needed-from-you).
