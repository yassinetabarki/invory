# Dev Docker = PostgreSQL + Mailpit only (remove `laravel.test` and Sail)

> Owner: **tech-lead** · Story: **S-29** ([BACKLOG](../BACKLOG.md)) · Status: **approved 2026-10-14 (STATUS D-2), in execution.** The human answered Q1–Q4 on 2026-10-14: Q1 (a) Docker Mailpit on 1026/8026 · Q2 yes, remove `laravel/sail` and `sail.cmd` · Q3 the main session applies DK-d · Q4 (a) skip the demo seed on the dev DB.
> ID note (2026-10-14): the follow-ups first numbered F-41…F-43 collided with existing items and are now **F-51…F-53** ([TECH-DEBT.md](../TECH-DEBT.md)). DK-c writes follow-ups to TECH-DEBT.md; backlog statuses belong to the product-owner.
> Decision source: human, 2026-10-13: remove the `laravel.test` service from `compose.yaml` entirely. Docker keeps only `pgsql` and `mailpit`;
> the app runs natively through Laragon. This goes further than [ADR-0007](../adr/0007-dev-runtime-native-serving.md), which kept
> `laravel.test` behind the `app` profile for the PostgreSQL parity suite and PHP 8.5 checks.
> Related: S-18 (dev runtime), F-25 (CLAUDE.md and agent definitions out of date), F-08 (coverage command), S-04 pending dev-DB step.

## Outcome
- `docker compose up -d` starts `invory-pgsql-1` and `invory-mailpit-1`, and nothing else exists in `compose.yaml`.
- The PostgreSQL parity suite runs **natively** (Laragon PHP 8.3 + `pdo_pgsql`) against Docker PostgreSQL on `127.0.0.1:5432`, database
  `testing`, with one command: `composer test:pgsql`. It can't reach the dev database `laravel` by mistake.
- PHP 8.5 is covered only by CI (the `pgsql` job matrix is `['8.3', '8.5']`, verified in `.github/workflows/tests.yml`).
- `laravel/sail` and `sail.cmd` are gone (pending Q2). No doc, agent definition or skill tells anyone to use `laravel.test`, `--profile app` or `.\sail`.
- Fresh setup creates `public/storage` with `php artisan storage:link` (NTFS junction on Windows), and the test suite checks it.

**Out of scope:** production image, Mailpit image pinning, historical plans in `docs/plans/` (left as written), `.claude/worktrees/`.

## Facts verified at intake (2026-10-14, read-only)
| Fact | Evidence |
|---|---|
| Laragon PHP on PATH prefix `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64` reports **PHP 8.3.35**, OPcache loaded, `pdo_pgsql`, `pgsql`, `pdo_sqlite` enabled | `php -v`, `php -m` |
| Containers: `invory-pgsql-1` Up (healthy) 5432, `invory-mailpit-1` Up (healthy) **1026→1025, 8026→8025**, `invory-laravel.test-1` Exited (0). Image `sail-8.5/app` (3.08 GB). Volume `invory_sail-pgsql` | `docker ps -a`, `docker image ls`, `docker volume ls` |
| PostgreSQL has databases `laravel` (dev) and `testing` | `psql -Atc "select datname from pg_database"` |
| Ports: **1025/8025 held by Laragon's `mailpit.exe`**; 1026/8026/5432 by `wslrelay` (Docker); **8000 by `php.exe` (artisan serve)**; 80 by Laragon `httpd.exe` | `Get-NetTCPConnection` |
| Apache runs (port 80) but has not bound the `Listen 8000` vhost: `php artisan serve` holds 8000. `composer dev` → `php artisan dev` starts `artisan serve`, which is likely how it got there | `composer.json` scripts, `DevCommand` |
| `public/storage` is an NTFS **Junction** to `storage/app/public` (gitignored by `/public/storage`) | `(Get-Item public\storage).LinkType` |
| Dev DB: **all 11 migrations have run** (the 3 S-04 migrations are batch 5; later batches 6–7 too). Rows: 2 materials (SKUs `SKD`, `DROS`), 2 products, 0 sales, 5 users. **None of the 12 demo material SKUs exist** | `php artisan migrate:status`, `psql` counts |
| `.env` (git-ignored, not edited): `DB_HOST=127.0.0.1` but **`MAIL_HOST=mailpit`, `MAIL_PORT=1025`, `FORWARD_MAILPIT_PORT=1025`, `FORWARD_MAILPIT_DASHBOARD_PORT=8025`**, `APP_PORT=8000`, `WWWUSER`/`WWWGROUP` | `grep` of key names only |
| `.env.example` has an **uncommitted** change switching `MAIL_PORT`/`FORWARD_MAILPIT_PORT`/`FORWARD_MAILPIT_DASHBOARD_PORT` from 1026/8026 to 1025/8025, while its own comment (line 54) still says 1026/8026 | `git diff .env.example` |
| `tests/Feature/Photos/StorageSetupTest.php` (commit `57e215b`, 2026-10-13) is skipped on Windows and tells people to add the container mount; on Linux CI it runs, but neither CI job creates `public/storage` | file, `.github/workflows/tests.yml` |
| PHPUnit `<env>` in `phpunit.xml` doesn't override real environment variables (CI relies on this for its pgsql job); Laravel's Dotenv is immutable, so real env vars also win over `.env`. Composer 2.10.2 supports `@putenv` in scripts | `phpunit.xml`, CI comments, `composer.phar` |
| The only uses of `laravel/sail` left: the `laravel.test` build context, the pgsql init mount `./vendor/laravel/sail/database/pgsql/create-testing-database.sql`, `sail.cmd`, and `scripts/bootstrap.ps1` (one-time scaffold script). No app code references `Laravel\Sail` | `grep` |

## Findings that change the human's steps (read before the runbook)
1. **Mail is broken right now and `docker compose up -d` would fail.** `.env` sends mail to host `mailpit`, which doesn't resolve from native PHP,
   so Fortify mails (verification, password reset) fail. Worse, `.env` now asks Compose to publish Mailpit on **1025/8025, which Laragon's own
   Mailpit holds**: the next `docker compose up -d` will recreate `invory-mailpit-1` and fail with "port is already allocated". The `.env`
   fix in runbook step H-1 must come first. See Q1.
2. **CI is probably red since `57e215b`.** `StorageSetupTest` asserts `public/storage` exists, runs on Linux, and no CI step runs
   `storage:link`. `gh` isn't installed here, so I couldn't confirm. DK-b fixes it either way.
3. **Port 8000 is served by `php artisan serve`, not Apache.** On Windows it is single-process (`PHP_CLI_SERVER_WORKERS` unsupported), so the
   deferred-prop XHRs of RL-01/RL-02 queue behind each other and S-18's p95 target can't be measured. Stop it and reload Apache (H-3).
4. **The S-04 dev seed would create 7 empty products.** `DemoProductsSeeder` skips composition lines whose material SKU is missing, and the
   dev DB has none of the 12 demo SKUs. Being idempotent by SKU, a later run won't add the lines. See Q4. The 3 S-04 migrations are already applied.

## Decisions taken by the tech-lead (not product decisions)
| # | Decision | Why |
|---|---|---|
| D-1 | New **ADR-0012** "Docker runs only PostgreSQL and Mailpit; Sail removed". It supersedes ADR-0005's "Runtime = Sail" and ADR-0007's "keep `laravel.test` behind the `app` profile". ADR-0005 and ADR-0007 get a status line pointing to it; their bodies stay as history | ADRs are immutable records; one new ADR is cleaner than a third amendment |
| D-2 | Keep the volume name **`sail-pgsql`** (project volume `invory_sail-pgsql`) | Renaming it gives an empty dev database. A cosmetic name isn't worth a data migration |
| D-3 | Drop the custom `sail` network and `depends_on`. The two services don't talk to each other; Compose's default network is enough | Only `laravel.test` needed DNS to `pgsql`/`mailpit`. Changing networks recreates the containers once; data lives in the volume |
| D-4 | Copy Sail's 2-line init SQL into **`docker/pgsql/create-testing-database.sql`** and mount that | The `testing` database stays for the parity suite and a fresh clone; no dependency on `vendor/` for Docker |
| D-5 | Parity suite = **`composer test:pgsql`**: `config:clear`, `@putenv DB_CONNECTION=pgsql`, `@putenv DB_DATABASE=testing`, then `php artisan test --compact`. Host, port, user and password come from `.env` (`127.0.0.1:5432`, `sail`). Works the same from PowerShell, Git Bash and agents | One command for humans, agents and the review skill; no session-polluting `$env:` assignments |
| D-6 | **Dev-DB guard** in the test bootstrap: if the default connection is `pgsql` and the database name isn't `testing`, abort the run before any `RefreshDatabase` with a clear message | Natively, a cached config or a typo would point RefreshDatabase at the dev DB `laravel` and wipe it. CI already uses `testing`, so the guard costs nothing |
| D-7 | Compose and `.env.example` defaults for Mailpit become **1026 (SMTP) / 8026 (dashboard)**, and `.env.example` keeps `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1026`. The uncommitted 1025/8025 change is reverted by the BE developer in DK-a **unless Q1 says otherwise** | Laragon ships its own Mailpit on 1025/8025, and Laragon is the project's documented dev setup, so every developer collides on 1025/8025 |
| D-8 | Keep `StorageSetupTest`, remove its Windows skip and its container wording, and make setup create the link: `composer setup` runs `php artisan storage:link`, and the CI `pgsql` job gets a `storage:link` step | The test caught exactly the 403 the main session just fixed (an empty `public/storage` folder passes `is_dir` but fails the file round-trip). A junction works natively |
| D-9 | SETUP.md documents the vhost **without** `Alias /storage`. The human may drop the Alias from `invory.conf` (H-3), then check that a photo URL returns 200 under Apache; if Apache returns 403 through the junction, keep the Alias and tell me | Dev should behave like production (`storage:link`). The Alias would hide a missing link |
| D-10 | SETUP.md steers to **Apache on :8000 + `npm run dev`**, and says not to use `composer dev` / `php artisan serve` for this project. Overriding `composer dev` is a follow-up (F-51) | Single-process `artisan serve` serialises the deferred-prop requests |
| D-11 | `.env.example` drops `APP_PORT`, `WWWUSER`, `WWWGROUP` (only `laravel.test` used them). It keeps `FORWARD_DB_PORT` (commented hint), `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT` and the `DB_*` keys the `pgsql` service reads | Dead keys mislead |
| D-12 | PHP 8.5 coverage = CI only. If an 8.5-only failure shows up, reproduce it by adding PHP 8.5 to Laragon (*Menu › PHP › Version*), not with a container | Matches the human's decision; CI matrix already has 8.5 |
| D-13 | Native coverage (F-08) needs pcov or Xdebug in Laragon's PHP, which is machine config (human). F-08 is rewritten accordingly; no coverage command runs in Docker any more | `laravel.test` was the only place with Xdebug |

## Target `compose.yaml` (frozen for DK-a)
```yaml
# Dev services only: the app runs natively through Laragon (ADR-0007, ADR-0012).
services:
    pgsql:
        image: 'postgres:18-alpine'
        ports:
            - '${FORWARD_DB_PORT:-5432}:5432'
        environment:
            PGPASSWORD: '${DB_PASSWORD:-secret}'
            POSTGRES_DB: '${DB_DATABASE}'
            POSTGRES_USER: '${DB_USERNAME}'
            POSTGRES_PASSWORD: '${DB_PASSWORD:-secret}'
        volumes:
            - 'sail-pgsql:/var/lib/postgresql'
            # Creates the `testing` database for the PostgreSQL parity suite (`composer test:pgsql`) on first start.
            - './docker/pgsql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql'
        healthcheck:
            test: ['CMD', 'pg_isready', '-q', '-d', '${DB_DATABASE}', '-U', '${DB_USERNAME}']
            retries: 3
            timeout: 5s
    mailpit:
        image: 'axllent/mailpit:latest'
        ports:
            # Laragon's own Mailpit holds 1025/8025, so the project defaults to 1026/8026.
            - '${FORWARD_MAILPIT_PORT:-1026}:1025'
            - '${FORWARD_MAILPIT_DASHBOARD_PORT:-8026}:8025'
volumes:
    # Name kept from Sail: renaming it would start an empty dev database.
    sail-pgsql:
        driver: local
```
(If Q1 picks Laragon's Mailpit, the `mailpit` service is removed too and the mail keys point at 1025.)

## Slicing table

| # | Subtask id | Lane | Files (exact paths) | Line budget | Tests allowed | Owner | Commit label |
|---|---|---|---|---|---|---|---|
| 1 | DK-a | CRITICAL (dev-DB data-loss guard, dependency removal, parity gate replacement) | `compose.yaml`, `docker/pgsql/create-testing-database.sql` (new), `.env.example`, `sail.cmd` (delete), `composer.json` (remove `laravel/sail`; add `test:pgsql`), `composer.lock`, `tests/TestCase.php` (guard, D-6), `scripts/perf-probe.php` (comment lines 14–18 only) | ~80 prod/config | 0 new Pest tests. Verified by commands: `docker compose config --quiet`; `docker compose config --services` = `mailpit`, `pgsql`; `composer test:pgsql` green; guard probe aborts (see Gates) | BE | `chore(dev): Docker runs only PostgreSQL and Mailpit; parity suite runs natively (ADR-0012)` |
| 2 | DK-b | NON-CRITICAL (test infra, no business rule) | `tests/Feature/Photos/StorageSetupTest.php`, `composer.json` (`setup` gains `@php artisan storage:link`), `.github/workflows/tests.yml` (pgsql job: `php artisan storage:link` step; line-56 comment) | ~40 | 0 new; the 2 existing tests run on Windows and Linux | BE (test + composer.json) + TL (`tests.yml`) | `test(photos): check the public storage link natively and in CI` |
| 3 | DK-c | NON-CRITICAL (docs) | `docs/adr/0012-dev-docker-db-and-mail-only.md` (new), `docs/adr/0005-dev-environment-laragon-sail.md` (status line), `docs/adr/0007-dev-runtime-native-serving.md` (status line), `docs/SETUP.md`, `docs/ARCHITECTURE.md` (lines 20, 29, 30, 387), `README.md` (Docker step, storage:link, mail ports, Apache), `docs/BACKLOG.md` (S-18, F-25, F-08, new follow-ups) | ~250 doc lines | — | TL | `docs(dev): Docker for PostgreSQL and Mailpit only; native parity suite (ADR-0012)` |
| 4 | DK-d | NON-CRITICAL (agent instructions) | `CLAUDE.md` (Stack line 14, Environment), `.claude/agents/backend-developer.md:81`, `.claude/agents/tech-lead.md:155-156`, `.claude/skills/invory-code-review/SKILL.md:26-30,111`, `.claude/skills/invory-laravel-backend/SKILL.md:310-316`, `.claude/skills/invory-react-frontend/SKILL.md:111`, `.claude/agent-memory/tech-lead/project_review_gotchas.md` (TL) | ~60 | — | **Main session** (F-25 precedent; my definition lets me write only `docs/**`, `.github/**` and my memory) + TL (memory). Exact text in the appendix. See Q3 | `docs(agents): native parity command; no Sail (F-25)` |

Notes:
- **Why DK-a is one lump:** removing `laravel.test` deletes the only parity runner, so the native runner and its guard must land in the
  same commit, or history has a commit with no parity gate. The compose init mount points into `vendor/laravel/sail`, so removing the package
  and repointing the mount also go together.
- **Who edits `compose.yaml`:** the backend-developer (it owns `config/`, `database/` and the runtime side; `compose.yaml` is runtime
  config). The tech-lead doesn't write it (hard rule: no application/config files).
- `composer.json` has hunks in DK-a (remove `laravel/sail`, add `test:pgsql`) and DK-b (`setup` + `storage:link`). The same BE developer does
  DK-a then DK-b in one dispatch; the main session stages DK-b's hunk with `git add -p composer.json`.
- `scripts/bootstrap.ps1` is left alone (one-time scaffold script that ran `sail:install`; it won't be re-run). Follow-up F-52.
- `bootstrap/cache/packages.php` and `services.php` regenerate on `composer install` (git-ignored). Nothing to commit.
- Historical plans (`docs/plans/phase-*.md`, `perf-dashboard-endpoint.md`, …) keep their old commands as history.

### Waves
```
Wave 1   DK-a (BE)  ∥  DK-c (TL docs, written against the frozen contract above)
Wave 2   DK-b (BE + TL tests.yml)        ─► TL full gates + parity ─► DK-d (main session, appendix text)
Then     human runbook H-1 … H-6
```

## DK-a brief contract (frozen)
- `compose.yaml`: exactly the target above.
- `docker/pgsql/create-testing-database.sql`: same content as `vendor/laravel/sail/database/pgsql/create-testing-database.sql`
  (`SELECT 'CREATE DATABASE testing' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'testing')\gexec`). LF line endings.
- `composer.json` `scripts.test:pgsql`:
  ```json
  "test:pgsql": [
      "@php artisan config:clear --ansi",
      "@putenv DB_CONNECTION=pgsql",
      "@putenv DB_DATABASE=testing",
      "@php artisan test --compact"
  ]
  ```
  plus a `scripts-descriptions` entry ("PostgreSQL parity suite: Pest against the Docker `testing` database (127.0.0.1:5432)").
- `composer remove --dev laravel/sail --minimal-changes`: the lock diff removes `laravel/sail` (and only packages nothing else needs); no version bumps.
- Guard (D-6) in `tests/TestCase.php`, before the app's database is touched (e.g. in `createApplication()` or `setUp()` before `parent::setUp()`
  returns to traits; BE verifies where `RefreshDatabase` runs in Laravel 13 and puts the check ahead of it):
  connection driver `pgsql` and database ≠ `testing` → `throw new RuntimeException('PostgreSQL tests must run on the "testing" database, not "<name>". Use composer test:pgsql.')`.
  SQLite in-memory runs are untouched.
- `.env.example`: D-7 and D-11; comments say "app served natively by Laragon Apache on :8000 (ADR-0007, ADR-0012)".
- `sail.cmd`: deleted.

## Gates
| Row | Gates (run by the tech-lead) |
|---|---|
| DK-a | `composer validate --strict` · `composer test` · `npx vp test` · `npm run check` · `npm run types:check` · `npm run build` · `docker compose config --quiet` and `docker compose config --services` (read-only) · **`composer test:pgsql`** (same pass count as the SQLite run, minus SQLite-only skips) · guard probe, in a fresh PowerShell process: `$env:DB_CONNECTION='pgsql'; $env:DB_DATABASE='invory_guard_probe'; php artisan test --compact` must stop with the guard message. The probe uses a database that doesn't exist, so a broken guard still can't touch `laravel` |
| DK-b | `composer test` (StorageSetupTest now **runs** on Windows: 2 passed) · `composer test:pgsql` · YAML lint of `tests.yml` by reading; CI result on the next push |
| DK-c, DK-d | a search for `laravel.test`, `--profile app`, `.\sail` and `sail.cmd` outside `docs/plans/`, `.claude/worktrees/` and `vendor/` finds only the ADR-0005/0007 history lines and ADR-0012 · links in SETUP/README resolve |

Parity runs share the `testing` database: never two at once (TL memory). Agents run `composer test:pgsql` only when Docker is up; otherwise
"parity not run (Docker down)".

## Human runbook (after the commits; the order matters)
H-1. **Fix `.env`** (git-ignored; agents never edit it). With Q1 = Docker Mailpit:
```
MAIL_HOST=127.0.0.1
MAIL_PORT=1026
FORWARD_MAILPIT_PORT=1026
FORWARD_MAILPIT_DASHBOARD_PORT=8026
```
Delete `APP_PORT`, `WWWUSER`, `WWWGROUP`. Then `php artisan config:clear`.

H-2. **Recreate the Docker services and drop the stopped app container** (repo root; touches only the `invory` project):
```powershell
docker compose up -d --remove-orphans     # recreates invory-pgsql-1 / invory-mailpit-1 on the new network; removes invory-laravel.test-1
docker compose ps                         # pgsql (5432) and mailpit (1026, 8026), both healthy
docker image rm sail-8.5/app              # optional, frees ~3 GB; only this project's image
docker network ls --filter name=invory    # optional: if invory_sail is still listed, `docker network rm invory_sail`
```
Never `docker compose down -v` (deletes the dev database volume `invory_sail-pgsql`) and never touch `odysseus-*`.

H-3. **Serve with Apache on :8000.** Stop `php artisan serve` / `composer dev` (Ctrl+C in its terminal). Optionally remove the
`Alias /storage …` line from `C:\laragon\etc\apache2\sites-enabled\invory.conf` (D-9). Laragon › Apache › Reload. Check:
`curl.exe -sI http://localhost:8000/login` shows `Server: Apache…`, and a material photo URL returns 200.

H-4. **Check mail and the parity suite:** request a password reset → it shows in http://localhost:8026. Run `composer test:pgsql` once.

H-5. **S-18 sign-off:** run SETUP.md §4.3 (curl with 3 s gaps, `php scripts/perf-probe.php …`); p95 ≤ 150 ms closes S-18.

H-6. **S-04 dev seed** (pending; now native): the migrations are already applied, so only
`php artisan db:seed --class=DemoProductsSeeder` remains. Never `DemoCatalogSeeder`, never `migrate:fresh`. **Hold until Q4 is answered.**

## Risks
| Risk | Mitigation |
|---|---|
| Parity suite hits the dev DB `laravel` (cached config, typo, wrong env) and RefreshDatabase wipes it | `config:clear` first in `test:pgsql`; guard D-6; probe against a non-existent DB name |
| `docker compose up -d` fails on 1025/8025 because `.env` still says 1025 | H-1 before H-2; compose defaults 1026/8026; finding 1 called out in the report |
| Renaming the volume or `down -v` loses dev data | D-2 keeps `sail-pgsql`; runbook warns against `-v` |
| Existing volume already initialised: the new init mount never runs on this machine | `testing` already exists (verified); the mount matters for fresh clones only |
| PHP 8.5-only regressions found late (CI only) | CI matrix 8.3 + 8.5 on every push to `main`/`phase/**` and PRs; D-12 local reproduction path |
| Windows-only behaviour (path case, junctions) escapes because no Linux run is local any more | CI runs both jobs on Linux; ARCHITECTURE "write portable code" stays |
| Apache refuses to follow the junction (403 on photos) | D-9 fallback: keep the Alias |
| `composer remove` bumps unrelated packages | `--minimal-changes`; TL reviews the lock diff |
| Docs drift (an agent still runs `laravel.test`) | DK-d sweep + the `rg` gate; F-25 closed only when it's clean |

## Open questions for the human
- **Q1 — Which Mailpit?** (a) Docker Mailpit on **1026/8026** (recommended; matches your decision, D-7, and reverts the uncommitted
  `.env.example` change to 1025/8025), or (b) Laragon's own Mailpit on 1025/8025, then the `mailpit` service leaves `compose.yaml` too and
  `.env.example` keeps 1025/8025. Who made the uncommitted `.env.example` change, and was it meant to switch to Laragon's Mailpit?
- **Q2 — Remove `laravel/sail` and `sail.cmd`?** Recommended: yes. Nothing uses them once `laravel.test` is gone, and keeping them invites
  agents to run `.\sail`. Keeping them dormant only means DK-a doesn't touch `composer.json`/`composer.lock`/`sail.cmd`.
- **Q3 — Who edits `CLAUDE.md`, `.claude/agents/*` and `.claude/skills/invory-*`?** Default: the main session applies the appendix text (F-25
  was assigned to it). Alternative: you authorise the tech-lead to edit those files for this story.
- **Q4 — S-04 demo seed on this dev DB?** `DemoProductsSeeder` alone creates the 7 demo products **with empty compositions**, because none of
  the 12 demo materials exist here, and later runs won't fill them (idempotent by SKU). Options: (a) skip demo seeding on this database;
  (b) run `DemoCatalogSeeder` first, which only creates the 12 missing demo materials here (it upserts by SKU and none exist yet), then
  `DemoProductsSeeder` (this lifts D-1 "never DemoCatalogSeeder" for this one run); (c) run it anyway and accept empty compositions.

## Follow-ups to add to the backlog (DK-c)
- **F-51 (dev tooling)**: `composer dev` / `php artisan dev` starts `artisan serve` on :8000, which clashes with Apache. Override the script
  to run only Vite (and the queue listener if needed).
- **F-52 (repo)**: `scripts/bootstrap.ps1` still runs `sail:install` and prints `.\sail` steps. Retire it or mark it historical.
- **F-08 rewritten**: native coverage needs pcov or Xdebug in Laragon's PHP (human, machine config); the Docker command is gone.
- **F-53 (NIT)**: pin `axllent/mailpit` to a version instead of `latest`.
- **F-25** closes with DK-d. **S-18** acceptance line changes from "the parity suite still runs in the container" to "the parity suite runs
  natively against Docker PostgreSQL (`composer test:pgsql`)"; status → done after H-5.

## Appendix — replacement text for DK-d

**`CLAUDE.md`, Stack (line 14):**
> Dev environment: **Laragon** serves the app natively (Apache :8000, PHP 8.3 + OPcache) and provides Composer for tooling and agents.
> **Docker Compose** runs only **PostgreSQL** and **Mailpit** (ADR-0007, ADR-0012).

**`CLAUDE.md`, Environment bullets (replace lines 65–70):**
> - Code tooling and **database commands** run natively: `composer test` (Pest on SQLite in-memory), `npm …`, `npx vp test`,
>   `php artisan migrate`, `php artisan tinker` (`.env` points at `127.0.0.1`).
> - Docker runs only `pgsql` and `mailpit`: `docker compose up -d`. If Docker isn't running, say so; don't start or install anything.
> - PostgreSQL parity suite: `composer test:pgsql` (database `testing`; never two runs at once). PHP 8.5 is tested in CI only.
> - Local ports (`.env`, git-ignored): app http://localhost:8000 (Laragon Apache) · Mailpit SMTP 1026, UI http://localhost:8026 ·
>   PostgreSQL 127.0.0.1:5432 · Vite `npm run dev` (native, 5174). Laragon's own Mailpit holds 1025/8025.
>   Other Docker projects (`odysseus-*`) are running. Never stop or remove containers that aren't `invory-*`.

**`.claude/agents/backend-developer.md:81`:**
> DB-touching artisan commands run natively (`php artisan …`, `.env` → 127.0.0.1). Never run `migrate:fresh` or seeders on the dev DB `laravel`
> unless the brief says so. PostgreSQL parity: `composer test:pgsql` (Docker up). If PHP or Docker is unavailable, report a blocker.

**`.claude/agents/tech-lead.md:155-156`:**
> - The app runs natively (Laragon Apache :8000). Docker runs only PostgreSQL and Mailpit: `docker compose up -d`.
> - PostgreSQL parity: `composer test:pgsql` (database `testing` on 127.0.0.1:5432). PHP 8.5 runs in CI only.

**`.claude/skills/invory-code-review/SKILL.md:26-30`:** the parity block becomes `composer test:pgsql`; keep "not run (Docker down)".

**`.claude/skills/invory-laravel-backend/SKILL.md:310-316`:** "Natively (Docker running for PostgreSQL):" then `php artisan migrate`,
`php artisan tinker`, `composer test:pgsql   # PostgreSQL parity`. Drop `.\sail artisan migrate:fresh --seed`.

**`.claude/skills/invory-react-frontend/SKILL.md:111`:** `npm run dev   # Vite, native; the app is served by Laragon Apache at http://localhost:8000`.

**TL memory (`project_review_gotchas.md`)**, written by the tech-lead in DK-d: replace the "After ADR-0007" entry with the native parity
command and the dev-DB guard; drop the 9p/`laravel.test` profiling entries (history now lives in ADR-0007).
