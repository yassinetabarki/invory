<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Enums\ProductionBatchStatus;
use App\Models\OrderLineSize;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductSizeStock;
use Illuminate\Support\Number;

/**
 * A colour with finished units in stock, units in production (INV-5-g) or units on open orders (INV-13-b) can't be taken off its product (INV-2-b, ADR-0019): the rows of a colour exist only while it
 * holds a unit, so "has stock" is one grouped read of the rows of the colours being taken off. A colour at 0 comes off as before, and
 * added back later it starts at 0.
 *
 * Checked twice: by the update request, so the sentences show with the other errors, and by `UpdateProduct` under the product's row
 * lock, so a stock update can't land between the check and the save.
 */
final class ProductColourLock
{
    /**
     * The sentence for every colour that is on `$product`, is not among `$keptColourIds` and still has units, keyed by colour id, in
     * list order (position, then name, then id). Empty when nothing stops the save.
     *
     * @param  list<int>  $keptColourIds  the colours the product keeps
     * @return array<int, string>
     */
    public function refusals(Product $product, array $keptColourIds): array
    {
        $held = ProductSizeStock::query()
            ->join('colours', 'colours.id', '=', 'product_size_stocks.colour_id')
            ->where('product_size_stocks.product_id', $product->id)
            ->whereNotIn('product_size_stocks.colour_id', $keptColourIds)
            ->groupBy('colours.id', 'colours.name', 'colours.position')
            ->orderBy('colours.position')->orderBy('colours.name')->orderBy('colours.id')
            ->toBase()
            ->selectRaw('colours.id, colours.name, colours.position, SUM(product_size_stocks.units) as units')
            ->get();

        $refusals = [];
        $order = [];

        foreach ($held as $colour) {
            $order[(int) $colour->id] = [(int) $colour->position, $colour->name, (int) $colour->id];

            $units = (int) $colour->units;

            // The plural is chosen on the raw integer; the count is printed grouped ("1,000,000").
            $refusals[(int) $colour->id] = trans_choice(
                "{1} :colour has :count unit in stock, so it can't be removed from :product. Set its stock to 0 first."
                ."|[2,*] :colour has :count units in stock, so it can't be removed from :product. Set its stock to 0 first.",
                $units,
                ['colour' => $colour->name, 'product' => $product->name, 'count' => (string) Number::format($units, locale: 'en')],
            );
        }

        // INV-5-g: a colour with units in production can't come off either. The shelf sentence wins when both apply (the human's answer,
        // 2026-11-02): the production sentence shows once the shelf is at 0.
        $inProduction = ProductionBatch::query()
            ->join('colours', 'colours.id', '=', 'production_batches.colour_id')
            ->where('production_batches.product_id', $product->id)
            ->where('production_batches.status', ProductionBatchStatus::InProduction->value)
            ->whereNotIn('production_batches.colour_id', $keptColourIds)
            ->groupBy('colours.id', 'colours.name', 'colours.position')
            ->toBase()
            ->selectRaw('colours.id, colours.name, colours.position, SUM(production_batches.units) as units')
            ->get();

        foreach ($inProduction as $colour) {
            if (isset($refusals[(int) $colour->id])) {
                continue;
            }

            $units = (int) $colour->units;
            $order[(int) $colour->id] = [(int) $colour->position, $colour->name, (int) $colour->id];
            $refusals[(int) $colour->id] = trans_choice(
                "{1} :colour has :count unit in production, so it can't be removed from :product."
                ."|[2,*] :colour has :count units in production, so it can't be removed from :product.",
                $units,
                ['colour' => $colour->name, 'product' => $product->name, 'count' => (string) Number::format($units, locale: 'en')],
            );
        }

        // INV-13-b: a colour that open orders still hold (units ordered and not shipped yet) can't come off either. The shelf and the
        // production sentences win: this one shows once both are at 0. A deleted order holds nothing.
        $onOrders = OrderLineSize::query()
            ->join('order_lines', 'order_lines.id', '=', 'order_line_sizes.order_line_id')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->join('colours', 'colours.id', '=', 'order_line_sizes.colour_id')
            ->where('order_lines.product_id', $product->id)
            ->whereNull('orders.deleted_at')
            ->whereNotIn('order_line_sizes.colour_id', $keptColourIds)
            ->whereColumn('order_line_sizes.shipped', '<', 'order_line_sizes.quantity')
            ->groupBy('colours.id', 'colours.name', 'colours.position')
            ->toBase()
            ->selectRaw('colours.id, colours.name, colours.position, SUM(order_line_sizes.quantity - order_line_sizes.shipped) as units')
            ->get();

        foreach ($onOrders as $colour) {
            if (isset($refusals[(int) $colour->id])) {
                continue;
            }

            $units = (int) $colour->units;
            $order[(int) $colour->id] = [(int) $colour->position, $colour->name, (int) $colour->id];
            $refusals[(int) $colour->id] = trans_choice(
                "{1} :colour has :count unit on open orders, so it can't be removed from :product."
                ."|[2,*] :colour has :count units on open orders, so it can't be removed from :product.",
                $units,
                ['colour' => $colour->name, 'product' => $product->name, 'count' => (string) Number::format($units, locale: 'en')],
            );
        }

        uksort($refusals, static fn (int $a, int $b): int => $order[$a] <=> $order[$b]);

        return $refusals;
    }
}
