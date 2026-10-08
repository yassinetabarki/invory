import { Link } from '@inertiajs/react';
import { useId } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { Money } from '@/components/shared/money';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import type { Sale } from '@/types/domain';

type RecentSalesCardProps = {
    /** The newest sales of any period (max 5). */
    sales: Sale[];
};

/**
 * The latest sales: a table from `md` up, stacked cards below. Unit price and
 * total are the server's strings, formatted only.
 */
export function RecentSalesCard({ sales }: RecentSalesCardProps) {
    const headingId = useId();
    const { locale } = useAppSettings();

    return (
        <section
            aria-labelledby={headingId}
            className="flex min-w-0 flex-col rounded-xl border bg-card text-card-foreground"
        >
            <div className="flex items-center justify-between gap-3 px-4 pt-4 pb-2">
                <h2 id={headingId} className="text-base font-bold">
                    Recent sales
                </h2>
                <Link
                    href={SaleController.index()}
                    className="text-[0.8125rem] font-semibold text-primary hover:underline focus-visible:underline"
                >
                    View all sales
                </Link>
            </div>

            {sales.length === 0 ? (
                <p className="px-4 pt-4 pb-6 text-sm text-muted-foreground">
                    No sales recorded yet. Sales you record show up here.
                </p>
            ) : (
                <>
                    {/* >= md: table */}
                    <div className="hidden md:block">
                        <Table className="[&_td]:px-4 [&_th]:px-4">
                            <TableHeader>
                                <TableRow>
                                    <TableHead scope="col">Date</TableHead>
                                    <TableHead scope="col">Product</TableHead>
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
                                    <TableHead scope="col">Note</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sales.map((sale) => (
                                    <TableRow key={sale.id}>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {formatDate(sale.sold_on, locale)}
                                        </TableCell>
                                        <TableCell className="font-semibold">
                                            <Link
                                                href={ProductController.show(
                                                    sale.product.id,
                                                )}
                                                className="hover:underline focus-visible:underline"
                                            >
                                                {sale.product.name}
                                            </Link>
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
                                        <TableCell className="text-muted-foreground">
                                            {sale.note}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {/* < md: stacked cards */}
                    <ul aria-label="Recent sales" className="md:hidden">
                        {sales.map((sale) => (
                            <li
                                key={sale.id}
                                className="space-y-1 border-t px-4 py-3"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <Link
                                        href={ProductController.show(
                                            sale.product.id,
                                        )}
                                        className="font-semibold hover:underline focus-visible:underline"
                                    >
                                        {sale.product.name}
                                    </Link>
                                    <span className="font-bold whitespace-nowrap">
                                        <Money value={sale.total} />
                                    </span>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {formatDate(sale.sold_on, locale)}
                                    {' · '}
                                    {formatCount(sale.quantity, locale)} ×{' '}
                                    <Money value={sale.unit_price} />
                                    {sale.note && ` · ${sale.note}`}
                                </p>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
