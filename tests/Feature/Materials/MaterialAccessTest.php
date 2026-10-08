<?php

use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

dataset('materialRoutes', [
    'index' => ['get', 'materials.index', false],
    'create' => ['get', 'materials.create', false],
    'store' => ['post', 'materials.store', false],
    'show' => ['get', 'materials.show', true],
    'edit' => ['get', 'materials.edit', true],
    'update' => ['put', 'materials.update', true],
    'destroy' => ['delete', 'materials.destroy', true],
]);

it('redirects guests to login on every materials route', function (string $method, string $route, bool $needsMaterial) {
    $parameters = $needsMaterial ? [Material::factory()->create()] : [];

    $this->{$method}(route($route, $parameters))->assertRedirect(route('login'));
})->with('materialRoutes');

it('blocks unverified users on every materials route', function (string $method, string $route, bool $needsMaterial) {
    $parameters = $needsMaterial ? [Material::factory()->create()] : [];

    actingAs(User::factory()->unverified()->create())
        ->{$method}(route($route, $parameters))
        ->assertRedirect(route('verification.notice'));
})->with('materialRoutes');

it('registers the seven resource routes with the expected URIs', function () {
    $expected = [
        'materials.index' => ['GET', 'materials'],
        'materials.create' => ['GET', 'materials/create'],
        'materials.store' => ['POST', 'materials'],
        'materials.show' => ['GET', 'materials/{material}'],
        'materials.edit' => ['GET', 'materials/{material}/edit'],
        'materials.update' => ['PUT', 'materials/{material}'],
        'materials.destroy' => ['DELETE', 'materials/{material}'],
    ];

    foreach ($expected as $name => [$method, $uri]) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->uri())->toBe($uri)
            ->and($route->methods())->toContain($method)
            ->and($route->gatherMiddleware())->toContain('auth', 'verified');
    }
});
