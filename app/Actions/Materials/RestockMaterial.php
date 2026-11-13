<?php

declare(strict_types=1);

namespace App\Actions\Materials;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Models\User;
use App\Services\Materials\MaterialStockSentences;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Books a receipt of material (costing skill, "Restock and price history"):
 *
 *     quantity_on_hand' = quantity_on_hand + q
 *     unit_cost'        = price paid   when update_unit_cost (the default): the latest price, not an average
 *                       = unit_cost    otherwise
 *
 * One `restock` movement, plus a `price_change` only when the unit cost actually changed, both dated with the receipt date.
 * Everything runs in one transaction under the row lock of the material, so two receipts at once add up instead of one
 * overwriting the other; quantities and costs are written by {@see MaterialStockWriter} only.
 *
 * A material kept per colour (INV-21, ADR-0029 section 8) takes one quantity per colour (`lines`): a `restock` entry for each colour
 * received, and a `price_change` where the cost changed: that colour's own cost with cost per colour, else the one cost, once. Colours not
 * received are left as they are. All the entries of the receipt share one `entry_group`.
 */
final readonly class RestockMaterial
{
    /** The largest balance `materials.quantity_on_hand` (decimal(14,4)) can hold. */
    private const MAX_STOCK = '9999999999.9999';

    public function __construct(
        private MaterialStockWriter $stock,
        private AppendStockMovement $appendMovement,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input: `received_on` (Y-m-d), optional `reference`, optional `update_unit_cost` (true when omitted), and
     *                                      one stock: `quantity`, `unit_price`; per colour: `lines[colourId]['quantity']` (empty = not received) and
     *                                      `lines[colourId]['unit_price']` with cost per colour, else `unit_price`
     *
     * @throws ValidationException when the new balance would not fit the stock column, or a colour is not the material's any more
     */
    public function handle(Material $material, array $data, User $actor): Material
    {
        return DB::transaction(function () use ($material, $data, $actor): Material {
            $this->stock->lock($material);
            $material->updated_by = $actor->id;

            return $material->stock_mode === MaterialStockMode::PerColour
                ? $this->restockColours($material, $data, $actor)
                : $this->restockOneStock($material, $data, $actor);
        });
    }

    /** @param  array<string, mixed>  $data */
    private function restockOneStock(Material $material, array $data, User $actor): Material
    {
        $quantity = BigDecimal::of($data['quantity']);
        $price = BigDecimal::of($data['unit_price'])->toScale(4, RoundingMode::HalfUp);
        $updateUnitCost = (bool) ($data['update_unit_cost'] ?? true);

        $stockAfter = BigDecimal::of($material->quantity_on_hand)->plus($quantity)->toScale(4, RoundingMode::HalfUp);

        if ($stockAfter->isGreaterThan(self::MAX_STOCK)) {
            throw ValidationException::withMessages([
                'quantity' => __('The stock would exceed :max.', ['max' => self::MAX_STOCK]),
            ]);
        }

        $costBefore = BigDecimal::of($material->unit_cost);

        $this->stock->addQuantity($material, null, $quantity);

        if ($updateUnitCost) {
            $this->stock->setCost($material, $price);
        }

        $costAfter = BigDecimal::of($material->unit_cost);
        $receivedOn = (string) $data['received_on'];

        $this->appendMovement->handle($material, StockMovementType::Restock, $receivedOn, $actor, [
            'quantity' => (string) $quantity->toScale(4, RoundingMode::HalfUp),
            'quantity_after' => (string) $stockAfter,
            'unit_price' => (string) $price,
            'reference' => $data['reference'] ?? null,
        ]);

        if (! $costAfter->isEqualTo($costBefore)) {
            $this->appendMovement->handle($material, StockMovementType::PriceChange, $receivedOn, $actor, [
                'unit_cost_before' => (string) $costBefore,
                'unit_cost_after' => (string) $costAfter,
                'quantity_after' => (string) $stockAfter,
            ]);
        }

        return $material;
    }

    /** @param  array<string, mixed>  $data */
    private function restockColours(Material $material, array $data, User $actor): Material
    {
        $rows = $material->loadMissing('colours.colour')->colours->keyBy(fn (MaterialColour $row): int => (int) $row->colour_id);
        $received = $this->receivedLines($data, $material->cost_mode === MaterialCostMode::PerColour);

        // Someone changed the colours of the material between the sheet and the save: nothing is booked.
        if (array_diff_key($received, $rows->all()) !== []) {
            throw ValidationException::withMessages([
                'lines' => __('The colours of :name have changed. Enter the quantities again.', ['name' => $material->name]),
            ]);
        }

        $total = BigDecimal::of($material->quantity_on_hand);

        foreach ($received as $colourId => $line) {
            $total = $total->plus($line['quantity']);

            if ($total->isGreaterThan(self::MAX_STOCK)) {
                throw ValidationException::withMessages([
                    "lines.{$colourId}.quantity" => $rows->get($colourId)?->colour->name.': '.MaterialStockSentences::overflow(self::MAX_STOCK),
                ]);
            }
        }

        $group = (string) Str::uuid();
        $receivedOn = (string) $data['received_on'];
        $updateUnitCost = (bool) ($data['update_unit_cost'] ?? true);
        $perColourCost = $material->cost_mode === MaterialCostMode::PerColour;
        $costBefore = BigDecimal::of($material->unit_cost);
        $price = null;

        foreach ($rows as $colourId => $row) {
            if (! isset($received[$colourId])) {
                continue;
            }

            $quantity = BigDecimal::of($received[$colourId]['quantity'])->toScale(4, RoundingMode::HalfUp);
            $price = BigDecimal::of($received[$colourId]['unit_price'])->toScale(4, RoundingMode::HalfUp);
            $quantityAfter = BigDecimal::of($row->quantity_on_hand)->plus($quantity)->toScale(4, RoundingMode::HalfUp);
            $colourCostBefore = $row->unit_cost ?? $material->unit_cost;

            $this->stock->addQuantity($material, $colourId, $quantity);

            $this->appendMovement->handle($material, StockMovementType::Restock, $receivedOn, $actor, [
                'quantity' => (string) $quantity,
                'quantity_after' => (string) $quantityAfter,
                'unit_price' => (string) $price,
                'reference' => $data['reference'] ?? null,
                'colour_id' => $colourId,
                'entry_group' => $group,
            ]);

            if ($perColourCost && $updateUnitCost && ! $price->isEqualTo($colourCostBefore)) {
                $this->stock->setColourCost($material, $colourId, $price);

                $this->appendMovement->handle($material, StockMovementType::PriceChange, $receivedOn, $actor, [
                    'unit_cost_before' => $colourCostBefore,
                    'unit_cost_after' => (string) $price,
                    'quantity_after' => (string) $quantityAfter,
                    'colour_id' => $colourId,
                    'entry_group' => $group,
                ]);
            }
        }

        // The same cost: one price for the whole receipt, one change of the one cost.
        if (! $perColourCost && $updateUnitCost && $price !== null && ! $price->isEqualTo($costBefore)) {
            $this->stock->setCost($material, $price);

            $this->appendMovement->handle($material, StockMovementType::PriceChange, $receivedOn, $actor, [
                'unit_cost_before' => (string) $costBefore,
                'unit_cost_after' => (string) $price,
                'quantity_after' => $material->quantity_on_hand,
                'entry_group' => $group,
            ]);
        }

        return $material->load('colours.colour');
    }

    /**
     * The colours that received a quantity, by colour id, each with the price paid for it: its own with cost per colour, else the receipt's.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{quantity: string, unit_price: string}>
     *
     * @throws LogicException when a received colour has no price (the request asks for it)
     */
    private function receivedLines(array $data, bool $perColourCost): array
    {
        $received = [];

        foreach ((array) ($data['lines'] ?? []) as $colourId => $line) {
            $quantity = is_array($line) ? ($line['quantity'] ?? null) : null;

            if ($quantity === null || $quantity === '') {
                continue;
            }

            $price = $perColourCost ? $line['unit_price'] ?? null : $data['unit_price'] ?? null;

            $received[(int) $colourId] = [
                'quantity' => (string) $quantity,
                'unit_price' => (string) ($price ?? throw new LogicException("Colour #{$colourId} received a quantity without a price.")),
            ];
        }

        return $received;
    }
}
