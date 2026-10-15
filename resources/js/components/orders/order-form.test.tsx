import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrderForm } from '@/components/orders/order-form';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { OrderProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function product(id: number, name: string, sku: string): OrderProduct {
    return { id, name, sku, status: 'active', deleted: false };
}

const products = [
    product(1, 'Denim Jacket', 'PRD-JKT-001'),
    product(5, 'Denim Jeans 5-pocket', 'PRD-JNS-005'),
];

function renderForm() {
    const user = userEvent.setup();

    render(
        <OrderForm
            title="New order"
            products={products}
            action={{ url: '/orders', method: 'post' }}
            cancelHref="/orders"
        />,
    );

    return user;
}

type User = ReturnType<typeof userEvent.setup>;

/** The nth line's group (1-based). */
function lineGroup(number: number) {
    return within(screen.getByRole('group', { name: `Line ${number}` }));
}

async function fillLine(
    user: User,
    number: number,
    option: string,
    quantity: string,
) {
    const line = lineGroup(number);

    await user.click(line.getByRole('combobox', { name: 'Product' }));
    await user.click(screen.getByRole('option', { name: option }));
    await user.type(line.getByLabelText('Quantity'), quantity);
}

describe('<OrderForm>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('records an order with two products (AC-2)', async () => {
        const user = renderForm();

        expect(screen.getByLabelText('Client')).toHaveAttribute(
            'placeholder',
            'e.g. Boutique Lina',
        );
        expect(screen.getByLabelText('Order date')).toHaveValue('2026-10-08');
        expect(screen.getByLabelText('Note (optional)')).toHaveAttribute(
            'placeholder',
            'Delivery date, packing, anything the client asked for',
        );
        expect(screen.getAllByRole('group', { name: /^Line / })).toHaveLength(
            1,
        );
        expect(
            lineGroup(1).getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Choose a product…');

        await user.type(screen.getByLabelText('Client'), 'Boutique Lina');
        fireEvent.change(screen.getByLabelText('Order date'), {
            target: { value: '2026-10-15' },
        });
        await user.click(screen.getByLabelText('Note (optional)'));
        await user.paste('Deliver before 30 Oct, in 2 boxes');

        await fillLine(user, 1, 'Denim Jacket · PRD-JKT-001', '50');
        await user.click(screen.getByRole('button', { name: 'Add product' }));
        await fillLine(user, 2, 'Denim Jeans 5-pocket · PRD-JNS-005', '30');

        expect(inertiaMock.submissions).toHaveLength(0);

        await user.click(screen.getByRole('button', { name: 'Save order' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/orders',
            method: 'post',
            data: {
                client: 'Boutique Lina',
                ordered_on: '2026-10-15',
                note: 'Deliver before 30 Oct, in 2 boxes',
                lines: [
                    { product_id: 1, quantity: '50' },
                    { product_id: 5, quantity: '30' },
                ],
            },
        });
        // The client-only line keys are never sent.
        expect(inertiaMock.submissions[0].data.lines).toEqual([
            { product_id: 1, quantity: '50' },
            { product_id: 5, quantity: '30' },
        ]);
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/orders',
        );
    });

    it("shows the server's messages under their fields and keeps what was typed (AC-4)", async () => {
        const user = renderForm();

        await user.type(screen.getByLabelText('Client'), 'Boutique Lina');
        await fillLine(user, 1, 'Denim Jacket · PRD-JKT-001', '0');
        await user.click(screen.getByRole('button', { name: 'Add product' }));

        setFormState({
            errors: {
                client: 'The client field must not be greater than 100 characters.',
                ordered_on: "The order date can't be in the future.",
                note: 'The note field must not be greater than 1000 characters.',
                lines: 'An order can hold up to 50 products.',
                'lines.1.product_id': 'Denim Jacket is already on this order.',
                'lines.0.quantity':
                    'The quantity must be a whole number from 1 to 1,000,000.',
            },
        });

        for (const [field, message] of [
            [
                screen.getByLabelText('Client'),
                'The client field must not be greater than 100 characters.',
            ],
            [
                screen.getByLabelText('Order date'),
                "The order date can't be in the future.",
            ],
            [
                screen.getByLabelText('Note (optional)'),
                'The note field must not be greater than 1000 characters.',
            ],
            [
                lineGroup(1).getByLabelText('Quantity'),
                'The quantity must be a whole number from 1 to 1,000,000.',
            ],
            [
                lineGroup(2).getByRole('combobox', { name: 'Product' }),
                'Denim Jacket is already on this order.',
            ],
        ] as const) {
            expect(field).toBeInvalid();
            expect(field).toHaveAccessibleDescription(message);
        }

        expect(
            within(screen.getByRole('region', { name: 'Products' })).getByRole(
                'alert',
            ),
        ).toHaveTextContent('An order can hold up to 50 products.');
        // Under the second line only.
        expect(
            lineGroup(1).queryByText('Denim Jacket is already on this order.'),
        ).not.toBeInTheDocument();
        expect(
            lineGroup(1).getByRole('combobox', { name: 'Product' }),
        ).toBeValid();

        // Nothing typed was lost.
        expect(screen.getByLabelText('Client')).toHaveValue('Boutique Lina');
        expect(lineGroup(1).getByLabelText('Quantity')).toHaveValue('0');
        expect(
            lineGroup(1).getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Denim Jacket · PRD-JKT-001');
    });
});
