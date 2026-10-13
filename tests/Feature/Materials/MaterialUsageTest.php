<?php

use App\Models\Material;
use App\Models\Product;
use App\Services\Costing\MaterialUsageService;
use App\Services\Reference\ReferenceData;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| "What you can make with it" (S-07-BE)
|--------------------------------------------------------------------------
|
| The usage of a material, from the real compositions through MaterialUsageService (the maths is the domain's, see "Material usage
| and next limit" in the costing skill). The demo seed is the worked example; the edge cases build their own small catalogue.
| Time frozen at 2026-10-08 when the demo is seeded.
*/

/** A material reference as the contract sends it. */
function usageMaterialRef(string $sku): array
{
    $material = demoMaterial($sku);

    return ['id' => $material->id, 'sku' => $sku, 'name' => $material->name, 'unit' => $material->unit->code];
}

/** The product of a usage row: a product reference with its status. `$number` is the plan's number of the demo product. */
function usageProduct(int $number, string $sku, string $name, string $status, ?string $photo = null): array
{
    return [
        'id' => pid($number),
        'sku' => $sku,
        'name' => $name,
        'photo_url' => $photo === null ? null : Storage::disk('public')->url($photo),
        'status' => $status,
        'status_label' => ucfirst($status),
    ];
}

/** A product made of `[material, quantity]` lines (no waste). */
function usageMake(string $name, array $lines): Product
{
    return Product::factory()->withComponents(array_map(
        static fn (array $line): array => ['material' => $line[0], 'quantity' => $line[1]],
        $lines,
    ))->create(['name' => $name]);
}

/**
 * What the service says about a material, reduced to what the edge cases compare:
 * `[product name, units covered, max producible, is bottleneck, next limit as [material name, units] or null]`.
 *
 * @return list<array{string, int, int, bool, array{string, int}|null}>
 */
function usageSummary(Material $material): array
{
    $rows = app(MaterialUsageService::class)->for($material, app(ReferenceData::class)->units());

    return array_map(static fn ($row): array => [
        $row->product->name,
        $row->unitsCovered,
        $row->maxProducible,
        $row->isBottleneck,
        $row->nextLimit === null ? null : [$row->nextLimit['material']->name, $row->nextLimit['max_producible']],
    ], $rows);
}

it('lists the Denim Jacket as the button\'s bottleneck row, and as a plain row on the denim', function () {
    actingAs(prepareDemo($this));
    demoProduct('PRD-JKT-001')->forceFill(['photo_path' => 'products/jacket.jpg'])->save();

    $button = $this->get(route('materials.show', demoMaterial('MAT-BTN-017')));

    expect(pageProp($button, 'usage'))->toBe([
        [
            'product' => usageProduct(1, 'PRD-JKT-001', 'Denim Jacket', 'active', photo: 'products/jacket.jpg'),
            'effective_quantity' => '8.0000',
            'units_covered' => 112,
            'max_producible' => 112,
            'bottleneck' => usageMaterialRef('MAT-BTN-017'),
            'is_bottleneck' => true,
            'next_limit' => ['material' => usageMaterialRef('MAT-DEN-012'), 'max_producible' => 126],
        ],
        [
            'product' => usageProduct(6, 'PRD-JKT-006', 'Canvas Chore Jacket', 'draft'),
            'effective_quantity' => '5.0000',
            'units_covered' => 180,
            'max_producible' => 129,
            'bottleneck' => usageMaterialRef('MAT-CAN-010'),
            'is_bottleneck' => false,
            'next_limit' => null,
        ],
        [
            'product' => usageProduct(5, 'PRD-JNS-005', 'Denim Jeans 5-pocket', 'active'),
            'effective_quantity' => '1.0000',
            'units_covered' => 900,
            'max_producible' => 105,
            'bottleneck' => usageMaterialRef('MAT-THR-PES'),
            'is_bottleneck' => false,
            'next_limit' => null,
        ],
    ]);

    // The same jacket seen from the denim: 250 m at 1.98 m (waste included) is 126 units, but the button limits it to 112.
    $denim = $this->get(route('materials.show', demoMaterial('MAT-DEN-012')));
    $jacket = collect(pageProp($denim, 'usage'))->firstWhere('product.id', pid(1));

    expect($jacket)->toBe([
        'product' => usageProduct(1, 'PRD-JKT-001', 'Denim Jacket', 'active', photo: 'products/jacket.jpg'),
        'effective_quantity' => '1.9800',
        'units_covered' => 126,
        'max_producible' => 112,
        'bottleneck' => usageMaterialRef('MAT-BTN-017'),
        'is_bottleneck' => false,
        'next_limit' => null,
    ]);
});

it('handles the edge cases of a composition', function (Closure $arrange, array $expected) {
    $button = Material::factory()->create(['name' => 'Aaa button', 'quantity_on_hand' => '100.0000']);
    $thread = Material::factory()->create(['name' => 'Zzz thread', 'quantity_on_hand' => '100.0000']);

    $arrange($button, $thread);

    expect(usageSummary($button))->toBe($expected);
})->with([
    // Aaa button: 100 / 4 = 25 units.
    'the only material of the composition has no next limit' => [
        function (Material $button) {
            usageMake('Solo', [[$button, '4.0000']]);
        },
        [['Solo', 25, 25, true, null]],
    ],
    // No button in stock: the product can't be made, and the thread is what it would be limited by next (100 / 10).
    'no stock at all: capacity 0, still the bottleneck' => [
        function (Material $button, Material $thread) {
            $button->update(['quantity_on_hand' => '0.0000']);
            usageMake('Empty', [[$button, '1.0000'], [$thread, '10.0000']]);
        },
        [['Empty', 0, 0, true, ['Zzz thread', 10]]],
    ],
    // Both cover 10 units: the lower name (Aaa button) is the bottleneck and the next limit is the same 10.
    'a tie at the bottleneck: the next limit equals the current capacity' => [
        function (Material $button, Material $thread) {
            usageMake('Tied', [[$button, '10.0000'], [$thread, '10.0000']]);
        },
        [['Tied', 10, 10, true, ['Zzz thread', 10]]],
    ],
    'a soft-deleted product is excluded' => [
        function (Material $button) {
            usageMake('Gone', [[$button, '1.0000']])->delete();
            usageMake('Kept', [[$button, '2.0000']]);
        },
        [['Kept', 50, 50, true, null]],
    ],
    'a product that does not use the material is excluded' => [
        function (Material $button, Material $thread) {
            usageMake('Other', [[$thread, '1.0000']]);
            usageMake('Mine', [[$button, '5.0000'], [$thread, '1.0000']]);
        },
        [['Mine', 20, 20, true, ['Zzz thread', 100]]],
    ],
]);

it('lists the bottleneck rows first, then the products by name and id', function () {
    actingAs(prepareDemo($this));

    // The thread limits only the Jeans: they come first, the other five by name.
    $thread = $this->get(route('materials.show', demoMaterial('MAT-THR-PES')));

    expect(pageProp($thread, 'usage.*.product.name'))->toBe([
        'Denim Jeans 5-pocket', 'Canvas Chore Jacket', 'Canvas Low Sneaker', 'Classic White T-shirt', 'Denim Jacket', 'Leather Tote',
    ])->and(pageProp($thread, 'usage.*.is_bottleneck'))->toBe([true, false, false, false, false, false])
        ->and(pageProp($thread, 'usage.0.next_limit'))->toBe(['material' => usageMaterialRef('MAT-DEN-012'), 'max_producible' => 165]);

    // With 7 buttons the button limits all three of its products: every row is a bottleneck row, by name.
    demoMaterial('MAT-BTN-017')->update(['quantity_on_hand' => '7']);
    $button = demoMaterial('MAT-BTN-017');

    // Two products with the same name come in id order, after the bottleneck rows (the 7 buttons cover 7 of these, so they are bottleneck rows too).
    $first = usageMake('Zzz twin', [[$button, '1.0000']]);
    $second = usageMake('zzz TWIN', [[$button, '1.0000']]);

    $response = $this->get(route('materials.show', $button));

    expect(pageProp($response, 'usage.*.product.id'))->toBe([pid(6), pid(1), pid(5), $first->id, $second->id])
        ->and(pageProp($response, 'usage.*.is_bottleneck'))->toBe([true, true, true, true, true]);
});

it('reads only the products that use the material, in a constant number of queries', function () {
    $material = Material::factory()->create();
    $units = app(ReferenceData::class)->units();

    // Products that don't use the material: never read.
    Product::factory()->count(30)->withComponents(2)->create();

    $hydrated = 0;
    Product::retrieved(function () use (&$hydrated): void {
        $hydrated++;
    });

    $run = function (int $users) use ($material, $units, &$hydrated): array {
        foreach (range(1, $users) as $i) {
            usageMake("Product $i", [[$material, '1.0000'], [Material::factory()->create(), '2.0000']]);
        }

        $hydrated = 0;

        return queryLog(fn () => app(MaterialUsageService::class)->for($material, $units));
    };

    $few = $run(3);
    $hydratedFew = $hydrated;
    $many = $run(27);

    // Products, their lines, the materials of the lines (the units come from the shared reference read). A product is hydrated
    // only when it uses the material: 3 of 33, then 30 of 60.
    expect($few)->toHaveCount(3)
        ->and($many)->toHaveCount(3)
        ->and($hydratedFew)->toBe(3)
        ->and($hydrated)->toBe(30)
        ->and(queriesMatching($many, '^select \* from "products"'))->toHaveCount(1);
});
