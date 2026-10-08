import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { HistoryPanel } from '@/components/materials/history-panel';
import { button, buttonHistory, denim } from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { StockMovement } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

/** A movement of another material that must never show up in the button's panel. */
const denimEntry: StockMovement = {
    ...buttonHistory[1],
    id: 99,
    material_id: denim.id,
    reference: 'PO-DENIM',
};

function renderPanel(
    recentHistory: StockMovement[] | undefined,
    onClose = vi.fn(),
) {
    const view = render(
        <HistoryPanel
            id="panel-1"
            material={button}
            recentHistory={recentHistory}
            showHref="/materials/3"
            onClose={onClose}
        />,
    );

    return {
        ...view,
        onClose,
        again: (next: StockMovement[] | undefined) =>
            view.rerender(
                <HistoryPanel
                    id="panel-1"
                    material={button}
                    recentHistory={next}
                    showHref="/materials/3"
                    onClose={onClose}
                />,
            ),
    };
}

describe('<HistoryPanel>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('is a named group with the title and the explanation', () => {
        setPageProps({ recent_history: buttonHistory });
        renderPanel(buttonHistory);

        const panel = screen.getByRole('group', {
            name: 'History · Metal shank button 17mm',
        });

        expect(panel).toHaveAttribute('id', 'panel-1');
        expect(
            within(panel).getByText(
                'Restocks, stock corrections and unit price changes, newest first.',
            ),
        ).toBeInTheDocument();
    });

    it('takes focus when it opens', () => {
        setPageProps({ recent_history: buttonHistory });
        renderPanel(buttonHistory);

        expect(
            screen.getByRole('group', {
                name: 'History · Metal shank button 17mm',
            }),
        ).toHaveFocus();
    });

    it('shows a loading skeleton until the deferred history arrives, then the entries', () => {
        // `recent_history` is not in the page props yet.
        const { again } = renderPanel(undefined);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Loading history…',
        );
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
        expect(screen.queryByText('No history yet.')).not.toBeInTheDocument();

        // The deferred request lands: the page props and the page's own props change.
        setPageProps({ recent_history: buttonHistory });
        again(buttonHistory);

        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        expect(screen.getAllByRole('listitem')).toHaveLength(4);
        expect(
            screen.getByText('+500 pc at €0.35 · PO-2291'),
        ).toBeInTheDocument();
    });

    it("lists only this material's entries, in the server's order", () => {
        setPageProps({ recent_history: [denimEntry, ...buttonHistory] });
        renderPanel([denimEntry, ...buttonHistory]);

        const items = screen.getAllByRole('listitem');

        expect(items).toHaveLength(4);
        expect(items[0]).toHaveTextContent('Price change');
        expect(items[3]).toHaveTextContent('Created');
        expect(screen.queryByText(/PO-DENIM/)).not.toBeInTheDocument();
    });

    it('says there is no history when the material has no entries', () => {
        setPageProps({ recent_history: [denimEntry] });
        renderPanel([denimEntry]);

        expect(screen.getByText('No history yet.')).toBeInTheDocument();
    });

    it('links to the material page', () => {
        setPageProps({ recent_history: buttonHistory });
        renderPanel(buttonHistory);

        expect(
            screen.getByRole('link', { name: 'Open material page' }),
        ).toHaveAttribute('href', '/materials/3');
    });

    it('closes from the close button and from Escape', async () => {
        const user = userEvent.setup();

        setPageProps({ recent_history: buttonHistory });

        const { onClose } = renderPanel(buttonHistory);

        await user.click(screen.getByRole('button', { name: 'Close history' }));
        await user.keyboard('{Escape}');

        expect(onClose).toHaveBeenCalledTimes(2);
    });
});
