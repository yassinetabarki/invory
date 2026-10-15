---
id: S-46
title: Name your workshop
type: feature
phase: 4
priority: Should
size: S
status: Ready
created: 2026-10-14
tags:
  - story
  - phase-4
---

# S-46 · Name your workshop

> [!abstract] Description
> As a **workshop owner**, I want **to give my workshop a name when I sign up, see it while I work and change it in Settings**, so that **I
> always know which workshop's figures I'm looking at**. Later, people who join a workshop will see which one they joined.
> The name is only a label: signing in still uses your email address and password.

> [!info] How it works today
> Nothing yet. "Create an account" asks for Name, Email address, Password and Confirm password. The account menu at the bottom of the
> sidebar shows your name, and your email address once it is open. Settings lists "Profile", "Security" and "Appearance", then the
> "Catalogue" group ("Units", "Material categories", "Product categories"). No screen shows a business name, so once each account has its
> own workshop, nothing tells you which one you are in.

## Acceptance criteria
- [ ] **AC-1 · Name at sign-up.** "Create an account" has a new field "Workshop name" right after "Name", with the placeholder "e.g. Atelier
  Sami". It is required: left empty, it shows "The workshop name field is required." and nothing is created. Names up to 100 characters are
  accepted; a longer one shows "The workshop name field must not be greater than 100 characters.".
- [ ] **AC-2 · Shown while you work.** Given Sami signed up with the workshop name "Atelier Sami", the account menu at the bottom of the
  sidebar shows "Atelier Sami" under "Sami", on every page, at phone width (375 px) and in dark mode. A long name is cut short with "…" and
  never makes the sidebar wider; the full name shows on Settings → "Workshop".
- [ ] **AC-3 · Rename it in Settings.** Settings has a new link "Workshop" above the "Catalogue" group. Its page shows one field, "Workshop
  name", and a "Save" button. Saving "Atelier Sami Tunis" shows the toast "Workshop updated." and the sidebar shows the new name at once.
  The same "required" and "100 characters" messages as AC-1 apply.
- [ ] **AC-4 · Workshops that already exist get a name.** A workshop created before this story, including the one that holds today's data,
  is named "My workshop" until someone renames it. On a fresh install, the demo workshop of `test@example.com` is named "Demo workshop".
- [ ] **AC-5 · A name is not an identity.** Two workshops may have the same name. Changing the name changes no data, no figure and no way
  of signing in.
