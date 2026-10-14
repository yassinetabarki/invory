# Invory — Team, skills and workflow

Four Claude Code subagents (in `.claude/agents/`) build Invory, split by **what/why** and **how**:

- The **product-owner** owns the *what* and *why*. It plans the phases, keeps the backlog ordered, writes story cards you can read and
  approve, specifies behaviour and UX, accepts delivered work against its acceptance criteria, and reports progress.
- The **tech-lead** owns the *how*. It turns an approved story card into technical cards (a plan), dispatches the two developers, reviews
  the code by running every gate itself, and owns architecture, ADRs, technical debt and CI.
- The **main Claude session is the liaison.** Subagents can't ask you questions, so it carries work between you and the two leads, asks
  you their questions, and commits only with your approval.

## Roles

| Agent | Model | Owns | Can spawn | Never does |
|---|---|---|---|---|
| **product-owner** | opus | Roadmap and phases (`docs/ROADMAP.md`), backlog order (`docs/BACKLOG.md`), story cards (`docs/stories/`), behaviour and UX, acceptance review, progress report (`docs/STATUS.md`) | nothing | Write code or technical plans, dispatch developers, review code, run gates, commit |
| **tech-lead** | opus | Technical plans (`docs/plans/`), contracts, dispatching, code review and gates, architecture, ADRs, `docs/TECH-DEBT.md`, `docs/SETUP.md`, CI (`.github/`) | `backend-developer`, `frontend-developer` | Write application code, decide behaviour/UX/priority, edit the product-owner's files, commit |
| **backend-developer** | sonnet | `app/`, `routes/`, `database/`, `config/`, `tests/` (PHP), `resources/js/types/domain.ts`, `sharedPageProps` | nothing | Build UI (page *stubs* only), invent business rules, commit |
| **frontend-developer** | sonnet | `resources/js/**` (except contract files and generated code), `resources/css`, Vitest and browser tests | nothing | Touch PHP or the contract files, invent copy or UX, commit |
| *main session* | — | Relaying, `AskUserQuestion`, commits after your yes | `product-owner`, `tech-lead` | Write stories, plans or code itself; brief developers directly |
| *human (you)* | — | Vision, approvals (cards, plans, acceptance, commits), product decisions | — | — |

### Decision rights (RACI-style; full table in the `invory-product-owner` skill §1)

| Topic | Decides | Proposes / does | Consulted |
|---|---|---|---|
| Phases, roadmap, backlog order | **Human** | Product-owner | Tech-lead (size, risk, dependencies) |
| Story text, acceptance criteria, UX, wording | **Human** (approves the card) | Product-owner | Tech-lead (feasibility) |
| Business rules (formulas, stock rules) | **Human** | Product-owner states it; tech-lead encodes it in `invory-costing-domain` first | — |
| Technical approach, contracts, slicing, tests | **Tech-lead** | Tech-lead; human approves the plan | Developers (pre-flight ack) |
| Technical debt items and their cost of delay | **Tech-lead** | Tech-lead (`TECH-DEBT.md`) | — |
| Which debt goes into a phase (≈ 15 % allowance) | **Product-owner** | Product-owner | Tech-lead |
| Code verdict (APPROVED / CHANGES REQUESTED) | **Tech-lead** | Tech-lead | — |
| Story accepted | **Human** | Product-owner (AC check + click-through script) | Tech-lead (evidence) |
| Commits | **Human** | Main session commits; tech-lead proposes messages | — |

No role overlaps another: the tech-lead no longer owns the backlog or product questions, and the main session no longer picks stories or
writes plans.

## Skills matrix: what each role must master

The *competency* column is the human-level skill set researched for each role. The *Claude skills / tools* column is what the agent loads to
apply it here.

| Role | Competencies required | Claude skills / tools |
|---|---|---|
| **Product owner** | Product vision and outcome-based roadmaps (Now / Next / Later) · story mapping and phase slicing · backlog management (DEEP, hygiene) · user stories (INVEST, 3 Cs) and acceptance criteria (Given / When / Then with real examples) · prioritisation (MoSCoW, value / effort, cost of delay) · UX writing, interaction design and WCAG 2.2 AA basics · domain knowledge of fashion BOM costing · acceptance testing · progress reporting (RAG, RAID, flow metrics) · stakeholder communication | Preloaded: `invory-product-owner`, `invory-costing-domain` · Read-only `git log` · WebSearch / WebFetch for research · Agent memory (project) |
| **Tech lead** | Laravel architecture (modular monolith, actions, policies) · domain modelling of BOM costing · decimal precision · OWASP Top 10 for web apps (mass assignment, broken access control, XSS, SQLi) · DB design and indexing · N+1 detection · test strategy (unit/feature/arch/E2E) · vertical slicing and technical task breakdown · accessibility review · CI/CD · writing ADRs · technical-debt management · mentoring-quality feedback | Preloaded: `invory-costing-domain`, `invory-code-review` · Built-in: `/security-review`, `/code-review` · Boost MCP: `search-docs`, `database-schema`, `application-info` · Agent memory (project) |
| **Backend developer** | PHP 8.3+ (enums, readonly classes, strict types) · Laravel 13: Eloquent, migrations, FormRequests, Policies, API Resources, transactions, service container · Inertia 3 server side · `brick/math` BigDecimal · Pest 4 (datasets, `assertInertia`, arch tests) · Larastan / PHPStan generics · Pint · Wayfinder route naming | Preloaded: `invory-costing-domain`, `invory-laravel-backend` · Boost skills: `pest-testing`, `wayfinder-development`, `infer-conventions` · Boost MCP: `search-docs`, `database-schema`, `database-query`, `last-error`, `read-log-entries` |
| **Frontend developer** | React 19 (Rules of React, React Compiler, ref-as-prop) · TypeScript strict · Inertia 3 client (pages, `<Form>`, `useForm`, partial reloads, `preserveState`) · Wayfinder · Tailwind 4 · shadcn/ui + Radix · form UX for numeric data · `Intl.NumberFormat` · WCAG 2.2 AA · responsive and dark mode · Vitest + React Testing Library + user-event · Pest browser tests | Preloaded: `invory-costing-domain`, `invory-react-frontend` · Boost skills: `inertia-react-development`, `wayfinder-development`, `tailwindcss-development` · Boost MCP: `search-docs`, `browser-logs` |

Boost skills and MCP tools appear after `php artisan boost:install` (see [SETUP.md](SETUP.md)).
Project skills live in `.claude/skills/invory-*` and are committed. `invory-costing-domain` is the single source of truth for business maths.

## Workflow

```
 human ⇄ main session (liaison)
              │
   0. REFINE  ├──► product-owner: phase proposal + story cards (docs/stories/<id>-<slug>.md), questions with options
              │◄── cards ──► human approves each card ──► status Ready
              │
   1. PLAN    ├──► tech-lead (planning-only): technical plan docs/plans/<id>-<slug>-plan.md
              │       technical cards S-xx-a, -b… · AC traceability · frozen contract · waves · risks · questions for the PO
              │◄── plan ──► human approves (questions for the PO go back through step 0)
              │
   2. BUILD   ├──► tech-lead (execute): dispatch ──► backend-developer ┐ same message = parallel
              │                                   ──► frontend-developer ┘
              │                         review (all gates + parity) · fix loop, max 3 rounds
              │◄── delivery report: verdicts, gates, AC → test evidence, proposed commits
              │
   3. ACCEPT  ├──► product-owner: acceptance review (each AC vs evidence) + click-through script
              │◄── human clicks through and accepts (or a gap goes back to step 2 / a new story)
              │
   4. CLOSE   └── main session commits per acceptance criterion (your yes) ──► product-owner updates BACKLOG / STATUS / ROADMAP
```

Small technical items from `TECH-DEBT.md` in a phase's tech allowance skip step 0 (no card) and step 3 (no click-through): the tech-lead
plans them, the human approves, they're built, reviewed and committed.

**Flow limits:** at most two stories between `Planned` and `Acceptance` at once. The product-owner flags any story older than its size suggests.

### Story card (product-owner → human)
Template, writing rules, Definition of Ready and Definition of Done (product side): [`invory-product-owner` skill](../.claude/skills/invory-product-owner/SKILL.md)
§4–§7. In short, a card is the story ("As a … I want … so that …"), why it matters, acceptance criteria AC-1…n (Given / When / Then or a
rule), an example with real numbers, what you will see (screens, exact wording, states), out of scope, and questions with options.
Only you move a card to `Ready`.

### Technical plan (`docs/plans/<id>-<slug>-plan.md`)

The tech-lead writes this file in planning-only mode and it's committed with the story after you approve it.

```markdown
# Technical plan: S-xx <story title>
> Status: draft | approved | in-progress | done · Date: YYYY-MM-DD · Story card: docs/stories/S-xx-<slug>.md

## Scope
In / out (out of scope must match the card).

## AC traceability
| AC | Technical card(s) | Test(s) that will prove it |
|---|---|---|

## Technical cards (slicing table)
| # | Card id | AC | Lane | Files (exact paths) | Line budget | Tests allowed | Owner | Commit label |
|---|---|---|---|---|---|---|---|---|

## Frozen contract
Routes, props shape, `domain.ts` types the backend delivers and the frontend consumes. Settled before dispatch.

## Approach and decisions
Chosen design, alternatives rejected, ADR links. Technical decisions only.

## Execution order / waves
Wave 1 (backend): … · Wave 2 (frontend, unblocked after wave 1 types land): …

## Risks
## Questions for the product-owner (behaviour, wording, scope)
```

The main session presents a plan by linking the file and summarising scope, cards, risks and questions. Execution doesn't start until you
say "approved" (or equivalent). Changes go back to the tech-lead via `SendMessage`; questions about behaviour go to the product-owner.

### Rules of engagement
1. **Card first, then contract first.** No plan without an approved card (except tech-allowance items and S-29, whose plan predates cards). Inside a plan the contract (routes,
   props, `domain.ts`, PHP APIs) is frozen before dispatch, and the backend writes the types first, so the frontend can build in parallel.
2. **One shared working tree, so disjoint files per wave.** Agents touch only the files in their brief and run focused checks while others work.
   The tech-lead runs the full gates once the wave is complete.
3. **Git:** one branch per phase (`phase/4-release-readiness`), **one commit per acceptance criterion** (not per story), staged from that
   criterion's file list. Conventional Commits (`feat(materials): …`, `test(costing): …`). You approve every commit, push and merge. Agents never commit.
4. **Handoff reports are mandatory** and use the exact template in each agent file. The tech-lead re-runs every gate. Pasted output is not proof.
5. **Disagreements:** technical ones are settled by the tech-lead; behaviour, UX, scope and priority by the product-owner, with you deciding
   anything the card doesn't settle. Neither lead edits the other's documents.
6. **Domain changes** (a formula, rounding, a new edge case) are product decisions: the product-owner states the rule in the card, you
   decide, and the tech-lead updates `invory-costing-domain` before any code changes. Code follows the skill, never the other way round.
7. **IDs are never reused.** The product-owner numbers stories `S-xx`; the tech-lead numbers technical cards `S-xx-a…` and debt `F-xx`.
   Next free IDs sit at the top of `BACKLOG.md` and `TECH-DEBT.md`.

## Two-lane delivery (CRITICAL vs NON-CRITICAL)

Every technical card is tagged by the tech-lead before dispatch. The tag drives the test budget, the pre-flight ack and the review template.

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
- "What's next?" → the product-owner proposes the next phase or the next stories, with cards to approve.
- "Write the cards for Phase 4." → the product-owner refines S-30, S-08, S-31, S-32; you approve them; then the tech-lead plans.
- "Plan S-31." → the tech-lead writes `docs/plans/S-31-…-plan.md` from the approved card, then stops for your approval.
- "Approved, build it." → the tech-lead dispatches, reviews and returns a delivery report; then the product-owner prepares your acceptance check.
- "Where are we?" → the product-owner refreshes `docs/STATUS.md`.
