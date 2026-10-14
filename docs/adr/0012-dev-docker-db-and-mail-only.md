# ADR-0012 — Dev: Docker runs only PostgreSQL and Mailpit; the app runs on PHP's built-in server; Sail removed

- **Status:** Accepted · 2026-10-14 (the human's decisions of 2026-10-13 and 2026-10-14, STATUS D-2; delivered by S-29,
  [plan](../plans/dev-docker-db-mail-only-plan.md))
- **Supersedes:** [ADR-0005](0005-dev-environment-laragon-sail.md) "Runtime = Sail" and [ADR-0007](0007-dev-runtime-native-serving.md)
  "serve through Laragon's Apache" and "keep `laravel.test` behind the `app` profile for parity runs"
- **Keeps:** ADR-0007's reason for leaving the container (the WSL2 9p bind mount cost ~2 s per request) and ADR-0002 (PostgreSQL in dev and production)

## Context
ADR-0007 moved serving out of the Sail container to Laragon's Apache and kept the `laravel.test` container only for the PostgreSQL parity
suite and PHP 8.5 checks. In practice (2026-10-14):
- The app on http://localhost:8000 is served by `php artisan serve` (started by `composer dev`), not by Apache. The Laragon application isn't
  used any more; only its PHP 8.3 binary is on the PATH. Any native PHP ≥ 8.3 with the listed extensions works.
- The `laravel.test` container was stopped, needed `laravel/sail`, a 3 GB image and WSL integration (`.\sail`), and was the only way to run
  the parity suite locally.
- Laragon's own Mailpit holds 1025/8025, so the project's Mailpit already ran on 1026/8026.

## Decision
- **Docker Compose runs only `pgsql` (PostgreSQL 18) and `mailpit`.** `compose.yaml` has no app service, no custom network, no profiles.
  The volume keeps its name `sail-pgsql` (renaming it would start an empty dev database). Mailpit defaults to **1026 (SMTP) / 8026 (inbox)**.
  The `testing` database is created on a fresh volume by `docker/pgsql/create-testing-database.sql`.
- **The app runs natively** with PHP's built-in server: `composer dev` (`php artisan dev`: `artisan serve` on :8000, the queue listener and
  Vite), or `php artisan serve --port=8000` plus `npm run dev`. `.env` points at `127.0.0.1` for the database (5432) and mail (1026).
  `public/storage` is created by `php artisan storage:link` (an NTFS junction on Windows; `composer setup` runs it).
- **`laravel/sail` and `sail.cmd` are removed.**
- **PostgreSQL parity suite = `composer test:pgsql`**, natively against Docker PostgreSQL on 127.0.0.1:5432, database `testing`
  (`config:clear`, then `DB_CONNECTION=pgsql` and `DB_DATABASE=testing` as real environment variables, which win over `phpunit.xml` and `.env`).
  A guard in `tests/TestCase.php` stops any PostgreSQL test run whose database isn't `testing`, before `RefreshDatabase` can touch the dev
  database `laravel`. Two parity runs must never overlap (they share `testing`).
- **PHP 8.5 is covered by CI only** (the `pgsql` job matrix is 8.3 and 8.5). To reproduce an 8.5-only failure locally, install PHP 8.5
  natively; there is no container for it any more.
- **Coverage** needs pcov or Xdebug in the native PHP (machine configuration, done by the human). No coverage command runs in Docker.

## Consequences
- ✅ One small Docker footprint (two containers, no 3 GB image), no WSL integration needed, one parity command for humans, agents and reviews.
- ✅ The dev database can't be wiped by a misconfigured parity run (guard + `config:clear`).
- ⚠️ **PHP's built-in server handles one request at a time on Windows** (`PHP_CLI_SERVER_WORKERS` isn't supported there). The deferred
  panels of the dashboard and product pages (RL-01, RL-02) load one after another. Measured 2026-10-14 on `/login`: ~0.31 s per request with
  OPcache off for the CLI (the machine's current `php.ini`), ~0.06 s with `opcache.enable_cli=1`; four parallel requests finish at
  0.06 / 0.17 / 0.27 / 0.37 s. The S-18 target (p95 ≤ 150 ms) depends on that choice; it is a product question in the S-29 delivery report.
- ⚠️ No local Linux run any more: Windows-only behaviour (path case, junctions vs symlinks) is caught only by CI, which runs both jobs on Linux.
- ⚠️ Production still needs its own image or platform (Laravel Cloud, Forge, or a FrankenPHP / php-fpm + nginx image); this ADR is dev only.
