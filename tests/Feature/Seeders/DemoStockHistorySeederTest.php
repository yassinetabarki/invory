<?php

use App\Actions\Materials\AppendStockMovement;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\StockLedgerVerifier;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoStockHistorySeeder;

/*
|--------------------------------------------------------------------------
| The demo stock history (S-08-c, AC-6 and AC-7)
|--------------------------------------------------------------------------
|
| Every demo material gets a "Created" entry, and the button, the label and the denim a restock and a price change too, all
| written through AppendStockMovement and credited to the test user. The ledger then balances.
*/

/** The ledger of a demo material in entry order, as the plain strings a screen would show. */
function demoLedger(string $sku): array
{
    return StockMovement::query()->where('material_id', demoMaterial($sku)->id)->orderBy('id')->get()
        ->map(fn (StockMovement $movement): array => array_filter([
            'type' => $movement->type->value,
            'on' => $movement->occurred_on->toDateString(),
            'quantity' => $movement->quantity,
            'quantity_before' => $movement->quantity_before,
            'quantity_after' => $movement->quantity_after,
            'unit_price' => $movement->unit_price,
            'unit_cost_before' => $movement->unit_cost_before,
            'unit_cost_after' => $movement->unit_cost_after,
            'reference' => $movement->reference,
        ], fn (?string $value): bool => $value !== null))
        ->all();
}

it('gives every demo material its history, and three of them a restock and a price change, so the ledger balances', function () {
    $this->travelTo('2026-10-14 12:00:00');
    $testUser = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
    $this->seed(DemoCatalogSeeder::class);

    $this->seed(DemoStockHistorySeeder::class);

    $types = StockMovement::query()->get()->countBy(fn (StockMovement $movement): string => $movement->type->value)->all();
    $actors = StockMovement::query()->distinct()->pluck('user_id')->all();

    expect($types)->toBe(['created' => 12, 'restock' => 3, 'price_change' => 3])
        ->and(StockMovement::query()->count())->toBe(18)
        ->and($actors)->toBe([$testUser->id])
        ->and(demoLedger('MAT-BTN-017'))->toBe([
            ['type' => 'created', 'on' => '2026-09-07', 'quantity' => '400.0000', 'quantity_after' => '400.0000', 'unit_price' => '0.3200'],
            ['type' => 'restock', 'on' => '2026-10-08', 'quantity' => '500.0000', 'quantity_after' => '900.0000', 'unit_price' => '0.3500', 'reference' => 'PO-2291'],
            ['type' => 'price_change', 'on' => '2026-10-08', 'quantity_after' => '900.0000', 'unit_cost_before' => '0.3200', 'unit_cost_after' => '0.3500'],
        ])
        ->and(array_map(fn (array $entry): array => [$entry['type'], $entry['on']], demoLedger('MAT-LBL-WOV')))->toBe([
            ['created', '2026-09-07'], ['restock', '2026-09-30'], ['price_change', '2026-09-30'],
        ])
        ->and(array_map(fn (array $entry): array => [$entry['type'], $entry['on']], demoLedger('MAT-DEN-012')))->toBe([
            ['created', '2026-09-07'], ['restock', '2026-09-26'], ['price_change', '2026-09-26'],
        ])
        ->and(app(StockLedgerVerifier::class)->verify()['drifts'])->toBe([]);
});

it('writes nothing without the test user, and a second run never repeats it or touches a real history', function () {
    $this->seed(DemoCatalogSeeder::class);

    $this->seed(DemoStockHistorySeeder::class);

    expect(StockMovement::query()->count())->toBe(0);

    $testUser = User::factory()->create(['email' => 'test@example.com']);
    $button = demoMaterial('MAT-BTN-017');
    app(AppendStockMovement::class)->handle($button, StockMovementType::Created, '2026-01-02', $testUser, [
        'quantity' => '900', 'quantity_after' => '900', 'unit_price' => '0.3500',
    ]);

    $this->seed(DemoStockHistorySeeder::class);
    $this->seed(DemoStockHistorySeeder::class);

    $perSku = Material::query()->withCount('stockMovements')->pluck('stock_movements_count', 'sku')->all();

    // The button keeps only its own row; the denim and the label have their three entries once, the other 9 their "Created".
    expect($perSku['MAT-BTN-017'])->toBe(1)
        ->and($perSku['MAT-DEN-012'])->toBe(3)
        ->and($perSku['MAT-LBL-WOV'])->toBe(3)
        ->and(array_sum($perSku))->toBe(1 + 3 + 3 + 9);
});
