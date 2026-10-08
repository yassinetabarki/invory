import { act, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { ProductsFilters } from '@/components/products/products-filters';
import {
    noProductFilters,
    productCategories,
    productStatuses,
} from '@/test/fixtures/products';
import type { ProductFilters } from '@/types/domain';

function setup(filters: ProductFilters = noProductFilters) {
    const onChange = vi.fn();
    const view = render(
        <ProductsFilters
            filters={filters}
            categories={productCategories}
            statuses={productStatuses}
            onChange={onChange}
        />,
    );

    return { onChange, ...view };
}

function rerenderWith(
    view: ReturnType<typeof setup>,
    filters: ProductFilters,
    onChange = view.onChange,
) {
    view.rerender(
        <ProductsFilters
            filters={filters}
            categories={productCategories}
            statuses={productStatuses}
            onChange={onChange}
        />,
    );
}

describe('<ProductsFilters>', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('is a labelled search region with a labelled control per filter', () => {
        setup();

        expect(
            screen.getByRole('search', { name: 'Filter products' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('searchbox')).toHaveAccessibleName(
            'Search products',
        );
        expect(
            screen.getByRole('combobox', { name: 'Category' }),
        ).toHaveTextContent('All categories');
        expect(
            screen.getByRole('combobox', { name: 'Status' }),
        ).toHaveTextContent('All statuses');
        expect(
            screen.getByRole('checkbox', { name: "Can't produce" }),
        ).not.toBeChecked();
        expect(
            screen.queryByRole('button', { name: 'Clear filters' }),
        ).not.toBeInTheDocument();
    });

    it('seeds every control from the filters', () => {
        setup({
            search: 'denim',
            category: 'footwear',
            status: 'draft',
            cant_produce: true,
        });

        expect(screen.getByRole('searchbox')).toHaveValue('denim');
        expect(
            screen.getByRole('combobox', { name: 'Category' }),
        ).toHaveTextContent('Footwear');
        expect(
            screen.getByRole('combobox', { name: 'Status' }),
        ).toHaveTextContent('Draft');
        expect(
            screen.getByRole('checkbox', { name: "Can't produce" }),
        ).toBeChecked();
    });

    it('emits a category right away, keeping the other filters', async () => {
        const user = userEvent.setup();
        const { onChange } = setup({ ...noProductFilters, status: 'active' });

        await user.click(screen.getByRole('combobox', { name: 'Category' }));
        await user.click(screen.getByRole('option', { name: 'Footwear' }));

        expect(onChange).toHaveBeenCalledExactlyOnceWith({
            search: null,
            category: 'footwear',
            status: 'active',
            cant_produce: false,
        });
    });

    it('clears a category by picking "All categories"', async () => {
        const user = userEvent.setup();
        const { onChange } = setup({
            ...noProductFilters,
            category: 'footwear',
        });

        await user.click(screen.getByRole('combobox', { name: 'Category' }));
        await user.click(
            screen.getByRole('option', { name: 'All categories' }),
        );

        expect(onChange).toHaveBeenCalledExactlyOnceWith(noProductFilters);
    });

    it('emits a status right away', async () => {
        const user = userEvent.setup();
        const { onChange } = setup();

        await user.click(screen.getByRole('combobox', { name: 'Status' }));
        await user.click(screen.getByRole('option', { name: 'Archived' }));

        expect(onChange).toHaveBeenCalledExactlyOnceWith({
            ...noProductFilters,
            status: 'archived',
        });
    });

    it('toggles "Can\'t produce"', async () => {
        const user = userEvent.setup();
        const { onChange } = setup();

        await user.click(
            screen.getByRole('checkbox', { name: "Can't produce" }),
        );

        expect(onChange).toHaveBeenCalledExactlyOnceWith({
            ...noProductFilters,
            cant_produce: true,
        });
    });

    describe('search', () => {
        it('waits 300 ms after the last keystroke, then emits the trimmed text', () => {
            vi.useFakeTimers();

            const { onChange } = setup();
            const box = screen.getByRole('searchbox');

            fireEvent.change(box, { target: { value: 'den' } });
            act(() => {
                vi.advanceTimersByTime(200);
            });
            fireEvent.change(box, { target: { value: ' denim ' } });
            act(() => {
                vi.advanceTimersByTime(299);
            });

            expect(onChange).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(1);
            });

            expect(onChange).toHaveBeenCalledExactlyOnceWith({
                ...noProductFilters,
                search: 'denim',
            });
        });

        it('does not emit on first render or for text that did not change', () => {
            vi.useFakeTimers();

            const { onChange } = setup({
                ...noProductFilters,
                search: 'denim',
            });

            act(() => {
                vi.advanceTimersByTime(1000);
            });

            expect(onChange).not.toHaveBeenCalled();
        });

        it('emits null when the box is emptied', () => {
            vi.useFakeTimers();

            const { onChange } = setup({
                ...noProductFilters,
                search: 'denim',
            });

            fireEvent.change(screen.getByRole('searchbox'), {
                target: { value: '' },
            });
            act(() => {
                vi.advanceTimersByTime(300);
            });

            expect(onChange).toHaveBeenCalledExactlyOnceWith(noProductFilters);
        });

        it('adopts a search that changed from outside (the back button)', () => {
            const view = setup({ ...noProductFilters, search: 'denim' });

            rerenderWith(view, { ...noProductFilters, search: 'tote' });

            expect(screen.getByRole('searchbox')).toHaveValue('tote');
        });
    });

    describe('clear filters', () => {
        it('shows up once a filter is active and resets everything', async () => {
            const user = userEvent.setup();
            const { onChange } = setup({
                search: 'denim',
                category: 'apparel',
                status: 'active',
                cant_produce: true,
            });

            await user.click(
                screen.getByRole('button', { name: 'Clear filters' }),
            );

            expect(onChange).toHaveBeenCalledExactlyOnceWith(noProductFilters);
            expect(screen.getByRole('searchbox')).toHaveValue('');
        });

        it('shows up as soon as something is typed', () => {
            setup();

            fireEvent.change(screen.getByRole('searchbox'), {
                target: { value: 'd' },
            });

            expect(
                screen.getByRole('button', { name: 'Clear filters' }),
            ).toBeInTheDocument();
        });
    });
});
