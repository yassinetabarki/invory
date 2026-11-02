<?php

use App\Enums\ProductionBatchStatus;
use App\Models\ProductionBatch;
use App\Models\ProductionPhase;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Settings › Production phases on the server (INV-5-b, AC-1)
|--------------------------------------------------------------------------
|
| The workshop's own phases between In cutting and Put in stock: add, rename, Move up / Move down, remove. A phase that holds units
| keeps its place and can't be removed. Each workshop has its own; a new one has none.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/** The workshop's phases as [name => position], in path order. */
function phaseNames(): array
{
    return ProductionPhase::query()->inOrder()->pluck('position', 'name')->all();
}

/** A batch of `$units` units in production in the phase. */
function holdIn(ProductionPhase $phase, int $units): ProductionBatch
{
    return ProductionBatch::factory()->create(['units' => $units, 'phase_id' => $phase->id]);
}

it('sends a guest to the login page', function (string $method, string $route) {
    $this->{$method}(route($route, [1]))->assertRedirect(route('login'));
})->with([
    'list' => ['get', 'settings.production-phases.index'],
    'add' => ['post', 'settings.production-phases.store'],
    'rename' => ['put', 'settings.production-phases.update'],
    'remove' => ['delete', 'settings.production-phases.destroy'],
    'move' => ['patch', 'settings.production-phases.position'],
]);

it('starts with no phase, then adds Sewing and Finishing at the end, with the toast (AC-1)', function () {
    actingAs($this->user)->get(route('settings.production-phases.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/production-phases')->where('phases', []));

    actingAs($this->user)->post(route('settings.production-phases.store'), ['name' => 'Sewing'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Phase added.']);
    actingAs($this->user)->post(route('settings.production-phases.store'), ['name' => '  Finishing  '])->assertSessionHasNoErrors();

    expect(phaseNames())->toBe(['Sewing' => 1, 'Finishing' => 2]);
});

it('lists each phase with what it holds and, when it holds units, why it can\'t be moved or removed (AC-1)', function () {
    $sewing = ProductionPhase::factory()->create(['name' => 'Sewing', 'position' => 1]);
    ProductionPhase::factory()->create(['name' => 'Finishing', 'position' => 2]);
    holdIn($sewing, 6);
    holdIn($sewing, 9);
    ProductionBatch::factory()->create(['units' => 4, 'phase_id' => $sewing->id, 'status' => ProductionBatchStatus::InStock]);

    actingAs($this->user)->get(route('settings.production-phases.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('phases.0', [
                'id' => $sewing->id, 'name' => 'Sewing', 'position' => 1, 'units' => 15,
                'refusal' => "Sewing holds 15 units, so it can't be moved or removed. Move them on or back first.",
            ])
            ->where('phases.1.units', 0)
            ->where('phases.1.refusal', null));
});

it('refuses a name with the card\'s sentence, and adds nothing', function (string $name, string $message) {
    ProductionPhase::factory()->create(['name' => 'Sewing', 'position' => 1]);

    actingAs($this->user)->post(route('settings.production-phases.store'), ['name' => $name])->assertSessionHasErrors(['name' => $message]);

    expect(phaseNames())->toBe(['Sewing' => 1]);
})->with([
    'empty' => ['', "Enter the phase's name."],
    'spaces only' => ['   ', "Enter the phase's name."],
    '25 characters' => [str_repeat('a', 25), 'Keep the name to 24 characters or fewer.'],
    'taken, other capitals' => ['SEWING', 'You already have a phase named "Sewing".'],
    'In cutting' => ['in cutting', 'In cutting and Put in stock are already there. Choose another name.'],
    'Put in stock' => ['Put In Stock', 'In cutting and Put in stock are already there. Choose another name.'],
]);

it('accepts a 24-character name and refuses a seventh phase', function () {
    foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
        ProductionPhase::factory()->create(['name' => $name]);
    }

    actingAs($this->user)->post(route('settings.production-phases.store'), ['name' => str_repeat('z', 24)])->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('settings.production-phases.store'), ['name' => 'Seventh'])
        ->assertSessionHasErrors(['name' => 'You can have up to 6 phases.']);

    expect(ProductionPhase::query()->count())->toBe(6);
});

it('renames a phase, also one that holds units, to its own name in other capitals but not to another\'s', function () {
    $sewing = ProductionPhase::factory()->create(['name' => 'Sewing', 'position' => 1]);
    ProductionPhase::factory()->create(['name' => 'Finishing', 'position' => 2]);
    holdIn($sewing, 3);

    actingAs($this->user)->put(route('settings.production-phases.update', $sewing), ['name' => 'SEWING'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Phase updated.']);
    actingAs($this->user)->put(route('settings.production-phases.update', $sewing), ['name' => 'finishing'])
        ->assertSessionHasErrors(['name' => 'You already have a phase named "Finishing".']);

    expect($sewing->fresh()->name)->toBe('SEWING');
});

it('moves an empty phase up and down, swapping it with its neighbour (AC-1)', function () {
    [$sewing, $finishing, $check] = collect(['Sewing', 'Finishing', 'Quality check'])
        ->map(fn (string $name, int $i) => ProductionPhase::factory()->create(['name' => $name, 'position' => $i + 1]))->all();

    actingAs($this->user)->patch(route('settings.production-phases.position', $check), ['direction' => 'up'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Phases reordered.']);
    expect(phaseNames())->toBe(['Sewing' => 1, 'Quality check' => 2, 'Finishing' => 3]);

    actingAs($this->user)->patch(route('settings.production-phases.position', $sewing), ['direction' => 'down'])->assertSessionHasNoErrors();
    expect(phaseNames())->toBe(['Quality check' => 1, 'Sewing' => 2, 'Finishing' => 3]);

    // The first can't go up, the last can't go down: nothing changes.
    actingAs($this->user)->patch(route('settings.production-phases.position', $check), ['direction' => 'up'])->assertSessionHasNoErrors();
    actingAs($this->user)->patch(route('settings.production-phases.position', $finishing), ['direction' => 'down'])->assertSessionHasNoErrors();
    expect(phaseNames())->toBe(['Quality check' => 1, 'Sewing' => 2, 'Finishing' => 3]);
});

it('keeps a phase that holds units in its place: neither it nor its neighbour can swap with it', function () {
    $sewing = ProductionPhase::factory()->create(['name' => 'Sewing', 'position' => 1]);
    $finishing = ProductionPhase::factory()->create(['name' => 'Finishing', 'position' => 2]);
    holdIn($sewing, 15);
    $message = "Sewing holds 15 units, so it can't be moved or removed. Move them on or back first.";

    actingAs($this->user)->patch(route('settings.production-phases.position', $sewing), ['direction' => 'down'])
        ->assertSessionHasErrors(['phase' => $message]);
    actingAs($this->user)->patch(route('settings.production-phases.position', $finishing), ['direction' => 'up'])
        ->assertSessionHasErrors(['phase' => $message]);

    expect(phaseNames())->toBe(['Sewing' => 1, 'Finishing' => 2]);
});

it('removes an empty phase and closes the gap, with the toast (AC-1)', function () {
    [$sewing, $check, $finishing] = collect(['Sewing', 'Quality check', 'Finishing'])
        ->map(fn (string $name, int $i) => ProductionPhase::factory()->create(['name' => $name, 'position' => $i + 1]))->all();
    // A batch that went through Quality check and was put in stock doesn't hold it.
    ProductionBatch::factory()->create(['phase_id' => null, 'status' => ProductionBatchStatus::InStock]);

    actingAs($this->user)->delete(route('settings.production-phases.destroy', $check))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Phase removed.']);

    expect(phaseNames())->toBe(['Sewing' => 1, 'Finishing' => 2])
        ->and(ProductionPhase::query()->find($check->id))->toBeNull();
});

it('refuses to remove a phase that holds units, even one that filled up since the page', function () {
    $check = ProductionPhase::factory()->create(['name' => 'Quality check', 'position' => 1]);
    holdIn($check, 2);

    actingAs($this->user)->delete(route('settings.production-phases.destroy', $check))
        ->assertSessionHasErrors(['phase' => "Quality check now holds 2 units, so it can't be removed."]);

    expect(phaseNames())->toBe(['Quality check' => 1]);
});

it('keeps each workshop\'s phases apart and answers 404 for another workshop\'s phase', function () {
    $theirs = CurrentWorkshop::run(Workshop::factory()->create(), fn (): ProductionPhase => ProductionPhase::factory()->create(['name' => 'Sewing']));

    actingAs($this->user)->post(route('settings.production-phases.store'), ['name' => 'Sewing'])->assertSessionHasNoErrors();
    actingAs($this->user)->put(route('settings.production-phases.update', $theirs->id), ['name' => 'Mine'])->assertNotFound();
    actingAs($this->user)->delete(route('settings.production-phases.destroy', $theirs->id))->assertNotFound();
    actingAs($this->user)->patch(route('settings.production-phases.position', $theirs->id), ['direction' => 'up'])->assertNotFound();

    expect($theirs->fresh()->name)->toBe('Sewing')
        ->and(phaseNames())->toBe(['Sewing' => 1]);
});
