import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SizesColoursCard } from '@/components/products/sizes-colours-card';
import {
    denimStockByColour,
    sizeListEu36To46,
    sizeListOneSize,
    sizeListSToXl,
    stockByColour,
    toteStockByColour,
} from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { FinishedStock, SizeList } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const TABLE_NAME = 'Units in stock per colour and size';

function renderCard(
    stock: FinishedStock = denimStockByColour,
    list: SizeList = sizeListSToXl,
    name = 'Denim Jacket',
) {
    return render(
        <SizesColoursCard
            product={{ id: 1, name, size_list: list }}
            stock={stock}
            manageHref="/products/1/edit#colours"
        />,
    );
}

/** The text of every cell of a row, header cells included. */
function cellsOf(row: HTMLElement): string[] {
    return Array.from(row.children).map((cell) => cell.textContent ?? '');
}

/** Which data cells of a row are in the grey text colour. */
function greyOf(row: HTMLElement): boolean[] {
    return Array.from(row.querySelectorAll('td')).map(
        (cell) => cell.querySelector('.text-muted-foreground') !== null,
    );
}

describe('<SizesColoursCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the stock table of the worked example: a row per colour, the size columns, Total, and a heavier Total row', () => {
        renderCard();

        const card = screen.getByRole('region', { name: 'Sizes & colours' });
        const table = within(card).getByRole('table', { name: TABLE_NAME });
        const rows = within(table).getAllByRole('row');

        expect(rows.map(cellsOf)).toEqual([
            ['Colour', 'S', 'M', 'L', 'XL', 'Total'],
            ['Black', '1', '0', '2', '1', '4'],
            ['Indigo', '2', '0', '3', '1', '6'],
            ['Light wash', '0', '0', '0', '0', '0'],
            ['Total', '3', '0', '5', '2', '10'],
        ]);
        // A 0 is grey, but still written; the others are not.
        expect(greyOf(rows[1])).toEqual([false, true, false, false, false]);
        expect(greyOf(rows[3])).toEqual([true, true, true, true, true]);
        // The Total row sits under a heavier line.
        expect(rows[4].parentElement?.tagName).toBe('TFOOT');
        expect(rows[4]).toHaveClass('border-t-2');
        expect(
            within(table).getByRole('rowheader', { name: 'Light wash' }),
        ).toBeInTheDocument();

        expect(
            within(card).getByText(
                'Grey zeros are variants with nothing on the shelf.',
            ),
        ).toBeInTheDocument();
    });

    it('says nothing about grey zeros when every cell is 0', () => {
        renderCard(
            stockByColour(sizeListSToXl, [
                ['Black', []],
                ['Indigo', []],
            ]),
        );

        const table = screen.getByRole('table', { name: TABLE_NAME });

        expect(greyOf(within(table).getAllByRole('row')[1])).toEqual([
            true,
            true,
            true,
            true,
            true,
        ]);
        expect(screen.queryByText(/Grey zeros/)).toBeNull();
    });

    it.each([
        {
            name: 'several colours and sizes',
            stock: denimStockByColour,
            list: sizeListSToXl,
            line: '3 colours × 4 sizes = 12 variants. Each variant has its own stock.',
        },
        {
            name: 'one colour',
            stock: stockByColour(sizeListSToXl, [['White', [3, 0, 5, 2]]]),
            list: sizeListSToXl,
            line: '1 colour × 4 sizes = 4 variants. Each variant has its own stock.',
        },
        {
            name: 'a "One size" product',
            stock: toteStockByColour,
            list: sizeListOneSize,
            line: '2 colours × One size = 2 variants. Each variant has its own stock.',
        },
    ])('words the variants line for $name', ({ stock, list, line }) => {
        renderCard(stock, list);

        expect(
            within(
                screen.getByRole('region', { name: 'Sizes & colours' }),
            ).getByText(line),
        ).toBeInTheDocument();
    });

    it('gives a "One size" product the columns Colour and In stock, and a Total row for two colours', () => {
        renderCard(toteStockByColour, sizeListOneSize, 'Leather Tote');

        const table = screen.getByRole('table', { name: TABLE_NAME });
        const rows = within(table).getAllByRole('row');

        expect(rows.map(cellsOf)).toEqual([
            ['Colour', 'In stock'],
            ['Camel', '4'],
            ['Black', '0'],
            ['Total', '4'],
        ]);
        expect(greyOf(rows[2])).toEqual([true]);
    });

    it("keeps a shoe's 11 columns inside a box that scrolls, and drops the Total row for one colour", () => {
        renderCard(
            stockByColour(sizeListEu36To46, [['Black', [0, 2]]]),
            sizeListEu36To46,
            'Canvas Low Sneaker',
        );

        const table = screen.getByRole('table', { name: TABLE_NAME });
        const rows = within(table).getAllByRole('row');

        expect(cellsOf(rows[0])).toHaveLength(13); // Colour, 11 sizes, Total
        expect(rows).toHaveLength(2); // header + Black
        expect(table.parentElement).toHaveClass('overflow-x-auto');
    });

    it("makes the table's scroll box a tab stop only when it really overflows", () => {
        const region = () =>
            screen.getByRole('region', { name: `${TABLE_NAME}, scrollable` });

        const { unmount } = renderCard();

        expect(region()).not.toHaveAttribute('tabindex');
        unmount();

        // A shoe's 11 columns are wider than the box (jsdom measures nothing, so mock it).
        const scrollWidth = vi
            .spyOn(Element.prototype, 'scrollWidth', 'get')
            .mockReturnValue(900);

        try {
            renderCard();

            expect(region()).toHaveAttribute('tabindex', '0');
        } finally {
            scrollWidth.mockRestore();
        }
    });

    it('has Manage sizes & colours go to Edit product at #colours, and Update stock open the window', async () => {
        const user = userEvent.setup();

        renderCard();

        expect(
            screen.getByRole('link', { name: 'Manage sizes & colours' }),
        ).toHaveAttribute('href', '/products/1/edit#colours');
        // Manage first, then Update stock: the DOM order is the keyboard order and the visual order.
        const manage = screen.getByRole('link', {
            name: 'Manage sizes & colours',
        });
        const update = screen.getByRole('button', { name: 'Update stock' });

        expect(manage.parentElement).not.toHaveClass('flex-col-reverse');
        expect(
            manage.compareDocumentPosition(update) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();

        await user.click(screen.getByRole('button', { name: 'Update stock' }));

        expect(
            screen.getByRole('dialog', { name: /Update stock/ }),
        ).toBeInTheDocument();
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

            renderCard();

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

    it('on a phone shows a card per colour with its size chips, then a Total card (jsdom renders table and cards)', () => {
        renderCard();

        const cards = within(screen.getByRole('list', { name: TABLE_NAME }));
        const items = cards.getAllByRole('listitem');

        expect(items).toHaveLength(4); // Black, Indigo, Light wash, Total

        const indigo = items[1];

        expect(indigo).toHaveTextContent('Indigo · 6 units');
        expect(
            within(indigo).getByText('Indigo, per size: S 2, M 0, L 3, XL 1'),
        ).toHaveClass('sr-only');

        // The chips keep their zeros, greyed.
        const chips = Array.from(
            indigo.querySelector('div[aria-hidden="true"]')?.children ?? [],
        );

        expect(
            chips.map((chip) => [
                chip.children[0].textContent,
                chip.children[1].textContent,
            ]),
        ).toEqual([
            ['S', '2'],
            ['M', '0'],
            ['L', '3'],
            ['XL', '1'],
        ]);
        expect(
            chips.filter((chip) => chip.hasAttribute('data-zero')),
        ).toHaveLength(1);

        const total = items[3];

        expect(total).toHaveTextContent('Total');
        expect(total).toHaveTextContent('10 units');
        expect(
            within(total).getByText('Total per size: S 3, M 0, L 5, XL 2'),
        ).toBeInTheDocument();
    });
});
