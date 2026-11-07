<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderLine;
use App\Services\Orders\OrderLineSizing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderLine` in resources/js/types/domain.ts. The line must carry its `product` (soft-deleted ones included) and its `sizes` (each with
 * its `size_label`), and the `is_one_size` count on its product so that `OrderLineSizing` costs no query: see `OrderBook`.
 * `delivered` and `cancelled` are columns; `to_deliver` is worked out from them by the model (INV-14), never by the screen.
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
            'to_deliver' => $this->to_deliver,
            'delivered' => $this->delivered,
            'cancelled' => $this->cancelled,
            'size_mode' => app(OrderLineSizing::class)->forLine($line)->value,
            'sizes' => OrderLineSizeResource::collection($this->sizes),
            'status' => $progress->value,
            'status_label' => $progress->label(),
        ];
    }
}
