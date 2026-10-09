<?php

use App\Models\Material;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\ExpectationFailedException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Query budgets per page (ADR-0009 rule 8, S-20)
|--------------------------------------------------------------------------
|
| Every page stays within 10 SQL queries and runs the same number of queries with 12 and with 42 materials (so no N+1: the
| count may not grow with the number of rows). The 12 DemoCatalogSeeder materials are in the database, the clock is frozen.
| Strict models already throw on a lazy load; this also catches a query repeated per row.
*/

/** The most queries any page may run (the plan's budget, ≤ 10). */
const QUERY_BUDGET = 10;

beforeEach(function () {
    $this->user = preparePreview($this);
    $this->material = previewMaterial('MAT-DEN-012');
});

/**
 * Every page, as a function of a material (the one the show and edit pages are about).
 *
 * @return array<string, array{Closure(Material): string}>
 */
dataset('pages', [
    'dashboard' => [fn (Material $material): string => route('dashboard')],
    'materials index' => [fn (Material $material): string => route('materials.index')],
    'materials index, second page' => [fn (Material $material): string => route('materials.index', ['page' => 2])],
    'materials index, filtered and sorted' => [fn (Material $material): string => route('materials.index', ['search' => 'a', 'category' => 'fabric', 'sort' => '-quantity_on_hand'])],
    'materials create' => [fn (Material $material): string => route('materials.create')],
    'materials show' => [fn (Material $material): string => route('materials.show', $material)],
    'materials edit' => [fn (Material $material): string => route('materials.edit', $material)],
    'products index' => [fn (Material $material): string => route('products.index')],
    'products create' => [fn (Material $material): string => route('products.create')],
    'products show' => [fn (Material $material): string => route('products.show', 1)],
    'products edit' => [fn (Material $material): string => route('products.edit', 1)],
    'sales index' => [fn (Material $material): string => route('sales.index')],
]);

it('stays within the query budget and runs the same queries however many materials there are', function (Closure $url) {
    $uri = $url($this->material);
    actingAs($this->user);

    // Warm up: the first request of a process may run one-off queries (schema introspection, the framework's own caches).
    $this->get($uri)->assertOk();

    $few = queryLog(fn () => $this->get($uri)->assertOk());

    Material::factory()->count(30)->create();

    $many = queryLog(fn () => $this->get($uri)->assertOk());

    expect(count($few))->toBeLessThanOrEqual(QUERY_BUDGET, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));
})->with('pages');

it('stays within the query budget when the deferred recent history of the materials list loads', function () {
    actingAs($this->user);
    $firstVisit = $this->get(route('materials.index'))->assertOk();

    $queries = partialReloadQueries($firstVisit, ['recent_history'], fn (Assert $partial) => $partial->has('recent_history'));

    expect(count($queries))->toBeLessThanOrEqual(QUERY_BUDGET, implode(PHP_EOL, $queries));

    Material::factory()->count(30)->create();
    $firstVisit = $this->get(route('materials.index'))->assertOk();

    expect(partialReloadQueries($firstVisit, ['recent_history'], fn (Assert $partial) => $partial->has('recent_history')))
        ->toHaveCount(count($queries));
});

it('has a reusable query budget expectation', function () {
    actingAs($this->user);

    expect(fn () => $this->get(route('materials.create'))->assertOk())->toRunAtMostQueries(QUERY_BUDGET);

    expect(countQueries(fn () => Material::query()->count()))->toBe(1)
        ->and(countQueries(fn () => null))->toBe(0);
});

it('fails the query budget expectation when a callback runs too many queries', function () {
    expect(fn () => expect(function () {
        Material::query()->count();
        Material::query()->count();
    })->toRunAtMostQueries(1))->toThrow(ExpectationFailedException::class);
});
