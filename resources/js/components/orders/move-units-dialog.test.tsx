import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MoveUnitsDialog } from '@/components/orders/move-units-dialog';
import type { MoveTarget } from '@/components/orders/move-units-dialog';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { Order, OrderLine } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const jacketLine: OrderLine = {
    id: 70,
    product: {
        id: 1,
        sku: 'PRD-JKT-001',
        name: 'Denim Jacket',
        status: 'active',
        deleted: false,
    },
    quantity: 50,
    to_start: 20,
    in_cutting: 20,
    ready_to_ship: 10,
    shipped: 0,
    started: 30,
    status: 'in_progress',
    status_label: 'In progress',
};

const order: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: null,
    lines: [jacketLine],
    units: 50,
    in_cutting: 20,
    ready_to_ship: 10,
    shipped: 0,
    status: 'in_progress',
    status_label: 'In progress',
    delete_refusal: null,
};

function renderDialog(target: MoveTarget) {
    const onClose = vi.fn();

    render(<MoveUnitsDialog order={order} target={target} onClose={onClose} />);

    return { onClose, user: userEvent.setup() };
}

describe('<MoveUnitsDialog>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('asks how many units and posts the move with what was typed', async () => {
        const { user } = renderDialog({ lineId: 70, move: 'start_cutting' });

        expect(
            screen.getByRole('dialog', {
                name: 'Start cutting · Denim Jacket',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByLabelText('How many units?'),
        ).toHaveAccessibleDescription('Up to 20 units.');

        await user.type(screen.getByLabelText('How many units?'), '15');
        await user.click(screen.getByRole('button', { name: 'Move units' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/orders/7/lines/70/moves',
            method: 'post',
            data: { move: 'start_cutting', units: '15' },
        });
    });

    it("shows the server's message under the field and stays open", async () => {
        setFormState({ errors: { units: 'You can move at most 20 units.' } });
        const { onClose } = renderDialog({
            lineId: 70,
            move: 'mark_ready',
        });

        const field = screen.getByLabelText('How many units?');

        expect(field).toBeInvalid();
        expect(field).toHaveAccessibleDescription(
            'Up to 20 units. You can move at most 20 units.',
        );
        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(onClose).not.toHaveBeenCalled();
    });

    it('sends nothing on Cancel', async () => {
        const { onClose, user } = renderDialog({
            lineId: 70,
            move: 'start_cutting',
        });

        await user.type(screen.getByLabelText('How many units?'), '5');
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onClose).toHaveBeenCalledOnce();
        expect(inertiaMock.submissions).toHaveLength(0);
    });

    it('names a move back with the stage it goes to and the units it can take', () => {
        renderDialog({ lineId: 70, move: 'back_to_start' });

        expect(
            screen.getByRole('dialog', {
                name: 'Move back to To start · Denim Jacket',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('Up to 20 units.')).toBeInTheDocument();
    });
});
