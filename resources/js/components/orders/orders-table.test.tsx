import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrdersTable } from '@/components/orders/orders-table';
import {
    sizedJacketLine,
    sizeIds,
    withOneSize,
    black,
    indigo,
    white,
} from '@/test/fixtures';
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
    counts: { delivered?: number; cancelled?: number } = {},
    deleted = false,
): OrderLine {
    const { delivered = 0, cancelled = 0 } = counts;

    return withOneSize({
        id,
        quantity,
        to_deliver: quantity - delivered - cancelled,
        delivered,
        cancelled,
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
        to_deliver: quantity,
        sizes: counts.map(([label, units]) => ({
            colour: white,
            size: { id: sizeIds[label], label },
            quantity: units,
            to_deliver: units,
            delivered: 0,
            cancelled: 0,
        })),
    };
}

const lina: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: 'Deliver before the spring show',
    lines: [
        line(1, 'Denim Jacket', 50, { delivered: 5 }),
        line(5, 'Denim Jeans 5-pocket', 30, { cancelled: 5 }),
    ],
    units: 80,
    to_deliver: 70,
    delivered: 5,
    cancelled: 5,
    status: 'in_progress',
    status_label: 'In progress',
    client_id: 3,
    client_details: null,
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

    it('shows the columns of INV-14 and a row with its figures: the date and the note under the client, "5 cancelled" under Ordered (AC-5)', () => {
        renderTable([lina]);

        expect(
            table()
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual([
            'Client',
            'Products',
            'Ordered',
            'To deliver',
            'Delivered',
            'Status',
            'Actions',
        ]);

        const cells = within(
            table().getByRole('row', { name: /Boutique Lina/ }),
        ).getAllByRole('cell');

        expect(
            within(cells[0]).getByRole('link', { name: 'Boutique Lina' }),
        ).toHaveAttribute('href', '/orders/7');
        expect(cells[0]).toHaveTextContent('15 Oct 2026');
        expect(
            within(cells[0]).getByText('Deliver before the spring show'),
        ).toHaveAttribute('title', 'Deliver before the spring show');
        expect(
            within(cells[1])
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual([
            '50 × Denim Jacket (White 50)',
            '30 × Denim Jeans 5-pocket (White 30)',
        ]);
        expect(cells.slice(2, 5).map((cell) => cell.textContent)).toEqual([
            '805 cancelled',
            '70',
            '5',
        ]);
        expect(within(cells[2]).getByText('5 cancelled')).toHaveClass(
            'text-muted-foreground',
        );
        expect(cells[5]).toHaveTextContent('In progress');
        expect(
            within(cells[6]).getByRole('link', {
                name: 'Edit order of Boutique Lina on 15 Oct 2026',
            }),
        ).toHaveAttribute('href', '/orders/7/edit');
        expect(
            within(cells[6]).getByRole('button', {
                name: 'Delete order of Boutique Lina on 15 Oct 2026',
            }),
        ).toBeInTheDocument();
        expect(
            table().queryByText(/To start|In cutting|Ready to ship|Shipped/),
        ).not.toBeInTheDocument();
    });

    it.each<[OrderProgress, string]>([
        ['not_started', 'Not started'],
        ['in_progress', 'In progress'],
        ['delivered', 'Delivered'],
        ['cancelled', 'Cancelled'],
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
            '10 × Denim Jacket (White 10)',
            '20 × Classic White T-shirt (White 20)',
            '30 × Canvas Low Sneaker (White 30)',
            '+ 2 more',
        ]);
        expect(table().queryByText(/Leather Tote/)).not.toBeInTheDocument();
    });

    it.each<[string, OrderLine, string]>([
        [
            'S 10 · M 20 · L 15 · XL 5',
            sizedJacketLine,
            '50 × Denim Jacket (White S 10 · M 20 · L 15 · XL 5)',
        ],
        [
            'a size with 0 units left out',
            sizedLine([
                ['S', 10],
                ['M', 0],
                ['XL', 5],
            ]),
            '15 × Denim Jacket (White S 10 · XL 5)',
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
                    colour: white,
                    size: { id: Number(id), label: String(label) },
                    quantity: Number(units),
                    to_deliver: Number(units),
                    delivered: 0,
                    cancelled: 0,
                })),
                quantity: 12,
                to_deliver: 12,
            },
            '12 × Canvas Low Sneaker (White 36: 4 · 37: 8)',
        ],
        [
            'a line counted as one size',
            { ...line(2, 'Leather Tote', 12), size_mode: 'one_size' },
            '12 × Leather Tote (White 12)',
        ],
        [
            'a line recorded before sizes',
            line(1, 'Denim Jacket', 50),
            '50 × Denim Jacket (White 50)',
        ],
        [
            'two colours, each named once before its sizes (INV-13 AC-5)',
            {
                ...sizedLine([['M', 1]]),
                quantity: 4,
                sizes: (
                    [
                        [black, 'M', 1],
                        [indigo, 'S', 1],
                        [indigo, 'M', 2],
                    ] as const
                ).map(([colour, label, units]) => ({
                    colour,
                    size: { id: sizeIds[label], label },
                    quantity: units,
                    to_deliver: units,
                    delivered: 0,
                    cancelled: 0,
                })),
            },
            '4 × Denim Jacket (Black M 1 · Indigo S 1 · M 2)',
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

    it('shows each order as a card with its status, date, "5 cancelled", counts, note and labelled actions (AC-5)', async () => {
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
                '50 × Denim Jacket (White 50)',
                '30 × Denim Jeans 5-pocket (White 30)',
            ]),
        );
        expect(card.getByText('To deliver 70 · Delivered 5')).toBeVisible();
        expect(card.getByText('15 Oct 2026 · 5 cancelled')).toBeVisible();
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
