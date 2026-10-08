<?php

use App\Models\Material;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('soft-deletes a material, redirects to the index and flashes a success toast', function () {
    $material = Material::factory()->create();

    actingAs($this->user)
        ->delete(route('materials.destroy', $material))
        ->assertRedirect(route('materials.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Material deleted.']);

    assertSoftDeleted($material);
    expect(Material::query()->count())->toBe(0)
        ->and(Material::withTrashed()->count())->toBe(1);
});

it('returns 404 for a soft-deleted material', function (string $method, string $route) {
    $material = Material::factory()->create();
    $material->delete();

    $request = in_array($method, ['put', 'post'], true)
        ? actingAs($this->user)->{$method}(route($route, $material), validMaterialPayload(['sku' => 'FRESH']))
        : actingAs($this->user)->{$method}(route($route, $material));

    $request->assertNotFound();
})->with([
    'show' => ['get', 'materials.show'],
    'edit' => ['get', 'materials.edit'],
    'update' => ['put', 'materials.update'],
    'destroy' => ['delete', 'materials.destroy'],
]);

it('returns 404 for an unknown material', function () {
    actingAs($this->user)->get(route('materials.show', 999999))->assertNotFound();
});
