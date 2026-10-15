<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| S-44-d, AC-5: the same SKU in two workshops (ADR-0013 decision 6, R3)
|--------------------------------------------------------------------------
|
| The demo workshop already uses "MAT-DEN-012" and "PRD-JKT-001". Sami, in his own workshop, can use them too; inside his workshop a SKU
| is still used once, among non-deleted items. One dataset runs the rules over materials and products.
*/

beforeEach(function () {
    prepareDemo($this);
    $this->sami = User::factory()->create(['workshop_id' => Workshop::factory()->create()->id]);
});

/**
 * What a material and a product share, so one test covers both. Payloads are built here, before Sami signs in: the helpers read the demo's records.
 *
 * @return array{table: string, sku: string, toast: string, model: class-string<Material|Product>, create: Closure(string): array<string, mixed>, post: Closure(array<string, mixed>): mixed, put: Closure(Material|Product, array<string, mixed>): mixed, delete: Closure(Material|Product): mixed}
 */
function skuSubject(string $kind): array
{
    $material = $kind === 'material';
    // A new product needs no composition: Sami has no materials, and an empty one is allowed.
    $productPayload = ['category' => 'apparel', 'status' => 'draft', 'labor_cost' => '8', 'overhead_cost' => '2', 'components' => []];

    return [
        'table' => $material ? 'materials' : 'products',
        'sku' => $material ? 'MAT-DEN-012' : 'PRD-JKT-001',
        'toast' => $material ? 'Material created.' : 'Product created.',
        'model' => $material ? Material::class : Product::class,
        'create' => static fn (string $sku): array => $material
            ? validMaterialPayload(['sku' => $sku, 'name' => 'Denim of Sami'])
            : [...$productPayload, 'sku' => $sku, 'name' => 'Jacket of Sami'],
        'post' => fn (array $payload) => test()->post(route($material ? 'materials.store' : 'products.store'), $payload),
        'put' => fn (Material|Product $item, array $payload) => test()->put(route($material ? 'materials.update' : 'products.update', $item), $payload),
        'delete' => fn (Material|Product $item) => test()->delete(route($material ? 'materials.destroy' : 'products.destroy', $item)),
    ];
}

it('lets the same SKU live in two workshops, once in each, and frees it when the item is deleted (AC-5)', function (string $kind) {
    $subject = skuSubject($kind);
    $sku = $subject['sku'];
    $payload = $subject['create']($sku);
    $message = 'The sku has already been taken.';

    $this->actingAs($this->sami);

    // Another workshop's SKU is free: Sami creates his item with it.
    $subject['post']($payload)->assertSessionHasNoErrors()->assertInertiaFlash('toast', ['type' => 'success', 'message' => $subject['toast']]);

    expect(DB::table($subject['table'])->where('sku', $sku)->count())->toBe(2)
        ->and(DB::table($subject['table'])->where('sku', $sku)->distinct()->count('workshop_id'))->toBe(2);

    // Inside his workshop it is taken, with today's message.
    $subject['post']($payload)->assertSessionHasErrors(['sku' => $message]);

    $item = $subject['model']::query()->where('sku', $sku)->firstOrFail();
    expect(DB::table($subject['table'])->where('sku', $sku)->count())->toBe(2);

    // Keeping its own SKU while editing passes.
    $subject['put']($item, [...$payload, 'name' => 'Renamed'])->assertSessionHasNoErrors();
    expect($item->fresh()->name)->toBe('Renamed');

    // A deleted item no longer holds its SKU.
    $subject['delete']($item)->assertSessionHasNoErrors();
    $subject['post']($payload)->assertSessionHasNoErrors();

    expect($subject['model']::query()->where('sku', $sku)->count())->toBe(1);
})->with(['material', 'product']);
