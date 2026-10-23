import { fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrderForm } from '@/components/orders/order-form';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import { formProduct, sizeIds, sizeLists } from '@/test/fixtures';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function product(
    id: number,
    name: string,
    sku: string,
    sizeListCode = 's_to_xl',
) {
    return formProduct(
        { id, name, sku, status: 'active', deleted: false },
        sizeListCode,
    );
}

const products = [
    product(1, 'Denim Jacket', 'PRD-JKT-001'),
    product(2, 'Leather Tote', 'PRD-TOT-002', 'one_size'),
    product(3, 'Leather Belt', 'PRD-BLT-003', 'one_size'),
];

function renderForm() {
    const user = userEvent.setup();

    render(
        <OrderForm
            title="New order"
            products={products}
            sizeLists={sizeLists}
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

    it('records an order with two one-size products (AC-2)', async () => {
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

        await fillLine(user, 1, 'Leather Tote · PRD-TOT-002', '50');
        await user.click(screen.getByRole('button', { name: 'Add product' }));
        await fillLine(user, 2, 'Leather Belt · PRD-BLT-003', '30');

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
                    { product_id: 2, quantity: '50' },
                    { product_id: 3, quantity: '30' },
                ],
            },
        });
        // The client-only line keys are never sent.
        expect(inertiaMock.submissions[0].data.lines).toEqual([
            { product_id: 2, quantity: '50' },
            { product_id: 3, quantity: '30' },
        ]);
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/orders',
        );
    });

    it("shows the server's messages under their fields and keeps what was typed (AC-4)", async () => {
        const user = renderForm();

        await user.type(screen.getByLabelText('Client'), 'Boutique Lina');
        await fillLine(user, 1, 'Leather Tote · PRD-TOT-002', '0');
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
        ).toHaveTextContent('Leather Tote · PRD-TOT-002');
    });

    it('asks a quantity per size for a product with sizes and posts them by size id (S-60 AC-1)', async () => {
        const user = renderForm();
        const line = lineGroup(1);

        await user.type(screen.getByLabelText('Client'), 'Boutique Lina');
        await user.click(line.getByRole('combobox', { name: 'Product' }));
        await user.click(
            screen.getByRole('option', { name: 'Denim Jacket · PRD-JKT-001' }),
        );

        // The group has a visible name (S-60 F4), not a screen-reader-only one.
        expect(
            line.getByRole('group', { name: 'Quantity per size' }),
        ).toBeInTheDocument();
        expect(line.getByText('Quantity per size')).not.toHaveClass('sr-only');

        // One field per size, in the list's order, in place of "Quantity".
        const fields = line.getAllByRole('textbox');

        expect(fields).toHaveLength(4);
        ['S', 'M', 'L', 'XL'].forEach((label, index) =>
            expect(fields[index]).toHaveAccessibleName(label),
        );
        expect(line.queryByLabelText('Quantity')).not.toBeInTheDocument();
        expect(line.getByText('Total 0 units')).toBeInTheDocument();

        // XL is left empty at first: it counts 0.
        await user.type(line.getByLabelText('S'), '10');
        await user.type(line.getByLabelText('M'), '20');
        await user.type(line.getByLabelText('L'), '15');
        expect(line.getByText('Total 45 units')).toBeInTheDocument();
        await user.type(line.getByLabelText('XL'), '5');
        expect(line.getByText('Total 50 units')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Add product' }));
        await user.click(
            lineGroup(2).getByRole('combobox', { name: 'Product' }),
        );
        await user.click(
            screen.getByRole('option', { name: 'Leather Tote · PRD-TOT-002' }),
        );
        expect(lineGroup(2).getByLabelText('Quantity')).toBeInTheDocument();
        expect(lineGroup(2).queryByText(/^Total/)).not.toBeInTheDocument();
        await user.type(lineGroup(2).getByLabelText('Quantity'), '12');

        await user.click(screen.getByRole('button', { name: 'Save order' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0].data.lines).toEqual([
            {
                product_id: 1,
                sizes: {
                    [sizeIds.S]: '10',
                    [sizeIds.M]: '20',
                    [sizeIds.L]: '15',
                    [sizeIds.XL]: '5',
                },
            },
            { product_id: 2, quantity: '12' },
        ]);
    });

    it("shows a product's new sizes and the refusal under Total after its sizes changed, without a second post (S-60 F3)", async () => {
        const user = userEvent.setup();
        const props = {
            title: 'New order',
            sizeLists,
            action: { url: '/orders', method: 'post' as const },
            cancelHref: '/orders',
        };
        const { rerender } = render(
            <OrderForm {...props} products={products} />,
        );
        const line = lineGroup(1);

        await user.type(screen.getByLabelText('Client'), 'Boutique Lina');
        await user.click(line.getByRole('combobox', { name: 'Product' }));
        await user.click(
            screen.getByRole('option', { name: 'Denim Jacket · PRD-JKT-001' }),
        );
        await user.type(line.getByLabelText('S'), '10');
        await user.click(screen.getByRole('button', { name: 'Save order' }));
        expect(inertiaMock.submissions).toHaveLength(1);

        // The server refuses: the product moved to the 36 to 46 list meanwhile.
        const message =
            'The sizes of Denim Jacket have changed. Enter its quantities again.';

        rerender(
            <OrderForm
                {...props}
                products={[
                    product(1, 'Denim Jacket', 'PRD-JKT-001', 'eu_36_to_46'),
                    ...products.slice(1),
                ]}
            />,
        );

        // The page's new props and the refusal arrive together.
        setFormState({ errors: { 'lines.0.sizes': message } });

        const sizes = line.getAllByRole('textbox');

        expect(sizes).toHaveLength(11);
        expect(sizes[0]).toHaveAccessibleName('36');
        expect(sizes[10]).toHaveAccessibleName('46');
        expect(line.queryByLabelText('XL')).not.toBeInTheDocument();
        expect(
            line.getByText('Total 0 units').nextElementSibling,
        ).toHaveTextContent(message);
        expect(sizes[0]).toHaveFocus();
        expect(inertiaMock.submissions).toHaveLength(1);
    });
});
