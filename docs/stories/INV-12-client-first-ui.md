---
id: INV-12
title: Client first on New order
type: ui
priority: Must
status: Approved
created: 2026-11-01
tags:
  - ui-card
---

# INV-12 · Client first on New order — UI card

> [!abstract] Description
> New order starts with the **client**, and the **phone number** is how Invory finds her. The owner types the number she has just
> received in a message; when she leaves the field, Invory says "Existing client" and shows who it is, or "New client" and asks for
> the name, city and, if known, the source. Name and city are never typed twice for a regular client. A new client is saved together
> with the order, in one go.
> New: the **Client** card at the top of New order (Phone, Name, City, Source), the automatic lookup, the Existing client summary, the
> New client note, the note when typed details differ from the saved client, and the button **Find a client by name**. Changed: the
> Client part of New order (INV-4's switch "Existing client · New client" and its picker leave New order; the picker stays in Edit
> order and in **Find a client by name**), the order of the cards (Client, Products, Details) and the toasts.
> This is the first of three cards that refactor the order. The product search is INV-13; the order tracker, with orders served
> from the finished stock instead of production, is INV-14.
> Left out on purpose: editing a client's saved details from an order, merging clients, looking a client up by name as she types
> (the name picker is a helper), and the product search (INV-13).

> [!info] How it looks today
> Today the client of an order is a name typed on each order, in the Details card, with "e.g. Boutique Lina" as example. INV-4
> (approved, built before this card) turns it into a switch **Existing client · New client**: the owner chooses a side before
> typing, and a new client's phone that already belongs to someone stops the save with "22 345 678 is already the phone of Amira Ben
> Salah.". She has to guess which side she is on, and with 6 clients and 3 called "Mohamed Ben Ali" the name is a poor key.
> The owner takes orders on her phone, often while answering an Instagram or WhatsApp message. The one thing she has in front of
> her is the phone number.

## Pages

Every example uses INV-4's clients. **Amira Ben Salah**: Instagram, Sousse, 22 345 678, 3 orders, 1 open, last order 28 Oct 2026.
**Boutique Lina**: Walk-in / shop, Tunis, 71 234 567. Today is 30 Oct 2026.

### How a client is found
- **By the digits of the phone, nothing else.** "22345678", "22 345 678", "+216 22 345 678" and "22.345.678" all find Amira Ben Salah.
  A number abroad is found by its digits with its country code. The rules for a valid number are INV-4's.
- **The lookup runs when the focus leaves Phone.** A number that is not valid yet shows INV-4's phone message on save, not while
  typing.
- **The save has the last word.** Saving finds or creates the client by the same digits, together with the order, whatever the lookup
  said (another person may have added her meanwhile).
- **A known phone uses the saved client unchanged.** An order never changes a client's saved name, city or source.
- **A name already used is never an error.** INV-4's rule stays.

### New order · the Client card
Board: INV-12 · New order · client first, phone finds the client, new or existing, errors · desktop (Invory App UI canvas)
The page order is now **Client**, then **Products**, then **Details** (Order date, Note). Top to bottom:
1. Header **New order**.
2. The **Client** card: the heading, the line "A phone number belongs to one client. If it is already one of your clients, the order goes
   to them." and, on the right, the outline button **Find a client by name** with a search icon.
3. In the card, a grey box "Client details":
   - **Phone** (number font) with the hint "8 digits, such as 22 345 678. For a number abroad, start with + and the country code.",
     next to **Name** with the hint "The person or the shop, as you call them.".
   - **City**, suggesting the cities already used (INV-4).
   - **Source (optional)**: INV-4's eight chips, hint "Where did this client find you?", **Other** adds "Other source (optional)".
   - Under the box: "The client is saved to your clients when you save the order, if the phone number is new."
4. **Existing client.** When the number belongs to a client, the grey box is replaced by one summary block with a 2 px Denim border:
   - her name (bold) and the pill **Existing client**;
   - the source label, city and phone ("[Instagram] · Sousse · 22 345 678");
   - "3 orders · 1 open · last order 28 Oct 2026";
   - the outline button **Use another number**, which clears Phone, shows the form again and puts the focus in Phone.
   - Under it: "The order uses the details saved for this client. Name, City and Source aren't asked again." No line about saving
     the client is shown: nothing new is saved.
5. **New client.** When the number belongs to nobody, the fields stay and, under Phone, a green note with a user-plus icon: "New
   client. 20 456 789 isn't one of your clients yet." The line under the box then reads the short form "Saved to your clients when
   you save the order."
6. **Typed details differ.** If the owner typed a Name or City before the phone and the number belongs to a client, the summary shows
   and, in an amber note under it: "You typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben Salah (Sousse), so the order goes to
   her. Her saved details stay as they are." **Use another number** is the way out.
7. **The name is known, with another number.** INV-4's information note stays, under Name: "You already have a client named Amira
   Ben Salah (Sousse · 22 345 678)." with **Choose Amira Ben Salah**. Choosing shows her as Existing client; the products and the
   note stay. It never blocks saving.
8. **Save order** saves the order and, for a new number, the client together, or nothing. Toast for a new client: "Order recorded.
   Ines Mrad is now one of your clients." For an existing client: "Order recorded." The Orders list opens, as today.

### Find a client by name
Board: INV-12 · New order · client first, phone finds the client, new or existing, errors · desktop (Invory App UI canvas)
- The outline button in the Client card opens INV-4's picker, unchanged: the search "Search clients" (name, phone digits or city),
  the options A → Z with "Instagram · Sousse · 22 345 678" in grey. Its last option "+ Add … as a new client" is not offered here:
  a new client is typed in the Client card.
- Choosing a client shows her as Existing client, with her phone filled in. This is for a client whose number the owner doesn't have.

### From a client page
- **New order** on a client page (INV-4) opens New order with that client shown as Existing client; the focus starts on **Search
  products** (INV-13).

### Edit order
- The Client field stays INV-4's picker, with no phone lookup and no switch; an order can be moved to another client at any stage, as
  INV-4 says. Its option "+ Add … as a new client" and the Add client window are unchanged.

## States

- **Empty:** Phone, Name, City, the eight Source chips, nothing chosen; the hints.
- **Checking:** under Phone, a spinner and "Checking 22 345 678…"; nothing blocks typing.
- **New client:** the green note "New client. 20 456 789 isn't one of your clients yet."; the fields stay.
- **Existing client:** the summary block with **Use another number** and "The order uses the details saved for this client. Name, City
  and Source aren't asked again."
- **Typed details differ:** the summary and the amber note "You typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben Salah
  (Sousse), so the order goes to her. Her saved details stay as they are."
- **Name known:** the information note and **Choose Amira Ben Salah**; it never blocks.
- **Lookup failed:** an amber note under Phone, "Couldn't check this number. Save the order and Invory checks it again.", and **Try
  again**; saving still works.
- **Client picker:** "Search clients"; no match "No client matches "Ines Mrad"."; none yet "No clients yet."; error "Couldn't load the
  clients. Try again." and **Try again**.
- **Errors on save:** under their field, the focus on the first one, and at the top "Check the client: 3 fields can't be saved."
- **Saving:** **Save order** shows a spinner and keeps its label.
- **Success:** "Order recorded. Ines Mrad is now one of your clients." (new client) or "Order recorded." (existing client).
- **Save failed:** "Couldn't save the order. Try again." Everything typed stays; an error in the products saves no client either.

- **Error messages** (INV-4's, under their field): "Enter the client's phone." · "Enter 8 digits, such as 22 345 678, or a number
  abroad starting with +." · "Enter the client's name." · "Keep the name to 100 characters or fewer." · "Enter the client's city." ·
  "Keep the city to 60 characters or fewer." · "Keep it to 40 characters or fewer." (Other source). Source needs nothing. A phone that
  already belongs to a client is **no longer an error** in New order: it is the Existing client.
- **Nothing blocks typing** while the lookup runs. If the owner changes the phone after a lookup, the lookup runs again when she
  leaves the field.
- **Long content:** a 100-character name is cut with "…" in the summary, in full on the order page.

## Wording
- New order: "New order" · "Client" · "A phone number belongs to one client. If it is already one of your clients, the order goes to
  them." · "Find a client by name" · "Client details" · "Phone" · "Name" · "City" · "Source (optional)" · "Existing client" · "Use
  another number" · "New client. 20 456 789 isn't one of your clients yet." · "The order uses the details saved for this client.
  Name, City and Source aren't asked again." · "The client is saved to your clients when you save the order, if the phone number is
  new." · "Saved to your clients when you save the order." · "Checking 22 345 678…" · "Couldn't check this number. Save the order
  and Invory checks it again." · "Try again" · "3 orders · 1 open · last order 28 Oct 2026" · "Check the client: 3 fields can't be
  saved."
- The note: "You typed "Amina B." in Tunis. 22 345 678 belongs to Amira Ben Salah (Sousse), so the order goes to her. Her saved
  details stay as they are."
- Kept from INV-4: the hints and errors above, "Search clients", "No client matches "Ines Mrad".", "No clients yet.", "You already
  have a client named Amira Ben Salah (Sousse · 22 345 678).", "Choose Amira Ben Salah".
- Toasts: "Order recorded. Ines Mrad is now one of your clients." · "Order recorded." · "Order updated." (Edit order).
- Gone from New order: "Existing client · New client" (the switch), "Choose a client…", "+ Add "…" as a new client", "+ New client",
  "Choose a client.", "22 345 678 is the phone of Amira Ben Salah (Instagram · Sousse).", "22 345 678 is already the phone of Amira
  Ben Salah." (they stay in Edit order, the Add client window and the Clients page, where they are INV-4's).

## Phone layout
Board: INV-12 · New order, order page, Cancel units, Orders list · 375 (Invory App UI canvas)
- **New order:** the Client card first. **Find a client by name** full width (44 px), then Phone, Name, City and the Source chips two
  to a row (44 px), one under another in the grey box. The lookup notes sit under their field. Phone's text is 16 px, so the screen
  doesn't zoom, and it opens the number keypad.
- **Existing client:** the summary block, then **Use another number** full width (44 px).
- **Save order** in the bar fixed at the bottom, as in today's forms; the bar never hides the field with the focus.
- At 320 px nothing scrolls sideways.
