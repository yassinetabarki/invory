<?php

declare(strict_types=1);

namespace App\Services\Reference;

use App\Enums\ProductStatus;
use App\Enums\SalesPeriod;
use App\Enums\StockStatus;
use App\Models\MaterialCategory;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

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
    private const UNITS_ATTRIBUTE = 'invory.units';

    private const MATERIAL_CATEGORIES_ATTRIBUTE = 'invory.material_categories';

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
            'units' => array_values($this->units()->map(self::unit(...))->all()),
            'material_categories' => array_values($this->materialCategories()->map(self::category(...))->all()),
            'product_categories' => array_values(ProductCategory::query()->ordered()->get()->map(self::category(...))->all()),
            'product_statuses' => ProductStatus::options(),
            'sales_periods' => SalesPeriod::options(),
            'stock_statuses' => StockStatus::options(),
        ];
    }

    /**
     * Every unit by id, in display order. Read once per request (kept on the request, so it can never outlive it): the shared
     * `reference` prop and a page that needs the units of its rows (the usage of a material) share the one query.
     *
     * @return Collection<int, Unit>
     */
    public function units(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::UNITS_ATTRIBUTE)) {
            $attributes->set(self::UNITS_ATTRIBUTE, Unit::query()->ordered()->get()->keyBy('id'));
        }

        /** @var Collection<int, Unit> */
        return $attributes->get(self::UNITS_ATTRIBUTE);
    }

    /**
     * Every material category by id, in display order. Read once per request, like {@see self::units()}: the shared `reference`
     * prop and a page that lists materials with their category (the dashboard's restock list) share the one query.
     *
     * @return Collection<int, MaterialCategory>
     */
    public function materialCategories(): Collection
    {
        $attributes = request()->attributes;

        if (! $attributes->has(self::MATERIAL_CATEGORIES_ATTRIBUTE)) {
            $attributes->set(self::MATERIAL_CATEGORIES_ATTRIBUTE, MaterialCategory::query()->ordered()->get()->keyBy('id'));
        }

        /** @var Collection<int, MaterialCategory> */
        return $attributes->get(self::MATERIAL_CATEGORIES_ATTRIBUTE);
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
