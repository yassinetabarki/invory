import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialDetailsCard } from '@/components/materials/material-details-card';
import { MaterialKpis } from '@/components/materials/material-kpis';
import { MaterialStockCallout } from '@/components/materials/material-stock-callout';
import { StockByColourCard } from '@/components/materials/stock-by-colour-card';
import { black, denim, indigo } from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Material, MaterialColourStock } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const indigoStock: MaterialColourStock = {
    colour: indigo,
    quantity_on_hand: '250.0000',
    unit_cost: '8.5000',
    stock_value: '2125.0000',
    stock_status: 'in_stock',
    removable: false,
    remove_reason: null,
};
const blackStock: MaterialColourStock = {
    colour: black,
    quantity_on_hand: '40.0000',
    unit_cost: '9.2000',
    stock_value: '368.0000',
    stock_status: 'low',
    removable: false,
    remove_reason: null,
};

/** Denim 12oz kept per colour, with cost per colour (the UI card's example). */
const denim12: Material = {
    ...denim,
    name: 'Denim 12oz',
    quantity_on_hand: '290.0000',
    reorder_level: '100.0000',
    unit_cost: '8.5000',
    stock_value: '2493.0000',
    stock_status: 'low',
    stock_mode: 'per_colour',
    cost_mode: 'per_colour',
    colours: [indigoStock, blackStock],
    unit_cost_range: { min: '8.5000', max: '9.2000' },
    stock_callout: {
        title: 'Black is below reorder level.',
        detail: '40 m on hand, reorder at 100 m.',
    },
};

/** The same material with one cost for every colour. */
const denim12SameCost: Material = {
    ...denim12,
    cost_mode: 'same',
    stock_value: '2465.0000',
    colours: [indigoStock, blackStock].map((row) => ({
        ...row,
        unit_cost: null,
        stock_value: null,
    })),
    unit_cost_range: null,
};

const table = () => within(screen.getByRole('table'));
const cells = (row: HTMLElement) =>
    Array.from(row.querySelectorAll('th, td')).map((cell) =>
        cell.textContent?.replace(/ /g, ' '),
    );

/** The value (`<dd>`) next to a `<dt>` label. */
function valueOf(label: string): HTMLElement {
    // The card's phone blocks have their own <dt>s: read the figures only.
    const value = within(
        screen.getByRole('group', { name: 'Figures' }),
    ).getByText(label, { selector: 'dt' }).nextElementSibling;

    if (!(value instanceof HTMLElement)) {
        throw new Error(`No value for "${label}"`);
    }

    return value;
}

describe('<StockByColourCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-11-12' },
        });
    });

    it("shows Denim 12oz's colours in order with their cost, the total, the figures and the named callout", () => {
        const onRestock = vi.fn();
        const { rerender } = render(
            <>
                <div role="group" aria-label="Figures">
                    <MaterialKpis material={denim12} />
                </div>
                <MaterialStockCallout material={denim12} />
                <StockByColourCard material={denim12} onRestock={onRestock} />
                <MaterialDetailsCard material={denim12} />
            </>,
        );

        expect(
            screen.getByRole('heading', { level: 2, name: 'Stock by colour' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Each colour has its own stock. A product takes its material from its own colour.',
            ),
        ).toBeInTheDocument();
        expect(
            table()
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual([
            'Colour',
            'In stock',
            'Unit cost',
            'Stock value',
            'Status',
            'Restock',
        ]);

        const [, indigoRow, blackRow, totalRow] = table().getAllByRole('row');

        expect(cells(indigoRow)).toEqual([
            'Indigo',
            '250 m',
            'TND 8.500 / m',
            'TND 2,125.000',
            'In stock',
            'Restock',
        ]);
        expect(cells(blackRow)).toEqual([
            'Black',
            '40 m',
            'TND 9.200 / m',
            'TND 368.000',
            'Low stock',
            'Restock',
        ]);
        expect(cells(totalRow)).toEqual([
            'All colours',
            '290 m',
            '',
            'TND 2,493.000',
            '',
            '',
        ]);

        // Only a colour that needs restock gets a badge; the button is named for its colour.
        expect(within(indigoRow).queryByText('In stock')).not.toHaveAttribute(
            'data-status',
        );
        expect(blackRow.querySelector('[data-status="low"]')).not.toBeNull();
        expect(
            within(blackRow).getByRole('button', { name: 'Restock Black' }),
        ).toBeInTheDocument();

        // The figures and the callout read what the server sent.
        expect(valueOf('In stock, all colours')).toHaveTextContent(
            '290 m2 colours',
        );
        expect(valueOf('Reorder at')).toHaveTextContent('100 meach colour');
        expect(valueOf('Unit cost')).toHaveTextContent(
            'TND 8.500–9.200 / mper colour',
        );
        expect(valueOf('Stock value')).toHaveTextContent('TND 2,493.000');
        expect(screen.getByText('Black is below reorder level.')).toBeVisible();
        expect(
            screen
                .getByText('Black is below reorder level.')
                .closest('[data-slot="callout"]'),
        ).toHaveTextContent('40 m on hand, reorder at 100 m.');

        // A per-colour material's Details leave out the free-text Colour.
        expect(screen.queryByText('Colour', { selector: 'dt' })).toBeNull();

        // With one cost for every colour the cost columns go.
        rerender(
            <StockByColourCard
                material={denim12SameCost}
                onRestock={onRestock}
            />,
        );

        expect(
            table()
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Colour', 'In stock', 'Status', 'Restock']);
        expect(screen.queryByText(/TND 8\.500/)).toBeNull();
    });

    it('with more than 8 colours shows the summary and a switch that filters without reordering', async () => {
        const user = userEvent.setup();
        const names = [
            'White',
            'Ecru',
            'Beige',
            'Camel',
            'Brown',
            'Grey',
            'Charcoal',
            'Black',
            'Navy',
        ];
        const stocks: MaterialColourStock[] = names.map((name, index) => ({
            ...indigoStock,
            colour: { ...indigo, id: 100 + index, name },
            // Beige, Black and Navy need restock.
            stock_status: ['Beige', 'Black', 'Navy'].includes(name)
                ? 'low'
                : 'in_stock',
        }));
        const material: Material = {
            ...denim12,
            colours: stocks,
            colours_restock_summary: '3 of 9 colours need restock',
        };
        const rowNames = () =>
            table()
                .getAllByRole('row')
                .slice(1, -1)
                .map((row) => cells(row)[0]);

        const { rerender } = render(<StockByColourCard material={material} />);

        expect(screen.getByText('3 of 9 colours need restock')).toBeVisible();
        expect(rowNames()).toEqual(names);
        // No handler: no Restock buttons.
        expect(screen.queryByRole('button', { name: /^Restock/ })).toBeNull();

        const toggle = screen.getByRole('switch', {
            name: 'Only colours that need restock',
        });

        expect(toggle).toHaveAttribute('aria-checked', 'false');
        await user.click(toggle);

        expect(toggle).toHaveAttribute('aria-checked', 'true');
        expect(rowNames()).toEqual(['Beige', 'Black', 'Navy']);
        // The total is still the whole material's.
        expect(table().getByText('290 m')).toBeInTheDocument();
        expect(
            screen.queryByText('No colour needs restock.'),
        ).not.toBeInTheDocument();

        // The filter on and every colour fine: the card says so.
        rerender(
            <StockByColourCard
                material={{
                    ...material,
                    colours: stocks.map((stock) => ({
                        ...stock,
                        stock_status: 'in_stock',
                    })),
                }}
            />,
        );

        expect(screen.getByText('No colour needs restock.')).toBeVisible();

        // 8 colours or fewer: neither the line nor the switch.
        rerender(<StockByColourCard material={denim12} />);

        expect(screen.queryByRole('switch')).toBeNull();
        expect(screen.queryByText(/colours need restock/)).toBeNull();
    });
});
