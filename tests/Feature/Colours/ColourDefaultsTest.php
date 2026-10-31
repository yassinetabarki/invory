<?php

use App\Models\Colour;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| INV-1-a: the 20 default colours of every workshop (AC-1)
|--------------------------------------------------------------------------
|
| RefreshDatabase has already run the migration. The migration test rolls it back and forward again with the migration object: schema
| changes are transactional on SQLite and PostgreSQL, so RefreshDatabase's own transaction undoes them after the test.
*/

/**
 * The UI card's table, written out once more here on purpose: the migration's frozen copy and the app's copy must both equal it.
 *
 * @return list<array{int, string, string, string}> position, name, code, swatch
 */
function expectedDefaultColours(): array
{
    return [
        [1, 'White', 'WHT', '#FFFFFF'], [2, 'Ecru', 'ECR', '#EFE6D2'], [3, 'Beige', 'BEI', '#D8C3A0'], [4, 'Camel', 'CAM', '#B98A57'],
        [5, 'Brown', 'BRN', '#6B4A32'], [6, 'Grey', 'GRY', '#8C8C8C'], [7, 'Charcoal', 'CHA', '#3D3D42'], [8, 'Black', 'BLK', '#1F1F23'],
        [9, 'Navy', 'NAV', '#1F2A44'], [10, 'Indigo', 'IND', '#3E4C7E'], [11, 'Light wash', 'LWH', '#9DB3D1'], [12, 'Lilac', 'LIL', '#B3A2D3'],
        [13, 'Pink', 'PNK', '#E8A9B8'], [14, 'Red', 'RED', '#B8282E'], [15, 'Burgundy', 'BUR', '#6E1F2E'], [16, 'Rust', 'RST', '#B5552B'],
        [17, 'Mustard', 'MUS', '#D4A12A'], [18, 'Khaki', 'KHA', '#A69F74'], [19, 'Olive', 'OLV', '#5F6B3A'], [20, 'Forest green', 'FGR', '#2F5A43'],
    ];
}

/**
 * A workshop's colours in list order, as the rows of {@see expectedDefaultColours()}. Read around the workshop scope on purpose: no one is
 * signed in, so the scope adds nothing, and the workshop is named in the query.
 *
 * @return list<array{int, string, string, string}>
 */
function coloursOfWorkshop(int $workshopId): array
{
    return Colour::query()->where('workshop_id', $workshopId)->ordered()->get()
        ->map(fn (Colour $colour): array => [$colour->position, $colour->name, $colour->code, $colour->swatch])->all();
}

it('writes the 20 defaults into every workshop that exists, in order, and none into a database without a workshop', function (int $workshops) {
    $ids = Workshop::factory()->count($workshops)->create()->pluck('id')->all();

    // On PostgreSQL `product_colours` points at `colours`, so it goes first and comes back last.
    productColoursMigration()->down();
    coloursMigration()->down();

    expect(Schema::hasTable('colours'))->toBeFalse();

    coloursMigration()->up();
    productColoursMigration()->up();

    expect(Colour::query()->count())->toBe(20 * $workshops);

    foreach ($ids as $id) {
        expect(coloursOfWorkshop($id))->toBe(expectedDefaultColours());
    }

    // Each list is its own: both workshops hold an IND, and renaming one changes nothing in the other.
    if ($workshops === 2) {
        Colour::query()->where('workshop_id', $ids[0])->where('code', 'IND')->update(['name' => 'Indigo blue']);

        expect(Colour::query()->where('code', 'IND')->count())->toBe(2)
            ->and(Colour::query()->where('workshop_id', $ids[1])->where('code', 'IND')->value('name'))->toBe('Indigo');
    }
})->with([
    'no workshop' => [0],
    'two workshops' => [2],
]);

it('gives a workshop created by signing up the same 20 colours as a migrated one', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $workshopId = User::query()->where('email', 'test@example.com')->firstOrFail()->workshop_id;

    expect(coloursOfWorkshop($workshopId))->toBe(expectedDefaultColours());
});

it('refuses to save a changed code on an existing colour', function () {
    $colour = Colour::factory()->create(['code' => 'STW']);

    expect(fn () => $colour->update(['code' => 'XYZ']))->toThrow(LogicException::class)
        ->and(DB::table('colours')->where('id', $colour->id)->value('code'))->toBe('STW');
});
