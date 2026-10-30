type: index

tags:

- project/story-cards
    
- documentation
    
    vault_path: docs/
    
    updated: 2026-10-24
    

# Story Cards (Product-Owner & Tech-Lead)

This document tracks all story cards across active and past phases. Each story card is stored in a standalone Markdown file inside the `docs/` vault using these naming conventions:

- **New work (from 2026-10-24, D-21):** every item is numbered `INV-<n>`. A feature has two cards: the UI card first, `INV-<n>-<slug>-ui.md` (e.g., `INV-1-colours-ui.md`), then the development card, `INV-<n>-<slug>.md` (e.g., `INV-1-colours.md`). A technical item is `INV-<n>` too and has only a development card, `INV-<n>-<slug>.md`.
- **Older cards:** S- and F- cards keep their names, `<id>-<slug>.md` (e.g., `S-31-deferred-section-try-again.md`, `F-41-summed-requirement-per-material.md`). Their IDs are never reused.

## 📋 Story Card Lifecycle & Rules

> [!INFO] Card Structure
> 
> Each story card consists of two primary sections:
> 
> 1. **Product Owner Section:** Properties, Description, How it works today, and Acceptance criteria.
>     
> 2. **Tech Lead Section:** Technical work added below a horizontal rule (`---`), formatted as a concise bulleted list for simple tasks or a detailed implementation plan for complex tasks.
>     

### Lifecycle Rules

- **Draft:** A card is in _Draft_ status while the Product Owner writes it.
    
- **Ready:** Moves to _Ready_ once approved by the human owner. Only _Ready_ cards are handed off to the Tech Lead.
    
- **Planned:** The story becomes _Planned_ once the human approves the Technical Work plan.
    
- **Changes:** Any modification to acceptance criteria after approval requires human sign-off.
    
- **Questions:** Questions from either lead are directed to the human via the main session—never within the card.
    
- **Done:** Ticked by the Product Owner with completion dates and commit hashes.
    
- **Tech Debt:** Technical items from `TECH-DEBT.md` used the format `F-xx-<slug>.md` and are authored fully by the Tech Lead. From 2026-10-24 (D-21) a new technical item is `INV-<n>-<slug>.md`, with no UI card.
    
- **Legacy Exemption:** Cards existing prior to **2026-10-14** retain their original layout (`story`, `UX`, `out of scope`, `questions`, `acceptance check`, `plan in docs/plans/`) and are not converted.
    
- **On Hold:** Paused cards have the status `On hold`. Since **2026-10-24 (D-20)** every card that isn't `Done` is on hold while the product is reworked around colour × size variants. New work is INV-1 to INV-5 (colours, product sizes × colours with stock and barcode per variant, restock of finished products, clients, a production progress page; D-21); the next free ID is **INV-6**. On-hold cards are checked against the variant model before they resume.
    

> [!NOTE] Special Note on S-29
> 
> **S-29 (Reliable Dev Environment)** has no individual story card because it was executed directly from its predating plan. It was closed on **2026-10-15 (D-13)**. Any unfinished checks for S-08 and S-29 are tracked as known gaps for go-live in `STATUS.md §6`.

## 🏷️ Story Cards Overview

|                                                                                                    |           |                |          |                                                                                                        |
| -------------------------------------------------------------------------------------------------- | --------- | -------------- | -------- | ------------------------------------------------------------------------------------------------------ |
| **ID & Title**                                                                                     | **Phase** | **Priority**   | **Size** | **Status**                                                                                             |
| **INV-1** — Colours as fixed values (UI card)                                                      | —         | Must           | —        | `UI approved` (validated 2026-10-25, amended the same day: colours required)                           |
| **INV-1** — Colours as fixed values                                                                | —         | Must           | L        | `Planned` (card and Technical work approved 2026-10-30)                                                |
| **INV-2** — Product sizes × colours, stock and barcodes (UI card)                                  | —         | Must           | —        | `UI approved` (validated 2026-10-25, amended the same day by INV-3: Restock button)                    |
| **INV-3** — Restock finished products (UI card)                                                    | —         | Must           | —        | `UI approved` (UI card validated 2026-10-25; development card when you ask for it)                     |
| **INV-4** — Clients as an entity (UI card)                                                         | —         | Must           | —        | `UI approved` (UI card validated 2026-10-30; development card when you ask for it)                     |
| **INV-5** — Progress (UI card)                                                                     | —         | Must           | —        | `UI approved` (validated 2026-10-31; orders per colour still to be discussed)                          |
| **S-48** — Record customer orders with one or more products                                        | 5         | Must           | M        | `Done` (accepted 2026-10-15; `e3b7ae7`, `5af8ed1`)                                                     |
| **S-49** — Track each product of an order through cutting, ready to ship and shipped               | 5         | Must           | M        | `Done` (accepted 2026-10-21; `0ead667`, `51a4f76`, `05b3101`, `507c1c1`, `dd90245`, `8ab9df8`)         |
| **S-53** — Starting to cut an order takes its materials out of stock                               | 5         | Must           | L        | `Done` (accepted 2026-10-21; `cd98f04`, `e809fb9`, `c5a38e1`, `06beb6d`, `15cfd26`, `8ab9df8`)         |
| **S-50** — See on the Orders list how far each order has got, and what is being made               | 5         | Must           | S        | `Done` (accepted 2026-10-22; `ba0a0cd`, `74f05a8`, `a23a5d7`)                                          |
| **S-58** — Every product gets the sizes of its category                                            | 5         | Must           | S        | `Done` (accepted 2026-10-22; `2f02d87`, `9eeee19`, `f1074b6`, `ce9a36f`, `eb5ff13`)                    |
| **S-60** — Order a product in several sizes                                                        | 5         | Must           | M        | `Done` (accepted 2026-10-23; `1db11bb`, one commit with S-61)                                          |
| **S-61** — Follow each size through cutting, ready to ship and shipped                             | 5         | Must           | M        | `Done` (accepted 2026-10-23; `1db11bb`, one commit with S-60)                                          |
| **S-56** — Finished products in stock, per size                                                    | 5         | Should         | M        | `Done` (accepted 2026-10-23; `83062eb`, one commit with its fix round)                                 |
| **S-70** — Sizes look the same on every screen                                                     | 5         | Should         | M        | `Done` (accepted 2026-10-23; `b200905` … `4d646e9`, six commits)                                       |
| **S-71** — The order page's stage table and its buttons look like the design                       | 5         | Should         | S        | `Done` (accepted 2026-10-24; `722638c`, `7e00fff`, `da6ff31`)                                          |
| **S-73** — The product page and form match the size board                                          | 5         | Should         | S        | `Done` (accepted 2026-10-24; `51ba6ce`, `078bead`, `bf5db86`)                                          |
| **S-57** — Cancel an order                                                                         | 5         | Should         | M        | `On hold` since 2026-10-24 (was `Ready`, approved 2026-10-22, D-17)                                    |
| **S-65** — Close a fully shipped order                                                             | 5         | Should         | M        | `On hold` since 2026-10-24 (was `Ready`, approved 2026-10-22)                                          |
| **S-72** — The order page shows the whole order at a glance                                        | 5         | Could          | S        | `On hold` since 2026-10-24 (was `Draft`, added 2026-10-24)                                             |
| **F-41** — The cost engine gives the total need of each material                                   | 5         | Tech Allowance | S        | `Done` (accepted 2026-10-21; `e99615e`)                                                                |
| **S-08** — One automated test walks the whole journey, and a fresh install shows the demo workshop | 4         | Must           | M        | `Done` (closed as is 2026-10-15; on main: `723f57a` … `5241147`)                                       |
| **S-44** — Each workshop sees only its own materials, products and sales                           | 4         | Must           | M        | `Done` (accepted 2026-10-15; on main: `672937f`, `a54856f`, `9a48cd3`)                                 |
| **S-45** — Each workshop has its own units and categories                                          | 4         | Must           | S        | `On hold` since 2026-10-24 (was `Ready`, on hold since 2026-10-15, D-12)                               |
| **S-30** — Walk through every MVP screen and sign it off                                           | 4         | Must           | S        | `On hold` since 2026-10-24 (was `Ready`, on hold since 2026-10-15, D-12)                               |
| **S-46** — Name your workshop                                                                      | 4         | Should         | S        | `On hold` since 2026-10-24 (was `Ready`, on hold since 2026-10-15, D-12)                               |
| **S-31** — A section that fails to load offers "Try again"                                         | 4         | Should         | S        | `On hold` since 2026-10-24 (was `Ready`, on hold since 2026-10-14)                                     |
| **S-32** — A duplicate SKU saved at the same moment shows a clear message                          | 4         | Should         | S        | `On hold` since 2026-10-24 (was `Ready`, on hold since 2026-10-14)                                     |
| **S-43** — Save a product without filling in the overhead cost                                     | 4         | Should         | S        | `Done` (accepted 2026-10-14; on main: `6179d52`, `4644a08`, `9fc8e98`, `9df0ca0`, close-out `eccb09a`) |
| **S-40** — Fill a fresh install with a realistic catalogue of 100 products                         | 4         | Could          | M        | `On hold` since 2026-10-24 (was `Ready`, postponed 2026-10-15, kept for later)                         |
| **S-41** — A real photo on every item of the demo catalogue                                        | 4         | Could          | M        | `On hold` since 2026-10-24 (was `Ready`, postponed 2026-10-15, needs Pexels key)                       |
| **S-42** — Screen readers hear "112 units", not "112units"                                         | 4         | Could          | S        | `On hold` since 2026-10-24 (was `Ready`, postponed 2026-10-15, kept for later)                         |