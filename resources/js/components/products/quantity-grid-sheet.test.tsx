import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vite-plus/test';
import type { CellError, QuantityGrid } from '@/components/products/bom-rows';
import { QuantityGridSheet } from '@/components/products/quantity-grid-sheet';
import { colourNamed, defaultColours } from '@/test/fixtures/colours';
import { sizeOptionsFixture } from '@/test/fixtures/products';
import type { Colour, SizeOption } from '@/types/domain';

const indigo = colourNamed('Indigo');
const black = colourNamed('Black');
const white = colourNamed('White');
const sizes = sizeOptionsFixture.slice(0, 4); // S, M, L, XL

const row = (...values: string[]) =>
    Object.fromEntries(sizes.map((size, index) => [size.id, values[index]]));

type HarnessProps = {
    colours?: Colour[];
    sizes?: SizeOption[];
    grid?: QuantityGrid;
    errors?: CellError[];
    readOnly?: boolean;
    onClose?: () => void;
};

/** The panel is controlled, so the test holds the grid like the editor does. */
function Harness({
    colours = [indigo, black],
    sizes: shownSizes = sizes,
    grid: initial = {},
    errors = [],
    readOnly,
    onClose = vi.fn(),
}: HarnessProps) {
    const [grid, setGrid] = useState(initial);

    return (
        <QuantityGridSheet
            title="Denim 12oz Indigo"
            unit="m"
            colours={colours}
            sizes={shownSizes}
            grid={grid}
            errors={errors}
            readOnly={readOnly}
            onChange={setGrid}
            onClose={onClose}
        />
    );
}

function setup(props: HarnessProps = {}) {
    const user = userEvent.setup();

    return { user, ...render(<Harness {...props} />) };
}

const field = (name: string) => screen.getByLabelText(name);
const colourRows = () =>
    within(screen.getByRole('list', { name: 'Colours' })).getAllByRole(
        'listitem',
    );

describe('<QuantityGridSheet>', () => {
    it('opens the colours that have a cell or an error, and sums each closed row up', async () => {
        const { user, unmount } = setup({
            colours: [indigo, black, white],
            grid: {
                [indigo.id]: row('1.6000', '1.8000', '2.0000', '2.2000'),
                [white.id]: row('1.8000', '1.8000', '1.8000', '1.8000'),
            },
        });

        expect(
            screen.getByRole('dialog', { name: 'Denim 12oz Indigo' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                "Quantity by colour and size, in m. A blank cell means that colour and size doesn't use this material.",
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('2 of 3 colours used')).toBeInTheDocument();
        expect(field('Indigo, size S')).toHaveValue('1.6000');
        expect(field('Indigo, size XL')).toHaveValue('2.2000');
        // Black has no cell: closed, nothing warns.
        expect(screen.queryByLabelText('Black, size S')).toBeNull();

        await user.click(screen.getByRole('button', { name: 'Collapse all' }));

        for (const [colour, summary] of [
            [/^Indigo/, '4 of 4 sizes · 1.6000–2.2000 m'],
            [/^Black/, 'Not used'],
            [/^White/, '4 of 4 sizes · 1.8000 m'],
        ] as const) {
            expect(
                screen.getByRole('button', { name: colour }),
            ).toHaveTextContent(summary);
        }

        // A refused cell opens its colour and sits under the cell.
        unmount();
        setup({
            errors: [
                {
                    colourId: black.id,
                    sizeId: sizes[1].id,
                    message:
                        'Enter a number from 0 to 1,000,000 with up to 4 decimals.',
                },
            ],
        });

        expect(field('Black, size M')).toHaveAccessibleDescription(
            expect.stringContaining('Enter a number from 0 to 1,000,000'),
        );
    });

    it('narrows 20 colours as you type in Find colour', async () => {
        const { user } = setup({ colours: defaultColours });

        expect(colourRows()).toHaveLength(20);

        await user.type(
            screen.getByRole('textbox', { name: 'Find colour' }),
            'ind',
        );

        expect(colourRows()).toHaveLength(1);
        expect(colourRows()[0]).toHaveTextContent('Indigo');
        // The count still speaks of the whole product.
        expect(screen.getByText('0 of 20 colours used')).toBeInTheDocument();
    });

    it('says when no colour matches Find colour', async () => {
        const { user } = setup({ colours: defaultColours });

        await user.type(
            screen.getByRole('textbox', { name: 'Find colour' }),
            'xyz',
        );

        expect(screen.queryByRole('list', { name: 'Colours' })).toBeNull();
        expect(
            screen.getByText('No colour matches "xyz".'),
        ).toBeInTheDocument();
    });

    it('opens the first colour of a blank grid, and the colours with cells otherwise', () => {
        const { unmount } = setup({ colours: [black, indigo, white] });

        expect(field('Black, size S')).toBeInTheDocument();
        expect(screen.queryByLabelText('Indigo, size S')).toBeNull();
        expect(screen.queryByLabelText('White, size S')).toBeNull();

        unmount();
        setup({
            colours: [black, indigo, white],
            grid: { [white.id]: row('1.8000', '', '', '') },
        });

        expect(field('White, size S')).toBeInTheDocument();
        expect(screen.queryByLabelText('Black, size S')).toBeNull();
    });

    it("names the field of a One size product with the workshop's own name for it", () => {
        setup({
            sizes: [{ id: 16, label: 'Free size', one_size: true }],
            grid: { [indigo.id]: { 16: '1.8000' } },
        });

        expect(field('Indigo, Free size')).toHaveValue('1.8000');
        expect(screen.queryByLabelText('Indigo, size Free size')).toBeNull();
    });

    it('opens and closes every colour with Expand all and Collapse all', async () => {
        const { user } = setup();

        // Blank grid: only the first colour is open.
        expect(field('Indigo, size S')).toBeInTheDocument();
        expect(screen.queryByLabelText('Black, size S')).toBeNull();

        await user.click(screen.getByRole('button', { name: 'Expand all' }));

        expect(field('Indigo, size S')).toBeInTheDocument();
        expect(field('Black, size XL')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Collapse all' }));

        expect(screen.queryByLabelText('Indigo, size S')).toBeNull();
        expect(screen.queryByLabelText('Black, size XL')).toBeNull();
    });

    it('fills the empty sizes of a colour with its first typed value and never changes a typed cell', async () => {
        const { user } = setup({
            grid: {
                [indigo.id]: row('1.6000', '', '2.0000', ''),
                [black.id]: row('1.6000', '', '', ''),
            },
        });

        // Black first: only S is typed.
        const [indigoRow, blackRow] = colourRows();

        await user.click(
            within(blackRow).getByRole('button', {
                name: 'Fill empty sizes with 1.6000 m',
            }),
        );

        for (const size of ['M', 'L', 'XL']) {
            expect(field(`Black, size ${size}`)).toHaveValue('1.6000');
        }

        // Indigo has L typed: it stays 2.0000.
        await user.click(
            within(indigoRow).getByRole('button', {
                name: 'Fill empty sizes with 1.6000 m',
            }),
        );

        expect(field('Indigo, size M')).toHaveValue('1.6000');
        expect(field('Indigo, size L')).toHaveValue('2.0000');
        expect(field('Indigo, size XL')).toHaveValue('1.6000');
        // Every size is typed: nothing left to fill.
        expect(
            screen.queryByRole('button', { name: /^Fill empty sizes/ }),
        ).toBeNull();
    });

    it('counts the cells filled, where empty and 0 are no cell', async () => {
        const { user } = setup({
            grid: { [indigo.id]: row('1.6000', '1.8000', '2.0000', '2.2000') },
        });

        expect(screen.getByText('4 of 8 cells filled')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Expand all' }));
        await user.type(field('Black, size S'), '1.6');

        expect(screen.getByText('5 of 8 cells filled')).toBeInTheDocument();

        await user.type(field('Black, size M'), '0');
        await user.clear(field('Indigo, size S'));

        expect(screen.getByText('4 of 8 cells filled')).toBeInTheDocument();
    });

    it('shows the cells as plain values when read-only', () => {
        const onClose = vi.fn();

        setup({
            readOnly: true,
            grid: { [indigo.id]: row('1.6000', '1.8000', '', '2.2000') },
            onClose,
        });

        const indigoRow = within(colourRows()[0]);

        expect(
            screen.getByRole('dialog', { name: 'Denim 12oz Indigo' }),
        ).toBeInTheDocument();
        expect(screen.queryByLabelText(/^Indigo, size/)).toBeNull();
        expect(indigoRow.getByText('1.8000 m')).toBeInTheDocument();
        expect(indigoRow.getByText('—')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: /^Fill empty sizes/ }),
        ).toBeNull();
        expect(screen.getByText('3 of 8 cells filled')).toBeInTheDocument();
    });
});
