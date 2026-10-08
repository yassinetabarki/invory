import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { RecentSalesCard } from '@/components/dashboard/recent-sales-card';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import { recentSales } from '@/test/fixtures/sales';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<RecentSalesCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('table (>= md)', () => {
        it('has the mockup columns', () => {
            render(<RecentSalesCard sales={recentSales} />);

            const table = screen.getByRole('table');

            expect(
                within(table)
                    .getAllByRole('columnheader')
                    .map((header) => header.textContent),
            ).toEqual([
                'Date',
                'Product',
                'Qty',
                'Unit price',
                'Total',
                'Note',
            ]);
        });

        it('renders one row per sale with formatted date and money', () => {
            render(<RecentSalesCard sales={recentSales} />);

            const rows = within(screen.getByRole('table')).getAllByRole('row');

            // header + 5 sales
            expect(rows).toHaveLength(6);

            const jeans = rows[1];

            expect(within(jeans).getByText('7 Oct 2026')).toBeInTheDocument();
            expect(
                within(jeans).getByRole('link', {
                    name: 'Denim Jeans 5-pocket',
                }),
            ).toHaveAttribute('href', '/products/5');
            expect(within(jeans).getByText('6')).toBeInTheDocument();
            expect(within(jeans).getByText('€79.00')).toBeInTheDocument();
            expect(within(jeans).getByText('€474.00')).toBeInTheDocument();
            expect(
                within(jeans).getByText('Boutique Lyon'),
            ).toBeInTheDocument();
        });

        it('shows the total in bold', () => {
            render(<RecentSalesCard sales={recentSales} />);

            const total = within(
                within(screen.getByRole('table')).getAllByRole('row')[2],
            ).getByText('€480.00');

            expect(total.closest('td')).toHaveClass('font-bold');
        });

        it('uses the configured currency', () => {
            setPageProps({
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });

            render(<RecentSalesCard sales={recentSales} />);

            expect(
                within(screen.getByRole('table')).getAllByText(/TND/).length,
            ).toBeGreaterThan(0);
            expect(screen.getByRole('table')).not.toHaveTextContent('€');
        });
    });

    describe('cards (< md)', () => {
        it('summarises each sale in a stacked card', () => {
            render(<RecentSalesCard sales={recentSales} />);

            const cards = within(
                screen.getByRole('list', { name: 'Recent sales' }),
            ).getAllByRole('listitem');

            expect(cards).toHaveLength(5);
            expect(
                within(cards[0]).getByRole('link', {
                    name: 'Denim Jeans 5-pocket',
                }),
            ).toHaveAttribute('href', '/products/5');
            expect(within(cards[0]).getByText('€474.00')).toBeInTheDocument();
            expect(cards[0]).toHaveTextContent(
                '7 Oct 2026 · 6 × €79.00 · Boutique Lyon',
            );
        });

        it('leaves out the note separator when a sale has no note', () => {
            render(
                <RecentSalesCard sales={[{ ...recentSales[0], note: null }]} />,
            );

            const card = within(
                screen.getByRole('list', { name: 'Recent sales' }),
            ).getByRole('listitem');

            expect(card).toHaveTextContent('7 Oct 2026 · 6 × €79.00');
            expect(card).not.toHaveTextContent('Boutique Lyon');
            expect(card.textContent?.trim().endsWith('·')).toBe(false);
        });
    });

    it('links View all sales to the sales log', () => {
        render(<RecentSalesCard sales={recentSales} />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'Recent sales' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'View all sales' }),
        ).toHaveAttribute('href', '/sales');
    });

    it('explains an empty log instead of showing an empty table', () => {
        render(<RecentSalesCard sales={[]} />);

        expect(screen.getByText(/No sales recorded yet/)).toBeVisible();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'View all sales' }),
        ).toBeVisible();
    });
});
