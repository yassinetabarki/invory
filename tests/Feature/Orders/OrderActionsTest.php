<?php

use App\Actions\Orders\RecordOrder;
use App\Models\Client;
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

it('leaves no order, no line and no new client behind when a line fails to save', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['category' => 'bag']); // "One size": counted by quantity, so only the repeated product can fail

    // The second line repeats the product: the database's unique index refuses it, after the order and the first line were written.
    expect(fn () => app(RecordOrder::class)->handle([
        'client' => ['phone' => '71 234 567', 'name' => 'Boutique Lina', 'city' => 'Tunis'],
        'ordered_on' => '2026-10-15',
        'note' => null,
        'lines' => [['product_id' => $product->id, 'quantity' => 5], ['product_id' => $product->id, 'quantity' => 7]],
    ], $user))->toThrow(UniqueConstraintViolationException::class)
        ->and(Order::query()->count())->toBe(0)
        ->and(Client::query()->withoutGlobalScopes()->where('workshop_id', $user->workshop_id)->count())->toBe(0)
        ->and(OrderLine::query()->count())->toBe(0);
});
