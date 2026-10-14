---
name: invory-laravel-backend
description: Invory backend conventions for Laravel 13 + Inertia 3 + Pest 4 — layer responsibilities (thin controllers, FormRequests, Policies, single-purpose Actions, pure costing domain, API Resources as the props contract), file locations, code templates, decimal/money handling with brick/math, Pest feature/unit/arch test patterns, and the pre-handoff checklist. Use when writing or reviewing any PHP in app/, routes/, database/, config/ or tests/.
---

# Invory — Laravel backend conventions

Read `docs/ARCHITECTURE.md` (sections 3–5 and 8–9) and the `invory-costing-domain` skill first.
When Laravel Boost is installed, **use its `search-docs` MCP tool before using any API you are not 100% sure exists in Laravel 13 / Inertia 3 / Pest 4**,
and use `database-schema` / `last-error` / `read-log-entries` while debugging. Boost's `pest-testing` and `wayfinder-development` skills complement this one.

## Golden rules

1. **Follow Laravel conventions first.** Use `php artisan make:*` generators (`make:model -mfs`, `make:request`, `make:policy --model`, `make:resource`, `make:enum`, `make:class`, `make:test --pest`).
2. **Thin controllers.** A controller authorizes, calls one Action or Service, and returns `Inertia::render()` or a redirect. No business rules, no `DB::`.
3. **One Action = one write use case**: `final readonly class` with a single public `handle()` method. Wrap multi-row writes in `DB::transaction`.
4. **Validation lives in FormRequests.** Authorization goes through Policies (FormRequest `authorize()` for writes, `Gate::authorize()` for reads).
5. **Persist only `$request->validated()`.** Every model declares its mass-assignable attributes explicitly.
6. **Money and quantities are decimal strings** handled with `Brick\Math\BigDecimal`. Never use floats.
7. **The costing maths lives only in `App\Domain\Costing`** (pure PHP). Everything else calls `ProductCostingService`.
8. **Eager-load what Resources use.** `Model::shouldBeStrict()` is on outside production, so N+1 queries throw in tests.
9. **Every route ships with Pest tests** (see templates). No test means not done.
10. **`declare(strict_types=1);`**, full parameter and return types, and generics in docblocks for relations and collections (Larastan).

## File placement

| Thing | Location | Naming |
|---|---|---|
| Enum | `app/Enums/` | singular, `MaterialCategory` — string-backed, with `label()` + `options()` |
| Model | `app/Models/` | singular, casts in `casts()` method |
| Action | `app/Actions/<Module>/` | verb + noun: `CreateMaterial`, `SyncProductComponents` |
| Domain (pure) | `app/Domain/Costing/` | value objects are `final readonly` |
| Service (adapter) | `app/Services/Costing/` | `ProductCostingService` |
| Controller | `app/Http/Controllers/` | resource controllers, `MaterialController` |
| FormRequest | `app/Http/Requests/<Module>/` | `StoreMaterialRequest`, `UpdateMaterialRequest` |
| Resource | `app/Http/Resources/` | `MaterialResource`, `ProductListResource` (lightweight) vs `ProductResource` (detail) |
| Policy | `app/Policies/` | auto-discovered |
| Exception | `app/Exceptions/` | `MaterialInUseException` (domain-meaningful) |
| Config | `config/invory.php` | `currency`, `per_page` |
| Tests | `tests/Unit/Domain/Costing/`, `tests/Feature/<Module>/`, `tests/Arch/` | `*Test.php` |

## Templates

### Enum
```php
enum MaterialCategory: string
{
    case Fabric = 'fabric';
    case Thread = 'thread';
    // …

    public function label(): string
    {
        return match ($this) {
            self::Fabric => __('Fabric'),
            self::Thread => __('Thread'),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
```

### Model
```php
class Material extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['sku', 'name', 'category', 'unit', 'unit_cost', 'quantity_on_hand', 'reorder_level',
        'supplier_name', 'supplier_reference', 'color', 'composition', 'notes'];

    protected function casts(): array
    {
        return [
            'category' => MaterialCategory::class,
            'unit' => UnitOfMeasure::class,
            'unit_cost' => 'decimal:4',          // → string "8.5000"
            'quantity_on_hand' => 'decimal:4',
            'reorder_level' => 'decimal:4',
        ];
    }

    /** @return HasMany<ProductComponent, $this> */
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class);
    }
}
```
If Boost's Laravel 13 guidelines prefer attribute-based model configuration, follow them and be consistent across all models.

### Action (write use case)
```php
final readonly class UpdateProduct
{
    public function __construct(private SyncProductComponents $syncComponents) {}

    /** @param array<string, mixed> $data validated input, incl. 'components' */
    public function handle(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update(Arr::except($data, ['components']));
            $this->syncComponents->handle($product, $data['components'] ?? []);

            return $product->refresh();
        });
    }
}
```
`SyncProductComponents` upserts the submitted lines (keyed by `material_id`), deletes lines that were not submitted, and sets `position` from the array index.

### Controller
```php
final class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Material::class);

        $materials = Material::query()
            ->when($request->string('search')->trim()->value(), fn ($q, string $s) => $q->where(
                fn ($q) => $q->whereLike('name', "%{$s}%")->orWhereLike('sku', "%{$s}%")))
            ->when($request->enum('category', MaterialCategory::class), fn ($q, $c) => $q->where('category', $c))
            ->orderBy('name')
            ->paginate(config('invory.per_page'))
            ->withQueryString();

        return Inertia::render('materials/index', [
            'materials' => MaterialResource::collection($materials),   // → { data, links, meta }
            'filters' => $request->filters(),   // IndexMaterialRequest normalises to the MaterialFilters shape in domain.ts
            'categories' => MaterialCategory::options(),
        ]);
    }

    public function store(StoreMaterialRequest $request, CreateMaterial $createMaterial): RedirectResponse
    {
        $material = $createMaterial->handle($request->validated());

        // Starter-kit native flash → <Toaster> via useFlashToast(). Types: success | info | warning | error.
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material created.')]);

        return to_route('materials.show', $material);
    }

    public function destroy(Material $material, DeleteMaterial $deleteMaterial): RedirectResponse
    {
        Gate::authorize('delete', $material);

        try {
            $deleteMaterial->handle($material);
        } catch (MaterialInUseException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Material deleted.')]);

        return to_route('materials.index');
    }
}
```

### FormRequest
```php
final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('products')->withoutTrashed()],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'labor_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999'],
            'overhead_cost' => ['required', 'decimal:0,4', 'min:0', 'max:99999999'],
            'components' => ['present', 'array'],
            'components.*.material_id' => ['required', 'integer', 'distinct',
                Rule::exists('materials', 'id')->withoutTrashed()],
            'components.*.quantity' => ['required', 'decimal:0,4', 'gt:0', 'max:9999999'],
            'components.*.waste_percent' => ['nullable', 'decimal:0,2', 'min:0', 'lt:100'],
            'components.*.notes' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

### Resource (the props contract — mirror it in `resources/js/types/domain.ts`)
```php
/** @mixin Material */
final class MaterialResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'unit' => $this->unit->value,
            'unit_cost' => $this->unit_cost,                 // "8.5000"
            'quantity_on_hand' => $this->quantity_on_hand,   // "250.0000"
            'reorder_level' => $this->reorder_level,
            'stock_value' => (string) BigDecimal::of($this->quantity_on_hand)
                ->multipliedBy($this->unit_cost)->toScale(4, RoundingMode::HalfUp),
            'stock_status' => $this->stockStatus()->value,   // in_stock | low | out (rule in invory-costing-domain)
            // nullable descriptive fields…
        ];
    }
}
```
Keys are **snake_case**. Decimals are **strings**, enums are their `value`, and dates are ISO-8601 strings.
**When you add, remove or rename a key, update `resources/js/types/domain.ts` in the same change and list it under "Contract changes" in your handoff.**

### Inertia page stubs
Inertia's testing helper checks that the page component file exists. When you add a new `Inertia::render('x/y')` before the
frontend exists, create a stub `resources/js/pages/x/y.tsx` exporting a component that renders `null`, with the comment
`// STUB — owned by frontend-developer`. This is the **only** frontend file you create besides `types/domain.ts`.

## Testing (Pest 4)

```php
use Inertia\Testing\AssertableInertia as Assert;
use function Pest\Laravel\{actingAs, get, post, put, delete, assertDatabaseHas, assertSoftDeleted};

beforeEach(fn () => $this->user = User::factory()->create());

it('redirects guests to login', function (string $method, string $route) {
    $this->{$method}(route($route))->assertRedirect(route('login'));
})->with([['get', 'materials.index'], ['get', 'materials.create'], ['post', 'materials.store']]);

it('lists materials with the agreed contract', function () {
    Material::factory()->count(3)->create();

    actingAs($this->user)->get(route('materials.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('materials/index')
            ->has('materials.data', 3)
            ->has('materials.data.0', fn (Assert $m) => $m
                ->hasAll(['id', 'sku', 'name', 'category', 'unit', 'unit_cost', 'quantity_on_hand', 'stock_value', 'stock_status'])
                ->etc())
            ->has('categories'));
});

it('rejects invalid material input', function (array $override, string $field) {
    actingAs($this->user)
        ->post(route('materials.store'), [...validMaterialPayload(), ...$override])
        ->assertSessionHasErrors($field);
})->with([
    'negative cost' => [['unit_cost' => '-1'], 'unit_cost'],
    'too many decimals' => [['unit_cost' => '1.12345'], 'unit_cost'],
    'unknown unit' => [['unit' => 'bananas'], 'unit'],
]);

it('refuses to delete a material used in a product', function () { /* … assert flash error + not soft-deleted */ });
```
- **Unit tests for `App\Domain\Costing`** run without a DB or the app (`tests/Unit`). Cover every row of the edge-case table in `invory-costing-domain` and the worked example (exact strings: `->toBe('35.7540')`).
- Put reusable payload builders in `tests/Pest.php` or as dataset helpers.
- **Architecture tests** in `tests/Arch/ArchTest.php`:
```php
arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();

arch('costing domain is framework-free')
    ->expect('App\Domain')->not->toUse(['Illuminate', 'App\Models', 'App\Http']);

arch('controllers never touch the DB facade')
    ->expect('App\Http\Controllers')->not->toUse('Illuminate\Support\Facades\DB');

// `->ignoring()` only applies to the expectation it follows, so use one arch test per expectation.
arch('actions are final')
    ->expect('App\Actions')->classes()->toBeFinal()->ignoring('App\Actions\Fortify');
arch('actions expose a handle method')
    ->expect('App\Actions')->classes()->toHaveMethod('handle')->ignoring('App\Actions\Fortify');
```
(This is what `tests/Arch/ArchTest.php` contains since S-00.)

## Factories and seeders
- Factories produce **realistic fashion data** (e.g. "Denim 12oz Indigo", unit `m`) with states such as `lowStock()`, `outOfStock()`, and `withComponents(int $n)` on `ProductFactory`.
- `DemoCatalogSeeder` seeds the worked example from the domain skill plus 3 more products (t-shirt, sneaker, leather tote), so the UI is reviewable right after `php artisan migrate:fresh --seed`.

## Commands
Native (PHP on the PATH; tests use SQLite in-memory):
```
php artisan test --compact                        # all tests
php artisan test --compact --filter=Material      # focused
vendor/bin/pint --dirty                           # format changed files
vendor/bin/phpstan analyse --memory-limit=1G      # Larastan
composer test                                     # full gate (pint --test + phpstan + tests), must be green before handoff
php artisan route:list --path=materials
```
Natively, with Docker running for PostgreSQL (the dev DB `laravel`):
```
php artisan migrate
php artisan tinker
composer test:pgsql      # PostgreSQL parity (database `testing` only)
```
Portability: no SQLite- or PostgreSQL-only SQL. `whereLike` is fine (Laravel handles case-insensitivity per driver), and so are the migration column types used above.

## Pre-handoff checklist
- [ ] Migrations are reversible, have FKs and indexes, and use the decimal scales from the domain skill
- [ ] Explicit fillable, `casts()` with enums and `decimal:N`, typed relations with generics
- [ ] FormRequest + Policy on every write; `Gate::authorize` on every read
- [ ] Multi-row writes inside `DB::transaction`
- [ ] No float maths; BigDecimal used and outputs are scale-4 strings
- [ ] Relations eager-loaded for every Resource (no lazy loading)
- [ ] Feature tests: guest, validation datasets, happy path + DB assertions, Inertia props contract, delete rules
- [ ] `resources/js/types/domain.ts` updated for any contract change
- [ ] `composer test` green; output pasted in the handoff
