import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrdersTable } from '@/components/orders/orders-table';
import { sizedJacketLine, sizeIds, withOneSize } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';
import type { Order, OrderLine, OrderProgress } from '@/types/domain';

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
    stages: { in_cutting?: number; ready_to_ship?: number } = {},
    deleted = false,
): OrderLine {
    const { in_cutting = 0, ready_to_ship = 0 } = stages;

    return withOneSize({
        id,
        quantity,
        to_start: quantity - in_cutting - ready_to_ship,
        in_cutting,
        ready_to_ship,
        shipped: 0,
        started: in_cutting + ready_to_ship,
        status: 'not_started',
        status_label: 'Not started',
        product: {
            id,
            sku: `PRD-${id}`,
            name,
            status: deleted ? 'archived' : 'active',
            deleted,
        },
    });
}

/** A Denim Jacket counted by size from [label, units] pairs; the units add up to the line. */
function sizedLine(counts: [keyof typeof sizeIds, number][]): OrderLine {
    const quantity = counts.reduce((sum, [, units]) => sum + units, 0);

    return {
        ...sizedJacketLine,
        quantity,
        to_start: quantity,
        sizes: counts.map(([label, units]) => ({
            size: { id: sizeIds[label], label },
            quantity: units,
            to_start: units,
            in_cutting: 0,
            ready_to_ship: 0,
            shipped: 0,
            started: 0,
        })),
    };
}

const lina: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: 'Deliver before the spring show',
    lines: [
        line(1, 'Denim Jacket', 50, { in_cutting: 20, ready_to_ship: 10 }),
        line(5, 'Denim Jeans 5-pocket', 30),
    ],
    units: 80,
    to_start: 50,
    in_cutting: 20,
    ready_to_ship: 10,
    shipped: 0,
    status: 'in_progress',
    status_label: 'In progress',
    delete_refusal: null,
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

/** The table (>= lg); the cards below repeat its content, so scope queries. */
function table() {
    return within(screen.getByRole('table', { name: 'Orders' }));
}

describe('<OrdersTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the stage columns and a row with its figures, the note under the client and its actions', () => {
        renderTable([lina]);

        expect(
            table()
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual([
            'Order date',
            'Client',
            'Products',
            'Ordered',
            'To start',
            'In cutting',
            'Ready to ship',
            'Shipped',
            'Status',
            'Actions',
        ]);
        expect(table().queryByText('Units')).not.toBeInTheDocument();
        expect(table().queryByText('Note')).not.toBeInTheDocument();

        const cells = within(
            table().getByRole('row', { name: /Boutique Lina/ }),
        ).getAllByRole('cell');

        expect(cells[0]).toHaveTextContent('15 Oct 2026');
        expect(
            within(cells[1]).getByRole('link', { name: 'Boutique Lina' }),
        ).toHaveAttribute('href', '/orders/7');
        expect(
            within(cells[1]).getByText('Deliver before the spring show'),
        ).toHaveAttribute('title', 'Deliver before the spring show');
        expect(
            within(cells[2])
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual(['50 × Denim Jacket', '30 × Denim Jeans 5-pocket']);
        expect(cells.slice(3, 8).map((cell) => cell.textContent)).toEqual([
            '80',
            '50',
            '20',
            '10',
            '0',
        ]);
        expect(cells[8]).toHaveTextContent('In progress');
        expect(
            within(cells[9]).getByRole('link', {
                name: 'Edit order of Boutique Lina on 15 Oct 2026',
            }),
        ).toHaveAttribute('href', '/orders/7/edit');
        expect(
            within(cells[9]).getByRole('button', {
                name: 'Delete order of Boutique Lina on 15 Oct 2026',
            }),
        ).toBeInTheDocument();
    });

    it.each<[OrderProgress, string]>([
        ['not_started', 'Not started'],
        ['in_progress', 'In progress'],
        ['shipped', 'Shipped'],
    ])(
        'shows the %s status as a word with an icon hidden from screen readers',
        (status, label) => {
            renderTable([{ ...lina, status, status_label: label }]);

            const badge = within(
                table().getByRole('row', { name: /Boutique Lina/ }),
            ).getByText(label, { selector: '[data-status]' });

            expect(badge).toHaveAttribute('data-status', status);
            expect(badge.querySelector('svg')).toHaveAttribute(
                'aria-hidden',
                'true',
            );
        },
    );

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

    it.each<[string, OrderLine, string]>([
        [
            'S 10 · M 20 · L 15 · XL 5',
            sizedJacketLine,
            '50 × Denim Jacket (S 10 · M 20 · L 15 · XL 5)',
        ],
        [
            'a size with 0 units left out',
            sizedLine([
                ['S', 10],
                ['M', 0],
                ['XL', 5],
            ]),
            '15 × Denim Jacket (S 10 · XL 5)',
        ],
        [
            'number sizes get a colon (S-60 F9)',
            {
                ...sizedLine([['S', 4]]),
                product: {
                    ...sizedJacketLine.product,
                    name: 'Canvas Low Sneaker',
                },
                sizes: [
                    ['36', 5, 4],
                    ['37', 6, 8],
                    ['38', 7, 0],
                ].map(([label, id, units]) => ({
                    size: { id: Number(id), label: String(label) },
                    quantity: Number(units),
                    to_start: Number(units),
                    in_cutting: 0,
                    ready_to_ship: 0,
                    shipped: 0,
                    started: 0,
                })),
                quantity: 12,
                to_start: 12,
            },
            '12 × Canvas Low Sneaker (36: 4 · 37: 8)',
        ],
        [
            'a line counted as one size',
            { ...line(2, 'Leather Tote', 12), size_mode: 'one_size' },
            '12 × Leather Tote',
        ],
        [
            'a line recorded before sizes',
            line(1, 'Denim Jacket', 50),
            '50 × Denim Jacket',
        ],
    ])('lists the products with their sizes: %s', (_case, orderLine, text) => {
        renderTable([{ ...lina, lines: [orderLine] }]);

        expect(
            table()
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual([text]);
    });

    it('links an active product and leaves a deleted one as plain text', () => {
        renderTable([
            {
                ...lina,
                lines: [
                    line(1, 'Denim Jacket', 50),
                    line(9, 'Old Parka', 5, {}, true),
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

    it('shows each order as a card with its status, stages, date, note and labelled actions', async () => {
        const { user, onDelete } = renderTable([lina]);

        const card = within(screen.getByRole('list', { name: 'Orders' }));
        const top = card
            .getByRole('link', { name: 'Boutique Lina' })
            .closest('div');

        expect(top).toContainElement(card.getByText('In progress'));
        expect(
            card.getAllByRole('listitem').map((item) => item.textContent),
        ).toEqual(
            expect.arrayContaining([
                '50 × Denim Jacket',
                '30 × Denim Jeans 5-pocket',
            ]),
        );
        expect(
            card.getByText(
                'To start 50 · In cutting 20 · Ready to ship 10 · Shipped 0',
            ),
        ).toBeVisible();
        expect(card.getByText('15 Oct 2026')).toBeVisible();
        expect(card.getByText('Deliver before the spring show')).toBeVisible();
        expect(card.queryByText(/80 units/)).not.toBeInTheDocument();
        expect(card.getByRole('link', { name: /^Edit/ })).toHaveAttribute(
            'href',
            '/orders/7/edit',
        );

        await user.click(card.getByRole('button', { name: /^Delete/ }));

        expect(onDelete).toHaveBeenCalledExactlyOnceWith(lina);
    });

    it('swaps the table for the cards below 1024 px', () => {
        renderTable([lina]);

        const wrapper = screen
            .getByRole('table', { name: 'Orders' })
            .closest('div.hidden');

        expect(wrapper).toHaveClass('hidden', 'lg:block');
        expect(screen.getByRole('list', { name: 'Orders' })).toHaveClass(
            'lg:hidden',
        );
    });
});
