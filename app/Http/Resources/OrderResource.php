<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\Orders\OrderBook;
use App\Services\Orders\OrderMessages;
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
        $held = $this->unitsHeld();

        return [
            'id' => $this->id,
            'client' => $this->client,
            'ordered_on' => $this->ordered_on->toDateString(),
            'note' => $this->note,
            'lines' => OrderLineResource::collection($this->lines),
            'units' => (int) $this->lines_sum_quantity,
            'in_cutting' => $this->unitsInCutting(),
            'ready_to_ship' => $this->unitsReadyToShip(),
            'shipped' => $this->unitsShipped(),
            'status' => $progress->value,
            'status_label' => $progress->label(),
            'delete_refusal' => $held > 0 ? OrderMessages::deleteRefusal($this->client, $held) : null,
        ];
    }
}
