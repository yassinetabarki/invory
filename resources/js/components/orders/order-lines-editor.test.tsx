import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it } from 'vite-plus/test';
import { newOrderLine } from '@/components/orders/order-line-row';
import type { OrderLineRow } from '@/components/orders/order-line-row';
import { OrderLinesEditor } from '@/components/orders/order-lines-editor';
import type { OrderProduct } from '@/types/domain';

const products: OrderProduct[] = [
    {
        id: 1,
        name: 'Denim Jacket',
        sku: 'PRD-JKT-001',
        status: 'active',
        deleted: false,
    },
    {
        id: 5,
        name: 'Denim Jeans 5-pocket',
        sku: 'PRD-JNS-005',
        status: 'active',
        deleted: false,
    },
];

function Harness({ initial }: { initial: OrderLineRow[] }) {
    const [rows, setRows] = useState(initial);

    return (
        <OrderLinesEditor
            rows={rows}
            products={products}
            errors={{}}
            onChange={setRows}
        />
    );
}

describe('<OrderLinesEditor>', () => {
    it('names each remove button after its product, or "Remove line" without one (AC-3)', () => {
        render(
            <Harness
                initial={[
                    newOrderLine({ product_id: 5, quantity: '30' }),
                    newOrderLine(),
                ]}
            />,
        );

        expect(
            screen.getByRole('button', { name: 'Remove Denim Jeans 5-pocket' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Remove line' }),
        ).toBeInTheDocument();
    });

    it('keeps the other lines typed values when one is removed, and shows the empty text with none (AC-3)', async () => {
        const user = userEvent.setup();

        render(
            <Harness
                initial={[
                    newOrderLine({ product_id: 1, quantity: '50' }),
                    newOrderLine({ product_id: 5, quantity: '30' }),
                ]}
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Remove Denim Jacket' }),
        );

        expect(screen.getByLabelText('Quantity')).toHaveValue('30');

        await user.click(
            screen.getByRole('button', { name: 'Remove Denim Jeans 5-pocket' }),
        );

        expect(
            screen.getByText(
                'No products yet. Add the products the client ordered.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Add product' }),
        ).toBeEnabled();
    });

    it('marks archived and deleted products "(not active)" in the picker and leaves active ones plain (AC-2, AC-7)', async () => {
        const user = userEvent.setup();
        const mixed: OrderProduct[] = [
            products[0],
            {
                id: 8,
                name: 'Wool Coat',
                sku: 'PRD-COT-008',
                status: 'archived',
                deleted: false,
            },
            {
                id: 9,
                name: 'Linen Shirt',
                sku: 'PRD-SHT-009',
                status: 'active',
                deleted: true,
            },
        ];

        render(
            <OrderLinesEditor
                rows={[newOrderLine()]}
                products={mixed}
                errors={{}}
                onChange={() => {}}
            />,
        );

        await user.click(screen.getByRole('combobox', { name: 'Product' }));

        expect(
            screen.getByRole('option', { name: 'Denim Jacket · PRD-JKT-001' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', {
                name: 'Wool Coat · PRD-COT-008 (not active)',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', {
                name: 'Linen Shirt · PRD-SHT-009 (not active)',
            }),
        ).toBeInTheDocument();
    });

    it('disables "Add product" at 50 lines and says why (AC-3)', () => {
        render(
            <Harness
                initial={Array.from({ length: 50 }, () =>
                    newOrderLine({ product_id: 1, quantity: '1' }),
                )}
            />,
        );

        const add = screen.getByRole('button', { name: 'Add product' });

        expect(add).toBeDisabled();
        expect(add).toHaveAccessibleDescription(
            'An order can hold up to 50 products.',
        );
    });
});
