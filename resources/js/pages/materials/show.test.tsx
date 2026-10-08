import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import MaterialsShow from '@/pages/materials/show';
import { denim, thread } from '@/test/fixtures';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

/** The value (`<dd>`) next to a `<dt>` label. */
function valueOf(label: string): HTMLElement {
    const term = screen.getByText(label, { selector: 'dt' });
    const value = term.nextElementSibling;

    if (!(value instanceof HTMLElement)) {
        throw new Error(`No value for "${label}"`);
    }

    return value;
}

describe('materials/show page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/materials/1' });
    });

    it('shows the name, SKU and category in the header', () => {
        render(<MaterialsShow material={denim} />);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Denim 12oz Indigo',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('FAB-001 · Fabric')).toBeInTheDocument();
    });

    it('shows the stock card with units, cost and value', () => {
        render(<MaterialsShow material={denim} />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'Stock' }),
        ).toBeInTheDocument();
        expect(valueOf('Quantity on hand')).toHaveTextContent('250 m');
        expect(valueOf('Reorder level')).toHaveTextContent('50 m');
        expect(valueOf('Status')).toHaveTextContent('In stock');
        expect(valueOf('Unit cost')).toHaveTextContent('€8.50 per Metre (m)');
        expect(valueOf('Stock value')).toHaveTextContent('€2,125.00');
    });

    it('shows the supplier details and keeps note line breaks as text', () => {
        render(<MaterialsShow material={denim} />);

        expect(valueOf('Supplier')).toHaveTextContent('Tessuti Rossi');
        expect(valueOf('Supplier reference')).toHaveTextContent('TR-4471');
        expect(valueOf('Colour')).toHaveTextContent('Indigo');
        expect(valueOf('Composition')).toHaveTextContent('100% cotton');

        const notes = valueOf('Notes').querySelector('span');

        expect(notes).toHaveTextContent('First line Second line');
        expect(notes?.textContent).toBe('First line\nSecond line');
        expect(notes).toHaveClass('whitespace-pre-line');
    });

    it('renders an em dash for empty optional fields and "Not set" for the reorder level', () => {
        render(<MaterialsShow material={thread} />);

        expect(valueOf('Reorder level')).toHaveTextContent('Not set');
        expect(valueOf('Status')).toHaveTextContent('Out of stock');
        expect(valueOf('Unit cost')).toHaveTextContent('€0.0040');

        for (const label of [
            'Supplier',
            'Supplier reference',
            'Colour',
            'Composition',
            'Notes',
        ]) {
            expect(valueOf(label)).toHaveTextContent('—');
        }
    });

    it('does not interpret notes as HTML', () => {
        render(
            <MaterialsShow
                material={{ ...denim, notes: '<b>bold</b> <img src=x>' }}
            />,
        );

        expect(valueOf('Notes')).toHaveTextContent('<b>bold</b> <img src=x>');
        expect(valueOf('Notes').querySelector('b, img')).toBeNull();
    });

    it('links Edit to the edit route', () => {
        render(<MaterialsShow material={denim} />);

        expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
            'href',
            '/materials/1/edit',
        );
    });

    describe('delete', () => {
        it('opens the confirmation dialog without deleting yet', async () => {
            const user = userEvent.setup();

            render(<MaterialsShow material={denim} />);

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

            render(<MaterialsShow material={denim} />);

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

            render(<MaterialsShow material={denim} />);

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

            render(<MaterialsShow material={denim} />);

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

            act(() => options.onFinish());

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });
    });

    it('defines the Materials > name breadcrumbs', () => {
        const { breadcrumbs } = MaterialsShow.layout({ material: denim });

        expect(breadcrumbs.map((c) => c.title)).toEqual([
            'Materials',
            'Denim 12oz Indigo',
        ]);
        expect(breadcrumbs[0].href).toMatchObject({ url: '/materials' });
    });
});
