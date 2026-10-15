<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SyncOrderLines
{
    /**
     * Make the order's lines equal to the submitted ones, in one transaction: lines are matched by product (a product still listed
     * keeps its line, with quantity and position updated in place; a new product gets a new line), lines whose product is no longer
     * listed are deleted, and `position` follows the submitted order. Matching by product means swapping two products between
     * lines never meets the `unique(order_id, product_id)` index.
     *
     * A product listed twice is not merged: the second line is inserted and the database's unique index rejects it, which rolls the
     * whole sync back (the form request refuses it earlier).
     *
     * @param  array<int|string, array{product_id: int|string, quantity: int|string}>  $lines  validated input, in display order (the keys are ignored: `position` is the index in the order given)
     */
    public function handle(Order $order, array $lines): void
    {
        DB::transaction(function () use ($order, $lines): void {
            /** @var Collection<int, OrderLine> $stale lines still to be matched, by product id */
            $stale = $order->lines()->get()->keyBy('product_id');

            foreach (array_values($lines) as $position => $line) {
                $attributes = ['quantity' => (int) $line['quantity'], 'position' => $position];

                // `pull` takes the line out of the pool, so a repeated product finds nothing and hits the unique index.
                $existing = $stale->pull((int) $line['product_id']);

                if ($existing === null) {
                    $new = $order->lines()->make([...$attributes, 'product_id' => (int) $line['product_id']]);

                    // Never mass-assignable: a line belongs to its order's workshop, whatever the request says.
                    $new->workshop_id = $order->workshop_id;
                    $new->save();
                } else {
                    $existing->update($attributes);
                }
            }

            if ($stale->isNotEmpty()) {
                OrderLine::query()->whereKey($stale->pluck('id')->all())->delete();
            }

            // A relation loaded before the sync no longer matches the table.
            $order->unsetRelation('lines');
        });
    }
}
