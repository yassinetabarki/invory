<?php

use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Inventory\StockLedgerVerifier;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

/*
|--------------------------------------------------------------------------
| Switching how a material keeps its stock and cost (INV-21-g, ADR-0029)
|--------------------------------------------------------------------------
|
| Denim 12oz: one stock of 250 m at 8.5000, then Indigo 250 m + Black 40 m, and back. Colours list in the order of Settings > Colours,
| where Black comes before Indigo.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->ids = Colour::query()->whereIn('code', ['IND', 'BLK'])->pluck('id', 'code')->all();
});

/**
 * A Store/UpdateMaterialRequest payload for Denim 12oz in the given modes. A one-stock payload carries its quantity and cost in `$overrides`.
 *
 * @param  list<array{string, string, 2?: string}>  $rows  [colour code ('' = none chosen), quantity, unit cost]
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stockSwitchPayload(array $ids, string $stockMode, string $costMode, array $rows = [], array $overrides = []): array
{
    return [
        ...Arr::except(validMaterialPayload(['sku' => 'FAB-DEN-012', 'name' => 'Denim 12oz']), ['quantity_on_hand', 'unit_cost']),
        'stock_mode' => $stockMode,
        'cost_mode' => $costMode,
        'colours' => array_map(fn (array $row): array => ['colour_id' => $row[0] === '' ? '' : $ids[$row[0]], 'quantity_on_hand' => $row[1], 'unit_cost' => $row[2] ?? ''], $rows),
        ...$overrides,
    ];
}

it('switches the stock and cost settings: the entries of the switch, the rows, the cost, and a balanced ledger', function (string $case) {
    $ids = $this->ids;
    $codes = array_flip($ids);
    $drifts = fn (): array => app(StockLedgerVerifier::class)->verify()['drifts'];

    // The material as it is before the switch, saved through the form so that its ledger opens with its stock.
    $start = match ($case) {
        'split', 'split without a colour' => stockSwitchPayload($ids, 'single', 'same', [], ['quantity_on_hand' => '250', 'unit_cost' => '8.5']),
        'cost on' => stockSwitchPayload($ids, 'per_colour', 'same', [['IND', '250'], ['BLK', '40']], ['unit_cost' => '8.5']),
        default => stockSwitchPayload($ids, 'per_colour', 'per_colour', [['IND', '250', '8.5'], ['BLK', '40', '9.2']]),
    };
    actingAs($this->user)->post(route('materials.store'), $start)->assertSessionHasNoErrors();

    $denim = Material::query()->where('sku', 'FAB-DEN-012')->firstOrFail();
    $opening = StockMovement::query()->where('material_id', $denim->id)->count();

    $switch = match ($case) {
        'split' => stockSwitchPayload($ids, 'per_colour', 'same', [['IND', '250'], ['BLK', '40']], ['unit_cost' => '8.5']),
        'split without a colour' => stockSwitchPayload($ids, 'per_colour', 'same', [['', '250'], ['BLK', '40']], ['unit_cost' => '8.5']),
        'join' => stockSwitchPayload($ids, 'single', 'same', [], ['unit_cost' => '8.5', 'quantity_on_hand' => '999']),
        'cost on' => stockSwitchPayload($ids, 'per_colour', 'per_colour', [['IND', '250', '8.5'], ['BLK', '40', '9.2']]),
        'cost off' => stockSwitchPayload($ids, 'per_colour', 'same', [['IND', '250'], ['BLK', '40']], ['unit_cost' => '8.5']),
    };
    $response = actingAs($this->user)->patch(route('materials.update', $denim), $switch);

    if ($case === 'split without a colour') {
        $response->assertSessionHasErrors(['colours.0.colour_id' => 'Choose the colour that holds the 250 m you have now.']);

        $denim->refresh();

        expect($denim->stock_mode->value)->toBe('single')
            ->and($denim->quantity_on_hand)->toBe('250.0000')
            ->and(StockMovement::query()->where('material_id', $denim->id)->count())->toBe($opening)
            ->and(MaterialColour::query()->where('material_id', $denim->id)->count())->toBe(0);

        return;
    }

    $response->assertSessionHasNoErrors()->assertRedirect(route('materials.show', $denim));

    $denim->refresh();
    $new = StockMovement::query()->where('material_id', $denim->id)->orderBy('id')->get()->slice($opening)->values();
    $seen = $new->map(fn (StockMovement $entry): array => [$entry->type->value, $entry->colour_id === null ? null : $codes[$entry->colour_id]])->all();
    $rows = MaterialColour::query()->where('material_id', $denim->id)->pluck('unit_cost', 'colour_id')->all();

    if ($case === 'split') {
        // One group: the stock going to 0 and Indigo coming from 0; Black, new at 40, is a correction of its own.
        expect($seen)->toBe([['colour_split', null], ['colour_split', 'IND'], ['correction', 'BLK']])
            ->and($new[0])->quantity->toBe('250.0000')->quantity_before->toBe('250.0000')->quantity_after->toBe('0.0000')
            ->and($new[1])->quantity->toBe('250.0000')->quantity_before->toBe('0.0000')->quantity_after->toBe('250.0000')
            ->and($new[1]->entry_group)->toBe($new[0]->entry_group)
            ->and($new[2])->quantity_before->toBe('0.0000')->quantity_after->toBe('40.0000')
            ->and($new[2]->entry_group)->not->toBe($new[0]->entry_group)
            ->and($denim->stock_mode->value)->toBe('per_colour')
            ->and($denim->cost_mode->value)->toBe('same')
            ->and($denim)->quantity_on_hand->toBe('290.0000')->unit_cost->toBe('8.5000')
            ->and($rows)->toEqualCanonicalizing([$ids['IND'] => null, $ids['BLK'] => null]);

        // The add and edit pages hand the Colours table the workshop's colours, in Settings > Colours order.
        actingAs($this->user)->get(route('materials.edit', $denim))->assertInertia(fn (Assert $page) => $page
            ->has('colours', 20)->has('colours.0', fn (Assert $colour) => $colour->where('code', 'WHT')->hasAll(['id', 'name', 'swatch'])));
        actingAs($this->user)->get(route('materials.create'))->assertInertia(fn (Assert $page) => $page->has('colours', 20));
    } elseif ($case === 'join') {
        // Black's cost drops to the kept 8.5000; then every colour goes to 0 and the one stock comes from 0 to 290, in one group.
        expect($seen)->toBe([['price_change', 'BLK'], ['colour_join', 'BLK'], ['colour_join', 'IND'], ['colour_join', null]])
            ->and($new[0])->unit_cost_before->toBe('9.2000')->unit_cost_after->toBe('8.5000')
            ->and($new[1])->quantity->toBe('40.0000')->quantity_after->toBe('0.0000')
            ->and($new[3])->quantity->toBe('290.0000')->quantity_before->toBe('0.0000')->quantity_after->toBe('290.0000')
            ->and($new->pluck('entry_group')->unique())->toHaveCount(1)
            ->and($denim->stock_mode->value)->toBe('single')
            ->and($denim->cost_mode->value)->toBe('same')
            ->and($denim)->quantity_on_hand->toBe('290.0000')->unit_cost->toBe('8.5000')
            ->and($denim->stockValue())->toBe('2465.0000')
            ->and($rows)->toBe([])
            ->and(StockMovement::query()->where('material_id', $denim->id)->whereNotNull('colour_id')->count())->toBe(5);
    } elseif ($case === 'cost on') {
        // Each colour starts at 8.5000; only Black differs.
        expect($seen)->toBe([['price_change', 'BLK']])
            ->and($new[0])->unit_cost_before->toBe('8.5000')->unit_cost_after->toBe('9.2000')
            ->and($denim->cost_mode->value)->toBe('per_colour')
            ->and($denim->unit_cost)->toBe('8.5000')
            ->and($rows)->toEqualCanonicalizing([$ids['IND'] => '8.5000', $ids['BLK'] => '9.2000']);
    } else {
        // Indigo's 8.5000 is kept for every colour.
        expect($seen)->toBe([['price_change', 'BLK']])
            ->and($new[0])->unit_cost_before->toBe('9.2000')->unit_cost_after->toBe('8.5000')
            ->and($denim->cost_mode->value)->toBe('same')
            ->and($denim->unit_cost)->toBe('8.5000')
            ->and($rows)->toEqualCanonicalizing([$ids['IND'] => null, $ids['BLK'] => null])
            ->and($denim->stockValue())->toBe('2465.0000');
    }

    expect($drifts())->toBe([]);
})->with(['split', 'split without a colour', 'join', 'cost on', 'cost off']);

it('works out the total of a switch back from the typed quantities, and refuses bad input', function () {
    $query = fn (array $overrides = []): array => ['unit' => 'm', 'colours' => [$this->ids['IND'] => '250', $this->ids['BLK'] => '40'], ...$overrides];

    getJson(route('materials.stock-total', $query()))->assertUnauthorized();

    actingAs($this->user)->getJson(route('materials.stock-total', $query()))->assertOk()->assertExactJson([
        'total' => '290.0000',
        'sentence' => 'Black 40 m + Indigo 250 m become one stock of 290 m.',
    ]);
    actingAs($this->user)->getJson(route('materials.stock-total', $query(['colours' => [$this->ids['IND'] => '12.50']])))->assertOk()->assertExactJson([
        'total' => '12.5000',
        'sentence' => 'Indigo 12.5 m becomes one stock of 12.5 m.',
    ]);

    // Thousands are separated with a comma, like the screens: 1,250 m and a total of 1,290 m.
    actingAs($this->user)->getJson(route('materials.stock-total', $query(['colours' => [$this->ids['IND'] => '1250', $this->ids['BLK'] => '40']])))->assertOk()->assertExactJson([
        'total' => '1290.0000',
        'sentence' => 'Black 40 m + Indigo 1,250 m become one stock of 1,290 m.',
    ]);

    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn (): int => Colour::query()->where('code', 'IND')->value('id'));

    foreach ([
        'colours' => [$query(['colours' => [$this->ids['IND'] => '4O']]), 'colours.'.$this->ids['IND']],
        'five decimals' => [$query(['colours' => [$this->ids['IND'] => '1.00001']]), 'colours.'.$this->ids['IND']],
        'negative' => [$query(['colours' => [$this->ids['IND'] => '-1']]), 'colours.'.$this->ids['IND']],
        'no colours' => [$query(['colours' => []]), 'colours'],
        'unknown unit' => [$query(['unit' => 'bananas']), 'unit'],
        'colour of another workshop' => [$query(['colours' => [$foreign => '1']]), 'colours'],
    ] as [$input, $field]) {
        actingAs($this->user)->getJson(route('materials.stock-total', $input))->assertUnprocessable()->assertJsonValidationErrors($field);
    }
});
