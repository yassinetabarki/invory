import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { newOrderLine } from '@/components/orders/order-line-row';
import type {
    OrderLineRow,
    StartedLine,
} from '@/components/orders/order-line-row';
import { OrderLinesEditor } from '@/components/orders/order-lines-editor';
import type { OrderProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

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

function Harness({
    initial,
    started,
    errors = {},
}: {
    initial: OrderLineRow[];
    started?: ReadonlyMap<string, StartedLine>;
    errors?: Record<string, string | undefined>;
}) {
    const [rows, setRows] = useState(initial);

    return (
        <OrderLinesEditor
            rows={rows}
            products={products}
            errors={errors}
            started={started}
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

    describe('once work has started on a line (AC-6)', () => {
        function startedRows() {
            const jacket = newOrderLine({ product_id: 1, quantity: '50' });
            const jeans = newOrderLine({ product_id: 5, quantity: '30' });
            const fresh = newOrderLine();

            return {
                rows: [jacket, jeans, fresh],
                started: new Map([
                    [jacket.uid, { name: 'Denim Jacket', count: 30 }],
                ]),
            };
        }

        it('locks its product and Remove with the reason readable, and leaves the other lines free', async () => {
            const user = userEvent.setup();
            const { rows, started } = startedRows();

            render(<Harness initial={rows} started={started} />);

            const first = within(screen.getByRole('group', { name: 'Line 1' }));
            const picker = first.getByRole('combobox', { name: 'Product' });
            const remove = first.getByRole('button', {
                name: 'Remove Denim Jacket',
            });

            expect(picker).toBeDisabled();
            expect(picker).toHaveAccessibleDescription(
                "The product can't change once work on this line has started.",
            );
            expect(remove).toBeDisabled();
            expect(remove).toHaveAccessibleDescription(
                "Denim Jacket can't be removed: 30 units have started.",
            );

            // The quantity stays editable.
            await user.type(first.getByLabelText('Quantity'), '5');
            expect(first.getByLabelText('Quantity')).toHaveValue('505');

            for (const name of ['Line 2', 'Line 3']) {
                const line = within(screen.getByRole('group', { name }));

                expect(
                    line.getByRole('combobox', { name: 'Product' }),
                ).toBeEnabled();
                expect(
                    line.getByRole('button', { name: /^Remove/ }),
                ).toBeEnabled();
            }
        });

        it('says "1 unit has started" for a single unit', () => {
            const row = newOrderLine({ product_id: 1, quantity: '50' });

            render(
                <Harness
                    initial={[row]}
                    started={
                        new Map([[row.uid, { name: 'Denim Jacket', count: 1 }]])
                    }
                />,
            );

            expect(
                screen.getByRole('button', { name: 'Remove Denim Jacket' }),
            ).toHaveAccessibleDescription(
                "Denim Jacket can't be removed: 1 unit has started.",
            );
        });

        it("shows the server's quantity message under its line and the removal message under the products", () => {
            const { rows, started } = startedRows();
            const quantityMessage =
                "The quantity can't be less than the 30 units already in cutting, ready to ship or shipped.";
            const removalMessage =
                "Denim Jacket can't be removed: 30 units have started.";

            render(
                <Harness
                    initial={rows}
                    started={started}
                    errors={{
                        lines: removalMessage,
                        'lines.0.quantity': quantityMessage,
                    }}
                />,
            );

            const first = within(screen.getByRole('group', { name: 'Line 1' }));

            expect(first.getByText(quantityMessage)).toBeInTheDocument();
            expect(
                first.getByLabelText('Quantity'),
            ).toHaveAccessibleDescription(quantityMessage);
            expect(
                within(
                    screen.getByRole('region', { name: 'Products' }),
                ).getByRole('alert'),
            ).toHaveTextContent(removalMessage);
        });
    });
});
