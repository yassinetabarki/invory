<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderProduct` in resources/js/types/domain.ts: a product as an order shows and offers it. The product must carry `id`, `sku`, `name`,
 * `status` and `deleted_at` (a soft-deleted product is shown by name, flagged `deleted`). No photo: the order pages don't show one.
 *
 * @mixin Product
 */
final class OrderProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'status' => $this->status->value,
            'deleted' => $this->trashed(),
        ];
    }
}
