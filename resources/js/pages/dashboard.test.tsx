import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Dashboard from '@/pages/dashboard';
import {
    choreAlert,
    dashboardProps,
    dashboardStats,
    sneakerAlert,
} from '@/test/fixtures/dashboard';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const VISIT_OPTIONS = {
    only: ['period', 'stats', 'top_products'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

describe('dashboard page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/dashboard', component: 'dashboard' });
    });

    describe('with data (mockup values)', () => {
        it('has the mockup header and every section', () => {
            render(<Dashboard {...dashboardProps()} />);

            expect(
                screen.getByRole('heading', { level: 1, name: 'Dashboard' }),
            ).toBeInTheDocument();
            expect(
                screen.getByText('Sales and stock at a glance.'),
            ).toBeVisible();
            expect(
                screen.getByRole('region', { name: 'Key figures' }),
            ).toBeVisible();
            expect(
                screen.getByRole('heading', { name: 'Top selling products' }),
            ).toBeVisible();
            expect(
                screen.getByRole('heading', { name: 'Needs restock' }),
            ).toBeVisible();
            expect(
                screen.getByRole('heading', { name: 'Recent sales' }),
            ).toBeVisible();
        });

        it('shows the KPI values and the links they lead to', () => {
            render(<Dashboard {...dashboardProps()} />);

            const figures = screen.getByRole('region', {
                name: 'Key figures',
            });

            expect(within(figures).getByText('€16,850.00')).toBeVisible();
            expect(within(figures).getByText('296')).toBeVisible();
            expect(within(figures).getByText('€10,544.30')).toBeVisible();
            expect(
                within(figures).getByRole('link', { name: /Needs restock/ }),
            ).toHaveAttribute('href', '/materials?low_stock=1');
            expect(
                within(figures).getByRole('link', { name: /Can't produce/ }),
            ).toHaveAttribute('href', '/products?cant_produce=1');
        });

        it('names the selected period in the hint and the top products subtitle', () => {
            render(
                <Dashboard
                    {...dashboardProps({
                        period: 'this_month',
                        stats: { ...dashboardStats, sales_count: 9 },
                    })}
                />,
            );

            expect(screen.getByText('9 sales · this month')).toBeVisible();
            expect(
                screen.getByText('This month, ranked by units sold'),
            ).toBeVisible();
        });

        it('shows the top products, the restock list and the recent sales', () => {
            render(<Dashboard {...dashboardProps()} />);

            const top = screen.getByRole('list', {
                name: 'Top selling products',
            });

            expect(within(top).getAllByRole('listitem')).toHaveLength(5);

            const restock = screen.getByRole('region', {
                name: 'Needs restock',
            });

            expect(within(restock).getAllByRole('listitem')).toHaveLength(4);

            const recent = screen.getByRole('table');

            expect(within(recent).getAllByRole('row')).toHaveLength(6);
        });

        it('switches the top products between units and revenue', async () => {
            const user = userEvent.setup();

            render(<Dashboard {...dashboardProps()} />);

            const top = screen.getByRole('list', {
                name: 'Top selling products',
            });

            expect(
                within(within(top).getAllByRole('listitem')[0]).getByRole(
                    'link',
                ),
            ).toHaveTextContent('Classic White T-shirt');

            await user.click(screen.getByRole('button', { name: 'Revenue' }));

            expect(
                within(within(top).getAllByRole('listitem')[0]).getByRole(
                    'link',
                ),
            ).toHaveTextContent('Denim Jeans 5-pocket');
        });
    });

    describe('alerts', () => {
        it('shows one alert per product that cannot be produced', () => {
            render(
                <Dashboard
                    {...dashboardProps({ alerts: [sneakerAlert, choreAlert] })}
                />,
            );

            const alerts = screen.getAllByRole('alert');

            expect(alerts).toHaveLength(2);
            expect(alerts[0]).toHaveTextContent(
                "Canvas Low Sneaker can't be produced. Rubber cupsole 42 is out of stock.",
            );
            expect(alerts[1]).toHaveTextContent(
                "Canvas Chore Jacket can't be produced. Metal shank button 17mm doesn't cover one unit.",
            );
        });

        it('shows no alert when everything can be produced', () => {
            render(<Dashboard {...dashboardProps({ alerts: [] })} />);

            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        });
    });

    describe('period select', () => {
        it('is labelled Period and offers the server periods', async () => {
            const user = userEvent.setup();

            render(<Dashboard {...dashboardProps()} />);

            const select = screen.getByRole('combobox', { name: 'Period' });

            expect(select).toHaveTextContent('Last 30 days');

            await user.click(select);

            expect(
                screen
                    .getAllByRole('option')
                    .map((option) => option.textContent),
            ).toEqual([
                'Last 7 days',
                'Last 30 days',
                'This month',
                'This year',
            ]);
        });

        it('requests the chosen period, keeping state and scroll', async () => {
            const user = userEvent.setup();

            render(<Dashboard {...dashboardProps()} />);

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(screen.getByRole('option', { name: 'This year' }));

            expect(inertiaMock.router.get).toHaveBeenCalledOnce();
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/dashboard',
                { period: 'this_year' },
                VISIT_OPTIONS,
            );
            // The select answers at once, before the server does.
            expect(
                screen.getByRole('combobox', { name: 'Period' }),
            ).toHaveTextContent('This year');
        });

        it('reloads only the period figures: restock, recent sales and alerts stay', async () => {
            const user = userEvent.setup();

            render(<Dashboard {...dashboardProps()} />);

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(
                screen.getByRole('option', { name: 'This month' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/dashboard',
                { period: 'this_month' },
                expect.objectContaining({
                    only: ['period', 'stats', 'top_products'],
                }),
            );
        });

        it('omits the default period from the query', async () => {
            const user = userEvent.setup();

            render(
                <Dashboard {...dashboardProps({ period: 'last_7_days' })} />,
            );

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(
                screen.getByRole('option', { name: 'Last 30 days' }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/dashboard',
                {},
                VISIT_OPTIONS,
            );
        });

        it('adopts a period that did not come from the select (back button)', () => {
            const { rerender } = render(<Dashboard {...dashboardProps()} />);

            rerender(
                <Dashboard {...dashboardProps({ period: 'last_7_days' })} />,
            );

            expect(
                screen.getByRole('combobox', { name: 'Period' }),
            ).toHaveTextContent('Last 7 days');
        });
    });

    describe('Record sale', () => {
        it('prefetches the sales page under the sales tag', () => {
            render(<Dashboard {...dashboardProps()} />);

            const link = screen.getByRole('link', { name: 'Record sale' });

            expect(link).toHaveAttribute('data-prefetch', 'true');
            expect(link).toHaveAttribute('data-cache-tags', 'sales');
        });

        it('links to the sales page with the form open', () => {
            render(<Dashboard {...dashboardProps()} />);

            expect(
                screen.getByRole('link', { name: 'Record sale' }),
            ).toHaveAttribute('href', '/sales?record=1');
        });
    });

    describe('empty states', () => {
        it('explains an empty period and an empty log', () => {
            render(
                <Dashboard
                    {...dashboardProps({
                        stats: {
                            ...dashboardStats,
                            revenue: '0.0000',
                            units_sold: 0,
                            sales_count: 0,
                            products_sold_count: 0,
                        },
                        top_products: { by_units: [], by_revenue: [] },
                        recent_sales: [],
                    })}
                />,
            );

            expect(screen.getByText(/No sales in this period/)).toBeVisible();
            expect(screen.getByText(/No sales recorded yet/)).toBeVisible();
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
            expect(
                screen.queryByRole('list', { name: 'Top selling products' }),
            ).not.toBeInTheDocument();
        });

        it('says nothing needs restock', () => {
            render(
                <Dashboard
                    {...dashboardProps({
                        needs_restock: [],
                        stats: {
                            ...dashboardStats,
                            needs_restock_count: 0,
                            low_stock_count: 0,
                            out_of_stock_count: 0,
                        },
                    })}
                />,
            );

            expect(
                screen.getByText(
                    'All materials are above their reorder level.',
                ),
            ).toBeVisible();
        });
    });
});
