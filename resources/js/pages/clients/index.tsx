import { Head, router } from '@inertiajs/react';
import { Plus, Search, SearchX, SlidersHorizontal, Users } from 'lucide-react';
import { useEffect, useEffectEvent, useState } from 'react';
import { AddClientDialog } from '@/components/clients/add-client-dialog';
import { ClientsTable } from '@/components/clients/clients-table';
import { EmptyState } from '@/components/shared/empty-state';
import { SEARCH_DEBOUNCE_MS } from '@/components/shared/filters-bar';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { index } from '@/routes/clients';
import type { ClientSource, ClientsIndexProps, Option } from '@/types/domain';

/** A search, filter or page change reloads only the list and its filters. */
const LIST_PROPS = ['clients', 'filters'];

/** The value of "All sources" in the Source list (a Select item can't be empty). */
const ALL = 'all';

type SourceSelectProps = {
    id: string;
    value: ClientSource | null;
    sources: Option<ClientSource>[];
    onChange: (source: ClientSource | null) => void;
};

function SourceSelect({ id, value, sources, onChange }: SourceSelectProps) {
    return (
        <Select
            value={value ?? ALL}
            onValueChange={(next) =>
                onChange(next === ALL ? null : (next as ClientSource))
            }
        >
            <SelectTrigger id={id} className="h-11 w-full sm:h-9">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>All sources</SelectItem>
                {sources.map((source) => (
                    <SelectItem key={source.value} value={source.value}>
                        {source.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

/**
 * Clients (INV-4, AC-2): the people and shops who order from the workshop, A
 * → Z, with the search ("Search name, phone or city") and the Source filter,
 * both kept in the page address. Add client opens its window here.
 */
export default function ClientsIndex({
    clients,
    filters: serverFilters,
    client_sources: sources,
    cities,
    has_clients: hasClients,
}: ClientsIndexProps) {
    const [text, setText] = useState(serverFilters.search ?? '');
    const [emitted, setEmitted] = useState(serverFilters.search);
    const [seenSearch, setSeenSearch] = useState(serverFilters.search);
    const [source, setSource] = useState(serverFilters.source);
    const [seenSource, setSeenSource] = useState(serverFilters.source);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const [adding, setAdding] = useState(false);
    const [filtersOpen, setFiltersOpen] = useState(false);

    // A search or source we did not ask for (back / forward button) is adopted.
    if (serverFilters.search !== seenSearch) {
        setSeenSearch(serverFilters.search);

        if (serverFilters.search !== emitted) {
            setText(serverFilters.search ?? '');
            setEmitted(serverFilters.search);
        }
    }

    if (serverFilters.source !== seenSource) {
        setSeenSource(serverFilters.source);
        setSource(serverFilters.source);
    }

    function visit(search: string | null, nextSource: ClientSource | null) {
        // `page` is never sent, so a new search or source returns to page 1.
        const query = {
            ...(search === null ? {} : { search }),
            ...(nextSource === null ? {} : { source: nextSource }),
        };

        router.get(index.url(), query, {
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

    const debouncedText = useDebouncedValue(text, SEARCH_DEBOUNCE_MS);

    const emitSearch = useEffectEvent((value: string) => {
        const search = value.trim() === '' ? null : value.trim();

        if (search !== emitted) {
            setEmitted(search);
            visit(search, source);
        }
    });

    useEffect(() => {
        emitSearch(debouncedText);
    }, [debouncedText]);

    function chooseSource(next: ClientSource | null) {
        setSource(next);
        visit(emitted, next);
    }

    function clearFilters() {
        setText('');
        setEmitted(null);
        setSource(null);
        visit(null, null);
    }

    const sourceLabel = sources.find(
        (option) => option.value === serverFilters.source,
    )?.label;
    const addButton = (
        <Button
            type="button"
            className="max-sm:h-11 max-sm:w-full"
            onClick={() => setAdding(true)}
        >
            <Plus aria-hidden="true" />
            Add client
        </Button>
    );

    let body;

    if (failed) {
        body = (
            <div
                role="alert"
                className="flex flex-col items-center gap-3 p-8 text-center text-sm"
            >
                <p>Couldn&apos;t load the clients.</p>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => visit(emitted, source)}
                >
                    Try again
                </Button>
            </div>
        );
    } else if (loading) {
        body = (
            <div
                aria-busy="true"
                aria-label="Loading clients"
                className="flex flex-col gap-3 p-4"
            >
                {Array.from({ length: 5 }, (_, row) => (
                    <Skeleton key={row} className="h-8 w-full" />
                ))}
            </div>
        );
    } else if (clients.data.length === 0) {
        body = (
            <div className="p-4">
                {!hasClients ? (
                    <EmptyState
                        icon={Users}
                        title="No clients yet"
                        description="Your clients appear here when you record their first order, or add one now."
                        action={addButton}
                    />
                ) : serverFilters.search === null && sourceLabel ? (
                    <EmptyState
                        icon={SearchX}
                        title={`No client came from ${sourceLabel} yet.`}
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
                ) : (
                    <EmptyState
                        icon={SearchX}
                        title={`No client matches "${serverFilters.search ?? ''}".`}
                        description="Check the spelling, or search by phone or city."
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
                <ClientsTable clients={clients.data} />
                {clients.meta.last_page > 1 && (
                    <div className="border-t px-4 py-3">
                        <Pagination meta={clients.meta} only={['clients']} />
                    </div>
                )}
            </>
        );
    }

    return (
        <>
            <Head title="Clients" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Clients"
                    description="The people and shops who order from you, and where they found you. Each order belongs to one client."
                    actions={addButton}
                />

                <section
                    aria-label="Clients list"
                    className="min-w-0 rounded-xl border bg-card text-card-foreground"
                >
                    {hasClients && (
                        <div
                            role="search"
                            aria-label="Search clients"
                            className="flex gap-3 border-b p-4"
                        >
                            <div className="relative flex-1">
                                <Label
                                    htmlFor="clients-search"
                                    className="sr-only"
                                >
                                    Search clients
                                </Label>
                                <Search
                                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="clients-search"
                                    type="search"
                                    value={text}
                                    placeholder="Search name, phone or city"
                                    autoComplete="off"
                                    className="h-11 pl-9 sm:h-9"
                                    onChange={(event) =>
                                        setText(event.target.value)
                                    }
                                />
                            </div>
                            <div className="hidden w-52 sm:block">
                                <Label
                                    htmlFor="clients-source"
                                    className="sr-only"
                                >
                                    Source
                                </Label>
                                <SourceSelect
                                    id="clients-source"
                                    value={source}
                                    sources={sources}
                                    onChange={chooseSource}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11 sm:hidden"
                                onClick={() => setFiltersOpen(true)}
                            >
                                <SlidersHorizontal aria-hidden="true" />
                                {source === null ? 'Filters' : 'Filters · 1'}
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
                            Choose where the clients found you.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="flex flex-col gap-2 px-4 pb-6">
                        <Label htmlFor="clients-source-sheet">Source</Label>
                        <SourceSelect
                            id="clients-source-sheet"
                            value={source}
                            sources={sources}
                            onChange={(next) => {
                                chooseSource(next);
                                setFiltersOpen(false);
                            }}
                        />
                    </div>
                </SheetContent>
            </Sheet>

            <AddClientDialog
                open={adding}
                onOpenChange={setAdding}
                cities={cities}
                sources={sources}
            />
        </>
    );
}

ClientsIndex.layout = {
    breadcrumbs: [{ title: 'Clients', href: index() }],
};
