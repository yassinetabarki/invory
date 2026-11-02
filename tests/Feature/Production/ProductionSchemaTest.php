<?php

use App\Enums\ProductionBatchStatus;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionCounter;
use App\Models\ProductionCut;
use App\Models\ProductionPhase;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| The production tables (INV-5-a, ADR-0023)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The unique keys exist on both engines; the composite keys and the CHECKs only exist
| on PostgreSQL (the parity run), so those rows are skipped on SQLite.
*/

it('keeps a batch in its workshop, numbered once, with 1 to 1,000,000 units', function (Closure $write, string $postgres, ?string $sqlite) {
    if ($sqlite === null && DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The composite keys and CHECKs are PostgreSQL-only.');
    }

    [$mine, $other] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $batch = CurrentWorkshop::run($mine, fn (): ProductionBatch => ProductionBatch::factory()->create());
    $theirs = CurrentWorkshop::run($other, fn (): array => [
        'product' => Product::factory()->create(['category' => 'apparel']),
        'cut' => ProductionCut::factory()->create(),
        'phase' => ProductionPhase::factory()->create(['name' => 'Sewing']),
    ]);

    $insert = fn (array $override): bool => DB::table('production_batches')->insert(array_merge([
        'workshop_id' => $batch->workshop_id, 'number' => 99, 'product_id' => $batch->product_id, 'colour_id' => $batch->colour_id,
        'size_id' => $batch->size_id, 'production_cut_id' => $batch->production_cut_id, 'units' => 5, 'status' => 'in_production',
        'phase_since' => now()->toDateString(), 'started_on' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ], $override));

    expect(fn () => DB::transaction(fn () => $write($insert, $batch, $theirs)))
        ->toThrow(QueryException::class, DB::getDriverName() === 'pgsql' ? $postgres : $sqlite);
})->with([
    'a second batch with the same number' => [
        fn (Closure $insert, ProductionBatch $batch) => $insert(['number' => $batch->number]),
        'production_batches_workshop_id_number_unique',
        'production_batches.workshop_id, production_batches.number',
    ],
    'a batch of another workshop\'s product' => [
        fn (Closure $insert, ProductionBatch $batch, array $theirs) => $insert(['product_id' => $theirs['product']->id]),
        'production_batches_workshop_product_foreign',
        null,
    ],
    'a batch of another workshop\'s cut' => [
        fn (Closure $insert, ProductionBatch $batch, array $theirs) => $insert(['production_cut_id' => $theirs['cut']->id]),
        'production_batches_workshop_cut_foreign',
        null,
    ],
    'a batch in another workshop\'s phase' => [
        fn (Closure $insert, ProductionBatch $batch, array $theirs) => $insert(['phase_id' => $theirs['phase']->id]),
        'production_batches_workshop_phase_foreign',
        null,
    ],
    'a batch of 0 units' => [
        fn (Closure $insert) => $insert(['units' => 0]),
        'production_batches_units_check',
        null,
    ],
    'a batch of 1,000,001 units' => [
        fn (Closure $insert) => $insert(['units' => 1000001]),
        'production_batches_units_check',
        null,
    ],
    'a batch with an unknown status' => [
        fn (Closure $insert) => $insert(['status' => 'lost']),
        'production_batches_status_check',
        null,
    ],
]);

it('keeps a phase name once per workshop ignoring capitals, at positions 1 to 6', function (array $row, string $postgres, ?string $sqlite) {
    if ($sqlite === null && DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The CHECKs are PostgreSQL-only.');
    }

    $phase = ProductionPhase::factory()->create(['name' => 'Sewing', 'position' => 1]);

    expect(fn () => DB::transaction(fn () => DB::table('production_phases')->insert(array_merge([
        'workshop_id' => $phase->workshop_id, 'name' => 'Finishing', 'position' => 2, 'created_at' => now(), 'updated_at' => now(),
    ], $row))))->toThrow(QueryException::class, DB::getDriverName() === 'pgsql' ? $postgres : $sqlite);
})->with([
    'the same name in other capitals' => [['name' => 'SEWING'], 'production_phases_workshop_name_unique', 'production_phases_workshop_name_unique'],
    'a seventh position' => [['position' => 7], 'production_phases_position_check', null],
    'position 0' => [['position' => 0], 'production_phases_position_check', null],
]);

it('lets two workshops use the same phase name and the same batch number', function () {
    [$a, $b] = [Workshop::factory()->create(), Workshop::factory()->create()];

    $first = CurrentWorkshop::run($a, fn (): array => [ProductionPhase::factory()->create(['name' => 'Sewing']), ProductionBatch::factory()->create()]);
    $second = CurrentWorkshop::run($b, fn (): array => [ProductionPhase::factory()->create(['name' => 'Sewing']), ProductionBatch::factory()->create()]);

    expect($first[1]->number)->toBe(1)
        ->and($second[1]->number)->toBe(1)
        ->and($first[1]->label)->toBe('B-0001')
        ->and(CurrentWorkshop::run($a, fn (): int => ProductionCounter::query()->sole()->last_batch_number))->toBe(1);
});

it('labels a batch with at least four digits', function (int $number, string $label) {
    expect(ProductionBatch::labelOf($number))->toBe($label);
})->with([
    [1, 'B-0001'],
    [147, 'B-0147'],
    [9999, 'B-9999'],
    [10000, 'B-10000'],
]);

it('makes a batch in production, In cutting, of a colour of its product, in its cut\'s workshop', function () {
    $batch = ProductionBatch::factory()->create(['units' => 6]);

    expect($batch->status)->toBe(ProductionBatchStatus::InProduction)
        ->and($batch->isInCutting())->toBeTrue()
        ->and($batch->cut->units)->toBe(6)
        ->and($batch->cut->workshop_id)->toBe($batch->workshop_id)
        ->and($batch->product->colours()->pluck('colours.id')->all())->toContain($batch->colour_id);
});
