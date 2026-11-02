<?php

use App\Enums\ClientSource;
use App\Models\Client;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Add client (INV-4-c, AC-1, AC-3)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** Ines Mrad, as the window sends her. */
function inesMrad(array $overrides = []): array
{
    return ['name' => 'Ines Mrad', 'phone' => '20456789', 'city' => 'Monastir', 'source' => 'instagram', 'source_other' => '', ...$overrides];
}

it('adds the client, back where the window was opened, with "Client created." and the new client in the flash', function () {
    $response = actingAs($this->user)->from(route('clients.index'))->post(route('clients.store'), inesMrad())
        ->assertRedirect(route('clients.index'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Client created.']);

    $ines = Client::query()->sole();

    $response->assertInertiaFlash('client_created', [
        'id' => $ines->id, 'name' => 'Ines Mrad', 'phone' => '20 456 789', 'city' => 'Monastir',
        'source' => 'instagram', 'source_label' => 'Instagram', 'source_other' => null,
    ]);

    expect($ines->only(['name', 'phone', 'city', 'source', 'user_id', 'workshop_id']))->toBe([
        'name' => 'Ines Mrad', 'phone' => '20 456 789', 'city' => 'Monastir', 'source' => ClientSource::Instagram,
        'user_id' => $this->user->id, 'workshop_id' => $this->user->workshop_id,
    ]);
});

it('refuses a field with its message under it and adds no one', function (array $override, string $field, string $message) {
    actingAs($this->user)->from(route('clients.index'))->post(route('clients.store'), inesMrad($override))
        ->assertRedirect(route('clients.index'))
        ->assertInertiaFlashMissing('toast');

    expect(session('errors')->keys())->toBe([$field])
        ->and(session('errors')->get($field))->toBe([$message])
        ->and(Client::query()->count())->toBe(0);
})->with([
    'name empty' => [['name' => ''], 'name', "Enter the client's name."],
    'name of 101 characters' => [['name' => str_repeat('a', 101)], 'name', 'Keep the name to 100 characters or fewer.'],
    'phone empty' => [['phone' => ''], 'phone', "Enter the client's phone."],
    'phone of 7 digits' => [['phone' => '2234567'], 'phone', 'Enter 8 digits, such as 22 345 678, or a number abroad starting with +.'],
    'city empty' => [['city' => ''], 'city', "Enter the client's city."],
    'city of 61 characters' => [['city' => str_repeat('a', 61)], 'city', 'Keep the city to 60 characters or fewer.'],
    'other source of 41 characters' => [['source' => 'other', 'source_other' => str_repeat('a', 41)], 'source_other', 'Keep it to 40 characters or fewer.'],
]);

it('refuses a phone another client has, however typed, and says whose it is', function (string $typed) {
    $amira = demoClient('Amira Ben Salah');

    actingAs($this->user)->from(route('clients.index'))->post(route('clients.store'), inesMrad(['phone' => $typed]))
        ->assertRedirect(route('clients.index'))
        ->assertSessionHasErrors(['phone' => '22 345 678 is already the phone of Amira Ben Salah.', 'phone_taken_by' => (string) $amira->id]);

    expect(Client::query()->count())->toBe(1);
})->with(['22 345 678', '+216 22345678']);

it('adds a client whose name is already used, and a phone another workshop has', function () {
    demoClient('Amira Ben Salah');
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => Client::factory()->create(['phone' => '20 456 789']));

    actingAs($this->user)->post(route('clients.store'), inesMrad(['name' => 'Amira Ben Salah', 'phone' => '20 456 789']))
        ->assertSessionHasNoErrors();

    expect(Client::query()->where('name', 'Amira Ben Salah')->count())->toBe(2);
});

it('sends a guest to sign in', function () {
    $this->post(route('clients.store'), inesMrad())->assertRedirect(route('login'));

    expect(Client::query()->withoutGlobalScopes()->count())->toBe(0);
});
