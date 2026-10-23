<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderStatusFilter;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Size;
use App\Models\SizeList;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The Eloquent side of the Orders pages (S-48): the list query with its search, and the products the order forms offer.
 * Nothing here reads or writes materials, stock history or sales, and nothing does maths: the units of an order are an integer SQL sum.
 */
final readonly class OrderBook
{
    /** The columns of a product the order resources read (`status` and `deleted_at` included, strict models); `category_id` for its sizes. */
    private const PRODUCT_COLUMNS = ['id', 'sku', 'name', 'status', 'deleted_at', 'category_id'];

    /**
     * The list: orders of that status matching the search (client, note, or a line's product name or SKU), newest first, with what
     * the order resources read: the lines in the order entered, each with its product (soft-deleted ones
     * included), and the units sum. Only the product columns the resources use; `deleted_at` and `status` are among them.
     *
     * @return Builder<Order>
     */
    public function list(?string $search, OrderStatusFilter $status): Builder
    {
        return Order::query()
            ->when($status === OrderStatusFilter::Open, fn (Builder $orders) => $orders->open())
            ->when($status === OrderStatusFilter::Shipped, fn (Builder $orders) => $orders->shipped())
            ->search($search)->newestFirst()
            ->with($this->lineRelations())
            ->withSum('lines', 'quantity');
    }

    /**
     * The figures above the list: the open orders and the units of every line of every open order of the workshop, whatever the
     * search or the filter (S-50). One query; whole units, cast to int (PostgreSQL returns sums as strings, a sum over no rows is null).
     *
     * @return array{open_orders: int, to_start: int, in_cutting: int, ready_to_ship: int}
     */
    public function openTotals(): array
    {
        $row = OrderLine::query()
            ->whereIn('order_id', Order::query()->open()->select('id'))
            ->selectRaw('COUNT(DISTINCT order_id) as open_orders, SUM(quantity - in_cutting - ready_to_ship - shipped) as to_start, SUM(in_cutting) as in_cutting, SUM(ready_to_ship) as ready_to_ship')
            ->toBase()
            ->first();

        return [
            'open_orders' => (int) ($row->open_orders ?? 0),
            'to_start' => (int) ($row->to_start ?? 0),
            'in_cutting' => (int) ($row->in_cutting ?? 0),
            'ready_to_ship' => (int) ($row->ready_to_ship ?? 0),
        ];
    }

    /** Any order at all in the workshop, whatever its status: tells "No orders yet" from "No open orders". */
    public function hasOrders(): bool
    {
        return Order::query()->exists();
    }

    /**
     * What an order resource reads, for an order read by route-model binding (the edit page).
     */
    public function load(Order $order): Order
    {
        $order->load($this->lineRelations());

        // The units are the sum of the loaded lines' whole quantities: no query of their own (the page stays within its query budget).
        return $order->setAttribute('lines_sum_quantity', $order->lines->sum('quantity'));
    }

    /**
     * What the order resources read of the lines, in a fixed number of queries however many orders and lines there are: the lines, their
     * products (narrowed) and the size rows. The sizes' labels and the number of sizes of the line's and of the product's list
     * (`OrderLineSizing`) are subselects of those queries, not queries of their own, so an order page stays within the query budget.
     *
     * @return array<string, Closure(Relation<*, *, *>): mixed>
     */
    private function lineRelations(): array
    {
        return [
            'lines' => fn (Relation $lines) => $lines->addSelect([OrderLineSizing::SIZES_IN_LIST => Size::query()->selectRaw('count(*)')->whereColumn('sizes.size_list_id', 'order_lines.size_list_id')]),
            'lines.product' => fn (Relation $products) => $products->select(self::PRODUCT_COLUMNS)
                ->addSelect([OrderLineSizing::SIZES_IN_LIST => Size::query()->selectRaw('count(*)')->whereIn('size_list_id', $this->productListId())]),
            'lines.sizes' => fn (Relation $sizes) => $sizes->addSelect(['size_label' => Size::query()->select('label')->whereColumn('sizes.id', 'order_line_sizes.size_id')]),
        ];
    }

    /**
     * "New order" offers the workshop's active, non-deleted products, by name.
     *
     * @return Collection<int, Product>
     */
    public function newOrderProducts(): Collection
    {
        return $this->formProducts(Product::query()->where('status', ProductStatus::Active->value))->get();
    }

    /**
     * "Edit order" offers the active products plus every product already on the order, whatever its status, deleted ones included.
     *
     * @return Collection<int, Product>
     */
    public function editOrderProducts(Order $order): Collection
    {
        return $this->formProducts(Product::query()->withTrashed()
            ->where(fn (Builder $offered) => $offered
                ->where(fn (Builder $active) => $active->where('status', ProductStatus::Active->value)->whereNull('deleted_at'))
                ->orWhereIn('id', OrderLine::query()->where('order_id', $order->id)->select('product_id'))))
            ->get();
    }

    /**
     * The products an order form offers, by name, each with the code of its size list (a subselect, so no query per product).
     *
     * @param  Builder<Product>  $products
     * @return Builder<Product>
     */
    private function formProducts(Builder $products): Builder
    {
        return $products->byName()->select(self::PRODUCT_COLUMNS)
            ->addSelect(['size_list_code' => SizeList::query()->select('code')->whereIn('id', $this->productListId())]);
    }

    /**
     * The id of the size list of the category of a product, as a subselect of a query on `products`.
     *
     * @return Builder<ProductCategory>
     */
    private function productListId(): Builder
    {
        return ProductCategory::query()->select('size_list_id')->whereColumn('product_categories.id', 'products.category_id');
    }
}
