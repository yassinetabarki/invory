<?php

use App\Enums\StockStatus;
use App\Models\Material;

/*
|--------------------------------------------------------------------------
| The status of a material kept per colour (INV-21-b, ADR-0029 section 8)
|--------------------------------------------------------------------------
|
| Out when every colour is at 0; low when the reorder level is set and some colour is at or under it (a colour at 0 is); else in stock.
| The "needs restock" scope (SQL) agrees with the model for every row.
*/

it('reads the status of a per-colour material from its colours, and the restock scope agrees', function (array $quantities, ?string $reorderLevel, StockStatus $status) {
    $material = Material::factory()
        ->perColour(array_map(fn (string $code, string $quantity): array => [$code, $quantity], array_keys($quantities), $quantities))
        ->create(['reorder_level' => $reorderLevel]);

    expect(Material::query()->with('colours')->findOrFail($material->id)->stockStatus())->toBe($status)
        ->and(Material::query()->needsRestock()->whereKey($material->id)->exists())->toBe($status !== StockStatus::InStock)
        // The status never lazy-loads the colours.
        ->and(fn () => Material::query()->findOrFail($material->id)->stockStatus())->toThrow(LogicException::class, 'load `colours`');
})->with([
    'every colour above the reorder level' => [['IND' => '250.0000', 'BLK' => '150.0000'], '100.0000', StockStatus::InStock],
    'one colour at the reorder level' => [['IND' => '250.0000', 'BLK' => '100.0000'], '100.0000', StockStatus::Low],
    'one colour at 0' => [['IND' => '250.0000', 'BLK' => '0.0000'], '100.0000', StockStatus::Low],
    'every colour at 0' => [['IND' => '0.0000', 'BLK' => '0.0000'], '100.0000', StockStatus::Out],
    'no reorder level, one colour at 0: Low needs a reorder level' => [['IND' => '250.0000', 'BLK' => '0.0000'], null, StockStatus::InStock],
    'no reorder level, every colour at 0' => [['IND' => '0.0000', 'BLK' => '0.0000'], null, StockStatus::Out],
]);
