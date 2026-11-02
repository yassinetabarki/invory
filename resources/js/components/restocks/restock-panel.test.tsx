import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { RestockPanel } from '@/components/restocks/restock-panel';
import { bl0412, bl0412Detail, tote6 } from '@/test/fixtures/restocks';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { RestockDetail } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const REFUSAL =
    "This restock can't be undone: Denim Jacket in Indigo, size M, has 2 units on the shelf, fewer than the 8 it added. Set the right count with Update stock instead.";

function renderPanel(
    restock: RestockDetail | null,
    { failed = false, pending = bl0412 } = {},
) {
    const onClose = vi.fn();
    const onRetry = vi.fn();

    render(
        <RestockPanel
            restock={restock}
            pending={pending}
            open
            failed={failed}
            onClose={onClose}
            onRetry={onRetry}
        />,
    );

    return { onClose, onRetry, panel: within(screen.getByRole('dialog')) };
}

describe('<RestockPanel>', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/restocks?restock=3' });
    });

    it('shows the restock: title, who and when, figures, note and each product per colour and size (AC-5)', () => {
        const { panel } = renderPanel(bl0412Detail);

        expect(
            panel.getByRole('heading', { name: 'Restock · 25 Oct 2026' }),
        ).toBeInTheDocument();
        expect(
            panel.getByText(
                'BL-0412 · recorded by Admin on 25 Oct 2026 at 14:32',
            ),
        ).toBeInTheDocument();
        expect(panel.getByText('34 units')).toBeInTheDocument();
        expect(panel.getByText(/· 2 products/)).toBeInTheDocument();
        expect(
            panel.getByText(
                'Batch sewn by Atelier Nour, delivered with the invoice.',
            ),
        ).toBeInTheDocument();
        expect(
            panel.getByRole('link', { name: 'Denim Jacket' }),
        ).toHaveAttribute('href', '/products/1');

        const jacket = within(
            panel.getByRole('table', { name: 'Units added · Denim Jacket' }),
        );
        const rows = jacket.getAllByRole('row').map((row) =>
            within(row)
                .queryAllByRole('cell')
                .map((cell) => cell.textContent),
        );

        expect(rows.slice(1)).toEqual([
            ['Black', '0', '4', '0', '0', '4'],
            ['Indigo', '4', '8', '6', '2', '20'],
            ['Total', '4', '12', '6', '2', '24'],
        ]);

        const jeans = within(
            panel.getByRole('table', {
                name: 'Units added · Denim Jeans 5-pocket',
            }),
        );
        expect(jeans.getAllByRole('row')).toHaveLength(2);
    });

    it('asks before undoing, then posts the undo; a refusal shows in red in the confirm (AC-6)', async () => {
        const user = userEvent.setup();
        const { panel } = renderPanel(bl0412Detail);

        await user.click(panel.getByRole('button', { name: 'Undo restock' }));

        const confirm = within(screen.getByRole('alertdialog'));
        expect(
            confirm.getByRole('heading', { name: 'Undo this restock?' }),
        ).toBeInTheDocument();
        expect(
            confirm.getByText(
                'Takes the 34 units of 25 Oct 2026 (BL-0412) back off the shelf: Denim Jacket 24, Denim Jeans 5-pocket 10. The restock stays in the list, marked Undone.',
            ),
        ).toBeInTheDocument();
        expect(
            confirm.getByRole('button', { name: 'Keep restock' }),
        ).toBeInTheDocument();

        await user.click(confirm.getByRole('button', { name: 'Undo restock' }));

        expect(inertiaMock.router.post).toHaveBeenCalledWith(
            '/restocks/3/undo',
            {},
            expect.objectContaining({ preserveState: true }),
        );

        const options = inertiaMock.router.post.mock.lastCall?.[2] as {
            onError: (errors: Record<string, string>) => void;
        };
        options.onError({ undo: REFUSAL });

        expect(await screen.findByRole('alert')).toHaveTextContent(REFUSAL);
    });

    it('words the confirm without a reference', async () => {
        const user = userEvent.setup();
        const { panel } = renderPanel(
            {
                ...bl0412Detail,
                ...tote6,
                note: null,
                products_count: 1,
                blocks: [
                    {
                        ...bl0412Detail.blocks[0],
                        product: {
                            ...bl0412Detail.blocks[0].product,
                            name: 'Leather Tote',
                        },
                        units: 6,
                    },
                ],
            },
            { pending: tote6 },
        );

        await user.click(panel.getByRole('button', { name: 'Undo restock' }));

        expect(
            within(screen.getByRole('alertdialog')).getByText(
                'Takes the 6 units of 22 Oct 2026 back off the shelf: Leather Tote 6. The restock stays in the list, marked Undone.',
            ),
        ).toBeInTheDocument();
    });

    it("can't undo while the shelf holds fewer units: the reason shows and is part of the button's name (AC-6)", async () => {
        const user = userEvent.setup();
        const { panel } = renderPanel({
            ...bl0412Detail,
            undo_refusal: REFUSAL,
        });

        const button = panel.getByRole('button', {
            name: `Undo restock. ${REFUSAL}`,
        });

        expect(button).toHaveAttribute('aria-disabled', 'true');
        expect(panel.getByText(REFUSAL)).toBeInTheDocument();

        button.focus();
        expect(button).toHaveFocus();

        await user.click(button);
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
    });

    it('shows an undone restock: the badge, who undid it, and no Undo (AC-6)', () => {
        const { panel } = renderPanel({
            ...bl0412Detail,
            undone: true,
            undone_at: '2026-10-26T09:10',
            undone_by: 'Sami',
        });

        expect(panel.getByText('Undone')).toBeInTheDocument();
        expect(
            panel.getByText(
                'Undone by Sami on 26 Oct 2026. Its 34 units were taken back off the shelf.',
            ),
        ).toBeInTheDocument();
        expect(
            panel.queryByRole('button', { name: /Undo restock/ }),
        ).not.toBeInTheDocument();
    });

    it('names a deleted product without a link (AC-5)', () => {
        const { panel } = renderPanel({
            ...bl0412Detail,
            blocks: [
                {
                    ...bl0412Detail.blocks[0],
                    product: {
                        ...bl0412Detail.blocks[0].product,
                        deleted: true,
                    },
                },
            ],
        });

        expect(panel.getByText('Denim Jacket (deleted)')).toBeInTheDocument();
        expect(
            panel.queryByRole('link', { name: /Denim Jacket/ }),
        ).not.toBeInTheDocument();
    });

    it('shows the title and figures at once while loading', () => {
        const loading = renderPanel(null);

        expect(
            loading.panel.getByRole('heading', {
                name: 'Restock · 25 Oct 2026',
            }),
        ).toBeInTheDocument();
        expect(loading.panel.getByText('34 units')).toBeInTheDocument();
        expect(
            loading.panel.getByLabelText('Loading restock'),
        ).toBeInTheDocument();
    });

    it('says "Couldn\'t load this restock." with Try again, and Close closes', async () => {
        const user = userEvent.setup();
        const { panel, onRetry, onClose } = renderPanel(null, { failed: true });

        expect(
            panel.getByText("Couldn't load this restock."),
        ).toBeInTheDocument();

        await user.click(panel.getByRole('button', { name: 'Try again' }));
        expect(onRetry).toHaveBeenCalled();

        await user.click(panel.getAllByRole('button', { name: 'Close' })[0]);
        expect(onClose).toHaveBeenCalled();
    });
});
