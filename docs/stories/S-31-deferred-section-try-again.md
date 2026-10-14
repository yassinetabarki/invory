# S-31 — A section that fails to load offers "Try again"
> Phase 4 · Priority Should · Size S · Status **Ready** (approved by the human 2026-10-14), on hold since 2026-10-14 so S-43 goes first (D-8) · Type feature (was F-48)
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Related: D-7 (dashboard figures load
> just after the frame), S-33 and S-35 (later pages that will load the same way and must reuse this) · Technical plan: added by the tech-lead

## Story
As the workshop owner, I want a part of a page that failed to load to tell me so and let me try again, so that I'm never left looking at
grey placeholders that never go away.

## Why it matters
Four places now show their frame first and fill in a moment later. If that second step fails (the server hiccups, the connection drops),
the placeholders stay forever and the only way out is to reload the whole page. On the dashboard and the product page, that hides the
numbers you came for.

## Where this applies (checked in the app on 2026-10-14, commit `3a0785f`)
| # | Page | What fills in after the frame | Loads together with |
|---|---|---|---|
| 1 | Dashboard | the five figure cards (Total sales, Units sold, Inventory value, Needs restock, Can't produce) and "Top selling products" | each other |
| 2 | Dashboard | "Needs restock", "Recent sales" and the red "can't be produced" alerts | each other |
| 3 | Product page | everything below the breadcrumb: header, the three answer cards, bottleneck, cost breakdown, composition, recent sales | each other |
| 4 | Materials list | the recent history shown in each row's History panel | — |

The material page's own History card, the product edit page and the lists' filters are not in this table: they arrive with the page itself
today (S-33 and S-35 will change that and must reuse this behaviour).

## Acceptance criteria
AC-1 **A clear message instead of endless placeholders.** Given a section from the table above, when its content can't be loaded, then its
placeholder is replaced by its message (see "What you will see") and a "Try again" button. The section's own title stays visible, so you
know what is missing.

AC-2 **Try again reloads only that section.** When you press "Try again", the section shows its loading placeholder again and then its
content. Everything else on the page stays as it was: the chosen period, an open panel, what you typed, the scroll position. Sections that
load together (the "Loads together with" column) come back together, whichever button you press.

AC-3 **It can fail again.** If the second attempt fails too, the message comes back. You can try as often as you like. It never turns into
an endless placeholder.

AC-4 **The rest of the page keeps working.** Sections that did load stay visible and usable. No error page or pop-up covers the screen.

AC-5 **Accessible.** The message is read out once by screen readers. "Try again" is reachable with the keyboard and shows a visible focus
outline. The message carries an icon and words, never colour alone. It works at 375 px wide and in dark mode.

AC-6 **The failure is recorded.** Each failure is written to the application log with the page and section, so the tech-lead can find the
cause.

## Example with real numbers
With the demo data (S-08), you open the Dashboard on "Last 30 days". The frame appears, and "Needs restock" fills in with 4 materials. The
figure cards and the top 5 fail. In their place you see "Couldn't load the key figures." and, inside the "Top selling products" card,
"Couldn't load the top sellers.", each with "Try again". You press "Try again": the five grey placeholders come back, then
"Total sales TND 16,850.000 · 48 sales · last 30 days" and the top 5 starting with Classic White T-shirt, 142 units. "Needs restock" never
moved.

## What you will see (UX)
Every message has the same shape, inside the section's own card or area: a warning icon, one line saying what is missing, the help line
"Check your connection, then try again.", and an outlined "Try again" button. This follows the pattern the composition picker already uses
("Try again").

| Section | Title that stays | Message |
|---|---|---|
| Dashboard figure cards | (the row has no title) | "Couldn't load the key figures." |
| Dashboard "Top selling products" | "Top selling products" | "Couldn't load the top sellers." |
| Dashboard "Needs restock" | "Needs restock" | "Couldn't load the restock list." |
| Dashboard "Recent sales" | "Recent sales" | "Couldn't load the recent sales." |
| Dashboard alerts (above the figure cards) | — | "Couldn't check which products can't be produced." |
| Product page | (the breadcrumb "Products") | "Couldn't load this product." plus a link "Back to products" next to "Try again" |
| Materials list, History panel | "History · <material name>" | "Couldn't load the history." The link "Open material page" stays. |

States:
- **Loading:** the existing grey pulsing placeholders, unchanged.
- **Error:** the message above, replacing the placeholder in the same space, so the page doesn't jump.
- **Retrying:** the placeholder again.
- **Success:** the normal content. No extra success message.
- The dashboard alert message is only shown when the alerts fail. Today nothing is visible while they load, and that stays.

## Out of scope
- A failed filter, sort, period or page change on a list or on the dashboard: those aren't sections that load after the page. Not
  planned; a candidate if your walkthrough (S-30) shows it matters.
- Being signed out while a section loads: you're sent to the sign-in page, as today.
- The composition picker's existing wording ("Materials could not be loaded."): unchanged here; it could join the polish bundle S-38.
- Automatic retries in the background: you decide when to try again.

## Questions for you (answered 2026-10-14)
Q1 Button wording. **Answer: (a) "Try again"**, the same as the composition picker. (Other option was: "Retry".)

Q2 How do you see the error state, since you can't make a section fail by hand? **Answer: (a)** the automated tests, plus screenshots of
the dashboard and the product page in their error state at desktop width, at 375 px and in dark mode, attached to the delivery report.
(Other option was: a development-only way to make a section fail.)

## For the tech-lead's sizing
- One shared behaviour for the four places, so S-33 and S-35 can reuse it.
- What a failure looks like today with debugging on and off (an error pop-up, or endless placeholders), so AC-4 is tested both ways.
- The screenshots chosen in Q2 are part of the delivery report.

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 Message instead of placeholders (4 places) | | |
| AC-2 Try again reloads only that section | | |
| AC-3 Can fail again | | |
| AC-4 Rest of the page keeps working | | |
| AC-5 Accessible, 375 px, dark mode | | |
| AC-6 Failure recorded in the log | | |
