import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrdersTable } from '@/components/orders/orders-table';
import { resetInertiaMock } from '@/test/inertia';
import type { Order, OrderLine } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function line(
    id: number,
    name: string,
    quantity: number,
    deleted = false,
): OrderLine {
    return {
        id,
        quantity,
        product: {
            id,
            sku: `PRD-${id}`,
            name,
            status: deleted ? 'archived' : 'active',
            deleted,
        },
    };
}

const lina: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: 'Deliver before the spring show',
    lines: [line(1, 'Denim Jacket', 50), line(5, 'Denim Jeans 5-pocket', 30)],
    units: 80,
};

const fiveProducts: Order = {
    ...lina,
    id: 8,
    client: 'Maison Rive',
    lines: [
        line(1, 'Denim Jacket', 10),
        line(2, 'Classic White T-shirt', 20),
        line(3, 'Canvas Low Sneaker', 30),
        line(4, 'Leather Tote', 40),
        line(7, 'Leather Belt', 50),
    ],
    units: 150,
};

function renderTable(orders: Order[]) {
    const user = userEvent.setup();
    const onDelete = vi.fn();

    render(<OrdersTable orders={orders} onDelete={onDelete} />);

    return { user, onDelete };
}

/** The table (>= md); the cards below repeat its content, so scope queries. */
function table() {
    return within(screen.getByRole('table', { name: 'Orders' }));
}

describe('<OrdersTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the first three products of an order and "+ 2 more"', () => {
        renderTable([fiveProducts]);

        expect(
            table()
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual([
            '10 × Denim Jacket',
            '20 × Classic White T-shirt',
            '30 × Canvas Low Sneaker',
            '+ 2 more',
        ]);
        expect(table().queryByText(/Leather Tote/)).not.toBeInTheDocument();
    });

    it('links an active product and leaves a deleted one as plain text', () => {
        renderTable([
            {
                ...lina,
                lines: [
                    line(1, 'Denim Jacket', 50),
                    line(9, 'Old Parka', 5, true),
                ],
            },
        ]);

        expect(
            table().getByRole('link', { name: 'Denim Jacket' }),
        ).toHaveAttribute('href', '/products/1');
        expect(table().getByText(/Old Parka/)).toBeVisible();
        expect(
            table().queryByRole('link', { name: 'Old Parka' }),
        ).not.toBeInTheDocument();
    });

    it('shows each order as a card below md, with Edit and Delete', async () => {
        const { user, onDelete } = renderTable([lina]);

        const card = within(screen.getByRole('list', { name: 'Orders' }));

        expect(card.getByText('Boutique Lina')).toBeVisible();
        expect(
            card.getAllByRole('listitem').map((item) => item.textContent),
        ).toEqual(
            expect.arrayContaining([
                '50 × Denim Jacket',
                '30 × Denim Jeans 5-pocket',
            ]),
        );
        expect(card.getByText('15 Oct 2026')).toBeVisible();
        expect(card.getByText('Deliver before the spring show')).toBeVisible();
        expect(card.getByRole('link', { name: /^Edit/ })).toHaveAttribute(
            'href',
            '/orders/7/edit',
        );

        await user.click(card.getByRole('button', { name: /^Delete/ }));

        expect(onDelete).toHaveBeenCalledExactlyOnceWith(lina);
    });
});
