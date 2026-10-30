---
id: S-71
title: The order page's stage table and its buttons look like the design
type: bug
phase: 5
priority: Should
size: S
status: Done
created: 2026-10-24
tags:
  - story
  - phase-5
---

# S-71 · The order page's stage table and its buttons look like the design

> [!abstract] Description
> As a **workshop owner**, I want **the buttons that move units to sit in two tidy rows under their figures, and the stage table to
> read like the approved design**, so that **I see at a glance which move belongs to which stage and which figures matter**.
> S-49 and S-61 were built to their criteria, but those didn't spell out the design board "Order detail per size", and next to it the
> page reads as a different table. No figure, rule, window or message changes. The order's five figures across the top, a progress bar,
> the product photo, the grey SKU and the phone layout of the sizes are S-72. Taller buttons and "Move back to …" names are S-68.

> [!info] How it works today
> - **Buttons:** on Boutique Lina's Denim Jacket after cutting S 4 · M 10, each Total figure has its buttons stacked under it, each
>   stretched to its column's width.
>   - Under To start: "Start cutting". Under In cutting: "Mark ready" and "Move back". Under Ready to ship: "Ship" and "Move back".
>   - Under Shipped: "Move back" alone. It sits beside "Ship" in the first row, while the other two "Move back"s are in a second row.
>   - "Start cutting" is wider than its column, so it starts left of the "To start" heading.
> - **Table:** it has no frame. The figures S 6 · 4 · 0 · 0, M 10 · 10 · 0 · 0, L 15 · 0 · 0 · 0, XL 5 · 0 · 0 · 0 and Total 36 · 14 ·
>   0 · 0 are in the body font, every 0 as dark as the other figures. The Total row is only bold. The size chips above use the number
>   font, so the line mixes two styles.
> - **Buttons that can't be clicked:** "Ship" with 0 ready to ship is the same blue button, only paler. A "Move back" that can't be
>   clicked almost disappears.
> - **One-size lines:** on a one-size line such as the Leather Tote, each of the four boxes has its buttons at the bottom. So "Start
>   cutting" lines up with the "Move back"s, not with "Mark ready".

## Acceptance criteria
- [x] **AC-1 · Two rows of buttons under the Total.** Take Boutique Lina's Denim Jacket at Total 36 · 14 · 0 · 0. Under the Total row,
  the first row of buttons reads "Start cutting" under To start, "Mark ready" under In cutting and "Ship" under Ready to ship, with
  nothing under Shipped. The row below reads "Move back" under In cutting, Ready to ship and Shipped, with nothing under To start. The
  Total row itself holds only the figures 36, 14, 0 and 0. Each button opens the same window as today and moves units in the same way. ✅ 2026-10-24 · 722638c
- [x] **AC-2 · Every button sits under its figure.** Each button is only as wide as its word: "Start cutting" is no wider than those two
  words need. Its right edge lines up with the right edge of the figures above it. Buttons keep today's height. At 1024 px with the
  sidebar open, the table and both rows fit without scrolling sideways. With the keyboard, Tab goes through the first row left to right
  and then the second: "Start cutting", "Mark ready", "Ship", then the three "Move back"s. Buttons that can't be clicked are skipped, as
  today. Screen readers still hear the table as its sizes and the Total; the button rows aren't read out as sizes. ✅ 2026-10-24 · 722638c
- [x] **AC-3 · One-size lines and narrow screens.** Take the Leather Tote ("One size", 12 units: To start 8 · In cutting 4 · Ready to
  ship 0 · Shipped 0). Across its four boxes, "Start cutting", "Mark ready" and "Ship" sit on one line, and the three "Move back"s on
  the line below. The To start box has nothing on the second line and the Shipped box nothing on the first. Each button is only as
  wide as its word and lines up with the left edge of its figure. When the boxes go two per row (below 1024 px, and for the Total of
  a line with sizes), the same holds in each row of boxes. "Start cutting" lines up with "Mark ready", and Shipped's "Move back" with
  Ready to ship's "Move back". ✅ 2026-10-24 · 722638c
- [x] **AC-4 · The table reads like the design.** The table sits in a light frame with rounded corners. The header row ("Size", "To
  start", "In cutting", "Ready to ship", "Shipped") is on a grey band. Every figure, the Total row's included, is in the number font,
  the same as the size chips above it. Every 0 is in the same grey as the column headings, which stays readable (6.3:1 on white):
  in the size rows and in the Total row alike, so the Total's 0 under Ready to ship and under Shipped is grey too. The Total row is
  shaded grey and has a heavier line above it than the lines between the sizes. The size labels are semibold in the body font, the
  board's and the size chips' weight. The size labels and "Total" sit as far from the frame's left edge as the figures sit from its
  right edge. In dark mode, the frame, the band, the shading and the grey zeros follow the dark theme, and the zeros stay readable. ✅ 2026-10-24 · 7e00fff
- [x] **AC-5 · A button that can't be clicked looks grey.** Every move button on the order page that can't be clicked shows as a grey
  button (grey fill, grey word, thin border), not a pale blue one. That means "Mark ready" with 0 in cutting, "Ship" with 0 ready to
  ship, a "Move back" whose stage holds 0, and "Start cutting" on a deleted product. A grey "Move back" stays clearly visible. These
  buttons still can't be clicked. The hint "Denim Jacket was deleted, so it can't be cut." stays. Buttons that can be clicked keep their
  look: "Start cutting", "Mark ready" and "Ship" filled blue, "Move back" outlined. This holds in the table, in the boxes and on a phone. ✅ 2026-10-24 · da6ff31
- [x] **AC-6 · Nothing shipped yet.** With 0 shipped (Denim Jacket at 36 · 14 · 0 · 0), the Shipped column has nothing in the first row
  and a grey "Move back" in the second. No "Nothing to move" text shows anywhere. Once units have shipped, that "Move back" is the
  outlined one and moves them back to Ready to ship, as today. ✅ 2026-10-24 · 722638c

---

## Technical work
> [!warning] Complex · NON-CRITICAL · frontend · 3 commits
> Tech-lead · 2026-10-24 · approved 2026-10-24 · delivered 2026-10-24
> Why complex: a layout choice worth recording. The button rows leave the table so screen readers don't hear them as sizes, yet they must still line up with the table's columns.

### In short
Only the order page's screen changes; the server, the figures, the windows and the messages stay exactly as they are. First the
buttons: they leave the table's cells and go into two rows of their own right under it, forward moves on top and the "Move back"s
below, drawn on the same column grid as the table so each one ends where its figures end. The one-size boxes (and the Total's boxes on
a narrow screen) get the same two lines. Then the table gets the design's look: a frame, a grey header band, the number font, grey
zeros and a shaded Total row. Last, a move button that can't be clicked turns grey, through a small order-page wrapper around the
standard button, so the shared button file is never edited by hand.

### Steps

| Step       | What it does                                           | Proves                 | Lane         | Who      | Size       |
| ---------- | ------------------------------------------------------ | ---------------------- | ------------ | -------- | ---------- |
| **S-71-a** | Two rows of buttons under the Total and in the boxes   | AC-1, AC-2, AC-3, AC-6 | NON-CRITICAL | Frontend | ~180 lines |
| **S-71-b** | The table frame, grey band, number font and grey zeros | AC-4                   | NON-CRITICAL | Frontend | ~60 lines  |
| **S-71-c** | A button that can't be clicked looks grey              | AC-5                   | NON-CRITICAL | Frontend | ~60 lines  |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green, and runs the order
golden path in Chromium (`composer test:browser`), which gains one step at 1024 px. No PostgreSQL parity: no database change.

#### S-71-a · Two rows of buttons under the Total and in the boxes
**Commit:** `fix(orders): move buttons in two rows under the Total, lined up with the figures (S-71-a)`

- **Table (from 1024 px):** the Total row holds only its four figures. Under the table, inside the same frame, a grid of two rows: row 1
  "Start cutting", "Mark ready", "Ship", empty; row 2 empty, "Move back" × 3. The grid is not part of the `<table>`, so screen readers
  still hear the sizes and the Total only. The table switches to a fixed layout with a set width for the Size column; the grid uses the
  same column template and the same cell padding, so each button's right edge meets its column's figures. Buttons keep `size="sm"`
  (32 px) and their natural width.
- **Boxes (one-size and before-sizes lines, and the Total of a line with sizes below 1024 px):** each box gets three shared rows
  (figure, forward line, back line) through CSS subgrid, so "Start cutting" lines up with "Mark ready", and Shipped's "Move back" with
  Ready to ship's, in every row of boxes, even when a figure wraps. An empty line keeps its place. Buttons sit at natural width on the
  left edge, no longer stretched.
- `order-line-card.tsx` describes each stage's moves as a forward move and a move back instead of one list, and hands both to the table
  and the boxes. No "Nothing to move" text anywhere.
- Each "Move back" is described by its stage's heading, so a screen reader hears "Move back, In cutting" now that the button has left
  the table cell (the forward buttons and the deleted-product hint keep today's description).

**Tests**
- Rewrite S-61-b's "shows a table with a row per size and the buttons in the Total row only" as "the table holds only figures; the
  buttons sit in two rows under the Total" (AC-1, AC-6): the table's rows end with Total 50 · 0 · 0 · 0 and hold no button; row 1
  reads Start cutting, Mark ready, Ship, empty; row 2 empty, Move back × 3 (Shipped's disabled at 0 shipped); no "Nothing to move"; a
  click still calls `onMove` with the same move. Update the Total-cell expectation of "takes the status and the shipped count…".
- 1 Vitest, AC-2: at Total 36 · 14 · 0 · 0, Tab from before the line goes Start cutting → Mark ready → In cutting's Move back (Ship
  and the two empty-stage Move backs skipped).
- 1 Vitest, AC-3: Leather Tote 8 · 4 · 0 · 0, the four boxes' first line reads Start cutting, Mark ready, Ship, empty; the second
  empty, Move back × 3. Extend the phone test "stacks on a phone…" with the same two lines in the Total's boxes.
- Browser, `OrderGoldenPathTest`: the size-table read no longer looks for `span.tabular-nums` (Total cells now hold the figure only).
  New step at 1024 × 768 with the sidebar expanded, at Total 36 · 14 · 0 · 0: the card and the table don't overflow sideways (the
  app clips overflow, so the check is on the card's own width, not the page's); row 1 and row 2 read in the order of AC-1 by
  position; each button's right edge is within 1 px of its column's figures; every button is 32 px tall and narrower than its column.

> [!example]- Files (5)
> - `resources/js/components/orders/order-line-card.tsx`
> - `resources/js/components/orders/size-stages-table.tsx`
> - `resources/js/components/orders/stage-figure.tsx`
> - `resources/js/components/orders/order-line-card.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php`

#### S-71-b · The table frame, grey band, number font and grey zeros
**Commit:** `fix(orders): the size table reads like the design (S-71-b)`

- The table and the button rows sit in a rounded frame on the border token. Header row and Total row on the muted grey at 60 %; the
  Total row gets a 2 px line above it. Every figure, the Total's included, in `font-mono` like the size chips. Every 0, in any row, in
  `text-muted-foreground`, the column headings' colour. Size labels stay bold in the body font. Dark mode follows from the tokens.

**Tests**
- 1 Vitest, AC-4: the frame and the band are there; every figure cell is in the number font; exactly the zero cells (the Total's two
  included) are grey; the Total row is shaded with the heavier line; the size labels are not in the number font.
- Browser, the 1024 px step: the Total's 0 under Shipped has the same computed colour as the "Shipped" heading.

> [!example]- Files (3)
> - `resources/js/components/orders/size-stages-table.tsx`
> - `resources/js/components/orders/order-line-card.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php`

#### S-71-c · A button that can't be clicked looks grey
**Commit:** `fix(orders): a move button that can't be clicked looks grey (S-71-c)`

- A new `MoveButton` in `components/orders` wraps the generated `Button` and adds the disabled look on top of either variant: muted
  fill, muted word, thin border on the border token, full opacity. Enabled buttons keep the filled and outline looks. Every move button
  of the order page (table rows, boxes, phone) uses it. The generated `components/ui/button.tsx` is not touched, and no other button
  in the app changes.

**Tests**
- 1 Vitest dataset on `MoveButton`: filled and outline, each enabled and disabled; a disabled one carries the grey look and not the
  faded one, an enabled one keeps its variant. The existing deleted-product test keeps its hint assertion unchanged.
- Browser, the 1024 px step: the disabled "Ship" has opacity 1 and a different fill from "Start cutting".

> [!example]- Files (5)
> - `resources/js/components/orders/move-button.tsx`
> - `resources/js/components/orders/move-button.test.tsx`
> - `resources/js/components/orders/order-line-card.tsx`
> - `resources/js/components/orders/order-line-card.test.tsx`
> - `tests/Browser/OrderGoldenPathTest.php`

### Decisions
- **Button rows outside the `<table>`, on the table's column grid.** Rejected: the rows inside the table (a `tfoot`, or rows marked
  presentational): a screen reader would still count them as rows, or readers differ, and the card asks that they aren't read as
  sizes. Rejected: one CSS grid shared with the table through `display: contents`, which drops table semantics in some browsers.
- **Subgrid for the boxes** rather than fixed-height placeholders: the lines stay aligned when a long figure ("1,000,000 units") wraps.
  Subgrid is in every browser the build targets.
- **Grey disabled look in an order-page wrapper**, not in the generated button and not as a global CSS rule (that would change every
  disabled button in the app, beyond this card).
- **Zeros in the muted text token** (#5f5f66: 6.3:1 on white, about 5.9:1 on the 60 % shaded Total row; #a1a1aa in dark mode).
- **Keyboard order in the boxes stays box by box** (a box's forward move, then its Move back), as each box is one list item; the
  table's buttons go row by row, as AC-2 says.
- Button height stays 32 px (44 px is S-68). No new server value, no contract change.

### Order
One frontend developer, three commits in order: S-71-a, then S-71-b, then S-71-c (they share `size-stages-table.tsx` and the tests).
The worktree first needs `composer install`, `npm ci` and `npx playwright install chromium`.

### Risks
- The rows drift from the columns (a cell's padding or a border differs between the table and the grid) → one shared column template
  and padding; the browser step measures the right edges at 1024 px.
- Sideways overflow hidden by the app's clipping (`overflow-x-clip` on the content area) → the browser step checks the card's own
  width, not the page's scroll.
- The 1024 px fit is tight (about 670 px for the table with the sidebar open; "Start cutting" needs about 130 px of a 143 px column)
  → a narrow Size column; the browser step fails if it no longer fits.
- jsdom has no layout and renders the table and the boxes at once → alignment and colour are proved in Chromium; Vitest scopes
  queries with `within()` and checks order and structure only.
- The golden path's size-table read depends on the old markup → updated in S-71-a, in the same commit as the markup.
