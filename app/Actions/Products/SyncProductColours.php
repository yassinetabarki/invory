<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;

final readonly class SyncProductColours
{
    /**
     * Make the product's colours exactly these colours (INV-1). The link rows carry the product's workshop, whatever the request
     * says; the order they were picked in is not stored (the list order decides how they show).
     *
     * @param  list<int|string>  $colourIds  validated ids of the workshop's colours
     */
    public function handle(Product $product, array $colourIds): void
    {
        $product->colours()->syncWithPivotValues(array_map('intval', $colourIds), ['workshop_id' => $product->workshop_id]);

        // A relation loaded before the sync no longer matches the table.
        $product->unsetRelation('colours');
    }
}
