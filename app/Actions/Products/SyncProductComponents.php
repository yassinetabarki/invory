<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SyncProductComponents
{
    /**
     * Make the product's composition equal to the submitted lines, in one transaction: lines are matched by material (updated in
     * place, or inserted), lines whose material is no longer submitted are deleted, and `position` follows the submitted order.
     *
     * A material listed twice is not merged: the second line is inserted and the database's `unique(product_id, material_id)`
     * rejects it, which rolls the whole sync back (the FormRequest's `distinct` rule normally stops it earlier).
     *
     * @param  array<int, array{material_id: int|string, quantity: string, waste_percent?: string|null, notes?: string|null}>  $lines  validated input, in display order (the keys are ignored: `position` is the index in the order given)
     */
    public function handle(Product $product, array $lines): void
    {
        DB::transaction(function () use ($product, $lines): void {
            /** @var Collection<int, ProductComponent> $stale lines still to be matched, by material id */
            $stale = $product->components()->get()->keyBy('material_id');

            foreach (array_values($lines) as $position => $line) {
                $attributes = [
                    'quantity' => $line['quantity'],
                    'waste_percent' => $line['waste_percent'] ?? '0',
                    'notes' => $line['notes'] ?? null,
                    'position' => $position,
                ];

                // `pull` takes the line out of the pool, so a repeated material finds nothing and hits the unique index.
                $existing = $stale->pull((int) $line['material_id']);

                if ($existing === null) {
                    $component = $product->components()->make([...$attributes, 'material_id' => (int) $line['material_id']]);

                    // Never mass-assignable: a line belongs to its product's workshop, whatever the request says.
                    $component->workshop_id = $product->workshop_id;
                    $component->save();
                } else {
                    $existing->update($attributes);
                }
            }

            if ($stale->isNotEmpty()) {
                ProductComponent::query()->whereKey($stale->pluck('id')->all())->delete();
            }

            // A relation loaded before the sync no longer matches the table.
            $product->unsetRelation('components');
        });
    }
}
