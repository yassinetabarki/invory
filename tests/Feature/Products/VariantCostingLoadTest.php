<?php

use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\Size;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Costing every variant of every product on a page (INV-16-d, ADR-0027 risk 1)
|--------------------------------------------------------------------------
|
| Group `load`: left out of `composer test` (phpunit.xml) and run on purpose, by the tech-lead in review:
|
|     php artisan test --compact --group=load
|
| The cap is 2,000 filled cells per product. A product at the cap here has the workshop's 20 colours and 11 sizes (220 variants), 9
| by-colour-and-size lines of 220 cells and a tenth of 20, each variant with its own quantity (so no two variants share a composition:
| the worst case for "one costing per distinct composition"). Cells are bulk-inserted; everything else goes through the factories.
*/

/** The most a page that costs every product at the cap may take, on SQLite, in seconds. */
const LOAD_BUDGET_SECONDS = 1.5;

/**
 * Ten materials with plenty of stock, shared by every product of the test.
 *
 * @return list<Material>
 */
function loadMaterials(): array
{
    return Material::factory()->count(10)->create(['quantity_on_hand' => '1000000.0000'])->all();
}

/**
 * A product with the workshop's 20 colours, the sizes 36 to 46 and 2,000 cells over 10 by-colour-and-size lines.
 *
 * @param  list<Material>  $materials
 */
function productAtTheCap(array $materials): Product
{
    $product = Product::factory()->withSizes(array_map('strval', range(36, 46)))->create();
    $colourIds = Colour::query()->ordered()->pluck('id')->all();
    $sizeIds = Size::query()->whereIn('label', array_map('strval', range(36, 46)))->ordered()->pluck('id')->all();
    $product->colours()->syncWithPivotValues($colourIds, ['workshop_id' => $product->workshop_id]);

    $now = now();
    $rows = [];

    foreach ($materials as $position => $material) {
        $line = ProductComponent::factory()->byVariant()->create([
            'product_id' => $product->id, 'material_id' => $material->id, 'waste_percent' => '5.00', 'position' => $position,
        ]);
        // Nine lines of 220 cells, the tenth of 20 (the first size of every colour): 2,000.
        $sizesOfLine = $position < 9 ? $sizeIds : array_slice($sizeIds, 0, 1);

        foreach ($colourIds as $colourIndex => $colourId) {
            foreach ($sizesOfLine as $sizeIndex => $sizeId) {
                $rows[] = [
                    'workshop_id' => $product->workshop_id, 'product_component_id' => $line->id, 'product_id' => $product->id,
                    'colour_id' => $colourId, 'size_id' => $sizeId, 'quantity' => sprintf('1.%04d', $colourIndex * 11 + $sizeIndex),
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
    }

    foreach (array_chunk($rows, 1000) as $chunk) {
        ProductComponentQuantity::query()->insert($chunk);
    }

    return $product;
}

/** Seconds a request takes, measured around the whole of it. */
function secondsOf(Closure $request): float
{
    $start = microtime(true);
    $request();

    return microtime(true) - $start;
}

it('lists 25 products at 2,000 cells each within the budget', function () {
    $user = User::factory()->create();
    $materials = loadMaterials();

    foreach (range(1, 25) as $unused) {
        productAtTheCap($materials);
    }

    expect(ProductComponentQuantity::query()->count())->toBe(25 * 2000);

    actingAs($user)->get(route('products.index'))->assertOk(); // warm-up

    $seconds = secondsOf(fn () => actingAs($user)->get(route('products.index'))->assertOk());
    fwrite(STDERR, sprintf("\n[load] products list, 25 products at 2,000 cells: %.3f s\n", $seconds));

    expect($seconds)->toBeLessThan(LOAD_BUDGET_SECONDS);
})->group('load');

it('answers the dashboard with 100 products, five of them at 2,000 cells, within the budget', function () {
    $user = User::factory()->create();
    $materials = loadMaterials();

    foreach (range(1, 5) as $unused) {
        productAtTheCap($materials);
    }

    Product::factory()->count(95)->withComponents(array_map(
        fn (Material $material): array => ['material' => $material, 'quantity' => '1.5000', 'waste_percent' => '5.00'],
        array_slice($materials, 0, 5),
    ))->create();

    expect(Product::query()->count())->toBe(100);

    loadedPageProps(actingAs($user)->get(route('dashboard'))); // warm-up

    $seconds = secondsOf(fn () => loadedPageProps(actingAs($user)->get(route('dashboard'))));
    fwrite(STDERR, sprintf("\n[load] dashboard, 100 products, 5 at 2,000 cells: %.3f s (first request and both deferred groups)\n", $seconds));

    expect($seconds)->toBeLessThan(LOAD_BUDGET_SECONDS);
})->group('load');
