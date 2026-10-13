---
name: invory-code-review
description: Invory tech-lead review procedure — quality gates to run, the architecture/security/performance/testing/UX checklist for Laravel 13 + Inertia 3 + React 19 changes, severity definitions, and the exact review report format (APPROVED / CHANGES REQUESTED). Use when reviewing a story, a diff or a pull request in this repository.
---

# Invory — code review procedure

You are reviewing as the tech lead. Your job is to protect **correctness of the numbers**, **architecture boundaries**,
**security**, and **test quality**, in that order. Be specific, cite `file:line`, and propose the fix. Don't rewrite the code yourself.

## 1. Establish scope
- `git status` and `git diff --stat` (plus `git diff` for content) against the base branch, or against the last approved commit.
- Re-read the story's acceptance criteria in `docs/BACKLOG.md` and the developer's handoff report.
- Read the lane you tagged in the brief (CRITICAL or NON-CRITICAL) and pick the matching checklist in §3.
- Note any **contract changes** (Resources ↔ `resources/js/types/domain.ts`, route names) and check both sides moved together.
- **Upgrade the lane** if the diff touches anything from the CRITICAL list in `docs/TEAM.md` (money/quantity, migration FK, policy, business-rule FormRequest, transaction, domain code, contract). A NON-CRITICAL tag can't shield CRITICAL code.

## 2. Run the gates yourself (never trust a pasted result)
```
composer test                 # pint --test + larastan + pest
npx vp test                   # vitest
npm run check                 # oxfmt + oxlint (denyWarnings)
npm run types:check           # tsc --noEmit
npm run build                 # production bundle
```
For stories touching migrations, queries or seeders, also run the **PostgreSQL parity suite** when Docker is up (`docs/SETUP.md` §5):
```
docker compose exec -u sail -e DB_CONNECTION=pgsql -e DB_DATABASE=testing laravel.test php artisan test --compact
```
A red gate is an automatic **BLOCKER**. Record each gate's result in the report. Write "not run (Docker down)" for parity if that applies.

## 3. Checklist

### 3a. Fast-lane checklist (NON-CRITICAL subtasks only)

Use this when every row of the subtask is pure CRUD without business rules, copy, labels, layout, pure styling, shadcn wrappers, empty / loading /
error states, toast wording, formatter displays that call tested primitives, or docs. All must still be green:

- [ ] All gates pass (§2). PostgreSQL parity if the diff touches migrations, queries or seeders.
- [ ] **No CRITICAL marker in the diff** — grep for `BigDecimal`, new `FormRequest` rules beyond type/required, new policy methods, new migration FKs, edits to `resources/js/types/domain.ts`, new transactions, new files under `app/Domain/`. If any show up, abort the fast lane and run §3b.
- [ ] Acceptance criteria met and visible in the diff.
- [ ] Hygiene: no `dd`, `dump`, `console.log`, commented-out code, generated files edited.
- [ ] Test budget respected (not padded, not short).

If all boxes are ticked and there are no CRITICAL markers, approve. Otherwise go to §3b.

### 3b. Full checklist (CRITICAL subtasks)

**Domain correctness (highest priority)**
- [ ] Formulas, rounding and edge cases match `invory-costing-domain` exactly. The worked example is a test with exact string assertions.
- [ ] No float anywhere in money or quantity paths (PHP: `float`, `(float)`, `round()`, arithmetic on numeric strings; TS: `Number()`, `parseFloat`, `*`/`+` on decimals).
- [ ] Capacity floors (`RoundingMode::Down`), empty BOM → `null`, requirements aggregated per material.

**Architecture**
- [ ] Layer rules from `docs/ARCHITECTURE.md` §3: thin controllers, Actions for writes, pure `App\Domain\Costing`, Resources without queries.
- [ ] Naming and placement follow `invory-laravel-backend` / `invory-react-frontend`.
- [ ] No new dependency without a reason in the handoff. A significant new dependency or pattern needs an ADR.

**Security**
- [ ] Every route sits behind `auth` (+ `verified`); a Policy is checked for reads **and** writes.
- [ ] Persists `validated()` only; explicit fillable; validation covers type, range, scale, `distinct`, `exists` without trashed rows.
- [ ] No raw SQL interpolation, no `dangerouslySetInnerHTML`, no secrets in code, no sensitive data in props (e.g. a full `User` model).
- [ ] Delete rules enforced server-side (material in use).

**Data and performance**
- [ ] Migrations are reversible, with FKs (`cascadeOnDelete` / `restrictOnDelete` as specified), indexes on filter/sort columns, and correct decimal scales.
- [ ] No N+1 (eager loading; `shouldBeStrict` would throw in tests). Pagination on lists. Transactions around multi-row writes.

**Tests (match the brief's test budget — not more, not less)**
- [ ] Each acceptance criterion maps to at least one test. Validation is covered with **datasets** (not one test per rule). Guest access is tested. Props-contract assertions use `assertInertia` on the shape (one row, not per column).
- [ ] Backend tests follow the `docs/TEAM.md` test rubric: domain full-branch, 1 policy dataset, 1 happy + 1 rollback + 1-per-rule action, 1 happy + 1 validation dataset + 1 guest per route, 1 Resource contract, 1 N+1 per index.
- [ ] Frontend tests follow the rubric: formatters per rule, form editor happy + error + edge, 1 page smoke per page, 1 E2E golden path per feature.
- [ ] No test padding (framework glue, per-column assertions, snapshots, duplicates covered by a dataset, implementation-detail queries).
- [ ] Tests assert behaviour, not implementation; no sleeping, no reliance on test order; time is controlled with `travelTo` / `vi.setSystemTime`. Factories valid-by-default.
- [ ] Frontend tests query by role or label (so a11y comes along), cover component branches, and cover the BOM editor interactions.

**UX and accessibility**
- [ ] Empty, loading and error states; flash toasts; confirm dialog before delete.
- [ ] Labels, `aria-invalid`/`aria-describedby` on errors, keyboard operable, status not shown by colour alone, works at 375 px and in dark mode.
- [ ] Copy uses the domain glossary (Material, Product, Composition, Can produce, Bottleneck).

**Hygiene**
- [ ] No `dd`, `dump`, `ray`, `console.log` or commented-out code. No TODO without a backlog reference. Generated files (Wayfinder, shadcn ui) are untouched.

Optionally run the built-in `/security-review` on large or risky diffs.

## 4. Severity
| Level | Meaning | Merge? |
|---|---|---|
| **BLOCKER** | Wrong numbers, security hole, data loss risk, red gate, broken acceptance criterion | No |
| **MAJOR** | Architecture violation, missing tests for a criterion, N+1, accessibility failure on a core flow | No |
| **MINOR** | Naming, small duplication, missing edge-case test, copy issue | Yes, with a follow-up in the backlog |
| **NIT** | Taste | Yes |

Verdict: **APPROVED** only if there are zero BLOCKER and zero MAJOR findings.

## 5. Report format (return exactly this)
```
## Review — <story id> <subtask id> <title>
**Lane:** CRITICAL | NON-CRITICAL (upgraded from NON-CRITICAL because …)
**Verdict:** APPROVED | CHANGES REQUESTED

### Gates
| Gate | Result |
|---|---|
| composer test | ✅ 48 passed / ❌ <summary> |
| npx vp test | … |
| npm run check | … |
| npm run types:check | … |
| npm run build | … |
| PostgreSQL parity (if DB touched) | … / not run (Docker down) |

### Findings
1. **[BLOCKER]** `app/Domain/Costing/CostingCalculator.php:42` — capacity uses HALF_UP so 126.9 → 127. **Fix:** `dividedBy($req, 0, RoundingMode::Down)`. **Owner:** backend-developer
2. **[MAJOR]** …

### Contract check
Resources ↔ domain.ts: in sync / drift: <details>

### Budget check
Lines: +<prod> / +<tests> (brief said ~<N>). Test count: <N>. Over budget / within / under — <reason if diverged>.

### Follow-ups for backlog (MINOR/NIT accepted)
- …
```
Also record recurring mistakes in your agent memory so that future reviews catch them faster.
