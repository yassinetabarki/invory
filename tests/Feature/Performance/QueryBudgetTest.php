<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\Sale;
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
    $this->user = prepareDemo($this);
    $this->material = demoMaterial('MAT-DEN-012');
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
    'products show' => [fn (Material $material): string => route('products.show', pid(1))],
    'products edit' => [fn (Material $material): string => route('products.edit', pid(1))],
    'sales index' => [fn (Material $material): string => route('sales.index')],
    'settings units' => [fn (Material $material): string => route('settings.units.index')],
    'settings material categories' => [fn (Material $material): string => route('settings.material-categories.index')],
    'settings product categories' => [fn (Material $material): string => route('settings.product-categories.index')],
]);

it('stays within the query budget and runs the same queries however many materials there are', function (Closure $url) {
    $uri = $url($this->material);
    actingAs($this->user);

    // Warm up: the first request of a process may run one-off queries (schema introspection, the framework's own caches).
    $this->get($uri)->assertOk();

    // 26 materials in all, so even the second page of the list has a row. The category and unit of the rows are eager-loaded
    // (two queries), and an empty page loads no row, so an empty baseline would run two queries fewer.
    Material::factory()->count(14)->create();

    $few = queryLog(fn () => $this->get($uri)->assertOk());

    Material::factory()->count(30)->create();

    $many = queryLog(fn () => $this->get($uri)->assertOk());

    expect(count($few))->toBeLessThanOrEqual(QUERY_BUDGET, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));
})->with('pages');

/**
 * Every page that reads products (and the pages that read their compositions), as a function of a product and a material.
 *
 * @return array<string, array{Closure(Product, Material): string}>
 */
dataset('productPages', [
    'dashboard' => [fn (Product $product, Material $material): string => route('dashboard')],
    'products index' => [fn (Product $product, Material $material): string => route('products.index')],
    'products index, cant produce' => [fn (Product $product, Material $material): string => route('products.index', ['cant_produce' => 1])],
    'products index, searched' => [fn (Product $product, Material $material): string => route('products.index', ['search' => 'PRD', 'category' => 'apparel', 'status' => 'active'])],
    'products create' => [fn (Product $product, Material $material): string => route('products.create')],
    'products show' => [fn (Product $product, Material $material): string => route('products.show', $product)],
    'products edit' => [fn (Product $product, Material $material): string => route('products.edit', $product)],
    'sales index' => [fn (Product $product, Material $material): string => route('sales.index')],
    'materials index' => [fn (Product $product, Material $material): string => route('materials.index')],
    'materials show' => [fn (Product $product, Material $material): string => route('materials.show', $material)],
    'materials edit' => [fn (Product $product, Material $material): string => route('materials.edit', $material)],
    'settings product categories' => [fn (Product $product, Material $material): string => route('settings.product-categories.index')],
]);

it('stays within the query budget and runs the same queries with 3 and with 30 products', function (Closure $url) {
    // Start from 3 products (the demo ones and their sales go), each with a composition of 3 of the demo materials.
    Sale::query()->delete();
    Product::query()->forceDelete();

    $lines = fn (): array => array_map(
        fn (string $sku): array => ['material' => demoMaterial($sku), 'quantity' => '1.5000', 'waste_percent' => '5.00'],
        ['MAT-DEN-012', 'MAT-BTN-017', 'MAT-THR-PES'],
    );

    Product::factory()->count(3)->withComponents($lines())->create(['category' => 'apparel', 'status' => 'active']);

    $product = Product::query()->orderBy('id')->firstOrFail();
    $uri = $url($product, $this->material);
    actingAs($this->user);

    // Warm up: the first request of a process may run one-off queries (schema introspection, the framework's own caches).
    $this->get($uri)->assertOk();

    $few = queryLog(fn () => $this->get($uri)->assertOk());

    Product::factory()->count(27)->withComponents($lines())->create(['category' => 'apparel', 'status' => 'active']);

    $many = queryLog(fn () => $this->get($uri)->assertOk());

    expect(count($few))->toBeLessThanOrEqual(QUERY_BUDGET, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));
})->with('productPages');

it('stays within the query budget and runs the same queries with 3 and with 25 orders of 5 lines', function () {
    $lines = array_map(fn (int $number): array => [demoProduct(DEMO_PRODUCT_SKUS[$number]), 10 * $number], [1, 2, 3, 4, 5]);
    $place = function (int $count) use ($lines): void {
        for ($i = 0; $i < $count; $i++) {
            bookOrder(['client' => "Client {$i}"], $lines);
        }
    };
    $uri = route('orders.index');
    actingAs($this->user);

    $place(3);

    // Warm up: the first request of a process may run one-off queries (schema introspection, the framework's own caches).
    $this->get($uri)->assertOk();

    $few = queryLog(fn () => $this->get($uri)->assertOk());

    $place(22);

    $many = queryLog(fn () => $this->get($uri)->assertOk());

    expect(count($few))->toBeLessThanOrEqual(QUERY_BUDGET, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));
});

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

it('stays within the query budget when the deferred props of the product page load, however many sales there are', function () {
    actingAs($this->user);
    $uri = route('products.show', pid(1));
    // Only the deferred request is measured: the first visit happens outside the query log.
    $deferredQueries = function () use ($uri): array {
        $firstVisit = $this->get($uri)->assertOk();

        return queryLog(fn () => $firstVisit->assertInertia(
            fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded->hasAll(['product', 'sales_last_30_days', 'recent_sales'])),
        ));
    };

    // Warm up: the first request of a process may run one-off queries (schema introspection, the framework's own caches).
    $deferredQueries();

    $few = $deferredQueries();

    Sale::factory()->for(demoProduct('PRD-JKT-001'))->count(30)->create();

    $many = $deferredQueries();

    expect(count($few))->toBeLessThanOrEqual(QUERY_BUDGET, implode(PHP_EOL, $few))
        ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many));
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
