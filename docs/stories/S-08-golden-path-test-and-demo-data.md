# S-08 — One automated test walks the whole journey, and a fresh install shows the demo workshop
> Phase 4 · Priority Must · Size M · Status **Acceptance** (delivered 2026-10-14, uncommitted on `phase/4-mvp-release-readiness`; card approved 2026-10-14) · Type feature (quality)
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Related: S-30 (your walkthrough),
> D-1 (decided 2026-10-14: the repository is on GitHub and the automatic checks run on every push) · Technical plan: added by the tech-lead

## Story
As the workshop owner, I want one automated test that does what a new user does, from signing in to seeing a sale on the dashboard,
so that a break between two screens is caught before I ever see it.
As someone installing Invory on a new machine, I want one documented step that fills an empty database with the demo workshop, so that
I can see every screen working with realistic numbers straight away.

## Why it matters
Today about 3 200 automated checks test each screen and each calculation on its own, but none goes from one page to the next the way a
person does. A broken link between "save the product" and "see its cost", or between "record a sale" and "see it on the dashboard", would
go unnoticed until you clicked through it yourself.

## Acceptance criteria
AC-1 **The golden path, end to end.** Given an empty install with one user account, when the test runs, it:
1. signs in,
2. adds two materials: Denim 12oz Indigo (metre, TND 8.500 per m, 250 m in stock) and Metal shank button 17mm (piece, TND 0.350 per pc,
   900 pc in stock),
3. adds the product Denim Jacket (Active, labor TND 12.000, overhead TND 3.500, selling price TND 89.000) with the composition 1.8 m of
   denim plus 10% waste and 8 buttons with no waste,
4. sees on the product page: unit cost **TND 35.130**, can produce **112 units**, bottleneck **Metal shank button 17mm**, margin
   **TND 53.870 · 60.53%**,
5. records a sale of 3 Denim Jackets at the pre-filled price, dated today,
6. sees on the dashboard (Last 30 days): Total sales **TND 267.000** ("1 sale · last 30 days"), Units sold **3**, Inventory value
   **TND 2,440.000**, Can't produce **0** "of 1 product", and Denim Jacket first in "Top selling products" with 3 units,
7. goes back to the product and still sees **112 units**, because a sale never changes stock.

AC-2 **It waits for what loads after the page.** The test waits for the dashboard figures and the product page content that fill in just
after the page frame (decision D-7). It never passes on a loading placeholder.

AC-3 **A failure says where.** When the test fails, its message names the step that broke (for example "dashboard: Total sales") and
keeps a screenshot of the screen at that moment.

AC-4 **It never touches your data.** The test runs on its own throwaway database. It can't reach your development database `laravel`.

AC-5 **It runs with the other checks, here and on GitHub.** The tech-lead runs it as one of the quality gates before every delivery, and it
runs automatically on GitHub with the other checks on every push to `main` and `phase/**` (decision D-1). The story is done only when you
have seen it green in GitHub's Actions tab (the repository is private, so only you can look).

AC-6 **A fresh install shows the demo workshop.** Given a new, empty database, when someone follows the setup guide's demo-data step,
they can sign in as `test@example.com` (password `password`) and see:
- **12 materials**, inventory value **TND 10,544.300**, and **4** that need a restock (3 low, 1 out of stock)
- **7 products**, including Denim Jacket at unit cost **TND 35.754**, can produce **112**, bottleneck **Metal shank button 17mm**, margin
  **59.83%** at TND 89.000
- **60 sales**. The dashboard's "Last 30 days" shows Total sales **TND 16,850.000** from **48 sales**, **296** units sold, and the top 5 by
  units: Classic White T-shirt 142, Denim Jeans 5-pocket 64, Denim Jacket 38, Leather Belt 31, Leather Tote 12
- a history on every demo material: each one has its "Created" entry, and three of them also have a restock and a price change (Q1)

AC-7 **Safe to repeat, never on real data.** Running the demo-data step a second time creates nothing twice, and a demo product you
deleted stays deleted (decision F-37). The setup guide says the step is for a new, empty database only, and warns that on a database with
your own data it resets the 12 demo materials to their demo values. Invory's own instructions never run it on `laravel`
(your decision, 2026-10-14).

AC-8 **The guide matches the app.** The setup guide's worked example reads "Denim Jacket costs TND 35.754 per unit". Today it says
"35.75 TND/unit".

## Example with real numbers
The test's Denim Jacket uses two of the worked example's four materials, which keeps the test short:
material cost 1.98 m × TND 8.500 = TND 16.830, plus 8 pc × TND 0.350 = TND 2.800, gives TND 19.630. Add labor TND 12.000 and overhead
TND 3.500: unit cost **TND 35.130**. Capacity: denim covers ⌊250 ÷ 1.98⌋ = 126 jackets and the buttons ⌊900 ÷ 8⌋ = **112**, so the
button is the bottleneck. Margin: TND 89.000 − TND 35.130 = **TND 53.870**, which is **60.53%** of the selling price.
The full four-material example (TND 35.754) is already covered by the costing tests and appears in the demo data (AC-6).

## What you will see (UX)
- Nothing changes on any screen. You see the result in the tech-lead's delivery reports: one more gate, "golden path", green or red.
- On a fresh install, the Dashboard, Materials, Products and Sales pages open full of demo data instead of empty states.
- The setup guide has one clearly labelled demo-data step, with the sign-in details and the figures you should see (AC-6).

## Out of scope
- Browser tests for every screen: one golden path only. Your walkthrough (S-30) covers the rest by hand.
- Error paths (a section that fails to load is S-31; a duplicate SKU saved at the same moment is S-32).
- Speed measurement (S-18, S-27) and checks in browsers other than the one the tech-lead picks.
- Loading demo data into your development database `laravel`: declined on 2026-10-14, not planned.
- Making the rest of the automatic checks green on GitHub if they are red today: S-29 already fixes the one known cause (the photo-storage
  check); anything else the first CI result shows becomes its own item.

## Questions for you (answered 2026-10-14)
Q1 Should the demo materials come with a history? **Answer: (a)** every demo material starts with its "Created" entry, and three of them
also have a restock and a price change. (Other options were: "Created" only; no history.)

Q2 Keep the test and the demo data in one story? **Answer: (a) one story.** (Other option was: split the demo data into its own story.)

## Details settled during planning (product-owner, 2026-10-14; no acceptance criterion changes)
The tech-lead's plan asked four product questions (P-1 to P-4). The answers fill in details that Q1 and AC-6 to AC-8 left open. They
reach you with the plan for approval.

**P-1: the three materials with a restock and a price change.** The other 9 have their "Created" entry only. Today's stock and unit
costs are unchanged, so the inventory value stays TND 10,544.300.

| Material | Created (37 days ago) | Restocked | Price change |
|---|---|---|---|
| Metal shank button 17mm | 400 pc at TND 0.320 | 6 days ago: +500 pc, reference PO-2291 | TND 0.320 → TND 0.350 |
| Woven brand label | 250 pc at TND 0.100 | 14 days ago: +150 pc | TND 0.100 → TND 0.120 |
| Denim 12oz Indigo | 150 m at TND 8.200 | 18 days ago: +100 m, reference PO-2281 | TND 8.200 → TND 8.500 |

**P-2: who made the demo history entries.** The demo user, so each entry reads "by Test User", like a real entry.

**P-3: dates.** The history is dated relative to the day the step runs: "Created" 37 days ago, restocks 6 to 18 days ago. The history then
reads like a real one. This holds even though the materials themselves are added on the day the step runs.

**P-4: "the setup guide".** This is the README's demo-data step; the setup document links to it. It gives the sign-in details and the
AC-6 figures word for word, and says the step is safe to run again: nothing is created twice, and deleted demo products stay deleted. It
also keeps the warning that the step resets the 12 demo materials on a database with your own data. The current line "a second run fails
because the test user already exists" goes.

## For the tech-lead's sizing
- Which browser-test tooling, how much it adds to a fresh setup (downloads, Windows support), and how long one run takes.
- Whether the golden path joins the standard test command or gets its own gate, given the gates run one at a time; and what the CI
  workflow needs to run it (browser download, run time on GitHub).
- Which parts of AC-6 already hold today (the 12 materials, 7 products and 60 sales are loaded by the existing setup), and what the
  history chosen in Q1 costs.

## Acceptance check (product-owner review 2026-10-14, from the tech-lead's delivery report; nothing committed yet)
Delivery status: PARTIAL. All 5 technical cards are APPROVED, and every gate is green on the final tree. Two items are open:
- the PostgreSQL parity check wasn't re-run after the restart (CI covers it);
- AC-5 needs your look at GitHub.

| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 Golden path | `GoldenPathTest` › "a new user goes from sign-in to a sale on the dashboard": 16 named steps with the card's exact figures (TND 35.130, 112, button, TND 53.870 · 60.53%, TND 267.000, 3, TND 2,440.000, "0 of 1 product", Denim Jacket first with 3 units, still 112). Passed 3 runs in a row | Accepted |
| AC-2 Waits for deferred content | The same test waits for each figure by its exact value, then checks that no "Loading…" or "Checking…" text is left; no fixed pauses | Accepted |
| AC-3 Failure names the step | `GoldenPathStepTest`, plus failures made on purpose: `Golden path failed at step "dashboard: Total sales"` with a screenshot; on GitHub the screenshots are kept as `golden-path-screenshots` | Accepted |
| AC-4 Own database | First step "database: throwaway" checks the in-memory database; the test suite refuses any PostgreSQL database other than `testing` | Accepted |
| AC-5 Runs with the gates and green on GitHub | Local gate `composer test:browser` green; new `golden-path` job on GitHub | **Accepted pending your look** at the Actions tab after the push |
| AC-6 Fresh install shows the demo workshop | `FreshInstallDemoTest` › "a fresh install shows the demo workshop" (every AC-6 figure, the history on exactly the button, label and denim) · `DemoStockHistorySeederTest` (2 tests, every entry "by Test User") · CI runs the documented step on a fresh database | Accepted by tests; **your click-through steps 4–8** |
| AC-7 Safe to repeat, never on real data | `FreshInstallDemoTest` re-run test (nothing twice, the deleted Belt stays deleted, 18 history entries) · CI runs the step twice · README Step 6 and SETUP §3 carry the warning and "safe to run again" | Accepted |
| AC-8 Guide matches the app | README Step 6 reads "*Denim Jacket* costs **TND 35.754** per unit" (read by the product-owner); "35.75 TND/unit" and "a second run fails" are gone | Accepted |

Found during the review (not gaps in S-08's criteria):
- The guide says "a new, empty database only" but doesn't say how to get one on a machine whose database is `laravel`. Setting the
  database in the terminal doesn't reach the running app: `php artisan serve` passes on only `APP_ENV`, so the pages would show
  `laravel`. The click-through below uses a separate settings file, which the tech-lead confirms first. S-40 AC-5 makes this part of the
  guide.
- On the product page, "Can produce now 112 units" reaches screen readers as "112units" (no space). Proposed as S-42.
