# INV-12 · Refactor the order — design spec
Status: proposal · **Partly superseded on 2026-11-01**: the client part (INV-12) and the product-search part (INV-13) stand; the
order tracker, Mark delivered, Cancel units, the Orders list and the delete refusal are replaced by [INV-14-order-tracker.md](INV-14-order-tracker.md) (an order is
covered from the shelf; production fills it, see [INV-5-production-for-the-shelf.md](INV-5-production-for-the-shelf.md)) · Designer · 2026-11-01 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc),
row "Refactor the order (INV-12)" (canvas version 68):
- "INV-12 · New order · client first, phone finds the client, new or existing, errors · desktop" (`project/Inv12Client.dc.html`)
- "INV-12 · New order · product search with stock per colour and size, quantity grid, states · desktop" (`project/Inv12Products.dc.html`)
- "INV-12 · Order page · order tracker, breakdown by colour and size, Mark delivered, Cancel units, states · light and dark · desktop" (`project/Inv12OrderPage.dc.html`)
- "INV-12 · Orders list · the tracker's words, totals, status filter, states · desktop" (`project/Inv12OrdersList.dc.html`)
- "INV-12 · New order, order page, Cancel units, Orders list · 375" (`project/Inv12Phone.dc.html`)

> **What this builds on.** INV-4 (clients: Name, Phone, City required, Source optional; a phone belongs to one client) and INV-5
> (Progress: every phase, cutting, tickets) ship first, as approved. INV-2 and INV-3 keep the finished stock per colour and size that
> the product search shows. The product-owner's eleven recommendations (2026-11-01, not yet confirmed by the human) are the design basis; the
> table "Where the design depends on an answer" says where.
>
> **Two things must be settled before this can be built** (questions 1 and 2): INV-5's open discussion "orders per colour and size"
> (an order line has no colour today; points 2 and 4 of the request need one), and whether the last step is called **Delivered** or
> **Shipped**.
>
> **Where this replaces what is already approved** (the product-owner decides how to word it in the cards; the boards of INV-4 and
> INV-5 are not redrawn):
> - INV-4, New order: the switch "Existing client · New client", the picker "Choose a client…" and "+ Add … as a new client" are
>   replaced by the phone-first client form below. INV-4's picker stays in Edit order and becomes the helper "Find a client by name".
>   Its note "22 345 678 is already the phone of Amira Ben Salah." (a blocking error) becomes the automatic match.
> - INV-5, order page: the columns per phase, **Start cutting**, **Move to …**, **Mark ready**, **Ship**, the three **Move back**, and
>   **Tickets (2)** leave the order page. **See these units in Progress** stays. INV-5's Orders list ("To start · In production ·
>   Ready to ship · Shipped") takes the tracker's words. INV-5's "Orders per colour and size — to be discussed" grid is used as
>   drawn, for New order and Edit order.
> - INV-4 client page: its orders table takes the same words.

## Problem and user
The workshop owner takes orders on a **phone**, often while answering an Instagram or WhatsApp message, and follows them on a
**laptop** at the cutting table. Three questions:
1. **Taking the order:** "Who is this, and is she already a client?" then "Do we have this jacket in Indigo M, or must we make it?"
2. **Following the order:** "Where is Boutique Lina's order: how many units are still waiting, being made, ready to go, delivered or
   cancelled, and in which colour and size?"
3. **Doing the work:** cutting and the production phases belong to Progress, one place, not to every order.

## How it looks today
- **New order** (one Details card): "Client" is a typed name (INV-4 turns it into a switch and a picker), then "Order date", "Note
  (optional)". Below, "Products": each line is a product picker and a quantity per size ("50 × Denim Jacket (S 10 · M 20 · L 15 · XL
  5)"); the picker lists every product with no stock information.
- **Order page:** under the title, a status and "0 of 80 shipped"; for each product four boxes or a table with the columns To start ·
  In cutting · Ready to ship · Shipped, and buttons Start cutting, Mark ready, Ship and three Move back, each opening a window with
  the materials of a cut. INV-5 adds columns per production phase and a Tickets link.
- **Orders list:** per order To start · In cutting · Ready to ship · Shipped and a status; at the top "Open orders 3 · To start …".
- No screen says whether a product is on the shelf when the order is taken; the owner opens the product page, then comes back.

## Example data used everywhere
Today is 30 Oct 2026. **Boutique Lina**'s order of 15 Oct 2026 (Tunis, 71 234 567, Walk-in / shop), 80 units. The boards show it after
the owner cancelled the 5 Jeans XL.

| Product · colour · size          | Not started | In progress | Ready to ship | Delivered | Cancelled | Total |
| -------------------------------- | ----------- | ----------- | ------------- | --------- | --------- | ----- |
| Denim Jacket · Indigo S          | 0           | 4           | 6             | 0         | 0         | 10    |
| Denim Jacket · Indigo M          | 6           | 14          | 0             | 0         | 0         | 20    |
| Denim Jacket · Indigo L          | 15          | 0           | 0             | 0         | 0         | 15    |
| Denim Jacket · Indigo XL         | 0           | 0           | 0             | 5         | 0         | 5     |
| **Denim Jacket**                 | 21          | 18          | 6             | 5         | 0         | 50    |
| Denim Jeans 5-pocket · Indigo S  | 0           | 5           | 0             | 0         | 0         | 5     |
| Denim Jeans 5-pocket · Indigo M  | 10          | 0           | 0             | 0         | 0         | 10    |
| Denim Jeans 5-pocket · Indigo L  | 10          | 0           | 0             | 0         | 0         | 10    |
| Denim Jeans 5-pocket · Indigo XL | 0           | 0           | 0             | 0         | 5         | 5     |
| **Denim Jeans 5-pocket**         | 20          | 5           | 0             | 0         | 5         | 30    |
| **Boutique Lina**                | 41          | 23          | 6             | 5         | 5         | 80    |

(In progress is INV-5's In cutting + Sewing + Finishing: Jacket Indigo S 4 sewing, M 4 cutting + 6 sewing + 4 finishing; Jeans S 5
sewing.) The Orders list's three open orders: Amira Ben Salah (28 Oct, 2 units, Not started 2), Yassine Trabelsi (27 Oct, 3 units, In
progress 3) and Boutique Lina → totals **Open orders 3 · Not started 43 · In progress 26 · Ready to ship 6**. Two more for "All
orders": Mehdi Jaziri (Leather Tote 12, Delivered) and Nour Gharbi (Classic White T-shirt 2, Cancelled).

Stock on the shelf (INV-2/INV-3), used in the search: Denim Jacket Black 1 · 0 · 2 · 1, Indigo 2 · 0 · 3 · 1, Light wash 0 · 0 · 0 · 0
(S · M · L · XL; "In stock 10 units", "Can produce now 112"); Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0, Black 1 · 2 · 0 · 0 ("In
stock 10 units", "Can produce now 64").

## Flow
1. **New order opens on the client.** She types the phone number she has just received in the message: 22 345 678. When she leaves
   the field, Invory finds **Amira Ben Salah** (Instagram · Sousse) and shows her as the **Existing client**; Name, City and Source
   aren't asked again. A number Invory doesn't know says **New client**; she types Name, City and, if she knows it, the Source.
2. **Products.** She types "den" in **Search products**. Two results appear, each with the stock of every colour and size: the Jacket
   has Indigo M "Out", the Jeans have Indigo M 5. She asks **Add to order** on the Jacket. The focus lands in its first quantity
   field. Under each field she sees "In stock 2" or "Out of stock". She types Indigo S 4 and Indigo M 8 (out of stock, so it will be
   made), adds the Jeans, types 10 units, and sees "Total on this order 22 units · 2 products".
3. **Details and Save.** Order date and Note come last. **Save order** records the order, and the client first when the number was
   new: "Order recorded. Ines Mrad is now one of your clients." (an existing client: "Order recorded.").
4. **The order page** says where the order stands. The **Order tracker** shows Not started 41 · In progress 23 · Ready to ship 6 ·
   Delivered 5 · Cancelled 5 · Total 80, with a bar; under it each product has a table by colour and size.
5. **Delivering.** When the parcel leaves, she presses **Mark delivered** under the Jacket; the window "Mark delivered · Denim Jacket"
   asks how many of Indigo S (up to 6). Toast: "6 units of Denim Jacket marked delivered."
6. **Cancelling.** Boutique Lina drops 5 Jeans: **Cancel units** → "Cancel units · Denim Jeans 5-pocket" → Indigo L 5 → **Cancel 5
   units**. The units stay on the order as Cancelled.
7. **Cutting, sewing, tickets, Move back** are on Progress only (**See these units in Progress**).

## Options and recommendation

### A · The client part of New order
- (a) **Phone first, one form, automatic match — recommended.** Phone, Name, City, Source in the Client card. Leaving the phone field
  looks the number up (digits only): known → the **Existing client** summary replaces Name, City and Source; new → **New client**,
  the fields stay. A small **Find a client by name** button opens INV-4's picker for a client whose number she doesn't have. Why
  phone first: the phone is the identity (b2c: buyers share names); typing it first means Name and City are never typed twice for a
  regular client.
- (b) Keep INV-4's switch "Existing client · New client" and add the phone rule behind it. Two taps before typing, and the owner
  must guess which side she is on.
- (c) Name first, suggestions as she types. Matches how she thinks of clients, but common names ("Mohamed Ben Ali") suggest the wrong
  person; the phone is the only exact key.
- *Phone known but the typed details differ* (the product-owner's question 1): if she typed a Name or City before the phone, the
  Existing client summary shows and a note says what happened: "You typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben
  Salah (Sousse), so the order goes to her. Her saved details stay as they are." **Use another number** clears the phone.
- *Name known, another number:* INV-4's note stays ("You already have a client named Amira Ben Salah (Sousse · 22 345 678)." ·
  **Choose Amira Ben Salah**); it never blocks saving.

### B · The product search
- (a) **One search; each result shows the stock of every colour and size; Add to order puts the product on the order with a
  quantity grid whose cells repeat the stock — recommended.** The question "do you have it in Indigo M?" is answered before
  anything is added; the grid keeps the answer next to the number being typed.
- (b) The same search, a one-line result (name, SKU, total in stock); stock only in the grid after Add. Shorter list, but the owner
  must add a product to learn it is out of stock, and remove it again.
- (c) A search that lists variants ("Denim Jacket · Indigo · M — In stock 0") one per row. Direct, but a jacket becomes 12 rows and
  the owner can't compare sizes.
- The search covers name, SKU, a variant SKU ("PRD-JKT-001-IND-M"), a colour name and a barcode number (INV-8), so a label read to
  her in a message finds the product. Up to 6 results; "Showing 6 of 14. Keep typing to narrow the list."
- *Availability* (the product-owner's questions 9 and 10): "In stock N" when the shelf holds more than 0 of that colour and size,
  otherwise "Out of stock"; in the compact result table the cell reads the number or "Out". Out of stock never blocks the order, and
  no warning appears when the typed number is higher than the stock. "Can produce now 112 from materials" is **per product** (every
  colour and size uses the same materials, so it can't be split per colour); it is the small grey second line of a result and of the
  line on the order.

### C · The order tracker
- (a) **A card with a status, "5 of 80 units delivered · 5 cancelled", a thin bar and six tiles (Not started · In progress · Ready to
  ship · Delivered · Cancelled · Total), then one table per product by colour and size — recommended.** The five counts add up to the
  Total, as the product-owner proposed (question 6), so nothing is hidden.
- (b) Only the table per product, no order-level card. Less on screen, but "how is Boutique Lina's order?" needs adding up.
- (c) A stepper (Not started → In progress → Ready to ship → Delivered). It reads as a path; the tracker is a count, and Cancelled
  doesn't sit on the path.
- Each tile carries a word, an icon and a tone: Not started (neutral, dashed circle), In progress (info, clock), Ready to ship
  (warning, package), Delivered (success, check circle), Cancelled (dashed border, "ban" icon), Total (plain). The bar's segments
  follow the counts; Cancelled is hatched, so no step is told apart by colour alone. The bar is decorative: the tiles hold the
  numbers.

### D · What stays on the order page (questions 4, 5 and 6)
- (a) **Two buttons per product, Mark delivered and Cancel units, and a "⋯" menu with "Move delivered back to Ready to ship" —
  recommended.** INV-5 itself said shipping stays on the order page "because a parcel is made per client". Cancel units only
  concerns units **not started** (no stock effect, no undo). Everything about cutting, phases and tickets is on Progress.
- (b) No buttons on the order page: Progress does all moves. Matches "cutting only on Progress" literally, but delivering a parcel
  takes the owner to another page and back.
- (c) Cancel the whole order only (S-57). Coarser than the request ("cancelled" per colour and size).

## Layout

### New order (desktop, one column, about 960 px wide, same page as today)
Board: INV-12 · New order · client first … and INV-12 · New order · product search …
1. Header **New order**.
2. **Client** card (heading, one line: "A phone number belongs to one client. If it is already one of your clients, the order goes to
   them." and, on the right, the outline button **Find a client by name** with a search icon). A grey box "Client details":
   **Phone** (number font; hint "8 digits, such as 22 345 678. For a number abroad, start with + and the country code.") and **Name**
   (hint "The person or the shop, as you call them.") side by side; **City**; **Source (optional)** with INV-4's eight chips ("Where
   did this client find you?"). Under the box: "The client is saved to your clients when you save the order, if the phone number is
   new." (short form after a lookup: "Saved to your clients when you save the order.").
   - **Existing client:** the box is replaced by one summary block with a 2 px Denim border: the name (bold), the pill "Existing
     client", the source label, city and phone, "3 orders · 1 open · last order 28 Oct 2026", and the outline button **Use another
     number** (clears Phone, shows the form again, the focus in Phone). Under it: "The order uses the details saved for this client.
     Name, City and Source aren't asked again."
   - **New client:** under Phone, a green note with a user-plus icon: "New client. 20 456 789 isn't one of your clients yet."
3. **Products** card, heading and "Search what the client wants, check what is in stock, then add it."
   - **Search products** (visible label; placeholder "Name, SKU, colour or barcode"; a search icon; a clear button named "Clear
     search"). Hint: "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of stock can
     still be ordered."
   - **Results** (only while there is a search): "2 products found", then one block per product: a small photo, the name (bold), the
     SKU in the number font, a grey line "In stock 10 units · Can produce now 112 from materials", the outline button **Add to
     order** (named "Add Denim Jacket to this order"), and a table "Stock of Denim Jacket by colour and size": one row per colour
     (swatch and name, in Settings › Colours order), one column per size; each cell the number (number font) or an amber label
     "Out". A product already on the order shows **On this order** with a check; it takes the focus to that line.
   - **On this order** (heading with "· 2 products"): one block per product: the name, SKU, "Can produce now 112 from materials", a
     **Remove** button (named "Remove Denim Jacket from this order"), the table "Quantity per colour and size · Denim Jacket" (INV-5's
     name): rows are colours, columns are sizes and **Total**; **each cell is a quantity field (44 px high) with its stock under it**
     in 12 px: "In stock 2" in grey or "Out of stock" in amber and bold. A "One size" product: columns Colour · Quantity. Under the
     table "Total 12 units". After the products: a grey band "Total on this order · 22 units · 2 products".
4. **Details** card: **Order date** and **Note (optional)** as today.
5. **Save order** (main) and **Cancel**, with "22 units · 2 products" beside them.
- **Edit order:** the same Products part and the same grid. A cell whose units have left Not started, or were cancelled, shows "At
  least 10" under the field, after the stock line. The Client field is INV-4's picker, unchanged.

### Order page (desktop)
Board: INV-12 · Order page · order tracker …
1. Breadcrumb "Orders › Boutique Lina"; header and INV-4's client line, **Edit** and **Delete** as today.
2. **Order tracker** card: the heading, the status badge (**In progress**), "**5 of 80** units delivered · 5 cancelled" and, on the
   right, the outline button **See these units in Progress** (INV-5's link, kept). The bar. The six tiles in one row: name with icon
   (13 px), count in the number font (28 px), "units". A grey line explains the two unclear words: "In progress: cut or being made.
   Ready to ship: finished for this order, waiting to go. Delivered and Cancelled units stay in the total."
3. **Products**: one card per product: the name (a link to the product), SKU, "50 units", its status badge and "5 of 50 delivered";
   the table "Denim Jacket by colour and size": first column **Colour · size**, then **Not started · In progress · Ready to ship ·
   Delivered · Cancelled · Total**; a colour row (swatch, name, subtotals, bold, on a light band), its sizes indented, a **Total** row
   with a heavier line above; every figure in the number font, right-aligned, zeros in the readable grey. "One size": colour rows
   only. Under the table the row of buttons: **Mark delivered** (main when units are ready, otherwise grey with its reason in text
   beside it), **Cancel units** (outline, red text), and the "⋯" button named "More actions for Denim Jacket".
4. The figures never link anywhere. Everything comes from the server.

### Mark delivered and Cancel units (windows)
Board: INV-12 · Order page … section 3
- Both have INV-5's move-window shape: title with the product ("Mark delivered · Denim Jacket", "Cancel units · Denim Jeans
  5-pocket"), a grey line "Boutique Lina · ready to ship: Indigo S 6" (or "not started: Indigo M 10 · L 10"), the group "How many
  units of each colour and size?" with one row per colour that has units in the step and one field per size that has them, "Up to 6"
  under each, and the field of the first row has the focus.
- **Mark delivered** (main). The "⋯" entry "Move delivered back to Ready to ship" opens the same window titled "Move back to Ready to
  ship · Denim Jacket".
- **Cancel units**: an amber note "Cancelled units stay on the order, marked Cancelled, and are not made. This can't be undone. Only
  units not started can be cancelled." and the buttons **Keep units** (outline) and **Cancel 5 units** (red; the number follows what
  is typed). The window is the confirmation: the consequence is in it, with the real number.

### Orders list (desktop)
Board: INV-12 · Orders list …
- Header as today with the outline link **See Progress** (INV-5) beside **New order**.
- Four totals: **Open orders** 3 · **Not started** 43 units · **In progress** 26 units · **Ready to ship** 6 units, still counting
  every open order whatever the filters (S-50).
- Filters: the search (as today), **Status** ("Open orders", "Delivered", "Cancelled", "All orders") and **Client** (INV-4).
- Table: **Client** (name, a link; the order date in grey under it) · **Products** · **Ordered** (the order's total; "5 cancelled" in
  grey under it when there are any) · **Not started** · **In progress** · **Ready to ship** · **Delivered** · **Status** (badge:
  Not started, In progress, Delivered, Cancelled, each with its icon). The old columns To start, In cutting (In production), Shipped
  are gone.

## States (every screen)

| Screen · state          | What the screen shows |
| ----------------------- | --------------------------------------------------------------------------------------------------------------- |
| Client · empty          | Phone, Name, City, the eight Source chips, nothing chosen; the hint about the phone number |
| Client · checking       | Under Phone: spinner and "Checking 22 345 678…"; nothing blocks typing |
| Client · new            | Green note "New client. 20 456 789 isn't one of your clients yet."; the fields stay |
| Client · existing       | The summary block with **Use another number**; "The order uses the details saved for this client. Name, City and Source aren't asked again." |
| Client · details differ | The summary plus the amber note "You typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben Salah (Sousse), so the order goes to her. Her saved details stay as they are." |
| Client · name known     | INV-4's info note and **Choose Amira Ben Salah**; never blocks |
| Client · lookup failed  | Amber note under Phone: "Couldn't check this number. Save the order and Invory checks it again." · **Try again**; saving still works (the server decides on save) |
| Client · errors on save | Under their field, focus on the first: "Enter the client's phone." · "Enter 8 digits, such as 22 345 678, or a number abroad starting with +." · "Enter the client's name." · "Keep the name to 100 characters or fewer." · "Enter the client's city." · "Keep the city to 60 characters or fewer." · "Keep it to 40 characters or fewer." (Other source); at the top "Check the client: 3 fields can't be saved." Source needs nothing |
| Client picker (Find a client by name) | INV-4's: "Search clients"; no match "No client matches "Ines Mrad"."; none yet "No clients yet."; error "Couldn't load the clients. Try again." · **Try again** |
| Search · just opened    | Only the box and its hint; no list |
| Search · results        | "2 products found" and the blocks; more than 6: "Showing 6 of 14. Keep typing to narrow the list." |
| Search · no match       | "No product matches "denm xyz"." · "Check the spelling, or search by SKU, colour or barcode." |
| Search · loading        | The box stays; a placeholder block (photo, two lines, a table) and "Searching…" |
| Search · error          | "Couldn't search the products." · **Try again**; the lines already on the order stay |
| Search · already added  | **On this order** (check) in place of Add to order |
| Search · disabled       | With 50 products: **Add to order** can't be pressed; beside it "An order can hold up to 50 products." (read by the keyboard) |
| Lines · empty           | "No products on this order yet" · "Search for a product above and add what the client ordered." |
| Lines · typed           | "Total 12 units" per product; the band "Total on this order 22 units · 2 products"; the button area "22 units · 2 products" |
| Lines · errors          | "Indigo M: Enter a whole number from 0 to 1,000,000." · "Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order." · "A line can hold up to 1,000,000 units." · "The colours or sizes of Denim Jacket have changed. Enter its quantities again." · with no product: "Add at least one product to this order." |
| Edit order · started    | "At least 10" under a cell; "Indigo M can't be less than the 10 units already in progress, ready to ship, delivered or cancelled." |
| Save · saving           | **Save order** shows a spinner and keeps its label |
| Save · success          | New client: "Order recorded. Ines Mrad is now one of your clients."; existing: "Order recorded."; the Orders list opens |
| Save · failed           | "Couldn't save the order. Try again." Everything typed stays; a wrong line saves no client either |
| Tracker · default       | As in Layout |
| Tracker · all delivered | Status **Delivered**, "12 of 12 units delivered", bar all green; **Mark delivered** can't be pressed: "Nothing is ready to ship."; **Cancel units**: "Nothing to cancel: every unit has started or been delivered." |
| Tracker · all cancelled | Status **Cancelled**, "0 of 2 units delivered · 2 cancelled", bar all hatched; both buttons can't be pressed with their reasons |
| Tracker · loading       | The header and the card frame at once; a bar and six tile placeholders |
| Tracker · error         | "Couldn't load the tracker of this order." · **Try again** |
| Tracker · read-only     | No unit left to act on: the two buttons can't be pressed (reasons above); Edit and Delete as today |
| Mark delivered · window | Fields for the sizes with ready units; saving: spinner, label kept; "Enter at least one unit to mark delivered." · "You can mark at most 6 units of Indigo S." · "Indigo S: Enter a whole number of units." · units moved meanwhile: "These units have moved on since you opened this page." · **Close** |
| Mark delivered · success| "6 units of Denim Jacket marked delivered." (one: "1 unit of Denim Jacket marked delivered."); back: "5 units of Denim Jacket moved back to Ready to ship." |
| Cancel units · window   | "Enter at least one unit to cancel." · "You can cancel at most 10 units of Indigo L." · "Indigo L: Enter a whole number of units." |
| Cancel units · success  | "5 units of Denim Jeans 5-pocket cancelled." (one: "1 unit of … cancelled.") |
| Delete order · refused  | "Boutique Lina can't be deleted" · "29 units are in progress or ready to ship. Move them back to Not started on the Progress page first." · **Close** · **Open Progress** |
| Delete order · confirm  | As today ("Delete order?", **Delete order**, **Keep order**, toast "Order deleted."), naming the client |
| Orders · empty          | "No orders yet" · "Record a client's order to keep track of what you've promised to make." · **New order** (as today) |
| Orders · none open      | "No open orders" · "Every order has been delivered or cancelled." · **Show all orders** |
| Orders · no match       | "No orders match your filters" · "Try a different client, product or note, or clear the filters to see your open orders." · **Clear filters** (as today) |
| Orders · loading/error  | Header, totals and filters at once, 5 placeholder rows; "Couldn't load the orders." · **Try again** |
| Long content            | A 60-character product name wraps in the result and the card header; a 100-character client name is cut with "…" in the list, in full on the order; 12 colours make 12 rows; 11 shoe sizes scroll inside their table (a labelled region), never the page; 7-digit counts fit the tiles; 50 products on an order |

## Wording
- **Words that change everywhere** (Orders list, order page, client page, Progress, windows, toasts): To start → **Not started** ·
  In cutting, Sewing, Finishing, In production → **In progress** · Ready to ship (same) · Shipped → **Delivered** (question 2) ·
  new **Cancelled**. Verbs: **Mark delivered**, **Cancel units**, **Keep units**. The same verb runs through each flow: **Mark
  delivered** → "Mark delivered · Denim Jacket" → **Mark delivered** → "6 units of Denim Jacket marked delivered."; **Cancel units** →
  "Cancel units · Denim Jeans 5-pocket" → **Cancel 5 units** → "5 units of Denim Jeans 5-pocket cancelled."
- New order: "Client" · "A phone number belongs to one client. If it is already one of your clients, the order goes to them." ·
  "Find a client by name" · "Phone" · "Name" · "City" · "Source (optional)" · "Existing client" · "Use another number" · "New client.
  20 456 789 isn't one of your clients yet." · "The order uses the details saved for this client. Name, City and Source aren't asked
  again." · "The client is saved to your clients when you save the order, if the phone number is new." · "Checking 22 345 678…" ·
  "Couldn't check this number. Save the order and Invory checks it again." · the notes and errors in States.
- Products: "Products" · "Search what the client wants, check what is in stock, then add it." · "Search products" · "Name, SKU, colour
  or barcode" · "Clear search" · "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of
  stock can still be ordered." · "2 products found" · "In stock 10 units" · "Can produce now 112 from materials" · "Add to order" ·
  "On this order" · "Out" · "In stock 2" · "Out of stock" · "Quantity per colour and size" · "Total 12 units" · "Total on this
  order" · "Remove" · "At least 10" · "Details" · "Order date" · "Note (optional)" · "Save order" · "Cancel".
- Order page: "Order tracker" · "5 of 80 units delivered · 5 cancelled" · "See these units in Progress" · "Not started" · "In
  progress" · "Ready to ship" · "Delivered" · "Cancelled" · "Total" · "units" · "units ordered" · "In progress: cut or being made.
  Ready to ship: finished for this order, waiting to go. Delivered and Cancelled units stay in the total." · "Colour · size" ·
  "Mark delivered" · "Cancel units" · "More actions for Denim Jacket" · "Move delivered back to Ready to ship" · "Nothing is ready
  to ship yet. Units come here from the Progress page." · "Cancelled units stay on the order, marked Cancelled, and are not made.
  This can't be undone. Only units not started can be cancelled." · "How many units of each colour and size?" · "Up to 6".
- Orders list: "Open orders" · "Not started" · "In progress" · "Ready to ship" · "Delivered" · "Ordered" · "5 cancelled" · "See
  Progress" · Status list "Open orders", "Delivered", "Cancelled", "All orders".
- Gone from the order page: "Start cutting", "Mark ready", "Ship", "Move back" (three), "Move to Sewing", "Tickets (2)", the stage
  boxes, "To start", "In cutting".

## Accessibility
- **Contrast (1.4.3, 1.4.11).** Light: success `#0B5C4B` on `#E6F4EF` 7.0:1; warning `#8A4405` on `#FDF0DC` 6.4:1 (and on white 7.2:1,
  the "Out of stock" caption); info `#2B3A86` on `#EEF0FA` 9.0:1; neutral `#3F3F46` on `#F0F0EC` 9.1:1; danger `#A3221A` on `#FDE8E6`
  6.4:1; grey text `#5F5F66` on white 6.3:1 (zeros, captions, hints), on `#F0F0EC` 5.5:1; field border `#91918B` on white 3.2:1.
  Dark: success 8.2:1, warning 8.1:1, info 7.8:1, neutral 9.7:1, danger 8.0:1, grey text 6.8:1.
- **Colour is never alone (1.4.1).** Every count has its word; each step has an icon; Cancelled is a dashed outline with the "ban"
  icon and a hatched bar segment; "Out of stock" is words, bold, amber; the bar is hidden from screen readers because the tiles
  say the same.
- **Tiles and tables (1.3.1).** The tiles are a list named "Units of this order by step", each read "Not started, 41 units". The
  breakdown is a real table with header cells (column and row), a caption "Denim Jacket by colour and size". Search results are
  a list of groups named by the product; each **Add to order** is a Tab stop named "Add Denim Jacket to this order" (the boards'
  list roles are shorthand). The stock table is named "Stock of Denim Jacket by colour and size"; a cell reads "Indigo, M: out of
  stock" or "Indigo, M: 3 in stock".
- **Fields (3.3.2, 1.3.1).** Each quantity field is named "Indigo M quantity" and described by its stock line ("In stock 2" / "Out
  of stock"), so it is spoken with the field. Phone uses the number keypad and autofill "tel"; Name "name"; City "address-level2";
  16 px text, so the phone doesn't zoom.
- **Announcements (4.1.3).** Result counts, "Checking…", the client notes and every error are polite live messages tied to their
  field; the focus moves to the first wrong field after a failed save.
- **Focus (2.4.3, 2.4.7, 2.4.11).** Add to order → focus in the first quantity field of the new line; Remove → the neighbour line or
  the search; **Use another number** → Phone; windows trap focus, Esc closes, focus returns to the button that opened them; the
  bar fixed at the bottom of a phone never hides the focused field.
- **Disabled buttons (never silent).** **Mark delivered**, **Cancel units** and **Add to order** say why in text beside them; the
  button stays reachable with the keyboard and its description includes the reason.
- **Targets (2.5.8, 44 px).** Every field, chip, button and summary action is at least 44 px high on a phone; the 36 px small
  buttons of a laptop are 44 px on a phone.
- **Redundant entry (3.3.7).** An existing client's details are never asked again; "Find a client by name" avoids retyping a number.
- **Reflow (1.4.10).** At 320 px nothing scrolls the page sideways. The wide tables (11 shoe sizes) scroll inside a labelled region
  on a laptop; on a phone they become groups (see below).
- **Motion (2.3.3).** No animation beyond the defaults.

## Phone (375 px)
Board: INV-12 · New order, order page, Cancel units, Orders list · 375
- **New order:** the Client card first: **Find a client by name** full width, then Phone, Name, City and the Source chips two to a
  row (44 px), one under another in the box; the lookup notes under the field. **Save order** in the bar fixed at the bottom (with
  "22 units · 2 products" above it once there are products). An existing client: the summary block, **Use another number** full
  width.
- **Search results:** each result is a box: the name and SKU, "In stock 10 units · Can produce now 112 from materials", then one
  group per colour (swatch and name) with a chip per size: "S 1", "M" and the amber "Out", "L 2"; a colour with nothing in any size
  reads "Out of stock in every size"; **Add to order** full width (44 px).
- **Quantities:** per colour a band (swatch, name, the colour's total), under it the sizes **three to a row**: the size above, the 44
  px field, "In stock 2" or "Out of stock" under it. **Remove** is a 44 px icon button named "Remove Denim Jacket from this order".
- **Order page:** the title, the client line stacked (source label and city, then the phone, then "All orders of Boutique Lina"),
  **Edit** and **Delete** side by side. The tracker: status and "5 of 80 units delivered · 5 cancelled", the bar, **Total** first and
  full width, then the five counts two to a row; **See these units in Progress** full width. Each product: a band per colour and
  one line per size with only the steps that hold units ("M · Not started 6 · In progress 14 · Total 20"), the Total line first;
  **Mark delivered**, **Cancel units** and **More** full width, one under another (44 px). The reason of a disabled button shows
  under it.
- **Windows:** full screen; the fields three to a row; **Cancel 5 units** and **Keep units** (or **Mark delivered** and **Cancel**)
  in the bar fixed at the bottom.
- **Orders list:** the totals two to a row, **See Progress**, the search, **Filters** ("Filters · 1" when set) opening a full-height
  panel with Status and Client, then one card per order: name, status badge, date, "5 cancelled", the products, a small bar and
  "Not started 41 · In progress 23 · Ready to ship 6 · Delivered 5".

## Where the design depends on an answer
| Product-owner recommendation (its question)    | If it changes |
| ---------------------------------------------- | ------------- |
| 1 · Existing client unchanged, with a note     | The note "You typed … so the order goes to her" goes; the typed details would replace or be refused instead |
| 2 · Phone matching by digits only              | The lookup text and hint; "22345678", "+216 22 345 678" and "22.345.678" all find Amira |
| 3 · In progress = past Not started, not ready  | The tracker's second tile and the Orders list "In progress" column; the Progress phases stay finer |
| 4 · Ready to ship = finished for this order    | If shelf stock were reserved, the search would show "Available" (stock minus orders) and the server would send it |
| 5 · Cancelled per unit, by colour and size     | The Cancelled column and the Cancel units window; whole-order cancel (S-57) would be one button |
| 6 · Fifth count "Not started"                  | Without it, the tiles wouldn't add up and a "Total" tile would be misleading |
| 7 · Old boxes and buttons replaced             | Mark delivered and Cancel units are the only two buttons that remain on the order page |
| 8 · Units in cutting count as In progress      | Orders recorded before INV-12 read correctly with no conversion |
| 9 · Available = shelf stock above 0            | The cell label and the "Out" cell; "Can produce" stays a product-level second line |
| 10 · Out of stock can still be ordered         | No block, no warning; only the amber words |
| 11 · Split into 2 or 3 cards                   | See the proposed split below |

## For the product-owner
Proposed split (question 11), in this order, each with its own UI card so the human validates screen by screen:
1. **INV-12a · Client first on New order.** Needs only INV-4. Client card, phone match, notes, errors, toasts. Independent of the rest.
2. **INV-12b · Product search with stock.** Needs INV-2/INV-3 (stock) and **orders per colour and size** (question 1). The search,
   the results with stock, the quantity grid with stock under each cell, Edit order's grid.
3. **INV-12c · Order tracker and no cutting on orders.** Needs INV-5 (Progress holds cutting) and the colour × size lines. The tracker,
   the breakdown, Mark delivered, Cancel units, the new words on the Orders list and the client page, the delete refusal, the removal
   of the old boxes, buttons and tickets from the order page.

Proposed acceptance wording for the development cards (to be folded into the UI cards first):
- Opening New order shows **Client** first, with **Phone** at the top. Typing 22345678 and leaving the field shows "Amira Ben Salah"
  with "Existing client" and no Name, City or Source field. Saving with the Denim Jacket ordered shows "Order recorded." and no new
  client.
- Typing 20 456 789, "Ines Mrad", "Monastir", choosing Instagram and saving shows "Order recorded. Ines Mrad is now one of your
  clients." and the Clients page lists her with Instagram.
- Typing "Amina B." and "Tunis", then 22 345 678, shows Amira Ben Salah and the note "You typed "Amina B." in Tunis. 22 345 678
  belongs to Amira Ben Salah (Sousse), so the order goes to her. Her saved details stay as they are."; saving changes nothing about her.
- Typing "den" in Search products shows Denim Jacket and Denim Jeans 5-pocket, each with the stock of every colour and size; the Jacket
  shows Indigo M "Out", the Jeans Indigo M 5.
- **Add to order** on the Jacket adds a line whose Indigo M field shows "Out of stock" under it; typing 8 and saving works.
- The order of Boutique Lina reads Not started 41 · In progress 23 · Ready to ship 6 · Delivered 5 · Cancelled 5 · Total 80, and its
  page has no Start cutting, Mark ready, Ship, Move back, phase columns or Tickets.
- **Mark delivered** on the Jacket with Indigo S 6 shows "6 units of Denim Jacket marked delivered." and the tracker reads
  Ready to ship 0 · Delivered 11. **Cancel units** on the Jeans with Indigo L 5 shows "5 units of Denim Jeans 5-pocket cancelled."
  and Not started 36 · Cancelled 10.
- Deleting Boutique Lina is refused: "29 units are in progress or ready to ship. Move them back to Not started on the Progress page
  first."
- The Orders list totals read "Open orders 3 · Not started 43 · In progress 26 · Ready to ship 6".
- Rules to word in plain language: a client is found by the digits of its phone; a known phone uses the saved client unchanged; the
  five counts always add up to the Total; "In progress" is everything started that is neither ready to ship nor delivered; only Not
  started units can be cancelled; a cancelled unit changes no stock; an order is Cancelled when every unit is cancelled and
  Delivered when nothing is left open and at least one unit was delivered.
- Things to check in other cards: **S-65** (close a delivered order) and **S-57** (cancel an order, on hold) both talk about
  "shipped" and about cutting; both need rewording against the tracker before they resume. **S-68** (tap targets on the order
  screens) is met by this design (44 px everywhere on a phone) and can be folded in. INV-5's tiles and "Open orders" figures must not
  count Cancelled units.

## For the tech-lead
- **Values the server sends.**
  - *Client lookup:* from the digits of a phone, the client or "none": name, source (code, label, "Other" text), city, phone shown,
    orders count, open orders count, last order date. Also the INV-4 search for "Find a client by name". The save finds or creates the
    client by the same digits in one write with the order, whatever the lookup said.
  - *Product search:* from a text (name, SKU, variant SKU, colour name, barcode number), up to 6 products: id, name, SKU, photo, status,
    in-stock total, "can produce now", and for every colour and size of the product its shelf stock. The order form also needs, for
    each chosen product, its colours and sizes with the stock, so the grid cells can say "In stock 2" / "Out of stock".
  - *Tracker:* per order, per line and per colour × size: not started, in progress, ready to ship, delivered, cancelled, total; the
    order's status code and label (Not started, In progress, Delivered, Cancelled); delivered and total units. The bar uses the
    counts as weights, so no percentages are needed.
  - *Orders list:* the same five counts and total per order; the open-orders totals in the new words; the status filter options.
  - *Writes:* Mark delivered and its move back (the units of INV-5's Ship), Cancel units (Not started only, per colour and size), the
    order save with a client by phone.
- **New components.** None from shadcn that are not installed: search field, toggle group (chips), table, dialog and sheet, dropdown
  menu, badge, skeleton, spinner. New shared pieces: the tracker tiles and bar, the stock table, the stock caption under a field
  (maybe one for INV-3's "now 2" as well), the client summary block.
- **Costly or risky.**
  - The grid and the tracker need order lines per colour and size (INV-5's open discussion); without it INV-12b and INV-12c can't start.
  - "In progress" re-groups INV-5's phases, and Cancelled units must leave Progress's counts and tiles.
  - The product search with stock per colour and size can be many queries per result; one query for the six products is wanted.
  - The phone digits must be normalised the same way in INV-4 (unique), the lookup and the save.
  - Every screen and test that reads the stage words (`to_start`, `in_cutting`, `shipped`) or shows the old buttons and boxes.
  - The whole-unit totals shown while typing ("Total 12 units", "22 units") are sums of the typed whole numbers, as INV-3's restock
    and today's size totals already are.
- **Rule changes the human decides** (listed in BACKLOG): removing "Start cutting" and "Move back to Not started" from orders (they move
  to Progress, INV-5); the delete refusal keeps its meaning, reworded.

## Questions for the product-owner / human
1. **Orders per colour and size (INV-5's open discussion; blocks INV-12b and INV-12c).** (a) Decide it as INV-5 drew it, before
   INV-12 is built: a line is a grid of colour × size **recommended** · (b) INV-12b includes it, one bigger card · (c) lines stay per
   size and colour is only shown as stock (the tracker and the search then lose "by colour"). Default if unanswered: (a).
2. **The last step's word.** (a) **Delivered** everywhere: tracker, Orders list, status filter, windows, and INV-5's Progress tile
   "Shipped" and button "Ship" when INV-12c is built **recommended** · (b) Delivered on the order only, Progress keeps "Shipped" (two
   words for one fact) · (c) keep "Shipped" and drop the human's "delivered". Default: (a).
3. **The client part of New order.** (a) Phone first with automatic match, plus "Find a client by name" **recommended** · (b) INV-4's
   switch and picker, with the phone rule behind it · (c) Name first with suggestions. Default: (a).
4. **What can be cancelled.** (a) Only Not started units, no undo; a mistake is corrected by raising the quantity in Edit order
   **recommended** · (b) also units in progress or ready to ship (S-57's rule: ready units go on the shelf, others are finished for the
   shelf), which needs INV-5's "production for the shelf" · (c) whole order only. Default: (a).
5. **Where Mark delivered lives.** (a) On the order page and on Progress, one window **recommended** (INV-5 says shipping stays on the
   order page) · (b) Progress only; the order page is read-only. Default: (a).
6. **Tickets on the order page.** (a) Removed, printed from Progress only **recommended** · (b) kept as a link. Default: (a).
7. **Stock in the search.** (a) The full colour × size stock in every result, repeated under each quantity field **recommended** · (b)
   only under the quantity fields. Default: (a).
8. **Order of the cards on New order.** (a) Client → Products → Details (date, note) **recommended** · (b) Client and Details first,
   then Products. Default: (a).
9. **Order statuses and the Orders list filter.** (a) Not started, In progress, Delivered, Cancelled; filter "Open orders · Delivered
   · Cancelled · All orders" **recommended** · (b) also "Ready to ship" as a status when everything left is ready. Default: (a).
10. **Delete order.** (a) Keep the refusal while units are in progress or ready to ship (materials taken for a cut would be lost),
   reworded and sending the owner to Progress **recommended** · (b) allow it and return the materials (changes S-53's rule). Default: (a).
