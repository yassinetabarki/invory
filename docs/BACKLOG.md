# Invory — product backlog

> Owner: **product-owner** (order, wording, acceptance criteria) · Technical detail: **tech-lead** ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> Phases and goals: [ROADMAP.md](ROADMAP.md) · Progress: [STATUS.md](STATUS.md) · Done work and full history: [archive/backlog-2026-10-14.md](archive/backlog-2026-10-14.md)
> Last cleaned: 2026-10-22 (S-58 done: you accepted it after your click-through, kept the sizes between the category and the status, and wanted no follow-up story for the lock message, so it left this file; next, the four size cards waiting for your approval). Earlier the same day, S-65 "Close a fully shipped order" added as a Should after S-57, from your design, with your eight answers written into its card, then approved, so it is `Ready`; S-66 "Each shipment is remembered" and S-67 "Materials used by an order" added after it as `Idea` Shoulds in Phase 5, their cards to come later. Before that, S-50 done: you accepted it, so it left this file. Before that, 2026-10-21 (F-41, S-49 and S-53 done: you accepted them after your click-through, so they left this file; S-58 card and then its Technical work approved, so it is `Planned`: its sizes lock while the product is on any order that isn't deleted, and Settings shows no sizes). Before that, 2026-10-18 (D-15 and D-16 products by size: S-58, S-60 and S-61 added as Musts, S-56 and S-57 written per size, all five cards `Draft`; S-52 and S-54 become Won't (this time); S-62 added as a Could; S-63 and S-64 to Phase 8). Before that, 2026-10-16 (D-11 decided with your final answers: S-49 and S-53 revised, then their revised Technical work approved, so F-41, S-49 and S-53 are `Planned` and S-50 is `Ready`; S-56 and S-57 added; on 2026-10-15 S-48 done and accepted, so it left this file; before that, D-12: Phase 5 starts now, the rest of Phase 4 is on hold; D-13: S-08 and S-29 done). **Open work only**, top = next.
> Done items leave this file; they are recorded in STATUS.md and the archive.

## How to read an item
- **Status:** `Idea` → `Draft` (product-owner writing the card) → `Ready` (you approved the card) → `Planned` (you approved the tech-lead's plan)
  → `In progress` → `In review` (tech-lead gates) → `Acceptance` (product-owner check + your click-through) → `Done` (committed).
  A paused item reads "`Ready`, on hold since <date>".
- **Priority** (MoSCoW, per phase): Must · Should · Could · Won't (this time). **Size:** S (≤ 1 day of agent work) · M · L (split it).
- **Card:** the full story card (story, acceptance criteria with examples, what you will see, out of scope, questions) lives in
  `docs/stories/<id>-<slug>.md` once the product-owner writes it. Items below show the short version.
- **IDs:** `S-` stories are numbered by the product-owner, `F-` technical items by the tech-lead. IDs are never reused. Next free: **S-68** (S-47 was dropped on 2026-10-14; S-59 was never used, folded into S-58 on 2026-10-18), **F-67** (F-IDs: see [TECH-DEBT.md](TECH-DEBT.md)).

---

## NOW — Phase 5: Customer orders (started 2026-10-15, D-12)
You started it on 2026-10-15: "we will start focusing more on the orders … we will start with S-48". The rest of Phase 4 is on hold
(see below), and S-08 and S-29 were closed as they stood (D-13). You answered the scope questions the same day (D-10 in
[STATUS.md](STATUS.md#2-decisions-needed-from-you)):
- an order holds **several products**, each with its own quantity and its own four stages;
- **"Start cutting" takes the materials out of stock**, which replaces Q9;
- shipping doesn't record a sale (S-54 may add it later);
- the stages are To start, In cutting, Ready to ship and Shipped;
- the client is typed on each order, and the delivery date goes in the note;
- only active products can go on an order, and an order never sets stock aside (S-48, approved 2026-10-15).

Order of work: **F-41, S-49 and S-53 are done** (you accepted them on 2026-10-21 after your one click-through; built in one build
and landed on `main` together, as D-11 asked), **S-50 is done** (you accepted it on 2026-10-22), and **S-58 is done** (you accepted
it on 2026-10-22: every product has the sizes of its category). Next come S-60 and S-61 (below), once you approve their cards, then the
Shoulds S-56 and S-57, then S-65 (close a fully shipped order), S-66 and S-67.

**Products by size (D-15 and D-16, your answers of 2026-10-18):** S-58 came first on its own (done), then S-60 and S-61 in one build
(one click-through), then S-56 and S-57, both written per size. Every size of a product uses the same composition, so unit cost, "Can
produce" and the bottleneck don't change; a bigger size using more fabric (S-63) and a shoe size using its own sole (S-64) come later.
The product's category decides its sizes, from a short list set up with Invory: Apparel S, M, L, XL; Footwear 36 to 46 (all 11 sizes);
every other category "One size". A product gets every size of its category, and there is no Settings page for sizes for now. One selling
price and one SKU for all sizes ("PRD-JKT-001 · M"). Finished stock comes from "Update stock" and from cancelled orders; sales don't take
units out of it. S-52 and S-54 become Won't (this time) to make room. S-58 is done (accepted 2026-10-22); the other four size cards are `Draft`, waiting for your approval.

**Done in Phase 5 so far** (records in [STATUS.md §4](STATUS.md#4-delivery-history)):
- **S-48** (accepted 2026-10-15; `e3b7ae7`, `5af8ed1`): record, find, edit and delete an order of one or more products.
- **S-49** (accepted 2026-10-21): each order's own page, with To start, In cutting, Ready to ship and Shipped per product line, the moves
  between them, and an order that can't be deleted while units are in cutting or ready to ship.
- **S-53** (accepted 2026-10-21): "Start cutting" takes the materials out of stock, "Move back to To start" returns them, and each
  material's history shows "Cutting" and "Back from cutting".
- **F-41** (accepted 2026-10-21): the costing works out each material's total need per unit, which S-53 uses.
- **S-50** (accepted 2026-10-22; `ba0a0cd`, `74f05a8`, `a23a5d7`, merged in `0270014`): the Orders list shows each order's four stages
  and its status, opens on "Open orders", and shows the totals of all open orders at the top.
- **S-58** (accepted 2026-10-22; `2f02d87`, `9eeee19`, `f1074b6`, `ce9a36f`, `eb5ff13`, docs `69b064b`; on the build branch, reaching
  `main` when it merges): each product has the sizes of its category (Apparel "S, M, L, XL", Footwear "36 to 46", anything else "One
  size"), shown on the product page and in the form, and a product on an order can't move to a category with other sizes.

### S-60 · Order a product in several sizes · Must · M · feature · `Draft` (card written 2026-10-18, D-15, D-16)
Card: [stories/S-60-order-a-product-in-several-sizes.md](stories/S-60-order-a-product-in-several-sizes.md). Depends on S-58 (done); one build
and one click-through with S-61.
**You get:** on "New order" and "Edit order", a quantity per size under each product ("S 10 · M 20 · L 15 · XL 5", "Total 50 units"),
shown on the order's page and the Orders list. Lines recorded before sizes stay "One size".
**Why:** the cutting table needs to know how many of each size, not just "50 Denim Jackets".

### S-61 · Follow each size through cutting, ready to ship and shipped · Must · M · feature · `Draft` (card written 2026-10-18, D-15, D-16)
Card: [stories/S-61-follow-each-size-through-the-stages.md](stories/S-61-follow-each-size-through-the-stages.md). Depends on S-60; one
build and one click-through with it.
**You get:** each order line shows To start, In cutting, Ready to ship and Shipped for every size, and every move window asks a number
per size, so one cut can take S 4 and M 10 at once. Cutting takes the same materials per unit for every size.
**Why:** you see that 4 S and 10 M are being cut while the L and XL haven't started.

### S-56 · Finished products in stock, per size · Should · M · feature · `Draft` (card written 2026-10-18, per size, D-15, D-16)
Card: [stories/S-56-finished-products-in-stock-per-size.md](stories/S-56-finished-products-in-stock-per-size.md). After S-61; S-57 needs
it.
**You get:** each product shows the finished units on the shelf per size: "In stock 10 units", "S 3 · M 0 · L 5 · XL 2", next to "Can
produce now 112". "Update stock" lets you count and correct it, and cancelled orders add to it (S-57). "Can produce now" stays what the
materials allow, the Inventory value stays materials only, and recording a sale or a new order doesn't change it (a sale has no size).
**Why:** "how many we have in S, M, L and XL", and units made for a cancelled order wait on the shelf for another client.

### S-57 · Cancel an order · Should · M · feature · `Draft` (card written 2026-10-18, per size, D-15, D-16)
Card: [stories/S-57-cancel-an-order.md](stories/S-57-cancel-an-order.md). Depends on S-56. Separate from "Delete", which stays for orders
entered by mistake (S-49).
**You get:** "Cancel order" on an order's page; the order reads "Cancelled", and it can't be undone. Units to start are dropped, units
ready to ship go into stock in their sizes, shipped units stay shipped, and units in cutting stay until "Cutting finished · Put in stock",
per size. Cancelled orders leave "Open orders" and the totals.
**Why:** in your words: "if and order is cancelled and we already start cutting we will continue producing the product and then put them
in the stock user need confirm that cutting is finished and the product is ready to put in stock".
**Done when:** cancelling Boutique Lina's order at Denim Jacket 20 · 20 · 10 · 0 drops the 20 to start, shows "In stock 10 units" (M 5 ·
XL 5) and keeps 20 in cutting; putting all 20 in stock then shows "In stock 30 units".

### S-65 · Close a fully shipped order · Should · M · feature · `Ready` (card approved 2026-10-22; written that day from your design "Orders · shipped and closed", with your answers)
Card: [stories/S-65-close-a-fully-shipped-order.md](stories/S-65-close-a-fully-shipped-order.md). After S-61 (its tables are per size)
and S-57 (its Status filter lists "Cancelled").
**You get:** when the last unit ships, the order reads "All shipped · ready to close" on a page that shows everything (ordered and
shipped per size, the order's details and who recorded it). The only button left is "Move back" on shipped units, to fix a wrong
shipment, which makes the order "In progress" again. "Confirm finished · Close order" closes it at once as Shipped, for good ("Boutique
Lina closed as Shipped.", with "View order"); it then reads "Shipped · closed 30 Oct 2026 by Admin" and nothing can change it. Neither a
ready-to-close nor a closed order can be deleted or cancelled. The Status filter gains "Ready to close"; "Shipped" now means closed.
**Why:** a finished order today can still be edited or deleted at any time, and nothing records that you checked it was finished.
**Done when:** Boutique Lina's 80 units have all shipped, the order waits as "Ready to close" and counts in "Open orders 2" until you
confirm, then "Open orders 1", and nothing on its page can change it. Orders that shipped in full before this change become "ready to
close" too.

### S-66 · Each shipment is remembered · Should · M · feature · `Idea` (added 2026-10-22, your answer to S-65 question 5)
Right after S-65; per size (after S-61).
**You get:** each "Ship" (and each "Mark ready") is remembered with its date, sizes, units and who did it. The order's page shows a
"Shipments" table (date, product, sizes, units, by), the day each size shipped in the size tables, "3 shipments" and "15 days from
order to last shipment", and a timeline: order recorded, cutting started, first ready, first shipment, last shipment, then your
confirmation. For Boutique Lina: 24 Oct (Denim Jacket S 10 · M 10), 28 Oct (Denim Jacket M 10 · L 15 · XL 5; Denim Jeans 5-pocket S 5 ·
M 10), 30 Oct (Denim Jeans 5-pocket L 10 · XL 5). Orders shipped before it read "Shipped before shipments were recorded".
**Why:** today Invory keeps only how many units have shipped, not when or by whom, so a closed order can't show its deliveries.
Overlaps S-52 (a history of every move, Won't this time); the card says how a "Move back" on shipped units shows in the list.

### S-67 · Materials used by an order · Should · S · feature · `Idea` (added 2026-10-22, your answer to S-65 question 6)
After S-65.
**You get:** the order's page shows "Materials used": for each material, what its cuts took out of stock minus what came back, waste
included. For 50 Denim Jackets: Denim 12oz Indigo 99 m, Metal shank button 17mm 400 pc, Polyester thread 6,300 m and Woven brand label
50 pc.
**Why:** when an order is finished, you see what it really used, from the cuts Invory already remembers.

### S-51 · Units on order and in cutting show on the product page and the dashboard · Should · S · feature · `Idea` (per size when its card is written)
**You get:** Denim Jacket's page shows how many units are still to start on open orders, in cutting and ready to ship, next to "Can produce
now 82"; the dashboard gets an "In cutting" figure.
**Why:** "Can produce" doesn't set stock aside for orders not yet cut (S-48, S-53 AC-7), so you need the demand next to it to see a
shortage coming.

### S-62 · Open orders by size: what's being made in each size · Could · S · feature · `Idea` (added 2026-10-18, D-15)
After S-61.
**You get:** for one product, the units of every open order per size: still to start, in cutting and ready to ship ("Denim Jacket · M:
to start 15, in cutting 10, ready 5").
**Why:** at the cutting table you plan by size across all orders, not one order at a time.

### S-52 · Each order keeps a history of its moves · Won't (this time) · S · feature · `Idea` (was Could; D-15, 2026-10-18, to make room for sizes)
**You get:** the order page lists every move, newest first, with who did it: "15 Oct 2026 · 30 units of Denim Jacket moved to In cutting ·
by Sami".
**Why:** when two people update the same order, you can see what happened. (Each material's history already shows cutting, S-53.)

### S-54 · "Ship" can also record the sale · Won't (this time) · S · feature · `Idea` (your answer of 2026-10-15: "not now"; was Could, D-15, 2026-10-18)
**You get:** "Ship" offers "Also record a sale", pre-filled with the product, the units shipped, the selling price and today's date.
**Why:** today you would type the same sale again on the Sales page.

**Phase 5 tech allowance (~15 %):** **F-41 only** (your choice, 2026-10-15), **done** (accepted 2026-10-21, `e99615e`; card
[stories/F-41-summed-requirement-per-material.md](stories/F-41-summed-requirement-per-material.md)): the costing works out, for each
product, the total amount of each material one unit needs, which S-53 takes out of stock. Open technical items from the orders builds
(F-60 to F-63, and F-64 to F-66 from S-58, in [TECH-DEBT.md](TECH-DEBT.md)) can join the allowance if the tech-lead proposes them. Phase 4's allowance (F-22,
F-12, F-45) stays with Phase 4.

---

## ON HOLD — rest of Phase 4: MVP release readiness (postponed 2026-10-15, D-12)
You postponed it on 2026-10-15: "phase 4 will [be postponed] now for [a] late[r] time". Every card stays approved. S-08 and S-29 were
closed as they stood the same day (D-13) and left this list; their unfinished checks are known gaps for go-live (see Phase 7 below and
[STATUS.md §6](STATUS.md#6-what-is-missing-or-incomplete)). Two things still hold while Phase 4 waits:
- **No second real workshop signs up until S-45 is done** (D-9): units and categories are still shared by every workshop.
- **Phase 4 closes before the production server goes live** (Phase 7): the MVP sign-off (S-30) and S-45 are release Musts.

### S-45 · Each workshop has its own units and categories · Must · S · feature · `Ready`, on hold since 2026-10-15
Card: [stories/S-45-each-workshop-has-its-own-units-and-categories.md](stories/S-45-each-workshop-has-its-own-units-and-categories.md).
Depends on S-44 (done). No Technical work yet. When it resumes, its criteria gain one line from S-58: a new workshop's Apparel has sizes S, M, L, XL, its Footwear 36 to 46 and every other category "One size", and existing categories keep their sizes (F-65).
**You get:** each workshop starts with the standard 13 units, 12 material categories and 6 product categories, and adds, renames or
archives them without touching any other workshop. Two workshops may use the same name.
**Why:** with shared lists, one workshop renaming "Fabric" would change another workshop's screens.

### S-30 · MVP acceptance walkthrough · Must · S · acceptance · `Ready`, on hold since 2026-10-15
Card: [stories/S-30-mvp-acceptance-walkthrough.md](stories/S-30-mvp-acceptance-walkthrough.md) (the card is the click-through script).
**You get:** a step-by-step script (about 35 minutes) over every MVP screen on your own data, with the worked example typed under `WT-`
SKUs and removed at the end.
**Why:** no screen has ever been signed off. Phase 2's click-through has been pending since 2026-10-08.

### S-46 · Name your workshop · Should · S · feature · `Ready`, on hold since 2026-10-15
Card: [stories/S-46-name-your-workshop.md](stories/S-46-name-your-workshop.md). Depends on S-44; after S-45.
**You get:** a "Workshop name" field when you sign up, the name under yours in the sidebar's account menu, and a Settings → "Workshop" page
to rename it ("Workshop updated.").

### S-31 · A section that fails to load offers "Try again" · Should · S · feature · `Ready`, on hold since 2026-10-14
Card: [stories/S-31-deferred-section-try-again.md](stories/S-31-deferred-section-try-again.md).
**You get:** if a part of a page that loads after the frame fails, you see a message such as "Couldn't load the key figures." with a
**Try again** button, instead of loading placeholders that never go away.

### S-32 · A duplicate SKU saved at the same moment shows a clear message · Should · S · bug · `Ready`, on hold since 2026-10-14
Card: [stories/S-32-duplicate-sku-clear-message.md](stories/S-32-duplicate-sku-clear-message.md).
**You get:** if two people save the same SKU at the same moment, the second one sees "This SKU is already used by another material." (or
"…another product.") on the SKU field, not a server error page. Since S-44, "the same SKU" means the same SKU inside one workshop.

### S-42 · Screen readers hear "112 units", not "112units" · Could · S · bug (accessibility) · `Ready`, on hold since 2026-10-15 (postponed, kept for later)
Card: [stories/S-42-screen-readers-hear-units.md](stories/S-42-screen-readers-hear-units.md). After S-32.
**You get:** on the product page, "Can produce now 112 units" and "Sold · last 30 days 38 units" are read aloud with a space between the
number and its unit.

### S-40 · A realistic catalogue of 100 products on a fresh install · Could · M · enabler · `Ready`, on hold since 2026-10-15 (postponed, kept for later)
Card: [stories/S-40-realistic-catalogue-100-products.md](stories/S-40-realistic-catalogue-100-products.md). Depends on S-08.
**You get:** an optional setup step on a new, empty database only (never `laravel`) that adds 93 believable products and about 110
materials, covering low stock, out of stock, can't produce, drafts and negative margins. No sales; the same data every run.

### S-41 · A real photo on every item of the demo catalogue · Could · M · enabler · `Ready`, on hold since 2026-10-15 (postponed, kept for later)
Card: [stories/S-41-real-photos-for-the-catalogue.md](stories/S-41-real-photos-for-the-catalogue.md). Depends on S-40 and on **your
Pexels key** in your local settings (never committed).
**You get:** about 220 real Pexels photos, one per catalogue item, 80 KB or less each, with a licence record and "Photos provided by
Pexels" in the setup guide.

---

## NEXT — Phase 6: Fast at real catalogue sizes (was Phase 5 until 2026-10-15)

### S-27 · Measure speed on a realistic dataset · Must · S · `Idea`
**You get:** a report of how fast every page is with 2 000 materials, 500 products, 50 000 sales and 20 000 stock movements, against
agreed targets (development-only data, never on your database).
**Why:** today's pages are fast on small data. The dashboard and the "can't produce" filter cost every product, which may not scale.
**Note (2026-10-14):** once S-44 lands, the figures are per workshop, so the dataset should sit in one workshop, with a few other workshops
beside it.

### S-23 · Pre-computed product figures · Must (if S-27 shows the need) · M · `Idea`
**You get:** the products list, its "can't produce" filter and the dashboard stay fast at any catalogue size, because each product's
cost and capacity are kept up to date when something changes instead of being recalculated on every page view.
**Done when:** the figures always equal the live calculation (golden example) after every kind of change, and S-27's budgets pass.

### S-33 · Material history loads on demand · Should · S · `Idea` (was RL-03 + part of S-24)
**You get:** a material's page shows its stock figures and "what you can make with it" at once; the history fills in just after, with
"Load more" for older entries.

### S-35 · The product edit page opens instantly · Should · S · `Idea` (was F-50)
**You get:** clicking "Edit" on a product shows the form frame at once and fills it in, like the product page already does.

### S-34 · The products list shows it is loading · Could · S · `Idea` (was RL-05)
**You get:** while a filter or page change is loading, the list shows a loading state instead of looking frozen.

### S-36 · Deleting a sale or archiving a catalogue item feels instant · Could · S · `Idea` (was part of S-24)
**You get:** the row disappears at once, and comes back with an error message if the server refuses.

**Phase 6 tech allowance:** F-26 · F-38 · F-42 · F-49 (F-41 moved to Phase 5 on 2026-10-15).

---

## NEXT — Phase 7: Production go-live (was Phase 6 until 2026-10-15)

### S-26 · Production server · Must · M · `Idea`
**You get:** Invory running on a production server: fast page loads, compressed and cached static files, a health check, and a decided
home for sessions and cache. Backups and the database host are part of the card.
**Needs from you first:** decision D-5 (database host with PostgreSQL 13+, where the app can install `pg_trgm`, or a one-time
`CREATE EXTENSION pg_trgm;` by an administrator). Confirm the provisional decisions Q4 and Q6. Confirm again that sign-up stays open to
anyone before the server is public (D-9, 2026-10-14). The rest of Phase 4 (at least S-45 and S-30) is done first (D-12).
**Known gaps to close before go-live** (left open when S-08 and S-29 were closed as they stood, D-13, 2026-10-15):
- nobody has seen the `golden-path` check green on GitHub (S-08 AC-5);
- the fresh-install demo click-through wasn't walked; an automated test covers its figures (S-08 AC-6);
- nobody has seen a password-reset email arrive in the test mailbox (S-29);
- the page-speed target, pages under 0.15 s (S-18), was never measured, and may not hold on the built-in server `php artisan serve` (S-29).

**Phase 7 tech allowance:** F-43 · F-46 · F-52 · F-53.

---

## LATER — Phase 8: Planning features (was Phase 7 until 2026-10-15; candidates; order to confirm with you)

| ID   | You get                                                                                                         | Priority (proposed) |
| ---- | --------------------------------------------------------------------------------------------------------------- | ------------------- |
| S-10 | **Production planner:** "Plan N units" shows, per material, what's needed, what's in stock and the shortage     | Should              |
| S-63 | **A bigger size uses more fabric:** a quantity per size on a composition line; cost and "Can produce" per size  | Could               |
| S-64 | **A shoe size uses its own sole:** a material per size on a composition line (38 → "Rubber cupsole 38")         | Could               |
| S-09 | **Live cost preview:** the cost updates while you edit a product's composition                                  | Could               |
| S-11 | **CSV import/export** of materials                                                                              | Could               |
| S-37 | **Custom date range** ("from – to") for the sales log and the dashboard (was F-11)                              | Could               |
| S-38 | **UX polish bundle** (F-14, F-16, F-17, F-18, F-21)                                                             | Could               |
| S-55 | **Find a product by typing** in the order and sale pickers, instead of scrolling a long list                    | Could               |
| S-39 | **"Manage units" link** in the material form (F-31 q3; you said "not now" on 2026-10-09)                        | Won't (this time)   |

- S-38 bundles: sale panel layout at 1024 px (F-14), a clearer "Filter by product" label for screen readers (F-16), unit price shown the
  same way everywhere (F-17), the darker button hover from the mockup (F-18), a clearer sentence when two materials tie as the limit (F-21).
- S-55 (feature, `Idea`, size S–M, added 2026-10-15 during S-48's planning, raised again at its delivery): the product pickers on "New
  order" and "Record a sale" let you type part of a product's name to find it, so a large catalogue doesn't mean scrolling a long list.
  Today both pickers are plain lists, sorted by name.
- S-63 and S-64 (feature, `Idea`, size M each, added 2026-10-18, D-15): S-63 lets a size use more or less of a material (S 1.70 · M 1.80 ·
  L 1.90 · XL 2.00 m of denim); S-64 lets a size use its own material, such as a shoe sole. Both change the costing rules, so the
  tech-lead writes the rule and a decision record first. You said shoes aren't made in volume ("leave it for later"), so S-64 isn't right
  after the size stories.

## VISION — not scheduled
Suppliers and purchase orders · unit conversions in compositions · a SKU and a price per size, and a page to edit the size lists · sub-assemblies · **several people in one workshop**
(inviting people, removing them, roles and permissions, and history that still says who did it after that person's account is deleted,
e.g. "by a former member", where today the "by <name>" line just disappears; S-44 already keeps the data with the workshop) · cost history snapshots ·
setting stock aside for orders not yet cut · a client list · REST API / mobile. See [ROADMAP.md](ROADMAP.md). (Production that takes
materials out of stock moved to Phase 5 as S-53, D-10.)

---

## Decisions that shape the backlog (still valid)
Q1 native serving · Q2 units editable, conversions later · Q3 any member of a workshop edits that workshop's catalogue (was "any signed-in
user", changed by D-9) · Q4 bigint keys (provisional) ·
Q6 currency/locale in config (provisional) · Q7 unit locked while used · Q8 sales stock-neutral, hard delete · ~~Q9 no production runs~~ replaced by D-10 (2026-10-15): "Start cutting" on an order takes its materials out of stock ·
F-20 restock updates unit cost by default · F-37 deleted demo products stay deleted · F-39 no thumbnails · F-40 no correction reason ·
dev DB demo seed skipped (2026-10-14) · D-1 repository on GitHub, CI on every push (2026-10-14) · D-3 Phase 4 scope approved with its four cards (2026-10-14) · D-8 overhead cost optional, blank = 0 (S-43, done 2026-10-14);
S-31 and S-32 on hold (2026-10-14) · low stock: no new story, no email alerts, no flag without "Reorder at" (2026-10-14) · large demo catalogue: new throwaway database only,
add-on after the demo workshop, no sales, same every run, no brand or sub-category, real Pexels photos with credits off-screen (S-40 Q1–Q7,
S-41 Q1–Q4, 2026-10-14) · **D-9 one workshop per account** (2026-10-14; S-44, S-45, S-46): the screen says "Workshop"; business rules
R1–R6 approved; every existing account joins one workshop holding today's data; sign-up stays open (confirm again before S-26); each
workshop has its own units and categories; another workshop's address shows the plain "404 Not Found"; deleting an account deletes only the
person and the workshop's data stays (so S-47 was dropped; its ID is not reused); photos need only an unguessable address; S-44 started
over the two-story limit · **D-10 customer orders** (2026-10-15; S-48 to S-54): several products per order, each
with its own stages (To start, In cutting, Ready to ship, Shipped); "Start cutting" takes the materials out of stock (replaces Q9);
shipping doesn't record a sale (S-54 later); client typed on each order; delivery date in the note · **D-12 Phase 5 starts now**
(2026-10-15): the rest of Phase 4 (S-45, S-30, S-46, S-31, S-32) is on hold for later; S-40, S-41 and S-42 are postponed and kept for
later; S-48 approved: only active products on an order, and an order never sets stock aside (part of D-11);
S-48 done 2026-10-15: a deleted order is hidden, with no way back on screen; a product no longer active stays on the orders that have it ·
**D-13 S-08 and S-29 closed as they stood** (2026-10-15): Done on the commits on `main`; their open checks are known gaps for go-live ·
**D-11 how cutting changes stock** (your final answers, 2026-10-16; they replace the "every recommended answer" version committed earlier
that day; S-49 and S-53 built on them and done 2026-10-21, S-50 done 2026-10-22): cutting more than stock allows is refused, with the most you can
cut and what to do; "Move back to To start" returns exactly what was taken, the latest cut first, also for a deleted product; "Delete" is
refused while units are in cutting or ready to ship, and a cancelled order uses "Cancel order" (S-57), whose cut units go into the
product's own stock (S-56); history entries read "Cutting" and "Back from cutting", dated the day you click "Move units"; no new
low-stock alerts; archived or draft products move as usual, a deleted product can't start cutting; S-49 and S-53 ship together (one
build, one click-through); S-56 and S-57 are Phase 5 Shoulds after S-50 · **D-15 and D-16 products by size** (2026-10-18; S-58,
S-60, S-61, S-56, S-57): count units per size when ordered, in production and on the shelf; every size uses the same composition
(more fabric for bigger sizes later, S-63); shoes' own soles later, as shoes aren't made in volume (S-64); the product's category decides
its sizes, from a short list set up with Invory (Apparel S, M, L, XL; Footwear 36 to 46, all 11; any other category "One size"), with no
Settings page for now; a product gets every size of its category; existing products follow their category at once and order lines
recorded before sizes stay "One size"; one line per product on an order with a quantity per size, stages tracked per size, and each move
window asks a number per size; one selling price and one SKU for all sizes; finished stock comes from "Update stock" and cancelled orders,
and sales and new orders never touch it (a sale has no size); cancelling an order can't be undone, and cancelled orders leave "Open
orders" and the totals; S-58 is built first on its own (done 2026-10-22), then S-60 and S-61 in one build; S-52 and S-54 become Won't (this time); the smaller choices take the
defaults written in the five cards, which stay `Draft` until you approve them. · **S-65 closing an order** (your answers of 2026-10-22, all
recommended): an order shipped in full stays in "Open orders" and its count until you close it ("Ready to close"); closing is final,
at once, with no reopen; before closing only "Move back" on shipped units stays, after closing nothing; neither a ready-to-close nor a
closed order can be deleted or cancelled; orders already shipped in full become "ready to close"; "Not yet" goes back to the Orders
list; shipments and the timeline are S-66, materials used S-67.
Pending: [STATUS.md §2](STATUS.md#2-decisions-needed-from-you).
