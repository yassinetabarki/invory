<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class RecordOrder
{
    public function __construct(private SyncOrderLines $syncLines) {}

    /**
     * Records an order with its lines in one transaction. Orders are stock-neutral: no material, stock movement or sale is touched.
     *
     * @param  array<string, mixed>  $data  validated input: `client`, `ordered_on` (Y-m-d), optional `note`, and `lines` (a list of `product_id` and `quantity` or `sizes`)
     */
    public function handle(array $data, User $actor): Order
    {
        return DB::transaction(function () use ($data, $actor): Order {
            $order = new Order(Arr::only($data, ['client', 'ordered_on', 'note']));

            // Never mass-assignable: the workshop and who recorded it come from the actor, not from the request.
            $order->workshop_id = $actor->workshop_id;
            $order->user_id = $actor->id;
            $order->save();

            $this->syncLines->handle($order, $data['lines']);

            return $order;
        });
    }
}
