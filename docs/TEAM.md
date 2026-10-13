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
 tech-lead ── 1. PLAN: writes docs/plans/<story>-plan.md (see format below), returns WITHOUT dispatching
              │
              ▼ plan ──► main session ──► human: scope, subtasks, risks, open questions
                                                  │
                                      human approves (or requests changes → back to 1)
                                                  │
              ◄─────────────────── "approved, execute" ───────────────────────────────
              │
          ├── 2. DISPATCH (Agent tool, briefs) ──► backend-developer  ┐ same message = parallel
          │                                  ──► frontend-developer ┘ (also 2× backend on disjoint stories)
          │                                       │ handoff reports
          ├── 3. REVIEW: runs all gates + parity ◄┘
          ├── 4. FIX LOOP: findings → same developer (SendMessage), max 3 rounds
          ▼
 5. DELIVERY REPORT ──► main session ──► human: verdicts, questions, "commit S-xx?" ──► scoped commit per story
```

### Plan document format (`docs/plans/<story>-plan.md`)

The tech-lead writes this file in phase 1 and commits it only after the human approves (the main session commits it together with the first story).

```markdown
# Story Plan: <title>
> Status: draft | approved | in-progress | done
> Date: YYYY-MM-DD
> Stories: S-xx, S-xx

## Scope
What is in / out of scope.

## Subtasks
| ID | Lane | Owner | Files | Lines est. | Commit label |
|---|---|---|---|---|---|

## Frozen contract
Routes, props shape, `domain.ts` types the backend delivers and the frontend consumes.
Settled before dispatch so both agents can build against a stable interface.

## Execution order / waves
Wave 1 (backend): …
Wave 2 (frontend, unblocked after wave 1 types land): …

## Risks & open questions
- Risk: …
- Q (for human): Should X be Y?
```

The main session presents a plan by linking the file and summarising the open questions. Execution does not start until the human says "approved" (or equivalent). Changes requested by the human go back to the tech-lead via `SendMessage`; the file is updated in-place before execution begins.

### Rules of engagement
1. **Contract first.** The contract (routes, props, `domain.ts`, PHP APIs) is frozen in the plan, and the backend writes the types first, so the
   frontend can build components in parallel. Pages that need routes wait for the backend story.
2. **One shared working tree, so disjoint files per wave.** Agents touch only the files in their brief and run focused checks while others work.
   The tech-lead runs the full gates once the wave is complete.
3. **Git:** one branch per phase (`phase/1-materials`), **one commit per acceptance criterion** (not per story), staged from that criterion's file list.
   Conventional Commits (`feat(materials): …`, `test(costing): …`). The human approves every commit, push and merge. Agents never commit.
4. **Handoff reports are mandatory** and use the exact template in each agent file. The tech-lead re-runs every gate. Pasted output is not proof.
5. **Disagreements** are settled by the tech-lead. Product questions (scope, wording, business rules) go up to the human through the main session.
6. **Domain changes** (a formula, rounding, a new edge case) require the tech-lead to update `invory-costing-domain` first. Code follows the skill, never the other way round.

## Two-lane delivery (CRITICAL vs NON-CRITICAL)

Every subtask is tagged by the tech-lead before dispatch. The tag drives the test budget, the pre-flight ack and the review template.

| Lane | What belongs | Pre-flight ack | Test budget | Review |
|---|---|---|---|---|
| **CRITICAL** | `App\Domain\*`, money / quantities / `BigDecimal`, migrations w/ FKs or in-use guards, transactions, policies, `FormRequest`s with business rules, contract edits (`domain.ts`, Resources, routes), form editors with validation branching, anything the human called out | **Yes** — dev replies with files + listed tests + risks and waits for a confirm before coding | Full (see tables below). 100 % branch on domain code | Full checklist from `invory-code-review` |
| **NON-CRITICAL** | Pure CRUD without business rules, copy, labels, layouts, pure styling, shadcn wrappers, empty / loading / error states, toast wording, formatter displays that call tested primitives, docs | No | Light (see tables below) | Fast-lane: gates green + domain-correctness scan + security scan + contract check |

When in doubt, tag CRITICAL. If a NON-CRITICAL commit turns out to touch anything from the CRITICAL list (added a `BigDecimal`, grew a `FormRequest`
rule, drifted the contract), the tech-lead upgrades the lane and re-reviews full.

## Slicing rubric — slice or lump?

**Slice when any of these is true:**
- Two concerns have independent acceptance criteria (one criterion ≈ one commit).
- One is CRITICAL, the other is NON-CRITICAL — different review depth.
- File list spans > 2 directories or > ~400 lines of production code.
- One could ship, revert or bisect without the other.
- A reviewer would scroll back and forth between unrelated chunks.

**Lump when any of these is true (bisect-safety wins):**
- Migration + model + factory + first policy for a new table — splitting breaks `composer test` mid-history.
- Resource + the `resources/js/types/domain.ts` edit it caused — never split a contract.
- A page + the components only that page uses.
- Removing a half-made feature in one revert beats two.

**Commit sweet spot:** 3–8 files, 100–400 lines of production code, one coherent concern that reads top-to-bottom in a diff.
`composer test` and `npx vp test` must stay green at every head.

The tech-lead publishes a **slicing table** per story in the phase plan (`docs/plans/`): subtask id → lane → files → line budget → tests allowed → owner → commit label.

## Testing rubric — "test the risk, not the code"

**Backend — must-have per layer:**

| Layer | Tests | Count |
|---|---|---|
| `App\Domain\*` (costing, sales, capacity) | Worked example with exact string assertions + every edge case named in the domain skill | 100 % branch |
| Policy | Guest, denied, allowed — in one dataset | 1 per policy |
| Action | Happy path + rollback-on-exception if transactional + one per business rule it enforces | 1 happy + 1 rollback + 1 per rule |
| Controller (route) | Happy path per route + validation dataset (5–10 rule combinations in one test) + guest redirect | 1 happy + 1 dataset + 1 guest |
| Resource | Shape via `assertInertia` on one row | 1 contract |
| Index page | Query budget / N+1 regression | 1 per index |
| Architecture | Each layer rule from §3 | 1 per rule, not per file |

**Backend — skip:**
- "Request extends FormRequest", "Resource maps field 1:1", getters, setters, framework behaviour.
- Repeating the same assertion on different data when one dataset covers it.
- Per-column assertions when `assertInertia` on the shape proves it.
- Private methods and internal state.

**Frontend — must-have per target:**

| Target | Tests | Count |
|---|---|---|
| Formatters (`lib/format.ts`) | One per format rule | per rule |
| Form editors (`BomEditor`, `ProductForm`, forms with validation branching) | user-event happy + error branch + one key edge, queries by role / label | 1 happy + 1 error + 1 edge |
| Page | Render with representative props; one smoke that the critical values show (unit cost, can produce, bottleneck) | 1 per page |
| E2E (Pest 4 browser) | Golden path per feature | 1 per feature (S-08) |

**Frontend — skip:**
- shadcn/ui wrappers, pure layouts, empty-state components.
- Per-column render checks on tables.
- Snapshot tests.
- Multiple page tests for the same page unless the branches differ.

**Reliability patterns (both sides):**
- Time: `travelTo()` / `vi.setSystemTime`. No `now()` or `new Date()` in assertions.
- Factories valid-by-default; the test overrides only what it's about.
- `waitFor` wraps a positive assertion with a tight timeout; no bare sleeps.
- Behaviour not internals: no private-method tests; use `getByRole` not `getByTestId` where possible.
- One assertion focus per test; split only when the setup differs.
- Agents list the tests they'll add in the pre-flight ack (one line each) so the tech-lead prunes before code is written.
- Handoffs report lines-of-diff + test count + runtime.

## Example prompts for the main session
- "Start Phase 1." → the main session hands `docs/plans/phase-1-foundation-materials.md` to the tech-lead, who runs waves 1–3.
- "Do only Wave 1 of Phase 1, then stop for review."
- "Start S-04 and S-05." → the tech-lead writes the Phase 2 plan first, then dispatches.
