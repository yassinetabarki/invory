import { formatUnits } from '@/components/orders/stage-figure';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { OpenOrderTotals as OpenOrderTotalsData } from '@/types/domain';

type OpenOrderTotalsProps = {
    totals: OpenOrderTotalsData;
};

/**
 * The two totals of every open order of the workshop (INV-14, AC-5): Open
 * orders and their units To deliver. The server counts them whatever the
 * search and the Status filter, so they are only formatted here. Side by side,
 * also on a phone.
 */
export function OpenOrderTotals({ totals }: OpenOrderTotalsProps) {
    const { locale } = useAppSettings();

    const figures = [
        {
            label: 'Open orders',
            value: formatCount(totals.open_orders, locale),
        },
        { label: 'To deliver', value: formatUnits(totals.to_deliver, locale) },
    ];

    return (
        <section aria-label="Open orders totals">
            <dl className="grid grid-cols-2 gap-3 lg:max-w-xl">
                {figures.map(({ label, value }) => (
                    <div
                        key={label}
                        className="flex min-w-0 flex-col gap-1 rounded-xl border bg-card px-4 py-3 text-card-foreground"
                    >
                        <dt className="text-sm font-medium text-muted-foreground">
                            {label}
                        </dt>
                        <dd className="text-xl font-bold tracking-tight tabular-nums">
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
