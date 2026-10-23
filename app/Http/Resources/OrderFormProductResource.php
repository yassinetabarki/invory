<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderFormProduct` in resources/js/types/domain.ts: an {@see OrderProductResource} and the code of its size list, which the page finds
 * in its `size_lists` prop. The product must carry `size_list_code` (not a column: `OrderBook` adds it to the products it offers).
 *
 * @mixin Product
 */
final class OrderFormProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...(new OrderProductResource($this->resource))->toArray($request),
            'size_list_code' => $this->getAttribute('size_list_code'),
        ];
    }
}
