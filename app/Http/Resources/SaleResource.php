<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Sales\SalesCalculator;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Sale` in resources/js/types/domain.ts. The sale must carry its `product` (soft-deleted ones included) with the attributes
 * `id`, `sku`, `name` and `photo_url` (`photo_url` is the model accessor over `photo_path`). The total is computed, never stored.
 *
 * @mixin Sale
 */
final class SaleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
                'photo_url' => $this->product->photo_url,
            ],
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => (new SalesCalculator)->lineTotal($this->quantity, $this->unit_price),
            'sold_on' => $this->sold_on->toDateString(),
            'note' => $this->note,
        ];
    }
}
