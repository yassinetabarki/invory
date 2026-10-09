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
import ProductsIndex from '@/pages/products/index';
import {
    allProductItems,
    denimJacketItem,
    noProductFilters,
    paginateProducts,
    productStatuses,
    sneakerItem,
} from '@/test/fixtures/products';
import {
    archived,
    referenceData,
    referenceProductCategories,
} from '@/test/fixtures/reference';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { ProductFilters, ProductsIndexProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const VISIT_OPTIONS = {
    only: ['products', 'filters'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

function props(
    overrides: Partial<ProductsIndexProps> = {},
): ProductsIndexProps {
    return {
        products: paginateProducts(allProductItems),
        filters: noProductFilters,
        statuses: productStatuses,
        ...overrides,
    };
}

function filtersWith(overrides: Partial<ProductFilters>): ProductFilters {
    return { ...noProductFilters, ...overrides };
}

describe('products/index page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/products', component: 'products/index' });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('with products', () => {
        it('has a title, a description and an Add product link', () => {
            render(<ProductsIndex {...props()} />);

            expect(
                screen.getByRole('heading', { level: 1, name: 'Products' }),
            ).toBeInTheDocument();
            expect(
                screen.getByText(
                    'Final items you make. Cost and capacity update from current material stock.',
                ),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Add product' }),
            ).toHaveAttribute('href', '/products/create');
        });

        it('lists a row per product with cost, price, capacity and bottleneck', () => {
            render(<ProductsIndex {...props()} />);

            const table = screen.getByRole('table', { name: 'Products' });

            expect(within(table).getAllByRole('row')).toHaveLength(8);

            const jacket = within(
                within(table).getByRole('row', { name: /Denim Jacket/ }),
            );

            expect(jacket.getByText('€35.75')).toBeInTheDocument();
            expect(jacket.getByText('€89.00')).toBeInTheDocument();
            expect(jacket.getByText('59.83%')).toBeInTheDocument();
            expect(jacket.getByText('112 units')).toBeInTheDocument();
            expect(jacket.getByText('38')).toBeInTheDocument();
            expect(
                within(
                    within(table).getByRole('row', {
                        name: /Canvas Low Sneaker/,
                    }),
                ).getByText("Can't produce"),
            ).toBeInTheDocument();
        });

        it('links each product, its edit page and its bottleneck material through Wayfinder', () => {
            render(<ProductsIndex {...props()} />);

            const table = screen.getByRole('table');

            expect(
                within(table).getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(
                within(table).getByRole('link', { name: 'View Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
            expect(
                within(table).getByRole('link', { name: 'Edit Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1/edit');

            const row = within(table).getByRole('row', {
                name: /Denim Jacket/,
            });

            expect(
                within(row).getByRole('link', {
                    name: 'Metal shank button 17mm',
                }),
            ).toHaveAttribute('href', '/materials/5');
        });

        it('prefetches Add product and the row links, each under the tag of its page', () => {
            render(<ProductsIndex {...props()} />);

            const table = screen.getByRole('table');
            const row = within(table).getByRole('row', {
                name: /Denim Jacket/,
            });

            for (const link of [
                screen.getByRole('link', { name: 'Add product' }),
                within(table).getByRole('link', { name: 'Denim Jacket' }),
                within(table).getByRole('link', { name: 'Edit Denim Jacket' }),
            ]) {
                expect(link).toHaveAttribute('data-prefetch', 'true');
                expect(link).toHaveAttribute('data-cache-tags', 'products');
            }

            expect(
                within(row).getByRole('link', {
                    name: 'Metal shank button 17mm',
                }),
            ).toHaveAttribute('data-cache-tags', 'materials');
        });

        it('shows the stacked cards too (swapped with the table by CSS below md)', () => {
            render(<ProductsIndex {...props()} />);

            expect(
                within(
                    screen.getByRole('list', { name: 'Products' }),
                ).getAllByRole('listitem'),
            ).toHaveLength(7);
        });

        it('shows the pagination summary only when there are several pages', () => {
            const { rerender } = render(<ProductsIndex {...props()} />);

            expect(
                screen.queryByRole('navigation', { name: 'Pagination' }),
            ).not.toBeInTheDocument();

            rerender(
                <ProductsIndex
                    {...props({
                        products: paginateProducts(allProductItems, {
                            last_page: 2,
                            total: 30,
                            from: 1,
                            to: 25,
                            links: [
                                {
                                    url: null,
                                    label: '« Previous',
                                    active: false,
                                },
                                { url: null, label: '1', active: true },
                                {
                                    url: '/products?page=2',
                                    label: '2',
                                    active: false,
                                },
                                {
                                    url: '/products?page=2',
                                    label: 'Next »',
                                    active: false,
                                },
                            ],
                        }),
                    })}
                />,
            );

            expect(
                screen.getByRole('navigation', { name: 'Pagination' }),
            ).toBeInTheDocument();
            expect(screen.getByText('Showing 1–25 of 30')).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Next page' }),
            ).toHaveAttribute('href', '/products?page=2');
        });

        it('defines the Products breadcrumb', () => {
            expect(ProductsIndex.layout.breadcrumbs).toEqual([
                expect.objectContaining({
                    title: 'Products',
                    href: { url: '/products', method: 'get' },
                }),
            ]);
        });
    });

    describe('empty states', () => {
        it('invites the user to add the first product when there are none', () => {
            render(
                <ProductsIndex
                    {...props({ products: paginateProducts([]) })}
                />,
            );

            expect(
                screen.getByRole('heading', { name: 'No products yet' }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'Add your first product' }),
            ).toHaveAttribute('href', '/products/create');
            // Nothing to filter yet.
            expect(
                screen.queryByRole('search', { name: 'Filter products' }),
            ).not.toBeInTheDocument();
            expect(screen.queryByRole('table')).not.toBeInTheDocument();
        });

        it('explains when filters match nothing and offers to clear them', async () => {
            const user = userEvent.setup();

            render(
                <ProductsIndex
                    {...props({
                        products: paginateProducts([]),
                        filters: filtersWith({ search: 'zzz' }),
                    })}
                />,
            );

            const heading = screen.getByRole('heading', {
                name: 'No products match your filters',
            });

            expect(
                screen.queryByRole('heading', { name: 'No products yet' }),
            ).not.toBeInTheDocument();
            expect(screen.getByRole('searchbox')).toHaveValue('zzz');
            expect(screen.queryByRole('table')).not.toBeInTheDocument();

            // The filters bar has its own "Clear filters"; use the empty state's.
            await user.click(
                within(heading.parentElement as HTMLElement).getByRole(
                    'button',
                    {
                        name: 'Clear filters',
                    },
                ),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/products',
                {},
                VISIT_OPTIONS,
            );
            expect(screen.getByRole('searchbox')).toHaveValue('');
        });

        it('keeps the filters bar when "Can\'t produce" finds nothing', () => {
            render(
                <ProductsIndex
                    {...props({
                        products: paginateProducts([]),
                        filters: filtersWith({ cant_produce: true }),
                    })}
                />,
            );

            expect(
                screen.getByRole('heading', {
                    name: 'No products match your filters',
                }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            ).toBeChecked();
        });
    });

    describe('filters (synced to the URL)', () => {
        it('seeds the controls from the server filters', () => {
            render(
                <ProductsIndex
                    {...props({
                        products: paginateProducts([sneakerItem]),
                        filters: filtersWith({
                            search: 'sneaker',
                            category: 'footwear',
                            status: 'active',
                            cant_produce: true,
                        }),
                    })}
                />,
            );

            expect(screen.getByRole('searchbox')).toHaveValue('sneaker');
            expect(
                screen.getByRole('combobox', { name: 'Category' }),
            ).toHaveTextContent('Footwear');
            expect(
                screen.getByRole('combobox', { name: 'Status' }),
            ).toHaveTextContent('Active');
            expect(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            ).toBeChecked();
        });

        it('sends cant_produce=1 for the toggle, with no page and no empty values', async () => {
            const user = userEvent.setup();

            render(<ProductsIndex {...props()} />);

            await user.click(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/products',
                { cant_produce: 1 },
                VISIT_OPTIONS,
            );
        });

        it('reloads only the rows and the filters on a filter change', async () => {
            const user = userEvent.setup();

            render(<ProductsIndex {...props()} />);

            await user.click(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            );

            // The category and status options are not requested again.
            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/products',
                expect.anything(),
                expect.objectContaining({ only: ['products', 'filters'] }),
            );
        });

        it('removes cant_produce when the toggle is switched off', async () => {
            const user = userEvent.setup();

            render(
                <ProductsIndex
                    {...props({
                        filters: filtersWith({ cant_produce: true }),
                    })}
                />,
            );

            await user.click(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            );

            expect(inertiaMock.router.get).toHaveBeenCalledWith(
                '/products',
                {},
                VISIT_OPTIONS,
            );
        });

        it('sends the category and accumulates filters picked one after another', async () => {
            const user = userEvent.setup();

            render(<ProductsIndex {...props()} />);

            await user.click(
                screen.getByRole('combobox', { name: 'Category' }),
            );
            await user.click(screen.getByRole('option', { name: 'Apparel' }));

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/products',
                { category: 'apparel' },
                VISIT_OPTIONS,
            );

            // The server has not answered yet (props unchanged): the page's own
            // state must not lose the category.
            await user.click(screen.getByRole('combobox', { name: 'Status' }));
            await user.click(screen.getByRole('option', { name: 'Draft' }));

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/products',
                { category: 'apparel', status: 'draft' },
                VISIT_OPTIONS,
            );

            await user.click(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            );

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/products',
                { category: 'apparel', status: 'draft', cant_produce: 1 },
                VISIT_OPTIONS,
            );
        });

        it('drops a filter when "All categories" is picked', async () => {
            const user = userEvent.setup();

            render(
                <ProductsIndex
                    {...props({
                        filters: filtersWith({ category: 'apparel' }),
                    })}
                />,
            );

            await user.click(
                screen.getByRole('combobox', { name: 'Category' }),
            );
            await user.click(
                screen.getByRole('option', { name: 'All categories' }),
            );

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/products',
                {},
                VISIT_OPTIONS,
            );
        });

        it('debounces the search by 300 ms and sends the trimmed text', () => {
            vi.useFakeTimers();

            render(<ProductsIndex {...props()} />);

            const box = screen.getByRole('searchbox');

            fireEvent.change(box, { target: { value: 'den' } });
            act(() => {
                vi.advanceTimersByTime(200);
            });
            fireEvent.change(box, { target: { value: ' denim ' } });
            act(() => {
                vi.advanceTimersByTime(299);
            });

            expect(inertiaMock.router.get).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(1);
            });

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/products',
                { search: 'denim' },
                VISIT_OPTIONS,
            );
        });

        it('never sends the page, even when the user is on page 3', async () => {
            const user = userEvent.setup();

            render(
                <ProductsIndex
                    {...props({
                        products: paginateProducts([denimJacketItem], {
                            current_page: 3,
                            last_page: 5,
                        }),
                    })}
                />,
            );

            await user.click(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            );

            const [, query] = inertiaMock.router.get.mock.calls[0] as [
                string,
                Record<string, unknown>,
            ];

            expect(query).not.toHaveProperty('page');
        });

        it('clears every filter from the bar', async () => {
            const user = userEvent.setup();

            render(
                <ProductsIndex
                    {...props({
                        filters: filtersWith({
                            search: 'denim',
                            category: 'apparel',
                            cant_produce: true,
                        }),
                    })}
                />,
            );

            await user.click(
                screen.getByRole('button', { name: 'Clear filters' }),
            );

            expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
                '/products',
                {},
                VISIT_OPTIONS,
            );
            expect(screen.getByRole('searchbox')).toHaveValue('');
            expect(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            ).not.toBeChecked();
        });

        it('follows filters the server changed (back button) but not the echo of its own request', async () => {
            const user = userEvent.setup();
            const { rerender } = render(<ProductsIndex {...props()} />);

            await user.click(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            );

            // The server echoes the request we made: nothing changes.
            rerender(
                <ProductsIndex
                    {...props({ filters: filtersWith({ cant_produce: true }) })}
                />,
            );

            expect(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            ).toBeChecked();

            // Then history navigation brings other filters.
            rerender(
                <ProductsIndex
                    {...props({
                        filters: filtersWith({ status: 'draft' }),
                    })}
                />,
            );

            expect(
                screen.getByRole('checkbox', { name: "Can't produce" }),
            ).not.toBeChecked();
            expect(
                screen.getByRole('combobox', { name: 'Status' }),
            ).toHaveTextContent('Draft');
        });
    });
});

describe('products/index page category filter', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/products', component: 'products/index' });
    });

    function filterOptions() {
        return within(screen.getByRole('listbox'))
            .getAllByRole('option')
            .map((option) => option.textContent);
    }

    it('offers the categories from the shared reference prop, archived ones hidden', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                product_categories: [
                    referenceProductCategories[0],
                    archived(referenceProductCategories[1]),
                    referenceProductCategories[2],
                ],
            }),
        });
        render(<ProductsIndex {...props()} />);

        await user.click(screen.getByRole('combobox', { name: 'Category' }));

        expect(filterOptions()).toEqual(['All categories', 'Apparel', 'Bag']);
    });

    it('keeps an archived category that is the active filter', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                product_categories: [
                    referenceProductCategories[0],
                    archived(referenceProductCategories[1]),
                ],
            }),
        });
        render(
            <ProductsIndex
                {...props({ filters: filtersWith({ category: 'footwear' }) })}
            />,
        );

        await user.click(screen.getByRole('combobox', { name: 'Category' }));

        expect(filterOptions()).toEqual([
            'All categories',
            'Apparel',
            'Footwear (archived)',
        ]);
    });
});
