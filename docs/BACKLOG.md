# Invory — product backlog

> Owner: **product-owner** (order, wording, acceptance criteria) · Technical detail: **tech-lead** ([TECH-DEBT.md](TECH-DEBT.md), [plans/](plans/README.md))
> Phases and goals: [ROADMAP.md](ROADMAP.md) · Progress: [STATUS.md](STATUS.md) · Done work and full history: [archive/backlog-2026-10-14.md](archive/backlog-2026-10-14.md)
> Last cleaned: 2026-10-16 (D-11 decided with your final answers: S-49 and S-53 revised, then their revised Technical work approved, so F-41, S-49 and S-53 are `Planned` and S-50 is `Ready`; S-56 and S-57 added; on 2026-10-15 S-48 done and accepted, so it left this file; before that, D-12: Phase 5 starts now, the rest of Phase 4 is on hold; D-13: S-08 and S-29 done). **Open work only**, top = next.
> Done items leave this file; they are recorded in STATUS.md and the archive.

## How to read an item
- **Status:** `Idea` → `Draft` (product-owner writing the card) → `Ready` (you approved the card) → `Planned` (you approved the tech-lead's plan)
  → `In progress` → `In review` (tech-lead gates) → `Acceptance` (product-owner check + your click-through) → `Done` (committed).
  A paused item reads "`Ready`, on hold since <date>".
- **Priority** (MoSCoW, per phase): Must · Should · Could · Won't (this time). **Size:** S (≤ 1 day of agent work) · M · L (split it).
- **Card:** the full story card (story, acceptance criteria with examples, what you will see, out of scope, questions) lives in
  `docs/stories/<id>-<slug>.md` once the product-owner writes it. Items below show the short version.
- **IDs:** `S-` stories are numbered by the product-owner, `F-` technical items by the tech-lead. IDs are never reused. Next free: **S-58** (S-47 was dropped on 2026-10-14), **F-62** (F-IDs: see [TECH-DEBT.md](TECH-DEBT.md)).

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

Order of work (D-11, decided 2026-10-16): one build in a worktree, F-41 first, then S-49 and S-53, which are accepted and land on
`main` together (one click-through); then S-50, then the Shoulds S-56 and S-57. Your final D-11 answers (2026-10-16) replace the
"every recommended answer" version committed earlier that day: S-49 and S-53 were revised with them, and you approved their
revised Technical work the same day, so they are `Planned` with F-41. S-53 stays one L story.
**S-48 is done** (accepted 2026-10-15 after your click-through; `e3b7ae7`, `5af8ed1`): you can record, find, edit and delete an order
of one or more products, and nothing else in Invory changes. Its record is in [STATUS.md §4](STATUS.md#4-delivery-history).

### S-49 · Track each product of an order through cutting, ready to ship and shipped · Must · M · feature · `Planned` (card and Technical work approved 2026-10-16, revised to the final D-11 answers; next: the build)
Card: [stories/S-49-track-cutting-ready-shipped.md](stories/S-49-track-cutting-ready-shipped.md). Depends on S-48 (done); **ships
together with S-53** (D-11).
**You get:** each order's own page. Every product line has four counts that add up to its quantity: To start, In cutting, Ready to ship
and Shipped. "Start cutting", "Mark ready" and "Ship" move a number of units on; "Move back" fixes a mistake. Each line and the order read
"Not started", "In progress" or "Shipped". A deleted product can't start cutting. "Delete" is refused while units are in cutting or ready
to ship ("Move them back to To start first.").
**Why:** this is what you asked for: how many units are ready to ship, and how many are in cutting.
**Done when:** Boutique Lina's Denim Jacket line can show 20 · 20 · 10 · 0 while its jeans line shows 30 · 0 · 0 · 0; a move never takes
more than the stage holds; a started line can't drop below its started units or be removed; that order can't be deleted.

### S-53 · Starting to cut an order takes its materials out of stock · Must · L · feature · `Planned` (card and Technical work approved 2026-10-16, revised to the final D-11 answers; next: the build)
Card: [stories/S-53-cutting-takes-materials-out-of-stock.md](stories/S-53-cutting-takes-materials-out-of-stock.md). Ships together with
S-49 (D-11). **Changes a recorded decision:** Q9 "making products never reduces stock" is replaced (D-10). The new rule was written into
the business rules, with decision record ADR-0014 (accepted 2026-10-16, amended the same day to the final D-11 answers), before any code.
**You get:** "Start cutting" shows each material needed, in stock and left after, then takes it out of stock. You can't cut more than the
stock allows, and the message says what to do. "Move back to To start" returns exactly what was taken, the latest cut first. An order
with units in cutting or ready to ship can't be deleted, so no material is lost. Each material's history shows "Cutting" and "Back from
cutting" entries, dated the day you click "Move units". No new low-stock alerts.
**Why:** stock, "Can produce" and the restock list then match the shelves without correcting four materials by hand.
**Done when:** cutting 30 Denim Jackets leaves Denim 12oz Indigo at 190.6 m, Denim Jacket at "Can produce now 82" and Inventory value at
TND 9,936.680; cutting 20 with 100 buttons is refused ("at most 12").

### S-50 · The Orders list shows each order's progress and the totals · Must · S · feature · `Ready` (approved 2026-10-16)
Card: [stories/S-50-orders-list-progress-and-totals.md](stories/S-50-orders-list-progress-and-totals.md). Depends on S-49.
**You get:** every order's four counts (the sums of its lines) and its status, the list opening on "Open orders", and totals for all open
orders at the top: "Open orders 2", "To start 170 units", "In cutting 20 units", "Ready to ship 10 units".
**Why:** "so we have an idea of how many products will be produced": one look, without opening every order.

### S-56 · Finished products in stock · Should · M · feature · `Idea` (added 2026-10-16, D-11; card to be written)
Comes after S-50; S-57 needs it.
**You get:** each product keeps a count of finished units on the shelf. Denim Jacket's page shows "In stock 10 units" next to "Can
produce now 82". "Can produce now" stays what the materials allow, and the Inventory value stays materials only.
**Why:** units made for an order that was cancelled are not lost; they wait on the shelf for another client.
**Still to decide when the card is written:** whether recording a sale takes units out of this stock, and whether a new order can use it.

### S-57 · Cancel an order · Should · M · feature · `Idea` (added 2026-10-16, D-11; card to be written)
Comes after S-56. Separate from "Delete", which stays for orders entered by mistake (S-49).
**You get:** "Cancel order" on an order's page, and the order then reads "Cancelled". Units still to start are dropped. Units ready to ship
go into the product's stock at once (S-56). Shipped units stay shipped. Units in cutting stay on the cancelled order until you click
"Cutting finished · Put in stock", for any number up to the units in cutting at a time; those units go into the product's stock.
**Why:** in your words: "if and order is cancelled and we already start cutting we will continue producing the product and then put them
in the stock user need confirm that cutting is finished and the product is ready to put in stock".
**Done when:** cancelling Boutique Lina's order at Denim Jacket 20 · 20 · 10 · 0 drops the 20 to start, puts 10 in stock ("In stock 10
units") and keeps 20 in cutting; "Cutting finished · Put in stock" with 20 then shows "In stock 30 units".

### S-51 · Units on order and in cutting show on the product page and the dashboard · Should · S · feature · `Idea`
**You get:** Denim Jacket's page shows how many units are still to start on open orders, in cutting and ready to ship, next to "Can produce
now 82"; the dashboard gets an "In cutting" figure.
**Why:** "Can produce" doesn't set stock aside for orders not yet cut (S-48, S-53 AC-7), so you need the demand next to it to see a
shortage coming.

### S-52 · Each order keeps a history of its moves · Could · S · feature · `Idea`
**You get:** the order page lists every move, newest first, with who did it: "15 Oct 2026 · 30 units of Denim Jacket moved to In cutting ·
by Sami".
**Why:** when two people update the same order, you can see what happened. (Each material's history already shows cutting, S-53.)

### S-54 · "Ship" can also record the sale · Could · S · feature · `Idea` (your answer of 2026-10-15: "not now", later story)
**You get:** "Ship" offers "Also record a sale", pre-filled with the product, the units shipped, the selling price and today's date.
**Why:** today you would type the same sale again on the Sales page.

**Phase 5 tech allowance (~15 %):** **F-41 only** (your choice, 2026-10-15; `Planned`, Technical work approved 2026-10-16, built first in
the S-49 and S-53 build; card [stories/F-41-summed-requirement-per-material.md](stories/F-41-summed-requirement-per-material.md)): the costing works out, for each product, the total amount of
each material one unit needs, ready for S-53 to take out of stock. It moved here from Phase 6's allowance. Phase 4's allowance (F-22,
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
Depends on S-44 (done). No Technical work yet.
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

## VISION — not scheduled
Suppliers and purchase orders · unit conversions in compositions · sizes and variants · sub-assemblies · **several people in one workshop**
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
that day; S-49 and S-53 revised and `Planned` with their revised Technical work, S-50 `Ready`): cutting more than stock allows is refused, with the most you can
cut and what to do; "Move back to To start" returns exactly what was taken, the latest cut first, also for a deleted product; "Delete" is
refused while units are in cutting or ready to ship, and a cancelled order uses "Cancel order" (S-57), whose cut units go into the
product's own stock (S-56); history entries read "Cutting" and "Back from cutting", dated the day you click "Move units"; no new
low-stock alerts; archived or draft products move as usual, a deleted product can't start cutting; S-49 and S-53 ship together (one
build, one click-through); S-56 and S-57 are Phase 5 Shoulds after S-50. Pending: [STATUS.md §2](STATUS.md#2-decisions-needed-from-you).
