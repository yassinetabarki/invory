import { Head, Link, router } from '@inertiajs/react';
import { Boxes, Plus, SearchX } from 'lucide-react';
import { useRef, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { MaterialsTable } from '@/components/materials/materials-table';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { EmptyState } from '@/components/shared/empty-state';
import { FiltersBar } from '@/components/shared/filters-bar';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import type {
    Material,
    MaterialFilters,
    MaterialsIndexProps,
} from '@/types/domain';

const DEFAULT_SORT: MaterialFilters['sort'] = 'name';

const NO_FILTERS: Pick<MaterialFilters, 'search' | 'category' | 'low_stock'> = {
    search: null,
    category: null,
    low_stock: false,
};

/**
 * Query string for a set of filters. Null, false and default values are
 * omitted, and `page` is never sent, so any filter change returns to page 1.
 */
function toQuery(filters: MaterialFilters): Record<string, string | number> {
    const query: Record<string, string | number> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.category) {
        query.category = filters.category;
    }

    if (filters.low_stock) {
        query.low_stock = 1;
    }

    if (filters.sort !== DEFAULT_SORT) {
        query.sort = filters.sort;
    }

    return query;
}

function filtersKey(filters: MaterialFilters): string {
    return JSON.stringify(toQuery(filters));
}

function hasActiveFilters(filters: MaterialFilters): boolean {
    return (
        filters.search !== null ||
        filters.category !== null ||
        filters.low_stock
    );
}

const hrefs = {
    show: (material: Material) => MaterialController.show.url(material),
    edit: (material: Material) => MaterialController.edit.url(material),
};

export default function MaterialsIndex({
    materials,
    filters: serverFilters,
    categories,
}: MaterialsIndexProps) {
    // The filter controls own their state (seeded from the server) so a slow
    // response can never overwrite what the user typed or picked meanwhile.
    const [filters, setFilters] = useState(serverFilters);
    const [sentKey, setSentKey] = useState(filtersKey(serverFilters));
    const [seenKey, setSeenKey] = useState(filtersKey(serverFilters));
    const [deleting, setDeleting] = useState<Material | null>(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    // The control that opened the dialog, so focus can return to it.
    const deleteTriggerRef = useRef<HTMLElement | null>(null);

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

    function applyFilters(next: MaterialFilters) {
        setFilters(next);
        setSentKey(filtersKey(next));

        router.get(MaterialController.index.url(), toQuery(next), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function confirmDelete() {
        if (!deleting) {
            return;
        }

        router.delete(MaterialController.destroy.url(deleting), {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => {
                setDeleteProcessing(false);
                setDeleting(null);
            },
        });
    }

    const filtered = hasActiveFilters(serverFilters);
    const isEmpty = materials.data.length === 0;

    return (
        <>
            <Head title="Materials" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Materials"
                    description="Primary materials in stock, with cost and stock status."
                    actions={
                        <Button asChild>
                            <Link href={MaterialController.create()}>
                                <Plus aria-hidden="true" />
                                Add material
                            </Link>
                        </Button>
                    }
                />

                {/* Hidden while the catalogue is empty and nothing is filtered. */}
                {(!isEmpty || filtered) && (
                    <FiltersBar
                        filters={filters}
                        categories={categories}
                        onChange={applyFilters}
                    />
                )}

                {isEmpty && !filtered && (
                    <EmptyState
                        icon={Boxes}
                        title="No materials yet"
                        description="Add the fabrics, trims and other primary materials you keep in stock."
                        action={
                            <Button asChild>
                                <Link href={MaterialController.create()}>
                                    <Plus aria-hidden="true" />
                                    Add your first material
                                </Link>
                            </Button>
                        }
                    />
                )}

                {isEmpty && filtered && (
                    <EmptyState
                        icon={SearchX}
                        title="No materials match your filters"
                        description="Try a different search, or clear the filters to see every material."
                        action={
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    applyFilters({ ...filters, ...NO_FILTERS })
                                }
                            >
                                Clear filters
                            </Button>
                        }
                    />
                )}

                {!isEmpty && (
                    <>
                        <MaterialsTable
                            materials={materials.data}
                            hrefs={hrefs}
                            onDelete={(material) => {
                                deleteTriggerRef.current =
                                    document.activeElement instanceof
                                    HTMLElement
                                        ? document.activeElement
                                        : null;
                                setDeleting(material);
                            }}
                        />
                        <Pagination meta={materials.meta} />
                    </>
                )}
            </div>

            <ConfirmDeleteDialog
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open && !deleteProcessing) {
                        setDeleting(null);
                    }
                }}
                title="Delete material?"
                description={
                    deleting && (
                        <p>
                            <strong>{deleting.name}</strong> (SKU {deleting.sku}
                            ) will be removed from your inventory. This cannot
                            be undone.
                        </p>
                    )
                }
                confirmLabel="Delete material"
                processing={deleteProcessing}
                returnFocusTo={deleteTriggerRef}
                onConfirm={confirmDelete}
            />
        </>
    );
}

MaterialsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Materials',
            href: MaterialController.index(),
        },
    ],
};
