<?php

use App\Enums\MaterialCostMode;
use App\Models\Colour;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| Add a missing colour to a material, from where it is used (INV-21-p, AC-13)
|--------------------------------------------------------------------------
|
| Denim 12oz kept per colour, cost per colour: Indigo 250 m at 8.5000, Black 40 m at 9.2000. "Add Light wash to Denim 12oz" adds the row at 0,
| writes no ledger entry, and its toast carries the action that opens the Restock sheet on that colour, whose data is the JSON of
| `materials.restock-sheet`.
*/

it('adds the colour at 0 with no entry and a toast that restocks it, asks for its cost with cost per colour, and refuses the rest', function () {
    $user = User::factory()->create();
    $ids = Colour::query()->whereIn('code', ['IND', 'BLK', 'LWH'])->pluck('id', 'code')->all();
    $theirs = Workshop::factory()->create();
    $theirLightWash = CurrentWorkshop::run($theirs, fn (): int => Colour::query()->where('code', 'LWH')->value('id'));
    $theirDenim = CurrentWorkshop::run($theirs, fn (): Material => Material::factory()->perColour([['IND', '1.0000']])->create());

    $denim = Material::factory()->perColour([['IND', '250.0000', '8.5'], ['BLK', '40.0000', '9.2']], MaterialCostMode::PerColour)->create(['name' => 'Denim 12oz', 'unit' => 'm']);
    $buttons = Material::factory()->perColour([['IND', '10.0000']])->create(['name' => 'Metal shank button']);
    $thread = Material::factory()->create(['name' => 'Polyester thread']);
    $entries = StockMovement::query()->count();

    // Guests are sent to log in; nothing is added.
    post(route('materials.colours.store', $denim), ['colour_id' => $ids['LWH'], 'unit_cost' => '9'])->assertRedirect(route('login'));
    get(route('materials.restock-sheet', $denim))->assertRedirect(route('login'));

    // With cost per colour the cost is required, "Light wash: Enter a unit cost." like the form's "Black: Enter a unit cost.", and a number.
    foreach (['missing' => [null, 'Enter a unit cost.'], 'empty' => ['', 'Enter a unit cost.'], 'negative' => ['-1', 'Enter a number from 0, with up to 4 decimals.'], 'five decimals' => ['9.00001', 'Enter a number from 0, with up to 4 decimals.']] as [$cost, $message]) {
        actingAs($user)->post(route('materials.colours.store', $denim), ['colour_id' => $ids['LWH'], 'unit_cost' => $cost])
            ->assertSessionHasErrors(['unit_cost' => "Light wash: {$message}"]);
    }

    expect(MaterialColour::query()->where('material_id', $denim->id)->count())->toBe(2);

    // The colour is added at 0 and its cost; the total, the lowest cost and the ledger don't move; the toast restocks that colour.
    actingAs($user)->post(route('materials.colours.store', $denim), ['colour_id' => $ids['LWH'], 'unit_cost' => '9'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Light wash added to Denim 12oz.',
            'action' => ['label' => 'Restock Light wash', 'restock' => ['material_id' => $denim->id, 'colour_id' => $ids['LWH'], 'material_name' => 'Denim 12oz']],
        ]);

    $row = MaterialColour::query()->where('material_id', $denim->id)->where('colour_id', $ids['LWH'])->firstOrFail();
    $denim->refresh();

    expect([$row->quantity_on_hand, $row->unit_cost])->toBe(['0.0000', '9.0000'])
        ->and([$denim->quantity_on_hand, $denim->unit_cost])->toBe(['290.0000', '8.5000'])
        ->and(StockMovement::query()->count())->toBe($entries);

    // With the same cost for every colour the row has none, whatever was sent.
    actingAs($user)->post(route('materials.colours.store', $buttons), ['colour_id' => $ids['LWH'], 'unit_cost' => '5'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Light wash added to Metal shank button.',
            'action' => ['label' => 'Restock Light wash', 'restock' => ['material_id' => $buttons->id, 'colour_id' => $ids['LWH'], 'material_name' => 'Metal shank button']],
        ]);

    expect(MaterialColour::query()->where('material_id', $buttons->id)->where('colour_id', $ids['LWH'])->value('unit_cost'))->toBeNull();

    // Refused on `colour_id`, nothing added: a colour already on the material, one stock, a colour of another workshop, no colour at all.
    actingAs($user)->post(route('materials.colours.store', $denim), ['colour_id' => $ids['LWH'], 'unit_cost' => '9'])
        ->assertSessionHasErrors(['colour_id' => 'That colour is already on Denim 12oz.']);
    actingAs($user)->post(route('materials.colours.store', $thread), ['colour_id' => $ids['LWH']])
        ->assertSessionHasErrors(['colour_id' => 'Polyester thread keeps one stock for all colours.']);
    actingAs($user)->post(route('materials.colours.store', $buttons), ['colour_id' => $theirLightWash])
        ->assertSessionHasErrors(['colour_id' => 'Choose a colour.']);
    actingAs($user)->post(route('materials.colours.store', $buttons), [])
        ->assertSessionHasErrors(['colour_id' => 'Choose a colour.']);

    expect(MaterialColour::query()->where('material_id', $denim->id)->count())->toBe(3)
        ->and(MaterialColour::query()->where('material_id', $thread->id)->count())->toBe(0)
        ->and(MaterialColour::query()->where('material_id', $buttons->id)->count())->toBe(2);

    // Another workshop's material is a 404, never a 403.
    actingAs($user)->post(route('materials.colours.store', $theirDenim), ['colour_id' => $ids['LWH'], 'unit_cost' => '9'])->assertNotFound();
    actingAs($user)->get(route('materials.restock-sheet', $theirDenim))->assertNotFound();

    // The Restock sheet's data: the material with its colours in Settings > Colours order, the new one at 0 (no lazy loading in tests).
    actingAs($user)->getJson(route('materials.restock-sheet', $denim))
        ->assertOk()
        ->assertJsonPath('material.id', $denim->id)
        ->assertJsonPath('material.unit', 'm')
        ->assertJsonPath('material.colours.*.colour.code', ['BLK', 'IND', 'LWH'])
        ->assertJsonPath('material.colours.2.quantity_on_hand', '0.0000')
        ->assertJsonPath('material.colours.2.unit_cost', '9.0000');
});
