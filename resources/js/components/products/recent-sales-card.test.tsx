import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { RecentSalesCard } from '@/components/products/recent-sales-card';
import {
    denimRecentSales,
    denimSaleWithoutNote,
} from '@/test/fixtures/products';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Sale } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const VIEW_ALL = '/sales?product_id=1';
const RECORD = '/sales?record=1&record_product_id=1';

function renderCard(
    sales: Sale[] = denimRecentSales,
    recordHref: string | null = RECORD,
) {
    return render(
        <RecentSalesCard
            sales={sales}
            viewAllHref={VIEW_ALL}
            recordHref={recordHref}
        />,
    );
}

describe('<RecentSalesCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists the newest sales with date, quantity, unit price, total and note', () => {
        renderCard();

        const table = screen.getByRole('table', { name: 'Recent sales' });

        expect(
            within(table)
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Date', 'Qty', 'Unit price', 'Total', 'Note']);

        const rows = within(table).getAllByRole('row').slice(1);

        expect(rows).toHaveLength(5);

        const first = within(rows[0]);

        expect(first.getByText('5 Oct 2026')).toBeInTheDocument();
        expect(first.getByText('4')).toBeInTheDocument();
        expect(first.getByText('€89.00')).toBeInTheDocument();
        expect(first.getByText('€356.00')).toBeInTheDocument();
        expect(first.getByText('Web shop')).toBeInTheDocument();

        expect(within(rows[1]).getByText('1 Oct 2026')).toBeInTheDocument();
        expect(within(rows[1]).getByText('€445.00')).toBeInTheDocument();
        expect(within(rows[2]).getByText('26 Sep 2026')).toBeInTheDocument();
        expect(within(rows[2]).getByText('€534.00')).toBeInTheDocument();
    });

    it('formats money with the installation currency', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        renderCard();

        const table = screen.getByRole('table', { name: 'Recent sales' });

        expect(within(table).getAllByText('TND 89.000')).toHaveLength(5);
        expect(within(table).getByText('TND 356.000')).toBeInTheDocument();
    });

    it('shows an em dash for a sale without a note', () => {
        renderCard([denimSaleWithoutNote]);

        const table = screen.getByRole('table', { name: 'Recent sales' });

        expect(within(table).getByText('—')).toBeInTheDocument();
        expect(within(table).getByText('20 Sep 2026')).toBeInTheDocument();
    });

    it('links "View all" to the sales of this product', () => {
        renderCard();

        expect(screen.getByRole('link', { name: 'View all' })).toHaveAttribute(
            'href',
            VIEW_ALL,
        );
    });

    it('has a stacked card per sale below md', () => {
        renderCard();

        const cards = within(
            screen.getByRole('list', { name: 'Recent sales' }),
        ).getAllByRole('listitem');

        expect(cards).toHaveLength(5);
        expect(cards[0]).toHaveTextContent('5 Oct 2026');
        expect(cards[0]).toHaveTextContent('€356.00');
        expect(cards[0]).toHaveTextContent('4 × €89.00');
        expect(cards[0]).toHaveTextContent('Web shop');
    });

    describe('with no sales yet', () => {
        it('says so and offers to record one', () => {
            renderCard([]);

            expect(
                screen.getByText('No sales recorded for this product yet.'),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Record a sale' }),
            ).toHaveAttribute('href', RECORD);
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
            // The list of all sales is still one click away.
            expect(
                screen.getByRole('link', { name: 'View all' }),
            ).toBeInTheDocument();
        });

        it('does not offer to record a sale for a product that cannot be sold', () => {
            render(
                <RecentSalesCard
                    sales={[]}
                    viewAllHref={VIEW_ALL}
                    recordHref={null}
                />,
            );

            expect(
                screen.getByText('No sales recorded for this product yet.'),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('link', { name: 'Record a sale' }),
            ).not.toBeInTheDocument();
        });
    });
});
