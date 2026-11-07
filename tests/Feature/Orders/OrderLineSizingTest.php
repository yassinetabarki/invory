<?php

use App\Enums\OrderLineSizeMode;
use App\Models\Order;
use App\Services\Orders\OrderBook;
use App\Services\Orders\OrderLineSizing;

/*
|--------------------------------------------------------------------------
| How a line counts, and how a new line of a product would (INV-18-b, AC-7)
|--------------------------------------------------------------------------
|
| `OrderLineSizing` is the one place that decides. A saved line answers from `OrderLine::countsBySize()`; a product, from its sizes
| (`ProductSizes`). Both answer the same whether the order page's query added the size counts or the models are plain.
*/

beforeEach(fn () => prepareDemo($this));

it('says how a line counts and how a new line of its product would count', function (Closure $book, OrderLineSizeMode $line, OrderLineSizeMode $product, bool $precomputed) {
    $order = $book();
    $saved = $precomputed
        ? app(OrderBook::class)->load($order)->lines->firstOrFail()
        : $order->lines()->with('product')->firstOrFail();
    $sizing = app(OrderLineSizing::class);

    expect($sizing->forLine($saved))->toBe($line)
        ->and($sizing->forProduct($saved->product))->toBe($product);
})->with([
    'a Denim Jacket line recorded by size' => [fn (): Order => bookSizedOrder(['M' => 20]), OrderLineSizeMode::BySize, OrderLineSizeMode::BySize],
    'a Leather Tote line (one size)' => [fn (): Order => bookOrder([], [[demoProduct('PRD-TOT-004'), 12]]), OrderLineSizeMode::OneSize, OrderLineSizeMode::OneSize],
    'a Denim Jacket line recorded before sizes' => [fn (): Order => bookOrder([], [[demoProduct('PRD-JKT-001'), 50]]), OrderLineSizeMode::BeforeSizes, OrderLineSizeMode::BySize],
])->with([
    'plain models' => false,
    'with the order page\'s size counts' => true,
]);
