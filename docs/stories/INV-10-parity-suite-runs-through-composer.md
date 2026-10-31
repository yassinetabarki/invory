---
id: INV-10
title: The PostgreSQL check runs to the end, on the right PHP
type: tech
phase: Colour × size variants
priority: Must
size: S
status: Planned
created: 2026-10-31
tags:
  - tech
  - variants
---

# INV-10 · The PostgreSQL check runs to the end, on the right PHP

> [!abstract] Description
> Before a change that touches the database is delivered, the tech-lead runs the whole test suite a second time against PostgreSQL,
> the database the app uses for real (the "parity" check). Its shortcut, `composer test:pgsql`, now stops after five minutes, before
> the suite is done, and it runs on a different PHP than the rest of the checks. This item makes the shortcut run to the end on the
> PHP the project uses, so INV-2 (which changes the stock table) can be checked the normal way. Nothing in the app changes.

> [!info] How it works today
> The suite takes about six minutes on PostgreSQL. `composer test:pgsql` is stopped by Composer's own five-minute limit ("exceeded the
> timeout of 300 seconds"), so it reports a failure that isn't one. The browser check, `composer test:browser`, already switches that
> limit off; the PostgreSQL one doesn't. On this machine, Composer also runs its scripts with Herd's PHP 8.4
> (`C:\Users\Xpardox\.config\herd\bin\php84\php.exe`), even when it is started with the PHP 8.3 the project and `docs/SETUP.md` name,
> so the check doesn't run on the PHP every other local check uses. During INV-1 the tech-lead worked around both by calling `php artisan test`
> directly with the PostgreSQL settings.

## Acceptance criteria
- [ ] **AC-1 · It runs to the end.** `composer test:pgsql` runs the whole suite against the `testing` database however long it takes
  (about six minutes today) and ends with the suite's own result: 2604 tests passed on `main` after INV-1, none stopped by a time limit.
- [ ] **AC-2 · On the project's PHP.** The check runs on PHP 8.3, the version `docs/SETUP.md` gives: either Composer is made to use it,
  or `docs/SETUP.md` says in one short paragraph why Composer picks Herd's PHP 8.4 on this machine, how to see which one it uses
  (`composer --version`), and the one command to run the check on 8.3.
- [ ] **AC-3 · Nothing else changes.** `composer test`, `composer test:browser` and the app behave as before; the guard that refuses a
  PostgreSQL run on any database but `testing` stays.

---

## Technical work
> [!tip] Simple · NON-CRITICAL · backend (config) + docs · 1 commit
> Tech-lead · 2026-10-31 · proposed

- `composer.json`: `test:pgsql` starts with `Composer\\Config::disableProcessTimeout`, as `test:browser` does (backend developer).
- Find why Composer runs Herd's PHP 8.4 here: `composer diagnose`, the `php` Composer's `@php` resolves, and any global Composer
  config or environment that points at Herd. If a project-level fix exists without touching system settings (for example the scripts
  calling `@php` from the PHP that started Composer), make it; otherwise the tech-lead writes the paragraph in `docs/SETUP.md` §5
  with the check command for 8.3 (`DB_CONNECTION=pgsql DB_DATABASE=testing php artisan test --compact` after `php artisan config:clear`).
  No change to Herd, Laragon, `php.ini` or the system PATH.
- **Proof:** AC-1 → the tech-lead's run of `composer test:pgsql` (result line in the delivery report, Docker up) · AC-2 →
  `composer --version` before/after, or the SETUP.md paragraph · AC-3 → `composer test` and `composer test:browser` green, and the
  `tests/TestCase.php` guard untouched.
