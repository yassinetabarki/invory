import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersEdit from '@/pages/orders/edit';
import {
    indigo,
    jacketStock,
    jeansStock,
    sizedJacketLine,
    sizeIds,
    stockProduct,
    white,
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
    // Both lines were recorded before sizes: their grid is "One size", one field per colour.
    line_products: [
        {
            ...stockProduct(
                1,
                'Denim Jacket',
                'PRD-JKT-001',
                [
                    [white, [3]],
                    [indigo, [6]],
                ],
                true,
            ),
            product: { ...jacket, photo_url: null },
        },
        {
            ...jeansStock,
            ...stockProduct(
                5,
                'Denim Jeans 5-pocket',
                'PRD-JNS-005',
                [[white, [0]]],
                true,
            ),
        },
    ],
    cities: ['Tunis'],
    client_sources: [],
};

describe('orders/edit page', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('opens "Edit order" with each line\'s grid filled in, a deleted product still on it (INV-13 AC-4)', async () => {
        const user = userEvent.setup();

        render(<OrdersEdit {...props} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Edit order' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', {
                level: 3,
                name: 'On this order · 2 products',
            }),
        ).toBeInTheDocument();

        const jacketBlock = within(
            screen.getByRole('region', { name: 'Denim Jacket' }),
        );

        // White holds the 50 units of before INV-13; Indigo is empty; the shelf is under each field.
        expect(jacketBlock.getByLabelText('White')).toHaveValue('50');
        expect(jacketBlock.getByLabelText('White')).toHaveAccessibleDescription(
            'In stock 3',
        );
        expect(jacketBlock.getByLabelText('Indigo')).toHaveValue('');
        expect(jacketBlock.getByText('Total 50 units')).toBeInTheDocument();
        expect(
            screen.getAllByText('Total on this order · 80 units · 2 products'),
        ).toHaveLength(1);

        await user.clear(jacketBlock.getByLabelText('White'));
        await user.type(jacketBlock.getByLabelText('White'), '40');
        await user.type(jacketBlock.getByLabelText('Indigo'), '6');
        await user.click(screen.getByRole('button', { name: 'Save order' }));

        expect(inertiaMock.submissions.at(-1)).toMatchObject({
            action: '/orders/7',
            method: 'put',
            data: {
                lines: [
                    {
                        product_id: 1,
                        units: {
                            [white.id]: { [sizeIds.oneSize]: '40' },
                            [indigo.id]: { [sizeIds.oneSize]: '6' },
                        },
                    },
                    {
                        product_id: 5,
                        units: { [white.id]: { [sizeIds.oneSize]: '30' } },
                    },
                ],
            },
        });
    });

    it('fills a line counted by size per colour and size, and shows a colour since taken off the product (INV-13 AC-4)', () => {
        const line: OrderLine = {
            ...sizedJacketLine,
            sizes: [
                ...sizedJacketLine.sizes.slice(0, 2),
                {
                    ...sizedJacketLine.sizes[1],
                    colour: indigo,
                    quantity: 7,
                    to_start: 7,
                },
            ],
        };

        render(
            <OrdersEdit
                {...props}
                order={{ ...props.order, lines: [line] }}
                // The jacket no longer has White: its row still shows, with nothing on the shelf.
                line_products={[
                    {
                        ...jacketStock,
                        stock: {
                            ...jacketStock.stock,
                            colours: jacketStock.stock.colours.filter(
                                (entry) => entry.colour.id !== white.id,
                            ),
                        },
                    },
                ]}
            />,
        );

        const block = within(
            screen.getByRole('region', { name: 'Denim Jacket' }),
        );

        expect(block.getByLabelText('White S')).toHaveValue('10');
        expect(block.getByLabelText('White S')).toHaveAccessibleDescription(
            'Out of stock',
        );
        expect(block.getByLabelText('White M')).toHaveValue('20');
        expect(block.getByLabelText('Indigo M')).toHaveValue('7');
        expect(block.getByLabelText('Indigo S')).toHaveAccessibleDescription(
            'In stock 2',
        );
    });

    it('shows a started cell its minimum and locks Remove on a started line (INV-13 AC-4)', () => {
        const started: OrderLine = {
            ...sizedJacketLine,
            in_cutting: 10,
            to_start: 40,
            started: 10,
            sizes: sizedJacketLine.sizes.map((row) =>
                row.size.id === sizeIds.M
                    ? { ...row, in_cutting: 10, to_start: 10, started: 10 }
                    : row,
            ),
        };

        render(
            <OrdersEdit
                {...props}
                order={{ ...props.order, lines: [started] }}
                line_products={[jacketStock]}
            />,
        );

        const block = within(
            screen.getByRole('region', { name: 'Denim Jacket' }),
        );

        expect(block.getByLabelText('White M')).toHaveAccessibleDescription(
            'Out of stock At least 10',
        );
        expect(block.getByLabelText('White S')).toHaveAccessibleDescription(
            'Out of stock',
        );
        expect(
            block.getByRole('button', {
                name: 'Remove Denim Jacket from this order',
            }),
        ).toBeDisabled();
        expect(
            block.getByRole('button', {
                name: 'Remove Denim Jacket from this order',
            }),
        ).toHaveAccessibleDescription(
            "Denim Jacket can't be removed: 10 units have started.",
        );
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
