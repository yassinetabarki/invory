<?php

use App\Domain\Barcodes\NameKey;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Colour;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductSizeStock;
use App\Models\Size;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Saving a product's sizes (INV-18-f, AC-5, AC-6, ADR-0028)
|--------------------------------------------------------------------------
|
| Add product creates the product with the sizes chosen; Edit product changes them. The Denim Jacket (White, Black, Indigo) comes in
| XS to XXXL here, with stock, an order and a barcode in S to XL (so those four are locked and XS, XXL and XXXL are free). A size pressed on
| gains nothing else; a size taken off loses only its link.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
    $this->jacket->colours()->attach(Colour::query()->whereIn('code', ['BLK', 'IND'])->pluck('id')->mapWithKeys(fn (int $id): array => [$id => ['workshop_id' => $this->jacket->workshop_id]])->all());
    $white = demoColour('WHT')->id;

    foreach (['S', 'M', 'L', 'XL'] as $label) {
        ProductSizeStock::factory()->create(['product_id' => $this->jacket->id, 'colour_id' => $white, 'size_id' => sizeId($label), 'units' => 2]);
        OrderLine::factory()->sized([$label => 3])->create(['order_id' => Order::factory()->create()->id, 'product_id' => $this->jacket->id]);
        BarcodeName::factory()->create(['barcode_id' => Barcode::factory()->create(['colour_id' => $white, 'size_id' => sizeId($label)])->id, 'name_key' => 'denim jacket']);
    }
});

/** Edit product's save of the jacket (name, colours as they are) with these sizes by label. */
function szwSave(Product $jacket, array $labels, array $overrides = [])
{
    return actingAs(test()->user)->from(route('products.edit', $jacket))->put(route('products.update', $jacket), validProductPayload([
        'sku' => $jacket->sku, 'name' => 'Denim Jacket', 'status' => 'active',
        'colours' => $jacket->colours()->pluck('colours.id')->all(),
        'sizes' => array_map(fn (string $label): int => sizeId($label), $labels), ...$overrides,
    ]));
}

/** How many variants (colours × sizes) the product has now. */
function szwVariants(Product $product): int
{
    return $product->colours()->count() * sizesOf($product)->count();
}

it('creates a product with the sizes chosen, and only those (AC-5)', function () {
    actingAs($this->user)->post(route('products.store'), validProductPayload([
        'sku' => 'PRD-NEW-101', 'name' => 'Denim Jacket Slim', 'category' => 'footwear',
        'sizes' => array_map(fn (string $label): int => sizeId($label), ['S', 'M', 'L', 'XL']),
    ]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Product created.']);

    expect(szwLabelsOf('PRD-NEW-101'))->toBe(['S', 'M', 'L', 'XL']);
});

/** The labels of the sizes of the product with this SKU. */
function szwLabelsOf(string $sku): array
{
    return sizesOf(demoProduct($sku))->pluck('label')->map(fn (mixed $label): string => (string) $label)->all();
}

it('takes the free sizes off, puts a new one on with nothing in it, and offers any size, 36 included (AC-6)', function () {
    expect(szwVariants($this->jacket))->toBe(21);

    // XS, XXL and XXXL are free: "Product updated." and 3 colours × 4 sizes = 12 variants.
    szwSave($this->jacket, ['S', 'M', 'L', 'XL'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Product updated.']);

    expect(szwLabelsOf('PRD-JKT-001'))->toBe(['S', 'M', 'L', 'XL'])
        ->and(szwVariants($this->jacket))->toBe(12);

    // XXS (a size of this workshop, position 15) on: 15 variants, none with stock or a barcode.
    $xxs = Size::factory()->create(['label' => 'XXS', 'position' => 15]);
    szwSave($this->jacket, ['XXS', 'S', 'M', 'L', 'XL'])->assertSessionHasNoErrors();

    expect(szwLabelsOf('PRD-JKT-001'))->toBe(['XXS', 'S', 'M', 'L', 'XL'])
        ->and(szwVariants($this->jacket))->toBe(15)
        ->and(ProductSizeStock::query()->where('size_id', $xxs->id)->count())->toBe(0)
        ->and(BarcodeName::query()->where('size_id', $xxs->id)->count())->toBe(0);

    // The category doesn't limit it: a shoe size on a jacket.
    szwSave($this->jacket, ['XXS', 'S', 'M', 'L', 'XL', '36'])->assertSessionHasNoErrors();

    expect(szwLabelsOf('PRD-JKT-001'))->toBe(['XXS', 'S', 'M', 'L', 'XL', '36']);
});

it('gives the new name the barcodes of the sizes the save leaves, and refuses a clash on them (AC-5)', function () {
    $white = demoColour('WHT')->id;
    $barcode = fn (string $name, string $label): Barcode => tap(
        Barcode::factory()->create(['colour_id' => $white, 'size_id' => sizeId($label)]),
        fn (Barcode $made) => BarcodeName::factory()->create(['barcode_id' => $made->id, 'name_key' => NameKey::of($name)]),
    );

    // White S to XL already have a barcode under "Denim Jacket". It also has White 36 and 37 (sizes the jacket does not have yet);
    // "Trucker Jacket" has its own, different number for White 37.
    $own36 = $barcode('Denim Jacket', '36');
    $barcode('Denim Jacket', '37');
    $own37 = $barcode('Trucker Jacket', '37');

    // 37 pressed on in the same save: the two names meet on White 37 with different numbers: refused, nothing saved.
    szwSave($this->jacket, ['S', 'M', 'L', 'XL', '36', '37'], ['name' => 'Trucker Jacket'])
        ->assertSessionHasErrors(['name' => "Trucker Jacket already has its own barcode for White size 37, so Denim Jacket can't take that name. Choose another name."]);

    expect($this->jacket->fresh()->name)->toBe('Denim Jacket')
        ->and(szwLabelsOf('PRD-JKT-001'))->toBe(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']);

    // Without 37: the new name follows the sizes the save leaves (36, pressed on in this save, included), not the ones taken off.
    szwSave($this->jacket, ['S', 'M', 'L', 'XL', '36'], ['name' => 'Trucker Jacket'])->assertSessionHasNoErrors();

    $trucker = BarcodeName::query()->where('name_key', 'trucker jacket')->with(['size', 'barcode'])->get()->sortBy('size.position');

    expect($trucker->map(fn (BarcodeName $row): string => (string) $row->size->label)->values()->all())->toBe(['S', 'M', 'L', 'XL', '36', '37'])
        ->and($trucker->firstWhere('size_id', sizeId('36'))->barcode_id)->toBe($own36->id)
        ->and($trucker->firstWhere('size_id', sizeId('37'))->barcode_id)->toBe($own37->id)
        ->and(BarcodeName::query()->where('name_key', 'denim jacket')->count())->toBe(6);
});
