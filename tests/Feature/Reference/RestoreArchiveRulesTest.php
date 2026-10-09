<?php

use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\Reference\ReferenceVersion;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Restore and archive rules of the reference rows (F-27, ADR-0008)
|--------------------------------------------------------------------------
|
| Names are unique among the active rows, so restoring an archived row is refused with an error on `name` while another active
| row has the same name. Archiving an archived row changes nothing. Both rules run for the three tables.
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(fn () => Carbon::setTestNow());

/** Did the response carry a validation error on `name`? */
function refusesName(TestResponse $response): bool
{
    try {
        $response->assertSessionHasErrors('name');
    } catch (AssertionFailedError) {
        return false;
    }

    return true;
}

/**
 * @return array<string, array{array<string, mixed>}>
 */
dataset('referenceKinds', [
    'units' => [[
        'model' => Unit::class,
        'route' => 'settings.units',
        'noun' => 'unit',
        'restored' => 'Unit restored.',
        'archived' => 'Unit archived.',
        'store' => fn (string $name): array => ['code' => 'new_one', 'name' => $name, 'dimension' => 'length', 'factor_to_base' => '0.0254'],
    ]],
    'material categories' => [[
        'model' => MaterialCategory::class,
        'route' => 'settings.material-categories',
        'noun' => 'material category',
        'restored' => 'Category restored.',
        'archived' => 'Category archived.',
        'store' => fn (string $name): array => ['code' => 'new_one', 'name' => $name],
    ]],
    'product categories' => [[
        'model' => ProductCategory::class,
        'route' => 'settings.product-categories',
        'noun' => 'product category',
        'restored' => 'Category restored.',
        'archived' => 'Category archived.',
        'store' => fn (string $name): array => ['code' => 'new_one', 'name' => $name],
    ]],
]);

describe('restore', function () {
    it('is refused with an error on name while an active row has the same name', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Elastic']);
        $version = app(ReferenceVersion::class)->current();

        actingAs($this->user)
            ->from(route("{$kind['route']}.index"))
            ->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertRedirect(route("{$kind['route']}.index"))
            ->assertSessionHasErrors(['name' => "An active {$kind['noun']} is already named \"Elastic\". Rename one of them first."]);

        // Nothing changed: still archived, no new reference version.
        expect($archived->refresh()->archived_at)->not->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('referenceKinds');

    it('sends no success toast when it is refused', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Elastic']);

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertInertiaFlashMissing('toast');
    })->with('referenceKinds');

    it('is allowed when no active row has the name, and archived namesakes do not count', function (array $kind) {
        $kind['model']::factory()->archived()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Elastic']);
        $before = app(ReferenceVersion::class)->current();

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $kind['restored']]);

        expect($archived->refresh()->archived_at)->toBeNull()
            ->and(app(ReferenceVersion::class)->current())->not->toBe($before);
    })->with('referenceKinds');

    it('is allowed after the active row is renamed', function (array $kind) {
        $active = $kind['model']::factory()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Elastic']);

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertSessionHasErrors('name');

        actingAs($this->user)->put(route("{$kind['route']}.update", $active->id), ['name' => 'Elastic band'])
            ->assertSessionHasNoErrors();

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $kind['restored']]);

        expect($archived->refresh()->archived_at)->toBeNull();
    })->with('referenceKinds');

    it('is allowed after the active row is archived', function (array $kind) {
        $active = $kind['model']::factory()->create(['name' => 'Elastic']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Elastic']);

        actingAs($this->user)->patch(route("{$kind['route']}.archive", $active->id))->assertSessionHasNoErrors();
        actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id))->assertSessionHasNoErrors();

        expect($archived->refresh()->archived_at)->toBeNull();
    })->with('referenceKinds');

    it('uses the same case rule as the name uniqueness of create', function (array $kind) {
        $kind['model']::factory()->create(['name' => 'ELASTIC']);
        $kind['model']::factory()->create(['name' => 'PLASTIC']);
        $archived = $kind['model']::factory()->archived()->create(['name' => 'Plastic']);

        // Names are unique ignoring case (F-32): creating "Elastic" is refused (an active "ELASTIC" exists), and so is restoring
        // an archived "Plastic" (an active "PLASTIC" exists). ReferenceNameCaseTest covers the cases one by one.
        $createRefused = refusesName(actingAs($this->user)->post(route("{$kind['route']}.store"), $kind['store']('Elastic')));
        $restoreRefused = refusesName(actingAs($this->user)->patch(route("{$kind['route']}.restore", $archived->id)));

        expect($createRefused)->toBeTrue()
            ->and($restoreRefused)->toBeTrue();
    })->with('referenceKinds');

    it('restoring an active row is a no-op: no error, no write, no new reference version, the success toast as usual (F-30)', function (array $kind) {
        Carbon::setTestNow('2026-10-09 12:00:00');
        $active = $kind['model']::factory()->create(['name' => 'Elastic', 'updated_at' => '2026-01-02 03:04:05']);
        $version = app(ReferenceVersion::class)->current();

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $active->id))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $kind['restored']]);

        expect($active->refresh()->archived_at)->toBeNull()
            ->and($active->updated_at?->toDateTimeString())->toBe('2026-01-02 03:04:05')
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('referenceKinds');

    it('restoring a restored row twice changes nothing the second time', function (array $kind) {
        Carbon::setTestNow('2026-10-09 10:00:00');
        $row = $kind['model']::factory()->archived()->create();

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $row->id))->assertSessionHasNoErrors();

        $version = app(ReferenceVersion::class)->current();
        Carbon::setTestNow('2026-10-09 11:30:00');

        actingAs($this->user)->patch(route("{$kind['route']}.restore", $row->id))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $kind['restored']]);

        expect($row->refresh()->updated_at?->toDateTimeString())->toBe('2026-10-09 10:00:00')
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('referenceKinds');

    it('still answers 404 for an unknown row', function (array $kind) {
        actingAs($this->user)->patch(route("{$kind['route']}.restore", 999999))->assertNotFound();
    })->with('referenceKinds');

    it('redirects guests to the login page', function (array $kind) {
        $archived = $kind['model']::factory()->archived()->create();

        $this->patch(route("{$kind['route']}.restore", $archived->id))->assertRedirect(route('login'));
    })->with('referenceKinds');
});

describe('archive', function () {
    it('archives an active row and stamps it', function (array $kind) {
        Carbon::setTestNow('2026-10-09 10:00:00');
        $active = $kind['model']::factory()->create();
        $before = app(ReferenceVersion::class)->current();

        actingAs($this->user)->patch(route("{$kind['route']}.archive", $active->id))
            ->assertRedirect()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $kind['archived']]);

        expect($active->refresh()->archived_at?->toDateTimeString())->toBe('2026-10-09 10:00:00')
            ->and(app(ReferenceVersion::class)->current())->not->toBe($before);
    })->with('referenceKinds');

    it('twice changes nothing the second time: same archived_at, same reference version, same success toast', function (array $kind) {
        Carbon::setTestNow('2026-10-09 10:00:00');
        $row = $kind['model']::factory()->create();

        actingAs($this->user)->patch(route("{$kind['route']}.archive", $row->id))->assertRedirect();

        $stamp = $row->refresh()->archived_at?->toDateTimeString();
        $version = app(ReferenceVersion::class)->current();

        Carbon::setTestNow('2026-10-09 11:30:00');

        actingAs($this->user)->patch(route("{$kind['route']}.archive", $row->id))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => $kind['archived']]);

        expect($row->refresh()->archived_at?->toDateTimeString())->toBe($stamp)->toBe('2026-10-09 10:00:00')
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('referenceKinds');

    it('keeps the original timestamp of a row that was archived long before', function (array $kind) {
        Carbon::setTestNow('2026-10-09 12:00:00');
        $row = $kind['model']::factory()->create(['archived_at' => '2026-01-02 03:04:05']);
        $version = app(ReferenceVersion::class)->current();

        actingAs($this->user)->patch(route("{$kind['route']}.archive", $row->id))->assertRedirect();

        expect($row->refresh()->archived_at?->toDateTimeString())->toBe('2026-01-02 03:04:05')
            ->and(app(ReferenceVersion::class)->current())->toBe($version);
    })->with('referenceKinds');
});
