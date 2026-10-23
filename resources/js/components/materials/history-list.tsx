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
 * quantities and prices are only formatted, never computed.
 */
function describe(
    entry: StockMovement,
    unit: UnitOfMeasure,
    { currency, locale }: Formatters,
): string {
    const quantity = (value: string | null) =>
        formatQuantity(value, { locale, unit });
    const price = (value: string | null) =>
        formatMoney(value, { currency, locale, precise: true });

    switch (entry.type) {
        case 'restock': {
            const text = `+${quantity(entry.quantity)} at ${price(entry.unit_price)}`;

            return entry.reference ? `${text} · ${entry.reference}` : text;
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
    }
}

/**
 * The stock history of a material: date, type badge, what happened and who did
 * it. Used by the index's History panel and the detail page's History section.
 */
export function HistoryList({ entries, unit, className }: HistoryListProps) {
    const { currency, locale } = useAppSettings();

    if (entries.length === 0) {
        return (
            <p className={cn('text-sm text-muted-foreground', className)}>
                No history yet.
            </p>
        );
    }

    return (
        <ol className={cn('flex flex-col', className)}>
            {entries.map((entry) => (
                <li
                    key={entry.id}
                    className="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 gap-y-1 border-t py-2.5 first:border-t-0 sm:grid-cols-[6rem_7rem_minmax(0,1fr)_auto] sm:gap-x-4"
                >
                    <time
                        dateTime={entry.occurred_on}
                        className="text-sm whitespace-nowrap text-muted-foreground"
                    >
                        {formatDate(entry.occurred_on, locale)}
                    </time>
                    <span>
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
                    </span>
                    <span className="col-span-2 text-sm font-medium tabular-nums sm:col-span-1">
                        {describe(entry, unit, { currency, locale })}
                    </span>
                    {entry.user_name && (
                        <span className="col-span-2 text-xs text-muted-foreground sm:col-span-1">
                            by {entry.user_name}
                        </span>
                    )}
                </li>
            ))}
        </ol>
    );
}
