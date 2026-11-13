<?php

use App\Models\Colour;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\StockLedgerVerifier;
use Illuminate\Support\Arr;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Restock several colours in one receipt, on the server (INV-21-l, ADR-0029 section 8)
|--------------------------------------------------------------------------
|
| Denim 12oz kept per colour, cost per colour: Indigo 250 m at 8.5000, Black 40 m at 9.2000. A receipt books one `restock` entry for each
| colour that received a quantity (and a `price_change` where its cost changed), all in one entry group. Colours list in the order of
| Settings > Colours, where Black comes before Indigo.
*/

beforeEach(function () {
    $this->travelTo('2026-11-12 12:00:00');
    $this->user = User::factory()->create();
    $this->ids = Colour::query()->whereIn('code', ['IND', 'BLK'])->pluck('id', 'code')->all();
});

/** Denim 12oz saved through the form: one stock of 250 m at 8.5000, or Indigo 250 m at 8.5000 and Black 40 m at 9.2000 with cost per colour. */
function restockColoursDenim(User $user, array $ids, bool $perColour): Material
{
    $payload = validMaterialPayload(['sku' => 'FAB-DEN-012', 'name' => 'Denim 12oz']);

    actingAs($user)->post(route('materials.store'), $perColour
        ? [
            ...Arr::except($payload, ['quantity_on_hand', 'unit_cost']),
            'stock_mode' => 'per_colour',
            'cost_mode' => 'per_colour',
            'colours' => [
                ['colour_id' => $ids['IND'], 'quantity_on_hand' => '250', 'unit_cost' => '8.5'],
                ['colour_id' => $ids['BLK'], 'quantity_on_hand' => '40', 'unit_cost' => '9.2'],
            ],
        ]
        : $payload)->assertSessionHasNoErrors();

    return Material::query()->where('sku', 'FAB-DEN-012')->firstOrFail();
}

/** What a material holds per colour: code => [quantity, unit cost]. */
function restockColoursRows(Material $material): array
{
    return $material->colours()->with('colour')->get()
        ->mapWithKeys(fn ($row): array => [$row->colour->code => [$row->quantity_on_hand, $row->unit_cost]])->all();
}

it('books each colour that received a quantity in one group, changes only those colours\' costs, and words the toast', function () {
    $denim = restockColoursDenim($this->user, $this->ids, true);
    $opening = StockMovement::query()->where('material_id', $denim->id)->count();
    $codes = array_flip($this->ids);

    actingAs($this->user)
        ->post(route('materials.restocks.store', $denim), [
            'received_on' => '2026-11-12',
            'reference' => 'PO-77',
            'update_unit_cost' => '1',
            'lines' => [
                $this->ids['IND'] => ['quantity' => '100', 'unit_price' => '8.8'],
                $this->ids['BLK'] => ['quantity' => '20', 'unit_price' => '9.2'],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Restocked Denim 12oz: Black +20 m, Indigo +100 m.']);

    $denim->refresh();
    $entries = StockMovement::query()->where('material_id', $denim->id)->where('type', '!=', 'created')->get();
    $entry = fn (string $code, string $type): StockMovement => $entries->first(fn (StockMovement $m): bool => $codes[$m->colour_id] === $code && $m->type->value === $type);

    expect(restockColoursRows($denim))->toBe(['BLK' => ['60.0000', '9.2000'], 'IND' => ['350.0000', '8.8000']])
        ->and($denim->quantity_on_hand)->toBe('410.0000')
        ->and($denim->unit_cost)->toBe('8.8000')
        ->and($entries->count())->toBe(3)
        ->and($entries->map(fn (StockMovement $m): string => $codes[$m->colour_id].':'.$m->type->value)->sort()->values()->all())->toBe(['BLK:restock', 'IND:price_change', 'IND:restock'])
        ->and($entries->pluck('entry_group')->unique()->all())->toHaveCount(1)->and($entries->first()->entry_group)->not->toBeNull()
        ->and($entry('IND', 'restock')->only(['quantity', 'quantity_after', 'unit_price', 'reference', 'user_id']))->toBe([
            'quantity' => '100.0000', 'quantity_after' => '350.0000', 'unit_price' => '8.8000', 'reference' => 'PO-77', 'user_id' => $this->user->id,
        ])
        ->and($entry('IND', 'price_change')->only(['unit_cost_before', 'unit_cost_after']))->toBe(['unit_cost_before' => '8.5000', 'unit_cost_after' => '8.8000'])
        ->and($entries->pluck('occurred_on')->map->toDateString()->unique()->all())->toBe(['2026-11-12'])
        ->and(app(StockLedgerVerifier::class)->verify()['drifts'])->toBe([]);

    // One colour only: the other line is left empty, so it is not in the receipt.
    actingAs($this->user)
        ->post(route('materials.restocks.store', $denim), [
            'received_on' => '2026-11-12',
            'update_unit_cost' => '0',
            'lines' => [
                $this->ids['IND'] => ['quantity' => '100.0000', 'unit_price' => '9'],
                $this->ids['BLK'] => ['quantity' => '', 'unit_price' => ''],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Restocked Denim 12oz: Indigo +100 m.']);

    expect(restockColoursRows($denim->refresh()))->toBe(['BLK' => ['60.0000', '9.2000'], 'IND' => ['450.0000', '8.8000']])
        ->and(StockMovement::query()->where('material_id', $denim->id)->count())->toBe($opening + 3 + 1);
});

it('refuses a receipt that is not complete, with the message under the colour or the lines, and saves nothing', function (array $lines, string $key, string $message) {
    $denim = restockColoursDenim($this->user, $this->ids, true);
    $movements = StockMovement::query()->count();

    $lines = collect($lines)->mapWithKeys(fn (array $line, string $code): array => [$this->ids[$code] ?? (int) $code => $line])->all();

    actingAs($this->user)
        ->post(route('materials.restocks.store', $denim), ['received_on' => '2026-11-12', 'update_unit_cost' => '1', 'lines' => $lines])
        ->assertSessionHasErrors([str_replace(['IND', 'BLK'], [$this->ids['IND'], $this->ids['BLK']], $key) => $message]);

    expect(restockColoursRows($denim->refresh()))->toBe(['BLK' => ['40.0000', '9.2000'], 'IND' => ['250.0000', '8.5000']])
        ->and($denim->quantity_on_hand)->toBe('290.0000')
        ->and(StockMovement::query()->count())->toBe($movements);
})->with([
    'no quantity at all' => [
        ['IND' => ['quantity' => '', 'unit_price' => ''], 'BLK' => ['quantity' => '', 'unit_price' => '']],
        'lines', 'Enter a quantity for at least one colour.',
    ],
    'a quantity of 0' => [
        ['IND' => ['quantity' => '0', 'unit_price' => '8.8'], 'BLK' => ['quantity' => '', 'unit_price' => '']],
        'lines.IND.quantity', 'Indigo: Enter a number above 0, with up to 4 decimals.',
    ],
    'a quantity with 5 decimals' => [
        ['IND' => ['quantity' => '1.00001', 'unit_price' => '8.8'], 'BLK' => ['quantity' => '', 'unit_price' => '']],
        'lines.IND.quantity', 'Indigo: Enter a number above 0, with up to 4 decimals.',
    ],
    'a quantity that would put the stock over the maximum' => [
        ['IND' => ['quantity' => '9999999999.9999', 'unit_price' => '8.8'], 'BLK' => ['quantity' => '', 'unit_price' => '']],
        'lines.IND.quantity', 'Indigo: The stock would exceed 9,999,999,999.9999.',
    ],
    'a colour received without its price' => [
        ['IND' => ['quantity' => '100', 'unit_price' => '8.8'], 'BLK' => ['quantity' => '20', 'unit_price' => '']],
        'lines.BLK.unit_price', 'Black: Enter the price paid.',
    ],
    'a colour of the material missing' => [
        ['IND' => ['quantity' => '100', 'unit_price' => '8.8']],
        'lines', 'The colours of Denim 12oz have changed. Enter the quantities again.',
    ],
    'a colour the material does not have' => [
        ['IND' => ['quantity' => '100', 'unit_price' => '8.8'], 'BLK' => ['quantity' => '', 'unit_price' => ''], '999999' => ['quantity' => '5', 'unit_price' => '1']],
        'lines', 'The colours of Denim 12oz have changed. Enter the quantities again.',
    ],
]);

it('keeps a one-stock restock as it was: the same entries and toast, written through the stock writer', function () {
    $denim = restockColoursDenim($this->user, $this->ids, false);

    actingAs($this->user)
        ->post(route('materials.restocks.store', $denim), ['quantity' => '100', 'unit_price' => '8.8', 'received_on' => '2026-11-12', 'reference' => 'PO-78', 'update_unit_cost' => '1'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Restocked Denim 12oz: +100 m.']);

    $entries = StockMovement::query()->where('material_id', $denim->id)->where('type', '!=', 'created')->orderBy('id')->get();

    expect($denim->refresh()->quantity_on_hand)->toBe('350.0000')
        ->and($denim->unit_cost)->toBe('8.8000')
        ->and($entries->pluck('type')->map->value->all())->toBe(['restock', 'price_change'])
        ->and($entries[0]->only(['quantity', 'quantity_after', 'unit_price', 'reference', 'colour_id', 'entry_group']))->toBe([
            'quantity' => '100.0000', 'quantity_after' => '350.0000', 'unit_price' => '8.8000', 'reference' => 'PO-78', 'colour_id' => null, 'entry_group' => null,
        ])
        ->and($entries[1]->only(['unit_cost_before', 'unit_cost_after', 'quantity_after', 'colour_id']))->toBe([
            'unit_cost_before' => '8.5000', 'unit_cost_after' => '8.8000', 'quantity_after' => '350.0000', 'colour_id' => null,
        ])
        ->and(app(StockLedgerVerifier::class)->verify()['drifts'])->toBe([]);
});
