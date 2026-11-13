<?php

use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Costing\ProductCostingService;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The quantity tables of a composition (INV-16-a, ADR-0027)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The unique keys exist on both engines; the CHECKs and the composite keys only exist on
| PostgreSQL (the parity run), so the refusal rows are skipped on SQLite. The migration test runs the migration backwards and forwards
| on both engines.
*/

/**
 * A product in `$mine` with Indigo and the sizes S and M, one by-colour-and-size line with a cell in Indigo S, one "same" line, a second
 * product that has Indigo S and M too, a production cut, and what `$theirs` owns (a colour and a size). Ids only.
 *
 * @return array<string, int>
 */
function quantityCellFixture(Workshop $mine, Workshop $theirs): array
{
    $other = CurrentWorkshop::run($theirs, fn (): array => [
        'colour' => Colour::query()->where('code', 'IND')->firstOrFail()->id,
        'size' => Size::query()->where('label', 'S')->firstOrFail()->id,
    ]);

    return CurrentWorkshop::run($mine, function () use ($mine, $other): array {
        $indigo = Colour::query()->where('code', 'IND')->firstOrFail();
        $size = fn (string $label): int => Size::query()->where('label', $label)->firstOrFail()->id;
        $product = fn (): Product => tap(Product::factory()->withSizes(['S', 'M'])->create(['category' => 'apparel']), fn (Product $product) => $product->colours()->attach($indigo->id, ['workshop_id' => $product->workshop_id]));

        $first = $product();
        $line = ProductComponent::factory()->byVariant()->create(['product_id' => $first->id]);
        ProductComponentQuantity::factory()->create(['product_component_id' => $line->id, 'colour_id' => $indigo->id, 'size_id' => $size('S'), 'quantity' => '1.8000']);

        return [
            'workshop' => $mine->id,
            'product' => $first->id,
            'other product' => $product()->id,
            'line' => $line->id,
            'same line' => ProductComponent::factory()->create(['product_id' => $first->id])->id,
            'indigo' => $indigo->id,
            'black' => Colour::query()->where('code', 'BLK')->firstOrFail()->id,
            's' => $size('S'),
            'm' => $size('M'),
            'xl' => $size('XL'),
            'cut' => ProductionCut::factory()->create()->id,
            'their colour' => $other['colour'],
            'their size' => $other['size'],
        ];
    });
}

it('refuses at the database a cell, a line or a cut variant that breaks the grid', function (Closure $write, string $message) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite keys and CHECKs are PostgreSQL-only.');
    }

    $f = quantityCellFixture(Workshop::factory()->create(), Workshop::factory()->create());

    // Indigo M of the first product: valid until a row below changes one thing about it.
    $cell = fn (array $override = []): bool => DB::table('product_component_quantities')->insert(array_merge([
        'workshop_id' => $f['workshop'], 'product_component_id' => $f['line'], 'product_id' => $f['product'], 'colour_id' => $f['indigo'],
        'size_id' => $f['m'], 'quantity' => '2.0000', 'created_at' => now(), 'updated_at' => now(),
    ], $override));

    expect(fn () => DB::transaction(fn () => $write($cell, $f)))->toThrow(QueryException::class, $message);
})->with([
    'a colour the product does not have' => [
        fn (Closure $cell, array $f) => $cell(['colour_id' => $f['black']]),
        'product_component_quantities_product_colour_foreign',
    ],
    'a size the product does not have' => [
        fn (Closure $cell, array $f) => $cell(['size_id' => $f['xl']]),
        'product_component_quantities_product_size_foreign',
    ],
    'a colour of another workshop' => [
        fn (Closure $cell, array $f) => $cell(['colour_id' => $f['their colour']]),
        'violates foreign key constraint',
    ],
    'a size of another workshop' => [
        fn (Closure $cell, array $f) => $cell(['size_id' => $f['their size']]),
        'violates foreign key constraint',
    ],
    'a quantity of 0' => [
        fn (Closure $cell) => $cell(['quantity' => '0.0000']),
        'product_component_quantities_quantity_check',
    ],
    'a quantity over 1,000,000' => [
        fn (Closure $cell) => $cell(['quantity' => '1000000.0001']),
        'product_component_quantities_quantity_check',
    ],
    'a second cell for the same line, colour and size' => [
        fn (Closure $cell, array $f) => $cell(['size_id' => $f['s']]),
        'product_component_quantities_cell_unique',
    ],
    'a cell of another product than its line\'s' => [
        fn (Closure $cell, array $f) => $cell(['product_id' => $f['other product']]),
        'product_component_quantities_component_product_foreign',
    ],
    'a by-colour-and-size line with a quantity' => [
        fn (Closure $cell, array $f) => DB::table('product_components')->where('id', $f['line'])->update(['quantity' => '1.0000']),
        'product_components_quantity_mode_check',
    ],
    'a same line without a quantity' => [
        fn (Closure $cell, array $f) => DB::table('product_components')->where('id', $f['same line'])->update(['quantity' => null]),
        'product_components_quantity_mode_check',
    ],
    'a cut variant that returned more units than it held' => [
        fn (Closure $cell, array $f) => DB::table('production_cut_variants')->insert([
            'workshop_id' => $f['workshop'], 'production_cut_id' => $f['cut'], 'colour_id' => $f['indigo'], 'size_id' => $f['s'],
            'units' => 3, 'units_returned' => 4, 'created_at' => now(), 'updated_at' => now(),
        ]),
        'production_cut_variants_units_check',
    ],
]);

it('removes the cells of a deleted product with its lines, and keeps another product\'s', function () {
    $workshop = Workshop::factory()->create();

    [$first, $second] = CurrentWorkshop::run($workshop, function (): array {
        $indigo = Colour::query()->where('code', 'IND')->firstOrFail();

        return array_map(function () use ($indigo): Product {
            $product = Product::factory()->withSizes(['S', 'M'])->create(['category' => 'apparel']);
            $product->colours()->attach($indigo->id, ['workshop_id' => $product->workshop_id]);
            $line = ProductComponent::factory()->byVariant()->create(['product_id' => $product->id]);

            foreach (['S', 'M'] as $label) {
                ProductComponentQuantity::factory()->create(['product_component_id' => $line->id, 'colour_id' => $indigo->id, 'size_id' => Size::query()->where('label', $label)->firstOrFail()->id]);
            }

            return $product;
        }, [1, 2]);
    });

    expect(DB::table('product_component_quantities')->count())->toBe(4);

    // The link rows (colours, sizes), the lines and the cells all go in the one statement of the product's own cascade.
    $first->forceDelete();

    expect(DB::table('product_component_quantities')->pluck('product_id')->unique()->all())->toBe([$second->id])
        ->and(DB::table('product_component_quantities')->count())->toBe(2)
        ->and(DB::table('product_components')->where('product_id', $first->id)->count())->toBe(0);
});

it('leaves every line "same" with its quantity and the Denim Jacket at 35.7540 and 112 across the migration', function () {
    actingAs(prepareDemo($this));

    $migration = quantityCellsMigration();
    $jacket = demoProduct('PRD-JKT-001');
    $denim = demoMaterial('MAT-DEN-012');
    ProductionCutMaterial::factory()->create([
        'production_cut_id' => ProductionCut::factory()->create(['product_id' => $jacket->id])->id,
        'material_id' => $denim->id, 'unit_id' => $denim->unit_id, 'quantity_per_unit' => '1.98000000',
    ]);

    // What the screens read before: every line and every remembered amount per unit.
    $snapshot = fn (): array => [
        ProductComponent::query()->orderBy('id')->get()->map(fn (ProductComponent $line): string => "{$line->id} {$line->quantity_mode->value} {$line->quantity} {$line->waste_percent}")->all(),
        ProductionCutMaterial::query()->orderBy('id')->get()->map(fn (ProductionCutMaterial $row): string => "{$row->id} {$row->quantity_per_unit}")->all(),
    ];
    $before = $snapshot();

    expect($before[0])->not->toBe([])
        ->and($before[0])->each->toContain(' same ');

    // A line by colour and size has no single quantity to go back to: the rollback says so and changes nothing.
    $variantLine = ProductComponent::factory()->byVariant()->create(['product_id' => $jacket->id, 'material_id' => Material::factory()->create()->id]);

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, '1 composition line(s) are By colour and size');
    $variantLine->delete();

    $migration->down();

    expect(Schema::hasTable('product_component_quantities'))->toBeFalse()
        ->and(Schema::hasTable('production_cut_variants'))->toBeFalse()
        ->and(Schema::hasTable('production_cut_variant_materials'))->toBeFalse()
        ->and(Schema::hasColumn('product_components', 'quantity_mode'))->toBeFalse();

    $migration->up();

    // Nothing moved, and the rebuilt tables kept their keys and indexes (SQLite rebuilds both to change a column).
    $foreignKeys = fn (string $table): array => collect(Schema::getForeignKeys($table))->pluck('foreign_table')->unique()->sort()->values()->all();

    expect($snapshot())->toBe($before)
        ->and(Schema::hasIndex('product_components', ['product_id', 'material_id'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('product_components', ['material_id']))->toBeTrue()
        ->and($foreignKeys('product_components'))->toBe(['materials', 'products', 'workshops'])
        ->and(Schema::hasIndex('production_cut_materials', ['production_cut_id', 'material_id'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('production_cut_materials', ['material_id']))->toBeTrue()
        ->and($foreignKeys('production_cut_materials'))->toBe(['colours', 'materials', 'production_cuts', 'units', 'workshops']); // `colours`: INV-21-a's colour_id, brought back by the same object

    $costing = app(ProductCostingService::class)->cost($jacket->load('components.material'));

    expect($costing->result->cost->unitCost)->toBe('35.7540')
        ->and($costing->result->capacity->maxProducible)->toBe(112);
});
