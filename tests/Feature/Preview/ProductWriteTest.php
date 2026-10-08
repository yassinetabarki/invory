<?php

use App\Models\Material;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Create / edit / delete a product on the preview fixture (ADR-0006, S-13c)
|--------------------------------------------------------------------------
|
| The writes go through the final FormRequests (validation is real: nested composition errors, photo type and size, SKU
| uniqueness against the fixture), then nothing is saved: an `info` toast says so.
| Rules: `invory-costing-domain` skill, "Validation rules".
*/

beforeEach(fn () => $this->user = preparePreview($this));

const PRODUCT_SAVED_TOAST = ['type' => 'info', 'message' => "Preview: the product passed validation but wasn't saved."];

/** One composition line on a seeded material, with overrides. */
function componentRow(string $sku, array $overrides = []): array
{
    return ['material_id' => previewMaterial($sku)->id, 'quantity' => '1.5', 'waste_percent' => '5', 'notes' => 'Body', ...$overrides];
}

/** The fields every invalid-input row can break: [overrides, the error key]. Components are replaced as a whole. */
dataset('invalidProductInput', [
    'missing sku' => [['sku' => null], 'sku'],
    'sku too long' => [['sku' => str_repeat('a', 65)], 'sku'],
    'array sku' => [['sku' => ['x']], 'sku'],
    'missing name' => [['name' => null], 'name'],
    'name too long' => [['name' => str_repeat('a', 151)], 'name'],
    'missing category' => [['category' => null], 'category'],
    'unknown category' => [['category' => 'bananas'], 'category'],
    'missing status' => [['status' => null], 'status'],
    'unknown status' => [['status' => 'sold_out'], 'status'],
    'description too long' => [['description' => str_repeat('a', 2001)], 'description'],
    'missing labor cost' => [['labor_cost' => null], 'labor_cost'],
    'negative labor cost' => [['labor_cost' => '-1'], 'labor_cost'],
    'labor cost with 5 decimals' => [['labor_cost' => '1.12345'], 'labor_cost'],
    'text labor cost' => [['labor_cost' => 'abc'], 'labor_cost'],
    'exponent labor cost' => [['labor_cost' => '1e3'], 'labor_cost'],
    'labor cost over the column' => [['labor_cost' => '100000000'], 'labor_cost'],
    'missing overhead cost' => [['overhead_cost' => null], 'overhead_cost'],
    'negative overhead cost' => [['overhead_cost' => '-0.0001'], 'overhead_cost'],
    'overhead cost with 5 decimals' => [['overhead_cost' => '2.00001'], 'overhead_cost'],
    'negative selling price' => [['selling_price' => '-1'], 'selling_price'],
    'selling price with 5 decimals' => [['selling_price' => '9.12345'], 'selling_price'],
    'text selling price' => [['selling_price' => 'free'], 'selling_price'],
    'selling price over the column' => [['selling_price' => '100000000'], 'selling_price'],
    'photo that is not a file' => [['photo' => 'a-path.jpg'], 'photo'],
    'photo that is an svg' => [['photo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')], 'photo'],
    'photo that is a pdf' => [['photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')], 'photo'],
    'photo that is a gif' => [['photo' => UploadedFile::fake()->image('anim.gif')], 'photo'],
    'a text file sent as a jpg' => [['photo' => realUpload('fake.jpg', 'not an image', 'image/jpeg')], 'photo'],
    'an svg sent as a jpg' => [['photo' => realUpload('logo.jpg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/jpeg')], 'photo'],
    'photo over 4096 KB' => [['photo' => UploadedFile::fake()->image('big.jpg')->size(4097)], 'photo'],
    'remove_photo not a boolean' => [['remove_photo' => 'maybe'], 'remove_photo'],
    'components that are not an array' => [['components' => 'abc'], 'components'],
    'a component without a material' => [['components' => [['quantity' => '1', 'waste_percent' => '0']]], 'components.0.material_id'],
    'a component with a text material' => [['components' => [['material_id' => 'abc', 'quantity' => '1']]], 'components.0.material_id'],
    'a component with an unknown material' => [['components' => [['material_id' => 999999, 'quantity' => '1']]], 'components.0.material_id'],
    'a component without a quantity' => [['components' => [['material_id' => 1, 'quantity' => null]]], 'components.0.quantity'],
    'a component with a zero quantity' => [['components' => [['material_id' => 1, 'quantity' => '0']]], 'components.0.quantity'],
    'a component with a negative quantity' => [['components' => [['material_id' => 1, 'quantity' => '-2']]], 'components.0.quantity'],
    'a component with 5 decimals' => [['components' => [['material_id' => 1, 'quantity' => '0.00001']]], 'components.0.quantity'],
    'a component with a text quantity' => [['components' => [['material_id' => 1, 'quantity' => 'lots']]], 'components.0.quantity'],
    'a component with waste at 100' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '100']]], 'components.0.waste_percent'],
    'a component with waste over 100' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '150']]], 'components.0.waste_percent'],
    'a component with negative waste' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '-1']]], 'components.0.waste_percent'],
    'a component with 3 waste decimals' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '5.123']]], 'components.0.waste_percent'],
    'a component with a text waste' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => 'high']]], 'components.0.waste_percent'],
    'a component with notes over 100 characters' => [['components' => [['material_id' => 1, 'quantity' => '1', 'notes' => str_repeat('a', 101)]]], 'components.0.notes'],
]);

dataset('validProductBoundaries', [
    'free product' => [['labor_cost' => '0', 'overhead_cost' => '0.0000', 'selling_price' => '0']],
    'no selling price' => [['selling_price' => null]],
    'an empty selling price' => [['selling_price' => '']],
    'the biggest costs' => [['labor_cost' => '99999999.9999', 'overhead_cost' => '99999999.9999', 'selling_price' => '99999999.9999']],
    'four decimals' => [['labor_cost' => '1.2345', 'selling_price' => '12.3456']],
    'no description' => [['description' => null]],
    'longest strings' => [['sku' => str_repeat('a', 64), 'name' => str_repeat('a', 150), 'description' => str_repeat('a', 2000)]],
    'every category and status' => [['category' => 'jewelry', 'status' => 'archived']],
    'an empty composition' => [['components' => []]],
    'no composition key' => [['components' => null]],
    'waste just under 100' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '99.99']]]],
    'no waste' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => null]]]],
    'zero waste' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '0']]]],
    'no notes' => [['components' => [['material_id' => 1, 'quantity' => '1', 'waste_percent' => '5', 'notes' => null]]]],
    'the tiniest quantity' => [['components' => [['material_id' => 1, 'quantity' => '0.0001']]]],
    'a big quantity' => [['components' => [['material_id' => 1, 'quantity' => '9999999999.9999']]]],
    'notes of 100 characters' => [['components' => [['material_id' => 1, 'quantity' => '1', 'notes' => str_repeat('a', 100)]]]],
]);

/** `material_id => 1` in the datasets stands for a real seeded material: swap it in before sending. */
function withRealMaterials(array $payload): array
{
    $firstMaterial = Material::query()->orderBy('id')->firstOrFail()->id;

    if (isset($payload['components']) && is_array($payload['components'])) {
        $payload['components'] = array_map(
            fn (array $row): array => ($row['material_id'] ?? null) === 1 ? [...$row, 'material_id' => $firstMaterial] : $row,
            $payload['components'],
        );
    }

    return $payload;
}

describe('store', function () {
    it('validates, saves nothing and flashes the preview toast', function () {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload())
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_SAVED_TOAST);
    });

    it('rejects invalid input with an error on the field and no toast', function (array $override, string $field) {
        actingAs($this->user)
            ->from(route('products.create'))
            ->post(route('products.store'), withRealMaterials(validProductPayload($override)))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors($field)
            ->assertInertiaFlashMissing('toast');
    })->with('invalidProductInput');

    it('accepts the boundary values', function (array $override) {
        actingAs($this->user)
            ->post(route('products.store'), withRealMaterials(validProductPayload($override)))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_SAVED_TOAST);
    })->with('validProductBoundaries');

    it('accepts a photo of each allowed type, up to 4096 KB', function (UploadedFile $photo) {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['photo' => $photo]))
            ->assertSessionHasNoErrors();
    })->with([
        'jpg' => fn () => UploadedFile::fake()->image('a.jpg'),
        'jpeg' => fn () => UploadedFile::fake()->image('a.jpeg'),
        'png' => fn () => UploadedFile::fake()->image('a.png'),
        'webp' => fn () => UploadedFile::fake()->image('a.webp'),
        'exactly 4096 KB' => fn () => UploadedFile::fake()->image('a.jpg')->size(4096),
    ]);

    it('accepts remove_photo as a boolean or its numeric forms', function (mixed $value) {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['remove_photo' => $value]))
            ->assertSessionHasNoErrors();
    })->with([true, false, 1, 0, '1', '0']);

    it('rejects a duplicate of a fixture SKU', function (string $sku) {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['sku' => $sku]))
            ->assertSessionHasErrors(['sku' => 'The sku has already been taken.']);
    })->with(['the first' => 'PRD-JKT-001', 'the last' => 'PRD-BLT-007', 'a draft' => 'PRD-JKT-006']);

    it('puts a duplicate material on the line that repeats it', function () {
        $denim = previewMaterial('MAT-DEN-012')->id;

        $response = actingAs($this->user)->post(route('products.store'), validProductPayload(['components' => [
            componentRow('MAT-THR-PES'),
            componentRow('MAT-DEN-012'),
            componentRow('MAT-LBL-WOV'),
            ['material_id' => $denim, 'quantity' => '2', 'waste_percent' => '0'],
        ]]));

        $response->assertSessionHasErrors(['components.1.material_id', 'components.3.material_id'])
            ->assertSessionDoesntHaveErrors(['components.0.material_id', 'components.2.material_id']);
    });

    it('puts a nested error on the right row only', function () {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['components' => [
                componentRow('MAT-DEN-012'),
                componentRow('MAT-THR-PES'),
                componentRow('MAT-LBL-WOV', ['quantity' => '0', 'waste_percent' => '100']),
            ]]))
            ->assertSessionHasErrors(['components.2.quantity', 'components.2.waste_percent'])
            ->assertSessionDoesntHaveErrors([
                'components.0.material_id', 'components.0.quantity', 'components.0.waste_percent',
                'components.1.material_id', 'components.1.quantity', 'components.1.waste_percent',
                'components.2.material_id',
            ]);
    });

    it('reports every broken field at once', function () {
        actingAs($this->user)
            ->post(route('products.store'), ['sku' => '', 'components' => [['material_id' => 'x']]])
            ->assertSessionHasErrors(['sku', 'name', 'category', 'status', 'labor_cost', 'overhead_cost', 'components.0.material_id', 'components.0.quantity']);
    });

    it('rejects a soft-deleted material in the composition', function () {
        $material = previewMaterial('MAT-LAC-120');
        $material->delete();

        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['components' => [
                ['material_id' => $material->id, 'quantity' => '1', 'waste_percent' => '0'],
            ]]))
            ->assertSessionHasErrors('components.0.material_id');
    });

    it('accepts a multipart request with a photo and a nested composition', function () {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload([
                'photo' => UploadedFile::fake()->image('shirt.png', 600, 800),
                'components' => [componentRow('MAT-DEN-012'), componentRow('MAT-THR-PES', ['quantity' => '90'])],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'))
            ->assertInertiaFlash('toast', PRODUCT_SAVED_TOAST);
    });

    it('accepts a multipart request without a composition (a body cannot carry an empty array)', function () {
        $payload = validProductPayload(['photo' => UploadedFile::fake()->image('shirt.png')]);
        unset($payload['components']);

        actingAs($this->user)->post(route('products.store'), $payload)->assertSessionHasNoErrors();
    });
});

describe('update', function () {
    it('validates, saves nothing and flashes the preview toast', function () {
        actingAs($this->user)
            ->put(route('products.update', 4), validProductPayload())
            ->assertRedirect(route('products.show', 4))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_SAVED_TOAST);
    });

    it('rejects invalid input with an error on the field and no toast', function (array $override, string $field) {
        actingAs($this->user)
            ->from(route('products.edit', 4))
            ->put(route('products.update', 4), withRealMaterials(validProductPayload($override)))
            ->assertRedirect(route('products.edit', 4))
            ->assertSessionHasErrors($field)
            ->assertInertiaFlashMissing('toast');
    })->with('invalidProductInput');

    it('accepts the boundary values', function (array $override) {
        actingAs($this->user)
            ->put(route('products.update', 4), withRealMaterials(validProductPayload($override)))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_SAVED_TOAST);
    })->with('validProductBoundaries');

    it('keeps the SKU of the product being edited valid', function () {
        actingAs($this->user)
            ->put(route('products.update', 1), validProductPayload(['sku' => 'PRD-JKT-001']))
            ->assertSessionHasNoErrors();
    });

    it('refuses the SKU of another product', function () {
        actingAs($this->user)
            ->put(route('products.update', 1), validProductPayload(['sku' => 'PRD-TSH-002']))
            ->assertSessionHasErrors(['sku' => 'The sku has already been taken.']);
    });

    it('accepts a multipart update with a photo', function () {
        actingAs($this->user)
            ->post(route('products.update', 1), [...validProductPayload(['photo' => UploadedFile::fake()->image('x.webp')]), '_method' => 'PUT'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.show', 1));
    });

    it('answers 404 for an unknown product before it validates anything', function () {
        actingAs($this->user)->put(route('products.update', 8), ['sku' => ''])
            ->assertNotFound()
            ->assertSessionHasNoErrors();
    });
});

describe('destroy', function () {
    it('deletes nothing and says so', function () {
        actingAs($this->user)
            ->delete(route('products.destroy', 3))
            ->assertRedirect(route('products.index'))
            ->assertInertiaFlash('toast', ['type' => 'info', 'message' => 'Preview: nothing was deleted.']);

        // The product is still served.
        actingAs($this->user)->get(route('products.show', 3))->assertOk();
    });

    it('answers 404 for an unknown product', function () {
        actingAs($this->user)->delete(route('products.destroy', 8))->assertNotFound();
    });
});
