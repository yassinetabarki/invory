<?php

use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\SizeList;
use App\Models\Workshop;
use App\Services\Workshops\CurrentWorkshop;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Record and edit an order through the routes (S-48-c: AC-2, AC-3, AC-4, AC-6 and AC-7 server side)
|--------------------------------------------------------------------------
|
| Rules: the card's Validation block. The clock is frozen at 2026-10-15, the day of the card's example.
*/

beforeEach(function () {
    $this->user = prepareDemo($this);
    $this->travelTo('2026-10-15 12:00:00');
});

/**
 * A valid payload with overrides. A line is `['product' => <which>, 'quantity' => <as typed>]`, where which is `jacket` (Denim Jacket),
 * `jeans` (Denim Jeans 5-pocket), `tshirt` (Classic White T-shirt), `draft` (Canvas Chore Jacket), `tote` (Leather Tote, a "One size"
 * product), `archived` (a new archived bag), `new` (a new active bag), `foreign` (a product of another workshop), `missing` (an id
 * nobody has) or `null` (nothing chosen). Datasets are built before the app boots, so they name products instead of holding ids.
 * A line posts its quantity as `quantity` and as M of `sizes` (S-60): the server uses the one that fits the product, ignoring the other.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function orderBody(array $overrides = []): array
{
    $overrides += ['ordered_on' => '2026-10-15'];

    if (isset($overrides['lines'])) {
        $overrides['lines'] = array_map(static fn (array $line): array => [
            'product_id' => match ($line['product']) {
                null => null,
                'jacket' => pid(1),
                'tshirt' => pid(2),
                'jeans' => pid(5),
                'draft' => pid(6),
                'tote' => pid(4),
                'archived' => Product::factory()->archived()->create(['category' => 'bag'])->id,
                'new' => Product::factory()->create(['category' => 'bag'])->id,
                'foreign' => CurrentWorkshop::run(Workshop::factory()->create(), fn () => Product::factory()->create()->id),
                'missing' => NO_SUCH_ID,
            },
            'quantity' => $line['quantity'],
            'sizes' => sizeIds(['M' => $line['quantity']]),
        ], $overrides['lines']);
    }

    return validOrderPayload($overrides);
}

/** Every line of the order that counts as one size ("One size" product, or recorded before sizes) holds one row equal to the line (ADR-0016). */
function expectLinesCountedAsOneSize(Order $order): void
{
    $oneSize = SizeList::query()->where('code', SizeList::ONE_SIZE)->firstOrFail();
    $lines = $order->lines()->with('sizes.size')->get()->where('size_list_id', $oneSize->id);

    expect($lines)->not->toBeEmpty();

    foreach ($lines as $line) {
        expect($line->size_list_id)->toBe($oneSize->id)
            ->and($line->sizes->map(fn ($row): array => [$row->size->label, $row->quantity, $row->in_cutting, $row->ready_to_ship, $row->shipped])->all())
            ->toBe([['One size', $line->quantity, 0, 0, 0]]);
    }
}

/** The lines of an order as the list reads them: `50 × Denim Jacket`. */
function orderLinesAsListed(array $row): array
{
    return array_map(static fn (array $line): string => "{$line['quantity']} × {$line['product']['name']}", $row['lines']);
}

it('records Boutique Lina\'s order and lands on its page, where the list shows it first (AC-2, S-49 AC-1)', function () {
    $response = actingAs($this->user)->post(route('orders.store'), orderBody());
    $order = Order::query()->with('lines')->firstOrFail();

    $response->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order recorded.']);

    expect($order->only(['client', 'note', 'user_id']))
        ->toBe(['client' => 'Boutique Lina', 'note' => 'Deliver before 30 Oct, in 2 boxes', 'user_id' => $this->user->id])
        ->and($order->ordered_on->toDateString())->toBe('2026-10-15')
        ->and($order->workshop_id)->toBe($this->user->workshop_id)
        ->and($order->lines->map(fn (OrderLine $line): array => $line->only(['product_id', 'quantity', 'position', 'workshop_id']))->all())->toBe([
            ['product_id' => pid(1), 'quantity' => 50, 'position' => 0, 'workshop_id' => $order->workshop_id],
            ['product_id' => pid(5), 'quantity' => 30, 'position' => 1, 'workshop_id' => $order->workshop_id],
        ]);

    // Both lines are counted by size, and each equals the sum of its rows (sizedFigures checks it).
    expect(sizedFigures($order))->toBe([
        pid(1) => ['S' => [10, 0, 0, 0], 'M' => [20, 0, 0, 0], 'L' => [15, 0, 0, 0], 'XL' => [5, 0, 0, 0]],
        pid(5) => ['M' => [30, 0, 0, 0]],
    ]);

    $row = pageProp(actingAs($this->user)->get(route('orders.index'))->assertOk(), 'orders.data.0');

    expect($row)->toMatchArray([
        'ordered_on' => '2026-10-15', 'client' => 'Boutique Lina', 'units' => 80, 'note' => 'Deliver before 30 Oct, in 2 boxes',
    ])->and(orderLinesAsListed($row))->toBe(['50 × Denim Jacket', '30 × Denim Jeans 5-pocket']);
});

it('refuses a mistake with the card\'s message under its field and saves nothing (AC-3, AC-4)', function (array $override, string $field, string $message) {
    actingAs($this->user)->from(route('orders.create'))->post(route('orders.store'), orderBody($override))
        ->assertRedirect(route('orders.create'))
        ->assertInertiaFlashMissing('toast');

    // Only the field of the case is flagged: the first line of a repeated product stays clean.
    expect(session('errors')->keys())->toBe([$field])
        ->and(session('errors')->get($field))->toBe([$message])
        ->and(Order::query()->count())->toBe(0)
        ->and(OrderLine::query()->count())->toBe(0);
})->with([
    'client empty' => [['client' => ''], 'client', 'The client field is required.'],
    'client of 101 characters' => [['client' => str_repeat('a', 101)], 'client', 'The client field must not be greater than 100 characters.'],
    'order date empty' => [['ordered_on' => ''], 'ordered_on', 'The order date field is required.'],
    'order date tomorrow' => [['ordered_on' => '2026-10-16'], 'ordered_on', 'The order date can\'t be in the future.'],
    'note of 1001 characters' => [['note' => str_repeat('a', 1001)], 'note', 'The note field must not be greater than 1000 characters.'],
    'no lines' => [['lines' => []], 'lines', 'Add at least one product.'],
    'a line without a product' => [['lines' => [['product' => null, 'quantity' => '5']]], 'lines.0.product_id', 'Choose a product.'],
    'quantity empty, on a "One size" product' => [['lines' => [['product' => 'tote', 'quantity' => '']]], 'lines.0.quantity', 'The quantity must be a whole number from 1 to 1,000,000.'],
    'quantity 0, on a "One size" product' => [['lines' => [['product' => 'tote', 'quantity' => '0']]], 'lines.0.quantity', 'The quantity must be a whole number from 1 to 1,000,000.'],
    'quantity 2.5, on a "One size" product' => [['lines' => [['product' => 'tote', 'quantity' => '2.5']]], 'lines.0.quantity', 'The quantity must be a whole number from 1 to 1,000,000.'],
    'quantity 1,000,001, on a "One size" product' => [['lines' => [['product' => 'tote', 'quantity' => '1000001']]], 'lines.0.quantity', 'The quantity must be a whole number from 1 to 1,000,000.'],
    'Denim Jacket twice, flagged on the second line' => [
        ['lines' => [['product' => 'jacket', 'quantity' => '5'], ['product' => 'jacket', 'quantity' => '7']]],
        'lines.1.product_id', 'Denim Jacket is already on this order.',
    ],
    '51 products' => [['lines' => array_fill(0, 51, ['product' => 'new', 'quantity' => '1'])], 'lines', 'An order can hold up to 50 products.'],
    'a draft product' => [['lines' => [['product' => 'draft', 'quantity' => '5']]], 'lines.0.product_id', 'Only active products can be ordered.'],
    'an archived product' => [['lines' => [['product' => 'archived', 'quantity' => '5']]], 'lines.0.product_id', 'Only active products can be ordered.'],
    'a product nobody has' => [['lines' => [['product' => 'missing', 'quantity' => '5']]], 'lines.0.product_id', 'The selected product is invalid.'],
    'another workshop\'s product, like a product nobody has' => [['lines' => [['product' => 'foreign', 'quantity' => '5']]], 'lines.0.product_id', 'The selected product is invalid.'],
]);

it('changes Denim Jeans to 40, adds 20 T-shirts, keeps the Jeans line and lands on the order\'s page (AC-6, S-49 AC-1)', function () {
    $order = bookOrder();
    $jeansLine = $order->lines()->where('product_id', pid(5))->firstOrFail();

    actingAs($this->user)->put(route('orders.update', $order), orderBody(['lines' => [
        ['product' => 'jacket', 'quantity' => '50'], ['product' => 'jeans', 'quantity' => '40'], ['product' => 'tshirt', 'quantity' => '20'],
    ]]))->assertRedirect(route('orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order updated.']);

    $row = pageProp(actingAs($this->user)->get(route('orders.index'))->assertOk(), 'orders.data.0');

    expect(orderLinesAsListed($row))->toBe(['50 × Denim Jacket', '40 × Denim Jeans 5-pocket', '20 × Classic White T-shirt'])
        ->and($row['units'])->toBe(110)
        ->and(Order::query()->count())->toBe(1)
        ->and($jeansLine->fresh()->only(['quantity', 'position']))->toBe(['quantity' => 40, 'position' => 1]);

    expectLinesCountedAsOneSize($order);
});

it('saves two products swapped between lines and drops a product that is no longer listed', function () {
    $order = bookOrder();
    $jacketLine = $order->lines()->where('product_id', pid(1))->firstOrFail();

    actingAs($this->user)->put(route('orders.update', $order), orderBody(['lines' => [
        ['product' => 'jeans', 'quantity' => '30'], ['product' => 'jacket', 'quantity' => '50'],
    ]]))->assertSessionHasNoErrors();

    expect($order->lines()->get()->map(fn (OrderLine $line): array => $line->only(['product_id', 'position']))->all())
        ->toBe([['product_id' => pid(5), 'position' => 0], ['product_id' => pid(1), 'position' => 1]])
        ->and($jacketLine->fresh()->position)->toBe(1);

    actingAs($this->user)->put(route('orders.update', $order), orderBody(['lines' => [['product' => 'tshirt', 'quantity' => '5']]]))
        ->assertSessionHasNoErrors();

    expect($order->lines()->get()->map(fn (OrderLine $line): array => $line->only(['product_id', 'quantity', 'position']))->all())
        ->toBe([['product_id' => pid(2), 'quantity' => 5, 'position' => 0]]);
});

it('keeps a product that is no longer active on the order that has it, and nowhere else (AC-7)', function (string $state, string $message) {
    $order = bookOrder();
    $other = bookOrder(['client' => 'Atelier Noor'], [[demoProduct('PRD-TSH-002'), 5]]);
    $jacket = demoProduct('PRD-JKT-001');

    // Both bodies are built first: a deleted product can no longer be looked up by its SKU.
    $keep = orderBody(['lines' => [['product' => 'jacket', 'quantity' => '60'], ['product' => 'jeans', 'quantity' => '30']]]);
    $elsewhere = orderBody(['lines' => [['product' => 'tshirt', 'quantity' => '5'], ['product' => 'jacket', 'quantity' => '1']]]);

    match ($state) {
        'archived' => $jacket->update(['status' => ProductStatus::Archived]),
        'draft' => $jacket->update(['status' => ProductStatus::Draft]),
        'deleted' => $jacket->delete(),
    };

    actingAs($this->user)->put(route('orders.update', $order), $keep)->assertSessionHasNoErrors();

    expect($order->lines()->where('product_id', $jacket->id)->value('quantity'))->toBe(60);

    // The same product on an order it isn't on is refused (a deleted one like a product nobody has).
    actingAs($this->user)->put(route('orders.update', $other), $elsewhere)->assertSessionHasErrors(['lines.1.product_id' => $message]);

    expect($other->lines()->count())->toBe(1);
})->with([
    'archived' => ['archived', 'Only active products can be ordered.'],
    'set back to draft' => ['draft', 'Only active products can be ordered.'],
    'deleted' => ['deleted', 'The selected product is invalid.'],
]);

it('changes no stock, sale or figure, recording or editing (AC-7)', function () {
    $before = figuresOrdersLeaveAlone();

    actingAs($this->user)->post(route('orders.store'), orderBody())->assertSessionHasNoErrors();
    $order = Order::query()->firstOrFail();

    expect($before['can produce'])->toBe(112)
        ->and(figuresOrdersLeaveAlone())->toBe($before);

    actingAs($this->user)->put(route('orders.update', $order), orderBody(['lines' => [['product' => 'jacket', 'quantity' => '99']]]))
        ->assertSessionHasNoErrors();

    expect(figuresOrdersLeaveAlone())->toBe($before);
});
