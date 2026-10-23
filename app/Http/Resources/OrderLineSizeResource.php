<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderLineSize;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderLineSize` in resources/js/types/domain.ts: one size of an order line with its quantity and four stages (S-60, S-61). The row
 * must carry `size_label`, the label of its size (not a column: the order pages' queries add it, see `OrderBook`).
 *
 * @mixin OrderLineSize
 */
final class OrderLineSizeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'size' => ['id' => $this->size_id, 'label' => $this->getAttribute('size_label')],
            'quantity' => $this->quantity,
            'to_start' => $this->to_start,
            'in_cutting' => $this->in_cutting,
            'ready_to_ship' => $this->ready_to_ship,
            'shipped' => $this->shipped,
        ];
    }
}
