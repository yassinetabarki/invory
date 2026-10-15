---
id: S-44
title: Each workshop sees only its own materials, products and sales
type: feature
phase: 4
priority: Must
size: M
status: Acceptance
created: 2026-10-14
tags:
  - story
  - phase-4
---

# S-44 · Each workshop sees only its own materials, products and sales

> [!abstract] Description
> As a **workshop owner**, I want **my account to open only my own workshop's materials, products and sales**, so that **I can use
> Invory without seeing another business's figures, and without anyone else seeing or changing mine**.
> From now on every account belongs to one workshop (your company). Everything you record belongs to the workshop, not to you as a
> person: materials and their stock and history, products and their compositions, sales, photos, and every figure built from them
> (unit cost, can produce, bottleneck, dashboard). Signing up creates a new, empty workshop with you as its only member. Invory is built so
> that later several people can work in the same workshop and share its data, but inviting people and roles are not part of this story.
> Units and categories become per workshop in S-45, and the workshop gets a visible name in S-46.

> [!info] How it works today
> Anyone can create an account: "Register" on the welcome page, or "Sign up" under "Log in to your account". The form asks for Name, Email
> address, Password and Confirm password, then "Create account" and a confirmation email. After that, **every account sees and changes the
> same data**. On a fresh install with the demo workshop, a brand-new account opens on the 12 demo materials (inventory value
> TND 10,544.300), the 7 demo products and the 60 demo sales that belong to `test@example.com`, and it can edit or delete any of them. A
> SKU can be used only once in the whole installation, so a second business can't even give its denim the SKU "MAT-DEN-012".
> "Delete account" in Settings → "Profile" warns that "all of its resources and data will also be permanently deleted", but it deletes only
> the person: the materials, products and sales stay.

## Acceptance criteria
- [ ] **AC-1 · Signing up opens an empty workshop.** Given a fresh install with the demo workshop of `test@example.com` (12 materials,
  7 products, 60 sales), when Sami signs up as `sami@atelier-b.test` and confirms his email address, then Materials shows "No materials
  yet", Products shows "No products yet", Sales shows "No sales yet", and the dashboard shows Total sales **TND 0.000** ("0 sales · last 30
  days"), Units sold **0**, Inventory value **TND 0.000** ("0 materials"), Needs restock **0** and Can't produce **0** ("of 0 products").
  `test@example.com` still sees its 12 materials, 7 products and 60 sales. Signing up stays open to anyone, from "Register" and "Sign up"
  as today, and every sign-up gets its own new, empty workshop.
- [ ] **AC-2 · Every screen shows only your workshop.** Sami adds the material "Cotton canvas natural" (SKU `B-CAN-001`, Fabric, Metre,
  unit cost 6, stock 40) and the product "Canvas Tote" (SKU `B-TOT-001`, Bag, Active, labor cost 5, overhead left empty, 0.8 m of the canvas
  with 0 % waste). Then:
  - Sami sees 1 material, Inventory value **TND 240.000** ("1 material"), and Canvas Tote at unit cost **TND 9.800**, can produce **50**,
    limited by "Cotton canvas natural".
  - In Sami's composition picker, typing "Denim" shows "No material found.", and typing "canvas" shows only "Cotton canvas natural", never
    the demo's "Cotton canvas 10oz Natural". "Record a sale" and the sales log's product filter offer only Canvas Tote.
  - `test@example.com` still sees Inventory value **TND 10,544.300** ("12 materials"), **4** materials that need a restock, and Denim Jacket
    at **TND 35.754**, **112** units, limited by "Metal shank button 17mm". None of Sami's records appear anywhere in its lists, searches,
    filters, dashboard panels (top selling products, recent sales, needs restock, can't-produce alerts), material history or "what you can
    make with it". The same holds the other way round.
- [ ] **AC-3 · Another workshop's page shows "Not Found".** When Sami opens the address of `test@example.com`'s Denim Jacket, its Edit page,
  or the page of "Denim 12oz Indigo", he sees the same "404 Not Found" page as for a product that was deleted. Nothing on it tells him the
  record exists, not even its name. The same holds when `test@example.com` opens the address of Canvas Tote. Photos follow the same rule:
  another workshop's photos never appear on Sami's screens, and a photo's address can't be guessed from its record's name or number.
- [ ] **AC-4 · Another workshop's records can't be changed.** Anything sent from Sami's account for `test@example.com`'s records is refused
  as if the record didn't exist, and changes nothing:
  - editing or deleting a material, product or sale, restocking, or changing a photo is refused with "Not Found", like AC-3;
  - adding "Denim 12oz Indigo" to the Canvas Tote composition, or recording a sale of Denim Jacket, is refused with the same field error
    as for a material or product that doesn't exist, and nothing is saved. Sami's screens never offer these records, because the picker
    and "Record a sale" list only his own workshop's items.

  Afterwards the demo still shows Denim Jacket at TND 35.754, 112 units, and 60 sales.
- [ ] **AC-5 · The same SKU in two workshops.** Sami can save a material with the SKU "MAT-DEN-012", which the demo's "Denim 12oz Indigo"
  already uses: he sees "Material created.". The same holds for product SKUs. Inside one workshop a SKU still can't be used twice, exactly as
  today.
- [ ] **AC-6 · The data belongs to the workshop, not to the person.** Given two accounts in the same workshop (today only the move of
  existing data in AC-7 creates this; inviting people comes later), a material one of them adds appears for the other at once, and its
  history says who did it ("by <name>"). Deleting an account deletes only the person: every material, product and sale of the workshop
  stays, for the other account, and also when it was the workshop's only account. Settings → "Profile" says so: under "Delete account"
  the text reads "Delete your account. Your workshop's data stays.", and the confirmation reads "Once your account is deleted, you can no
  longer sign in with it. Your workshop's materials, products and sales are not deleted. Please enter your password to confirm you would
  like to permanently delete your account."
- [ ] **AC-7 · Nothing that exists today is lost.** When this change is installed, every account that already exists joins one workshop that
  holds everything recorded so far. Each account sees exactly what it saw before, with the same figures: no material, product, composition,
  sale, history entry or photo is lost, moved or changed. On a fresh install, the demo workshop belongs to `test@example.com` and shows the
  same figures as before (12 materials worth TND 10,544.300, 7 products, 60 sales).

---

## Technical work
> [!warning] Complex · CRITICAL · backend + frontend · 5 commits
> Tech-lead · 2026-10-14 · delivered 2026-10-15
> Why complex: two migrations that move every existing record into a workshop, a new rule on every query, policy and validation, and
> new business rules (R1–R6, [ADR-0013](../adr/0013-one-workshop-per-account.md), status Proposed).

### In short
A new `workshops` table holds the business. Each account points to exactly one workshop, and each material, product, composition line,
stock-history entry and sale is stamped with its workshop. One shared piece of code adds "only this workshop" to **every** database
question the app asks, including the lookup behind an address like `/products/12`, so another workshop's record is simply "not found"
without each screen having to remember it. The steps go in this order: accounts get a workshop and sign-up creates one (a); records get a
workshop and the existing data moves into one, losing nothing (b); then every screen reads only its own workshop (c); then every address,
action and form refuses another workshop's records, and SKUs become unique per workshop (d). The "Delete account" wording (e) is separate
and can go first. Tests walk every page and every address of the app as a second workshop, so a page or route added later that forgets the
rule fails the build.

### Steps

| Step       | What it does                                                       | Proves                               | Lane         | Who      | Size                     |
| ---------- | ------------------------------------------------------------------ | ------------------------------------ | ------------ | -------- | ------------------------ |
| **S-44-a** | Every account belongs to a workshop; sign-up creates one           | AC-1 (sign-up part), AC-7 (accounts) | CRITICAL     | Backend  | ~150 lines               |
| **S-44-b** | Every record belongs to a workshop; existing data moves into one   | AC-7                                 | CRITICAL     | Backend  | ~380 lines               |
| **S-44-c** | Every screen reads only its own workshop                           | AC-1, AC-2, AC-6                     | CRITICAL     | Backend  | ~80 lines (+ tests ~350) |
| **S-44-d** | Another workshop's records are Not Found; SKUs unique per workshop | AC-3, AC-4, AC-5                     | CRITICAL     | Backend  | ~170 lines               |
| **S-44-e** | Delete account keeps the workshop's data                           | AC-6 (wording)                       | NON-CRITICAL | Frontend | ~10 lines                |

Every step keeps `composer test` and `npx vp test` green on its own; S-44-b to S-44-d also run `composer test:pgsql` and
`composer test:browser` (golden path).

#### S-44-a · Every account belongs to a workshop; sign-up creates one
**Commit:** `feat(workshops): every account belongs to a workshop; sign-up creates one (S-44-a)`

**Tests**
- 1 sign-up happy path: a new workshop, with the account as its sole member.
- 1 test: two sign-ups → two workshops.
- 1 rollback: the account fails → no workshop left.
- 1 migration dataset: accounts exist → all join one workshop · empty database → none.

> [!example]- Files (10)
> - `app/Models/Workshop.php`
> - `app/Models/User.php`
> - `app/Actions/Workshops/CreateWorkshop.php`
> - `app/Actions/Fortify/CreateNewUser.php`
> - `database/migrations/2026_10_15_100000_create_workshops_table.php`
> - `database/factories/WorkshopFactory.php`
> - `database/factories/UserFactory.php`
> - `database/seeders/DatabaseSeeder.php`
> - `tests/Feature/Auth/RegistrationTest.php`
> - `tests/Feature/Workshops/WorkshopMembershipMigrationTest.php`

#### S-44-b · Every record belongs to a workshop; existing data moves into one
**Commit:** `feat(workshops): every record belongs to a workshop; existing data moves into one (S-44-b)`

**Tests**
- 1 migration test: every row, soft-deleted included, joins the one workshop; counts and Denim Jacket TND 35.754 / 112 unchanged; rollback.
- 1 stamp dataset: root takes the current workshop · child takes its parent's · no workshop → refused.
- 1 fresh-install assertion: demo data in `test@example.com`'s workshop.
- 1 PostgreSQL-only test: a composition line across workshops is rejected by the database.

> [!example]- Files (34, plus the rollback lists of older migration tests)
> - `app/Services/Workshops/CurrentWorkshop.php`
> - `app/Models/Concerns/BelongsToWorkshop.php` (relation + stamping only)
> - `app/Models/{Material,Product,ProductComponent,StockMovement,Sale}.php`
> - `app/Exceptions/MissingWorkshopException.php`
> - `app/Actions/Materials/CreateMaterial.php`
> - `app/Actions/Products/CreateProduct.php`
> - `app/Actions/Products/SyncProductComponents.php`
> - `app/Actions/Materials/AppendStockMovement.php`
> - `app/Actions/Sales/RecordSale.php`
> - `app/Providers/AppServiceProvider.php`
> - `database/migrations/2026_10_15_100100_add_workshop_to_owned_tables.php`
> - `database/factories/{Material,Product,ProductComponent,StockMovement,Sale}Factory.php`
> - `database/factories/Concerns/DefaultWorkshop.php`
> - `database/factories/UserFactory.php`
> - `database/seeders/DemoProductsSeeder.php`
> - `database/seeders/{DatabaseSeeder,DemoCatalogSeeder,DemoSalesSeeder,DemoStockHistorySeeder}.php`
> - `database/seeders/Concerns/ResolvesDemoWorkshop.php`
> - `tests/Feature/Workshops/OwnedDataMigrationTest.php`
> - `tests/Feature/Workshops/WorkshopStampTest.php`
> - `tests/Feature/Seeders/FreshInstallDemoTest.php`
> - `tests/Feature/Performance/MaterialRestockIndexTest.php` (index-name lines)
> - `tests/Feature/Products/ProductMigrationsTest.php` (index-name lines)
> - `tests/Feature/Photos/PhotoWritesTest.php` (index-name lines)
> - the rollback lists of older migration tests

#### S-44-c · Every screen reads only its own workshop
**Commit:** `feat(workshops): every screen reads only its own workshop (S-44-c)`

**Tests**
- AC-1 test: Sami's empty lists and dashboard zeros; demo keeps 12 / 7 / 60.
- AC-2 test: Sami's figures TND 240.000, TND 9.800, 50; picker "Denim" / "canvas"; sale products; demo's TND 10,544.300, 4 restock,
  TND 35.754 / 112.
- 1 leak scan over every signed-in GET page with deferred props.
- AC-6 test: shared workshop + "by <name>"; deleting the only account keeps the data.
- 3 arch rules.

> [!example]- Files (7)
> - `app/Models/Concerns/BelongsToWorkshop.php` (global scope)
> - `app/Models/Scopes/WorkshopScope.php`
> - `tests/Feature/Workshops/WorkshopIsolationTest.php`
> - `tests/Feature/Workshops/WorkshopLeakScanTest.php`
> - `tests/Feature/Workshops/SharedWorkshopTest.php`
> - `tests/Feature/Workshops/WorkshopStampTest.php` (adapted: a sale finds its product through the scope)
> - `tests/Arch/WorkshopScopeTest.php`

#### S-44-d · Another workshop's records are Not Found; SKUs unique per workshop
**Commit:** `feat(workshops): another workshop's records are Not Found; SKUs unique per workshop (S-44-d)`

**Tests**
- 1 router-driven dataset: every route with a bound record, as Sami on the demo's records → 404, no name on the page, nothing changed:
  Denim Jacket TND 35.754 / 112, 60 sales.
- 1 body-id dataset: demo material in Sami's composition, demo product in Sami's sale → the field error is identical, message for message,
  to the one for an id that doesn't exist; nothing saved.
- 1 photo-address test.
- 1 SKU dataset: other workshop accepted "Material created." · same workshop refused · deleted SKU reusable; materials and products.
- 1 policy dataset: same workshop allowed · other workshop 404 · guest.

> [!example]- Files (17)
> - `app/Policies/{Material,Product,Sale}Policy.php`
> - `app/Validation/WorkshopRule.php`
> - `app/Http/Requests/Materials/{Store,Update}MaterialRequest.php`
> - `app/Http/Requests/Materials/StoreRestockRequest.php`
> - `app/Http/Requests/Products/{Store,Update}ProductRequest.php`
> - `app/Http/Requests/Sales/UpdateSaleRequest.php`
> - `tests/Feature/Workshops/CrossWorkshopAccessTest.php`
> - `tests/Feature/Workshops/SkuPerWorkshopTest.php`
> - `tests/Feature/Workshops/WorkshopPoliciesTest.php` (Feature, not Unit: Unit tests have no database here)
> - `tests/Arch/WorkshopScopeTest.php` (validation rule)
> - `tests/Feature/Workshops/WorkshopLeakScanTest.php` (photo addresses; now loads the deferred props, see Progress)
> - `tests/Feature/Products/ProductWriteTest.php`, `tests/Feature/Routes/RoutesTest.php` (test policy doubles return `Response`)

#### S-44-e · Delete account keeps the workshop's data
**Commit:** `feat(settings): delete account keeps the workshop's data (S-44-e)`

**Tests**
- 1 render test: both texts, exact wording.

> [!example]- Files (2)
> - `resources/js/components/delete-user.tsx`
> - `resources/js/components/delete-user.test.tsx`

### Contract (frozen before dispatch)
- **No change to `resources/js/types/domain.ts`, page props, routes or route names.** `auth.user` is already an explicit allow-list in
  `HandleInertiaRequests::authUser()`, so `workshop_id` never reaches the browser. S-46 adds the workshop name as a shared prop.
- **Schema:** `workshops (id, timestamps)`. `users.workshop_id` and `workshop_id` on `materials`, `products`, `product_components`,
  `stock_movements`, `sales`: `foreignId` → `workshops`, NOT NULL, `restrictOnDelete`, never in `$fillable`. Indexes: `materials_sku_unique`
  and `products_sku_unique` keep their names on `(workshop_id, sku) WHERE deleted_at IS NULL`; `materials_restock_idx` on
  `(workshop_id, name, id)` with the same predicate; the sales log index on `(workshop_id, sold_on, id)`. PostgreSQL only:
  `unique(workshop_id, id)` on `materials` and `products`, and composite FKs `product_components (workshop_id, product_id)`,
  `(workshop_id, material_id)`, `sales (workshop_id, product_id)`, `stock_movements (workshop_id, material_id)`, alongside today's FKs.
- **PHP:**
  - `CurrentWorkshop::id(): ?int`: the explicit workshop if one is set, else the signed-in user's `workshop_id`, else null.
  - `CurrentWorkshop::run(Workshop|int $workshop, Closure $callback): mixed`: runs the callback with that workshop.
  - `CreateWorkshop::handle(): Workshop`.
  - `BelongsToWorkshop`: `workshop(): BelongsTo`, global scope `WorkshopScope`, and a `creating` hook that stamps the current workshop
    and throws when there is none.
  - `WorkshopRule::exists(class-string<Model> $model): Exists` and `WorkshopRule::unique(class-string<Model> $model, string $column): Unique`,
    both limited to the current workshop and to non-deleted rows.
  - Policies' `view`/`update`/`delete` return `Response`: allow or `denyAsNotFound()`.

### Decisions
- **One shared scope, not hand-written filters.** The global scope also covers subqueries, eager loads, `toBase()` aggregates (inventory
  value on PostgreSQL) and route-model binding. Another workshop's address is therefore the plain 404 with no per-controller code. Policies
  check the workshop again and answer `denyAsNotFound` (404, never 403) as a second layer. Details and rejected alternatives: ADR-0013.
- **No workshop → no filter** (seeders, migrations, `invory:stock:verify`, test set-up). Web requests always have one, because every data
  route is behind `auth` (RoutesTest).
- **Factories** put new rows and users in the current workshop, else the first one, else a new one. The ~45 existing feature tests keep
  their single-workshop meaning, and two-workshop tests create the second workshop explicitly.
- **A composition line, history entry or sale takes its parent's workshop** in the Action (`SyncProductComponents`, `AppendStockMovement`,
  `RecordSale`), not the request's. A new material or product takes its **actor's** workshop in `CreateMaterial` / `CreateProduct`
  (the Actions already receive the actor), so an Action called outside a request still stamps the right workshop. The trait's hook only
  fills what nobody set, from the current workshop, and throws `App\Exceptions\MissingWorkshopException` when there is none.
- **The data-migration test with rows runs on PostgreSQL** (parity suite, a gate for this story). On SQLite the table rebuild would fire the
  foreign keys pointing at the rebuilt tables (F-59), so SQLite checks the schema (NOT NULL, foreign key, index columns and predicates) on
  an empty database only.
- **Ids inside a form** (a composition's material, a sale's product, the sales filter `product_id`, `record_product_id`) are treated
  exactly like an id that doesn't exist: the same field error, or an empty filter, as today. Answering differently would reveal that the
  record exists. The product-owner chose this (AC-4 as reworded); the tests compare the message with the "doesn't exist" one.
- **Demo data** goes in `test@example.com`'s workshop: `DatabaseSeeder` runs the demo seeders inside `CurrentWorkshop::run()`. The demo
  seeders called alone (`prepareDemo()`) use the current workshop, else `test@example.com`'s, else the first one, else a new one.
  `DemoCatalogSeeder` keys `updateOrCreate` on `(workshop_id, sku)`. `DemoSalesSeeder` sets `workshop_id` on its raw insert and skips only
  when **that workshop** already has sales. The golden-path test (S-08) signs in with a factory user, who joins the demo workshop.
- **Photos:** `UploadedFile::store()` already names files with 40 random characters, so no code change; S-44-d adds a test that proves it.
- **Units and categories stay shared until S-45**, which ships with S-44.

### Order
- **Wave 1:** S-44-a (BE) ∥ S-44-e (FE): disjoint files.
- **Wave 2:** S-44-b (BE), after S-44-a is committed.
- **Wave 3:** S-44-c (BE), after S-44-b is committed.
- **Wave 4:** S-44-d (BE), after S-44-c is committed.
- **Checkpoints:** after reviewing a wave, the tech-lead returns a wave report (verdicts, gates, files to stage, commit messages) and
  stops. The main session gets the human's yes, commits, and only then asks for the next wave, so every commit has been gate-checked on its
  own.
- **Where:** this worktree (`.claude/worktrees/user-card-company-products-b74228`, branch `claude/user-card-company-products-b74228`),
  with its own `vendor/`, `node_modules/`, `public/storage` link and a git-ignored `.env` whose `DB_DATABASE` is `testing`, never `laravel`.
  Gates run on Laragon PHP 8.3 with `ext-sockets` loaded for the session only (plain `php` is Herd 8.4).
- **How S-45 plugs in:** it adds `workshop_id` to `units` and the two category tables, puts the trait on their models (the arch test's
  allow-list then only has `User` and `Workshop`), extends `CreateWorkshop` to copy the standard lists, scopes `ReferenceVersion`'s cache key
  and `UniqueActiveName`'s `DB::table` check per workshop, adds composite FKs `materials (workshop_id, unit_id | category_id)` and
  `products (workshop_id, category_id)`, and adds the three settings parameters to the route dataset. S-45 is planned once S-44-c is
  approved.
- **How S-46 plugs in:** it adds `workshops.name`, a field passed through `CreateNewUser` → `CreateWorkshop`, a shared prop and a
  `WorkshopPolicy::update` for members.

### Risks
- **SQLite rebuilds a table when a column becomes NOT NULL or gets a foreign key, and drops partial-index predicates on the way** (the reason
  2026_10_09_300000 drops and recreates `materials_sku_unique`). → The migrations drop the partial indexes first, alter the tables, then
  recreate them. The index tests in S-44-b's file list check the result on both databases. With rows present, the rebuild also fires
  the foreign keys that point at the rebuilt table (F-59: `nullOnDelete` nulls them, `restrictOnDelete` throws). PostgreSQL alters in place,
  so S-44-b's data-migration test runs its "with data" case where that matters on PostgreSQL, or rebuilds in an order SQLite accepts.
- **Ripple in existing tests** (index names, sales seeder "table empty" check, tests that sign in and then count rows). → Single-workshop
  factory defaults. S-44-b's file list names the index-name tests. The developer runs the full suite, not only `tests/Feature/Workshops`.
- **A query that bypasses the scope** (`DB::table`, `withoutGlobalScopes`, an unscoped `Rule::exists`/`unique`, a route taking a raw
  `int $id`). → Arch rules and source scans in `tests/Arch/WorkshopScopeTest.php`, the router-driven 404 dataset and the leak scan over
  every page.
- **Between commits c and d, `Rule::exists('materials')` and `Rule::exists('products')` are still unscoped.** A hand-crafted request could
  then put another workshop's material in a composition, or record a sale of another workshop's product (stamped with the actor's
  workshop, because `RecordSale` finds the product through the scope): PostgreSQL rejects both (composite FKs), SQLite would accept them.
  → No release happens between those commits; d closes it.
- **AC-7 on real data** is proven by the test suites only (SQLite and PostgreSQL migration tests): nobody touches the development database
  `laravel` (no migrate, dump or restore by agents). → The delivery report reminds the human to back up `laravel` (`pg_dump`) and migrate it
  themselves before the click-through.
- **Query budget (≤ 10 per page) and speed.** The scope adds a `where`, not a query: the user is already loaded by `auth`. →
  `QueryBudgetTest` and `PartialReloadTest` stay unchanged and must stay green. S-27 measures a multi-workshop dataset.
- **R-12: three stories in flight.** S-08 is committed; S-29 touches the dev setup, not `app/`. → Never run two parity suites at once (they
  share the `testing` database). S-44 waits if S-29 needs the parity suite.

### Progress
- 2026-10-14 · Preparation done: dependencies installed in this worktree; baseline gates green (composer test 2085, Vitest 1136, check,
  types, build, parity 2085, golden path).
- 2026-10-14 · Wave 1 (S-44-a, S-44-e) approved in round 1; reviewed by the human, copied to the main checkout uncommitted.
- 2026-10-14 · Wave 2 (S-44-b) approved in round 1; all gates green (composer test 2103, parity 2103, golden path).
- 2026-10-15 · Wave 3 (S-44-c) approved in round 1; all gates green (composer test 2114, parity 2114, Vitest 1137, check, types, build,
  golden path).
- 2026-10-15 · Wave 4 (S-44-d) approved in round 1. Its developer found that the S-44-c leak scan never loaded the deferred props
  (it read a key this Inertia version drops); fixed in the same test file, and the full scan still finds no leak. All gates green on
  the final tree (composer test 2132, parity 2132, Vitest 1137, check, types, build, golden path). Delivered; next: product-owner
  acceptance, then S-45.
