# ADR-0013 — One workshop per account: every record belongs to a workshop, every query is scoped to it

- **Status:** Accepted · 2026-10-14 (the human's decision D-9 of 2026-10-14, STATUS §2; delivered by S-44, S-45, S-46)
- **Supersedes:** [ADR-0003](0003-single-tenant-mvp.md) "single company, all users share the same data" and decision Q3 "any signed-in
  user edits the catalogue" (now: any member of a workshop edits that workshop's catalogue)
- **Keeps:** ADR-0002 (decimal strings + BigDecimal), ADR-0004 (pure costing domain: the formulas don't change, they run on one
  workshop's records), ADR-0008 (reference data in tables; S-45 makes them per workshop)

## Context
The human asked on 2026-10-14 that each account sees only its own materials and products, with room for several people to share one
business later. The product-owner named the business account **Workshop** and the human approved business rules R1–R6 (D-9):

- **R1** Every account belongs to exactly one workshop. Every material, product, composition line, stock movement, sale, photo, unit and
  category belongs to exactly one workshop.
- **R2** You see and change only your workshop's records. Another workshop's record behaves as if it doesn't exist: **404, never 403**.
- **R3** SKUs, and unit and category names, are unique per workshop (not per installation).
- **R4** A composition uses only materials of its product's workshop; a sale only products of its workshop.
- **R5** Every figure (unit cost, can produce, bottleneck, inventory value, sales totals, dashboard) is computed over one workshop's
  records. The costing formulas don't change.
- **R6** Signing up creates a new, empty workshop with the new account as its only member.

Also decided: existing accounts and data all join one workshop; sign-up stays open; deleting an account deletes only the person (the
workshop's data stays); a photo needs only an unguessable address. No invitations or roles are built now.

ADR-0003 foresaw this change ("a migration adding `team_id`, a global scope or trait"). The risk to manage is **forgetting the scope**:
one unscoped query, validation rule or route is a data leak between businesses.

## Decision
1. **A `workshops` table** (`id`, timestamps; S-46 adds `name`). **`users.workshop_id`** (NOT NULL, FK `restrictOnDelete`): one workshop per
   account (R1), many accounts per workshop. Registration (`CreateNewUser`) creates the workshop and the account in one transaction through
   `App\Actions\Workshops\CreateWorkshop`, which S-45 extends to give the workshop its standard units and categories.
2. **`workshop_id` (NOT NULL, FK → workshops) on every owned table**: `materials`, `products`, `product_components`, `stock_movements`,
   `sales` (S-45: `units`, `material_categories`, `product_categories`). Child rows carry it too, so one uniform rule covers every owned
   model and the database can check R4.
3. **Scoping is automatic and fail-safe in the request path.** Every owned model uses the trait `App\Models\Concerns\BelongsToWorkshop`:
   - a global scope (`App\Models\Scopes\WorkshopScope`) adds `<table>.workshop_id = <current workshop>` to every query, subquery, eager load,
     `toBase()` aggregate and **route-model binding** (so another workshop's `/materials/{id}` is a plain 404 with no extra code);
   - a `creating` hook stamps `workshop_id` from the current workshop when the caller didn't set it, and refuses to save an owned record
     without one. Child rows take their parent's workshop explicitly in the Actions (`SyncProductComponents`, `AppendStockMovement`,
     `RecordSale`).
   - `workshop_id` is never mass-assignable.
4. **The current workshop** comes from `App\Services\Workshops\CurrentWorkshop` (a scoped singleton): an explicit workshop set with
   `CurrentWorkshop::run($workshop, $callback)` wins, otherwise the signed-in user's `workshop_id` (read lazily from the auth guard, so it
   is known before route-model binding runs), otherwise none. **With no workshop the scope adds nothing** (system context): migrations,
   seeders, `invory:stock:verify` and test set-up. Every route that reads owned data sits behind `auth` (a route test guards it), so a web
   request always has a workshop.
5. **Policies** (`MaterialPolicy`, `ProductPolicy`, `SalePolicy`; S-45 the reference policies): `viewAny`/`create` for any member;
   `view`/`update`/`delete` only when `$user->workshop_id === $model->workshop_id`, otherwise `Response::denyAsNotFound()` (404, R2).
   FormRequests that authorize a bound record return the Gate response (`Gate::inspect`), not a bool, so a denial stays a 404.
   This is defence in depth: through HTTP the scope already hides the record.
6. **Validation is scoped through one helper**, `App\Validation\WorkshopRule::exists()` / `::unique()`, which adds
   `where workshop_id = <current>` (Laravel's `exists`/`unique` rules query the table directly and ignore global scopes). An id that belongs
   to another workshop fails exactly like an id that doesn't exist.
7. **Indexes:** the partial unique SKU indexes keep their names and become `(workshop_id, sku) WHERE deleted_at IS NULL` (R3, and the
   per-workshop key for S-32's race handling). `materials_restock_idx` leads with `workshop_id`; the sales log index becomes
   `(workshop_id, sold_on, id)`. S-27 measures the rest on a multi-workshop dataset.
8. **PostgreSQL also enforces R4**: `unique(workshop_id, id)` on `materials` and `products`, and composite foreign keys
   `product_components (workshop_id, product_id | material_id)`, `sales (workshop_id, product_id)`,
   `stock_movements (workshop_id, material_id)` → `(workshop_id, id)`. PostgreSQL only (like the CHECK constraints of 2026_10_09_300200);
   the SQLite suite relies on the validation and the Actions, the parity suite proves the constraints.
9. **Data migration:** if the database holds any account or record, one workshop is created and every account and every row (soft-deleted
   ones included) joins it; a fresh database gets none. The demo seeders put the demo data in `test@example.com`'s workshop.
10. **Guards against forgetting** (tests): an arch test that every model in `App\Models` uses `BelongsToWorkshop` except an explicit
    allow-list (`User`, `Workshop`, and `Unit`, `MaterialCategory`, `ProductCategory` until S-45); a source scan that `app/` never calls
    `withoutGlobalScope(s)` or raw `DB::table()` on owned tables, and never uses `Rule::exists`/`Rule::unique` on them outside
    `WorkshopRule`; a route test that walks the router and checks every route with a bound record answers 404 to another workshop and
    changes nothing; and a leak scan that opens every signed-in page (deferred props included) as a second workshop and finds none of the
    first workshop's names or SKUs.
11. **Photos** keep random file names on the public disk (`UploadedFile::store()`, 40 random characters): the address can't be guessed from
    the record's name or id, and it only appears on the owning workshop's screens. Sign-in-only photos are a possible later story.
12. **Deleting an account** deletes only the user row. The existing `nullOnDelete` audit columns keep every record; the workshop stays, even
    with no member left (risk R-13, revisit before the production server, S-26).

## Alternatives rejected
- **Starter-kit teams with `/{team}/…` URLs:** changes every route, Wayfinder call and test for a choice users never make (one workshop per
  account). The scope gives the same isolation without URL churn. If a person ever needs several workshops, add a membership table and a
  "current workshop" switch; only `CurrentWorkshop` changes.
- **A database or schema per workshop:** operational cost (migrations × N, backups, connections) far beyond a small SaaS's needs.
- **A tenancy package (e.g. stancl/tenancy single-database mode):** a dependency and its conventions for about 150 lines of our own code
  that the arch tests can see.
- **Scoping by hand in each query (`where('workshop_id', …)`):** the failure mode is silent; one forgotten clause leaks data.
- **`workshop_id` on root tables only (children reached through their parent):** saves two columns but loses the uniform arch rule and the
  database check of R4.
- **Fail closed (no rows) when there is no workshop:** would need an explicit workshop in every seeder, command and several hundred
  existing tests for no gain in the request path, where a workshop is always present.

## Consequences
- ✅ Isolation is the default: a new query, relation or route is scoped without anyone remembering to scope it; route binding gives 404.
- ✅ Several people can share a workshop later without moving data (R1 is enforced by `users.workshop_id`).
- ✅ The query budget doesn't change: the scope adds a `where`, not a query (the user is already loaded by `auth`).
- ⚠️ Code that runs with a signed-in user but must see every workshop (none today) has to say so explicitly; the source scan makes it visible.
- ⚠️ Test code that signs in and then counts rows sees only that user's workshop. Factories put rows and users in the current or first
  workshop by default, so single-workshop tests behave as before; two-workshop tests create a second workshop explicitly.
- ⚠️ Until S-45, units and categories are still shared by every workshop: S-44 and S-45 ship together (D-9).
- ⚠️ `down()` of the data migration merges everything back; it refuses when two workshops use the same SKU.
