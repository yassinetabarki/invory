# Invory — Team, skills and workflow

Five Claude Code subagents (in `.claude/agents/`) build Invory, split by **what/why** and **how**:

- The **product-owner** owns the *what* and *why*. It plans the phases, keeps the backlog ordered, writes story cards you can read and
  approve, specifies behaviour and UX, accepts delivered work against its acceptance criteria, and reports progress.
- The **tech-lead** owns the *how*. Under an approved story card, in the same file, it adds the **Technical work** (short when the work is
  simple, detailed when it is complex), dispatches the two developers, reviews the code by running every gate itself, and owns
  architecture, ADRs, technical debt and CI.
- The **ui-ux-designer** advises the product-owner on how screens look and behave: it proposes flows, layouts, states, accessibility and
  wording as design specs and boards on the design canvas, and reviews delivered UI. The product-owner decides; the designer never writes
  code or cards.
- The **main Claude session is the liaison.** Subagents can't ask you questions, so it carries work between you and the two leads, asks
  you their questions, and commits only with your approval.

## Roles

| Agent | Model | Owns | Can spawn | Never does |
|---|---|---|---|---|
| **product-owner** | opus | Roadmap and phases (`docs/ROADMAP.md`), backlog order (`docs/BACKLOG.md`), story cards (`docs/stories/`: properties, Description, How it works today, Acceptance criteria), behaviour and UX, acceptance review, progress report (`docs/STATUS.md`) | nothing | Write code or Technical work, dispatch developers, review code, run gates, commit |
| **ui-ux-designer** | opus | UX proposals: design specs (`docs/design/specs/`), boards on the claude.ai design canvas, card reviews for UX gaps, UX review of delivered UI (Step 3) | nothing | Decide UX, write cards, Technical work or code, edit existing canvas boards, run gates, commit |
| **tech-lead** | opus | The **Technical work** section of every card, `F-xx` cards, contracts, dispatching, code review and gates, architecture, ADRs, `docs/TECH-DEBT.md`, `docs/SETUP.md`, CI (`.github/`); earlier plans in `docs/plans/` | `backend-developer`, `frontend-developer` | Write application code, decide behaviour/UX/priority, edit the product-owner's files or part of a card, commit |
| **backend-developer** | sonnet | `app/`, `routes/`, `database/`, `config/`, `tests/` (PHP), `resources/js/types/domain.ts`, `sharedPageProps` | nothing | Build UI (page *stubs* only), invent business rules, commit |
| **frontend-developer** | sonnet | `resources/js/**` (except contract files and generated code), `resources/css`, Vitest and browser tests | nothing | Touch PHP or the contract files, invent copy or UX, commit |
| *main session* | — | Relaying, `AskUserQuestion`, commits after your yes | `product-owner`, `ui-ux-designer`, `tech-lead` | Write stories, Technical work or code itself; brief developers directly |
| *human (you)* | — | Vision, approvals (cards, Technical work, acceptance, commits), product decisions | — | — |

### Decision rights (RACI-style; full table in the `invory-product-owner` skill §1)

| Topic | Decides | Proposes / does | Consulted |
|---|---|---|---|
| Phases, roadmap, backlog order | **Human** | Product-owner | Tech-lead (size, risk, dependencies) |
| Story text, acceptance criteria, UX, wording | **Human** (approves the card) | Product-owner | UI/UX designer (design spec, boards), tech-lead (feasibility) |
| Business rules (formulas, stock rules) | **Human** | Product-owner states it; tech-lead encodes it in `invory-costing-domain` first | — |
| Technical approach, contracts, slicing, tests | **Tech-lead** | Tech-lead (the card's Technical work); human approves it | Developers (pre-flight ack) |
| Technical debt items and their cost of delay | **Tech-lead** | Tech-lead (`TECH-DEBT.md`) | — |
| Which debt goes into a phase (≈ 15 % allowance) | **Product-owner** | Product-owner | Tech-lead |
| Code verdict (APPROVED / CHANGES REQUESTED) | **Tech-lead** | Tech-lead | — |
| Story accepted | **Human** | Product-owner (AC check + click-through script) | Tech-lead (evidence), UI/UX designer (UX review) |
| Commits | **Human** | Main session commits; tech-lead proposes messages | — |

No role overlaps another: the tech-lead no longer owns the backlog or product questions, and the main session no longer picks stories or
writes plans.

## Skills matrix: what each role must master

The *competency* column is the human-level skill set researched for each role. The *Claude skills / tools* column is what the agent loads to
apply it here.

| Role | Competencies required | Claude skills / tools |
|---|---|---|
| **Product owner** | Product vision and outcome-based roadmaps (Now / Next / Later) · story mapping and phase slicing · backlog management (DEEP, hygiene) · user stories (INVEST, 3 Cs) and acceptance criteria (Given / When / Then with real examples) · prioritisation (MoSCoW, value / effort, cost of delay) · UX writing, interaction design and WCAG 2.2 AA basics · domain knowledge of fashion BOM costing · acceptance testing · progress reporting (RAG, RAID, flow metrics) · stakeholder communication | Preloaded: `invory-product-owner`, `invory-costing-domain` · Read-only `git log` · WebSearch / WebFetch for research · Agent memory (project) |
| **UI/UX designer** | Interaction design and UX flows · information hierarchy for numeric, data-dense screens · design systems (tokens, shadcn components, consistency) · interaction states · WCAG 2.2 AA (contrast, targets, focus, keyboard, colour-only cues) · responsive and mobile-first layout (375 px) · UX writing and microcopy · design specs and mockups · heuristic and accessibility review | Preloaded: `invory-ux-design`, `bencium-controlled-ux-designer`, `web-design-guidelines`, `invory-product-owner`, `invory-costing-domain` · On demand: `frontend-design` · claude.ai design canvas (Artifact tool) · Agent memory (project) |
| **Tech lead** | Laravel architecture (modular monolith, actions, policies) · domain modelling of BOM costing · decimal precision · OWASP Top 10 for web apps (mass assignment, broken access control, XSS, SQLi) · DB design and indexing · N+1 detection · test strategy (unit/feature/arch/E2E) · vertical slicing and technical task breakdown · accessibility review · CI/CD · writing ADRs · technical-debt management · mentoring-quality feedback | Preloaded: `invory-costing-domain`, `invory-code-review` · Built-in: `/security-review`, `/code-review` · Boost MCP: `search-docs`, `database-schema`, `application-info` · Agent memory (project) |
| **Backend developer** | PHP 8.3+ (enums, readonly classes, strict types) · Laravel 13: Eloquent, migrations, FormRequests, Policies, API Resources, transactions, service container · Inertia 3 server side · `brick/math` BigDecimal · Pest 4 (datasets, `assertInertia`, arch tests) · Larastan / PHPStan generics · Pint · Wayfinder route naming | Preloaded: `invory-costing-domain`, `invory-laravel-backend` · Boost skills: `pest-testing`, `wayfinder-development`, `infer-conventions` · Boost MCP: `search-docs`, `database-schema`, `database-query`, `last-error`, `read-log-entries` |
| **Frontend developer** | React 19 (Rules of React, React Compiler, ref-as-prop) · TypeScript strict · Inertia 3 client (pages, `<Form>`, `useForm`, partial reloads, `preserveState`) · Wayfinder · Tailwind 4 · shadcn/ui + Radix · form UX for numeric data · `Intl.NumberFormat` · WCAG 2.2 AA · responsive and dark mode · Vitest + React Testing Library + user-event · Pest browser tests | Preloaded: `invory-costing-domain`, `invory-react-frontend` · Boost skills: `inertia-react-development`, `wayfinder-development`, `tailwindcss-development` · Boost MCP: `search-docs`, `browser-logs` |

Boost skills and MCP tools appear after `php artisan boost:install` (see [SETUP.md](SETUP.md)).
Project skills live in `.claude/skills/invory-*` and are committed. The three third-party design skills (`bencium-controlled-ux-designer`,
`web-design-guidelines`, `frontend-design`) are copied with their licence and a `SOURCE.md` (upstream commit, changes made). `invory-costing-domain` is the single source of truth for business maths.

## Workflow

```
 human ⇄ main session (liaison)
              │
   0. REFINE  ├──► product-owner: phase proposal + story cards (docs/stories/<id>-<slug>.md, Obsidian style)
              │       ui-ux-designer (when a story changes a screen): design spec + canvas boards → the PO decides
              │       Description · How it works today · Acceptance criteria — questions with options in its hand-off
              │◄── cards ──► human approves each card ──► status Ready ──► main session commits the docs on main
              │
   1. PLAN    ├──► tech-lead (planning-only): "Technical work" added under the card's criteria, same file
              │       simple → a few plain bullets · complex → steps S-xx-a, -b…, contract, order, risks
              │◄── card ──► human approves the Technical work ──► status Planned ──► main session commits the card on main
              │       (questions for the PO go back through step 0)
              │
   2. BUILD   ├──► tech-lead (execute): dispatch ──► backend-developer ┐ same message = parallel
              │                                   ──► frontend-developer ┘
              │       in a worktree session cut from main after the docs commit
              │                         review (all gates + parity) · fix loop, max 3 rounds
              │◄── delivery report: verdicts, gates, AC → test evidence, proposed commits
              │
   3. ACCEPT  ├──► product-owner: acceptance review (each AC vs evidence) + click-through script
              │       ui-ux-designer: UX review of the delivered screens (spec, states, WCAG) → folded into the PO's review
              │◄── human clicks through and accepts (or a gap goes back to step 2 / a new story)
              │
   4. CLOSE   └── main session commits per acceptance criterion (your yes) ──► product-owner updates BACKLOG / STATUS / ROADMAP
```

Small technical items from `TECH-DEBT.md` in a phase's tech allowance skip step 0 and step 3 (no click-through): the tech-lead writes
the whole card (`F-xx`, same format), you approve it once, and it's built, reviewed and committed.

**Flow limits:** at most two stories between `Planned` and `Acceptance` at once. The product-owner flags any story older than its size suggests.

### Cards and planning on the main checkout, building in a worktree
You read `docs/` in Obsidian from the **main checkout** (`G:\Projects\Invory\docs`). The app only lets a session edit files inside its
own folder, and a worktree's copy of `docs/` is invisible to Obsidian. So steps 0–1 (cards, Technical work, ROADMAP, BACKLOG, STATUS) run
from a session opened on `G:\Projects\Invory`, and only step 2 (building) runs in a worktree session.

Each time you approve cards (→ `Ready`) or a card's Technical work (→ `Planned`), the main session commits exactly those doc files on
`main` straight away: your approval is the yes for that commit. The build worktree is cut from `main` only after the card and its
Technical work are committed, so it starts with them. Docs changed during the build and acceptance (the card's Progress lines,
TECH-DEBT, acceptance ticks, BACKLOG and STATUS) are committed on the build branch and show up in Obsidian when it merges into `main`.

### One card, two authors (`docs/stories/<id>-<slug>.md`)
You read cards in Obsidian (`docs/` in the main checkout is the vault). Each card is one file with two parts:

| Part | Written by | What it holds |
|---|---|---|
| Properties, **Description**, **How it works today**, **Acceptance criteria** | product-owner, you approve → `Ready` | Only these three parts: what changes for the user and why, today's behaviour, and 3–7 ticked-at-the-end criteria with real numbers and exact wording. No "what's next", questions or out-of-scope sections. |
| **Technical work** (after a horizontal rule) | tech-lead, you approve → `Planned` | **Simple** work: a few plain bullets and which test proves each criterion. **Complex** work (money maths, database, business rule, contract, more than two commits, real risk): a plain summary, then steps, contract, decisions, order and risks. |

```markdown
---
id: S-xx
title: See which material stops a product
type: feature
phase: 4
priority: Must
size: S
status: Ready
created: YYYY-MM-DD
tags:
  - story
  - phase-4
---

# S-xx · See which material stops a product

> [!abstract] Description
> As a **workshop owner**, I want **to see which material limits a product**, so that **I know what to restock first**.

> [!info] How it works today
> The Denim Jacket page says "Can produce 112" but not which material stops it.

## Acceptance criteria
- [ ] **AC-1 · Bottleneck named.** Given the Denim Jacket (can produce 112), when you open its page, then you see "Limited by Metal
  shank button 17mm".

---

## Technical work
> [!tip] Simple · NON-CRITICAL · frontend · 1 commit
> Tech-lead · YYYY-MM-DD · proposed

- Show the bottleneck the page already receives under the "Can produce" figure.
- **Proof:** AC-1 → product page test "shows the bottleneck material".
```

The full rules are in the [`invory-product-owner` skill](../.claude/skills/invory-product-owner/SKILL.md) §4 (the product-owner's part,
Definition of Ready and Done in §6–§7) and in the [tech-lead agent](../.claude/agents/tech-lead.md) §Technical work (the simple / complex
rule and both layouts). Questions from either lead come to you through the main session, never inside the card. When a story is `Done`,
the product-owner ticks each criterion with its date and commit (`✅ 2026-10-15 · 4644a08`).

Cards and plans that existed on 2026-10-14 keep their old layout, including those on hold or unfinished; they aren't converted.
Earlier technical plans stay in [`docs/plans/`](plans/README.md) as history.

The main session presents the Technical work by linking the card and saying whether it is simple or complex, with its steps, risks and
questions. Execution doesn't start until you say "approved" (or equivalent). Changes go back to the tech-lead via `SendMessage`; questions
about behaviour go to the product-owner.

### Rules of engagement
1. **Card first, then contract first.** No Technical work without an approved card (tech-allowance items get an `F-xx` card from the
   tech-lead; S-29 runs from its earlier plan). In complex Technical work the contract (routes, props, `domain.ts`, PHP APIs) is frozen
   before dispatch, and the backend writes the types first, so the frontend can build in parallel.
2. **One shared working tree, so disjoint files per wave.** Agents touch only the files in their brief and run focused checks while others work.
   The tech-lead runs the full gates once the wave is complete.
3. **Git:** one branch per phase (`phase/4-release-readiness`), **one commit per acceptance criterion** (not per story), staged from that
   criterion's file list. Conventional Commits (`feat(materials): …`, `test(costing): …`). You approve every commit, push and merge. Agents never commit.
   Approved cards and Technical work are the exception: they're committed straight to `main` before the build branch exists (see above).
4. **Handoff reports are mandatory** and use the exact template in each agent file. The tech-lead re-runs every gate. Pasted output is not proof.
5. **Disagreements:** technical ones are settled by the tech-lead; behaviour, UX, scope and priority by the product-owner, with you deciding
   anything the card doesn't settle. The UI/UX designer proposes and reviews; where it disagrees with the product-owner, the main session
   puts both options to you. Neither lead edits the other's documents, or the other's part of a card.
6. **Domain changes** (a formula, rounding, a new edge case) are product decisions: the product-owner proposes the rule, you decide, it
   becomes an acceptance criterion, and the tech-lead updates `invory-costing-domain` before any code changes. Code follows the skill,
   never the other way round.
7. **IDs are never reused.** The product-owner numbers stories `S-xx`; the tech-lead numbers technical steps `S-xx-a…` and debt `F-xx`.
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

For complex work the tech-lead writes *Steps* in the card's Technical work as a padded overview table (Step · What it does · Proves ·
Lane · Who · Size), then one block per step with its commit, its tests and a folded file list. Simple work needs no Steps.

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
- "Design the order sizes screen." → the ui-ux-designer writes a design spec with options and boards; the product-owner turns your choice into the card.
- "Plan S-31." → the tech-lead adds the Technical work under S-31's approved card (simple or complex), then stops for your approval.
- "Approved, build it." → the tech-lead dispatches, reviews and returns a delivery report; then the product-owner prepares your acceptance check.
- "Where are we?" → the product-owner refreshes `docs/STATUS.md`.
