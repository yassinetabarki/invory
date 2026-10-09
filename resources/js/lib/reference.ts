import { EMPTY_VALUE } from '@/lib/format';
import { trimDecimal } from '@/components/products/decimals';
import type {
    Option,
    ReferenceOption,
    UnitDimension,
    UnitOption,
} from '@/types/domain';

/**
 * Pure helpers over the shared `reference` prop (ADR-0008). Lists arrive in
 * server order (position, then name) and include archived rows. Nothing here
 * does arithmetic: decimals stay strings.
 */

/** Plain text, so screen readers announce it too (never a colour-only cue). */
export const ARCHIVED_SUFFIX = ' (archived)';

/**
 * The rows a picker or filter offers: every active row, plus the archived row
 * whose code is `keepCode` (the record's current value, or the active filter),
 * so an existing value never disappears. Server order is kept. The option
 * labels below add the "(archived)" suffix to that kept archived row.
 */
function visibleRows<T extends ReferenceOption>(
    rows: readonly T[],
    keepCode?: string | null,
): T[] {
    return rows.filter((row) => !row.archived || row.code === keepCode);
}

/** `{ value: code, label: name }` options of a category list. */
export function categoryOptions(
    categories: readonly ReferenceOption[],
    keepCode?: string | null,
): Option[] {
    return visibleRows(categories, keepCode).map((category) => ({
        value: category.code,
        label: category.archived
            ? `${category.name}${ARCHIVED_SUFFIX}`
            : category.name,
    }));
}

/** "Metre (m)": the name with its code, as before the units moved to the database. */
export function unitLabel(unit: Pick<UnitOption, 'name' | 'code'>): string {
    return `${unit.name} (${unit.code})`;
}

/** `{ value: code, label: "Name (code)" }` options of the unit list. */
export function unitOptions(
    units: readonly UnitOption[],
    keepCode?: string | null,
): Option[] {
    return visibleRows(units, keepCode).map((unit) => ({
        value: unit.code,
        label: unit.archived
            ? `${unitLabel(unit)}${ARCHIVED_SUFFIX}`
            : unitLabel(unit),
    }));
}

/** Active rows first, then archived ones; each group keeps the server order. */
export function activeFirst<T extends { archived: boolean }>(
    rows: readonly T[],
): T[] {
    return [
        ...rows.filter((row) => !row.archived),
        ...rows.filter((row) => row.archived),
    ];
}

/** The unit each dimension's factor is expressed in. `package` has none. */
export const BASE_UNIT_CODE: Record<
    Exclude<UnitDimension, 'package'>,
    string
> = {
    length: 'm',
    area: 'sqm',
    mass: 'kg',
    volume: 'l',
    count: 'pc',
};

export const PACKAGE_FACTOR_TEXT = 'Varies per material';

/**
 * How a unit relates to its dimension's base unit: "1 yd = 0.9144 m". The
 * factor is the server's decimal string with trailing zeros dropped (string
 * only, never a number). Package units have no generic factor.
 */
export function describeFactor(
    unit: Pick<UnitOption, 'code' | 'dimension' | 'factor_to_base'>,
): string {
    if (unit.dimension === 'package') {
        return PACKAGE_FACTOR_TEXT;
    }

    if (unit.factor_to_base === null) {
        return EMPTY_VALUE;
    }

    return `1 ${unit.code} = ${trimDecimal(unit.factor_to_base)} ${BASE_UNIT_CODE[unit.dimension]}`;
}
