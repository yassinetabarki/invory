# S-40 — Fill a fresh install with a realistic catalogue of 100 products
> Phase 4 · Priority Could (planned after S-08; slips to Phase 5 first) · Size M · Status **Ready** (approved by the human 2026-10-14; the
> tech-lead plans it after S-08) · Type enabler (demo data)
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Depends on: [S-08](S-08-golden-path-test-and-demo-data.md)
> (the demo-data step this builds on) · Followed by: [S-41](S-41-real-photos-for-the-catalogue.md) (a real photo on every item) · Related: S-27
> (speed on a much larger, development-only dataset) · Decisions: dev DB demo seed declined (2026-10-14, confirmed for this story in Q1),
> F-37 (deleted demo products stay deleted) · Technical plan: added by the tech-lead

## Story
As the workshop owner, I want to fill a new, empty Invory with a believable catalogue of about 100 products and the materials they are
made of, so that I can try every screen, filter and page the way it will look with a real workshop's data, and show it to others.

## Why it matters
The demo workshop (S-08) has 12 materials and 7 products: enough to check the maths, too small to see how lists, filters, pages of 25,
"Needs restock" and "Can't produce" behave with a real-size catalogue. The 100 products must be believable: compositions a pattern-maker
would recognise, prices in TND, and the mix of good and bad situations a workshop really has.

## How your sample maps onto Invory
Your sample is a shop catalogue; Invory is a workshop's bill of materials. They differ in four places (decided 2026-10-14, Q7: keep
Invory's shape):

| Your sample | In Invory |
|---|---|
| `composition: cotton 98%, elastane 2%` (fibre content of the garment) | Not the product's composition. A product's **composition** is the list of materials and how much of each one unit uses: for a jacket, 2.0 m of stretch denim, 9 buttons, 450 m of thread, 1 label. The fibre content goes into the **fabric material's** own "Composition" field, e.g. material "Stretch denim 11oz Indigo", composition "98% cotton, 2% elastane". |
| `brand`, `subcategory` | Not stored. Invory has no brand or sub-category field; one workshop makes its own products. Product categories are Apparel, Footwear, Bag, Accessory, Jewelry, Other. |
| Four `images` per product (front, back, model, detail) | Invory keeps **one picture** per product and per material. Real photos come with S-41; this story adds none. |
| `price: 89.99 USD` | Invory shows money in the installation currency, **TND** with 3 decimals. Each product has a labour cost, an overhead cost and an optional selling price. |

All four sample products are in the catalogue, adapted this way (the first is renamed so it isn't confused with the demo's "Denim Jacket").

## Acceptance criteria
AC-1 **One optional step makes it 100 products.** Given a new, empty database with the demo workshop loaded (S-08), when someone follows the
setup guide's optional "Large demo catalogue" step, then Products shows **100 products** (the 7 demo products plus 93 new ones) and
Materials shows the 12 demo materials plus **100 to 120** new ones. The guide states the exact figures to expect: number of materials,
inventory value, how many need a restock (low and out), how many products can't be produced, and the two worked products below.

AC-2 **The demo workshop is unchanged.** After the step, every figure that S-08 AC-6 lists for a demo product or for sales still holds:
Denim Jacket costs **TND 35.754**, can produce **112**, bottleneck **Metal shank button 17mm**, margin **59.83%**; the dashboard's "Last 30
days" still shows **TND 16,850.000** from **48 sales**, **296** units sold and the same top 5. The step adds **no sales**. Only
whole-catalogue figures change (material count, inventory value, "Needs restock", "Can't produce"), and the guide lists their new values.
No demo material's stock, cost or "Reorder at" changes.

AC-3 **Compositions a pattern-maker recognises.** Every new product with a composition has **3 to 10 lines**, each quantity in the
material's own unit and inside the reference ranges below, and the trims its type needs: every pair of jeans has a waist button, rivets
and a fly zipper; every sneaker has a pair of soles, a pair of laces and eyelets; every sewn product has thread and a brand label. Fabric
fibre content appears in the fabric's "Composition" field (e.g. "98% cotton, 2% elastane"), never as composition lines. Names are generic
product names (no real brand names). Money is in TND within the price ranges below.

AC-4 **Every situation shows up.** Among the 93 new products: at least **60** can produce 20 units or more; at least **10** can produce
between 1 and 19; at least **10** show "Can't produce" (they appear under the products list's "Can't produce" filter); **2** drafts
have no composition and show "No composition defined."; at least **5** are archived; at least **2** have a negative margin (selling
price below unit cost); the bottleneck comes from at least **6** different material categories. Among the new materials: at least **8**
are out of stock, at least **12** are low (at or below "Reorder at"), at least **10** have no "Reorder at" (so they are never flagged low),
and at least **5** are used by no product. At least **8** different units are used (e.g. m, kg, sqft, pc, pair, cone, ml, roll).

AC-5 **Same every time, safe to repeat, never on your data.** Running the step on a new database always gives the same catalogue and the
same figures (a fake-data generator may produce it, as long as the result never changes). Running it a second time creates nothing twice,
and a catalogue product you deleted stays deleted (as F-37 for the demo). The catalogue's SKUs start with **`CAT-`** (e.g. `CAT-M-001` for a
material, `CAT-P-001` for a product), so you can tell them apart; it never changes an item with another SKU. The step refuses to run on
a production installation. The guide says it is for a new, empty database only and explains how to open Invory on a second, throwaway
database. Invory's own instructions never run it on your development database `laravel`.

## Example with real numbers
**Vegan Leather Crossbody Bag** (Bag, Active, labour TND 9.000, overhead TND 2.000, selling price TND 95.000)

| Material | Unit | Unit cost | Qty / unit | Waste | Effective qty | Line cost | Stock | Units covered |
|---|---|---|---|---|---|---|---|---|
| PU vegan leather Black (polyurethane on polyester backing) | m | 14.000 | 0.60 | 12% | 0.672 | 9.408 | 40 m | 59 |
| Recycled cotton lining Natural (100% recycled cotton) | m | 5.000 | 0.50 | 5% | 0.525 | 2.625 | 120 m | 228 |
| Metal zipper 18cm (demo) | pc | 0.950 | 1 | 0% | 1 | 0.950 | 1,250 pc | 1,250 |
| Polypropylene webbing 25mm Black | m | 0.800 | 1.30 | 5% | 1.365 | 1.092 | 200 m | 146 |
| Swivel snap hook 25mm | pc | 0.650 | 2 | 0% | 2 | 1.300 | 90 pc | **45** |
| Polyester thread (demo) | m | 0.0040 | 60 | 5% | 63 | 0.252 | 20,000 m | 317 |
| Woven brand label (demo) | pc | 0.120 | 1 | 0% | 1 | 0.120 | 400 pc | 400 |

Material cost TND 15.747, plus labour TND 9.000 and overhead TND 2.000: unit cost **TND 26.747**. Can produce **45**, bottleneck
**Swivel snap hook 25mm**; restock it and the next limit is PU vegan leather Black at **59**. Margin TND 95.000 − TND 26.747 =
**TND 68.253 · 71.85%**.

**Active Breathable Leggings** (Apparel, Active, labour TND 7.000, overhead TND 1.500, selling price TND 120.000): recycled nylon jersey
Black ("82% recycled nylon, 18% spandex", TND 18.000 per m, 1.10 m + 8% waste, **0 m in stock**), wide elastic 40mm (TND 1.000 per m,
0.70 m + 3% waste, 60 m in stock), polyester thread 150 m + 5% waste, 1 woven brand label. Unit cost **TND 31.355**, margin **TND 88.645 ·
73.87%**, and the page shows **"Can't produce"** with the jersey as the bottleneck; restock it and the next limit is wide elastic 40mm at
**83**.

### Reference ranges (from garment-costing sources, rounded; the step stays inside them)
| Product type | Main material per unit (before waste) | Typical trims and thread |
|---|---|---|
| T-shirt, polo | 1.1–1.3 m jersey or piqué (or 0.20–0.30 kg) | 100–150 m thread; polo: 3 buttons |
| Woven shirt, blouse | 1.5–1.8 m | 7–10 buttons, 0.3 m interlining, 150–200 m thread |
| Jeans, chinos | 1.3–1.6 m | 1 shank button, 5–6 rivets (jeans), 1 zipper, 200–250 m thread |
| Hoodie, sweatshirt | 1.6–1.9 m fleece + 0.2–0.3 m rib | drawcord 1.5 m, 2 eyelets, 200–250 m thread |
| Jacket | 1.8–2.4 m (+ 1.5 m lining if lined) | 6–10 buttons or 1 zipper, 300–450 m thread |
| Coat | 2.5–3.2 m + 2 m lining | 4–8 buttons, interlining 1 m, 400–500 m thread |
| Dress, skirt | dress 2.0–3.0 m, skirt 0.8–1.5 m | 1 zipper, 150–250 m thread |
| Sneaker, loafer, derby (pair, mid size EU 40–42) | 2–3 sqft leather or 0.5–0.7 m canvas | 1 pair soles, 1 pair insoles, 1 pair laces, 12–16 eyelets |
| Ankle boot (pair) | 3–5 sqft leather | 1 pair soles, 1 pair insoles, zipper or elastic |
| Handbag, tote | 5–16 sqft leather (by size) or 0.5–1.5 m canvas / PU | lining 0.5–1 m, zipper, rings and hooks |
| Belt | 1.0–1.4 sqft leather | 1 buckle, 2–4 rivets |

Waste: fabrics 3–12%, leather 10–30%, trims and hardware 0%.

### Price ranges (TND, in line with the demo workshop's 12 materials)
Fabric 4–35 per m (jersey 4–6, denim 8–12, linen 12–18, wool 25–35) · knit fabric sold by weight 25–60 per kg · lining 3–7 per m ·
interlining 1.5–4 per m · leather 4–15 per sqft · PU / vegan leather 12–20 per m · thread 0.002–0.010 per m · trims 0.30–3 per m ·
buttons 0.05–0.80 per pc · zippers 0.60–3.50 per pc · labels 0.05–0.30 per pc · soles and insoles 4–18 per pair · hardware 0.08–2.50 per
pc · packaging 0.20–4 per pc · labour 2–45 per product · overhead 0.50–10 per product. Most selling prices are 2 to 4 times the unit cost.

### Product mix (93 new products)
- **Apparel 51:** T-shirts 5 · polos 3 · woven shirts 5 · blouses 3 · hoodies and sweatshirts 5 · sweaters and knitwear 4 (incl. Merino Wool
  Blend Sweater) · jeans 4 · chinos and trousers 5 · shorts 3 · skirts 3 · dresses 3 · jackets 4 (incl. Stretch Denim Jacket) · coat 1 ·
  activewear 3 (incl. Active Breathable Leggings)
- **Footwear 14:** sneakers 4 · loafers 2 · derbies 2 · ankle boots 2 · sandals 2 · espadrilles 2
- **Bag 14:** totes 3 · crossbody bags 3 (incl. Vegan Leather Crossbody Bag) · backpacks 2 · clutches 2 · canvas shoppers 2 · duffels 2
- **Accessory 9:** belts 3 · wallets and card holders 3 · scarves 2 · cap 1
- **Jewelry 5:** leather bracelets 2 · brass necklaces 2 · earrings 1

With the 7 demo products: 55 Apparel, 15 Footwear, 15 Bag, 10 Accessory, 5 Jewelry = **100**. Status: about 85 Active, 10 Draft, 5 Archived.
New materials cover every material category (about 34 fabric, 6 lining, 4 interlining, 12 leather, 6 thread, 12 trim, 14 fastener, 5 label,
8 sole, 6 hardware, 4 packaging, 3 other). The new products also use the demo's thread, labels, zipper, rivets and buttons where a real
workshop would.

## What you will see (UX)
- No screen changes. On a database with the catalogue: Materials has 5 to 6 pages of 25, Products 4 pages, and the dashboard's
  "Needs restock" and "Can't produce" panels are well filled.
- **Pictures:** this story adds none. Every row shows today's placeholder (the same one a material or product without a photo shows now)
  until S-41 adds a real photo to every item. Product-owner's call (2026-10-14): a temporary generated picture would be thrown away by
  S-41, and the placeholder already handles a missing photo.
- A material's "What you can make with it" lists many products for shared materials such as Polyester thread.
- The setup guide gets one clearly labelled optional step, "Large demo catalogue", right after the demo-data step, with the figures to
  expect (AC-1), the two worked products, and how to open Invory on a second, throwaway database.

## Out of scope
- Pictures: S-41 (real photos from free photo libraries, one per item).
- Loading the catalogue into your development database `laravel` (confirmed 2026-10-14, Q1).
- Sales for the new products (Q4); the 60 demo sales stay the only sales.
- Brand, sub-category and several pictures per product: not stored, and not added to the Vision (Q7).
- A button or command to remove the catalogue again: start from a new database instead.
- Sizes: each product's composition is for one middle size (M, EU 40–42). Sizes and variants stay in the Vision.
- Speed measurement at large sizes: S-27 (2 000 materials, 500 products, 50 000 sales, development only).
- Real supplier names or real brand names.

## Questions for you (answered 2026-10-14)
Q1 Where may the catalogue be loaded? **Answer: (a)** only on a new, empty database; your `laravel` data stays as it is, and the guide
explains how to open Invory on a second, throwaway database. (Other option was: also in `laravel`, next to your own rows.)

Q2 How does it sit next to the demo workshop? **Answer: (a)** an optional add-on loaded after the demo workshop: 7 demo + 93 new = 100
products. (Other options were: stand-alone without the demo workshop; replace the demo workshop.)

Q3 What kind of pictures? **Answer: (b)** real photos from free photo libraries (Unsplash, Pexels), one per item, stored with the app. As
the card said, this became its own story: **S-41**. This story adds no pictures. (Other options were: generated illustrations; no
pictures for now.)

Q4 Sales for the new products? **Answer: (a)** none; the dashboard's sales figures don't change. (Other option was: about 300 sales over
12 months.)

Q5 Same data every run, or different each time? **Answer: (a)** the same catalogue and figures every time; a fake-data generator is fine if
the result never changes. (Other option was: random data each run.)

Q6 When? **Answer: (a)** Phase 4 as a Could, planned after S-08; it slips to Phase 5 first. (Other options were: Phase 5 Should; Phase 4
Should ahead of S-31 and S-32.)

Q7 Keep Invory's shape? **Answer: (a)** keep it: no brand or sub-category, one picture per item, fibre content in the fabric's
"Composition" field; nothing added to the Vision. (Other option was: also add Brand, Sub-category and several pictures to the Vision.)

## For the tech-lead's sizing
- Whether the catalogue is written out or generated with a fixed seed is your call; AC-5 only needs the same result every run.
- How the human opens Invory on a second, throwaway local database with today's setup (`php artisan serve`, PostgreSQL in Docker), and how
  the guide explains it.
- The step runs after S-08's demo step (and its "only when there are no sales" rule); how the two steps are ordered and documented.
- S-41 will add a photo to every catalogue item through the same step; leave room for it.
- Whether S-27's large speed dataset could reuse this catalogue's compositions (not required here).

## Where the reference figures come from
Fabric per garment: [onlineclothingstudy.com, fabric consumption](https://onlineclothingstudy.com/2020/04/how-do-we-calculate-fabric-consumption.html),
[T-shirt and polo](https://onlineclothingstudy.com/how-much-fabric-is-required-making-t/) · Thread per garment (denim jacket about 450 m):
[thread consumption sheet](https://www.scribd.com/document/633992222/Thread-Consumption), [Coats, denim jackets](https://coats.com/industries/apparel/applications/denim-jackets) ·
Leather per pair and per bag: [ova.it.com](https://ova.it.com/footwear-materials/how-much-leather-is-used-in-a-shoe),
[Chicago School of Shoemaking](https://chicagoschoolofshoemaking.com/?p=12505), [Montana Leather, bag leather](https://montanaleather.com/product/sacchetto-bag-leather) ·
Shoe components (eyelets, laces, insoles): [leather shoe specification](https://pubsec.gov.lk/wp-content/uploads/2024/12/Specification-Leather-Shoes.pdf).
No public source gives current TND prices per metre; the price ranges follow the demo workshop's 12 materials.

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 One optional step, 100 products | | |
| AC-2 Demo workshop unchanged, no sales added | | |
| AC-3 Realistic compositions | | |
| AC-4 Every situation shows up | | |
| AC-5 Same every time, safe, never on your data | | |
