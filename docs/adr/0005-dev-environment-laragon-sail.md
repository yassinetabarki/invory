# ADR-0005 — Dev environment: Laragon for tooling, Laravel Sail (Docker) for the runtime

- **Status:** Accepted · 2026-10-07 (decided by the product owner) · **runtime part amended by [ADR-0007](0007-dev-runtime-native-serving.md) on 2026-10-08**:
  the app is served natively by Laragon; Docker runs PostgreSQL and Mailpit, and the `laravel.test` container only for parity runs

## Context
The team works on Windows and wants a dockerized project. Laravel Sail is Laravel's official Docker Compose environment. On Windows it
runs through WSL2 + Docker Desktop. The Claude Code agents, the IDE and the Laravel/Boost tooling also need a native PHP on Windows.
Laragon provides that (PHP, Composer, Node, Git) without Docker.

## Decision
- **Runtime = Sail** (`compose.yaml`): `laravel.test` (PHP 8.5 runtime), **PostgreSQL 18** (same engine as production) and **Mailpit** (catches Fortify emails).
  App at http://localhost. Use `.\sail …` (root `sail.cmd` → WSL2 → `vendor/bin/sail`).
- **Tooling = Laragon** PHP (8.3.33 on the first dev machine) + Composer on the Windows PATH: `composer`, Pint, Larastan, the fast Pest suite, Wayfinder generation,
  the Boost MCP server, and `npm run dev` (Vite runs natively for fast HMR).
- **Tests:** the default suite runs on SQLite in-memory (`phpunit.xml`), natively and in seconds, so agents don't depend on Docker.
  A **PostgreSQL parity run** happens in CI (env vars override `phpunit.xml`) and locally when Docker is up (see SETUP.md).
- Laragon's own Apache/Nginx/MySQL are **not** used for this project. Stop Apache/Nginx while Sail is up, because they clash with the app on port 80.

## Consequences
- ✅ Everyone runs identical services (DB version, mail) in Docker, with production parity on PostgreSQL.
- ✅ Agents and gates stay fast and Docker-independent.
- ⚠️ Two PHPs exist: Laragon's 8.3.33 (native tests, tooling) and the container's 8.5. Laravel 13 supports both, and the PostgreSQL parity run
  covers the container's PHP. Align them when convenient (add PHP 8.5 to Laragon, or point `compose.yaml` at `runtimes/8.3`), and never write code
  that needs a PHP newer than the lowest one in use.
- ⚠️ The repo lives on a Windows drive, so the bind mount through WSL2 is slower than a Linux-filesystem checkout. This is acceptable for the MVP.
  Revisit (move the repo into WSL2) if file I/O becomes painful.
- ⚠️ Sail is a **development** environment. A production Docker image (e.g. FrankenPHP/Octane or php-fpm + nginx) is a phase-2 item.
