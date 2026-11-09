<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\ComponentQuantityMode;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductComponentQuantity;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SyncProductComponents
{
    /** Rows per insert of cells: 8 columns each, far under the drivers' limits on bound values. */
    private const CELLS_PER_INSERT = 500;

    /**
     * Make the product's composition equal to the submitted lines, in one transaction: lines are matched by material (updated in
     * place, or inserted), lines whose material is no longer submitted are deleted, and `position` follows the submitted order.
     *
     * A line is `same` (its `quantity`, the default) or `by_variant` (INV-16: no `quantity`, and its `quantities[colourId][sizeId]`
     * replace the cells it had; an empty or 0 cell is no cell). A line that goes back to `same` loses its cells. The colours and sizes
     * of the cells must already be the product's own (PostgreSQL's keys refuse others): sync the colours and sizes first.
     *
     * A material listed twice is not merged: the second line is inserted and the database's `unique(product_id, material_id)`
     * rejects it, which rolls the whole sync back (the FormRequest's `distinct` rule normally stops it earlier).
     *
     * @param  array<int, array{material_id: int|string, quantity_mode?: string|null, quantity?: string|null, quantities?: array<int|string, mixed>|null, waste_percent?: string|null, notes?: string|null}>  $lines  validated input, in display order (the keys are ignored: `position` is the index in the order given)
     */
    public function handle(Product $product, array $lines): void
    {
        DB::transaction(function () use ($product, $lines): void {
            /** @var Collection<int, ProductComponent> $stale lines still to be matched, by material id */
            $stale = $product->components()->get()->keyBy('material_id');

            foreach (array_values($lines) as $position => $line) {
                $mode = ComponentQuantityMode::tryFrom((string) ($line['quantity_mode'] ?? '')) ?? ComponentQuantityMode::Same;
                $byVariant = $mode === ComponentQuantityMode::ByVariant;

                $attributes = [
                    'quantity_mode' => $mode,
                    'quantity' => $byVariant ? null : ($line['quantity'] ?? throw new InvalidArgumentException("The line of material #{$line['material_id']} needs a quantity.")),
                    'waste_percent' => $line['waste_percent'] ?? '0',
                    'notes' => $line['notes'] ?? null,
                    'position' => $position,
                ];

                // `pull` takes the line out of the pool, so a repeated material finds nothing and hits the unique index.
                $existing = $stale->pull((int) $line['material_id']);

                // A line that was `same` and stays so never had cells: nothing to replace.
                $mayHaveCells = $existing !== null && ($existing->quantity_mode === ComponentQuantityMode::ByVariant || $byVariant);

                if ($existing === null) {
                    $component = $product->components()->make([...$attributes, 'material_id' => (int) $line['material_id']]);

                    // Never mass-assignable: a line belongs to its product's workshop, whatever the request says.
                    $component->workshop_id = $product->workshop_id;
                    $component->save();
                } else {
                    $component = $existing;
                    $component->update($attributes);
                }

                if ($mayHaveCells) {
                    ProductComponentQuantity::query()->where('product_component_id', $component->id)->delete();
                }

                if ($byVariant) {
                    $this->insertCells($product, $component, $line['quantities'] ?? []);
                }
            }

            if ($stale->isNotEmpty()) {
                ProductComponent::query()->whereKey($stale->pluck('id')->all())->delete();
            }

            // A relation loaded before the sync no longer matches the table.
            $product->unsetRelation('components');
        });
    }

    /**
     * Stores the filled cells of a line (a number above 0; empty and 0 are no cell), the product's workshop and id stamped on each.
     *
     * @param  array<int|string, mixed>  $quantities  by colour id, then size id
     */
    private function insertCells(Product $product, ProductComponent $component, array $quantities): void
    {
        $now = now();
        $rows = [];

        foreach ($quantities as $colourId => $bySize) {
            foreach (is_array($bySize) ? $bySize : [] as $sizeId => $value) {
                if (! is_scalar($value) || trim((string) $value) === '') {
                    continue;
                }

                $quantity = BigDecimal::of(trim((string) $value));

                if (! $quantity->isPositive()) {
                    continue;
                }

                $rows[] = [
                    'workshop_id' => $product->workshop_id,
                    'product_component_id' => $component->id,
                    'product_id' => $product->id,
                    'colour_id' => (int) $colourId,
                    'size_id' => (int) $sizeId,
                    'quantity' => (string) $quantity->toScale(4, RoundingMode::HalfUp),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, self::CELLS_PER_INSERT) as $chunk) {
            ProductComponentQuantity::query()->insert($chunk);
        }
    }
}
