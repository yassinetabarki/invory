import { Head, router } from '@inertiajs/react';
import { Plus, ReceiptText, SearchX } from 'lucide-react';
import { useRef, useState } from 'react';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { DeleteSaleDialog } from '@/components/sales/delete-sale-dialog';
import { DEFAULT_PERIOD } from '@/components/sales/period-select';
import { SalePanel } from '@/components/sales/sale-panel';
import { SalesFilterBar } from '@/components/sales/sales-filter-bar';
import { SalesTable } from '@/components/sales/sales-table';
import { SalesTotals } from '@/components/sales/sales-totals';
import { useIsWideScreen } from '@/components/sales/use-wide-screen';
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Sale, SalesFilters, SalesIndexProps } from '@/types/domain';

const NO_FILTERS: SalesFilters = {
    search: null,
    product_id: null,
    period: DEFAULT_PERIOD,
};

/**
 * Query string for a set of filters. Null and default values are omitted, and
 * `page` is never sent, so any filter change returns to page 1.
 */
function toQuery(filters: SalesFilters): Record<string, string | number> {
    const query: Record<string, string | number> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.product_id !== null) {
        query.product_id = filters.product_id;
    }

    if (filters.period !== DEFAULT_PERIOD) {
        query.period = filters.period;
    }

    return query;
}

function filtersKey(filters: SalesFilters): string {
    return JSON.stringify(toQuery(filters));
}

function hasActiveFilters(filters: SalesFilters): boolean {
    return filtersKey(filters) !== filtersKey(NO_FILTERS);
}

/**
 * A filter, sort or page change reloads the log, the filters and the totals
 * (which follow the filters). `record` is not asked for: the "Record a sale"
 * panel keeps its state, as the form someone is filling in must not close.
 */
const LIST_PROPS = ['sales', 'filters', 'totals'];

/** What the form panel is doing: recording a sale or editing one. */
type Panel =
    | { kind: 'create'; productId: number | null }
    | { kind: 'edit'; sale: Sale };

/** The control that has focus now, so it can get focus back (not `<body>`). */
function activeElement(): HTMLElement | null {
    return document.activeElement instanceof HTMLElement &&
        document.activeElement !== document.body
        ? document.activeElement
        : null;
}

export default function SalesIndex({
    sales,
    filters: serverFilters,
    totals,
    periods,
    products,
    record,
}: SalesIndexProps) {
    // The filter controls own their state (seeded from the server) so a slow
    // response can never overwrite what the user typed or picked meanwhile.
    const [filters, setFilters] = useState(serverFilters);
    const [sentKey, setSentKey] = useState(filtersKey(serverFilters));
    const [seenKey, setSeenKey] = useState(filtersKey(serverFilters));

    const [panel, setPanel] = useState<Panel | null>(
        record === null
            ? null
            : { kind: 'create', productId: record.product_id },
    );
    const [seenRecord, setSeenRecord] = useState(record);

    const [deleting, setDeleting] = useState<Sale | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    // The controls that opened the form / the dialog, so focus can return to them.
    const panelOpenerRef = useRef<HTMLElement | null>(null);
    const deleteTriggerRef = useRef<HTMLElement | null>(null);
    const wide = useIsWideScreen();

    // Server filters we did not ask for (back / forward button) are adopted;
    // the echo of our own request is not.
    const serverKey = filtersKey(serverFilters);

    if (serverKey !== seenKey) {
        setSeenKey(serverKey);

        if (serverKey !== sentKey) {
            setFilters(serverFilters);
            setSentKey(serverKey);
        }
    }

    // A visit that asks to record a sale (`?record=1`) opens the form, also
    // when this page was already showing. Dropping `record` from the URL (a
    // filter change) must not close a form somebody is filling in.
    if (record !== seenRecord) {
        setSeenRecord(record);

        if (record !== null && record.product_id !== seenRecord?.product_id) {
            setPanel({ kind: 'create', productId: record.product_id });
        }
    }

    function applyFilters(next: SalesFilters) {
        setFilters(next);
        setSentKey(filtersKey(next));

        router.get(SaleController.index.url(), toQuery(next), {
            only: LIST_PROPS,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function openCreate() {
        panelOpenerRef.current = activeElement();
        setPanel((current) =>
            current?.kind === 'create'
                ? current
                : { kind: 'create', productId: null },
        );
    }

    function openEdit(sale: Sale) {
        panelOpenerRef.current = activeElement();
        setPanel({ kind: 'edit', sale });
    }

    function askToDelete(sale: Sale) {
        deleteTriggerRef.current = activeElement();
        setDeleting(sale);
    }

    function confirmDelete() {
        if (!deleting) {
            return;
        }

        const deleted = deleting;

        router.delete(SaleController.destroy.url(deleted.id), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            // A form open on the sale that just went has nothing left to save.
            onSuccess: () =>
                setPanel((current) =>
                    current?.kind === 'edit' && current.sale.id === deleted.id
                        ? null
                        : current,
                ),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(null);
            },
        });
    }

    const filtered = hasActiveFilters(serverFilters);
    const isEmpty = sales.data.length === 0;
    const sideBySide = wide && panel !== null;

    return (
        <>
            <Head title="Sales" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Sales"
                    description="Every sale you record feeds the dashboard totals and the top sellers."
                    actions={
                        <Button type="button" onClick={openCreate}>
                            <Plus aria-hidden="true" />
                            Record sale
                        </Button>
                    }
                />

                <SalesTotals totals={totals} />

                <div
                    className={cn(
                        'grid items-start gap-5',
                        sideBySide && 'grid-cols-[minmax(0,1fr)_21rem]',
                    )}
                >
                    <section
                        aria-label="Sales list"
                        className="min-w-0 rounded-xl border bg-card text-card-foreground"
                    >
                        <SalesFilterBar
                            filters={filters}
                            periods={periods}
                            products={products}
                            onChange={applyFilters}
                        />

                        {isEmpty ? (
                            <div className="p-4">
                                {filtered ? (
                                    <EmptyState
                                        icon={SearchX}
                                        title="No sales match your filters"
                                        description="Try a different search, period or product, or clear the filters to see every sale."
                                        action={
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    applyFilters(NO_FILTERS)
                                                }
                                            >
                                                Clear filters
                                            </Button>
                                        }
                                    />
                                ) : (
                                    <EmptyState
                                        icon={ReceiptText}
                                        title="No sales yet"
                                        description="Record a sale to see it here and in the dashboard totals."
                                        action={
                                            <Button
                                                type="button"
                                                onClick={openCreate}
                                            >
                                                <Plus aria-hidden="true" />
                                                Record sale
                                            </Button>
                                        }
                                    />
                                )}
                            </div>
                        ) : (
                            <>
                                <SalesTable
                                    sales={sales.data}
                                    onEdit={openEdit}
                                    onDelete={askToDelete}
                                />
                                {sales.meta.last_page > 1 && (
                                    <div className="border-t px-4 py-3">
                                        <Pagination
                                            meta={sales.meta}
                                            only={['sales']}
                                        />
                                    </div>
                                )}
                            </>
                        )}
                    </section>

                    {panel !== null && (
                        <SalePanel
                            variant={wide ? 'aside' : 'sheet'}
                            sale={panel.kind === 'edit' ? panel.sale : null}
                            products={products}
                            initialProductId={
                                panel.kind === 'create' ? panel.productId : null
                            }
                            returnFocusTo={panelOpenerRef}
                            onClose={() => setPanel(null)}
                        />
                    )}
                </div>
            </div>

            <DeleteSaleDialog
                sale={deleting}
                processing={deleteProcessing}
                returnFocusTo={deleteTriggerRef}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
            />
        </>
    );
}

SalesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Sales',
            href: SaleController.index(),
        },
    ],
};
