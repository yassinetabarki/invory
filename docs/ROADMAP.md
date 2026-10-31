# Invory — product roadmap

> Owner: **product-owner** · Last updated: 2026-10-31
> INV-10 is done (`76b1770`), so new cards can be added again. INV-2 is done (`46af1cc`, `ab313eb`, `4088200`). Next is INV-8.

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
| INV-8  | Barcodes for every colour and size                 | `Ready`       | Every variant gets its own barcode         |
| INV-9  | Print barcode labels                               | `Ready`       | Print a label for a variant, or a sheet    |
| INV-3  | Restock of finished products                       | `UI approved` | Add finished units to the shelf at once    |
| INV-4  | Clients                                            | `UI approved` | Orders belong to a client                  |
| INV-5  | A production progress page                         | `UI approved` | See where every bundle is in production    |
| INV-7  | Bigger tap targets on the Settings pages (Could)   | `Idea`        | Settings are easy to use on a phone        |

INV-7 comes after INV-5. INV-6 is a tech-lead item (the colour picker row's swatch edge). It has no card and is not in
the order.

- D-20 (2026-10-24): the card flow is paused for this rework. Every older open story is on hold.
- D-21 (2026-10-24): the order is INV-1, INV-2, INV-3, INV-4, INV-5.

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
Phases 1 to 3 and the first Phase 5 work, plus INV-1. See [STATUS.md §4](STATUS.md#4-delivery-history) and the
[backlog archive](archive/backlog-2026-10-14.md).
