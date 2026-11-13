import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialsTable } from '@/components/materials/materials-table';
import { denim } from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Material, MaterialColourStock } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const hrefs = {
    show: (m: Material) => `/materials/${m.id}`,
    edit: (m: Material) => `/materials/${m.id}/edit`,
};

function colourStock(
    id: number,
    name: string,
    quantity: string,
    stock_status: MaterialColourStock['stock_status'],
): MaterialColourStock {
    return {
        colour: {
            id,
            code: name.slice(0, 3).toUpperCase(),
            name,
            swatch: '#3E4C7E',
        },
        quantity_on_hand: quantity,
        unit_cost: null,
        stock_value: null,
        stock_status,
        removable: false,
        remove_reason: null,
    };
}

/** Denim 12oz, stock per colour: Indigo 250 m (fine), Black 40 m (low), reorder at 100 m. */
const denimByColour: Material = {
    ...denim,
    id: 7,
    name: 'Denim 12oz',
    stock_mode: 'per_colour',
    quantity_on_hand: '290.0000',
    reorder_level: '100.0000',
    stock_status: 'low',
    colours: [
        colourStock(1, 'Indigo', '250.0000', 'in_stock'),
        colourStock(2, 'Black', '40.0000', 'low'),
    ],
    restock_colours: 'Black',
};

/** The table (>= md); the stacked cards (< md) are in the DOM too. */
function table() {
    return within(screen.getByRole('table', { name: 'Materials' }));
}

describe('<MaterialsTable> stock by colour', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({});
    });

    it('shows the total, the colours button and the panel with a tile per colour', async () => {
        const user = userEvent.setup();

        const { rerender } = render(
            <MaterialsTable materials={[denimByColour]} hrefs={hrefs} />,
        );

        const row = within(table().getByRole('row', { name: /Denim 12oz/ }));

        expect(row.getByText('290 m')).toBeVisible();
        expect(row.getByText('each colour')).toBeVisible();
        expect(row.getByText('Low stock')).toBeVisible();
        expect(row.getByText('Black')).toBeVisible();

        const button = table().getByRole('button', {
            name: '2 colours, show stock by colour for Denim 12oz',
        });

        expect(button).toHaveTextContent('2 colours');
        expect(button).toHaveAttribute('aria-expanded', 'false');

        await user.click(button);

        const group = table().getByRole('group', {
            name: 'Stock by colour · Denim 12oz',
        });
        const panel = within(group);
        const tiles = panel.getAllByRole('listitem');

        expect(tiles).toHaveLength(2);
        expect(tiles[0]).toHaveTextContent('Indigo');
        expect(tiles[0]).toHaveTextContent('250 m');
        expect(within(tiles[0]).queryByText(/stock/i)).not.toBeInTheDocument();
        expect(tiles[1]).toHaveTextContent('Black');
        expect(tiles[1]).toHaveTextContent('40 m');
        expect(within(tiles[1]).getByText('Low stock')).toBeVisible();
        expect(group).toHaveTextContent('Reorder at 100 m for each colour.');

        await user.click(panel.getByRole('button', { name: 'Close' }));

        expect(
            table().queryByRole('group', {
                name: 'Stock by colour · Denim 12oz',
            }),
        ).not.toBeInTheDocument();
        expect(button).toHaveFocus();

        rerender(
            <MaterialsTable
                materials={[
                    { ...denimByColour, colours: [denimByColour.colours[0]] },
                ]}
                hrefs={hrefs}
            />,
        );

        expect(
            table().getByRole('button', {
                name: '1 colour, show stock by colour for Denim 12oz',
            }),
        ).toHaveTextContent('1 colour');
    });
});
