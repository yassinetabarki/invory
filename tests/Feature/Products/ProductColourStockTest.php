<?php

use App\Enums\MaterialCostMode;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\User;
use App\Services\Costing\ProductCostingService;
use Illuminate\Support\Facades\DB;
use Tests\Support\VariantJacket;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Products costed from each colour's stock (INV-21-e, AC-11, AC-12, AC-16, AC-18, ADR-0029)
|--------------------------------------------------------------------------
|
| The Denim Jacket of INV-16 (Indigo and Black, S · M · L · XL; labor 12, overhead 3.5, selling price 89) with ONE denim line by colour
| and size (S 1.6 · M 1.8 · L 2.0 · XL 2.2, waste 10 %) on a Denim 12oz kept per colour, Indigo 250 m at 8.5000 and Black 40 m at 9.2000.
| The maths is in tests/Unit/Domain/Costing/VariantColourStockTest; these tests check what the adapter loads and what the page sends.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

/**
 * The jacket with its two one-stock denim lines swapped for one line on a single Denim 12oz kept per colour.
 *
 * @return array{jacket: VariantJacket, denim: Material}
 */
function csjJacketOnColourDenim(): array
{
    $jacket = VariantJacket::create();
    $lineIds = ProductComponent::query()->whereIn('material_id', [$jacket->materials['indigo_denim']->id, $jacket->materials['black_denim']->id])->pluck('id');
    ProductComponentQuantity::query()->whereIn('product_component_id', $lineIds)->delete();
    ProductComponent::query()->whereIn('id', $lineIds)->delete();

    $denim = Material::factory()
        ->perColour([[$jacket->colours['Indigo'], '250.0000', '8.5000'], [$jacket->colours['Black'], '40.0000', '9.2000']], MaterialCostMode::PerColour)
        ->create(['workshop_id' => $jacket->workshop->id, 'sku' => 'MAT-CSJ-DEN', 'name' => 'Denim 12oz', 'category' => 'fabric', 'unit' => 'm', 'reorder_level' => '100.0000']);

    $line = ProductComponent::factory()->byVariant()->create([
        'product_id' => $jacket->product->id, 'material_id' => $denim->id, 'waste_percent' => '10.00', 'position' => 0, 'notes' => null,
    ]);

    csjCells($jacket, $line, ['Indigo', 'Black']);

    return ['jacket' => $jacket, 'denim' => $denim];
}

/** @param  list<string>  $colours */
function csjCells(VariantJacket $jacket, ProductComponent $line, array $colours): void
{
    foreach ($colours as $colour) {
        foreach (['S' => '1.6000', 'M' => '1.8000', 'L' => '2.0000', 'XL' => '2.2000'] as $label => $quantity) {
            ProductComponentQuantity::factory()->create([
                'product_component_id' => $line->id, 'colour_id' => $jacket->colours[$colour], 'size_id' => $jacket->sizes[$label], 'quantity' => $quantity,
            ]);
        }
    }
}

/** @return array<string, mixed> */
function csjProductProp(VariantJacket $jacket): array
{
    return loadedPageProp(actingAs(test()->user)->get(route('products.show', $jacket->product)), 'product');
}

/** The reading of one colour on a line, as `[colour name, missing, stock, min, max]`. */
function csjReadings(array $line): array
{
    return array_map(
        fn (array $reading): array => [$reading['colour']['name'], $reading['missing'], $reading['stock_on_hand'], $reading['units_possible']['min'], $reading['units_possible']['max']],
        $line['stock_by_colour'],
    );
}

it('costs the Denim Jacket from each colour of Denim 12oz and sends the colour fields on the product page (AC-11, AC-12, AC-16)', function () {
    ['jacket' => $jacket, 'denim' => $denim] = csjJacketOnColourDenim();

    // Indigo 250 m · Black 40 m, cost per colour: INV-16's capacities, Black M 37.1400, the range 33.8840 to 41.1880.
    $product = csjProductProp($jacket);
    $lines = collect($product['components'])->keyBy(fn (array $line) => $line['material']['name']);

    expect(array_keys($lines->all()))->toBe(['Denim 12oz', 'Metal shank button 17mm', 'Polyester thread', 'Woven brand label'])
        ->and($product['cost']['unit_cost'])->toBe(['min' => '33.8840', 'max' => '41.1880'])
        ->and($product['margin']['amount'])->toBe(['min' => '47.8120', 'max' => '55.1160'])
        ->and($product['capacity'])->toMatchArray(['max_producible' => 16, 'best' => 112, 'bottleneck_missing' => false, 'bottleneck_stock' => '40.0000', 'bottleneck_colour_status' => 'low'])
        ->and($product['capacity']['bottleneck']['name'])->toBe('Denim 12oz')
        ->and($product['capacity']['bottleneck_colour']['name'])->toBe('Black')
        ->and($product['capacity']['by_variant']['binding']['colour']['name'])->toBe('Black')
        ->and($product['capacity']['by_variant']['binding']['size']['label'])->toBe('XL')
        ->and(array_map(fn (array $row) => [$row['colour']['name'], $row['guaranteed'], $row['best'], $row['bottleneck_colour']['name'] ?? null, $row['bottleneck_missing']], $product['capacity_by_colour']))
        ->toBe([['Indigo', 103, 112, 'Indigo', false], ['Black', 16, 22, 'Black', false]])
        ->and($lines['Denim 12oz']['material']['stock_mode'])->toBe('per_colour')
        ->and($lines['Denim 12oz']['material']['cost_mode'])->toBe('per_colour')
        ->and($lines['Metal shank button 17mm']['material']['cost_mode'])->toBe('same')
        ->and($lines['Denim 12oz']['stock_on_hand'])->toBe('290.0000')
        ->and(csjReadings($lines['Denim 12oz']))->toBe([['Indigo', false, '250.0000', 103, 142], ['Black', false, '40.0000', 16, 22]])
        ->and($lines['Denim 12oz']['is_bottleneck'])->toBeTrue()
        ->and($lines['Metal shank button 17mm']['material']['stock_mode'])->toBe('single')
        ->and($lines['Metal shank button 17mm']['stock_by_colour'])->toBeNull();

    // Black M 37.1400 and the margin 51.8600, 58.27 %: the product's own figures are ranges; the variant is in the domain test, so check
    // the service for the cell the page does not send.
    $black = app(ProductCostingService::class)->cost($jacket->fresh())->variants->variants;
    $blackM = collect($black)->first(fn ($variant) => $variant->colourId === $jacket->colours['Black'] && $variant->sizeId === $jacket->sizes['M']);

    expect([$blackM->unitCost, $blackM->margin->amount, $blackM->margin->percent])->toBe(['37.1400', '51.8600', '58.27']);

    // Light wash is added to the product and has denim cells, but the material has no Light wash: missing, Can produce 0, and with cost per
    // colour no unit cost, so it stays out of the cost and margin ranges.
    csjCells($jacket, ProductComponent::query()->where('material_id', $denim->id)->firstOrFail(), ['Light wash']);

    $product = csjProductProp($jacket);
    $lines = collect($product['components'])->keyBy(fn (array $line) => $line['material']['name']);

    expect($product['capacity'])->toMatchArray(['max_producible' => 0, 'best' => 112, 'bottleneck_missing' => true, 'bottleneck_stock' => '0.0000', 'bottleneck_colour_status' => 'out'])
        ->and($product['capacity']['bottleneck_colour']['name'])->toBe('Light wash')
        ->and($product['cost']['unit_cost'])->toBe(['min' => '33.8840', 'max' => '41.1880'])
        ->and($product['margin']['amount'])->toBe(['min' => '47.8120', 'max' => '55.1160'])
        ->and(array_map(fn (array $row) => [$row['colour']['name'], $row['guaranteed'], $row['best'], $row['bottleneck_missing']], $product['capacity_by_colour']))
        ->toBe([['Indigo', 103, 112, false], ['Black', 16, 22, false], ['Light wash', 0, 0, true]])
        ->and(csjReadings($lines['Denim 12oz']))->toBe([['Indigo', false, '250.0000', 103, 142], ['Black', false, '40.0000', 16, 22], ['Light wash', true, '0.0000', 0, 0]]);

    // The same material with one cost for every colour: Light wash has a unit cost again (8.5000), so it is in the ranges.
    DB::table('materials')->where('id', $denim->id)->update(['cost_mode' => 'same', 'unit_cost' => '8.5000']);
    DB::table('material_colours')->where('material_id', $denim->id)->update(['unit_cost' => null]);

    $product = csjProductProp($jacket);

    expect($product['cost']['unit_cost'])->toBe(['min' => '33.8840', 'max' => '39.4940'])
        ->and($product['capacity'])->toMatchArray(['max_producible' => 0, 'bottleneck_missing' => true]);

    // Cost per colour again but no product colour has a row: no variant has a unit cost, so the ranges are null, not "0.0000".
    DB::table('materials')->where('id', $denim->id)->update(['cost_mode' => 'per_colour']);
    DB::table('material_colours')->where('material_id', $denim->id)->delete();

    $product = csjProductProp($jacket);

    expect($product['cost']['unit_cost'])->toBeNull()
        ->and($product['cost']['material_cost'])->toBeNull()
        ->and($product['margin'])->toBeNull()
        ->and($product['cost']['labor_cost'])->toBe('12.0000')
        ->and($product['capacity'])->toMatchArray(['max_producible' => 0, 'bottleneck_missing' => true, 'bottleneck_colour_status' => 'out']);
});

it('keeps INV-16 exactly for two one-stock denim materials with a line only for their colour (AC-18)', function () {
    $jacket = VariantJacket::create();

    $product = csjProductProp($jacket);

    expect($product['cost']['unit_cost'])->toBe(['min' => '33.8840', 'max' => '39.4940'])
        ->and($product['margin']['amount'])->toBe(['min' => '49.5060', 'max' => '55.1160'])
        ->and($product['capacity'])->toMatchArray(['max_producible' => 16, 'best' => 112, 'bottleneck_colour' => null, 'bottleneck_missing' => false, 'bottleneck_stock' => '40.0000', 'bottleneck_colour_status' => null])
        ->and($product['capacity']['bottleneck']['name'])->toBe('Denim 12oz Black')
        ->and(array_map(fn (array $row) => [$row['colour']['name'], $row['guaranteed'], $row['best'], $row['bottleneck']['name'], $row['bottleneck_colour'], $row['bottleneck_missing']], $product['capacity_by_colour']))
        ->toBe([['Indigo', 103, 112, 'Denim 12oz Indigo', null, false], ['Black', 16, 22, 'Denim 12oz Black', null, false]])
        ->and(array_filter(array_column($product['components'], 'stock_by_colour'), fn ($readings) => $readings !== null))->toBe([])
        ->and(array_unique(array_column(array_column($product['components'], 'material'), 'stock_mode')))->toBe(['single'])
        ->and(array_column($product['components'], 'stock_on_hand'))->toBe(['250.0000', '40.0000', '900.0000', '20000.0000', '400.0000']);
});

it('lists products on per-colour materials in a constant number of queries', function () {
    $jacket = VariantJacket::create();
    $colours = [$jacket->colours['Indigo'], $jacket->colours['Black']];

    $make = function (int $count) use ($jacket, $colours): void {
        foreach (range(1, $count) as $unused) {
            $denim = Material::factory()
                ->perColour([[$colours[0], '250.0000'], [$colours[1], '40.0000']])
                ->create(['workshop_id' => $jacket->workshop->id, 'unit' => 'm', 'unit_cost' => '8.5000']);
            $product = Product::factory()->withSizes(['S', 'M'])->withComponents([['material' => $denim, 'quantity' => '1.8000', 'waste_percent' => '10.00']])
                ->create(['workshop_id' => $jacket->workshop->id]);
            $product->colours()->syncWithPivotValues($colours, ['workshop_id' => $jacket->workshop->id]);
        }
    };

    $list = fn (): int => countQueries(fn () => actingAs($this->user)->get(route('products.index'))->assertOk());

    $make(1);
    $list(); // warm-up
    $make(1);
    $few = $list(); // 2 products on per-colour materials (+ the jacket)
    $make(4);
    $many = $list(); // 6 (+ the jacket)

    expect($many)->toBe($few);
});
