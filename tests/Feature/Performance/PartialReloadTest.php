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

/** The inventory-summary scan of InventorySummary (every material, only the columns the stock maths reads). */
const SUMMARY_SCAN = 'select\s+"id",\s*"quantity_on_hand",\s*"unit_cost",\s*"reorder_level"\s+from\s+"materials"';

/** The "needs restock" list of the dashboard (out of stock first). */
const NEEDS_RESTOCK_QUERY = 'case when quantity_on_hand';

beforeEach(function () {
    $this->user = preparePreview($this);
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
            ->and($queries)->toHaveCount(4);
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
    it('does not load the materials picker when it is not requested', function () {
        $firstVisit = $this->get(route('products.create'))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['statuses'],
            fn (Assert $partial) => $partial->missing('materials')->missing('categories')->has('statuses'),
        );

        // No query: the status list is a free enum list and the picker's materials are not read.
        expect($queries)->toBeEmpty();
    });

    it('sends the picker on a full visit and when it is requested', function () {
        $firstVisit = $this->get(route('products.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('materials', 12));

        $queries = partialReloadQueries($firstVisit, ['materials'], fn (Assert $partial) => $partial->has('materials', 12));

        // One read of the materials plus their units (eager-loaded, never the categories).
        expect(queriesMatching($queries, 'from "materials"'))->toHaveCount(1)
            ->and($queries)->toHaveCount(2);
    });

    it('reads the materials once for the picker and the costing of the product on the edit page', function () {
        $firstVisit = $this->get(route('products.edit', 1))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['materials', 'product'],
            fn (Assert $partial) => $partial->has('materials', 12)->has('product.components'),
        );

        // One read of the materials (however many of the two props are sent), their units (eager-loaded) and the category names
        // of the product.
        expect(queriesMatching($queries, 'from "materials"'))->toHaveCount(1)
            ->and($queries)->toHaveCount(3);
    });

    it('loads nothing on the edit page when only the status list is requested', function () {
        $firstVisit = $this->get(route('products.edit', 1))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['statuses'],
            fn (Assert $partial) => $partial->missingAll(['materials', 'product', 'categories']),
        );

        // No query: neither the materials nor the product are loaded.
        expect($queries)->toBeEmpty();
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

        expect($queries)->toBeEmpty();
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
    it('runs the restock list on a full visit', function () {
        $queries = queryLog(fn () => $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->hasAll(['period', 'periods', 'stats', 'top_products', 'needs_restock', 'recent_sales', 'alerts'])));

        expect(queriesMatching($queries, NEEDS_RESTOCK_QUERY))->toHaveCount(1);
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
            // The stats still need the inventory summary and the costed products (and their units), once each.
            ->and(queriesMatching($queries, SUMMARY_SCAN))->toHaveCount(1)
            ->and($queries)->toHaveCount(3);
    });

    it('builds the costed products once when several sections need them', function () {
        $queries = queryLog(fn () => $this->get(route('dashboard'))->assertOk());

        expect(queriesMatching($queries, '"sku" in'))->toHaveCount(1);
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
        $material = previewMaterial('MAT-BTN-017');
        $firstVisit = $this->get(route('materials.show', $material))->assertOk();

        $queries = partialReloadQueries(
            $firstVisit,
            ['material'],
            fn (Assert $partial) => $partial->missingAll(['usage', 'history'])->where('material.id', $material->id),
        );

        // Only the route-model binding of the material itself.
        expect(queriesMatching($queries, '"sku" in'))->toBeEmpty()
            ->and(Material::query()->count())->toBe(12);
    });
});
