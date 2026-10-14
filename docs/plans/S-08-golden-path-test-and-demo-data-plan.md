# Technical plan: S-08 One automated test walks the whole journey, and a fresh install shows the demo workshop
> Status: **approved 2026-10-14 (H-1 a, H-2 a, H-3 a; PO answers P-1 a, P-2 a, P-3 yes, P-4 yes), in execution** on `phase/4-mvp-release-readiness` · Date: 2026-10-14 · Owner: tech-lead
> Story card: [docs/stories/S-08-golden-path-test-and-demo-data.md](../stories/S-08-golden-path-test-and-demo-data.md) (`Ready`, approved 2026-10-14)
> Constraints from the brief: dev runtime per ADR-0012 (app on `php artisan serve` :8000, Docker = PostgreSQL + Mailpit only). **Nothing in this
> plan seeds, migrates or wipes the development database `laravel`** (human, 2026-10-14). F-37 holds. D-1: CI on every push to `main` and `phase/**`.
> D-7: dashboard KPIs stay deferred. Next up (not planned here): S-40 adds an optional catalogue step after this demo step; S-41 adds photos.

## Scope
**In:** one Pest 4 browser test (the golden path, AC-1…AC-4) and the tooling it needs; a separate "golden path" gate locally and a CI job
(AC-5); a stock history for the 12 demo materials (Q1), a re-runnable demo-data step and one end-to-end test that a fresh install shows the
AC-6 figures (AC-6, AC-7); the setup guide (README Step 6, `docs/SETUP.md` §3) brought in line (AC-7, AC-8).
**Out (matches the card):** browser tests for other screens, error paths (S-31, S-32), speed (S-18, S-27), other browsers, demo data in
`laravel`, fixing other CI reds that the first run may show (each becomes its own item).

## Facts verified at intake (2026-10-14, read-only)
| Fact | Evidence |
|---|---|
| `php` on the documented Laragon path now resolves to **Herd PHP 8.4.26** (`C:\Users\Xpardox\.config\herd\bin\php84\php.exe`), with `sockets`, `pdo_sqlite`, `pdo_pgsql`, `intl`. CI still covers 8.3 (ci) and 8.3/8.5 (pgsql) | `php -v`, `php -m`, `PHP_BINARY` |
| Docker: `invory-pgsql-1` and `invory-mailpit-1` up and healthy | `docker ps` |
| Pest is **v4.7.8**. `pestphp/pest-plugin-browser` **v4.3.1** (2026-04-08) needs PHP ^8.3, Pest ^4.4.5, `ext-sockets`. v5.x needs PHP 8.4 + Pest 5: **not usable** | `composer.lock`, Packagist |
| The plugin serves the app **in the test process** (Amp `SocketHttpServer` → Laravel `HttpKernel`), and serves files under `public/` itself. So `RefreshDatabase` on phpunit.xml's SQLite `:memory:` is shared with the browser's requests. It has no Vite handling: it needs a built `public/build`, and a `public/hot` file (the human's `composer dev`) would point pages at the Vite dev server | plugin source `src/Drivers/LaravelHttpServer.php` (4.x) |
| Plugin docs: screenshots of failures go to `tests/Browser/Screenshots`, traces to `tests/Browser/Traces`; default wait 5 s, `pest()->browser()->timeout(ms)`; CI recipe `npx playwright install --with-deps` + upload artifacts on failure. **No statement about Windows** | pestphp.com/docs/browser-testing |
| `docs/ARCHITECTURE.md` §testing and `docs/TEAM.md` testing rubric already name "Pest 4 browser tests (Playwright)" for the golden path (S-08) | docs |
| phpunit.xml lists suites Unit, Feature, Arch; `<env>` doesn't override real env vars. PHPUnit supports `defaultTestSuite` | `phpunit.xml`, PHPUnit Loader |
| Skeletons announce themselves as `role="status"` "Loading {label}…" (`components/shared/skeletons.tsx`) | source |
| `config('app.timezone')` is `UTC`; currency default `TND` (`config/invory.php`) | config |
| CI (`.github/workflows/tests.yml`): job `ci` (PHP 8.3, `composer setup` on a SQLite file, Vitest, `composer ci:check`) and job `pgsql` (8.3/8.5) | workflow |

### AC-6 today: what already holds and what doesn't
| AC-6 / AC-7 item | Today | Evidence |
|---|---|---|
| 12 materials, inventory value 10544.3000, 4 to restock (3 low, 1 out) | **Holds** | `tests/Feature/Seeders/DemoSeedersTest.php` › "seeds 12 materials worth 10544.3…" |
| 7 products; Denim Jacket 35.7540 / 112 / button / 59.83 % at 89.0000 | **Holds** | same file › "seeds the lines of the Denim Jacket with the worked example…" |
| 60 sales; last 30 days 48 sales, 296 units, 16850.0000; top 5 by units 142/64/38/31/12 | **Holds** (with `DemoCatalogSeeder` + `DemoSalesSeeder`, time frozen) | `tests/Feature/Dashboard/DashboardTest.php`, `tests/Feature/Sales/DemoSalesSeederTest.php` |
| Sign in as `test@example.com` / `password` | **Holds once**: `DatabaseSeeder` uses `User::factory()->create(...)` | `database/seeders/DatabaseSeeder.php` |
| A history on every demo material (Q1) | **Missing.** The seeders write **no** `stock_movements`: every demo material has an empty history, and `php artisan invory:stock:verify` reports all 12 as drifting (ledger 0 ≠ stored stock) | `DemoCatalogSeeder::run()` uses `updateOrCreate`; only `CreateMaterial` and the S-16 backfill migration write `created` rows |
| Re-run creates nothing twice | **Fails**: the second `php artisan db:seed` throws on the unique `users.email`. Materials, products and sales are already idempotent | `DatabaseSeeder` |
| A deleted demo product stays deleted (F-37) | **Holds** | `DemoSeedersTest` › "creates only the demo products whose SKU is missing, and never one that was deleted (F-37)" |
| No test runs the **whole** documented step (`DatabaseSeeder`) and checks the AC-6 figures through the screens' props | **Missing** (only counts are checked) | `DemoSeedersTest` › "is part of the database seeder…" |
| AC-8 guide wording | **Mostly holds**: README Step 6 already says "*Denim Jacket* costs **TND 35.754** per unit". Nothing in the repo says "35.75 TND/unit" any more. README still says "a second run fails because the test user already exists" (contradicts AC-7) and lists no figures or history | `README.md` L182–203, `grep` |

**Cost of the Q1 history:** one new seeder (~100 lines, mostly data) writing 18 ledger rows through the existing `AppendStockMovement`
(12 `created`, 3 `restock`, 3 `price_change`), plus 2 tests. It also fixes a real gap: after the demo step the ledger balances
(`invory:stock:verify` green) instead of drifting on all 12 materials. Roughly half a card (S-08-c).

## AC traceability
| AC | Technical card(s) | Test(s) that will prove it |
|---|---|---|
| AC-1 Golden path | S-08-b (on S-08-a tooling) | `tests/Browser/GoldenPathTest.php` › "a new user goes from sign-in to a sale on the dashboard" |
| AC-2 Waits for deferred content | S-08-b | same test: every deferred figure is asserted by its exact value (a placeholder can't match), and each page step asserts that no `role="status"` "Loading …" remains before reading figures |
| AC-3 Failure names the step | S-08-a (step helper), S-08-b (uses it) | `tests/Unit/Browser/GoldenPathStepTest.php` › "a failing step rethrows with the step name" (no browser) + the plugin's failure screenshot; the tech-lead also breaks one assertion once during review and attaches the message + screenshot path to the report |
| AC-4 Own throwaway database | S-08-a | `GoldenPathTest` first step asserts the connection is SQLite `:memory:` (or PostgreSQL `testing`, already guarded by `tests/TestCase.php`); the suite uses `RefreshDatabase` |
| AC-5 Runs with the gates, here and on GitHub | S-08-a (`composer test:browser`), S-08-e (CI job) | tech-lead gate table shows "golden path" green; GitHub Actions job `golden-path` green on the phase branch (the human looks: the repo is private) |
| AC-6 Fresh install shows the demo workshop | S-08-c (history), S-08-d (end-to-end check) | `tests/Feature/Seeders/DemoStockHistorySeederTest.php` (history + ledger balances) · `tests/Feature/Seeders/FreshInstallDemoTest.php` › "a fresh install shows the demo workshop" (signs in with `test@example.com` / `password`, then dashboard, materials, products, sales and history props) · CI step "demo data on a fresh database" (S-08-e) |
| AC-7 Safe to repeat, never on real data | S-08-c (re-runnable user + history), S-08-d (test + guide) | `FreshInstallDemoTest` › "running the demo step again creates nothing twice and keeps a deleted demo product deleted" · CI step runs `db:seed` twice on a throwaway SQLite file · README Step 6 + SETUP §3 wording |
| AC-8 Guide matches the app | S-08-d | README Step 6 reads "Denim Jacket costs TND 35.754 per unit" (review check; no test) |

## Technical cards (slicing table)
| # | Card id | AC | Lane | Files (exact paths) | Line budget | Tests allowed | Owner | Commit label |
|---|---|---|---|---|---|---|---|---|
| 1 | S-08-a | AC-3, AC-4, AC-5 (local) | CRITICAL (new dependency, a new gate, data safety) | `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `phpunit.xml`, `tests/Pest.php`, `.gitignore`, `tests/Browser/Support/GoldenPathStep.php`, `tests/Browser/SmokeTest.php` (temporary, deleted by S-08-b), `tests/Unit/Browser/GoldenPathStepTest.php` | ~120 (config + helper) | 1 browser smoke (guest sees the login page, proves Windows + Chromium), 1 unit test of the step helper | BE | `test(e2e): add Pest browser tests and a separate golden-path gate (S-08-a)` |
| 2 | S-08-b | AC-1, AC-2, AC-3 | CRITICAL (asserts money figures; a flaky gate blocks every delivery) | `tests/Browser/GoldenPathTest.php`, delete `tests/Browser/SmokeTest.php`; only if a control has no accessible name: the one component in `resources/js/components/**` that needs a label (report it, no copy change) | ~200 test lines | exactly 1 browser test | FE | `test(e2e): golden path from sign-in to a sale on the dashboard (S-08-b)` |
| 3 | S-08-c | AC-6 (history), AC-7 | CRITICAL (quantities and prices, multi-row ledger writes, seeder order) | `database/seeders/DemoStockHistorySeeder.php` (new), `database/seeders/DatabaseSeeder.php`, `tests/Feature/Seeders/DemoStockHistorySeederTest.php` (new) | ~150 | 1 history + balance test (12 created, 3 restock, 3 price change; `StockLedgerVerifier` has no drift), 1 idempotency/skip test (second run writes nothing; a material that already has ledger rows is left alone) | BE | `feat(seed): demo stock history and a re-runnable demo-data step (S-08-c)` |
| 4 | S-08-d | AC-6, AC-7, AC-8 | NON-CRITICAL (tests + docs; no production code) | `tests/Feature/Seeders/FreshInstallDemoTest.php` (new), `README.md` (Step 6); tech-lead: `docs/SETUP.md` §3 | ~180 test lines + docs | 2 tests: the AC-6 figures end to end; re-run + F-37 at `DatabaseSeeder` level | BE (+ TL for SETUP.md) | `test(seed): a fresh install shows the demo workshop; setup guide (S-08-d)` |
| 5 | S-08-e | AC-5, AC-6, AC-7 | NON-CRITICAL (CI only) | `.github/workflows/tests.yml` | ~50 | none (the job is the evidence) | TL | `ci: golden path job and demo-data check on a fresh database (S-08-e)` |

`composer test`, `npx vp test` and (from row 1) `composer test:browser` stay green at every row. Rows 1 and 3 are independent; row 2 needs
row 1, row 4 needs row 3, row 5 needs rows 1–4.

## Frozen contract
No route, prop, Resource or `domain.ts` change. The app is tested as it is.

- **Gate command:** `composer test:browser` = `@php artisan config:clear --ansi` then `@php artisan test tests/Browser --compact`.
  Precondition: a current `npm run build` (the tech-lead runs gates in the order `composer test` → `npx vp test` → `npm run check` →
  `npm run types:check` → `npm run build` → `composer test:browser` → `composer test:pgsql`).
- **`composer test`, `composer test:pgsql`, `composer ci:check` and the CI `pgsql` job do not run `tests/Browser`.** The browser suite is not
  added to phpunit.xml's default run (either no `Browser` testsuite, or `defaultTestSuite="Unit,Feature,Arch"`; S-08-a picks whichever
  PHPUnit 12 honours and shows it).
- **`tests/Pest.php` browser block:** `pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Browser')`, **without** `withoutVite()`,
  plus a `beforeEach` that points `Vite::useHotFile()` at a path that never exists, so pages always load the built assets even while the human
  runs `composer dev`. Browser timeout raised to 10 s (`pest()->browser()->timeout(10000)`), Chromium only.
- **Step helper (AC-3):** `Tests\Browser\Support\GoldenPathStep::run(string $step, Closure $body): mixed` runs the body and, on any
  `Throwable`, rethrows an `AssertionFailedError` whose message starts with `Golden path failed at step "<step>": ` followed by the original
  message (original kept as `previous`). Step names follow the card: `sign in`, `materials: add Denim 12oz Indigo`, …,
  `product: unit cost`, `dashboard: Total sales`, `product: still 112 units`.
- **Test data in the golden path:** one factory user (verified, password `password`); materials and the product are typed in through the UI with
  the card's values. Fields the card doesn't name but the forms require (SKU, category, reorder level) use fixed test values chosen by the
  developer (not visible in any AC). The sale date is filled with the **server's** `today()` (UTC), not left to the browser's local date.
- **Money in assertions:** the app formats `TND 35.130` with a no-break space (U+00A0) after the code. Assertions use a tiny local helper
  (`tnd('35.130')`) that builds the exact displayed string. Values are the card's: `35.130`, `112`, `Metal shank button 17mm`, `53.870`,
  `60.53%`, `267.000`, `1 sale · last 30 days`, `3`, `2,440.000`, `0` "of 1 product", top product Denim Jacket with 3 units, then `112` again.
- **Demo-data entry point (S-40-friendly):** `php artisan db:seed` (= `DatabaseSeeder`) stays the one documented step. `DatabaseSeeder::run()`
  calls, in this order, each step idempotent on its own:
  1. the demo user: `User::query()->firstOrCreate(['email' => 'test@example.com'], ['name' => 'Test User', 'password' => 'password',
     'email_verified_at' => now()])` (no factory, so it doesn't depend on Faker; an existing user is left untouched);
  2. `DemoCatalogSeeder` (12 materials by SKU, then `DemoProductsSeeder`: only missing SKUs, never a soft-deleted one, F-37);
  3. `DemoStockHistorySeeder` (new; see below);
  4. `DemoSalesSeeder` (only when `sales` is empty).
  A docblock on `DatabaseSeeder` states this order and the rule "each demo step is idempotent; sales only when there are none", which is where
  S-40's optional step will hang (it gets its own plan; nothing is added for it now).
- **`DemoStockHistorySeeder`:** public const `HISTORY` keyed by material SKU. For each of the 12 demo materials that exists **and has no ledger
  row yet**, it appends through `App\Actions\Materials\AppendStockMovement` (the only ledger writer), inside one `DB::transaction` per material,
  with the demo user (`test@example.com`, else `null`) as actor. It is **not** called from `DemoCatalogSeeder` (existing tests seed the catalogue
  and then `Tests\Support\DemoHistory::seed()`; calling it there would give them two "Created" entries). Ledger values balance against the
  seeded stock and unit cost, so `StockLedgerVerifier` reports no drift. Proposed data (subject to PO question P-1):

  | Material | `created` (37 days ago): quantity @ unit price | `restock` (receipt date, reference) | `price_change` (same date) |
  |---|---|---|---|
  | Metal shank button 17mm | 400 @ 0.3200 | 6 days ago: +500 → 900 @ 0.3500, `PO-2291` | 0.3200 → 0.3500 |
  | Woven brand label | 250 @ 0.1000 | 14 days ago: +150 → 400 @ 0.1200 | 0.1000 → 0.1200 |
  | Denim 12oz Indigo | 150 @ 8.2000 | 18 days ago: +100 → 250 @ 8.5000, `PO-2281` | 8.2000 → 8.5000 |
  | the other 9 | their seeded stock @ their seeded unit cost | — | — |

## Approach and decisions
1. **Tooling: Pest 4 browser plugin (`pestphp/pest-plugin-browser` ^4.3.1) + Playwright, Chromium only.** It is already the documented choice
   (ARCHITECTURE, TEAM rubric), it runs inside the Pest process, so the golden path shares `RefreshDatabase` and factories with the rest of the
   suite and can never reach `laravel` (AC-4 comes for free with SQLite `:memory:`). Rejected: Playwright Test in TypeScript against
   `php artisan serve` (needs a second server process and its own database file with `migrate:fresh`: one wrong env var and it wipes a real
   database); Laravel Dusk (ChromeDriver version drift on Windows, no Inertia-aware waiting). Decision for the human: H-1.
2. **Windows is a go/no-go spike inside S-08-a.** Playwright supports Windows; the plugin's docs don't mention it. S-08-a's first task is the
   smoke test on this machine. If it can't run natively, the developer stops and reports; I re-plan (fallback: decision 1's rejected option,
   with a guard on the database name) and come back before any more work.
3. **Its own gate, not inside `composer test`** (decision for the human: H-2). `composer test` must keep working on a machine without Chromium,
   in the CI `pgsql` job and for developers who run focused checks; the browser test needs a fresh `npm run build`, which `composer test`
   deliberately doesn't (`withoutVite()`). Gates already run one at a time, so a seventh line in the gate table costs nothing extra.
4. **CI: a separate job `golden-path`** next to `ci` and `pgsql`: PHP 8.3, Node 24, `composer setup` on a SQLite file (as `ci` does, which also
   builds the frontend), `npx playwright install --with-deps chromium`, `composer test:browser`, and on failure upload
   `tests/Browser/Screenshots` and `tests/Browser/Traces` as an artifact (AC-3 on GitHub). Actions pinned by SHA like the rest of the file.
   A separate job gives the human a "golden-path" line in the Actions tab and runs in parallel (no longer wall time).
5. **CI also checks the documented demo step on a real fresh database** (cheap, no browser): in job `ci`, after `composer setup` migrated its
   throwaway SQLite file, run `php artisan db:seed --force` twice and `php artisan invory:stock:verify`. That is AC-6/AC-7 evidence on the exact
   command the guide gives, and it never touches PostgreSQL.
6. **Local verification of the demo step by the tech-lead** uses a throwaway SQLite file in the scratchpad, with env overrides
   (`DB_CONNECTION=sqlite`, `DB_DATABASE=<scratchpad>\demo.sqlite`, after `php artisan config:clear`), and first prints the connection's database
   name to prove it isn't `laravel`. No brief allows `migrate`, `db:seed` or `migrate:fresh` against `.env`'s database.
7. **Playwright browsers are a separate, documented one-time step** (`npx playwright install chromium`, about 150–180 MB into
   `%LOCALAPPDATA%\ms-playwright`), not part of `composer setup` (decision for the human: H-3). `npm install` only adds the small `playwright`
   package (pinned to the version the plugin's 4.x line is tested with; S-08-a states it).
8. **Who owns what.** S-08-a (dependencies, phpunit/Pest config) is backend: those files are PHP tooling and must land in one commit.
   S-08-b (the golden path) is frontend per ARCHITECTURE ("E2E smoke … frontend"): it knows the screens' labels and roles. The README edit rides
   with S-08-d (backend); `docs/SETUP.md` and the workflow are the tech-lead's.
9. **History seeder writes the ledger directly through `AppendStockMovement`**, not through `RestockMaterial`, because the catalogue seeder sets
   the final stock and cost: replaying real restocks would double them. Same pattern as `Tests\Support\DemoHistory` (which stays as it is: it
   is a richer test fixture used by `StockHistoryPropsTest` and `MaterialPreviewDataTest`).

## Execution order / waves
- **Wave 0 (main session, before execution):** the human's approval; a phase branch (e.g. `phase/4-release-readiness`) with the PO's pending
  doc changes committed or set aside, so the tree is clean. Docker up for the parity gate.
- **Wave 1 (parallel, disjoint files):** S-08-a (backend-developer #1) ∥ S-08-c (backend-developer #2).
- **Wave 2 (parallel):** S-08-b (frontend-developer, needs S-08-a) ∥ S-08-d (backend-developer, needs S-08-c). Tech-lead writes SETUP.md §3.
- **Wave 3 (tech-lead):** S-08-e workflow; full gates in order (incl. `composer test:browser` and `composer test:pgsql`); local demo-step check on a
  scratch SQLite file; one deliberate failure of the golden path to capture the AC-3 message and screenshot; delivery report.
- **After the human commits and pushes:** the human checks the Actions tab (AC-5's last condition). Any unrelated red there becomes its own item.

## Risks
| Risk | Likelihood / impact | Mitigation |
|---|---|---|
| The browser plugin doesn't run on native Windows (Playwright process launch, `HOME` not set, firewall prompt for the local server) | Medium / High | Go/no-go smoke as S-08-a's first task; stop rule; fallback plan in decision 2 |
| Flaky golden path: deferred props load after the frame; the in-process server is single-threaded and also serves the JS files | Medium / High (a flaky gate blocks every delivery) | Exact-value assertions with auto-wait, "no Loading… status" check per page, 10 s timeout, no sleeps; tech-lead runs it 3× in review before approving |
| Stale or missing `public/build`, or the human's `composer dev` (`public/hot`) at gate time | High / Medium | Gate order puts `npm run build` first; `Vite::useHotFile()` override in the browser block |
| Browser's local date ≠ server's UTC date around midnight → "date can't be in the future" | Low / Medium | The test fills the sale date with the server's `today()` |
| Money strings use a no-break space; a plain space never matches | High / Low | `tnd()` helper in the test |
| Seeding history from `DemoCatalogSeeder` would double "Created" rows in existing tests | Medium / Medium | History seeder is called only from `DatabaseSeeder` (frozen above) |
| Re-running the demo step on a database with the user's own changes resets the 12 materials without a `correction` entry, so their ledger drifts | Low / Low (the guide warns against it) | Proposed follow-up **F-55** (MINOR): reset writes a `correction` entry, or the step refuses on a non-empty database. Not in S-08 |
| Playwright npm version incompatible with the plugin | Low / Medium | S-08-a pins the version the plugin's 4.x CI uses and records it |
| Chromium download (~150–180 MB) on each new machine and each CI run (~30–60 s) | Certain / Low | Documented one-time step locally; CI may cache `~/.cache/ms-playwright` keyed on the Playwright version |
| AC-5 can't be closed by agents: the tech-lead can't push or see the private Actions tab | Certain / Low | The report says so; the human confirms after pushing |
| The PHP on this machine changed (Herd 8.4.26 behind the Laragon path) | Known / Low | Local gates run on 8.4; CI keeps 8.3 and 8.5. Noted in the report |

**Estimated cost:** one golden-path run ~15–30 s locally (Chromium start ~2 s, about a dozen page loads and form posts through the in-process
server); the `golden-path` CI job ~3–4 min wall (setup + Chromium install dominate), in parallel with `ci` and `pgsql`.

## Questions for the product-owner (behaviour, wording, scope)
- **P-1 Which three demo materials get the restock and price change, and does everyone else have only "Created"?** Options: (a) Metal shank
  button 17mm, Woven brand label, Denim 12oz Indigo with the numbers in the table above, the other 9 "Created" only (the card's literal
  reading); (b) the richer history the tests already use (restocks on 9 materials, 3 stock counts, 2 price changes); (c) another three.
  **Recommendation: (a)**: it matches Q1 exactly, gives the button (the bottleneck) a believable story, and the figures are easy to check.
- **P-2 Who "did" the demo history entries?** Options: (a) the demo user "Test User" [recommended: the history shows a name, like real
  entries]; (b) nobody (shown however the history shows an unknown user today).
- **P-3 "Created" date of the demo history.** The entries are dated relative to the day of seeding ("Created" 37 days ago, restocks 6–18 days
  ago), while the materials themselves are created on the day of seeding. Acceptable? **Recommendation: yes**, it makes the history readable;
  the alternative (everything dated today) shows a restock and a price change on the same day as "Created".
- **P-4 "The setup guide" = README Step 6** (SETUP.md §3 links to it). The step will list the sign-in details and the AC-6 figures verbatim, say
  it is safe to run again (nothing created twice, deleted demo products stay deleted), keep the warning that it resets the 12 demo materials,
  and drop "a second run fails". OK to use the card's AC-6 bullets as the guide's text? **Recommendation: yes.**

## Decisions for the human
- **H-1 Browser-test tooling.** (a) Pest 4 browser plugin + Playwright Chromium [recommended: in-process, shares the test database, already
  the documented choice]; (b) Playwright Test (TypeScript) against `php artisan serve` with its own database file; (c) Laravel Dusk.
- **H-2 Where the golden path runs.** (a) its own gate `composer test:browser` locally and its own CI job `golden-path` [recommended];
  (b) inside `composer test` (every run then needs Chromium and a fresh build, including the PostgreSQL parity job).
- **H-3 Chromium download (~150–180 MB per machine).** (a) a separate one-time step in the setup guide, for whoever runs the gates
  [recommended]; (b) automatic inside `composer setup` for everyone.

## Proposed follow-ups (added to TECH-DEBT.md at delivery, not now)
- **F-55** (MINOR): re-running the demo step resets the 12 demo materials without a ledger entry (drift). Cost of delay: low while the guide
  says "new, empty database only".

## Progress log
- 2026-10-14: plan written (planning-only). Nothing dispatched.
- 2026-10-14: approved. Intake: HEAD `6e776f0` clean, `composer test` green (2076 tests, 24 skipped). Wave 1 dispatched: S-08-a (BE #1) ∥ S-08-c (BE #2).
- 2026-10-14: Wave 1 reviewed, both APPROVED in round 1. S-08-a: plugin v4.3.1, `playwright` 1.59.1 (exact; the plugin's floor), Chromium
  headless shell 147 (111.5 MiB); Windows GO; browser suite excluded from `composer test` by leaving it out of phpunit.xml's suites.
  **New fact:** once installed, the plugin's `terminate()` opens a socket on **every** Pest run, so `ext-sockets` is required for
  `composer test` and `test:pgsql` too. Laragon PHP 8.3.35 has it commented out; Herd PHP 8.4 has it but `memory_limit=128M` (suite peaks
  ~182 MB). Gates run on Laragon PHP with a session-only `PHP_INI_SCAN_DIR` holding `extension=sockets` (no system change). S-08-c: 18 ledger
  rows, `DatabaseSeeder` re-runnable. Gates on the wave-1 tree: composer test 2079 (24 skipped) · vp test 1134 · check · types · build ·
  test:browser 1 (≈4 s) · test:pgsql 2079 — all green. Wave 2 dispatched: S-08-b (FE) ∥ S-08-d (BE).
- 2026-10-14: Wave 2 cut off by an API session limit before writing anything (no GoldenPathTest, no FreshInstallDemoTest, README untouched).
  Wave 1 still on disk and approved. Wave 2 re-dispatched with the same briefs.
- 2026-10-14: Wave 2 re-run. S-08-d APPROVED round 1. S-08-b CHANGES REQUESTED round 1 (MAJOR: a non-assertion failure kept no screenshot,
  AC-3) → fixed by the FE developer (`GoldenPathStep::run(..., ?object $page)` takes the screenshot itself) → APPROVED round 2. S-08-e and
  SETUP.md written by the tech-lead. Demo step verified twice on a scratch SQLite file (12/7/60/18 rows, 1 user, ledger balances).
  Host restarted mid-delivery: Docker containers exited (parity not re-run), and Laragon PHP 8.3 became ~6× slower per HTTP test (F-57), so the
  final gates ran on Herd PHP 8.4.26 with a session-only `memory_limit=1G`. Follow-ups F-55, F-56, F-57 added to TECH-DEBT.md.
- 2026-10-14 (acceptance follow-up): click-through recipe on a throwaway SQLite (`.env.demo` + `APP_ENV=demo`) proven on a scratch copy:
  `artisan serve` passes only `APP_ENV` to its server, which then loads `.env.demo` (signed in as the demo user, 12 materials served).
  `.env.demo` git-ignored (commit 1); `database/demo.sqlite` was already covered by `database/.gitignore`.
