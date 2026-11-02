import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, SearchX } from 'lucide-react';
import { useState } from 'react';
import { ClientHeader } from '@/components/clients/client-header';
import { OrdersTable } from '@/components/orders/orders-table';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { index, show } from '@/routes/clients';
import { create } from '@/routes/orders';
import type { ClientShowProps, OrderStatusFilter } from '@/types/domain';

/**
 * A client's page (INV-4, AC-4): who she is, her figures, and her orders with
 * the Status list ("All orders" here), newest first. Read-only: a client is
 * never edited.
 */
export default function ClientsShow({
    client,
    orders,
    filters,
    statuses,
}: ClientShowProps) {
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);

    function chooseStatus(status: OrderStatusFilter) {
        router.get(show.url(client.id), status === 'all' ? {} : { status }, {
            only: ['orders', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                setLoading(true);
                setFailed(false);
            },
            onFinish: () => setLoading(false),
            onHttpException: () => {
                setFailed(true);

                return false;
            },
            onNetworkError: () => {
                setFailed(true);

                return false;
            },
        });
    }

    // "Amira Ben Salah has no shipped orders." (the filter "All orders" always shows her orders, if any).
    const filtered = filters.status === 'shipped' ? 'shipped' : 'open';

    let body;

    if (failed) {
        body = (
            <div
                role="alert"
                className="flex flex-col items-center gap-3 p-8 text-center text-sm"
            >
                <p>Couldn&apos;t load the orders of {client.name}.</p>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => chooseStatus(filters.status)}
                >
                    Try again
                </Button>
            </div>
        );
    } else if (loading) {
        body = (
            <div
                aria-busy="true"
                aria-label="Loading orders"
                className="flex flex-col gap-3 p-4"
            >
                {Array.from({ length: 3 }, (_, row) => (
                    <Skeleton key={row} className="h-8 w-full" />
                ))}
            </div>
        );
    } else if (orders.data.length === 0) {
        body = (
            <div className="p-4">
                {client.orders === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="No orders yet"
                        description={`Record an order for ${client.name} to follow it here.`}
                        action={
                            <Button asChild>
                                <Link
                                    href={create.url({
                                        query: { client: client.id },
                                    })}
                                >
                                    New order
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <EmptyState
                        icon={SearchX}
                        title={`${client.name} has no ${filtered} orders.`}
                        action={
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => chooseStatus('all')}
                            >
                                Show all orders
                            </Button>
                        }
                    />
                )}
            </div>
        );
    } else {
        body = (
            <>
                <OrdersTable orders={orders.data} withoutClient />
                {orders.meta.last_page > 1 && (
                    <div className="border-t px-4 py-3">
                        <Pagination meta={orders.meta} only={['orders']} />
                    </div>
                )}
            </>
        );
    }

    return (
        <>
            <Head title={client.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <ClientHeader client={client} />

                <section
                    aria-labelledby="client-orders"
                    className="min-w-0 rounded-xl border bg-card text-card-foreground"
                >
                    <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 id="client-orders" className="text-base font-bold">
                            Orders
                        </h2>
                        <div className="sm:w-48">
                            <Label
                                htmlFor="client-orders-status"
                                className="sr-only"
                            >
                                Status
                            </Label>
                            <Select
                                value={filters.status}
                                onValueChange={(value) =>
                                    chooseStatus(value as OrderStatusFilter)
                                }
                            >
                                <SelectTrigger
                                    id="client-orders-status"
                                    className="h-11 w-full sm:h-9"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    {body}
                </section>
            </div>
        </>
    );
}

ClientsShow.layout = (props: ClientShowProps) => ({
    breadcrumbs: [
        { title: 'Clients', href: index() },
        { title: props.client.name, href: show(props.client.id) },
    ],
});
