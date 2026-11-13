import { ColourTag } from '@/components/shared/colour-tag';
import { Badge } from '@/components/ui/badge';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';
import { formatSizes } from '@/lib/sizes';
import { cn } from '@/lib/utils';
import type {
    StockMovement,
    StockMovementType,
    UnitOfMeasure,
} from '@/types/domain';

type HistoryListProps = {
    /** Movements of ONE material, newest first (the order the server sends). */
    entries: StockMovement[];
    /** The material's unit, shown next to every quantity. */
    unit: UnitOfMeasure;
    className?: string;
};

/** Text and colour always travel together: the label is never omitted. */
const TONE: Record<StockMovementType, string> = {
    restock: 'bg-success text-success-foreground',
    price_change: 'bg-info text-info-foreground',
    correction: 'bg-warning text-warning-foreground',
    created: 'bg-neutral text-neutral-foreground',
    cutting: 'bg-warning text-warning-foreground',
    cutting_return: 'bg-success text-success-foreground',
    colour_split: 'bg-info text-info-foreground',
    colour_join: 'bg-info text-info-foreground',
};

type Formatters = { currency: string; locale: string };

/**
 * "−59.4 m · Boutique Lina · 30 × Denim Jacket": the sign (U+2212 for cutting)
 * is a literal prefix, the server sends the quantity positive. A missing order
 * or product is left out, never printed as "null". A line counted by size adds
 * its sizes: "14 × Denim Jacket (S 4 · M 10)".
 */
function describeCut(
    entry: StockMovement,
    quantity: string,
    locale: string,
): string {
    const sizes = entry.sizes ? formatSizes(entry.sizes, locale) : '';
    const product =
        sizes && entry.product_name
            ? `${entry.product_name} (${sizes})`
            : entry.product_name;
    const what =
        entry.units !== null && entry.product_name
            ? `${entry.units} × ${product}`
            : product;

    return [quantity, entry.order?.client, what].filter(Boolean).join(' · ');
}

/**
 * What happened, as a sentence. The `+` before a restock is a literal prefix:
 * quantities and prices are only formatted, never computed. The reference of a
 * receipt's entries is left out when the group heading already shows it.
 */
function describe(
    entry: StockMovement,
    unit: UnitOfMeasure,
    { currency, locale }: Formatters,
    showReference: boolean,
): string {
    const quantity = (value: string | null) =>
        formatQuantity(value, { locale, unit });
    const price = (value: string | null) =>
        formatMoney(value, { currency, locale, precise: true });

    switch (entry.type) {
        case 'restock': {
            const text = `+${quantity(entry.quantity)} at ${price(entry.unit_price)}`;

            return entry.reference && showReference
                ? `${text} · ${entry.reference}`
                : text;
        }
        case 'created':
            return `${quantity(entry.quantity)} at ${price(entry.unit_price)}`;
        case 'correction': {
            const text = `${quantity(entry.quantity_before)} → ${quantity(entry.quantity_after)}`;

            return entry.note ? `${text} (${entry.note})` : text;
        }
        case 'price_change':
            return `${price(entry.unit_cost_before)} → ${price(entry.unit_cost_after)} per ${unit}`;
        case 'cutting':
            return describeCut(entry, `−${quantity(entry.quantity)}`, locale);
        case 'cutting_return':
            return describeCut(entry, `+${quantity(entry.quantity)}`, locale);
        case 'colour_split':
        case 'colour_join':
            return quantity(entry.quantity);
    }
}

type DescriptionProps = {
    entry: StockMovement;
    unit: UnitOfMeasure;
    formatters: Formatters;
    showReference: boolean;
};

/** What the entry says, led by its colour (swatch and name) when it has one. */
function Description({
    entry,
    unit,
    formatters,
    showReference,
}: DescriptionProps) {
    return (
        <span className="inline-flex flex-wrap items-center gap-x-1.5">
            {entry.colour && <ColourTag colour={entry.colour} />}
            <span>{describe(entry, unit, formatters, showReference)}</span>
        </span>
    );
}

function TypeBadge({ entry }: { entry: StockMovement }) {
    return (
        <Badge
            variant="outline"
            data-type={entry.type}
            className={cn(
                'rounded-full border-transparent font-semibold',
                TONE[entry.type],
            )}
        >
            {entry.type_label}
        </Badge>
    );
}

/** Entries of one receipt or switch: the server tags them with one `entry_group`. */
function toBlocks(entries: StockMovement[]): StockMovement[][] {
    const blocks: StockMovement[][] = [];
    const groups = new Map<string, StockMovement[]>();

    for (const entry of entries) {
        const group = entry.entry_group ? groups.get(entry.entry_group) : null;

        if (group) {
            group.push(entry);
            continue;
        }

        const block = [entry];
        blocks.push(block);

        if (entry.entry_group) {
            groups.set(entry.entry_group, block);
        }
    }

    return blocks;
}

/**
 * A switch is ONE line: "Split by colour · Indigo 250 m" names the coloured
 * entry's quantity, "Joined into one stock · 290 m" the colourless one's.
 */
function switchEntry(entries: StockMovement[]): StockMovement {
    const wantsColour = entries[0].type === 'colour_split';

    return (
        entries.find((entry) => (entry.colour !== null) === wantsColour) ??
        entries[0]
    );
}

/**
 * The stock history of a material: date, type badge, what happened and who did
 * it. Used by the index's History panel and the detail page's History section.
 */
export function HistoryList({ entries, unit, className }: HistoryListProps) {
    const { currency, locale } = useAppSettings();
    const formatters = { currency, locale };

    if (entries.length === 0) {
        return (
            <p className={cn('text-sm text-muted-foreground', className)}>
                No history yet.
            </p>
        );
    }

    return (
        <ol className={cn('flex flex-col', className)}>
            {toBlocks(entries).map((block) => {
                const first = block[0];
                const isSwitch =
                    first.type === 'colour_split' ||
                    first.type === 'colour_join';

                if (block.length === 1 || isSwitch) {
                    const entry = isSwitch ? switchEntry(block) : first;

                    return (
                        <li
                            key={first.id}
                            className="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-1 border-t py-2.5 first:border-t-0 sm:grid-cols-[6rem_7rem_minmax(0,1fr)_auto] sm:gap-x-4"
                        >
                            <time
                                dateTime={entry.occurred_on}
                                className="text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {formatDate(entry.occurred_on, locale)}
                            </time>
                            <span>
                                <TypeBadge entry={entry} />
                            </span>
                            <span className="col-span-2 text-sm font-medium tabular-nums sm:col-span-1">
                                <Description
                                    entry={entry}
                                    unit={unit}
                                    formatters={formatters}
                                    showReference
                                />
                            </span>
                            {entry.user_name && (
                                <span className="col-span-2 text-xs text-muted-foreground sm:col-span-1">
                                    by {entry.user_name}
                                </span>
                            )}
                        </li>
                    );
                }

                const reference = block.find(
                    (entry) => entry.reference,
                )?.reference;

                return (
                    <li
                        key={first.id}
                        className="border-t py-2.5 first:border-t-0"
                    >
                        <p className="flex flex-wrap items-baseline gap-x-3 gap-y-1 text-sm text-muted-foreground">
                            <time dateTime={first.occurred_on}>
                                {formatDate(first.occurred_on, locale)}
                            </time>
                            {reference && <span>{reference}</span>}
                            {first.user_name && (
                                <span className="text-xs">
                                    by {first.user_name}
                                </span>
                            )}
                        </p>
                        <ol className="mt-1.5 flex flex-col gap-1.5 sm:pl-24">
                            {block.map((entry) => (
                                <li
                                    key={entry.id}
                                    className="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 sm:grid-cols-[7rem_minmax(0,1fr)] sm:gap-x-4"
                                >
                                    <span>
                                        <TypeBadge entry={entry} />
                                    </span>
                                    <span className="text-sm font-medium tabular-nums">
                                        <Description
                                            entry={entry}
                                            unit={unit}
                                            formatters={formatters}
                                            showReference={false}
                                        />
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </li>
                );
            })}
        </ol>
    );
}
