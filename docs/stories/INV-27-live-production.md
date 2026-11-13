---
id: INV-27
title: Live production, a TV board
type: feature
phase: Colour × size variants
priority: Should
size: M
status: Draft
created: 2026-11-12
tags:
  - story
  - variants
---

# INV-27 · Live production, a TV board

> [!abstract] Description
> As a **workshop owner**, I want **a board for a TV in the workshop that shows what is being made and which orders just came in, and
> updates by itself**, so that **my team sees where every batch is without walking to the computer, and nobody has to refresh or sign
> in**. On Progress she presses **Open on TV**, makes a private link, and opens it in the TV's browser. The TV is never signed in.
> New: the button **Open on TV** and its dialog on Progress (create, copy, choose the look, open, switch off, create a new link) and
> the board page that the link opens. The pages are drawn in [[INV-27-live-production-ui]], which builds on [[INV-5-progress-ui]] and
> the order tracker of [[INV-14-order-tracker-ui]].
> Left out on purpose: anything to press or type on the TV, moving or starting a batch from the board, units one by one (INV-17),
> sound, charts, history, late-order rules, several links or TVs per workshop, and clients' phone numbers, addresses, prices and notes.
> The board changes nothing: no stock, no batch, no order.

> [!info] How it works today
> Nothing yet. To know what is in production, the team walks to the screen where Progress is open, and someone has to refresh it:
> "All phases 26 · In cutting 7 · Sewing 15 · Finishing 4". New orders are seen only on the Orders page, where Amira Ben Salah's order
> of 2 jackets sits among the others. Progress has the buttons **Scan a ticket** and **Start batch**, and nothing for a TV.

## Acceptance criteria
- [ ] **AC-1 · Open on TV.** Given Progress, when a member presses **Open on TV** and then **Create TV link**, then the toast "TV link
  created." shows and the dialog reads "Your TV link" with the address, **Copy link** and **Open board**. **Copy link** shows "Link
  copied."; **Open board** opens the board in a new window; the line "Created 11 Nov 2026 by Admin. Paste it in the TV's browser and
  bookmark it there." names who made it and when. Opening the address in a browser that is not signed in shows the live board of this
  workshop and nothing else of Invory (no menu, no button, no other page). The link lives until it is switched off and never expires
  by itself; a workshop has one link, and a new link replaces the old one.
- [ ] **AC-2 · The board.** Given the batches B-0146 to B-0152 of the Progress card, when the board opens, then it reads "Live
  production · Atelier Nour", "26 units in production", "7 batches · 2 products · oldest since 25 Oct 2026", and the columns **In
  cutting** "7 units · 3 batches", **Sewing** "15 units · 3 batches" and **Finishing** "4 units · 1 batch". Each column lists its
  batches oldest first, a card with the batch number, the product (photo, or its initials when it has none), the units, the colour and
  size, and since when: "B-0150 · Denim Jacket · Indigo · M · 4 units · 5 days". A One size product reads "Indigo" alone, a digit
  size reads "Indigo · size 38", a deleted product reads "Denim Jacket (deleted)". The date, the clock and "Updated just now" show.
  Nothing on the board can be pressed.
- [ ] **AC-3 · New orders and Waiting.** Given Amira Ben Salah's order of 28 Oct, recorded today at 14:31 (Denim Jacket Indigo S 1 · M
  1; the shelf holds Indigo S 2, M 0), then **New orders** reads "1 in 48 h · 2 units" and a card shows "Amira Ben Salah · Tunis · 2
  units · Denim Jacket · Indigo S 1 · M 1 · Ordered 28 Oct · recorded today 14:31 · waiting 2 days · On the shelf: 1 of 2 units"
  (otherwise "On the shelf: all 3 units" or "On the shelf: none"; an order dated today reads "Ordered today · recorded today 14:31" with
  no waiting). "New" means recorded in the last 48 hours, newest first; units are those still to deliver; each order is compared with
  the shelf on its own. An open order recorded more than 48 hours ago with nothing delivered shows under **Waiting** ("Concept Store
  Nour · Tunis · 120 units · 6 days", oldest first, whole days from the order date); with none: "0 older orders" · "No older order is
  waiting." An order with units delivered (Yassine Trabelsi, Boutique Lina), a delivered order (Mehdi Jaziri) and a cancelled order
  (Nour Gharbi) show nowhere. No phone number, address, price or note appears.
- [ ] **AC-4 · It updates by itself.** Given the board open, when B-0148 is moved to Finishing on Progress, then within 15 seconds
  its card is in the **Finishing** column with the word **Just moved** for 2 minutes; a new batch In cutting shows **Just started**
  and a newly recorded order shows **Just arrived**, each for 2 minutes, with no sound and nothing that blinks. When B-0148 is put
  in stock, its card leaves and **Put in stock today** rises by 4 units (from 38 to 42); undoing that restock takes the 4 units off
  again. The counter counts from the start of the workshop's day. Moving, starting, recording or putting in stock is done on
  Progress and Orders as today; the board only shows it.
- [ ] **AC-5 · Many batches.** Given a column with more than 5 batches, then every column switches to one-line cards (number ·
  product · colour and size · units · since), up to 12 in a column, oldest first, then "and 4 more batches". On the orders side, up to
  3 new orders then "and 2 more new orders", up to 3 waiting lines then "and 5 more waiting", and up to 4 products on a card then "and
  2 more products". Nothing scrolls sideways.
- [ ] **AC-6 · Empty, loading and lost connection.** With no batch the board reads "0 units in production", "No batch in production",
  "Nothing in production" and "Batches started on Progress show here, phase by phase, as soon as they are cut.", and still shows New
  orders and Waiting. With no new order: "0 in 48 h · 0 units" · "No new orders" · "Orders of the last 48 hours show here when they
  arrive." A step with no batch stays as a column: "Pressing" "0 units · 0 batches" · "Nothing in Pressing". While loading, the header
  shows at once with grey placeholders and no spinner. When the server can't be reached, then within 15 seconds an amber band
  reads "Connection lost. This board shows what was happening at 14:32. It tries again every 15 seconds." with the figures unchanged
  and nothing dimmed; after 2 minutes "Connection lost for 4 minutes. Last update 14:32. Check the Wi-Fi of this screen, or reload
  the page."; the band leaves at the first refresh that works. If the board can't load at all: "Couldn't load the board." · "Trying
  again every 15 seconds. Check the Wi-Fi of this screen if this stays."
- [ ] **AC-7 · Look.** The dialog's group **Look of the board on the TV** offers **Light**, **Dark** and **Follow the TV**, starting as
  the owner's own setting. A change saves on its own ("Look of the board updated.") and applies to the TV at its next update with the
  same link; if saving fails: "Couldn't save the look. Try again." and the choice goes back. All board text is at least 20 px on a 1920
  px screen and readable in both looks.
- [ ] **AC-8 · Switch off.** Given an active link, when a member presses **Switch off link**, then "Switch off the TV link?" asks, with
  **Keep link** and **Switch off link**. After confirming, the toast "TV link switched off." shows, the dialog says "The TV link was
  switched off on 11 Nov 2026 by Admin. The TV shows a notice until you create a new one.", and the old address shows "This TV link
  was switched off." · "Ask the owner for a new one." with no data. An address that never existed reads the same. **Create new link**
  gives a different address, and the old one stays dead. If another member changed the link meanwhile: "The TV link changed since
  you opened this window." and the dialog reloads. Errors read "Couldn't create the TV link. Try again." · "Couldn't switch off the TV
  link. Try again." · "Couldn't copy the link. Select it and copy it yourself." · "Your browser blocked the board's window. Allow
  pop-ups for Invory, or copy the link and open it yourself."
- [ ] **AC-9 · Warnings and phone.** Before the link exists and while it is active, the dialog says in an amber note: "Anyone with the
  link sees the board without signing in, including your clients' names and cities. Keep it inside the workshop. Nothing on the board
  can be changed." On a phone (375 px), **Open on TV** is full width under **Scan a ticket** and **Phases**, the dialog opens as a
  sheet from the bottom with 44 px buttons, **Open board** first, and the board is one column.
