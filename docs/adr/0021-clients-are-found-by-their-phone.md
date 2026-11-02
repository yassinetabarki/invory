# ADR-0021 — Clients are found by their phone

- **Status:** Accepted · 2026-11-02 (approved by the human with the Technical work of INV-4; tech-lead)
- **Builds on:** ADR-0013 (every record belongs to a workshop; another workshop's record is a 404), ADR-0009 rule 5 (small
  session-authenticated JSON routes for typeaheads and previews), ADR-0014 (cuts and the material history point at their order)

## Context
INV-4 makes every order belong to a **client**: a name, a phone, a city and, if known, a source. The workshops sell mostly to people
who order by message, so the phone is the key: no two clients of a workshop share one, a name may repeat, and New order (INV-12's
Client card, built within INV-4) finds the client by the phone the owner types, or adds her with the order. A client is never edited
or removed. The orders recorded before clients are test data and are removed when INV-4 ships (the human's decision, 2026-11-02).

## Decision
1. **One phone rule, in pure PHP.** `App\Domain\Clients\PhoneNumber::parse()` gives the **display** form and the **key**. Eight
   digits, or +216 / 00216 and eight digits, is Tunisian: shown "22 345 678", keyed "21622345678". A number starting with + and
   another country code, 8 to 15 digits, is shown as typed with single spaces and keyed by its digits. Everything else is refused.
   The save, the lookup and the search all use it, so they always agree.
2. **`clients`**: `workshop_id`, `name` (100), `phone` (the display form), `phone_key`, `city` (60), `source` (`ClientSource`,
   nullable), `source_other` (40, nullable), `user_id`, timestamps; **unique (`workshop_id`, `phone_key`)**. No soft deletes: nothing
   removes a client. Setting `phone` on the model sets `phone_key` (a mutator), whoever writes.
3. **`orders.client_id`** → clients (`restrictOnDelete`), and **`orders.client` keeps the client's name**, copied by the Actions.
   A client can't be renamed, so the copy is always right, and the search, the material history ("Cutting · Boutique Lina") and the
   Delete messages keep reading `orders.client` unchanged. **If clients ever become editable, renaming one must update its orders'
   copies in the same transaction.** The relation is `Order::clientRecord()`: `client()` would be shadowed by the column.
4. **New order sends the Client card, not a client id.** The save parses the phone and finds the client by the key, or creates her
   with `createOrFirst` inside the order's transaction (a savepoint on PostgreSQL), so a client added meanwhile by someone else is
   found, never duplicated. The lookup the card makes when the phone loses focus is only a hint. Edit order sends `client_id`.
5. **The orders recorded before clients are soft-deleted by the migration**, not removed: their cuts and the material history's
   `cutting` rows point at them (`restrictOnDelete`). Soft-deleting hides them everywhere and changes no stock, history, sale or
   finished stock. So `client_id` is nullable; PostgreSQL's CHECK `client_id IS NOT NULL OR deleted_at IS NOT NULL` keeps every order
   still shown with a client, and the composite key (`workshop_id`, `client_id`) → clients (`workshop_id`, `id`) keeps it in its
   workshop. `down()` doesn't bring those orders back.
6. **Lookup and picker are JSON routes** (ADR-0009 rule 5): `clients.lookup` (by phone or by name) and `clients.options` (search,
   A → Z, a small limit), as the material picker and the move preview.

## Consequences
- An order is read with its client in one eager load; the Orders list filters by `client_id`.
- A phone number abroad typed without + is refused: the owner adds the + and the country code.
- Tests make orders with `Order::factory()`, which makes a client named like the order's `client`, so read-side tests are unchanged.

## Alternatives rejected
- **Reading the name through the client everywhere** (dropping `orders.client`): touches the search, the history join, the Delete
  messages and every order test, for no behaviour the card asks for.
- **Sending the looked-up client id from New order**: stale when someone adds the same phone between the lookup and the save.
- **Deleting the old orders for good**: impossible without deleting material history, which the card says stays.
