<?php

use App\Enums\MaterialCostMode;
use App\Enums\StockMovementType;
use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Inventory\StockLedgerVerifier;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Arr;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Saving a material that keeps its stock per colour (INV-21-f, ADR-0029)
|--------------------------------------------------------------------------
|
| Denim 12oz per colour with cost per colour: Indigo 250 m at 8.5000, Black 40 m at 9.2000 (total 290, lowest cost 8.5000).
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->ids = Colour::query()->whereIn('code', ['IND', 'BLK'])->pluck('id', 'code')->all();
});

/**
 * A Store/UpdateMaterialRequest payload for a per-colour Denim 12oz with cost per colour (the total and the cost are the colours').
 *
 * @param  list<array{string, string, string}>  $rows  [colour code, quantity, unit cost]
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function perColourMaterialPayload(array $ids, array $rows, array $overrides = []): array
{
    return [
        ...Arr::except(validMaterialPayload(['sku' => 'FAB-DEN-012', 'name' => 'Denim 12oz', 'color' => 'Antique indigo']), ['quantity_on_hand', 'unit_cost']),
        'stock_mode' => 'per_colour',
        'cost_mode' => 'per_colour',
        'colours' => array_map(fn (array $row): array => ['colour_id' => $ids[$row[0]], 'quantity_on_hand' => $row[1], 'unit_cost' => $row[2]], $rows),
        ...$overrides,
    ];
}

it('adds a material with a stock and a cost per colour: its colours, its total and one created entry for each', function () {
    $response = actingAs($this->user)->post(route('materials.store'), perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['BLK', '40', '9.2']]));

    $denim = Material::query()->where('sku', 'FAB-DEN-012')->firstOrFail();

    $response->assertRedirect(route('materials.show', $denim))->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material created.']);

    expect($denim->stock_mode->value)->toBe('per_colour')
        ->and($denim->cost_mode)->toBe(MaterialCostMode::PerColour)
        ->and($denim->quantity_on_hand)->toBe('290.0000')
        ->and($denim->unit_cost)->toBe('8.5000')
        ->and($denim->color)->toBe('Antique indigo')
        ->and(MaterialColour::query()->where('material_id', $denim->id)->pluck('unit_cost', 'colour_id')->all())
        ->toEqualCanonicalizing([$this->ids['IND'] => '8.5000', $this->ids['BLK'] => '9.2000']);

    $entries = StockMovement::query()->where('material_id', $denim->id)->orderBy('id')->get();

    expect($entries->pluck('type')->unique()->all())->toBe([StockMovementType::Created])
        ->and($entries->pluck('colour_id')->all())->toBe([$this->ids['IND'], $this->ids['BLK']])
        ->and($entries->pluck('quantity')->all())->toBe(['250.0000', '40.0000'])
        ->and($entries->pluck('unit_price')->all())->toBe(['8.5000', '9.2000'])
        ->and($entries->pluck('entry_group')->unique())->toHaveCount(1)
        ->and(app(StockLedgerVerifier::class)->verify()['drifts'])->toBe([]);

    actingAs($this->user)->get(route('materials.show', $denim))->assertInertia(fn ($page) => $page
        ->where('material.stock_value', '2493.0000')
        ->where('material.colours.1.colour.code', 'IND')
        ->where('material.colours.1.stock_value', '2125.0000')
        ->where('material.colours.1.removable', false)
        ->where('material.colours.1.remove_reason', "Indigo has 250 m in stock, so it can't be removed."));
});

it('edits a material per colour: a changed quantity is one correction and a changed cost one price change, for that colour only', function () {
    actingAs($this->user)->post(route('materials.store'), perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['BLK', '40', '9.2']]));
    $denim = Material::query()->where('sku', 'FAB-DEN-012')->firstOrFail();
    $opening = StockMovement::query()->where('material_id', $denim->id)->count();

    // A hidden free-text colour that arrives empty is not written, and quantities typed as "250" over "250.0000" are no change.
    $response = actingAs($this->user)->put(route('materials.update', $denim), perColourMaterialPayload(
        $this->ids,
        [['IND', '250', '8.5'], ['BLK', '35', '9.5']],
        ['color' => null],
    ));

    $response->assertRedirect(route('materials.show', $denim))->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material updated.']);

    $new = StockMovement::query()->where('material_id', $denim->id)->orderBy('id')->get()->slice($opening)->values();

    expect($new)->toHaveCount(2)
        ->and($new[0])->type->toBe(StockMovementType::Correction)->colour_id->toBe($this->ids['BLK'])
        ->quantity_before->toBe('40.0000')->quantity_after->toBe('35.0000')
        ->and($new[1])->type->toBe(StockMovementType::PriceChange)->colour_id->toBe($this->ids['BLK'])
        ->unit_cost_before->toBe('9.2000')->unit_cost_after->toBe('9.5000')
        ->and($new->pluck('entry_group')->unique())->toHaveCount(1);

    $denim->refresh();

    expect($denim->quantity_on_hand)->toBe('285.0000')
        ->and($denim->unit_cost)->toBe('8.5000')
        ->and($denim->color)->toBe('Antique indigo')
        ->and(app(StockLedgerVerifier::class)->verify()['drifts'])->toBe([]);
});

it('refuses a per-colour material that is not valid, with the card\'s messages, and saves nothing', function (string $case) {
    $foreign = CurrentWorkshop::run(Workshop::factory()->create(), fn (): int => Colour::query()->where('code', 'IND')->value('id'));
    $good = [['IND', '250', '8.5'], ['BLK', '40', '9.2']];

    // Colours list in the order of Settings > Colours (Black before Indigo). Each case: the changed payload and the errors it must give (a null message only checks the key).
    $cases = [
        'a quantity that is not a number' => [perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['BLK', '4O', '9.2']]), ['colours.1.quantity_on_hand' => 'Black: Enter a number from 0, with up to 4 decimals.']],
        'a quantity of five decimals' => [perColourMaterialPayload($this->ids, [['IND', '250.00001', '8.5'], ['BLK', '40', '9.2']]), ['colours.0.quantity_on_hand' => 'Indigo: Enter a number from 0, with up to 4 decimals.']],
        'a negative quantity' => [perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['BLK', '-1', '9.2']]), ['colours.1.quantity_on_hand' => 'Black: Enter a number from 0, with up to 4 decimals.']],
        'a missing cost with cost per colour' => [perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['BLK', '40', '']]), ['colours.1.unit_cost' => 'Black: Enter a unit cost.']],
        'a negative cost' => [perColourMaterialPayload($this->ids, [['IND', '250', '-8.5'], ['BLK', '40', '9.2']]), ['colours.0.unit_cost' => 'Indigo: Enter a number from 0, with up to 4 decimals.']],
        'no colour' => [perColourMaterialPayload($this->ids, $good, ['colours' => []]), ['colours' => 'Add at least one colour, or choose One stock for all colours.']],
        'a colour of another workshop' => [perColourMaterialPayload($this->ids, $good, ['colours' => [['colour_id' => $foreign, 'quantity_on_hand' => '1', 'unit_cost' => '1']]]), ['colours.0.colour_id' => null]],
        'a stock over the maximum' => [perColourMaterialPayload($this->ids, [['IND', '9999999999.9999', '8.5'], ['BLK', '1', '9.2']]), ['colours' => 'The stock would exceed 9,999,999,999.9999.']],
        'a colour twice' => [perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['IND', '40', '9.2']]), ['colours.1.colour_id' => null]],
        'cost per colour with one stock' => [validMaterialPayload(['stock_mode' => 'single', 'cost_mode' => 'per_colour']), ['cost_mode' => null]],
    ];

    [$payload, $errors] = $cases[$case];

    actingAs($this->user)->post(route('materials.store'), $payload)->assertSessionHasErrors(array_keys($errors));

    foreach ($errors as $key => $message) {
        if ($message !== null) {
            expect(session('errors')->get($key)[0])->toBe($message);
        }
    }

    expect(Material::query()->count())->toBe(0);
})->with([
    'a quantity that is not a number', 'a quantity of five decimals', 'a negative quantity', 'a missing cost with cost per colour', 'a negative cost',
    'no colour', 'a colour of another workshop', 'a stock over the maximum', 'a colour twice', 'cost per colour with one stock',
]);

it('refuses colour quantities over the maximum stock on Edit, under the table, and saves nothing', function (bool $perColourBefore) {
    $material = $perColourBefore
        ? Material::factory()->perColour([['IND', '250.0000', '8.5000'], ['BLK', '40.0000', '9.2000']], MaterialCostMode::PerColour)->create(['sku' => 'FAB-DEN-012', 'unit' => 'm'])
        : Material::factory()->create(['sku' => 'FAB-DEN-012', 'unit' => 'm', 'quantity_on_hand' => '10.0000']);

    actingAs($this->user)
        ->put(route('materials.update', $material), perColourMaterialPayload($this->ids, [['IND', '9999999999.9999', '8.5'], ['BLK', '1', '9.2']]))
        ->assertSessionHasErrors(['colours' => 'The stock would exceed 9,999,999,999.9999.']);

    expect($material->refresh()->stock_mode->value)->toBe($perColourBefore ? 'per_colour' : 'single')
        ->and(MaterialColour::query()->where('material_id', $material->id)->count())->toBe($perColourBefore ? 2 : 0);
})->with(['a per-colour material' => [true], 'a switch to per colour' => [false]]);

it('lets a colour go only at 0 with no history, and says why otherwise', function (string $case) {
    if ($case === 'Black at 0 with no history') {
        // Built without the form: no ledger entry names Black.
        $material = Material::factory()->perColour([['IND', '250.0000', '8.5000'], ['BLK', '0.0000', '9.2000']], MaterialCostMode::PerColour)->create(['sku' => 'FAB-DEN-012', 'unit' => 'm']);
    } else {
        // Saved through the form: its opening entries name Indigo and Black, even Black at 0.
        actingAs($this->user)->post(route('materials.store'), perColourMaterialPayload($this->ids, [['IND', '250', '8.5'], ['BLK', '0', '9.2']]));
        $material = Material::query()->where('sku', 'FAB-DEN-012')->firstOrFail();
    }

    // The save leaves out Indigo in the Indigo case, Black in the others.
    $kept = $case === 'Indigo with 250 m' ? ['BLK', '0', '9.2'] : ['IND', '250', '8.5'];

    $response = actingAs($this->user)->put(route('materials.update', $material), perColourMaterialPayload($this->ids, [$kept]));

    match ($case) {
        'Black at 0 with no history' => $response->assertSessionHasNoErrors()->assertRedirect(route('materials.show', $material)),
        'Indigo with 250 m' => $response->assertSessionHasErrors(['colours' => "Indigo has 250 m in stock, so it can't be removed."]),
        'Black with history' => $response->assertSessionHasErrors(['colours' => "Black has history, so it can't be removed."]),
    };

    expect(MaterialColour::query()->where('material_id', $material->id)->count())->toBe($case === 'Black at 0 with no history' ? 1 : 2);
})->with(['Black at 0 with no history', 'Indigo with 250 m', 'Black with history']);
