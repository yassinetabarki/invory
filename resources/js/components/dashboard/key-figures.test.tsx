import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { KeyFigures } from '@/components/dashboard/key-figures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import { dashboardStats } from '@/test/fixtures/dashboard';
import type { DashboardStats } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderFigures(
    stats: DashboardStats = dashboardStats,
    periodLabel = 'Last 30 days',
) {
    render(<KeyFigures stats={stats} periodLabel={periodLabel} />);

    return screen.getByRole('region', { name: 'Key figures' });
}

/** The card whose label is `label` (a link card or a plain one). */
function cardOf(region: HTMLElement, label: string): HTMLElement {
    const card = within(region)
        .getByText(label)
        .closest<HTMLElement>('[data-slot="stat-card"]');

    if (!card) {
        throw new Error(`No stat card labelled "${label}"`);
    }

    return card;
}

describe('<KeyFigures>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the five figures with their hints (mockup values, EUR)', () => {
        const region = renderFigures();

        const total = cardOf(region, 'Total sales');
        expect(within(total).getByText('€16,850.00')).toBeInTheDocument();
        expect(
            within(total).getByText('48 sales · last 30 days'),
        ).toBeVisible();

        const units = cardOf(region, 'Units sold');
        expect(within(units).getByText('296')).toBeInTheDocument();
        expect(within(units).getByText('across 6 products')).toBeVisible();

        const inventory = cardOf(region, 'Inventory value');
        expect(within(inventory).getByText('€10,544.30')).toBeInTheDocument();
        expect(within(inventory).getByText('12 materials')).toBeVisible();

        const restock = cardOf(region, 'Needs restock');
        expect(within(restock).getByText('4')).toBeInTheDocument();
        expect(
            within(restock).getByText('3 low · 1 out of stock'),
        ).toBeVisible();

        const cantProduce = cardOf(region, "Can't produce");
        expect(within(cantProduce).getByText('1')).toBeInTheDocument();
        expect(within(cantProduce).getByText('of 7 products')).toBeVisible();
    });

    it('formats money in the configured currency, never a hard-coded one', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });

        const region = renderFigures();

        expect(
            within(cardOf(region, 'Total sales')).getByText(/16,850\.000/),
        ).toHaveTextContent('TND');
        expect(region).not.toHaveTextContent('€');
    });

    it('links Needs restock to the materials that need restocking', () => {
        renderFigures();

        const link = screen.getByRole('link', { name: /Needs restock/ });

        expect(link).toHaveAttribute('href', '/materials?low_stock=1');
        expect(link).toHaveTextContent('3 low · 1 out of stock');
    });

    it("links Can't produce to the products that can't be produced", () => {
        renderFigures();

        const link = screen.getByRole('link', { name: /Can't produce/ });

        expect(link).toHaveAttribute('href', '/products?cant_produce=1');
        expect(link).toHaveTextContent('of 7 products');
    });

    it('leaves the other three figures as plain cards, not links', () => {
        renderFigures();

        expect(screen.getAllByRole('link')).toHaveLength(2);
    });

    it('uses the warning and danger tones when there is something to fix', () => {
        renderFigures();

        expect(screen.getByText('3 low · 1 out of stock')).toHaveClass(
            'text-warning-foreground',
        );
        expect(screen.getByText('of 7 products')).toHaveClass(
            'text-danger-foreground',
        );
    });

    it('drops those tones when nothing needs restocking or is blocked', () => {
        renderFigures({
            ...dashboardStats,
            needs_restock_count: 0,
            low_stock_count: 0,
            out_of_stock_count: 0,
            unproducible_count: 0,
        });

        expect(screen.getByText('0 low · 0 out of stock')).not.toHaveClass(
            'text-warning-foreground',
        );
        expect(screen.getByText('of 7 products')).not.toHaveClass(
            'text-danger-foreground',
        );
    });

    it('puts the selected period in the sales hint, in lower case', () => {
        renderFigures(dashboardStats, 'This month');

        expect(screen.getByText('48 sales · this month')).toBeVisible();
    });

    it('uses singular nouns for a count of one', () => {
        renderFigures({
            ...dashboardStats,
            sales_count: 1,
            products_sold_count: 1,
            materials_count: 1,
            products_count: 1,
        });

        expect(screen.getByText('1 sale · last 30 days')).toBeVisible();
        expect(screen.getByText('across 1 product')).toBeVisible();
        expect(screen.getByText('1 material')).toBeVisible();
        expect(screen.getByText('of 1 product')).toBeVisible();
    });

    it('groups thousands with the locale', () => {
        renderFigures({ ...dashboardStats, units_sold: 12345 });

        expect(screen.getByText('12,345')).toBeInTheDocument();
    });
});
