<?php

use App\Http\Requests\Colours\StoreColourRequest;
use App\Http\Requests\Colours\UpdateColourRequest;
use App\Models\Colour;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Settings > Colours on the server (INV-1-b, ADR-0018)
|--------------------------------------------------------------------------
|
| Every factory workshop holds the 20 default colours, so the user's workshop starts with White first, Indigo IND tenth and Forest
| green last. "Used by" counts the non-deleted products that have the colour (INV-1-e).
*/

beforeEach(function () {
    $this->user = User::factory()->create();
});

/** What a valid Add colour form sends. */
function colourPayload(array $override = []): array
{
    return ['code' => 'STW', 'name' => 'Stone wash', 'swatch' => '#B7C3CF', 'position' => '', ...$override];
}

/** The colour of a workshop with this code, whoever is signed in. */
function colourOf(Workshop $workshop, string $code): Colour
{
    return CurrentWorkshop::run($workshop, fn () => Colour::query()->where('code', $code)->firstOrFail());
}

describe('access', function () {
    it('sends a guest to the login page', function (string $method, string $route) {
        $this->{$method}(route($route, [1]))->assertRedirect(route('login'));
    })->with([
        'list' => ['get', 'settings.colours.index'],
        'add' => ['post', 'settings.colours.store'],
        'edit' => ['put', 'settings.colours.update'],
    ]);

    it('lets a member see the list, and answers Not Found for another workshop\'s colour', function () {
        $foreign = colourOf(Workshop::factory()->create(), 'IND');

        actingAs($this->user)->get(route('settings.colours.index'))->assertOk();
        actingAs($this->user)->put(route('settings.colours.update', $foreign->id), ['name' => 'Mine now', 'swatch' => '#000000'])->assertNotFound();

        expect($foreign->refresh()->name)->toBe('Indigo');
    });
});

it('lists every colour of the workshop in one list, in list order, with the agreed shape', function () {
    Workshop::factory()->create();
    // Position 5 is Brown's too: the name puts "Cocoa" after it, and position 6 (Grey) after both.
    Colour::factory()->create(['name' => 'Cocoa', 'code' => 'COC', 'position' => 5]);

    $page = actingAs($this->user)->get(route('settings.colours.index'))->assertOk();

    $page->assertInertia(fn (Assert $page) => $page
        ->component('settings/colours')
        ->has('colours', 21)
        ->has('colours.0', fn (Assert $row) => $row
            ->where('id', fn ($id) => is_int($id))
            ->where('code', 'WHT')
            ->where('name', 'White')
            ->where('swatch', '#FFFFFF')
            ->where('position', 1)
            ->where('usage_count', 0)
            ->etc())
        ->where('colours', fn ($colours) => collect($colours)->pluck('name')->slice(3, 5)->values()->all() === ['Camel', 'Brown', 'Cocoa', 'Grey', 'Charcoal']));

    // 40 colours are one plain list, not pages: no `data`, `links` or `meta` around it.
    Colour::factory()->count(19)->create();

    actingAs($this->user)->get(route('settings.colours.index'))->assertInertia(fn (Assert $page) => $page
        ->has('colours', 40)
        ->missing('colours.data')
        ->missing('links')
        ->missing('meta'));
});

describe('Add colour', function () {
    it('saves the code and swatch in capitals, "#" included, and lists the colour last', function (string $swatch) {
        actingAs($this->user)->post(route('settings.colours.store'), colourPayload(['code' => 'stw', 'swatch' => $swatch]))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Colour created.']);

        $colours = Colour::query()->ordered()->get();

        expect($colours)->toHaveCount(21)
            ->and($colours->last()->only(['code', 'name', 'swatch', 'position']))->toBe(['code' => 'STW', 'name' => 'Stone wash', 'swatch' => '#B7C3CF', 'position' => 21])
            ->and($colours->get(19)->name)->toBe('Forest green');
    })->with([
        'lower case with #' => ['#b7c3cf'],
        'without #' => ['B7C3CF'],
    ]);

    it('refuses a wrong entry with the one message of its field and saves nothing', function (array $override, string $field, string $message) {
        actingAs($this->user)->post(route('settings.colours.store'), colourPayload($override))
            ->assertRedirect()
            ->assertSessionHasErrors([$field => $message]);

        expect(session('errors')->keys())->toBe([$field])
            ->and(session('errors')->get($field))->toBe([$message])
            ->and(DB::table('colours')->count())->toBe(20);
    })->with([
        'an empty name' => [['name' => ''], 'name', "Enter the colour's name."],
        '"indigo" while Indigo exists' => [['name' => 'indigo'], 'name', 'You already have a colour named "Indigo".'],
        'a 33-character name' => [['name' => str_repeat('a', 33)], 'name', 'Keep the name to 32 characters or fewer.'],
        'an empty code' => [['code' => ''], 'code', 'Enter 2 to 4 capital letters or digits, such as IND.'],
        'the code "INDIG"' => [['code' => 'INDIG'], 'code', 'Enter 2 to 4 capital letters or digits, such as IND.'],
        'the code "I-D"' => [['code' => 'I-D'], 'code', 'Enter 2 to 4 capital letters or digits, such as IND.'],
        'the code "IND" while Indigo has it' => [['code' => 'ind'], 'code', 'Code IND is already used by Indigo.'],
        'an empty swatch' => [['swatch' => ''], 'swatch', 'Enter a colour code like #3E4C7E.'],
        'the swatch "blue"' => [['swatch' => 'blue'], 'swatch', 'Enter a colour code like #3E4C7E.'],
        'the swatch "#FFF"' => [['swatch' => '#FFF'], 'swatch', 'Enter a colour code like #3E4C7E.'],
        'the swatch "FFF"' => [['swatch' => 'FFF'], 'swatch', 'Enter a colour code like #3E4C7E.'],
        'the position "2.5"' => [['position' => '2.5'], 'position', 'Enter a whole number from 0 to 32,767, or leave it empty.'],
        'the position "-1"' => [['position' => '-1'], 'position', 'Enter a whole number from 0 to 32,767, or leave it empty.'],
        'the position "40000"' => [['position' => '40000'], 'position', 'Enter a whole number from 0 to 32,767, or leave it empty.'],
    ]);
});

describe('Edit colour', function () {
    it('renames a colour and changes nothing else of it, the code least of all', function () {
        $mine = colourOf($this->user->workshop, 'IND');
        $theirs = colourOf(Workshop::factory()->create(), 'IND');

        actingAs($this->user)->put(route('settings.colours.update', $mine->id), ['code' => 'XYZ', 'name' => 'Indigo blue', 'swatch' => '#3e4c7f', 'position' => ''])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Colour updated.']);

        expect($mine->refresh()->only(['code', 'name', 'swatch', 'position']))->toBe(['code' => 'IND', 'name' => 'Indigo blue', 'swatch' => '#3E4C7F', 'position' => 10])
            ->and($theirs->refresh()->only(['code', 'name', 'swatch']))->toBe(['code' => 'IND', 'name' => 'Indigo', 'swatch' => '#3E4C7E']);
    });

    it('lets a colour take its own name in other capitals and refuses another colour\'s name', function (string $name, ?string $message) {
        $indigo = colourOf($this->user->workshop, 'IND');

        $response = actingAs($this->user)->put(route('settings.colours.update', $indigo->id), ['name' => $name, 'swatch' => '#3E4C7E', 'position' => '12']);

        if ($message === null) {
            $response->assertSessionHasNoErrors();
            expect($indigo->refresh()->only(['name', 'position']))->toBe(['name' => $name, 'position' => 12]);

            return;
        }

        $response->assertSessionHasErrors(['name' => $message]);

        expect(session('errors')->get('name'))->toBe([$message])
            ->and($indigo->refresh()->only(['name', 'position']))->toBe(['name' => 'Indigo', 'position' => 10]);
    })->with([
        'INDIGO' => ['INDIGO', null],
        'black' => ['black', 'You already have a colour named "Black".'],
    ]);
});

it('answers a name or code error, not a 500, when it was taken after validation (a lost race)', function (string $request, array $taken, string $field, string $message) {
    $indigo = colourOf($this->user->workshop, 'IND');
    $rollbacks = 0;
    Event::listen(TransactionRolledBack::class, function () use (&$rollbacks): void {
        $rollbacks++;
    });

    // The clashing colour appears right after the request has validated and before the Action writes.
    app()->afterResolving($request, fn () => DB::table('colours')->insert(Colour::factory()->raw($taken)));

    $response = $request === StoreColourRequest::class
        ? actingAs($this->user)->post(route('settings.colours.store'), colourPayload())
        : actingAs($this->user)->put(route('settings.colours.update', $indigo->id), ['name' => 'Stone wash', 'swatch' => '#3E4C7E', 'position' => '']);

    $response->assertRedirect()->assertSessionHasErrors([$field => $message]);

    expect(session('errors')->get($field))->toBe([$message])
        ->and($rollbacks)->toBe(1)
        ->and(DB::table('colours')->count())->toBe(21)
        ->and($indigo->refresh()->name)->toBe('Indigo');
})->with([
    'a name on Add' => [StoreColourRequest::class, ['code' => 'ZZZ', 'name' => 'STONE WASH'], 'name', 'You already have a colour named "STONE WASH".'],
    'a code on Add' => [StoreColourRequest::class, ['code' => 'STW', 'name' => 'Other'], 'code', 'Code STW is already used by Other.'],
    'a name on Edit' => [UpdateColourRequest::class, ['code' => 'ZZZ', 'name' => 'STONE WASH'], 'name', 'You already have a colour named "STONE WASH".'],
]);
