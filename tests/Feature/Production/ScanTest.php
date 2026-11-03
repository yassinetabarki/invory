<?php

use App\Enums\ProductionBatchStatus;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMove;
use App\Models\ProductionPhase;
use App\Models\ProductRestock;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Scan a ticket (INV-15-c, AC-3 to AC-6)
|--------------------------------------------------------------------------
|
| `GET scan/lookup?q=` reads a typed number or a scanned ticket link and answers with the batch's confirm or the reason nothing can
| move; `POST scan/batches/{batch}` moves the whole batch on, or puts it in stock at the last phase, after checking nothing changed since
| the confirm; `GET scan` and `GET scan/t/{code}` render the page. The card's batches, on 30 Oct 2026, phases Sewing and Finishing.
*/

beforeEach(function () {
    $example = progressExample($this);
    $this->user = $example['user'];
    $this->jacket = $example['jacket'];
    $this->sewing = $example['sewing'];
    $this->finishing = $example['finishing'];
    $this->batches = $example['batches'];
});

function scanLookup(object $test, string $q)
{
    return actingAs($test->user)->getJson(route('scan.lookup', ['q' => $q]))->assertOk();
}

/** POST the confirm's button for what a lookup of the batch shows now (or with `$override`). */
function scanBatch(object $test, ProductionBatch $batch, array $override = [])
{
    $confirm = scanLookup($test, $batch->label)->json('confirm');

    return actingAs($test->user)->post(route('scan.batches.store', $batch), array_merge([
        'from' => $confirm['from'], 'to' => $confirm['to'], 'units' => $confirm['units'],
    ], $override));
}

it('finds B-0147 however its number is typed, and from its ticket link on any host (AC-3, AC-5)', function (string $typed) {
    expect(scanLookup($this, $typed)->json('confirm.number'))->toBe('B-0147');
})->with(['B-0147', 'b-0147', 'B147', '147', '0147', ' B-0147 ', 'b0147']);

it('finds a batch from the link in its QR code, scanned from any host', function () {
    $code = $this->batches['B-0147']->fresh()->ticket_code;

    expect(scanLookup($this, "https://elsewhere.example/scan/t/{$code}")->json('confirm.number'))->toBe('B-0147')
        ->and(scanLookup($this, route('scan.ticket', $code))->json('confirm.number'))->toBe('B-0147');
});

it('shows the confirm of the next phase: B-0147 in Sewing goes to Finishing (AC-4)', function () {
    scanLookup($this, 'B-0147')->assertExactJson([
        'confirm' => [
            'batch_id' => $this->batches['B-0147']->id,
            'number' => 'B-0147',
            'units' => 4,
            'product' => 'Denim Jacket',
            'variant' => 'Indigo · S',
            'started_on' => '2026-10-23',
            'path' => [
                ['name' => 'In cutting', 'state' => 'done'],
                ['name' => 'Sewing', 'state' => 'now'],
                ['name' => 'Finishing', 'state' => 'next'],
                ['name' => 'Put in stock', 'state' => 'later'],
            ],
            'from' => $this->sewing->id,
            'to' => $this->finishing->id,
            'to_name' => 'Finishing',
            'shelf' => null,
            'warning' => null,
        ],
        'refusal' => null,
    ]);

    expect(scanLookup($this, 'B-0150')->json('confirm'))->toMatchArray(['from' => 'cutting', 'to' => $this->sewing->id, 'to_name' => 'Sewing']);
});

it('offers Put in stock at the last phase, with the shelf now and after (AC-4)', function () {
    expect(scanLookup($this, 'B-0148')->json('confirm'))->toMatchArray([
        'from' => $this->finishing->id, 'to' => 'stock', 'to_name' => 'Put in stock', 'shelf' => ['now' => 0, 'after' => 4],
    ]);
});

it('says why nothing can move (AC-6)', function (Closure $arrange, string $typed, string $refusal) {
    $arrange($this);

    scanLookup($this, $typed)->assertExactJson(['confirm' => null, 'refusal' => $refusal]);
})->with([
    'already in stock' => [
        fn (object $test) => ProductionBatch::factory()->create([
            'number' => 139, 'product_id' => $test->jacket->id, 'status' => ProductionBatchStatus::InStock, 'finished_at' => '2026-10-28 15:00:00',
        ]),
        'B-0139', 'B-0139 was put in stock on 28 Oct 2026. Nothing left to do.',
    ],
    'undone' => [
        fn (object $test) => $test->batches['B-0150']->forceFill(['status' => ProductionBatchStatus::Undone, 'undone_at' => now()])->save(),
        'B-0150', 'B-0150 was undone on 30 Oct 2026: its materials went back to stock. Nothing to move.',
    ],
    'an unknown number' => [fn () => null, 'B-0999', 'No batch B-0999 in your workshop. Check the number on the ticket.'],
    'not a batch number' => [fn () => null, 'hello', 'A batch number looks like B-0142. Check what you typed.'],
    'letters in the number' => [fn () => null, 'B-01a7', 'A batch number looks like B-0142. Check what you typed.'],
    'a link that isn\'t a ticket' => [fn () => null, 'https://invory.test/scan/t/'.str_repeat('x', 32), 'This code isn\'t a work ticket of your workshop. Check the ticket, or type its number.'],
    'a ticket link cut short' => [fn () => null, 'https://invory.test/scan/t/abc', 'This code isn\'t a work ticket of your workshop. Check the ticket, or type its number.'],
    'another QR code' => [fn () => null, 'https://example.com/menu', 'This code isn\'t a work ticket of your workshop. Check the ticket, or type its number.'],
    'a deleted product at the last phase' => [
        fn (object $test) => $test->jacket->delete(),
        'B-0148', 'Denim Jacket was deleted, so B-0148 can\'t be put in stock.',
    ],
]);

it('still moves a deleted product\'s batch between phases', function () {
    $this->jacket->delete();

    expect(scanLookup($this, 'B-0147')->json('confirm.to_name'))->toBe('Finishing');
    scanBatch($this, $this->batches['B-0147'])->assertSessionHasNoErrors();
    expect($this->batches['B-0147']->fresh()->phase_id)->toBe($this->finishing->id);
});

it('never finds another workshop\'s batch, by code or by number, and never says it exists (AC-6)', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): ProductionBatch => ProductionBatch::factory()->create(['number' => 160]));

    scanLookup($this, route('scan.ticket', $theirs->ticket_code))
        ->assertExactJson(['confirm' => null, 'refusal' => 'This code isn\'t a work ticket of your workshop. Check the ticket, or type its number.']);
    scanLookup($this, 'B-0160')
        ->assertExactJson(['confirm' => null, 'refusal' => 'No batch B-0160 in your workshop. Check the number on the ticket.']);

    actingAs($this->user)->post(route('scan.batches.store', $theirs), ['from' => 'cutting', 'to' => 'stock', 'units' => 1])->assertNotFound();
    actingAs($this->user)->get(route('scan.ticket', $theirs->ticket_code))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('confirm', null)
        ->where('refusal', 'This code isn\'t a work ticket of your workshop. Check the ticket, or type its number.'));
});

it('moves the whole batch to the next phase and goes back to the Scan page (AC-4)', function () {
    scanBatch($this, $this->batches['B-0147'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('scan.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '4 units of Denim Jacket · Indigo S moved to Finishing.']);

    $b147 = $this->batches['B-0147']->fresh();
    $move = $b147->moves()->latest('id')->first();

    expect([$b147->number, $b147->units, $b147->phase_id, $b147->phase_since->toDateString()])->toBe([147, 4, $this->finishing->id, '2026-10-30'])
        ->and([$move->from_label, $move->to_label, $move->units, $move->scanned, $move->user_id])->toBe(['Sewing', 'Finishing', 4, true, $this->user->id])
        ->and(ProductionBatch::query()->where('split_from_id', $b147->id)->exists())->toBeFalse();

    scanBatch($this, $this->batches['B-0150'])->assertSessionHasNoErrors();
    expect($this->batches['B-0150']->fresh()->phase_id)->toBe($this->sewing->id);
});

it('puts the batch in stock at the last phase, as Progress does (AC-4)', function () {
    scanBatch($this, $this->batches['B-0148'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('scan.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '4 units of Denim Jacket · Indigo M put in stock.']);

    $restock = ProductRestock::query()->sole();
    $b148 = $this->batches['B-0148']->fresh();

    expect(shelfByColour($this->jacket)['Indigo']['M'])->toBe(4)
        ->and([$restock->reference, $restock->note, $restock->units])->toBe(['B-0148', 'From production', 4])
        ->and($b148->status)->toBe(ProductionBatchStatus::InStock)
        ->and($b148->moves()->latest('id')->first()->scanned)->toBeTrue();
});

it('puts a batch in stock straight from In cutting when the workshop has no phase of its own (AC-4)', function () {
    ProductionBatch::query()->whereNotNull('phase_id')->get()->each(fn (ProductionBatch $batch) => $batch->forceFill(['phase_id' => null])->save());
    ProductionPhase::query()->delete();

    expect(scanLookup($this, 'B-0150')->json('confirm'))->toMatchArray(['from' => 'cutting', 'to' => 'stock', 'to_name' => 'Put in stock']);
    scanBatch($this, $this->batches['B-0150'])->assertSessionHasNoErrors();
    expect($this->batches['B-0150']->fresh()->status)->toBe(ProductionBatchStatus::InStock);
});

it('moves nothing when the batch changed since the confirm (AC-6)', function (Closure $meanwhile, string $batch, array $sent, string $key, string $message) {
    $confirm = scanLookup($this, $batch)->json('confirm');
    $meanwhile($this);
    $before = ProductionBatch::query()->orderBy('id')->get(['id', 'units', 'phase_id', 'status'])->toArray();
    $moves = ProductionBatchMove::query()->count();

    actingAs($this->user)->post(route('scan.batches.store', $this->batches[$batch]), array_merge([
        'from' => $confirm['from'], 'to' => $confirm['to'], 'units' => $confirm['units'],
    ], $sent))->assertSessionHasErrors([$key => $message]);

    expect(ProductionBatch::query()->orderBy('id')->get(['id', 'units', 'phase_id', 'status'])->toArray())->toBe($before)
        ->and(ProductionBatchMove::query()->count())->toBe($moves);
})->with([
    'moved on meanwhile' => [
        fn (object $test) => actingAs($test->user)->post(route('progress.batches.moves.store', $test->batches['B-0147']), [
            'from_phase' => $test->sewing->id, 'to_phase' => $test->finishing->id, 'units' => 4,
        ]),
        'B-0147', [], 'moved', 'B-0147 has moved since you scanned it: it is now in Finishing. Scan it again to move it on.',
    ],
    'part put in stock meanwhile' => [
        fn (object $test) => actingAs($test->user)->post(route('progress.batches.stock', $test->batches['B-0148']), ['from_phase' => $test->finishing->id, 'units' => 1]),
        'B-0148', [], 'moved', 'B-0148 has moved since you scanned it: it is now in Finishing. Scan it again to move it on.',
    ],
    'a phase added after its phase' => [
        fn () => ProductionPhase::factory()->create(['name' => 'Quality check', 'position' => 3]),
        'B-0148', [], 'phases', 'The phases have changed since you scanned. B-0148 now goes to Quality check.',
    ],
    'its product deleted' => [
        fn (object $test) => $test->jacket->delete(),
        'B-0148', [], 'moved', 'Denim Jacket was deleted, so B-0148 can\'t be put in stock.',
    ],
    'put in stock meanwhile' => [
        fn (object $test) => actingAs($test->user)->post(route('progress.batches.stock', $test->batches['B-0148']), ['from_phase' => $test->finishing->id, 'units' => 4]),
        'B-0148', [], 'moved', 'B-0148 was put in stock on 30 Oct 2026. Nothing left to do.',
    ],
    'a move back asked for' => [
        fn () => null,
        'B-0147', ['to' => 'cutting'], 'phases', 'The phases have changed since you scanned. B-0147 now goes to Finishing.',
    ],
]);

it('warns when the batch moved less than 2 minutes ago, naming who moved it (AC-6)', function () {
    scanBatch($this, $this->batches['B-0147'])->assertSessionHasNoErrors();

    $this->travel(12)->seconds();
    expect(scanLookup($this, 'B-0147')->json('confirm.warning'))
        ->toBe("B-0147 moved to Finishing 12 seconds ago ({$this->user->name}). Scanning again moves it on to Put in stock.");

    $this->travel(107)->seconds();
    expect(scanLookup($this, 'B-0147')->json('confirm.warning'))
        ->toBe("B-0147 moved to Finishing 1 minute ago ({$this->user->name}). Scanning again moves it on to Put in stock.");

    $this->travel(2)->seconds();
    expect(scanLookup($this, 'B-0147')->json('confirm.warning'))->toBeNull();
});

it('lists this member\'s scans of today, newest first (AC-3)', function () {
    $this->travelTo(now()->setTime(9, 0));
    scanBatch($this, $this->batches['B-0150'])->assertSessionHasNoErrors();
    $this->travelTo(now()->setTime(9, 5));
    scanBatch($this, $this->batches['B-0148'])->assertSessionHasNoErrors();

    // Not listed: a move from Progress, someone else's scan, and yesterday's scan.
    actingAs($this->user)->post(route('progress.batches.moves.store', $this->batches['B-0146']), [
        'from_phase' => $this->sewing->id, 'to_phase' => $this->finishing->id, 'units' => 5,
    ])->assertSessionHasNoErrors();
    $other = User::factory()->create(['workshop_id' => $this->user->workshop_id]);
    actingAs($other)->post(route('scan.batches.store', $this->batches['B-0149']), ['from' => $this->sewing->id, 'to' => $this->finishing->id, 'units' => 6])
        ->assertSessionHasNoErrors();
    ProductionBatchMove::query()->where('scanned', true)->where('user_id', $this->user->id)->oldest('id')->first()
        ->forceFill(['created_at' => now()->subDay()])->save();
    scanBatch($this, $this->batches['B-0147'])->assertSessionHasNoErrors();

    actingAs($this->user)->get(route('scan.index'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('scan/index')
        ->where('confirm', null)
        ->where('refusal', null)
        ->where('scans_today', [
            ['number' => 'B-0147', 'product' => 'Denim Jacket', 'to' => 'Finishing', 'at' => now()->toIso8601String()],
            ['number' => 'B-0148', 'product' => 'Denim Jacket', 'to' => 'Put in stock', 'at' => now()->toIso8601String()],
        ]));
});

it('opens the Scan page with the ticket\'s confirm from its QR link (AC-5)', function () {
    $code = $this->batches['B-0147']->fresh()->ticket_code;

    actingAs($this->user)->get(route('scan.ticket', $code))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('scan/index')
        ->where('confirm.number', 'B-0147')
        ->where('confirm.to_name', 'Finishing')
        ->where('refusal', null));
});

it('sends a guest to sign in and straight back to the ticket (AC-5)', function () {
    $code = $this->batches['B-0147']->fresh()->ticket_code;

    $this->get(route('scan.ticket', $code))->assertRedirect(route('login'));
    $this->post(route('login.store'), ['email' => $this->user->email, 'password' => 'password'])
        ->assertRedirect(route('scan.ticket', $code));
});
