<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `RestockProductRef` in resources/js/types/domain.ts (INV-3): a product as the restock pages name it, deleted ones included.
 *
 * @mixin Product
 */
final class RestockProductRefResource extends JsonResource
{
    /** @return array{id: int, name: string, sku: string, status: string, deleted: bool} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'status' => $this->status->value,
            'deleted' => $this->trashed(),
        ];
    }
}
