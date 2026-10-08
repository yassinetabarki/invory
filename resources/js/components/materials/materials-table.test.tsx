import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialsTable } from '@/components/materials/materials-table';
import { button, buttonHistory, denim, thread } from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Material, StockMovement } from '@/types/domain';

// What the table hands to Inertia's <Form>: the mock ignores `onSuccess`, so a
// thin wrapper records it and a test can play "the server accepted the restock".
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

const hrefs = {
    show: (m: Material) => `/materials/${m.id}`,
    edit: (m: Material) => `/materials/${m.id}/edit`,
};

/** One product uses it: the singular of "Used in". */
const laces: Material = {
    ...denim,
    id: 4,
    sku: 'MAT-LAC-120',
    name: 'Waxed cotton laces 120cm',
    unit: 'pair',
    used_in_count: 1,
};

function renderTable(materials: Material[], recentHistory?: StockMovement[]) {
    // The deferred prop is part of the page props once it has loaded.
    setPageProps(recentHistory ? { recent_history: recentHistory } : {});

    return render(
        <MaterialsTable
            materials={materials}
            hrefs={hrefs}
            recentHistory={recentHistory}
        />,
    );
}

/** The table (>= md). Both views are in the DOM; CSS shows one of them. */
function table() {
    return within(screen.getByRole('table', { name: 'Materials' }));
}

/** The cards (< md). */
function cards() {
    return within(screen.getByRole('list', { name: 'Materials' }));
}

describe('<MaterialsTable>', () => {
    beforeEach(() => {
        resetInertiaMock();
        received.onSuccess = undefined;
    });

    describe('table (>= md)', () => {
        it('has the mockup columns, then one row per material', () => {
            renderTable([denim, thread]);

            for (const header of [
                'Material',
                'Category',
                'In stock',
                'Reorder at',
                'Unit cost',
                'Stock value',
                'Status',
                'Used in',
                'Actions',
            ]) {
                expect(
                    table().getByRole('columnheader', { name: header }),
                ).toBeInTheDocument();
            }

            // header row + 2 data rows
            expect(table().getAllByRole('row')).toHaveLength(3);
        });

        it('shows the name, SKU, category, stock, reorder level, cost, value and status', () => {
            renderTable([denim]);

            const row = table().getByRole('row', {
                name: /Denim 12oz Indigo/,
            });

            expect(within(row).getByText('FAB-001')).toHaveClass('font-mono');
            expect(within(row).getByText('Fabric')).toBeInTheDocument();
            expect(within(row).getByText('250 m')).toBeInTheDocument();
            expect(within(row).getByText('50 m')).toBeInTheDocument();
            expect(within(row).getByText('€8.50')).toBeInTheDocument();
            expect(within(row).getByText('/ m')).toBeInTheDocument();
            expect(within(row).getByText('€2,125.00')).toBeInTheDocument();
            expect(within(row).getByText('In stock')).toBeInTheDocument();
        });

        it('shows the photo as a thumbnail, or a placeholder without one', () => {
            renderTable([button, denim]);

            const [withPhoto, without] = table().getAllByRole('row').slice(1);

            expect(withPhoto.querySelector('img')).toHaveAttribute(
                'src',
                '/mock/photos/mat-btn-017.svg',
            );
            expect(without.querySelector('img')).toBeNull();
            expect(
                without.querySelector('[data-slot="photo-thumb"]'),
            ).toHaveAttribute('data-empty', 'true');
        });

        it('shows an em dash when there is no reorder level, and 4 decimals for tiny costs', () => {
            renderTable([thread]);

            const row = table().getByRole('row', { name: /Polyester thread/ });

            expect(within(row).getByText('€0.0040')).toBeInTheDocument();
            expect(within(row).getByText('Out of stock')).toBeInTheDocument();
            // Reorder at and Used in are both empty for this material.
            expect(within(row).getAllByText('—')).toHaveLength(2);
        });

        it('shows the stock status of every row, always as text', () => {
            renderTable([denim, button, thread]);

            // "In stock" is also a column header: match the badge itself.
            expect(
                table().getByText('In stock', {
                    selector: '[data-status="in_stock"]',
                }),
            ).toBeInTheDocument();
            expect(table().getByText('Low stock')).toBeInTheDocument();
            expect(table().getByText('Out of stock')).toBeInTheDocument();
        });

        it('says how many products use the material', () => {
            renderTable([button, laces, denim]);

            const [many, one, none] = table().getAllByRole('row').slice(1);

            expect(within(many).getByText('3 products')).toBeInTheDocument();
            expect(within(one).getByText('1 product')).toBeInTheDocument();
            // Not used: an em dash, not "0 products".
            expect(within(none).queryByText(/product/)).not.toBeInTheDocument();
            expect(within(none).getByText('—')).toBeInTheDocument();
        });

        it('links the name, View and Edit through the hrefs builders', () => {
            renderTable([denim]);

            expect(
                table().getByRole('link', { name: 'Denim 12oz Indigo' }),
            ).toHaveAttribute('href', '/materials/1');
            expect(
                table().getByRole('link', { name: 'View Denim 12oz Indigo' }),
            ).toHaveAttribute('href', '/materials/1');
            expect(
                table().getByRole('link', { name: 'Edit Denim 12oz Indigo' }),
            ).toHaveAttribute('href', '/materials/1/edit');
        });

        it('offers Restock and History per row, and no Delete (it moved to the detail page)', () => {
            renderTable([denim]);

            expect(
                table().getByRole('button', {
                    name: 'Restock Denim 12oz Indigo',
                }),
            ).toBeInTheDocument();
            expect(
                table().getByRole('button', {
                    name: 'History of Denim 12oz Indigo',
                }),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('button', { name: /delete/i }),
            ).not.toBeInTheDocument();
        });

        it('makes Restock the primary action only when the material needs restocking', () => {
            renderTable([denim, button, thread]);

            const restock = (name: string) =>
                table().getByRole('button', { name: `Restock ${name}` });

            expect(restock('Denim 12oz Indigo')).not.toHaveClass('bg-primary');
            expect(restock('Metal shank button 17mm')).toHaveClass(
                'bg-primary',
            );
            expect(restock('Polyester thread')).toHaveClass('bg-primary');
        });
    });

    describe('inline panels', () => {
        const restockName = 'Restock Denim 12oz Indigo';
        const historyName = 'History of Denim 12oz Indigo';

        it('starts with every panel closed and announces it with aria-expanded', () => {
            renderTable([denim]);

            for (const name of [restockName, historyName]) {
                const trigger = table().getByRole('button', { name });

                expect(trigger).toHaveAttribute('aria-expanded', 'false');
                expect(trigger).not.toHaveAttribute('aria-controls');
            }

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
        });

        it('opens the restock panel, names it with aria-controls and focuses the quantity', async () => {
            const user = userEvent.setup();

            renderTable([denim]);

            const trigger = table().getByRole('button', { name: restockName });

            await user.click(trigger);

            const panel = table().getByRole('group', {
                name: 'Restock Denim 12oz Indigo',
            });

            expect(trigger).toHaveAttribute('aria-expanded', 'true');
            expect(trigger).toHaveAttribute('aria-controls', panel.id);
            expect(panel).toHaveTextContent('In stock now: 250 m');
            expect(
                within(panel).getByLabelText('Quantity received'),
            ).toHaveFocus();
        });

        it('opens the history panel and moves focus into it', async () => {
            const user = userEvent.setup();

            renderTable([button], buttonHistory);

            const trigger = table().getByRole('button', {
                name: 'History of Metal shank button 17mm',
            });

            await user.click(trigger);

            const panel = table().getByRole('group', {
                name: 'History · Metal shank button 17mm',
            });

            expect(trigger).toHaveAttribute('aria-expanded', 'true');
            expect(trigger).toHaveAttribute('aria-controls', panel.id);
            expect(panel).toHaveFocus();
            expect(
                within(panel).getByRole('link', { name: 'Open material page' }),
            ).toHaveAttribute('href', '/materials/3');
        });

        it('closes with the same button (a toggle)', async () => {
            const user = userEvent.setup();

            renderTable([denim]);

            const trigger = table().getByRole('button', { name: restockName });

            await user.click(trigger);
            await user.click(trigger);

            expect(trigger).toHaveAttribute('aria-expanded', 'false');
            expect(screen.queryByRole('group')).not.toBeInTheDocument();
        });

        it('keeps one panel per row: opening the other kind replaces it', async () => {
            const user = userEvent.setup();

            renderTable([denim], []);

            await user.click(
                table().getByRole('button', { name: restockName }),
            );
            await user.click(
                table().getByRole('button', { name: historyName }),
            );

            expect(table().getAllByRole('group')).toHaveLength(1);
            expect(
                table().getByRole('group', {
                    name: 'History · Denim 12oz Indigo',
                }),
            ).toBeInTheDocument();
            expect(
                table().getByRole('button', { name: restockName }),
            ).toHaveAttribute('aria-expanded', 'false');
            expect(
                table().getByRole('button', { name: historyName }),
            ).toHaveAttribute('aria-expanded', 'true');
        });

        it('lets every row have its own panel', async () => {
            const user = userEvent.setup();

            renderTable([denim, thread]);

            await user.click(
                table().getByRole('button', { name: restockName }),
            );
            await user.click(
                table().getByRole('button', {
                    name: 'Restock Polyester thread',
                }),
            );

            expect(table().getAllByRole('group')).toHaveLength(2);
        });

        it('shows an open panel in a full-width row under its material', async () => {
            const user = userEvent.setup();

            renderTable([denim, thread]);

            await user.click(
                table().getByRole('button', { name: restockName }),
            );

            const rows = table().getAllByRole('row');
            // header, Denim, its panel, Polyester thread
            const panelRow = rows[2];

            expect(rows).toHaveLength(4);
            expect(within(panelRow).getByRole('group')).toBeInTheDocument();
            expect(rows[1]).toHaveTextContent('Denim 12oz Indigo');
            expect(rows[3]).toHaveTextContent('Polyester thread');
        });

        it.each([
            ['the close button', 'Close restock'],
            ['the Cancel button', 'Cancel'],
        ])(
            'closes the restock panel from %s and gives focus back to Restock',
            async (_label, name) => {
                const user = userEvent.setup();

                renderTable([denim]);

                await user.click(
                    table().getByRole('button', { name: restockName }),
                );
                await user.click(table().getByRole('button', { name }));

                expect(screen.queryByRole('group')).not.toBeInTheDocument();
                expect(
                    table().getByRole('button', { name: restockName }),
                ).toHaveFocus();
            },
        );

        it('closes the restock panel with Escape from a field and returns focus', async () => {
            const user = userEvent.setup();

            renderTable([denim]);

            await user.click(
                table().getByRole('button', { name: restockName }),
            );
            await user.type(screen.getByLabelText('Quantity received'), '5');
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
            expect(
                table().getByRole('button', { name: restockName }),
            ).toHaveFocus();
        });

        it('closes the history panel with Escape or its close button and returns focus', async () => {
            const user = userEvent.setup();

            renderTable([denim], []);

            const trigger = table().getByRole('button', { name: historyName });

            await user.click(trigger);
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
            expect(trigger).toHaveFocus();

            await user.click(trigger);
            await user.click(
                table().getByRole('button', { name: 'Close history' }),
            );

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
            await waitFor(() => expect(trigger).toHaveFocus());
        });

        it('closes the restock panel once the server accepted the restock', async () => {
            const user = userEvent.setup();

            renderTable([denim]);

            await user.click(
                table().getByRole('button', { name: restockName }),
            );

            expect(received.onSuccess).toBeTypeOf('function');

            act(() => {
                (received.onSuccess as () => void)();
            });

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
            expect(
                table().getByRole('button', { name: restockName }),
            ).toHaveFocus();
        });

        it('posts the restock form to the material restock route', async () => {
            const user = userEvent.setup();

            renderTable([button]);

            await user.click(
                table().getByRole('button', {
                    name: 'Restock Metal shank button 17mm',
                }),
            );

            expect(
                table().getByRole('group').querySelector('form'),
            ).toHaveAttribute('action', '/materials/3/restocks');
        });

        it('shows a skeleton in the history panel until the deferred history arrives', async () => {
            const user = userEvent.setup();

            // `recent_history` is not in the page props yet.
            const { rerender } = renderTable([button]);

            await user.click(
                table().getByRole('button', {
                    name: 'History of Metal shank button 17mm',
                }),
            );

            expect(screen.getByRole('status')).toHaveTextContent(
                'Loading history…',
            );
            expect(table().queryByRole('listitem')).not.toBeInTheDocument();

            // The deferred request lands.
            setPageProps({ recent_history: buttonHistory });
            rerender(
                <MaterialsTable
                    materials={[button]}
                    hrefs={hrefs}
                    recentHistory={buttonHistory}
                />,
            );

            expect(screen.queryByRole('status')).not.toBeInTheDocument();
            expect(
                table().getByText('+500 pc at €0.35 · PO-2291'),
            ).toBeInTheDocument();
        });

        it("lists only the opened material's history", async () => {
            const user = userEvent.setup();
            const denimEntry: StockMovement = {
                ...buttonHistory[1],
                id: 99,
                material_id: denim.id,
                reference: 'PO-DENIM',
            };

            renderTable([button, denim], [denimEntry, ...buttonHistory]);

            await user.click(
                table().getByRole('button', {
                    name: 'History of Metal shank button 17mm',
                }),
            );

            const panel = table().getByRole('group', {
                name: 'History · Metal shank button 17mm',
            });

            expect(within(panel).getAllByRole('listitem')).toHaveLength(4);
            expect(within(panel).queryByText(/PO-DENIM/)).toBeNull();
        });
    });

    describe('cards (< md)', () => {
        it('renders one card per material with the same information', () => {
            renderTable([button, denim]);

            const items = cards().getAllByRole('listitem');

            expect(items).toHaveLength(2);

            const card = within(items[0]);

            expect(
                card.getByText('Metal shank button 17mm'),
            ).toBeInTheDocument();
            expect(card.getByText('MAT-BTN-017')).toHaveClass('font-mono');
            expect(card.getByText(/Fastener/)).toBeInTheDocument();
            expect(card.getByText('Low stock')).toBeInTheDocument();
            expect(card.getByText('900 pc')).toBeInTheDocument();
            expect(card.getByText('1,000 pc')).toBeInTheDocument();
            expect(card.getByText('€0.35')).toBeInTheDocument();
            expect(card.getByText('€315.00')).toBeInTheDocument();
            expect(card.getByText('3 products')).toBeInTheDocument();
        });

        it('labels every figure of the card', () => {
            renderTable([denim]);

            const card = within(cards().getByRole('listitem'));

            for (const label of [
                'In stock',
                'Reorder at',
                'Unit cost',
                'Stock value',
                'Used in',
            ]) {
                expect(card.getByText(label, { selector: 'dt' })).toBeVisible();
            }
        });

        it('uses the hrefs builders for the name, View and Edit', () => {
            renderTable([button]);

            expect(
                cards().getByRole('link', { name: 'Metal shank button 17mm' }),
            ).toHaveAttribute('href', '/materials/3');
            expect(
                cards().getByRole('link', {
                    name: 'View Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/3');
            expect(
                cards().getByRole('link', {
                    name: 'Edit Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/3/edit');
        });

        it('labels the History action with text in a card', () => {
            renderTable([denim]);

            expect(
                cards().getByRole('button', {
                    name: 'History of Denim 12oz Indigo',
                }),
            ).toHaveTextContent('History');
        });

        it('opens a panel inside the card and returns focus when it closes', async () => {
            const user = userEvent.setup();

            renderTable([button]);

            const trigger = cards().getByRole('button', {
                name: 'Restock Metal shank button 17mm',
            });

            await user.click(trigger);

            const card = cards().getByRole('listitem');

            expect(trigger).toHaveAttribute('aria-expanded', 'true');
            expect(
                within(card).getByRole('group', {
                    name: 'Restock Metal shank button 17mm',
                }),
            ).toBeInTheDocument();
            // The table's own panel is separate: only the card's view opened.
            expect(table().queryByRole('group')).not.toBeInTheDocument();

            await user.click(
                within(card).getByRole('button', { name: 'Close restock' }),
            );

            expect(screen.queryByRole('group')).not.toBeInTheDocument();
            expect(trigger).toHaveFocus();
        });
    });
});
