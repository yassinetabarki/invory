<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductSizeStock;
use Illuminate\Support\Number;

/**
 * A colour with finished units in stock can't be taken off its product (INV-2-b, ADR-0019): the rows of a colour exist only while it
 * holds a unit, so "has stock" is one grouped read of the rows of the colours being taken off. A colour at 0 comes off as before, and
 * added back later it starts at 0.
 *
 * Checked twice: by the update request, so the sentences show with the other errors, and by `UpdateProduct` under the product's row
 * lock, so a stock update can't land between the check and the save.
 */
final class ProductColourLock
{
    /**
     * The sentence for every colour that is on `$product`, is not among `$keptColourIds` and still has units, keyed by colour id, in
     * list order (position, then name, then id). Empty when nothing stops the save.
     *
     * @param  list<int>  $keptColourIds  the colours the product keeps
     * @return array<int, string>
     */
    public function refusals(Product $product, array $keptColourIds): array
    {
        $held = ProductSizeStock::query()
            ->join('colours', 'colours.id', '=', 'product_size_stocks.colour_id')
            ->where('product_size_stocks.product_id', $product->id)
            ->whereNotIn('product_size_stocks.colour_id', $keptColourIds)
            ->groupBy('colours.id', 'colours.name', 'colours.position')
            ->orderBy('colours.position')->orderBy('colours.name')->orderBy('colours.id')
            ->toBase()
            ->selectRaw('colours.id, colours.name, SUM(product_size_stocks.units) as units')
            ->get();

        $refusals = [];

        foreach ($held as $colour) {
            $units = (int) $colour->units;

            // The plural is chosen on the raw integer; the count is printed grouped ("1,000,000").
            $refusals[(int) $colour->id] = trans_choice(
                "{1} :colour has :count unit in stock, so it can't be removed from :product. Set its stock to 0 first."
                ."|[2,*] :colour has :count units in stock, so it can't be removed from :product. Set its stock to 0 first.",
                $units,
                ['colour' => $colour->name, 'product' => $product->name, 'count' => (string) Number::format($units, locale: 'en')],
            );
        }

        return $refusals;
    }
}
