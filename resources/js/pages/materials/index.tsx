import { Head, Link, router } from '@inertiajs/react';
import { Boxes, Plus, SearchX } from 'lucide-react';
import { useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { MaterialsTable } from '@/components/materials/materials-table';
import { EmptyState } from '@/components/shared/empty-state';
import { FiltersBar } from '@/components/shared/filters-bar';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useReference } from '@/hooks/use-reference';
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { formatCount, formatMoney } from '@/lib/format';
import { categoryOptions } from '@/lib/reference';
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

/**
 * A filter, sort or page change reloads only what it changes. `summary` covers
 * every material, so filters don't touch it; `recent_history` is deferred and
 * has to be asked for again because it belongs to the rows of the new page.
 */
const LIST_PROPS = ['materials', 'filters', 'recent_history'];

const hrefs = {
    show: (material: Material) => MaterialController.show.url(material),
    edit: (material: Material) => MaterialController.edit.url(material),
};

/** "12 materials · inventory value €10,544.30", over every material, not just this page. */
function describeSummary(
    summary: MaterialsIndexProps['summary'],
    currency: string,
    locale: string,
): string {
    const count = formatCount(summary.materials_count, locale);
    const noun = summary.materials_count === 1 ? 'material' : 'materials';
    const value = formatMoney(summary.inventory_value, { currency, locale });

    return `${count} ${noun} · inventory value ${value}`;
}

export default function MaterialsIndex({
    materials,
    filters: serverFilters,
    summary,
    recent_history,
}: MaterialsIndexProps) {
    const { currency, locale } = useAppSettings();
    const reference = useReference();
    // The filter controls own their state (seeded from the server) so a slow
    // response can never overwrite what the user typed or picked meanwhile.
    const [filters, setFilters] = useState(serverFilters);
    const [sentKey, setSentKey] = useState(filtersKey(serverFilters));
    const [seenKey, setSeenKey] = useState(filtersKey(serverFilters));

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
            only: LIST_PROPS,
            preserveState: true,
            preserveScroll: true,
            replace: true,
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
                    description={describeSummary(summary, currency, locale)}
                    actions={
                        <Button asChild>
                            <Link
                                href={MaterialController.create()}
                                {...prefetchProps(CACHE_TAGS.materials)}
                            >
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
                        categories={categoryOptions(
                            reference.material_categories,
                            filters.category,
                        )}
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
                                <Link
                                    href={MaterialController.create()}
                                    {...prefetchProps(CACHE_TAGS.materials)}
                                >
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
                            recentHistory={recent_history}
                        />
                        <Pagination
                            meta={materials.meta}
                            only={['materials', 'recent_history']}
                        />
                    </>
                )}
            </div>
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
