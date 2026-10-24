import { Link } from '@inertiajs/react';
import { unitsWord } from '@/components/products/product-hero';
import { Money } from '@/components/shared/money';
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
import { EMPTY_VALUE, formatCount, formatDate } from '@/lib/format';
import type { Decimal, Sale } from '@/types/domain';

type RecentSalesCardProps = {
    sales: Sale[];
    /** Units sold and revenue of the last 30 days (the page's `sales_last_30_days`). */
    last30Days: { units_sold: number; revenue: Decimal };
    /** Sales page filtered to this product ("View all"). */
    viewAllHref: string;
    /** Sales page with "Record a sale" open for this product; null when it can't be sold (not active). */
    recordHref: string | null;
};

/** The product's newest sales, with a link to the full list. */
export function RecentSalesCard({
    sales,
    last30Days,
    viewAllHref,
    recordHref,
}: RecentSalesCardProps) {
    const { locale } = useAppSettings();

    return (
        <section
            aria-labelledby="recent-sales-title"
            className="overflow-hidden rounded-xl border bg-card text-card-foreground"
        >
            <div className="flex items-center justify-between gap-3 px-4 pt-5 pb-2 md:px-5">
                <h2 id="recent-sales-title" className="text-base font-bold">
                    Recent sales
                </h2>
                <Link
                    href={viewAllHref}
                    className="text-sm font-semibold text-primary underline-offset-4 hover:underline"
                >
                    View all
                </Link>
            </div>

            {sales.length === 0 ? (
                <p className="px-4 pt-1 pb-5 text-sm text-muted-foreground md:px-5">
                    No sales recorded for this product yet.{' '}
                    {recordHref !== null && (
                        <Link
                            href={recordHref}
                            className="font-semibold text-primary underline underline-offset-2"
                        >
                            Record a sale
                        </Link>
                    )}
                </p>
            ) : (
                <>
                    <p className="px-4 pb-3 text-sm text-muted-foreground md:px-5">
                        {last30Days.units_sold === 0 ? (
                            'No sales in the last 30 days.'
                        ) : (
                            <>
                                {`${formatCount(last30Days.units_sold, locale)} ${unitsWord(last30Days.units_sold)} sold in the last 30 days · `}
                                <Money value={last30Days.revenue} />
                            </>
                        )}
                    </p>

                    {/* >= md: table */}
                    <div className="hidden md:block">
                        <Table>
                            <TableCaption className="sr-only">
                                Recent sales
                            </TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead scope="col" className="pl-5">
                                        Date
                                    </TableHead>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Qty
                                    </TableHead>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Unit price
                                    </TableHead>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Total
                                    </TableHead>
                                    <TableHead scope="col" className="pr-5">
                                        Note
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sales.map((sale) => (
                                    <TableRow key={sale.id}>
                                        <TableCell className="pl-5 text-muted-foreground">
                                            {formatDate(sale.sold_on, locale)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatCount(sale.quantity, locale)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            <Money value={sale.unit_price} />
                                        </TableCell>
                                        <TableCell className="text-right font-bold tabular-nums">
                                            <Money value={sale.total} />
                                        </TableCell>
                                        <TableCell className="pr-5 text-muted-foreground">
                                            {sale.note ?? EMPTY_VALUE}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {/* < md: stacked cards */}
                    <ul
                        aria-label="Recent sales"
                        className="space-y-3 px-4 pb-4 md:hidden"
                    >
                        {sales.map((sale) => (
                            <li
                                key={sale.id}
                                className="space-y-1 rounded-lg border p-3"
                            >
                                <div className="flex items-baseline justify-between gap-3">
                                    <span className="text-sm text-muted-foreground">
                                        {formatDate(sale.sold_on, locale)}
                                    </span>
                                    <span className="font-bold tabular-nums">
                                        <Money value={sale.total} />
                                    </span>
                                </div>
                                <p className="text-sm tabular-nums">
                                    {formatCount(sale.quantity, locale)} ×{' '}
                                    <Money value={sale.unit_price} />
                                </p>
                                {sale.note && (
                                    <p className="text-sm text-muted-foreground">
                                        {sale.note}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
