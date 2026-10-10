<?php

use App\Http\Requests\Materials\MaterialOptionsRequest;
use App\Models\Material;
use App\Models\User;
use App\Policies\MaterialPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

/*
|--------------------------------------------------------------------------
| GET materials/options (S-25, ADR-0009 rule 5)
|--------------------------------------------------------------------------
|
| The BOM picker's typeahead: a bare MaterialOption[] (name, then id), the matches first (up to `limit`), then the `include`d
| materials that were not among them. Runs the same number of queries whatever the size of the catalogue.
*/

/** A policy that refuses to list materials, to prove the route asks it. */
class DenyMaterialViewAnyPolicy extends MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }
}

beforeEach(fn () => $this->user = User::factory()->create());

/**
 * @param  array<string, mixed>  $query
 * @return list<array<string, mixed>>
 */
function materialOptions(mixed $test, array $query = []): array
{
    $response = actingAs($test->user)->getJson(route('materials.options', $query))->assertOk();

    return $response->json();
}

/**
 * @param  list<array<string, mixed>>  $options
 * @return list<string>
 */
function optionNames(array $options): array
{
    return array_column($options, 'name');
}

it('registers the route before the resource, behind auth and verified', function () {
    $route = Route::getRoutes()->getByName('materials.options');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('materials/options')
        ->and($route->methods())->toContain('GET')
        ->and($route->gatherMiddleware())->toContain('auth', 'verified')
        ->and(route('materials.options', [], false))->toBe('/materials/options');
});

it('is not read as a material id', function () {
    Material::factory()->create();

    actingAs($this->user)->getJson('/materials/options')->assertOk()->assertJsonCount(1);
});

it('redirects guests to login and answers 401 to an XHR guest', function () {
    $this->get(route('materials.options'))->assertRedirect(route('login'));
    getJson(route('materials.options'))->assertUnauthorized();
});

it('blocks unverified users', function () {
    actingAs(User::factory()->unverified()->create())
        ->get(route('materials.options'))
        ->assertRedirect(route('verification.notice'));
});

it('asks the policy', function () {
    Gate::policy(Material::class, DenyMaterialViewAnyPolicy::class);

    actingAs($this->user)->getJson(route('materials.options'))->assertForbidden();
});

it('answers a bare array with exactly the MaterialOption shape and scale-4 strings', function () {
    $material = Material::factory()->create([
        'sku' => 'MAT-DEN-012',
        'name' => 'Denim 12oz Indigo',
        'unit' => 'm',
        'unit_cost' => '8.5',
        'quantity_on_hand' => '250',
        'reorder_level' => '100',
    ]);

    $response = actingAs($this->user)->getJson(route('materials.options'))->assertOk();

    expect($response->json())->toBe([[
        'id' => $material->id,
        'sku' => 'MAT-DEN-012',
        'name' => 'Denim 12oz Indigo',
        'unit' => 'm',
        'unit_label' => 'Metre (m)',
        'unit_cost' => '8.5000',
        'quantity_on_hand' => '250.0000',
        'stock_status' => 'in_stock',
    ]]);
});

it('computes the stock status on the server', function () {
    Material::factory()->create(['name' => 'A out', 'quantity_on_hand' => '0', 'reorder_level' => '10']);
    Material::factory()->create(['name' => 'B low', 'quantity_on_hand' => '10', 'reorder_level' => '10']);
    Material::factory()->create(['name' => 'C ok', 'quantity_on_hand' => '11', 'reorder_level' => '10']);

    expect(array_column(materialOptions($this), 'stock_status'))->toBe(['out', 'low', 'in_stock']);
});

it('is empty for an empty catalogue', function () {
    actingAs($this->user)->getJson(route('materials.options'))->assertOk()->assertExactJson([]);
});

describe('search', function () {
    beforeEach(function () {
        Material::factory()->create(['name' => 'Denim 12oz Indigo', 'sku' => 'MAT-DEN-012']);
        Material::factory()->create(['name' => 'Cotton poplin', 'sku' => 'MAT-COT-001']);
        Material::factory()->create(['name' => 'Brass rivet', 'sku' => 'MAT-DENIM-RIV']);
    });

    it('lists everything by name without a search', function () {
        expect(optionNames(materialOptions($this)))->toBe(['Brass rivet', 'Cotton poplin', 'Denim 12oz Indigo']);
    });

    it('matches on the name, case-insensitively', function (string $term) {
        expect(optionNames(materialOptions($this, ['search' => $term])))->toBe(['Cotton poplin']);
    })->with(['lower' => 'poplin', 'upper' => 'POPLIN', 'mixed' => 'PoP', 'padded' => '  poplin  ']);

    it('matches on the SKU, case-insensitively', function () {
        expect(optionNames(materialOptions($this, ['search' => 'mat-cot'])))->toBe(['Cotton poplin']);
    });

    it('matches on the name or the SKU, by name then id', function () {
        expect(optionNames(materialOptions($this, ['search' => 'denim'])))->toBe(['Brass rivet', 'Denim 12oz Indigo']);
    });

    it('treats a blank search as none', function () {
        expect(materialOptions($this, ['search' => '   ']))->toHaveCount(3);
    });

    it('finds nothing for a term that matches nothing', function () {
        actingAs($this->user)->getJson(route('materials.options', ['search' => 'zzz']))->assertOk()->assertExactJson([]);
    });

    it('leaves soft-deleted materials out', function () {
        Material::query()->where('sku', 'MAT-COT-001')->firstOrFail()->delete();

        expect(optionNames(materialOptions($this)))->toBe(['Brass rivet', 'Denim 12oz Indigo'])
            ->and(materialOptions($this, ['search' => 'cotton']))->toBe([]);
    });
});

describe('ordering and limit', function () {
    it('orders by name, then id', function () {
        $second = Material::factory()->create(['name' => 'Twin']);
        $first = Material::factory()->create(['name' => 'Twin']);
        Material::factory()->create(['name' => 'Alpha']);

        $ids = array_column(materialOptions($this), 'id');

        expect(optionNames(materialOptions($this)))->toBe(['Alpha', 'Twin', 'Twin'])
            ->and(array_slice($ids, 1))->toBe([$second->id, $first->id]);
    });

    it('defaults to 20 rows', function () {
        Material::factory()->count(25)->create();

        expect(materialOptions($this))->toHaveCount(20);
    });

    it('honours the limit and takes the first rows by name', function () {
        foreach (['E', 'B', 'D', 'A', 'C'] as $name) {
            Material::factory()->create(['name' => $name]);
        }

        expect(optionNames(materialOptions($this, ['limit' => 3])))->toBe(['A', 'B', 'C'])
            ->and(materialOptions($this, ['limit' => 1]))->toHaveCount(1)
            ->and(materialOptions($this, ['limit' => 50]))->toHaveCount(5);
    });

    it('caps the rows at the maximum limit', function () {
        Material::factory()->count(55)->create();

        expect(materialOptions($this, ['limit' => 50]))->toHaveCount(50);
    });
});

describe('include', function () {
    beforeEach(function () {
        $this->materials = [];

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            $this->materials[$name] = Material::factory()->create(['name' => $name]);
        }
    });

    it('returns the selected rows even when they do not match, after the matches', function () {
        $options = materialOptions($this, ['search' => 'alp', 'include' => [$this->materials['Delta']->id, $this->materials['Bravo']->id]]);

        // The match first, then the included ones by name (they are not part of the `limit`).
        expect(optionNames($options))->toBe(['Alpha', 'Bravo', 'Delta']);
    });

    it('does not repeat a selected row that matches', function () {
        $options = materialOptions($this, ['search' => 'a', 'include' => [$this->materials['Alpha']->id, $this->materials['Delta']->id]]);

        expect(optionNames($options))->toBe(['Alpha', 'Bravo', 'Charlie', 'Delta'])
            ->and(array_unique(array_column($options, 'id')))->toHaveCount(4);
    });

    it('does not repeat an id sent twice', function () {
        $delta = $this->materials['Delta']->id;
        $options = materialOptions($this, ['search' => 'alp', 'include' => [$delta, $delta]]);

        expect(optionNames($options))->toBe(['Alpha', 'Delta']);
    });

    it('adds the selected rows on top of the limit', function () {
        $options = materialOptions($this, ['limit' => 1, 'include' => [$this->materials['Delta']->id]]);

        expect(optionNames($options))->toBe(['Alpha', 'Delta']);
    });

    it('skips soft-deleted and unknown ids', function () {
        $this->materials['Delta']->delete();

        $options = materialOptions($this, ['search' => 'alp', 'include' => [$this->materials['Delta']->id, NO_SUCH_ID]]);

        expect(optionNames($options))->toBe(['Alpha']);
    });

    it('sends the selected rows without a search too', function () {
        $options = materialOptions($this, ['limit' => 1, 'include' => [$this->materials['Charlie']->id, $this->materials['Bravo']->id]]);

        expect(optionNames($options))->toBe(['Alpha', 'Bravo', 'Charlie']);
    });

    it('accepts the largest list', function () {
        $ids = range(1, MaterialOptionsRequest::MAX_INCLUDE);

        actingAs($this->user)->getJson(route('materials.options', ['include' => $ids]))->assertOk();
    });
});

it('rejects invalid queries', function (array $query, string $field) {
    actingAs($this->user)->getJson(route('materials.options', $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'search too long' => [['search' => str_repeat('a', 101)], 'search'],
    'search is an array' => [['search' => ['a']], 'search'],
    'limit zero' => [['limit' => 0], 'limit'],
    'limit negative' => [['limit' => -1], 'limit'],
    'limit above the maximum' => [['limit' => 51], 'limit'],
    'limit not a number' => [['limit' => 'many'], 'limit'],
    'limit decimal' => [['limit' => '2.5'], 'limit'],
    'include is not an array' => [['include' => '1'], 'include'],
    'include too long' => [['include' => range(1, 51)], 'include'],
    'include not integers' => [['include' => ['abc']], 'include.0'],
    'include zero' => [['include' => [0]], 'include.0'],
    'include negative' => [['include' => [-3]], 'include.0'],
    'include beyond bigint' => [['include' => ['99999999999999999999']], 'include.0'],
]);

it('accepts the extreme valid values', function (array $query) {
    actingAs($this->user)->getJson(route('materials.options', $query))->assertOk();
})->with([
    'limit 1' => [['limit' => 1]],
    'limit 50' => [['limit' => 50]],
    'search of 100 characters' => [['search' => str_repeat('a', 100)]],
    'null search and limit' => [['search' => null, 'limit' => null, 'include' => null]],
]);

it('runs the same, small number of queries for 10 and for 200 materials', function () {
    actingAs($this->user);
    Material::factory()->count(9)->create();
    // One match and one selected row that does not match, so both queries (and their units) always run.
    Material::factory()->create(['name' => 'Denim test', 'sku' => 'MAT-DNM-1']);
    $selected = Material::factory()->create(['name' => 'Zed', 'sku' => 'ZED-1']);
    $url = route('materials.options', ['search' => 'denim', 'limit' => 50, 'include' => [$selected->id]]);

    // Warm up: the first request of a process may run one-off queries.
    $this->getJson($url)->assertOk();
    $few = queryLog(fn () => $this->getJson($url)->assertOk());

    Material::factory()->count(190)->create();
    $many = queryLog(fn () => $this->getJson($url)->assertOk());

    // The session user, the matches and their units, the included rows and their units: never one query per row.
    expect(count($few))->toBeLessThanOrEqual(6, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));
});

it('runs a bounded number of queries whatever the limit', function () {
    Material::factory()->count(60)->create();
    actingAs($this->user);
    $this->getJson(route('materials.options'))->assertOk();

    $counts = array_map(
        fn (int $limit): int => countQueries(fn () => $this->getJson(route('materials.options', ['limit' => $limit]))->assertOk()),
        [1, 20, 50],
    );

    expect(array_unique($counts))->toHaveCount(1)
        ->and($counts[0])->toBeLessThanOrEqual(4);
});
