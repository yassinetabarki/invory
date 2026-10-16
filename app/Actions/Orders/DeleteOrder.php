<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Exceptions\OrderDeleteRefusedException;
use App\Models\Order;
use App\Services\Orders\OrderMessages;
use Illuminate\Support\Facades\DB;

final readonly class DeleteOrder
{
    /**
     * Soft delete: the order disappears from every page and the lines stay with it, so a later story can still point at them.
     * It never reads or writes materials, stock history or sales: deleting an order changes no figure.
     *
     * Refused while any unit is in cutting or ready to ship (S-49): their materials are out of stock and would be lost. The order row
     * is locked first, like every move and edit, and the lines are read under that lock, never from the model the route handed in.
     *
     * @throws OrderDeleteRefusedException units are in cutting or ready to ship; nothing is deleted
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            // An order deleted in the meantime is not found.
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $held = $locked->load('lines')->unitsHeld();

            if ($held > 0) {
                throw new OrderDeleteRefusedException(OrderMessages::deleteRefusal($locked->client, $held));
            }

            $order->delete();
        });
    }
}
