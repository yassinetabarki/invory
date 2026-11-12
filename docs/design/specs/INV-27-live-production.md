# INV-27 · Live production, a TV board — design spec
Status: proposal · Designer · 2026-11-11 · Boards: [Invory App UI canvas][canvas], title "Live production, a TV board (INV-27)" —
`Inv27Board` (the board, light, 1920×1080), `Inv27BoardDark` (dark), `Inv27States` (connection lost, empty, many batches, seven
steps, no orders, link switched off, couldn't load, loading), `Inv27TvLink` (Progress › Open on TV and the TV link), `Inv27Phone` (375)

[canvas]: https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc

Designed to the human's decisions of 2026-11-11: one screen with two zones (production large on the left, new orders in a narrower
column on the right); the board is opened by a **private link with no sign-in**, so it is a bare page with nothing to press; "newly
arrived orders" are the open orders created in the last 48 hours, newest first, with older not-started orders under **Waiting**;
finished batches leave the board at once and a **Put in stock today** counter runs; orders show the client's full name and city, no
phone or address; the page refreshes itself every 15 seconds (the product-owner's default). Example data is the Progress card's
(today 30 Oct 2026, 14:32; batches B-0146 to B-0152; phases Sewing and Finishing) plus the INV-14 UI card's orders (Amira Ben Salah,
Not started, is the only new order; Yassine Trabelsi and Boutique Lina have deliveries; Mehdi Jaziri is delivered and Nour Gharbi
cancelled, so none of them is on the board).

## Problem and user
Two readers, one question each:
1. **The team**, 3 to 6 m from a TV on the workshop wall, hands busy, never touching it: **"What is being made right now, where is it,
   and what just came in?"**
2. **The owner**, on Progress, who wants to put that on the TV once and forget it: **"How do I get this on the TV, and how do I take
   it off again?"**

The board is read-only by nature and by decision: it decides nothing, moves nothing, and shows nothing that needs a click.

## Flow
1. On **Progress** the owner presses **Open on TV** (outline button, TV icon, between **Scan a ticket** and **Start batch**). A dialog
   explains the link, lets her choose the board's look (Light / Dark / Follow the TV), and warns that anyone with the link sees the
   board. **Create TV link** → toast "TV link created." The dialog now shows the link with **Copy link** and **Open board**.
2. She opens the link in the TV's browser (or **Open board** opens it in a new window she drags to the TV), bookmarks it there, puts
   the browser full screen. The TV is never signed in.
3. The board shows: headline **26 units in production**, the clock, **Put in stock today 38 units**, **Updated just now**; three
   columns **In cutting 7 units · 3 batches**, **Sewing 15 · 3**, **Finishing 4 · 1**, a card per batch; on the right **New orders 1
   in 48 h · 2 units** (Amira Ben Salah) and **Waiting 0 older orders** ("No older order is waiting.": INV-14's other open orders,
   Yassine Trabelsi and Boutique Lina, have units delivered, so they are being served and are not on the board).
4. Sami scans B-0148's ticket at the machine: 15 seconds later its card is in **Finishing**, outlined in Denim with the word **Just
   moved** for two minutes. A client orders on Instagram and the owner records it: the card **Amira Ben Salah · Tunis · 2 units**
   appears at the top of New orders with **Just arrived**. B-0148 is put in stock: its card leaves and the counter reads 42.
5. The Wi-Fi drops: within 15 seconds an amber band reads **Connection lost. This board shows what was happening at 14:32. It tries
   again every 15 seconds.** The figures stay; nothing dims or disappears. The band leaves by itself when the next refresh works.
6. The owner lends the TV to a pop-up shop: Progress › **Open on TV** › **Switch off link** → confirm → toast "TV link switched off."
   The TV reads **This TV link was switched off. Ask the owner for a new one.** and shows no data. **Create new link** makes a fresh,
   different address.

## Layout

### The board (1920×1080, the TV)
Boards: Inv27Board (light), Inv27BoardDark (dark)
A bare page: no sidebar, no breadcrumbs, no Invory header, no button, no link. 40 px margins. Every size below is for 1920 px wide;
the board keeps the same proportions in a smaller window (a 1280 px laptop window reads the same, two thirds the size).

1. **Header** (one row, a 2 px line under it):
   - left: the small line "Live production · Atelier Nour" (the workshop's name, 24 px, grey); the headline **26** (88 px, number
     font) **units in production** (40 px); under it "7 batches · 2 products · oldest since 25 Oct 2026" (26 px, grey);
   - right: **Put in stock today** / **38 units** (52 px number) and, beside it, the date "Thu 30 Oct 2026" with the clock **14:32**
     (52 px) and the status line "● Updated just now" (24 px, grey, a green dot).
   The headline is the hero: from the door you read one number.
2. **Production zone** (left, 1280 px): **one column per step, in path order**: **In cutting** first, then the workshop's own phases
   (Sewing, Finishing, up to six more). Put in stock is not a column; a batch put in stock leaves at the next refresh. Each column:
   - a head: the step's name (36 px bold) and "7 units · 3 batches" (24 px; the figures in the number font), a 3 px line under it;
   - **a card per batch**, oldest first (the batch that waited longest is at the top, as on Progress): line 1 the number "B-0150"
     (24 px, number font, grey) and "5 days" / "1 day" / "today" at the right (the "since" of Progress); line 2 the product's photo
     (64 px; its initials "DJ" on grey when it has none), the product name (30 px bold, at most two lines) and the units **4 units**
     (48 px number); line 3 the colour swatch (22 px) and "Indigo · M" (26 px; "Indigo" alone for a "One size" product; "Indigo ·
     size 38" for a digit size, as Progress words it). A deleted product reads "Denim Jacket (deleted)".
   - With 3 steps a column is 410 px and holds 5 cards; the headline, heads and cards stay the same size up to 4 steps. With 5 to 7
     steps the columns narrow (162 px at 7), the step name wraps on two lines and the cards drop the photo; the text sizes don't
     change (board Inv27States, 5).
3. **Orders zone** (right, 520 px):
   - head **New orders** and "1 in 48 h · 2 units";
   - **a card per open order recorded in Invory in the last 48 hours, newest first**: the client's full name (30 px bold) and city
     (22 px grey) with the units **2 units** at the right (30 px number); one line per product, "Denim Jacket · Indigo S 1 · M 1"
     (24 px; the sizes and counts in the number font; a "One size" product reads "Leather Tote · Camel 2"); a grey line "Ordered 28 Oct
     · recorded today 14:31 · waiting 2 days" (the order date is what the owner typed, often a day or two after the Instagram message;
     "recorded" is when it entered Invory and what makes it new; "waiting" counts whole days from the order date, which has no time of
     day: "waiting 1 day", "waiting 2 days"; an order dated today reads "Ordered today · recorded today 14:31" with no waiting part);
     and the shelf line (question 4) **On the shelf: all 3 units** (green) · **On the shelf: 1 of 2 units** (amber) · **On the
     shelf: none** (red), the colour never alone, each compared with the shelf alone (not with other orders). Up to 4 products on a
     card, then "and 2 more products"; up to 3 cards, then "and 2 more new orders";
   - head **Waiting** and "0 older orders": the open orders recorded more than 48 hours ago on which nothing is delivered yet (status
     "Not started"), oldest first, one line each: "<client> · <city> · 120 units · 6 days". Up to 3 lines, then "and 5 more
     waiting"; none: "No older order is waiting.". An order with something delivered (Yassine Trabelsi, Boutique Lina: In progress) is
     not on the board: it is being served. INV-14's example gives exactly one new order (Amira Ben Salah, ordered 28 Oct, recorded today)
     and nothing waiting.
4. **Noticing a change without sound.** A batch that reached its column in the last 2 minutes, and an order recorded in the last
   2 minutes, get the info tint, a Denim outline and a word in a pill: **Just moved** (a batch that moved forward or back), **Just
   started** (a new batch In cutting), **Just arrived** (an order). The pill goes at the refresh after the two minutes. No animation:
   the card simply is there at the next refresh (a 300 ms fade-in is acceptable, dropped under reduced motion). Nothing blinks.
5. **Many batches** (board Inv27States, 4): when any column holds more than 5 batches, **every** column switches to one-line cards
   (56 px: number · product · swatch and colour · size · units · since), up to 12 a column, then "and 4 more batches" (the server's
   count). The whole board shares one density so the columns stay comparable. Still oldest first: the batches that fall off are the
   newest, which the team saw started.
6. **Status line and refresh.** The page asks the server for the whole board every 15 seconds and redraws only what changed. "Updated
   just now" is the normal state (the exact second is of no use from 5 m); the clock ticks every minute and is the sign the page is
   alive. When a refresh fails or gets no answer within 15 seconds, the band of state **Connection lost** appears at once under the
   header and the status line turns amber: "Last update 14:32".
7. **Look.** The board takes the look chosen on the link (Light, Dark or Follow the TV); both are drawn with the app's tokens. Dark
   is recommended as the default for a screen that stays on all day (less glare, text reads better from a distance), but the choice
   starts as the owner's own setting so that nothing surprises her (question 6).

### Progress › Open on TV and the TV link (desktop)
Board: Inv27TvLink
1. **Progress header:** the new outline button **Open on TV** (TV icon) between **Scan a ticket** and **Start batch**, named "Open on
   TV: the live production board in a new window". On a phone it is full width under the two side-by-side buttons.
2. **Dialog, no link yet:** title **Open on TV**; "Make a private link for a TV in your workshop. The TV opens it once, needs no
   sign-in, and the board updates by itself every 15 seconds. It shows your batches by phase and the orders that just arrived."; the
   group **Look of the board on the TV** with three choices, **Light** ("Like Invory in light mode."), **Dark** ("Easier on the eyes
   on a screen that stays on all day."), **Follow the TV** ("Light or dark as the TV's browser is set."), hint "Starts as your own
   setting. A change applies to the TV at its next update, with the same link."; the amber note "Anyone with the link sees the board
   without signing in, including your clients' names and cities. Keep it inside the workshop. Nothing on the board can be changed.";
   **Cancel** · **Create TV link** (main).
3. **Dialog, link active:** the field **Your TV link** (read-only, the address, number font) with **Copy link**; under it "Created 11
   Nov 2026 by Admin. Paste it in the TV's browser and bookmark it there."; the look group (a change saves on its own: toast "Look of
   the board updated."); the amber note; at the bottom **Switch off link** (outline, red text) on the left, **Close** and **Open
   board** (main; opens the link in a new window) on the right.
4. **Confirm:** **Switch off the TV link?** · "The TV shows "This TV link was switched off" until you create a new one. The old link
   stops working for good; it can't be switched on again." · **Keep link** · **Switch off link** (red).
5. **Dialog, switched off:** the grey note "The TV link was switched off on 11 Nov 2026 by Admin. The TV shows a notice until you
   create a new one.", the look group, the amber note, **Cancel** · **Create new link** (main). A new link is a new, different
   address; the old one stays dead.
6. One link per workshop (question 7). Any member of the workshop sees the dialog and the link (question 8).

### Phone (375 px), secondary
Board: Inv27Phone
- **The board:** one column, 16 px gutter: the workshop line, the headline **26** units in production, "7 batches · 2 products · Put in
  stock today 38 units", the status line with the date and time; then each step as a section (name, "7 units · 3 batches", a card
  per batch with number and since, product and units, swatch and colour · size); then **New orders** (the cards, shorter) and
  **Waiting** (the lines). Same density rule, same pills, same bands (full width). Nothing to press, nothing scrolls sideways.
- **Progress:** **Open on TV** full width under **Scan a ticket** and **Phases**; the dialog opens as a sheet from the bottom, its
  buttons one under another, 44 px, **Open board** first.

## States

| Screen · state | What is on screen (exact words) |
|---|---|
| Board · default | As in Layout: "Live production · Atelier Nour" · "26 units in production" · "7 batches · 2 products · oldest since 25 Oct 2026" · "Put in stock today" "38 units" · "Thu 30 Oct 2026" "14:32" · "Updated just now" · columns "In cutting" "7 units · 3 batches", "Sewing" "15 units · 3 batches", "Finishing" "4 units · 1 batch" · cards B-0150 Denim Jacket Indigo · M 4 units 5 days, B-0151 Light wash · L 2 units 1 day, B-0152 Light wash · XL 1 unit today **Just started**; B-0146 Denim Jeans 5-pocket Indigo · S 5 units 4 days, B-0147 Denim Jacket Indigo · S 4 units 3 days, B-0149 Indigo · M 6 units 2 days; B-0148 Indigo · M 4 units today **Just moved** · "New orders" "1 in 48 h · 2 units" · "Amira Ben Salah" "Tunis" "2 units" "Denim Jacket · Indigo S 1 · M 1" "Ordered 28 Oct · recorded today 14:31 · waiting 2 days" **Just arrived** "On the shelf: 1 of 2 units" · "Waiting" "0 older orders" "No older order is waiting." |
| Board · loading (first paint) | The header words at once; grey placeholders for the headline, the counter, the column heads and the first card of each column; no spinner, no words. |
| Board · nothing in production | Headline "0 units in production" · "No batch in production" · the left zone: "Nothing in production" · "Batches started on Progress show here, phase by phase, as soon as they are cut." (an icon, no button) · the orders zone as usual; the counter and the clock stay. |
| Board · no new orders | Right column: "New orders" "0 in 48 h · 0 units" · "No new orders" · "Orders of the last 48 hours show here when they arrive." · Waiting as usual; with nothing waiting: "Waiting" "0 older orders" · "No older order is waiting." |
| Board · an empty step | The column stays, head "Pressing" "0 units · 0 batches", the line "Nothing in Pressing". |
| Board · many batches | One-line cards in every column, "and 4 more batches"; "and 2 more new orders"; "and 5 more waiting". |
| Board · connection lost | Status line amber "Last update 14:32"; the band (warning tone, icon) "Connection lost. This board shows what was happening at 14:32. It tries again every 15 seconds."; after 2 minutes: "Connection lost for 4 minutes. Last update 14:32. Check the Wi-Fi of this screen, or reload the page." Figures and cards stay as they were; the "since" words don't age; pills stay. The band leaves at the first refresh that works. |
| Board · couldn't load (first open) | No data; centred: "Couldn't load the board." · "Trying again every 15 seconds. Check the Wi-Fi of this screen if this stays." Nothing to press. |
| Board · link switched off, wrong or unknown | No data; centred, in the link's look: "This TV link was switched off." · "Ask the owner for a new one." An address that never existed reads the same (the board never says whether a workshop exists). |
| Board · long content | A 60-character product name wraps on two lines then cuts with "…"; a 100-character client name cuts with "…" on one line; 7-digit counts fit the headline (the headline text shrinks a step at 6 digits); 12 colours × 11 sizes only ever appear as batches; a one-line card cuts the product name with "…" and never the units. |
| Open on TV · default, no link | As in Layout 2. Focus starts on **Light** / the chosen look. |
| Open on TV · link active | As in Layout 3. **Copy link** → toast "Link copied."; the field's text is selectable for a manual copy. Focus starts on **Copy link**. |
| Open on TV · saving | **Create TV link** / **Create new link** / **Switch off link** keep their label and show a spinner. |
| Open on TV · errors | "Couldn't create the TV link. Try again." · "Couldn't switch off the TV link. Try again." · "Couldn't copy the link. Select it and copy it yourself." · "Your browser blocked the board's window. Allow pop-ups for Invory, or copy the link and open it yourself." · a look change that fails: "Couldn't save the look. Try again." (the chosen option goes back). |
| Open on TV · confirm | "Switch off the TV link?" · body above · **Keep link** · **Switch off link**. |
| Open on TV · switched off | As in Layout 5. |
| Open on TV · success | Toasts "TV link created." · "TV link switched off." · "Look of the board updated." The dialog stays open after Create (the owner copies the link next). |
| Open on TV · changed meanwhile | Another member switched the link off or made a new one while the dialog was open: "The TV link changed since you opened this window." and the dialog reloads its content. |

## Accessibility
- **Reading distance.** Rule of thumb: text height ≈ viewing distance ÷ 200 (6 m → 30 mm; on a 55-inch 1920 px screen 1 px ≈ 0.63 mm, so
  30 mm ≈ 48 px). Sizes on the board: headline 88, units 48, step names 36, product and client names 30, colour · size 26, every
  other word 24, pills 20 (the smallest, read from 3 m beside a 30 px word that says the same). Nothing under 20 px. The owner's
  laptop window keeps the proportions (24 px at 1920 ≈ 16 px at 1280).
- **Contrast (1.4.3, 1.4.11).** Light: `#18181b` on `#fafaf9` 16.4:1; grey `#5f5f66` on white 6.3:1, on `#fafaf9` 6.0:1; Denim
  `#2b3a86` on the info tint `#eef0fa` 8.3:1; amber `#8a4405` on `#fdf0dc` 6.4:1; green `#0b5c4b` on `#e6f4ef` 7.6:1 and on white
  8.9:1; red `#a3221a` on white 6.9:1; card border `#e4e4e0` on white 1.3:1 (decorative; the cards are also separated by spacing);
  the Denim outline of a pill 3.4:1 against the tint. Dark: `#f4f4f5` on `#131316` 16.9:1; grey `#a1a1aa` on `#1a1a1e` 7.6:1;
  `#b7c1f7` on `#222a55` 7.1:1; amber `#f3b866` on `#3a2609` 8.1:1; green `#7ed9bf` on `#12332b` 8.5:1; red `#f7a8a2` on
  `#411917` 7.4:1. Nothing is dimmed in the connection-lost state so no text falls under 4.5:1.
- **Colour never alone (1.4.1).** The highlight has a word (**Just moved**, **Just started**, **Just arrived**); the shelf line is a
  sentence; the connection band is a sentence with an icon; the swatch is beside the colour's name; the status dot is beside its word.
- **Structure (1.3.1, 2.4.6).** One `h1` ("Live production"), one `h2` per step and for "New orders" and "Waiting"; each column is a
  list of batches, each card a list item whose text reads "B-0150, Denim Jacket, Indigo M, 4 units, in In cutting since 5 days";
  the band is an alert (`role="alert"`, announced once); the status line and the headline are polite live regions that announce only
  when their words change (not every 15 seconds); the pills are plain text in the card.
- **Timing (2.2.1, 2.2.2).** The refresh is the board's purpose (the "essential" exception of 2.2.2); there is no control on the TV by
  decision. The board never times out, never redirects and never flashes (2.3.1).
- **Keyboard (2.1.1, 2.4.7).** The board has nothing to focus; Tab does nothing, which is correct for a page with no control. On
  Progress, **Open on TV** is a button with a name; the dialog traps focus, Esc closes it, focus returns to the button; the link field
  is read-only and focusable (its text can be selected and copied); **Copy link** is a button; the look is a radio group named "Look of
  the board on the TV".
- **Targets (2.5.8).** Every dialog control is 40 px on a laptop and 44 px on a phone, with 8 px between neighbours.
- **Motion (2.3.3).** The only motion is an optional 300 ms fade-in of a new card, dropped with reduced motion; the clock changes text.
- **Reflow (1.4.10).** The phone layout is one column; at 320 px the one-line cards become two lines; nothing scrolls sideways.
- **Privacy, as an accessibility of the workshop floor.** The TV shows client names and cities, which the human decided; the dialog
  says so in words before the link exists. No phone, no address, no price, no note.

## Options and recommendation
1. **Production zone layout.** (a) **One column per step, cards oldest first — recommended**: mirrors the Progress tiles left to right,
   gives the long phase (Sewing) a whole column, holds 5 big cards or 12 one-line cards per column, scales to 7 steps by narrowing ·
   (b) one list grouped by phase, a band per step and cards wrapping under it: scales to any number of phases but a busy Sewing
   pushes Finishing off the screen, and a list reads less like a flow · (c) columns up to 4 steps and (b) from 5: two layouts to
   build for a rare case.
2. **Many batches.** (a) **One-line cards past 5 per column, then "and N more" — recommended**: everything that fits is still a
   fact, and "and 4 more" is honest · (b) auto-paging every 10 s ("1 / 2"): text that changes under your eyes from 5 m is confusing
   and a batch is off screen half the time · (c) scroll: nobody is at the keyboard.
3. **Noticing a change.** (a) **Tint, outline and a word for 2 minutes — recommended** · (b) a 2 s flash: fails 2.3.3 and is missed
   by anyone not looking · (c) a "Changed since 14:17" list under the header: a second thing to read.
4. **Shelf coverage on an order card.** (a) **One line, three wordings ("all 3 units" / "1 of 2 units" / "none") — recommended**: it
   is the question the owner asks of a new order, and one line stays simple · (b) nothing about the shelf: simpler, but the team
   can't tell "ready to deliver" from "we must make it" · (c) per product line: too much text at 24 px.
5. **Connection lost.** (a) **Band at once, figures stay, status line amber — recommended** · (b) dim the whole board: fails contrast
   on grey text · (c) blank the board: loses the last known state, which is still useful for a few minutes.
6. **The look of a link with no user.** (a) **A choice on the link (Light / Dark / Follow the TV), preset to the owner's own setting —
   recommended** (the human asked for the owner's setting; a TV benefits from dark, and the choice makes that one click) · (b) always
   the owner's setting at creation, no choice: fewer words, but a dark-mode owner can't give a bright room a light board · (c) dark
   always: ignores the "no forced dark" decision.
7. **Where the link lives.** (a) **In the Open on TV dialog on Progress — recommended**: one place, where the owner is when she
   thinks of the TV · (b) a page Settings › Production › TV link: discoverable from Settings, but a second place for one link ·
   (c) both: the dialog plus a line in Settings that opens it. Default (a); (c) later if asked.

## For the product-owner
- **Glossary to keep:** Live production (the board), TV link (the private address), Open on TV (the button and the dialog), New
  orders, Waiting, Put in stock today, Just moved / Just started / Just arrived, Updated just now, Connection lost.
- **Acceptance wording, proposed:**
  - AC-1 · Open on TV. Given Progress, when you press **Open on TV** and then **Create TV link**, then the dialog shows "Your TV link"
    with an address, **Copy link** and **Open board**, and the toast "TV link created." Opening that address in a browser that is not
    signed in shows the live board of this workshop and nothing else of Invory.
  - AC-2 · The board. Given the Progress card's seven batches, when the board opens, then it reads "26 units in production", the
    columns **In cutting** "7 units · 3 batches", **Sewing** "15 units · 3 batches", **Finishing** "4 units · 1 batch", a card per
    batch with its number, product, colour · size, units and since ("B-0150 · Denim Jacket · Indigo · M · 4 units · 5 days"), oldest
    first; no sidebar, no button, no link.
  - AC-3 · New orders and Waiting. Given Amira Ben Salah's order of 28 Oct, recorded today 14:31 (Denim Jacket Indigo S 1, M 1; shelf
    Indigo S 2, M 0), then the board shows under **New orders** "1 in 48 h · 2 units" and "Amira Ben Salah · Tunis · 2 units · Denim
    Jacket · Indigo S 1 · M 1 · Ordered 28 Oct · recorded today 14:31 · waiting 2 days · On the shelf: 1 of 2 units"; an open order
    recorded more than 48 h ago with nothing delivered shows under **Waiting** as "<client> · <city> · <units> units · <n> days"; with
    none (INV-14's example), "No older order is waiting."; an order with units delivered (Yassine Trabelsi, Boutique Lina) shows nowhere.
  - AC-4 · Updates by itself. Given the board open, when B-0148 is moved to Finishing on Progress, then within 15 seconds its card is
    in the Finishing column with the word **Just moved**, and when it is put in stock its card leaves and **Put in stock today** rises
    by 4. The status line reads "Updated just now".
  - AC-5 · Connection lost. When the server can't be reached, then within 15 seconds the board shows the band "Connection lost. This
    board shows what was happening at 14:32. It tries again every 15 seconds." with its figures unchanged, and the band leaves at the
    first refresh that works.
  - AC-6 · Switch off. When you press **Switch off link** and confirm, then the toast "TV link switched off." shows, the old address
    reads "This TV link was switched off. Ask the owner for a new one." with no data, and **Create new link** gives a different address.
  - AC-7 · Look. The board uses the look chosen in the dialog (Light, Dark, Follow the TV) and starts as the owner's own setting.
  - AC-8 · Nothing in production. With no batch, the board reads "0 units in production", "Nothing in production", and still shows New
    orders and Waiting.
- **The TV link could be its own card** (INV-27a): the dialog, the create/switch-off, the bare page that answers the address. The board
  itself (INV-27b) then builds on it. The split keeps each card at S–M.
- **Left out on purpose** (say so in the card): per-unit cards (INV-17), sound, charts, history, late rules, several links or several
  TVs per workshop, editing from the board, a pause control on the TV, phone numbers or addresses of clients.

## For the tech-lead
- **Values the server sends to the board** (one answer per refresh): the workshop's name; the look of the link; the totals (units in
  production, batches, products, oldest since date, put in stock today); the steps in path order, each with name, units, batches, and
  its batches oldest first (number, product name and deleted flag, photo address, colour name and swatch, size label and the "One
  size" flag, units, since date, since days, **minutes since its last move** for the pill); the number of batches not sent per column
  when over 12; the new orders newest first (client name and city, units, lines as "product · colour · size count" words, arrival
  date (ordered_on) and the recording date-time, **waiting days** as a whole number counted from the order date (0 = no waiting part;
  "1 day", "2 days"), minutes since recording, the shelf line's figures: units covered by the shelf alone and units ordered); the waiting orders oldest first (client,
  city, units, waiting days); the counts not sent. The board formats and never adds
  up; "waiting 2 days" and "5 days" are whole-day numbers from the server, as Progress's `since_days`.
- **The TV link:** one secret address per workshop, long and unguessable, with the look saved on it; created, replaced and switched
  off from Progress (any member; the dialog shows who and when); a switched-off or unknown address answers the same notice without
  naming a workshop; the page it serves is outside the signed-in app (no session, no CSRF, no user; read-only); rate-limit the
  refresh; nothing on it writes.
- **Costly or risky:** the 15 s refresh is a polling read of the whole board per TV (one TV per workshop in practice; keep the answer
  small and cached a few seconds); "Put in stock today" needs the workshop's day boundary (its timezone); the "minutes since last
  move" needs the move timestamp a batch already keeps for "since"; client names on a no-login page is the human's decision, the
  dialog warns, and the link can be killed; the Progress page gets one more button and a dialog (new shadcn: none; a `RadioGroup` is
  already in use).
- **New shadcn components:** none expected (dialog, radio group, button, toast exist).

## Questions for the product-owner / human
1. **Product photo on a batch card?** (a) Yes, 64 px, initials when there is none **recommended** (a picture reads from 6 m before
   any word) · (b) no photo, text only. Default (a).
2. **The clock and date on the board?** (a) Yes, top right, next to "Updated just now" **recommended** (a TV board is also the
   workshop's clock, and a stopped clock shows a frozen page) · (b) only "Updated just now". Default (a).
3. **How long does the highlight stay?** (a) 2 minutes **recommended** · (b) 5 minutes · (c) until the next change on the board.
   Default (a).
4. **Shelf line on a new order?** (a) Yes, one line: "On the shelf: all 3 units" / "1 of 2 units" / "none" **recommended** · (b) no
   shelf line. Default (a).
5. **Waiting: which orders?** (a) Open orders older than 48 h with nothing delivered yet (status Not started), oldest first
   **recommended** (an order with a delivery is being served) · (b) every open order older than 48 h, In progress included, with
   its units still to deliver. Default (a).
6. **The look of the TV link.** (a) A choice in the dialog (Light / Dark / Follow the TV), preset to the owner's own setting
   **recommended** · (b) always the owner's setting at creation, no choice · (c) dark always. Default (a).
7. **One link per workshop, or several?** (a) One; a new link replaces the old **recommended** (one TV, one switch to kill) · (b)
   several named links ("Cutting room TV", "Sewing TV"), each switched off on its own. Default (a).
8. **Who may create or switch off the link?** (a) Any member of the workshop **recommended** (there are no roles yet, ADR-0013; the
   dialog shows who did it) · (b) wait for roles. Default (a).
9. **Where the link lives.** (a) The Open on TV dialog on Progress **recommended** · (b) Settings › Production › TV link · (c) both.
   Default (a).
10. **Many batches.** (a) One-line cards past 5 per column, then "and N more" **recommended** · (b) auto-paging every 10 s. Default (a).
11. **Split the card?** (a) INV-27a the TV link and the bare page, INV-27b the board **recommended** · (b) one card. Default (a); the
    product-owner decides.
