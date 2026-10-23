import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersEdit from '@/pages/orders/edit';
import {
    formProduct,
    sizedJacketLine,
    sizeIds,
    sizeLists,
    withOneSize,
} from '@/test/fixtures';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { OrderEditProps, OrderLine, OrderProduct } from '@/types/domain';

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

function notStarted(
    id: number,
    product: OrderProduct,
    quantity: number,
): OrderLine {
    return withOneSize({
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
    });
}

const props: OrderEditProps = {
    order: {
        id: 7,
        client: 'Boutique Lina',
        ordered_on: '2026-10-15',
        note: null,
        lines: [notStarted(70, jacket, 50), notStarted(71, jeans, 30)],
        units: 80,
        to_start: 80,
        in_cutting: 0,
        ready_to_ship: 0,
        shipped: 0,
        status: 'not_started',
        status_label: 'Not started',
        delete_refusal: null,
    },
    products: [formProduct(jacket), formProduct(jeans)],
    size_lists: sizeLists,
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

    it('fills each size with its saved quantity, and keeps "Quantity" with a hint for a line recorded before sizes (S-60 AC-4, AC-5)', async () => {
        const user = userEvent.setup();

        render(
            <OrdersEdit
                {...props}
                order={{
                    ...props.order,
                    lines: [sizedJacketLine, props.order.lines[1]],
                }}
            />,
        );

        const first = within(screen.getByRole('group', { name: 'Line 1' }));
        const second = within(screen.getByRole('group', { name: 'Line 2' }));

        expect(first.queryByLabelText('Quantity')).not.toBeInTheDocument();
        expect(
            ['S', 'M', 'L', 'XL'].map(
                (label) =>
                    (first.getByLabelText(label) as HTMLInputElement).value,
            ),
        ).toEqual(['10', '20', '15', '5']);
        expect(first.getByText('Total 50 units')).toBeInTheDocument();

        expect(second.getByLabelText('Quantity')).toHaveValue('30');
        expect(second.getByLabelText('Quantity')).toHaveAccessibleDescription(
            'Recorded before sizes.',
        );

        // Changing M from 20 to 25 is posted by size id; the total follows.
        await user.clear(first.getByLabelText('M'));
        await user.type(first.getByLabelText('M'), '25');
        expect(first.getByText('Total 55 units')).toBeInTheDocument();

        // Removed and added back in the same visit, the line is still counted as before.
        await user.click(
            screen.getByRole('button', { name: 'Remove Denim Jeans 5-pocket' }),
        );
        await user.click(screen.getByRole('button', { name: 'Add product' }));
        await user.click(
            within(screen.getByRole('group', { name: 'Line 2' })).getByRole(
                'combobox',
                { name: 'Product' },
            ),
        );
        await user.click(
            screen.getByRole('option', {
                name: 'Denim Jeans 5-pocket · PRD-JNS-005',
            }),
        );

        const back = within(screen.getByRole('group', { name: 'Line 2' }));

        expect(back.getByLabelText('Quantity')).toHaveAccessibleDescription(
            'Recorded before sizes.',
        );
        await user.type(back.getByLabelText('Quantity'), '30');
        await user.click(screen.getByRole('button', { name: 'Save order' }));

        expect(inertiaMock.submissions[0].data.lines).toEqual([
            {
                product_id: 1,
                sizes: {
                    [sizeIds.S]: '10',
                    [sizeIds.M]: '25',
                    [sizeIds.L]: '15',
                    [sizeIds.XL]: '5',
                },
            },
            { product_id: 5, quantity: '30' },
        ]);
    });

    it('locks the product and Remove of a line that has started (AC-6)', () => {
        const started: OrderLine = {
            ...notStarted(70, jacket, 50),
            to_start: 20,
            in_cutting: 30,
            started: 30,
            status: 'in_progress',
            status_label: 'In progress',
        };

        render(
            <OrdersEdit
                {...props}
                order={{
                    ...props.order,
                    lines: [started, props.order.lines[1]],
                }}
            />,
        );

        const first = within(screen.getByRole('group', { name: 'Line 1' }));
        const second = within(screen.getByRole('group', { name: 'Line 2' }));

        expect(first.getByRole('combobox', { name: 'Product' })).toBeDisabled();
        expect(
            first.getByRole('button', { name: 'Remove Denim Jacket' }),
        ).toHaveAccessibleDescription(
            "Denim Jacket can't be removed: 30 units have started.",
        );
        expect(second.getByRole('combobox', { name: 'Product' })).toBeEnabled();
    });
});
