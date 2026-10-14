import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { CostBreakdownCard } from '@/components/products/cost-breakdown-card';
import {
    choreJacket,
    denimJacket,
    denimJacketBelowCost,
    denimJacketGiveaway,
    jacketWithoutComposition,
} from '@/test/fixtures/products';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { Product } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderCard(product: Product = denimJacket) {
    return render(
        <CostBreakdownCard
            cost={product.cost}
            linesCount={product.components.length}
            sellingPrice={product.selling_price}
            margin={product.margin}
            editHref={`/products/${product.id}/edit`}
        />,
    );
}

/** The value shown next to a label, e.g. the amount beside "Labor". */
function valueOf(label: string): HTMLElement {
    const term = screen.getByText(label, { selector: 'dt' });
    const value = term.nextElementSibling;

    if (!(value instanceof HTMLElement)) {
        throw new Error(`Expected a value next to "${label}".`);
    }

    return value;
}

describe('<CostBreakdownCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('breaks the unit cost into materials, labor and overhead', () => {
        renderCard();

        expect(
            screen.getByRole('region', { name: 'Cost breakdown' }),
        ).toBeInTheDocument();
        expect(
            valueOf('Materials (4 lines, waste included)'),
        ).toHaveTextContent('€20.25');
        expect(valueOf('Labor')).toHaveTextContent('€12.00');
        expect(valueOf('Overhead')).toHaveTextContent('€3.50');
        // The worked example: 20.2540 + 12.0000 + 3.5000.
        expect(valueOf('Unit cost')).toHaveTextContent('€35.75');
    });

    it('formats the unit cost with the installation currency (TND, 3 decimals)', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        renderCard();

        expect(valueOf('Unit cost')).toHaveTextContent('TND 35.754');
        expect(valueOf('Labor')).toHaveTextContent('TND 12.000');
        expect(valueOf('Selling price')).toHaveTextContent('TND 89.000');
        expect(valueOf('Margin')).toHaveTextContent('TND 53.246 · 59.83%');
    });

    it('keeps the Overhead row and shows a zero overhead as TND 0.000', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-14' },
        });
        renderCard({
            ...denimJacket,
            cost: { ...denimJacket.cost, overhead_cost: '0.0000' },
        });

        expect(valueOf('Overhead')).toHaveTextContent('TND 0.000');
    });

    it('says "1 line" for a single line', () => {
        render(
            <CostBreakdownCard
                cost={denimJacket.cost}
                linesCount={1}
                sellingPrice={null}
                margin={null}
                editHref="/products/1/edit"
            />,
        );

        expect(
            screen.getByText('Materials (1 line, waste included)'),
        ).toBeInTheDocument();
    });

    describe('with a selling price', () => {
        it('shows the price and the margin amount with its percentage', () => {
            renderCard();

            expect(valueOf('Selling price')).toHaveTextContent('€89.00');
            expect(valueOf('Margin')).toHaveTextContent('€53.25 · 59.83%');
            expect(
                screen.queryByText('No selling price set.', { exact: false }),
            ).not.toBeInTheDocument();
        });

        it('does not flag a positive margin', () => {
            renderCard();

            const margin = valueOf('Margin');

            expect(margin).not.toHaveTextContent('below cost');
            expect(margin.firstElementChild).not.toHaveClass(
                'text-danger-foreground',
            );
        });
    });

    describe('with a negative margin', () => {
        it('shows the minus sign and says "below cost" to screen readers, not by colour alone', () => {
            renderCard(denimJacketBelowCost);

            const margin = valueOf('Margin');

            expect(margin).toHaveTextContent('-€5.75 · -19.18%');
            expect(within(margin).getByText(', below cost')).toHaveClass(
                'sr-only',
            );
            expect(valueOf('Selling price')).toHaveTextContent('€30.00');
            expect(margin.firstElementChild).toHaveClass(
                'text-danger-foreground',
            );
        });
    });

    describe('with no percentage (a zero selling price)', () => {
        it('shows the margin amount only', () => {
            renderCard(denimJacketGiveaway);

            const margin = valueOf('Margin');

            expect(margin).toHaveTextContent('-€35.75');
            expect(margin).not.toHaveTextContent('%');
            expect(margin).not.toHaveTextContent('·');
            expect(valueOf('Selling price')).toHaveTextContent('€0.00');
        });
    });

    describe('without a selling price', () => {
        it('says so and links to the edit page', () => {
            renderCard(choreJacket);

            expect(valueOf('Selling price')).toHaveTextContent(
                'No selling price set.',
            );
            expect(
                screen.getByRole('link', { name: 'Set a price' }),
            ).toHaveAttribute('href', '/products/6/edit');
            expect(screen.queryByText('Margin')).not.toBeInTheDocument();
            expect(valueOf('Unit cost')).toHaveTextContent('€32.43');
        });
    });

    it('shows zero materials for a product without a composition', () => {
        renderCard(jacketWithoutComposition);

        expect(
            valueOf('Materials (0 lines, waste included)'),
        ).toHaveTextContent('€0.00');
        expect(valueOf('Unit cost')).toHaveTextContent('€17.54');
    });
});
