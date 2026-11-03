<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\Orders\OrderBook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Order` in resources/js/types/domain.ts. The order must carry its `lines` (in the order entered, each with its product) and the
 * `lines_sum_quantity` aggregate: see {@see OrderBook}. The units are a whole number: PostgreSQL returns the SQL
 * sum as a string, so it is cast here. The stage totals and the status are worked out from the loaded lines (no extra query).
 *
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $progress = $this->progress();

        return [
            'id' => $this->id,
            'client' => $this->client,
            'ordered_on' => $this->ordered_on->toDateString(),
            'note' => $this->note,
            'lines' => OrderLineResource::collection($this->lines),
            'units' => (int) $this->lines_sum_quantity,
            // INV-14: the three counts add up to `units`; worked out from the loaded lines, never by the screen.
            'to_deliver' => $this->unitsToDeliver(),
            'delivered' => $this->unitsDelivered(),
            'cancelled' => $this->unitsCancelled(),
            'status' => $progress->value,
            'status_label' => $progress->label(),
            // INV-4: her id (Edit order's picker, the lists), and who she is on the order page only (loaded there, never queried here).
            'client_id' => $this->client_id,
            'client_details' => $this->relationLoaded('clientRecord') && $this->clientRecord !== null
                ? (new ClientRefResource($this->clientRecord))->resolve($request)
                : null,
        ];
    }
}
