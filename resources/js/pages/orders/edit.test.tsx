import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersEdit from '@/pages/orders/edit';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { OrderEditProps, OrderProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

// Denim Jacket was deleted after the order was recorded: still offered on this order.
const jacket: OrderProduct = {
    id: 1,
    name: 'Denim Jacket',
    sku: 'PRD-JKT-001',
    status: 'archived',
    deleted: true,
};
const jeans: OrderProduct = {
    id: 5,
    name: 'Denim Jeans 5-pocket',
    sku: 'PRD-JNS-005',
    status: 'active',
    deleted: false,
};

const props: OrderEditProps = {
    order: {
        id: 7,
        client: 'Boutique Lina',
        ordered_on: '2026-10-15',
        note: null,
        lines: [
            { id: 70, product: jacket, quantity: 50 },
            { id: 71, product: jeans, quantity: 30 },
        ],
        units: 80,
    },
    products: [jacket, jeans],
};

describe('orders/edit page', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('opens "Edit order" with the order filled in and a deleted product still editable', async () => {
        const user = userEvent.setup();

        render(<OrdersEdit {...props} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Edit order' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Client')).toHaveValue('Boutique Lina');
        expect(screen.getByLabelText('Order date')).toHaveValue('2026-10-15');
        expect(screen.getByLabelText('Note (optional)')).toHaveValue('');

        const first = within(screen.getByRole('group', { name: 'Line 1' }));
        const second = within(screen.getByRole('group', { name: 'Line 2' }));

        expect(
            first.getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Denim Jacket');
        expect(first.getByLabelText('Quantity')).toHaveValue('50');
        expect(
            second.getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Denim Jeans 5-pocket');

        await user.clear(first.getByLabelText('Quantity'));
        await user.type(first.getByLabelText('Quantity'), '60');
        await user.click(screen.getByRole('button', { name: 'Save order' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/orders/7',
            method: 'put',
            data: {
                client: 'Boutique Lina',
                ordered_on: '2026-10-15',
                note: '',
                lines: [
                    { product_id: 1, quantity: '60' },
                    { product_id: 5, quantity: '30' },
                ],
            },
        });
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/orders',
        );
    });
});
