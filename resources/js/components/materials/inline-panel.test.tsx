import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { InlinePanel } from '@/components/materials/inline-panel';

function renderPanel(
    overrides: Partial<Parameters<typeof InlinePanel>[0]> = {},
) {
    const onClose = vi.fn();

    render(
        <InlinePanel
            id="panel-1"
            title="Restock Denim"
            description="In stock now: 250 m"
            closeLabel="Close restock"
            onClose={onClose}
            {...overrides}
        >
            <input aria-label="Quantity" />
            <button type="button">Save</button>
        </InlinePanel>,
    );

    return { onClose };
}

describe('<InlinePanel>', () => {
    it('is a group named by its title, with its description and content', () => {
        renderPanel();

        const panel = screen.getByRole('group', { name: 'Restock Denim' });

        expect(panel).toHaveAttribute('id', 'panel-1');
        expect(
            screen.getByRole('heading', { name: 'Restock Denim' }),
        ).toBeInTheDocument();
        expect(screen.getByText('In stock now: 250 m')).toBeInTheDocument();
        expect(screen.getByLabelText('Quantity')).toBeInTheDocument();
    });

    it('closes from the close button', async () => {
        const user = userEvent.setup();
        const { onClose } = renderPanel();

        await user.click(screen.getByRole('button', { name: 'Close restock' }));

        expect(onClose).toHaveBeenCalledOnce();
    });

    it('closes with Escape from any control inside it', async () => {
        const user = userEvent.setup();
        const { onClose } = renderPanel();

        await user.click(screen.getByLabelText('Quantity'));
        await user.keyboard('{Escape}');

        expect(onClose).toHaveBeenCalledOnce();
    });

    it('ignores other keys', async () => {
        const user = userEvent.setup();
        const { onClose } = renderPanel();

        await user.click(screen.getByLabelText('Quantity'));
        await user.keyboard('abc{Enter}');

        expect(onClose).not.toHaveBeenCalled();
    });

    it('takes focus itself only when asked to', () => {
        renderPanel({ focusOnMount: true });

        expect(
            screen.getByRole('group', { name: 'Restock Denim' }),
        ).toHaveFocus();
    });

    it('leaves focus alone by default (a form inside takes it)', () => {
        renderPanel();

        expect(
            screen.getByRole('group', { name: 'Restock Denim' }),
        ).not.toHaveFocus();
    });
});
