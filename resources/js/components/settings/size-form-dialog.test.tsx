import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SizeFormDialog } from '@/components/settings/size-form-dialog';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { SizeRow } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function sizeRow(label: string, usage_count: number, one_size = false) {
    return { id: 7, label, position: 70, usage_count, one_size } as SizeRow;
}

function renderDialog(size: SizeRow | null = null) {
    const onOpenChange = vi.fn();

    render(<SizeFormDialog open onOpenChange={onOpenChange} size={size} />);

    return {
        user: userEvent.setup(),
        onOpenChange,
        dialog: within(
            screen.getByRole('dialog', {
                name: size ? `Edit ${size.label}` : 'Add size',
            }),
        ),
    };
}

describe('SizeFormDialog', () => {
    beforeEach(() => resetInertiaMock());

    it('adds a size: the name is capped at 12, the position hint is read, a spinner shows while saving, and it closes on success', async () => {
        const { user, dialog, onOpenChange } = renderDialog();

        expect(dialog.getByLabelText(/^Name/)).toHaveAttribute(
            'maxlength',
            '12',
        );
        expect(dialog.getByLabelText(/^Position/)).toHaveAccessibleName(
            'Position (optional)',
        );
        expect(dialog.getByLabelText(/^Position/)).toHaveAccessibleDescription(
            'Lower numbers are listed first. Leave empty to list it last.',
        );

        await user.type(dialog.getByLabelText(/^Name/), 'XXS');
        await user.type(dialog.getByLabelText(/^Position/), '15');
        await user.click(dialog.getByRole('button', { name: 'Save size' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/settings/sizes',
            method: 'post',
            options: { preserveScroll: true },
        });
        expect(inertiaMock.submissions[0].data).toEqual({
            label: 'XXS',
            position: '15',
        });

        const sent = inertiaMock.submissions[0].options as {
            onSuccess: () => void;
        };

        act(() => sent.onSuccess());

        expect(onOpenChange).toHaveBeenCalledWith(false);

        setFormState({ processing: true });

        const save = dialog.getByRole('button', { name: /Save size/ });

        expect(save).toBeDisabled();
        expect(
            within(save).getByRole('status', { name: 'Loading' }),
        ).toBeInTheDocument();
    });

    it('shows the server errors under their fields, focuses the first wrong one and stays open', () => {
        setFormState({
            errors: {
                label: 'You already have a size named "XL".',
                position:
                    'Enter a whole number from 0 to 32,767, or leave it empty.',
            },
        });

        const { dialog, onOpenChange } = renderDialog();

        expect(dialog.getByLabelText(/^Name/)).toBeInvalid();
        expect(dialog.getByLabelText(/^Name/)).toHaveAccessibleDescription(
            'You already have a size named "XL".',
        );
        expect(dialog.getByLabelText(/^Position/)).toHaveAccessibleDescription(
            expect.stringContaining(
                'Enter a whole number from 0 to 32,767, or leave it empty.',
            ),
        );
        expect(dialog.getByLabelText(/^Name/)).toHaveFocus();
        expect(onOpenChange).not.toHaveBeenCalled();
    });

    it('edits a size: it pre-fills the fields, sends the update, and a size no product uses has no usage line', async () => {
        const { user, dialog } = renderDialog(sizeRow('XXS', 0));

        expect(dialog.getByLabelText(/^Name/)).toHaveValue('XXS');
        expect(dialog.getByLabelText(/^Position/)).toHaveValue('70');
        expect(
            dialog.getByLabelText(/^Name/),
        ).not.toHaveAccessibleDescription();

        await user.clear(dialog.getByLabelText(/^Name/));
        await user.type(dialog.getByLabelText(/^Name/), '2XS');
        await user.click(dialog.getByRole('button', { name: 'Save size' }));

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/settings/sizes/7',
            method: 'put',
        });
        expect(inertiaMock.submissions[0].data).toEqual({
            label: '2XS',
            position: '70',
        });
    });

    it.each([
        [
            sizeRow('XXL', 3),
            "A new name shows on the 3 products that use XXL, and in their variant SKUs. Barcodes don't change.",
        ],
        [
            sizeRow('XXL', 1),
            "A new name shows on the 1 product that uses XXL, and in its variant SKUs. Barcodes don't change.",
        ],
        [
            sizeRow('36', 1),
            "A new name shows on the 1 product that uses size 36, and in its variant SKUs. Barcodes don't change.",
        ],
    ])('tells how many products a new name reaches (%#)', (size, line) => {
        const { dialog } = renderDialog(size);

        expect(dialog.getByLabelText(/^Name/)).toHaveAccessibleDescription(
            line,
        );
    });

    it('explains the One size row under the title, and only that row', () => {
        const line =
            'The size for products without sizes. A product that has it has no other size.';
        const { dialog } = renderDialog(sizeRow('One size', 2, true));

        expect(dialog.getByText(line)).toBeVisible();
        expect(screen.getByRole('dialog')).toHaveAccessibleDescription(line);
    });
});
