<?php

use App\Actions\Products\UpdateProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductComponent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The sizes are locked once the product is on an order (S-58-c, AC-4)
|--------------------------------------------------------------------------
|
| A product on an order that isn't deleted (also once fully shipped) can't change to a category with another size list. Checked by
| UpdateProductRequest (the error sits on `category`) and again by UpdateProduct under the product row lock. Rule: ADR-0015 decision 4.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    Storage::fake('public');
});

const SIZE_LOCK_JACKET = 'Denim Jacket is on orders, so its category can\'t change to one with other sizes. Keep a category with sizes S, M, L, XL.';
const SIZE_LOCK_SNEAKER = 'Canvas Low Sneaker is on orders, so its category can\'t change to one with other sizes. Keep a category with sizes 36 to 46.';
const SIZE_LOCK_TOTE = 'Leather Tote is on orders, so its category can\'t change to one with other sizes. Keep a "One size" category.';

/** What a refused save must leave alone: name, category and composition. */
function sizeLockState(int $id): array
{
    $product = Product::query()->findOrFail($id);

    return [
        $product->name,
        $product->category_id,
        ProductComponent::query()->where('product_id', $id)->orderBy('position')->pluck('quantity', 'material_id')->all(),
    ];
}

/** Book demo product number `$number` (1 = Denim Jacket) on an order of the given kind: open, fully shipped, deleted, or none. */
function bookForSizeLock(string $kind, int $number): void
{
    $lines = [[demoProduct(DEMO_PRODUCT_SKUS[$number]), 10]];

    match ($kind) {
        'open' => bookOrder(lines: $lines),
        'shipped' => bookShippedOrder([], $number, 10),
        'deleted' => bookOrder(lines: $lines)->delete(),
        default => null,
    };
}

/** The update route, from the edit page, with the product's own SKU and name unless overridden. The composition differs from the seeded one. */
function updateForSizeLock(object $test, int $id, array $overrides): TestResponse
{
    $product = Product::query()->findOrFail($id);

    return actingAs($test->user)
        ->from(route('products.edit', $id))
        ->put(route('products.update', $id), validProductPayload(['sku' => $product->sku, 'name' => $product->name, 'status' => 'active', ...$overrides]));
}

it('keeps the sizes of the Denim Jacket while it is on an order', function (string $orders, array $overrides, ?string $message) {
    $id = pid(1);
    bookForSizeLock($orders, 1);
    $before = sizeLockState($id);

    $response = updateForSizeLock($this, $id, $overrides);

    if ($message !== null) {
        $response->assertRedirect(route('products.edit', $id))->assertSessionHasErrors(['category' => $message]);
        expect(sizeLockState($id))->toBe($before);

        return;
    }

    $response->assertSessionHasNoErrors();
    expect(sizeLockState($id))->not->toBe($before);
})->with([
    'on an open order, to Footwear' => ['open', ['category' => 'footwear'], SIZE_LOCK_JACKET],
    'on an open order, to Bag' => ['open', ['category' => 'bag'], SIZE_LOCK_JACKET],
    'on a fully shipped order, to Footwear' => ['shipped', ['category' => 'footwear'], SIZE_LOCK_JACKET],
    'on a deleted order only, to Footwear' => ['deleted', ['category' => 'footwear'], null],
    'on no order, to Footwear' => ['none', ['category' => 'footwear'], null],
    'on an order, Apparel kept and renamed' => ['open', ['category' => 'apparel', 'name' => 'Denim Jacket Slim'], null],
]);

it('names the sizes of a Footwear product it keeps', function () {
    $id = pid(3);
    bookForSizeLock('open', 3);
    $before = sizeLockState($id);

    updateForSizeLock($this, $id, ['category' => 'apparel'])->assertSessionHasErrors(['category' => SIZE_LOCK_SNEAKER]);

    expect(sizeLockState($id))->toBe($before);
});

it('asks for a "One size" category when the product has one size', function () {
    $id = pid(4);
    bookForSizeLock('open', 4);
    $before = sizeLockState($id);

    updateForSizeLock($this, $id, ['category' => 'apparel'])->assertSessionHasErrors(['category' => SIZE_LOCK_TOTE]);

    expect(sizeLockState($id))->toBe($before);
});

it('lets a one-size product on an order move to any other one-size category', function (string $code) {
    ProductCategory::factory()->create(['code' => 'kids', 'name' => 'Kids']);
    $id = pid(4);
    bookForSizeLock('open', 4);

    updateForSizeLock($this, $id, ['category' => $code])->assertSessionHasNoErrors();

    expect(Product::query()->findOrFail($id)->category_id)->toBe(ProductCategory::query()->where('code', $code)->value('id'));
})->with(['Accessory' => 'accessory', 'a category added in Settings' => 'kids']);

it('checks again in UpdateProduct and saves nothing, not even the photo', function () {
    $jacket = Product::query()->findOrFail(pid(1));
    bookForSizeLock('open', 1);
    $before = sizeLockState($jacket->id);
    actingAs($this->user);

    $save = fn () => app(UpdateProduct::class)->handle(
        $jacket,
        validProductPayload(['sku' => $jacket->sku, 'name' => 'Changed', 'category' => 'footwear', 'photo' => UploadedFile::fake()->image('new.jpg')]),
        $this->user,
    );

    expect($save)->toThrow(fn (ValidationException $e) => expect($e->errors())->toBe(['category' => [SIZE_LOCK_JACKET]]));
    expect(sizeLockState($jacket->id))->toBe($before)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});
