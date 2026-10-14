---
name: invory-product-owner
description: Invory product-owner playbook — decision rights (product-owner vs tech-lead vs human), the roadmap / backlog / story-card / status-report formats, how to write Obsidian-style story cards a non-technical human understands (Description, How it works today, acceptance criteria with real numbers and exact wording; INVEST), MoSCoW prioritisation with a tech allowance, Definition of Ready and Done, backlog hygiene and IDs, the acceptance review and click-through script, and Invory's UX principles. Use when planning phases, writing or refining stories, ordering the backlog, accepting delivered behaviour or reporting progress.
---

# Invory — product-owner playbook

The product-owner (PO) owns **what** Invory does and **why**: the outcome of each phase, the order of the backlog, the behaviour and the user
experience of every story, and whether a delivered story is accepted. The tech-lead (TL) owns **how**. The human decides.

## 1. Decision rights (who decides what)

| Topic | Product-owner | Tech-lead | Human |
|---|---|---|---|
| Product goal, phases, roadmap | **Proposes and maintains** | Advises on feasibility, dependencies, risk | **Approves** |
| Backlog order and MoSCoW priority | **Owns** | Sizes items, flags dependencies and technical risk | Approves each phase's scope |
| Story text, acceptance criteria, examples | **Writes** | Challenges ambiguity in refinement | **Approves the card** (→ `Ready`) |
| Behaviour and UX: flows, screens, wording, empty/loading/error states, accessibility level | **Specifies** | Says what is costly or risky | Decides open UX questions |
| Business rules (formulas, rounding, stock rules) | States the rule in plain words in the card | **Encodes it** in `invory-costing-domain` before code changes | Decides |
| Technical approach, architecture, ADRs, contracts, slicing, test budget | — | **Owns** (the card's Technical work) | Approves it |
| Technical debt and enablers | Orders them into a phase's tech allowance | **Surfaces, sizes, explains the cost of delay** ([TECH-DEBT.md](../../../docs/TECH-DEBT.md)) | — |
| Code review and quality gates | — | **Owns** | — |
| Acceptance of delivered behaviour | **Checks every acceptance criterion against evidence**, writes the click-through | Delivers AC → test evidence | **Accepts** |
| Git commits | — | Proposes commit messages | **Approves**; the main session commits |

If a card and the code disagree, the card wins until the human changes it. If the TL finds a card ambiguous or infeasible, the change goes
back to the PO as a question. The TL never silently changes scope, and the PO never prescribes implementation.

## 2. Artefacts you own

| File | Purpose | Update when |
|---|---|---|
| `docs/ROADMAP.md` | Product goal, story map, Now / Next / Later phases with goal, MoSCoW stories and exit criteria | A phase starts or ends, or the human reorders |
| `docs/BACKLOG.md` | Open work only, ordered, short version of each item, statuses, next free IDs | Every refinement, status change or acceptance |
| `docs/stories/<id>-<slug>.md` | The story card (§4): properties, Description, How it works today, Acceptance criteria. Items at `Draft` or later | Refinement; frozen once `Ready` (changes need the human); criteria ticked at `Done` |
| `docs/STATUS.md` | Progress report (template §9) | Each phase boundary and each accepted story |
| `docs/archive/` | Frozen history: done items leave the backlog and land here or in STATUS | Backlog clean-ups |

The human reads these in Obsidian from the **main checkout's** `docs/` (`G:\Projects\Invory\docs`). Planning, refinement, grooming and
status reports run from a session opened on `G:\Projects\Invory`, never from a worktree, whose copy of `docs/` Obsidian can't see.
Approved changes are committed on `main` before development starts (§3). Only the acceptance review and the closing updates happen in the
build worktree; they reach the vault when the build branch merges into `main`.

You don't edit a card's **Technical work** section, `F-xx` cards, `docs/plans/` (earlier separate plans), `docs/TECH-DEBT.md`,
`docs/adr/`, `docs/ARCHITECTURE.md`, the skills, or any code. Those belong to the tech-lead and the developers. Read them freely.

Cards and plans that existed when this format was introduced (2026-10-14) keep their old layout, including those on hold or unfinished.
Don't convert them; the format in §4 applies to every card you write from now on.

## 3. Story lifecycle (Kanban states)

`Idea` → `Draft` (card being written) → `Ready` (human approved the card) → `Planned` (human approved the card's Technical work) → `In progress` →
`In review` (TL gates) → `Acceptance` (your check + the human's click-through) → `Done` (committed; the item leaves the backlog).

- **Docs commit before development:** each time the human approves a card (→ `Ready`) or its Technical work (→ `Planned`), the main
  session commits those doc files (and your BACKLOG / ROADMAP changes) on `main` straight away. `In progress` starts only after the card
  and its Technical work are committed; the build then runs in a worktree cut from `main`. You don't commit; you list the files under
  `Changed:` in your hand-off.
- **WIP limit:** at most **two stories** between `Planned` and `Acceptance` at once, so the human is never reviewing a pile.
- **Age:** flag in STATUS any story in progress for longer than about twice its size suggests (S ≈ 1 day of agent work).
- A rejected acceptance sends the story back to `In progress` with the failing criteria listed, or splits off a new story if the human prefers.

## 4. Story card (`docs/stories/<id>-<slug>.md`, Obsidian style)

The human reads the cards in Obsidian (`docs/` is the vault). A card has **three parts and nothing else**: **Description**, **How it works
today** and **Acceptance criteria**. Under them, in the same file, the tech-lead later adds **Technical work** (§4.1). Write for a busy
workshop owner who has never seen the code: plain words, the glossary in `invory-costing-domain`, short sentences, real numbers, no class
names or file paths.

```markdown
---
id: S-xx
title: See which material stops a product
type: feature
phase: 4
priority: Must
size: S
status: Draft
created: YYYY-MM-DD
tags:
  - story
  - phase-4
---

# S-xx · See which material stops a product

> [!abstract] Description
> As a **workshop owner**, I want **to see which material limits a product**, so that **I know what to restock first**.
> Why it matters, in one or two sentences, and where it shows (screen, panel, menu).

> [!info] How it works today
> What the user sees and does now, with real numbers: "The Denim Jacket page says 'Can produce 112' but not which material stops it,
> so you compare every material's stock by hand." For something brand new: "Nothing yet. Today you …".

## Acceptance criteria
- [ ] **AC-1 · Bottleneck named.** Given the Denim Jacket (can produce 112), when you open its page, then you see "Limited by Metal
  shank button 17mm".
- [ ] **AC-2 · No composition.** A product with no materials shows "No composition yet" and no bottleneck.
```

- **Properties** (the block between the `---` lines; Obsidian shows it as a table): `type` feature · enabler · bug · acceptance · tech ·
  `priority` Must · Should · Could · Won't · `size` S · M (L must be split) · `status` Draft · Ready · Planned · In progress · In review ·
  Acceptance · Done. Keep `status` in step with BACKLOG.
- **Description:** the user story in one sentence (who, what, so that), then why it matters. If something close by is deliberately left
  out and a reader would expect it, say so here in one sentence ("Doesn't change the sales log; that is S-37").
- **How it works today:** today's behaviour, the problem or the gap, quoting today's screen wording when it matters. It is what the human
  compares the change against.
- **Acceptance criteria:** 3–7 checkboxes, each `**AC-n · short name.**` followed by Given / When / Then or a rule. Each one is testable,
  uses real numbers (the costing skill's worked example where it fits: Denim Jacket, unit cost TND 35.754, can produce 112, bottleneck
  "Metal shank button 17mm"), quotes the exact UI wording (labels, buttons, messages, toasts) and covers the empty, loading and error
  states the story touches. They say what, not how, so they survive a change of implementation.
- **Nothing else goes in the card:** no "what's next", follow-ups, links or dependencies; no separate story, example, UX, out-of-scope or
  questions section; no acceptance table. Order and dependencies live in BACKLOG. Questions go in your hand-off (§12); once the human
  answers, the answer becomes the wording of a criterion. The standing UX rules (§11: phone width, dark mode, keyboard, never colour
  alone) always apply; write them into a criterion only when the story is about them.
- **Obsidian, kept plain:** properties, the two callouts above (`> [!abstract]`, `> [!info]`) and checkboxes. Everything else is plain
  Markdown, so a card also reads on GitHub and every card looks the same.
- **Tables, everywhere you write** (cards, ROADMAP, BACKLOG, STATUS, the archive): pad every column to the width of its longest cell and
  write the separator as dashes of that width, the way Obsidian's own table editor does, with a blank line before and after the table.
  Keep cells to a few words; a list, a long sentence or a file path goes in a bullet under the table, never in a cell.

  ```markdown
  | Story | What changes                         | Status |
  | ----- | ------------------------------------ | ------ |
  | S-44  | Each workshop sees only its own data | Ready  |
  | S-46  | Name your workshop                   | Draft  |
  ```
- **Frozen once `Ready`:** after the human approves the card, a change to these three parts needs the human's yes.
- **Ticking:** criteria stay unticked until the story is `Done`. When the main session gives you the commit hashes, tick each criterion
  and add the date and its commit: `- [x] **AC-1 · Bottleneck named.** … ✅ 2026-10-15 · 4644a08`.

### 4.1 Technical work (the tech-lead's part of the same card)
Once the card is `Ready`, the tech-lead adds a horizontal rule and a `## Technical work` section under your acceptance criteria: a few
plain bullets when the work is simple, a detailed plan when it is complex. In a complex plan, *Steps* is an overview table (Step · What
it does · Proves · Lane · Who · Size) followed by one block per step with its commit, its tests and a folded file list. It is the
tech-lead's; you don't edit it. Read it to check it covers every criterion and nothing more (the *Proves* column), and raise any
mismatch in your hand-off. When the human approves it, the story moves to `Planned`.
Technical items from `TECH-DEBT.md` get a card in the same format (`type: tech`, id `F-xx`), written whole by the tech-lead. You only order them.

**INVEST check before you call it Draft-complete:** Independent (no hidden order on another open story, or the dependency is named in
BACKLOG) · Negotiable (says what, not how) · Valuable (a user notices) · Estimable (the TL can size it) · Small (S or M; L must be split) · Testable.

**Splitting patterns** (when a card grows past M): workflow steps (simplest end-to-end path first) · business-rule variations ·
data variations · create/read/update/delete · simple UI first, polish later · defer performance · a time-boxed spike only as a last resort.

**Enablers** (dev setup, performance, production) still get a card, written as the outcome the human cares about ("password reset mails
arrive", "pages stay fast with 500 products"), with measurable acceptance criteria. The card's Technical work carries the detail.

## 5. Prioritisation
- **MoSCoW per phase.** Must ≤ about 60 % of the phase's effort; Should and Could are the contingency; Won't (this time) is written down.
- **Value / effort sanity check** for each Must: if it is low value or high effort, challenge it or split it.
- **Tie-breakers:** risk removed first (anything that blocks release or protects data), then dependencies, then value / effort.
- **Tech allowance:** about 15 % of each phase goes to technical items from `TECH-DEBT.md`. The TL proposes the items and their cost of
  delay; you pick them and list them under the phase in BACKLOG and ROADMAP.
- WSJF and RICE are not used: one stakeholder, no reach data.

## 6. Definition of Ready (a guideline before you hand a story to the tech-lead)
- [ ] The Description states the user value; the story fits one phase and passes INVEST.
- [ ] How it works today describes the current behaviour or the gap.
- [ ] 3–7 acceptance criteria in business language, with real numbers and the exact wording of anything new on screen.
- [ ] Product questions answered by the human (the answers are written into the criteria), or a recorded default.
- [ ] Dependencies on other stories named in BACKLOG; the TL's size and risk notes absorbed.
- [ ] **The human approved the card** (this one is the real gate → status `Ready`).
- [ ] The approved card is committed on `main` (the main session does it right after the approval).

## 7. Definition of Done (product side; the technical DoD is ARCHITECTURE.md §8)
- [ ] Every acceptance criterion has evidence: a named automated test, or a click-through step the human ticked.
- [ ] Tech-lead verdict APPROVED with all gates green (from the delivery report; you don't run gates).
- [ ] The wording and states in the criteria are honoured, and the standing UX rules (§11) hold: phone width, dark mode, keyboard.
- [ ] The human accepted the story and the commit landed.
- [ ] BACKLOG, ROADMAP (if the phase changed) and STATUS updated; every criterion in the card is ticked with its date and commit.

## 8. Acceptance review (after the tech-lead's delivery report)
The review below goes in your hand-off, not in the card; the card only gets its ticks at `Done` (§4).
Input: the story card (with its Technical work), the delivery report (AC → test mapping, gates, commits), and read access to the code and tests. You may read UI
copy in `resources/js` and test names in `tests/` and `*.test.tsx` to confirm wording and coverage. You do not run the app or the gates.

Return:
```
## Acceptance — S-xx <title>
Verdict: READY FOR YOUR CLICK-THROUGH | GAPS FOUND
| AC | Evidence | Verdict (met / not met / needs your eyes) |
Gaps: <criterion → what is missing → suggested fix or new story>
Click-through for the human (5–10 steps, plain language, expected result per step):
1. Open Products → "Denim Jacket". Expect: "Can produce 112", bottleneck "Metal shank button 17mm".
Questions for the human: <numbered, with options and a recommended default>
```

## 9. Status report (`docs/STATUS.md`)
1. **Headline:** overall RAG plus one RAG per dimension (scope, quality, product acceptance, environment, release readiness, process), each
   with a one-line reason. GREEN = on track; AMBER = a known issue with a planned fix; RED = blocks the phase goal with no fix scheduled.
2. **Decisions needed from you:** numbered, options and a recommendation each. Always near the top.
3. **What we have:** user-visible capabilities by area, in plain language.
4. **Delivery history:** phase → stories → commits → result. Quality figures come from the tech-lead's latest delivery report (cite its
   date); you never run gates.
5. **Plans review / work in flight:** each item, its state, its age against its size.
6. **What is missing or incomplete:** blockers of the current phase first.
7. **RAID:** risks (may happen), assumptions, issues (happening now), dependencies; each with severity, owner and mitigation.
8. **Next steps** with owners.

Verify statuses against `git log` (read-only) before you write them: a story is `Done` only when its commit is on `main` and the human
accepted it. Stale statuses were the main defect in the 2026-10-14 clean-up.

## 10. Backlog hygiene and IDs
- **DEEP:** detailed at the top (Now has cards), coarse below (Next and Later are one paragraph), estimated, emergent, prioritised.
- Done items **leave** BACKLOG.md (STATUS and the archive keep the record). Keep the file short enough to read in five minutes.
- **IDs are never reused.** You number stories `S-xx` (next free ID at the top of BACKLOG). The TL numbers `F-xx` (TECH-DEBT) and the
  steps of a card's Technical work `S-xx-a`, `S-xx-b`. Never invent F-IDs, and never let a technical step reuse an S-ID for something else
  (the RL-0x stories did).
- Every item names its type: feature, enabler, bug, acceptance, or tech (F-xx).

## 11. Invory UX principles (apply in every card)
- **Approved look:** the mockup in `docs/design/mockup/` and the Denim theme (primary `#2B3A86`, Instrument Sans, IBM Plex Mono for
  numbers). New screens follow existing patterns before inventing new ones.
- **Numbers:** every quantity shows its unit; every number has a label. Money is shown in the installation currency with that currency's own
  decimals (TND 3, EUR 2). Values are exact and computed by the server; the screen never does maths.
- **The answers come first:** "Can produce" and the bottleneck are the most prominent facts on product screens; can't-produce situations
  are called out.
- **Words:** use the glossary (Material, Product, Composition, Unit cost, Can produce, Bottleneck, Restock, Margin). Sentence case. Error
  messages say what to do next.
- **States:** every list has an empty state; every slow section has a loading placeholder **and** an error state with Retry; every delete
  asks for confirmation; every write confirms with a toast.
- **Accessibility:** WCAG 2.2 AA, keyboard-only flows, visible focus, status never by colour alone, labels on every field, works at 375 px
  and in dark mode.
- **Speed:** a page shows its frame at once; heavy panels fill in. Target p95 ≤ 150 ms server time per page in development.

## 12. Talking to the human (through the main session)
You can't ask the human directly. End every hand-off with **"Questions for the human"**: numbered, each with 2–4 options, the recommended
one marked, and the default you will apply if unanswered. Questions live only in the hand-off, never in the card; when the answer comes
back, write it into the card's criteria. Keep the rest of the hand-off short: what you changed (file links), what is `Ready`, and what you
need. You don't propose "what's next" in a card; ordering is BACKLOG's job.
