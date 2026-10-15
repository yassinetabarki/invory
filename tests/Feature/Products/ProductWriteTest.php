<?php

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComponent;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Create / edit / delete a product on the real tables (S-04b)
|--------------------------------------------------------------------------
|
| The writes go through the final FormRequests (nested composition errors, photo type and size, SKU uniqueness among non-deleted
| products, archived categories) and the S-04a Actions, then redirect with a real `success` toast. A submitted photo is validated
| and stored on the public disk (S-15-BE, faked here; PhotoWritesTest holds the disk rules). Rules: `invory-costing-domain` skill, "Validation rules".
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    Storage::fake('public');
});

const PRODUCT_CREATED_TOAST = ['type' => 'success', 'message' => 'Product created.'];
const PRODUCT_UPDATED_TOAST = ['type' => 'success', 'message' => 'Product updated.'];
const PRODUCT_DELETED_TOAST = ['type' => 'success', 'message' => 'Product deleted.'];

/** A policy that refuses everything, to prove every route asks it. */
class DenyAllProductPolicy extends ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Product $product): Response
    {
        return Response::deny();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Product $product): Response
    {
        return Response::deny();
    }

    public function delete(User $user, Product $product): Response
    {
        return Response::deny();
    }
}

/** One composition line on a seeded material, with overrides. */
function componentRow(string $sku, array $overrides = []): array
{
    return ['material_id' => demoMaterial($sku)->id, 'quantity' => '1.5', 'waste_percent' => '5', 'notes' => 'Body', ...$overrides];
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
    'text overhead cost' => [['overhead_cost' => 'abc'], 'overhead_cost'],
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
    'a component with an unknown material' => [['components' => [['material_id' => NO_SUCH_ID, 'quantity' => '1']]], 'components.0.material_id'],
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

/** The four composition lines of the Denim Jacket of the costing skill's worked example, on the seeded materials. */
function denimJacketLines(): array
{
    return [
        ['material_id' => demoMaterial('MAT-DEN-012')->id, 'quantity' => '1.8', 'waste_percent' => '10', 'notes' => 'Body, sleeves, collar'],
        ['material_id' => demoMaterial('MAT-BTN-017')->id, 'quantity' => '8', 'waste_percent' => '0', 'notes' => 'Front placket (6), cuffs (2)'],
        ['material_id' => demoMaterial('MAT-THR-PES')->id, 'quantity' => '120', 'waste_percent' => '5', 'notes' => 'All seams'],
        ['material_id' => demoMaterial('MAT-LBL-WOV')->id, 'quantity' => '1', 'waste_percent' => '0', 'notes' => 'Inside back yoke'],
    ];
}

/** The materials and quantities of a product's stored lines, in position order. */
function storedComposition(Product $product): array
{
    return ProductComponent::query()->where('product_id', $product->id)->orderBy('position')->get()
        ->map(fn (ProductComponent $line): array => [
            'sku' => Material::query()->without('category', 'unit')->findOrFail($line->material_id)->sku,
            'quantity' => $line->quantity,
            'waste_percent' => $line->waste_percent,
            'notes' => $line->notes,
            'position' => $line->position,
        ])
        ->all();
}

describe('store', function () {
    it('creates the product with its composition and goes to its page with a success toast', function () {
        $response = actingAs($this->user)->post(route('products.store'), validProductPayload());

        $product = demoProduct('PRD-NEW-100');

        $response->assertRedirect(route('products.show', $product))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_CREATED_TOAST);

        expect($product->name)->toBe('Indigo Work Shirt')
            ->and($product->status->value)->toBe('draft')
            ->and($product->category_id)->toBe(ProductCategory::query()->where('code', 'apparel')->value('id'))
            ->and($product->description)->toBe('A new shirt.')
            ->and($product->labor_cost)->toBe('8.5000')
            ->and($product->overhead_cost)->toBe('2.0000')
            ->and($product->selling_price)->toBe('59.9000')
            ->and($product->created_by)->toBe($this->user->id)
            ->and($product->updated_by)->toBe($this->user->id)
            ->and(storedComposition($product))->toBe([
                ['sku' => 'MAT-DEN-012', 'quantity' => '1.6000', 'waste_percent' => '8.00', 'notes' => 'Body', 'position' => 0],
                ['sku' => 'MAT-THR-PES', 'quantity' => '90.0000', 'waste_percent' => '5.00', 'notes' => 'Seams', 'position' => 1],
            ]);
    });

    it('creates the Denim Jacket of the worked example, and its page sends the golden figures', function () {
        actingAs($this->user)->post(route('products.store'), validProductPayload([
            'sku' => 'PRD-JKT-NEW',
            'name' => 'Denim Jacket',
            'status' => 'active',
            'description' => 'Classic trucker jacket in 12oz indigo denim with metal shank buttons.',
            'labor_cost' => '12',
            'overhead_cost' => '3.5',
            'selling_price' => '89',
            'components' => denimJacketLines(),
        ]))->assertSessionHasNoErrors();

        $created = demoProduct('PRD-JKT-NEW');
        $response = actingAs($this->user)->get(route('products.show', $created));

        // The golden values of the costing skill.
        $response->assertInertia(fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded
            ->where('product.cost', ['material_cost' => '20.2540', 'labor_cost' => '12.0000', 'overhead_cost' => '3.5000', 'unit_cost' => '35.7540'])
            ->where('product.capacity.max_producible', 112)
            ->where('product.capacity.bottleneck.sku', 'MAT-BTN-017')
            ->where('product.margin', ['amount' => '53.2460', 'percent' => '59.83'])
            ->where('product.selling_price', '89.0000')));

        expect(loadedPageProp($response, 'product.components.*.units_possible'))->toBe([126, 112, 158, 400]);

        // And it is the page of the seeded Denim Jacket, prop for prop (what identifies the rows and the photo aside).
        $seeded = actingAs($this->user)->get(route('products.show', pid(1)));
        $strip = fn (array $product): array => [
            ...Arr::except($product, ['id', 'sku', 'photo_url', 'created_at', 'updated_at']),
            'components' => array_map(fn (array $line): array => Arr::except($line, ['id']), $product['components']),
        ];

        expect($strip(loadedPageProp($response, 'product')))->toBe($strip(loadedPageProp($seeded, 'product')));
    });

    it('saves a blank overhead as 0, and the Denim Jacket then costs 32.2540', function (mixed $overhead, bool $sent) {
        $payload = validProductPayload([
            'sku' => 'PRD-JKT-NEW',
            'name' => 'Denim Jacket',
            'status' => 'active',
            'labor_cost' => '12',
            'overhead_cost' => $overhead,
            'selling_price' => '89',
            'components' => denimJacketLines(),
        ]);

        $created = actingAs($this->user)
            ->post(route('products.store'), $sent ? $payload : Arr::except($payload, 'overhead_cost'))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_CREATED_TOAST);

        $product = demoProduct('PRD-JKT-NEW');
        $created->assertRedirect(route('products.show', $product));

        expect($product->overhead_cost)->toBe('0.0000');

        actingAs($this->user)->get(route('products.show', $product))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded
                ->where('product.cost', ['material_cost' => '20.2540', 'labor_cost' => '12.0000', 'overhead_cost' => '0.0000', 'unit_cost' => '32.2540'])
                ->where('product.margin', ['amount' => '56.7460', 'percent' => '63.76'])
                ->where('product.capacity.max_producible', 112)
                ->where('product.capacity.bottleneck.sku', 'MAT-BTN-017')));
    })->with([
        'a null overhead' => [null, true],
        'an empty overhead' => ['', true],
        'no overhead key' => [null, false],
    ]);

    it('rejects invalid input with an error on the field, no toast and nothing saved', function (array $override, string $field) {
        $before = Product::query()->count();

        actingAs($this->user)
            ->from(route('products.create'))
            ->post(route('products.store'), withRealMaterials(validProductPayload($override)))
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors($field)
            ->assertInertiaFlashMissing('toast');

        expect(Product::query()->count())->toBe($before)
            ->and(ProductComponent::query()->count())->toBe(ProductComponent::query()->whereIn('product_id', Product::query()->select('id'))->count());
    })->with('invalidProductInput');

    it('accepts the boundary values and saves them', function (array $override) {
        $payload = withRealMaterials(validProductPayload($override));

        actingAs($this->user)
            ->post(route('products.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_CREATED_TOAST);

        expect(Product::query()->where('sku', $payload['sku'])->exists())->toBeTrue();
    })->with('validProductBoundaries');

    it('accepts a photo of each allowed type, up to 4096 KB, and stores it', function (UploadedFile $photo) {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['photo' => $photo]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_CREATED_TOAST);

        expect(demoProduct('PRD-NEW-100')->photo_path)->toStartWith('products/');
        Storage::disk('public')->assertExists(demoProduct('PRD-NEW-100')->photo_path);
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

    it('rejects a duplicate of the SKU of a non-deleted product', function (string $sku) {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['sku' => $sku]))
            ->assertSessionHasErrors(['sku' => 'The sku has already been taken.']);
    })->with(['the first' => 'PRD-JKT-001', 'the last' => 'PRD-BLT-007', 'a draft' => 'PRD-JKT-006']);

    it('accepts the SKU of a soft-deleted product', function () {
        demoProduct('PRD-JKT-001')->delete();

        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['sku' => 'PRD-JKT-001']))
            ->assertSessionHasNoErrors();

        expect(Product::query()->where('sku', 'PRD-JKT-001')->count())->toBe(1)
            ->and(Product::withTrashed()->where('sku', 'PRD-JKT-001')->count())->toBe(2);
    });

    it('puts a duplicate material on the line that repeats it', function () {
        $denim = demoMaterial('MAT-DEN-012')->id;

        $response = actingAs($this->user)->post(route('products.store'), validProductPayload(['components' => [
            componentRow('MAT-THR-PES'),
            componentRow('MAT-DEN-012'),
            componentRow('MAT-LBL-WOV'),
            ['material_id' => $denim, 'quantity' => '2', 'waste_percent' => '0'],
        ]]));

        $response->assertSessionHasErrors(['components.1.material_id', 'components.3.material_id'])
            ->assertSessionDoesntHaveErrors(['components.0.material_id', 'components.2.material_id']);

        expect(Product::query()->where('sku', 'PRD-NEW-100')->exists())->toBeFalse();
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
            ->assertSessionHasErrors(['sku', 'name', 'category', 'status', 'labor_cost', 'components.0.material_id', 'components.0.quantity'])
            ->assertSessionDoesntHaveErrors('overhead_cost');
    });

    it('rejects a soft-deleted material in the composition', function () {
        $material = demoMaterial('MAT-LAC-120');
        $material->delete();

        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['components' => [
                ['material_id' => $material->id, 'quantity' => '1', 'waste_percent' => '0'],
            ]]))
            ->assertSessionHasErrors('components.0.material_id');
    });

    it('refuses an archived category and accepts an active one', function () {
        ProductCategory::query()->where('code', 'bag')->update(['archived_at' => now()]);

        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['category' => 'bag']))
            ->assertSessionHasErrors('category');

        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['category' => 'footwear']))
            ->assertSessionHasNoErrors();
    });

    it('takes the audit columns from the signed-in user, never from the request', function () {
        $other = User::factory()->create();

        actingAs($this->user)
            ->post(route('products.store'), validProductPayload(['created_by' => $other->id, 'updated_by' => $other->id, 'photo_path' => 'x.jpg', 'id' => 999]))
            ->assertSessionHasNoErrors();

        $product = demoProduct('PRD-NEW-100');

        expect($product->created_by)->toBe($this->user->id)
            ->and($product->updated_by)->toBe($this->user->id)
            ->and($product->photo_path)->toBeNull()
            ->and($product->id)->not->toBe(999);
    });

    it('accepts a multipart request with a photo and a nested composition', function () {
        actingAs($this->user)
            ->post(route('products.store'), validProductPayload([
                'photo' => UploadedFile::fake()->image('shirt.png', 600, 800),
                'components' => [componentRow('MAT-DEN-012'), componentRow('MAT-THR-PES', ['quantity' => '90'])],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.show', Product::query()->where('sku', 'PRD-NEW-100')->firstOrFail()));

        expect(storedComposition(demoProduct('PRD-NEW-100')))->toHaveCount(2);
    });

    it('accepts a multipart request without a composition (a body cannot carry an empty array)', function () {
        $payload = validProductPayload(['photo' => UploadedFile::fake()->image('shirt.png')]);
        unset($payload['components']);

        actingAs($this->user)->post(route('products.store'), $payload)->assertSessionHasNoErrors();

        expect(storedComposition(demoProduct('PRD-NEW-100')))->toBe([]);
    });
});

describe('update', function () {
    it('saves the changes and goes to the page with a success toast', function () {
        $tote = pid(4);

        actingAs($this->user)
            ->put(route('products.update', $tote), validProductPayload())
            ->assertRedirect(route('products.show', $tote))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_UPDATED_TOAST);

        $product = Product::query()->findOrFail($tote);

        expect($product->sku)->toBe('PRD-NEW-100')
            ->and($product->name)->toBe('Indigo Work Shirt')
            ->and($product->status->value)->toBe('draft')
            ->and($product->labor_cost)->toBe('8.5000')
            ->and($product->selling_price)->toBe('59.9000')
            // Created by nobody (the seeder), updated by the user.
            ->and($product->created_by)->toBeNull()
            ->and($product->updated_by)->toBe($this->user->id);
    });

    it('clears the overhead to 0 and the unit cost drops by the old overhead', function () {
        $jacket = pid(1);
        $unitCost = fn (): string => loadedPageProp(actingAs($this->user)->get(route('products.show', $jacket)), 'product.cost.unit_cost');

        expect($unitCost())->toBe('35.7540');

        actingAs($this->user)
            ->put(route('products.update', $jacket), validProductPayload([
                'sku' => 'PRD-JKT-001',
                'name' => 'Denim Jacket',
                'status' => 'active',
                'labor_cost' => '12',
                'overhead_cost' => '',
                'selling_price' => '89',
                'components' => denimJacketLines(),
            ]))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_UPDATED_TOAST);

        expect(Product::query()->findOrFail($jacket)->overhead_cost)->toBe('0.0000')
            ->and($unitCost())->toBe('32.2540');
    });

    it('replaces the composition: lines kept, added, removed and reordered', function () {
        $jacket = pid(1);
        $denimLine = ProductComponent::query()->where('product_id', $jacket)->where('material_id', demoMaterial('MAT-DEN-012')->id)->firstOrFail();

        // Was: denim, button, thread, label. Now: label first, then denim with a new quantity, then the zipper (new). Button and thread are removed.
        actingAs($this->user)->put(route('products.update', $jacket), validProductPayload(['sku' => 'PRD-JKT-001', 'components' => [
            componentRow('MAT-LBL-WOV', ['quantity' => '2', 'waste_percent' => '0', 'notes' => 'Neck']),
            componentRow('MAT-DEN-012', ['quantity' => '2.1', 'waste_percent' => '12.5', 'notes' => 'Everything']),
            componentRow('MAT-ZIP-018', ['quantity' => '1', 'waste_percent' => '0', 'notes' => 'Front']),
        ]]))->assertSessionHasNoErrors();

        expect(storedComposition(Product::query()->findOrFail($jacket)))->toBe([
            ['sku' => 'MAT-LBL-WOV', 'quantity' => '2.0000', 'waste_percent' => '0.00', 'notes' => 'Neck', 'position' => 0],
            ['sku' => 'MAT-DEN-012', 'quantity' => '2.1000', 'waste_percent' => '12.50', 'notes' => 'Everything', 'position' => 1],
            ['sku' => 'MAT-ZIP-018', 'quantity' => '1.0000', 'waste_percent' => '0.00', 'notes' => 'Front', 'position' => 2],
        ])
            // The line of a material that stays is updated in place.
            ->and(ProductComponent::query()->findOrFail($denimLine->id)->quantity)->toBe('2.1000');

        // The page shows the new composition with fresh figures: denim needs 2.1 x 1.125 = 2.3625 per unit, so 250 / 2.3625 = 105 units.
        actingAs($this->user)->get(route('products.show', $jacket))
            ->assertInertia(fn (Assert $page) => $page->loadDeferredProps('default', fn (Assert $reloaded) => $reloaded
                ->where('product.components.1.effective_quantity', '2.3625')
                ->where('product.capacity.max_producible', 105)
                ->where('product.capacity.bottleneck.sku', 'MAT-DEN-012')));

        expect(loadedPageProp(actingAs($this->user)->get(route('products.show', $jacket)), 'product.components.*.material.sku'))
            ->toBe(['MAT-LBL-WOV', 'MAT-DEN-012', 'MAT-ZIP-018']);
    });

    it('clears the composition when an empty one is submitted', function () {
        $jacket = pid(1);

        actingAs($this->user)->put(route('products.update', $jacket), validProductPayload(['sku' => 'PRD-JKT-001', 'components' => []]))
            ->assertSessionHasNoErrors();

        expect(storedComposition(Product::query()->findOrFail($jacket)))->toBe([]);
    });

    it('rejects invalid input with an error on the field, no toast and nothing changed', function (array $override, string $field) {
        $tote = pid(4);
        $before = Product::query()->findOrFail($tote)->only(['sku', 'name', 'labor_cost']);
        $lines = storedComposition(Product::query()->findOrFail($tote));

        actingAs($this->user)
            ->from(route('products.edit', $tote))
            ->put(route('products.update', $tote), withRealMaterials(validProductPayload($override)))
            ->assertRedirect(route('products.edit', $tote))
            ->assertSessionHasErrors($field)
            ->assertInertiaFlashMissing('toast');

        expect(Product::query()->findOrFail($tote)->only(['sku', 'name', 'labor_cost']))->toBe($before)
            ->and(storedComposition(Product::query()->findOrFail($tote)))->toBe($lines);
    })->with('invalidProductInput');

    it('accepts the boundary values', function (array $override) {
        actingAs($this->user)
            ->put(route('products.update', pid(4)), withRealMaterials(validProductPayload($override)))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', PRODUCT_UPDATED_TOAST);
    })->with('validProductBoundaries');

    it('keeps the SKU of the product being edited valid', function () {
        actingAs($this->user)
            ->put(route('products.update', pid(1)), validProductPayload(['sku' => 'PRD-JKT-001']))
            ->assertSessionHasNoErrors();
    });

    it('refuses the SKU of another product, and accepts one only a soft-deleted product has', function () {
        actingAs($this->user)
            ->put(route('products.update', pid(1)), validProductPayload(['sku' => 'PRD-TSH-002']))
            ->assertSessionHasErrors(['sku' => 'The sku has already been taken.']);

        demoProduct('PRD-TSH-002')->delete();

        actingAs($this->user)
            ->put(route('products.update', pid(1)), validProductPayload(['sku' => 'PRD-TSH-002']))
            ->assertSessionHasNoErrors();
    });

    it('keeps the current category valid when it was archived, and refuses it for another product', function () {
        [$tote, $jacket] = [pid(4), pid(1)];
        ProductCategory::query()->where('code', 'bag')->update(['archived_at' => now()]);

        // The Tote is already in `bag`: it can stay there.
        actingAs($this->user)
            ->put(route('products.update', $tote), validProductPayload(['category' => 'bag']))
            ->assertSessionHasNoErrors();

        // The Jacket is not: it can't move there.
        actingAs($this->user)
            ->put(route('products.update', $jacket), validProductPayload(['category' => 'bag']))
            ->assertSessionHasErrors('category');

        // And the Tote can't move to another archived category either.
        ProductCategory::query()->where('code', 'jewelry')->update(['archived_at' => now()]);

        actingAs($this->user)
            ->put(route('products.update', $tote), validProductPayload(['category' => 'jewelry']))
            ->assertSessionHasErrors('category');
    });

    it('accepts a multipart update with a photo and stores it', function () {
        $jacket = pid(1);

        actingAs($this->user)
            ->post(route('products.update', $jacket), [...validProductPayload(['photo' => UploadedFile::fake()->image('x.webp')]), '_method' => 'PUT'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.show', $jacket))
            ->assertInertiaFlash('toast', PRODUCT_UPDATED_TOAST);

        Storage::disk('public')->assertExists(Product::query()->findOrFail($jacket)->photo_path);
    });

    it('rejects a soft-deleted material in the composition', function () {
        $laces = demoMaterial('MAT-LAC-120');
        $laces->delete();

        actingAs($this->user)
            ->put(route('products.update', pid(1)), validProductPayload(['components' => [['material_id' => $laces->id, 'quantity' => '1', 'waste_percent' => '0']]]))
            ->assertSessionHasErrors('components.0.material_id');
    });

    it('answers 404 for an unknown or soft-deleted product before it validates anything', function () {
        actingAs($this->user)->put(route('products.update', NO_SUCH_ID), ['sku' => ''])
            ->assertNotFound()
            ->assertSessionHasNoErrors();

        $deleted = demoProduct('PRD-TSH-002');
        $deleted->delete();

        actingAs($this->user)->put(route('products.update', $deleted->id), ['sku' => ''])
            ->assertNotFound()
            ->assertSessionHasNoErrors();
    });
});

describe('destroy', function () {
    it('soft-deletes the product and says so', function () {
        $sneaker = pid(3);

        actingAs($this->user)
            ->delete(route('products.destroy', $sneaker))
            ->assertRedirect(route('products.index'))
            ->assertInertiaFlash('toast', PRODUCT_DELETED_TOAST);

        $this->assertSoftDeleted('products', ['id' => $sneaker]);

        // It is gone from every page, and its lines stay for history.
        actingAs($this->user)->get(route('products.show', $sneaker))->assertNotFound();
        expect(pageProp(actingAs($this->user)->get(route('products.index')), 'products.meta.total'))->toBe(6)
            ->and(ProductComponent::query()->where('product_id', $sneaker)->count())->toBe(5);
    });

    it('answers 404 for an unknown product, and for one already deleted', function () {
        actingAs($this->user)->delete(route('products.destroy', NO_SUCH_ID))->assertNotFound();

        actingAs($this->user)->delete(route('products.destroy', pid(3)))->assertRedirect();
        actingAs($this->user)->delete(route('products.destroy', Product::withTrashed()->where('sku', 'PRD-SNK-003')->value('id')))->assertNotFound();
    });
});

describe('authorization', function () {
    it('answers 403 on every product route when the policy refuses', function (string $method, string $route, bool $withProduct) {
        Gate::policy(Product::class, DenyAllProductPolicy::class);

        $url = route($route, $withProduct ? [pid(1)] : []);
        $payload = in_array($method, ['post', 'put'], true) ? validProductPayload() : [];

        actingAs($this->user)->{$method}($url, $payload)->assertForbidden();
    })->with([
        'index' => ['get', 'products.index', false],
        'create' => ['get', 'products.create', false],
        'store' => ['post', 'products.store', false],
        'show' => ['get', 'products.show', true],
        'edit' => ['get', 'products.edit', true],
        'update' => ['put', 'products.update', true],
        'destroy' => ['delete', 'products.destroy', true],
    ]);

    it('changes nothing when the policy refuses a write', function () {
        Gate::policy(Product::class, DenyAllProductPolicy::class);
        $before = Product::query()->count();

        actingAs($this->user)->post(route('products.store'), validProductPayload())->assertForbidden();
        actingAs($this->user)->put(route('products.update', pid(1)), validProductPayload())->assertForbidden();
        actingAs($this->user)->delete(route('products.destroy', pid(1)))->assertForbidden();

        expect(Product::query()->count())->toBe($before)
            ->and(demoProduct('PRD-JKT-001')->name)->toBe('Denim Jacket');
    });

    it('serves the product pages in production', function () {
        $this->app->detectEnvironment(fn () => 'production');

        actingAs($this->user)->get(route('products.index'))->assertOk();
        actingAs($this->user)->get(route('products.show', pid(1)))->assertOk();
        actingAs($this->user)->get(route('products.edit', pid(1)))->assertOk();
        actingAs($this->user)->get(route('products.create'))->assertOk();
    });
});
