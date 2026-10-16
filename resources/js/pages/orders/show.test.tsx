import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersShow from '@/pages/orders/show';
import { Toaster } from '@/components/ui/sonner';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
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

    describe('Focus after a move (S-49 AC-2)', () => {
        const inCutting20: OrderLine = {
            ...notStarted(70, jacket, 50),
            to_start: 30,
            in_cutting: 20,
            started: 20,
            status: 'in_progress',
            status_label: 'In progress',
        };

        async function moveSuccessfully(afterMove: OrderLine) {
            const user = userEvent.setup();
            const order = { ...props.order, lines: [inCutting20] };
            const { rerender } = render(<OrdersShow order={order} />);
            const jackets = within(
                screen.getByRole('region', {
                    name: 'Denim Jacket · PRD-JKT-001 · 50 units',
                }),
            );

            await user.click(
                jackets.getByRole('button', { name: 'Mark ready' }),
            );
            await user.type(screen.getByLabelText('How many units?'), '20');
            await user.click(
                screen.getByRole('button', { name: 'Move units' }),
            );

            // The page reloads with the new counts, then the move reports success.
            rerender(<OrdersShow order={{ ...order, lines: [afterMove] }} />);
            act(() => {
                const onSuccess = inertiaMock.submissions[0].options
                    ?.onSuccess as () => void;

                onSuccess();
            });
            await waitFor(() =>
                expect(screen.queryByRole('dialog')).not.toBeInTheDocument(),
            );

            return jackets;
        }

        it('goes back to the button that opened the window while it can still be clicked', async () => {
            await moveSuccessfully({
                ...inCutting20,
                in_cutting: 10,
                ready_to_ship: 10,
            });

            await waitFor(
                () =>
                    expect(
                        screen.getAllByRole('button', {
                            name: 'Mark ready',
                        })[0],
                    ).toHaveFocus(),
                { timeout: 500 },
            );
        });

        it("goes to the line's heading when the button is now disabled", async () => {
            await moveSuccessfully({
                ...inCutting20,
                in_cutting: 0,
                ready_to_ship: 20,
            });

            expect(
                screen.getAllByRole('button', { name: 'Mark ready' })[0],
            ).toBeDisabled();
            await waitFor(
                () =>
                    expect(
                        screen.getByRole('heading', {
                            level: 3,
                            name: 'Denim Jacket · PRD-JKT-001 · 50 units',
                        }),
                    ).toHaveFocus(),
                { timeout: 500 },
            );
        });
    });
    describe('Delete (AC-6)', () => {
        const refusal =
            "Boutique Lina can't be deleted: 30 units are in cutting or ready to ship. Move them back to To start first.";

        it('opens the refusal window instead of the question and sends nothing', async () => {
            const user = userEvent.setup();

            render(
                <OrdersShow
                    order={{ ...props.order, delete_refusal: refusal }}
                />,
            );

            await user.click(screen.getByRole('button', { name: 'Delete' }));

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete order?',
            });

            expect(dialog).toHaveTextContent(refusal);
            expect(
                within(dialog).queryByRole('button', { name: 'Delete order' }),
            ).not.toBeInTheDocument();

            await user.click(
                within(dialog).getByRole('button', { name: 'Close' }),
            );

            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });

        it('shows a refusal answered by the server as an error toast, and the order stays', async () => {
            render(
                <>
                    <OrdersShow {...props} />
                    <Toaster />
                </>,
            );

            // The Toaster listens to the router's "flash" event, like in the app.
            const listeners = inertiaMock.router.on.mock.calls as unknown as [
                string,
                (event: Event) => void,
            ][];
            const flash = listeners.find(([event]) => event === 'flash')?.[1];

            expect(flash).toBeDefined();

            act(() => {
                flash?.(
                    new CustomEvent('flash', {
                        detail: {
                            flash: {
                                toast: { type: 'error', message: refusal },
                            },
                        },
                    }),
                );
            });

            expect(await screen.findByText(refusal)).toBeInTheDocument();
            expect(
                screen.getByRole('heading', {
                    level: 1,
                    name: 'Boutique Lina',
                }),
            ).toBeInTheDocument();
        });
    });
});
