<?php

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use App\Policies\MaterialCategoryPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\UnitPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Routes and authorization of the reference-data settings (ADR-0008, decision Q3)
|--------------------------------------------------------------------------
|
| Any verified user may manage units and categories, so the policies allow everything. The policies are still asked on every
| route, including the index, so a roles story can tighten them in one place: the deny tests below prove they are asked.
*/

/** A policy that refuses everything. */
class DenyEverythingPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $model): bool
    {
        return false;
    }

    public function delete(User $user, mixed $model): bool
    {
        return false;
    }
}

/**
 * Every route of the three settings pages: [HTTP method, route name, model class, valid payload].
 *
 * @return array<string, array{string, string, class-string, array<string, mixed>}>
 */
dataset('referenceRoutes', (function (): array {
    $rows = [];

    foreach ([
        'units' => [Unit::class, ['code' => 'inch', 'name' => 'Inch', 'dimension' => 'length', 'factor_to_base' => '0.0254']],
        'material-categories' => [MaterialCategory::class, ['code' => 'elastic', 'name' => 'Elastic']],
        'product-categories' => [ProductCategory::class, ['code' => 'elastic', 'name' => 'Elastic']],
    ] as $slug => [$model, $payload]) {
        $rows["{$slug} index"] = ['get', "settings.{$slug}.index", $model, []];
        $rows["{$slug} store"] = ['post', "settings.{$slug}.store", $model, $payload];
        $rows["{$slug} update"] = ['put', "settings.{$slug}.update", $model, ['name' => 'Renamed']];
        $rows["{$slug} destroy"] = ['delete', "settings.{$slug}.destroy", $model, []];
        $rows["{$slug} archive"] = ['patch', "settings.{$slug}.archive", $model, []];
        $rows["{$slug} restore"] = ['patch', "settings.{$slug}.restore", $model, []];
    }

    return $rows;
})());

/** The URL of a route, with a freshly made custom row as its parameter when it takes one. */
function referenceUrl(string $route, string $model): string
{
    $needsModel = in_array(last(explode('.', $route)), ['update', 'destroy', 'archive', 'restore'], true);

    return route($route, $needsModel ? [$model::factory()->create()->id] : []);
}

it('redirects guests to login on every route', function (string $method, string $route, string $model, array $payload) {
    $url = referenceUrl($route, $model);

    $this->{$method}($url, $payload)->assertRedirect(route('login'));
})->with('referenceRoutes');

it('blocks unverified users on every route', function (string $method, string $route, string $model, array $payload) {
    $url = referenceUrl($route, $model);
    $before = $model::query()->count();

    actingAs(User::factory()->unverified()->create())->{$method}($url, $payload)->assertRedirect(route('verification.notice'));

    expect($model::query()->count())->toBe($before);
})->with('referenceRoutes');

it('asks the policy on every route, the index included, and refuses with 403 when it denies', function (string $method, string $route, string $model, array $payload) {
    Gate::policy($model, DenyEverythingPolicy::class);
    $url = referenceUrl($route, $model);
    $before = $model::query()->count();
    $names = $model::query()->pluck('name', 'id')->all();

    actingAs(User::factory()->create())->{$method}($url, $payload)->assertForbidden();

    expect($model::query()->count())->toBe($before)
        ->and($model::query()->pluck('name', 'id')->all())->toBe($names);
})->with('referenceRoutes');

it('refuses with an authorization exception, not some other failure', function (string $method, string $route, string $model, array $payload) {
    Gate::policy($model, DenyEverythingPolicy::class);
    $url = referenceUrl($route, $model);
    $this->withoutExceptionHandling();

    actingAs(User::factory()->create())->{$method}($url, $payload);
})->with('referenceRoutes')->throws(AuthorizationException::class);

it('allows everything to a verified user in the three policies (decision Q3)', function (string $policy, string $model) {
    $user = User::factory()->create();
    $row = $model::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', $model))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', $model))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $row))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $row))->toBeTrue()
        ->and(Gate::getPolicyFor($model))->toBeInstanceOf($policy);
})->with([
    'units' => [UnitPolicy::class, Unit::class],
    'material categories' => [MaterialCategoryPolicy::class, MaterialCategory::class],
    'product categories' => [ProductCategoryPolicy::class, ProductCategory::class],
]);

it('registers the six routes of each settings page with the agreed URIs, names and middleware', function (string $slug, string $parameter) {
    $expected = [
        ["settings.{$slug}.index", 'GET', "settings/{$slug}"],
        ["settings.{$slug}.store", 'POST', "settings/{$slug}"],
        ["settings.{$slug}.update", 'PUT', "settings/{$slug}/{{$parameter}}"],
        ["settings.{$slug}.destroy", 'DELETE', "settings/{$slug}/{{$parameter}}"],
        ["settings.{$slug}.archive", 'PATCH', "settings/{$slug}/{{$parameter}}/archive"],
        ["settings.{$slug}.restore", 'PATCH', "settings/{$slug}/{{$parameter}}/restore"],
    ];

    foreach ($expected as [$name, $method, $uri]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->uri())->toBe($uri)
            ->and($route->methods())->toContain($method)
            ->and($route->gatherMiddleware())->toContain('auth', 'verified');
    }
})->with([
    'units' => ['units', 'unit'],
    'material categories' => ['material-categories', 'material_category'],
    'product categories' => ['product-categories', 'product_category'],
]);

it('constrains the id to 1 to 18 digits, so anything else is a 404', function (string $slug) {
    $user = User::factory()->create();

    foreach (['abc', '1e3', '-1', str_repeat('9', 19), '0x1'] as $id) {
        actingAs($user)->put("/settings/{$slug}/{$id}", ['name' => 'X'])->assertNotFound();
        actingAs($user)->delete("/settings/{$slug}/{$id}")->assertNotFound();
        actingAs($user)->patch("/settings/{$slug}/{$id}/archive")->assertNotFound();
        actingAs($user)->patch("/settings/{$slug}/{$id}/restore")->assertNotFound();
    }
})->with(['units', 'material-categories', 'product-categories']);
