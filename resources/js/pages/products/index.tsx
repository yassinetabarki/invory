import { Head, Link, router } from '@inertiajs/react';
import { Plus, SearchX, Shirt } from 'lucide-react';
import { useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { ProductsFilters } from '@/components/products/products-filters';
import { ProductsTable } from '@/components/products/products-table';
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import type {
    MaterialRef,
    ProductFilters,
    ProductListItem,
    ProductsIndexProps,
} from '@/types/domain';

const NO_FILTERS: ProductFilters = {
    search: null,
    category: null,
    status: null,
    cant_produce: false,
};

/**
 * Query string for a set of filters. Null, false and default values are
 * omitted, and `page` is never sent, so any filter change returns to page 1.
 */
function toQuery(filters: ProductFilters): Record<string, string | number> {
    const query: Record<string, string | number> = {};

    if (filters.search) {
        query.search = filters.search;
    }

    if (filters.category) {
        query.category = filters.category;
    }

    if (filters.status) {
        query.status = filters.status;
    }

    if (filters.cant_produce) {
        query.cant_produce = 1;
    }

    return query;
}

function filtersKey(filters: ProductFilters): string {
    return JSON.stringify(toQuery(filters));
}

function hasActiveFilters(filters: ProductFilters): boolean {
    return (
        filters.search !== null ||
        filters.category !== null ||
        filters.status !== null ||
        filters.cant_produce
    );
}

/** A filter, sort or page change reloads the list and its filters only. */
const LIST_PROPS = ['products', 'filters'];

const hrefs = {
    show: (product: ProductListItem) => ProductController.show.url(product.id),
    edit: (product: ProductListItem) => ProductController.edit.url(product.id),
    material: (material: MaterialRef) =>
        MaterialController.show.url(material.id),
};

export default function ProductsIndex({
    products,
    filters: serverFilters,
    categories,
    statuses,
}: ProductsIndexProps) {
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

    function applyFilters(next: ProductFilters) {
        setFilters(next);
        setSentKey(filtersKey(next));

        router.get(ProductController.index.url(), toQuery(next), {
            only: LIST_PROPS,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const filtered = hasActiveFilters(serverFilters);
    const isEmpty = products.data.length === 0;

    return (
        <>
            <Head title="Products" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Products"
                    description="Final items you make. Cost and capacity update from current material stock."
                    actions={
                        <Button asChild>
                            <Link
                                href={ProductController.create()}
                                {...prefetchProps(CACHE_TAGS.products)}
                            >
                                <Plus aria-hidden="true" />
                                Add product
                            </Link>
                        </Button>
                    }
                />

                {/* Hidden while there are no products and nothing is filtered. */}
                {(!isEmpty || filtered) && (
                    <ProductsFilters
                        filters={filters}
                        categories={categories}
                        statuses={statuses}
                        onChange={applyFilters}
                    />
                )}

                {isEmpty && !filtered && (
                    <EmptyState
                        icon={Shirt}
                        title="No products yet"
                        description="Add the items you make and the materials that go into one unit. Cost and capacity are worked out for you."
                        action={
                            <Button asChild>
                                <Link
                                    href={ProductController.create()}
                                    {...prefetchProps(CACHE_TAGS.products)}
                                >
                                    <Plus aria-hidden="true" />
                                    Add your first product
                                </Link>
                            </Button>
                        }
                    />
                )}

                {isEmpty && filtered && (
                    <EmptyState
                        icon={SearchX}
                        title="No products match your filters"
                        description="Try a different search, or clear the filters to see every product."
                        action={
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => applyFilters(NO_FILTERS)}
                            >
                                Clear filters
                            </Button>
                        }
                    />
                )}

                {!isEmpty && (
                    <>
                        <ProductsTable products={products.data} hrefs={hrefs} />
                        <Pagination meta={products.meta} only={['products']} />
                    </>
                )}
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: ProductController.index(),
        },
    ],
};
