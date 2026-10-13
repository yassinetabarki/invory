import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialActions } from '@/components/materials/material-actions';
import { button, denim } from '@/test/fixtures';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { Material } from '@/types/domain';

// What the restock dialog hands to Inertia's <Form>: the mock ignores
// `onSuccess`, so a thin wrapper records it for the tests.
const received = vi.hoisted(() => ({
    onSuccess: undefined as unknown,
}));

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');
    const inertia = createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
    const MockForm = inertia.Form;

    return {
        ...inertia,
        Form: (props: Parameters<typeof MockForm>[0]) => {
            received.onSuccess = props.onSuccess;

            return <MockForm {...props} />;
        },
    };
});

/** One product uses it: the singular of the reason. */
const usedOnce: Material = { ...denim, used_in_count: 1 };

describe('<MaterialActions>', () => {
    beforeEach(() => {
        resetInertiaMock();
        received.onSuccess = undefined;
    });

    describe('Edit', () => {
        it('does not prefetch the edit page', () => {
            render(<MaterialActions material={denim} />);

            const link = screen.getByRole('link', { name: 'Edit' });

            expect(link).not.toHaveAttribute('data-prefetch');
        });

        it('links to the edit page', () => {
            render(<MaterialActions material={denim} />);

            expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
                'href',
                '/materials/1/edit',
            );
        });
    });

    describe('Restock', () => {
        it('opens the restock form in a dialog named after the material', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={button} />);

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

            await user.click(screen.getByRole('button', { name: 'Restock' }));

            const dialog = screen.getByRole('dialog', {
                name: 'Restock Metal shank button 17mm',
            });

            expect(dialog).toHaveTextContent('In stock now: 900 pc');
            expect(dialog.querySelector('form')).toHaveAttribute(
                'action',
                '/materials/3/restocks',
            );
            expect(
                within(dialog).getByLabelText('Quantity received'),
            ).toBeInTheDocument();
            expect(
                within(dialog).getByRole('button', { name: 'Add to stock' }),
            ).toBeInTheDocument();
        });

        it('closes with Cancel and gives focus back to the Restock button', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={button} />);

            const opener = screen.getByRole('button', { name: 'Restock' });

            await user.click(opener);
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            await waitFor(() => expect(opener).toHaveFocus());
        });

        it('closes with Escape and gives focus back to the Restock button', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={button} />);

            const opener = screen.getByRole('button', { name: 'Restock' });

            await user.click(opener);
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            await waitFor(() => expect(opener).toHaveFocus());
        });

        it('closes once the server accepted the restock', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={button} />);

            await user.click(screen.getByRole('button', { name: 'Restock' }));

            expect(received.onSuccess).toBeTypeOf('function');

            act(() => {
                (received.onSuccess as () => void)();
            });

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });

        it('stays available for a material that is in use', () => {
            render(<MaterialActions material={button} />);

            expect(
                screen.getByRole('button', { name: 'Restock' }),
            ).toBeEnabled();
        });
    });

    describe('Delete of a material that no product uses', () => {
        it('is enabled and gives no reason', () => {
            render(<MaterialActions material={denim} />);

            const remove = screen.getByRole('button', { name: 'Delete' });

            expect(remove).toBeEnabled();
            expect(remove).not.toHaveAttribute('aria-describedby');
            expect(
                screen.queryByText(/can't be deleted/),
            ).not.toBeInTheDocument();
        });

        it('opens the confirmation dialog without deleting yet', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={denim} />);

            await user.click(screen.getByRole('button', { name: 'Delete' }));

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete material?',
            });

            expect(within(dialog).getByText(/Denim 12oz Indigo/)).toBeVisible();
            expect(within(dialog).getByText(/SKU FAB-001/)).toBeVisible();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('closes with Cancel or Escape and deletes nothing', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={denim} />);

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('gives focus back to the Delete button when the dialog is dismissed', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={denim} />);

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.keyboard('{Escape}');

            await waitFor(() =>
                expect(
                    screen.getByRole('button', { name: 'Delete' }),
                ).toHaveFocus(),
            );
        });

        it('deletes through the destroy URL, shows progress, then closes', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={denim} />);

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.click(
                screen.getByRole('button', { name: 'Delete material' }),
            );

            expect(inertiaMock.router.delete).toHaveBeenCalledOnce();
            expect(inertiaMock.router.delete).toHaveBeenCalledWith(
                '/materials/1',
                expect.objectContaining({
                    onStart: expect.any(Function),
                    onFinish: expect.any(Function),
                }),
            );

            const [, options] = inertiaMock.router.delete.mock.calls[0] as [
                string,
                { onStart: () => void; onFinish: () => void },
            ];

            act(() => options.onStart());

            expect(
                screen.getByRole('button', { name: /delete material/i }),
            ).toBeDisabled();
            expect(
                screen.getByRole('button', { name: 'Cancel' }),
            ).toBeDisabled();

            act(() => options.onFinish());

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });
    });

    describe('Delete of a material that products use', () => {
        it('is disabled, with the reason next to it and linked by aria-describedby', () => {
            render(<MaterialActions material={button} />);

            const remove = screen.getByRole('button', { name: 'Delete' });
            const reason = screen.getByText(
                "Used in 3 products, so it can't be deleted.",
            );

            expect(remove).toBeDisabled();
            expect(reason).toBeVisible();
            expect(remove).toHaveAttribute('aria-describedby', reason.id);
            expect(remove).toHaveAccessibleDescription(
                "Used in 3 products, so it can't be deleted.",
            );
        });

        it('uses the singular when a single product uses it', () => {
            render(<MaterialActions material={usedOnce} />);

            expect(
                screen.getByText("Used in 1 product, so it can't be deleted."),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Delete' }),
            ).toBeDisabled();
        });

        it('cannot open the confirmation dialog', async () => {
            const user = userEvent.setup();

            render(<MaterialActions material={button} />);

            await user.click(screen.getByRole('button', { name: 'Delete' }));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });
    });
});
