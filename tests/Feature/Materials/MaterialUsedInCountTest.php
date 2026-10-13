<?php

use App\Models\Material;
use App\Models\Product;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| `used_in_count` of a material counts the real compositions (F-29, S-04b)
|--------------------------------------------------------------------------
|
| The number of NON-DELETED products whose composition uses the material, on the list, the page, the form and the dashboard's
| restock list. The lines of a soft-deleted product stay in the table (history, restore) but are not a use.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

/** The count of each material by SKU, from the list page. */
function usedInCounts(): array
{
    $rows = pageProp(actingAs(test()->user)->get(route('materials.index')), 'materials.data');

    return array_combine(array_column($rows, 'sku'), array_column($rows, 'used_in_count'));
}

it('counts the demo compositions on the list', function () {
    $counts = usedInCounts();
    ksort($counts);

    expect($counts)->toBe([
        'MAT-BTN-017' => 3, 'MAT-CAN-010' => 2, 'MAT-DEN-012' => 2, 'MAT-JRS-180' => 1, 'MAT-LAC-120' => 1, 'MAT-LBL-WOV' => 6,
        'MAT-LIN-VIS' => 1, 'MAT-LTH-NAP' => 2, 'MAT-RVT-009' => 3, 'MAT-SOL-042' => 1, 'MAT-THR-PES' => 6, 'MAT-ZIP-018' => 2,
    ]);
});

it('gives the same count on the list, the page and the form', function () {
    $material = demoMaterial('MAT-BTN-017');

    expect(actingAs($this->user)->get(route('materials.show', $material))->inertiaProps('material.used_in_count'))->toBe(3)
        ->and(actingAs($this->user)->get(route('materials.edit', $material))->inertiaProps('material.used_in_count'))->toBe(3)
        ->and(usedInCounts()['MAT-BTN-017'])->toBe(3);
});

it('does not count the lines of a soft-deleted product, and counts them again once it is restored', function () {
    $jacket = demoProduct('PRD-JKT-001');
    $jacket->delete();

    // The Denim Jacket used the denim, the button, the thread and the label.
    expect(usedInCounts())->toMatchArray(['MAT-DEN-012' => 1, 'MAT-BTN-017' => 2, 'MAT-THR-PES' => 5, 'MAT-LBL-WOV' => 5]);

    $jacket->restore();

    expect(usedInCounts())->toMatchArray(['MAT-DEN-012' => 2, 'MAT-BTN-017' => 3]);
});

it('follows the products as they are created, changed and deleted through the pages', function () {
    $zip = demoMaterial('MAT-ZIP-018');

    actingAs($this->user)->post(route('products.store'), validProductPayload(['components' => [
        ['material_id' => $zip->id, 'quantity' => '1', 'waste_percent' => '0'],
    ]]))->assertSessionHasNoErrors();

    $created = demoProduct('PRD-NEW-100');

    expect(usedInCounts()['MAT-ZIP-018'])->toBe(3);

    // Move the line to another material.
    actingAs($this->user)->put(route('products.update', $created), validProductPayload(['components' => [
        ['material_id' => demoMaterial('MAT-LAC-120')->id, 'quantity' => '1', 'waste_percent' => '0'],
    ]]))->assertSessionHasNoErrors();

    expect(usedInCounts())->toMatchArray(['MAT-ZIP-018' => 2, 'MAT-LAC-120' => 2]);

    actingAs($this->user)->delete(route('products.destroy', $created))->assertRedirect();

    expect(usedInCounts()['MAT-LAC-120'])->toBe(1);
});

it('is zero for a material no product uses', function () {
    $unused = Material::factory()->create(['sku' => 'ZZZ-UNUSED', 'name' => 'Zzz unused']);
    $product = Product::factory()->withComponents([['material' => $unused, 'quantity' => '1']])->create();

    expect(actingAs($this->user)->get(route('materials.show', Material::factory()->create()))->inertiaProps('material.used_in_count'))->toBe(0)
        ->and(actingAs($this->user)->get(route('materials.show', $unused))->inertiaProps('material.used_in_count'))->toBe(1);

    $product->delete();

    expect(actingAs($this->user)->get(route('materials.show', $unused))->inertiaProps('material.used_in_count'))->toBe(0);
});

it('counts a product once per material, however many lines the others have', function () {
    // Two products on the same material: 2. (A material is on one line of a product: database-unique.)
    $material = Material::factory()->create();
    Product::factory()->count(2)->withComponents([['material' => $material, 'quantity' => '1']])->create();

    expect(actingAs($this->user)->get(route('materials.show', $material))->inertiaProps('material.used_in_count'))->toBe(2);
});

it('sends the count with the restock list of the dashboard', function () {
    $response = actingAs($this->user)->get(route('dashboard'));

    expect(loadedPageProp($response, 'needs_restock.*.sku'))->toBe(['MAT-SOL-042', 'MAT-JRS-180', 'MAT-BTN-017', 'MAT-LBL-WOV'])
        ->and(loadedPageProp($response, 'needs_restock.*.used_in_count'))->toBe([1, 1, 3, 6]);

    demoProduct('PRD-SNK-003')->delete();

    expect(loadedPageProp(actingAs($this->user)->get(route('dashboard')), 'needs_restock.0.used_in_count'))->toBe(0);
});

it('answers 404 for a soft-deleted material even though the route loads the count with it', function () {
    $material = demoMaterial('MAT-LAC-120');
    $material->delete();

    actingAs($this->user)->get(route('materials.show', $material->id))->assertNotFound();
    actingAs($this->user)->get(route('materials.edit', $material->id))->assertNotFound();
});

it('keeps writing a material fine when the route loaded the count with it', function () {
    $material = demoMaterial('MAT-BTN-017');

    actingAs($this->user)->put(route('materials.update', $material), validMaterialPayload([
        'sku' => $material->sku, 'name' => 'Renamed button', 'category' => 'fastener', 'unit' => 'pc',
    ]))->assertSessionHasNoErrors();

    expect($material->refresh()->name)->toBe('Renamed button')
        ->and(actingAs($this->user)->get(route('materials.show', $material))->assertOk()->inertiaProps('material.used_in_count'))->toBe(3);
});
