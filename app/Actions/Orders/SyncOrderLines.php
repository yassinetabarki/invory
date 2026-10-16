<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderLine;
use App\Services\Orders\OrderMessages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * A line that has started (S-49) keeps its product and a quantity of at least its started units. The counts are read here, inside
     * the transaction, after the caller took the order's row lock, so they are the saved ones: this check is the one that counts.
     *
     * @param  array<int|string, array{product_id: int|string, quantity: int|string}>  $lines  validated input, in display order (`position` is the index in the order given; a key only names a line in an error message)
     *
     * @throws ValidationException a started line was removed or given a quantity below its started units
     */
    public function handle(Order $order, array $lines): void
    {
        DB::transaction(function () use ($order, $lines): void {
            /** @var Collection<int, OrderLine> $stale lines still to be matched, by product id */
            $stale = $order->lines()->get()->keyBy('product_id');

            $refusals = $this->refusals($stale, $lines);

            if ($refusals !== []) {
                throw ValidationException::withMessages($refusals);
            }

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

    /**
     * What a submitted list of lines may not do to the lines that have started: remove one (a swap of its product reads as a removal,
     * since lines are matched by product) or lower its quantity below the units started. Lines that haven't started, and new
     * lines, are free. The form request runs this on unlocked data for early messages; `handle()` runs it under the order lock.
     *
     * @param  Collection<int, OrderLine>  $current  the order's saved lines, by product id
     * @param  array<int|string, mixed>  $lines  as submitted (the request has validated their shape; anything else is skipped)
     * @return array<string, list<string>> messages by error key: `lines` (a started line is missing) and `lines.<key>.quantity`
     */
    public function refusals(Collection $current, array $lines): array
    {
        $submitted = [];

        foreach ($lines as $key => $line) {
            if (is_array($line)) {
                $submitted[(int) $line['product_id']] ??= [$key, (int) $line['quantity']];
            }
        }

        $refusals = [];

        foreach ($current as $productId => $saved) {
            if ($saved->started === 0) {
                continue;
            }

            if (! isset($submitted[$productId])) {
                // The product may be deleted: the line keeps it.
                $refusals['lines'][] = OrderMessages::startedLineRemoved($saved->product()->firstOrFail()->name, $saved->started);
            } elseif ($submitted[$productId][1] < $saved->started) {
                $refusals["lines.{$submitted[$productId][0]}.quantity"][] = OrderMessages::quantityBelowStarted($saved->started);
            }
        }

        return $refusals;
    }
}
