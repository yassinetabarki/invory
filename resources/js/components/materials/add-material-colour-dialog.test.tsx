import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { AddMaterialColourDialog } from '@/components/materials/add-material-colour-dialog';
import { RestockSheetHost } from '@/components/materials/restock-sheet-host';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { black, denim, indigo, lightWash } from '@/test/fixtures';
import {
    inertiaMock,
    resetInertiaMock,
    respondToHttp,
    setFormState,
} from '@/test/inertia';
import type { Material, MaterialColourStock } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const toast = vi.hoisted(() => ({
    success: vi.fn(),
    info: vi.fn(),
    warning: vi.fn(),
    error: vi.fn(),
}));
vi.mock('sonner', () => ({ toast }));

function stock(
    colour: MaterialColourStock['colour'],
    quantity: string,
    unitCost: string,
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

/** Denim 12oz kept per colour with cost per colour; Light wash was just added, at 0 m. */
const denim12: Material = {
    ...denim,
    id: 7,
    name: 'Denim 12oz',
    quantity_on_hand: '290.0000',
    stock_mode: 'per_colour',
    cost_mode: 'per_colour',
    colours: [
        stock(indigo, '250.0000', '8.5000'),
        stock(black, '40.0000', '9.2000'),
        stock(lightWash, '0.0000', '9.0000'),
    ],
};

/** What the product page renders: the link, its window, and the app's toast host. */
function Page() {
    const [open, setOpen] = useState(false);
    useFlashToast();

    return (
        <>
            <button type="button" onClick={() => setOpen(true)}>
                Add Light wash to Denim 12oz
            </button>
            <AddMaterialColourDialog
                open={open}
                onOpenChange={setOpen}
                material={{ id: 7, name: 'Denim 12oz', unit: 'm' }}
                colour={lightWash}
                productName="Denim Jacket"
            />
            <RestockSheetHost />
        </>
    );
}

function sendFlash(data: unknown) {
    const calls = inertiaMock.router.on.mock.calls as unknown as [
        string,
        (event: CustomEvent) => void,
    ][];

    act(() => {
        calls[0][1](
            new CustomEvent('flash', { detail: { flash: { toast: data } } }),
        );
    });
}

describe('AddMaterialColourDialog', () => {
    beforeEach(() => {
        resetInertiaMock();
        vi.clearAllMocks();
        respondToHttp(() => ({ material: denim12 }));
    });

    it('asks, adds with its cost, and the toast opens the Restock sheet on that colour', async () => {
        const user = userEvent.setup();
        render(<Page />);
        const link = screen.getByRole('button', {
            name: 'Add Light wash to Denim 12oz',
        });

        // The window: title, sentence, and the required cost of a material with cost per colour.
        await user.click(link);
        const dialog = await screen.findByRole('alertdialog', {
            name: 'Add Light wash to Denim 12oz?',
        });
        expect(
            within(dialog).getByText(
                "Light wash starts at 0 m, so Denim Jacket can't be made in Light wash until you restock it.",
            ),
        ).toBeInTheDocument();
        const cost = await within(dialog).findByLabelText(
            'Unit cost of Light wash',
        );

        // The server's error sits under the field and is read with it.
        setFormState({
            errors: { unit_cost: 'Light wash: Enter a unit cost.' },
        });
        expect(cost).toHaveAccessibleDescription(
            expect.stringContaining('Light wash: Enter a unit cost.'),
        );
        setFormState({ errors: {} });

        // Cancel adds nothing and focus goes back to the link.
        await user.click(
            within(dialog).getByRole('button', { name: 'Cancel' }),
        );
        await waitFor(() => expect(link).toHaveFocus());
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        expect(inertiaMock.submissions).toHaveLength(0);

        // Add posts the colour and its cost.
        await user.click(link);
        await user.type(
            await screen.findByLabelText('Unit cost of Light wash'),
            '9',
        );
        await user.click(
            screen.getByRole('button', { name: 'Add Light wash' }),
        );
        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/materials/7/colours',
            method: 'post',
            data: { colour_id: String(lightWash.id), unit_cost: '9' },
        });
        await user.keyboard('{Escape}'); // the page closes the window once the save succeeds

        // The toast's "Restock Light wash" opens the sheet over the page, on Light wash.
        sendFlash({
            type: 'success',
            message: 'Light wash added to Denim 12oz.',
            action: {
                label: 'Restock Light wash',
                restock: {
                    material_id: 7,
                    colour_id: lightWash.id,
                    material_name: 'Denim 12oz',
                },
            },
        });
        const [message, options] = toast.success.mock.calls[0];
        expect(message).toBe('Light wash added to Denim 12oz.');
        expect(options.action.label).toBe('Restock Light wash');
        act(() => {
            options.action.onClick();
        });

        const sheet = await screen.findByRole('dialog', {
            name: 'Restock Denim 12oz',
        });
        await waitFor(() =>
            expect(
                within(
                    within(sheet).getByRole('group', { name: /light wash/i }),
                ).getByLabelText('Quantity received'),
            ).toHaveFocus(),
        );

        // AC-7: if the sheet's data can't be loaded, no sheet opens and a toast says so.
        await user.keyboard('{Escape}');
        await waitFor(() =>
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument(),
        );
        respondToHttp(() => {
            throw new Error('offline');
        });
        act(() => {
            options.action.onClick();
        });
        await waitFor(() =>
            expect(toast.error).toHaveBeenCalledWith(
                "Couldn't open Restock for Denim 12oz. Try again.",
            ),
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
});
