# S-42 — Screen readers hear "112 units", not "112units"
> Phase 4 · Priority Could (after S-32; slips to Phase 5 first) · Size S · Status **Ready** (approved by the human 2026-10-14; the tech-lead
> plans it after S-32) · Type bug (accessibility)
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Found in: [S-08](S-08-golden-path-test-and-demo-data.md)
> acceptance review (2026-10-14) · Technical plan: added by the tech-lead

## Story
As a workshop owner who uses a screen reader, I want the product page to read "112 units" with a space, so that I hear a number and a
unit, not a made-up word.

## Why it matters
Invory aims at WCAG 2.2 AA. On the product page, the number and its unit word ("units") are separated only visually. Assistive
technology, copy-and-paste and the automated golden-path test all get "112units". It's a small fix, and it removes a known flaw before
you sign the MVP off (S-30).

## Acceptance criteria
AC-1 **Can produce now.** Given the demo Denim Jacket (can produce 112), when a screen reader reads the product page's "Can produce now"
card, it hears "Can produce now 112 units". The screen looks exactly as it does today: big number, smaller unit word.

AC-2 **Sold · last 30 days.** Given the demo Denim Jacket (38 sold in the last 30 days), the "Sold · last 30 days" card reads "38 units".
A product with one unit sold reads "1 unit", with no "s".

AC-3 **The golden path checks it.** The automated golden-path test (S-08) expects "Can produce now 112 units", with the space, and stays
green.

AC-4 **Nothing else moves.** "Can't produce" and "No composition", the colours, sizes and spacing of the cards, and every other figure on
the page stay as they are, at phone width (375 px) and in dark mode.

## Example with real numbers
Demo workshop, Denim Jacket: "Can produce now **112 units** from current stock" · "Sold · last 30 days **38 units**, TND 3,382.000 in sales"
(38 × TND 89.000). Today a screen reader and a copy-paste give "112units" and "38units".

## What you will see (UX)
- No visible change. The difference is what a screen reader says and what you get when you copy the figure.
- Wording stays: "Can produce now", "from current stock", "Sold · last 30 days", "in sales", "unit" / "units".

## Out of scope
- Other accessibility checks of the product page or other pages: S-30's walkthrough covers them by hand. If the tech-lead finds the same
  "number and unit joined only visually" pattern elsewhere while sizing, it's listed in the plan, and fixed here only if you agree.
- Changing how quantities with a material unit are shown (e.g. "250 m"): they already have a space.

## Questions for you
None. You set the priority on 2026-10-14 (Phase 4 Could, after S-32).

## For the tech-lead's sizing
- The pattern was found in the product page's two hero cards. Please confirm it appears nowhere else, or list where it does.

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 Can produce now reads "112 units" | | |
| AC-2 Sold reads "38 units" / "1 unit" | | |
| AC-3 Golden path expects the space | | |
| AC-4 Nothing else moves | | |
