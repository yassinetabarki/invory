# INV-1 · Colours as fixed values — design spec
Status: proposal, decisions taken 2026-10-25 (see "Decisions") · Designer · 2026-10-24 · Boards: [Invory App UI
canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc), new section "Colours (INV-1)":
- "INV-1 · Product form and page · Colours field, picker, states · desktop" (`project/ColourPicker.dc.html`)
- "INV-1 · Settings › Colours · list, used, archived · desktop" (`project/ColourSettings.dc.html`)
- "INV-1 · Settings › Colours · add, edit, errors, delete · desktop" (`project/ColourDialogs.dc.html`)
- "INV-1 · Product form, picker, Settings › Colours · 375" (`project/ColourPhone.dc.html`)
- "INV-1 · The 20 default colours · light and dark" (`project/ColourPalette.dc.html`)

The boards were drawn before the decisions and are left as they are. Where they differ from this spec, this spec wins: INV-1 has
**no archive, restore or delete** of a colour, so the Archive / Restore / Delete buttons, the "Archived" badge and row, the "Stone wash
· archived" chip, the "archived meanwhile" error, the Delete colour confirm and the archive / restore / delete toasts on the boards are
not part of INV-1.

Reference boards (the human's, not changed): "Product detail with sizes, colors and barcodes" (`SizeProductShow.dc.html`) and "Update
stock dialog, per color" (`SizeUpdateStock.dc.html`). Their look is kept: a 14 px rounded square swatch beside the name, and round
colour pills for picking one colour.

## Problem and user
The workshop owner sets up products on a laptop at the cutting table and checks them on a phone in the stockroom. From INV-2 on, every
product will count its stock, barcodes and orders per colour and size, so a colour has to be the same colour every time: "Indigo" can't
also be "indigo", "Indgo" or "Dark blue". INV-1 makes colours a list the workshop picks from, never words typed on a product.

The question each colour screen answers: **which colours does this product come in?** The answer is always a small swatch **and** the
colour's name: ■ Indigo · ■ Black · ■ Light wash.

## How it looks today
- **Products have no colour at all.** Add / Edit product has Details (SKU, Name, Category with "Sizes: S M L XL" and "Sizes come with the
  category.", Status with "Only active products can be sold.", Description), Costs and price, Photo and Composition. The product page
  header shows "PRD-JKT-001", the category badge "Apparel", the badge "Sizes S, M, L, XL" and the status.
- **Materials have a free-text colour.** The material form has "Colour (optional)" with the placeholder "e.g. Indigo, Antique brass";
  the material page shows "Colour  Indigo" (or a dash). Anything can be typed.
- **Settings › Catalogue** has Units, Material categories and Product categories. Each is a table (Name, Code, Used by, Status, and
  Edit / Archive / Restore / Delete), cards below laptop width. Delete stays visible but can't be pressed while something uses the row,
  and says why ("Used by 3 products"); unused rows read "Not used". Archived rows are hidden from the forms' lists but stay on the
  records that use them (Edit product shows "Apparel (archived)"). Toasts read "Category created.", "Category archived." and so on.
- **The human's boards** already draw colours: "4 sizes · 3 colors" in the product header, "In stock" per colour (■ Indigo 6 · ■ Black 4
  · ■ Light wash 0), a "Manage sizes & colors" button that opens Edit product, and round colour pills in "Update stock".

## Flow
1. **Once, in Settings.** Settings › Catalogue › **Colours** lists the workshop's colours, each as swatch + name, with its code and how
   many products use it. Every workshop starts with the 20 default colours below. The owner adds "Stone wash" with **Add colour**
   (name, code, swatch) or changes a colour's name or swatch with **Edit**. A colour, once in the list, stays there: INV-1 has no
   archive and no delete (human's decision, 2026-10-25).
2. **On a product.** In Add / Edit product, under Category and Status, the **Colours** field shows the chosen colours as chips. **Add
   colour** opens a short searchable list of the workshop's colours; the owner types "ind", presses Enter on Indigo, picks Black and
   Light wash the same way, and presses **Done**. The chips appear in the Settings order, not the click order: Black, Indigo, Light wash
   (decided). Each chip has a ✕ named "Remove Indigo"; removing a colour from a product is the only "remove" in INV-1.
3. **Save product** → toast "Product updated." → the product page shows **Colours ■ Black ■ Indigo ■ Light wash** under the header.

## Layout

### Building blocks (used everywhere a colour shows)
- **Swatch:** a 14 × 14 px square with 4 px corners, filled with the colour, with a 1 px edge in the field-border colour (see
  Accessibility). Always next to the colour's name; never alone. The swatch's fill is the same in light and dark mode (it is the
  fabric's colour); only its edge follows the theme. Screen readers skip the swatch and read the name.
- **Colour tag (read-only):** swatch + name, no border, as in the board's "In stock" line. Used on the product page and in Settings.
- **Colour chip (removable, in forms):** a round pill (the board's `pill` look): swatch, name, then a ✕ button. 40 px tall on a laptop,
  44 px on a phone; the ✕ has a 44 × 44 px target on a phone.
- **Colour option (in the picker):** one row: a check mark when chosen (empty space when not), swatch, name, the code in grey numbers
  font on the right (IND). Row height 44 px.

### Product form · Colours field (Add product and Edit product)
In the **Details** card, a full-width row after Category / Status, before Description:
- Label **Colours** with "(optional)" (decided: colours are optional on a product), hint under it: "The colours this product is made in, from your list in
  Settings › Colours."
- The chosen chips in a row that wraps, then an outline **Add colour** button (plus icon) at the end of the row.
- Nothing chosen: the grey line "No colours chosen yet." and the **Add colour** button.
- Components: Label, Button (outline), Popover + Command (the searchable list, as the material picker in Composition already uses).

### The colour picker
- Opens under **Add colour**, about 320 px wide. Top: a search field labelled "Search colours". Then all the workshop's colours in
  Settings order, chosen ones checked. Typing filters by name or code ("ind" finds Indigo; "BLK" finds Black).
- Enter or click toggles a colour; the list stays open so several can be picked. Bottom: "3 chosen" on the left, **Done** on the right.
  Esc or **Done** closes it and puts focus back on **Add colour**.
- No match: "No colour named "teal"." and under it "Missing one? Add it in Settings › Colours." (the link opens Settings in a new tab,
  so the form keeps what was typed; its name says "opens in a new tab").

### Product page · Colours line
- In the header, a line under the badges row (SKU · Apparel · Sizes S, M, L, XL · Active): grey "Colours" then the colour tags
  "■ Black ■ Indigo ■ Light wash", wrapping on a narrow screen. Screen readers hear "Colours: Black, Indigo, Light wash".
- No colours: grey "Colours" then "None yet ·" and the link **Add colours** (opens Edit product with focus on the Colours field).
- The three figures ("Can produce now", "In stock", "Unit cost") don't change in INV-1. Stock per colour, the "4 sizes · 3 colours"
  badge and the "Sizes & colours" card are INV-2.

### Settings › Colours
- Settings nav, group **Catalogue**: Units, Material categories, Product categories, **Colours**.
- Page header: **Colours**, description "The colours your products come in. Each one shows as its swatch and its name. Rename any
  of them, or add your own.", primary button **Add colour**.
- Table (laptop): **Colour** (swatch + name) · **Code** (IND, numbers font) · **Used by** ("3 products", "1 product", "Not used") ·
  one action, **Edit**, named "Edit Indigo". No Status column (every colour is active), no Archive, Restore or Delete. Colours in
  their order.
- Below laptop width: one card per colour (swatch + name as the title, code, used by, and an **Edit** button), as Units today.

### Add colour / Edit colour dialog
- Title **Add colour** (or **Edit Indigo** with "Code IND can't be changed." under it).
- **Name** (up to 32 characters).
- **Code** (only when adding): hint "2 to 4 capital letters or digits, such as IND. It can't be changed later."
- **Swatch**: a 44 × 44 px colour well (opens the system colour chooser; name "Pick swatch colour") beside a text field with the colour
  code "#3E4C7E". Hint: "Pick the colour, or type its code, such as #3E4C7E."
- **Preview**: the colour tag as it will look, on a light strip and on a dark strip ("On a light screen", "On a dark screen"), so the
  owner sees that a pale or very dark colour still reads.
- **Position (optional)**, as in Units and categories ("Lower numbers are listed first. Leave empty to list it last.").
- In **Edit Indigo**, the swatch hint adds: "A new name or swatch shows on the 3 products that use Indigo." (the count is a value the
  server sends; for an unused colour the sentence is left out).
- Buttons **Cancel** · **Save colour** (spinner while saving; the dialog stays open on errors).

### Phone (375 px)
- **Product form:** one column. The Colours field takes the full width; chips wrap two or three to a row; **Add colour** sits on its own
  row, full width, 44 px. Save product stays in the sticky bottom bar, as in the product form today.
- **Picker:** a full-height sheet titled **Choose colours** with ✕ "Close", the search field at the top, the list (44 px rows), and a
  sticky bottom bar "3 chosen" + **Done**. Closing returns focus to **Add colour**.
- **Product page:** the Colours line wraps under the badges; nothing scrolls sideways.
- **Settings › Colours:** cards, one per colour, **Add colour** full width under the description. The dialog becomes full width.

## States

### Product form · Colours field

| State        | What the screen shows                                                                                          |
| ------------ | -------------------------------------------------------------------------------------------------------------- |
| Default      | Chips "Black", "Indigo", "Light wash" and **Add colour**                                                      |
| Empty        | "No colours chosen yet." and **Add colour**                                                                   |
| Loading      | Nothing extra: the colours come with the page; **Save product** keeps its label and shows a spinner          |
| Error        | None from the Colours field: a colour never leaves the list, so every chosen colour can always be saved      |
| Disabled     | None: the list always holds at least the 20 defaults, so **Add colour** always works                         |
| Read-only    | None in INV-1 (removing a colour with stock or orders is INV-2's question)                                    |
| Success      | Toast "Product updated." (Edit) or "Product created." (Add), the product page opens                           |
| Long content | A 32-character name fits on one chip; 12 colours wrap onto more rows; nothing overlaps                         |

### Picker

| State   | What the screen shows                                                                              |
| ------- | -------------------------------------------------------------------------------------------------- |
| Default | 20 colours, Black, Indigo and Light wash checked; "3 chosen" · **Done**                           |
| Empty   | Search with no match: "No colour named "teal"." + "Missing one? Add it in Settings › Colours."     |
| Long    | 40 colours: the list scrolls inside the popover (about 8 rows high); the search stays on top       |

### Product page · Colours line

| State    | What the screen shows                                    |
| -------- | -------------------------------------------------------- |
| Default  | "Colours ■ Black ■ Indigo ■ Light wash"                  |
| Empty    | "Colours None yet · Add colours"                         |

### Settings › Colours

| State        | What the screen shows                                                                                                   |
| ------------ | ----------------------------------------------------------------------------------------------------------------------- |
| Default      | The 20 colours; Black "4 products", Indigo "3 products", White "2 products", others "Not used"                         |
| Empty        | None: every workshop starts with the 20 defaults and no colour is ever removed                                        |
| Loading      | As Units today (the list comes with the page); **Save colour** shows a spinner                                        |
| Error        | Field errors in the Add / Edit colour dialog (below)                                                                  |
| Disabled     | None                                                                                                                  |
| Read-only    | The code: "Code IND can't be changed." in the edit dialog                                                             |
| Confirm      | None: adding and editing need no confirmation, and there is no delete                                                 |
| Success      | Toasts "Colour created.", "Colour updated."                                                                           |
| Long content | A 32-character name wraps in its cell; 40 colours fit in one list (no paging, as Units)                               |

### Add / Edit colour dialog · errors (under the field, after Save colour)
- Name empty: "Enter the colour's name."
- Name taken (ignoring capitals): "You already have a colour named "Indigo"."
- Name too long: "Keep the name to 32 characters or fewer."
- Code empty or wrong: "Enter 2 to 4 capital letters or digits, such as IND."
- Code taken: "Code IND is already used by Indigo."
- Swatch empty or wrong: "Enter a colour code like #3E4C7E."

## Accessibility
- **Colour is never alone (1.4.1).** Every swatch has its name beside it, in every place: chips, tags, options, table, cards. The picker
  shows "chosen" with a check mark, not with a tint only; screen readers hear "Indigo, IND, selected".
- **Swatch edge (1.4.11).** The swatch's edge uses the field-border colour: #91918B on the white card **3.2:1**, on the page background
  3.0:1; in dark mode #6B6B65 on the card #1A1A1E **3.2:1**, on the page 3.5:1. Without an edge, a White swatch on a white card is
  1.0:1 and a Black (#1F1F23) swatch on the dark card 1.1:1, so the shape would vanish. The human's boards draw a fainter edge
  (black at 18 %, about 1.5:1 on white); the human chose the field-border edge (2026-10-25). Strictly, the name already carries the meaning, so the swatch is a support,
  but a visible edge keeps White, Ecru, Beige, Light wash, Lilac, Pink, Mustard and Khaki readable on white, and Black, Charcoal, Navy,
  Indigo, Brown, Burgundy, Forest green and Red readable in dark mode.
- **Text** on chips, tags and options uses the normal text colour (#18181B on white 17:1; #F4F4F5 on the dark card 16:1). The code in
  the picker uses the grey text colour (#5F5F66 on white 6.3:1; #A1A1AA on the dark popover 6.4:1).
- **Focus (2.4.7, 2.4.11).** The focus ring (Denim) shows on every chip ✕, option, button and the colour well; the picker never hides the
  focused option under its sticky footer; the phone sheet traps focus and returns it to **Add colour**; Esc closes.
- **Keyboard (2.1.1).** Add colour → type to search → arrows → Enter toggles → Tab to Done. Each chip's ✕ is its own button, so the
  chosen colours can be removed without the mouse. No dragging (2.5.7): the order comes from Settings.
- **Names (4.1.2, 2.4.4).** "Remove Indigo", "Edit Indigo", "Pick swatch colour"; the Colours field's
  chips are a labelled group "Colours".
- **Targets (2.5.8, Invory 44 px).** Chip ✕ 44 × 44 on a phone, options 44 px tall, colour well 44 × 44, Add colour 44 px.
- **Errors (3.3.1, 3.3.3).** Under the field, with what to do; announced; focus moves to the first one.
- **Reflow (1.4.10).** Chips and tags wrap at 320 px; the Settings table becomes cards.
- **Dark mode.** Swatch fills stay the colour itself; the edge, text, chip border and focus ring follow the theme tokens. The dialog's
  preview shows both, so a colour that disappears on one background is visible before saving.

## The 20 default colours
Every workshop starts with these, in this order (neutrals light to dark, then blues, pinks and reds, warm colours, greens). Indigo,
Black and Light wash are the human's board values. "Edge needed on" names the background on which the swatch would be hard to see
without its edge (under 3:1).

| #  | Name         | Code | Swatch  | Edge needed on |
| -- | ------------ | ---- | ------- | -------------- |
| 1  | White        | WHT  | #FFFFFF | light          |
| 2  | Ecru         | ECR  | #EFE6D2 | light          |
| 3  | Beige        | BEI  | #D8C3A0 | light          |
| 4  | Camel        | CAM  | #B98A57 | —              |
| 5  | Brown        | BRN  | #6B4A32 | dark           |
| 6  | Grey         | GRY  | #8C8C8C | —              |
| 7  | Charcoal     | CHA  | #3D3D42 | dark           |
| 8  | Black        | BLK  | #1F1F23 | dark           |
| 9  | Navy         | NAV  | #1F2A44 | dark           |
| 10 | Indigo       | IND  | #3E4C7E | dark           |
| 11 | Light wash   | LWH  | #9DB3D1 | light          |
| 12 | Lilac        | LIL  | #B3A2D3 | light          |
| 13 | Pink         | PNK  | #E8A9B8 | light          |
| 14 | Red          | RED  | #B8282E | dark           |
| 15 | Burgundy     | BUR  | #6E1F2E | dark           |
| 16 | Rust         | RST  | #B5552B | —              |
| 17 | Mustard      | MUS  | #D4A12A | light          |
| 18 | Khaki        | KHA  | #A69F74 | light          |
| 19 | Olive        | OLV  | #5F6B3A | —              |
| 20 | Forest green | FGR  | #2F5A43 | dark           |

Seed products for the examples: Denim Jacket → Black, Indigo, Light wash · Classic White T-shirt → White, Black, Navy · Denim Jeans
5-pocket → Indigo, Light wash · Leather Tote → Camel, Black · Canvas Low Sneaker → White.

## Options considered (all decided, see "Decisions")

**A. Who owns the colour list?** Decided: (a), per workshop.
- (a) **Each workshop has its own list, managed in Settings › Colours, starting from the 20 defaults** (add and rename), like Units
  and categories. **Recommended:** the human's own board uses "Light wash", a denim wash no generic
  palette would hold; washes, "Stone wash", "Bordeaux" differ per workshop; it reuses a pattern the owner already knows.
- (b) One fixed palette, the same for every workshop, set by the app; no Settings page. Simplest and identical everywhere, but a missing
  colour can't be added without a new app version.
- (c) A fixed app palette in which each workshop turns colours on or off; no own colours. Shorter pickers, but the same gap as (b).

**B. Where are a product's colours chosen?** Decided: (a).
- (a) **In Add / Edit product, a Colours field in Details** (like the sizes beside Category; the board's "Manage sizes & colors" already
  opens Edit product). **Recommended:** one place to set up a product.
- (b) A separate "Manage colours" dialog from the product page. Quicker for one change, but a second place to edit a product.
- (c) Colours come with the category, like sizes. Doesn't fit: two jackets of the same category rarely share colours.

**C. What does the picker look like?** Decided: (a).
- (a) **A searchable list (Popover + Command on a laptop, full-height sheet on a phone) with chips for the chosen colours.**
  **Recommended:** the material picker in Composition already works this way; it stays short with 40 colours; typing "ind" is fast.
- (b) A grid of swatch tiles, all visible, tap to toggle. Very visual for 20 colours, but it takes 10 rows on a phone and grows with
  every colour the workshop adds.
- (c) A plain checkbox list, always open in the form. Simple, but long, and it pushes Description and Costs far down.

**D. What happens to a colour that is removed?** Decided by the human: a colour is never removed in INV-1. There is no archive, no
restore and no delete, so the archive / delete options proposed earlier are dropped. Removing a colour from one product (the chip's ✕)
stays.

**E. The materials' "Colour" field.** Decided: (a), stays free text.
- (a) **Stays free text in INV-1** ("Antique brass", "Natural"): a material's colour describes what was bought, it isn't a variant, and
  hardware finishes would crowd the product list. **Recommended.**
- (b) Picks from the same colour list. One rule everywhere, but brass, silver and gold join the product colours, and today's typed
  colours must be matched by hand.
- (c) Picks from the list, with "Other…" for a typed one. Both behaviours at once; the hardest to explain.

## For the product-owner
Proposed content for the UI card `INV-1-colours-ui.md` (wording exactly as above):
- **Pages:** Settings › Colours (list, Add / Edit colour dialog); Add / Edit product, Colours field and picker; product page,
  Colours line. Boards as listed at the top (ignore their archive and delete parts).
- **States:** the tables above: the empty Colours field, "None yet · Add colours" on the product page, the picker's no-match line, the
  Add / Edit colour field errors, long content.
- **Wording:** every quoted string in Layout and States; toasts "Colour created.", "Colour updated.", "Product updated.", "Product
  created.".
- **Phone layout:** as in "Phone (375 px)".
- Rules the development card will need as criteria (decided): the 20 defaults for every workshop, existing ones included; the list is
  per workshop; colours are optional on a product; names unique per workshop ignoring capitals, up to 32 characters; code 2–4
  capitals or digits, unique, never changed; a product's colours shown in Settings order; a colour, once in the list, is never
  archived or deleted.
- Leave out of INV-1 (say so in the Description): removing a colour from the list, stock, barcodes, orders and sales per colour (INV-2
  and later), colours on the Products list, a colour filter, and the materials' Colour field (stays free text).

## For the tech-lead
- **Values the server sends:** the workshop's colours (name, code, swatch colour, order, how many products use each); for a product,
  its colours in list order.
- **Every workshop gets the 20 defaults**, new ones at sign-up and existing ones when INV-1 ships.
- **No archive, restore or delete** of a colour in INV-1, so no "in use" checks and no "changed meanwhile" errors on saving a product.
- **Components:** all installed already: Popover + Command (picker), Sheet (phone picker), Dialog and the Settings list of Units and
  categories (with a Colour column and a Swatch field added, and only the Edit action), Badge, Tooltip. The swatch field uses the browser's own colour chooser
  next to a text field; no new library.
- **Costly or risky:** low. The Settings page is the fourth list of the same kind. The code chosen now is what INV-2 puts in variant
  SKUs (the board's "JKT-001-IND-S"), so it must never change once created.
- **Spelling:** "Colour" on every screen (decided), as the app says today; the boards say "color".

## Decisions (2026-10-25)
The human decided questions 1, 4, 7, 8, 9 and 11. The product-owner decided questions 2, 3, 5, 6 and 10 as recommended.

| #  | Question                                | Decision                                                      | By            |
| -- | --------------------------------------- | ------------------------------------------------------------- | ------------- |
| 1  | Who owns the colour list?               | Each workshop, in Settings › Colours                          | Human         |
| 2  | Where are a product's colours chosen?   | A Colours field in Add / Edit product                         | Product-owner |
| 3  | What does the picker look like?         | Searchable list with chips; full-height sheet on a phone      | Product-owner |
| 4  | What happens to a removed colour?       | Not in INV-1: a colour is never removed (no archive, delete)  | Human         |
| 5  | The materials' "Colour" field?          | Stays free text                                               | Product-owner |
| 6  | Spelling on screen?                     | "Colour"                                                      | Product-owner |
| 7  | Must a product have a colour?           | No, colours are optional                                      | Human         |
| 8  | The 20 default colours?                 | As listed                                                     | Human         |
| 9  | The swatch's edge?                      | The field-border grey (3.2:1 in both themes)                  | Human         |
| 10 | Colours on the Products list in INV-1?  | No                                                            | Product-owner |
| 11 | Order of a product's colours?           | Settings order                                                | Human         |

- The human's words on question 4: "ignore this cases for now image that the color will never be removed".

No questions are open.
