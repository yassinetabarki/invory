---
id: INV-9
title: Print barcode labels
type: feature
phase: Colour × size variants
priority: Must
size: M
status: Ready
created: 2026-10-31
tags:
  - story
  - variants
---

# INV-9 · Print barcode labels

> [!abstract] Description
> As a **workshop owner**, I want **to print a label with the barcode for any colour and size**, so that **every finished unit carries
> a label that can be scanned and read**. Labels print from the Barcodes tab, and a new page, Settings › Labels, sets how they print.
> This is the last of three cards that share one UI card: [[INV-2-product-sizes-colours-barcodes-ui]]; it builds on the barcodes of
> INV-8. Left out on purpose: scanning, and barcodes on the Products list.

> [!info] How it works today
> Once INV-8 is built, the Barcodes tab of the Denim Jacket shows Indigo S 2000000000015, M 2000000000022, L 2000000000039 and XL
> 2000000000046 with their SKUs ("PRD-JKT-001-IND-S") and stock, and a variant without a barcode has a **Generate** button. Invory
> prints no labels, and Settings › Catalogue lists Units, Material categories, Product categories and Colours.

## Acceptance criteria
- [ ] **AC-1 · Print buttons on the Barcodes tab.** A variant with a barcode shows the button **Print label** in its row (named "Print
  label for PRD-JKT-001-IND-S"); on the pills' row, next to **Generate missing barcodes (4)**, the outline button **Print labels ·
  Indigo** with a printer icon. **Print labels · Light wash** can't be pressed while Light wash has no barcode; the reason "Generate
  the barcodes of Light wash first." sits in a tip that the keyboard reaches. A "One size" product (Leather Tote) has **Print
  labels**, which prints every colour. After "Generate", the focus goes to the first new barcode's **Print label**, instead of the
  table of barcodes. On a phone, **Print labels · Indigo** is full width under the pills, and each size's card has **Print label**
  (or **Generate**) full width, 44 px.
- [ ] **AC-2 · Print labels.** **Print label** on a row opens no window: one label of that variant opens in a new browser tab, ready
  to print (the button's name ends "opens in a new tab"). **Print labels · Indigo** opens "Print labels · Denim Jacket · Indigo" with
  one row per size that has a barcode: a checkbox with the size ("S") and a **Labels** field (a whole number, 1 to 999) filled in with
  the units in stock (S 2, M 1, L 3, XL 1); a size at 0 in stock (M) starts unchecked, with 1 in its field. A size without a barcode
  is listed greyed, "XL · No barcode yet".
  - Under the rows: "Total 6 labels · Label 50 × 30 mm · Price shown" and the link **Change in Settings › Labels**, which opens in a
    new tab and says so in its name. Buttons **Cancel** and **Print 6 labels**: the labels open in a new browser tab sized for the
    printer, the browser's print window opens there and the window closes; no toast. A wrong count shows "Enter a whole number
    from 1 to 999." under the field (**Print** stays clickable and shows the error on click); with every size unchecked, **Print 0
    labels** can't be pressed and "Choose at least one size." shows under the rows; if the browser blocks the tab: "Your browser
    blocked the labels' tab. Allow pop-ups for Invory, then print again." On a phone the window fills the screen with one 44 px row
    per size and **Print 6 labels** fixed at the bottom.
  - **A label** (50 × 30 mm; the A4 sheet has the same layout, 3 × 8 per page), top to bottom: the product name in bold, at most two
    lines ("Denim Jacket"); colour and size "Indigo · S" (a "One size" product the colour alone, "Camel"); the barcode with its 13
    digits, always black on white; and the bottom line, the variant SKU in the number font on the left ("PRD-JKT-001-IND-S") and on
    the right the selling price "TND 89.000" when Settings › Labels says to show it and the product has a selling price.
- [ ] **AC-3 · Settings › Labels.** A new page in Settings › Catalogue, after Colours, for the workshop: the header "Labels" with "How
  your barcode labels print.", the list **Label size** ("50 × 30 mm, one per label (label printer)" or "A4 sheet, 24 labels (3 ×
  8)"), the checkbox **Show the selling price on labels** (ticked at first) and the button **Save label settings**, which shows a
  spinner and then the toast "Label settings saved.". Any member of the workshop can change it. On a phone it is one column with
  **Save label settings** in the bar fixed at the bottom.
