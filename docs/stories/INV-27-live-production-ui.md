---
id: INV-27
title: Live production, a TV board
type: ui
priority: Should
status: Approved
created: 2026-11-11
tags:
  - ui-card
---

# INV-27 · Live production, a TV board — UI card

> [!abstract] Description
> The owner makes a **private TV link** on Progress and opens it on a TV in the workshop. The TV shows the **Live production** board:
> what is being made now, step by step, and the orders that just arrived. Nobody touches it and nobody signs in; it refreshes itself
> every 15 seconds. New: the button **Open on TV** and its dialog on Progress, and the board page that the link opens.
> Left out on purpose: anything to press or type on the TV, moving or starting a batch from the board, units one by one (INV-17),
> sound, charts, history, late-order rules, several links or several TVs per workshop, clients' phone numbers, addresses, prices and
> notes.

> [!info] How it looks today
> Nothing yet. To know what is in production the team walks to the screen where Progress is open, and someone has to refresh it. New
> orders are seen only on the Orders page. Progress has the buttons **Scan a ticket** and **Start batch**.

## Pages

Examples: Thursday 30 Oct 2026, 14:32, workshop "Atelier Nour", the batches of the Progress card (B-0146 to B-0152; steps In cutting,
Sewing, Finishing). Boards: `Inv27Board` (light, 1920×1080), `Inv27BoardDark`, `Inv27States`, `Inv27TvLink`, `Inv27Phone` (Invory App UI
canvas). Design spec: INV-27-live-production.

### Open on TV (Progress)
Board: Inv27TvLink
- A new outline button with a TV icon, **Open on TV**, between **Scan a ticket** and **Start batch**. Its name for screen readers: "Open
  on TV: the live production board in a new window". Any member of the workshop sees it.
- **No link yet.** Dialog **Open on TV**: "Make a private link for a TV in your workshop. The TV opens it once, needs no sign-in, and the
  board updates by itself every 15 seconds. It shows your batches by phase and the orders that just arrived." Then the group **Look of
  the board on the TV** with three choices: **Light** ("Like Invory in light mode."), **Dark** ("Easier on the eyes on a screen that
  stays on all day."), **Follow the TV** ("Light or dark as the TV's browser is set."), and the hint "Starts as your own setting. A
  change applies to the TV at its next update, with the same link." The choice starts on the owner's own setting. An amber note: "Anyone
  with the link sees the board without signing in, including your clients' names and cities. Keep it inside the workshop. Nothing on
  the board can be changed." Buttons **Cancel** and **Create TV link**.
- **Link active.** The field **Your TV link** (read-only address, number font) with **Copy link**; under it "Created 11 Nov 2026 by
  Admin. Paste it in the TV's browser and bookmark it there."; the look group (a change saves on its own); the amber note; at the
  bottom **Switch off link** (outline, red text) on the left, **Close** and **Open board** on the right. **Open board** opens the board
  in a new window.
- **Confirm.** "Switch off the TV link?" · "The TV shows "This TV link was switched off" until you create a new one. The old link stops
  working for good; it can't be switched on again." · **Keep link** · **Switch off link** (red).
- **Switched off.** A grey note "The TV link was switched off on 11 Nov 2026 by Admin. The TV shows a notice until you create a new
  one.", the look group, the amber note, **Cancel** and **Create new link**. A new link is a different address; the old one stays dead.
- There is one link per workshop; a new link replaces the old one. A link lives until the owner switches it off; it never expires by
  itself.

### The board (the TV page)
Boards: Inv27Board, Inv27BoardDark
A bare page: no sidebar, no menu, no button, no link, nothing to focus. Sizes below are for a 1920 px wide screen; a smaller window keeps
the proportions. Nothing is smaller than 20 px.
1. **Header.** Small line "Live production · Atelier Nour"; the headline **26** with "units in production"; under it "7 batches · 2
   products · oldest since 25 Oct 2026". On the right: **Put in stock today** with **38 units**; the date "Thu 30 Oct 2026" and the clock
   **14:32**; the status line "● Updated just now".
2. **Production zone (left).** One column per step in path order: **In cutting**, then the workshop's own phases (Sewing, Finishing …).
   Put in stock is not a column. Each column has a head (the step's name and "7 units · 3 batches") and a card per batch, **oldest
   first**. A batch card shows: its number ("B-0150") and since ("5 days", "1 day", "today"); the product's photo (64 px, its initials
   "DJ" on grey when it has none), the product's name and the units ("4 units", the largest number on the card); the colour swatch and
   "Indigo · M" ("Indigo" alone for a One size product, "Indigo · size 38" for a digit size). A deleted product reads "Denim Jacket
   (deleted)". Example: In cutting 7 units · 3 batches (B-0150 Denim Jacket Indigo · M 4 units 5 days, B-0151 Light wash · L 2 units 1
   day, B-0152 Light wash · XL 1 unit today); Sewing 15 units · 3 batches; Finishing 4 units · 1 batch.
3. **Orders zone (right).**
   - **New orders** with "1 in 48 h · 2 units": a card per open order **recorded in Invory** in the last 48 hours, **newest first**.
     "New" means recorded in the last 48 hours, not dated in the last 48 hours: the order date is what the owner typed, often a day or
     two after the Instagram message. A card shows the client's full name and city, the units still to deliver ("2 units"), one line
     per product ("Denim Jacket · Indigo S 1 · M 1"; "Leather Tote · Camel 2" for a One size product), a grey line "Ordered 28 Oct ·
     recorded today 14:31 · waiting 2 days", and a shelf line: **On the shelf: all 3 units**, **On the shelf: 1 of 2 units** or **On the
     shelf: none**. "Waiting" counts whole days from the order date ("waiting 1 day", "waiting 2 days"); an order dated today reads
     "Ordered today · recorded today 14:31" with no waiting. Up to 3 cards, then "and 2 more new orders" (on a busy day); up to 4
     products on a card, then "and 2 more products".
   - **Waiting** with "0 older orders": the open orders recorded more than 48 hours ago on which nothing is delivered yet (status Not
     started), **oldest first by order date**, one line each: "<client> · <city> · 120 units · 6 days" (whole days from the order date).
     Up to 3 lines, then "and 5 more waiting" (on a busy day). With none, "No older order is waiting." An order with something
     already delivered is not on the board: it is being served.
   - Example: with the orders of the order-tracker card, only Amira Ben Salah is on the board (ordered 28 Oct, recorded today). Yassine
     Trabelsi and Boutique Lina have deliveries, Mehdi Jaziri is delivered and Nour Gharbi is cancelled.
   - Units on an order are the units still **To deliver**; a cancelled or delivered unit is not counted.
   - The shelf line compares each colour and size of the order with the finished stock now, for that order alone. The shelf is not set
     aside, so two orders can both read "all 3 units" from the same 3 units on the shelf.
4. **Noticing a change, without sound.** A batch that reached its column in the last 2 minutes, and an order recorded in the last 2
   minutes, get a tint, a Denim outline and a word: **Just moved** (forward or back), **Just started** (a new batch In cutting), **Just
   arrived** (an order). The word goes at the first refresh after the 2 minutes. Nothing blinks or flashes.
5. **Many batches.** When any column holds more than 5 batches, every column switches to one-line cards (number · product · swatch and
   colour · size · units · since), up to 12 in a column, then "and 4 more batches". Oldest first, so the ones left out are the newest.
   More than 4 steps: the columns narrow and the cards drop the photo; the text sizes don't change.
6. **Look.** Light, Dark or Follow the TV, as chosen on the link; the colours are the app's own.
7. **Put in stock today.** The units put in stock from Progress since the start of the workshop's day. Undoing the restock of a Put in
   stock takes its units off the count.

## States

- **Board · loading (first paint).** The header words at once; grey placeholders for the headline, the counter, the column heads and the
  first card of each column. No spinner.
- **Board · nothing in production.** "0 units in production" · "No batch in production" · "Nothing in production" · "Batches started on
  Progress show here, phase by phase, as soon as they are cut." The orders zone, the counter and the clock stay.
- **Board · no new orders.** "New orders" "0 in 48 h · 0 units" · "No new orders" · "Orders of the last 48 hours show here when they
  arrive."
- **Board · nothing waiting.** "Waiting" "0 older orders" · "No older order is waiting."
- **Board · an empty step.** The column stays: "Pressing" "0 units · 0 batches" · "Nothing in Pressing".
- **Board · connection lost.** The status line turns amber, "Last update 14:32", and a band appears: "Connection lost. This board shows
  what was happening at 14:32. It tries again every 15 seconds." The figures and cards stay as they were; nothing dims. After 2
  minutes: "Connection lost for 4 minutes. Last update 14:32. Check the Wi-Fi of this screen, or reload the page." The band leaves at the
  first refresh that works.
- **Board · couldn't load (first open).** Centred: "Couldn't load the board." · "Trying again every 15 seconds. Check the Wi-Fi of this
  screen if this stays."
- **Board · link switched off or unknown.** Centred, no data: "This TV link was switched off." · "Ask the owner for a new one." An
  address that never existed reads the same.
- **Board · long content.** A long product name wraps on two lines then ends with "…"; a long client name ends with "…" on one line; big
  counts fit; a one-line card shortens the name and never the units.
- **Open on TV · saving.** **Create TV link**, **Create new link** and **Switch off link** keep their label and show a spinner.
- **Open on TV · errors.** "Couldn't create the TV link. Try again." · "Couldn't switch off the TV link. Try again." · "Couldn't copy the
  link. Select it and copy it yourself." · "Your browser blocked the board's window. Allow pop-ups for Invory, or copy the link and
  open it yourself." · "Couldn't save the look. Try again." (the chosen option goes back).
- **Open on TV · changed meanwhile.** Another member switched the link off or made a new one while the dialog was open: "The TV link
  changed since you opened this window." and the dialog reloads.

## Wording
- Buttons: **Open on TV** · **Create TV link** · **Create new link** · **Copy link** · **Open board** · **Switch off link** · **Keep link**
  · **Cancel** · **Close**.
- Dialog: **Open on TV** · **Your TV link** · **Look of the board on the TV** · **Light** · **Dark** · **Follow the TV**.
- Toasts: "TV link created." · "TV link switched off." · "Look of the board updated." · "Link copied."
- Board: "Live production · <workshop>" · "units in production" · "<n> batches · <n> products · oldest since <date>" · "Put in stock
  today" · "Updated just now" · "Last update 14:32" · "New orders" · "Waiting" · "Just moved" · "Just started" · "Just arrived" ·
  "On the shelf: all 3 units" · "On the shelf: 1 of 2 units" · "On the shelf: none".
- Everything else as in Pages and States.

## Phone layout
- **Progress:** **Open on TV** full width under the side-by-side **Scan a ticket** and **Phases**. The dialog opens as a sheet from the
  bottom, its buttons one under another, 44 px high, **Open board** first.
- **The board** (secondary, the TV is its target): one column with a 16 px gutter: the workshop line, the headline, "7 batches · 2
  products · Put in stock today 38 units", the status line with the date and time; then each step as a section (name, "7 units · 3
  batches", its cards); then **New orders** and **Waiting**. Same pills and bands, full width. Nothing scrolls sideways; at 320 px a
  one-line card takes two lines.
