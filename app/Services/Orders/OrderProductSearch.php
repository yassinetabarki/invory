<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Size;
use App\Models\SizeList;
use App\Services\Products\FinishedStock;
use App\Services\Products\FinishedStockLevel;
use App\Services\Products\SizeLists;
use App\Services\Restocks\RestockBarcodeLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * "Search products" on New order and Edit order (INV-13, AC-1; ADR-0025): the active products a search finds, each with its shelf stock
 * per colour and size. Read-only; the workshop scope limits every query to the current workshop. No costing: the search shows only
 * what is on the shelf (the human's answer, 2026-11-03).
 *
 * A product is found by its name or SKU (a part of either), by the SKU of one of its variants typed in full ("PRD-JKT-001-IND-M"), by
 * the name of one of its colours (a part of it) or by a barcode number it carries (INV-8's matching, as on New restock).
 */
final readonly class OrderProductSearch
{
    /** At most this many products come back; the count of every match says how many more there are. */
    public const LIMIT = 6;

    /** A search is cut to this many characters. */
    public const MAX_TERM = 64;

    public function __construct(private FinishedStock $finishedStock, private SizeLists $sizeLists, private RestockBarcodeLookup $barcodes) {}

    /**
     * @return array{total: int, products: list<array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel}>}
     */
    public function find(string $typed): array
    {
        $term = trim(mb_substr($typed, 0, self::MAX_TERM));

        if ($term === '') {
            return ['total' => 0, 'products' => []];
        }

        $matches = $this->matching($term);
        $total = (clone $matches)->count();

        return ['total' => $total, 'products' => $total === 0 ? [] : $this->lines($matches->byName()->limit(self::LIMIT)->get())];
    }

    /**
     * A product's data on the order forms, the same as a search result: its sizes, whether it is "One size", and its shelf stock. The
     * stock of every product is read in one query.
     *
     * @param  iterable<Product>  $products
     * @param  array<int, SizeList>  $lists  a list to use instead of the product's, by product id (Edit order: the list a saved line
     *                                       counts by, "One size" for a line recorded before sizes)
     * @return list<array{product: Product, sizes: list<Size>, one_size: bool, stock: FinishedStockLevel}>
     */
    public function lines(iterable $products, array $lists = []): array
    {
        $stock = $this->finishedStock->ofMany($products);
        $lines = [];

        foreach ($products as $product) {
            $sizeList = $lists[$product->id] ?? $this->sizeLists->category($product->category_id)->sizeList;
            $lines[] = [
                'product' => $product,
                'sizes' => array_values($sizeList->sizes->all()),
                'one_size' => $sizeList->code === SizeList::ONE_SIZE,
                'stock' => $stock[$product->id],
            ];
        }

        return $lines;
    }

    /** @return Builder<Product> */
    private function matching(string $term): Builder
    {
        $variantSkus = $this->skuPrefixes($term);
        $barcodeProducts = $this->barcodeProducts($term);

        return Product::query()
            ->where('status', ProductStatus::Active->value)
            ->where(fn (Builder $found) => $found
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('sku', "%{$term}%")
                ->orWhereHas('colours', fn (Builder $colours) => $colours->whereLike('colours.name', "%{$term}%"))
                ->when($variantSkus !== [], fn (Builder $query) => $query->orWhereIn(DB::raw('upper(sku)'), $variantSkus))
                ->when($barcodeProducts !== [], fn (Builder $query) => $query->orWhereIn('id', $barcodeProducts)));
    }

    /**
     * What a variant SKU typed in full may start with: "PRD-JKT-001-IND-M" → "PRD-JKT-001-IND", "PRD-JKT-001", "PRD-JKT", "PRD" (a SKU
     * may itself hold dashes). A term without a dash has none.
     *
     * @return list<string>
     */
    private function skuPrefixes(string $term): array
    {
        $parts = explode('-', mb_strtoupper($term));
        $prefixes = [];

        for ($count = count($parts) - 1; $count >= 1; $count--) {
            $prefixes[] = implode('-', array_slice($parts, 0, $count));
        }

        return $prefixes;
    }

    /**
     * The products a barcode number stands for (INV-8's matching, reused from New restock); none when the term isn't a known number.
     *
     * @return list<int>
     */
    private function barcodeProducts(string $term): array
    {
        if (preg_match('/^\d{13}$/', preg_replace('/\s+/', '', $term) ?? '') !== 1) {
            return [];
        }

        $found = $this->barcodes->find($term);

        return $found['result'] === 'found' ? array_map(static fn (array $line): int => $line['product']->id, $found['matches']) : [];
    }
}
