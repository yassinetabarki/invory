<?php

use App\Enums\TicketSize;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopLabelSetting;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Settings › Labels: the ticket size (INV-15-g, AC-7)
|--------------------------------------------------------------------------
|
| How the workshop's work tickets print: an A4 sheet of 8 (the default) or one per 100 × 60 mm label. One row per workshop, made on
| first save. INV-9 adds its label settings to the same page and row.
*/

it('shows the A4 sheet of 8 until the workshop saves another size', function () {
    actingAs(User::factory()->create())->get(route('settings.labels.edit'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/labels')
        ->where('ticket_size', 'a4_8')
        ->where('ticket_sizes', [
            ['value' => 'a4_8', 'label' => 'A4 sheet, 8 tickets (2 × 4)'],
            ['value' => 'label_100x60', 'label' => '100 × 60 mm, one per label (label printer)'],
        ]));
});

it('saves the ticket size for the whole workshop, in one row', function () {
    $user = User::factory()->create();
    $colleague = User::factory()->create(['workshop_id' => $user->workshop_id]);

    actingAs($user)->put(route('settings.labels.update'), ['ticket_size' => 'label_100x60'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings.labels.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Label settings saved.']);
    actingAs($colleague)->put(route('settings.labels.update'), ['ticket_size' => 'a4_8'])->assertSessionHasNoErrors();
    actingAs($colleague)->put(route('settings.labels.update'), ['ticket_size' => 'label_100x60'])->assertSessionHasNoErrors();

    $rows = CurrentWorkshop::run($user->workshop_id, fn () => WorkshopLabelSetting::query()->get());
    expect($rows)->toHaveCount(1)->and($rows->sole()->ticket_size)->toBe(TicketSize::Label100x60);

    actingAs($user)->get(route('settings.labels.edit'))->assertInertia(fn (AssertableInertia $page) => $page->where('ticket_size', 'label_100x60'));
    actingAs(User::factory()->create(['workshop_id' => Workshop::factory()->create()->id]))->get(route('settings.labels.edit'))->assertInertia(fn (AssertableInertia $page) => $page->where('ticket_size', 'a4_8'));
});

it('refuses a size that isn\'t in the list', function (mixed $size) {
    actingAs(User::factory()->create())->put(route('settings.labels.update'), ['ticket_size' => $size])
        ->assertSessionHasErrors(['ticket_size' => 'Choose a ticket size.']);
})->with(['a3', '', null]);

it('sends guests to sign in', function () {
    $this->get(route('settings.labels.edit'))->assertRedirect(route('login'));
    $this->put(route('settings.labels.update'), ['ticket_size' => 'a4_8'])->assertRedirect(route('login'));
});

it('keeps one row per workshop, and on PostgreSQL only the two sizes', function (Closure $row, string $postgres, ?string $sqlite) {
    if ($sqlite === null && DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The CHECK is PostgreSQL-only.');
    }

    $workshop = Workshop::factory()->create();
    CurrentWorkshop::run($workshop, fn () => WorkshopLabelSetting::query()->create(['ticket_size' => TicketSize::A4Sheet]));

    expect(fn () => DB::transaction(fn () => DB::table('workshop_label_settings')->insert(array_merge([
        'workshop_id' => $workshop->id, 'ticket_size' => 'a4_8', 'created_at' => now(), 'updated_at' => now(),
    ], $row()))))->toThrow(QueryException::class, DB::getDriverName() === 'pgsql' ? $postgres : $sqlite);
})->with([
    'a second row' => [fn (): array => [], 'workshop_label_settings_workshop_id_unique', 'workshop_label_settings.workshop_id'],
    'another size' => [fn (): array => ['ticket_size' => 'a3', 'workshop_id' => Workshop::factory()->create()->id], 'workshop_label_settings_ticket_size_check', null],
]);
