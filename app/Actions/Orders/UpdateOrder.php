<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOrder
{
    public function __construct(private SyncOrderLines $syncLines) {}

    /**
     * Edits an order and its lines in one transaction. Stock-neutral, and the order keeps the user who recorded it.
     *
     * @param  array<string, mixed>  $data  validated input: `client`, `ordered_on` (Y-m-d), optional `note`, and `lines` (a list of `product_id` and `quantity`)
     */
    public function handle(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            // Two people saving the same order take turns; an order deleted in the meantime is not found.
            Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $order->fill(Arr::only($data, ['client', 'ordered_on', 'note']))->save();
            $this->syncLines->handle($order, $data['lines']);

            return $order;
        });
    }
}
