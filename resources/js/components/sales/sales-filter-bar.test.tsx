import { act, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { SalesFilterBar } from '@/components/sales/sales-filter-bar';
import {
    defaultSalesFilters,
    saleProducts,
    salesPeriods,
} from '@/test/fixtures/sales';
import type { SalesFilters } from '@/types/domain';

function search(value: string) {
    fireEvent.change(screen.getByRole('searchbox'), { target: { value } });
}

function setup(filters: SalesFilters = defaultSalesFilters) {
    const onChange = vi.fn();
    const user = userEvent.setup();
    const view = render(
        <SalesFilterBar
            filters={filters}
            periods={salesPeriods}
            products={saleProducts}
            onChange={onChange}
        />,
    );

    return { onChange, user, ...view };
}

function rerenderWith(
    view: ReturnType<typeof setup>,
    filters: SalesFilters,
    onChange = view.onChange,
) {
    view.rerender(
        <SalesFilterBar
            filters={filters}
            periods={salesPeriods}
            products={saleProducts}
            onChange={onChange}
        />,
    );
}

describe('<SalesFilterBar>', () => {
    it('labels every control', () => {
        setup();

        expect(
            screen.getByRole('search', { name: 'Filter sales' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('searchbox', { name: 'Search sales' }),
        ).toHaveAttribute('placeholder', 'Search product or note');
        expect(
            screen.getByRole('combobox', { name: 'Period' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('combobox', { name: 'Product' }),
        ).toBeInTheDocument();
    });

    it('reflects the current filters', () => {
        setup({ search: 'web', product_id: 1, period: 'this_year' });

        expect(screen.getByRole('searchbox')).toHaveValue('web');
        expect(
            screen.getByRole('combobox', { name: 'Period' }),
        ).toHaveTextContent('This year');
        expect(
            screen.getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('Denim Jacket');
    });

    it('starts on the default period and every product', () => {
        setup();

        expect(
            screen.getByRole('combobox', { name: 'Period' }),
        ).toHaveTextContent('Last 30 days');
        expect(
            screen.getByRole('combobox', { name: 'Product' }),
        ).toHaveTextContent('All products');
    });

    it('offers the server periods and every product, whatever its status', async () => {
        const { user } = setup();

        await user.click(screen.getByRole('combobox', { name: 'Period' }));

        expect(
            screen.getAllByRole('option').map((option) => option.textContent),
        ).toEqual(['Last 7 days', 'Last 30 days', 'This month', 'This year']);

        await user.keyboard('{Escape}');
        await user.click(screen.getByRole('combobox', { name: 'Product' }));

        expect(
            screen.getAllByRole('option').map((option) => option.textContent),
        ).toEqual([
            'All products',
            'Canvas Chore Jacket',
            'Canvas Low Sneaker',
            'Classic White T-shirt',
            'Denim Jacket',
            'Denim Jeans 5-pocket',
            'Leather Belt',
            'Leather Tote',
        ]);
    });

    describe('period and product', () => {
        it('emits the chosen period at once, keeping the other filters', async () => {
            const { user, onChange } = setup({
                search: 'web',
                product_id: 2,
                period: 'last_30_days',
            });

            await user.click(screen.getByRole('combobox', { name: 'Period' }));
            await user.click(
                screen.getByRole('option', { name: 'Last 7 days' }),
            );

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: 'web',
                product_id: 2,
                period: 'last_7_days',
            });
        });

        it('emits the chosen product id', async () => {
            const { user, onChange } = setup();

            await user.click(screen.getByRole('combobox', { name: 'Product' }));
            await user.click(
                screen.getByRole('option', { name: 'Leather Tote' }),
            );

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: null,
                product_id: 4,
                period: 'last_30_days',
            });
        });

        it('emits a null product for "All products"', async () => {
            const { user, onChange } = setup({
                ...defaultSalesFilters,
                product_id: 4,
            });

            await user.click(screen.getByRole('combobox', { name: 'Product' }));
            await user.click(
                screen.getByRole('option', { name: 'All products' }),
            );

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: null,
                product_id: null,
                period: 'last_30_days',
            });
        });
    });

    // Fake timers are scoped to the tests that need them: Testing Library's
    // async helpers (used by userEvent) hang when Vitest fakes setTimeout, so
    // these tests drive the search box with fireEvent instead.
    describe('search', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('emits once, 300 ms after the last keystroke', () => {
            const { onChange } = setup();

            search('w');
            search('we');
            search('web');

            expect(onChange).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(299);
            });
            expect(onChange).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(1);
            });
            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: 'web',
                product_id: null,
                period: 'last_30_days',
            });
        });

        it('trims the search and keeps the other filters', () => {
            const { onChange } = setup({
                search: null,
                product_id: 5,
                period: 'this_month',
            });

            search('  Lyon  ');

            act(() => {
                vi.advanceTimersByTime(300);
            });

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: 'Lyon',
                product_id: 5,
                period: 'this_month',
            });
        });

        it('emits a null search when the box is emptied', () => {
            const { onChange } = setup({
                ...defaultSalesFilters,
                search: 'web',
            });

            search('');

            act(() => {
                vi.advanceTimersByTime(300);
            });

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: null,
                product_id: null,
                period: 'last_30_days',
            });
        });

        it('does not emit again for a search it has already sent', () => {
            const view = setup();

            search('web');

            act(() => {
                vi.advanceTimersByTime(300);
            });
            expect(view.onChange).toHaveBeenCalledOnce();

            // The server echoes the filter back.
            rerenderWith(view, { ...defaultSalesFilters, search: 'web' });

            act(() => {
                vi.advanceTimersByTime(1000);
            });
            expect(view.onChange).toHaveBeenCalledOnce();
        });

        it('keeps what was typed when a slower echo of an older search arrives', () => {
            const view = setup();

            search('we');

            act(() => {
                vi.advanceTimersByTime(300);
            });

            search('web shop');

            rerenderWith(view, { ...defaultSalesFilters, search: 'we' });

            expect(screen.getByRole('searchbox')).toHaveValue('web shop');
        });

        it('adopts a search that came from the URL (back button)', () => {
            const view = setup();

            rerenderWith(view, { ...defaultSalesFilters, search: 'showroom' });

            expect(screen.getByRole('searchbox')).toHaveValue('showroom');
            expect(view.onChange).not.toHaveBeenCalled();
        });
    });

    describe('Clear filters', () => {
        it('is not offered while nothing is filtered', () => {
            setup();

            expect(
                screen.queryByRole('button', { name: 'Clear filters' }),
            ).not.toBeInTheDocument();
        });

        it.each([
            ['a search', { ...defaultSalesFilters, search: 'web' }],
            ['a product', { ...defaultSalesFilters, product_id: 3 }],
            ['another period', { ...defaultSalesFilters, period: 'this_year' }],
        ] satisfies [string, SalesFilters][])(
            'is offered with %s',
            (_, filters) => {
                setup(filters);

                expect(
                    screen.getByRole('button', { name: 'Clear filters' }),
                ).toBeInTheDocument();
            },
        );

        it('resets search, product and period, and empties the search box', async () => {
            const { user, onChange } = setup({
                search: 'web',
                product_id: 3,
                period: 'this_year',
            });

            await user.click(
                screen.getByRole('button', { name: 'Clear filters' }),
            );

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                search: null,
                product_id: null,
                period: 'last_30_days',
            });
            expect(screen.getByRole('searchbox')).toHaveValue('');
        });
    });
});
