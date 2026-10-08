import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Money } from '@/components/shared/money';
import { StatCard } from '@/components/shared/stat-card';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { DashboardStats } from '@/types/domain';

type KeyFiguresProps = {
    stats: DashboardStats;
    /** Label of the selected period from the server, e.g. "Last 30 days". */
    periodLabel: string;
};

function plural(count: number, one: string, many: string): string {
    return count === 1 ? one : many;
}

/**
 * The five KPI cards. Every figure is the server's (`stats`); this formats
 * and words them. "Needs restock" and "Can't produce" link to the filtered
 * lists and only take the warning / danger tone when there is something to fix.
 */
export function KeyFigures({ stats, periodLabel }: KeyFiguresProps) {
    const { locale } = useAppSettings();
    const count = (value: number) => formatCount(value, locale);

    return (
        <section
            aria-label="Key figures"
            className="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4"
        >
            <StatCard
                label="Total sales"
                value={<Money value={stats.revenue} />}
                hint={`${count(stats.sales_count)} ${plural(stats.sales_count, 'sale', 'sales')} · ${periodLabel.toLocaleLowerCase(locale)}`}
            />
            <StatCard
                label="Units sold"
                value={count(stats.units_sold)}
                hint={`across ${count(stats.products_sold_count)} ${plural(stats.products_sold_count, 'product', 'products')}`}
            />
            <StatCard
                label="Inventory value"
                value={<Money value={stats.inventory_value} />}
                hint={`${count(stats.materials_count)} ${plural(stats.materials_count, 'material', 'materials')}`}
            />
            <StatCard
                label="Needs restock"
                value={count(stats.needs_restock_count)}
                hint={`${count(stats.low_stock_count)} low · ${count(stats.out_of_stock_count)} out of stock`}
                tone={stats.needs_restock_count > 0 ? 'warning' : 'default'}
                href={MaterialController.index({ query: { low_stock: 1 } })}
            />
            <StatCard
                label="Can't produce"
                value={count(stats.unproducible_count)}
                hint={`of ${count(stats.products_count)} ${plural(stats.products_count, 'product', 'products')}`}
                tone={stats.unproducible_count > 0 ? 'danger' : 'default'}
                href={ProductController.index({ query: { cant_produce: 1 } })}
            />
        </section>
    );
}
