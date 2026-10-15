import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { DeleteOrderDialog } from '@/components/orders/delete-order-dialog';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { Order } from '@/types/domain';

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

const lina: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: null,
    lines: [
        { id: 1, product: product(1, 'Denim Jacket'), quantity: 50 },
        { id: 2, product: product(5, 'Denim Jeans 5-pocket'), quantity: 30 },
    ],
    units: 80,
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
});
