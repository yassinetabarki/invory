# Story cards (product-owner + tech-lead)

One file per story, `<id>-<slug>.md` (e.g. `S-31-deferred-retry.md`), styled for Obsidian (`docs/` is the vault). Each card has two parts:

1. **The product-owner's part**, for the human to read and approve: properties, **Description**, **How it works today** and
   **Acceptance criteria**, and nothing else. Rules: [`invory-product-owner` skill](../../.claude/skills/invory-product-owner/SKILL.md) §4–§7.
2. **Technical work**, added by the **tech-lead** under the criteria after a horizontal rule: a few plain bullets when the work is simple,
   a detailed plan when it is complex. Rules: [tech-lead agent](../../.claude/agents/tech-lead.md) §Technical work.

- A card is `Draft` while the product-owner writes it, and `Ready` once **the human approves it**. Only `Ready` cards go to the tech-lead.
- After `Ready`, a change to the acceptance criteria needs the human's yes. The story is `Planned` once the human approves its Technical work.
- Questions from either lead come to the human through the main session, never inside the card.
- When a story is `Done`, the product-owner ticks each criterion with its date and commit.
- Technical items from `TECH-DEBT.md` get an `F-xx-<slug>.md` card in the same format, written whole by the tech-lead.
- Cards that existed on 2026-10-14, including those on hold or unfinished, keep their old layout (story, UX, out of scope, questions,
  acceptance check, plan in `docs/plans/`). They aren't converted.
- A paused card reads "`Ready`, on hold since <date>". Since 2026-10-15 (D-12) Phase 5 is under way and the rest of Phase 4 is on hold.

| Card                                                                                                                                     | Phase · priority · size | Status                                                                                                   |
| ---------------------------------------------------------------------------------------------------------------------------------------- | ----------------------- | -------------------------------------------------------------------------------------------------------- |
| [S-48 — Record customer orders with one or more products](S-48-record-customer-orders.md)                                                | 5 · Must · M            | `Done` (accepted 2026-10-15; e3b7ae7, 5af8ed1)                                                           |
| [S-49 — Track each product of an order through cutting, ready to ship and shipped](S-49-track-cutting-ready-shipped.md)                  | 5 · Must · M            | `Ready` (approved 2026-10-16; ships with S-53, D-11)                                                     |
| [S-53 — Starting to cut an order takes its materials out of stock](S-53-cutting-takes-materials-out-of-stock.md)                         | 5 · Must · M            | `Ready` (approved 2026-10-16; ships with S-49, D-11)                                                     |
| [S-50 — See on the Orders list how far each order has got, and what is being made](S-50-orders-list-progress-and-totals.md)              | 5 · Must · S            | `Ready` (approved 2026-10-16; after S-49)                                                                |
| [S-08 — One automated test walks the whole journey, and a fresh install shows the demo workshop](S-08-golden-path-test-and-demo-data.md) | 4 · Must · M            | `Done` (closed as is 2026-10-15; on `main`: `723f57a` … `5241147`)                                       |
| [S-44 — Each workshop sees only its own materials, products and sales](S-44-each-workshop-sees-only-its-own-data.md)                     | 4 · Must · M            | `Done` (accepted 2026-10-15; on `main`: `672937f`, `a54856f`, `9a48cd3`)                                 |
| [S-45 — Each workshop has its own units and categories](S-45-each-workshop-has-its-own-units-and-categories.md)                          | 4 · Must · S            | `Ready`, on hold since 2026-10-15 (Phase 4 postponed, D-12)                                              |
| [S-30 — Walk through every MVP screen and sign it off](S-30-mvp-acceptance-walkthrough.md)                                               | 4 · Must · S            | `Ready`, on hold since 2026-10-15 (Phase 4 postponed, D-12)                                              |
| [S-46 — Name your workshop](S-46-name-your-workshop.md)                                                                                  | 4 · Should · S          | `Ready`, on hold since 2026-10-15 (Phase 4 postponed, D-12)                                              |
| [S-31 — A section that fails to load offers "Try again"](S-31-deferred-section-try-again.md)                                             | 4 · Should · S          | `Ready` (approved 2026-10-14), on hold since 2026-10-14                                                  |
| [S-32 — A duplicate SKU saved at the same moment shows a clear message](S-32-duplicate-sku-clear-message.md)                             | 4 · Should · S          | `Ready` (approved 2026-10-14), on hold since 2026-10-14                                                  |
| [S-43 — Save a product without filling in the overhead cost](S-43-overhead-cost-optional.md)                                             | 4 · Should · S          | `Done` (accepted 2026-10-14; on `main`: `6179d52`, `4644a08`, `9fc8e98`, `9df0ca0`, close-out `eccb09a`) |
| [S-40 — Fill a fresh install with a realistic catalogue of 100 products](S-40-realistic-catalogue-100-products.md)                       | 4 · Could · M           | `Ready`, on hold since 2026-10-15 (postponed, kept for later)                                            |
| [S-41 — A real photo on every item of the demo catalogue](S-41-real-photos-for-the-catalogue.md)                                         | 4 · Could · M           | `Ready`, on hold since 2026-10-15 (postponed; needs your Pexels key)                                     |
| [S-42 — Screen readers hear "112 units", not "112units"](S-42-screen-readers-hear-units.md)                                              | 4 · Could · S           | `Ready`, on hold since 2026-10-15 (postponed, kept for later)                                            |

S-29 (reliable dev environment) has no card: it ran from its plan, which predates story cards. It is done, closed as it stood on
2026-10-15 (D-13). S-08 and S-29's unfinished checks are known gaps for go-live ([STATUS.md §6](../STATUS.md#6-what-is-missing-or-incomplete)).
