<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\StockStatus;
use App\Models\Material;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Inventory totals over every non-deleted material (the whole table, not one page).
 *
 * The keys are the inventory half of `DashboardStats` and the `summary` prop of the materials list in
 * resources/js/types/domain.ts, so callers can use the result as is.
 *
 * Money stays exact: the value is a BigDecimal sum of each material's `stockValue()` (already a scale-4
 * string, so the total equals the sum of the stock values the materials table shows), serialised at
 * scale 4, half-up. Nothing is summed in SQL, because SQLite would do it in floats.
 *
 * @phpstan-type InventoryTotals array{
 *     materials_count: int,
 *     inventory_value: string,
 *     needs_restock_count: int,
 *     low_stock_count: int,
 *     out_of_stock_count: int,
 * }
 */
final class InventorySummary
{
    /** Rows loaded per query, so memory stays flat however large the catalogue grows. */
    private const CHUNK_SIZE = 500;

    /**
     * `needs_restock_count` is `low_stock_count + out_of_stock_count` (see "Stock status" in the costing domain skill).
     *
     * @return InventoryTotals
     */
    public function summarize(): array
    {
        $materialsCount = 0;
        $low = 0;
        $out = 0;
        $value = BigDecimal::zero();

        // Only the columns stockStatus() and stockValue() read: strict models would throw on any other attribute (and the
        // always-eager category and unit would need their `*_id` columns).
        $materials = Material::query()
            ->without(['category', 'unit'])
            ->select(['id', 'quantity_on_hand', 'unit_cost', 'reorder_level'])
            ->lazyById(self::CHUNK_SIZE);

        foreach ($materials as $material) {
            $materialsCount++;
            $value = $value->plus($material->stockValue());

            match ($material->stockStatus()) {
                StockStatus::Low => $low++,
                StockStatus::Out => $out++,
                StockStatus::InStock => null,
            };
        }

        return [
            'materials_count' => $materialsCount,
            'inventory_value' => (string) $value->toScale(4, RoundingMode::HalfUp),
            'needs_restock_count' => $low + $out,
            'low_stock_count' => $low,
            'out_of_stock_count' => $out,
        ];
    }
}
