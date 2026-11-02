import { Link } from '@inertiajs/react';
import { MapPin, Phone, Plus } from 'lucide-react';
import { SourceLabel } from '@/components/clients/source-label';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import { create } from '@/routes/orders';
import type { ClientSummary } from '@/types/domain';

/** "tel:22345678": the digits of the phone, with its + when abroad. */
export function telHref(phone: string): string {
    return `tel:${phone.replace(/[^0-9+]/g, '')}`;
}

/**
 * The top of a client page (INV-4, AC-4): her name, "[Instagram] · Sousse ·
 * 22 345 678" (the phone a call link), New order, and three figures: Open
 * orders, Orders and Last order. On a phone: a full-width Call button and New
 * order, the figures two to a row.
 */
export function ClientHeader({ client }: { client: ClientSummary }) {
    const { locale } = useAppSettings();
    const newOrder = create.url({ query: { client: client.id } });
    const figures = [
        {
            label: 'Open orders',
            value: formatCount(client.open_orders, locale),
        },
        { label: 'Orders', value: formatCount(client.orders, locale) },
        {
            label: 'Last order',
            value: client.last_order_on
                ? formatDate(client.last_order_on, locale)
                : 'No orders yet',
        },
    ];

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 flex-col gap-2">
                    <h1 className="text-2xl font-bold break-words">
                        {client.name}
                    </h1>
                    <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground">
                        {client.source && (
                            <>
                                <SourceLabel client={client} />
                                <span aria-hidden="true">·</span>
                            </>
                        )}
                        <span className="inline-flex items-center gap-1">
                            <MapPin className="size-3.5" aria-hidden="true" />
                            {client.city}
                        </span>
                        <span aria-hidden="true" className="max-sm:hidden">
                            ·
                        </span>
                        <a
                            href={telHref(client.phone)}
                            aria-label={`Call ${client.name}, ${client.phone}`}
                            className="inline-flex items-center gap-1 font-mono hover:underline focus-visible:underline max-sm:hidden"
                        >
                            <Phone className="size-3.5" aria-hidden="true" />
                            {client.phone}
                        </a>
                    </p>
                </div>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 sm:hidden"
                    >
                        <a href={telHref(client.phone)}>
                            <Phone aria-hidden="true" />
                            Call {client.phone}
                        </a>
                    </Button>
                    <Button asChild className="max-sm:h-11">
                        <Link
                            href={newOrder}
                            aria-label={`New order for ${client.name}`}
                        >
                            <Plus aria-hidden="true" />
                            New order
                        </Link>
                    </Button>
                </div>
            </div>

            <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {figures.map((figure) => (
                    <div
                        key={figure.label}
                        className="rounded-xl border bg-card p-4 text-card-foreground"
                    >
                        <dt className="text-sm text-muted-foreground">
                            {figure.label}
                        </dt>
                        <dd className="mt-1 text-xl font-semibold tabular-nums">
                            {figure.value}
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
