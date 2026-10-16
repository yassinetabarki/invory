import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersShow from '@/pages/orders/show';
import { resetInertiaMock } from '@/test/inertia';
import type { OrderLine, OrderProduct, OrderShowProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const jacket: OrderProduct = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    status: 'active',
    deleted: false,
};
const jeans: OrderProduct = {
    id: 5,
    sku: 'PRD-JNS-005',
    name: 'Denim Jeans 5-pocket',
    status: 'active',
    deleted: false,
};

function notStarted(
    id: number,
    product: OrderProduct,
    quantity: number,
): OrderLine {
    return {
        id,
        product,
        quantity,
        to_start: quantity,
        in_cutting: 0,
        ready_to_ship: 0,
        shipped: 0,
        started: 0,
        status: 'not_started',
        status_label: 'Not started',
    };
}

const props: OrderShowProps = {
    order: {
        id: 7,
        client: 'Boutique Lina',
        ordered_on: '2026-10-15',
        note: 'Deliver before 30 Oct, in 2 boxes',
        lines: [notStarted(70, jacket, 50), notStarted(71, jeans, 30)],
        units: 80,
        in_cutting: 0,
        ready_to_ship: 0,
        shipped: 0,
        status: 'not_started',
        status_label: 'Not started',
        delete_refusal: null,
    },
};

describe('orders/show page', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it("shows the order, its note and each line's four figures", () => {
        render(<OrdersShow {...props} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Boutique Lina' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Ordered 15 Oct 2026 · 2 products · 80 units'),
        ).toBeInTheDocument();
        // The order's status and each line's own.
        expect(screen.getAllByText('Not started')).toHaveLength(3);
        expect(screen.getByText('0 of 80 shipped')).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Note' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Deliver before 30 Oct, in 2 boxes'),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
            'href',
            '/orders/7/edit',
        );
        expect(
            screen.getByRole('button', { name: 'Delete' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { level: 2, name: 'Products' }),
        ).toBeInTheDocument();

        const jackets = within(
            screen.getByRole('region', {
                name: 'Denim Jacket · PRD-JKT-001 · 50 units',
            }),
        );

        expect(
            jackets
                .getAllByRole('listitem')
                // Caption and figure only: the buttons come after them.
                .map((item) => item.querySelector('p')?.textContent),
        ).toEqual([
            'To start 50 units',
            'In cutting 0 units',
            'Ready to ship 0 units',
            'Shipped 0 units',
        ]);
        expect(jackets.getByText('0 of 50 shipped')).toBeInTheDocument();
        expect(jackets.getByText('Not started')).toBeInTheDocument();

        const jeansBlock = within(
            screen.getByRole('region', {
                name: 'Denim Jeans 5-pocket · PRD-JNS-005 · 30 units',
            }),
        );

        expect(jeansBlock.getByText('0 of 30 shipped')).toBeInTheDocument();
    });

    it('leaves out the Note section when the client left none', () => {
        render(<OrdersShow order={{ ...props.order, note: null }} />);

        expect(
            screen.queryByRole('heading', { name: 'Note' }),
        ).not.toBeInTheDocument();
    });
});
