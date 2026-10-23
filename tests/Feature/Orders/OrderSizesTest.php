<?php

use App\Enums\OrderLineSizeMode;
use App\Models\Order;
use App\Services\Orders\OrderLineSizing;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| The sizes of an order line on the order's page (S-60-b: S-60 AC-3 and AC-5, S-61 AC-1, server side)
|--------------------------------------------------------------------------
|
| Boutique Lina's Denim Jacket S 10 · M 20 · L 15 · XL 5. The shape of the props is in OrderPageTest, the form props in OrderPagesTest.
*/

beforeEach(fn () => $this->user = prepareDemo($this));

function sizedOrderPage(Order $order): TestResponse
{
    return actingAs(test()->user)->get(route('orders.show', $order))->assertOk();
}

it('sends a line counted by size with a row per size, in size order, and the line\'s figures as their sums (S-60 AC-3, S-61 AC-1)', function () {
    $response = sizedOrderPage(bookSizedOrder());

    expect(pageProp($response, 'order.lines.0.size_mode'))->toBe('by_size')
        ->and(pageProp($response, 'order.lines.0.sizes.*.size.label'))->toBe(['S', 'M', 'L', 'XL'])
        ->and(pageProp($response, 'order.lines.0.sizes.*.quantity'))->toBe([10, 20, 15, 5])
        ->and(pageProp($response, 'order.lines.0.sizes.*.to_start'))->toBe([10, 20, 15, 5])
        ->and(pageProp($response, 'order.lines.0.sizes.*.in_cutting'))->toBe([0, 0, 0, 0])
        ->and(pageProp($response, 'order.lines.0.sizes.*.ready_to_ship'))->toBe([0, 0, 0, 0])
        ->and(pageProp($response, 'order.lines.0.sizes.*.shipped'))->toBe([0, 0, 0, 0])
        ->and(pageProp($response, 'order.lines.0'))->toMatchArray(['quantity' => 50, 'to_start' => 50]);
});

it('tells how a line counts: by size, as a "One size" product, or as recorded before sizes (S-60 AC-5)', function (callable $book, string $mode, string $label, int $quantity) {
    $response = sizedOrderPage($book());

    expect(pageProp($response, 'order.lines.0.size_mode'))->toBe($mode)
        ->and(pageProp($response, 'order.lines.0.sizes.*.size.label'))->toBe([$label])
        ->and(pageProp($response, 'order.lines.0.sizes.*.quantity'))->toBe([$quantity]);
})->with([
    'counted by size' => [fn () => bookSizedOrder(['M' => 20]), 'by_size', 'M', 20],
    'a Leather Tote line' => [fn () => bookOrder([], [[demoProduct('PRD-TOT-004'), 12]]), 'one_size', 'One size', 12],
    'a Denim Jacket line recorded before sizes' => [fn () => bookOrder([], [[demoProduct('PRD-JKT-001'), 50]]), 'before_sizes', 'One size', 50],
]);

it('answers the same from plain models, without the order pages\' counts (what S-60-c and S-61-a will ask)', function () {
    $sized = bookSizedOrder()->lines()->with('product')->firstOrFail();
    $tote = bookOrder([], [[demoProduct('PRD-TOT-004'), 12]])->lines()->with('product')->firstOrFail();
    $beforeSizes = bookOrder([], [[demoProduct('PRD-JKT-001'), 50]])->lines()->with('product')->firstOrFail();
    $sizing = app(OrderLineSizing::class);

    expect($sizing->forLine($sized))->toBe(OrderLineSizeMode::BySize)
        ->and($sizing->forLine($tote))->toBe(OrderLineSizeMode::OneSize)
        ->and($sizing->forLine($beforeSizes))->toBe(OrderLineSizeMode::BeforeSizes)
        ->and($sizing->forProduct(demoProduct('PRD-JKT-001')))->toBe(OrderLineSizeMode::BySize)
        ->and($sizing->forProduct(demoProduct('PRD-SNK-003')))->toBe(OrderLineSizeMode::BySize)
        ->and($sizing->forProduct(demoProduct('PRD-TOT-004')))->toBe(OrderLineSizeMode::OneSize);
});
