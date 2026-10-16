<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateOrder
{
    public function __construct(private SyncOrderLines $syncLines) {}

    /**
     * Edits an order and its lines in one transaction. Stock-neutral, and the order keeps the user who recorded it. The lines are synced after the
     * order's row lock, which every move also takes first, so a started line (S-49) is checked against its saved counts: the form
     * request's own check ran on unlocked data.
     *
     * @param  array<string, mixed>  $data  validated input: `client`, `ordered_on` (Y-m-d), optional `note`, and `lines` (a list of `product_id` and `quantity`)
     *
     * @throws ValidationException a started line was removed or given a quantity below its started units; nothing is saved
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
