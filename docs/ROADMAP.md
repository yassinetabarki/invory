# Invory — product roadmap

> Owner: **product-owner** · Last updated: 2026-11-06 (INV-3 done)
> INV-10 is done (`76b1770`), so new cards can be added again. INV-2 is done (`46af1cc`, `ab313eb`, `4088200`). INV-8 is done (`9f93175` … `3b55319`). INV-4 is done, with INV-12 (accepted 2026-11-02, on `PI-DEV`). INV-5 is done (accepted 2026-11-02, on `PI-DEV`). INV-15 is done (accepted 2026-11-03, on `PI-DEV`). INV-3 is done (accepted 2026-11-06; `2575dd0` … `cbdae79`, `aeee262`). Next: INV-18, then INV-16 (on hold until INV-18 is done), then INV-17 (development card `Ready`, approved 2026-11-06; its Technical work waits for its turn), then INV-9 (D-27, D-28, D-29). Next free ID: INV-19.

## Product goal
A small fashion workshop knows, at any moment, what each product costs to make, how many it can make from the stock
on its shelves, which material will run out first, and how sales are going. It does this without a spreadsheet and
with exact numbers.

## NOW — Colour × size variants
Each product comes in fixed colours and in its sizes. Every colour-and-size variant has its own stock and barcode.
Finished products can be restocked, orders belong to clients, and one page shows how production is going.
Work goes one item at a time, in the order below. A UI card comes first, then the development card.

| ID     | Feature                                            | Status        | Goal                                       |
| ------ | -------------------------------------------------- | ------------- | ------------------------------------------ |
| INV-1  | Colours: a fixed list                              | `Done`        | Every product has colours from a list      |
| INV-10 | The PostgreSQL check runs to the end (tech)        | `Done`        | The check INV-2 needs runs to the end      |
| INV-2  | Product sizes × colours: stock per colour and size | `Done`        | Know the shelf stock of each variant       |
| INV-8  | Barcodes for every colour and size                 | `Done`        | Every variant gets its own barcode         |
| INV-4  | Clients, found by phone on New order (+ INV-12)    | `Done`        | Orders belong to a client                  |
| INV-5  | Production for the shelf: a progress page          | `Done`        | See where every batch is in production     |
| INV-3  | Restock of finished products                       | `Done`        | Add finished units to the shelf at once    |
| INV-18 | Sizes chosen per product, managed in Settings      | `Done`        | Each product has the sizes it really has   |
| INV-16 | Material use per size and colour                   | `Ready`, replan first | Each size and colour uses its own material |
| INV-17 | Per-unit QR codes, moved one by one                | `Ready`       | Follow each unit, not only its batch       |
| INV-9  | Print barcode labels                               | `Ready`       | Print a label for a variant, or a sheet    |
| INV-15 | Work tickets and the Scan page                     | `Done`        | Move a batch on by scanning its ticket     |
| INV-12 | Refactor the order, 1 of 3: client first           | `Done`        | Built within INV-4                         |
| INV-13 | Refactor the order, 2 of 3: product search, stock  | `UI approved` | See what is in stock when taking an order  |
| INV-14 | Refactor the order, 3 of 3: tracker, from shelf    | `UI approved` | See where an order stands at a glance      |
| INV-7  | Bigger tap targets on the Settings pages (Could)   | `Idea`        | Settings are easy to use on a phone        |

INV-5's UI card was reopened, rewritten and validated again on 2026-11-01: finished products are not tied to an order, so Progress
makes products for the shelf and an order is served from the finished stock. INV-12 is built within INV-4 (D-22), INV-13 comes after INV-5, INV-14 after INV-13 and INV-7 after INV-14. INV-6 is a tech-lead item (the colour picker row's swatch edge). It has no card and is not in
the order.

- D-20 (2026-10-24): the card flow is paused for this rework. Every older open story is on hold.
- D-21 (2026-10-24): the order is INV-1, INV-2, INV-3, INV-4, INV-5.
- D-29 (2026-11-06): INV-18 (sizes chosen per product, managed in Settings) came first and is done (2026-11-07); INV-16 was on hold until then. Decided with it: one workshop-wide size list, the category pre-selects sizes on Add product, existing products keep their sizes, a size is never deleted and a used size can't be taken off a product, at least one size per product, order by position in Settings.
- D-27 (2026-11-06): INV-16 comes first, ahead of INV-9 and INV-7.
- D-28 (2026-11-06): INV-17 (per-unit QR codes, alongside batch tracking; the user chooses the mode) comes after INV-16 and before INV-9. Its UI card is `Approved` and its development card is `Ready` (approved 2026-11-06, [INV-17-per-unit-qr.md](stories/INV-17-per-unit-qr.md)); it also changes INV-5's splitting: every move joins the batch of the same start already in the phase (batches already side by side are left as they are; the next move joins the lowest-numbered one).
- D-24 (2026-11-02): INV-5 comes next, ahead of INV-3's acceptance and INV-9; INV-15 (work tickets and Scan) is split from it and follows INV-9.
- D-25 (2026-11-02): INV-15 comes next, ahead of INV-3's acceptance and INV-9; its Ticket size part waits for INV-9's Settings › Labels.
- D-22 (2026-11-02): INV-4 comes next, before INV-9 and INV-3; New order uses INV-12's phone-first Client card, so INV-12 is built within INV-4.

**Exit criteria:** to be set with the card of each item.

## VISION — not scheduled
- Suppliers and purchase orders
- Unit conversions in compositions
- A SKU and a price per size, and a page to edit the size lists
- Sub-assemblies (a bill of materials with several levels)
- Several people in one workshop: invitations, roles and permissions
- Cost history snapshots
- Setting stock aside for orders not yet cut
- A REST API and a mobile app

## Done
Phases 1 to 3 and the first Phase 5 work, plus INV-1, INV-2, INV-8, INV-10, INV-4 (with INV-12), INV-5 and INV-15. See [STATUS.md §4](STATUS.md#4-delivery-history) and the
[backlog archive](archive/backlog-2026-10-14.md).
