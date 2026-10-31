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
Pest 4 + Larastan + Pint · Vite+ (Vitest, Oxlint, Oxfmt) · PostgreSQL · Laravel Boost.
**One workshop per account** (ADR-0013): every record belongs to the signed-in account's workshop; several accounts may share a workshop; no invitations or roles yet.
Dev environment: **native PHP 8.3 + Composer** serves the app (`composer dev` → `php artisan serve` on :8000, plus Vite) and runs all tooling and agents.
**Docker Compose** runs only **PostgreSQL** and **Mailpit** (ADR-0012).

## How work gets done: two leads, you are the liaison
The **product-owner** owns *what* and *why* (phases, backlog order, story cards, behaviour and UX, acceptance, progress).
The **tech-lead** owns *how* (the Technical work in each card, dispatching developers, code review and gates, architecture, ADRs, tech debt, CI).
The **ui-ux-designer** advises the product-owner. It proposes flows, layouts, states, accessibility and wording (design specs in
`docs/design/specs/`, boards on the design canvas) and reviews delivered UI. It decides nothing and never writes code or cards.
The main session **does not write feature code, stories or Technical work, and does not brief the developers directly**. Subagents can't ask the
human questions, so you carry their questions to the human with `AskUserQuestion`. Full workflow and decision rights: [docs/TEAM.md](docs/TEAM.md).

**Everything runs in the main checkout, one task at a time.** Since 2026-10-24 there are no worktrees: refining, designing, planning,
building and accepting all run in `G:\Projects\Invory` on `main`, where `docs/` is the human's Obsidian vault. Never create or use a
worktree and never offer a worktree session. Tasks are done **one at a time, in order** (the order in `docs/BACKLOG.md`): the next task
starts only when the current one is approved or done.

**Everything new is `INV-1`, `INV-2`, …** (files `docs/stories/INV-<n>-<slug>.md`): features and technical items alike; a technical
item gets an INV card with no UI card.
sits at the top of `docs/BACKLOG.md`.

**Each feature gets two cards, in this order** (Obsidian style, read by the human in Obsidian; questions travel in hand-offs, never inside a card):
1. **UI card** `docs/stories/INV-<n>-<slug>-ui.md`: how the pages look and behave (screens, states, wording, phone layout), designed
   by the **ui-ux-designer** (design spec in `docs/design/specs/`, boards on the design canvas) and decided and written by the
   **product-owner**. The human validates it (→ `Approved`).
2. **Development card** `docs/stories/INV-<n>-<slug>.md`: written by the product-owner only after the UI card is approved (Description,
   How it works today, Acceptance criteria, pointing at the UI card); the human approves it (→ `Ready`). Only then does the
   **tech-lead** add **Technical work** under it in the same file (a few plain bullets when simple, a detailed plan when complex).

Cards and plans that existed before 2026-10-24 keep their layout and aren't converted.

**Approved docs are committed right away.** As soon as the human approves a UI card (→ `Approved`), a development card (→ `Ready`) or a
card's Technical work (→ `Planned`), commit exactly the doc files the hand-off lists, on `main` (`docs: INV-n UI card`,
`docs: INV-n card`, `docs: INV-n technical work`). That approval is the human's yes for this docs commit; don't ask again. If the
checkout isn't on `main`, or has staged changes that aren't these files, stop and ask. Ask before every other commit.

```
human ⇄ main session ──► ui-ux-designer  0a. DESIGN: design spec + boards for the feature's pages
   (main checkout,   ──► product-owner   0b. UI CARD: decides the design, writes INV-n-<slug>-ui.md      ◄── human validates the UI card
    one task at a    ──► product-owner   0c. DEV CARD: writes INV-n-<slug>.md from the approved UI card   ◄── human approves it (Ready)
    time, on main)   ──► tech-lead       1. PLAN (planning-only): "Technical work" added in the dev card  ◄── human approves it
                     main session        ↳ after each approval: commit the approved docs on main
                     ──► tech-lead       2. BUILD: dispatch ──► backend-developer ∥ frontend-developer, review, delivery report
                     ──► product-owner   3. ACCEPT: AC check + click-through script                      ◄── human accepts
                     ──► ui-ux-designer    ↳ UX review of the delivered screens against the UI card
                     main session        4. CLOSE: commit per acceptance criterion (human's yes) ──► product-owner updates BACKLOG/STATUS
```

### Step 0 — UI card, then development card (ui-ux-designer, product-owner)
1. When the human asks "what's next?", names a feature, or a phase ends, dispatch the **product-owner** (`subagent_type: "product-owner"`)
   with the request and any decisions made so far. It orders the work in `docs/ROADMAP.md` / `docs/BACKLOG.md`. Don't pick stories yourself.
2. **Design.** For the next feature in order, dispatch the **ui-ux-designer** (`subagent_type: "ui-ux-designer"`) with the request and any
   source the human named (boards, mockups). It writes the design spec and boards. Send its proposal to the product-owner, which decides
   and writes the **UI card**. Present the UI card: link it and the boards, summarise it in two lines, and ask the open questions of both
   with `AskUserQuestion` (keep their options and recommendation; don't add rules they didn't propose). Send the answers back with
   `SendMessage`. Only the human validates a UI card; commit it on `main` right away (`docs: INV-n UI card`).
3. **Development card.** Then dispatch the product-owner to write the development card from the approved UI card. Present it the same
   way. Only the human makes it `Ready`; commit it on `main` right away (`docs: INV-n card`).

### Step 1 — Plan (tech-lead, planning-only)
4. Dispatch the **tech-lead** (`subagent_type: "tech-lead"`) in **planning-only mode** with the `Ready` development card path (or the
   `TECH-DEBT.md` IDs of the phase's tech allowance): it adds the **Technical work** section under the card's acceptance criteria (for a
   tech-allowance item it writes a whole `INV-n` card of `type: tech`) and returns without dispatching. Run it in the background.
5. Present it: link the card, say whether the tech-lead rated the work simple or complex, and summarise its steps, risks and questions.
   Route behaviour/UX/scope questions to the product-owner, product decisions to the human. Revisions go back to the same tech-lead with
   `SendMessage`. Wait for the human's approval, then commit the card with its Technical work on `main` (`docs: INV-n technical work`).

### Step 2 — Build (tech-lead, execute)
6. In this same checkout, dispatch the **tech-lead** with the card path: "Technical work approved. Execute it." It dispatches, reviews
   every handoff by running all gates, loops fixes (max 3 rounds) and returns a **delivery report** with an AC → test evidence table.

### Step 3 — Accept (product-owner)
7. Send the delivery report to the product-owner for its **acceptance review**; also send it to the **ui-ux-designer** and pass its UX
   review (against the UI card) to the product-owner. Relay the verdicts, the gates and its click-through script to the human. Gaps go
   back to the tech-lead (fix) or become new stories (product-owner).

### Step 4 — Close
8. When the human accepts: **ask before committing**, then commit per acceptance criterion on `main`, staging only the files the report
   lists (Conventional Commits), and tell the product-owner the commit hashes so it ticks the card's criteria and updates BACKLOG,
   STATUS and ROADMAP. Only then does the next task start.

Don't do the developers', the tech-lead's or the product-owner's work yourself, even if it looks quick. If an agent is blocked
(environment, permissions), tell the human. Process documents (CLAUDE.md, `.claude/**`) change only when the human asks.

## Working rules
1. Use `/clear` between unrelated tasks (the human runs it).
2. Run `/mcp` at the start of each session (the human runs it).
3. Make no changes until about 95% confident in what to build. Ask follow-up questions until then.
4. Keep token use low: short replies, read only the lines needed, dispatch subagents only for broad work.
5. No new cards until INV-10 is complete. The roadmap shows INV-1 to INV-10 only.

## Non-negotiables
- Money and quantities are **decimal strings + `Brick\Math\BigDecimal`**. Never floats, in PHP or TS. The UI formats numbers and never computes them.
- Costing maths lives only in `app/Domain/Costing` (pure PHP). Controllers stay thin. Writes go through `app/Actions`. Policies are checked on every route.
- Every model of workshop data uses `BelongsToWorkshop`. In `app/` never call `withoutGlobalScope(s)`, raw `DB::table()` on workshop tables, or
  `Rule::exists/unique` on them outside `App\Validation\WorkshopRule`. Another workshop's record is a 404, never a 403 (ADR-0013).
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
