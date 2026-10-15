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
 * sum as a string, so it is cast here.
 *
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->client,
            'ordered_on' => $this->ordered_on->toDateString(),
            'note' => $this->note,
            'lines' => OrderLineResource::collection($this->lines),
            'units' => (int) $this->lines_sum_quantity,
        ];
    }
}
