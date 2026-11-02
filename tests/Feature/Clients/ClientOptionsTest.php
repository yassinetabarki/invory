<?php

use App\Models\Client;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The client picker's search (INV-4-c, AC-5, AC-6, AC-7; ADR-0009 rule 5)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->user = prepareDemo($this);

    foreach (array_keys(DEMO_CLIENTS) as $name) {
        demoClient($name);
    }
});

it('offers the clients A → Z as ClientRef, matching a name, phone digits or a city', function (array $query, array $names) {
    $options = actingAs($this->user)->getJson(route('clients.options', $query))->assertOk()->json();

    expect(array_column($options, 'name'))->toBe($names);
})->with([
    'nothing typed' => [[], ['Amira Ben Salah', 'Boutique Lina', 'Mehdi Jaziri', 'Nour Gharbi', 'Rania Hamdi', 'Yassine Trabelsi']],
    'a name' => [['search' => 'amira'], ['Amira Ben Salah']],
    'phone digits' => [['search' => '29 111'], ['Mehdi Jaziri']],
    'a city' => [['search' => 'tunis'], ['Boutique Lina', 'Yassine Trabelsi']],
    'the limit' => [['limit' => 2], ['Amira Ben Salah', 'Boutique Lina']],
    'no match' => [['search' => 'Ines Mrad'], []],
]);

it('sends the shape of a ClientRef, the chosen client along even when she does not match, and never another workshop\'s', function () {
    $yassine = Client::query()->where('name', 'Yassine Trabelsi')->sole();
    CurrentWorkshop::run(Workshop::factory()->create(), fn () => Client::factory()->create(['name' => 'Amira Other']));

    $options = actingAs($this->user)->getJson(route('clients.options', ['search' => 'amira', 'include' => [$yassine->id]]))->json();

    expect(array_column($options, 'name'))->toBe(['Amira Ben Salah', 'Yassine Trabelsi'])
        ->and(array_keys($options[0]))->toBe(['id', 'name', 'phone', 'city', 'source', 'source_label', 'source_other'])
        ->and($options[0])->toMatchArray(['phone' => '22 345 678', 'city' => 'Sousse', 'source' => 'instagram', 'source_label' => 'Instagram', 'source_other' => null]);
});

it('refuses a limit above 50 and sends a guest to sign in', function () {
    actingAs($this->user)->getJson(route('clients.options', ['limit' => 51]))->assertUnprocessable();

    auth()->logout();

    $this->get(route('clients.options'))->assertRedirect(route('login'));
});
