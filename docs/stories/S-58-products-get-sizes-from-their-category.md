---
id: S-58
title: Every product gets the sizes of its category
type: feature
phase: 5
priority: Must
size: S
status: Ready
created: 2026-10-18
tags:
  - story
  - phase-5
---

# S-58 · Every product gets the sizes of its category

> [!abstract] Description
> As a **workshop owner**, I want **each product to carry the sizes of its kind, clothing in S, M, L and XL and shoes in 36 to 46**, so
> that **orders, production and finished stock can be counted per size**.
> The sizes come from a short list set up with Invory for each product category. You don't pick them, and there is no screen to change
> these lists for now. Sizes don't change the composition, the unit cost, "Can produce" or the bottleneck: every size of a product uses
> the same composition. The products list, sales and "Record sale" don't change: a sale has no size. Using sizes on orders is S-60 and
> S-61.

> [!info] How it works today
> Nothing in Invory knows about sizes. The Denim Jacket's page shows "PRD-JKT-001", the category "Apparel" and the status "Active" under
> its name, then "Can produce now 112 units from current stock" and "Unit cost TND 35.754", without saying in which sizes the jacket is
> made. On "Add product" and "Edit Denim Jacket" the "Category" field (Apparel, Footwear, Bag, Accessory, Jewelry, Other, plus any you add
> in Settings) only sorts and filters products. The demo Canvas Low Sneaker uses the material "Rubber cupsole 42": the size is written
> in the material's name because there is nowhere else to put it. On the Orders list, "50 × Denim Jacket" can't say how many are S or
> XL.

## Acceptance criteria
- [ ] **AC-1 · Sizes come with the category.** Every workshop, including one created at sign-up, has these sizes:
  - Apparel: S, M, L, XL, in that order;
  - Footwear: 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, in that order;
  - every other category (Bag, Accessory, Jewelry, Other, and any category you add in Settings): "One size".

  A product has every size of its category. There is no screen to change these lists. Renaming Apparel to "Clothing" in Settings, or
  archiving it, keeps its sizes S, M, L, XL.
- [ ] **AC-2 · The product page names the sizes.** Under its name, next to "Apparel", the Denim Jacket's page shows "Sizes S, M, L, XL",
  and its "Can produce now" card reads "112 units" and "from current stock, any mix of sizes". The Canvas Low Sneaker shows "Sizes 36 to
  46". The Leather Tote shows "One size", and its card keeps "from current stock". "Can't produce" and "No composition" read as today.
  The unit cost (TND 35.754), "Can produce now" (112) and the bottleneck (Metal shank button 17mm) are unchanged.
- [ ] **AC-3 · The form shows the sizes of the chosen category.** On "Add product" and "Edit Denim Jacket", under
  "Category", the line "Sizes: S, M, L, XL" appears when Apparel is chosen, "Sizes: 36 to 46" for Footwear and "One size" for any other
  category, with the hint "Sizes come with the category.". It changes as soon as you choose another category. On "Add product" no line
  shows until a category is chosen. "Save product" works as today ("Product created." or "Product updated.").
- [ ] **AC-4 · The sizes are locked once the product is on an order.** When the Denim Jacket is on Boutique Lina's order, changing its
  category from Apparel to Footwear (or to Bag) and clicking "Save product" shows, under "Category", "Denim Jacket is on orders, so its
  category can't change to one with other sizes. Keep a category with sizes S, M, L, XL." and nothing is saved. An order counts until it
  is deleted, also once it is fully shipped. Changing to a category with the same sizes (Bag to Accessory, both "One size") is always
  allowed, and a product on no order can change to any category.
- [ ] **AC-5 · Existing products follow their category.** After this change, every product you already have shows the sizes of its
  category: the Denim Jacket, the Denim Jeans 5-pocket, the Classic White T-shirt and the Canvas Chore Jacket "Sizes S, M, L, XL"; the
  Canvas Low Sneaker "Sizes 36 to 46"; the Leather Tote and the Leather Belt "One size". Nothing else about them changes: costs, "Can
  produce", sales and orders read as before.
