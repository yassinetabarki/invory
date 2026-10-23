<?php

use App\Actions\Orders\RecordOrder;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/*
|--------------------------------------------------------------------------
| The order Actions (S-48-c)
|--------------------------------------------------------------------------
*/

it('leaves no order and no line behind when a line fails to save', function () {
    $product = Product::factory()->create(['category' => 'bag']); // "One size": counted by quantity, so only the repeated product can fail

    // The second line repeats the product: the database's unique index refuses it, after the order and the first line were written.
    expect(fn () => app(RecordOrder::class)->handle([
        'client' => 'Boutique Lina',
        'ordered_on' => '2026-10-15',
        'note' => null,
        'lines' => [['product_id' => $product->id, 'quantity' => 5], ['product_id' => $product->id, 'quantity' => 7]],
    ], User::factory()->create()))->toThrow(UniqueConstraintViolationException::class)
        ->and(Order::query()->count())->toBe(0)
        ->and(OrderLine::query()->count())->toBe(0);
});
