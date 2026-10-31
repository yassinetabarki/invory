---
id: INV-8
title: Barcodes for every colour and size
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
created: 2026-10-31
tags:
  - story
  - variants
---

# INV-8 · Barcodes for every colour and size

> [!abstract] Description
> As a **workshop owner**, I want **every colour and size of a product to have its own barcode, made by Invory**, so that **each unit
> can be told apart and counted by a scan later**. One barcode stands for one name + colour + size, so every product with the same
> name, colour and size shares it. Barcodes are EAN-13 numbers from the workshop's own in-store range. This is the second of three
> cards that share one UI card: [[INV-2-product-sizes-colours-barcodes-ui]]; it builds on the stock card INV-2.
> Left out on purpose: printing labels and Settings › Labels (INV-9); scanning; orders, sales and "Put in stock" per colour;
> **Restock** (INV-3); and barcodes on the Products list.

> [!info] How it works today
> Once INV-2 is built, the Denim Jacket page has a Sizes & colours card under the three figures, with the line "3 colours × 4 sizes =
> 12 variants. Each variant has its own stock." and the table "Units in stock per colour and size", but no tabs. No product has a
> barcode or a variant SKU. Each colour has a short code that never changes (Black BLK, Indigo IND, Light wash LWH). Renaming a product
> changes only its name. "Delete product?" reads "Denim Jacket (SKU PRD-JKT-001) and its composition will be removed. This cannot be
> undone.".

## Acceptance criteria
- [ ] **AC-1 · Two tabs in the Sizes & colours card.** The card's line becomes "3 colours × 4 sizes = 12 variants. Each variant has its
  own stock and its own barcode." (other products: "1 colour × 4 sizes = 4 variants", "2 colours × One size = 2 variants"). Under the
  heading, two tabs: **Stock by colour**, which holds the stock table of INV-2, and **Barcodes**. The page opens on Stock by colour,
  and the open tab is kept in the page address, so a link can open Barcodes. While any variant has no barcode, **Barcodes** carries the
  warning badge "4 missing". The link **By size and colour** under the In stock figure moves to the card and opens its Stock tab. While
  loading: the frame, heading and tabs at once, with a placeholder of 3 rows; an error shows "Couldn't load the stock and barcodes."
  with **Try again**, which reloads the card and the In stock figure together. On a phone the tabs fill the width, half each.
- [ ] **AC-2 · Barcodes tab.** The Barcodes tab shows each variant's barcode, with Black, Indigo and Light wash as colour pills (a
  group named "Colour"): swatch, name and in grey "· 4 missing" when that colour has variants without a barcode; the chosen pill has a
  2 px ring and a tint and is announced "pressed"; the first colour (Black) is chosen when the tab opens.
  - A table "Barcodes for Indigo": **Size** (the size tag) · **SKU** (the number font: product SKU, colour code, size,
    "PRD-JKT-001-IND-S"; a shoe "PRD-SNK-003-BLK-38") · **Barcode (EAN-13)** (the bars and the 13 digits under them in the number
    font) · **In stock** (this product's units of that variant) · the row's button. With barcodes made, Indigo reads S 2000000000015
    · M 2000000000022 · L 2000000000039 · XL 2000000000046; a variant with a barcode has no button.
  - A variant without a barcode shows the warning badge "No barcode yet" and the button **Generate** (named "Generate barcode for
    PRD-JKT-001-LWH-S"). Light wash with nothing made reads: the pill "Light wash · 4 missing", the tab badge "4 missing", and on the
    pills' row the main button **Generate missing barcodes (4)**, shown only while any variant of the product, in any colour, has no
    barcode (the count is the product's total).
  - **A shared barcode:** under the digits a grey line "Shared with PRD-JKT-002" (PRD-JKT-002 a link to that product); two or three
    others "Shared with PRD-JKT-002, PRD-JKT-007"; more "Shared with PRD-JKT-002, PRD-JKT-007 and 3 more", where "3 more" opens a
    small list. A combination that already has a barcode on any product with the same name never counts as missing and never shows
    **Generate**. "The same name" means the same words, ignoring capitals and extra spaces: "denim jacket" is "Denim Jacket".
  - Under the table: "One barcode for each colour and size. Every product named Denim Jacket shares it, and a barcode never
    changes." Archived and draft products show and generate barcodes like active ones.
  - **A "One size" product** (Leather Tote) has no pills: one table with one row per colour, **Colour** · SKU "PRD-TOT-001-CAM" ·
    Barcode · In stock · button.
  - **Phone:** the pills wrap and are 44 px tall; **Generate missing barcodes (4)** is full width under them; then one card per
    size: the size tag and SKU on the first line, the barcode (bars about 140 px wide) with its digits, "In stock 2", "Shared with
    PRD-JKT-002", and, on a variant without a barcode, **Generate** full width, 44 px. 12 colour pills wrap and a 32-character
    colour name wraps inside its pill.
- [ ] **AC-3 · Generate barcodes.** Generating always asks first, because a number is used for good.
  - From **Generate missing barcodes (4)**: the title **Generate 4 barcodes?**, the text "Denim Jacket gets a barcode for Light wash
    S, M, L and XL, from your in-store numbers. A barcode never changes, and its number is never given to anything else." and the
    buttons **Not now** and **Generate 4 barcodes**. From a row's **Generate**: **Generate a barcode?**, "Denim Jacket in Light wash,
    size S, gets a barcode from your in-store numbers. A barcode never changes, and its number is never given to anything else." and
    **Not now** and **Generate barcode**. On a phone the window is full width and the confirm button comes first.
  - Each new name + colour + size takes the next number of the workshop's own in-store EAN-13 range, starting at 2000000000015, then
    2000000000022, 2000000000039, 2000000000046 (each with its right check digit). A number is never given twice, and no second barcode
    is ever made for the same name, colour and size. Generating is all or nothing: either every barcode asked for is made, or none.
    Two workshops can each have the number 2000000000015.
  - While generating, the confirm button shows a spinner and keeps its label. On success the window closes, the focus goes to the
    table of barcodes, and the toast reads "4 barcodes generated for Light wash." (one: "Barcode generated for Light wash S."); the
    rows show their barcodes, and the tab badge and the **Generate missing barcodes** button go. If someone made one a moment ago: "4
    barcodes ready for Light wash: 3 new, 1 already used by PRD-JKT-002.". On failure the window stays open with "Couldn't generate
    the barcodes. Try again.".
- [ ] **AC-4 · Barcodes follow the name, not the product.** Renaming a product keeps its barcodes; no window asks first. If a
  barcode was shared with another product of the old name, it now stands for both names, the other product keeps it too, and
  labels already printed still scan. A product renamed "Trucker Jacket" that later gets Navy shares the barcodes of Navy under its
  current name: Navy S is the same number on every "Trucker Jacket". A colour taken off a product keeps its barcodes, and they come
  back if the colour is added again. **Delete product** keeps the barcodes: when the product has barcodes, the "Delete product?"
  window adds the sentence "Its 12 barcodes are kept: they come back on any product named Denim Jacket in the same colours and sizes."
  after today's text; with none, the window is as today.
