<?php

declare(strict_types=1);

namespace App\Services\Reference;

use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Enums\StockStatus;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;

/**
 * The payload of the shared `reference` once-prop (`ReferenceData` in resources/js/types/domain.ts): the three managed lists,
 * archived rows included (flagged `archived`), ordered by position then name, and the options of the enums that stay in PHP.
 * It also owns the row shapes (`ReferenceOption`, `UnitOption`) that the settings pages reuse.
 *
 * @phpstan-type ReferenceOptionRow array{id: int, code: string, name: string, position: int, archived: bool}
 * @phpstan-type UnitOptionRow array{
 *     id: int, code: string, name: string, position: int, archived: bool,
 *     dimension: string, dimension_label: string, factor_to_base: string|null, is_system: bool,
 * }
 */
final class ReferenceData
{
    /**
     * @return array{
     *     units: list<UnitOptionRow>,
     *     material_categories: list<ReferenceOptionRow>,
     *     product_categories: list<ReferenceOptionRow>,
     *     product_statuses: list<array{value: string, label: string}>,
     *     sales_periods: list<array{value: string, label: string}>,
     *     stock_statuses: list<array{value: string, label: string}>,
     * }
     */
    public function all(): array
    {
        return [
            'units' => array_values(Unit::query()->ordered()->get()->map(self::unit(...))->all()),
            'material_categories' => array_values(MaterialCategory::query()->ordered()->get()->map(self::category(...))->all()),
            'product_categories' => array_values(ProductCategory::query()->ordered()->get()->map(self::category(...))->all()),
            'product_statuses' => ProductStatus::options(),
            'sales_periods' => SalesPeriod::options(),
            'stock_statuses' => StockStatus::options(),
        ];
    }

    /**
     * `ReferenceOption`: a material or product category.
     *
     * @return ReferenceOptionRow
     */
    public static function category(MaterialCategory|ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'code' => $category->code,
            'name' => $category->name,
            'position' => $category->position,
            'archived' => $category->archived_at !== null,
        ];
    }

    /**
     * `UnitOption`. `factor_to_base` is a decimal string at scale 12 (never a float), null for `package` units.
     *
     * @return UnitOptionRow
     */
    public static function unit(Unit $unit): array
    {
        return [
            'id' => $unit->id,
            'code' => $unit->code,
            'name' => $unit->name,
            'position' => $unit->position,
            'archived' => $unit->archived_at !== null,
            'dimension' => $unit->dimension->value,
            'dimension_label' => $unit->dimension->label(),
            'factor_to_base' => $unit->factor_to_base,
            'is_system' => $unit->is_system,
        ];
    }
}
