<?php

use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\DemoHistory;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The stock history props: `history` (show) and the deferred `recent_history` (index) (S-16-BE)
|--------------------------------------------------------------------------
|
| Real ledger rows through StockMovementResource, newest first (occurred_on, then id). The 12 demo materials exist and DemoHistory
| gives them the history the Phase 2 fixture had, built through the AppendStockMovement Action. Time is frozen at 2026-10-08.
| Ids are not fixed (a PostgreSQL sequence never goes back), so tests compare them with the ids the ledger really has.
*/

beforeEach(function () {
    $this->user = preparePreview($this);
    $this->admin = DemoHistory::seed(); // "Admin" did every entry
});

/** The keys of the `StockMovement` type in resources/js/types/domain.ts. */
const STOCK_MOVEMENT_KEYS = [
    'id', 'material_id', 'type', 'type_label', 'occurred_on', 'quantity', 'quantity_before', 'quantity_after',
    'unit_price', 'unit_cost_before', 'unit_cost_after', 'reference', 'note', 'user_name',
];

/** The ids of a material's entries, newest first (every demo entry is dated in order, so that is the reverse of the entry order). */
function newestFirstIds(string $sku): array
{
    return array_reverse(DemoHistory::idsOf(previewMaterial($sku)));
}

describe('show: history', function () {
    it('sends the history of the button, newest first, with every value', function () {
        $button = previewMaterial('MAT-BTN-017');
        [$priceChange, $restock, $correction, $created] = newestFirstIds('MAT-BTN-017');

        $response = actingAs($this->user)->get(route('materials.show', $button))->assertOk();

        expect(pageProp($response, 'history'))->toBe([
            [
                'id' => $priceChange,
                'material_id' => $button->id,
                'type' => 'price_change',
                'type_label' => 'Price change',
                'occurred_on' => '2026-10-02',
                'quantity' => null,
                'quantity_before' => null,
                // The ledger stores the balance at that moment; the contract sends null for a price change.
                'quantity_after' => null,
                'unit_price' => null,
                'unit_cost_before' => '0.3200',
                'unit_cost_after' => '0.3500',
                'reference' => null,
                'note' => null,
                'user_name' => 'Admin',
            ],
            [
                'id' => $restock,
                'material_id' => $button->id,
                'type' => 'restock',
                'type_label' => 'Restock',
                'occurred_on' => '2026-10-02',
                'quantity' => '500.0000',
                'quantity_before' => null,
                'quantity_after' => '900.0000',
                'unit_price' => '0.3500',
                'unit_cost_before' => null,
                'unit_cost_after' => null,
                'reference' => 'PO-2291',
                'note' => null,
                'user_name' => 'Admin',
            ],
            [
                'id' => $correction,
                'material_id' => $button->id,
                'type' => 'correction',
                'type_label' => 'Correction',
                'occurred_on' => '2026-09-14',
                'quantity' => null,
                'quantity_before' => '600.0000',
                'quantity_after' => '400.0000',
                'unit_price' => null,
                'unit_cost_before' => null,
                'unit_cost_after' => null,
                'reference' => null,
                'note' => 'Stock count',
                'user_name' => 'Admin',
            ],
            [
                'id' => $created,
                'material_id' => $button->id,
                'type' => 'created',
                'type_label' => 'Created',
                'occurred_on' => '2026-09-01',
                'quantity' => '600.0000',
                'quantity_before' => null,
                'quantity_after' => '600.0000',
                'unit_price' => '0.3200',
                'unit_cost_before' => null,
                'unit_cost_after' => null,
                'reference' => null,
                'note' => null,
                'user_name' => 'Admin',
            ],
        ]);
    });

    it('sends exactly the keys of the StockMovement contract', function () {
        actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')))
            ->assertInertia(fn (Assert $page) => $page->has('history', 4, fn (Assert $movement) => $movement->hasAll(STOCK_MOVEMENT_KEYS)));
    });

    it('stores the stock after a price change but sends null for it', function () {
        $priceChange = StockMovement::query()->where('material_id', previewMaterial('MAT-BTN-017')->id)->where('type', 'price_change')->firstOrFail();

        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-BTN-017')));

        expect($priceChange->quantity_after)->toBe('900.0000')
            ->and(pageProp($response, 'history.0.quantity_after'))->toBeNull();
    });

    it('lists a same-day restock and price change with the later entry first (higher id)', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-LBL-WOV')));
        [$priceChange, $restock, $created] = newestFirstIds('MAT-LBL-WOV');

        expect(pageProp($response, 'history.*.id'))->toBe([$priceChange, $restock, $created])
            ->and(pageProp($response, 'history.*.type'))->toBe(['price_change', 'restock', 'created'])
            ->and(pageProp($response, 'history.*.occurred_on'))->toBe(['2026-09-24', '2026-09-24', '2026-09-01'])
            ->and(pageProp($response, 'history.0'))->toMatchArray(['unit_cost_before' => '0.1000', 'unit_cost_after' => '0.1200'])
            ->and(pageProp($response, 'history.1'))->toMatchArray(['quantity' => '150.0000', 'quantity_after' => '400.0000', 'unit_price' => '0.1200', 'reference' => null]);
    });

    it('orders by date first, whatever the order the entries were written in', function () {
        $material = Material::factory()->create();
        $late = StockMovement::factory()->for($material)->restock('1.0000', '1.0000')->create(['occurred_on' => '2026-10-05']);
        $early = StockMovement::factory()->for($material)->restock('1.0000', '2.0000')->create(['occurred_on' => '2026-10-01']); // written later, dated earlier
        $sameDay = StockMovement::factory()->for($material)->restock('1.0000', '3.0000')->create(['occurred_on' => '2026-10-05']);

        $response = actingAs($this->user)->get(route('materials.show', $material));

        expect(pageProp($response, 'history.*.id'))->toBe([$sameDay->id, $late->id, $early->id]);
    });

    it('has only the opening entry for a material that was never touched', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-LIN-VIS')));

        expect(pageProp($response, 'history'))->toHaveCount(1)
            ->and(pageProp($response, 'history.0'))->toMatchArray([
                'type' => 'created',
                'quantity' => '180.0000',
                'quantity_after' => '180.0000',
                'unit_price' => '3.1000',
                'occurred_on' => '2026-09-01',
            ]);
    });

    it('sends every material\'s history by its entry counts', function (string $sku, int $entries, array $types) {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial($sku)));

        expect(pageProp($response, 'history'))->toHaveCount($entries)
            ->and(pageProp($response, 'history.*.type'))->toBe($types);
    })->with([
        'rivet' => ['MAT-RVT-009', 2, ['restock', 'created']],
        'canvas' => ['MAT-CAN-010', 2, ['restock', 'created']],
        'jersey' => ['MAT-JRS-180', 2, ['correction', 'created']],
        'denim' => ['MAT-DEN-012', 2, ['restock', 'created']],
        'button' => ['MAT-BTN-017', 4, ['price_change', 'restock', 'correction', 'created']],
        'zipper' => ['MAT-ZIP-018', 2, ['restock', 'created']],
        'nappa' => ['MAT-LTH-NAP', 2, ['restock', 'created']],
        'thread' => ['MAT-THR-PES', 2, ['restock', 'created']],
        'cupsole' => ['MAT-SOL-042', 2, ['correction', 'created']],
        'lining' => ['MAT-LIN-VIS', 1, ['created']],
        'laces' => ['MAT-LAC-120', 2, ['restock', 'created']],
        'label' => ['MAT-LBL-WOV', 3, ['price_change', 'restock', 'created']],
    ]);

    it('sends a correction with the quantities before and after, never a unit price', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-SOL-042')));

        expect(pageProp($response, 'history.0'))->toMatchArray([
            'type' => 'correction',
            'quantity_before' => '60.0000',
            'quantity_after' => '0.0000',
            'unit_price' => null,
            'note' => 'Used in production',
            'occurred_on' => '2026-09-30',
        ]);
    });

    it('sends only the history of the material shown', function () {
        $response = actingAs($this->user)->get(route('materials.show', previewMaterial('MAT-DEN-012')));

        expect(array_unique(pageProp($response, 'history.*.material_id')))->toBe([previewMaterial('MAT-DEN-012')->id]);
    });

    it('sends an empty list for a material with no history', function () {
        $material = Material::factory()->create();

        actingAs($this->user)->get(route('materials.show', $material))
            ->assertInertia(fn (Assert $page) => $page->where('history', [])->has('history', 0));
    });

    it('names who did an entry, and sends null once that user is gone', function () {
        $button = previewMaterial('MAT-BTN-017');
        $other = User::factory()->create(['name' => 'Amel']);
        StockMovement::factory()->for($button)->restock('1.0000', '901.0000')->create(['user_id' => $other->id, 'occurred_on' => '2026-10-08']);

        expect(pageProp(actingAs($this->user)->get(route('materials.show', $button)), 'history.0.user_name'))->toBe('Amel');

        $other->delete(); // user_id is nulled, the entry stays

        expect(pageProp(actingAs($this->user)->get(route('materials.show', $button)), 'history.0.user_name'))->toBeNull();
    });

    it('stays within the query budget, flat in the number of entries', function () {
        $button = previewMaterial('MAT-BTN-017');
        $url = route('materials.show', $button);
        actingAs($this->user)->get($url)->assertOk(); // warm up

        $few = queryLog(fn () => $this->get($url)->assertOk());

        StockMovement::factory()->count(40)->for($button)->restock('1.0000', '1.0000')->create();

        $many = queryLog(fn () => $this->get($url)->assertOk());

        expect(count($few))->toBeLessThanOrEqual(10, implode(PHP_EOL, $few))
            ->and(count($many))->toBe(count($few), implode(PHP_EOL, $many))
            ->and(queriesMatching($many, 'from "stock_movements"'))->toHaveCount(1);
    });
});

describe('index: recent_history', function () {
    it('is not part of the first response', function () {
        actingAs($this->user)->get(route('materials.index'))
            ->assertInertia(fn (Assert $page) => $page->missing('recent_history')->has('materials.data', 12));
    });

    it('loads the newest movements of every material on the page with the StockMovement keys', function () {
        actingAs($this->user)->get(route('materials.index'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                $reloaded->has('recent_history', 26, fn (Assert $movement) => $movement->hasAll(STOCK_MOVEMENT_KEYS));
            }));
    });

    it('keeps the order of the materials and puts the newest entry of each first', function () {
        // The materials by name: rivet, canvas, jersey, denim, button, zipper, nappa, thread, cupsole, lining, laces, label.
        $skus = ['MAT-RVT-009', 'MAT-CAN-010', 'MAT-JRS-180', 'MAT-DEN-012', 'MAT-BTN-017', 'MAT-ZIP-018', 'MAT-LTH-NAP', 'MAT-THR-PES', 'MAT-SOL-042', 'MAT-LIN-VIS', 'MAT-LAC-120', 'MAT-LBL-WOV'];

        actingAs($this->user)->get(route('materials.index'))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) use ($skus) {
                $props = $reloaded->toArray()['props'];

                expect(array_column($props['recent_history'], 'id'))->toBe(array_merge(...array_map(newestFirstIds(...), $skus)))
                    ->and(array_column($props['recent_history'], 'material_id'))->toBe(array_merge(...array_map(
                        fn (string $sku): array => array_fill(0, count(DemoHistory::entries()[$sku]), previewMaterial($sku)->id),
                        $skus,
                    )));
            }));
    });

    it('sends at most the newest 5 movements of each material, in one query', function () {
        $busy = Material::factory()->create(['sku' => 'ZZZ-BUSY', 'name' => 'Zzz busy']);
        $quiet = Material::factory()->create(['sku' => 'ZZZ-QUIET', 'name' => 'Zzz quiet']);

        // Eight entries, written oldest first on distinct days, plus two on the newest day (the later id is newer).
        foreach (range(1, 8) as $day) {
            StockMovement::factory()->for($busy)->restock('1.0000', "{$day}.0000")->create(['occurred_on' => sprintf('2026-09-%02d', $day)]);
        }
        $sameDay = StockMovement::factory()->for($busy)->restock('1.0000', '9.0000')->create(['occurred_on' => '2026-09-08']);
        $quietOne = StockMovement::factory()->for($quiet)->created()->create(['occurred_on' => '2026-01-01']);

        $firstVisit = actingAs($this->user)->get(route('materials.index', ['search' => 'zzz']))->assertOk();
        $props = null;

        $queries = queryLog(function () use ($firstVisit, &$props) {
            $firstVisit->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) use (&$props) {
                $props = $reloaded->toArray()['props']['recent_history'];
            }));
        });

        $busyRows = array_values(array_filter($props, fn (array $m): bool => $m['material_id'] === $busy->id));
        $quietRows = array_values(array_filter($props, fn (array $m): bool => $m['material_id'] === $quiet->id));

        expect($busyRows)->toHaveCount(5)
            // Newest first: the same-day entry written last, then the 8th, 7th, 6th and 5th of September.
            ->and(array_column($busyRows, 'occurred_on'))->toBe(['2026-09-08', '2026-09-08', '2026-09-07', '2026-09-06', '2026-09-05'])
            ->and($busyRows[0]['id'])->toBe($sameDay->id)
            ->and(array_column($quietRows, 'id'))->toBe([$quietOne->id])
            // The material of the page that comes first in the list (by name) has its rows first.
            ->and($props[0]['material_id'])->toBe($busy->id)
            ->and(queriesMatching($queries, 'stock_movements'))->toHaveCount(1);
    });

    it('only covers the materials of the filtered page', function () {
        actingAs($this->user)->get(route('materials.index', ['category' => 'thread']))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                expect(array_column($reloaded->toArray()['props']['recent_history'], 'id'))->toBe(newestFirstIds('MAT-THR-PES'));
            }));
    });

    it('is empty for a page of materials with no history, without querying the ledger for it', function () {
        Material::factory()->create(['sku' => 'ZZZ-NEW', 'name' => 'Zzz new']);

        actingAs($this->user)->get(route('materials.index', ['search' => 'zzz new']))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps(function (Assert $reloaded) {
                expect($reloaded->toArray()['props']['recent_history'])->toBe([]);
            }));
    });

    it('sends nothing, and runs no ledger query, for an empty page', function () {
        $firstVisit = actingAs($this->user)->get(route('materials.index', ['search' => 'no such material']))->assertOk();

        $queries = partialReloadQueries($firstVisit, ['recent_history'], fn (Assert $partial) => $partial->where('recent_history', []));

        expect(queriesMatching($queries, 'stock_movements'))->toBeEmpty();
    });

    it('stays within the query budget, flat in the number of materials and entries', function () {
        $firstVisit = actingAs($this->user)->get(route('materials.index'))->assertOk();
        $few = partialReloadQueries($firstVisit, ['recent_history'], fn (Assert $partial) => $partial->has('recent_history'));

        Material::factory()->count(12)->create()->each(
            fn (Material $material) => StockMovement::factory()->count(7)->for($material)->restock('1.0000', '1.0000')->create(),
        );
        $firstVisit = actingAs($this->user)->get(route('materials.index'))->assertOk();
        $many = partialReloadQueries($firstVisit, ['recent_history'], fn (Assert $partial) => $partial->has('recent_history'));

        expect(count($few))->toBeLessThanOrEqual(10, implode(PHP_EOL, $few))
            ->and(count($many))->toBe(count($few))
            ->and(queriesMatching($many, 'stock_movements'))->toHaveCount(1);
    });
});

it('gives the demo materials a ledger that balances with their seeded stock', function () {
    $this->artisan('invory:stock:verify')->assertExitCode(0);
});
