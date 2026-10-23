<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductSizeStock;
use App\Models\Size;

/**
 * Reads the finished stock of a product (S-56, ADR-0017): one query for its rows, the sizes from the request's size lists. A size
 * without a row holds 0.
 */
final class FinishedStock
{
    public function __construct(private readonly SizeLists $sizeLists) {}

    public function of(Product $product): FinishedStockLevel
    {
        /** @var array<int, int> $unitsBySizeId */
        $unitsBySizeId = ProductSizeStock::query()
            ->where('product_id', $product->id)
            ->pluck('units', 'size_id')
            ->map(fn (mixed $units): int => (int) $units)
            ->all();

        $sizes = array_values($this->sizeLists->category($product->category_id)->sizeList->sizes
            ->map(fn (Size $size): array => ['size' => $size, 'units' => $unitsBySizeId[$size->id] ?? 0])
            ->all());

        return new FinishedStockLevel(array_sum(array_column($sizes, 'units')), $sizes);
    }
}
