<?php

use App\Actions\Products\UpdateProduct;
use App\Enums\ProductionBatchStatus;
use App\Models\Barcode;
use App\Models\BarcodeName;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductSizeStock;
use App\Models\Size;
use App\Models\Workshop;
use App\Services\Products\ProductSizeLock;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| A size the product uses stays on it (INV-18-f, AC-5, AC-6, ADR-0028)
|--------------------------------------------------------------------------
|
| The Denim Jacket (PRD-JKT-001, White) comes in XS to XXXL here; the Leather Tote is "One size". A size is locked while the product has
| finished units in it, a row on a (non-deleted) order, a barcode of its name in one of its colours, or a batch In production. Taking a
| locked size off on Edit product saves nothing and puts one sentence per size on `sizes.removed.{sizeId}`, in list order.
*/

const SIZES_COMBINED = "One size can't be combined with other sizes. Choose it alone, or choose other sizes.";

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
    $this->tote = demoProduct('PRD-TOT-004');
});

/** Puts one thing on the product's size (by label) so that it is locked for that reason: stock, orders, barcodes or production ('size' gives the product the size first). */
function szlPlant(Product $product, string $reason, string $label, bool $delivered = false): void
{
    $size = sizeId($label);
    $colour = $product->colours()->value('colours.id');

    match ($reason) {
        'size' => $product->sizes()->attach($size, ['workshop_id' => $product->workshop_id]),
        'stock' => ProductSizeStock::factory()->create(['product_id' => $product->id, 'colour_id' => $colour, 'size_id' => $size, 'units' => 2]),
        'orders' => OrderLine::factory()->sized([$label => 3], $delivered ? [$label => [3, 0]] : [])
            ->create(['order_id' => Order::factory()->create()->id, 'product_id' => $product->id]),
        'barcodes' => BarcodeName::factory()->create([
            'barcode_id' => Barcode::factory()->create(['colour_id' => $colour, 'size_id' => $size])->id, 'name_key' => $product->name_key,
        ]),
        'production' => ProductionBatch::factory()->create(['product_id' => $product->id, 'colour_id' => $colour, 'size_id' => $size, 'units' => 3]),
    };
}

/** Edit product's save of a product keeping its name and colours, with exactly these sizes (labels) or, as a list of ids, these; null sends no `sizes` at all. */
function szlSave(Product $product, ?array $sizes, array $overrides = [])
{
    $payload = validProductPayload([
        'sku' => $product->sku, 'name' => $product->name, 'status' => 'active', 'category' => $product->category->code,
        'colours' => $product->colours()->pluck('colours.id')->all(), ...$overrides,
    ]);
    $payload['sizes'] = $sizes === null ? null : array_map(fn (string|int $size): int => is_int($size) ? $size : sizeId($size), $sizes);

    return actingAs(test()->user)->from(route('products.edit', $product))->put(route('products.update', $product), array_filter($payload, fn (mixed $value, string $key): bool => $key !== 'sizes' || $value !== null, ARRAY_FILTER_USE_BOTH));
}

/** The labels of the sizes of the product now. */
function szlLabels(Product $product): array
{
    return sizesOf($product)->pluck('label')->map(fn (mixed $label): string => (string) $label)->all();
}

const SZL_ALL = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

it('refuses a save that chooses no size, One size with others, a size of another workshop or a locked size taken off, and saves nothing (AC-5, AC-6)', function (string $product, array $plants, ?array $sizes, array $expected) {
    $product = $this->{$product};

    foreach ($plants as [$reason, $label]) {
        szlPlant($product, $reason, $label);
    }

    $before = szlLabels($product);

    $ids = $sizes === null ? null : array_map(fn (string $label): int|string => $label === 'other workshop' ? CurrentWorkshop::run(Workshop::factory()->create(), fn () => Size::factory()->create())->id : $label, $sizes);
    $expected = collect($expected)->mapWithKeys(fn (?string $message, string $key): array => [
        (string) preg_replace_callback('/\{([^}]+)\}/', fn (array $match): string => (string) sizeId($match[1]), $key) => $message,
    ])->all();

    szlSave($product, $ids)->assertRedirect(route('products.edit', $product))->assertSessionHasErrors(array_keys($expected));

    // Every key and nothing else, in list order, each with its sentence; and nothing saved.
    expect(session('errors')->keys())->toBe(array_keys($expected))
        ->and(szlLabels($product))->toBe($before);

    foreach (array_filter($expected) as $key => $message) {
        expect(session('errors')->first($key))->toBe($message);
    }

    // A save with no `sizes` at all is refused the same way on Add product (INV-18-h).
    if ($sizes === null) {
        actingAs($this->user)->from(route('products.create'))->post(route('products.store'), Arr::except(validProductPayload(), 'sizes'))
            ->assertRedirect(route('products.create'))->assertSessionHasErrors(['sizes' => 'Choose at least one size.']);
        expect(Product::query()->where('sku', 'PRD-NEW-100')->exists())->toBeFalse();
    }
})->with([
    'none chosen' => ['jacket', [], [], ['sizes' => 'Choose at least one size.']],
    'sizes missing' => ['jacket', [], null, ['sizes' => 'Choose at least one size.']],
    'One size with others' => ['jacket', [], ['S', 'One size'], ['sizes' => SIZES_COMBINED]],
    'a size of another workshop' => ['jacket', [], ['S', 'other workshop'], ['sizes.1' => null]],
    'XL with stock, orders and barcodes' => ['jacket', [['stock', 'XL'], ['orders', 'XL'], ['barcodes', 'XL']], ['XS', 'S', 'M', 'L', 'XXL', 'XXXL'], [
        'sizes.removed.{XL}' => "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has barcodes.",
    ]],
    'S and M, two keys in list order' => ['jacket', [['barcodes', 'M'], ['stock', 'S']], ['XS', 'L', 'XL', 'XXL', 'XXXL'], [
        'sizes.removed.{S}' => "S can't be taken off Denim Jacket: it has units in stock.",
        'sizes.removed.{M}' => "M can't be taken off Denim Jacket: it has barcodes.",
    ]],
    'a number size reads "Size 36"' => ['jacket', [['size', '36'], ['orders', '36']], SZL_ALL, [
        'sizes.removed.{36}' => "Size 36 can't be taken off Denim Jacket: it is on orders.",
    ]],
    'stock alone' => ['jacket', [['stock', 'M']], ['XS', 'S', 'L', 'XL', 'XXL', 'XXXL'], ['sizes.removed.{M}' => "M can't be taken off Denim Jacket: it has units in stock."]],
    'orders alone' => ['jacket', [['orders', 'M']], ['XS', 'S', 'L', 'XL', 'XXL', 'XXXL'], ['sizes.removed.{M}' => "M can't be taken off Denim Jacket: it is on orders."]],
    'barcodes alone' => ['jacket', [['barcodes', 'M']], ['XS', 'S', 'L', 'XL', 'XXL', 'XXXL'], ['sizes.removed.{M}' => "M can't be taken off Denim Jacket: it has barcodes."]],
    'production alone' => ['jacket', [['production', 'M']], ['XS', 'S', 'L', 'XL', 'XXL', 'XXXL'], ['sizes.removed.{M}' => "M can't be taken off Denim Jacket: it has units in production."]],
    'all four reasons' => ['jacket', [['stock', 'M'], ['orders', 'M'], ['barcodes', 'M'], ['production', 'M']], ['XS', 'S', 'L', 'XL', 'XXL', 'XXXL'], [
        'sizes.removed.{M}' => "M can't be taken off Denim Jacket: it has units in stock, is on orders, has barcodes and has units in production.",
    ]],
    'the Tote with One size locked, adding S (a stale page)' => ['tote', [['stock', 'One size']], ['S', 'One size'], ['sizes' => SIZES_COMBINED]],
]);

it('words why One size can\'t be chosen and why other sizes can\'t (AC-6)', function (array $labels, array $locked, ?string $oneSize, ?string $others) {
    $product = Product::factory()->withSizes($labels)->create(['sku' => 'PRD-SZL-001', 'name' => 'Slim Jacket', 'category' => 'apparel']);
    $product->colours()->attach(demoColour('WHT')->id, ['workshop_id' => $product->workshop_id]);

    foreach ($locked as $label) {
        szlPlant($product, 'stock', $label);
    }

    $lock = app(ProductSizeLock::class);

    expect($lock->oneSizeLock($product))->toBe($oneSize)
        ->and($lock->otherSizesLock($product))->toBe($others)
        ->and($lock->tooltip(['has units in stock', 'is on orders', 'has barcodes']))->toBe("Can't be taken off: it has units in stock, is on orders and has barcodes.");
})->with([
    'one letter size' => [['S', 'M', 'L', 'XL'], ['XL'], "XL is in use, so One size can't be chosen.", null],
    'one number size' => [['36', '37', '38'], ['38'], "Size 38 is in use, so One size can't be chosen.", null],
    'several number sizes' => [['36', '37', '38'], ['36', '37', '38'], "Sizes 36, 37 and 38 are in use, so One size can't be chosen.", null],
    'several letter sizes' => [['S', 'M', 'L', 'XL'], ['S', 'M', 'L', 'XL'], "S, M, L and XL are in use, so One size can't be chosen.", null],
    'nothing locked' => [['S', 'M'], [], null, null],
    'One size locked' => [['One size'], ['One size'], null, "One size is in use, so other sizes can't be chosen."],
    'One size free' => [['One size'], [], null, null],
]);

it('locks a size only for what is really on it, in four queries (AC-6)', function (Closure $plant, array $expected) {
    $plant->call($this, $this->jacket);

    $lock = app(ProductSizeLock::class);
    $reasons = [];
    $queries = countQueries(function () use ($lock, &$reasons) {
        $reasons = $lock->reasons($this->jacket);
    });
    $m = sizeId('M');

    expect($queries)->toBe(4)
        ->and($reasons[$m] ?? [])->toBe($expected);
})->with([
    'a deleted order' => [function (Product $jacket) {
        szlPlant($jacket, 'orders', 'M');
        Order::query()->latest('id')->firstOrFail()->delete();
    }, []],
    'a fully delivered order' => [fn (Product $jacket) => szlPlant($jacket, 'orders', 'M', delivered: true), ['is on orders']],
    'a batch put in stock or undone' => [function (Product $jacket) {
        szlPlant($jacket, 'production', 'M');
        ProductionBatch::query()->update(['status' => ProductionBatchStatus::InStock->value]);
        szlPlant($jacket, 'production', 'M');
        ProductionBatch::query()->latest('id')->firstOrFail()->update(['status' => ProductionBatchStatus::Undone->value]);
    }, []],
    'a batch in production' => [fn (Product $jacket) => szlPlant($jacket, 'production', 'M'), ['has units in production']],
    'a barcode of the name in a current colour' => [fn (Product $jacket) => szlPlant($jacket, 'barcodes', 'M'), ['has barcodes']],
    'a barcode of another name, or in a colour the product does not have' => [function (Product $jacket) {
        $other = BarcodeName::factory()->create([
            'barcode_id' => Barcode::factory()->create(['colour_id' => $jacket->colours()->value('colours.id'), 'size_id' => sizeId('M')])->id, 'name_key' => 'trucker jacket',
        ]);
        BarcodeName::factory()->create([
            'barcode_id' => Barcode::factory()->create(['colour_id' => demoColour('RED')->id, 'size_id' => sizeId('M')])->id, 'name_key' => $jacket->name_key,
        ]);
        expect($other->exists)->toBeTrue();
    }, []],
    'stock and orders, in that order' => [function (Product $jacket) {
        szlPlant($jacket, 'orders', 'M');
        szlPlant($jacket, 'stock', 'M');
    }, ['has units in stock', 'is on orders']],
]);

/**
 * Two saves at once: the lock is read again under the product's row lock, and every other writer reads the product's sizes under it too,
 * so a size can't be taken off by one save while another puts something on it, or the other way round.
 */
dataset('saves that meet a size change', [
    'Update product, after stock was written' => [function () {
        actingAs($this->user);
        $stale = demoProduct('PRD-JKT-001');
        // The request passed with XL free; before the save, 3 units were counted in XL.
        szlPlant($this->jacket, 'stock', 'XL');
        $save = fn () => app(UpdateProduct::class)->handle($stale, validProductPayload([
            'sku' => $stale->sku, 'name' => 'Changed', 'sizes' => array_map(fn (string $label): int => sizeId($label), ['XS', 'S', 'M', 'L', 'XXL', 'XXXL']),
        ]), $this->user);

        expect($save)->toThrow(fn (ValidationException $e) => expect($e->errors())->toBe([
            'sizes.removed.'.sizeId('XL') => ["XL can't be taken off Denim Jacket: it has units in stock."],
        ]));
        expect($this->jacket->fresh()->name)->toBe('Denim Jacket')
            ->and(szlLabels($this->jacket))->toBe(SZL_ALL);
    }],
    'Update stock' => [function () {
        $body = ['stock' => [demoColour('WHT')->id => sizeIds(array_fill_keys(SZL_ALL, '1'))]];
        $this->jacket->sizes()->detach(sizeId('XXXL'));

        actingAs($this->user)->put(route('products.stock.update', $this->jacket), $body)
            ->assertSessionHasErrors(['stock' => 'The sizes or colours of Denim Jacket have changed. Enter the stock again.']);
    }],
    'Record restock' => [function () {
        $body = restockBody([restockLine($this->jacket, ['White' => ['XXXL' => 1]])]);
        $this->jacket->sizes()->detach(sizeId('XXXL'));

        actingAs($this->user)->post(route('restocks.store'), $body)
            ->assertSessionHasErrors(['products.0.product' => 'The sizes or colours of Denim Jacket have changed. Enter its units again.']);
    }],
    'an order save' => [function () {
        $body = validOrderPayload(['lines' => [['product_id' => $this->jacket->id, 'units' => orderUnits($this->jacket->id, ['S' => '1', 'XXXL' => '2'])]]]);
        $this->jacket->sizes()->detach(sizeId('XXXL'));

        actingAs($this->user)->post(route('orders.store'), $body)
            ->assertSessionHasErrors(['lines.0.units' => 'The colours or sizes of Denim Jacket have changed. Enter its quantities again.']);
    }],
    'Start batch' => [function () {
        $body = ['product_id' => $this->jacket->id, 'units' => restockLine($this->jacket, ['White' => ['XXXL' => 1]])['units']];
        $this->jacket->sizes()->detach(sizeId('XXXL'));

        actingAs($this->user)->post(route('progress.batches.store'), $body)
            ->assertSessionHasErrors(['units' => 'The colours or sizes of Denim Jacket have changed. Enter the units again.']);
    }],
    'Generate barcodes' => [function () {
        $variants = [['colour_id' => demoColour('WHT')->id, 'size_id' => sizeId('XXXL')]];
        $this->jacket->sizes()->detach(sizeId('XXXL'));

        actingAs($this->user)->post(route('products.barcodes.store', $this->jacket), ['variants' => $variants])
            ->assertSessionHasErrors(['variants' => "Couldn't generate the barcodes. Try again."]);
        expect(Barcode::query()->count())->toBe(0);
    }],
]);

it('refuses a size change that meets another save, and a save on a size taken off (AC-6)', function (Closure $case) {
    $case->call($this);
})->with('saves that meet a size change');
