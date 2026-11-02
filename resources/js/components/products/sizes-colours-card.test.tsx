import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { FinishedStockFigure } from '@/components/products/finished-stock-figure';
import {
    SizesColoursCard,
    SizesColoursCardError,
    SizesColoursCardLoading,
} from '@/components/products/sizes-colours-card';
import {
    denimBarcodes,
    denimStockByColour,
    sizeListEu36To46,
    sizeListOneSize,
    sizeListSToXl,
    stockByColour,
    toteBarcodes,
    toteStockByColour,
} from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type {
    FinishedStock,
    ProductBarcodes,
    ProductShowProps,
    SizeList,
} from '@/types/domain';

// The phone layout of the buttons (INV-3): `useIsMobile` reads the viewport once, so the tests set it here.
const viewport = vi.hoisted(() => ({ mobile: false }));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => viewport.mobile }));

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
    barcodes: ProductBarcodes = denimBarcodes,
    lastRestock?: ProductShowProps['last_restock'],
) {
    return render(
        <SizesColoursCard
            product={{ id: 1, name, sku: 'PRD-JKT-001', size_list: list }}
            stock={stock}
            barcodes={barcodes}
            manageHref="/products/1/edit#colours"
            lastRestock={lastRestock}
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

    it('opens on Stock by colour, keeps the open tab in the address without asking the server, and badges the missing barcodes', async () => {
        const user = userEvent.setup();

        // A client-side visit moves the address, like Inertia does.
        inertiaMock.router.replace.mockImplementation(({ url }) =>
            setPageProps({}, { url }),
        );
        setPageProps({}, { url: '/products/1' });

        const first = renderCard();
        const tab = (name: RegExp) => screen.getByRole('tab', { name });

        expect(tab(/^Stock by colour$/)).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(
            screen.getByRole('table', { name: TABLE_NAME }),
        ).toBeInTheDocument();
        // The badge belongs to the Barcodes tab and reads the server's count.
        expect(tab(/^Barcodes/)).toHaveAccessibleName('Barcodes 4 missing');

        await user.click(tab(/^Barcodes/));

        expect(inertiaMock.router.replace).toHaveBeenLastCalledWith({
            url: '/products/1?tab=barcodes',
            preserveState: true,
            preserveScroll: true,
        });
        expect(tab(/^Barcodes/)).toHaveAttribute('aria-selected', 'true');
        expect(
            screen.getByRole('group', { name: 'Colour' }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('table', { name: TABLE_NAME })).toBeNull();

        await user.click(tab(/^Stock by colour$/));

        expect(inertiaMock.router.replace).toHaveBeenLastCalledWith({
            url: '/products/1',
            preserveState: true,
            preserveScroll: true,
        });
        expect(inertiaMock.router.get).not.toHaveBeenCalled();
        expect(inertiaMock.router.visit).not.toHaveBeenCalled();
        first.unmount();

        // A link with ?tab=barcodes opens Barcodes; no badge when nothing is missing.
        setPageProps({}, { url: '/products/1?tab=barcodes' });
        const second = renderCard(
            denimStockByColour,
            sizeListSToXl,
            'Denim Jacket',
            {
                ...denimBarcodes,
                missing: 0,
                missing_names: null,
            },
        );

        expect(tab(/^Barcodes/)).toHaveAttribute('aria-selected', 'true');
        expect(tab(/^Barcodes/)).toHaveAccessibleName('Barcodes');
        second.unmount();

        // "By size and colour" under the In stock figure brings the card back to Stock.
        render(
            <>
                <FinishedStockFigure stock={denimStockByColour} />
                <SizesColoursCard
                    product={{
                        id: 1,
                        name: 'Denim Jacket',
                        sku: 'PRD-JKT-001',
                        size_list: sizeListSToXl,
                    }}
                    stock={denimStockByColour}
                    barcodes={denimBarcodes}
                    manageHref="/products/1/edit#colours"
                />
            </>,
        );
        await user.click(
            screen.getByRole('link', { name: 'By size and colour' }),
        );

        expect(tab(/^Stock by colour$/)).toHaveAttribute(
            'aria-selected',
            'true',
        );
        expect(inertiaMock.router.replace).toHaveBeenLastCalledWith({
            url: '/products/1',
            preserveState: true,
            preserveScroll: true,
        });
    });

    it('shows the frame, heading and tabs with 3 placeholder rows while loading, and one sentence with Try again, which reloads both props, on error', async () => {
        const user = userEvent.setup();
        const loading = render(<SizesColoursCardLoading />);

        expect(
            screen.getByRole('heading', { name: 'Sizes & colours' }),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('tab').map((tab) => tab.textContent),
        ).toEqual(['Stock by colour', 'Barcodes']);
        expect(screen.getByRole('status')).toHaveTextContent(
            'Loading sizes and colours…',
        );
        expect(
            loading.container.querySelectorAll('[data-slot="skeleton-row"]'),
        ).toHaveLength(3);
        loading.unmount();

        render(<SizesColoursCardError reloading={false} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't load the stock and barcodes.",
        );
        await user.click(screen.getByRole('button', { name: 'Try again' }));

        expect(inertiaMock.router.reload).toHaveBeenCalledExactlyOnceWith({
            only: ['finished_stock', 'barcodes'],
        });
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
            line: '3 colours × 4 sizes = 12 variants. Each variant has its own stock and its own barcode.',
        },
        {
            name: 'one colour',
            stock: stockByColour(sizeListSToXl, [['White', [3, 0, 5, 2]]]),
            list: sizeListSToXl,
            line: '1 colour × 4 sizes = 4 variants. Each variant has its own stock and its own barcode.',
        },
        {
            name: 'a "One size" product',
            stock: toteStockByColour,
            list: sizeListOneSize,
            line: '2 colours × One size = 2 variants. Each variant has its own stock and its own barcode.',
        },
    ])('words the variants line for $name', ({ stock, list, line }) => {
        renderCard(stock, list, 'Denim Jacket', toteBarcodes);

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

    it("keeps the table's scroll box a tab stop after opening on Barcodes and switching tabs back and forth", async () => {
        const user = userEvent.setup();

        inertiaMock.router.replace.mockImplementation(({ url }) =>
            setPageProps({}, { url }),
        );
        setPageProps({}, { url: '/products/1?tab=barcodes' });

        // A shoe's 11 columns are wider than the box (jsdom measures nothing, so mock it).
        const scrollWidth = vi
            .spyOn(Element.prototype, 'scrollWidth', 'get')
            .mockReturnValue(900);

        try {
            renderCard();

            const region = () =>
                screen.queryByRole('region', {
                    name: `${TABLE_NAME}, scrollable`,
                });

            // The box is not on the page yet: it lives in the Stock tab.
            expect(region()).toBeNull();

            for (const _ of [1, 2]) {
                await user.click(
                    screen.getByRole('tab', { name: /^Stock by colour$/ }),
                );
                expect(region()).toHaveAttribute('tabindex', '0');

                await user.click(
                    screen.getByRole('tab', { name: /^Barcodes/ }),
                );
                expect(region()).toBeNull();
            }
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

    describe('Restock (INV-3, AC-7)', () => {
        afterEach(() => {
            viewport.mobile = false;
        });

        it('is the main button after Update stock, and opens New restock with this product', () => {
            renderCard();

            const restock = screen.getByRole('link', { name: 'Restock' });
            const update = screen.getByRole('button', { name: 'Update stock' });

            expect(restock).toHaveAttribute(
                'href',
                '/restocks/create?product=1',
            );
            expect(
                update.compareDocumentPosition(restock) &
                    Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();
        });

        it('comes first on a phone', () => {
            viewport.mobile = true;
            renderCard();

            const restock = screen.getByRole('link', { name: 'Restock' });
            const manage = screen.getByRole('link', {
                name: 'Manage sizes & colours',
            });

            expect(
                restock.compareDocumentPosition(manage) &
                    Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();
        });

        it('says when the product was last restocked, with a link to its restocks', () => {
            renderCard(
                denimStockByColour,
                sizeListSToXl,
                'Denim Jacket',
                denimBarcodes,
                { restocked_on: '2026-10-25', units: 24 },
            );

            expect(
                screen.getByText(/^Last restock: 25 Oct 2026, 24 units/),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', {
                    name: 'See the restocks of Denim Jacket',
                }),
            ).toHaveAttribute('href', '/restocks?product=1');
        });

        it('says "Not restocked yet." without a link when it never was', () => {
            renderCard(
                denimStockByColour,
                sizeListSToXl,
                'Denim Jacket',
                denimBarcodes,
                null,
            );

            expect(screen.getByText('Not restocked yet.')).toBeInTheDocument();
            expect(
                screen.queryByRole('link', { name: /See the restocks/ }),
            ).not.toBeInTheDocument();
        });
    });
});
