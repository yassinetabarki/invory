---
id: INV-4
title: Clients as an entity
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Ready
created: 2026-11-02
tags:
  - story
  - variants
---

# INV-4 · Clients as an entity

> [!abstract] Description
> As a **workshop owner**, I want **every order to belong to a client I can find by her phone number**, so that **I never type a
> regular client twice and I know which network brought her**. A client is a person or a shop with a name, a phone, a city and, if
> known, the source that brought her (Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop, Word of mouth, Other). The
> workshops sell mostly to people who find them on social media and order by message, so the phone number is the key.
> New: the **Clients** page in the sidebar, the **Add client** window, the client page with her orders, and on New order the
> **Client** card where the phone finds the client. Changed: the Client field of Edit order (a picker), a client line on the order
> page and a Client filter on the Orders list. The pages are drawn in [[INV-4-clients-ui]]; New order's Client card is the one of
> [[INV-12-client-first-ui]], which replaces INV-4's "Existing client · New client" switch (the human's choice, 2026-11-02).
> Left out on purpose: editing, removing or merging clients; an Instagram name on the client; clients on the dashboard; addresses
> beyond the city; client logins; a client on a sale; the product search on New order (INV-13); the Restocks entry in the sidebar
> (INV-3).

> [!info] How it works today
> The client of an order is a name typed on each order, in the Details card of New order and Edit order: "Client" (placeholder "e.g.
> Boutique Lina"), with the errors "The client field is required." and "The client field must not be greater than 100 characters.".
> The owner types the name again for every order, and a typo makes a second client. The order page's title is the typed name
> ("Orders › Boutique Lina", "Ordered 15 Oct 2026 · 2 products · 80 units"); the Orders list has a Client column, the search "Search
> client, product or note" and the Status list ("Open orders", "Shipped", "All orders"). There is no list of clients and no phone,
> city or source anywhere: when Amira messages "where is my jacket?", the owner searches the Orders list for "amira" and hopes she
> typed it the same way. The sidebar reads Dashboard, Materials, Products, Orders, Sales.

## Acceptance criteria

The examples use these clients (Boutique Lina is the one shop; today is 30 Oct 2026):

| Client           | Source         | City    | Phone      | Orders | Open orders | Last order  |
| ---------------- | -------------- | ------- | ---------- | ------ | ----------- | ----------- |
| Amira Ben Salah  | Instagram      | Sousse  | 22 345 678 | 3      | 1           | 28 Oct 2026 |
| Boutique Lina    | Walk-in / shop | Tunis   | 71 234 567 | 3      | 1           | 15 Oct 2026 |
| Mehdi Jaziri     | WhatsApp       | Ariana  | 29 111 222 | 2      | 0           | 12 Oct 2026 |
| Nour Gharbi      | TikTok         | Sfax    | 55 123 456 | 1      | 0           | 20 Oct 2026 |
| Rania Hamdi      | Website        | Bizerte | 50 987 654 | 1      | 0           | 9 Oct 2026  |
| Yassine Trabelsi | Facebook       | Tunis   | 98 765 432 | 1      | 1           | 27 Oct 2026 |

- [ ] **AC-1 · A client and her details.** A client has a **Name** (required, up to 100 characters), a **Phone** (required), a
  **City** (required, up to 60 characters) and a **Source** (optional), and belongs to the workshop like every other record.
  - **Phone:** a Tunisian number of 8 digits, typed with or without +216 or 00216, spaces, dots or dashes, is kept and shown as "22 345
    678" ("22345678", "+216 22 345 678" and "22.345.678" are all "22 345 678"). A number abroad is accepted when it starts with + and
    its country code (8 to 15 digits) and is shown as typed with single spaces. **No two clients of a workshop share a phone**,
    compared on the digits only; two clients may share a name ("Mohamed Ben Ali").
  - **City** is free text and suggests the cities the workshop's clients already have ("Tunis", "Sousse", "Bizerte").
  - **Source** is one of the fixed list, in this order: Instagram, Facebook, TikTok, WhatsApp, Website, Walk-in / shop, Word of mouth,
    Other. "Other" takes an optional text up to 40 characters ("Fashion fair Sousse"). It is picked with a row of chips, one choice,
    nothing chosen at first; the chosen chip shows a check in place of its icon, a 2 px Denim border and Denim text, and a second tap
    un-chooses it; screen readers hear one group named "Source".
  - **The source label**, everywhere: a small rounded grey label, the same tone for every source, with an icon and the word
    ([camera] Instagram, [thumbs-up] Facebook, [music] TikTok, [message] WhatsApp, [globe] Website, [store] Walk-in / shop, [speech]
    Word of mouth, [dots] Other; "Other · Fashion fair Sousse"). No brand logos or colours; screen readers read the word only. A
    client with no source has no label ("—" in the Clients table, read "No source").
  - A client can't be edited, removed or archived, and deleting an order never removes its client. Orders recorded before this card
    are test data and are removed when it ships, with their lines; materials, their history, sales and finished stock stay as they
    are. From then on every order has a client.
  - Errors, under their field, the focus on the first one: "Enter the client's name." · "Keep the name to 100 characters or fewer." ·
    "Enter the client's phone." · "Enter 8 digits, such as 22 345 678, or a number abroad starting with +." · "Enter the client's
    city." · "Keep the city to 60 characters or fewer." · "Keep it to 40 characters or fewer." (Other source). The hints: Name "The
    person or the shop, as you call them." · Phone "8 digits, such as 22 345 678. For a number abroad, start with + and the country
    code." · Source "Where did this client find you?" · Other source "In a few words, such as a fashion fair.". The phone field uses
    the number font, opens the number keypad on a phone and its text is 16 px.
- [ ] **AC-2 · Clients page.** The sidebar reads Dashboard, Materials, Products, Orders, **Clients** (users icon), Sales. The page
  **Clients**, "The people and shops who order from you, and where they found you. Each order belongs to one client.", has the main
  button **Add client** (plus sign), the search "Search name, phone or city" and the list **Source** ("All sources", then the eight
  sources), both kept in the page address and combined. The table, 25 a page, sorted by name A → Z: **Client** (bold, a link to her
  page) · **Source** (the label, "—") · **City** · **Phone** (number font, "22 345 678") · **Open orders** (number font, right-aligned)
  · **Last order** ("28 Oct 2026"; "No orders yet"). The six clients above show in that order with those figures.
  - Searching "22 345" or "sousse" lists Amira Ben Salah; Source "Instagram" lists Amira Ben Salah alone.
  - **Empty:** "No clients yet" · "Your clients appear here when you record their first order, or add one now." · **Add client**.
    **No match:** "No client matches "Amina"." · "Check the spelling, or search by phone or city." · **Clear filters**. **Source with
    no client:** "No client came from TikTok yet." · **Clear filters**. **Loading:** the header, filters and table head at once, 5
    placeholder rows. **Error:** "Couldn't load the clients." · **Try again**. A 100-character name, a 60-character city and "Other ·
    " with 40 characters are cut with "…" in the table, in full on the client page.
  - **Phone (375 px):** the header and **Add client** full width, the search, **Filters** ("Filters · 1" when a source is chosen; a
    panel with Source), then one card per client: the name (a link), the source label, "Sousse · 22 345 678", "1 open order · last 28
    Oct 2026". At 320 px nothing scrolls sideways.
- [ ] **AC-3 · Add client window.** **Add client** opens the window **Add client** with Name, Phone, City and **Source (optional)**
  (the eight chips; **Other** adds "Other source (optional)"), and the buttons **Cancel** and **Save client**, which shows a spinner
  while saving. Saving Ines Mrad · 20 456 789 · Monastir · Instagram closes it with the toast "Client created." and Ines Mrad shows in
  the list in her place. On an error the window stays open with what was typed.
  - Phone 22 345 678 is refused with "22 345 678 is already the phone of Amira Ben Salah." and **Open Amira Ben Salah**, which opens
    her page.
  - A name already used is never an error: under Name, "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." with
    **Open Amira Ben Salah**.
  - Save failed: "Couldn't save the client. Try again.", and everything typed stays.
  - **Phone:** a panel over the whole screen, the fields one under another, **Save client** in a bar fixed at the bottom.
- [ ] **AC-4 · Client page.** Breadcrumb "Clients › Amira Ben Salah", header **Amira Ben Salah**, under it "[Instagram] · [map pin]
  Sousse · [phone] 22 345 678", the phone a call link named "Call Amira Ben Salah, 22 345 678" (a client with no source: the line
  starts with the city). The main button **New order**, named "New order for Amira Ben Salah", opens New order with her already
  shown as Existing client (AC-5). Three figures: **Open orders** "1" · **Orders** "3" · **Last order** "28 Oct 2026". The section
  **Orders** has the Status list, "All orders" by default here, and today's Orders table without the Client column, 25 a page, newest
  first; a click on an order date opens the order. Amira's orders: 28 Oct 2026 "1 × Denim Jacket · 1 × Denim Jeans 5-pocket" 2
  ordered, 2 to start, "Not started"; 14 Oct 2026 "1 × Leather Tote" 1 shipped, "Shipped"; 1 Sep 2026 "2 × Classic White T-shirt" 2
  shipped, "Shipped".
  - **No orders:** "0" · "0" · "No orders yet"; the section "No orders yet" · "Record an order for Ines Mrad to follow it here." ·
    **New order**. **Status with no match:** "Amira Ben Salah has no shipped orders." · **Show all orders**. **Loading:** the header
    and figures at once, 3 placeholder rows. **Error:** "Couldn't load the orders of Amira Ben Salah." · **Try again**. The page is
    read-only: there is no Edit.
  - **Phone:** the title, the source label and city, a full-width **Call 22 345 678** button (44 px), **New order** full width, the
    figures two to a row, her orders as today's order cards without the name.
- [ ] **AC-5 · New order starts with the client, found by her phone.** New order shows its cards in the order **Client**, then
  **Products**, then **Details** (Order date and Note; the Client field leaves Details). The Client card: the heading **Client**, "A
  phone number belongs to one client. If it is already one of your clients, the order goes to them.", the outline button **Find a
  client by name** (search icon), and a grey box "Client details" with **Phone** next to **Name**, then **City**, then **Source
  (optional)**; under the box "The client is saved to your clients when you save the order, if the phone number is new.".
  - **The lookup** runs when the focus leaves Phone, on the digits only ("22345678", "+216 22 345 678" and "22.345.678" all find Amira
    Ben Salah). While it runs: a spinner and "Checking 22 345 678…", and nothing blocks typing. Changing the phone runs it again.
  - **Existing client:** the grey box gives way to a block with a 2 px Denim border: **Amira Ben Salah** and the pill **Existing
    client**, "[Instagram] · Sousse · 22 345 678", "3 orders · 1 open · last order 28 Oct 2026", the outline button **Use another
    number** (clears Phone, shows the form again, focus in Phone), and under it "The order uses the details saved for this client.
    Name, City and Source aren't asked again.". An order never changes a client's saved details.
  - **New client:** the fields stay and under Phone a green note with a user-plus icon, "New client. 20 456 789 isn't one of your
    clients yet."; the line under the box reads "Saved to your clients when you save the order.".
  - **Typed details differ:** with "Amina B." and "Tunis" typed before 22 345 678, the Existing client block and the amber note "You
    typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben Salah (Sousse), so the order goes to her. Her saved details stay as they
    are.".
  - **Name known with another number:** under Name, "You already have a client named Amira Ben Salah (Sousse · 22 345 678)." with
    **Choose Amira Ben Salah**, which shows her as Existing client and keeps the products and the note; it never blocks saving.
  - **Lookup failed:** an amber note under Phone, "Couldn't check this number. Save the order and Invory checks it again.", and **Try
    again**; saving still works.
  - **Find a client by name** opens a picker: the search "Search clients" (name, phone digits or city), the clients A → Z, each with
    "Instagram · Sousse · 22 345 678" in grey; choosing one shows her as Existing client with her phone filled in. No match: "No client
    matches "Ines Mrad"."; none yet: "No clients yet."; error: "Couldn't load the clients. Try again." and **Try again**. It offers no
    "Add … as a new client".
  - **Save order** finds or creates the client by the phone's digits together with the order, whatever the lookup said, or saves
    nothing: an error in the products saves no client either. Toasts: "Order recorded. Ines Mrad is now one of your clients." (a new
    number) or "Order recorded." (an existing client); the Orders list opens, as today. Errors on save sit under their field (AC-1),
    with the focus on the first one and at the top "Check the client: 3 fields can't be saved."; a phone that belongs to a client is
    never an error here. **Save order** shows a spinner and keeps its label; save failed: "Couldn't save the order. Try again." and
    everything typed stays.
  - **From a client page** (AC-4), New order opens with her as Existing client and the focus in the Products card.
  - **Phone:** the Client card first; **Find a client by name** full width (44 px); Phone, Name, City and the Source chips two to a row
    (44 px), one under another; **Use another number** full width (44 px); **Save order** in the bar fixed at the bottom, never hiding
    the field with the focus. A 100-character name is cut with "…" in the block. At 320 px nothing scrolls sideways.
- [ ] **AC-6 · Edit order chooses from the clients.** Edit order's Client field is a picker, with no phone lookup, showing the order's
  client; any client can be chosen at any stage of the order. The search "Search clients" and the options are those of AC-5, with
  "Instagram · Sousse · 22 345 678" shown under the chosen client. The last option, with a plus icon, "+ Add "amira" as a new client"
  ("+ New client" when nothing is typed), opens the Add client window (AC-3) over the order with Name filled in (digits typed fill
  Phone instead); **Save client** → "Client created.", the window closes and the new client is chosen, and a phone already used reads
  "22 345 678 is already the phone of Amira Ben Salah." with **Choose Amira Ben Salah instead**. **Save order** → "Order updated.". On a phone the list
  is a panel over the whole screen titled "Choose a client", with the search on top and **Close**.
- [ ] **AC-7 · The client on the order page and the Orders list.** The order page's title and breadcrumb are the client's name, as
  today; under "Ordered 28 Oct 2026 · 2 products · 2 units" the line "[Instagram] · Sousse · 22 345 678 · **All orders of Amira Ben
  Salah**" (a link to her page; the phone a call link; with no source it starts with the city; on a phone it wraps: the label and
  city, then the phone, then the link). The Delete order
  window and its refusal keep naming the client. The Orders list keeps its Client column and adds the filter **Client** after Status:
  "All clients" first, then the clients A → Z with their phone in grey, with its own search; kept in the page address and combined
  with the search and Status. Client "Amira Ben Salah" lists her open order of 28 Oct 2026; the four totals above the list still count
  every open order; no match shows today's message, and **Clear filters** also clears Client. On a phone, **Filters** holds Status and
  Client.
