import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ColourFormDialog } from '@/components/settings/colour-form-dialog';
import { colourNamed } from '@/test/fixtures/colours';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { ColourRow } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderDialog(colour: ColourRow | null = null) {
    const onOpenChange = vi.fn();

    const { unmount } = render(
        <ColourFormDialog open onOpenChange={onOpenChange} colour={colour} />,
    );

    const title = colour ? `Edit ${colour.name}` : 'Add colour';

    return {
        unmount,
        user: userEvent.setup(),
        onOpenChange,
        dialog: within(screen.getByRole('dialog', { name: title })),
    };
}

/** The swatches of the two preview strips. */
function previewSwatches(): Element[] {
    return Array.from(
        screen
            .getByRole('group', { name: 'Preview' })
            .querySelectorAll('[data-slot="colour-swatch"]'),
    );
}

describe('ColourFormDialog', () => {
    beforeEach(() => resetInertiaMock());

    it('adds a colour: capitals in the swatch, a preview that follows, a spinner while saving, Cancel saves nothing', async () => {
        const { user, dialog, onOpenChange } = renderDialog();

        expect(dialog.getByLabelText(/^Code/)).toHaveAccessibleDescription(
            "2 to 4 capital letters or digits, such as IND. It can't be changed later.",
        );
        expect(dialog.getByLabelText(/^Swatch/)).toHaveAccessibleDescription(
            'Pick the colour, or type its code, such as #3E4C7E.',
        );
        expect(dialog.getByLabelText(/^Position/)).toHaveAccessibleDescription(
            'Lower numbers are listed first. Leave empty to list it last.',
        );
        expect(screen.getByText('On a light screen')).toBeInTheDocument();
        expect(screen.getByText('On a dark screen')).toBeInTheDocument();

        const swatch = dialog.getByLabelText(/^Swatch/);

        await user.type(dialog.getByLabelText(/^Name/), 'Stone wash');
        await user.type(dialog.getByLabelText(/^Code/), 'stw');
        await user.type(swatch, '#b7c3cf');

        expect(swatch).toHaveValue('#B7C3CF');
        expect(previewSwatches()).toHaveLength(2);

        for (const preview of previewSwatches()) {
            expect(preview).toHaveStyle({ backgroundColor: '#B7C3CF' });
        }

        expect(
            within(screen.getByRole('group', { name: 'Preview' })).getAllByText(
                'Stone wash',
            ),
        ).toHaveLength(2);

        // A text that is not a colour code yet keeps the last good preview.
        await user.clear(swatch);
        await user.type(swatch, '#12');

        expect(previewSwatches()[0]).toHaveStyle({
            backgroundColor: '#B7C3CF',
        });

        // Without "#", and the colour well, both end as capitals with "#".
        await user.clear(swatch);
        await user.type(swatch, 'b7c3cf');

        expect(swatch).toHaveValue('#B7C3CF');

        fireEvent.change(dialog.getByLabelText('Pick swatch colour'), {
            target: { value: '#112233' },
        });

        expect(swatch).toHaveValue('#112233');
        expect(previewSwatches()[1]).toHaveStyle({
            backgroundColor: '#112233',
        });

        await user.clear(swatch);
        await user.type(swatch, 'B7C3CF');
        await user.click(dialog.getByRole('button', { name: 'Save colour' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/settings/colours',
            method: 'post',
            options: { preserveScroll: true },
        });
        expect(inertiaMock.submissions[0].data).toEqual({
            code: 'stw',
            name: 'Stone wash',
            swatch: '#B7C3CF',
        });

        // The window closes when the server accepts it.
        const sent = inertiaMock.submissions[0].options as {
            onSuccess: () => void;
        };

        act(() => sent.onSuccess());

        expect(onOpenChange).toHaveBeenCalledWith(false);

        setFormState({ processing: true });

        const save = dialog.getByRole('button', { name: /Save colour/ });

        expect(save).toBeDisabled();
        expect(
            within(save).getByRole('status', { name: 'Loading' }),
        ).toBeInTheDocument();

        setFormState({ processing: false });
        onOpenChange.mockClear();
        inertiaMock.submissions.length = 0;

        await user.click(dialog.getByRole('button', { name: 'Cancel' }));

        expect(onOpenChange).toHaveBeenCalledWith(false);
        expect(inertiaMock.submissions).toHaveLength(0);
    });

    it('keeps the title and the buttons in view: the window is capped at the screen and only the fields scroll', () => {
        renderDialog();

        const scrolling = screen
            .getByLabelText(/^Name/)
            .closest('.overflow-y-auto');

        expect(screen.getByRole('dialog')).toHaveClass(
            'max-h-[calc(100dvh-2rem)]',
        );
        expect(scrolling).not.toBeNull();
        expect(screen.getByLabelText(/^Position/)).toBeInTheDocument();
        expect(scrolling).toContainElement(screen.getByLabelText(/^Position/));

        for (const outside of [
            screen.getByRole('heading', { name: 'Add colour' }),
            screen.getByRole('button', { name: 'Cancel' }),
            screen.getByRole('button', { name: 'Save colour' }),
        ]) {
            expect(scrolling).not.toContainElement(outside);
        }
    });

    it('describes the colour well by the hint and the error, and never previews a lone swatch', async () => {
        const { user, dialog } = renderDialog();
        const well = dialog.getByLabelText('Pick swatch colour');
        const preview = within(screen.getByRole('group', { name: 'Preview' }));

        expect(well).toHaveAccessibleDescription(
            'Pick the colour, or type its code, such as #3E4C7E.',
        );
        // No name yet: the grey word stands next to the swatch, on both strips.
        expect(preview.getAllByText('Name')).toHaveLength(2);

        await user.type(dialog.getByLabelText(/^Name/), 'Stone wash');
        expect(preview.queryByText('Name')).toBeNull();
        expect(preview.getAllByText('Stone wash')).toHaveLength(2);
    });

    it('also describes the colour well by the swatch error', () => {
        setFormState({
            errors: { swatch: 'Enter a colour code like #3E4C7E.' },
        });

        const { dialog } = renderDialog();

        expect(
            dialog.getByLabelText('Pick swatch colour'),
        ).toHaveAccessibleDescription(
            'Pick the colour, or type its code, such as #3E4C7E. Enter a colour code like #3E4C7E.',
        );
    });

    it('shows the server errors under their fields, focuses the first wrong one and stays open', () => {
        setFormState({
            errors: {
                name: "Enter the colour's name.",
                code: 'Enter 2 to 4 capital letters or digits, such as IND.',
                position:
                    'Enter a whole number from 0 to 32,767, or leave it empty.',
            },
        });

        const { dialog, onOpenChange } = renderDialog();

        expect(dialog.getByLabelText(/^Name/)).toBeInvalid();
        expect(dialog.getByLabelText(/^Name/)).toHaveAccessibleDescription(
            "Enter the colour's name.",
        );
        expect(dialog.getByLabelText(/^Code/)).toHaveAccessibleDescription(
            expect.stringContaining(
                'Enter 2 to 4 capital letters or digits, such as IND.',
            ),
        );
        expect(dialog.getByLabelText(/^Position/)).toHaveAccessibleDescription(
            expect.stringContaining(
                'Enter a whole number from 0 to 32,767, or leave it empty.',
            ),
        );
        expect(dialog.getByLabelText(/^Swatch/)).toBeValid();
        expect(dialog.getByLabelText(/^Name/)).toHaveFocus();
        expect(onOpenChange).not.toHaveBeenCalled();
    });

    it('edits a colour: no Code field, the usage sentence only when products use it, and it sends the update', async () => {
        const indigo = colourNamed('Indigo');
        const { user, dialog, unmount } = renderDialog(indigo);

        expect(dialog.getByText("Code IND can't be changed.")).toBeVisible();
        expect(dialog.queryByLabelText(/^Code/)).not.toBeInTheDocument();
        expect(dialog.getByLabelText(/^Name/)).toHaveValue('Indigo');
        expect(dialog.getByLabelText(/^Swatch/)).toHaveValue('#3E4C7E');
        expect(dialog.getByLabelText(/^Position/)).toHaveValue('10');
        expect(dialog.getByLabelText(/^Swatch/)).toHaveAccessibleDescription(
            'Pick the colour, or type its code, such as #3E4C7E. A new name or swatch shows on the 3 products that use Indigo.',
        );

        await user.clear(dialog.getByLabelText(/^Name/));
        await user.type(dialog.getByLabelText(/^Name/), 'Indigo blue');
        await user.click(dialog.getByRole('button', { name: 'Save colour' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: `/settings/colours/${indigo.id}`,
            method: 'put',
        });
        expect(inertiaMock.submissions[0].data).toEqual({
            name: 'Indigo blue',
            swatch: '#3E4C7E',
            position: '10',
        });

        // A colour no product uses has no usage sentence.
        unmount();

        const ecru = renderDialog(colourNamed('Ecru'));

        expect(
            ecru.dialog.getByLabelText(/^Swatch/),
        ).toHaveAccessibleDescription(
            'Pick the colour, or type its code, such as #3E4C7E.',
        );
    });
});
