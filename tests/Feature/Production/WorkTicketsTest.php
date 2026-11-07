<?php

use App\Enums\ProductionBatchStatus;
use App\Enums\TicketSize;
use App\Models\ProductionBatch;
use App\Models\Workshop;
use App\Models\WorkshopLabelSetting;
use App\Services\Production\TicketQr;
use App\Services\Workshops\CurrentWorkshop;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The work tickets and their print page (INV-15-b, AC-1, AC-2)
|--------------------------------------------------------------------------
|
| `GET progress/tickets?batches[]=…` renders the page that prints the tickets of some batches. A ticket names no client, no phone
| number and no price. The card's batches, on 30 Oct 2026: B-0147 holds 4 Denim Jacket Indigo S in Sewing, started 23 Oct 2026.
*/

beforeEach(function () {
    $example = progressExample($this);
    $this->user = $example['user'];
    $this->jacket = $example['jacket'];
    $this->tote = $example['tote'];
    $this->sewing = $example['sewing'];
    $this->finishing = $example['finishing'];
    $this->batches = $example['batches'];
});

/** @param  list<ProductionBatch>  $batches */
function printTickets(object $test, array $batches)
{
    return actingAs($test->user)->get(route('progress.tickets', ['batches' => array_map(fn (ProductionBatch $batch): int => $batch->id, $batches)]));
}

it('prints a ticket with the batch, its variant, units, start, SKU and path, and nothing about a client or a price (AC-1)', function () {
    $b147 = $this->batches['B-0147'];

    printTickets($this, [$b147])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('progress/tickets')
        ->where('size', 'a4_8')
        ->has('tickets', 1)
        ->where('tickets.0', fn ($ticket): bool => array_keys($ticket->all()) === ['id', 'number', 'product', 'variant', 'units', 'started_on', 'sku', 'split_from', 'path', 'current', 'printed_on', 'qr'])
        ->where('tickets.0.id', $b147->id)
        ->where('tickets.0.number', 'B-0147')
        ->where('tickets.0.product', 'Denim Jacket')
        ->where('tickets.0.variant', 'Indigo · S')
        ->where('tickets.0.units', 4)
        ->where('tickets.0.started_on', '2026-10-23')
        ->where('tickets.0.sku', 'PRD-JKT-001-IND-S')
        ->where('tickets.0.split_from', null)
        ->where('tickets.0.path', ['In cutting', 'Sewing', 'Finishing', 'Put in stock'])
        ->where('tickets.0.current', 1)
        ->where('tickets.0.printed_on', '2026-10-30')
        ->where('tickets.0.qr', app(TicketQr::class)->of($b147->fresh())));
});

it('ticks In cutting for a batch still being cut, and prints the tickets in the order asked', function () {
    printTickets($this, [$this->batches['B-0150'], $this->batches['B-0148']])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('tickets.0.number', 'B-0150')
        ->where('tickets.0.current', 0)
        ->where('tickets.1.number', 'B-0148')
        ->where('tickets.1.current', 2));
});

it('prints "Split from B-0149" on a split batch, and its own QR code', function () {
    actingAs($this->user)->post(route('progress.batches.moves.store', $this->batches['B-0149']), [
        'from_phase' => $this->sewing->id, 'to_phase' => $this->finishing->id, 'units' => 4,
    ])->assertSessionHasNoErrors();
    $split = ProductionBatch::query()->where('number', 155)->sole();

    printTickets($this, [$split])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('tickets.0.number', 'B-0155')
        ->where('tickets.0.split_from', 'B-0149')
        ->where('tickets.0.started_on', '2026-10-23')
        ->where('tickets.0.units', 4)
        ->where('tickets.0.qr', app(TicketQr::class)->of($split)));
});

it('prints a one-size product with its colour only', function () {
    $batch = ProductionBatch::factory()->create([
        'product_id' => $this->tote->id, 'colour_id' => demoColour('CAM')->id,
        'size_id' => array_key_first(sizeIds(['One size' => 0], 'one_size')), 'units' => 3,
    ]);

    printTickets($this, [$batch])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('tickets.0.variant', 'Camel')
        ->where('tickets.0.sku', 'PRD-TOT-004-CAM'));
});

it('prints what the batch holds now: 3 units after 1 of 4 is put in stock (AC-1)', function () {
    actingAs($this->user)->post(route('progress.batches.stock', $this->batches['B-0148']), ['from_phase' => $this->finishing->id, 'units' => 1])
        ->assertSessionHasNoErrors();

    printTickets($this, [$this->batches['B-0148']])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('tickets.0.units', 3));
});

it('leaves out a batch that left Progress', function () {
    $this->batches['B-0150']->forceFill(['status' => ProductionBatchStatus::Undone, 'undone_at' => now()])->save();

    printTickets($this, [$this->batches['B-0150'], $this->batches['B-0147']])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->has('tickets', 1)
        ->where('tickets.0.number', 'B-0147'));
});

it('prints in the ticket size of Settings › Labels (AC-7)', function () {
    CurrentWorkshop::run($this->user->workshop_id, fn () => WorkshopLabelSetting::query()->create(['ticket_size' => TicketSize::Label100x60]));

    printTickets($this, [$this->batches['B-0147']])->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('size', 'label_100x60'));
});

it('refuses no batch or more than 50, and treats another workshop\'s batch as one that doesn\'t exist', function () {
    actingAs($this->user)->get(route('progress.tickets'))->assertSessionHasErrors('batches');
    actingAs($this->user)->get(route('progress.tickets', ['batches' => range(1, 51)]))->assertSessionHasErrors('batches');

    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): ProductionBatch => ProductionBatch::factory()->create());

    printTickets($this, [$this->batches['B-0147'], $theirs])->assertNotFound();
    actingAs($this->user)->get(route('progress.tickets', ['batches' => [999999]]))->assertNotFound();
});

it('sends guests to sign in', function () {
    $this->get(route('progress.tickets', ['batches' => [$this->batches['B-0147']->id]]))->assertRedirect(route('login'));
});

it('draws exactly the dark modules of the ticket link, with a 4-module margin', function () {
    $batch = $this->batches['B-0147']->fresh();
    $qr = app(TicketQr::class);
    $link = $qr->link($batch);
    $drawn = $qr->of($batch);

    expect($link)->toBe(route('scan.ticket', ['code' => $batch->ticket_code]))
        ->and($link)->toEndWith('/scan/t/'.$batch->ticket_code);

    $matrix = Encoder::encode($link, ErrorCorrectionLevel::M(), Encoder::DEFAULT_BYTE_MODE_ENCODING, null, false)->getMatrix();
    $dark = [];

    for ($y = 0; $y < $matrix->getWidth(); $y++) {
        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            if ($matrix->get($x, $y) === 1) {
                $dark[] = ($x + 4).','.($y + 4);
            }
        }
    }

    preg_match_all('/M(\d+) (\d+)h(\d+)v1h-\3z/', $drawn['path'], $runs, PREG_SET_ORDER);
    $cells = [];

    foreach ($runs as [, $x, $y, $length]) {
        for ($i = 0; $i < (int) $length; $i++) {
            $cells[] = ((int) $x + $i).','.$y;
        }
    }

    expect($drawn['size'])->toBe($matrix->getWidth() + 8)
        ->and(implode('', array_map(fn (array $run): string => $run[0], $runs)))->toBe($drawn['path'])
        ->and($cells)->toBe($dark)
        ->and($qr->of($batch))->toBe($drawn);
});
