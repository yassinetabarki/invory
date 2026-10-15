<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderLine` in resources/js/types/domain.ts. The line must carry its `product` (soft-deleted ones included).
 *
 * @mixin OrderLine
 */
final class OrderLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new OrderProductResource($this->product),
            'quantity' => $this->quantity,
        ];
    }
}
