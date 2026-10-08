# Approved clickable mockup (2026-10-08)

Eight screens the human approved on 2026-10-08 as the visual and data reference for the UI-first track
([plan](../../plans/phase-2-ui-first-preview.md)). They are **references, not code to port**: each `*.dc.html` file is a static "Design Component"
(inline styles + a small `DCLogic` class holding sample data and toggle state). `support.js` is not included, so opening a file in a browser shows
raw template tags (`{{…}}`, `<sc-for>`). Read the markup and the data in the `<script type="text/x-dc">` block instead.

| File | Screen | Built in |
|---|---|---|
| `Main.dc.html` | Dashboard: period select, can't-produce alert, 5 KPI cards, top 5 sellers (Units / Revenue toggle), needs restock, recent sales | S-06-FE |
| `Sales.dc.html` | Sales log: totals, search / period / product filters, table with edit + delete, "Record a sale" panel | S-14-FE |
| `Materials.dc.html` | Materials list: thumbnail, "Used in" column, per-row **Restock** quick form and **History** panel | S-15-FE, S-16-FE |
| `MaterialDetail.dc.html` | Material detail: photo, KPIs, below-reorder callout, details, **What you can make with it** + suggestion | S-07-FE, S-15-FE |
| `MaterialForm.dc.html` | Add / edit material with photo upload and inline errors | S-15-FE |
| `Products.dc.html` | Products list: unit cost, can produce, bottleneck, sold (30 d), "Can't produce" filter | S-05 |
| `ProductDetail.dc.html` | Product detail: photo, can-produce hero, unit cost, sold (30 d), bottleneck callout, cost breakdown, composition, recent sales | S-05 |
| `ProductForm.dc.html` | Edit product: details, production costs, photo, composition (BOM) editor with material picker | S-05 |
| `canvas.json` | Canvas layout of the boards only | — |

Differences decided after the mockup (see the plan, "Mockup deltas"): money is shown in the installation currency (TND by default, 3 decimals),
not `€`; the product **selling price** and **margin** (decision 5) are not in the mockup and are placed by the plan; the sales period select has no
"Custom range…" yet (backlog F-11).
