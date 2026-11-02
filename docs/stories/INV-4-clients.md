---
id: INV-4
title: Clients as an entity
type: feature
phase: Colour × size variants
priority: Must
size: L
status: Planned
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

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 9 commits
> Tech-lead · 2026-11-02 · approved 2026-11-02
> Why complex: a new table with a uniqueness rule on a normalised phone, a migration that puts every existing order away, a new
> payload for New order (find or create the client in the same save), new JSON routes and a changed contract on four pages.

### In short
A client is a new record of the workshop. Her phone is turned, by one pure rule, into the form the screens show ("22 345 678") and
a key of digits only, so "22345678", "+216 22 345 678" and "22.345.678" are one number; the key is unique in the workshop, which is
what stops two clients sharing a phone. Every order points at its client and keeps a copy of her name, as today's `client` text:
a client can't be renamed, so the copy never goes stale, and the search, the stock history and the Delete messages keep working
unchanged. New order no longer sends a client id: it sends what is in the Client card, and the save finds the client by the phone's
digits or creates her, inside the order's transaction, so "the save has the last word" holds even when two people add her at
once. The lookup the card does when the phone loses focus, the picker's search and the Clients page are small read-only routes. The
orders recorded before this card are put away (soft-deleted) by the migration, which leaves materials, their history, sales and
finished stock untouched. The screens follow the server in four steps: the shared client pieces, the two client pages, New order's
Client card, then Edit order, the order page and the Orders list.

### Steps

| Step        | What it does                                         | Proves           | Lane         | Who      | Size       |
| ----------- | ---------------------------------------------------- | ---------------- | ------------ | -------- | ---------- |
| **INV-4-a** | The clients table, the phone rule, old orders away   | AC-1             | CRITICAL     | Backend  | ~280 lines |
| **INV-4-b** | New order finds or creates its client by phone       | AC-1, AC-5       | CRITICAL     | Backend  | ~320 lines |
| **INV-4-c** | Clients list, Add client, client page, picker search | AC-2, AC-3, AC-4 | CRITICAL     | Backend  | ~340 lines |
| **INV-4-d** | Edit order's client, the order page, Client filter   | AC-6, AC-7       | CRITICAL     | Backend  | ~220 lines |
| **INV-4-e** | Source label and chips, client fields, the windows   | AC-1, AC-3, AC-6 | CRITICAL     | Frontend | ~380 lines |
| **INV-4-f** | The Clients page and the sidebar entry               | AC-2, AC-3       | NON-CRITICAL | Frontend | ~300 lines |
| **INV-4-g** | The client page                                      | AC-4             | NON-CRITICAL | Frontend | ~280 lines |
| **INV-4-h** | New order's Client card and its phone lookup         | AC-5             | CRITICAL     | Frontend | ~380 lines |
| **INV-4-i** | Edit order's picker, the client line, Client filter  | AC-6, AC-7       | CRITICAL     | Frontend | ~260 lines |

Every step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. The backend steps
run their PostgreSQL tests; after the last wave the whole tree runs `composer test:pgsql` and `php artisan test tests/Browser` (after
`npm run build`). The server steps can ship before the screens only once INV-4-b and INV-4-h land together in the history order
below: INV-4-b changes what New order sends, so the old form stops saving until INV-4-h. The brief for INV-4-b therefore includes the
smallest change to today's form that keeps it saving (the typed client becomes `client.name` plus Phone and City fields), which INV-4-h
replaces.

#### INV-4-a · The clients table, the phone rule, old orders away
**Commit:** `feat(clients): clients with a unique phone per workshop; orders recorded before are put away (INV-4-a)`

- `App\Domain\Clients\PhoneNumber` (pure PHP): `parse(string): ?PhoneNumber` with `display` and `key`. Spaces, dots, dashes and
  brackets are dropped. Eight digits, or +216 / 00216 and eight digits, is a Tunisian number: `display` "22 345 678" (2-3-3), `key`
  "21622345678". A number starting with + and another country code, 8 to 15 digits in all, is a number abroad: `display` as typed with
  single spaces, `key` its digits. Anything else is `null` (the AC-1 message).
- `App\Enums\ClientSource`: instagram, facebook, tiktok, whatsapp, website, walk_in, word_of_mouth, other, in that order, with
  `label()` and `options()` (the AC-1 words).
- Migration `clients`: `workshop_id`, `name` (100), `phone` (32, the display form), `phone_key` (16), `city` (60), `source` (16,
  nullable), `source_other` (40, nullable), `user_id` (who added her, `nullOnDelete`), timestamps; no soft deletes (a client is never
  removed). Unique (`workshop_id`, `phone_key`); index (`workshop_id`, `name`, `id`) for the A → Z list. PostgreSQL only: unique
  (`workshop_id`, `id`).
- Same migration on `orders`: `client_id` nullable, FK → clients `restrictOnDelete`, indexed; PostgreSQL only the composite key
  (`workshop_id`, `client_id`) → clients (`workshop_id`, `id`), so an order can't point at another workshop's client, and the CHECK
  `client_id IS NOT NULL OR deleted_at IS NOT NULL`, so every order still shown has a client. Then every order that exists (all have
  no client) gets `deleted_at` = now: the human's decision 2026-11-02. `orders.client` stays and keeps the client's name.
  `down()` drops what `up()` added; the orders it put away stay put away.
- Model `Client` (`BelongsToWorkshop`, `orders()`), `ClientPolicy` (any member of the workshop; another workshop is a 404 by the
  scope), `Order::client()`; `ClientFactory`; `OrderFactory` makes a client for every order (named after its `client`, so existing tests
  read the same).
- The tech-lead writes in this commit: ADR-0021 "Clients are found by their phone" (the key, the name kept on the order, the nullable
  column and its CHECK, the orders put away), `docs/ARCHITECTURE.md` (the table, the routes) and the business rules file (Client in
  the glossary, the phone rule).

**Tests**
- 1 unit dataset for `PhoneNumber`: "22345678", "22 345 678", "22.345.678", "22-345-678", "+216 22 345 678", "0021622345678" are one
  key and show "22 345 678"; "+33 6 12 34 56 78" stays as typed; "2234567", "123456789", "+33 1234", "abc" and "" are refused.
- 1 migration test: two workshops' orders, one with cuts and its `cutting` movements, are put away; their lines, cuts, materials,
  stock movements, sales and finished stock are unchanged; `down()` removes the table and the column.
- 1 PostgreSQL schema test: a second client with the same key in one workshop is refused, in two workshops it is not; an order
  pointing at another workshop's client is refused; an order shown without a client is refused.
- The workshop arch test lists `clients`; 1 policy dataset (guest · member · other workshop 404).

> [!example]- Files (13)
> - `.claude/skills/invory-costing-domain/SKILL.md`, `docs/adr/0021-clients-are-found-by-their-phone.md` (new), `docs/ARCHITECTURE.md` (tech-lead)
> - `app/Domain/Clients/PhoneNumber.php` (new), `app/Enums/ClientSource.php` (new)
> - `app/Models/Client.php` (new), `app/Models/Order.php`, `app/Policies/ClientPolicy.php` (new)
> - `database/migrations/2026_11_02_200000_create_clients_table.php` (new)
> - `database/factories/ClientFactory.php` (new), `database/factories/OrderFactory.php`
> - `tests/Unit/Domain/Clients/PhoneNumberTest.php` (new), `tests/Feature/Clients/ClientsMigrationTest.php` (new)

#### INV-4-b · New order finds or creates its client by phone
**Commit:** `feat(clients): New order finds the client by her phone, or adds her with the order (INV-4-b)`

- `StoreOrderRequest`: `client.phone` required and valid (`PhoneNumber`); when no client of the workshop has its key, `client.name`
  (≤ 100), `client.city` (≤ 60) are required, `client.source` is a `ClientSource` or empty, `client.source_other` ≤ 40 (kept only for
  Other); when one has it, the other fields are ignored. Every message is AC-1's. `UpdateOrderRequest` stops inheriting the client
  rules (INV-4-d gives it its own).
- `App\Actions\Clients\FindOrAddClient`: by the key, else creates the client (`createOrFirst`, so a client added meanwhile by someone
  else is found instead of failing on the unique key). `RecordOrder` calls it inside its transaction, sets `client_id` and copies the
  name into `orders.client`; nothing else of a found client changes. The controller's toast is "Order recorded. Ines Mrad is now one of
  your clients." when the client was added, else "Order recorded.".
- `GET clients/lookup` (`clients.lookup`, JSON, read-only, ADR-0009 rule 5): `?phone=` gives the client with that key, `?name=` the
  first client (A → Z, then id) whose name matches ignoring capitals; either way `{ client: ClientSummary | null }` with her orders,
  open orders and last order date (open = not every unit shipped, as the Orders list). An invalid phone answers `{ client: null }`.
- `orders/create` gets `cities` (the workshop's cities A → Z, distinct) and `client_sources` (`ClientSource::options()`); `client` on
  `orders/create` from a client page: `?client=<id>` gives `client_preset: ClientSummary | null` (INV-4-c's link).
- `validOrderPayload()` and every test that posts a typed client switch to the `client` object (about 40 places).

**Tests**
- 1 feature dataset: a known phone in four spellings records the order to Amira Ben Salah and changes none of her details; a new
  phone adds Ines Mrad with the order and the "now one of your clients" toast; each missing or too long field is refused with its
  AC-1 message on `client.*`; a bad product line saves neither the order nor the client.
- 1 race test: the client added between the lookup and the save is found, not duplicated (`createOrFirst` path).
- 1 lookup dataset: by phone, by name ignoring capitals, nothing found, an invalid phone, another workshop's client never found,
  guest redirected.
- 1 contract test: `ClientSummary` keys and types.

> [!example]- Files (11)
> - `app/Actions/Clients/FindOrAddClient.php` (new), `app/Actions/Orders/RecordOrder.php`
> - `app/Http/Controllers/ClientLookupController.php` (new), `app/Http/Controllers/OrderController.php`
> - `app/Http/Requests/Orders/{StoreOrderRequest,UpdateOrderRequest}.php`, `app/Http/Resources/ClientSummaryResource.php` (new)
> - `routes/web.php`, `resources/js/types/domain.ts`, `resources/js/components/orders/order-form.tsx` (the stop-gap fields)
> - `tests/Pest.php`, `tests/Feature/Clients/{RecordOrderClientTest,ClientLookupTest}.php` (new), the order tests that post a client

#### INV-4-c · Clients list, Add client, client page, picker search
**Commit:** `feat(clients): the Clients page, Add client, the client page and the client search (INV-4-c)`

- `GET clients` (`clients.index`, page `clients/index`): `search` (name or city containing it, or the phone key containing its
  digits), `source` (a `ClientSource`), `page`; 25 a page by name, then id; each row with open orders and last order (sub-selects,
  one query). Props `clients`, `filters`, `client_sources`, `cities`, `has_clients`.
- `POST clients` (`clients.store`): `StoreClientRequest` (the AC-1 rules; a phone already used is refused on `phone` with "22 345
  678 is already the phone of Amira Ben Salah." and the error bag carries her id, `phone_taken_by`, for **Open … / Choose … instead**);
  `AddClient` Action; redirect back with the toast "Client created." and the flash `client_created` (a `ClientRef`), which Edit
  order uses to choose her.
- `GET clients/{client}` (`clients.show`, page `clients/show`): `client` (`ClientSummary`), her orders with `OrderBook::list` limited to
  her (`status` default `all` here, 25 a page, newest first), `filters`, `statuses`.
- `GET clients/options` (`clients.options`, JSON): `search`, `limit` (≤ 50, default 20), `include` (the chosen id comes along), A → Z;
  `ClientRef` items. Used by Find a client by name, Edit order and the Orders list filter.

**Tests**
- 1 feature dataset for the list: the six example clients in order with their figures; search by name, city and "22 345"; Source
  Instagram; the page address kept; empty; another workshop's clients never shown.
- 1 feature dataset for Add client: saved with the phone's display form; every AC-1 refusal; a taken phone gives the message and
  `phone_taken_by`; a name already used is saved.
- 1 feature test for the client page: Amira's figures and three orders newest first, the Shipped filter, another workshop's client a
  404, guest redirected.
- 1 options dataset: search by name, phone digits and city, `include`, the limit; 1 contract test (`ClientRef`, the two page props).

> [!example]- Files (12)
> - `app/Actions/Clients/AddClient.php` (new)
> - `app/Http/Controllers/{ClientController,ClientOptionController}.php` (new)
> - `app/Http/Requests/Clients/{IndexClientRequest,StoreClientRequest,ClientOptionsRequest}.php` (new)
> - `app/Http/Resources/ClientRefResource.php` (new), `app/Services/Clients/ClientBook.php` (new), `app/Services/Orders/OrderBook.php`
> - `routes/web.php`, `resources/js/types/domain.ts`, `resources/js/pages/clients/{index,show}.tsx` (stubs)
> - `tests/Feature/Clients/{ClientsPageTest,AddClientTest,ClientPageTest,ClientOptionsTest}.php` (new)

#### INV-4-d · Edit order's client, the order page, Client filter
**Commit:** `feat(clients): Edit order chooses the client; the order page and the Orders list show and filter by her (INV-4-d)`

- `UpdateOrderRequest`: `client_id` required, `WorkshopRule::exists(Client::class)`, "Choose a client." when missing.
  `UpdateOrder` sets `client_id` and copies her name into `orders.client`, under the order's row lock as today.
- `OrderResource` adds `client_id` and `client_details` (`ClientRef`). The client is loaded only for the order page (its client
  line); the lists and Edit order read `client_id` and the name the order keeps, so they stay within the 10-query budget, and Edit
  order's picker fetches the chosen client through `clients.options` (`include`). *(Revised during the build, 2026-11-02: loading the
  client everywhere put the Orders list and Edit order at 11 and 12 queries.)*
- `IndexOrderRequest` and `OrderBook::list()` take `client` (an id of the workshop; an unknown id is ignored); `filters.client`, and
  the prop `client_filter` (the chosen `ClientRef`, so the filter shows her name without loading every client). `totals` still
  ignore every filter.
- `orders/edit` gets `client_sources`, and `cities` as an optional prop the Add client window asks for when it opens.

**Tests**
- 1 feature dataset: Edit order moves the order to Boutique Lina and the copy of the name follows; no client, another workshop's
  client and an unknown id are refused; the order page carries `client_details`.
- 1 feature dataset for the Orders list: Client Amira lists her open order; combined with search and status; the totals unchanged; an
  unknown client id lists everything; the address keeps it.
- Contract tests updated: `Order.client_details`, `OrderFilters.client`, `OrdersIndexProps.client_filter`.

> [!example]- Files (8)
> - `app/Actions/Orders/UpdateOrder.php`
> - `app/Http/Controllers/OrderController.php`, `app/Http/Requests/Orders/{IndexOrderRequest,UpdateOrderRequest}.php`
> - `app/Http/Resources/OrderResource.php`, `app/Services/Orders/OrderBook.php`
> - `resources/js/types/domain.ts`
> - `tests/Feature/Clients/OrderClientTest.php` (new), the order contract tests

#### INV-4-e · Source label and chips, client fields, the windows
**Commit:** `feat(clients): the source label and chips, the client fields, Add client and the client picker (INV-4-e)`

- `SourceLabel` (grey pill, icon + word, "Other · …", nothing for no source), `SourceChips` (one choice, toggle off, a group named
  "Source", 44 px on a phone), `ClientFields` (Name, Phone in the number font with the number keypad and 16 px text, City with the
  `cities` suggestions, Source and Other source; the AC-1 hints and errors), `AddClientDialog` (a panel over the whole screen on a
  phone, **Save client** in a fixed bar; the taken-phone message with **Open …** or **Choose … instead** from `phone_taken_by`; the
  name-known note from the lookup), and `ClientPicker` (search through `useHttp` on `clients.options`, debounced as the material
  picker, A → Z with the grey line, its empty, no-match and error states, an optional "+ Add … as a new client" last option, a full-
  screen panel "Choose a client" on a phone).
- No page uses them yet; every wording is AC-1, AC-3, AC-5 and AC-6's.

**Tests**
- Vitest: the label for each source, "Other · Fashion fair Sousse" and none; the chips choose, toggle off and read as one group; the
  fields show each server error under its field and focus the first; the window keeps what was typed on an error and offers **Open
  Amira Ben Salah**; the picker searches, shows its three states, and the "+ Add "amira" …" option with digits filling Phone.

> [!example]- Files (8)
> - `resources/js/components/clients/{source-label,source-chips,client-fields,add-client-dialog,client-picker}.tsx` (new)
> - `resources/js/components/clients/use-client-options.ts` (new)
> - `resources/js/components/clients/*.test.tsx` (new)

#### INV-4-f · The Clients page and the sidebar entry
**Commit:** `feat(clients): the Clients page in the sidebar (INV-4-f)`

- `clients/index`: header, **Add client** (INV-4-e's window), search and Source kept in the address (partial reloads of `clients` and
  `filters`), the table with the AC-2 columns, the empty, no-match, source-empty, loading and error states, long text cut with "…",
  and the cards and **Filters** panel on a phone. The sidebar gains **Clients** (users icon) after Orders.

**Tests**
- Vitest: the six rows and their figures; each state's words and **Clear filters**; the Source filter in the address; the phone
  cards; the sidebar order.

> [!example]- Files (5)
> - `resources/js/pages/clients/index.tsx`, `resources/js/components/clients/{clients-table,clients-filters}.tsx` (new)
> - `resources/js/components/app-sidebar.tsx`, `resources/js/pages/clients/index.test.tsx` (new)

#### INV-4-g · The client page
**Commit:** `feat(clients): the client page with her orders (INV-4-g)`

- `clients/show`: breadcrumb, header line with the source label, city and the call link, **New order** (to `orders.create` with
  `?client=`), the three figures, the Orders section with Status ("All orders" here) reusing the Orders table without the Client
  column, its no-orders, no-match, loading and error states, and the phone layout (**Call 22 345 678**, figures two to a row, cards).

**Tests**
- Vitest: Amira's page as in AC-4; Ines Mrad with no orders; "Amira Ben Salah has no shipped orders." and **Show all orders**; the
  call link's name.

> [!example]- Files (4)
> - `resources/js/pages/clients/show.tsx`, `resources/js/components/clients/client-header.tsx` (new)
> - `resources/js/components/orders/orders-table.tsx` (a prop to leave out the Client column)
> - `resources/js/pages/clients/show.test.tsx` (new)

#### INV-4-h · New order's Client card and its phone lookup
**Commit:** `feat(clients): New order starts with the client, found by her phone (INV-4-h)`

- `ClientCard` on New order, first, then Products, then Details: the grey box with INV-4-e's fields; the lookup on blur through
  `useHttp` on `clients.lookup` (checking, existing, new, typed-details-differ, lookup-failed with **Try again**), **Use another
  number**, the name-known note with **Choose …**, **Find a client by name** (INV-4-e's picker without the add option), and
  `client_preset` from a client page with the focus in Products. The form sends `client` as `ClientFields`; the error summary "Check
  the client: 3 fields can't be saved." counts the `client.*` errors. Replaces INV-4-b's stop-gap fields.
- Phone layout: the card first, full-width buttons, the fixed **Save order** bar never hiding the focused field.

**Tests**
- Vitest with the lookup mocked: a known phone shows the Existing client block and sends her phone; a new phone shows the green note;
  the typed-details-differ note; lookup failed still saves; **Use another number** clears and focuses Phone; **Choose Amira Ben
  Salah** keeps the lines; the error summary; the preset client from a client page.
- Browser test (golden path): a new client by phone, then a second order for her found by "+216 22 345 678".

> [!example]- Files (6)
> - `resources/js/components/orders/order-form.tsx`, `resources/js/components/clients/client-card.tsx` (new)
> - `resources/js/components/clients/use-client-lookup.ts` (new), `resources/js/pages/orders/create.tsx`
> - `resources/js/components/clients/client-card.test.tsx` (new), `tests/Browser/OrderGoldenPathTest.php`

#### INV-4-i · Edit order's picker, the client line, Client filter
**Commit:** `feat(clients): Edit order's client picker, the client line on the order page and the Client filter (INV-4-i)`

- Edit order: INV-4-e's picker with the order's client chosen and the grey line under it; its "+ Add …" opens the Add client window
  over the order and `client_created` chooses her; the form sends `client_id`.
- Order page: the client line under "Ordered …" (label, city, call link, **All orders of …**), wrapping on a phone.
- Orders list: the **Client** filter after Status (the picker, "All clients" first, the phone in grey), in the address and in
  **Clear filters**, inside **Filters** on a phone.

**Tests**
- Vitest: Edit order sends the chosen client; a new client from the window is chosen; "Choose a client."; the client line with and
  without a source; the Client filter in the address and cleared by **Clear filters**.

> [!example]- Files (8)
> - `resources/js/components/orders/{order-form,order-header,orders-filters}.tsx`
> - `resources/js/pages/orders/{edit,show,index}.tsx`
> - `resources/js/pages/orders/{edit,index}.test.tsx`

### Contract (frozen before dispatch)
```ts
export type ClientSource = 'instagram' | 'facebook' | 'tiktok' | 'whatsapp' | 'website' | 'walk_in' | 'word_of_mouth' | 'other';
export type ClientRef = {
    id: number; name: string; phone: string; city: string; // phone in its display form: "22 345 678"
    source: ClientSource | null; source_label: string | null; source_other: string | null;
};
export type ClientSummary = ClientRef & { orders: number; open_orders: number; last_order_on: string | null }; // 'YYYY-MM-DD'
export type ClientLookup = { client: ClientSummary | null };                     // GET clients/lookup?phone= | ?name=
export type ClientFields = { phone: string; name: string; city: string; source: ClientSource | ''; source_other: string };
export type ClientFilters = { search: string | null; source: ClientSource | null };
export type ClientsIndexProps = { clients: Paginated<ClientSummary>; filters: ClientFilters; client_sources: Option<ClientSource>[];
    cities: string[]; has_clients: boolean };
export type ClientShowProps = { client: ClientSummary; orders: Paginated<Order>; filters: { status: OrderStatusFilter };
    statuses: Option<OrderStatusFilter>[] };
// Changed
Order          += { client_id: number | null; client_details: ClientRef | null };  // details on the order page only
OrderFilters   += { client: number | null };
OrdersIndexProps += { client_filter: ClientRef | null };
OrderCreateProps += { cities: string[]; client_sources: Option<ClientSource>[]; client_preset: ClientSummary | null };
OrderEditProps   += { cities?: string[]; client_sources: Option<ClientSource>[] };  // cities: optional, asked for by the window
OrderFormData: New order sends `client: ClientFields`; Edit order sends `client_id: number`. Errors: client.phone, client.name,
client.city, client.source_other (New order) · client_id (Edit order) · name, phone, city, source_other, phone_taken_by (Add client).
Flash after Add client: `client_created: ClientRef`.
```
Routes: `clients.index` GET clients · `clients.store` POST clients · `clients.show` GET clients/{client} · `clients.options` GET
clients/options (JSON) · `clients.lookup` GET clients/lookup (JSON). `{client}` is route-model bound with the digit constraint.

### Decisions
- **The phone's key, not the typed text, is unique.** One pure rule makes the key and the display form, used by the save, the
  lookup and the search, so they always agree. Tunisian numbers carry 216 in the key so "22 345 678" and "+216 22 345 678" match.
- **The order keeps a copy of the client's name** in `orders.client` instead of reading it through the client everywhere. A client
  can't be renamed (AC-1), so the copy is always right, and the search, the material history and the Delete messages stay as they
  are. If clients become editable one day, the edit must update the copies too (written in ADR-0021).
- **New order sends the card, not an id.** The save finds or creates by the key with `createOrFirst` inside the order's transaction:
  the lookup is only a hint, as INV-12 says. Rejected: sending the looked-up id (stale when someone adds her meanwhile).
- **Old orders are soft-deleted, not removed from the database.** Their cuts and the material history's `cutting` rows point at them
  (restrict); soft-deleting hides them everywhere, keeps the history readable and changes no stock. `client_id` is therefore nullable,
  and PostgreSQL's CHECK keeps every shown order with a client.
- **Picker and lookup are JSON routes** (ADR-0009 rule 5), as the material picker and the move preview: the client list can grow
  past what a page prop should carry.

### Order
Wave 1 (backend, one after another, they share `routes/web.php` and `domain.ts`): INV-4-a → INV-4-b → INV-4-c → INV-4-d.
Wave 2 (frontend, after wave 1's types land): INV-4-e, then INV-4-f ∥ INV-4-g (disjoint pages), then INV-4-h → INV-4-i (both touch
the order form). Merge order is the step order; INV-4-b's stop-gap keeps New order saving until INV-4-h.

### Risks
- **The migration puts away every order of the dev database `laravel` too** (the human's decision) → said in the delivery report
  before `php artisan migrate` is run there; `down()` doesn't bring them back.
- **About 40 order tests post a typed client** → INV-4-b changes `validOrderPayload()` once and the posts in the same commit; the
  factory makes a client named like today's `client`, so read-side tests don't move.
- **Two people add the same phone at once** → the unique key plus `createOrFirst` (a savepoint on PostgreSQL); a dedicated race test.
- **A CHECK only on PostgreSQL** → the Actions always set `client_id`; a feature test asserts no shown order without a client on
  SQLite, the parity suite proves the CHECK.
- **Edit order and New order share `order-form.tsx`** → INV-4-h and INV-4-i run one after the other; the form takes a `mode` for the
  client part.
