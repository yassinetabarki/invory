<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Query\JoinClause;

/**
 * Reads the finished stock of a product (S-56, ADR-0017; per colour since INV-2, ADR-0019): ONE query for its colours with their stock
 * rows (a colour with no row still comes back, once), the sizes from the request's size lists, and every total added up here (whole
 * numbers). A colour and size without a row holds 0.
 */
final class FinishedStock
{
    public function __construct(private readonly SizeLists $sizeLists) {}

    public function of(Product $product): FinishedStockLevel
    {
        $sizes = $this->sizeLists->category($product->category_id)->sizeList->sizes;

        /** @var array<int, Colour> $colours in list order (position, name, id) */
        $colours = [];

        /** @var array<int, array<int, int>> $units colour id → size id → units */
        $units = [];

        // The colours of the product LEFT JOIN its stock rows: a row per colour and size with units, one row for a colour with none.
        $rows = Colour::query()
            ->join('product_colours', 'product_colours.colour_id', '=', 'colours.id')
            ->leftJoin('product_size_stocks', fn (JoinClause $join) => $join
                ->on('product_size_stocks.colour_id', '=', 'colours.id')
                ->on('product_size_stocks.product_id', '=', 'product_colours.product_id'))
            ->where('product_colours.product_id', $product->id)
            ->orderBy('colours.position')->orderBy('colours.name')->orderBy('colours.id')
            ->get(['colours.*', 'product_size_stocks.size_id as stock_size_id', 'product_size_stocks.units as stock_units']);

        foreach ($rows as $row) {
            $colours[$row->id] ??= $row;
            $sizeId = $row->getAttribute('stock_size_id');

            if ($sizeId !== null) {
                $units[$row->id][(int) $sizeId] = (int) $row->getAttribute('stock_units');
            }
        }

        $perSize = array_fill_keys($sizes->map(fn (Size $size): int => $size->id)->all(), 0);

        $byColour = [];

        foreach ($colours as $colourId => $colour) {
            $perColour = array_values($sizes->map(fn (Size $size): array => ['size' => $size, 'units' => $units[$colourId][$size->id] ?? 0])->all());

            foreach ($perColour as $row) {
                $perSize[$row['size']->id] += $row['units'];
            }

            $byColour[] = ['colour' => $colour, 'units' => array_sum(array_column($perColour, 'units')), 'sizes' => $perColour];
        }

        $totals = array_values($sizes->map(fn (Size $size): array => ['size' => $size, 'units' => $perSize[$size->id]])->all());

        return new FinishedStockLevel(array_sum($perSize), $totals, $byColour);
    }
}
