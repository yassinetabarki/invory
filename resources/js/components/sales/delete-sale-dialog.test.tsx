import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { DeleteSaleDialog } from '@/components/sales/delete-sale-dialog';
import { recentSales } from '@/test/fixtures/sales';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const openers: HTMLElement[] = [];

function createOpener() {
    const opener = document.createElement('button');

    opener.textContent = 'Delete sale of Denim Jeans 5-pocket';
    document.body.append(opener);
    openers.push(opener);

    return { current: opener };
}

function renderDialog(
    props: Partial<ComponentProps<typeof DeleteSaleDialog>> = {},
) {
    const onCancel = vi.fn();
    const onConfirm = vi.fn();
    const returnFocusTo = createOpener();
    const user = userEvent.setup();

    const view = render(
        <DeleteSaleDialog
            sale={recentSales[0]}
            processing={false}
            returnFocusTo={returnFocusTo}
            onCancel={onCancel}
            onConfirm={onConfirm}
            {...props}
        />,
    );

    return { onCancel, onConfirm, returnFocusTo, user, ...view };
}

describe('<DeleteSaleDialog>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    afterEach(() => {
        for (const opener of openers.splice(0)) {
            opener.remove();
        }
    });

    it('is closed while there is no sale to delete', () => {
        renderDialog({ sale: null });

        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
    });

    it('names the sale it would remove', () => {
        renderDialog();

        const dialog = screen.getByRole('alertdialog', {
            name: 'Delete sale?',
        });

        expect(dialog).toHaveTextContent(
            'The sale of 6 × Denim Jeans 5-pocket on 7 Oct 2026 will be removed from the sales log and the dashboard totals.',
        );
    });

    it('promises that material stock is not affected and that it cannot be undone', () => {
        renderDialog();

        const dialog = screen.getByRole('alertdialog');

        expect(dialog).toHaveTextContent('Material stock is not affected.');
        expect(dialog).toHaveTextContent('This cannot be undone.');
    });

    it('groups thousands and follows the locale for dates', () => {
        setPageProps({
            app: { currency: 'EUR', locale: 'de', today: '2026-10-08' },
        });

        renderDialog({
            sale: { ...recentSales[0], quantity: 1250, sold_on: '2026-03-04' },
        });

        expect(screen.getByRole('alertdialog')).toHaveTextContent(
            '1.250 × Denim Jeans 5-pocket on 4. März 2026',
        );
    });

    it('offers Cancel and a destructive Delete sale', () => {
        renderDialog();

        const dialog = screen.getByRole('alertdialog');

        expect(
            within(dialog).getByRole('button', { name: 'Cancel' }),
        ).toBeEnabled();
        expect(
            within(dialog).getByRole('button', { name: 'Delete sale' }),
        ).toBeEnabled();
    });

    it('asks the page to delete on Delete sale, without closing by itself', async () => {
        const { user, onConfirm, onCancel } = renderDialog();

        await user.click(screen.getByRole('button', { name: 'Delete sale' }));

        expect(onConfirm).toHaveBeenCalledOnce();
        expect(onCancel).not.toHaveBeenCalled();
        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
    });

    it('cancels on Cancel without deleting', async () => {
        const { user, onConfirm, onCancel } = renderDialog();

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onCancel).toHaveBeenCalledOnce();
        expect(onConfirm).not.toHaveBeenCalled();
    });

    it('cancels on Escape without deleting', async () => {
        const { user, onConfirm, onCancel } = renderDialog();

        await user.keyboard('{Escape}');

        expect(onCancel).toHaveBeenCalledOnce();
        expect(onConfirm).not.toHaveBeenCalled();
    });

    it('hands focus back to the control that opened it', async () => {
        const { user, returnFocusTo, rerender, onCancel, onConfirm } =
            renderDialog();

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        // The page clears `sale` in response to onCancel.
        rerender(
            <DeleteSaleDialog
                sale={null}
                processing={false}
                returnFocusTo={returnFocusTo}
                onCancel={onCancel}
                onConfirm={onConfirm}
            />,
        );

        await waitFor(() => expect(returnFocusTo.current).toHaveFocus());
    });

    describe('while the request runs', () => {
        it('disables both buttons and shows a spinner', () => {
            renderDialog({ processing: true });

            const dialog = screen.getByRole('alertdialog');

            expect(
                within(dialog).getByRole('button', { name: 'Cancel' }),
            ).toBeDisabled();

            const confirm = within(dialog).getByRole('button', {
                name: /Delete sale/,
            });

            expect(confirm).toBeDisabled();
            expect(within(confirm).getByRole('status')).toBeInTheDocument();
        });

        it('ignores Escape', async () => {
            const { user, onCancel } = renderDialog({ processing: true });

            await user.keyboard('{Escape}');

            expect(onCancel).not.toHaveBeenCalled();
            expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        });
    });
});
