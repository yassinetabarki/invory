<?php

use App\Actions\Products\SyncProductColours;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\Size;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\VariantJacket;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Saving the quantity grid with the product (INV-16-e, AC-3, ADR-0027)
|--------------------------------------------------------------------------
|
| Add product and Edit product save the page and its grid together. The Denim Jacket of the card (Indigo and Black, S · M · L · XL) has
| two By colour and size denim lines (Indigo denim under Indigo only, Black denim under Black only) and three Same for every variant
| lines. A cell is empty or 0 (no cell) or a number up to 1,000,000 with up to 4 decimals; a refused save keeps everything as it was.
*/

const QGW_CELL = 'Enter a number from 0 to 1,000,000 with up to 4 decimals.';
const QGW_CHANGED = 'The sizes or colours of Denim Jacket have changed. Enter its quantities again.';

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
    $this->jacket = VariantJacket::create();
});

/**
 * The jacket's composition as the page submits it (INV-16-e): line 0 Indigo denim and line 1 Black denim by colour and size (the card's
 * quantities unless `$denim` says otherwise: colour label => [S, M, L, XL]), then the button, thread and label Same for every variant.
 *
 * @param  array<string, list<string>>  $denim
 * @return list<array<string, mixed>>
 */
function qgwLines(VariantJacket $jacket, array $denim = []): array
{
    $grid = fn (string $colour, array $quantities): array => [$jacket->colours[$colour] => array_combine(
        array_map(fn (string $label): int => $jacket->sizes[$label], ['S', 'M', 'L', 'XL']),
        $quantities,
    )];
    $denim += ['Indigo' => ['1.6', '1.8', '2', '2.2'], 'Black' => ['1.6', '1.8', '2', '2.2']];
    $material = $jacket->materials;

    return [
        ['material_id' => $material['indigo_denim']->id, 'quantity_mode' => 'by_variant', 'quantities' => $grid('Indigo', $denim['Indigo']), 'waste_percent' => '10', 'notes' => null],
        ['material_id' => $material['black_denim']->id, 'quantity_mode' => 'by_variant', 'quantities' => $grid('Black', $denim['Black']), 'waste_percent' => '10', 'notes' => null],
        ['material_id' => $material['button']->id, 'quantity_mode' => 'same', 'quantity' => '8', 'waste_percent' => '0', 'notes' => null],
        ['material_id' => $material['thread']->id, 'quantity_mode' => 'same', 'quantity' => '120', 'waste_percent' => '5', 'notes' => null],
        ['material_id' => $material['label']->id, 'quantity' => '1', 'waste_percent' => '0', 'notes' => null],
    ];
}

/** The payload of a save of the jacket: Edit keeps its SKU, Add takes a new one; colours Indigo and Black, sizes S · M · L · XL unless said otherwise. */
function qgwPayload(VariantJacket $jacket, array $components, bool $edit = true, array $overrides = []): array
{
    return [
        'sku' => $edit ? $jacket->product->sku : 'PRD-QGW-001',
        'name' => 'Denim Jacket',
        'category' => 'apparel',
        'status' => 'active',
        'description' => null,
        'labor_cost' => '12',
        'overhead_cost' => '3.5',
        'selling_price' => '89',
        'colours' => [$jacket->colours['Indigo'], $jacket->colours['Black']],
        'sizes' => array_map(fn (string $label): int => $jacket->sizes[$label], ['S', 'M', 'L', 'XL']),
        'components' => $components,
        ...$overrides,
    ];
}

/**
 * Every cell of a product as "material|colour|size|quantity", sorted.
 *
 * @return list<string>
 */
function qgwCells(Product $product): array
{
    $cells = DB::table('product_component_quantities as q')
        ->join('product_components as pc', 'pc.id', '=', 'q.product_component_id')
        ->join('materials as m', 'm.id', '=', 'pc.material_id')
        ->join('colours as c', 'c.id', '=', 'q.colour_id')
        ->join('sizes as s', 's.id', '=', 'q.size_id')
        ->where('q.product_id', $product->id)
        ->get(['m.name as material', 'c.name as colour', 's.label as size', 'q.quantity'])
        ->map(fn (object $cell): string => implode('|', [$cell->material, $cell->colour, $cell->size, number_format((float) $cell->quantity, 4, '.', '')]))
        ->all();

    sort($cells);

    return $cells;
}

/** How the lines of a product read: material name => "mode quantity". */
function qgwLinesOf(Product $product): array
{
    return ProductComponent::query()->where('product_id', $product->id)->with('material')->orderBy('position')->get()
        ->mapWithKeys(fn (ProductComponent $line): array => [$line->material->name => $line->quantity_mode->value.' '.($line->quantity ?? 'null')])
        ->all();
}

it('saves the card\'s two denim lines with the product on Edit and on Add, an empty or 0 cell making no cell (AC-3)', function () {
    $product = $this->jacket->product;
    $edited = qgwLines($this->jacket, ['Indigo' => ['1.6', '1.85', '2', '2.2'], 'Black' => ['1.6', '', '0', '2.2000']]);

    actingAs($this->user)->put(route('products.update', $product), qgwPayload($this->jacket, $edited))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.show', $product))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Product updated.']);

    expect(qgwCells($product))->toBe([
        'Denim 12oz Black|Black|S|1.6000',
        'Denim 12oz Black|Black|XL|2.2000',
        'Denim 12oz Indigo|Indigo|L|2.0000',
        'Denim 12oz Indigo|Indigo|M|1.8500',
        'Denim 12oz Indigo|Indigo|S|1.6000',
        'Denim 12oz Indigo|Indigo|XL|2.2000',
    ])->and(qgwLinesOf($product))->toBe([
        'Denim 12oz Indigo' => 'by_variant null',
        'Denim 12oz Black' => 'by_variant null',
        'Metal shank button 17mm' => 'same 8.0000',
        'Polyester thread' => 'same 120.0000',
        'Woven brand label' => 'same 1.0000',
    ]);

    // The same grid on Add: a new product holds its own cells, stamped with its workshop.
    actingAs($this->user)->post(route('products.store'), qgwPayload($this->jacket, qgwLines($this->jacket), edit: false))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Product created.']);

    $created = Product::query()->where('sku', 'PRD-QGW-001')->firstOrFail();

    expect(qgwCells($created))->toHaveCount(8)
        ->and(ProductComponentQuantity::query()->where('product_id', $created->id)->pluck('workshop_id')->unique()->all())->toBe([$created->workshop_id])
        ->and(qgwCells($product))->toHaveCount(6);
});

it('refuses a bad grid with its sentence on its key, on Edit and on Add, and saves nothing (AC-3)', function (Closure $change, array $expected) {
    $jacket = $this->jacket;
    $product = $jacket->product;
    $other = CurrentWorkshop::run(Workshop::factory()->create(), fn (): int => Colour::factory()->create()->id);
    $extra = ['Light wash' => $jacket->colours['Light wash'], 'XXL' => Size::query()->where('label', 'XXL')->value('id'), 'Other' => $other];
    $lines = $change(qgwLines($jacket), $jacket, $extra);

    // The placeholders of the keys are the ids of the jacket's colours and sizes.
    $keys = collect($expected)->mapWithKeys(fn (string $message, string $key): array => [
        (string) preg_replace_callback('/\{([^}]+)\}/', fn (array $m): string => (string) ($jacket->colours[$m[1]] ?? $jacket->sizes[$m[1]]), $key) => $message,
    ])->all();
    $before = [qgwCells($product), qgwLinesOf($product), Product::query()->count()];

    foreach ([
        ['edit', fn () => actingAs($this->user)->from(route('products.edit', $product))->put(route('products.update', $product), qgwPayload($jacket, $lines))],
        ['add', fn () => actingAs($this->user)->from(route('products.create'))->post(route('products.store'), qgwPayload($jacket, $lines, edit: false))],
    ] as [$where, $send]) {
        $send()->assertRedirect($where === 'edit' ? route('products.edit', $product) : route('products.create'))->assertSessionHasErrors(array_keys($keys));

        expect(session('errors')->keys())->toBe(array_keys($keys));

        foreach ($keys as $key => $message) {
            expect(session('errors')->first($key))->toBe($message);
        }

        expect([qgwCells($product), qgwLinesOf($product), Product::query()->count()])->toBe($before);
    }
})->with([
    'letters' => [fn (array $lines, VariantJacket $j) => data_set($lines, '0.quantities.'.$j->colours['Indigo'].'.'.$j->sizes['S'], 'abc'), ['components.0.quantities.{Indigo}.{S}' => QGW_CELL]],
    'negative' => [fn (array $lines, VariantJacket $j) => data_set($lines, '0.quantities.'.$j->colours['Indigo'].'.'.$j->sizes['M'], '-1'), ['components.0.quantities.{Indigo}.{M}' => QGW_CELL]],
    'five decimals' => [fn (array $lines, VariantJacket $j) => data_set($lines, '1.quantities.'.$j->colours['Black'].'.'.$j->sizes['L'], '1.12345'), ['components.1.quantities.{Black}.{L}' => QGW_CELL]],
    'over 1,000,000' => [fn (array $lines, VariantJacket $j) => data_set($lines, '1.quantities.'.$j->colours['Black'].'.'.$j->sizes['XL'], '1000000.0001'), ['components.1.quantities.{Black}.{XL}' => QGW_CELL]],
    'a line with no cell' => [fn (array $lines, VariantJacket $j) => data_set($lines, '1.quantities', [$j->colours['Black'] => [$j->sizes['S'] => '', $j->sizes['M'] => '0', $j->sizes['L'] => '0.0000']]), [
        'components.1.quantities' => 'Add at least one quantity for Denim 12oz Black, or switch it to Same for every variant.',
    ]],
    'a cell of a colour not in the save' => [fn (array $lines, VariantJacket $j, array $extra) => data_set($lines, '0.quantities.'.$extra['Light wash'], [$j->sizes['S'] => '1.5']), ['components' => QGW_CHANGED]],
    'a cell of a size not in the save' => [fn (array $lines, VariantJacket $j, array $extra) => data_set($lines, '0.quantities.'.$j->colours['Indigo'].'.'.$extra['XXL'], '2.4'), ['components' => QGW_CHANGED]],
    'a cell of another workshop\'s colour' => [fn (array $lines, VariantJacket $j, array $extra) => data_set($lines, '0.quantities.'.$extra['Other'], [$j->sizes['S'] => '1.5']), ['components' => QGW_CHANGED]],
]);

it('accepts 2,000 quantities across the lines and refuses 2,001 (AC-3)', function (int $cells, bool $accepted) {
    $colours = Colour::query()->ordered()->pluck('id')->all();
    $sizes = Size::query()->whereIn('label', array_map('strval', range(36, 46)))->ordered()->pluck('id')->all();
    $materials = Material::factory()->count(10)->create();

    // Ten lines: nine of 20 colours × 11 sizes (1,980) and the last of what is left.
    $lines = $materials->map(function (Material $material, int $index) use ($colours, $sizes, $cells): array {
        $count = $index < 9 ? 220 : $cells - 1980;
        $grid = [];

        foreach (array_slice(array_merge(...array_map(fn (int $colour): array => array_map(fn (int $size): array => [$colour, $size], $sizes), $colours)), 0, $count) as [$colour, $size]) {
            $grid[$colour][$size] = '1.5';
        }

        return ['material_id' => $material->id, 'quantity_mode' => 'by_variant', 'quantities' => $grid, 'waste_percent' => '0', 'notes' => null];
    })->all();

    $response = actingAs($this->user)->post(route('products.store'), qgwPayload($this->jacket, $lines, edit: false, overrides: ['colours' => $colours, 'sizes' => $sizes]));

    if ($accepted) {
        $response->assertSessionHasNoErrors();
        expect(ProductComponentQuantity::query()->count())->toBe(2000 + 8);
    } else {
        $response->assertSessionHasErrors(['components' => 'Denim Jacket can hold at most 2,000 quantities across its materials. Remove some, or switch a line to Same for every variant.']);
        expect(Product::query()->where('sku', 'PRD-QGW-001')->exists())->toBeFalse();
    }
})->with([
    'exactly 2,000' => [2000, true],
    '2,001' => [2001, false],
]);

it('deletes the cells of a colour taken off the product in the same save (AC-3)', function () {
    $jacket = $this->jacket;
    $product = $jacket->product;

    // The Black denim line also has Indigo cells. The page drops them with the colour; the colours sync deletes them before it detaches Indigo.
    foreach (['S', 'M'] as $label) {
        ProductComponentQuantity::factory()->create([
            'product_component_id' => $product->components()->where('material_id', $jacket->materials['black_denim']->id)->value('id'),
            'colour_id' => $jacket->colours['Indigo'], 'size_id' => $jacket->sizes[$label], 'quantity' => '1.0000',
        ]);
    }

    expect(qgwCells($product))->toHaveCount(10);

    $lines = array_slice(qgwLines($jacket), 1);

    actingAs($this->user)->put(route('products.update', $product), qgwPayload($jacket, $lines, overrides: ['colours' => [$jacket->colours['Black']]]))
        ->assertSessionHasNoErrors();

    expect($product->colours()->pluck('colours.id')->all())->toBe([$jacket->colours['Black']])
        ->and(qgwCells($product))->toBe([
            'Denim 12oz Black|Black|L|2.0000',
            'Denim 12oz Black|Black|M|1.8000',
            'Denim 12oz Black|Black|S|1.6000',
            'Denim 12oz Black|Black|XL|2.2000',
        ]);

    // The colours sync alone does it too, whatever else the save does.
    $other = VariantJacket::create();
    app(SyncProductColours::class)->handle($other->product, [$other->colours['Black']]);

    expect(ProductComponentQuantity::query()->where('product_id', $other->product->id)->pluck('colour_id')->unique()->all())->toBe([$other->colours['Black']]);
});

it('gives a colour or a size added to the product no cells (AC-3)', function () {
    $jacket = $this->jacket;
    $product = $jacket->product;
    $before = qgwCells($product);

    actingAs($this->user)->put(route('products.update', $product), qgwPayload($jacket, qgwLines($jacket), overrides: [
        'colours' => [$jacket->colours['Indigo'], $jacket->colours['Black'], $jacket->colours['Light wash']],
        'sizes' => array_map(fn (string $label): int => Size::query()->where('label', $label)->value('id'), ['S', 'M', 'L', 'XL', 'XXL']),
    ]))->assertSessionHasNoErrors();

    $lightWash = ProductComponentQuantity::query()->where('product_id', $product->id)->where('colour_id', $jacket->colours['Light wash'])->count();
    $xxl = ProductComponentQuantity::query()->where('product_id', $product->id)->where('size_id', Size::query()->where('label', 'XXL')->value('id'))->count();

    expect($product->colours()->count())->toBe(3)
        ->and($product->sizes()->count())->toBe(5)
        ->and([$lightWash, $xxl])->toBe([0, 0])
        ->and(qgwCells($product))->toBe($before);
});

it('clears the cells of a line switched back to Same for every variant, and gives a Same line its cells when switched (AC-3)', function () {
    $jacket = $this->jacket;
    $product = $jacket->product;
    $lines = qgwLines($jacket);

    // The Indigo denim goes back to one quantity; the thread becomes By colour and size with one cell.
    $lines[0] = ['material_id' => $jacket->materials['indigo_denim']->id, 'quantity_mode' => 'same', 'quantity' => '1.9', 'waste_percent' => '10', 'notes' => null, 'quantities' => $lines[0]['quantities']];
    $lines[3] = ['material_id' => $jacket->materials['thread']->id, 'quantity_mode' => 'by_variant', 'quantity' => '120', 'waste_percent' => '5', 'notes' => null, 'quantities' => [$jacket->colours['Indigo'] => [$jacket->sizes['S'] => '118.5']]];

    actingAs($this->user)->put(route('products.update', $product), qgwPayload($jacket, $lines))->assertSessionHasNoErrors();

    expect(qgwLinesOf($product))->toBe([
        'Denim 12oz Indigo' => 'same 1.9000',
        'Denim 12oz Black' => 'by_variant null',
        'Metal shank button 17mm' => 'same 8.0000',
        'Polyester thread' => 'by_variant null',
        'Woven brand label' => 'same 1.0000',
    ])->and(qgwCells($product))->toBe([
        'Denim 12oz Black|Black|L|2.0000',
        'Denim 12oz Black|Black|M|1.8000',
        'Denim 12oz Black|Black|S|1.6000',
        'Denim 12oz Black|Black|XL|2.2000',
        'Polyester thread|Indigo|S|118.5000',
    ]);
});

it('sends each line with its mode and cells in colour and size order, and a Same line with its figures (AC-3)', function () {
    $jacket = $this->jacket;
    $sizes = fn (int $colour): array => array_map(fn (string $label): array => ['colour_id' => $colour, 'size_id' => $jacket->sizes[$label]], ['S', 'M', 'L', 'XL']);

    actingAs($this->user)->get(route('products.edit', $jacket->product))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('products/edit')
        // Black is the binding variant's bottleneck, so the Black denim line is the bottleneck; the Indigo denim is not.
        ->has('product.components.0', fn (Assert $line) => $line
            ->where('quantity_mode', 'by_variant')
            ->where('quantities', array_map(
                fn (array $cell, string $quantity): array => [...$cell, 'quantity' => $quantity],
                $sizes($jacket->colours['Indigo']),
                ['1.6000', '1.8000', '2.0000', '2.2000'],
            ))
            ->where('quantity', null)->where('effective_quantity', null)->where('line_cost', null)->where('units_possible', null)
            ->where('waste_percent', '10.00')->where('unit_cost', '8.5000')->where('stock_on_hand', '250.0000')->where('is_bottleneck', false)
            ->etc())
        ->where('product.components.1.is_bottleneck', true)
        ->has('product.components.2', fn (Assert $line) => $line
            ->where('quantity_mode', 'same')->where('quantities', [])
            ->where('quantity', '8.0000')->where('effective_quantity', '8.0000')->where('line_cost', '2.8000')->where('units_possible', 112)
            ->where('is_bottleneck', false)
            ->etc())
        // All four sizes hold a cell, so all four are locked (the fifth reason).
        ->where('size_locks.'.$jacket->sizes['XL'], "Can't be taken off: it has material quantities.")
        ->has('size_locks', 4));
});
