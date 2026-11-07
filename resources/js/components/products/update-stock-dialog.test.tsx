import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useRef, useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { UpdateStockDialog } from '@/components/products/update-stock-dialog';
import { colourNamed } from '@/test/fixtures/colours';
import {
    denimStockByColour,
    sizeListEu36To46,
    sizeListSToXl,
    stockByColour,
    toteStockByColour,
} from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { FinishedStock } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const denim = { id: 1, name: 'Denim Jacket', one_size: false };
const tote = { id: 2, name: 'Denim Jacket', one_size: true };
const changedMessage =
    'The sizes or colours of Denim Jacket have changed. Enter the stock again.';
const [black, indigo, lightWash] = ['Black', 'Indigo', 'Light wash'].map(
    (name) => colourNamed(name).id,
);

/** The callbacks Inertia would call on the visit the window made. */
type Visit = {
    only: string[];
    preserveScroll: boolean;
    preserveState: boolean;
    onStart: () => void;
    onSuccess: () => void;
    onError: (errors: Record<string, string>) => void;
    onHttpException: () => unknown;
    onNetworkError: () => unknown;
    onFinish: () => void;
};

function lastVisit(): { url: string; data: unknown; options: Visit } {
    const [url, data, options] = inertiaMock.router.put.mock.calls.at(-1) ?? [];

    return { url: String(url), data, options: options as Visit };
}

/** The server answers the save with these errors. */
function refuse(errors: Record<string, string>) {
    act(() => {
        lastVisit().options.onStart();
        lastVisit().options.onError(errors);
        lastVisit().options.onFinish();
    });
}

/** The page's "Update stock" button, which opens the window and gets the focus back. */
function Page({
    stock,
    product = denim,
}: {
    stock: FinishedStock;
    product?: { id: number; name: string; one_size: boolean };
}) {
    const [open, setOpen] = useState(false);
    const buttonRef = useRef<HTMLButtonElement>(null);

    return (
        <>
            <button type="button" ref={buttonRef} onClick={() => setOpen(true)}>
                Update stock
            </button>
            <UpdateStockDialog
                product={product}
                stock={stock}
                open={open}
                onOpenChange={setOpen}
                returnFocusTo={buttonRef}
            />
        </>
    );
}

async function openWindow(stock = denimStockByColour, product = denim) {
    const user = userEvent.setup();
    const view = render(<Page stock={stock} product={product} />);

    await user.click(screen.getByRole('button', { name: 'Update stock' }));

    return {
        user,
        show: (next: FinishedStock) =>
            view.rerender(<Page stock={next} product={product} />),
    };
}

const dialog = () =>
    screen.getByRole('dialog', { name: 'Update stock · Denim Jacket' });
const field = (label: string) => within(dialog()).getByLabelText(label);
const pill = (name: string) => within(dialog()).getByRole('radio', { name });
const band = () =>
    within(dialog()).getByText('Total after saving, all colours').parentElement;

describe('<UpdateStockDialog>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('counts per colour and size, keeps what was typed when switching colours, saves every colour and gives the focus back (AC-3)', async () => {
        const { user } = await openWindow();

        expect(dialog()).toHaveAccessibleDescription(
            'Finished units on the shelf, counted per colour and size. Saving sets these counts; to add units that arrived, use Restock.',
        );
        expect(
            within(dialog()).getByRole('button', { name: 'Close' }),
        ).toBeInTheDocument();

        // The pills: the colour's total after saving; the first colour is chosen.
        const pills = within(
            within(dialog()).getByRole('radiogroup', { name: 'Colour' }),
        ).getAllByRole('radio');

        expect(pills.map((radio) => radio.getAttribute('aria-label'))).toEqual([
            'Black, 4',
            'Indigo, 6',
            'Light wash, 0',
        ]);
        expect(pill('Black, 4')).toBeChecked();
        expect(
            within(dialog()).getByRole('group', {
                name: 'Units in stock per size · Black',
            }),
        ).toBeInTheDocument();
        expect(band()).toHaveTextContent(
            'Total after saving, all colours 10 units',
        );
        expect(dialog()).toHaveTextContent(
            `Doesn't change materials, "Can produce now" or sales.`,
        );

        await user.click(pill('Indigo, 6'));

        expect(
            within(dialog()).getByRole('group', {
                name: 'Units in stock per size · Indigo',
            }),
        ).toBeInTheDocument();
        expect(['S', 'M', 'L', 'XL'].map((size) => field(size))).toHaveLength(
            4,
        );
        expect(field('S')).toHaveValue('2');
        expect(field('M')).toHaveValue('0');
        expect(field('L')).toHaveValue('3');
        expect(field('XL')).toHaveValue('1');
        expect(field('M')).not.toHaveAccessibleDescription();

        // Indigo M from 0 to 4: "was 0", a dot on Indigo (spoken), the pill and the total follow.
        await user.clear(field('M'));
        await user.type(field('M'), '4');

        expect(field('M')).toHaveAccessibleDescription('was 0');
        expect(pill('Indigo, 10, changed')).toBeChecked();
        expect(pill('Black, 4')).toBeInTheDocument();
        expect(band()).toHaveTextContent('14 units');

        // Switching pills keeps what was typed in every colour.
        await user.click(pill('Black, 4'));
        expect(field('S')).toHaveValue('1');
        await user.click(pill('Indigo, 10, changed'));
        expect(field('M')).toHaveValue('4');
        expect(field('M')).toHaveAccessibleDescription('was 0');

        await user.click(screen.getByRole('button', { name: 'Save' }));

        expect(lastVisit()).toMatchObject({
            url: '/products/1/stock',
            data: {
                stock: {
                    [black]: { 1: '1', 2: '0', 3: '2', 4: '1' },
                    [indigo]: { 1: '2', 2: '4', 3: '3', 4: '1' },
                    [lightWash]: { 1: '0', 2: '0', 3: '0', 4: '0' },
                },
            },
            options: {
                only: ['finished_stock'],
                preserveScroll: true,
                preserveState: true,
            },
        });

        // Save waits, with a spinner, while the request runs.
        act(() => lastVisit().options.onStart());
        expect(screen.getByRole('button', { name: /save/i })).toBeDisabled();
        expect(within(dialog()).getByRole('status')).toBeInTheDocument();

        // The window closes when the save went through, and the focus goes back to "Update stock".
        act(() => {
            lastVisit().options.onSuccess();
            lastVisit().options.onFinish();
        });

        await waitFor(
            () =>
                expect(
                    screen.getByRole('button', { name: 'Update stock' }),
                ).toHaveFocus(),
            { timeout: 1000 },
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('brings the first colour with a refused number forward and focuses its field (AC-3)', async () => {
        const { user } = await openWindow();
        const refused = 'M: Enter a whole number from 0 to 1,000,000.';

        await user.click(pill('Indigo, 6'));
        await user.click(screen.getByRole('button', { name: 'Save' }));
        refuse({ [`stock.${black}.2`]: refused });

        // The pill says so in text; the top names the colour; the window switched and focused the field.
        expect(pill('Black, 4, 1 error')).toBeChecked();
        expect(
            within(pill('Black, 4, 1 error').closest('label')!).getByText(
                '1 error',
            ),
        ).toBeInTheDocument();
        expect(within(dialog()).getByRole('alert')).toHaveTextContent(
            "Check Black: 1 size can't be saved.",
        );
        expect(
            within(dialog()).getByRole('group', {
                name: 'Units in stock per size · Black',
            }),
        ).toBeInTheDocument();
        expect(field('M')).toHaveFocus();
        expect(field('M')).toBeInvalid();
        expect(field('M')).toHaveAccessibleDescription(refused);
        expect(field('S')).toBeValid();
    });

    it('has no pills, no "Check …" line and a plain total for a product with one colour; a refused size shows its message and takes the focus (AC-3)', async () => {
        const { user } = await openWindow(
            stockByColour(sizeListSToXl, [['White', [3, 0, 5, 2]]]),
        );
        const refused = 'M: Enter a whole number from 0 to 1,000,000.';

        expect(within(dialog()).queryByRole('radiogroup')).toBeNull();
        expect(
            within(dialog()).getByRole('group', {
                name: 'Units in stock per size · White',
            }),
        ).toBeInTheDocument();
        expect(
            within(dialog()).getByText('Total after saving').parentElement,
        ).toHaveTextContent('Total after saving 10 units');
        expect(
            within(dialog()).queryByText('Total after saving, all colours'),
        ).toBeNull();

        await user.click(screen.getByRole('button', { name: 'Save' }));
        refuse({ [`stock.${colourNamed('White').id}.2`]: refused });

        expect(within(dialog()).queryByRole('alert')).toBeNull();
        expect(within(dialog()).queryByText(/^Check /)).toBeNull();
        expect(field('M')).toHaveFocus();
        expect(field('M')).toHaveAccessibleDescription(refused);
        expect(within(dialog()).getByText(refused)).toBeInTheDocument();
    });

    it('shows the new colours and sizes, from their counts, when they changed meanwhile (AC-3)', async () => {
        const { user, show } = await openWindow();

        await user.clear(field('S'));
        await user.type(field('S'), '9');
        await user.click(screen.getByRole('button', { name: 'Save' }));
        refuse({ stock: changedMessage });
        // The refusal's redirect brings back `finished_stock` as the product is now.
        show(
            stockByColour(sizeListEu36To46, [
                ['Indigo', [0, 2, 0, 0, 7]],
                ['Navy', [1]],
            ]),
        );

        expect(within(dialog()).getByRole('alert')).toHaveTextContent(
            changedMessage,
        );
        expect(
            within(dialog())
                .getAllByRole('radio')
                .map((radio) => radio.getAttribute('aria-label')),
        ).toEqual(['Indigo, 9', 'Navy, 1']);
        expect(pill('Indigo, 9')).toBeChecked();
        expect(within(dialog()).getAllByRole('textbox')).toHaveLength(11);
        expect(field('36')).toHaveValue('0');
        expect(field('37')).toHaveValue('2');
        expect(field('40')).toHaveValue('7');
        expect(within(dialog()).queryByLabelText('XL')).not.toBeInTheDocument();
        expect(band()).toHaveTextContent('10 units');
    });

    it('keeps the window and what was typed in every colour when the save failed (AC-3)', async () => {
        const { user } = await openWindow();
        const onClose = vi.fn();

        await user.clear(field('S'));
        await user.type(field('S'), '7');
        await user.click(pill('Indigo, 6'));
        await user.clear(field('M'));
        await user.type(field('M'), '4');
        await user.click(screen.getByRole('button', { name: 'Save' }));
        act(() => lastVisit().options.onStart());

        let stopsInertia: unknown;

        act(() => {
            stopsInertia = lastVisit().options.onHttpException();
            lastVisit().options.onFinish();
        });

        // `false` keeps Inertia from showing its own error window.
        expect(stopsInertia).toBe(false);
        expect(within(dialog()).getByRole('alert')).toHaveTextContent(
            "Couldn't save the stock. Try again.",
        );
        expect(screen.getByRole('button', { name: 'Save' })).toBeEnabled();
        expect(onClose).not.toHaveBeenCalled();
        expect(field('M')).toHaveValue('4');

        await user.click(pill('Black, 10, changed'));
        expect(field('S')).toHaveValue('7');
        expect(band()).toHaveTextContent('20 units');
    });

    it('keeps the size field for a product whose only size is M, which is not One size (AC-7)', async () => {
        const onlyM = { ...sizeListSToXl, sizes: [sizeListSToXl.sizes[1]] };
        await openWindow(
            stockByColour(onlyM, [
                ['Black', [3]],
                ['Indigo', [2]],
            ]),
            denim,
        );

        expect(
            within(dialog()).getByRole('group', {
                name: 'Units in stock per size · Black',
            }),
        ).toBeInTheDocument();
        expect(within(dialog()).getAllByRole('textbox')).toHaveLength(1);
        expect(field('M')).toHaveValue('3');
    });

    it('has no pills for a "One size" product: a field per colour, named by the colour (AC-3)', async () => {
        const { user } = await openWindow(toteStockByColour, tote);

        expect(within(dialog()).queryByRole('radiogroup')).toBeNull();
        expect(
            within(dialog()).getByRole('group', {
                name: 'Units in stock per colour',
            }),
        ).toBeInTheDocument();
        expect(within(dialog()).getAllByRole('textbox')).toHaveLength(2);
        expect(field('Camel')).toHaveValue('4');
        expect(field('Black')).toHaveValue('0');
        expect(band()).toHaveTextContent('4 units');

        await user.clear(field('Black'));
        await user.type(field('Black'), '3');

        expect(field('Black')).toHaveAccessibleDescription('was 0');
        expect(band()).toHaveTextContent('7 units');

        await user.click(screen.getByRole('button', { name: 'Save' }));

        expect(lastVisit().data).toEqual({
            stock: {
                [colourNamed('Camel').id]: { 16: '4' },
                [black]: { 16: '3' },
            },
        });

        refuse({
            [`stock.${black}.16`]:
                'One size: Enter a whole number from 0 to 1,000,000.',
        });

        expect(field('Black')).toHaveAccessibleDescription(
            expect.stringContaining(
                'Enter a whole number from 0 to 1,000,000.',
            ),
        );
        expect(field('Black')).toHaveFocus();
    });
});
