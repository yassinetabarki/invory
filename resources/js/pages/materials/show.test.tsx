import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import MaterialsShow from '@/pages/materials/show';
import {
    button,
    buttonHistory,
    buttonUsage,
    denim,
    thread,
} from '@/test/fixtures';
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
        setPageProps({}, { url: '/materials/3' });
    });

    describe('header', () => {
        it('shows the name, SKU, category and stock status', () => {
            render(
                <MaterialsShow
                    material={button}
                    usage={buttonUsage}
                    history={buttonHistory}
                />,
            );

            expect(
                screen.getByRole('heading', {
                    level: 1,
                    name: 'Metal shank button 17mm',
                }),
            ).toBeInTheDocument();
            expect(screen.getByText('MAT-BTN-017')).toHaveClass('font-mono');
            expect(screen.getByText('Fastener')).toBeInTheDocument();
            expect(screen.getByText('Low stock')).toHaveAttribute(
                'data-status',
                'low',
            );
        });

        it('offers Restock, Edit and Delete', () => {
            render(<MaterialsShow material={button} usage={[]} history={[]} />);

            expect(
                screen.getByRole('button', { name: 'Restock' }),
            ).toBeEnabled();
            expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
                'href',
                '/materials/3/edit',
            );
            expect(
                screen.getByRole('button', { name: 'Delete' }),
            ).toBeInTheDocument();
        });

        it('opens the restock form in a dialog for this material', async () => {
            const user = userEvent.setup();

            render(<MaterialsShow material={button} usage={[]} history={[]} />);

            await user.click(screen.getByRole('button', { name: 'Restock' }));

            const dialog = screen.getByRole('dialog', {
                name: 'Restock Metal shank button 17mm',
            });

            expect(dialog.querySelector('form')).toHaveAttribute(
                'action',
                '/materials/3/restocks',
            );
            expect(dialog).toHaveTextContent(
                'In stock now: 900 pc · unit cost €0.35',
            );
        });
    });

    describe('delete', () => {
        it('is disabled with the reason when products use the material', async () => {
            const user = userEvent.setup();

            render(
                <MaterialsShow
                    material={button}
                    usage={buttonUsage}
                    history={buttonHistory}
                />,
            );

            const remove = screen.getByRole('button', { name: 'Delete' });

            expect(remove).toBeDisabled();
            expect(
                screen.getByText("Used in 3 products, so it can't be deleted."),
            ).toBeVisible();
            expect(remove).toHaveAccessibleDescription(
                "Used in 3 products, so it can't be deleted.",
            );

            await user.click(remove);

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('opens the confirmation dialog naming the material and its SKU, without deleting yet', async () => {
            const user = userEvent.setup();

            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            expect(
                screen.getByRole('button', { name: 'Delete' }),
            ).toBeEnabled();
            expect(
                screen.queryByText(/can't be deleted/),
            ).not.toBeInTheDocument();

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

            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('deletes through the destroy URL, shows progress, then closes', async () => {
            const user = userEvent.setup();

            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

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

    describe('photo', () => {
        it('shows the photo (square) and links "Replace photo" to the edit page', () => {
            render(<MaterialsShow material={button} usage={[]} history={[]} />);

            expect(
                screen.getByRole('img', {
                    name: 'Photo of Metal shank button 17mm',
                }),
            ).toHaveAttribute('src', '/mock/photos/mat-btn-017.svg');
            expect(
                screen.getByRole('link', { name: 'Replace photo' }),
            ).toHaveAttribute('href', '/materials/3/edit');
        });

        it('offers to add a photo when there is none', () => {
            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            expect(screen.queryByRole('img')).not.toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Add photo' }),
            ).toHaveAttribute('href', '/materials/1/edit');
        });
    });

    describe('numbers and callouts', () => {
        it('shows the four KPIs with units and currency', () => {
            render(<MaterialsShow material={button} usage={[]} history={[]} />);

            expect(valueOf('In stock')).toHaveTextContent('900 pc');
            expect(valueOf('Reorder at')).toHaveTextContent('1,000 pc');
            expect(valueOf('Unit cost')).toHaveTextContent('€0.35 / pc');
            expect(valueOf('Stock value')).toHaveTextContent('€315.00');
        });

        it('says "Not set" for a missing reorder level and keeps 4 decimals for tiny costs', () => {
            render(<MaterialsShow material={thread} usage={[]} history={[]} />);

            expect(valueOf('Reorder at')).toHaveTextContent('Not set');
            expect(valueOf('Unit cost')).toHaveTextContent('€0.0040 / m');
        });

        it('warns when the stock is below the reorder level', () => {
            render(<MaterialsShow material={button} usage={[]} history={[]} />);

            expect(
                screen.getByText('Below reorder level.').closest('[data-slot]'),
            ).toHaveTextContent(
                'Below reorder level. 900 pc on hand, reorder at 1,000 pc.',
            );
        });

        it('raises a danger alert when the material is out of stock', () => {
            render(<MaterialsShow material={thread} usage={[]} history={[]} />);

            expect(screen.getByRole('alert')).toHaveTextContent(
                'Out of stock. 0 m on hand.',
            );
            expect(
                screen.getByText('Out of stock', { selector: '[data-status]' }),
            ).toBeInTheDocument();
        });

        it('shows no callout while the material is in stock', () => {
            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            expect(screen.queryByRole('alert')).not.toBeInTheDocument();
            expect(
                screen.queryByText('Below reorder level.'),
            ).not.toBeInTheDocument();
        });
    });

    describe('details', () => {
        it('shows the supplier details and keeps note line breaks as text', () => {
            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            expect(valueOf('Unit')).toHaveTextContent('Metre (m)');
            expect(valueOf('Supplier')).toHaveTextContent('Tessuti Rossi');
            expect(valueOf('Supplier ref.')).toHaveTextContent('TR-4471');
            expect(valueOf('Colour')).toHaveTextContent('Indigo');
            expect(valueOf('Composition')).toHaveTextContent('100% cotton');

            const notes = valueOf('Notes').querySelector('span');

            expect(notes?.textContent).toBe('First line\nSecond line');
            expect(notes).toHaveClass('whitespace-pre-line');
            expect(valueOf('Last updated')).toHaveTextContent('1 Jan 2026');
        });

        it('renders an em dash for empty optional fields', () => {
            render(<MaterialsShow material={thread} usage={[]} history={[]} />);

            for (const label of [
                'Supplier',
                'Supplier ref.',
                'Colour',
                'Composition',
                'Notes',
            ]) {
                expect(valueOf(label)).toHaveTextContent('—');
            }
        });
    });

    describe('what you can make with it', () => {
        it('lists the products that use the material, with a suggestion for the bottleneck', () => {
            render(
                <MaterialsShow
                    material={button}
                    usage={buttonUsage}
                    history={buttonHistory}
                />,
            );

            const section = screen.getByRole('region', {
                name: 'What you can make with it',
            });

            expect(
                within(section).getByText(/^3 products use this material\./),
            ).toBeInTheDocument();
            // The table (>= md) and the cards (< md) are both in the DOM.
            const table = within(within(section).getByRole('table'));

            expect(
                table.getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(
                table.getByText('This material (bottleneck)'),
            ).toBeInTheDocument();
            expect(
                within(section).getByText(
                    /this material limits Denim Jacket to 112 units\. If you restock it, the next limit is Denim 12oz Indigo at 126 units\./,
                ),
            ).toBeInTheDocument();
        });

        it('says so when no product uses the material', () => {
            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            expect(
                screen.getByText('Not used in any product yet.'),
            ).toBeInTheDocument();
        });
    });

    describe('history', () => {
        it('shows the full history under "What you can make with it", newest first', () => {
            render(
                <MaterialsShow
                    material={button}
                    usage={buttonUsage}
                    history={buttonHistory}
                />,
            );

            const usage = screen.getByRole('region', {
                name: 'What you can make with it',
            });
            const history = screen.getByRole('region', { name: 'History' });

            expect(
                usage.compareDocumentPosition(history) &
                    Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();

            const items = within(history).getAllByRole('listitem');

            expect(items).toHaveLength(4);
            expect(items[0]).toHaveTextContent('€0.32 → €0.35 per pc');
            expect(items[1]).toHaveTextContent('+500 pc at €0.35 · PO-2291');
            expect(items[3]).toHaveTextContent('600 pc at €0.32');
        });

        it('says so when there is no history', () => {
            render(<MaterialsShow material={denim} usage={[]} history={[]} />);

            expect(screen.getByText('No history yet.')).toBeInTheDocument();
        });
    });

    it('defines the Materials > name breadcrumbs', () => {
        const { breadcrumbs } = MaterialsShow.layout({
            material: denim,
            usage: [],
            history: [],
        });

        expect(breadcrumbs.map((c) => c.title)).toEqual([
            'Materials',
            'Denim 12oz Indigo',
        ]);
        expect(breadcrumbs[0].href).toMatchObject({ url: '/materials' });
    });
});
