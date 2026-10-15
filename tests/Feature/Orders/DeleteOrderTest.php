<?php

use App\Models\Order;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

/*
|--------------------------------------------------------------------------
| Delete an order (S-48-b, AC-6 and AC-7 server side)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => $this->user = prepareDemo($this));

it('deletes the order, goes back to the same page of the list and changes no figure (AC-6, AC-7)', function () {
    $order = bookOrder();
    $other = bookOrder(['client' => 'Atelier Noor', 'ordered_on' => '2026-10-01']);
    $before = figuresOrdersLeaveAlone();

    actingAs($this->user)->from(route('orders.index', ['search' => 'lina', 'page' => 1]))
        ->delete(route('orders.destroy', $order))
        ->assertRedirect(route('orders.index', ['search' => 'lina', 'page' => 1]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Order deleted.']);

    // Hidden, not erased: the lines stay, the other order is untouched, and the deleted one is a 404 everywhere.
    assertSoftDeleted($order);
    expect($order->lines()->count())->toBe(2)
        ->and(pageProp(actingAs($this->user)->get(route('orders.index')), 'orders.data.*.client'))->toBe(['Atelier Noor'])
        ->and(Order::query()->whereKey($other->id)->exists())->toBeTrue()
        ->and($before['can produce'])->toBe(112)
        ->and(figuresOrdersLeaveAlone())->toBe($before);

    actingAs($this->user)->get(route('orders.edit', $order))->assertNotFound();
    actingAs($this->user)->delete(route('orders.destroy', $order))->assertNotFound();
});
