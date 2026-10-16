import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { MoveUnitsDialog } from '@/components/orders/move-units-dialog';
import type { MoveTarget } from '@/components/orders/move-units-dialog';
import { PREVIEW_DEBOUNCE_MS } from '@/components/orders/use-move-preview';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type {
    MaterialMoveRow,
    MovePreview,
    Order,
    OrderLine,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule, useHttpMock } = await import('@/test/inertia');
    const { useMemo } = await import('react');
    const inertia = createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );

    type OnError = (errors: Record<string, string>) => void;

    // The mocked `useHttp` never calls `onError`: a reply of `{ errors }` stands for the server's 422.
    function useHttpWithValidation() {
        const http = useHttpMock();
        // Stable, like the real `get`: the hook's effect depends on it.
        const get = useMemo(
            () =>
                (url: string, options: { onError?: OnError } = {}) =>
                    http.get(url, {
                        ...options,
                        onSuccess: (data: unknown) => {
                            if (
                                typeof data === 'object' &&
                                data !== null &&
                                'errors' in data &&
                                typeof data.errors === 'object' &&
                                data.errors !== null
                            ) {
                                options.onError?.(
                                    Object.fromEntries(
                                        Object.entries(data.errors).map(
                                            ([key, value]) => [
                                                key,
                                                String(value),
                                            ],
                                        ),
                                    ),
                                );
                            }
                        },
                    }),
            [http.get],
        );

        return { ...http, get };
    }

    return { ...inertia, useHttp: useHttpWithValidation };
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

function row(
    id: number,
    name: string,
    unit: string,
    figures: Pick<MaterialMoveRow, 'quantity' | 'in_stock' | 'after'> &
        Partial<MaterialMoveRow>,
): MaterialMoveRow {
    return {
        material: { id, sku: `MAT-${id}`, name, unit },
        short_by: null,
        not_returned: null,
        ...figures,
    };
}

/** What the server answers for 30 units of Denim Jacket on the demo stock (AC-1). */
const cutting30: MovePreview = {
    move: 'start_cutting',
    units: 30,
    rows: [
        row(1, 'Denim 12oz Indigo', 'm', {
            quantity: '59.4000',
            in_stock: '250.0000',
            after: '190.6000',
        }),
        row(2, 'Metal shank button 17mm', 'pc', {
            quantity: '240.0000',
            in_stock: '900.0000',
            after: '660.0000',
        }),
        row(3, 'Polyester thread', 'm', {
            quantity: '3780.0000',
            in_stock: '20000.0000',
            after: '16220.0000',
        }),
        row(4, 'Woven brand label', 'pc', {
            quantity: '30.0000',
            in_stock: '400.0000',
            after: '370.0000',
        }),
    ],
    has_composition: true,
    nothing_taken: false,
    max_cuttable: 112,
    short_message: null,
};

const shortMessage =
    'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.';

/** Buttons at 100 pc: 20 units need 160 pc (AC-3). */
const cutting20Short: MovePreview = {
    ...cutting30,
    units: 20,
    rows: [
        row(2, 'Metal shank button 17mm', 'pc', {
            quantity: '160.0000',
            in_stock: '100.0000',
            after: null,
            short_by: '60.0000',
        }),
    ],
    max_cuttable: 12,
    short_message: shortMessage,
};

/** 5 units back after 30 were cut and 10 moved on (AC-5). */
const back5: MovePreview = {
    move: 'back_to_start',
    units: 5,
    rows: [
        row(1, 'Denim 12oz Indigo', 'm', {
            quantity: '9.9000',
            in_stock: '190.6000',
            after: '200.5000',
        }),
    ],
    has_composition: true,
    nothing_taken: false,
    max_cuttable: null,
    short_message: null,
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
        const { user } = renderDialog({ lineId: 70, move: 'mark_ready' });

        expect(
            screen.getByRole('dialog', { name: 'Mark ready · Denim Jacket' }),
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
            data: { move: 'mark_ready', units: '15' },
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

describe('<MoveUnitsDialog> materials table (S-53)', () => {
    beforeEach(() => {
        resetInertiaMock();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    function setup(move: MoveTarget['move'], shown: Order = order) {
        render(
            <MoveUnitsDialog
                order={shown}
                target={{ lineId: 70, move }}
                onClose={vi.fn()}
            />,
        );

        return {
            field: () => screen.getByLabelText('How many units?'),
            moveUnits: () => screen.getByRole('button', { name: 'Move units' }),
        };
    }

    // Fake timers and userEvent don't mix; the number is set the way a keystroke does.
    function type(input: HTMLElement, value: string) {
        fireEvent.change(input, { target: { value } });
    }

    /** The typing rests: the server is asked. */
    async function rest() {
        await act(async () => {
            await vi.advanceTimersByTimeAsync(PREVIEW_DEBOUNCE_MS);
        });
    }

    async function answer(index: number, data: unknown) {
        await act(async () => {
            inertiaMock.http.requests[index].resolve(data);
        });
    }

    async function fail(index: number) {
        await act(async () => {
            inertiaMock.http.requests[index].reject();
        });
    }

    it('shows what Start cutting takes once the server answers for the number typed (AC-1)', async () => {
        const { field, moveUnits } = setup('start_cutting');

        type(field(), '30');

        // Nothing is shown, and nothing can be confirmed, without a server answer.
        expect(inertiaMock.http.requests).toHaveLength(0);
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();

        await rest();

        expect(inertiaMock.http.requests).toHaveLength(1);
        expect(inertiaMock.http.requests[0]).toMatchObject({
            path: '/orders/7/lines/70/moves/preview',
            params: { move: 'start_cutting', units: '30' },
        });
        expect(screen.getByText('Loading materials…')).toBeInTheDocument();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();

        await answer(0, cutting30);

        const table = screen.getByRole('table', {
            name: 'Materials taken out of stock',
        });

        expect(
            within(table)
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Material', 'Needed', 'In stock', 'Left after']);
        expect(
            within(within(table).getByRole('row', { name: /Polyester thread/ }))
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual(['3,780 m', '20,000 m', '16,220 m']);
        expect(within(table).getAllByRole('row')).toHaveLength(5);
        expect(
            screen.getByText('Waste included. Stock on hand allows 112 units.'),
        ).toBeInTheDocument();
        expect(moveUnits()).toBeEnabled();
    });

    it('follows the number typed: the old table goes away until the new answer arrives', async () => {
        const { field, moveUnits } = setup('start_cutting');

        type(field(), '30');
        await rest();
        await answer(0, cutting30);
        expect(screen.getByRole('table')).toBeInTheDocument();

        type(field(), '20');

        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();

        await rest();

        expect(inertiaMock.http.requests[1].params.units).toBe('20');

        await answer(1, cutting20Short);

        expect(screen.getByText('Short by 60 pc')).toBeInTheDocument();
    });

    it.each([
        ['start_cutting', 'Materials taken out of stock', cutting30],
        ['back_to_start', 'Materials returned to stock', back5],
    ] as const)(
        'says when the %s materials could not load, and asks again on "Try again" (AC-1, AC-5)',
        async (move, heading, preview) => {
            const { field, moveUnits } = setup(move);

            type(field(), '5');
            await rest();
            await fail(0);

            expect(screen.getByRole('alert')).toHaveTextContent(
                "Couldn't load the materials for this move.",
            );
            expect(moveUnits()).toBeDisabled();

            fireEvent.click(screen.getByRole('button', { name: 'Try again' }));

            expect(inertiaMock.http.requests).toHaveLength(2);
            expect(screen.getByText('Loading materials…')).toBeInTheDocument();
            expect(moveUnits()).toBeDisabled();

            await answer(1, preview);

            expect(
                screen.getByRole('table', { name: heading }),
            ).toBeInTheDocument();
            expect(moveUnits()).toBeEnabled();
        },
    );

    it("puts the server's refusal of the number under the field and keeps Move units disabled", async () => {
        const { field, moveUnits } = setup('start_cutting');

        type(field(), '0');
        await rest();
        await answer(0, {
            errors: { units: 'Enter a whole number of units, at least 1.' },
        });

        expect(field()).toBeInvalid();
        expect(field()).toHaveAccessibleDescription(
            'Up to 20 units. Enter a whole number of units, at least 1.',
        );
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();
    });

    it("keeps Move units clickable when stock is short, and shows the server's refusal under the field (AC-3)", async () => {
        const { field, moveUnits } = setup('start_cutting');

        type(field(), '20');
        await rest();
        await answer(0, cutting20Short);

        expect(screen.getByText('Short by 60 pc')).toBeInTheDocument();
        expect(screen.getAllByText(shortMessage)).toHaveLength(1);
        expect(moveUnits()).toBeEnabled();

        fireEvent.click(moveUnits());

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            data: { move: 'start_cutting', units: '20' },
        });

        // The server refuses and the page reloads with its message: still one sentence on screen,
        // and the field is invalid and described by it.
        setFormState({ errors: { units: shortMessage } });

        expect(screen.getAllByText(shortMessage)).toHaveLength(1);
        expect(field()).toBeInvalid();
        expect(field()).toHaveAccessibleDescription(
            `Up to 20 units. ${shortMessage}`,
        );
    });

    it('still shows any other refusal under the field (AC-4)', async () => {
        const { field } = setup('start_cutting');

        type(field(), '20');
        await rest();
        await answer(0, cutting20Short);
        fireEvent.click(screen.getByRole('button', { name: 'Move units' }));
        setFormState({ errors: { units: 'You can move at most 5 units.' } });

        expect(
            screen.getByText('You can move at most 5 units.'),
        ).toBeInTheDocument();
        expect(field()).toBeInvalid();
        expect(field()).toHaveAccessibleDescription(
            'Up to 20 units. You can move at most 5 units.',
        );
    });

    it.each(['start_cutting', 'back_to_start'] as const)(
        'asks nothing and shows no error when the %s window opens (S-49 AC-4)',
        async (move) => {
            const { field, moveUnits } = setup(move);

            await rest();

            expect(inertiaMock.http.requests).toHaveLength(0);
            expect(field()).not.toBeInvalid();
            expect(field()).toHaveAccessibleDescription('Up to 20 units.');
            expect(moveUnits()).toBeDisabled();
        },
    );

    it('asks the server about the field once it is emptied, and shows its message under the field (S-49 AC-4)', async () => {
        const { field, moveUnits } = setup('start_cutting');

        type(field(), '30');
        await rest();
        await answer(0, cutting30);
        expect(screen.getByRole('table')).toBeInTheDocument();

        type(field(), '');

        // Nothing stale while the empty value waits to be asked.
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();

        await rest();

        expect(inertiaMock.http.requests).toHaveLength(2);
        expect(inertiaMock.http.requests[1].params.units).toBe('');

        await answer(1, {
            errors: { units: 'Enter a whole number of units, at least 1.' },
        });

        expect(field()).toBeInvalid();
        expect(field()).toHaveAccessibleDescription(
            'Up to 20 units. Enter a whole number of units, at least 1.',
        );
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();
    });

    it('shows the server\'s "at most" message as you type more than the stage holds, never a table (S-49 AC-4, S-53 AC-1)', async () => {
        const fifty: Order = {
            ...order,
            lines: [{ ...jacketLine, to_start: 50, in_cutting: 0 }],
        };
        const { field, moveUnits } = setup('start_cutting', fifty);

        type(field(), '30');
        await rest();
        await answer(0, cutting30);
        expect(screen.getByRole('table')).toBeInTheDocument();

        type(field(), '60');

        expect(screen.queryByRole('table')).not.toBeInTheDocument();

        await rest();
        await answer(1, {
            errors: { units: 'You can move at most 50 units.' },
        });

        expect(
            screen.getByText('You can move at most 50 units.'),
        ).toBeVisible();
        expect(field()).toBeInvalid();
        expect(field()).toHaveAccessibleDescription(
            'Up to 50 units. You can move at most 50 units.',
        );
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(moveUnits()).toBeDisabled();
    });

    it('shows what Move back to To start returns (AC-5)', async () => {
        const { field } = setup('back_to_start');

        type(field(), '5');
        await rest();
        await answer(0, back5);

        expect(inertiaMock.http.requests[0].params).toMatchObject({
            move: 'back_to_start',
            units: '5',
        });
        expect(
            within(
                screen.getByRole('table', {
                    name: 'Materials returned to stock',
                }),
            )
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Material', 'Returned', 'In stock', 'After return']);
        expect(
            within(screen.getByRole('row', { name: /Denim 12oz Indigo/ }))
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ).toEqual(['9.9 m', '190.6 m', '200.5 m']);
    });

    it.each([
        'mark_ready',
        'ship',
        'back_to_cutting',
        'back_to_ready',
    ] as const)(
        'has no table for %s, and Move units works as before',
        async (move) => {
            const { field, moveUnits } = setup(move);

            type(field(), '5');
            await rest();

            expect(inertiaMock.http.requests).toHaveLength(0);
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
            expect(
                screen.queryByText('Loading materials…'),
            ).not.toBeInTheDocument();
            expect(moveUnits()).toBeEnabled();

            fireEvent.click(moveUnits());

            expect(inertiaMock.submissions[0]).toMatchObject({
                data: { move, units: '5' },
            });
        },
    );
});
