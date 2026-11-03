<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

final readonly class DeleteOrder
{
    /**
     * Soft delete: the order disappears from every page and the lines stay with it, so a later story can still point at them.
     * It never reads or writes materials, stock history, sales or the shelf: deleting an order changes no figure, and delivered units are
     * not put back (INV-14, AC-6). Since INV-14 an order holds no materials and no production, so it is never refused (ADR-0026).
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            // The order row is locked like every edit and delivery; an order deleted in the meantime is not found.
            Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $order->delete();
        });
    }
}
