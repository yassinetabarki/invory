import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';

function setup(props: Partial<Parameters<typeof ConfirmDeleteDialog>[0]> = {}) {
    const onOpenChange = vi.fn();
    const onConfirm = vi.fn();

    render(
        <ConfirmDeleteDialog
            open
            onOpenChange={onOpenChange}
            title="Delete Denim 12oz Indigo?"
            description={<p>This cannot be undone.</p>}
            onConfirm={onConfirm}
            {...props}
        />,
    );

    return { onOpenChange, onConfirm };
}

describe('<ConfirmDeleteDialog>', () => {
    it('shows the title and description in an alert dialog', () => {
        setup();

        expect(
            screen.getByRole('alertdialog', {
                name: 'Delete Denim 12oz Indigo?',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('This cannot be undone.')).toBeInTheDocument();
    });

    it('renders nothing when closed', () => {
        setup({ open: false });

        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
    });

    it('calls onConfirm when the destructive button is pressed', async () => {
        const user = userEvent.setup();
        const { onConfirm, onOpenChange } = setup();

        await user.click(screen.getByRole('button', { name: 'Delete' }));

        expect(onConfirm).toHaveBeenCalledOnce();
        // The page closes the dialog once the request finishes.
        expect(onOpenChange).not.toHaveBeenCalled();
    });

    it('uses a custom confirm label', () => {
        setup({ confirmLabel: 'Delete material' });

        expect(
            screen.getByRole('button', { name: 'Delete material' }),
        ).toBeInTheDocument();
    });

    it('closes without confirming on cancel', async () => {
        const user = userEvent.setup();
        const { onConfirm, onOpenChange } = setup();

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onOpenChange).toHaveBeenCalledWith(false);
        expect(onConfirm).not.toHaveBeenCalled();
    });

    it('disables both buttons while processing', () => {
        setup({ processing: true });

        expect(screen.getByRole('button', { name: 'Cancel' })).toBeDisabled();
        expect(screen.getByRole('button', { name: /delete/i })).toBeDisabled();
    });

    it('returns focus to the given element when closed', async () => {
        const user = userEvent.setup();
        const returnFocusTo = createRef<HTMLButtonElement>();
        const { rerender } = render(
            <>
                <button ref={returnFocusTo} type="button">
                    Opener
                </button>
                <ConfirmDeleteDialog
                    open
                    onOpenChange={vi.fn()}
                    title="Delete?"
                    description="Sure?"
                    returnFocusTo={returnFocusTo}
                    onConfirm={vi.fn()}
                />
            </>,
        );

        await user.keyboard('{Escape}');
        rerender(
            <>
                <button ref={returnFocusTo} type="button">
                    Opener
                </button>
                <ConfirmDeleteDialog
                    open={false}
                    onOpenChange={vi.fn()}
                    title="Delete?"
                    description="Sure?"
                    returnFocusTo={returnFocusTo}
                    onConfirm={vi.fn()}
                />
            </>,
        );

        await waitFor(() =>
            expect(
                screen.getByRole('button', { name: 'Opener' }),
            ).toHaveFocus(),
        );
    });
});
