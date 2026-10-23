import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { DeleteOrderDialog } from '@/components/orders/delete-order-dialog';
import { withOneSize } from '@/test/fixtures';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { Order, OrderLine, OrderProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function product(id: number, name: string) {
    return {
        id,
        sku: `PRD-${id}`,
        name,
        status: 'active' as const,
        deleted: false,
    };
}

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

const lina: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: null,
    lines: [
        notStarted(1, product(1, 'Denim Jacket'), 50),
        notStarted(2, product(5, 'Denim Jeans 5-pocket'), 30),
    ],
    units: 80,
    to_start: 80,
    in_cutting: 0,
    ready_to_ship: 0,
    shipped: 0,
    status: 'not_started',
    status_label: 'Not started',
    delete_refusal: null,
};

function renderDialog(order: Order) {
    const onCancel = vi.fn();
    const onConfirm = vi.fn();
    const user = userEvent.setup();

    render(
        <DeleteOrderDialog
            order={order}
            processing={false}
            returnFocusTo={{ current: null }}
            onCancel={onCancel}
            onConfirm={onConfirm}
        />,
    );

    return { onCancel, onConfirm, user };
}

describe('<DeleteOrderDialog>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('names the order with its product and unit counts', () => {
        renderDialog(lina);

        expect(
            screen.getByRole('alertdialog', { name: 'Delete order?' }),
        ).toHaveTextContent(
            'The order of Boutique Lina (2 products, 80 units) will be removed. Material stock and sales are not affected. This cannot be undone.',
        );
        expect(
            screen.getByRole('button', { name: 'Delete order' }),
        ).toBeEnabled();
    });

    it('uses the singular for one product and one unit', () => {
        renderDialog({ ...lina, lines: [lina.lines[0]], units: 1 });

        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            'The order of Boutique Lina (1 product, 1 unit) will be removed.',
        );
    });

    it('keeps the order on Cancel: nothing is deleted', async () => {
        const { user, onCancel, onConfirm } = renderDialog(lina);

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onCancel).toHaveBeenCalledOnce();
        expect(onConfirm).not.toHaveBeenCalled();
        expect(inertiaMock.router.delete).not.toHaveBeenCalled();
    });

    describe('when units are in cutting or ready to ship (AC-6)', () => {
        const refusal =
            "Boutique Lina can't be deleted: 30 units are in cutting or ready to ship. Move them back to To start first.";

        it('shows the refusal in place of the question, with only Close, and sends nothing', async () => {
            const { user, onCancel, onConfirm } = renderDialog({
                ...lina,
                delete_refusal: refusal,
            });

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete order?',
            });

            expect(dialog).toHaveTextContent(refusal);
            expect(dialog).not.toHaveTextContent('will be removed');
            expect(within(dialog).getAllByRole('button')).toHaveLength(1);
            expect(
                screen.queryByRole('button', { name: 'Delete order' }),
            ).not.toBeInTheDocument();

            await user.click(screen.getByRole('button', { name: 'Close' }));

            expect(onCancel).toHaveBeenCalledOnce();
            expect(onConfirm).not.toHaveBeenCalled();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('shows the one-unit text as the server sent it', () => {
            const oneUnit =
                "Boutique Lina can't be deleted: 1 unit is in cutting or ready to ship. Move it back to To start first.";

            renderDialog({ ...lina, delete_refusal: oneUnit });

            expect(screen.getByRole('alertdialog')).toHaveTextContent(oneUnit);
        });
    });
});
