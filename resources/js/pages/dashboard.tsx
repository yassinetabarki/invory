import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { KeyFigures } from '@/components/dashboard/key-figures';
import { NeedsRestockCard } from '@/components/dashboard/needs-restock-card';
import { RecentSalesCard } from '@/components/dashboard/recent-sales-card';
import { TopProductsCard } from '@/components/dashboard/top-products-card';
import { UnproducibleAlerts } from '@/components/dashboard/unproducible-alerts';
import { PageHeader } from '@/components/shared/page-header';
import { DEFAULT_PERIOD, PeriodSelect } from '@/components/sales/period-select';
import { Button } from '@/components/ui/button';
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { dashboard } from '@/routes';
import type { DashboardProps, SalesPeriod } from '@/types/domain';

/** Only the figures that follow the period; restock, recent sales and alerts don't. */
const PERIOD_PROPS = ['period', 'stats', 'top_products'];

export default function Dashboard({
    period,
    periods,
    stats,
    top_products: topProducts,
    needs_restock: needsRestock,
    recent_sales: recentSales,
    alerts,
}: DashboardProps) {
    // The select owns its value so it answers at once; a period the server
    // sends (the response, or the back button) is adopted.
    const [selected, setSelected] = useState(period);
    const [seenPeriod, setSeenPeriod] = useState(period);

    if (period !== seenPeriod) {
        setSeenPeriod(period);
        setSelected(period);
    }

    function changePeriod(next: SalesPeriod) {
        setSelected(next);

        router.get(
            dashboard.url(),
            next === DEFAULT_PERIOD ? {} : { period: next },
            {
                only: PERIOD_PROPS,
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    const periodLabel =
        periods.find((option) => option.value === period)?.label ?? '';

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Dashboard"
                    description="Sales and stock at a glance."
                    actions={
                        <>
                            <PeriodSelect
                                id="dashboard-period"
                                value={selected}
                                periods={periods}
                                onChange={changePeriod}
                                className="w-44"
                            />
                            <Button asChild>
                                <Link
                                    href={SaleController.index({
                                        query: { record: 1 },
                                    })}
                                    {...prefetchProps(CACHE_TAGS.sales)}
                                >
                                    <Plus aria-hidden="true" />
                                    Record sale
                                </Link>
                            </Button>
                        </>
                    }
                />

                <UnproducibleAlerts alerts={alerts} />

                <KeyFigures stats={stats} periodLabel={periodLabel} />

                <div className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] xl:items-start">
                    <TopProductsCard
                        byUnits={topProducts.by_units}
                        byRevenue={topProducts.by_revenue}
                        periodLabel={periodLabel}
                    />
                    <NeedsRestockCard materials={needsRestock} />
                </div>

                <RecentSalesCard sales={recentSales} />
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
