import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { UpdateStockDialog } from '@/components/products/update-stock-dialog';
import {
    sizeListEu36To46,
    sizeListOneSize,
    sizeListSToXl,
} from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { FinishedStock, SizeList } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const denim = { id: 1, name: 'Denim Jacket' };
const sizesChanged =
    'The sizes of Denim Jacket have changed. Enter the stock again.';

/** The stock the server sends: every size of the list, in order, with its units. */
function stockOf(list: SizeList, units: number[]): FinishedStock {
    return {
        units: units.reduce((sum, count) => sum + count, 0),
        sizes: list.sizes.map((size, index) => ({
            size,
            units: units[index] ?? 0,
        })),
    };
}

const denimStock = stockOf(sizeListSToXl, [3, 0, 5, 2]);

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

function openWindow(
    stock = denimStock,
    onOpenChange: (open: boolean) => void = () => undefined,
) {
    const ui = (next: FinishedStock) => (
        <UpdateStockDialog
            product={denim}
            stock={next}
            open
            onOpenChange={onOpenChange}
        />
    );
    const view = render(ui(stock));

    return { ...view, show: (next: FinishedStock) => view.rerender(ui(next)) };
}

const dialog = () =>
    screen.getByRole('dialog', { name: 'Update stock · Denim Jacket' });
const field = (label: string) => within(dialog()).getByLabelText(label);
const band = () =>
    within(dialog()).getByText('Total after saving').parentElement;

describe('<UpdateStockDialog>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('opens on today’s counts and saves what is typed, by size id', async () => {
        const user = userEvent.setup();
        const onOpenChange = vi.fn();
        const { unmount } = openWindow(denimStock, onOpenChange);

        expect(dialog()).toHaveAccessibleDescription(
            `Doesn't change materials, "Can produce now" or sales.`,
        );
        expect(['S', 'M', 'L', 'XL'].map((label) => field(label))).toHaveLength(
            4,
        );
        expect(field('S')).toHaveValue('3');
        expect(field('M')).toHaveValue('0');
        expect(field('L')).toHaveValue('5');
        expect(field('XL')).toHaveValue('2');
        expect(band()).toHaveTextContent('Total after saving 10 units');

        // Typing moves the total; only the size that differs from today shows "was" and the outline.
        await user.clear(field('M'));
        await user.type(field('M'), '4');
        expect(band()).toHaveTextContent('Total after saving 14 units');
        expect(field('M')).toHaveAccessibleDescription('was 0');
        expect(field('M')).toHaveClass('border-primary');

        for (const label of ['S', 'L', 'XL']) {
            expect(field(label)).not.toHaveAccessibleDescription();
            expect(field(label)).not.toHaveClass('border-primary');
        }

        await user.click(screen.getByRole('button', { name: 'Save' }));

        expect(lastVisit()).toMatchObject({
            url: '/products/1/stock',
            data: { sizes: { 1: '3', 2: '4', 3: '5', 4: '2' } },
            options: {
                only: ['finished_stock'],
                preserveScroll: true,
                preserveState: true,
            },
        });

        // Put back to today's count, M loses its hint and outline.
        await user.clear(field('M'));
        await user.type(field('M'), '0');
        expect(field('M')).not.toHaveAccessibleDescription();
        expect(field('M')).not.toHaveClass('border-primary');
        expect(band()).toHaveTextContent('Total after saving 10 units');

        // An emptied field goes empty; the server counts it 0.
        await user.clear(field('M'));
        await user.click(screen.getByRole('button', { name: 'Save' }));

        expect(lastVisit().data).toEqual({
            sizes: { 1: '3', 2: '', 3: '5', 4: '2' },
        });

        // The window closes when the save went through.
        act(() => lastVisit().options.onSuccess());
        expect(onOpenChange).toHaveBeenCalledWith(false);

        onOpenChange.mockClear();
        inertiaMock.router.put.mockClear();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(onOpenChange).toHaveBeenCalledWith(false);
        expect(inertiaMock.router.put).not.toHaveBeenCalled();

        unmount();
        openWindow(stockOf(sizeListOneSize, [4]));

        expect(field('One size')).toHaveValue('4');
        expect(within(dialog()).getAllByRole('textbox')).toHaveLength(1);
    });

    it.each([
        { size: 'M', typed: '4', total: '14 units' },
        { size: 'M', typed: '', total: '10 units' },
        { size: 'M', typed: '2.5', total: '10 units' },
        { size: 'M', typed: 'abc', total: '10 units' },
        { size: 'S', typed: '', total: '7 units' },
    ])(
        'counts $typed in $size as the total $total',
        async ({ size, typed, total }) => {
            const user = userEvent.setup();

            openWindow();
            await user.clear(field(size));

            if (typed !== '') {
                await user.type(field(size), typed);
            }

            expect(band()).toHaveTextContent(`Total after saving ${total}`);
        },
    );

    it.each<{
        name: string;
        errors: Record<string, string>;
        expectation: () => void;
    }>([
        {
            name: 'a refused number shows under the fields, naming its size',
            errors: {
                'sizes.2': 'M: Enter a whole number from 0 to 1,000,000.',
            },
            expectation: () => {
                expect(field('M')).toHaveAccessibleDescription(
                    'M: Enter a whole number from 0 to 1,000,000.',
                );
                expect(field('M')).toBeInvalid();
                expect(field('S')).toBeValid();
            },
        },
        {
            name: 'the sizes having changed shows at the top of the window',
            errors: { sizes: sizesChanged },
            expectation: () => {
                expect(within(dialog()).getByRole('alert')).toHaveTextContent(
                    sizesChanged,
                );
                expect(field('S')).toHaveAccessibleDescription(sizesChanged);
            },
        },
    ])('$name', async ({ errors, expectation }) => {
        const user = userEvent.setup();

        openWindow();
        await user.click(screen.getByRole('button', { name: 'Save' }));
        act(() => {
            lastVisit().options.onStart();
            lastVisit().options.onError(errors);
            lastVisit().options.onFinish();
        });

        expectation();
    });

    it('shows the new sizes under the same message when they changed', async () => {
        const user = userEvent.setup();
        const { show } = openWindow();

        await user.type(field('S'), '9');
        await user.click(screen.getByRole('button', { name: 'Save' }));
        act(() => {
            lastVisit().options.onStart();
            lastVisit().options.onError({ sizes: sizesChanged });
            lastVisit().options.onFinish();
        });
        // The refusal's redirect brings back `finished_stock` with the product's sizes now.
        show(stockOf(sizeListEu36To46, [0, 2, 0, 0, 7]));

        expect(within(dialog()).getByRole('alert')).toHaveTextContent(
            sizesChanged,
        );
        expect(within(dialog()).getAllByRole('textbox')).toHaveLength(11);
        expect(field('36')).toHaveValue('0');
        expect(field('37')).toHaveValue('2');
        expect(field('40')).toHaveValue('7');
        expect(within(dialog()).queryByLabelText('XL')).not.toBeInTheDocument();
    });

    it.each(['onHttpException', 'onNetworkError'] as const)(
        'keeps the window and what was typed when %s stops the save',
        async (failure) => {
            const user = userEvent.setup();
            const onOpenChange = vi.fn();

            openWindow(denimStock, onOpenChange);
            await user.clear(field('S'));
            await user.type(field('S'), '7');
            await user.click(screen.getByRole('button', { name: 'Save' }));
            act(() => lastVisit().options.onStart());

            // Save waits while the request runs.
            expect(
                screen.getByRole('button', { name: /save/i }),
            ).toBeDisabled();

            let stopsInertia: unknown;

            act(() => {
                stopsInertia = lastVisit().options[failure]();
                lastVisit().options.onFinish();
            });

            // `false` keeps Inertia from showing its own error window.
            expect(stopsInertia).toBe(false);
            expect(within(dialog()).getByRole('alert')).toHaveTextContent(
                "Couldn't save the stock. Try again.",
            );
            expect(field('S')).toHaveValue('7');
            expect(screen.getByRole('button', { name: 'Save' })).toBeEnabled();
            expect(onOpenChange).not.toHaveBeenCalled();
        },
    );

    it('wraps the fields, each labelled, with Save and Cancel after the last', () => {
        openWindow(stockOf(sizeListEu36To46, []));

        const group = within(dialog()).getByRole('group', {
            name: 'Units in stock, by size',
        });
        const inputs = within(group).getAllByRole('textbox');

        expect(inputs[0].closest('[data-slot="size-fields"]')).not.toBeNull();
        expect(inputs).toHaveLength(11);

        for (const input of inputs) {
            expect(input).toHaveAccessibleName();
        }

        const last = inputs[inputs.length - 1];

        for (const name of ['Save', 'Cancel']) {
            const button = screen.getByRole('button', { name });

            expect(
                last.compareDocumentPosition(button) &
                    Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();
        }
    });
});
