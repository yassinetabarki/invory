<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderLine;
use App\Services\Orders\OrderLineSizing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderLine` in resources/js/types/domain.ts. The line must carry its `product` (soft-deleted ones included) and its `sizes` (each with
 * its `size_label`), and the `sizes_in_list` count on itself and its product so that `OrderLineSizing` costs no query: see `OrderBook`.
 * The stage counts are columns; `to_start` and `started` are worked out here from them by the model, never by the screen.
 *
 * @mixin OrderLine
 */
final class OrderLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var OrderLine $line */
        $line = $this->resource;
        $progress = $this->progress();

        return [
            'id' => $this->id,
            'product' => new OrderProductResource($this->product),
            'quantity' => $this->quantity,
            'to_start' => $this->to_start,
            'in_cutting' => $this->in_cutting,
            'ready_to_ship' => $this->ready_to_ship,
            'shipped' => $this->shipped,
            'started' => $this->started,
            'size_mode' => app(OrderLineSizing::class)->forLine($line)->value,
            'sizes' => OrderLineSizeResource::collection($this->sizes),
            'status' => $progress->value,
            'status_label' => $progress->label(),
        ];
    }
}
