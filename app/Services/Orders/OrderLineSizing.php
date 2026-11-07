<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderLineSizeMode;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\Products\ProductSizes;
use App\Services\Products\ProductSizeSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * How an order line counts its units (S-60, ADR-0016; INV-18, ADR-0028), the one place that decides it.
 *
 * - The line counts by size (`OrderLine::countsBySize()`, the flag stored on the line): `BySize`.
 * - It does not, and its product is "One size": `OneSize`.
 * - It does not, but its product has other sizes: `BeforeSizes` (the line was recorded before sizes).
 *
 * A new line counts by size unless its product is "One size" (`ProductSizes`). A product that carries `is_one_size` (the order pages'
 * queries add it, see `OrderBook`) is answered without a query, so a page of many lines costs none per line.
 */
final readonly class OrderLineSizing
{
    public const IS_ONE_SIZE = 'is_one_size';

    public function __construct(private ProductSizes $productSizes) {}

    /** The line's `product` must be loaded (soft-deleted ones included). */
    public function forLine(OrderLine $line): OrderLineSizeMode
    {
        if ($line->countsBySize()) {
            return OrderLineSizeMode::BySize;
        }

        return $this->forProduct($line->product) === OrderLineSizeMode::BySize ? OrderLineSizeMode::BeforeSizes : OrderLineSizeMode::OneSize;
    }

    /** What a line recorded now for this product would count by: `BySize` or `OneSize`. */
    public function forProduct(Product $product): OrderLineSizeMode
    {
        $oneSize = $this->loadedOneSize($product) ?? $this->productSizes->of($product)->oneSize;

        return $oneSize ? OrderLineSizeMode::OneSize : OrderLineSizeMode::BySize;
    }

    /**
     * The sizes of a line that does not count by size: the workshop's One size, whatever its product has become (a line recorded before
     * sizes keeps its "One size": its rows point at it). Read once per request.
     */
    public function ownSizes(OrderLine $line): ProductSizeSet
    {
        $one = $this->productSizes->oneSizeOf($line->workshop_id);

        return new ProductSizeSet(new Collection($one === null ? [] : [$one]), oneSize: true);
    }

    /** Whether the product is "One size", when the model carries it (the order pages' queries add `is_one_size`); null when it does not. */
    private function loadedOneSize(Model $model): ?bool
    {
        return array_key_exists(self::IS_ONE_SIZE, $model->getAttributes()) ? (int) $model->getAttribute(self::IS_ONE_SIZE) > 0 : null;
    }
}
