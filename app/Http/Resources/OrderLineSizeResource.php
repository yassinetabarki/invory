<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderLineSize;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderLineSize` in resources/js/types/domain.ts: one colour and size of an order line with its quantity, delivered and cancelled units (S-60,
 * INV-13, INV-14). The row must carry `size_label` and `colour_code`, `colour_name`, `colour_swatch` (not columns: the order pages' queries add
 * them, see `OrderBook`).
 *
 * @mixin OrderLineSize
 */
final class OrderLineSizeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'colour' => [
                'id' => $this->colour_id,
                'code' => $this->getAttribute('colour_code'),
                'name' => $this->getAttribute('colour_name'),
                'swatch' => $this->getAttribute('colour_swatch'),
            ],
            'size' => ['id' => $this->size_id, 'label' => $this->getAttribute('size_label')],
            'quantity' => $this->quantity,
            'to_deliver' => $this->to_deliver,
            'delivered' => $this->delivered,
            'cancelled' => $this->cancelled,
        ];
    }
}
