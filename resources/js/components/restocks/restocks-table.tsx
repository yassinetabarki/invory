import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { RestockSummary } from '@/types/domain';

/** "Denim Jacket 24 · Denim Jeans 5-pocket 10", or "Denim Jacket 24 · Leather Tote 6 and 2 more". */
export function productsLine(restock: RestockSummary, locale: string): string {
    const named = restock.products
        .map(
            (product) =>
                `${product.name} ${formatCount(product.units, locale)}`,
        )
        .join(' · ');

    return restock.more_products > 0
        ? `${named} and ${restock.more_products} more`
        : named;
}

/** "Open restock of 25 Oct 2026, BL-0412" (no reference: "Open restock of 22 Oct 2026"). */
export function openLabel(restock: RestockSummary, locale: string): string {
    const date = formatDate(restock.restocked_on, locale);

    return restock.reference
        ? `Open restock of ${date}, ${restock.reference}`
        : `Open restock of ${date}`;
}

/** "34 units", "1 unit". */
export function unitsText(units: number, locale: string): string {
    return `${formatCount(units, locale)} ${units === 1 ? 'unit' : 'units'}`;
}

function UndoneBadge() {
    return (
        <Badge
            variant="outline"
            className="rounded-full border-transparent bg-neutral font-semibold text-neutral-foreground"
        >
            Undone
        </Badge>
    );
}

type RestocksTableProps = {
    restocks: RestockSummary[];
    onOpen: (restock: RestockSummary) => void;
};

/**
 * The restocks (INV-3, AC-2): a table on a laptop (Date · Reference ·
 * Products · Units · By · Open), one card per restock on a phone. A click on
 * a row, its **Open** button or the card opens the restock's panel. An undone
 * restock carries the grey **Undone** badge and its units in grey. A long
 * reference or product name is cut with "…", in full in the panel.
 */
export function RestocksTable({ restocks, onOpen }: RestocksTableProps) {
    const { locale } = useAppSettings();

    return (
        <>
            <ul aria-label="Restocks" className="divide-y md:hidden">
                {restocks.map((restock) => (
                    <li key={restock.id}>
                        <button
                            type="button"
                            aria-label={openLabel(restock, locale)}
                            className="flex w-full flex-col gap-1 p-4 text-left hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none"
                            onClick={() => onOpen(restock)}
                        >
                            <span className="flex min-w-0 items-center gap-2 font-semibold">
                                <span className="truncate">
                                    {formatDate(restock.restocked_on, locale)}
                                    {restock.reference
                                        ? ` · ${restock.reference}`
                                        : ''}
                                </span>
                                {restock.undone && <UndoneBadge />}
                            </span>
                            <span className="truncate text-sm">
                                {productsLine(restock, locale)}
                            </span>
                            <span className="text-sm text-muted-foreground">
                                <span
                                    className={cn(
                                        'font-semibold',
                                        restock.undone
                                            ? 'text-muted-foreground'
                                            : 'text-foreground',
                                    )}
                                >
                                    {unitsText(restock.units, locale)}
                                </span>{' '}
                                · by {restock.recorded_by ?? '—'}
                            </span>
                        </button>
                    </li>
                ))}
            </ul>

            <Table className="hidden table-fixed md:table">
                <TableCaption className="sr-only">Restocks</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-[14%]">Date</TableHead>
                        <TableHead className="w-[22%]">Reference</TableHead>
                        <TableHead className="w-[36%]">Products</TableHead>
                        <TableHead className="w-[10%] text-right">
                            Units
                        </TableHead>
                        <TableHead className="w-[10%]">By</TableHead>
                        <TableHead className="w-[8%]">
                            <span className="sr-only">Open</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {restocks.map((restock) => (
                        <TableRow
                            key={restock.id}
                            className="cursor-pointer"
                            onClick={() => onOpen(restock)}
                        >
                            <TableCell className="whitespace-nowrap">
                                {formatDate(restock.restocked_on, locale)}
                            </TableCell>
                            <TableCell>
                                <span className="flex min-w-0 items-center gap-2">
                                    <span className="truncate">
                                        {restock.reference ?? '—'}
                                    </span>
                                    {restock.undone && <UndoneBadge />}
                                </span>
                            </TableCell>
                            <TableCell className="truncate">
                                {productsLine(restock, locale)}
                            </TableCell>
                            <TableCell
                                className={cn(
                                    'text-right font-mono tabular-nums',
                                    restock.undone && 'text-muted-foreground',
                                )}
                            >
                                {formatCount(restock.units, locale)}
                            </TableCell>
                            <TableCell className="truncate">
                                {restock.recorded_by ?? '—'}
                            </TableCell>
                            <TableCell className="text-right">
                                <button
                                    type="button"
                                    aria-label={openLabel(restock, locale)}
                                    className="text-sm font-medium text-primary hover:underline focus-visible:underline"
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        onOpen(restock);
                                    }}
                                >
                                    Open
                                </button>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </>
    );
}
