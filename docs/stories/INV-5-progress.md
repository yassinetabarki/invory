---
id: INV-5
title: Progress, production for the shelf
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
created: 2026-11-02
tags:
  - story
  - variants
---

# INV-5 · Progress, production for the shelf

> [!abstract] Description
> As a **workshop owner**, I want **to start batches of a product in its colours and sizes, follow them through my own production
> phases and put the finished units in stock**, so that **I see at a glance what is being made, where each batch is and since when,
> and the shelf fills up without counting in my head**. A **batch** is units of one product in one colour and one size, made together,
> with its own number (**B-0147**). Starting a batch takes its materials out of stock; putting it in stock adds its units to the
> finished stock and records a restock "From production". Nothing is tied to an order or a client.
> New: the **Progress** page in the sidebar (phase tiles, a table per product with "On the shelf now", a view per phase), **Start
> batch**, the moves between phases (with splitting), **Put in stock**, **Undo batch**, and **Settings › Production phases**. Changed:
> Edit product refuses two changes while units are in production. The pages are drawn in [[INV-5-progress-ui]].
> Left out on purpose: the work tickets, their printing, the ticket size and the **Scan** page (INV-15, same UI card); any link
> between a batch and an order or a client; setting shelf stock aside; starting a batch from an order or the product page; undoing a
> batch after cutting; dragging; moving several batches at once. The order page keeps its cutting buttons as today.

> [!info] How it works today
> There is no production page. The owner cuts from an order: the order page shows each line's table (To start · In cutting · Ready to
> ship · Shipped) with **Start cutting**, **Mark ready**, **Ship** and the Move back buttons. To know how many Indigo M jackets are being
> made she opens every order and adds them up in her head. Sewing, finishing or a quality check don't exist anywhere, and a pile of
> finished jackets only reaches the shelf when someone makes a restock or types a count in **Update stock**.
> Cutting takes the materials out of stock: 30 Denim Jackets take 59.4 m Denim 12oz Indigo, 240 pc Metal shank button 17mm, 3,780 m
> Polyester thread and 30 pc Woven brand label. If stock is short, cutting is refused with the number that can be cut.
> The sidebar reads Dashboard, Materials, Products, Restocks, Orders, Clients, Sales. Edit product already refuses to take off a colour
> that has units on the shelf ("Indigo has 6 units in stock, so it can't be removed from Denim Jacket. Set its stock to 0 first.") and
> to move a product that is on orders to a category with other sizes.

## Acceptance criteria

Today is 30 Oct 2026. The workshop's phases are **Sewing** and **Finishing**. The examples use these batches:

| Batch  | Product · colour · size         | Units | Phase      | Since       |
| ------ | ------------------------------- | ----- | ---------- | ----------- |
| B-0146 | Denim Jeans 5-pocket · Indigo S | 5     | Sewing     | 26 Oct 2026 |
| B-0147 | Denim Jacket · Indigo S         | 4     | Sewing     | 27 Oct 2026 |
| B-0149 | Denim Jacket · Indigo M         | 6     | Sewing     | 28 Oct 2026 |
| B-0148 | Denim Jacket · Indigo M         | 4     | Finishing  | 29 Oct 2026 |
| B-0150 | Denim Jacket · Indigo M         | 4     | In cutting | 25 Oct 2026 |
| B-0151 | Denim Jacket · Light wash · L   | 2     | In cutting | 29 Oct 2026 |
| B-0152 | Denim Jacket · Light wash · XL  | 1     | In cutting | 29 Oct 2026 |

The shelf (S · M · L · XL): Denim Jacket Indigo 2 · 0 · 3 · 1, Light wash 0 · 0 · 0 · 0; Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0. The
Denim Jacket (per unit: 1.98 m Denim 12oz Indigo, 8 pc Metal shank button 17mm, 126 m Polyester thread, 1 pc Woven brand label; 900
buttons in stock) shows "Can produce now 112", bottleneck Metal shank button 17mm.

- [ ] **AC-1 · Production phases.** The Settings menu has a group **Production** with **Production phases**: "The phases a batch goes
  through between cutting and putting it in stock. In cutting and Put in stock are always there." and **Add phase**. The list shows
  the whole path: **In cutting** (grey, lock icon, "Always there. Start batch puts a batch here and takes the materials."), the
  workshop's phases in order (here **Sewing** "Holds 15 units", **Finishing** "Holds 4 units", each with **Move up**, **Move down**,
  **Edit Sewing**, **Remove Sewing**), and **Put in stock** (grey, lock icon, "Always there. Ends a batch: its units are added to your
  finished stock.").
  - **Add phase** / **Edit Sewing** ask for a **Name** (hint "A short verb or noun your team uses, such as Sewing or Quality check.") with
    **Cancel** and **Save phase**. Refusals: "Enter the phase's name." · "Keep the name to 24 characters or fewer." · "You already have a
    phase named "Sewing"." · "In cutting and Put in stock are already there. Choose another name." · "You can have up to 6 phases."
  - A phase that holds units can't be moved or removed: the buttons can't be pressed and a tip the keyboard reaches says "Sewing holds 15
    units, so it can't be moved or removed. Move them on or back first." Removing an empty phase asks **Remove Quality check?** ·
    "Quality check is empty. It leaves Progress; batches then go from Finishing to Put in stock." · **Remove phase** · **Keep phase**; if
    it filled up meanwhile: "Quality check now holds 2 units, so it can't be removed."
  - With no phase of its own: "No phase of your own. Batches go from In cutting straight to Put in stock." · **Add phase**.
  - Toasts: "Phase added." · "Phase updated." · "Phase removed." · "Phases reordered." Each workshop has its own phases; a new workshop
    has none.
- [ ] **AC-2 · Start batch.** **Start batch** opens from the Progress header and from each product card on Progress (product already
  chosen, named "Start a batch of Denim Jacket"). The window **Start batch**, "Make products for your shelf. Materials are taken out of
  stock now.", has **Product** (searchable, showing "Can produce now 112") and "How many units of each colour and size?": a row per
  colour in the order of Settings › Colours, a field per size, the shelf under each ("Shelf 2" or the amber "Shelf Out").
  - Given Indigo M 6 and Indigo L 10: "Total 16 units · 2 batches (Indigo M 6, Indigo L 10)" and the table "Materials for 16 units"
    (Material · Taken · In stock · After): 31.68 m Denim 12oz Indigo, 128 pc Metal shank button 17mm, 2,016 m Polyester thread, 16 pc
    Woven brand label. **Start batch** makes **one batch per colour and size**, B-0153 (Indigo M, 6) and B-0154 (Indigo L, 10), both In
    cutting since today, takes those materials out of stock all at once or not at all, and "Can produce now" goes from 112 to 96. The
    shelf doesn't change. The window then shows "2 batches started" · "16 units of Denim Jacket started. Materials taken out of
    stock." · **Done**.
  - Active, draft and archived products can be started, like a restock; a deleted product can't.
  - Batch numbers are B- followed by at least four digits, counted per workshop and never reused.
  - Short stock refuses the whole batch and shows a "Short by" column: "Not enough stock to cut 20 units. You can cut at most 12 now:
    Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer." or, when nothing can be cut, "You can't cut any now:
    restock Metal shank button 17mm first." A product with no composition can be started: nothing is taken.
  - Other refusals, keeping everything typed: "Choose a product." · "Enter at least one unit to start." · "Indigo M: Enter a whole number
    from 0 to 1,000,000." · "The colours or sizes of Denim Jacket have changed. Enter the units again." · "Couldn't start the batch. Try
    again." The button shows a spinner while saving.
- [ ] **AC-3 · Progress, all phases.** The sidebar reads Dashboard, Materials, Products, Restocks, Orders, **Progress** (factory icon),
  Clients, Sales. The page **Progress**, "The batches you are making for your shelf, phase by phase, in their colour and size.", has
  **Production phases** and **Start batch**, then the tiles "Phases": **All phases** 26 · **In cutting** 7 · **Sewing** 15 · **Finishing**
  4 units (the chosen tile is marked by more than its colour and announced as the current page; a tile at 0 still opens).
  - Filters **Product** ("All products") and **Colour** ("All colours"), kept in the page address. With Indigo: "Showing Indigo · 23 of
    26 units", **Clear filters**, and the tiles In cutting 4 · Sewing 15 · Finishing 4. There is no Client and no Order filter.
  - One card per product with units in production, A → Z, 10 a page: the name (a link), the SKU, "21 units in 6 batches", **Start
    batch**, and the table "Denim Jacket by phase": Colour · size, In cutting, Sewing, Finishing, **In production**, **On the shelf now**.
    Colour rows carry their subtotals, only colours and sizes in production have rows, a **Total** row ends it, a shelf of 0 reads
    **Out**. Indigo M: 4 · 6 · 4 · 14 · Out; Indigo S: 0 · 4 · 0 · 4 · 2; Total 7 · 10 · 4 · 21. Denim Jeans 5-pocket: "5 units in 1
    batch". A "One size" product has colour rows only. Every figure comes from the server.
  - States: "Nothing in production" · "Start a batch to make products for your shelf. They are added to your finished stock when you
    put them in stock." · **Start batch** (tiles at 0); "No batch matches these filters." · "Try another product or colour, or clear the
    filters." · **Clear filters**; loading placeholders for the tile counts and two cards; "Couldn't load the progress of your
    production." · **Try again**. Progress never mentions an order or a client.
- [ ] **AC-4 · One phase.** A tile opens "Progress › Sewing": the heading **Sewing**, "15 units · 3 batches · 2 products · oldest since
  26 Oct 2026", "From In cutting. Next: Finishing.", the filters kept, and the table "In Sewing", one row per batch, oldest first, 25 a
  page: **Since** ("26 Oct 2026" / "4 days"; "1 day"; "today") · **Batch** · **Product** (a link, SKU in grey) · **Colour · size** ·
  **Units**, then **Move to Finishing** (named "Move batch B-0146 Denim Jeans 5-pocket Indigo S to Finishing") and the menu "More moves
  for batch B-0146" with **Move back to In cutting**.
  - In cutting: the button is **Move to Sewing** (or **Put in stock** with no phase of its own); the menu has **Undo batch**; Since is
    the day the batch started. The last phase (Finishing): the main button **Put in stock**; the menu **Move back to Sewing**.
  - A deleted product reads "Denim Jacket (deleted)" with no link; its **Put in stock** can't be pressed, with the tip "Denim Jacket was
    deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials."
  - States: "Nothing in Sewing" · "Batches come here from In cutting with Move to Sewing." · **Show all phases**; "Nothing in Sewing
    matches these filters." · **Clear filters**; five placeholder rows while loading; "Couldn't load what is in Sewing." · **Try again**.
- [ ] **AC-5 · Moving a batch, and splitting it.** **Move to Finishing** on B-0149 opens "Move to Finishing · Denim Jacket", "B-0149 ·
  Indigo M · in Sewing: 6 units", "How many units?" ("Up to 6", empty, focused) and **Move units**. A batch moves one phase at a time;
  **Move back to …** uses the same window. Moves never touch materials or the shelf, and each move remembers its day.
  - Moving all 6: "6 units of Denim Jacket · Indigo M moved to Finishing." B-0149 keeps its number.
  - Moving 4 of 6 splits it: the 4 units become **B-0155** in Finishing since today; B-0149 keeps 2 in Sewing with its number and its
    day. Toast: "4 units of Denim Jacket · Indigo M moved to Finishing. Batch B-0149 was split: the units moved are on B-0155."
  - Refusals: "You can move at most 6 units of this batch." · "Enter at least one unit to move." · "Enter a whole number of units." If
    someone moved the batch meanwhile: "These units have moved on since you opened this page." and **Close**; nothing moves.
- [ ] **AC-6 · Put in stock and Undo batch.** **Put in stock** on B-0148 opens "Put in stock · Denim Jacket", "B-0148 · Indigo M · in
  Finishing: 4 units", "How many units?" ("Up to 4") and "Adds to your finished stock: Indigo M goes from 0 to 4. Recorded as a restock
  "From production"." Confirming adds 4 to the Denim Jacket's Indigo M shelf (0 → 4), the batch leaves Progress, and Restocks lists a
  restock dated today, by whoever pressed it, Reference "B-0148", Note "From production". Toast: "4 units of Denim Jacket · Indigo M
  put in stock." with **See the restock**. Materials and "Can produce now" don't change.
  - Putting 3 of 4 in stock adds 3; B-0148 keeps 1 unit, its number and its phase.
  - That restock can be undone in Restocks like any other: the units leave the shelf, the batch doesn't come back and its materials
    stay used.
  - Refusals: "Enter at least one unit to put in stock." · "You can put at most 4 units of this batch in stock." · "Enter a whole number
    of units." · "Couldn't put the units in stock. Try again." No colour and size can go over 1,000,000 units on the shelf: "Indigo M
    can't go over 1,000,000 units on the shelf."
  - **Undo batch**, only for a batch In cutting, asks **Undo batch B-0150?** · "Takes the 4 units of Denim Jacket · Indigo M off Progress
    and gives back what was cut: 7.92 m Denim 12oz Indigo, 32 pc Metal shank button 17mm, 504 m Polyester thread and 4 pc Woven brand
    label." · **Undo batch** (red) · **Keep batch**. Toast: "Batch B-0150 undone. Materials returned to stock." It returns exactly what
    was taken at the cut, nothing for a material deleted or whose unit changed since. If the batch moved on meanwhile: "B-0150 has moved
    on since you opened this page, so it can't be undone. Move it back to In cutting first." Otherwise: "Couldn't undo the batch. Try
    again."
- [ ] **AC-7 · Edit product while units are in production.** Taking Light wash off the Denim Jacket (B-0151 and B-0152 hold 3 units) is
  refused: "Light wash has 3 units in production, so it can't be removed from Denim Jacket." (1 unit: "has 1 unit in production").
  Moving the Denim Jacket to a category with other sizes is refused: "Denim Jacket has units in production, so its category can't change
  to one with other sizes. Keep a category with sizes S, M, L, XL." The existing refusals (units on the shelf, on orders) stay as they
  are.
