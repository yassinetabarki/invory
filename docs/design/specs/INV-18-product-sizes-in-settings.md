# INV-18 · Sizes chosen per product, managed in Settings — design spec
Status: proposal · Designer · 2026-11-06 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc), new
row "Sizes chosen per product (INV-18)":
- "INV-18 · Settings › Sizes · list, Settings menu, long list · desktop" (`project/Inv18SizeSettings.dc.html`)
- "INV-18 · Add size, Edit size, errors, Product categories suggest sizes · desktop" (`project/Inv18SizeDialogs.dc.html`)
- "INV-18 · Add and Edit product · Sizes field: pre-select, locked sizes, refusals, confirm · desktop" (`project/Inv18ProductForm.dc.html`)
- "INV-18 · Product page · Sizes badge, Sizes & colours card, Manage sizes & colours · desktop" (`project/Inv18ProductShow.dc.html`)
- "INV-18 · Settings › Sizes, Add size, Sizes field, locked size, confirm, product page · 375" (`project/Inv18Phone.dc.html`)

Reads: [S-58](S-58-size-building-blocks.md) and [INV-2](INV-2-product-sizes-colours-barcodes.md) (how sizes read today),
[INV-1](INV-1-colours.md) (the pattern this follows), [INV-16](INV-16-material-per-variant.md) (the grid that will use these sizes).
Nearest patterns in the app: Settings › Colours (list, dialog, Position), Settings › Units (cards below laptop width), the Colours field
on the product form (chips, hint, "Choose at least one colour.", the colour lock), Settings › Production phases (Move up, Move down).

This reverses two earlier decisions, on the human's word of 2026-11-06: S-58's "Settings shows no sizes" and ADR-0015's "no screen
edits the size lists". The category stops deciding a product's sizes.

## Problem and user
The workshop owner sets up products on a laptop and checks them on a phone. Today a product's sizes come from its category: every
Apparel product has XS to XXXL, every Footwear product 36 to 46, everything else "One size", and nothing in the app can change that.
So the Denim Jacket carries XS, XXL and XXXL it never makes, each with a column in the stock table, a barcode to generate and a field
to fill in; a sneaker made only in 38 to 44 shows 11 sizes; a new kind of garment can only be "One size".

From INV-18 on, **the workshop has one list of sizes in Settings, like Units and Colours, and each product picks the sizes it
really comes in.** The questions the screens answer: **which sizes does my workshop use?** (Settings › Sizes) and **which of them
does this product come in?** (the Sizes field on the product form, the badge and the card on the product page).

## Decisions already taken (human, 2026-11-06)
1. One workshop-wide list of sizes in Settings (XS … XXXL, 36 … 46 and One size together); each product picks some.
2. On Add product, the category pre-selects the sizes; the owner can change them.
3. Existing products keep exactly the sizes they have now.
4. A size is never deleted, only renamed and reordered (like colours). A size can't be taken off a product that holds stock, orders,
   barcodes, production or INV-16 cells: one refusal sentence.
5. A product keeps at least one size ("One size" counts).
6. The order of sizes is the position set in Settings, as for colours.

## How it looks today
- **Product form:** under Category, a line "Sizes:" with a tag per size [XS] [S] [M] [L] [XL] [XXL] [XXXL], or the name "36 to 46", or
  "One size", and under it the hint "Sizes come with the category." Nothing to edit.
- **Product page:** the header badge "Sizes XS to XXXL" (or "One size"); the Sizes & colours card "3 colours × 7 sizes = 21 variants"
  with the stock table's seven size columns and the Barcodes tab's seven rows per colour; "Can produce now … any mix of sizes";
  "Unit cost … same for every size". The Manage sizes & colours button opens Edit product on the Colours field.
- **Category lock:** a product on an order, with stock or in production can't move to a category with other sizes (three sentences,
  "Denim Jacket is on orders, so its category can't change to one with other sizes. Keep a category with sizes XS to XXXL.").
- **Settings:** Catalogue holds Units, Material categories, Product categories, Colours and Labels. No sizes.

## Flow
1. **Once, in Settings.** Settings › Catalogue › **Sizes** lists the workshop's sizes in order, each with its position and how many
   products use it. A workshop starts with today's 19 sizes (XS, S, M, L, XL, XXL, XXXL, 36 to 46, One size). The owner adds "XXS"
   with **Add size** (name, position), or renames "XXL" with **Edit**. A size is never removed (human's decision).
2. **Add product.** The owner types the SKU and name and chooses the category Apparel. The **Sizes** field, empty until now, fills
   with XS, S, M, L, XL, XXL, XXXL pressed. She presses XS, XXL and XXXL to take them off and keeps S, M, L, XL. She adds colours and
   a composition, **Add product** → "Product created." → the page shows "Sizes S, M, L, XL".
3. **Edit product.** Sizes already chosen are pressed. The sizes the product uses (stock, orders, barcodes, production, material
   quantities) carry a lock and can't be pressed off, with the reason on screen. The free ones can: XS comes off. A size the product
   lacked can be pressed on: 36 isn't offered to the jacket by the category any more, but the owner may still add it (it is her list).
   **Save product** → "Product updated.".
4. **Product page.** The badge, the Sizes & colours card, the stock table, the barcodes and every size field in the app (Update stock,
   Restock, order lines, Start batch) show the product's own sizes in Settings order. **Manage sizes & colours** opens Edit product
   on the Sizes field.
5. **Renaming** a size in Settings shows the new name wherever the size shows, at once, on every product that has it.

## Layout

### Settings menu
Catalogue group: Units · Material categories · Product categories · Colours · **Sizes** · Labels. "Sizes" sits after Colours (the two
lists a product picks variants from). Board: `Inv18SizeSettings`.

### Settings › Sizes (laptop)
Built like Settings › Colours: page heading **Sizes**, the description, primary **Add size** on the right, then one table in a card.
- Description: "The sizes your products come in. Each product picks the ones it has. Rename any of them, or add your own."
- Table (shadcn table, caption "Sizes"): **Size** (the name, as a size tag in the same outlined shape the form uses) · **Position**
  (number font) · **Used by** ("3 products", "1 product", "Not used") · one action, **Edit**, named "Edit XXL". No Status column, no
  Archive, Restore or Delete (a size is never removed). Rows in list order, 19 on a new workshop, no paging (as Units and Colours).
- Position column: the numbers the owner types in the dialog, so "put XXS before XS" needs no guessing. The 19 starting sizes are
  numbered 10, 20, 30 … 190, so a new size can go between two (15), and 0 puts it first.
- Under the table: "A size is never removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One size" is for
  products that don't come in sizes: a product that has it has no other size."
- The "One size" row carries a small neutral badge "No size" after its name, because that one is special (a product with only it has no
  size columns and no size in its SKU). It can be renamed like the others; the badge stays.
- Phone: one compact row per size (not a full card, as there are 19 of them): the size tag, "Used by 3 products" in grey, an
  **Edit** button, 44 px high. **Add size** full width above the list.

### Add size / Edit size (dialog, the Colours dialog's shape)
- Title **Add size**, or **Edit XXL**. **Name** (up to 12 characters). **Position (optional)**: "Lower numbers are listed first. Leave
  empty to list it last." (today's PositionField words).
- Under Name when editing a size that products use: "A new name shows on the 3 products that use XXL, and in their variant SKUs.
  Barcodes don't change." (count from the server; left out for an unused size). Variant SKUs are built from the size name on every read
  ("PRD-JKT-001-IND-XXL"), so a rename changes them and no barcode.
- Edit "One size" adds a line under the title: "The size for products without sizes. A product that has it has no other size."
- Buttons **Cancel** · **Save size** (spinner while saving; the dialog stays open on errors). Phone: full-width dialog, buttons stacked.
- A new size is on no product until it is chosen on one. Nothing in the dialog says otherwise.
- Board: `Inv18SizeDialogs`.

### Product categories suggest sizes (needs the human's yes, question 1)
The category can only pre-select if the category knows which sizes to suggest, and the three starting lists that did this job are
gone. Proposed: **Settings › Product categories** gains a **Suggests** column ("XS to XXXL", "36 to 46", "One size") and the Edit
category dialog a **Suggested sizes** group with the same chips as the product form (nothing locked). Hint: "Add product pre-selects
these sizes when you choose this category." Error "Choose at least one size.". A category added in Settings starts by suggesting "One
size", as it does today. This is the only screen beyond the ones named in the brief. Without it, the three starting categories keep
their suggestions for good and every new category suggests "One size" with no way to change it. Board: `Inv18SizeDialogs`, bottom.

### Product form · the Sizes field (Add product and Edit product)
In the **Details** card, a full-width row **after Category and Status and before Colours** (sizes sit next to the category that
suggests them; colours stay last as today). The hint "Sizes come with the category." and the line under Category go.
- Label **Sizes** with the red star. Hint under it. On Add: "Choosing a category pre-selects its sizes. Change them as you like."
  On Edit: "A new size adds a variant for every colour, with no stock and no barcode yet."
- **One chip per size of the workshop's list, all visible, in Settings order** (19 on a new workshop; they wrap). Each chip is a
  toggle: **pressed** = the product has the size (tint, 2 px Denim ring **and** a check mark before the name, so never colour alone);
  not pressed = plain outline. 40 px tall on a laptop, 44 on a phone, at least 44 px wide, 8 px between.
- Under the chips, a status line: "4 sizes chosen" / "1 size chosen" / "None chosen yet" (Add, before a category).
- **Locked chips** (Edit only): pressed, with a lock icon after the name, full-strength text (not greyed). They can't be pressed off.
  Each one is described by its reason, read when it takes focus or is hovered: "Can't be taken off: it has units in stock, is on orders
  and has barcodes." Under the group, once: "Sizes with a lock are in use by this product's stock, orders, barcodes, production or
  material quantities, so they can't be taken off."
- **Add product, category changed after the sizes were changed by hand:** a category replaces the sizes only while the owner has not
  changed them herself and no composition line holds quantities per size (INV-16). Otherwise the sizes stay and one line shows under
  the chips: "Sizes kept: you changed them. [Use the sizes of Footwear]" (a text button). Pressing it replaces the sizes (with the
  confirm below when quantities would be cleared). **Edit product never changes sizes when the category changes.**
- **One size** and other sizes exclude each other (question 2): pressing One size turns the others off, pressing another size turns
  One size off. If a locked size is pressed, One size can't be pressed: it is described "S, M, L and XL are in use, so One size can't
  be chosen." and does nothing. The reverse is never needed (a product with only One size has nothing else locked).
- Components: Label, `ToggleGroup` (multiple) or a row of toggle buttons, `Tooltip` for the reason, lucide check and lock icons. No new
  shadcn component. The locked chip must stay focusable (not `disabled`), so its reason can be read.
- Board: `Inv18ProductForm`.

### Confirm · taking a size off a product on Add product while a line holds quantities (INV-16)
Replaces INV-16's "Change the category? This clears the 8 quantities …". When the owner presses off a size on the **form** (Add
product; on Edit also for quantities typed but not saved) and a composition line holds quantities for that size, an alert dialog opens
before the chip changes:
- Title **Take XL off this product?** Body "This clears the 8 quantities you set for XL across its materials." (one: "the 1 quantity").
  Digits-only name: "Take size 36 off this product?", "…you set for size 36…".
- Buttons **Keep XL** (default focus) and the red **Take XL off**. Esc keeps. Pressing the chip off with no quantity for that size
  needs no confirm.
- The same dialog, "Use the sizes of Footwear?" "This replaces the sizes and clears the 8 quantities you set across its materials.",
  **Keep the sizes** · **Use Footwear sizes**, opens from the "Use the sizes of …" button when quantities exist.
- Saved quantities are different: on Edit product a size with saved INV-16 quantities is **locked** (above), never confirmed away.

### Product page
- **Header badge** (outline, between Category and Status), written by the server from the product's own sizes:
  - one size: "One size", or "Size M" / "Size 38" for a single size that is not One size;
  - two to six: "Sizes S, M, L, XL", "Sizes 38, 39, 40";
  - seven or more in an unbroken run of the list: first and last, "Sizes XS to XXXL", "Sizes 38 to 44";
  - seven or more with gaps: the first three and a count, "Sizes XS, S, M and 4 more" (as colour lists do).
  Letter and digit names follow today's text rule ("Sizes 36 to 46"; a single digit size is "Size 38").
- **Sizes & colours card:** the same card. "3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode." counts the
  product's own sizes; the stock table, the Barcodes tab and the phone cards list only those sizes, in Settings order. **Manage sizes &
  colours** opens Edit product on the Sizes field. Nothing else changes: "Can produce now … any mix of sizes" and "Unit cost … same for every
  size" apply when the product has more than one size, as today.
- A product with a single size that is not One size reads "1 colour × 1 size = 1 variant", and the stock table keeps its size column.
- Board: `Inv18ProductShow`.

### Everything else that names a product's sizes
Update stock, New restock, order lines, Start batch, labels, barcode lists and the INV-16 grid already ask the product for its sizes.
They keep their layouts and now show the product's own, in Settings order. No screen below is redrawn.

## States

### Settings › Sizes
| State        | What the screen shows                                                                                                          |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------ |
| Default      | 19 sizes: XS … XXXL "3 products", 36 … 46 "1 product", One size "2 products" (+ "No size" badge); a new "XXS" "Not used"         |
| Empty        | None: a workshop starts with 19 and a size is never removed                                                                    |
| Loading      | As Colours today (the list comes with the page); **Save size** shows a spinner                                                |
| Error        | Field errors in the dialog (below); a failed load keeps Inertia's error page                                                   |
| Disabled     | None                                                                                                                           |
| Read-only    | In Edit One size: the badge "No size"; nothing else is locked (every size can be renamed and moved)                             |
| Confirm      | None: adding and editing need no confirmation, and there is no delete                                                          |
| Success      | Toasts "Size created.", "Size updated."; the list shows the size in its place                                                  |
| Long content | A 12-character name fits its row; 40 sizes fit in one list (no paging); on a phone each size is one 44 px row                  |

### Add / Edit size dialog · errors (under the field, after Save size; focus on the first)
- Name empty: "Enter the size's name."
- Name taken (ignoring capitals): "You already have a size named "XL"."
- Name too long: "Keep the name to 12 characters or fewer."
- Position wrong: "Enter a whole number from 0 to 32,767, or leave it empty." (the Colours message)

### Product form · Sizes field
| State          | What the screen shows                                                                                                                  |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Default (Add)  | No category yet: all 19 chips not pressed, "None chosen yet". Choose Apparel: XS to XXXL pressed, "7 sizes chosen"                       |
| Default (Edit) | Denim Jacket: XS, XXL, XXXL pressed and free; S, M, L, XL pressed and locked; "7 sizes chosen"; the lock sentence under the group        |
| Empty          | Every chip pressed off, then Save: "Choose at least one size." under the group; focus goes to the first chip                           |
| Loading        | Nothing extra: the sizes come with the page; **Save product** keeps its label and shows a spinner                                      |
| Error          | "Choose at least one size." · "One size can't be combined with other sizes. Choose it alone, or choose other sizes." · "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has barcodes." (one sentence per size, in list order, if the page was stale) |
| Disabled       | A locked chip (pressed, lock icon, reason on focus and hover; never greyed text). One size while a locked size is chosen (reason on focus) |
| Read-only      | None: any size not in use can be changed at any time                                                                                   |
| Confirm        | "Take XL off this product?" / "Use the sizes of Footwear?" (only on the form, only while a line holds quantities)                      |
| Success        | "Product created." (Add), "Product updated." (Edit); the product page opens with its badge                                             |
| Long content   | 40 sizes wrap onto more rows; a 12-character name is one chip wider; nothing overlaps, nothing scrolls sideways                         |

### Product page
| State        | What the screen shows                                                                                                                   |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| Default      | "Sizes S, M, L, XL"; card "3 colours × 4 sizes = 12 variants…" with four size columns                                                    |
| Other cases  | "One size"; "Size M"; "Sizes 38 to 44"; "Sizes XS, S, M and 4 more"                                                                      |
| Loading, error, empty | Unchanged (INV-2: skeleton in the card, "Couldn't load the stock and barcodes." with Try again)                               |
| Long content | Seven or more sizes use the range or the "and N more" form, so the badge never wraps past a line                                        |

## Accessibility
- **Chips are toggle buttons (4.1.2, 1.3.1):** a group named "Sizes"; each chip is announced "XL, toggle button, pressed". Pressed = tint
  + 2 px ring + check mark, so state never rests on colour alone (1.4.1). Arrow keys move along the group, Space toggles, one tab stop
  for the group (roving focus), so 19 chips don't mean 19 tab stops (2.1.1).
- **Contrast (1.4.3, 1.4.11):** chip border #91918B on white 3.2:1, on the dark card (#6B6B65 on #1A1A1E) 3.2:1; pressed ring Denim #2B3990
  on white about 10:1, dark #97A4EE on #1A1A1E 7.3:1; chip text #18181B on the tint #EEF0FB about 16:1, dark #F4F4F5 on the dark tint
  above 12:1; lock icon #5F5F66 on white 6.3:1, dark #A1A1AA on #1A1A1E 7.0:1. A locked chip's text keeps its full colour: greying it
  would drop below 4.5:1 and read as "disabled" when the fact is "in use".
- **Locked reason is reachable (3.3.2, 4.1.2):** the locked chip stays focusable (`aria-disabled`, never `disabled`) and its reason is its
  accessible description; the one sentence under the group is on screen for everyone. Never a silent grey chip.
- **Targets (2.5.8, Invory's 44 px):** chips 40 px tall on a laptop and 44 px on a phone, at least 44 px wide, 8 px apart; Edit buttons
  in Settings 44 px on a phone; dialog buttons 46 px.
- **Errors (3.3.1, 3.3.3, 4.1.3):** under the group with what to do, announced; focus goes to the first chip after a failed save.
- **Confirm (2.1.2, 2.4.3):** an alert dialog traps focus, Esc keeps the size, focus returns to the chip.
- **Table (1.3.1):** Settings › Sizes has header cells and a caption; the Edit button's name carries the size ("Edit XXL", 2.4.4).
- **Reflow (1.4.10):** 19 chips wrap four rows at 375 px, five at 320 px; the settings table becomes rows below `xl`, as the other
  catalogue lists do.
- **Dark mode:** every colour above is a token; the dialog needs no preview (a size has no swatch).

## Phone (375 px)
- **Settings › Sizes:** description, **Add size** full width 44 px, then 20 rows of 56 px: tag, "Used by 3 products", **Edit** (44 px).
  The "No size" badge sits after "One size".
- **Add size dialog:** full width, Name and Position stacked, **Cancel** and **Save size** stacked, Save first.
- **Sizes field:** full width, chips 44 px high, six or seven to a row; the status line and the lock sentence below. **Save product**
  stays in the sticky bar.
- **Confirm:** the alert dialog at full width, buttons stacked, **Keep XL** first.
- **Product page:** the badge wraps with the other badges; the card is as INV-2 drew it, with fewer size chips per colour.
- Board: `Inv18Phone`.

## Options and recommendation

**A. How does a product pick its sizes?** Decided in the interface by the owner, not by the brief.
- (a) **A row of toggle chips, one per size, all visible** — **recommended.** Sizes are short (S, 38); 19 fit in four rows; she sees
  what is chosen and what isn't at once; one press per size; works with the keyboard as one group.
- (b) A searchable picker with chips for the chosen sizes, like Colours. One pattern for both fields, scales to 200 sizes, but hides
  what isn't chosen and costs two taps per size for items that need no search.
- (c) Checkboxes in a list. Plain, but 19 rows push Colours and Description off the screen.

**B. Locks: stop it before saving, or refuse after?**
- (a) **Locked chips can't be pressed off, with the reason on screen** — **recommended.** The lock is known when the page opens (it
  comes from stock, orders, barcodes, production and cells), so the owner never presses, saves and is refused. The server still
  refuses with the sentence when the page was stale.
- (b) Any chip can be pressed off; Save refuses with the sentence, as the colour lock does today. Same screens as colours, but with
  up to 19 sizes and five reasons the owner would learn the locks by failing.

**C. What a category change does on Add product** (question 3).
- (a) **Replaces the sizes only while she hasn't changed them herself and no line holds quantities; otherwise keeps them and offers
  "Use the sizes of Footwear"** — **recommended.** No work is thrown away by a second thought about the category.
- (b) Always replaces (and asks first when quantities exist). Simple to say; one wrong category click overwrites her choices.

**D. Reordering.**
- (a) **Position field in the dialog, with the Position column visible** — **recommended**, as decided ("as for colours"), nothing new to
  learn, one write.
- (b) Also Move up and Move down on each row, as Production phases has. Nicer for nudging one size, but a second way to do the same thing,
  and moving XXS from the bottom to the top is 19 presses. Could be added later without changing anything above.

**E. The size name's length.** (a) **Up to 12 characters** — **recommended** ("One size" is 8, "38.5" is 4; a longer name wraps
every table header). (b) 32 like colours; a header can then be 32 characters wide.

## For the product-owner
Proposed content for the UI card `INV-18-product-sizes-in-settings-ui.md`:
- **Pages:** Settings menu and Settings › Sizes; Add size and Edit size dialogs; the Sizes field on Add and Edit product, the locked-size
  state and the refusals; the take-off confirm; the product page's Sizes badge and Sizes & colours card; (if the human says yes to
  question 1) Settings › Product categories with its Suggests column and Suggested sizes group. Boards as listed at the top.
- **Wording:** every quoted string in Layout and States, and these toasts: "Size created." · "Size updated." · "Product created." ·
  "Product updated.".
- **Proposed criteria (adopt or change):**
  - **A list in Settings.** Given a workshop, then Settings › Catalogue › Sizes lists its sizes in order with the words "Used by 3
    products" / "Not used", a new workshop starts with XS, S, M, L, XL, XXL, XXXL, 36 to 46 and One size, and **Add size** and **Edit**
    are the only actions.
  - **Rename and order.** Renaming XXL to 2XL shows "2XL" on every product that has it, in its variant SKUs ("PRD-JKT-001-IND-2XL")
    and in the barcode lists, and changes no barcode; a Position of 15 lists a size between the sizes numbered 10 and 20.
  - **Pick on the product.** In Add and Edit product, a **Sizes** field shows a chip for every size of the list, chosen ones pressed.
    Choosing the category Apparel on Add product presses XS to XXXL; the owner can press any off.
  - **At least one size.** Saving with none pressed shows "Choose at least one size." and saves nothing.
  - **Existing products.** Every product keeps exactly its sizes: the Denim Jacket still has XS to XXXL until the owner changes it.
  - **In use stays.** On Edit product, a size with stock, orders, barcodes, production or material quantities shows a lock and can't be
    pressed off; the refusal sentence is "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has barcodes.".
  - **The product's sizes everywhere.** The page's badge, "3 colours × 4 sizes = 12 variants", the stock table, the barcodes, Update
    stock, Restock, order lines and Start batch show only the product's own sizes, in Settings order.
  - **Take off with quantities (INV-16).** On Add product, pressing a size off while a line holds quantities for it asks "Take XL off
    this product?" and, on confirm, clears them.
- **Rules the development card will need:** one list per workshop, seeded at sign-up and for existing workshops from today's three
  lists; names unique ignoring capitals, up to 12 characters; a size is never removed; a product has at least one size; One size
  excludes the others; the category no longer locks anything; the lock is per size and per product (stock, orders, barcodes,
  production, INV-16 cells); renaming changes variant SKUs and no barcode.
- **Leave out of INV-18** (say so in the Description): deleting or archiving a size, a size with its own code, size groups, sizes on the
  Products list or its filters, the materials' own sizes, and changing INV-16's grid beyond its sizes and its take-off confirm.
- **INV-16's cards** (both `Approved`/`Ready`): the "Change the category? …" confirm in AC-1 and the category lock in AC-3 are replaced
  by this spec's take-off confirm and the size lock. Their wording changes need the human's yes.

## For the tech-lead
- **Values the server sends:** the workshop's sizes (id, name, position, how many products use it, whether it is the "One size" one);
  for a product, its sizes in list order; the product's **sizes line** ("Sizes S, M, L, XL", "Sizes XS to XXXL", "One size",
  "Sizes XS, S, M and 4 more": one rule, server-side, like the colour and SKU lists; the screens never work out a range); per size on
  Edit, the lock sentence or none ("Can't be taken off: it has units in stock, is on orders and has barcodes."); per category, its
  suggested sizes and their line ("Suggests"); on the Edit form, the count of material quantities typed per size for the confirm (as
  INV-16 already needs for its switch-back confirm).
- **Writes:** Add and Edit size (name, position; name unique ignoring capitals, 12 characters, position as colours); the product's
  sizes (at least one, from its workshop, One size alone, locks checked under the product's row lock); the category's suggested sizes
  (if question 1 is yes).
- **Same tie order for positions as colours** except that ties break by id, not by name (sizes sorted by name would read L, M, S).
  Seed positions 10, 20 … 190 so a new size can go between.
- **Migration:** build the workshop's list from today's three lists (19 sizes) keeping their ids, point every product at its category's
  current sizes, so every product keeps exactly what it has (decision 3); `ProductSizeLock` becomes a per-size lock; ADR-0015 needs an
  amendment. "One size" must be a marker on the row, not the name (it can be renamed).
- **Everything that read a category's size list** (order lines, Update stock, restocks, batches, barcodes, labels, INV-16's grid) reads
  the product's sizes instead.
- **New shadcn component:** none (toggle group, tooltip, alert dialog, dialog, table, badge exist). The locked chip needs `aria-disabled`
  with a description, not `disabled`.
- **Costly or risky:** the five-way lock per size (stock, orders, barcodes, production, cells) on every save; the migration of every
  product's sizes; a barcode lock that is **permanent** (barcodes are never deleted, unlike stock, which can be set to 0), see question 4;
  INV-16's Technical work replanned.

## Questions for the product-owner / human
1. How does the category pre-select once sizes are no longer its own? (a) **The Edit category dialog gets "Suggested sizes", and the
   table a "Suggests" column — recommended**, small and the same chips · (b) the three starting categories keep what they suggest today,
   a category added in Settings suggests "One size", no screen to change it · default if unanswered: (a).
2. May "One size" be chosen together with other sizes? (a) **No: pressing it turns the others off, and the reverse — recommended** (a
   one-size product has no size in its SKU, no size columns) · (b) yes · default: (a).
3. After she changes the sizes by hand on Add product, what does a category change do? (a) **Keeps them and offers "Use the sizes of
   Footwear" — recommended** · (b) replaces them always · default: (a).
4. The barcode lock never lifts: barcodes are never deleted, so a size that ever got a barcode on a product stays on it for good, while
   stock can be set to 0. Keep it as decided? (a) **Yes, as decided — recommended**, a barcode is on printed labels · (b) barcodes don't
   lock a size, as they don't lock a colour (they stay under the name and come back if the size is added again) · default: (a).
5. A rename changes variant SKUs ("-XXL" to "-2XL"); barcodes stay. (a) **Accept, with the sentence in the Edit dialog — recommended**
   (the owner already changes SKUs by editing a product's SKU) · (b) give every size a fixed code like colours' (a second field to
   learn) · default: (a).
6. Reorder: (a) **Position field, as colours — recommended** · (b) also Move up and Move down · default: (a).
