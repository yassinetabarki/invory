# S-32 — A duplicate SKU saved at the same moment shows a clear message
> Phase 4 · Priority Should · Size S · Status **Ready** (approved by the human 2026-10-14), on hold since 2026-10-14 so S-43 goes first (D-8) · Type bug (was F-35)
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Related: F-33 (the same protection
> for catalogue names, done 2026-10-09) · Technical plan: added by the tech-lead

## Story
As someone adding materials and products, I want a clear message on the SKU field when another person has just saved the same SKU, so
that I can pick another one instead of landing on a server error page.

## Why it matters
Invory already refuses a SKU that is in use. But if two people save the same new SKU within the same split second, both pass that check
and the second save crashes with a server error. What they typed is lost. It's rare, but it's a crash, and the same gap was already closed
for catalogue names (F-33).

## Acceptance criteria
AC-1 **Materials: a field error, not a crash.** Given two people saving a material with the same SKU at the same moment, when the second
save reaches the server, then it is refused with the message under the SKU field (see "What you will see"), the form stays open, and
everything typed is still there.

AC-2 **Products: the same.** The same holds for products, including the composition rows already entered.

AC-3 **Create and edit.** This holds when adding an item and when editing one to a SKU that someone else has just taken.

AC-4 **One message for both cases.** The message is the same whether the SKU was taken long before (today's check) or at the same moment.

AC-5 **Nothing half-saved.** After the refused save, exactly one item has that SKU. The refused attempt leaves nothing behind: no
material, no product, no composition lines, no "Created" history entry and no stored photo.

AC-6 **Rule unchanged.** SKUs stay unique among items that aren't deleted. A deleted item's SKU can still be reused, as today.

## Example with real numbers
Amira and Karim both receive the new buttons. At 10:02 each fills in "Add material": SKU `MAT-BTN-017`, Metal shank button 17mm, Fastener,
Piece (pc), unit cost 0.35, 900 in stock. Amira clicks "Save material" a fraction of a second before Karim.
Amira sees "Material created." and the material's page. Karim stays on "Add material". Under SKU he sees "This SKU is already used by
another material." His values (0.35, 900) are still in the form, as after any other field error, so he can change the SKU or cancel. The Materials list
shows one Metal shank button 17mm, with one "Created" entry in its history.

## What you will see (UX)
- Where: the SKU field of "Add material", "Edit <material>", "Add product" and "Edit <product>".
- The message, in the same red style as every other field error, with the cursor moved to the SKU field:
  - on materials: "This SKU is already used by another material."
  - on products: "This SKU is already used by another product."
- This replaces today's wording for the normal case, "The sku has already been taken." (AC-4).
- No toast and no pop-up: the field error is enough, as for every other validation error.

## Out of scope
- Whether "ABC-1" and "abc-1" count as the same SKU: unchanged by this story.
- Naming the other item in the message ("already used by Metal shank button 17mm"): not planned.
- Clearer wording for the other standard field errors (for example "The sku field is required."): a candidate for your walkthrough findings
  (S-30).
- Two people editing the same material at once (the last save wins, as today): not planned.

## Questions for you (answered 2026-10-14)
Q1 The message wording. **Answer: (a)** "This SKU is already used by another material." / "This SKU is already used by another product."
for both the normal case and the same-moment case. (Other option was: keep "The sku has already been taken." and only stop the crash.)

## For the tech-lead's sizing
- AC-5 with a photo attached: confirm that a refused save leaves no stored file (it may touch F-43, orphan photo files).
- How the same-moment case is proven, given the human can't reproduce it by hand (the evidence will be automated tests).

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 Materials: field error | | |
| AC-2 Products: field error, composition kept | | |
| AC-3 Create and edit | | |
| AC-4 One message for both cases | | |
| AC-5 Nothing half-saved | | |
| AC-6 Rule unchanged | | |
