<?php

use App\Models\Material;
use App\Services\Inventory\InventorySummary;
use Illuminate\Support\Facades\DB;

/**
 * The 12 materials of the UI-first preview fixture (docs/plans/phase-2-ui-first-preview.md, "Materials").
 * Inventory value 10544.3000; needs restock 4 (low: jersey, button, label; out: cupsole).
 *
 * @return list<Material>
 */
function previewFixtureMaterials(): array
{
    // sku, name, category, unit, unit cost, stock, reorder at
    $rows = [
        ['MAT-RVT-009', 'Brass rivet 9mm', 'hardware', 'pc', '0.0800', '5000', '1000'],
        ['MAT-CAN-010', 'Cotton canvas 10oz Natural', 'fabric', 'm', '5.4000', '300', '60'],
        ['MAT-JRS-180', 'Cotton jersey 180gsm White', 'fabric', 'm', '4.2000', '64', '80'],
        ['MAT-DEN-012', 'Denim 12oz Indigo', 'fabric', 'm', '8.5000', '250', '100'],
        ['MAT-BTN-017', 'Metal shank button 17mm', 'fastener', 'pc', '0.3500', '900', '1000'],
        ['MAT-ZIP-018', 'Metal zipper 18cm', 'fastener', 'pc', '0.9500', '1250', '300'],
        ['MAT-LTH-NAP', 'Nappa leather Cognac', 'leather', 'sqft', '6.8000', '540', '150'],
        ['MAT-THR-PES', 'Polyester thread', 'thread', 'm', '0.0040', '20000', '5000'],
        ['MAT-SOL-042', 'Rubber cupsole 42', 'sole', 'pair', '5.9000', '0', '40'],
        ['MAT-LIN-VIS', 'Viscose lining Black', 'lining', 'm', '3.1000', '180', '50'],
        ['MAT-LAC-120', 'Waxed cotton laces 120cm', 'trim', 'pair', '0.4500', '600', '200'],
        ['MAT-LBL-WOV', 'Woven brand label', 'label', 'pc', '0.1200', '400', '500'],
    ];

    return array_map(
        fn (array $row): Material => Material::factory()->create([
            'sku' => $row[0],
            'name' => $row[1],
            'category' => $row[2],
            'unit' => $row[3],
            'unit_cost' => $row[4],
            'quantity_on_hand' => $row[5],
            'reorder_level' => $row[6],
        ]),
        $rows,
    );
}

it('returns zeros and "0.0000" for an empty table', function () {
    expect(app(InventorySummary::class)->summarize())->toBe([
        'materials_count' => 0,
        'inventory_value' => '0.0000',
        'needs_restock_count' => 0,
        'low_stock_count' => 0,
        'out_of_stock_count' => 0,
    ]);
});

it('summarises the 12 preview fixture materials', function () {
    previewFixtureMaterials();

    expect(app(InventorySummary::class)->summarize())->toBe([
        'materials_count' => 12,
        'inventory_value' => '10544.3000',
        'needs_restock_count' => 4,
        'low_stock_count' => 3,
        'out_of_stock_count' => 1,
    ]);
});

it('returns exactly the dashboard stats keys, with the documented types', function () {
    previewFixtureMaterials();

    $totals = app(InventorySummary::class)->summarize();

    expect(array_keys($totals))->toBe([
        'materials_count', 'inventory_value', 'needs_restock_count', 'low_stock_count', 'out_of_stock_count',
    ])
        ->and($totals['materials_count'])->toBeInt()
        ->and($totals['inventory_value'])->toBeString()->toMatch('/^\d+\.\d{4}$/')
        ->and($totals['needs_restock_count'])->toBeInt()
        ->and($totals['low_stock_count'])->toBeInt()
        ->and($totals['out_of_stock_count'])->toBeInt();
});

it('excludes soft-deleted materials from every figure', function () {
    $materials = previewFixtureMaterials();

    // Delete the out-of-stock cupsole, a low-stock label and a healthy denim: 12 - 3 = 9 left.
    foreach ($materials as $material) {
        if (in_array($material->sku, ['MAT-SOL-042', 'MAT-LBL-WOV', 'MAT-DEN-012'], true)) {
            $material->delete();
        }
    }

    // 10544.3000 - 0 (cupsole) - 48.0000 (label) - 2125.0000 (denim) = 8371.3000
    expect(app(InventorySummary::class)->summarize())->toBe([
        'materials_count' => 9,
        'inventory_value' => '8371.3000',
        'needs_restock_count' => 2,
        'low_stock_count' => 2,
        'out_of_stock_count' => 0,
    ]);
});

it('counts every status exactly once', function (string $quantity, ?string $reorderLevel, array $expected) {
    Material::factory()->create(['quantity_on_hand' => $quantity, 'unit_cost' => '1', 'reorder_level' => $reorderLevel]);

    $totals = app(InventorySummary::class)->summarize();

    expect([$totals['needs_restock_count'], $totals['low_stock_count'], $totals['out_of_stock_count']])->toBe($expected);
})->with([
    'well above reorder level' => ['100', '50', [0, 0, 0]],
    'just above reorder level' => ['50.0001', '50', [0, 0, 0]],
    'equal to reorder level is low' => ['50', '50', [1, 1, 0]],
    'just below reorder level is low' => ['49.9999', '50', [1, 1, 0]],
    'zero stock is out, not low' => ['0', '50', [1, 0, 1]],
    'zero stock with zero reorder level is out, not low' => ['0', '0', [1, 0, 1]],
    'zero stock without reorder level is out' => ['0', null, [1, 0, 1]],
    'no reorder level is never low' => ['1', null, [0, 0, 0]],
]);

it('sums the scale-4 stock values the materials table shows', function () {
    // 0.0001 x 0.5 = 0.00005 per material, which each stockValue() rounds half-up to 0.0001.
    Material::factory()->count(2)->create(['quantity_on_hand' => '0.0001', 'unit_cost' => '0.5', 'reorder_level' => null]);

    expect(app(InventorySummary::class)->summarize()['inventory_value'])->toBe('0.0002');
});

it('keeps the sum exact beyond float precision', function () {
    // 9999999999.9999 x 9999999.9999 = 99999999998999000.00000001: not representable as a float (> 2^53).
    Material::factory()->create(['quantity_on_hand' => '9999999999.9999', 'unit_cost' => '9999999.9999', 'reorder_level' => null]);
    Material::factory()->create(['quantity_on_hand' => '1', 'unit_cost' => '0.0001', 'reorder_level' => null]);

    expect(app(InventorySummary::class)->summarize()['inventory_value'])->toBe('99999999998999000.0001');
});

it('reads more rows than one chunk', function () {
    Material::factory()->count(501)->create(['quantity_on_hand' => '2', 'unit_cost' => '0.5', 'reorder_level' => null]);

    $totals = app(InventorySummary::class)->summarize();

    expect($totals['materials_count'])->toBe(501)
        ->and($totals['inventory_value'])->toBe('501.0000');
});

it('runs a single query per chunk, never one per material (strict models)', function () {
    previewFixtureMaterials();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    app(InventorySummary::class)->summarize();

    // 12 materials fit in one chunk, so a single query reads them all.
    expect($queries)->toBe(1);
});

it('reads the totals in one aggregate query on PostgreSQL, however many materials', function () {
    Material::factory()->count(501)->create(['quantity_on_hand' => '2', 'unit_cost' => '0.5', 'reorder_level' => null]);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $totals = app(InventorySummary::class)->summarize();

    expect($queries)->toBe(1)
        ->and($totals['materials_count'])->toBe(501)
        ->and($totals['inventory_value'])->toBe('501.0000');
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'PostgreSQL only');
