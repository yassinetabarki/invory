# Invory — fashion BOM costing & production capacity

Invory tracks an **inventory of primary materials** (fabric, trims, thread, leather, soles…) and lets users define **final fashion products**
and the **materials that compose them**. For every product it shows the **unit cost** and **how many units can be produced** from stock,
plus the **bottleneck material**.

- Architecture: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) · Decisions: [docs/adr/](docs/adr/) · Backlog: [docs/BACKLOG.md](docs/BACKLOG.md)
- Team and workflow: [docs/TEAM.md](docs/TEAM.md) · Setup: [docs/SETUP.md](docs/SETUP.md)
- Business maths (single source of truth): [.claude/skills/invory-costing-domain/SKILL.md](.claude/skills/invory-costing-domain/SKILL.md)

## Stack
Laravel 13 (PHP 8.3+) · Inertia 3 · React 19 + TypeScript + React Compiler · Tailwind 4 + shadcn/ui · Wayfinder · Fortify auth ·
Pest 4 + Larastan + Pint · Vite+ (Vitest, Oxlint, Oxfmt) · PostgreSQL · Laravel Boost. Single company, no teams.
Dev environment: **Laravel Sail** (Docker: app + PostgreSQL + Mailpit) runs the app. **Laragon** provides native PHP 8.3 + Composer for tooling and agents.

## How work gets done: you are the orchestrator
The main session **does not write feature code**. It delegates to the project subagents and keeps the human in the loop:

1. Pick the next `todo` story in `docs/BACKLOG.md` (or the one the human names).
2. **tech-lead** → plan: tasks per developer, the contract (routes, Inertia page names, `domain.ts` types), and tests per acceptance criterion.
3. **backend-developer** → implements the server side + Pest tests and returns a handoff report.
4. **frontend-developer** → implements the UI + Vitest tests against the contract and returns a handoff report.
   Steps 3 and 4 may run in parallel when the contract is fixed and the files are disjoint.
5. **tech-lead** → review: runs all gates and returns APPROVED / CHANGES REQUESTED. Route findings to the owner and loop (max 3 rounds, then ask the human).
6. When approved: summarize for the human, **ask before committing** (Conventional Commits, branch `feature/S-XX-…`), and update the backlog status.

Relay product questions (scope, wording, business rules) to the human. Don't guess them.

## Non-negotiables
- Money and quantities are **decimal strings + `Brick\Math\BigDecimal`**. Never floats, in PHP or TS. The UI formats numbers and never computes them.
- Costing maths lives only in `app/Domain/Costing` (pure PHP). Controllers stay thin. Writes go through `app/Actions`. Policies are checked on every route.
- `resources/js/types/domain.ts` mirrors the API Resources and changes in the same commit as them.
- Every change ships with tests. All gates must be green: `composer test`, `npx vp test`, `npm run check`, `npm run types:check`, `npm run build`.
- Never edit generated code (`resources/js/actions|routes|wayfinder`, `resources/js/components/ui`). Never commit `.env`.
- Agents never commit or push. The human approves git operations.

## Environment
Windows 10, PowerShell 5.1 + Git Bash. Node 24, Git 2.54, Docker Desktop + WSL2 (Ubuntu-24.04), Laragon in `C:\laragon`.
**Laragon's PHP (`C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64`) and Composer are not on the PATH by default** (docs/SETUP.md, step 1). If `php` isn't found,
prepend them to `$env:PATH` for the command (`C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;C:\laragon\bin\composer`).
- Code tooling runs **natively**: `composer …`, `composer test` (Pest on SQLite in-memory, no Docker needed), `npm …`, `npx vp test`.
- Anything touching the **database or the running app** goes through Sail: `.\sail up -d`, `.\sail artisan migrate`, `.\sail artisan tinker`.
  `.\sail` (root `sail.cmd`) forwards to `vendor/bin/sail` inside WSL2, which needs Docker Desktop's WSL integration. **It is not enabled on this machine**,
  so use the equivalent Compose commands from the repo root: `docker compose up -d`, `docker compose exec -u sail laravel.test php artisan <cmd>`
  (table in docs/SETUP.md §4). If Docker isn't running, say so; don't try to start or install anything.
- Local ports on this machine (`.env`, git-ignored): app http://localhost:8000 · Mailpit http://localhost:8026 · PostgreSQL 127.0.0.1:5432 · Vite `npm run dev` (native, 5174).
  Port 80 and Mailpit's 1025/8025 are taken by Laragon, and other Docker projects (`odysseus-*`) are running. Never stop or remove containers that aren't `invory-*`.
