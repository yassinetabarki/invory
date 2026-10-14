# ADR-0007 — Dev runtime: serve the app natively (Laragon), keep Docker for PostgreSQL and Mailpit

- **Status:** Accepted · 2026-10-08 (the human chose option 1, decision Q1 of [phase-3 plan](../plans/phase-3-performance-spa-data.md); delivered by S-18)
- **Amends:** [ADR-0005](0005-dev-environment-laragon-sail.md) ("Runtime = Sail"; its consequence "revisit if file I/O becomes painful")
- **Superseded in part by [ADR-0012](0012-dev-docker-db-and-mail-only.md) (2026-10-14):** the app is served by `php artisan serve` (`composer dev`),
  not Laragon's Apache; `laravel.test` and Sail are removed; the parity suite runs natively (`composer test:pgsql`). Leaving the container
  (the 9p measurements below) still holds. The text below is history.

## Context
The human reports slow page loads. Measurements on 2026-10-08 (plan §A):
- The repo sits on `G:\` and is bind-mounted into `laravel.test` through WSL2 **9p** (`G:\ on /var/www/html type 9p (aname=drvfs …)`).
- OPcache revalidates file timestamps every 2 s, and a request includes 755–831 PHP files at about 1.4 ms per `stat` over 9p.
- `/login` takes **2.1 s** when requests are 3 s apart and **0.16 s** back to back. The app's own work is 32–55 ms per page.
- `php artisan --version` takes 2.6 s in the container vs 0.33 s natively with Laragon (no OPcache).

Every human click pays the ~2 s, so the app feels slow whatever we do in code.

Options:
1. **Serve natively with Laragon** (Apache or nginx + PHP with OPcache) against Sail's PostgreSQL (127.0.0.1:5432) and Mailpit (1026/8026).
2. **Move the repo into the WSL2 filesystem** and keep Sail for everything. Native Linux I/O in Docker. But Laragon PHP, Git, Node and the Claude Code
   agents on Windows would then reach the code through `\\wsl$`, the same slow path in the other direction, unless the whole toolchain moves into WSL.
3. **Tune OPcache in the container** (`revalidate_freq` 30–60 s, `composer dump-autoload -o`, `artisan optimize`). Fewer stats, but edits show up
   late or need manual cache clears. Error-prone for agents editing code.
4. Docker Desktop synchronized file shares (paid tiers).

## Decision
Option 1:
- The app runs natively on Windows through Laragon's web server with OPcache on (`validate_timestamps=1`, `revalidate_freq=0`; native NTFS stats
  are cheap), at e.g. http://invory.test.
- Docker keeps `pgsql` and `mailpit`. `docker compose up -d` starts only those two.
- The `laravel.test` container moves behind the Compose profile `app` and its own port (`APP_PORT`, default 8080). It is started only for the
  PostgreSQL parity suite and PHP 8.5 checks: `docker compose --profile app up -d laravel.test`. Its `environment` points the database and mail
  at the Compose service names, so it works with the same `.env` as the native app.
- The native app serves http://localhost:8000 through a Laragon Apache virtual host (`Listen 8000`, `DocumentRoot G:/Projects/Invory/public`),
  so `APP_URL` and bookmarks don't change.
- `.env` points at `127.0.0.1` for the database and mail.
- Xdebug is off unless debugging.
- SETUP.md gets the steps (story S-18). The machine configuration (php.ini, the vhost) is done by the human, not by agents.

## Consequences
- ✅ Expected server time per visit drops from ~2 s to tens of milliseconds (target p95 ≤ 150 ms, verified by S-18).
- ✅ Agents, gates and tooling don't change (already native).
- ⚠️ The dev app runs on PHP 8.3 (Laragon) while the container and production may be 8.5. CI already tests 8.3 and 8.5, and the parity suite keeps
  running in the container.
- ⚠️ Windows is not production-like (file paths, case sensitivity). The parity suite in Linux and CI cover that.
- ⚠️ Laragon's Apache/nginx must not clash with Sail on port 80. Sail's app container already maps to 8000 on this machine.
