<?php

use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A ticket code on every batch (INV-15-a, AC-5, ADR-0024)
|--------------------------------------------------------------------------
|
| Every batch gets 32 random letters and digits when it is made: by Start batch, by a split and by the factories. The QR code on its
| work ticket carries it. A split never shares its parent's code. The code is never sent to a page.
*/

beforeEach(function () {
    $example = progressExample($this);
    $this->user = $example['user'];
    $this->sewing = $example['sewing'];
    $this->finishing = $example['finishing'];
    $this->batches = $example['batches'];
});

it('gives every batch its own 32-character code', function () {
    $codes = collect($this->batches)->map(fn (ProductionBatch $batch): string => $batch->fresh()->ticket_code);

    expect($codes->every(fn (string $code): bool => preg_match('/^[A-Za-z0-9]{32}$/', $code) === 1))->toBeTrue()
        ->and($codes->unique())->toHaveCount(count($this->batches));
});

it('gives a split batch a code of its own and lets its parent keep its code', function () {
    $parent = $this->batches['B-0149'];
    $code = $parent->fresh()->ticket_code;

    actingAs($this->user)->post(route('progress.batches.moves.store', $parent), [
        'from_phase' => $this->sewing->id, 'to_phase' => $this->finishing->id, 'units' => 4,
    ])->assertSessionHasNoErrors();

    $split = ProductionBatch::query()->where('split_from_id', $parent->id)->sole();

    expect($parent->fresh()->ticket_code)->toBe($code)
        ->and($split->ticket_code)->toMatch('/^[A-Za-z0-9]{32}$/')
        ->and($split->ticket_code)->not->toBe($code);
});

it('gives a batch started with Start batch a code', function () {
    $jacket = $this->batches['B-0147']->product;

    actingAs($this->user)->post(route('progress.batches.store'), [
        'product_id' => $jacket->id, 'units' => restockLine($jacket, ['Indigo' => ['S' => 1]])['units'],
    ])->assertSessionHasNoErrors();

    expect(ProductionBatch::query()->where('number', 155)->sole()->ticket_code)->toMatch('/^[A-Za-z0-9]{32}$/');
});

it('never sends the code to a page', function () {
    expect($this->batches['B-0147']->fresh()->toArray())->not->toHaveKey('ticket_code');
});

it('gives the batches made before INV-15 a code when the migration runs', function () {
    $ids = collect($this->batches)->map->id->values();

    ticketCodesMigration()->down();
    expect(DB::getSchemaBuilder()->hasColumn('production_batches', 'ticket_code'))->toBeFalse();
    ticketCodesMigration()->up();

    $codes = DB::table('production_batches')->whereIn('id', $ids)->pluck('ticket_code');

    expect($codes)->toHaveCount($ids->count())
        ->and($codes->every(fn (?string $code): bool => is_string($code) && strlen($code) === 32))->toBeTrue()
        ->and($codes->unique())->toHaveCount($ids->count());
});
