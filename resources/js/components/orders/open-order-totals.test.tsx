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

    it('reads Open orders and To deliver with its units (INV-14 AC-5)', () => {
        const { pairs } = figures({ open_orders: 3, to_deliver: 73 });

        expect(pairs).toEqual(['Open orders 3', 'To deliver 73 units']);
    });

    it('reads "0 units" at zero and "1 unit" at one', () => {
        expect(figures({ open_orders: 0, to_deliver: 0 }).pairs).toEqual([
            'Open orders 0',
            'To deliver 0 units',
        ]);
    });

    it('sits side by side, also on a phone', () => {
        const { terms } = figures({ open_orders: 3, to_deliver: 73 });

        // term → figure wrapper → the grid
        expect(terms[0].parentElement?.parentElement).toHaveClass(
            'grid-cols-2',
        );
    });
});
