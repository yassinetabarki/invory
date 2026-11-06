# Invory — project status report

> Owner: **product-owner** (updated at every phase boundary and after every accepted story) · Report date: **2026-11-06**
> Sources: every plan in [docs/plans/](plans/README.md), the [backlog archive](archive/backlog-2026-10-14.md) and `git log` on `main`.
> Gate results: run by the main session on 2026-10-14 for this first report. Later updates quote the tech-lead's latest delivery report
> (the product-owner never runs gates).
> Decision IDs: **D-x** are this report's open decisions. Recorded decisions keep their source, e.g. "Phase 3 plan Q7" or "rendering plan Q3".
> Live documents: [ROADMAP.md](ROADMAP.md) (phases) · [BACKLOG.md](BACKLOG.md) (ordered stories) · [TECH-DEBT.md](TECH-DEBT.md) (technical register)

---

## 1. Headline

| | Status | In one line |
|---|---|---|
| **Overall** | 🟡 **AMBER** | **Since 2026-10-24 every open story is on hold (D-20)** while you rework the product around colour × size variants; the next work is numbered INV-1, INV-2 …, a UI card first and then a development card. 
| Process | 🟡 AMBER | Roles are split: product-owner (what and why) and tech-lead (how). Phase 5 started before Phase 4 closed, by your choice (D-12). S-08 and S-29 were closed without their last checks, by your choice (D-13). Since 2026-10-24 the card flow is paused (D-20): new work gets a UI card first, then a development card, numbered INV-1, INV-2 …; the process documents are the main session's to update. |

**Current work:** **the colour × size variant rework** (since 2026-10-24, D-20): fixed colours, stock and a barcode per colour and
size, restock of finished products, clients and a production progress page. **Every open story of every phase is on hold** (cards
`On hold`), and nothing is planned, in progress or waiting for acceptance. The next work, in your order (D-21): **INV-1** colours,
INV-2 product sizes × colours with stock and a barcode per variant, INV-3 restock of finished products, INV-4 clients, INV-5 a
production progress page. Each gets a UI card first and then a development card. **INV-1 "Colours as fixed values" is `Done`**
(accepted 2026-10-31, after the designer's review, one fix round and your click-through; `a66f4bf` and `78a4eee` on `main`): each
workshop has its own list of 20 colours in Settings › Colours, every product has at least one colour (White for products recorded
before), chosen in Add / Edit product, and the product page shows its "Colours". **INV-2 "Product sizes × colours, stock per
colour and size" is `Done`** (accepted 2026-10-31 after the designer's review, one fix round and your click-through; `46af1cc`,
`ab313eb`, `4088200` on `main`, not pushed yet; tech-lead's gates green, PostgreSQL parity 2619 tests passed before the commits): every
colour and size of a product has its own finished stock, the Sizes & colours card shows the table and holds **Update stock**
(colour pills; none for a one-colour product), and a colour with stock can't be taken off a product. **INV-8 "Barcodes for every
colour and size" is `Done`** (accepted 2026-11-01 after the designer's review, one fix round and your click-through; `9f93175`,
`28003cb`, `63513ef`, `4678cad`, `5704050`, `36b34a3`, `3b55319`, closing docs `da41e1b`, on `main`, not pushed yet): the Sizes & colours
card has two tabs, Stock by colour and Barcodes; every colour and size gets its own EAN-13 barcode, made by Invory in the workshop's own
range, all or nothing; products with the same name share barcodes; a renamed product keeps them, or the rename is refused when it
would give one colour and size two numbers. **INV-4 "Clients as an entity" is `Done`** (accepted 2026-11-02; INV-12's phone-first
Client card was built within it, see §4). **INV-5 "Progress, production for the shelf" is `Done`** (accepted 2026-11-02, on
`PI-DEV`, see §4). **INV-15 "Work tickets and the Scan page" is `Done`** (accepted 2026-11-03, on `PI-DEV`, see §4), with
Settings › Labels and its Ticket size. **INV-13 "Search products, order lines per colour and size" is `Done`** (accepted
2026-11-03, on `PI-DEV`, see §4). **INV-14 "Order tracker, served from the shelf" is `Done`** (accepted 2026-11-03, on `PI-DEV`, see
§4). **INV-3 "Restock finished products" is `Done`** (accepted 2026-11-06 after you merged `PI-DEV` into `main` and confirmed it
yourself; `2575dd0` … `cbdae79`, docs `194534a`, `c8c4716`, browser golden path `aeee262`): the Restocks page, New restock (colour ×
size grid, scan or type a barcode), the restock detail, Undo restock, and "Restock" on the product page. **Next: INV-18** "Sizes chosen per
product, managed in Settings" (an `Idea`, D-29, 2026-11-06; its UI design is in progress), then INV-16 "Material use per colour and
size" (card `Ready`, **on hold until INV-18 is done**), then INV-17 "Per-unit QR codes" (UI card `Approved` and development card `Ready`, both 2026-11-06, D-28), then INV-9 "Print barcode
labels" (card `Ready`), then INV-7.
**Phase 5: Customer orders** (started 2026-10-15, D-12; on hold since 2026-10-24). **Done:** S-48 (accepted 2026-10-15), and **F-41, S-49
and S-53** (accepted 2026-10-21 after your one click-through; built in one build and landed on `main` together, `e99615e` … `8ab9df8`,
closing docs `f803cb1`), **S-50** (accepted 2026-10-22; `ba0a0cd`, `74f05a8`, `a23a5d7`, merged in `0270014`), **S-58**
(accepted 2026-10-22 after your click-through; `2f02d87`, `9eeee19`, `f1074b6`, `ce9a36f`, `eb5ff13`, docs `69b064b`; on `main`),
**S-60 and S-61** (accepted 2026-10-23 after your one click-through; one commit `1db11bb`, on `main`), **S-56**
(accepted 2026-10-23; one commit `83062eb`, on `main`), and **S-70** (accepted 2026-10-23 after one fix round and
your click-through; six commits `b200905` … `4d646e9`, on `main`), and **S-71** (accepted 2026-10-24 after one
fix round and your click-through; `722638c`, `7e00fff`, `da6ff31`, card `c3b0eb3`, on `main`), and **S-73** (accepted 2026-10-24
after the designer's review and your click-through; `51ba6ce`, `078bead`, `bf5db86`, closing docs `507ff22`, on `main`).
Everything, S-73 included, is on GitHub (`origin/main` at `507ff22`, checked 2026-10-24).
**Every Phase 5 Must is done, and the phase's exit criteria are met.** Phase 5 stayed open for its Shoulds (D-18, 2026-10-23):
S-56, S-70, S-71 and S-73 are done. The rest is on hold since 2026-10-24 (D-20); the order before the pause was S-57 "Cancel an
order" (card approved 2026-10-22, D-17), then S-74 "Long figures and small leftovers on the product page" (no card yet), then S-65
"Close a fully shipped order" (card approved), then S-66, S-67 and S-51 (no cards yet). Phase 5 tech allowance: F-41 only, done.
Nothing is in progress, so the two-story limit is free.
**Phase 4: MVP release readiness** is on hold for later; S-08 and S-29 are done (closed as they stood, D-13; see [ROADMAP.md](ROADMAP.md)).
Phases 6, 7 and 8 are on hold too (D-20).

---

## 2. Decisions needed from you

**Decided on 2026-11-06: INV-3 accepted.** You merged `PI-DEV` into `main` and confirmed INV-3 "Restock finished products" (AC-1 to
AC-7) `Done`: the Restocks page, New restock (colour × size grid, scanning), the restock detail and Undo restock, and "Restock" on
the product page's Sizes & colours card. Eleven commits `2575dd0` … `cbdae79` (INV-3-a … INV-3-k), docs `194534a`, `c8c4716`, and the
browser golden path `aeee262`. It left the open list.

**Decided on 2026-11-06 (D-29): sizes chosen per product come first.** You decided that each product gets its own sizes from one
workshop list in Settings (like units), built **before** INV-16. It is `INV-18`, an `Idea`. Decided with it: the category pre-selects
sizes on Add product; existing products keep the sizes they have; a size is never deleted (rename and reorder only); a size that is
used can't be taken off a product; a product keeps at least one size; sizes are ordered by position in Settings. INV-16's card stays
`Ready` with its 4-size Denim Jacket example but **waits**; its Technical work is replanned afterwards. New order: `INV-18` →
`INV-16` → `INV-17` → `INV-9` → `INV-7`. Next free ID: INV-19.

**Decided on 2026-11-06 (D-28): INV-17 registered.** You asked for a QR code per unit (one garment), so that progress is kept per
unit as well as per batch: scanning a unit's code moves only that unit. Both modes stay and the user chooses (move a batch, or move
units one by one). It is `INV-17` "Per-unit QR codes", placed after INV-16 and before INV-9: `INV-16` → `INV-17` →
`INV-9` → `INV-7`. The same day you decided how the two modes fit together (a unit scan splits the unit off its batch and joins the batch of
its start already in that phase; unit codes by a tick at Start batch or "Print unit codes"; the last phase puts one unit in stock; a
"Move units as soon as I scan" switch), and its **UI card is `Approved`** ([INV-17-per-unit-qr-ui.md](stories/INV-17-per-unit-qr-ui.md),
2026-11-06) with three answers from you: a unit ahead of the floor is not refused, every move joins a batch of the same start already in
the phase (batches without unit codes too), and the two limits are left to the tech-lead. Its **development card is `Ready`** (you approved it on 2026-11-06,
[INV-17-per-unit-qr.md](stories/INV-17-per-unit-qr.md)): ten criteria, size L, kept as one card (the tech-lead proposes a split only if it
rates the work above L). Your answers: the unit toasts stay as written (the note's last sentence is dropped when all units of a batch
move); batches of one start already side by side in a phase are left as they are, and the next move joins the lowest-numbered one. Its
Technical work is added only when its turn comes (`INV-18` → `INV-16` → `INV-17`). Next free ID: INV-19.

**Decided on 2026-11-06 (D-27): INV-16 prioritized now.** You asked to start `INV-16` "Material use per size and colour" right away,
ahead of the rest of the queue, instead of appending it after `INV-7`. It is still pending an ADR and the tech-lead's agreement on the
costing rules before it can be carded. **Next: INV-16, then INV-9.**

**Decided on 2026-11-03: INV-14 accepted.** Old orders: Shipped became Delivered and Ready-to-ship units went on the shelf as one
restock "Orders" per workshop (your answer 1b); kept as one card (L). Orders no longer cut: the order page has the Order tracker, Mark
delivered (off the shelf), its move back and Cancel units; Delete order is never refused. One commit `7af1a63` on `PI-DEV`
(`--no-verify`). A line recorded before sizes can't be delivered until it gets real sizes. **Next (D-26): INV-3's acceptance, then INV-9.**

**Decided on 2026-11-03: INV-13 accepted.** You moved INV-13 then INV-14 ahead of INV-3's acceptance and INV-9 (D-26), approved
INV-13's card and Technical work, left "Can produce now" out of the search, and accepted the delivery: one commit `39647ce` on `PI-DEV`
(`--no-verify`, the Talisman hook). Order lines now hold colour and size; Search products shows the shelf per colour and size; a colour on
open orders can't be taken off its product. **INV-14 is next.**

**Decided on 2026-11-03: INV-15 accepted.** You asked to build INV-15 whole without waiting for INV-9, so Settings › Labels exists
with Ticket size only and INV-9 adds its label settings there. You accepted INV-15 (AC-1 to AC-7) after your own checks; it is `Done`.
You chose one commit per part, and `--no-verify` for the three last ones (the Talisman hook flags the list item props of React screens).
Next: INV-3's acceptance, then INV-9.

**Decided on 2026-11-02: INV-5 accepted; D-25 reorders the backlog.** You accepted **INV-5** "Progress, production for the
shelf" (AC-1 to AC-7) after running the checks yourself; it is `Done`. You then said to start **INV-15** (work tickets and the Scan
page) now, ahead of INV-3's acceptance and INV-9: its development card is written first, and its Ticket size part waits for INV-9's
Settings › Labels. New order: `INV-15` → `INV-3` (acceptance) → `INV-9` → `INV-13` → `INV-14` → `INV-7`.

**Decided on 2026-11-02: INV-4 accepted; D-23 reorders the backlog.** You accepted **INV-4** "Clients as an entity" (AC-1 to AC-7,
with INV-12's Client card built within it); it is `Done`. You then said to start **INV-3**: it now comes ahead of **INV-9**. New
order: `INV-3` → `INV-9` → `INV-5` → `INV-13` → `INV-14` → `INV-7`. You approved INV-3's development card the same day (`Ready`; size L,
kept as one card).

**Decided on 2026-11-01: Progress for the shelf and the order tracker, your answers and approvals** (all the recommended options).
You validated the rewritten **INV-5** UI card (it replaces the one of 2026-10-30), the redrawn **INV-14** UI card, and a one-line
wording fix in **INV-13** (re-approved). Your answers:
1. **Put in stock leaves a trace in Restocks:** one restock per Put in stock, Reference = the batch number, Note "From production",
   undoable like any restock; an undone restock does not bring the batch back, and its materials stay used.
2. **A scan can finish a batch** (at the last phase it asks "Put 4 units in stock?").
3. **Undo batch only while the batch is In cutting;** later phases move back first.
4. **Progress shows no order data;** the owner decides what to make from the shelf and what is in production. A "Waiting in open
   orders" column could be its own later card.
5. **Part of a batch can be put in stock;** the units left keep the batch number.
6. **INV-13's line** now reads "(M is out of stock, so those units wait for the shelf)".
7. **Defaults kept:** "In stock" in the product search and "On the shelf now" on the order page and Progress; no delivery history on
   the order; no "Make more" link; one number for a batch and its ticket; Start batch only on Progress and its product cards; a colour
   or a category with units in production can't be taken off or changed on a product.

**Decided on 2026-11-01: INV-12 "Refactor the order", your answers and approvals.** The request was split into three UI cards.
You validated **INV-12 (client first on New order)** and **INV-13 (search products and see stock)**, so both are `UI approved`.
**INV-14 (order tracker)** stays `UI draft` until its boards are redrawn. Your answers:
1. **Order lines are a colour × size grid**, decided now and built before INV-13; old orders read as the product's first colour.
2. **Words:** "Not started" and "Delivered" everywhere. **The client is found by phone first**, with "Find a client by name"; a known
   phone uses the saved client unchanged, with a note; phones match by digits only. Cards go Client → Products → Details.
3. **Cancel** only what is not delivered, no undo, no stock effect. **Out of stock never blocks** an order and gives no warning.
4. **Finished products are not tied to an order.** Progress makes products for the shelf; an order is covered from the finished
   stock. The shelf is checked, never held (no reservation). **Mark delivered** takes the units off the finished stock, up to what
   the shelf holds; its move back returns them.
5. **An order counts** To deliver · Delivered · Cancelled · Total; its status is Not started · In progress (partly delivered) ·
   Delivered · Cancelled; the list filter is Open orders · Delivered · Cancelled · All orders.
6. **Deleting an order is always allowed** (no refusal), and the confirm adds a sentence when units were delivered.
7. **INV-5's UI card is reopened.** The designer redraws Progress as production for the shelf, the product-owner rewrites the card
   and you validate it again. Order of work: INV-9 → INV-3 → INV-4 → INV-5 → INV-12 → INV-13 → INV-14.

**Decided on 2026-10-31: INV-1 accepted, your answers during the build and the acceptance.** You accepted INV-1 after the
designer's review and one fix round, and it is on `main` as `a66f4bf` (Settings › Colours, AC-1 to AC-5) and `78a4eee` (colours on
products, AC-6, AC-7). Your answers:
1. **Colours need no stock check yet:** the rule "a colour with units in stock can't be taken off a product" moved to INV-2's
   development card, where stock per colour arrives.
2. **The picker with no match** shows "No colour named "teal"." and "Missing one? Add it in Settings › Colours." as plain text.
3. **Small letters are forgiven:** a code typed "stw" is saved as STW; a swatch typed "#b7c3cf" or "B7C3CF" (no "#") is saved as
   "#B7C3CF"; a wrong position reads "Enter a whole number from 0 to 32,767, or leave it empty.".
4. **One card, one build, size L** (7 steps), instead of a split.
5. **On a phone the picker starts with the focus on the sheet**, so the keyboard stays closed until you tap "Search colours" (added
   to AC-7); "Choose at least one colour." goes away as soon as one colour is chosen (added to AC-6).
6. **Bigger tap targets on the Settings pages** are not part of INV-1: a separate app-wide Could, INV-7.
7. **PostgreSQL parity runs before the commit:** 2 604 of 2 604 passed.
- **After the designer's review, one fix round:** the colour window fits a phone screen with its buttons in view; the chip ✕ has a
  full-strength focus ring; a 32-letter name breaks inside its box; the colour well is linked to its hint and error, and an empty
  name previews as a swatch beside "Name".
- **Left open, by design:** the swatch edge on a hovered picker row reads 2.77:1 light and 2.81:1 dark, where 3:1 is wanted; no row
  tint fixes it, so it is the tech-lead's INV-6 (minor). Home/End keys in the picker and the focus after taking off a chip go to
  the later UX polish bundle.

**Decided on 2026-10-30: INV-5 work tickets, your answers on the revised UI card** (all five the recommended (a)). You then
validated the UI card the same day, so INV-5 is `UI approved`:
1. **Orders per colour and size** stays in INV-5's card, marked "to be discussed"; it is decided before INV-5's development card.
2. **A QR code stands for a bundle:** the units of one colour and size that one Start cutting cut for one order line. A scan moves
   the whole bundle one phase on; moving part of it stays in the move windows, which split its ticket.
3. **Scanning stops at Ready to ship;** shipping stays the order page's Ship.
4. **People scan both ways:** the Scan page in Invory (camera, scanner or typed number) and the phone's own camera opening the
   ticket's link.
5. **One ticket per colour and size for each cut.**
- **Ticket size in Settings › Labels:** your validation of INV-5 includes the "Ticket size" choice beside INV-2's label size. INV-5
  owns it (tickets only exist with INV-5), so INV-2's card stays as approved and INV-5's card carries it.
- Decided by the product-owner: one confirm per scan, with a warning on a second scan within 2 minutes; tickets on an A4 sheet of 8
  by default or 100 × 60 mm labels; the phase view's buttons unchanged, with a Tickets column; Scan in the sidebar after Progress.

**Decided on 2026-10-30: INV-5 progress, your answers on the UI card** (before the QR work tickets were added):
1. **Production phases:** each workshop keeps its own list in Settings › Production phases, 0 to 6 phases between In cutting and
   Ready to ship, starting with Sewing and Finishing; To start, In cutting, Ready to ship and Shipped stay fixed; a phase that holds
   units can't be moved or removed.
2. **The shelf:** an order's units never go on the shelf; Ready to ship and Shipped never change "In stock". **Idea noted:**
   production for the shelf (a batch with no client going through the phases and ending with "Put in stock"), as a later INV card.
3. **Colour lock:** a colour can't be taken off a product while it is on an open order ("Light wash is on 1 open order, so it can't be
   removed from Denim Jacket.").
4. **Orders per colour and size** (order lines counted per colour, the colour × size grid in New order and Edit order): in your words,
   "ignore this part we will discuss later". **To be discussed**; the card will mark it so.
- **New request, the QR code**, in your words: "when product is in progress we will generate a QR code that when the cutter or any
  other user will scan that code it mean we move the next phase until the finish for now we will not handle the part where we go back
  when scanning the QR code". So: while units are in progress Invory makes a QR code; scanning it moves those units to the next phase,
  phase after phase until the end; moving back by scanning is left out for now. The designer is adding it to the spec and boards
  (making the code, the work ticket, a scan moving units on, the end state, the errors); the product-owner then revises the UI card.

**Decided on 2026-10-30: INV-4 clients, your request and your answers on the UI card.** You then validated the UI card the same
day, so INV-4 is `UI approved`. Your request: keep the design, add "the source from where this client has come (facebook, insta .... other social
media or from web site)", and on a new order "fields to create the client also in case of new clients"; Invory is "mostly B TO C".
1. **Required fields:** a client has a Name, a Phone and a City, all required ("Enter the client's city." when empty); the Source is
   optional.
2. **Duplicates:** a phone belongs to one client only ("22 345 678 is already the phone of Amira Ben Salah."); a name already used is
   allowed, with a note that offers the existing client. Merging clients is out of scope.
3. **Orders recorded before INV-4:** in your words, "all the record are dummy data we still are dev phases so we can delete". They are
   not turned into clients. **For the tech-lead:** the existing order data is dummy and may be dropped, which is your call; the rule
   of the project instructions never to reset or re-seed the dev database `laravel` still holds until you say otherwise, so the
   tech-lead asks you how before anything is removed.
4. **Editing a client:** "there is no edit for now". Clients are created only with Add client or as a New client in New order.
5. **Removing a client:** "there is no delete of client for now"; no delete and no archive.
6. **The source list:** fixed, the same for every workshop (Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop, Word of
   mouth, Other); "Other" takes an optional text of up to 40 characters. No social-media name or handle on the client.
- Decided by the product-owner from the designer's options: a Clients page and a client page; the "Existing client · New client"
  switch on New order (on Existing client, or New client when the workshop has none), with the picker only in Edit order; the phone
  format (8 Tunisian digits shown "22 345 678", or + and a country code); the city free text with suggestions; the source as a grey
  label with an icon and the word, no brand logos or colours, picked with chips (a second tap un-chooses, since it is optional); the
  "already a client" note as soon as the phone or name field is left; a Client filter on the Orders list that never changes the
  totals; the client page's orders open on "All orders".

**Decided on 2026-10-25: INV-3 restock finished products, your answers on the UI card** (all four the recommended (a)). You then
validated the UI card the same day, so INV-3 is `UI approved`:
1. **A wrong restock is undone as a whole**, at any time, as long as every colour and size it added still has at least that many
   units on the shelf; it stays in the list, marked "Undone". Otherwise: "This restock can't be undone: Denim Jacket in Indigo, size
   M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update stock instead."
2. **A restock never changes materials** (units made in the workshop already took theirs when they were cut, S-53).
3. **Restock is the main button** of the Sizes & colours card, Update stock an outline button, with the line "Last restock: 25 Oct
   2026, 24 units · See the restocks of Denim Jacket" ("Not restocked yet." when there is none).
4. **The Update stock window's line** reads "Finished units on the shelf, counted per colour and size. Saving sets these counts; to
   add units that arrived, use Restock."
- Decided by the product-owner from the designer's options: a Restocks page in the sidebar plus Restock on the product page; a
  colour × size grid with every colour visible; a "Scan or type a barcode" field for a barcode scanner (no phone camera); only
  restocks are recorded (Update stock still writes no history, S-56); draft and archived products can be restocked; an Update stock
  save still wins over a restock saved in between (the last save wins, S-56).

**Amended on 2026-10-25: INV-2's Sizes & colours card** (approved by you through your INV-3 answers 3 and 4; the card stays `UI
approved`): Restock is the main button and Update stock an outline button; the "Last restock" line sits under the stock table; the
Update stock window's line is the new one above.

**Decided on 2026-10-25: INV-2 sizes × colours and barcodes, your answers on the UI card.** You then validated the UI card the same
day, with the amendment of INV-1 below, so INV-2 is `UI approved`:
1. **Every product has at least one colour** ("every product must have at least one"). This reverses INV-1's "optional"; see the
   INV-1 amendment below.
2. **Stock counted before colours:** "all product get the first color by default". Today's per-size units go into the product's
   first colour, in the order of Settings › Colours (White, in the 20 defaults). There is no "No colour" stock.
3. **A colour with stock can't be taken off a product** until its stock is 0: "Indigo has 6 units in stock, so it can't be removed
   from Denim Jacket. Set its stock to 0 first." Its barcodes stay and come back with the colour.
4. **Barcode numbers:** "the barcode is generate for each product based on the name+size+color so if have 3 jacket with same color and
   size and name they will have the same barcode". The numbers are in-store EAN-13: each new name + colour + size takes the next number
   of the workshop's own range. No GS1 prefix and no numbering choice in Settings.
5. **"The same name"** ignores capitals and extra spaces ("denim jacket" = "Denim Jacket").
6. **Renaming a product keeps its barcodes**, so a barcode shared with another product can then stand for two names. No confirm. A renamed product that later gets a new colour shares that colour's barcodes under its current name (your answer to the designer).
7. **"Update stock" moves into the Sizes & colours card only.** This changes S-56's delivered AC-2 ("Update stock", a button at the
   top right of the "In stock" figure), with your approval through this answer. The S-56 card stays as accepted; INV-2 carries the
   change.
8. **The header keeps INV-1's** "Sizes S, M, L, XL" badge and "Colours" line, not the board's "4 sizes · 3 colors".
- Decided by the product-owner: the variant SKU "PRD-JKT-001-IND-S"; a row's "Print label" prints one label at once, "Print labels ·
  Indigo" opens a count per size; labels 50 × 30 mm or A4 3 × 8 with the price shown by default; always a confirm before generating;
  archived and draft products work like active ones; the boards' footnotes about sales, orders and scanning are dropped; colours in
  the order of Settings › Colours in every example. With no numbering choice left, the settings page holds only label size and price,
  so it is called **Settings › Labels**.

**Amended on 2026-10-25: INV-1 colours are required** (approved by you through your INV-2 answer 1; the card stays `UI approved`):
- The field reads "Colours", not "Colours (optional)"; saving with no colour shows "Choose at least one colour." and saves nothing.
- The product page's "None yet · Add colours" line is gone.
- Every product already recorded when colours arrive gets the first colour of the workshop's list (White).
- A colour with units in stock can't be taken off a product (INV-2 answer 3), with the message above.

**Decided on 2026-10-25: INV-1 colours, your answers on the UI card.** You then validated the UI card the same day, so INV-1 is
`UI approved`. You also removed the Colours field's states from the board, and kept the saving spinner and the long scrolling picker:
1. **Each workshop has its own colour list** in Settings › Colours, starting from the 20 default colours (White WHT … Forest green
   FGR), every workshop included. Colours can be added and edited (name, code, swatch); the code never changes.
2. **Colours are never removed in INV-1**, in your words: "ignore this cases for now image that the color will never be removed".
   There is no archive, restore or delete for colours, and no message about it; the buttons just aren't there.
3. ~~**A product's colours are optional**~~: replaced the same day by the INV-1 amendment above (colours are required).
4. **The 20 default colours stay as listed** (names up to 32 characters, codes of 2 to 4 capital letters or digits).
5. **The swatch's edge is the field-border grey** (3.2:1 on the card, light and dark), not your boards' fainter edge.
6. **A product's colours show in the order of Settings › Colours**, the same on every product (Black, Indigo, Light wash).
- Decided by the product-owner from the designer's options: colours are chosen in a Colours field in Add / Edit product, with a
  searchable list (a full-screen sheet on a phone); the spelling is "Colour" everywhere; the materials' "Colour" field stays free text;
  no colours on the Products list in INV-1.

**Decided on 2026-10-24: D-20 the card flow is paused.** You are reworking the product model around **colour × size variants**:
fixed colours, stock per variant, a barcode per variant, restock of finished products, a client entity and a production progress page.
- **Every open item is on hold** from 2026-10-24: each card that isn't `Done` now reads `On hold` (S-57, S-65, S-72, S-45, S-46 and the
  older cards S-30, S-31, S-32, S-40, S-41, S-42), and every backlog item without a card too (S-74, S-66, S-67, S-51, S-62, S-68,
  S-52, S-54, and Phases 6 to 8). Nothing is dropped. They all stay on hold, and each is checked against the variant model before it
  resumes (your answer).
- **A new way of working for new work:** each feature gets a UI card first, then a development card.
- Every F-xx technical item in the tech-lead's register waits too; F-41, the only technical item with a card, is done.

**Decided on 2026-10-24: D-21 the INV order and numbering** (your answers to the product-owner's questions):
- **Everything new is INV-n, technical items too**: a technical item gets an INV number and no UI card. Old S- and F- items keep
  their IDs, which are never reused (the last story ID given was S-74).
- **The order:** INV-1 colours (a fixed list) → INV-2 product sizes × colours, with stock and a barcode per variant → INV-3 restock
  of finished products → INV-4 clients → INV-5 a production progress page. The next free ID is INV-6.
- D-19 (the canvas note "sizenote") stays as it is.

- **The design board wins over the first build.** You held the screens to the board, so AC-2 and AC-6 were rewritten and built in a
  fix round: a chip per size, a 0 greyed with its "0" still shown, and screen readers hear "In stock per size: S 3, M 0, L 5, XL 2";
  "Update stock" at the top right on the "In stock" row; in the window, the note "Doesn't change materials, "Can produce now" or
  sales.", "Total after saving 10 units" as you type, and "was 0" under a size you changed.
- **Your choices during that round:** the window's old description ("Count the finished units on the shelf in each size. An empty
  size counts as 0.") goes, only the board's note shows; a non-whole value ("2.5", "abc") counts as 0 in "Total after saving", like
  the order form's Total, and the error still shows on Save; "In stock 0 units" shows no chips; shoe chips read "36: 2". An emptied
  size that was 0 still shows "was 0", as built (the product-owner's answer to the tech-lead).
- **From the first acceptance round, still standing:** focus goes back to "Update stock" when the window closes; after a refused
  save, focus doesn't jump to the first refused size (F-69, tech debt); "Cancel" stays clickable while a save runs; S-68 now also
  covers "Update stock" and the "Try again" buttons on the product page and the materials screens (your answer (a)).
- Two board lines are left out on purpose: the subtitle "Finished jackets on the shelf, counted per size." (only the note shows) and
  "Cancelled orders also add here." (that is S-57).

---

## 3. What we have (user-visible capabilities, all on real data)

| Area | What a signed-in user can do today |
|---|---|

**Under the hood:** pure costing domain with 100 % branch coverage, every application route behind a policy, 23 migrations, architecture tests,
a PostgreSQL parity suite (last green: 2 604 of 2 604 on 2026-10-31, during INV-1), and 17 ADRs (the latest, ADR-0017: finished stock per size).

---

## 4. Delivery history

| Phase | Stories | Committed | Result |
|---|---|---|---|

| **Colour × size variants** | INV-1 colours as fixed values (D-20, D-21) | 2026-10-31 (card `8e44eeb`, Technical work `8fa6d27`; INV-1-a to d `a66f4bf` (Settings › Colours, AC-1 to AC-5); INV-1-e to g `78a4eee` (colours on products, AC-6, AC-7); on `main`) | ✅ Done, accepted 2026-10-31 after the designer's review, one fix round and your click-through. Tech-lead APPROVED, all 7 steps; the fix round: the colour window on a phone, the chip ✕ focus ring, long names, the colour well, the phone picker's focus, the error clearing. Every gate green (final tree): 2 548 backend tests passed (56 skipped), 1 351 screen tests, 3 browser tests, PostgreSQL parity 2 604 of 2 604. Follow-ups: INV-6 (picker row edge, minor), INV-7 (tap targets on Settings pages) |
| **Colour × size variants** | INV-10 the PostgreSQL check runs to the end, on the right PHP (tech) | 2026-10-31 (card `1f854bf`; `76b1770`: `composer.json` and `docs/SETUP.md`; on `main`) | ✅ Done 2026-10-31. The time limit on `composer test:pgsql` is removed (proven by a 61-minute run) and `docs/SETUP.md` says which PHP runs the gates; the developer's PHP 8.3 run passed 2 604 of 2 604. **Pending:** the tech-lead's own green run of `composer test:pgsql`, to be confirmed when Docker is up |
| **Colour × size variants** | INV-2 stock per colour and size | 2026-10-31 (`46af1cc`, `ab313eb`, `4088200`; on `main`) | ✅ Done, accepted 2026-10-31. PostgreSQL parity green before the commits (2 619 tests) |
| **Colour × size variants** | INV-8 barcodes for every colour and size (L, 8 steps) | 2026-11-01 (INV-8-a `9f93175`, -b `28003cb`, -c `63513ef`, -d `4678cad`, -e `5704050`, -f/g `36b34a3`, -h `3b55319`; docs `da41e1b`; on `main`) | ✅ Done, accepted 2026-11-01 (AC-1 to AC-5) after the designer's review and one fix round (visible focus, 44 px phone tabs, the "N missing" badge on a phone, tablet cards up to 1023 px, the "Barcodes changed. Check the list." toast, the Delete sentence as its own paragraph). Tech-lead APPROVED, all 8 steps. Gates quoted from the tech-lead's delivery report, 2026-11-01: screen tests, lint and format, type check and build green. `composer test`, the PostgreSQL parity suite and the browser golden path are red only because of your own uncommitted edit to the size-list migration (Apparel gets XS, XXL and XXXL; 38 tests still expect S to XL, the same on both databases); you are handling that edit, and it is not an INV-8 gap. Follow-ups: INV-11 (small barcode clean-ups, minor tech debt) and the parked idea "Barcodes tab polish", planned with INV-9 |
| **Colour × size variants** | INV-4 clients, found by phone on New order, with INV-12's Client card (L, 9 steps; D-22) | 2026-11-02 (card `ccd4fe4`, Technical work `7528910`; INV-4-a `4ed7e4d`, -b `0e6fcec`, -c `f4f9fd8`, -d `ab5bb22`, test fix `2448e70`, chore `66acaf9`, -e `d8b2ccb`, -f `a2c2a05`, -g `5966a39`, -h `7102486`, -i `d2bf98f`; delivered `64f463c`; on branch `PI-DEV`, not merged to `main`, not pushed) | ✅ Done, accepted by the human 2026-11-02 (AC-1 to AC-7). INV-12 is done with it. Built in this session without the separate agents (the human's choice). Gates on this machine: `composer test` green except the tests that need PHP's GD extension (not installed here); Pint and PHPStan clean; screen tests (1 416), lint, type check and build green. **Not run here:** the PostgreSQL parity suite (Docker not running), the browser golden path and the GD tests: run them before merging to `main`. The orders recorded before INV-4 are put away by its migration (`php artisan migrate` on the dev database removes them, as decided). Changes during the build: the client's details load only on the order page (query budget); on a phone the Orders list shows Status and Client in the filter bar rather than a Filters panel |
| **Colour × size variants** | INV-5 Progress, production for the shelf (L, 12 steps; D-24) | 2026-11-02 (card `12496f7`, Technical work `d993ce2`; INV-5-a `9672c68`, AC-1 `9c1b08e`, AC-2 `6ae913a`, AC-5 `788c3d2`, AC-6 `351267e`, AC-3/AC-4 `3ac5d2e`, AC-7 `a6c121d`; delivered `41ea604`; on branch `PI-DEV`, not merged to `main`, not pushed) | ✅ Done, accepted by the human 2026-11-02 (AC-1 to AC-7), who ran the checks himself. Built in this session without the separate agents (the human's choice). Work tickets, Ticket size and the Scan page were left out on purpose: they are INV-15 |
| **Colour × size variants** | INV-15 work tickets and the Scan page (M, 7 steps; D-25) | 2026-11-03 (card `a523494`, Technical work `ae2716d`; INV-15-a `967dcf4`, AC-7 `d876d37`, AC-3 to AC-6 `7af351d`, AC-1/AC-2 `adea45b`; on branch `PI-DEV`, not merged to `main`, not pushed) | ✅ Done, accepted by the human 2026-11-03 (AC-1 to AC-7). Built in this session without the separate agents. Gates: `composer test` (3,046 tests), screen tests (1,514), lint, type check, build, and the PostgreSQL parity suite (3,044 of 3,044, then the scan tests 34 of 34) green. Not run: the browser golden path. New dependencies: `bacon/bacon-qr-code` (already installed), `barcode-detector` and `zxing-wasm` (the camera's QR reader, Scan page only). Run `php artisan migrate` on the dev database (two migrations) |
| **Colour × size variants** | INV-13 search products, order lines per colour and size (M, 8 steps; D-26) | 2026-11-03 (card `f07ef4e`, Technical work `7bf05bb`; `39647ce`; on branch `PI-DEV`, not merged to `main`, not pushed) | ✅ Done, accepted by the human 2026-11-03 (AC-1 to AC-7). Built in this session without the separate agents. Gates: `composer test` (3,079 tests), screen tests (1,513), lint, type check, build, and the PostgreSQL parity suite (3,079 of 3,079). |
| **Colour × size variants** | INV-14 order tracker, orders served from the shelf (L, 8 steps; D-26) | 2026-11-03 (card `432731b`, Technical work `753c611`; `7af1a63`; on branch `PI-DEV`, not merged to `main`, not pushed) | ✅ Done, accepted by the human 2026-11-03 (AC-1 to AC-7). Built in this session without the separate agents. Gates: `composer test` (2,989 tests), screen tests (1,455), lint, type check, build, and the PostgreSQL parity suite (2,989 of 2,989). |

All phase branches (`phase/1-materials`, `phase/2-ui-first-preview`, `phase/3-performance-spa-data`, `fix/rl-02-product-show-loading`,
`worktree-fix-hover-prefetch-bug`) are fully merged into `main`. They and the worktree under `.claude/worktrees/` can be deleted (human decision D-6).

---

## 5. Review of every plan

| Plan | Status written in the file | Real status (verified against `git log` and the code) |
|---|---|---|


---

## 6. What is missing or incomplete

### 6.1 Blocks the MVP release (Phase 4, on hold since 2026-10-15; must be done before go-live)
| # | Gap | Why it matters | Backlog |
|---|---|---|---|


### 6.2 Not started, needed for real-world use (Phases 5–6)
- **Speed at real catalogue sizes:** the dashboard and the "can't produce" filter cost every product in PHP, and sales totals walk every row
  of the period. Fine today, never measured at 500 products and 50 000 sales (S-27 measures, S-23 fixes).
- **On-demand material history** with "Load more" (S-33), a loading state on the products list (S-34), an instant product edit page (S-35),
  instant delete and archive feedback (S-36).
- **Production:** no runtime image, no production database chosen (it needs PostgreSQL 13+ with `pg_trgm`), no session or cache store
  decision, no health check, no backups (S-26).
- **CI results not seen yet:** since 2026-10-14 (D-1) the automatic checks run on GitHub on every push to `main` and `phase/**`, but nobody
  has looked at a result yet (gap K-1 below).

### 6.3 Open human steps
- Dev database: demo seeding was **skipped by decision** (2026-10-14). Don't re-offer it.

## 7. Risks and issues (RAID)

| # | Type | Item | Severity | Mitigation / owner |
|---|---|---|---|---|



## 9. Next steps (recommended order)
**From 2026-10-24 (D-20): the colour × size variant rework first; every open story is on hold.** Updated 2026-10-25.
1. Done 2026-10-30: all five UI cards are approved (INV-1 to INV-3 on 2026-10-25, INV-4 and INV-5 on 2026-10-30).
2. Done 2026-10-31: INV-1 colours accepted and committed (`a66f4bf`, `78a4eee`); INV-10 (the PostgreSQL check) committed as
   `76b1770`. Done 2026-10-31: INV-2 stock per colour and size accepted and committed (`46af1cc`, `ab313eb`, `4088200`; PostgreSQL parity
   green before the commits). Done 2026-11-01: INV-8 barcodes accepted and committed (`9f93175` … `3b55319`).
   Done 2026-11-02: the sizes XS to XXXL through a new migration (`1ea9c7c`), and INV-4 clients (with INV-12) accepted, on branch
   `PI-DEV` (`4ed7e4d` … `64f463c`). **D-23 (2026-11-02): `INV-3` now comes next, ahead of `INV-9`.** Human: run the PostgreSQL parity
   suite, the browser golden path and the GD tests on `PI-DEV`, then merge it to `main`.
   Done 2026-11-02: INV-5 Progress accepted, on `PI-DEV` (`9672c68` … `41ea604`). Done 2026-11-03: INV-15 work tickets and Scan
   accepted, on `PI-DEV` (`967dcf4` … `adea45b`). Done 2026-11-03: INV-13 accepted, on `PI-DEV` (`39647ce`), and INV-14 (`7af1a63`).
   Done 2026-11-06: INV-3 accepted, after you merged `PI-DEV` into `main` (`2575dd0` … `cbdae79`, `aeee262`). **D-27 (2026-11-06):
   `INV-16` came first, ahead of `INV-9` and `INV-7`; D-29 (2026-11-06) puts `INV-18` ahead of it and holds `INV-16` until INV-18 is done.**
3. Done: orders per colour and size was settled and built within INV-5/INV-13.
4. Done 2026-10-31: INV-2's development card (stock per colour and size) was built and accepted, with the rule "a colour with stock
   can't be taken off a product". INV-8 was built and accepted on 2026-11-01. **INV-3 is `Done`** (2026-11-06); `INV-18` (sizes
   per product, an `Idea`, D-29) is next, then INV-16 (card `Ready`, on hold until INV-18 is done), then INV-17 (UI card `Approved` and development card `Ready`, both 2026-11-06, D-28; Technical work when its turn comes),
   then INV-9 (card `Ready`), then INV-7.
5. Product-owner, at the next grooming: INV-7 (bigger tap targets on the Settings pages, a Could) gets its card when you ask.
6. Done 2026-10-24: the process documents describe the new way of working (UI card, then development card, INV IDs; `e2be378`).
7. Product-owner: before any on-hold story resumes, check its card against the new product model (R-21).
