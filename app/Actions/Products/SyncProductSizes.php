<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;

final readonly class SyncProductSizes
{
    /**
     * Make the product's sizes exactly these sizes (INV-18-f) and nothing else: a size pressed on gains no stock and no barcode, a size
     * taken off loses only its link (it was free, `ProductSizeLock` checked). The link rows carry the product's workshop.
     *
     * @param  list<int|string>  $sizeIds  validated ids of the workshop's sizes
     */
    public function handle(Product $product, array $sizeIds): void
    {
        $product->sizes()->syncWithPivotValues(array_map('intval', $sizeIds), ['workshop_id' => $product->workshop_id]);

        // A relation loaded before the sync no longer matches the table.
        $product->unsetRelation('sizes');
    }
}
