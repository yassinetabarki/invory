---
id: INV-4
title: Clients as an entity
type: ui
priority: Must
status: Approved
created: 2026-10-30
tags:
  - ui-card
---

# INV-4 · Clients as an entity — UI card

> [!abstract] Description
> Each order belongs to a **client**: a person or a shop with a name, a phone, a city and, if known, the **source** that brought them
> (Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop, Word of mouth, Other). Invory's workshops sell mostly to
> individuals who find them on social media and order by message, so a client is usually a person ("Amira Ben Salah"), reached by
> phone, and the owner wants to know which network brought her.
> New: the **Clients** page (in the sidebar) with a Source filter, the Add client window, the client page with its orders, and on New
> order the switch **Existing client · New client** with the new client's fields in the order form. Changed: the Client field of Edit
> order (a picker), a client line on the order page, and a Client filter on the Orders list.
> Left out on purpose: editing, removing or merging clients, an Instagram name or handle on the client, clients by source on the
> dashboard, progress (INV-5), invoicing, addresses beyond the city, client accounts and logins, and sales (a sale still has no
> client).

> [!info] How it looks today
> The client of an order is a name typed on each order. New order and Edit order have, in the Details card, "Client" (a text field,
> placeholder "e.g. Boutique Lina"), "Order date" and "Note (optional)", with the errors "The client field is required." and "The
> client field must not be greater than 100 characters.". The owner types the name again for every order, and a typo makes a second
> client.
> The order page's title is the typed name, with "Ordered 15 Oct 2026 · 2 products · 80 units" under it and the breadcrumb "Orders ›
> Boutique Lina". The Orders list has the column "Client" (the name, a link to the order, the note under it), the search "Search
> client, product or note" and the Status list ("Open orders", "Shipped", "All orders"). "Delete order?" names the client: "The order
> of **Boutique Lina** (2 products, 80 units) will be removed. …".
> There is no list of clients, and no phone, city or source anywhere. When Amira messages "where is my jacket?", the owner searches
> the Orders list for "amira" and hopes she typed it the same way.

## Pages

Every example uses these clients:

| Client           | Source         | City    | Phone      | Orders | Open orders | Last order  |
| ---------------- | -------------- | ------- | ---------- | ------ | ----------- | ----------- |
| Amira Ben Salah  | Instagram      | Sousse  | 22 345 678 | 3      | 1           | 28 Oct 2026 |
| Boutique Lina    | Walk-in / shop | Tunis   | 71 234 567 | 3      | 1           | 15 Oct 2026 |
| Mehdi Jaziri     | WhatsApp       | Ariana  | 29 111 222 | 2      | 0           | 12 Oct 2026 |
| Nour Gharbi      | TikTok         | Sfax    | 55 123 456 | 1      | 0           | 20 Oct 2026 |
| Rania Hamdi      | Website        | Bizerte | 50 987 654 | 1      | 0           | 9 Oct 2026  |
| Yassine Trabelsi | Facebook       | Tunis   | 98 765 432 | 1      | 1           | 27 Oct 2026 |

- Boutique Lina is the one shop.
- Amira's open order of 28 Oct 2026: 1 × Denim Jacket (M) and 1 × Denim Jeans 5-pocket, 2 units, "Not started".

### How clients work
- A client has a **Name** (required, up to 100 characters), a **Phone** (required), a **City** (required, up to 60 characters) and a
  **Source** (optional).
- **Phone:** a Tunisian number of 8 digits, typed with or without +216 or 00216, spaces, dots or dashes, is shown as "22 345 678"
  ("22345678", "+216 22 345 678" and "22.345.678" all become "22 345 678"). A number abroad is accepted when it starts with + and its
  country code (8 to 15 digits), and is shown as typed with single spaces.
- **City:** free text, which suggests the cities the workshop's clients already have ("Tunis", "Sousse", "Bizerte").
- **Source:** a fixed list, the same for every workshop, in this order: Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in /
  shop, Word of mouth, Other. "Other" takes an optional short text, up to 40 characters ("Fashion fair Sousse").
- **No two clients share a phone.** A name already used is allowed (two buyers can both be "Mohamed Ben Ali"), but a note offers the
  existing client.
- A client is created with **Add client** (Clients page, Edit order) or as a **New client** in New order. A client can't be edited,
  removed or archived; deleting an order never removes its client.
- Orders recorded before INV-4 are test data and are not turned into clients.

### The source label (everywhere)
- A small rounded grey label, the same tone for every source, with a small icon and the word: [camera] Instagram, [thumbs-up]
  Facebook, [music] TikTok, [message] WhatsApp, [globe] Website, [store] Walk-in / shop, [speech] Word of mouth, [dots] Other. No brand
  logos or brand colours. "Other" with a text reads "Other · Fashion fair Sousse".
- A client with no source: no label; the Clients table shows "—", read "No source" by screen readers, and the other screens leave it
  out.
- Screen readers skip the icon and read the word.

### Sidebar
- A new entry **Clients** with a users icon, after Orders: Dashboard, Materials, Products, Restocks, Orders, **Clients**, Sales.
  Breadcrumbs: "Clients", "Clients › Amira Ben Salah".

### Clients page
Board: INV-4 · Clients list and client page · source, default, empty, missing details, errors · desktop (Invory App UI canvas)
- Header **Clients**, description "The people and shops who order from you, and where they found you. Each order belongs to one
  client.", main button **Add client** with a plus sign.
- Filters: the search "Search name, phone or city" and **Source** ("All sources", then the eight sources). Both are kept in the page
  address. 25 a page, sorted by name A → Z.
- Table: **Client** (the name, bold, a link to its page) · **Source** (the label; "—") · **City** ("Sousse") · **Phone** (number font,
  "22 345 678") · **Open orders** (number font, right-aligned) · **Last order** ("28 Oct 2026"; "No orders yet").

### Add client window
Board: INV-4 · Clients list and client page · source, default, empty, missing details, errors · desktop (Invory App UI canvas)
- Opened from the Clients page and from Edit order. Title **Add client**.
- **Name**, with the hint "The person or the shop, as you call them."
- **Phone**, in the number font, with the hint "8 digits, such as 22 345 678. For a number abroad, start with + and the country
  code." On a phone it opens the number keypad.
- **City**, suggesting the cities already used.
- **Source (optional)**: the eight chips (see "How the source is picked"), hint "Where did this client find you?". **Other** adds the
  field **Other source (optional)**, hint "In a few words, such as a fashion fair.".
- Buttons **Cancel** and **Save client**. **Save client** shows a spinner while saving; on an error the window stays open with what
  was typed. **Save client** → toast "Client created.", and the client shows in the list in its place.

### How the source is picked
- A row of chips, one choice, each with its icon and word, wrapping; nothing chosen at first, and nothing chosen is fine. The chosen
  chip shows a check in place of its icon, a 2 px Denim border and Denim text; a second tap on it un-chooses it. Screen readers hear
  the chips as one group named "Source".

### Client page
Board: INV-4 · Clients list and client page · source, default, empty, missing details, errors · desktop (Invory App UI canvas)
- Breadcrumb "Clients › Amira Ben Salah". Header **Amira Ben Salah**; under it "[Instagram] · [map pin] Sousse · [phone] 22 345 678",
  the phone a call link named "Call Amira Ben Salah, 22 345 678". A client with no source: the line starts with the city.
- Button **New order** (main), named "New order for Amira Ben Salah", which opens New order on Existing client with her chosen.
- Three figures: **Open orders** "1" · **Orders** "3" · **Last order** "28 Oct 2026".
- Section **Orders** with the Status list, "All orders" by default here: today's Orders table without the Client column, 25 a page,
  newest first. A click on an order date opens the order.

| Order date  | Products                                    | Ordered | To start | In cutting | Ready to ship | Shipped | Status      |
| ----------- | ------------------------------------------- | ------- | -------- | ---------- | ------------- | ------- | ----------- |
| 28 Oct 2026 | 1 × Denim Jacket · 1 × Denim Jeans 5-pocket | 2       | 2        | 0          | 0             | 0       | Not started |
| 14 Oct 2026 | 1 × Leather Tote                            | 1       | 0        | 0          | 0             | 1       | Shipped     |
| 1 Sep 2026  | 2 × Classic White T-shirt                   | 2       | 0        | 0          | 0             | 2       | Shipped     |

### New order · the Client part of the Details card
Board: INV-4 · New order · existing client or new client inline, errors · desktop (Invory App UI canvas)
At the top of the Details card, before Order date and Note:
1. Label **Client**, then a two-way switch, one choice, 44 px: **Existing client** · **New client**. It starts on Existing client, or
   on New client when the workshop has no clients yet.
2. **Existing client:** the picker button **Choose a client…** (as the product picker). The search "Search clients" (name, phone
   digits or city); the options A → Z, each the name and, in grey, "Instagram · Sousse · 22 345 678". The last option is always there,
   with a plus icon: "+ Add "amira" as a new client" (or "+ New client" when nothing is typed). It switches to New client with Name
   filled in and the focus in Phone; when the search was digits ("22 345"), they fill Phone instead and the focus goes to Name. Once a
   client is chosen, under the button: "Instagram · Sousse · 22 345 678", so the owner sees she picked the right person.
3. **New client:** a light grey box headed "New client" holding, on a laptop in two columns, **Name** · **Phone**; **City**; then
   **Source (optional)**, full width (the eight chips; **Other** adds "Other source (optional)"). Under the box: "Saved to your clients
   when you save the order."
4. **Already a client?** When the focus leaves Phone and the number belongs to a client, an information note under the field: "22 345
   678 is the phone of Amira Ben Salah (Instagram · Sousse)." · **Choose Amira Ben Salah**. When the name matches a client (ignoring
   capitals), a note under Name: "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." · **Choose Amira Ben
   Salah**. Choosing switches to Existing client with her chosen; the order's products, sizes and note stay. The name note never blocks
   saving; the phone one does, on save.
5. Switching between Existing client and New client keeps what was typed in each until Save order; only the one showing is saved.
- **Save order** saves the order and, in New client, the client together, or nothing. Toast: "Order recorded. Amira Ben Salah is now
  one of your clients." (an existing client: today's "Order recorded."). The Orders list opens, as today.
- **From a client page:** New order opens on Existing client with her chosen; the focus starts on Order date.
- Nothing else in the form changes: Order date, Note, Products, the sizes.

### Edit order · the Client field
Board: INV-4 · New order · existing client or new client inline, errors · desktop (Invory App UI canvas)
- The picker only, with no switch, and the order's client chosen. Another client can be chosen at any stage of the order. Its last
  option "+ Add "…" as a new client" opens the Add client window over the order with Name filled in; **Save client** → "Client
  created.", the window closes and the new client is chosen. A closed or cancelled order has no Edit, so its client can't change there.
- **Save order** → "Order updated.", as today.

### Order page
Board: INV-4 · Order page, Orders list and source labels · light and dark · desktop (Invory App UI canvas)
- The title and the breadcrumb are the client's name, as today.
- Under "Ordered 28 Oct 2026 · 2 products · 2 units": "[Instagram] · Sousse · 22 345 678 · **All orders of Amira Ben Salah**" (a link
  to the client page; the phone a call link). A client with no source: "Sousse · 22 345 678 · **All orders of …**".
- The Delete order window and its refusal keep naming the client.

### Orders list
Board: INV-4 · Order page, Orders list and source labels · light and dark · desktop (Invory App UI canvas)
- Filters: the search (as today) · **Status** (as today) · **Client** (new): a list with its own search, "All clients" first; its
  options show the phone in grey, so two "Mohamed Ben Ali" can be told apart. All are kept in the page address, and they combine.
- The Client column is unchanged. The four totals above the list still count every open order, whatever is filtered.
- No match: today's message and **Clear filters**, which also clears Client.

## States

### Clients page
- **Default:** the six example clients.
- **Empty:** "No clients yet" · "Your clients appear here when you record their first order, or add one now." · **Add client**.
- **No match:** "No client matches "Amina"." · "Check the spelling, or search by phone or city." · **Clear filters**.
- **Source filter:** Source "Instagram" lists Amira Ben Salah; nothing: "No client came from TikTok yet." · **Clear filters**.
- **Loading:** the header, filters and table head at once; 5 placeholder rows.
- **Error:** "Couldn't load the clients." · **Try again**.
- **Long content:** a 100-character name, a 60-character city and "Other · " with 40 characters are cut with "…" in the table, the full
  text on the client page.

### Client fields (Add client window, and New client in New order)
Errors under their field after saving, with the focus on the first one:
- Name empty: "Enter the client's name." · too long: "Keep the name to 100 characters or fewer."
- Phone empty: "Enter the client's phone." · not a number Invory knows: "Enter 8 digits, such as 22 345 678, or a number abroad
  starting with +." · already used: "22 345 678 is already the phone of Amira Ben Salah." with **Choose Amira Ben Salah instead** (New
  order, Edit order) or **Open Amira Ben Salah** (Clients page).
- City empty: "Enter the client's city." · too long: "Keep the city to 60 characters or fewer."
- Other source too long: "Keep it to 40 characters or fewer."
- Name already used: never an error; the note "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." with **Choose
  Amira Ben Salah** (in the Clients page window: **Open Amira Ben Salah**).
- Save failed: "Couldn't save the client. Try again." (in New order: today's order message). Everything typed stays.

### New order · Client part
- **Existing, nothing yet:** the switch on **Existing client**, **Choose a client…**.
- **Existing, chosen:** "Amira Ben Salah", under it "Instagram · Sousse · 22 345 678".
- **Picker search:** "amira" → Amira Ben Salah; the last option "+ Add "amira" as a new client".
- **Picker, no match:** "No client matches "Ines Mrad"." and "+ Add "Ines Mrad" as a new client".
- **Picker error:** "Couldn't load the clients. Try again." · **Try again**.
- **New client, empty:** Name, Phone, City, the Source chips with none chosen; "Saved to your clients when you save the order."
- **New client, filled:** Ines Mrad · 20 456 789 · Monastir · Instagram.
- **Phone already a client:** the note "22 345 678 is the phone of Amira Ben Salah (Instagram · Sousse)." · **Choose Amira Ben Salah**.
- **Name already a client:** the note "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." · **Choose Amira Ben
  Salah**.
- **Checking:** nothing blocks typing; the note appears when the check answers.
- **Nothing chosen on save:** Existing client with no one chosen: "Choose a client." under the picker.
- **Errors on save:** the field messages above; an order error (a line, a size) also saves no client; everything typed stays.
- **Saving:** **Save order** shows a spinner and keeps its label.
- **Success:** "Order recorded. Ines Mrad is now one of your clients."; with an existing client: "Order recorded.".
- **No clients yet:** the switch starts on **New client**; Existing client is still there, its picker reading "No clients yet.".

### Client page
- **Default:** Amira Ben Salah as in Pages.
- **No orders:** "0" · "0" · "No orders yet"; the Orders section: "No orders yet" · "Record an order for Ines Mrad to follow it
  here." · **New order**.
- **Status with no match:** "Amira Ben Salah has no shipped orders." · **Show all orders**.
- **Loading:** the header and figures at once, 3 placeholder rows. **Error:** "Couldn't load the orders of Amira Ben Salah." · **Try
  again**.
- **Read-only:** the whole page: a client can't be edited.

### Order page and Orders list
- **Order page:** "[Instagram] · Sousse · 22 345 678 · All orders of Amira Ben Salah".
- **Orders list, Client "Amira Ben Salah":** her open order of 28 Oct 2026; the totals unchanged; no match: today's message and
  **Clear filters**.

## Wording
- Sidebar and breadcrumbs: "Clients" · "Clients › Amira Ben Salah".
- Sources: "Instagram" · "Facebook" · "TikTok" · "WhatsApp" · "Website" · "Walk-in / shop" · "Word of mouth" · "Other" · "Other ·
  Fashion fair Sousse" · the filter's "All sources".
- Clients page: "Clients" · "The people and shops who order from you, and where they found you. Each order belongs to one client." ·
  "Add client" · "Search name, phone or city" · "Source" · "All sources" · column names "Client", "Source", "City", "Phone", "Open
  orders", "Last order" · "No orders yet" · "No clients yet" · "Your clients appear here when you record their first order, or add one
  now." · "No client matches "Amina"." · "Check the spelling, or search by phone or city." · "No client came from TikTok yet." · "Clear
  filters" · "Couldn't load the clients." · "Try again".
- Client fields: "Name" · "The person or the shop, as you call them." · "Phone" · "8 digits, such as 22 345 678. For a number abroad,
  start with + and the country code." · "City" · "Source (optional)" · "Where did this client find you?" · "Other source (optional)" ·
  "In a few words, such as a fashion fair." · "Cancel" · "Save client" · "Add client" · "Enter the client's name." · "Keep the name to
  100 characters or fewer." · "Enter the client's phone." · "Enter 8 digits, such as 22 345 678, or a number abroad starting with +." ·
  "22 345 678 is already the phone of Amira Ben Salah." · "Choose Amira Ben Salah instead" · "Open Amira Ben Salah" · "Enter the
  client's city." · "Keep the city to 60 characters or fewer." · "Keep it to 40 characters or fewer." · "Couldn't save the client. Try
  again."
- New order: "Client" · "Existing client" · "New client" · "Choose a client…" · "Search clients" · "+ New client" · "+ Add "Ines Mrad"
  as a new client" · "No client matches "Ines Mrad"." · "No clients yet." · "Saved to your clients when you save the order." · "22 345
  678 is the phone of Amira Ben Salah (Instagram · Sousse)." · "You already have a client named Amira Ben Salah (Sousse · 22 345
  678)." · "Choose Amira Ben Salah" · "Choose a client." · on a phone, "Choose a client" (the panel's title) and "Close".
- Client page: "Call Amira Ben Salah, 22 345 678" · "New order" · "New order for Amira Ben Salah" · "Open orders" · "Orders" · "Last
  order" · "All orders" · "No orders yet" · "Record an order for Ines Mrad to follow it here." · "Amira Ben Salah has no shipped
  orders." · "Show all orders" · "Couldn't load the orders of Amira Ben Salah." · on a phone "Call 22 345 678".
- Order page: "All orders of Amira Ben Salah". Orders list: "Client" · "All clients".
- Toasts: "Client created." · "Order recorded. Ines Mrad is now one of your clients." · "Order recorded." · "Order updated."
- Gone: "e.g. Boutique Lina", "The client field is required.", "The client field must not be greater than 100 characters.".
- The "+" in an option is a plus icon.

## Phone layout
Board: INV-4 · Clients, client page, New order with a new client · 375 (Invory App UI canvas)
- **New order:** the switch full width (two halves, 44 px). Existing client: the picker full width, its list a panel over the whole
  screen titled "Choose a client", with the search on top and "+ New client" fixed at the bottom. New client: Name, Phone, City, then
  the Source chips two or three to a row (44 px), one under another, in the grey box; the "already a client" note under its field.
  **Save order** in the bar fixed at the bottom, as in today's forms; the bar never hides the field with the focus.
- **Clients:** the header and **Add client** full width, the search, **Filters** ("Filters · 1" when a source is chosen; a panel with
  Source). One card per client: the name (a link), the source label, "Sousse · 22 345 678", "1 open order · last 28 Oct 2026".
- **Add client:** a panel over the whole screen, the fields one under another, **Save client** in a bar fixed at the bottom. The phone
  field's text is 16 px, so the phone doesn't zoom.
- **Client page:** the title, the source label and city, a full-width **Call 22 345 678** button (44 px), **New order** full width,
  the figures two to a row, her orders as today's order cards without the name.
- **Order page:** the client line wraps: the source label and city, then the phone, then "All orders of Amira Ben Salah".
- **Orders list:** the search, then **Filters** with Status and Client.
- At 320 px nothing scrolls sideways; the chips and the client line wrap.
