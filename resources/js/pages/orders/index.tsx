import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, PackageCheck, Plus, SearchX } from 'lucide-react';
import { useEffect, useEffectEvent, useRef, useState } from 'react';
import { DeleteOrderDialog } from '@/components/orders/delete-order-dialog';
import { OpenOrderTotals } from '@/components/orders/open-order-totals';
import { OrdersFilters } from '@/components/orders/orders-filters';
import { OrdersTable } from '@/components/orders/orders-table';
import { EmptyState } from '@/components/shared/empty-state';
import { SEARCH_DEBOUNCE_MS } from '@/components/shared/filters-bar';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { create, destroy, index } from '@/routes/orders';
import type {
    Order,
    OrderStatusFilter,
    OrdersIndexProps,
} from '@/types/domain';

/** A search or page change reloads only the list and its filters. */
const LIST_PROPS = ['orders', 'filters'];

function NewOrderButton() {
    return (
        <Button asChild>
            <Link href={create()}>
                <Plus aria-hidden="true" />
                New order
            </Link>
        </Button>
    );
}

export default function OrdersIndex({
    orders,
    filters: serverFilters,
    statuses,
    totals,
    has_orders: hasOrders,
}: OrdersIndexProps) {
    // The search box owns its text (seeded from the server) so a slow response
    // never overwrites what the user typed meanwhile.
    const [text, setText] = useState(serverFilters.search ?? '');
    // The last search we asked for: its echo from the server is not adopted.
    const [emitted, setEmitted] = useState(serverFilters.search);
    const [seenSearch, setSeenSearch] = useState(serverFilters.search);
    const [status, setStatus] = useState(serverFilters.status);
    const [seenStatus, setSeenStatus] = useState(serverFilters.status);

    const [deleting, setDeleting] = useState<Order | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    // The control that opened the dialog, so focus can return to it.
    const deleteTriggerRef = useRef<HTMLElement | null>(null);

    // A search we did not ask for (back / forward button) is adopted.
    if (serverFilters.search !== seenSearch) {
        setSeenSearch(serverFilters.search);

        if (serverFilters.search !== emitted) {
            setText(serverFilters.search ?? '');
            setEmitted(serverFilters.search);
        }
    }

    // A status we did not ask for (back / forward button) is adopted.
    if (serverFilters.status !== seenStatus) {
        setSeenStatus(serverFilters.status);
        setStatus(serverFilters.status);
    }

    function visit(search: string | null, nextStatus: OrderStatusFilter) {
        // `page` is never sent, so a new search or status returns to page 1;
        // `status` is left out while it is the default, "open".
        const query = {
            ...(search === null ? {} : { search }),
            ...(nextStatus === 'open' ? {} : { status: nextStatus }),
        };

        router.get(index.url(), query, {
            only: LIST_PROPS,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const debouncedText = useDebouncedValue(text, SEARCH_DEBOUNCE_MS);

    const emitSearch = useEffectEvent((value: string) => {
        const search = value.trim() === '' ? null : value.trim();

        if (search !== emitted) {
            setEmitted(search);
            visit(search, status);
        }
    });

    useEffect(() => {
        emitSearch(debouncedText);
    }, [debouncedText]);

    function chooseStatus(next: OrderStatusFilter) {
        setStatus(next);
        visit(emitted, next);
    }

    function clearFilters() {
        setText('');
        setEmitted(null);
        setStatus('open');
        visit(null, 'open');
    }

    function askToDelete(order: Order) {
        deleteTriggerRef.current =
            document.activeElement instanceof HTMLElement &&
            document.activeElement !== document.body
                ? document.activeElement
                : null;
        setDeleting(order);
    }

    function confirmDelete() {
        if (!deleting) {
            return;
        }

        router.delete(destroy.url(deleting.id), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(null);
            },
        });
    }

    const isEmpty = orders.data.length === 0;
    const everyOrderShipped =
        serverFilters.search === null && serverFilters.status === 'open';

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Orders"
                    description="Every order your clients place with you."
                    actions={<NewOrderButton />}
                />

                <OpenOrderTotals totals={totals} />

                <section
                    aria-label="Orders list"
                    className="min-w-0 rounded-xl border bg-card text-card-foreground"
                >
                    {/* Nothing to search or filter before the first order. */}
                    {hasOrders && (
                        <OrdersFilters
                            search={text}
                            status={status}
                            statuses={statuses}
                            onSearchChange={setText}
                            onStatusChange={chooseStatus}
                        />
                    )}

                    {isEmpty ? (
                        <div className="p-4">
                            {!hasOrders ? (
                                <EmptyState
                                    icon={ClipboardList}
                                    title="No orders yet"
                                    description="Record a client's order to keep track of what you've promised to make."
                                    action={<NewOrderButton />}
                                />
                            ) : everyOrderShipped ? (
                                <EmptyState
                                    icon={PackageCheck}
                                    title="No open orders"
                                    description="Every order has shipped."
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
                            ) : (
                                <EmptyState
                                    icon={SearchX}
                                    title="No orders match your filters"
                                    description="Try a different client, product or note, or clear the filters to see your open orders."
                                    action={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={clearFilters}
                                        >
                                            Clear filters
                                        </Button>
                                    }
                                />
                            )}
                        </div>
                    ) : (
                        <>
                            <OrdersTable
                                orders={orders.data}
                                onDelete={askToDelete}
                            />
                            {orders.meta.last_page > 1 && (
                                <div className="border-t px-4 py-3">
                                    <Pagination
                                        meta={orders.meta}
                                        only={['orders']}
                                    />
                                </div>
                            )}
                        </>
                    )}
                </section>
            </div>

            <DeleteOrderDialog
                order={deleting}
                processing={deleteProcessing}
                returnFocusTo={deleteTriggerRef}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
            />
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: index(),
        },
    ],
};
