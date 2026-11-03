import { formatCount } from '@/lib/format';
import type { OrderLine, OrderLineSize } from '@/types/domain';

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

/** Whether an order line is read per colour and size on the order page: counted by size, or holding several colours (INV-13). */
export function linePerCell(line: OrderLine): boolean {
    return line.size_mode === 'by_size' || line.sizes.length > 1;
}

/**
 * An order line's row as one name (INV-13): "Indigo M", "Black, size 38", or the colour alone on a line that counts as one size
 * ("Camel"). The server names a row the same way in its messages.
 */
export function orderCellName(row: OrderLineSize, oneField: boolean): string {
    if (oneField) {
        return row.colour.name;
    }

    return /^\d+$/.test(row.size.label)
        ? `${row.colour.name}, size ${row.size.label}`
        : `${row.colour.name} ${row.size.label}`;
}

/**
 * An order line's units per colour and size, as the Orders list and the client page write them (INV-13, AC-5): each colour named
 * once before its sizes, "Indigo S 10 · M 20 · L 15 · XL 5"; two colours "Black M 1 · Indigo S 1 · M 2"; a line that counts as one
 * size by colour, "Camel 10". The rows come in the server's order (colour list order, then size); nothing is added up here.
 */
export function formatOrderLineCells(line: OrderLine, locale: string): string {
    const oneField = line.size_mode !== 'by_size';
    const groups: { name: string; parts: string[] }[] = [];

    for (const row of line.sizes) {
        if (row.quantity <= 0) {
            continue;
        }

        const part = oneField
            ? formatCount(row.quantity, locale)
            : `${sizeLabelInText(row.size.label)} ${formatCount(row.quantity, locale)}`;
        const last = groups.at(-1);

        if (last && last.name === row.colour.name) {
            last.parts.push(part);
        } else {
            groups.push({ name: row.colour.name, parts: [part] });
        }
    }

    return groups
        .map(({ name, parts }) => `${name} ${parts.join(' · ')}`)
        .join(' · ');
}
