<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderLineSizeMode;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Products\SizeLists;
use Illuminate\Database\Eloquent\Model;

/**
 * How an order line counts its units (S-60, ADR-0016), the one place that decides it.
 *
 * - The line's size list has several sizes: `BySize`.
 * - The line's list is "One size" and its product's list is too: `OneSize`.
 * - The line's list is "One size" but its product has several sizes: `BeforeSizes` (the line was recorded before sizes).
 *
 * A model that carries `sizes_in_list` (the number of sizes of its list: the order pages' queries add it, see `OrderBook`) is answered
 * without a query, so a page of many lines costs none per line. Any other model reads the lists through the request-scoped `SizeLists`.
 */
final readonly class OrderLineSizing
{
    public const SIZES_IN_LIST = 'sizes_in_list';

    public function __construct(private SizeLists $lists) {}

    /** The line's `product` must be loaded (soft-deleted ones included). */
    public function forLine(OrderLine $line): OrderLineSizeMode
    {
        $several = $this->loaded($line)
            ?? ($line->size_list_id !== null && $this->lists->list($line->size_list_id)->sizes->count() > 1);

        if ($several) {
            return OrderLineSizeMode::BySize;
        }

        return $this->forProduct($line->product) === OrderLineSizeMode::BySize ? OrderLineSizeMode::BeforeSizes : OrderLineSizeMode::OneSize;
    }

    /** What a line recorded now for this product would count by: `BySize` or `OneSize`. */
    public function forProduct(Product $product): OrderLineSizeMode
    {
        $several = $this->loaded($product)
            ?? $this->lists->category($product->category_id)->sizeList->sizes->count() > 1;

        return $several ? OrderLineSizeMode::BySize : OrderLineSizeMode::OneSize;
    }

    /** Whether the list has several sizes, when the model carries the count; null when it does not. */
    private function loaded(Model $model): ?bool
    {
        return array_key_exists(self::SIZES_IN_LIST, $model->getAttributes()) ? (int) $model->getAttribute(self::SIZES_IN_LIST) > 1 : null;
    }
}
