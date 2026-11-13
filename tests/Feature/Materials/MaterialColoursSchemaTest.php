<?php

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Colour;
use App\Models\Material;
use App\Models\ProductionCut;
use App\Models\ProductionCutMaterial;
use App\Models\StockMovement;
use App\Models\Workshop;
use App\Services\Costing\ProductCostingService;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The colour stock table and the two settings (INV-21-a, ADR-0029)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The unique keys exist on both engines; the CHECKs and the composite keys only exist on
| PostgreSQL (the parity run), so the refusal rows are skipped on SQLite. The migration test runs the migration backwards and forwards
| on both engines.
*/

/**
 * A per-colour material of `$mine` with Indigo 250, a one-stock material, and a production cut that took the per-colour material with no
 * colour; and the Indigo of `$theirs`. Ids only.
 *
 * @return array<string, int>
 */
function materialColourFixture(Workshop $mine, Workshop $theirs): array
{
    $theirIndigo = CurrentWorkshop::run($theirs, fn (): int => Colour::query()->where('code', 'IND')->firstOrFail()->id);

    return CurrentWorkshop::run($mine, function () use ($mine, $theirIndigo): array {
        $material = Material::factory()->perColour([['IND', '250.0000']])->create();
        $cut = ProductionCut::factory()->create();
        ProductionCutMaterial::factory()->create(['production_cut_id' => $cut->id, 'material_id' => $material->id]);

        return [
            'workshop' => $mine->id,
            'material' => $material->id,
            'one stock' => Material::factory()->create()->id,
            'cut' => $cut->id,
            'movement' => StockMovement::factory()->created('250.0000', '8.5000')->create(['material_id' => $material->id])->id,
            'indigo' => Colour::query()->where('code', 'IND')->firstOrFail()->id,
            'their indigo' => $theirIndigo,
        ];
    });
}

it('refuses at the database a colour row, a mode or a cut take that breaks the rules', function (Closure $write, string $message) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The CHECKs and the composite keys are PostgreSQL-only.');
    }

    $f = materialColourFixture(Workshop::factory()->create(), Workshop::factory()->create());

    expect(fn () => DB::transaction(fn () => $write($f)))->toThrow(QueryException::class, $message);
})->with([
    'a negative quantity' => [
        fn (array $f) => DB::table('material_colours')->where('material_id', $f['material'])->update(['quantity_on_hand' => '-1.0000']),
        'material_colours_quantity_check',
    ],
    'a negative cost' => [
        fn (array $f) => DB::table('material_colours')->where('material_id', $f['material'])->update(['unit_cost' => '-0.0001']),
        'material_colours_unit_cost_check',
    ],
    'a second row of one colour for one material' => [
        fn (array $f) => DB::table('material_colours')->insert(['workshop_id' => $f['workshop'], 'material_id' => $f['material'], 'colour_id' => $f['indigo'], 'created_at' => now(), 'updated_at' => now()]),
        'material_colours_material_id_colour_id_unique',
    ],
    'a colour of another workshop' => [
        fn (array $f) => DB::table('material_colours')->insert(['workshop_id' => $f['workshop'], 'material_id' => $f['one stock'], 'colour_id' => $f['their indigo'], 'created_at' => now(), 'updated_at' => now()]),
        'material_colours_workshop_colour_foreign',
    ],
    'a colour of another workshop on a history entry' => [
        fn (array $f) => DB::table('stock_movements')->where('id', $f['movement'])->update(['colour_id' => $f['their indigo']]),
        'stock_movements_workshop_colour_foreign',
    ],
    'a colour of another workshop on a cut take' => [
        fn (array $f) => DB::table('production_cut_materials')->where('material_id', $f['material'])->update(['colour_id' => $f['their indigo']]),
        'production_cut_materials_workshop_colour_foreign',
    ],
    'cost per colour on a one-stock material' => [
        fn (array $f) => DB::table('materials')->where('id', $f['one stock'])->update(['cost_mode' => 'per_colour']),
        'materials_cost_mode_single_check',
    ],
    'a stock mode that does not exist' => [
        fn (array $f) => DB::table('materials')->where('id', $f['one stock'])->update(['stock_mode' => 'mixed']),
        'materials_stock_mode_check',
    ],
    'a second take of one material with no colour in one cut' => [
        fn (array $f) => DB::table('production_cut_materials')->insert([
            'workshop_id' => $f['workshop'], 'production_cut_id' => $f['cut'], 'material_id' => $f['material'],
            'quantity_per_unit' => '1.00000000', 'quantity_taken' => '1.0000', 'created_at' => now(), 'updated_at' => now(),
        ]),
        'production_cut_materials_cut_material_uncoloured_unique',
    ],
]);

it('leaves every material one stock and the Denim Jacket at 35.7540 and 112 across the migration', function () {
    actingAs(prepareDemo($this));

    $migration = stockPerColourMigration();
    $jacket = demoProduct('PRD-JKT-001');
    $denim = demoMaterial('MAT-DEN-012');
    $cut = ProductionCut::factory()->create(['product_id' => $jacket->id]);
    $take = ['production_cut_id' => $cut->id, 'material_id' => $denim->id, 'unit_id' => $denim->unit_id, 'quantity_per_unit' => '1.98000000'];
    ProductionCutMaterial::factory()->create($take);
    StockMovement::factory()->created('250.0000', '8.5000')->create(['material_id' => $denim->id]);

    // What the screens read before: each material's modes and figures, each entry's and each take's colour.
    $snapshot = fn (): array => [
        Material::query()->orderBy('id')->get()->map(fn (Material $m): string => "{$m->id} {$m->stock_mode->value} {$m->cost_mode->value} {$m->quantity_on_hand} {$m->unit_cost}")->all(),
        StockMovement::query()->orderBy('id')->get()->map(fn (StockMovement $m): string => "{$m->id} {$m->type->value} {$m->quantity_after} [{$m->colour_id}] [{$m->entry_group}]")->all(),
        ProductionCutMaterial::query()->orderBy('id')->get()->map(fn (ProductionCutMaterial $m): string => "{$m->id} {$m->quantity_per_unit} [{$m->colour_id}]")->all(),
    ];
    $before = $snapshot();

    expect($before[0])->not->toBe([])->each->toContain(' single same ')
        ->and($before[1])->not->toBe([])->each->toEndWith('[] []')
        ->and($before[2])->not->toBe([])->each->toEndWith('[]');

    // A per-colour material in its Settings › Colours order (Black before Indigo), consistent: the total, and the lowest cost.
    $fabric = Material::factory()->perColour([['IND', '250.0000', '8.5000'], ['BLK', '40.0000', '9.2000']], MaterialCostMode::PerColour)->create();

    $loaded = Material::query()->with('colours.colour')->findOrFail($fabric->id);

    expect($loaded)
        ->quantity_on_hand->toBe('290.0000')
        ->unit_cost->toBe('8.5000')
        ->stock_mode->toBe(MaterialStockMode::PerColour)
        ->and($loaded->colours->map(fn ($row): string => "{$row->colour->code} {$row->quantity_on_hand} {$row->unit_cost}")->all())
        ->toBe(['BLK 40.0000 9.2000', 'IND 250.0000 8.5000']);

    // The rollback says why it can't go back, and changes nothing; with the material gone it removes everything.
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, '1 material(s) keep their stock per colour');
    $fabric->forceDelete();

    $migration->down();

    expect(Schema::hasTable('material_colours'))->toBeFalse()
        ->and(Schema::hasColumns('materials', ['stock_mode', 'cost_mode']))->toBeFalse()
        ->and(Schema::hasColumn('stock_movements', 'colour_id'))->toBeFalse()
        ->and(Schema::hasColumn('stock_movements', 'entry_group'))->toBeFalse()
        ->and(Schema::hasColumn('production_cut_materials', 'colour_id'))->toBeFalse()
        ->and(Schema::hasIndex('production_cut_materials', ['production_cut_id', 'material_id'], 'unique'))->toBeTrue();

    $migration->up();

    // Nothing moved; one colourless take per cut and material, on both drivers, and another take of the same material for a colour is fine.
    expect($snapshot())->toBe($before)
        ->and(Schema::hasIndex('production_cut_materials', ['production_cut_id', 'material_id', 'colour_id'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('production_cut_materials', 'production_cut_materials_cut_material_uncoloured_unique'))->toBeTrue()
        ->and(Schema::hasIndex('stock_movements', ['material_id', 'colour_id']))->toBeTrue()
        ->and(fn () => ProductionCutMaterial::factory()->create($take))->toThrow(QueryException::class);

    ProductionCutMaterial::factory()->create([...$take, 'colour_id' => demoColour('IND')->id]);

    $costing = app(ProductCostingService::class)->cost($jacket->load('components.material'));

    expect($costing->result->cost->unitCost)->toBe('35.7540')
        ->and($costing->result->capacity->maxProducible)->toBe(112);
});
