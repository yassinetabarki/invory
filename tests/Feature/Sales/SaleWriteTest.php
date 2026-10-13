<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Record / edit / delete a sale through the routes (S-14-b)
|--------------------------------------------------------------------------
|
| Rules: `invory-costing-domain` skill, "Validation rules". The clock is frozen at 2026-10-08.
*/

beforeEach(function () {
    $this->travelTo('2026-10-08 12:00:00');
    $this->user = User::factory()->create();
    $this->product = Product::factory()->create();
});

/** A valid payload for the product of the test, with overrides. A `product` override ('draft', 'archived', 'deleted') makes that product and sells it. */
function saleBody(Product $product, array $override = []): array
{
    $state = $override['product'] ?? null;
    unset($override['product']);

    if ($state === 'draft' || $state === 'archived') {
        $product = Product::factory()->{$state}()->create();
    } elseif ($state === 'deleted') {
        $product = Product::factory()->create();
        $product->delete();
    }

    return ['product_id' => $product->id, 'quantity' => '3', 'unit_price' => '89', 'sold_on' => '2026-10-08', 'note' => 'Web shop', ...$override];
}

it('records, edits and deletes a sale and says so', function () {
    actingAs($this->user)->from(route('sales.index', ['page' => 2]))
        ->post(route('sales.store'), saleBody($this->product))
        ->assertRedirect(route('sales.index', ['page' => 2]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Sale recorded.']);

    $sale = Sale::query()->firstOrFail();

    expect($sale->only(['product_id', 'quantity', 'unit_price', 'note', 'user_id']))
        ->toBe(['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => '89.0000', 'note' => 'Web shop', 'user_id' => $this->user->id]);

    actingAs($this->user)->put(route('sales.update', $sale), saleBody($this->product, ['quantity' => '9', 'note' => '']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Sale updated.']);

    expect($sale->fresh()->only(['quantity', 'note']))->toBe(['quantity' => 9, 'note' => null]);

    actingAs($this->user)->delete(route('sales.destroy', $sale))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Sale deleted.']);

    expect(Sale::query()->count())->toBe(0);
});

it('rejects invalid input with an error on the field and saves nothing', function (array $override, string $field) {
    $sale = Sale::factory()->for($this->product)->create();

    actingAs($this->user)->from(route('sales.index'))->post(route('sales.store'), saleBody($this->product, $override))
        ->assertRedirect(route('sales.index'))
        ->assertSessionHasErrors($field)
        ->assertInertiaFlashMissing('toast');

    // The same rules on edit (the sale's own product is active here, so the product rows behave the same).
    actingAs($this->user)->put(route('sales.update', $sale), saleBody($this->product, $override))
        ->assertSessionHasErrors($field);

    expect(Sale::query()->count())->toBe(1);
})->with([
    'missing product' => [['product_id' => null], 'product_id'],
    'unknown product' => [['product_id' => 900000000], 'product_id'],
    'a draft product' => [['product' => 'draft'], 'product_id'],
    'an archived product' => [['product' => 'archived'], 'product_id'],
    'a deleted product' => [['product' => 'deleted'], 'product_id'],
    'array product' => [['product_id' => [1]], 'product_id'],
    'zero quantity' => [['quantity' => '0'], 'quantity'],
    'fractional quantity' => [['quantity' => '1.5'], 'quantity'],
    'quantity over a million' => [['quantity' => '1000001'], 'quantity'],
    'negative unit price' => [['unit_price' => '-0.0001'], 'unit_price'],
    'unit price with 5 decimals' => [['unit_price' => '1.12345'], 'unit_price'],
    'exponent unit price' => [['unit_price' => '1e3'], 'unit_price'],
    'unit price over the column' => [['unit_price' => '100000000'], 'unit_price'],
    'tomorrow' => [['sold_on' => '2026-10-09'], 'sold_on'],
    'a date in the wrong format' => [['sold_on' => '08/10/2026'], 'sold_on'],
    'a note over 255 characters' => [['note' => str_repeat('a', 256)], 'note'],
]);

it('accepts the boundary values', function (array $override) {
    actingAs($this->user)->post(route('sales.store'), saleBody($this->product, $override))->assertSessionHasNoErrors();

    expect(Sale::query()->count())->toBe(1);
})->with([
    'a million units' => [['quantity' => '1000000']],
    'a free sale' => [['unit_price' => '0']],
    'the biggest price' => [['unit_price' => '99999999.9999']],
    'today' => [['sold_on' => '2026-10-08']],
    'a note of 255 characters' => [['note' => str_repeat('a', 255)]],
    'no note' => [['note' => null]],
]);

it('says why a product can not be sold', function () {
    actingAs($this->user)->post(route('sales.store'), saleBody($this->product, ['product' => 'draft']))
        ->assertSessionHasErrors(['product_id' => 'Only active products can be sold.']);

    actingAs($this->user)->post(route('sales.store'), saleBody($this->product, ['product' => 'deleted']))
        ->assertSessionHasErrors(['product_id' => 'The selected product id is invalid.']);
});

it('keeps the current product of an edited sale valid once it is archived, and only that one', function () {
    $sale = Sale::factory()->for($this->product)->create();
    $this->product->update(['status' => ProductStatus::Archived]);

    actingAs($this->user)->put(route('sales.update', $sale), saleBody($this->product, ['quantity' => '2']))->assertSessionHasNoErrors();
    expect($sale->fresh()->quantity)->toBe(2);

    // Moving the sale to another archived product is refused, and so is recording a new sale of the archived one.
    $archived = Product::factory()->archived()->create();
    actingAs($this->user)->put(route('sales.update', $sale), saleBody($archived))->assertSessionHasErrors('product_id');
    actingAs($this->user)->post(route('sales.store'), saleBody($this->product))->assertSessionHasErrors('product_id');
});

it('answers 404 for an unknown sale before it validates anything', function () {
    actingAs($this->user)->put(route('sales.update', 900000000), ['quantity' => '0'])->assertNotFound()->assertSessionHasNoErrors();
    actingAs($this->user)->delete(route('sales.destroy', 900000000))->assertNotFound();
});

it('redirects guests to login on every sales route', function (string $method, string $route) {
    $parameter = $route === 'sales.index' || $route === 'sales.store' ? [] : [Sale::factory()->create()];

    $this->{$method}(route($route, $parameter))->assertRedirect(route('login'));
})->with([['get', 'sales.index'], ['post', 'sales.store'], ['put', 'sales.update'], ['delete', 'sales.destroy']]);
