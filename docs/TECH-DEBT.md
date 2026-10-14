# Invory — technical debt and follow-up register

> Owner: **tech-lead** (adds items from reviews, sizes them, states the cost of delay). Scheduling: **product-owner**, who pulls items into a
> phase's "tech allowance" in [BACKLOG.md](BACKLOG.md) (about 15 % of a phase). User-visible behaviour or UX items don't belong here: they
> go to the product-owner as story candidates.
> Open items only. Closed items: [archive/backlog-2026-10-14.md](archive/backlog-2026-10-14.md) §"Follow-ups from reviews". Next free ID: **F-55**.

"Proposed phase" is the tech-lead's suggestion; the phase lists in [BACKLOG.md](BACKLOG.md) are the source of truth.

Severity: **MAJOR** (fix before release) · **MINOR** (fix when touching the area or in an allowance) · **NIT** (cosmetic) · **NOTE** (known behaviour, no action).

## Scheduled
| ID | Sev. | Area | Item | Cost of delay (plain language) | Proposed phase |
|---|---|---|---|---|---|
| F-25 | MINOR | docs | `CLAUDE.md`, the agent files and the skills still describe Sail, `--profile app` and Laragon Apache. Replacement text: S-29 delivery report (DK-d, applied by the main session) | Agents run outdated commands | 4 (S-29, DK-d) |
| F-12 | NIT | docs | The `invory-react-frontend` skill says `CapacityBadge` 0 → "Out of stock" in `components/products/`. The shipped copy is "Can't produce" in `components/shared/` | Agents copy the wrong wording | 4 |
| F-22 | MINOR | FE tests | `product-form.test.tsx` "posts the typed values…" and `pages/materials/create.test.tsx` ("submits the typed values…", "sends the chosen photo…") flake near the 5 s timeout when other heavy processes run. On 2026-10-14 (S-29 review) the full `npx vp test` failed 3 runs in a row while `composer dev` (Vite) ran; the files pass alone and the full suite passes with `--maxWorkers=3`. Fix: `user.paste` / `fireEvent.change` for long fields, or cap Vitest workers | False red gates; worse now that `composer dev` runs Vite alongside the gates | 4 |
| F-45 | NIT | tests | Stale "preview" wording in test names and comments (`MaterialPreviewDataTest`, `previewFixtureMaterials()`…), `/mock/photos/...` strings in Vitest fixtures, and two dashboard test files (`tests/Feature/DashboardTest.php` and `tests/Feature/Dashboard/DashboardTest.php`) | Misleads readers about what is real | 4 |
| F-26 | MINOR | BE perf | `Material::$with = ['category','unit']` adds 2 queries per page that loads materials; the `reference` once-prop adds 3 on full loads. Option: version-keyed cached `ReferenceData` | Uses query budget headroom | 5 (with S-23/S-27) |
| F-38 | NOTE→MINOR | BE perf | The dashboard costs every product, and sales totals walk every row of the period in PHP (O(n)) until S-23 | Slows down as data grows | 5 (S-23) |
| F-41 | MINOR | domain | `MaterialUsageService` reads `effective_quantity` / `units_covered` from the material's single line (DB-unique). Expose the summed requirement per material from `CostingCalculator` so "same material on two lines" holds by construction | Wrong figures if the uniqueness rule is ever relaxed | 5 |
| F-42 | MINOR | BE arch | `DashboardController` shapes `TopProduct`, `UnproducibleAlert`, `ProductRef` in private methods. Move them to Resources | Controller is no longer thin | 5 |
| F-49 | NIT | BE | `DashboardController` relies on prop **order** (`alerts` before `recent_sales`, shared `ProductCatalog`). Consider an explicit `$catalog->warm()` | A harmless-looking reorder adds queries | 5 |
| F-43 | NIT | BE | `PhotoStorage::persist()` deletes the old file when the Action's transaction returns; use `DB::afterCommit` if Actions are ever nested. Two concurrent photo edits can orphan a file | Orphan files on disk | 6 |
| F-46 | NIT | BE | `RecordSale` doesn't re-check, inside its transaction, that the product is still active (validation already did) | A sale on a product archived a millisecond earlier | 6 |
| F-52 | NIT | repo | `scripts/bootstrap.ps1` still runs `sail:install` and prints `.\sail` steps. Retire it or mark it historical (SETUP.md §2 already says not to re-run it). *(was "F-42" in the dev-Docker plan)* | Misleads a new developer | 6 |
| F-53 | NIT | dev | Pin `axllent/mailpit` to a version instead of `latest`. *(was "F-43" in the dev-Docker plan)* | Surprise upgrades | 6 |

## Unscheduled (fix when touching the area)
| ID | Sev. | Area | Item |
|---|---|---|---|
| F-04 | MINOR | FE tooling | The shadcn CLI emits `import { cn } from "cn"` and umbrella `radix-ui` imports, and tries `pnpm`. New components need hand fixes. Find a working CLI setup |
| F-06 | NIT | BE | `%` and `_` in the materials search act as LIKE wildcards (bound values, safe). Escape them if users complain |
| F-07 | NIT | BE | `UpdateMaterial::handle()` returns the model without `refresh()`. Harmless today (redirect) |
| F-09 | NIT | FE tooling | `npm ls` flags vitest's nested `vite` as "invalid" because of the vite-plus alias. Cosmetic; revisit on the next vite-plus upgrade |
| F-15 | MINOR | FE | `components/materials/form-field.tsx` and `components/products/form-field.tsx` (plus the decimal input and currency affix helpers) duplicate each other: extract to `components/shared`. Move `components/ui/popover-command.test.tsx` out of the generated `ui/` folder |
| F-19 | NIT | contract | Laravel's `meta.links` entries carry a `page` key that `PaginationLink` doesn't declare. Add `page?: number \| null` |
| F-23 | NIT | FE build | Name vendor chunks (`manualChunks`: vendor-react, vendor-inertia) for readable bundle reports |
| F-44 | NIT | FE | After a pick, the material picker fires one more (cancelled) request before the popover unmounts |

## Notes (known behaviour, no action)
- **F-28:** system units accept only `name` and `position` on update; `factor_to_base` arrives as a scale-12 string and is trimmed for display.
- **F-54:** the dev server is PHP's built-in server (`composer dev` → `php artisan serve`, ADR-0012). On Windows it serves one request at a
  time, so deferred props load one after another; with `opcache.enable_cli=0` each request costs ~0.3 s, with `1` ~0.06 s (2026-10-14). The
  S-18 speed target depends on this; the trade-off went to the product-owner in the S-29 delivery report.
- **F-34:** PostgreSQL's `lower()` folds non-ASCII letters and SQLite's doesn't, so "Étiquette" and "étiquette" clash only on PostgreSQL
  (production behaves as intended; tests stay ASCII).

## Closed during the 2026-10-14 clean-up
F-01, F-02 (S-12) · F-03 (S-20) · F-05, F-10 (2026-10-08) · F-13 (S-19) · F-20 (S-16-BE) · F-24 (S-28, on PostgreSQL) · F-27 (S-22) ·
F-29 (S-04: audit columns, CHECKs, trigram indexes, real usage sources) · F-30, F-31 q1/q2, F-32 (S-22b) · F-33 · F-36 (S-15-BE) ·
F-37 (S-14-BE) · F-39, F-40 (human: skip) · F-47 (P-02) · F-08 (S-29: `.gitignore` guard; native coverage needs pcov or Xdebug,
machine config documented in SETUP.md §1) · F-51 (S-29, won't do: the human serves the app with `composer dev` / `artisan serve`, so its
premise "Apache owns :8000" is void). Details in the [archive](archive/backlog-2026-10-14.md).

## Moved to the product backlog (behaviour / UX, product-owner decides)
F-11 → S-37 · F-14, F-16, F-17, F-18, F-21 → S-38 · F-31 q3 → S-39 · F-35 → S-32 · F-48 → S-31 · F-50 → S-35 · RL-03 → S-33 · RL-05 → S-34.
