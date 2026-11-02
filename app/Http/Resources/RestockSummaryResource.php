<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductRestock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * `RestockSummary` in resources/js/types/domain.ts (INV-3, AC-2): a row of the Restocks list. The restock must come from
 * `RestockBook::page()`, which sets its `productTotals` (the products in the order added, with their units).
 *
 * @mixin ProductRestock
 */
final class RestockSummaryResource extends JsonResource
{
    /** How many products a row names before "and 2 more". */
    public const NAMED = 2;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, array{product: Product, units: int}> $products */
        $products = $this->resource->relationLoaded('productTotals') ? $this->resource->getRelation('productTotals') : collect();

        return self::summary($this->resource, array_values($products->all()));
    }

    /**
     * @param  list<array{product: Product, units: int}>  $products
     * @return array<string, mixed>
     */
    public static function summary(ProductRestock $restock, array $products): array
    {
        return [
            'id' => $restock->id,
            'restocked_on' => $restock->restocked_on->toDateString(),
            'reference' => $restock->reference,
            'units' => $restock->units,
            'products' => array_map(fn (array $row): array => ['name' => $row['product']->name, 'units' => $row['units']], array_slice($products, 0, self::NAMED)),
            'more_products' => max(0, count($products) - self::NAMED),
            'recorded_by' => $restock->recorder?->name,
            'undone' => $restock->isUndone(),
        ];
    }
}
