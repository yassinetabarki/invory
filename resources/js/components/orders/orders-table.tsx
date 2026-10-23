import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
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
import { ProgressStatus } from '@/components/orders/progress-status';
import { formatCount, formatDate } from '@/lib/format';
import { formatSizes, orderLineSizeCounts } from '@/lib/sizes';
import { edit, show as showOrder } from '@/routes/orders';
import { show } from '@/routes/products';
import type { Order } from '@/types/domain';

/** An order lists this many products; the rest collapse into "+ N more". */
const VISIBLE_LINES = 3;

/** The four stages, in the order the columns and the card line show them. */
const STAGES = [
    { key: 'to_start', label: 'To start' },
    { key: 'in_cutting', label: 'In cutting' },
    { key: 'ready_to_ship', label: 'Ready to ship' },
    { key: 'shipped', label: 'Shipped' },
] as const;

type OrdersTableProps = {
    orders: Order[];
    /** The page opens the confirm dialog; this component never deletes. */
    onDelete: (order: Order) => void;
};

type RowActionsProps = {
    order: Order;
    /** Formatted date, so each control names the order it acts on. */
    date: string;
    onDelete: (order: Order) => void;
};

/**
 * One "50 × Denim Jacket" line per product; a deleted product has no link. A
 * line counted by size adds its sizes: "50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)".
 */
function ProductLines({ order }: { order: Order }) {
    const { locale } = useAppSettings();
    const hidden = order.lines.length - VISIBLE_LINES;

    return (
        <ul className="space-y-0.5">
            {order.lines.slice(0, VISIBLE_LINES).map((line) => {
                const sizes =
                    line.size_mode === 'by_size'
                        ? formatSizes(orderLineSizeCounts(line.sizes), locale)
                        : '';

                return (
                    <li key={line.id} className="break-words">
                        <span className="tabular-nums">
                            {formatCount(line.quantity, locale)}
                        </span>{' '}
                        ×{' '}
                        {line.product.deleted ? (
                            line.product.name
                        ) : (
                            <Link
                                href={show(line.product.id)}
                                className="font-semibold hover:underline focus-visible:underline"
                            >
                                {line.product.name}
                            </Link>
                        )}
                        {sizes && (
                            <span className="tabular-nums"> ({sizes})</span>
                        )}
                    </li>
                );
            })}
            {hidden > 0 && (
                <li className="text-muted-foreground">+ {hidden} more</li>
            )}
        </ul>
    );
}

/** The client's name, linking to the order's page. */
function ClientLink({ order }: { order: Order }) {
    return (
        <Link
            href={showOrder(order.id)}
            className="hover:underline focus-visible:underline"
        >
            {order.client}
        </Link>
    );
}

/** Icon-only actions for the table; every control names the client and date. */
function IconActions({ order, date, onDelete }: RowActionsProps) {
    const target = `order of ${order.client} on ${date}`;

    return (
        <div className="flex justify-end gap-1">
            <Button asChild variant="ghost" size="icon" title="Edit">
                <Link href={edit(order.id)} aria-label={`Edit ${target}`}>
                    <Pencil aria-hidden="true" />
                </Link>
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                aria-label={`Delete ${target}`}
                title="Delete"
                className="text-destructive-foreground hover:text-destructive-foreground"
                onClick={() => onDelete(order)}
            >
                <Trash2 aria-hidden="true" />
            </Button>
        </div>
    );
}

/** Labelled actions for the phone cards (larger touch targets). */
function TextActions({ order, date, onDelete }: RowActionsProps) {
    const target = `order of ${order.client} on ${date}`;

    return (
        <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
                <Link href={edit(order.id)} aria-label={`Edit ${target}`}>
                    <Pencil aria-hidden="true" />
                    Edit
                </Link>
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                aria-label={`Delete ${target}`}
                className="text-destructive-foreground hover:text-destructive-foreground"
                onClick={() => onDelete(order)}
            >
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </div>
    );
}

/**
 * The orders list: a table from `lg` up (tablets get the cards), stacked cards
 * below. Every figure and the status come from the server; this component only
 * formats them.
 */
export function OrdersTable({ orders, onDelete }: OrdersTableProps) {
    const { locale } = useAppSettings();

    return (
        <>
            {/* >= lg: table */}
            <div className="hidden lg:block">
                <Table className="[&_td]:px-4 [&_th]:px-4">
                    <TableCaption className="sr-only">Orders</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Order date</TableHead>
                            <TableHead scope="col">Client</TableHead>
                            <TableHead scope="col">Products</TableHead>
                            <TableHead scope="col" className="text-right">
                                Ordered
                            </TableHead>
                            {STAGES.map(({ key, label }) => (
                                <TableHead
                                    key={key}
                                    scope="col"
                                    className="text-right"
                                >
                                    {label}
                                </TableHead>
                            ))}
                            <TableHead scope="col">Status</TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {orders.map((order) => {
                            const date = formatDate(order.ordered_on, locale);

                            return (
                                <TableRow key={order.id}>
                                    <TableCell className="align-top whitespace-nowrap text-muted-foreground">
                                        {date}
                                    </TableCell>
                                    <TableCell className="align-top">
                                        <p className="font-semibold">
                                            <ClientLink order={order} />
                                        </p>
                                        {order.note && (
                                            <p
                                                className="max-w-48 truncate text-sm text-muted-foreground"
                                                title={order.note}
                                            >
                                                {order.note}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell className="align-top whitespace-normal">
                                        <ProductLines order={order} />
                                    </TableCell>
                                    <TableCell className="text-right align-top font-bold tabular-nums">
                                        {formatCount(order.units, locale)}
                                    </TableCell>
                                    {STAGES.map(({ key }) => (
                                        <TableCell
                                            key={key}
                                            className="text-right align-top tabular-nums"
                                        >
                                            {formatCount(order[key], locale)}
                                        </TableCell>
                                    ))}
                                    <TableCell className="align-top">
                                        <ProgressStatus
                                            status={order.status}
                                            label={order.status_label}
                                        />
                                    </TableCell>
                                    <TableCell className="align-top">
                                        <IconActions
                                            order={order}
                                            date={date}
                                            onDelete={onDelete}
                                        />
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>

            {/* < lg: stacked cards */}
            <ul aria-label="Orders" className="lg:hidden">
                {orders.map((order) => {
                    const date = formatDate(order.ordered_on, locale);

                    return (
                        <li key={order.id} className="space-y-3 border-t p-4">
                            <div className="flex items-start justify-between gap-3">
                                <p className="font-semibold break-words">
                                    <ClientLink order={order} />
                                </p>
                                <ProgressStatus
                                    status={order.status}
                                    label={order.status_label}
                                    className="shrink-0"
                                />
                            </div>
                            <ProductLines order={order} />
                            <p className="text-sm tabular-nums">
                                {STAGES.map(
                                    ({ key, label }) =>
                                        `${label} ${formatCount(order[key], locale)}`,
                                ).join(' · ')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {date}
                            </p>
                            {order.note && (
                                <p
                                    className="line-clamp-2 text-sm text-muted-foreground"
                                    title={order.note}
                                >
                                    {order.note}
                                </p>
                            )}
                            <TextActions
                                order={order}
                                date={date}
                                onDelete={onDelete}
                            />
                        </li>
                    );
                })}
            </ul>
        </>
    );
}
