import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Money } from '@/components/shared/money';
import { Button } from '@/components/ui/button';
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
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { EMPTY_VALUE, formatCount, formatDate } from '@/lib/format';
import type { Sale } from '@/types/domain';

type SalesTableProps = {
    sales: Sale[];
    /** The page opens the form; this component never saves. */
    onEdit: (sale: Sale) => void;
    /** The page opens the confirm dialog; this component never deletes. */
    onDelete: (sale: Sale) => void;
};

type RowActionsProps = {
    sale: Sale;
    /** Formatted date, so the control names the sale it acts on. */
    date: string;
    onEdit: (sale: Sale) => void;
    onDelete: (sale: Sale) => void;
};

/** Icon-only actions for the table; every control names the product and date. */
function IconActions({ sale, date, onEdit, onDelete }: RowActionsProps) {
    const target = `sale of ${sale.product.name} on ${date}`;

    return (
        <div className="flex justify-end gap-1">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={`Edit ${target}`}
                title="Edit"
                onClick={() => onEdit(sale)}
            >
                <Pencil aria-hidden="true" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={`Delete ${target}`}
                title="Delete"
                className="text-destructive-foreground hover:text-destructive-foreground"
                onClick={() => onDelete(sale)}
            >
                <Trash2 aria-hidden="true" />
            </Button>
        </div>
    );
}

/** Labelled actions for the mobile cards (larger touch targets). */
function TextActions({ sale, date, onEdit, onDelete }: RowActionsProps) {
    const target = `sale of ${sale.product.name} on ${date}`;

    return (
        <div className="flex flex-wrap gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                aria-label={`Edit ${target}`}
                onClick={() => onEdit(sale)}
            >
                <Pencil aria-hidden="true" />
                Edit
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                aria-label={`Delete ${target}`}
                className="text-destructive-foreground hover:text-destructive-foreground"
                onClick={() => onDelete(sale)}
            >
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </div>
    );
}

/**
 * The sales log: a table from `md` up, stacked cards below. Unit price and
 * total are the server's strings, formatted only (the total is computed
 * server-side, never here).
 */
export function SalesTable({ sales, onEdit, onDelete }: SalesTableProps) {
    const { locale } = useAppSettings();

    return (
        <>
            {/* >= md: table */}
            <div className="hidden md:block">
                <Table className="[&_td]:px-4 [&_th]:px-4">
                    <TableCaption className="sr-only">Sales</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Date</TableHead>
                            <TableHead scope="col">Product</TableHead>
                            <TableHead scope="col" className="text-right">
                                Qty
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Unit price
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Total
                            </TableHead>
                            <TableHead scope="col">Note</TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {sales.map((sale) => {
                            const date = formatDate(sale.sold_on, locale);

                            return (
                                <TableRow key={sale.id}>
                                    <TableCell className="text-muted-foreground">
                                        {date}
                                    </TableCell>
                                    <TableCell>
                                        <Link
                                            {...prefetchProps(
                                                CACHE_TAGS.products,
                                            )}
                                            href={ProductController.show(
                                                sale.product.id,
                                            )}
                                            className="font-semibold hover:underline focus-visible:underline"
                                        >
                                            {sale.product.name}
                                        </Link>
                                        <span className="block font-mono text-xs text-muted-foreground">
                                            {sale.product.sku}
                                        </span>
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
                                    <TableCell className="max-w-48 truncate text-muted-foreground">
                                        {sale.note ?? EMPTY_VALUE}
                                    </TableCell>
                                    <TableCell>
                                        <IconActions
                                            sale={sale}
                                            date={date}
                                            onEdit={onEdit}
                                            onDelete={onDelete}
                                        />
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>

            {/* < md: stacked cards */}
            <ul aria-label="Sales" className="md:hidden">
                {sales.map((sale) => {
                    const date = formatDate(sale.sold_on, locale);

                    return (
                        <li key={sale.id} className="space-y-3 border-t p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <Link
                                        {...prefetchProps(CACHE_TAGS.products)}
                                        href={ProductController.show(
                                            sale.product.id,
                                        )}
                                        className="font-semibold break-words hover:underline focus-visible:underline"
                                    >
                                        {sale.product.name}
                                    </Link>
                                    <p className="font-mono text-xs text-muted-foreground">
                                        {sale.product.sku}
                                    </p>
                                </div>
                                <span className="font-bold whitespace-nowrap tabular-nums">
                                    <Money value={sale.total} />
                                </span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {date}
                                {' · '}
                                {formatCount(sale.quantity, locale)} ×{' '}
                                <Money value={sale.unit_price} />
                                {sale.note && ` · ${sale.note}`}
                            </p>
                            <TextActions
                                sale={sale}
                                date={date}
                                onEdit={onEdit}
                                onDelete={onDelete}
                            />
                        </li>
                    );
                })}
            </ul>
        </>
    );
}
