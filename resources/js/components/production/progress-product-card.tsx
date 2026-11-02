import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/products';
import type {
    ProgressProductCard as Card,
    ProgressRow,
    ProgressStep,
} from '@/types/domain';

type ProgressProductCardProps = {
    card: Card;
    path: ProgressStep[];
    onStartBatch: (card: Card) => void;
};

/** A count in the number font; 0 in the readable grey. */
function Figure({ value }: { value: number }) {
    const { locale } = useAppSettings();

    return (
        <span
            className={cn(
                'font-mono tabular-nums',
                value === 0 && 'text-muted-foreground',
            )}
        >
            {formatCount(value, locale)}
        </span>
    );
}

/** "On the shelf now": the count, or the amber word Out at 0 (never colour alone). */
function Shelf({ value }: { value: number | null }) {
    const { locale } = useAppSettings();

    if (value === null) {
        return null;
    }

    if (value === 0) {
        return (
            <span className="font-medium text-amber-700 dark:text-amber-400">
                Out
            </span>
        );
    }

    return (
        <span className="font-mono tabular-nums">
            {formatCount(value, locale)}
        </span>
    );
}

/** The row's name: a colour (with its swatch) or, indented, a size. */
function rowName(row: ProgressRow, oneSize: boolean) {
    if (row.size === null || oneSize) {
        return <ColourTag colour={row.colour} />;
    }

    return <span className="pl-6">{row.size.label}</span>;
}

function isColourRow(row: ProgressRow, oneSize: boolean): boolean {
    return row.size === null && !oneSize;
}

/** The phone: a band per colour and a line per size with the phases that hold units and the shelf. */
function PhoneLines({
    card,
    path,
}: Omit<ProgressProductCardProps, 'onStartBatch'>) {
    const { locale } = useAppSettings();

    const line = (byPhase: number[], extra: string | null) =>
        [
            ...path.flatMap((step, i) =>
                byPhase[i] > 0
                    ? [`${step.name} ${formatCount(byPhase[i], locale)}`]
                    : [],
            ),
            ...(extra === null ? [] : [extra]),
        ].join(' · ');

    const shelf = (value: number | null) =>
        value === null
            ? null
            : `On the shelf ${value === 0 ? 'Out' : formatCount(value, locale)}`;

    return (
        <ul className="flex flex-col gap-1 text-sm">
            <li className="font-semibold">
                Total · {line(card.total.by_phase, null)}
            </li>
            {card.rows.map((row) =>
                isColourRow(row, card.one_size) ? (
                    <li
                        key={`c-${row.colour.id}`}
                        className="mt-1 rounded-md bg-muted/60 px-2 py-1 font-medium"
                    >
                        <ColourTag colour={row.colour} />
                    </li>
                ) : (
                    <li
                        key={`s-${row.colour.id}-${row.size?.id}`}
                        className="px-2"
                    >
                        {card.one_size
                            ? row.colour.name
                            : (row.size?.label ?? '')}{' '}
                        · {line(row.by_phase, shelf(row.on_shelf))}
                    </li>
                ),
            )}
        </ul>
    );
}

/** One product's card on Progress, All phases (INV-5, AC-3). */
export function ProgressProductCard({
    card,
    path,
    onStartBatch,
}: ProgressProductCardProps) {
    const { locale } = useAppSettings();
    const isMobile = useIsMobile();
    const { product } = card;
    const name = product.deleted ? `${product.name} (deleted)` : product.name;

    const startButton = (
        <Button
            type="button"
            variant="outline"
            className="max-sm:h-11 max-sm:w-full"
            aria-label={`Start a batch of ${product.name}`}
            disabled={product.deleted}
            onClick={() => onStartBatch(card)}
        >
            <Plus aria-hidden="true" />
            Start batch
        </Button>
    );

    return (
        <section
            aria-label={product.name}
            className="flex flex-col gap-3 rounded-xl border bg-card p-4 text-card-foreground"
        >
            <header className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <h2 className="text-base font-semibold [overflow-wrap:anywhere]">
                        {product.deleted ? (
                            name
                        ) : (
                            <Link
                                href={show(product.id)}
                                className="hover:underline"
                            >
                                {name}
                            </Link>
                        )}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        <span className="font-mono">{product.sku}</span> ·{' '}
                        {formatCount(card.units, locale)}{' '}
                        {card.units === 1 ? 'unit' : 'units'} in{' '}
                        {formatCount(card.batches, locale)}{' '}
                        {card.batches === 1 ? 'batch' : 'batches'}
                    </p>
                </div>
                {!isMobile && startButton}
            </header>

            {isMobile ? (
                <>
                    <PhoneLines card={card} path={path} />
                    {startButton}
                </>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <caption className="sr-only">
                            {product.name} by phase
                        </caption>
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th
                                    scope="col"
                                    className="py-2 pr-4 font-medium"
                                >
                                    Colour · size
                                </th>
                                {path.map((step) => (
                                    <th
                                        key={String(step.phase)}
                                        scope="col"
                                        className="px-3 py-2 text-right font-medium"
                                    >
                                        {step.name}
                                    </th>
                                ))}
                                <th
                                    scope="col"
                                    className="px-3 py-2 text-right font-medium"
                                >
                                    In production
                                </th>
                                <th
                                    scope="col"
                                    className="border-l py-2 pl-6 text-right font-medium"
                                >
                                    On the shelf now
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {card.rows.map((row) => {
                                const colourRow = isColourRow(
                                    row,
                                    card.one_size,
                                );

                                return (
                                    <tr
                                        key={`${row.colour.id}-${row.size?.id ?? 'all'}`}
                                        className={cn(
                                            'border-b',
                                            colourRow &&
                                                'bg-muted/50 font-medium',
                                        )}
                                    >
                                        <th
                                            scope="row"
                                            className="py-2 pr-4 text-left font-normal"
                                        >
                                            {rowName(row, card.one_size)}
                                        </th>
                                        {row.by_phase.map((units, i) => (
                                            <td
                                                key={i}
                                                className="px-3 py-2 text-right"
                                            >
                                                <Figure value={units} />
                                            </td>
                                        ))}
                                        <td className="px-3 py-2 text-right font-medium">
                                            <Figure value={row.in_production} />
                                        </td>
                                        <td className="border-l py-2 pl-6 text-right">
                                            <Shelf value={row.on_shelf} />
                                        </td>
                                    </tr>
                                );
                            })}
                            <tr className="border-t-2 border-foreground/30 font-semibold">
                                <th scope="row" className="py-2 pr-4 text-left">
                                    Total
                                </th>
                                {card.total.by_phase.map((units, i) => (
                                    <td
                                        key={i}
                                        className="px-3 py-2 text-right"
                                    >
                                        <Figure value={units} />
                                    </td>
                                ))}
                                <td className="px-3 py-2 text-right">
                                    <Figure value={card.total.in_production} />
                                </td>
                                <td className="border-l py-2 pl-6" />
                            </tr>
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}
