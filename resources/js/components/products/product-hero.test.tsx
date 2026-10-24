import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ProductHero } from '@/components/products/product-hero';
import { denimJacket } from '@/test/fixtures/products';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Capacity } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderHero(
    capacity: Capacity = denimJacket.capacity,
    multipleSizes = true,
) {
    return render(
        <ProductHero
            capacity={capacity}
            unitCost={denimJacket.cost.unit_cost}
            multipleSizes={multipleSizes}
            stock={null}
        />,
    );
}

function card(name: string): HTMLElement {
    return screen.getByRole('region', { name });
}

describe('<ProductHero>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('makes "Can produce now" the first, primary card', () => {
        renderHero();

        const production = within(card('Production capacity'));

        expect(production.getByText('Can produce now')).toBeInTheDocument();
        expect(production.getByText('112')).toBeInTheDocument();
        expect(production.getByText('units')).toBeInTheDocument();
        // A real space between the count and its word, not only a margin.
        expect(production.getByText('units').parentElement?.textContent).toBe(
            '112 units',
        );
        expect(card('Production capacity')).toHaveClass('bg-primary');
        expect(card('Unit cost')).not.toHaveClass('bg-primary');
    });

    it.each([
        [
            'several sizes',
            denimJacket.capacity,
            true,
            'from current stock, any mix of sizes',
        ],
        ['one size', denimJacket.capacity, false, 'from current stock'],
        [
            "can't produce",
            { ...denimJacket.capacity, max_producible: 0 },
            true,
            'with current stock',
        ],
        [
            'no composition',
            { max_producible: null, bottleneck: null },
            true,
            'Add materials to see capacity',
        ],
    ] satisfies [string, Capacity, boolean, string][])(
        'reads the capacity sub-line for %s',
        (_, capacity, multipleSizes, subLine) => {
            renderHero(capacity, multipleSizes);

            const production = within(card('Production capacity'));

            expect(production.getByText(subLine)).toBeInTheDocument();

            if (!subLine.includes('any mix of sizes')) {
                expect(production.queryByText(/any mix of sizes/)).toBeNull();
            }
        },
    );

    it('says "1 unit" for a single unit', () => {
        renderHero({ ...denimJacket.capacity, max_producible: 1 });

        expect(
            within(card('Production capacity')).getByText('unit'),
        ).toBeInTheDocument();
    });

    it('says "Can\'t produce" at zero, in words', () => {
        renderHero({ ...denimJacket.capacity, max_producible: 0 });

        const production = within(card('Production capacity'));

        expect(production.getByText("Can't produce")).toBeInTheDocument();
        expect(production.getByText('with current stock')).toBeInTheDocument();
        expect(production.queryByText('units')).not.toBeInTheDocument();
    });

    it('says "No composition" without one', () => {
        renderHero({ max_producible: null, bottleneck: null });

        const production = within(card('Production capacity'));

        expect(production.getByText('No composition')).toBeInTheDocument();
        expect(
            production.getByText('Add materials to see capacity'),
        ).toBeInTheDocument();
    });

    it('shows the unit cost of the worked example (35.7540 → €35.75)', () => {
        renderHero();

        // The currency is drawn at the size of "units", yet the figure reads as one text.
        expect(card('Unit cost')).toHaveTextContent('Unit cost€35.75');
        expect(within(card('Unit cost')).getByText('€')).toHaveClass('text-lg');
    });

    it.each([
        ['several sizes', true, 'same for every size'],
        ['one size', false, 'materials, labor and overhead'],
    ])('reads the unit cost sub-line for %s', (_, multipleSizes, subLine) => {
        renderHero(denimJacket.capacity, multipleSizes);

        expect(
            within(card('Unit cost')).getByText(subLine),
        ).toBeInTheDocument();
    });

    it('shows the unit cost with the installation currency (35.7540 → TND 35.754)', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        renderHero();

        expect(card('Unit cost')).toHaveTextContent('Unit costTND 35.754');
    });
});
