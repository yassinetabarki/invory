import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OpenOrderTotals } from '@/components/orders/open-order-totals';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { OpenOrderTotals as Totals } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function figures(totals: Totals) {
    render(<OpenOrderTotals totals={totals} />);

    const region = within(
        screen.getByRole('region', { name: 'Open orders totals' }),
    );
    const terms = region.getAllByRole('term');
    const definitions = region.getAllByRole('definition');

    return {
        terms,
        pairs: terms.map(
            (term, index) =>
                `${term.textContent} ${definitions[index].textContent}`,
        ),
    };
}

describe('<OpenOrderTotals>', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({});
    });

    it('reads the four totals with their units', () => {
        const { pairs } = figures({
            open_orders: 2,
            to_start: 170,
            in_cutting: 20,
            ready_to_ship: 10,
        });

        expect(pairs).toEqual([
            'Open orders 2',
            'To start 170 units',
            'In cutting 20 units',
            'Ready to ship 10 units',
        ]);
    });

    it('reads "0 units" at zero and "1 unit" at one', () => {
        const { pairs } = figures({
            open_orders: 0,
            to_start: 1,
            in_cutting: 0,
            ready_to_ship: 1,
        });

        expect(pairs).toEqual([
            'Open orders 0',
            'To start 1 unit',
            'In cutting 0 units',
            'Ready to ship 1 unit',
        ]);
    });

    it('sits in two rows of two on a phone and four columns from lg', () => {
        const { terms } = figures({
            open_orders: 2,
            to_start: 170,
            in_cutting: 20,
            ready_to_ship: 10,
        });

        // term → figure wrapper → the grid
        expect(terms[0].parentElement?.parentElement).toHaveClass(
            'grid-cols-2',
            'lg:grid-cols-4',
        );
    });
});
