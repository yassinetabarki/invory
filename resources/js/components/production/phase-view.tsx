import { Link } from '@inertiajs/react';
import { MoreHorizontal, SearchX } from 'lucide-react';
import { daysLabel, variantName } from '@/components/production/batch-names';
import { ReasonedButton } from '@/components/production/reasoned-button';
import { ColourTag } from '@/components/shared/colour-tag';
import { EmptyState } from '@/components/shared/empty-state';
import { Pagination } from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount, formatDate } from '@/lib/format';
import { show } from '@/routes/products';
import type { ProgressBatch, ProgressPhaseView } from '@/types/domain';

export type BatchAction =
    | { kind: 'forward'; batch: ProgressBatch }
    | { kind: 'back'; batch: ProgressBatch }
    | { kind: 'stock'; batch: ProgressBatch }
    | { kind: 'undo'; batch: ProgressBatch };

type PhaseViewProps = {
    view: ProgressPhaseView;
    filtered: boolean;
    onAction: (action: BatchAction) => void;
    onClearFilters: () => void;
    showAllHref: string;
};

/** "15 units · 3 batches · 2 products · oldest since 26 Oct 2026". */
function summary(view: ProgressPhaseView, locale: string): string {
    const count = (value: number, one: string, many: string) =>
        `${formatCount(value, locale)} ${value === 1 ? one : many}`;

    return [
        count(view.units, 'unit', 'units'),
        count(view.batch_count, 'batch', 'batches'),
        count(view.product_count, 'product', 'products'),
        ...(view.oldest_since
            ? [`oldest since ${formatDate(view.oldest_since, locale)}`]
            : []),
    ].join(' · ');
}

/** "From In cutting. Next: Finishing." */
function pathLine(view: ProgressPhaseView): string {
    return [
        view.previous ? `From ${view.previous.name}.` : null,
        `Next: ${view.next?.name ?? 'Put in stock'}.`,
    ]
        .filter(Boolean)
        .join(' ');
}

function productName(batch: ProgressBatch): string {
    return batch.product.deleted
        ? `${batch.product.name} (deleted)`
        : batch.product.name;
}

function ForwardButton({
    batch,
    view,
    onAction,
    className,
}: {
    batch: ProgressBatch;
    view: ProgressPhaseView;
    onAction: PhaseViewProps['onAction'];
    className?: string;
}) {
    const variant = variantName(batch);

    if (view.next === null) {
        return (
            <ReasonedButton
                type="button"
                className={className}
                aria-label={`Put batch ${batch.number} ${batch.product.name} ${variant} in stock`}
                reason={
                    batch.product.deleted
                        ? `${batch.product.name} was deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials.`
                        : null
                }
                onClick={() => onAction({ kind: 'stock', batch })}
            >
                Put in stock
            </ReasonedButton>
        );
    }

    return (
        <Button
            type="button"
            variant="outline"
            className={className}
            aria-label={`Move batch ${batch.number} ${batch.product.name} ${variant} to ${view.next.name}`}
            onClick={() => onAction({ kind: 'forward', batch })}
        >
            Move to {view.next.name}
        </Button>
    );
}

function MoreMenu({
    batch,
    view,
    onAction,
    className,
}: {
    batch: ProgressBatch;
    view: ProgressPhaseView;
    onAction: PhaseViewProps['onAction'];
    className?: string;
}) {
    const inCutting = view.phase.phase === 'cutting';

    if (view.previous === null && !inCutting) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    className={className}
                    aria-label={`More moves for batch ${batch.number}`}
                >
                    <MoreHorizontal aria-hidden="true" />
                    <span className="sm:sr-only">More</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {view.previous && (
                    <DropdownMenuItem
                        onSelect={() => onAction({ kind: 'back', batch })}
                    >
                        Move back to {view.previous.name}
                    </DropdownMenuItem>
                )}
                {inCutting && (
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => onAction({ kind: 'undo', batch })}
                    >
                        Undo batch
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function ProductCell({ batch }: { batch: ProgressBatch }) {
    return (
        <div className="min-w-0">
            {batch.product.deleted ? (
                <span>{productName(batch)}</span>
            ) : (
                <Link href={show(batch.product.id)} className="hover:underline">
                    {batch.product.name}
                </Link>
            )}
            <p className="font-mono text-xs text-muted-foreground">
                {batch.product.sku}
            </p>
        </div>
    );
}

function VariantCell({ batch }: { batch: ProgressBatch }) {
    return (
        <span className="inline-flex items-center gap-1">
            <ColourTag colour={batch.colour} />
            {!batch.one_size && <span>· {batch.size.label}</span>}
        </span>
    );
}

/** The view of one phase (INV-5, AC-4): its heading and a row per batch, oldest first. */
export function PhaseView({
    view,
    filtered,
    onAction,
    onClearFilters,
    showAllHref,
}: PhaseViewProps) {
    const { locale } = useAppSettings();
    const isMobile = useIsMobile();
    const name = view.phase.name;
    const batches = view.batches.data;

    let body;

    if (batches.length === 0 && filtered) {
        body = (
            <EmptyState
                icon={SearchX}
                title={`Nothing in ${name} matches these filters.`}
                action={
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClearFilters}
                    >
                        Clear filters
                    </Button>
                }
            />
        );
    } else if (batches.length === 0) {
        body = (
            <EmptyState
                title={`Nothing in ${name}`}
                description={
                    view.previous
                        ? `Batches come here from ${view.previous.name} with Move to ${name}.`
                        : 'Batches come here with Start batch.'
                }
                action={
                    <Button asChild variant="outline">
                        <Link href={showAllHref}>Show all phases</Link>
                    </Button>
                }
            />
        );
    } else if (isMobile) {
        body = (
            <ul className="flex flex-col gap-3" aria-label={`In ${name}`}>
                {batches.map((batch) => (
                    <li
                        key={batch.id}
                        className="flex flex-col gap-2 rounded-xl border bg-card p-4 text-card-foreground"
                    >
                        <div className="flex items-start justify-between gap-2">
                            <ProductCell batch={batch} />
                            <span className="font-mono text-sm">
                                {batch.number}
                            </span>
                        </div>
                        <VariantCell batch={batch} />
                        <p className="text-lg font-bold">
                            {formatCount(batch.units, locale)}{' '}
                            {batch.units === 1 ? 'unit' : 'units'}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            In {name} since {formatDate(batch.since, locale)} ·{' '}
                            {daysLabel(batch.since_days)}
                        </p>
                        <ForwardButton
                            batch={batch}
                            view={view}
                            onAction={onAction}
                            className="h-11 w-full"
                        />
                        <MoreMenu
                            batch={batch}
                            view={view}
                            onAction={onAction}
                            className="h-11 w-full border"
                        />
                    </li>
                ))}
            </ul>
        );
    } else {
        body = (
            <div className="overflow-x-auto rounded-xl border bg-card">
                <table className="w-full text-sm">
                    <caption className="sr-only">In {name}</caption>
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th scope="col" className="px-4 py-2 font-medium">
                                Since
                            </th>
                            <th scope="col" className="px-4 py-2 font-medium">
                                Batch
                            </th>
                            <th scope="col" className="px-4 py-2 font-medium">
                                Product
                            </th>
                            <th scope="col" className="px-4 py-2 font-medium">
                                Colour · size
                            </th>
                            <th
                                scope="col"
                                className="px-4 py-2 text-right font-medium"
                            >
                                Units
                            </th>
                            <th scope="col" className="px-4 py-2">
                                <span className="sr-only">Moves</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {batches.map((batch) => (
                            <tr
                                key={batch.id}
                                className="border-b last:border-b-0"
                            >
                                <td className="px-4 py-2">
                                    <p>{formatDate(batch.since, locale)}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {daysLabel(batch.since_days)}
                                    </p>
                                </td>
                                <td className="px-4 py-2 font-mono">
                                    {batch.number}
                                </td>
                                <td className="px-4 py-2">
                                    <ProductCell batch={batch} />
                                </td>
                                <td className="px-4 py-2">
                                    <VariantCell batch={batch} />
                                </td>
                                <td className="px-4 py-2 text-right font-mono font-bold tabular-nums">
                                    {formatCount(batch.units, locale)}
                                </td>
                                <td className="px-4 py-2">
                                    <div className="flex justify-end gap-1">
                                        <ForwardButton
                                            batch={batch}
                                            view={view}
                                            onAction={onAction}
                                            className="h-11 sm:h-9"
                                        />
                                        <MoreMenu
                                            batch={batch}
                                            view={view}
                                            onAction={onAction}
                                            className="size-11 sm:size-9"
                                        />
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        );
    }

    return (
        <section
            aria-labelledby="phase-heading"
            className="flex flex-col gap-3"
        >
            <div>
                <h2 id="phase-heading" className="text-lg font-semibold">
                    {name}
                </h2>
                <p className="text-sm">{summary(view, locale)}</p>
                <p className="text-sm text-muted-foreground">
                    {pathLine(view)}
                </p>
            </div>
            {body}
            {view.batches.meta.last_page > 1 && (
                <Pagination meta={view.batches.meta} only={['phase_view']} />
            )}
        </section>
    );
}
