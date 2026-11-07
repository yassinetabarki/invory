<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Enums\ProductionBatchStatus;
use App\Models\BarcodeName;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductSizeStock;
use App\Models\Size;

/**
 * A size a product uses can't be taken off it (INV-18-f, AC-6, ADR-0028), whatever the reason: finished units in stock, a row on an order
 * (delivered and cancelled included, a deleted order doesn't count), a barcode of the product's name in one of its current colours (a
 * size that ever had one stays for good), or a batch In production. A size with none of them is free.
 *
 * {@see reasons()} is the one reader: FOUR grouped queries per product, whatever its sizes. The sentences are written here too, so no
 * screen words a lock itself. Checked twice, like the colour lock: by the update request (so the sentences show with the other errors)
 * and by `UpdateProduct` under the product's row lock (so a stock update, an order or a barcode can't land between the check and the save).
 */
final class ProductSizeLock
{
    private const STOCK = 'has units in stock';

    private const ORDERS = 'is on orders';

    private const BARCODES = 'has barcodes';

    private const PRODUCTION = 'has units in production';

    public function __construct(private readonly ProductSizes $productSizes) {}

    /**
     * Each locked size of the product (by size id) with the reasons it is locked, in this order: stock, orders, barcodes, production.
     * A free size has no entry.
     *
     * @return array<int, list<string>>
     */
    public function reasons(Product $product): array
    {
        $found = [
            self::STOCK => ProductSizeStock::query()->where('product_id', $product->id)->where('units', '>', 0)
                ->distinct()->pluck('size_id'),
            self::ORDERS => OrderLineSize::query()
                ->join('order_lines', 'order_lines.id', '=', 'order_line_sizes.order_line_id')
                ->join('orders', 'orders.id', '=', 'order_lines.order_id')
                ->where('order_lines.product_id', $product->id)
                ->whereNull('orders.deleted_at')
                ->toBase()->distinct()->pluck('order_line_sizes.size_id'),
            self::BARCODES => BarcodeName::query()
                ->where('name_key', $product->name_key)
                ->whereIn('colour_id', $product->colours()->reorder()->select('colours.id'))
                ->distinct()->pluck('size_id'),
            self::PRODUCTION => ProductionBatch::query()->where('product_id', $product->id)
                ->where('status', ProductionBatchStatus::InProduction->value)
                ->distinct()->pluck('size_id'),
        ];

        $reasons = [];

        foreach ($found as $reason => $sizeIds) {
            foreach ($sizeIds as $sizeId) {
                $reasons[(int) $sizeId][] = $reason;
            }
        }

        ksort($reasons);

        return $reasons;
    }

    /**
     * The hint of a locked size: "Can't be taken off: it has units in stock, is on orders and has barcodes."
     *
     * @param  list<string>  $reasons  as {@see reasons()} gives them
     */
    public function tooltip(array $reasons): string
    {
        return "Can't be taken off: it ".self::join($reasons).'.';
    }

    /**
     * The sentence of a refused save: "XL can't be taken off Denim Jacket: it has units in stock, is on orders and has barcodes."
     *
     * @param  list<string>  $reasons  as {@see reasons()} gives them
     */
    public function refusal(Product $product, Size $size, array $reasons): string
    {
        return SizeNames::inText($size->label, first: true)." can't be taken off {$product->name}: it ".self::join($reasons).'.';
    }

    /**
     * The sentence for every size the product has now, is not among `$keptSizeIds` and is locked, by size id, in list order. The sizes
     * are read again (the caller may hold the product's row lock). Empty when nothing stops the save.
     *
     * @param  list<int>  $keptSizeIds  the sizes the product keeps
     * @return array<int, string>
     */
    public function refusals(Product $product, array $keptSizeIds): array
    {
        $reasons = $this->reasons($product);

        if ($reasons === []) {
            return [];
        }

        $refusals = [];

        foreach ($this->productSizes->fresh($product)->sizes as $size) {
            if (isset($reasons[$size->id]) && ! in_array($size->id, $keptSizeIds, true)) {
                $refusals[$size->id] = $this->refusal($product, $size, $reasons[$size->id]);
            }
        }

        return $refusals;
    }

    /**
     * Why One size can't be chosen: "XL is in use, so One size can't be chosen." The sizes are named by the size text rule ("Size 38",
     * "Sizes 36, 37 and 38", "S, M, L and XL"). Null when no size is locked, or the product is One size already.
     *
     * @param  array<int, list<string>>|null  $reasons  {@see reasons()}, when the caller has them already
     */
    public function oneSizeLock(Product $product, ?array $reasons = null): ?string
    {
        $set = $this->productSizes->of($product);

        if ($set->oneSize) {
            return null;
        }

        $reasons ??= $this->reasons($product);
        $labels = array_values($set->sizes->filter(fn (Size $size): bool => isset($reasons[$size->id]) && ! $size->is_one_size)
            ->map(fn (Size $size): string => $size->label)->all());

        if ($labels === []) {
            return null;
        }

        $names = match (true) {
            count($labels) === 1 => SizeNames::inText($labels[0], first: true).' is',
            collect($labels)->every(fn (string $label): bool => ctype_digit($label)) => 'Sizes '.self::join($labels).' are',
            default => ucfirst(self::join(array_map(fn (string $label): string => SizeNames::inText($label), $labels))).' are',
        };

        return "{$names} in use, so One size can't be chosen.";
    }

    /**
     * Why no other size can be chosen: "One size is in use, so other sizes can't be chosen." Null unless the product is One size and
     * that size is locked.
     *
     * @param  array<int, list<string>>|null  $reasons  {@see reasons()}, when the caller has them already
     */
    public function otherSizesLock(Product $product, ?array $reasons = null): ?string
    {
        $set = $this->productSizes->of($product);

        if (! $set->oneSize) {
            return null;
        }

        $reasons ??= $this->reasons($product);

        return isset($reasons[(int) $set->sizes->first()?->id]) ? "One size is in use, so other sizes can't be chosen." : null;
    }

    /**
     * "A", "A and B", "A, B and C".
     *
     * @param  list<string>  $items
     */
    private static function join(array $items): string
    {
        $last = (string) array_pop($items);

        return $items === [] ? $last : implode(', ', $items).' and '.$last;
    }
}
