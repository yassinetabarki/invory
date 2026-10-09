<?php

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
|--------------------------------------------------------------------------
| The shared `reference` once-prop (ADR-0008, ADR-0009)
|--------------------------------------------------------------------------
|
| Signed-in users get the lists once: the prop is registered under `reference:{version}`, the client remembers that key and
| sends it back in `X-Inertia-Except-Once-Props`, and the server skips the work until an Action bumps the version.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * A page visit as the SPA makes it after the first load: an Inertia request that says which once-props it already holds.
 *
 * @param  list<string>  $held
 * @return TestResponse<Response>
 */
function spaVisit(string $url, array $held = []): TestResponse
{
    $first = get($url);
    $headers = ['X-Inertia' => 'true', 'X-Inertia-Version' => $first->inertiaPage()['version']];

    if ($held !== []) {
        $headers['X-Inertia-Except-Once-Props'] = implode(',', $held);
    }

    $response = test()->withHeaders($headers)->get($url);

    // The headers would stick to the next request of the test, which must be a full load again.
    test()->flushHeaders();

    return $response;
}

it('shares the reference lists with signed-in users on every kind of page', function (string $route) {
    actingAs($this->user)->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('reference', fn (Assert $reference) => $reference
            ->hasAll(['units', 'material_categories', 'product_categories', 'product_statuses', 'sales_periods', 'stock_statuses'])
            ->has('units', 13)
            ->has('material_categories', 12)
            ->has('product_categories', 6)));
})->with(['dashboard', 'materials.index', 'materials.create', 'products.index', 'sales.index', 'settings.units.index', 'appearance.edit']);

it('sends exactly the contract: lists with their row keys, and the options of the enums that stay in PHP', function () {
    $reference = actingAs($this->user)->get(route('dashboard'))->inertiaProps('reference');

    expect(array_keys($reference))->toBe(['units', 'material_categories', 'product_categories', 'product_statuses', 'sales_periods', 'stock_statuses'])
        ->and(array_keys($reference['units'][0]))->toBe(['id', 'code', 'name', 'position', 'archived', 'dimension', 'dimension_label', 'factor_to_base', 'is_system'])
        ->and(array_keys($reference['material_categories'][0]))->toBe(['id', 'code', 'name', 'position', 'archived'])
        ->and(array_keys($reference['product_categories'][0]))->toBe(['id', 'code', 'name', 'position', 'archived'])
        ->and($reference['units'][0])->toBe([
            'id' => Unit::query()->where('code', 'm')->value('id'),
            'code' => 'm',
            'name' => 'Metre',
            'position' => 0,
            'archived' => false,
            'dimension' => 'length',
            'dimension_label' => 'Length',
            'factor_to_base' => '1.000000000000',
            'is_system' => true,
        ])
        ->and($reference['material_categories'][0])->toMatchArray(['code' => 'fabric', 'name' => 'Fabric', 'position' => 0, 'archived' => false])
        ->and($reference['product_categories'][0])->toMatchArray(['code' => 'apparel', 'name' => 'Apparel', 'position' => 0, 'archived' => false])
        ->and($reference['product_statuses'])->toBe([['value' => 'draft', 'label' => 'Draft'], ['value' => 'active', 'label' => 'Active'], ['value' => 'archived', 'label' => 'Archived']])
        ->and(array_column($reference['sales_periods'], 'value'))->toBe(['last_7_days', 'last_30_days', 'this_month', 'this_year'])
        ->and($reference['stock_statuses'])->toBe([['value' => 'in_stock', 'label' => 'In stock'], ['value' => 'low', 'label' => 'Low stock'], ['value' => 'out', 'label' => 'Out of stock']]);
});

it('keeps the units and categories as codes with a null factor for package units, never floats', function () {
    $units = collect(actingAs($this->user)->get(route('dashboard'))->inertiaProps('reference.units'));

    expect($units->firstWhere('code', 'roll')['factor_to_base'])->toBeNull()
        ->and($units->firstWhere('code', 'yd')['factor_to_base'])->toBe('0.914400000000')
        ->and($units->whereNotNull('factor_to_base')->every(fn (array $unit): bool => is_string($unit['factor_to_base'])))->toBeTrue();
});

it('lists archived rows with the flag, ordered by position then name', function () {
    Unit::factory()->archived()->create(['code' => 'old', 'name' => 'Old', 'position' => 0]);
    MaterialCategory::factory()->archived()->create(['code' => 'old', 'name' => 'Old', 'position' => 0]);
    ProductCategory::factory()->create(['code' => 'early', 'name' => 'Aaa', 'position' => 0]);

    $reference = actingAs($this->user)->get(route('dashboard'))->inertiaProps('reference');

    // position 0 holds the first default and the new row; the name decides.
    expect(collect($reference['units'])->pluck('code')->take(2)->all())->toBe(['m', 'old'])
        ->and(collect($reference['units'])->firstWhere('code', 'old')['archived'])->toBeTrue()
        ->and(collect($reference['units'])->firstWhere('code', 'm')['archived'])->toBeFalse()
        ->and(collect($reference['material_categories'])->firstWhere('code', 'old')['archived'])->toBeTrue()
        ->and(collect($reference['product_categories'])->pluck('code')->take(2)->all())->toBe(['early', 'apparel']);
});

it('has no reference key for guests', function (string $url) {
    get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->missing('reference'));
})->with(['the welcome page' => ['/'], 'the login page' => ['/login'], 'the register page' => ['/register']]);

it('has no once-prop metadata for guests either', function () {
    $page = spaVisit('/login')->json();

    expect($page['props'])->not->toHaveKey('reference')
        ->and($page)->not->toHaveKey('onceProps')
        ->and($page['sharedProps'])->not->toContain('reference');
});

it('registers the prop as a once-prop under reference:{version}', function () {
    $version = app(ReferenceVersion::class)->current();

    actingAs($this->user);
    $page = spaVisit(route('dashboard'))->json();

    expect($version)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/')
        ->and($page['onceProps'])->toBe(["reference:{$version}" => ['prop' => 'reference', 'expiresAt' => null]])
        ->and($page['sharedProps'])->toContain('reference');
});

describe('the client already holds the current key', function () {
    it('gets the page without the reference lists, and the work is skipped', function () {
        actingAs($this->user);
        $key = app(ReferenceVersion::class)->propKey();

        $response = spaVisit(route('settings.units.index'), [$key]);

        expect($response->json('props'))->not->toHaveKey('reference')
            // The page prop is still there, and the once metadata still tells the client which key it holds.
            ->and($response->json('props'))->toHaveKey('units')
            ->and($response->json('onceProps'))->toHaveKey($key);
    });

    it('does not even query the reference tables', function () {
        actingAs($this->user);
        $key = app(ReferenceVersion::class)->propKey();

        $withoutKey = queryLog(fn () => spaVisit(route('appearance.edit')));
        $withKey = queryLog(fn () => spaVisit(route('appearance.edit'), [$key]));

        // spaVisit makes two requests (a full load to learn the asset version, then the visit): the visit alone saves the three
        // list queries.
        expect(count($withoutKey) - count($withKey))->toBe(REFERENCE_QUERIES)
            ->and(queriesMatching($withKey, '"units"|"material_categories"|"product_categories"'))->toHaveCount(REFERENCE_QUERIES);
    });

    it('still sends the reference lists when the client holds some other key', function () {
        actingAs($this->user);

        $response = spaVisit(route('appearance.edit'), ['reference:01HZZZZZZZZZZZZZZZZZZZZZZZ']);

        expect($response->json('props.reference.units'))->toHaveCount(13);
    });

    it('still sends the reference lists to a client that holds nothing', function () {
        actingAs($this->user);

        expect(spaVisit(route('appearance.edit'))->json('props.reference.material_categories'))->toHaveCount(12);
    });
});

describe('a reference write', function () {
    it('makes the next visit send the lists again, under a new key', function () {
        actingAs($this->user);
        $oldKey = app(ReferenceVersion::class)->propKey();

        // The client holds the current key: nothing is sent.
        expect(spaVisit(route('appearance.edit'), [$oldKey])->json('props'))->not->toHaveKey('reference');

        $this->post(route('settings.material-categories.store'), ['code' => 'elastic', 'name' => 'Elastic'])->assertRedirect();

        $newKey = app(ReferenceVersion::class)->propKey();
        $stale = spaVisit(route('appearance.edit'), [$oldKey]);

        expect($newKey)->not->toBe($oldKey)
            // The client still holds the OLD key, so it gets the lists again, with the new category, and learns the new key.
            ->and(collect($stale->json('props.reference.material_categories'))->pluck('code')->all())->toContain('elastic')
            ->and($stale->json('onceProps'))->toHaveKey($newKey)
            ->and($stale->json('onceProps'))->not->toHaveKey($oldKey)
            // Holding the new key it is skipped again.
            ->and(spaVisit(route('appearance.edit'), [$newKey])->json('props'))->not->toHaveKey('reference');
    });

    it('is what every one of the reference Actions does', function (Closure $write) {
        actingAs($this->user);
        $before = app(ReferenceVersion::class)->current();

        $write($this);

        expect(app(ReferenceVersion::class)->current())->not->toBe($before);
    })->with([
        'create a unit' => [fn () => test()->post(route('settings.units.store'), ['code' => 'inch', 'name' => 'Inch', 'dimension' => 'length', 'factor_to_base' => '0.0254'])],
        'update a unit' => [fn () => test()->put(route('settings.units.update', Unit::query()->where('code', 'm')->value('id')), ['name' => 'Meter'])],
        'archive a unit' => [fn () => test()->patch(route('settings.units.archive', Unit::query()->where('code', 'm')->value('id')))],
        'restore a unit' => [fn () => test()->patch(route('settings.units.restore', Unit::factory()->archived()->create()->id))],
        'delete a unit' => [fn () => test()->delete(route('settings.units.destroy', Unit::factory()->create()->id))],
        'create a material category' => [fn () => test()->post(route('settings.material-categories.store'), ['code' => 'elastic', 'name' => 'Elastic'])],
        'update a material category' => [fn () => test()->put(route('settings.material-categories.update', MaterialCategory::query()->value('id')), ['name' => 'Cloth'])],
        'archive a material category' => [fn () => test()->patch(route('settings.material-categories.archive', MaterialCategory::query()->value('id')))],
        'restore a material category' => [fn () => test()->patch(route('settings.material-categories.restore', MaterialCategory::factory()->archived()->create()->id))],
        'delete a material category' => [fn () => test()->delete(route('settings.material-categories.destroy', MaterialCategory::factory()->create()->id))],
        'create a product category' => [fn () => test()->post(route('settings.product-categories.store'), ['code' => 'elastic', 'name' => 'Elastic'])],
        'update a product category' => [fn () => test()->put(route('settings.product-categories.update', ProductCategory::query()->value('id')), ['name' => 'Clothes'])],
        'archive a product category' => [fn () => test()->patch(route('settings.product-categories.archive', ProductCategory::query()->value('id')))],
        'restore a product category' => [fn () => test()->patch(route('settings.product-categories.restore', ProductCategory::factory()->archived()->create()->id))],
        'delete a product category' => [fn () => test()->delete(route('settings.product-categories.destroy', ProductCategory::factory()->create()->id))],
    ]);
});

describe('the version', function () {
    it('is a ULID kept in the cache under invory.reference.version', function () {
        $version = app(ReferenceVersion::class)->current();

        expect(ReferenceVersion::KEY)->toBe('invory.reference.version')
            ->and(Cache::get('invory.reference.version'))->toBe($version)
            ->and($version)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
    });

    it('stays the same until something bumps it', function () {
        $service = app(ReferenceVersion::class);

        expect($service->current())->toBe($service->current())
            ->and($service->propKey())->toBe("reference:{$service->current()}");
    });

    it('is replaced by a new ULID on every bump', function () {
        $service = app(ReferenceVersion::class);
        $first = $service->current();
        $second = $service->bump();
        $third = $service->bump();

        expect($second)->not->toBe($first)
            ->and($third)->not->toBe($second)
            ->and($service->current())->toBe($third)
            ->and($second)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
    });

    it('is created on a cache miss, so a flushed cache costs each client one refetch', function () {
        actingAs($this->user);
        $oldKey = app(ReferenceVersion::class)->propKey();

        Cache::forget(ReferenceVersion::KEY);

        $response = spaVisit(route('appearance.edit'), [$oldKey]);
        $newKey = app(ReferenceVersion::class)->propKey();

        expect($newKey)->not->toBe($oldKey)
            ->and($response->json('props.reference.units'))->toHaveCount(13)
            ->and(Cache::get(ReferenceVersion::KEY))->not->toBeNull();
    });
});
