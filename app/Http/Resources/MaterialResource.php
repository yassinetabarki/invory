<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\MaterialCostMode;
use App\Enums\MaterialStockMode;
use App\Models\Material;
use App\Models\MaterialColour;
use App\Services\Materials\MaterialStockSentences;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Material` in resources/js/types/domain.ts. A material kept per colour (INV-21) needs its `colours` (with their `colour`) loaded: the
 * status, the value and the colour rows are read from them, never queried per row.
 *
 * @mixin Material
 */
final class MaterialResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $sentences = app(MaterialStockSentences::class);

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            // Codes, as before the reference tables (ADR-0008); the labels are the row's name ("Metre (m)" for units).
            'category' => $this->category->code,
            'category_label' => $this->category->name,
            'unit' => $this->unit->code,
            'unit_label' => $this->unit->label(),
            'unit_cost' => $this->unit_cost,
            'quantity_on_hand' => $this->quantity_on_hand,
            'reorder_level' => $this->reorder_level,
            'stock_value' => $this->stockValue(),
            'stock_status' => $this->stockStatus()->value,
            'stock_mode' => $this->stock_mode->value,
            'cost_mode' => $this->cost_mode->value,
            'colours' => $this->colourStocks($request),
            // The server-written sentences of a per-colour material (INV-21-j, -k); null for one stock, or when there is nothing to say.
            'unit_cost_range' => $sentences->unitCostRange($this->resource),
            'stock_callout' => $sentences->callout($this->resource),
            'colours_restock_summary' => $sentences->coloursSummary($this->resource),
            'restock_colours' => $sentences->restockColours($this->resource),
            'supplier_name' => $this->supplier_name,
            'supplier_reference' => $this->supplier_reference,
            'color' => $this->color,
            'composition' => $this->composition,
            'notes' => $this->notes,
            // Not columns: `photo_url` is the model's accessor over `photo_path` and `used_in_count` comes from `withUsedInCount()`
            // (strict mode throws when a narrow select left either source out).
            'photo_url' => $this->photo_url,
            'used_in_count' => $this->used_in_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * `MaterialColourStock[]`: Settings › Colours order; empty for a one-stock material. `unit_cost` and `stock_value` only with cost per colour.
     *
     * @return list<array<string, mixed>>
     */
    private function colourStocks(Request $request): array
    {
        if ($this->stock_mode !== MaterialStockMode::PerColour) {
            return [];
        }

        $perColourCost = $this->cost_mode === MaterialCostMode::PerColour;

        return array_values($this->colours->map(function (MaterialColour $row) use ($request, $perColourCost): array {
            $reason = $this->colourRemovalRefusal($row, $row->getAttribute('has_history') !== null);

            return [
                'colour' => (new ColourResource($row->colour))->resolve($request),
                'quantity_on_hand' => $row->quantity_on_hand,
                'unit_cost' => $perColourCost ? $row->unit_cost : null,
                'stock_value' => $perColourCost && $row->unit_cost !== null
                    ? (string) BigDecimal::of($row->quantity_on_hand)->multipliedBy($row->unit_cost)->toScale(4, RoundingMode::HalfUp)
                    : null,
                'stock_status' => $this->colourStatus($row)->value,
                'removable' => $reason === null,
                'remove_reason' => $reason,
            ];
        })->all());
    }
}
