import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { ColoursField } from '@/components/products/colours-field';
import { defaultColours } from '@/test/fixtures/colours';

// The app's phone breakpoint is read once at import: stand in for it.
const device = vi.hoisted(() => ({ mobile: false }));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

function Harness() {
    const [value, setValue] = useState<number[]>([]);

    return (
        <ColoursField
            id="colours"
            colours={defaultColours}
            value={value}
            onChange={setValue}
        />
    );
}

async function openPicker() {
    const user = userEvent.setup();

    render(<Harness />);

    const add = screen.getByRole('button', { name: 'Add colour' });

    await user.click(add);

    return {
        user,
        add,
        dialog: within(screen.getByRole('dialog', { name: 'Choose colours' })),
        search: screen.getByRole('combobox', { name: 'Search colours' }),
    };
}

describe('<ColourPicker>', () => {
    afterEach(() => {
        device.mobile = false;
    });

    it('filters by name or code, says when nothing matches (no link) and chooses with Enter or a click while the list stays open', async () => {
        const { user, dialog, search } = await openPicker();

        // On a laptop the focus starts in the search.
        expect(search).toHaveFocus();

        // The workshop's colours in list order, each spoken as "Indigo, IND".
        expect(
            dialog
                .getAllByRole('option')
                .map((option) => option.getAttribute('aria-label')),
        ).toEqual(defaultColours.map((c) => `${c.name}, ${c.code}`));
        expect(dialog.getByText('0 chosen')).toBeInTheDocument();

        await user.type(search, 'ind');
        expect(dialog.getAllByRole('option')).toHaveLength(1);
        expect(
            dialog.getByRole('option', { name: 'Indigo, IND' }),
        ).toHaveAttribute('aria-selected', 'false');
        await user.keyboard('{Enter}');
        expect(
            dialog.getByRole('option', { name: 'Indigo, IND', selected: true }),
        ).toBeInTheDocument();
        expect(dialog.getByText('1 chosen')).toBeInTheDocument();

        await user.clear(search);
        await user.type(search, 'BLK');
        expect(
            dialog
                .getAllByRole('option')
                .map((option) => option.getAttribute('aria-label')),
        ).toEqual(['Black, BLK']);
        await user.keyboard('{Enter}');

        // Arrow keys move the active row; Enter chooses it.
        await user.clear(search);
        await user.keyboard('{ArrowDown}{ArrowDown}{Enter}');
        expect(
            dialog.getByRole('option', { name: 'Beige, BEI', selected: true }),
        ).toBeInTheDocument();
        expect(search).toHaveAttribute(
            'aria-activedescendant',
            dialog.getByRole('option', { name: 'Beige, BEI' }).id,
        );
        expect(dialog.getByText('3 chosen')).toBeInTheDocument();

        // A click un-chooses; the list is still open.
        await user.click(
            dialog.getByRole('option', { name: 'Indigo, IND', selected: true }),
        );
        expect(dialog.getByText('2 chosen')).toBeInTheDocument();

        await user.type(search, 'teal');
        expect(dialog.queryAllByRole('option')).toHaveLength(0);
        expect(dialog.queryByRole('listbox')).toBeNull();
        expect(search).not.toHaveAttribute('aria-controls');
        expect(dialog.getByText('No colour named "teal".')).toBeInTheDocument();
        expect(
            dialog.getByText('Missing one? Add it in Settings › Colours.'),
        ).toBeInTheDocument();
        expect(dialog.queryByRole('link')).toBeNull();
    });

    it('closes with Done or Esc and puts the focus back on Add colour', async () => {
        const { user, add, dialog } = await openPicker();

        await user.click(dialog.getByRole('button', { name: 'Done' }));
        expect(screen.queryByRole('dialog')).toBeNull();
        await waitFor(() => expect(add).toHaveFocus());

        await user.click(add);
        expect(
            screen.getByRole('dialog', { name: 'Choose colours' }),
        ).toBeInTheDocument();
        await user.keyboard('{Escape}');
        expect(screen.queryByRole('dialog')).toBeNull();
        await waitFor(() => expect(add).toHaveFocus());
    });

    it('is a full-screen sheet on a phone, keeps the focus inside and gives it back when closed', async () => {
        device.mobile = true;

        const { user, add, dialog } = await openPicker();
        const sheet = screen.getByRole('dialog', { name: 'Choose colours' });

        expect(dialog.getByRole('button', { name: 'Close' })).toBeVisible();
        expect(dialog.getByText('0 chosen')).toBeInTheDocument();
        expect(sheet).toContainElement(document.activeElement as HTMLElement);

        await user.tab({ shift: true });
        await user.tab({ shift: true });
        await user.tab({ shift: true });
        expect(sheet).toContainElement(document.activeElement as HTMLElement);

        await user.click(dialog.getByRole('button', { name: 'Close' }));
        expect(screen.queryByRole('dialog')).toBeNull();
        await waitFor(() => expect(add).toHaveFocus());
    });

    it('opens on a phone with the focus on the sheet, no active row until the search is used, and Done gives the focus back', async () => {
        device.mobile = true;

        const { user, add, dialog, search } = await openPicker();
        const sheet = screen.getByRole('dialog', { name: 'Choose colours' });

        // The keyboard stays closed: the focus is on the sheet, not in the search.
        expect(sheet).toHaveFocus();
        expect(search).not.toHaveFocus();
        expect(search).not.toHaveAttribute('aria-activedescendant');

        await user.click(search);
        expect(search).toHaveAttribute(
            'aria-activedescendant',
            dialog.getByRole('option', { name: 'White, WHT' }).id,
        );

        await user.click(dialog.getByRole('button', { name: 'Done' }));
        expect(screen.queryByRole('dialog')).toBeNull();
        await waitFor(() => expect(add).toHaveFocus());
    });
});
