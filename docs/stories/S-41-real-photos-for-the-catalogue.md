# S-41 — A real photo on every item of the demo catalogue
> Phase 4 · Priority Could (right after S-40; first to slip to Phase 5) · Size M · Status **Ready** (approved by the human 2026-10-14;
> the tech-lead plans it after S-40) · Type enabler (demo data)
> **Can't start until your Pexels key exists:** you create a free Pexels account and key and put the key in your local settings (never
> committed). The work starts once you say it's there.
> Links: [Roadmap, Phase 4](../ROADMAP.md#now--phase-4-mvp-release-readiness) · [Backlog](../BACKLOG.md) · Depends on:
> [S-40](S-40-realistic-catalogue-100-products.md) (the catalogue these photos belong to) · Decisions: S-40 Q3 (2026-10-14: real photos from
> free photo libraries, one per item, stored with the app), F-39 (photos are shown as stored, no thumbnails), D-1 (the repository is
> private) · Technical plan: added by the tech-lead

## Story
As the workshop owner, I want every material and every product of the demo catalogue to show a real photo of that kind of item, so that
the lists and pages look like a real workshop's Invory when I try them or show them to others.

## Why it matters
With S-40 alone, about 220 rows show the grey placeholder. A real photo of a denim fabric, a metal button or a leather tote makes the
catalogue recognisable at a glance, which is the point of demo data.

## What the photo libraries allow (checked 2026-10-14)
Both libraries let you download, change and use their photos for free, including commercially, with no credit required. Their limits
shape this story:
- **Unsplash** forbids using scripts or robots on its website. Its automatic access (its API) is allowed, but only if each photo is
  shown **from Unsplash's own servers** with a credit and a link to the photographer. So Unsplash photos can be **stored with the app**
  only if a person picks and downloads each one in a browser. Its licence also forbids compiling its photos to build a similar or
  competing service. Invory isn't one.
- **Pexels** offers automatic access with a free key, tied to a Pexels account. The key allows 200 requests an hour. Pexels asks API users
  to credit photographers when possible and to link to Pexels, and forbids redistributing its photos on other photo sites, implying a
  brand's endorsement, or showing identifiable people in a bad light.
- **Your choice (2026-10-14, Q1): Pexels only, through its API.** Unsplash isn't used.

## Acceptance criteria
AC-1 **A photo on every catalogue item.** Given a new database with the demo workshop and the large catalogue (S-40), when someone follows
the setup guide's catalogue step, every material (the 12 demo ones and the 100–120 new ones) and every one of the 100 products has a photo.
A photo someone already uploaded is never replaced. The demo workshop loaded on its own (S-08) stays without photos.

AC-2 **The right kind of item.** Each photo shows the same kind of item as its name. A jacket shows a jacket, "Metal shank button 17mm"
shows metal buttons, and "Denim 12oz Indigo" shows blue denim fabric. Colour, size and design may differ, because these are example
photos. All photos come from Pexels. When no close photo exists, the item gets the closest photo of the same kind of item (any metal snap
hook for "Swivel snap hook 25mm"). One photo may be shared by **at most 3** items of the same kind, such as one thread in three colours.
No photo shows an identifiable face, a visible brand name or logo, or anything offensive.

AC-3 **A licence record for every photo.** The project keeps one readable list with one row per photo: the item's SKU and name, the
library, the link to the photo's page, the photographer, the licence and the date it was read, and the download date. Every stored photo
is in the list, and every row has its photo. The setup guide reads **"Photos provided by Pexels"** and links to the list. No credit
appears on any screen.

AC-4 **Light enough for pages of 25.** Each photo is a JPG, PNG or WebP that passes the same rules as an uploaded photo, and weighs at most
**80 KB**. One page of 25 products therefore carries at most **2 MB** of photos, and all the photos together stay under **20 MB**
(at most 232 photos × 80 KB ≈ 18.6 MB).

AC-5 **Offline and the same every time.** The photos are stored with the app. The catalogue step needs no internet connection, gives
every item the same photo on every run, and copies nothing twice when it runs again.

AC-6 **Used only as demo pictures.** The photos stay inside Invory's private repository and its installs. They are not published as a
separate photo pack, sold or offered for download. If the repository is ever made public, the product-owner raises this story's licence
conditions with you first.

## Example with real numbers
- **Item count:** S-40 creates 12 demo + 100 to 120 new materials, and 100 products, so **212 to 232 photos**. The guide states the exact
  number.
- **One row of the licence record** (shape only; the real values come from the chosen photo):
  `CAT-P-012 · Vegan Leather Crossbody Bag · Pexels · https://www.pexels.com/photo/<id>/ · <photographer> · Pexels licence (read 2026-10-14) · downloaded <date> · 64 KB`
- **On screen:** Products → page 1 of 4 shows 25 rows, each with a photo. Open "Vegan Leather Crossbody Bag": the large photo shows a
  black crossbody bag, next to "Can produce 45" and bottleneck "Swivel snap hook 25mm" (figures from S-40).

## What you will see (UX)
- Lists (Materials, Products, the composition table, the dashboard panels that show pictures) and detail pages show the photo wherever
  they show a picture today. No screen changes.
- The photo's text alternative stays the item's name (today's rule).
- No credit line appears on screens (Q2). Credits live in the licence record and the setup guide.
- Works in dark mode and at phone width as today's photos do.

## Out of scope
- Several photos per product (front, back, detail, worn): not stored (S-40 Q7).
- Photos of people wearing the products: not used (no identifiable faces).
- Photos for the demo workshop loaded on its own (S-08): it stays as approved.
- Changing the photo rules or adding thumbnails (F-39 stays).
- Real photos of your own products: you upload those yourself, as today.
- Unsplash photos (Q1) and credit lines on screens (Q2).

## How you will check it
At acceptance you spot-check **20 photos** picked at random from the licence record. For each one, check that it shows the right kind of
item, with no face and no logo, and that its row is complete. The product-owner checks the counts and sizes from the delivery report.

## Questions for you (answered 2026-10-14)
Q1 Which route, given the libraries' rules? **Answer: (a)** Pexels only, picked automatically through its free API. You create a free
Pexels account and key and put the key in your local settings, which are never committed. **The work can't start until the key exists.**
(Other options were: Pexels plus Unsplash photos you pick by hand in a browser; both libraries, every photo picked by hand.)

Q2 Credit on screens? **Answer: (a)** credit only in the licence record and the setup guide ("Photos provided by Pexels"); nothing on
screens. (Other option was: a "Photo: <photographer> on Pexels" line under catalogue photos on detail pages.)

Q3 When no close photo exists? **Answer: (a)** use the closest photo of the same kind of item; one photo may be shared by up to 3 items of
the same kind. (Other options were: leave that item on the placeholder; every item gets its own photo even when it fits less well.)

Q4 When? **Answer: (a)** Phase 4 as a Could, right after S-40; first item to slip to Phase 5. (Other option was: Phase 5 as a Should.)

## For the tech-lead's sizing
- Whether the developer agents can reach the Pexels API from this machine and download photos, and how long about 220 searches and
  downloads take under the 200-requests-an-hour limit.
- How the key reaches the download without being committed or written into any document.
- How photos are reduced to 80 KB or less and stored with the app, and how they reach a fresh install's public storage.
- How the licence record is kept and checked against the stored photos (AC-3).
- Picking the right kind of item (AC-2) needs a judgement per photo. Say who makes it. The human spot-checks 20 photos at acceptance.
- If the agents can't reach the Pexels API, or the limit makes the work impractical, bring it back as a question for the human. Don't
  switch to another library or to hand-picking.

## Sources (licence terms read 2026-10-14)
[Unsplash licence](https://unsplash.com/license) · [Unsplash terms, prohibited conduct](https://unsplash.com/terms) ·
[Unsplash API guidelines](https://help.unsplash.com/en/articles/2511245-unsplash-api-guidelines) ·
[Unsplash, compiling photos](https://help.unsplash.com/en/articles/2612331-why-can-t-i-compile-photos-from-unsplash-to-replicate-a-similar-or-competing-service) ·
[Pexels licence](https://www.pexels.com/license/) · [Pexels API documentation and guidelines](https://www.pexels.com/api/documentation/)

## Acceptance check (filled in at the end)
| AC | Evidence (test name / screen) | Result |
|---|---|---|
| AC-1 A photo on every catalogue item | | |
| AC-2 The right kind of item | | |
| AC-3 Licence record for every photo | | |
| AC-4 Light enough for pages of 25 | | |
| AC-5 Offline and the same every time | | |
| AC-6 Used only as demo pictures | | |
