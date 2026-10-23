import { formatCount } from '@/lib/format';
import type { OrderLineSize } from '@/types/domain';

/** One size and how many units were counted on it. */
export type SizeCount = { label: string; units: number };

/** A size's label as it reads before its count: digits only get a colon ("36:"), so two numbers can't be read as one. */
export function sizeLabelInText(label: string): string {
    return /^\d+$/.test(label) ? `${label}:` : label;
}

/**
 * Writes a list of sizes the way the screens show it: "S 10 · M 20 · L 15 · XL 5".
 * A size named by digits only gets a colon, so the two numbers can't be read
 * as one: "36: 4 · 37: 8". Sizes with 0 units are left out unless `keepZeros` is set (the
 * finished stock lists every size: "S 3 · M 0"), order is kept, counts use the app's locale
 * (`formatCount`). `separator` joins the sizes (" · " by default; ", " for a spoken
 * list). Nothing is added up here. Returns '' when no size is left.
 *
 * Used by the order page (S-61-b); S-60-e and S-61-c reuse it.
 */
export function formatSizes(
    sizes: SizeCount[],
    locale: string,
    {
        keepZeros = false,
        separator = ' · ',
    }: { keepZeros?: boolean; separator?: string } = {},
): string {
    return sizes
        .filter(({ units }) => keepZeros || units > 0)
        .map(
            ({ label, units }) =>
                `${sizeLabelInText(label)} ${formatCount(units, locale)}`,
        )
        .join(separator);
}

/** Adapter for an order line's sizes: each size's `quantity` is its units. */
export function orderLineSizeCounts(sizes: OrderLineSize[]): SizeCount[] {
    return sizes.map(({ size, quantity }) => ({
        label: size.label,
        units: quantity,
    }));
}
