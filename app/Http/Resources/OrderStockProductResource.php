<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\Size;
use App\Services\Products\FinishedStockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `OrderStockProduct` in resources/js/types/domain.ts (INV-13, AC-1, AC-2): a product as the order forms' search and quantity grid show
 * it, with its photo, its sizes and its shelf stock per colour and size. Built from `OrderProductSearch::lines()`.
 *
 * @property array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel} $resource
 */
final class OrderStockProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $product = $this->resource['product'];

        return [
            'product' => [...(new OrderProductResource($product))->toArray($request), 'photo_url' => $product->photo_url],
            'sizes' => array_map(fn (Size $size): array => ['id' => $size->id, 'label' => $size->label], $this->resource['sizes']),
            'one_size' => $this->resource['one_size'],
            'stock' => (new FinishedStockResource($this->resource['stock']))->toArray($request),
        ];
    }
}
