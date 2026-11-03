import { render, screen } from '@testing-library/react';
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
        to_deliver: quantity,
        delivered: 0,
        cancelled: 0,
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
    to_deliver: 80,
    delivered: 0,
    cancelled: 0,
    status: 'not_started',
    status_label: 'Not started',
    client_id: 3,
    client_details: null,
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

    it('adds that delivered units are not put back, and is never refused (INV-14 AC-6)', () => {
        renderDialog({ ...lina, delivered: 5, to_deliver: 75 });

        expect(
            screen.getByRole('alertdialog', { name: 'Delete order?' }),
        ).toHaveTextContent(
            "This cannot be undone. Finished stock isn't changed: the 5 units already delivered are not put back.",
        );
        expect(
            screen.getByRole('button', { name: 'Delete order' }),
        ).toBeEnabled();
    });

    it('says "the 1 unit already delivered is not put back" in the singular', () => {
        renderDialog({ ...lina, delivered: 1, to_deliver: 79 });

        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            'the 1 unit already delivered is not put back.',
        );
    });
});
