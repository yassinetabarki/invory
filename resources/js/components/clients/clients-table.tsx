import { Link } from '@inertiajs/react';
import { SourceLabel } from '@/components/clients/source-label';
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
import { show } from '@/routes/clients';
import type { ClientSummary } from '@/types/domain';

/** "28 Oct 2026", or "No orders yet". */
function LastOrder({ client }: { client: ClientSummary }) {
    const { locale } = useAppSettings();

    return client.last_order_on
        ? formatDate(client.last_order_on, locale)
        : 'No orders yet';
}

/**
 * The clients (INV-4, AC-2): a table on a laptop (Client · Source · City ·
 * Phone · Open orders · Last order), one card per client on a phone. Long
 * names and cities are cut with "…" here, in full on the client page.
 */
export function ClientsTable({ clients }: { clients: ClientSummary[] }) {
    const { locale } = useAppSettings();

    return (
        <>
            <ul aria-label="Clients" className="divide-y md:hidden">
                {clients.map((client) => (
                    <li key={client.id} className="flex flex-col gap-1 p-4">
                        <Link
                            href={show(client.id)}
                            className="truncate font-semibold hover:underline focus-visible:underline"
                        >
                            {client.name}
                        </Link>
                        <SourceLabel client={client} className="self-start" />
                        <p className="truncate text-sm text-muted-foreground">
                            {client.city} ·{' '}
                            <span className="font-mono">{client.phone}</span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {client.last_order_on
                                ? `${formatCount(client.open_orders, locale)} open ${client.open_orders === 1 ? 'order' : 'orders'} · last ${formatDate(client.last_order_on, locale)}`
                                : 'No orders yet'}
                        </p>
                    </li>
                ))}
            </ul>

            <Table className="hidden table-fixed md:table">
                <TableCaption className="sr-only">Clients</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-[28%]">Client</TableHead>
                        <TableHead className="w-[18%]">Source</TableHead>
                        <TableHead className="w-[14%]">City</TableHead>
                        <TableHead className="w-[14%]">Phone</TableHead>
                        <TableHead className="w-[12%] text-right">
                            Open orders
                        </TableHead>
                        <TableHead className="w-[14%]">Last order</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {clients.map((client) => (
                        <TableRow key={client.id}>
                            <TableCell className="truncate">
                                <Link
                                    href={show(client.id)}
                                    className="font-semibold hover:underline focus-visible:underline"
                                >
                                    {client.name}
                                </Link>
                            </TableCell>
                            <TableCell className="truncate">
                                {client.source ? (
                                    <SourceLabel client={client} />
                                ) : (
                                    <span aria-label="No source">—</span>
                                )}
                            </TableCell>
                            <TableCell className="truncate">
                                {client.city}
                            </TableCell>
                            <TableCell className="font-mono">
                                {client.phone}
                            </TableCell>
                            <TableCell className="text-right font-mono tabular-nums">
                                {formatCount(client.open_orders, locale)}
                            </TableCell>
                            <TableCell>
                                <LastOrder client={client} />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </>
    );
}
