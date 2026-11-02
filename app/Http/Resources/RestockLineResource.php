<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\Size;
use App\Services\Products\FinishedStockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `RestockLine` in resources/js/types/domain.ts (INV-3, AC-3): one product's card on New restock, with what each colour and size has
 * on the shelf ("now 2"). Built from `RestockBook::line()`.
 *
 * @property array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel} $resource
 */
final class RestockLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'product' => (new RestockProductRefResource($this->resource['product']))->toArray($request),
            'sizes' => array_map(fn (Size $size): array => ['id' => $size->id, 'label' => $size->label], $this->resource['sizes']),
            'one_size' => $this->resource['one_size'],
            'stock' => (new FinishedStockResource($this->resource['stock']))->toArray($request),
        ];
    }
}
