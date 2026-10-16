<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reads of the stock ledger for the materials pages. Every movement comes back with `user_name`, and for a cutting entry the order's
 * `order_client` and the `product_name`, joined in (one query, soft-deleted orders and products included), so a Resource never
 * lazy-loads any of them. Newest first means `occurred_on` descending, then `id` descending (entry order within a day).
 */
final class StockHistory
{
    /** The movement's own columns, the user's name, and the order and product of a cutting entry. */
    private const COLUMNS = ['stock_movements.*', 'users.name as user_name', 'orders.client as order_client', 'products.name as product_name'];

    /**
     * Every movement of one material, newest first.
     *
     * @return Collection<int, StockMovement>
     */
    public function forMaterial(Material $material): Collection
    {
        return StockMovement::query()
            ->leftJoin('users', 'users.id', '=', 'stock_movements.user_id')
            ->leftJoin('orders', 'orders.id', '=', 'stock_movements.order_id')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.material_id', $material->id)
            ->select(self::COLUMNS)
            ->orderByDesc('stock_movements.occurred_on')
            ->orderByDesc('stock_movements.id')
            ->get();
    }

    /**
     * The newest `$perMaterial` movements of each given material, in ONE query (a `ROW_NUMBER()` window, portable to PostgreSQL and
     * SQLite): flattened in the order of `$materialIds`, newest first within a material.
     *
     * @param  list<int>  $materialIds
     * @return list<StockMovement>
     */
    public function recentFor(array $materialIds, int $perMaterial = 5): array
    {
        if ($materialIds === []) {
            return [];
        }

        $ranked = StockMovement::query()
            ->leftJoin('users', 'users.id', '=', 'stock_movements.user_id')
            ->leftJoin('orders', 'orders.id', '=', 'stock_movements.order_id')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->whereIn('stock_movements.material_id', $materialIds)
            ->select(self::COLUMNS)
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY stock_movements.material_id ORDER BY stock_movements.occurred_on DESC, stock_movements.id DESC) AS recency');

        $byMaterial = StockMovement::query()
            ->fromSub($ranked, 'stock_movements')
            ->where('recency', '<=', $perMaterial)
            ->orderBy('recency')
            ->get()
            ->groupBy('material_id');

        $recent = [];

        foreach ($materialIds as $materialId) {
            array_push($recent, ...($byMaterial->get($materialId)?->all() ?? []));
        }

        return $recent;
    }
}
