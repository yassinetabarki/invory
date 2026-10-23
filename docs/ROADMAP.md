# Invory — product roadmap

> Owner: **product-owner** · Approved by: the human · Last updated: 2026-10-23 (S-70 "Sizes look the same on every screen" done: accepted after one fix round and your click-through, six commits `b200905` … `4d646e9` on `main`; S-57 is next. Earlier the same day, S-70 was added as a Should, next in line, from your request, and approved, so `Ready`; S-69 merged into it; earlier the same day, S-56 done, accepted and committed on `main` as `83062eb`: each product shows its finished units per size; S-68 widened to the product page and the materials screens; earlier the same day, D-18: Phase 5 stays open for its Shoulds S-56, S-57, S-65, S-66, S-67 and S-51, in that order; earlier the same day, S-60 and S-61 done, accepted after your click-through, so every Phase 5 Must is done and its exit criteria are met; S-68 and S-69 added as Coulds from the design review; earlier, 2026-10-22, D-17: the four size cards S-60, S-61, S-56 and S-57 approved, `Ready`, S-56 and S-57 with a phone-width criterion each; earlier the same day, S-58 done, accepted after your click-through; S-65 "Close a fully shipped order" approved, `Ready`, a Should after S-57, with S-66 and S-67 as Shoulds after it; before that, S-50 done, accepted; earlier, 2026-10-21: F-41, S-49 and S-53 done, accepted after your click-through; S-58 card and Technical work approved; earlier, 2026-10-18: D-15 and D-16 products by size: S-58, S-60, S-61 added to Phase 5, S-56 and S-57 per size, S-52 and S-54 Won't (this time); earlier, 2026-10-16: D-11 decided with your final answers: F-41, S-49 and S-53 `Planned`, S-50 `Ready`, S-56 and S-57 added; 2026-10-15: S-48 done, the first Phase 5 story; D-12: Phase 5 "Customer orders" started, the rest of Phase 4 on hold)
> Format: **Now / Next / Later**, organised by outcomes, not dates. Each phase is one slice of the story map with one goal, a MoSCoW-labelled
> set of stories and exit criteria. Detailed stories live in [BACKLOG.md](BACKLOG.md); progress lives in [STATUS.md](STATUS.md).

## Product goal
A small fashion workshop knows, at any moment, **what each product costs to make**, **how many it can make from the stock on the shelves**,
**which material will run out first**, and how sales are going. All of this comes without a spreadsheet and with exact numbers.

## Story map (user activities → what exists → where the rest lands)

| Activity | Built (Phases 1–3) | Phase 4 | Phase 5 (orders) | Phase 6 (speed) | Phase 7 (go-live) | Phase 8 + Vision |
|---|---|---|---|---|---|---|
| **Keep stock of materials** | List, filters, CRUD, photo, restock, history, status badges | Acceptance | | History on demand (S-33) | | Suppliers and purchase orders, CSV import (S-11) |
| **Define products** | Product CRUD, composition editor, selling price, photo | Acceptance; overhead cost optional (S-43, done) | Sizes from the category (S-58, done); finished products in stock per size (S-56, done); sizes look the same on every screen (S-70, done); units on order on the product page (S-51) | Instant edit page (S-35) | | More fabric for bigger sizes (S-63), own sole per shoe size (S-64), sub-assemblies, unit conversions |
| **Take client orders** | Nothing yet | | Orders with several products (S-48, done); to start, in cutting, ready to ship, shipped per line (S-49, done); cutting takes materials out of stock (S-53, done); progress and totals on the list (S-50, done); a quantity per size (S-60, done) and the stages per size (S-61, done); cancel an order (S-57); close a fully shipped order (S-65), with its shipments (S-66) and the materials it used (S-67); open orders by size (S-62) | | | A client list |
| **Know cost and capacity** | Unit cost, can produce, bottleneck, next limit, margin, "what you can make with it" | Acceptance, E2E (S-08) | | Fast at 500 products (S-23, S-27) | | Live cost preview (S-09), production planner (S-10), cost history |
| **Sell** | Sales log, totals, filters, record/edit/delete | Acceptance | | Instant delete feedback (S-36) | | Custom date range (S-37) |
| **Monitor** | Dashboard: KPIs, top 5, restock list, alerts | Panels offer "Try again" after an error (S-31) | Units in cutting on the dashboard (S-51) | Loading states (S-34) | | |
| **Run the business on it** | Login, 2FA, catalogue settings | Reliable dev setup (S-29), crash-free SKU saves (S-32), each workshop sees only its own data (S-44 done, S-45), workshop name (S-46) | | | Production server, backups (S-26) | Several people in one workshop (invitations, roles and permissions) |

**Phase numbers changed on 2026-10-15:** customer orders became Phase 5, so speed is now Phase 6, go-live Phase 7 and planning Phase 8.
Cards written before that day say "Phase 5" for speed (S-27, S-23), and "slips to Phase 5" there means "slips to the next phase".

---

## NOW — Phase 5: Customer orders (started 2026-10-15, D-12)
**Goal:** the workshop records every client order and sees at any moment how many units of each order, in each size, are still to
start, in cutting, ready to ship and shipped, how many units are being made across all open orders, and how many finished units of each
size are on the shelf.
You asked for it on 2026-10-15 ("design the order management UI") and answered the scope questions the same day (D-10). The same day you
started it ahead of Phase 4: "we will start with S-48" (D-12). Orders belong to a workshop, and S-44 is done, so nothing blocks it.
**S-48 is done** (accepted 2026-10-15 after your click-through): the Orders page records, finds, edits and deletes orders of one or more
products. **S-49 and S-53 are done** (accepted 2026-10-21 after your one click-through, with F-41, in one build): each order has its
own page with the four stages per product line, and "Start cutting" takes the materials out of stock. **S-50 is done** (accepted 2026-10-22): the Orders list
shows each order's stages and status and the totals of all open orders ([stories](stories/README.md)).
On 2026-10-18 you asked for products by size and answered the questions (D-15, D-16): the product's category decides its sizes, the
units are counted per size on orders, through the stages and on the shelf, and every size uses the same composition for now. **S-58 is
done** (accepted 2026-10-22 after your click-through): every product has the sizes of its category. **S-60 and S-61 are done**
(accepted 2026-10-23 after your one click-through, one commit `1db11bb`): an order holds a quantity per size, and every size is followed
through the four stages. **S-56 is done** (accepted 2026-10-23, one commit `83062eb`): each product shows "In stock" with its
units per size, and "Update stock" sets them. **S-70 is done** (accepted 2026-10-23 after your click-through, six commits
`b200905` … `4d646e9`): sizes look the same on every screen. Next comes S-57, written per size, `Ready` since 2026-10-22 (D-17). On 2026-10-22 you also approved
S-65 (close a fully shipped order, `Ready`), from your design; it comes after S-57, followed by S-66 and S-67, whose cards come later.

| Priority               | Story                                                         | Size |
| ---------------------- | ------------------------------------------------------------- | ---- |
| Must                   | S-48 Record customer orders with several products (done)      | M    |
| Must                   | S-49 Track each product line through the stages (done)        | M    |
| Must                   | S-53 Cutting takes materials out of stock (done)              | L    |
| Must                   | S-50 Orders list: progress of each order and totals (done)    | S    |
| Must                   | S-58 Every product gets the sizes of its category (done)      | S    |
| Must                   | S-60 Order a product in several sizes (done)                  | M    |
| Must                   | S-61 Follow each size through the stages (done)               | M    |
| Should                 | S-56 Finished products in stock, per size (done)              | M    |
| Should                 | S-70 Sizes look the same on every screen (done)               | M    |
| Should                 | S-57 Cancel an order                                          | M    |
| Should                 | S-65 Close a fully shipped order                              | M    |
| Should                 | S-66 Each shipment is remembered                              | M    |
| Should                 | S-67 Materials used by an order                               | S    |
| Should                 | S-51 Product page and dashboard show units on order           | S    |
| Could                  | S-62 Open orders by size                                      | S    |
| Could                  | S-68 Easier to tap and clearer buttons, orders and more       | S    |
| Won't (this time)      | S-52 Each order keeps a history of its moves                  | S    |
| Won't (this time)      | S-54 "Ship" can also record the sale                          | S    |
| Tech allowance (~15 %) | F-41 only (each material's total need, for S-53; done)        | —    |

With the size stories the Musts were about 70 % of the phase, above the usual 60 %. Every Must is done since 2026-10-23 (S-48, S-49,
S-53, S-50, S-58, S-60, S-61). Order of what's left: S-57 (D-18, D-15, D-16; S-56 and S-70 done 2026-10-23), then S-65, S-66, S-67 and S-51. S-52 and S-54 became
Won't (this time) to make room. If time runs short, S-68 slips first (S-69 is now part of S-70), then S-62, S-67, S-66 and S-51.

- An order holds one or more products, each with its own quantity and its own four stages: To start, In cutting, Ready to ship, Shipped.
- Only active products can go on an order, and an order never sets stock aside (S-48, approved 2026-10-15).
- **"Start cutting" takes the materials out of stock** (D-10, 2026-10-15). This replaces decision Q9 ("making products never reduces
  stock"). S-49 and S-53 ship together (D-11, decided 2026-10-16: one build, one click-through), so there is never a time when cutting leaves the stock untouched.
  The cutting rules (D-11, your final answers of 2026-10-16): cutting more than stock allows is refused, with the most you can cut and
  what to do; "Move back to To start" returns exactly what was taken, the latest cut first; the history reads "Cutting" and "Back from
  cutting", dated the day you click "Move units"; a deleted product can't start cutting; no new low-stock alerts.
- "Delete" stays for orders entered by mistake and is refused while units are in cutting or ready to ship. A cancelled order uses
  "Cancel order" (S-57) and reads "Cancelled"; the units already cut go into the product's own stock (S-56).
- Shipping doesn't record a sale (S-54 may add that later). Clients are typed on each order; the delivery date goes in the note.
- **Sizes** (D-15, D-16, 2026-10-18): Apparel S, M, L, XL; Footwear 36 to 46; any other category "One size". A product gets every size of
  its category; the lists come with Invory and there is no page to change them for now. Every size uses the same composition, so "Can
  produce now 112" means 112 in any mix of sizes. One selling price and one SKU for all sizes. Finished stock comes from "Update stock"
  and cancelled orders; sales and new orders never touch it.
- **Closing an order** (S-65, your answers of 2026-10-22): an order shipped in full reads "All shipped · ready to close" and stays in
  "Open orders" until you click "Confirm finished · Close order". Until then only "Move back" on shipped units works; closing is
  final, and a closed order reads "Shipped · closed" and can't be changed, deleted or cancelled.
- Won't (this time): a client list, order numbers, setting stock aside for orders not yet cut, low-stock emails or notifications (D-11),
  the order history (S-52), "Ship" recording the sale (S-54), a SKU or a price per size, a page to edit the size lists (D-15).

**Exit criteria:** you have accepted S-48 (done 2026-10-15), S-49 and S-53 (done 2026-10-21), S-50 and S-58 (done 2026-10-22), S-60 and S-61 in a click-through (done 2026-10-23) · an order of
Denim Jackets S 10 · M 20 · L 15 · XL 5 can be followed size by size through the four stages · cutting an order changes stock exactly as
the cards say · another workshop never sees your orders · every gate green.
**Met on 2026-10-23.** The phase goal also names the finished units on the shelf (S-56), so on 2026-10-23 you kept Phase 5 open for
its Shoulds, in the order above: S-56, S-57, S-65, S-66, S-67, S-51 (D-18 in [STATUS.md](STATUS.md#2-decisions-needed-from-you)).
S-56 is done (accepted 2026-10-23), so the shelf part of the goal is met; S-57 adds cancelled units to it.

## ON HOLD — Phase 4: MVP release readiness (postponed 2026-10-15, D-12)
**Goal:** the business owner has accepted every MVP screen, one automated test proves the golden path end to end, and each workshop sees
only its own data.
**Postponed on 2026-10-15** (D-12): you put the rest of Phase 4 on hold for later. The same day you closed S-08 and S-29 as they stood
(D-13): they are done on the commits already on `main`, and their unfinished checks are known gaps for go-live (Phase 7).
Every card stays approved. S-44 and S-43 are done. Until S-45 is done, no second real workshop signs up (D-9), and Phase 4 closes before
the production server goes live (Phase 7).

| Priority | Story                                                              | State                             | Size |
| -------- | ------------------------------------------------------------------ | --------------------------------- | ---- |
| Must     | S-29 Reliable dev environment                                      | ✅ Done 2026-10-15 (as is, D-13)   | M    |
| Must     | S-08 End-to-end golden-path test + demo data on a fresh install    | ✅ Done 2026-10-15 (as is, D-13)   | M    |
| Must     | S-44 Each workshop sees only its own materials, products and sales | ✅ Done 2026-10-15                 | M    |
| Must     | S-45 Each workshop has its own units and categories                | On hold since 2026-10-15          | S    |
| Must     | S-30 MVP acceptance walkthrough                                    | On hold since 2026-10-15          | S    |
| Should   | S-43 Save a product without filling in the overhead cost           | ✅ Done 2026-10-14                 | S    |
| Should   | S-46 Name your workshop                                            | On hold since 2026-10-15          | S    |
| Should   | S-31 A section that fails to load offers "Try again"               | On hold since 2026-10-14          | S    |
| Should   | S-32 A duplicate SKU saved at once shows a clear message           | On hold since 2026-10-14          | S    |
| Could    | S-42 Screen readers hear "112 units", not "112units"               | Postponed, kept for later         | S    |
| Could    | S-40 A realistic catalogue of 100 products on a fresh install      | Postponed, kept for later         | M    |
| Could    | S-41 A real photo on every item of the demo catalogue              | Postponed, kept for later         | M    |

- S-44 commits: `672937f`, `a54856f`, `9a48cd3`. S-43 commits: `6179d52`, `4644a08`, `9fc8e98`, `9df0ca0`, close-out `eccb09a`.
- S-08 commits: `723f57a`, `932f01a`, `1b40f75`, `af3d6de`, `5241147`. S-29 commits: `d351514`, `7b0fb05`, `c70109f`, `a68043e`.
- Tech allowance: F-22 flaky form test · F-12 frontend skill wording · F-45 stale "preview" test names. They can move to Phase 5's
  allowance if the tech-lead proposes it.

**Exit criteria:** S-30 accepted by the human · S-45 accepted by the human · every gate green. The two criteria S-08 and S-29 left open
(S-08 seen green in CI on GitHub; the S-18 page-speed target measured, p95 ≤ 150 ms) moved to Phase 7's known gaps (D-13).

## NEXT — Phase 6: Fast at real catalogue sizes (was Phase 5 until 2026-10-15)
**Goal:** every page stays within its speed budget with 2 000 materials, 500 products and 50 000 sales.

| Priority | Story | Size |
|---|---|---|
| Must | S-27 Performance dataset and measured budgets (measure first; per workshop once S-44 lands) | S |
| Must | S-23 Pre-computed product figures (`product_metrics`), if S-27 shows the need | M |
| Should | S-33 Material history loads on demand, with "Load more" | S |
| Should | S-35 The product edit page opens instantly | S |
| Could | S-34 The products list shows it is loading while filters change | S |
| Could | S-36 Deleting a sale or archiving a catalogue item feels instant | S |
| Tech allowance | F-26 · F-38 · F-42 · F-49 (F-41 moved to Phase 5 on 2026-10-15) | — |

**Exit criteria:** S-27's report shows every page within budget, and the human has accepted S-33 and S-35.

## NEXT — Phase 7: Production go-live (was Phase 6 until 2026-10-15)
**Goal:** Invory runs on a production server the business can rely on: backups, health check, fast static assets.

| Priority | Story | Size |
|---|---|---|
| Must | S-26 Production runtime (needs decision D-5: database host with PostgreSQL 13+ and `pg_trgm`) | M |
| Must | Confirm the provisional decisions Q4 (bigint keys), Q6 (currency and locale in config) (Q9 was replaced by D-10 on 2026-10-15), and confirm again that sign-up stays open to anyone once the server is public (D-9) | — |
| Tech allowance | F-43 · F-46 · F-52 · F-53 | — |

The rest of Phase 4 (at least S-45 and the S-30 sign-off) is done before the server goes live (D-12). So are the known gaps left when
S-08 and S-29 were closed as they stood (D-13): the `golden-path` check seen green on GitHub, the fresh-install demo click-through, a
password-reset email seen in the test mailbox, and the page-speed target (S-18) measured.

## LATER — Phase 8: Planning features (was Phase 7 until 2026-10-15; order to be confirmed with the human)
| Candidate | Value |
|---|---|
| S-10 Production planner: "plan N units" shows each material's requirement, the stock and the shortage to buy | High: turns capacity into a shopping list |
| S-63 A bigger size uses more fabric: cost and "Can produce" per size | Medium: exact fabric for mostly-XL orders (about 11 % more) |
| S-64 A shoe size uses its own sole | Low until shoes are made in volume (your answer, 2026-10-18) |
| S-09 Live cost preview while editing the composition | Medium |
| S-11 CSV import/export of materials | Medium: onboarding a real inventory |
| S-37 Custom date range for sales and the dashboard (F-11) | Low |
| S-38 UX polish bundle (F-14, F-16, F-17, F-18, F-21) | Low |
| S-55 Find a product by typing in the order and sale pickers | Low: helps once the catalogue is large |
| S-39 "Manage units" link in the material form (F-31 q3: "not now") | Low |

## VISION — not scheduled
Suppliers and purchase orders · unit conversions in compositions (columns exist, Q2) · a SKU and a price per size, and a page to edit the size lists · sub-assemblies
(multi-level BOM) · **several people in one workshop**: inviting people, removing them, roles and permissions, and history that still
names a person after their account is deleted (e.g. "by a former member") (S-44 keeps the data with the workshop, so this adds people
without moving data) · cost history snapshots · setting stock aside for orders not yet cut · REST API / mobile. (Production that takes
materials out of stock moved to Phase 5 as S-53, D-10.)
Design notes: [ARCHITECTURE.md §10](ARCHITECTURE.md#10-scalability-path-post-mvp-designed-for-but-not-built).

## Done (Phases 1–3)
Phase 1 foundation + materials · Phase 2 UI-first build of the approved mockup · Phase 3 real backend, reference data, performance quick wins,
dashboard endpoint, rendering and loading. Details: [STATUS.md §4](STATUS.md#4-delivery-history) and the
[backlog archive](archive/backlog-2026-10-14.md).
