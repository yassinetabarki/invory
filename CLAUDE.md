# Invory — fashion BOM costing & production capacity

Invory tracks an **inventory of primary materials** (fabric, trims, thread, leather, soles…) and lets users define **final fashion products**
and the **materials that compose them**. For every product it shows the **unit cost** and **how many units can be produced** from stock,
plus the **bottleneck material**.

- Product: [docs/ROADMAP.md](docs/ROADMAP.md) · Backlog: [docs/BACKLOG.md](docs/BACKLOG.md) · Status: [docs/STATUS.md](docs/STATUS.md) · Stories: `docs/stories/`
- Technical: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) · Decisions: [docs/adr/](docs/adr/) · Earlier plans: [docs/plans/](docs/plans/README.md) · Tech debt: [docs/TECH-DEBT.md](docs/TECH-DEBT.md)
- Team and workflow: [docs/TEAM.md](docs/TEAM.md) · Setup: [docs/SETUP.md](docs/SETUP.md)
- Business maths (single source of truth): [.claude/skills/invory-costing-domain/SKILL.md](.claude/skills/invory-costing-domain/SKILL.md)

## Stack
Laravel 13 (PHP 8.3+) · Inertia 3 · React 19 + TypeScript + React Compiler · Tailwind 4 + shadcn/ui · Wayfinder · Fortify auth ·
Pest 4 + Larastan + Pint · Vite+ (Vitest, Oxlint, Oxfmt) · PostgreSQL · Laravel Boost. Single company, no teams.
Dev environment: **native PHP 8.3 + Composer** serves the app (`composer dev` → `php artisan serve` on :8000, plus Vite) and runs all tooling and agents.
**Docker Compose** runs only **PostgreSQL** and **Mailpit** (ADR-0012).

## How work gets done: two leads, you are the liaison
The **product-owner** owns *what* and *why* (phases, backlog order, story cards, behaviour and UX, acceptance, progress).
The **tech-lead** owns *how* (the Technical work in each card, dispatching developers, code review and gates, architecture, ADRs, tech debt, CI).
The main session **does not write feature code, stories or Technical work, and does not brief the developers directly**. Subagents can't ask the
human questions, so you carry their questions to the human with `AskUserQuestion`. Full workflow and decision rights: [docs/TEAM.md](docs/TEAM.md).

**One card per story, two authors** (`docs/stories/<id>-<slug>.md`, Obsidian style, read by the human in Obsidian): the product-owner writes
only **Description**, **How it works today** and **Acceptance criteria**; the tech-lead then adds **Technical work** under them in the same
file (a few plain bullets when simple, a detailed plan when complex). Questions travel in hand-offs, never inside the card. Cards and plans
that existed on 2026-10-14 (including on-hold or unfinished ones) keep their old layout and aren't converted.

**Cards and planning run on the main checkout; only building runs in a worktree.** The Obsidian vault is the main checkout's `docs/`
(`G:\Projects\Invory\docs`), and the app only lets a session edit files inside its own folder. So steps 0–1 (story cards, Technical work,
ROADMAP, BACKLOG, STATUS) run from a session opened on `G:\Projects\Invory`, where both leads write straight into the vault. Only step 2
(build) runs in a worktree session. If this session is in a worktree and is asked to refine or plan, don't write the docs here: tell the
human and offer to start a session on `G:\Projects\Invory`. Docs changed during steps 2–4 (the card's Progress lines, TECH-DEBT,
acceptance ticks, BACKLOG/STATUS) are committed on the build branch and reach the vault when it merges into `main`.

**Approved docs are committed before development.** As soon as the human approves cards (→ `Ready`) or a card's Technical work
(→ `Planned`), commit exactly the doc files the hand-off lists, on `main` (`docs(phase-N): S-xx card`, `docs(phase-N): S-xx technical
work`). That approval is the human's yes for this docs commit; don't ask again. If the checkout isn't on `main`, or has staged changes that
aren't these files, stop and ask. The build worktree is cut from `main` only after the card and its Technical work are committed, so it
starts with them.

```
human ⇄ main session ──► product-owner   0. REFINE: phase proposal, story cards docs/stories/<id>-<slug>.md   ◄── human approves cards
     (main checkout) ──► tech-lead       1. PLAN (planning-only): "Technical work" added in the same card      ◄── human approves it
                     main session        ↳ after each approval: commit the approved docs on main ── then a worktree is cut from main
          (worktree) ──► tech-lead       2. BUILD: dispatch ──► backend-developer ∥ frontend-developer, review, delivery report
                     ──► product-owner   3. ACCEPT: AC check + click-through script                          ◄── human accepts
                     main session        4. CLOSE: commit per acceptance criterion (human's yes) ──► product-owner updates BACKLOG/STATUS
```

### Step 0 — Refine (product-owner)
1. From a session on `G:\Projects\Invory` (steps 0–1 never run in a worktree): when the human asks "what's next?", names a feature, or a
   phase ends, dispatch the **product-owner** (`subagent_type: "product-owner"`)
   with the request and any decisions made so far. It updates `docs/ROADMAP.md` / `docs/BACKLOG.md` and writes story cards.
   Don't pick stories yourself.
2. Present its output: link the cards, summarise each in two lines, and ask its "Questions for the human" with `AskUserQuestion` (keep its
   options and recommendation; don't add rules it didn't propose). Send the answers back with `SendMessage`. Only the human makes a card `Ready`.
   When they do, commit the approved cards and the backlog/roadmap changes on `main` right away (docs commit rule above).

### Step 1 — Plan (tech-lead, planning-only)
3. Dispatch the **tech-lead** (`subagent_type: "tech-lead"`) in **planning-only mode** with the `Ready` card paths (or the `TECH-DEBT.md` IDs
   of the phase's tech allowance): it adds the **Technical work** section under each card's acceptance criteria (for a tech-allowance item
   it writes a whole `F-xx` card) and returns without dispatching. Run it in the background.
4. Present it: link the card, say whether the tech-lead rated the work simple or complex, and summarise its steps, risks and questions.
   Route behaviour/UX/scope questions to the product-owner, product decisions to the human. Revisions go back to the same tech-lead with
   `SendMessage`. Wait for the human's approval, then commit the card with its Technical work on `main` (docs commit rule above).

### Step 2 — Build (tech-lead, execute)
5. Once the docs commit is on `main`, the build runs in a worktree session cut from `main`: offer one with `start_session` (worktree on,
   the card path in the brief), or the human opens one. There, dispatch the **tech-lead** with the card path: "Technical work approved.
   Execute it." It dispatches, reviews every handoff by running all gates, loops fixes (max 3 rounds) and returns a **delivery report**
   with an AC → test evidence table.

### Step 3 — Accept (product-owner)
6. Send the delivery report to the product-owner for its **acceptance review**. Relay the verdicts, the gates and its click-through
   script to the human. Gaps go back to the tech-lead (fix) or become new stories (product-owner).

### Step 4 — Close
7. When the human accepts: **ask before committing**, then commit per acceptance criterion, staging only the files the report lists
   (Conventional Commits, on the build branch), and tell the product-owner the commit hashes so it ticks the card's criteria and updates
   BACKLOG, STATUS and ROADMAP.

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
Windows 10, PowerShell 5.1 + Git Bash. Node 24, Git 2.54, Docker Desktop. PHP 8.3 lives in `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64` (only as a
PATH entry; the Laragon app isn't used) and Composer in `C:\composer`. If `php` isn't found, prepend it for the command:
`$env:PATH = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;' + $env:PATH`.
- Code tooling and **database commands** run natively: `composer test` (Pest on SQLite in-memory, no Docker needed), `npm …`, `npx vp test`,
  `php artisan migrate`, `php artisan tinker` (`.env` points at `127.0.0.1`). Never `migrate:fresh` or seed the dev database `laravel`.
- Docker runs only `pgsql` and `mailpit`: `docker compose up -d`. If Docker isn't running, say so; don't try to start or install anything.
- PostgreSQL parity suite: `composer test:pgsql` (database `testing`; a guard aborts runs on any other database; never two runs at once).
  PHP 8.5 is tested in CI only (GitHub Actions on every push to `main`). Run the gates one at a time.
- Local ports (`.env`, git-ignored): app http://localhost:8000 (`composer dev` / `php artisan serve`, single-process) · Mailpit SMTP 1026, inbox
  http://localhost:8026 · PostgreSQL 127.0.0.1:5432 · Vite (native, 5173/5174). A stray Laragon Mailpit holds 1025/8025; port 80 is an unrelated nginx.
  Other Docker projects (`odysseus-*`) are running. Never stop or remove containers that aren't `invory-*`.
