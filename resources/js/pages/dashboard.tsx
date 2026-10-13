import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { KeyFigures } from '@/components/dashboard/key-figures';
import { NeedsRestockCard } from '@/components/dashboard/needs-restock-card';
import { RecentSalesCard } from '@/components/dashboard/recent-sales-card';
import { TopProductsCard } from '@/components/dashboard/top-products-card';
import { UnproducibleAlerts } from '@/components/dashboard/unproducible-alerts';
import { PageHeader } from '@/components/shared/page-header';
import { SkeletonCard, SkeletonStat } from '@/components/shared/skeletons';
import { DEFAULT_PERIOD, PeriodSelect } from '@/components/sales/period-select';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { DashboardProps, SalesPeriod } from '@/types/domain';

/**
 * Only the figures that follow the period. Restock, recent sales and alerts
 * don't: they are deferred props, loaded once after the first paint.
 */
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
                                >
                                    <Plus aria-hidden="true" />
                                    Record sale
                                </Link>
                            </Button>
                        </>
                    }
                />

                <Deferred
                    data="alerts"
                    fallback={
                        <span role="status" className="sr-only">
                            Checking production alerts…
                        </span>
                    }
                >
                    <UnproducibleAlerts alerts={alerts ?? []} />
                </Deferred>

                <Deferred
                    data="stats"
                    fallback={
                        <section
                            aria-label="Key figures"
                            className="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4"
                        >
                            <SkeletonStat label="Total sales" />
                            <SkeletonStat label="Units sold" />
                            <SkeletonStat label="Inventory value" />
                            <SkeletonStat label="Needs restock" />
                            <SkeletonStat label="Can't produce" />
                        </section>
                    }
                >
                    {stats && (
                        <KeyFigures stats={stats} periodLabel={periodLabel} />
                    )}
                </Deferred>

                <div className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] xl:items-start">
                    <Deferred
                        data="top_products"
                        fallback={
                            <SkeletonCard
                                title="Top selling products"
                                label="top selling products"
                                rows={5}
                            />
                        }
                    >
                        <TopProductsCard
                            byUnits={topProducts?.by_units ?? []}
                            byRevenue={topProducts?.by_revenue ?? []}
                            periodLabel={periodLabel}
                        />
                    </Deferred>
                    <Deferred
                        data="needs_restock"
                        fallback={
                            <SkeletonCard
                                title="Needs restock"
                                label="restock list"
                                rows={5}
                            />
                        }
                    >
                        <NeedsRestockCard materials={needsRestock ?? []} />
                    </Deferred>
                </div>

                <Deferred
                    data="recent_sales"
                    fallback={
                        <SkeletonCard
                            title="Recent sales"
                            label="recent sales"
                            rows={5}
                            thumb={false}
                        />
                    }
                >
                    <RecentSalesCard sales={recentSales ?? []} />
                </Deferred>
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
