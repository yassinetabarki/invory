# INV-4 · Clients — design spec
Status: proposal, revision 3 with the decisions of 2026-10-30 written in (revision 2: the client's source, a new client inside New
order, B2C examples) · Designer · 2026-10-30 · Boards: [Invory App UI canvas](https://claude.ai/artifact/RJenVL3s6DDEg75AmdZ4zc),
row "Clients (INV-4)" (drawn for revision 2):
- "INV-4 · Clients list and client page · source, default, empty, missing details, errors · desktop" (`project/Inv4Clients.dc.html`)
- "INV-4 · New order · existing client or new client inline, errors · desktop" (`project/Inv4OrderForm.dc.html`)
- "INV-4 · Order page, Orders list and source labels · light and dark · desktop" (`project/Inv4OrderShowList.dc.html`)
- "INV-4 · Clients, client page, New order with a new client · 375" (`project/Inv4Phone.dc.html`)

> **Decisions of 2026-10-30** (the human; the product-owner for the last three): Name, Phone and City are required, Source is
> optional · a phone belongs to one client only, a shared name only shows a note · orders recorded before INV-4 are dev data and may be
> dropped, so nothing is converted · no Edit client in INV-4 · no delete or archive · a fixed source list, "Other" with an optional
> text, no social-media name field · the New order switch starts on Existing client (New client when the workshop has none) · grey
> source labels · the "already a client" note appears when the field is left.

> **Where the boards differ from this spec** (they were drawn before the decisions and are not redrawn; this spec wins):
> - "Clients list and client page": the **Edit** buttons in the table and the **Edit client** button on the client page; the Edit
>   window (section 4), "Edit Sarra K." and "Not recorded yet" (section 5), the toast "Client updated."; the row Sarra K., "Not
>   recorded" and the Source filter's "Not recorded" option; the callout "1 client has no phone or source yet. …" with **Show them**,
>   the **Show** filter and "Every client has a phone and a source."; section 11 ("No phone or source yet." and **Add details**);
>   "City (optional)" and Mehdi Jaziri without a city; "Source" without "(optional)" and the error "Choose where this client found
>   you.".
> - "New order": "City (optional)", "Source" without "(optional)", and the error "Choose where this client found you.".
> - "Order page, Orders list and source labels": section 2 (Sarra K., "No phone or source yet") and "Not recorded" among the labels.
> - "375": the **Edit** buttons on the client cards, **Edit client** on the client page, the callout, and "City (optional)".

Builds on the approved UI cards of INV-1 (the Settings › Colours list-and-window pattern, "never removed", "Colour" spelling), INV-2
and INV-3 (a new sidebar page, the "See the restocks of Denim Jacket" link, the phone rules). The order screens are today's (S-48,
S-49, S-50, S-60, S-61, S-65): nothing about lines, sizes or stages changes.

**Invory's workshops sell mostly to individuals (B2C)** who find them on Instagram, Facebook or TikTok and order by message; shops and
boutiques are a minority. So a client is usually a person ("Amira Ben Salah"), reached by phone or WhatsApp, delivered by courier, and
the owner wants to know which social network brought her.

Example data used everywhere:

| Client           | Source         | City    | Phone      | Orders | Open orders | Last order  |
| ---------------- | -------------- | ------- | ---------- | ------ | ----------- | ----------- |
| Amira Ben Salah  | Instagram      | Sousse  | 22 345 678 | 3      | 1           | 28 Oct 2026 |
| Boutique Lina    | Walk-in / shop | Tunis   | 71 234 567 | 3      | 1           | 15 Oct 2026 |
| Mehdi Jaziri     | WhatsApp       | Ariana  | 29 111 222 | 2      | 0           | 12 Oct 2026 |
| Nour Gharbi      | TikTok         | Sfax    | 55 123 456 | 1      | 0           | 20 Oct 2026 |
| Rania Hamdi      | —              | Bizerte | 50 987 654 | 1      | 0           | 9 Oct 2026  |
| Yassine Trabelsi | Facebook       | Tunis   | 98 765 432 | 1      | 1           | 27 Oct 2026 |

Rania Hamdi was added without a source (it is optional). Boutique Lina is the one shop.
Amira's open order of 28 Oct 2026: 1 × Denim Jacket (M) and 1 × Denim Jeans 5-pocket, 2 units, "Not started".

## Problem and user
Today the client of an order is a name typed on each order ("Client", placeholder "e.g. Boutique Lina"). The owner types it again for
every order, a typo makes a second client, nowhere in Invory says how to call the buyer or where to deliver, and nothing tells her
which network her buyers come from. When Amira messages "where is my jacket?", the owner searches the Orders list for "amira" and
hopes she typed it the same way.

The owner takes orders on a **phone**, often while answering an Instagram or WhatsApp message, and on a **laptop** at the cutting
table. The questions: **"Who is this buyer, how do I reach her, and where did she find us?"** and **"What has she ordered, and what is
still open?"** On a new order the question is simply: **"Is this a buyer I already have, or a new one?"**

## How it looks today
- **New order / Edit order**, Details card: "Client" (a text field, placeholder "e.g. Boutique Lina"), "Order date", "Note
  (optional)". Errors: "The client field is required." and "The client field must not be greater than 100 characters.".
- **Order page**: the title is the typed name, under it "Ordered 15 Oct 2026 · 2 products · 80 units"; breadcrumb "Orders › Boutique
  Lina".
- **Orders list**: the column "Client" (the name, a link to the order, the note under it), the search "Search client, product or
  note" and the Status select ("Open orders", "Shipped", "All orders"). The four totals count every open order, whatever the search.
- **Delete order?** names the client: "The order of **Boutique Lina** (2 products, 80 units) will be removed. …"; the refusal starts
  "Boutique Lina can't be deleted: …".
- There is no list of clients, and no phone, city or source anywhere.

## Flow
1. **New order** (the usual way in). Under **Client**, a two-way switch: **Existing client** · **New client**.
   - **Existing client:** the picker **Choose a client…**. Typing "amira" or "22 345" lists Amira Ben Salah (Instagram · Sousse · 22 345
     678). Under the field: "Instagram · Sousse · 22 345 678", so the owner sees she picked the right person.
   - **New client:** the client's fields sit right in the order form: **Name**, **Phone**, **City**, **Source (optional)** ("Where did
     this client find you?": Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop, Word of mouth, Other).
   - The picker's last option "+ Add "Amira" as a new client" switches to **New client** with the name filled in.
2. **Already a client?** When the typed phone (or name) belongs to a client, a note appears under it: "22 345 678 is the phone of Amira
   Ben Salah (Instagram · Sousse)." · **Choose Amira Ben Salah**. One click switches to Existing client with her chosen; the products
   typed stay.
3. **Save order** saves the order **and** the new client together, or nothing: "Order recorded. Amira Ben Salah is now one of your
   clients." and the Orders list opens.
4. **Sidebar › Clients** (new, after Orders). The list shows name, source, city, phone, open orders, last order; a **Source** filter
   answers "how many come from Instagram?". **Add client** opens a small window with the same fields.
5. A click on a client opens **its page**: name, the source label, city, phone (tap to call), three figures, **New order** (with her
   chosen) and her orders. A client can't be edited in INV-4.
6. **The order page** shows the client under the title: "[Instagram] · Sousse · 22 345 678 · All orders of Amira Ben Salah".
7. **The Orders list** gets a **Client** filter beside Status.
8. **Orders recorded before INV-4** are development data and may be dropped: nothing is converted, so every order has a client from
   the start.

## Options and recommendation

Decided options are marked **decided**; the others stay for the record.

### A · Where clients live (unchanged)
- (a) **A Clients page in the sidebar (list and Add client; no Edit client in INV-4) and a page per client that shows its orders** —
  **recommended.**
- (b) Settings › Clients, no client page. (c) Only inside the order form.

### B · How a new client is entered on an order (revised)
- (a) **New order has a switch "Existing client · New client"; New client shows Name, Phone, City and Source inline; Save order
  creates both; Edit order keeps the picker only, whose last option opens the Add client window** — **recommended.** Matches how a B2C
  order arrives (a new buyer writes on Instagram): everything on one screen, one save, nothing lost.
- (b) The first proposal: picker only, its last option opens the Add client window over the order. One more step, a window on a phone.
- (c) No switch: the Client field is a picker that becomes inline fields as soon as the typed name matches no one. Fewer clicks, but
  the switch from "searching" to "creating" happens under the owner's fingers and is easy to miss.

### C · Which fields are required — **decided: Name, Phone and City required; Source optional**
- Decided by the human on 2026-10-30. An individual buyer is reached and delivered by phone, the city tells the courier where, and the
  source is asked but never blocks an order.
- Proposed before: (a) Name, Phone and Source required, City optional · (b) Name only · (c) Name and Phone.

### D · Phone format (unchanged)
- (a) **8 Tunisian digits, typed with or without +216 / 00216, spaces, dots or dashes, shown "22 345 678"; a number abroad starting
  with + (8 to 15 digits), shown as typed with single spaces** — **recommended.** (b) Tunisian only. (c) Any text.

### E · Duplicates — **decided: (a)**
- (a) **No two clients share a phone (a blocking error that offers the existing client). A name already used is allowed — two buyers
  can both be "Mohamed Ben Ali" — but a note offers the existing one: "You already have a client named Amira Ben Salah (Sousse · 22
  345 678)." · Choose Amira Ben Salah** — **decided.** The phone is the person; the name only hints.
- (b) The first proposal: name and phone both unique. Wrong for individuals with common names.
- (c) Phone unique, no note about names.

### F · Orders recorded before INV-4 — **decided: nothing is converted**
- The orders recorded so far are development data and may be dropped. No client is made from a typed name; there is no "Not
  recorded" source, no "missing details" callout and no filter for it.
- Proposed before: (a) each typed name becomes a client · (b) old orders keep the typed name as text.

### G · Editing a client — **decided: no Edit client in INV-4**
- A client keeps the details entered when it was added. There is no Edit client window, no Edit button on the Clients page or the
  client page, and no "A new name … shows on the 3 orders …" hint. An order shows its client's name, source, city and phone.
- Proposed before: (a) edits show on every order · (b) each order keeps a copy.

### H · Removing a client — **decided: not in INV-4**
- A client is never deleted or archived, as colours. Deleting an order never removes its client.

### I · The client on the order page and the Orders list (unchanged, plus the source)
- (a) **Order page: the source label, city, phone (a call link) and "All orders of Amira Ben Salah" under the summary. Orders list:
  the Client column as today, a Client filter beside Status, the totals unchanged (S-50)** — **recommended.**

### J · City (unchanged)
- (a) **Free text suggesting the cities already used** — **recommended.** (b) The 24 governorates. (c) Plain text.

### K · The source list — **decided: (a); no social-media name field**
- (a) **A fixed list, the same for every workshop, in this order: Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop,
  Word of mouth, Other. "Other" takes an optional short text (up to 40 characters: "Fashion fair Sousse")** — **decided.** Nothing to
  set up, the same words on every screen and filter, and "Other" catches the rest without growing the list.
- (b) A list each workshop edits in Settings › Client sources, like Colours. Flexible (Snapchat, a market), but one more Settings page
  and sources that drift ("Insta", "IG").
- (c) Fixed list, "Other" without text.

### L · How the source looks — **decided: (a)**
- (a) **A neutral label with a small icon and the word, the same grey tone for every source: [camera] Instagram, [thumbs-up]
  Facebook, [music] TikTok, [message] WhatsApp, [globe] Website, [store] Walk-in / shop, [speech] Word of mouth, [dots] Other** —
  **decided.** The word carries the meaning, the icon helps scanning; no brand logos or brand colours (they are trademarks, and
  colour would end up carrying the meaning).
- (b) Brand logos in their colours. Instantly recognisable, but trademark use, colour-coded, and the icon set has no reliable logos.
- (c) The word alone.

### M · How the source is picked (new)
- (a) **A row of choice chips (one choice), icon and word, wrapping; nothing chosen at first; the chosen chip shows a check in place
  of its icon, a 2 px Denim border and Denim text** — **recommended.** Eight options are seen at once and picked with one tap on a
  phone (two chips to a row there).
- (b) A select. Compact, but two taps and the options are hidden.

## Layout

### Sidebar
- New entry **Clients** (lucide "users" icon) after Orders: Dashboard, Materials, Products, Restocks, Orders, **Clients**, Sales.
  Breadcrumbs: "Clients", "Clients › Amira Ben Salah".

### The source label (everywhere)
- A small rounded label in the neutral tone (`--neutral` background with its own text colour), a 14 px icon and the word:
  "Instagram". "Other" with a text reads "Other · Fashion fair Sousse". A client with no source: "—" in the Clients table (read "No
  source"); on the client page and the order page the label is simply left out.
- Icons (lucide): Instagram camera · Facebook thumbs-up · TikTok music · WhatsApp message-circle · Website globe · Walk-in / shop store
  · Word of mouth messages-square · Other circle-ellipsis. The icon is hidden from screen readers; the word is read.

### Clients page (desktop)
The INV-1 Settings › Colours pattern (a table and an Add window, no removal; no Edit window in INV-4), on a page of its own:
- `PageHeader`: **Clients**, description "The people and shops who order from you, and where they found you. Each order belongs to one
  client.", main button **Add client** (plus icon).
- `FiltersBar`: search "Search name, phone or city" · **Source** ("All sources", then the eight sources). Both in the page address.
  25 a page, sorted by name A → Z, `Pagination`.
- Table: **Client** (name, bold, a link to its page) · **Source** (the label; "—" when none, read "No source") · **City** ("Sousse")
  · **Phone** (number font, "22 345 678") · **Open orders** (number font, right) · **Last order** ("28 Oct 2026"; "No orders yet").
  No row buttons: a click on the name opens the client page.

### Add client window (Clients page, Edit order)
- Title **Add client**.
- **Name**: hint "The person or the shop, as you call them." Up to 100 characters.
- **Phone**: number font, hint "8 digits, such as 22 345 678. For a number abroad, start with + and the country code."
- **City**: suggestions of the cities already used, up to 60 characters.
- **Source (optional)**: the eight chips, hint "Where did this client find you?". **Other** adds the field **Other source
  (optional)**, hint "In a few words, such as a fashion fair.", up to 40 characters. A chosen chip can be tapped again to clear it.
- Buttons **Cancel** and **Save client** (spinner while saving; on an error the window stays open with what was typed).
- **Save client** → "Client created.".

### Client page (desktop)
- Breadcrumb "Clients › Amira Ben Salah". `PageHeader`: **Amira Ben Salah**; under it "[Instagram] · [map pin] Sousse · [phone] 22 345
  678" (the phone a call link named "Call Amira Ben Salah, 22 345 678"); with no source the label is left out. One button: **New
  order** (main), named "New order for Amira Ben Salah", which opens New order on Existing client with her chosen.
- Three `StatCard`s: **Open orders** "1" · **Orders** "3" · **Last order** "28 Oct 2026".
- Section **Orders**, Status select ("All orders" by default here): today's Orders table without the Client column, 25 a page.

  | Order date  | Products                                     | Ordered | To start | In cutting | Ready to ship | Shipped | Status      |
  | ----------- | -------------------------------------------- | ------- | -------- | ---------- | ------------- | ------- | ----------- |
  | 28 Oct 2026 | 1 × Denim Jacket · 1 × Denim Jeans 5-pocket  | 2       | 2        | 0          | 0             | 0       | Not started |
  | 14 Oct 2026 | 1 × Leather Tote                             | 1       | 0        | 0          | 0             | 1       | Shipped     |
  | 1 Sep 2026  | 2 × Classic White T-shirt                    | 2       | 0        | 0          | 0             | 2       | Shipped     |

### New order: the Client part of the Details card
Top of the Details card, before Order date and Note:
1. Label **Client**, then a two-way switch (toggle group, one choice, 44 px): **Existing client** · **New client**. It starts on
   Existing client, or on New client when the workshop has no clients yet (decided).
2. **Existing client:** the picker button **Choose a client…** (popover + command, as the product picker). Search "Search clients"
   (name, phone digits or city); options A → Z, each the name and, in grey, "Instagram · Sousse · 22 345 678"; the last option, always
   there, "+ Add "amira" as a new client" (or "+ New client" when nothing is typed) switches to New client with Name filled and the focus
   in Phone; when the search was digits ("22 345"), they fill Phone instead and the focus goes to Name. Once chosen, under the button: "Instagram · Sousse · 22 345 678".
3. **New client:** a light grey box headed "New client" holding, on a laptop in two columns: **Name** · **Phone**; **City**; then
   **Source (optional)** full width (the eight chips; **Other** adds "Other source (optional)"). Under the box: "Saved to your clients
   when you save the order."
4. **Already a client?** (decided: when the field is left) When the focus leaves Phone and the number belongs to a client, an info note under the field: "22 345 678 is
   the phone of Amira Ben Salah (Instagram · Sousse)." · **Choose Amira Ben Salah**. When the name matches a client (ignoring capitals),
   a note under Name: "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." · **Choose Amira Ben Salah**. Choosing
   switches to Existing client with her chosen; the order's products, sizes and note stay. The name note never blocks saving; the
   phone one does on save (see States).
5. Switching between Existing client and New client keeps what was typed in each until Save order; only the visible one is saved.
- **Save order** → the order and, in New client, the client are saved together or not at all. Toast: "Order recorded. Amira Ben Salah
  is now one of your clients." (an existing client: today's "Order recorded."). The Orders list opens, as today.
- **From a client page:** New order opens on Existing client with her chosen; the focus starts on Order date.

### Edit order: the Client field
- The picker only (no switch), with the order's client chosen. Another client can be chosen at any stage of the order. Its last option
  "+ Add "…" as a new client" opens the Add client window over the order; **Save client** → "Client created.", the window closes and
  the new client is chosen. A closed (S-65) or cancelled (S-57) order has no Edit.

### Order page
- Title and breadcrumb: the client's name, as today.
- Under "Ordered 28 Oct 2026 · 2 products · 2 units": "[Instagram] · Sousse · 22 345 678 · **All orders of Amira Ben Salah**". With
  no source: "Bizerte · 50 987 654 · **All orders of Rania Hamdi**".
- Delete order and its refusal keep naming the client.

### Orders list
- `FiltersBar`: search (as today) · **Status** (as today) · **Client** (new, searchable, "All clients" first; options show the phone in
  grey, so two "Mohamed Ben Ali" can be told apart). All in the page address.
- The Client column is unchanged. The four totals still count every open order, whatever is filtered (S-50).

## States

### Clients page

| State          | What the screen shows                                                                                                                      |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Default        | The six example clients                                                                                                                   |
| Empty          | `EmptyState`: "No clients yet" · "Your clients appear here when you record their first order, or add one now." · **Add client**           |
| No match       | "No client matches "Amina"." · "Check the spelling, or search by phone or city." · **Clear filters**                                      |
| Source filter  | Source "Instagram" lists Amira Ben Salah; nothing: "No client came from TikTok yet." · **Clear filters**                                   |
| Loading        | Header, filters and the table head at once; 5 skeleton rows                                                                                |
| Error          | "Couldn't load the clients." · **Try again**                                                                                               |
| Long content   | A 100-character name, a 60-character city and "Other · " + 40 characters are cut with "…" in the table, the full text on the client page |

### Client fields (Add client window and New client in New order)
Errors under their field after saving, the focus on the first one:
- Name empty: "Enter the client's name." · too long: "Keep the name to 100 characters or fewer."
- Phone empty: "Enter the client's phone." · not a number we know: "Enter 8 digits, such as 22 345 678, or a number abroad starting
  with +." · already used: "22 345 678 is already the phone of Amira Ben Salah." with **Choose Amira Ben Salah instead** (New order,
  Edit order) or **Open Amira Ben Salah** (Clients page).
- City empty: "Enter the client's city." · too long: "Keep the city to 60 characters or fewer."
- Source: optional, no message. Other source too long: "Keep it to 40 characters or fewer."
- Name already used: never an error; the note "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." with **Choose
  Amira Ben Salah** (in the Clients page window: **Open Amira Ben Salah**).
- Save failed: "Couldn't save the client. Try again." (New order: today's order message.) Everything typed stays.

### New order: Client part

| State                  | What the screen shows                                                                                                          |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Existing, nothing yet  | Switch on **Existing client**, **Choose a client…**                                                                            |
| Existing, chosen       | "Amira Ben Salah", under it "Instagram · Sousse · 22 345 678"                                                                  |
| Picker search          | "amira" → Amira Ben Salah; last option "+ Add "amira" as a new client"                                                         |
| Picker, no match       | "No client matches "Ines Mrad"." and "+ Add "Ines Mrad" as a new client"                                                       |
| Picker error           | "Couldn't load the clients. Try again." · **Try again**                                                                        |
| New client, empty      | Name, Phone, City, Source (optional) chips with none chosen; "Saved to your clients when you save the order."                  |
| New client, filled     | Ines Mrad · 20 456 789 · Monastir · Instagram                                                                                  |
| Phone already a client | Info note: "22 345 678 is the phone of Amira Ben Salah (Instagram · Sousse)." · **Choose Amira Ben Salah**                    |
| Name already a client  | Info note: "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." · **Choose Amira Ben Salah**              |
| Checking (if slow)     | Nothing blocks typing; the note appears when the check answers                                                                 |
| Nothing chosen on save | Existing client with no one chosen: "Choose a client." under the picker (replaces "The client field is required.")            |
| Errors on save         | The field messages above; also an order error (a line, a size) saves no client either; everything typed stays                 |
| Saving                 | **Save order** shows a spinner and keeps its label                                                                             |
| Success                | "Order recorded. Ines Mrad is now one of your clients."; an existing client: "Order recorded."                                |
| No clients yet         | The switch starts on **New client** (Existing client still there; its picker reads "No clients yet.")                         |

### Client page

| State           | What the screen shows                                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------------------ |
| Default         | Amira Ben Salah as in Layout                                                                                 |
| No source       | "Bizerte · 50 987 654", no label (Rania Hamdi)                                                               |
| No orders       | "0" · "0" · "No orders yet"; Orders: "No orders yet" · "Record an order for Ines Mrad to follow it here." · **New order** |
| Status no match | "Amira Ben Salah has no shipped orders." · **Show all orders**                                                |
| Loading / error | Header and figures at once, 3 skeleton rows; "Couldn't load the orders of Amira Ben Salah." · **Try again**  |
| Read-only       | None                                                                                                         |

### Order page and Orders list
- Order page: "[Instagram] · Sousse · 22 345 678 · All orders of Amira Ben Salah"; no source: "Bizerte · 50 987 654 · All orders of
  Rania Hamdi".
- Orders list, Client "Amira Ben Salah": her open order of 28 Oct 2026; the totals unchanged; no match: today's message and **Clear
  filters** (also clears Client).

## Wording
- Sidebar and breadcrumbs: "Clients" · "Clients › Amira Ben Salah".
- Sources: "Instagram" · "Facebook" · "TikTok" · "WhatsApp" · "Website" · "Walk-in / shop" · "Word of mouth" · "Other" · "Other ·
  Fashion fair Sousse" · "No source" (screen readers, for "—") · filter "All sources".
- Clients page: "Clients" · "The people and shops who order from you, and where they found you. Each order belongs to one client." ·
  "Add client" · "Search name, phone or city" · "Source" · "All sources" · columns "Client", "Source", "City", "Phone", "Open orders",
  "Last order" · "No orders yet" · "No clients yet" · "Your clients appear here when you record their first order, or add one now." ·
  "No client matches "Amina"." · "Check the spelling, or search by phone or city." · "No client came from TikTok yet." · "Clear
  filters" · "Couldn't load the clients." · "Try again".
- Client fields: "Name" · "The person or the shop, as you call them." · "Phone" · "8 digits, such as 22 345 678. For a number abroad,
  start with + and the country code." · "City" · "Source (optional)" · "Where did this client find you?" · "Other source (optional)" ·
  "In a few words, such as a fashion fair." · "Cancel" · "Save client" · "Add client", and the messages in States.
- New order: "Client" · "Existing client" · "New client" · "Choose a client…" · "Search clients" · "+ New client" · "+ Add "Ines Mrad"
  as a new client" · "No client matches "Ines Mrad"." · "No clients yet." · "Saved to your clients when you save the order." · "22 345
  678 is the phone of Amira Ben Salah (Instagram · Sousse)." · "You already have a client named Amira Ben Salah (Sousse · 22 345
  678)." · "Choose Amira Ben Salah" · "Choose a client." · on a phone "Choose a client" (the sheet title) and "Close".
- Client page: "Call Amira Ben Salah, 22 345 678" · "New order" · "Open orders" · "Orders" · "Last order" · "All orders" · "Record an
  order for Ines Mrad to follow it here." · "Amira Ben Salah has no shipped orders." · "Show all orders" · "Couldn't load the orders
  of Amira Ben Salah."
- Order page: "All orders of Amira Ben Salah". Orders list: "Client" · "All clients".
- Toasts: "Client created." · "Order recorded. Ines Mrad is now one of your clients." · "Order recorded." · "Order updated."
- Gone: "e.g. Boutique Lina", "The client field is required.", "The client field must not be greater than 100 characters.".
- "+" in an option is a plus icon. "Colour" keeps its spelling wherever it shows.

## Accessibility
- **Switch (4.1.2):** a radio group named "Client" with "Existing client" and "New client"; arrow keys move between them; changing it
  moves the focus to the picker or to Name.
- **Picker (4.1.2, 1.3.1):** a button named "Client" opening a listbox; options read "Amira Ben Salah, Instagram, Sousse, 22 345 678";
  Esc closes and returns the focus.
- **Source chips (1.3.1, 4.1.2):** a radio group named "Source" (fieldset and legend), each chip a radio with its word; the icons are
  hidden. On a label, the word is the content: never the icon alone, never a colour (1.4.1).
- **Notes and errors (3.3.1, 3.3.3, 4.1.3):** the "already a client" note is a polite live region tied to its field; errors are tied to
  their field and announced; **Choose Amira Ben Salah** is the next Tab stop after the note.
- **Redundant entry (3.3.7):** the picker's search becomes the new client's name; a known phone offers the existing client; New order
  from a client page has her chosen.
- **Names (2.4.4):** "Call Amira Ben Salah, 22 345 678", "All orders of Amira Ben Salah", "New order for Amira Ben Salah".
- **Phone field (1.3.5):** autofill "tel", the number keypad, 16 px text.
- **Contrast (1.4.3, 1.4.11):** the source label (`--neutral`) #3F3F46 on #F0F0EC 9.1:1, dark #D4D4D8 on #2A2A30 9.6:1; a chosen chip
  Denim #2B3A86 border and text on #E9ECFA 9.0:1, dark #97A4EE on #1E2240 7.1:1, plus a check icon so "chosen" isn't colour alone; grey
  lines #5F5F66 on white 6.3:1 (dark #A1A1AA 7.0:1); the info note (`--info`) its own text on its tint; field borders 3.2:1.
- **Targets (2.5.8, Invory 44 px):** switch halves, chips, picker options and Call 44 px on a phone.
- **Reflow (1.4.10):** at 320 px nothing scrolls sideways; chips and the client line wrap.

## Phone (375 px)
- **New order:** the switch full width (two halves, 44 px). Existing client: the picker full width, its list a full-height sheet
  "Choose a client" with the search on top and "+ New client" fixed at the bottom. New client: Name, Phone, City, then Source chips two
  or three to a row (44 px), one under another, in the grey box; the "already a client" note under its field. **Save order** in the bar
  fixed at the bottom, as today forms; the bar never hides the focused field.
- **Clients:** header and **Add client** full width, the search, **Filters** ("Filters · 1" when a source is chosen; a sheet with
  Source). One card per client, the whole card a link to its page: the name, the source label, "Sousse · 22 345 678", "1 open order ·
  last 28 Oct 2026".
- **Add client:** a sheet over the whole screen, fields one under another, **Save client** in a bar fixed at the bottom.
- **Client page:** the title, the source label and city, a full-width **Call 22 345 678** button (44 px), **New order** full width,
  the figures two to a row, her orders as today's order cards without the name.
- **Order page:** the client line wraps: the source label and city, then the phone, then "All orders of Amira Ben Salah".
- **Orders list:** the search, then **Filters** with Status and Client.

## For the product-owner
Proposed content of the UI card `docs/stories/INV-4-clients-ui.md`:
- **Description:** each order belongs to a client: a person or a shop with a name, a phone, a city and the source that brought them
  (Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop, Word of mouth, Other). New: the Clients page (sidebar) with a Source
  filter, the Add client window, the client page with its orders, and on New order the switch "Existing client · New client" with
  the new client's fields inline. Changed: the Client field of Edit order (a picker), a client line on the order page, a Client filter
  on Orders. Left out: editing, removing or merging clients, a social-media name on the client, converting the orders recorded before
  INV-4 (development data, may be dropped), clients by source on the dashboard, progress (INV-5), invoicing, addresses beyond the
  city, client accounts and logins, sales (a sale still has no client).
- **Pages, States, Wording, Phone:** the sections above, word for word.
- **Proposed acceptance wording for the development card later:**
  - On New order, choosing **New client**, typing "Ines Mrad", "20 456 789", "Monastir", choosing Instagram, adding 1 × Denim Jacket
    (M) and saving shows "Order recorded. Ines Mrad is now one of your clients."; the Clients page lists "Ines Mrad · Instagram · Monastir
    · 20 456 789 · 1".
  - Typing 22 345 678 as a new client's phone shows "22 345 678 is the phone of Amira Ben Salah (Instagram · Sousse)." and **Choose
    Amira Ben Salah**, which chooses her and keeps the Denim Jacket line; saving anyway shows "22 345 678 is already the phone of Amira
    Ben Salah." and saves nothing.
  - A wrong order line (quantity 0) saves neither the order nor the new client.
  - "22345678", "+216 22 345 678" and "22.345.678" all save as "22 345 678".
  - Source "Instagram" on the Clients page lists only the clients from Instagram; the order page of Amira's order reads "Instagram ·
    Sousse · 22 345 678 · All orders of Amira Ben Salah".
  - A new client with City left empty shows "Enter the client's city." and saves nothing; with Source left empty it saves, and the
    Clients page shows "—" in Source.
- **Rules the development card will need:** a client has a name (required, up to 100, not unique), a phone (required; 8 Tunisian
  digits or + and 8 to 15 digits; unique per workshop), a city (required, up to 60) and an optional source from the fixed list
  ("Other" with up to 40 characters of optional text); every order has exactly one client of its workshop; an order and its new
  client save together or not at all; a client is never edited, removed or archived in INV-4.

## For the tech-lead
- **Values the server sends.**
  - A client: id, name, source (code, its label, the "Other" text; or none), city, phone shown and phone to dial ("+21622345678"),
    open orders count (S-50 `open(O)`), orders count, last order date.
  - The source options (code, label, icon name) as reference values, like the other enums' `options()`; the frontend never hard-codes
    labels.
  - Clients list: 25 a page by name; search over name, city and phone digits; the Source filter.
  - City suggestions: the workshop's distinct cities.
  - Orders (list, page, form, delete dialog and refusal): the client object in place of today's client text; the Orders list's
    `client` filter and its options (name and phone) or a search.
  - Client page: its orders, Status filter defaulting to "All orders".
  - New order: the client options or a search; a **lookup by phone and by name** for the "already a client" notes (or the notes appear
    only on save, if the lookup is costly: say so); saving an order with a new client is one write (order + client, all or nothing),
    returning the field errors under the new client's fields; a duplicate phone returns the existing client's id and name.
  - Edit order: creating a client from its window returns it.
- **Rules to encode** (costing skill glossary: "Order … client name" becomes "Order … client"; a new Client entry and a ClientSource
  enum): names trimmed and double spaces collapsed, not unique; phones normalised then unique per workshop; phone and city required;
  source nullable; `BelongsToWorkshop`; `WorkshopRule`; 404 for another workshop's client.
- **Data change:** none for clients. The orders recorded so far are development data and may be dropped (the human, 2026-10-30), so
  every order can require a client from the start; the demo seed needs clients for its orders. Whether to drop or reseed is yours.
- **Components:** toggle-group (switch and chips), popover + command, dialog, sheet, table, select, `FiltersBar`, `Pagination`,
  `EmptyState`, `Callout`, `StatCard`, skeleton, sonner: all exist. New: the sidebar entry; a small shared source label.
- **Costly or risky:** the combined order + client save and its errors in one form; the live phone / name lookup; every screen and
  test that reads the order's client as text (`domain.ts`, title, breadcrumbs, delete dialog, search, S-57 / S-65 messages).

## Questions for the product-owner / human
All eleven questions of revision 2 are decided (see the decisions at the top). None open.
