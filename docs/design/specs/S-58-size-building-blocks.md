# S-58 · Size building blocks and states: board against the app — design spec
Status: proposal · Designer · 2026-10-23 · Boards: [Design canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc), section
"Sizes · products (S-58, S-56)": "Size building blocks and states" (`project/SizeDetails.dc.html`) and "Denim Jacket with sizes"
(`project/SizeProductShow.dc.html`). Canvas note "sizenote".

## Problem and user
The workshop owner meets sizes in six places: the product form, the product page, the order form, the order page, the Orders list and
the move window. S-58, S-60, S-61 and S-56 each built their own piece, so the same idea ("Denim Jacket comes in S, M, L, XL"; "this
order holds S 10 · M 20") looks different from one screen to the next. The board "Size building blocks and states" fixes one look for
each piece. The question every block answers: **which sizes, and how many of each?**

This spec compares every block on the board with what the app shows today (read from the code on `main`, 83062eb; the running app was
not walked) and proposes how to close each gap. The board wins over a delivered build (the human's rule, applied to S-56 today), except
where the board contradicts an approved card; those are listed under "Conflicts" for the human.

## Gap table

| #   | Block                                    | Story       | Today vs board | Visibility |
| --- | ---------------------------------------- | ----------- | -------------- | ---------- |
| B1  | Sizes under the order line heading       | S-60 AC-3   | Differs        | High       |
| B2  | "One size 50" on a line before sizes     | S-60 AC-5   | Differs        | High       |
| B3  | Sizes next to the category, product page | S-58 AC-2   | Differs        | High       |
| B4  | One size's error in a row of size fields | S-60 AC-2   | Differs        | Medium     |
| B5  | Started minimum under a size, Edit order | S-61 AC-5   | Missing        | Medium     |
| B6  | Size field shape (width, label, height)  | S-60 AC-1   | Differs        | Medium     |
| B7  | Sizes line under "Category", form        | S-58 AC-3   | Differs        | Medium     |
| B8  | Unit cost card's second line             | S-58 board  | Differs        | Medium     |
| B9  | Sizes in the Orders list's Products cell | S-60 AC-3   | Differs        | Low        |
| B10 | A shoe size's label inside a chip        | S-56 AC-2   | Open question  | Low        |
| B11 | Category refused: product on orders      | S-58 AC-4   | Matches        | —          |
| B12 | Category refused: units in stock         | S-56 AC-4   | Board conflict | —          |
| B13 | Every size left empty                    | S-60 AC-2   | Matches        | —          |
| B14 | "Can produce now" second line            | S-58 AC-2   | Matches        | —          |
| B15 | "In stock" chips, M 0 greyed             | S-56 AC-2   | Matches        | —          |
| B16 | "One size" product keeps "Quantity"      | S-60 AC-1   | Matches        | —          |
| B17 | Stage table, a row per size plus Total   | S-61 AC-1   | Matches note   | —          |

Nothing on the board belongs to S-57 (cancel an order, not built). Settings › Categories shows no sizes, on the board and in the app
(S-58 decision: "Settings shows no sizes"), so it has no gap.

### Details, block by block

**B1 · Sizes under the order line heading (S-60 AC-3) — differs.**
- Board: under "Denim Jacket · PRD-JKT-001 · 50 units", one **chip per size ordered**: the size on grey, its count beside it in the
  number font: [S | 10] [M | 20] [L | 15] [XL | 5]. Zero sizes left out. The SKU is in the number font.
- App: a plain grey line of text, "S 10 · M 20 · L 15 · XL 5"; the SKU in the body font.
- Proposal: the same chip as the "In stock" figure (B15), one shared building block, so a size reads the same on the product page and
  on the order page. Chips wrap at 375 px. Screen readers keep hearing the card's text once, "S 10, M 20, L 15, XL 5", not four
  separate chips (as the "In stock" figure already does).

**B2 · A line recorded before sizes (S-60 AC-5) — differs.**
- Board: a single chip [One size | 50].
- App: the text "One size 50".
- Proposal: the same chip as B1. The board's grey note "Line recorded before sizes (S-60 AC-5)" is an annotation for the reader of the
  board, not screen text; the order page adds no hint (the hint "Recorded before sizes." stays in Edit order, as today).

**B3 · Sizes next to the category on the product page (S-58 AC-2) — differs.**
- Board: under "Denim Jacket", in the row PRD-JKT-001 · [Apparel] · [Sizes S, M, L, XL] · [Active], the sizes are an **outlined pill**
  (white background, thin border, dark text) of the same height as the category and status badges.
- App: plain small grey text "Sizes S, M, L, XL" between the badges, so it reads as a caption, not as a fact of the product.
- Proposal: an outlined badge (shadcn badge, outline) with the same words: "Sizes S, M, L, XL", "Sizes 36 to 46", "One size". The
  words don't change, so S-58 AC-2 holds as written.

**B4 · One size's error in a row of size fields (S-60 AC-2) — differs.**
- Board: the row of fields stays as it is (S, M, L, XL side by side); the wrong field gets the red border; the message sits **under the
  row** and names the size: "M: Enter a whole number from 0 to 1,000,000."
- App: the field in error jumps onto a row of its own, with "Enter a whole number from 0 to 1,000,000." under it, so the other sizes
  move and the row no longer reads S, M, L, XL.
- Proposal: follow the board. The size is named the way the 2026-10-22 rule names sizes in text: "M: Enter a whole number from 0 to
  1,000,000." for a letter size, "Size 36: Enter a whole number from 0 to 1,000,000." for a number. Two sizes in error give two lines,
  in size order. A message that already names the size ("M can't be less than the 10 units already in cutting, ready to ship or
  shipped.", "You can move at most 20 units of M.") shows as it is, without a second name. The same rule applies wherever the size
  fields are used: the order form, the move window and "Update stock".
- This changes where the message shows, which S-60 AC-2 words as "under that size": see Conflicts, C3.

**B5 · The started minimum under a size in Edit order (S-61 AC-5) — missing.**
- Board: in "Edit order", with 10 M already in cutting, ready to ship or shipped, a small lock icon and "min 10" under the M field,
  before you type anything. The error, after saving M 8, is the card's: "M can't be less than the 10 units already in cutting, ready to
  ship or shipped."
- App: no hint; you learn the minimum only from the error.
- Proposal: show the hint under every size that has started units, with the lock icon. Proposed wording "At least 10" rather than
  "min 10" (plain words, no abbreviation; microcopy rule). Sizes with nothing started show no hint. The number is a value the server
  sends per size (see For the tech-lead).

**B6 · Size field shape (S-60 AC-1, AC-6) — differs.**
- Board: each field is 64 px wide and 44 px high, the figure centred, the size label small and bold above it (13 px), 8 px between
  fields. At 375 px a row holds 4 fields, so the 11 shoe sizes take 3 rows.
- App: each field is 96 px wide and 36 px high, the figure left-aligned, the label at the normal label size, 12 px between fields. At
  375 px a row holds 3 fields, so the 11 shoe sizes take 4 rows.
- Proposal: follow the board, with one correction for long content: 64 px cuts off a 7-digit figure ("1000000"), which the fields
  accept. Recommended: the board's look with a width that fits 7 digits (about 80 px; still 4 to a row at 375 px). The 44 px height
  meets Invory's touch rule; today's 36 px fields are the app's input height everywhere, so raising it only here is a choice (Q5).

**B7 · Sizes line under "Category" in the product form (S-58 AC-3) — differs.**
- Board: under the Category field, a line in body size: grey "Sizes:" then, for Apparel, one outlined tag per size [S] [M] [L] [XL];
  for Footwear, grey "Sizes:" then **36 to 46** in bold (the list's name, not 11 tags); for Bag, **One size** in bold. Under it the hint
  "Sizes come with the category." in small grey text.
- App: both lines in small grey hint text: "Sizes: S, M, L, XL" (plain) and "Sizes come with the category.".
- Proposal: follow the board. It needs one rule the board leaves implicit: when to show tags and when the list's name. Recommended: tags
  when the list has at most 6 sizes, the list's name otherwise (Q4). The words screen readers hear stay the card's: "Sizes: S, M, L,
  XL", "Sizes: 36 to 46", "One size", then the hint.

**B8 · The unit cost card's second line (product page board, no card) — differs.**
- Board: "Unit cost TND 35.754" with "same for every size" under it.
- App: "materials, labor and overhead" under it.
- Proposal: "same for every size" when the product has more than one size, today's "materials, labor and overhead" for a "One size"
  product (the board shows only the jacket). It mirrors "Can produce now … any mix of sizes". The "Cost breakdown" card below still
  says what the cost is made of.

**B9 · Sizes in the Orders list's Products cell (S-60 AC-3) — differs (styling only).**
- Board: "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)" with the count "50" in the number font and the sizes part grey.
- App: the same words; the count isn't in the number font and the sizes part is in the text colour.
- Proposal: follow the board. Words unchanged.

**B10 · A shoe size's label inside a chip (S-56 AC-2) — open question.**
- The 2026-10-22 rule gives a number size a colon in running text ("36: 4 · 37: 8") and the bare label in fields and table rows. The
  "In stock" chip today shows [36: | 4]. The board draws only letter sizes, so it doesn't settle it. Inside a chip the grey box
  already keeps the two numbers apart, so the colon is redundant there. Recommended: the bare label in a chip, [36 | 4], on the
  product page and on the order page alike; screen readers keep hearing "36: 4" in the spoken line (Q3).

**B11 · Category refused because the product is on orders (S-58 AC-4) — matches.** The Category field turns red, the sizes line of the
chosen category (Footwear "36 to 46") stays, and the card's message shows under it: "Denim Jacket is on orders, so its category can't
change to one with other sizes. Keep a category with sizes S, M, L, XL."

**B12 · Category refused because of units in stock (S-56 AC-4) — the board is shorter than the card.** The board shows "Denim Jacket
has units in stock, so its category can't change to one with other sizes." The card and the app add "Keep a category with sizes S, M,
L, XL." Keep the card's wording (C2); the board looks abbreviated.

**B13 · Every size left empty (S-60 AC-2) — matches.** Every field is marked in error and "Enter at least one unit of Denim Jacket."
shows under the fields. The app also shows "Total 0 units" above the message; the board leaves the Total out of this block, but the
canvas note puts the Total under the last row, so the app matches the note.

**B14 · "Can produce now" (S-58 AC-2) — matches.** "112 units", "from current stock, any mix of sizes"; "from current stock" for the
Leather Tote.

**B15 · "In stock" chips (S-56 AC-1, AC-2) — matches.** [S | 3] [M | 0] [L | 5] [XL | 2] with M 0 greyed, "Update stock" on the label's
row, chips that wrap, one spoken line "In stock per size: S 3, M 0, L 5, XL 2". This is the chip B1 and B2 reuse.

**B16 · "One size" product (S-60 AC-1) — matches.** "12 × Leather Tote" on the Orders list, a single "Quantity" field in the form.

**B17 · Stage table (S-61 AC-1, AC-7) — not drawn on this board; the app matches the canvas note.** One row per size ordered, a Total
row last on a wide screen; at 375 px the Total first, then one card per size.

## Conflicts with decisions already taken

- **C1 · "Zero sizes are left out of summaries" (canvas note, S-60 AC-3) and "M 0" greyed (S-56 AC-2, approved today).** Both are right
  for what they show: an order line lists **what was ordered**, and a size with 0 units was not ordered (it has no row at all); the
  "In stock" figure lists **the shelf**, and "M 0" is information ("none left"). No screen changes. Proposed reading of the note: "Zero
  sizes are left out of order summaries (order page, Orders list, history). The In stock figure lists every size, 0 greyed."
- **C2 · The S-56 refusal message.** The board drops "Keep a category with sizes S, M, L, XL."; the approved card, the business rules
  and the app keep it. Recommended: keep the card's message; the board needs no change for the build.
- **C3 · Where a size's error shows.** S-60 AC-2 says the message shows "under that size"; the board (B4) puts it under the row with the
  size named. S-60 is Done, so following the board changes the wording of a ticked criterion and needs the human's yes.
- **C4 · The colon rule in chips (B10).** Turning the order-line text into chips (B1) moves "36: 4" from running text into a chip, which
  the 2026-10-22 decision didn't foresee.
- **C5 · S-60 AC-3 describes the line's sizes as text** ("with 'S 10 · M 20 · L 15 · XL 5' under it"). Chips keep the same words for
  screen readers, so the criterion still holds; it reads slightly differently on screen.

## Flow
Nothing in any flow changes. Every proposal changes how a size looks, where a message sits, or adds a hint (B5). The steps of "New
order", "Edit order", the move window, "Update stock" and the product form stay as built.

## Layout
- **Size chip (B1, B2, B15).** One building block: the size on the muted background in bold body text, its count beside it in IBM Plex
  Mono, inside one thin border with the app's small radius; chips wrap with a small gap. Zero greyed only on the "In stock" figure.
  Desktop and 375 px are the same; at 375 px the 11 shoe sizes wrap onto two or three lines.
- **Sizes badge (B3).** Outlined badge in the header row, between the category and the status badges; wraps with them at 375 px.
- **Size fields (B4, B5, B6).** A labelled group of small fields in one wrapping row, label above each field, hint under a field
  ("At least 10", "Up to 10", "was 0"), the "Total 50 units" line under the last row, then the group's messages under the Total, one
  line per message, in size order.
- **Form sizes line (B7).** Under the Category select: the sizes line (tags or the list's name), then the hint, then any error.
- Components: shadcn badge (outline) for B3 and the B7 tags; shadcn input and label for the fields; lucide lock icon for B5. The chip is
  a new shared building block next to `components/shared` (it already exists inside the "In stock" figure; the proposal is to share it).
  No new shadcn component.

## States

| State        | Exact content                                                                                       |
| ------------ | --------------------------------------------------------------------------------------------------- |
| Default      | Chips [S 10] [M 20] [L 15] [XL 5]; badge "Sizes S, M, L, XL"; form "Sizes:" [S] [M] [L] [XL]         |
| Default      | Footwear: badge "Sizes 36 to 46"; form "Sizes: **36 to 46**"; Bag: badge and form "One size"         |
| Empty        | No category chosen on "Add product": no sizes line, no hint (as today)                               |
| Empty        | Every size empty: all fields in error, "Enter at least one unit of Denim Jacket."                    |
| Error        | "M: Enter a whole number from 0 to 1,000,000." under the row; M's field red                          |
| Error        | "M can't be less than the 10 units already in cutting, ready to ship or shipped."                    |
| Error        | Category refused: the S-58 or S-56 message under Category, sizes line of the chosen category stays   |
| Read-only    | Started size in Edit order: lock icon and "At least 10" under M before any typing                    |
| Loading      | No change: the chips come with the page; "In stock" keeps its own loading and "Try again"            |
| Long content | 7-digit counts in chips wrap whole; 7 digits fit a size field; 11 shoe sizes wrap, never scroll      |

- Disabled: no size control is disabled by these proposals (a started size can still go up; it only can't go below its minimum).
- Confirm and Success: unchanged ("Order recorded.", "Order updated.", "Stock updated.", "Product updated.").

## Accessibility
- **Chips (1.3.1, 4.1.2):** the chips are hidden from screen readers and one spoken line carries the same words, as the "In stock"
  figure does: "S 10, M 20, L 15, XL 5". Contrast: label text #18181b on the muted grey #f0f0ec is about 15:1; a greyed "0" in
  #5f5f66 on #ffffff is 6.3:1 (light); #a1a1aa on #1a1a1e is about 6.8:1 (dark). The chip border is decorative (not a control), so
  1.4.11 doesn't apply.
- **Sizes badge (1.4.1):** the words say it all ("Sizes S, M, L, XL"); no meaning by colour.
- **Errors (3.3.1, 3.3.3):** each per-size message is linked to its own field, so focusing M reads "M, invalid, M: Enter a whole number
  from 0 to 1,000,000."; focus goes to the first field in error after "Save order".
- **Started hint (3.3.2):** "At least 10" is linked to the M field as its hint, so it is heard before the user types. The lock icon is
  decorative (the word carries the meaning); #2b3a86 on white is about 10.2:1, #97a4ee on #1a1a1e about 7.3:1.
- **Targets (2.5.8, Invory 44 px):** the board's 44 px high fields meet the touch rule; today's 36 px fields pass 2.5.8 (24 px) but
  not Invory's own rule. 8 px between fields keeps neighbours apart.
- **Reflow (1.4.10):** at 320 px, 80 px fields with 8 px gaps give 3 to a row; the 11 shoe sizes take 4 rows, no sideways scroll.

## Options and recommendation
1. **How far to follow the board.**
   (a) **Every "differs" block (B1–B9) in one small follow-up story, "Sizes look the same on every screen"** — **recommended**: one
   look, one review, mostly frontend. ·
   (b) Only the high-visibility blocks (B1, B2, B3) now, the rest later — less change, the per-size error still jumps (B4). ·
   (c) Leave the build as it is and mark the board as aspirational — no work, the screens keep three looks for a size.
2. **The per-size error (B4).** (a) **Under the row, size named: "M: Enter a whole number from 0 to 1,000,000."** — **recommended**:
   the row keeps its order; needs the human's yes to reword S-60 AC-2. · (b) Keep today's "under that size" with the field on its own
   row — the card as written, but the row breaks.
3. **The field width (B6).** (a) **The board's look at about 80 px, so 7 digits fit** — **recommended**. · (b) Exactly 64 px — as drawn,
   a 7-digit figure is cut off. · (c) Keep 96 px — today's 3 to a row at 375 px.

## For the product-owner
Proposed criteria for a follow-up story (wording to adopt or change):
- **Sizes as chips on the order page.** Given Boutique Lina's Denim Jacket line (S 10 · M 20 · L 15 · XL 5), the order's page shows
  "Denim Jacket · PRD-JKT-001 · 50 units" and under it one chip per size ordered: "S 10", "M 20", "L 15", "XL 5", each with the size on
  grey and its count beside it, like the "In stock" chips. A size with 0 units has no chip. A line recorded before sizes shows one chip
  "One size 50". Screen readers hear "S 10, M 20, L 15, XL 5" once.
- **Sizes badge on the product page.** Under "Denim Jacket", between "Apparel" and "Active", an outlined badge reads "Sizes S, M, L,
  XL" ("Sizes 36 to 46" on the Canvas Low Sneaker, "One size" on the Leather Tote).
- **A size's error keeps the row.** Typing 2.5 in M and clicking "Save order" marks M in red, keeps S, M, L and XL on one row, and shows
  "M: Enter a whole number from 0 to 1,000,000." under the sizes ("Size 36: …" for a shoe size). A message that already names the size
  shows as it is.
- **Started sizes show their minimum.** In "Edit order", with 10 M in cutting, ready to ship or shipped, the M field shows a lock and
  "At least 10" under it before you type. Sizes with nothing started show no hint.
- **The form names the sizes as the board does.** Choosing Apparel shows "Sizes:" and the tags S, M, L, XL; Footwear shows "Sizes: 36 to
  46"; Bag shows "One size"; under it "Sizes come with the category.".
- **Unit cost says it's the same for every size.** The Denim Jacket's "Unit cost TND 35.754" reads "same for every size" under it; the
  Leather Tote keeps "materials, labor and overhead".
- **Size fields.** Each size field is small, its figure centred, its label above it; at 375 px four fit on a row and a 7-digit figure
  shows whole.

## For the tech-lead
- **New server value (B5):** for each size of a saved order line in Edit order, the units already started (in cutting, ready to ship or
  shipped), the same figure the line already sends as `started`, per size. The screen must not add up the three stages itself.
- **Size named in a per-size message (B4):** today the server's message under a size doesn't name it. Whether the server names the size
  in that message or the screen puts the size's name in front of it is your call; the naming rule of 2026-10-22 applies either way.
- **One shared chip:** the "In stock" chip already exists inside the finished stock figure; the order page would reuse it. No new
  shadcn component; the B7 tags and the B3 badge are the shadcn badge (outline).
- **Nothing costly:** no new figure, no formula, no data shape change apart from the per-size started count. Mostly frontend.
