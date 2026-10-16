import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { MaterialsMoveTable } from '@/components/orders/materials-move-table';
import type { MaterialMoveRow, MovePreview } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function row(
    id: number,
    name: string,
    unit: string,
    figures: Pick<MaterialMoveRow, 'quantity' | 'in_stock' | 'after'> &
        Partial<MaterialMoveRow>,
): MaterialMoveRow {
    return {
        material: { id, sku: `MAT-${id}`, name, unit },
        short_by: null,
        not_returned: null,
        ...figures,
    };
}

const denim = (after: string | null = '190.6000') =>
    row(1, 'Denim 12oz Indigo', 'm', {
        quantity: '59.4000',
        in_stock: '250.0000',
        after,
    });
const button = row(2, 'Metal shank button 17mm', 'pc', {
    quantity: '240.0000',
    in_stock: '900.0000',
    after: '660.0000',
});
const thread = row(3, 'Polyester thread', 'm', {
    quantity: '3780.0000',
    in_stock: '20000.0000',
    after: '16220.0000',
});
const label = row(4, 'Woven brand label', 'pc', {
    quantity: '30.0000',
    in_stock: '400.0000',
    after: '370.0000',
});

function cutting(overrides: Partial<MovePreview> = {}): MovePreview {
    return {
        move: 'start_cutting',
        units: 30,
        rows: [denim(), button, thread, label],
        has_composition: true,
        nothing_taken: false,
        max_cuttable: 112,
        short_message: null,
        ...overrides,
    };
}

function returning(overrides: Partial<MovePreview> = {}): MovePreview {
    return {
        move: 'back_to_start',
        units: 5,
        rows: [],
        has_composition: true,
        nothing_taken: false,
        max_cuttable: null,
        short_message: null,
        ...overrides,
    };
}

function show(preview: MovePreview) {
    render(
        <MaterialsMoveTable
            state={{ status: 'ready', preview }}
            productName="Denim Jacket"
        />,
    );
}

/** A row's cells as the person reads them. */
function cells(name: string): string[] {
    const found = screen.getByRole('row', { name: new RegExp(name) });

    return [
        within(found).getByRole('rowheader'),
        ...within(found).getAllByRole('cell'),
    ].map((cell) => cell.textContent ?? '');
}

describe('<MaterialsMoveTable>', () => {
    it('lists what Start cutting takes, as the server sent it, with the waste note', () => {
        show(cutting());

        const table = screen.getByRole('table', {
            name: 'Materials taken out of stock',
        });

        expect(
            within(table)
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Material', 'Needed', 'In stock', 'Left after']);
        expect(cells('Denim 12oz Indigo')).toEqual([
            'Denim 12oz Indigo',
            '59.4 m',
            '250 m',
            '190.6 m',
        ]);
        expect(cells('Polyester thread')).toEqual([
            'Polyester thread',
            '3,780 m',
            '20,000 m',
            '16,220 m',
        ]);
        expect(cells('Woven brand label')).toEqual([
            'Woven brand label',
            '30 pc',
            '400 pc',
            '370 pc',
        ]);
        expect(
            screen.getByText('Waste included. Stock on hand allows 112 units.'),
        ).toBeInTheDocument();
    });

    it('says "1 unit" when the stock allows one', () => {
        show(cutting({ max_cuttable: 1 }));

        expect(
            screen.getByText('Waste included. Stock on hand allows 1 unit.'),
        ).toBeInTheDocument();
    });

    it("marks every short row with words and an icon, and shows the server's message as sent", () => {
        const message =
            'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.';

        show(
            cutting({
                units: 20,
                rows: [
                    denim('18.4000'),
                    row(2, 'Metal shank button 17mm', 'pc', {
                        quantity: '160.0000',
                        in_stock: '100.0000',
                        after: null,
                        short_by: '60.0000',
                    }),
                    row(3, 'Polyester thread', 'm', {
                        quantity: '2520.0000',
                        in_stock: '2000.0000',
                        after: null,
                        short_by: '520.0000',
                    }),
                ],
                max_cuttable: 12,
                short_message: message,
            }),
        );

        const buttonRow = screen.getByRole('row', {
            name: /Metal shank button 17mm/,
        });

        expect(within(buttonRow).getByText('Short by 60 pc')).toBeVisible();
        expect(buttonRow.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );
        expect(screen.getByText('Short by 520 m')).toBeInTheDocument();
        expect(screen.getByText(message)).toBeInTheDocument();
        // The row that is fine still shows what is left.
        expect(cells('Denim 12oz Indigo')[3]).toBe('18.4 m');
    });

    it('shows the "can\'t cut any" message as sent', () => {
        const message =
            "Not enough stock to cut 20 units. You can't cut any now: restock Metal shank button 17mm first.";

        show(cutting({ max_cuttable: 0, short_message: message }));

        expect(screen.getByText(message)).toBeInTheDocument();
    });

    it('says so when the product has no composition, with no table', () => {
        show(cutting({ rows: [], has_composition: false, max_cuttable: null }));

        expect(
            screen.getByText(
                'Denim Jacket has no composition, so no material is taken out of stock.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
    });

    it('lists what Move back to To start returns, and why a material gets nothing', () => {
        show(
            returning({
                rows: [
                    row(1, 'Denim 12oz Indigo', 'm', {
                        quantity: '9.9000',
                        in_stock: '190.6000',
                        after: '200.5000',
                    }),
                    row(2, 'Metal shank button 17mm', 'pc', {
                        quantity: '40.0000',
                        in_stock: '660.0000',
                        after: null,
                        not_returned: 'deleted',
                    }),
                    row(3, 'Polyester thread', 'm', {
                        quantity: '630.0000',
                        in_stock: '16220.0000',
                        after: null,
                        not_returned: 'unit_changed',
                    }),
                ],
            }),
        );

        expect(
            within(
                screen.getByRole('table', {
                    name: 'Materials returned to stock',
                }),
            )
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Material', 'Returned', 'In stock', 'After return']);
        expect(cells('Denim 12oz Indigo')).toEqual([
            'Denim 12oz Indigo',
            '9.9 m',
            '190.6 m',
            '200.5 m',
        ]);
        expect(cells('Metal shank button 17mm')[3]).toBe(
            'Deleted, not returned',
        );
        expect(cells('Polyester thread')[3]).toBe('Unit changed, not returned');
        expect(screen.queryByText(/Waste included/)).not.toBeInTheDocument();
    });

    it('says so when no material was taken for the units moved back', () => {
        show(returning({ nothing_taken: true }));

        expect(
            screen.getByText(
                'No material was taken out of stock for these units, so none is returned.',
            ),
        ).toBeInTheDocument();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
    });

    it('shows a loading line, and a retry when the materials could not load', async () => {
        const retry = vi.fn();
        const { rerender } = render(
            <MaterialsMoveTable
                state={{ status: 'loading' }}
                productName="Denim Jacket"
            />,
        );

        expect(screen.getByText('Loading materials…')).toBeInTheDocument();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();

        rerender(
            <MaterialsMoveTable
                state={{ status: 'failed', retry }}
                productName="Denim Jacket"
            />,
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't load the materials for this move.",
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Try again' }),
        );

        expect(retry).toHaveBeenCalledOnce();
    });
});
