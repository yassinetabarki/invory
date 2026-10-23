import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import ProductsShow from '@/pages/products/show';
import {
    canvasLowSneaker,
    choreJacket,
    denimJacket,
    denimJacketBelowCost,
    denimRecentSales,
    denimSalesLast30Days,
    jacketWithoutComposition,
    sizeListSToXl,
} from '@/test/fixtures/products';
import {
    inertiaMock,
    resetInertiaMock,
    setPageProps,
    type TestPageProps,
} from '@/test/inertia';
import type { FinishedStock, Product, ProductShowProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

/** The Denim Jacket's stock: S 3 · M 0 · L 5 · XL 2. */
const denimStock: FinishedStock = {
    units: 10,
    sizes: sizeListSToXl.sizes.map((size, index) => ({
        size,
        units: [3, 0, 5, 2][index] ?? 0,
    })),
};

function props(
    product: Product = denimJacket,
    overrides: Partial<ProductShowProps> = {},
): ProductShowProps {
    return {
        product,
        sales_last_30_days: denimSalesLast30Days,
        recent_sales: denimRecentSales,
        finished_stock: denimStock,
        ...overrides,
    };
}

/**
 * Renders the page with the page props mirroring what it renders, so the
 * mocked `<Deferred>` sees the deferred props as loaded (`settings` are extra
 * shared props, e.g. `app`).
 */
function renderShow(
    showProps: ProductShowProps,
    { url = '/products/1', ...settings }: { url?: string } & TestPageProps = {},
) {
    setPageProps(
        { ...showProps, ...settings },
        { url, component: 'products/show' },
    );

    return render(<ProductsShow {...showProps} />);
}

/** The value shown beside a term of the cost breakdown (e.g. the amount next to "Margin"). */
function costValue(term: string): HTMLElement {
    const label = within(
        screen.getByRole('region', { name: 'Cost breakdown' }),
    ).getByText(term, {
        selector: 'dt',
    });
    const value = label.nextElementSibling;

    if (!(value instanceof HTMLElement)) {
        throw new Error(`Expected a value next to "${term}".`);
    }

    return value;
}

describe('products/show page', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('deferred props', () => {
        it('paints a skeleton of the whole page until the deferred props land, then the product, its cost, composition and sales', () => {
            // The first response: none of the deferred props is in it.
            const first: ProductShowProps = {};

            setPageProps(first, {
                url: '/products/1',
                component: 'products/show',
            });
            const { rerender } = render(<ProductsShow {...first} />);

            expect(screen.getByText('Loading Unit cost…')).toBeInTheDocument();
            expect(
                screen.getByText('Loading recent sales…'),
            ).toBeInTheDocument();
            expect(
                screen.queryByRole('region', { name: 'Production capacity' }),
            ).not.toBeInTheDocument();
            expect(
                screen.queryByRole('table', { name: 'Composition' }),
            ).not.toBeInTheDocument();

            // The deferred request lands: Inertia re-renders the page with the new props.
            const landed = props();

            setPageProps({ ...landed });
            rerender(<ProductsShow {...landed} />);

            expect(
                screen.getByRole('region', { name: 'Production capacity' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('table', { name: 'Composition' }),
            ).toBeInTheDocument();
            expect(
                within(
                    screen.getByRole('region', { name: 'Sales, last 30 days' }),
                ).getByText('38'),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('table', { name: 'Recent sales' }),
            ).toBeInTheDocument();
            expect(screen.queryByRole('status')).not.toBeInTheDocument();
        });
    });

    describe('header and actions', () => {
        it('shows the name, SKU, category and status', () => {
            renderShow(props());

            expect(
                screen.getByRole('heading', { level: 1, name: 'Denim Jacket' }),
            ).toBeInTheDocument();
            expect(screen.getByText('PRD-JKT-001')).toBeInTheDocument();
            expect(screen.getByText('Apparel')).toBeInTheDocument();
            expect(screen.getByText('Active')).toBeInTheDocument();
        });

        it('does not prefetch the Record sale or Edit pages', () => {
            renderShow(props());

            for (const name of ['Record sale', 'Edit']) {
                const link = screen.getByRole('link', { name });

                expect(link).not.toHaveAttribute('data-prefetch');
            }
        });

        it('links Record sale to the sales page with this product pre-selected', () => {
            renderShow(props());

            const link = screen.getByRole('link', { name: 'Record sale' });

            expect(link).toHaveAttribute(
                'href',
                '/sales?record=1&record_product_id=1',
            );
        });

        it('links Edit to the edit page', () => {
            renderShow(props());

            expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
                'href',
                '/products/1/edit',
            );
        });

        it('disables Record sale, with the reason, for a product that is not active', () => {
            renderShow(props(choreJacket), { url: '/products/6' });

            const button = screen.getByRole('button', { name: 'Record sale' });

            expect(button).toBeDisabled();
            expect(button).toHaveAccessibleDescription(
                'Only active products can be sold.',
            );
            expect(
                screen.queryByRole('link', { name: 'Record sale' }),
            ).not.toBeInTheDocument();
            // A draft can still be edited and deleted.
            expect(
                screen.getByRole('link', { name: 'Edit' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Delete' }),
            ).toBeEnabled();
        });

        it('defines the Products > product breadcrumbs', () => {
            expect(ProductsShow.layout(props()).breadcrumbs).toEqual([
                expect.objectContaining({
                    title: 'Products',
                    href: { url: '/products', method: 'get' },
                }),
                expect.objectContaining({
                    title: 'Denim Jacket',
                    href: { url: '/products/1', method: 'get' },
                }),
            ]);
        });

        it('has only the Products breadcrumb until the product lands', () => {
            expect(ProductsShow.layout({}).breadcrumbs).toEqual([
                expect.objectContaining({ title: 'Products' }),
            ]);
        });
    });

    describe('photo and description', () => {
        it('shows the portrait photo with the description as its caption', () => {
            renderShow(props());

            expect(
                screen.getByRole('img', { name: 'Photo of Denim Jacket' }),
            ).toHaveAttribute('src', '/mock/photos/prd-jkt-001.svg');
            expect(
                screen.getByText(
                    'Classic trucker jacket in 12oz indigo denim with metal shank buttons.',
                ),
            ).toBeInTheDocument();
        });

        it('shows a placeholder when there is no photo', () => {
            renderShow(props(choreJacket));

            expect(
                screen.queryByRole('img', { name: /Photo of/ }),
            ).not.toBeInTheDocument();
        });
    });

    describe('the worked example', () => {
        it('shows the four hero cards: can produce, in stock, unit cost, sold', () => {
            renderShow(props());

            const production = within(
                screen.getByRole('region', { name: 'Production capacity' }),
            );

            expect(production.getByText('Can produce now')).toBeInTheDocument();
            expect(production.getByText('112')).toBeInTheDocument();
            expect(production.getByText('units')).toBeInTheDocument();
            const inStock = within(
                screen.getByRole('region', { name: 'Finished stock' }),
            );

            expect(inStock.getByText('In stock')).toBeInTheDocument();
            expect(inStock.getByText('units').parentElement).toHaveTextContent(
                '10 units',
            );
            expect(
                inStock.getByText('In stock per size: S 3, M 0, L 5, XL 2'),
            ).toBeInTheDocument();
            expect(
                within(
                    screen.getByRole('region', { name: 'Unit cost' }),
                ).getByText('€35.75'),
            ).toBeInTheDocument();

            const sold = within(
                screen.getByRole('region', { name: 'Sales, last 30 days' }),
            );

            expect(sold.getByText('38')).toBeInTheDocument();
            expect(sold.getByText(/in sales$/)).toHaveTextContent(
                '€3,382.00 in sales',
            );
        });

        it('shows the unit cost as TND 35.754 with the installation currency', () => {
            renderShow(props(), {
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });

            expect(
                within(
                    screen.getByRole('region', { name: 'Unit cost' }),
                ).getByText('TND 35.754'),
            ).toBeInTheDocument();
            expect(
                within(
                    screen.getByRole('region', { name: 'Cost breakdown' }),
                ).getByText('TND 35.754'),
            ).toBeInTheDocument();
        });

        it('explains the bottleneck, including the reorder level', () => {
            renderShow(props());

            expect(screen.getByRole('note')).toHaveTextContent(
                "Bottleneck: Metal shank button 17mm. 900 pc in stock covers 112 units at 8 pc each. It's also below its reorder level of 1,000 pc.",
            );
        });

        it('shows the cost breakdown with the selling price and margin', () => {
            renderShow(props());

            const card = within(
                screen.getByRole('region', { name: 'Cost breakdown' }),
            );

            expect(
                card.getByText('Materials (4 lines, waste included)'),
            ).toBeInTheDocument();
            expect(card.getByText('€20.25')).toBeInTheDocument();
            expect(card.getByText('€12.00')).toBeInTheDocument();
            expect(card.getByText('€3.50')).toBeInTheDocument();
            expect(card.getByText('€89.00')).toBeInTheDocument();
            expect(costValue('Margin')).toHaveTextContent('€53.25 · 59.83%');
        });

        it('shows the composition with the bottleneck row badged', () => {
            renderShow(props());

            const table = screen.getByRole('table', { name: 'Composition' });

            expect(within(table).getAllByRole('row')).toHaveLength(6);
            expect(
                within(
                    within(table).getByRole('row', {
                        name: /Metal shank button/,
                    }),
                ).getByText('Bottleneck'),
            ).toBeInTheDocument();
            expect(
                within(table).getByRole('link', {
                    name: 'Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/5');
            expect(
                screen.getByRole('link', { name: 'Edit composition' }),
            ).toHaveAttribute('href', '/products/1/edit');
        });

        it('lists the recent sales and links to all of them', () => {
            renderShow(props());

            const table = screen.getByRole('table', { name: 'Recent sales' });

            expect(within(table).getAllByRole('row')).toHaveLength(6);
            expect(within(table).getByText('5 Oct 2026')).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'View all' }),
            ).toHaveAttribute('href', '/sales?product_id=1');
        });
    });

    describe('the stock figure (S-56)', () => {
        it('lays the four figures out one under the other on a phone and two by two from tablet width', () => {
            renderShow(props());

            const grid = screen.getByRole('region', {
                name: 'Production capacity',
            }).parentElement;

            expect(
                Array.from(grid?.children ?? []).map((figure) =>
                    figure.getAttribute('aria-label'),
                ),
            ).toEqual([
                'Production capacity',
                'Finished stock',
                'Unit cost',
                'Sales, last 30 days',
            ]);
            expect(grid).toHaveClass('grid', 'sm:grid-cols-2');
            expect(grid?.className).not.toMatch(/(?:md|lg|xl|2xl):grid-cols/);
            // The sizes wrap instead of scrolling a phone sideways (jsdom has no layout).
            expect(
                screen
                    .getByRole('region', { name: 'Finished stock' })
                    .querySelector('[aria-hidden="true"]'),
            ).toHaveClass('flex-wrap');
        });

        it('shows a placeholder while the stock loads, apart from the rest of the page', () => {
            renderShow(props(denimJacket, { finished_stock: undefined }));

            expect(screen.getByText('Loading In stock…')).toBeInTheDocument();
            expect(
                screen.queryByRole('region', { name: 'Finished stock' }),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('region', { name: 'Production capacity' }),
            ).toBeInTheDocument();
        });

        it("says the stock couldn't load, and Try again reloads only that prop", async () => {
            const user = userEvent.setup();
            const rescued = props(denimJacket, { finished_stock: undefined });

            setPageProps(
                { ...rescued },
                { url: '/products/1', rescuedProps: ['finished_stock'] },
            );
            render(<ProductsShow {...rescued} />);

            expect(screen.getByRole('alert')).toHaveTextContent(
                "Couldn't load the stock.",
            );
            // The rest of the page is there.
            expect(
                screen.getByRole('region', { name: 'Production capacity' }),
            ).toBeInTheDocument();

            await user.click(screen.getByRole('button', { name: 'Try again' }));

            expect(inertiaMock.router.reload).toHaveBeenCalledExactlyOnceWith({
                only: ['finished_stock'],
            });
        });
    });

    describe('other states', () => {
        it("flags a product that can't be produced", () => {
            renderShow(props(canvasLowSneaker));

            expect(
                within(
                    screen.getByRole('region', { name: 'Production capacity' }),
                ).getByText("Can't produce"),
            ).toBeInTheDocument();
            expect(screen.getByRole('alert')).toHaveTextContent(
                "Canvas Low Sneaker can't be produced: Rubber cupsole 42 is out of stock.",
            );
        });

        it('explains a missing composition and links to the edit page', () => {
            renderShow(props(jacketWithoutComposition));

            expect(
                within(
                    screen.getByRole('region', { name: 'Production capacity' }),
                ).getByText('No composition'),
            ).toBeInTheDocument();
            expect(screen.getByRole('note')).toHaveTextContent(
                'No composition defined.',
            );
            expect(
                screen.getByRole('heading', { name: 'No materials yet' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Add materials' }),
            ).toHaveAttribute('href', '/products/6/edit');
        });

        it('invites the user to set a price when there is none', () => {
            renderShow(props(choreJacket));

            const card = within(
                screen.getByRole('region', { name: 'Cost breakdown' }),
            );

            expect(
                card.getByText(/No selling price set\./),
            ).toBeInTheDocument();
            expect(
                card.getByRole('link', { name: 'Set a price' }),
            ).toHaveAttribute('href', '/products/6/edit');
            expect(card.queryByText('Margin')).not.toBeInTheDocument();
        });

        it('shows a negative margin with its sign', () => {
            renderShow(props(denimJacketBelowCost));

            expect(costValue('Margin')).toHaveTextContent('-€5.75 · -19.18%');
        });

        it('says there are no sales yet, with a link to record one', () => {
            renderShow(
                props(denimJacket, {
                    recent_sales: [],
                    sales_last_30_days: { units_sold: 0, revenue: '0.0000' },
                }),
            );

            expect(
                screen.getByText('No sales recorded for this product yet.'),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Record a sale' }),
            ).toHaveAttribute('href', '/sales?record=1&record_product_id=1');
            expect(
                screen.queryByRole('table', { name: 'Recent sales' }),
            ).toBeNull();
        });
    });

    describe('deleting', () => {
        it('opens a confirmation naming the product and its SKU, without deleting yet', async () => {
            const user = userEvent.setup();

            renderShow(props());
            await user.click(screen.getByRole('button', { name: 'Delete' }));

            const dialog = screen.getByRole('alertdialog', {
                name: 'Delete product?',
            });

            expect(within(dialog).getByText('Denim Jacket')).toBeVisible();
            expect(within(dialog).getByText(/SKU PRD-JKT-001/)).toBeVisible();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('closes with Cancel or Escape and deletes nothing', async () => {
            const user = userEvent.setup();

            renderShow(props());

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            // Focus returns to the button that opened it.
            expect(
                screen.getByRole('button', { name: 'Delete' }),
            ).toHaveFocus();

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.keyboard('{Escape}');

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(inertiaMock.router.delete).not.toHaveBeenCalled();
        });

        it('deletes through the destroy URL, shows progress, then closes', async () => {
            const user = userEvent.setup();

            renderShow(props());

            await user.click(screen.getByRole('button', { name: 'Delete' }));
            await user.click(
                screen.getByRole('button', { name: 'Delete product' }),
            );

            expect(inertiaMock.router.delete).toHaveBeenCalledExactlyOnceWith(
                '/products/1',
                expect.objectContaining({
                    onStart: expect.any(Function),
                    onFinish: expect.any(Function),
                }),
            );

            const [, options] = inertiaMock.router.delete.mock.calls[0] as [
                string,
                { onStart: () => void; onFinish: () => void },
            ];

            act(() => options.onStart());

            expect(
                screen.getByRole('button', { name: /delete product/i }),
            ).toBeDisabled();
            expect(
                screen.getByRole('button', { name: 'Cancel' }),
            ).toBeDisabled();

            act(() => options.onFinish());

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
        });
    });
});
