<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Colour;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Query\JoinClause;

/**
 * Reads the finished stock of a product (S-56, ADR-0017; per colour since INV-2, ADR-0019): ONE query for its colours with their stock
 * rows (a colour with no row still comes back, once), the sizes from {@see ProductSizes}, and every total added up here (whole
 * numbers). A colour and size without a row holds 0.
 */
final class FinishedStock
{
    public function __construct(private readonly ProductSizes $productSizes) {}

    public function of(Product $product): FinishedStockLevel
    {
        return $this->ofMany([$product])[$product->id];
    }

    /**
     * The finished stock of several products in ONE query (INV-13: the order search and Edit order), by product id.
     *
     * @param  iterable<Product>  $products
     * @return array<int, FinishedStockLevel>
     */
    public function ofMany(iterable $products): array
    {
        $byId = [];

        foreach ($products as $product) {
            $byId[$product->id] = $product;
        }

        if ($byId === []) {
            return [];
        }

        /** @var array<int, array<int, Colour>> $colours product id → colour id → colour, in list order (position, name, id) */
        $colours = array_fill_keys(array_keys($byId), []);

        /** @var array<int, array<int, array<int, int>>> $units product id → colour id → size id → units */
        $units = [];

        // The colours of the products LEFT JOIN their stock rows: a row per colour and size with units, one row for a colour with none.
        $rows = Colour::query()
            ->join('product_colours', 'product_colours.colour_id', '=', 'colours.id')
            ->leftJoin('product_size_stocks', fn (JoinClause $join) => $join
                ->on('product_size_stocks.colour_id', '=', 'colours.id')
                ->on('product_size_stocks.product_id', '=', 'product_colours.product_id'))
            ->whereIn('product_colours.product_id', array_keys($byId))
            ->orderBy('colours.position')->orderBy('colours.name')->orderBy('colours.id')
            ->get(['colours.*', 'product_colours.product_id as stock_product_id', 'product_size_stocks.size_id as stock_size_id', 'product_size_stocks.units as stock_units']);

        foreach ($rows as $row) {
            $productId = (int) $row->getAttribute('stock_product_id');
            $colours[$productId][$row->id] ??= (new Colour)->setRawAttributes(array_diff_key($row->getAttributes(), array_flip(['stock_product_id', 'stock_size_id', 'stock_units'])), true);
            $sizeId = $row->getAttribute('stock_size_id');

            if ($sizeId !== null) {
                $units[$productId][$row->id][(int) $sizeId] = (int) $row->getAttribute('stock_units');
            }
        }

        $sizes = $this->productSizes->forProducts($byId);
        $levels = [];

        foreach ($byId as $productId => $product) {
            $levels[$productId] = $this->level($sizes[$productId], $colours[$productId], $units[$productId] ?? []);
        }

        return $levels;
    }

    /**
     * @param  array<int, Colour>  $colours  in list order
     * @param  array<int, array<int, int>>  $units  colour id → size id → units
     */
    private function level(ProductSizeSet $set, array $colours, array $units): FinishedStockLevel
    {
        $sizes = $set->sizes;
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
