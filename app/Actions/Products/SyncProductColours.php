<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductComponentQuantity;

final readonly class SyncProductColours
{
    /**
     * Make the product's colours exactly these colours (INV-1). The link rows carry the product's workshop, whatever the request
     * says; the order they were picked in is not stored (the list order decides how they show).
     *
     * A colour taken off loses its quantity cells first, in the same save (INV-16, ADR-0027): PostgreSQL's key from a cell to the
     * product's colours refuses to detach a colour that a cell still points at. A colour added has none.
     *
     * @param  list<int|string>  $colourIds  validated ids of the workshop's colours
     */
    public function handle(Product $product, array $colourIds): void
    {
        $ids = array_map('intval', $colourIds);

        ProductComponentQuantity::query()->where('product_id', $product->id)->whereNotIn('colour_id', $ids)->delete();

        $product->colours()->syncWithPivotValues($ids, ['workshop_id' => $product->workshop_id]);

        // A relation loaded before the sync no longer matches the table.
        $product->unsetRelation('colours');
    }
}
