# Story cards (product-owner)

One file per story, `<id>-<slug>.md` (e.g. `S-31-deferred-retry.md`), written by the **product-owner** for the human to read and approve.
Template, writing rules, Definition of Ready and Done: [`invory-product-owner` skill](../../.claude/skills/invory-product-owner/SKILL.md) §4–§7.

- A card is `Draft` while the product-owner writes it, and `Ready` once **the human approves it**. Only `Ready` cards go to the tech-lead.
- After `Ready`, a change to the acceptance criteria needs the human's yes.
- The tech-lead links its technical plan (`docs/plans/<id>-<slug>-plan.md`) at the top of the card. The product-owner fills in the
  card's "Acceptance check" at the end.

| Card | Phase · priority · size | Status |
|---|---|---|
| [S-30 — Walk through every MVP screen and sign it off](S-30-mvp-acceptance-walkthrough.md) | 4 · Must · S | `Ready` (approved 2026-10-14; walked as soon as S-29 lands) |
| [S-08 — One automated test walks the whole journey, and a fresh install shows the demo workshop](S-08-golden-path-test-and-demo-data.md) | 4 · Must · M | `Acceptance` (delivered 2026-10-14; waits for the click-through and the green `golden-path` job) |
| [S-31 — A section that fails to load offers "Try again"](S-31-deferred-section-try-again.md) | 4 · Should · S | `Ready` (approved 2026-10-14) |
| [S-32 — A duplicate SKU saved at the same moment shows a clear message](S-32-duplicate-sku-clear-message.md) | 4 · Should · S | `Ready` (approved 2026-10-14) |
| [S-40 — Fill a fresh install with a realistic catalogue of 100 products](S-40-realistic-catalogue-100-products.md) | 4 · Could · M | `Ready` (approved 2026-10-14) |
| [S-41 — A real photo on every item of the demo catalogue](S-41-real-photos-for-the-catalogue.md) | 4 · Could · M | `Ready` (approved 2026-10-14; work starts once the human's Pexels key exists) |
| [S-42 — Screen readers hear "112 units", not "112units"](S-42-screen-readers-hear-units.md) | 4 · Could · S | `Ready` (approved 2026-10-14) |

S-29 (reliable dev environment) has no card: it runs from its plan, which predates story cards.
