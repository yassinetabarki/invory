import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SalesTable } from '@/components/sales/sales-table';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import { recentSales, saleWithoutNote } from '@/test/fixtures/sales';
import type { Sale } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderTable(sales: Sale[] = recentSales) {
    const onEdit = vi.fn();
    const onDelete = vi.fn();

    render(<SalesTable sales={sales} onEdit={onEdit} onDelete={onDelete} />);

    return { onEdit, onDelete };
}

describe('<SalesTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('table (>= md)', () => {
        it('has the mockup columns', () => {
            renderTable();

            const table = screen.getByRole('table', { name: 'Sales' });

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
                'Actions',
            ]);
        });

        it('renders a row per sale with date, product, SKU, price, total and note', () => {
            renderTable();

            const rows = within(screen.getByRole('table')).getAllByRole('row');

            expect(rows).toHaveLength(6);

            const jeans = rows[1];

            expect(within(jeans).getByText('7 Oct 2026')).toBeInTheDocument();
            expect(
                within(jeans).getByRole('link', {
                    name: 'Denim Jeans 5-pocket',
                }),
            ).toHaveAttribute('href', '/products/5');
            expect(within(jeans).getByText('PRD-JNS-005')).toHaveClass(
                'font-mono',
            );
            expect(within(jeans).getByText('6')).toBeInTheDocument();
            expect(within(jeans).getByText('€79.00')).toBeInTheDocument();
            expect(within(jeans).getByText('€474.00')).toBeInTheDocument();
            expect(
                within(jeans).getByText('Boutique Lyon'),
            ).toBeInTheDocument();
        });

        it('shows the server total in bold, not a recomputed one', () => {
            // quantity × price would be 120.00; the row must show what the server sent.
            renderTable([{ ...recentSales[1], total: '999.0000' }]);

            const row = within(screen.getByRole('table')).getAllByRole(
                'row',
            )[1];

            expect(within(row).getByText('€999.00').closest('td')).toHaveClass(
                'font-bold',
            );
            expect(within(row).queryByText('€480.00')).not.toBeInTheDocument();
        });

        it('shows an em dash when a sale has no note', () => {
            renderTable([saleWithoutNote]);

            const row = within(screen.getByRole('table')).getAllByRole(
                'row',
            )[1];

            expect(within(row).getByText('—')).toBeInTheDocument();
        });

        it('names the product and date on Edit and Delete', () => {
            renderTable();

            const table = screen.getByRole('table');

            expect(
                within(table).getByRole('button', {
                    name: 'Edit sale of Denim Jacket on 5 Oct 2026',
                }),
            ).toBeInTheDocument();
            expect(
                within(table).getByRole('button', {
                    name: 'Delete sale of Denim Jacket on 5 Oct 2026',
                }),
            ).toBeInTheDocument();
        });

        it('hands the sale to onEdit and onDelete', async () => {
            const user = userEvent.setup();
            const { onEdit, onDelete } = renderTable();

            const table = screen.getByRole('table');

            await user.click(
                within(table).getByRole('button', {
                    name: 'Edit sale of Leather Tote on 6 Oct 2026',
                }),
            );

            expect(onEdit).toHaveBeenCalledExactlyOnceWith(recentSales[2]);

            await user.click(
                within(table).getByRole('button', {
                    name: 'Delete sale of Leather Belt on 5 Oct 2026',
                }),
            );

            expect(onDelete).toHaveBeenCalledExactlyOnceWith(recentSales[4]);
        });

        it('formats money in the configured currency', () => {
            setPageProps({
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });
            renderTable();

            const table = screen.getByRole('table');

            expect(within(table).getAllByText(/TND/).length).toBeGreaterThan(0);
            expect(table).not.toHaveTextContent('€');
        });
    });

    describe('cards (< md)', () => {
        function cards(): HTMLElement[] {
            return within(
                screen.getByRole('list', { name: 'Sales' }),
            ).getAllByRole('listitem');
        }

        it('renders a stacked card per sale', () => {
            renderTable();

            expect(cards()).toHaveLength(5);

            const jeans = cards()[0];

            expect(
                within(jeans).getByRole('link', {
                    name: 'Denim Jeans 5-pocket',
                }),
            ).toHaveAttribute('href', '/products/5');
            expect(within(jeans).getByText('PRD-JNS-005')).toBeInTheDocument();
            expect(within(jeans).getByText('€474.00')).toBeInTheDocument();
            expect(jeans).toHaveTextContent(
                '7 Oct 2026 · 6 × €79.00 · Boutique Lyon',
            );
        });

        it('has Edit and Delete buttons with the same descriptive names', async () => {
            const user = userEvent.setup();
            const { onEdit, onDelete } = renderTable();

            await user.click(
                within(cards()[3]).getByRole('button', {
                    name: 'Edit sale of Denim Jacket on 5 Oct 2026',
                }),
            );
            await user.click(
                within(cards()[3]).getByRole('button', {
                    name: 'Delete sale of Denim Jacket on 5 Oct 2026',
                }),
            );

            expect(onEdit).toHaveBeenCalledExactlyOnceWith(recentSales[3]);
            expect(onDelete).toHaveBeenCalledExactlyOnceWith(recentSales[3]);
            expect(
                within(cards()[3]).getByRole('button', {
                    name: /^Edit sale/,
                }),
            ).toHaveTextContent('Edit');
        });

        it('drops the note part when there is no note', () => {
            renderTable([saleWithoutNote]);

            expect(cards()[0]).toHaveTextContent('21 Sep 2026 · 1 × €69.00');
            expect(cards()[0]).not.toHaveTextContent('· null');
        });
    });
});
