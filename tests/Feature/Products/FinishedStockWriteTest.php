<?php

use App\Actions\Products\UpdateFinishedStock;
use App\Models\Colour;
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
| "Update stock": saving a count per colour and size (S-56-c, INV-2-a, AC-1, AC-3)
|--------------------------------------------------------------------------
|
| `PUT products/{product}/stock` sets the finished units of the Denim Jacket (Apparel: S, M, L, XL) in each of its colours, White and
| Indigo here, and writes nothing else. The demo products are in the database; a factory product pins its category (F-68).
*/

const STOCK_UPDATED_TOAST = ['type' => 'success', 'message' => 'Stock updated.'];
const STOCK_CHANGED = 'The sizes or colours of Denim Jacket have changed. Enter the stock again.';
const WHOLE_NUMBER = 'Enter a whole number from 0 to 1,000,000.';

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->jacket = demoProduct('PRD-JKT-001');
    $this->jacket->colours()->attach(demoColour('IND')->id, ['workshop_id' => $this->jacket->workshop_id]);
});

/**
 * The body of the window: colour NAME → size label → what was typed, as `stock[colourId][sizeId]`.
 *
 * @param  array<string, array<string, int|string>>  $byColour
 * @return array<int, array<int, int|string|null>>
 */
function stockBody(array $byColour, string $list = SizeList::S_TO_XL): array
{
    $body = [];

    foreach ($byColour as $name => $bySize) {
        $body[Colour::query()->where('name', $name)->value('id')] = sizeIds($bySize, $list);
    }

    return $body;
}

/** The stock rows of a product as `['White' => ['S' => 3, 'L' => 5]]` (sizes without a row are left out). */
function shelfOf(Product $product): array
{
    $shelf = [];

    foreach (ProductSizeStock::query()->where('product_id', $product->id)->with(['size', 'colour'])->orderBy('id')->get() as $row) {
        $shelf[$row->colour->name][$row->size->label] = $row->units;
    }

    return $shelf;
}

/** Puts finished units on the shelf, `['S' => 3]`, straight in the table: in White unless a colour code is named. */
function stockShelf(Product $product, array $byLabel, string $list = SizeList::S_TO_XL, string $colour = 'WHT'): void
{
    foreach (sizeIds($byLabel, $list) as $sizeId => $units) {
        ProductSizeStock::factory()->create(['product_id' => $product->id, 'colour_id' => demoColour($colour)->id, 'size_id' => $sizeId, 'units' => $units]);
    }
}

it('sets the count of every colour and size at once; empty counts as 0 and a size at 0 has no row (AC-3)', function () {
    stockShelf($this->jacket, ['S' => 3, 'L' => 5, 'XL' => 2]);
    $updatedAt = $this->jacket->fresh()->updated_at;

    // Indigo M from 0 to 4: 14 units in all.
    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['stock' => stockBody([
            'White' => ['S' => '3', 'M' => '0', 'L' => '5', 'XL' => '2'],
            'Indigo' => ['S' => '0', 'M' => '4', 'L' => '0', 'XL' => '0'],
        ])])
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', STOCK_UPDATED_TOAST);

    expect(shelfOf($this->jacket))->toBe(['White' => ['S' => 3, 'L' => 5, 'XL' => 2], 'Indigo' => ['M' => 4]])
        ->and(app(FinishedStock::class)->of($this->jacket)->units)->toBe(14);

    // An empty field is 0: White L and Indigo M go, the rest is as typed.
    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['stock' => stockBody([
            'White' => ['S' => '3', 'M' => '', 'L' => '', 'XL' => '2'],
            'Indigo' => ['S' => '1', 'M' => '', 'L' => '', 'XL' => ''],
        ])])
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertSessionHasNoErrors();

    expect(shelfOf($this->jacket))->toBe(['White' => ['S' => 3, 'XL' => 2], 'Indigo' => ['S' => 1]])
        ->and(app(FinishedStock::class)->of($this->jacket)->units)->toBe(6)
        ->and($this->jacket->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('refuses a bad count with the message of its size on its own colour and size, and saves nothing (AC-3)', function (string $typed, string $sku, string $message) {
    stockShelf($this->jacket, ['S' => 3, 'L' => 5, 'XL' => 2]);
    $before = ProductSizeStock::query()->orderBy('id')->get()->toArray();
    $product = demoProduct($sku);
    $isJacket = $product->is($this->jacket);
    $list = $isJacket ? SizeList::S_TO_XL : SizeList::EU_36_TO_46;
    $sizeId = array_key_first(sizeIds([$isJacket ? 'M' : '36' => ''], $list));
    $body = $isJacket
        ? stockBody(['White' => ['S' => '3', 'M' => '0', 'L' => '5', 'XL' => '2'], 'Indigo' => ['S' => '0', 'M' => '0', 'L' => '0', 'XL' => '0']])
        : stockBody(['White' => array_fill_keys(range(36, 46), '1')], $list);
    $colourId = array_key_last($body);
    $body[$colourId][$sizeId] = $typed;

    actingAs($this->user)
        ->from(route('products.show', $product))
        ->put(route('products.stock.update', $product), ['stock' => $body])
        ->assertRedirect(route('products.show', $product))
        ->assertInertiaFlashMissing('toast');

    expect(session('errors')->keys())->toBe(["stock.{$colourId}.{$sizeId}"])
        ->and(session('errors')->get("stock.{$colourId}.{$sizeId}"))->toBe([$message])
        ->and(ProductSizeStock::query()->orderBy('id')->get()->toArray())->toBe($before);
})->with([
    'negative' => ['-1', 'PRD-JKT-001', 'M: '.WHOLE_NUMBER],
    'decimal' => ['2.5', 'PRD-JKT-001', 'M: '.WHOLE_NUMBER],
    'letters' => ['x', 'PRD-JKT-001', 'M: '.WHOLE_NUMBER],
    'above a million' => ['1000001', 'PRD-JKT-001', 'M: '.WHOLE_NUMBER],
    'a shoe size is named "Size 36"' => ['x', 'PRD-SNK-003', 'Size 36: '.WHOLE_NUMBER],
]);

it('refuses colours or sizes that are not exactly the product\'s with one message on stock, and saves nothing (AC-3)', function (string $case) {
    stockShelf($this->jacket, ['S' => 3, 'L' => 5, 'XL' => 2]);
    $before = ProductSizeStock::query()->orderBy('id')->get()->toArray();
    $white = ['S' => '3', 'M' => '0', 'L' => '5', 'XL' => '2'];
    $indigo = ['S' => '0', 'M' => '4', 'L' => '0', 'XL' => '0'];
    $foreignIndigo = CurrentWorkshop::run(Workshop::factory()->create(), fn (): int => Colour::query()->where('code', 'IND')->value('id'));
    $attachBlack = fn () => $this->jacket->colours()->attach(demoColour('BLK')->id, ['workshop_id' => $this->jacket->workshop_id]);

    $body = match ($case) {
        'a colour missing' => stockBody(['White' => $white]),
        // The window was opened before Black was added to the product.
        'a colour added to the product since' => tap(stockBody(['White' => $white, 'Indigo' => $indigo]), $attachBlack),
        'a colour the product does not have' => stockBody(['White' => $white, 'Indigo' => $indigo, 'Black' => $indigo]),
        'a colour of another workshop' => stockBody(['White' => $white]) + [$foreignIndigo => sizeIds($indigo)],
        'a size missing' => stockBody(['White' => $white, 'Indigo' => ['S' => '0', 'M' => '4', 'L' => '0']]),
        'a size of another list' => array_replace_recursive(stockBody(['White' => $white, 'Indigo' => $indigo]), [demoColour('IND')->id => sizeIds(['36' => '1'], SizeList::EU_36_TO_46)]),
        'no stock at all' => [],
    };

    actingAs($this->user)
        ->from(route('products.show', $this->jacket))
        ->put(route('products.stock.update', $this->jacket), ['stock' => $body])
        ->assertRedirect(route('products.show', $this->jacket))
        ->assertInertiaFlashMissing('toast');

    expect(session('errors')->keys())->toBe(['stock'])
        ->and(session('errors')->get('stock'))->toBe([STOCK_CHANGED])
        ->and(ProductSizeStock::query()->orderBy('id')->get()->toArray())->toBe($before);
})->with(['a colour missing', 'a colour added to the product since', 'a colour the product does not have', 'a colour of another workshop', 'a size missing', 'a size of another list', 'no stock at all']);

it('checks the colours and sizes again under the product\'s lock, and saves nothing (AC-3: they changed after the request)', function () {
    stockShelf($this->jacket, ['S' => 3]);
    $before = ProductSizeStock::query()->orderBy('id')->get()->toArray();
    $stale = demoProduct('PRD-JKT-001');
    $body = fn (): array => array_map(fn (array $sizes): array => array_map('intval', $sizes), stockBody([
        'White' => ['S' => 9, 'M' => 1, 'L' => 1, 'XL' => 1],
        'Indigo' => ['S' => 1, 'M' => 1, 'L' => 1, 'XL' => 1],
    ]));
    actingAs($this->user);

    // A colour was taken off after the request was checked.
    $this->jacket->colours()->detach(demoColour('IND')->id);

    expect(fn () => app(UpdateFinishedStock::class)->handle($stale, $body()))->toThrow(ValidationException::class, STOCK_CHANGED);

    // The category moved to another size list after the request was checked.
    $this->jacket->colours()->attach(demoColour('IND')->id, ['workshop_id' => $this->jacket->workshop_id]);
    $this->jacket->update(['category_id' => ProductCategory::query()->where('code', 'footwear')->value('id')]);

    expect(fn () => app(UpdateFinishedStock::class)->handle($stale, $body()))->toThrow(ValidationException::class, STOCK_CHANGED)
        ->and(ProductSizeStock::query()->orderBy('id')->get()->toArray())->toBe($before);
});

it('deletes the finished stock of every colour of a product with the product, and nothing else (human decision 2026-10-23)', function () {
    $tote = demoProduct('PRD-TOT-004');
    stockShelf($this->jacket, ['S' => 3, 'M' => 4, 'L' => 5, 'XL' => 2]);
    stockShelf($this->jacket, ['S' => 1, 'XL' => 6], colour: 'IND');
    stockShelf($tote, ['One size' => 4], SizeList::ONE_SIZE);
    $others = fn (): array => [
        Material::query()->orderBy('id')->get()->toArray(),
        StockMovement::query()->orderBy('id')->get()->toArray(),
        ProductSizeStock::query()->where('product_id', $tote->id)->get()->toArray(),
    ];
    $before = $others();

    expect(ProductSizeStock::query()->where('product_id', $this->jacket->id)->count())->toBe(6);

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
    $payload = ['stock' => stockBody(['White' => ['S' => '1', 'M' => '1', 'L' => '1', 'XL' => '1'], 'Indigo' => ['S' => '1', 'M' => '1', 'L' => '1', 'XL' => '1']])];

    if ($who === 'guest') {
        $this->put(route('products.stock.update', $product), $payload)->assertRedirect(route('login'));
    } else {
        actingAs($this->user)->put(route('products.stock.update', $product), $payload)->assertNotFound();
    }

    expect(ProductSizeStock::query()->count())->toBe(0);
})->with(['guest', 'other workshop', 'deleted']);

it('changes nothing but the count: capacity, cost, materials, history, dashboard, then a sale and an order (AC-1)', function () {
    $jacketPage = fn () => loadedPageProps(actingAs($this->user)->get(route('products.show', $this->jacket)));
    $dashboard = fn () => loadedPageProp(actingAs($this->user)->get(route('dashboard')), 'stats');
    $materials = fn (): array => Material::query()->orderBy('id')->pluck('quantity_on_hand', 'id')->all();
    $movements = fn (): int => StockMovement::query()->count();
    [$stats, $stock, $history] = [$dashboard(), $materials(), $movements()];
    $updatedAt = $this->jacket->fresh()->updated_at;

    actingAs($this->user)
        ->put(route('products.stock.update', $this->jacket), ['stock' => stockBody([
            'White' => ['S' => '3', 'M' => '0', 'L' => '5', 'XL' => '2'],
            'Indigo' => ['S' => '0', 'M' => '4', 'L' => '0', 'XL' => '0'],
        ])])
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

    expect(app(FinishedStock::class)->of($this->jacket)->units)->toBe(14)
        ->and(shelfOf($this->jacket))->toBe(['White' => ['S' => 3, 'L' => 5, 'XL' => 2], 'Indigo' => ['M' => 4]]);
});
