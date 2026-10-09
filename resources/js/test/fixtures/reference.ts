/**
 * The shared `reference` page prop as the server sends it: the defaults the
 * migration seeds (13 units, 12 material categories, 6 product categories),
 * in server order. Factors are scale-12 strings.
 */
import type {
    Option,
    ReferenceData,
    ReferenceOption,
    ReferenceRow,
    UnitDimension,
    UnitOption,
    UnitRow,
} from '@/types/domain';

function reference(
    id: number,
    code: string,
    name: string,
    position: number,
    overrides: Partial<ReferenceOption> = {},
): ReferenceOption {
    return { id, code, name, position, archived: false, ...overrides };
}

const DIMENSION_LABELS: Record<UnitDimension, string> = {
    length: 'Length',
    area: 'Area',
    mass: 'Mass',
    volume: 'Volume',
    count: 'Count',
    package: 'Package',
};

function unit(
    id: number,
    code: string,
    name: string,
    dimension: UnitDimension,
    factor: string | null,
    overrides: Partial<UnitOption> = {},
): UnitOption {
    return {
        ...reference(id, code, name, id),
        dimension,
        dimension_label: DIMENSION_LABELS[dimension],
        factor_to_base: factor,
        is_system: true,
        ...overrides,
    };
}

export const referenceUnits: UnitOption[] = [
    unit(1, 'm', 'Metre', 'length', '1.000000000000'),
    unit(2, 'cm', 'Centimetre', 'length', '0.010000000000'),
    unit(3, 'yd', 'Yard', 'length', '0.914400000000'),
    unit(4, 'sqm', 'Square metre', 'area', '1.000000000000'),
    unit(5, 'sqft', 'Square foot', 'area', '0.092903040000'),
    unit(6, 'kg', 'Kilogram', 'mass', '1.000000000000'),
    unit(7, 'g', 'Gram', 'mass', '0.001000000000'),
    unit(8, 'l', 'Litre', 'volume', '1.000000000000'),
    unit(9, 'ml', 'Millilitre', 'volume', '0.001000000000'),
    unit(10, 'pc', 'Piece', 'count', '1.000000000000'),
    unit(11, 'pair', 'Pair', 'count', '2.000000000000'),
    unit(12, 'roll', 'Roll', 'package', null),
    unit(13, 'cone', 'Cone', 'package', null),
];

function categories(rows: [string, string][]): ReferenceOption[] {
    return rows.map(([code, name], index) =>
        reference(index + 1, code, name, index + 1),
    );
}

export const referenceMaterialCategories: ReferenceOption[] = categories([
    ['fabric', 'Fabric'],
    ['lining', 'Lining'],
    ['interlining', 'Interlining'],
    ['leather', 'Leather'],
    ['thread', 'Thread'],
    ['trim', 'Trim'],
    ['fastener', 'Fastener'],
    ['label', 'Label'],
    ['sole', 'Sole'],
    ['hardware', 'Hardware'],
    ['packaging', 'Packaging'],
    ['other', 'Other'],
]);

export const referenceProductCategories: ReferenceOption[] = categories([
    ['apparel', 'Apparel'],
    ['footwear', 'Footwear'],
    ['bag', 'Bag'],
    ['accessory', 'Accessory'],
    ['jewelry', 'Jewelry'],
    ['other', 'Other'],
]);

/** The default `reference` prop. Override a list to test archived rows or custom entries. */
export function referenceData(
    overrides: Partial<ReferenceData> = {},
): ReferenceData {
    return {
        units: referenceUnits,
        material_categories: referenceMaterialCategories,
        product_categories: referenceProductCategories,
        product_statuses: [
            { value: 'draft', label: 'Draft' },
            { value: 'active', label: 'Active' },
            { value: 'archived', label: 'Archived' },
        ],
        sales_periods: [
            { value: 'last_7_days', label: 'Last 7 days' },
            { value: 'last_30_days', label: 'Last 30 days' },
            { value: 'this_month', label: 'This month' },
            { value: 'this_year', label: 'This year' },
        ],
        stock_statuses: [
            { value: 'in_stock', label: 'In stock' },
            { value: 'low', label: 'Low stock' },
            { value: 'out', label: 'Out of stock' },
        ],
        ...overrides,
    };
}

/** A settings-table row: a reference option plus how many records use it. */
export function referenceRow(
    option: ReferenceOption,
    usageCount = 0,
): ReferenceRow {
    return { ...option, usage_count: usageCount };
}

export function unitRow(option: UnitOption, usageCount = 0): UnitRow {
    return { ...option, usage_count: usageCount };
}

/** Archived copy of a row or option. */
export function archived<T extends ReferenceOption>(option: T): T {
    return { ...option, archived: true };
}

/** The dimension options of the unit form (`UnitDimension::options()`). */
export const dimensionOptions: Option<UnitDimension>[] = (
    Object.entries(DIMENSION_LABELS) as [UnitDimension, string][]
).map(([value, label]) => ({ value, label }));

/** A unit the company added itself: not a system unit, so dimension and factor can be edited. */
export function customUnit(
    overrides: Partial<UnitOption> & Pick<UnitOption, 'id' | 'code' | 'name'>,
): UnitOption {
    return {
        position: 14,
        archived: false,
        dimension: 'length',
        dimension_label: 'Length',
        factor_to_base: '0.025400000000',
        is_system: false,
        ...overrides,
    };
}
