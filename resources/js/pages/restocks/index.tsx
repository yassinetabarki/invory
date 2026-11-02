import { Head, Link, router } from '@inertiajs/react';
import {
    PackagePlus,
    Plus,
    Search,
    SearchX,
    SlidersHorizontal,
} from 'lucide-react';
import { useEffect, useEffectEvent, useState } from 'react';
import { RestockProductPicker } from '@/components/restocks/restock-product-picker';
import { RestocksTable } from '@/components/restocks/restocks-table';
import { EmptyState } from '@/components/shared/empty-state';
import { SEARCH_DEBOUNCE_MS } from '@/components/shared/filters-bar';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { create, index } from '@/routes/restocks';
import type {
    RestockProductRef,
    RestockSummary,
    RestocksIndexProps,
} from '@/types/domain';

/** A search, filter or page change reloads only the list and its filters. */
const LIST_PROPS = ['restocks', 'filters', 'product_filter'];

/** The page address of the list: the filters, and the open restock when there is one. */
function addressQuery(
    search: string | null,
    product: number | null,
    restock: number | null = null,
) {
    return {
        ...(search === null ? {} : { search }),
        ...(product === null ? {} : { product }),
        ...(restock === null ? {} : { restock }),
    };
}

/**
 * Restocks (INV-3, AC-2): the finished units that were made or arrived, newest
 * first, with the search ("Search reference, note or product") and the Product
 * filter, both kept in the page address. A row opens the restock's panel,
 * which is kept in the address too (`?restock=`).
 */
export default function RestocksIndex({
    restocks,
    filters: serverFilters,
    product_filter: productFilter,
    has_restocks: hasRestocks,
}: RestocksIndexProps) {
    const [text, setText] = useState(serverFilters.search ?? '');
    const [emitted, setEmitted] = useState(serverFilters.search);
    const [seenSearch, setSeenSearch] = useState(serverFilters.search);
    const [product, setProduct] = useState<RestockProductRef | null>(
        productFilter,
    );
    const [seenProduct, setSeenProduct] = useState(productFilter);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const [filtersOpen, setFiltersOpen] = useState(false);

    // A search or product we did not ask for (back / forward button) is adopted.
    if (serverFilters.search !== seenSearch) {
        setSeenSearch(serverFilters.search);

        if (serverFilters.search !== emitted) {
            setText(serverFilters.search ?? '');
            setEmitted(serverFilters.search);
        }
    }

    if (productFilter?.id !== seenProduct?.id) {
        setSeenProduct(productFilter);
        setProduct(productFilter);
    }

    function visit(search: string | null, next: RestockProductRef | null) {
        // `page` is never sent, so a new search or product returns to page 1; the panel closes.
        router.get(index.url(), addressQuery(search, next?.id ?? null), {
            only: LIST_PROPS,
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

    function open(restock: RestockSummary) {
        router.get(
            index.url(),
            {
                ...addressQuery(
                    serverFilters.search,
                    serverFilters.product,
                    restock.id,
                ),
                ...(restocks.meta.current_page > 1
                    ? { page: restocks.meta.current_page }
                    : {}),
            },
            {
                only: ['restock', 'filters'],
                preserveState: true,
                preserveScroll: true,
            },
        );
    }

    const debouncedText = useDebouncedValue(text, SEARCH_DEBOUNCE_MS);

    const emitSearch = useEffectEvent((value: string) => {
        const search = value.trim() === '' ? null : value.trim();

        if (search !== emitted) {
            setEmitted(search);
            visit(search, product);
        }
    });

    useEffect(() => {
        emitSearch(debouncedText);
    }, [debouncedText]);

    function chooseProduct(next: RestockProductRef | null) {
        setProduct(next);
        visit(emitted, next);
    }

    function clearFilters() {
        setText('');
        setEmitted(null);
        setProduct(null);
        visit(null, null);
    }

    const newButton = (
        <Button asChild className="max-sm:h-11 max-sm:w-full">
            <Link href={create()}>
                <Plus aria-hidden="true" />
                New restock
            </Link>
        </Button>
    );

    let body;

    if (failed) {
        body = (
            <div
                role="alert"
                className="flex flex-col items-center gap-3 p-8 text-center text-sm"
            >
                <p>Couldn&apos;t load the restocks.</p>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => visit(emitted, product)}
                >
                    Try again
                </Button>
            </div>
        );
    } else if (loading) {
        body = (
            <div
                aria-busy="true"
                aria-label="Loading restocks"
                className="flex flex-col gap-3 p-4"
            >
                {Array.from({ length: 5 }, (_, row) => (
                    <Skeleton key={row} className="h-8 w-full" />
                ))}
            </div>
        );
    } else if (restocks.data.length === 0) {
        body = (
            <div className="p-4">
                {!hasRestocks ? (
                    <EmptyState
                        icon={PackagePlus}
                        title="No restocks yet"
                        description="Each time finished units are made or arrive, add them here. They go on the shelf in their colours and sizes."
                        action={newButton}
                    />
                ) : (
                    <EmptyState
                        icon={SearchX}
                        title={
                            serverFilters.search === null
                                ? `No restock of ${productFilter?.name ?? 'this product'} yet.`
                                : `No restock matches "${serverFilters.search}".`
                        }
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
        );
    } else {
        body = (
            <>
                <RestocksTable restocks={restocks.data} onOpen={open} />
                {restocks.meta.last_page > 1 && (
                    <div className="border-t px-4 py-3">
                        <Pagination meta={restocks.meta} only={['restocks']} />
                    </div>
                )}
            </>
        );
    }

    return (
        <>
            <Head title="Restocks" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Restocks"
                    description="Finished units that were made or arrived, added to the shelf in their colours and sizes."
                    actions={newButton}
                />

                <section
                    aria-label="Restocks list"
                    className="min-w-0 rounded-xl border bg-card text-card-foreground"
                >
                    {hasRestocks && (
                        <div
                            role="search"
                            aria-label="Search restocks"
                            className="flex gap-3 border-b p-4"
                        >
                            <div className="relative flex-1">
                                <Label
                                    htmlFor="restocks-search"
                                    className="sr-only"
                                >
                                    Search restocks
                                </Label>
                                <Search
                                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="restocks-search"
                                    type="search"
                                    value={text}
                                    placeholder="Search reference, note or product"
                                    autoComplete="off"
                                    className="h-11 pl-9 sm:h-9"
                                    onChange={(event) =>
                                        setText(event.target.value)
                                    }
                                />
                            </div>
                            <div className="hidden w-60 sm:block">
                                <span
                                    id="restocks-product-label"
                                    className="sr-only"
                                >
                                    Product
                                </span>
                                <RestockProductPicker
                                    mode="filter"
                                    id="restocks-product"
                                    labelledBy="restocks-product-label"
                                    value={product}
                                    onSelect={chooseProduct}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11 sm:hidden"
                                onClick={() => setFiltersOpen(true)}
                            >
                                <SlidersHorizontal aria-hidden="true" />
                                {product === null ? 'Filters' : 'Filters · 1'}
                            </Button>
                        </div>
                    )}

                    {body}
                </section>
            </div>

            <Sheet open={filtersOpen} onOpenChange={setFiltersOpen}>
                <SheetContent side="bottom">
                    <SheetHeader>
                        <SheetTitle>Filters</SheetTitle>
                        <SheetDescription className="sr-only">
                            Choose a product to see its restocks.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="flex flex-col gap-2 px-4 pb-6">
                        <span
                            id="restocks-product-sheet-label"
                            className="text-sm font-medium"
                        >
                            Product
                        </span>
                        <RestockProductPicker
                            mode="filter"
                            id="restocks-product-sheet"
                            labelledBy="restocks-product-sheet-label"
                            value={product}
                            onSelect={(next) => {
                                chooseProduct(next);
                                setFiltersOpen(false);
                            }}
                        />
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

RestocksIndex.layout = {
    breadcrumbs: [{ title: 'Restocks', href: index() }],
};
