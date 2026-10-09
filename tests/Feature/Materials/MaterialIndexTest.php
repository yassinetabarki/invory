<?php

use App\Http\Requests\Materials\IndexMaterialRequest;
use App\Models\Material;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * @return list<string>
 */
function indexedNames(mixed $response, string $key = 'name'): array
{
    return array_column($response->inertiaProps('materials.data'), $key);
}

it('lists materials with the agreed contract shape', function () {
    Material::factory()->count(3)->create();

    actingAs($this->user)->get(route('materials.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('materials/index')
            ->has('materials.data', 3)
            ->has('materials.data.0', fn (Assert $material) => $material->hasAll(materialContractKeys()))
            ->has('materials.links', fn (Assert $links) => $links->hasAll(['first', 'last', 'prev', 'next']))
            ->has('materials.meta', fn (Assert $meta) => $meta
                ->hasAll(['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']))
            ->where('materials.meta.total', 3)
            ->where('materials.meta.per_page', config('invory.per_page'))
            // The category options come from the shared `reference` prop (S-22): the page no longer sends them.
            ->missing('categories'));
});

it('sends the inventory summary over all materials, not just the current page', function () {
    Material::factory()->count(26)->create(['quantity_on_hand' => '2', 'unit_cost' => '0.5', 'reorder_level' => null]);
    Material::factory()->create(['quantity_on_hand' => '100', 'unit_cost' => '10'])->delete();

    foreach ([1, 2] as $page) {
        actingAs($this->user)->get(route('materials.index', ['page' => $page]))
            ->assertInertia(fn (Assert $props) => $props
                ->where('summary', ['materials_count' => 26, 'inventory_value' => '26.0000'])
                ->has('summary', fn (Assert $summary) => $summary->hasAll(['materials_count', 'inventory_value'])));
    }
});

it('keeps the summary independent of the filters', function () {
    Material::factory()->create(['name' => 'Denim', 'category' => 'fabric', 'quantity_on_hand' => '10', 'unit_cost' => '8.5']);
    Material::factory()->create(['name' => 'Thread', 'category' => 'thread', 'quantity_on_hand' => '1000', 'unit_cost' => '0.004']);

    actingAs($this->user)->get(route('materials.index', ['category' => 'thread', 'search' => 'zzz']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('materials.data', 0)
            ->where('summary', ['materials_count' => 2, 'inventory_value' => '89.0000']));
});

it('reports an empty inventory as zero with a scale-4 value', function () {
    actingAs($this->user)->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', ['materials_count' => 0, 'inventory_value' => '0.0000']));
});

it('serialises the photo and usage placeholders on every row until S-13c wires them', function () {
    Material::factory()->count(2)->create();

    actingAs($this->user)->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('materials.data.0.photo_url', null)
            ->where('materials.data.0.used_in_count', 0)
            ->where('materials.data.1.photo_url', null)
            ->where('materials.data.1.used_in_count', 0));
});

it('exposes all four filter keys with defaults', function () {
    actingAs($this->user)->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', [
                'search' => null,
                'category' => null,
                'low_stock' => false,
                'sort' => 'name',
            ])
            ->has('materials.data', 0));
});

it('echoes normalised filters back', function () {
    actingAs($this->user)->get(route('materials.index', [
        'search' => '  denim ',
        'category' => 'fabric',
        'low_stock' => '1',
        'sort' => '-unit_cost',
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('filters', [
            'search' => 'denim',
            'category' => 'fabric',
            'low_stock' => true,
            'sort' => '-unit_cost',
        ]));
});

it('treats empty filter values as absent', function () {
    actingAs($this->user)->get(route('materials.index', ['search' => '', 'category' => '', 'sort' => '', 'low_stock' => '0']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['search' => null, 'category' => null, 'low_stock' => false, 'sort' => 'name']));
});

it('serialises decimals as scale-4 strings', function () {
    Material::factory()->create(['unit_cost' => '8.5', 'quantity_on_hand' => '250', 'reorder_level' => '50']);

    actingAs($this->user)->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('materials.data.0.unit_cost', '8.5000')
            ->where('materials.data.0.quantity_on_hand', '250.0000')
            ->where('materials.data.0.reorder_level', '50.0000')
            ->where('materials.data.0.stock_value', '2125.0000'));
});

it('searches name and sku case-insensitively', function () {
    Material::factory()->create(['name' => 'Denim 12oz Indigo', 'sku' => 'FAB-001']);
    Material::factory()->create(['name' => 'Woven brand label', 'sku' => 'LAB-DENIM']);
    Material::factory()->create(['name' => 'Polyester thread', 'sku' => 'THR-001']);

    $byName = actingAs($this->user)->get(route('materials.index', ['search' => 'DENIM']));
    expect(indexedNames($byName))->toBe(['Denim 12oz Indigo', 'Woven brand label']);

    $bySku = actingAs($this->user)->get(route('materials.index', ['search' => 'thr-0']));
    expect(indexedNames($bySku))->toBe(['Polyester thread']);

    $none = actingAs($this->user)->get(route('materials.index', ['search' => 'zzz']));
    expect(indexedNames($none))->toBe([]);
});

it('filters by category', function () {
    Material::factory()->create(['name' => 'Denim', 'category' => 'fabric']);
    Material::factory()->create(['name' => 'Thread', 'category' => 'thread']);

    $response = actingAs($this->user)->get(route('materials.index', ['category' => 'thread']));

    expect(indexedNames($response))->toBe(['Thread']);
});

it('low_stock returns low and out materials only', function () {
    Material::factory()->create(['name' => 'A in stock', 'quantity_on_hand' => '100', 'reorder_level' => '50']);
    Material::factory()->create(['name' => 'B no threshold', 'quantity_on_hand' => '1', 'reorder_level' => null]);
    Material::factory()->create(['name' => 'C low', 'quantity_on_hand' => '40', 'reorder_level' => '50']);
    Material::factory()->create(['name' => 'D equal', 'quantity_on_hand' => '50', 'reorder_level' => '50']);
    Material::factory()->create(['name' => 'E out', 'quantity_on_hand' => '0', 'reorder_level' => '50']);
    Material::factory()->create(['name' => 'F out no threshold', 'quantity_on_hand' => '0', 'reorder_level' => null]);

    $response = actingAs($this->user)->get(route('materials.index', ['low_stock' => '1']));

    expect(indexedNames($response))->toBe(['C low', 'D equal', 'E out', 'F out no threshold']);
    expect(indexedNames($response, 'stock_status'))->toBe(['low', 'low', 'out', 'out']);
});

it('excludes soft-deleted materials from the list', function () {
    Material::factory()->create(['name' => 'Kept']);
    Material::factory()->create(['name' => 'Gone'])->delete();

    expect(indexedNames(actingAs($this->user)->get(route('materials.index'))))->toBe(['Kept']);
});

it('sorts by every whitelisted column in both directions', function (string $sort, array $expected) {
    // name, sku, quantity, cost and updated_at all order differently on purpose.
    $b = Material::factory()->create(['name' => 'Bravo', 'sku' => 'S-3', 'quantity_on_hand' => '20', 'unit_cost' => '3', 'updated_at' => now()->subDays(1)]);
    $a = Material::factory()->create(['name' => 'Alpha', 'sku' => 'S-2', 'quantity_on_hand' => '30', 'unit_cost' => '1', 'updated_at' => now()->subDays(3)]);
    $c = Material::factory()->create(['name' => 'Charlie', 'sku' => 'S-1', 'quantity_on_hand' => '10', 'unit_cost' => '2', 'updated_at' => now()->subDays(2)]);

    $response = actingAs($this->user)->get(route('materials.index', ['sort' => $sort]));

    expect(indexedNames($response))->toBe($expected);
})->with([
    'name' => ['name', ['Alpha', 'Bravo', 'Charlie']],
    '-name' => ['-name', ['Charlie', 'Bravo', 'Alpha']],
    'sku' => ['sku', ['Charlie', 'Alpha', 'Bravo']],
    '-sku' => ['-sku', ['Bravo', 'Alpha', 'Charlie']],
    'quantity_on_hand' => ['quantity_on_hand', ['Charlie', 'Bravo', 'Alpha']],
    '-quantity_on_hand' => ['-quantity_on_hand', ['Alpha', 'Bravo', 'Charlie']],
    'unit_cost' => ['unit_cost', ['Alpha', 'Charlie', 'Bravo']],
    '-unit_cost' => ['-unit_cost', ['Bravo', 'Charlie', 'Alpha']],
    'updated_at' => ['updated_at', ['Alpha', 'Charlie', 'Bravo']],
    '-updated_at' => ['-updated_at', ['Bravo', 'Charlie', 'Alpha']],
]);

it('uses id as a tiebreaker so ordering is deterministic', function () {
    $first = Material::factory()->create(['name' => 'Same']);
    $second = Material::factory()->create(['name' => 'Same']);
    $third = Material::factory()->create(['name' => 'Same']);

    foreach (['name', '-name'] as $sort) {
        $response = actingAs($this->user)->get(route('materials.index', ['sort' => $sort]));

        expect(indexedNames($response, 'id'))->toBe([$first->id, $second->id, $third->id]);
    }
});

it('paginates with the configured page size and keeps the query string in links', function () {
    Material::factory()->count(26)->create(['name' => 'Denim']);

    actingAs($this->user)->get(route('materials.index', ['search' => 'Denim', 'sort' => '-name']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('materials.data', 25)
            ->where('materials.meta.current_page', 1)
            ->where('materials.meta.last_page', 2)
            ->where('materials.meta.per_page', 25)
            ->where('materials.meta.total', 26)
            ->where('materials.links.prev', null)
            ->where('materials.links.next', fn (string $url) => str_contains($url, 'page=2')
                && str_contains($url, 'search=Denim')
                && str_contains($url, 'sort=-name')));

    actingAs($this->user)->get(route('materials.index', ['search' => 'Denim', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('materials.data', 1)
            ->where('materials.meta.current_page', 2)
            ->where('materials.links.next', null));
});

it('takes the page size from config', function () {
    config(['invory.per_page' => 10]);
    Material::factory()->count(12)->create();

    actingAs($this->user)->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('materials.data', 10)
            ->where('materials.meta.per_page', 10)
            ->where('materials.meta.last_page', 2));
});

it('rejects invalid index filters', function (array $query, string $field) {
    actingAs($this->user)->get(route('materials.index', $query))->assertSessionHasErrors($field);
})->with([
    'unknown sort' => [['sort' => 'color'], 'sort'],
    'sql in sort' => [['sort' => 'name; drop table materials'], 'sort'],
    'unknown category' => [['category' => 'bananas'], 'category'],
    'array search' => [['search' => ['a']], 'search'],
    'long search' => [['search' => str_repeat('a', 101)], 'search'],
    'non boolean low_stock' => [['low_stock' => 'maybe'], 'low_stock'],
    'page zero' => [['page' => '0'], 'page'],
    'page text' => [['page' => 'abc'], 'page'],
]);

it('derives the accepted sort values from the model allowlist', function () {
    expect(IndexMaterialRequest::sorts())->toEqualCanonicalizing([
        'name', '-name', 'sku', '-sku', 'quantity_on_hand', '-quantity_on_hand',
        'unit_cost', '-unit_cost', 'updated_at', '-updated_at',
    ]);
});

it('refuses to sort by a column outside the allowlist, whatever the caller validated', function (string $sort) {
    Material::query()->sortedBy($sort);
})->with([
    'unknown column' => 'color',
    'unknown column, descending' => '-supplier_name',
    'sql injection' => 'name; drop table materials',
    'empty' => '',
    'bare dash' => '-',
])->throws(InvalidArgumentException::class);
