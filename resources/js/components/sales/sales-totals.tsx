import type { ReactNode } from 'react';
import { Money } from '@/components/shared/money';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { SalesTotals as SalesTotalsData } from '@/types/domain';

type SalesTotalsProps = {
    /** The server's totals for the filtered set (search, product and period). */
    totals: SalesTotalsData;
};

function Total({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex flex-col gap-0.5 rounded-xl border bg-card px-[18px] py-3.5 text-card-foreground">
            <dt className="text-[0.8125rem] font-medium text-muted-foreground">
                {label}
            </dt>
            <dd className="text-[1.375rem] font-bold tabular-nums">
                {children}
            </dd>
        </div>
    );
}

/** Totals of what the list below shows. The average is "—" without sales. */
export function SalesTotals({ totals }: SalesTotalsProps) {
    const { locale } = useAppSettings();

    return (
        <section aria-label="Totals for the selected period">
            <dl className="grid grid-cols-[repeat(auto-fit,minmax(11.25rem,1fr))] gap-3">
                <Total label="Total sales">
                    <Money value={totals.revenue} />
                </Total>
                <Total label="Units sold">
                    {formatCount(totals.units_sold, locale)}
                </Total>
                <Total label="Sales recorded">
                    {formatCount(totals.sales_count, locale)}
                </Total>
                <Total label="Average sale">
                    <Money value={totals.average_sale} />
                </Total>
            </dl>
        </section>
    );
}
