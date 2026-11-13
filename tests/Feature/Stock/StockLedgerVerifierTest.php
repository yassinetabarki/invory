<?php

use App\Enums\StockMovementType;
use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\StockMovement;
use App\Services\Inventory\StockLedgerVerifier;

/*
|--------------------------------------------------------------------------
| The ledger counts cutting and cutting_return (S-53-a, ADR-0014 section 6)
|--------------------------------------------------------------------------
|
| Denim 12oz Indigo: opens at 250 m, 30 jackets are cut (59.4 m taken), 5 of them go back to To start (9.9 m returned): 200.5 m.
*/

/** The three ledger entries of the denim, by name. */
function denimLedger(Material $material): array
{
    return [
        'created' => StockMovement::factory()->for($material)->created('250.0000')->create(['occurred_on' => '2026-10-01']),
        'cutting' => StockMovement::factory()->for($material)->create([
            'type' => StockMovementType::Cutting, 'occurred_on' => '2026-10-16',
            'quantity' => '59.4000', 'quantity_before' => '250.0000', 'quantity_after' => '190.6000', 'unit_price' => null,
        ]),
        'return' => StockMovement::factory()->for($material)->create([
            'type' => StockMovementType::CuttingReturn, 'occurred_on' => '2026-10-16',
            'quantity' => '9.9000', 'quantity_before' => '190.6000', 'quantity_after' => '200.5000', 'unit_price' => null,
        ]),
    ];
}

it('balances a material with a cutting and a cutting return', function () {
    $material = Material::factory()->create(['quantity_on_hand' => '200.5000']);
    denimLedger($material);

    $this->artisan('invory:stock:verify')
        ->expectsOutputToContain('Checked 1 materials: every balance matches its ledger.')
        ->assertExitCode(0);
});

it('reports drift when the cutting or the return is missing from the ledger', function (string $missing, string $ledger, string $drift) {
    $material = Material::factory()->create(['sku' => 'MAT-DEN-012', 'name' => 'Denim 12oz Indigo', 'quantity_on_hand' => '200.5000']);
    denimLedger($material)[$missing]->delete();

    $this->artisan('invory:stock:verify')
        ->expectsTable(['ID', 'SKU', 'Name', 'Stored', 'Ledger', 'Drift'], [[$material->id, 'MAT-DEN-012', 'Denim 12oz Indigo', '200.5000', $ledger, $drift]])
        ->expectsOutputToContain('1 of 1 materials drift from their ledger.')
        ->assertExitCode(1);
})->with([
    'the cutting is missing' => ['cutting', '259.9000', '-59.4000'],
    'the return is missing' => ['return', '190.6000', '9.9000'],
]);

it('checks a per-colour material colour by colour: a row off by 0.0001 drifts, balanced entries do not (INV-21-b)', function () {
    // Denim 12oz per colour: Indigo 250 and Black 40, each opened by its own `created` entry naming the colour.
    $denim = Material::factory()->perColour([['IND', '250.0000'], ['BLK', '40.0000']])->create(['sku' => 'MAT-DEN-012', 'name' => 'Denim 12oz']);
    $colours = Colour::query()->whereIn('code', ['IND', 'BLK'])->get()->keyBy('code');

    foreach (['IND' => '250.0000', 'BLK' => '40.0000'] as $code => $quantity) {
        StockMovement::factory()->for($denim)->created($quantity)->create(['colour_id' => $colours[$code]->id]);
    }

    $verifier = app(StockLedgerVerifier::class);

    expect($verifier->verify())->toBe(['checked' => 1, 'drifts' => []]);

    MaterialColour::query()->where('material_id', $denim->id)->where('colour_id', $colours['IND']->id)->update(['quantity_on_hand' => '250.0001']);

    expect($verifier->verify()['drifts'])->toBe([[
        'material_id' => $denim->id, 'sku' => 'MAT-DEN-012', 'name' => 'Denim 12oz · Indigo',
        'stored' => '250.0001', 'ledger' => '250.0000', 'drift' => '0.0001', 'colour_id' => $colours['IND']->id,
    ]]);
});
