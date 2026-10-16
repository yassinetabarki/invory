import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrderLineCard } from '@/components/orders/order-line-card';
import { resetInertiaMock } from '@/test/inertia';
import type { OrderLine, OrderProduct } from '@/types/domain';

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

function jacketLine(overrides: Partial<OrderLine> = {}): OrderLine {
    return {
        id: 70,
        product: jacket,
        quantity: 50,
        to_start: 20,
        in_cutting: 20,
        ready_to_ship: 10,
        shipped: 0,
        started: 30,
        status: 'in_progress',
        status_label: 'In progress',
        ...overrides,
    };
}

function renderCard(line: OrderLine) {
    const onMove = vi.fn();

    render(<OrderLineCard line={line} onMove={onMove} />);

    return { onMove, user: userEvent.setup() };
}

describe('<OrderLineCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the status as a word with a hidden icon and opens the right move', async () => {
        const { onMove, user } = renderCard(jacketLine());

        const status = screen.getByText('In progress');

        expect(status.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );

        await user.click(screen.getByRole('button', { name: 'Ship' }));

        expect(onMove).toHaveBeenCalledExactlyOnceWith(jacketLine(), 'ship');
    });

    it('disables a button whose stage holds nothing', () => {
        renderCard(
            jacketLine({
                to_start: 50,
                in_cutting: 0,
                ready_to_ship: 0,
                started: 0,
                status: 'not_started',
                status_label: 'Not started',
            }),
        );

        expect(
            screen.getByRole('button', { name: 'Start cutting' }),
        ).toBeEnabled();
        expect(
            screen.getByRole('button', { name: 'Mark ready' }),
        ).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Ship' })).toBeDisabled();
        for (const back of screen.getAllByRole('button', {
            name: 'Move back',
        })) {
            expect(back).toBeDisabled();
        }
    });

    it('disables the three forward buttons on a fully shipped line', () => {
        renderCard(
            jacketLine({
                to_start: 0,
                in_cutting: 0,
                ready_to_ship: 0,
                shipped: 50,
                started: 50,
                status: 'shipped',
                status_label: 'Shipped',
            }),
        );

        expect(
            screen.getByText('Shipped', { selector: '[data-status]' }),
        ).toBeVisible();
        for (const name of ['Start cutting', 'Mark ready', 'Ship']) {
            expect(screen.getByRole('button', { name })).toBeDisabled();
        }
        expect(
            screen.getAllByRole('button', { name: 'Move back' }).at(-1),
        ).toBeEnabled();
    });

    it('keeps a deleted product from starting cutting but lets its units move on and back', () => {
        renderCard(jacketLine({ product: { ...jacket, deleted: true } }));

        const startCutting = screen.getByRole('button', {
            name: 'Start cutting',
        });

        expect(startCutting).toBeDisabled();
        expect(startCutting).toHaveAccessibleDescription(
            "Denim Jacket was deleted, so it can't be cut.",
        );
        expect(
            screen.getByRole('button', { name: 'Mark ready' }),
        ).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Ship' })).toBeEnabled();
        expect(
            screen.getAllByRole('button', { name: 'Move back' })[0],
        ).toBeEnabled();
    });
});
