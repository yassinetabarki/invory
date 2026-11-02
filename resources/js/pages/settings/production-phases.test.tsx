import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactElement } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { TooltipProvider } from '@/components/ui/tooltip';
import ProductionPhases from '@/pages/settings/production-phases';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { ProductionPhase } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const SEWING_HOLDS =
    "Sewing holds 15 units, so it can't be moved or removed. Move them on or back first.";

const sewing: ProductionPhase = {
    id: 1,
    name: 'Sewing',
    position: 1,
    units: 15,
    refusal: SEWING_HOLDS,
};
const finishing: ProductionPhase = {
    id: 2,
    name: 'Finishing',
    position: 2,
    units: 4,
    refusal:
        "Finishing holds 4 units, so it can't be moved or removed. Move them on or back first.",
};
const check: ProductionPhase = {
    id: 3,
    name: 'Quality check',
    position: 3,
    units: 0,
    refusal: null,
};

function renderPage(phases: ProductionPhase[]) {
    const ui: ReactElement = (
        <TooltipProvider>
            <ProductionPhases phases={phases} />
        </TooltipProvider>
    );

    return { user: userEvent.setup(), ...render(ui) };
}

describe('settings/production-phases page (INV-5, AC-1)', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/settings/production-phases' });
    });

    it('shows the whole path: In cutting, the phases with what they hold, Put in stock', () => {
        renderPage([sewing, finishing]);

        expect(
            screen.getByRole('heading', {
                name: 'Production phases',
                level: 2,
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The phases a batch goes through between cutting and putting it in stock. In cutting and Put in stock are always there.',
            ),
        ).toBeInTheDocument();

        const steps = within(
            screen.getByRole('list', { name: 'The path of a batch' }),
        ).getAllByRole('listitem');

        expect(
            steps.map((step) => step.querySelector('p')?.textContent),
        ).toEqual(['In cutting', 'Sewing', 'Finishing', 'Put in stock']);
        expect(steps[0]).toHaveTextContent(
            'Always there. Start batch puts a batch here and takes the materials.',
        );
        expect(steps[1]).toHaveTextContent('Holds 15 units');
        expect(steps[3]).toHaveTextContent(
            'Always there. Ends a batch: its units are added to your finished stock.',
        );
    });

    it('says when the workshop has no phase of its own', () => {
        renderPage([]);

        expect(
            screen.getByText(
                'No phase of your own. Batches go from In cutting straight to Put in stock.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Add phase' }),
        ).toBeInTheDocument();
    });

    it("keeps a phase that holds units in place: its buttons and its neighbour's can't be pressed, with the reason", async () => {
        const { user } = renderPage([sewing, check]);

        const remove = screen.getByRole('button', { name: 'Remove Sewing' });
        expect(remove).toHaveAttribute('aria-disabled', 'true');
        expect(remove).toHaveAccessibleDescription(SEWING_HOLDS);

        const steps = within(
            screen.getByRole('list', { name: 'The path of a batch' }),
        ).getAllByRole('listitem');
        const checkUp = within(steps[2]).getByRole('button', {
            name: 'Move up',
        });
        expect(checkUp).toHaveAccessibleDescription(SEWING_HOLDS);

        await user.click(remove);
        await user.click(checkUp);
        expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        expect(inertiaMock.router.patch).not.toHaveBeenCalled();

        // The first can't go up, the last can't go down.
        expect(
            within(steps[1]).getByRole('button', { name: 'Move up' }),
        ).toBeDisabled();
        expect(
            within(steps[2]).getByRole('button', { name: 'Move down' }),
        ).toBeDisabled();
    });

    it('moves an empty phase between empty phases', async () => {
        const empty = { ...sewing, units: 0, refusal: null };
        const { user } = renderPage([empty, check]);

        const steps = within(
            screen.getByRole('list', { name: 'The path of a batch' }),
        ).getAllByRole('listitem');
        await user.click(
            within(steps[2]).getByRole('button', { name: 'Move up' }),
        );

        expect(inertiaMock.router.patch).toHaveBeenCalledWith(
            '/settings/production-phases/3/position',
            { direction: 'up' },
            expect.anything(),
        );
    });

    it('asks before removing an empty phase, saying what the path becomes', async () => {
        const { user } = renderPage([sewing, check, finishing]);

        await user.click(
            screen.getByRole('button', { name: 'Remove Quality check' }),
        );

        const confirm = within(
            screen.getByRole('alertdialog', { name: 'Remove Quality check?' }),
        );
        expect(
            confirm.getByText(
                'Quality check is empty. It leaves Progress; batches then go from Sewing to Finishing.',
            ),
        ).toBeInTheDocument();
        expect(
            confirm.getByRole('button', { name: 'Keep phase' }),
        ).toBeInTheDocument();

        await user.click(confirm.getByRole('button', { name: 'Remove phase' }));

        expect(inertiaMock.router.delete).toHaveBeenCalledWith(
            '/settings/production-phases/3',
            expect.anything(),
        );
    });

    it("adds a phase with Name and its hint, and shows the server's error under it", async () => {
        const { user } = renderPage([sewing]);

        await user.click(screen.getByRole('button', { name: 'Add phase' }));

        const dialog = within(
            screen.getByRole('dialog', { name: 'Add phase' }),
        );
        const name = dialog.getByLabelText(/^Name/);
        expect(name).toHaveAccessibleDescription(
            'A short verb or noun your team uses, such as Sewing or Quality check.',
        );

        await user.type(name, 'Quality check');
        await user.click(dialog.getByRole('button', { name: 'Save phase' }));

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/settings/production-phases',
            method: 'post',
            data: { name: 'Quality check' },
        });

        setFormState({
            errors: { name: 'You already have a phase named "Sewing".' },
        });

        expect(
            await dialog.findByText('You already have a phase named "Sewing".'),
        ).toBeInTheDocument();
    });

    it('renames a phase in "Edit Sewing"', async () => {
        const { user } = renderPage([sewing]);

        await user.click(screen.getByRole('button', { name: 'Edit Sewing' }));

        const dialog = within(
            screen.getByRole('dialog', { name: 'Edit Sewing' }),
        );
        expect(dialog.getByLabelText(/^Name/)).toHaveValue('Sewing');

        await user.click(dialog.getByRole('button', { name: 'Save phase' }));

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/settings/production-phases/1',
            method: 'put',
        });
    });
});
