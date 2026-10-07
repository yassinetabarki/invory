# Invory — Team, skills and workflow

Three Claude Code subagents (in `.claude/agents/`) build Invory. The **main Claude session acts as the orchestrator / product owner**:
it picks stories, delegates, relays reports, and asks you (the human) for product decisions and git approvals.

## Roles

| Agent | Model | Owns | Never does |
|---|---|---|---|
| **tech-lead** | opus | Architecture, ADRs, backlog, story plans and contracts, reviews, CI | Write application code |
| **backend-developer** | sonnet | `app/`, `routes/`, `database/`, `config/`, `tests/` (PHP), `resources/js/types/domain.ts` | Build UI (page *stubs* only) |
| **frontend-developer** | sonnet | `resources/js/**` (except `types/domain.ts` and generated code), `resources/css`, Vitest and browser tests | Touch PHP or the contract file |

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

## Workflow per story

```
          ┌──────────────── main session (orchestrator) ────────────────┐
          │ 1. pick next story from docs/BACKLOG.md                     │
          ▼                                                             │
   tech-lead: PLAN ── tasks + contract (routes, props, domain.ts types) │
          │                                                             │
          ├──────────────► backend-developer: implement + Pest tests    │
          │                     │ handoff report                        │
          │                     ▼                                       │
          │              frontend-developer: implement + Vitest tests   │  (BE and FE in parallel when the
          │                     │ handoff report                        │   contract is fixed and the stories
          ▼                     ▼                                       │   touch disjoint files)
   tech-lead: REVIEW ── runs all gates ── APPROVED / CHANGES REQUESTED  │
          │                                                             │
          ├── CHANGES REQUESTED → findings routed to the owning dev ────┘ (loop, max 3 rounds, then escalate)
          ▼
   APPROVED → main session asks the human to commit (Conventional Commits), updates the backlog
```

### Rules of engagement
1. **Contract first.** No frontend work starts on a feature until its routes and `domain.ts` types exist (stubs are fine).
2. **One story, one branch:** `feature/S-04-products-backend`. Commits follow Conventional Commits (`feat(products): …`, `test(costing): …`).
3. **Handoff reports are mandatory** and use the exact template in each agent file. The tech-lead re-runs every gate. Pasted output is not proof.
4. **Disagreements** go to the tech-lead. Product questions (scope, wording, business rules) go to the human through the main session.
5. **Domain changes** (a formula, rounding, a new edge case) require the tech-lead to update `invory-costing-domain` first. Code follows the skill, never the other way round.
6. The human approves every commit, push and PR. Agents never commit.

## Example prompts for the main session
- "Start S-01: ask the tech-lead for the plan, then have the backend-developer implement it, then get a tech-lead review."
- "Run S-02 and S-03 in parallel: frontend-developer on S-02, backend-developer on S-03, then review both."
- "The tech-lead requested changes on S-04. Send the findings to the backend-developer and re-review."
