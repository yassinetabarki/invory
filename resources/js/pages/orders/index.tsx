import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Plus, Search, SearchX } from 'lucide-react';
import { useEffect, useEffectEvent, useRef, useState } from 'react';
import { DeleteOrderDialog } from '@/components/orders/delete-order-dialog';
import { OrdersTable } from '@/components/orders/orders-table';
import { EmptyState } from '@/components/shared/empty-state';
import { SEARCH_DEBOUNCE_MS } from '@/components/shared/filters-bar';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { create, destroy, index } from '@/routes/orders';
import type { Order, OrdersIndexProps } from '@/types/domain';

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
}: OrdersIndexProps) {
    // The search box owns its text (seeded from the server) so a slow response
    // never overwrites what the user typed meanwhile.
    const [text, setText] = useState(serverFilters.search ?? '');
    // The last search we asked for: its echo from the server is not adopted.
    const [emitted, setEmitted] = useState(serverFilters.search);
    const [seenSearch, setSeenSearch] = useState(serverFilters.search);

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

    function visit(search: string | null) {
        // `page` is never sent, so a new search returns to page 1.
        router.get(index.url(), search === null ? {} : { search }, {
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
            visit(search);
        }
    });

    useEffect(() => {
        emitSearch(debouncedText);
    }, [debouncedText]);

    function clearSearch() {
        setText('');
        setEmitted(null);
        visit(null);
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

    const searching = serverFilters.search !== null;
    const isEmpty = orders.data.length === 0;
    // Nothing to search before the first order is recorded.
    const showSearch = !isEmpty || searching || text.trim() !== '';

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Orders"
                    description="Every order your clients place with you."
                    actions={<NewOrderButton />}
                />

                <section
                    aria-label="Orders list"
                    className="min-w-0 rounded-xl border bg-card text-card-foreground"
                >
                    {showSearch && (
                        <div
                            role="search"
                            aria-label="Search orders"
                            className="border-b p-4"
                        >
                            <div className="relative">
                                <Label
                                    htmlFor="orders-search"
                                    className="sr-only"
                                >
                                    Search orders
                                </Label>
                                <Search
                                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="orders-search"
                                    type="search"
                                    value={text}
                                    placeholder="Search client, product or note"
                                    autoComplete="off"
                                    className="pl-9"
                                    onChange={(event) =>
                                        setText(event.target.value)
                                    }
                                />
                            </div>
                        </div>
                    )}

                    {isEmpty ? (
                        <div className="p-4">
                            {searching ? (
                                <EmptyState
                                    icon={SearchX}
                                    title="No orders match your search"
                                    description="Try a different client, product or note, or clear the search to see every order."
                                    action={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={clearSearch}
                                        >
                                            Clear search
                                        </Button>
                                    }
                                />
                            ) : (
                                <EmptyState
                                    icon={ClipboardList}
                                    title="No orders yet"
                                    description="Record a client's order to keep track of what you've promised to make."
                                    action={<NewOrderButton />}
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
