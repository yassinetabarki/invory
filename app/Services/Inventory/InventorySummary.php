<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\MaterialStockMode;
use App\Enums\StockStatus;
use App\Models\Material;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Collection;

/**
 * Inventory totals over every non-deleted material (the whole table, not one page).
 *
 * The keys are the inventory half of `DashboardStats` and the `summary` prop of the materials list in
 * resources/js/types/domain.ts, so callers can use the result as is.
 *
 * Money stays exact on both paths and the two give identical figures (ADR-0011):
 * - PostgreSQL: one aggregate query. The columns are exact NUMERIC, so `SUM(ROUND(quantity_on_hand * unit_cost, 4))` is the sum
 *   of each material's scale-4 stock value, with no row hydrated.
 * - Any other driver (SQLite in the fast test suite sums in floats): a BigDecimal sum of each material's `stockValue()` (already
 *   a scale-4 string, so the total equals the sum of the stock values the materials table shows) over chunks.
 * A material kept per colour (INV-21, ADR-0029 section 8) counts as `Material::stockStatus()` and `stockValue()` say: out when the stored
 * total is 0, low when the reorder level is set and some colour row is at or under it, and with cost per colour the sum of each colour's
 * `round4(quantity x cost)`. The PHP path reads the colour rows of a chunk's per-colour materials in one query; the SQL path in sub-selects.
 * Either way the value is serialised at scale 4, half-up.
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
        return (new Material)->getConnection()->getDriverName() === 'pgsql'
            ? $this->summarizeInSql()
            : $this->summarizeInPhp();
    }

    /**
     * One aggregate query; `toBase()` keeps the SoftDeletes scope (`deleted_at IS NULL`) and skips hydration.
     * `ROUND(numeric, 4)` rounds half away from zero, which is HalfUp for the non-negative values the CHECK constraints guarantee.
     * The filters mirror `stockStatus()` and the `needsRestock` scope (stock is never negative).
     *
     * @return InventoryTotals
     */
    private function summarizeInSql(): array
    {
        $row = Material::query()
            ->without(['category', 'unit'])
            ->toBase()
            ->selectRaw(
                'COUNT(*) AS materials_count, '
                .'COUNT(*) FILTER (WHERE quantity_on_hand <= 0) AS out_of_stock_count, '
                .'COUNT(*) FILTER (WHERE reorder_level IS NOT NULL AND quantity_on_hand > 0 AND (quantity_on_hand <= reorder_level OR (stock_mode = \'per_colour\' AND EXISTS ('
                .'SELECT 1 FROM material_colours AS mc WHERE mc.material_id = materials.id AND mc.quantity_on_hand <= materials.reorder_level)))) AS low_stock_count, '
                .'COALESCE(SUM(CASE WHEN cost_mode = \'per_colour\' THEN ('
                .'SELECT COALESCE(SUM(ROUND(mc.quantity_on_hand * COALESCE(mc.unit_cost, 0), 4)), 0) FROM material_colours AS mc WHERE mc.material_id = materials.id'
                .') ELSE ROUND(quantity_on_hand * unit_cost, 4) END), 0) AS inventory_value'
            )
            ->first();

        $low = (int) $row->low_stock_count;
        $out = (int) $row->out_of_stock_count;

        return [
            'materials_count' => (int) $row->materials_count,
            'inventory_value' => (string) BigDecimal::of((string) $row->inventory_value)->toScale(4, RoundingMode::HalfUp),
            'needs_restock_count' => $low + $out,
            'low_stock_count' => $low,
            'out_of_stock_count' => $out,
        ];
    }

    /** @return InventoryTotals */
    private function summarizeInPhp(): array
    {
        $materialsCount = 0;
        $low = 0;
        $out = 0;
        $value = BigDecimal::zero();

        // Only the columns stockStatus() and stockValue() read: strict models would throw on any other attribute (and the
        // always-eager category and unit would need their `*_id` columns).
        Material::query()
            ->without(['category', 'unit'])
            ->select(['id', 'quantity_on_hand', 'unit_cost', 'reorder_level', 'stock_mode', 'cost_mode'])
            ->chunkById(self::CHUNK_SIZE, function (Collection $materials) use (&$materialsCount, &$low, &$out, &$value): void {
                // One query for the colour rows of this chunk's per-colour materials (none when it has none).
                $materials->filter(fn (Material $material): bool => $material->stock_mode === MaterialStockMode::PerColour)->load('colours');

                foreach ($materials as $material) {
                    $materialsCount++;
                    $value = $value->plus($material->stockValue());

                    match ($material->stockStatus()) {
                        StockStatus::Low => $low++,
                        StockStatus::Out => $out++,
                        StockStatus::InStock => null,
                    };
                }
            });

        return [
            'materials_count' => $materialsCount,
            'inventory_value' => (string) $value->toScale(4, RoundingMode::HalfUp),
            'needs_restock_count' => $low + $out,
            'low_stock_count' => $low,
            'out_of_stock_count' => $out,
        ];
    }
}
