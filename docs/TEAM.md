# Invory — Team, skills and workflow

Three Claude Code subagents (in `.claude/agents/`) build Invory. The **tech-lead orchestrates**: it plans, dispatches the two developers,
reviews and loops fixes. The **main Claude session is the liaison**: it hands work to the tech-lead, relays reports, asks you (the human) for
product decisions, and commits only with your approval.

## Roles

| Agent | Model | Owns | Can spawn | Never does |
|---|---|---|---|---|
| **tech-lead** | opus | Planning, contracts, dispatching, reviews, architecture, ADRs, backlog, plans (`docs/plans/`), CI (`.github/`) | `backend-developer`, `frontend-developer` | Write application code, commit |
| **backend-developer** | sonnet | `app/`, `routes/`, `database/`, `config/`, `tests/` (PHP), `resources/js/types/domain.ts`, `sharedPageProps` | nothing (`disallowedTools: Agent`) | Build UI (page *stubs* only), commit |
| **frontend-developer** | sonnet | `resources/js/**` (except contract files and generated code), `resources/css`, Vitest and browser tests | nothing (`disallowedTools: Agent`) | Touch PHP or the contract files, commit |

Subagents can't ask you questions directly. The tech-lead collects product questions in its report, and the main session asks you.

## Skills matrix: what each role must master

The *competency* column is the human-level skill set we researched for each role. The *Claude skills / tools* column is
what the agent loads to apply it in this codebase.

| Role | Competencies required | Claude skills / tools |
|---|---|---|
| **Tech lead** | Laravel architecture (modular monolith, actions, policies) · domain modelling of BOM costing · decimal precision · OWASP Top 10 for web apps (mass assignment, broken access control, XSS, SQLi) · DB design and indexing · N+1 detection · test strategy (unit/feature/arch/E2E) · accessibility review · CI/CD · writing ADRs · mentoring-quality feedback | Preloaded: `invory-costing-domain`, `invory-code-review` · Built-in: `/security-review`, `/code-review` · Boost MCP: `search-docs`, `database-schema`, `application-info` · Agent memory (project) |
| **Backend developer** | PHP 8.3+ (enums, readonly classes, strict types) · Laravel 13: Eloquent, migrations, FormRequests, Policies, API Resources, transactions, service container · Inertia 3 server side · `brick/math` BigDecimal · Pest 4 (datasets, `assertInertia`, arch tests) · Larastan / PHPStan generics · Pint · Wayfinder route naming | Preloaded: `invory-costing-domain`, `invory-laravel-backend` · Boost skills: `pest-testing`, `wayfinder-development`, `infer-conventions` · Boost MCP: `search-docs`, `database-schema`, `database-query`, `last-error`, `read-log-entries` |
| **Frontend developer** | React 19 (Rules of React, React Compiler, ref-as-prop) · TypeScript strict · Inertia 3 client (pages, `<Form>`, `useForm`, partial reloads, `preserveState`) · Wayfinder · Tailwind 4 · shadcn/ui + Radix · form UX for numeric data · `Intl.NumberFormat` · WCAG 2.2 AA · responsive and dark mode · Vitest + React Testing Library + user-event · Pest browser tests | Preloaded: `invory-costing-domain`, `invory-react-frontend` · Boost skills: `inertia-react-development`, `wayfinder-development`, `tailwindcss-development` · Boost MCP: `search-docs`, `browser-logs` |

Boost skills and MCP tools appear after `php artisan boost:install` (see [SETUP.md](SETUP.md)).
Project skills live in `.claude/skills/invory-*` and are committed. `invory-costing-domain` is the single source of truth for business maths.

## Workflow

```
 human ⇄ main session (liaison)
              │ hands over a story / phase (+ human decisions)
              ▼
 tech-lead ── 1. PLAN: plan in docs/plans/, subtasks with disjoint file lists, frozen contract, waves
          │
          ├── 2. DISPATCH (Agent tool, briefs) ──► backend-developer  ┐ same message = parallel
          │                                  ──► frontend-developer ┘ (also 2× backend on disjoint stories)
          │                                       │ handoff reports
          ├── 3. REVIEW: runs all gates + parity ◄┘
          ├── 4. FIX LOOP: findings → same developer (SendMessage), max 3 rounds
          ▼
 5. DELIVERY REPORT ──► main session ──► human: verdicts, questions, "commit S-xx?" ──► scoped commit per story
```

### Rules of engagement
1. **Contract first.** The contract (routes, props, `domain.ts`, PHP APIs) is frozen in the plan, and the backend writes the types first, so the
   frontend can build components in parallel. Pages that need routes wait for the backend story.
2. **One shared working tree, so disjoint files per wave.** Agents touch only the files in their brief and run focused checks while others work.
   The tech-lead runs the full gates once the wave is complete.
3. **Git:** one branch per phase (`phase/1-materials`), **one commit per approved story**, staged from the story's file list.
   Conventional Commits (`feat(materials): …`, `test(costing): …`). The human approves every commit, push and merge. Agents never commit.
4. **Handoff reports are mandatory** and use the exact template in each agent file. The tech-lead re-runs every gate. Pasted output is not proof.
5. **Disagreements** are settled by the tech-lead. Product questions (scope, wording, business rules) go up to the human through the main session.
6. **Domain changes** (a formula, rounding, a new edge case) require the tech-lead to update `invory-costing-domain` first. Code follows the skill, never the other way round.

## Example prompts for the main session
- "Start Phase 1." → the main session hands `docs/plans/phase-1-foundation-materials.md` to the tech-lead, who runs waves 1–3.
- "Do only Wave 1 of Phase 1, then stop for review."
- "Start S-04 and S-05." → the tech-lead writes the Phase 2 plan first, then dispatches.
