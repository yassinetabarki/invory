<?php

use App\Models\Material;
use App\Models\User;
use App\Policies\MaterialPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| UI-first preview routes (ADR-0006)
|--------------------------------------------------------------------------
|
| The final routes of products, sales, restocks and the dashboard: this file pins the route contract, authentication,
| the 404 rules and the production lock that must hold for as long as App\Mock exists. The props are asserted in
| ProductPagesTest, SalesPageTest and DashboardPreviewTest, the validation in the *WriteTest files.
*/

/** A Material policy that refuses updates, to prove the restock action asks the policy. */
class DenyMaterialUpdatePolicy extends MaterialPolicy
{
    public function update(User $user, Material $material): bool
    {
        return false;
    }
}

beforeEach(fn () => $this->user = preparePreview($this));

/** Every new route: [method, route name, route parameter ('material' = create a Material first)]. */
dataset('previewRoutes', [
    'dashboard' => ['get', 'dashboard', null],
    'products.index' => ['get', 'products.index', null],
    'products.create' => ['get', 'products.create', null],
    'products.store' => ['post', 'products.store', null],
    'products.show' => ['get', 'products.show', 1],
    'products.edit' => ['get', 'products.edit', 1],
    'products.update' => ['put', 'products.update', 1],
    'products.destroy' => ['delete', 'products.destroy', 1],
    'sales.index' => ['get', 'sales.index', null],
    'sales.store' => ['post', 'sales.store', null],
    'sales.update' => ['put', 'sales.update', 1],
    'sales.destroy' => ['delete', 'sales.destroy', 1],
    'materials.restocks.store' => ['post', 'materials.restocks.store', 'material'],
]);

/** Build the URL of a route from the dataset's parameter column. */
function previewUrl(string $route, int|string|null $parameter): string
{
    if ($parameter === 'material') {
        $parameter = Material::factory()->create();
    }

    return route($route, $parameter === null ? [] : [$parameter]);
}

/**
 * A valid body for each kind of preview write. Datasets are built before the app boots, so they carry the kind and
 * the test asks for the payload (the product payload needs the seeded materials).
 *
 * @return array<string, mixed>
 */
function previewPayload(string $kind): array
{
    return match ($kind) {
        'product' => validProductPayload(),
        'sale' => validSalePayload(),
        default => [],
    };
}

it('redirects guests to login on every new route', function (string $method, string $route, int|string|null $parameter) {
    $this->{$method}(previewUrl($route, $parameter))->assertRedirect(route('login'));
})->with('previewRoutes');

it('blocks unverified users on every new route', function (string $method, string $route, int|string|null $parameter) {
    actingAs(User::factory()->unverified()->create())
        ->{$method}(previewUrl($route, $parameter))
        ->assertRedirect(route('verification.notice'));
})->with('previewRoutes');

it('registers the frozen routes behind auth and verified', function (string $name, string $method, string $uri) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe($uri)
        ->and($route->methods())->toContain($method)
        ->and($route->gatherMiddleware())->toContain('auth', 'verified');
})->with([
    'dashboard' => ['dashboard', 'GET', 'dashboard'],
    'materials.restocks.store' => ['materials.restocks.store', 'POST', 'materials/{material}/restocks'],
    'products.index' => ['products.index', 'GET', 'products'],
    'products.create' => ['products.create', 'GET', 'products/create'],
    'products.store' => ['products.store', 'POST', 'products'],
    'products.show' => ['products.show', 'GET', 'products/{product}'],
    'products.edit' => ['products.edit', 'GET', 'products/{product}/edit'],
    'products.update' => ['products.update', 'PUT', 'products/{product}'],
    'products.destroy' => ['products.destroy', 'DELETE', 'products/{product}'],
    'sales.index' => ['sales.index', 'GET', 'sales'],
    'sales.store' => ['sales.store', 'POST', 'sales'],
    'sales.update' => ['sales.update', 'PUT', 'sales/{sale}'],
    'sales.destroy' => ['sales.destroy', 'DELETE', 'sales/{sale}'],
]);

it('keeps the existing materials route names and does not add sale pages beyond the contract', function () {
    foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $action) {
        expect(Route::has("materials.{$action}"))->toBeTrue();
    }

    foreach (['show', 'create', 'edit'] as $action) {
        expect(Route::has("sales.{$action}"))->toBeFalse();
    }
});

it('renders each page component for a verified user', function (string $route, ?int $parameter, string $component) {
    actingAs($this->user)->get(previewUrl($route, $parameter))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'dashboard' => ['dashboard', null, 'dashboard'],
    'products.index' => ['products.index', null, 'products/index'],
    'products.create' => ['products.create', null, 'products/create'],
    'products.show' => ['products.show', 1, 'products/show'],
    'products.edit' => ['products.edit', 1, 'products/edit'],
    'sales.index' => ['sales.index', null, 'sales/index'],
]);

it('does not mistake /products/create for a product id', function () {
    actingAs($this->user)->get('/products/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('products/create'));
});

it('redirects valid preview writes to the page the contract names', function (string $method, string $route, ?int $parameter, string $payload, ?string $target, array $targetParameters) {
    // A null target means "back to the page the request came from".
    $expected = $target === null ? route('sales.index') : route($target, $targetParameters);

    actingAs($this->user)
        ->from(route('sales.index'))
        ->{$method}(previewUrl($route, $parameter), previewPayload($payload))
        ->assertRedirect($expected)
        ->assertSessionHasNoErrors();
})->with([
    'products.store goes to the list' => ['post', 'products.store', null, 'product', 'products.index', []],
    'products.update goes to the product' => ['put', 'products.update', 1, 'product', 'products.show', [1]],
    'products.destroy goes to the list' => ['delete', 'products.destroy', 1, 'none', 'products.index', []],
    'sales.store goes back' => ['post', 'sales.store', null, 'sale', null, []],
    'sales.update goes back' => ['put', 'sales.update', 1, 'sale', null, []],
    'sales.destroy goes back' => ['delete', 'sales.destroy', 1, 'none', null, []],
]);

it('sends an invalid preview write back to where it came from, with its errors', function (string $method, string $route, int|string|null $parameter, array $errors) {
    actingAs($this->user)
        ->from(route('sales.index'))
        ->{$method}(previewUrl($route, $parameter), [])
        ->assertRedirect(route('sales.index'))
        ->assertSessionHasErrors($errors);
})->with([
    'products.store' => ['post', 'products.store', null, ['sku', 'name', 'category', 'status', 'labor_cost', 'overhead_cost']],
    'products.update' => ['put', 'products.update', 1, ['sku', 'name', 'category', 'status', 'labor_cost', 'overhead_cost']],
    'sales.store' => ['post', 'sales.store', null, ['product_id', 'quantity', 'unit_price', 'sold_on']],
    'sales.update' => ['put', 'sales.update', 1, ['product_id', 'quantity', 'unit_price', 'sold_on']],
    'restock' => ['post', 'materials.restocks.store', 'material', ['quantity', 'unit_price', 'received_on', 'update_unit_cost']],
]);

it('redirects a restock back to where it came from', function () {
    $material = Material::factory()->create();

    actingAs($this->user)
        ->from(route('materials.index'))
        ->post(route('materials.restocks.store', $material), validRestockPayload())
        ->assertRedirect(route('materials.index'))
        ->assertSessionHasNoErrors();
});

it('persists nothing on valid preview writes', function () {
    $material = Material::factory()->create(['quantity_on_hand' => '10.0000', 'unit_cost' => '2.0000']);
    $rowsBefore = Material::withTrashed()->orderBy('id')->get()->toArray();
    $tablesBefore = Schema::getTableListing();

    actingAs($this->user)->post(route('materials.restocks.store', $material), validRestockPayload())->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('sales.store'), validSalePayload())->assertSessionHasNoErrors();
    actingAs($this->user)->put(route('sales.update', 1), validSalePayload())->assertSessionHasNoErrors();
    actingAs($this->user)->delete(route('sales.destroy', 1))->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('products.store'), validProductPayload())->assertSessionHasNoErrors();
    actingAs($this->user)->put(route('products.update', 1), validProductPayload())->assertSessionHasNoErrors();
    actingAs($this->user)->delete(route('products.destroy', 1))->assertSessionHasNoErrors();

    expect(Material::withTrashed()->orderBy('id')->get()->toArray())->toBe($rowsBefore)
        ->and(Schema::getTableListing())->toBe($tablesBefore)
        ->and($material->fresh()->quantity_on_hand)->toBe('10.0000')
        ->and($material->fresh()->unit_cost)->toBe('2.0000');
});

it('answers 404 for a restock on an unknown or deleted material', function () {
    $deleted = Material::factory()->create();
    $deleted->delete();

    actingAs($this->user)->post('/materials/999999/restocks', validRestockPayload())->assertNotFound();
    actingAs($this->user)->post(route('materials.restocks.store', $deleted->id), validRestockPayload())->assertNotFound();
});

// PostgreSQL throws on `id = 'abc'` (bigint) where SQLite does not, so the route itself must reject these.
it('answers 404 for a non-numeric or out-of-range material id on a restock', function (string $id) {
    actingAs($this->user)->post("/materials/{$id}/restocks")->assertNotFound();
})->with([
    'letters' => 'abc',
    'decimal' => '1.5',
    'negative' => '-1',
    'digits then letters' => '12abc',
    'beyond bigint' => '99999999999999999999',
]);

it('answers 404 for a non-numeric product or sale id', function (string $method, string $uri) {
    actingAs($this->user)->{$method}($uri)->assertNotFound();
})->with([
    'show, letters' => ['get', '/products/abc'],
    'show, decimal' => ['get', '/products/1.5'],
    'show, negative' => ['get', '/products/-1'],
    'show, digits then letters' => ['get', '/products/12abc'],
    'show, uuid' => ['get', '/products/0a1b2c3d-0000-4000-8000-000000000000'],
    'show, beyond the integer range' => ['get', '/products/99999999999999999999'],
    'edit, beyond the integer range' => ['get', '/products/99999999999999999999/edit'],
    'update, beyond the integer range' => ['put', '/products/99999999999999999999'],
    'sale update, beyond the integer range' => ['put', '/sales/99999999999999999999'],
    'sale destroy, beyond the integer range' => ['delete', '/sales/99999999999999999999'],
    'edit, letters' => ['get', '/products/abc/edit'],
    'update, letters' => ['put', '/products/abc'],
    'destroy, letters' => ['delete', '/products/abc'],
    'sale update, letters' => ['put', '/sales/abc'],
    'sale update, decimal' => ['put', '/sales/1.5'],
    'sale destroy, letters' => ['delete', '/sales/abc'],
    'sale destroy, negative' => ['delete', '/sales/-3'],
]);

// The fixture has products 1 to 7 and sales 1 to 60: any other id is unknown, as route model binding will make it.
it('answers 404 for an unknown product or sale id, before anything is validated', function (string $method, string $uri, string $payload) {
    actingAs($this->user)->{$method}($uri, previewPayload($payload))->assertNotFound();
    actingAs($this->user)->{$method}($uri)->assertNotFound();
})->with([
    'show a product' => ['get', '/products/8', 'none'],
    'edit a product' => ['get', '/products/8/edit', 'none'],
    'update a product' => ['put', '/products/8', 'product'],
    'destroy a product' => ['delete', '/products/8', 'none'],
    'product zero' => ['get', '/products/0', 'none'],
    'a far away product' => ['put', '/products/999999', 'product'],
    'update a sale' => ['put', '/sales/61', 'sale'],
    'destroy a sale' => ['delete', '/sales/61', 'none'],
    'sale zero' => ['put', '/sales/0', 'sale'],
    'a far away sale' => ['delete', '/sales/999999', 'none'],
]);

it('serves the last fixture product and sale', function () {
    actingAs($this->user)->get('/products/7')->assertOk();
    actingAs($this->user)->get('/products/7/edit')->assertOk();
    actingAs($this->user)->put('/sales/60', validSalePayload())->assertRedirect();
    actingAs($this->user)->delete('/sales/60')->assertRedirect();
});

it('asks the material policy before a restock, on top of the preview ability', function () {
    $material = Material::factory()->create();
    Gate::policy(Material::class, DenyMaterialUpdatePolicy::class);

    actingAs($this->user)->post(route('materials.restocks.store', $material), validRestockPayload())->assertForbidden();
});

describe('access-preview', function () {
    it('is granted to any authenticated user outside production', function (string $environment) {
        $this->app->detectEnvironment(fn () => $environment);

        expect(Gate::forUser($this->user)->allows('access-preview'))->toBeTrue();
    })->with(['local', 'testing', 'staging']);

    it('is denied in production', function () {
        $this->app->detectEnvironment(fn () => 'production');

        expect(Gate::forUser($this->user)->allows('access-preview'))->toBeFalse()
            ->and(Gate::forUser($this->user)->denies('access-preview'))->toBeTrue();
    });

    it('is denied to guests', function () {
        expect(Gate::allows('access-preview'))->toBeFalse();
    });

    it('answers 403 on every new route in production', function (string $method, string $route, int|string|null $parameter) {
        $url = previewUrl($route, $parameter);
        $this->app->detectEnvironment(fn () => 'production');

        // CSRF is only skipped in the testing environment, so write requests carry a token.
        $response = actingAs($this->user)
            ->withSession(['_token' => 'preview-test'])
            ->{$method}($url, $method === 'get' ? [] : ['_token' => 'preview-test']);

        $response->assertForbidden();
    })->with('previewRoutes');

    // The 403 above could in theory come from elsewhere: prove it is the ability that refuses.
    it('refuses every new route with an authorization exception in production', function (string $method, string $route, int|string|null $parameter) {
        $url = previewUrl($route, $parameter);
        $this->app->detectEnvironment(fn () => 'production');
        $this->withoutExceptionHandling();

        actingAs($this->user)
            ->withSession(['_token' => 'preview-test'])
            ->{$method}($url, $method === 'get' ? [] : ['_token' => 'preview-test']);
    })->with('previewRoutes')->throws(AuthorizationException::class, 'This action is unauthorized.');

    // A valid body must not get past the lock either (the 403 comes before validation and before the preview action).
    it('refuses valid preview writes in production', function (string $method, string $uri, string $payload) {
        $this->app->detectEnvironment(fn () => 'production');

        actingAs($this->user)
            ->withSession(['_token' => 'preview-test'])
            ->{$method}($uri, [...previewPayload($payload), '_token' => 'preview-test'])
            ->assertForbidden();
    })->with([
        'products.store' => ['post', '/products', 'product'],
        'products.update' => ['put', '/products/1', 'product'],
        'products.destroy' => ['delete', '/products/1', 'none'],
        'sales.store' => ['post', '/sales', 'sale'],
        'sales.update' => ['put', '/sales/1', 'sale'],
        'sales.destroy' => ['delete', '/sales/1', 'none'],
    ]);

    it('still serves the real materials routes in production', function () {
        $material = Material::factory()->create();
        $this->app->detectEnvironment(fn () => 'production');

        actingAs($this->user)->get(route('materials.index'))->assertOk();
        actingAs($this->user)->get(route('materials.show', $material))->assertOk();
    });
});
