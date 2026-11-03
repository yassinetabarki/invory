<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateOrder
{
    public function __construct(private SyncOrderLines $syncLines) {}

    /**
     * Edits an order and its lines in one transaction. Stock-neutral, and the order keeps the user who recorded it. The lines are synced after the
     * order's row lock, which every delivery also takes first, so a line's delivered and cancelled units (INV-14) are checked against its saved counts: the form
     * request's own check ran on unlocked data.
     *
     * The client (INV-4, AC-6) is the one chosen in Edit order's picker; the order keeps her name in `client` (ADR-0021).
     *
     * @param  array<string, mixed>  $data  validated input: `client_id`, `ordered_on` (Y-m-d), optional `note`, and `lines` (a list of `product_id` and `quantity` or `sizes`)
     *
     * @throws ValidationException a line with delivered or cancelled units was removed or given fewer units than those (per size when counted by size), or a product's sizes changed; nothing is saved
     */
    public function handle(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            // Two people saving the same order take turns; an order deleted in the meantime is not found.
            Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            // A client is never removed, so the one the request found is still there.
            $client = Client::query()->whereKey((int) $data['client_id'])->firstOrFail();
            $order->client_id = $client->id;
            $order->fill([...Arr::only($data, ['ordered_on', 'note']), 'client' => $client->name])->save();
            $this->syncLines->handle($order, $data['lines']);

            return $order;
        });
    }
}
