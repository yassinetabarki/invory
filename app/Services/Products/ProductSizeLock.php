<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductCategory;

/**
 * The sizes of a product are locked once it is on an order (S-58, AC-4, ADR-0015 decision 4): its category may change only to one
 * with the same size list. An order counts until it is deleted, also once it is fully shipped. Add product has no lock, and a
 * product on no order can change to any category.
 *
 * Checked twice: by the update request, so the error shows with the other errors, and by `UpdateProduct` under the product row lock.
 */
final class ProductSizeLock
{
    /**
     * The message that refuses moving `$product` to category `$to`, or null when the move is allowed.
     *
     * @param  Product  $product  with its CURRENT category (loaded here if it is not)
     */
    public function refusal(Product $product, ProductCategory $to): ?string
    {
        $product->loadMissing('category');
        $from = $product->category;

        if ((int) $to->size_list_id === (int) $from->size_list_id || ! $this->isOnAnOrder($product)) {
            return null;
        }

        $list = $from->sizeList()->firstOrFail();
        $keep = $list->sizes()->count() > 1
            ? __('Keep a category with sizes :sizes.', ['sizes' => $list->name])
            : __('Keep a "One size" category.');

        return __(':product is on orders, so its category can\'t change to one with other sizes.', ['product' => $product->name]).' '.$keep;
    }

    /** One `exists`: the Order scopes (soft delete, workshop) apply through the relation, so a deleted order's lines don't count. */
    private function isOnAnOrder(Product $product): bool
    {
        return OrderLine::query()->where('product_id', $product->id)->whereHas('order')->exists();
    }
}
