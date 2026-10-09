import { Link } from '@inertiajs/react';
import { useId, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Money } from '@/components/shared/money';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { useAppSettings } from '@/hooks/use-app-settings';
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { TopProduct } from '@/types/domain';

type Metric = 'units' | 'revenue';

type TopProductsCardProps = {
    byUnits: TopProduct[];
    byRevenue: TopProduct[];
    /** Label of the selected period from the server, e.g. "Last 30 days". */
    periodLabel: string;
};

const METRICS: { value: Metric; label: string; rankedBy: string }[] = [
    { value: 'units', label: 'Units', rankedBy: 'units sold' },
    { value: 'revenue', label: 'Revenue', rankedBy: 'revenue' },
];

function UnitsText({ value }: { value: number }) {
    const { locale } = useAppSettings();

    return (
        <>
            {formatCount(value, locale)} {value === 1 ? 'unit' : 'units'}
        </>
    );
}

/**
 * Best sellers of the period with a Units / Revenue switch. The server sends
 * both rankings with their own bar lengths (`percent_of_top`, scale-2 string
 * of percent points), so the switch only picks a list: the bar width is that
 * string as CSS and nothing is ever divided or compared here.
 */
export function TopProductsCard({
    byUnits,
    byRevenue,
    periodLabel,
}: TopProductsCardProps) {
    const headingId = useId();
    const [metric, setMetric] = useState<Metric>('units');
    const rows = metric === 'units' ? byUnits : byRevenue;
    const rankedBy = METRICS.find((m) => m.value === metric)?.rankedBy;

    return (
        <section
            aria-labelledby={headingId}
            className="flex min-w-0 flex-col gap-3 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="space-y-0.5">
                    <h2 id={headingId} className="text-base font-bold">
                        Top selling products
                    </h2>
                    <p className="text-[0.8125rem] text-muted-foreground">
                        {periodLabel}, ranked by {rankedBy}
                    </p>
                </div>
                {byUnits.length > 0 && (
                    <div
                        role="group"
                        aria-label="Rank by"
                        className="inline-flex gap-0.5 rounded-lg bg-muted p-[3px]"
                    >
                        {METRICS.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                aria-pressed={metric === option.value}
                                onClick={() => setMetric(option.value)}
                                className={cn(
                                    'min-h-8 rounded-md px-3 text-sm font-semibold transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                    metric === option.value
                                        ? 'bg-card text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {rows.length === 0 ? (
                <p className="py-6 text-sm text-muted-foreground">
                    No sales in this period. Record a sale to see your best
                    sellers here.
                </p>
            ) : (
                <ol aria-labelledby={headingId}>
                    {rows.map((row, index) => (
                        <li
                            key={row.product.id}
                            className="grid grid-cols-[1.25rem_2.5rem_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2 border-t py-2.5 sm:grid-cols-[1.25rem_2.5rem_minmax(0,1fr)_minmax(3.75rem,12.5rem)_6.5rem]"
                        >
                            <span className="font-semibold text-muted-foreground tabular-nums">
                                {index + 1}
                            </span>
                            <PhotoThumb
                                src={row.product.photo_url}
                                alt=""
                                size="md"
                            />
                            <span className="flex min-w-0 flex-col">
                                <Link
                                    {...prefetchProps(CACHE_TAGS.products)}
                                    href={ProductController.show(
                                        row.product.id,
                                    )}
                                    className="truncate font-semibold hover:underline focus-visible:underline"
                                >
                                    {row.product.name}
                                </Link>
                                <span className="font-mono text-xs text-muted-foreground">
                                    {row.product.sku}
                                </span>
                            </span>
                            <span
                                aria-hidden="true"
                                data-slot="bar"
                                className="col-span-2 col-start-3 row-start-2 block h-2 overflow-hidden rounded-sm bg-bar-track sm:col-span-1 sm:col-start-4 sm:row-start-1"
                            >
                                <span
                                    className="block h-full rounded-sm bg-primary"
                                    style={{ width: `${row.percent_of_top}%` }}
                                />
                            </span>
                            <span className="col-start-4 row-start-1 flex flex-col items-end text-right whitespace-nowrap tabular-nums sm:col-start-5">
                                <span className="font-bold">
                                    {metric === 'units' ? (
                                        <UnitsText value={row.units_sold} />
                                    ) : (
                                        <Money value={row.revenue} />
                                    )}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {metric === 'units' ? (
                                        <Money value={row.revenue} />
                                    ) : (
                                        <UnitsText value={row.units_sold} />
                                    )}
                                </span>
                            </span>
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}
