# Invory — fashion BOM costing & production capacity

Invory tracks an **inventory of primary materials** (fabric, trims, thread, leather, soles…) and lets users define **final fashion products**
and the **materials that compose them**. For every product it shows the **unit cost** and **how many units can be produced** from stock,
plus the **bottleneck material**.

- Product: [docs/ROADMAP.md](docs/ROADMAP.md) · Backlog: [docs/BACKLOG.md](docs/BACKLOG.md) · Status: [docs/STATUS.md](docs/STATUS.md) · Stories: `docs/stories/`
- Technical: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) · Decisions: [docs/adr/](docs/adr/) · Plans: [docs/plans/](docs/plans/README.md) · Tech debt: [docs/TECH-DEBT.md](docs/TECH-DEBT.md)
- Team and workflow: [docs/TEAM.md](docs/TEAM.md) · Setup: [docs/SETUP.md](docs/SETUP.md)
- Business maths (single source of truth): [.claude/skills/invory-costing-domain/SKILL.md](.claude/skills/invory-costing-domain/SKILL.md)

## Stack
Laravel 13 (PHP 8.3+) · Inertia 3 · React 19 + TypeScript + React Compiler · Tailwind 4 + shadcn/ui · Wayfinder · Fortify auth ·
Pest 4 + Larastan + Pint · Vite+ (Vitest, Oxlint, Oxfmt) · PostgreSQL · Laravel Boost. Single company, no teams.
Dev environment: **Laravel Sail** (Docker: app + PostgreSQL + Mailpit) runs the app. **Laragon** provides native PHP 8.3 + Composer for tooling and agents.

## How work gets done: two leads, you are the liaison
The **product-owner** owns *what* and *why* (phases, backlog order, story cards, behaviour and UX, acceptance, progress).
The **tech-lead** owns *how* (technical plans, dispatching developers, code review and gates, architecture, ADRs, tech debt, CI).
The main session **does not write feature code, stories or plans, and does not brief the developers directly**. Subagents can't ask the
human questions, so you carry their questions to the human with `AskUserQuestion`. Full workflow and decision rights: [docs/TEAM.md](docs/TEAM.md).

```
human ⇄ main session ──► product-owner   0. REFINE: phase proposal, story cards docs/stories/<id>-<slug>.md   ◄── human approves cards
                     ──► tech-lead       1. PLAN (planning-only): docs/plans/<id>-<slug>-plan.md            ◄── human approves plan
                     ──► tech-lead       2. BUILD: dispatch ──► backend-developer ∥ frontend-developer, review, delivery report
                     ──► product-owner   3. ACCEPT: AC check + click-through script                          ◄── human accepts
                     main session        4. CLOSE: commit per acceptance criterion (human's yes) ──► product-owner updates BACKLOG/STATUS
```

### Step 0 — Refine (product-owner)
1. When the human asks "what's next?", names a feature, or a phase ends: dispatch the **product-owner** (`subagent_type: "product-owner"`)
   with the request and any decisions made so far. It updates `docs/ROADMAP.md` / `docs/BACKLOG.md` and writes story cards.
   Don't pick stories yourself.
2. Present its output: link the cards, summarise each in two lines, and ask its "Questions for the human" with `AskUserQuestion` (keep its
   options and recommendation; don't add rules it didn't propose). Send the answers back with `SendMessage`. Only the human makes a card `Ready`.

### Step 1 — Plan (tech-lead, planning-only)
3. Dispatch the **tech-lead** (`subagent_type: "tech-lead"`) in **planning-only mode** with the `Ready` card paths (or the `TECH-DEBT.md` IDs
   of the phase's tech allowance): it writes the technical plan and returns without dispatching. Run it in the background.
4. Present the plan: link it, summarise technical cards, risks and questions. Route behaviour/UX/scope questions to the product-owner,
   product decisions to the human. Revisions go back to the same tech-lead with `SendMessage`. Wait for the human's approval.

### Step 2 — Build (tech-lead, execute)
5. `SendMessage` the same tech-lead: "Plan approved. Execute it." It dispatches, reviews every handoff by running all gates, loops fixes
   (max 3 rounds) and returns a **delivery report** with an AC → test evidence table.

### Step 3 — Accept (product-owner)
6. Send the delivery report to the product-owner for its **acceptance review**. Relay the verdicts, the gates and its click-through
   script to the human. Gaps go back to the tech-lead (fix) or become new stories (product-owner).

### Step 4 — Close
7. When the human accepts: **ask before committing**, then commit per acceptance criterion, staging only the files the report lists
   (Conventional Commits, branch per phase), and tell the product-owner the commit hashes so it updates BACKLOG, STATUS and ROADMAP.

Don't do the developers', the tech-lead's or the product-owner's work yourself, even if it looks quick. If an agent is blocked
(environment, permissions), tell the human. Process documents (CLAUDE.md, `.claude/**`) change only when the human asks.

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
