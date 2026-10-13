import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { BomEditor } from '@/components/products/bom-editor';
import type { BomRow } from '@/components/products/bom-rows';
import type { MaterialOption } from '@/types/domain';
import { inertiaMock, resetInertiaMock, respondToHttp } from '@/test/inertia';
import {
    answerMaterialOptions,
    materialOptions,
} from '@/test/fixtures/products';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const DENIM = 4;
const BUTTON = 5;
const THREAD = 8;

/** What the form knows of its saved rows: the picker is not asked for these. */
const knownMaterials = materialOptions.filter((material) =>
    [DENIM, BUTTON, THREAD].includes(material.id),
);

function row(
    uid: string,
    materialId: number | '',
    quantity: string,
    waste = '0',
    notes = '',
): BomRow {
    return {
        uid,
        material_id: materialId,
        quantity,
        waste_percent: waste,
        notes,
    };
}

const jacketRows = (): BomRow[] => [
    row('a', DENIM, '1.8', '10', 'Body, sleeves, collar'),
    row('b', BUTTON, '8', '0', 'Front placket (6), cuffs (2)'),
    row('c', THREAD, '120', '5', 'All seams'),
];

type HarnessProps = {
    initial?: BomRow[];
    /** What the form knows of its saved rows (the edit page's `product.components`). */
    known?: readonly MaterialOption[];
    errors?: Record<string, string | undefined>;
    onRows?: (rows: BomRow[]) => void;
};

/** The editor is controlled, so tests hold the rows in state like the form does. */
function Harness({
    initial = jacketRows(),
    known = knownMaterials,
    errors = {},
    onRows,
}: HarnessProps) {
    const [rows, setRows] = useState(initial);

    return (
        <BomEditor
            rows={rows}
            knownMaterials={known}
            errors={errors}
            onChange={(next) => {
                setRows(next);
                onRows?.(next);
            }}
        />
    );
}

function setup(props: HarnessProps = {}) {
    const user = userEvent.setup();
    const view = render(<Harness {...props} />);

    return { user, ...view };
}

/** The rows the editor reported on its last change. */
function lastRows(onRows: ReturnType<typeof vi.fn>): BomRow[] {
    const call = onRows.mock.lastCall;

    if (!call) {
        throw new Error('Expected the editor to have reported a change.');
    }

    return call[0] as BomRow[];
}

/** The n-th row (1-based) of the composition. */
function rowGroup(n: number): HTMLElement {
    return screen.getByRole('group', { name: new RegExp(`^Row ${n}:`) });
}

/** The material names of the rows, in order. */
function materialNames(): string[] {
    return screen
        .getAllByRole('group', { name: /^Row \d+:/ })
        .map((group) => group.getAttribute('aria-label') ?? '');
}

function searchBox(): HTMLElement {
    return screen.getByRole('combobox', { name: 'Search materials' });
}

/** Waits for the server's first answer to show in the open picker. */
async function optionsShown(): Promise<HTMLElement[]> {
    return screen.findAllByRole('option');
}

describe('<BomEditor>', () => {
    beforeEach(() => {
        resetInertiaMock();
        respondToHttp(({ params }) => answerMaterialOptions(params));
    });

    describe('layout', () => {
        it('lists the rows with a count, the hint and the add button', () => {
            setup();

            expect(
                screen.getByRole('heading', { level: 2, name: 'Composition' }),
            ).toBeInTheDocument();
            expect(screen.getByText('3 materials')).toBeInTheDocument();
            expect(
                screen.getByText(
                    'Unit cost and capacity are recalculated from current stock when you save.',
                ),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Add material' }),
            ).toBeInTheDocument();
            expect(materialNames()).toEqual([
                'Row 1: Denim 12oz Indigo',
                'Row 2: Metal shank button 17mm',
                'Row 3: Polyester thread',
            ]);
        });

        it('says "1 material" for a single row', () => {
            setup({ initial: [row('a', DENIM, '1.8')] });

            expect(screen.getByText('1 material')).toBeInTheDocument();
        });

        it('shows an empty message and keeps the add button when there are no rows', () => {
            setup({ initial: [] });

            expect(screen.getByText('0 materials')).toBeInTheDocument();
            expect(
                screen.getByText(/No materials yet\. Add the materials/),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Add material' }),
            ).toBeInTheDocument();
        });

        it('fills each row with its material, quantity, waste and notes', () => {
            setup();

            const first = within(rowGroup(1));

            expect(
                first.getByRole('combobox', { name: /Denim 12oz Indigo/ }),
            ).toHaveTextContent('MAT-DEN-012');
            expect(
                first.getByLabelText('Quantity per unit for Denim 12oz Indigo'),
            ).toHaveValue('1.8');
            expect(
                first.getByLabelText('Waste percent for Denim 12oz Indigo'),
            ).toHaveValue('10');
            expect(
                first.getByLabelText('Placement notes for Denim 12oz Indigo'),
            ).toHaveValue('Body, sleeves, collar');
        });
    });

    describe('add a row', () => {
        it('adds a blank row that opens its material picker', async () => {
            const onRows = vi.fn();
            const { user } = setup({ onRows });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );

            expect(screen.getByText('4 materials')).toBeInTheDocument();

            const added = within(rowGroup(4));

            expect(
                added.getByRole('combobox', { name: /Select a material/ }),
            ).toHaveAttribute('aria-expanded', 'true');
            expect(searchBox()).toBeInTheDocument();
            expect(searchBox()).toHaveFocus();
            // Blank quantity, 0 waste, no unit yet.
            expect(
                added.getByLabelText('Quantity per unit for new material'),
            ).toHaveValue('');
            expect(
                added.getByLabelText('Waste percent for new material'),
            ).toHaveValue('0');

            const rows = lastRows(onRows);

            expect(rows).toHaveLength(4);
            expect(rows[3]).toMatchObject({
                material_id: '',
                quantity: '',
                waste_percent: '0',
                notes: '',
            });
        });

        it('gives every new row its own key', async () => {
            const onRows = vi.fn();
            const { user } = setup({ initial: [], onRows });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('{Escape}');
            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );

            const rows = lastRows(onRows);

            expect(rows).toHaveLength(2);
            expect(rows[0].uid).not.toBe(rows[1].uid);
            expect(rows[0].uid).toBeTruthy();
        });
    });

    describe('pick a material', () => {
        it('picks with the mouse and shows the material with its SKU', async () => {
            const onRows = vi.fn();
            const { user } = setup({ initial: [], onRows });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.click(
                await screen.findByRole('option', {
                    name: /Metal zipper 18cm/,
                }),
            );

            const picker = within(rowGroup(1)).getByRole('combobox', {
                name: /Metal zipper 18cm/,
            });

            expect(picker).toHaveTextContent('MAT-ZIP-018');
            expect(picker).toHaveAttribute('aria-expanded', 'false');
            expect(searchBox).toThrow();
            expect(lastRows(onRows)).toMatchObject([{ material_id: 6 }]);
        });

        it('picks with the keyboard: type to search, Enter to choose', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            // Focus is already in the search box.
            await optionsShown();
            await user.keyboard('thread{Enter}');

            expect(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Polyester thread/,
                }),
            ).toBeInTheDocument();
        });

        it('opens a closed picker with the arrow-down key and searches by SKU', async () => {
            const { user } = setup();

            within(rowGroup(3))
                .getByRole('combobox', { name: /Polyester thread/ })
                .focus();
            await user.keyboard('{ArrowDown}');

            expect(searchBox()).toBeInTheDocument();

            await optionsShown();
            await user.keyboard('LBL-WOV');

            await waitFor(() =>
                expect(screen.getAllByRole('option')).toHaveLength(1),
            );
            expect(screen.getByRole('option')).toHaveTextContent(
                'Woven brand label',
            );
        });

        it('shows each option with its SKU, stock and unit cost', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );

            const option = await screen.findByRole('option', {
                name: /Polyester thread/,
            });

            expect(option).toHaveTextContent('MAT-THR-PES');
            expect(option).toHaveTextContent('20,000 m in stock · €0.0040 / m');
            expect(
                screen.getByRole('option', { name: /Nappa leather Cognac/ }),
            ).toHaveTextContent('540 sqft in stock · €6.80 / sqft');
        });

        it('says when no material matches the search', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('zzzz');

            expect(
                await screen.findByText('No material found.'),
            ).toBeInTheDocument();
            expect(screen.queryByRole('option')).not.toBeInTheDocument();
        });

        it('closes the picker with Escape and keeps the row blank', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('option')).not.toBeInTheDocument();
            expect(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Select a material/,
                }),
            ).toBeInTheDocument();
        });

        it('changes the material of a row and its unit with it', async () => {
            const { user } = setup();

            const quantity = () =>
                within(rowGroup(2)).getByLabelText(/^Quantity per unit for/);

            expect(quantity().parentElement).toHaveTextContent('pc');

            await user.click(
                within(rowGroup(2)).getByRole('combobox', {
                    name: /Metal shank button/,
                }),
            );
            await user.click(
                await screen.findByRole('option', {
                    name: /Cotton canvas 10oz/,
                }),
            );

            expect(quantity().parentElement).toHaveTextContent('m');
            expect(quantity().parentElement).not.toHaveTextContent('pc');
        });
    });

    describe('a material can only be used once', () => {
        it('shows materials used on other rows, but not as choices', async () => {
            const onRows = vi.fn();
            const { user } = setup({ onRows });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );

            const denim = await screen.findByRole('option', {
                name: /Denim 12oz Indigo/,
            });

            expect(denim).toHaveAttribute('aria-disabled', 'true');
            expect(denim).toHaveTextContent('Already in this composition');

            const rivet = screen.getByRole('option', {
                name: /Brass rivet 9mm/,
            });

            expect(rivet).not.toHaveAttribute('aria-disabled', 'true');

            const before = onRows.mock.calls.length;

            await user.click(denim);

            // Nothing was picked: no change, and the picker is still open.
            expect(onRows).toHaveBeenCalledTimes(before);
            expect(searchBox()).toBeInTheDocument();
        });

        it('does not pick a used material with the keyboard either', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await optionsShown();
            await user.keyboard('denim{Enter}');

            expect(
                within(rowGroup(4)).getByRole('combobox', {
                    name: /Select a material/,
                }),
            ).toBeInTheDocument();
        });

        it("keeps the row's own material selectable", async () => {
            const { user } = setup();

            await user.click(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Denim 12oz Indigo/,
                }),
            );

            expect(
                await screen.findByRole('option', {
                    name: /Denim 12oz Indigo/,
                }),
            ).not.toHaveAttribute('aria-disabled', 'true');
            expect(
                screen.getByRole('option', { name: /Polyester thread/ }),
            ).toHaveAttribute('aria-disabled', 'true');
        });

        it('offers a material again once its row is removed', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', {
                    name: 'Remove Metal shank button 17mm',
                }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );

            expect(
                await screen.findByRole('option', {
                    name: /Metal shank button/,
                }),
            ).not.toHaveAttribute('aria-disabled', 'true');
        });
    });

    describe('unit suffix', () => {
        it('shows the unit of the picked material after the quantity', () => {
            setup();

            const suffixOf = (n: number) =>
                within(rowGroup(n)).getByLabelText(/^Quantity per unit for/)
                    .parentElement;

            expect(suffixOf(1)).toHaveTextContent('m');
            expect(suffixOf(2)).toHaveTextContent('pc');
            expect(suffixOf(3)).toHaveTextContent('m');
        });

        it('shows a dash until a material is picked, then its unit', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('{Escape}');

            const quantity = within(rowGroup(1)).getByLabelText(
                'Quantity per unit for new material',
            );

            expect(quantity.parentElement).toHaveTextContent('—');

            await user.click(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Select a material/,
                }),
            );
            await user.click(
                await screen.findByRole('option', {
                    name: /Rubber cupsole 42/,
                }),
            );

            expect(
                within(rowGroup(1)).getByLabelText(
                    'Quantity per unit for Rubber cupsole 42',
                ).parentElement,
            ).toHaveTextContent('pair');
        });

        it('announces the unit with the field (but not the dash)', () => {
            setup({ initial: [row('a', BUTTON, '8'), row('b', '', '')] });

            const picked = within(rowGroup(1)).getByLabelText(
                'Quantity per unit for Metal shank button 17mm',
            );
            const blank = within(rowGroup(2)).getByLabelText(
                'Quantity per unit for new material',
            );

            expect(picked).toHaveAccessibleDescription('pc');
            expect(blank).not.toHaveAccessibleDescription();
        });

        it('uses a plain text input with a decimal keypad, not a number input', () => {
            setup();

            const quantity = within(rowGroup(1)).getByLabelText(
                'Quantity per unit for Denim 12oz Indigo',
            );

            expect(quantity).toHaveAttribute('type', 'text');
            expect(quantity).toHaveAttribute('inputmode', 'decimal');
        });
    });

    describe('edit the fields', () => {
        it('keeps typed values as the text typed', async () => {
            const onRows = vi.fn();
            const { user } = setup({ initial: [row('a', DENIM, '')], onRows });

            const group = within(rowGroup(1));

            await user.type(
                group.getByLabelText('Quantity per unit for Denim 12oz Indigo'),
                '1.80',
            );
            await user.clear(
                group.getByLabelText('Waste percent for Denim 12oz Indigo'),
            );
            await user.type(
                group.getByLabelText('Waste percent for Denim 12oz Indigo'),
                '10.5',
            );
            await user.type(
                group.getByLabelText('Placement notes for Denim 12oz Indigo'),
                'Body',
            );

            expect(lastRows(onRows)).toEqual([
                {
                    uid: 'a',
                    material_id: DENIM,
                    quantity: '1.80',
                    waste_percent: '10.5',
                    notes: 'Body',
                },
            ]);
        });
    });

    describe('remove a row', () => {
        it('removes the row and updates the count', async () => {
            const onRows = vi.fn();
            const { user } = setup({ onRows });

            await user.click(
                screen.getByRole('button', {
                    name: 'Remove Metal shank button 17mm',
                }),
            );

            expect(screen.getByText('2 materials')).toBeInTheDocument();
            expect(materialNames()).toEqual([
                'Row 1: Denim 12oz Indigo',
                'Row 2: Polyester thread',
            ]);
            expect(lastRows(onRows).map((item) => item.uid)).toEqual([
                'a',
                'c',
            ]);
        });

        it('names the remove button of a blank row "new material"', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('{Escape}');

            expect(
                screen.getByRole('button', { name: 'Remove new material' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Move new material up' }),
            ).toBeInTheDocument();
        });

        it('moves focus to the next row, so keyboard users do not lose their place', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', {
                    name: 'Remove Metal shank button 17mm',
                }),
            );

            expect(
                within(rowGroup(2)).getByRole('combobox', {
                    name: /Polyester thread/,
                }),
            ).toHaveFocus();
        });

        it('moves focus to the previous row when the last one is removed', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', {
                    name: 'Remove Polyester thread',
                }),
            );

            expect(
                within(rowGroup(2)).getByRole('combobox', {
                    name: /Metal shank button/,
                }),
            ).toHaveFocus();
        });

        it('moves focus to "Add material" when the only row is removed', async () => {
            const { user } = setup({ initial: [row('a', DENIM, '1.8')] });

            await user.click(
                screen.getByRole('button', {
                    name: 'Remove Denim 12oz Indigo',
                }),
            );

            expect(screen.getByText('0 materials')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Add material' }),
            ).toHaveFocus();
        });
    });

    describe('reorder', () => {
        it('moves a row up and down with buttons', async () => {
            const onRows = vi.fn();
            const { user } = setup({ onRows });

            await user.click(
                screen.getByRole('button', {
                    name: 'Move Metal shank button 17mm up',
                }),
            );

            expect(materialNames()).toEqual([
                'Row 1: Metal shank button 17mm',
                'Row 2: Denim 12oz Indigo',
                'Row 3: Polyester thread',
            ]);

            await user.click(
                screen.getByRole('button', {
                    name: 'Move Metal shank button 17mm down',
                }),
            );
            await user.click(
                screen.getByRole('button', {
                    name: 'Move Metal shank button 17mm down',
                }),
            );

            expect(materialNames()).toEqual([
                'Row 1: Denim 12oz Indigo',
                'Row 2: Polyester thread',
                'Row 3: Metal shank button 17mm',
            ]);
            expect(lastRows(onRows).map((item) => item.uid)).toEqual([
                'a',
                'c',
                'b',
            ]);
        });

        it('cannot move the first row up or the last row down', () => {
            setup();

            expect(
                screen.getByRole('button', {
                    name: 'Move Denim 12oz Indigo up',
                }),
            ).toBeDisabled();
            expect(
                screen.getByRole('button', {
                    name: 'Move Denim 12oz Indigo down',
                }),
            ).toBeEnabled();
            expect(
                screen.getByRole('button', {
                    name: 'Move Polyester thread down',
                }),
            ).toBeDisabled();
            expect(
                screen.getByRole('button', {
                    name: 'Move Polyester thread up',
                }),
            ).toBeEnabled();
        });

        it('carries the typed values with the row', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', {
                    name: 'Move Polyester thread up',
                }),
            );

            const moved = within(rowGroup(2));

            expect(
                moved.getByLabelText('Quantity per unit for Polyester thread'),
            ).toHaveValue('120');
            expect(
                moved.getByLabelText('Placement notes for Polyester thread'),
            ).toHaveValue('All seams');
        });

        it('keeps focus on the button that was used, so it can be pressed again', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', {
                    name: 'Move Polyester thread up',
                }),
            );

            expect(
                screen.getByRole('button', {
                    name: 'Move Polyester thread up',
                }),
            ).toHaveFocus();
        });

        it('moves focus to the opposite button when the row reaches the end', async () => {
            const { user } = setup();

            await user.click(
                screen.getByRole('button', {
                    name: 'Move Metal shank button 17mm up',
                }),
            );

            // Now first: "up" is disabled, so focus lands on "down".
            expect(
                screen.getByRole('button', {
                    name: 'Move Metal shank button 17mm down',
                }),
            ).toHaveFocus();
        });
    });

    describe('server search', () => {
        it('shows rows it already knows without any request, and asks only when a picker opens', async () => {
            const { user } = setup();

            expect(inertiaMock.http.requests).toHaveLength(0);

            await user.click(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Denim 12oz Indigo/,
                }),
            );
            await optionsShown();

            expect(inertiaMock.http.requests).toHaveLength(1);
            expect(inertiaMock.http.requests[0].params).toEqual({
                limit: '20',
                include: ['4', '5', '8'],
            });
        });

        it('remembers a material found by the search: the row shows its name and unit', async () => {
            const onRows = vi.fn();
            const { user } = setup({ initial: [], onRows });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('cupsole');
            // The option is already in the first page, so wait for the
            // debounced search request itself before picking.
            await waitFor(() =>
                expect(
                    inertiaMock.http.requests.map((request) => request.params),
                ).toContainEqual({ search: 'cupsole', limit: '20' }),
            );
            await user.click(
                await screen.findByRole('option', {
                    name: /Rubber cupsole 42/,
                }),
            );

            const group = within(rowGroup(1));

            expect(
                group.getByRole('combobox', { name: /Rubber cupsole 42/ }),
            ).toHaveTextContent('MAT-SOL-042');
            expect(
                group.getByLabelText('Quantity per unit for Rubber cupsole 42')
                    .parentElement,
            ).toHaveTextContent('pair');
            expect(lastRows(onRows)).toMatchObject([{ material_id: 9 }]);
        });

        it('keeps a picked material shown when a later search does not find it', async () => {
            const { user } = setup({ initial: [] });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.click(
                await screen.findByRole('option', { name: /Brass rivet 9mm/ }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );
            await user.keyboard('canvas');
            await user.click(
                await screen.findByRole('option', {
                    name: /Cotton canvas 10oz/,
                }),
            );

            expect(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Brass rivet 9mm/,
                }),
            ).toBeInTheDocument();
            expect(
                within(rowGroup(2)).getByRole('combobox', {
                    name: /Cotton canvas 10oz/,
                }),
            ).toBeInTheDocument();
        });

        it('shows an unknown material by its id until the picker has loaded it', () => {
            setup({ initial: [row('a', 9, '1')] });

            expect(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Material #9/,
                }),
            ).toBeInTheDocument();
            expect(
                within(rowGroup(1)).getByLabelText(
                    'Quantity per unit for material 9',
                ).parentElement,
            ).toHaveTextContent('—');
        });

        it('stays light with 50 rows and 2000 materials: one request, 20 matches plus the picked ones', async () => {
            const catalogue: MaterialOption[] = Array.from(
                { length: 2000 },
                (_, index) => ({
                    id: 1000 + index,
                    sku: `SKU-${String(index).padStart(4, '0')}`,
                    name: `Material ${String(index).padStart(4, '0')}`,
                    unit: 'm',
                    unit_label: 'Metre (m)',
                    unit_cost: '1.0000',
                    quantity_on_hand: '10.0000',
                    stock_status: 'in_stock',
                }),
            );
            // The 50 picked ones are the last 50, far from the first page.
            const picked = catalogue.slice(-50);

            respondToHttp(({ params }) =>
                answerMaterialOptions(params, catalogue),
            );

            const { user } = setup({
                known: picked,
                initial: picked.map((material, index) =>
                    row(`r${index}`, material.id, '1'),
                ),
            });

            // The 50 rows are on screen without a single request.
            expect(screen.getByText('50 materials')).toBeInTheDocument();

            await user.click(
                within(rowGroup(1)).getByRole('combobox', {
                    name: /Material 1950/,
                }),
            );
            await optionsShown();

            expect(inertiaMock.http.requests).toHaveLength(1);

            const { params } = inertiaMock.http.requests[0];

            expect(params.limit).toBe('20');
            expect(params.include).toHaveLength(50);
            // 20 matches + 50 picked, never the 2000.
            expect(screen.getAllByRole('option')).toHaveLength(70);
        });
    });

    describe('errors', () => {
        const errors = {
            'components.1.material_id': 'Choose a different material.',
            'components.1.quantity': 'The quantity must be greater than 0.',
            'components.2.waste_percent': 'The waste must be below 100.',
            'components.2.notes': 'The notes are too long.',
        };

        it('shows a nested error on the row and field it belongs to', () => {
            setup({ errors });

            const second = within(rowGroup(2));

            expect(
                second.getByText('The quantity must be greater than 0.'),
            ).toBeInTheDocument();
            expect(
                second.getByText('Choose a different material.'),
            ).toBeInTheDocument();

            const third = within(rowGroup(3));

            expect(
                third.getByText('The waste must be below 100.'),
            ).toBeInTheDocument();
            expect(
                third.getByText('The notes are too long.'),
            ).toBeInTheDocument();

            // The first row is clean.
            expect(
                within(rowGroup(1)).queryByText(/must be|different|too long/),
            ).not.toBeInTheDocument();
        });

        it('marks the invalid field and links it to its message', () => {
            setup({ errors });

            const quantity = within(rowGroup(2)).getByLabelText(
                'Quantity per unit for Metal shank button 17mm',
            );

            expect(quantity).toBeInvalid();
            expect(quantity).toHaveAttribute('aria-invalid', 'true');
            expect(quantity).toHaveAccessibleDescription(
                'pc The quantity must be greater than 0.',
            );

            const picker = within(rowGroup(2)).getByRole('combobox', {
                name: /Metal shank button/,
            });

            expect(picker).toHaveAttribute('aria-invalid', 'true');
            expect(picker).toHaveAccessibleDescription(
                'Choose a different material.',
            );

            const clean = within(rowGroup(1)).getByLabelText(
                'Quantity per unit for Denim 12oz Indigo',
            );

            expect(clean).not.toHaveAttribute('aria-invalid');
        });

        it('shows an error about the list as a whole as an alert', () => {
            setup({
                initial: [],
                errors: { components: 'Add at least one material.' },
            });

            expect(screen.getByRole('alert')).toHaveTextContent(
                'Add at least one material.',
            );
        });
    });
});
