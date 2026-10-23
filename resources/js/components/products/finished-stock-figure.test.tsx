import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { FinishedStockFigure } from '@/components/products/finished-stock-figure';
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

describe('<FinishedStockFigure>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it.each([
        {
            name: 'a chip per size, zeros greyed and spoken as one line',
            stock: stockOf(sizeListSToXl, [3, 0, 5, 2]),
            total: '10 units',
            chips: [
                ['S', '3'],
                ['M', '0'],
                ['L', '5'],
                ['XL', '2'],
            ],
            spoken: 'In stock per size: S 3, M 0, L 5, XL 2',
        },
        {
            name: 'nothing in stock, without chips or spoken line',
            stock: stockOf(sizeListSToXl, []),
            total: '0 units',
            chips: [],
            spoken: null,
        },
        {
            name: 'one unit, in the singular',
            stock: stockOf(sizeListSToXl, [0, 1, 0, 0]),
            total: '1 unit',
            chips: [
                ['S', '0'],
                ['M', '1'],
                ['L', '0'],
                ['XL', '0'],
            ],
            spoken: 'In stock per size: S 0, M 1, L 0, XL 0',
        },
        {
            name: 'a "One size" product, the total only',
            stock: stockOf(sizeListOneSize, [4]),
            total: '4 units',
            chips: [],
            spoken: null,
        },
        {
            name: "a shoe's 11 sizes, with the colon rule",
            stock: stockOf(sizeListEu36To46, [0, 2]),
            total: '2 units',
            chips: [
                ['36:', '0'],
                ['37:', '2'],
                ['38:', '0'],
                ['39:', '0'],
                ['40:', '0'],
                ['41:', '0'],
                ['42:', '0'],
                ['43:', '0'],
                ['44:', '0'],
                ['45:', '0'],
                ['46:', '0'],
            ],
            spoken: 'In stock per size: 36: 0, 37: 2, 38: 0, 39: 0, 40: 0, 41: 0, 42: 0, 43: 0, 44: 0, 45: 0, 46: 0',
        },
    ])('shows $name', ({ stock, total, chips, spoken }) => {
        render(
            <FinishedStockFigure
                product={{ id: 1, name: 'Denim Jacket' }}
                stock={stock}
            />,
        );

        const figure = screen.getByRole('region', { name: 'Finished stock' });
        const big = within(figure).getByText(/units?$/).parentElement;

        // A real space between the count and its word.
        expect(big?.textContent).toBe(total);

        // "Update stock" is at the top right, on the label's row, before the total.
        const button = within(figure).getByRole('button', {
            name: 'Update stock',
        });
        const label = within(figure).getByText('In stock');

        expect(label.parentElement).toContainElement(button);
        expect(label.parentElement).toHaveClass('justify-between');
        expect(
            button.compareDocumentPosition(big as Node) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();

        const spokenLine = within(figure).queryByText(/^In stock per size:/);

        if (spoken === null) {
            expect(spokenLine).toBeNull();
            expect(
                figure.querySelectorAll('[aria-hidden="true"]'),
            ).toHaveLength(0);

            return;
        }

        expect(spokenLine).toHaveTextContent(spoken);
        expect(spokenLine).toHaveClass('sr-only');

        // The chips are hidden from screen readers (the spoken line says it all) and wrap.
        const row = figure.querySelector('[aria-hidden="true"]');

        expect(row).toHaveClass('flex-wrap');
        expect(
            Array.from(row?.children ?? []).map((chip) => [
                chip.children[0].textContent,
                chip.children[1].textContent,
            ]),
        ).toEqual(chips);
        // Only a size at 0 is greyed.
        expect(
            Array.from(row?.children ?? [])
                .filter((chip) => chip.hasAttribute('data-zero'))
                .map((chip) => chip.children[1].textContent),
        ).toEqual(chips.filter(([, count]) => count === '0').map(([, c]) => c));
    });

    it.each([
        {
            way: 'Cancel',
            close: (user: ReturnType<typeof userEvent.setup>) =>
                user.click(screen.getByRole('button', { name: 'Cancel' })),
        },
        {
            way: 'Escape',
            close: (user: ReturnType<typeof userEvent.setup>) =>
                user.keyboard('{Escape}'),
        },
        {
            way: 'Save',
            close: async (user: ReturnType<typeof userEvent.setup>) => {
                await user.click(screen.getByRole('button', { name: 'Save' }));
                const [, , options] =
                    inertiaMock.router.put.mock.calls.at(-1) ?? [];

                act(() => (options as { onSuccess: () => void }).onSuccess());
            },
        },
    ])(
        'puts focus back on "Update stock" when the window closes by $way',
        async ({ close }) => {
            const user = userEvent.setup();

            render(
                <FinishedStockFigure
                    product={{ id: 1, name: 'Denim Jacket' }}
                    stock={stockOf(sizeListSToXl, [3, 0, 5, 2])}
                />,
            );

            const button = screen.getByRole('button', { name: 'Update stock' });

            await user.click(button);
            expect(screen.getByRole('dialog')).toBeInTheDocument();

            await close(user);

            await waitFor(
                () =>
                    expect(
                        screen.queryByRole('dialog'),
                    ).not.toBeInTheDocument(),
                { timeout: 1000 },
            );
            await waitFor(() => expect(button).toHaveFocus(), {
                timeout: 1000,
            });
        },
    );
});
