---
id: S-45
title: Each workshop has its own units and categories
type: feature
phase: 4
priority: Must
size: S
status: Ready
created: 2026-10-14
tags:
  - story
  - phase-4
---

# S-45 · Each workshop has its own units and categories

> [!abstract] Description
> As a **workshop owner**, I want **my own lists of units, material categories and product categories**, so that **what I add, rename or
> archive in Settings never changes another workshop's screens, and theirs never show up in mine**.
> Every new workshop starts with the standard lists and changes them freely. The currency and the language stay the same for every
> workshop of the installation; they are not part of this story.

> [!info] How it works today
> Settings → "Catalogue" has three pages: "Units", "Material categories" and "Product categories". Each is **one list for the whole
> installation**, and any signed-in account can add, rename, archive, restore or delete entries (decision Q3). The installation starts with
> 13 units, 12 material categories and 6 product categories. If one account adds the unit "Spool" or renames "Fabric", every account sees
> it at once in its forms, filters and pages. Once each workshop has its own materials and products, these lists would still be shared.

## Acceptance criteria
- [ ] **AC-1 · A new workshop starts with the standard lists.** When Sami signs up, his Settings show the standard lists and nothing else,
  none archived:
  - "Units": Metre, Centimetre, Yard, Square metre, Square foot, Kilogram, Gram, Litre, Millilitre, Piece, Pair, Roll, Cone (13).
  - "Material categories": Fabric, Lining, Interlining, Leather, Thread, Trim, Fastener, Label, Sole, Hardware, Packaging, Other (12).
  - "Product categories": Apparel, Footwear, Bag, Accessory, Jewelry, Other (6).
- [ ] **AC-2 · Your changes stay in your workshop.** Given `test@example.com` adds the unit "Spool", renames the material category "Fabric"
  to "Fabrics" and archives the product category "Jewelry", when Sami opens his three Settings pages, the "Unit" and "Category" lists of
  "Add material", the materials list's category filter and the "Category" list of "Add product", then he sees no "Spool", still sees
  "Fabric", and "Jewelry" is still active. Changes Sami makes never show in `test@example.com`'s workshop either.
- [ ] **AC-3 · The same name in two workshops.** Sami can add his own unit "Spool" although `test@example.com` already has one: he sees
  "Unit created.". The same holds for material and product categories. Inside one workshop a name still can't be used twice, whatever its
  capitals, exactly as today.
- [ ] **AC-4 · Another workshop's entries can't be reached.** Renaming, archiving, restoring or deleting one of `test@example.com`'s units or
  categories from Sami's account is refused with "Not Found" and changes nothing. A material or product can only use units and categories
  of its own workshop.
- [ ] **AC-5 · Today's lists are kept.** When this change is installed, the workshop that holds today's data keeps today's three lists
  exactly: every entry with its name, order and archived state, including any you added. Every material and product keeps its unit and
  category, and a unit stays locked while a product's composition uses it (decision Q7). On a fresh install, the demo workshop of
  `test@example.com` has the standard lists of AC-1.
