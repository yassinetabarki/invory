import { act, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    afterEach,
    beforeAll,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { FiltersBar } from '@/components/shared/filters-bar';
import type { MaterialCategory, MaterialFilters, Option } from '@/types/domain';

const categories: Option<MaterialCategory>[] = [
    { value: 'fabric', label: 'Fabric' },
    { value: 'thread', label: 'Thread' },
    { value: 'trim', label: 'Trim' },
];

const baseFilters: MaterialFilters = {
    search: null,
    category: null,
    low_stock: false,
    sort: 'name',
};

// jsdom lacks the pointer-capture / scroll APIs Radix Select relies on.
beforeAll(() => {
    Element.prototype.hasPointerCapture = () => false;
    Element.prototype.setPointerCapture = () => undefined;
    Element.prototype.releasePointerCapture = () => undefined;
    Element.prototype.scrollIntoView = () => undefined;
});

function search(value: string) {
    fireEvent.change(screen.getByRole('searchbox'), { target: { value } });
}

function setup(filters: MaterialFilters = baseFilters) {
    const onChange = vi.fn();
    const user = userEvent.setup();
    const view = render(
        <FiltersBar
            filters={filters}
            categories={categories}
            onChange={onChange}
        />,
    );

    return { onChange, user, ...view };
}

describe('<FiltersBar>', () => {
    it('labels every control', () => {
        setup();

        expect(
            screen.getByRole('searchbox', { name: 'Search materials' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('combobox', { name: 'Category' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('combobox', { name: 'Sort by' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: 'Needs restock' }),
        ).toBeInTheDocument();
    });

    it('reflects the current filters', () => {
        setup({
            search: 'denim',
            category: 'fabric',
            low_stock: true,
            sort: '-unit_cost',
        });

        expect(screen.getByRole('searchbox')).toHaveValue('denim');
        expect(
            screen.getByRole('combobox', { name: 'Category' }),
        ).toHaveTextContent('Fabric');
        expect(
            screen.getByRole('combobox', { name: 'Sort by' }),
        ).toHaveTextContent('Unit cost high→low');
        expect(
            screen.getByRole('checkbox', { name: 'Needs restock' }),
        ).toBeChecked();
    });

    // Fake timers are scoped to the tests that need them: Testing Library's
    // async helpers (used by userEvent) hang when Vitest fakes setTimeout, so
    // these tests drive the inputs with fireEvent instead.
    describe('search', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('emits once, 300 ms after the last keystroke', () => {
            const { onChange } = setup();

            search('d');
            search('de');
            search('denim');

            expect(onChange).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(299);
            });
            expect(onChange).not.toHaveBeenCalled();

            act(() => {
                vi.advanceTimersByTime(1);
            });
            expect(onChange).toHaveBeenCalledOnce();
            expect(onChange).toHaveBeenCalledWith({
                ...baseFilters,
                search: 'denim',
            });
        });

        it('emits null when the search is cleared', () => {
            const { onChange } = setup({ ...baseFilters, search: 'denim' });

            search('');
            act(() => {
                vi.advanceTimersByTime(300);
            });

            expect(onChange).toHaveBeenCalledWith({
                ...baseFilters,
                search: null,
            });
        });

        it('treats whitespace-only input as no search', () => {
            const { onChange } = setup();

            search('   ');
            act(() => {
                vi.advanceTimersByTime(300);
            });

            expect(onChange).not.toHaveBeenCalled();
        });

        it('does not emit on mount', () => {
            const { onChange } = setup({ ...baseFilters, search: 'denim' });

            act(() => {
                vi.advanceTimersByTime(1000);
            });

            expect(onChange).not.toHaveBeenCalled();
        });
    });

    it('emits the category immediately, and null for "All categories"', async () => {
        const { onChange, user } = setup({
            ...baseFilters,
            category: 'fabric',
        });

        await user.click(screen.getByRole('combobox', { name: 'Category' }));
        await user.click(screen.getByRole('option', { name: 'Thread' }));

        expect(onChange).toHaveBeenLastCalledWith({
            ...baseFilters,
            category: 'thread',
        });

        await user.click(screen.getByRole('combobox', { name: 'Category' }));
        await user.click(
            screen.getByRole('option', { name: 'All categories' }),
        );

        expect(onChange).toHaveBeenLastCalledWith({
            ...baseFilters,
            category: null,
        });
    });

    it('lists the categories it was given plus "All categories"', async () => {
        const { user } = setup();

        await user.click(screen.getByRole('combobox', { name: 'Category' }));

        expect(
            screen.getAllByRole('option').map((option) => option.textContent),
        ).toEqual(['All categories', 'Fabric', 'Thread', 'Trim']);
    });

    it('emits "Needs restock" immediately', async () => {
        const { onChange, user } = setup();

        await user.click(
            screen.getByRole('checkbox', { name: 'Needs restock' }),
        );

        expect(onChange).toHaveBeenCalledWith({
            ...baseFilters,
            low_stock: true,
        });
    });

    it('emits low_stock false when "Needs restock" is unchecked', async () => {
        const { onChange, user } = setup({ ...baseFilters, low_stock: true });

        await user.click(
            screen.getByRole('checkbox', { name: 'Needs restock' }),
        );

        expect(onChange).toHaveBeenCalledWith({
            ...baseFilters,
            low_stock: false,
        });
    });

    it.each([
        ['Name Z–A', '-name'],
        ['SKU A–Z', 'sku'],
        ['SKU Z–A', '-sku'],
        ['Stock low→high', 'quantity_on_hand'],
        ['Stock high→low', '-quantity_on_hand'],
        ['Unit cost low→high', 'unit_cost'],
        ['Unit cost high→low', '-unit_cost'],
        ['Recently updated', '-updated_at'],
        ['Least recently updated', 'updated_at'],
    ])('maps the "%s" sort option to %s', async (label, value) => {
        const { onChange, user } = setup();

        await user.click(screen.getByRole('combobox', { name: 'Sort by' }));
        await user.click(screen.getByRole('option', { name: label }));

        expect(onChange).toHaveBeenCalledWith({ ...baseFilters, sort: value });
    });

    it('offers "Name A–Z" as the default sort', () => {
        setup();

        expect(
            screen.getByRole('combobox', { name: 'Sort by' }),
        ).toHaveTextContent('Name A–Z');
    });

    describe('clear filters', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('is hidden when no filter is active (sort is not a filter)', () => {
            setup({ ...baseFilters, sort: '-unit_cost' });

            expect(
                screen.queryByRole('button', { name: 'Clear filters' }),
            ).not.toBeInTheDocument();
        });

        it.each<[string, Partial<MaterialFilters>]>([
            ['a search', { search: 'denim' }],
            ['a category', { category: 'fabric' }],
            ['needs restock', { low_stock: true }],
        ])('is shown with %s', (_name, active) => {
            setup({ ...baseFilters, ...active });

            expect(
                screen.getByRole('button', { name: 'Clear filters' }),
            ).toBeInTheDocument();
        });

        it('resets search, category and needs restock but keeps the sort', () => {
            const { onChange } = setup({
                search: 'denim',
                category: 'fabric',
                low_stock: true,
                sort: '-unit_cost',
            });

            fireEvent.click(
                screen.getByRole('button', { name: 'Clear filters' }),
            );

            expect(onChange).toHaveBeenCalledWith({
                search: null,
                category: null,
                low_stock: false,
                sort: '-unit_cost',
            });
            expect(screen.getByRole('searchbox')).toHaveValue('');

            // The debounced search settling must not emit a second, stale change.
            onChange.mockClear();
            act(() => {
                vi.advanceTimersByTime(300);
            });
            expect(onChange).not.toHaveBeenCalled();
        });
    });

    describe('synchronising with the filters prop', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('adopts an external change of the search (e.g. browser back)', () => {
            const { rerender, onChange } = setup({
                ...baseFilters,
                search: 'denim',
            });

            rerender(
                <FiltersBar
                    filters={{ ...baseFilters, search: 'thread' }}
                    categories={categories}
                    onChange={onChange}
                />,
            );

            expect(screen.getByRole('searchbox')).toHaveValue('thread');

            act(() => {
                vi.advanceTimersByTime(300);
            });
            expect(onChange).not.toHaveBeenCalled();
        });

        it('does not overwrite text typed after a search was emitted when the server echoes it', () => {
            const { onChange, rerender } = setup();

            search('den');
            act(() => {
                vi.advanceTimersByTime(300);
            });
            expect(onChange).toHaveBeenCalledWith({
                ...baseFilters,
                search: 'den',
            });

            // The user keeps typing while the request is in flight...
            search('denim');

            // ...then the server echoes the earlier search back.
            rerender(
                <FiltersBar
                    filters={{ ...baseFilters, search: 'den' }}
                    categories={categories}
                    onChange={onChange}
                />,
            );

            expect(screen.getByRole('searchbox')).toHaveValue('denim');
        });
    });
});
