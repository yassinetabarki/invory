<?php

use App\Enums\ProductionBatchStatus;
use App\Models\ProductionBatch;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The Progress page's data (INV-5-f, AC-3 and AC-4)
|--------------------------------------------------------------------------
|
| `GET progress` with the card's seven batches on 30 Oct 2026: 26 units, In cutting 7 · Sewing 15 · Finishing 4. The shelf: Denim
| Jacket Indigo 2 · 0 · 3 · 1, Light wash nothing; Denim Jeans 5-pocket Indigo 0 · 5 · 2 · 0.
*/

beforeEach(function () {
    $example = progressExample($this);
    $this->user = $example['user'];
    $this->jacket = $example['jacket'];
    $this->jeans = $example['jeans'];
    $this->sewing = $example['sewing'];
    $this->finishing = $example['finishing'];
    $this->batches = $example['batches'];
});

function progressPage(object $test, array $query = []): TestResponse
{
    return actingAs($test->user)->get(route('progress.index', $query))->assertOk();
}

/** The page's props with the deferred figures loaded. */
function progressProps(object $test, array $query = []): array
{
    return loadedPageProps(progressPage($test, $query));
}

/** A card's rows as [colour, size, ...by phase, in production, shelf]. */
function cardRows(array $card): array
{
    return array_map(fn (array $row): array => [$row['colour']['name'], $row['size']['label'] ?? null, ...$row['by_phase'], $row['in_production'], $row['on_shelf']], $card['rows']);
}

it('shows the path, the tiles and a card per product, A → Z (AC-3)', function () {
    $page = progressProps($this);

    expect(data_get($page, 'path'))->toBe([
        ['phase' => 'cutting', 'name' => 'In cutting'],
        ['phase' => $this->sewing->id, 'name' => 'Sewing'],
        ['phase' => $this->finishing->id, 'name' => 'Finishing'],
    ])
        ->and(array_column(data_get($page, 'tiles'), 'units', 'name'))->toBe(['In cutting' => 7, 'Sewing' => 15, 'Finishing' => 4])
        ->and(data_get($page, 'all_units'))->toBe(26)
        ->and(data_get($page, 'total_units'))->toBe(26)
        ->and(data_get($page, 'has_batches'))->toBeTrue()
        ->and(data_get($page, 'phase_view'))->toBeNull()
        ->and(data_get($page, 'products.data.*.product.name'))->toBe(['Denim Jacket', 'Denim Jeans 5-pocket'])
        ->and(data_get($page, 'products.data.0.units'))->toBe(21)
        ->and(data_get($page, 'products.data.0.batches'))->toBe(6)
        ->and(data_get($page, 'products.data.1.units'))->toBe(5)
        ->and(data_get($page, 'products.data.1.batches'))->toBe(1);

    expect(cardRows(data_get($page, 'products.data.0')))->toBe([
        ['Indigo', null, 4, 10, 4, 18, null],
        ['Indigo', 'S', 0, 4, 0, 4, 2],
        ['Indigo', 'M', 4, 6, 4, 14, 0],
        ['Light wash', null, 3, 0, 0, 3, null],
        ['Light wash', 'L', 2, 0, 0, 2, 0],
        ['Light wash', 'XL', 1, 0, 0, 1, 0],
    ])->and(data_get($page, 'products.data.0.total'))->toBe(['by_phase' => [7, 10, 4], 'in_production' => 21]);
});

it('filters by colour and product, keeping the totals for "Showing Indigo · 23 of 26 units" (AC-3)', function () {
    $indigo = progressProps($this, ['colour' => demoColour('IND')->id]);

    expect(array_column(data_get($indigo, 'tiles'), 'units'))->toBe([4, 15, 4])
        ->and(data_get($indigo, 'all_units'))->toBe(23)
        ->and(data_get($indigo, 'total_units'))->toBe(26)
        ->and(data_get($indigo, 'products.data.0.units'))->toBe(18)
        ->and(array_column(cardRows(data_get($indigo, 'products.data.0')), 0))->toBe(['Indigo', 'Indigo', 'Indigo']);

    $jeans = progressProps($this, ['product' => $this->jeans->id]);
    expect(data_get($jeans, 'products.data.*.product.name'))->toBe(['Denim Jeans 5-pocket'])
        ->and(data_get($jeans, 'product_filter.name'))->toBe('Denim Jeans 5-pocket');

    $none = progressProps($this, ['colour' => demoColour('BLK')->id]);
    expect(data_get($none, 'products.data'))->toBe([])
        ->and(data_get($none, 'has_batches'))->toBeTrue();
});

it('shows one phase: its heading and its batches, oldest first, with the days since (AC-4)', function () {
    $page = progressProps($this, ['phase' => $this->sewing->id]);
    $view = data_get($page, 'phase_view');

    expect(data_get($page, 'products'))->toBeNull()
        ->and([$view['phase']['name'], $view['previous']['name'], $view['next']['name']])->toBe(['Sewing', 'In cutting', 'Finishing'])
        ->and([$view['units'], $view['batch_count'], $view['product_count'], $view['oldest_since']])->toBe([15, 3, 2, '2026-10-26'])
        ->and(array_map(fn (array $b): array => [$b['number'], $b['product']['name'], $b['colour']['name'], $b['size']['label'], $b['units'], $b['since'], $b['since_days'], $b['on_shelf']], $view['batches']['data']))
        ->toBe([
            ['B-0146', 'Denim Jeans 5-pocket', 'Indigo', 'S', 5, '2026-10-26', 4, 0],
            ['B-0147', 'Denim Jacket', 'Indigo', 'S', 4, '2026-10-27', 3, 2],
            ['B-0149', 'Denim Jacket', 'Indigo', 'M', 6, '2026-10-28', 2, 0],
        ]);

    $cutting = data_get(progressProps($this, ['phase' => 'cutting']), 'phase_view');
    expect($cutting['previous'])->toBeNull()
        ->and($cutting['batches']['data'][0]['number'])->toBe('B-0150')
        ->and($cutting['batches']['data'][0]['since'])->toBe('2026-10-25');

    expect(data_get(progressProps($this, ['phase' => $this->finishing->id]), 'phase_view.next'))->toBeNull();
});

it('reads a deleted product\'s batch, and never shows finished or undone batches or another workshop\'s', function () {
    $this->jacket->delete();
    $this->batches['B-0146']->update(['status' => ProductionBatchStatus::InStock, 'phase_id' => null]);
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => ProductionBatch::factory()->count(2)->create());

    $page = progressProps($this);
    expect(data_get($page, 'total_units'))->toBe(21)
        ->and(data_get($page, 'products.data.*.product.name'))->toBe(['Denim Jacket'])
        ->and(data_get($page, 'products.data.0.product.deleted'))->toBeTrue();

    expect(data_get(progressProps($this, ['phase' => $this->finishing->id]), 'phase_view.batches.data.0.product.deleted'))->toBeTrue();
});

it('says when nothing is in production, and answers 404 for an unknown phase', function () {
    ProductionBatch::query()->update(['status' => ProductionBatchStatus::Undone, 'phase_id' => null]);

    $page = progressProps($this);
    expect(data_get($page, 'has_batches'))->toBeFalse()
        ->and(array_column(data_get($page, 'tiles'), 'units'))->toBe([0, 0, 0])
        ->and(data_get($page, 'products.data'))->toBe([]);

    actingAs($this->user)->get(route('progress.index', ['phase' => 999999]))->assertNotFound();
});

it('keeps its queries flat for 10 cards and 25 rows', function () {
    $few = countQueries(fn () => progressProps($this));
    ProductionBatch::factory()->count(12)->create();
    $many = countQueries(fn () => progressProps($this));

    expect($many)->toBeLessThanOrEqual($few + 2);

    $few = countQueries(fn () => progressProps($this, ['phase' => 'cutting']));
    ProductionBatch::factory()->count(20)->create();
    expect(countQueries(fn () => progressProps($this, ['phase' => 'cutting'])))->toBeLessThanOrEqual($few + 2);
});

it('sends a guest to the login page', function () {
    $this->get(route('progress.index'))->assertRedirect(route('login'));
});
