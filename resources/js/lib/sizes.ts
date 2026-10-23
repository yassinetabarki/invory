import { formatCount } from '@/lib/format';
import type { OrderLineSize } from '@/types/domain';

/** One size and how many units were counted on it. */
export type SizeCount = { label: string; units: number };

/**
 * Writes a list of sizes the way the screens show it: "S 10 · M 20 · L 15 · XL 5".
 * A size named by digits only gets a colon, so the two numbers can't be read
 * as one: "36: 4 · 37: 8". Sizes with 0 units are left out, order is kept, counts use the app's locale
 * (`formatCount`). Nothing is added up here. Returns '' when no size is left.
 *
 * Used by the order page (S-61-b); S-60-e and S-61-c reuse it.
 */
export function formatSizes(sizes: SizeCount[], locale: string): string {
    return sizes
        .filter(({ units }) => units > 0)
        .map(
            ({ label, units }) =>
                `${label}${/^\d+$/.test(label) ? ':' : ''} ${formatCount(units, locale)}`,
        )
        .join(' · ');
}

/** Adapter for an order line's sizes: each size's `quantity` is its units. */
export function orderLineSizeCounts(sizes: OrderLineSize[]): SizeCount[] {
    return sizes.map(({ size, quantity }) => ({
        label: size.label,
        units: quantity,
    }));
}
