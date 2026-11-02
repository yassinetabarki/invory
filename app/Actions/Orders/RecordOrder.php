<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Actions\Clients\FindOrAddClient;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class RecordOrder
{
    public function __construct(private SyncOrderLines $syncLines, private FindOrAddClient $findOrAddClient) {}

    /**
     * Records an order with its lines in one transaction. Orders are stock-neutral: no material, stock movement or sale is touched.
     *
     * The client (INV-4) is found by the phone's key, or added, in the same transaction: an order that fails saves no client either.
     * The order keeps her name in `client` (ADR-0021). The order's `clientRecord` is set: its `wasRecentlyCreated` tells a new client.
     *
     * @param  array<string, mixed>  $data  validated input: `client` (the Client card's fields: `phone`, and `name`, `city`, `source`, `source_other` for a new client), `ordered_on` (Y-m-d), optional `note`, and `lines` (a list of `product_id` and `quantity` or `sizes`)
     */
    public function handle(array $data, User $actor): Order
    {
        return DB::transaction(function () use ($data, $actor): Order {
            [$client] = $this->findOrAddClient->handle($data['client'], $actor);

            $order = new Order([...Arr::only($data, ['ordered_on', 'note']), 'client' => $client->name]);
            $order->client_id = $client->id;

            // Never mass-assignable: the workshop and who recorded it come from the actor, not from the request.
            $order->workshop_id = $actor->workshop_id;
            $order->user_id = $actor->id;
            $order->save();

            $this->syncLines->handle($order, $data['lines']);

            return $order->setRelation('clientRecord', $client);
        });
    }
}
