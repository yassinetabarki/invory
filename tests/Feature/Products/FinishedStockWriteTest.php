<?php

use App\Actions\Products\UpdateFinishedStock;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSizeStock;
use App\Models\SizeList;
use App\Models\StockMovement;
use App\Models\Workshop;
use App\Services\Products\FinishedStock;
use App\Services\Workshops\CurrentWorkshop;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| "Update stock": saving a count per size (S-56-c, AC-2, AC-3)
|--------------------------------------------------------------------------
|
| `PUT products/{product}/stock` sets the finished units of the Denim Jacket (Apparel: S, M, L, XL) and writes nothing else. The demo
| products are in the database; a factory product pins its category (F-68).
*/

const STOCK_UPDATED_TOAST = ['type' => 'success', 'message' => 'Stock updated.'];
const SIZES_CHANGED = 'The sizes of Denim Jacket have changed. Enter the stock again.';
const WHOLE_NUMBER = 'Enter a whole number from 0 to 1,000,000.';

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
});

/** The stock rows of a product as `['S' => 3, 'L' => 5]` (sizes without a row are left out). */
function shelfOf(Product $product): array
{
    return ProductSizeStock::query()->where('product_id', $product->id)->with('size')->get()
        ->mapWithKeys(fn (ProductSizeStock $row): array => [$row->size->label => $row->units])->all();
}

/** Puts finished units on the shelf, `['S' => 3]`, straight in the table. */
function stockShelf(Product $product, array $byLabel, string $list = SizeList::S_TO_XL): void
{
    foreach (sizeIds($byLabel, $list) as $sizeId => $units) {
        ProductSizeStock::factory()->create(['product_id' => $product->id, 'size_id' => $sizeId, 'units' => $units]);
    }
}

it('sets the count of every size, and a size set to 0 has no row (AC-2)', function () {
    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['sizes' => sizeIds(['S' => '3', 'M' => '4', 'L' => '5', 'XL' => '2'])])
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', STOCK_UPDATED_TOAST);

    expect(shelfOf($this->jacket))->toBe(['S' => 3, 'M' => 4, 'L' => 5, 'XL' => 2])
        ->and(app(FinishedStock::class)->of($this->jacket)->units)->toBe(14);

    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['sizes' => sizeIds(['S' => '3', 'M' => '0', 'L' => '5', 'XL' => '2'])])
        ->assertRedirect(route('products.show', $this->jacket));

    expect(shelfOf($this->jacket))->toBe(['S' => 3, 'L' => 5, 'XL' => 2]);
});

it('counts a size left empty as 0 (AC-2)', function () {
    stockShelf($this->jacket, ['M' => 6]);

    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['sizes' => sizeIds(['S' => '3', 'M' => '', 'L' => '5', 'XL' => '2'])])
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertSessionHasNoErrors();

    expect(shelfOf($this->jacket))->toBe(['S' => 3, 'L' => 5, 'XL' => 2])
        ->and(app(FinishedStock::class)->of($this->jacket)->units)->toBe(10);
});

it('refuses a bad count or the wrong sizes with one message and saves nothing (AC-2)', function (string $case, string $key, string $message) {
    stockShelf($this->jacket, ['S' => 3, 'L' => 5, 'XL' => 2]);
    $before = ProductSizeStock::query()->orderBy('id')->get()->toArray();
    $sizes = sizeIds(['S' => '3', 'M' => '0', 'L' => '5', 'XL' => '2']);
    $medium = array_key_first(sizeIds(['M' => '']));
    $product = $this->jacket;

    // The shoe row types a letter in size 36 of the sneaker, whose list is 36 to 46: `{M}` then stands for that size.
    if ($case === 'shoe letters') {
        $product = demoProduct('PRD-SNK-003');
        $sizes = array_map(fn (): string => '1', sizeIds(array_fill_keys(range(36, 46), ''), SizeList::EU_36_TO_46));
        $medium = array_key_first(sizeIds(['36' => ''], SizeList::EU_36_TO_46));
        $sizes[$medium] = 'x';
    }

    $sizes = match ($case) {
        'shoe size added' => $sizes + sizeIds(['36' => '1'], SizeList::EU_36_TO_46),
        'XL missing' => array_diff_key($sizes, sizeIds(['XL' => ''])),
        'shoe letters' => $sizes,
        default => [$medium => $case] + $sizes,
    };

    actingAs($this->user)
        ->from(route('products.show', $product))
        ->put(route('products.stock.update', $product), ['sizes' => $sizes])
        ->assertRedirect(route('products.show', $product))
        ->assertInertiaFlashMissing('toast');

    $key = str_replace('{M}', (string) $medium, $key);

    expect(session('errors')->keys())->toBe([$key])
        ->and(session('errors')->get($key))->toBe([$message])
        ->and(ProductSizeStock::query()->orderBy('id')->get()->toArray())->toBe($before);
})->with([
    'negative' => ['-1', 'sizes.{M}', 'M: '.WHOLE_NUMBER],
    'decimal' => ['2.5', 'sizes.{M}', 'M: '.WHOLE_NUMBER],
    'letters' => ['abc', 'sizes.{M}', 'M: '.WHOLE_NUMBER],
    'above a million' => ['1000001', 'sizes.{M}', 'M: '.WHOLE_NUMBER],
    'a shoe size is named "Size 36"' => ['shoe letters', 'sizes.{M}', 'Size 36: '.WHOLE_NUMBER],
    'a size of the shoe list added' => ['shoe size added', 'sizes', SIZES_CHANGED],
    'a size missing' => ['XL missing', 'sizes', SIZES_CHANGED],
]);

it('deletes the finished stock of a product with the product, and nothing else (human decision 2026-10-23)', function () {
    $tote = demoProduct('PRD-TOT-004');
    stockShelf($this->jacket, ['S' => 3, 'M' => 4, 'L' => 5, 'XL' => 2]);
    stockShelf($tote, ['One size' => 4], SizeList::ONE_SIZE);
    $others = fn (): array => [
        Material::query()->orderBy('id')->get()->toArray(),
        StockMovement::query()->orderBy('id')->get()->toArray(),
        ProductSizeStock::query()->where('product_id', $tote->id)->get()->toArray(),
    ];
    $before = $others();

    actingAs($this->user)->delete(route('products.destroy', $this->jacket))->assertRedirect(route('products.index'));

    expect(ProductSizeStock::query()->where('product_id', $this->jacket->id)->count())->toBe(0)
        ->and(Product::withTrashed()->whereKey($this->jacket->id)->first()->trashed())->toBeTrue()
        ->and($others())->toBe($before);
});

it('sends a guest to login and answers 404 for the product of another workshop or a deleted one', function (string $who) {
    $product = match ($who) {
        'other workshop' => CurrentWorkshop::run(Workshop::factory()->create(), fn () => Product::factory()->create(['category' => 'apparel'])),
        'deleted' => tap(demoProduct('PRD-TSH-002'))->delete(),
        default => $this->jacket,
    };
    $payload = ['sizes' => sizeIds(['S' => '1', 'M' => '1', 'L' => '1', 'XL' => '1'])];

    if ($who === 'guest') {
        $this->put(route('products.stock.update', $product), $payload)->assertRedirect(route('login'));
    } else {
        actingAs($this->user)->put(route('products.stock.update', $product), $payload)->assertNotFound();
    }

    expect(ProductSizeStock::query()->count())->toBe(0);
})->with(['guest', 'other workshop', 'deleted']);

it('changes nothing but the count: capacity, cost, materials, history, dashboard, then a sale and an order (AC-3)', function () {
    $jacketPage = fn () => loadedPageProps(actingAs($this->user)->get(route('products.show', $this->jacket)));
    $dashboard = fn () => loadedPageProp(actingAs($this->user)->get(route('dashboard')), 'stats');
    $materials = fn (): array => Material::query()->orderBy('id')->pluck('quantity_on_hand', 'id')->all();
    $movements = fn (): int => StockMovement::query()->count();
    [$stats, $stock, $history] = [$dashboard(), $materials(), $movements()];
    $updatedAt = $this->jacket->fresh()->updated_at;

    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['sizes' => sizeIds(['S' => '3', 'M' => '4', 'L' => '5', 'XL' => '2'])])
        ->assertRedirect(route('products.show', $this->jacket));

    $page = $jacketPage();
    expect($page['product']['capacity']['max_producible'])->toBe(112)
        ->and($page['product']['cost']['unit_cost'])->toBe('35.7540')
        ->and($page['finished_stock']['units'])->toBe(14)
        ->and($materials())->toBe($stock)
        ->and($movements())->toBe($history)
        ->and($dashboard())->toBe($stats)
        ->and($stats['inventory_value'])->toBe('10544.3000')
        ->and($this->jacket->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();

    actingAs($this->user)->post(route('sales.store'), validSalePayload(['quantity' => '2']))->assertSessionHasNoErrors();
    actingAs($this->user)->post(route('orders.store'), validOrderPayload())->assertSessionHasNoErrors();

    expect(app(FinishedStock::class)->of($this->jacket)->units)->toBe(14);
});

it('refuses, under the lock, sizes of another list than the product has now (AC-2: the category changed after the request)', function () {
    $apparel = sizeIds(['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1]);
    $stale = demoProduct('PRD-JKT-001');
    $stale->loadMissing('category');
    $this->jacket->update(['category_id' => ProductCategory::query()->where('code', 'footwear')->value('id')]);
    actingAs($this->user);

    expect(fn () => app(UpdateFinishedStock::class)->handle($stale, $apparel))->toThrow(ValidationException::class, SIZES_CHANGED);
    expect(ProductSizeStock::query()->count())->toBe(0);
});
