import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Factory, Plus, SearchX, Settings } from 'lucide-react';
import { useState } from 'react';
import type { BatchAction } from '@/components/production/phase-view';
import { PhaseView } from '@/components/production/phase-view';
import { PhaseTiles } from '@/components/production/phase-tiles';
import { MoveBatchDialog } from '@/components/production/move-batch-dialog';
import type { BatchWindow } from '@/components/production/move-batch-dialog';
import {
    progressQuery,
    progressUrl,
} from '@/components/production/progress-address';
import { ProgressFiltersBar } from '@/components/production/progress-filters';
import { ProgressProductCard } from '@/components/production/progress-product-card';
import { StartBatchDialog } from '@/components/production/start-batch-dialog';
import { UndoBatchDialog } from '@/components/production/undo-batch-dialog';
import { EmptyState } from '@/components/shared/empty-state';
import { PageHeader } from '@/components/shared/page-header';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import { index } from '@/routes/progress';
import { index as phasesIndex } from '@/routes/settings/production-phases';
import type {
    ProgressBatch,
    ProgressIndexProps,
    ProgressStep,
    RestockProductRef,
} from '@/types/domain';

/** The figures, deferred in one group: one "Try again" reloads them all. */
const FIGURES = [
    'tiles',
    'all_units',
    'total_units',
    'has_batches',
    'products',
    'phase_view',
];

function LoadError({ message }: { message: string }) {
    const [reloading, setReloading] = useState(false);

    return (
        <div
            role="alert"
            className="flex flex-col items-center gap-3 rounded-xl border p-8 text-center text-sm"
        >
            <p>{message}</p>
            <Button
                type="button"
                variant="outline"
                aria-disabled={reloading || undefined}
                onClick={() => {
                    if (!reloading) {
                        setReloading(true);
                        router.reload({
                            only: FIGURES,
                            onFinish: () => setReloading(false),
                        });
                    }
                }}
            >
                Try again
            </Button>
        </div>
    );
}

function CardsLoading() {
    return (
        <div
            aria-busy="true"
            aria-label="Loading progress"
            className="flex flex-col gap-4"
        >
            {[0, 1].map((card) => (
                <Skeleton key={card} className="h-48 w-full rounded-xl" />
            ))}
        </div>
    );
}

function RowsLoading() {
    return (
        <div
            aria-busy="true"
            aria-label="Loading batches"
            className="flex flex-col gap-2"
        >
            <Skeleton className="h-6 w-40" />
            {Array.from({ length: 5 }, (_, row) => (
                <Skeleton key={row} className="h-10 w-full" />
            ))}
        </div>
    );
}

/**
 * Progress (INV-5, AC-3 and AC-4): the batches the workshop is making for its
 * shelf. The tiles choose All phases (a card per product) or one phase (a row
 * per batch); Product and Colour filter both, kept in the page address.
 */
export default function Progress({
    path,
    filters,
    product_filter: productFilter,
    colours,
    tiles,
    all_units: allUnits,
    total_units: totalUnits,
    has_batches: hasBatches,
    products,
    phase_view: phaseView,
}: ProgressIndexProps) {
    const { locale } = useAppSettings();
    const [starting, setStarting] = useState<{
        open: boolean;
        preset: RestockProductRef | null;
    }>({ open: false, preset: null });
    const [moving, setMoving] = useState<BatchWindow | null>(null);
    const [undoing, setUndoing] = useState<ProgressBatch | null>(null);
    const filtered = filters.product !== null || filters.colour !== null;
    const phase =
        filters.phase === null
            ? null
            : (path.find((step) => step.phase === filters.phase) ?? null);

    function visit(next: { product: number | null; colour: number | null }) {
        // `page` is never sent: a new filter starts at page 1.
        router.get(index.url(), progressQuery(next, filters.phase), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function act(action: BatchAction) {
        if (!phaseView) {
            return;
        }

        const from: ProgressStep = phaseView.phase;

        switch (action.kind) {
            case 'forward':
                setMoving({
                    batch: action.batch,
                    from,
                    to: phaseView.next,
                    back: false,
                });
                break;
            case 'stock':
                setMoving({ batch: action.batch, from, to: null, back: false });
                break;
            case 'back':
                setMoving({
                    batch: action.batch,
                    from,
                    to: phaseView.previous,
                    back: true,
                });
                break;
            case 'undo':
                setUndoing(action.batch);
                break;
        }
    }

    const start = (preset: RestockProductRef | null) =>
        setStarting({ open: true, preset });

    const startButton = (
        <Button
            type="button"
            className="max-sm:h-11 max-sm:w-full"
            onClick={() => start(null)}
        >
            <Plus aria-hidden="true" />
            Start batch
        </Button>
    );

    const colourName = colours.find(
        (colour) => colour.id === filters.colour,
    )?.name;
    const showing = [productFilter?.name, colourName]
        .filter(Boolean)
        .join(' · ');

    return (
        <>
            <Head title={phase ? `${phase.name} · Progress` : 'Progress'} />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <PageHeader
                    title="Progress"
                    description="The batches you are making for your shelf, phase by phase, in their colour and size."
                    actions={
                        <>
                            <Button
                                asChild
                                variant="outline"
                                className="max-sm:h-11 max-sm:flex-1"
                            >
                                <Link href={phasesIndex()}>
                                    <Settings aria-hidden="true" />
                                    Production phases
                                </Link>
                            </Button>
                            {startButton}
                        </>
                    }
                />

                <PhaseTiles
                    path={path}
                    filters={filters}
                    tiles={tiles}
                    allUnits={allUnits}
                />

                <div className="flex flex-col gap-2">
                    <ProgressFiltersBar
                        product={productFilter}
                        colour={filters.colour}
                        colours={colours}
                        onProduct={(next) =>
                            visit({
                                product: next?.id ?? null,
                                colour: filters.colour,
                            })
                        }
                        onColour={(next) =>
                            visit({ product: filters.product, colour: next })
                        }
                    />
                    {filtered && (
                        <p className="flex flex-wrap items-center gap-2 text-sm">
                            {allUnits !== undefined &&
                                totalUnits !== undefined && (
                                    <span>
                                        Showing {showing} ·{' '}
                                        {formatCount(allUnits, locale)} of{' '}
                                        {formatCount(totalUnits, locale)}{' '}
                                        {totalUnits === 1 ? 'unit' : 'units'}
                                    </span>
                                )}
                            <Button
                                type="button"
                                variant="link"
                                className="h-auto p-0"
                                onClick={() =>
                                    visit({ product: null, colour: null })
                                }
                            >
                                Clear filters
                            </Button>
                        </p>
                    )}
                </div>

                <Deferred
                    data={FIGURES}
                    fallback={phase ? <RowsLoading /> : <CardsLoading />}
                    rescue={() => (
                        <LoadError
                            message={
                                phase
                                    ? `Couldn't load what is in ${phase.name}.`
                                    : "Couldn't load the progress of your production."
                            }
                        />
                    )}
                >
                    {phase && phaseView ? (
                        <PhaseView
                            view={phaseView}
                            filtered={filtered}
                            onAction={act}
                            onClearFilters={() =>
                                visit({ product: null, colour: null })
                            }
                            showAllHref={progressUrl(filters, null)}
                        />
                    ) : products && products.data.length > 0 ? (
                        <div className="flex flex-col gap-4">
                            {products.data.map((card) => (
                                <ProgressProductCard
                                    key={card.product.id}
                                    card={card}
                                    path={path}
                                    onStartBatch={(chosen) =>
                                        start(chosen.product)
                                    }
                                />
                            ))}
                            {products.meta.last_page > 1 && (
                                <Pagination
                                    meta={products.meta}
                                    only={['products']}
                                />
                            )}
                        </div>
                    ) : hasBatches ? (
                        <EmptyState
                            icon={SearchX}
                            title="No batch matches these filters."
                            description="Try another product or colour, or clear the filters."
                            action={
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        visit({ product: null, colour: null })
                                    }
                                >
                                    Clear filters
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            icon={Factory}
                            title="Nothing in production"
                            description="Start a batch to make products for your shelf. They are added to your finished stock when you put them in stock."
                            action={startButton}
                        />
                    )}
                </Deferred>
            </div>

            <StartBatchDialog
                open={starting.open}
                preset={starting.preset}
                onClose={() =>
                    setStarting((current) => ({ ...current, open: false }))
                }
            />
            <MoveBatchDialog target={moving} onClose={() => setMoving(null)} />
            <UndoBatchDialog batch={undoing} onClose={() => setUndoing(null)} />
        </>
    );
}

Progress.layout = (props: ProgressIndexProps) => {
    const phase =
        props.filters.phase === null
            ? null
            : props.path.find((step) => step.phase === props.filters.phase);

    return {
        breadcrumbs: [
            { title: 'Progress', href: index() },
            ...(phase
                ? [
                      {
                          title: phase.name,
                          href: progressUrl(props.filters, phase.phase),
                      },
                  ]
                : []),
        ],
    };
};
