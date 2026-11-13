import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { MaterialColoursTable } from '@/components/materials/material-colours-table';
import { black, indigo } from '@/test/fixtures';
import { defaultColours } from '@/test/fixtures/colours';
import type { MaterialColourStock } from '@/types/domain';

const stored: MaterialColourStock[] = [
    {
        colour: black,
        quantity_on_hand: '0.0000',
        unit_cost: null,
        stock_value: null,
        stock_status: 'out',
        removable: true,
        remove_reason: null,
    },
    {
        colour: indigo,
        quantity_on_hand: '250.0000',
        unit_cost: null,
        stock_value: null,
        stock_status: 'in_stock',
        removable: false,
        remove_reason: "Indigo has 250 m in stock, so it can't be removed.",
    },
];

describe('<MaterialColoursTable>', () => {
    it('removes a colour at 0 with no history, and keeps one with stock reachable and explained', async () => {
        const user = userEvent.setup();
        const onRemove = vi.fn();

        render(
            <MaterialColoursTable
                id="colours"
                colours={defaultColours}
                rows={[
                    {
                        uid: 1,
                        colour_id: black.id,
                        quantity_on_hand: '0.0000',
                        unit_cost: '',
                    },
                    {
                        uid: 2,
                        colour_id: indigo.id,
                        quantity_on_hand: '250.0000',
                        unit_cost: '',
                    },
                ]}
                stored={stored}
                onAdd={vi.fn()}
                onRemove={onRemove}
                onEdit={vi.fn()}
                costPerColour={false}
                unit="m"
                currencySymbol="€"
                errors={{}}
            />,
        );

        const indigoRemove = screen.getByRole('button', {
            name: 'Remove Indigo',
        });

        // Greyed but not disabled: the keyboard reaches it and hears why.
        expect(indigoRemove).toHaveAttribute('aria-disabled', 'true');
        expect(indigoRemove).toBeEnabled();
        expect(indigoRemove).toHaveAccessibleDescription(
            "Indigo has 250 m in stock, so it can't be removed.",
        );
        await user.click(indigoRemove);
        expect(onRemove).not.toHaveBeenCalled();

        const blackRemove = screen.getByRole('button', {
            name: 'Remove Black',
        });

        expect(blackRemove).not.toHaveAttribute('aria-disabled');
        await user.click(blackRemove);
        expect(onRemove).toHaveBeenCalledExactlyOnceWith(1);
        // The button that had the focus is gone: the focus stays in the table.
        expect(
            screen.getByRole('button', { name: 'Add colour' }),
        ).toHaveFocus();
    });
});
