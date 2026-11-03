import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { ProductSearch } from '@/components/orders/product-search';
import { ORDER_SEARCH_DEBOUNCE_MS } from '@/components/orders/use-product-search';
import {
    jacketStock,
    jeansStock,
    stockProduct,
    toteStock,
} from '@/test/fixtures';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { OrderStockProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const device = vi.hoisted(() => ({ mobile: false }));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

function renderSearch(
    props: Partial<Parameters<typeof ProductSearch>[0]> = {},
) {
    const onAdd = vi.fn();
    const onShowLine = vi.fn();

    render(
        <ProductSearch
            onOrder={new Set()}
            full={false}
            onAdd={onAdd}
            onShowLine={onShowLine}
            {...props}
        />,
    );

    return { onAdd, onShowLine };
}

function box() {
    return screen.getByRole('searchbox', { name: 'Search products' });
}

/** Types into the box and lets the debounce pass. */
async function search(text: string) {
    fireEvent.change(box(), { target: { value: text } });
    await act(async () => {
        await vi.advanceTimersByTimeAsync(ORDER_SEARCH_DEBOUNCE_MS);
    });
}

async function answer(data: unknown) {
    const request = inertiaMock.http.requests.at(-1);

    await act(async () => request?.resolve(data));
}

/** Each row of a stock table: the colour, then each cell as shown. */
function stockRows(name: string) {
    const table = screen.getByRole('table', {
        name: `Stock of ${name} by colour and size`,
    });

    return within(table)
        .getAllByRole('row')
        .slice(1)
        .map((row) => [
            within(row).getByRole('rowheader').textContent,
            ...within(row)
                .getAllByRole('cell')
                .map((cell) => cell.textContent),
        ]);
}

describe('<ProductSearch> (INV-13, AC-1)', () => {
    beforeEach(() => {
        resetInertiaMock();
        device.mobile = false;
        vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('shows the labelled box and its hint, and asks nothing before a search', () => {
        renderSearch();

        expect(box()).toHaveAttribute(
            'placeholder',
            'Name, SKU, colour or barcode',
        );
        expect(box()).toHaveAccessibleDescription(
            "In stock is what is on the shelf now. Open orders don't set it aside, and a colour that is out of stock can still be ordered.",
        );
        expect(
            screen.queryByRole('button', { name: 'Clear search' }),
        ).not.toBeInTheDocument();
        expect(inertiaMock.http.requests).toHaveLength(0);
    });

    it('finds "den": two products with their stock per colour and size, "Out" in amber words', async () => {
        renderSearch();

        fireEvent.change(box(), { target: { value: 'den' } });
        expect(screen.getByText('Searching…')).toBeVisible();

        await search('den');
        expect(inertiaMock.http.requests.at(-1)).toMatchObject({
            path: '/orders/products/search',
            params: { q: 'den' },
        });
        await answer({ total: 2, products: [jacketStock, jeansStock] });

        expect(screen.getByText('2 products found')).toBeVisible();
        expect(
            screen.getByRole('article', { name: 'Denim Jacket' }),
        ).toHaveTextContent('PRD-JKT-001');
        expect(screen.getAllByText('In stock 10 units')).toHaveLength(2);
        expect(screen.queryByText(/Can produce/)).not.toBeInTheDocument();
        expect(stockRows('Denim Jacket')).toEqual([
            ['Black', '1', 'Out', '2', '1'],
            ['Indigo', '2', 'Out', '3', '1'],
            ['Light wash', 'Out', 'Out', 'Out', 'Out'],
        ]);
        expect(stockRows('Denim Jeans 5-pocket')).toEqual([
            ['Indigo', 'Out', '5', '2', 'Out'],
            ['Black', '1', '2', 'Out', 'Out'],
        ]);
        expect(
            screen.getByRole('button', {
                name: 'Add Denim Jacket to this order',
            }),
        ).toBeEnabled();
        expect(
            screen.getByRole('region', {
                name: 'Stock of Denim Jacket by colour and size',
            }),
        ).toBeInTheDocument();
    });

    it('adds a product, or shows "On this order" for one already on it', async () => {
        const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });
        const { onAdd, onShowLine } = renderSearch({ onOrder: new Set([5]) });

        await search('den');
        await answer({ total: 2, products: [jacketStock, jeansStock] });

        await user.click(
            screen.getByRole('button', {
                name: 'Add Denim Jacket to this order',
            }),
        );
        expect(onAdd).toHaveBeenCalledWith(jacketStock);

        const jeans = within(
            screen.getByRole('article', { name: 'Denim Jeans 5-pocket' }),
        );

        expect(
            jeans.queryByRole('button', { name: /Add/ }),
        ).not.toBeInTheDocument();
        await user.click(jeans.getByRole('button', { name: 'On this order' }));
        expect(onShowLine).toHaveBeenCalledWith(5);
    });

    it("says why Add to order can't be pressed with 50 products", async () => {
        renderSearch({ full: true });

        await search('den');
        await answer({ total: 1, products: [jacketStock] });

        const add = screen.getByRole('button', {
            name: 'Add Denim Jacket to this order',
        });

        expect(add).toBeDisabled();
        expect(add).toHaveAccessibleDescription(
            'An order can hold up to 50 products.',
        );
    });

    it('shows 6 of 14 with a line to keep typing', async () => {
        renderSearch();
        const six = Array.from({ length: 6 }, (_, index) =>
            stockProduct(
                10 + index,
                `Denim Shirt ${index + 1}`,
                `SH-${index}`,
                [],
            ),
        );

        await search('den');
        await answer({ total: 14, products: six });

        expect(screen.getByText('14 products found')).toBeVisible();
        expect(screen.getAllByRole('article')).toHaveLength(6);
        expect(
            screen.getByText(
                'Showing 6 of 14. Keep typing to narrow the list.',
            ),
        ).toBeVisible();
    });

    it('says when nothing matches, and offers Try again when the search fails', async () => {
        renderSearch();

        await search('denm xyz');
        await answer({ total: 0, products: [] });

        expect(
            screen.getByText('No product matches "denm xyz".'),
        ).toBeVisible();
        expect(
            screen.getByText(
                'Check the spelling, or search by SKU, colour or barcode.',
            ),
        ).toBeVisible();

        await search('den');
        await act(async () => inertiaMock.http.requests.at(-1)?.reject());

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't search the products.",
        );
        fireEvent.click(screen.getByRole('button', { name: 'Try again' }));
        await answer({ total: 1, products: [jacketStock] });
        expect(screen.getByText('1 product found')).toBeVisible();
    });

    it('clears the search and goes back to the box', async () => {
        renderSearch();

        await search('den');
        await answer({ total: 1, products: [jacketStock] });
        fireEvent.click(screen.getByRole('button', { name: 'Clear search' }));

        expect(box()).toHaveValue('');
        expect(box()).toHaveFocus();
        expect(screen.queryByRole('article')).not.toBeInTheDocument();
    });

    it('gives a "One size" product the columns Colour · Stock', async () => {
        renderSearch();

        await search('tote');
        await answer({ total: 1, products: [toteStock] });

        const table = screen.getByRole('table', {
            name: 'Stock of Leather Tote by colour and size',
        });

        expect(
            within(table)
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Colour', 'Stock']);
        expect(stockRows('Leather Tote')).toEqual([['Camel', '4']]);
    });

    it('shows a chip per size on a phone, and "Out of stock in every size" for an empty colour', async () => {
        device.mobile = true;
        renderSearch();

        await search('den');
        await answer({
            total: 1,
            products: [jacketStock] as OrderStockProduct[],
        });

        const chips = screen.getByRole('list', {
            name: 'Stock of Denim Jacket by colour and size',
        });
        const [black, indigo, light] = within(chips).getAllByRole('listitem');

        expect(black).toHaveTextContent('BlackS1MOutL2XL1');
        expect(indigo).toHaveTextContent('IndigoS2MOutL3XL1');
        expect(light).toHaveTextContent('Light washOut of stock in every size');
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: 'Add Denim Jacket to this order',
            }),
        ).toHaveClass('max-sm:w-full');
    });
});
