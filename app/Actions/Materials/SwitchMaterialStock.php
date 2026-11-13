<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Switches a material between one stock and stock per colour, and between the same cost and cost per colour (INV-21-g, ADR-0029
 * sections 2, 3 and 7). Called by {@see UpdateMaterial} inside its transaction, under the material's row lock; it moves the modes
 * and the stock through {@see MaterialStockWriter} and writes the ledger entries of the switch itself. What is typed in the colour
 * rows besides (the other colours, a changed quantity or cost) is written afterwards by UpdateMaterial, like any other edit.
 *
 * - To per colour with stock: one `colour_split` group, the one stock going to 0 (no colour) and the first submitted colour coming
 *   from 0 to that stock. At 0 nothing is logged.
 * - Back to one stock: one `colour_join` group, every colour with stock going to 0 and the one stock coming from 0 to the total.
 *   The colour rows go, their entries stay. The kept cost is the submitted `unit_cost` (the material's own when none).
 * - Cost per colour on: each colour starts at the current cost, no entry. Off: the kept cost applies to every colour.
 * - Every colour whose cost changes with the kept cost gets a `price_change` for that colour.
 */
final readonly class SwitchMaterialStock
{
    public function __construct(
        private MaterialStockWriter $stock,
        private AppendStockMovement $appendMovement,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input, with `colours` rows when the target is per colour and `unit_cost` when the kept cost is submitted
     */
    public function handle(Material $material, MaterialStockMode $stockMode, MaterialCostMode $costMode, array $data, User $actor): void
    {
        if ($stockMode === $material->stock_mode && $costMode === $material->cost_mode) {
            return;
        }

        if ($stockMode === MaterialStockMode::PerColour && $material->stock_mode === MaterialStockMode::Single) {
            $this->split($material, $costMode, $data, $actor);
        } elseif ($stockMode === MaterialStockMode::Single) {
            $this->join($material, $data, $actor);
        } elseif ($costMode === MaterialCostMode::PerColour) {
            $this->stock->switchMode($material, $stockMode, $costMode);
        } else {
            $kept = $this->keptCost($material, $data);
            $stays = array_map(static fn (mixed $row): int => (int) Arr::get((array) $row, 'colour_id'), array_values((array) ($data['colours'] ?? [])));

            $this->logCostChanges($material, $kept, $stays, (string) Str::uuid(), $actor);
            $this->stock->switchMode($material, $stockMode, $costMode, $kept);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function split(Material $material, MaterialCostMode $costMode, array $data, User $actor): void
    {
        $stock = BigDecimal::of($material->quantity_on_hand);
        $cost = $material->unit_cost;
        $first = (array) (array_values((array) ($data['colours'] ?? []))[0] ?? []);
        $colourId = (int) Arr::get($first, 'colour_id');

        $this->stock->switchMode($material, MaterialStockMode::PerColour, $costMode);

        if ($stock->isZero()) {
            return;
        }

        $this->stock->addColour($material, $colourId, $stock, $costMode === MaterialCostMode::PerColour ? $cost : null);

        $group = (string) Str::uuid();
        $moved = (string) $stock->toScale(4, RoundingMode::HalfUp);

        $this->move($material, StockMovementType::ColourSplit, null, $moved, $moved, '0.0000', $group, $actor);
        $this->move($material, StockMovementType::ColourSplit, $colourId, $moved, '0.0000', $moved, $group, $actor);
    }

    /** @param  array<string, mixed>  $data */
    private function join(Material $material, array $data, User $actor): void
    {
        $kept = $this->keptCost($material, $data);
        $rows = $material->colours->filter(fn (MaterialColour $row): bool => BigDecimal::of($row->quantity_on_hand)->isPositive())->values();

        // The rows are read before they go; the price changes come first, then the two sides of the move, all dated today.
        $group = (string) Str::uuid();

        $this->logCostChanges($material, $kept, $material->colours->map(static fn (MaterialColour $row): int => $row->colour_id)->all(), $group, $actor);

        foreach ($rows as $row) {
            $this->move($material, StockMovementType::ColourJoin, $row->colour_id, $row->quantity_on_hand, $row->quantity_on_hand, '0.0000', $group, $actor);
        }

        $total = (string) $this->stock->switchMode($material, MaterialStockMode::Single, MaterialCostMode::Same, $kept)->toScale(4, RoundingMode::HalfUp);

        if ($rows->isNotEmpty()) {
            $this->move($material, StockMovementType::ColourJoin, null, $total, '0.0000', $total, $group, $actor);
        }
    }

    /**
     * The submitted `unit_cost`, or the cost the material has.
     *
     * @param  array<string, mixed>  $data
     */
    private function keptCost(Material $material, array $data): BigDecimal
    {
        $given = $data['unit_cost'] ?? null;

        return BigDecimal::of(is_scalar($given) && $given !== '' ? (string) $given : $material->unit_cost)->toScale(4, RoundingMode::HalfUp);
    }

    /**
     * A `price_change` for each colour (among `$colourIds`) whose cost isn't the kept cost, or, when the material has the same cost,
     * one without a colour when the kept cost isn't the material's.
     *
     * @param  array<int, int>  $colourIds
     */
    private function logCostChanges(Material $material, BigDecimal $kept, array $colourIds, string $group, User $actor): void
    {
        $today = today()->toDateString();

        if ($material->cost_mode === MaterialCostMode::Same) {
            if (! $kept->isEqualTo($material->unit_cost)) {
                $this->appendMovement->handle($material, StockMovementType::PriceChange, $today, $actor, [
                    'unit_cost_before' => $material->unit_cost,
                    'unit_cost_after' => (string) $kept,
                    'quantity_after' => $material->quantity_on_hand,
                    'entry_group' => $group,
                ]);
            }

            return;
        }

        foreach ($material->colours as $row) {
            if (in_array($row->colour_id, $colourIds, true) && $row->unit_cost !== null && ! $kept->isEqualTo($row->unit_cost)) {
                $this->appendMovement->handle($material, StockMovementType::PriceChange, $today, $actor, [
                    'unit_cost_before' => $row->unit_cost,
                    'unit_cost_after' => (string) $kept,
                    'quantity_after' => $row->quantity_on_hand,
                    'colour_id' => $row->colour_id,
                    'entry_group' => $group,
                ]);
            }
        }
    }

    /** One side of a split or a join: `$colourId` null is the one stock. */
    private function move(Material $material, StockMovementType $type, ?int $colourId, string $quantity, string $before, string $after, string $group, User $actor): void
    {
        $this->appendMovement->handle($material, $type, today()->toDateString(), $actor, [
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'colour_id' => $colourId,
            'entry_group' => $group,
        ]);
    }
}
