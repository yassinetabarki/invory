import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { RecentSalesCard } from '@/components/products/recent-sales-card';
import {
    denimRecentSales,
    denimSalesLast30Days,
    denimSaleWithoutNote,
} from '@/test/fixtures/products';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Decimal, Sale } from '@/types/domain';

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
    last30Days: { units_sold: number; revenue: Decimal } = denimSalesLast30Days,
) {
    return render(
        <RecentSalesCard
            sales={sales}
            last30Days={last30Days}
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

    it.each([
        [
            '38 units',
            denimRecentSales,
            { units_sold: 38, revenue: '3382.0000' },
            '38 units sold in the last 30 days · €3,382.00',
        ],
        [
            '1 unit',
            denimRecentSales,
            { units_sold: 1, revenue: '89.0000' },
            '1 unit sold in the last 30 days · €89.00',
        ],
        [
            'older sales only',
            denimRecentSales,
            { units_sold: 0, revenue: '0.0000' },
            'No sales in the last 30 days.',
        ],
        ['never sold', [], { units_sold: 0, revenue: '0.0000' }, null],
    ] satisfies [
        string,
        Sale[],
        { units_sold: number; revenue: Decimal },
        string | null,
    ][])(
        'reads the 30-day figure above the list for %s',
        (_, sales, last30Days, line) => {
            renderCard(sales, RECORD, last30Days);

            if (line === null) {
                // Neither line: only the empty text stays.
                expect(screen.queryByText(/in the last 30 days/)).toBeNull();
                expect(
                    screen.getByText('No sales recorded for this product yet.'),
                ).toBeInTheDocument();

                return;
            }

            const text = screen.getByText(/in the last 30 days/);

            expect(text).toHaveTextContent(line);

            // Plain text under the heading, above the list.
            expect(
                screen
                    .getByRole('heading', { name: 'Recent sales' })
                    .compareDocumentPosition(text) &
                    Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();
            expect(
                text.compareDocumentPosition(
                    screen.getByRole('table', { name: 'Recent sales' }),
                ) & Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();
        },
    );

    describe('with no sales yet', () => {
        it('says so and offers to record one', () => {
            renderCard([], RECORD, { units_sold: 0, revenue: '0.0000' });

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
                    last30Days={{ units_sold: 0, revenue: '0.0000' }}
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
