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
import { act } from '@testing-library/react';
import { amira, boutiqueLina, ref } from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
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
        client_id: 3,
        client_details: {
            id: 3,
            name: 'Boutique Lina',
            phone: '71 234 567',
            city: 'Tunis',
            source: 'walk_in',
            source_label: 'Walk-in / shop',
            source_other: null,
        },
    },
    products: [formProduct(jacket), formProduct(jeans)],
    size_lists: sizeLists,
    cities: ['Tunis'],
    client_sources: [],
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
        // Edit order shows the order's client and keeps it (INV-4-d gives it a picker).
        expect(screen.getByText('Boutique Lina')).toBeInTheDocument();
        expect(screen.queryByLabelText('Phone')).not.toBeInTheDocument();
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
                client_id: 3,
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

    it("shows the order's client in the picker with her grey line, and moves the order to another client (INV-4 AC-6)", async () => {
        const user = userEvent.setup();

        render(<OrdersEdit {...props} />);

        expect(
            screen.getByRole('combobox', { name: 'Client Boutique Lina' }),
        ).toBeInTheDocument();
        // Who she is comes from the picker's search, which brings her along by id.
        expect(inertiaMock.http.requests[0]).toMatchObject({
            path: '/clients/options',
            params: { include: ['3'] },
        });

        await act(async () => {
            inertiaMock.http.requests[0].resolve([
                { ...ref(boutiqueLina), id: 3 },
            ]);
        });

        expect(
            screen.getByText('Walk-in / shop · Tunis · 71 234 567'),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('combobox', { name: 'Client Boutique Lina' }),
        );
        await act(async () => {
            inertiaMock.http.requests
                .at(-1)
                ?.resolve([ref(amira), { ...ref(boutiqueLina), id: 3 }]);
        });
        await user.click(
            screen.getByRole('option', { name: /Amira Ben Salah/ }),
        );
        await user.click(screen.getByRole('button', { name: 'Save order' }));

        expect(inertiaMock.submissions.at(-1)?.data).toMatchObject({
            client_id: 1,
        });
    });

    it('adds a new client from the picker over the order, and chooses her (INV-4 AC-6)', async () => {
        const user = userEvent.setup();

        // The first visit has no cities: the window asks for them.
        render(<OrdersEdit {...props} cities={undefined} />);
        await user.click(
            screen.getByRole('combobox', { name: 'Client Boutique Lina' }),
        );
        await user.type(screen.getByPlaceholderText('Search clients'), 'Ines');
        await act(async () => {
            for (const request of inertiaMock.http.requests) {
                if (request.status === 'pending') {
                    request.resolve([]);
                }
            }
        });
        await user.click(
            await screen.findByRole('option', {
                name: 'Add "Ines" as a new client',
            }),
        );

        expect(
            screen.getByRole('dialog', { name: 'Add client' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Name')).toHaveValue('Ines');
        // The cities come when the window asks for them.
        expect(inertiaMock.router.reload).toHaveBeenCalledWith({
            only: ['cities'],
        });
    });

    it('asks to choose a client when the save says so (INV-4 AC-6)', () => {
        render(<OrdersEdit {...props} />);

        setFormState({ errors: { client_id: 'Choose a client.' } });

        expect(screen.getByText('Choose a client.')).toBeInTheDocument();
        expect(screen.getByRole('combobox', { name: /^Client/ })).toBeInvalid();
    });
});
