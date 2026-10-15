<?php

use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| S-44-d: the policies check the workshop again, and answer Not Found (ADR-0013 decision 5)
|--------------------------------------------------------------------------
|
| Through HTTP the workshop scope already hides another workshop's record (CrossWorkshopAccessTest); this is the second layer. The
| records are built in each workshop's own context and handed to the Gate as objects, so the scope plays no part. This file lives in
| tests/Feature, not tests/Unit, because only Feature tests get the database in this repository's Pest configuration.
*/

it('allows a member of the same workshop and answers Not Found for another workshop', function (string $model, string $ability) {
    [$mine, $theirs] = [Workshop::factory()->create(), Workshop::factory()->create()];
    $user = User::factory()->create(['workshop_id' => $mine->id]);

    $own = CurrentWorkshop::run($mine, fn () => $model::factory()->create());
    $foreign = CurrentWorkshop::run($theirs, fn () => $model::factory()->create());

    $allowed = Gate::forUser($user)->inspect($ability, $own);
    $denied = Gate::forUser($user)->inspect($ability, $foreign);

    expect($allowed->allowed())->toBeTrue()
        ->and($denied->allowed())->toBeFalse()
        ->and($denied->status())->toBe(404);
})->with(function () {
    foreach ([Material::class => ['view', 'update', 'delete'], Product::class => ['view', 'update', 'delete'], Sale::class => ['update', 'delete'], Order::class => ['update', 'delete']] as $model => $abilities) {
        foreach ($abilities as $ability) {
            yield class_basename($model)." {$ability}" => [$model, $ability];
        }
    }
});

it('sends a guest to the login page', function () {
    $this->get(route('materials.index'))->assertRedirect(route('login'));
});
