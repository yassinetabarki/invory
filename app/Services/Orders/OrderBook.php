<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Eloquent side of the Orders pages (S-48): the list query with its search, and the products the order forms offer.
 * Nothing here reads or writes materials, stock history or sales, and nothing does maths: the units of an order are an integer SQL sum.
 */
final readonly class OrderBook
{
    /** The lines with their product, narrowed to what the resources read (`status` and `deleted_at` included, strict models). */
    private const LINES = ['lines.product:id,sku,name,status,deleted_at'];

    /**
     * The list: orders matching the search (client, note, or a line's product name or SKU), newest first, with what
     * the order resources read: the lines in the order entered, each with its product (soft-deleted ones
     * included), and the units sum. Only the product columns the resources use; `deleted_at` and `status` are among them.
     *
     * @return Builder<Order>
     */
    public function list(?string $search): Builder
    {
        return Order::query()->search($search)->newestFirst()
            ->with(self::LINES)
            ->withSum('lines', 'quantity');
    }

    /**
     * What an order resource reads, for an order read by route-model binding (the edit page).
     */
    public function load(Order $order): Order
    {
        return $order->load(self::LINES)->loadSum('lines', 'quantity');
    }

    /**
     * "New order" offers the workshop's active, non-deleted products, by name.
     *
     * @return Collection<int, Product>
     */
    public function newOrderProducts(): Collection
    {
        return Product::query()->where('status', ProductStatus::Active->value)->byName()->get($this->productColumns());
    }

    /**
     * "Edit order" offers the active products plus every product already on the order, whatever its status, deleted ones included.
     *
     * @return Collection<int, Product>
     */
    public function editOrderProducts(Order $order): Collection
    {
        return Product::query()->withTrashed()
            ->where(fn (Builder $offered) => $offered
                ->where(fn (Builder $active) => $active->where('status', ProductStatus::Active->value)->whereNull('deleted_at'))
                ->orWhereIn('id', OrderLine::query()->where('order_id', $order->id)->select('product_id')))
            ->byName()
            ->get($this->productColumns());
    }

    /** @return list<string> */
    private function productColumns(): array
    {
        return ['id', 'sku', 'name', 'status', 'deleted_at'];
    }
}
