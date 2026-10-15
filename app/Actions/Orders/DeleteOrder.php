<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;

final readonly class DeleteOrder
{
    /**
     * Soft delete: the order disappears from every page and the lines stay with it, so a later story can still point at them.
     * It never reads or writes materials, stock history or sales: deleting an order changes no figure.
     */
    public function handle(Order $order): void
    {
        $order->delete();
    }
}
