<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Domain\Barcodes\VariantNames;
use App\Domain\Barcodes\VariantSku;
use App\Models\Colour;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Reads the barcodes of a product (INV-8, ADR-0020): a product owns none, it looks up the number that stands for its name, each colour
 * and each size. TWO queries however many colours, sizes or sharing products there are (the colours of the product with the numbers of its name, and
 * the other products that show those numbers; the last is skipped when the name has no number), plus the product's sizes, which
 * {@see ProductSizes} reads once per request. Nothing is computed beyond counting.
 */
final class ProductBarcodes
{
    public function __construct(private readonly ProductSizes $productSizes) {}

    public function of(Product $product): ProductBarcodeLevel
    {
        $set = $this->productSizes->of($product);
        $sizes = $set->sizes;
        $oneSize = $set->oneSize;
        [$colours, $numbers] = $this->coloursWithNumbers($product);

        $barcodeIds = [];

        foreach ($numbers as $bySize) {
            foreach ($bySize as $found) {
                $barcodeIds[$found['id']] = $found['id'];
            }
        }

        $sharedWith = $this->sharedWith(array_values($barcodeIds), $product->id);

        $missing = 0;
        $missingColours = [];
        $rows = [];

        foreach ($colours as $colour) {
            $variants = [];
            $missingSizes = [];

            foreach ($sizes as $size) {
                $found = $numbers[$colour->id][$size->id] ?? null;

                $variants[] = [
                    'size' => $size,
                    'sku' => VariantSku::of($product->sku, $colour->code, $oneSize ? null : $size->label),
                    'barcode' => $found['number'] ?? null,
                    'shared_with' => $found === null ? [] : $sharedWith[$found['id']] ?? [],
                ];

                if ($found === null) {
                    $missingSizes[] = $size->label;
                }
            }

            $missing += count($missingSizes);

            if ($missingSizes !== []) {
                $missingColours[] = ['colour' => $colour->name, 'sizes' => $missingSizes];
            }

            $rows[] = ['colour' => $colour, 'missing' => count($missingSizes), 'variants' => $variants];
        }

        return new ProductBarcodeLevel(
            $missing,
            $missing === 0 ? null : VariantNames::list($missingColours, $oneSize),
            count($colours) * count($sizes) - $missing,
            $rows,
        );
    }

    /**
     * The product's colours in list order together with the number that stands for its name in each colour and size: ONE query (the
     * colours LEFT JOIN the numbers of the name, so a colour with none still comes back, once). Sizes outside the product's list are
     * ignored by the caller, which only looks up the sizes of the list.
     *
     * @return array{0: array<int, Colour>, 1: array<int, array<int, array{id: int, number: string}>>} the colours by id, and colour id → size id → the barcode's id and number
     */
    private function coloursWithNumbers(Product $product): array
    {
        $colours = [];
        $numbers = [];

        $rows = Colour::query()
            ->join('product_colours', 'product_colours.colour_id', '=', 'colours.id')
            ->leftJoin('barcode_names', fn (JoinClause $join) => $join
                ->on('barcode_names.colour_id', '=', 'colours.id')
                ->where('barcode_names.workshop_id', '=', $product->workshop_id)
                ->where('barcode_names.name_key', '=', $product->name_key))
            ->leftJoin('barcodes', 'barcodes.id', '=', 'barcode_names.barcode_id')
            ->where('product_colours.product_id', $product->id)
            ->orderBy('colours.position')->orderBy('colours.name')->orderBy('colours.id')
            ->get(['colours.*', 'barcode_names.size_id as named_size_id', 'barcode_names.barcode_id as named_barcode_id', 'barcodes.number as named_number']);

        foreach ($rows as $row) {
            $colours[$row->id] ??= $row;

            if ($row->getAttribute('named_size_id') !== null) {
                $numbers[$row->id][(int) $row->getAttribute('named_size_id')] = ['id' => (int) $row->getAttribute('named_barcode_id'), 'number' => (string) $row->getAttribute('named_number')];
            }
        }

        return [$colours, $numbers];
    }

    /**
     * The other non-deleted products of the workshop, any status, that show each of these barcodes (ONE query): their name stands for the
     * number, they have its colour and its size is one of their sizes. By SKU, then id.
     *
     * @param  list<int>  $barcodeIds
     * @param  int  $exceptProductId  the product being read or generated for, never listed as sharing with itself
     * @return array<int, list<array{id: int, sku: string}>> barcode id → the products
     */
    public function sharedWith(array $barcodeIds, int $exceptProductId): array
    {
        if ($barcodeIds === []) {
            return [];
        }

        $shared = [];

        $rows = $this->withTheSizeOfTheName(Product::query()
            ->join('barcode_names', fn (JoinClause $join) => $join
                ->on('barcode_names.workshop_id', '=', 'products.workshop_id')
                ->on('barcode_names.name_key', '=', 'products.name_key'))
            ->join('product_colours', fn (JoinClause $join) => $join
                ->on('product_colours.product_id', '=', 'products.id')
                ->on('product_colours.colour_id', '=', 'barcode_names.colour_id')))
            ->whereIn('barcode_names.barcode_id', $barcodeIds)
            ->where('products.id', '<>', $exceptProductId)
            ->orderBy('products.sku')->orderBy('products.id')
            ->get(['products.id', 'products.sku', 'barcode_names.barcode_id']);

        foreach ($rows as $row) {
            $shared[(int) $row->getAttribute('barcode_id')][] = ['id' => $row->id, 'sku' => $row->sku];
        }

        return $shared;
    }

    /**
     * Keeps the products that have the size of `barcode_names` (SQL, so one query covers every product): the one place that joins a
     * product to its sizes (`product_sizes`, ADR-0028).
     *
     * @param  Builder<Product>  $products
     * @return Builder<Product>
     */
    private function withTheSizeOfTheName(Builder $products): Builder
    {
        return $products->join('product_sizes', fn (JoinClause $join) => $join
            ->on('product_sizes.product_id', '=', 'products.id')
            ->on('product_sizes.size_id', '=', 'barcode_names.size_id'));
    }
}
