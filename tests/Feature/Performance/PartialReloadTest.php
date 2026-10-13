<?php

use App\Models\Material;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Partial reloads skip unrequested work (ADR-0009 rule 1, S-20)
|--------------------------------------------------------------------------
|
| The props are closures, so a reload with `X-Inertia-Partial-Data` runs only what it lists. The frontend sends these `only`
| lists (materials, products, sales, dashboard). Each test checks the props that are absent and the queries that did not run.
| A full visit is the positive control: the same SQL must show up there, so the matcher really matches.
*/

/** The inventory summary of InventorySummary: the PHP scan of the stock-maths columns (SQLite) or the SQL aggregate (PostgreSQL, ADR-0011). */
const SUMMARY_SCAN = 'select\s+"id",\s*"quantity_on_hand",\s*"unit_cost",\s*"reorder_level"\s+from\s+"materials"|sum\(round\(quantity_on_hand \* unit_cost, 4\)\)';

/** The "needs restock" list of the dashboard (out of stock first). */
const NEEDS_RESTOCK_QUERY = 'case when quantity_on_hand';

beforeEach(function () {
    $this->user = prepareDemo($this);
    actingAs($this->user);
});

describe('materials index', function () {
    it('sends every prop on a full visit, with the inventory summary scan', function () {

        $queries = queryLog(function () {
            $this->get(route('materials.index'))
                ->assertInertia(fn (Assert $page) => $page
                    ->hasAll(['materials', 'filters', 'summary'])
                    ->missing('categories')
                    ->where('summary.materials_count', 12));
        });

        expect(queriesMatching($queries, SUMMARY_SCAN))->toHaveCount(1);
    });

    it('skips the summary and its inventory scan when only the table, the filters and the history are requested', function () {
        $firstVisit = $this->get(route('materials.index', ['search' => 'denim']))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['materials', 'filters', 'recent_history'],
            fn (Assert $partial) => $partial
                ->missing('summary')
                ->where('filters.search', 'denim')
                ->has('materials.data', 1)
                ->has('recent_history'),
        );

        expect(queriesMatching($queries, SUMMARY_SCAN))->toBeEmpty()
            // The page itself: the count, the rows, and the categories and units of those rows (eager-loaded, two constant queries).
            // (The category option query is gone: S-22 moved the options to the shared reference prop.)
            // Plus the one ledger query of `recent_history`, which is part of this reload.
            ->and(queriesMatching($queries, 'stock_movements'))->toHaveCount(1)
            ->and($queries)->toHaveCount(5);
    });

    it('runs the summary scan and nothing else when only the summary is requested', function () {
        $firstVisit = $this->get(route('materials.index'))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['summary'],
            fn (Assert $partial) => $partial
                ->missingAll(['materials', 'filters', 'recent_history'])
                ->where('summary.materials_count', 12),
        );

        expect(queriesMatching($queries, SUMMARY_SCAN))->toHaveCount(1)
            ->and($queries)->toHaveCount(1);
    });

    it('sends the same props on a partial reload as on a full visit', function () {
        $firstVisit = $this->get(route('materials.index', ['category' => 'fabric', 'sort' => '-quantity_on_hand']))->assertOk();
        $full = $firstVisit->inertiaProps();

        $firstVisit->assertInertia(fn (Assert $page) => $page->reloadOnly(
            ['materials', 'filters', 'summary'],
            fn (Assert $partial) => $partial
                ->where('materials', $full['materials'])
                ->where('filters', $full['filters'])
                ->where('summary', $full['summary']),
        ));
    });
});

describe('products index', function () {
    it('skips the status list when only the list and the filters are requested', function () {
        $firstVisit = $this->get(route('products.index', ['status' => 'active']))->assertOk();
        $full = $firstVisit->inertiaProps();

        partialReloadQueries(
            $firstVisit,
            ['products', 'filters'],
            fn (Assert $partial) => $partial
                ->missing('statuses')
                ->where('products', $full['products'])
                ->where('filters.status', 'active'),
        );
    });

    it('costs no product when the list is not requested', function () {
        $firstVisit = $this->get(route('products.index'))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['statuses', 'filters'],
            fn (Assert $partial) => $partial->missing('products')->missing('categories')->has('statuses'),
        );

        // No query at all: the status list is a free enum list, and no product is costed (no materials read).
        expect($queries)->toBeEmpty();
    });
});

describe('products create and edit', function () {
    it('sends no materials on the create page: the picker searches them itself (S-25)', function () {
        $firstVisit = $this->get(route('products.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('materials')->has('statuses', 3));

        $queries = partialReloadQueries(
            $firstVisit,
            ['statuses'],
            fn (Assert $partial) => $partial->missing('materials')->missing('categories')->has('statuses'),
        );

        // No query: the status list is a free enum list, and there is no picker prop to read the materials for.
        expect($queries)->toBeEmpty();
    });

    it('reads only the materials of the lines to cost the product on the edit page', function () {
        $firstVisit = $this->get(route('products.edit', pid(1)))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['product'],
            fn (Assert $partial) => $partial->missing('materials')->has('product.components'),
        );

        // The route-model binding of the product, its category and lines, ONE read of the materials (those of the lines) and their
        // units (eager-loaded).
        expect(queriesMatching($queries, 'from "materials"'))->toHaveCount(1)
            ->and($queries)->toHaveCount(5);
    });

    it('loads nothing on the edit page when only the status list is requested', function () {
        $firstVisit = $this->get(route('products.edit', pid(1)))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['statuses'],
            fn (Assert $partial) => $partial->missingAll(['materials', 'product', 'categories']),
        );

        // Only the route-model binding of the product: neither the materials, nor the category and the lines are loaded.
        expect($queries)->toHaveCount(1)
            ->and(queriesMatching($queries, 'from "materials"'))->toBeEmpty();
    });
});

describe('sales index', function () {
    it('skips the product options, the periods and the record prop when only the log and the totals are requested', function () {
        $firstVisit = $this->get(route('sales.index', ['period' => 'last_7_days']))->assertOk();
        $full = $firstVisit->inertiaProps();

        $queries = partialReloadQueries(
            $firstVisit,
            ['sales', 'filters', 'totals'],
            fn (Assert $partial) => $partial
                ->missingAll(['products', 'periods', 'record'])
                ->where('sales', $full['sales'])
                ->where('totals', $full['totals'])
                ->where('filters.period', 'last_7_days'),
        );

        // The page count, the page, the products of the page and the totals read: no product options, no costing.
        expect($queries)->toHaveCount(4)
            ->and(queriesMatching($queries, '"selling_price"'))->toBeEmpty()
            ->and(queriesMatching($queries, 'from "sales"'))->toHaveCount(3);
    });

    it('sends the product options and the record prop when they are requested', function () {
        $firstVisit = $this->get(route('sales.index', ['record' => 1]))->assertOk();
        $full = $firstVisit->inertiaProps();

        partialReloadQueries(
            $firstVisit,
            ['products', 'record'],
            fn (Assert $partial) => $partial
                ->missingAll(['sales', 'totals'])
                ->where('products', $full['products'])
                ->where('record', $full['record']),
        );

        expect($full['record'])->not->toBeNull();
    });
});

describe('dashboard', function () {
    it('runs the restock list in the deferred request, not in the first response', function () {
        $first = null;
        $firstQueries = queryLog(function () use (&$first) {
            $first = $this->get(route('dashboard'))
                ->assertInertia(fn (Assert $page) => $page->hasAll(['period', 'periods', 'stats', 'top_products'])->missingAll(['needs_restock', 'recent_sales', 'alerts']));
        });
        $deferredQueries = queryLog(fn () => $first->assertInertia(
            fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded->hasAll(['needs_restock', 'recent_sales', 'alerts'])),
        ));

        expect(queriesMatching($firstQueries, NEEDS_RESTOCK_QUERY))->toBeEmpty()
            ->and(queriesMatching($deferredQueries, NEEDS_RESTOCK_QUERY))->toHaveCount(1);
    });

    it('skips the restock list, the recent sales and the alerts on a period switch', function () {
        $firstVisit = $this->get(route('dashboard', ['period' => 'last_7_days']))->assertOk();
        $full = $firstVisit->inertiaProps();

        $queries = partialReloadQueries(
            $firstVisit,
            ['period', 'stats', 'top_products'],
            fn (Assert $partial) => $partial
                ->missingAll(['needs_restock', 'recent_sales', 'alerts', 'periods'])
                ->where('period', 'last_7_days')
                ->where('stats', $full['stats'])
                ->where('top_products', $full['top_products']),
        );

        expect(queriesMatching($queries, NEEDS_RESTOCK_QUERY))->toBeEmpty()
            // The stats still need the inventory summary, the sales of the period and the costed products (the units, the products,
            // their lines and the materials of those), once each; the rankings take their product refs from those products.
            ->and(queriesMatching($queries, SUMMARY_SCAN))->toHaveCount(1)
            ->and($queries)->toHaveCount(6);
    });

    it('builds the costed products once per request when several sections need them', function () {
        $first = null;
        $firstQueries = queryLog(function () use (&$first) {
            $first = $this->get(route('dashboard'))->assertOk();
        });
        $deferredQueries = queryLog(fn () => $first->assertInertia(fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded->has('alerts'))));

        // First request: one read of the products and their lines, shared by the stats and the rankings (whose product refs come
        // from them). Deferred request: one read again, shared by the alerts and the recent sales.
        foreach ([$firstQueries, $deferredQueries] as $queries) {
            expect(queriesMatching($queries, 'select \* from "products"'))->toHaveCount(1)
                ->and(queriesMatching($queries, 'from "product_components" where "product_components"."product_id" in'))->toHaveCount(1)
                ->and(queriesMatching($queries, 'from "products" where "id" in|from "products" where "products"."id" in'))->toBeEmpty();
        }
    });

    it('loads only the restock list when only that is requested', function () {
        $firstVisit = $this->get(route('dashboard'))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['needs_restock'],
            fn (Assert $partial) => $partial->missingAll(['stats', 'top_products', 'alerts', 'recent_sales']),
        );

        expect(queriesMatching($queries, SUMMARY_SCAN))->toBeEmpty()
            ->and(queriesMatching($queries, NEEDS_RESTOCK_QUERY))->toHaveCount(1)
            // The list itself plus the categories and units of its rows (eager-loaded).
            ->and($queries)->toHaveCount(3);
    });
});

describe('material show', function () {
    it('skips the usage costing when only the material is requested', function () {
        $material = demoMaterial('MAT-BTN-017');
        $firstVisit = $this->get(route('materials.show', $material))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['material'],
            fn (Assert $partial) => $partial->missingAll(['usage', 'history'])->where('material.id', $material->id),
        );

        // Only the route-model binding of the material itself: no product or composition is read.
        expect(queriesMatching($queries, '^select \* from "products"'))->toBeEmpty()
            ->and(queriesMatching($queries, '^select \* from "product_components"'))->toBeEmpty()
            ->and(Material::query()->count())->toBe(12);
    });
});
