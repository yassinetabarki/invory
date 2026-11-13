import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { RestockSheet } from '@/components/materials/restock-sheet';
import { black, denim, indigo } from '@/test/fixtures';
import { defaultColours } from '@/test/fixtures/colours';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { Material, MaterialColourStock } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function stock(
    colour: MaterialColourStock['colour'],
    quantity: string,
    unitCost: string | null,
): MaterialColourStock {
    return {
        colour,
        quantity_on_hand: quantity,
        unit_cost: unitCost,
        stock_value: null,
        stock_status: 'in_stock',
        removable: false,
        remove_reason: null,
    };
}

/** Denim 12oz kept per colour with cost per colour: Indigo 250 m, Black 40 m. */
const denim12: Material = {
    ...denim,
    id: 7,
    name: 'Denim 12oz',
    quantity_on_hand: '290.0000',
    unit_cost: '8.5000',
    stock_mode: 'per_colour',
    cost_mode: 'per_colour',
    colours: [
        stock(indigo, '250.0000', '8.5000'),
        stock(black, '40.0000', '9.2000'),
    ],
};

/** The same material with one cost for every colour. */
const denim12SameCost: Material = {
    ...denim12,
    cost_mode: 'same',
    colours: denim12.colours.map((row) => ({ ...row, unit_cost: null })),
};

/** Ten colours: Find colour appears. */
const tenColours: Material = {
    ...denim12SameCost,
    colours: defaultColours
        .slice(0, 10)
        .map((colour) => stock(colour, '0.0000', null)),
};

/** The sheet opened by a button, like the pages do. */
function Opener({
    material,
    colourId,
}: {
    material: Material;
    colourId: number | null;
}) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <button type="button" onClick={() => setOpen(true)}>
                Open restock
            </button>
            {open && (
                <RestockSheet
                    material={material}
                    open
                    onOpenChange={setOpen}
                    initialColourId={colourId}
                />
            )}
        </>
    );
}

const line = (colour: string) =>
    within(screen.getByRole('group', { name: colour }));

describe('<RestockSheet>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('posts a line for every colour, counts the colours received and words the checkbox by cost mode', async () => {
        const user = userEvent.setup();
        const { rerender } = render(
            <RestockSheet material={denim12} open onOpenChange={vi.fn()} />,
        );

        expect(
            screen.getByRole('heading', { name: 'Restock Denim 12oz' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('dialog')).toHaveAccessibleDescription(
            'In stock now: 290 m in 2 colours · cost per colour',
        );
        expect(
            screen.getByRole('checkbox', {
                name: 'Use these prices as the new unit costs',
            }),
        ).toBeChecked();
        expect(screen.getByRole('group', { name: 'Indigo' })).toHaveTextContent(
            'Now 250 m',
        );
        expect(screen.getByText('0 colours in this receipt')).toBeVisible();

        await user.type(
            line('Indigo').getByLabelText('Quantity received'),
            '100',
        );
        await user.type(
            line('Indigo').getByLabelText('Unit price paid'),
            '8.6',
        );
        await user.type(
            line('Black').getByLabelText('Quantity received'),
            '20',
        );
        await user.type(line('Black').getByLabelText('Unit price paid'), '9.4');

        expect(screen.getByText('2 colours in this receipt')).toBeVisible();

        await user.click(screen.getByRole('button', { name: 'Add to stock' }));

        expect(inertiaMock.submissions).toEqual([
            {
                action: '/materials/7/restocks',
                method: 'post',
                data: {
                    received_on: '2026-10-08',
                    reference: '',
                    'lines[10][quantity]': '100',
                    'lines[10][unit_price]': '8.6',
                    'lines[8][quantity]': '20',
                    'lines[8][unit_price]': '9.4',
                    update_unit_cost: '1',
                },
            },
        ]);

        // Same cost: one price above the lines, and the singular wording.
        rerender(
            <RestockSheet
                material={denim12SameCost}
                open
                onOpenChange={vi.fn()}
            />,
        );

        expect(screen.getByRole('dialog')).toHaveAccessibleDescription(
            /unit cost €8\.50$/,
        );
        expect(
            screen.getByRole('checkbox', {
                name: 'Use this price as the new unit cost',
            }),
        ).toBeChecked();
        expect(screen.getAllByLabelText('Unit price paid')).toHaveLength(1);
        expect(
            line('Black').queryByLabelText('Unit price paid'),
        ).not.toBeInTheDocument();
    });

    it('focuses the colour it was opened from, gives focus back, and Find colour keeps typed values', async () => {
        const user = userEvent.setup();
        const view = render(<Opener material={denim12} colourId={black.id} />);
        const opener = screen.getByRole('button', { name: 'Open restock' });

        await user.click(opener);

        await waitFor(() =>
            expect(
                line('Black').getByLabelText('Quantity received'),
            ).toHaveFocus(),
        );

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        await waitFor(() => expect(opener).toHaveFocus());

        view.unmount();
        render(
            <RestockSheet material={tenColours} open onOpenChange={vi.fn()} />,
        );

        await user.type(line('White').getByLabelText('Quantity received'), '5');
        await user.type(screen.getByLabelText('Find colour'), 'indigo');

        expect(
            screen.queryByRole('group', { name: 'White' }),
        ).not.toBeInTheDocument();
        expect(
            line('Indigo').getByLabelText('Quantity received'),
        ).toBeVisible();

        await user.clear(screen.getByLabelText('Find colour'));

        expect(line('White').getByLabelText('Quantity received')).toHaveValue(
            '5',
        );
        expect(screen.getByText('1 colour in this receipt')).toBeVisible();
    });

    it('shows the server messages on their lines and keeps the typed values', async () => {
        const user = userEvent.setup();

        render(<RestockSheet material={denim12} open onOpenChange={vi.fn()} />);

        await user.type(
            line('Indigo').getByLabelText('Quantity received'),
            '0',
        );
        await user.type(
            line('Black').getByLabelText('Quantity received'),
            '20',
        );

        setFormState({
            errors: {
                lines: 'The colours of Denim 12oz have changed. Enter the quantities again.',
                [`lines.${indigo.id}.quantity`]:
                    'Indigo: Enter a number above 0, with up to 4 decimals.',
                [`lines.${black.id}.unit_price`]:
                    'Black: Enter the price paid.',
            },
        });

        expect(
            line('Indigo').getByLabelText('Quantity received'),
        ).toHaveAccessibleDescription(
            'm Indigo: Enter a number above 0, with up to 4 decimals.',
        );
        expect(
            line('Indigo').getByLabelText('Quantity received'),
        ).toBeInvalid();
        expect(line('Black').getByLabelText('Unit price paid')).toBeInvalid();
        expect(
            line('Black').getByText('Black: Enter the price paid.'),
        ).toBeVisible();
        expect(
            screen.getByText(
                'The colours of Denim 12oz have changed. Enter the quantities again.',
            ),
        ).toBeVisible();

        // Nothing typed is lost, and focus goes to the first problem.
        expect(line('Indigo').getByLabelText('Quantity received')).toHaveValue(
            '0',
        );
        expect(line('Black').getByLabelText('Quantity received')).toHaveValue(
            '20',
        );
        expect(
            line('Indigo').getByLabelText('Quantity received'),
        ).toHaveFocus();
    });
});
